<?php

    function render_block_core_search($attributes, $content, $block)
    {
        // Older versions of the Search block defaulted the label and buttonText
        // attributes to `__( 'Search' )` meaning that many posts contain `<!--
        // wp:search /-->`. Support these by defaulting an undefined label and
        // buttonText to `__( 'Search' )`.
        $attributes = wp_parse_args($attributes, [
            'label' => __('Search'),
            'buttonText' => __('Search'),
        ]);

        $input_id = wp_unique_id('wp-block-search__input-');
        $classnames = classnames_for_block_core_search($attributes);
        $show_label = (! empty($attributes['showLabel'])) ? true : false;
        $use_icon_button = (! empty($attributes['buttonUseIcon'])) ? true : false;
        $show_button = (! empty($attributes['buttonPosition']) && 'no-button' === $attributes['buttonPosition']) ? false : true;
        $button_position = $show_button ? $attributes['buttonPosition'] : null;
        $query_params = (! empty($attributes['query'])) ? $attributes['query'] : [];
        $button_behavior = (! empty($attributes['buttonBehavior'])) ? $attributes['buttonBehavior'] : 'default';
        $button = '';
        $query_params_markup = '';
        $inline_styles = styles_for_block_core_search($attributes);
        $color_classes = get_color_classes_for_block_core_search($attributes);
        $typography_classes = get_typography_classes_for_block_core_search($attributes);
        $is_button_inside = ! empty($attributes['buttonPosition']) && 'button-inside' === $attributes['buttonPosition'];
        // Border color classes need to be applied to the elements that have a border color.
        $border_color_classes = get_border_color_classes_for_block_core_search($attributes);

        $label_inner_html = empty($attributes['label']) ? __('Search') : wp_kses_post($attributes['label']);
        $label = new WP_HTML_Tag_Processor(sprintf('<label %1$s>%2$s</label>', $inline_styles['label'], $label_inner_html));
        if($label->next_tag())
        {
            $label->set_attribute('for', $input_id);
            $label->add_class('wp-block-search__label');
            if($show_label && ! empty($attributes['label']))
            {
                if(! empty($typography_classes))
                {
                    $label->add_class($typography_classes);
                }
            }
            else
            {
                $label->add_class('screen-reader-text');
            }
        }

        $input = new WP_HTML_Tag_Processor(sprintf('<input type="search" name="s" required %s/>', $inline_styles['input']));
        $input_classes = ['wp-block-search__input'];
        if(! $is_button_inside && ! empty($border_color_classes))
        {
            $input_classes[] = $border_color_classes;
        }
        if(! empty($typography_classes))
        {
            $input_classes[] = $typography_classes;
        }
        if($input->next_tag())
        {
            $input->add_class(implode(' ', $input_classes));
            $input->set_attribute('id', $input_id);
            $input->set_attribute('value', get_search_query());
            $input->set_attribute('placeholder', $attributes['placeholder']);

            $is_expandable_searchfield = 'button-only' === $button_position && 'expand-searchfield' === $button_behavior;
            if($is_expandable_searchfield)
            {
                $input->set_attribute('aria-hidden', 'true');
                $input->set_attribute('tabindex', '-1');
            }

            // If the script already exists, there is no point in removing it from viewScript.
            $view_js_file = 'wp-block-search-view';
            if(! wp_script_is($view_js_file))
            {
                $script_handles = $block->block_type->view_script_handles;

                // If the script is not needed, and it is still in the `view_script_handles`, remove it.
                if(! $is_expandable_searchfield && in_array($view_js_file, $script_handles, true))
                {
                    $block->block_type->view_script_handles = array_diff($script_handles, [$view_js_file]);
                }
                // If the script is needed, but it was previously removed, add it again.
                if($is_expandable_searchfield && ! in_array($view_js_file, $script_handles, true))
                {
                    $block->block_type->view_script_handles = array_merge($script_handles, [$view_js_file]);
                }
            }
        }

        if(count($query_params) > 0)
        {
            foreach($query_params as $param => $value)
            {
                $query_params_markup .= sprintf('<input type="hidden" name="%s" value="%s" />', esc_attr($param), esc_attr($value));
            }
        }

        if($show_button)
        {
            $button_classes = ['wp-block-search__button'];
            $button_internal_markup = '';
            if(! empty($color_classes))
            {
                $button_classes[] = $color_classes;
            }
            if(! empty($typography_classes))
            {
                $button_classes[] = $typography_classes;
            }

            if(! $is_button_inside && ! empty($border_color_classes))
            {
                $button_classes[] = $border_color_classes;
            }
            if(! $use_icon_button)
            {
                if(! empty($attributes['buttonText']))
                {
                    $button_internal_markup = wp_kses_post($attributes['buttonText']);
                }
            }
            else
            {
                $button_classes[] = 'has-icon';
                $button_internal_markup = '<svg class="search-icon" viewBox="0 0 24 24" width="24" height="24">
					<path d="M13 5c-3.3 0-6 2.7-6 6 0 1.4.5 2.7 1.3 3.7l-3.8 3.8 1.1 1.1 3.8-3.8c1 .8 2.3 1.3 3.7 1.3 3.3 0 6-2.7 6-6S16.3 5 13 5zm0 10.5c-2.5 0-4.5-2-4.5-4.5s2-4.5 4.5-4.5 4.5 2 4.5 4.5-2 4.5-4.5 4.5z"></path>
				</svg>';
            }

            // Include the button element class.
            $button_classes[] = wp_theme_get_element_class_name('button');
            $button = new WP_HTML_Tag_Processor(sprintf('<button type="submit" %s>%s</button>', $inline_styles['button'], $button_internal_markup));

            if($button->next_tag())
            {
                $button->add_class(implode(' ', $button_classes));
                if('expand-searchfield' === $attributes['buttonBehavior'] && 'button-only' === $attributes['buttonPosition'])
                {
                    $button->set_attribute('aria-label', __('Expand search field'));
                    $button->set_attribute('aria-controls', 'wp-block-search__input-'.$input_id);
                    $button->set_attribute('aria-expanded', 'false');
                }
                else
                {
                    $button->set_attribute('aria-label', wp_strip_all_tags($attributes['buttonText']));
                }
            }
        }

        $field_markup_classes = $is_button_inside ? $border_color_classes : '';
        $field_markup = sprintf('<div class="wp-block-search__inside-wrapper %s" %s>%s</div>', esc_attr($field_markup_classes), $inline_styles['wrapper'], $input.$query_params_markup.$button);
        $wrapper_attributes = get_block_wrapper_attributes(['class' => $classnames]);

        return sprintf('<form role="search" method="get" action="%s" %s>%s</form>', esc_url(home_url('/')), $wrapper_attributes, $label.$field_markup);
    }

    function register_block_core_search()
    {
        register_block_type_from_metadata(__DIR__.'/search', [
            'render_callback' => 'render_block_core_search',
        ]);
    }

    add_action('init', 'register_block_core_search');

    function classnames_for_block_core_search($attributes)
    {
        $classnames = [];

        if(! empty($attributes['buttonPosition']))
        {
            if('button-inside' === $attributes['buttonPosition'])
            {
                $classnames[] = 'wp-block-search__button-inside';
            }

            if('button-outside' === $attributes['buttonPosition'])
            {
                $classnames[] = 'wp-block-search__button-outside';
            }

            if('no-button' === $attributes['buttonPosition'])
            {
                $classnames[] = 'wp-block-search__no-button';
            }

            if('button-only' === $attributes['buttonPosition'])
            {
                $classnames[] = 'wp-block-search__button-only';
                if(! empty($attributes['buttonBehavior']) && 'expand-searchfield' === $attributes['buttonBehavior'])
                {
                    $classnames[] = 'wp-block-search__button-behavior-expand wp-block-search__searchfield-hidden';
                }
            }
        }

        if(isset($attributes['buttonUseIcon']))
        {
            if(! empty($attributes['buttonPosition']) && 'no-button' !== $attributes['buttonPosition'])
            {
                if($attributes['buttonUseIcon'])
                {
                    $classnames[] = 'wp-block-search__icon-button';
                }
                else
                {
                    $classnames[] = 'wp-block-search__text-button';
                }
            }
        }

        return implode(' ', $classnames);
    }

    function apply_block_core_search_border_style(
        $attributes, $property, $side, &$wrapper_styles, &$button_styles, &$input_styles
    ) {
        $is_button_inside = 'button-inside' === _wp_array_get($attributes, ['buttonPosition'], false);

        $path = ['style', 'border', $property];

        if($side)
        {
            array_splice($path, 2, 0, $side);
        }

        $value = _wp_array_get($attributes, $path, false);

        if(empty($value))
        {
            return;
        }

        if('color' === $property && $side)
        {
            $has_color_preset = str_contains($value, 'var:preset|color|');
            if($has_color_preset)
            {
                $named_color_value = substr($value, strrpos($value, '|') + 1);
                $value = sprintf('var(--wp--preset--color--%s)', $named_color_value);
            }
        }

        $property_suffix = $side ? sprintf('%s-%s', $side, $property) : $property;

        if($is_button_inside)
        {
            $wrapper_styles[] = sprintf('border-%s: %s;', $property_suffix, esc_attr($value));
        }
        else
        {
            $button_styles[] = sprintf('border-%s: %s;', $property_suffix, esc_attr($value));
            $input_styles[] = sprintf('border-%s: %s;', $property_suffix, esc_attr($value));
        }
    }

    function apply_block_core_search_border_styles(
        $attributes, $property, &$wrapper_styles, &$button_styles, &$input_styles
    ) {
        apply_block_core_search_border_style($attributes, $property, null, $wrapper_styles, $button_styles, $input_styles);
        apply_block_core_search_border_style($attributes, $property, 'top', $wrapper_styles, $button_styles, $input_styles);
        apply_block_core_search_border_style($attributes, $property, 'right', $wrapper_styles, $button_styles, $input_styles);
        apply_block_core_search_border_style($attributes, $property, 'bottom', $wrapper_styles, $button_styles, $input_styles);
        apply_block_core_search_border_style($attributes, $property, 'left', $wrapper_styles, $button_styles, $input_styles);
    }

    function styles_for_block_core_search($attributes)
    {
        $wrapper_styles = [];
        $button_styles = [];
        $input_styles = [];
        $label_styles = [];
        $is_button_inside = ! empty($attributes['buttonPosition']) && 'button-inside' === $attributes['buttonPosition'];
        $show_label = (isset($attributes['showLabel'])) && false !== $attributes['showLabel'];

        // Add width styles.
        $has_width = ! empty($attributes['width']) && ! empty($attributes['widthUnit']);

        if($has_width)
        {
            $wrapper_styles[] = sprintf('width: %d%s;', esc_attr($attributes['width']), esc_attr($attributes['widthUnit']));
        }

        // Add border width and color styles.
        apply_block_core_search_border_styles($attributes, 'width', $wrapper_styles, $button_styles, $input_styles);
        apply_block_core_search_border_styles($attributes, 'color', $wrapper_styles, $button_styles, $input_styles);
        apply_block_core_search_border_styles($attributes, 'style', $wrapper_styles, $button_styles, $input_styles);

        // Add border radius styles.
        $has_border_radius = ! empty($attributes['style']['border']['radius']);

        if($has_border_radius)
        {
            $default_padding = '4px';
            $border_radius = $attributes['style']['border']['radius'];

            if(is_array($border_radius))
            {
                // Apply styles for individual corner border radii.
                foreach($border_radius as $key => $value)
                {
                    if(null !== $value)
                    {
                        // Convert camelCase key to kebab-case.
                        $name = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $key));

                        // Add shared styles for individual border radii for input & button.
                        $border_style = sprintf('border-%s-radius: %s;', esc_attr($name), esc_attr($value));
                        $input_styles[] = $border_style;
                        $button_styles[] = $border_style;

                        // Add adjusted border radius styles for the wrapper element
                        // if button is positioned inside.
                        if($is_button_inside && intval($value) !== 0)
                        {
                            $wrapper_styles[] = sprintf('border-%s-radius: calc(%s + %s);', esc_attr($name), esc_attr($value), $default_padding);
                        }
                    }
                }
            }
            else
            {
                // Numeric check is for backwards compatibility purposes.
                $border_radius = is_numeric($border_radius) ? $border_radius.'px' : $border_radius;
                $border_style = sprintf('border-radius: %s;', esc_attr($border_radius));
                $input_styles[] = $border_style;
                $button_styles[] = $border_style;

                if($is_button_inside && intval($border_radius) !== 0)
                {
                    // Adjust wrapper border radii to maintain visual consistency
                    // with inner elements when button is positioned inside.
                    $wrapper_styles[] = sprintf('border-radius: calc(%s + %s);', esc_attr($border_radius), $default_padding);
                }
            }
        }

        // Add color styles.
        $has_text_color = ! empty($attributes['style']['color']['text']);
        if($has_text_color)
        {
            $button_styles[] = sprintf('color: %s;', $attributes['style']['color']['text']);
        }

        $has_background_color = ! empty($attributes['style']['color']['background']);
        if($has_background_color)
        {
            $button_styles[] = sprintf('background-color: %s;', $attributes['style']['color']['background']);
        }

        $has_custom_gradient = ! empty($attributes['style']['color']['gradient']);
        if($has_custom_gradient)
        {
            $button_styles[] = sprintf('background: %s;', $attributes['style']['color']['gradient']);
        }

        // Get typography styles to be shared across inner elements.
        $typography_styles = esc_attr(get_typography_styles_for_block_core_search($attributes));
        if(! empty($typography_styles))
        {
            $label_styles [] = $typography_styles;
            $button_styles[] = $typography_styles;
            $input_styles [] = $typography_styles;
        }

        // Typography text-decoration is only applied to the label and button.
        if(! empty($attributes['style']['typography']['textDecoration']))
        {
            $text_decoration_value = sprintf('text-decoration: %s;', esc_attr($attributes['style']['typography']['textDecoration']));
            $button_styles[] = $text_decoration_value;
            // Input opts out of text decoration.
            if($show_label)
            {
                $label_styles[] = $text_decoration_value;
            }
        }

        return [
            'input' => ! empty($input_styles) ? sprintf(' style="%s"', esc_attr(safecss_filter_attr(implode(' ', $input_styles)))) : '',
            'button' => ! empty($button_styles) ? sprintf(' style="%s"', esc_attr(safecss_filter_attr(implode(' ', $button_styles)))) : '',
            'wrapper' => ! empty($wrapper_styles) ? sprintf(' style="%s"', esc_attr(safecss_filter_attr(implode(' ', $wrapper_styles)))) : '',
            'label' => ! empty($label_styles) ? sprintf(' style="%s"', esc_attr(safecss_filter_attr(implode(' ', $label_styles)))) : '',
        ];
    }

    function get_typography_classes_for_block_core_search($attributes)
    {
        $typography_classes = [];
        $has_named_font_family = ! empty($attributes['fontFamily']);
        $has_named_font_size = ! empty($attributes['fontSize']);

        if($has_named_font_size)
        {
            $typography_classes[] = sprintf('has-%s-font-size', esc_attr($attributes['fontSize']));
        }

        if($has_named_font_family)
        {
            $typography_classes[] = sprintf('has-%s-font-family', esc_attr($attributes['fontFamily']));
        }

        return implode(' ', $typography_classes);
    }

    function get_typography_styles_for_block_core_search($attributes)
    {
        $typography_styles = [];

        // Add typography styles.
        if(! empty($attributes['style']['typography']['fontSize']))
        {
            $typography_styles[] = sprintf(
                'font-size: %s;', wp_get_typography_font_size_value([
                                                                        'size' => $attributes['style']['typography']['fontSize'],
                                                                    ])
            );
        }

        if(! empty($attributes['style']['typography']['fontFamily']))
        {
            $typography_styles[] = sprintf('font-family: %s;', $attributes['style']['typography']['fontFamily']);
        }

        if(! empty($attributes['style']['typography']['letterSpacing']))
        {
            $typography_styles[] = sprintf('letter-spacing: %s;', $attributes['style']['typography']['letterSpacing']);
        }

        if(! empty($attributes['style']['typography']['fontWeight']))
        {
            $typography_styles[] = sprintf('font-weight: %s;', $attributes['style']['typography']['fontWeight']);
        }

        if(! empty($attributes['style']['typography']['fontStyle']))
        {
            $typography_styles[] = sprintf('font-style: %s;', $attributes['style']['typography']['fontStyle']);
        }

        if(! empty($attributes['style']['typography']['lineHeight']))
        {
            $typography_styles[] = sprintf('line-height: %s;', $attributes['style']['typography']['lineHeight']);
        }

        if(! empty($attributes['style']['typography']['textTransform']))
        {
            $typography_styles[] = sprintf('text-transform: %s;', $attributes['style']['typography']['textTransform']);
        }

        return implode('', $typography_styles);
    }

    function get_border_color_classes_for_block_core_search($attributes)
    {
        $border_color_classes = [];
        $has_custom_border_color = ! empty($attributes['style']['border']['color']);
        $has_named_border_color = ! empty($attributes['borderColor']);

        if($has_custom_border_color || $has_named_border_color)
        {
            $border_color_classes[] = 'has-border-color';
        }

        if($has_named_border_color)
        {
            $border_color_classes[] = sprintf('has-%s-border-color', esc_attr($attributes['borderColor']));
        }

        return implode(' ', $border_color_classes);
    }

    function get_color_classes_for_block_core_search($attributes)
    {
        $classnames = [];

        // Text color.
        $has_named_text_color = ! empty($attributes['textColor']);
        $has_custom_text_color = ! empty($attributes['style']['color']['text']);
        if($has_named_text_color)
        {
            $classnames[] = sprintf('has-text-color has-%s-color', $attributes['textColor']);
        }
        elseif($has_custom_text_color)
        {
            // If a custom 'textColor' was selected instead of a preset, still add the generic `has-text-color` class.
            $classnames[] = 'has-text-color';
        }

        // Background color.
        $has_named_background_color = ! empty($attributes['backgroundColor']);
        $has_custom_background_color = ! empty($attributes['style']['color']['background']);
        $has_named_gradient = ! empty($attributes['gradient']);
        $has_custom_gradient = ! empty($attributes['style']['color']['gradient']);
        if($has_named_background_color || $has_custom_background_color || $has_named_gradient || $has_custom_gradient)
        {
            $classnames[] = 'has-background';
        }
        if($has_named_background_color)
        {
            $classnames[] = sprintf('has-%s-background-color', $attributes['backgroundColor']);
        }
        if($has_named_gradient)
        {
            $classnames[] = sprintf('has-%s-gradient-background', $attributes['gradient']);
        }

        return implode(' ', $classnames);
    }
