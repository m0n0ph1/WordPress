<?php

    if(is_network_admin())
    {
        do_action('_network_admin_menu');
    }
    elseif(is_user_admin())
    {
        do_action('_user_admin_menu');
    }
    else
    {
        do_action('_admin_menu');
    }

// Create list of page plugin hook names.
    foreach($menu as $menu_page)
    {
        $pos = strpos($menu_page[2], '?');

        if(false !== $pos)
        {
            // Handle post_type=post|page|foo pages.
            $hook_name = substr($menu_page[2], 0, $pos);
            $hook_args = substr($menu_page[2], $pos + 1);
            wp_parse_str($hook_args, $hook_args);

            // Set the hook name to be the post type.
            if(isset($hook_args['post_type']))
            {
                $hook_name = $hook_args['post_type'];
            }
            else
            {
                $hook_name = basename($hook_name, '.php');
            }
            unset($hook_args);
        }
        else
        {
            $hook_name = basename($menu_page[2], '.php');
        }

        $hook_name = sanitize_title($hook_name);

        if(isset($compat[$hook_name]))
        {
            $hook_name = $compat[$hook_name];
        }
        elseif(! $hook_name)
        {
            continue;
        }

        $admin_page_hooks[$menu_page[2]] = $hook_name;
    }
    unset($menu_page, $compat);

    $_wp_submenu_nopriv = [];
    $_wp_menu_nopriv = [];
