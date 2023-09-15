<?php

    #[AllowDynamicProperties]
    final class WP_Block_Pattern_Categories_Registry
    {
        private static $instance = null;

        private $registered_categories = [];

        private $registered_categories_outside_init = [];

        public static function get_instance()
        {
            if(null === self::$instance)
            {
                self::$instance = new self();
            }

            return self::$instance;
        }

        public function register($category_name, $category_properties)
        {
            if(! isset($category_name) || ! is_string($category_name))
            {
                _doing_it_wrong(__METHOD__, __('Block pattern category name must be a string.'), '5.5.0');

                return false;
            }

            $category = array_merge(['name' => $category_name], $category_properties);

            $this->registered_categories[$category_name] = $category;

            // If the category is registered inside an action other than `init`, store it
            // also to a dedicated array. Used to detect deprecated registrations inside
            // `admin_init` or `current_screen`.
            if(current_action() && 'init' !== current_action())
            {
                $this->registered_categories_outside_init[$category_name] = $category;
            }

            return true;
        }

        public function unregister($category_name)
        {
            if(! $this->is_registered($category_name))
            {
                _doing_it_wrong(__METHOD__, /* translators: %s: Block pattern name. */ sprintf(__('Block pattern category "%s" not found.'), $category_name), '5.5.0');

                return false;
            }

            unset($this->registered_categories[$category_name]);
            unset($this->registered_categories_outside_init[$category_name]);

            return true;
        }

        public function is_registered($category_name)
        {
            return isset($this->registered_categories[$category_name]);
        }

        public function get_registered($category_name)
        {
            if(! $this->is_registered($category_name))
            {
                return null;
            }

            return $this->registered_categories[$category_name];
        }

        public function get_all_registered($outside_init_only = false)
        {
            return array_values($outside_init_only ? $this->registered_categories_outside_init : $this->registered_categories);
        }
    }

    function register_block_pattern_category($category_name, $category_properties)
    {
        return WP_Block_Pattern_Categories_Registry::get_instance()->register($category_name, $category_properties);
    }

    function unregister_block_pattern_category($category_name)
    {
        return WP_Block_Pattern_Categories_Registry::get_instance()->unregister($category_name);
    }
