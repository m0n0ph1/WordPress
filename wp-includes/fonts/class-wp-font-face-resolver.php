<?php

    class WP_Font_Face_Resolver
    {
        public static function get_fonts_from_theme_json()
        {
            $settings = wp_get_global_settings();

            // Bail out early if there are no font settings.
            if(empty($settings['typography']['fontFamilies']))
            {
                return [];
            }

            return static::parse_settings($settings);
        }

        private static function parse_settings(array $settings)
        {
            $fonts = [];

            foreach($settings['typography']['fontFamilies'] as $font_families)
            {
                foreach($font_families as $definition)
                {
                    // Skip if font-family "name" is not defined.
                    if(empty($definition['name']))
                    {
                        continue;
                    }

                    // Skip if "fontFace" is not defined, meaning there are no variations.
                    if(empty($definition['fontFace']))
                    {
                        continue;
                    }

                    $font_family = $definition['name'];

                    // Prepare the fonts array structure for this font-family.
                    if(! array_key_exists($font_family, $fonts))
                    {
                        $fonts[$font_family] = [];
                    }

                    $fonts[$font_family] = static::convert_font_face_properties($definition['fontFace'], $font_family);
                }
            }

            return $fonts;
        }

        private static function convert_font_face_properties(array $font_face_definition, $font_family_property)
        {
            $converted_font_faces = [];

            foreach($font_face_definition as $font_face)
            {
                // Add the font-family property to the font-face.
                $font_face['font-family'] = $font_family_property;

                // Converts the "file:./" src placeholder into a theme font file URI.
                if(! empty($font_face['src']))
                {
                    $font_face['src'] = static::to_theme_file_uri((array) $font_face['src']);
                }

                // Convert camelCase properties into kebab-case.
                $font_face = static::to_kebab_case($font_face);

                $converted_font_faces[] = $font_face;
            }

            return $converted_font_faces;
        }

        private static function to_theme_file_uri(array $src)
        {
            $placeholder = 'file:./';

            foreach($src as $src_key => $src_url)
            {
                // Skip if the src doesn't start with the placeholder, as there's nothing to replace.
                if(! str_starts_with($src_url, $placeholder))
                {
                    continue;
                }

                $src_file = str_replace($placeholder, '', $src_url);
                $src[$src_key] = get_theme_file_uri($src_file);
            }

            return $src;
        }

        private static function to_kebab_case(array $data)
        {
            foreach($data as $key => $value)
            {
                $kebab_case = _wp_to_kebab_case($key);
                $data[$kebab_case] = $value;
                if($kebab_case !== $key)
                {
                    unset($data[$key]);
                }
            }

            return $data;
        }
    }
