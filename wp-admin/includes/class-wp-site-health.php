<?php

    #[AllowDynamicProperties]
    class WP_Site_Health
    {
        private static $instance = null;

        public $is_mariadb = false;

        public $php_memory_limit;

        public $schedules;

        public $crons;

        public $last_missed_cron = null;

        public $last_late_cron = null;

        private $is_acceptable_mysql_version;

        private $is_recommended_mysql_version;

        private $mysql_server_version = '';

        private $mysql_required_version = '5.5';

        private $mysql_recommended_version = '5.7';

        private $mariadb_recommended_version = '10.4';

        private $timeout_missed_cron = null;

        private $timeout_late_cron = null;

        public function __construct()
        {
            $this->maybe_create_scheduled_event();

            // Save memory limit before it's affected by wp_raise_memory_limit( 'admin' ).
            $this->php_memory_limit = ini_get('memory_limit');

            $this->timeout_late_cron = 0;
            $this->timeout_missed_cron = -5 * MINUTE_IN_SECONDS;

            if(defined('DISABLE_WP_CRON') && DISABLE_WP_CRON)
            {
                $this->timeout_late_cron = -15 * MINUTE_IN_SECONDS;
                $this->timeout_missed_cron = -1 * HOUR_IN_SECONDS;
            }

            add_filter('admin_body_class', [$this, 'admin_body_class']);

            add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
            add_action('wp_site_health_scheduled_check', [$this, 'wp_cron_scheduled_check']);

            add_action('site_health_tab_content', [$this, 'show_site_health_tab']);
        }

        public function maybe_create_scheduled_event()
        {
            if(! wp_next_scheduled('wp_site_health_scheduled_check') && ! wp_installing())
            {
                wp_schedule_event(time() + DAY_IN_SECONDS, 'weekly', 'wp_site_health_scheduled_check');
            }
        }

        public function show_site_health_tab($tab)
        {
            if('debug' === $tab)
            {
                require_once ABSPATH.'wp-admin/site-health-info.php';
            }
        }

        public function enqueue_scripts()
        {
            $screen = get_current_screen();
            if('site-health' !== $screen->id && 'dashboard' !== $screen->id)
            {
                return;
            }

            $health_check_js_variables = [
                'screen' => $screen->id,
                'nonce' => [
                    'site_status' => wp_create_nonce('health-check-site-status'),
                    'site_status_result' => wp_create_nonce('health-check-site-status-result'),
                ],
                'site_status' => [
                    'direct' => [],
                    'async' => [],
                    'issues' => [
                        'good' => 0,
                        'recommended' => 0,
                        'critical' => 0,
                    ],
                ],
            ];

            $issue_counts = get_transient('health-check-site-status-result');

            if(false !== $issue_counts)
            {
                $issue_counts = json_decode($issue_counts);

                $health_check_js_variables['site_status']['issues'] = $issue_counts;
            }

            if('site-health' === $screen->id && (! isset($_GET['tab']) || empty($_GET['tab'])))
            {
                $tests = WP_Site_Health::get_tests();

                // Don't run https test on development environments.
                if($this->is_development_environment())
                {
                    unset($tests['async']['https_status']);
                }

                foreach($tests['direct'] as $test)
                {
                    if(is_string($test['test']))
                    {
                        $test_function = sprintf('get_test_%s', $test['test']);

                        if(method_exists($this, $test_function) && is_callable([$this, $test_function]))
                        {
                            $health_check_js_variables['site_status']['direct'][] = $this->perform_test([
                                                                                                            $this,
                                                                                                            $test_function,
                                                                                                        ]);
                            continue;
                        }
                    }

                    if(is_callable($test['test']))
                    {
                        $health_check_js_variables['site_status']['direct'][] = $this->perform_test($test['test']);
                    }
                }

                foreach($tests['async'] as $test)
                {
                    if(is_string($test['test']))
                    {
                        $health_check_js_variables['site_status']['async'][] = [
                            'test' => $test['test'],
                            'has_rest' => (isset($test['has_rest']) ? $test['has_rest'] : false),
                            'completed' => false,
                            'headers' => isset($test['headers']) ? $test['headers'] : [],
                        ];
                    }
                }
            }

            wp_localize_script('site-health', 'SiteHealth', $health_check_js_variables);
        }

        public static function get_tests()
        {
            $tests = [
                'direct' => [
                    'wordpress_version' => [
                        'label' => __('WordPress Version'),
                        'test' => 'wordpress_version',
                    ],
                    'plugin_version' => [
                        'label' => __('Plugin Versions'),
                        'test' => 'plugin_version',
                    ],
                    'theme_version' => [
                        'label' => __('Theme Versions'),
                        'test' => 'theme_version',
                    ],
                    'php_version' => [
                        'label' => __('PHP Version'),
                        'test' => 'php_version',
                    ],
                    'php_extensions' => [
                        'label' => __('PHP Extensions'),
                        'test' => 'php_extensions',
                    ],
                    'php_default_timezone' => [
                        'label' => __('PHP Default Timezone'),
                        'test' => 'php_default_timezone',
                    ],
                    'php_sessions' => [
                        'label' => __('PHP Sessions'),
                        'test' => 'php_sessions',
                    ],
                    'sql_server' => [
                        'label' => __('Database Server version'),
                        'test' => 'sql_server',
                    ],
                    'utf8mb4_support' => [
                        'label' => __('MySQL utf8mb4 support'),
                        'test' => 'utf8mb4_support',
                    ],
                    'ssl_support' => [
                        'label' => __('Secure communication'),
                        'test' => 'ssl_support',
                    ],
                    'scheduled_events' => [
                        'label' => __('Scheduled events'),
                        'test' => 'scheduled_events',
                    ],
                    'http_requests' => [
                        'label' => __('HTTP Requests'),
                        'test' => 'http_requests',
                    ],
                    'rest_availability' => [
                        'label' => __('REST API availability'),
                        'test' => 'rest_availability',
                        'skip_cron' => true,
                    ],
                    'debug_enabled' => [
                        'label' => __('Debugging enabled'),
                        'test' => 'is_in_debug_mode',
                    ],
                    'file_uploads' => [
                        'label' => __('File uploads'),
                        'test' => 'file_uploads',
                    ],
                    'plugin_theme_auto_updates' => [
                        'label' => __('Plugin and theme auto-updates'),
                        'test' => 'plugin_theme_auto_updates',
                    ],
                    'update_temp_backup_writable' => [
                        'label' => __('Plugin and theme temporary backup directory access'),
                        'test' => 'update_temp_backup_writable',
                    ],
                    'available_updates_disk_space' => [
                        'label' => __('Available disk space'),
                        'test' => 'available_updates_disk_space',
                    ],
                ],
                'async' => [
                    'dotorg_communication' => [
                        'label' => __('Communication with WordPress.org'),
                        'test' => rest_url('wp-site-health/v1/tests/dotorg-communication'),
                        'has_rest' => true,
                        'async_direct_test' => [WP_Site_Health::get_instance(), 'get_test_dotorg_communication'],
                    ],
                    'background_updates' => [
                        'label' => __('Background updates'),
                        'test' => rest_url('wp-site-health/v1/tests/background-updates'),
                        'has_rest' => true,
                        'async_direct_test' => [WP_Site_Health::get_instance(), 'get_test_background_updates'],
                    ],
                    'loopback_requests' => [
                        'label' => __('Loopback request'),
                        'test' => rest_url('wp-site-health/v1/tests/loopback-requests'),
                        'has_rest' => true,
                        'async_direct_test' => [WP_Site_Health::get_instance(), 'get_test_loopback_requests'],
                    ],
                    'https_status' => [
                        'label' => __('HTTPS status'),
                        'test' => rest_url('wp-site-health/v1/tests/https-status'),
                        'has_rest' => true,
                        'async_direct_test' => [WP_Site_Health::get_instance(), 'get_test_https_status'],
                    ],
                ],
            ];

            // Conditionally include Authorization header test if the site isn't protected by Basic Auth.
            if(! wp_is_site_protected_by_basic_auth())
            {
                $tests['async']['authorization_header'] = [
                    'label' => __('Authorization header'),
                    'test' => rest_url('wp-site-health/v1/tests/authorization-header'),
                    'has_rest' => true,
                    'headers' => ['Authorization' => 'Basic '.base64_encode('user:pwd')],
                    'skip_cron' => true,
                ];
            }

            // Only check for caches in production environments.
            if('production' === wp_get_environment_type())
            {
                $tests['async']['page_cache'] = [
                    'label' => __('Page cache'),
                    'test' => rest_url('wp-site-health/v1/tests/page-cache'),
                    'has_rest' => true,
                    'async_direct_test' => [WP_Site_Health::get_instance(), 'get_test_page_cache'],
                ];

                $tests['direct']['persistent_object_cache'] = [
                    'label' => __('Persistent object cache'),
                    'test' => 'persistent_object_cache',
                ];
            }

            $tests = apply_filters('site_status_tests', $tests);

            // Ensure that the filtered tests contain the required array keys.
            $tests = array_merge([
                                     'direct' => [],
                                     'async' => [],
                                 ], $tests);

            return $tests;
        }

        public static function get_instance()
        {
            if(null === self::$instance)
            {
                self::$instance = new WP_Site_Health();
            }

            return self::$instance;
        }

        public function is_development_environment()
        {
            return in_array(wp_get_environment_type(), ['development', 'local'], true);
        }

        private function perform_test($callback)
        {
            return apply_filters('site_status_test_result', call_user_func($callback));
        }

        public function check_wp_version_check_exists()
        {
            if(! is_admin() || ! is_user_logged_in() || ! current_user_can('update_core') || ! isset($_GET['health-check-test-wp_version_check']))
            {
                return;
            }

            echo(has_filter('wp_version_check', 'wp_version_check') ? 'yes' : 'no');

            die();
        }

        public function get_test_wordpress_version()
        {
            $result = [
                'label' => '',
                'status' => '',
                'badge' => [
                    'label' => __('Performance'),
                    'color' => 'blue',
                ],
                'description' => '',
                'actions' => '',
                'test' => 'wordpress_version',
            ];

            $core_current_version = get_bloginfo('version');
            $core_updates = get_core_updates();

            if(! is_array($core_updates))
            {
                $result['status'] = 'recommended';

                $result['label'] = sprintf(/* translators: %s: Your current version of WordPress. */ __('WordPress version %s'), $core_current_version);

                $result['description'] = sprintf('<p>%s</p>', __('Unable to check if any new versions of WordPress are available.'));

                $result['actions'] = sprintf('<a href="%s">%s</a>', esc_url(admin_url('update-core.php?force-check=1')), __('Check for updates manually'));
            }
            else
            {
                foreach($core_updates as $core => $update)
                {
                    if('upgrade' === $update->response)
                    {
                        $current_version = explode('.', $core_current_version);
                        $new_version = explode('.', $update->version);

                        $current_major = $current_version[0].'.'.$current_version[1];
                        $new_major = $new_version[0].'.'.$new_version[1];

                        $result['label'] = sprintf(/* translators: %s: The latest version of WordPress available. */ __('WordPress update available (%s)'), $update->version);

                        $result['actions'] = sprintf('<a href="%s">%s</a>', esc_url(admin_url('update-core.php')), __('Install the latest version of WordPress'));

                        if($current_major !== $new_major)
                        {
                            // This is a major version mismatch.
                            $result['status'] = 'recommended';
                            $result['description'] = sprintf('<p>%s</p>', __('A new version of WordPress is available.'));
                        }
                        else
                        {
                            // This is a minor version, sometimes considered more critical.
                            $result['status'] = 'critical';
                            $result['badge']['label'] = __('Security');
                            $result['description'] = sprintf('<p>%s</p>', __('A new minor update is available for your site. Because minor updates often address security, it&#8217;s important to install them.'));
                        }
                    }
                    else
                    {
                        $result['status'] = 'good';
                        $result['label'] = sprintf(/* translators: %s: The current version of WordPress installed on this site. */ __('Your version of WordPress (%s) is up to date'), $core_current_version);

                        $result['description'] = sprintf('<p>%s</p>', __('You are currently running the latest version of WordPress available, keep it up!'));
                    }
                }
            }

            return $result;
        }

        public function get_test_plugin_version()
        {
            $result = [
                'label' => __('Your plugins are all up to date'),
                'status' => 'good',
                'badge' => [
                    'label' => __('Security'),
                    'color' => 'blue',
                ],
                'description' => sprintf('<p>%s</p>', __('Plugins extend your site&#8217;s functionality with things like contact forms, ecommerce and much more. That means they have deep access to your site, so it&#8217;s vital to keep them up to date.')),
                'actions' => sprintf('<p><a href="%s">%s</a></p>', esc_url(admin_url('plugins.php')), __('Manage your plugins')),
                'test' => 'plugin_version',
            ];

            $plugins = get_plugins();
            $plugin_updates = get_plugin_updates();

            $plugins_active = 0;
            $plugins_total = 0;
            $plugins_need_update = 0;

            // Loop over the available plugins and check their versions and active state.
            foreach($plugins as $plugin_path => $plugin)
            {
                ++$plugins_total;

                if(is_plugin_active($plugin_path))
                {
                    ++$plugins_active;
                }

                if(array_key_exists($plugin_path, $plugin_updates))
                {
                    ++$plugins_need_update;
                }
            }

            // Add a notice if there are outdated plugins.
            if($plugins_need_update > 0)
            {
                $result['status'] = 'critical';

                $result['label'] = __('You have plugins waiting to be updated');

                $result['description'] .= sprintf('<p>%s</p>', sprintf(/* translators: %d: The number of outdated plugins. */ _n('Your site has %d plugin waiting to be updated.', 'Your site has %d plugins waiting to be updated.', $plugins_need_update), $plugins_need_update));

                $result['actions'] .= sprintf('<p><a href="%s">%s</a></p>', esc_url(network_admin_url('plugins.php?plugin_status=upgrade')), __('Update your plugins'));
            }
            else
            {
                if(1 === $plugins_active)
                {
                    $result['description'] .= sprintf('<p>%s</p>', __('Your site has 1 active plugin, and it is up to date.'));
                }
                elseif($plugins_active > 0)
                {
                    $result['description'] .= sprintf('<p>%s</p>', sprintf(/* translators: %d: The number of active plugins. */ _n('Your site has %d active plugin, and it is up to date.', 'Your site has %d active plugins, and they are all up to date.', $plugins_active), $plugins_active));
                }
                else
                {
                    $result['description'] .= sprintf('<p>%s</p>', __('Your site does not have any active plugins.'));
                }
            }

            // Check if there are inactive plugins.
            if($plugins_total > $plugins_active && ! is_multisite())
            {
                $unused_plugins = $plugins_total - $plugins_active;

                $result['status'] = 'recommended';

                $result['label'] = __('You should remove inactive plugins');

                $result['description'] .= sprintf('<p>%s %s</p>', sprintf(/* translators: %d: The number of inactive plugins. */ _n('Your site has %d inactive plugin.', 'Your site has %d inactive plugins.', $unused_plugins), $unused_plugins), __('Inactive plugins are tempting targets for attackers. If you are not going to use a plugin, you should consider removing it.'));

                $result['actions'] .= sprintf('<p><a href="%s">%s</a></p>', esc_url(admin_url('plugins.php?plugin_status=inactive')), __('Manage inactive plugins'));
            }

            return $result;
        }

        public function get_test_theme_version()
        {
            $result = [
                'label' => __('Your themes are all up to date'),
                'status' => 'good',
                'badge' => [
                    'label' => __('Security'),
                    'color' => 'blue',
                ],
                'description' => sprintf('<p>%s</p>', __('Themes add your site&#8217;s look and feel. It&#8217;s important to keep them up to date, to stay consistent with your brand and keep your site secure.')),
                'actions' => sprintf('<p><a href="%s">%s</a></p>', esc_url(admin_url('themes.php')), __('Manage your themes')),
                'test' => 'theme_version',
            ];

            $theme_updates = get_theme_updates();

            $themes_total = 0;
            $themes_need_updates = 0;
            $themes_inactive = 0;

            // This value is changed during processing to determine how many themes are considered a reasonable amount.
            $allowed_theme_count = 1;

            $has_default_theme = false;
            $has_unused_themes = false;
            $show_unused_themes = true;
            $using_default_theme = false;

            // Populate a list of all themes available in the install.
            $all_themes = wp_get_themes();
            $active_theme = wp_get_theme();

            // If WP_DEFAULT_THEME doesn't exist, fall back to the latest core default theme.
            $default_theme = wp_get_theme(WP_DEFAULT_THEME);
            if(! $default_theme->exists())
            {
                $default_theme = WP_Theme::get_core_default_theme();
            }

            if($default_theme)
            {
                $has_default_theme = true;

                if($active_theme->get_stylesheet() === $default_theme->get_stylesheet() || is_child_theme() && $active_theme->get_template() === $default_theme->get_template())
                {
                    $using_default_theme = true;
                }
            }

            foreach($all_themes as $theme_slug => $theme)
            {
                ++$themes_total;

                if(array_key_exists($theme_slug, $theme_updates))
                {
                    ++$themes_need_updates;
                }
            }

            // If this is a child theme, increase the allowed theme count by one, to account for the parent.
            if(is_child_theme())
            {
                ++$allowed_theme_count;
            }

            // If there's a default theme installed and not in use, we count that as allowed as well.
            if($has_default_theme && ! $using_default_theme)
            {
                ++$allowed_theme_count;
            }

            if($themes_total > $allowed_theme_count)
            {
                $has_unused_themes = true;
                $themes_inactive = ($themes_total - $allowed_theme_count);
            }

            // Check if any themes need to be updated.
            if($themes_need_updates > 0)
            {
                $result['status'] = 'critical';

                $result['label'] = __('You have themes waiting to be updated');

                $result['description'] .= sprintf('<p>%s</p>', sprintf(/* translators: %d: The number of outdated themes. */ _n('Your site has %d theme waiting to be updated.', 'Your site has %d themes waiting to be updated.', $themes_need_updates), $themes_need_updates));
            }
            else
            {
                // Give positive feedback about the site being good about keeping things up to date.
                if(1 === $themes_total)
                {
                    $result['description'] .= sprintf('<p>%s</p>', __('Your site has 1 installed theme, and it is up to date.'));
                }
                elseif($themes_total > 0)
                {
                    $result['description'] .= sprintf('<p>%s</p>', sprintf(/* translators: %d: The number of themes. */ _n('Your site has %d installed theme, and it is up to date.', 'Your site has %d installed themes, and they are all up to date.', $themes_total), $themes_total));
                }
                else
                {
                    $result['description'] .= sprintf('<p>%s</p>', __('Your site does not have any installed themes.'));
                }
            }

            if($has_unused_themes && $show_unused_themes && ! is_multisite())
            {
                // This is a child theme, so we want to be a bit more explicit in our messages.
                if($active_theme->parent())
                {
                    // Recommend removing inactive themes, except a default theme, your current one, and the parent theme.
                    $result['status'] = 'recommended';

                    $result['label'] = __('You should remove inactive themes');

                    if($using_default_theme)
                    {
                        $result['description'] .= sprintf('<p>%s %s</p>', sprintf(/* translators: %d: The number of inactive themes. */ _n('Your site has %d inactive theme.', 'Your site has %d inactive themes.', $themes_inactive), $themes_inactive), sprintf(/* translators: 1: The currently active theme. 2: The active theme's parent theme. */ __('To enhance your site&#8217;s security, you should consider removing any themes you are not using. You should keep your active theme, %1$s, and %2$s, its parent theme.'), $active_theme->name, $active_theme->parent()->name));
                    }
                    else
                    {
                        $result['description'] .= sprintf('<p>%s %s</p>', sprintf(/* translators: %d: The number of inactive themes. */ _n('Your site has %d inactive theme.', 'Your site has %d inactive themes.', $themes_inactive), $themes_inactive), sprintf(/* translators: 1: The default theme for WordPress. 2: The currently active theme. 3: The active theme's parent theme. */ __('To enhance your site&#8217;s security, you should consider removing any themes you are not using. You should keep %1$s, the default WordPress theme, %2$s, your active theme, and %3$s, its parent theme.'), $default_theme ? $default_theme->name : WP_DEFAULT_THEME, $active_theme->name, $active_theme->parent()->name));
                    }
                }
                else
                {
                    // Recommend removing all inactive themes.
                    $result['status'] = 'recommended';

                    $result['label'] = __('You should remove inactive themes');

                    if($using_default_theme)
                    {
                        $result['description'] .= sprintf('<p>%s %s</p>', sprintf(/* translators: 1: The amount of inactive themes. 2: The currently active theme. */ _n('Your site has %1$d inactive theme, other than %2$s, your active theme.', 'Your site has %1$d inactive themes, other than %2$s, your active theme.', $themes_inactive), $themes_inactive, $active_theme->name), __('You should consider removing any unused themes to enhance your site&#8217;s security.'));
                    }
                    else
                    {
                        $result['description'] .= sprintf('<p>%s %s</p>', sprintf(/* translators: 1: The amount of inactive themes. 2: The default theme for WordPress. 3: The currently active theme. */ _n('Your site has %1$d inactive theme, other than %2$s, the default WordPress theme, and %3$s, your active theme.', 'Your site has %1$d inactive themes, other than %2$s, the default WordPress theme, and %3$s, your active theme.', $themes_inactive), $themes_inactive, $default_theme ? $default_theme->name : WP_DEFAULT_THEME, $active_theme->name), __('You should consider removing any unused themes to enhance your site&#8217;s security.'));
                    }
                }
            }

            // If no default Twenty* theme exists.
            if(! $has_default_theme)
            {
                $result['status'] = 'recommended';

                $result['label'] = __('Have a default theme available');

                $result['description'] .= sprintf('<p>%s</p>', __('Your site does not have any default theme. Default themes are used by WordPress automatically if anything is wrong with your chosen theme.'));
            }

            return $result;
        }

        public function get_test_php_version()
        {
            $response = wp_check_php_version();

            $result = [
                'label' => sprintf(/* translators: %s: The current PHP version. */ __('Your site is running the current version of PHP (%s)'), PHP_VERSION),
                'status' => 'good',
                'badge' => [
                    'label' => __('Performance'),
                    'color' => 'blue',
                ],
                'description' => sprintf('<p>%s</p>', sprintf(/* translators: %s: The minimum recommended PHP version. */ __('PHP is one of the programming languages used to build WordPress. Newer versions of PHP receive regular security updates and may increase your site&#8217;s performance. The minimum recommended version of PHP is %s.'), $response ? $response['recommended_version'] : '')),
                'actions' => sprintf('<p><a href="%s" target="_blank" rel="noopener">%s<span class="screen-reader-text"> %s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a></p>', esc_url(wp_get_update_php_url()), __('Learn more about updating PHP'), /* translators: Hidden accessibility text. */ __('(opens in a new tab)')),
                'test' => 'php_version',
            ];

            // PHP is up to date.
            if(! $response || version_compare(PHP_VERSION, $response['recommended_version'], '>='))
            {
                return $result;
            }

            // The PHP version is older than the recommended version, but still receiving active support.
            if($response['is_supported'])
            {
                $result['label'] = sprintf(/* translators: %s: The server PHP version. */ __('Your site is running on an older version of PHP (%s)'), PHP_VERSION);
                $result['status'] = 'recommended';

                return $result;
            }

            /*
             * The PHP version is still receiving security fixes, but is lower than
             * the expected minimum version that will be required by WordPress in the near future.
             */
            if($response['is_secure'] && $response['is_lower_than_future_minimum'])
            {
                // The `is_secure` array key name doesn't actually imply this is a secure version of PHP. It only means it receives security updates.

                $result['label'] = sprintf(/* translators: %s: The server PHP version. */ __('Your site is running on an outdated version of PHP (%s), which soon will not be supported by WordPress.'), PHP_VERSION);

                $result['status'] = 'critical';
                $result['badge']['label'] = __('Requirements');

                return $result;
            }

            // The PHP version is only receiving security fixes.
            if($response['is_secure'])
            {
                $result['label'] = sprintf(/* translators: %s: The server PHP version. */ __('Your site is running on an older version of PHP (%s), which should be updated'), PHP_VERSION);
                $result['status'] = 'recommended';

                return $result;
            }

            // No more security updates for the PHP version, and lower than the expected minimum version required by WordPress.
            if($response['is_lower_than_future_minimum'])
            {
                $message = sprintf(/* translators: %s: The server PHP version. */ __('Your site is running on an outdated version of PHP (%s), which does not receive security updates and soon will not be supported by WordPress.'), PHP_VERSION);
            }
            else
            {
                // No more security updates for the PHP version, must be updated.
                $message = sprintf(/* translators: %s: The server PHP version. */ __('Your site is running on an outdated version of PHP (%s), which does not receive security updates. It should be updated.'), PHP_VERSION);
            }

            $result['label'] = $message;
            $result['status'] = 'critical';

            $result['badge']['label'] = __('Security');

            return $result;
        }

        public function get_test_php_extensions()
        {
            $result = [
                'label' => __('Required and recommended modules are installed'),
                'status' => 'good',
                'badge' => [
                    'label' => __('Performance'),
                    'color' => 'blue',
                ],
                'description' => sprintf('<p>%s</p><p>%s</p>', __('PHP modules perform most of the tasks on the server that make your site run. Any changes to these must be made by your server administrator.'), sprintf(/* translators: 1: Link to the hosting group page about recommended PHP modules. 2: Additional link attributes. 3: Accessibility text. */ __('The WordPress Hosting Team maintains a list of those modules, both recommended and required, in <a href="%1$s" %2$s>the team handbook%3$s</a>.'), /* translators: Localized team handbook, if one exists. */ esc_url(__('https://make.wordpress.org/hosting/handbook/handbook/server-environment/#php-extensions')), 'target="_blank" rel="noopener"', sprintf('<span class="screen-reader-text"> %s</span><span aria-hidden="true" class="dashicons dashicons-external"></span>', /* translators: Hidden accessibility text. */ __('(opens in a new tab)')))),
                'actions' => '',
                'test' => 'php_extensions',
            ];

            $modules = [
                'curl' => [
                    'function' => 'curl_version',
                    'required' => false,
                ],
                'dom' => [
                    'class' => 'DOMNode',
                    'required' => false,
                ],
                'exif' => [
                    'function' => 'exif_read_data',
                    'required' => false,
                ],
                'fileinfo' => [
                    'function' => 'finfo_file',
                    'required' => false,
                ],
                'hash' => [
                    'function' => 'hash',
                    'required' => false,
                ],
                'imagick' => [
                    'extension' => 'imagick',
                    'required' => false,
                ],
                'json' => [
                    'function' => 'json_last_error',
                    'required' => true,
                ],
                'mbstring' => [
                    'function' => 'mb_check_encoding',
                    'required' => false,
                ],
                'mysqli' => [
                    'function' => 'mysqli_connect',
                    'required' => false,
                ],
                'libsodium' => [
                    'constant' => 'SODIUM_LIBRARY_VERSION',
                    'required' => false,
                    'php_bundled_version' => '7.2.0',
                ],
                'openssl' => [
                    'function' => 'openssl_encrypt',
                    'required' => false,
                ],
                'pcre' => [
                    'function' => 'preg_match',
                    'required' => false,
                ],
                'mod_xml' => [
                    'extension' => 'libxml',
                    'required' => false,
                ],
                'zip' => [
                    'class' => 'ZipArchive',
                    'required' => false,
                ],
                'filter' => [
                    'function' => 'filter_list',
                    'required' => false,
                ],
                'gd' => [
                    'extension' => 'gd',
                    'required' => false,
                    'fallback_for' => 'imagick',
                ],
                'iconv' => [
                    'function' => 'iconv',
                    'required' => false,
                ],
                'intl' => [
                    'extension' => 'intl',
                    'required' => false,
                ],
                'mcrypt' => [
                    'extension' => 'mcrypt',
                    'required' => false,
                    'fallback_for' => 'libsodium',
                ],
                'simplexml' => [
                    'extension' => 'simplexml',
                    'required' => false,
                    'fallback_for' => 'mod_xml',
                ],
                'xmlreader' => [
                    'extension' => 'xmlreader',
                    'required' => false,
                    'fallback_for' => 'mod_xml',
                ],
                'zlib' => [
                    'extension' => 'zlib',
                    'required' => false,
                    'fallback_for' => 'zip',
                ],
            ];

            $modules = apply_filters('site_status_test_php_modules', $modules);

            $failures = [];

            foreach($modules as $library => $module)
            {
                $extension_name = (isset($module['extension']) ? $module['extension'] : null);
                $function_name = (isset($module['function']) ? $module['function'] : null);
                $constant_name = (isset($module['constant']) ? $module['constant'] : null);
                $class_name = (isset($module['class']) ? $module['class'] : null);

                // If this module is a fallback for another function, check if that other function passed.
                if(isset($module['fallback_for']))
                {
                    /*
                     * If that other function has a failure, mark this module as required for usual operations.
                     * If that other function hasn't failed, skip this test as it's only a fallback.
                     */
                    if(isset($failures[$module['fallback_for']]))
                    {
                        $module['required'] = true;
                    }
                    else
                    {
                        continue;
                    }
                }

                if(! $this->test_php_extension_availability($extension_name, $function_name, $constant_name, $class_name) && (! isset($module['php_bundled_version']) || version_compare(PHP_VERSION, $module['php_bundled_version'], '<')))
                {
                    if($module['required'])
                    {
                        $result['status'] = 'critical';

                        $class = 'error';
                        /* translators: Hidden accessibility text. */
                        $screen_reader = __('Error');
                        $message = sprintf(/* translators: %s: The module name. */ __('The required module, %s, is not installed, or has been disabled.'), $library);
                    }
                    else
                    {
                        $class = 'warning';
                        /* translators: Hidden accessibility text. */
                        $screen_reader = __('Warning');
                        $message = sprintf(/* translators: %s: The module name. */ __('The optional module, %s, is not installed, or has been disabled.'), $library);
                    }

                    if(! $module['required'] && 'good' === $result['status'])
                    {
                        $result['status'] = 'recommended';
                    }

                    $failures[$library] = "<span class='dashicons $class'><span class='screen-reader-text'>$screen_reader</span></span> $message";
                }
            }

            if(! empty($failures))
            {
                $output = '<ul>';

                foreach($failures as $failure)
                {
                    $output .= sprintf('<li>%s</li>', $failure);
                }

                $output .= '</ul>';
            }

            if('good' !== $result['status'])
            {
                if('recommended' === $result['status'])
                {
                    $result['label'] = __('One or more recommended modules are missing');
                }
                if('critical' === $result['status'])
                {
                    $result['label'] = __('One or more required modules are missing');
                }

                $result['description'] .= $output;
            }

            return $result;
        }

        private function test_php_extension_availability(
            $extension_name = null, $function_name = null, $constant_name = null, $class_name = null
        ) {
            // If no extension or function is passed, claim to fail testing, as we have nothing to test against.
            if(! $extension_name && ! $function_name && ! $constant_name && ! $class_name)
            {
                return false;
            }

            if($extension_name && ! extension_loaded($extension_name))
            {
                return false;
            }

            if($function_name && ! function_exists($function_name))
            {
                return false;
            }

            if($constant_name && ! defined($constant_name))
            {
                return false;
            }

            if($class_name && ! class_exists($class_name))
            {
                return false;
            }

            return true;
        }

        public function get_test_php_default_timezone()
        {
            $result = [
                'label' => __('PHP default timezone is valid'),
                'status' => 'good',
                'badge' => [
                    'label' => __('Performance'),
                    'color' => 'blue',
                ],
                'description' => sprintf('<p>%s</p>', __('PHP default timezone was configured by WordPress on loading. This is necessary for correct calculations of dates and times.')),
                'actions' => '',
                'test' => 'php_default_timezone',
            ];

            if('UTC' !== date_default_timezone_get())
            {
                $result['status'] = 'critical';

                $result['label'] = __('PHP default timezone is invalid');

                $result['description'] = sprintf('<p>%s</p>', sprintf(/* translators: %s: date_default_timezone_set() */ __('PHP default timezone was changed after WordPress loading by a %s function call. This interferes with correct calculations of dates and times.'), '<code>date_default_timezone_set()</code>'));
            }

            return $result;
        }

        public function get_test_php_sessions()
        {
            $result = [
                'label' => __('No PHP sessions detected'),
                'status' => 'good',
                'badge' => [
                    'label' => __('Performance'),
                    'color' => 'blue',
                ],
                'description' => sprintf('<p>%s</p>', sprintf(/* translators: 1: session_start(), 2: session_write_close() */ __('PHP sessions created by a %1$s function call may interfere with REST API and loopback requests. An active session should be closed by %2$s before making any HTTP requests.'), '<code>session_start()</code>', '<code>session_write_close()</code>')),
                'test' => 'php_sessions',
            ];

            if(function_exists('session_status') && PHP_SESSION_ACTIVE === session_status())
            {
                $result['status'] = 'critical';

                $result['label'] = __('An active PHP session was detected');

                $result['description'] = sprintf('<p>%s</p>', sprintf(/* translators: 1: session_start(), 2: session_write_close() */ __('A PHP session was created by a %1$s function call. This interferes with REST API and loopback requests. The session should be closed by %2$s before making any HTTP requests.'), '<code>session_start()</code>', '<code>session_write_close()</code>'));
            }

            return $result;
        }

        public function get_test_sql_server()
        {
            if(! $this->mysql_server_version)
            {
                $this->prepare_sql_data();
            }

            $result = [
                'label' => __('SQL server is up to date'),
                'status' => 'good',
                'badge' => [
                    'label' => __('Performance'),
                    'color' => 'blue',
                ],
                'description' => sprintf('<p>%s</p>', __('The SQL server is a required piece of software for the database WordPress uses to store all your site&#8217;s content and settings.')),
                'actions' => sprintf('<p><a href="%s" target="_blank" rel="noopener">%s<span class="screen-reader-text"> %s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a></p>', /* translators: Localized version of WordPress requirements if one exists. */ esc_url(__('https://wordpress.org/about/requirements/')), __('Learn more about what WordPress requires to run.'), /* translators: Hidden accessibility text. */ __('(opens in a new tab)')),
                'test' => 'sql_server',
            ];

            $db_dropin = file_exists(WP_CONTENT_DIR.'/db.php');

            if(! $this->is_recommended_mysql_version)
            {
                $result['status'] = 'recommended';

                $result['label'] = __('Outdated SQL server');

                $result['description'] .= sprintf('<p>%s</p>', sprintf(/* translators: 1: The database engine in use (MySQL or MariaDB). 2: Database server recommended version number. */ __('For optimal performance and security reasons, you should consider running %1$s version %2$s or higher. Contact your web hosting company to correct this.'), ($this->is_mariadb ? 'MariaDB' : 'MySQL'), $this->mysql_recommended_version));
            }

            if(! $this->is_acceptable_mysql_version)
            {
                $result['status'] = 'critical';

                $result['label'] = __('Severely outdated SQL server');
                $result['badge']['label'] = __('Security');

                $result['description'] .= sprintf('<p>%s</p>', sprintf(/* translators: 1: The database engine in use (MySQL or MariaDB). 2: Database server minimum version number. */ __('WordPress requires %1$s version %2$s or higher. Contact your web hosting company to correct this.'), ($this->is_mariadb ? 'MariaDB' : 'MySQL'), $this->mysql_required_version));
            }

            if($db_dropin)
            {
                $result['description'] .= sprintf(
                    '<p>%s</p>', wp_kses(sprintf(/* translators: 1: The name of the drop-in. 2: The name of the database engine. */ __('You are using a %1$s drop-in which might mean that a %2$s database is not being used.'), '<code>wp-content/db.php</code>', ($this->is_mariadb ? 'MariaDB' : 'MySQL')), [
                                   'code' => true,
                               ])
                );
            }

            return $result;
        }

        private function prepare_sql_data()
        {
            global $wpdb;

            $mysql_server_type = $wpdb->db_server_info();

            $this->mysql_server_version = $wpdb->get_var('SELECT VERSION()');

            if(stristr($mysql_server_type, 'mariadb'))
            {
                $this->is_mariadb = true;
                $this->mysql_recommended_version = $this->mariadb_recommended_version;
            }

            $this->is_acceptable_mysql_version = version_compare($this->mysql_required_version, $this->mysql_server_version, '<=');
            $this->is_recommended_mysql_version = version_compare($this->mysql_recommended_version, $this->mysql_server_version, '<=');
        }

        public function get_test_utf8mb4_support()
        {
            global $wpdb;

            if(! $this->mysql_server_version)
            {
                $this->prepare_sql_data();
            }

            $result = [
                'label' => __('UTF8MB4 is supported'),
                'status' => 'good',
                'badge' => [
                    'label' => __('Performance'),
                    'color' => 'blue',
                ],
                'description' => sprintf('<p>%s</p>', __('UTF8MB4 is the character set WordPress prefers for database storage because it safely supports the widest set of characters and encodings, including Emoji, enabling better support for non-English languages.')),
                'actions' => '',
                'test' => 'utf8mb4_support',
            ];

            if(! $this->is_mariadb)
            {
                if(version_compare($this->mysql_server_version, '5.5.3', '<'))
                {
                    $result['status'] = 'recommended';

                    $result['label'] = __('utf8mb4 requires a MySQL update');

                    $result['description'] .= sprintf('<p>%s</p>', sprintf(/* translators: %s: Version number. */ __('WordPress&#8217; utf8mb4 support requires MySQL version %s or greater. Please contact your server administrator.'), '5.5.3'));
                }
                else
                {
                    $result['description'] .= sprintf('<p>%s</p>', __('Your MySQL version supports utf8mb4.'));
                }
            }
            else
            { // MariaDB introduced utf8mb4 support in 5.5.0.
                if(version_compare($this->mysql_server_version, '5.5.0', '<'))
                {
                    $result['status'] = 'recommended';

                    $result['label'] = __('utf8mb4 requires a MariaDB update');

                    $result['description'] .= sprintf('<p>%s</p>', sprintf(/* translators: %s: Version number. */ __('WordPress&#8217; utf8mb4 support requires MariaDB version %s or greater. Please contact your server administrator.'), '5.5.0'));
                }
                else
                {
                    $result['description'] .= sprintf('<p>%s</p>', __('Your MariaDB version supports utf8mb4.'));
                }
            }

            // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_get_client_info
            $mysql_client_version = mysqli_get_client_info();

            /*
             * libmysql has supported utf8mb4 since 5.5.3, same as the MySQL server.
             * mysqlnd has supported utf8mb4 since 5.0.9.
             */
            if(str_contains($mysql_client_version, 'mysqlnd'))
            {
                $mysql_client_version = preg_replace('/^\D+([\d.]+).*/', '$1', $mysql_client_version);
                if(version_compare($mysql_client_version, '5.0.9', '<'))
                {
                    $result['status'] = 'recommended';

                    $result['label'] = __('utf8mb4 requires a newer client library');

                    $result['description'] .= sprintf('<p>%s</p>', sprintf(/* translators: 1: Name of the library, 2: Number of version. */ __('WordPress&#8217; utf8mb4 support requires MySQL client library (%1$s) version %2$s or newer. Please contact your server administrator.'), 'mysqlnd', '5.0.9'));
                }
            }
            else
            {
                if(version_compare($mysql_client_version, '5.5.3', '<'))
                {
                    $result['status'] = 'recommended';

                    $result['label'] = __('utf8mb4 requires a newer client library');

                    $result['description'] .= sprintf('<p>%s</p>', sprintf(/* translators: 1: Name of the library, 2: Number of version. */ __('WordPress&#8217; utf8mb4 support requires MySQL client library (%1$s) version %2$s or newer. Please contact your server administrator.'), 'libmysql', '5.5.3'));
                }
            }

            return $result;
        }

        public function get_test_dotorg_communication()
        {
            $result = [
                'label' => __('Can communicate with WordPress.org'),
                'status' => '',
                'badge' => [
                    'label' => __('Security'),
                    'color' => 'blue',
                ],
                'description' => sprintf('<p>%s</p>', __('Communicating with the WordPress servers is used to check for new versions, and to both install and update WordPress core, themes or plugins.')),
                'actions' => '',
                'test' => 'dotorg_communication',
            ];

            $wp_dotorg = wp_remote_get('https://api.wordpress.org', [
                'timeout' => 10,
            ]);
            if(! is_wp_error($wp_dotorg))
            {
                $result['status'] = 'good';
            }
            else
            {
                $result['status'] = 'critical';

                $result['label'] = __('Could not reach WordPress.org');

                $result['description'] .= sprintf('<p>%s</p>', sprintf('<span class="error"><span class="screen-reader-text">%s</span></span> %s', /* translators: Hidden accessibility text. */ __('Error'), sprintf(/* translators: 1: The IP address WordPress.org resolves to. 2: The error returned by the lookup. */ __('Your site is unable to reach WordPress.org at %1$s, and returned the error: %2$s'), gethostbyname('api.wordpress.org'), $wp_dotorg->get_error_message())));

                $result['actions'] = sprintf('<p><a href="%s" target="_blank" rel="noopener">%s<span class="screen-reader-text"> %s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a></p>', /* translators: Localized Support reference. */ esc_url(__('https://wordpress.org/support/forums/')), __('Get help resolving this issue.'), /* translators: Hidden accessibility text. */ __('(opens in a new tab)'));
            }

            return $result;
        }

        public function get_test_is_in_debug_mode()
        {
            $result = [
                'label' => __('Your site is not set to output debug information'),
                'status' => 'good',
                'badge' => [
                    'label' => __('Security'),
                    'color' => 'blue',
                ],
                'description' => sprintf('<p>%s</p>', __('Debug mode is often enabled to gather more details about an error or site failure, but may contain sensitive information which should not be available on a publicly available website.')),
                'actions' => sprintf('<p><a href="%s" target="_blank" rel="noopener">%s<span class="screen-reader-text"> %s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a></p>', /* translators: Documentation explaining debugging in WordPress. */ esc_url(__('https://wordpress.org/documentation/article/debugging-in-wordpress/')), __('Learn more about debugging in WordPress.'), /* translators: Hidden accessibility text. */ __('(opens in a new tab)')),
                'test' => 'is_in_debug_mode',
            ];

            if(defined('WP_DEBUG') && WP_DEBUG)
            {
                if(defined('WP_DEBUG_LOG') && WP_DEBUG_LOG)
                {
                    $result['label'] = __('Your site is set to log errors to a potentially public file');

                    $result['status'] = str_starts_with(ini_get('error_log'), ABSPATH) ? 'critical' : 'recommended';

                    $result['description'] .= sprintf('<p>%s</p>', sprintf(/* translators: %s: WP_DEBUG_LOG */ __('The value, %s, has been added to this website&#8217;s configuration file. This means any errors on the site will be written to a file which is potentially available to all users.'), '<code>WP_DEBUG_LOG</code>'));
                }

                if(defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY)
                {
                    $result['label'] = __('Your site is set to display errors to site visitors');

                    $result['status'] = 'critical';

                    // On development environments, set the status to recommended.
                    if($this->is_development_environment())
                    {
                        $result['status'] = 'recommended';
                    }

                    $result['description'] .= sprintf('<p>%s</p>', sprintf(/* translators: 1: WP_DEBUG_DISPLAY, 2: WP_DEBUG */ __('The value, %1$s, has either been enabled by %2$s or added to your configuration file. This will make errors display on the front end of your site.'), '<code>WP_DEBUG_DISPLAY</code>', '<code>WP_DEBUG</code>'));
                }
            }

            return $result;
        }

        public function get_test_https_status()
        {
            /*
             * Enforce fresh HTTPS detection results. This is normally invoked by using cron,
             * but for Site Health it should always rely on the latest results.
             */
            wp_update_https_detection_errors();

            $default_update_url = wp_get_default_update_https_url();

            $result = [
                'label' => __('Your website is using an active HTTPS connection'),
                'status' => 'good',
                'badge' => [
                    'label' => __('Security'),
                    'color' => 'blue',
                ],
                'description' => sprintf('<p>%s</p>', __('An HTTPS connection is a more secure way of browsing the web. Many services now have HTTPS as a requirement. HTTPS allows you to take advantage of new features that can increase site speed, improve search rankings, and gain the trust of your visitors by helping to protect their online privacy.')),
                'actions' => sprintf('<p><a href="%s" target="_blank" rel="noopener">%s<span class="screen-reader-text"> %s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a></p>', esc_url($default_update_url), __('Learn more about why you should use HTTPS'), /* translators: Hidden accessibility text. */ __('(opens in a new tab)')),
                'test' => 'https_status',
            ];

            if(! wp_is_using_https())
            {
                /*
                 * If the website is not using HTTPS, provide more information
                 * about whether it is supported and how it can be enabled.
                 */
                $result['status'] = 'recommended';
                $result['label'] = __('Your website does not use HTTPS');

                if(wp_is_site_url_using_https())
                {
                    if(is_ssl())
                    {
                        $result['description'] = sprintf('<p>%s</p>', sprintf(/* translators: %s: URL to Settings > General > Site Address. */ __('You are accessing this website using HTTPS, but your <a href="%s">Site Address</a> is not set up to use HTTPS by default.'), esc_url(admin_url('options-general.php').'#home')));
                    }
                    else
                    {
                        $result['description'] = sprintf('<p>%s</p>', sprintf(/* translators: %s: URL to Settings > General > Site Address. */ __('Your <a href="%s">Site Address</a> is not set up to use HTTPS.'), esc_url(admin_url('options-general.php').'#home')));
                    }
                }
                else
                {
                    if(is_ssl())
                    {
                        $result['description'] = sprintf('<p>%s</p>', sprintf(/* translators: 1: URL to Settings > General > WordPress Address, 2: URL to Settings > General > Site Address. */ __('You are accessing this website using HTTPS, but your <a href="%1$s">WordPress Address</a> and <a href="%2$s">Site Address</a> are not set up to use HTTPS by default.'), esc_url(admin_url('options-general.php').'#siteurl'), esc_url(admin_url('options-general.php').'#home')));
                    }
                    else
                    {
                        $result['description'] = sprintf('<p>%s</p>', sprintf(/* translators: 1: URL to Settings > General > WordPress Address, 2: URL to Settings > General > Site Address. */ __('Your <a href="%1$s">WordPress Address</a> and <a href="%2$s">Site Address</a> are not set up to use HTTPS.'), esc_url(admin_url('options-general.php').'#siteurl'), esc_url(admin_url('options-general.php').'#home')));
                    }
                }

                if(wp_is_https_supported())
                {
                    $result['description'] .= sprintf('<p>%s</p>', __('HTTPS is already supported for your website.'));

                    if(defined('WP_HOME') || defined('WP_SITEURL'))
                    {
                        $result['description'] .= sprintf('<p>%s</p>', sprintf(/* translators: 1: wp-config.php, 2: WP_HOME, 3: WP_SITEURL */ __('However, your WordPress Address is currently controlled by a PHP constant and therefore cannot be updated. You need to edit your %1$s and remove or update the definitions of %2$s and %3$s.'), '<code>wp-config.php</code>', '<code>WP_HOME</code>', '<code>WP_SITEURL</code>'));
                    }
                    elseif(current_user_can('update_https'))
                    {
                        $default_direct_update_url = add_query_arg('action', 'update_https', wp_nonce_url(admin_url('site-health.php'), 'wp_update_https'));
                        $direct_update_url = wp_get_direct_update_https_url();

                        if(! empty($direct_update_url))
                        {
                            $result['actions'] = sprintf('<p class="button-container"><a class="button button-primary" href="%1$s" target="_blank" rel="noopener">%2$s<span class="screen-reader-text"> %3$s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a></p>', esc_url($direct_update_url), __('Update your site to use HTTPS'), /* translators: Hidden accessibility text. */ __('(opens in a new tab)'));
                        }
                        else
                        {
                            $result['actions'] = sprintf('<p class="button-container"><a class="button button-primary" href="%1$s">%2$s</a></p>', esc_url($default_direct_update_url), __('Update your site to use HTTPS'));
                        }
                    }
                }
                else
                {
                    // If host-specific "Update HTTPS" URL is provided, include a link.
                    $update_url = wp_get_update_https_url();
                    if($update_url !== $default_update_url)
                    {
                        $result['description'] .= sprintf('<p><a href="%s" target="_blank" rel="noopener">%s<span class="screen-reader-text"> %s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a></p>', esc_url($update_url), __('Talk to your web host about supporting HTTPS for your website.'), /* translators: Hidden accessibility text. */ __('(opens in a new tab)'));
                    }
                    else
                    {
                        $result['description'] .= sprintf('<p>%s</p>', __('Talk to your web host about supporting HTTPS for your website.'));
                    }
                }
            }

            return $result;
        }

        public function get_test_ssl_support()
        {
            $result = [
                'label' => '',
                'status' => '',
                'badge' => [
                    'label' => __('Security'),
                    'color' => 'blue',
                ],
                'description' => sprintf('<p>%s</p>', __('Securely communicating between servers are needed for transactions such as fetching files, conducting sales on store sites, and much more.')),
                'actions' => '',
                'test' => 'ssl_support',
            ];

            $supports_https = wp_http_supports(['ssl']);

            if($supports_https)
            {
                $result['status'] = 'good';

                $result['label'] = __('Your site can communicate securely with other services');
            }
            else
            {
                $result['status'] = 'critical';

                $result['label'] = __('Your site is unable to communicate securely with other services');

                $result['description'] .= sprintf('<p>%s</p>', __('Talk to your web host about OpenSSL support for PHP.'));
            }

            return $result;
        }

        public function get_test_scheduled_events()
        {
            $result = [
                'label' => __('Scheduled events are running'),
                'status' => 'good',
                'badge' => [
                    'label' => __('Performance'),
                    'color' => 'blue',
                ],
                'description' => sprintf('<p>%s</p>', __('Scheduled events are what periodically looks for updates to plugins, themes and WordPress itself. It is also what makes sure scheduled posts are published on time. It may also be used by various plugins to make sure that planned actions are executed.')),
                'actions' => '',
                'test' => 'scheduled_events',
            ];

            $this->wp_schedule_test_init();

            if(is_wp_error($this->has_missed_cron()))
            {
                $result['status'] = 'critical';

                $result['label'] = __('It was not possible to check your scheduled events');

                $result['description'] = sprintf('<p>%s</p>', sprintf(/* translators: %s: The error message returned while from the cron scheduler. */ __('While trying to test your site&#8217;s scheduled events, the following error was returned: %s'), $this->has_missed_cron()->get_error_message()));
            }
            elseif($this->has_missed_cron())
            {
                $result['status'] = 'recommended';

                $result['label'] = __('A scheduled event has failed');

                $result['description'] = sprintf('<p>%s</p>', sprintf(/* translators: %s: The name of the failed cron event. */ __('The scheduled event, %s, failed to run. Your site still works, but this may indicate that scheduling posts or automated updates may not work as intended.'), $this->last_missed_cron));
            }
            elseif($this->has_late_cron())
            {
                $result['status'] = 'recommended';

                $result['label'] = __('A scheduled event is late');

                $result['description'] = sprintf('<p>%s</p>', sprintf(/* translators: %s: The name of the late cron event. */ __('The scheduled event, %s, is late to run. Your site still works, but this may indicate that scheduling posts or automated updates may not work as intended.'), $this->last_late_cron));
            }

            return $result;
        }

        private function wp_schedule_test_init()
        {
            $this->schedules = wp_get_schedules();
            $this->get_cron_tasks();
        }

        private function get_cron_tasks()
        {
            $cron_tasks = _get_cron_array();

            if(empty($cron_tasks))
            {
                $this->crons = new WP_Error('no_tasks', __('No scheduled events exist on this site.'));

                return;
            }

            $this->crons = [];

            foreach($cron_tasks as $time => $cron)
            {
                foreach($cron as $hook => $dings)
                {
                    foreach($dings as $sig => $data)
                    {
                        $this->crons["$hook-$sig-$time"] = (object) [
                            'hook' => $hook,
                            'time' => $time,
                            'sig' => $sig,
                            'args' => $data['args'],
                            'schedule' => $data['schedule'],
                            'interval' => isset($data['interval']) ? $data['interval'] : null,
                        ];
                    }
                }
            }
        }

        public function has_missed_cron()
        {
            if(is_wp_error($this->crons))
            {
                return $this->crons;
            }

            foreach($this->crons as $id => $cron)
            {
                if(($cron->time - time()) < $this->timeout_missed_cron)
                {
                    $this->last_missed_cron = $cron->hook;

                    return true;
                }
            }

            return false;
        }

        public function has_late_cron()
        {
            if(is_wp_error($this->crons))
            {
                return $this->crons;
            }

            foreach($this->crons as $id => $cron)
            {
                $cron_offset = $cron->time - time();
                if($cron_offset >= $this->timeout_missed_cron && $cron_offset < $this->timeout_late_cron)
                {
                    $this->last_late_cron = $cron->hook;

                    return true;
                }
            }

            return false;
        }

        public function get_test_background_updates()
        {
            $result = [
                'label' => __('Background updates are working'),
                'status' => 'good',
                'badge' => [
                    'label' => __('Security'),
                    'color' => 'blue',
                ],
                'description' => sprintf('<p>%s</p>', __('Background updates ensure that WordPress can auto-update if a security update is released for the version you are currently using.')),
                'actions' => '',
                'test' => 'background_updates',
            ];

            if(! class_exists('WP_Site_Health_Auto_Updates'))
            {
                require_once ABSPATH.'wp-admin/includes/class-wp-site-health-auto-updates.php';
            }

            /*
             * Run the auto-update tests in a separate class,
             * as there are many considerations to be made.
             */
            $automatic_updates = new WP_Site_Health_Auto_Updates();
            $tests = $automatic_updates->run_tests();

            $output = '<ul>';

            foreach($tests as $test)
            {
                /* translators: Hidden accessibility text. */
                $severity_string = __('Passed');

                if('fail' === $test->severity)
                {
                    $result['label'] = __('Background updates are not working as expected');

                    $result['status'] = 'critical';

                    /* translators: Hidden accessibility text. */
                    $severity_string = __('Error');
                }

                if('warning' === $test->severity && 'good' === $result['status'])
                {
                    $result['label'] = __('Background updates may not be working properly');

                    $result['status'] = 'recommended';

                    /* translators: Hidden accessibility text. */
                    $severity_string = __('Warning');
                }

                $output .= sprintf('<li><span class="dashicons %s"><span class="screen-reader-text">%s</span></span> %s</li>', esc_attr($test->severity), $severity_string, $test->description);
            }

            $output .= '</ul>';

            if('good' !== $result['status'])
            {
                $result['description'] .= $output;
            }

            return $result;
        }

        public function get_test_plugin_theme_auto_updates()
        {
            $result = [
                'label' => __('Plugin and theme auto-updates appear to be configured correctly'),
                'status' => 'good',
                'badge' => [
                    'label' => __('Security'),
                    'color' => 'blue',
                ],
                'description' => sprintf('<p>%s</p>', __('Plugin and theme auto-updates ensure that the latest versions are always installed.')),
                'actions' => '',
                'test' => 'plugin_theme_auto_updates',
            ];

            $check_plugin_theme_updates = $this->detect_plugin_theme_auto_update_issues();

            $result['status'] = $check_plugin_theme_updates->status;

            if('good' !== $result['status'])
            {
                $result['label'] = __('Your site may have problems auto-updating plugins and themes');

                $result['description'] .= sprintf('<p>%s</p>', $check_plugin_theme_updates->message);
            }

            return $result;
        }

        public function detect_plugin_theme_auto_update_issues()
        {
            $mock_plugin = (object) [
                'id' => 'w.org/plugins/a-fake-plugin',
                'slug' => 'a-fake-plugin',
                'plugin' => 'a-fake-plugin/a-fake-plugin.php',
                'new_version' => '9.9',
                'url' => 'https://wordpress.org/plugins/a-fake-plugin/',
                'package' => 'https://downloads.wordpress.org/plugin/a-fake-plugin.9.9.zip',
                'icons' => [
                    '2x' => 'https://ps.w.org/a-fake-plugin/assets/icon-256x256.png',
                    '1x' => 'https://ps.w.org/a-fake-plugin/assets/icon-128x128.png',
                ],
                'banners' => [
                    '2x' => 'https://ps.w.org/a-fake-plugin/assets/banner-1544x500.png',
                    '1x' => 'https://ps.w.org/a-fake-plugin/assets/banner-772x250.png',
                ],
                'banners_rtl' => [],
                'tested' => '5.5.0',
                'requires_php' => '5.6.20',
                'compatibility' => new stdClass(),
            ];

            $mock_theme = (object) [
                'theme' => 'a-fake-theme',
                'new_version' => '9.9',
                'url' => 'https://wordpress.org/themes/a-fake-theme/',
                'package' => 'https://downloads.wordpress.org/theme/a-fake-theme.9.9.zip',
                'requires' => '5.0.0',
                'requires_php' => '5.6.20',
            ];

            $test_plugins_enabled = wp_is_auto_update_forced_for_item('plugin', true, $mock_plugin);
            $test_themes_enabled = wp_is_auto_update_forced_for_item('theme', true, $mock_theme);

            $ui_enabled_for_plugins = wp_is_auto_update_enabled_for_type('plugin');
            $ui_enabled_for_themes = wp_is_auto_update_enabled_for_type('theme');
            $plugin_filter_present = has_filter('auto_update_plugin');
            $theme_filter_present = has_filter('auto_update_theme');

            if((! $test_plugins_enabled && $ui_enabled_for_plugins) || (! $test_themes_enabled && $ui_enabled_for_themes))
            {
                return (object) [
                    'status' => 'critical',
                    'message' => __('Auto-updates for plugins and/or themes appear to be disabled, but settings are still set to be displayed. This could cause auto-updates to not work as expected.'),
                ];
            }

            if((! $test_plugins_enabled && $plugin_filter_present) && (! $test_themes_enabled && $theme_filter_present))
            {
                return (object) [
                    'status' => 'recommended',
                    'message' => __('Auto-updates for plugins and themes appear to be disabled. This will prevent your site from receiving new versions automatically when available.'),
                ];
            }
            elseif(! $test_plugins_enabled && $plugin_filter_present)
            {
                return (object) [
                    'status' => 'recommended',
                    'message' => __('Auto-updates for plugins appear to be disabled. This will prevent your site from receiving new versions automatically when available.'),
                ];
            }
            elseif(! $test_themes_enabled && $theme_filter_present)
            {
                return (object) [
                    'status' => 'recommended',
                    'message' => __('Auto-updates for themes appear to be disabled. This will prevent your site from receiving new versions automatically when available.'),
                ];
            }

            return (object) [
                'status' => 'good',
                'message' => __('There appear to be no issues with plugin and theme auto-updates.'),
            ];
        }

        public function get_test_available_updates_disk_space()
        {
            $available_space = function_exists('disk_free_space') ? @disk_free_space(WP_CONTENT_DIR.'/upgrade/') : false;

            $result = [
                'label' => __('Disk space available to safely perform updates'),
                'status' => 'good',
                'badge' => [
                    'label' => __('Security'),
                    'color' => 'blue',
                ],
                'description' => sprintf(/* translators: %s: Available disk space in MB or GB. */ '<p>'.__('%s available disk space was detected, update routines can be performed safely.').'</p>', size_format($available_space)),
                'actions' => '',
                'test' => 'available_updates_disk_space',
            ];

            if(false === $available_space)
            {
                $result['description'] = __('Could not determine available disk space for updates.');
                $result['status'] = 'recommended';
            }
            elseif($available_space < 20 * MB_IN_BYTES)
            {
                $result['description'] = __('Available disk space is critically low, less than 20 MB available. Proceed with caution, updates may fail.');
                $result['status'] = 'critical';
            }
            elseif($available_space < 100 * MB_IN_BYTES)
            {
                $result['description'] = __('Available disk space is low, less than 100 MB available.');
                $result['status'] = 'recommended';
            }

            return $result;
        }

        public function get_test_update_temp_backup_writable()
        {
            global $wp_filesystem;

            $result = [
                'label' => __('Plugin and theme temporary backup directory is writable'),
                'status' => 'good',
                'badge' => [
                    'label' => __('Security'),
                    'color' => 'blue',
                ],
                'description' => sprintf(/* translators: %s: wp-content/upgrade-temp-backup */ '<p>'.__('The %s directory used to improve the stability of plugin and theme updates is writable.').'</p>', '<code>wp-content/upgrade-temp-backup</code>'),
                'actions' => '',
                'test' => 'update_temp_backup_writable',
            ];

            if(! function_exists('WP_Filesystem'))
            {
                require_once ABSPATH.'/wp-admin/includes/file.php';
            }

            ob_start();
            $credentials = request_filesystem_credentials('');
            ob_end_clean();

            if(false === $credentials || ! WP_Filesystem($credentials))
            {
                $result['status'] = 'recommended';
                $result['label'] = __('Could not access filesystem');
                $result['description'] = __('Unable to connect to the filesystem. Please confirm your credentials.');

                return $result;
            }

            $wp_content = $wp_filesystem->wp_content_dir();

            if(! $wp_content)
            {
                $result['status'] = 'critical';
                $result['label'] = __('Unable to locate WordPress content directory');
                $result['description'] = sprintf(/* translators: %s: wp-content */ '<p>'.__('The %s directory cannot be located.').'</p>', '<code>wp-content</code>');

                return $result;
            }

            $upgrade_dir_exists = $wp_filesystem->is_dir("$wp_content/upgrade");
            $upgrade_dir_is_writable = $wp_filesystem->is_writable("$wp_content/upgrade");
            $backup_dir_exists = $wp_filesystem->is_dir("$wp_content/upgrade-temp-backup");
            $backup_dir_is_writable = $wp_filesystem->is_writable("$wp_content/upgrade-temp-backup");

            $plugins_dir_exists = $wp_filesystem->is_dir("$wp_content/upgrade-temp-backup/plugins");
            $plugins_dir_is_writable = $wp_filesystem->is_writable("$wp_content/upgrade-temp-backup/plugins");
            $themes_dir_exists = $wp_filesystem->is_dir("$wp_content/upgrade-temp-backup/themes");
            $themes_dir_is_writable = $wp_filesystem->is_writable("$wp_content/upgrade-temp-backup/themes");

            if($plugins_dir_exists && ! $plugins_dir_is_writable && $themes_dir_exists && ! $themes_dir_is_writable)
            {
                $result['status'] = 'critical';
                $result['label'] = __('Plugin and theme temporary backup directories exist but are not writable');
                $result['description'] = sprintf(/* translators: 1: wp-content/upgrade-temp-backup/plugins, 2: wp-content/upgrade-temp-backup/themes. */ '<p>'.__('The %1$s and %2$s directories exist but are not writable. These directories are used to improve the stability of plugin updates. Please make sure the server has write permissions to these directories.').'</p>', '<code>wp-content/upgrade-temp-backup/plugins</code>', '<code>wp-content/upgrade-temp-backup/themes</code>');

                return $result;
            }

            if($plugins_dir_exists && ! $plugins_dir_is_writable)
            {
                $result['status'] = 'critical';
                $result['label'] = __('Plugin temporary backup directory exists but is not writable');
                $result['description'] = sprintf(/* translators: %s: wp-content/upgrade-temp-backup/plugins */ '<p>'.__('The %s directory exists but is not writable. This directory is used to improve the stability of plugin updates. Please make sure the server has write permissions to this directory.').'</p>', '<code>wp-content/upgrade-temp-backup/plugins</code>');

                return $result;
            }

            if($themes_dir_exists && ! $themes_dir_is_writable)
            {
                $result['status'] = 'critical';
                $result['label'] = __('Theme temporary backup directory exists but is not writable');
                $result['description'] = sprintf(/* translators: %s: wp-content/upgrade-temp-backup/themes */ '<p>'.__('The %s directory exists but is not writable. This directory is used to improve the stability of theme updates. Please make sure the server has write permissions to this directory.').'</p>', '<code>wp-content/upgrade-temp-backup/themes</code>');

                return $result;
            }

            if((! $plugins_dir_exists || ! $themes_dir_exists) && $backup_dir_exists && ! $backup_dir_is_writable)
            {
                $result['status'] = 'critical';
                $result['label'] = __('The temporary backup directory exists but is not writable');
                $result['description'] = sprintf(/* translators: %s: wp-content/upgrade-temp-backup */ '<p>'.__('The %s directory exists but is not writable. This directory is used to improve the stability of plugin and theme updates. Please make sure the server has write permissions to this directory.').'</p>', '<code>wp-content/upgrade-temp-backup</code>');

                return $result;
            }

            if(! $backup_dir_exists && $upgrade_dir_exists && ! $upgrade_dir_is_writable)
            {
                $result['status'] = 'critical';
                $result['label'] = __('The upgrade directory exists but is not writable');
                $result['description'] = sprintf(/* translators: %s: wp-content/upgrade */ '<p>'.__('The %s directory exists but is not writable. This directory is used for plugin and theme updates. Please make sure the server has write permissions to this directory.').'</p>', '<code>wp-content/upgrade</code>');

                return $result;
            }

            if(! $upgrade_dir_exists && ! $wp_filesystem->is_writable($wp_content))
            {
                $result['status'] = 'critical';
                $result['label'] = __('The upgrade directory cannot be created');
                $result['description'] = sprintf(/* translators: 1: wp-content/upgrade, 2: wp-content. */ '<p>'.__('The %1$s directory does not exist, and the server does not have write permissions in %2$s to create it. This directory is used for plugin and theme updates. Please make sure the server has write permissions in %2$s.').'</p>', '<code>wp-content/upgrade</code>', '<code>wp-content</code>');

                return $result;
            }

            return $result;
        }

        public function get_test_loopback_requests()
        {
            $result = [
                'label' => __('Your site can perform loopback requests'),
                'status' => 'good',
                'badge' => [
                    'label' => __('Performance'),
                    'color' => 'blue',
                ],
                'description' => sprintf('<p>%s</p>', __('Loopback requests are used to run scheduled events, and are also used by the built-in editors for themes and plugins to verify code stability.')),
                'actions' => '',
                'test' => 'loopback_requests',
            ];

            $check_loopback = $this->can_perform_loopback();

            $result['status'] = $check_loopback->status;

            if('good' !== $result['status'])
            {
                $result['label'] = __('Your site could not complete a loopback request');

                $result['description'] .= sprintf('<p>%s</p>', $check_loopback->message);
            }

            return $result;
        }

        public function can_perform_loopback()
        {
            $body = ['site-health' => 'loopback-test'];
            $cookies = wp_unslash($_COOKIE);
            $timeout = 10; // 10 seconds.
            $headers = [
                'Cache-Control' => 'no-cache',
            ];

            $sslverify = apply_filters('https_local_ssl_verify', false);

            // Include Basic auth in loopback requests.
            if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
            {
                $headers['Authorization'] = 'Basic '.base64_encode(wp_unslash($_SERVER['PHP_AUTH_USER']).':'.wp_unslash($_SERVER['PHP_AUTH_PW']));
            }

            $url = site_url('wp-cron.php');

            /*
             * A post request is used for the wp-cron.php loopback test to cause the file
             * to finish early without triggering cron jobs. This has two benefits:
             * - cron jobs are not triggered a second time on the site health page,
             * - the loopback request finishes sooner providing a quicker result.
             *
             * Using a POST request causes the loopback to differ slightly to the standard
             * GET request WordPress uses for wp-cron.php loopback requests but is close
             * enough. See https://core.trac.wordpress.org/ticket/52547
             */
            $r = wp_remote_post($url, compact('body', 'cookies', 'headers', 'timeout', 'sslverify'));

            if(is_wp_error($r))
            {
                return (object) [
                    'status' => 'critical',
                    'message' => sprintf('%s<br>%s', __('The loopback request to your site failed, this means features relying on them are not currently working as expected.'), sprintf(/* translators: 1: The WordPress error message. 2: The WordPress error code. */ __('Error: %1$s (%2$s)'), $r->get_error_message(), $r->get_error_code())),
                ];
            }

            if(200 !== wp_remote_retrieve_response_code($r))
            {
                return (object) [
                    'status' => 'recommended',
                    'message' => sprintf(/* translators: %d: The HTTP response code returned. */ __('The loopback request returned an unexpected http status code, %d, it was not possible to determine if this will prevent features from working as expected.'), wp_remote_retrieve_response_code($r)),
                ];
            }

            return (object) [
                'status' => 'good',
                'message' => __('The loopback request to your site completed successfully.'),
            ];
        }

        public function get_test_http_requests()
        {
            $result = [
                'label' => __('HTTP requests seem to be working as expected'),
                'status' => 'good',
                'badge' => [
                    'label' => __('Performance'),
                    'color' => 'blue',
                ],
                'description' => sprintf('<p>%s</p>', __('It is possible for site maintainers to block all, or some, communication to other sites and services. If set up incorrectly, this may prevent plugins and themes from working as intended.')),
                'actions' => '',
                'test' => 'http_requests',
            ];

            $blocked = false;
            $hosts = [];

            if(defined('WP_HTTP_BLOCK_EXTERNAL') && WP_HTTP_BLOCK_EXTERNAL)
            {
                $blocked = true;
            }

            if(defined('WP_ACCESSIBLE_HOSTS'))
            {
                $hosts = explode(',', WP_ACCESSIBLE_HOSTS);
            }

            if($blocked && 0 === count($hosts))
            {
                $result['status'] = 'critical';

                $result['label'] = __('HTTP requests are blocked');

                $result['description'] .= sprintf('<p>%s</p>', sprintf(/* translators: %s: Name of the constant used. */ __('HTTP requests have been blocked by the %s constant, with no allowed hosts.'), '<code>WP_HTTP_BLOCK_EXTERNAL</code>'));
            }

            if($blocked && 0 < count($hosts))
            {
                $result['status'] = 'recommended';

                $result['label'] = __('HTTP requests are partially blocked');

                $result['description'] .= sprintf('<p>%s</p>', sprintf(/* translators: 1: Name of the constant used. 2: List of allowed hostnames. */ __('HTTP requests have been blocked by the %1$s constant, with some allowed hosts: %2$s.'), '<code>WP_HTTP_BLOCK_EXTERNAL</code>', implode(',', $hosts)));
            }

            return $result;
        }

        public function get_test_rest_availability()
        {
            $result = [
                'label' => __('The REST API is available'),
                'status' => 'good',
                'badge' => [
                    'label' => __('Performance'),
                    'color' => 'blue',
                ],
                'description' => sprintf('<p>%s</p>', __('The REST API is one way that WordPress and other applications communicate with the server. For example, the block editor screen relies on the REST API to display and save your posts and pages.')),
                'actions' => '',
                'test' => 'rest_availability',
            ];

            $cookies = wp_unslash($_COOKIE);
            $timeout = 10; // 10 seconds.
            $headers = [
                'Cache-Control' => 'no-cache',
                'X-WP-Nonce' => wp_create_nonce('wp_rest'),
            ];

            $sslverify = apply_filters('https_local_ssl_verify', false);

            // Include Basic auth in loopback requests.
            if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
            {
                $headers['Authorization'] = 'Basic '.base64_encode(wp_unslash($_SERVER['PHP_AUTH_USER']).':'.wp_unslash($_SERVER['PHP_AUTH_PW']));
            }

            $url = rest_url('wp/v2/types/post');

            // The context for this is editing with the new block editor.
            $url = add_query_arg([
                                     'context' => 'edit',
                                 ], $url);

            $r = wp_remote_get($url, compact('cookies', 'headers', 'timeout', 'sslverify'));

            if(is_wp_error($r))
            {
                $result['status'] = 'critical';

                $result['label'] = __('The REST API encountered an error');

                $result['description'] .= sprintf(
                    '<p>%s</p><p>%s<br>%s</p>', __('When testing the REST API, an error was encountered:'), sprintf(// translators: %s: The REST API URL.
                    __('REST API Endpoint: %s'), $url
                ),  sprintf(// translators: 1: The WordPress error code. 2: The WordPress error message.
                        __('REST API Response: (%1$s) %2$s'), $r->get_error_code(), $r->get_error_message()
                    )
                );
            }
            elseif(200 !== wp_remote_retrieve_response_code($r))
            {
                $result['status'] = 'recommended';

                $result['label'] = __('The REST API encountered an unexpected result');

                $result['description'] .= sprintf(
                    '<p>%s</p><p>%s<br>%s</p>', __('When testing the REST API, an unexpected result was returned:'), sprintf(// translators: %s: The REST API URL.
                    __('REST API Endpoint: %s'), $url
                ),  sprintf(// translators: 1: The WordPress error code. 2: The HTTP status code error message.
                        __('REST API Response: (%1$s) %2$s'), wp_remote_retrieve_response_code($r), wp_remote_retrieve_response_message($r)
                    )
                );
            }
            else
            {
                $json = json_decode(wp_remote_retrieve_body($r), true);

                if(false !== $json && ! isset($json['capabilities']))
                {
                    $result['status'] = 'recommended';

                    $result['label'] = __('The REST API did not behave correctly');

                    $result['description'] .= sprintf('<p>%s</p>', sprintf(/* translators: %s: The name of the query parameter being tested. */ __('The REST API did not process the %s query parameter correctly.'), '<code>context</code>'));
                }
            }

            return $result;
        }

        public function get_test_file_uploads()
        {
            $result = [
                'label' => __('Files can be uploaded'),
                'status' => 'good',
                'badge' => [
                    'label' => __('Performance'),
                    'color' => 'blue',
                ],
                'description' => sprintf('<p>%s</p>', sprintf(/* translators: 1: file_uploads, 2: php.ini */ __('The %1$s directive in %2$s determines if uploading files is allowed on your site.'), '<code>file_uploads</code>', '<code>php.ini</code>')),
                'actions' => '',
                'test' => 'file_uploads',
            ];

            if(! function_exists('ini_get'))
            {
                $result['status'] = 'critical';
                $result['description'] .= sprintf(/* translators: %s: ini_get() */ __('The %s function has been disabled, some media settings are unavailable because of this.'), '<code>ini_get()</code>');

                return $result;
            }

            if(empty(ini_get('file_uploads')))
            {
                $result['status'] = 'critical';
                $result['description'] .= sprintf('<p>%s</p>', sprintf(/* translators: 1: file_uploads, 2: 0 */ __('%1$s is set to %2$s. You won\'t be able to upload files on your site.'), '<code>file_uploads</code>', '<code>0</code>'));

                return $result;
            }

            $post_max_size = ini_get('post_max_size');
            $upload_max_filesize = ini_get('upload_max_filesize');

            if(wp_convert_hr_to_bytes($post_max_size) < wp_convert_hr_to_bytes($upload_max_filesize))
            {
                $result['label'] = sprintf(/* translators: 1: post_max_size, 2: upload_max_filesize */ __('The "%1$s" value is smaller than "%2$s"'), 'post_max_size', 'upload_max_filesize');
                $result['status'] = 'recommended';

                if(0 === wp_convert_hr_to_bytes($post_max_size))
                {
                    $result['description'] = sprintf('<p>%s</p>', sprintf(/* translators: 1: post_max_size, 2: upload_max_filesize */ __('The setting for %1$s is currently configured as 0, this could cause some problems when trying to upload files through plugin or theme features that rely on various upload methods. It is recommended to configure this setting to a fixed value, ideally matching the value of %2$s, as some upload methods read the value 0 as either unlimited, or disabled.'), '<code>post_max_size</code>', '<code>upload_max_filesize</code>'));
                }
                else
                {
                    $result['description'] = sprintf('<p>%s</p>', sprintf(/* translators: 1: post_max_size, 2: upload_max_filesize */ __('The setting for %1$s is smaller than %2$s, this could cause some problems when trying to upload files.'), '<code>post_max_size</code>', '<code>upload_max_filesize</code>'));
                }

                return $result;
            }

            return $result;
        }

        public function get_test_authorization_header()
        {
            $result = [
                'label' => __('The Authorization header is working as expected'),
                'status' => 'good',
                'badge' => [
                    'label' => __('Security'),
                    'color' => 'blue',
                ],
                'description' => sprintf('<p>%s</p>', __('The Authorization header is used by third-party applications you have approved for this site. Without this header, those apps cannot connect to your site.')),
                'actions' => '',
                'test' => 'authorization_header',
            ];

            if(! isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']))
            {
                $result['label'] = __('The authorization header is missing');
            }
            elseif('user' !== $_SERVER['PHP_AUTH_USER'] || 'pwd' !== $_SERVER['PHP_AUTH_PW'])
            {
                $result['label'] = __('The authorization header is invalid');
            }
            else
            {
                return $result;
            }

            $result['status'] = 'recommended';
            $result['description'] .= sprintf('<p>%s</p>', __('If you are still seeing this warning after having tried the actions below, you may need to contact your hosting provider for further assistance.'));

            if(! function_exists('got_mod_rewrite'))
            {
                require_once ABSPATH.'wp-admin/includes/misc.php';
            }

            if(got_mod_rewrite())
            {
                $result['actions'] .= sprintf('<p><a href="%s">%s</a></p>', esc_url(admin_url('options-permalink.php')), __('Flush permalinks'));
            }
            else
            {
                $result['actions'] .= sprintf('<p><a href="%s" target="_blank" rel="noopener">%s<span class="screen-reader-text"> %s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a></p>', __('https://developer.wordpress.org/rest-api/frequently-asked-questions/#why-is-authentication-not-working'), __('Learn how to configure the Authorization header.'), /* translators: Hidden accessibility text. */ __('(opens in a new tab)'));
            }

            return $result;
        }

        public function get_test_page_cache()
        {
            $description = '<p>'.__('Page cache enhances the speed and performance of your site by saving and serving static pages instead of calling for a page every time a user visits.').'</p>';
            $description .= '<p>'.__('Page cache is detected by looking for an active page cache plugin as well as making three requests to the homepage and looking for one or more of the following HTTP client caching response headers:').'</p>';
            $description .= '<code>'.implode('</code>, <code>', array_keys($this->get_page_cache_headers())).'.</code>';

            $result = [
                'badge' => [
                    'label' => __('Performance'),
                    'color' => 'blue',
                ],
                'description' => wp_kses_post($description),
                'test' => 'page_cache',
                'status' => 'good',
                'label' => '',
                'actions' => sprintf('<p><a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s<span class="screen-reader-text"> %3$s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a></p>', __('https://wordpress.org/documentation/article/optimization/#Caching'), __('Learn more about page cache'), /* translators: Hidden accessibility text. */ __('(opens in a new tab)')),
            ];

            $page_cache_detail = $this->get_page_cache_detail();

            if(is_wp_error($page_cache_detail))
            {
                $result['label'] = __('Unable to detect the presence of page cache');
                $result['status'] = 'recommended';
                $error_info = sprintf(/* translators: 1: Error message, 2: Error code. */ __('Unable to detect page cache due to possible loopback request problem. Please verify that the loopback request test is passing. Error: %1$s (Code: %2$s)'), $page_cache_detail->get_error_message(), $page_cache_detail->get_error_code());
                $result['description'] = wp_kses_post("<p>$error_info</p>").$result['description'];

                return $result;
            }

            $result['status'] = $page_cache_detail['status'];

            switch($page_cache_detail['status'])
            {
                case 'recommended':
                    $result['label'] = __('Page cache is not detected but the server response time is OK');
                    break;
                case 'good':
                    $result['label'] = __('Page cache is detected and the server response time is good');
                    break;
                default:
                    if(empty($page_cache_detail['headers']) && ! $page_cache_detail['advanced_cache_present'])
                    {
                        $result['label'] = __('Page cache is not detected and the server response time is slow');
                    }
                    else
                    {
                        $result['label'] = __('Page cache is detected but the server response time is still slow');
                    }
            }

            $page_cache_test_summary = [];

            if(empty($page_cache_detail['response_time']))
            {
                $page_cache_test_summary[] = '<span class="dashicons dashicons-dismiss"></span> '.__('Server response time could not be determined. Verify that loopback requests are working.');
            }
            else
            {
                $threshold = $this->get_good_response_time_threshold();
                if($page_cache_detail['response_time'] < $threshold)
                {
                    $page_cache_test_summary[] = '<span class="dashicons dashicons-yes-alt"></span> '.sprintf(/* translators: 1: The response time in milliseconds, 2: The recommended threshold in milliseconds. */ __('Median server response time was %1$s milliseconds. This is less than the recommended %2$s milliseconds threshold.'), number_format_i18n($page_cache_detail['response_time']), number_format_i18n($threshold));
                }
                else
                {
                    $page_cache_test_summary[] = '<span class="dashicons dashicons-warning"></span> '.sprintf(/* translators: 1: The response time in milliseconds, 2: The recommended threshold in milliseconds. */ __('Median server response time was %1$s milliseconds. It should be less than the recommended %2$s milliseconds threshold.'), number_format_i18n($page_cache_detail['response_time']), number_format_i18n($threshold));
                }

                if(empty($page_cache_detail['headers']))
                {
                    $page_cache_test_summary[] = '<span class="dashicons dashicons-warning"></span> '.__('No client caching response headers were detected.');
                }
                else
                {
                    $headers_summary = '<span class="dashicons dashicons-yes-alt"></span>';
                    $headers_summary .= ' '.sprintf(/* translators: %d: Number of caching headers. */ _n('There was %d client caching response header detected:', 'There were %d client caching response headers detected:', count($page_cache_detail['headers'])), count($page_cache_detail['headers']));
                    $headers_summary .= ' <code>'.implode('</code>, <code>', $page_cache_detail['headers']).'</code>.';
                    $page_cache_test_summary[] = $headers_summary;
                }
            }

            if($page_cache_detail['advanced_cache_present'])
            {
                $page_cache_test_summary[] = '<span class="dashicons dashicons-yes-alt"></span> '.__('A page cache plugin was detected.');
            }
            elseif(! (is_array($page_cache_detail) && ! empty($page_cache_detail['headers'])))
            {
                // Note: This message is not shown if client caching response headers were present since an external caching layer may be employed.
                $page_cache_test_summary[] = '<span class="dashicons dashicons-warning"></span> '.__('A page cache plugin was not detected.');
            }

            $result['description'] .= '<ul><li>'.implode('</li><li>', $page_cache_test_summary).'</li></ul>';

            return $result;
        }

        public function get_page_cache_headers()
        {
            $cache_hit_callback = static function($header_value)
            {
                return str_contains(strtolower($header_value), 'hit');
            };

            $cache_headers = [
                'cache-control' => static function($header_value)
                {
                    return (bool) preg_match('/max-age=[1-9]/', $header_value);
                },
                'expires' => static function($header_value)
                {
                    return strtotime($header_value) > time();
                },
                'age' => static function($header_value)
                {
                    return is_numeric($header_value) && $header_value > 0;
                },
                'last-modified' => '',
                'etag' => '',
                'x-cache-enabled' => static function($header_value)
                {
                    return 'true' === strtolower($header_value);
                },
                'x-cache-disabled' => static function($header_value)
                {
                    return ('on' !== strtolower($header_value));
                },
                'x-srcache-store-status' => $cache_hit_callback,
                'x-srcache-fetch-status' => $cache_hit_callback,
            ];

            return apply_filters('site_status_page_cache_supported_cache_headers', $cache_headers);
        }

        private function get_page_cache_detail()
        {
            $page_cache_detail = $this->check_for_page_caching();
            if(is_wp_error($page_cache_detail))
            {
                return $page_cache_detail;
            }

            // Use the median server response time.
            $response_timings = $page_cache_detail['response_timing'];
            rsort($response_timings);
            $page_speed = $response_timings[floor(count($response_timings) / 2)];

            // Obtain unique set of all client caching response headers.
            $headers = [];
            foreach($page_cache_detail['page_caching_response_headers'] as $page_caching_response_headers)
            {
                $headers = array_merge($headers, array_keys($page_caching_response_headers));
            }
            $headers = array_unique($headers);

            // Page cache is detected if there are response headers or a page cache plugin is present.
            $has_page_caching = (count($headers) > 0 || $page_cache_detail['advanced_cache_present']);

            if($page_speed && $page_speed < $this->get_good_response_time_threshold())
            {
                $result = $has_page_caching ? 'good' : 'recommended';
            }
            else
            {
                $result = 'critical';
            }

            return [
                'status' => $result,
                'advanced_cache_present' => $page_cache_detail['advanced_cache_present'],
                'headers' => $headers,
                'response_time' => $page_speed,
            ];
        }

        private function check_for_page_caching()
        {
            $sslverify = apply_filters('https_local_ssl_verify', false);

            $headers = [];

            /*
             * Include basic auth in loopback requests. Note that this will only pass along basic auth when user is
             * initiating the test. If a site requires basic auth, the test will fail when it runs in WP Cron as part of
             * wp_site_health_scheduled_check. This logic is copied from WP_Site_Health::can_perform_loopback().
             */
            if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
            {
                $headers['Authorization'] = 'Basic '.base64_encode(wp_unslash($_SERVER['PHP_AUTH_USER']).':'.wp_unslash($_SERVER['PHP_AUTH_PW']));
            }

            $caching_headers = $this->get_page_cache_headers();
            $page_caching_response_headers = [];
            $response_timing = [];

            for($i = 1; $i <= 3; $i++)
            {
                $start_time = microtime(true);
                $http_response = wp_remote_get(home_url('/'), compact('sslverify', 'headers'));
                $end_time = microtime(true);

                if(is_wp_error($http_response))
                {
                    return $http_response;
                }
                if(wp_remote_retrieve_response_code($http_response) !== 200)
                {
                    return new WP_Error('http_'.wp_remote_retrieve_response_code($http_response), wp_remote_retrieve_response_message($http_response));
                }

                $response_headers = [];

                foreach($caching_headers as $header => $callback)
                {
                    $header_values = wp_remote_retrieve_header($http_response, $header);
                    if(empty($header_values))
                    {
                        continue;
                    }
                    $header_values = (array) $header_values;
                    if(empty($callback) || (is_callable($callback) && count(array_filter($header_values, $callback)) > 0))
                    {
                        $response_headers[$header] = $header_values;
                    }
                }

                $page_caching_response_headers[] = $response_headers;
                $response_timing[] = ($end_time - $start_time) * 1000;
            }

            return [
                'advanced_cache_present' => (file_exists(WP_CONTENT_DIR.'/advanced-cache.php') && (defined('WP_CACHE') && WP_CACHE) && apply_filters('enable_loading_advanced_cache_dropin', true)),
                'page_caching_response_headers' => $page_caching_response_headers,
                'response_timing' => $response_timing,
            ];
        }

        private function get_good_response_time_threshold()
        {
            return (int) apply_filters('site_status_good_response_time_threshold', 600);
        }

        public function get_test_persistent_object_cache()
        {
            $action_url = apply_filters('site_status_persistent_object_cache_url', /* translators: Localized Support reference. */ __('https://wordpress.org/documentation/article/optimization/#persistent-object-cache'));

            $result = [
                'test' => 'persistent_object_cache',
                'status' => 'good',
                'badge' => [
                    'label' => __('Performance'),
                    'color' => 'blue',
                ],
                'label' => __('A persistent object cache is being used'),
                'description' => sprintf('<p>%s</p>', __('A persistent object cache makes your site&#8217;s database more efficient, resulting in faster load times because WordPress can retrieve your site&#8217;s content and settings much more quickly.')),
                'actions' => sprintf('<p><a href="%s" target="_blank" rel="noopener">%s<span class="screen-reader-text"> %s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a></p>', esc_url($action_url), __('Learn more about persistent object caching.'), /* translators: Hidden accessibility text. */ __('(opens in a new tab)')),
            ];

            if(wp_using_ext_object_cache())
            {
                return $result;
            }

            if(! $this->should_suggest_persistent_object_cache())
            {
                $result['label'] = __('A persistent object cache is not required');

                return $result;
            }

            $available_services = $this->available_object_cache_services();

            $notes = __('Your hosting provider can tell you if a persistent object cache can be enabled on your site.');

            if(! empty($available_services))
            {
                $notes .= ' '.sprintf(/* translators: Available object caching services. */ __('Your host appears to support the following object caching services: %s.'), implode(', ', $available_services));
            }

            $notes = apply_filters('site_status_persistent_object_cache_notes', $notes, $available_services);

            $result['status'] = 'recommended';
            $result['label'] = __('You should use a persistent object cache');
            $result['description'] .= sprintf(
                '<p>%s</p>', wp_kses($notes, [
                               'a' => ['href' => true],
                               'code' => true,
                               'em' => true,
                               'strong' => true,
                           ])
            );

            return $result;
        }

        public function should_suggest_persistent_object_cache()
        {
            global $wpdb;

            $short_circuit = apply_filters('site_status_should_suggest_persistent_object_cache', null);
            if(is_bool($short_circuit))
            {
                return $short_circuit;
            }

            if(is_multisite())
            {
                return true;
            }

            $thresholds = apply_filters('site_status_persistent_object_cache_thresholds', [
                'alloptions_count' => 500,
                'alloptions_bytes' => 100000,
                'comments_count' => 1000,
                'options_count' => 1000,
                'posts_count' => 1000,
                'terms_count' => 1000,
                'users_count' => 1000,
            ]);

            $alloptions = wp_load_alloptions();

            if($thresholds['alloptions_count'] < count($alloptions))
            {
                return true;
            }

            if($thresholds['alloptions_bytes'] < strlen(serialize($alloptions)))
            {
                return true;
            }

            $table_names = implode("','", [$wpdb->comments, $wpdb->options, $wpdb->posts, $wpdb->terms, $wpdb->users]);

            // With InnoDB the `TABLE_ROWS` are estimates, which are accurate enough and faster to retrieve than individual `COUNT()` queries.
            $results = $wpdb->get_results(
                $wpdb->prepare(// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- This query cannot use interpolation.
                    "SELECT TABLE_NAME AS 'table', TABLE_ROWS AS 'rows', SUM(data_length + index_length) as 'bytes' FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME IN ('$table_names') GROUP BY TABLE_NAME;", DB_NAME
                ), OBJECT_K
            );

            $threshold_map = [
                'comments_count' => $wpdb->comments,
                'options_count' => $wpdb->options,
                'posts_count' => $wpdb->posts,
                'terms_count' => $wpdb->terms,
                'users_count' => $wpdb->users,
            ];

            foreach($threshold_map as $threshold => $table)
            {
                if($thresholds[$threshold] <= $results[$table]->rows)
                {
                    return true;
                }
            }

            return false;
        }

        private function available_object_cache_services()
        {
            $extensions = array_map('extension_loaded', [
                'APCu' => 'apcu',
                'Redis' => 'redis',
                'Relay' => 'relay',
                'Memcache' => 'memcache',
                'Memcached' => 'memcached',
            ]);

            $services = array_keys(array_filter($extensions));

            return apply_filters('site_status_available_object_cache_services', $services);
        }

        public function admin_body_class($body_class)
        {
            $screen = get_current_screen();
            if('site-health' !== $screen->id)
            {
                return $body_class;
            }

            $body_class .= ' site-health';

            return $body_class;
        }

        public function wp_cron_scheduled_check()
        {
            // Bootstrap wp-admin, as WP_Cron doesn't do this for us.
            require_once trailingslashit(ABSPATH).'wp-admin/includes/admin.php';

            $tests = WP_Site_Health::get_tests();

            $results = [];

            $site_status = [
                'good' => 0,
                'recommended' => 0,
                'critical' => 0,
            ];

            // Don't run https test on development environments.
            if($this->is_development_environment())
            {
                unset($tests['async']['https_status']);
            }

            foreach($tests['direct'] as $test)
            {
                if(! empty($test['skip_cron']))
                {
                    continue;
                }

                if(is_string($test['test']))
                {
                    $test_function = sprintf('get_test_%s', $test['test']);

                    if(method_exists($this, $test_function) && is_callable([$this, $test_function]))
                    {
                        $results[] = $this->perform_test([$this, $test_function]);
                        continue;
                    }
                }

                if(is_callable($test['test']))
                {
                    $results[] = $this->perform_test($test['test']);
                }
            }

            foreach($tests['async'] as $test)
            {
                if(! empty($test['skip_cron']))
                {
                    continue;
                }

                // Local endpoints may require authentication, so asynchronous tests can pass a direct test runner as well.
                if(! empty($test['async_direct_test']) && is_callable($test['async_direct_test']))
                {
                    // This test is callable, do so and continue to the next asynchronous check.
                    $results[] = $this->perform_test($test['async_direct_test']);
                    continue;
                }

                if(is_string($test['test']))
                {
                    // Check if this test has a REST API endpoint.
                    if(isset($test['has_rest']) && $test['has_rest'])
                    {
                        $result_fetch = wp_remote_get($test['test'], [
                            'body' => [
                                '_wpnonce' => wp_create_nonce('wp_rest'),
                            ],
                        ]);
                    }
                    else
                    {
                        $result_fetch = wp_remote_post(admin_url('admin-ajax.php'), [
                            'body' => [
                                'action' => $test['test'],
                                '_wpnonce' => wp_create_nonce('health-check-site-status'),
                            ],
                        ]);
                    }

                    if(! is_wp_error($result_fetch) && 200 === wp_remote_retrieve_response_code($result_fetch))
                    {
                        $result = json_decode(wp_remote_retrieve_body($result_fetch), true);
                    }
                    else
                    {
                        $result = false;
                    }

                    if(is_array($result))
                    {
                        $results[] = $result;
                    }
                    else
                    {
                        $results[] = [
                            'status' => 'recommended',
                            'label' => __('A test is unavailable'),
                        ];
                    }
                }
            }

            foreach($results as $result)
            {
                if('critical' === $result['status'])
                {
                    ++$site_status['critical'];
                }
                elseif('recommended' === $result['status'])
                {
                    ++$site_status['recommended'];
                }
                else
                {
                    ++$site_status['good'];
                }
            }

            set_transient('health-check-site-status-result', wp_json_encode($site_status));
        }
    }
