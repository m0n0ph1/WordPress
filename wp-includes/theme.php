<?php

    function wp_get_themes($args = [])
    {
        global $wp_theme_directories;

        $defaults = [
            'errors' => false,
            'allowed' => null,
            'blog_id' => 0,
        ];
        $args = wp_parse_args($args, $defaults);

        $theme_directories = search_theme_directories();

        if(is_array($wp_theme_directories) && count($wp_theme_directories) > 1)
        {
            /*
		 * Make sure the active theme wins out, in case search_theme_directories() picks the wrong
		 * one in the case of a conflict. (Normally, last registered theme root wins.)
		 */
            $current_theme = get_stylesheet();
            if(isset($theme_directories[$current_theme]))
            {
                $root_of_current_theme = get_raw_theme_root($current_theme);
                if(! in_array($root_of_current_theme, $wp_theme_directories, true))
                {
                    $root_of_current_theme = WP_CONTENT_DIR.$root_of_current_theme;
                }
                $theme_directories[$current_theme]['theme_root'] = $root_of_current_theme;
            }
        }

        if(empty($theme_directories))
        {
            return [];
        }

        if(is_multisite() && null !== $args['allowed'])
        {
            $allowed = $args['allowed'];
            if('network' === $allowed)
            {
                $theme_directories = array_intersect_key($theme_directories, WP_Theme::get_allowed_on_network());
            }
            elseif('site' === $allowed)
            {
                $theme_directories = array_intersect_key($theme_directories, WP_Theme::get_allowed_on_site($args['blog_id']));
            }
            elseif($allowed)
            {
                $theme_directories = array_intersect_key($theme_directories, WP_Theme::get_allowed($args['blog_id']));
            }
            else
            {
                $theme_directories = array_diff_key($theme_directories, WP_Theme::get_allowed($args['blog_id']));
            }
        }

        $themes = [];
        static $_themes = [];

        foreach($theme_directories as $theme => $theme_root)
        {
            if(isset($_themes[$theme_root['theme_root'].'/'.$theme]))
            {
                $themes[$theme] = $_themes[$theme_root['theme_root'].'/'.$theme];
            }
            else
            {
                $themes[$theme] = new WP_Theme($theme, $theme_root['theme_root']);

                $_themes[$theme_root['theme_root'].'/'.$theme] = $themes[$theme];
            }
        }

        if(null !== $args['errors'])
        {
            foreach($themes as $theme => $wp_theme)
            {
                if($wp_theme->errors() != $args['errors'])
                {
                    unset($themes[$theme]);
                }
            }
        }

        return $themes;
    }

    function wp_get_theme($stylesheet = '', $theme_root = '')
    {
        global $wp_theme_directories;

        if(empty($stylesheet))
        {
            $stylesheet = get_stylesheet();
        }

        if(empty($theme_root))
        {
            $theme_root = get_raw_theme_root($stylesheet);
            if(false === $theme_root)
            {
                $theme_root = WP_CONTENT_DIR.'/themes';
            }
            elseif(! in_array($theme_root, (array) $wp_theme_directories, true))
            {
                $theme_root = WP_CONTENT_DIR.$theme_root;
            }
        }

        return new WP_Theme($stylesheet, $theme_root);
    }

    function wp_clean_themes_cache($clear_update_cache = true)
    {
        if($clear_update_cache)
        {
            delete_site_transient('update_themes');
        }
        search_theme_directories(true);
        foreach(wp_get_themes(['errors' => null]) as $theme)
        {
            $theme->cache_delete();
        }
    }

    function is_child_theme()
    {
        return (TEMPLATEPATH !== STYLESHEETPATH);
    }

    function get_stylesheet()
    {
        return apply_filters('stylesheet', get_option('stylesheet'));
    }

    function get_stylesheet_directory()
    {
        $stylesheet = get_stylesheet();
        $theme_root = get_theme_root($stylesheet);
        $stylesheet_dir = "$theme_root/$stylesheet";

        return apply_filters('stylesheet_directory', $stylesheet_dir, $stylesheet, $theme_root);
    }

    function get_stylesheet_directory_uri()
    {
        $stylesheet = str_replace('%2F', '/', rawurlencode(get_stylesheet()));
        $theme_root_uri = get_theme_root_uri($stylesheet);
        $stylesheet_dir_uri = "$theme_root_uri/$stylesheet";

        return apply_filters('stylesheet_directory_uri', $stylesheet_dir_uri, $stylesheet, $theme_root_uri);
    }

    function get_stylesheet_uri()
    {
        $stylesheet_dir_uri = get_stylesheet_directory_uri();
        $stylesheet_uri = $stylesheet_dir_uri.'/style.css';

        return apply_filters('stylesheet_uri', $stylesheet_uri, $stylesheet_dir_uri);
    }

    function get_locale_stylesheet_uri()
    {
        global $wp_locale;
        $stylesheet_dir_uri = get_stylesheet_directory_uri();
        $dir = get_stylesheet_directory();
        $locale = get_locale();
        if(file_exists("$dir/$locale.css"))
        {
            $stylesheet_uri = "$stylesheet_dir_uri/$locale.css";
        }
        elseif(! empty($wp_locale->text_direction) && file_exists("$dir/{$wp_locale->text_direction}.css"))
        {
            $stylesheet_uri = "$stylesheet_dir_uri/{$wp_locale->text_direction}.css";
        }
        else
        {
            $stylesheet_uri = '';
        }

        return apply_filters('locale_stylesheet_uri', $stylesheet_uri, $stylesheet_dir_uri);
    }

    function get_template()
    {
        return apply_filters('template', get_option('template'));
    }

    function get_template_directory()
    {
        $template = get_template();
        $theme_root = get_theme_root($template);
        $template_dir = "$theme_root/$template";

        return apply_filters('template_directory', $template_dir, $template, $theme_root);
    }

    function get_template_directory_uri()
    {
        $template = str_replace('%2F', '/', rawurlencode(get_template()));
        $theme_root_uri = get_theme_root_uri($template);
        $template_dir_uri = "$theme_root_uri/$template";

        return apply_filters('template_directory_uri', $template_dir_uri, $template, $theme_root_uri);
    }

    function get_theme_roots()
    {
        global $wp_theme_directories;

        if(! is_array($wp_theme_directories) || count($wp_theme_directories) <= 1)
        {
            return '/themes';
        }

        $theme_roots = get_site_transient('theme_roots');
        if(false === $theme_roots)
        {
            search_theme_directories(true); // Regenerate the transient.
            $theme_roots = get_site_transient('theme_roots');
        }

        return $theme_roots;
    }

    function register_theme_directory($directory)
    {
        global $wp_theme_directories;

        if(! file_exists($directory))
        {
            // Try prepending as the theme directory could be relative to the content directory.
            $directory = WP_CONTENT_DIR.'/'.$directory;
            // If this directory does not exist, return and do not register.
            if(! file_exists($directory))
            {
                return false;
            }
        }

        if(! is_array($wp_theme_directories))
        {
            $wp_theme_directories = [];
        }

        $untrailed = untrailingslashit($directory);
        if(! empty($untrailed) && ! in_array($untrailed, $wp_theme_directories, true))
        {
            $wp_theme_directories[] = $untrailed;
        }

        return true;
    }

    function search_theme_directories($force = false)
    {
        global $wp_theme_directories;
        static $found_themes = null;

        if(empty($wp_theme_directories))
        {
            return false;
        }

        if(! $force && isset($found_themes))
        {
            return $found_themes;
        }

        $found_themes = [];

        $wp_theme_directories = (array) $wp_theme_directories;
        $relative_theme_roots = [];

        /*
	 * Set up maybe-relative, maybe-absolute array of theme directories.
	 * We always want to return absolute, but we need to cache relative
	 * to use in get_theme_root().
	 */
        foreach($wp_theme_directories as $theme_root)
        {
            if(str_starts_with($theme_root, WP_CONTENT_DIR))
            {
                $relative_theme_roots[str_replace(WP_CONTENT_DIR, '', $theme_root)] = $theme_root;
            }
            else
            {
                $relative_theme_roots[$theme_root] = $theme_root;
            }
        }

        $cache_expiration = apply_filters('wp_cache_themes_persistently', false, 'search_theme_directories');

        if($cache_expiration)
        {
            $cached_roots = get_site_transient('theme_roots');
            if(is_array($cached_roots))
            {
                foreach($cached_roots as $theme_dir => $theme_root)
                {
                    // A cached theme root is no longer around, so skip it.
                    if(! isset($relative_theme_roots[$theme_root]))
                    {
                        continue;
                    }
                    $found_themes[$theme_dir] = [
                        'theme_file' => $theme_dir.'/style.css',
                        'theme_root' => $relative_theme_roots[$theme_root], // Convert relative to absolute.
                    ];
                }

                return $found_themes;
            }
            if(! is_int($cache_expiration))
            {
                $cache_expiration = 30 * MINUTE_IN_SECONDS;
            }
        }
        else
        {
            $cache_expiration = 30 * MINUTE_IN_SECONDS;
        }

        /* Loop the registered theme directories and extract all themes */
        foreach($wp_theme_directories as $theme_root)
        {
            // Start with directories in the root of the active theme directory.
            $dirs = @ scandir($theme_root);
            if(! $dirs)
            {
                trigger_error("$theme_root is not readable", E_USER_NOTICE);
                continue;
            }
            foreach($dirs as $dir)
            {
                if(! is_dir($theme_root.'/'.$dir) || '.' === $dir[0] || 'CVS' === $dir)
                {
                    continue;
                }
                if(file_exists($theme_root.'/'.$dir.'/style.css'))
                {
                    /*
				 * wp-content/themes/a-single-theme
				 * wp-content/themes is $theme_root, a-single-theme is $dir.
				 */
                    $found_themes[$dir] = [
                        'theme_file' => $dir.'/style.css',
                        'theme_root' => $theme_root,
                    ];
                }
                else
                {
                    $found_theme = false;
                    /*
				 * wp-content/themes/a-folder-of-themes/*
				 * wp-content/themes is $theme_root, a-folder-of-themes is $dir, then themes are $sub_dirs.
				 */
                    $sub_dirs = @ scandir($theme_root.'/'.$dir);
                    if(! $sub_dirs)
                    {
                        trigger_error("$theme_root/$dir is not readable", E_USER_NOTICE);
                        continue;
                    }
                    foreach($sub_dirs as $sub_dir)
                    {
                        if(! is_dir($theme_root.'/'.$dir.'/'.$sub_dir) || '.' === $dir[0] || 'CVS' === $dir)
                        {
                            continue;
                        }
                        if(! file_exists($theme_root.'/'.$dir.'/'.$sub_dir.'/style.css'))
                        {
                            continue;
                        }
                        $found_themes[$dir.'/'.$sub_dir] = [
                            'theme_file' => $dir.'/'.$sub_dir.'/style.css',
                            'theme_root' => $theme_root,
                        ];
                        $found_theme = true;
                    }
                    /*
				 * Never mind the above, it's just a theme missing a style.css.
				 * Return it; WP_Theme will catch the error.
				 */
                    if(! $found_theme)
                    {
                        $found_themes[$dir] = [
                            'theme_file' => $dir.'/style.css',
                            'theme_root' => $theme_root,
                        ];
                    }
                }
            }
        }

        asort($found_themes);

        $theme_roots = [];
        $relative_theme_roots = array_flip($relative_theme_roots);

        foreach($found_themes as $theme_dir => $theme_data)
        {
            $theme_roots[$theme_dir] = $relative_theme_roots[$theme_data['theme_root']]; // Convert absolute to relative.
        }

        if(get_site_transient('theme_roots') != $theme_roots)
        {
            set_site_transient('theme_roots', $theme_roots, $cache_expiration);
        }

        return $found_themes;
    }

    function get_theme_root($stylesheet_or_template = '')
    {
        global $wp_theme_directories;

        $theme_root = '';

        if($stylesheet_or_template)
        {
            $theme_root = get_raw_theme_root($stylesheet_or_template);
            if($theme_root && ! in_array($theme_root, (array) $wp_theme_directories, true))
            {
                $theme_root = WP_CONTENT_DIR.$theme_root;
            }
        }

        if(! $theme_root)
        {
            $theme_root = WP_CONTENT_DIR.'/themes';
        }

        return apply_filters('theme_root', $theme_root);
    }

    function get_theme_root_uri($stylesheet_or_template = '', $theme_root = '')
    {
        global $wp_theme_directories;

        if($stylesheet_or_template && ! $theme_root)
        {
            $theme_root = get_raw_theme_root($stylesheet_or_template);
        }

        if($stylesheet_or_template && $theme_root)
        {
            if(in_array($theme_root, (array) $wp_theme_directories, true))
            {
                // Absolute path. Make an educated guess. YMMV -- but note the filter below.
                if(str_starts_with($theme_root, WP_CONTENT_DIR))
                {
                    $theme_root_uri = content_url(str_replace(WP_CONTENT_DIR, '', $theme_root));
                }
                elseif(str_starts_with($theme_root, ABSPATH))
                {
                    $theme_root_uri = site_url(str_replace(ABSPATH, '', $theme_root));
                }
                elseif(str_starts_with($theme_root, WP_PLUGIN_DIR) || str_starts_with($theme_root, WPMU_PLUGIN_DIR))
                {
                    $theme_root_uri = plugins_url(basename($theme_root), $theme_root);
                }
                else
                {
                    $theme_root_uri = $theme_root;
                }
            }
            else
            {
                $theme_root_uri = content_url($theme_root);
            }
        }
        else
        {
            $theme_root_uri = content_url('themes');
        }

        return apply_filters('theme_root_uri', $theme_root_uri, get_option('siteurl'), $stylesheet_or_template);
    }

    function get_raw_theme_root($stylesheet_or_template, $skip_cache = false)
    {
        global $wp_theme_directories;

        if(! is_array($wp_theme_directories) || count($wp_theme_directories) <= 1)
        {
            return '/themes';
        }

        $theme_root = false;

        // If requesting the root for the active theme, consult options to avoid calling get_theme_roots().
        if(! $skip_cache)
        {
            if(get_option('stylesheet') == $stylesheet_or_template)
            {
                $theme_root = get_option('stylesheet_root');
            }
            elseif(get_option('template') == $stylesheet_or_template)
            {
                $theme_root = get_option('template_root');
            }
        }

        if(empty($theme_root))
        {
            $theme_roots = get_theme_roots();
            if(! empty($theme_roots[$stylesheet_or_template]))
            {
                $theme_root = $theme_roots[$stylesheet_or_template];
            }
        }

        return $theme_root;
    }

    function locale_stylesheet()
    {
        $stylesheet = get_locale_stylesheet_uri();
        if(empty($stylesheet))
        {
            return;
        }

        $type_attr = current_theme_supports('html5', 'style') ? '' : ' type="text/css"';

        printf('<link rel="stylesheet" href="%s"%s media="screen" />', $stylesheet, $type_attr);
    }

    function switch_theme($stylesheet)
    {
        global $wp_theme_directories, $wp_customize, $sidebars_widgets, $wp_registered_sidebars;

        $requirements = validate_theme_requirements($stylesheet);
        if(is_wp_error($requirements))
        {
            wp_die($requirements);
        }

        $_sidebars_widgets = null;
        if('wp_ajax_customize_save' === current_action())
        {
            $old_sidebars_widgets_data_setting = $wp_customize->get_setting('old_sidebars_widgets_data');
            if($old_sidebars_widgets_data_setting)
            {
                $_sidebars_widgets = $wp_customize->post_value($old_sidebars_widgets_data_setting);
            }
        }
        elseif(is_array($sidebars_widgets))
        {
            $_sidebars_widgets = $sidebars_widgets;
        }

        if(is_array($_sidebars_widgets))
        {
            set_theme_mod('sidebars_widgets', [
                'time' => time(),
                'data' => $_sidebars_widgets,
            ]);
        }

        $nav_menu_locations = get_theme_mod('nav_menu_locations');
        update_option('theme_switch_menu_locations', $nav_menu_locations);

        if(func_num_args() > 1)
        {
            $stylesheet = func_get_arg(1);
        }

        $old_theme = wp_get_theme();
        $new_theme = wp_get_theme($stylesheet);
        $template = $new_theme->get_template();

        if(wp_is_recovery_mode())
        {
            $paused_themes = wp_paused_themes();
            $paused_themes->delete($old_theme->get_stylesheet());
            $paused_themes->delete($old_theme->get_template());
        }

        update_option('template', $template);
        update_option('stylesheet', $stylesheet);

        if(count($wp_theme_directories) > 1)
        {
            update_option('template_root', get_raw_theme_root($template, true));
            update_option('stylesheet_root', get_raw_theme_root($stylesheet, true));
        }
        else
        {
            delete_option('template_root');
            delete_option('stylesheet_root');
        }

        $new_name = $new_theme->get('Name');

        update_option('current_theme', $new_name);

        // Migrate from the old mods_{name} option to theme_mods_{slug}.
        if(is_admin() && false === get_option('theme_mods_'.$stylesheet))
        {
            $default_theme_mods = (array) get_option('mods_'.$new_name);
            if(! empty($nav_menu_locations) && empty($default_theme_mods['nav_menu_locations']))
            {
                $default_theme_mods['nav_menu_locations'] = $nav_menu_locations;
            }
            add_option("theme_mods_$stylesheet", $default_theme_mods);
        }
        else
        {
            /*
		 * Since retrieve_widgets() is called when initializing a theme in the Customizer,
		 * we need to remove the theme mods to avoid overwriting changes made via
		 * the Customizer when accessing wp-admin/widgets.php.
		 */
            if('wp_ajax_customize_save' === current_action())
            {
                remove_theme_mod('sidebars_widgets');
            }
        }

        // Stores classic sidebars for later use by block themes.
        if($new_theme->is_block_theme())
        {
            set_theme_mod('wp_classic_sidebars', $wp_registered_sidebars);
        }

        update_option('theme_switched', $old_theme->get_stylesheet());

        do_action('switch_theme', $new_name, $new_theme, $old_theme);
    }

    function validate_current_theme()
    {
        if(wp_installing() || ! apply_filters('validate_current_theme', true))
        {
            return true;
        }

        if(
            ! file_exists(get_template_directory().'/templates/index.html') && ! file_exists(get_template_directory().'/block-templates/index.html') // Deprecated path support since 5.9.0.
            && ! file_exists(get_template_directory().'/index.php')
        )
        {
            // Invalid.
        }
        elseif(! file_exists(get_template_directory().'/style.css'))
        {
            // Invalid.
        }
        elseif(is_child_theme() && ! file_exists(get_stylesheet_directory().'/style.css'))
        {
            // Invalid.
        }
        else
        {
            // Valid.
            return true;
        }

        $default = wp_get_theme(WP_DEFAULT_THEME);
        if($default->exists())
        {
            switch_theme(WP_DEFAULT_THEME);

            return false;
        }

        $default = WP_Theme::get_core_default_theme();
        if(false === $default || get_stylesheet() == $default->get_stylesheet())
        {
            return true;
        }

        switch_theme($default->get_stylesheet());

        return false;
    }

    function validate_theme_requirements($stylesheet)
    {
        $theme = wp_get_theme($stylesheet);

        $requirements = [
            'requires' => ! empty($theme->get('RequiresWP')) ? $theme->get('RequiresWP') : '',
            'requires_php' => ! empty($theme->get('RequiresPHP')) ? $theme->get('RequiresPHP') : '',
        ];

        $compatible_wp = is_wp_version_compatible($requirements['requires']);
        $compatible_php = is_php_version_compatible($requirements['requires_php']);

        if(! $compatible_wp && ! $compatible_php)
        {
            return new WP_Error('theme_wp_php_incompatible', sprintf(/* translators: %s: Theme name. */ _x('<strong>Error:</strong> Current WordPress and PHP versions do not meet minimum requirements for %s.', 'theme'), $theme->display('Name')));
        }
        elseif(! $compatible_php)
        {
            return new WP_Error('theme_php_incompatible', sprintf(/* translators: %s: Theme name. */ _x('<strong>Error:</strong> Current PHP version does not meet minimum requirements for %s.', 'theme'), $theme->display('Name')));
        }
        elseif(! $compatible_wp)
        {
            return new WP_Error('theme_wp_incompatible', sprintf(/* translators: %s: Theme name. */ _x('<strong>Error:</strong> Current WordPress version does not meet minimum requirements for %s.', 'theme'), $theme->display('Name')));
        }

        return true;
    }

    function get_theme_mods()
    {
        $theme_slug = get_option('stylesheet');
        $mods = get_option("theme_mods_$theme_slug");

        if(false === $mods)
        {
            $theme_name = get_option('current_theme');
            if(false === $theme_name)
            {
                $theme_name = wp_get_theme()->get('Name');
            }

            $mods = get_option("mods_$theme_name"); // Deprecated location.
            if(is_admin() && false !== $mods)
            {
                update_option("theme_mods_$theme_slug", $mods);
                delete_option("mods_$theme_name");
            }
        }

        if(! is_array($mods))
        {
            $mods = [];
        }

        return $mods;
    }

    function get_theme_mod($name, $default_value = false)
    {
        $mods = get_theme_mods();

        if(isset($mods[$name]))
        {
            return apply_filters("theme_mod_{$name}", $mods[$name]);
        }

        if(is_string($default_value) && preg_match('#(?<!%)%(?:\d+\$?)?s#', $default_value))
        {
            // Remove a single trailing percent sign.
            $default_value = preg_replace('#(?<!%)%$#', '', $default_value);
            $default_value = sprintf($default_value, get_template_directory_uri(), get_stylesheet_directory_uri());
        }

        return apply_filters("theme_mod_{$name}", $default_value);
    }

    function set_theme_mod($name, $value)
    {
        $mods = get_theme_mods();
        $old_value = isset($mods[$name]) ? $mods[$name] : false;

        $mods[$name] = apply_filters("pre_set_theme_mod_{$name}", $value, $old_value);

        $theme = get_option('stylesheet');

        return update_option("theme_mods_$theme", $mods);
    }

    function remove_theme_mod($name)
    {
        $mods = get_theme_mods();

        if(! isset($mods[$name]))
        {
            return;
        }

        unset($mods[$name]);

        if(empty($mods))
        {
            remove_theme_mods();

            return;
        }

        $theme = get_option('stylesheet');

        update_option("theme_mods_$theme", $mods);
    }

    function remove_theme_mods()
    {
        delete_option('theme_mods_'.get_option('stylesheet'));

        // Old style.
        $theme_name = get_option('current_theme');
        if(false === $theme_name)
        {
            $theme_name = wp_get_theme()->get('Name');
        }

        delete_option('mods_'.$theme_name);
    }

    function get_header_textcolor()
    {
        return get_theme_mod('header_textcolor', get_theme_support('custom-header', 'default-text-color'));
    }

    function header_textcolor()
    {
        echo get_header_textcolor();
    }

    function display_header_text()
    {
        if(! current_theme_supports('custom-header', 'header-text'))
        {
            return false;
        }

        $text_color = get_theme_mod('header_textcolor', get_theme_support('custom-header', 'default-text-color'));

        return 'blank' !== $text_color;
    }

    function has_header_image()
    {
        return (bool) get_header_image();
    }

    function get_header_image()
    {
        $url = get_theme_mod('header_image', get_theme_support('custom-header', 'default-image'));

        if('remove-header' === $url)
        {
            return false;
        }

        if(is_random_header_image())
        {
            $url = get_random_header_image();
        }

        $url = apply_filters('get_header_image', $url);

        if(! is_string($url))
        {
            return false;
        }

        $url = trim($url);

        return sanitize_url(set_url_scheme($url));
    }

    function get_header_image_tag($attr = [])
    {
        $header = get_custom_header();
        $header->url = get_header_image();

        if(! $header->url)
        {
            return '';
        }

        $width = absint($header->width);
        $height = absint($header->height);
        $alt = '';

        // Use alternative text assigned to the image, if available. Otherwise, leave it empty.
        if(! empty($header->attachment_id))
        {
            $image_alt = get_post_meta($header->attachment_id, '_wp_attachment_image_alt', true);

            if(is_string($image_alt))
            {
                $alt = $image_alt;
            }
        }

        $attr = wp_parse_args($attr, [
            'src' => $header->url,
            'width' => $width,
            'height' => $height,
            'alt' => $alt,
            'decoding' => 'async',
        ]);

        // Generate 'srcset' and 'sizes' if not already present.
        if(empty($attr['srcset']) && ! empty($header->attachment_id))
        {
            $image_meta = get_post_meta($header->attachment_id, '_wp_attachment_metadata', true);
            $size_array = [$width, $height];

            if(is_array($image_meta))
            {
                $srcset = wp_calculate_image_srcset($size_array, $header->url, $image_meta, $header->attachment_id);

                if(! empty($attr['sizes']))
                {
                    $sizes = $attr['sizes'];
                }
                else
                {
                    $sizes = wp_calculate_image_sizes($size_array, $header->url, $image_meta, $header->attachment_id);
                }

                if($srcset && $sizes)
                {
                    $attr['srcset'] = $srcset;
                    $attr['sizes'] = $sizes;
                }
            }
        }

        $attr = array_merge($attr, wp_get_loading_optimization_attributes('img', $attr, 'get_header_image_tag'));

        /*
	 * If the default value of `lazy` for the `loading` attribute is overridden
	 * to omit the attribute for this image, ensure it is not included.
	 */
        if(isset($attr['loading']) && ! $attr['loading'])
        {
            unset($attr['loading']);
        }

        // If the `fetchpriority` attribute is overridden and set to false or an empty string.
        if(isset($attr['fetchpriority']) && ! $attr['fetchpriority'])
        {
            unset($attr['fetchpriority']);
        }

        // If the `decoding` attribute is overridden and set to false or an empty string.
        if(isset($attr['decoding']) && ! $attr['decoding'])
        {
            unset($attr['decoding']);
        }

        $attr = apply_filters('get_header_image_tag_attributes', $attr, $header);

        $attr = array_map('esc_attr', $attr);
        $html = '<img';

        foreach($attr as $name => $value)
        {
            $html .= ' '.$name.'="'.$value.'"';
        }

        $html .= ' />';

        return apply_filters('get_header_image_tag', $html, $header, $attr);
    }

    function the_header_image_tag($attr = [])
    {
        echo get_header_image_tag($attr);
    }

    function _get_random_header_data()
    {
        global $_wp_default_headers;
        static $_wp_random_header = null;

        if(empty($_wp_random_header))
        {
            $header_image_mod = get_theme_mod('header_image', '');
            $headers = [];

            if('random-uploaded-image' === $header_image_mod)
            {
                $headers = get_uploaded_header_images();
            }
            elseif(! empty($_wp_default_headers))
            {
                if('random-default-image' === $header_image_mod)
                {
                    $headers = $_wp_default_headers;
                }
                else
                {
                    if(current_theme_supports('custom-header', 'random-default'))
                    {
                        $headers = $_wp_default_headers;
                    }
                }
            }

            if(empty($headers))
            {
                return new stdClass();
            }

            $_wp_random_header = (object) $headers[array_rand($headers)];

            $_wp_random_header->url = sprintf($_wp_random_header->url, get_template_directory_uri(), get_stylesheet_directory_uri());

            $_wp_random_header->thumbnail_url = sprintf($_wp_random_header->thumbnail_url, get_template_directory_uri(), get_stylesheet_directory_uri());
        }

        return $_wp_random_header;
    }

    function get_random_header_image()
    {
        $random_image = _get_random_header_data();

        if(empty($random_image->url))
        {
            return '';
        }

        return $random_image->url;
    }

    function is_random_header_image($type = 'any')
    {
        $header_image_mod = get_theme_mod('header_image', get_theme_support('custom-header', 'default-image'));

        if('any' === $type)
        {
            if('random-default-image' === $header_image_mod || 'random-uploaded-image' === $header_image_mod || ('' !== get_random_header_image() && empty($header_image_mod)))
            {
                return true;
            }
        }
        else
        {
            if("random-$type-image" === $header_image_mod)
            {
                return true;
            }
            elseif('default' === $type && empty($header_image_mod) && '' !== get_random_header_image())
            {
                return true;
            }
        }

        return false;
    }

    function header_image()
    {
        $image = get_header_image();

        if($image)
        {
            echo esc_url($image);
        }
    }

    function get_uploaded_header_images()
    {
        $header_images = [];

        // @todo Caching.
        $headers = get_posts([
                                 'post_type' => 'attachment',
                                 'meta_key' => '_wp_attachment_is_custom_header',
                                 'meta_value' => get_option('stylesheet'),
                                 'orderby' => 'none',
                                 'nopaging' => true,
                             ]);

        if(empty($headers))
        {
            return [];
        }

        foreach((array) $headers as $header)
        {
            $url = sanitize_url(wp_get_attachment_url($header->ID));
            $header_data = wp_get_attachment_metadata($header->ID);
            $header_index = $header->ID;

            $header_images[$header_index] = [];
            $header_images[$header_index]['attachment_id'] = $header->ID;
            $header_images[$header_index]['url'] = $url;
            $header_images[$header_index]['thumbnail_url'] = $url;
            $header_images[$header_index]['alt_text'] = get_post_meta($header->ID, '_wp_attachment_image_alt', true);

            if(isset($header_data['attachment_parent']))
            {
                $header_images[$header_index]['attachment_parent'] = $header_data['attachment_parent'];
            }
            else
            {
                $header_images[$header_index]['attachment_parent'] = '';
            }

            if(isset($header_data['width']))
            {
                $header_images[$header_index]['width'] = $header_data['width'];
            }
            if(isset($header_data['height']))
            {
                $header_images[$header_index]['height'] = $header_data['height'];
            }
        }

        return $header_images;
    }

    function get_custom_header()
    {
        global $_wp_default_headers;

        if(is_random_header_image())
        {
            $data = _get_random_header_data();
        }
        else
        {
            $data = get_theme_mod('header_image_data');
            if(! $data && current_theme_supports('custom-header', 'default-image'))
            {
                $directory_args = [get_template_directory_uri(), get_stylesheet_directory_uri()];
                $data = [];
                $data['url'] = vsprintf(get_theme_support('custom-header', 'default-image'), $directory_args);
                $data['thumbnail_url'] = $data['url'];
                if(! empty($_wp_default_headers))
                {
                    foreach((array) $_wp_default_headers as $default_header)
                    {
                        $url = vsprintf($default_header['url'], $directory_args);
                        if($data['url'] == $url)
                        {
                            $data = $default_header;
                            $data['url'] = $url;
                            $data['thumbnail_url'] = vsprintf($data['thumbnail_url'], $directory_args);
                            break;
                        }
                    }
                }
            }
        }

        $default = [
            'url' => '',
            'thumbnail_url' => '',
            'width' => get_theme_support('custom-header', 'width'),
            'height' => get_theme_support('custom-header', 'height'),
            'video' => get_theme_support('custom-header', 'video'),
        ];

        return (object) wp_parse_args($data, $default);
    }

    function register_default_headers($headers)
    {
        global $_wp_default_headers;

        $_wp_default_headers = array_merge((array) $_wp_default_headers, (array) $headers);
    }

    function unregister_default_headers($header)
    {
        global $_wp_default_headers;

        if(is_array($header))
        {
            array_map('unregister_default_headers', $header);
        }
        elseif(isset($_wp_default_headers[$header]))
        {
            unset($_wp_default_headers[$header]);

            return true;
        }
        else
        {
            return false;
        }
    }

    function has_header_video()
    {
        return (bool) get_header_video_url();
    }

    function get_header_video_url()
    {
        $id = absint(get_theme_mod('header_video'));

        if($id)
        {
            // Get the file URL from the attachment ID.
            $url = wp_get_attachment_url($id);
        }
        else
        {
            $url = get_theme_mod('external_header_video');
        }

        $url = apply_filters('get_header_video_url', $url);

        if(! $id && ! $url)
        {
            return false;
        }

        return sanitize_url(set_url_scheme($url));
    }

    function the_header_video_url()
    {
        $video = get_header_video_url();

        if($video)
        {
            echo esc_url($video);
        }
    }

    function get_header_video_settings()
    {
        $header = get_custom_header();
        $video_url = get_header_video_url();
        $video_type = wp_check_filetype($video_url, wp_get_mime_types());

        $settings = [
            'mimeType' => '',
            'posterUrl' => get_header_image(),
            'videoUrl' => $video_url,
            'width' => absint($header->width),
            'height' => absint($header->height),
            'minWidth' => 900,
            'minHeight' => 500,
            'l10n' => [
                'pause' => __('Pause'),
                'play' => __('Play'),
                'pauseSpeak' => __('Video is paused.'),
                'playSpeak' => __('Video is playing.'),
            ],
        ];

        if(preg_match('#^https?://(?:www\.)?(?:youtube\.com/watch|youtu\.be/)#', $video_url))
        {
            $settings['mimeType'] = 'video/x-youtube';
        }
        elseif(! empty($video_type['type']))
        {
            $settings['mimeType'] = $video_type['type'];
        }

        return apply_filters('header_video_settings', $settings);
    }

    function has_custom_header()
    {
        return has_header_image() || (has_header_video() && is_header_video_active());
    }

    function is_header_video_active()
    {
        if(! get_theme_support('custom-header', 'video'))
        {
            return false;
        }

        $video_active_cb = get_theme_support('custom-header', 'video-active-callback');

        if(empty($video_active_cb) || ! is_callable($video_active_cb))
        {
            $show_video = true;
        }
        else
        {
            $show_video = call_user_func($video_active_cb);
        }

        return apply_filters('is_header_video_active', $show_video);
    }

    function get_custom_header_markup()
    {
        if(! has_custom_header() && ! is_customize_preview())
        {
            return '';
        }

        return sprintf('<div id="wp-custom-header" class="wp-custom-header">%s</div>', get_header_image_tag());
    }

    function the_custom_header_markup()
    {
        $custom_header = get_custom_header_markup();
        if(empty($custom_header))
        {
            return;
        }

        echo $custom_header;

        if(is_header_video_active() && (has_header_video() || is_customize_preview()))
        {
            wp_enqueue_script('wp-custom-header');
            wp_localize_script('wp-custom-header', '_wpCustomHeaderSettings', get_header_video_settings());
        }
    }

    function get_background_image()
    {
        return get_theme_mod('background_image', get_theme_support('custom-background', 'default-image'));
    }

    function background_image()
    {
        echo get_background_image();
    }

    function get_background_color()
    {
        return get_theme_mod('background_color', get_theme_support('custom-background', 'default-color'));
    }

    function background_color()
    {
        echo get_background_color();
    }

    function _custom_background_cb()
    {
        // $background is the saved custom image, or the default image.
        $background = set_url_scheme(get_background_image());

        /*
	 * $color is the saved custom color.
	 * A default has to be specified in style.css. It will not be printed here.
	 */
        $color = get_background_color();

        if(get_theme_support('custom-background', 'default-color') === $color)
        {
            $color = false;
        }

        $type_attr = current_theme_supports('html5', 'style') ? '' : ' type="text/css"';

        if(! $background && ! $color)
        {
            if(is_customize_preview())
            {
                printf('<style%s id="custom-background-css"></style>', $type_attr);
            }

            return;
        }

        $style = $color ? "background-color: #$color;" : '';

        if($background)
        {
            $image = ' background-image: url("'.sanitize_url($background).'");';

            // Background Position.
            $position_x = get_theme_mod('background_position_x', get_theme_support('custom-background', 'default-position-x'));
            $position_y = get_theme_mod('background_position_y', get_theme_support('custom-background', 'default-position-y'));

            if(! in_array($position_x, ['left', 'center', 'right'], true))
            {
                $position_x = 'left';
            }

            if(! in_array($position_y, ['top', 'center', 'bottom'], true))
            {
                $position_y = 'top';
            }

            $position = " background-position: $position_x $position_y;";

            // Background Size.
            $size = get_theme_mod('background_size', get_theme_support('custom-background', 'default-size'));

            if(! in_array($size, ['auto', 'contain', 'cover'], true))
            {
                $size = 'auto';
            }

            $size = " background-size: $size;";

            // Background Repeat.
            $repeat = get_theme_mod('background_repeat', get_theme_support('custom-background', 'default-repeat'));

            if(! in_array($repeat, ['repeat-x', 'repeat-y', 'repeat', 'no-repeat'], true))
            {
                $repeat = 'repeat';
            }

            $repeat = " background-repeat: $repeat;";

            // Background Scroll.
            $attachment = get_theme_mod('background_attachment', get_theme_support('custom-background', 'default-attachment'));

            if('fixed' !== $attachment)
            {
                $attachment = 'scroll';
            }

            $attachment = " background-attachment: $attachment;";

            $style .= $image.$position.$size.$repeat.$attachment;
        }
        ?>
        <style<?php echo $type_attr; ?> id="custom-background-css">
            body.custom-background {
            <?php echo trim( $style ); ?>
            }
        </style>
        <?php
    }

    function wp_custom_css_cb()
    {
        $styles = wp_get_custom_css();
        if($styles || is_customize_preview()) :
            $type_attr = current_theme_supports('html5', 'style') ? '' : ' type="text/css"';
            ?>
            <style<?php echo $type_attr; ?> id="wp-custom-css">
                <?php
			// Note that esc_html() cannot be used because `div &gt; span` is not interpreted properly.
			echo strip_tags( $styles );
			?>
            </style>
        <?php
        endif;
    }

    function wp_get_custom_css_post($stylesheet = '')
    {
        if(empty($stylesheet))
        {
            $stylesheet = get_stylesheet();
        }

        $custom_css_query_vars = [
            'post_type' => 'custom_css',
            'post_status' => get_post_stati(),
            'name' => sanitize_title($stylesheet),
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'cache_results' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'lazy_load_term_meta' => false,
        ];

        $post = null;
        if(get_stylesheet() === $stylesheet)
        {
            $post_id = get_theme_mod('custom_css_post_id');

            if($post_id > 0 && get_post($post_id))
            {
                $post = get_post($post_id);
            }

            // `-1` indicates no post exists; no query necessary.
            if(! $post && -1 !== $post_id)
            {
                $query = new WP_Query($custom_css_query_vars);
                $post = $query->post;
                /*
			 * Cache the lookup. See wp_update_custom_css_post().
			 * @todo This should get cleared if a custom_css post is added/removed.
			 */
                set_theme_mod('custom_css_post_id', $post ? $post->ID : -1);
            }
        }
        else
        {
            $query = new WP_Query($custom_css_query_vars);
            $post = $query->post;
        }

        return $post;
    }

    function wp_get_custom_css($stylesheet = '')
    {
        $css = '';

        if(empty($stylesheet))
        {
            $stylesheet = get_stylesheet();
        }

        $post = wp_get_custom_css_post($stylesheet);
        if($post)
        {
            $css = $post->post_content;
        }

        $css = apply_filters('wp_get_custom_css', $css, $stylesheet);

        return $css;
    }

    function wp_update_custom_css_post($css, $args = [])
    {
        $args = wp_parse_args($args, [
            'preprocessed' => '',
            'stylesheet' => get_stylesheet(),
        ]);

        $data = [
            'css' => $css,
            'preprocessed' => $args['preprocessed'],
        ];

        $data = apply_filters('update_custom_css_data', $data, array_merge($args, compact('css')));

        $post_data = [
            'post_title' => $args['stylesheet'],
            'post_name' => sanitize_title($args['stylesheet']),
            'post_type' => 'custom_css',
            'post_status' => 'publish',
            'post_content' => $data['css'],
            'post_content_filtered' => $data['preprocessed'],
        ];

        // Update post if it already exists, otherwise create a new one.
        $post = wp_get_custom_css_post($args['stylesheet']);
        if($post)
        {
            $post_data['ID'] = $post->ID;
            $r = wp_update_post(wp_slash($post_data), true);
        }
        else
        {
            $r = wp_insert_post(wp_slash($post_data), true);

            if(! is_wp_error($r))
            {
                if(get_stylesheet() === $args['stylesheet'])
                {
                    set_theme_mod('custom_css_post_id', $r);
                }

                // Trigger creation of a revision. This should be removed once #30854 is resolved.
                $revisions = wp_get_latest_revision_id_and_total_count($r);
                if(! is_wp_error($revisions) && 0 === $revisions['count'])
                {
                    wp_save_post_revision($r);
                }
            }
        }

        if(is_wp_error($r))
        {
            return $r;
        }

        return get_post($r);
    }

    function add_editor_style($stylesheet = 'editor-style.css')
    {
        global $editor_styles;

        add_theme_support('editor-style');

        $editor_styles = (array) $editor_styles;
        $stylesheet = (array) $stylesheet;

        if(is_rtl())
        {
            $rtl_stylesheet = str_replace('.css', '-rtl.css', $stylesheet[0]);
            $stylesheet[] = $rtl_stylesheet;
        }

        $editor_styles = array_merge($editor_styles, $stylesheet);
    }

    function remove_editor_styles()
    {
        if(! current_theme_supports('editor-style'))
        {
            return false;
        }
        _remove_theme_support('editor-style');
        if(is_admin())
        {
            $GLOBALS['editor_styles'] = [];
        }

        return true;
    }

    function get_editor_stylesheets()
    {
        $stylesheets = [];
        // Load editor_style.css if the active theme supports it.
        if(! empty($GLOBALS['editor_styles']) && is_array($GLOBALS['editor_styles']))
        {
            $editor_styles = $GLOBALS['editor_styles'];

            $editor_styles = array_unique(array_filter($editor_styles));
            $style_uri = get_stylesheet_directory_uri();
            $style_dir = get_stylesheet_directory();

            // Support externally referenced styles (like, say, fonts).
            foreach($editor_styles as $key => $file)
            {
                if(preg_match('~^(https?:)?//~', $file))
                {
                    $stylesheets[] = sanitize_url($file);
                    unset($editor_styles[$key]);
                }
            }

            // Look in a parent theme first, that way child theme CSS overrides.
            if(is_child_theme())
            {
                $template_uri = get_template_directory_uri();
                $template_dir = get_template_directory();

                foreach($editor_styles as $key => $file)
                {
                    if($file && file_exists("$template_dir/$file"))
                    {
                        $stylesheets[] = "$template_uri/$file";
                    }
                }
            }

            foreach($editor_styles as $file)
            {
                if($file && file_exists("$style_dir/$file"))
                {
                    $stylesheets[] = "$style_uri/$file";
                }
            }
        }

        return apply_filters('editor_stylesheets', $stylesheets);
    }

    function get_theme_starter_content()
    {
        $theme_support = get_theme_support('starter-content');
        if(is_array($theme_support) && ! empty($theme_support[0]) && is_array($theme_support[0]))
        {
            $config = $theme_support[0];
        }
        else
        {
            $config = [];
        }

        $core_content = [
            'widgets' => [
                'text_business_info' => [
                    'text',
                    [
                        'title' => _x('Find Us', 'Theme starter content'),
                        'text' => implode('', [
                            '<strong>'._x('Address', 'Theme starter content')."</strong>\n",
                            _x('123 Main Street', 'Theme starter content')."\n",
                            _x('New York, NY 10001', 'Theme starter content')."\n\n",
                            '<strong>'._x('Hours', 'Theme starter content')."</strong>\n",
                            _x('Monday&ndash;Friday: 9:00AM&ndash;5:00PM', 'Theme starter content')."\n",
                            _x('Saturday &amp; Sunday: 11:00AM&ndash;3:00PM', 'Theme starter content'),
                        ]),
                        'filter' => true,
                        'visual' => true,
                    ],
                ],
                'text_about' => [
                    'text',
                    [
                        'title' => _x('About This Site', 'Theme starter content'),
                        'text' => _x('This may be a good place to introduce yourself and your site or include some credits.', 'Theme starter content'),
                        'filter' => true,
                        'visual' => true,
                    ],
                ],
                'archives' => [
                    'archives',
                    [
                        'title' => _x('Archives', 'Theme starter content'),
                    ],
                ],
                'calendar' => [
                    'calendar',
                    [
                        'title' => _x('Calendar', 'Theme starter content'),
                    ],
                ],
                'categories' => [
                    'categories',
                    [
                        'title' => _x('Categories', 'Theme starter content'),
                    ],
                ],
                'meta' => [
                    'meta',
                    [
                        'title' => _x('Meta', 'Theme starter content'),
                    ],
                ],
                'recent-comments' => [
                    'recent-comments',
                    [
                        'title' => _x('Recent Comments', 'Theme starter content'),
                    ],
                ],
                'recent-posts' => [
                    'recent-posts',
                    [
                        'title' => _x('Recent Posts', 'Theme starter content'),
                    ],
                ],
                'search' => [
                    'search',
                    [
                        'title' => _x('Search', 'Theme starter content'),
                    ],
                ],
            ],
            'nav_menus' => [
                'link_home' => [
                    'type' => 'custom',
                    'title' => _x('Home', 'Theme starter content'),
                    'url' => home_url('/'),
                ],
                'page_home' => [ // Deprecated in favor of 'link_home'.
                    'type' => 'post_type',
                    'object' => 'page',
                    'object_id' => '{{home}}',
                ],
                'page_about' => [
                    'type' => 'post_type',
                    'object' => 'page',
                    'object_id' => '{{about}}',
                ],
                'page_blog' => [
                    'type' => 'post_type',
                    'object' => 'page',
                    'object_id' => '{{blog}}',
                ],
                'page_news' => [
                    'type' => 'post_type',
                    'object' => 'page',
                    'object_id' => '{{news}}',
                ],
                'page_contact' => [
                    'type' => 'post_type',
                    'object' => 'page',
                    'object_id' => '{{contact}}',
                ],

                'link_email' => [
                    'title' => _x('Email', 'Theme starter content'),
                    'url' => 'mailto:wordpress@example.com',
                ],
                'link_facebook' => [
                    'title' => _x('Facebook', 'Theme starter content'),
                    'url' => 'https://www.facebook.com/wordpress',
                ],
                'link_foursquare' => [
                    'title' => _x('Foursquare', 'Theme starter content'),
                    'url' => 'https://foursquare.com/',
                ],
                'link_github' => [
                    'title' => _x('GitHub', 'Theme starter content'),
                    'url' => 'https://github.com/wordpress/',
                ],
                'link_instagram' => [
                    'title' => _x('Instagram', 'Theme starter content'),
                    'url' => 'https://www.instagram.com/explore/tags/wordcamp/',
                ],
                'link_linkedin' => [
                    'title' => _x('LinkedIn', 'Theme starter content'),
                    'url' => 'https://www.linkedin.com/company/1089783',
                ],
                'link_pinterest' => [
                    'title' => _x('Pinterest', 'Theme starter content'),
                    'url' => 'https://www.pinterest.com/',
                ],
                'link_twitter' => [
                    'title' => _x('Twitter', 'Theme starter content'),
                    'url' => 'https://twitter.com/wordpress',
                ],
                'link_yelp' => [
                    'title' => _x('Yelp', 'Theme starter content'),
                    'url' => 'https://www.yelp.com',
                ],
                'link_youtube' => [
                    'title' => _x('YouTube', 'Theme starter content'),
                    'url' => 'https://www.youtube.com/channel/UCdof4Ju7amm1chz1gi1T2ZA',
                ],
            ],
            'posts' => [
                'home' => [
                    'post_type' => 'page',
                    'post_title' => _x('Home', 'Theme starter content'),
                    'post_content' => sprintf("<!-- wp:paragraph -->\n<p>%s</p>\n<!-- /wp:paragraph -->", _x('Welcome to your site! This is your homepage, which is what most visitors will see when they come to your site for the first time.', 'Theme starter content')),
                ],
                'about' => [
                    'post_type' => 'page',
                    'post_title' => _x('About', 'Theme starter content'),
                    'post_content' => sprintf("<!-- wp:paragraph -->\n<p>%s</p>\n<!-- /wp:paragraph -->", _x('You might be an artist who would like to introduce yourself and your work here or maybe you are a business with a mission to describe.', 'Theme starter content')),
                ],
                'contact' => [
                    'post_type' => 'page',
                    'post_title' => _x('Contact', 'Theme starter content'),
                    'post_content' => sprintf("<!-- wp:paragraph -->\n<p>%s</p>\n<!-- /wp:paragraph -->", _x('This is a page with some basic contact information, such as an address and phone number. You might also try a plugin to add a contact form.', 'Theme starter content')),
                ],
                'blog' => [
                    'post_type' => 'page',
                    'post_title' => _x('Blog', 'Theme starter content'),
                ],
                'news' => [
                    'post_type' => 'page',
                    'post_title' => _x('News', 'Theme starter content'),
                ],

                'homepage-section' => [
                    'post_type' => 'page',
                    'post_title' => _x('A homepage section', 'Theme starter content'),
                    'post_content' => sprintf("<!-- wp:paragraph -->\n<p>%s</p>\n<!-- /wp:paragraph -->", _x('This is an example of a homepage section. Homepage sections can be any page other than the homepage itself, including the page that shows your latest blog posts.', 'Theme starter content')),
                ],
            ],
        ];

        $content = [];

        foreach($config as $type => $args)
        {
            switch($type)
            {
                // Use options and theme_mods as-is.
                case 'options':
                case 'theme_mods':
                    $content[$type] = $config[$type];
                    break;

                // Widgets are grouped into sidebars.
                case 'widgets':
                    foreach($config[$type] as $sidebar_id => $widgets)
                    {
                        foreach($widgets as $id => $widget)
                        {
                            if(is_array($widget))
                            {
                                // Item extends core content.
                                if(! empty($core_content[$type][$id]))
                                {
                                    $widget = [
                                        $core_content[$type][$id][0],
                                        array_merge($core_content[$type][$id][1], $widget),
                                    ];
                                }

                                $content[$type][$sidebar_id][] = $widget;
                            }
                            elseif(is_string($widget) && ! empty($core_content[$type]) && ! empty($core_content[$type][$widget]))
                            {
                                $content[$type][$sidebar_id][] = $core_content[$type][$widget];
                            }
                        }
                    }
                    break;

                // And nav menu items are grouped into nav menus.
                case 'nav_menus':
                    foreach($config[$type] as $nav_menu_location => $nav_menu)
                    {
                        // Ensure nav menus get a name.
                        if(empty($nav_menu['name']))
                        {
                            $nav_menu['name'] = $nav_menu_location;
                        }

                        $content[$type][$nav_menu_location]['name'] = $nav_menu['name'];

                        foreach($nav_menu['items'] as $id => $nav_menu_item)
                        {
                            if(is_array($nav_menu_item))
                            {
                                // Item extends core content.
                                if(! empty($core_content[$type][$id]))
                                {
                                    $nav_menu_item = array_merge($core_content[$type][$id], $nav_menu_item);
                                }

                                $content[$type][$nav_menu_location]['items'][] = $nav_menu_item;
                            }
                            elseif(is_string($nav_menu_item) && ! empty($core_content[$type]) && ! empty($core_content[$type][$nav_menu_item]))
                            {
                                $content[$type][$nav_menu_location]['items'][] = $core_content[$type][$nav_menu_item];
                            }
                        }
                    }
                    break;

                // Attachments are posts but have special treatment.
                case 'attachments':
                    foreach($config[$type] as $id => $item)
                    {
                        if(! empty($item['file']))
                        {
                            $content[$type][$id] = $item;
                        }
                    }
                    break;

                /*
			 * All that's left now are posts (besides attachments).
			 * Not a default case for the sake of clarity and future work.
			 */ case 'posts':
                foreach($config[$type] as $id => $item)
                {
                    if(is_array($item))
                    {
                        // Item extends core content.
                        if(! empty($core_content[$type][$id]))
                        {
                            $item = array_merge($core_content[$type][$id], $item);
                        }

                        // Enforce a subset of fields.
                        $content[$type][$id] = wp_array_slice_assoc($item, [
                            'post_type',
                            'post_title',
                            'post_excerpt',
                            'post_name',
                            'post_content',
                            'menu_order',
                            'comment_status',
                            'thumbnail',
                            'template',
                        ]);
                    }
                    elseif(is_string($item) && ! empty($core_content[$type][$item]))
                    {
                        $content[$type][$item] = $core_content[$type][$item];
                    }
                }
                break;
            }
        }

        return apply_filters('get_theme_starter_content', $content, $config);
    }

    function add_theme_support($feature, ...$args)
    {
        global $_wp_theme_features;

        if(! $args)
        {
            $args = true;
        }

        switch($feature)
        {
            case 'post-thumbnails':
                // All post types are already supported.
                if(true === get_theme_support('post-thumbnails'))
                {
                    return;
                }

                /*
			 * Merge post types with any that already declared their support
			 * for post thumbnails.
			 */
                if(isset($args[0]) && is_array($args[0]) && isset($_wp_theme_features['post-thumbnails']))
                {
                    $args[0] = array_unique(array_merge($_wp_theme_features['post-thumbnails'][0], $args[0]));
                }

                break;

            case 'post-formats':
                if(isset($args[0]) && is_array($args[0]))
                {
                    $post_formats = get_post_format_slugs();
                    unset($post_formats['standard']);

                    $args[0] = array_intersect($args[0], array_keys($post_formats));
                }
                else
                {
                    _doing_it_wrong("add_theme_support( 'post-formats' )", __('You need to pass an array of post formats.'), '5.6.0');

                    return false;
                }
                break;

            case 'html5':
                // You can't just pass 'html5', you need to pass an array of types.
                if(empty($args[0]) || ! is_array($args[0]))
                {
                    _doing_it_wrong("add_theme_support( 'html5' )", __('You need to pass an array of types.'), '3.6.1');

                    if(! empty($args[0]) && ! is_array($args[0]))
                    {
                        return false;
                    }

                    // Build an array of types for back-compat.
                    $args = [0 => ['comment-list', 'comment-form', 'search-form']];
                }

                // Calling 'html5' again merges, rather than overwrites.
                if(isset($_wp_theme_features['html5']))
                {
                    $args[0] = array_merge($_wp_theme_features['html5'][0], $args[0]);
                }
                break;

            case 'custom-logo':
                if(true === $args)
                {
                    $args = [0 => []];
                }
                $defaults = [
                    'width' => null,
                    'height' => null,
                    'flex-width' => false,
                    'flex-height' => false,
                    'header-text' => '',
                    'unlink-homepage-logo' => false,
                ];
                $args[0] = wp_parse_args(array_intersect_key($args[0], $defaults), $defaults);

                // Allow full flexibility if no size is specified.
                if(is_null($args[0]['width']) && is_null($args[0]['height']))
                {
                    $args[0]['flex-width'] = true;
                    $args[0]['flex-height'] = true;
                }
                break;

            case 'custom-header-uploads':
                return add_theme_support('custom-header', ['uploads' => true]);

            case 'custom-header':
                if(true === $args)
                {
                    $args = [0 => []];
                }

                $defaults = [
                    'default-image' => '',
                    'random-default' => false,
                    'width' => 0,
                    'height' => 0,
                    'flex-height' => false,
                    'flex-width' => false,
                    'default-text-color' => '',
                    'header-text' => true,
                    'uploads' => true,
                    'wp-head-callback' => '',
                    'admin-head-callback' => '',
                    'admin-preview-callback' => '',
                    'video' => false,
                    'video-active-callback' => 'is_front_page',
                ];

                $jit = isset($args[0]['__jit']);
                unset($args[0]['__jit']);

                /*
			 * Merge in data from previous add_theme_support() calls.
			 * The first value registered wins. (A child theme is set up first.)
			 */
                if(isset($_wp_theme_features['custom-header']))
                {
                    $args[0] = wp_parse_args($_wp_theme_features['custom-header'][0], $args[0]);
                }

                /*
			 * Load in the defaults at the end, as we need to insure first one wins.
			 * This will cause all constants to be defined, as each arg will then be set to the default.
			 */
                if($jit)
                {
                    $args[0] = wp_parse_args($args[0], $defaults);
                }

                /*
			 * If a constant was defined, use that value. Otherwise, define the constant to ensure
			 * the constant is always accurate (and is not defined later,  overriding our value).
			 * As stated above, the first value wins.
			 * Once we get to wp_loaded (just-in-time), define any constants we haven't already.
			 * Constants are lame. Don't reference them. This is just for backward compatibility.
			 */

                if(defined('NO_HEADER_TEXT'))
                {
                    $args[0]['header-text'] = ! NO_HEADER_TEXT;
                }
                elseif(isset($args[0]['header-text']))
                {
                    define('NO_HEADER_TEXT', empty($args[0]['header-text']));
                }

                if(defined('HEADER_IMAGE_WIDTH'))
                {
                    $args[0]['width'] = (int) HEADER_IMAGE_WIDTH;
                }
                elseif(isset($args[0]['width']))
                {
                    define('HEADER_IMAGE_WIDTH', (int) $args[0]['width']);
                }

                if(defined('HEADER_IMAGE_HEIGHT'))
                {
                    $args[0]['height'] = (int) HEADER_IMAGE_HEIGHT;
                }
                elseif(isset($args[0]['height']))
                {
                    define('HEADER_IMAGE_HEIGHT', (int) $args[0]['height']);
                }

                if(defined('HEADER_TEXTCOLOR'))
                {
                    $args[0]['default-text-color'] = HEADER_TEXTCOLOR;
                }
                elseif(isset($args[0]['default-text-color']))
                {
                    define('HEADER_TEXTCOLOR', $args[0]['default-text-color']);
                }

                if(defined('HEADER_IMAGE'))
                {
                    $args[0]['default-image'] = HEADER_IMAGE;
                }
                elseif(isset($args[0]['default-image']))
                {
                    define('HEADER_IMAGE', $args[0]['default-image']);
                }

                if($jit && ! empty($args[0]['default-image']))
                {
                    $args[0]['random-default'] = false;
                }

                /*
			 * If headers are supported, and we still don't have a defined width or height,
			 * we have implicit flex sizes.
			 */
                if($jit)
                {
                    if(empty($args[0]['width']) && empty($args[0]['flex-width']))
                    {
                        $args[0]['flex-width'] = true;
                    }
                    if(empty($args[0]['height']) && empty($args[0]['flex-height']))
                    {
                        $args[0]['flex-height'] = true;
                    }
                }

                break;

            case 'custom-background':
                if(true === $args)
                {
                    $args = [0 => []];
                }

                $defaults = [
                    'default-image' => '',
                    'default-preset' => 'default',
                    'default-position-x' => 'left',
                    'default-position-y' => 'top',
                    'default-size' => 'auto',
                    'default-repeat' => 'repeat',
                    'default-attachment' => 'scroll',
                    'default-color' => '',
                    'wp-head-callback' => '_custom_background_cb',
                    'admin-head-callback' => '',
                    'admin-preview-callback' => '',
                ];

                $jit = isset($args[0]['__jit']);
                unset($args[0]['__jit']);

                // Merge in data from previous add_theme_support() calls. The first value registered wins.
                if(isset($_wp_theme_features['custom-background']))
                {
                    $args[0] = wp_parse_args($_wp_theme_features['custom-background'][0], $args[0]);
                }

                if($jit)
                {
                    $args[0] = wp_parse_args($args[0], $defaults);
                }

                if(defined('BACKGROUND_COLOR'))
                {
                    $args[0]['default-color'] = BACKGROUND_COLOR;
                }
                elseif(isset($args[0]['default-color']) || $jit)
                {
                    define('BACKGROUND_COLOR', $args[0]['default-color']);
                }

                if(defined('BACKGROUND_IMAGE'))
                {
                    $args[0]['default-image'] = BACKGROUND_IMAGE;
                }
                elseif(isset($args[0]['default-image']) || $jit)
                {
                    define('BACKGROUND_IMAGE', $args[0]['default-image']);
                }

                break;

            // Ensure that 'title-tag' is accessible in the admin.
            case 'title-tag':
                // Can be called in functions.php but must happen before wp_loaded, i.e. not in header.php.
                if(did_action('wp_loaded'))
                {
                    _doing_it_wrong("add_theme_support( 'title-tag' )", sprintf(/* translators: 1: title-tag, 2: wp_loaded */ __('Theme support for %1$s should be registered before the %2$s hook.'), '<code>title-tag</code>', '<code>wp_loaded</code>'), '4.1.0');

                    return false;
                }
        }

        $_wp_theme_features[$feature] = $args;
    }

    function _custom_header_background_just_in_time()
    {
        global $custom_image_header, $custom_background;

        if(current_theme_supports('custom-header'))
        {
            // In case any constants were defined after an add_custom_image_header() call, re-run.
            add_theme_support('custom-header', ['__jit' => true]);

            $args = get_theme_support('custom-header');
            if($args[0]['wp-head-callback'])
            {
                add_action('wp_head', $args[0]['wp-head-callback']);
            }

            if(is_admin())
            {
                require_once ABSPATH.'wp-admin/includes/class-custom-image-header.php';
                $custom_image_header = new Custom_Image_Header($args[0]['admin-head-callback'], $args[0]['admin-preview-callback']);
            }
        }

        if(current_theme_supports('custom-background'))
        {
            // In case any constants were defined after an add_custom_background() call, re-run.
            add_theme_support('custom-background', ['__jit' => true]);

            $args = get_theme_support('custom-background');
            add_action('wp_head', $args[0]['wp-head-callback']);

            if(is_admin())
            {
                require_once ABSPATH.'wp-admin/includes/class-custom-background.php';
                $custom_background = new Custom_Background($args[0]['admin-head-callback'], $args[0]['admin-preview-callback']);
            }
        }
    }

    function _custom_logo_header_styles()
    {
        if(! current_theme_supports('custom-header', 'header-text') && get_theme_support('custom-logo', 'header-text') && ! get_theme_mod('header_text', true))
        {
            $classes = (array) get_theme_support('custom-logo', 'header-text');
            $classes = array_map('sanitize_html_class', $classes);
            $classes = '.'.implode(', .', $classes);

            $type_attr = current_theme_supports('html5', 'style') ? '' : ' type="text/css"';
            ?>
            <!-- Custom Logo: hide header text -->
            <style id="custom-logo-css"<?php echo $type_attr; ?>>
                <?php echo $classes; ?>
                {
                    position: absolute
                ;
                    clip: rect(1px, 1px, 1px, 1px)
                ;
                }
            </style>
            <?php
        }
    }

    function get_theme_support($feature, ...$args)
    {
        global $_wp_theme_features;

        if(! isset($_wp_theme_features[$feature]))
        {
            return false;
        }

        if(! $args)
        {
            return $_wp_theme_features[$feature];
        }

        switch($feature)
        {
            case 'custom-logo':
            case 'custom-header':
            case 'custom-background':
                if(isset($_wp_theme_features[$feature][0][$args[0]]))
                {
                    return $_wp_theme_features[$feature][0][$args[0]];
                }

                return false;

            default:
                return $_wp_theme_features[$feature];
        }
    }

    function remove_theme_support($feature)
    {
        // Do not remove internal registrations that are not used directly by themes.
        if(in_array($feature, ['editor-style', 'widgets', 'menus'], true))
        {
            return false;
        }

        return _remove_theme_support($feature);
    }

    function _remove_theme_support($feature)
    {
        global $_wp_theme_features;

        switch($feature)
        {
            case 'custom-header-uploads':
                if(! isset($_wp_theme_features['custom-header']))
                {
                    return false;
                }
                add_theme_support('custom-header', ['uploads' => false]);

                return; // Do not continue - custom-header-uploads no longer exists.
        }

        if(! isset($_wp_theme_features[$feature]))
        {
            return false;
        }

        switch($feature)
        {
            case 'custom-header':
                if(! did_action('wp_loaded'))
                {
                    break;
                }
                $support = get_theme_support('custom-header');
                if(isset($support[0]['wp-head-callback']))
                {
                    remove_action('wp_head', $support[0]['wp-head-callback']);
                }
                if(isset($GLOBALS['custom_image_header']))
                {
                    remove_action('admin_menu', [$GLOBALS['custom_image_header'], 'init']);
                    unset($GLOBALS['custom_image_header']);
                }
                break;

            case 'custom-background':
                if(! did_action('wp_loaded'))
                {
                    break;
                }
                $support = get_theme_support('custom-background');
                if(isset($support[0]['wp-head-callback']))
                {
                    remove_action('wp_head', $support[0]['wp-head-callback']);
                }
                remove_action('admin_menu', [$GLOBALS['custom_background'], 'init']);
                unset($GLOBALS['custom_background']);
                break;
        }

        unset($_wp_theme_features[$feature]);

        return true;
    }

    function current_theme_supports($feature, ...$args)
    {
        global $_wp_theme_features;

        if('custom-header-uploads' === $feature)
        {
            return current_theme_supports('custom-header', 'uploads');
        }

        if(! isset($_wp_theme_features[$feature]))
        {
            return false;
        }

        // If no args passed then no extra checks need to be performed.
        if(! $args)
        {
            return apply_filters("current_theme_supports-{$feature}", true, $args, $_wp_theme_features[$feature]); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
        }

        switch($feature)
        {
            case 'post-thumbnails':
                /*
			 * post-thumbnails can be registered for only certain content/post types
			 * by passing an array of types to add_theme_support().
			 * If no array was passed, then any type is accepted.
			 */ if(true === $_wp_theme_features[$feature])
            {  // Registered for all types.
                return true;
            }
                $content_type = $args[0];

                return in_array($content_type, $_wp_theme_features[$feature][0], true);

            case 'html5':
            case 'post-formats':
                /*
			 * Specific post formats can be registered by passing an array of types
			 * to add_theme_support().
			 *
			 * Specific areas of HTML5 support *must* be passed via an array to add_theme_support().
			 */ $type = $args[0];

                return in_array($type, $_wp_theme_features[$feature][0], true);

            case 'custom-logo':
            case 'custom-header':
            case 'custom-background':
                // Specific capabilities can be registered by passing an array to add_theme_support().
                return (isset($_wp_theme_features[$feature][0][$args[0]]) && $_wp_theme_features[$feature][0][$args[0]]);
        }

        return apply_filters("current_theme_supports-{$feature}", true, $args, $_wp_theme_features[$feature]); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
    }

    function require_if_theme_supports($feature, $file)
    {
        if(current_theme_supports($feature))
        {
            require $file;

            return true;
        }

        return false;
    }

    function register_theme_feature($feature, $args = [])
    {
        global $_wp_registered_theme_features;

        if(! is_array($_wp_registered_theme_features))
        {
            $_wp_registered_theme_features = [];
        }

        $defaults = [
            'type' => 'boolean',
            'variadic' => false,
            'description' => '',
            'show_in_rest' => false,
        ];

        $args = wp_parse_args($args, $defaults);

        if(true === $args['show_in_rest'])
        {
            $args['show_in_rest'] = [];
        }

        if(is_array($args['show_in_rest']))
        {
            $args['show_in_rest'] = wp_parse_args($args['show_in_rest'], [
                'schema' => [],
                'name' => $feature,
                'prepare_callback' => null,
            ]);
        }

        if(! in_array($args['type'], ['string', 'boolean', 'integer', 'number', 'array', 'object'], true))
        {
            return new WP_Error('invalid_type', __('The feature "type" is not valid JSON Schema type.'));
        }

        if(true === $args['variadic'] && 'array' !== $args['type'])
        {
            return new WP_Error('variadic_must_be_array', __('When registering a "variadic" theme feature, the "type" must be an "array".'));
        }

        if(false !== $args['show_in_rest'] && in_array($args['type'], ['array', 'object'], true))
        {
            if(! is_array($args['show_in_rest']) || empty($args['show_in_rest']['schema']))
            {
                return new WP_Error('missing_schema', __('When registering an "array" or "object" feature to show in the REST API, the feature\'s schema must also be defined.'));
            }

            if('array' === $args['type'] && ! isset($args['show_in_rest']['schema']['items']))
            {
                return new WP_Error('missing_schema_items', __('When registering an "array" feature, the feature\'s schema must include the "items" keyword.'));
            }

            if('object' === $args['type'] && ! isset($args['show_in_rest']['schema']['properties']))
            {
                return new WP_Error('missing_schema_properties', __('When registering an "object" feature, the feature\'s schema must include the "properties" keyword.'));
            }
        }

        if(is_array($args['show_in_rest']))
        {
            if(isset($args['show_in_rest']['prepare_callback']) && ! is_callable($args['show_in_rest']['prepare_callback']))
            {
                return new WP_Error('invalid_rest_prepare_callback', sprintf(/* translators: %s: prepare_callback */ __('The "%s" must be a callable function.'), 'prepare_callback'));
            }

            $args['show_in_rest']['schema'] = wp_parse_args($args['show_in_rest']['schema'], [
                'description' => $args['description'],
                'type' => $args['type'],
                'default' => false,
            ]);

            if(is_bool($args['show_in_rest']['schema']['default']) && ! in_array('boolean', (array) $args['show_in_rest']['schema']['type'], true))
            {
                // Automatically include the "boolean" type when the default value is a boolean.
                $args['show_in_rest']['schema']['type'] = (array) $args['show_in_rest']['schema']['type'];
                array_unshift($args['show_in_rest']['schema']['type'], 'boolean');
            }

            $args['show_in_rest']['schema'] = rest_default_additional_properties_to_false($args['show_in_rest']['schema']);
        }

        $_wp_registered_theme_features[$feature] = $args;

        return true;
    }

    function get_registered_theme_features()
    {
        global $_wp_registered_theme_features;

        if(! is_array($_wp_registered_theme_features))
        {
            return [];
        }

        return $_wp_registered_theme_features;
    }

    function get_registered_theme_feature($feature)
    {
        global $_wp_registered_theme_features;

        if(! is_array($_wp_registered_theme_features))
        {
            return null;
        }

        if(isset($_wp_registered_theme_features[$feature]))
        {
            return $_wp_registered_theme_features[$feature];
        }

        return null;
    }

    function _delete_attachment_theme_mod($id)
    {
        $attachment_image = wp_get_attachment_url($id);
        $header_image = get_header_image();
        $background_image = get_background_image();
        $custom_logo_id = get_theme_mod('custom_logo');

        if($custom_logo_id && $custom_logo_id == $id)
        {
            remove_theme_mod('custom_logo');
            remove_theme_mod('header_text');
        }

        if($header_image && $header_image == $attachment_image)
        {
            remove_theme_mod('header_image');
            remove_theme_mod('header_image_data');
        }

        if($background_image && $background_image == $attachment_image)
        {
            remove_theme_mod('background_image');
        }
    }

    function check_theme_switched()
    {
        $stylesheet = get_option('theme_switched');

        if($stylesheet)
        {
            $old_theme = wp_get_theme($stylesheet);

            // Prevent widget & menu mapping from running since Customizer already called it up front.
            if(get_option('theme_switched_via_customizer'))
            {
                remove_action('after_switch_theme', '_wp_menus_changed');
                remove_action('after_switch_theme', '_wp_sidebars_changed');
                update_option('theme_switched_via_customizer', false);
            }

            if($old_theme->exists())
            {
                do_action('after_switch_theme', $old_theme->get('Name'), $old_theme);
            }
            else
            {
                do_action('after_switch_theme', $stylesheet, $old_theme);
            }

            flush_rewrite_rules();

            update_option('theme_switched', false);
        }
    }

    function _wp_customize_include()
    {
        $is_customize_admin_page = (is_admin() && 'customize.php' === basename($_SERVER['PHP_SELF']));
        $should_include = ($is_customize_admin_page || (isset($_REQUEST['wp_customize']) && 'on' === $_REQUEST['wp_customize']) || (! empty($_GET['customize_changeset_uuid']) || ! empty($_POST['customize_changeset_uuid'])));

        if(! $should_include)
        {
            return;
        }

        /*
	 * Note that wp_unslash() is not being used on the input vars because it is
	 * called before wp_magic_quotes() gets called. Besides this fact, none of
	 * the values should contain any characters needing slashes anyway.
	 */
        $keys = [
            'changeset_uuid',
            'customize_changeset_uuid',
            'customize_theme',
            'theme',
            'customize_messenger_channel',
            'customize_autosaved',
        ];
        $input_vars = array_merge(wp_array_slice_assoc($_GET, $keys), wp_array_slice_assoc($_POST, $keys));

        $theme = null;
        $autosaved = null;
        $messenger_channel = null;

        /*
	 * Value false indicates UUID should be determined after_setup_theme
	 * to either re-use existing saved changeset or else generate a new UUID if none exists.
	 */
        $changeset_uuid = false;

        /*
	 * Set initially fo false since defaults to true for back-compat;
	 * can be overridden via the customize_changeset_branching filter.
	 */
        $branching = false;

        if($is_customize_admin_page && isset($input_vars['changeset_uuid']))
        {
            $changeset_uuid = sanitize_key($input_vars['changeset_uuid']);
        }
        elseif(! empty($input_vars['customize_changeset_uuid']))
        {
            $changeset_uuid = sanitize_key($input_vars['customize_changeset_uuid']);
        }

        // Note that theme will be sanitized via WP_Theme.
        if($is_customize_admin_page && isset($input_vars['theme']))
        {
            $theme = $input_vars['theme'];
        }
        elseif(isset($input_vars['customize_theme']))
        {
            $theme = $input_vars['customize_theme'];
        }

        if(! empty($input_vars['customize_autosaved']))
        {
            $autosaved = true;
        }

        if(isset($input_vars['customize_messenger_channel']))
        {
            $messenger_channel = sanitize_key($input_vars['customize_messenger_channel']);
        }

        /*
	 * Note that settings must be previewed even outside the customizer preview
	 * and also in the customizer pane itself. This is to enable loading an existing
	 * changeset into the customizer. Previewing the settings only has to be prevented
	 * here in the case of a customize_save action because this will cause WP to think
	 * there is nothing changed that needs to be saved.
	 */
        $is_customize_save_action = (wp_doing_ajax() && isset($_REQUEST['action']) && 'customize_save' === wp_unslash($_REQUEST['action']));
        $settings_previewed = ! $is_customize_save_action;

        require_once ABSPATH.WPINC.'/class-wp-customize-manager.php';
        $GLOBALS['wp_customize'] = new WP_Customize_Manager(compact('changeset_uuid', 'theme', 'messenger_channel', 'settings_previewed', 'autosaved', 'branching'));
    }

    function _wp_customize_publish_changeset($new_status, $old_status, $changeset_post)
    {
        global $wp_customize, $wpdb;

        $is_publishing_changeset = ('customize_changeset' === $changeset_post->post_type && 'publish' === $new_status && 'publish' !== $old_status);
        if(! $is_publishing_changeset)
        {
            return;
        }

        if(empty($wp_customize))
        {
            require_once ABSPATH.WPINC.'/class-wp-customize-manager.php';
            $wp_customize = new WP_Customize_Manager([
                                                         'changeset_uuid' => $changeset_post->post_name,
                                                         'settings_previewed' => false,
                                                     ]);
        }

        if(! did_action('customize_register'))
        {
            /*
		 * When running from CLI or Cron, the customize_register action will need
		 * to be triggered in order for core, themes, and plugins to register their
		 * settings. Normally core will add_action( 'customize_register' ) at
		 * priority 10 to register the core settings, and if any themes/plugins
		 * also add_action( 'customize_register' ) at the same priority, they
		 * will have a $wp_customize with those settings registered since they
		 * call add_action() afterward, normally. However, when manually doing
		 * the customize_register action after the setup_theme, then the order
		 * will be reversed for two actions added at priority 10, resulting in
		 * the core settings no longer being available as expected to themes/plugins.
		 * So the following manually calls the method that registers the core
		 * settings up front before doing the action.
		 */
            remove_action('customize_register', [$wp_customize, 'register_controls']);
            $wp_customize->register_controls();

            do_action('customize_register', $wp_customize);
        }
        $wp_customize->_publish_changeset_values($changeset_post->ID);

        /*
	 * Trash the changeset post if revisions are not enabled. Unpublished
	 * changesets by default get garbage collected due to the auto-draft status.
	 * When a changeset post is published, however, it would no longer get cleaned
	 * out. This is a problem when the changeset posts are never displayed anywhere,
	 * since they would just be endlessly piling up. So here we use the revisions
	 * feature to indicate whether or not a published changeset should get trashed
	 * and thus garbage collected.
	 */
        if(! wp_revisions_enabled($changeset_post))
        {
            $wp_customize->trash_changeset_post($changeset_post->ID);
        }
    }

    function _wp_customize_changeset_filter_insert_post_data($post_data, $supplied_post_data)
    {
        if(isset($post_data['post_type']) && 'customize_changeset' === $post_data['post_type'] && empty($post_data['post_name']) && ! empty($supplied_post_data['post_name']))
        {
            $post_data['post_name'] = $supplied_post_data['post_name'];
        }

        return $post_data;
    }

    function _wp_customize_loader_settings()
    {
        $admin_origin = parse_url(admin_url());
        $home_origin = parse_url(home_url());
        $cross_domain = (strtolower($admin_origin['host']) !== strtolower($home_origin['host']));

        $browser = [
            'mobile' => wp_is_mobile(),
            'ios' => wp_is_mobile() && preg_match('/iPad|iPod|iPhone/', $_SERVER['HTTP_USER_AGENT']),
        ];

        $settings = [
            'url' => esc_url(admin_url('customize.php')),
            'isCrossDomain' => $cross_domain,
            'browser' => $browser,
            'l10n' => [
                'saveAlert' => __('The changes you made will be lost if you navigate away from this page.'),
                'mainIframeTitle' => __('Customizer'),
            ],
        ];

        $script = 'var _wpCustomizeLoaderSettings = '.wp_json_encode($settings).';';

        $wp_scripts = wp_scripts();
        $data = $wp_scripts->get_data('customize-loader', 'data');
        if($data)
        {
            $script = "$data\n$script";
        }

        $wp_scripts->add_data('customize-loader', 'data', $script);
    }

    function wp_customize_url($stylesheet = '')
    {
        $url = admin_url('customize.php');
        if($stylesheet)
        {
            $url .= '?theme='.urlencode($stylesheet);
        }

        return esc_url($url);
    }

    function wp_customize_support_script()
    {
        $admin_origin = parse_url(admin_url());
        $home_origin = parse_url(home_url());
        $cross_domain = (strtolower($admin_origin['host']) !== strtolower($home_origin['host']));
        $type_attr = current_theme_supports('html5', 'script') ? '' : ' type="text/javascript"';
        ?>
        <script<?php echo $type_attr; ?>>
            (function () {
                var request, b = document.body, c = 'className', cs = 'customize-support',
                    rcs = new RegExp('(^|\\s+)(no-)?' + cs + '(\\s+|$)');

                <?php    if ( $cross_domain ) : ?>
                request = (function () {
                    var xhr = new XMLHttpRequest();
                    return ('withCredentials' in xhr);
                })();
                <?php    else : ?>
                request = true;
                <?php    endif; ?>

                b[c] = b[c].replace(rcs, ' ');
                // The customizer requires postMessage and CORS (if the site is cross domain).
                b[c] += (window.postMessage && request ? ' ' : ' no-') + cs;
            }());
        </script>
        <?php
    }

    function is_customize_preview()
    {
        global $wp_customize;

        return ($wp_customize instanceof WP_Customize_Manager) && $wp_customize->is_preview();
    }

    function _wp_keep_alive_customize_changeset_dependent_auto_drafts($new_status, $old_status, $post)
    {
        global $wpdb;
        unset($old_status);

        // Short-circuit if not a changeset or if the changeset was published.
        if('customize_changeset' !== $post->post_type || 'publish' === $new_status)
        {
            return;
        }

        $data = json_decode($post->post_content, true);
        if(empty($data['nav_menus_created_posts']['value']))
        {
            return;
        }

        /*
	 * Actually, in lieu of keeping alive, trash any customization drafts here if the changeset itself is
	 * getting trashed. This is needed because when a changeset transitions to a draft, then any of the
	 * dependent auto-draft post/page stubs will also get transitioned to customization drafts which
	 * are then visible in the WP Admin. We cannot wait for the deletion of the changeset in which
	 * _wp_delete_customize_changeset_dependent_auto_drafts() will be called, since they need to be
	 * trashed to remove from visibility immediately.
	 */
        if('trash' === $new_status)
        {
            foreach($data['nav_menus_created_posts']['value'] as $post_id)
            {
                if(! empty($post_id) && 'draft' === get_post_status($post_id))
                {
                    wp_trash_post($post_id);
                }
            }

            return;
        }

        $post_args = [];
        if('auto-draft' === $new_status)
        {
            /*
		 * Keep the post date for the post matching the changeset
		 * so that it will not be garbage-collected before the changeset.
		 */
            $post_args['post_date'] = $post->post_date; // Note wp_delete_auto_drafts() only looks at this date.
        }
        else
        {
            /*
		 * Since the changeset no longer has an auto-draft (and it is not published)
		 * it is now a persistent changeset, a long-lived draft, and so any
		 * associated auto-draft posts should likewise transition into having a draft
		 * status. These drafts will be treated differently than regular drafts in
		 * that they will be tied to the given changeset. The publish meta box is
		 * replaced with a notice about how the post is part of a set of customized changes
		 * which will be published when the changeset is published.
		 */
            $post_args['post_status'] = 'draft';
        }

        foreach($data['nav_menus_created_posts']['value'] as $post_id)
        {
            if(empty($post_id) || 'auto-draft' !== get_post_status($post_id))
            {
                continue;
            }
            $wpdb->update($wpdb->posts, $post_args, ['ID' => $post_id]);
            clean_post_cache($post_id);
        }
    }

    function create_initial_theme_features()
    {
        register_theme_feature('align-wide', [
            'description' => __('Whether theme opts in to wide alignment CSS class.'),
            'show_in_rest' => true,
        ]);
        register_theme_feature('automatic-feed-links', [
            'description' => __('Whether posts and comments RSS feed links are added to head.'),
            'show_in_rest' => true,
        ]);
        register_theme_feature('block-templates', [
            'description' => __('Whether a theme uses block-based templates.'),
            'show_in_rest' => true,
        ]);
        register_theme_feature('block-template-parts', [
            'description' => __('Whether a theme uses block-based template parts.'),
            'show_in_rest' => true,
        ]);
        register_theme_feature('custom-background', [
            'description' => __('Custom background if defined by the theme.'),
            'type' => 'object',
            'show_in_rest' => [
                'schema' => [
                    'properties' => [
                        'default-image' => [
                            'type' => 'string',
                            'format' => 'uri',
                        ],
                        'default-preset' => [
                            'type' => 'string',
                            'enum' => [
                                'default',
                                'fill',
                                'fit',
                                'repeat',
                                'custom',
                            ],
                        ],
                        'default-position-x' => [
                            'type' => 'string',
                            'enum' => [
                                'left',
                                'center',
                                'right',
                            ],
                        ],
                        'default-position-y' => [
                            'type' => 'string',
                            'enum' => [
                                'left',
                                'center',
                                'right',
                            ],
                        ],
                        'default-size' => [
                            'type' => 'string',
                            'enum' => [
                                'auto',
                                'contain',
                                'cover',
                            ],
                        ],
                        'default-repeat' => [
                            'type' => 'string',
                            'enum' => [
                                'repeat-x',
                                'repeat-y',
                                'repeat',
                                'no-repeat',
                            ],
                        ],
                        'default-attachment' => [
                            'type' => 'string',
                            'enum' => [
                                'scroll',
                                'fixed',
                            ],
                        ],
                        'default-color' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ]);
        register_theme_feature('custom-header', [
            'description' => __('Custom header if defined by the theme.'),
            'type' => 'object',
            'show_in_rest' => [
                'schema' => [
                    'properties' => [
                        'default-image' => [
                            'type' => 'string',
                            'format' => 'uri',
                        ],
                        'random-default' => [
                            'type' => 'boolean',
                        ],
                        'width' => [
                            'type' => 'integer',
                        ],
                        'height' => [
                            'type' => 'integer',
                        ],
                        'flex-height' => [
                            'type' => 'boolean',
                        ],
                        'flex-width' => [
                            'type' => 'boolean',
                        ],
                        'default-text-color' => [
                            'type' => 'string',
                        ],
                        'header-text' => [
                            'type' => 'boolean',
                        ],
                        'uploads' => [
                            'type' => 'boolean',
                        ],
                        'video' => [
                            'type' => 'boolean',
                        ],
                    ],
                ],
            ],
        ]);
        register_theme_feature('custom-logo', [
            'type' => 'object',
            'description' => __('Custom logo if defined by the theme.'),
            'show_in_rest' => [
                'schema' => [
                    'properties' => [
                        'width' => [
                            'type' => 'integer',
                        ],
                        'height' => [
                            'type' => 'integer',
                        ],
                        'flex-width' => [
                            'type' => 'boolean',
                        ],
                        'flex-height' => [
                            'type' => 'boolean',
                        ],
                        'header-text' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                            ],
                        ],
                        'unlink-homepage-logo' => [
                            'type' => 'boolean',
                        ],
                    ],
                ],
            ],
        ]);
        register_theme_feature('customize-selective-refresh-widgets', [
            'description' => __('Whether the theme enables Selective Refresh for Widgets being managed with the Customizer.'),
            'show_in_rest' => true,
        ]);
        register_theme_feature('dark-editor-style', [
            'description' => __('Whether theme opts in to the dark editor style UI.'),
            'show_in_rest' => true,
        ]);
        register_theme_feature('disable-custom-colors', [
            'description' => __('Whether the theme disables custom colors.'),
            'show_in_rest' => true,
        ]);
        register_theme_feature('disable-custom-font-sizes', [
            'description' => __('Whether the theme disables custom font sizes.'),
            'show_in_rest' => true,
        ]);
        register_theme_feature('disable-custom-gradients', [
            'description' => __('Whether the theme disables custom gradients.'),
            'show_in_rest' => true,
        ]);
        register_theme_feature('disable-layout-styles', [
            'description' => __('Whether the theme disables generated layout styles.'),
            'show_in_rest' => true,
        ]);
        register_theme_feature('editor-color-palette', [
            'type' => 'array',
            'description' => __('Custom color palette if defined by the theme.'),
            'show_in_rest' => [
                'schema' => [
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                            ],
                            'slug' => [
                                'type' => 'string',
                            ],
                            'color' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        register_theme_feature('editor-font-sizes', [
            'type' => 'array',
            'description' => __('Custom font sizes if defined by the theme.'),
            'show_in_rest' => [
                'schema' => [
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                            ],
                            'size' => [
                                'type' => 'number',
                            ],
                            'slug' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        register_theme_feature('editor-gradient-presets', [
            'type' => 'array',
            'description' => __('Custom gradient presets if defined by the theme.'),
            'show_in_rest' => [
                'schema' => [
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                            ],
                            'gradient' => [
                                'type' => 'string',
                            ],
                            'slug' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        register_theme_feature('editor-styles', [
            'description' => __('Whether theme opts in to the editor styles CSS wrapper.'),
            'show_in_rest' => true,
        ]);
        register_theme_feature('html5', [
            'type' => 'array',
            'description' => __('Allows use of HTML5 markup for search forms, comment forms, comment lists, gallery, and caption.'),
            'show_in_rest' => [
                'schema' => [
                    'items' => [
                        'type' => 'string',
                        'enum' => [
                            'search-form',
                            'comment-form',
                            'comment-list',
                            'gallery',
                            'caption',
                            'script',
                            'style',
                        ],
                    ],
                ],
            ],
        ]);
        register_theme_feature('post-formats', [
            'type' => 'array',
            'description' => __('Post formats supported.'),
            'show_in_rest' => [
                'name' => 'formats',
                'schema' => [
                    'items' => [
                        'type' => 'string',
                        'enum' => get_post_format_slugs(),
                    ],
                    'default' => ['standard'],
                ],
                'prepare_callback' => static function($formats)
                {
                    $formats = is_array($formats) ? array_values($formats[0]) : [];
                    $formats = array_merge(['standard'], $formats);

                    return $formats;
                },
            ],
        ]);
        register_theme_feature('post-thumbnails', [
            'type' => 'array',
            'description' => __('The post types that support thumbnails or true if all post types are supported.'),
            'show_in_rest' => [
                'type' => ['boolean', 'array'],
                'schema' => [
                    'items' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ]);
        register_theme_feature('responsive-embeds', [
            'description' => __('Whether the theme supports responsive embedded content.'),
            'show_in_rest' => true,
        ]);
        register_theme_feature('title-tag', [
            'description' => __('Whether the theme can manage the document title tag.'),
            'show_in_rest' => true,
        ]);
        register_theme_feature('wp-block-styles', [
            'description' => __('Whether theme opts in to default WordPress block styles for viewing.'),
            'show_in_rest' => true,
        ]);
    }

    function wp_is_block_theme()
    {
        return wp_get_theme()->is_block_theme();
    }

    function wp_theme_get_element_class_name($element)
    {
        return WP_Theme_JSON::get_element_class_name($element);
    }

    function _add_default_theme_supports()
    {
        if(! wp_is_block_theme())
        {
            return;
        }

        add_theme_support('post-thumbnails');
        add_theme_support('responsive-embeds');
        add_theme_support('editor-styles');
        /*
	 * Makes block themes support HTML5 by default for the comment block and search form
	 * (which use default template functions) and `[caption]` and `[gallery]` shortcodes.
	 * Other blocks contain their own HTML5 markup.
	 */
        add_theme_support('html5', [
            'comment-form',
            'comment-list',
            'search-form',
            'gallery',
            'caption',
            'style',
            'script'
        ]);
        add_theme_support('automatic-feed-links');

        add_filter('should_load_separate_core_block_assets', '__return_true');

        /*
	 * Remove the Customizer's Menus panel when block theme is active.
	 */
        add_filter('customize_panel_active', static function($active, WP_Customize_Panel $panel)
        {
            if('nav_menus' === $panel->id && ! current_theme_supports('menus') && ! current_theme_supports('widgets'))
            {
                $active = false;
            }

            return $active;
        },         10, 2);
    }
