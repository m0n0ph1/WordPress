<?php

    function render_block_core_query_pagination_previous($attributes, $content, $block)
    {
        $page_key = isset($block->context['queryId']) ? 'query-'.$block->context['queryId'].'-page' : 'query-page';
        $page = empty($_GET[$page_key]) ? 1 : (int) $_GET[$page_key];

        $wrapper_attributes = get_block_wrapper_attributes();
        $show_label = isset($block->context['showLabel']) ? (bool) $block->context['showLabel'] : true;
        $default_label = __('Previous Page');
        $label_text = isset($attributes['label']) && ! empty($attributes['label']) ? esc_html($attributes['label']) : $default_label;
        $label = $show_label ? $label_text : '';
        $pagination_arrow = get_query_pagination_arrow($block, false);
        if(! $label)
        {
            $wrapper_attributes .= ' aria-label="'.$label_text.'"';
        }
        if($pagination_arrow)
        {
            $label = $pagination_arrow.$label;
        }
        $content = '';
        // Check if the pagination is for Query that inherits the global context
        // and handle appropriately.
        if(isset($block->context['query']['inherit']) && $block->context['query']['inherit'])
        {
            $filter_link_attributes = static function() use ($wrapper_attributes)
            {
                return $wrapper_attributes;
            };

            add_filter('previous_posts_link_attributes', $filter_link_attributes);
            $content = get_previous_posts_link($label);
            remove_filter('previous_posts_link_attributes', $filter_link_attributes);
        }
        elseif(1 !== $page)
        {
            $content = sprintf('<a href="%1$s" %2$s>%3$s</a>', esc_url(add_query_arg($page_key, $page - 1)), $wrapper_attributes, $label);
        }

        return $content;
    }

    function register_block_core_query_pagination_previous()
    {
        register_block_type_from_metadata(__DIR__.'/query-pagination-previous', [
            'render_callback' => 'render_block_core_query_pagination_previous',
        ]);
    }

    add_action('init', 'register_block_core_query_pagination_previous');
