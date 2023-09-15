<?php

    function render_block_core_shortcode($attributes, $content)
    {
        return wpautop($content);
    }

    function register_block_core_shortcode()
    {
        register_block_type_from_metadata(__DIR__.'/shortcode', [
            'render_callback' => 'render_block_core_shortcode',
        ]);
    }

    add_action('init', 'register_block_core_shortcode');
