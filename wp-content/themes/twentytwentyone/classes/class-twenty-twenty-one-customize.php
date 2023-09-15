<?php

    if(! class_exists('Twenty_Twenty_One_Customize'))
    {
        class Twenty_Twenty_One_Customize
        {
            public function __construct()
            {
                add_action('customize_register', [$this, 'register']);
            }

            public function register($wp_customize)
            {
                // Change site-title & description to postMessage.
                $wp_customize->get_setting('blogname')->transport = 'postMessage'; // @phpstan-ignore-line. Assume that this setting exists.
                $wp_customize->get_setting('blogdescription')->transport = 'postMessage'; // @phpstan-ignore-line. Assume that this setting exists.

                // Add partial for blogname.
                $wp_customize->selective_refresh->add_partial('blogname', [
                    'selector' => '.site-title',
                    'render_callback' => [$this, 'partial_blogname'],
                ]);

                // Add partial for blogdescription.
                $wp_customize->selective_refresh->add_partial('blogdescription', [
                    'selector' => '.site-description',
                    'render_callback' => [$this, 'partial_blogdescription'],
                ]);

                // Add "display_title_and_tagline" setting for displaying the site-title & tagline.
                $wp_customize->add_setting('display_title_and_tagline', [
                    'capability' => 'edit_theme_options',
                    'default' => true,
                    'sanitize_callback' => [__CLASS__, 'sanitize_checkbox'],
                ]);

                // Add control for the "display_title_and_tagline" setting.
                $wp_customize->add_control('display_title_and_tagline', [
                    'type' => 'checkbox',
                    'section' => 'title_tagline',
                    'label' => esc_html__('Display Site Title & Tagline', 'twentytwentyone'),
                ]);

                $wp_customize->add_section('excerpt_settings', [
                    'title' => esc_html__('Excerpt Settings', 'twentytwentyone'),
                    'priority' => 120,
                ]);

                $wp_customize->add_setting('display_excerpt_or_full_post', [
                    'capability' => 'edit_theme_options',
                    'default' => 'excerpt',
                    'sanitize_callback' => static function($value)
                    {
                        return 'excerpt' === $value || 'full' === $value ? $value : 'excerpt';
                    },
                ]);

                $wp_customize->add_control('display_excerpt_or_full_post', [
                    'type' => 'radio',
                    'section' => 'excerpt_settings',
                    'label' => esc_html__('On Archive Pages, posts show:', 'twentytwentyone'),
                    'choices' => [
                        'excerpt' => esc_html__('Summary', 'twentytwentyone'),
                        'full' => esc_html__('Full text', 'twentytwentyone'),
                    ],
                ]);

                // Background color.
                // Include the custom control class.
                require_once get_theme_file_path('classes/class-twenty-twenty-one-customize-color-control.php'); // phpcs:ignore WPThemeReview.CoreFunctionality.FileInclude.FileIncludeFound

                // Register the custom control.
                $wp_customize->register_control_type('Twenty_Twenty_One_Customize_Color_Control');

                // Get the palette from theme-supports.
                $palette = get_theme_support('editor-color-palette');

                // Build the colors array from theme-support.
                $colors = [];
                if(isset($palette[0]) && is_array($palette[0]))
                {
                    foreach($palette[0] as $palette_color)
                    {
                        $colors[] = $palette_color['color'];
                    }
                }

                // Add the control. Overrides the default background-color control.
                $wp_customize->add_control(
                    new Twenty_Twenty_One_Customize_Color_Control($wp_customize, 'background_color', [
                        'label' => esc_html_x('Background color', 'Customizer control', 'twentytwentyone'),
                        'section' => 'colors',
                        'palette' => $colors,
                    ])
                );
            }

            public static function sanitize_checkbox($checked = null)
            {
                return (bool) isset($checked) && true === $checked;
            }

            public function partial_blogname()
            {
                bloginfo('name');
            }

            public function partial_blogdescription()
            {
                bloginfo('description');
            }
        }
    }
