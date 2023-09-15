<?php

    function get_comment_author($comment_id = 0)
    {
        $comment = get_comment($comment_id);

        $comment_id = ! empty($comment->comment_ID) ? $comment->comment_ID : $comment_id;

        if(empty($comment->comment_author))
        {
            $user = ! empty($comment->user_id) ? get_userdata($comment->user_id) : false;
            if($user)
            {
                $comment_author = $user->display_name;
            }
            else
            {
                $comment_author = __('Anonymous');
            }
        }
        else
        {
            $comment_author = $comment->comment_author;
        }

        return apply_filters('get_comment_author', $comment_author, $comment_id, $comment);
    }

    function comment_author($comment_id = 0)
    {
        $comment = get_comment($comment_id);

        $comment_author = get_comment_author($comment);

        echo apply_filters('comment_author', $comment_author, $comment->comment_ID);
    }

    function get_comment_author_email($comment_id = 0)
    {
        $comment = get_comment($comment_id);

        return apply_filters('get_comment_author_email', $comment->comment_author_email, $comment->comment_ID, $comment);
    }

    function comment_author_email($comment_id = 0)
    {
        $comment = get_comment($comment_id);

        $comment_author_email = get_comment_author_email($comment);

        echo apply_filters('author_email', $comment_author_email, $comment->comment_ID);
    }

    function comment_author_email_link($link_text = '', $before = '', $after = '', $comment = null)
    {
        $link = get_comment_author_email_link($link_text, $before, $after, $comment);
        if($link)
        {
            echo $link;
        }
    }

    function get_comment_author_email_link($link_text = '', $before = '', $after = '', $comment = null)
    {
        $comment = get_comment($comment);

        $comment_author_email = apply_filters('comment_email', $comment->comment_author_email, $comment);

        if((! empty($comment_author_email)) && ('@' !== $comment_author_email))
        {
            $display = ('' !== $link_text) ? $link_text : $comment_author_email;

            $comment_author_email_link = $before.sprintf('<a href="%1$s">%2$s</a>', esc_url('mailto:'.$comment_author_email), esc_html($display)).$after;

            return $comment_author_email_link;
        }
        else
        {
            return '';
        }
    }

    function get_comment_author_link($comment_id = 0)
    {
        $comment = get_comment($comment_id);

        $comment_id = ! empty($comment->comment_ID) ? $comment->comment_ID : (string) $comment_id;

        $comment_author_url = get_comment_author_url($comment);
        $comment_author = get_comment_author($comment);

        if(empty($comment_author_url) || 'http://' === $comment_author_url)
        {
            $comment_author_link = $comment_author;
        }
        else
        {
            $rel_parts = ['ugc'];
            if(! wp_is_internal_link($comment_author_url))
            {
                $rel_parts = array_merge($rel_parts, ['external', 'nofollow']);
            }

            $rel_parts = apply_filters('comment_author_link_rel', $rel_parts, $comment);

            $rel = implode(' ', $rel_parts);
            $rel = esc_attr($rel);
            // Empty space before 'rel' is necessary for later sprintf().
            $rel = ! empty($rel) ? sprintf(' rel="%s"', $rel) : '';

            $comment_author_link = sprintf('<a href="%1$s" class="url"%2$s>%3$s</a>', $comment_author_url, $rel, $comment_author);
        }

        return apply_filters('get_comment_author_link', $comment_author_link, $comment_author, $comment_id);
    }

    function comment_author_link($comment_id = 0)
    {
        echo get_comment_author_link($comment_id);
    }

    function get_comment_author_IP($comment_id = 0)
    { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
        $comment = get_comment($comment_id);

        return apply_filters('get_comment_author_IP', $comment->comment_author_IP, $comment->comment_ID, $comment);  // phpcs:ignore WordPress.NamingConventions.ValidHookName.NotLowercase
    }

    function comment_author_IP($comment_id = 0)
    { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
        echo esc_html(get_comment_author_IP($comment_id));
    }

    function get_comment_author_url($comment_id = 0)
    {
        $comment = get_comment($comment_id);

        $comment_author_url = '';
        $comment_id = 0;

        if(! empty($comment))
        {
            $comment_author_url = ('http://' === $comment->comment_author_url) ? '' : $comment->comment_author_url;
            $comment_author_url = esc_url($comment_author_url, ['http', 'https']);

            $comment_id = $comment->comment_ID;
        }

        return apply_filters('get_comment_author_url', $comment_author_url, $comment_id, $comment);
    }

    function comment_author_url($comment_id = 0)
    {
        $comment = get_comment($comment_id);

        $comment_author_url = get_comment_author_url($comment);

        echo apply_filters('comment_url', $comment_author_url, $comment->comment_ID);
    }

    function get_comment_author_url_link($link_text = '', $before = '', $after = '', $comment = 0)
    {
        $comment_author_url = get_comment_author_url($comment);

        $display = ('' !== $link_text) ? $link_text : $comment_author_url;
        $display = str_replace(array('http://www.', 'http://'), '', $display);

        if(str_ends_with($display, '/'))
        {
            $display = substr($display, 0, -1);
        }

        $comment_author_url_link = $before.sprintf('<a href="%1$s" rel="external">%2$s</a>', $comment_author_url, $display).$after;

        return apply_filters('get_comment_author_url_link', $comment_author_url_link);
    }

    function comment_author_url_link($link_text = '', $before = '', $after = '', $comment = 0)
    {
        echo get_comment_author_url_link($link_text, $before, $after, $comment);
    }

    function comment_class($css_class = '', $comment = null, $post = null, $display = true)
    {
        // Separates classes with a single space, collates classes for comment DIV.
        $css_class = 'class="'.implode(' ', get_comment_class($css_class, $comment, $post)).'"';

        if($display)
        {
            echo $css_class;
        }
        else
        {
            return $css_class;
        }
    }

    function get_comment_class($css_class = '', $comment_id = null, $post = null)
    {
        global $comment_alt, $comment_depth, $comment_thread_alt;

        $classes = [];

        $comment = get_comment($comment_id);
        if(! $comment)
        {
            return $classes;
        }

        // Get the comment type (comment, trackback).
        $classes[] = (empty($comment->comment_type)) ? 'comment' : $comment->comment_type;

        // Add classes for comment authors that are registered users.
        $user = $comment->user_id ? get_userdata($comment->user_id) : false;
        if($user)
        {
            $classes[] = 'byuser';
            $classes[] = 'comment-author-'.sanitize_html_class($user->user_nicename, $comment->user_id);
            // For comment authors who are the author of the post.
            $_post = get_post($post);
            if($_post && $comment->user_id === $_post->post_author)
            {
                $classes[] = 'bypostauthor';
            }
        }

        if(empty($comment_alt))
        {
            $comment_alt = 0;
        }
        if(empty($comment_depth))
        {
            $comment_depth = 1;
        }
        if(empty($comment_thread_alt))
        {
            $comment_thread_alt = 0;
        }

        if($comment_alt % 2)
        {
            $classes[] = 'odd';
            $classes[] = 'alt';
        }
        else
        {
            $classes[] = 'even';
        }

        ++$comment_alt;

        // Alt for top-level comments.
        if(1 == $comment_depth)
        {
            if($comment_thread_alt % 2)
            {
                $classes[] = 'thread-odd';
                $classes[] = 'thread-alt';
            }
            else
            {
                $classes[] = 'thread-even';
            }
            ++$comment_thread_alt;
        }

        $classes[] = "depth-$comment_depth";

        if(! empty($css_class))
        {
            if(! is_array($css_class))
            {
                $css_class = preg_split('#\s+#', $css_class);
            }
            $classes = array_merge($classes, $css_class);
        }

        $classes = array_map('esc_attr', $classes);

        return apply_filters('comment_class', $classes, $css_class, $comment->comment_ID, $comment, $post);
    }

    function get_comment_date($format = '', $comment_id = 0)
    {
        $comment = get_comment($comment_id);

        $_format = ! empty($format) ? $format : get_option('date_format');

        $comment_date = mysql2date($_format, $comment->comment_date);

        return apply_filters('get_comment_date', $comment_date, $format, $comment);
    }

    function comment_date($format = '', $comment_id = 0)
    {
        echo get_comment_date($format, $comment_id);
    }

    function get_comment_excerpt($comment_id = 0)
    {
        $comment = get_comment($comment_id);

        if(post_password_required($comment->comment_post_ID))
        {
            $comment_text = __('Password protected');
        }
        else
        {
            $comment_text = strip_tags(str_replace(["\n", "\r"], ' ', $comment->comment_content));
        }

        /* translators: Maximum number of words used in a comment excerpt. */
        $comment_excerpt_length = (int) _x('20', 'comment_excerpt_length');

        $comment_excerpt_length = apply_filters('comment_excerpt_length', $comment_excerpt_length);

        $comment_excerpt = wp_trim_words($comment_text, $comment_excerpt_length, '&hellip;');

        return apply_filters('get_comment_excerpt', $comment_excerpt, $comment->comment_ID, $comment);
    }

    function comment_excerpt($comment_id = 0)
    {
        $comment = get_comment($comment_id);

        $comment_excerpt = get_comment_excerpt($comment);

        echo apply_filters('comment_excerpt', $comment_excerpt, $comment->comment_ID);
    }

    function get_comment_ID()
    { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
        $comment = get_comment();

        $comment_id = ! empty($comment->comment_ID) ? $comment->comment_ID : '0';

        return apply_filters('get_comment_ID', $comment_id, $comment);  // phpcs:ignore WordPress.NamingConventions.ValidHookName.NotLowercase
    }

    function comment_ID()
    { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
        echo get_comment_ID();
    }

    function get_comment_link($comment = null, $args = [])
    {
        global $wp_rewrite, $in_comment_loop;

        $comment = get_comment($comment);

        // Back-compat.
        if(! is_array($args))
        {
            $args = ['page' => $args];
        }

        $defaults = [
            'type' => 'all',
            'page' => '',
            'per_page' => '',
            'max_depth' => '',
            'cpage' => null,
        ];

        $args = wp_parse_args($args, $defaults);

        $comment_link = get_permalink($comment->comment_post_ID);

        // The 'cpage' param takes precedence.
        if(is_null($args['cpage']))
        {
            if('' === $args['per_page'] && get_option('page_comments'))
            {
                $args['per_page'] = get_option('comments_per_page');
            }

            if(empty($args['per_page']))
            {
                $args['per_page'] = 0;
                $args['page'] = 0;
            }

            $cpage = $args['page'];

            if('' == $cpage)
            {
                if(! empty($in_comment_loop))
                {
                    $cpage = get_query_var('cpage');
                }
                else
                {
                    // Requires a database hit, so we only do it when we can't figure out from context.
                    $cpage = get_page_of_comment($comment->comment_ID, $args);
                }
            }

            /*
		 * If the default page displays the oldest comments, the permalinks for comments on the default page
		 * do not need a 'cpage' query var.
		 */
            if('oldest' === get_option('default_comments_page') && 1 === $cpage)
            {
                $cpage = '';
            }
        }
        else
        {
            $cpage = $args['cpage'];
            // No 'cpage' is provided, so we calculate one.
        }

        if($cpage && get_option('page_comments'))
        {
            if($wp_rewrite->using_permalinks())
            {
                if($cpage)
                {
                    $comment_link = trailingslashit($comment_link).$wp_rewrite->comments_pagination_base.'-'.$cpage;
                }

                $comment_link = user_trailingslashit($comment_link, 'comment');
            }
            elseif($cpage)
            {
                $comment_link = add_query_arg('cpage', $cpage, $comment_link);
            }
        }

        if($wp_rewrite->using_permalinks())
        {
            $comment_link = user_trailingslashit($comment_link, 'comment');
        }

        $comment_link = $comment_link.'#comment-'.$comment->comment_ID;

        return apply_filters('get_comment_link', $comment_link, $comment, $args, $cpage);
    }

    function get_comments_link($post = 0)
    {
        $hash = get_comments_number($post) ? '#comments' : '#respond';
        $comments_link = get_permalink($post).$hash;

        return apply_filters('get_comments_link', $comments_link, $post);
    }

    function comments_link($deprecated = '', $deprecated_2 = '')
    {
        if(! empty($deprecated))
        {
            _deprecated_argument(__FUNCTION__, '0.72');
        }
        if(! empty($deprecated_2))
        {
            _deprecated_argument(__FUNCTION__, '1.3.0');
        }
        echo esc_url(get_comments_link());
    }

    function get_comments_number($post = 0)
    {
        $post = get_post($post);

        $comments_number = $post ? $post->comment_count : 0;
        $post_id = $post ? $post->ID : 0;

        return apply_filters('get_comments_number', $comments_number, $post_id);
    }

    function comments_number($zero = false, $one = false, $more = false, $post = 0)
    {
        echo get_comments_number_text($zero, $one, $more, $post);
    }

    function get_comments_number_text($zero = false, $one = false, $more = false, $post = 0)
    {
        $comments_number = get_comments_number($post);

        if($comments_number > 1)
        {
            if(false !== $more)
            {
                // % Comments
                /*
			 * translators: If comment number in your language requires declension,
			 * translate this to 'on'. Do not translate into your own language.
			 */
                if('on' === _x('off', 'Comment number declension: on or off'))
                {
                    $text = preg_replace('#<span class="screen-reader-text">.+?</span>#', '', $more);
                    $text = preg_replace('/&.+?;/', '', $text); // Remove HTML entities.
                    $text = trim(strip_tags($text), '% ');

                    // Replace '% Comments' with a proper plural form.
                    if($text && ! preg_match('/[0-9]+/', $text) && str_contains($more, '%'))
                    {
                        /* translators: %s: Number of comments. */
                        $new_text = _n('%s Comment', '%s Comments', $comments_number);
                        $new_text = trim(sprintf($new_text, ''));

                        $more = str_replace($text, $new_text, $more);
                        if(! str_contains($more, '%'))
                        {
                            $more = '% '.$more;
                        }
                    }
                }

                $comments_number_text = str_replace('%', number_format_i18n($comments_number), $more);
            }
            else
            {
                $comments_number_text = sprintf(/* translators: %s: Number of comments. */ _n('%s Comment', '%s Comments', $comments_number), number_format_i18n($comments_number));
            }
        }
        elseif(0 == $comments_number)
        {
            $comments_number_text = (false === $zero) ? __('No Comments') : $zero;
        }
        else
        { // Must be one.
            $comments_number_text = (false === $one) ? __('1 Comment') : $one;
        }

        return apply_filters('comments_number', $comments_number_text, $comments_number);
    }

    function get_comment_text($comment_id = 0, $args = [])
    {
        $comment = get_comment($comment_id);

        $comment_text = $comment->comment_content;

        if(is_comment_feed() && $comment->comment_parent)
        {
            $parent = get_comment($comment->comment_parent);
            if($parent)
            {
                $parent_link = esc_url(get_comment_link($parent));
                $name = get_comment_author($parent);

                $comment_text = sprintf(/* translators: %s: Comment link. */ ent2ncr(__('In reply to %s.')), '<a href="'.$parent_link.'">'.$name.'</a>')."\n\n".$comment_text;
            }
        }

        return apply_filters('get_comment_text', $comment_text, $comment, $args);
    }

    function comment_text($comment_id = 0, $args = [])
    {
        $comment = get_comment($comment_id);

        $comment_text = get_comment_text($comment, $args);

        echo apply_filters('comment_text', $comment_text, $comment, $args);
    }

    function get_comment_time($format = '', $gmt = false, $translate = true, $comment_id = 0)
    {
        $comment = get_comment($comment_id);

        if(null === $comment)
        {
            return '';
        }

        $comment_date = $gmt ? $comment->comment_date_gmt : $comment->comment_date;

        $_format = ! empty($format) ? $format : get_option('time_format');

        $comment_time = mysql2date($_format, $comment_date, $translate);

        return apply_filters('get_comment_time', $comment_time, $format, $gmt, $translate, $comment);
    }

    function comment_time($format = '', $comment_id = 0)
    {
        echo get_comment_time($format, false, true, $comment_id);
    }

    function get_comment_type($comment_id = 0)
    {
        $comment = get_comment($comment_id);

        if('' === $comment->comment_type)
        {
            $comment->comment_type = 'comment';
        }

        return apply_filters('get_comment_type', $comment->comment_type, $comment->comment_ID, $comment);
    }

    function comment_type($commenttxt = false, $trackbacktxt = false, $pingbacktxt = false)
    {
        if(false === $commenttxt)
        {
            $commenttxt = _x('Comment', 'noun');
        }
        if(false === $trackbacktxt)
        {
            $trackbacktxt = __('Trackback');
        }
        if(false === $pingbacktxt)
        {
            $pingbacktxt = __('Pingback');
        }
        $type = get_comment_type();
        switch($type)
        {
            case 'trackback':
                echo $trackbacktxt;
                break;
            case 'pingback':
                echo $pingbacktxt;
                break;
            default:
                echo $commenttxt;
        }
    }

    function get_trackback_url()
    {
        if(get_option('permalink_structure'))
        {
            $trackback_url = trailingslashit(get_permalink()).user_trailingslashit('trackback', 'single_trackback');
        }
        else
        {
            $trackback_url = get_option('siteurl').'/wp-trackback.php?p='.get_the_ID();
        }

        return apply_filters('trackback_url', $trackback_url);
    }

    function trackback_url($deprecated_echo = true)
    {
        if(true !== $deprecated_echo)
        {
            _deprecated_argument(__FUNCTION__, '2.5.0', sprintf(/* translators: %s: get_trackback_url() */ __('Use %s instead if you do not want the value echoed.'), '<code>get_trackback_url()</code>'));
        }

        if($deprecated_echo)
        {
            echo get_trackback_url();
        }
        else
        {
            return get_trackback_url();
        }
    }

    function trackback_rdf($deprecated = '')
    {
        if(! empty($deprecated))
        {
            _deprecated_argument(__FUNCTION__, '2.5.0');
        }

        if(isset($_SERVER['HTTP_USER_AGENT']) && false !== stripos($_SERVER['HTTP_USER_AGENT'], 'W3C_Validator'))
        {
            return;
        }

        echo '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
			xmlns:dc="http://purl.org/dc/elements/1.1/"
			xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">
		<rdf:Description rdf:about="';
        the_permalink();
        echo '"'."\n";
        echo '    dc:identifier="';
        the_permalink();
        echo '"'."\n";
        echo '    dc:title="'.str_replace('--', '&#x2d;&#x2d;', wptexturize(strip_tags(get_the_title()))).'"'."\n";
        echo '    trackback:ping="'.get_trackback_url().'"'." />\n";
        echo '</rdf:RDF>';
    }

    function comments_open($post = null)
    {
        $_post = get_post($post);

        $post_id = $_post ? $_post->ID : 0;
        $comments_open = ($_post && ('open' === $_post->comment_status));

        return apply_filters('comments_open', $comments_open, $post_id);
    }

    function pings_open($post = null)
    {
        $_post = get_post($post);

        $post_id = $_post ? $_post->ID : 0;
        $pings_open = ($_post && ('open' === $_post->ping_status));

        return apply_filters('pings_open', $pings_open, $post_id);
    }

    function wp_comment_form_unfiltered_html_nonce()
    {
        $post = get_post();
        $post_id = $post ? $post->ID : 0;

        if(current_user_can('unfiltered_html'))
        {
            wp_nonce_field('unfiltered-html-comment_'.$post_id, '_wp_unfiltered_html_comment_disabled', false);
            echo "<script>(function(){if(window===window.parent){document.getElementById('_wp_unfiltered_html_comment_disabled').name='_wp_unfiltered_html_comment';}})();</script>\n";
        }
    }

    function comments_template($file = '/comments.php', $separate_comments = false)
    {
        global $wp_query, $withcomments, $post, $wpdb, $id, $comment, $user_login, $user_identity, $overridden_cpage;

        if(! (is_single() || is_page() || $withcomments) || empty($post))
        {
            return;
        }

        if(empty($file))
        {
            $file = '/comments.php';
        }

        $req = get_option('require_name_email');

        /*
	 * Comment author information fetched from the comment cookies.
	 */
        $commenter = wp_get_current_commenter();

        /*
	 * The name of the current comment author escaped for use in attributes.
	 * Escaped by sanitize_comment_cookies().
	 */
        $comment_author = $commenter['comment_author'];

        /*
	 * The email address of the current comment author escaped for use in attributes.
	 * Escaped by sanitize_comment_cookies().
	 */
        $comment_author_email = $commenter['comment_author_email'];

        /*
	 * The URL of the current comment author escaped for use in attributes.
	 */
        $comment_author_url = esc_url($commenter['comment_author_url']);

        $comment_args = [
            'orderby' => 'comment_date_gmt',
            'order' => 'ASC',
            'status' => 'approve',
            'post_id' => $post->ID,
            'no_found_rows' => false,
        ];

        if(get_option('thread_comments'))
        {
            $comment_args['hierarchical'] = 'threaded';
        }
        else
        {
            $comment_args['hierarchical'] = false;
        }

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

        $per_page = 0;
        if(get_option('page_comments'))
        {
            $per_page = (int) get_query_var('comments_per_page');
            if(0 === $per_page)
            {
                $per_page = (int) get_option('comments_per_page');
            }

            $comment_args['number'] = $per_page;
            $page = (int) get_query_var('cpage');

            if($page)
            {
                $comment_args['offset'] = ($page - 1) * $per_page;
            }
            elseif('oldest' === get_option('default_comments_page'))
            {
                $comment_args['offset'] = 0;
            }
            else
            {
                // If fetching the first page of 'newest', we need a top-level comment count.
                $top_level_query = new WP_Comment_Query();
                $top_level_args = [
                    'count' => true,
                    'orderby' => false,
                    'post_id' => $post->ID,
                    'status' => 'approve',
                ];

                if($comment_args['hierarchical'])
                {
                    $top_level_args['parent'] = 0;
                }

                if(isset($comment_args['include_unapproved']))
                {
                    $top_level_args['include_unapproved'] = $comment_args['include_unapproved'];
                }

                $top_level_args = apply_filters('comments_template_top_level_query_args', $top_level_args);

                $top_level_count = $top_level_query->query($top_level_args);

                $comment_args['offset'] = (ceil($top_level_count / $per_page) - 1) * $per_page;
            }
        }

        $comment_args = apply_filters('comments_template_query_args', $comment_args);

        $comment_query = new WP_Comment_Query($comment_args);
        $_comments = $comment_query->comments;

        // Trees must be flattened before they're passed to the walker.
        if($comment_args['hierarchical'])
        {
            $comments_flat = [];
            foreach($_comments as $_comment)
            {
                $comments_flat[] = $_comment;
                $comment_children = $_comment->get_children([
                                                                'format' => 'flat',
                                                                'status' => $comment_args['status'],
                                                                'orderby' => $comment_args['orderby'],
                                                            ]);

                foreach($comment_children as $comment_child)
                {
                    $comments_flat[] = $comment_child;
                }
            }
        }
        else
        {
            $comments_flat = $_comments;
        }

        $wp_query->comments = apply_filters('comments_array', $comments_flat, $post->ID);

        $comments = &$wp_query->comments;
        $wp_query->comment_count = count($wp_query->comments);
        $wp_query->max_num_comment_pages = $comment_query->max_num_pages;

        if($separate_comments)
        {
            $wp_query->comments_by_type = separate_comments($comments);
            $comments_by_type = &$wp_query->comments_by_type;
        }
        else
        {
            $wp_query->comments_by_type = [];
        }

        $overridden_cpage = false;

        if('' == get_query_var('cpage') && $wp_query->max_num_comment_pages > 1)
        {
            set_query_var('cpage', 'newest' === get_option('default_comments_page') ? get_comment_pages_count() : 1);
            $overridden_cpage = true;
        }

        if(! defined('COMMENTS_TEMPLATE'))
        {
            define('COMMENTS_TEMPLATE', true);
        }

        $theme_template = STYLESHEETPATH.$file;

        $include = apply_filters('comments_template', $theme_template);

        if(file_exists($include))
        {
            require $include;
        }
        elseif(file_exists(TEMPLATEPATH.$file))
        {
            require TEMPLATEPATH.$file;
        }
        else
        { // Backward compat code will be removed in a future release.
            require ABSPATH.WPINC.'/theme-compat/comments.php';
        }
    }

    function comments_popup_link($zero = false, $one = false, $more = false, $css_class = '', $none = false)
    {
        $post_id = get_the_ID();
        $post_title = get_the_title();
        $comments_number = get_comments_number($post_id);

        if(false === $zero)
        {
            /* translators: %s: Post title. */
            $zero = sprintf(__('No Comments<span class="screen-reader-text"> on %s</span>'), $post_title);
        }

        if(false === $one)
        {
            /* translators: %s: Post title. */
            $one = sprintf(__('1 Comment<span class="screen-reader-text"> on %s</span>'), $post_title);
        }

        if(false === $more)
        {
            /* translators: 1: Number of comments, 2: Post title. */
            $more = _n('%1$s Comment<span class="screen-reader-text"> on %2$s</span>', '%1$s Comments<span class="screen-reader-text"> on %2$s</span>', $comments_number);
            $more = sprintf($more, number_format_i18n($comments_number), $post_title);
        }

        if(false === $none)
        {
            /* translators: %s: Post title. */
            $none = sprintf(__('Comments Off<span class="screen-reader-text"> on %s</span>'), $post_title);
        }

        if(0 == $comments_number && ! comments_open() && ! pings_open())
        {
            printf('<span%1$s>%2$s</span>', ! empty($css_class) ? ' class="'.esc_attr($css_class).'"' : '', $none);

            return;
        }

        if(post_password_required())
        {
            _e('Enter your password to view comments.');

            return;
        }

        if(0 == $comments_number)
        {
            $respond_link = get_permalink().'#respond';

            $comments_link = apply_filters('respond_link', $respond_link, $post_id);
        }
        else
        {
            $comments_link = get_comments_link();
        }

        $link_attributes = '';

        $link_attributes = apply_filters('comments_popup_link_attributes', $link_attributes);

        printf('<a href="%1$s"%2$s%3$s>%4$s</a>', esc_url($comments_link), ! empty($css_class) ? ' class="'.$css_class.'" ' : '', $link_attributes, get_comments_number_text($zero, $one, $more));
    }

    function get_comment_reply_link($args = [], $comment = null, $post = null)
    {
        $defaults = [
            'add_below' => 'comment',
            'respond_id' => 'respond',
            'reply_text' => __('Reply'),
            /* translators: Comment reply button text. %s: Comment author name. */
            'reply_to_text' => __('Reply to %s'),
            'login_text' => __('Log in to Reply'),
            'max_depth' => 0,
            'depth' => 0,
            'before' => '',
            'after' => '',
        ];

        $args = wp_parse_args($args, $defaults);

        if(0 == $args['depth'] || $args['max_depth'] <= $args['depth'])
        {
            return;
        }

        $comment = get_comment($comment);

        if(empty($comment))
        {
            return;
        }

        if(empty($post))
        {
            $post = $comment->comment_post_ID;
        }

        $post = get_post($post);

        if(! comments_open($post->ID))
        {
            return false;
        }

        if(get_option('page_comments'))
        {
            $permalink = str_replace('#comment-'.$comment->comment_ID, '', get_comment_link($comment));
        }
        else
        {
            $permalink = get_permalink($post->ID);
        }

        $args = apply_filters('comment_reply_link_args', $args, $comment, $post);

        if(get_option('comment_registration') && ! is_user_logged_in())
        {
            $link = sprintf('<a rel="nofollow" class="comment-reply-login" href="%s">%s</a>', esc_url(wp_login_url(get_permalink())), $args['login_text']);
        }
        else
        {
            $data_attributes = [
                'commentid' => $comment->comment_ID,
                'postid' => $post->ID,
                'belowelement' => $args['add_below'].'-'.$comment->comment_ID,
                'respondelement' => $args['respond_id'],
                'replyto' => sprintf($args['reply_to_text'], get_comment_author($comment)),
            ];

            $data_attribute_string = '';

            foreach($data_attributes as $name => $value)
            {
                $data_attribute_string .= " data-{$name}=\"".esc_attr($value).'"';
            }

            $data_attribute_string = trim($data_attribute_string);

            $link = sprintf(
                "<a rel='nofollow' class='comment-reply-link' href='%s' %s aria-label='%s'>%s</a>", esc_url(
                                                                                                      add_query_arg([
                                                                                                                        'replytocom' => $comment->comment_ID,
                                                                                                                        'unapproved' => false,
                                                                                                                        'moderation-hash' => false,
                                                                                                                    ], $permalink)
                                                                                                  ).'#'.$args['respond_id'], $data_attribute_string, esc_attr(sprintf($args['reply_to_text'], get_comment_author($comment))), $args['reply_text']
            );
        }

        $comment_reply_link = $args['before'].$link.$args['after'];

        return apply_filters('comment_reply_link', $comment_reply_link, $args, $comment, $post);
    }

    function comment_reply_link($args = [], $comment = null, $post = null)
    {
        echo get_comment_reply_link($args, $comment, $post);
    }

    function get_post_reply_link($args = [], $post = null)
    {
        $defaults = [
            'add_below' => 'post',
            'respond_id' => 'respond',
            'reply_text' => __('Leave a Comment'),
            'login_text' => __('Log in to leave a Comment'),
            'before' => '',
            'after' => '',
        ];

        $args = wp_parse_args($args, $defaults);

        $post = get_post($post);

        if(! comments_open($post->ID))
        {
            return false;
        }

        if(get_option('comment_registration') && ! is_user_logged_in())
        {
            $link = sprintf('<a rel="nofollow" class="comment-reply-login" href="%s">%s</a>', wp_login_url(get_permalink()), $args['login_text']);
        }
        else
        {
            $onclick = sprintf('return addComment.moveForm( "%1$s-%2$s", "0", "%3$s", "%2$s" )', $args['add_below'], $post->ID, $args['respond_id']);

            $link = sprintf("<a rel='nofollow' class='comment-reply-link' href='%s' onclick='%s'>%s</a>", get_permalink($post->ID).'#'.$args['respond_id'], $onclick, $args['reply_text']);
        }

        $post_reply_link = $args['before'].$link.$args['after'];

        return apply_filters('post_comments_link', $post_reply_link, $post);
    }

    function post_reply_link($args = [], $post = null)
    {
        echo get_post_reply_link($args, $post);
    }

    function get_cancel_comment_reply_link($link_text = '', $post = null)
    {
        if(empty($link_text))
        {
            $link_text = __('Click here to cancel reply.');
        }

        $post = get_post($post);
        $reply_to_id = $post ? _get_comment_reply_id($post->ID) : 0;
        $link_style = 0 !== $reply_to_id ? '' : ' style="display:none;"';
        $link_url = esc_url(remove_query_arg(['replytocom', 'unapproved', 'moderation-hash'])).'#respond';

        $cancel_comment_reply_link = sprintf('<a rel="nofollow" id="cancel-comment-reply-link" href="%1$s"%2$s>%3$s</a>', $link_url, $link_style, $link_text);

        return apply_filters('cancel_comment_reply_link', $cancel_comment_reply_link, $link_url, $link_text);
    }

    function cancel_comment_reply_link($link_text = '')
    {
        echo get_cancel_comment_reply_link($link_text);
    }

    function get_comment_id_fields($post = null)
    {
        $post = get_post($post);
        if(! $post)
        {
            return '';
        }

        $post_id = $post->ID;
        $reply_to_id = _get_comment_reply_id($post_id);

        $comment_id_fields = "<input type='hidden' name='comment_post_ID' value='$post_id' id='comment_post_ID' />\n";
        $comment_id_fields .= "<input type='hidden' name='comment_parent' id='comment_parent' value='$reply_to_id' />\n";

        return apply_filters('comment_id_fields', $comment_id_fields, $post_id, $reply_to_id);
    }

    function comment_id_fields($post = null)
    {
        echo get_comment_id_fields($post);
    }

    function comment_form_title($no_reply_text = false, $reply_text = false, $link_to_parent = true, $post = null)
    {
        global $comment;

        if(false === $no_reply_text)
        {
            $no_reply_text = __('Leave a Reply');
        }

        if(false === $reply_text)
        {
            /* translators: %s: Author of the comment being replied to. */
            $reply_text = __('Leave a Reply to %s');
        }

        $post = get_post($post);
        if(! $post)
        {
            echo $no_reply_text;

            return;
        }

        $reply_to_id = _get_comment_reply_id($post->ID);

        if(0 === $reply_to_id)
        {
            echo $no_reply_text;

            return;
        }

        // Sets the global so that template tags can be used in the comment form.
        $comment = get_comment($reply_to_id);

        if($link_to_parent)
        {
            $comment_author = sprintf('<a href="#comment-%1$s">%2$s</a>', get_comment_ID(), get_comment_author($reply_to_id));
        }
        else
        {
            $comment_author = get_comment_author($reply_to_id);
        }

        printf($reply_text, $comment_author);
    }

    function _get_comment_reply_id($post = null)
    {
        $post = get_post($post);

        if(! $post || ! isset($_GET['replytocom']) || ! is_numeric($_GET['replytocom']))
        {
            return 0;
        }

        $reply_to_id = (int) $_GET['replytocom'];

        /*
	 * Validate the comment.
	 * Bail out if it does not exist, is not approved, or its
	 * `comment_post_ID` does not match the given post ID.
	 */
        $comment = get_comment($reply_to_id);

        if(! $comment instanceof WP_Comment || 0 === (int) $comment->comment_approved || $post->ID !== (int) $comment->comment_post_ID)
        {
            return 0;
        }

        return $reply_to_id;
    }

    function wp_list_comments($args = [], $comments = null)
    {
        global $wp_query, $comment_alt, $comment_depth, $comment_thread_alt, $overridden_cpage, $in_comment_loop;

        $in_comment_loop = true;

        $comment_alt = 0;
        $comment_thread_alt = 0;
        $comment_depth = 1;

        $defaults = [
            'walker' => null,
            'max_depth' => '',
            'style' => 'ul',
            'callback' => null,
            'end-callback' => null,
            'type' => 'all',
            'page' => '',
            'per_page' => '',
            'avatar_size' => 32,
            'reverse_top_level' => null,
            'reverse_children' => '',
            'format' => current_theme_supports('html5', 'comment-list') ? 'html5' : 'xhtml',
            'short_ping' => false,
            'echo' => true,
        ];

        $parsed_args = wp_parse_args($args, $defaults);

        $parsed_args = apply_filters('wp_list_comments_args', $parsed_args);

        // Figure out what comments we'll be looping through ($_comments).
        if(null !== $comments)
        {
            $comments = (array) $comments;
            if(empty($comments))
            {
                return;
            }
            if('all' !== $parsed_args['type'])
            {
                $comments_by_type = separate_comments($comments);
                if(empty($comments_by_type[$parsed_args['type']]))
                {
                    return;
                }
                $_comments = $comments_by_type[$parsed_args['type']];
            }
            else
            {
                $_comments = $comments;
            }
        }
        else
        {
            /*
		 * If 'page' or 'per_page' has been passed, and does not match what's in $wp_query,
		 * perform a separate comment query and allow Walker_Comment to paginate.
		 */
            if($parsed_args['page'] || $parsed_args['per_page'])
            {
                $current_cpage = get_query_var('cpage');
                if(! $current_cpage)
                {
                    $current_cpage = 'newest' === get_option('default_comments_page') ? 1 : $wp_query->max_num_comment_pages;
                }

                $current_per_page = get_query_var('comments_per_page');
                if($parsed_args['page'] != $current_cpage || $parsed_args['per_page'] != $current_per_page)
                {
                    $comment_args = [
                        'post_id' => get_the_ID(),
                        'orderby' => 'comment_date_gmt',
                        'order' => 'ASC',
                        'status' => 'approve',
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

                    $comments = get_comments($comment_args);

                    if('all' !== $parsed_args['type'])
                    {
                        $comments_by_type = separate_comments($comments);
                        if(empty($comments_by_type[$parsed_args['type']]))
                        {
                            return;
                        }

                        $_comments = $comments_by_type[$parsed_args['type']];
                    }
                    else
                    {
                        $_comments = $comments;
                    }
                }
                // Otherwise, fall back on the comments from `$wp_query->comments`.
            }
            else
            {
                if(empty($wp_query->comments))
                {
                    return;
                }
                if('all' !== $parsed_args['type'])
                {
                    if(empty($wp_query->comments_by_type))
                    {
                        $wp_query->comments_by_type = separate_comments($wp_query->comments);
                    }
                    if(empty($wp_query->comments_by_type[$parsed_args['type']]))
                    {
                        return;
                    }
                    $_comments = $wp_query->comments_by_type[$parsed_args['type']];
                }
                else
                {
                    $_comments = $wp_query->comments;
                }

                if($wp_query->max_num_comment_pages)
                {
                    $default_comments_page = get_option('default_comments_page');
                    $cpage = get_query_var('cpage');
                    if('newest' === $default_comments_page)
                    {
                        $parsed_args['cpage'] = $cpage;
                        /*
					* When first page shows oldest comments, post permalink is the same as
					* the comment permalink.
					*/
                    }
                    elseif(1 == $cpage)
                    {
                        $parsed_args['cpage'] = '';
                    }
                    else
                    {
                        $parsed_args['cpage'] = $cpage;
                    }

                    $parsed_args['page'] = 0;
                    $parsed_args['per_page'] = 0;
                }
            }
        }

        if('' === $parsed_args['per_page'] && get_option('page_comments'))
        {
            $parsed_args['per_page'] = get_query_var('comments_per_page');
        }

        if(empty($parsed_args['per_page']))
        {
            $parsed_args['per_page'] = 0;
            $parsed_args['page'] = 0;
        }

        if('' === $parsed_args['max_depth'])
        {
            if(get_option('thread_comments'))
            {
                $parsed_args['max_depth'] = get_option('thread_comments_depth');
            }
            else
            {
                $parsed_args['max_depth'] = -1;
            }
        }

        if('' === $parsed_args['page'])
        {
            if(empty($overridden_cpage))
            {
                $parsed_args['page'] = get_query_var('cpage');
            }
            else
            {
                $threaded = (-1 != $parsed_args['max_depth']);
                $parsed_args['page'] = ('newest' === get_option('default_comments_page')) ? get_comment_pages_count($_comments, $parsed_args['per_page'], $threaded) : 1;
                set_query_var('cpage', $parsed_args['page']);
            }
        }
        // Validation check.
        $parsed_args['page'] = (int) $parsed_args['page'];
        if(0 == $parsed_args['page'] && 0 != $parsed_args['per_page'])
        {
            $parsed_args['page'] = 1;
        }

        if(null === $parsed_args['reverse_top_level'])
        {
            $parsed_args['reverse_top_level'] = ('desc' === get_option('comment_order'));
        }

        if(empty($parsed_args['walker']))
        {
            $walker = new Walker_Comment();
        }
        else
        {
            $walker = $parsed_args['walker'];
        }

        $output = $walker->paged_walk($_comments, $parsed_args['max_depth'], $parsed_args['page'], $parsed_args['per_page'], $parsed_args);

        $in_comment_loop = false;

        if($parsed_args['echo'])
        {
            echo $output;
        }
        else
        {
            return $output;
        }
    }

    function comment_form($args = [], $post = null)
    {
        $post = get_post($post);

        // Exit the function if the post is invalid or comments are closed.
        if(! $post || ! comments_open($post))
        {
            do_action('comment_form_comments_closed');

            return;
        }

        $post_id = $post->ID;
        $commenter = wp_get_current_commenter();
        $user = wp_get_current_user();
        $user_identity = $user->exists() ? $user->display_name : '';

        $args = wp_parse_args($args);
        if(! isset($args['format']))
        {
            $args['format'] = current_theme_supports('html5', 'comment-form') ? 'html5' : 'xhtml';
        }

        $req = get_option('require_name_email');
        $html5 = 'html5' === $args['format'];

        // Define attributes in HTML5 or XHTML syntax.
        $required_attribute = ($html5 ? ' required' : ' required="required"');
        $checked_attribute = ($html5 ? ' checked' : ' checked="checked"');

        // Identify required fields visually and create a message about the indicator.
        $required_indicator = ' '.wp_required_field_indicator();
        $required_text = ' '.wp_required_field_message();

        $fields = [
            'author' => sprintf('<p class="comment-form-author">%s %s</p>', sprintf('<label for="author">%s%s</label>', __('Name'), ($req ? $required_indicator : '')), sprintf('<input id="author" name="author" type="text" value="%s" size="30" maxlength="245" autocomplete="name"%s />', esc_attr($commenter['comment_author']), ($req ? $required_attribute : ''))),
            'email' => sprintf('<p class="comment-form-email">%s %s</p>', sprintf('<label for="email">%s%s</label>', __('Email'), ($req ? $required_indicator : '')), sprintf('<input id="email" name="email" %s value="%s" size="30" maxlength="100" aria-describedby="email-notes" autocomplete="email"%s />', ($html5 ? 'type="email"' : 'type="text"'), esc_attr($commenter['comment_author_email']), ($req ? $required_attribute : ''))),
            'url' => sprintf('<p class="comment-form-url">%s %s</p>', sprintf('<label for="url">%s</label>', __('Website')), sprintf('<input id="url" name="url" %s value="%s" size="30" maxlength="200" autocomplete="url" />', ($html5 ? 'type="url"' : 'type="text"'), esc_attr($commenter['comment_author_url']))),
        ];

        if(has_action('set_comment_cookies', 'wp_set_comment_cookies') && get_option('show_comments_cookies_opt_in'))
        {
            $consent = empty($commenter['comment_author_email']) ? '' : $checked_attribute;

            $fields['cookies'] = sprintf('<p class="comment-form-cookies-consent">%s %s</p>', sprintf('<input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes"%s />', $consent), sprintf('<label for="wp-comment-cookies-consent">%s</label>', __('Save my name, email, and website in this browser for the next time I comment.')));

            // Ensure that the passed fields include cookies consent.
            if(isset($args['fields']) && ! isset($args['fields']['cookies']))
            {
                $args['fields']['cookies'] = $fields['cookies'];
            }
        }

        $fields = apply_filters('comment_form_default_fields', $fields);

        $defaults = [
            'fields' => $fields,
            'comment_field' => sprintf('<p class="comment-form-comment">%s %s</p>', sprintf('<label for="comment">%s%s</label>', _x('Comment', 'noun'), $required_indicator), '<textarea id="comment" name="comment" cols="45" rows="8" maxlength="65525"'.$required_attribute.'></textarea>'),
            'must_log_in' => sprintf('<p class="must-log-in">%s</p>', sprintf(/* translators: %s: Login URL. */ __('You must be <a href="%s">logged in</a> to post a comment.'), wp_login_url(apply_filters('the_permalink', get_permalink($post_id), $post_id)))),
            'logged_in_as' => sprintf('<p class="logged-in-as">%s%s</p>', sprintf(/* translators: 1: User name, 2: Edit user link, 3: Logout URL. */ __('Logged in as %1$s. <a href="%2$s">Edit your profile</a>. <a href="%3$s">Log out?</a>'), $user_identity, get_edit_user_link(), wp_logout_url(apply_filters('the_permalink', get_permalink($post_id), $post_id))), $required_text),
            'comment_notes_before' => sprintf('<p class="comment-notes">%s%s</p>', sprintf('<span id="email-notes">%s</span>', __('Your email address will not be published.')), $required_text),
            'comment_notes_after' => '',
            'action' => site_url('/wp-comments-post.php'),
            'id_form' => 'commentform',
            'id_submit' => 'submit',
            'class_container' => 'comment-respond',
            'class_form' => 'comment-form',
            'class_submit' => 'submit',
            'name_submit' => 'submit',
            'title_reply' => __('Leave a Reply'),
            /* translators: %s: Author of the comment being replied to. */
            'title_reply_to' => __('Leave a Reply to %s'),
            'title_reply_before' => '<h3 id="reply-title" class="comment-reply-title">',
            'title_reply_after' => '</h3>',
            'cancel_reply_before' => ' <small>',
            'cancel_reply_after' => '</small>',
            'cancel_reply_link' => __('Cancel reply'),
            'label_submit' => __('Post Comment'),
            'submit_button' => '<input name="%1$s" type="submit" id="%2$s" class="%3$s" value="%4$s" />',
            'submit_field' => '<p class="form-submit">%1$s %2$s</p>',
            'format' => 'xhtml',
        ];

        $args = wp_parse_args($args, apply_filters('comment_form_defaults', $defaults));

        // Ensure that the filtered arguments contain all required default values.
        $args = array_merge($defaults, $args);

        // Remove `aria-describedby` from the email field if there's no associated description.
        if(isset($args['fields']['email']) && ! str_contains($args['comment_notes_before'], 'id="email-notes"'))
        {
            $args['fields']['email'] = str_replace(' aria-describedby="email-notes"', '', $args['fields']['email']);
        }

        do_action('comment_form_before');
        ?>
        <div id="respond" class="<?php echo esc_attr($args['class_container']); ?>">
            <?php
                echo $args['title_reply_before'];

                comment_form_title($args['title_reply'], $args['title_reply_to'], true, $post_id);

                if(get_option('thread_comments'))
                {
                    echo $args['cancel_reply_before'];

                    cancel_comment_reply_link($args['cancel_reply_link']);

                    echo $args['cancel_reply_after'];
                }

                echo $args['title_reply_after'];

                if(get_option('comment_registration') && ! is_user_logged_in()) :

                    echo $args['must_log_in'];

                    do_action('comment_form_must_log_in_after');

                else :

                    printf('<form action="%s" method="post" id="%s" class="%s"%s>', esc_url($args['action']), esc_attr($args['id_form']), esc_attr($args['class_form']), ($html5 ? ' novalidate' : ''));

                    do_action('comment_form_top');

                    if(is_user_logged_in()) :

                        echo apply_filters('comment_form_logged_in', $args['logged_in_as'], $commenter, $user_identity);

                        do_action('comment_form_logged_in_after', $commenter, $user_identity);

                    else :

                        echo $args['comment_notes_before'];

                    endif;

                    // Prepare an array of all fields, including the textarea.
                    $comment_fields = ['comment' => $args['comment_field']] + (array) $args['fields'];

                    $comment_fields = apply_filters('comment_form_fields', $comment_fields);

                    // Get an array of field names, excluding the textarea.
                    $comment_field_keys = array_diff(array_keys($comment_fields), ['comment']);

                    // Get the first and the last field name, excluding the textarea.
                    $first_field = reset($comment_field_keys);
                    $last_field = end($comment_field_keys);

                    foreach($comment_fields as $name => $field)
                    {
                        if('comment' === $name)
                        {
                            echo apply_filters('comment_form_field_comment', $field);

                            echo $args['comment_notes_after'];
                        }
                        elseif(! is_user_logged_in())
                        {
                            if($first_field === $name)
                            {
                                do_action('comment_form_before_fields');
                            }

                            echo apply_filters("comment_form_field_{$name}", $field)."\n";

                            if($last_field === $name)
                            {
                                do_action('comment_form_after_fields');
                            }
                        }
                    }

                    $submit_button = sprintf($args['submit_button'], esc_attr($args['name_submit']), esc_attr($args['id_submit']), esc_attr($args['class_submit']), esc_attr($args['label_submit']));

                    $submit_button = apply_filters('comment_form_submit_button', $submit_button, $args);

                    $submit_field = sprintf($args['submit_field'], $submit_button, get_comment_id_fields($post_id));

                    echo apply_filters('comment_form_submit_field', $submit_field, $args);

                    do_action('comment_form', $post_id);

                    echo '</form>';

                endif;
            ?>
        </div><!-- #respond -->
        <?php

        do_action('comment_form_after');
    }
