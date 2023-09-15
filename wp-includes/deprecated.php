<?php

    /*
 * Deprecated functions come here to die.
 */

    function get_postdata($postid)
    {
        _deprecated_function(__FUNCTION__, '1.5.1', 'get_post()');

        $post = get_post($postid);

        $postdata = [
            'ID' => $post->ID,
            'Author_ID' => $post->post_author,
            'Date' => $post->post_date,
            'Content' => $post->post_content,
            'Excerpt' => $post->post_excerpt,
            'Title' => $post->post_title,
            'Category' => $post->post_category,
            'post_status' => $post->post_status,
            'comment_status' => $post->comment_status,
            'ping_status' => $post->ping_status,
            'post_password' => $post->post_password,
            'to_ping' => $post->to_ping,
            'pinged' => $post->pinged,
            'post_type' => $post->post_type,
            'post_name' => $post->post_name
        ];

        return $postdata;
    }

    function start_wp()
    {
        global $wp_query;

        _deprecated_function(__FUNCTION__, '1.5.0', __('new WordPress Loop'));

        // Since the old style loop is being used, advance the query iterator here.
        $wp_query->next_post();

        setup_postdata(get_post());
    }

    function the_category_ID($display = true)
    {
        _deprecated_function(__FUNCTION__, '0.71', 'get_the_category()');

        // Grab the first cat in the list.
        $categories = get_the_category();
        $cat = $categories[0]->term_id;

        if($display)
        {
            echo $cat;
        }

        return $cat;
    }

    function the_category_head($before = '', $after = '')
    {
        global $currentcat, $previouscat;

        _deprecated_function(__FUNCTION__, '0.71', 'get_the_category_by_ID()');

        // Grab the first cat in the list.
        $categories = get_the_category();
        $currentcat = $categories[0]->category_id;
        if($currentcat != $previouscat)
        {
            echo $before;
            echo get_the_category_by_ID($currentcat);
            echo $after;
            $previouscat = $currentcat;
        }
    }

    function previous_post(
        $format = '%', $previous = 'previous post: ', $title = 'yes', $in_same_cat = 'no', $limitprev = 1, $excluded_categories = ''
    ) {
        _deprecated_function(__FUNCTION__, '2.0.0', 'previous_post_link()');

        if(empty($in_same_cat) || 'no' == $in_same_cat)
        {
            $in_same_cat = false;
        }
        else
        {
            $in_same_cat = true;
        }

        $post = get_previous_post($in_same_cat, $excluded_categories);

        if(! $post)
        {
            return;
        }

        $string = '<a href="'.get_permalink($post->ID).'">'.$previous;
        if('yes' == $title)
        {
            $string .= apply_filters('the_title', $post->post_title, $post->ID);
        }
        $string .= '</a>';
        $format = str_replace('%', $string, $format);
        echo $format;
    }

    function next_post(
        $format = '%', $next = 'next post: ', $title = 'yes', $in_same_cat = 'no', $limitnext = 1, $excluded_categories = ''
    ) {
        _deprecated_function(__FUNCTION__, '2.0.0', 'next_post_link()');

        if(empty($in_same_cat) || 'no' == $in_same_cat)
        {
            $in_same_cat = false;
        }
        else
        {
            $in_same_cat = true;
        }

        $post = get_next_post($in_same_cat, $excluded_categories);

        if(! $post)
        {
            return;
        }

        $string = '<a href="'.get_permalink($post->ID).'">'.$next;
        if('yes' == $title)
        {
            $string .= apply_filters('the_title', $post->post_title, $post->ID);
        }
        $string .= '</a>';
        $format = str_replace('%', $string, $format);
        echo $format;
    }

    function user_can_create_post($user_id, $blog_id = 1, $category_id = 'None')
    {
        _deprecated_function(__FUNCTION__, '2.0.0', 'current_user_can()');

        $author_data = get_userdata($user_id);

        return ($author_data->user_level > 1);
    }

    function user_can_create_draft($user_id, $blog_id = 1, $category_id = 'None')
    {
        _deprecated_function(__FUNCTION__, '2.0.0', 'current_user_can()');

        $author_data = get_userdata($user_id);

        return ($author_data->user_level >= 1);
    }

    function user_can_edit_post($user_id, $post_id, $blog_id = 1)
    {
        _deprecated_function(__FUNCTION__, '2.0.0', 'current_user_can()');

        $author_data = get_userdata($user_id);
        $post = get_post($post_id);
        $post_author_data = get_userdata($post->post_author);

        return (($user_id == $post_author_data->ID) && ! ($post->post_status == 'publish' && $author_data->user_level < 2)) || ($author_data->user_level > $post_author_data->user_level) || ($author_data->user_level >= 10);
    }

    function user_can_delete_post($user_id, $post_id, $blog_id = 1)
    {
        _deprecated_function(__FUNCTION__, '2.0.0', 'current_user_can()');

        // Right now if one can edit, one can delete.
        return user_can_edit_post($user_id, $post_id, $blog_id);
    }

    function user_can_set_post_date($user_id, $blog_id = 1, $category_id = 'None')
    {
        _deprecated_function(__FUNCTION__, '2.0.0', 'current_user_can()');

        $author_data = get_userdata($user_id);

        return (($author_data->user_level > 4) && user_can_create_post($user_id, $blog_id, $category_id));
    }

    function user_can_edit_post_date($user_id, $post_id, $blog_id = 1)
    {
        _deprecated_function(__FUNCTION__, '2.0.0', 'current_user_can()');

        $author_data = get_userdata($user_id);

        return (($author_data->user_level > 4) && user_can_edit_post($user_id, $post_id, $blog_id));
    }

    function user_can_edit_post_comments($user_id, $post_id, $blog_id = 1)
    {
        _deprecated_function(__FUNCTION__, '2.0.0', 'current_user_can()');

        // Right now if one can edit a post, one can edit comments made on it.
        return user_can_edit_post($user_id, $post_id, $blog_id);
    }

    function user_can_delete_post_comments($user_id, $post_id, $blog_id = 1)
    {
        _deprecated_function(__FUNCTION__, '2.0.0', 'current_user_can()');

        // Right now if one can edit comments, one can delete comments.
        return user_can_edit_post_comments($user_id, $post_id, $blog_id);
    }

    function user_can_edit_user($user_id, $other_user)
    {
        _deprecated_function(__FUNCTION__, '2.0.0', 'current_user_can()');

        $user = get_userdata($user_id);
        $other = get_userdata($other_user);

        return $user->user_level > $other->user_level || $user->user_level > 8 || $user->ID == $other->ID;
    }

    function get_linksbyname(
        $cat_name = "noname", $before = '', $after = '<br />', $between = " ", $show_images = true, $orderby = 'id', $show_description = true, $show_rating = false, $limit = -1, $show_updated = 0
    ) {
        _deprecated_function(__FUNCTION__, '2.1.0', 'get_bookmarks()');

        $cat_id = -1;
        $cat = get_term_by('name', $cat_name, 'link_category');
        if($cat)
        {
            $cat_id = $cat->term_id;
        }

        get_links($cat_id, $before, $after, $between, $show_images, $orderby, $show_description, $show_rating, $limit, $show_updated);
    }

    function wp_get_linksbyname($category, $args = '')
    {
        _deprecated_function(__FUNCTION__, '2.1.0', 'wp_list_bookmarks()');

        $defaults = [
            'after' => '<br />',
            'before' => '',
            'categorize' => 0,
            'category_after' => '',
            'category_before' => '',
            'category_name' => $category,
            'show_description' => 1,
            'title_li' => '',
        ];

        $parsed_args = wp_parse_args($args, $defaults);

        return wp_list_bookmarks($parsed_args);
    }

    function get_linkobjectsbyname($cat_name = "noname", $orderby = 'name', $limit = -1)
    {
        _deprecated_function(__FUNCTION__, '2.1.0', 'get_bookmarks()');

        $cat_id = -1;
        $cat = get_term_by('name', $cat_name, 'link_category');
        if($cat)
        {
            $cat_id = $cat->term_id;
        }

        return get_linkobjects($cat_id, $orderby, $limit);
    }

    function get_linkobjects($category = 0, $orderby = 'name', $limit = 0)
    {
        _deprecated_function(__FUNCTION__, '2.1.0', 'get_bookmarks()');

        $links = get_bookmarks(compact('category', 'orderby', 'limit'));

        $links_array = [];
        foreach($links as $link)
        {
            $links_array[] = $link;
        }

        return $links_array;
    }

    function get_linksbyname_withrating(
        $cat_name = "noname", $before = '', $after = '<br />', $between = " ", $show_images = true, $orderby = 'id', $show_description = true, $limit = -1, $show_updated = 0
    ) {
        _deprecated_function(__FUNCTION__, '2.1.0', 'get_bookmarks()');

        get_linksbyname($cat_name, $before, $after, $between, $show_images, $orderby, $show_description, true, $limit, $show_updated);
    }

    function get_links_withrating(
        $category = -1, $before = '', $after = '<br />', $between = " ", $show_images = true, $orderby = 'id', $show_description = true, $limit = -1, $show_updated = 0
    ) {
        _deprecated_function(__FUNCTION__, '2.1.0', 'get_bookmarks()');

        get_links($category, $before, $after, $between, $show_images, $orderby, $show_description, true, $limit, $show_updated);
    }

    function get_autotoggle($id = 0)
    {
        _deprecated_function(__FUNCTION__, '2.1.0');

        return 0;
    }

    function list_cats(
        $optionall = 1, $all = 'All', $sort_column = 'ID', $sort_order = 'asc', $file = '', $list = true, $optiondates = 0, $optioncount = 0, $hide_empty = 1, $use_desc_for_title = 1, $children = false, $child_of = 0, $categories = 0, $recurse = 0, $feed = '', $feed_image = '', $exclude = '', $hierarchical = false
    ) {
        _deprecated_function(__FUNCTION__, '2.1.0', 'wp_list_categories()');

        $query = compact('optionall', 'all', 'sort_column', 'sort_order', 'file', 'list', 'optiondates', 'optioncount', 'hide_empty', 'use_desc_for_title', 'children', 'child_of', 'categories', 'recurse', 'feed', 'feed_image', 'exclude', 'hierarchical');

        return wp_list_cats($query);
    }

    function wp_list_cats($args = '')
    {
        _deprecated_function(__FUNCTION__, '2.1.0', 'wp_list_categories()');

        $parsed_args = wp_parse_args($args);

        // Map to new names.
        if(isset($parsed_args['optionall']) && isset($parsed_args['all']))
        {
            $parsed_args['show_option_all'] = $parsed_args['all'];
        }
        if(isset($parsed_args['sort_column']))
        {
            $parsed_args['orderby'] = $parsed_args['sort_column'];
        }
        if(isset($parsed_args['sort_order']))
        {
            $parsed_args['order'] = $parsed_args['sort_order'];
        }
        if(isset($parsed_args['optiondates']))
        {
            $parsed_args['show_last_update'] = $parsed_args['optiondates'];
        }
        if(isset($parsed_args['optioncount']))
        {
            $parsed_args['show_count'] = $parsed_args['optioncount'];
        }
        if(isset($parsed_args['list']))
        {
            $parsed_args['style'] = $parsed_args['list'] ? 'list' : 'break';
        }
        $parsed_args['title_li'] = '';

        return wp_list_categories($parsed_args);
    }

    function dropdown_cats(
        $optionall = 1, $all = 'All', $orderby = 'ID', $order = 'asc', $show_last_update = 0, $show_count = 0, $hide_empty = 1, $optionnone = false, $selected = 0, $exclude = 0
    ) {
        _deprecated_function(__FUNCTION__, '2.1.0', 'wp_dropdown_categories()');

        $show_option_all = '';
        if($optionall)
        {
            $show_option_all = $all;
        }

        $show_option_none = '';
        if($optionnone)
        {
            $show_option_none = __('None');
        }

        $vars = compact('show_option_all', 'show_option_none', 'orderby', 'order', 'show_last_update', 'show_count', 'hide_empty', 'selected', 'exclude');
        $query = add_query_arg($vars, '');

        return wp_dropdown_categories($query);
    }

    function list_authors(
        $optioncount = false, $exclude_admin = true, $show_fullname = false, $hide_empty = true, $feed = '', $feed_image = ''
    ) {
        _deprecated_function(__FUNCTION__, '2.1.0', 'wp_list_authors()');

        $args = compact('optioncount', 'exclude_admin', 'show_fullname', 'hide_empty', 'feed', 'feed_image');

        return wp_list_authors($args);
    }

    function wp_get_post_cats($blogid = '1', $post_id = 0)
    {
        _deprecated_function(__FUNCTION__, '2.1.0', 'wp_get_post_categories()');

        return wp_get_post_categories($post_id);
    }

    function wp_set_post_cats($blogid = '1', $post_id = 0, $post_categories = [])
    {
        _deprecated_function(__FUNCTION__, '2.1.0', 'wp_set_post_categories()');

        return wp_set_post_categories($post_id, $post_categories);
    }

    function get_archives(
        $type = '', $limit = '', $format = 'html', $before = '', $after = '', $show_post_count = false
    ) {
        _deprecated_function(__FUNCTION__, '2.1.0', 'wp_get_archives()');
        $args = compact('type', 'limit', 'format', 'before', 'after', 'show_post_count');

        return wp_get_archives($args);
    }

    function get_author_link($display, $author_id, $author_nicename = '')
    {
        _deprecated_function(__FUNCTION__, '2.1.0', 'get_author_posts_url()');

        $link = get_author_posts_url($author_id, $author_nicename);

        if($display)
        {
            echo $link;
        }

        return $link;
    }

    function link_pages(
        $before = '<br />', $after = '<br />', $next_or_number = 'number', $nextpagelink = 'next page', $previouspagelink = 'previous page', $pagelink = '%', $more_file = ''
    ) {
        _deprecated_function(__FUNCTION__, '2.1.0', 'wp_link_pages()');

        $args = compact('before', 'after', 'next_or_number', 'nextpagelink', 'previouspagelink', 'pagelink', 'more_file');

        return wp_link_pages($args);
    }

    function get_settings($option)
    {
        _deprecated_function(__FUNCTION__, '2.1.0', 'get_option()');

        return get_option($option);
    }

    function permalink_link()
    {
        _deprecated_function(__FUNCTION__, '1.2.0', 'the_permalink()');
        the_permalink();
    }

    function permalink_single_rss($deprecated = '')
    {
        _deprecated_function(__FUNCTION__, '2.3.0', 'the_permalink_rss()');
        the_permalink_rss();
    }

    function wp_get_links($args = '')
    {
        _deprecated_function(__FUNCTION__, '2.1.0', 'wp_list_bookmarks()');

        if(! str_contains($args, '='))
        {
            $cat_id = $args;
            $args = add_query_arg('category', $cat_id, $args);
        }

        $defaults = [
            'after' => '<br />',
            'before' => '',
            'between' => ' ',
            'categorize' => 0,
            'category' => '',
            'echo' => true,
            'limit' => -1,
            'orderby' => 'name',
            'show_description' => true,
            'show_images' => true,
            'show_rating' => false,
            'show_updated' => true,
            'title_li' => '',
        ];

        $parsed_args = wp_parse_args($args, $defaults);

        return wp_list_bookmarks($parsed_args);
    }

    function get_links(
        $category = -1, $before = '', $after = '<br />', $between = ' ', $show_images = true, $orderby = 'name', $show_description = true, $show_rating = false, $limit = -1, $show_updated = 1, $display = true
    ) {
        _deprecated_function(__FUNCTION__, '2.1.0', 'get_bookmarks()');

        $order = 'ASC';
        if(str_starts_with($orderby, '_'))
        {
            $order = 'DESC';
            $orderby = substr($orderby, 1);
        }

        if($category == -1) // get_bookmarks() uses '' to signify all categories.
        {
            $category = '';
        }

        $results = get_bookmarks(compact('category', 'orderby', 'order', 'show_updated', 'limit'));

        if(! $results)
        {
            return;
        }

        $output = '';

        foreach((array) $results as $row)
        {
            if(! isset($row->recently_updated))
            {
                $row->recently_updated = false;
            }
            $output .= $before;
            if($show_updated && $row->recently_updated)
            {
                $output .= get_option('links_recently_updated_prepend');
            }
            $the_link = '#';
            if(! empty($row->link_url))
            {
                $the_link = esc_url($row->link_url);
            }
            $rel = $row->link_rel;
            if('' != $rel)
            {
                $rel = ' rel="'.$rel.'"';
            }

            $desc = esc_attr(sanitize_bookmark_field('link_description', $row->link_description, $row->link_id, 'display'));
            $name = esc_attr(sanitize_bookmark_field('link_name', $row->link_name, $row->link_id, 'display'));
            $title = $desc;

            if($show_updated && ! str_starts_with($row->link_updated_f, '00'))
            {
                $title .= ' ('.__('Last updated').' '.gmdate(get_option('links_updated_date_format'), $row->link_updated_f + (get_option('gmt_offset') * HOUR_IN_SECONDS)).')';
            }

            if('' != $title)
            {
                $title = ' title="'.$title.'"';
            }

            $alt = ' alt="'.$name.'"';

            $target = $row->link_target;
            if('' != $target)
            {
                $target = ' target="'.$target.'"';
            }

            $output .= '<a href="'.$the_link.'"'.$rel.$title.$target.'>';

            if($row->link_image != null && $show_images)
            {
                if(str_contains($row->link_image, 'http'))
                {
                    $output .= "<img src=\"$row->link_image\" $alt $title />";
                }
                else // If it's a relative path.
                {
                    $output .= "<img src=\"".get_option('siteurl')."$row->link_image\" $alt $title />";
                }
            }
            else
            {
                $output .= $name;
            }

            $output .= '</a>';

            if($show_updated && $row->recently_updated)
            {
                $output .= get_option('links_recently_updated_append');
            }

            if($show_description && '' != $desc)
            {
                $output .= $between.$desc;
            }

            if($show_rating)
            {
                $output .= $between.get_linkrating($row);
            }

            $output .= "$after\n";
        } // End while.

        if(! $display)
        {
            return $output;
        }
        echo $output;
    }

    function get_links_list($order = 'name')
    {
        _deprecated_function(__FUNCTION__, '2.1.0', 'wp_list_bookmarks()');

        $order = strtolower($order);

        // Handle link category sorting.
        $direction = 'ASC';
        if(str_starts_with($order, '_'))
        {
            $direction = 'DESC';
            $order = substr($order, 1);
        }

        if(! isset($direction))
        {
            $direction = '';
        }

        $cats = get_categories(['type' => 'link', 'orderby' => $order, 'order' => $direction, 'hierarchical' => 0]);

        // Display each category.
        if($cats)
        {
            foreach((array) $cats as $cat)
            {
                // Handle each category.

                // Display the category name.
                echo '  <li id="linkcat-'.$cat->term_id.'" class="linkcat"><h2>'.apply_filters('link_category', $cat->name)."</h2>\n\t<ul>\n";
                // Call get_links() with all the appropriate params.
                get_links($cat->term_id, '<li>', "</li>", "\n", true, 'name', false);

                // Close the last category.
                echo "\n\t</ul>\n</li>\n";
            }
        }
    }

    function links_popup_script($text = 'Links', $width = 400, $height = 400, $file = 'links.all.php', $count = true)
    {
        _deprecated_function(__FUNCTION__, '2.1.0');
    }

    function get_linkrating($link)
    {
        _deprecated_function(__FUNCTION__, '2.1.0', 'sanitize_bookmark_field()');

        return sanitize_bookmark_field('link_rating', $link->link_rating, $link->link_id, 'display');
    }

    function get_linkcatname($id = 0)
    {
        _deprecated_function(__FUNCTION__, '2.1.0', 'get_category()');

        $id = (int) $id;

        if(empty($id))
        {
            return '';
        }

        $cats = wp_get_link_cats($id);

        if(empty($cats) || ! is_array($cats))
        {
            return '';
        }

        $cat_id = (int) $cats[0]; // Take the first cat.

        $cat = get_category($cat_id);

        return $cat->name;
    }

    function comments_rss_link($link_text = 'Comments RSS')
    {
        _deprecated_function(__FUNCTION__, '2.5.0', 'post_comments_feed_link()');
        post_comments_feed_link($link_text);
    }

    function get_category_rss_link($display = false, $cat_id = 1)
    {
        _deprecated_function(__FUNCTION__, '2.5.0', 'get_category_feed_link()');

        $link = get_category_feed_link($cat_id, 'rss2');

        if($display)
        {
            echo $link;
        }

        return $link;
    }

    function get_author_rss_link($display = false, $author_id = 1)
    {
        _deprecated_function(__FUNCTION__, '2.5.0', 'get_author_feed_link()');

        $link = get_author_feed_link($author_id);
        if($display)
        {
            echo $link;
        }

        return $link;
    }

    function comments_rss()
    {
        _deprecated_function(__FUNCTION__, '2.2.0', 'get_post_comments_feed_link()');

        return esc_url(get_post_comments_feed_link());
    }

    function create_user($username, $password, $email)
    {
        _deprecated_function(__FUNCTION__, '2.0.0', 'wp_create_user()');

        return wp_create_user($username, $password, $email);
    }

    function gzip_compression()
    {
        _deprecated_function(__FUNCTION__, '2.5.0');

        return false;
    }

    function get_commentdata($comment_id, $no_cache = 0, $include_unapproved = false)
    {
        _deprecated_function(__FUNCTION__, '2.7.0', 'get_comment()');

        return get_comment($comment_id, ARRAY_A);
    }

    function get_catname($cat_id)
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'get_cat_name()');

        return get_cat_name($cat_id);
    }

    function get_category_children($id, $before = '/', $after = '', $visited = [])
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'get_term_children()');
        if(0 == $id)
        {
            return '';
        }

        $chain = '';

        $cat_ids = get_all_category_ids();
        foreach((array) $cat_ids as $cat_id)
        {
            if($cat_id == $id)
            {
                continue;
            }

            $category = get_category($cat_id);
            if(is_wp_error($category))
            {
                return $category;
            }
            if($category->parent == $id && ! in_array($category->term_id, $visited))
            {
                $visited[] = $category->term_id;
                $chain .= $before.$category->term_id.$after;
                $chain .= get_category_children($category->term_id, $before, $after);
            }
        }

        return $chain;
    }

    function get_all_category_ids()
    {
        _deprecated_function(__FUNCTION__, '4.0.0', 'get_terms()');

        $cat_ids = get_terms([
                                 'taxonomy' => 'category',
                                 'fields' => 'ids',
                                 'get' => 'all',
                             ]);

        return $cat_ids;
    }

    function get_the_author_description()
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'get_the_author_meta(\'description\')');

        return get_the_author_meta('description');
    }

    function the_author_description()
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'the_author_meta(\'description\')');
        the_author_meta('description');
    }

    function get_the_author_login()
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'get_the_author_meta(\'login\')');

        return get_the_author_meta('login');
    }

    function the_author_login()
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'the_author_meta(\'login\')');
        the_author_meta('login');
    }

    function get_the_author_firstname()
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'get_the_author_meta(\'first_name\')');

        return get_the_author_meta('first_name');
    }

    function the_author_firstname()
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'the_author_meta(\'first_name\')');
        the_author_meta('first_name');
    }

    function get_the_author_lastname()
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'get_the_author_meta(\'last_name\')');

        return get_the_author_meta('last_name');
    }

    function the_author_lastname()
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'the_author_meta(\'last_name\')');
        the_author_meta('last_name');
    }

    function get_the_author_nickname()
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'get_the_author_meta(\'nickname\')');

        return get_the_author_meta('nickname');
    }

    function the_author_nickname()
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'the_author_meta(\'nickname\')');
        the_author_meta('nickname');
    }

    function get_the_author_email()
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'get_the_author_meta(\'email\')');

        return get_the_author_meta('email');
    }

    function the_author_email()
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'the_author_meta(\'email\')');
        the_author_meta('email');
    }

    function get_the_author_icq()
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'get_the_author_meta(\'icq\')');

        return get_the_author_meta('icq');
    }

    function the_author_icq()
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'the_author_meta(\'icq\')');
        the_author_meta('icq');
    }

    function get_the_author_yim()
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'get_the_author_meta(\'yim\')');

        return get_the_author_meta('yim');
    }

    function the_author_yim()
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'the_author_meta(\'yim\')');
        the_author_meta('yim');
    }

    function get_the_author_msn()
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'get_the_author_meta(\'msn\')');

        return get_the_author_meta('msn');
    }

    function the_author_msn()
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'the_author_meta(\'msn\')');
        the_author_meta('msn');
    }

    function get_the_author_aim()
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'get_the_author_meta(\'aim\')');

        return get_the_author_meta('aim');
    }

    function the_author_aim()
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'the_author_meta(\'aim\')');
        the_author_meta('aim');
    }

    function get_author_name($auth_id = false)
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'get_the_author_meta(\'display_name\')');

        return get_the_author_meta('display_name', $auth_id);
    }

    function get_the_author_url()
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'get_the_author_meta(\'url\')');

        return get_the_author_meta('url');
    }

    function the_author_url()
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'the_author_meta(\'url\')');
        the_author_meta('url');
    }

    function get_the_author_ID()
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'get_the_author_meta(\'ID\')');

        return get_the_author_meta('ID');
    }

    function the_author_ID()
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'the_author_meta(\'ID\')');
        the_author_meta('ID');
    }

    function the_content_rss(
        $more_link_text = '(more...)', $stripteaser = 0, $more_file = '', $cut = 0, $encode_html = 0
    ) {
        _deprecated_function(__FUNCTION__, '2.9.0', 'the_content_feed()');
        $content = get_the_content($more_link_text, $stripteaser);

        $content = apply_filters('the_content_rss', $content);
        if($cut && ! $encode_html)
        {
            $encode_html = 2;
        }
        if(1 == $encode_html)
        {
            $content = esc_html($content);
            $cut = 0;
        }
        elseif(0 == $encode_html)
        {
            $content = make_url_footnote($content);
        }
        elseif(2 == $encode_html)
        {
            $content = strip_tags($content);
        }
        if($cut)
        {
            $blah = explode(' ', $content);
            if(count($blah) > $cut)
            {
                $k = $cut;
                $use_dotdotdot = 1;
            }
            else
            {
                $k = count($blah);
                $use_dotdotdot = 0;
            }

            for($i = 0; $i < $k; $i++)
            {
                $excerpt .= $blah[$i].' ';
            }
            $excerpt .= ($use_dotdotdot) ? '...' : '';
            $content = $excerpt;
        }
        $content = str_replace(']]>', ']]&gt;', $content);
        echo $content;
    }

    function make_url_footnote($content)
    {
        _deprecated_function(__FUNCTION__, '2.9.0', '');
        preg_match_all('/<a(.+?)href=\"(.+?)\"(.*?)>(.+?)<\/a>/', $content, $matches);
        $links_summary = "\n";
        for($i = 0, $c = count($matches[0]); $i < $c; $i++)
        {
            $link_match = $matches[0][$i];
            $link_number = '['.($i + 1).']';
            $link_url = $matches[2][$i];
            $link_text = $matches[4][$i];
            $content = str_replace($link_match, $link_text.' '.$link_number, $content);
            $link_url = ((stripos($link_url, 'http://') !== 0) && (stripos($link_url, 'https://') !== 0)) ? get_option('home').$link_url : $link_url;
            $links_summary .= "\n".$link_number.' '.$link_url;
        }
        $content = strip_tags($content);
        $content .= $links_summary;

        return $content;
    }

    function _c($text, $domain = 'default')
    {
        _deprecated_function(__FUNCTION__, '2.9.0', '_x()');

        return before_last_bar(translate($text, $domain));
    }

    function translate_with_context($text, $domain = 'default')
    {
        _deprecated_function(__FUNCTION__, '2.9.0', '_x()');

        return before_last_bar(translate($text, $domain));
    }

    function _nc($single, $plural, $number, $domain = 'default')
    {
        _deprecated_function(__FUNCTION__, '2.9.0', '_nx()');

        return before_last_bar(_n($single, $plural, $number, $domain));
    }

    function __ngettext(...$args)
    { // phpcs:ignore PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore
        _deprecated_function(__FUNCTION__, '2.8.0', '_n()');

        return _n(...$args);
    }

    function __ngettext_noop(...$args)
    { // phpcs:ignore PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore
        _deprecated_function(__FUNCTION__, '2.8.0', '_n_noop()');

        return _n_noop(...$args);
    }

    function get_alloptions()
    {
        _deprecated_function(__FUNCTION__, '3.0.0', 'wp_load_alloptions()');

        return wp_load_alloptions();
    }

    function get_the_attachment_link($id = 0, $fullsize = false, $max_dims = false, $permalink = false)
    {
        _deprecated_function(__FUNCTION__, '2.5.0', 'wp_get_attachment_link()');
        $id = (int) $id;
        $_post = get_post($id);

        if(('attachment' != $_post->post_type) || ! $url = wp_get_attachment_url($_post->ID))
        {
            return __('Missing Attachment');
        }

        if($permalink)
        {
            $url = get_attachment_link($_post->ID);
        }

        $post_title = esc_attr($_post->post_title);

        $innerHTML = get_attachment_innerHTML($_post->ID, $fullsize, $max_dims);

        return "<a href='$url' title='$post_title'>$innerHTML</a>";
    }

    function get_attachment_icon_src($id = 0, $fullsize = false)
    {
        _deprecated_function(__FUNCTION__, '2.5.0', 'wp_get_attachment_image_src()');
        $id = (int) $id;
        if(! $post = get_post($id))
        {
            return false;
        }

        $file = get_attached_file($post->ID);

        if(! $fullsize && $src = wp_get_attachment_thumb_url($post->ID))
        {
            // We have a thumbnail desired, specified and existing.

            $src_file = wp_basename($src);
        }
        elseif(wp_attachment_is_image($post->ID))
        {
            // We have an image without a thumbnail.

            $src = wp_get_attachment_url($post->ID);
            $src_file = &$file;
        }
        elseif($src = wp_mime_type_icon($post->ID))
        {
            // No thumb, no image. We'll look for a mime-related icon instead.

            $icon_dir = apply_filters('icon_dir', get_template_directory().'/images');
            $src_file = $icon_dir.'/'.wp_basename($src);
        }

        if(! isset($src) || ! $src)
        {
            return false;
        }

        return [$src, $src_file];
    }

    function get_attachment_icon($id = 0, $fullsize = false, $max_dims = false)
    {
        _deprecated_function(__FUNCTION__, '2.5.0', 'wp_get_attachment_image()');
        $id = (int) $id;
        if(get_post($id) || ! $src = get_attachment_icon_src($post->ID, $fullsize))
        {
            return false;
        }

        [$src, $src_file] = $src;

        // Do we need to constrain the image?
        if(($max_dims = apply_filters('attachment_max_dims', $max_dims)) && file_exists($src_file))
        {
            $imagesize = wp_getimagesize($src_file);

            if(($imagesize[0] > $max_dims[0]) || $imagesize[1] > $max_dims[1])
            {
                $actual_aspect = $imagesize[0] / $imagesize[1];
                $desired_aspect = $max_dims[0] / $max_dims[1];

                if($actual_aspect >= $desired_aspect)
                {
                    $height = $actual_aspect * $max_dims[0];
                    $constraint = "width='{$max_dims[0]}' ";
                    $post->iconsize = [$max_dims[0], $height];
                }
                else
                {
                    $width = $max_dims[1] / $actual_aspect;
                    $constraint = "height='{$max_dims[1]}' ";
                    $post->iconsize = [$width, $max_dims[1]];
                }
            }
            else
            {
                $post->iconsize = [$imagesize[0], $imagesize[1]];
                $constraint = '';
            }
        }
        else
        {
            $constraint = '';
        }

        $post_title = esc_attr($post->post_title);

        $icon = "<img src='$src' title='$post_title' alt='$post_title' $constraint/>";

        return apply_filters('attachment_icon', $icon, $post->ID);
    }

    function get_attachment_innerHTML($id = 0, $fullsize = false, $max_dims = false)
    {
        _deprecated_function(__FUNCTION__, '2.5.0', 'wp_get_attachment_image()');
        $id = (int) $id;
        if(! $post = get_post($id))
        {
            return false;
        }

        if($innerHTML = get_attachment_icon($post->ID, $fullsize, $max_dims))
        {
            return $innerHTML;
        }

        $innerHTML = esc_attr($post->post_title);

        return apply_filters('attachment_innerHTML', $innerHTML, $post->ID);
    }

    function get_link($bookmark_id, $output = OBJECT, $filter = 'raw')
    {
        _deprecated_function(__FUNCTION__, '2.1.0', 'get_bookmark()');

        return get_bookmark($bookmark_id, $output, $filter);
    }

    function clean_url($url, $protocols = null, $context = 'display')
    {
        if($context == 'db')
        {
            _deprecated_function('clean_url( $context = \'db\' )', '3.0.0', 'sanitize_url()');
        }
        else
        {
            _deprecated_function(__FUNCTION__, '3.0.0', 'esc_url()');
        }

        return esc_url($url, $protocols, $context);
    }

    function js_escape($text)
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'esc_js()');

        return esc_js($text);
    }

    function wp_specialchars($text, $quote_style = ENT_NOQUOTES, $charset = false, $double_encode = false)
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'esc_html()');
        if(func_num_args() > 1)
        { // Maintain back-compat for people passing additional arguments.
            return _wp_specialchars($text, $quote_style, $charset, $double_encode);
        }
        else
        {
            return esc_html($text);
        }
    }

    function attribute_escape($text)
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'esc_attr()');

        return esc_attr($text);
    }

    function register_sidebar_widget($name, $output_callback, $classname = '', ...$params)
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'wp_register_sidebar_widget()');
        // Compat.
        if(is_array($name))
        {
            if(count($name) === 3)
            {
                $name = sprintf($name[0], $name[2]);
            }
            else
            {
                $name = $name[0];
            }
        }

        $id = sanitize_title($name);
        $options = [];
        if(! empty($classname) && is_string($classname))
        {
            $options['classname'] = $classname;
        }

        wp_register_sidebar_widget($id, $name, $output_callback, $options, ...$params);
    }

    function unregister_sidebar_widget($id)
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'wp_unregister_sidebar_widget()');

        return wp_unregister_sidebar_widget($id);
    }

    function register_widget_control($name, $control_callback, $width = '', $height = '', ...$params)
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'wp_register_widget_control()');
        // Compat.
        if(is_array($name))
        {
            if(count($name) === 3)
            {
                $name = sprintf($name[0], $name[2]);
            }
            else
            {
                $name = $name[0];
            }
        }

        $id = sanitize_title($name);
        $options = [];
        if(! empty($width))
        {
            $options['width'] = $width;
        }
        if(! empty($height))
        {
            $options['height'] = $height;
        }

        wp_register_widget_control($id, $name, $control_callback, $options, ...$params);
    }

    function unregister_widget_control($id)
    {
        _deprecated_function(__FUNCTION__, '2.8.0', 'wp_unregister_widget_control()');

        return wp_unregister_widget_control($id);
    }

    function delete_usermeta($user_id, $meta_key, $meta_value = '')
    {
        _deprecated_function(__FUNCTION__, '3.0.0', 'delete_user_meta()');
        global $wpdb;
        if(! is_numeric($user_id))
        {
            return false;
        }
        $meta_key = preg_replace('|[^a-z0-9_]|i', '', $meta_key);

        if(is_array($meta_value) || is_object($meta_value))
        {
            $meta_value = serialize($meta_value);
        }
        $meta_value = trim($meta_value);

        $cur = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->usermeta WHERE user_id = %d AND meta_key = %s", $user_id, $meta_key));

        if($cur && $cur->umeta_id)
        {
            do_action('delete_usermeta', $cur->umeta_id, $user_id, $meta_key, $meta_value);
        }

        if(! empty($meta_value))
        {
            $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->usermeta WHERE user_id = %d AND meta_key = %s AND meta_value = %s", $user_id, $meta_key, $meta_value));
        }
        else
        {
            $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->usermeta WHERE user_id = %d AND meta_key = %s", $user_id, $meta_key));
        }

        clean_user_cache($user_id);
        wp_cache_delete($user_id, 'user_meta');

        if($cur && $cur->umeta_id)
        {
            do_action('deleted_usermeta', $cur->umeta_id, $user_id, $meta_key, $meta_value);
        }

        return true;
    }

    function get_usermeta($user_id, $meta_key = '')
    {
        _deprecated_function(__FUNCTION__, '3.0.0', 'get_user_meta()');
        global $wpdb;
        $user_id = (int) $user_id;

        if(! $user_id)
        {
            return false;
        }

        if(! empty($meta_key))
        {
            $meta_key = preg_replace('|[^a-z0-9_]|i', '', $meta_key);
            $user = wp_cache_get($user_id, 'users');
            // Check the cached user object.
            if(false !== $user && isset($user->$meta_key))
            {
                $metas = [$user->$meta_key];
            }
            else
            {
                $metas = $wpdb->get_col($wpdb->prepare("SELECT meta_value FROM $wpdb->usermeta WHERE user_id = %d AND meta_key = %s", $user_id, $meta_key));
            }
        }
        else
        {
            $metas = $wpdb->get_col($wpdb->prepare("SELECT meta_value FROM $wpdb->usermeta WHERE user_id = %d", $user_id));
        }

        if(empty($metas))
        {
            if(empty($meta_key))
            {
                return [];
            }
            else
            {
                return '';
            }
        }

        $metas = array_map('maybe_unserialize', $metas);

        if(count($metas) === 1)
        {
            return $metas[0];
        }
        else
        {
            return $metas;
        }
    }

    function update_usermeta($user_id, $meta_key, $meta_value)
    {
        _deprecated_function(__FUNCTION__, '3.0.0', 'update_user_meta()');
        global $wpdb;
        if(! is_numeric($user_id))
        {
            return false;
        }
        $meta_key = preg_replace('|[^a-z0-9_]|i', '', $meta_key);

        if(is_string($meta_value))
        {
            $meta_value = stripslashes($meta_value);
        }
        $meta_value = maybe_serialize($meta_value);

        if(empty($meta_value))
        {
            return delete_usermeta($user_id, $meta_key);
        }

        $cur = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->usermeta WHERE user_id = %d AND meta_key = %s", $user_id, $meta_key));

        if($cur)
        {
            do_action('update_usermeta', $cur->umeta_id, $user_id, $meta_key, $meta_value);
        }

        if(! $cur)
        {
            $wpdb->insert($wpdb->usermeta, compact('user_id', 'meta_key', 'meta_value'));
        }
        elseif($cur->meta_value != $meta_value)
        {
            $wpdb->update($wpdb->usermeta, compact('meta_value'), compact('user_id', 'meta_key'));
        }
        else
        {
            return false;
        }

        clean_user_cache($user_id);
        wp_cache_delete($user_id, 'user_meta');

        if($cur)
        {
            do_action('updated_usermeta', $cur->umeta_id, $user_id, $meta_key, $meta_value);
        }
        else
        {
            do_action('added_usermeta', $wpdb->insert_id, $user_id, $meta_key, $meta_value);
        }

        return true;
    }

    function get_users_of_blog($id = '')
    {
        _deprecated_function(__FUNCTION__, '3.1.0', 'get_users()');

        global $wpdb;
        if(empty($id))
        {
            $id = get_current_blog_id();
        }
        $blog_prefix = $wpdb->get_blog_prefix($id);
        $users = $wpdb->get_results("SELECT user_id, user_id AS ID, user_login, display_name, user_email, meta_value FROM $wpdb->users, $wpdb->usermeta WHERE {$wpdb->users}.ID = {$wpdb->usermeta}.user_id AND meta_key = '{$blog_prefix}capabilities' ORDER BY {$wpdb->usermeta}.user_id");

        return $users;
    }

    function automatic_feed_links($add = true)
    {
        _deprecated_function(__FUNCTION__, '3.0.0', "add_theme_support( 'automatic-feed-links' )");

        if($add)
        {
            add_theme_support('automatic-feed-links');
        }
        else
        {
            remove_action('wp_head', 'feed_links_extra', 3);
        } // Just do this yourself in 3.0+.
    }

    function get_profile($field, $user = false)
    {
        _deprecated_function(__FUNCTION__, '3.0.0', 'get_the_author_meta()');
        if($user)
        {
            $user = get_user_by('login', $user);
            $user = $user->ID;
        }

        return get_the_author_meta($field, $user);
    }

    function get_usernumposts($userid)
    {
        _deprecated_function(__FUNCTION__, '3.0.0', 'count_user_posts()');

        return count_user_posts($userid);
    }

    function funky_javascript_callback($matches)
    {
        return "&#".base_convert($matches[1], 16, 10).";";
    }

    function funky_javascript_fix($text)
    {
        _deprecated_function(__FUNCTION__, '3.0.0');
        // Fixes for browsers' JavaScript bugs.
        global $is_macIE, $is_winIE;

        if($is_winIE || $is_macIE)
        {
            $text = preg_replace_callback("/\%u([0-9A-F]{4,4})/", "funky_javascript_callback", $text);
        }

        return $text;
    }

    function is_taxonomy($taxonomy)
    {
        _deprecated_function(__FUNCTION__, '3.0.0', 'taxonomy_exists()');

        return taxonomy_exists($taxonomy);
    }

    function is_term($term, $taxonomy = '', $parent = 0)
    {
        _deprecated_function(__FUNCTION__, '3.0.0', 'term_exists()');

        return term_exists($term, $taxonomy, $parent);
    }

    function is_plugin_page()
    {
        _deprecated_function(__FUNCTION__, '3.1.0');

        global $plugin_page;

        if(isset($plugin_page))
        {
            return true;
        }

        return false;
    }

    function update_category_cache()
    {
        _deprecated_function(__FUNCTION__, '3.1.0');

        return true;
    }

    function wp_timezone_supported()
    {
        _deprecated_function(__FUNCTION__, '3.2.0');

        return true;
    }

    function the_editor(
        $content, $id = 'content', $prev_id = 'title', $media_buttons = true, $tab_index = 2, $extended = true
    ) {
        _deprecated_function(__FUNCTION__, '3.3.0', 'wp_editor()');

        wp_editor($content, $id, ['media_buttons' => $media_buttons]);
    }

    function get_user_metavalues($ids)
    {
        _deprecated_function(__FUNCTION__, '3.3.0');

        $objects = [];

        $ids = array_map('intval', $ids);
        foreach($ids as $id)
        {
            $objects[$id] = [];
        }

        $metas = update_meta_cache('user', $ids);

        foreach($metas as $id => $meta)
        {
            foreach($meta as $key => $metavalues)
            {
                foreach($metavalues as $value)
                {
                    $objects[$id][] = (object) ['user_id' => $id, 'meta_key' => $key, 'meta_value' => $value];
                }
            }
        }

        return $objects;
    }

    function sanitize_user_object($user, $context = 'display')
    {
        _deprecated_function(__FUNCTION__, '3.3.0');

        if(is_object($user))
        {
            if(! isset($user->ID))
            {
                $user->ID = 0;
            }
            if(! ($user instanceof WP_User))
            {
                $vars = get_object_vars($user);
                foreach(array_keys($vars) as $field)
                {
                    if(is_string($user->$field) || is_numeric($user->$field))
                    {
                        $user->$field = sanitize_user_field($field, $user->$field, $user->ID, $context);
                    }
                }
            }
            $user->filter = $context;
        }
        else
        {
            if(! isset($user['ID']))
            {
                $user['ID'] = 0;
            }
            foreach(array_keys($user) as $field)
            {
                $user[$field] = sanitize_user_field($field, $user[$field], $user['ID'], $context);
            }
            $user['filter'] = $context;
        }

        return $user;
    }

    function get_boundary_post_rel_link(
        $title = '%title', $in_same_cat = false, $excluded_categories = '', $start = true
    ) {
        _deprecated_function(__FUNCTION__, '3.3.0');

        $posts = get_boundary_post($in_same_cat, $excluded_categories, $start);
        // If there is no post, stop.
        if(empty($posts))
        {
            return;
        }

        // Even though we limited get_posts() to return only 1 item it still returns an array of objects.
        $post = $posts[0];

        if(empty($post->post_title))
        {
            $post->post_title = $start ? __('First Post') : __('Last Post');
        }

        $date = mysql2date(get_option('date_format'), $post->post_date);

        $title = str_replace(array('%title', '%date'), array($post->post_title, $date), $title);
        $title = apply_filters('the_title', $title, $post->ID);

        $link = $start ? "<link rel='start' title='" : "<link rel='end' title='";
        $link .= esc_attr($title);
        $link .= "' href='".get_permalink($post)."' />\n";

        $boundary = $start ? 'start' : 'end';

        return apply_filters("{$boundary}_post_rel_link", $link);
    }

    function start_post_rel_link($title = '%title', $in_same_cat = false, $excluded_categories = '')
    {
        _deprecated_function(__FUNCTION__, '3.3.0');

        echo get_boundary_post_rel_link($title, $in_same_cat, $excluded_categories, true);
    }

    function get_index_rel_link()
    {
        _deprecated_function(__FUNCTION__, '3.3.0');

        $link = "<link rel='index' title='".esc_attr(get_bloginfo('name', 'display'))."' href='".esc_url(user_trailingslashit(get_bloginfo('url', 'display')))."' />\n";

        return apply_filters("index_rel_link", $link);
    }

    function index_rel_link()
    {
        _deprecated_function(__FUNCTION__, '3.3.0');

        echo get_index_rel_link();
    }

    function get_parent_post_rel_link($title = '%title')
    {
        _deprecated_function(__FUNCTION__, '3.3.0');

        if(! empty($GLOBALS['post']) && ! empty($GLOBALS['post']->post_parent))
        {
            $post = get_post($GLOBALS['post']->post_parent);
        }

        if(empty($post))
        {
            return;
        }

        $date = mysql2date(get_option('date_format'), $post->post_date);

        $title = str_replace(array('%title', '%date'), array($post->post_title, $date), $title);
        $title = apply_filters('the_title', $title, $post->ID);

        $link = "<link rel='up' title='";
        $link .= esc_attr($title);
        $link .= "' href='".get_permalink($post)."' />\n";

        return apply_filters("parent_post_rel_link", $link);
    }

    function parent_post_rel_link($title = '%title')
    {
        _deprecated_function(__FUNCTION__, '3.3.0');

        echo get_parent_post_rel_link($title);
    }

    function wp_admin_bar_dashboard_view_site_menu($wp_admin_bar)
    {
        _deprecated_function(__FUNCTION__, '3.3.0');

        $user_id = get_current_user_id();

        if(0 != $user_id)
        {
            if(is_admin())
            {
                $wp_admin_bar->add_menu(['id' => 'view-site', 'title' => __('Visit Site'), 'href' => home_url()]);
            }
            elseif(is_multisite())
            {
                $wp_admin_bar->add_menu([
                                            'id' => 'dashboard',
                                            'title' => __('Dashboard'),
                                            'href' => get_dashboard_url($user_id)
                                        ]);
            }
            else
            {
                $wp_admin_bar->add_menu(['id' => 'dashboard', 'title' => __('Dashboard'), 'href' => admin_url()]);
            }
        }
    }

    function is_blog_user($blog_id = 0)
    {
        _deprecated_function(__FUNCTION__, '3.3.0', 'is_user_member_of_blog()');

        return is_user_member_of_blog(get_current_user_id(), $blog_id);
    }

    function debug_fopen($filename, $mode)
    {
        _deprecated_function(__FUNCTION__, '3.4.0', 'error_log()');

        return false;
    }

    function debug_fwrite($fp, $message)
    {
        _deprecated_function(__FUNCTION__, '3.4.0', 'error_log()');
        if(! empty($GLOBALS['debug']))
        {
        }
    }

    function debug_fclose($fp)
    {
        _deprecated_function(__FUNCTION__, '3.4.0', 'error_log()');
    }

    function get_themes()
    {
        _deprecated_function(__FUNCTION__, '3.4.0', 'wp_get_themes()');

        global $wp_themes;
        if(isset($wp_themes))
        {
            return $wp_themes;
        }

        $themes = wp_get_themes();
        $wp_themes = [];

        foreach($themes as $theme)
        {
            $name = $theme->get('Name');
            if(isset($wp_themes[$name]))
            {
                $wp_themes[$name.'/'.$theme->get_stylesheet()] = $theme;
            }
            else
            {
                $wp_themes[$name] = $theme;
            }
        }

        return $wp_themes;
    }

    function get_theme($theme)
    {
        _deprecated_function(__FUNCTION__, '3.4.0', 'wp_get_theme( $stylesheet )');

        $themes = get_themes();
        if(is_array($themes) && array_key_exists($theme, $themes))
        {
            return $themes[$theme];
        }

        return null;
    }

    function get_current_theme()
    {
        _deprecated_function(__FUNCTION__, '3.4.0', 'wp_get_theme()');

        if($theme = get_option('current_theme'))
        {
            return $theme;
        }

        return wp_get_theme()->get('Name');
    }

    function clean_pre($matches)
    {
        _deprecated_function(__FUNCTION__, '3.4.0');

        if(is_array($matches))
        {
            $text = $matches[1].$matches[2]."</pre>";
        }
        else
        {
            $text = $matches;
        }

        $text = str_replace(['<br />', '<br/>', '<br>'], ['', '', ''], $text);
        $text = str_replace(array('<p>', '</p>'), array("\n", ''), $text);

        return $text;
    }

    function add_custom_image_header($wp_head_callback, $admin_head_callback, $admin_preview_callback = '')
    {
        _deprecated_function(__FUNCTION__, '3.4.0', 'add_theme_support( \'custom-header\', $args )');
        $args = [
            'wp-head-callback' => $wp_head_callback,
            'admin-head-callback' => $admin_head_callback,
        ];
        if($admin_preview_callback)
        {
            $args['admin-preview-callback'] = $admin_preview_callback;
        }

        return add_theme_support('custom-header', $args);
    }

    function remove_custom_image_header()
    {
        _deprecated_function(__FUNCTION__, '3.4.0', 'remove_theme_support( \'custom-header\' )');

        return remove_theme_support('custom-header');
    }

    function add_custom_background($wp_head_callback = '', $admin_head_callback = '', $admin_preview_callback = '')
    {
        _deprecated_function(__FUNCTION__, '3.4.0', 'add_theme_support( \'custom-background\', $args )');
        $args = [];
        if($wp_head_callback)
        {
            $args['wp-head-callback'] = $wp_head_callback;
        }
        if($admin_head_callback)
        {
            $args['admin-head-callback'] = $admin_head_callback;
        }
        if($admin_preview_callback)
        {
            $args['admin-preview-callback'] = $admin_preview_callback;
        }

        return add_theme_support('custom-background', $args);
    }

    function remove_custom_background()
    {
        _deprecated_function(__FUNCTION__, '3.4.0', 'remove_theme_support( \'custom-background\' )');

        return remove_theme_support('custom-background');
    }

    function get_theme_data($theme_file)
    {
        _deprecated_function(__FUNCTION__, '3.4.0', 'wp_get_theme()');
        $theme = new WP_Theme(wp_basename(dirname($theme_file)), dirname(dirname($theme_file)));

        $theme_data = [
            'Name' => $theme->get('Name'),
            'URI' => $theme->display('ThemeURI', true, false),
            'Description' => $theme->display('Description', true, false),
            'Author' => $theme->display('Author', true, false),
            'AuthorURI' => $theme->display('AuthorURI', true, false),
            'Version' => $theme->get('Version'),
            'Template' => $theme->get('Template'),
            'Status' => $theme->get('Status'),
            'Tags' => $theme->get('Tags'),
            'Title' => $theme->get('Name'),
            'AuthorName' => $theme->get('Author'),
        ];

        foreach(apply_filters('extra_theme_headers', []) as $extra_header)
        {
            if(! isset($theme_data[$extra_header]))
            {
                $theme_data[$extra_header] = $theme->get($extra_header);
            }
        }

        return $theme_data;
    }

    function update_page_cache(&$pages)
    {
        _deprecated_function(__FUNCTION__, '3.4.0', 'update_post_cache()');

        update_post_cache($pages);
    }

    function clean_page_cache($id)
    {
        _deprecated_function(__FUNCTION__, '3.4.0', 'clean_post_cache()');

        clean_post_cache($id);
    }

    function wp_explain_nonce($action)
    {
        _deprecated_function(__FUNCTION__, '3.4.1', 'wp_nonce_ays()');

        return __('Are you sure you want to do this?');
    }

    function sticky_class($post_id = null)
    {
        _deprecated_function(__FUNCTION__, '3.5.0', 'post_class()');
        if(is_sticky($post_id))
        {
            echo ' sticky';
        }
    }

    function _get_post_ancestors(&$post)
    {
        _deprecated_function(__FUNCTION__, '3.5.0');
    }

    function wp_load_image($file)
    {
        _deprecated_function(__FUNCTION__, '3.5.0', 'wp_get_image_editor()');

        if(is_numeric($file))
        {
            $file = get_attached_file($file);
        }

        if(! is_file($file))
        {
            /* translators: %s: File name. */
            return sprintf(__('File &#8220;%s&#8221; does not exist?'), $file);
        }

        if(! function_exists('imagecreatefromstring'))
        {
            return __('The GD image library is not installed.');
        }

        // Set artificially high because GD uses uncompressed images in memory.
        wp_raise_memory_limit('image');

        $image = imagecreatefromstring(file_get_contents($file));

        if(! is_gd_image($image))
        {
            /* translators: %s: File name. */
            return sprintf(__('File &#8220;%s&#8221; is not an image.'), $file);
        }

        return $image;
    }

    function image_resize($file, $max_w, $max_h, $crop = false, $suffix = null, $dest_path = null, $jpeg_quality = 90)
    {
        _deprecated_function(__FUNCTION__, '3.5.0', 'wp_get_image_editor()');

        $editor = wp_get_image_editor($file);
        if(is_wp_error($editor))
        {
            return $editor;
        }
        $editor->set_quality($jpeg_quality);

        $resized = $editor->resize($max_w, $max_h, $crop);
        if(is_wp_error($resized))
        {
            return $resized;
        }

        $dest_file = $editor->generate_filename($suffix, $dest_path);
        $saved = $editor->save($dest_file);

        if(is_wp_error($saved))
        {
            return $saved;
        }

        return $dest_file;
    }

    function wp_get_single_post($postid = 0, $mode = OBJECT)
    {
        _deprecated_function(__FUNCTION__, '3.5.0', 'get_post()');

        return get_post($postid, $mode);
    }

    function user_pass_ok($user_login, $user_pass)
    {
        _deprecated_function(__FUNCTION__, '3.5.0', 'wp_authenticate()');
        $user = wp_authenticate($user_login, $user_pass);
        if(is_wp_error($user))
        {
            return false;
        }

        return true;
    }

    function _save_post_hook() {}

    function gd_edit_image_support($mime_type)
    {
        _deprecated_function(__FUNCTION__, '3.5.0', 'wp_image_editor_supports()');

        if(function_exists('imagetypes'))
        {
            switch($mime_type)
            {
                case 'image/jpeg':
                    return (imagetypes() & IMG_JPG) != 0;
                case 'image/png':
                    return (imagetypes() & IMG_PNG) != 0;
                case 'image/gif':
                    return (imagetypes() & IMG_GIF) != 0;
                case 'image/webp':
                    return (imagetypes() & IMG_WEBP) != 0;
            }
        }
        else
        {
            switch($mime_type)
            {
                case 'image/jpeg':
                    return function_exists('imagecreatefromjpeg');
                case 'image/png':
                    return function_exists('imagecreatefrompng');
                case 'image/gif':
                    return function_exists('imagecreatefromgif');
                case 'image/webp':
                    return function_exists('imagecreatefromwebp');
            }
        }

        return false;
    }

    function wp_convert_bytes_to_hr($bytes)
    {
        _deprecated_function(__FUNCTION__, '3.6.0', 'size_format()');

        $units = [0 => 'B', 1 => 'KB', 2 => 'MB', 3 => 'GB', 4 => 'TB'];
        $log = log($bytes, KB_IN_BYTES);
        $power = (int) $log;
        $size = KB_IN_BYTES ** ($log - $power);

        if(! is_nan($size) && array_key_exists($power, $units))
        {
            $unit = $units[$power];
        }
        else
        {
            $size = $bytes;
            $unit = $units[0];
        }

        return $size.$unit;
    }

    function _search_terms_tidy($t)
    {
        _deprecated_function(__FUNCTION__, '3.7.0');

        return trim($t, "\"'\n\r ");
    }

    function rich_edit_exists()
    {
        global $wp_rich_edit_exists;
        _deprecated_function(__FUNCTION__, '3.9.0');

        if(! isset($wp_rich_edit_exists))
        {
            $wp_rich_edit_exists = file_exists(ABSPATH.WPINC.'/js/tinymce/tinymce.js');
        }

        return $wp_rich_edit_exists;
    }

    function default_topic_count_text($count)
    {
        return $count;
    }

    function format_to_post($content)
    {
        _deprecated_function(__FUNCTION__, '3.9.0');

        return $content;
    }

    function like_escape($text)
    {
        _deprecated_function(__FUNCTION__, '4.0.0', 'wpdb::esc_like()');

        return str_replace(["%", "_"], ["\\%", "\\_"], $text);
    }

    function url_is_accessable_via_ssl($url)
    {
        _deprecated_function(__FUNCTION__, '4.0.0');

        $response = wp_remote_get(set_url_scheme($url, 'https'));

        if(! is_wp_error($response))
        {
            $status = wp_remote_retrieve_response_code($response);
            if(200 == $status || 401 == $status)
            {
                return true;
            }
        }

        return false;
    }

    function preview_theme()
    {
        _deprecated_function(__FUNCTION__, '4.3.0');
    }

    function _preview_theme_template_filter()
    {
        _deprecated_function(__FUNCTION__, '4.3.0');

        return '';
    }

    function _preview_theme_stylesheet_filter()
    {
        _deprecated_function(__FUNCTION__, '4.3.0');

        return '';
    }

    function preview_theme_ob_filter($content)
    {
        _deprecated_function(__FUNCTION__, '4.3.0');

        return $content;
    }

    function preview_theme_ob_filter_callback($matches)
    {
        _deprecated_function(__FUNCTION__, '4.3.0');

        return '';
    }

    function wp_richedit_pre($text)
    {
        _deprecated_function(__FUNCTION__, '4.3.0', 'format_for_editor()');

        if(empty($text))
        {
            return apply_filters('richedit_pre', '');
        }

        $output = convert_chars($text);
        $output = wpautop($output);
        $output = htmlspecialchars($output, ENT_NOQUOTES, get_option('blog_charset'));

        return apply_filters('richedit_pre', $output);
    }

    function wp_htmledit_pre($output)
    {
        _deprecated_function(__FUNCTION__, '4.3.0', 'format_for_editor()');

        if(! empty($output))
        {
            $output = htmlspecialchars($output, ENT_NOQUOTES, get_option('blog_charset'));
        } // Convert only '< > &'.

        return apply_filters('htmledit_pre', $output);
    }

    function post_permalink($post = 0)
    {
        _deprecated_function(__FUNCTION__, '4.4.0', 'get_permalink()');

        return get_permalink($post);
    }

    function wp_get_http($url, $file_path = false, $red = 1)
    {
        _deprecated_function(__FUNCTION__, '4.4.0', 'WP_Http');

        if(function_exists('set_time_limit'))
        {
            @set_time_limit(60);
        }

        if($red > 5)
        {
            return false;
        }

        $options = [];
        $options['redirection'] = 5;

        if(false == $file_path)
        {
            $options['method'] = 'HEAD';
        }
        else
        {
            $options['method'] = 'GET';
        }

        $response = wp_safe_remote_request($url, $options);

        if(is_wp_error($response))
        {
            return false;
        }

        $headers = wp_remote_retrieve_headers($response);
        $headers['response'] = wp_remote_retrieve_response_code($response);

        // WP_HTTP no longer follows redirects for HEAD requests.
        if('HEAD' == $options['method'] && in_array($headers['response'], [301, 302]) && isset($headers['location']))
        {
            return wp_get_http($headers['location'], $file_path, ++$red);
        }

        if(false == $file_path)
        {
            return $headers;
        }

        // GET request - write it to the supplied filename.
        $out_fp = fopen($file_path, 'w');
        if(! $out_fp)
        {
            return $headers;
        }

        fwrite($out_fp, wp_remote_retrieve_body($response));
        fclose($out_fp);
        clearstatcache();

        return $headers;
    }

    function force_ssl_login($force = null)
    {
        _deprecated_function(__FUNCTION__, '4.4.0', 'force_ssl_admin()');

        return force_ssl_admin($force);
    }

    function get_comments_popup_template()
    {
        _deprecated_function(__FUNCTION__, '4.5.0');

        return '';
    }

    function is_comments_popup()
    {
        _deprecated_function(__FUNCTION__, '4.5.0');

        return false;
    }

    function comments_popup_script()
    {
        _deprecated_function(__FUNCTION__, '4.5.0');
    }

    function popuplinks($text)
    {
        _deprecated_function(__FUNCTION__, '4.5.0');
        $text = preg_replace('/<a (.+?)>/i', "<a $1 target='_blank' rel='external'>", $text);

        return $text;
    }

    function wp_embed_handler_googlevideo($matches, $attr, $url, $rawattr)
    {
        _deprecated_function(__FUNCTION__, '4.6.0');

        return '';
    }

    function get_paged_template()
    {
        _deprecated_function(__FUNCTION__, '4.7.0');

        return get_query_template('paged');
    }

    function wp_kses_js_entities($content)
    {
        _deprecated_function(__FUNCTION__, '4.7.0');

        return preg_replace('%&\s*\{[^}]*(\}\s*;?|$)%', '', $content);
    }

    function _usort_terms_by_ID($a, $b)
    {
        _deprecated_function(__FUNCTION__, '4.7.0', 'wp_list_sort()');

        if($a->term_id > $b->term_id)
        {
            return 1;
        }
        elseif($a->term_id < $b->term_id)
        {
            return -1;
        }
        else
        {
            return 0;
        }
    }

    function _usort_terms_by_name($a, $b)
    {
        _deprecated_function(__FUNCTION__, '4.7.0', 'wp_list_sort()');

        return strcmp($a->name, $b->name);
    }

    function _sort_nav_menu_items($a, $b)
    {
        global $_menu_item_sort_prop;

        _deprecated_function(__FUNCTION__, '4.7.0', 'wp_list_sort()');

        if(empty($_menu_item_sort_prop) || ! isset($a->$_menu_item_sort_prop) || ! isset($b->$_menu_item_sort_prop))
        {
            return 0;
        }

        $_a = (int) $a->$_menu_item_sort_prop;
        $_b = (int) $b->$_menu_item_sort_prop;

        if($a->$_menu_item_sort_prop == $b->$_menu_item_sort_prop)
        {
            return 0;
        }
        elseif($_a == $a->$_menu_item_sort_prop && $_b == $b->$_menu_item_sort_prop)
        {
            if($_a < $_b)
            {
                return -1;
            }

            return 1;
        }
        else
        {
            return strcmp($a->$_menu_item_sort_prop, $b->$_menu_item_sort_prop);
        }
    }

    function get_shortcut_link()
    {
        _deprecated_function(__FUNCTION__, '4.9.0');

        $link = '';

        return apply_filters('shortcut_link', $link);
    }

    function wp_ajax_press_this_save_post()
    {
        _deprecated_function(__FUNCTION__, '4.9.0');
        if(is_plugin_active('press-this/press-this-plugin.php'))
        {
            include WP_PLUGIN_DIR.'/press-this/class-wp-press-this-plugin.php';
            $wp_press_this = new WP_Press_This_Plugin();
            $wp_press_this->save_post();
        }
        else
        {
            wp_send_json_error(['errorMessage' => __('The Press This plugin is required.')]);
        }
    }

    function wp_ajax_press_this_add_category()
    {
        _deprecated_function(__FUNCTION__, '4.9.0');
        if(is_plugin_active('press-this/press-this-plugin.php'))
        {
            include WP_PLUGIN_DIR.'/press-this/class-wp-press-this-plugin.php';
            $wp_press_this = new WP_Press_This_Plugin();
            $wp_press_this->add_category();
        }
        else
        {
            wp_send_json_error(['errorMessage' => __('The Press This plugin is required.')]);
        }
    }

    function wp_get_user_request_data($request_id)
    {
        _deprecated_function(__FUNCTION__, '5.4.0', 'wp_get_user_request()');

        return wp_get_user_request($request_id);
    }

    function wp_make_content_images_responsive($content)
    {
        _deprecated_function(__FUNCTION__, '5.5.0', 'wp_filter_content_tags()');

        // This will also add the `loading` attribute to `img` tags, if enabled.
        return wp_filter_content_tags($content);
    }

    function wp_unregister_GLOBALS()
    {  // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
        // register_globals was deprecated in PHP 5.3 and removed entirely in PHP 5.4.
        _deprecated_function(__FUNCTION__, '5.5.0');
    }

    function wp_blacklist_check($author, $email, $url, $comment, $user_ip, $user_agent)
    {
        _deprecated_function(__FUNCTION__, '5.5.0', 'wp_check_comment_disallowed_list()');

        return wp_check_comment_disallowed_list($author, $email, $url, $comment, $user_ip, $user_agent);
    }

    function _wp_register_meta_args_whitelist($args, $default_args)
    {
        _deprecated_function(__FUNCTION__, '5.5.0', '_wp_register_meta_args_allowed_list()');

        return _wp_register_meta_args_allowed_list($args, $default_args);
    }

    function add_option_whitelist($new_options, $options = '')
    {
        _deprecated_function(__FUNCTION__, '5.5.0', 'add_allowed_options()');

        return add_allowed_options($new_options, $options);
    }

    function remove_option_whitelist($del_options, $options = '')
    {
        _deprecated_function(__FUNCTION__, '5.5.0', 'remove_allowed_options()');

        return remove_allowed_options($del_options, $options);
    }

    function wp_slash_strings_only($value)
    {
        return map_deep($value, 'addslashes_strings_only');
    }

    function addslashes_strings_only($value)
    {
        if(is_string($value))
        {
            return addslashes($value);
        }

        return $value;
    }

    function noindex()
    {
        _deprecated_function(__FUNCTION__, '5.7.0', 'wp_robots_noindex()');

        // If the blog is not public, tell robots to go away.
        if('0' == get_option('blog_public'))
        {
            wp_no_robots();
        }
    }

    function wp_no_robots()
    {
        _deprecated_function(__FUNCTION__, '5.7.0', 'wp_robots_no_robots()');

        if(get_option('blog_public'))
        {
            echo "<meta name='robots' content='noindex,follow' />\n";

            return;
        }

        echo "<meta name='robots' content='noindex,nofollow' />\n";
    }

    function wp_sensitive_page_meta()
    {
        _deprecated_function(__FUNCTION__, '5.7.0', 'wp_robots_sensitive_page()');

        ?>
        <meta name='robots' content='noindex,noarchive'/>
        <?php
        wp_strict_cross_origin_referrer();
    }

    function _excerpt_render_inner_columns_blocks($columns, $allowed_blocks)
    {
        _deprecated_function(__FUNCTION__, '5.8.0', '_excerpt_render_inner_blocks()');

        return _excerpt_render_inner_blocks($columns, $allowed_blocks);
    }

    function wp_render_duotone_filter_preset($preset)
    {
        _deprecated_function(__FUNCTION__, '5.9.1', 'wp_get_duotone_filter_property()');

        return wp_get_duotone_filter_property($preset);
    }

    function wp_skip_border_serialization($block_type)
    {
        _deprecated_function(__FUNCTION__, '6.0.0', 'wp_should_skip_block_supports_serialization()');

        $border_support = _wp_array_get($block_type->supports, ['__experimentalBorder'], false);

        return is_array($border_support) && array_key_exists('__experimentalSkipSerialization', $border_support) && $border_support['__experimentalSkipSerialization'];
    }

    function wp_skip_dimensions_serialization($block_type)
    {
        _deprecated_function(__FUNCTION__, '6.0.0', 'wp_should_skip_block_supports_serialization()');

        $dimensions_support = _wp_array_get($block_type->supports, ['__experimentalDimensions'], false);

        return is_array($dimensions_support) && array_key_exists('__experimentalSkipSerialization', $dimensions_support) && $dimensions_support['__experimentalSkipSerialization'];
    }

    function wp_skip_spacing_serialization($block_type)
    {
        _deprecated_function(__FUNCTION__, '6.0.0', 'wp_should_skip_block_supports_serialization()');

        $spacing_support = _wp_array_get($block_type->supports, ['spacing'], false);

        return is_array($spacing_support) && array_key_exists('__experimentalSkipSerialization', $spacing_support) && $spacing_support['__experimentalSkipSerialization'];
    }

    function wp_add_iframed_editor_assets_html()
    {
        _deprecated_function(__FUNCTION__, '6.0.0');
    }

    function wp_get_attachment_thumb_file($post_id = 0)
    {
        _deprecated_function(__FUNCTION__, '6.1.0');

        $post_id = (int) $post_id;
        $post = get_post($post_id);

        if(! $post)
        {
            return false;
        }

        // Use $post->ID rather than $post_id as get_post() may have used the global $post object.
        $imagedata = wp_get_attachment_metadata($post->ID);

        if(! is_array($imagedata))
        {
            return false;
        }

        $file = get_attached_file($post->ID);

        if(! empty($imagedata['thumb']))
        {
            $thumbfile = str_replace(wp_basename($file), $imagedata['thumb'], $file);
            if(file_exists($thumbfile))
            {
                return apply_filters('wp_get_attachment_thumb_file', $thumbfile, $post->ID);
            }
        }

        return false;
    }

    function _get_path_to_translation($domain, $reset = false)
    {
        _deprecated_function(__FUNCTION__, '6.1.0', 'WP_Textdomain_Registry');

        static $available_translations = [];

        if(true === $reset)
        {
            $available_translations = [];
        }

        if(! isset($available_translations[$domain]))
        {
            $available_translations[$domain] = _get_path_to_translation_from_lang_dir($domain);
        }

        return $available_translations[$domain];
    }

    function _get_path_to_translation_from_lang_dir($domain)
    {
        _deprecated_function(__FUNCTION__, '6.1.0', 'WP_Textdomain_Registry');

        static $cached_mofiles = null;

        if(null === $cached_mofiles)
        {
            $cached_mofiles = [];

            $locations = [
                WP_LANG_DIR.'/plugins',
                WP_LANG_DIR.'/themes',
            ];

            foreach($locations as $location)
            {
                $mofiles = glob($location.'/*.mo');
                if($mofiles)
                {
                    $cached_mofiles = array_merge($cached_mofiles, $mofiles);
                }
            }
        }

        $locale = determine_locale();
        $mofile = "{$domain}-{$locale}.mo";

        $path = WP_LANG_DIR.'/plugins/'.$mofile;
        if(in_array($path, $cached_mofiles, true))
        {
            return $path;
        }

        $path = WP_LANG_DIR.'/themes/'.$mofile;
        if(in_array($path, $cached_mofiles, true))
        {
            return $path;
        }

        return false;
    }

    function _wp_multiple_block_styles($metadata)
    {
        _deprecated_function(__FUNCTION__, '6.1.0');

        return $metadata;
    }

    function wp_typography_get_css_variable_inline_style($attributes, $feature, $css_property)
    {
        _deprecated_function(__FUNCTION__, '6.1.0', 'wp_style_engine_get_styles()');

        // Retrieve current attribute value or skip if not found.
        $style_value = _wp_array_get($attributes, ['style', 'typography', $feature], false);
        if(! $style_value)
        {
            return;
        }

        // If we don't have a preset CSS variable, we'll assume it's a regular CSS value.
        if(! str_contains($style_value, "var:preset|{$css_property}|"))
        {
            return sprintf('%s:%s;', $css_property, $style_value);
        }

        /*
	 * We have a preset CSS variable as the style.
	 * Get the style value from the string and return CSS style.
	 */
        $index_to_splice = strrpos($style_value, '|') + 1;
        $slug = substr($style_value, $index_to_splice);

        // Return the actual CSS inline style e.g. `text-decoration:var(--wp--preset--text-decoration--underline);`.
        return sprintf('%s:var(--wp--preset--%s--%s);', $css_property, $css_property, $slug);
    }

    function global_terms_enabled()
    {
        _deprecated_function(__FUNCTION__, '6.1.0');

        return false;
    }

    function _filter_query_attachment_filenames($clauses)
    {
        _deprecated_function(__FUNCTION__, '6.0.3', 'add_filter( "wp_allow_query_attachment_by_filename", "__return_true" )');
        remove_filter('posts_clauses', __FUNCTION__);

        return $clauses;
    }

    function get_page_by_title($page_title, $output = OBJECT, $post_type = 'page')
    {
        _deprecated_function(__FUNCTION__, '6.2.0', 'WP_Query');
        global $wpdb;

        if(is_array($post_type))
        {
            $post_type = esc_sql($post_type);
            $post_type_in_string = "'".implode("','", $post_type)."'";
            $sql = $wpdb->prepare(
                "SELECT ID
			FROM $wpdb->posts
			WHERE post_title = %s
			AND post_type IN ($post_type_in_string)", $page_title
            );
        }
        else
        {
            $sql = $wpdb->prepare(
                "SELECT ID
			FROM $wpdb->posts
			WHERE post_title = %s
			AND post_type = %s", $page_title, $post_type
            );
        }

        $page = $wpdb->get_var($sql);

        if($page)
        {
            return get_post($page, $output);
        }

        return null;
    }

    function _resolve_home_block_template()
    {
        _deprecated_function(__FUNCTION__, '6.2.0');

        $show_on_front = get_option('show_on_front');
        $front_page_id = get_option('page_on_front');

        if('page' === $show_on_front && $front_page_id)
        {
            return [
                'postType' => 'page',
                'postId' => $front_page_id,
            ];
        }

        $hierarchy = ['front-page', 'home', 'index'];
        $template = resolve_block_template('home', $hierarchy, '');

        if(! $template)
        {
            return null;
        }

        return [
            'postType' => 'wp_template',
            'postId' => $template->id,
        ];
    }

    function wlwmanifest_link()
    {
        _deprecated_function(__FUNCTION__, '6.3.0');
    }

    function wp_queue_comments_for_comment_meta_lazyload($comments)
    {
        _deprecated_function(__FUNCTION__, '6.3.0', 'wp_lazyload_comment_meta()');
        // Don't use `wp_list_pluck()` to avoid by-reference manipulation.
        $comment_ids = [];
        if(is_array($comments))
        {
            foreach($comments as $comment)
            {
                if($comment instanceof WP_Comment)
                {
                    $comment_ids[] = $comment->comment_ID;
                }
            }
        }

        wp_lazyload_comment_meta($comment_ids);
    }

    function wp_get_loading_attr_default($context)
    {
        _deprecated_function(__FUNCTION__, '6.3.0', 'wp_get_loading_optimization_attributes()');
        global $wp_query;

        // Skip lazy-loading for the overall block template, as it is handled more granularly.
        if('template' === $context)
        {
            return false;
        }

        /*
	 * Do not lazy-load images in the header block template part, as they are likely above the fold.
	 * For classic themes, this is handled in the condition below using the 'get_header' action.
	 */
        $header_area = WP_TEMPLATE_PART_AREA_HEADER;
        if("template_part_{$header_area}" === $context)
        {
            return false;
        }

        // Special handling for programmatically created image tags.
        if('the_post_thumbnail' === $context || 'wp_get_attachment_image' === $context)
        {
            /*
		 * Skip programmatically created images within post content as they need to be handled together with the other
		 * images within the post content.
		 * Without this clause, they would already be counted below which skews the number and can result in the first
		 * post content image being lazy-loaded only because there are images elsewhere in the post content.
		 */
            if(doing_filter('the_content'))
            {
                return false;
            }

            // Conditionally skip lazy-loading on images before the loop.
            if(// Only apply for main query but before the loop.
                $wp_query->before_loop && $wp_query->is_main_query() /*
			 * Any image before the loop, but after the header has started should not be lazy-loaded,
			 * except when the footer has already started which can happen when the current template
			 * does not include any loop.
			 */ && did_action('get_header') && ! did_action('get_footer')
            )
            {
                return false;
            }
        }

        /*
	 * The first elements in 'the_content' or 'the_post_thumbnail' should not be lazy-loaded,
	 * as they are likely above the fold.
	 */
        if('the_content' === $context || 'the_post_thumbnail' === $context)
        {
            // Only elements within the main query loop have special handling.
            if(is_admin() || ! in_the_loop() || ! is_main_query())
            {
                return 'lazy';
            }

            // Increase the counter since this is a main query content element.
            $content_media_count = wp_increase_content_media_count();

            // If the count so far is below the threshold, return `false` so that the `loading` attribute is omitted.
            if($content_media_count <= wp_omit_loading_attr_threshold())
            {
                return false;
            }

            // For elements after the threshold, lazy-load them as usual.
        }

        // Lazy-load by default for any unknown context.
        return 'lazy';
    }

    function wp_img_tag_add_loading_attr($image, $context)
    {
        _deprecated_function(__FUNCTION__, '6.3.0', 'wp_img_tag_add_loading_optimization_attrs()');
        /*
	 * Get loading attribute value to use. This must occur before the conditional check below so that even images that
	 * are ineligible for being lazy-loaded are considered.
	 */
        $value = wp_get_loading_attr_default($context);

        // Images should have source and dimension attributes for the `loading` attribute to be added.
        if(! str_contains($image, ' src="') || ! str_contains($image, ' width="') || ! str_contains($image, ' height="'))
        {
            return $image;
        }

        $value = apply_filters('wp_img_tag_add_loading_attr', $value, $image, $context);

        if($value)
        {
            if(! in_array($value, ['lazy', 'eager'], true))
            {
                $value = 'lazy';
            }

            return str_replace('<img', '<img loading="'.esc_attr($value).'"', $image);
        }

        return $image;
    }

    function wp_tinycolor_bound01($n, $max)
    {
        _deprecated_function(__FUNCTION__, '6.3.0');
        if(is_string($n) && str_contains($n, '.') && 1 === (float) $n)
        {
            $n = '100%';
        }

        $n = min($max, max(0, (float) $n));

        // Automatically convert percentage into number.
        if(is_string($n) && str_contains($n, '%'))
        {
            $n = (int) ($n * $max) / 100;
        }

        // Handle floating point rounding errors.
        if((abs($n - $max) < 0.000001))
        {
            return 1.0;
        }

        // Convert into [0, 1] range if it isn't already.
        return ($n % $max) / (float) $max;
    }

    function _wp_tinycolor_bound_alpha($n)
    {
        _deprecated_function(__FUNCTION__, '6.3.0');

        if(is_numeric($n))
        {
            $n = (float) $n;
            if($n >= 0 && $n <= 1)
            {
                return $n;
            }
        }

        return 1;
    }

    function wp_tinycolor_rgb_to_rgb($rgb_color)
    {
        _deprecated_function(__FUNCTION__, '6.3.0');

        return [
            'r' => wp_tinycolor_bound01($rgb_color['r'], 255) * 255,
            'g' => wp_tinycolor_bound01($rgb_color['g'], 255) * 255,
            'b' => wp_tinycolor_bound01($rgb_color['b'], 255) * 255,
        ];
    }

    function wp_tinycolor_hue_to_rgb($p, $q, $t)
    {
        _deprecated_function(__FUNCTION__, '6.3.0');

        if($t < 0)
        {
            ++$t;
        }
        if($t > 1)
        {
            --$t;
        }
        if($t < 1 / 6)
        {
            return $p + ($q - $p) * 6 * $t;
        }
        if($t < 1 / 2)
        {
            return $q;
        }
        if($t < 2 / 3)
        {
            return $p + ($q - $p) * (2 / 3 - $t) * 6;
        }

        return $p;
    }

    function wp_tinycolor_hsl_to_rgb($hsl_color)
    {
        _deprecated_function(__FUNCTION__, '6.3.0');

        $h = wp_tinycolor_bound01($hsl_color['h'], 360);
        $s = wp_tinycolor_bound01($hsl_color['s'], 100);
        $l = wp_tinycolor_bound01($hsl_color['l'], 100);

        if(0 === $s)
        {
            // Achromatic.
            $r = $l;
            $g = $l;
            $b = $l;
        }
        else
        {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = wp_tinycolor_hue_to_rgb($p, $q, $h + 1 / 3);
            $g = wp_tinycolor_hue_to_rgb($p, $q, $h);
            $b = wp_tinycolor_hue_to_rgb($p, $q, $h - 1 / 3);
        }

        return [
            'r' => $r * 255,
            'g' => $g * 255,
            'b' => $b * 255,
        ];
    }

    function wp_tinycolor_string_to_rgb($color_str)
    {
        _deprecated_function(__FUNCTION__, '6.3.0');

        $color_str = strtolower(trim($color_str));

        $css_integer = '[-\\+]?\\d+%?';
        $css_number = '[-\\+]?\\d*\\.\\d+%?';

        $css_unit = '(?:'.$css_number.')|(?:'.$css_integer.')';

        $permissive_match3 = '[\\s|\\(]+('.$css_unit.')[,|\\s]+('.$css_unit.')[,|\\s]+('.$css_unit.')\\s*\\)?';
        $permissive_match4 = '[\\s|\\(]+('.$css_unit.')[,|\\s]+('.$css_unit.')[,|\\s]+('.$css_unit.')[,|\\s]+('.$css_unit.')\\s*\\)?';

        $rgb_regexp = '/^rgb'.$permissive_match3.'$/';
        if(preg_match($rgb_regexp, $color_str, $match))
        {
            $rgb = wp_tinycolor_rgb_to_rgb([
                                               'r' => $match[1],
                                               'g' => $match[2],
                                               'b' => $match[3],
                                           ]);

            $rgb['a'] = 1;

            return $rgb;
        }

        $rgba_regexp = '/^rgba'.$permissive_match4.'$/';
        if(preg_match($rgba_regexp, $color_str, $match))
        {
            $rgb = wp_tinycolor_rgb_to_rgb([
                                               'r' => $match[1],
                                               'g' => $match[2],
                                               'b' => $match[3],
                                           ]);

            $rgb['a'] = _wp_tinycolor_bound_alpha($match[4]);

            return $rgb;
        }

        $hsl_regexp = '/^hsl'.$permissive_match3.'$/';
        if(preg_match($hsl_regexp, $color_str, $match))
        {
            $rgb = wp_tinycolor_hsl_to_rgb([
                                               'h' => $match[1],
                                               's' => $match[2],
                                               'l' => $match[3],
                                           ]);

            $rgb['a'] = 1;

            return $rgb;
        }

        $hsla_regexp = '/^hsla'.$permissive_match4.'$/';
        if(preg_match($hsla_regexp, $color_str, $match))
        {
            $rgb = wp_tinycolor_hsl_to_rgb([
                                               'h' => $match[1],
                                               's' => $match[2],
                                               'l' => $match[3],
                                           ]);

            $rgb['a'] = _wp_tinycolor_bound_alpha($match[4]);

            return $rgb;
        }

        $hex8_regexp = '/^#?([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/';
        if(preg_match($hex8_regexp, $color_str, $match))
        {
            $rgb = wp_tinycolor_rgb_to_rgb([
                                               'r' => base_convert($match[1], 16, 10),
                                               'g' => base_convert($match[2], 16, 10),
                                               'b' => base_convert($match[3], 16, 10),
                                           ]);

            $rgb['a'] = _wp_tinycolor_bound_alpha(base_convert($match[4], 16, 10) / 255);

            return $rgb;
        }

        $hex6_regexp = '/^#?([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/';
        if(preg_match($hex6_regexp, $color_str, $match))
        {
            $rgb = wp_tinycolor_rgb_to_rgb([
                                               'r' => base_convert($match[1], 16, 10),
                                               'g' => base_convert($match[2], 16, 10),
                                               'b' => base_convert($match[3], 16, 10),
                                           ]);

            $rgb['a'] = 1;

            return $rgb;
        }

        $hex4_regexp = '/^#?([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})$/';
        if(preg_match($hex4_regexp, $color_str, $match))
        {
            $rgb = wp_tinycolor_rgb_to_rgb([
                                               'r' => base_convert($match[1].$match[1], 16, 10),
                                               'g' => base_convert($match[2].$match[2], 16, 10),
                                               'b' => base_convert($match[3].$match[3], 16, 10),
                                           ]);

            $rgb['a'] = _wp_tinycolor_bound_alpha(base_convert($match[4].$match[4], 16, 10) / 255);

            return $rgb;
        }

        $hex3_regexp = '/^#?([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})$/';
        if(preg_match($hex3_regexp, $color_str, $match))
        {
            $rgb = wp_tinycolor_rgb_to_rgb([
                                               'r' => base_convert($match[1].$match[1], 16, 10),
                                               'g' => base_convert($match[2].$match[2], 16, 10),
                                               'b' => base_convert($match[3].$match[3], 16, 10),
                                           ]);

            $rgb['a'] = 1;

            return $rgb;
        }

        /*
	 * The JS color picker considers the string "transparent" to be a hex value,
	 * so we need to handle it here as a special case.
	 */
        if('transparent' === $color_str)
        {
            return [
                'r' => 0,
                'g' => 0,
                'b' => 0,
                'a' => 0,
            ];
        }
    }

    function wp_get_duotone_filter_id($preset)
    {
        _deprecated_function(__FUNCTION__, '6.3.0');

        return WP_Duotone::get_filter_id_from_preset($preset);
    }

    function wp_get_duotone_filter_property($preset)
    {
        _deprecated_function(__FUNCTION__, '6.3.0');

        return WP_Duotone::get_filter_css_property_value_from_preset($preset);
    }

    function wp_get_duotone_filter_svg($preset)
    {
        _deprecated_function(__FUNCTION__, '6.3.0');

        return WP_Duotone::get_filter_svg_from_preset($preset);
    }

    function wp_register_duotone_support($block_type)
    {
        _deprecated_function(__FUNCTION__, '6.3.0', 'WP_Duotone::register_duotone_support()');

        return WP_Duotone::register_duotone_support($block_type);
    }

    function wp_render_duotone_support($block_content, $block)
    {
        _deprecated_function(__FUNCTION__, '6.3.0', 'WP_Duotone::render_duotone_support()');
        $wp_block = new WP_Block($block);

        return WP_Duotone::render_duotone_support($block_content, $block, $wp_block);
    }

    function wp_get_global_styles_svg_filters()
    {
        _deprecated_function(__FUNCTION__, '6.3.0');

        /*
	 * Ignore cache when the development mode is set to 'theme', so it doesn't interfere with the theme
	 * developer's workflow.
	 */
        $can_use_cached = ! wp_is_development_mode('theme');
        $cache_group = 'theme_json';
        $cache_key = 'wp_get_global_styles_svg_filters';
        if($can_use_cached)
        {
            $cached = wp_cache_get($cache_key, $cache_group);
            if($cached)
            {
                return $cached;
            }
        }

        $supports_theme_json = wp_theme_has_theme_json();

        $origins = ['default', 'theme', 'custom'];
        if(! $supports_theme_json)
        {
            $origins = ['default'];
        }

        $tree = WP_Theme_JSON_Resolver::get_merged_data();
        $svgs = $tree->get_svg_filters($origins);

        if($can_use_cached)
        {
            wp_cache_set($cache_key, $svgs, $cache_group);
        }

        return $svgs;
    }

    function wp_global_styles_render_svg_filters()
    {
        _deprecated_function(__FUNCTION__, '6.3.0');

        /*
	 * When calling via the in_admin_header action, we only want to render the
	 * SVGs on block editor pages.
	 */
        if(is_admin() && ! get_current_screen()->is_block_editor())
        {
            return;
        }

        $filters = wp_get_global_styles_svg_filters();
        if(! empty($filters))
        {
            echo $filters;
        }
    }

    function block_core_navigation_submenu_build_css_colors($context, $attributes, $is_sub_menu = false)
    {
        _deprecated_function(__FUNCTION__, '6.3.0');
        $colors = [
            'css_classes' => [],
            'inline_styles' => '',
        ];

        // Text color.
        $named_text_color = null;
        $custom_text_color = null;

        if($is_sub_menu && array_key_exists('customOverlayTextColor', $context))
        {
            $custom_text_color = $context['customOverlayTextColor'];
        }
        elseif($is_sub_menu && array_key_exists('overlayTextColor', $context))
        {
            $named_text_color = $context['overlayTextColor'];
        }
        elseif(array_key_exists('customTextColor', $context))
        {
            $custom_text_color = $context['customTextColor'];
        }
        elseif(array_key_exists('textColor', $context))
        {
            $named_text_color = $context['textColor'];
        }
        elseif(isset($context['style']['color']['text']))
        {
            $custom_text_color = $context['style']['color']['text'];
        }

        // If has text color.
        if(! is_null($named_text_color))
        {
            // Add the color class.
            array_push($colors['css_classes'], 'has-text-color', sprintf('has-%s-color', $named_text_color));
        }
        elseif(! is_null($custom_text_color))
        {
            // Add the custom color inline style.
            $colors['css_classes'][] = 'has-text-color';
            $colors['inline_styles'] .= sprintf('color: %s;', $custom_text_color);
        }

        // Background color.
        $named_background_color = null;
        $custom_background_color = null;

        if($is_sub_menu && array_key_exists('customOverlayBackgroundColor', $context))
        {
            $custom_background_color = $context['customOverlayBackgroundColor'];
        }
        elseif($is_sub_menu && array_key_exists('overlayBackgroundColor', $context))
        {
            $named_background_color = $context['overlayBackgroundColor'];
        }
        elseif(array_key_exists('customBackgroundColor', $context))
        {
            $custom_background_color = $context['customBackgroundColor'];
        }
        elseif(array_key_exists('backgroundColor', $context))
        {
            $named_background_color = $context['backgroundColor'];
        }
        elseif(isset($context['style']['color']['background']))
        {
            $custom_background_color = $context['style']['color']['background'];
        }

        // If has background color.
        if(! is_null($named_background_color))
        {
            // Add the background-color class.
            array_push($colors['css_classes'], 'has-background', sprintf('has-%s-background-color', $named_background_color));
        }
        elseif(! is_null($custom_background_color))
        {
            // Add the custom background-color inline style.
            $colors['css_classes'][] = 'has-background';
            $colors['inline_styles'] .= sprintf('background-color: %s;', $custom_background_color);
        }

        return $colors;
    }

    function _wp_theme_json_webfonts_handler()
    {
        _deprecated_function(__FUNCTION__, '6.4.0', 'wp_print_font_faces');

        // Block themes are unavailable during installation.
        if(wp_installing() || ! wp_theme_has_theme_json())
        {
            return;
        }

        // Webfonts to be processed.
        $registered_webfonts = [];

        $fn_get_webfonts_from_theme_json = static function()
        {
            // Get settings from theme.json.
            $settings = WP_Theme_JSON_Resolver::get_merged_data()->get_settings();

            // If in the editor, add webfonts defined in variations.
            if(is_admin() || (defined('REST_REQUEST') && REST_REQUEST))
            {
                $variations = WP_Theme_JSON_Resolver::get_style_variations();
                foreach($variations as $variation)
                {
                    // Skip if fontFamilies are not defined in the variation.
                    if(empty($variation['settings']['typography']['fontFamilies']))
                    {
                        continue;
                    }

                    // Initialize the array structure.
                    if(empty($settings['typography']))
                    {
                        $settings['typography'] = [];
                    }
                    if(empty($settings['typography']['fontFamilies']))
                    {
                        $settings['typography']['fontFamilies'] = [];
                    }
                    if(empty($settings['typography']['fontFamilies']['theme']))
                    {
                        $settings['typography']['fontFamilies']['theme'] = [];
                    }

                    // Combine variations with settings. Remove duplicates.
                    $settings['typography']['fontFamilies']['theme'] = array_merge($settings['typography']['fontFamilies']['theme'], $variation['settings']['typography']['fontFamilies']['theme']);
                    $settings['typography']['fontFamilies'] = array_unique($settings['typography']['fontFamilies']);
                }
            }

            // Bail out early if there are no settings for webfonts.
            if(empty($settings['typography']['fontFamilies']))
            {
                return [];
            }

            $webfonts = [];

            // Look for fontFamilies.
            foreach($settings['typography']['fontFamilies'] as $font_families)
            {
                foreach($font_families as $font_family)
                {
                    // Skip if fontFace is not defined.
                    if(empty($font_family['fontFace']))
                    {
                        continue;
                    }

                    // Skip if fontFace is not an array of webfonts.
                    if(! is_array($font_family['fontFace']))
                    {
                        continue;
                    }

                    $webfonts = array_merge($webfonts, $font_family['fontFace']);
                }
            }

            return $webfonts;
        };

        $fn_transform_src_into_uri = static function(array $src)
        {
            foreach($src as $key => $url)
            {
                // Tweak the URL to be relative to the theme root.
                if(! str_starts_with($url, 'file:./'))
                {
                    continue;
                }

                $src[$key] = get_theme_file_uri(str_replace('file:./', '', $url));
            }

            return $src;
        };

        $fn_convert_keys_to_kebab_case = static function(array $font_face)
        {
            foreach($font_face as $property => $value)
            {
                $kebab_case = _wp_to_kebab_case($property);
                $font_face[$kebab_case] = $value;
                if($kebab_case !== $property)
                {
                    unset($font_face[$property]);
                }
            }

            return $font_face;
        };

        $fn_validate_webfont = static function($webfont)
        {
            $webfont = wp_parse_args($webfont, [
                'font-family' => '',
                'font-style' => 'normal',
                'font-weight' => '400',
                'font-display' => 'fallback',
                'src' => [],
            ]);

            // Check the font-family.
            if(empty($webfont['font-family']) || ! is_string($webfont['font-family']))
            {
                trigger_error(__('Webfont font family must be a non-empty string.'));

                return false;
            }

            // Check that the `src` property is defined and a valid type.
            if(empty($webfont['src']) || (! is_string($webfont['src']) && ! is_array($webfont['src'])))
            {
                trigger_error(__('Webfont src must be a non-empty string or an array of strings.'));

                return false;
            }

            // Validate the `src` property.
            foreach((array) $webfont['src'] as $src)
            {
                if(! is_string($src) || '' === trim($src))
                {
                    trigger_error(__('Each webfont src must be a non-empty string.'));

                    return false;
                }
            }

            // Check the font-weight.
            if(! is_string($webfont['font-weight']) && ! is_int($webfont['font-weight']))
            {
                trigger_error(__('Webfont font weight must be a properly formatted string or integer.'));

                return false;
            }

            // Check the font-display.
            if(! in_array($webfont['font-display'], ['auto', 'block', 'fallback', 'optional', 'swap'], true))
            {
                $webfont['font-display'] = 'fallback';
            }

            $valid_props = [
                'ascend-override',
                'descend-override',
                'font-display',
                'font-family',
                'font-stretch',
                'font-style',
                'font-weight',
                'font-variant',
                'font-feature-settings',
                'font-variation-settings',
                'line-gap-override',
                'size-adjust',
                'src',
                'unicode-range',
            ];

            foreach($webfont as $prop => $value)
            {
                if(! in_array($prop, $valid_props, true))
                {
                    unset($webfont[$prop]);
                }
            }

            return $webfont;
        };

        $fn_register_webfonts = static function() use (
            &$registered_webfonts, $fn_get_webfonts_from_theme_json, $fn_convert_keys_to_kebab_case, $fn_validate_webfont, $fn_transform_src_into_uri
        )
        {
            $registered_webfonts = [];

            foreach($fn_get_webfonts_from_theme_json() as $webfont)
            {
                if(! is_array($webfont))
                {
                    continue;
                }

                $webfont = $fn_convert_keys_to_kebab_case($webfont);

                $webfont = $fn_validate_webfont($webfont);

                $webfont['src'] = $fn_transform_src_into_uri((array) $webfont['src']);

                // Skip if not valid.
                if(empty($webfont))
                {
                    continue;
                }

                $registered_webfonts[] = $webfont;
            }
        };

        $fn_order_src = static function(array $webfont)
        {
            $src = [];
            $src_ordered = [];

            foreach($webfont['src'] as $url)
            {
                // Add data URIs first.
                if(str_starts_with(trim($url), 'data:'))
                {
                    $src_ordered[] = [
                        'url' => $url,
                        'format' => 'data',
                    ];
                    continue;
                }
                $format = pathinfo($url, PATHINFO_EXTENSION);
                $src[$format] = $url;
            }

            // Add woff2.
            if(! empty($src['woff2']))
            {
                $src_ordered[] = [
                    'url' => sanitize_url($src['woff2']),
                    'format' => 'woff2',
                ];
            }

            // Add woff.
            if(! empty($src['woff']))
            {
                $src_ordered[] = [
                    'url' => sanitize_url($src['woff']),
                    'format' => 'woff',
                ];
            }

            // Add ttf.
            if(! empty($src['ttf']))
            {
                $src_ordered[] = [
                    'url' => sanitize_url($src['ttf']),
                    'format' => 'truetype',
                ];
            }

            // Add eot.
            if(! empty($src['eot']))
            {
                $src_ordered[] = [
                    'url' => sanitize_url($src['eot']),
                    'format' => 'embedded-opentype',
                ];
            }

            // Add otf.
            if(! empty($src['otf']))
            {
                $src_ordered[] = [
                    'url' => sanitize_url($src['otf']),
                    'format' => 'opentype',
                ];
            }
            $webfont['src'] = $src_ordered;

            return $webfont;
        };

        $fn_compile_src = static function($font_family, array $value)
        {
            $src = '';

            foreach($value as $item)
            {
                $src .= ('data' === $item['format']) ? ", url({$item['url']})" : ", url('{$item['url']}') format('{$item['format']}')";
            }

            $src = ltrim($src, ', ');

            return $src;
        };

        $fn_compile_variations = static function(array $font_variation_settings)
        {
            $variations = '';

            foreach($font_variation_settings as $key => $value)
            {
                $variations .= "$key $value";
            }

            return $variations;
        };

        $fn_build_font_face_css = static function(array $webfont) use ($fn_compile_src, $fn_compile_variations)
        {
            $css = '';

            // Wrap font-family in quotes if it contains spaces.
            if(str_contains($webfont['font-family'], ' ') && ! str_contains($webfont['font-family'], '"') && ! str_contains($webfont['font-family'], "'"))
            {
                $webfont['font-family'] = '"'.$webfont['font-family'].'"';
            }

            foreach($webfont as $key => $value)
            {
                /*
			 * Skip "provider", since it's for internal API use,
			 * and not a valid CSS property.
			 */
                if('provider' === $key)
                {
                    continue;
                }

                // Compile the "src" parameter.
                if('src' === $key)
                {
                    $value = $fn_compile_src($webfont['font-family'], $value);
                }

                // If font-variation-settings is an array, convert it to a string.
                if('font-variation-settings' === $key && is_array($value))
                {
                    $value = $fn_compile_variations($value);
                }

                if(! empty($value))
                {
                    $css .= "$key:$value;";
                }
            }

            return $css;
        };

        $fn_get_css = static function() use (&$registered_webfonts, $fn_order_src, $fn_build_font_face_css)
        {
            $css = '';

            foreach($registered_webfonts as $webfont)
            {
                // Order the webfont's `src` items to optimize for browser support.
                $webfont = $fn_order_src($webfont);

                // Build the @font-face CSS for this webfont.
                $css .= '@font-face{'.$fn_build_font_face_css($webfont).'}';
            }

            return $css;
        };

        $fn_generate_and_enqueue_styles = static function() use ($fn_get_css)
        {
            // Generate the styles.
            $styles = $fn_get_css();

            // Bail out if there are no styles to enqueue.
            if('' === $styles)
            {
                return;
            }

            // Enqueue the stylesheet.
            wp_register_style('wp-webfonts', '');
            wp_enqueue_style('wp-webfonts');

            // Add the styles to the stylesheet.
            wp_add_inline_style('wp-webfonts', $styles);
        };

        $fn_generate_and_enqueue_editor_styles = static function() use ($fn_get_css)
        {
            // Generate the styles.
            $styles = $fn_get_css();

            // Bail out if there are no styles to enqueue.
            if('' === $styles)
            {
                return;
            }

            wp_add_inline_style('wp-block-library', $styles);
        };

        add_action('wp_loaded', $fn_register_webfonts);
        add_action('wp_enqueue_scripts', $fn_generate_and_enqueue_styles);
        add_action('admin_init', $fn_generate_and_enqueue_editor_styles);
    }
