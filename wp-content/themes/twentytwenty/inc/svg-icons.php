<?php

    if(! function_exists('twentytwenty_the_theme_svg'))
    {
        function twentytwenty_the_theme_svg($svg_name, $group = 'ui', $color = '')
        {
            echo twentytwenty_get_theme_svg($svg_name, $group, $color); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in twentytwenty_get_theme_svg().
        }
    }

    if(! function_exists('twentytwenty_get_theme_svg'))
    {
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
