<?php

    function get_default_block_categories()
    {
        return [
            [
                'slug' => 'text',
                'title' => _x('Text', 'block category'),
                'icon' => null,
            ],
            [
                'slug' => 'media',
                'title' => _x('Media', 'block category'),
                'icon' => null,
            ],
            [
                'slug' => 'design',
                'title' => _x('Design', 'block category'),
                'icon' => null,
            ],
            [
                'slug' => 'widgets',
                'title' => _x('Widgets', 'block category'),
                'icon' => null,
            ],
            [
                'slug' => 'theme',
                'title' => _x('Theme', 'block category'),
                'icon' => null,
            ],
            [
                'slug' => 'embed',
                'title' => _x('Embeds', 'block category'),
                'icon' => null,
            ],
            [
                'slug' => 'reusable',
                'title' => _x('Patterns', 'block category'),
                'icon' => null,
            ],
        ];
    }

    function get_block_categories($post_or_block_editor_context)
    {
        $block_categories = get_default_block_categories();
        $block_editor_context = $post_or_block_editor_context instanceof WP_Post ? new WP_Block_Editor_Context([
                                                                                                                   'post' => $post_or_block_editor_context,
                                                                                                               ]) : $post_or_block_editor_context;

        $block_categories = apply_filters('block_categories_all', $block_categories, $block_editor_context);

        if(! empty($block_editor_context->post))
        {
            $post = $block_editor_context->post;

            $block_categories = apply_filters_deprecated('block_categories', [
                $block_categories,
                $post
            ],                                           '5.8.0', 'block_categories_all');
        }

        return $block_categories;
    }

    function get_allowed_block_types($block_editor_context)
    {
        $allowed_block_types = true;

        $allowed_block_types = apply_filters('allowed_block_types_all', $allowed_block_types, $block_editor_context);

        if(! empty($block_editor_context->post))
        {
            $post = $block_editor_context->post;

            $allowed_block_types = apply_filters_deprecated('allowed_block_types', [
                $allowed_block_types,
                $post
            ],                                              '5.8.0', 'allowed_block_types_all');
        }

        return $allowed_block_types;
    }

    function get_default_block_editor_settings()
    {
        // Media settings.

        // wp_max_upload_size() can be expensive, so only call it when relevant for the current user.
        $max_upload_size = 0;
        if(current_user_can('upload_files'))
        {
            $max_upload_size = wp_max_upload_size();
            if(! $max_upload_size)
            {
                $max_upload_size = 0;
            }
        }

        $image_size_names = apply_filters('image_size_names_choose', [
            'thumbnail' => __('Thumbnail'),
            'medium' => __('Medium'),
            'large' => __('Large'),
            'full' => __('Full Size'),
        ]);

        $available_image_sizes = [];
        foreach($image_size_names as $image_size_slug => $image_size_name)
        {
            $available_image_sizes[] = [
                'slug' => $image_size_slug,
                'name' => $image_size_name,
            ];
        }

        $default_size = get_option('image_default_size', 'large');
        $image_default_size = array_key_exists($default_size, $image_size_names) ? $default_size : 'large';

        $image_dimensions = [];
        $all_sizes = wp_get_registered_image_subsizes();
        foreach($available_image_sizes as $size)
        {
            $key = $size['slug'];
            if(isset($all_sizes[$key]))
            {
                $image_dimensions[$key] = $all_sizes[$key];
            }
        }

        // These styles are used if the "no theme styles" options is triggered or on
        // themes without their own editor styles.
        $default_editor_styles_file = ABSPATH.WPINC.'/css/dist/block-editor/default-editor-styles.css';

        static $default_editor_styles_file_contents = false;
        if(! $default_editor_styles_file_contents && file_exists($default_editor_styles_file))
        {
            $default_editor_styles_file_contents = file_get_contents($default_editor_styles_file);
        }

        $default_editor_styles = [];
        if($default_editor_styles_file_contents)
        {
            $default_editor_styles = [
                ['css' => $default_editor_styles_file_contents],
            ];
        }

        $editor_settings = [
            'alignWide' => get_theme_support('align-wide'),
            'allowedBlockTypes' => true,
            'allowedMimeTypes' => get_allowed_mime_types(),
            'defaultEditorStyles' => $default_editor_styles,
            'blockCategories' => get_default_block_categories(),
            'isRTL' => is_rtl(),
            'imageDefaultSize' => $image_default_size,
            'imageDimensions' => $image_dimensions,
            'imageEditing' => true,
            'imageSizes' => $available_image_sizes,
            'maxUploadFileSize' => $max_upload_size,
            // The following flag is required to enable the new Gallery block format on the mobile apps in 5.9.
            '__unstableGalleryWithImageBlocks' => true,
        ];

        $theme_settings = get_classic_theme_supports_block_editor_settings();
        foreach($theme_settings as $key => $value)
        {
            $editor_settings[$key] = $value;
        }

        return $editor_settings;
    }

    function get_legacy_widget_block_editor_settings()
    {
        $editor_settings = [];

        $editor_settings['widgetTypesToHideFromLegacyWidgetBlock'] = apply_filters('widget_types_to_hide_from_legacy_widget_block', [
            'pages',
            'calendar',
            'archives',
            'media_audio',
            'media_image',
            'media_gallery',
            'media_video',
            'search',
            'text',
            'categories',
            'recent-posts',
            'recent-comments',
            'rss',
            'tag_cloud',
            'custom_html',
            'block',
        ]);

        return $editor_settings;
    }

    function _wp_get_iframed_editor_assets()
    {
        global $wp_styles, $wp_scripts, $pagenow;

        // Keep track of the styles and scripts instance to restore later.
        $current_wp_styles = $wp_styles;
        $current_wp_scripts = $wp_scripts;

        // Create new instances to collect the assets.
        $wp_styles = new WP_Styles();
        $wp_scripts = new WP_Scripts();

        /*
         * Register all currently registered styles and scripts. The actions that
         * follow enqueue assets, but don't necessarily register them.
         */
        $wp_styles->registered = $current_wp_styles->registered;
        $wp_scripts->registered = $current_wp_scripts->registered;

        /*
         * We generally do not need reset styles for the iframed editor.
         * However, if it's a classic theme, margins will be added to every block,
         * which is reset specifically for list items, so classic themes rely on
         * these reset styles.
         */
        $wp_styles->done = wp_theme_has_theme_json() ? ['wp-reset-editor-styles'] : [];

        wp_enqueue_script('wp-polyfill');
        // Enqueue the `editorStyle` handles for all core block, and dependencies.
        wp_enqueue_style('wp-edit-blocks');

        if('site-editor.php' === $pagenow)
        {
            wp_enqueue_style('wp-edit-site');
        }

        if(current_theme_supports('wp-block-styles'))
        {
            wp_enqueue_style('wp-block-library-theme');
        }

        /*
         * We don't want to load EDITOR scripts in the iframe, only enqueue
         * front-end assets for the content.
         */
        add_filter('should_load_block_editor_scripts_and_styles', '__return_false');
        do_action('enqueue_block_assets');
        remove_filter('should_load_block_editor_scripts_and_styles', '__return_false');

        $block_registry = WP_Block_Type_Registry::get_instance();

        /*
         * Additionally, do enqueue `editorStyle` assets for all blocks, which
         * contains editor-only styling for blocks (editor content).
         */
        foreach($block_registry->get_all_registered() as $block_type)
        {
            if(isset($block_type->editor_style_handles) && is_array($block_type->editor_style_handles))
            {
                foreach($block_type->editor_style_handles as $style_handle)
                {
                    wp_enqueue_style($style_handle);
                }
            }
        }

        ob_start();
        wp_print_styles();
        wp_print_font_faces();
        $styles = ob_get_clean();

        ob_start();
        wp_print_head_scripts();
        wp_print_footer_scripts();
        $scripts = ob_get_clean();

        // Restore the original instances.
        $wp_styles = $current_wp_styles;
        $wp_scripts = $current_wp_scripts;

        return compact('styles', 'scripts');
    }

    function wp_get_first_block($blocks, $block_name)
    {
        foreach($blocks as $block)
        {
            if($block_name === $block['blockName'])
            {
                return $block;
            }
            if(! empty($block['innerBlocks']))
            {
                $found_block = wp_get_first_block($block['innerBlocks'], $block_name);

                if(! empty($found_block))
                {
                    return $found_block;
                }
            }
        }

        return [];
    }

    function wp_get_post_content_block_attributes()
    {
        global $post_ID;

        $is_block_theme = wp_is_block_theme();

        if(! $is_block_theme || ! $post_ID)
        {
            return [];
        }

        $template_slug = get_page_template_slug($post_ID);

        if(! $template_slug)
        {
            $post_slug = 'singular';
            $page_slug = 'singular';
            $template_types = get_block_templates();

            foreach($template_types as $template_type)
            {
                if('page' === $template_type->slug)
                {
                    $page_slug = 'page';
                }
                if('single' === $template_type->slug)
                {
                    $post_slug = 'single';
                }
            }

            $what_post_type = get_post_type($post_ID);
            switch($what_post_type)
            {
                case 'page':
                    $template_slug = $page_slug;
                    break;
                default:
                    $template_slug = $post_slug;
                    break;
            }
        }

        $current_template = get_block_templates(['slug__in' => [$template_slug]]);

        if(! empty($current_template))
        {
            $template_blocks = parse_blocks($current_template[0]->content);
            $post_content_block = wp_get_first_block($template_blocks, 'core/post-content');

            if(! empty($post_content_block['attrs']))
            {
                return $post_content_block['attrs'];
            }
        }

        return [];
    }

    function get_block_editor_settings(array $custom_settings, $block_editor_context)
    {
        $editor_settings = array_merge(get_default_block_editor_settings(), [
            'allowedBlockTypes' => get_allowed_block_types($block_editor_context),
            'blockCategories' => get_block_categories($block_editor_context),
        ],                             $custom_settings);

        $global_styles = [];
        $presets = [
            [
                'css' => 'variables',
                '__unstableType' => 'presets',
                'isGlobalStyles' => true,
            ],
            [
                'css' => 'presets',
                '__unstableType' => 'presets',
                'isGlobalStyles' => true,
            ],
        ];
        foreach($presets as $preset_style)
        {
            $actual_css = wp_get_global_stylesheet([$preset_style['css']]);
            if('' !== $actual_css)
            {
                $preset_style['css'] = $actual_css;
                $global_styles[] = $preset_style;
            }
        }

        if(wp_theme_has_theme_json())
        {
            $block_classes = [
                'css' => 'styles',
                '__unstableType' => 'theme',
                'isGlobalStyles' => true,
            ];
            $actual_css = wp_get_global_stylesheet([$block_classes['css']]);
            if('' !== $actual_css)
            {
                $block_classes['css'] = $actual_css;
                $global_styles[] = $block_classes;
            }

            /*
             * Add the custom CSS as a separate stylesheet so any invalid CSS
             * entered by users does not break other global styles.
             */
            $global_styles[] = [
                'css' => wp_get_global_styles_custom_css(),
                '__unstableType' => 'user',
                'isGlobalStyles' => true,
            ];
        }
        else
        {
            // If there is no `theme.json` file, ensure base layout styles are still available.
            $block_classes = [
                'css' => 'base-layout-styles',
                '__unstableType' => 'base-layout',
                'isGlobalStyles' => true,
            ];
            $actual_css = wp_get_global_stylesheet([$block_classes['css']]);
            if('' !== $actual_css)
            {
                $block_classes['css'] = $actual_css;
                $global_styles[] = $block_classes;
            }
        }

        $editor_settings['styles'] = array_merge($global_styles, get_block_editor_theme_styles());

        $editor_settings['__experimentalFeatures'] = wp_get_global_settings();
        // These settings may need to be updated based on data coming from theme.json sources.
        if(isset($editor_settings['__experimentalFeatures']['color']['palette']))
        {
            $colors_by_origin = $editor_settings['__experimentalFeatures']['color']['palette'];
            $editor_settings['colors'] = isset($colors_by_origin['custom']) ? $colors_by_origin['custom'] : (isset($colors_by_origin['theme']) ? $colors_by_origin['theme'] : $colors_by_origin['default']);
        }
        if(isset($editor_settings['__experimentalFeatures']['color']['gradients']))
        {
            $gradients_by_origin = $editor_settings['__experimentalFeatures']['color']['gradients'];
            $editor_settings['gradients'] = isset($gradients_by_origin['custom']) ? $gradients_by_origin['custom'] : (isset($gradients_by_origin['theme']) ? $gradients_by_origin['theme'] : $gradients_by_origin['default']);
        }
        if(isset($editor_settings['__experimentalFeatures']['typography']['fontSizes']))
        {
            $font_sizes_by_origin = $editor_settings['__experimentalFeatures']['typography']['fontSizes'];
            $editor_settings['fontSizes'] = isset($font_sizes_by_origin['custom']) ? $font_sizes_by_origin['custom'] : (isset($font_sizes_by_origin['theme']) ? $font_sizes_by_origin['theme'] : $font_sizes_by_origin['default']);
        }
        if(isset($editor_settings['__experimentalFeatures']['color']['custom']))
        {
            $editor_settings['disableCustomColors'] = ! $editor_settings['__experimentalFeatures']['color']['custom'];
            unset($editor_settings['__experimentalFeatures']['color']['custom']);
        }
        if(isset($editor_settings['__experimentalFeatures']['color']['customGradient']))
        {
            $editor_settings['disableCustomGradients'] = ! $editor_settings['__experimentalFeatures']['color']['customGradient'];
            unset($editor_settings['__experimentalFeatures']['color']['customGradient']);
        }
        if(isset($editor_settings['__experimentalFeatures']['typography']['customFontSize']))
        {
            $editor_settings['disableCustomFontSizes'] = ! $editor_settings['__experimentalFeatures']['typography']['customFontSize'];
            unset($editor_settings['__experimentalFeatures']['typography']['customFontSize']);
        }
        if(isset($editor_settings['__experimentalFeatures']['typography']['lineHeight']))
        {
            $editor_settings['enableCustomLineHeight'] = $editor_settings['__experimentalFeatures']['typography']['lineHeight'];
            unset($editor_settings['__experimentalFeatures']['typography']['lineHeight']);
        }
        if(isset($editor_settings['__experimentalFeatures']['spacing']['units']))
        {
            $editor_settings['enableCustomUnits'] = $editor_settings['__experimentalFeatures']['spacing']['units'];
            unset($editor_settings['__experimentalFeatures']['spacing']['units']);
        }
        if(isset($editor_settings['__experimentalFeatures']['spacing']['padding']))
        {
            $editor_settings['enableCustomSpacing'] = $editor_settings['__experimentalFeatures']['spacing']['padding'];
            unset($editor_settings['__experimentalFeatures']['spacing']['padding']);
        }
        if(isset($editor_settings['__experimentalFeatures']['spacing']['customSpacingSize']))
        {
            $editor_settings['disableCustomSpacingSizes'] = ! $editor_settings['__experimentalFeatures']['spacing']['customSpacingSize'];
            unset($editor_settings['__experimentalFeatures']['spacing']['customSpacingSize']);
        }

        if(isset($editor_settings['__experimentalFeatures']['spacing']['spacingSizes']))
        {
            $spacing_sizes_by_origin = $editor_settings['__experimentalFeatures']['spacing']['spacingSizes'];
            $editor_settings['spacingSizes'] = isset($spacing_sizes_by_origin['custom']) ? $spacing_sizes_by_origin['custom'] : (isset($spacing_sizes_by_origin['theme']) ? $spacing_sizes_by_origin['theme'] : $spacing_sizes_by_origin['default']);
        }

        $editor_settings['__unstableResolvedAssets'] = _wp_get_iframed_editor_assets();
        $editor_settings['__unstableIsBlockBasedTheme'] = wp_is_block_theme();
        $editor_settings['localAutosaveInterval'] = 15;
        $editor_settings['disableLayoutStyles'] = current_theme_supports('disable-layout-styles');
        $editor_settings['__experimentalDiscussionSettings'] = [
            'commentOrder' => get_option('comment_order'),
            'commentsPerPage' => get_option('comments_per_page'),
            'defaultCommentsPage' => get_option('default_comments_page'),
            'pageComments' => get_option('page_comments'),
            'threadComments' => get_option('thread_comments'),
            'threadCommentsDepth' => get_option('thread_comments_depth'),
            'defaultCommentStatus' => get_option('default_comment_status'),
            'avatarURL' => get_avatar_url('', [
                'size' => 96,
                'force_default' => true,
                'default' => get_option('avatar_default'),
            ]),
        ];

        $post_content_block_attributes = wp_get_post_content_block_attributes();

        if(! empty($post_content_block_attributes))
        {
            $editor_settings['postContentAttributes'] = $post_content_block_attributes;
        }

        $editor_settings = apply_filters('block_editor_settings_all', $editor_settings, $block_editor_context);

        if(! empty($block_editor_context->post))
        {
            $post = $block_editor_context->post;

            $editor_settings = apply_filters_deprecated('block_editor_settings', [
                $editor_settings,
                $post
            ],                                          '5.8.0', 'block_editor_settings_all');
        }

        return $editor_settings;
    }

    function block_editor_rest_api_preload(array $preload_paths, $block_editor_context)
    {
        global $post, $wp_scripts, $wp_styles;

        $preload_paths = apply_filters('block_editor_rest_api_preload_paths', $preload_paths, $block_editor_context);

        if(! empty($block_editor_context->post))
        {
            $selected_post = $block_editor_context->post;

            $preload_paths = apply_filters_deprecated('block_editor_preload_paths', [
                $preload_paths,
                $selected_post
            ],                                        '5.8.0', 'block_editor_rest_api_preload_paths');
        }

        if(empty($preload_paths))
        {
            return;
        }

        /*
         * Ensure the global $post, $wp_scripts, and $wp_styles remain the same after
         * API data is preloaded.
         * Because API preloading can call the_content and other filters, plugins
         * can unexpectedly modify the global $post or enqueue assets which are not
         * intended for the block editor.
         */
        $backup_global_post = ! empty($post) ? clone $post : $post;
        $backup_wp_scripts = $wp_scripts !== null ? clone $wp_scripts : $wp_scripts;
        $backup_wp_styles = $wp_styles !== null ? clone $wp_styles : $wp_styles;

        foreach($preload_paths as &$path)
        {
            if(is_string($path) && ! str_starts_with($path, '/'))
            {
                $path = '/'.$path;
                continue;
            }

            if(is_array($path) && is_string($path[0]) && ! str_starts_with($path[0], '/'))
            {
                $path[0] = '/'.$path[0];
            }
        }

        unset($path);

        $preload_data = array_reduce($preload_paths, 'rest_preload_api_request', []);

        // Restore the global $post, $wp_scripts, and $wp_styles as they were before API preloading.
        $post = $backup_global_post;
        $wp_scripts = $backup_wp_scripts;
        $wp_styles = $backup_wp_styles;

        wp_add_inline_script('wp-api-fetch', sprintf('wp.apiFetch.use( wp.apiFetch.createPreloadingMiddleware( %s ) );', wp_json_encode($preload_data)), 'after');
    }

    function get_block_editor_theme_styles()
    {
        global $editor_styles;

        $styles = [];

        if($editor_styles && current_theme_supports('editor-styles'))
        {
            foreach($editor_styles as $style)
            {
                if(preg_match('~^(https?:)?//~', $style))
                {
                    $response = wp_remote_get($style);
                    if(! is_wp_error($response))
                    {
                        $styles[] = [
                            'css' => wp_remote_retrieve_body($response),
                            '__unstableType' => 'theme',
                            'isGlobalStyles' => false,
                        ];
                    }
                }
                else
                {
                    $file = get_theme_file_path($style);
                    if(is_file($file))
                    {
                        $styles[] = [
                            'css' => file_get_contents($file),
                            'baseURL' => get_theme_file_uri($style),
                            '__unstableType' => 'theme',
                            'isGlobalStyles' => false,
                        ];
                    }
                }
            }
        }

        return $styles;
    }

    function get_classic_theme_supports_block_editor_settings()
    {
        $theme_settings = [
            'disableCustomColors' => get_theme_support('disable-custom-colors'),
            'disableCustomFontSizes' => get_theme_support('disable-custom-font-sizes'),
            'disableCustomGradients' => get_theme_support('disable-custom-gradients'),
            'disableLayoutStyles' => get_theme_support('disable-layout-styles'),
            'enableCustomLineHeight' => get_theme_support('custom-line-height'),
            'enableCustomSpacing' => get_theme_support('custom-spacing'),
            'enableCustomUnits' => get_theme_support('custom-units'),
        ];

        // Theme settings.
        $color_palette = current((array) get_theme_support('editor-color-palette'));
        if(false !== $color_palette)
        {
            $theme_settings['colors'] = $color_palette;
        }

        $font_sizes = current((array) get_theme_support('editor-font-sizes'));
        if(false !== $font_sizes)
        {
            $theme_settings['fontSizes'] = $font_sizes;
        }

        $gradient_presets = current((array) get_theme_support('editor-gradient-presets'));
        if(false !== $gradient_presets)
        {
            $theme_settings['gradients'] = $gradient_presets;
        }

        return $theme_settings;
    }
