<?php

    function twentynineteen_customize_register($wp_customize)
    {
        $wp_customize->get_setting('blogname')->transport = 'postMessage';
        $wp_customize->get_setting('blogdescription')->transport = 'postMessage';
        $wp_customize->get_setting('header_textcolor')->transport = 'postMessage';

        if(isset($wp_customize->selective_refresh))
        {
            $wp_customize->selective_refresh->add_partial('blogname', [
                'selector' => '.site-title a',
                'render_callback' => 'twentynineteen_customize_partial_blogname',
            ]);
            $wp_customize->selective_refresh->add_partial('blogdescription', [
                'selector' => '.site-description',
                'render_callback' => 'twentynineteen_customize_partial_blogdescription',
            ]);
        }

        $wp_customize->add_setting('primary_color', [
            'default' => 'default',
            'transport' => 'postMessage',
            'sanitize_callback' => 'twentynineteen_sanitize_color_option',
        ]);

        $wp_customize->add_control('primary_color', [
            'type' => 'radio',
            'label' => __('Primary Color', 'twentynineteen'),
            'choices' => [
                'default' => _x('Default', 'primary color', 'twentynineteen'),
                'custom' => _x('Custom', 'primary color', 'twentynineteen'),
            ],
            'section' => 'colors',
            'priority' => 5,
        ]);

        // Add primary color hue setting and control.
        $wp_customize->add_setting('primary_color_hue', [
            'default' => 199,
            'transport' => 'postMessage',
            'sanitize_callback' => 'absint',
        ]);

        $wp_customize->add_control(
            new WP_Customize_Color_Control($wp_customize, 'primary_color_hue', [
                'description' => __('Apply a custom color for buttons, links, featured images, etc.', 'twentynineteen'),
                'section' => 'colors',
                'mode' => 'hue',
            ])
        );

        // Add image filter setting and control.
        $wp_customize->add_setting('image_filter', [
            'default' => 1,
            'sanitize_callback' => 'absint',
            'transport' => 'postMessage',
        ]);

        $wp_customize->add_control('image_filter', [
            'label' => __('Apply a filter to featured images using the primary color', 'twentynineteen'),
            'section' => 'colors',
            'type' => 'checkbox',
        ]);
    }

    add_action('customize_register', 'twentynineteen_customize_register');

    function twentynineteen_customize_partial_blogname()
    {
        bloginfo('name');
    }

    function twentynineteen_customize_partial_blogdescription()
    {
        bloginfo('description');
    }

    function twentynineteen_customize_preview_js()
    {
        wp_enqueue_script('twentynineteen-customize-preview', get_theme_file_uri('/js/customize-preview.js'), ['customize-preview'], '20181214', ['in_footer' => true]);
    }

    add_action('customize_preview_init', 'twentynineteen_customize_preview_js');

    function twentynineteen_panels_js()
    {
        wp_enqueue_script('twentynineteen-customize-controls', get_theme_file_uri('/js/customize-controls.js'), [], '20181214', ['in_footer' => true]);
    }

    add_action('customize_controls_enqueue_scripts', 'twentynineteen_panels_js');

    function twentynineteen_sanitize_color_option($choice)
    {
        $valid = [
            'default',
            'custom',
        ];

        if(in_array($choice, $valid, true))
        {
            return $choice;
        }

        return 'default';
    }
