<?php

    function render_block_core_block($attributes)
    {
        static $seen_refs = [];

        if(empty($attributes['ref']))
        {
            return '';
        }

        $reusable_block = get_post($attributes['ref']);
        if(! $reusable_block || 'wp_block' !== $reusable_block->post_type)
        {
            return '';
        }

        if(isset($seen_refs[$attributes['ref']]))
        {
            // WP_DEBUG_DISPLAY must only be honored when WP_DEBUG. This precedent
            // is set in `wp_debug_mode()`.
            $is_debug = WP_DEBUG && WP_DEBUG_DISPLAY;

            if($is_debug)
            {
                return __('[block rendering halted]');
            }

            return '';
        }

        if('publish' !== $reusable_block->post_status || ! empty($reusable_block->post_password))
        {
            return '';
        }

        $seen_refs[$attributes['ref']] = true;

        // Handle embeds for reusable blocks.
        global $wp_embed;
        $content = $wp_embed->run_shortcode($reusable_block->post_content);
        $content = $wp_embed->autoembed($content);

        $content = do_blocks($content);
        unset($seen_refs[$attributes['ref']]);

        return $content;
    }

    function register_block_core_block()
    {
        register_block_type_from_metadata(__DIR__.'/block', [
            'render_callback' => 'render_block_core_block',
        ]);
    }

    add_action('init', 'register_block_core_block');
