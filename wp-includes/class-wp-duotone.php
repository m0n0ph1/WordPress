<?php

    class WP_Duotone
    {
        private static $global_styles_block_names;

        private static $global_styles_presets;

        private static $used_global_styles_presets = [];

        private static $used_svg_filter_data = [];

        private static $block_css_declarations = [];

        public static function get_filter_svg_from_preset($preset)
        {
            _deprecated_function(__FUNCTION__, '6.3.0');

            $filter_id = self::get_filter_id_from_preset($preset);

            return self::get_filter_svg($filter_id, $preset['colors']);
        }

        public static function get_filter_id_from_preset($preset)
        {
            _deprecated_function(__FUNCTION__, '6.3.0');

            $filter_id = '';
            if(isset($preset['slug']))
            {
                $filter_id = self::get_filter_id($preset['slug']);
            }

            return $filter_id;
        }

        private static function get_filter_id($slug)
        {
            return "wp-duotone-$slug";
        }

        private static function get_filter_svg($filter_id, $colors)
        {
            $duotone_values = [
                'r' => [],
                'g' => [],
                'b' => [],
                'a' => [],
            ];

            foreach($colors as $color_str)
            {
                $color = self::colord_parse($color_str);

                if(null === $color)
                {
                    $error_message = sprintf(/* translators: %s: duotone colors */ __('"%s" in theme.json settings.color.duotone is not a hex or rgb string.'), $color_str);
                    _doing_it_wrong(__METHOD__, $error_message, '6.3.0');
                }
                else
                {
                    $duotone_values['r'][] = $color['r'] / 255;
                    $duotone_values['g'][] = $color['g'] / 255;
                    $duotone_values['b'][] = $color['b'] / 255;
                    $duotone_values['a'][] = $color['a'];
                }
            }

            ob_start();

            ?>

            <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 0 0"
                    width="0"
                    height="0"
                    focusable="false"
                    role="none"
                    style="visibility: hidden; position: absolute; left: -9999px; overflow: hidden;"
            >
                <defs>
                    <filter id="<?php echo esc_attr($filter_id); ?>">
                        <feColorMatrix
                                color-interpolation-filters="sRGB"
                                type="matrix"
                                values="
							.299 .587 .114 0 0
							.299 .587 .114 0 0
							.299 .587 .114 0 0
							.299 .587 .114 0 0
						"
                        />
                        <feComponentTransfer color-interpolation-filters="sRGB">
                            <feFuncR type="table"
                                     tableValues="<?php echo esc_attr(implode(' ', $duotone_values['r'])); ?>"/>
                            <feFuncG type="table"
                                     tableValues="<?php echo esc_attr(implode(' ', $duotone_values['g'])); ?>"/>
                            <feFuncB type="table"
                                     tableValues="<?php echo esc_attr(implode(' ', $duotone_values['b'])); ?>"/>
                            <feFuncA type="table"
                                     tableValues="<?php echo esc_attr(implode(' ', $duotone_values['a'])); ?>"/>
                        </feComponentTransfer>
                        <feComposite in2="SourceGraphic" operator="in"/>
                    </filter>
                </defs>
            </svg>

            <?php

            $svg = ob_get_clean();

            if(! SCRIPT_DEBUG)
            {
                // Clean up the whitespace.
                $svg = preg_replace("/[\r\n\t ]+/", ' ', $svg);
                $svg = str_replace('> <', '><', $svg);
                $svg = trim($svg);
            }

            return $svg;
        }

        private static function colord_parse($input)
        {
            $result = self::colord_parse_hex($input);

            if(! $result)
            {
                $result = self::colord_parse_rgba_string($input);
            }

            if(! $result)
            {
                $result = self::colord_parse_hsla_string($input);
            }

            return $result;
        }

        private static function colord_parse_hex($hex)
        {
            $is_match = preg_match('/^#([0-9a-f]{3,8})$/i', $hex, $hex_match);

            if(! $is_match)
            {
                return null;
            }

            $hex = $hex_match[1];

            if(4 >= strlen($hex))
            {
                return [
                    'r' => (int) base_convert($hex[0].$hex[0], 16, 10),
                    'g' => (int) base_convert($hex[1].$hex[1], 16, 10),
                    'b' => (int) base_convert($hex[2].$hex[2], 16, 10),
                    'a' => 4 === strlen($hex) ? round(base_convert($hex[3].$hex[3], 16, 10) / 255, 2) : 1,
                ];
            }

            if(6 === strlen($hex) || 8 === strlen($hex))
            {
                return [
                    'r' => (int) base_convert(substr($hex, 0, 2), 16, 10),
                    'g' => (int) base_convert(substr($hex, 2, 2), 16, 10),
                    'b' => (int) base_convert(substr($hex, 4, 2), 16, 10),
                    'a' => 8 === strlen($hex) ? round((int) base_convert(substr($hex, 6, 2), 16, 10) / 255, 2) : 1,
                ];
            }

            return null;
        }

        private static function colord_parse_rgba_string($input)
        {
            // Functional syntax.
            $is_match = preg_match('/^rgba?\(\s*([+-]?\d*\.?\d+)(%)?\s*,\s*([+-]?\d*\.?\d+)(%)?\s*,\s*([+-]?\d*\.?\d+)(%)?\s*(?:,\s*([+-]?\d*\.?\d+)(%)?\s*)?\)$/i', $input, $match);

            if(! $is_match)
            {
                // Whitespace syntax.
                $is_match = preg_match('/^rgba?\(\s*([+-]?\d*\.?\d+)(%)?\s+([+-]?\d*\.?\d+)(%)?\s+([+-]?\d*\.?\d+)(%)?\s*(?:\/\s*([+-]?\d*\.?\d+)(%)?\s*)?\)$/i', $input, $match);
            }

            if(! $is_match)
            {
                return null;
            }

            /*
             * For some reason, preg_match doesn't include empty matches at the end
             * of the array, so we add them manually to make things easier later.
             */
            for($i = 1; $i <= 8; $i++)
            {
                if(! isset($match[$i]))
                {
                    $match[$i] = '';
                }
            }

            if($match[2] !== $match[4] || $match[4] !== $match[6])
            {
                return null;
            }

            return self::colord_clamp_rgba([
                                               'r' => (float) $match[1] / ($match[2] ? 100 / 255 : 1),
                                               'g' => (float) $match[3] / ($match[4] ? 100 / 255 : 1),
                                               'b' => (float) $match[5] / ($match[6] ? 100 / 255 : 1),
                                               'a' => '' === $match[7] ? 1 : (float) $match[7] / ($match[8] ? 100 : 1),
                                           ]);
        }

        private static function colord_clamp_rgba($rgba)
        {
            $rgba['r'] = self::colord_clamp($rgba['r'], 0, 255);
            $rgba['g'] = self::colord_clamp($rgba['g'], 0, 255);
            $rgba['b'] = self::colord_clamp($rgba['b'], 0, 255);
            $rgba['a'] = self::colord_clamp($rgba['a']);

            return $rgba;
        }

        private static function colord_clamp($number, $min = 0, $max = 1)
        {
            if($number > $max)
            {
                return $max;
            }

            return $number > $min ? $number : $min;
        }

        private static function colord_parse_hsla_string($input)
        {
            // Functional syntax.
            $is_match = preg_match('/^hsla?\(\s*([+-]?\d*\.?\d+)(deg|rad|grad|turn)?\s*,\s*([+-]?\d*\.?\d+)%\s*,\s*([+-]?\d*\.?\d+)%\s*(?:,\s*([+-]?\d*\.?\d+)(%)?\s*)?\)$/i', $input, $match);

            if(! $is_match)
            {
                // Whitespace syntax.
                $is_match = preg_match('/^hsla?\(\s*([+-]?\d*\.?\d+)(deg|rad|grad|turn)?\s+([+-]?\d*\.?\d+)%\s+([+-]?\d*\.?\d+)%\s*(?:\/\s*([+-]?\d*\.?\d+)(%)?\s*)?\)$/i', $input, $match);
            }

            if(! $is_match)
            {
                return null;
            }

            /*
             * For some reason, preg_match doesn't include empty matches at the end
             * of the array, so we add them manually to make things easier later.
             */
            for($i = 1; $i <= 6; $i++)
            {
                if(! isset($match[$i]))
                {
                    $match[$i] = '';
                }
            }

            $hsla = self::colord_clamp_hsla([
                                                'h' => self::colord_parse_hue($match[1], $match[2]),
                                                's' => (float) $match[3],
                                                'l' => (float) $match[4],
                                                'a' => '' === $match[5] ? 1 : (float) $match[5] / ($match[6] ? 100 : 1),
                                            ]);

            return self::colord_hsla_to_rgba($hsla);
        }

        private static function colord_clamp_hsla($hsla)
        {
            $hsla['h'] = self::colord_clamp_hue($hsla['h']);
            $hsla['s'] = self::colord_clamp($hsla['s'], 0, 100);
            $hsla['l'] = self::colord_clamp($hsla['l'], 0, 100);
            $hsla['a'] = self::colord_clamp($hsla['a']);

            return $hsla;
        }

        private static function colord_clamp_hue($degrees)
        {
            $degrees = is_finite($degrees) ? $degrees % 360 : 0;

            if($degrees > 0)
            {
                return $degrees;
            }

            return $degrees + 360;
        }

        private static function colord_parse_hue($value, $unit = 'deg')
        {
            $angle_units = [
                'grad' => 360 / 400,
                'turn' => 360,
                'rad' => 360 / (M_PI * 2),
            ];

            $factor = $angle_units[$unit];
            if(! $factor)
            {
                $factor = 1;
            }

            return (float) $value * $factor;
        }

        private static function colord_hsla_to_rgba($hsla)
        {
            return self::colord_hsva_to_rgba(self::colord_hsla_to_hsva($hsla));
        }

        private static function colord_hsva_to_rgba($hsva)
        {
            $h = ($hsva['h'] / 360) * 6;
            $s = $hsva['s'] / 100;
            $v = $hsva['v'] / 100;
            $a = $hsva['a'];

            $hh = floor($h);
            $b = $v * (1 - $s);
            $c = $v * (1 - ($h - $hh) * $s);
            $d = $v * (1 - (1 - $h + $hh) * $s);
            $module = $hh % 6;

            return [
                'r' => [$v, $c, $b, $b, $d, $v][$module] * 255,
                'g' => [$d, $v, $v, $c, $b, $b][$module] * 255,
                'b' => [$b, $b, $d, $v, $v, $c][$module] * 255,
                'a' => $a,
            ];
        }

        private static function colord_hsla_to_hsva($hsla)
        {
            $h = $hsla['h'];
            $s = $hsla['s'];
            $l = $hsla['l'];
            $a = $hsla['a'];

            $s *= ($l < 50 ? $l : 100 - $l) / 100;

            return [
                'h' => $h,
                's' => $s > 0 ? ((2 * $s) / ($l + $s)) * 100 : 0,
                'v' => $l + $s,
                'a' => $a,
            ];
        }

        public static function register_duotone_support($block_type)
        {
            /*
             * Previous `color.__experimentalDuotone` support flag is migrated
             * to `filter.duotone` via `block_type_metadata_settings` filter.
             */
            if(block_has_support($block_type, ['filter', 'duotone'], null))
            {
                if(! $block_type->attributes)
                {
                    $block_type->attributes = [];
                }

                if(! array_key_exists('style', $block_type->attributes))
                {
                    $block_type->attributes['style'] = [
                        'type' => 'object',
                    ];
                }
            }
        }

        public static function render_duotone_support($block_content, $block, $wp_block)
        {
            if(empty($block_content) || ! $block['blockName'])
            {
                return $block_content;
            }
            $duotone_selector = self::get_selector($wp_block->block_type);

            if(! $duotone_selector)
            {
                return $block_content;
            }

            $global_styles_block_names = self::get_all_global_style_block_names();

            // The block should have a duotone attribute or have duotone defined in its theme.json to be processed.
            $has_duotone_attribute = isset($block['attrs']['style']['color']['duotone']);
            $has_global_styles_duotone = array_key_exists($block['blockName'], $global_styles_block_names);

            if(! $has_duotone_attribute && ! $has_global_styles_duotone)
            {
                return $block_content;
            }

            // Generate the pieces needed for rendering a duotone to the page.
            if($has_duotone_attribute)
            {
                /*
                 * Possible values for duotone attribute:
                 * 1. Array of colors - e.g. array('#000000', '#ffffff').
                 * 2. Variable for an existing Duotone preset - e.g. 'var:preset|duotone|blue-orange' or 'var(--wp--preset--duotone--blue-orange)''
                 * 3. A CSS string - e.g. 'unset' to remove globally applied duotone.
                 */

                $duotone_attr = $block['attrs']['style']['color']['duotone'];
                $is_preset = is_string($duotone_attr) && self::is_preset($duotone_attr);
                $is_css = is_string($duotone_attr) && ! $is_preset;
                $is_custom = is_array($duotone_attr);

                if($is_preset)
                {
                    $slug = self::get_slug_from_attribute($duotone_attr); // e.g. 'blue-orange'.
                    $filter_id = self::get_filter_id($slug); // e.g. 'wp-duotone-filter-blue-orange'.
                    $filter_value = self::get_css_var($slug); // e.g. 'var(--wp--preset--duotone--blue-orange)'.

                    // CSS custom property, SVG filter, and block CSS.
                    self::enqueue_global_styles_preset($filter_id, $duotone_selector, $filter_value);
                }
                elseif($is_css)
                {
                    $slug = wp_unique_id(sanitize_key($duotone_attr.'-')); // e.g. 'unset-1'.
                    $filter_id = self::get_filter_id($slug); // e.g. 'wp-duotone-filter-unset-1'.
                    $filter_value = $duotone_attr; // e.g. 'unset'.

                    // Just block CSS.
                    self::enqueue_block_css($filter_id, $duotone_selector, $filter_value);
                }
                elseif($is_custom)
                {
                    $slug = wp_unique_id(sanitize_key(implode('-', $duotone_attr).'-')); // e.g. '000000-ffffff-2'.
                    $filter_id = self::get_filter_id($slug); // e.g. 'wp-duotone-filter-000000-ffffff-2'.
                    $filter_value = self::get_filter_url($filter_id); // e.g. 'url(#wp-duotone-filter-000000-ffffff-2)'.
                    $filter_data = [
                        'slug' => $slug,
                        'colors' => $duotone_attr,
                    ];

                    // SVG filter and block CSS.
                    self::enqueue_custom_filter($filter_id, $duotone_selector, $filter_value, $filter_data);
                }
            }
            elseif($has_global_styles_duotone)
            {
                $slug = $global_styles_block_names[$block['blockName']]; // e.g. 'blue-orange'.
                $filter_id = self::get_filter_id($slug); // e.g. 'wp-duotone-filter-blue-orange'.
                $filter_value = self::get_css_var($slug); // e.g. 'var(--wp--preset--duotone--blue-orange)'.

                // CSS custom property, SVG filter, and block CSS.
                self::enqueue_global_styles_preset($filter_id, $duotone_selector, $filter_value);
            }

            // Like the layout hook, this assumes the hook only applies to blocks with a single wrapper.
            $tags = new WP_HTML_Tag_Processor($block_content);
            if($tags->next_tag())
            {
                $tags->add_class($filter_id);
            }

            return $tags->get_updated_html();
        }

        private static function get_selector($block_type)
        {
            if(! ($block_type instanceof WP_Block_Type))
            {
                return null;
            }

            /*
             * Backward compatibility with `supports.color.__experimentalDuotone`
             * is provided via the `block_type_metadata_settings` filter. If
             * `supports.filter.duotone` has not been set and the experimental
             * property has been, the experimental property value is copied into
             * `supports.filter.duotone`.
             */
            $duotone_support = block_has_support($block_type, ['filter', 'duotone']);
            if(! $duotone_support)
            {
                return null;
            }

            /*
             * If the experimental duotone support was set, that value is to be
             * treated as a selector and requires scoping.
             */
            $experimental_duotone = _wp_array_get($block_type->supports, ['color', '__experimentalDuotone'], false);
            if($experimental_duotone)
            {
                $root_selector = wp_get_block_css_selector($block_type);

                if(is_string($experimental_duotone))
                {
                    return WP_Theme_JSON::scope_selector($root_selector, $experimental_duotone);
                }

                return $root_selector;
            }

            // Regular filter.duotone support uses filter.duotone selectors with fallbacks.
            return wp_get_block_css_selector($block_type, ['filter', 'duotone'], true);
        }

        private static function get_all_global_style_block_names()
        {
            if(isset(self::$global_styles_block_names))
            {
                return self::$global_styles_block_names;
            }
            // Get the per block settings from the theme.json.
            $tree = WP_Theme_JSON_Resolver::get_merged_data();
            $block_nodes = $tree->get_styles_block_nodes();
            $theme_json = $tree->get_raw_data();

            self::$global_styles_block_names = [];

            foreach($block_nodes as $block_node)
            {
                // This block definition doesn't include any duotone settings. Skip it.
                if(empty($block_node['duotone']))
                {
                    continue;
                }

                // Value looks like this: 'var(--wp--preset--duotone--blue-orange)' or 'var:preset|duotone|blue-orange'.
                $duotone_attr_path = array_merge($block_node['path'], ['filter', 'duotone']);
                $duotone_attr = _wp_array_get($theme_json, $duotone_attr_path, []);

                if(empty($duotone_attr))
                {
                    continue;
                }
                // If it has a duotone filter preset, save the block name and the preset slug.
                $slug = self::get_slug_from_attribute($duotone_attr);

                if($slug && $slug !== $duotone_attr)
                {
                    self::$global_styles_block_names[$block_node['name']] = $slug;
                }
            }

            return self::$global_styles_block_names;
        }

        private static function get_slug_from_attribute($duotone_attr)
        {
            // Uses Branch Reset Groups `(?|…)` to return one capture group.
            preg_match('/(?|var:preset\|duotone\|(\S+)|var\(--wp--preset--duotone--(\S+)\))/', $duotone_attr, $matches);

            if(! empty($matches[1]))
            {
                return $matches[1];
            }

            return '';
        }

        private static function is_preset($duotone_attr)
        {
            $slug = self::get_slug_from_attribute($duotone_attr);
            $filter_id = self::get_filter_id($slug);

            return array_key_exists($filter_id, self::get_all_global_styles_presets());
        }

        private static function get_all_global_styles_presets()
        {
            if(isset(self::$global_styles_presets))
            {
                return self::$global_styles_presets;
            }
            // Get the per block settings from the theme.json.
            $tree = wp_get_global_settings();
            $presets_by_origin = _wp_array_get($tree, ['color', 'duotone'], []);

            self::$global_styles_presets = [];
            foreach($presets_by_origin as $presets)
            {
                foreach($presets as $preset)
                {
                    $filter_id = self::get_filter_id(_wp_to_kebab_case($preset['slug']));

                    self::$global_styles_presets[$filter_id] = $preset;
                }
            }

            return self::$global_styles_presets;
        }

        private static function get_css_var($slug)
        {
            $name = self::get_css_custom_property_name($slug);

            return "var($name)";
        }

        private static function get_css_custom_property_name($slug)
        {
            return "--wp--preset--duotone--$slug";
        }

        private static function enqueue_global_styles_preset($filter_id, $duotone_selector, $filter_value)
        {
            $global_styles_presets = self::get_all_global_styles_presets();
            if(! array_key_exists($filter_id, $global_styles_presets))
            {
                $error_message = sprintf(/* translators: %s: duotone filter ID */ __('The duotone id "%s" is not registered in theme.json settings'), $filter_id);
                _doing_it_wrong(__METHOD__, $error_message, '6.3.0');

                return;
            }
            self::$used_global_styles_presets[$filter_id] = $global_styles_presets[$filter_id];
            self::enqueue_custom_filter($filter_id, $duotone_selector, $filter_value, $global_styles_presets[$filter_id]);
        }

        private static function enqueue_custom_filter($filter_id, $duotone_selector, $filter_value, $filter_data)
        {
            self::$used_svg_filter_data[$filter_id] = $filter_data;
            self::enqueue_block_css($filter_id, $duotone_selector, $filter_value);
        }

        private static function enqueue_block_css($filter_id, $duotone_selector, $filter_value)
        {
            // Build the CSS selectors to which the filter will be applied.
            $selectors = explode(',', $duotone_selector);

            $selectors_scoped = [];
            foreach($selectors as $selector_part)
            {
                /*
                 * Assuming the selector part is a subclass selector (not a tag name)
                 * so we can prepend the filter id class. If we want to support elements
                 * such as `img` or namespaces, we'll need to add a case for that here.
                 */
                $selectors_scoped[] = '.'.$filter_id.trim($selector_part);
            }

            $selector = implode(', ', $selectors_scoped);

            self::$block_css_declarations[] = [
                'selector' => $selector,
                'declarations' => [
                    'filter' => $filter_value,
                ],
            ];
        }

        private static function get_filter_url($filter_id)
        {
            return "url(#$filter_id)";
        }

        public static function output_block_styles()
        {
            if(! empty(self::$block_css_declarations))
            {
                wp_style_engine_get_stylesheet_from_css_rules(self::$block_css_declarations, [
                    'context' => 'block-supports',
                ]);
            }
        }

        public static function output_global_styles()
        {
            if(! empty(self::$used_global_styles_presets))
            {
                wp_add_inline_style('global-styles', self::get_global_styles_presets(self::$used_global_styles_presets));
            }
        }

        private static function get_global_styles_presets($sources)
        {
            $css = 'body{';
            foreach($sources as $filter_id => $filter_data)
            {
                $slug = $filter_data['slug'];
                $colors = $filter_data['colors'];
                $css_property_name = self::get_css_custom_property_name($slug);
                $declaration_value = is_string($colors) ? $colors : self::get_filter_url($filter_id);
                $css .= "$css_property_name:$declaration_value;";
            }
            $css .= '}';

            return $css;
        }

        public static function output_footer_assets()
        {
            if(! empty(self::$used_svg_filter_data))
            {
                echo self::get_svg_definitions(self::$used_svg_filter_data);
            }

            // In block themes, the CSS is added in the head via wp_add_inline_style in the wp_enqueue_scripts action.
            if(! wp_is_block_theme())
            {
                $style_tag_id = 'core-block-supports-duotone';
                wp_register_style($style_tag_id, false);
                if(! empty(self::$used_global_styles_presets))
                {
                    wp_add_inline_style($style_tag_id, self::get_global_styles_presets(self::$used_global_styles_presets));
                }
                if(! empty(self::$block_css_declarations))
                {
                    wp_add_inline_style($style_tag_id, wp_style_engine_get_stylesheet_from_css_rules(self::$block_css_declarations));
                }
                wp_enqueue_style($style_tag_id);
            }
        }

        private static function get_svg_definitions($sources)
        {
            $svgs = '';
            foreach($sources as $filter_id => $filter_data)
            {
                $colors = $filter_data['colors'];
                $svgs .= self::get_filter_svg($filter_id, $colors);
            }

            return $svgs;
        }

        public static function add_editor_settings($settings)
        {
            $global_styles_presets = self::get_all_global_styles_presets();
            if(! empty($global_styles_presets))
            {
                if(! isset($settings['styles']))
                {
                    $settings['styles'] = [];
                }

                $settings['styles'][] = [
                    // For the editor we can add all of the presets by default.
                    'assets' => self::get_svg_definitions($global_styles_presets),
                    // The 'svgs' type is new in 6.3 and requires the corresponding JS changes in the EditorStyles component to work.
                    '__unstableType' => 'svgs',
                    // These styles not generated by global styles, so this must be false or they will be stripped out in wp_get_block_editor_settings.
                    'isGlobalStyles' => false,
                ];

                $settings['styles'][] = [
                    // For the editor we can add all of the presets by default.
                    'css' => self::get_global_styles_presets($global_styles_presets),
                    // This must be set and must be something other than 'theme' or they will be stripped out in the post editor <Editor> component.
                    '__unstableType' => 'presets',
                    // These styles are no longer generated by global styles, so this must be false or they will be stripped out in wp_get_block_editor_settings.
                    'isGlobalStyles' => false,
                ];
            }

            return $settings;
        }

        public static function migrate_experimental_duotone_support_flag($settings, $metadata)
        {
            $duotone_support = _wp_array_get($metadata, ['supports', 'color', '__experimentalDuotone'], null);

            if(! isset($settings['supports']['filter']['duotone']) && null !== $duotone_support)
            {
                _wp_array_set($settings, ['supports', 'filter', 'duotone'], (bool) $duotone_support);
            }

            return $settings;
        }

        public static function get_filter_css_property_value_from_preset($preset)
        {
            _deprecated_function(__FUNCTION__, '6.3.0');

            if(isset($preset['colors']) && is_string($preset['colors']))
            {
                return $preset['colors'];
            }

            $filter_id = self::get_filter_id_from_preset($preset);

            return 'url(#'.$filter_id.')';
        }
    }
