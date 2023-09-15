<?php

    #[AllowDynamicProperties]
    class WP_Network
    {
        public $domain = '';

        public $path = '';

        public $cookie_domain = '';

        public $site_name = '';

        private $id;

        private $blog_id = '0';

        public function __construct($network)
        {
            foreach(get_object_vars($network) as $key => $value)
            {
                $this->$key = $value;
            }

            $this->_set_site_name();
            $this->_set_cookie_domain();
        }

        private function _set_site_name()
        {
            if(! empty($this->site_name))
            {
                return;
            }

            $default = ucfirst($this->domain);
            $this->site_name = get_network_option($this->id, 'site_name', $default);
        }

        private function _set_cookie_domain()
        {
            if(! empty($this->cookie_domain))
            {
                return;
            }

            $this->cookie_domain = $this->domain;
            if(str_starts_with($this->cookie_domain, 'www.'))
            {
                $this->cookie_domain = substr($this->cookie_domain, 4);
            }
        }

        public static function get_instance($network_id)
        {
            global $wpdb;

            $network_id = (int) $network_id;
            if(! $network_id)
            {
                return false;
            }

            $_network = wp_cache_get($network_id, 'networks');

            if(false === $_network)
            {
                $_network = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->site} WHERE id = %d LIMIT 1", $network_id));

                if(empty($_network) || is_wp_error($_network))
                {
                    $_network = -1;
                }

                wp_cache_add($network_id, $_network, 'networks');
            }

            if(is_numeric($_network))
            {
                return false;
            }

            return new WP_Network($_network);
        }

        public static function get_by_path($domain = '', $path = '', $segments = null)
        {
            $domains = [$domain];
            $pieces = explode('.', $domain);

            /*
             * It's possible one domain to search is 'com', but it might as well
             * be 'localhost' or some other locally mapped domain.
             */
            while(array_shift($pieces))
            {
                if(! empty($pieces))
                {
                    $domains[] = implode('.', $pieces);
                }
            }

            /*
             * If we've gotten to this function during normal execution, there is
             * more than one network installed. At this point, who knows how many
             * we have. Attempt to optimize for the situation where networks are
             * only domains, thus meaning paths never need to be considered.
             *
             * This is a very basic optimization; anything further could have
             * drawbacks depending on the setup, so this is best done per-installation.
             */
            $using_paths = true;
            if(wp_using_ext_object_cache())
            {
                $using_paths = get_networks([
                                                'number' => 1,
                                                'count' => true,
                                                'path__not_in' => '/',
                                            ]);
            }

            $paths = [];
            if($using_paths)
            {
                $path_segments = array_filter(explode('/', trim($path, '/')));

                $segments = apply_filters('network_by_path_segments_count', $segments, $domain, $path);

                if((null !== $segments) && count($path_segments) > $segments)
                {
                    $path_segments = array_slice($path_segments, 0, $segments);
                }

                while(count($path_segments))
                {
                    $paths[] = '/'.implode('/', $path_segments).'/';
                    array_pop($path_segments);
                }

                $paths[] = '/';
            }

            $pre = apply_filters('pre_get_network_by_path', null, $domain, $path, $segments, $paths);
            if(null !== $pre)
            {
                return $pre;
            }

            if(! $using_paths)
            {
                $networks = get_networks([
                                             'number' => 1,
                                             'orderby' => [
                                                 'domain_length' => 'DESC',
                                             ],
                                             'domain__in' => $domains,
                                         ]);

                if(! empty($networks))
                {
                    return array_shift($networks);
                }

                return false;
            }

            $networks = get_networks([
                                         'orderby' => [
                                             'domain_length' => 'DESC',
                                             'path_length' => 'DESC',
                                         ],
                                         'domain__in' => $domains,
                                         'path__in' => $paths,
                                     ]);

            /*
             * Domains are sorted by length of domain, then by length of path.
             * The domain must match for the path to be considered. Otherwise,
             * a network with the path of / will suffice.
             */
            $found = false;
            foreach($networks as $network)
            {
                if(($network->domain === $domain) || ("www.{$network->domain}" === $domain))
                {
                    if(in_array($network->path, $paths, true))
                    {
                        $found = true;
                        break;
                    }
                }
                if('/' === $network->path)
                {
                    $found = true;
                    break;
                }
            }

            if(true === $found)
            {
                return $network;
            }

            return false;
        }

        public function __get($key)
        {
            switch($key)
            {
                case 'id':
                    return (int) $this->id;
                case 'blog_id':
                    return (string) $this->get_main_site_id();
                case 'site_id':
                    return $this->get_main_site_id();
            }

            return null;
        }

        public function __set($key, $value)
        {
            switch($key)
            {
                case 'id':
                    $this->id = (int) $value;
                    break;
                case 'blog_id':
                case 'site_id':
                    $this->blog_id = (string) $value;
                    break;
                default:
                    $this->$key = $value;
            }
        }

        private function get_main_site_id()
        {
            $main_site_id = (int) apply_filters('pre_get_main_site_id', null, $this);

            if(0 < $main_site_id)
            {
                return $main_site_id;
            }

            if(0 < (int) $this->blog_id)
            {
                return (int) $this->blog_id;
            }

            if((defined('DOMAIN_CURRENT_SITE') && defined('PATH_CURRENT_SITE') && DOMAIN_CURRENT_SITE === $this->domain && PATH_CURRENT_SITE === $this->path) || (defined('SITE_ID_CURRENT_SITE') && (int) SITE_ID_CURRENT_SITE === $this->id))
            {
                if(defined('BLOG_ID_CURRENT_SITE'))
                {
                    $this->blog_id = (string) BLOG_ID_CURRENT_SITE;

                    return (int) $this->blog_id;
                }

                if(defined('BLOGID_CURRENT_SITE'))
                { // Deprecated.
                    $this->blog_id = (string) BLOGID_CURRENT_SITE;

                    return (int) $this->blog_id;
                }
            }

            $site = get_site();
            if($site->domain === $this->domain && $site->path === $this->path)
            {
                $main_site_id = (int) $site->id;
            }
            else
            {
                $main_site_id = get_network_option($this->id, 'main_site');
                if(false === $main_site_id)
                {
                    $_sites = get_sites([
                                            'fields' => 'ids',
                                            'number' => 1,
                                            'domain' => $this->domain,
                                            'path' => $this->path,
                                            'network_id' => $this->id,
                                        ]);
                    $main_site_id = ! empty($_sites) ? array_shift($_sites) : 0;

                    update_network_option($this->id, 'main_site', $main_site_id);
                }
            }

            $this->blog_id = (string) $main_site_id;

            return (int) $this->blog_id;
        }

        public function __isset($key)
        {
            switch($key)
            {
                case 'id':
                case 'blog_id':
                case 'site_id':
                    return true;
            }

            return false;
        }
    }
