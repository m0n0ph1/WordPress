<?php

    #[AllowDynamicProperties]
    abstract class WP_Session_Tokens
    {
        protected $user_id;

        protected function __construct($user_id)
        {
            $this->user_id = $user_id;
        }

        final public static function get_instance($user_id)
        {
            $manager = apply_filters('session_token_manager', 'WP_User_Meta_Session_Tokens');

            return new $manager($user_id);
        }

        final public static function destroy_all_for_all_users()
        {
            $manager = apply_filters('session_token_manager', 'WP_User_Meta_Session_Tokens');
            call_user_func([$manager, 'drop_sessions']);
        }

        public static function drop_sessions() {}

        final public function get($token)
        {
            $verifier = $this->hash_token($token);

            return $this->get_session($verifier);
        }

        private function hash_token($token)
        {
            // If ext/hash is not present, use sha1() instead.
            if(function_exists('hash'))
            {
                return hash('sha256', $token);
            }
            else
            {
                return sha1($token);
            }
        }

        abstract protected function get_session($verifier);

        final public function verify($token)
        {
            $verifier = $this->hash_token($token);

            return (bool) $this->get_session($verifier);
        }

        final public function create($expiration)
        {
            $session = apply_filters('attach_session_information', [], $this->user_id);
            $session['expiration'] = $expiration;

            // IP address.
            if(! empty($_SERVER['REMOTE_ADDR']))
            {
                $session['ip'] = $_SERVER['REMOTE_ADDR'];
            }

            // User-agent.
            if(! empty($_SERVER['HTTP_USER_AGENT']))
            {
                $session['ua'] = wp_unslash($_SERVER['HTTP_USER_AGENT']);
            }

            // Timestamp.
            $session['login'] = time();

            $token = wp_generate_password(43, false, false);

            $this->update($token, $session);

            return $token;
        }

        final public function update($token, $session)
        {
            $verifier = $this->hash_token($token);
            $this->update_session($verifier, $session);
        }

        abstract protected function update_session($verifier, $session = null);

        final public function destroy($token)
        {
            $verifier = $this->hash_token($token);
            $this->update_session($verifier, null);
        }

        final public function destroy_others($token_to_keep)
        {
            $verifier = $this->hash_token($token_to_keep);
            $session = $this->get_session($verifier);
            if($session)
            {
                $this->destroy_other_sessions($verifier);
            }
            else
            {
                $this->destroy_all_sessions();
            }
        }

        abstract protected function destroy_other_sessions($verifier);

        abstract protected function destroy_all_sessions();

        final public function destroy_all()
        {
            $this->destroy_all_sessions();
        }

        final public function get_all()
        {
            return array_values($this->get_sessions());
        }

        abstract protected function get_sessions();

        final protected function is_still_valid($session)
        {
            return $session['expiration'] >= time();
        }
    }
