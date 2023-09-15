<?php

    if(version_compare($GLOBALS['wp_version'], '4.4-alpha', '<'))
    {
        require get_template_directory().'/inc/back-compat.php';
    }

    if(! function_exists('twentysixteen_setup')) :

        function twentysixteen_setup()
        {
            /*
             * Make theme available for translation.
             * Translations can be filed at WordPress.org. See: https://translate.wordpress.org/projects/wp-themes/twentysixteen
             * If you're building a theme based on Twenty Sixteen, use a find and replace
             * to change 'twentysixteen' to the name of your theme in all the template files.
             *
             * Manual loading of text domain is not required after the introduction of
             * just in time translation loading in WordPress version 4.6.
             *
             * @ticket 58318
             */
            if(version_compare($GLOBALS['wp_version'], '4.6', '<'))
            {
                load_theme_textdomain('twentysixteen');
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
             * Enable support for custom logo.
             *
             *  @since Twenty Sixteen 1.2
             */
            add_theme_support('custom-logo', [
                'height' => 240,
                'width' => 240,
                'flex-height' => true,
            ]);

            /*
             * Enable support for Post Thumbnails on posts and pages.
             *
             * @link https://developer.wordpress.org/reference/functions/add_theme_support/#post-thumbnails
             */
            add_theme_support('post-thumbnails');
            set_post_thumbnail_size(1200, 9999);

            // This theme uses wp_nav_menu() in two locations.
            register_nav_menus([
                                   'primary' => __('Primary Menu', 'twentysixteen'),
                                   'social' => __('Social Links Menu', 'twentysixteen'),
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
             * This theme styles the visual editor to resemble the theme style,
             * specifically font, colors, icons, and column width. When fonts are
             * self-hosted, the theme directory needs to be removed first.
             */
            $font_stylesheet = str_replace([
                                               get_template_directory_uri().'/',
                                               get_stylesheet_directory_uri().'/',
                                           ], '', twentysixteen_fonts_url());
            add_editor_style(['css/editor-style.css', $font_stylesheet]);

            // Load regular editor styles into the new block-based editor.
            add_theme_support('editor-styles');

            // Load default block styles.
            add_theme_support('wp-block-styles');

            // Add support for responsive embeds.
            add_theme_support('responsive-embeds');

            // Add support for custom color scheme.
            add_theme_support('editor-color-palette', [
                [
                    'name' => __('Dark Gray', 'twentysixteen'),
                    'slug' => 'dark-gray',
                    'color' => '#1a1a1a',
                ],
                [
                    'name' => __('Medium Gray', 'twentysixteen'),
                    'slug' => 'medium-gray',
                    'color' => '#686868',
                ],
                [
                    'name' => __('Light Gray', 'twentysixteen'),
                    'slug' => 'light-gray',
                    'color' => '#e5e5e5',
                ],
                [
                    'name' => __('White', 'twentysixteen'),
                    'slug' => 'white',
                    'color' => '#fff',
                ],
                [
                    'name' => __('Blue Gray', 'twentysixteen'),
                    'slug' => 'blue-gray',
                    'color' => '#4d545c',
                ],
                [
                    'name' => __('Bright Blue', 'twentysixteen'),
                    'slug' => 'bright-blue',
                    'color' => '#007acc',
                ],
                [
                    'name' => __('Light Blue', 'twentysixteen'),
                    'slug' => 'light-blue',
                    'color' => '#9adffd',
                ],
                [
                    'name' => __('Dark Brown', 'twentysixteen'),
                    'slug' => 'dark-brown',
                    'color' => '#402b30',
                ],
                [
                    'name' => __('Medium Brown', 'twentysixteen'),
                    'slug' => 'medium-brown',
                    'color' => '#774e24',
                ],
                [
                    'name' => __('Dark Red', 'twentysixteen'),
                    'slug' => 'dark-red',
                    'color' => '#640c1f',
                ],
                [
                    'name' => __('Bright Red', 'twentysixteen'),
                    'slug' => 'bright-red',
                    'color' => '#ff675f',
                ],
                [
                    'name' => __('Yellow', 'twentysixteen'),
                    'slug' => 'yellow',
                    'color' => '#ffef8e',
                ],
            ]);

            // Indicate widget sidebars can use selective refresh in the Customizer.
            add_theme_support('customize-selective-refresh-widgets');

            // Add support for custom line height controls.
            add_theme_support('custom-line-height');
        }
    endif; // twentysixteen_setup()
    add_action('after_setup_theme', 'twentysixteen_setup');

    function twentysixteen_content_width()
    {
        $GLOBALS['content_width'] = apply_filters('twentysixteen_content_width', 840);
    }

    add_action('after_setup_theme', 'twentysixteen_content_width', 0);

    function twentysixteen_resource_hints($urls, $relation_type)
    {
        if(wp_style_is('twentysixteen-fonts', 'queue') && 'preconnect' === $relation_type)
        {
            $urls[] = [
                'href' => 'https://fonts.gstatic.com',
                'crossorigin',
            ];
        }

        return $urls;
    }

// add_filter( 'wp_resource_hints', 'twentysixteen_resource_hints', 10, 2 );

    function twentysixteen_widgets_init()
    {
        register_sidebar([
                             'name' => __('Sidebar', 'twentysixteen'),
                             'id' => 'sidebar-1',
                             'description' => __('Add widgets here to appear in your sidebar.', 'twentysixteen'),
                             'before_widget' => '<section id="%1$s" class="widget %2$s">',
                             'after_widget' => '</section>',
                             'before_title' => '<h2 class="widget-title">',
                             'after_title' => '</h2>',
                         ]);

        register_sidebar([
                             'name' => __('Content Bottom 1', 'twentysixteen'),
                             'id' => 'sidebar-2',
                             'description' => __('Appears at the bottom of the content on posts and pages.', 'twentysixteen'),
                             'before_widget' => '<section id="%1$s" class="widget %2$s">',
                             'after_widget' => '</section>',
                             'before_title' => '<h2 class="widget-title">',
                             'after_title' => '</h2>',
                         ]);

        register_sidebar([
                             'name' => __('Content Bottom 2', 'twentysixteen'),
                             'id' => 'sidebar-3',
                             'description' => __('Appears at the bottom of the content on posts and pages.', 'twentysixteen'),
                             'before_widget' => '<section id="%1$s" class="widget %2$s">',
                             'after_widget' => '</section>',
                             'before_title' => '<h2 class="widget-title">',
                             'after_title' => '</h2>',
                         ]);
    }

    add_action('widgets_init', 'twentysixteen_widgets_init');

    if(! function_exists('twentysixteen_fonts_url')) :

        function twentysixteen_fonts_url()
        {
            $fonts_url = '';
            $fonts = [];

            /*
             * translators: If there are characters in your language that are not supported
             * by Merriweather, translate this to 'off'. Do not translate into your own language.
             */
            if('off' !== _x('on', 'Merriweather font: on or off', 'twentysixteen'))
            {
                $fonts[] = 'merriweather';
            }

            /*
             * translators: If there are characters in your language that are not supported
             * by Montserrat, translate this to 'off'. Do not translate into your own language.
             */
            if('off' !== _x('on', 'Montserrat font: on or off', 'twentysixteen'))
            {
                $fonts[] = 'montserrat';
            }

            /*
             * translators: If there are characters in your language that are not supported
             * by Inconsolata, translate this to 'off'. Do not translate into your own language.
             */
            if('off' !== _x('on', 'Inconsolata font: on or off', 'twentysixteen'))
            {
                $fonts[] = 'inconsolata';
            }

            if($fonts)
            {
                $fonts_url = get_template_directory_uri().'/fonts/'.implode('-plus-', $fonts).'.css';
            }

            return $fonts_url;
        }
    endif;

    function twentysixteen_javascript_detection()
    {
        echo "<script>(function(html){html.className = html.className.replace(/\bno-js\b/,'js')})(document.documentElement);</script>\n";
    }

    add_action('wp_head', 'twentysixteen_javascript_detection', 0);

    function twentysixteen_scripts()
    {
        // Add custom fonts, used in the main stylesheet.
        $font_version = (0 === strpos((string) twentysixteen_fonts_url(), get_template_directory_uri().'/')) ? '20230328' : null;
        wp_enqueue_style('twentysixteen-fonts', twentysixteen_fonts_url(), [], $font_version);

        // Add Genericons, used in the main stylesheet.
        wp_enqueue_style('genericons', get_template_directory_uri().'/genericons/genericons.css', [], '20201208');

        // Theme stylesheet.
        wp_enqueue_style('twentysixteen-style', get_stylesheet_uri(), [], '20230808');

        // Theme block stylesheet.
        wp_enqueue_style('twentysixteen-block-style', get_template_directory_uri().'/css/blocks.css', ['twentysixteen-style'], '20230628');

        // Load the Internet Explorer specific stylesheet.
        wp_enqueue_style('twentysixteen-ie', get_template_directory_uri().'/css/ie.css', ['twentysixteen-style'], '20170530');
        wp_style_add_data('twentysixteen-ie', 'conditional', 'lt IE 10');

        // Load the Internet Explorer 8 specific stylesheet.
        wp_enqueue_style('twentysixteen-ie8', get_template_directory_uri().'/css/ie8.css', ['twentysixteen-style'], '20170530');
        wp_style_add_data('twentysixteen-ie8', 'conditional', 'lt IE 9');

        // Load the Internet Explorer 7 specific stylesheet.
        wp_enqueue_style('twentysixteen-ie7', get_template_directory_uri().'/css/ie7.css', ['twentysixteen-style'], '20170530');
        wp_style_add_data('twentysixteen-ie7', 'conditional', 'lt IE 8');

        // Load the html5 shiv.
        wp_enqueue_script('twentysixteen-html5', get_template_directory_uri().'/js/html5.js', [], '3.7.3');
        wp_script_add_data('twentysixteen-html5', 'conditional', 'lt IE 9');

        // Skip-link fix is no longer enqueued by default.
        wp_register_script('twentysixteen-skip-link-focus-fix', get_template_directory_uri().'/js/skip-link-focus-fix.js', [], '20230526', ['in_footer' => true]);

        if(is_singular() && comments_open() && get_option('thread_comments'))
        {
            wp_enqueue_script('comment-reply');
        }

        if(is_singular() && wp_attachment_is_image())
        {
            wp_enqueue_script('twentysixteen-keyboard-image-navigation', get_template_directory_uri().'/js/keyboard-image-navigation.js', ['jquery'], '20170530');
        }

        wp_enqueue_script('twentysixteen-script', get_template_directory_uri().'/js/functions.js', ['jquery'], '20230629', [
            'in_footer' => false, // Because involves header.
            'strategy' => 'defer',
        ]);

        wp_localize_script('twentysixteen-script', 'screenReaderText', [
            'expand' => __('expand child menu', 'twentysixteen'),
            'collapse' => __('collapse child menu', 'twentysixteen'),
        ]);
    }

    add_action('wp_enqueue_scripts', 'twentysixteen_scripts');

    function twentysixteen_block_editor_styles()
    {
        // Block styles.
        wp_enqueue_style('twentysixteen-block-editor-style', get_template_directory_uri().'/css/editor-blocks.css', [], '20230628');
        // Add custom fonts.
        $font_version = (0 === strpos((string) twentysixteen_fonts_url(), get_template_directory_uri().'/')) ? '20230328' : null;
        wp_enqueue_style('twentysixteen-fonts', twentysixteen_fonts_url(), [], $font_version);
    }

    add_action('enqueue_block_editor_assets', 'twentysixteen_block_editor_styles');

    function twentysixteen_body_classes($classes)
    {
        // Adds a class of custom-background-image to sites with a custom background image.
        if(get_background_image())
        {
            $classes[] = 'custom-background-image';
        }

        // Adds a class of group-blog to sites with more than 1 published author.
        if(is_multi_author())
        {
            $classes[] = 'group-blog';
        }

        // Adds a class of no-sidebar to sites without active sidebar.
        if(! is_active_sidebar('sidebar-1'))
        {
            $classes[] = 'no-sidebar';
        }

        // Adds a class of hfeed to non-singular pages.
        if(! is_singular())
        {
            $classes[] = 'hfeed';
        }

        return $classes;
    }

    add_filter('body_class', 'twentysixteen_body_classes');

    function twentysixteen_hex2rgb($color)
    {
        $color = trim($color, '#');

        if(strlen($color) === 3)
        {
            $r = hexdec(substr($color, 0, 1).substr($color, 0, 1));
            $g = hexdec(substr($color, 1, 1).substr($color, 1, 1));
            $b = hexdec(substr($color, 2, 1).substr($color, 2, 1));
        }
        elseif(strlen($color) === 6)
        {
            $r = hexdec(substr($color, 0, 2));
            $g = hexdec(substr($color, 2, 2));
            $b = hexdec(substr($color, 4, 2));
        }
        else
        {
            return [];
        }

        return [
            'red' => $r,
            'green' => $g,
            'blue' => $b,
        ];
    }

    require get_template_directory().'/inc/template-tags.php';

    require get_template_directory().'/inc/block-patterns.php';

    require get_template_directory().'/inc/customizer.php';

    function twentysixteen_content_image_sizes_attr($sizes, $size)
    {
        $width = $size[0];

        if(840 <= $width)
        {
            $sizes = '(max-width: 709px) 85vw, (max-width: 909px) 67vw, (max-width: 1362px) 62vw, 840px';
        }

        if('page' === get_post_type())
        {
            if(840 > $width)
            {
                $sizes = '(max-width: '.$width.'px) 85vw, '.$width.'px';
            }
        }
        else
        {
            if(840 > $width && 600 <= $width)
            {
                $sizes = '(max-width: 709px) 85vw, (max-width: 909px) 67vw, (max-width: 984px) 61vw, (max-width: 1362px) 45vw, 600px';
            }
            elseif(600 > $width)
            {
                $sizes = '(max-width: '.$width.'px) 85vw, '.$width.'px';
            }
        }

        return $sizes;
    }

    add_filter('wp_calculate_image_sizes', 'twentysixteen_content_image_sizes_attr', 10, 2);

    function twentysixteen_post_thumbnail_sizes_attr($attr, $attachment, $size)
    {
        if('post-thumbnail' === $size)
        {
            if(is_active_sidebar('sidebar-1'))
            {
                $attr['sizes'] = '(max-width: 709px) 85vw, (max-width: 909px) 67vw, (max-width: 984px) 60vw, (max-width: 1362px) 62vw, 840px';
            }
            else
            {
                $attr['sizes'] = '(max-width: 709px) 85vw, (max-width: 909px) 67vw, (max-width: 1362px) 88vw, 1200px';
            }
        }

        return $attr;
    }

    add_filter('wp_get_attachment_image_attributes', 'twentysixteen_post_thumbnail_sizes_attr', 10, 3);

    function twentysixteen_widget_tag_cloud_args($args)
    {
        $args['largest'] = 1;
        $args['smallest'] = 1;
        $args['unit'] = 'em';
        $args['format'] = 'list';

        return $args;
    }

    add_filter('widget_tag_cloud_args', 'twentysixteen_widget_tag_cloud_args');
