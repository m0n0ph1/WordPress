<?php

    function get_category_link($category)
    {
        if(! is_object($category))
        {
            $category = (int) $category;
        }

        $category = get_term_link($category);

        if(is_wp_error($category))
        {
            return '';
        }

        return $category;
    }

    function get_category_parents($category_id, $link = false, $separator = '/', $nicename = false, $deprecated = [])
    {
        if(! empty($deprecated))
        {
            _deprecated_argument(__FUNCTION__, '4.8.0');
        }

        $format = $nicename ? 'slug' : 'name';

        $args = [
            'separator' => $separator,
            'link' => $link,
            'format' => $format,
        ];

        return get_term_parents_list($category_id, 'category', $args);
    }

    function get_the_category($post_id = false)
    {
        $categories = get_the_terms($post_id, 'category');
        if(! $categories || is_wp_error($categories))
        {
            $categories = [];
        }

        $categories = array_values($categories);

        foreach(array_keys($categories) as $key)
        {
            _make_cat_compat($categories[$key]);
        }

        return apply_filters('get_the_categories', $categories, $post_id);
    }

    function get_the_category_by_ID($cat_id)
    { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
        $cat_id = (int) $cat_id;
        $category = get_term($cat_id);

        if(is_wp_error($category))
        {
            return $category;
        }

        return ($category) ? $category->name : '';
    }

    function get_the_category_list($separator = '', $parents = '', $post_id = false)
    {
        global $wp_rewrite;

        if(! is_object_in_taxonomy(get_post_type($post_id), 'category'))
        {
            return apply_filters('the_category', '', $separator, $parents);
        }

        $categories = apply_filters('the_category_list', get_the_category($post_id), $post_id);

        if(empty($categories))
        {
            return apply_filters('the_category', __('Uncategorized'), $separator, $parents);
        }

        $rel = (is_object($wp_rewrite) && $wp_rewrite->using_permalinks()) ? 'rel="category tag"' : 'rel="category"';

        $thelist = '';
        if('' === $separator)
        {
            $thelist .= '<ul class="post-categories">';
            foreach($categories as $category)
            {
                $thelist .= "\n\t<li>";
                switch(strtolower($parents))
                {
                    case 'multiple':
                        if($category->parent)
                        {
                            $thelist .= get_category_parents($category->parent, true, $separator);
                        }
                        $thelist .= '<a href="'.esc_url(get_category_link($category->term_id)).'" '.$rel.'>'.$category->name.'</a></li>';
                        break;
                    case 'single':
                        $thelist .= '<a href="'.esc_url(get_category_link($category->term_id)).'"  '.$rel.'>';
                        if($category->parent)
                        {
                            $thelist .= get_category_parents($category->parent, false, $separator);
                        }
                        $thelist .= $category->name.'</a></li>';
                        break;
                    case '':
                    default:
                        $thelist .= '<a href="'.esc_url(get_category_link($category->term_id)).'" '.$rel.'>'.$category->name.'</a></li>';
                }
            }
            $thelist .= '</ul>';
        }
        else
        {
            $i = 0;
            foreach($categories as $category)
            {
                if(0 < $i)
                {
                    $thelist .= $separator;
                }
                switch(strtolower($parents))
                {
                    case 'multiple':
                        if($category->parent)
                        {
                            $thelist .= get_category_parents($category->parent, true, $separator);
                        }
                        $thelist .= '<a href="'.esc_url(get_category_link($category->term_id)).'" '.$rel.'>'.$category->name.'</a>';
                        break;
                    case 'single':
                        $thelist .= '<a href="'.esc_url(get_category_link($category->term_id)).'" '.$rel.'>';
                        if($category->parent)
                        {
                            $thelist .= get_category_parents($category->parent, false, $separator);
                        }
                        $thelist .= "$category->name</a>";
                        break;
                    case '':
                    default:
                        $thelist .= '<a href="'.esc_url(get_category_link($category->term_id)).'" '.$rel.'>'.$category->name.'</a>';
                }
                ++$i;
            }
        }

        return apply_filters('the_category', $thelist, $separator, $parents);
    }

    function in_category($category, $post = null)
    {
        if(empty($category))
        {
            return false;
        }

        return has_category($category, $post);
    }

    function the_category($separator = '', $parents = '', $post_id = false)
    {
        echo get_the_category_list($separator, $parents, $post_id);
    }

    function category_description($category = 0)
    {
        return term_description($category);
    }

    function wp_dropdown_categories($args = '')
    {
        $defaults = [
            'show_option_all' => '',
            'show_option_none' => '',
            'orderby' => 'id',
            'order' => 'ASC',
            'show_count' => 0,
            'hide_empty' => 1,
            'child_of' => 0,
            'exclude' => '',
            'echo' => 1,
            'selected' => 0,
            'hierarchical' => 0,
            'name' => 'cat',
            'id' => '',
            'class' => 'postform',
            'depth' => 0,
            'tab_index' => 0,
            'taxonomy' => 'category',
            'hide_if_empty' => false,
            'option_none_value' => -1,
            'value_field' => 'term_id',
            'required' => false,
            'aria_describedby' => '',
        ];

        $defaults['selected'] = (is_category()) ? get_query_var('cat') : 0;

        // Back compat.
        if(isset($args['type']) && 'link' === $args['type'])
        {
            _deprecated_argument(__FUNCTION__, '3.0.0', sprintf(/* translators: 1: "type => link", 2: "taxonomy => link_category" */ __('%1$s is deprecated. Use %2$s instead.'), '<code>type => link</code>', '<code>taxonomy => link_category</code>'));
            $args['taxonomy'] = 'link_category';
        }

        // Parse incoming $args into an array and merge it with $defaults.
        $parsed_args = wp_parse_args($args, $defaults);

        $option_none_value = $parsed_args['option_none_value'];

        if(! isset($parsed_args['pad_counts']) && $parsed_args['show_count'] && $parsed_args['hierarchical'])
        {
            $parsed_args['pad_counts'] = true;
        }

        $tab_index = $parsed_args['tab_index'];

        $tab_index_attribute = '';
        if((int) $tab_index > 0)
        {
            $tab_index_attribute = " tabindex=\"$tab_index\"";
        }

        // Avoid clashes with the 'name' param of get_terms().
        $get_terms_args = $parsed_args;
        unset($get_terms_args['name']);
        $categories = get_terms($get_terms_args);

        $name = esc_attr($parsed_args['name']);
        $class = esc_attr($parsed_args['class']);
        $id = $parsed_args['id'] ? esc_attr($parsed_args['id']) : $name;
        $required = $parsed_args['required'] ? 'required' : '';

        $aria_describedby_attribute = $parsed_args['aria_describedby'] ? ' aria-describedby="'.esc_attr($parsed_args['aria_describedby']).'"' : '';

        if(! $parsed_args['hide_if_empty'] || ! empty($categories))
        {
            $output = "<select $required name='$name' id='$id' class='$class'$tab_index_attribute$aria_describedby_attribute>\n";
        }
        else
        {
            $output = '';
        }
        if(empty($categories) && ! $parsed_args['hide_if_empty'] && ! empty($parsed_args['show_option_none']))
        {
            $show_option_none = apply_filters('list_cats', $parsed_args['show_option_none'], null);
            $output .= "\t<option value='".esc_attr($option_none_value)."' selected='selected'>$show_option_none</option>\n";
        }

        if(! empty($categories))
        {
            if($parsed_args['show_option_all'])
            {
                $show_option_all = apply_filters('list_cats', $parsed_args['show_option_all'], null);
                $selected = ('0' === (string) $parsed_args['selected']) ? " selected='selected'" : '';
                $output .= "\t<option value='0'$selected>$show_option_all</option>\n";
            }

            if($parsed_args['show_option_none'])
            {
                $show_option_none = apply_filters('list_cats', $parsed_args['show_option_none'], null);
                $selected = selected($option_none_value, $parsed_args['selected'], false);
                $output .= "\t<option value='".esc_attr($option_none_value)."'$selected>$show_option_none</option>\n";
            }

            if($parsed_args['hierarchical'])
            {
                $depth = $parsed_args['depth'];  // Walk the full depth.
            }
            else
            {
                $depth = -1; // Flat.
            }
            $output .= walk_category_dropdown_tree($categories, $depth, $parsed_args);
        }

        if(! $parsed_args['hide_if_empty'] || ! empty($categories))
        {
            $output .= "</select>\n";
        }

        $output = apply_filters('wp_dropdown_cats', $output, $parsed_args);

        if($parsed_args['echo'])
        {
            echo $output;
        }

        return $output;
    }

    function wp_list_categories($args = '')
    {
        $defaults = [
            'child_of' => 0,
            'current_category' => 0,
            'depth' => 0,
            'echo' => 1,
            'exclude' => '',
            'exclude_tree' => '',
            'feed' => '',
            'feed_image' => '',
            'feed_type' => '',
            'hide_empty' => 1,
            'hide_title_if_empty' => false,
            'hierarchical' => true,
            'order' => 'ASC',
            'orderby' => 'name',
            'separator' => '<br />',
            'show_count' => 0,
            'show_option_all' => '',
            'show_option_none' => __('No categories'),
            'style' => 'list',
            'taxonomy' => 'category',
            'title_li' => __('Categories'),
            'use_desc_for_title' => 0,
        ];

        $parsed_args = wp_parse_args($args, $defaults);

        if(! isset($parsed_args['pad_counts']) && $parsed_args['show_count'] && $parsed_args['hierarchical'])
        {
            $parsed_args['pad_counts'] = true;
        }

        // Descendants of exclusions should be excluded too.
        if($parsed_args['hierarchical'])
        {
            $exclude_tree = [];

            if($parsed_args['exclude_tree'])
            {
                $exclude_tree = array_merge($exclude_tree, wp_parse_id_list($parsed_args['exclude_tree']));
            }

            if($parsed_args['exclude'])
            {
                $exclude_tree = array_merge($exclude_tree, wp_parse_id_list($parsed_args['exclude']));
            }

            $parsed_args['exclude_tree'] = $exclude_tree;
            $parsed_args['exclude'] = '';
        }

        if(! isset($parsed_args['class']))
        {
            $parsed_args['class'] = ('category' === $parsed_args['taxonomy']) ? 'categories' : $parsed_args['taxonomy'];
        }

        if(! taxonomy_exists($parsed_args['taxonomy']))
        {
            return false;
        }

        $show_option_all = $parsed_args['show_option_all'];
        $show_option_none = $parsed_args['show_option_none'];

        $categories = get_categories($parsed_args);

        $output = '';

        if($parsed_args['title_li'] && 'list' === $parsed_args['style'] && (! empty($categories) || ! $parsed_args['hide_title_if_empty']))
        {
            $output = '<li class="'.esc_attr($parsed_args['class']).'">'.$parsed_args['title_li'].'<ul>';
        }

        if(empty($categories))
        {
            if(! empty($show_option_none))
            {
                if('list' === $parsed_args['style'])
                {
                    $output .= '<li class="cat-item-none">'.$show_option_none.'</li>';
                }
                else
                {
                    $output .= $show_option_none;
                }
            }
        }
        else
        {
            if(! empty($show_option_all))
            {
                $posts_page = '';

                // For taxonomies that belong only to custom post types, point to a valid archive.
                $taxonomy_object = get_taxonomy($parsed_args['taxonomy']);
                if(! in_array('post', $taxonomy_object->object_type, true) && ! in_array('page', $taxonomy_object->object_type, true))
                {
                    foreach($taxonomy_object->object_type as $object_type)
                    {
                        $_object_type = get_post_type_object($object_type);

                        // Grab the first one.
                        if(! empty($_object_type->has_archive))
                        {
                            $posts_page = get_post_type_archive_link($object_type);
                            break;
                        }
                    }
                }

                // Fallback for the 'All' link is the posts page.
                if(! $posts_page)
                {
                    if('page' === get_option('show_on_front') && get_option('page_for_posts'))
                    {
                        $posts_page = get_permalink(get_option('page_for_posts'));
                    }
                    else
                    {
                        $posts_page = home_url('/');
                    }
                }

                $posts_page = esc_url($posts_page);
                if('list' === $parsed_args['style'])
                {
                    $output .= "<li class='cat-item-all'><a href='$posts_page'>$show_option_all</a></li>";
                }
                else
                {
                    $output .= "<a href='$posts_page'>$show_option_all</a>";
                }
            }

            if(empty($parsed_args['current_category']) && (is_category() || is_tax() || is_tag()))
            {
                $current_term_object = get_queried_object();
                if($current_term_object && $parsed_args['taxonomy'] === $current_term_object->taxonomy)
                {
                    $parsed_args['current_category'] = get_queried_object_id();
                }
            }

            if($parsed_args['hierarchical'])
            {
                $depth = $parsed_args['depth'];
            }
            else
            {
                $depth = -1; // Flat.
            }
            $output .= walk_category_tree($categories, $depth, $parsed_args);
        }

        if($parsed_args['title_li'] && 'list' === $parsed_args['style'] && (! empty($categories) || ! $parsed_args['hide_title_if_empty']))
        {
            $output .= '</ul></li>';
        }

        $html = apply_filters('wp_list_categories', $output, $args);

        if($parsed_args['echo'])
        {
            echo $html;
        }
        else
        {
            return $html;
        }
    }

    function wp_tag_cloud($args = '')
    {
        $defaults = [
            'smallest' => 8,
            'largest' => 22,
            'unit' => 'pt',
            'number' => 45,
            'format' => 'flat',
            'separator' => "\n",
            'orderby' => 'name',
            'order' => 'ASC',
            'exclude' => '',
            'include' => '',
            'link' => 'view',
            'taxonomy' => 'post_tag',
            'post_type' => '',
            'echo' => true,
            'show_count' => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $tags = get_terms(
            array_merge($args, [
                'orderby' => 'count',
                'order' => 'DESC',
            ])
        ); // Always query top tags.

        if(empty($tags) || is_wp_error($tags))
        {
            return;
        }

        foreach($tags as $key => $tag)
        {
            if('edit' === $args['link'])
            {
                $link = get_edit_term_link($tag, $tag->taxonomy, $args['post_type']);
            }
            else
            {
                $link = get_term_link($tag, $tag->taxonomy);
            }

            if(is_wp_error($link))
            {
                return;
            }

            $tags[$key]->link = $link;
            $tags[$key]->id = $tag->term_id;
        }

        // Here's where those top tags get sorted according to $args.
        $return = wp_generate_tag_cloud($tags, $args);

        $return = apply_filters('wp_tag_cloud', $return, $args);

        if('array' === $args['format'] || empty($args['echo']))
        {
            return $return;
        }

        echo $return;
    }

    function default_topic_count_scale($count)
    {
        return round(log10($count + 1) * 100);
    }

    function wp_generate_tag_cloud($tags, $args = '')
    {
        $defaults = [
            'smallest' => 8,
            'largest' => 22,
            'unit' => 'pt',
            'number' => 0,
            'format' => 'flat',
            'separator' => "\n",
            'orderby' => 'name',
            'order' => 'ASC',
            'topic_count_text' => null,
            'topic_count_text_callback' => null,
            'topic_count_scale_callback' => 'default_topic_count_scale',
            'filter' => 1,
            'show_count' => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $return = ('array' === $args['format']) ? [] : '';

        if(empty($tags))
        {
            return $return;
        }

        // Juggle topic counts.
        if(isset($args['topic_count_text']))
        {
            // First look for nooped plural support via topic_count_text.
            $translate_nooped_plural = $args['topic_count_text'];
        }
        elseif(! empty($args['topic_count_text_callback']))
        {
            // Look for the alternative callback style. Ignore the previous default.
            if('default_topic_count_text' === $args['topic_count_text_callback'])
            {
                /* translators: %s: Number of items (tags). */
                $translate_nooped_plural = _n_noop('%s item', '%s items');
            }
            else
            {
                $translate_nooped_plural = false;
            }
        }
        elseif(isset($args['single_text']) && isset($args['multiple_text']))
        {
            // If no callback exists, look for the old-style single_text and multiple_text arguments.
            // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingle,WordPress.WP.I18n.NonSingularStringLiteralPlural
            $translate_nooped_plural = _n_noop($args['single_text'], $args['multiple_text']);
        }
        else
        {
            // This is the default for when no callback, plural, or argument is passed in.
            /* translators: %s: Number of items (tags). */
            $translate_nooped_plural = _n_noop('%s item', '%s items');
        }

        $tags_sorted = apply_filters('tag_cloud_sort', $tags, $args);
        if(empty($tags_sorted))
        {
            return $return;
        }

        if($tags_sorted !== $tags)
        {
            $tags = $tags_sorted;
            unset($tags_sorted);
        }
        else
        {
            if('RAND' === $args['order'])
            {
                shuffle($tags);
            }
            else
            {
                // SQL cannot save you; this is a second (potentially different) sort on a subset of data.
                if('name' === $args['orderby'])
                {
                    uasort($tags, '_wp_object_name_sort_cb');
                }
                else
                {
                    uasort($tags, '_wp_object_count_sort_cb');
                }

                if('DESC' === $args['order'])
                {
                    $tags = array_reverse($tags, true);
                }
            }
        }

        if($args['number'] > 0)
        {
            $tags = array_slice($tags, 0, $args['number']);
        }

        $counts = [];
        $real_counts = []; // For the alt tag.
        foreach((array) $tags as $key => $tag)
        {
            $real_counts[$key] = $tag->count;
            $counts[$key] = call_user_func($args['topic_count_scale_callback'], $tag->count);
        }

        $min_count = min($counts);
        $spread = max($counts) - $min_count;
        if($spread <= 0)
        {
            $spread = 1;
        }
        $font_spread = $args['largest'] - $args['smallest'];
        if($font_spread < 0)
        {
            $font_spread = 1;
        }
        $font_step = $font_spread / $spread;

        $aria_label = false;
        /*
         * Determine whether to output an 'aria-label' attribute with the tag name and count.
         * When tags have a different font size, they visually convey an important information
         * that should be available to assistive technologies too. On the other hand, sometimes
         * themes set up the Tag Cloud to display all tags with the same font size (setting
         * the 'smallest' and 'largest' arguments to the same value).
         * In order to always serve the same content to all users, the 'aria-label' gets printed out:
         * - when tags have a different size
         * - when the tag count is displayed (for example when users check the checkbox in the
         *   Tag Cloud widget), regardless of the tags font size
         */
        if($args['show_count'] || 0 !== $font_spread)
        {
            $aria_label = true;
        }

        // Assemble the data that will be used to generate the tag cloud markup.
        $tags_data = [];
        foreach($tags as $key => $tag)
        {
            $tag_id = isset($tag->id) ? $tag->id : $key;

            $count = $counts[$key];
            $real_count = $real_counts[$key];

            if($translate_nooped_plural)
            {
                $formatted_count = sprintf(translate_nooped_plural($translate_nooped_plural, $real_count), number_format_i18n($real_count));
            }
            else
            {
                $formatted_count = call_user_func($args['topic_count_text_callback'], $real_count, $tag, $args);
            }

            $tags_data[] = [
                'id' => $tag_id,
                'url' => ('#' !== $tag->link) ? $tag->link : '#',
                'role' => ('#' !== $tag->link) ? '' : ' role="button"',
                'name' => $tag->name,
                'formatted_count' => $formatted_count,
                'slug' => $tag->slug,
                'real_count' => $real_count,
                'class' => 'tag-cloud-link tag-link-'.$tag_id,
                'font_size' => $args['smallest'] + ($count - $min_count) * $font_step,
                'aria_label' => $aria_label ? sprintf(' aria-label="%1$s (%2$s)"', esc_attr($tag->name), esc_attr($formatted_count)) : '',
                'show_count' => $args['show_count'] ? '<span class="tag-link-count"> ('.$real_count.')</span>' : '',
            ];
        }

        $tags_data = apply_filters('wp_generate_tag_cloud_data', $tags_data);

        $a = [];

        // Generate the output links array.
        foreach($tags_data as $key => $tag_data)
        {
            $class = $tag_data['class'].' tag-link-position-'.($key + 1);
            $a[] = sprintf('<a href="%1$s"%2$s class="%3$s" style="font-size: %4$s;"%5$s>%6$s%7$s</a>', esc_url($tag_data['url']), $tag_data['role'], esc_attr($class), esc_attr(str_replace(',', '.', $tag_data['font_size']).$args['unit']), $tag_data['aria_label'], esc_html($tag_data['name']), $tag_data['show_count']);
        }

        switch($args['format'])
        {
            case 'array':
                $return =& $a;
                break;
            case 'list':
                /*
                 * Force role="list", as some browsers (sic: Safari 10) don't expose to assistive
                 * technologies the default role when the list is styled with `list-style: none`.
                 * Note: this is redundant but doesn't harm.
                 */ $return = "<ul class='wp-tag-cloud' role='list'>\n\t<li>";
                $return .= implode("</li>\n\t<li>", $a);
                $return .= "</li>\n</ul>\n";
                break;
            default:
                $return = implode($args['separator'], $a);
                break;
        }

        if($args['filter'])
        {
            return apply_filters('wp_generate_tag_cloud', $return, $tags, $args);
        }
        else
        {
            return $return;
        }
    }

    function _wp_object_name_sort_cb($a, $b)
    {
        return strnatcasecmp($a->name, $b->name);
    }

    function _wp_object_count_sort_cb($a, $b)
    {
        return ($a->count - $b->count);
    }

//
// Helper functions.
//

    function walk_category_tree(...$args)
    {
        // The user's options are the third parameter.
        if(empty($args[2]['walker']) || ! ($args[2]['walker'] instanceof Walker))
        {
            $walker = new Walker_Category();
        }
        else
        {
            $walker = $args[2]['walker'];
        }

        return $walker->walk(...$args);
    }

    function walk_category_dropdown_tree(...$args)
    {
        // The user's options are the third parameter.
        if(empty($args[2]['walker']) || ! ($args[2]['walker'] instanceof Walker))
        {
            $walker = new Walker_CategoryDropdown();
        }
        else
        {
            $walker = $args[2]['walker'];
        }

        return $walker->walk(...$args);
    }

//
// Tags.
//

    function get_tag_link($tag)
    {
        return get_category_link($tag);
    }

    function get_the_tags($post = 0)
    {
        $terms = get_the_terms($post, 'post_tag');

        return apply_filters('get_the_tags', $terms);
    }

    function get_the_tag_list($before = '', $sep = '', $after = '', $post_id = 0)
    {
        $tag_list = get_the_term_list($post_id, 'post_tag', $before, $sep, $after);

        return apply_filters('the_tags', $tag_list, $before, $sep, $after, $post_id);
    }

    function the_tags($before = null, $sep = ', ', $after = '')
    {
        if(null === $before)
        {
            $before = __('Tags: ');
        }

        $the_tags = get_the_tag_list($before, $sep, $after);

        if(! is_wp_error($the_tags))
        {
            echo $the_tags;
        }
    }

    function tag_description($tag = 0)
    {
        return term_description($tag);
    }

    function term_description($term = 0, $deprecated = null)
    {
        if(! $term && (is_tax() || is_tag() || is_category()))
        {
            $term = get_queried_object();
            if($term)
            {
                $term = $term->term_id;
            }
        }

        $description = get_term_field('description', $term);

        return is_wp_error($description) ? '' : $description;
    }

    function get_the_terms($post, $taxonomy)
    {
        $post = get_post($post);

        if(! $post)
        {
            return false;
        }

        $terms = get_object_term_cache($post->ID, $taxonomy);

        if(false === $terms)
        {
            $terms = wp_get_object_terms($post->ID, $taxonomy);
            if(! is_wp_error($terms))
            {
                $term_ids = wp_list_pluck($terms, 'term_id');
                wp_cache_add($post->ID, $term_ids, $taxonomy.'_relationships');
            }
        }

        $terms = apply_filters('get_the_terms', $terms, $post->ID, $taxonomy);

        if(empty($terms))
        {
            return false;
        }

        return $terms;
    }

    function get_the_term_list($post_id, $taxonomy, $before = '', $sep = '', $after = '')
    {
        $terms = get_the_terms($post_id, $taxonomy);

        if(is_wp_error($terms))
        {
            return $terms;
        }

        if(empty($terms))
        {
            return false;
        }

        $links = [];

        foreach($terms as $term)
        {
            $link = get_term_link($term, $taxonomy);
            if(is_wp_error($link))
            {
                return $link;
            }
            $links[] = '<a href="'.esc_url($link).'" rel="tag">'.$term->name.'</a>';
        }

        $term_links = apply_filters("term_links-{$taxonomy}", $links);  // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

        return $before.implode($sep, $term_links).$after;
    }

    function get_term_parents_list($term_id, $taxonomy, $args = [])
    {
        $list = '';
        $term = get_term($term_id, $taxonomy);

        if(is_wp_error($term))
        {
            return $term;
        }

        if(! $term)
        {
            return $list;
        }

        $term_id = $term->term_id;

        $defaults = [
            'format' => 'name',
            'separator' => '/',
            'link' => true,
            'inclusive' => true,
        ];

        $args = wp_parse_args($args, $defaults);

        foreach(['link', 'inclusive'] as $bool)
        {
            $args[$bool] = wp_validate_boolean($args[$bool]);
        }

        $parents = get_ancestors($term_id, $taxonomy, 'taxonomy');

        if($args['inclusive'])
        {
            array_unshift($parents, $term_id);
        }

        foreach(array_reverse($parents) as $term_id)
        {
            $parent = get_term($term_id, $taxonomy);
            $name = ('slug' === $args['format']) ? $parent->slug : $parent->name;

            if($args['link'])
            {
                $list .= '<a href="'.esc_url(get_term_link($parent->term_id, $taxonomy)).'">'.$name.'</a>'.$args['separator'];
            }
            else
            {
                $list .= $name.$args['separator'];
            }
        }

        return $list;
    }

    function the_terms($post_id, $taxonomy, $before = '', $sep = ', ', $after = '')
    {
        $term_list = get_the_term_list($post_id, $taxonomy, $before, $sep, $after);

        if(is_wp_error($term_list))
        {
            return false;
        }

        echo apply_filters('the_terms', $term_list, $taxonomy, $before, $sep, $after);
    }

    function has_category($category = '', $post = null)
    {
        return has_term($category, 'category', $post);
    }

    function has_tag($tag = '', $post = null)
    {
        return has_term($tag, 'post_tag', $post);
    }

    function has_term($term = '', $taxonomy = '', $post = null)
    {
        $post = get_post($post);

        if(! $post)
        {
            return false;
        }

        $r = is_object_in_term($post->ID, $taxonomy, $term);
        if(is_wp_error($r))
        {
            return false;
        }

        return $r;
    }
