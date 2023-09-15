<?php

    class Language_Pack_Upgrader extends WP_Upgrader
    {
        public $result;

        public $bulk = true;

        public static function async_upgrade($upgrader = false)
        {
            // Avoid recursion.
            if($upgrader && $upgrader instanceof Language_Pack_Upgrader)
            {
                return;
            }

            // Nothing to do?
            $language_updates = wp_get_translation_updates();
            if(! $language_updates)
            {
                return;
            }

            /*
             * Avoid messing with VCS installations, at least for now.
             * Noted: this is not the ideal way to accomplish this.
             */
            $check_vcs = new WP_Automatic_Updater();
            if($check_vcs->is_vcs_checkout(WP_CONTENT_DIR))
            {
                return;
            }

            foreach($language_updates as $key => $language_update)
            {
                $update = ! empty($language_update->autoupdate);

                $update = apply_filters('async_update_translation', $update, $language_update);

                if(! $update)
                {
                    unset($language_updates[$key]);
                }
            }

            if(empty($language_updates))
            {
                return;
            }

            // Re-use the automatic upgrader skin if the parent upgrader is using it.
            if($upgrader && $upgrader->skin instanceof Automatic_Upgrader_Skin)
            {
                $skin = $upgrader->skin;
            }
            else
            {
                $skin = new Language_Pack_Upgrader_Skin([
                                                            'skip_header_footer' => true,
                                                        ]);
            }

            $lp_upgrader = new Language_Pack_Upgrader($skin);
            $lp_upgrader->bulk_upgrade($language_updates);
        }

        public function bulk_upgrade($language_updates = [], $args = [])
        {
            global $wp_filesystem;

            $defaults = [
                'clear_update_cache' => true,
            ];
            $parsed_args = wp_parse_args($args, $defaults);

            $this->init();
            $this->upgrade_strings();

            if(! $language_updates)
            {
                $language_updates = wp_get_translation_updates();
            }

            if(empty($language_updates))
            {
                $this->skin->header();
                $this->skin->set_result(true);
                $this->skin->feedback('up_to_date');
                $this->skin->bulk_footer();
                $this->skin->footer();

                return true;
            }

            if('upgrader_process_complete' === current_filter())
            {
                $this->skin->feedback('starting_upgrade');
            }

            // Remove any existing upgrade filters from the plugin/theme upgraders #WP29425 & #WP29230.
            remove_all_filters('upgrader_pre_install');
            remove_all_filters('upgrader_clear_destination');
            remove_all_filters('upgrader_post_install');
            remove_all_filters('upgrader_source_selection');

            add_filter('upgrader_source_selection', [$this, 'check_package'], 10, 2);

            $this->skin->header();

            // Connect to the filesystem first.
            $res = $this->fs_connect([WP_CONTENT_DIR, WP_LANG_DIR]);
            if(! $res)
            {
                $this->skin->footer();

                return false;
            }

            $results = [];

            $this->update_count = count($language_updates);
            $this->update_current = 0;

            /*
             * The filesystem's mkdir() is not recursive. Make sure WP_LANG_DIR exists,
             * as we then may need to create a /plugins or /themes directory inside of it.
             */
            $remote_destination = $wp_filesystem->find_folder(WP_LANG_DIR);
            if(! $wp_filesystem->exists($remote_destination))
            {
                if(! $wp_filesystem->mkdir($remote_destination, FS_CHMOD_DIR))
                {
                    return new WP_Error('mkdir_failed_lang_dir', $this->strings['mkdir_failed'], $remote_destination);
                }
            }

            $language_updates_results = [];

            foreach($language_updates as $language_update)
            {
                $this->skin->language_update = $language_update;

                $destination = WP_LANG_DIR;
                if('plugin' === $language_update->type)
                {
                    $destination .= '/plugins';
                }
                elseif('theme' === $language_update->type)
                {
                    $destination .= '/themes';
                }

                ++$this->update_current;

                $options = [
                    'package' => $language_update->package,
                    'destination' => $destination,
                    'clear_destination' => true,
                    'abort_if_destination_exists' => false, // We expect the destination to exist.
                    'clear_working' => true,
                    'is_multi' => true,
                    'hook_extra' => [
                        'language_update_type' => $language_update->type,
                        'language_update' => $language_update,
                    ],
                ];

                $result = $this->run($options);

                $results[] = $this->result;

                // Prevent credentials auth screen from displaying multiple times.
                if(false === $result)
                {
                    break;
                }

                $language_updates_results[] = [
                    'language' => $language_update->language,
                    'type' => $language_update->type,
                    'slug' => isset($language_update->slug) ? $language_update->slug : 'default',
                    'version' => $language_update->version,
                ];
            }

            // Remove upgrade hooks which are not required for translation updates.
            remove_action('upgrader_process_complete', ['Language_Pack_Upgrader', 'async_upgrade'], 20);
            remove_action('upgrader_process_complete', 'wp_version_check');
            remove_action('upgrader_process_complete', 'wp_update_plugins');
            remove_action('upgrader_process_complete', 'wp_update_themes');

            do_action('upgrader_process_complete', $this, [
                'action' => 'update',
                'type' => 'translation',
                'bulk' => true,
                'translations' => $language_updates_results,
            ]);

            // Re-add upgrade hooks.
            add_action('upgrader_process_complete', ['Language_Pack_Upgrader', 'async_upgrade'], 20);
            add_action('upgrader_process_complete', 'wp_version_check', 10, 0);
            add_action('upgrader_process_complete', 'wp_update_plugins', 10, 0);
            add_action('upgrader_process_complete', 'wp_update_themes', 10, 0);

            $this->skin->bulk_footer();

            $this->skin->footer();

            // Clean up our hooks, in case something else does an upgrade on this connection.
            remove_filter('upgrader_source_selection', [$this, 'check_package']);

            if($parsed_args['clear_update_cache'])
            {
                wp_clean_update_cache();
            }

            return $results;
        }

        public function upgrade_strings()
        {
            $this->strings['starting_upgrade'] = __('Some of your translations need updating. Sit tight for a few more seconds while they are updated as well.');
            $this->strings['up_to_date'] = __('Your translations are all up to date.');
            $this->strings['no_package'] = __('Update package not available.');
            /* translators: %s: Package URL. */
            $this->strings['downloading_package'] = sprintf(__('Downloading translation from %s&#8230;'), '<span class="code pre">%s</span>');
            $this->strings['unpack_package'] = __('Unpacking the update&#8230;');
            $this->strings['process_failed'] = __('Translation update failed.');
            $this->strings['process_success'] = __('Translation updated successfully.');
            $this->strings['remove_old'] = __('Removing the old version of the translation&#8230;');
            $this->strings['remove_old_failed'] = __('Could not remove the old translation.');
        }

        public function upgrade($update = false, $args = [])
        {
            if($update)
            {
                $update = [$update];
            }

            $results = $this->bulk_upgrade($update, $args);

            if(! is_array($results))
            {
                return $results;
            }

            return $results[0];
        }

        public function check_package($source, $remote_source)
        {
            global $wp_filesystem;

            if(is_wp_error($source))
            {
                return $source;
            }

            // Check that the folder contains a valid language.
            $files = $wp_filesystem->dirlist($remote_source);

            // Check to see if a .po and .mo exist in the folder.
            $po = false;
            $mo = false;
            foreach((array) $files as $file => $filedata)
            {
                if(str_ends_with($file, '.po'))
                {
                    $po = true;
                }
                elseif(str_ends_with($file, '.mo'))
                {
                    $mo = true;
                }
            }

            if(! $mo || ! $po)
            {
                return new WP_Error('incompatible_archive_pomo', $this->strings['incompatible_archive'], sprintf(/* translators: 1: .po, 2: .mo */ __('The language pack is missing either the %1$s or %2$s files.'), '<code>.po</code>', '<code>.mo</code>'));
            }

            return $source;
        }

        public function get_name_for_update($update)
        {
            switch($update->type)
            {
                case 'core':
                    return 'WordPress'; // Not translated.

                case 'theme':
                    $theme = wp_get_theme($update->slug);
                    if($theme->exists())
                    {
                        return $theme->Get('Name');
                    }
                    break;
                case 'plugin':
                    $plugin_data = get_plugins('/'.$update->slug);
                    $plugin_data = reset($plugin_data);
                    if($plugin_data)
                    {
                        return $plugin_data['Name'];
                    }
                    break;
            }

            return '';
        }

        public function clear_destination($remote_destination)
        {
            global $wp_filesystem;

            $language_update = $this->skin->language_update;
            $language_directory = WP_LANG_DIR.'/'; // Local path for use with glob().

            if('core' === $language_update->type)
            {
                $files = [
                    $remote_destination.$language_update->language.'.po',
                    $remote_destination.$language_update->language.'.mo',
                    $remote_destination.'admin-'.$language_update->language.'.po',
                    $remote_destination.'admin-'.$language_update->language.'.mo',
                    $remote_destination.'admin-network-'.$language_update->language.'.po',
                    $remote_destination.'admin-network-'.$language_update->language.'.mo',
                    $remote_destination.'continents-cities-'.$language_update->language.'.po',
                    $remote_destination.'continents-cities-'.$language_update->language.'.mo',
                ];

                $json_translation_files = glob($language_directory.$language_update->language.'-*.json');
                if($json_translation_files)
                {
                    foreach($json_translation_files as $json_translation_file)
                    {
                        $files[] = str_replace($language_directory, $remote_destination, $json_translation_file);
                    }
                }
            }
            else
            {
                $files = [
                    $remote_destination.$language_update->slug.'-'.$language_update->language.'.po',
                    $remote_destination.$language_update->slug.'-'.$language_update->language.'.mo',
                ];

                $language_directory = $language_directory.$language_update->type.'s/';
                $json_translation_files = glob($language_directory.$language_update->slug.'-'.$language_update->language.'-*.json');
                if($json_translation_files)
                {
                    foreach($json_translation_files as $json_translation_file)
                    {
                        $files[] = str_replace($language_directory, $remote_destination, $json_translation_file);
                    }
                }
            }

            $files = array_filter($files, [$wp_filesystem, 'exists']);

            // No files to delete.
            if(! $files)
            {
                return true;
            }

            // Check all files are writable before attempting to clear the destination.
            $unwritable_files = [];

            // Check writability.
            foreach($files as $file)
            {
                if(! $wp_filesystem->is_writable($file))
                {
                    // Attempt to alter permissions to allow writes and try again.
                    $wp_filesystem->chmod($file, FS_CHMOD_FILE);
                    if(! $wp_filesystem->is_writable($file))
                    {
                        $unwritable_files[] = $file;
                    }
                }
            }

            if(! empty($unwritable_files))
            {
                return new WP_Error('files_not_writable', $this->strings['files_not_writable'], implode(', ', $unwritable_files));
            }

            foreach($files as $file)
            {
                if(! $wp_filesystem->delete($file))
                {
                    return new WP_Error('remove_old_failed', $this->strings['remove_old_failed']);
                }
            }

            return true;
        }
    }
