<?php

    if(function_exists('register_block_style'))
    {
        function twenty_twenty_one_register_block_styles()
        {
            // Columns: Overlap.
            register_block_style('core/columns', [
                'name' => 'twentytwentyone-columns-overlap',
                'label' => esc_html__('Overlap', 'twentytwentyone'),
            ]);

            // Cover: Borders.
            register_block_style('core/cover', [
                'name' => 'twentytwentyone-border',
                'label' => esc_html__('Borders', 'twentytwentyone'),
            ]);

            // Group: Borders.
            register_block_style('core/group', [
                'name' => 'twentytwentyone-border',
                'label' => esc_html__('Borders', 'twentytwentyone'),
            ]);

            // Image: Borders.
            register_block_style('core/image', [
                'name' => 'twentytwentyone-border',
                'label' => esc_html__('Borders', 'twentytwentyone'),
            ]);

            // Image: Frame.
            register_block_style('core/image', [
                'name' => 'twentytwentyone-image-frame',
                'label' => esc_html__('Frame', 'twentytwentyone'),
            ]);

            // Latest Posts: Dividers.
            register_block_style('core/latest-posts', [
                'name' => 'twentytwentyone-latest-posts-dividers',
                'label' => esc_html__('Dividers', 'twentytwentyone'),
            ]);

            // Latest Posts: Borders.
            register_block_style('core/latest-posts', [
                'name' => 'twentytwentyone-latest-posts-borders',
                'label' => esc_html__('Borders', 'twentytwentyone'),
            ]);

            // Media & Text: Borders.
            register_block_style('core/media-text', [
                'name' => 'twentytwentyone-border',
                'label' => esc_html__('Borders', 'twentytwentyone'),
            ]);

            // Separator: Thick.
            register_block_style('core/separator', [
                'name' => 'twentytwentyone-separator-thick',
                'label' => esc_html__('Thick', 'twentytwentyone'),
            ]);

            // Social icons: Dark gray color.
            register_block_style('core/social-links', [
                'name' => 'twentytwentyone-social-icons-color',
                'label' => esc_html__('Dark gray', 'twentytwentyone'),
            ]);
        }

        add_action('init', 'twenty_twenty_one_register_block_styles');
    }
