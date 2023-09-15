<?php

    function render_block_core_post_content($attributes, $content, $block)
    {
        static $seen_ids = [];

        if(! isset($block->context['postId']))
        {
            return '';
        }

        $post_id = $block->context['postId'];

        if(isset($seen_ids[$post_id]))
        {
            // WP_DEBUG_DISPLAY must only be honored when WP_DEBUG. This precedent
            // is set in `wp_debug_mode()`.
            $is_debug = WP_DEBUG && WP_DEBUG_DISPLAY;

            return $is_debug ? // translators: Visible only in the front end, this warning takes the place of a faulty block.
                __('[block rendering halted]') : '';
        }

        $seen_ids[$post_id] = true;

        // Check is needed for backward compatibility with third-party plugins
        // that might rely on the `in_the_loop` check; calling `the_post` sets it to true.
        if(! in_the_loop() && have_posts())
        {
            the_post();
        }

        // When inside the main loop, we want to use queried object
        // so that `the_preview` for the current post can apply.
        // We force this behavior by omitting the third argument (post ID) from the `get_the_content`.
        $content = get_the_content();
        // Check for nextpage to display page links for paginated posts.
        if(has_block('core/nextpage'))
        {
            $content .= wp_link_pages(['echo' => 0]);
        }

        $content = apply_filters('the_content', str_replace(']]>', ']]&gt;', $content));
        unset($seen_ids[$post_id]);

        if(empty($content))
        {
            return '';
        }

        $wrapper_attributes = get_block_wrapper_attributes(['class' => 'entry-content']);

        return ('<div '.$wrapper_attributes.'>'.$content.'</div>');
    }

    function register_block_core_post_content()
    {
        register_block_type_from_metadata(__DIR__.'/post-content', [
            'render_callback' => 'render_block_core_post_content',
        ]);
    }

    add_action('init', 'register_block_core_post_content');
