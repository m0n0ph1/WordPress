<?php

    function get_plugin_data($plugin_file, $markup = true, $translate = true)
    {
        $default_headers = [
            'Name' => 'Plugin Name',
            'PluginURI' => 'Plugin URI',
            'Version' => 'Version',
            'Description' => 'Description',
            'Author' => 'Author',
            'AuthorURI' => 'Author URI',
            'TextDomain' => 'Text Domain',
            'DomainPath' => 'Domain Path',
            'Network' => 'Network',
            'RequiresWP' => 'Requires at least',
            'RequiresPHP' => 'Requires PHP',
            'UpdateURI' => 'Update URI',
            // Site Wide Only is deprecated in favor of Network.
            '_sitewide' => 'Site Wide Only',
        ];

        $plugin_data = get_file_data($plugin_file, $default_headers, 'plugin');

        // Site Wide Only is the old header for Network.
        if(! $plugin_data['Network'] && $plugin_data['_sitewide'])
        {
            /* translators: 1: Site Wide Only: true, 2: Network: true */
            _deprecated_argument(__FUNCTION__, '3.0.0', sprintf(__('The %1$s plugin header is deprecated. Use %2$s instead.'), '<code>Site Wide Only: true</code>', '<code>Network: true</code>'));
            $plugin_data['Network'] = $plugin_data['_sitewide'];
        }
        $plugin_data['Network'] = ('true' === strtolower($plugin_data['Network']));
        unset($plugin_data['_sitewide']);

        // If no text domain is defined fall back to the plugin slug.
        if(! $plugin_data['TextDomain'])
        {
            $plugin_slug = dirname(plugin_basename($plugin_file));
            if('.' !== $plugin_slug && ! str_contains($plugin_slug, '/'))
            {
                $plugin_data['TextDomain'] = $plugin_slug;
            }
        }

        if($markup || $translate)
        {
            $plugin_data = _get_plugin_data_markup_translate($plugin_file, $plugin_data, $markup, $translate);
        }
        else
        {
            $plugin_data['Title'] = $plugin_data['Name'];
            $plugin_data['AuthorName'] = $plugin_data['Author'];
        }

        return $plugin_data;
    }

    function _get_plugin_data_markup_translate($plugin_file, $plugin_data, $markup = true, $translate = true)
    {
        // Sanitize the plugin filename to a WP_PLUGIN_DIR relative path.
        $plugin_file = plugin_basename($plugin_file);

        // Translate fields.
        if($translate)
        {
            $textdomain = $plugin_data['TextDomain'];
            if($textdomain)
            {
                if(! is_textdomain_loaded($textdomain))
                {
                    if($plugin_data['DomainPath'])
                    {
                        load_plugin_textdomain($textdomain, false, dirname($plugin_file).$plugin_data['DomainPath']);
                    }
                    else
                    {
                        load_plugin_textdomain($textdomain, false, dirname($plugin_file));
                    }
                }
            }
            elseif('hello.php' === basename($plugin_file))
            {
                $textdomain = 'default';
            }
            if($textdomain)
            {
                foreach(['Name', 'PluginURI', 'Description', 'Author', 'AuthorURI', 'Version'] as $field)
                {
                    if(! empty($plugin_data[$field]))
                    {
                        // phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction,WordPress.WP.I18n.NonSingularStringLiteralText,WordPress.WP.I18n.NonSingularStringLiteralDomain
                        $plugin_data[$field] = translate($plugin_data[$field], $textdomain);
                    }
                }
            }
        }

        // Sanitize fields.
        $allowed_tags_in_links = [
            'abbr' => ['title' => true],
            'acronym' => ['title' => true],
            'code' => true,
            'em' => true,
            'strong' => true,
        ];

        $allowed_tags = $allowed_tags_in_links;
        $allowed_tags['a'] = [
            'href' => true,
            'title' => true,
        ];

        /*
         * Name is marked up inside <a> tags. Don't allow these.
         * Author is too, but some plugins have used <a> here (omitting Author URI).
         */
        $plugin_data['Name'] = wp_kses($plugin_data['Name'], $allowed_tags_in_links);
        $plugin_data['Author'] = wp_kses($plugin_data['Author'], $allowed_tags);

        $plugin_data['Description'] = wp_kses($plugin_data['Description'], $allowed_tags);
        $plugin_data['Version'] = wp_kses($plugin_data['Version'], $allowed_tags);

        $plugin_data['PluginURI'] = esc_url($plugin_data['PluginURI']);
        $plugin_data['AuthorURI'] = esc_url($plugin_data['AuthorURI']);

        $plugin_data['Title'] = $plugin_data['Name'];
        $plugin_data['AuthorName'] = $plugin_data['Author'];

        // Apply markup.
        if($markup)
        {
            if($plugin_data['PluginURI'] && $plugin_data['Name'])
            {
                $plugin_data['Title'] = '<a href="'.$plugin_data['PluginURI'].'">'.$plugin_data['Name'].'</a>';
            }

            if($plugin_data['AuthorURI'] && $plugin_data['Author'])
            {
                $plugin_data['Author'] = '<a href="'.$plugin_data['AuthorURI'].'">'.$plugin_data['Author'].'</a>';
            }

            $plugin_data['Description'] = wptexturize($plugin_data['Description']);

            if($plugin_data['Author'])
            {
                $plugin_data['Description'] .= sprintf(/* translators: %s: Plugin author. */ ' <cite>'.__('By %s.').'</cite>', $plugin_data['Author']);
            }
        }

        return $plugin_data;
    }

    function get_plugin_files($plugin)
    {
        $plugin_file = WP_PLUGIN_DIR.'/'.$plugin;
        $dir = dirname($plugin_file);

        $plugin_files = [plugin_basename($plugin_file)];

        if(is_dir($dir) && WP_PLUGIN_DIR !== $dir)
        {
            $exclusions = (array) apply_filters('plugin_files_exclusions', [
                'CVS',
                'node_modules',
                'vendor',
                'bower_components',
            ]);

            $list_files = list_files($dir, 100, $exclusions);
            $list_files = array_map('plugin_basename', $list_files);

            $plugin_files = array_merge($plugin_files, $list_files);
            $plugin_files = array_values(array_unique($plugin_files));
        }

        return $plugin_files;
    }

    function get_plugins($plugin_folder = '')
    {
        $cache_plugins = wp_cache_get('plugins', 'plugins');
        if(! $cache_plugins)
        {
            $cache_plugins = [];
        }

        if(isset($cache_plugins[$plugin_folder]))
        {
            return $cache_plugins[$plugin_folder];
        }

        $wp_plugins = [];
        $plugin_root = WP_PLUGIN_DIR;
        if(! empty($plugin_folder))
        {
            $plugin_root .= $plugin_folder;
        }

        // Files in wp-content/plugins directory.
        $plugins_dir = @opendir($plugin_root);
        $plugin_files = [];

        if($plugins_dir)
        {
            while(($file = readdir($plugins_dir)) !== false)
            {
                if(str_starts_with($file, '.'))
                {
                    continue;
                }

                if(is_dir($plugin_root.'/'.$file))
                {
                    $plugins_subdir = @opendir($plugin_root.'/'.$file);

                    if($plugins_subdir)
                    {
                        while(($subfile = readdir($plugins_subdir)) !== false)
                        {
                            if(str_starts_with($subfile, '.'))
                            {
                                continue;
                            }

                            if(str_ends_with($subfile, '.php'))
                            {
                                $plugin_files[] = "$file/$subfile";
                            }
                        }

                        closedir($plugins_subdir);
                    }
                }
                else
                {
                    if(str_ends_with($file, '.php'))
                    {
                        $plugin_files[] = $file;
                    }
                }
            }

            closedir($plugins_dir);
        }

        if(empty($plugin_files))
        {
            return $wp_plugins;
        }

        foreach($plugin_files as $plugin_file)
        {
            if(! is_readable("$plugin_root/$plugin_file"))
            {
                continue;
            }

            // Do not apply markup/translate as it will be cached.
            $plugin_data = get_plugin_data("$plugin_root/$plugin_file", false, false);

            if(empty($plugin_data['Name']))
            {
                continue;
            }

            $wp_plugins[plugin_basename($plugin_file)] = $plugin_data;
        }

        uasort($wp_plugins, '_sort_uname_callback');

        $cache_plugins[$plugin_folder] = $wp_plugins;
        wp_cache_set('plugins', $cache_plugins, 'plugins');

        return $wp_plugins;
    }

    function get_mu_plugins()
    {
        $wp_plugins = [];
        $plugin_files = [];

        if(! is_dir(WPMU_PLUGIN_DIR))
        {
            return $wp_plugins;
        }

        // Files in wp-content/mu-plugins directory.
        $plugins_dir = @opendir(WPMU_PLUGIN_DIR);
        if($plugins_dir)
        {
            while(($file = readdir($plugins_dir)) !== false)
            {
                if(str_ends_with($file, '.php'))
                {
                    $plugin_files[] = $file;
                }
            }
        }
        else
        {
            return $wp_plugins;
        }

        closedir($plugins_dir);

        if(empty($plugin_files))
        {
            return $wp_plugins;
        }

        foreach($plugin_files as $plugin_file)
        {
            if(! is_readable(WPMU_PLUGIN_DIR."/$plugin_file"))
            {
                continue;
            }

            // Do not apply markup/translate as it will be cached.
            $plugin_data = get_plugin_data(WPMU_PLUGIN_DIR."/$plugin_file", false, false);

            if(empty($plugin_data['Name']))
            {
                $plugin_data['Name'] = $plugin_file;
            }

            $wp_plugins[$plugin_file] = $plugin_data;
        }

        if(isset($wp_plugins['index.php']) && filesize(WPMU_PLUGIN_DIR.'/index.php') <= 30)
        {
            // Silence is golden.
            unset($wp_plugins['index.php']);
        }

        uasort($wp_plugins, '_sort_uname_callback');

        return $wp_plugins;
    }

    function _sort_uname_callback($a, $b)
    {
        return strnatcasecmp($a['Name'], $b['Name']);
    }

    function get_dropins()
    {
        $dropins = [];
        $plugin_files = [];

        $_dropins = _get_dropins();

        // Files in wp-content directory.
        $plugins_dir = @opendir(WP_CONTENT_DIR);
        if($plugins_dir)
        {
            while(($file = readdir($plugins_dir)) !== false)
            {
                if(isset($_dropins[$file]))
                {
                    $plugin_files[] = $file;
                }
            }
        }
        else
        {
            return $dropins;
        }

        closedir($plugins_dir);

        if(empty($plugin_files))
        {
            return $dropins;
        }

        foreach($plugin_files as $plugin_file)
        {
            if(! is_readable(WP_CONTENT_DIR."/$plugin_file"))
            {
                continue;
            }

            // Do not apply markup/translate as it will be cached.
            $plugin_data = get_plugin_data(WP_CONTENT_DIR."/$plugin_file", false, false);

            if(empty($plugin_data['Name']))
            {
                $plugin_data['Name'] = $plugin_file;
            }

            $dropins[$plugin_file] = $plugin_data;
        }

        uksort($dropins, 'strnatcasecmp');

        return $dropins;
    }

    function _get_dropins()
    {
        $dropins = [
            'advanced-cache.php' => [__('Advanced caching plugin.'), 'WP_CACHE'],  // WP_CACHE
            'db.php' => [__('Custom database class.'), true],          // Auto on load.
            'db-error.php' => [__('Custom database error message.'), true],  // Auto on error.
            'install.php' => [__('Custom installation script.'), true],     // Auto on installation.
            'maintenance.php' => [__('Custom maintenance message.'), true],     // Auto on maintenance.
            'object-cache.php' => [__('External object cache.'), true],          // Auto on load.
            'php-error.php' => [__('Custom PHP error message.'), true],       // Auto on error.
            'fatal-error-handler.php' => [__('Custom PHP fatal error handler.'), true], // Auto on error.
        ];

        if(is_multisite())
        {
            $dropins['sunrise.php'] = [__('Executed before Multisite is loaded.'), 'SUNRISE']; // SUNRISE
            $dropins['blog-deleted.php'] = [__('Custom site deleted message.'), true];   // Auto on deleted blog.
            $dropins['blog-inactive.php'] = [__('Custom site inactive message.'), true];  // Auto on inactive blog.
            $dropins['blog-suspended.php'] = [
                __('Custom site suspended message.'),
                true,
            ]; // Auto on archived or spammed blog.
        }

        return $dropins;
    }

    function is_plugin_active($plugin)
    {
        return in_array($plugin, (array) get_option('active_plugins', []), true) || is_plugin_active_for_network($plugin);
    }

    function is_plugin_inactive($plugin)
    {
        return ! is_plugin_active($plugin);
    }

    function is_plugin_active_for_network($plugin)
    {
        if(! is_multisite())
        {
            return false;
        }

        $plugins = get_site_option('active_sitewide_plugins');
        if(isset($plugins[$plugin]))
        {
            return true;
        }

        return false;
    }

    function is_network_only_plugin($plugin)
    {
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR.'/'.$plugin);
        if($plugin_data)
        {
            return $plugin_data['Network'];
        }

        return false;
    }

    function activate_plugin($plugin, $redirect = '', $network_wide = false, $silent = false)
    {
        $plugin = plugin_basename(trim($plugin));

        if(is_multisite() && ($network_wide || is_network_only_plugin($plugin)))
        {
            $network_wide = true;
            $current = get_site_option('active_sitewide_plugins', []);
            $_GET['networkwide'] = 1; // Back compat for plugins looking for this value.
        }
        else
        {
            $current = get_option('active_plugins', []);
        }

        $valid = validate_plugin($plugin);
        if(is_wp_error($valid))
        {
            return $valid;
        }

        $requirements = validate_plugin_requirements($plugin);
        if(is_wp_error($requirements))
        {
            return $requirements;
        }

        if($network_wide && ! isset($current[$plugin]) || ! $network_wide && ! in_array($plugin, $current, true))
        {
            if(! empty($redirect))
            {
                // We'll override this later if the plugin can be included without fatal error.
                wp_redirect(add_query_arg('_error_nonce', wp_create_nonce('plugin-activation-error_'.$plugin), $redirect));
            }

            ob_start();

            // Load the plugin to test whether it throws any errors.
            plugin_sandbox_scrape($plugin);

            if(! $silent)
            {
                do_action('activate_plugin', $plugin, $network_wide);

                do_action("activate_{$plugin}", $network_wide);
            }

            if($network_wide)
            {
                $current = get_site_option('active_sitewide_plugins', []);
                $current[$plugin] = time();
                update_site_option('active_sitewide_plugins', $current);
            }
            else
            {
                $current = get_option('active_plugins', []);
                $current[] = $plugin;
                sort($current);
                update_option('active_plugins', $current);
            }

            if(! $silent)
            {
                do_action('activated_plugin', $plugin, $network_wide);
            }

            if(ob_get_length() > 0)
            {
                $output = ob_get_clean();

                return new WP_Error('unexpected_output', __('The plugin generated unexpected output.'), $output);
            }

            ob_end_clean();
        }

        return null;
    }

    function deactivate_plugins($plugins, $silent = false, $network_wide = null)
    {
        if(is_multisite())
        {
            $network_current = get_site_option('active_sitewide_plugins', []);
        }
        $current = get_option('active_plugins', []);
        $do_blog = false;
        $do_network = false;

        foreach((array) $plugins as $plugin)
        {
            $plugin = plugin_basename(trim($plugin));
            if(! is_plugin_active($plugin))
            {
                continue;
            }

            $network_deactivating = (false !== $network_wide) && is_plugin_active_for_network($plugin);

            if(! $silent)
            {
                do_action('deactivate_plugin', $plugin, $network_deactivating);
            }

            if(false !== $network_wide)
            {
                if(is_plugin_active_for_network($plugin))
                {
                    $do_network = true;
                    unset($network_current[$plugin]);
                }
                elseif($network_wide)
                {
                    continue;
                }
            }

            if(true !== $network_wide)
            {
                $key = array_search($plugin, $current, true);
                if(false !== $key)
                {
                    $do_blog = true;
                    unset($current[$key]);
                }
            }

            if($do_blog && wp_is_recovery_mode())
            {
                [$extension] = explode('/', $plugin);
                wp_paused_plugins()->delete($extension);
            }

            if(! $silent)
            {
                do_action("deactivate_{$plugin}", $network_deactivating);

                do_action('deactivated_plugin', $plugin, $network_deactivating);
            }
        }

        if($do_blog)
        {
            update_option('active_plugins', $current);
        }
        if($do_network)
        {
            update_site_option('active_sitewide_plugins', $network_current);
        }
    }

    function activate_plugins($plugins, $redirect = '', $network_wide = false, $silent = false)
    {
        if(! is_array($plugins))
        {
            $plugins = [$plugins];
        }

        $errors = [];
        foreach($plugins as $plugin)
        {
            if(! empty($redirect))
            {
                $redirect = add_query_arg('plugin', $plugin, $redirect);
            }
            $result = activate_plugin($plugin, $redirect, $network_wide, $silent);
            if(is_wp_error($result))
            {
                $errors[$plugin] = $result;
            }
        }

        if(! empty($errors))
        {
            return new WP_Error('plugins_invalid', __('One of the plugins is invalid.'), $errors);
        }

        return true;
    }

    function delete_plugins($plugins, $deprecated = '')
    {
        global $wp_filesystem;

        if(empty($plugins))
        {
            return false;
        }

        $checked = [];
        foreach($plugins as $plugin)
        {
            $checked[] = 'checked[]='.$plugin;
        }

        $url = wp_nonce_url('plugins.php?action=delete-selected&verify-delete=1&'.implode('&', $checked), 'bulk-plugins');

        ob_start();
        $credentials = request_filesystem_credentials($url);
        $data = ob_get_clean();

        if(false === $credentials)
        {
            if(! empty($data))
            {
                require_once ABSPATH.'wp-admin/admin-header.php';
                echo $data;
                require_once ABSPATH.'wp-admin/admin-footer.php';
                exit;
            }

            return;
        }

        if(! WP_Filesystem($credentials))
        {
            ob_start();
            // Failed to connect. Error and request again.
            request_filesystem_credentials($url, '', true);
            $data = ob_get_clean();

            if(! empty($data))
            {
                require_once ABSPATH.'wp-admin/admin-header.php';
                echo $data;
                require_once ABSPATH.'wp-admin/admin-footer.php';
                exit;
            }

            return;
        }

        if(! is_object($wp_filesystem))
        {
            return new WP_Error('fs_unavailable', __('Could not access filesystem.'));
        }

        if(is_wp_error($wp_filesystem->errors) && $wp_filesystem->errors->has_errors())
        {
            return new WP_Error('fs_error', __('Filesystem error.'), $wp_filesystem->errors);
        }

        // Get the base plugin folder.
        $plugins_dir = $wp_filesystem->wp_plugins_dir();
        if(empty($plugins_dir))
        {
            return new WP_Error('fs_no_plugins_dir', __('Unable to locate WordPress plugin directory.'));
        }

        $plugins_dir = trailingslashit($plugins_dir);

        $plugin_translations = wp_get_installed_translations('plugins');

        $errors = [];

        foreach($plugins as $plugin_file)
        {
            // Run Uninstall hook.
            if(is_uninstallable_plugin($plugin_file))
            {
                uninstall_plugin($plugin_file);
            }

            do_action('delete_plugin', $plugin_file);

            $this_plugin_dir = trailingslashit(dirname($plugins_dir.$plugin_file));

            /*
             * If plugin is in its own directory, recursively delete the directory.
             * Base check on if plugin includes directory separator AND that it's not the root plugin folder.
             */
            if(strpos($plugin_file, '/') && $this_plugin_dir !== $plugins_dir)
            {
                $deleted = $wp_filesystem->delete($this_plugin_dir, true);
            }
            else
            {
                $deleted = $wp_filesystem->delete($plugins_dir.$plugin_file);
            }

            do_action('deleted_plugin', $plugin_file, $deleted);

            if(! $deleted)
            {
                $errors[] = $plugin_file;
                continue;
            }

            $plugin_slug = dirname($plugin_file);

            if('hello.php' === $plugin_file)
            {
                $plugin_slug = 'hello-dolly';
            }

            // Remove language files, silently.
            if('.' !== $plugin_slug && ! empty($plugin_translations[$plugin_slug]))
            {
                $translations = $plugin_translations[$plugin_slug];

                foreach($translations as $translation => $data)
                {
                    $wp_filesystem->delete(WP_LANG_DIR.'/plugins/'.$plugin_slug.'-'.$translation.'.po');
                    $wp_filesystem->delete(WP_LANG_DIR.'/plugins/'.$plugin_slug.'-'.$translation.'.mo');

                    $json_translation_files = glob(WP_LANG_DIR.'/plugins/'.$plugin_slug.'-'.$translation.'-*.json');
                    if($json_translation_files)
                    {
                        array_map([$wp_filesystem, 'delete'], $json_translation_files);
                    }
                }
            }
        }

        // Remove deleted plugins from the plugin updates list.
        $current = get_site_transient('update_plugins');
        if($current)
        {
            // Don't remove the plugins that weren't deleted.
            $deleted = array_diff($plugins, $errors);

            foreach($deleted as $plugin_file)
            {
                unset($current->response[$plugin_file]);
            }

            set_site_transient('update_plugins', $current);
        }

        if(! empty($errors))
        {
            if(1 === count($errors))
            {
                /* translators: %s: Plugin filename. */
                $message = __('Could not fully remove the plugin %s.');
            }
            else
            {
                /* translators: %s: Comma-separated list of plugin filenames. */
                $message = __('Could not fully remove the plugins %s.');
            }

            return new WP_Error('could_not_remove_plugin', sprintf($message, implode(', ', $errors)));
        }

        return true;
    }

    function validate_active_plugins()
    {
        $plugins = get_option('active_plugins', []);
        // Validate vartype: array.
        if(! is_array($plugins))
        {
            update_option('active_plugins', []);
            $plugins = [];
        }

        if(is_multisite() && current_user_can('manage_network_plugins'))
        {
            $network_plugins = (array) get_site_option('active_sitewide_plugins', []);
            $plugins = array_merge($plugins, array_keys($network_plugins));
        }

        if(empty($plugins))
        {
            return [];
        }

        $invalid = [];

        // Invalid plugins get deactivated.
        foreach($plugins as $plugin)
        {
            $result = validate_plugin($plugin);
            if(is_wp_error($result))
            {
                $invalid[$plugin] = $result;
                deactivate_plugins($plugin, true);
            }
        }

        return $invalid;
    }

    function validate_plugin($plugin)
    {
        if(validate_file($plugin))
        {
            return new WP_Error('plugin_invalid', __('Invalid plugin path.'));
        }
        if(! file_exists(WP_PLUGIN_DIR.'/'.$plugin))
        {
            return new WP_Error('plugin_not_found', __('Plugin file does not exist.'));
        }

        $installed_plugins = get_plugins();
        if(! isset($installed_plugins[$plugin]))
        {
            return new WP_Error('no_plugin_header', __('The plugin does not have a valid header.'));
        }

        return 0;
    }

    function validate_plugin_requirements($plugin)
    {
        $plugin_headers = get_plugin_data(WP_PLUGIN_DIR.'/'.$plugin);

        $requirements = [
            'requires' => ! empty($plugin_headers['RequiresWP']) ? $plugin_headers['RequiresWP'] : '',
            'requires_php' => ! empty($plugin_headers['RequiresPHP']) ? $plugin_headers['RequiresPHP'] : '',
        ];

        $compatible_wp = is_wp_version_compatible($requirements['requires']);
        $compatible_php = is_php_version_compatible($requirements['requires_php']);

        $php_update_message = '</p><p>'.sprintf(/* translators: %s: URL to Update PHP page. */ __('<a href="%s">Learn more about updating PHP</a>.'), esc_url(wp_get_update_php_url()));

        $annotation = wp_get_update_php_annotation();

        if($annotation)
        {
            $php_update_message .= '</p><p><em>'.$annotation.'</em>';
        }

        if(! $compatible_wp && ! $compatible_php)
        {
            return new WP_Error('plugin_wp_php_incompatible', '<p>'.sprintf(/* translators: 1: Current WordPress version, 2: Current PHP version, 3: Plugin name, 4: Required WordPress version, 5: Required PHP version. */ _x('<strong>Error:</strong> Current versions of WordPress (%1$s) and PHP (%2$s) do not meet minimum requirements for %3$s. The plugin requires WordPress %4$s and PHP %5$s.', 'plugin'), get_bloginfo('version'), PHP_VERSION, $plugin_headers['Name'], $requirements['requires'], $requirements['requires_php']).$php_update_message.'</p>');
        }
        elseif(! $compatible_php)
        {
            return new WP_Error('plugin_php_incompatible', '<p>'.sprintf(/* translators: 1: Current PHP version, 2: Plugin name, 3: Required PHP version. */ _x('<strong>Error:</strong> Current PHP version (%1$s) does not meet minimum requirements for %2$s. The plugin requires PHP %3$s.', 'plugin'), PHP_VERSION, $plugin_headers['Name'], $requirements['requires_php']).$php_update_message.'</p>');
        }
        elseif(! $compatible_wp)
        {
            return new WP_Error('plugin_wp_incompatible', '<p>'.sprintf(/* translators: 1: Current WordPress version, 2: Plugin name, 3: Required WordPress version. */ _x('<strong>Error:</strong> Current WordPress version (%1$s) does not meet minimum requirements for %2$s. The plugin requires WordPress %3$s.', 'plugin'), get_bloginfo('version'), $plugin_headers['Name'], $requirements['requires']).'</p>');
        }

        return true;
    }

    function is_uninstallable_plugin($plugin)
    {
        $file = plugin_basename($plugin);

        $uninstallable_plugins = (array) get_option('uninstall_plugins');
        if(isset($uninstallable_plugins[$file]) || file_exists(WP_PLUGIN_DIR.'/'.dirname($file).'/uninstall.php'))
        {
            return true;
        }

        return false;
    }

    function uninstall_plugin($plugin)
    {
        $file = plugin_basename($plugin);

        $uninstallable_plugins = (array) get_option('uninstall_plugins');

        do_action('pre_uninstall_plugin', $plugin, $uninstallable_plugins);

        if(file_exists(WP_PLUGIN_DIR.'/'.dirname($file).'/uninstall.php'))
        {
            if(isset($uninstallable_plugins[$file]))
            {
                unset($uninstallable_plugins[$file]);
                update_option('uninstall_plugins', $uninstallable_plugins);
            }
            unset($uninstallable_plugins);

            define('WP_UNINSTALL_PLUGIN', $file);

            wp_register_plugin_realpath(WP_PLUGIN_DIR.'/'.$file);
            include_once WP_PLUGIN_DIR.'/'.dirname($file).'/uninstall.php';

            return true;
        }

        if(isset($uninstallable_plugins[$file]))
        {
            $callable = $uninstallable_plugins[$file];
            unset($uninstallable_plugins[$file]);
            update_option('uninstall_plugins', $uninstallable_plugins);
            unset($uninstallable_plugins);

            wp_register_plugin_realpath(WP_PLUGIN_DIR.'/'.$file);
            include_once WP_PLUGIN_DIR.'/'.$file;

            add_action("uninstall_{$file}", $callable);

            do_action("uninstall_{$file}");
        }
    }

