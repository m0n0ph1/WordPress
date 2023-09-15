<?php

    function get_the_author($deprecated = '')
    {
        global $authordata;

        if(! empty($deprecated))
        {
            _deprecated_argument(__FUNCTION__, '2.1.0');
        }

        return apply_filters('the_author', is_object($authordata) ? $authordata->display_name : '');
    }

    function the_author($deprecated = '', $deprecated_echo = true)
    {
        if(! empty($deprecated))
        {
            _deprecated_argument(__FUNCTION__, '2.1.0');
        }

        if(true !== $deprecated_echo)
        {
            _deprecated_argument(__FUNCTION__, '1.5.0', sprintf(/* translators: %s: get_the_author() */ __('Use %s instead if you do not want the value echoed.'), '<code>get_the_author()</code>'));
        }

        if($deprecated_echo)
        {
            echo get_the_author();
        }

        return get_the_author();
    }

    function get_the_modified_author()
    {
        $last_id = get_post_meta(get_post()->ID, '_edit_last', true);

        if($last_id)
        {
            $last_user = get_userdata($last_id);

            return apply_filters('the_modified_author', $last_user ? $last_user->display_name : '');
        }
    }

    function the_modified_author()
    {
        echo get_the_modified_author();
    }

    function get_the_author_meta($field = '', $user_id = false)
    {
        $original_user_id = $user_id;

        if($user_id)
        {
            $authordata = get_userdata($user_id);
        }
        else
        {
            global $authordata;
            $user_id = isset($authordata->ID) ? $authordata->ID : 0;
        }

        if(
            in_array($field, [
                'login',
                'pass',
                'nicename',
                'email',
                'url',
                'registered',
                'activation_key',
                'status'
            ],       true)
        )
        {
            $field = 'user_'.$field;
        }

        $value = isset($authordata->$field) ? $authordata->$field : '';

        return apply_filters("get_the_author_{$field}", $value, $user_id, $original_user_id);
    }

    function the_author_meta($field = '', $user_id = false)
    {
        $author_meta = get_the_author_meta($field, $user_id);

        echo apply_filters("the_author_{$field}", $author_meta, $user_id);
    }

    function get_the_author_link()
    {
        if(get_the_author_meta('url'))
        {
            global $authordata;

            $author_url = get_the_author_meta('url');
            $author_display_name = get_the_author();

            $link = sprintf('<a href="%1$s" title="%2$s" rel="author external">%3$s</a>', esc_url($author_url), /* translators: %s: Author's display name. */ esc_attr(sprintf(__('Visit %s&#8217;s website'), $author_display_name)), $author_display_name);

            return apply_filters('the_author_link', $link, $author_url, $authordata);
        }
        else
        {
            return get_the_author();
        }
    }

    function the_author_link()
    {
        echo get_the_author_link();
    }

    function get_the_author_posts()
    {
        $post = get_post();
        if(! $post)
        {
            return 0;
        }

        return count_user_posts($post->post_author, $post->post_type);
    }

    function the_author_posts()
    {
        echo get_the_author_posts();
    }

    function get_the_author_posts_link()
    {
        global $authordata;

        if(! is_object($authordata))
        {
            return '';
        }

        $link = sprintf('<a href="%1$s" title="%2$s" rel="author">%3$s</a>', esc_url(get_author_posts_url($authordata->ID, $authordata->user_nicename)), /* translators: %s: Author's display name. */ esc_attr(sprintf(__('Posts by %s'), get_the_author())), get_the_author());

        return apply_filters('the_author_posts_link', $link);
    }

    function the_author_posts_link($deprecated = '')
    {
        if(! empty($deprecated))
        {
            _deprecated_argument(__FUNCTION__, '2.1.0');
        }
        echo get_the_author_posts_link();
    }

    function get_author_posts_url($author_id, $author_nicename = '')
    {
        global $wp_rewrite;

        $author_id = (int) $author_id;
        $link = $wp_rewrite->get_author_permastruct();

        if(empty($link))
        {
            $file = home_url('/');
            $link = $file.'?author='.$author_id;
        }
        else
        {
            if('' === $author_nicename)
            {
                $user = get_userdata($author_id);
                if(! empty($user->user_nicename))
                {
                    $author_nicename = $user->user_nicename;
                }
            }
            $link = str_replace('%author%', $author_nicename, $link);
            $link = home_url(user_trailingslashit($link));
        }

        $link = apply_filters('author_link', $link, $author_id, $author_nicename);

        return $link;
    }

    function wp_list_authors($args = '')
    {
        global $wpdb;

        $defaults = [
            'orderby' => 'name',
            'order' => 'ASC',
            'number' => '',
            'optioncount' => false,
            'exclude_admin' => true,
            'show_fullname' => false,
            'hide_empty' => true,
            'feed' => '',
            'feed_image' => '',
            'feed_type' => '',
            'echo' => true,
            'style' => 'list',
            'html' => true,
            'exclude' => '',
            'include' => '',
        ];

        $parsed_args = wp_parse_args($args, $defaults);

        $return = '';

        $query_args = wp_array_slice_assoc($parsed_args, ['orderby', 'order', 'number', 'exclude', 'include']);
        $query_args['fields'] = 'ids';

        $query_args = apply_filters('wp_list_authors_args', $query_args, $parsed_args);

        $authors = get_users($query_args);
        $post_counts = [];

        $post_counts = apply_filters('pre_wp_list_authors_post_counts_query', false, $parsed_args);

        if(! is_array($post_counts))
        {
            $post_counts = [];
            $post_counts_query = $wpdb->get_results(
                "SELECT DISTINCT post_author, COUNT(ID) AS count
			FROM $wpdb->posts
			WHERE ".get_private_posts_cap_sql('post').'
			GROUP BY post_author'
            );

            foreach((array) $post_counts_query as $row)
            {
                $post_counts[$row->post_author] = $row->count;
            }
        }

        foreach($authors as $author_id)
        {
            $posts = isset($post_counts[$author_id]) ? $post_counts[$author_id] : 0;

            if(! $posts && $parsed_args['hide_empty'])
            {
                continue;
            }

            $author = get_userdata($author_id);

            if($parsed_args['exclude_admin'] && 'admin' === $author->display_name)
            {
                continue;
            }

            if($parsed_args['show_fullname'] && $author->first_name && $author->last_name)
            {
                $name = sprintf(/* translators: 1: User's first name, 2: Last name. */ _x('%1$s %2$s', 'Display name based on first name and last name'), $author->first_name, $author->last_name);
            }
            else
            {
                $name = $author->display_name;
            }

            if(! $parsed_args['html'])
            {
                $return .= $name.', ';

                continue; // No need to go further to process HTML.
            }

            if('list' === $parsed_args['style'])
            {
                $return .= '<li>';
            }

            $link = sprintf('<a href="%1$s" title="%2$s">%3$s</a>', esc_url(get_author_posts_url($author->ID, $author->user_nicename)), /* translators: %s: Author's display name. */ esc_attr(sprintf(__('Posts by %s'), $author->display_name)), $name);

            if(! empty($parsed_args['feed_image']) || ! empty($parsed_args['feed']))
            {
                $link .= ' ';
                if(empty($parsed_args['feed_image']))
                {
                    $link .= '(';
                }

                $link .= '<a href="'.get_author_feed_link($author->ID, $parsed_args['feed_type']).'"';

                $alt = '';
                if(! empty($parsed_args['feed']))
                {
                    $alt = ' alt="'.esc_attr($parsed_args['feed']).'"';
                    $name = $parsed_args['feed'];
                }

                $link .= '>';

                if(! empty($parsed_args['feed_image']))
                {
                    $link .= '<img src="'.esc_url($parsed_args['feed_image']).'" style="border: none;"'.$alt.' />';
                }
                else
                {
                    $link .= $name;
                }

                $link .= '</a>';

                if(empty($parsed_args['feed_image']))
                {
                    $link .= ')';
                }
            }

            if($parsed_args['optioncount'])
            {
                $link .= ' ('.$posts.')';
            }

            $return .= $link;
            $return .= ('list' === $parsed_args['style']) ? '</li>' : ', ';
        }

        $return = rtrim($return, ', ');

        if($parsed_args['echo'])
        {
            echo $return;
        }
        else
        {
            return $return;
        }
    }

    function is_multi_author()
    {
        global $wpdb;

        $is_multi_author = get_transient('is_multi_author');
        if(false === $is_multi_author)
        {
            $rows = (array) $wpdb->get_col("SELECT DISTINCT post_author FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' LIMIT 2");
            $is_multi_author = 1 < count($rows) ? 1 : 0;
            set_transient('is_multi_author', $is_multi_author);
        }

        return apply_filters('is_multi_author', (bool) $is_multi_author);
    }

    function __clear_multi_author_cache()
    { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionDoubleUnderscore,PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore
        delete_transient('is_multi_author');
    }
