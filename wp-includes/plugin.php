<?php

// Initialize the filter globals.
    require __DIR__.'/class-wp-hook.php';

    global $wp_filter;

    global $wp_actions;

    global $wp_filters;

    global $wp_current_filter;

    if($wp_filter)
    {
        $wp_filter = WP_Hook::build_preinitialized_hooks($wp_filter);
    }
    else
    {
        $wp_filter = [];
    }

    if(! isset($wp_actions))
    {
        $wp_actions = [];
    }

    if(! isset($wp_filters))
    {
        $wp_filters = [];
    }

    if(! isset($wp_current_filter))
    {
        $wp_current_filter = [];
    }

    function add_filter($hook_name, $callback, $priority = 10, $accepted_args = 1)
    {
        global $wp_filter;

        if(! isset($wp_filter[$hook_name]))
        {
            $wp_filter[$hook_name] = new WP_Hook();
        }

        $wp_filter[$hook_name]->add_filter($hook_name, $callback, $priority, $accepted_args);

        return true;
    }

    function apply_filters($hook_name, $value, ...$args)
    {
        global $wp_filter, $wp_filters, $wp_current_filter;

        if(! isset($wp_filters[$hook_name]))
        {
            $wp_filters[$hook_name] = 1;
        }
        else
        {
            ++$wp_filters[$hook_name];
        }

        // Do 'all' actions first.
        if(isset($wp_filter['all']))
        {
            $wp_current_filter[] = $hook_name;

            $all_args = func_get_args(); // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
            _wp_call_all_hook($all_args);
        }

        if(! isset($wp_filter[$hook_name]))
        {
            if(isset($wp_filter['all']))
            {
                array_pop($wp_current_filter);
            }

            return $value;
        }

        if(! isset($wp_filter['all']))
        {
            $wp_current_filter[] = $hook_name;
        }

        // Pass the value to WP_Hook.
        array_unshift($args, $value);

        $filtered = $wp_filter[$hook_name]->apply_filters($value, $args);

        array_pop($wp_current_filter);

        return $filtered;
    }

    function apply_filters_ref_array($hook_name, $args)
    {
        global $wp_filter, $wp_filters, $wp_current_filter;

        if(! isset($wp_filters[$hook_name]))
        {
            $wp_filters[$hook_name] = 1;
        }
        else
        {
            ++$wp_filters[$hook_name];
        }

        // Do 'all' actions first.
        if(isset($wp_filter['all']))
        {
            $wp_current_filter[] = $hook_name;
            $all_args = func_get_args(); // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
            _wp_call_all_hook($all_args);
        }

        if(! isset($wp_filter[$hook_name]))
        {
            if(isset($wp_filter['all']))
            {
                array_pop($wp_current_filter);
            }

            return $args[0];
        }

        if(! isset($wp_filter['all']))
        {
            $wp_current_filter[] = $hook_name;
        }

        $filtered = $wp_filter[$hook_name]->apply_filters($args[0], $args);

        array_pop($wp_current_filter);

        return $filtered;
    }

    function has_filter($hook_name, $callback = false)
    {
        global $wp_filter;

        if(! isset($wp_filter[$hook_name]))
        {
            return false;
        }

        return $wp_filter[$hook_name]->has_filter($hook_name, $callback);
    }

    function remove_filter($hook_name, $callback, $priority = 10)
    {
        global $wp_filter;

        $r = false;

        if(isset($wp_filter[$hook_name]))
        {
            $r = $wp_filter[$hook_name]->remove_filter($hook_name, $callback, $priority);

            if(! $wp_filter[$hook_name]->callbacks)
            {
                unset($wp_filter[$hook_name]);
            }
        }

        return $r;
    }

    function remove_all_filters($hook_name, $priority = false)
    {
        global $wp_filter;

        if(isset($wp_filter[$hook_name]))
        {
            $wp_filter[$hook_name]->remove_all_filters($priority);

            if(! $wp_filter[$hook_name]->has_filters())
            {
                unset($wp_filter[$hook_name]);
            }
        }

        return true;
    }

    function current_filter()
    {
        global $wp_current_filter;

        return end($wp_current_filter);
    }

    function doing_filter($hook_name = null)
    {
        global $wp_current_filter;

        if(null === $hook_name)
        {
            return ! empty($wp_current_filter);
        }

        return in_array($hook_name, $wp_current_filter, true);
    }

    function did_filter($hook_name)
    {
        global $wp_filters;

        if(! isset($wp_filters[$hook_name]))
        {
            return 0;
        }

        return $wp_filters[$hook_name];
    }

    function add_action($hook_name, $callback, $priority = 10, $accepted_args = 1)
    {
        return add_filter($hook_name, $callback, $priority, $accepted_args);
    }

    function do_action($hook_name, ...$arg)
    {
        global $wp_filter, $wp_actions, $wp_current_filter;

        if(! isset($wp_actions[$hook_name]))
        {
            $wp_actions[$hook_name] = 1;
        }
        else
        {
            ++$wp_actions[$hook_name];
        }

        // Do 'all' actions first.
        if(isset($wp_filter['all']))
        {
            $wp_current_filter[] = $hook_name;
            $all_args = func_get_args(); // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
            _wp_call_all_hook($all_args);
        }

        if(! isset($wp_filter[$hook_name]))
        {
            if(isset($wp_filter['all']))
            {
                array_pop($wp_current_filter);
            }

            return;
        }

        if(! isset($wp_filter['all']))
        {
            $wp_current_filter[] = $hook_name;
        }

        if(empty($arg))
        {
            $arg[] = '';
        }
        elseif(is_array($arg[0]) && 1 === count($arg[0]) && isset($arg[0][0]) && is_object($arg[0][0]))
        {
            // Backward compatibility for PHP4-style passing of `array( &$this )` as action `$arg`.
            $arg[0] = $arg[0][0];
        }

        $wp_filter[$hook_name]->do_action($arg);

        array_pop($wp_current_filter);
    }

    function do_action_ref_array($hook_name, $args)
    {
        global $wp_filter, $wp_actions, $wp_current_filter;

        if(! isset($wp_actions[$hook_name]))
        {
            $wp_actions[$hook_name] = 1;
        }
        else
        {
            ++$wp_actions[$hook_name];
        }

        // Do 'all' actions first.
        if(isset($wp_filter['all']))
        {
            $wp_current_filter[] = $hook_name;
            $all_args = func_get_args(); // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
            _wp_call_all_hook($all_args);
        }

        if(! isset($wp_filter[$hook_name]))
        {
            if(isset($wp_filter['all']))
            {
                array_pop($wp_current_filter);
            }

            return;
        }

        if(! isset($wp_filter['all']))
        {
            $wp_current_filter[] = $hook_name;
        }

        $wp_filter[$hook_name]->do_action($args);

        array_pop($wp_current_filter);
    }

    function has_action($hook_name, $callback = false)
    {
        return has_filter($hook_name, $callback);
    }

    function remove_action($hook_name, $callback, $priority = 10)
    {
        return remove_filter($hook_name, $callback, $priority);
    }

    function remove_all_actions($hook_name, $priority = false)
    {
        return remove_all_filters($hook_name, $priority);
    }

    function current_action()
    {
        return current_filter();
    }

    function doing_action($hook_name = null)
    {
        return doing_filter($hook_name);
    }

    function did_action($hook_name)
    {
        global $wp_actions;

        if(! isset($wp_actions[$hook_name]))
        {
            return 0;
        }

        return $wp_actions[$hook_name];
    }

    function apply_filters_deprecated($hook_name, $args, $version, $replacement = '', $message = '')
    {
        if(! has_filter($hook_name))
        {
            return $args[0];
        }

        _deprecated_hook($hook_name, $version, $replacement, $message);

        return apply_filters_ref_array($hook_name, $args);
    }

    function do_action_deprecated($hook_name, $args, $version, $replacement = '', $message = '')
    {
        if(! has_action($hook_name))
        {
            return;
        }

        _deprecated_hook($hook_name, $version, $replacement, $message);

        do_action_ref_array($hook_name, $args);
    }

