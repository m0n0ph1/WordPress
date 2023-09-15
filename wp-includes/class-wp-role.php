<?php

    #[AllowDynamicProperties]
    class WP_Role
    {
        public $name;

        public $capabilities;

        public function __construct($role, $capabilities)
        {
            $this->name = $role;
            $this->capabilities = $capabilities;
        }

        public function add_cap($cap, $grant = true)
        {
            $this->capabilities[$cap] = $grant;
            wp_roles()->add_cap($this->name, $cap, $grant);
        }

        public function remove_cap($cap)
        {
            unset($this->capabilities[$cap]);
            wp_roles()->remove_cap($this->name, $cap);
        }

        public function has_cap($cap)
        {
            $capabilities = apply_filters('role_has_cap', $this->capabilities, $cap, $this->name);

            if(! empty($capabilities[$cap]))
            {
                return $capabilities[$cap];
            }
            else
            {
                return false;
            }
        }
    }
