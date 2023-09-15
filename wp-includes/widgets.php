<?php

//
// Global Variables.
//

    global $wp_registered_sidebars, $wp_registered_widgets, $wp_registered_widget_controls, $wp_registered_widget_updates;

    $wp_registered_sidebars = [];

    $wp_registered_widgets = [];

    $wp_registered_widget_controls = [];

    $wp_registered_widget_updates = [];

    $_wp_sidebars_widgets = [];

    $GLOBALS['_wp_deprecated_widgets_callbacks'] = [
        'wp_widget_pages',
        'wp_widget_pages_control',
        'wp_widget_calendar',
        'wp_widget_calendar_control',
        'wp_widget_archives',
        'wp_widget_archives_control',
        'wp_widget_links',
        'wp_widget_meta',
        'wp_widget_meta_control',
        'wp_widget_search',
        'wp_widget_recent_entries',
        'wp_widget_recent_entries_control',
        'wp_widget_tag_cloud',
        'wp_widget_tag_cloud_control',
        'wp_widget_categories',
        'wp_widget_categories_control',
        'wp_widget_text',
        'wp_widget_text_control',
        'wp_widget_rss',
        'wp_widget_rss_control',
        'wp_widget_recent_comments',
        'wp_widget_recent_comments_control',
    ];

