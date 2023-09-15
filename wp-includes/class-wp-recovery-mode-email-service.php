<?php

    #[AllowDynamicProperties]
    final class WP_Recovery_Mode_Email_Service
    {
        const RATE_LIMIT_OPTION = 'recovery_mode_email_last_sent';

        private $link_service;

        public function __construct(WP_Recovery_Mode_Link_Service $link_service)
        {
            $this->link_service = $link_service;
        }

        public function maybe_send_recovery_mode_email($rate_limit, $error, $extension)
        {
            $last_sent = get_option(self::RATE_LIMIT_OPTION);

            if(! $last_sent || time() > $last_sent + $rate_limit)
            {
                if(! update_option(self::RATE_LIMIT_OPTION, time()))
                {
                    return new WP_Error('storage_error', __('Could not update the email last sent time.'));
                }

                $sent = $this->send_recovery_mode_email($rate_limit, $error, $extension);

                if($sent)
                {
                    return true;
                }

                return new WP_Error('email_failed', sprintf(/* translators: %s: mail() */ __('The email could not be sent. Possible reason: your host may have disabled the %s function.'), 'mail()'));
            }

            $err_message = sprintf(/* translators: 1: Last sent as a human time diff, 2: Wait time as a human time diff. */ __('A recovery link was already sent %1$s ago. Please wait another %2$s before requesting a new email.'), human_time_diff($last_sent), human_time_diff($last_sent + $rate_limit));

            return new WP_Error('email_sent_already', $err_message);
        }

        private function send_recovery_mode_email($rate_limit, $error, $extension)
        {
            $url = $this->link_service->generate_url();
            $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

            $switched_locale = switch_to_locale(get_locale());

            if($extension)
            {
                $cause = $this->get_cause($extension);
                $details = wp_strip_all_tags(wp_get_extension_error_description($error));

                if($details)
                {
                    $header = __('Error Details');
                    $details = "\n\n".$header."\n".str_pad('', strlen($header), '=')."\n".$details;
                }
            }
            else
            {
                $cause = '';
                $details = '';
            }

            $support = apply_filters('recovery_email_support_info', __('Please contact your host for assistance with investigating this issue further.'));

            $debug = apply_filters('recovery_email_debug_info', $this->get_debug($extension));

            /* translators: Do not translate LINK, EXPIRES, CAUSE, DETAILS, SITEURL, PAGEURL, SUPPORT. DEBUG: those are placeholders. */
            $message = __(
                'Howdy!

WordPress has a built-in feature that detects when a plugin or theme causes a fatal error on your site, and notifies you with this automated email.
###CAUSE###
First, visit your website (###SITEURL###) and check for any visible issues. Next, visit the page where the error was caught (###PAGEURL###) and check for any visible issues.

###SUPPORT###

If your site appears broken and you can\'t access your dashboard normally, WordPress now has a special "recovery mode". This lets you safely login to your dashboard and investigate further.

###LINK###

To keep your site safe, this link will expire in ###EXPIRES###. Don\'t worry about that, though: a new link will be emailed to you if the error occurs again after it expires.

When seeking help with this issue, you may be asked for some of the following information:
###DEBUG###

###DETAILS###'
            );
            $message = str_replace([
                                       '###LINK###',
                                       '###EXPIRES###',
                                       '###CAUSE###',
                                       '###DETAILS###',
                                       '###SITEURL###',
                                       '###PAGEURL###',
                                       '###SUPPORT###',
                                       '###DEBUG###',
                                   ], [
                                       $url,
                                       human_time_diff(time() + $rate_limit),
                                       $cause ? "\n{$cause}\n" : "\n",
                                       $details,
                                       home_url('/'),
                                       home_url($_SERVER['REQUEST_URI']),
                                       $support,
                                       implode("\r\n", $debug),
                                   ], $message);

            $email = [
                'to' => $this->get_recovery_mode_email_address(),
                /* translators: %s: Site title. */
                'subject' => __('[%s] Your Site is Experiencing a Technical Issue'),
                'message' => $message,
                'headers' => '',
                'attachments' => '',
            ];

            $email = apply_filters('recovery_mode_email', $email, $url);

            $sent = wp_mail($email['to'], wp_specialchars_decode(sprintf($email['subject'], $blogname)), $email['message'], $email['headers'], $email['attachments']);

            if($switched_locale)
            {
                restore_previous_locale();
            }

            return $sent;
        }

        private function get_cause($extension)
        {
            if('plugin' === $extension['type'])
            {
                $plugin = $this->get_plugin($extension);

                if(false === $plugin)
                {
                    $name = $extension['slug'];
                }
                else
                {
                    $name = $plugin['Name'];
                }

                /* translators: %s: Plugin name. */
                $cause = sprintf(__('In this case, WordPress caught an error with one of your plugins, %s.'), $name);
            }
            else
            {
                $theme = wp_get_theme($extension['slug']);
                $name = $theme->exists() ? $theme->display('Name') : $extension['slug'];

                /* translators: %s: Theme name. */
                $cause = sprintf(__('In this case, WordPress caught an error with your theme, %s.'), $name);
            }

            return $cause;
        }

        private function get_plugin($extension)
        {
            if(! function_exists('get_plugins'))
            {
                require_once ABSPATH.'wp-admin/includes/plugin.php';
            }

            $plugins = get_plugins();

            // Assume plugin main file name first since it is a common convention.
            if(isset($plugins["{$extension['slug']}/{$extension['slug']}.php"]))
            {
                return $plugins["{$extension['slug']}/{$extension['slug']}.php"];
            }
            else
            {
                foreach($plugins as $file => $plugin_data)
                {
                    if(str_starts_with($file, "{$extension['slug']}/") || $file === $extension['slug'])
                    {
                        return $plugin_data;
                    }
                }
            }

            return false;
        }

        private function get_debug($extension)
        {
            $theme = wp_get_theme();
            $wp_version = get_bloginfo('version');

            if($extension)
            {
                $plugin = $this->get_plugin($extension);
            }
            else
            {
                $plugin = null;
            }

            $debug = [
                'wp' => sprintf(/* translators: %s: Current WordPress version number. */ __('WordPress version %s'), $wp_version),
                'theme' => sprintf(/* translators: 1: Current active theme name. 2: Current active theme version. */ __('Active theme: %1$s (version %2$s)'), $theme->get('Name'), $theme->get('Version')),
            ];

            if(null !== $plugin)
            {
                $debug['plugin'] = sprintf(/* translators: 1: The failing plugins name. 2: The failing plugins version. */ __('Current plugin: %1$s (version %2$s)'), $plugin['Name'], $plugin['Version']);
            }

            $debug['php'] = sprintf(/* translators: %s: The currently used PHP version. */ __('PHP version %s'), PHP_VERSION);

            return $debug;
        }

        private function get_recovery_mode_email_address()
        {
            if(defined('RECOVERY_MODE_EMAIL') && is_email(RECOVERY_MODE_EMAIL))
            {
                return RECOVERY_MODE_EMAIL;
            }

            return get_option('admin_email');
        }

        public function clear_rate_limit()
        {
            return delete_option(self::RATE_LIMIT_OPTION);
        }
    }
