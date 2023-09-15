<?php

    function render_block_core_post_excerpt($attributes, $content, $block)
    {
        if(! isset($block->context['postId']))
        {
            return '';
        }

        /*
        * The purpose of the excerpt length setting is to limit the length of both
        * automatically generated and user-created excerpts.
        * Because the excerpt_length filter only applies to auto generated excerpts,
        * wp_trim_words is used instead.
        */
        $excerpt_length = $attributes['excerptLength'];
        $excerpt = get_the_excerpt($block->context['postId']);
        if(isset($excerpt_length))
        {
            $excerpt = wp_trim_words($excerpt, $excerpt_length);
        }

        $more_text = ! empty($attributes['moreText']) ? '<a class="wp-block-post-excerpt__more-link" href="'.esc_url(get_the_permalink($block->context['postId'])).'">'.wp_kses_post($attributes['moreText']).'</a>' : '';
        $filter_excerpt_more = static function($more) use ($more_text)
        {
            if(empty($more_text))
            {
                return $more;
            }

            return '';
        };

        add_filter('excerpt_more', $filter_excerpt_more);
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

        $content = '<p class="wp-block-post-excerpt__excerpt">'.$excerpt;
        $show_more_on_new_line = ! isset($attributes['showMoreOnNewLine']) || $attributes['showMoreOnNewLine'];
        if($show_more_on_new_line && ! empty($more_text))
        {
            $content .= '</p><p class="wp-block-post-excerpt__more-text">'.$more_text.'</p>';
        }
        else
        {
            $content .= " $more_text</p>";
        }
        remove_filter('excerpt_more', $filter_excerpt_more);

        return sprintf('<div %1$s>%2$s</div>', $wrapper_attributes, $content);
    }

    function register_block_core_post_excerpt()
    {
        register_block_type_from_metadata(__DIR__.'/post-excerpt', [
            'render_callback' => 'render_block_core_post_excerpt',
        ]);
    }

    add_action('init', 'register_block_core_post_excerpt');

    if(is_admin() || defined('REST_REQUEST') && REST_REQUEST)
    {
        add_filter('excerpt_length', static function()
        {
            return 100;
        },         PHP_INT_MAX);
    }