//
// Menu.
//

    function add_menu_page(
        $page_title, $menu_title, $capability, $menu_slug, $callback = '', $icon_url = '', $position = null
    ) {
        global $menu, $admin_page_hooks, $_registered_pages, $_parent_pages;

        $menu_slug = plugin_basename($menu_slug);

        $admin_page_hooks[$menu_slug] = sanitize_title($menu_title);

        $hookname = get_plugin_page_hookname($menu_slug, '');

        if(! empty($callback) && ! empty($hookname) && current_user_can($capability))
        {
            add_action($hookname, $callback);
        }

        if(empty($icon_url))
        {
            $icon_url = 'dashicons-admin-generic';
            $icon_class = 'menu-icon-generic ';
        }
        else
        {
            $icon_url = set_url_scheme($icon_url);
            $icon_class = '';
        }

        $new_menu = [
            $menu_title,
            $capability,
            $menu_slug,
            $page_title,
            'menu-top '.$icon_class.$hookname,
            $hookname,
            $icon_url,
        ];

        if(null !== $position && ! is_numeric($position))
        {
            _doing_it_wrong(__FUNCTION__, sprintf(/* translators: %s: add_menu_page() */ __('The seventh parameter passed to %s should be numeric representing menu position.'), '<code>add_menu_page()</code>'), '6.0.0');
            $position = null;
        }

        if(null === $position || ! is_numeric($position))
        {
            $menu[] = $new_menu;
        }
        elseif(isset($menu[(string) $position]))
        {
            $collision_avoider = base_convert(substr(md5($menu_slug.$menu_title), -4), 16, 10) * 0.00001;
            $position = (string) ($position + $collision_avoider);
            $menu[$position] = $new_menu;
        }
        else
        {
            /*
             * Cast menu position to a string.
             *
             * This allows for floats to be passed as the position. PHP will normally cast a float to an
             * integer value, this ensures the float retains its mantissa (positive fractional part).
             *
             * A string containing an integer value, eg "10", is treated as a numeric index.
             */
            $position = (string) $position;
            $menu[$position] = $new_menu;
        }

        $_registered_pages[$hookname] = true;

        // No parent as top level.
        $_parent_pages[$menu_slug] = false;

        return $hookname;
    }

    function add_submenu_page(
        $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null
    ) {
        global $submenu, $menu, $_wp_real_parent_file, $_wp_submenu_nopriv, $_registered_pages, $_parent_pages;

        $menu_slug = plugin_basename($menu_slug);
        $parent_slug = plugin_basename($parent_slug);

        if(isset($_wp_real_parent_file[$parent_slug]))
        {
            $parent_slug = $_wp_real_parent_file[$parent_slug];
        }

        if(! current_user_can($capability))
        {
            $_wp_submenu_nopriv[$parent_slug][$menu_slug] = true;

            return false;
        }

        /*
         * If the parent doesn't already have a submenu, add a link to the parent
         * as the first item in the submenu. If the submenu file is the same as the
         * parent file someone is trying to link back to the parent manually. In
         * this case, don't automatically add a link back to avoid duplication.
         */
        if(! isset($submenu[$parent_slug]) && $menu_slug !== $parent_slug)
        {
            foreach((array) $menu as $parent_menu)
            {
                if($parent_menu[2] === $parent_slug && current_user_can($parent_menu[1]))
                {
                    $submenu[$parent_slug][] = array_slice($parent_menu, 0, 4);
                }
            }
        }

        $new_sub_menu = [$menu_title, $capability, $menu_slug, $page_title];

        if(null !== $position && ! is_numeric($position))
        {
            _doing_it_wrong(__FUNCTION__, sprintf(/* translators: %s: add_submenu_page() */ __('The seventh parameter passed to %s should be numeric representing menu position.'), '<code>add_submenu_page()</code>'), '5.3.0');
            $position = null;
        }

        if(null === $position || (! isset($submenu[$parent_slug]) || $position >= count($submenu[$parent_slug])))
        {
            $submenu[$parent_slug][] = $new_sub_menu;
        }
        else
        {
            // Test for a negative position.
            $position = max($position, 0);
            if(0 === $position)
            {
                // For negative or `0` positions, prepend the submenu.
                array_unshift($submenu[$parent_slug], $new_sub_menu);
            }
            else
            {
                $position = absint($position);
                // Grab all of the items before the insertion point.
                $before_items = array_slice($submenu[$parent_slug], 0, $position, true);
                // Grab all of the items after the insertion point.
                $after_items = array_slice($submenu[$parent_slug], $position, null, true);
                // Add the new item.
                $before_items[] = $new_sub_menu;
                // Merge the items.
                $submenu[$parent_slug] = array_merge($before_items, $after_items);
            }
        }

        // Sort the parent array.
        ksort($submenu[$parent_slug]);

        $hookname = get_plugin_page_hookname($menu_slug, $parent_slug);
        if(! empty($callback) && ! empty($hookname))
        {
            add_action($hookname, $callback);
        }

        $_registered_pages[$hookname] = true;

        /*
         * Backward-compatibility for plugins using add_management_page().
         * See wp-admin/admin.php for redirect from edit.php to tools.php.
         */
        if('tools.php' === $parent_slug)
        {
            $_registered_pages[get_plugin_page_hookname($menu_slug, 'edit.php')] = true;
        }

        // No parent as top level.
        $_parent_pages[$menu_slug] = $parent_slug;

        return $hookname;
    }

    function add_management_page($page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null)
    {
        return add_submenu_page('tools.php', $page_title, $menu_title, $capability, $menu_slug, $callback, $position);
    }

    function add_options_page($page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null)
    {
        return add_submenu_page('options-general.php', $page_title, $menu_title, $capability, $menu_slug, $callback, $position);
    }

    function add_theme_page($page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null)
    {
        return add_submenu_page('themes.php', $page_title, $menu_title, $capability, $menu_slug, $callback, $position);
    }

    function add_plugins_page($page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null)
    {
        return add_submenu_page('plugins.php', $page_title, $menu_title, $capability, $menu_slug, $callback, $position);
    }

    function add_users_page($page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null)
    {
        if(current_user_can('edit_users'))
        {
            $parent = 'users.php';
        }
        else
        {
            $parent = 'profile.php';
        }

        return add_submenu_page($parent, $page_title, $menu_title, $capability, $menu_slug, $callback, $position);
    }

    function add_dashboard_page($page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null)
    {
        return add_submenu_page('index.php', $page_title, $menu_title, $capability, $menu_slug, $callback, $position);
    }

    function add_posts_page($page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null)
    {
        return add_submenu_page('edit.php', $page_title, $menu_title, $capability, $menu_slug, $callback, $position);
    }

    function add_media_page($page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null)
    {
        return add_submenu_page('upload.php', $page_title, $menu_title, $capability, $menu_slug, $callback, $position);
    }

    function add_links_page($page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null)
    {
        return add_submenu_page('link-manager.php', $page_title, $menu_title, $capability, $menu_slug, $callback, $position);
    }

    function add_pages_page($page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null)
    {
        return add_submenu_page('edit.php?post_type=page', $page_title, $menu_title, $capability, $menu_slug, $callback, $position);
    }

    function add_comments_page($page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null)
    {
        return add_submenu_page('edit-comments.php', $page_title, $menu_title, $capability, $menu_slug, $callback, $position);
    }

    function remove_menu_page($menu_slug)
    {
        global $menu;

        foreach($menu as $i => $item)
        {
            if($menu_slug === $item[2])
            {
                unset($menu[$i]);

                return $item;
            }
        }

        return false;
    }

    function remove_submenu_page($menu_slug, $submenu_slug)
    {
        global $submenu;

        if(! isset($submenu[$menu_slug]))
        {
            return false;
        }

        foreach($submenu[$menu_slug] as $i => $item)
        {
            if($submenu_slug === $item[2])
            {
                unset($submenu[$menu_slug][$i]);

                return $item;
            }
        }

        return false;
    }

    function menu_page_url($menu_slug, $display = true)
    {
        global $_parent_pages;

        if(isset($_parent_pages[$menu_slug]))
        {
            $parent_slug = $_parent_pages[$menu_slug];

            if($parent_slug && ! isset($_parent_pages[$parent_slug]))
            {
                $url = admin_url(add_query_arg('page', $menu_slug, $parent_slug));
            }
            else
            {
                $url = admin_url('admin.php?page='.$menu_slug);
            }
        }
        else
        {
            $url = '';
        }

        $url = esc_url($url);

        if($display)
        {
            echo $url;
        }

        return $url;
    }

