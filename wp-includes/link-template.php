<?php /** @noinspection ALL */
    /** @noinspection ALL */
    /** @noinspection ALL */
    /** @noinspection ALL */
    /** @noinspection ALL */
    /** @noinspection ALL */
    /** @noinspection ALL */
    /** @noinspection ALL */
    /** @noinspection ALL */
    /** @noinspection ALL */
    /** @noinspection ALL */
    /** @noinspection ALL */
    /** @noinspection ALL */
    /** @noinspection ALL */
    /** @noinspection ALL */
    /** @noinspection ALL */

    function the_permalink($post = 0)
    {
        echo esc_url(apply_filters('the_permalink', get_permalink($post), $post));
    }

    function user_trailingslashit($url, $type_of_url = '')
    {
        global $wp_rewrite;
        if($wp_rewrite->use_trailing_slashes)
        {
            $url = trailingslashit($url);
        }
        else
        {
            $url = untrailingslashit($url);
        }

        return apply_filters('user_trailingslashit', $url, $type_of_url);
    }

    function permalink_anchor($mode = 'id')
    {
        $post = get_post();
        switch(strtolower($mode))
        {
            case 'title':
                $title = sanitize_title($post->post_title).'-'.$post->ID;
                echo '<a id="'.$title.'"></a>';
                break;
            case 'id':
            default:
                echo '<a id="post-'.$post->ID.'"></a>';
                break;
        }
    }

    function wp_force_plain_post_permalink($post = null, $sample = null)
    {
        if(null === $sample && is_object($post) && isset($post->filter) && 'sample' === $post->filter)
        {
            $sample = true;
        }
        else
        {
            $post = get_post($post);
            $sample = null !== $sample ? $sample : false;
        }

        if(! $post)
        {
            return true;
        }

        $post_status_obj = get_post_status_object(get_post_status($post));
        $post_type_obj = get_post_type_object(get_post_type($post));

        if(! $post_status_obj || ! $post_type_obj)
        {
            return true;
        }

        if(// Publicly viewable links never have plain permalinks.
            is_post_status_viewable($post_status_obj) || (// Private posts don't have plain permalinks if the user can read them.
                $post_status_obj->private && current_user_can('read_post', $post->ID)) || // Protected posts don't have plain links if getting a sample URL.
            ($post_status_obj->protected && $sample)
        )
        {
            return false;
        }

        return true;
    }

    function get_the_permalink($post = 0, $leavename = false)
    {
        return get_permalink($post, $leavename);
    }

    function get_permalink($post = 0, $leavename = false)
    {
        $rewritecode = [
            '%year%',
            '%monthnum%',
            '%day%',
            '%hour%',
            '%minute%',
            '%second%',
            $leavename ? '' : '%postname%',
            '%post_id%',
            '%category%',
            '%author%',
            $leavename ? '' : '%pagename%',
        ];

        if(is_object($post) && isset($post->filter) && 'sample' === $post->filter)
        {
            $sample = true;
        }
        else
        {
            $post = get_post($post);
            $sample = false;
        }

        /** @noinspection NativeMemberUsageInspection */
        if(empty($post->ID))
        {
            return false;
        }

        /** @noinspection NativeMemberUsageInspection */
        if('page' === $post->post_type)
        {
            return get_page_link($post, $leavename, $sample);
        }
        /** @noinspection NativeMemberUsageInspection */
        elseif('attachment' === $post->post_type)
        {
            return get_attachment_link($post, $leavename);
        }
        /** @noinspection NativeMemberUsageInspection */
        elseif(in_array($post->post_type, get_post_types(['_builtin' => false]), true))
        {
            return get_post_permalink($post, $leavename, $sample);
        }

        $permalink = get_option('permalink_structure');

        $permalink = apply_filters('pre_post_link', $permalink, $post, $leavename);

        if($permalink && ! wp_force_plain_post_permalink($post))
        {
            $category = '';
            if(str_contains($permalink, '%category%'))
            {
                /** @noinspection NativeMemberUsageInspection */
                $cats = get_the_category($post->ID);
                if($cats)
                {
                    $cats = wp_list_sort($cats, [
                        'term_id' => 'ASC',
                    ]);

                    $category_object = apply_filters('post_link_category', $cats[0], $cats, $post);

                    $category_object = get_term($category_object, 'category');
                    $category = $category_object->slug;
                    if($category_object->parent)
                    {
                        $category = get_category_parents($category_object->parent, false, '/', true).$category;
                    }
                }
                /*
			 * Show default category in permalinks,
			 * without having to assign it explicitly.
			 */
                if(empty($category))
                {
                    $default_category = get_term(get_option('default_category'), 'category');
                    if($default_category && ! is_wp_error($default_category))
                    {
                        $category = $default_category->slug;
                    }
                }
            }

            $author = '';
            if(str_contains($permalink, '%author%'))
            {
                /** @noinspection NativeMemberUsageInspection */
                $authordata = get_userdata($post->post_author);
                $author = $authordata->user_nicename;
            }

            /*
		 * This is not an API call because the permalink is based on the stored post_date value,
		 * which should be parsed as local time regardless of the default PHP timezone.
		 */
            /** @noinspection NativeMemberUsageInspection */
            $date = explode(' ', str_replace(['-', ':'], ' ', $post->post_date));

            /** @noinspection NativeMemberUsageInspection */
            /** @noinspection NativeMemberUsageInspection */
            /** @noinspection NativeMemberUsageInspection */
            $rewritereplace = [
                $date[0],
                $date[1],
                $date[2],
                $date[3],
                $date[4],
                $date[5],
                $post->post_name,
                $post->ID,
                $category,
                $author,
                $post->post_name,
            ];

            $permalink = home_url(str_replace($rewritecode, $rewritereplace, $permalink));
            $permalink = user_trailingslashit($permalink, 'single');
        }
        else
        { // If they're not using the fancy permalink option.
            /** @noinspection NativeMemberUsageInspection */
            $permalink = home_url('?p='.$post->ID);
        }

        return apply_filters('post_link', $permalink, $post, $leavename);
    }

    function get_post_permalink($post = 0, $leavename = false, $sample = false)
    {
        global $wp_rewrite;

        $post = get_post($post);

        if(! $post)
        {
            return false;
        }

        $post_link = $wp_rewrite->get_extra_permastruct($post->post_type);

        $slug = $post->post_name;

        $force_plain_link = wp_force_plain_post_permalink($post);

        $post_type = get_post_type_object($post->post_type);

        if($post_type->hierarchical)
        {
            $slug = get_page_uri($post);
        }

        if(! empty($post_link) && (! $force_plain_link || $sample))
        {
            if(! $leavename)
            {
                $post_link = str_replace("%$post->post_type%", $slug, $post_link);
            }
            $post_link = home_url(user_trailingslashit($post_link));
        }
        else
        {
            if($post_type->query_var && (isset($post->post_status) && ! $force_plain_link))
            {
                $post_link = add_query_arg($post_type->query_var, $slug, '');
            }
            else
            {
                $post_link = add_query_arg([
                                               'post_type' => $post->post_type,
                                               'p' => $post->ID,
                                           ], '');
            }
            $post_link = home_url($post_link);
        }

        return apply_filters('post_type_link', $post_link, $post, $leavename, $sample);
    }

    function get_page_link($post = false, $leavename = false, $sample = false)
    {
        $post = get_post($post);

        if('page' === get_option('show_on_front') && get_option('page_on_front') == $post->ID)
        {
            $link = home_url('/');
        }
        else
        {
            $link = _get_page_link($post, $leavename, $sample);
        }

        return apply_filters('page_link', $link, $post->ID, $sample);
    }

    function _get_page_link($post = false, $leavename = false, $sample = false)
    {
        global $wp_rewrite;

        $post = get_post($post);

        $force_plain_link = wp_force_plain_post_permalink($post);

        $link = $wp_rewrite->get_page_permastruct();

        if(! empty($link) && ((isset($post->post_status) && ! $force_plain_link) || $sample))
        {
            if(! $leavename)
            {
                $link = str_replace('%pagename%', get_page_uri($post), $link);
            }

            $link = home_url($link);
            $link = user_trailingslashit($link, 'page');
        }
        else
        {
            $link = home_url('?page_id='.$post->ID);
        }

        return apply_filters('_get_page_link', $link, $post->ID);
    }

    function get_attachment_link($post = null, $leavename = false)
    {
        global $wp_rewrite;

        $link = false;

        $post = get_post($post);
        $force_plain_link = wp_force_plain_post_permalink($post);
        $parent_id = $post->post_parent;
        $parent = $parent_id ? get_post($parent_id) : false;
        $parent_valid = true; // Default for no parent.
        if($parent_id && ($post->post_parent === $post->ID || ! $parent || ! is_post_type_viewable(get_post_type($parent))))
        {
            // Post is either its own parent or parent post unavailable.
            $parent_valid = false;
        }

        if($force_plain_link || ! $parent_valid)
        {
            $link = false;
        }
        elseif($wp_rewrite->using_permalinks() && $parent)
        {
            if('page' === $parent->post_type)
            {
                $parentlink = _get_page_link($post->post_parent); // Ignores page_on_front.
            }
            else
            {
                $parentlink = get_permalink($post->post_parent);
            }

            if(is_numeric($post->post_name) || str_contains(get_option('permalink_structure'), '%category%'))
            {
                $name = 'attachment/'.$post->post_name; // <permalink>/<int>/ is paged so we use the explicit attachment marker.
            }
            else
            {
                $name = $post->post_name;
            }

            if(! str_contains($parentlink, '?'))
            {
                $link = user_trailingslashit(trailingslashit($parentlink).'%postname%');
            }

            if(! $leavename)
            {
                $link = str_replace('%postname%', $name, $link);
            }
        }
        elseif($wp_rewrite->using_permalinks() && ! $leavename)
        {
            $link = home_url(user_trailingslashit($post->post_name));
        }

        if(! $link)
        {
            $link = home_url('/?attachment_id='.$post->ID);
        }

        return apply_filters('attachment_link', $link, $post->ID);
    }

    function get_year_link($year)
    {
        global $wp_rewrite;
        if(! $year)
        {
            $year = current_time('Y');
        }
        $yearlink = $wp_rewrite->get_year_permastruct();
        if(! empty($yearlink))
        {
            $yearlink = str_replace('%year%', $year, $yearlink);
            $yearlink = home_url(user_trailingslashit($yearlink, 'year'));
        }
        else
        {
            $yearlink = home_url('?m='.$year);
        }

        return apply_filters('year_link', $yearlink, $year);
    }

    function get_month_link($year, $month)
    {
        global $wp_rewrite;
        if(! $year)
        {
            $year = current_time('Y');
        }
        if(! $month)
        {
            $month = current_time('m');
        }
        $monthlink = $wp_rewrite->get_month_permastruct();
        if(! empty($monthlink))
        {
            $monthlink = str_replace('%year%', $year, $monthlink);
            $monthlink = str_replace('%monthnum%', zeroise((int) $month, 2), $monthlink);
            $monthlink = home_url(user_trailingslashit($monthlink, 'month'));
        }
        else
        {
            $monthlink = home_url('?m='.$year.zeroise($month, 2));
        }

        return apply_filters('month_link', $monthlink, $year, $month);
    }

    function get_day_link($year, $month, $day)
    {
        global $wp_rewrite;
        if(! $year)
        {
            $year = current_time('Y');
        }
        if(! $month)
        {
            $month = current_time('m');
        }
        if(! $day)
        {
            $day = current_time('j');
        }

        $daylink = $wp_rewrite->get_day_permastruct();
        if(! empty($daylink))
        {
            $daylink = str_replace('%year%', $year, $daylink);
            $daylink = str_replace('%monthnum%', zeroise((int) $month, 2), $daylink);
            $daylink = str_replace('%day%', zeroise((int) $day, 2), $daylink);
            $daylink = home_url(user_trailingslashit($daylink, 'day'));
        }
        else
        {
            $daylink = home_url('?m='.$year.zeroise($month, 2).zeroise($day, 2));
        }

        return apply_filters('day_link', $daylink, $year, $month, $day);
    }

    function the_feed_link($anchor, $feed = '')
    {
        $link = '<a href="'.esc_url(get_feed_link($feed)).'">'.$anchor.'</a>';

        echo apply_filters('the_feed_link', $link, $feed);
    }

    function get_feed_link($feed = '')
    {
        global $wp_rewrite;

        $permalink = $wp_rewrite->get_feed_permastruct();

        if($permalink)
        {
            if(str_contains($feed, 'comments_'))
            {
                $feed = str_replace('comments_', '', $feed);
                $permalink = $wp_rewrite->get_comment_feed_permastruct();
            }

            if(get_default_feed() == $feed)
            {
                $feed = '';
            }

            $permalink = str_replace('%feed%', $feed, $permalink);
            $permalink = preg_replace('#/+#', '/', "/$permalink");
            $output = home_url(user_trailingslashit($permalink, 'feed'));
        }
        else
        {
            if(empty($feed))
            {
                $feed = get_default_feed();
            }

            if(str_contains($feed, 'comments_'))
            {
                $feed = str_replace('comments_', 'comments-', $feed);
            }

            $output = home_url("?feed={$feed}");
        }

        return apply_filters('feed_link', $output, $feed);
    }

    function get_post_comments_feed_link($post_id = 0, $feed = '')
    {
        $post_id = absint($post_id);

        if(! $post_id)
        {
            $post_id = get_the_ID();
        }

        if(empty($feed))
        {
            $feed = get_default_feed();
        }

        $post = get_post($post_id);

        // Bail out if the post does not exist.
        if(! $post instanceof WP_Post)
        {
            return '';
        }

        $unattached = 'attachment' === $post->post_type && 0 === (int) $post->post_parent;

        if(get_option('permalink_structure'))
        {
            if('page' === get_option('show_on_front') && get_option('page_on_front') == $post_id)
            {
                $url = _get_page_link($post_id);
            }
            else
            {
                $url = get_permalink($post_id);
            }

            if($unattached)
            {
                $url = home_url('/feed/');
                if(get_default_feed() !== $feed)
                {
                    $url .= "$feed/";
                }
                $url = add_query_arg('attachment_id', $post_id, $url);
            }
            else
            {
                $url = trailingslashit($url).'feed';
                if(get_default_feed() != $feed)
                {
                    $url .= "/$feed";
                }
                $url = user_trailingslashit($url, 'single_feed');
            }
        }
        else
        {
            if($unattached)
            {
                $url = add_query_arg([
                                         'feed' => $feed,
                                         'attachment_id' => $post_id,
                                     ], home_url('/'));
            }
            elseif('page' === $post->post_type)
            {
                $url = add_query_arg([
                                         'feed' => $feed,
                                         'page_id' => $post_id,
                                     ], home_url('/'));
            }
            else
            {
                $url = add_query_arg([
                                         'feed' => $feed,
                                         'p' => $post_id,
                                     ], home_url('/'));
            }
        }

        return apply_filters('post_comments_feed_link', $url);
    }

    function post_comments_feed_link($link_text = '', $post_id = '', $feed = '')
    {
        $url = get_post_comments_feed_link($post_id, $feed);
        if(empty($link_text))
        {
            $link_text = __('Comments Feed');
        }

        $link = '<a href="'.esc_url($url).'">'.$link_text.'</a>';

        echo apply_filters('post_comments_feed_link_html', $link, $post_id, $feed);
    }

    function get_author_feed_link($author_id, $feed = '')
    {
        $author_id = (int) $author_id;
        $permalink_structure = get_option('permalink_structure');

        if(empty($feed))
        {
            $feed = get_default_feed();
        }

        if(! $permalink_structure)
        {
            $link = home_url("?feed=$feed&amp;author=".$author_id);
        }
        else
        {
            $link = get_author_posts_url($author_id);
            if(get_default_feed() == $feed)
            {
                $feed_link = 'feed';
            }
            else
            {
                $feed_link = "feed/$feed";
            }

            $link = trailingslashit($link).user_trailingslashit($feed_link, 'feed');
        }

        $link = apply_filters('author_feed_link', $link, $feed);

        return $link;
    }

    function get_category_feed_link($cat, $feed = '')
    {
        return get_term_feed_link($cat, 'category', $feed);
    }

    function get_term_feed_link($term, $taxonomy = '', $feed = '')
    {
        if(! is_object($term))
        {
            $term = (int) $term;
        }

        $term = get_term($term, $taxonomy);

        if(empty($term) || is_wp_error($term))
        {
            return false;
        }

        $taxonomy = $term->taxonomy;

        if(empty($feed))
        {
            $feed = get_default_feed();
        }

        $permalink_structure = get_option('permalink_structure');

        if(! $permalink_structure)
        {
            if('category' === $taxonomy)
            {
                $link = home_url("?feed=$feed&amp;cat=$term->term_id");
            }
            elseif('post_tag' === $taxonomy)
            {
                $link = home_url("?feed=$feed&amp;tag=$term->slug");
            }
            else
            {
                $t = get_taxonomy($taxonomy);
                $link = home_url("?feed=$feed&amp;$t->query_var=$term->slug");
            }
        }
        else
        {
            $link = get_term_link($term, $term->taxonomy);
            if(get_default_feed() == $feed)
            {
                $feed_link = 'feed';
            }
            else
            {
                $feed_link = "feed/$feed";
            }

            $link = trailingslashit($link).user_trailingslashit($feed_link, 'feed');
        }

        if('category' === $taxonomy)
        {
            $link = apply_filters('category_feed_link', $link, $feed);
        }
        elseif('post_tag' === $taxonomy)
        {
            $link = apply_filters('tag_feed_link', $link, $feed);
        }
        else
        {
            $link = apply_filters('taxonomy_feed_link', $link, $feed, $taxonomy);
        }

        return $link;
    }

    function get_tag_feed_link($tag, $feed = '')
    {
        return get_term_feed_link($tag, 'post_tag', $feed);
    }

    function get_edit_tag_link($tag, $taxonomy = 'post_tag')
    {
        return apply_filters('get_edit_tag_link', get_edit_term_link($tag, $taxonomy));
    }

    function edit_tag_link($link = '', $before = '', $after = '', $tag = null)
    {
        $link = edit_term_link($link, '', '', $tag, false);

        echo $before.apply_filters('edit_tag_link', $link).$after;
    }

    function get_edit_term_link($term, $taxonomy = '', $object_type = '')
    {
        $term = get_term($term, $taxonomy);
        if(! $term || is_wp_error($term))
        {
            return;
        }

        $tax = get_taxonomy($term->taxonomy);
        $term_id = $term->term_id;
        if(! $tax || ! current_user_can('edit_term', $term_id))
        {
            return;
        }

        $args = [
            'taxonomy' => $taxonomy,
            'tag_ID' => $term_id,
        ];

        if($object_type)
        {
            $args['post_type'] = $object_type;
        }
        elseif(! empty($tax->object_type))
        {
            $args['post_type'] = reset($tax->object_type);
        }

        if($tax->show_ui)
        {
            $location = add_query_arg($args, admin_url('term.php'));
        }
        else
        {
            $location = '';
        }

        return apply_filters('get_edit_term_link', $location, $term_id, $taxonomy, $object_type);
    }

    function edit_term_link($link = '', $before = '', $after = '', $term = null, $display = true)
    {
        if(is_null($term))
        {
            $term = get_queried_object();
        }
        else
        {
            $term = get_term($term);
        }

        if(! $term)
        {
            return;
        }

        $tax = get_taxonomy($term->taxonomy);
        if(! current_user_can('edit_term', $term->term_id))
        {
            return;
        }

        if(empty($link))
        {
            $link = __('Edit This');
        }

        $link = '<a href="'.get_edit_term_link($term->term_id, $term->taxonomy).'">'.$link.'</a>';

        $link = $before.apply_filters('edit_term_link', $link, $term->term_id).$after;

        if($display)
        {
            echo $link;
        }
        else
        {
            return $link;
        }
    }

    function get_search_link($query = '')
    {
        global $wp_rewrite;

        if(empty($query))
        {
            $search = get_search_query(false);
        }
        else
        {
            $search = stripslashes($query);
        }

        $permastruct = $wp_rewrite->get_search_permastruct();

        if(empty($permastruct))
        {
            $link = home_url('?s='.urlencode($search));
        }
        else
        {
            $search = urlencode($search);
            $search = str_replace('%2F', '/', $search); // %2F(/) is not valid within a URL, send it un-encoded.
            $link = str_replace('%search%', $search, $permastruct);
            $link = home_url(user_trailingslashit($link, 'search'));
        }

        return apply_filters('search_link', $link, $search);
    }

    function get_search_feed_link($search_query = '', $feed = '')
    {
        global $wp_rewrite;
        $link = get_search_link($search_query);

        if(empty($feed))
        {
            $feed = get_default_feed();
        }

        $permastruct = $wp_rewrite->get_search_permastruct();

        if(empty($permastruct))
        {
            $link = add_query_arg('feed', $feed, $link);
        }
        else
        {
            $link = trailingslashit($link);
            $link .= "feed/$feed/";
        }

        return apply_filters('search_feed_link', $link, $feed, 'posts');
    }

    function get_search_comments_feed_link($search_query = '', $feed = '')
    {
        global $wp_rewrite;

        if(empty($feed))
        {
            $feed = get_default_feed();
        }

        $link = get_search_feed_link($search_query, $feed);

        $permastruct = $wp_rewrite->get_search_permastruct();

        if(empty($permastruct))
        {
            $link = add_query_arg('feed', 'comments-'.$feed, $link);
        }
        else
        {
            $link = add_query_arg('withcomments', 1, $link);
        }

        return apply_filters('search_feed_link', $link, $feed, 'comments');
    }

    function get_post_type_archive_link($post_type)
    {
        global $wp_rewrite;

        $post_type_obj = get_post_type_object($post_type);

        if(! $post_type_obj)
        {
            return false;
        }

        if('post' === $post_type)
        {
            $show_on_front = get_option('show_on_front');
            $page_for_posts = get_option('page_for_posts');

            if('page' === $show_on_front && $page_for_posts)
            {
                $link = get_permalink($page_for_posts);
            }
            else
            {
                $link = get_home_url();
            }

            return apply_filters('post_type_archive_link', $link, $post_type);
        }

        if(! $post_type_obj->has_archive)
        {
            return false;
        }

        if(get_option('permalink_structure') && is_array($post_type_obj->rewrite))
        {
            $struct = (true === $post_type_obj->has_archive) ? $post_type_obj->rewrite['slug'] : $post_type_obj->has_archive;
            if($post_type_obj->rewrite['with_front'])
            {
                $struct = $wp_rewrite->front.$struct;
            }
            else
            {
                $struct = $wp_rewrite->root.$struct;
            }
            $link = home_url(user_trailingslashit($struct, 'post_type_archive'));
        }
        else
        {
            $link = home_url('?post_type='.$post_type);
        }

        return apply_filters('post_type_archive_link', $link, $post_type);
    }

    function get_post_type_archive_feed_link($post_type, $feed = '')
    {
        $default_feed = get_default_feed();
        if(empty($feed))
        {
            $feed = $default_feed;
        }

        $link = get_post_type_archive_link($post_type);
        if(! $link)
        {
            return false;
        }

        $post_type_obj = get_post_type_object($post_type);
        if(get_option('permalink_structure') && is_array($post_type_obj->rewrite) && $post_type_obj->rewrite['feeds'])
        {
            $link = trailingslashit($link);
            $link .= 'feed/';
            if($feed != $default_feed)
            {
                $link .= "$feed/";
            }
        }
        else
        {
            $link = add_query_arg('feed', $feed, $link);
        }

        return apply_filters('post_type_archive_feed_link', $link, $feed);
    }

    function get_preview_post_link($post = null, $query_args = [], $preview_link = '')
    {
        $post = get_post($post);

        if(! $post)
        {
            return;
        }

        $post_type_object = get_post_type_object($post->post_type);
        if(is_post_type_viewable($post_type_object))
        {
            if(! $preview_link)
            {
                $preview_link = set_url_scheme(get_permalink($post));
            }

            $query_args['preview'] = 'true';
            $preview_link = add_query_arg($query_args, $preview_link);
        }

        return apply_filters('preview_post_link', $preview_link, $post);
    }

    function get_edit_post_link($post = 0, $context = 'display')
    {
        $post = get_post($post);

        if(! $post)
        {
            return;
        }

        if('revision' === $post->post_type)
        {
            $action = '';
        }
        elseif('display' === $context)
        {
            $action = '&amp;action=edit';
        }
        else
        {
            $action = '&action=edit';
        }

        $post_type_object = get_post_type_object($post->post_type);

        if(! $post_type_object || ! current_user_can('edit_post', $post->ID))
        {
            return;
        }

        $link = '';

        if('wp_template' === $post->post_type || 'wp_template_part' === $post->post_type)
        {
            $slug = urlencode(get_stylesheet().'//'.$post->post_name);
            $link = admin_url(sprintf($post_type_object->_edit_link, $post->post_type, $slug));
        }
        elseif('wp_navigation' === $post->post_type)
        {
            $link = admin_url(sprintf($post_type_object->_edit_link, (string) $post->ID));
        }
        elseif($post_type_object->_edit_link)
        {
            $link = admin_url(sprintf($post_type_object->_edit_link.$action, $post->ID));
        }

        return apply_filters('get_edit_post_link', $link, $post->ID, $context);
    }

    function edit_post_link($text = null, $before = '', $after = '', $post = 0, $css_class = 'post-edit-link')
    {
        $post = get_post($post);

        if(! $post)
        {
            return;
        }

        $url = get_edit_post_link($post->ID);

        if(! $url)
        {
            return;
        }

        if(null === $text)
        {
            $text = __('Edit This');
        }

        $link = '<a class="'.esc_attr($css_class).'" href="'.esc_url($url).'">'.$text.'</a>';

        echo $before.apply_filters('edit_post_link', $link, $post->ID, $text).$after;
    }

    function get_delete_post_link($post = 0, $deprecated = '', $force_delete = false)
    {
        if(! empty($deprecated))
        {
            _deprecated_argument(__FUNCTION__, '3.0.0');
        }

        $post = get_post($post);

        if(! $post)
        {
            return;
        }

        $post_type_object = get_post_type_object($post->post_type);

        if(! $post_type_object || ! current_user_can('delete_post', $post->ID))
        {
            return;
        }

        $action = ($force_delete || ! EMPTY_TRASH_DAYS) ? 'delete' : 'trash';

        $delete_link = add_query_arg('action', $action, admin_url(sprintf($post_type_object->_edit_link, $post->ID)));

        return apply_filters('get_delete_post_link', wp_nonce_url($delete_link, "$action-post_{$post->ID}"), $post->ID, $force_delete);
    }

    function get_edit_comment_link($comment_id = 0)
    {
        $comment = get_comment($comment_id);

        if(! current_user_can('edit_comment', $comment->comment_ID))
        {
            return;
        }

        $location = admin_url('comment.php?action=editcomment&amp;c=').$comment->comment_ID;

        return apply_filters('get_edit_comment_link', $location);
    }

    function edit_comment_link($text = null, $before = '', $after = '')
    {
        $comment = get_comment();

        if(! current_user_can('edit_comment', $comment->comment_ID))
        {
            return;
        }

        if(null === $text)
        {
            $text = __('Edit This');
        }

        $link = '<a class="comment-edit-link" href="'.esc_url(get_edit_comment_link($comment)).'">'.$text.'</a>';

        echo $before.apply_filters('edit_comment_link', $link, $comment->comment_ID, $text).$after;
    }

    function get_edit_bookmark_link($link = 0)
    {
        $link = get_bookmark($link);

        if(! current_user_can('manage_links'))
        {
            return;
        }

        $location = admin_url('link.php?action=edit&amp;link_id=').$link->link_id;

        return apply_filters('get_edit_bookmark_link', $location, $link->link_id);
    }

    function edit_bookmark_link($link = '', $before = '', $after = '', $bookmark = null)
    {
        $bookmark = get_bookmark($bookmark);

        if(! current_user_can('manage_links'))
        {
            return;
        }

        if(empty($link))
        {
            $link = __('Edit This');
        }

        $link = '<a href="'.esc_url(get_edit_bookmark_link($bookmark)).'">'.$link.'</a>';

        echo $before.apply_filters('edit_bookmark_link', $link, $bookmark->link_id).$after;
    }

    function get_edit_user_link($user_id = null)
    {
        if(! $user_id)
        {
            $user_id = get_current_user_id();
        }

        if(empty($user_id) || ! current_user_can('edit_user', $user_id))
        {
            return '';
        }

        $user = get_userdata($user_id);

        if(! $user)
        {
            return '';
        }

        if(get_current_user_id() == $user->ID)
        {
            $link = get_edit_profile_url($user->ID);
        }
        else
        {
            $link = add_query_arg('user_id', $user->ID, self_admin_url('user-edit.php'));
        }

        return apply_filters('get_edit_user_link', $link, $user->ID);
    }

