<?php

    function register_block_core_pattern()
    {
        register_block_type_from_metadata(__DIR__.'/pattern', [
            'render_callback' => 'render_block_core_pattern',
        ]);
    }

    function render_block_core_pattern($attributes)
    {
        if(empty($attributes['slug']))
        {
            return '';
        }

        $slug = $attributes['slug'];
        $registry = WP_Block_Patterns_Registry::get_instance();

        if(! $registry->is_registered($slug))
        {
            return '';
        }

        $pattern = $registry->get_registered($slug);

        return do_blocks($pattern['content']);
    }

    add_action('init', 'register_block_core_pattern');
