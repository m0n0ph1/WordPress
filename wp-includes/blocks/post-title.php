<?php

    function render_block_core_post_title($attributes, $content, $block)
    {
        if(! isset($block->context['postId']))
        {
            return '';
        }

        $title = get_the_title();

        if(! $title)
        {
            return '';
        }

        $tag_name = 'h2';
        if(isset($attributes['level']))
        {
            $tag_name = 'h'.$attributes['level'];
        }

        if(isset($attributes['isLink']) && $attributes['isLink'])
        {
            $rel = ! empty($attributes['rel']) ? 'rel="'.esc_attr($attributes['rel']).'"' : '';
            $title = sprintf('<a href="%1$s" target="%2$s" %3$s>%4$s</a>', get_the_permalink($block->context['postId']), esc_attr($attributes['linkTarget']), $rel, $title);
        }

        $classes = [];
        if(isset($attributes['textAlign']))
        {
            $classes[] = 'has-text-align-'.$attributes['textAlign'];
        }
        if(isset($attributes['style']['elements']['link']['color']['text']))
        {
            $classes[] = 'has-link-color';
        }
        $wrapper_attributes = get_block_wrapper_attributes(['class' => implode(' ', $classes)]);

        return sprintf('<%1$s %2$s>%3$s</%1$s>', $tag_name, $wrapper_attributes, $title);
    }

    function register_block_core_post_title()
    {
        register_block_type_from_metadata(__DIR__.'/post-title', [
            'render_callback' => 'render_block_core_post_title',
        ]);
    }

    add_action('init', 'register_block_core_post_title');
