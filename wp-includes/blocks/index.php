<?php

    define('BLOCKS_PATH', ABSPATH.WPINC.'/blocks/');

// Include files required for core blocks registration.
    require BLOCKS_PATH.'legacy-widget.php';
    require BLOCKS_PATH.'widget-group.php';
    require BLOCKS_PATH.'require-dynamic-blocks.php';

    function register_core_block_style_handles()
    {
        global $wp_version;

        if(! wp_should_load_separate_core_block_assets())
        {
            return;
        }

        $blocks_url = includes_url('blocks/');
        $suffix = wp_scripts_get_suffix();
        $wp_styles = wp_styles();
        $style_fields = [
            'style' => 'style',
            'editorStyle' => 'editor',
        ];

        static $core_blocks_meta;
        if(! $core_blocks_meta)
        {
            $core_blocks_meta = require BLOCKS_PATH.'blocks-json.php';
        }

        $files = false;
        $transient_name = 'wp_core_block_css_files';

        /*
         * Ignore transient cache when the development mode is set to 'core'. Why? To avoid interfering with
         * the core developer's workflow.
         */
        $can_use_cached = ! wp_is_development_mode('core');

        if($can_use_cached)
        {
            $cached_files = get_transient($transient_name);

            // Check the validity of cached values by checking against the current WordPress version.
            if(is_array($cached_files) && isset($cached_files['version']) && $cached_files['version'] === $wp_version && isset($cached_files['files']))
            {
                $files = $cached_files['files'];
            }
        }

        if(! $files)
        {
            $files = glob(wp_normalize_path(BLOCKS_PATH.'**
            $schema = apply_filters('block_type_metadata', $schema);

            // Backfill these properties similar to `register_block_type_from_metadata()`.
            if(! isset($schema['style']))
            {
                $schema['style'] = "wp-block-{$name}";
            }
            if(! isset($schema['editorStyle']))
            {
                $schema['editorStyle'] = "wp-block-{$name}-editor";
            }

            // Register block theme styles.
            $register_style($name, 'theme', "wp-block-{$name}-theme");

            foreach($style_fields as $style_field => $filename)
            {
                $style_handle = $schema[$style_field];
                if(is_array($style_handle))
                {
                    continue;
                }
                $register_style($name, $filename, $style_handle);
            }
        }
    }

    add_action('init', 'register_core_block_style_handles', 9);

    
    function register_core_block_types_from_metadata()
    {
        $block_folders = require BLOCKS_PATH.'require -static-blocks.php';
        foreach($block_folders as $block_folder)
        {
            register_block_type_from_metadata(BLOCKS_PATH.$block_folder);
        }
    }

    add_action('init', 'register_core_block_types_from_metadata');
