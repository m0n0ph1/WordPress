<?php

    function wp_register_dimensions_support($block_type)
    {
        // Setup attributes and styles within that if needed.
        if(! $block_type->attributes)
        {
            $block_type->attributes = [];
        }

        // Check for existing style attribute definition e.g. from block.json.
        if(array_key_exists('style', $block_type->attributes))
        {
            return;
        }

        $has_dimensions_support = block_has_support($block_type, 'dimensions', false);

        if($has_dimensions_support)
        {
            $block_type->attributes['style'] = [
                'type' => 'object',
            ];
        }
    }

    function wp_apply_dimensions_support($block_type, $block_attributes)
    { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
        if(wp_should_skip_block_supports_serialization($block_type, 'dimensions'))
        {
            return [];
        }

        $attributes = [];

        // Width support to be added in near future.

        $has_min_height_support = block_has_support($block_type, ['dimensions', 'minHeight'], false);
        $block_styles = isset($block_attributes['style']) ? $block_attributes['style'] : null;

        if(! $block_styles)
        {
            return $attributes;
        }

        $skip_min_height = wp_should_skip_block_supports_serialization($block_type, 'dimensions', 'minHeight');
        $dimensions_block_styles = [];
        $dimensions_block_styles['minHeight'] = $has_min_height_support && ! $skip_min_height ? _wp_array_get($block_styles, [
            'dimensions',
            'minHeight'
        ],                                                                                                    null) : null;
        $styles = wp_style_engine_get_styles(['dimensions' => $dimensions_block_styles]);

        if(! empty($styles['css']))
        {
            $attributes['style'] = $styles['css'];
        }

        return $attributes;
    }

// Register the block support.
    WP_Block_Supports::get_instance()->register('dimensions', [
        'register_attribute' => 'wp_register_dimensions_support',
        'apply' => 'wp_apply_dimensions_support',
    ]);
