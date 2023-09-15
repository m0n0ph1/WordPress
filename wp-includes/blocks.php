<?php

    function remove_block_asset_path_prefix($asset_handle_or_path)
    {
        $path_prefix = 'file:';
        if(! str_starts_with($asset_handle_or_path, $path_prefix))
        {
            return $asset_handle_or_path;
        }
        $path = substr($asset_handle_or_path, strlen($path_prefix));
        if(str_starts_with($path, './'))
        {
            $path = substr($path, 2);
        }

        return $path;
    }

    function generate_block_asset_handle($block_name, $field_name, $index = 0)
    {
        if(str_starts_with($block_name, 'core/'))
        {
            $asset_handle = str_replace('core/', 'wp-block-', $block_name);
            if(str_starts_with($field_name, 'editor'))
            {
                $asset_handle .= '-editor';
            }
            if(str_starts_with($field_name, 'view'))
            {
                $asset_handle .= '-view';
            }
            if($index > 0)
            {
                $asset_handle .= '-'.($index + 1);
            }

            return $asset_handle;
        }

        $field_mappings = [
            'editorScript' => 'editor-script',
            'script' => 'script',
            'viewScript' => 'view-script',
            'editorStyle' => 'editor-style',
            'style' => 'style',
        ];
        $asset_handle = str_replace('/', '-', $block_name).'-'.$field_mappings[$field_name];
        if($index > 0)
        {
            $asset_handle .= '-'.($index + 1);
        }

        return $asset_handle;
    }

    function register_block_script_handle($metadata, $field_name, $index = 0)
    {
        if(empty($metadata[$field_name]))
        {
            return false;
        }

        $script_handle = $metadata[$field_name];
        if(is_array($script_handle))
        {
            if(empty($script_handle[$index]))
            {
                return false;
            }
            $script_handle = $script_handle[$index];
        }

        $script_path = remove_block_asset_path_prefix($script_handle);
        if($script_handle === $script_path)
        {
            return $script_handle;
        }

        $script_asset_raw_path = dirname($metadata['file']).'/'.substr_replace($script_path, '.asset.php', -strlen('.js'));
        $script_handle = generate_block_asset_handle($metadata['name'], $field_name, $index);
        $script_asset_path = wp_normalize_path(realpath($script_asset_raw_path));

        if(empty($script_asset_path))
        {
            _doing_it_wrong(__FUNCTION__, sprintf(/* translators: 1: Asset file location, 2: Field name, 3: Block name.  */ __('The asset file (%1$s) for the "%2$s" defined in "%3$s" block definition is missing.'), $script_asset_raw_path, $field_name, $metadata['name']), '5.5.0');

            return false;
        }

        // Path needs to be normalized to work in Windows env.
        static $wpinc_path_norm = '';
        if(! $wpinc_path_norm)
        {
            $wpinc_path_norm = wp_normalize_path(realpath(ABSPATH.WPINC));
        }

        // Cache $template_path_norm and $stylesheet_path_norm to avoid unnecessary additional calls.
        static $template_path_norm = '';
        static $stylesheet_path_norm = '';
        if(! $template_path_norm || ! $stylesheet_path_norm)
        {
            $template_path_norm = wp_normalize_path(get_template_directory());
            $stylesheet_path_norm = wp_normalize_path(get_stylesheet_directory());
        }

        $script_path_norm = wp_normalize_path(realpath(dirname($metadata['file']).'/'.$script_path));

        $is_core_block = isset($metadata['file']) && str_starts_with($metadata['file'], $wpinc_path_norm);

        /*
         * Determine if the block script was registered in a theme, by checking if the script path starts with either
         * the parent (template) or child (stylesheet) directory path.
         */
        $is_parent_theme_block = str_starts_with($script_path_norm, trailingslashit($template_path_norm));
        $is_child_theme_block = str_starts_with($script_path_norm, trailingslashit($stylesheet_path_norm));
        $is_theme_block = ($is_parent_theme_block || $is_child_theme_block);

        $script_uri = '';
        if($is_core_block)
        {
            $script_uri = includes_url(str_replace($wpinc_path_norm, '', $script_path_norm));
        }
        elseif($is_theme_block)
        {
            // Get the script path deterministically based on whether or not it was registered in a parent or child theme.
            $script_uri = $is_parent_theme_block ? get_theme_file_uri(str_replace($template_path_norm, '', $script_path_norm)) : get_theme_file_uri(str_replace($stylesheet_path_norm, '', $script_path_norm));
        }
        else
        {
            // Fallback to plugins_url().
            $script_uri = plugins_url($script_path, $metadata['file']);
        }

        $script_args = [];
        if('viewScript' === $field_name)
        {
            $script_args['strategy'] = 'defer';
        }

        $script_asset = require $script_asset_path;
        $script_dependencies = isset($script_asset['dependencies']) ? $script_asset['dependencies'] : [];
        $result = wp_register_script($script_handle, $script_uri, $script_dependencies, isset($script_asset['version']) ? $script_asset['version'] : false, $script_args);
        if(! $result)
        {
            return false;
        }

        if(! empty($metadata['textdomain']) && in_array('wp-i18n', $script_dependencies, true))
        {
            wp_set_script_translations($script_handle, $metadata['textdomain']);
        }

        return $script_handle;
    }

    function register_block_style_handle($metadata, $field_name, $index = 0)
    {
        if(empty($metadata[$field_name]))
        {
            return false;
        }

        $style_handle = $metadata[$field_name];
        if(is_array($style_handle))
        {
            if(empty($style_handle[$index]))
            {
                return false;
            }
            $style_handle = $style_handle[$index];
        }

        $style_handle_name = generate_block_asset_handle($metadata['name'], $field_name, $index);
        // If the style handle is already registered, skip re-registering.
        if(wp_style_is($style_handle_name, 'registered'))
        {
            return $style_handle_name;
        }

        static $wpinc_path_norm = '';
        if(! $wpinc_path_norm)
        {
            $wpinc_path_norm = wp_normalize_path(realpath(ABSPATH.WPINC));
        }

        $is_core_block = isset($metadata['file']) && str_starts_with($metadata['file'], $wpinc_path_norm);
        // Skip registering individual styles for each core block when a bundled version provided.
        if($is_core_block && ! wp_should_load_separate_core_block_assets())
        {
            return false;
        }

        $style_path = remove_block_asset_path_prefix($style_handle);
        $is_style_handle = $style_handle === $style_path;
        // Allow only passing style handles for core blocks.
        if($is_core_block && ! $is_style_handle)
        {
            return false;
        }
        // Return the style handle unless it's the first item for every core block that requires special treatment.
        if($is_style_handle && ! ($is_core_block && 0 === $index))
        {
            return $style_handle;
        }

        // Check whether styles should have a ".min" suffix or not.
        $suffix = SCRIPT_DEBUG ? '' : '.min';
        if($is_core_block)
        {
            $style_path = ('editorStyle' === $field_name) ? "editor{$suffix}.css" : "style{$suffix}.css";
        }

        $style_path_norm = wp_normalize_path(realpath(dirname($metadata['file']).'/'.$style_path));
        $has_style_file = '' !== $style_path_norm;

        if($has_style_file)
        {
            $style_uri = plugins_url($style_path, $metadata['file']);

            // Cache $template_path_norm and $stylesheet_path_norm to avoid unnecessary additional calls.
            static $template_path_norm = '';
            static $stylesheet_path_norm = '';
            if(! $template_path_norm || ! $stylesheet_path_norm)
            {
                $template_path_norm = wp_normalize_path(get_template_directory());
                $stylesheet_path_norm = wp_normalize_path(get_stylesheet_directory());
            }

            // Determine if the block style was registered in a theme, by checking if the script path starts with either
            // the parent (template) or child (stylesheet) directory path.
            $is_parent_theme_block = str_starts_with($style_path_norm, trailingslashit($template_path_norm));
            $is_child_theme_block = str_starts_with($style_path_norm, trailingslashit($stylesheet_path_norm));
            $is_theme_block = ($is_parent_theme_block || $is_child_theme_block);

            if($is_core_block)
            {
                // All possible $style_path variants for core blocks are hard-coded above.
                $style_uri = includes_url('blocks/'.str_replace('core/', '', $metadata['name']).'/'.$style_path);
            }
            elseif($is_theme_block)
            {
                // Get the script path deterministically based on whether or not it was registered in a parent or child theme.
                $style_uri = $is_parent_theme_block ? get_theme_file_uri(str_replace($template_path_norm, '', $style_path_norm)) : get_theme_file_uri(str_replace($stylesheet_path_norm, '', $style_path_norm));
            }
        }
        else
        {
            $style_uri = false;
        }

        $version = ! $is_core_block && isset($metadata['version']) ? $metadata['version'] : false;
        $result = wp_register_style($style_handle_name, $style_uri, [], $version);
        if(! $result)
        {
            return false;
        }

        if($has_style_file)
        {
            wp_style_add_data($style_handle_name, 'path', $style_path_norm);

            if($is_core_block)
            {
                $rtl_file = str_replace("{$suffix}.css", "-rtl{$suffix}.css", $style_path_norm);
            }
            else
            {
                $rtl_file = str_replace('.css', '-rtl.css', $style_path_norm);
            }

            if(is_rtl() && file_exists($rtl_file))
            {
                wp_style_add_data($style_handle_name, 'rtl', 'replace');
                wp_style_add_data($style_handle_name, 'suffix', $suffix);
                wp_style_add_data($style_handle_name, 'path', $rtl_file);
            }
        }

        return $style_handle_name;
    }

    function get_block_metadata_i18n_schema()
    {
        static $i18n_block_schema;

        if(! isset($i18n_block_schema))
        {
            $i18n_block_schema = wp_json_file_decode(__DIR__.'/block-i18n.json');
        }

        return $i18n_block_schema;
    }

    function register_block_type_from_metadata($file_or_folder, $args = [])
    {
        /*
         * Get an array of metadata from a PHP file.
         * This improves performance for core blocks as it's only necessary to read a single PHP file
         * instead of reading a JSON file per-block, and then decoding from JSON to PHP.
         * Using a static variable ensures that the metadata is only read once per request.
         */
        static $core_blocks_meta;
        if(! $core_blocks_meta)
        {
            $core_blocks_meta = require ABSPATH.WPINC.'/blocks/blocks-json.php';
        }

        $metadata_file = (! str_ends_with($file_or_folder, 'block.json')) ? trailingslashit($file_or_folder).'block.json' : $file_or_folder;

        $is_core_block = str_starts_with($file_or_folder, ABSPATH.WPINC);

        if(! $is_core_block && ! file_exists($metadata_file))
        {
            return false;
        }

        // Try to get metadata from the static cache for core blocks.
        $metadata = false;
        if($is_core_block)
        {
            $core_block_name = str_replace(ABSPATH.WPINC.'/blocks/', '', $file_or_folder);
            if(! empty($core_blocks_meta[$core_block_name]))
            {
                $metadata = $core_blocks_meta[$core_block_name];
            }
        }

        // If metadata is not found in the static cache, read it from the file.
        if(! $metadata)
        {
            $metadata = wp_json_file_decode($metadata_file, ['associative' => true]);
        }

        if(! is_array($metadata) || empty($metadata['name']))
        {
            return false;
        }
        $metadata['file'] = wp_normalize_path(realpath($metadata_file));

        $metadata = apply_filters('block_type_metadata', $metadata);

        // Add `style` and `editor_style` for core blocks if missing.
        if(! empty($metadata['name']) && str_starts_with($metadata['name'], 'core/'))
        {
            $block_name = str_replace('core/', '', $metadata['name']);

            if(! isset($metadata['style']))
            {
                $metadata['style'] = "wp-block-$block_name";
            }
            if(current_theme_supports('wp-block-styles') && wp_should_load_separate_core_block_assets())
            {
                $metadata['style'] = (array) $metadata['style'];
                $metadata['style'][] = "wp-block-{$block_name}-theme";
            }
            if(! isset($metadata['editorStyle']))
            {
                $metadata['editorStyle'] = "wp-block-{$block_name}-editor";
            }
        }

        $settings = [];
        $property_mappings = [
            'apiVersion' => 'api_version',
            'title' => 'title',
            'category' => 'category',
            'parent' => 'parent',
            'ancestor' => 'ancestor',
            'icon' => 'icon',
            'description' => 'description',
            'keywords' => 'keywords',
            'attributes' => 'attributes',
            'providesContext' => 'provides_context',
            'usesContext' => 'uses_context',
            'selectors' => 'selectors',
            'supports' => 'supports',
            'styles' => 'styles',
            'variations' => 'variations',
            'example' => 'example',
        ];
        $textdomain = ! empty($metadata['textdomain']) ? $metadata['textdomain'] : null;
        $i18n_schema = get_block_metadata_i18n_schema();

        foreach($property_mappings as $key => $mapped_key)
        {
            if(isset($metadata[$key]))
            {
                $settings[$mapped_key] = $metadata[$key];
                if($textdomain && isset($i18n_schema->$key))
                {
                    $settings[$mapped_key] = translate_settings_using_i18n_schema($i18n_schema->$key, $settings[$key], $textdomain);
                }
            }
        }

        $script_fields = [
            'editorScript' => 'editor_script_handles',
            'script' => 'script_handles',
            'viewScript' => 'view_script_handles',
        ];
        foreach($script_fields as $metadata_field_name => $settings_field_name)
        {
            if(! empty($metadata[$metadata_field_name]))
            {
                $scripts = $metadata[$metadata_field_name];
                $processed_scripts = [];
                if(is_array($scripts))
                {
                    for($index = 0; $index < count($scripts); $index++)
                    {
                        $result = register_block_script_handle($metadata, $metadata_field_name, $index);
                        if($result)
                        {
                            $processed_scripts[] = $result;
                        }
                    }
                }
                else
                {
                    $result = register_block_script_handle($metadata, $metadata_field_name);
                    if($result)
                    {
                        $processed_scripts[] = $result;
                    }
                }
                $settings[$settings_field_name] = $processed_scripts;
            }
        }

        $style_fields = [
            'editorStyle' => 'editor_style_handles',
            'style' => 'style_handles',
        ];
        foreach($style_fields as $metadata_field_name => $settings_field_name)
        {
            if(! empty($metadata[$metadata_field_name]))
            {
                $styles = $metadata[$metadata_field_name];
                $processed_styles = [];
                if(is_array($styles))
                {
                    for($index = 0; $index < count($styles); $index++)
                    {
                        $result = register_block_style_handle($metadata, $metadata_field_name, $index);
                        if($result)
                        {
                            $processed_styles[] = $result;
                        }
                    }
                }
                else
                {
                    $result = register_block_style_handle($metadata, $metadata_field_name);
                    if($result)
                    {
                        $processed_styles[] = $result;
                    }
                }
                $settings[$settings_field_name] = $processed_styles;
            }
        }

        if(! empty($metadata['blockHooks']))
        {
            $position_mappings = [
                'before' => 'before',
                'after' => 'after',
                'firstChild' => 'first_child',
                'lastChild' => 'last_child',
            ];

            $settings['block_hooks'] = [];
            foreach($metadata['blockHooks'] as $anchor_block_name => $position)
            {
                // Avoid infinite recursion (hooking to itself).
                if($metadata['name'] === $anchor_block_name)
                {
                    _doing_it_wrong(__METHOD__, __('Cannot hook block to itself.'), '6.4.0');
                    continue;
                }

                if(! isset($position_mappings[$position]))
                {
                    continue;
                }

                $settings['block_hooks'][$anchor_block_name] = $position_mappings[$position];
            }
        }

        if(! empty($metadata['render']))
        {
            $template_path = wp_normalize_path(realpath(dirname($metadata['file']).'/'.remove_block_asset_path_prefix($metadata['render'])));
            if($template_path)
            {
                $settings['render_callback'] = static function($attributes, $content, $block) use ($template_path)
                { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
                    ob_start();
                    require $template_path;

                    return ob_get_clean();
                };
            }
        }

        $settings = apply_filters('block_type_metadata_settings', array_merge($settings, $args), $metadata);

        return WP_Block_Type_Registry::get_instance()->register($metadata['name'], $settings);
    }

    function register_block_type($block_type, $args = [])
    {
        if(is_string($block_type) && file_exists($block_type))
        {
            return register_block_type_from_metadata($block_type, $args);
        }

        return WP_Block_Type_Registry::get_instance()->register($block_type, $args);
    }

    function unregister_block_type($name)
    {
        return WP_Block_Type_Registry::get_instance()->unregister($name);
    }

    function has_blocks($post = null)
    {
        if(! is_string($post))
        {
            $wp_post = get_post($post);

            if(! $wp_post instanceof WP_Post)
            {
                return false;
            }

            $post = $wp_post->post_content;
        }

        return str_contains((string) $post, '<!-- wp:');
    }

    function has_block($block_name, $post = null)
    {
        if(! has_blocks($post))
        {
            return false;
        }

        if(! is_string($post))
        {
            $wp_post = get_post($post);
            if($wp_post instanceof WP_Post)
            {
                $post = $wp_post->post_content;
            }
        }

        /*
         * Normalize block name to include namespace, if provided as non-namespaced.
         * This matches behavior for WordPress 5.0.0 - 5.3.0 in matching blocks by
         * their serialized names.
         */
        if(! str_contains($block_name, '/'))
        {
            $block_name = 'core/'.$block_name;
        }

        // Test for existence of block by its fully qualified name.
        $has_block = str_contains($post, '<!-- wp:'.$block_name.' ');

        if(! $has_block)
        {
            /*
             * If the given block name would serialize to a different name, test for
             * existence by the serialized form.
             */
            $serialized_block_name = strip_core_block_namespace($block_name);
            if($serialized_block_name !== $block_name)
            {
                $has_block = str_contains($post, '<!-- wp:'.$serialized_block_name.' ');
            }
        }

        return $has_block;
    }

    function get_dynamic_block_names()
    {
        $dynamic_block_names = [];

        $block_types = WP_Block_Type_Registry::get_instance()->get_all_registered();
        foreach($block_types as $block_type)
        {
            if($block_type->is_dynamic())
            {
                $dynamic_block_names[] = $block_type->name;
            }
        }

        return $dynamic_block_names;
    }

    function serialize_block_attributes($block_attributes)
    {
        $encoded_attributes = wp_json_encode($block_attributes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $encoded_attributes = preg_replace('/--/', '\\u002d\\u002d', $encoded_attributes);
        $encoded_attributes = preg_replace('/</', '\\u003c', $encoded_attributes);
        $encoded_attributes = preg_replace('/>/', '\\u003e', $encoded_attributes);
        $encoded_attributes = preg_replace('/&/', '\\u0026', $encoded_attributes);
        // Regex: /\\"/
        $encoded_attributes = preg_replace('/\\\\"/', '\\u0022', $encoded_attributes);

        return $encoded_attributes;
    }

    function strip_core_block_namespace($block_name = null)
    {
        if(is_string($block_name) && str_starts_with($block_name, 'core/'))
        {
            return substr($block_name, 5);
        }

        return $block_name;
    }

    function get_comment_delimited_block_content($block_name, $block_attributes, $block_content)
    {
        if(is_null($block_name))
        {
            return $block_content;
        }

        $serialized_block_name = strip_core_block_namespace($block_name);
        $serialized_attributes = empty($block_attributes) ? '' : serialize_block_attributes($block_attributes).' ';

        if(empty($block_content))
        {
            return sprintf('<!-- wp:%s %s/-->', $serialized_block_name, $serialized_attributes);
        }

        return sprintf('<!-- wp:%s %s-->%s<!-- /wp:%s -->', $serialized_block_name, $serialized_attributes, $block_content, $serialized_block_name);
    }

    function serialize_block($block, $callback = null)
    {
        if(is_callable($callback))
        {
            $block = call_user_func($callback, $block);
        }

        $block_content = '';

        $index = 0;
        foreach($block['innerContent'] as $chunk)
        {
            $block_content .= is_string($chunk) ? $chunk : serialize_block($block['innerBlocks'][$index++], $callback);
        }

        if(! is_array($block['attrs']))
        {
            $block['attrs'] = [];
        }

        return get_comment_delimited_block_content($block['blockName'], $block['attrs'], $block_content);
    }

    function serialize_blocks($blocks, $callback = null)
    {
        $result = '';
        foreach($blocks as $block)
        {
            $result .= serialize_block($block, $callback);
        };

        return $result;
    }

    function filter_block_content($text, $allowed_html = 'post', $allowed_protocols = [])
    {
        $result = '';

        if(str_contains($text, '<!--') && str_contains($text, '--->'))
        {
            $text = preg_replace_callback('%<!--(.*?)--->%', '_filter_block_content_callback', $text);
        }

        $blocks = parse_blocks($text);
        foreach($blocks as $block)
        {
            $block = filter_block_kses($block, $allowed_html, $allowed_protocols);
            $result .= serialize_block($block);
        }

        return $result;
    }

    function _filter_block_content_callback($matches)
    {
        return '<!--'.rtrim($matches[1], '-').'-->';
    }

    function filter_block_kses($block, $allowed_html, $allowed_protocols = [])
    {
        $block['attrs'] = filter_block_kses_value($block['attrs'], $allowed_html, $allowed_protocols);

        if(is_array($block['innerBlocks']))
        {
            foreach($block['innerBlocks'] as $i => $inner_block)
            {
                $block['innerBlocks'][$i] = filter_block_kses($inner_block, $allowed_html, $allowed_protocols);
            }
        }

        return $block;
    }

    function filter_block_kses_value($value, $allowed_html, $allowed_protocols = [])
    {
        if(is_array($value))
        {
            foreach($value as $key => $inner_value)
            {
                $filtered_key = filter_block_kses_value($key, $allowed_html, $allowed_protocols);
                $filtered_value = filter_block_kses_value($inner_value, $allowed_html, $allowed_protocols);

                if($filtered_key !== $key)
                {
                    unset($value[$key]);
                }

                $value[$filtered_key] = $filtered_value;
            }
        }
        elseif(is_string($value))
        {
            return wp_kses($value, $allowed_html, $allowed_protocols);
        }

        return $value;
    }

    function excerpt_remove_blocks($content)
    {
        if(! has_blocks($content))
        {
            return $content;
        }

        $allowed_inner_blocks = [
            // Classic blocks have their blockName set to null.
            null,
            'core/freeform',
            'core/heading',
            'core/html',
            'core/list',
            'core/media-text',
            'core/paragraph',
            'core/preformatted',
            'core/pullquote',
            'core/quote',
            'core/table',
            'core/verse',
        ];

        $allowed_wrapper_blocks = [
            'core/columns',
            'core/column',
            'core/group',
        ];

        $allowed_wrapper_blocks = apply_filters('excerpt_allowed_wrapper_blocks', $allowed_wrapper_blocks);

        $allowed_blocks = array_merge($allowed_inner_blocks, $allowed_wrapper_blocks);

        $allowed_blocks = apply_filters('excerpt_allowed_blocks', $allowed_blocks);
        $blocks = parse_blocks($content);
        $output = '';

        foreach($blocks as $block)
        {
            if(in_array($block['blockName'], $allowed_blocks, true))
            {
                if(! empty($block['innerBlocks']))
                {
                    if(in_array($block['blockName'], $allowed_wrapper_blocks, true))
                    {
                        $output .= _excerpt_render_inner_blocks($block, $allowed_blocks);
                        continue;
                    }

                    // Skip the block if it has disallowed or nested inner blocks.
                    foreach($block['innerBlocks'] as $inner_block)
                    {
                        if(! in_array($inner_block['blockName'], $allowed_inner_blocks, true) || ! empty($inner_block['innerBlocks']))
                        {
                            continue 2;
                        }
                    }
                }

                $output .= render_block($block);
            }
        }

        return $output;
    }

    function excerpt_remove_footnotes($content)
    {
        if(! str_contains($content, 'data-fn='))
        {
            return $content;
        }

        return preg_replace('_<sup data-fn="[^"]+" class="[^"]+">\s*<a href="[^"]+" id="[^"]+">\d+</a>\s*</sup>_', '', $content);
    }

    function _excerpt_render_inner_blocks($parsed_block, $allowed_blocks)
    {
        $output = '';

        foreach($parsed_block['innerBlocks'] as $inner_block)
        {
            if(! in_array($inner_block['blockName'], $allowed_blocks, true))
            {
                continue;
            }

            if(empty($inner_block['innerBlocks']))
            {
                $output .= render_block($inner_block);
            }
            else
            {
                $output .= _excerpt_render_inner_blocks($inner_block, $allowed_blocks);
            }
        }

        return $output;
    }

    function render_block($parsed_block)
    {
        global $post;
        $parent_block = null;

        $pre_render = apply_filters('pre_render_block', null, $parsed_block, $parent_block);
        if(! is_null($pre_render))
        {
            return $pre_render;
        }

        $source_block = $parsed_block;

        $parsed_block = apply_filters('render_block_data', $parsed_block, $source_block, $parent_block);

        $context = [];

        if($post instanceof WP_Post)
        {
            $context['postId'] = $post->ID;

            /*
             * The `postType` context is largely unnecessary server-side, since the ID
             * is usually sufficient on its own. That being said, since a block's
             * manifest is expected to be shared between the server and the client,
             * it should be included to consistently fulfill the expectation.
             */
            $context['postType'] = $post->post_type;
        }

        $context = apply_filters('render_block_context', $context, $parsed_block, $parent_block);

        $block = new WP_Block($parsed_block, $context);

        return $block->render();
    }

    function parse_blocks($content)
    {
        $parser_class = apply_filters('block_parser_class', 'WP_Block_Parser');

        $parser = new $parser_class();

        return $parser->parse($content);
    }

    function do_blocks($content)
    {
        $blocks = parse_blocks($content);
        $output = '';

        foreach($blocks as $block)
        {
            $output .= render_block($block);
        }

        // If there are blocks in this content, we shouldn't run wpautop() on it later.
        $priority = has_filter('the_content', 'wpautop');
        if(false !== $priority && doing_filter('the_content') && has_blocks($content))
        {
            remove_filter('the_content', 'wpautop', $priority);
            add_filter('the_content', '_restore_wpautop_hook', $priority + 1);
        }

        return $output;
    }

    function _restore_wpautop_hook($content)
    {
        $current_priority = has_filter('the_content', '_restore_wpautop_hook');

        add_filter('the_content', 'wpautop', $current_priority - 1);
        remove_filter('the_content', '_restore_wpautop_hook', $current_priority);

        return $content;
    }

    function block_version($content)
    {
        return has_blocks($content) ? 1 : 0;
    }

    function register_block_style($block_name, $style_properties)
    {
        return WP_Block_Styles_Registry::get_instance()->register($block_name, $style_properties);
    }

    function unregister_block_style($block_name, $block_style_name)
    {
        return WP_Block_Styles_Registry::get_instance()->unregister($block_name, $block_style_name);
    }

    function block_has_support($block_type, $feature, $default_value = false)
    {
        $block_support = $default_value;
        if($block_type && property_exists($block_type, 'supports'))
        {
            if(is_array($feature) && count($feature) === 1)
            {
                $feature = $feature[0];
            }

            if(is_array($feature))
            {
                $block_support = _wp_array_get($block_type->supports, $feature, $default_value);
            }
            elseif(isset($block_type->supports[$feature]))
            {
                $block_support = $block_type->supports[$feature];
            }
        }

        return true === $block_support || is_array($block_support);
    }

    function wp_migrate_old_typography_shape($metadata)
    {
        if(! isset($metadata['supports']))
        {
            return $metadata;
        }

        $typography_keys = [
            '__experimentalFontFamily',
            '__experimentalFontStyle',
            '__experimentalFontWeight',
            '__experimentalLetterSpacing',
            '__experimentalTextDecoration',
            '__experimentalTextTransform',
            'fontSize',
            'lineHeight',
        ];

        foreach($typography_keys as $typography_key)
        {
            $support_for_key = _wp_array_get($metadata['supports'], [$typography_key], null);

            if(null !== $support_for_key)
            {
                _doing_it_wrong('register_block_type_from_metadata()', sprintf(/* translators: 1: Block type, 2: Typography supports key, e.g: fontSize, lineHeight, etc. 3: block.json, 4: Old metadata key, 5: New metadata key. */ __('Block "%1$s" is declaring %2$s support in %3$s file under %4$s. %2$s support is now declared under %5$s.'), $metadata['name'], "<code>$typography_key</code>", '<code>block.json</code>', "<code>supports.$typography_key</code>", "<code>supports.typography.$typography_key</code>"), '5.8.0');

                _wp_array_set($metadata['supports'], ['typography', $typography_key], $support_for_key);
                unset($metadata['supports'][$typography_key]);
            }
        }

        return $metadata;
    }

    function build_query_vars_from_query_block($block, $page)
    {
        $query = [
            'post_type' => 'post',
            'order' => 'DESC',
            'orderby' => 'date',
            'post__not_in' => [],
        ];

        if(isset($block->context['query']))
        {
            if(! empty($block->context['query']['postType']))
            {
                $post_type_param = $block->context['query']['postType'];
                if(is_post_type_viewable($post_type_param))
                {
                    $query['post_type'] = $post_type_param;
                }
            }
            if(isset($block->context['query']['sticky']) && ! empty($block->context['query']['sticky']))
            {
                $sticky = get_option('sticky_posts');
                if('only' === $block->context['query']['sticky'])
                {
                    /*
                     * Passing an empty array to post__in will return have_posts() as true (and all posts will be returned).
                     * Logic should be used before hand to determine if WP_Query should be used in the event that the array
                     * being passed to post__in is empty.
                     *
                     * @see https://core.trac.wordpress.org/ticket/28099
                     */
                    $query['post__in'] = ! empty($sticky) ? $sticky : [0];
                    $query['ignore_sticky_posts'] = 1;
                }
                else
                {
                    $query['post__not_in'] = array_merge($query['post__not_in'], $sticky);
                }
            }
            if(! empty($block->context['query']['exclude']))
            {
                $excluded_post_ids = array_map('intval', $block->context['query']['exclude']);
                $excluded_post_ids = array_filter($excluded_post_ids);
                $query['post__not_in'] = array_merge($query['post__not_in'], $excluded_post_ids);
            }
            if(isset($block->context['query']['perPage']) && is_numeric($block->context['query']['perPage']))
            {
                $per_page = absint($block->context['query']['perPage']);
                $offset = 0;

                if(isset($block->context['query']['offset']) && is_numeric($block->context['query']['offset']))
                {
                    $offset = absint($block->context['query']['offset']);
                }

                $query['offset'] = ($per_page * ($page - 1)) + $offset;
                $query['posts_per_page'] = $per_page;
            }
            // Migrate `categoryIds` and `tagIds` to `tax_query` for backwards compatibility.
            if(! empty($block->context['query']['categoryIds']) || ! empty($block->context['query']['tagIds']))
            {
                $tax_query = [];
                if(! empty($block->context['query']['categoryIds']))
                {
                    $tax_query[] = [
                        'taxonomy' => 'category',
                        'terms' => array_filter(array_map('intval', $block->context['query']['categoryIds'])),
                        'include_children' => false,
                    ];
                }
                if(! empty($block->context['query']['tagIds']))
                {
                    $tax_query[] = [
                        'taxonomy' => 'post_tag',
                        'terms' => array_filter(array_map('intval', $block->context['query']['tagIds'])),
                        'include_children' => false,
                    ];
                }
                $query['tax_query'] = $tax_query;
            }
            if(! empty($block->context['query']['taxQuery']))
            {
                $query['tax_query'] = [];
                foreach($block->context['query']['taxQuery'] as $taxonomy => $terms)
                {
                    if(is_taxonomy_viewable($taxonomy) && ! empty($terms))
                    {
                        $query['tax_query'][] = [
                            'taxonomy' => $taxonomy,
                            'terms' => array_filter(array_map('intval', $terms)),
                            'include_children' => false,
                        ];
                    }
                }
            }
            if(
                isset($block->context['query']['order']) && in_array(strtoupper($block->context['query']['order']), [
                    'ASC',
                    'DESC'
                ],                                                   true)
            )
            {
                $query['order'] = strtoupper($block->context['query']['order']);
            }
            if(isset($block->context['query']['orderBy']))
            {
                $query['orderby'] = $block->context['query']['orderBy'];
            }
            if(isset($block->context['query']['author']))
            {
                if(is_array($block->context['query']['author']))
                {
                    $query['author__in'] = array_filter(array_map('intval', $block->context['query']['author']));
                }
                elseif(is_string($block->context['query']['author']))
                {
                    $query['author__in'] = array_filter(array_map('intval', explode(',', $block->context['query']['author'])));
                }
                elseif(is_int($block->context['query']['author']) && $block->context['query']['author'] > 0)
                {
                    $query['author'] = $block->context['query']['author'];
                }
            }
            if(! empty($block->context['query']['search']))
            {
                $query['s'] = $block->context['query']['search'];
            }
            if(! empty($block->context['query']['parents']) && is_post_type_hierarchical($query['post_type']))
            {
                $query['post_parent__in'] = array_filter(array_map('intval', $block->context['query']['parents']));
            }
        }

        return apply_filters('query_loop_block_query_vars', $query, $block, $page);
    }

    function get_query_pagination_arrow($block, $is_next)
    {
        $arrow_map = [
            'none' => '',
            'arrow' => [
                'next' => '→',
                'previous' => '←',
            ],
            'chevron' => [
                'next' => '»',
                'previous' => '«',
            ],
        ];
        if(! empty($block->context['paginationArrow']) && array_key_exists($block->context['paginationArrow'], $arrow_map) && ! empty($arrow_map[$block->context['paginationArrow']]))
        {
            $pagination_type = $is_next ? 'next' : 'previous';
            $arrow_attribute = $block->context['paginationArrow'];
            $arrow = $arrow_map[$block->context['paginationArrow']][$pagination_type];
            $arrow_classes = "wp-block-query-pagination-$pagination_type-arrow is-arrow-$arrow_attribute";

            return "<span class='$arrow_classes' aria-hidden='true'>$arrow</span>";
        }

        return null;
    }

    function build_comment_query_vars_from_block($block)
    {
        $comment_args = [
            'orderby' => 'comment_date_gmt',
            'order' => 'ASC',
            'status' => 'approve',
            'no_found_rows' => false,
        ];

        if(is_user_logged_in())
        {
            $comment_args['include_unapproved'] = [get_current_user_id()];
        }
        else
        {
            $unapproved_email = wp_get_unapproved_comment_author_email();

            if($unapproved_email)
            {
                $comment_args['include_unapproved'] = [$unapproved_email];
            }
        }

        if(! empty($block->context['postId']))
        {
            $comment_args['post_id'] = (int) $block->context['postId'];
        }

        if(get_option('thread_comments'))
        {
            $comment_args['hierarchical'] = 'threaded';
        }
        else
        {
            $comment_args['hierarchical'] = false;
        }

        if(get_option('page_comments') === '1' || get_option('page_comments') === true)
        {
            $per_page = get_option('comments_per_page');
            $default_page = get_option('default_comments_page');
            if($per_page > 0)
            {
                $comment_args['number'] = $per_page;

                $page = (int) get_query_var('cpage');
                if($page)
                {
                    $comment_args['paged'] = $page;
                }
                elseif('oldest' === $default_page)
                {
                    $comment_args['paged'] = 1;
                }
                elseif('newest' === $default_page)
                {
                    $max_num_pages = (int) (new WP_Comment_Query($comment_args))->max_num_pages;
                    if(0 !== $max_num_pages)
                    {
                        $comment_args['paged'] = $max_num_pages;
                    }
                }
                // Set the `cpage` query var to ensure the previous and next pagination links are correct
                // when inheriting the Discussion Settings.
                if(0 === $page && isset($comment_args['paged']) && $comment_args['paged'] > 0)
                {
                    set_query_var('cpage', $comment_args['paged']);
                }
            }
        }

        return $comment_args;
    }

    function get_comments_pagination_arrow($block, $pagination_type = 'next')
    {
        $arrow_map = [
            'none' => '',
            'arrow' => [
                'next' => '→',
                'previous' => '←',
            ],
            'chevron' => [
                'next' => '»',
                'previous' => '«',
            ],
        ];
        if(! empty($block->context['comments/paginationArrow']) && ! empty($arrow_map[$block->context['comments/paginationArrow']][$pagination_type]))
        {
            $arrow_attribute = $block->context['comments/paginationArrow'];
            $arrow = $arrow_map[$block->context['comments/paginationArrow']][$pagination_type];
            $arrow_classes = "wp-block-comments-pagination-$pagination_type-arrow is-arrow-$arrow_attribute";

            return "<span class='$arrow_classes' aria-hidden='true'>$arrow</span>";
        }

        return null;
    }
