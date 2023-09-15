<?php

    #[AllowDynamicProperties]
    class WP_Site_Health_Auto_Updates
    {
        public function __construct()
        {
            require_once ABSPATH.'wp-admin/includes/class-wp-upgrader.php';
        }

        public function run_tests()
        {
            $tests = [
                $this->test_constants('WP_AUTO_UPDATE_CORE', [
                    true,
                    'beta',
                    'rc',
                    'development',
                    'branch-development',
                    'minor',
                ]),
                $this->test_wp_version_check_attached(),
                $this->test_filters_automatic_updater_disabled(),
                $this->test_wp_automatic_updates_disabled(),
                $this->test_if_failed_update(),
                $this->test_vcs_abspath(),
                $this->test_check_wp_filesystem_method(),
                $this->test_all_files_writable(),
                $this->test_accepts_dev_updates(),
                $this->test_accepts_minor_updates(),
            ];

            $tests = array_filter($tests);
            $tests = array_map(static function($test)
            {
                $test = (object) $test;

                if(empty($test->severity))
                {
                    $test->severity = 'warning';
                }

                return $test;
            }, $tests);

            return $tests;
        }

        public function test_constants($constant, $value)
        {
            $acceptable_values = (array) $value;

            if(defined($constant) && ! in_array(constant($constant), $acceptable_values, true))
            {
                return [
                    'description' => sprintf(/* translators: 1: Name of the constant used. 2: Value of the constant used. */ __('The %1$s constant is defined as %2$s'), "<code>$constant</code>", '<code>'.esc_html(var_export(constant($constant), true)).'</code>'),
                    'severity' => 'fail',
                ];
            }
        }

        public function test_wp_version_check_attached()
        {
            if((! is_multisite() || is_main_site() && is_network_admin()) && ! has_filter('wp_version_check', 'wp_version_check'))
            {
                return [
                    'description' => sprintf(/* translators: %s: Name of the filter used. */ __('A plugin has prevented updates by disabling %s.'), '<code>wp_version_check()</code>'),
                    'severity' => 'fail',
                ];
            }
        }

        public function test_filters_automatic_updater_disabled()
        {
            if(apply_filters('automatic_updater_disabled', false))
            {
                return [
                    'description' => sprintf(/* translators: %s: Name of the filter used. */ __('The %s filter is enabled.'), '<code>automatic_updater_disabled</code>'),
                    'severity' => 'fail',
                ];
            }
        }

        public function test_wp_automatic_updates_disabled()
        {
            if(! class_exists('WP_Automatic_Updater'))
            {
                require_once ABSPATH.'wp-admin/includes/class-wp-automatic-updater.php';
            }

            $auto_updates = new WP_Automatic_Updater();

            if(! $auto_updates->is_disabled())
            {
                return false;
            }

            return [
                'description' => __('All automatic updates are disabled.'),
                'severity' => 'fail',
            ];
        }

        public function test_if_failed_update()
        {
            $failed = get_site_option('auto_core_update_failed');

            if(! $failed)
            {
                return false;
            }

            if(! empty($failed['critical']))
            {
                $description = __('A previous automatic background update ended with a critical failure, so updates are now disabled.');
                $description .= ' '.__('You would have received an email because of this.');
                $description .= ' '.__("When you've been able to update using the \"Update now\" button on Dashboard > Updates, this error will be cleared for future update attempts.");
                $description .= ' '.sprintf(/* translators: %s: Code of error shown. */ __('The error code was %s.'), '<code>'.$failed['error_code'].'</code>');

                return [
                    'description' => $description,
                    'severity' => 'warning',
                ];
            }

            $description = __('A previous automatic background update could not occur.');
            if(empty($failed['retry']))
            {
                $description .= ' '.__('You would have received an email because of this.');
            }

            $description .= ' '.__('Another attempt will be made with the next release.');
            $description .= ' '.sprintf(/* translators: %s: Code of error shown. */ __('The error code was %s.'), '<code>'.$failed['error_code'].'</code>');

            return [
                'description' => $description,
                'severity' => 'warning',
            ];
        }

        public function test_vcs_abspath()
        {
            $context_dirs = [ABSPATH];
            $vcs_dirs = ['.svn', '.git', '.hg', '.bzr'];
            $check_dirs = [];

            foreach($context_dirs as $context_dir)
            {
                // Walk up from $context_dir to the root.
                do
                {
                    $check_dirs[] = $context_dir;

                    // Once we've hit '/' or 'C:\', we need to stop. dirname will keep returning the input here.
                    if(dirname($context_dir) === $context_dir)
                    {
                        break;
                    }
                    // Continue one level at a time.
                }
                while($context_dir = dirname($context_dir));
            }

            $check_dirs = array_unique($check_dirs);

            // Search all directories we've found for evidence of version control.
            foreach($vcs_dirs as $vcs_dir)
            {
                foreach($check_dirs as $check_dir)
                {
                    // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition,Squiz.PHP.DisallowMultipleAssignments
                    if($checkout = @is_dir(rtrim($check_dir, '\\/')."/$vcs_dir"))
                    {
                        break 2;
                    }
                }
            }

            if($checkout && ! apply_filters('automatic_updates_is_vcs_checkout', true, ABSPATH))
            {
                return [
                    'description' => sprintf(/* translators: 1: Folder name. 2: Version control directory. 3: Filter name. */ __('The folder %1$s was detected as being under version control (%2$s), but the %3$s filter is allowing updates.'), '<code>'.$check_dir.'</code>', "<code>$vcs_dir</code>", '<code>automatic_updates_is_vcs_checkout</code>'),
                    'severity' => 'info',
                ];
            }

            if($checkout)
            {
                return [
                    'description' => sprintf(/* translators: 1: Folder name. 2: Version control directory. */ __('The folder %1$s was detected as being under version control (%2$s).'), '<code>'.$check_dir.'</code>', "<code>$vcs_dir</code>"),
                    'severity' => 'warning',
                ];
            }

            return [
                'description' => __('No version control systems were detected.'),
                'severity' => 'pass',
            ];
        }

        public function test_check_wp_filesystem_method()
        {
            // Make sure the `request_filesystem_credentials()` function is available during our REST API call.
            if(! function_exists('request_filesystem_credentials'))
            {
                require_once ABSPATH.'wp-admin/includes/file.php';
            }

            $skin = new Automatic_Upgrader_Skin();
            $success = $skin->request_filesystem_credentials(false, ABSPATH);

            if(! $success)
            {
                $description = __('Your installation of WordPress prompts for FTP credentials to perform updates.');
                $description .= ' '.__('(Your site is performing updates over FTP due to file ownership. Talk to your hosting company.)');

                return [
                    'description' => $description,
                    'severity' => 'fail',
                ];
            }

            return [
                'description' => __('Your installation of WordPress does not require FTP credentials to perform updates.'),
                'severity' => 'pass',
            ];
        }

        public function test_all_files_writable()
        {
            global $wp_filesystem;

            require ABSPATH.WPINC.'/version.php'; // $wp_version; // x.y.z

            $skin = new Automatic_Upgrader_Skin();
            $success = $skin->request_filesystem_credentials(false, ABSPATH);

            if(! $success)
            {
                return false;
            }

            WP_Filesystem();

            if('direct' !== $wp_filesystem->method)
            {
                return false;
            }

            // Make sure the `get_core_checksums()` function is available during our REST API call.
            if(! function_exists('get_core_checksums'))
            {
                require_once ABSPATH.'wp-admin/includes/update.php';
            }

            $checksums = get_core_checksums($wp_version, 'en_US');
            $dev = (str_contains($wp_version, '-'));
            // Get the last stable version's files and test against that.
            if(! $checksums && $dev)
            {
                $checksums = get_core_checksums((float) $wp_version - 0.1, 'en_US');
            }

            // There aren't always checksums for development releases, so just skip the test if we still can't find any.
            if(! $checksums && $dev)
            {
                return false;
            }

            if(! $checksums)
            {
                $description = sprintf(/* translators: %s: WordPress version. */ __("Couldn't retrieve a list of the checksums for WordPress %s."), $wp_version);
                $description .= ' '.__('This could mean that connections are failing to WordPress.org.');

                return [
                    'description' => $description,
                    'severity' => 'warning',
                ];
            }

            $unwritable_files = [];
            foreach(array_keys($checksums) as $file)
            {
                if(str_starts_with($file, 'wp-content'))
                {
                    continue;
                }
                if(! file_exists(ABSPATH.$file))
                {
                    continue;
                }
                if(! is_writable(ABSPATH.$file))
                {
                    $unwritable_files[] = $file;
                }
            }

            if($unwritable_files)
            {
                if(count($unwritable_files) > 20)
                {
                    $unwritable_files = array_slice($unwritable_files, 0, 20);
                    $unwritable_files[] = '...';
                }

                return [
                    'description' => __('Some files are not writable by WordPress:').' <ul><li>'.implode('</li><li>', $unwritable_files).'</li></ul>',
                    'severity' => 'fail',
                ];
            }
            else
            {
                return [
                    'description' => __('All of your WordPress files are writable.'),
                    'severity' => 'pass',
                ];
            }
        }

        public function test_accepts_dev_updates()
        {
            require ABSPATH.WPINC.'/version.php'; // $wp_version; // x.y.z
            // Only for dev versions.
            if(! str_contains($wp_version, '-'))
            {
                return false;
            }

            if(defined('WP_AUTO_UPDATE_CORE') && ('minor' === WP_AUTO_UPDATE_CORE || false === WP_AUTO_UPDATE_CORE))
            {
                return [
                    'description' => sprintf(/* translators: %s: Name of the constant used. */ __('WordPress development updates are blocked by the %s constant.'), '<code>WP_AUTO_UPDATE_CORE</code>'),
                    'severity' => 'fail',
                ];
            }

            if(! apply_filters('allow_dev_auto_core_updates', $wp_version))
            {
                return [
                    'description' => sprintf(/* translators: %s: Name of the filter used. */ __('WordPress development updates are blocked by the %s filter.'), '<code>allow_dev_auto_core_updates</code>'),
                    'severity' => 'fail',
                ];
            }
        }

        public function test_accepts_minor_updates()
        {
            if(defined('WP_AUTO_UPDATE_CORE') && false === WP_AUTO_UPDATE_CORE)
            {
                return [
                    'description' => sprintf(/* translators: %s: Name of the constant used. */ __('WordPress security and maintenance releases are blocked by %s.'), "<code>define( 'WP_AUTO_UPDATE_CORE', false );</code>"),
                    'severity' => 'fail',
                ];
            }

            if(! apply_filters('allow_minor_auto_core_updates', true))
            {
                return [
                    'description' => sprintf(/* translators: %s: Name of the filter used. */ __('WordPress security and maintenance releases are blocked by the %s filter.'), '<code>allow_minor_auto_core_updates</code>'),
                    'severity' => 'fail',
                ];
            }
        }
    }
