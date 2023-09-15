<?php

    function wp_initial_constants()
    {
        global $blog_id, $wp_version;

        define('KB_IN_BYTES', 1024);
        define('MB_IN_BYTES', 1024 * KB_IN_BYTES);
        define('GB_IN_BYTES', 1024 * MB_IN_BYTES);
        define('TB_IN_BYTES', 1024 * GB_IN_BYTES);
        define('PB_IN_BYTES', 1024 * TB_IN_BYTES);
        define('EB_IN_BYTES', 1024 * PB_IN_BYTES);
        define('ZB_IN_BYTES', 1024 * EB_IN_BYTES);
        define('YB_IN_BYTES', 1024 * ZB_IN_BYTES);

        // Start of run timestamp.
        if(! defined('WP_START_TIMESTAMP'))
        {
            define('WP_START_TIMESTAMP', microtime(true));
        }

        $current_limit = ini_get('memory_limit');
        $current_limit_int = wp_convert_hr_to_bytes($current_limit);

        // Define memory limits.
        if(! defined('WP_MEMORY_LIMIT'))
        {
            if(false === wp_is_ini_value_changeable('memory_limit'))
            {
                define('WP_MEMORY_LIMIT', $current_limit);
            }
            elseif(is_multisite())
            {
                define('WP_MEMORY_LIMIT', '64M');
            }
            else
            {
                define('WP_MEMORY_LIMIT', '40M');
            }
        }

        if(! defined('WP_MAX_MEMORY_LIMIT'))
        {
            if(false === wp_is_ini_value_changeable('memory_limit'))
            {
                define('WP_MAX_MEMORY_LIMIT', $current_limit);
            }
            elseif(-1 === $current_limit_int || $current_limit_int > 268435456 /* = 256M */)
            {
                define('WP_MAX_MEMORY_LIMIT', $current_limit);
            }
            else
            {
                define('WP_MAX_MEMORY_LIMIT', '256M');
            }
        }

        // Set memory limits.
        $wp_limit_int = wp_convert_hr_to_bytes(WP_MEMORY_LIMIT);
        if(-1 !== $current_limit_int && (-1 === $wp_limit_int || $wp_limit_int > $current_limit_int))
        {
            ini_set('memory_limit', WP_MEMORY_LIMIT);
        }

        if(! isset($blog_id))
        {
            $blog_id = 1;
        }

        if(! defined('WP_CONTENT_DIR'))
        {
            define('WP_CONTENT_DIR', ABSPATH.'wp-content'); // No trailing slash, full paths only - WP_CONTENT_URL is defined further down.
        }

        /*
         * Add define( 'WP_DEVELOPMENT_MODE', 'core' ), or define( 'WP_DEVELOPMENT_MODE', 'plugin' ), or
         * define( 'WP_DEVELOPMENT_MODE', 'theme' ), or define( 'WP_DEVELOPMENT_MODE', 'all' ) to wp-config.php
         * to signify development mode for WordPress core, a plugin, a theme, or all three types respectively.
         */
        if(! defined('WP_DEVELOPMENT_MODE'))
        {
            define('WP_DEVELOPMENT_MODE', '');
        }

        // Add define( 'WP_DEBUG', true ); to wp-config.php to enable display of notices during development.
        if(! defined('WP_DEBUG'))
        {
            if(wp_get_development_mode() || 'development' === wp_get_environment_type())
            {
                define('WP_DEBUG', true);
            }
            else
            {
                define('WP_DEBUG', false);
            }
        }

        /*
         * Add define( 'WP_DEBUG_DISPLAY', null ); to wp-config.php to use the globally configured setting
         * for 'display_errors' and not force errors to be displayed. Use false to force 'display_errors' off.
         */
        if(! defined('WP_DEBUG_DISPLAY'))
        {
            define('WP_DEBUG_DISPLAY', true);
        }

        // Add define( 'WP_DEBUG_LOG', true ); to enable error logging to wp-content/debug.log.
        if(! defined('WP_DEBUG_LOG'))
        {
            define('WP_DEBUG_LOG', false);
        }

        if(! defined('WP_CACHE'))
        {
            define('WP_CACHE', false);
        }

        /*
         * Add define( 'SCRIPT_DEBUG', true ); to wp-config.php to enable loading of non-minified,
         * non-concatenated scripts and stylesheets.
         */
        if(! defined('SCRIPT_DEBUG'))
        {
            if(! empty($wp_version))
            {
                $develop_src = str_contains($wp_version, '-src');
            }
            else
            {
                $develop_src = false;
            }

            define('SCRIPT_DEBUG', $develop_src);
        }

        if(! defined('MEDIA_TRASH'))
        {
            define('MEDIA_TRASH', false);
        }

        if(! defined('SHORTINIT'))
        {
            define('SHORTINIT', false);
        }

        // Constants for features added to WP that should short-circuit their plugin implementations.
        define('WP_FEATURE_BETTER_PASSWORDS', true);

        define('MINUTE_IN_SECONDS', 60);
        define('HOUR_IN_SECONDS', 60 * MINUTE_IN_SECONDS);
        define('DAY_IN_SECONDS', 24 * HOUR_IN_SECONDS);
        define('WEEK_IN_SECONDS', 7 * DAY_IN_SECONDS);
        define('MONTH_IN_SECONDS', 30 * DAY_IN_SECONDS);
        define('YEAR_IN_SECONDS', 365 * DAY_IN_SECONDS);
    }

    function wp_plugin_directory_constants()
    {
        if(! defined('WP_CONTENT_URL'))
        {
            define('WP_CONTENT_URL', get_option('siteurl').'/wp-content'); // Full URL - WP_CONTENT_DIR is defined further up.
        }

        if(! defined('WP_PLUGIN_DIR'))
        {
            define('WP_PLUGIN_DIR', WP_CONTENT_DIR.'/plugins'); // Full path, no trailing slash.
        }

        if(! defined('WP_PLUGIN_URL'))
        {
            define('WP_PLUGIN_URL', WP_CONTENT_URL.'/plugins'); // Full URL, no trailing slash.
        }

        if(! defined('PLUGINDIR'))
        {
            define('PLUGINDIR', 'wp-content/plugins'); // Relative to ABSPATH. For back compat.
        }

        if(! defined('WPMU_PLUGIN_DIR'))
        {
            define('WPMU_PLUGIN_DIR', WP_CONTENT_DIR.'/mu-plugins'); // Full path, no trailing slash.
        }

        if(! defined('WPMU_PLUGIN_URL'))
        {
            define('WPMU_PLUGIN_URL', WP_CONTENT_URL.'/mu-plugins'); // Full URL, no trailing slash.
        }

        if(! defined('MUPLUGINDIR'))
        {
            define('MUPLUGINDIR', 'wp-content/mu-plugins'); // Relative to ABSPATH. For back compat.
        }
    }

    function wp_cookie_constants()
    {
        if(! defined('COOKIEHASH'))
        {
            $siteurl = get_site_option('siteurl');
            if($siteurl)
            {
                define('COOKIEHASH', md5($siteurl));
            }
            else
            {
                define('COOKIEHASH', '');
            }
        }

        if(! defined('USER_COOKIE'))
        {
            define('USER_COOKIE', 'wordpressuser_'.COOKIEHASH);
        }

        if(! defined('PASS_COOKIE'))
        {
            define('PASS_COOKIE', 'wordpresspass_'.COOKIEHASH);
        }

        if(! defined('AUTH_COOKIE'))
        {
            define('AUTH_COOKIE', 'wordpress_'.COOKIEHASH);
        }

        if(! defined('SECURE_AUTH_COOKIE'))
        {
            define('SECURE_AUTH_COOKIE', 'wordpress_sec_'.COOKIEHASH);
        }

        if(! defined('LOGGED_IN_COOKIE'))
        {
            define('LOGGED_IN_COOKIE', 'wordpress_logged_in_'.COOKIEHASH);
        }

        if(! defined('TEST_COOKIE'))
        {
            define('TEST_COOKIE', 'wordpress_test_cookie');
        }

        if(! defined('COOKIEPATH'))
        {
            define('COOKIEPATH', preg_replace('|https?://[^/]+|i', '', get_option('home').'/'));
        }

        if(! defined('SITECOOKIEPATH'))
        {
            define('SITECOOKIEPATH', preg_replace('|https?://[^/]+|i', '', get_option('siteurl').'/'));
        }

        if(! defined('ADMIN_COOKIE_PATH'))
        {
            define('ADMIN_COOKIE_PATH', SITECOOKIEPATH.'wp-admin');
        }

        if(! defined('PLUGINS_COOKIE_PATH'))
        {
            define('PLUGINS_COOKIE_PATH', preg_replace('|https?://[^/]+|i', '', WP_PLUGIN_URL));
        }

        if(! defined('COOKIE_DOMAIN'))
        {
            define('COOKIE_DOMAIN', false);
        }

        if(! defined('RECOVERY_MODE_COOKIE'))
        {
            define('RECOVERY_MODE_COOKIE', 'wordpress_rec_'.COOKIEHASH);
        }
    }

    function wp_ssl_constants()
    {
        if(! defined('FORCE_SSL_ADMIN'))
        {
            if('https' === parse_url(get_option('siteurl'), PHP_URL_SCHEME))
            {
                define('FORCE_SSL_ADMIN', true);
            }
            else
            {
                define('FORCE_SSL_ADMIN', false);
            }
        }
        force_ssl_admin(FORCE_SSL_ADMIN);

        if(defined('FORCE_SSL_LOGIN') && FORCE_SSL_LOGIN)
        {
            force_ssl_admin(true);
        }
    }

    function wp_functionality_constants()
    {
        if(! defined('AUTOSAVE_INTERVAL'))
        {
            define('AUTOSAVE_INTERVAL', MINUTE_IN_SECONDS);
        }

        if(! defined('EMPTY_TRASH_DAYS'))
        {
            define('EMPTY_TRASH_DAYS', 30);
        }

        if(! defined('WP_POST_REVISIONS'))
        {
            define('WP_POST_REVISIONS', true);
        }

        if(! defined('WP_CRON_LOCK_TIMEOUT'))
        {
            define('WP_CRON_LOCK_TIMEOUT', MINUTE_IN_SECONDS);
        }
    }

    function wp_templating_constants()
    {
        define('TEMPLATEPATH', get_template_directory());

        define('STYLESHEETPATH', get_stylesheet_directory());

        if(! defined('WP_DEFAULT_THEME'))
        {
            define('WP_DEFAULT_THEME', 'twentytwentythree');
        }
    }
