<?php

    function render_block_core_query_pagination($attributes, $content)
    {
        if(empty(trim($content)))
        {
            return '';
        }

        $classes = (isset($attributes['style']['elements']['link']['color']['text'])) ? 'has-link-color' : '';
        $wrapper_attributes = get_block_wrapper_attributes([
                                                               'aria-label' => __('Pagination'),
                                                               'class' => $classes,
                                                           ]);

        return sprintf('<nav %1$s>%2$s</nav>', $wrapper_attributes, $content);
    }

    function register_block_core_query_pagination()
    {
        register_block_type_from_metadata(__DIR__.'/query-pagination', [
            'render_callback' => 'render_block_core_query_pagination',
        ]);
    }

    add_action('init', 'register_block_core_query_pagination');
