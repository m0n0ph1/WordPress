<?php

    function block_core_post_template_uses_featured_image($inner_blocks)
    {
        foreach($inner_blocks as $block)
        {
            if('core/post-featured-image' === $block->name)
            {
                return true;
            }
            if('core/cover' === $block->name && ! empty($block->attributes['useFeaturedImage']))
            {
                return true;
            }
            if($block->inner_blocks && block_core_post_template_uses_featured_image($block->inner_blocks))
            {
                return true;
            }
        }

        return false;
    }

    function render_block_core_post_template($attributes, $content, $block)
    {
        $page_key = isset($block->context['queryId']) ? 'query-'.$block->context['queryId'].'-page' : 'query-page';
        $page = empty($_GET[$page_key]) ? 1 : (int) $_GET[$page_key];

        // Use global query if needed.
        $use_global_query = (isset($block->context['query']['inherit']) && $block->context['query']['inherit']);
        if($use_global_query)
        {
            global $wp_query;
            $query = clone $wp_query;
        }
        else
        {
            $query_args = build_query_vars_from_query_block($block, $page);
            $query = new WP_Query($query_args);
        }

        if(! $query->have_posts())
        {
            return '';
        }

        if(block_core_post_template_uses_featured_image($block->inner_blocks))
        {
            update_post_thumbnail_cache($query);
        }

        $classnames = '';
        if(isset($block->context['displayLayout']) && isset($block->context['query']))
        {
            if(isset($block->context['displayLayout']['type']) && 'flex' === $block->context['displayLayout']['type'])
            {
                $classnames = "is-flex-container columns-{$block->context['displayLayout']['columns']}";
            }
        }
        if(isset($attributes['style']['elements']['link']['color']['text']))
        {
            $classnames .= ' has-link-color';
        }

        // Ensure backwards compatibility by flagging the number of columns via classname when using grid layout.
        if(isset($attributes['layout']['type']) && 'grid' === $attributes['layout']['type'] && ! empty($attributes['layout']['columnCount']))
        {
            $classnames .= ' '.sanitize_title('columns-'.$attributes['layout']['columnCount']);
        }

        $wrapper_attributes = get_block_wrapper_attributes(['class' => trim($classnames)]);

        $content = '';
        while($query->have_posts())
        {
            $query->the_post();

            // Get an instance of the current Post Template block.
            $block_instance = $block->parsed_block;

            // Set the block name to one that does not correspond to an existing registered block.
            // This ensures that for the inner instances of the Post Template block, we do not render any block supports.
            $block_instance['blockName'] = 'core/null';

            $post_id = get_the_ID();
            $post_type = get_post_type();
            $filter_block_context = static function($context) use ($post_id, $post_type)
            {
                $context['postType'] = $post_type;
                $context['postId'] = $post_id;

                return $context;
            };

            // Use an early priority to so that other 'render_block_context' filters have access to the values.
            add_filter('render_block_context', $filter_block_context, 1);
            // Render the inner blocks of the Post Template block with `dynamic` set to `false` to prevent calling
            // `render_callback` and ensure that no wrapper markup is included.
            $block_content = (new WP_Block($block_instance))->render(['dynamic' => false]);
            remove_filter('render_block_context', $filter_block_context, 1);

            // Wrap the render inner blocks in a `li` element with the appropriate post classes.
            $post_classes = implode(' ', get_post_class('wp-block-post'));
            $content .= '<li class="'.esc_attr($post_classes).'">'.$block_content.'</li>';
        }

        /*
         * Use this function to restore the context of the template tags
         * from a secondary query loop back to the main query loop.
         * Since we use two custom loops, it's safest to always restore.
        */
        wp_reset_postdata();

        return sprintf('<ul %1$s>%2$s</ul>', $wrapper_attributes, $content);
    }

    function register_block_core_post_template()
    {
        register_block_type_from_metadata(__DIR__.'/post-template', [
            'render_callback' => 'render_block_core_post_template',
            'skip_inner_blocks' => true,
        ]);
    }

    add_action('init', 'register_block_core_post_template');
