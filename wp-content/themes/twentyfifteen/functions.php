<?php

    if(! isset($content_width))
    {
        $content_width = 660;
    }

    if(version_compare($GLOBALS['wp_version'], '4.1-alpha', '<'))
    {
        require get_template_directory().'/inc/back-compat.php';
    }

    if(! function_exists('twentyfifteen_setup')) :

        function twentyfifteen_setup()
        {
            /*
             * Make theme available for translation.
             * Translations can be filed at WordPress.org. See: https://translate.wordpress.org/projects/wp-themes/twentyfifteen
             * If you're building a theme based on twentyfifteen, use a find and replace
             * to change 'twentyfifteen' to the name of your theme in all the template files.
             *
             * Manual loading of text domain is not required after the introduction of
             * just in time translation loading in WordPress version 4.6.
             *
             * @ticket 58318
             */

            if(version_compare($GLOBALS['wp_version'], '4.6', '<'))
            {
                load_theme_textdomain('twentyfifteen');
            }

            // Add default posts and comments RSS feed links to head.
            add_theme_support('automatic-feed-links');

            /*
             * Let WordPress manage the document title.
             * By adding theme support, we declare that this theme does not use a
             * hard-coded <title> tag in the document head, and expect WordPress to
             * provide it for us.
             */
            add_theme_support('title-tag');

            /*
             * Enable support for Post Thumbnails on posts and pages.
             *
             * See: https://developer.wordpress.org/reference/functions/add_theme_support/#post-thumbnails
             */
            add_theme_support('post-thumbnails');
            set_post_thumbnail_size(825, 510, true);

            // This theme uses wp_nav_menu() in two locations.
            register_nav_menus([
                                   'primary' => __('Primary Menu', 'twentyfifteen'),
                                   'social' => __('Social Links Menu', 'twentyfifteen'),
                               ]);

            /*
             * Switch default core markup for search form, comment form, and comments
             * to output valid HTML5.
             */
            add_theme_support('html5', [
                'search-form',
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
                'script',
                'style',
                'navigation-widgets',
            ]);

            /*
             * Enable support for Post Formats.
             *
             * See: https://wordpress.org/documentation/article/post-formats/
             */
            add_theme_support('post-formats', [
                'aside',
                'image',
                'video',
                'quote',
                'link',
                'gallery',
                'status',
                'audio',
                'chat',
            ]);

            /*
             * Enable support for custom logo.
             *
             * @since Twenty Fifteen 1.5
             */
            add_theme_support('custom-logo', [
                'height' => 248,
                'width' => 248,
                'flex-height' => true,
            ]);

            $color_scheme = twentyfifteen_get_color_scheme();
            $default_color = trim($color_scheme[0], '#');

            // Setup the WordPress core custom background feature.

            add_theme_support(
                'custom-background', apply_filters('twentyfifteen_custom_background_args', [
                                       'default-color' => $default_color,
                                       'default-attachment' => 'fixed',
                                   ])
            );

            /*
             * This theme styles the visual editor to resemble the theme style,
             * specifically font, colors, icons, and column width. When fonts are
             * self-hosted, the theme directory needs to be removed first.
             */
            $font_stylesheet = str_replace([
                                               get_template_directory_uri().'/',
                                               get_stylesheet_directory_uri().'/',
                                           ], '', twentyfifteen_fonts_url());
            add_editor_style(['css/editor-style.css', 'genericons/genericons.css', $font_stylesheet]);

            // Load regular editor styles into the new block-based editor.
            add_theme_support('editor-styles');

            // Load default block styles.
            add_theme_support('wp-block-styles');

            // Add support for responsive embeds.
            add_theme_support('responsive-embeds');

            // Add support for custom color scheme.
            add_theme_support('editor-color-palette', [
                [
                    'name' => __('Dark Gray', 'twentyfifteen'),
                    'slug' => 'dark-gray',
                    'color' => '#111',
                ],
                [
                    'name' => __('Light Gray', 'twentyfifteen'),
                    'slug' => 'light-gray',
                    'color' => '#f1f1f1',
                ],
                [
                    'name' => __('White', 'twentyfifteen'),
                    'slug' => 'white',
                    'color' => '#fff',
                ],
                [
                    'name' => __('Yellow', 'twentyfifteen'),
                    'slug' => 'yellow',
                    'color' => '#f4ca16',
                ],
                [
                    'name' => __('Dark Brown', 'twentyfifteen'),
                    'slug' => 'dark-brown',
                    'color' => '#352712',
                ],
                [
                    'name' => __('Medium Pink', 'twentyfifteen'),
                    'slug' => 'medium-pink',
                    'color' => '#e53b51',
                ],
                [
                    'name' => __('Light Pink', 'twentyfifteen'),
                    'slug' => 'light-pink',
                    'color' => '#ffe5d1',
                ],
                [
                    'name' => __('Dark Purple', 'twentyfifteen'),
                    'slug' => 'dark-purple',
                    'color' => '#2e2256',
                ],
                [
                    'name' => __('Purple', 'twentyfifteen'),
                    'slug' => 'purple',
                    'color' => '#674970',
                ],
                [
                    'name' => __('Blue Gray', 'twentyfifteen'),
                    'slug' => 'blue-gray',
                    'color' => '#22313f',
                ],
                [
                    'name' => __('Bright Blue', 'twentyfifteen'),
                    'slug' => 'bright-blue',
                    'color' => '#55c3dc',
                ],
                [
                    'name' => __('Light Blue', 'twentyfifteen'),
                    'slug' => 'light-blue',
                    'color' => '#e9f2f9',
                ],
            ]);

            // Add support for custom color scheme.
            add_theme_support('editor-gradient-presets', [
                [
                    'name' => __('Dark Gray Gradient', 'twentyfifteen'),
                    'slug' => 'dark-gray-gradient-gradient',
                    'gradient' => 'linear-gradient(90deg, rgba(17,17,17,1) 0%, rgba(42,42,42,1) 100%)',
                ],
                [
                    'name' => __('Light Gray Gradient', 'twentyfifteen'),
                    'slug' => 'light-gray-gradient',
                    'gradient' => 'linear-gradient(90deg, rgba(241,241,241,1) 0%, rgba(215,215,215,1) 100%)',
                ],
                [
                    'name' => __('White Gradient', 'twentyfifteen'),
                    'slug' => 'white-gradient',
                    'gradient' => 'linear-gradient(90deg, rgba(255,255,255,1) 0%, rgba(230,230,230,1) 100%)',
                ],
                [
                    'name' => __('Yellow Gradient', 'twentyfifteen'),
                    'slug' => 'yellow-gradient',
                    'gradient' => 'linear-gradient(90deg, rgba(244,202,22,1) 0%, rgba(205,168,10,1) 100%)',
                ],
                [
                    'name' => __('Dark Brown Gradient', 'twentyfifteen'),
                    'slug' => 'dark-brown-gradient',
                    'gradient' => 'linear-gradient(90deg, rgba(53,39,18,1) 0%, rgba(91,67,31,1) 100%)',
                ],
                [
                    'name' => __('Medium Pink Gradient', 'twentyfifteen'),
                    'slug' => 'medium-pink-gradient',
                    'gradient' => 'linear-gradient(90deg, rgba(229,59,81,1) 0%, rgba(209,28,51,1) 100%)',
                ],
                [
                    'name' => __('Light Pink Gradient', 'twentyfifteen'),
                    'slug' => 'light-pink-gradient',
                    'gradient' => 'linear-gradient(90deg, rgba(255,229,209,1) 0%, rgba(255,200,158,1) 100%)',
                ],
                [
                    'name' => __('Dark Purple Gradient', 'twentyfifteen'),
                    'slug' => 'dark-purple-gradient',
                    'gradient' => 'linear-gradient(90deg, rgba(46,34,86,1) 0%, rgba(66,48,123,1) 100%)',
                ],
                [
                    'name' => __('Purple Gradient', 'twentyfifteen'),
                    'slug' => 'purple-gradient',
                    'gradient' => 'linear-gradient(90deg, rgba(103,73,112,1) 0%, rgba(131,93,143,1) 100%)',
                ],
                [
                    'name' => __('Blue Gray Gradient', 'twentyfifteen'),
                    'slug' => 'blue-gray-gradient',
                    'gradient' => 'linear-gradient(90deg, rgba(34,49,63,1) 0%, rgba(52,75,96,1) 100%)',
                ],
                [
                    'name' => __('Bright Blue Gradient', 'twentyfifteen'),
                    'slug' => 'bright-blue-gradient',
                    'gradient' => 'linear-gradient(90deg, rgba(85,195,220,1) 0%, rgba(43,180,211,1) 100%)',
                ],
                [
                    'name' => __('Light Blue Gradient', 'twentyfifteen'),
                    'slug' => 'light-blue-gradient',
                    'gradient' => 'linear-gradient(90deg, rgba(233,242,249,1) 0%, rgba(193,218,238,1) 100%)',
                ],
            ]);

            // Indicate widget sidebars can use selective refresh in the Customizer.
            add_theme_support('customize-selective-refresh-widgets');
        }
    endif; // twentyfifteen_setup()
    add_action('after_setup_theme', 'twentyfifteen_setup');

    function twentyfifteen_widgets_init()
    {
        register_sidebar([
                             'name' => __('Widget Area', 'twentyfifteen'),
                             'id' => 'sidebar-1',
                             'description' => __('Add widgets here to appear in your sidebar.', 'twentyfifteen'),
                             'before_widget' => '<aside id="%1$s" class="widget %2$s">',
                             'after_widget' => '</aside>',
                             'before_title' => '<h2 class="widget-title">',
                             'after_title' => '</h2>',
                         ]);
    }

    add_action('widgets_init', 'twentyfifteen_widgets_init');

    if(! function_exists('twentyfifteen_fonts_url')) :

        function twentyfifteen_fonts_url()
        {
            $fonts_url = '';
            $fonts = [];

            /*
             * translators: If there are characters in your language that are not supported
             * by Noto Sans, translate this to 'off'. Do not translate into your own language.
             */
            if('off' !== _x('on', 'Noto Sans font: on or off', 'twentyfifteen'))
            {
                $fonts[] = 'noto-sans';
            }

            /*
             * translators: If there are characters in your language that are not supported
             * by Noto Serif, translate this to 'off'. Do not translate into your own language.
             */
            if('off' !== _x('on', 'Noto Serif font: on or off', 'twentyfifteen'))
            {
                $fonts[] = 'noto-serif';
            }

            /*
             * translators: If there are characters in your language that are not supported
             * by Inconsolata, translate this to 'off'. Do not translate into your own language.
             */
            if('off' !== _x('on', 'Inconsolata font: on or off', 'twentyfifteen'))
            {
                $fonts[] = 'inconsolata';
            }

            if($fonts)
            {
                $fonts_url = get_template_directory_uri().'/assets/fonts/'.implode('-plus-', $fonts).'.css';
            }

            return $fonts_url;
        }
    endif;

    function twentyfifteen_javascript_detection()
    {
        echo "<script>(function(html){html.className = html.className.replace(/\bno-js\b/,'js')})(document.documentElement);</script>\n";
    }

    add_action('wp_head', 'twentyfifteen_javascript_detection', 0);

    function twentyfifteen_scripts()
    {
        // Add custom fonts, used in the main stylesheet.
        $font_version = (0 === strpos((string) twentyfifteen_fonts_url(), get_template_directory_uri().'/')) ? '20230328' : null;
        wp_enqueue_style('twentyfifteen-fonts', twentyfifteen_fonts_url(), [], $font_version);

        // Add Genericons, used in the main stylesheet.
        wp_enqueue_style('genericons', get_template_directory_uri().'/genericons/genericons.css', [], '20201026');

        // Load our main stylesheet.
        wp_enqueue_style('twentyfifteen-style', get_stylesheet_uri(), [], '20230808');

        // Theme block stylesheet.
        wp_enqueue_style('twentyfifteen-block-style', get_template_directory_uri().'/css/blocks.css', ['twentyfifteen-style'], '20230623');

        // Register the Internet Explorer specific stylesheet.
        wp_register_style('twentyfifteen-ie', get_template_directory_uri().'/css/ie.css', ['twentyfifteen-style'], '20220908');
        wp_style_add_data('twentyfifteen-ie', 'conditional', 'lt IE 9');

        // Register the Internet Explorer 7 specific stylesheet.
        wp_register_style('twentyfifteen-ie7', get_template_directory_uri().'/css/ie7.css', ['twentyfifteen-style'], '20141210');
        wp_style_add_data('twentyfifteen-ie7', 'conditional', 'lt IE 8');

        // Skip-link fix is no longer enqueued by default.
        wp_register_script('twentyfifteen-skip-link-focus-fix', get_template_directory_uri().'/js/skip-link-focus-fix.js', [], '20230526', ['in_footer' => true]);

        if(is_singular() && comments_open() && get_option('thread_comments'))
        {
            wp_enqueue_script('comment-reply');
        }

        if(is_singular() && wp_attachment_is_image())
        {
            wp_enqueue_script('twentyfifteen-keyboard-image-navigation', get_template_directory_uri().'/js/keyboard-image-navigation.js', ['jquery'], '20141210');
        }

        wp_enqueue_script('twentyfifteen-script', get_template_directory_uri().'/js/functions.js', ['jquery'], '20221101', [
            'in_footer' => false, // Because involves header.
            'strategy' => 'defer',
        ]);
        wp_localize_script('twentyfifteen-script', 'screenReaderText', [
            /* translators: Hidden accessibility text. */
            'expand' => '<span class="screen-reader-text">'.__('expand child menu', 'twentyfifteen').'</span>',
            /* translators: Hidden accessibility text. */
            'collapse' => '<span class="screen-reader-text">'.__('collapse child menu', 'twentyfifteen').'</span>',
        ]);
    }

    add_action('wp_enqueue_scripts', 'twentyfifteen_scripts');

    function twentyfifteen_block_editor_styles()
    {
        // Block styles.
        wp_enqueue_style('twentyfifteen-block-editor-style', get_template_directory_uri().'/css/editor-blocks.css', [], '20230623');
        // Add custom fonts.
        $font_version = (0 === strpos((string) twentyfifteen_fonts_url(), get_template_directory_uri().'/')) ? '20230328' : null;
        wp_enqueue_style('twentyfifteen-fonts', twentyfifteen_fonts_url(), [], $font_version);
    }

    add_action('enqueue_block_editor_assets', 'twentyfifteen_block_editor_styles');

    function twentyfifteen_resource_hints($urls, $relation_type)
    {
        if(wp_style_is('twentyfifteen-fonts', 'queue') && 'preconnect' === $relation_type)
        {
            if(version_compare($GLOBALS['wp_version'], '4.7-alpha', '>='))
            {
                $urls[] = [
                    'href' => 'https://fonts.gstatic.com',
                    'crossorigin',
                ];
            }
            else
            {
                $urls[] = 'https://fonts.gstatic.com';
            }
        }

        return $urls;
    }

