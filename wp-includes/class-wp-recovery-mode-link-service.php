<?php

    #[AllowDynamicProperties]
    class WP_Recovery_Mode_Link_Service
    {
        public const LOGIN_ACTION_ENTER = 'enter_recovery_mode';

        public const LOGIN_ACTION_ENTERED = 'entered_recovery_mode';

        private $key_service;

        private $cookie_service;

        public function __construct(
            WP_Recovery_Mode_Cookie_Service $cookie_service, WP_Recovery_Mode_Key_Service $key_service
        ) {
            $this->cookie_service = $cookie_service;
            $this->key_service = $key_service;
        }

        public function generate_url()
        {
            $token = $this->key_service->generate_recovery_mode_token();
            $key = $this->key_service->generate_and_store_recovery_mode_key($token);

            return $this->get_recovery_mode_begin_url($token, $key);
        }

        private function get_recovery_mode_begin_url($token, $key)
        {
            $url = add_query_arg([
                                     'action' => self::LOGIN_ACTION_ENTER,
                                     'rm_token' => $token,
                                     'rm_key' => $key,
                                 ], wp_login_url());

            return apply_filters('recovery_mode_begin_url', $url, $token, $key);
        }

        public function handle_begin_link($ttl)
        {
            if(! isset($GLOBALS['pagenow']) || 'wp-login.php' !== $GLOBALS['pagenow'] || ! isset($_GET['action'], $_GET['rm_token'], $_GET['rm_key']) || self::LOGIN_ACTION_ENTER !== $_GET['action'])
            {
                return;
            }

            if(! function_exists('wp_generate_password'))
            {
                require_once ABSPATH.WPINC.'/pluggable.php';
            }

            $validated = $this->key_service->validate_recovery_mode_key($_GET['rm_token'], $_GET['rm_key'], $ttl);

            if(is_wp_error($validated))
            {
                wp_die($validated, '');
            }

            $this->cookie_service->set_cookie();

            $url = add_query_arg('action', self::LOGIN_ACTION_ENTERED, wp_login_url());
            wp_redirect($url);
            die;
        }
    }
