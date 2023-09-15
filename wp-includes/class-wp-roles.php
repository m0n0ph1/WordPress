<?php

    #[AllowDynamicProperties]
    class WP_Roles
    {
        public $roles;

        public $role_objects = [];

        public $role_names = [];

        public $role_key;

        public $use_db = true;

        protected $site_id = 0;

        public function __construct($site_id = null)
        {
            global $wp_user_roles;

            $this->use_db = empty($wp_user_roles);

            $this->for_site($site_id);
        }

        public function for_site($site_id = null)
        {
            global $wpdb;

            if(! empty($site_id))
            {
                $this->site_id = absint($site_id);
            }
            else
            {
                $this->site_id = get_current_blog_id();
            }

            $this->role_key = $wpdb->get_blog_prefix($this->site_id).'user_roles';

            if(! empty($this->roles) && ! $this->use_db)
            {
                return;
            }

            $this->roles = $this->get_roles_data();

            $this->init_roles();
        }

        protected function get_roles_data()
        {
            global $wp_user_roles;

            if(! empty($wp_user_roles))
            {
                return $wp_user_roles;
            }

            if(is_multisite() && get_current_blog_id() !== $this->site_id)
            {
                remove_action('switch_blog', 'wp_switch_roles_and_user', 1);

                $roles = get_blog_option($this->site_id, $this->role_key, []);

                add_action('switch_blog', 'wp_switch_roles_and_user', 1, 2);

                return $roles;
            }

            return get_option($this->role_key, []);
        }

        public function init_roles()
        {
            if(empty($this->roles))
            {
                return;
            }

            $this->role_objects = [];
            $this->role_names = [];
            foreach(array_keys($this->roles) as $role)
            {
                $this->role_objects[$role] = new WP_Role($role, $this->roles[$role]['capabilities']);
                $this->role_names[$role] = $this->roles[$role]['name'];
            }

            do_action('wp_roles_init', $this);
        }

        public function __call($name, $arguments)
        {
            if('_init' === $name)
            {
                return $this->_init(...$arguments);
            }

            return false;
        }

        protected function _init()
        {
            _deprecated_function(__METHOD__, '4.9.0', 'WP_Roles::for_site()');

            $this->for_site();
        }

        public function reinit()
        {
            _deprecated_function(__METHOD__, '4.7.0', 'WP_Roles::for_site()');

            $this->for_site();
        }

        public function add_role($role, $display_name, $capabilities = [])
        {
            if(empty($role) || isset($this->roles[$role]))
            {
                return;
            }

            $this->roles[$role] = [
                'name' => $display_name,
                'capabilities' => $capabilities,
            ];
            if($this->use_db)
            {
                update_option($this->role_key, $this->roles);
            }
            $this->role_objects[$role] = new WP_Role($role, $capabilities);
            $this->role_names[$role] = $display_name;

            return $this->role_objects[$role];
        }

        public function remove_role($role)
        {
            if(! isset($this->role_objects[$role]))
            {
                return;
            }

            unset($this->role_objects[$role]);
            unset($this->role_names[$role]);
            unset($this->roles[$role]);

            if($this->use_db)
            {
                update_option($this->role_key, $this->roles);
            }

            if(get_option('default_role') === $role)
            {
                update_option('default_role', 'subscriber');
            }
        }

        public function add_cap($role, $cap, $grant = true)
        {
            if(! isset($this->roles[$role]))
            {
                return;
            }

            $this->roles[$role]['capabilities'][$cap] = $grant;
            if($this->use_db)
            {
                update_option($this->role_key, $this->roles);
            }
        }

        public function remove_cap($role, $cap)
        {
            if(! isset($this->roles[$role]))
            {
                return;
            }

            unset($this->roles[$role]['capabilities'][$cap]);
            if($this->use_db)
            {
                update_option($this->role_key, $this->roles);
            }
        }

        public function get_role($role)
        {
            if(isset($this->role_objects[$role]))
            {
                return $this->role_objects[$role];
            }
            else
            {
                return null;
            }
        }

        public function get_names()
        {
            return $this->role_names;
        }

        public function is_role($role)
        {
            return isset($this->role_names[$role]);
        }

        public function get_site_id()
        {
            return $this->site_id;
        }
    }