//
// Pluggable Menu Support -- Private.
//

    function get_admin_page_parent($parent_page = '')
    {
        global $parent_file, $menu, $submenu, $pagenow, $typenow, $plugin_page, $_wp_real_parent_file, $_wp_menu_nopriv, $_wp_submenu_nopriv;

        if(! empty($parent_page) && 'admin.php' !== $parent_page)
        {
            if(isset($_wp_real_parent_file[$parent_page]))
            {
                $parent_page = $_wp_real_parent_file[$parent_page];
            }

            return $parent_page;
        }

        if('admin.php' === $pagenow && isset($plugin_page))
        {
            foreach((array) $menu as $parent_menu)
            {
                if($parent_menu[2] === $plugin_page)
                {
                    $parent_file = $plugin_page;

                    if(isset($_wp_real_parent_file[$parent_file]))
                    {
                        $parent_file = $_wp_real_parent_file[$parent_file];
                    }

                    return $parent_file;
                }
            }
            if(isset($_wp_menu_nopriv[$plugin_page]))
            {
                $parent_file = $plugin_page;

                if(isset($_wp_real_parent_file[$parent_file]))
                {
                    $parent_file = $_wp_real_parent_file[$parent_file];
                }

                return $parent_file;
            }
        }

        if(isset($plugin_page) && isset($_wp_submenu_nopriv[$pagenow][$plugin_page]))
        {
            $parent_file = $pagenow;

            if(isset($_wp_real_parent_file[$parent_file]))
            {
                $parent_file = $_wp_real_parent_file[$parent_file];
            }

            return $parent_file;
        }

        foreach(array_keys((array) $submenu) as $parent_page)
        {
            foreach($submenu[$parent_page] as $submenu_array)
            {
                if(isset($_wp_real_parent_file[$parent_page]))
                {
                    $parent_page = $_wp_real_parent_file[$parent_page];
                }

                if(! empty($typenow) && "$pagenow?post_type=$typenow" === $submenu_array[2])
                {
                    $parent_file = $parent_page;

                    return $parent_page;
                }
                elseif(empty($typenow) && $pagenow === $submenu_array[2] && (empty($parent_file) || ! str_contains($parent_file, '?')))
                {
                    $parent_file = $parent_page;

                    return $parent_page;
                }
                elseif(isset($plugin_page) && $plugin_page === $submenu_array[2])
                {
                    $parent_file = $parent_page;

                    return $parent_page;
                }
            }
        }

        if(empty($parent_file))
        {
            $parent_file = '';
        }

        return '';
    }

    function get_admin_page_title()
    {
        global $title, $menu, $submenu, $pagenow, $typenow, $plugin_page;

        if(! empty($title))
        {
            return $title;
        }

        $hook = get_plugin_page_hook($plugin_page, $pagenow);

        $parent = get_admin_page_parent();
        $parent1 = $parent;

        if(empty($parent))
        {
            foreach((array) $menu as $menu_array)
            {
                if(isset($menu_array[3]))
                {
                    if($menu_array[2] === $pagenow)
                    {
                        $title = $menu_array[3];

                        return $menu_array[3];
                    }
                    elseif(isset($plugin_page) && $plugin_page === $menu_array[2] && $hook === $menu_array[5])
                    {
                        $title = $menu_array[3];

                        return $menu_array[3];
                    }
                }
                else
                {
                    $title = $menu_array[0];

                    return $title;
                }
            }
        }
        else
        {
            foreach(array_keys($submenu) as $parent)
            {
                foreach($submenu[$parent] as $submenu_array)
                {
                    if(isset($plugin_page) && $plugin_page === $submenu_array[2] && ($pagenow === $parent || $plugin_page === $parent || $plugin_page === $hook || 'admin.php' === $pagenow && $parent1 !== $submenu_array[2] || ! empty($typenow) && "$pagenow?post_type=$typenow" === $parent))
                    {
                        $title = $submenu_array[3];

                        return $submenu_array[3];
                    }

                    if($submenu_array[2] !== $pagenow || isset($_GET['page']))
                    { // Not the current page.
                        continue;
                    }

                    if(isset($submenu_array[3]))
                    {
                        $title = $submenu_array[3];

                        return $submenu_array[3];
                    }
                    else
                    {
                        $title = $submenu_array[0];

                        return $title;
                    }
                }
            }
            if(empty($title))
            {
                foreach($menu as $menu_array)
                {
                    if(isset($plugin_page) && $plugin_page === $menu_array[2] && 'admin.php' === $pagenow && $parent1 === $menu_array[2])
                    {
                        $title = $menu_array[3];

                        return $menu_array[3];
                    }
                }
            }
        }

        return $title;
    }

    function get_plugin_page_hook($plugin_page, $parent_page)
    {
        $hook = get_plugin_page_hookname($plugin_page, $parent_page);
        if(has_action($hook))
        {
            return $hook;
        }
        else
        {
            return null;
        }
    }

    function get_plugin_page_hookname($plugin_page, $parent_page)
    {
        global $admin_page_hooks;

        $parent = get_admin_page_parent($parent_page);

        $page_type = 'admin';
        if(empty($parent_page) || 'admin.php' === $parent_page || isset($admin_page_hooks[$plugin_page]))
        {
            if(isset($admin_page_hooks[$plugin_page]))
            {
                $page_type = 'toplevel';
            }
            elseif(isset($admin_page_hooks[$parent]))
            {
                $page_type = $admin_page_hooks[$parent];
            }
        }
        elseif(isset($admin_page_hooks[$parent]))
        {
            $page_type = $admin_page_hooks[$parent];
        }

        $plugin_name = preg_replace('!\.php!', '', $plugin_page);

        return $page_type.'_page_'.$plugin_name;
    }

    function user_can_access_admin_page()
    {
        global $pagenow, $menu, $submenu, $_wp_menu_nopriv, $_wp_submenu_nopriv, $plugin_page, $_registered_pages;

        $parent = get_admin_page_parent();

        if(! isset($plugin_page) && isset($_wp_submenu_nopriv[$parent][$pagenow]))
        {
            return false;
        }

        if(isset($plugin_page))
        {
            if(isset($_wp_submenu_nopriv[$parent][$plugin_page]))
            {
                return false;
            }

            $hookname = get_plugin_page_hookname($plugin_page, $parent);

            if(! isset($_registered_pages[$hookname]))
            {
                return false;
            }
        }

        if(empty($parent))
        {
            if(isset($_wp_menu_nopriv[$pagenow]))
            {
                return false;
            }
            if(isset($_wp_submenu_nopriv[$pagenow][$pagenow]))
            {
                return false;
            }
            if(isset($plugin_page) && isset($_wp_submenu_nopriv[$pagenow][$plugin_page]))
            {
                return false;
            }
            if(isset($plugin_page) && isset($_wp_menu_nopriv[$plugin_page]))
            {
                return false;
            }

            foreach(array_keys($_wp_submenu_nopriv) as $key)
            {
                if(isset($_wp_submenu_nopriv[$key][$pagenow]))
                {
                    return false;
                }
                if(isset($plugin_page) && isset($_wp_submenu_nopriv[$key][$plugin_page]))
                {
                    return false;
                }
            }

            return true;
        }

        if(isset($plugin_page) && $plugin_page === $parent && isset($_wp_menu_nopriv[$plugin_page]))
        {
            return false;
        }

        if(isset($submenu[$parent]))
        {
            foreach($submenu[$parent] as $submenu_array)
            {
                if(isset($plugin_page) && $submenu_array[2] === $plugin_page)
                {
                    return current_user_can($submenu_array[1]);
                }
                elseif($submenu_array[2] === $pagenow)
                {
                    return current_user_can($submenu_array[1]);
                }
            }
        }

        foreach($menu as $menu_array)
        {
            if($menu_array[2] === $parent)
            {
                return current_user_can($menu_array[1]);
            }
        }

        return true;
    }

    /* Allowed list functions */

    function option_update_filter($options)
    {
        global $new_allowed_options;

        if(is_array($new_allowed_options))
        {
            $options = add_allowed_options($new_allowed_options, $options);
        }

        return $options;
    }

    function add_allowed_options($new_options, $options = '')
    {
        if('' === $options)
        {
            global $allowed_options;
        }
        else
        {
            $allowed_options = $options;
        }

        foreach($new_options as $page => $keys)
        {
            foreach($keys as $key)
            {
                if(! isset($allowed_options[$page]) || ! is_array($allowed_options[$page]))
                {
                    $allowed_options[$page] = [];
                    $allowed_options[$page][] = $key;
                }
                else
                {
                    $pos = array_search($key, $allowed_options[$page], true);
                    if(false === $pos)
                    {
                        $allowed_options[$page][] = $key;
                    }
                }
            }
        }

        return $allowed_options;
    }

    function remove_allowed_options($del_options, $options = '')
    {
        if('' === $options)
        {
            global $allowed_options;
        }
        else
        {
            $allowed_options = $options;
        }

        foreach($del_options as $page => $keys)
        {
            foreach($keys as $key)
            {
                if(isset($allowed_options[$page]) && is_array($allowed_options[$page]))
                {
                    $pos = array_search($key, $allowed_options[$page], true);
                    if(false !== $pos)
                    {
                        unset($allowed_options[$page][$pos]);
                    }
                }
            }
        }

        return $allowed_options;
    }

    function settings_fields($option_group)
    {
        echo "<input type='hidden' name='option_page' value='".esc_attr($option_group)."' />";
        echo '<input type="hidden" name="action" value="update" />';
        wp_nonce_field("$option_group-options");
    }

    function wp_clean_plugins_cache($clear_update_cache = true)
    {
        if($clear_update_cache)
        {
            delete_site_transient('update_plugins');
        }
        wp_cache_delete('plugins', 'plugins');
    }

    function plugin_sandbox_scrape($plugin)
    {
        if(! defined('WP_SANDBOX_SCRAPING'))
        {
            define('WP_SANDBOX_SCRAPING', true);
        }

        wp_register_plugin_realpath(WP_PLUGIN_DIR.'/'.$plugin);
        include_once WP_PLUGIN_DIR.'/'.$plugin;
    }

    function wp_add_privacy_policy_content($plugin_name, $policy_text)
    {
        if(! is_admin())
        {
            _doing_it_wrong(__FUNCTION__, sprintf(/* translators: %s: admin_init */ __('The suggested privacy policy content should be added only in wp-admin by using the %s (or later) action.'), '<code>admin_init</code>'), '4.9.7');

            return;
        }
        elseif(! doing_action('admin_init') && ! did_action('admin_init'))
        {
            _doing_it_wrong(__FUNCTION__, sprintf(/* translators: %s: admin_init */ __('The suggested privacy policy content should be added by using the %s (or later) action. Please see the inline documentation.'), '<code>admin_init</code>'), '4.9.7');

            return;
        }

        if(! class_exists('WP_Privacy_Policy_Content'))
        {
            require_once ABSPATH.'wp-admin/includes/class-wp-privacy-policy-content.php';
        }

        WP_Privacy_Policy_Content::add($plugin_name, $policy_text);
    }

    function is_plugin_paused($plugin)
    {
        if(! isset($GLOBALS['_paused_plugins']))
        {
            return false;
        }

        if(! is_plugin_active($plugin))
        {
            return false;
        }

        [$plugin] = explode('/', $plugin);

        return array_key_exists($plugin, $GLOBALS['_paused_plugins']);
    }

    function wp_get_plugin_error($plugin)
    {
        if(! isset($GLOBALS['_paused_plugins']))
        {
            return false;
        }

        [$plugin] = explode('/', $plugin);

        if(! array_key_exists($plugin, $GLOBALS['_paused_plugins']))
        {
            return false;
        }

        return $GLOBALS['_paused_plugins'][$plugin];
    }

    function resume_plugin($plugin, $redirect = '')
    {
        /*
         * We'll override this later if the plugin could be resumed without
         * creating a fatal error.
         */
        if(! empty($redirect))
        {
            wp_redirect(add_query_arg('_error_nonce', wp_create_nonce('plugin-resume-error_'.$plugin), $redirect));

            // Load the plugin to test whether it throws a fatal error.
            ob_start();
            plugin_sandbox_scrape($plugin);
            ob_clean();
        }

        [$extension] = explode('/', $plugin);

        $result = wp_paused_plugins()->delete($extension);

        if(! $result)
        {
            return new WP_Error('could_not_resume_plugin', __('Could not resume the plugin.'));
        }

        return true;
    }

    function paused_plugins_notice()
    {
        if('plugins.php' === $GLOBALS['pagenow'])
        {
            return;
        }

        if(! current_user_can('resume_plugins'))
        {
            return;
        }

        if(! isset($GLOBALS['_paused_plugins']) || empty($GLOBALS['_paused_plugins']))
        {
            return;
        }

        $message = sprintf('<strong>%s</strong><br>%s</p><p><a href="%s">%s</a>', __('One or more plugins failed to load properly.'), __('You can find more details and make changes on the Plugins screen.'), esc_url(admin_url('plugins.php?plugin_status=paused')), __('Go to the Plugins screen'));
        wp_admin_notice($message, ['type' => 'error']);
    }

    function deactivated_plugins_notice()
    {
        if('plugins.php' === $GLOBALS['pagenow'])
        {
            return;
        }

        if(! current_user_can('activate_plugins'))
        {
            return;
        }

        $blog_deactivated_plugins = get_option('wp_force_deactivated_plugins');
        $site_deactivated_plugins = [];

        if(false === $blog_deactivated_plugins)
        {
            // Option not in database, add an empty array to avoid extra DB queries on subsequent loads.
            update_option('wp_force_deactivated_plugins', []);
        }

        if(is_multisite())
        {
            $site_deactivated_plugins = get_site_option('wp_force_deactivated_plugins');
            if(false === $site_deactivated_plugins)
            {
                // Option not in database, add an empty array to avoid extra DB queries on subsequent loads.
                update_site_option('wp_force_deactivated_plugins', []);
            }
        }

        if(empty($blog_deactivated_plugins) && empty($site_deactivated_plugins))
        {
            // No deactivated plugins.
            return;
        }

        $deactivated_plugins = array_merge($blog_deactivated_plugins, $site_deactivated_plugins);

        foreach($deactivated_plugins as $plugin)
        {
            if(! empty($plugin['version_compatible']) && ! empty($plugin['version_deactivated']))
            {
                $explanation = sprintf(/* translators: 1: Name of deactivated plugin, 2: Plugin version deactivated, 3: Current WP version, 4: Compatible plugin version. */ __('%1$s %2$s was deactivated due to incompatibility with WordPress %3$s, please upgrade to %1$s %4$s or later.'), $plugin['plugin_name'], $plugin['version_deactivated'], $GLOBALS['wp_version'], $plugin['version_compatible']);
            }
            else
            {
                $explanation = sprintf(/* translators: 1: Name of deactivated plugin, 2: Plugin version deactivated, 3: Current WP version. */ __('%1$s %2$s was deactivated due to incompatibility with WordPress %3$s.'), $plugin['plugin_name'], ! empty($plugin['version_deactivated']) ? $plugin['version_deactivated'] : '', $GLOBALS['wp_version'], $plugin['version_compatible']);
            }

            $message = sprintf('<strong>%s</strong><br>%s</p><p><a href="%s">%s</a>', sprintf(/* translators: %s: Name of deactivated plugin. */ __('%s plugin deactivated during WordPress upgrade.'), $plugin['plugin_name']), $explanation, esc_url(admin_url('plugins.php?plugin_status=inactive')), __('Go to the Plugins screen'));
            wp_admin_notice($message, ['type' => 'warning']);
        }

        // Empty the options.
        update_option('wp_force_deactivated_plugins', []);
        if(is_multisite())
        {
            update_site_option('wp_force_deactivated_plugins', []);
        }
    }
