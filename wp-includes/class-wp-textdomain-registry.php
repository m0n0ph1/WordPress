<?php

    #[AllowDynamicProperties]
    class WP_Textdomain_Registry
    {
        protected $all = [];

        protected $current = [];

        protected $custom_paths = [];

        protected $cached_mo_files = [];

        protected $domains_with_translations = [];

        public function get($domain, $locale)
        {
            if(isset($this->all[$domain][$locale]))
            {
                return $this->all[$domain][$locale];
            }

            return $this->get_path_from_lang_dir($domain, $locale);
        }

        private function get_path_from_lang_dir($domain, $locale)
        {
            $locations = $this->get_paths_for_domain($domain);

            $found_location = false;

            foreach($locations as $location)
            {
                if(! isset($this->cached_mo_files[$location]))
                {
                    $this->set_cached_mo_files($location);
                }

                $path = "$location/$domain-$locale.mo";

                foreach($this->cached_mo_files[$location] as $mo_path)
                {
                    if(! in_array($domain, $this->domains_with_translations, true) && str_starts_with(str_replace("$location/", '', $mo_path), "$domain-"))
                    {
                        $this->domains_with_translations[] = $domain;
                    }

                    if($mo_path === $path)
                    {
                        $found_location = rtrim($location, '/').'/';
                    }
                }
            }

            if($found_location)
            {
                $this->set($domain, $locale, $found_location);

                return $found_location;
            }

            /*
             * If no path is found for the given locale and a custom path has been set
             * using load_plugin_textdomain/load_theme_textdomain, use that one.
             */
            if('en_US' !== $locale && isset($this->custom_paths[$domain]))
            {
                $fallback_location = rtrim($this->custom_paths[$domain], '/').'/';
                $this->set($domain, $locale, $fallback_location);

                return $fallback_location;
            }

            $this->set($domain, $locale, false);

            return false;
        }

        private function get_paths_for_domain($domain)
        {
            $locations = [
                WP_LANG_DIR.'/plugins',
                WP_LANG_DIR.'/themes',
            ];

            if(isset($this->custom_paths[$domain]))
            {
                $locations[] = $this->custom_paths[$domain];
            }

            return $locations;
        }

        private function set_cached_mo_files($path)
        {
            $this->cached_mo_files[$path] = [];

            $mo_files = glob($path.'/*.mo');

            if($mo_files)
            {
                $this->cached_mo_files[$path] = $mo_files;
            }
        }

        public function set($domain, $locale, $path)
        {
            $this->all[$domain][$locale] = $path ? rtrim($path, '/').'/' : false;
            $this->current[$domain] = $this->all[$domain][$locale];
        }

        public function has($domain)
        {
            return (isset($this->current[$domain]) || empty($this->all[$domain]) || in_array($domain, $this->domains_with_translations, true));
        }

        public function set_custom_path($domain, $path)
        {
            $this->custom_paths[$domain] = rtrim($path, '/');
        }
    }
