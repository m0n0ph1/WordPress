<?php

    #[AllowDynamicProperties]
    final class WP_Block_Patterns_Registry
    {
        private static $instance = null;

        private $registered_patterns = [];

        private $registered_patterns_outside_init = [];

        public static function get_instance()
        {
            if(null === self::$instance)
            {
                self::$instance = new self();
            }

            return self::$instance;
        }

        public function register($pattern_name, $pattern_properties)
        {
            if(! isset($pattern_name) || ! is_string($pattern_name))
            {
                _doing_it_wrong(__METHOD__, __('Pattern name must be a string.'), '5.5.0');

                return false;
            }

            if(! isset($pattern_properties['title']) || ! is_string($pattern_properties['title']))
            {
                _doing_it_wrong(__METHOD__, __('Pattern title must be a string.'), '5.5.0');

                return false;
            }

            if(! isset($pattern_properties['content']) || ! is_string($pattern_properties['content']))
            {
                _doing_it_wrong(__METHOD__, __('Pattern content must be a string.'), '5.5.0');

                return false;
            }

            $pattern = array_merge($pattern_properties, ['name' => $pattern_name]);

            $this->registered_patterns[$pattern_name] = $pattern;

            // If the pattern is registered inside an action other than `init`, store it
            // also to a dedicated array. Used to detect deprecated registrations inside
            // `admin_init` or `current_screen`.
            if(current_action() && 'init' !== current_action())
            {
                $this->registered_patterns_outside_init[$pattern_name] = $pattern;
            }

            return true;
        }

        public function unregister($pattern_name)
        {
            if(! $this->is_registered($pattern_name))
            {
                _doing_it_wrong(__METHOD__, /* translators: %s: Pattern name. */ sprintf(__('Pattern "%s" not found.'), $pattern_name), '5.5.0');

                return false;
            }

            unset($this->registered_patterns[$pattern_name]);
            unset($this->registered_patterns_outside_init[$pattern_name]);

            return true;
        }

        public function is_registered($pattern_name)
        {
            return isset($this->registered_patterns[$pattern_name]);
        }

        public function get_registered($pattern_name)
        {
            if(! $this->is_registered($pattern_name))
            {
                return null;
            }

            return $this->registered_patterns[$pattern_name];
        }

        public function get_all_registered($outside_init_only = false)
        {
            return array_values($outside_init_only ? $this->registered_patterns_outside_init : $this->registered_patterns);
        }
    }

    function register_block_pattern($pattern_name, $pattern_properties)
    {
        return WP_Block_Patterns_Registry::get_instance()->register($pattern_name, $pattern_properties);
    }

    function unregister_block_pattern($pattern_name)
    {
        return WP_Block_Patterns_Registry::get_instance()->unregister($pattern_name);
    }
