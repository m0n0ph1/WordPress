<?php

    require_once ABSPATH.'wp-admin/includes/class-wp-upgrader-skin.php';

    require_once ABSPATH.'wp-admin/includes/class-plugin-upgrader-skin.php';

    require_once ABSPATH.'wp-admin/includes/class-theme-upgrader-skin.php';

    require_once ABSPATH.'wp-admin/includes/class-bulk-upgrader-skin.php';

    require_once ABSPATH.'wp-admin/includes/class-bulk-plugin-upgrader-skin.php';

    require_once ABSPATH.'wp-admin/includes/class-bulk-theme-upgrader-skin.php';

    require_once ABSPATH.'wp-admin/includes/class-plugin-installer-skin.php';

    require_once ABSPATH.'wp-admin/includes/class-theme-installer-skin.php';

    require_once ABSPATH.'wp-admin/includes/class-language-pack-upgrader-skin.php';

    require_once ABSPATH.'wp-admin/includes/class-automatic-upgrader-skin.php';

    require_once ABSPATH.'wp-admin/includes/class-wp-ajax-upgrader-skin.php';

    #[AllowDynamicProperties]
    class WP_Upgrader
    {
        public $strings = [];

        public $skin = null;

        public $result = [];

        public $update_count = 0;

        public $update_current = 0;

        private $temp_backups = [];

        private $temp_restores = [];

        public function __construct($skin = null)
        {
            if(null === $skin)
            {
                $this->skin = new WP_Upgrader_Skin();
            }
            else
            {
                $this->skin = $skin;
            }
        }

        public static function create_lock($lock_name, $release_timeout = null)
        {
            global $wpdb;
            if(! $release_timeout)
            {
                $release_timeout = HOUR_IN_SECONDS;
            }
            $lock_option = $lock_name.'.lock';

            // Try to lock.
            $lock_result = $wpdb->query($wpdb->prepare("INSERT IGNORE INTO `$wpdb->options` ( `option_name`, `option_value`, `autoload` ) VALUES (%s, %s, 'no') /* LOCK */", $lock_option, time()));

            if(! $lock_result)
            {
                $lock_result = get_option($lock_option);

                // If a lock couldn't be created, and there isn't a lock, bail.
                // Check to see if the lock is still valid. If it is, bail.
                if(! $lock_result || $lock_result > (time() - $release_timeout))
                {
                    return false;
                }

                // There must exist an expired lock, clear it and re-gain it.
                WP_Upgrader::release_lock($lock_name);

                return WP_Upgrader::create_lock($lock_name, $release_timeout);
            }

            // Update the lock, as by this point we've definitely got a lock, just need to fire the actions.
            update_option($lock_option, time());

            return true;
        }

        public static function release_lock($lock_name)
        {
            return delete_option($lock_name.'.lock');
        }

        public function init()
        {
            $this->skin->set_upgrader($this);
            $this->generic_strings();

            if(! wp_installing())
            {
                $this->schedule_temp_backup_cleanup();
            }
        }

        public function generic_strings()
        {
            $this->strings['bad_request'] = __('Invalid data provided.');
            $this->strings['fs_unavailable'] = __('Could not access filesystem.');
            $this->strings['fs_error'] = __('Filesystem error.');
            $this->strings['fs_no_root_dir'] = __('Unable to locate WordPress root directory.');
            /* translators: %s: Directory name. */
            $this->strings['fs_no_content_dir'] = sprintf(__('Unable to locate WordPress content directory (%s).'), 'wp-content');
            $this->strings['fs_no_plugins_dir'] = __('Unable to locate WordPress plugin directory.');
            $this->strings['fs_no_themes_dir'] = __('Unable to locate WordPress theme directory.');
            /* translators: %s: Directory name. */
            $this->strings['fs_no_folder'] = __('Unable to locate needed folder (%s).');

            $this->strings['download_failed'] = __('Download failed.');
            $this->strings['installing_package'] = __('Installing the latest version&#8230;');
            $this->strings['no_files'] = __('The package contains no files.');
            $this->strings['folder_exists'] = __('Destination folder already exists.');
            $this->strings['mkdir_failed'] = __('Could not create directory.');
            $this->strings['incompatible_archive'] = __('The package could not be installed.');
            $this->strings['files_not_writable'] = __('The update cannot be installed because some files could not be copied. This is usually due to inconsistent file permissions.');

            $this->strings['maintenance_start'] = __('Enabling Maintenance mode&#8230;');
            $this->strings['maintenance_end'] = __('Disabling Maintenance mode&#8230;');

            /* translators: %s: upgrade-temp-backup */
            $this->strings['temp_backup_mkdir_failed'] = sprintf(__('Could not create the %s directory.'), 'upgrade-temp-backup');
            /* translators: %s: upgrade-temp-backup */
            $this->strings['temp_backup_move_failed'] = sprintf(__('Could not move the old version to the %s directory.'), 'upgrade-temp-backup');
            /* translators: %s: The plugin or theme slug. */
            $this->strings['temp_backup_restore_failed'] = __('Could not restore the original version of %s.');
            /* translators: %s: The plugin or theme slug. */
            $this->strings['temp_backup_delete_failed'] = __('Could not delete the temporary backup directory for %s.');
        }

        protected function schedule_temp_backup_cleanup()
        {
            if(false === wp_next_scheduled('wp_delete_temp_updater_backups'))
            {
                wp_schedule_event(time(), 'weekly', 'wp_delete_temp_updater_backups');
            }
        }

        public function run($options)
        {
            $defaults = [
                'package' => '',
                // Please always pass this.
                'destination' => '',
                // ...and this.
                'clear_destination' => false,
                'clear_working' => true,
                'abort_if_destination_exists' => true,
                // Abort if the destination directory exists. Pass clear_destination as false please.
                'is_multi' => false,
                'hook_extra' => [],
                // Pass any extra $hook_extra args here, this will be passed to any hooked filters.
            ];

            $options = wp_parse_args($options, $defaults);

            $options = apply_filters('upgrader_package_options', $options);

            if(! $options['is_multi'])
            { // Call $this->header separately if running multiple times.
                $this->skin->header();
            }

            // Connect to the filesystem first.
            $res = $this->fs_connect([WP_CONTENT_DIR, $options['destination']]);
            // Mainly for non-connected filesystem.
            if(! $res)
            {
                if(! $options['is_multi'])
                {
                    $this->skin->footer();
                }

                return false;
            }

            $this->skin->before();

            if(is_wp_error($res))
            {
                $this->skin->error($res);
                $this->skin->after();
                if(! $options['is_multi'])
                {
                    $this->skin->footer();
                }

                return $res;
            }

            /*
             * Download the package. Note: If the package is the full path
             * to an existing local file, it will be returned untouched.
             */
            $download = $this->download_package($options['package'], true, $options['hook_extra']);

            /*
             * Allow for signature soft-fail.
             * WARNING: This may be removed in the future.
             */
            if(is_wp_error($download) && $download->get_error_data('softfail-filename'))
            {
                // Don't output the 'no signature could be found' failure message for now.
                if('signature_verification_no_signature' !== $download->get_error_code() || WP_DEBUG)
                {
                    // Output the failure error as a normal feedback, and not as an error.
                    $this->skin->feedback($download->get_error_message());

                    // Report this failure back to WordPress.org for debugging purposes.
                    wp_version_check([
                                         'signature_failure_code' => $download->get_error_code(),
                                         'signature_failure_data' => $download->get_error_data(),
                                     ]);
                }

                // Pretend this error didn't happen.
                $download = $download->get_error_data('softfail-filename');
            }

            if(is_wp_error($download))
            {
                $this->skin->error($download);
                $this->skin->after();
                if(! $options['is_multi'])
                {
                    $this->skin->footer();
                }

                return $download;
            }

            $delete_package = ($download !== $options['package']); // Do not delete a "local" file.

            // Unzips the file into a temporary directory.
            $working_dir = $this->unpack_package($download, $delete_package);
            if(is_wp_error($working_dir))
            {
                $this->skin->error($working_dir);
                $this->skin->after();
                if(! $options['is_multi'])
                {
                    $this->skin->footer();
                }

                return $working_dir;
            }

            // With the given options, this installs it to the destination directory.
            $result = $this->install_package([
                                                 'source' => $working_dir,
                                                 'destination' => $options['destination'],
                                                 'clear_destination' => $options['clear_destination'],
                                                 'abort_if_destination_exists' => $options['abort_if_destination_exists'],
                                                 'clear_working' => $options['clear_working'],
                                                 'hook_extra' => $options['hook_extra'],
                                             ]);

            $result = apply_filters('upgrader_install_package_result', $result, $options['hook_extra']);

            $this->skin->set_result($result);

            if(is_wp_error($result))
            {
                if(! empty($options['hook_extra']['temp_backup']))
                {
                    $this->temp_restores[] = $options['hook_extra']['temp_backup'];

                    /*
                     * Restore the backup on shutdown.
                     * Actions running on `shutdown` are immune to PHP timeouts,
                     * so in case the failure was due to a PHP timeout,
                     * it will still be able to properly restore the previous version.
                     */
                    add_action('shutdown', [$this, 'restore_temp_backup']);
                }
                $this->skin->error($result);

                if(! method_exists($this->skin, 'hide_process_failed') || ! $this->skin->hide_process_failed($result))
                {
                    $this->skin->feedback('process_failed');
                }
            }
            else
            {
                // Installation succeeded.
                $this->skin->feedback('process_success');
            }

            $this->skin->after();

            // Clean up the backup kept in the temporary backup directory.
            if(! empty($options['hook_extra']['temp_backup']))
            {
                // Delete the backup on `shutdown` to avoid a PHP timeout.
                add_action('shutdown', [$this, 'delete_temp_backup'], 100, 0);
            }

            if(! $options['is_multi'])
            {
                do_action('upgrader_process_complete', $this, $options['hook_extra']);

                $this->skin->footer();
            }

            return $result;
        }

        public function fs_connect($directories = [], $allow_relaxed_file_ownership = false)
        {
            global $wp_filesystem;

            $credentials = $this->skin->request_filesystem_credentials(false, $directories[0], $allow_relaxed_file_ownership);
            if(false === $credentials)
            {
                return false;
            }

            if(! WP_Filesystem($credentials, $directories[0], $allow_relaxed_file_ownership))
            {
                $error = true;
                if(is_object($wp_filesystem) && $wp_filesystem->errors->has_errors())
                {
                    $error = $wp_filesystem->errors;
                }
                // Failed to connect. Error and request again.
                $this->skin->request_filesystem_credentials($error, $directories[0], $allow_relaxed_file_ownership);

                return false;
            }

            if(! is_object($wp_filesystem))
            {
                return new WP_Error('fs_unavailable', $this->strings['fs_unavailable']);
            }

            if(is_wp_error($wp_filesystem->errors) && $wp_filesystem->errors->has_errors())
            {
                return new WP_Error('fs_error', $this->strings['fs_error'], $wp_filesystem->errors);
            }

            foreach((array) $directories as $dir)
            {
                switch($dir)
                {
                    case ABSPATH:
                        if(! $wp_filesystem->abspath())
                        {
                            return new WP_Error('fs_no_root_dir', $this->strings['fs_no_root_dir']);
                        }
                        break;
                    case WP_CONTENT_DIR:
                        if(! $wp_filesystem->wp_content_dir())
                        {
                            return new WP_Error('fs_no_content_dir', $this->strings['fs_no_content_dir']);
                        }
                        break;
                    case WP_PLUGIN_DIR:
                        if(! $wp_filesystem->wp_plugins_dir())
                        {
                            return new WP_Error('fs_no_plugins_dir', $this->strings['fs_no_plugins_dir']);
                        }
                        break;
                    case get_theme_root():
                        if(! $wp_filesystem->wp_themes_dir())
                        {
                            return new WP_Error('fs_no_themes_dir', $this->strings['fs_no_themes_dir']);
                        }
                        break;
                    default:
                        if(! $wp_filesystem->find_folder($dir))
                        {
                            return new WP_Error('fs_no_folder', sprintf($this->strings['fs_no_folder'], esc_html(basename($dir))));
                        }
                        break;
                }
            }

            return true;
        }

        public function download_package($package, $check_signatures = false, $hook_extra = [])
        {
            $reply = apply_filters('upgrader_pre_download', false, $package, $this, $hook_extra);
            if(false !== $reply)
            {
                return $reply;
            }

            if(! preg_match('!^(http|https|ftp)://!i', $package) && file_exists($package))
            { // Local file or remote?
                return $package; // Must be a local file.
            }

            if(empty($package))
            {
                return new WP_Error('no_package', $this->strings['no_package']);
            }

            $this->skin->feedback('downloading_package', $package);

            $download_file = download_url($package, 300, $check_signatures);

            if(is_wp_error($download_file) && ! $download_file->get_error_data('softfail-filename'))
            {
                return new WP_Error('download_failed', $this->strings['download_failed'], $download_file->get_error_message());
            }

            return $download_file;
        }

        public function unpack_package($package, $delete_package = true)
        {
            global $wp_filesystem;

            $this->skin->feedback('unpack_package');

            if(! $wp_filesystem->wp_content_dir())
            {
                return new WP_Error('fs_no_content_dir', $this->strings['fs_no_content_dir']);
            }

            $upgrade_folder = $wp_filesystem->wp_content_dir().'upgrade/';

            // Clean up contents of upgrade directory beforehand.
            $upgrade_files = $wp_filesystem->dirlist($upgrade_folder);
            if(! empty($upgrade_files))
            {
                foreach($upgrade_files as $file)
                {
                    $wp_filesystem->delete($upgrade_folder.$file['name'], true);
                }
            }

            // We need a working directory - strip off any .tmp or .zip suffixes.
            $working_dir = $upgrade_folder.basename(basename($package, '.tmp'), '.zip');

            // Clean up working directory.
            if($wp_filesystem->is_dir($working_dir))
            {
                $wp_filesystem->delete($working_dir, true);
            }

            // Unzip package to working directory.
            $result = unzip_file($package, $working_dir);

            // Once extracted, delete the package if required.
            if($delete_package)
            {
                unlink($package);
            }

            if(is_wp_error($result))
            {
                $wp_filesystem->delete($working_dir, true);
                if('incompatible_archive' === $result->get_error_code())
                {
                    return new WP_Error('incompatible_archive', $this->strings['incompatible_archive'], $result->get_error_data());
                }

                return $result;
            }

            return $working_dir;
        }

        public function install_package($args = [])
        {
            global $wp_filesystem, $wp_theme_directories;

            $defaults = [
                'source' => '', // Please always pass this.
                'destination' => '', // ...and this.
                'clear_destination' => false,
                'clear_working' => false,
                'abort_if_destination_exists' => true,
                'hook_extra' => [],
            ];

            $args = wp_parse_args($args, $defaults);

            // These were previously extract()'d.
            $source = $args['source'];
            $destination = $args['destination'];
            $clear_destination = $args['clear_destination'];

            if(function_exists('set_time_limit'))
            {
                set_time_limit(300);
            }

            if(empty($source) || empty($destination))
            {
                return new WP_Error('bad_request', $this->strings['bad_request']);
            }
            $this->skin->feedback('installing_package');

            $res = apply_filters('upgrader_pre_install', true, $args['hook_extra']);

            if(is_wp_error($res))
            {
                return $res;
            }

            // Retain the original source and destinations.
            $remote_source = $args['source'];
            $local_destination = $destination;

            $source_files = array_keys($wp_filesystem->dirlist($remote_source));
            $remote_destination = $wp_filesystem->find_folder($local_destination);

            // Locate which directory to copy to the new folder. This is based on the actual folder holding the files.
            if(1 === count($source_files) && $wp_filesystem->is_dir(trailingslashit($args['source']).$source_files[0].'/'))
            {
                // Only one folder? Then we want its contents.
                $source = trailingslashit($args['source']).trailingslashit($source_files[0]);
            }
            elseif(0 === count($source_files))
            {
                // There are no files?
                return new WP_Error('incompatible_archive_empty', $this->strings['incompatible_archive'], $this->strings['no_files']);
            }
            else
            {
                /*
                 * It's only a single file, the upgrader will use the folder name of this file as the destination folder.
                 * Folder name is based on zip filename.
                 */
                $source = trailingslashit($args['source']);
            }

            $source = apply_filters('upgrader_source_selection', $source, $remote_source, $this, $args['hook_extra']);

            if(is_wp_error($source))
            {
                return $source;
            }

            if(! empty($args['hook_extra']['temp_backup']))
            {
                $temp_backup = $this->move_to_temp_backup_dir($args['hook_extra']['temp_backup']);

                if(is_wp_error($temp_backup))
                {
                    return $temp_backup;
                }

                $this->temp_backups[] = $args['hook_extra']['temp_backup'];
            }

            // Has the source location changed? If so, we need a new source_files list.
            if($source !== $remote_source)
            {
                $source_files = array_keys($wp_filesystem->dirlist($source));
            }

            /*
             * Protection against deleting files in any important base directories.
             * Theme_Upgrader & Plugin_Upgrader also trigger this, as they pass the
             * destination directory (WP_PLUGIN_DIR / wp-content/themes) intending
             * to copy the directory into the directory, whilst they pass the source
             * as the actual files to copy.
             */
            $protected_directories = [ABSPATH, WP_CONTENT_DIR, WP_PLUGIN_DIR, WP_CONTENT_DIR.'/themes'];

            if(is_array($wp_theme_directories))
            {
                $protected_directories = array_merge($protected_directories, $wp_theme_directories);
            }

            if(in_array($destination, $protected_directories, true))
            {
                $remote_destination = trailingslashit($remote_destination).trailingslashit(basename($source));
                $destination = trailingslashit($destination).trailingslashit(basename($source));
            }

            if($clear_destination)
            {
                // We're going to clear the destination if there's something there.
                $this->skin->feedback('remove_old');

                $removed = $this->clear_destination($remote_destination);

                $removed = apply_filters('upgrader_clear_destination', $removed, $local_destination, $remote_destination, $args['hook_extra']);

                if(is_wp_error($removed))
                {
                    return $removed;
                }
            }
            elseif($args['abort_if_destination_exists'] && $wp_filesystem->exists($remote_destination))
            {
                /*
                 * If we're not clearing the destination folder and something exists there already, bail.
                 * But first check to see if there are actually any files in the folder.
                 */
                $_files = $wp_filesystem->dirlist($remote_destination);
                if(! empty($_files))
                {
                    $wp_filesystem->delete($remote_source, true); // Clear out the source files.

                    return new WP_Error('folder_exists', $this->strings['folder_exists'], $remote_destination);
                }
            }

            /*
             * If 'clear_working' is false, the source should not be removed, so use copy_dir() instead.
             *
             * Partial updates, like language packs, may want to retain the destination.
             * If the destination exists or has contents, this may be a partial update,
             * and the destination should not be removed, so use copy_dir() instead.
             */
            if(
                $args['clear_working'] && (// Destination does not exist or has no contents.
                    ! $wp_filesystem->exists($remote_destination) || empty($wp_filesystem->dirlist($remote_destination)))
            )
            {
                $result = move_dir($source, $remote_destination, true);
            }
            else
            {
                // Create destination if needed.
                if(! $wp_filesystem->exists($remote_destination) && ! $wp_filesystem->mkdir($remote_destination, FS_CHMOD_DIR))
                {
                    return new WP_Error('mkdir_failed_destination', $this->strings['mkdir_failed'], $remote_destination);
                }
                $result = copy_dir($source, $remote_destination);
            }

            // Clear the working directory?
            if($args['clear_working'])
            {
                $wp_filesystem->delete($remote_source, true);
            }

            if(is_wp_error($result))
            {
                return $result;
            }

            $destination_name = basename(str_replace($local_destination, '', $destination));
            if('.' === $destination_name)
            {
                $destination_name = '';
            }

            $this->result = compact('source', 'source_files', 'destination', 'destination_name', 'local_destination', 'remote_destination', 'clear_destination');

            $res = apply_filters('upgrader_post_install', true, $args['hook_extra'], $this->result);

            if(is_wp_error($res))
            {
                $this->result = $res;

                return $res;
            }

            // Bombard the calling function will all the info which we've just used.
            return $this->result;
        }

        public function move_to_temp_backup_dir($args)
        {
            global $wp_filesystem;

            /*
             * Skip any plugin that has "." as its slug.
             * A slug of "." will result in a `$src` value ending in a period.
             *
             * On Windows, this will cause the 'plugins' folder to be moved,
             * and will cause a failure when attempting to call `mkdir()`.
             */
            if(empty($args['slug']) || empty($args['src']) || empty($args['dir']) || '.' === $args['slug'])
            {
                return false;
            }

            if(! $wp_filesystem->wp_content_dir())
            {
                return new WP_Error('fs_no_content_dir', $this->strings['fs_no_content_dir']);
            }

            $dest_dir = $wp_filesystem->wp_content_dir().'upgrade-temp-backup/';
            $sub_dir = $dest_dir.$args['dir'].'/';

            // Create the temporary backup directory if it does not exist.
            if(! $wp_filesystem->is_dir($sub_dir))
            {
                if(! $wp_filesystem->is_dir($dest_dir))
                {
                    $wp_filesystem->mkdir($dest_dir, FS_CHMOD_DIR);
                }

                if(! $wp_filesystem->mkdir($sub_dir, FS_CHMOD_DIR))
                {
                    // Could not create the backup directory.
                    return new WP_Error('fs_temp_backup_mkdir', $this->strings['temp_backup_mkdir_failed']);
                }
            }

            $src_dir = $wp_filesystem->find_folder($args['src']);
            $src = trailingslashit($src_dir).$args['slug'];
            $dest = $dest_dir.trailingslashit($args['dir']).$args['slug'];

            // Delete the temporary backup directory if it already exists.
            if($wp_filesystem->is_dir($dest))
            {
                $wp_filesystem->delete($dest, true);
            }

            // Move to the temporary backup directory.
            $result = move_dir($src, $dest, true);
            if(is_wp_error($result))
            {
                return new WP_Error('fs_temp_backup_move', $this->strings['temp_backup_move_failed']);
            }

            return true;
        }

        public function clear_destination($remote_destination)
        {
            global $wp_filesystem;

            $files = $wp_filesystem->dirlist($remote_destination, true, true);

            // False indicates that the $remote_destination doesn't exist.
            if(false === $files)
            {
                return true;
            }

            // Flatten the file list to iterate over.
            $files = $this->flatten_dirlist($files);

            // Check all files are writable before attempting to clear the destination.
            $unwritable_files = [];

            // Check writability.
            foreach($files as $filename => $file_details)
            {
                if(! $wp_filesystem->is_writable($remote_destination.$filename))
                {
                    // Attempt to alter permissions to allow writes and try again.
                    $wp_filesystem->chmod($remote_destination.$filename, ('d' === $file_details['type'] ? FS_CHMOD_DIR : FS_CHMOD_FILE));
                    if(! $wp_filesystem->is_writable($remote_destination.$filename))
                    {
                        $unwritable_files[] = $filename;
                    }
                }
            }

            if(! empty($unwritable_files))
            {
                return new WP_Error('files_not_writable', $this->strings['files_not_writable'], implode(', ', $unwritable_files));
            }

            if(! $wp_filesystem->delete($remote_destination, true))
            {
                return new WP_Error('remove_old_failed', $this->strings['remove_old_failed']);
            }

            return true;
        }

        protected function flatten_dirlist($nested_files, $path = '')
        {
            $files = [];

            foreach($nested_files as $name => $details)
            {
                $files[$path.$name] = $details;

                // Append children recursively.
                if(! empty($details['files']))
                {
                    $children = $this->flatten_dirlist($details['files'], $path.$name.'/');

                    // Merge keeping possible numeric keys, which array_merge() will reindex from 0..n.
                    $files = $files + $children;
                }
            }

            return $files;
        }

        public function maintenance_mode($enable = false)
        {
            global $wp_filesystem;
            $file = $wp_filesystem->abspath().'.maintenance';
            if($enable)
            {
                $this->skin->feedback('maintenance_start');
                // Create maintenance file to signal that we are upgrading.
                $maintenance_string = '<?php $upgrading = '.time().'; ?>';
                $wp_filesystem->delete($file);
                $wp_filesystem->put_contents($file, $maintenance_string, FS_CHMOD_FILE);
            }
            elseif(! $enable && $wp_filesystem->exists($file))
            {
                $this->skin->feedback('maintenance_end');
                $wp_filesystem->delete($file);
            }
        }

        public function restore_temp_backup()
        {
            global $wp_filesystem;

            $errors = new WP_Error();

            foreach($this->temp_restores as $args)
            {
                if(empty($args['slug']) || empty($args['src']) || empty($args['dir']))
                {
                    return false;
                }

                if(! $wp_filesystem->wp_content_dir())
                {
                    $errors->add('fs_no_content_dir', $this->strings['fs_no_content_dir']);

                    return $errors;
                }

                $src = $wp_filesystem->wp_content_dir().'upgrade-temp-backup/'.$args['dir'].'/'.$args['slug'];
                $dest_dir = $wp_filesystem->find_folder($args['src']);
                $dest = trailingslashit($dest_dir).$args['slug'];

                if($wp_filesystem->is_dir($src))
                {
                    // Cleanup.
                    if($wp_filesystem->is_dir($dest) && ! $wp_filesystem->delete($dest, true))
                    {
                        $errors->add('fs_temp_backup_delete', sprintf($this->strings['temp_backup_restore_failed'], $args['slug']));
                        continue;
                    }

                    // Move it.
                    $result = move_dir($src, $dest, true);
                    if(is_wp_error($result))
                    {
                        $errors->add('fs_temp_backup_delete', sprintf($this->strings['temp_backup_restore_failed'], $args['slug']));
                        continue;
                    }
                }
            }

            if($errors->has_errors())
            {
                return $errors;
            }

            return true;
        }

        public function delete_temp_backup()
        {
            global $wp_filesystem;

            $errors = new WP_Error();

            foreach($this->temp_backups as $args)
            {
                if(empty($args['slug']) || empty($args['dir']))
                {
                    return false;
                }

                if(! $wp_filesystem->wp_content_dir())
                {
                    $errors->add('fs_no_content_dir', $this->strings['fs_no_content_dir']);

                    return $errors;
                }

                $temp_backup_dir = $wp_filesystem->wp_content_dir()."upgrade-temp-backup/{$args['dir']}/{$args['slug']}";

                if(! $wp_filesystem->delete($temp_backup_dir, true))
                {
                    $errors->add('temp_backup_delete_failed', sprintf($this->strings['temp_backup_delete_failed'], $args['slug']));
                    continue;
                }
            }

            if($errors->has_errors())
            {
                return $errors;
            }

            return true;
        }
    }

    require_once ABSPATH.'wp-admin/includes/class-plugin-upgrader.php';

    require_once ABSPATH.'wp-admin/includes/class-theme-upgrader.php';

    require_once ABSPATH.'wp-admin/includes/class-language-pack-upgrader.php';

    require_once ABSPATH.'wp-admin/includes/class-core-upgrader.php';

    require_once ABSPATH.'wp-admin/includes/class-file-upload-upgrader.php';

    require_once ABSPATH.'wp-admin/includes/class-wp-automatic-updater.php';
