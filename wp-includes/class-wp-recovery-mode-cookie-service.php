<?php

    #[AllowDynamicProperties]
    final class WP_Recovery_Mode_Cookie_Service
    {
        public function is_cookie_set()
        {
            return ! empty($_COOKIE[RECOVERY_MODE_COOKIE]);
        }

        public function set_cookie()
        {
            $value = $this->generate_cookie();

            $length = apply_filters('recovery_mode_cookie_length', WEEK_IN_SECONDS);

            $expire = time() + $length;

            setcookie(RECOVERY_MODE_COOKIE, $value, $expire, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);

            if(COOKIEPATH !== SITECOOKIEPATH)
            {
                setcookie(RECOVERY_MODE_COOKIE, $value, $expire, SITECOOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            }
        }

        private function generate_cookie()
        {
            $to_sign = sprintf('recovery_mode|%s|%s', time(), wp_generate_password(20, false));
            $signed = $this->recovery_mode_hash($to_sign);

            return base64_encode(sprintf('%s|%s', $to_sign, $signed));
        }

        private function recovery_mode_hash($data)
        {
            $default_keys = array_unique([
                                             'put your unique phrase here',
                                             /*
                                              * translators: This string should only be translated if wp-config-sample.php is localized.
                                              * You can check the localized release package or
                                              * https://i18n.svn.wordpress.org/<locale code>/branches/<wp version>/dist/wp-config-sample.php
                                              */ __('put your unique phrase here'),
                                         ]);

            if(! defined('AUTH_KEY') || in_array(AUTH_KEY, $default_keys, true))
            {
                $auth_key = get_site_option('recovery_mode_auth_key');

                if(! $auth_key)
                {
                    if(! function_exists('wp_generate_password'))
                    {
                        require_once ABSPATH.WPINC.'/pluggable.php';
                    }

                    $auth_key = wp_generate_password(64, true, true);
                    update_site_option('recovery_mode_auth_key', $auth_key);
                }
            }
            else
            {
                $auth_key = AUTH_KEY;
            }

            if(! defined('AUTH_SALT') || in_array(AUTH_SALT, $default_keys, true) || AUTH_SALT === $auth_key)
            {
                $auth_salt = get_site_option('recovery_mode_auth_salt');

                if(! $auth_salt)
                {
                    if(! function_exists('wp_generate_password'))
                    {
                        require_once ABSPATH.WPINC.'/pluggable.php';
                    }

                    $auth_salt = wp_generate_password(64, true, true);
                    update_site_option('recovery_mode_auth_salt', $auth_salt);
                }
            }
            else
            {
                $auth_salt = AUTH_SALT;
            }

            $secret = $auth_key.$auth_salt;

            return hash_hmac('sha1', $data, $secret);
        }

        public function clear_cookie()
        {
            setcookie(RECOVERY_MODE_COOKIE, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
            setcookie(RECOVERY_MODE_COOKIE, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN);
        }

        public function validate_cookie($cookie = '')
        {
            if(! $cookie)
            {
                if(empty($_COOKIE[RECOVERY_MODE_COOKIE]))
                {
                    return new WP_Error('no_cookie', __('No cookie present.'));
                }

                $cookie = $_COOKIE[RECOVERY_MODE_COOKIE];
            }

            $parts = $this->parse_cookie($cookie);

            if(is_wp_error($parts))
            {
                return $parts;
            }

            [, $created_at, $random, $signature] = $parts;

            if(! ctype_digit($created_at))
            {
                return new WP_Error('invalid_created_at', __('Invalid cookie format.'));
            }

            $length = apply_filters('recovery_mode_cookie_length', WEEK_IN_SECONDS);

            if(time() > $created_at + $length)
            {
                return new WP_Error('expired', __('Cookie expired.'));
            }

            $to_sign = sprintf('recovery_mode|%s|%s', $created_at, $random);
            $hashed = $this->recovery_mode_hash($to_sign);

            if(! hash_equals($signature, $hashed))
            {
                return new WP_Error('signature_mismatch', __('Invalid cookie.'));
            }

            return true;
        }

        private function parse_cookie($cookie)
        {
            $cookie = base64_decode($cookie);
            $parts = explode('|', $cookie);

            if(4 !== count($parts))
            {
                return new WP_Error('invalid_format', __('Invalid cookie format.'));
            }

            return $parts;
        }

        public function get_session_id_from_cookie($cookie = '')
        {
            if(! $cookie)
            {
                if(empty($_COOKIE[RECOVERY_MODE_COOKIE]))
                {
                    return new WP_Error('no_cookie', __('No cookie present.'));
                }

                $cookie = $_COOKIE[RECOVERY_MODE_COOKIE];
            }

            $parts = $this->parse_cookie($cookie);
            if(is_wp_error($parts))
            {
                return $parts;
            }

            [, , $random] = $parts;

            return sha1($random);
        }
    }
