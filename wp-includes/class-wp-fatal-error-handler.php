<?php

    #[AllowDynamicProperties]
    class WP_Fatal_Error_Handler
    {
        public function handle()
        {
            if(defined('WP_SANDBOX_SCRAPING') && WP_SANDBOX_SCRAPING)
            {
                return;
            }

            // Do not trigger the fatal error handler while updates are being installed.
            if(wp_is_maintenance_mode())
            {
                return;
            }

            try
            {
                // Bail if no error found.
                $error = $this->detect_error();
                if(! $error)
                {
                    return;
                }

                if(! isset($GLOBALS['wp_locale']) && function_exists('load_default_textdomain'))
                {
                    load_default_textdomain();
                }

                $handled = false;

                if(! is_multisite() && wp_recovery_mode()->is_initialized())
                {
                    $handled = wp_recovery_mode()->handle_error($error);
                }

                // Display the PHP error template if headers not sent.
                if(is_admin() || ! headers_sent())
                {
                    $this->display_error_template($error, $handled);
                }
            }
            catch(Exception $e)
            {
                // Catch exceptions and remain silent.
            }
        }

        protected function detect_error()
        {
            $error = error_get_last();

            // No error, just skip the error handling code.
            // Bail if this error should not be handled.
            if(null === $error || ! $this->should_handle_error($error))
            {
                return null;
            }

            return $error;
        }

        protected function should_handle_error($error)
        {
            $error_types_to_handle = [
                E_ERROR,
                E_PARSE,
                E_USER_ERROR,
                E_COMPILE_ERROR,
                E_RECOVERABLE_ERROR,
            ];

            if(isset($error['type']) && in_array($error['type'], $error_types_to_handle, true))
            {
                return true;
            }

            return (bool) apply_filters('wp_should_handle_php_error', false, $error);
        }

        protected function display_error_template($error, $handled)
        {
            if(defined('WP_CONTENT_DIR'))
            {
                // Load custom PHP error template, if present.
                $php_error_pluggable = WP_CONTENT_DIR.'/php-error.php';
                if(is_readable($php_error_pluggable))
                {
                    require_once $php_error_pluggable;

                    return;
                }
            }

            // Otherwise, display the default error template.
            $this->display_default_error_template($error, $handled);
        }

        protected function display_default_error_template($error, $handled)
        {
            if(! function_exists('__'))
            {
                wp_load_translations_early();
            }

            if(! function_exists('wp_die'))
            {
                require_once ABSPATH.WPINC.'/functions.php';
            }

            if(! class_exists('WP_Error'))
            {
                require_once ABSPATH.WPINC.'/class-wp-error.php';
            }

            if(true === $handled && wp_is_recovery_mode())
            {
                $message = __('There has been a critical error on this website, putting it in recovery mode. Please check the Themes and Plugins screens for more details. If you just installed or updated a theme or plugin, check the relevant page for that first.');
            }
            elseif(is_protected_endpoint() && wp_recovery_mode()->is_initialized())
            {
                if(is_multisite())
                {
                    $message = __('There has been a critical error on this website. Please reach out to your site administrator, and inform them of this error for further assistance.');
                }
                else
                {
                    $message = __('There has been a critical error on this website. Please check your site admin email inbox for instructions.');
                }
            }
            else
            {
                $message = __('There has been a critical error on this website.');
            }

            $message = sprintf('<p>%s</p><p><a href="%s">%s</a></p>', $message, /* translators: Documentation about troubleshooting. */ __('https://wordpress.org/documentation/article/faq-troubleshooting/'), __('Learn more about troubleshooting WordPress.'));

            $args = [
                'response' => 500,
                'exit' => false,
            ];

            $message = apply_filters('wp_php_error_message', $message, $error);

            $args = apply_filters('wp_php_error_args', $args, $error);

            $wp_error = new WP_Error('internal_server_error', $message, [
                'error' => $error,
            ]);

            wp_die($wp_error, '', $args);
        }
    }