//
// Navigation links.
//

    function get_previous_post($in_same_term = false, $excluded_terms = '', $taxonomy = 'category')
    {
        return get_adjacent_post($in_same_term, $excluded_terms, true, $taxonomy);
    }

    function get_next_post($in_same_term = false, $excluded_terms = '', $taxonomy = 'category')
    {
        return get_adjacent_post($in_same_term, $excluded_terms, false, $taxonomy);
    }

    function get_adjacent_post($in_same_term = false, $excluded_terms = '', $previous = true, $taxonomy = 'category')
    {
        global $wpdb;

        $post = get_post();

        if(! $post || ! taxonomy_exists($taxonomy))
        {
            return null;
        }

        $current_post_date = $post->post_date;

        $join = '';
        $where = '';
        $adjacent = $previous ? 'previous' : 'next';

        if(! empty($excluded_terms) && ! is_array($excluded_terms))
        {
            // Back-compat, $excluded_terms used to be $excluded_categories with IDs separated by " and ".
            if(str_contains($excluded_terms, ' and '))
            {
                _deprecated_argument(__FUNCTION__, '3.3.0', sprintf(/* translators: %s: The word 'and'. */ __('Use commas instead of %s to separate excluded terms.'), "'and'"));
                $excluded_terms = explode(' and ', $excluded_terms);
            }
            else
            {
                $excluded_terms = explode(',', $excluded_terms);
            }

            $excluded_terms = array_map('intval', $excluded_terms);
        }

        $excluded_terms = apply_filters("get_{$adjacent}_post_excluded_terms", $excluded_terms);

        if($in_same_term || ! empty($excluded_terms))
        {
            if($in_same_term)
            {
                $join .= " INNER JOIN $wpdb->term_relationships AS tr ON p.ID = tr.object_id INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id";
                $where .= $wpdb->prepare('AND tt.taxonomy = %s', $taxonomy);

                if(! is_object_in_taxonomy($post->post_type, $taxonomy))
                {
                    return '';
                }
                $term_array = wp_get_object_terms($post->ID, $taxonomy, ['fields' => 'ids']);

                // Remove any exclusions from the term array to include.
                $term_array = array_diff($term_array, (array) $excluded_terms);
                $term_array = array_map('intval', $term_array);

                if(! $term_array || is_wp_error($term_array))
                {
                    return '';
                }

                $where .= ' AND tt.term_id IN ('.implode(',', $term_array).')';
            }

            if(! empty($excluded_terms))
            {
                $where .= " AND p.ID NOT IN ( SELECT tr.object_id FROM $wpdb->term_relationships tr LEFT JOIN $wpdb->term_taxonomy tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id) WHERE tt.term_id IN (".implode(',', array_map('intval', $excluded_terms)).') )';
            }
        }

        // 'post_status' clause depends on the current user.
        if(is_user_logged_in())
        {
            $user_id = get_current_user_id();

            $post_type_object = get_post_type_object($post->post_type);
            if(empty($post_type_object))
            {
                $post_type_cap = $post->post_type;
                $read_private_cap = 'read_private_'.$post_type_cap.'s';
            }
            else
            {
                $read_private_cap = $post_type_object->cap->read_private_posts;
            }

            /*
		 * Results should include private posts belonging to the current user, or private posts where the
		 * current user has the 'read_private_posts' cap.
		 */
            $private_states = get_post_stati(['private' => true]);
            $where .= " AND ( p.post_status = 'publish'";
            foreach($private_states as $state)
            {
                if(current_user_can($read_private_cap))
                {
                    $where .= $wpdb->prepare(' OR p.post_status = %s', $state);
                }
                else
                {
                    $where .= $wpdb->prepare(' OR (p.post_author = %d AND p.post_status = %s)', $user_id, $state);
                }
            }
            $where .= ' )';
        }
        else
        {
            $where .= " AND p.post_status = 'publish'";
        }

        $op = $previous ? '<' : '>';
        $order = $previous ? 'DESC' : 'ASC';

        $join = apply_filters("get_{$adjacent}_post_join", $join, $in_same_term, $excluded_terms, $taxonomy, $post);

        $where = apply_filters("get_{$adjacent}_post_where", $wpdb->prepare("WHERE p.post_date $op %s AND p.post_type = %s $where", $current_post_date, $post->post_type), $in_same_term, $excluded_terms, $taxonomy, $post);

        $sort = apply_filters("get_{$adjacent}_post_sort", "ORDER BY p.post_date $order LIMIT 1", $post, $order);

        $query = "SELECT p.ID FROM $wpdb->posts AS p $join $where $sort";
        $key = md5($query);
        $last_changed = wp_cache_get_last_changed('posts');
        if($in_same_term || ! empty($excluded_terms))
        {
            $last_changed .= wp_cache_get_last_changed('terms');
        }
        $cache_key = "adjacent_post:$key:$last_changed";

        $result = wp_cache_get($cache_key, 'post-queries');
        if(false !== $result)
        {
            if($result)
            {
                $result = get_post($result);
            }

            return $result;
        }

        $result = $wpdb->get_var($query);
        if(null === $result)
        {
            $result = '';
        }

        wp_cache_set($cache_key, $result, 'post-queries');

        if($result)
        {
            $result = get_post($result);
        }

        return $result;
    }

    function get_adjacent_post_rel_link(
        $title = '%title', $in_same_term = false, $excluded_terms = '', $previous = true, $taxonomy = 'category'
    ) {
        $post = get_post();
        if($previous && is_attachment() && $post)
        {
            $post = get_post($post->post_parent);
        }
        else
        {
            $post = get_adjacent_post($in_same_term, $excluded_terms, $previous, $taxonomy);
        }

        if(empty($post))
        {
            return;
        }

        $post_title = the_title_attribute([
                                              'echo' => false,
                                              'post' => $post,
                                          ]);

        if(empty($post_title))
        {
            $post_title = $previous ? __('Previous Post') : __('Next Post');
        }

        /** @noinspection NativeMemberUsageInspection */
        $date = mysql2date(get_option('date_format'), $post->post_date);

        $title = str_replace('%title', $post_title, $title);
        $title = str_replace('%date', $date, $title);

        $link = $previous ? "<link rel='prev' title='" : "<link rel='next' title='";
        $link .= esc_attr($title);
        $link .= "' href='".get_permalink($post)."' />\n";

        $adjacent = $previous ? 'previous' : 'next';

        return apply_filters("{$adjacent}_post_rel_link", $link);
    }

    function adjacent_posts_rel_link(
        $title = '%title', $in_same_term = false, $excluded_terms = '', $taxonomy = 'category'
    ) {
        echo get_adjacent_post_rel_link($title, $in_same_term, $excluded_terms, true, $taxonomy);
        echo get_adjacent_post_rel_link($title, $in_same_term, $excluded_terms, false, $taxonomy);
    }

    function adjacent_posts_rel_link_wp_head()
    {
        if(! is_single() || is_attachment())
        {
            return;
        }
        adjacent_posts_rel_link();
    }

    function next_post_rel_link($title = '%title', $in_same_term = false, $excluded_terms = '', $taxonomy = 'category')
    {
        echo get_adjacent_post_rel_link($title, $in_same_term, $excluded_terms, false, $taxonomy);
    }

    function prev_post_rel_link($title = '%title', $in_same_term = false, $excluded_terms = '', $taxonomy = 'category')
    {
        echo get_adjacent_post_rel_link($title, $in_same_term, $excluded_terms, true, $taxonomy);
    }

    function get_boundary_post($in_same_term = false, $excluded_terms = '', $start = true, $taxonomy = 'category')
    {
        $post = get_post();

        if(! $post || ! is_single() || is_attachment() || ! taxonomy_exists($taxonomy))
        {
            return null;
        }

        $query_args = [
            'posts_per_page' => 1,
            'order' => $start ? 'ASC' : 'DESC',
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,
        ];

        $term_array = [];

        if(! is_array($excluded_terms))
        {
            if(! empty($excluded_terms))
            {
                $excluded_terms = explode(',', $excluded_terms);
            }
            else
            {
                $excluded_terms = [];
            }
        }

        if($in_same_term || ! empty($excluded_terms))
        {
            if($in_same_term)
            {
                $term_array = wp_get_object_terms($post->ID, $taxonomy, ['fields' => 'ids']);
            }

            if(! empty($excluded_terms))
            {
                $excluded_terms = array_map('intval', $excluded_terms);
                $excluded_terms = array_diff($excluded_terms, $term_array);

                $inverse_terms = [];
                foreach($excluded_terms as $excluded_term)
                {
                    $inverse_terms[] = $excluded_term * -1;
                }
                $excluded_terms = $inverse_terms;
            }

            $query_args['tax_query'] = [
                [
                    'taxonomy' => $taxonomy,
                    'terms' => array_merge($term_array, $excluded_terms),
                ],
            ];
        }

        return get_posts($query_args);
    }

    function get_previous_post_link(
        $format = '&laquo; %link', $link = '%title', $in_same_term = false, $excluded_terms = '', $taxonomy = 'category'
    ) {
        return get_adjacent_post_link($format, $link, $in_same_term, $excluded_terms, true, $taxonomy);
    }

    function previous_post_link(
        $format = '&laquo; %link', $link = '%title', $in_same_term = false, $excluded_terms = '', $taxonomy = 'category'
    ) {
        echo get_previous_post_link($format, $link, $in_same_term, $excluded_terms, $taxonomy);
    }

    function get_next_post_link(
        $format = '%link &raquo;', $link = '%title', $in_same_term = false, $excluded_terms = '', $taxonomy = 'category'
    ) {
        return get_adjacent_post_link($format, $link, $in_same_term, $excluded_terms, false, $taxonomy);
    }

    function next_post_link(
        $format = '%link &raquo;', $link = '%title', $in_same_term = false, $excluded_terms = '', $taxonomy = 'category'
    ) {
        echo get_next_post_link($format, $link, $in_same_term, $excluded_terms, $taxonomy);
    }

    function get_adjacent_post_link(
        $format, $link, $in_same_term = false, $excluded_terms = '', $previous = true, $taxonomy = 'category'
    ) {
        if($previous && is_attachment())
        {
            $post = get_post(get_post()->post_parent);
        }
        else
        {
            $post = get_adjacent_post($in_same_term, $excluded_terms, $previous, $taxonomy);
        }

        if(! $post)
        {
            $output = '';
        }
        else
        {
            /** @noinspection NativeMemberUsageInspection */
            $title = $post->post_title;

            /** @noinspection NativeMemberUsageInspection */
            if(empty($post->post_title))
            {
                $title = $previous ? __('Previous Post') : __('Next Post');
            }

            /** @noinspection NativeMemberUsageInspection */
            $title = apply_filters('the_title', $title, $post->ID);

            /** @noinspection NativeMemberUsageInspection */
            $date = mysql2date(get_option('date_format'), $post->post_date);
            $rel = $previous ? 'prev' : 'next';

            $string = '<a href="'.get_permalink($post).'" rel="'.$rel.'">';
            $inlink = str_replace('%title', $title, $link);
            $inlink = str_replace('%date', $date, $inlink);
            $inlink = $string.$inlink.'</a>';

            $output = str_replace('%link', $inlink, $format);
        }

        $adjacent = $previous ? 'previous' : 'next';

        return apply_filters("{$adjacent}_post_link", $output, $format, $link, $post, $adjacent);
    }

    function adjacent_post_link(
        $format, $link, $in_same_term = false, $excluded_terms = '', $previous = true, $taxonomy = 'category'
    ) {
        echo get_adjacent_post_link($format, $link, $in_same_term, $excluded_terms, $previous, $taxonomy);
    }

    function get_pagenum_link($pagenum = 1, $escape = true)
    {
        global $wp_rewrite;

        $pagenum = (int) $pagenum;

        $request = remove_query_arg('paged');

        $home_root = parse_url(home_url());
        $home_root = (isset($home_root['path'])) ? $home_root['path'] : '';
        $home_root = preg_quote($home_root, '|');

        $request = preg_replace('|^'.$home_root.'|i', '', $request);
        $request = preg_replace('|^/+|', '', $request);

        if(! $wp_rewrite->using_permalinks() || is_admin())
        {
            $base = trailingslashit(get_bloginfo('url'));

            if($pagenum > 1)
            {
                $result = add_query_arg('paged', $pagenum, $base.$request);
            }
            else
            {
                $result = $base.$request;
            }
        }
        else
        {
            $qs_regex = '|\?.*?$|';
            preg_match($qs_regex, $request, $qs_match);

            if(! empty($qs_match[0]))
            {
                $query_string = $qs_match[0];
                $request = preg_replace($qs_regex, '', $request);
            }
            else
            {
                $query_string = '';
            }

            $request = preg_replace("|$wp_rewrite->pagination_base/\d+/?$|", '', $request);
            $request = preg_replace('|^'.preg_quote($wp_rewrite->index, '|').'|i', '', $request);
            $request = ltrim($request, '/');

            $base = trailingslashit(get_bloginfo('url'));

            if($wp_rewrite->using_index_permalinks() && ($pagenum > 1 || '' !== $request))
            {
                $base .= $wp_rewrite->index.'/';
            }

            if($pagenum > 1)
            {
                $request = ((! empty($request)) ? trailingslashit($request) : $request).user_trailingslashit($wp_rewrite->pagination_base.'/'.$pagenum, 'paged');
            }

            $result = $base.$request.$query_string;
        }

        $result = apply_filters('get_pagenum_link', $result, $pagenum);

        if($escape)
        {
            return esc_url($result);
        }
        else
        {
            return sanitize_url($result);
        }
    }

    function get_next_posts_page_link($max_page = 0)
    {
        global $paged;

        if(! is_single())
        {
            if(! $paged)
            {
                $paged = 1;
            }

            $next_page = (int) $paged + 1;

            if(! $max_page || $max_page >= $next_page)
            {
                return get_pagenum_link($next_page);
            }
        }
    }

    function next_posts($max_page = 0, $display = true)
    {
        $output = esc_url(get_next_posts_page_link($max_page));

        if($display)
        {
            echo $output;
        }
        else
        {
            return $output;
        }
    }

    function get_next_posts_link($label = null, $max_page = 0)
    {
        global $paged, $wp_query;

        if(! $max_page)
        {
            $max_page = $wp_query->max_num_pages;
        }

        if(! $paged)
        {
            $paged = 1;
        }

        $next_page = (int) $paged + 1;

        if(null === $label)
        {
            $label = __('Next Page &raquo;');
        }

        if(! is_single() && ($next_page <= $max_page))
        {
            $attr = apply_filters('next_posts_link_attributes', '');

            return sprintf('<a href="%1$s" %2$s>%3$s</a>', next_posts($max_page, false), $attr, preg_replace('/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label));
        }
    }

    function next_posts_link($label = null, $max_page = 0)
    {
        echo get_next_posts_link($label, $max_page);
    }

    function get_previous_posts_page_link()
    {
        global $paged;

        if(! is_single())
        {
            $previous_page = (int) $paged - 1;

            if($previous_page < 1)
            {
                $previous_page = 1;
            }

            return get_pagenum_link($previous_page);
        }
    }

    function previous_posts($display = true)
    {
        $output = esc_url(get_previous_posts_page_link());

        if($display)
        {
            echo $output;
        }
        else
        {
            return $output;
        }
    }

    function get_previous_posts_link($label = null)
    {
        global $paged;

        if(null === $label)
        {
            $label = __('&laquo; Previous Page');
        }

        if(! is_single() && $paged > 1)
        {
            $attr = apply_filters('previous_posts_link_attributes', '');

            return sprintf('<a href="%1$s" %2$s>%3$s</a>', previous_posts(false), $attr, preg_replace('/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label));
        }
    }

    function previous_posts_link($label = null)
    {
        echo get_previous_posts_link($label);
    }

    function get_posts_nav_link($args = [])
    {
        global $wp_query;

        $return = '';

        if(! is_singular())
        {
            $defaults = [
                'sep' => ' &#8212; ',
                'prelabel' => __('&laquo; Previous Page'),
                'nxtlabel' => __('Next Page &raquo;'),
            ];
            $args = wp_parse_args($args, $defaults);

            $max_num_pages = $wp_query->max_num_pages;
            $paged = get_query_var('paged');

            // Only have sep if there's both prev and next results.
            if($paged < 2 || $paged >= $max_num_pages)
            {
                $args['sep'] = '';
            }

            if($max_num_pages > 1)
            {
                $return = get_previous_posts_link($args['prelabel']);
                $return .= preg_replace('/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $args['sep']);
                $return .= get_next_posts_link($args['nxtlabel']);
            }
        }

        return $return;
    }

    function posts_nav_link($sep = '', $prelabel = '', $nxtlabel = '')
    {
        $args = array_filter(compact('sep', 'prelabel', 'nxtlabel'));
        echo get_posts_nav_link($args);
    }

    function get_the_post_navigation($args = [])
    {
        // Make sure the nav element has an aria-label attribute: fallback to the screen reader text.
        if(! empty($args['screen_reader_text']) && empty($args['aria_label']))
        {
            $args['aria_label'] = $args['screen_reader_text'];
        }

        $args = wp_parse_args($args, [
            'prev_text' => '%title',
            'next_text' => '%title',
            'in_same_term' => false,
            'excluded_terms' => '',
            'taxonomy' => 'category',
            'screen_reader_text' => __('Post navigation'),
            'aria_label' => __('Posts'),
            'class' => 'post-navigation',
        ]);

        $navigation = '';

        $previous = get_previous_post_link('<div class="nav-previous">%link</div>', $args['prev_text'], $args['in_same_term'], $args['excluded_terms'], $args['taxonomy']);

        $next = get_next_post_link('<div class="nav-next">%link</div>', $args['next_text'], $args['in_same_term'], $args['excluded_terms'], $args['taxonomy']);

        // Only add markup if there's somewhere to navigate to.
        if($previous || $next)
        {
            $navigation = _navigation_markup($previous.$next, $args['class'], $args['screen_reader_text'], $args['aria_label']);
        }

        return $navigation;
    }

    function the_post_navigation($args = [])
    {
        echo get_the_post_navigation($args);
    }

    function get_the_posts_navigation($args = [])
    {
        global $wp_query;

        $navigation = '';

        // Don't print empty markup if there's only one page.
        if($wp_query->max_num_pages > 1)
        {
            // Make sure the nav element has an aria-label attribute: fallback to the screen reader text.
            if(! empty($args['screen_reader_text']) && empty($args['aria_label']))
            {
                $args['aria_label'] = $args['screen_reader_text'];
            }

            $args = wp_parse_args($args, [
                'prev_text' => __('Older posts'),
                'next_text' => __('Newer posts'),
                'screen_reader_text' => __('Posts navigation'),
                'aria_label' => __('Posts'),
                'class' => 'posts-navigation',
            ]);

            $next_link = get_previous_posts_link($args['next_text']);
            $prev_link = get_next_posts_link($args['prev_text']);

            if($prev_link)
            {
                $navigation .= '<div class="nav-previous">'.$prev_link.'</div>';
            }

            if($next_link)
            {
                $navigation .= '<div class="nav-next">'.$next_link.'</div>';
            }

            $navigation = _navigation_markup($navigation, $args['class'], $args['screen_reader_text'], $args['aria_label']);
        }

        return $navigation;
    }

    function the_posts_navigation($args = [])
    {
        echo get_the_posts_navigation($args);
    }

    function get_the_posts_pagination($args = [])
    {
        global $wp_query;

        $navigation = '';

        // Don't print empty markup if there's only one page.
        if($wp_query->max_num_pages > 1)
        {
            // Make sure the nav element has an aria-label attribute: fallback to the screen reader text.
            if(! empty($args['screen_reader_text']) && empty($args['aria_label']))
            {
                $args['aria_label'] = $args['screen_reader_text'];
            }

            $args = wp_parse_args($args, [
                'mid_size' => 1,
                'prev_text' => _x('Previous', 'previous set of posts'),
                'next_text' => _x('Next', 'next set of posts'),
                'screen_reader_text' => __('Posts navigation'),
                'aria_label' => __('Posts'),
                'class' => 'pagination',
            ]);

            $args = apply_filters('the_posts_pagination_args', $args);

            // Make sure we get a string back. Plain is the next best thing.
            if(isset($args['type']) && 'array' === $args['type'])
            {
                $args['type'] = 'plain';
            }

            // Set up paginated links.
            $links = paginate_links($args);

            if($links)
            {
                $navigation = _navigation_markup($links, $args['class'], $args['screen_reader_text'], $args['aria_label']);
            }
        }

        return $navigation;
    }

    function the_posts_pagination($args = [])
    {
        echo get_the_posts_pagination($args);
    }

    function _navigation_markup($links, $css_class = 'posts-navigation', $screen_reader_text = '', $aria_label = '')
    {
        if(empty($screen_reader_text))
        {
            $screen_reader_text = /* translators: Hidden accessibility text. */
                __('Posts navigation');
        }
        if(empty($aria_label))
        {
            $aria_label = $screen_reader_text;
        }

        $template = '
	<nav class="navigation %1$s" aria-label="%4$s">
		<h2 class="screen-reader-text">%2$s</h2>
		<div class="nav-links">%3$s</div>
	</nav>';

        $template = apply_filters('navigation_markup_template', $template, $css_class);

        return sprintf($template, sanitize_html_class($css_class), esc_html($screen_reader_text), $links, esc_attr($aria_label));
    }

    function get_comments_pagenum_link($pagenum = 1, $max_page = 0)
    {
        global $wp_rewrite;

        $pagenum = (int) $pagenum;

        $result = get_permalink();

        if('newest' === get_option('default_comments_page'))
        {
            if($pagenum != $max_page)
            {
                if($wp_rewrite->using_permalinks())
                {
                    $result = user_trailingslashit(trailingslashit($result).$wp_rewrite->comments_pagination_base.'-'.$pagenum, 'commentpaged');
                }
                else
                {
                    $result = add_query_arg('cpage', $pagenum, $result);
                }
            }
        }
        elseif($pagenum > 1)
        {
            if($wp_rewrite->using_permalinks())
            {
                $result = user_trailingslashit(trailingslashit($result).$wp_rewrite->comments_pagination_base.'-'.$pagenum, 'commentpaged');
            }
            else
            {
                $result = add_query_arg('cpage', $pagenum, $result);
            }
        }

        $result .= '#comments';

        return apply_filters('get_comments_pagenum_link', $result);
    }

    function get_next_comments_link($label = '', $max_page = 0)
    {
        global $wp_query;

        if(! is_singular())
        {
            return;
        }

        $page = get_query_var('cpage');

        if(! $page)
        {
            $page = 1;
        }

        $next_page = (int) $page + 1;

        if(empty($max_page))
        {
            $max_page = $wp_query->max_num_comment_pages;
        }

        if(empty($max_page))
        {
            $max_page = get_comment_pages_count();
        }

        if($next_page > $max_page)
        {
            return;
        }

        if(empty($label))
        {
            $label = __('Newer Comments &raquo;');
        }

        $attr = apply_filters('next_comments_link_attributes', '');

        return sprintf('<a href="%1$s" %2$s>%3$s</a>', esc_url(get_comments_pagenum_link($next_page, $max_page)), $attr, preg_replace('/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label));
    }

    function next_comments_link($label = '', $max_page = 0)
    {
        echo get_next_comments_link($label, $max_page);
    }

    function get_previous_comments_link($label = '')
    {
        if(! is_singular())
        {
            return;
        }

        $page = get_query_var('cpage');

        if((int) $page <= 1)
        {
            return;
        }

        $previous_page = (int) $page - 1;

        if(empty($label))
        {
            $label = __('&laquo; Older Comments');
        }

        $attr = apply_filters('previous_comments_link_attributes', '');

        return sprintf('<a href="%1$s" %2$s>%3$s</a>', esc_url(get_comments_pagenum_link($previous_page)), $attr, preg_replace('/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label));
    }

    function previous_comments_link($label = '')
    {
        echo get_previous_comments_link($label);
    }

    function paginate_comments_links($args = [])
    {
        global $wp_rewrite;

        if(! is_singular())
        {
            return;
        }

        $page = get_query_var('cpage');
        if(! $page)
        {
            $page = 1;
        }
        $max_page = get_comment_pages_count();
        $defaults = [
            'base' => add_query_arg('cpage', '%#%'),
            'format' => '',
            'total' => $max_page,
            'current' => $page,
            'echo' => true,
            'type' => 'plain',
            'add_fragment' => '#comments',
        ];
        if($wp_rewrite->using_permalinks())
        {
            $defaults['base'] = user_trailingslashit(trailingslashit(get_permalink()).$wp_rewrite->comments_pagination_base.'-%#%', 'commentpaged');
        }

        $args = wp_parse_args($args, $defaults);
        $page_links = paginate_links($args);

        if($args['echo'] && 'array' !== $args['type'])
        {
            echo $page_links;
        }
        else
        {
            return $page_links;
        }
    }

    function get_the_comments_navigation($args = [])
    {
        $navigation = '';

        // Are there comments to navigate through?
        if(get_comment_pages_count() > 1)
        {
            // Make sure the nav element has an aria-label attribute: fallback to the screen reader text.
            if(! empty($args['screen_reader_text']) && empty($args['aria_label']))
            {
                $args['aria_label'] = $args['screen_reader_text'];
            }

            $args = wp_parse_args($args, [
                'prev_text' => __('Older comments'),
                'next_text' => __('Newer comments'),
                'screen_reader_text' => __('Comments navigation'),
                'aria_label' => __('Comments'),
                'class' => 'comment-navigation',
            ]);

            $prev_link = get_previous_comments_link($args['prev_text']);
            $next_link = get_next_comments_link($args['next_text']);

            if($prev_link)
            {
                $navigation .= '<div class="nav-previous">'.$prev_link.'</div>';
            }

            if($next_link)
            {
                $navigation .= '<div class="nav-next">'.$next_link.'</div>';
            }

            $navigation = _navigation_markup($navigation, $args['class'], $args['screen_reader_text'], $args['aria_label']);
        }

        return $navigation;
    }

    function the_comments_navigation($args = [])
    {
        echo get_the_comments_navigation($args);
    }

    function get_the_comments_pagination($args = [])
    {
        $navigation = '';

        // Make sure the nav element has an aria-label attribute: fallback to the screen reader text.
        if(! empty($args['screen_reader_text']) && empty($args['aria_label']))
        {
            $args['aria_label'] = $args['screen_reader_text'];
        }

        $args = wp_parse_args($args, [
            'screen_reader_text' => __('Comments navigation'),
            'aria_label' => __('Comments'),
            'class' => 'comments-pagination',
        ]);
        $args['echo'] = false;

        // Make sure we get a string back. Plain is the next best thing.
        if(isset($args['type']) && 'array' === $args['type'])
        {
            $args['type'] = 'plain';
        }

        $links = paginate_comments_links($args);

        if($links)
        {
            $navigation = _navigation_markup($links, $args['class'], $args['screen_reader_text'], $args['aria_label']);
        }

        return $navigation;
    }

    function the_comments_pagination($args = [])
    {
        echo get_the_comments_pagination($args);
    }

    function home_url($path = '', $scheme = null)
    {
        return get_home_url(null, $path, $scheme);
    }

    function get_home_url($blog_id = null, $path = '', $scheme = null)
    {
        $orig_scheme = $scheme;

        if(empty($blog_id) || ! is_multisite())
        {
            $url = get_option('home');
        }
        else
        {
            switch_to_blog($blog_id);
            $url = get_option('home');
            restore_current_blog();
        }

        if(! in_array($scheme, ['http', 'https', 'relative'], true))
        {
            if(is_ssl())
            {
                $scheme = 'https';
            }
            else
            {
                $scheme = parse_url($url, PHP_URL_SCHEME);
            }
        }

        $url = set_url_scheme($url, $scheme);

        if($path && is_string($path))
        {
            $url .= '/'.ltrim($path, '/');
        }

        return apply_filters('home_url', $url, $path, $orig_scheme, $blog_id);
    }

    function site_url($path = '', $scheme = null)
    {
        return get_site_url(null, $path, $scheme);
    }

    function get_site_url($blog_id = null, $path = '', $scheme = null)
    {
        if(empty($blog_id) || ! is_multisite())
        {
            $url = get_option('siteurl');
        }
        else
        {
            switch_to_blog($blog_id);
            $url = get_option('siteurl');
            restore_current_blog();
        }

        $url = set_url_scheme($url, $scheme);

        if($path && is_string($path))
        {
            $url .= '/'.ltrim($path, '/');
        }

        return apply_filters('site_url', $url, $path, $scheme, $blog_id);
    }

    function admin_url($path = '', $scheme = 'admin')
    {
        return get_admin_url(null, $path, $scheme);
    }

    function get_admin_url($blog_id = null, $path = '', $scheme = 'admin')
    {
        $url = get_site_url($blog_id, 'wp-admin/', $scheme);

        if($path && is_string($path))
        {
            $url .= ltrim($path, '/');
        }

        return apply_filters('admin_url', $url, $path, $blog_id, $scheme);
    }

    function includes_url($path = '', $scheme = null)
    {
        $url = site_url('/'.WPINC.'/', $scheme);

        if($path && is_string($path))
        {
            $url .= ltrim($path, '/');
        }

        return apply_filters('includes_url', $url, $path, $scheme);
    }

    function content_url($path = '')
    {
        $url = set_url_scheme(WP_CONTENT_URL);

        if($path && is_string($path))
        {
            $url .= '/'.ltrim($path, '/');
        }

        return apply_filters('content_url', $url, $path);
    }

    function plugins_url($path = '', $plugin = '')
    {
        $path = wp_normalize_path($path);
        $plugin = wp_normalize_path($plugin);
        $mu_plugin_dir = wp_normalize_path(WPMU_PLUGIN_DIR);

        if(! empty($plugin) && str_starts_with($plugin, $mu_plugin_dir))
        {
            $url = WPMU_PLUGIN_URL;
        }
        else
        {
            $url = WP_PLUGIN_URL;
        }

        $url = set_url_scheme($url);

        if(! empty($plugin) && is_string($plugin))
        {
            $folder = dirname(plugin_basename($plugin));
            if('.' !== $folder)
            {
                $url .= '/'.ltrim($folder, '/');
            }
        }

        if($path && is_string($path))
        {
            $url .= '/'.ltrim($path, '/');
        }

        return apply_filters('plugins_url', $url, $path, $plugin);
    }

    function network_site_url($path = '', $scheme = null)
    {
        if(! is_multisite())
        {
            return site_url($path, $scheme);
        }

        $current_network = get_network();

        if('relative' === $scheme)
        {
            $url = $current_network->path;
        }
        else
        {
            $url = set_url_scheme('http://'.$current_network->domain.$current_network->path, $scheme);
        }

        if($path && is_string($path))
        {
            $url .= ltrim($path, '/');
        }

        return apply_filters('network_site_url', $url, $path, $scheme);
    }

    function network_home_url($path = '', $scheme = null)
    {
        if(! is_multisite())
        {
            return home_url($path, $scheme);
        }

        $current_network = get_network();
        $orig_scheme = $scheme;

        if(! in_array($scheme, ['http', 'https', 'relative'], true))
        {
            $scheme = is_ssl() ? 'https' : 'http';
        }

        if('relative' === $scheme)
        {
            $url = $current_network->path;
        }
        else
        {
            $url = set_url_scheme('http://'.$current_network->domain.$current_network->path, $scheme);
        }

        if($path && is_string($path))
        {
            $url .= ltrim($path, '/');
        }

        return apply_filters('network_home_url', $url, $path, $orig_scheme);
    }

    function network_admin_url($path = '', $scheme = 'admin')
    {
        if(! is_multisite())
        {
            return admin_url($path, $scheme);
        }

        $url = network_site_url('wp-admin/network/', $scheme);

        if($path && is_string($path))
        {
            $url .= ltrim($path, '/');
        }

        return apply_filters('network_admin_url', $url, $path, $scheme);
    }

    function user_admin_url($path = '', $scheme = 'admin')
    {
        $url = network_site_url('wp-admin/user/', $scheme);

        if($path && is_string($path))
        {
            $url .= ltrim($path, '/');
        }

        return apply_filters('user_admin_url', $url, $path, $scheme);
    }

    function self_admin_url($path = '', $scheme = 'admin')
    {
        if(is_network_admin())
        {
            $url = network_admin_url($path, $scheme);
        }
        elseif(is_user_admin())
        {
            $url = user_admin_url($path, $scheme);
        }
        else
        {
            $url = admin_url($path, $scheme);
        }

        return apply_filters('self_admin_url', $url, $path, $scheme);
    }

    function set_url_scheme($url, $scheme = null)
    {
        $orig_scheme = $scheme;

        if(! $scheme)
        {
            $scheme = is_ssl() ? 'https' : 'http';
        }
        elseif('admin' === $scheme || 'login' === $scheme || 'login_post' === $scheme || 'rpc' === $scheme)
        {
            $scheme = is_ssl() || force_ssl_admin() ? 'https' : 'http';
        }
        elseif('http' !== $scheme && 'https' !== $scheme && 'relative' !== $scheme)
        {
            $scheme = is_ssl() ? 'https' : 'http';
        }

        $url = trim($url);
        if(str_starts_with($url, '//'))
        {
            $url = 'http:'.$url;
        }

        if('relative' === $scheme)
        {
            $url = ltrim(preg_replace('#^\w+://[^/]*#', '', $url));
            if('' !== $url && '/' === $url[0])
            {
                $url = '/'.ltrim($url, "/ \t\n\r\0\x0B");
            }
        }
        else
        {
            $url = preg_replace('#^\w+://#', $scheme.'://', $url);
        }

        return apply_filters('set_url_scheme', $url, $scheme, $orig_scheme);
    }

    function get_dashboard_url($user_id = 0, $path = '', $scheme = 'admin')
    {
        $user_id = $user_id ? (int) $user_id : get_current_user_id();

        $blogs = get_blogs_of_user($user_id);

        if(is_multisite() && ! user_can($user_id, 'manage_network') && empty($blogs))
        {
            $url = user_admin_url($path, $scheme);
        }
        elseif(! is_multisite())
        {
            $url = admin_url($path, $scheme);
        }
        else
        {
            $current_blog = get_current_blog_id();

            if($current_blog && (user_can($user_id, 'manage_network') || in_array($current_blog, array_keys($blogs), true)))
            {
                $url = admin_url($path, $scheme);
            }
            else
            {
                $active = get_active_blog_for_user($user_id);
                if($active)
                {
                    $url = get_admin_url($active->blog_id, $path, $scheme);
                }
                else
                {
                    $url = user_admin_url($path, $scheme);
                }
            }
        }

        return apply_filters('user_dashboard_url', $url, $user_id, $path, $scheme);
    }

    function get_edit_profile_url($user_id = 0, $scheme = 'admin')
    {
        $user_id = $user_id ? (int) $user_id : get_current_user_id();

        if(is_user_admin())
        {
            $url = user_admin_url('profile.php', $scheme);
        }
        elseif(is_network_admin())
        {
            $url = network_admin_url('profile.php', $scheme);
        }
        else
        {
            $url = get_dashboard_url($user_id, 'profile.php', $scheme);
        }

        return apply_filters('edit_profile_url', $url, $user_id, $scheme);
    }

    function wp_get_canonical_url($post = null)
    {
        $post = get_post($post);

        if(! $post || 'publish' !== $post->post_status)
        {
            return false;
        }

        $canonical_url = get_permalink($post);

        // If a canonical is being generated for the current page, make sure it has pagination if needed.
        if(get_queried_object_id() === $post->ID)
        {
            $page = get_query_var('page', 0);
            if($page >= 2)
            {
                if(! get_option('permalink_structure'))
                {
                    $canonical_url = add_query_arg('page', $page, $canonical_url);
                }
                else
                {
                    $canonical_url = trailingslashit($canonical_url).user_trailingslashit($page, 'single_paged');
                }
            }

            $cpage = get_query_var('cpage', 0);
            if($cpage)
            {
                $canonical_url = get_comments_pagenum_link($cpage);
            }
        }

        return apply_filters('get_canonical_url', $canonical_url, $post);
    }

    function rel_canonical()
    {
        if(! is_singular())
        {
            return;
        }

        $id = get_queried_object_id();

        if(0 === $id)
        {
            return;
        }

        $url = wp_get_canonical_url($id);

        if(! empty($url))
        {
            echo '<link rel="canonical" href="'.esc_url($url).'" />'."\n";
        }
    }

    function wp_get_shortlink($id = 0, $context = 'post', $allow_slugs = true)
    {
        $shortlink = apply_filters('pre_get_shortlink', false, $id, $context, $allow_slugs);

        if(false !== $shortlink)
        {
            return $shortlink;
        }

        $post_id = 0;
        if('query' === $context && is_singular())
        {
            $post_id = get_queried_object_id();
            $post = get_post($post_id);
        }
        elseif('post' === $context)
        {
            $post = get_post($id);
            if(! empty($post->ID))
            {
                $post_id = $post->ID;
            }
        }

        $shortlink = '';

        // Return `?p=` link for all public post types.
        if(! empty($post_id))
        {
            $post_type = get_post_type_object($post->post_type);

            if('page' === $post->post_type && get_option('page_on_front') == $post->ID && 'page' === get_option('show_on_front'))
            {
                $shortlink = home_url('/');
            }
            elseif($post_type && $post_type->public)
            {
                $shortlink = home_url('?p='.$post_id);
            }
        }

        return apply_filters('get_shortlink', $shortlink, $id, $context, $allow_slugs);
    }

    function wp_shortlink_wp_head()
    {
        $shortlink = wp_get_shortlink(0, 'query');

        if(empty($shortlink))
        {
            return;
        }

        echo "<link rel='shortlink' href='".esc_url($shortlink)."' />\n";
    }

    function wp_shortlink_header()
    {
        if(headers_sent())
        {
            return;
        }

        $shortlink = wp_get_shortlink(0, 'query');

        if(empty($shortlink))
        {
            return;
        }

        header('Link: <'.$shortlink.'>; rel=shortlink', false);
    }

    function the_shortlink($text = '', $title = '', $before = '', $after = '')
    {
        $post = get_post();

        if(empty($text))
        {
            $text = __('This is the short link.');
        }

        if(empty($title))
        {
            $title = the_title_attribute(['echo' => false]);
        }

        $shortlink = wp_get_shortlink($post->ID);

        if(! empty($shortlink))
        {
            $link = '<a rel="shortlink" href="'.esc_url($shortlink).'" title="'.$title.'">'.$text.'</a>';

            $link = apply_filters('the_shortlink', $link, $shortlink, $text, $title);
            echo $before, $link, $after;
        }
    }

    function get_avatar_url($id_or_email, $args = null)
    {
        $args = get_avatar_data($id_or_email, $args);

        return $args['url'];
    }

    function is_avatar_comment_type($comment_type)
    {
        $allowed_comment_types = apply_filters('get_avatar_comment_types', ['comment']);

        return in_array($comment_type, (array) $allowed_comment_types, true);
    }

    function get_avatar_data($id_or_email, $args = null)
    {
        $args = wp_parse_args($args, [
            'size' => 96,
            'height' => null,
            'width' => null,
            'default' => get_option('avatar_default', 'mystery'),
            'force_default' => false,
            'rating' => get_option('avatar_rating'),
            'scheme' => null,
            'processed_args' => null, // If used, should be a reference.
            'extra_attr' => '',
        ]);

        if(is_numeric($args['size']))
        {
            $args['size'] = absint($args['size']);
            if(! $args['size'])
            {
                $args['size'] = 96;
            }
        }
        else
        {
            $args['size'] = 96;
        }

        if(is_numeric($args['height']))
        {
            $args['height'] = absint($args['height']);
            if(! $args['height'])
            {
                $args['height'] = $args['size'];
            }
        }
        else
        {
            $args['height'] = $args['size'];
        }

        if(is_numeric($args['width']))
        {
            $args['width'] = absint($args['width']);
            if(! $args['width'])
            {
                $args['width'] = $args['size'];
            }
        }
        else
        {
            $args['width'] = $args['size'];
        }

        if(empty($args['default']))
        {
            $args['default'] = get_option('avatar_default', 'mystery');
        }

        switch($args['default'])
        {
            case 'mm':
            case 'mystery':
            case 'mysteryman':
                $args['default'] = 'mm';
                break;
            case 'gravatar_default':
                $args['default'] = false;
                break;
        }

        $args['force_default'] = (bool) $args['force_default'];

        $args['rating'] = strtolower($args['rating']);

        $args['found_avatar'] = false;

        $args = apply_filters('pre_get_avatar_data', $args, $id_or_email);

        if(isset($args['url']))
        {
            return apply_filters('get_avatar_data', $args, $id_or_email);
        }

        $email_hash = '';
        $user = false;
        $email = false;

        if(is_object($id_or_email) && isset($id_or_email->comment_ID))
        {
            $id_or_email = get_comment($id_or_email);
        }

        // Process the user identifier.
        if(is_numeric($id_or_email))
        {
            $user = get_user_by('id', absint($id_or_email));
        }
        elseif(is_string($id_or_email))
        {
            if(str_contains($id_or_email, '@md5.gravatar.com'))
            {
                // MD5 hash.
                [$email_hash] = explode('@', $id_or_email);
            }
            else
            {
                // Email address.
                $email = $id_or_email;
            }
        }
        elseif($id_or_email instanceof WP_User)
        {
            // User object.
            $user = $id_or_email;
        }
        elseif($id_or_email instanceof WP_Post)
        {
            // Post object.
            $user = get_user_by('id', (int) $id_or_email->post_author);
        }
        elseif($id_or_email instanceof WP_Comment)
        {
            if(! is_avatar_comment_type(get_comment_type($id_or_email)))
            {
                $args['url'] = false;

                return apply_filters('get_avatar_data', $args, $id_or_email);
            }

            if(! empty($id_or_email->user_id))
            {
                $user = get_user_by('id', (int) $id_or_email->user_id);
            }
            if((! $user || is_wp_error($user)) && ! empty($id_or_email->comment_author_email))
            {
                $email = $id_or_email->comment_author_email;
            }
        }

        if(! $email_hash)
        {
            if($user)
            {
                $email = $user->user_email;
            }

            if($email)
            {
                $email_hash = md5(strtolower(trim($email)));
            }
        }

        if($email_hash)
        {
            $args['found_avatar'] = true;
            $gravatar_server = hexdec($email_hash[0]) % 3;
        }
        else
        {
            $gravatar_server = random_int(0, 2);
        }

        $url_args = [
            's' => $args['size'],
            'd' => $args['default'],
            'f' => $args['force_default'] ? 'y' : false,
            'r' => $args['rating'],
        ];

        if(is_ssl())
        {
            $url = 'https://secure.gravatar.com/avatar/'.$email_hash;
        }
        else
        {
            $url = sprintf('http://%d.gravatar.com/avatar/%s', $gravatar_server, $email_hash);
        }

        $url = add_query_arg(rawurlencode_deep(array_filter($url_args)), set_url_scheme($url, $args['scheme']));

        $args['url'] = apply_filters('get_avatar_url', $url, $id_or_email, $args);

        return apply_filters('get_avatar_data', $args, $id_or_email);
    }

    function get_theme_file_uri($file = '')
    {
        $file = ltrim($file, '/');

        $stylesheet_directory = get_stylesheet_directory();

        if(empty($file))
        {
            $url = get_stylesheet_directory_uri();
        }
        elseif(get_template_directory() !== $stylesheet_directory && file_exists($stylesheet_directory.'/'.$file))
        {
            $url = get_stylesheet_directory_uri().'/'.$file;
        }
        else
        {
            $url = get_template_directory_uri().'/'.$file;
        }

        return apply_filters('theme_file_uri', $url, $file);
    }

    function get_parent_theme_file_uri($file = '')
    {
        $file = ltrim($file, '/');

        if(empty($file))
        {
            $url = get_template_directory_uri();
        }
        else
        {
            $url = get_template_directory_uri().'/'.$file;
        }

        return apply_filters('parent_theme_file_uri', $url, $file);
    }

    function get_theme_file_path($file = '')
    {
        $file = ltrim($file, '/');

        $stylesheet_directory = get_stylesheet_directory();
        $template_directory = get_template_directory();

        if(empty($file))
        {
            $path = $stylesheet_directory;
        }
        elseif($stylesheet_directory !== $template_directory && file_exists($stylesheet_directory.'/'.$file))
        {
            $path = $stylesheet_directory.'/'.$file;
        }
        else
        {
            $path = $template_directory.'/'.$file;
        }

        return apply_filters('theme_file_path', $path, $file);
    }

    function get_parent_theme_file_path($file = '')
    {
        $file = ltrim($file, '/');

        if(empty($file))
        {
            $path = get_template_directory();
        }
        else
        {
            $path = get_template_directory().'/'.$file;
        }

        return apply_filters('parent_theme_file_path', $path, $file);
    }

    function get_privacy_policy_url()
    {
        $url = '';
        $policy_page_id = (int) get_option('wp_page_for_privacy_policy');

        if(! empty($policy_page_id) && get_post_status($policy_page_id) === 'publish')
        {
            $url = (string) get_permalink($policy_page_id);
        }

        return apply_filters('privacy_policy_url', $url, $policy_page_id);
    }

    function the_privacy_policy_link($before = '', $after = '')
    {
        echo get_the_privacy_policy_link($before, $after);
    }

    function get_the_privacy_policy_link($before = '', $after = '')
    {
        $link = '';
        $privacy_policy_url = get_privacy_policy_url();
        $policy_page_id = (int) get_option('wp_page_for_privacy_policy');
        $page_title = ($policy_page_id) ? get_the_title($policy_page_id) : '';

        if($privacy_policy_url && $page_title)
        {
            $link = sprintf('<a class="privacy-policy-link" href="%s" rel="privacy-policy">%s</a>', esc_url($privacy_policy_url), esc_html($page_title));
        }

        $link = apply_filters('the_privacy_policy_link', $link, $privacy_policy_url);

        if($link)
        {
            return $before.$link.$after;
        }

        return '';
    }

    function wp_internal_hosts()
    {
        static $internal_hosts;

        if(empty($internal_hosts))
        {
            $internal_hosts = apply_filters('wp_internal_hosts', [
                wp_parse_url(home_url(), PHP_URL_HOST),
            ]);
            $internal_hosts = array_unique(array_map('strtolower', (array) $internal_hosts));
        }

        return $internal_hosts;
    }

    function wp_is_internal_link($link)
    {
        $link = strtolower($link);
        if(in_array(wp_parse_url($link, PHP_URL_SCHEME), wp_allowed_protocols(), true))
        {
            return in_array(wp_parse_url($link, PHP_URL_HOST), wp_internal_hosts(), true);
        }

        return false;
    }
