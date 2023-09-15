<?php

    function check_comment($author, $email, $url, $comment, $user_ip, $user_agent, $comment_type)
    {
        global $wpdb;

        // If manual moderation is enabled, skip all checks and return false.
        if(1 == get_option('comment_moderation'))
        {
            return false;
        }

        $comment = apply_filters('comment_text', $comment, null, []);

        // Check for the number of external links if a max allowed number is set.
        $max_links = get_option('comment_max_links');
        if($max_links)
        {
            $num_links = preg_match_all('/<a [^>]*href/i', $comment, $out);

            $num_links = apply_filters('comment_max_links_url', $num_links, $url, $comment);

            /*
		 * If the number of links in the comment exceeds the allowed amount,
		 * fail the check by returning false.
		 */
            if($num_links >= $max_links)
            {
                return false;
            }
        }

        $mod_keys = trim(get_option('moderation_keys'));

        // If moderation 'keys' (keywords) are set, process them.
        if(! empty($mod_keys))
        {
            $words = explode("\n", $mod_keys);

            foreach((array) $words as $word)
            {
                $word = trim($word);

                // Skip empty lines.
                if(empty($word))
                {
                    continue;
                }

                /*
			 * Do some escaping magic so that '#' (number of) characters in the spam
			 * words don't break things:
			 */
                $word = preg_quote($word, '#');

                /*
			 * Check the comment fields for moderation keywords. If any are found,
			 * fail the check for the given field by returning false.
			 */
                $pattern = "#$word#iu";
                if(preg_match($pattern, $author))
                {
                    return false;
                }
                if(preg_match($pattern, $email))
                {
                    return false;
                }
                if(preg_match($pattern, $url))
                {
                    return false;
                }
                if(preg_match($pattern, $comment))
                {
                    return false;
                }
                if(preg_match($pattern, $user_ip))
                {
                    return false;
                }
                if(preg_match($pattern, $user_agent))
                {
                    return false;
                }
            }
        }

        /*
	 * Check if the option to approve comments by previously-approved authors is enabled.
	 *
	 * If it is enabled, check whether the comment author has a previously-approved comment,
	 * as well as whether there are any moderation keywords (if set) present in the author
	 * email address. If both checks pass, return true. Otherwise, return false.
	 */
        if(1 == get_option('comment_previously_approved'))
        {
            if('trackback' !== $comment_type && 'pingback' !== $comment_type && '' !== $author && '' !== $email)
            {
                $comment_user = get_user_by('email', wp_unslash($email));
                if(! empty($comment_user->ID))
                {
                    $ok_to_comment = $wpdb->get_var($wpdb->prepare("SELECT comment_approved FROM $wpdb->comments WHERE user_id = %d AND comment_approved = '1' LIMIT 1", $comment_user->ID));
                }
                else
                {
                    // expected_slashed ($author, $email)
                    $ok_to_comment = $wpdb->get_var($wpdb->prepare("SELECT comment_approved FROM $wpdb->comments WHERE comment_author = %s AND comment_author_email = %s and comment_approved = '1' LIMIT 1", $author, $email));
                }
                if((1 == $ok_to_comment) && (empty($mod_keys) || ! str_contains($email, $mod_keys)))
                {
                    return true;
                }
                else
                {
                    return false;
                }
            }
            else
            {
                return false;
            }
        }

        return true;
    }

    function get_approved_comments($post_id, $args = [])
    {
        if(! $post_id)
        {
            return [];
        }

        $defaults = [
            'status' => 1,
            'post_id' => $post_id,
            'order' => 'ASC',
        ];
        $parsed_args = wp_parse_args($args, $defaults);

        $query = new WP_Comment_Query();

        return $query->query($parsed_args);
    }

    function get_comment($comment = null, $output = OBJECT)
    {
        if(empty($comment) && isset($GLOBALS['comment']))
        {
            $comment = $GLOBALS['comment'];
        }

        if($comment instanceof WP_Comment)
        {
            $_comment = $comment;
        }
        elseif(is_object($comment))
        {
            $_comment = new WP_Comment($comment);
        }
        else
        {
            $_comment = WP_Comment::get_instance($comment);
        }

        if(! $_comment)
        {
            return null;
        }

        $_comment = apply_filters('get_comment', $_comment);

        if(OBJECT === $output)
        {
            return $_comment;
        }
        elseif(ARRAY_A === $output)
        {
            return $_comment->to_array();
        }
        elseif(ARRAY_N === $output)
        {
            return array_values($_comment->to_array());
        }

        return $_comment;
    }

    function get_comments($args = '')
    {
        $query = new WP_Comment_Query();

        return $query->query($args);
    }

    function get_comment_statuses()
    {
        $status = [
            'hold' => __('Unapproved'),
            'approve' => _x('Approved', 'comment status'),
            'spam' => _x('Spam', 'comment status'),
            'trash' => _x('Trash', 'comment status'),
        ];

        return $status;
    }

    function get_default_comment_status($post_type = 'post', $comment_type = 'comment')
    {
        switch($comment_type)
        {
            case 'pingback':
            case 'trackback':
                $supports = 'trackbacks';
                $option = 'ping';
                break;
            default:
                $supports = 'comments';
                $option = 'comment';
                break;
        }

        // Set the status.
        if('page' === $post_type)
        {
            $status = 'closed';
        }
        elseif(post_type_supports($post_type, $supports))
        {
            $status = get_option("default_{$option}_status");
        }
        else
        {
            $status = 'closed';
        }

        return apply_filters('get_default_comment_status', $status, $post_type, $comment_type);
    }

    function get_lastcommentmodified($timezone = 'server')
    {
        global $wpdb;

        $timezone = strtolower($timezone);
        $key = "lastcommentmodified:$timezone";

        $comment_modified_date = wp_cache_get($key, 'timeinfo');
        if(false !== $comment_modified_date)
        {
            return $comment_modified_date;
        }

        switch($timezone)
        {
            case 'gmt':
                $comment_modified_date = $wpdb->get_var("SELECT comment_date_gmt FROM $wpdb->comments WHERE comment_approved = '1' ORDER BY comment_date_gmt DESC LIMIT 1");
                break;
            case 'blog':
                $comment_modified_date = $wpdb->get_var("SELECT comment_date FROM $wpdb->comments WHERE comment_approved = '1' ORDER BY comment_date_gmt DESC LIMIT 1");
                break;
            case 'server':
                $add_seconds_server = gmdate('Z');

                $comment_modified_date = $wpdb->get_var($wpdb->prepare("SELECT DATE_ADD(comment_date_gmt, INTERVAL %s SECOND) FROM $wpdb->comments WHERE comment_approved = '1' ORDER BY comment_date_gmt DESC LIMIT 1", $add_seconds_server));
                break;
        }

        if($comment_modified_date)
        {
            wp_cache_set($key, $comment_modified_date, 'timeinfo');

            return $comment_modified_date;
        }

        return false;
    }

    function get_comment_count($post_id = 0)
    {
        $post_id = (int) $post_id;

        $comment_count = [
            'approved' => 0,
            'awaiting_moderation' => 0,
            'spam' => 0,
            'trash' => 0,
            'post-trashed' => 0,
            'total_comments' => 0,
            'all' => 0,
        ];

        $args = [
            'count' => true,
            'update_comment_meta_cache' => false,
        ];
        if($post_id > 0)
        {
            $args['post_id'] = $post_id;
        }
        $mapping = [
            'approved' => 'approve',
            'awaiting_moderation' => 'hold',
            'spam' => 'spam',
            'trash' => 'trash',
            'post-trashed' => 'post-trashed',
        ];
        $comment_count = [];
        foreach($mapping as $key => $value)
        {
            $comment_count[$key] = get_comments(array_merge($args, ['status' => $value]));
        }

        $comment_count['all'] = $comment_count['approved'] + $comment_count['awaiting_moderation'];
        $comment_count['total_comments'] = $comment_count['all'] + $comment_count['spam'];

        return array_map('intval', $comment_count);
    }

