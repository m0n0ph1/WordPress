<?php

    function _wp_post_revision_fields($post = [], $deprecated = false)
    {
        static $fields = null;

        if(! is_array($post))
        {
            $post = get_post($post, ARRAY_A);
        }

        if(is_null($fields))
        {
            // Allow these to be versioned.
            $fields = [
                'post_title' => __('Title'),
                'post_content' => __('Content'),
                'post_excerpt' => __('Excerpt'),
            ];
        }

        $fields = apply_filters('_wp_post_revision_fields', $fields, $post);

        // WP uses these internally either in versioning or elsewhere - they cannot be versioned.
        foreach(
            [
                'ID',
                'post_name',
                'post_parent',
                'post_date',
                'post_date_gmt',
                'post_status',
                'post_type',
                'comment_count',
                'post_author'
            ] as $protect
        )
        {
            unset($fields[$protect]);
        }

        return $fields;
    }

    function _wp_post_revision_data($post = [], $autosave = false)
    {
        if(! is_array($post))
        {
            $post = get_post($post, ARRAY_A);
        }

        $fields = _wp_post_revision_fields($post);

        $revision_data = [];

        foreach(array_intersect(array_keys($post), array_keys($fields)) as $field)
        {
            $revision_data[$field] = $post[$field];
        }

        $revision_data['post_parent'] = $post['ID'];
        $revision_data['post_status'] = 'inherit';
        $revision_data['post_type'] = 'revision';
        $revision_data['post_name'] = $autosave ? "$post[ID]-autosave-v1" : "$post[ID]-revision-v1"; // "1" is the revisioning system version.
        $revision_data['post_date'] = isset($post['post_modified']) ? $post['post_modified'] : '';
        $revision_data['post_date_gmt'] = isset($post['post_modified_gmt']) ? $post['post_modified_gmt'] : '';

        return $revision_data;
    }

    function wp_save_post_revision($post_id)
    {
        if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        {
            return;
        }

        $post = get_post($post_id);

        if(! $post || ! post_type_supports($post->post_type, 'revisions') || 'auto-draft' === $post->post_status || ! wp_revisions_enabled($post))
        {
            return;
        }

        /*
         * Compare the proposed update with the last stored revision verifying that
         * they are different, unless a plugin tells us to always save regardless.
         * If no previous revisions, save one.
         */
        $revisions = wp_get_post_revisions($post_id);
        if($revisions)
        {
            // Grab the latest revision, but not an autosave.
            foreach($revisions as $revision)
            {
                if(str_contains($revision->post_name, "{$revision->post_parent}-revision"))
                {
                    $latest_revision = $revision;
                    break;
                }
            }

            if(isset($latest_revision) && apply_filters('wp_save_post_revision_check_for_changes', true, $latest_revision, $post))
            {
                $post_has_changed = false;

                foreach(array_keys(_wp_post_revision_fields($post)) as $field)
                {
                    if(normalize_whitespace($post->$field) !== normalize_whitespace($latest_revision->$field))
                    {
                        $post_has_changed = true;
                        break;
                    }
                }

                $post_has_changed = (bool) apply_filters('wp_save_post_revision_post_has_changed', $post_has_changed, $latest_revision, $post);

                // Don't save revision if post unchanged.
                if(! $post_has_changed)
                {
                    return;
                }
            }
        }

        $return = _wp_put_post_revision($post);

        /*
         * If a limit for the number of revisions to keep has been set,
         * delete the oldest ones.
         */
        $revisions_to_keep = wp_revisions_to_keep($post);

        if($revisions_to_keep < 0)
        {
            return $return;
        }

        $revisions = wp_get_post_revisions($post_id, ['order' => 'ASC']);

        $revisions = apply_filters('wp_save_post_revision_revisions_before_deletion', $revisions, $post_id);

        $delete = count($revisions) - $revisions_to_keep;

        if($delete < 1)
        {
            return $return;
        }

        $revisions = array_slice($revisions, 0, $delete);

        for($i = 0; isset($revisions[$i]); $i++)
        {
            if(str_contains($revisions[$i]->post_name, 'autosave'))
            {
                continue;
            }

            wp_delete_post_revision($revisions[$i]->ID);
        }

        return $return;
    }

    function wp_get_post_autosave($post_id, $user_id = 0)
    {
        global $wpdb;

        $autosave_name = $post_id.'-autosave-v1';
        $user_id_query = (0 !== $user_id) ? "AND post_author = $user_id" : null;

        // Construct the autosave query.
        $autosave_query = "
		SELECT *
		FROM $wpdb->posts
		WHERE post_parent = %d
		AND post_type = 'revision'
		AND post_status = 'inherit'
		AND post_name   = %s ".$user_id_query.'
		ORDER BY post_date DESC
		LIMIT 1';

        $autosave = $wpdb->get_results($wpdb->prepare($autosave_query, $post_id, $autosave_name));

        if(! $autosave)
        {
            return false;
        }

        return get_post($autosave[0]);
    }

    function wp_is_post_revision($post)
    {
        $post = wp_get_post_revision($post);

        if(! $post)
        {
            return false;
        }

        return (int) $post->post_parent;
    }

    function wp_is_post_autosave($post)
    {
        $post = wp_get_post_revision($post);

        if(! $post)
        {
            return false;
        }

        if(str_contains($post->post_name, "{$post->post_parent}-autosave"))
        {
            return (int) $post->post_parent;
        }

        return false;
    }

    function _wp_put_post_revision($post = null, $autosave = false)
    {
        if(is_object($post))
        {
            $post = get_object_vars($post);
        }
        elseif(! is_array($post))
        {
            $post = get_post($post, ARRAY_A);
        }

        if(! $post || empty($post['ID']))
        {
            return new WP_Error('invalid_post', __('Invalid post ID.'));
        }

        if(isset($post['post_type']) && 'revision' === $post['post_type'])
        {
            return new WP_Error('post_type', __('Cannot create a revision of a revision'));
        }

        $post = _wp_post_revision_data($post, $autosave);
        $post = wp_slash($post); // Since data is from DB.

        $revision_id = wp_insert_post($post, true);
        if(is_wp_error($revision_id))
        {
            return $revision_id;
        }

        if($revision_id)
        {
            do_action('_wp_put_post_revision', $revision_id);
        }

        return $revision_id;
    }

    function wp_get_post_revision(&$post, $output = OBJECT, $filter = 'raw')
    {
        $revision = get_post($post, OBJECT, $filter);

        if(! $revision)
        {
            return $revision;
        }

        if('revision' !== $revision->post_type)
        {
            return null;
        }

        if(OBJECT === $output)
        {
        }
        elseif(ARRAY_A === $output)
        {
            $_revision = get_object_vars($revision);

            return $_revision;
        }
        elseif(ARRAY_N === $output)
        {
            $_revision = array_values(get_object_vars($revision));

            return $_revision;
        }

        return $revision;
    }

    function wp_restore_post_revision($revision, $fields = null)
    {
        $revision = wp_get_post_revision($revision, ARRAY_A);

        if(! $revision)
        {
            return $revision;
        }

        if(! is_array($fields))
        {
            $fields = array_keys(_wp_post_revision_fields($revision));
        }

        $update = [];
        foreach(array_intersect(array_keys($revision), $fields) as $field)
        {
            $update[$field] = $revision[$field];
        }

        if(! $update)
        {
            return false;
        }

        $update['ID'] = $revision['post_parent'];

        $update = wp_slash($update); // Since data is from DB.

        $post_id = wp_update_post($update);

        if(! $post_id || is_wp_error($post_id))
        {
            return $post_id;
        }

        // Update last edit user.
        update_post_meta($post_id, '_edit_last', get_current_user_id());

        do_action('wp_restore_post_revision', $post_id, $revision['ID']);

        return $post_id;
    }

    function wp_delete_post_revision($revision)
    {
        $revision = wp_get_post_revision($revision);

        if(! $revision)
        {
            return $revision;
        }

        $delete = wp_delete_post($revision->ID);

        if($delete)
        {
            do_action('wp_delete_post_revision', $revision->ID, $revision);
        }

        return $delete;
    }

    function wp_get_post_revisions($post = 0, $args = null)
    {
        $post = get_post($post);

        if(! $post || empty($post->ID))
        {
            return [];
        }

        $defaults = [
            'order' => 'DESC',
            'orderby' => 'date ID',
            'check_enabled' => true,
        ];
        $args = wp_parse_args($args, $defaults);

        if($args['check_enabled'] && ! wp_revisions_enabled($post))
        {
            return [];
        }

        $args = array_merge($args, [
            'post_parent' => $post->ID,
            'post_type' => 'revision',
            'post_status' => 'inherit',
        ]);

        $revisions = get_children($args);

        if(! $revisions)
        {
            return [];
        }

        return $revisions;
    }

    function wp_get_latest_revision_id_and_total_count($post = 0)
    {
        $post = get_post($post);

        if(! $post)
        {
            return new WP_Error('invalid_post', __('Invalid post.'));
        }

        if(! wp_revisions_enabled($post))
        {
            return new WP_Error('revisions_not_enabled', __('Revisions not enabled.'));
        }

        $args = [
            'post_parent' => $post->ID,
            'fields' => 'ids',
            'post_type' => 'revision',
            'post_status' => 'inherit',
            'order' => 'DESC',
            'orderby' => 'date ID',
            'posts_per_page' => 1,
            'ignore_sticky_posts' => true,
        ];

        $revision_query = new WP_Query();
        $revisions = $revision_query->query($args);

        if(! $revisions)
        {
            return [
                'latest_id' => 0,
                'count' => 0,
            ];
        }

        return [
            'latest_id' => $revisions[0],
            'count' => $revision_query->found_posts,
        ];
    }

    function wp_get_post_revisions_url($post = 0)
    {
        $post = get_post($post);

        if(! $post instanceof WP_Post)
        {
            return null;
        }

        // If the post is a revision, return early.
        if('revision' === $post->post_type)
        {
            return get_edit_post_link($post);
        }

        if(! wp_revisions_enabled($post))
        {
            return null;
        }

        $revisions = wp_get_latest_revision_id_and_total_count($post->ID);

        if(is_wp_error($revisions) || 0 === $revisions['count'])
        {
            return null;
        }

        return get_edit_post_link($revisions['latest_id']);
    }

    function wp_revisions_enabled($post)
    {
        return wp_revisions_to_keep($post) !== 0;
    }

    function wp_revisions_to_keep($post)
    {
        $num = WP_POST_REVISIONS;

        if(true === $num)
        {
            $num = -1;
        }
        else
        {
            $num = (int) $num;
        }

        if(! post_type_supports($post->post_type, 'revisions'))
        {
            $num = 0;
        }

        $num = apply_filters('wp_revisions_to_keep', $num, $post);

        $num = apply_filters("wp_{$post->post_type}_revisions_to_keep", $num, $post);

        return (int) $num;
    }

    function _set_preview($post)
    {
        if(! is_object($post))
        {
            return $post;
        }

        $preview = wp_get_post_autosave($post->ID);

        if(is_object($preview))
        {
            $preview = sanitize_post($preview);

            $post->post_content = $preview->post_content;
            $post->post_title = $preview->post_title;
            $post->post_excerpt = $preview->post_excerpt;
        }

        add_filter('get_the_terms', '_wp_preview_terms_filter', 10, 3);
        add_filter('get_post_metadata', '_wp_preview_post_thumbnail_filter', 10, 3);

        return $post;
    }

    function _show_post_preview()
    {
        if(isset($_GET['preview_id']) && isset($_GET['preview_nonce']))
        {
            $id = (int) $_GET['preview_id'];

            if(false === wp_verify_nonce($_GET['preview_nonce'], 'post_preview_'.$id))
            {
                wp_die(__('Sorry, you are not allowed to preview drafts.'), 403);
            }

            add_filter('the_preview', '_set_preview');
        }
    }

    function _wp_preview_terms_filter($terms, $post_id, $taxonomy)
    {
        $post = get_post();

        if(! $post || empty($_REQUEST['post_format']) || $post->ID !== $post_id || 'post_format' !== $taxonomy || 'revision' === $post->post_type)
        {
            return $terms;
        }

        if('standard' === $_REQUEST['post_format'])
        {
            $terms = [];
        }
        else
        {
            $term = get_term_by('slug', 'post-format-'.sanitize_key($_REQUEST['post_format']), 'post_format');

            if($term)
            {
                $terms = [$term]; // Can only have one post format.
            }
        }

        return $terms;
    }

    function _wp_preview_post_thumbnail_filter($value, $post_id, $meta_key)
    {
        $post = get_post();

        if(! $post || empty($_REQUEST['_thumbnail_id']) || empty($_REQUEST['preview_id']) || $post->ID !== $post_id || $post_id !== (int) $_REQUEST['preview_id'] || '_thumbnail_id' !== $meta_key || 'revision' === $post->post_type)
        {
            return $value;
        }

        $thumbnail_id = (int) $_REQUEST['_thumbnail_id'];

        if($thumbnail_id <= 0)
        {
            return '';
        }

        return (string) $thumbnail_id;
    }

    function _wp_get_post_revision_version($revision)
    {
        if(is_object($revision))
        {
            $revision = get_object_vars($revision);
        }
        elseif(! is_array($revision))
        {
            return false;
        }

        if(preg_match('/^\d+-(?:autosave|revision)-v(\d+)$/', $revision['post_name'], $matches))
        {
            return (int) $matches[1];
        }

        return 0;
    }

    function _wp_upgrade_revisions_of_post($post, $revisions)
    {
        global $wpdb;

        // Add post option exclusively.
        $lock = "revision-upgrade-{$post->ID}";
        $now = time();
        $result = $wpdb->query($wpdb->prepare("INSERT IGNORE INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, 'no') /* LOCK */", $lock, $now));

        if(! $result)
        {
            // If we couldn't get a lock, see how old the previous lock is.
            $locked = get_option($lock);

            if(! $locked || $locked > $now - HOUR_IN_SECONDS)
            {
                // Lock is not too old: some other process may be upgrading this post. Bail.
                return false;
            }
            // Lock is too old - update it (below) and continue.
        }

        // If we could get a lock, re-"add" the option to fire all the correct filters.
        update_option($lock, $now);

        reset($revisions);
        $add_last = true;

        do
        {
            $this_revision = current($revisions);
            $prev_revision = next($revisions);

            $this_revision_version = _wp_get_post_revision_version($this_revision);

            // Something terrible happened.
            if(false === $this_revision_version)
            {
                continue;
            }

            /*
             * 1 is the latest revision version, so we're already up to date.
             * No need to add a copy of the post as latest revision.
             */
            if(0 < $this_revision_version)
            {
                $add_last = false;
                continue;
            }

            // Always update the revision version.
            $update = [
                'post_name' => preg_replace('/^(\d+-(?:autosave|revision))[\d-]*$/', '$1-v1', $this_revision->post_name),
            ];

            /*
             * If this revision is the oldest revision of the post, i.e. no $prev_revision,
             * the correct post_author is probably $post->post_author, but that's only a good guess.
             * Update the revision version only and Leave the author as-is.
             */
            if($prev_revision)
            {
                $prev_revision_version = _wp_get_post_revision_version($prev_revision);

                // If the previous revision is already up to date, it no longer has the information we need :(
                if($prev_revision_version < 1)
                {
                    $update['post_author'] = $prev_revision->post_author;
                }
            }

            // Upgrade this revision.
            $result = $wpdb->update($wpdb->posts, $update, ['ID' => $this_revision->ID]);

            if($result)
            {
                wp_cache_delete($this_revision->ID, 'posts');
            }
        }
        while($prev_revision);

        delete_option($lock);

        // Add a copy of the post as latest revision.
        if($add_last)
        {
            wp_save_post_revision($post->ID);
        }

        return true;
    }
