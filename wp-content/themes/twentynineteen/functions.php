<?php

    if(version_compare($GLOBALS['wp_version'], '4.7', '<'))
    {
        require get_template_directory().'/inc/back-compat.php';

        return;
    }

    if(! function_exists('twentynineteen_setup')) :

        function twentynineteen_setup()
        {
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
             * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
             */
            add_theme_support('post-thumbnails');
            set_post_thumbnail_size(1568, 9999);

            // This theme uses wp_nav_menu() in two locations.
            register_nav_menus([
                                   'menu-1' => __('Primary', 'twentynineteen'),
                                   'footer' => __('Footer Menu', 'twentynineteen'),
                                   'social' => __('Social Links Menu', 'twentynineteen'),
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

            add_theme_support('custom-logo', [
                'height' => 190,
                'width' => 190,
                'flex-width' => false,
                'flex-height' => false,
            ]);

            // Add theme support for selective refresh for widgets.
            add_theme_support('customize-selective-refresh-widgets');

            // Add support for Block Styles.
            add_theme_support('wp-block-styles');

            // Add support for full and wide align images.
            add_theme_support('align-wide');

            // Add support for editor styles.
            add_theme_support('editor-styles');

            // Enqueue editor styles.
            add_editor_style('style-editor.css');

            // Add custom editor font sizes.
            add_theme_support('editor-font-sizes', [
                [
                    'name' => __('Small', 'twentynineteen'),
                    'shortName' => __('S', 'twentynineteen'),
                    'size' => 19.5,
                    'slug' => 'small',
                ],
                [
                    'name' => __('Normal', 'twentynineteen'),
                    'shortName' => __('M', 'twentynineteen'),
                    'size' => 22,
                    'slug' => 'normal',
                ],
                [
                    'name' => __('Large', 'twentynineteen'),
                    'shortName' => __('L', 'twentynineteen'),
                    'size' => 36.5,
                    'slug' => 'large',
                ],
                [
                    'name' => __('Huge', 'twentynineteen'),
                    'shortName' => __('XL', 'twentynineteen'),
                    'size' => 49.5,
                    'slug' => 'huge',
                ],
            ]);

            // Editor color palette.
            add_theme_support('editor-color-palette', [
                [
                    'name' => 'default' === get_theme_mod('primary_color') ? __('Blue', 'twentynineteen') : null,
                    'slug' => 'primary',
                    'color' => twentynineteen_hsl_hex('default' === get_theme_mod('primary_color') ? 199 : get_theme_mod('primary_color_hue', 199), 100, 33),
                ],
                [
                    'name' => 'default' === get_theme_mod('primary_color') ? __('Dark Blue', 'twentynineteen') : null,
                    'slug' => 'secondary',
                    'color' => twentynineteen_hsl_hex('default' === get_theme_mod('primary_color') ? 199 : get_theme_mod('primary_color_hue', 199), 100, 23),
                ],
                [
                    'name' => __('Dark Gray', 'twentynineteen'),
                    'slug' => 'dark-gray',
                    'color' => '#111',
                ],
                [
                    'name' => __('Light Gray', 'twentynineteen'),
                    'slug' => 'light-gray',
                    'color' => '#767676',
                ],
                [
                    'name' => __('White', 'twentynineteen'),
                    'slug' => 'white',
                    'color' => '#FFF',
                ],
            ]);

            // Add support for responsive embedded content.
            add_theme_support('responsive-embeds');

            // Add support for custom line height.
            add_theme_support('custom-line-height');
        }
    endif;
    add_action('after_setup_theme', 'twentynineteen_setup');

    if(! function_exists('wp_get_list_item_separator')) :

        function wp_get_list_item_separator()
        {
            /* translators: Used between list items, there is a space after the comma. */
            return __(', ', 'twentynineteen');
        }
    endif;

    function twentynineteen_widgets_init()
    {
        register_sidebar([
                             'name' => __('Footer', 'twentynineteen'),
                             'id' => 'sidebar-1',
                             'description' => __('Add widgets here to appear in your footer.', 'twentynineteen'),
                             'before_widget' => '<section id="%1$s" class="widget %2$s">',
                             'after_widget' => '</section>',
                             'before_title' => '<h2 class="widget-title">',
                             'after_title' => '</h2>',
                         ]);
    }

    add_action('widgets_init', 'twentynineteen_widgets_init');

    function twentynineteen_excerpt_more($link)
    {
        if(is_admin())
        {
            return $link;
        }

        $link = sprintf('<p class="link-more"><a href="%1$s" class="more-link">%2$s</a></p>', esc_url(get_permalink(get_the_ID())), /* translators: %s: Post title. Only visible to screen readers. */ sprintf(__('Continue reading<span class="screen-reader-text"> "%s"</span>', 'twentynineteen'), get_the_title(get_the_ID())));

        return ' &hellip; '.$link;
    }

    add_filter('excerpt_more', 'twentynineteen_excerpt_more');

    function twentynineteen_content_width()
    {
        // This variable is intended to be overruled from themes.
        // Open WPCS issue: {@link https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/issues/1043}.
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
        $GLOBALS['content_width'] = apply_filters('twentynineteen_content_width', 640);
    }

    add_action('after_setup_theme', 'twentynineteen_content_width', 0);

    function twentynineteen_scripts()
    {
        wp_enqueue_style('twentynineteen-style', get_stylesheet_uri(), [], wp_get_theme()->get('Version'));

        wp_style_add_data('twentynineteen-style', 'rtl', 'replace');

        if(has_nav_menu('menu-1'))
        {
            wp_enqueue_script('twentynineteen-priority-menu', get_theme_file_uri('/js/priority-menu.js'), [], '20200129', [
                'in_footer' => false, // Because involves header.
                'strategy' => 'defer',
            ]);
            wp_enqueue_script('twentynineteen-touch-navigation', get_theme_file_uri('/js/touch-keyboard-navigation.js'), [], '20230621', [
                'in_footer' => true,
                'strategy' => 'defer',
            ]);
        }

        wp_enqueue_style('twentynineteen-print-style', get_template_directory_uri().'/print.css', [], wp_get_theme()->get('Version'), 'print');

        if(is_singular() && comments_open() && get_option('thread_comments'))
        {
            wp_enqueue_script('comment-reply');
        }
    }

    add_action('wp_enqueue_scripts', 'twentynineteen_scripts');

    function twentynineteen_skip_link_focus_fix()
    {
        // The following is minified via `terser --compress --mangle -- js/skip-link-focus-fix.js`.
        ?>
        <script>
            /(trident|msie)/i.test(navigator.userAgent) && document.getElementById && window.addEventListener && window.addEventListener('hashchange', function () {
                var t, e = location.hash.substring(1);
                /^[A-z0-9_-]+$/.test(e) && (t = document.getElementById(e)) && (/^(?:a|select|input|button|textarea)$/i.test(t.tagName) || (t.tabIndex = -1), t.focus());
            }, !1);
        </script>
        <?php
    }

    function twentynineteen_editor_customizer_styles()
    {
        wp_enqueue_style('twentynineteen-editor-customizer-styles', get_theme_file_uri('/style-editor-customizer.css'), false, '2.1', 'all');

        if('custom' === get_theme_mod('primary_color'))
        {
            // Include color patterns.
            require_once get_parent_theme_file_path('/inc/color-patterns.php');
            wp_add_inline_style('twentynineteen-editor-customizer-styles', twentynineteen_custom_colors_css());
        }
    }

    add_action('enqueue_block_editor_assets', 'twentynineteen_editor_customizer_styles');

    function twentynineteen_colors_css_wrap()
    {
        // Only include custom colors in customizer or frontend.
        if((! is_customize_preview() && 'default' === get_theme_mod('primary_color', 'default')) || is_admin())
        {
            return;
        }

        require_once get_parent_theme_file_path('/inc/color-patterns.php');

        $primary_color = 199;
        if('default' !== get_theme_mod('primary_color', 'default'))
        {
            $primary_color = get_theme_mod('primary_color_hue', 199);
        }
        ?>

        <style type="text/css"
               id="custom-theme-colors" <?php echo is_customize_preview() ? 'data-hue="'.absint($primary_color).'"' : ''; ?>>
            <?php echo twentynineteen_custom_colors_css(); ?>
        </style>
        <?php
    }

    add_action('wp_head', 'twentynineteen_colors_css_wrap');

    require get_template_directory().'/classes/class-twentynineteen-svg-icons.php';

    require get_template_directory().'/classes/class-twentynineteen-walker-comment.php';

    require get_template_directory().'/inc/helper-functions.php';

    require get_template_directory().'/inc/icon-functions.php';

    require get_template_directory().'/inc/template-functions.php';

    require get_template_directory().'/inc/template-tags.php';

    require get_template_directory().'/inc/customizer.php';

    require get_template_directory().'/inc/block-patterns.php';