//
// Template tags & API functions.
//

    function register_widget($widget)
    {
        global $wp_widget_factory;

        $wp_widget_factory->register($widget);
    }

    function unregister_widget($widget)
    {
        global $wp_widget_factory;

        $wp_widget_factory->unregister($widget);
    }

    function register_sidebars($number = 1, $args = [])
    {
        global $wp_registered_sidebars;
        $number = (int) $number;

        if(is_string($args))
        {
            parse_str($args, $args);
        }

        for($i = 1; $i <= $number; $i++)
        {
            $_args = $args;

            if($number > 1)
            {
                if(isset($args['name']))
                {
                    $_args['name'] = sprintf($args['name'], $i);
                }
                else
                {
                    /* translators: %d: Sidebar number. */
                    $_args['name'] = sprintf(__('Sidebar %d'), $i);
                }
            }
            else
            {
                $_args['name'] = isset($args['name']) ? $args['name'] : __('Sidebar');
            }

            /*
		 * Custom specified ID's are suffixed if they exist already.
		 * Automatically generated sidebar names need to be suffixed regardless starting at -0.
		 */
            if(isset($args['id']))
            {
                $_args['id'] = $args['id'];
                $n = 2; // Start at -2 for conflicting custom IDs.
                while(is_registered_sidebar($_args['id']))
                {
                    $_args['id'] = $args['id'].'-'.$n++;
                }
            }
            else
            {
                $n = count($wp_registered_sidebars);
                do
                {
                    $_args['id'] = 'sidebar-'.++$n;
                }
                while(is_registered_sidebar($_args['id']));
            }
            register_sidebar($_args);
        }
    }

    function register_sidebar($args = [])
    {
        global $wp_registered_sidebars;

        $i = count($wp_registered_sidebars) + 1;

        $id_is_empty = empty($args['id']);

        $defaults = [
            /* translators: %d: Sidebar number. */
            'name' => sprintf(__('Sidebar %d'), $i),
            'id' => "sidebar-$i",
            'description' => '',
            'class' => '',
            'before_widget' => '<li id="%1$s" class="widget %2$s">',
            'after_widget' => "</li>\n",
            'before_title' => '<h2 class="widgettitle">',
            'after_title' => "</h2>\n",
            'before_sidebar' => '',
            'after_sidebar' => '',
            'show_in_rest' => false,
        ];

        $sidebar = wp_parse_args($args, apply_filters('register_sidebar_defaults', $defaults));

        if($id_is_empty)
        {
            _doing_it_wrong(__FUNCTION__, sprintf(/* translators: 1: The 'id' argument, 2: Sidebar name, 3: Recommended 'id' value. */ __('No %1$s was set in the arguments array for the "%2$s" sidebar. Defaulting to "%3$s". Manually set the %1$s to "%3$s" to silence this notice and keep existing sidebar content.'), '<code>id</code>', $sidebar['name'], $sidebar['id']), '4.2.0');
        }

        $wp_registered_sidebars[$sidebar['id']] = $sidebar;

        add_theme_support('widgets');

        do_action('register_sidebar', $sidebar);

        return $sidebar['id'];
    }

    function unregister_sidebar($sidebar_id)
    {
        global $wp_registered_sidebars;

        unset($wp_registered_sidebars[$sidebar_id]);
    }

    function is_registered_sidebar($sidebar_id)
    {
        global $wp_registered_sidebars;

        return isset($wp_registered_sidebars[$sidebar_id]);
    }

    function wp_register_sidebar_widget($id, $name, $output_callback, $options = [], ...$params)
    {
        global $wp_registered_widgets, $wp_registered_widget_controls, $wp_registered_widget_updates, $_wp_deprecated_widgets_callbacks;

        $id = strtolower($id);

        if(empty($output_callback))
        {
            unset($wp_registered_widgets[$id]);

            return;
        }

        $id_base = _get_widget_id_base($id);
        if(in_array($output_callback, $_wp_deprecated_widgets_callbacks, true) && ! is_callable($output_callback))
        {
            unset($wp_registered_widget_controls[$id]);
            unset($wp_registered_widget_updates[$id_base]);

            return;
        }

        $defaults = ['classname' => $output_callback];
        $options = wp_parse_args($options, $defaults);
        $widget = [
            'name' => $name,
            'id' => $id,
            'callback' => $output_callback,
            'params' => $params,
        ];
        $widget = array_merge($widget, $options);

        if(is_callable($output_callback) && (! isset($wp_registered_widgets[$id]) || did_action('widgets_init')))
        {
            do_action('wp_register_sidebar_widget', $widget);
            $wp_registered_widgets[$id] = $widget;
        }
    }

    function wp_widget_description($id)
    {
        if(! is_scalar($id))
        {
            return;
        }

        global $wp_registered_widgets;

        if(isset($wp_registered_widgets[$id]['description']))
        {
            return esc_html($wp_registered_widgets[$id]['description']);
        }
    }

    function wp_sidebar_description($id)
    {
        if(! is_scalar($id))
        {
            return;
        }

        global $wp_registered_sidebars;

        if(isset($wp_registered_sidebars[$id]['description']))
        {
            return wp_kses($wp_registered_sidebars[$id]['description'], 'sidebar_description');
        }
    }

    function wp_unregister_sidebar_widget($id)
    {
        do_action('wp_unregister_sidebar_widget', $id);

        wp_register_sidebar_widget($id, '', '');
        wp_unregister_widget_control($id);
    }

    function wp_register_widget_control($id, $name, $control_callback, $options = [], ...$params)
    {
        global $wp_registered_widget_controls, $wp_registered_widget_updates, $wp_registered_widgets, $_wp_deprecated_widgets_callbacks;

        $id = strtolower($id);
        $id_base = _get_widget_id_base($id);

        if(empty($control_callback))
        {
            unset($wp_registered_widget_controls[$id]);
            unset($wp_registered_widget_updates[$id_base]);

            return;
        }

        if(in_array($control_callback, $_wp_deprecated_widgets_callbacks, true) && ! is_callable($control_callback))
        {
            unset($wp_registered_widgets[$id]);

            return;
        }

        if(isset($wp_registered_widget_controls[$id]) && ! did_action('widgets_init'))
        {
            return;
        }

        $defaults = [
            'width' => 250,
            'height' => 200,
        ]; // Height is never used.
        $options = wp_parse_args($options, $defaults);
        $options['width'] = (int) $options['width'];
        $options['height'] = (int) $options['height'];

        $widget = [
            'name' => $name,
            'id' => $id,
            'callback' => $control_callback,
            'params' => $params,
        ];
        $widget = array_merge($widget, $options);

        $wp_registered_widget_controls[$id] = $widget;

        if(isset($wp_registered_widget_updates[$id_base]))
        {
            return;
        }

        if(isset($widget['params'][0]['number']))
        {
            $widget['params'][0]['number'] = -1;
        }

        unset($widget['width'], $widget['height'], $widget['name'], $widget['id']);
        $wp_registered_widget_updates[$id_base] = $widget;
    }

    function _register_widget_update_callback($id_base, $update_callback, $options = [], ...$params)
    {
        global $wp_registered_widget_updates;

        if(isset($wp_registered_widget_updates[$id_base]))
        {
            if(empty($update_callback))
            {
                unset($wp_registered_widget_updates[$id_base]);
            }

            return;
        }

        $widget = [
            'callback' => $update_callback,
            'params' => $params,
        ];

        $widget = array_merge($widget, $options);
        $wp_registered_widget_updates[$id_base] = $widget;
    }

    function _register_widget_form_callback($id, $name, $form_callback, $options = [], ...$params)
    {
        global $wp_registered_widget_controls;

        $id = strtolower($id);

        if(empty($form_callback))
        {
            unset($wp_registered_widget_controls[$id]);

            return;
        }

        if(isset($wp_registered_widget_controls[$id]) && ! did_action('widgets_init'))
        {
            return;
        }

        $defaults = [
            'width' => 250,
            'height' => 200,
        ];
        $options = wp_parse_args($options, $defaults);
        $options['width'] = (int) $options['width'];
        $options['height'] = (int) $options['height'];

        $widget = [
            'name' => $name,
            'id' => $id,
            'callback' => $form_callback,
            'params' => $params,
        ];
        $widget = array_merge($widget, $options);

        $wp_registered_widget_controls[$id] = $widget;
    }

    function wp_unregister_widget_control($id)
    {
        wp_register_widget_control($id, '', '');
    }

    function dynamic_sidebar($index = 1)
    {
        global $wp_registered_sidebars, $wp_registered_widgets;

        if(is_int($index))
        {
            $index = "sidebar-$index";
        }
        else
        {
            $index = sanitize_title($index);
            foreach((array) $wp_registered_sidebars as $key => $value)
            {
                if(sanitize_title($value['name']) === $index)
                {
                    $index = $key;
                    break;
                }
            }
        }

        $sidebars_widgets = wp_get_sidebars_widgets();
        if(empty($wp_registered_sidebars[$index]) || empty($sidebars_widgets[$index]) || ! is_array($sidebars_widgets[$index]))
        {
            do_action('dynamic_sidebar_before', $index, false);

            do_action('dynamic_sidebar_after', $index, false);

            return apply_filters('dynamic_sidebar_has_widgets', false, $index);
        }

        $sidebar = $wp_registered_sidebars[$index];

        $sidebar['before_sidebar'] = sprintf($sidebar['before_sidebar'], $sidebar['id'], $sidebar['class']);

        do_action('dynamic_sidebar_before', $index, true);

        if(! is_admin() && ! empty($sidebar['before_sidebar']))
        {
            echo $sidebar['before_sidebar'];
        }

        $did_one = false;
        foreach((array) $sidebars_widgets[$index] as $id)
        {
            if(! isset($wp_registered_widgets[$id]))
            {
                continue;
            }

            $params = array_merge([
                                      array_merge($sidebar, [
                                          'widget_id' => $id,
                                          'widget_name' => $wp_registered_widgets[$id]['name'],
                                      ]),
                                  ], (array) $wp_registered_widgets[$id]['params']);

            // Substitute HTML `id` and `class` attributes into `before_widget`.
            $classname_ = '';
            foreach((array) $wp_registered_widgets[$id]['classname'] as $cn)
            {
                if(is_string($cn))
                {
                    $classname_ .= '_'.$cn;
                }
                elseif(is_object($cn))
                {
                    $classname_ .= '_'.get_class($cn);
                }
            }
            $classname_ = ltrim($classname_, '_');

            $params[0]['before_widget'] = sprintf($params[0]['before_widget'], str_replace('\\', '_', $id), $classname_);

            $params = apply_filters('dynamic_sidebar_params', $params);

            $callback = $wp_registered_widgets[$id]['callback'];

            do_action('dynamic_sidebar', $wp_registered_widgets[$id]);

            if(is_callable($callback))
            {
                call_user_func_array($callback, $params);
                $did_one = true;
            }
        }

        if(! is_admin() && ! empty($sidebar['after_sidebar']))
        {
            echo $sidebar['after_sidebar'];
        }

        do_action('dynamic_sidebar_after', $index, true);

        return apply_filters('dynamic_sidebar_has_widgets', $did_one, $index);
    }

    function is_active_widget($callback = false, $widget_id = false, $id_base = false, $skip_inactive = true)
    {
        global $wp_registered_widgets;

        $sidebars_widgets = wp_get_sidebars_widgets();

        if(is_array($sidebars_widgets))
        {
            foreach($sidebars_widgets as $sidebar => $widgets)
            {
                if($skip_inactive && ('wp_inactive_widgets' === $sidebar || str_starts_with($sidebar, 'orphaned_widgets')))
                {
                    continue;
                }

                if(is_array($widgets))
                {
                    foreach($widgets as $widget)
                    {
                        if(($callback && isset($wp_registered_widgets[$widget]['callback']) && $wp_registered_widgets[$widget]['callback'] === $callback) || ($id_base && _get_widget_id_base($widget) === $id_base))
                        {
                            if(! $widget_id || $widget_id === $wp_registered_widgets[$widget]['id'])
                            {
                                return $sidebar;
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    function is_dynamic_sidebar()
    {
        global $wp_registered_widgets, $wp_registered_sidebars;

        $sidebars_widgets = get_option('sidebars_widgets');

        foreach((array) $wp_registered_sidebars as $index => $sidebar)
        {
            if(! empty($sidebars_widgets[$index]))
            {
                foreach((array) $sidebars_widgets[$index] as $widget)
                {
                    if(array_key_exists($widget, $wp_registered_widgets))
                    {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    function is_active_sidebar($index)
    {
        $index = (is_int($index)) ? "sidebar-$index" : sanitize_title($index);
        $sidebars_widgets = wp_get_sidebars_widgets();
        $is_active_sidebar = ! empty($sidebars_widgets[$index]);

        return apply_filters('is_active_sidebar', $is_active_sidebar, $index);
    }

//
// Internal Functions.
//

    function wp_get_sidebars_widgets($deprecated = true)
    {
        if(true !== $deprecated)
        {
            _deprecated_argument(__FUNCTION__, '2.8.1');
        }

        global $_wp_sidebars_widgets, $sidebars_widgets;

        /*
	 * If loading from front page, consult $_wp_sidebars_widgets rather than options
	 * to see if wp_convert_widget_settings() has made manipulations in memory.
	 */
        if(is_admin())
        {
            $sidebars_widgets = get_option('sidebars_widgets', []);
        }
        else
        {
            if(empty($_wp_sidebars_widgets))
            {
                $_wp_sidebars_widgets = get_option('sidebars_widgets', []);
            }

            $sidebars_widgets = $_wp_sidebars_widgets;
        }

        if(is_array($sidebars_widgets) && isset($sidebars_widgets['array_version']))
        {
            unset($sidebars_widgets['array_version']);
        }

        return apply_filters('sidebars_widgets', $sidebars_widgets);
    }

    function wp_get_sidebar($id)
    {
        global $wp_registered_sidebars;

        foreach((array) $wp_registered_sidebars as $sidebar)
        {
            if($sidebar['id'] === $id)
            {
                return $sidebar;
            }
        }

        if('wp_inactive_widgets' === $id)
        {
            return [
                'id' => 'wp_inactive_widgets',
                'name' => __('Inactive widgets'),
            ];
        }

        return null;
    }

    function wp_set_sidebars_widgets($sidebars_widgets)
    {
        global $_wp_sidebars_widgets;

        // Clear cached value used in wp_get_sidebars_widgets().
        $_wp_sidebars_widgets = null;

        if(! isset($sidebars_widgets['array_version']))
        {
            $sidebars_widgets['array_version'] = 3;
        }

        update_option('sidebars_widgets', $sidebars_widgets);
    }

    function wp_get_widget_defaults()
    {
        global $wp_registered_sidebars;

        $defaults = [];

        foreach((array) $wp_registered_sidebars as $index => $sidebar)
        {
            $defaults[$index] = [];
        }

        return $defaults;
    }

    function wp_convert_widget_settings($base_name, $option_name, $settings)
    {
        // This test may need expanding.
        $single = false;
        $changed = false;

        if(empty($settings))
        {
            $single = true;
        }
        else
        {
            foreach(array_keys($settings) as $number)
            {
                if('number' === $number)
                {
                    continue;
                }
                if(! is_numeric($number))
                {
                    $single = true;
                    break;
                }
            }
        }

        if($single)
        {
            $settings = [2 => $settings];

            // If loading from the front page, update sidebar in memory but don't save to options.
            if(is_admin())
            {
                $sidebars_widgets = get_option('sidebars_widgets');
            }
            else
            {
                if(empty($GLOBALS['_wp_sidebars_widgets']))
                {
                    $GLOBALS['_wp_sidebars_widgets'] = get_option('sidebars_widgets', []);
                }
                $sidebars_widgets = &$GLOBALS['_wp_sidebars_widgets'];
            }

            foreach((array) $sidebars_widgets as $index => $sidebar)
            {
                if(is_array($sidebar))
                {
                    foreach($sidebar as $i => $name)
                    {
                        if($base_name === $name)
                        {
                            $sidebars_widgets[$index][$i] = "$name-2";
                            $changed = true;
                            break 2;
                        }
                    }
                }
            }

            if(is_admin() && $changed)
            {
                update_option('sidebars_widgets', $sidebars_widgets);
            }
        }

        $settings['_multiwidget'] = 1;
        if(is_admin())
        {
            update_option($option_name, $settings);
        }

        return $settings;
    }

    function the_widget($widget, $instance = [], $args = [])
    {
        global $wp_widget_factory;

        if(! isset($wp_widget_factory->widgets[$widget]))
        {
            _doing_it_wrong(__FUNCTION__, sprintf(/* translators: %s: register_widget() */ __('Widgets need to be registered using %s, before they can be displayed.'), '<code>register_widget()</code>'), '4.9.0');

            return;
        }

        $widget_obj = $wp_widget_factory->widgets[$widget];
        if(! ($widget_obj instanceof WP_Widget))
        {
            return;
        }

        $default_args = [
            'before_widget' => '<div class="widget %s">',
            'after_widget' => '</div>',
            'before_title' => '<h2 class="widgettitle">',
            'after_title' => '</h2>',
        ];
        $args = wp_parse_args($args, $default_args);
        $args['before_widget'] = sprintf($args['before_widget'], $widget_obj->widget_options['classname']);

        $instance = wp_parse_args($instance);

        $instance = apply_filters('widget_display_callback', $instance, $widget_obj, $args);

        if(false === $instance)
        {
            return;
        }

        do_action('the_widget', $widget, $instance, $args);

        $widget_obj->_set(-1);
        $widget_obj->widget($args, $instance);
    }

    function _get_widget_id_base($id)
    {
        return preg_replace('/-[0-9]+$/', '', $id);
    }

    function _wp_sidebars_changed()
    {
        global $sidebars_widgets;

        if(! is_array($sidebars_widgets))
        {
            $sidebars_widgets = wp_get_sidebars_widgets();
        }

        retrieve_widgets(true);
    }

    function retrieve_widgets($theme_changed = false)
    {
        global $wp_registered_sidebars, $sidebars_widgets, $wp_registered_widgets;

        $registered_sidebars_keys = array_keys($wp_registered_sidebars);
        $registered_widgets_ids = array_keys($wp_registered_widgets);

        if(! is_array(get_theme_mod('sidebars_widgets')))
        {
            if(empty($sidebars_widgets))
            {
                return [];
            }

            unset($sidebars_widgets['array_version']);

            $sidebars_widgets_keys = array_keys($sidebars_widgets);
            sort($sidebars_widgets_keys);
            sort($registered_sidebars_keys);

            if($sidebars_widgets_keys === $registered_sidebars_keys)
            {
                $sidebars_widgets = _wp_remove_unregistered_widgets($sidebars_widgets, $registered_widgets_ids);

                return $sidebars_widgets;
            }
        }

        // Discard invalid, theme-specific widgets from sidebars.
        $sidebars_widgets = _wp_remove_unregistered_widgets($sidebars_widgets, $registered_widgets_ids);
        $sidebars_widgets = wp_map_sidebars_widgets($sidebars_widgets);

        // Find hidden/lost multi-widget instances.
        $shown_widgets = array_merge(...array_values($sidebars_widgets));
        $lost_widgets = array_diff($registered_widgets_ids, $shown_widgets);

        foreach($lost_widgets as $key => $widget_id)
        {
            $number = preg_replace('/.+?-([0-9]+)$/', '$1', $widget_id);

            // Only keep active and default widgets.
            if(is_numeric($number) && (int) $number < 2)
            {
                unset($lost_widgets[$key]);
            }
        }
        $sidebars_widgets['wp_inactive_widgets'] = array_merge($lost_widgets, (array) $sidebars_widgets['wp_inactive_widgets']);

        if('customize' !== $theme_changed)
        {
            // Update the widgets settings in the database.
            wp_set_sidebars_widgets($sidebars_widgets);
        }

        return $sidebars_widgets;
    }

    function wp_map_sidebars_widgets($existing_sidebars_widgets)
    {
        global $wp_registered_sidebars;

        $new_sidebars_widgets = [
            'wp_inactive_widgets' => [],
        ];

        // Short-circuit if there are no sidebars to map.
        if(! is_array($existing_sidebars_widgets) || empty($existing_sidebars_widgets))
        {
            return $new_sidebars_widgets;
        }

        foreach($existing_sidebars_widgets as $sidebar => $widgets)
        {
            if('wp_inactive_widgets' === $sidebar || str_starts_with($sidebar, 'orphaned_widgets'))
            {
                $new_sidebars_widgets['wp_inactive_widgets'] = array_merge($new_sidebars_widgets['wp_inactive_widgets'], (array) $widgets);
                unset($existing_sidebars_widgets[$sidebar]);
            }
        }

        // If old and new theme have just one sidebar, map it and we're done.
        if(1 === count($existing_sidebars_widgets) && 1 === count($wp_registered_sidebars))
        {
            $new_sidebars_widgets[key($wp_registered_sidebars)] = array_pop($existing_sidebars_widgets);

            return $new_sidebars_widgets;
        }

        // Map locations with the same slug.
        $existing_sidebars = array_keys($existing_sidebars_widgets);

        foreach($wp_registered_sidebars as $sidebar => $name)
        {
            if(in_array($sidebar, $existing_sidebars, true))
            {
                $new_sidebars_widgets[$sidebar] = $existing_sidebars_widgets[$sidebar];
                unset($existing_sidebars_widgets[$sidebar]);
            }
            elseif(! array_key_exists($sidebar, $new_sidebars_widgets))
            {
                $new_sidebars_widgets[$sidebar] = [];
            }
        }

        // If there are more sidebars, try to map them.
        if(! empty($existing_sidebars_widgets))
        {
            /*
		 * If old and new theme both have sidebars that contain phrases
		 * from within the same group, make an educated guess and map it.
		 */
            $common_slug_groups = [
                ['sidebar', 'primary', 'main', 'right'],
                ['second', 'left'],
                ['sidebar-2', 'footer', 'bottom'],
                ['header', 'top'],
            ];

            // Go through each group...
            foreach($common_slug_groups as $slug_group)
            {
                // ...and see if any of these slugs...
                foreach($slug_group as $slug)
                {
                    // ...and any of the new sidebars...
                    foreach($wp_registered_sidebars as $new_sidebar => $args)
                    {
                        // ...actually match!
                        if(false === stripos($new_sidebar, $slug) && false === stripos($slug, $new_sidebar))
                        {
                            continue;
                        }

                        // Then see if any of the existing sidebars...
                        foreach($existing_sidebars_widgets as $sidebar => $widgets)
                        {
                            // ...and any slug in the same group...
                            foreach($slug_group as $slug)
                            {
                                // ... have a match as well.
                                if(false === stripos($sidebar, $slug) && false === stripos($slug, $sidebar))
                                {
                                    continue;
                                }

                                // Make sure this sidebar wasn't mapped and removed previously.
                                if(! empty($existing_sidebars_widgets[$sidebar]))
                                {
                                    // We have a match that can be mapped!
                                    $new_sidebars_widgets[$new_sidebar] = array_merge($new_sidebars_widgets[$new_sidebar], $existing_sidebars_widgets[$sidebar]);

                                    // Remove the mapped sidebar so it can't be mapped again.
                                    unset($existing_sidebars_widgets[$sidebar]);

                                    // Go back and check the next new sidebar.
                                    continue 3;
                                }
                            } // End foreach ( $slug_group as $slug ).
                        } // End foreach ( $existing_sidebars_widgets as $sidebar => $widgets ).
                    } // End foreach ( $wp_registered_sidebars as $new_sidebar => $args ).
                } // End foreach ( $slug_group as $slug ).
            } // End foreach ( $common_slug_groups as $slug_group ).
        }

        // Move any left over widgets to inactive sidebar.
        foreach($existing_sidebars_widgets as $widgets)
        {
            if(is_array($widgets) && ! empty($widgets))
            {
                $new_sidebars_widgets['wp_inactive_widgets'] = array_merge($new_sidebars_widgets['wp_inactive_widgets'], $widgets);
            }
        }

        // Sidebars_widgets settings from when this theme was previously active.
        $old_sidebars_widgets = get_theme_mod('sidebars_widgets');
        $old_sidebars_widgets = isset($old_sidebars_widgets['data']) ? $old_sidebars_widgets['data'] : false;

        if(is_array($old_sidebars_widgets))
        {
            // Remove empty sidebars, no need to map those.
            $old_sidebars_widgets = array_filter($old_sidebars_widgets);

            // Only check sidebars that are empty or have not been mapped to yet.
            foreach($new_sidebars_widgets as $new_sidebar => $new_widgets)
            {
                if(array_key_exists($new_sidebar, $old_sidebars_widgets) && ! empty($new_widgets))
                {
                    unset($old_sidebars_widgets[$new_sidebar]);
                }
            }

            // Remove orphaned widgets, we're only interested in previously active sidebars.
            foreach($old_sidebars_widgets as $sidebar => $widgets)
            {
                if(str_starts_with($sidebar, 'orphaned_widgets'))
                {
                    unset($old_sidebars_widgets[$sidebar]);
                }
            }

            $old_sidebars_widgets = _wp_remove_unregistered_widgets($old_sidebars_widgets);

            if(! empty($old_sidebars_widgets))
            {
                // Go through each remaining sidebar...
                foreach($old_sidebars_widgets as $old_sidebar => $old_widgets)
                {
                    // ...and check every new sidebar...
                    foreach($new_sidebars_widgets as $new_sidebar => $new_widgets)
                    {
                        // ...for every widget we're trying to revive.
                        foreach($old_widgets as $key => $widget_id)
                        {
                            $active_key = array_search($widget_id, $new_widgets, true);

                            // If the widget is used elsewhere...
                            if(false !== $active_key)
                            {
                                // ...and that elsewhere is inactive widgets...
                                if('wp_inactive_widgets' === $new_sidebar)
                                {
                                    // ...remove it from there and keep the active version...
                                    unset($new_sidebars_widgets['wp_inactive_widgets'][$active_key]);
                                }
                                else
                                {
                                    // ...otherwise remove it from the old sidebar and keep it in the new one.
                                    unset($old_sidebars_widgets[$old_sidebar][$key]);
                                }
                            } // End if ( $active_key ).
                        } // End foreach ( $old_widgets as $key => $widget_id ).
                    } // End foreach ( $new_sidebars_widgets as $new_sidebar => $new_widgets ).
                } // End foreach ( $old_sidebars_widgets as $old_sidebar => $old_widgets ).
            } // End if ( ! empty( $old_sidebars_widgets ) ).

            // Restore widget settings from when theme was previously active.
            $new_sidebars_widgets = array_merge($new_sidebars_widgets, $old_sidebars_widgets);
        }

        return $new_sidebars_widgets;
    }

    function _wp_remove_unregistered_widgets($sidebars_widgets, $allowed_widget_ids = [])
    {
        if(empty($allowed_widget_ids))
        {
            $allowed_widget_ids = array_keys($GLOBALS['wp_registered_widgets']);
        }

        foreach($sidebars_widgets as $sidebar => $widgets)
        {
            if(is_array($widgets))
            {
                $sidebars_widgets[$sidebar] = array_intersect($widgets, $allowed_widget_ids);
            }
        }

        return $sidebars_widgets;
    }

    function wp_widget_rss_output($rss, $args = [])
    {
        if(is_string($rss))
        {
            $rss = fetch_feed($rss);
        }
        elseif(is_array($rss) && isset($rss['url']))
        {
            $args = $rss;
            $rss = fetch_feed($rss['url']);
        }
        elseif(! is_object($rss))
        {
            return;
        }

        if(is_wp_error($rss))
        {
            if(is_admin() || current_user_can('manage_options'))
            {
                echo '<p><strong>'.__('RSS Error:').'</strong> '.esc_html($rss->get_error_message()).'</p>';
            }

            return;
        }

        $default_args = [
            'show_author' => 0,
            'show_date' => 0,
            'show_summary' => 0,
            'items' => 0,
        ];
        $args = wp_parse_args($args, $default_args);

        $items = (int) $args['items'];
        if($items < 1 || 20 < $items)
        {
            $items = 10;
        }
        $show_summary = (int) $args['show_summary'];
        $show_author = (int) $args['show_author'];
        $show_date = (int) $args['show_date'];

        if(! $rss->get_item_quantity())
        {
            echo '<ul><li>'.__('An error has occurred, which probably means the feed is down. Try again later.').'</li></ul>';
            $rss->__destruct();
            unset($rss);

            return;
        }

        echo '<ul>';
        foreach($rss->get_items(0, $items) as $item)
        {
            $link = $item->get_link();
            while(! empty($link) && stristr($link, 'http') !== $link)
            {
                $link = substr($link, 1);
            }
            $link = esc_url(strip_tags($link));

            $title = esc_html(trim(strip_tags($item->get_title())));
            if(empty($title))
            {
                $title = __('Untitled');
            }

            $desc = html_entity_decode($item->get_description(), ENT_QUOTES, get_option('blog_charset'));
            $desc = esc_attr(wp_trim_words($desc, 55, ' [&hellip;]'));

            $summary = '';
            if($show_summary)
            {
                $summary = $desc;

                // Change existing [...] to [&hellip;].
                if(str_ends_with($summary, '[...]'))
                {
                    $summary = substr($summary, 0, -5).'[&hellip;]';
                }

                $summary = '<div class="rssSummary">'.esc_html($summary).'</div>';
            }

            $date = '';
            if($show_date)
            {
                $date = $item->get_date('U');

                if($date)
                {
                    $date = ' <span class="rss-date">'.date_i18n(get_option('date_format'), $date).'</span>';
                }
            }

            $author = '';
            if($show_author)
            {
                $author = $item->get_author();
                if(is_object($author))
                {
                    $author = $author->get_name();
                    $author = ' <cite>'.esc_html(strip_tags($author)).'</cite>';
                }
            }

            if('' === $link)
            {
                echo "<li>$title{$date}{$summary}{$author}</li>";
            }
            elseif($show_summary)
            {
                echo "<li><a class='rsswidget' href='$link'>$title</a>{$date}{$summary}{$author}</li>";
            }
            else
            {
                echo "<li><a class='rsswidget' href='$link'>$title</a>{$date}{$author}</li>";
            }
        }
        echo '</ul>';
        $rss->__destruct();
        unset($rss);
    }

    function wp_widget_rss_form($args, $inputs = null)
    {
        $default_inputs = [
            'url' => true,
            'title' => true,
            'items' => true,
            'show_summary' => true,
            'show_author' => true,
            'show_date' => true,
        ];
        $inputs = wp_parse_args($inputs, $default_inputs);

        $args['title'] = isset($args['title']) ? $args['title'] : '';
        $args['url'] = isset($args['url']) ? $args['url'] : '';
        $args['items'] = isset($args['items']) ? (int) $args['items'] : 0;

        if($args['items'] < 1 || 20 < $args['items'])
        {
            $args['items'] = 10;
        }

        $args['show_summary'] = isset($args['show_summary']) ? (int) $args['show_summary'] : (int) $inputs['show_summary'];
        $args['show_author'] = isset($args['show_author']) ? (int) $args['show_author'] : (int) $inputs['show_author'];
        $args['show_date'] = isset($args['show_date']) ? (int) $args['show_date'] : (int) $inputs['show_date'];

        if(! empty($args['error']))
        {
            echo '<p class="widget-error"><strong>'.__('RSS Error:').'</strong> '.esc_html($args['error']).'</p>';
        }

        $esc_number = esc_attr($args['number']);
        if($inputs['url']) :
            ?>
            <p><label for="rss-url-<?php echo $esc_number; ?>"><?php _e('Enter the RSS feed URL here:'); ?></label>
                <input class="widefat"
                       id="rss-url-<?php echo $esc_number; ?>"
                       name="widget-rss[<?php echo $esc_number; ?>][url]"
                       type="text"
                       value="<?php echo esc_url($args['url']); ?>"/></p>
        <?php endif;
        if($inputs['title']) : ?>
            <p>
                <label for="rss-title-<?php echo $esc_number; ?>"><?php _e('Give the feed a title (optional):'); ?></label>
                <input class="widefat"
                       id="rss-title-<?php echo $esc_number; ?>"
                       name="widget-rss[<?php echo $esc_number; ?>][title]"
                       type="text"
                       value="<?php echo esc_attr($args['title']); ?>"/></p>
        <?php endif;
        if($inputs['items']) : ?>
            <p>
                <label for="rss-items-<?php echo $esc_number; ?>"><?php _e('How many items would you like to display?'); ?></label>
                <select id="rss-items-<?php echo $esc_number; ?>" name="widget-rss[<?php echo $esc_number; ?>][items]">
                    <?php
                        for($i = 1; $i <= 20; ++$i)
                        {
                            echo "<option value='$i' ".selected($args['items'], $i, false).">$i</option>";
                        }
                    ?>
                </select></p>
        <?php endif;
        if($inputs['show_summary'] || $inputs['show_author'] || $inputs['show_date']) : ?>
            <p>
                <?php if($inputs['show_summary']) : ?>
                    <input id="rss-show-summary-<?php echo $esc_number; ?>"
                           name="widget-rss[<?php echo $esc_number; ?>][show_summary]"
                           type="checkbox"
                           value="1" <?php checked($args['show_summary']); ?> />
                    <label for="rss-show-summary-<?php echo $esc_number; ?>"><?php _e('Display item content?'); ?></label>
                    <br/>
                <?php endif;
                    if($inputs['show_author']) : ?>
                        <input id="rss-show-author-<?php echo $esc_number; ?>"
                               name="widget-rss[<?php echo $esc_number; ?>][show_author]"
                               type="checkbox"
                               value="1" <?php checked($args['show_author']); ?> />
                        <label for="rss-show-author-<?php echo $esc_number; ?>"><?php _e('Display item author if available?'); ?></label>
                        <br/>
                    <?php endif;
                    if($inputs['show_date']) : ?>
                        <input id="rss-show-date-<?php echo $esc_number; ?>"
                               name="widget-rss[<?php echo $esc_number; ?>][show_date]"
                               type="checkbox"
                               value="1" <?php checked($args['show_date']); ?>/>
                        <label for="rss-show-date-<?php echo $esc_number; ?>"><?php _e('Display item date?'); ?></label>
                        <br/>
                    <?php endif; ?>
            </p>
        <?php
        endif; // End of display options.
        foreach(array_keys($default_inputs) as $input) :
            if('hidden' === $inputs[$input]) :
                $id = str_replace('_', '-', $input);
                ?>
                <input type="hidden"
                       id="rss-<?php echo esc_attr($id); ?>-<?php echo $esc_number; ?>"
                       name="widget-rss[<?php echo $esc_number; ?>][<?php echo esc_attr($input); ?>]"
                       value="<?php echo esc_attr($args[$input]); ?>"/>
            <?php
            endif;
        endforeach;
    }

    function wp_widget_rss_process($widget_rss, $check_feed = true)
    {
        $items = (int) $widget_rss['items'];
        if($items < 1 || 20 < $items)
        {
            $items = 10;
        }
        $url = sanitize_url(strip_tags($widget_rss['url']));
        $title = isset($widget_rss['title']) ? trim(strip_tags($widget_rss['title'])) : '';
        $show_summary = isset($widget_rss['show_summary']) ? (int) $widget_rss['show_summary'] : 0;
        $show_author = isset($widget_rss['show_author']) ? (int) $widget_rss['show_author'] : 0;
        $show_date = isset($widget_rss['show_date']) ? (int) $widget_rss['show_date'] : 0;
        $error = false;
        $link = '';

        if($check_feed)
        {
            $rss = fetch_feed($url);

            if(is_wp_error($rss))
            {
                $error = $rss->get_error_message();
            }
            else
            {
                $link = esc_url(strip_tags($rss->get_permalink()));
                while(stristr($link, 'http') !== $link)
                {
                    $link = substr($link, 1);
                }

                $rss->__destruct();
                unset($rss);
            }
        }

        return compact('title', 'url', 'link', 'items', 'error', 'show_summary', 'show_author', 'show_date');
    }

    function wp_widgets_init()
    {
        if(! is_blog_installed())
        {
            return;
        }

        register_widget('WP_Widget_Pages');

        register_widget('WP_Widget_Calendar');

        register_widget('WP_Widget_Archives');

        if(get_option('link_manager_enabled'))
        {
            register_widget('WP_Widget_Links');
        }

        register_widget('WP_Widget_Media_Audio');

        register_widget('WP_Widget_Media_Image');

        register_widget('WP_Widget_Media_Gallery');

        register_widget('WP_Widget_Media_Video');

        register_widget('WP_Widget_Meta');

        register_widget('WP_Widget_Search');

        register_widget('WP_Widget_Text');

        register_widget('WP_Widget_Categories');

        register_widget('WP_Widget_Recent_Posts');

        register_widget('WP_Widget_Recent_Comments');

        register_widget('WP_Widget_RSS');

        register_widget('WP_Widget_Tag_Cloud');

        register_widget('WP_Nav_Menu_Widget');

        register_widget('WP_Widget_Custom_HTML');

        register_widget('WP_Widget_Block');

        do_action('widgets_init');
    }

    function wp_setup_widgets_block_editor()
    {
        add_theme_support('widgets-block-editor');
    }

    function wp_use_widgets_block_editor()
    {
        return apply_filters('use_widgets_block_editor', get_theme_support('widgets-block-editor'));
    }

    function wp_parse_widget_id($id)
    {
        $parsed = [];

        if(preg_match('/^(.+)-(\d+)$/', $id, $matches))
        {
            $parsed['id_base'] = $matches[1];
            $parsed['number'] = (int) $matches[2];
        }
        else
        {
            // Likely an old single widget.
            $parsed['id_base'] = $id;
        }

        return $parsed;
    }

    function wp_find_widgets_sidebar($widget_id)
    {
        foreach(wp_get_sidebars_widgets() as $sidebar_id => $widget_ids)
        {
            foreach($widget_ids as $maybe_widget_id)
            {
                if($maybe_widget_id === $widget_id)
                {
                    return (string) $sidebar_id;
                }
            }
        }

        return null;
    }

    function wp_assign_widget_to_sidebar($widget_id, $sidebar_id)
    {
        $sidebars = wp_get_sidebars_widgets();

        foreach($sidebars as $maybe_sidebar_id => $widgets)
        {
            foreach($widgets as $i => $maybe_widget_id)
            {
                if($widget_id === $maybe_widget_id && $sidebar_id !== $maybe_sidebar_id)
                {
                    unset($sidebars[$maybe_sidebar_id][$i]);
                    // We could technically break 2 here, but continue looping in case the ID is duplicated.
                    continue 2;
                }
            }
        }

        if($sidebar_id)
        {
            $sidebars[$sidebar_id][] = $widget_id;
        }

        wp_set_sidebars_widgets($sidebars);
    }

    function wp_render_widget($widget_id, $sidebar_id)
    {
        global $wp_registered_widgets, $wp_registered_sidebars;

        if(! isset($wp_registered_widgets[$widget_id]))
        {
            return '';
        }

        if(isset($wp_registered_sidebars[$sidebar_id]))
        {
            $sidebar = $wp_registered_sidebars[$sidebar_id];
        }
        elseif('wp_inactive_widgets' === $sidebar_id)
        {
            $sidebar = [];
        }
        else
        {
            return '';
        }

        $params = array_merge([
                                  array_merge($sidebar, [
                                      'widget_id' => $widget_id,
                                      'widget_name' => $wp_registered_widgets[$widget_id]['name'],
                                  ]),
                              ], (array) $wp_registered_widgets[$widget_id]['params']);

        // Substitute HTML `id` and `class` attributes into `before_widget`.
        $classname_ = '';
        foreach((array) $wp_registered_widgets[$widget_id]['classname'] as $cn)
        {
            if(is_string($cn))
            {
                $classname_ .= '_'.$cn;
            }
            elseif(is_object($cn))
            {
                $classname_ .= '_'.get_class($cn);
            }
        }
        $classname_ = ltrim($classname_, '_');
        $params[0]['before_widget'] = sprintf($params[0]['before_widget'], $widget_id, $classname_);

        $params = apply_filters('dynamic_sidebar_params', $params);

        $callback = $wp_registered_widgets[$widget_id]['callback'];

        ob_start();

        do_action('dynamic_sidebar', $wp_registered_widgets[$widget_id]);

        if(is_callable($callback))
        {
            call_user_func_array($callback, $params);
        }

        return ob_get_clean();
    }

    function wp_render_widget_control($id)
    {
        global $wp_registered_widget_controls;

        if(! isset($wp_registered_widget_controls[$id]['callback']))
        {
            return null;
        }

        $callback = $wp_registered_widget_controls[$id]['callback'];
        $params = $wp_registered_widget_controls[$id]['params'];

        ob_start();

        if(is_callable($callback))
        {
            call_user_func_array($callback, $params);
        }

        return ob_get_clean();
    }

    function wp_check_widget_editor_deps()
    {
        global $wp_scripts, $wp_styles;

        if($wp_scripts->query('wp-edit-widgets', 'enqueued') || $wp_scripts->query('wp-customize-widgets', 'enqueued'))
        {
            if($wp_scripts->query('wp-editor', 'enqueued'))
            {
                _doing_it_wrong('wp_enqueue_script()', sprintf(/* translators: 1: 'wp-editor', 2: 'wp-edit-widgets', 3: 'wp-customize-widgets'. */ __('"%1$s" script should not be enqueued together with the new widgets editor (%2$s or %3$s).'), 'wp-editor', 'wp-edit-widgets', 'wp-customize-widgets'), '5.8.0');
            }
            if($wp_styles->query('wp-edit-post', 'enqueued'))
            {
                _doing_it_wrong('wp_enqueue_style()', sprintf(/* translators: 1: 'wp-edit-post', 2: 'wp-edit-widgets', 3: 'wp-customize-widgets'. */ __('"%1$s" style should not be enqueued together with the new widgets editor (%2$s or %3$s).'), 'wp-edit-post', 'wp-edit-widgets', 'wp-customize-widgets'), '5.8.0');
            }
        }
    }

    function _wp_block_theme_register_classic_sidebars()
    {
        global $wp_registered_sidebars;

        if(! wp_is_block_theme())
        {
            return;
        }

        $classic_sidebars = get_theme_mod('wp_classic_sidebars');
        if(empty($classic_sidebars))
        {
            return;
        }

        // Don't use `register_sidebar` since it will enable the `widgets` support for a theme.
        foreach($classic_sidebars as $sidebar)
        {
            $wp_registered_sidebars[$sidebar['id']] = $sidebar;
        }
    }
