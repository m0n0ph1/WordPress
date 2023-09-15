<?php
    /**
     * Twenty Twenty SVG Icon helper functions
     *
     * @package    WordPress
     * @subpackage Twenty_Twenty
     * @since      Twenty Twenty 1.0
     */
    if(! function_exists('twentytwenty_the_theme_svg'))
    {
        /**
         * Output and Get Theme SVG.
         * Output and get the SVG markup for an icon in the TwentyTwenty_SVG_Icons class.
         *
         * @param string $svg_name The name of the icon.
         * @param string $group    The group the icon belongs to.
         * @param string $color    Color code.
         *
         * @since Twenty Twenty 1.0
         *
         */
        function twentytwenty_the_theme_svg($svg_name, $group = 'ui', $color = '')
        {
            echo twentytwenty_get_theme_svg($svg_name, $group, $color); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in twentytwenty_get_theme_svg().
        }
    }
    if(! function_exists('twentytwenty_get_theme_svg'))
    {
        /**
         * Get information about the SVG icon.
         *
         * @param string $svg_name The name of the icon.
         * @param string $group    The group the icon belongs to.
         * @param string $color    Color code.
         *
         * @since Twenty Twenty 1.0
         *
         */
        function twentytwenty_get_theme_svg($svg_name, $group = 'ui', $color = '')
        {
            // Make sure that only our allowed tags and attributes are included.
            $svg = wp_kses(TwentyTwenty_SVG_Icons::get_svg($svg_name, $group, $color), [
                'svg' => [
                    'class' => true,
                    'xmlns' => true,
                    'width' => true,
                    'height' => true,
                    'viewbox' => true,
                    'aria-hidden' => true,
                    'role' => true,
                    'focusable' => true,
                ],
                'path' => [
                    'fill' => true,
                    'fill-rule' => true,
                    'd' => true,
                    'transform' => true,
                ],
                'polygon' => [
                    'fill' => true,
                    'fill-rule' => true,
                    'points' => true,
                    'transform' => true,
                    'focusable' => true,
                ],
            ]);
            if(! $svg)
            {
                return false;
            }

            return $svg;
        }
    }
