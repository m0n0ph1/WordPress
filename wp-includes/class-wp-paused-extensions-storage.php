<?php

    #[AllowDynamicProperties]
    class WP_Paused_Extensions_Storage
    {
        protected $type;

        public function __construct($extension_type)
        {
            $this->type = $extension_type;
        }

        public function set($extension, $error)
        {
            if(! $this->is_api_loaded())
            {
                return false;
            }

            $option_name = $this->get_option_name();

            if(! $option_name)
            {
                return false;
            }

            $paused_extensions = (array) get_option($option_name, []);

            // Do not update if the error is already stored.
            if(isset($paused_extensions[$this->type][$extension]) && $paused_extensions[$this->type][$extension] === $error)
            {
                return true;
            }

            $paused_extensions[$this->type][$extension] = $error;

            return update_option($option_name, $paused_extensions);
        }

        protected function is_api_loaded()
        {
            return function_exists('get_option');
        }

        protected function get_option_name()
        {
            if(! wp_recovery_mode()->is_active())
            {
                return '';
            }

            $session_id = wp_recovery_mode()->get_session_id();
            if(empty($session_id))
            {
                return '';
            }

            return "{$session_id}_paused_extensions";
        }

        public function delete($extension)
        {
            if(! $this->is_api_loaded())
            {
                return false;
            }

            $option_name = $this->get_option_name();

            if(! $option_name)
            {
                return false;
            }

            $paused_extensions = (array) get_option($option_name, []);

            // Do not delete if no error is stored.
            if(! isset($paused_extensions[$this->type][$extension]))
            {
                return true;
            }

            unset($paused_extensions[$this->type][$extension]);

            if(empty($paused_extensions[$this->type]))
            {
                unset($paused_extensions[$this->type]);
            }

            // Clean up the entire option if we're removing the only error.
            if(! $paused_extensions)
            {
                return delete_option($option_name);
            }

            return update_option($option_name, $paused_extensions);
        }

        public function get($extension)
        {
            if(! $this->is_api_loaded())
            {
                return null;
            }

            $paused_extensions = $this->get_all();

            if(! isset($paused_extensions[$extension]))
            {
                return null;
            }

            return $paused_extensions[$extension];
        }

        public function get_all()
        {
            if(! $this->is_api_loaded())
            {
                return [];
            }

            $option_name = $this->get_option_name();

            if(! $option_name)
            {
                return [];
            }

            $paused_extensions = (array) get_option($option_name, []);

            if(isset($paused_extensions[$this->type]))
            {
                return $paused_extensions[$this->type];
            }

            return [];
        }

        public function delete_all()
        {
            if(! $this->is_api_loaded())
            {
                return false;
            }

            $option_name = $this->get_option_name();

            if(! $option_name)
            {
                return false;
            }

            $paused_extensions = (array) get_option($option_name, []);

            unset($paused_extensions[$this->type]);

            if(! $paused_extensions)
            {
                return delete_option($option_name);
            }

            return update_option($option_name, $paused_extensions);
        }
    }