//
// Functions for handling plugins.
//

    function plugin_basename($file)
    {
        global $wp_plugin_paths;

        // $wp_plugin_paths contains normalized paths.
        $file = wp_normalize_path($file);

        arsort($wp_plugin_paths);

        foreach($wp_plugin_paths as $dir => $realdir)
        {
            if(str_starts_with($file, $realdir))
            {
                $file = $dir.substr($file, strlen($realdir));
            }
        }

        $plugin_dir = wp_normalize_path(WP_PLUGIN_DIR);
        $mu_plugin_dir = wp_normalize_path(WPMU_PLUGIN_DIR);

        // Get relative path from plugins directory.
        $file = preg_replace('#^'.preg_quote($plugin_dir, '#').'/|^'.preg_quote($mu_plugin_dir, '#').'/#', '', $file);
        $file = trim($file, '/');

        return $file;
    }

    function wp_register_plugin_realpath($file)
    {
        global $wp_plugin_paths;

        // Normalize, but store as static to avoid recalculation of a constant value.
        static $wp_plugin_path = null, $wpmu_plugin_path = null;

        if(! isset($wp_plugin_path))
        {
            $wp_plugin_path = wp_normalize_path(WP_PLUGIN_DIR);
            $wpmu_plugin_path = wp_normalize_path(WPMU_PLUGIN_DIR);
        }

        $plugin_path = wp_normalize_path(dirname($file));
        $plugin_realpath = wp_normalize_path(dirname(realpath($file)));

        if($plugin_path === $wp_plugin_path || $plugin_path === $wpmu_plugin_path)
        {
            return false;
        }

        if($plugin_path !== $plugin_realpath)
        {
            $wp_plugin_paths[$plugin_path] = $plugin_realpath;
        }

        return true;
    }

    function plugin_dir_path($file)
    {
        return trailingslashit(dirname($file));
    }

    function plugin_dir_url($file)
    {
        return trailingslashit(plugins_url('', $file));
    }

    function register_activation_hook($file, $callback)
    {
        $file = plugin_basename($file);
        add_action('activate_'.$file, $callback);
    }

    function register_deactivation_hook($file, $callback)
    {
        $file = plugin_basename($file);
        add_action('deactivate_'.$file, $callback);
    }

    function register_uninstall_hook($file, $callback)
    {
        if(is_array($callback) && is_object($callback[0]))
        {
            _doing_it_wrong(__FUNCTION__, __('Only a static class method or function can be used in an uninstall hook.'), '3.1.0');

            return;
        }

        /*
         * The option should not be autoloaded, because it is not needed in most
         * cases. Emphasis should be put on using the 'uninstall.php' way of
         * uninstalling the plugin.
         */
        $uninstallable_plugins = (array) get_option('uninstall_plugins');
        $plugin_basename = plugin_basename($file);

        if(! isset($uninstallable_plugins[$plugin_basename]) || $uninstallable_plugins[$plugin_basename] !== $callback)
        {
            $uninstallable_plugins[$plugin_basename] = $callback;
            update_option('uninstall_plugins', $uninstallable_plugins);
        }
    }

    function _wp_call_all_hook($args)
    {
        global $wp_filter;

        $wp_filter['all']->do_all_hook($args);
    }

    function _wp_filter_build_unique_id($hook_name, $callback, $priority)
    {
        if(is_string($callback))
        {
            return $callback;
        }

        if(is_object($callback))
        {
            // Closures are currently implemented as objects.
            $callback = [$callback, ''];
        }
        else
        {
            $callback = (array) $callback;
        }

        if(is_object($callback[0]))
        {
            // Object class calling.
            return spl_object_hash($callback[0]).$callback[1];
        }
        elseif(is_string($callback[0]))
        {
            // Static calling.
            return $callback[0].'::'.$callback[1];
        }
    }
