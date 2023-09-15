<?php

// Define constants for supported wp_template_part_area taxonomy.
    if(! defined('WP_TEMPLATE_PART_AREA_HEADER'))
    {
        define('WP_TEMPLATE_PART_AREA_HEADER', 'header');
    }
    if(! defined('WP_TEMPLATE_PART_AREA_FOOTER'))
    {
        define('WP_TEMPLATE_PART_AREA_FOOTER', 'footer');
    }
    if(! defined('WP_TEMPLATE_PART_AREA_SIDEBAR'))
    {
        define('WP_TEMPLATE_PART_AREA_SIDEBAR', 'sidebar');
    }
    if(! defined('WP_TEMPLATE_PART_AREA_UNCATEGORIZED'))
    {
        define('WP_TEMPLATE_PART_AREA_UNCATEGORIZED', 'uncategorized');
    }

    function get_block_theme_folders($theme_stylesheet = null)
    {
        $theme_name = null === $theme_stylesheet ? get_stylesheet() : $theme_stylesheet;
        $root_dir = get_theme_root($theme_name);
        $theme_dir = "$root_dir/$theme_name";

        if(file_exists($theme_dir.'/block-templates') || file_exists($theme_dir.'/block-template-parts'))
        {
            return [
                'wp_template' => 'block-templates',
                'wp_template_part' => 'block-template-parts',
            ];
        }

        return [
            'wp_template' => 'templates',
            'wp_template_part' => 'parts',
        ];
    }

    function get_allowed_block_template_part_areas()
    {
        $default_area_definitions = [
            [
                'area' => WP_TEMPLATE_PART_AREA_UNCATEGORIZED,
                'label' => _x('General', 'template part area'),
                'description' => __('General templates often perform a specific role like displaying post content, and are not tied to any particular area.'),
                'icon' => 'layout',
                'area_tag' => 'div',
            ],
            [
                'area' => WP_TEMPLATE_PART_AREA_HEADER,
                'label' => _x('Header', 'template part area'),
                'description' => __('The Header template defines a page area that typically contains a title, logo, and main navigation.'),
                'icon' => 'header',
                'area_tag' => 'header',
            ],
            [
                'area' => WP_TEMPLATE_PART_AREA_FOOTER,
                'label' => _x('Footer', 'template part area'),
                'description' => __('The Footer template defines a page area that typically contains site credits, social links, or any other combination of blocks.'),
                'icon' => 'footer',
                'area_tag' => 'footer',
            ],
        ];

        return apply_filters('default_wp_template_part_areas', $default_area_definitions);
    }

    function get_default_block_template_types()
    {
        $default_template_types = [
            'index' => [
                'title' => _x('Index', 'Template name'),
                'description' => __('Used as a fallback template for all pages when a more specific template is not defined.'),
            ],
            'home' => [
                'title' => _x('Blog Home', 'Template name'),
                'description' => __('Displays the latest posts as either the site homepage or as the "Posts page" as defined under reading settings. If it exists, the Front Page template overrides this template when posts are shown on the homepage.'),
            ],
            'front-page' => [
                'title' => _x('Front Page', 'Template name'),
                'description' => __('Displays your site\'s homepage, whether it is set to display latest posts or a static page. The Front Page template takes precedence over all templates.'),
            ],
            'singular' => [
                'title' => _x('Single Entries', 'Template name'),
                'description' => __('Displays any single entry, such as a post or a page. This template will serve as a fallback when a more specific template (e.g. Single Post, Page, or Attachment) cannot be found.'),
            ],
            'single' => [
                'title' => _x('Single Posts', 'Template name'),
                'description' => __('Displays single posts on your website unless a custom template has been applied to that post or a dedicated template exists.'),
            ],
            'page' => [
                'title' => _x('Pages', 'Template name'),
                'description' => __('Display all static pages unless a custom template has been applied or a dedicated template exists.'),
            ],
            'archive' => [
                'title' => _x('All Archives', 'Template name'),
                'description' => __('Displays any archive, including posts by a single author, category, tag, taxonomy, custom post type, and date. This template will serve as a fallback when more specific templates (e.g. Category or Tag) cannot be found.'),
            ],
            'author' => [
                'title' => _x('Author Archives', 'Template name'),
                'description' => __('Displays a single author\'s post archive. This template will serve as a fallback when a more specific template (e.g. Author: Admin) cannot be found.'),
            ],
            'category' => [
                'title' => _x('Category Archives', 'Template name'),
                'description' => __('Displays a post category archive. This template will serve as a fallback when a more specific template (e.g. Category: Recipes) cannot be found.'),
            ],
            'taxonomy' => [
                'title' => _x('Taxonomy', 'Template name'),
                'description' => __('Displays a custom taxonomy archive. Like categories and tags, taxonomies have terms which you use to classify things. For example: a taxonomy named "Art" can have multiple terms, such as "Modern" and "18th Century." This template will serve as a fallback when a more specific template (e.g. Taxonomy: Art) cannot be found.'),
            ],
            'date' => [
                'title' => _x('Date Archives', 'Template name'),
                'description' => __('Displays a post archive when a specific date is visited (e.g., example.com/2023/).'),
            ],
            'tag' => [
                'title' => _x('Tag Archives', 'Template name'),
                'description' => __('Displays a post tag archive. This template will serve as a fallback when a more specific template (e.g. Tag: Pizza) cannot be found.'),
            ],
            'attachment' => [
                'title' => __('Attachment Pages'),
                'description' => __('Displays when a visitor views the dedicated page that exists for any media attachment.'),
            ],
            'search' => [
                'title' => _x('Search Results', 'Template name'),
                'description' => __('Displays when a visitor performs a search on your website.'),
            ],
            'privacy-policy' => [
                'title' => __('Privacy Policy'),
                'description' => __('Displays your site\'s Privacy Policy page.'),
            ],
            '404' => [
                'title' => _x('Page: 404', 'Template name'),
                'description' => __('Displays when a visitor views a non-existent page, such as a dead link or a mistyped URL.'),
            ],
        ];

        return apply_filters('default_template_types', $default_template_types);
    }

    function _filter_block_template_part_area($type)
    {
        $allowed_areas = array_map(static function($item)
        {
            return $item['area'];
        }, get_allowed_block_template_part_areas());
        if(in_array($type, $allowed_areas, true))
        {
            return $type;
        }

        $warning_message = sprintf(/* translators: %1$s: Template area type, %2$s: the uncategorized template area value. */ __('"%1$s" is not a supported wp_template_part area value and has been added as "%2$s".'), $type, WP_TEMPLATE_PART_AREA_UNCATEGORIZED);
        trigger_error($warning_message, E_USER_NOTICE);

        return WP_TEMPLATE_PART_AREA_UNCATEGORIZED;
    }

    function _get_block_templates_paths($base_directory)
    {
        $path_list = [];
        if(file_exists($base_directory))
        {
            $nested_files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base_directory));
            $nested_html_files = new RegexIterator($nested_files, '/^.+\.html$/i', RecursiveRegexIterator::GET_MATCH);
            foreach($nested_html_files as $path => $file)
            {
                $path_list[] = $path;
            }
        }

        return $path_list;
    }

    function _get_block_template_file($template_type, $slug)
    {
        if('wp_template' !== $template_type && 'wp_template_part' !== $template_type)
        {
            return null;
        }

        $themes = [
            get_stylesheet() => get_stylesheet_directory(),
            get_template() => get_template_directory(),
        ];
        foreach($themes as $theme_slug => $theme_dir)
        {
            $template_base_paths = get_block_theme_folders($theme_slug);
            $file_path = $theme_dir.'/'.$template_base_paths[$template_type].'/'.$slug.'.html';
            if(file_exists($file_path))
            {
                $new_template_item = [
                    'slug' => $slug,
                    'path' => $file_path,
                    'theme' => $theme_slug,
                    'type' => $template_type,
                ];

                if('wp_template_part' === $template_type)
                {
                    return _add_block_template_part_area_info($new_template_item);
                }

                if('wp_template' === $template_type)
                {
                    return _add_block_template_info($new_template_item);
                }

                return $new_template_item;
            }
        }

        return null;
    }

    function _get_block_templates_files($template_type, $query = [])
    {
        if('wp_template' !== $template_type && 'wp_template_part' !== $template_type)
        {
            return null;
        }

        // Prepare metadata from $query.
        $slugs_to_include = isset($query['slug__in']) ? $query['slug__in'] : [];
        $slugs_to_skip = isset($query['slug__not_in']) ? $query['slug__not_in'] : [];
        $area = isset($query['area']) ? $query['area'] : null;
        $post_type = isset($query['post_type']) ? $query['post_type'] : '';

        $stylesheet = get_stylesheet();
        $template = get_template();
        $themes = [
            $stylesheet => get_stylesheet_directory(),
        ];
        // Add the parent theme if it's not the same as the current theme.
        if($stylesheet !== $template)
        {
            $themes[$template] = get_template_directory();
        }
        $template_files = [];
        foreach($themes as $theme_slug => $theme_dir)
        {
            $template_base_paths = get_block_theme_folders($theme_slug);
            $theme_template_files = _get_block_templates_paths($theme_dir.'/'.$template_base_paths[$template_type]);
            foreach($theme_template_files as $template_file)
            {
                $template_base_path = $template_base_paths[$template_type];
                $template_slug = substr(
                    $template_file, // Starting position of slug.
                    strpos($template_file, $template_base_path.DIRECTORY_SEPARATOR) + 1 + strlen($template_base_path), // Subtract ending '.html'.
                    -5
                );

                // Skip this item if its slug doesn't match any of the slugs to include.
                if(! empty($slugs_to_include) && ! in_array($template_slug, $slugs_to_include, true))
                {
                    continue;
                }

                // Skip this item if its slug matches any of the slugs to skip.
                if(! empty($slugs_to_skip) && in_array($template_slug, $slugs_to_skip, true))
                {
                    continue;
                }

                /*
                 * The child theme items (stylesheet) are processed before the parent theme's (template).
                 * If a child theme defines a template, prevent the parent template from being added to the list as well.
                 */
                if(isset($template_files[$template_slug]))
                {
                    continue;
                }

                $new_template_item = [
                    'slug' => $template_slug,
                    'path' => $template_file,
                    'theme' => $theme_slug,
                    'type' => $template_type,
                ];

                if('wp_template_part' === $template_type)
                {
                    $candidate = _add_block_template_part_area_info($new_template_item);
                    if(! isset($area) || (isset($area) && $area === $candidate['area']))
                    {
                        $template_files[$template_slug] = $candidate;
                    }
                }

                if('wp_template' === $template_type)
                {
                    $candidate = _add_block_template_info($new_template_item);
                    if(! $post_type || ($post_type && isset($candidate['postTypes']) && in_array($post_type, $candidate['postTypes'], true)))
                    {
                        $template_files[$template_slug] = $candidate;
                    }
                }
            }
        }

        return array_values($template_files);
    }

    function _add_block_template_info($template_item)
    {
        if(! wp_theme_has_theme_json())
        {
            return $template_item;
        }

        $theme_data = wp_get_theme_data_custom_templates();
        if(isset($theme_data[$template_item['slug']]))
        {
            $template_item['title'] = $theme_data[$template_item['slug']]['title'];
            $template_item['postTypes'] = $theme_data[$template_item['slug']]['postTypes'];
        }

        return $template_item;
    }

    function _add_block_template_part_area_info($template_info)
    {
        if(wp_theme_has_theme_json())
        {
            $theme_data = wp_get_theme_data_template_parts();
        }

        if(isset($theme_data[$template_info['slug']]['area']))
        {
            $template_info['title'] = $theme_data[$template_info['slug']]['title'];
            $template_info['area'] = _filter_block_template_part_area($theme_data[$template_info['slug']]['area']);
        }
        else
        {
            $template_info['area'] = WP_TEMPLATE_PART_AREA_UNCATEGORIZED;
        }

        return $template_info;
    }

    function _flatten_blocks(&$blocks)
    {
        $all_blocks = [];
        $queue = [];
        foreach($blocks as &$block)
        {
            $queue[] = &$block;
        }

        while(count($queue) > 0)
        {
            $block = &$queue[0];
            array_shift($queue);
            $all_blocks[] = &$block;

            if(! empty($block['innerBlocks']))
            {
                foreach($block['innerBlocks'] as &$inner_block)
                {
                    $queue[] = &$inner_block;
                }
            }
        }

        return $all_blocks;
    }

    function _inject_theme_attribute_in_block_template_content($template_content)
    {
        $has_updated_content = false;
        $new_content = '';
        $template_blocks = parse_blocks($template_content);

        $blocks = _flatten_blocks($template_blocks);
        foreach($blocks as &$block)
        {
            if('core/template-part' === $block['blockName'] && ! isset($block['attrs']['theme']))
            {
                $block['attrs']['theme'] = get_stylesheet();
                $has_updated_content = true;
            }
        }

        if($has_updated_content)
        {
            foreach($template_blocks as &$block)
            {
                $new_content .= serialize_block($block);
            }

            return $new_content;
        }

        return $template_content;
    }

    function _inject_theme_attribute_in_template_part_block($block)
    {
        if('core/template-part' === $block['blockName'] && ! isset($block['attrs']['theme']))
        {
            $block['attrs']['theme'] = get_stylesheet();
        }

        return $block;
    }

    function _remove_theme_attribute_in_block_template_content($template_content)
    {
        $has_updated_content = false;
        $new_content = '';
        $template_blocks = parse_blocks($template_content);

        $blocks = _flatten_blocks($template_blocks);
        foreach($blocks as $key => $block)
        {
            if('core/template-part' === $block['blockName'] && isset($block['attrs']['theme']))
            {
                unset($blocks[$key]['attrs']['theme']);
                $has_updated_content = true;
            }
        }

        if(! $has_updated_content)
        {
            return $template_content;
        }

        foreach($template_blocks as $block)
        {
            $new_content .= serialize_block($block);
        }

        return $new_content;
    }

    function _build_block_template_result_from_file($template_file, $template_type)
    {
        $default_template_types = get_default_block_template_types();
        $template_content = file_get_contents($template_file['path']);
        $theme = get_stylesheet();

        $template = new WP_Block_Template();
        $template->id = $theme.'//'.$template_file['slug'];
        $template->theme = $theme;
        $template->slug = $template_file['slug'];
        $template->source = 'theme';
        $template->type = $template_type;
        $template->title = ! empty($template_file['title']) ? $template_file['title'] : $template_file['slug'];
        $template->status = 'publish';
        $template->has_theme_file = true;
        $template->is_custom = true;
        $template->modified = null;

        if('wp_template' === $template_type && isset($default_template_types[$template_file['slug']]))
        {
            $template->description = $default_template_types[$template_file['slug']]['description'];
            $template->title = $default_template_types[$template_file['slug']]['title'];
            $template->is_custom = false;
        }

        if('wp_template' === $template_type && isset($template_file['postTypes']))
        {
            $template->post_types = $template_file['postTypes'];
        }

        if('wp_template_part' === $template_type && isset($template_file['area']))
        {
            $template->area = $template_file['area'];
        }

        $blocks = parse_blocks($template_content);
        $template->content = serialize_blocks($blocks, '_inject_theme_attribute_in_template_part_block');

        return $template;
    }

    function _wp_build_title_and_description_for_single_post_type_block_template(
        $post_type, $slug, WP_Block_Template $template
    ) {
        $post_type_object = get_post_type_object($post_type);

        $default_args = [
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
        ];

        $args = [
            'name' => $slug,
        ];
        $args = wp_parse_args($args, $default_args);

        $posts_query = new WP_Query($args);

        if(empty($posts_query->posts))
        {
            $template->title = sprintf(/* translators: Custom template title in the Site Editor referencing a post that was not found. 1: Post type singular name, 2: Post type slug. */ __('Not found: %1$s (%2$s)'), $post_type_object->labels->singular_name, $slug);

            return false;
        }

        $post_title = $posts_query->posts[0]->post_title;

        $template->title = sprintf(/* translators: Custom template title in the Site Editor. 1: Post type singular name, 2: Post title. */ __('%1$s: %2$s'), $post_type_object->labels->singular_name, $post_title);

        $template->description = sprintf(/* translators: Custom template description in the Site Editor. %s: Post title. */ __('Template for %s'), $post_title);

        $args = [
            'title' => $post_title,
        ];
        $args = wp_parse_args($args, $default_args);

        $posts_with_same_title_query = new WP_Query($args);

        if(count($posts_with_same_title_query->posts) > 1)
        {
            $template->title = sprintf(/* translators: Custom template title in the Site Editor. 1: Template title, 2: Post type slug. */ __('%1$s (%2$s)'), $template->title, $slug);
        }

        return true;
    }

    function _wp_build_title_and_description_for_taxonomy_block_template($taxonomy, $slug, WP_Block_Template $template)
    {
        $taxonomy_object = get_taxonomy($taxonomy);

        $default_args = [
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'update_term_meta_cache' => false,
        ];

        $term_query = new WP_Term_Query();

        $args = [
            'number' => 1,
            'slug' => $slug,
        ];
        $args = wp_parse_args($args, $default_args);

        $terms_query = $term_query->query($args);

        if(empty($terms_query))
        {
            $template->title = sprintf(/* translators: Custom template title in the Site Editor, referencing a taxonomy term that was not found. 1: Taxonomy singular name, 2: Term slug. */ __('Not found: %1$s (%2$s)'), $taxonomy_object->labels->singular_name, $slug);

            return false;
        }

        $term_title = $terms_query[0]->name;

        $template->title = sprintf(/* translators: Custom template title in the Site Editor. 1: Taxonomy singular name, 2: Term title. */ __('%1$s: %2$s'), $taxonomy_object->labels->singular_name, $term_title);

        $template->description = sprintf(/* translators: Custom template description in the Site Editor. %s: Term title. */ __('Template for %s'), $term_title);

        $term_query = new WP_Term_Query();

        $args = [
            'number' => 2,
            'name' => $term_title,
        ];
        $args = wp_parse_args($args, $default_args);

        $terms_with_same_title_query = $term_query->query($args);

        if(count($terms_with_same_title_query) > 1)
        {
            $template->title = sprintf(/* translators: Custom template title in the Site Editor. 1: Template title, 2: Term slug. */ __('%1$s (%2$s)'), $template->title, $slug);
        }

        return true;
    }

    function _build_block_template_result_from_post($post)
    {
        $default_template_types = get_default_block_template_types();
        $terms = get_the_terms($post, 'wp_theme');

        if(is_wp_error($terms))
        {
            return $terms;
        }

        if(! $terms)
        {
            return new WP_Error('template_missing_theme', __('No theme is defined for this template.'));
        }

        $theme = $terms[0]->name;
        $template_file = _get_block_template_file($post->post_type, $post->post_name);
        $has_theme_file = get_stylesheet() === $theme && null !== $template_file;

        $origin = get_post_meta($post->ID, 'origin', true);
        $is_wp_suggestion = get_post_meta($post->ID, 'is_wp_suggestion', true);

        $template = new WP_Block_Template();
        $template->wp_id = $post->ID;
        $template->id = $theme.'//'.$post->post_name;
        $template->theme = $theme;
        $template->content = $post->post_content;
        $template->slug = $post->post_name;
        $template->source = 'custom';
        $template->origin = ! empty($origin) ? $origin : null;
        $template->type = $post->post_type;
        $template->description = $post->post_excerpt;
        $template->title = $post->post_title;
        $template->status = $post->post_status;
        $template->has_theme_file = $has_theme_file;
        $template->is_custom = empty($is_wp_suggestion);
        $template->author = $post->post_author;
        $template->modified = $post->post_modified;

        if('wp_template' === $post->post_type && $has_theme_file && isset($template_file['postTypes']))
        {
            $template->post_types = $template_file['postTypes'];
        }

        if('wp_template' === $post->post_type && isset($default_template_types[$template->slug]))
        {
            $template->is_custom = false;
        }

        if('wp_template_part' === $post->post_type)
        {
            $type_terms = get_the_terms($post, 'wp_template_part_area');
            if(! is_wp_error($type_terms) && false !== $type_terms)
            {
                $template->area = $type_terms[0]->name;
            }
        }

        // Check for a block template without a description and title or with a title equal to the slug.
        if('wp_template' === $post->post_type && empty($template->description) && (empty($template->title) || $template->title === $template->slug))
        {
            $matches = [];

            // Check for a block template for a single author, page, post, tag, category, custom post type, or custom taxonomy.
            if(preg_match('/(author|page|single|tag|category|taxonomy)-(.+)/', $template->slug, $matches))
            {
                $type = $matches[1];
                $slug_remaining = $matches[2];

                switch($type)
                {
                    case 'author':
                        $nice_name = $slug_remaining;
                        $users = get_users([
                                               'capability' => 'edit_posts',
                                               'search' => $nice_name,
                                               'search_columns' => ['user_nicename'],
                                               'fields' => 'display_name',
                                           ]);

                        if(empty($users))
                        {
                            $template->title = sprintf(/* translators: Custom template title in the Site Editor, referencing a deleted author. %s: Author nicename. */ __('Deleted author: %s'), $nice_name);
                        }
                        else
                        {
                            $author_name = $users[0];

                            $template->title = sprintf(/* translators: Custom template title in the Site Editor. %s: Author name. */ __('Author: %s'), $author_name);

                            $template->description = sprintf(/* translators: Custom template description in the Site Editor. %s: Author name. */ __('Template for %s'), $author_name);

                            $users_with_same_name = get_users([
                                                                  'capability' => 'edit_posts',
                                                                  'search' => $author_name,
                                                                  'search_columns' => ['display_name'],
                                                                  'fields' => 'display_name',
                                                              ]);

                            if(count($users_with_same_name) > 1)
                            {
                                $template->title = sprintf(/* translators: Custom template title in the Site Editor. 1: Template title of an author template, 2: Author nicename. */ __('%1$s (%2$s)'), $template->title, $nice_name);
                            }
                        }
                        break;
                    case 'page':
                        _wp_build_title_and_description_for_single_post_type_block_template('page', $slug_remaining, $template);
                        break;
                    case 'single':
                        $post_types = get_post_types();

                        foreach($post_types as $post_type)
                        {
                            $post_type_length = strlen($post_type) + 1;

                            // If $slug_remaining starts with $post_type followed by a hyphen.
                            if(0 === strncmp($slug_remaining, $post_type.'-', $post_type_length))
                            {
                                $slug = substr($slug_remaining, $post_type_length, strlen($slug_remaining));
                                $found = _wp_build_title_and_description_for_single_post_type_block_template($post_type, $slug, $template);

                                if($found)
                                {
                                    break;
                                }
                            }
                        }
                        break;
                    case 'tag':
                        _wp_build_title_and_description_for_taxonomy_block_template('post_tag', $slug_remaining, $template);
                        break;
                    case 'category':
                        _wp_build_title_and_description_for_taxonomy_block_template('category', $slug_remaining, $template);
                        break;
                    case 'taxonomy':
                        $taxonomies = get_taxonomies();

                        foreach($taxonomies as $taxonomy)
                        {
                            $taxonomy_length = strlen($taxonomy) + 1;

                            // If $slug_remaining starts with $taxonomy followed by a hyphen.
                            if(0 === strncmp($slug_remaining, $taxonomy.'-', $taxonomy_length))
                            {
                                $slug = substr($slug_remaining, $taxonomy_length, strlen($slug_remaining));
                                $found = _wp_build_title_and_description_for_taxonomy_block_template($taxonomy, $slug, $template);

                                if($found)
                                {
                                    break;
                                }
                            }
                        }
                        break;
                }
            }
        }

        return $template;
    }

    function get_block_templates($query = [], $template_type = 'wp_template')
    {
        $templates = apply_filters('pre_get_block_templates', null, $query, $template_type);
        if(! is_null($templates))
        {
            return $templates;
        }

        $post_type = isset($query['post_type']) ? $query['post_type'] : '';
        $wp_query_args = [
            'post_status' => ['auto-draft', 'draft', 'publish'],
            'post_type' => $template_type,
            'posts_per_page' => -1,
            'no_found_rows' => true,
            'lazy_load_term_meta' => false,
            'tax_query' => [
                [
                    'taxonomy' => 'wp_theme',
                    'field' => 'name',
                    'terms' => get_stylesheet(),
                ],
            ],
        ];

        if('wp_template_part' === $template_type && isset($query['area']))
        {
            $wp_query_args['tax_query'][] = [
                'taxonomy' => 'wp_template_part_area',
                'field' => 'name',
                'terms' => $query['area'],
            ];
            $wp_query_args['tax_query']['relation'] = 'AND';
        }

        if(! empty($query['slug__in']))
        {
            $wp_query_args['post_name__in'] = $query['slug__in'];
            $wp_query_args['posts_per_page'] = count(array_unique($query['slug__in']));
        }

        // This is only needed for the regular templates/template parts post type listing and editor.
        if(isset($query['wp_id']))
        {
            $wp_query_args['p'] = $query['wp_id'];
        }
        else
        {
            $wp_query_args['post_status'] = 'publish';
        }

        $template_query = new WP_Query($wp_query_args);
        $query_result = [];
        foreach($template_query->posts as $post)
        {
            $template = _build_block_template_result_from_post($post);

            if(is_wp_error($template))
            {
                continue;
            }

            if($post_type && ! $template->is_custom)
            {
                continue;
            }

            if($post_type && isset($template->post_types) && ! in_array($post_type, $template->post_types, true))
            {
                continue;
            }

            $query_result[] = $template;
        }

        if(! isset($query['wp_id']))
        {
            /*
             * If the query has found some use templates, those have priority
             * over the theme-provided ones, so we skip querying and building them.
             */
            $query['slug__not_in'] = wp_list_pluck($query_result, 'slug');
            $template_files = _get_block_templates_files($template_type, $query);
            foreach($template_files as $template_file)
            {
                $query_result[] = _build_block_template_result_from_file($template_file, $template_type);
            }
        }

        return apply_filters('get_block_templates', $query_result, $query, $template_type);
    }

    function get_block_template($id, $template_type = 'wp_template')
    {
        $block_template = apply_filters('pre_get_block_template', null, $id, $template_type);
        if(! is_null($block_template))
        {
            return $block_template;
        }

        $parts = explode('//', $id, 2);
        if(count($parts) < 2)
        {
            return null;
        }
        [$theme, $slug] = $parts;
        $wp_query_args = [
            'post_name__in' => [$slug],
            'post_type' => $template_type,
            'post_status' => ['auto-draft', 'draft', 'publish', 'trash'],
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'tax_query' => [
                [
                    'taxonomy' => 'wp_theme',
                    'field' => 'name',
                    'terms' => $theme,
                ],
            ],
        ];
        $template_query = new WP_Query($wp_query_args);
        $posts = $template_query->posts;

        if(count($posts) > 0)
        {
            $template = _build_block_template_result_from_post($posts[0]);

            if(! is_wp_error($template))
            {
                return $template;
            }
        }

        $block_template = get_block_file_template($id, $template_type);

        return apply_filters('get_block_template', $block_template, $id, $template_type);
    }

    function get_block_file_template($id, $template_type = 'wp_template')
    {
        $block_template = apply_filters('pre_get_block_file_template', null, $id, $template_type);
        if(! is_null($block_template))
        {
            return $block_template;
        }

        $parts = explode('//', $id, 2);
        if(count($parts) < 2)
        {
            return apply_filters('get_block_file_template', null, $id, $template_type);
        }
        [$theme, $slug] = $parts;

        if(get_stylesheet() !== $theme)
        {
            return apply_filters('get_block_file_template', null, $id, $template_type);
        }

        $template_file = _get_block_template_file($template_type, $slug);
        if(null === $template_file)
        {
            return apply_filters('get_block_file_template', null, $id, $template_type);
        }

        $block_template = _build_block_template_result_from_file($template_file, $template_type);

        return apply_filters('get_block_file_template', $block_template, $id, $template_type);
    }

    function block_template_part($part)
    {
        $template_part = get_block_template(get_stylesheet().'//'.$part, 'wp_template_part');
        if(! $template_part || empty($template_part->content))
        {
            return;
        }
        echo do_blocks($template_part->content);
    }

    function block_header_area()
    {
        block_template_part('header');
    }

    function block_footer_area()
    {
        block_template_part('footer');
    }

    function wp_is_theme_directory_ignored($path)
    {
        $directories_to_ignore = ['.DS_Store', '.svn', '.git', '.hg', '.bzr', 'node_modules', 'vendor'];

        foreach($directories_to_ignore as $directory)
        {
            if(str_starts_with($path, $directory))
            {
                return true;
            }
        }

        return false;
    }

    function wp_generate_block_templates_export_file()
    {
        global $wp_version;

        if(! class_exists('ZipArchive'))
        {
            return new WP_Error('missing_zip_package', __('Zip Export not supported.'));
        }

        $obscura = wp_generate_password(12, false, false);
        $theme_name = basename(get_stylesheet());
        $filename = get_temp_dir().$theme_name.$obscura.'.zip';

        $zip = new ZipArchive();
        if(true !== $zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE))
        {
            return new WP_Error('unable_to_create_zip', __('Unable to open export file (archive) for writing.'));
        }

        $zip->addEmptyDir('templates');
        $zip->addEmptyDir('parts');

        // Get path of the theme.
        $theme_path = wp_normalize_path(get_stylesheet_directory());

        // Create recursive directory iterator.
        $theme_files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($theme_path), RecursiveIteratorIterator::LEAVES_ONLY);

        // Make a copy of the current theme.
        foreach($theme_files as $file)
        {
            // Skip directories as they are added automatically.
            if(! $file->isDir())
            {
                // Get real and relative path for current file.
                $file_path = wp_normalize_path($file);
                $relative_path = substr($file_path, strlen($theme_path) + 1);

                if(! wp_is_theme_directory_ignored($relative_path))
                {
                    $zip->addFile($file_path, $relative_path);
                }
            }
        }

        // Load templates into the zip file.
        $templates = get_block_templates();
        foreach($templates as $template)
        {
            $template->content = _remove_theme_attribute_in_block_template_content($template->content);

            $zip->addFromString('templates/'.$template->slug.'.html', $template->content);
        }

        // Load template parts into the zip file.
        $template_parts = get_block_templates([], 'wp_template_part');
        foreach($template_parts as $template_part)
        {
            $zip->addFromString('parts/'.$template_part->slug.'.html', $template_part->content);
        }

        // Load theme.json into the zip file.
        $tree = WP_Theme_JSON_Resolver::get_theme_data([], ['with_supports' => false]);
        // Merge with user data.
        $tree->merge(WP_Theme_JSON_Resolver::get_user_data());

        $theme_json_raw = $tree->get_data();
        // If a version is defined, add a schema.
        if($theme_json_raw['version'])
        {
            $theme_json_version = 'wp/'.substr($wp_version, 0, 3);
            $schema = ['$schema' => 'https://schemas.wp.org/'.$theme_json_version.'/theme.json'];
            $theme_json_raw = array_merge($schema, $theme_json_raw);
        }

        // Convert to a string.
        $theme_json_encoded = wp_json_encode($theme_json_raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Replace 4 spaces with a tab.
        $theme_json_tabbed = preg_replace('~(?:^|\G)\h{4}~m', "\t", $theme_json_encoded);

        // Add the theme.json file to the zip.
        $zip->addFromString('theme.json', $theme_json_tabbed);

        // Save changes to the zip file.
        $zip->close();

        return $filename;
    }

    function get_template_hierarchy($slug, $is_custom = false, $template_prefix = '')
    {
        if('index' === $slug)
        {
            return ['index'];
        }
        if($is_custom)
        {
            return ['page', 'singular', 'index'];
        }
        if('front-page' === $slug)
        {
            return ['front-page', 'home', 'index'];
        }

        $matches = [];

        $template_hierarchy = [$slug];
        // Most default templates don't have `$template_prefix` assigned.
        if(! empty($template_prefix))
        {
            [$type] = explode('-', $template_prefix);
            // We need these checks because we always add the `$slug` above.
            if(! in_array($template_prefix, [$slug, $type], true))
            {
                $template_hierarchy[] = $template_prefix;
            }
            if($slug !== $type)
            {
                $template_hierarchy[] = $type;
            }
        }
        elseif(preg_match('/^(author|category|archive|tag|page)-.+$/', $slug, $matches))
        {
            $template_hierarchy[] = $matches[1];
        }
        elseif(preg_match('/^(taxonomy|single)-(.+)$/', $slug, $matches))
        {
            $type = $matches[1];
            $slug_remaining = $matches[2];

            $items = 'single' === $type ? get_post_types() : get_taxonomies();
            foreach($items as $item)
            {
                if(! str_starts_with($slug_remaining, $item))
                {
                    continue;
                }

                // If $slug_remaining is equal to $post_type or $taxonomy we have
                // the single-$post_type template or the taxonomy-$taxonomy template.
                if($slug_remaining === $item)
                {
                    $template_hierarchy[] = $type;
                    break;
                }

                // If $slug_remaining is single-$post_type-$slug template.
                if(strlen($slug_remaining) > strlen($item) + 1)
                {
                    $template_hierarchy[] = "$type-$item";
                    $template_hierarchy[] = $type;
                    break;
                }
            }
        }
        // Handle `archive` template.
        if(str_starts_with($slug, 'author') || str_starts_with($slug, 'taxonomy') || str_starts_with($slug, 'category') || str_starts_with($slug, 'tag') || 'date' === $slug)
        {
            $template_hierarchy[] = 'archive';
        }
        // Handle `single` template.
        if('attachment' === $slug)
        {
            $template_hierarchy[] = 'single';
        }
        // Handle `singular` template.
        if(str_starts_with($slug, 'single') || str_starts_with($slug, 'page') || 'attachment' === $slug)
        {
            $template_hierarchy[] = 'singular';
        }
        $template_hierarchy[] = 'index';

        return $template_hierarchy;
    }
