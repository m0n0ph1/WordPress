<?php
    /**
     * HTTP Proxy connection interface
     *
     * @package Requests\Proxy
     * @since   1.6
     */

    namespace WpOrg\Requests\Proxy;

    use WpOrg\Requests\Exception\ArgumentCount;
    use WpOrg\Requests\Exception\InvalidArgument;
    use WpOrg\Requests\Hooks;
    use WpOrg\Requests\Proxy;

    /**
     * HTTP Proxy connection interface
     *
     * Provides a handler for connection via an HTTP proxy
     *
     * @package Requests\Proxy
     * @since   1.6
     */
    final class Http implements Proxy
    {
        /**
         * Proxy host and port
         *
         * Notation: "host:port" (eg 127.0.0.1:8080 or someproxy.com:3128)
         *
         * @var string
         */
        public $proxy;

        /**
         * Username
         *
         * @var string
         */
        public $user;

        /**
         * Password
         *
         * @var string
         */
        public $pass;

        /**
         * Do we need to authenticate? (ie username & password have been provided)
         *
         * @var boolean
         */
        public $use_authentication;

        /**
         * Constructor
         *
         * @param array|string|null $args Proxy as a string or an array of proxy, user and password.
         *                                When passed as an array, must have exactly one (proxy)
         *                                or three elements (proxy, user, password).
         *
         * @throws \WpOrg\Requests\Exception\InvalidArgument When the passed argument is not an array, a string or null.
         * @throws \WpOrg\Requests\Exception\ArgumentCount On incorrect number of arguments (`proxyhttpbadargs`)
         * @since 1.6
         *
         */
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

        /**
         * Register the necessary callbacks
         *
         * @param \WpOrg\Requests\Hooks $hooks Hook system
         *
         * @see   \WpOrg\Requests\Proxy\Http::curl_before_send()
         * @see   \WpOrg\Requests\Proxy\Http::fsockopen_remote_socket()
         * @see   \WpOrg\Requests\Proxy\Http::fsockopen_remote_host_path()
         * @see   \WpOrg\Requests\Proxy\Http::fsockopen_header()
         * @since 1.6
         */
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

        /**
         * Set cURL parameters before the data is sent
         *
         * @param resource|\CurlHandle $handle cURL handle
         *
         * @since 1.6
         */
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

        /**
         * Get the authentication string (user:pass)
         *
         * @return string
         * @since 1.6
         */
        public function get_auth_string()
        {
            return $this->user.':'.$this->pass;
        }

        /**
         * Alter remote socket information before opening socket connection
         *
         * @param string $remote_socket Socket connection string
         *
         * @since 1.6
         */
        public function fsockopen_remote_socket(&$remote_socket)
        {
            $remote_socket = $this->proxy;
        }

        /**
         * Alter remote path before getting stream data
         *
         * @param string $path Path to send in HTTP request string ("GET ...")
         * @param string $url  Full URL we're requesting
         *
         * @since 1.6
         */
        public function fsockopen_remote_host_path(&$path, $url)
        {
            $path = $url;
        }

        /**
         * Add extra headers to the request before sending
         *
         * @param string $out HTTP header string
         *
         * @since 1.6
         */
        public function fsockopen_header(&$out)
        {
            $out .= sprintf("Proxy-Authorization: Basic %s\r\n", base64_encode($this->get_auth_string()));
        }
    }