// add_filter( 'wp_resource_hints', 'twentyfifteen_resource_hints', 10, 2 );

    function twentyfifteen_post_nav_background()
    {
        if(! is_single())
        {
            return;
        }

        $previous = (is_attachment()) ? get_post(get_post()->post_parent) : get_adjacent_post(false, '', true);
        $next = get_adjacent_post(false, '', false);
        $css = '';

        if(is_attachment() && 'attachment' === $previous->post_type)
        {
            return;
        }

        if($previous && has_post_thumbnail($previous->ID))
        {
            $prevthumb = wp_get_attachment_image_src(get_post_thumbnail_id($previous->ID), 'post-thumbnail');
            $css .= '
			.post-navigation .nav-previous { background-image: url('.esc_url($prevthumb[0]).'); }
			.post-navigation .nav-previous .post-title, .post-navigation .nav-previous a:hover .post-title, .post-navigation .nav-previous .meta-nav { color: #fff; }
			.post-navigation .nav-previous a:before { background-color: rgba(0, 0, 0, 0.4); }
		';
        }

        if($next && has_post_thumbnail($next->ID))
        {
            $nextthumb = wp_get_attachment_image_src(get_post_thumbnail_id($next->ID), 'post-thumbnail');
            $css .= '
			.post-navigation .nav-next { background-image: url('.esc_url($nextthumb[0]).'); border-top: 0; }
			.post-navigation .nav-next .post-title, .post-navigation .nav-next a:hover .post-title, .post-navigation .nav-next .meta-nav { color: #fff; }
			.post-navigation .nav-next a:before { background-color: rgba(0, 0, 0, 0.4); }
		';
        }

        wp_add_inline_style('twentyfifteen-style', $css);
    }

    add_action('wp_enqueue_scripts', 'twentyfifteen_post_nav_background');

    function twentyfifteen_nav_description($item_output, $item, $depth, $args)
    {
        if('primary' === $args->theme_location && $item->description)
        {
            $item_output = str_replace($args->link_after.'</a>', '<div class="menu-item-description">'.$item->description.'</div>'.$args->link_after.'</a>', $item_output);
        }

        return $item_output;
    }

    add_filter('walker_nav_menu_start_el', 'twentyfifteen_nav_description', 10, 4);

    function twentyfifteen_search_form_modify($html)
    {
        return str_replace('class="search-submit"', 'class="search-submit screen-reader-text"', $html);
    }

    add_filter('get_search_form', 'twentyfifteen_search_form_modify');

    function twentyfifteen_widget_tag_cloud_args($args)
    {
        $args['largest'] = 22;
        $args['smallest'] = 8;
        $args['unit'] = 'pt';
        $args['format'] = 'list';

        return $args;
    }

    add_filter('widget_tag_cloud_args', 'twentyfifteen_widget_tag_cloud_args');

    function twentyfifteen_author_bio_template($template)
    {
        if(is_author())
        {
            $author = get_queried_object();
            if($author instanceof WP_User && 'bio' === $author->user_nicename)
            {
                // Use author templates if exist, fall back to template hierarchy otherwise.
                return locate_template(["author-{$author->ID}.php", 'author.php']);
            }
        }

        return $template;
    }

    add_filter('author_template', 'twentyfifteen_author_bio_template');

    require get_template_directory().'/inc/custom-header.php';

    require get_template_directory().'/inc/template-tags.php';

    require get_template_directory().'/inc/customizer.php';

    require get_template_directory().'/inc/block-patterns.php';