// Loop over submenus and remove pages for which the user does not have privs.
    foreach($submenu as $parent => $sub)
    {
        foreach($sub as $index => $data)
        {
            if(! current_user_can($data[1]))
            {
                unset($submenu[$parent][$index]);
                $_wp_submenu_nopriv[$parent][$data[2]] = true;
            }
        }
        unset($index, $data);

        if(empty($submenu[$parent]))
        {
            unset($submenu[$parent]);
        }
    }
    unset($sub, $parent);

    /*
     * Loop over the top-level menu.
     * Menus for which the original parent is not accessible due to lack of privileges
     * will have the next submenu in line be assigned as the new menu parent.
     */
    foreach($menu as $id => $data)
    {
        if(empty($submenu[$data[2]]))
        {
            continue;
        }

        $subs = $submenu[$data[2]];
        $first_sub = reset($subs);
        $old_parent = $data[2];
        $new_parent = $first_sub[2];

        /*
         * If the first submenu is not the same as the assigned parent,
         * make the first submenu the new parent.
         */
        if($new_parent !== $old_parent)
        {
            $_wp_real_parent_file[$old_parent] = $new_parent;

            $menu[$id][2] = $new_parent;

            foreach($submenu[$old_parent] as $index => $data)
            {
                $submenu[$new_parent][$index] = $submenu[$old_parent][$index];
                unset($submenu[$old_parent][$index]);
            }
            unset($submenu[$old_parent], $index);

            if(isset($_wp_submenu_nopriv[$old_parent]))
            {
                $_wp_submenu_nopriv[$new_parent] = $_wp_submenu_nopriv[$old_parent];
            }
        }
    }
    unset($id, $data, $subs, $first_sub, $old_parent, $new_parent);

    if(is_network_admin())
    {
        do_action('network_admin_menu', '');
    }
    elseif(is_user_admin())
    {
        do_action('user_admin_menu', '');
    }
    else
    {
        do_action('admin_menu', '');
    }

    /*
     * Remove menus that have no accessible submenus and require privileges
     * that the user does not have. Run re-parent loop again.
     */
    foreach($menu as $id => $data)
    {
        if(! current_user_can($data[1]))
        {
            $_wp_menu_nopriv[$data[2]] = true;
        }

        /*
         * If there is only one submenu and it is has same destination as the parent,
         * remove the submenu.
         */
        if(! empty($submenu[$data[2]]) && 1 === count($submenu[$data[2]]))
        {
            $subs = $submenu[$data[2]];
            $first_sub = reset($subs);

            if($data[2] === $first_sub[2])
            {
                unset($submenu[$data[2]]);
            }
        }

        // If submenu is empty...
        if(empty($submenu[$data[2]]) && isset($_wp_menu_nopriv[$data[2]]))
        {
            unset($menu[$id]);
        }
    }
    unset($id, $data, $subs, $first_sub);

    function add_cssclass($class_to_add, $classes)
    {
        if(empty($classes))
        {
            return $class_to_add;
        }

        return $classes.' '.$class_to_add;
    }

    function add_menu_classes($menu)
    {
        $first_item = false;
        $last_order = false;
        $items_count = count($menu);

        $i = 0;

        foreach($menu as $order => $top)
        {
            ++$i;

            if(0 === $order)
            { // Dashboard is always shown/single.
                $menu[0][4] = add_cssclass('menu-top-first', $top[4]);
                $last_order = 0;
                continue;
            }

            if(str_starts_with($top[2], 'separator') && false !== $last_order)
            { // If separator.
                $first_item = true;
                $classes = $menu[$last_order][4];

                $menu[$last_order][4] = add_cssclass('menu-top-last', $classes);
                continue;
            }

            if($first_item)
            {
                $first_item = false;
                $classes = $menu[$order][4];

                $menu[$order][4] = add_cssclass('menu-top-first', $classes);
            }

            if($i === $items_count)
            { // Last item.
                $classes = $menu[$order][4];

                $menu[$order][4] = add_cssclass('menu-top-last', $classes);
            }

            $last_order = $order;
        }

        return apply_filters('add_menu_classes', $menu);
    }

    uksort($menu, 'strnatcasecmp'); // Make it all pretty.

    if(apply_filters('custom_menu_order', false))
    {
        $menu_order = [];

        foreach($menu as $menu_item)
        {
            $menu_order[] = $menu_item[2];
        }
        unset($menu_item);

        $default_menu_order = $menu_order;

        $menu_order = apply_filters('menu_order', $menu_order);
        $menu_order = array_flip($menu_order);

        $default_menu_order = array_flip($default_menu_order);

        function sort_menu($a, $b)
        {
            global $menu_order, $default_menu_order;

            $a = $a[2];
            $b = $b[2];

            if(isset($menu_order[$a]) && ! isset($menu_order[$b]))
            {
                return -1;
            }
            elseif(! isset($menu_order[$a]) && isset($menu_order[$b]))
            {
                return 1;
            }
            elseif(isset($menu_order[$a]) && isset($menu_order[$b]))
            {
                if($menu_order[$a] === $menu_order[$b])
                {
                    return 0;
                }

                if($menu_order[$a] < $menu_order[$b])
                {
                    return -1;
                }

                return 1;
            }
            else
            {
                if($default_menu_order[$a] <= $default_menu_order[$b])
                {
                    return -1;
                }

                return 1;
            }
        }

        usort($menu, 'sort_menu');
        unset($menu_order, $default_menu_order);
    }

// Prevent adjacent separators.
    $prev_menu_was_separator = false;
    foreach($menu as $id => $data)
    {
        if(false !== stristr($data[4], 'wp-menu-separator'))
        {
            // The previous item was a separator, so unset this one.
            if(true === $prev_menu_was_separator)
            {
                unset($menu[$id]);
            }

            // This item is a separator, so truthy the toggler and move on.
            $prev_menu_was_separator = true;
        }
        else
        {
            // This item is not a separator, so falsey the toggler and do nothing.
            $prev_menu_was_separator = false;
        }
    }
    unset($id, $data, $prev_menu_was_separator);

// Remove the last menu item if it is a separator.
    $last_menu_key = array_keys($menu);
    $last_menu_key = array_pop($last_menu_key);
    if(! empty($menu) && 'wp-menu-separator' === $menu[$last_menu_key][4])
    {
        unset($menu[$last_menu_key]);
    }
    unset($last_menu_key);

    if(! user_can_access_admin_page())
    {
        do_action('admin_page_access_denied');

        wp_die(__('Sorry, you are not allowed to access this page.'), 403);
    }

    $menu = add_menu_classes($menu);
