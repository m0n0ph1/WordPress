<?php

    /*
     * Integrators who cannot yet upgrade to the PSR-4 class names can silence deprecations
     * by defining a `REQUESTS_SILENCE_PSR0_DEPRECATIONS` constant and setting it to `true`.
     * The constant needs to be defined before this class is required.
     */
    if(! defined('REQUESTS_SILENCE_PSR0_DEPRECATIONS') || REQUESTS_SILENCE_PSR0_DEPRECATIONS !== true)
    {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
        trigger_error('The PSR-0 `Requests_...` class names in the Requests library are deprecated.'.' Switch to the PSR-4 `WpOrg\Requests\...` class names at your earliest convenience.', E_USER_DEPRECATED);

        // Prevent the deprecation notice from being thrown twice.
        if(! defined('REQUESTS_SILENCE_PSR0_DEPRECATIONS'))
        {
            define('REQUESTS_SILENCE_PSR0_DEPRECATIONS', true);
        }
    }

    require_once __DIR__.'/Requests/src/Requests.php';

    class Requests extends WpOrg\Requests\Requests
    {
        public static function autoloader($class)
        {
            if(class_exists('WpOrg\Requests\Autoload') === false)
            {
                require_once __DIR__.'/Requests/src/Autoload.php';
            }

            return WpOrg\Requests\Autoload::load($class);
        }

        public static function register_autoloader()
        {
            require_once __DIR__.'/Requests/src/Autoload.php';
            WpOrg\Requests\Autoload::register();
        }
    }
