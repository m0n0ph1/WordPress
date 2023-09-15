<?php

// This theme requires WordPress 5.3 or later.
    if(version_compare($GLOBALS['wp_version'], '5.3', '<'))
    {
        require get_template_directory().'/inc/back-compat.php';
    }

    if(! function_exists('twenty_twenty_one_setup'))
    {
        function twenty_twenty_one_setup()
        {
            // Add default posts and comments RSS feed links to head.
            add_theme_support('automatic-feed-links');

            /*
             * Let WordPress manage the document title.
             * This theme does not use a hard-coded <title> tag in the document head,
             * WordPress will provide it for us.
             */
            add_theme_support('title-tag');

            add_theme_support('post-formats', [
                'link',
                'aside',
                'gallery',
                'image',
                'quote',
                'status',
                'video',
                'audio',
                'chat',
            ]);

            /*
             * Enable support for Post Thumbnails on posts and pages.
             *
             * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
             */
            add_theme_support('post-thumbnails');
            set_post_thumbnail_size(1568, 9999);

            register_nav_menus([
                                   'primary' => esc_html__('Primary menu', 'twentytwentyone'),
                                   'footer' => esc_html__('Secondary menu', 'twentytwentyone'),
                               ]);

            /*
             * Switch default core markup for search form, comment form, and comments
             * to output valid HTML5.
             */
            add_theme_support('html5', [
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
                'style',
                'script',
                'navigation-widgets',
            ]);

            /*
             * Add support for core custom logo.
             *
             * @link https://codex.wordpress.org/Theme_Logo
             */
            $logo_width = 300;
            $logo_height = 100;

            add_theme_support('custom-logo', [
                'height' => $logo_height,
                'width' => $logo_width,
                'flex-width' => true,
                'flex-height' => true,
                'unlink-homepage-logo' => true,
            ]);

            // Add theme support for selective refresh for widgets.
            add_theme_support('customize-selective-refresh-widgets');

            // Add support for Block Styles.
            add_theme_support('wp-block-styles');

            // Add support for full and wide align images.
            add_theme_support('align-wide');

            // Add support for editor styles.
            add_theme_support('editor-styles');
            $background_color = get_theme_mod('background_color', 'D1E4DD');
            if(127 > Twenty_Twenty_One_Custom_Colors::get_relative_luminance_from_hex($background_color))
            {
                add_theme_support('dark-editor-style');
            }

            $editor_stylesheet_path = './assets/css/style-editor.css';

            // Note, the is_IE global variable is defined by WordPress and is used
            // to detect if the current browser is internet explorer.
            global $is_IE;
            if($is_IE)
            {
                $editor_stylesheet_path = './assets/css/ie-editor.css';
            }

            // Enqueue editor styles.
            add_editor_style($editor_stylesheet_path);

            // Add custom editor font sizes.
            add_theme_support('editor-font-sizes', [
                [
                    'name' => esc_html__('Extra small', 'twentytwentyone'),
                    'shortName' => esc_html_x('XS', 'Font size', 'twentytwentyone'),
                    'size' => 16,
                    'slug' => 'extra-small',
                ],
                [
                    'name' => esc_html__('Small', 'twentytwentyone'),
                    'shortName' => esc_html_x('S', 'Font size', 'twentytwentyone'),
                    'size' => 18,
                    'slug' => 'small',
                ],
                [
                    'name' => esc_html__('Normal', 'twentytwentyone'),
                    'shortName' => esc_html_x('M', 'Font size', 'twentytwentyone'),
                    'size' => 20,
                    'slug' => 'normal',
                ],
                [
                    'name' => esc_html__('Large', 'twentytwentyone'),
                    'shortName' => esc_html_x('L', 'Font size', 'twentytwentyone'),
                    'size' => 24,
                    'slug' => 'large',
                ],
                [
                    'name' => esc_html__('Extra large', 'twentytwentyone'),
                    'shortName' => esc_html_x('XL', 'Font size', 'twentytwentyone'),
                    'size' => 40,
                    'slug' => 'extra-large',
                ],
                [
                    'name' => esc_html__('Huge', 'twentytwentyone'),
                    'shortName' => esc_html_x('XXL', 'Font size', 'twentytwentyone'),
                    'size' => 96,
                    'slug' => 'huge',
                ],
                [
                    'name' => esc_html__('Gigantic', 'twentytwentyone'),
                    'shortName' => esc_html_x('XXXL', 'Font size', 'twentytwentyone'),
                    'size' => 144,
                    'slug' => 'gigantic',
                ],
            ]);

            // Custom background color.
            add_theme_support('custom-background', [
                'default-color' => 'd1e4dd',
            ]);

            // Editor color palette.
            $black = '#000000';
            $dark_gray = '#28303D';
            $gray = '#39414D';
            $green = '#D1E4DD';
            $blue = '#D1DFE4';
            $purple = '#D1D1E4';
            $red = '#E4D1D1';
            $orange = '#E4DAD1';
            $yellow = '#EEEADD';
            $white = '#FFFFFF';

            add_theme_support('editor-color-palette', [
                [
                    'name' => esc_html__('Black', 'twentytwentyone'),
                    'slug' => 'black',
                    'color' => $black,
                ],
                [
                    'name' => esc_html__('Dark gray', 'twentytwentyone'),
                    'slug' => 'dark-gray',
                    'color' => $dark_gray,
                ],
                [
                    'name' => esc_html__('Gray', 'twentytwentyone'),
                    'slug' => 'gray',
                    'color' => $gray,
                ],
                [
                    'name' => esc_html__('Green', 'twentytwentyone'),
                    'slug' => 'green',
                    'color' => $green,
                ],
                [
                    'name' => esc_html__('Blue', 'twentytwentyone'),
                    'slug' => 'blue',
                    'color' => $blue,
                ],
                [
                    'name' => esc_html__('Purple', 'twentytwentyone'),
                    'slug' => 'purple',
                    'color' => $purple,
                ],
                [
                    'name' => esc_html__('Red', 'twentytwentyone'),
                    'slug' => 'red',
                    'color' => $red,
                ],
                [
                    'name' => esc_html__('Orange', 'twentytwentyone'),
                    'slug' => 'orange',
                    'color' => $orange,
                ],
                [
                    'name' => esc_html__('Yellow', 'twentytwentyone'),
                    'slug' => 'yellow',
                    'color' => $yellow,
                ],
                [
                    'name' => esc_html__('White', 'twentytwentyone'),
                    'slug' => 'white',
                    'color' => $white,
                ],
            ]);

            add_theme_support('editor-gradient-presets', [
                [
                    'name' => esc_html__('Purple to yellow', 'twentytwentyone'),
                    'gradient' => 'linear-gradient(160deg, '.$purple.' 0%, '.$yellow.' 100%)',
                    'slug' => 'purple-to-yellow',
                ],
                [
                    'name' => esc_html__('Yellow to purple', 'twentytwentyone'),
                    'gradient' => 'linear-gradient(160deg, '.$yellow.' 0%, '.$purple.' 100%)',
                    'slug' => 'yellow-to-purple',
                ],
                [
                    'name' => esc_html__('Green to yellow', 'twentytwentyone'),
                    'gradient' => 'linear-gradient(160deg, '.$green.' 0%, '.$yellow.' 100%)',
                    'slug' => 'green-to-yellow',
                ],
                [
                    'name' => esc_html__('Yellow to green', 'twentytwentyone'),
                    'gradient' => 'linear-gradient(160deg, '.$yellow.' 0%, '.$green.' 100%)',
                    'slug' => 'yellow-to-green',
                ],
                [
                    'name' => esc_html__('Red to yellow', 'twentytwentyone'),
                    'gradient' => 'linear-gradient(160deg, '.$red.' 0%, '.$yellow.' 100%)',
                    'slug' => 'red-to-yellow',
                ],
                [
                    'name' => esc_html__('Yellow to red', 'twentytwentyone'),
                    'gradient' => 'linear-gradient(160deg, '.$yellow.' 0%, '.$red.' 100%)',
                    'slug' => 'yellow-to-red',
                ],
                [
                    'name' => esc_html__('Purple to red', 'twentytwentyone'),
                    'gradient' => 'linear-gradient(160deg, '.$purple.' 0%, '.$red.' 100%)',
                    'slug' => 'purple-to-red',
                ],
                [
                    'name' => esc_html__('Red to purple', 'twentytwentyone'),
                    'gradient' => 'linear-gradient(160deg, '.$red.' 0%, '.$purple.' 100%)',
                    'slug' => 'red-to-purple',
                ],
            ]);

            /*
            * Adds starter content to highlight the theme on fresh sites.
            * This is done conditionally to avoid loading the starter content on every
            * page load, as it is a one-off operation only needed once in the customizer.
            */
            if(is_customize_preview())
            {
                require get_template_directory().'/inc/starter-content.php';
                add_theme_support('starter-content', twenty_twenty_one_get_starter_content());
            }

            // Add support for responsive embedded content.
            add_theme_support('responsive-embeds');

            // Add support for custom line height controls.
            add_theme_support('custom-line-height');

            // Add support for link color control.
            add_theme_support('link-color');

            // Add support for experimental cover block spacing.
            add_theme_support('custom-spacing');

            // Add support for custom units.
            // This was removed in WordPress 5.6 but is still required to properly support WP 5.5.
            add_theme_support('custom-units');

            // Remove feed icon link from legacy RSS widget.
            add_filter('rss_widget_feed_link', '__return_empty_string');
        }
    }
    add_action('after_setup_theme', 'twenty_twenty_one_setup');

    function twenty_twenty_one_widgets_init()
    {
        register_sidebar([
                             'name' => esc_html__('Footer', 'twentytwentyone'),
                             'id' => 'sidebar-1',
                             'description' => esc_html__('Add widgets here to appear in your footer.', 'twentytwentyone'),
                             'before_widget' => '<section id="%1$s" class="widget %2$s">',
                             'after_widget' => '</section>',
                             'before_title' => '<h2 class="widget-title">',
                             'after_title' => '</h2>',
                         ]);
    }

    add_action('widgets_init', 'twenty_twenty_one_widgets_init');

    function twenty_twenty_one_content_width()
    {
        // This variable is intended to be overruled from themes.
        // Open WPCS issue: {@link https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/issues/1043}.
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
        $GLOBALS['content_width'] = apply_filters('twenty_twenty_one_content_width', 750);
    }

    add_action('after_setup_theme', 'twenty_twenty_one_content_width', 0);

    function twenty_twenty_one_scripts()
    {
        // Note, the is_IE global variable is defined by WordPress and is used
        // to detect if the current browser is internet explorer.
        global $is_IE, $wp_scripts;
        if($is_IE)
        {
            // If IE 11 or below, use a flattened stylesheet with static values replacing CSS Variables.
            wp_enqueue_style('twenty-twenty-one-style', get_template_directory_uri().'/assets/css/ie.css', [], wp_get_theme()->get('Version'));
        }
        else
        {
            // If not IE, use the standard stylesheet.
            wp_enqueue_style('twenty-twenty-one-style', get_template_directory_uri().'/style.css', [], wp_get_theme()->get('Version'));
        }

        // RTL styles.
        wp_style_add_data('twenty-twenty-one-style', 'rtl', 'replace');

        // Print styles.
        wp_enqueue_style('twenty-twenty-one-print-style', get_template_directory_uri().'/assets/css/print.css', [], wp_get_theme()->get('Version'), 'print');

        // Threaded comment reply styles.
        if(is_singular() && comments_open() && get_option('thread_comments'))
        {
            wp_enqueue_script('comment-reply');
        }

        // Register the IE11 polyfill file.
        wp_register_script('twenty-twenty-one-ie11-polyfills-asset', get_template_directory_uri().'/assets/js/polyfills.js', [], wp_get_theme()->get('Version'), ['in_footer' => true]);

        // Register the IE11 polyfill loader.
        wp_register_script('twenty-twenty-one-ie11-polyfills', null, [], wp_get_theme()->get('Version'), ['in_footer' => true]);
        wp_add_inline_script(
            'twenty-twenty-one-ie11-polyfills', wp_get_script_polyfill($wp_scripts, [
                                                  'Element.prototype.matches && Element.prototype.closest && window.NodeList && NodeList.prototype.forEach' => 'twenty-twenty-one-ie11-polyfills-asset',
                                              ])
        );

        // Main navigation scripts.
        if(has_nav_menu('primary'))
        {
            wp_enqueue_script('twenty-twenty-one-primary-navigation-script', get_template_directory_uri().'/assets/js/primary-navigation.js', ['twenty-twenty-one-ie11-polyfills'], wp_get_theme()->get('Version'), [
                'in_footer' => false, // Because involves header.
                'strategy' => 'defer',
            ]);
        }

        // Responsive embeds script.
        wp_enqueue_script('twenty-twenty-one-responsive-embeds-script', get_template_directory_uri().'/assets/js/responsive-embeds.js', ['twenty-twenty-one-ie11-polyfills'], wp_get_theme()->get('Version'), ['in_footer' => true]);
    }

    add_action('wp_enqueue_scripts', 'twenty_twenty_one_scripts');

    function twentytwentyone_block_editor_script()
    {
        wp_enqueue_script('twentytwentyone-editor', get_theme_file_uri('/assets/js/editor.js'), [
            'wp-blocks',
            'wp-dom',
        ],                wp_get_theme()->get('Version'), ['in_footer' => true]);
    }

    add_action('enqueue_block_editor_assets', 'twentytwentyone_block_editor_script');

    function twenty_twenty_one_skip_link_focus_fix()
    {
        // If SCRIPT_DEBUG is defined and true, print the unminified file.
        if(defined('SCRIPT_DEBUG') && SCRIPT_DEBUG)
        {
            echo '<script>';
            include get_template_directory().'/assets/js/skip-link-focus-fix.js';
            echo '</script>';
        }
        else
        {
            // The following is minified via `npx terser --compress --mangle -- assets/js/skip-link-focus-fix.js`.
            ?>
            <script>
                /(trident|msie)/i.test(navigator.userAgent) && document.getElementById && window.addEventListener && window.addEventListener('hashchange', (function () {
                    var t, e = location.hash.substring(1);
                    /^[A-z0-9_-]+$/.test(e) && (t = document.getElementById(e)) && (/^(?:a|select|input|button|textarea)$/i.test(t.tagName) || (t.tabIndex = -1), t.focus());
                }), !1);
            </script>
            <?php
        }
    }

    function twenty_twenty_one_non_latin_languages()
    {
        $custom_css = twenty_twenty_one_get_non_latin_css('front-end');

        if($custom_css)
        {
            wp_add_inline_style('twenty-twenty-one-style', $custom_css);
        }
    }

    add_action('wp_enqueue_scripts', 'twenty_twenty_one_non_latin_languages');

