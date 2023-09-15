<?php

    if(! class_exists('TwentyTwenty_Customize'))
    {
        class TwentyTwenty_Customize
        {
            public static function register($wp_customize)
            {
                $wp_customize->get_setting('blogname')->transport = 'postMessage';
                $wp_customize->get_setting('blogdescription')->transport = 'postMessage';

                $wp_customize->selective_refresh->add_partial('blogname', [
                    'selector' => '.site-title a',
                    'render_callback' => 'twentytwenty_customize_partial_blogname',
                ]);

                $wp_customize->selective_refresh->add_partial('blogdescription', [
                    'selector' => '.site-description',
                    'render_callback' => 'twentytwenty_customize_partial_blogdescription',
                ]);

                $wp_customize->selective_refresh->add_partial('custom_logo', [
                    'selector' => '.header-titles [class*=site-]:not(.site-description)',
                    'render_callback' => 'twentytwenty_customize_partial_site_logo',
                    'container_inclusive' => true,
                ]);

                $wp_customize->selective_refresh->add_partial('retina_logo', [
                    'selector' => '.header-titles [class*=site-]:not(.site-description)',
                    'render_callback' => 'twentytwenty_customize_partial_site_logo',
                ]);

                /* 2X Header Logo ---------------- */
                $wp_customize->add_setting('retina_logo', [
                    'capability' => 'edit_theme_options',
                    'sanitize_callback' => [__CLASS__, 'sanitize_checkbox'],
                    'transport' => 'postMessage',
                ]);

                $wp_customize->add_control('retina_logo', [
                    'type' => 'checkbox',
                    'section' => 'title_tagline',
                    'priority' => 10,
                    'label' => __('Retina logo', 'twentytwenty'),
                    'description' => __('Scales the logo to half its uploaded size, making it sharp on high-res screens.', 'twentytwenty'),
                ]);

                // Header & Footer Background Color.
                $wp_customize->add_setting('header_footer_background_color', [
                    'default' => '#ffffff',
                    'sanitize_callback' => 'sanitize_hex_color',
                    'transport' => 'postMessage',
                ]);

                $wp_customize->add_control(
                    new WP_Customize_Color_Control($wp_customize, 'header_footer_background_color', [
                        'label' => __('Header &amp; Footer Background Color', 'twentytwenty'),
                        'section' => 'colors',
                    ])
                );

                // Enable picking an accent color.
                $wp_customize->add_setting('accent_hue_active', [
                    'capability' => 'edit_theme_options',
                    'sanitize_callback' => [__CLASS__, 'sanitize_select'],
                    'transport' => 'postMessage',
                    'default' => 'default',
                ]);

                $wp_customize->add_control('accent_hue_active', [
                    'type' => 'radio',
                    'section' => 'colors',
                    'label' => __('Primary Color', 'twentytwenty'),
                    'choices' => [
                        'default' => _x('Default', 'color', 'twentytwenty'),
                        'custom' => _x('Custom', 'color', 'twentytwenty'),
                    ],
                ]);

                // Add the setting for the hue colorpicker.
                $wp_customize->add_setting('accent_hue', [
                    'default' => 344,
                    'type' => 'theme_mod',
                    'sanitize_callback' => 'absint',
                    'transport' => 'postMessage',
                ]);

                // Add setting to hold colors derived from the accent hue.
                $wp_customize->add_setting('accent_accessible_colors', [
                    'default' => [
                        'content' => [
                            'text' => '#000000',
                            'accent' => '#cd2653',
                            'secondary' => '#6d6d6d',
                            'borders' => '#dcd7ca',
                        ],
                        'header-footer' => [
                            'text' => '#000000',
                            'accent' => '#cd2653',
                            'secondary' => '#6d6d6d',
                            'borders' => '#dcd7ca',
                        ],
                    ],
                    'type' => 'theme_mod',
                    'transport' => 'postMessage',
                    'sanitize_callback' => [__CLASS__, 'sanitize_accent_accessible_colors'],
                ]);

                // Add the hue-only colorpicker for the accent color.
                $wp_customize->add_control(
                    new WP_Customize_Color_Control($wp_customize, 'accent_hue', [
                        'section' => 'colors',
                        'settings' => 'accent_hue',
                        'description' => __('Apply a custom color for links, buttons, featured images.', 'twentytwenty'),
                        'mode' => 'hue',
                        'active_callback' => static function() use ($wp_customize)
                        {
                            return ('custom' === $wp_customize->get_setting('accent_hue_active')->value());
                        },
                    ])
                );

                // Update background color with postMessage, so inline CSS output is updated as well.
                $wp_customize->get_setting('background_color')->transport = 'postMessage';

                $wp_customize->add_section('options', [
                    'title' => __('Theme Options', 'twentytwenty'),
                    'priority' => 40,
                    'capability' => 'edit_theme_options',
                ]);

                /* Enable Header Search ----------------------------------------------- */

                $wp_customize->add_setting('enable_header_search', [
                    'capability' => 'edit_theme_options',
                    'default' => true,
                    'sanitize_callback' => [__CLASS__, 'sanitize_checkbox'],
                ]);

                $wp_customize->add_control('enable_header_search', [
                    'type' => 'checkbox',
                    'section' => 'options',
                    'priority' => 10,
                    'label' => __('Show search in header', 'twentytwenty'),
                ]);

                /* Show author bio ---------------------------------------------------- */

                $wp_customize->add_setting('show_author_bio', [
                    'capability' => 'edit_theme_options',
                    'default' => true,
                    'sanitize_callback' => [__CLASS__, 'sanitize_checkbox'],
                ]);

                $wp_customize->add_control('show_author_bio', [
                    'type' => 'checkbox',
                    'section' => 'options',
                    'priority' => 10,
                    'label' => __('Show author bio', 'twentytwenty'),
                ]);

                /* Display full content or excerpts on the blog and archives --------- */

                $wp_customize->add_setting('blog_content', [
                    'capability' => 'edit_theme_options',
                    'default' => 'full',
                    'sanitize_callback' => [__CLASS__, 'sanitize_select'],
                ]);

                $wp_customize->add_control('blog_content', [
                    'type' => 'radio',
                    'section' => 'options',
                    'priority' => 10,
                    'label' => __('On archive pages, posts show:', 'twentytwenty'),
                    'choices' => [
                        'full' => __('Full text', 'twentytwenty'),
                        'summary' => __('Summary', 'twentytwenty'),
                    ],
                ]);

                $wp_customize->add_section('cover_template_options', [
                    'title' => __('Cover Template', 'twentytwenty'),
                    'capability' => 'edit_theme_options',
                    'description' => __('Settings for the "Cover Template" page template. Add a featured image to use as background.', 'twentytwenty'),
                    'priority' => 42,
                ]);

                /* Overlay Fixed Background ------ */

                $wp_customize->add_setting('cover_template_fixed_background', [
                    'capability' => 'edit_theme_options',
                    'default' => true,
                    'sanitize_callback' => [__CLASS__, 'sanitize_checkbox'],
                    'transport' => 'postMessage',
                ]);

                $wp_customize->add_control('cover_template_fixed_background', [
                    'type' => 'checkbox',
                    'section' => 'cover_template_options',
                    'label' => __('Fixed Background Image', 'twentytwenty'),
                    'description' => __('Creates a parallax effect when the visitor scrolls.', 'twentytwenty'),
                ]);

                $wp_customize->selective_refresh->add_partial('cover_template_fixed_background', [
                    'selector' => '.cover-header',
                    'type' => 'cover_fixed',
                ]);

                /* Separator --------------------- */

                $wp_customize->add_setting('cover_template_separator_1', [
                    'sanitize_callback' => 'wp_filter_nohtml_kses',
                ]);

                $wp_customize->add_control(
                    new TwentyTwenty_Separator_Control($wp_customize, 'cover_template_separator_1', [
                        'section' => 'cover_template_options',
                    ])
                );

                /* Overlay Background Color ------ */

                $wp_customize->add_setting('cover_template_overlay_background_color', [
                    'default' => twentytwenty_get_color_for_area('content', 'accent'),
                    'sanitize_callback' => 'sanitize_hex_color',
                ]);

                $wp_customize->add_control(
                    new WP_Customize_Color_Control($wp_customize, 'cover_template_overlay_background_color', [
                        'label' => __('Overlay Background Color', 'twentytwenty'),
                        'description' => __('The color used for the overlay. Defaults to the accent color.', 'twentytwenty'),
                        'section' => 'cover_template_options',
                    ])
                );

                /* Overlay Text Color ------------ */

                $wp_customize->add_setting('cover_template_overlay_text_color', [
                    'default' => '#ffffff',
                    'sanitize_callback' => 'sanitize_hex_color',
                ]);

                $wp_customize->add_control(
                    new WP_Customize_Color_Control($wp_customize, 'cover_template_overlay_text_color', [
                        'label' => __('Overlay Text Color', 'twentytwenty'),
                        'description' => __('The color used for the text in the overlay.', 'twentytwenty'),
                        'section' => 'cover_template_options',
                    ])
                );

                /* Overlay Color Opacity --------- */

                $wp_customize->add_setting('cover_template_overlay_opacity', [
                    'default' => 80,
                    'sanitize_callback' => 'absint',
                    'transport' => 'postMessage',
                ]);

                $wp_customize->add_control('cover_template_overlay_opacity', [
                    'label' => __('Overlay Opacity', 'twentytwenty'),
                    'description' => __('Make sure that the contrast is high enough so that the text is readable.', 'twentytwenty'),
                    'section' => 'cover_template_options',
                    'type' => 'range',
                    'input_attrs' => twentytwenty_customize_opacity_range(),
                ]);

                $wp_customize->selective_refresh->add_partial('cover_template_overlay_opacity', [
                    'selector' => '.cover-color-overlay',
                    'type' => 'cover_opacity',
                ]);
            }

            public static function sanitize_accent_accessible_colors($value)
            {
                // Make sure the value is an array. Do not typecast, use empty array as fallback.
                $value = is_array($value) ? $value : [];

                // Loop values.
                foreach($value as $area => $values)
                {
                    foreach($values as $context => $color_val)
                    {
                        $value[$area][$context] = sanitize_hex_color($color_val);
                    }
                }

                return $value;
            }

            public static function sanitize_select($input, $setting)
            {
                $input = sanitize_key($input);
                $choices = $setting->manager->get_control($setting->id)->choices;

                return (array_key_exists($input, $choices) ? $input : $setting->default);
            }

            public static function sanitize_checkbox($checked)
            {
                return ((isset($checked) && true === $checked) ? true : false);
            }
        }

        // Setup the Theme Customizer settings and controls.
        add_action('customize_register', ['TwentyTwenty_Customize', 'register']);
    }

    if(! function_exists('twentytwenty_customize_partial_blogname'))
    {
        function twentytwenty_customize_partial_blogname()
        {
            bloginfo('name');
        }
    }

    if(! function_exists('twentytwenty_customize_partial_blogdescription'))
    {
        function twentytwenty_customize_partial_blogdescription()
        {
            bloginfo('description');
        }
    }

    if(! function_exists('twentytwenty_customize_partial_site_logo'))
    {
        function twentytwenty_customize_partial_site_logo()
        {
            twentytwenty_site_logo();
        }
    }

    function twentytwenty_customize_opacity_range()
    {
        return apply_filters('twentytwenty_customize_opacity_range', [
            'min' => 0,
            'max' => 90,
            'step' => 5,
        ]);
    }
