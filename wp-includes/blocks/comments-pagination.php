<?php

    function render_block_core_comments_pagination($attributes, $content)
    {
        if(empty(trim($content)))
        {
            return '';
        }

        if(post_password_required())
        {
            return;
        }

        $classes = (isset($attributes['style']['elements']['link']['color']['text'])) ? 'has-link-color' : '';
        $wrapper_attributes = get_block_wrapper_attributes(['class' => $classes]);

        return sprintf('<div %1$s>%2$s</div>', $wrapper_attributes, $content);
    }

    function register_block_core_comments_pagination()
    {
        register_block_type_from_metadata(__DIR__.'/comments-pagination', [
            'render_callback' => 'render_block_core_comments_pagination',
        ]);
    }

    add_action('init', 'register_block_core_comments_pagination');
