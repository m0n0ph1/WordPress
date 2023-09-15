<?php

    if(! class_exists('WpOrg\Requests\Autoload'))
    {
        require ABSPATH.WPINC.'/Requests/src/Autoload.php';

        WpOrg\Requests\Autoload::register();
        WpOrg\Requests\Requests::set_certificate_path(ABSPATH.WPINC.'/certificates/ca-bundle.crt');
    }

    #[AllowDynamicProperties]
    class WP_Http
    {
        // Aliases for HTTP response codes.
        const HTTP_CONTINUE = 100;

        const SWITCHING_PROTOCOLS = 101;

        const PROCESSING = 102;

        const EARLY_HINTS = 103;

        const OK = 200;

        const CREATED = 201;

        const ACCEPTED = 202;

        const NON_AUTHORITATIVE_INFORMATION = 203;

        const NO_CONTENT = 204;

        const RESET_CONTENT = 205;

        const PARTIAL_CONTENT = 206;

        const MULTI_STATUS = 207;

        const IM_USED = 226;

        const MULTIPLE_CHOICES = 300;

        const MOVED_PERMANENTLY = 301;

        const FOUND = 302;

        const SEE_OTHER = 303;

        const NOT_MODIFIED = 304;

        const USE_PROXY = 305;

        const RESERVED = 306;

        const TEMPORARY_REDIRECT = 307;

        const PERMANENT_REDIRECT = 308;

        const BAD_REQUEST = 400;

        const UNAUTHORIZED = 401;

        const PAYMENT_REQUIRED = 402;

        const FORBIDDEN = 403;

        const NOT_FOUND = 404;

        const METHOD_NOT_ALLOWED = 405;

        const NOT_ACCEPTABLE = 406;

        const PROXY_AUTHENTICATION_REQUIRED = 407;

        const REQUEST_TIMEOUT = 408;

        const CONFLICT = 409;

        const GONE = 410;

        const LENGTH_REQUIRED = 411;

        const PRECONDITION_FAILED = 412;

        const REQUEST_ENTITY_TOO_LARGE = 413;

        const REQUEST_URI_TOO_LONG = 414;

        const UNSUPPORTED_MEDIA_TYPE = 415;

        const REQUESTED_RANGE_NOT_SATISFIABLE = 416;

        const EXPECTATION_FAILED = 417;

        const IM_A_TEAPOT = 418;

        const MISDIRECTED_REQUEST = 421;

        const UNPROCESSABLE_ENTITY = 422;

        const LOCKED = 423;

        const FAILED_DEPENDENCY = 424;

        const UPGRADE_REQUIRED = 426;

        const PRECONDITION_REQUIRED = 428;

        const TOO_MANY_REQUESTS = 429;

        const REQUEST_HEADER_FIELDS_TOO_LARGE = 431;

        const UNAVAILABLE_FOR_LEGAL_REASONS = 451;

        const INTERNAL_SERVER_ERROR = 500;

        const NOT_IMPLEMENTED = 501;

        const BAD_GATEWAY = 502;

        const SERVICE_UNAVAILABLE = 503;

        const GATEWAY_TIMEOUT = 504;

        const HTTP_VERSION_NOT_SUPPORTED = 505;

        const VARIANT_ALSO_NEGOTIATES = 506;

        const INSUFFICIENT_STORAGE = 507;

        const NOT_EXTENDED = 510;

        const NETWORK_AUTHENTICATION_REQUIRED = 511;

        public static function browser_redirect_compatibility($location, $headers, $data, &$options, $original)
        {
            // Browser compatibility.
            if(302 === $original->status_code)
            {
                $options['type'] = WpOrg\Requests\Requests::GET;
            }
        }

        public static function validate_redirects($location)
        {
            if(! wp_http_validate_url($location))
            {
                throw new WpOrg\Requests\Exception(__('A valid URL was not provided.'), 'wp_http.redirect_failed_validation');
            }
        }

        public static function processResponse($response)
        { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
            $response = explode("\r\n\r\n", $response, 2);

            return [
                'headers' => $response[0],
                'body' => isset($response[1]) ? $response[1] : '',
            ];
        }

        public static function buildCookieHeader(&$r)
        { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
            if(! empty($r['cookies']))
            {
                // Upgrade any name => value cookie pairs to WP_HTTP_Cookie instances.
                foreach($r['cookies'] as $name => $value)
                {
                    if(! is_object($value))
                    {
                        $r['cookies'][$name] = new WP_Http_Cookie([
                                                                      'name' => $name,
                                                                      'value' => $value,
                                                                  ]);
                    }
                }

                $cookies_header = '';
                foreach((array) $r['cookies'] as $cookie)
                {
                    $cookies_header .= $cookie->getHeaderValue().'; ';
                }

                $cookies_header = substr($cookies_header, 0, -2);
                $r['headers']['cookie'] = $cookies_header;
            }
        }

        public static function chunkTransferDecode($body)
        { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
            // The body is not chunked encoded or is malformed.
            if(! preg_match('/^([0-9a-f]+)[^\r\n]*\r\n/i', trim($body)))
            {
                return $body;
            }

            $parsed_body = '';

            // We'll be altering $body, so need a backup in case of error.
            $body_original = $body;

            while(true)
            {
                $has_chunk = (bool) preg_match('/^([0-9a-f]+)[^\r\n]*\r\n/i', $body, $match);
                if(! $has_chunk || empty($match[1]))
                {
                    return $body_original;
                }

                $length = hexdec($match[1]);
                $chunk_length = strlen($match[0]);

                // Parse out the chunk of data.
                $parsed_body .= substr($body, $chunk_length, $length);

                // Remove the chunk from the raw data.
                $body = substr($body, $length + $chunk_length);

                // End of the document.
                if('0' === trim($body))
                {
                    return $parsed_body;
                }
            }
        }

        public static function handle_redirects($url, $args, $response)
        {
            // If no redirects are present, or, redirects were not requested, perform no action.
            if(! isset($response['headers']['location']) || 0 === $args['_redirection'])
            {
                return false;
            }

            // Only perform redirections on redirection http codes.
            if($response['response']['code'] > 399 || $response['response']['code'] < 300)
            {
                return false;
            }

            // Don't redirect if we've run out of redirects.
            if($args['redirection']-- <= 0)
            {
                return new WP_Error('http_request_failed', __('Too many redirects.'));
            }

            $redirect_location = $response['headers']['location'];

            // If there were multiple Location headers, use the last header specified.
            if(is_array($redirect_location))
            {
                $redirect_location = array_pop($redirect_location);
            }

            $redirect_location = WP_Http::make_absolute_url($redirect_location, $url);

            // POST requests should not POST to a redirected location.
            if('POST' === $args['method'])
            {
                if(in_array($response['response']['code'], [302, 303], true))
                {
                    $args['method'] = 'GET';
                }
            }

            // Include valid cookies in the redirect process.
            if(! empty($response['cookies']))
            {
                foreach($response['cookies'] as $cookie)
                {
                    if($cookie->test($redirect_location))
                    {
                        $args['cookies'][] = $cookie;
                    }
                }
            }

            return wp_remote_request($redirect_location, $args);
        }

        public static function make_absolute_url($maybe_relative_path, $url)
        {
            if(empty($url))
            {
                return $maybe_relative_path;
            }

            $url_parts = wp_parse_url($url);
            if(! $url_parts)
            {
                return $maybe_relative_path;
            }

            $relative_url_parts = wp_parse_url($maybe_relative_path);
            if(! $relative_url_parts)
            {
                return $maybe_relative_path;
            }

            // Check for a scheme on the 'relative' URL.
            if(! empty($relative_url_parts['scheme']))
            {
                return $maybe_relative_path;
            }

            $absolute_path = $url_parts['scheme'].'://';

            // Schemeless URLs will make it this far, so we check for a host in the relative URL
            // and convert it to a protocol-URL.
            if(isset($relative_url_parts['host']))
            {
                $absolute_path .= $relative_url_parts['host'];
                if(isset($relative_url_parts['port']))
                {
                    $absolute_path .= ':'.$relative_url_parts['port'];
                }
            }
            else
            {
                $absolute_path .= $url_parts['host'];
                if(isset($url_parts['port']))
                {
                    $absolute_path .= ':'.$url_parts['port'];
                }
            }

            // Start off with the absolute URL path.
            $path = ! empty($url_parts['path']) ? $url_parts['path'] : '/';

            // If it's a root-relative path, then great.
            if(! empty($relative_url_parts['path']) && '/' === $relative_url_parts['path'][0])
            {
                $path = $relative_url_parts['path'];
                // Else it's a relative path.
            }
            elseif(! empty($relative_url_parts['path']))
            {
                // Strip off any file components from the absolute path.
                $path = substr($path, 0, strrpos($path, '/') + 1);

                // Build the new path.
                $path .= $relative_url_parts['path'];

                // Strip all /path/../ out of the path.
                while(strpos($path, '../') > 1)
                {
                    $path = preg_replace('![^/]+/\.\./!', '', $path);
                }

                // Strip any final leading ../ from the path.
                $path = preg_replace('!^/(\.\./)+!', '', $path);
            }

            // Add the query string.
            if(! empty($relative_url_parts['query']))
            {
                $path .= '?'.$relative_url_parts['query'];
            }

            // Add the fragment.
            if(! empty($relative_url_parts['fragment']))
            {
                $path .= '#'.$relative_url_parts['fragment'];
            }

            return $absolute_path.'/'.ltrim($path, '/');
        }

        public static function is_ip_address($maybe_ip)
        {
            if(preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $maybe_ip))
            {
                return 4;
            }

            if(str_contains($maybe_ip, ':') && preg_match('/^(((?=.*(::))(?!.*\3.+\3))\3?|([\dA-F]{1,4}(\3|:\b|$)|\2))(?4){5}((?4){2}|(((2[0-4]|1\d|[1-9])?\d|25[0-5])\.?\b){4})$/i', trim($maybe_ip, ' []')))
            {
                return 6;
            }

            return false;
        }

        protected static function parse_url($url)
        {
            _deprecated_function(__METHOD__, '4.4.0', 'wp_parse_url()');

            return wp_parse_url($url);
        }

        public function post($url, $args = [])
        {
            $defaults = ['method' => 'POST'];
            $parsed_args = wp_parse_args($args, $defaults);

            return $this->request($url, $parsed_args);
        }

        public function request($url, $args = [])
        {
            $defaults = [
                'method' => 'GET',

                'timeout' => apply_filters('http_request_timeout', 5, $url),

                'redirection' => apply_filters('http_request_redirection_count', 5, $url),

                'httpversion' => apply_filters('http_request_version', '1.0', $url),

                'user-agent' => apply_filters('http_headers_useragent', 'WordPress/'.get_bloginfo('version').'; '.get_bloginfo('url'), $url),

                'reject_unsafe_urls' => apply_filters('http_request_reject_unsafe_urls', false, $url),
                'blocking' => true,
                'headers' => [],
                'cookies' => [],
                'body' => null,
                'compress' => false,
                'decompress' => true,
                'sslverify' => true,
                'sslcertificates' => ABSPATH.WPINC.'/certificates/ca-bundle.crt',
                'stream' => false,
                'filename' => null,
                'limit_response_size' => null,
            ];

            // Pre-parse for the HEAD checks.
            $args = wp_parse_args($args);

            // By default, HEAD requests do not cause redirections.
            if(isset($args['method']) && 'HEAD' === $args['method'])
            {
                $defaults['redirection'] = 0;
            }

            $parsed_args = wp_parse_args($args, $defaults);

            $parsed_args = apply_filters('http_request_args', $parsed_args, $url);

            // The transports decrement this, store a copy of the original value for loop purposes.
            if(! isset($parsed_args['_redirection']))
            {
                $parsed_args['_redirection'] = $parsed_args['redirection'];
            }

            $pre = apply_filters('pre_http_request', false, $parsed_args, $url);

            if(false !== $pre)
            {
                return $pre;
            }

            if(function_exists('wp_kses_bad_protocol'))
            {
                if($parsed_args['reject_unsafe_urls'])
                {
                    $url = wp_http_validate_url($url);
                }
                if($url)
                {
                    $url = wp_kses_bad_protocol($url, ['http', 'https', 'ssl']);
                }
            }

            $parsed_url = parse_url($url);

            if(empty($url) || empty($parsed_url['scheme']))
            {
                $response = new WP_Error('http_request_failed', __('A valid URL was not provided.'));

                do_action('http_api_debug', $response, 'response', 'WpOrg\Requests\Requests', $parsed_args, $url);

                return $response;
            }

            if($this->block_request($url))
            {
                $response = new WP_Error('http_request_not_executed', __('User has blocked requests through HTTP.'));

                do_action('http_api_debug', $response, 'response', 'WpOrg\Requests\Requests', $parsed_args, $url);

                return $response;
            }

            // If we are streaming to a file but no filename was given drop it in the WP temp dir
            // and pick its name using the basename of the $url.
            if($parsed_args['stream'])
            {
                if(empty($parsed_args['filename']))
                {
                    $parsed_args['filename'] = get_temp_dir().basename($url);
                }

                // Force some settings if we are streaming to a file and check for existence
                // and perms of destination directory.
                $parsed_args['blocking'] = true;
                if(! wp_is_writable(dirname($parsed_args['filename'])))
                {
                    $response = new WP_Error('http_request_failed', __('Destination directory for file streaming does not exist or is not writable.'));

                    do_action('http_api_debug', $response, 'response', 'WpOrg\Requests\Requests', $parsed_args, $url);

                    return $response;
                }
            }

            if(is_null($parsed_args['headers']))
            {
                $parsed_args['headers'] = [];
            }

            // WP allows passing in headers as a string, weirdly.
            if(! is_array($parsed_args['headers']))
            {
                $processed_headers = WP_Http::processHeaders($parsed_args['headers']);
                $parsed_args['headers'] = $processed_headers['headers'];
            }

            // Setup arguments.
            $headers = $parsed_args['headers'];
            $data = $parsed_args['body'];
            $type = $parsed_args['method'];
            $options = [
                'timeout' => $parsed_args['timeout'],
                'useragent' => $parsed_args['user-agent'],
                'blocking' => $parsed_args['blocking'],
                'hooks' => new WP_HTTP_Requests_Hooks($url, $parsed_args),
            ];

            // Ensure redirects follow browser behavior.
            $options['hooks']->register('requests.before_redirect', [static::class, 'browser_redirect_compatibility']);

            // Validate redirected URLs.
            if(function_exists('wp_kses_bad_protocol') && $parsed_args['reject_unsafe_urls'])
            {
                $options['hooks']->register('requests.before_redirect', [static::class, 'validate_redirects']);
            }

            if($parsed_args['stream'])
            {
                $options['filename'] = $parsed_args['filename'];
            }
            if(empty($parsed_args['redirection']))
            {
                $options['follow_redirects'] = false;
            }
            else
            {
                $options['redirects'] = $parsed_args['redirection'];
            }

            // Use byte limit, if we can.
            if(isset($parsed_args['limit_response_size']))
            {
                $options['max_bytes'] = $parsed_args['limit_response_size'];
            }

            // If we've got cookies, use and convert them to WpOrg\Requests\Cookie.
            if(! empty($parsed_args['cookies']))
            {
                $options['cookies'] = WP_Http::normalize_cookies($parsed_args['cookies']);
            }

            // SSL certificate handling.
            if(! $parsed_args['sslverify'])
            {
                $options['verify'] = false;
                $options['verifyname'] = false;
            }
            else
            {
                $options['verify'] = $parsed_args['sslcertificates'];
            }

            // All non-GET/HEAD requests should put the arguments in the form body.
            if('HEAD' !== $type && 'GET' !== $type)
            {
                $options['data_format'] = 'body';
            }

            $options['verify'] = apply_filters('https_ssl_verify', $options['verify'], $url);

            // Check for proxies.
            $proxy = new WP_HTTP_Proxy();
            if($proxy->is_enabled() && $proxy->send_through_proxy($url))
            {
                $options['proxy'] = new WpOrg\Requests\Proxy\Http($proxy->host().':'.$proxy->port());

                if($proxy->use_authentication())
                {
                    $options['proxy']->use_authentication = true;
                    $options['proxy']->user = $proxy->username();
                    $options['proxy']->pass = $proxy->password();
                }
            }

            // Avoid issues where mbstring.func_overload is enabled.
            mbstring_binary_safe_encoding();

            try
            {
                $requests_response = WpOrg\Requests\Requests::request($url, $headers, $data, $type, $options);

                // Convert the response into an array.
                $http_response = new WP_HTTP_Requests_Response($requests_response, $parsed_args['filename']);
                $response = $http_response->to_array();

                // Add the original object to the array.
                $response['http_response'] = $http_response;
            }
            catch(WpOrg\Requests\Exception $e)
            {
                $response = new WP_Error('http_request_failed', $e->getMessage());
            }

            reset_mbstring_encoding();

            do_action('http_api_debug', $response, 'response', 'WpOrg\Requests\Requests', $parsed_args, $url);
            if(is_wp_error($response))
            {
                return $response;
            }

            if(! $parsed_args['blocking'])
            {
                return [
                    'headers' => [],
                    'body' => '',
                    'response' => [
                        'code' => false,
                        'message' => false,
                    ],
                    'cookies' => [],
                    'http_response' => null,
                ];
            }

            return apply_filters('http_response', $response, $parsed_args, $url);
        }

        public function block_request($uri)
        {
            // We don't need to block requests, because nothing is blocked.
            if(! defined('WP_HTTP_BLOCK_EXTERNAL') || ! WP_HTTP_BLOCK_EXTERNAL)
            {
                return false;
            }

            $check = parse_url($uri);
            if(! $check)
            {
                return true;
            }

            $home = parse_url(get_option('siteurl'));

            // Don't block requests back to ourselves by default.
            if('localhost' === $check['host'] || (isset($home['host']) && $home['host'] === $check['host']))
            {
                return apply_filters('block_local_requests', false);
            }

            if(! defined('WP_ACCESSIBLE_HOSTS'))
            {
                return true;
            }

            static $accessible_hosts = null;
            static $wildcard_regex = [];
            if(null === $accessible_hosts)
            {
                $accessible_hosts = preg_split('|,\s*|', WP_ACCESSIBLE_HOSTS);

                if(str_contains(WP_ACCESSIBLE_HOSTS, '*'))
                {
                    $wildcard_regex = [];
                    foreach($accessible_hosts as $host)
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
                return ! in_array($check['host'], $accessible_hosts, true); // Inverse logic, if it's in the array, then don't block it.
            }
        }

        public static function processHeaders($headers, $url = '')
        { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
            // Split headers, one per array element.
            if(is_string($headers))
            {
                // Tolerate line terminator: CRLF = LF (RFC 2616 19.3).
                $headers = str_replace("\r\n", "\n", $headers);
                /*
                 * Unfold folded header fields. LWS = [CRLF] 1*( SP | HT ) <US-ASCII SP, space (32)>,
                 * <US-ASCII HT, horizontal-tab (9)> (RFC 2616 2.2).
                 */
                $headers = preg_replace('/\n[ \t]/', ' ', $headers);
                // Create the headers array.
                $headers = explode("\n", $headers);
            }

            $response = [
                'code' => 0,
                'message' => '',
            ];

            /*
             * If a redirection has taken place, The headers for each page request may have been passed.
             * In this case, determine the final HTTP header and parse from there.
             */
            for($i = count($headers) - 1; $i >= 0; $i--)
            {
                if(! empty($headers[$i]) && ! str_contains($headers[$i], ':'))
                {
                    $headers = array_splice($headers, $i);
                    break;
                }
            }

            $cookies = [];
            $newheaders = [];
            foreach((array) $headers as $tempheader)
            {
                if(empty($tempheader))
                {
                    continue;
                }

                if(! str_contains($tempheader, ':'))
                {
                    $stack = explode(' ', $tempheader, 3);
                    $stack[] = '';
                    [, $response['code'], $response['message']] = $stack;
                    continue;
                }

                [$key, $value] = explode(':', $tempheader, 2);

                $key = strtolower($key);
                $value = trim($value);

                if(isset($newheaders[$key]))
                {
                    if(! is_array($newheaders[$key]))
                    {
                        $newheaders[$key] = [$newheaders[$key]];
                    }
                    $newheaders[$key][] = $value;
                }
                else
                {
                    $newheaders[$key] = $value;
                }
                if('set-cookie' === $key)
                {
                    $cookies[] = new WP_Http_Cookie($value, $url);
                }
            }

            // Cast the Response Code to an int.
            $response['code'] = (int) $response['code'];

            return [
                'response' => $response,
                'headers' => $newheaders,
                'cookies' => $cookies,
            ];
        }

        public static function normalize_cookies($cookies)
        {
            $cookie_jar = new WpOrg\Requests\Cookie\Jar();

            foreach($cookies as $name => $value)
            {
                if($value instanceof WP_Http_Cookie)
                {
                    $attributes = array_filter($value->get_attributes(), static function($attr)
                    {
                        return null !== $attr;
                    });
                    $cookie_jar[$value->name] = new WpOrg\Requests\Cookie($value->name, $value->value, $attributes, ['host-only' => $value->host_only]);
                }
                elseif(is_scalar($value))
                {
                    $cookie_jar[$name] = new WpOrg\Requests\Cookie($name, (string) $value);
                }
            }

            return $cookie_jar;
        }

        public function get($url, $args = [])
        {
            $defaults = ['method' => 'GET'];
            $parsed_args = wp_parse_args($args, $defaults);

            return $this->request($url, $parsed_args);
        }

        public function head($url, $args = [])
        {
            $defaults = ['method' => 'HEAD'];
            $parsed_args = wp_parse_args($args, $defaults);

            return $this->request($url, $parsed_args);
        }

        private function _dispatch_request($url, $args)
        {
            static $transports = [];

            $class = $this->_get_first_available_transport($args, $url);
            if(! $class)
            {
                return new WP_Error('http_failure', __('There are no HTTP transports available which can complete the requested request.'));
            }

            // Transport claims to support request, instantiate it and give it a whirl.
            if(empty($transports[$class]))
            {
                $transports[$class] = new $class();
            }

            $response = $transports[$class]->request($url, $args);

            do_action('http_api_debug', $response, 'response', $class, $args, $url);

            if(is_wp_error($response))
            {
                return $response;
            }

            return apply_filters('http_response', $response, $args, $url);
        }

        public function _get_first_available_transport($args, $url = null)
        {
            $transports = ['curl', 'streams'];

            $request_order = apply_filters('http_api_transports', $transports, $args, $url);

            // Loop over each transport on each HTTP request looking for one which will serve this request's needs.
            foreach($request_order as $transport)
            {
                if(in_array($transport, $transports, true))
                {
                    $transport = ucfirst($transport);
                }
                $class = 'WP_Http_'.$transport;

                // Check to see if this transport is a possibility, calls the transport statically.
                if(! call_user_func([$class, 'test'], $args, $url))
                {
                    continue;
                }

                return $class;
            }

            return false;
        }
    }