//
// Comment meta functions.
//

    function add_comment_meta($comment_id, $meta_key, $meta_value, $unique = false)
    {
        return add_metadata('comment', $comment_id, $meta_key, $meta_value, $unique);
    }

    function delete_comment_meta($comment_id, $meta_key, $meta_value = '')
    {
        return delete_metadata('comment', $comment_id, $meta_key, $meta_value);
    }

    function get_comment_meta($comment_id, $key = '', $single = false)
    {
        return get_metadata('comment', $comment_id, $key, $single);
    }

    function wp_lazyload_comment_meta(array $comment_ids)
    {
        if(empty($comment_ids))
        {
            return;
        }
        $lazyloader = wp_metadata_lazyloader();
        $lazyloader->queue_objects('comment', $comment_ids);
    }

    function update_comment_meta($comment_id, $meta_key, $meta_value, $prev_value = '')
    {
        return update_metadata('comment', $comment_id, $meta_key, $meta_value, $prev_value);
    }

    function wp_set_comment_cookies($comment, $user, $cookies_consent = true)
    {
        // If the user already exists, or the user opted out of cookies, don't set cookies.
        if($user->exists())
        {
            return;
        }

        if(false === $cookies_consent)
        {
            // Remove any existing cookies.
            $past = time() - YEAR_IN_SECONDS;
            setcookie('comment_author_'.COOKIEHASH, ' ', $past, COOKIEPATH, COOKIE_DOMAIN);
            setcookie('comment_author_email_'.COOKIEHASH, ' ', $past, COOKIEPATH, COOKIE_DOMAIN);
            setcookie('comment_author_url_'.COOKIEHASH, ' ', $past, COOKIEPATH, COOKIE_DOMAIN);

            return;
        }

        $comment_cookie_lifetime = time() + apply_filters('comment_cookie_lifetime', 30000000);

        $secure = ('https' === parse_url(home_url(), PHP_URL_SCHEME));

        setcookie('comment_author_'.COOKIEHASH, $comment->comment_author, $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN, $secure);
        setcookie('comment_author_email_'.COOKIEHASH, $comment->comment_author_email, $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN, $secure);
        setcookie('comment_author_url_'.COOKIEHASH, esc_url($comment->comment_author_url), $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN, $secure);
    }

    function sanitize_comment_cookies()
    {
        if(isset($_COOKIE['comment_author_'.COOKIEHASH]))
        {
            $comment_author = apply_filters('pre_comment_author_name', $_COOKIE['comment_author_'.COOKIEHASH]);
            $comment_author = wp_unslash($comment_author);
            $comment_author = esc_attr($comment_author);

            $_COOKIE['comment_author_'.COOKIEHASH] = $comment_author;
        }

        if(isset($_COOKIE['comment_author_email_'.COOKIEHASH]))
        {
            $comment_author_email = apply_filters('pre_comment_author_email', $_COOKIE['comment_author_email_'.COOKIEHASH]);
            $comment_author_email = wp_unslash($comment_author_email);
            $comment_author_email = esc_attr($comment_author_email);

            $_COOKIE['comment_author_email_'.COOKIEHASH] = $comment_author_email;
        }

        if(isset($_COOKIE['comment_author_url_'.COOKIEHASH]))
        {
            $comment_author_url = apply_filters('pre_comment_author_url', $_COOKIE['comment_author_url_'.COOKIEHASH]);
            $comment_author_url = wp_unslash($comment_author_url);

            $_COOKIE['comment_author_url_'.COOKIEHASH] = $comment_author_url;
        }
    }

    function wp_allow_comment($commentdata, $wp_error = false)
    {
        global $wpdb;

        /*
	 * Simple duplicate check.
	 * expected_slashed ($comment_post_ID, $comment_author, $comment_author_email, $comment_content)
	 */
        $dupe = $wpdb->prepare("SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_parent = %s AND comment_approved != 'trash' AND ( comment_author = %s ", wp_unslash($commentdata['comment_post_ID']), wp_unslash($commentdata['comment_parent']), wp_unslash($commentdata['comment_author']));
        if($commentdata['comment_author_email'])
        {
            $dupe .= $wpdb->prepare('AND comment_author_email = %s ', wp_unslash($commentdata['comment_author_email']));
        }
        $dupe .= $wpdb->prepare(') AND comment_content = %s LIMIT 1', wp_unslash($commentdata['comment_content']));

        $dupe_id = $wpdb->get_var($dupe);

        $dupe_id = apply_filters('duplicate_comment_id', $dupe_id, $commentdata);

        if($dupe_id)
        {
            do_action('comment_duplicate_trigger', $commentdata);

            $comment_duplicate_message = apply_filters('comment_duplicate_message', __('Duplicate comment detected; it looks as though you&#8217;ve already said that!'));

            if($wp_error)
            {
                return new WP_Error('comment_duplicate', $comment_duplicate_message, 409);
            }
            else
            {
                if(wp_doing_ajax())
                {
                    die($comment_duplicate_message);
                }

                wp_die($comment_duplicate_message, 409);
            }
        }

        do_action('check_comment_flood', $commentdata['comment_author_IP'], $commentdata['comment_author_email'], $commentdata['comment_date_gmt'], $wp_error);

        $is_flood = apply_filters('wp_is_comment_flood', false, $commentdata['comment_author_IP'], $commentdata['comment_author_email'], $commentdata['comment_date_gmt'], $wp_error);

        if($is_flood)
        {
            $comment_flood_message = apply_filters('comment_flood_message', __('You are posting comments too quickly. Slow down.'));

            return new WP_Error('comment_flood', $comment_flood_message, 429);
        }

        if(! empty($commentdata['user_id']))
        {
            $user = get_userdata($commentdata['user_id']);
            $post_author = $wpdb->get_var($wpdb->prepare("SELECT post_author FROM $wpdb->posts WHERE ID = %d LIMIT 1", $commentdata['comment_post_ID']));
        }

        if(isset($user) && ($commentdata['user_id'] == $post_author || $user->has_cap('moderate_comments')))
        {
            // The author and the admins get respect.
            $approved = 1;
        }
        else
        {
            // Everyone else's comments will be checked.
            if(check_comment($commentdata['comment_author'], $commentdata['comment_author_email'], $commentdata['comment_author_url'], $commentdata['comment_content'], $commentdata['comment_author_IP'], $commentdata['comment_agent'], $commentdata['comment_type']))
            {
                $approved = 1;
            }
            else
            {
                $approved = 0;
            }

            if(wp_check_comment_disallowed_list($commentdata['comment_author'], $commentdata['comment_author_email'], $commentdata['comment_author_url'], $commentdata['comment_content'], $commentdata['comment_author_IP'], $commentdata['comment_agent']))
            {
                $approved = EMPTY_TRASH_DAYS ? 'trash' : 'spam';
            }
        }

        return apply_filters('pre_comment_approved', $approved, $commentdata);
    }

    function check_comment_flood_db()
    {
        add_filter('wp_is_comment_flood', 'wp_check_comment_flood', 10, 5);
    }

    function wp_check_comment_flood($is_flood, $ip, $email, $date, $avoid_die = false)
    {
        global $wpdb;

        // Another callback has declared a flood. Trust it.
        if(true === $is_flood)
        {
            return $is_flood;
        }

        // Don't throttle admins or moderators.
        if(current_user_can('manage_options') || current_user_can('moderate_comments'))
        {
            return false;
        }

        $hour_ago = gmdate('Y-m-d H:i:s', time() - HOUR_IN_SECONDS);

        if(is_user_logged_in())
        {
            $user = get_current_user_id();
            $check_column = '`user_id`';
        }
        else
        {
            $user = $ip;
            $check_column = '`comment_author_IP`';
        }

        $sql = $wpdb->prepare("SELECT `comment_date_gmt` FROM `$wpdb->comments` WHERE `comment_date_gmt` >= %s AND ( $check_column = %s OR `comment_author_email` = %s ) ORDER BY `comment_date_gmt` DESC LIMIT 1", $hour_ago, $user, $email);

        $lasttime = $wpdb->get_var($sql);

        if($lasttime)
        {
            $time_lastcomment = mysql2date('U', $lasttime, false);
            $time_newcomment = mysql2date('U', $date, false);

            $flood_die = apply_filters('comment_flood_filter', false, $time_lastcomment, $time_newcomment);

            if($flood_die)
            {
                do_action('comment_flood_trigger', $time_lastcomment, $time_newcomment);

                if($avoid_die)
                {
                    return true;
                }
                else
                {
                    $comment_flood_message = apply_filters('comment_flood_message', __('You are posting comments too quickly. Slow down.'));

                    if(wp_doing_ajax())
                    {
                        die($comment_flood_message);
                    }

                    wp_die($comment_flood_message, 429);
                }
            }
        }

        return false;
    }

    function separate_comments(&$comments)
    {
        $comments_by_type = [
            'comment' => [],
            'trackback' => [],
            'pingback' => [],
            'pings' => [],
        ];

        $count = count($comments);

        for($i = 0; $i < $count; $i++)
        {
            $type = $comments[$i]->comment_type;

            if(empty($type))
            {
                $type = 'comment';
            }

            $comments_by_type[$type][] = &$comments[$i];

            if('trackback' === $type || 'pingback' === $type)
            {
                $comments_by_type['pings'][] = &$comments[$i];
            }
        }

        return $comments_by_type;
    }

    function get_comment_pages_count($comments = null, $per_page = null, $threaded = null)
    {
        global $wp_query;

        if(null === $comments && null === $per_page && null === $threaded && ! empty($wp_query->max_num_comment_pages))
        {
            return $wp_query->max_num_comment_pages;
        }

        if((! $comments || ! is_array($comments)) && ! empty($wp_query->comments))
        {
            $comments = $wp_query->comments;
        }

        if(empty($comments))
        {
            return 0;
        }

        if(! get_option('page_comments'))
        {
            return 1;
        }

        if(! isset($per_page))
        {
            $per_page = (int) get_query_var('comments_per_page');
        }
        if(0 === $per_page)
        {
            $per_page = (int) get_option('comments_per_page');
        }
        if(0 === $per_page)
        {
            return 1;
        }

        if(! isset($threaded))
        {
            $threaded = get_option('thread_comments');
        }

        if($threaded)
        {
            $walker = new Walker_Comment();
            $count = ceil($walker->get_number_of_root_elements($comments) / $per_page);
        }
        else
        {
            $count = ceil(count($comments) / $per_page);
        }

        return $count;
    }

    function get_page_of_comment($comment_id, $args = [])
    {
        global $wpdb;

        $page = null;

        $comment = get_comment($comment_id);
        if(! $comment)
        {
            return;
        }

        $defaults = [
            'type' => 'all',
            'page' => '',
            'per_page' => '',
            'max_depth' => '',
        ];
        $args = wp_parse_args($args, $defaults);
        $original_args = $args;

        // Order of precedence: 1. `$args['per_page']`, 2. 'comments_per_page' query_var, 3. 'comments_per_page' option.
        if(get_option('page_comments'))
        {
            if('' === $args['per_page'])
            {
                $args['per_page'] = get_query_var('comments_per_page');
            }

            if('' === $args['per_page'])
            {
                $args['per_page'] = get_option('comments_per_page');
            }
        }

        if(empty($args['per_page']))
        {
            $args['per_page'] = 0;
            $args['page'] = 0;
        }

        if($args['per_page'] < 1)
        {
            $page = 1;
        }

        if(null === $page)
        {
            if('' === $args['max_depth'])
            {
                if(get_option('thread_comments'))
                {
                    $args['max_depth'] = get_option('thread_comments_depth');
                }
                else
                {
                    $args['max_depth'] = -1;
                }
            }

            // Find this comment's top-level parent if threading is enabled.
            if($args['max_depth'] > 1 && 0 != $comment->comment_parent)
            {
                return get_page_of_comment($comment->comment_parent, $args);
            }

            $comment_args = [
                'type' => $args['type'],
                'post_id' => $comment->comment_post_ID,
                'fields' => 'ids',
                'count' => true,
                'status' => 'approve',
                'parent' => 0,
                'date_query' => [
                    [
                        'column' => "$wpdb->comments.comment_date_gmt",
                        'before' => $comment->comment_date_gmt,
                    ],
                ],
            ];

            if(is_user_logged_in())
            {
                $comment_args['include_unapproved'] = [get_current_user_id()];
            }
            else
            {
                $unapproved_email = wp_get_unapproved_comment_author_email();

                if($unapproved_email)
                {
                    $comment_args['include_unapproved'] = [$unapproved_email];
                }
            }

            $comment_args = apply_filters('get_page_of_comment_query_args', $comment_args);

            $comment_query = new WP_Comment_Query();
            $older_comment_count = $comment_query->query($comment_args);

            // No older comments? Then it's page #1.
            if(0 == $older_comment_count)
            {
                $page = 1;
                // Divide comments older than this one by comments per page to get this comment's page number.
            }
            else
            {
                $page = ceil(($older_comment_count + 1) / $args['per_page']);
            }
        }

        return apply_filters('get_page_of_comment', (int) $page, $args, $original_args, $comment_id);
    }

    function wp_get_comment_fields_max_lengths()
    {
        global $wpdb;

        $lengths = [
            'comment_author' => 245,
            'comment_author_email' => 100,
            'comment_author_url' => 200,
            'comment_content' => 65525,
        ];

        if($wpdb->is_mysql)
        {
            foreach($lengths as $column => $length)
            {
                $col_length = $wpdb->get_col_length($wpdb->comments, $column);
                $max_length = 0;

                // No point if we can't get the DB column lengths.
                if(is_wp_error($col_length))
                {
                    break;
                }

                if(! is_array($col_length) && (int) $col_length > 0)
                {
                    $max_length = (int) $col_length;
                }
                elseif(is_array($col_length) && isset($col_length['length']) && (int) $col_length['length'] > 0)
                {
                    $max_length = (int) $col_length['length'];

                    if(! empty($col_length['type']) && 'byte' === $col_length['type'])
                    {
                        $max_length = $max_length - 10;
                    }
                }

                if($max_length > 0)
                {
                    $lengths[$column] = $max_length;
                }
            }
        }

        return apply_filters('wp_get_comment_fields_max_lengths', $lengths);
    }

    function wp_check_comment_data_max_lengths($comment_data)
    {
        $max_lengths = wp_get_comment_fields_max_lengths();

        if(isset($comment_data['comment_author']) && mb_strlen($comment_data['comment_author'], '8bit') > $max_lengths['comment_author'])
        {
            return new WP_Error('comment_author_column_length', __('<strong>Error:</strong> Your name is too long.'), 200);
        }

        if(isset($comment_data['comment_author_email']) && strlen($comment_data['comment_author_email']) > $max_lengths['comment_author_email'])
        {
            return new WP_Error('comment_author_email_column_length', __('<strong>Error:</strong> Your email address is too long.'), 200);
        }

        if(isset($comment_data['comment_author_url']) && strlen($comment_data['comment_author_url']) > $max_lengths['comment_author_url'])
        {
            return new WP_Error('comment_author_url_column_length', __('<strong>Error:</strong> Your URL is too long.'), 200);
        }

        if(isset($comment_data['comment_content']) && mb_strlen($comment_data['comment_content'], '8bit') > $max_lengths['comment_content'])
        {
            return new WP_Error('comment_content_column_length', __('<strong>Error:</strong> Your comment is too long.'), 200);
        }

        return true;
    }

    function wp_check_comment_disallowed_list($author, $email, $url, $comment, $user_ip, $user_agent)
    {
        do_action_deprecated('wp_blacklist_check', [
            $author,
            $email,
            $url,
            $comment,
            $user_ip,
            $user_agent
        ],                   '5.5.0', 'wp_check_comment_disallowed_list', __('Please consider writing more inclusive code.'));

        do_action('wp_check_comment_disallowed_list', $author, $email, $url, $comment, $user_ip, $user_agent);

        $mod_keys = trim(get_option('disallowed_keys'));
        if('' === $mod_keys)
        {
            return false; // If moderation keys are empty.
        }

        // Ensure HTML tags are not being used to bypass the list of disallowed characters and words.
        $comment_without_html = wp_strip_all_tags($comment);

        $words = explode("\n", $mod_keys);

        foreach((array) $words as $word)
        {
            $word = trim($word);

            // Skip empty lines.
            if(empty($word))
            {
                continue;
            }

            // Do some escaping magic so that '#' chars in the spam words don't break things:
            $word = preg_quote($word, '#');

            $pattern = "#$word#iu";
            if(preg_match($pattern, $author) || preg_match($pattern, $email) || preg_match($pattern, $url) || preg_match($pattern, $comment) || preg_match($pattern, $comment_without_html) || preg_match($pattern, $user_ip) || preg_match($pattern, $user_agent))
            {
                return true;
            }
        }

        return false;
    }

    function wp_count_comments($post_id = 0)
    {
        $post_id = (int) $post_id;

        $filtered = apply_filters('wp_count_comments', [], $post_id);
        if(! empty($filtered))
        {
            return $filtered;
        }

        $count = wp_cache_get("comments-{$post_id}", 'counts');
        if(false !== $count)
        {
            return $count;
        }

        $stats = get_comment_count($post_id);
        $stats['moderated'] = $stats['awaiting_moderation'];
        unset($stats['awaiting_moderation']);

        $stats_object = (object) $stats;
        wp_cache_set("comments-{$post_id}", $stats_object, 'counts');

        return $stats_object;
    }

    function wp_delete_comment($comment_id, $force_delete = false)
    {
        global $wpdb;

        $comment = get_comment($comment_id);
        if(! $comment)
        {
            return false;
        }

        if(
            ! $force_delete && EMPTY_TRASH_DAYS && ! in_array(wp_get_comment_status($comment), [
                'trash',
                'spam'
            ],                                                true)
        )
        {
            return wp_trash_comment($comment_id);
        }

        do_action('delete_comment', $comment->comment_ID, $comment);

        // Move children up a level.
        $children = $wpdb->get_col($wpdb->prepare("SELECT comment_ID FROM $wpdb->comments WHERE comment_parent = %d", $comment->comment_ID));
        if(! empty($children))
        {
            $wpdb->update($wpdb->comments, ['comment_parent' => $comment->comment_parent], ['comment_parent' => $comment->comment_ID]);
            clean_comment_cache($children);
        }

        // Delete metadata.
        $meta_ids = $wpdb->get_col($wpdb->prepare("SELECT meta_id FROM $wpdb->commentmeta WHERE comment_id = %d", $comment->comment_ID));
        foreach($meta_ids as $mid)
        {
            delete_metadata_by_mid('comment', $mid);
        }

        if(! $wpdb->delete($wpdb->comments, ['comment_ID' => $comment->comment_ID]))
        {
            return false;
        }

        do_action('deleted_comment', $comment->comment_ID, $comment);

        $post_id = $comment->comment_post_ID;
        if($post_id && 1 == $comment->comment_approved)
        {
            wp_update_comment_count($post_id);
        }

        clean_comment_cache($comment->comment_ID);

        do_action('wp_set_comment_status', $comment->comment_ID, 'delete');

        wp_transition_comment_status('delete', $comment->comment_approved, $comment);

        return true;
    }

    function wp_trash_comment($comment_id)
    {
        if(! EMPTY_TRASH_DAYS)
        {
            return wp_delete_comment($comment_id, true);
        }

        $comment = get_comment($comment_id);
        if(! $comment)
        {
            return false;
        }

        do_action('trash_comment', $comment->comment_ID, $comment);

        if(wp_set_comment_status($comment, 'trash'))
        {
            delete_comment_meta($comment->comment_ID, '_wp_trash_meta_status');
            delete_comment_meta($comment->comment_ID, '_wp_trash_meta_time');
            add_comment_meta($comment->comment_ID, '_wp_trash_meta_status', $comment->comment_approved);
            add_comment_meta($comment->comment_ID, '_wp_trash_meta_time', time());

            do_action('trashed_comment', $comment->comment_ID, $comment);

            return true;
        }

        return false;
    }

    function wp_untrash_comment($comment_id)
    {
        $comment = get_comment($comment_id);
        if(! $comment)
        {
            return false;
        }

        do_action('untrash_comment', $comment->comment_ID, $comment);

        $status = (string) get_comment_meta($comment->comment_ID, '_wp_trash_meta_status', true);
        if(empty($status))
        {
            $status = '0';
        }

        if(wp_set_comment_status($comment, $status))
        {
            delete_comment_meta($comment->comment_ID, '_wp_trash_meta_time');
            delete_comment_meta($comment->comment_ID, '_wp_trash_meta_status');

            do_action('untrashed_comment', $comment->comment_ID, $comment);

            return true;
        }

        return false;
    }

    function wp_spam_comment($comment_id)
    {
        $comment = get_comment($comment_id);
        if(! $comment)
        {
            return false;
        }

        do_action('spam_comment', $comment->comment_ID, $comment);

        if(wp_set_comment_status($comment, 'spam'))
        {
            delete_comment_meta($comment->comment_ID, '_wp_trash_meta_status');
            delete_comment_meta($comment->comment_ID, '_wp_trash_meta_time');
            add_comment_meta($comment->comment_ID, '_wp_trash_meta_status', $comment->comment_approved);
            add_comment_meta($comment->comment_ID, '_wp_trash_meta_time', time());

            do_action('spammed_comment', $comment->comment_ID, $comment);

            return true;
        }

        return false;
    }

    function wp_unspam_comment($comment_id)
    {
        $comment = get_comment($comment_id);
        if(! $comment)
        {
            return false;
        }

        do_action('unspam_comment', $comment->comment_ID, $comment);

        $status = (string) get_comment_meta($comment->comment_ID, '_wp_trash_meta_status', true);
        if(empty($status))
        {
            $status = '0';
        }

        if(wp_set_comment_status($comment, $status))
        {
            delete_comment_meta($comment->comment_ID, '_wp_trash_meta_status');
            delete_comment_meta($comment->comment_ID, '_wp_trash_meta_time');

            do_action('unspammed_comment', $comment->comment_ID, $comment);

            return true;
        }

        return false;
    }

    function wp_get_comment_status($comment_id)
    {
        $comment = get_comment($comment_id);
        if(! $comment)
        {
            return false;
        }

        $approved = $comment->comment_approved;

        if(null == $approved)
        {
            return false;
        }
        elseif('1' == $approved)
        {
            return 'approved';
        }
        elseif('0' == $approved)
        {
            return 'unapproved';
        }
        elseif('spam' === $approved)
        {
            return 'spam';
        }
        elseif('trash' === $approved)
        {
            return 'trash';
        }
        else
        {
            return false;
        }
    }

    function wp_transition_comment_status($new_status, $old_status, $comment)
    {
        /*
	 * Translate raw statuses to human-readable formats for the hooks.
	 * This is not a complete list of comment status, it's only the ones
	 * that need to be renamed.
	 */
        $comment_statuses = [
            0 => 'unapproved',
            'hold' => 'unapproved', // wp_set_comment_status() uses "hold".
            1 => 'approved',
            'approve' => 'approved',   // wp_set_comment_status() uses "approve".
        ];
        if(isset($comment_statuses[$new_status]))
        {
            $new_status = $comment_statuses[$new_status];
        }
        if(isset($comment_statuses[$old_status]))
        {
            $old_status = $comment_statuses[$old_status];
        }

        // Call the hooks.
        if($new_status != $old_status)
        {
            do_action('transition_comment_status', $new_status, $old_status, $comment);

            do_action("comment_{$old_status}_to_{$new_status}", $comment);
        }

        do_action("comment_{$new_status}_{$comment->comment_type}", $comment->comment_ID, $comment);
    }

    function _clear_modified_cache_on_transition_comment_status($new_status, $old_status)
    {
        if('approved' === $new_status || 'approved' === $old_status)
        {
            $data = [];
            foreach(['server', 'gmt', 'blog'] as $timezone)
            {
                $data[] = "lastcommentmodified:$timezone";
            }
            wp_cache_delete_multiple($data, 'timeinfo');
        }
    }

    function wp_get_current_commenter()
    {
        // Cookies should already be sanitized.

        $comment_author = '';
        if(isset($_COOKIE['comment_author_'.COOKIEHASH]))
        {
            $comment_author = $_COOKIE['comment_author_'.COOKIEHASH];
        }

        $comment_author_email = '';
        if(isset($_COOKIE['comment_author_email_'.COOKIEHASH]))
        {
            $comment_author_email = $_COOKIE['comment_author_email_'.COOKIEHASH];
        }

        $comment_author_url = '';
        if(isset($_COOKIE['comment_author_url_'.COOKIEHASH]))
        {
            $comment_author_url = $_COOKIE['comment_author_url_'.COOKIEHASH];
        }

        return apply_filters('wp_get_current_commenter', compact('comment_author', 'comment_author_email', 'comment_author_url'));
    }

    function wp_get_unapproved_comment_author_email()
    {
        $commenter_email = '';

        if(! empty($_GET['unapproved']) && ! empty($_GET['moderation-hash']))
        {
            $comment_id = (int) $_GET['unapproved'];
            $comment = get_comment($comment_id);

            if($comment && hash_equals($_GET['moderation-hash'], wp_hash($comment->comment_date_gmt)))
            {
                // The comment will only be viewable by the comment author for 10 minutes.
                $comment_preview_expires = strtotime($comment->comment_date_gmt.'+10 minutes');

                if(time() < $comment_preview_expires)
                {
                    $commenter_email = $comment->comment_author_email;
                }
            }
        }

        if(! $commenter_email)
        {
            $commenter = wp_get_current_commenter();
            $commenter_email = $commenter['comment_author_email'];
        }

        return $commenter_email;
    }

    function wp_insert_comment($commentdata)
    {
        global $wpdb;

        $data = wp_unslash($commentdata);

        $comment_author = ! isset($data['comment_author']) ? '' : $data['comment_author'];
        $comment_author_email = ! isset($data['comment_author_email']) ? '' : $data['comment_author_email'];
        $comment_author_url = ! isset($data['comment_author_url']) ? '' : $data['comment_author_url'];
        $comment_author_ip = ! isset($data['comment_author_IP']) ? '' : $data['comment_author_IP'];

        $comment_date = ! isset($data['comment_date']) ? current_time('mysql') : $data['comment_date'];
        $comment_date_gmt = ! isset($data['comment_date_gmt']) ? get_gmt_from_date($comment_date) : $data['comment_date_gmt'];

        $comment_post_id = ! isset($data['comment_post_ID']) ? 0 : $data['comment_post_ID'];
        $comment_content = ! isset($data['comment_content']) ? '' : $data['comment_content'];
        $comment_karma = ! isset($data['comment_karma']) ? 0 : $data['comment_karma'];
        $comment_approved = ! isset($data['comment_approved']) ? 1 : $data['comment_approved'];
        $comment_agent = ! isset($data['comment_agent']) ? '' : $data['comment_agent'];
        $comment_type = empty($data['comment_type']) ? 'comment' : $data['comment_type'];
        $comment_parent = ! isset($data['comment_parent']) ? 0 : $data['comment_parent'];

        $user_id = ! isset($data['user_id']) ? 0 : $data['user_id'];

        $compacted = [
            'comment_post_ID' => $comment_post_id,
            'comment_author_IP' => $comment_author_ip,
        ];

        $compacted += compact('comment_author', 'comment_author_email', 'comment_author_url', 'comment_date', 'comment_date_gmt', 'comment_content', 'comment_karma', 'comment_approved', 'comment_agent', 'comment_type', 'comment_parent', 'user_id');

        if(! $wpdb->insert($wpdb->comments, $compacted))
        {
            return false;
        }

        $id = (int) $wpdb->insert_id;

        if(1 == $comment_approved)
        {
            wp_update_comment_count($comment_post_id);

            $data = [];
            foreach(['server', 'gmt', 'blog'] as $timezone)
            {
                $data[] = "lastcommentmodified:$timezone";
            }
            wp_cache_delete_multiple($data, 'timeinfo');
        }

        clean_comment_cache($id);

        $comment = get_comment($id);

        // If metadata is provided, store it.
        if(isset($commentdata['comment_meta']) && is_array($commentdata['comment_meta']))
        {
            foreach($commentdata['comment_meta'] as $meta_key => $meta_value)
            {
                add_comment_meta($comment->comment_ID, $meta_key, $meta_value, true);
            }
        }

        do_action('wp_insert_comment', $id, $comment);

        return $id;
    }

    function wp_filter_comment($commentdata)
    {
        if(isset($commentdata['user_ID']))
        {
            $commentdata['user_id'] = apply_filters('pre_user_id', $commentdata['user_ID']);
        }
        elseif(isset($commentdata['user_id']))
        {
            $commentdata['user_id'] = apply_filters('pre_user_id', $commentdata['user_id']);
        }

        $commentdata['comment_agent'] = apply_filters('pre_comment_user_agent', (isset($commentdata['comment_agent']) ? $commentdata['comment_agent'] : ''));

        $commentdata['comment_author'] = apply_filters('pre_comment_author_name', $commentdata['comment_author']);

        $commentdata['comment_content'] = apply_filters('pre_comment_content', $commentdata['comment_content']);

        $commentdata['comment_author_IP'] = apply_filters('pre_comment_user_ip', $commentdata['comment_author_IP']);

        $commentdata['comment_author_url'] = apply_filters('pre_comment_author_url', $commentdata['comment_author_url']);

        $commentdata['comment_author_email'] = apply_filters('pre_comment_author_email', $commentdata['comment_author_email']);

        $commentdata['filtered'] = true;

        return $commentdata;
    }

    function wp_throttle_comment_flood($block, $time_lastcomment, $time_newcomment)
    {
        if($block)
        { // A plugin has already blocked... we'll let that decision stand.
            return $block;
        }
        if(($time_newcomment - $time_lastcomment) < 15)
        {
            return true;
        }

        return false;
    }

    function wp_new_comment($commentdata, $wp_error = false)
    {
        global $wpdb;

        /*
	 * Normalize `user_ID` to `user_id`, but pass the old key
	 * to the `preprocess_comment` filter for backward compatibility.
	 */
        if(isset($commentdata['user_ID']))
        {
            $commentdata['user_ID'] = (int) $commentdata['user_ID'];
            $commentdata['user_id'] = $commentdata['user_ID'];
        }
        elseif(isset($commentdata['user_id']))
        {
            $commentdata['user_id'] = (int) $commentdata['user_id'];
            $commentdata['user_ID'] = $commentdata['user_id'];
        }

        $prefiltered_user_id = (isset($commentdata['user_id'])) ? (int) $commentdata['user_id'] : 0;

        if(! isset($commentdata['comment_author_IP']))
        {
            $commentdata['comment_author_IP'] = $_SERVER['REMOTE_ADDR'];
        }

        if(! isset($commentdata['comment_agent']))
        {
            $commentdata['comment_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        }

        $commentdata = apply_filters('preprocess_comment', $commentdata);

        $commentdata['comment_post_ID'] = (int) $commentdata['comment_post_ID'];

        // Normalize `user_ID` to `user_id` again, after the filter.
        if(isset($commentdata['user_ID']) && $prefiltered_user_id !== (int) $commentdata['user_ID'])
        {
            $commentdata['user_ID'] = (int) $commentdata['user_ID'];
            $commentdata['user_id'] = $commentdata['user_ID'];
        }
        elseif(isset($commentdata['user_id']))
        {
            $commentdata['user_id'] = (int) $commentdata['user_id'];
            $commentdata['user_ID'] = $commentdata['user_id'];
        }

        $commentdata['comment_parent'] = isset($commentdata['comment_parent']) ? absint($commentdata['comment_parent']) : 0;

        $parent_status = ($commentdata['comment_parent'] > 0) ? wp_get_comment_status($commentdata['comment_parent']) : '';

        $commentdata['comment_parent'] = ('approved' === $parent_status || 'unapproved' === $parent_status) ? $commentdata['comment_parent'] : 0;

        $commentdata['comment_author_IP'] = preg_replace('/[^0-9a-fA-F:., ]/', '', $commentdata['comment_author_IP']);

        $commentdata['comment_agent'] = substr($commentdata['comment_agent'], 0, 254);

        if(empty($commentdata['comment_date']))
        {
            $commentdata['comment_date'] = current_time('mysql');
        }

        if(empty($commentdata['comment_date_gmt']))
        {
            $commentdata['comment_date_gmt'] = current_time('mysql', 1);
        }

        if(empty($commentdata['comment_type']))
        {
            $commentdata['comment_type'] = 'comment';
        }

        $commentdata = wp_filter_comment($commentdata);

        $commentdata['comment_approved'] = wp_allow_comment($commentdata, $wp_error);

        if(is_wp_error($commentdata['comment_approved']))
        {
            return $commentdata['comment_approved'];
        }

        $comment_id = wp_insert_comment($commentdata);

        if(! $comment_id)
        {
            $fields = ['comment_author', 'comment_author_email', 'comment_author_url', 'comment_content'];

            foreach($fields as $field)
            {
                if(isset($commentdata[$field]))
                {
                    $commentdata[$field] = $wpdb->strip_invalid_text_for_column($wpdb->comments, $field, $commentdata[$field]);
                }
            }

            $commentdata = wp_filter_comment($commentdata);

            $commentdata['comment_approved'] = wp_allow_comment($commentdata, $wp_error);
            if(is_wp_error($commentdata['comment_approved']))
            {
                return $commentdata['comment_approved'];
            }

            $comment_id = wp_insert_comment($commentdata);
            if(! $comment_id)
            {
                return false;
            }
        }

        do_action('comment_post', $comment_id, $commentdata['comment_approved'], $commentdata);

        return $comment_id;
    }

    function wp_new_comment_notify_moderator($comment_id)
    {
        $comment = get_comment($comment_id);

        // Only send notifications for pending comments.
        $maybe_notify = ('0' == $comment->comment_approved);

        $maybe_notify = apply_filters('notify_moderator', $maybe_notify, $comment_id);

        if(! $maybe_notify)
        {
            return false;
        }

        return wp_notify_moderator($comment_id);
    }

    function wp_new_comment_notify_postauthor($comment_id)
    {
        $comment = get_comment($comment_id);

        $maybe_notify = get_option('comments_notify');

        $maybe_notify = apply_filters('notify_post_author', $maybe_notify, $comment_id);

        /*
	 * wp_notify_postauthor() checks if notifying the author of their own comment.
	 * By default, it won't, but filters can override this.
	 */
        if(! $maybe_notify)
        {
            return false;
        }

        // Only send notifications for approved comments.
        if(! isset($comment->comment_approved) || '1' != $comment->comment_approved)
        {
            return false;
        }

        return wp_notify_postauthor($comment_id);
    }

    function wp_set_comment_status($comment_id, $comment_status, $wp_error = false)
    {
        global $wpdb;

        switch($comment_status)
        {
            case 'hold':
            case '0':
                $status = '0';
                break;
            case 'approve':
            case '1':
                $status = '1';
                add_action('wp_set_comment_status', 'wp_new_comment_notify_postauthor');
                break;
            case 'spam':
                $status = 'spam';
                break;
            case 'trash':
                $status = 'trash';
                break;
            default:
                return false;
        }

        $comment_old = clone get_comment($comment_id);

        if(! $wpdb->update($wpdb->comments, ['comment_approved' => $status], ['comment_ID' => $comment_old->comment_ID]))
        {
            if($wp_error)
            {
                return new WP_Error('db_update_error', __('Could not update comment status.'), $wpdb->last_error);
            }
            else
            {
                return false;
            }
        }

        clean_comment_cache($comment_old->comment_ID);

        $comment = get_comment($comment_old->comment_ID);

        do_action('wp_set_comment_status', $comment->comment_ID, $comment_status);

        wp_transition_comment_status($comment_status, $comment_old->comment_approved, $comment);

        wp_update_comment_count($comment->comment_post_ID);

        return true;
    }

    function wp_update_comment($commentarr, $wp_error = false)
    {
        global $wpdb;

        // First, get all of the original fields.
        $comment = get_comment($commentarr['comment_ID'], ARRAY_A);

        if(empty($comment))
        {
            if($wp_error)
            {
                return new WP_Error('invalid_comment_id', __('Invalid comment ID.'));
            }
            else
            {
                return false;
            }
        }

        // Make sure that the comment post ID is valid (if specified).
        if(! empty($commentarr['comment_post_ID']) && ! get_post($commentarr['comment_post_ID']))
        {
            if($wp_error)
            {
                return new WP_Error('invalid_post_id', __('Invalid post ID.'));
            }
            else
            {
                return false;
            }
        }

        $filter_comment = false;
        if(! has_filter('pre_comment_content', 'wp_filter_kses'))
        {
            $filter_comment = ! user_can(isset($comment['user_id']) ? $comment['user_id'] : 0, 'unfiltered_html');
        }

        if($filter_comment)
        {
            add_filter('pre_comment_content', 'wp_filter_kses');
        }

        // Escape data pulled from DB.
        $comment = wp_slash($comment);

        $old_status = $comment['comment_approved'];

        // Merge old and new fields with new fields overwriting old ones.
        $commentarr = array_merge($comment, $commentarr);

        $commentarr = wp_filter_comment($commentarr);

        if($filter_comment)
        {
            remove_filter('pre_comment_content', 'wp_filter_kses');
        }

        // Now extract the merged array.
        $data = wp_unslash($commentarr);

        $data['comment_content'] = apply_filters('comment_save_pre', $data['comment_content']);

        $data['comment_date_gmt'] = get_gmt_from_date($data['comment_date']);

        if(! isset($data['comment_approved']))
        {
            $data['comment_approved'] = 1;
        }
        elseif('hold' === $data['comment_approved'])
        {
            $data['comment_approved'] = 0;
        }
        elseif('approve' === $data['comment_approved'])
        {
            $data['comment_approved'] = 1;
        }

        $comment_id = $data['comment_ID'];
        $comment_post_id = $data['comment_post_ID'];

        $data = apply_filters('wp_update_comment_data', $data, $comment, $commentarr);

        // Do not carry on on failure.
        if(is_wp_error($data))
        {
            if($wp_error)
            {
                return $data;
            }
            else
            {
                return false;
            }
        }

        $keys = [
            'comment_post_ID',
            'comment_author',
            'comment_author_email',
            'comment_author_url',
            'comment_author_IP',
            'comment_date',
            'comment_date_gmt',
            'comment_content',
            'comment_karma',
            'comment_approved',
            'comment_agent',
            'comment_type',
            'comment_parent',
            'user_id',
        ];

        $data = wp_array_slice_assoc($data, $keys);

        $result = $wpdb->update($wpdb->comments, $data, ['comment_ID' => $comment_id]);

        if(false === $result)
        {
            if($wp_error)
            {
                return new WP_Error('db_update_error', __('Could not update comment in the database.'), $wpdb->last_error);
            }
            else
            {
                return false;
            }
        }

        // If metadata is provided, store it.
        if(isset($commentarr['comment_meta']) && is_array($commentarr['comment_meta']))
        {
            foreach($commentarr['comment_meta'] as $meta_key => $meta_value)
            {
                update_comment_meta($comment_id, $meta_key, $meta_value);
            }
        }

        clean_comment_cache($comment_id);
        wp_update_comment_count($comment_post_id);

        do_action('edit_comment', $comment_id, $data);

        $comment = get_comment($comment_id);

        wp_transition_comment_status($comment->comment_approved, $old_status, $comment);

        return $result;
    }

    function wp_defer_comment_counting($defer = null)
    {
        static $_defer = false;

        if(is_bool($defer))
        {
            $_defer = $defer;
            // Flush any deferred counts.
            if(! $defer)
            {
                wp_update_comment_count(null, true);
            }
        }

        return $_defer;
    }

    function wp_update_comment_count($post_id, $do_deferred = false)
    {
        static $_deferred = [];

        if(empty($post_id) && ! $do_deferred)
        {
            return false;
        }

        if($do_deferred)
        {
            $_deferred = array_unique($_deferred);
            foreach($_deferred as $i => $_post_id)
            {
                wp_update_comment_count_now($_post_id);
                unset($_deferred[$i]);
            }
        }

        if(wp_defer_comment_counting())
        {
            $_deferred[] = $post_id;

            return true;
        }
        elseif($post_id)
        {
            return wp_update_comment_count_now($post_id);
        }
    }

    function wp_update_comment_count_now($post_id)
    {
        global $wpdb;

        $post_id = (int) $post_id;

        if(! $post_id)
        {
            return false;
        }

        wp_cache_delete('comments-0', 'counts');
        wp_cache_delete("comments-{$post_id}", 'counts');

        $post = get_post($post_id);

        if(! $post)
        {
            return false;
        }

        $old = (int) $post->comment_count;

        $new = apply_filters('pre_wp_update_comment_count_now', null, $old, $post_id);

        if(is_null($new))
        {
            $new = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_approved = '1'", $post_id));
        }
        else
        {
            $new = (int) $new;
        }

        $wpdb->update($wpdb->posts, ['comment_count' => $new], ['ID' => $post_id]);

        clean_post_cache($post);

        do_action('wp_update_comment_count', $post_id, $new, $old);

        do_action("edit_post_{$post->post_type}", $post_id, $post);

        do_action('edit_post', $post_id, $post);

        return true;
    }

//
// Ping and trackback functions.
//

    function discover_pingback_server_uri($url, $deprecated = '')
    {
        if(! empty($deprecated))
        {
            _deprecated_argument(__FUNCTION__, '2.7.0');
        }

        $pingback_str_dquote = 'rel="pingback"';
        $pingback_str_squote = 'rel=\'pingback\'';

        $parsed_url = parse_url($url);

        if(! isset($parsed_url['host']))
        { // Not a URL. This should never happen.
            return false;
        }

        // Do not search for a pingback server on our own uploads.
        $uploads_dir = wp_get_upload_dir();
        if(str_starts_with($url, $uploads_dir['baseurl']))
        {
            return false;
        }

        $response = wp_safe_remote_head($url, [
            'timeout' => 2,
            'httpversion' => '1.0',
        ]);

        if(is_wp_error($response))
        {
            return false;
        }

        if(wp_remote_retrieve_header($response, 'X-Pingback'))
        {
            return wp_remote_retrieve_header($response, 'X-Pingback');
        }

        // Not an (x)html, sgml, or xml page, no use going further.
        if(preg_match('#(image|audio|video|model)/#is', wp_remote_retrieve_header($response, 'Content-Type')))
        {
            return false;
        }

        // Now do a GET since we're going to look in the HTML headers (and we're sure it's not a binary file).
        $response = wp_safe_remote_get($url, [
            'timeout' => 2,
            'httpversion' => '1.0',
        ]);

        if(is_wp_error($response))
        {
            return false;
        }

        $contents = wp_remote_retrieve_body($response);

        $pingback_link_offset_dquote = strpos($contents, $pingback_str_dquote);
        $pingback_link_offset_squote = strpos($contents, $pingback_str_squote);
        if($pingback_link_offset_dquote || $pingback_link_offset_squote)
        {
            $quote = ($pingback_link_offset_dquote) ? '"' : '\'';
            $pingback_link_offset = ('"' === $quote) ? $pingback_link_offset_dquote : $pingback_link_offset_squote;
            $pingback_href_pos = strpos($contents, 'href=', $pingback_link_offset);
            $pingback_href_start = $pingback_href_pos + 6;
            $pingback_href_end = strpos($contents, $quote, $pingback_href_start);
            $pingback_server_url_len = $pingback_href_end - $pingback_href_start;
            $pingback_server_url = substr($contents, $pingback_href_start, $pingback_server_url_len);

            // We may find rel="pingback" but an incomplete pingback URL.
            if($pingback_server_url_len > 0)
            { // We got it!
                return $pingback_server_url;
            }
        }

        return false;
    }

    function do_all_pings()
    {
        do_action('do_all_pings');
    }

    function do_all_pingbacks()
    {
        $pings = get_posts([
                               'post_type' => get_post_types(),
                               'suppress_filters' => false,
                               'nopaging' => true,
                               'meta_key' => '_pingme',
                               'fields' => 'ids',
                           ]);

        foreach($pings as $ping)
        {
            delete_post_meta($ping, '_pingme');
            pingback(null, $ping);
        }
    }

    function do_all_enclosures()
    {
        $enclosures = get_posts([
                                    'post_type' => get_post_types(),
                                    'suppress_filters' => false,
                                    'nopaging' => true,
                                    'meta_key' => '_encloseme',
                                    'fields' => 'ids',
                                ]);

        foreach($enclosures as $enclosure)
        {
            delete_post_meta($enclosure, '_encloseme');
            do_enclose(null, $enclosure);
        }
    }

    function do_all_trackbacks()
    {
        $trackbacks = get_posts([
                                    'post_type' => get_post_types(),
                                    'suppress_filters' => false,
                                    'nopaging' => true,
                                    'meta_key' => '_trackbackme',
                                    'fields' => 'ids',
                                ]);

        foreach($trackbacks as $trackback)
        {
            delete_post_meta($trackback, '_trackbackme');
            do_trackbacks($trackback);
        }
    }

    function do_trackbacks($post)
    {
        global $wpdb;

        $post = get_post($post);

        if(! $post)
        {
            return false;
        }

        $to_ping = get_to_ping($post);
        $pinged = get_pung($post);

        if(empty($to_ping))
        {
            $wpdb->update($wpdb->posts, ['to_ping' => ''], ['ID' => $post->ID]);

            return;
        }

        if(empty($post->post_excerpt))
        {
            $excerpt = apply_filters('the_content', $post->post_content, $post->ID);
        }
        else
        {
            $excerpt = apply_filters('the_excerpt', $post->post_excerpt);
        }

        $excerpt = str_replace(']]>', ']]&gt;', $excerpt);
        $excerpt = wp_html_excerpt($excerpt, 252, '&#8230;');

        $post_title = apply_filters('the_title', $post->post_title, $post->ID);
        $post_title = strip_tags($post_title);

        if($to_ping)
        {
            foreach((array) $to_ping as $tb_ping)
            {
                $tb_ping = trim($tb_ping);
                if(! in_array($tb_ping, $pinged, true))
                {
                    trackback($tb_ping, $post_title, $excerpt, $post->ID);
                    $pinged[] = $tb_ping;
                }
                else
                {
                    $wpdb->query(
                        $wpdb->prepare(
                            "UPDATE $wpdb->posts SET to_ping = TRIM(REPLACE(to_ping, %s,
					'')) WHERE ID = %d", $tb_ping, $post->ID
                        )
                    );
                }
            }
        }
    }

    function generic_ping($post_id = 0)
    {
        $services = get_option('ping_sites');

        $services = explode("\n", $services);
        foreach((array) $services as $service)
        {
            $service = trim($service);
            if('' !== $service)
            {
                weblog_ping($service);
            }
        }

        return $post_id;
    }

    function pingback($content, $post)
    {
        require_once ABSPATH.WPINC.'/class-IXR.php';
        require_once ABSPATH.WPINC.'/class-wp-http-ixr-client.php';

        // Original code by Mort (http://mort.mine.nu:8080).
        $post_links = [];

        $post = get_post($post);

        if(! $post)
        {
            return;
        }

        $pung = get_pung($post);

        if(empty($content))
        {
            $content = $post->post_content;
        }

        /*
	 * Step 1.
	 * Parsing the post, external links (if any) are stored in the $post_links array.
	 */
        $post_links_temp = wp_extract_urls($content);

        /*
	 * Step 2.
	 * Walking through the links array.
	 * First we get rid of links pointing to sites, not to specific files.
	 * Example:
	 * http://dummy-weblog.org
	 * http://dummy-weblog.org/
	 * http://dummy-weblog.org/post.php
	 * We don't wanna ping first and second types, even if they have a valid <link/>.
	 */
        foreach((array) $post_links_temp as $link_test)
        {
            // If we haven't pung it already and it isn't a link to itself.
            if(
                ! in_array($link_test, $pung, true) && (url_to_postid($link_test) != $post->ID) // Also, let's never ping local attachments.
                && ! is_local_attachment($link_test)
            )
            {
                $test = parse_url($link_test);
                if($test)
                {
                    if(isset($test['query']))
                    {
                        $post_links[] = $link_test;
                    }
                    elseif(isset($test['path']) && ('/' !== $test['path']) && ('' !== $test['path']))
                    {
                        $post_links[] = $link_test;
                    }
                }
            }
        }

        $post_links = array_unique($post_links);

        do_action_ref_array('pre_ping', [&$post_links, &$pung, $post->ID]);

        foreach((array) $post_links as $pagelinkedto)
        {
            $pingback_server_url = discover_pingback_server_uri($pagelinkedto);

            if($pingback_server_url)
            {
                if(function_exists('set_time_limit'))
                {
                    set_time_limit(60);
                }

                // Now, the RPC call.
                $pagelinkedfrom = get_permalink($post);

                // Using a timeout of 3 seconds should be enough to cover slow servers.
                $client = new WP_HTTP_IXR_Client($pingback_server_url);
                $client->timeout = 3;

                $client->useragent = apply_filters('pingback_useragent', $client->useragent.' -- WordPress/'.get_bloginfo('version'), $client->useragent, $pingback_server_url, $pagelinkedto, $pagelinkedfrom);
                // When set to true, this outputs debug messages by itself.
                $client->debug = false;

                if($client->query('pingback.ping', $pagelinkedfrom, $pagelinkedto) || (isset($client->error->code) && 48 == $client->error->code))
                { // Already registered.
                    add_ping($post, $pagelinkedto);
                }
            }
        }
    }

    function privacy_ping_filter($sites)
    {
        if('0' != get_option('blog_public'))
        {
            return $sites;
        }
        else
        {
            return '';
        }
    }

    function trackback($trackback_url, $title, $excerpt, $ID)
    {
        global $wpdb;

        if(empty($trackback_url))
        {
            return;
        }

        $options = [];
        $options['timeout'] = 10;
        $options['body'] = [
            'title' => $title,
            'url' => get_permalink($ID),
            'blog_name' => get_option('blogname'),
            'excerpt' => $excerpt,
        ];

        $response = wp_safe_remote_post($trackback_url, $options);

        if(is_wp_error($response))
        {
            return;
        }

        $wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET pinged = CONCAT(pinged, '\n', %s) WHERE ID = %d", $trackback_url, $ID));

        return $wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET to_ping = TRIM(REPLACE(to_ping, %s, '')) WHERE ID = %d", $trackback_url, $ID));
    }

    function weblog_ping($server = '', $path = '')
    {
        require_once ABSPATH.WPINC.'/class-IXR.php';
        require_once ABSPATH.WPINC.'/class-wp-http-ixr-client.php';

        // Using a timeout of 3 seconds should be enough to cover slow servers.
        $client = new WP_HTTP_IXR_Client($server, ((! strlen(trim($path)) || ('/' === $path)) ? false : $path));
        $client->timeout = 3;
        $client->useragent .= ' -- WordPress/'.get_bloginfo('version');

        // When set to true, this outputs debug messages by itself.
        $client->debug = false;
        $home = trailingslashit(home_url());
        if(! $client->query('weblogUpdates.extendedPing', get_option('blogname'), $home, get_bloginfo('rss2_url')))
        { // Then try a normal ping.
            $client->query('weblogUpdates.ping', get_option('blogname'), $home);
        }
    }

    function pingback_ping_source_uri($source_uri)
    {
        return (string) wp_http_validate_url($source_uri);
    }

    function xmlrpc_pingback_error($ixr_error)
    {
        if(48 === $ixr_error->code)
        {
            return $ixr_error;
        }

        return new IXR_Error(0, '');
    }

