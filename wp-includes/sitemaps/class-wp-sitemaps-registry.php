<?php

    #[AllowDynamicProperties]
    class WP_Sitemaps_Registry
    {
        private $providers = [];

        public function add_provider($name, WP_Sitemaps_Provider $provider)
        {
            if(isset($this->providers[$name]))
            {
                return false;
            }

            $provider = apply_filters('wp_sitemaps_add_provider', $provider, $name);
            if(! $provider instanceof WP_Sitemaps_Provider)
            {
                return false;
            }

            $this->providers[$name] = $provider;

            return true;
        }

        public function get_provider($name)
        {
            if(! is_string($name) || ! isset($this->providers[$name]))
            {
                return null;
            }

            return $this->providers[$name];
        }

        public function get_providers()
        {
            return $this->providers;
        }
    }
