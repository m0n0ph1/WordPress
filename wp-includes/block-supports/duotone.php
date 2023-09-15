<?php

// Register the block support.
    WP_Block_Supports::get_instance()->register('duotone', [
        'register_attribute' => ['WP_Duotone', 'register_duotone_support'],
    ]);

// Add classnames to blocks using duotone support.
    add_filter('render_block', ['WP_Duotone', 'render_duotone_support'], 10, 3);

// Enqueue styles.
// Block styles (core-block-supports-inline-css) before the style engine (wp_enqueue_stored_styles).
// Global styles (global-styles-inline-css) after the other global styles (wp_enqueue_global_styles).
    add_action('wp_enqueue_scripts', ['WP_Duotone', 'output_block_styles'], 9);
    add_action('wp_enqueue_scripts', ['WP_Duotone', 'output_global_styles'], 11);

// Add SVG filters to the footer. Also, for classic themes, output block styles (core-block-supports-inline-css).
    add_action('wp_footer', ['WP_Duotone', 'output_footer_assets'], 10);

// Add styles and SVGs for use in the editor via the EditorStyles component.
    add_filter('block_editor_settings_all', ['WP_Duotone', 'add_editor_settings'], 10);

// Migrate the old experimental duotone support flag.
    add_filter('block_type_metadata_settings', ['WP_Duotone', 'migrate_experimental_duotone_support_flag'], 10, 2);
