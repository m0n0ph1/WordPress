<?php

    function wp_style_engine_get_styles($block_styles, $options = [])
    {
        $options = wp_parse_args($options, [
            'selector' => null,
            'context' => null,
            'convert_vars_to_classnames' => false,
        ]);

        $parsed_styles = WP_Style_Engine::parse_block_styles($block_styles, $options);

        // Output.
        $styles_output = [];

        if(! empty($parsed_styles['declarations']))
        {
            $styles_output['css'] = WP_Style_Engine::compile_css($parsed_styles['declarations'], $options['selector']);
            $styles_output['declarations'] = $parsed_styles['declarations'];
            if(! empty($options['context']))
            {
                WP_Style_Engine::store_css_rule($options['context'], $options['selector'], $parsed_styles['declarations']);
            }
        }

        if(! empty($parsed_styles['classnames']))
        {
            $styles_output['classnames'] = implode(' ', array_unique($parsed_styles['classnames']));
        }

        return array_filter($styles_output);
    }

    function wp_style_engine_get_stylesheet_from_css_rules($css_rules, $options = [])
    {
        if(empty($css_rules))
        {
            return '';
        }

        $options = wp_parse_args($options, [
            'context' => null,
        ]);

        $css_rule_objects = [];
        foreach($css_rules as $css_rule)
        {
            if(empty($css_rule['selector']) || empty($css_rule['declarations']) || ! is_array($css_rule['declarations']))
            {
                continue;
            }

            if(! empty($options['context']))
            {
                WP_Style_Engine::store_css_rule($options['context'], $css_rule['selector'], $css_rule['declarations']);
            }

            $css_rule_objects[] = new WP_Style_Engine_CSS_Rule($css_rule['selector'], $css_rule['declarations']);
        }

        if(empty($css_rule_objects))
        {
            return '';
        }

        return WP_Style_Engine::compile_stylesheet_from_css_rules($css_rule_objects, $options);
    }

    function wp_style_engine_get_stylesheet_from_context($context, $options = [])
    {
        return WP_Style_Engine::compile_stylesheet_from_css_rules(WP_Style_Engine::get_store($context)->get_all_rules(), $options);
    }
