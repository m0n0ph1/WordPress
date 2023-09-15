<?php

    function _walk_bookmarks($bookmarks, $args = '')
    {
        $defaults = [
            'show_updated' => 0,
            'show_description' => 0,
            'show_images' => 1,
            'show_name' => 0,
            'before' => '<li>',
            'after' => '</li>',
            'between' => "\n",
            'show_rating' => 0,
            'link_before' => '',
            'link_after' => '',
        ];

        $parsed_args = wp_parse_args($args, $defaults);

        $output = ''; // Blank string to start with.

        foreach((array) $bookmarks as $bookmark)
        {
            if(! isset($bookmark->recently_updated))
            {
                $bookmark->recently_updated = false;
            }
            $output .= $parsed_args['before'];
            if($parsed_args['show_updated'] && $bookmark->recently_updated)
            {
                $output .= '<em>';
            }
            $the_link = '#';
            if(! empty($bookmark->link_url))
            {
                $the_link = esc_url($bookmark->link_url);
            }
            $desc = esc_attr(sanitize_bookmark_field('link_description', $bookmark->link_description, $bookmark->link_id, 'display'));
            $name = esc_attr(sanitize_bookmark_field('link_name', $bookmark->link_name, $bookmark->link_id, 'display'));
            $title = $desc;

            if($parsed_args['show_updated'])
            {
                if(! str_starts_with($bookmark->link_updated_f, '00'))
                {
                    $title .= ' (';
                    $title .= sprintf(/* translators: %s: Date and time of last update. */ __('Last updated: %s'), gmdate(get_option('links_updated_date_format'), $bookmark->link_updated_f + (get_option('gmt_offset') * HOUR_IN_SECONDS)));
                    $title .= ')';
                }
            }
            $alt = ' alt="'.$name.($parsed_args['show_description'] ? ' '.$title : '').'"';

            if('' !== $title)
            {
                $title = ' title="'.$title.'"';
            }
            $rel = $bookmark->link_rel;

            $target = $bookmark->link_target;
            if('' !== $target)
            {
                if(is_string($rel) && '' !== $rel)
                {
                    if(! str_contains($rel, 'noopener'))
                    {
                        $rel = trim($rel).' noopener';
                    }
                }
                else
                {
                    $rel = 'noopener';
                }

                $target = ' target="'.$target.'"';
            }

            if('' !== $rel)
            {
                $rel = ' rel="'.esc_attr($rel).'"';
            }

            $output .= '<a href="'.$the_link.'"'.$rel.$title.$target.'>';

            $output .= $parsed_args['link_before'];

            if(null != $bookmark->link_image && $parsed_args['show_images'])
            {
                if(str_starts_with($bookmark->link_image, 'http'))
                {
                    $output .= "<img src=\"$bookmark->link_image\" $alt $title />";
                }
                else
                { // If it's a relative path.
                    $output .= '<img src="'.get_option('siteurl')."$bookmark->link_image\" $alt $title />";
                }
                if($parsed_args['show_name'])
                {
                    $output .= " $name";
                }
            }
            else
            {
                $output .= $name;
            }

            $output .= $parsed_args['link_after'];

            $output .= '</a>';

            if($parsed_args['show_updated'] && $bookmark->recently_updated)
            {
                $output .= '</em>';
            }

            if($parsed_args['show_description'] && '' !== $desc)
            {
                $output .= $parsed_args['between'].$desc;
            }

            if($parsed_args['show_rating'])
            {
                $output .= $parsed_args['between'].sanitize_bookmark_field('link_rating', $bookmark->link_rating, $bookmark->link_id, 'display');
            }
            $output .= $parsed_args['after']."\n";
        } // End while.

        return $output;
    }

    function wp_list_bookmarks($args = '')
    {
        $defaults = [
            'orderby' => 'name',
            'order' => 'ASC',
            'limit' => -1,
            'category' => '',
            'exclude_category' => '',
            'category_name' => '',
            'hide_invisible' => 1,
            'show_updated' => 0,
            'echo' => 1,
            'categorize' => 1,
            'title_li' => __('Bookmarks'),
            'title_before' => '<h2>',
            'title_after' => '</h2>',
            'category_orderby' => 'name',
            'category_order' => 'ASC',
            'class' => 'linkcat',
            'category_before' => '<li id="%id" class="%class">',
            'category_after' => '</li>',
        ];

        $parsed_args = wp_parse_args($args, $defaults);

        $output = '';

        if(! is_array($parsed_args['class']))
        {
            $parsed_args['class'] = explode(' ', $parsed_args['class']);
        }
        $parsed_args['class'] = array_map('sanitize_html_class', $parsed_args['class']);
        $parsed_args['class'] = trim(implode(' ', $parsed_args['class']));

        if($parsed_args['categorize'])
        {
            $cats = get_terms([
                                  'taxonomy' => 'link_category',
                                  'name__like' => $parsed_args['category_name'],
                                  'include' => $parsed_args['category'],
                                  'exclude' => $parsed_args['exclude_category'],
                                  'orderby' => $parsed_args['category_orderby'],
                                  'order' => $parsed_args['category_order'],
                                  'hierarchical' => 0,
                              ]);
            if(empty($cats))
            {
                $parsed_args['categorize'] = false;
            }
        }

        if($parsed_args['categorize'])
        {
            // Split the bookmarks into ul's for each category.
            foreach((array) $cats as $cat)
            {
                $params = array_merge($parsed_args, ['category' => $cat->term_id]);
                $bookmarks = get_bookmarks($params);
                if(empty($bookmarks))
                {
                    continue;
                }
                $output .= str_replace(['%id', '%class'], [
                    "linkcat-$cat->term_id",
                    $parsed_args['class']
                ],                     $parsed_args['category_before']);

                $catname = apply_filters('link_category', $cat->name);

                $output .= $parsed_args['title_before'];
                $output .= $catname;
                $output .= $parsed_args['title_after'];
                $output .= "\n\t<ul class='xoxo blogroll'>\n";
                $output .= _walk_bookmarks($bookmarks, $parsed_args);
                $output .= "\n\t</ul>\n";
                $output .= $parsed_args['category_after']."\n";
            }
        }
        else
        {
            // Output one single list using title_li for the title.
            $bookmarks = get_bookmarks($parsed_args);

            if(! empty($bookmarks))
            {
                if(! empty($parsed_args['title_li']))
                {
                    $output .= str_replace(['%id', '%class'], [
                        'linkcat-'.$parsed_args['category'],
                        $parsed_args['class']
                    ],                     $parsed_args['category_before']);
                    $output .= $parsed_args['title_before'];
                    $output .= $parsed_args['title_li'];
                    $output .= $parsed_args['title_after'];
                    $output .= "\n\t<ul class='xoxo blogroll'>\n";
                    $output .= _walk_bookmarks($bookmarks, $parsed_args);
                    $output .= "\n\t</ul>\n";
                    $output .= $parsed_args['category_after']."\n";
                }
                else
                {
                    $output .= _walk_bookmarks($bookmarks, $parsed_args);
                }
            }
        }

        $html = apply_filters('wp_list_bookmarks', $output);

        if($parsed_args['echo'])
        {
            echo $html;
        }
        else
        {
            return $html;
        }
    }
