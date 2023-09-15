<?php

    function wp_register_position_support($block_type)
    {
        $has_position_support = block_has_support($block_type, 'position', false);

        // Set up attributes and styles within that if needed.
        if(! $block_type->attributes)
        {
            $block_type->attributes = [];
        }

        if($has_position_support && ! array_key_exists('style', $block_type->attributes))
        {
            $block_type->attributes['style'] = [
                'type' => 'object',
            ];
        }
    }

    function wp_render_position_support($block_content, $block)
    {
        $block_type = WP_Block_Type_Registry::get_instance()->get_registered($block['blockName']);
        $has_position_support = block_has_support($block_type, 'position', false);

        if(! $has_position_support || empty($block['attrs']['style']['position']))
        {
            return $block_content;
        }

        $global_settings = wp_get_global_settings();
        $theme_has_sticky_support = _wp_array_get($global_settings, ['position', 'sticky'], false);
        $theme_has_fixed_support = _wp_array_get($global_settings, ['position', 'fixed'], false);

        // Only allow output for position types that the theme supports.
        $allowed_position_types = [];
        if(true === $theme_has_sticky_support)
        {
            $allowed_position_types[] = 'sticky';
        }
        if(true === $theme_has_fixed_support)
        {
            $allowed_position_types[] = 'fixed';
        }

        $style_attribute = _wp_array_get($block, ['attrs', 'style'], null);
        $class_name = wp_unique_id('wp-container-');
        $selector = ".$class_name";
        $position_styles = [];
        $position_type = _wp_array_get($style_attribute, ['position', 'type'], '');
        $wrapper_classes = [];

        if(in_array($position_type, $allowed_position_types, true))
        {
            $wrapper_classes[] = $class_name;
            $wrapper_classes[] = 'is-position-'.$position_type;
            $sides = ['top', 'right', 'bottom', 'left'];

            foreach($sides as $side)
            {
                $side_value = _wp_array_get($style_attribute, ['position', $side]);
                if(null !== $side_value)
                {
                    /*
                     * For fixed or sticky top positions,
                     * ensure the value includes an offset for the logged in admin bar.
                     */
                    if('top' === $side && ('fixed' === $position_type || 'sticky' === $position_type))
                    {
                        // Ensure 0 values can be used in `calc()` calculations.
                        if('0' === $side_value || 0 === $side_value)
                        {
                            $side_value = '0px';
                        }

                        // Ensure current side value also factors in the height of the logged in admin bar.
                        $side_value = "calc($side_value + var(--wp-admin--admin-bar--position-offset, 0px))";
                    }

                    $position_styles[] = [
                        'selector' => $selector,
                        'declarations' => [
                            $side => $side_value,
                        ],
                    ];
                }
            }

            $position_styles[] = [
                'selector' => $selector,
                'declarations' => [
                    'position' => $position_type,
                    'z-index' => '10',
                ],
            ];
        }

        if(! empty($position_styles))
        {
            /*
             * Add to the style engine store to enqueue and render position styles.
             */
            wp_style_engine_get_stylesheet_from_css_rules($position_styles, [
                'context' => 'block-supports',
                'prettify' => false,
            ]);

            // Inject class name to block container markup.
            $content = new WP_HTML_Tag_Processor($block_content);
            $content->next_tag();
            foreach($wrapper_classes as $class)
            {
                $content->add_class($class);
            }

            return (string) $content;
        }

        return $block_content;
    }

// Register the block support.
    WP_Block_Supports::get_instance()->register('position', [
        'register_attribute' => 'wp_register_position_support',
    ]);
    add_filter('render_block', 'wp_render_position_support', 10, 2);
