<?php

    #[AllowDynamicProperties]
    class WP_HTTP_Proxy
    {
        public function is_enabled()
        {
            return defined('WP_PROXY_HOST') && defined('WP_PROXY_PORT');
        }

        public function use_authentication()
        {
            return defined('WP_PROXY_USERNAME') && defined('WP_PROXY_PASSWORD');
        }

        public function host()
        {
            if(defined('WP_PROXY_HOST'))
            {
                return WP_PROXY_HOST;
            }

            return '';
        }

        public function port()
        {
            if(defined('WP_PROXY_PORT'))
            {
                return WP_PROXY_PORT;
            }

            return '';
        }

        public function authentication_header()
        {
            return 'Proxy-Authorization: Basic '.base64_encode($this->authentication());
        }

        public function authentication()
        {
            return $this->username().':'.$this->password();
        }

        public function username()
        {
            if(defined('WP_PROXY_USERNAME'))
            {
                return WP_PROXY_USERNAME;
            }

            return '';
        }

        public function password()
        {
            if(defined('WP_PROXY_PASSWORD'))
            {
                return WP_PROXY_PASSWORD;
            }

            return '';
        }

        public function send_through_proxy($uri)
        {
            $check = parse_url($uri);

            // Malformed URL, can not process, but this could mean ssl, so let through anyway.
            if(false === $check)
            {
                return true;
            }

            $home = parse_url(get_option('siteurl'));

            $result = apply_filters('pre_http_send_through_proxy', null, $uri, $check, $home);
            if(! is_null($result))
            {
                return $result;
            }

            if('localhost' === $check['host'] || (isset($home['host']) && $home['host'] === $check['host']))
            {
                return false;
            }

            if(! defined('WP_PROXY_BYPASS_HOSTS'))
            {
                return true;
            }

            static $bypass_hosts = null;
            static $wildcard_regex = [];
            if(null === $bypass_hosts)
            {
                $bypass_hosts = preg_split('|,\s*|', WP_PROXY_BYPASS_HOSTS);

                if(str_contains(WP_PROXY_BYPASS_HOSTS, '*'))
                {
                    $wildcard_regex = [];
                    foreach($bypass_hosts as $host)
                    {
                        $wildcard_regex[] = str_replace('\*', '.+', preg_quote($host, '/'));
                    }
                    $wildcard_regex = '/^('.implode('|', $wildcard_regex).')$/i';
                }
            }

            if(! empty($wildcard_regex))
            {
                return ! preg_match($wildcard_regex, $check['host']);
            }
            else
            {
                return ! in_array($check['host'], $bypass_hosts, true);
            }
        }
    }
