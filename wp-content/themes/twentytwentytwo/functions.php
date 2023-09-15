<?php

    if(! function_exists('twentytwentytwo_support')) :

        function twentytwentytwo_support()
        {
            // Add support for block styles.
            add_theme_support('wp-block-styles');

            // Enqueue editor styles.
            add_editor_style('style.css');
        }

    endif;

    add_action('after_setup_theme', 'twentytwentytwo_support');

    if(! function_exists('twentytwentytwo_styles')) :

        function twentytwentytwo_styles()
        {
            // Register theme stylesheet.
            $theme_version = wp_get_theme()->get('Version');

            $version_string = is_string($theme_version) ? $theme_version : false;
            wp_register_style('twentytwentytwo-style', get_template_directory_uri().'/style.css', [], $version_string);

            // Enqueue theme stylesheet.
            wp_enqueue_style('twentytwentytwo-style');
        }

    endif;

    add_action('wp_enqueue_scripts', 'twentytwentytwo_styles');

// Add block patterns
    require get_template_directory().'/inc/block-patterns.php';
