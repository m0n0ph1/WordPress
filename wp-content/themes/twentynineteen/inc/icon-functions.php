<?php

    function twentynineteen_get_icon_svg($icon, $size = 24)
    {
        return TwentyNineteen_SVG_Icons::get_svg('ui', $icon, $size);
    }

    function twentynineteen_get_social_icon_svg($icon, $size = 24)
    {
        return TwentyNineteen_SVG_Icons::get_svg('social', $icon, $size);
    }

    function twentynineteen_get_social_link_svg($uri, $size = 24)
    {
        return TwentyNineteen_SVG_Icons::get_social_link_svg($uri, $size);
    }

    function twentynineteen_nav_menu_social_icons($item_output, $item, $depth, $args)
    {
        // Change SVG icon inside social links menu if there is supported URL.
        if('social' === $args->theme_location)
        {
            $svg = twentynineteen_get_social_link_svg($item->url, 32);
            if(empty($svg))
            {
                $svg = twentynineteen_get_icon_svg('link');
            }
            $item_output = str_replace($args->link_after, '</span>'.$svg, $item_output);
        }

        return $item_output;
    }

    add_filter('walker_nav_menu_start_el', 'twentynineteen_nav_menu_social_icons', 10, 4);

    function twentynineteen_add_dropdown_icons($item_output, $item, $depth, $args)
    {
        // Only add class to 'top level' items on the 'primary' menu.
        if(! isset($args->theme_location) || 'menu-1' !== $args->theme_location)
        {
            return $item_output;
        }

        if(in_array('mobile-parent-nav-menu-item', $item->classes, true) && isset($item->original_id))
        {
            // Inject the keyboard_arrow_left SVG inside the parent nav menu item, and let the item link to the parent item.
            // @todo Only do this for nested submenus? If on a first-level submenu, then really the link could be "#" since the desire is to remove the target entirely.
            $link = sprintf('<button class="menu-item-link-return" tabindex="-1">%s', twentynineteen_get_icon_svg('chevron_left', 24));

            // Replace opening <a> with <button>.
            $item_output = preg_replace(
                '/<a\s.*?>/', $link, $item_output, 1 // Limit.
            );

            // Replace closing </a> with </button>.
            $item_output = preg_replace(
                '#</a>#i', '</button>', $item_output, 1 // Limit.
            );
        }
        elseif(in_array('menu-item-has-children', $item->classes, true))
        {
            // Add SVG icon to parent items.
            $icon = twentynineteen_get_icon_svg('keyboard_arrow_down', 24);

            $item_output .= sprintf('<button class="submenu-expand" tabindex="-1">%s</button>', $icon);
        }

        return $item_output;
    }

    add_filter('walker_nav_menu_start_el', 'twentynineteen_add_dropdown_icons', 10, 4);
