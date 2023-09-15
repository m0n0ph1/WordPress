<?php

    function render_block_core_post_author_biography($attributes, $content, $block)
    {
        if(! isset($block->context['postId']))
        {
            return '';
        }

        $author_id = get_post_field('post_author', $block->context['postId']);
        if(empty($author_id))
        {
            return '';
        }

        $author_biography = get_the_author_meta('description', $author_id);
        if(empty($author_biography))
        {
            return '';
        }

        $align_class_name = empty($attributes['textAlign']) ? '' : "has-text-align-{$attributes['textAlign']}";
        $wrapper_attributes = get_block_wrapper_attributes(['class' => $align_class_name]);

        return sprintf('<div %1$s>', $wrapper_attributes).$author_biography.'</div>';
    }

    function register_block_core_post_author_biography()
    {
        register_block_type_from_metadata(__DIR__.'/post-author-biography', [
            'render_callback' => 'render_block_core_post_author_biography',
        ]);
    }

    add_action('init', 'register_block_core_post_author_biography');
