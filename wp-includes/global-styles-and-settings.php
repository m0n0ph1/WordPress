<?php

    function wp_get_global_settings($path = [], $context = [])
    {
        if(! empty($context['block_name']))
        {
            $new_path = ['blocks', $context['block_name']];
            foreach($path as $subpath)
            {
                $new_path[] = $subpath;
            }
            $path = $new_path;
        }

        /*
         * This is the default value when no origin is provided or when it is 'all'.
         *
         * The $origin is used as part of the cache key. Changes here need to account
         * for clearing the cache appropriately.
         */
        $origin = 'custom';
        if(! wp_theme_has_theme_json() || (isset($context['origin']) && 'base' === $context['origin']))
        {
            $origin = 'theme';
        }

        /*
         * By using the 'theme_json' group, this data is marked to be non-persistent across requests.
         * See `wp_cache_add_non_persistent_groups` in src/wp-includes/load.php and other places.
         *
         * The rationale for this is to make sure derived data from theme.json
         * is always fresh from the potential modifications done via hooks
         * that can use dynamic data (modify the stylesheet depending on some option,
         * settings depending on user permissions, etc.).
         * See some of the existing hooks to modify theme.json behaviour:
         * https://make.wordpress.org/core/2022/10/10/filters-for-theme-json-data/
         *
         * A different alternative considered was to invalidate the cache upon certain
         * events such as options add/update/delete, user meta, etc.
         * It was judged not enough, hence this approach.
         * See https://github.com/WordPress/gutenberg/pull/45372
         */
        $cache_group = 'theme_json';
        $cache_key = 'wp_get_global_settings_'.$origin;

        /*
         * Ignore cache when the development mode is set to 'theme', so it doesn't interfere with the theme
         * developer's workflow.
         */
        $can_use_cached = ! wp_is_development_mode('theme');

        $settings = false;
        if($can_use_cached)
        {
            $settings = wp_cache_get($cache_key, $cache_group);
        }

        if(false === $settings)
        {
            $settings = WP_Theme_JSON_Resolver::get_merged_data($origin)->get_settings();
            if($can_use_cached)
            {
                wp_cache_set($cache_key, $settings, $cache_group);
            }
        }

        return _wp_array_get($settings, $path, $settings);
    }

    function wp_get_global_styles($path = [], $context = [])
    {
        if(! empty($context['block_name']))
        {
            $path = array_merge(['blocks', $context['block_name']], $path);
        }

        $origin = 'custom';
        if(isset($context['origin']) && 'base' === $context['origin'])
        {
            $origin = 'theme';
        }

        $resolve_variables = isset($context['transforms']) && is_array($context['transforms']) && in_array('resolve-variables', $context['transforms'], true);

        $merged_data = WP_Theme_JSON_Resolver::get_merged_data($origin);
        if($resolve_variables)
        {
            $merged_data = WP_Theme_JSON::resolve_variables($merged_data);
        }
        $styles = $merged_data->get_raw_data()['styles'];

        return _wp_array_get($styles, $path, $styles);
    }

    function wp_get_global_stylesheet($types = [])
    {
        /*
         * Ignore cache when the development mode is set to 'theme', so it doesn't interfere with the theme
         * developer's workflow.
         */
        $can_use_cached = empty($types) && ! wp_is_development_mode('theme');

        /*
         * By using the 'theme_json' group, this data is marked to be non-persistent across requests.
         * @see `wp_cache_add_non_persistent_groups()`.
         *
         * The rationale for this is to make sure derived data from theme.json
         * is always fresh from the potential modifications done via hooks
         * that can use dynamic data (modify the stylesheet depending on some option,
         * settings depending on user permissions, etc.).
         * See some of the existing hooks to modify theme.json behavior:
         * @see https://make.wordpress.org/core/2022/10/10/filters-for-theme-json-data/
         *
         * A different alternative considered was to invalidate the cache upon certain
         * events such as options add/update/delete, user meta, etc.
         * It was judged not enough, hence this approach.
         * @see https://github.com/WordPress/gutenberg/pull/45372
         */
        $cache_group = 'theme_json';
        $cache_key = 'wp_get_global_stylesheet';
        if($can_use_cached)
        {
            $cached = wp_cache_get($cache_key, $cache_group);
            if($cached)
            {
                return $cached;
            }
        }

        $tree = WP_Theme_JSON_Resolver::get_merged_data();

        $supports_theme_json = wp_theme_has_theme_json();
        if(empty($types) && ! $supports_theme_json)
        {
            $types = ['variables', 'presets', 'base-layout-styles'];
        }
        elseif(empty($types))
        {
            $types = ['variables', 'styles', 'presets'];
        }

        /*
         * If variables are part of the stylesheet, then add them.
         * This is so themes without a theme.json still work as before 5.9:
         * they can override the default presets.
         * See https://core.trac.wordpress.org/ticket/54782
         */
        $styles_variables = '';
        if(in_array('variables', $types, true))
        {
            /*
             * Only use the default, theme, and custom origins. Why?
             * Because styles for `blocks` origin are added at a later phase
             * (i.e. in the render cycle). Here, only the ones in use are rendered.
             * @see wp_add_global_styles_for_blocks
             */
            $origins = ['default', 'theme', 'custom'];
            $styles_variables = $tree->get_stylesheet(['variables'], $origins);
            $types = array_diff($types, ['variables']);
        }

        /*
         * For the remaining types (presets, styles), we do consider origins:
         *
         * - themes without theme.json: only the classes for the presets defined by core
         * - themes with theme.json: the presets and styles classes, both from core and the theme
         */
        $styles_rest = '';
        if(! empty($types))
        {
            /*
             * Only use the default, theme, and custom origins. Why?
             * Because styles for `blocks` origin are added at a later phase
             * (i.e. in the render cycle). Here, only the ones in use are rendered.
             * @see wp_add_global_styles_for_blocks
             */
            $origins = ['default', 'theme', 'custom'];
            if(! $supports_theme_json)
            {
                $origins = ['default'];
            }
            $styles_rest = $tree->get_stylesheet($types, $origins);
        }

        $stylesheet = $styles_variables.$styles_rest;
        if($can_use_cached)
        {
            wp_cache_set($cache_key, $stylesheet, $cache_group);
        }

        return $stylesheet;
    }

    function wp_get_global_styles_custom_css()
    {
        if(! wp_theme_has_theme_json())
        {
            return '';
        }
        /*
         * Ignore cache when the development mode is set to 'theme', so it doesn't interfere with the theme
         * developer's workflow.
         */
        $can_use_cached = ! wp_is_development_mode('theme');

        /*
         * By using the 'theme_json' group, this data is marked to be non-persistent across requests.
         * @see `wp_cache_add_non_persistent_groups()`.
         *
         * The rationale for this is to make sure derived data from theme.json
         * is always fresh from the potential modifications done via hooks
         * that can use dynamic data (modify the stylesheet depending on some option,
         * settings depending on user permissions, etc.).
         * See some of the existing hooks to modify theme.json behavior:
         * @see https://make.wordpress.org/core/2022/10/10/filters-for-theme-json-data/
         *
         * A different alternative considered was to invalidate the cache upon certain
         * events such as options add/update/delete, user meta, etc.
         * It was judged not enough, hence this approach.
         * @see https://github.com/WordPress/gutenberg/pull/45372
         */
        $cache_key = 'wp_get_global_styles_custom_css';
        $cache_group = 'theme_json';
        if($can_use_cached)
        {
            $cached = wp_cache_get($cache_key, $cache_group);
            if($cached)
            {
                return $cached;
            }
        }

        $tree = WP_Theme_JSON_Resolver::get_merged_data();
        $stylesheet = $tree->get_custom_css();

        if($can_use_cached)
        {
            wp_cache_set($cache_key, $stylesheet, $cache_group);
        }

        return $stylesheet;
    }

    function wp_add_global_styles_for_blocks()
    {
        $tree = WP_Theme_JSON_Resolver::get_merged_data();
        $block_nodes = $tree->get_styles_block_nodes();
        foreach($block_nodes as $metadata)
        {
            $block_css = $tree->get_styles_for_block($metadata);

            if(! wp_should_load_separate_core_block_assets())
            {
                wp_add_inline_style('global-styles', $block_css);
                continue;
            }

            $stylesheet_handle = 'global-styles';
            if(isset($metadata['name']))
            {
                /*
                 * These block styles are added on block_render.
                 * This hooks inline CSS to them so that they are loaded conditionally
                 * based on whether or not the block is used on the page.
                 */
                if(str_starts_with($metadata['name'], 'core/'))
                {
                    $block_name = str_replace('core/', '', $metadata['name']);
                    $stylesheet_handle = 'wp-block-'.$block_name;
                }
                wp_add_inline_style($stylesheet_handle, $block_css);
            }

            // The likes of block element styles from theme.json do not have  $metadata['name'] set.
            if(! isset($metadata['name']) && ! empty($metadata['path']))
            {
                $block_name = wp_get_block_name_from_theme_json_path($metadata['path']);
                if($block_name)
                {
                    if(str_starts_with($block_name, 'core/'))
                    {
                        $block_name = str_replace('core/', '', $block_name);
                        $stylesheet_handle = 'wp-block-'.$block_name;
                    }
                    wp_add_inline_style($stylesheet_handle, $block_css);
                }
            }
        }
    }

    function wp_get_block_name_from_theme_json_path($path)
    {
        // Block name is expected to be the third item after 'styles' and 'blocks'.
        if(count($path) >= 3 && 'styles' === $path[0] && 'blocks' === $path[1] && str_contains($path[2], '/'))
        {
            return $path[2];
        }

        /*
         * As fallback and for backward compatibility, allow any core block to be
         * at any position.
         */
        $result = array_values(
            array_filter($path, static function($item)
            {
                if(str_contains($item, 'core/'))
                {
                    return true;
                }

                return false;
            })
        );
        if(isset($result[0]))
        {
            return $result[0];
        }

        return '';
    }

    function wp_theme_has_theme_json()
    {
        static $theme_has_support = [];

        $stylesheet = get_stylesheet();

        if(
            isset($theme_has_support[$stylesheet]) && /*
             * Ignore static cache when the development mode is set to 'theme', to avoid interfering with
             * the theme developer's workflow.
             */ ! wp_is_development_mode('theme')
        )
        {
            return $theme_has_support[$stylesheet];
        }

        $stylesheet_directory = get_stylesheet_directory();
        $template_directory = get_template_directory();

        // This is the same as get_theme_file_path(), which isn't available in load-styles.php context
        if($stylesheet_directory !== $template_directory && file_exists($stylesheet_directory.'/theme.json'))
        {
            $path = $stylesheet_directory.'/theme.json';
        }
        else
        {
            $path = $template_directory.'/theme.json';
        }

        $path = apply_filters('theme_file_path', $path, 'theme.json');

        $theme_has_support[$stylesheet] = file_exists($path);

        return $theme_has_support[$stylesheet];
    }

    function wp_clean_theme_json_cache()
    {
        wp_cache_delete('wp_get_global_stylesheet', 'theme_json');
        wp_cache_delete('wp_get_global_styles_svg_filters', 'theme_json');
        wp_cache_delete('wp_get_global_settings_custom', 'theme_json');
        wp_cache_delete('wp_get_global_settings_theme', 'theme_json');
        wp_cache_delete('wp_get_global_styles_custom_css', 'theme_json');
        wp_cache_delete('wp_get_theme_data_template_parts', 'theme_json');
        WP_Theme_JSON_Resolver::clean_cached_data();
    }

    function wp_get_theme_directory_pattern_slugs()
    {
        return WP_Theme_JSON_Resolver::get_theme_data([], ['with_supports' => false])->get_patterns();
    }

    function wp_get_theme_data_custom_templates()
    {
        return WP_Theme_JSON_Resolver::get_theme_data([], ['with_supports' => false])->get_custom_templates();
    }

    function wp_get_theme_data_template_parts()
    {
        $cache_group = 'theme_json';
        $cache_key = 'wp_get_theme_data_template_parts';
        $can_use_cached = ! wp_is_development_mode('theme');

        $metadata = false;
        if($can_use_cached)
        {
            $metadata = wp_cache_get($cache_key, $cache_group);
            if(false !== $metadata)
            {
                return $metadata;
            }
        }

        if(false === $metadata)
        {
            $metadata = WP_Theme_JSON_Resolver::get_theme_data([], ['with_supports' => false])->get_template_parts();
            if($can_use_cached)
            {
                wp_cache_set($cache_key, $metadata, $cache_group);
            }
        }

        return $metadata;
    }

    function wp_get_block_css_selector($block_type, $target = 'root', $fallback = false)
    {
        if(empty($target))
        {
            return null;
        }

        $has_selectors = ! empty($block_type->selectors);

        // Root Selector.

        // Calculated before returning as it can be used as fallback for
        // feature selectors later on.
        $root_selector = null;

        if($has_selectors && isset($block_type->selectors['root']))
        {
            // Use the selectors API if available.
            $root_selector = $block_type->selectors['root'];
        }
        elseif(isset($block_type->supports['__experimentalSelector']) && is_string($block_type->supports['__experimentalSelector']))
        {
            // Use the old experimental selector supports property if set.
            $root_selector = $block_type->supports['__experimentalSelector'];
        }
        else
        {
            // If no root selector found, generate default block class selector.
            $block_name = str_replace('/', '-', str_replace('core/', '', $block_type->name));
            $root_selector = ".wp-block-{$block_name}";
        }

        // Return selector if it's the root target we are looking for.
        if('root' === $target)
        {
            return $root_selector;
        }

        // If target is not `root` we have a feature or subfeature as the target.
        // If the target is a string convert to an array.
        if(is_string($target))
        {
            $target = explode('.', $target);
        }

        // Feature Selectors ( May fallback to root selector ).
        if(1 === count($target))
        {
            $fallback_selector = $fallback ? $root_selector : null;

            // Prefer the selectors API if available.
            if($has_selectors)
            {
                // Look for selector under `feature.root`.
                $path = [current($target), 'root'];
                $feature_selector = _wp_array_get($block_type->selectors, $path, null);

                if($feature_selector)
                {
                    return $feature_selector;
                }

                // Check if feature selector is set via shorthand.
                $feature_selector = _wp_array_get($block_type->selectors, $target, null);

                return is_string($feature_selector) ? $feature_selector : $fallback_selector;
            }

            // Try getting old experimental supports selector value.
            $path = [current($target), '__experimentalSelector'];
            $feature_selector = _wp_array_get($block_type->supports, $path, null);

            // Nothing to work with, provide fallback or null.
            if(null === $feature_selector)
            {
                return $fallback_selector;
            }

            // Scope the feature selector by the block's root selector.
            return WP_Theme_JSON::scope_selector($root_selector, $feature_selector);
        }

        // Subfeature selector
        // This may fallback either to parent feature or root selector.
        $subfeature_selector = null;

        // Use selectors API if available.
        if($has_selectors)
        {
            $subfeature_selector = _wp_array_get($block_type->selectors, $target, null);
        }

        // Only return if we have a subfeature selector.
        if($subfeature_selector)
        {
            return $subfeature_selector;
        }

        // To this point we don't have a subfeature selector. If a fallback
        // has been requested, remove subfeature from target path and return
        // results of a call for the parent feature's selector.
        if($fallback)
        {
            return wp_get_block_css_selector($block_type, $target[0], $fallback);
        }

        return null;
    }
