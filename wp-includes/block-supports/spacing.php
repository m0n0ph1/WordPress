<?php

    function wp_register_spacing_support($block_type)
    {
        $has_spacing_support = block_has_support($block_type, 'spacing', false);

        // Setup attributes and styles within that if needed.
        if(! $block_type->attributes)
        {
            $block_type->attributes = [];
        }

        if($has_spacing_support && ! array_key_exists('style', $block_type->attributes))
        {
            $block_type->attributes['style'] = [
                'type' => 'object',
            ];
        }
    }

    function wp_apply_spacing_support($block_type, $block_attributes)
    {
        if(wp_should_skip_block_supports_serialization($block_type, 'spacing'))
        {
            return [];
        }

        $attributes = [];
        $has_padding_support = block_has_support($block_type, ['spacing', 'padding'], false);
        $has_margin_support = block_has_support($block_type, ['spacing', 'margin'], false);
        $block_styles = isset($block_attributes['style']) ? $block_attributes['style'] : null;

        if(! $block_styles)
        {
            return $attributes;
        }

        $skip_padding = wp_should_skip_block_supports_serialization($block_type, 'spacing', 'padding');
        $skip_margin = wp_should_skip_block_supports_serialization($block_type, 'spacing', 'margin');
        $spacing_block_styles = [];
        $spacing_block_styles['padding'] = $has_padding_support && ! $skip_padding ? _wp_array_get($block_styles, [
            'spacing',
            'padding'
        ],                                                                                         null) : null;
        $spacing_block_styles['margin'] = $has_margin_support && ! $skip_margin ? _wp_array_get($block_styles, [
            'spacing',
            'margin'
        ],                                                                                      null) : null;
        $styles = wp_style_engine_get_styles(['spacing' => $spacing_block_styles]);

        if(! empty($styles['css']))
        {
            $attributes['style'] = $styles['css'];
        }

        return $attributes;
    }

// Register the block support.
    WP_Block_Supports::get_instance()->register('spacing', [
        'register_attribute' => 'wp_register_spacing_support',
        'apply' => 'wp_apply_spacing_support',
    ]);