// SVG Icons class.
    require get_template_directory().'/classes/class-twenty-twenty-one-svg-icons.php';

// Custom color classes.
    require get_template_directory().'/classes/class-twenty-twenty-one-custom-colors.php';
    new Twenty_Twenty_One_Custom_Colors();

// Enhance the theme by hooking into WordPress.
    require get_template_directory().'/inc/template-functions.php';

// Menu functions and filters.
    require get_template_directory().'/inc/menu-functions.php';

// Custom template tags for the theme.
    require get_template_directory().'/inc/template-tags.php';

// Customizer additions.
    require get_template_directory().'/classes/class-twenty-twenty-one-customize.php';
    new Twenty_Twenty_One_Customize();

// Block Patterns.
    require get_template_directory().'/inc/block-patterns.php';

// Block Styles.
    require get_template_directory().'/inc/block-styles.php';

// Dark Mode.
    require_once get_template_directory().'/classes/class-twenty-twenty-one-dark-mode.php';
    new Twenty_Twenty_One_Dark_Mode();

    function twentytwentyone_customize_preview_init()
    {
        wp_enqueue_script('twentytwentyone-customize-helpers', get_theme_file_uri('/assets/js/customize-helpers.js'), [], wp_get_theme()->get('Version'), ['in_footer' => true]);

        wp_enqueue_script('twentytwentyone-customize-preview', get_theme_file_uri('/assets/js/customize-preview.js'), [
            'customize-preview',
            'customize-selective-refresh',
            'jquery',
            'twentytwentyone-customize-helpers',
        ],                wp_get_theme()->get('Version'), ['in_footer' => true]);
    }

    add_action('customize_preview_init', 'twentytwentyone_customize_preview_init');

    function twentytwentyone_customize_controls_enqueue_scripts()
    {
        wp_enqueue_script('twentytwentyone-customize-helpers', get_theme_file_uri('/assets/js/customize-helpers.js'), [], wp_get_theme()->get('Version'), ['in_footer' => true]);
    }

    add_action('customize_controls_enqueue_scripts', 'twentytwentyone_customize_controls_enqueue_scripts');

    function twentytwentyone_the_html_classes()
    {
        $classes = apply_filters('twentytwentyone_html_classes', '');
        if(! $classes)
        {
            return;
        }
        echo 'class="'.esc_attr($classes).'"';
    }

    function twentytwentyone_add_ie_class()
    {
        ?>
        <script>
            if (-1 !== navigator.userAgent.indexOf('MSIE') || -1 !== navigator.appVersion.indexOf('Trident/')) {
                document.body.classList.add('is-IE');
            }
        </script>
        <?php
    }

    add_action('wp_footer', 'twentytwentyone_add_ie_class');

    if(! function_exists('wp_get_list_item_separator')) :

        function wp_get_list_item_separator()
        {
            /* translators: Used between list items, there is a space after the comma. */
            return __(', ', 'twentytwentyone');
        }
    endif;
