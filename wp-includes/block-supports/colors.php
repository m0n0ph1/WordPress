<?php

    function wp_register_colors_support($block_type)
    {
        $color_support = property_exists($block_type, 'supports') ? _wp_array_get($block_type->supports, ['color'], false) : false;
        $has_text_colors_support = true === $color_support || (is_array($color_support) && _wp_array_get($color_support, ['text'], true));
        $has_background_colors_support = true === $color_support || (is_array($color_support) && _wp_array_get($color_support, ['background'], true));
        $has_gradients_support = _wp_array_get($color_support, ['gradients'], false);
        $has_link_colors_support = _wp_array_get($color_support, ['link'], false);
        $has_color_support = $has_text_colors_support || $has_background_colors_support || $has_gradients_support || $has_link_colors_support;

        if(! $block_type->attributes)
        {
            $block_type->attributes = [];
        }

        if($has_color_support && ! array_key_exists('style', $block_type->attributes))
        {
            $block_type->attributes['style'] = [
                'type' => 'object',
            ];
        }

        if($has_background_colors_support && ! array_key_exists('backgroundColor', $block_type->attributes))
        {
            $block_type->attributes['backgroundColor'] = [
                'type' => 'string',
            ];
        }

        if($has_text_colors_support && ! array_key_exists('textColor', $block_type->attributes))
        {
            $block_type->attributes['textColor'] = [
                'type' => 'string',
            ];
        }

        if($has_gradients_support && ! array_key_exists('gradient', $block_type->attributes))
        {
            $block_type->attributes['gradient'] = [
                'type' => 'string',
            ];
        }
    }

    function wp_apply_colors_support($block_type, $block_attributes)
    {
        $color_support = _wp_array_get($block_type->supports, ['color'], false);

        if(is_array($color_support) && wp_should_skip_block_supports_serialization($block_type, 'color'))
        {
            return [];
        }

        $has_text_colors_support = true === $color_support || (is_array($color_support) && _wp_array_get($color_support, ['text'], true));
        $has_background_colors_support = true === $color_support || (is_array($color_support) && _wp_array_get($color_support, ['background'], true));
        $has_gradients_support = _wp_array_get($color_support, ['gradients'], false);
        $color_block_styles = [];

        // Text colors.
        if($has_text_colors_support && ! wp_should_skip_block_supports_serialization($block_type, 'color', 'text'))
        {
            $preset_text_color = array_key_exists('textColor', $block_attributes) ? "var:preset|color|{$block_attributes['textColor']}" : null;
            $custom_text_color = _wp_array_get($block_attributes, ['style', 'color', 'text'], null);
            $color_block_styles['text'] = $preset_text_color ? $preset_text_color : $custom_text_color;
        }

        // Background colors.
        if($has_background_colors_support && ! wp_should_skip_block_supports_serialization($block_type, 'color', 'background'))
        {
            $preset_background_color = array_key_exists('backgroundColor', $block_attributes) ? "var:preset|color|{$block_attributes['backgroundColor']}" : null;
            $custom_background_color = _wp_array_get($block_attributes, ['style', 'color', 'background'], null);
            $color_block_styles['background'] = $preset_background_color ? $preset_background_color : $custom_background_color;
        }

        // Gradients.
        if($has_gradients_support && ! wp_should_skip_block_supports_serialization($block_type, 'color', 'gradients'))
        {
            $preset_gradient_color = array_key_exists('gradient', $block_attributes) ? "var:preset|gradient|{$block_attributes['gradient']}" : null;
            $custom_gradient_color = _wp_array_get($block_attributes, ['style', 'color', 'gradient'], null);
            $color_block_styles['gradient'] = $preset_gradient_color ? $preset_gradient_color : $custom_gradient_color;
        }

        $attributes = [];
        $styles = wp_style_engine_get_styles(['color' => $color_block_styles], ['convert_vars_to_classnames' => true]);

        if(! empty($styles['classnames']))
        {
            $attributes['class'] = $styles['classnames'];
        }

        if(! empty($styles['css']))
        {
            $attributes['style'] = $styles['css'];
        }

        return $attributes;
    }

// Register the block support.
    WP_Block_Supports::get_instance()->register('colors', [
        'register_attribute' => 'wp_register_colors_support',
        'apply' => 'wp_apply_colors_support',
    ]);
