<?php

    namespace WpOrg\Requests\Proxy;

    use WpOrg\Requests\Exception\ArgumentCount;
    use WpOrg\Requests\Exception\InvalidArgument;
    use WpOrg\Requests\Hooks;
    use WpOrg\Requests\Proxy;

    final class Http implements Proxy
    {
        public $proxy;

        public $user;

        public $pass;

        public $use_authentication;

        public function __construct($args = null)
        {
            if(is_string($args))
            {
                $this->proxy = $args;
            }
            elseif(is_array($args))
            {
                if(count($args) === 1)
                {
                    [$this->proxy] = $args;
                }
                elseif(count($args) === 3)
                {
                    [$this->proxy, $this->user, $this->pass] = $args;
                    $this->use_authentication = true;
                }
                else
                {
                    throw ArgumentCount::create('an array with exactly one element or exactly three elements', count($args), 'proxyhttpbadargs');
                }
            }
            elseif($args !== null)
            {
                throw InvalidArgument::create(1, '$args', 'array|string|null', gettype($args));
            }
        }

        public function register(Hooks $hooks)
        {
            $hooks->register('curl.before_send', [$this, 'curl_before_send']);

            $hooks->register('fsockopen.remote_socket', [$this, 'fsockopen_remote_socket']);
            $hooks->register('fsockopen.remote_host_path', [$this, 'fsockopen_remote_host_path']);
            if($this->use_authentication)
            {
                $hooks->register('fsockopen.after_headers', [$this, 'fsockopen_header']);
            }
        }

        public function curl_before_send(&$handle)
        {
            curl_setopt($handle, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            curl_setopt($handle, CURLOPT_PROXY, $this->proxy);

            if($this->use_authentication)
            {
                curl_setopt($handle, CURLOPT_PROXYAUTH, CURLAUTH_ANY);
                curl_setopt($handle, CURLOPT_PROXYUSERPWD, $this->get_auth_string());
            }
        }

        public function get_auth_string()
        {
            return $this->user.':'.$this->pass;
        }

        public function fsockopen_remote_socket(&$remote_socket)
        {
            $remote_socket = $this->proxy;
        }

        public function fsockopen_remote_host_path(&$path, $url)
        {
            $path = $url;
        }

        public function fsockopen_header(&$out)
        {
            $out .= sprintf("Proxy-Authorization: Basic %s\r\n", base64_encode($this->get_auth_string()));
        }
    }
