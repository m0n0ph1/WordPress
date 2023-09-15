<?php

    #[AllowDynamicProperties]
    class WP_Style_Engine_CSS_Rules_Store
    {
        protected static $stores = [];

        protected $name = '';

        protected $rules = [];

        public static function get_store($store_name = 'default')
        {
            if(! is_string($store_name) || empty($store_name))
            {
                return;
            }
            if(! isset(static::$stores[$store_name]))
            {
                static::$stores[$store_name] = new static();
                // Set the store name.
                static::$stores[$store_name]->set_name($store_name);
            }

            return static::$stores[$store_name];
        }

        public static function get_stores()
        {
            return static::$stores;
        }

        public static function remove_all_stores()
        {
            static::$stores = [];
        }

        public function get_name()
        {
            return $this->name;
        }

        public function set_name($name)
        {
            $this->name = $name;
        }

        public function get_all_rules()
        {
            return $this->rules;
        }

        public function add_rule($selector)
        {
            $selector = trim($selector);

            // Bail early if there is no selector.
            if(empty($selector))
            {
                return;
            }

            // Create the rule if it doesn't exist.
            if(empty($this->rules[$selector]))
            {
                $this->rules[$selector] = new WP_Style_Engine_CSS_Rule($selector);
            }

            return $this->rules[$selector];
        }

        public function remove_rule($selector)
        {
            unset($this->rules[$selector]);
        }
    }