//
// Cache.
//

    function clean_comment_cache($ids)
    {
        $comment_ids = (array) $ids;
        wp_cache_delete_multiple($comment_ids, 'comment');
        foreach($comment_ids as $id)
        {
            do_action('clean_comment_cache', $id);
        }

        wp_cache_set_comments_last_changed();
    }

    function update_comment_cache($comments, $update_meta_cache = true)
    {
        $data = [];
        foreach((array) $comments as $comment)
        {
            $data[$comment->comment_ID] = $comment;
        }
        wp_cache_add_multiple($data, 'comment');

        if($update_meta_cache)
        {
            // Avoid `wp_list_pluck()` in case `$comments` is passed by reference.
            $comment_ids = [];
            foreach($comments as $comment)
            {
                $comment_ids[] = $comment->comment_ID;
            }
            update_meta_cache('comment', $comment_ids);
        }
    }

    function _prime_comment_caches($comment_ids, $update_meta_cache = true)
    {
        global $wpdb;

        $non_cached_ids = _get_non_cached_ids($comment_ids, 'comment');
        if(! empty($non_cached_ids))
        {
            $fresh_comments = $wpdb->get_results(sprintf("SELECT $wpdb->comments.* FROM $wpdb->comments WHERE comment_ID IN (%s)", implode(',', array_map('intval', $non_cached_ids))));

            update_comment_cache($fresh_comments, false);
        }

        if($update_meta_cache)
        {
            wp_lazyload_comment_meta($comment_ids);
        }
    }

