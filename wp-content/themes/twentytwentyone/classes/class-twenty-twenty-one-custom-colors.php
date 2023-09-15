<?php

    class Twenty_Twenty_One_Custom_Colors
    {
        public function __construct()
        {
            // Enqueue color variables for customizer & frontend.
            add_action('wp_enqueue_scripts', [$this, 'custom_color_variables']);

            // Enqueue color variables for editor.
            add_action('enqueue_block_assets', [$this, 'editor_custom_color_variables']);

            // Add body-class if needed.
            add_filter('body_class', [$this, 'body_class']);
        }

        public function custom_color_variables()
        {
            if('d1e4dd' !== strtolower(get_theme_mod('background_color', 'D1E4DD')))
            {
                wp_add_inline_style('twenty-twenty-one-style', $this->generate_custom_color_variables());
            }
        }

        public function generate_custom_color_variables($context = null)
        {
            $theme_css = 'editor' === $context ? ':root .editor-styles-wrapper{' : ':root{';
            $background_color = get_theme_mod('background_color', 'D1E4DD');

            if('d1e4dd' !== strtolower($background_color))
            {
                $theme_css .= '--global--color-background: #'.$background_color.';';
                $theme_css .= '--global--color-primary: '.$this->custom_get_readable_color($background_color).';';
                $theme_css .= '--global--color-secondary: '.$this->custom_get_readable_color($background_color).';';
                $theme_css .= '--button--color-background: '.$this->custom_get_readable_color($background_color).';';
                $theme_css .= '--button--color-text-hover: '.$this->custom_get_readable_color($background_color).';';

                if('#fff' === $this->custom_get_readable_color($background_color))
                {
                    $theme_css .= '--table--stripes-border-color: rgba(240, 240, 240, 0.15);';
                    $theme_css .= '--table--stripes-background-color: rgba(240, 240, 240, 0.15);';
                }
            }

            $theme_css .= '}';

            return $theme_css;
        }

        public function custom_get_readable_color($background_color)
        {
            return (127 < self::get_relative_luminance_from_hex($background_color)) ? '#000' : '#fff';
        }

        public static function get_relative_luminance_from_hex($hex)
        {
            // Remove the "#" symbol from the beginning of the color.
            $hex = ltrim($hex, '#');

            // Make sure there are 6 digits for the below calculations.
            if(3 === strlen($hex))
            {
                $hex = substr($hex, 0, 1).substr($hex, 0, 1).substr($hex, 1, 1).substr($hex, 1, 1).substr($hex, 2, 1).substr($hex, 2, 1);
            }

            // Get red, green, blue.
            $red = hexdec(substr($hex, 0, 2));
            $green = hexdec(substr($hex, 2, 2));
            $blue = hexdec(substr($hex, 4, 2));

            // Calculate the luminance.
            $lum = (0.2126 * $red) + (0.7152 * $green) + (0.0722 * $blue);

            return (int) round($lum);
        }

        public function editor_custom_color_variables()
        {
            wp_enqueue_style('twenty-twenty-one-custom-color-overrides', get_theme_file_uri('assets/css/custom-color-overrides.css'), [], wp_get_theme()->get('Version'));

            $background_color = get_theme_mod('background_color', 'D1E4DD');
            if('d1e4dd' !== strtolower($background_color))
            {
                wp_add_inline_style('twenty-twenty-one-custom-color-overrides', $this->generate_custom_color_variables('editor'));
            }
        }

        public function body_class($classes)
        {
            $background_color = get_theme_mod('background_color', 'D1E4DD');
            $luminance = self::get_relative_luminance_from_hex($background_color);

            if(127 > $luminance)
            {
                $classes[] = 'is-dark-theme';
            }
            else
            {
                $classes[] = 'is-light-theme';
            }

            if(225 <= $luminance)
            {
                $classes[] = 'has-background-white';
            }

            return $classes;
        }
    }