//
// Internal.
//

    function _close_comments_for_old_posts($posts, $query)
    {
        if(empty($posts) || ! $query->is_singular() || ! get_option('close_comments_for_old_posts'))
        {
            return $posts;
        }

        $post_types = apply_filters('close_comments_for_post_types', ['post']);
        if(! in_array($posts[0]->post_type, $post_types, true))
        {
            return $posts;
        }

        $days_old = (int) get_option('close_comments_days_old');
        if(! $days_old)
        {
            return $posts;
        }

        if(time() - strtotime($posts[0]->post_date_gmt) > ($days_old * DAY_IN_SECONDS))
        {
            $posts[0]->comment_status = 'closed';
            $posts[0]->ping_status = 'closed';
        }

        return $posts;
    }

    function _close_comments_for_old_post($open, $post_id)
    {
        if(! $open)
        {
            return $open;
        }

        if(! get_option('close_comments_for_old_posts'))
        {
            return $open;
        }

        $days_old = (int) get_option('close_comments_days_old');
        if(! $days_old)
        {
            return $open;
        }

        $post = get_post($post_id);

        $post_types = apply_filters('close_comments_for_post_types', ['post']);
        if(! in_array($post->post_type, $post_types, true))
        {
            return $open;
        }

        // Undated drafts should not show up as comments closed.
        if('0000-00-00 00:00:00' === $post->post_date_gmt)
        {
            return $open;
        }

        if(time() - strtotime($post->post_date_gmt) > ($days_old * DAY_IN_SECONDS))
        {
            return false;
        }

        return $open;
    }

    function wp_handle_comment_submission($comment_data)
    {
        $comment_post_id = 0;
        $comment_author = '';
        $comment_author_email = '';
        $comment_author_url = '';
        $comment_content = '';
        $comment_parent = 0;
        $user_id = 0;

        if(isset($comment_data['comment_post_ID']))
        {
            $comment_post_id = (int) $comment_data['comment_post_ID'];
        }
        if(isset($comment_data['author']) && is_string($comment_data['author']))
        {
            $comment_author = trim(strip_tags($comment_data['author']));
        }
        if(isset($comment_data['email']) && is_string($comment_data['email']))
        {
            $comment_author_email = trim($comment_data['email']);
        }
        if(isset($comment_data['url']) && is_string($comment_data['url']))
        {
            $comment_author_url = trim($comment_data['url']);
        }
        if(isset($comment_data['comment']) && is_string($comment_data['comment']))
        {
            $comment_content = trim($comment_data['comment']);
        }
        if(isset($comment_data['comment_parent']))
        {
            $comment_parent = absint($comment_data['comment_parent']);
            $comment_parent_object = get_comment($comment_parent);

            if(0 !== $comment_parent && (! $comment_parent_object instanceof WP_Comment || 0 === (int) $comment_parent_object->comment_approved))
            {
                do_action('comment_reply_to_unapproved_comment', $comment_post_id, $comment_parent);

                return new WP_Error('comment_reply_to_unapproved_comment', __('Sorry, replies to unapproved comments are not allowed.'), 403);
            }
        }

        $post = get_post($comment_post_id);

        if(empty($post->comment_status))
        {
            do_action('comment_id_not_found', $comment_post_id);

            return new WP_Error('comment_id_not_found');
        }

        // get_post_status() will get the parent status for attachments.
        $status = get_post_status($post);

        if(('private' === $status) && ! current_user_can('read_post', $comment_post_id))
        {
            return new WP_Error('comment_id_not_found');
        }

        $status_obj = get_post_status_object($status);

        if(! comments_open($comment_post_id))
        {
            do_action('comment_closed', $comment_post_id);

            return new WP_Error('comment_closed', __('Sorry, comments are closed for this item.'), 403);
        }
        elseif('trash' === $status)
        {
            do_action('comment_on_trash', $comment_post_id);

            return new WP_Error('comment_on_trash');
        }
        elseif(! $status_obj->public && ! $status_obj->private)
        {
            do_action('comment_on_draft', $comment_post_id);

            if(current_user_can('read_post', $comment_post_id))
            {
                return new WP_Error('comment_on_draft', __('Sorry, comments are not allowed for this item.'), 403);
            }
            else
            {
                return new WP_Error('comment_on_draft');
            }
        }
        elseif(post_password_required($comment_post_id))
        {
            do_action('comment_on_password_protected', $comment_post_id);

            return new WP_Error('comment_on_password_protected');
        }
        else
        {
            do_action('pre_comment_on_post', $comment_post_id);
        }

        // If the user is logged in.
        $user = wp_get_current_user();
        if($user->exists())
        {
            if(empty($user->display_name))
            {
                $user->display_name = $user->user_login;
            }

            $comment_author = $user->display_name;
            $comment_author_email = $user->user_email;
            $comment_author_url = $user->user_url;
            $user_id = $user->ID;

            if(current_user_can('unfiltered_html'))
            {
                if(! isset($comment_data['_wp_unfiltered_html_comment']) || ! wp_verify_nonce($comment_data['_wp_unfiltered_html_comment'], 'unfiltered-html-comment_'.$comment_post_id))
                {
                    kses_remove_filters(); // Start with a clean slate.
                    kses_init_filters();   // Set up the filters.
                    remove_filter('pre_comment_content', 'wp_filter_post_kses');
                    add_filter('pre_comment_content', 'wp_filter_kses');
                }
            }
        }
        else
        {
            if(get_option('comment_registration'))
            {
                return new WP_Error('not_logged_in', __('Sorry, you must be logged in to comment.'), 403);
            }
        }

        $comment_type = 'comment';

        if(get_option('require_name_email') && ! $user->exists())
        {
            if('' == $comment_author_email || '' == $comment_author)
            {
                return new WP_Error('require_name_email', __('<strong>Error:</strong> Please fill the required fields.'), 200);
            }
            elseif(! is_email($comment_author_email))
            {
                return new WP_Error('require_valid_email', __('<strong>Error:</strong> Please enter a valid email address.'), 200);
            }
        }

        $commentdata = [
            'comment_post_ID' => $comment_post_id,
        ];

        $commentdata += compact('comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_type', 'comment_parent', 'user_id');

        $allow_empty_comment = apply_filters('allow_empty_comment', false, $commentdata);
        if('' === $comment_content && ! $allow_empty_comment)
        {
            return new WP_Error('require_valid_comment', __('<strong>Error:</strong> Please type your comment text.'), 200);
        }

        $check_max_lengths = wp_check_comment_data_max_lengths($commentdata);
        if(is_wp_error($check_max_lengths))
        {
            return $check_max_lengths;
        }

        $comment_id = wp_new_comment(wp_slash($commentdata), true);
        if(is_wp_error($comment_id))
        {
            return $comment_id;
        }

        if(! $comment_id)
        {
            return new WP_Error('comment_save_error', __('<strong>Error:</strong> The comment could not be saved. Please try again later.'), 500);
        }

        return get_comment($comment_id);
    }

    function wp_register_comment_personal_data_exporter($exporters)
    {
        $exporters['wordpress-comments'] = [
            'exporter_friendly_name' => __('WordPress Comments'),
            'callback' => 'wp_comments_personal_data_exporter',
        ];

        return $exporters;
    }

    function wp_comments_personal_data_exporter($email_address, $page = 1)
    {
        // Limit us to 500 comments at a time to avoid timing out.
        $number = 500;
        $page = (int) $page;

        $data_to_export = [];

        $comments = get_comments([
                                     'author_email' => $email_address,
                                     'number' => $number,
                                     'paged' => $page,
                                     'orderby' => 'comment_ID',
                                     'order' => 'ASC',
                                     'update_comment_meta_cache' => false,
                                 ]);

        $comment_prop_to_export = [
            'comment_author' => __('Comment Author'),
            'comment_author_email' => __('Comment Author Email'),
            'comment_author_url' => __('Comment Author URL'),
            'comment_author_IP' => __('Comment Author IP'),
            'comment_agent' => __('Comment Author User Agent'),
            'comment_date' => __('Comment Date'),
            'comment_content' => __('Comment Content'),
            'comment_link' => __('Comment URL'),
        ];

        foreach((array) $comments as $comment)
        {
            $comment_data_to_export = [];

            foreach($comment_prop_to_export as $key => $name)
            {
                $value = '';

                switch($key)
                {
                    case 'comment_author':
                    case 'comment_author_email':
                    case 'comment_author_url':
                    case 'comment_author_IP':
                    case 'comment_agent':
                    case 'comment_date':
                        $value = $comment->{$key};
                        break;

                    case 'comment_content':
                        $value = get_comment_text($comment->comment_ID);
                        break;

                    case 'comment_link':
                        $value = get_comment_link($comment->comment_ID);
                        $value = sprintf('<a href="%s" target="_blank" rel="noopener">%s</a>', esc_url($value), esc_html($value));
                        break;
                }

                if(! empty($value))
                {
                    $comment_data_to_export[] = [
                        'name' => $name,
                        'value' => $value,
                    ];
                }
            }

            $data_to_export[] = [
                'group_id' => 'comments',
                'group_label' => __('Comments'),
                'group_description' => __('User&#8217;s comment data.'),
                'item_id' => "comment-{$comment->comment_ID}",
                'data' => $comment_data_to_export,
            ];
        }

        $done = count($comments) < $number;

        return [
            'data' => $data_to_export,
            'done' => $done,
        ];
    }

    function wp_register_comment_personal_data_eraser($erasers)
    {
        $erasers['wordpress-comments'] = [
            'eraser_friendly_name' => __('WordPress Comments'),
            'callback' => 'wp_comments_personal_data_eraser',
        ];

        return $erasers;
    }

    function wp_comments_personal_data_eraser($email_address, $page = 1)
    {
        global $wpdb;

        if(empty($email_address))
        {
            return [
                'items_removed' => false,
                'items_retained' => false,
                'messages' => [],
                'done' => true,
            ];
        }

        // Limit us to 500 comments at a time to avoid timing out.
        $number = 500;
        $page = (int) $page;
        $items_removed = false;
        $items_retained = false;

        $comments = get_comments([
                                     'author_email' => $email_address,
                                     'number' => $number,
                                     'paged' => $page,
                                     'orderby' => 'comment_ID',
                                     'order' => 'ASC',
                                     'include_unapproved' => true,
                                 ]);

        /* translators: Name of a comment's author after being anonymized. */
        $anon_author = __('Anonymous');
        $messages = [];

        foreach((array) $comments as $comment)
        {
            $anonymized_comment = [];
            $anonymized_comment['comment_agent'] = '';
            $anonymized_comment['comment_author'] = $anon_author;
            $anonymized_comment['comment_author_email'] = '';
            $anonymized_comment['comment_author_IP'] = wp_privacy_anonymize_data('ip', $comment->comment_author_IP);
            $anonymized_comment['comment_author_url'] = '';
            $anonymized_comment['user_id'] = 0;

            $comment_id = (int) $comment->comment_ID;

            $anon_message = apply_filters('wp_anonymize_comment', true, $comment, $anonymized_comment);

            if(true !== $anon_message)
            {
                if($anon_message && is_string($anon_message))
                {
                    $messages[] = esc_html($anon_message);
                }
                else
                {
                    /* translators: %d: Comment ID. */
                    $messages[] = sprintf(__('Comment %d contains personal data but could not be anonymized.'), $comment_id);
                }

                $items_retained = true;

                continue;
            }

            $args = [
                'comment_ID' => $comment_id,
            ];

            $updated = $wpdb->update($wpdb->comments, $anonymized_comment, $args);

            if($updated)
            {
                $items_removed = true;
                clean_comment_cache($comment_id);
            }
            else
            {
                $items_retained = true;
            }
        }

        $done = count($comments) < $number;

        return [
            'items_removed' => $items_removed,
            'items_retained' => $items_retained,
            'messages' => $messages,
            'done' => $done,
        ];
    }

    function wp_cache_set_comments_last_changed()
    {
        wp_cache_set_last_changed('comment');
    }

    function _wp_batch_update_comment_type()
    {
        global $wpdb;

        $lock_name = 'update_comment_type.lock';

        // Try to lock.
        $lock_result = $wpdb->query($wpdb->prepare("INSERT IGNORE INTO `$wpdb->options` ( `option_name`, `option_value`, `autoload` ) VALUES (%s, %s, 'no') /* LOCK */", $lock_name, time()));

        if(! $lock_result)
        {
            $lock_result = get_option($lock_name);

            // Bail if we were unable to create a lock, or if the existing lock is still valid.
            if(! $lock_result || ($lock_result > (time() - HOUR_IN_SECONDS)))
            {
                wp_schedule_single_event(time() + (5 * MINUTE_IN_SECONDS), 'wp_update_comment_type_batch');

                return;
            }
        }

        // Update the lock, as by this point we've definitely got a lock, just need to fire the actions.
        update_option($lock_name, time());

        // Check if there's still an empty comment type.
        $empty_comment_type = $wpdb->get_var(
            "SELECT comment_ID FROM $wpdb->comments
		WHERE comment_type = ''
		LIMIT 1"
        );

        // No empty comment type, we're done here.
        if(! $empty_comment_type)
        {
            update_option('finished_updating_comment_type', true);
            delete_option($lock_name);

            return;
        }

        // Empty comment type found? We'll need to run this script again.
        wp_schedule_single_event(time() + (2 * MINUTE_IN_SECONDS), 'wp_update_comment_type_batch');

        $comment_batch_size = (int) apply_filters('wp_update_comment_type_batch_size', 100);

        // Get the IDs of the comments to update.
        $comment_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT comment_ID
			FROM {$wpdb->comments}
			WHERE comment_type = ''
			ORDER BY comment_ID DESC
			LIMIT %d", $comment_batch_size
            )
        );

        if($comment_ids)
        {
            $comment_id_list = implode(',', $comment_ids);

            // Update the `comment_type` field value to be `comment` for the next batch of comments.
            $wpdb->query(
                "UPDATE {$wpdb->comments}
			SET comment_type = 'comment'
			WHERE comment_type = ''
			AND comment_ID IN ({$comment_id_list})" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            );

            // Make sure to clean the comment cache.
            clean_comment_cache($comment_ids);
        }

        delete_option($lock_name);
    }

    function _wp_check_for_scheduled_update_comment_type()
    {
        if(! get_option('finished_updating_comment_type') && ! wp_next_scheduled('wp_update_comment_type_batch'))
        {
            wp_schedule_single_event(time() + MINUTE_IN_SECONDS, 'wp_update_comment_type_batch');
        }
    }
