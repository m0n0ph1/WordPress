<?php

    #[AllowDynamicProperties]
    class WP_REST_Server
    {
        const READABLE = 'GET';

        const CREATABLE = 'POST';

        const EDITABLE = 'POST, PUT, PATCH';

        const DELETABLE = 'DELETE';

        const ALLMETHODS = 'GET, POST, PUT, PATCH, DELETE';

        protected $namespaces = [];

        protected $endpoints = [];

        protected $route_options = [];

        protected $embed_cache = [];

        public function __construct()
        {
            $this->endpoints = [
                // Meta endpoints.
                '/' => [
                    'callback' => [$this, 'get_index'],
                    'methods' => 'GET',
                    'args' => [
                        'context' => [
                            'default' => 'view',
                        ],
                    ],
                ],
                '/batch/v1' => [
                    'callback' => [$this, 'serve_batch_request_v1'],
                    'methods' => 'POST',
                    'args' => [
                        'validation' => [
                            'type' => 'string',
                            'enum' => ['require-all-validate', 'normal'],
                            'default' => 'normal',
                        ],
                        'requests' => [
                            'required' => true,
                            'type' => 'array',
                            'maxItems' => $this->get_max_batch_size(),
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'method' => [
                                        'type' => 'string',
                                        'enum' => ['POST', 'PUT', 'PATCH', 'DELETE'],
                                        'default' => 'POST',
                                    ],
                                    'path' => [
                                        'type' => 'string',
                                        'required' => true,
                                    ],
                                    'body' => [
                                        'type' => 'object',
                                        'properties' => [],
                                        'additionalProperties' => true,
                                    ],
                                    'headers' => [
                                        'type' => 'object',
                                        'properties' => [],
                                        'additionalProperties' => [
                                            'type' => ['string', 'array'],
                                            'items' => [
                                                'type' => 'string',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        protected function get_max_batch_size()
        {
            return apply_filters('rest_get_max_batch_size', 25);
        }

        public function serve_request($path = null)
        {
            /* @var WP_User|null $current_user */ global $current_user;

            if($current_user instanceof WP_User && ! $current_user->exists())
            {
                /*
                 * If there is no current user authenticated via other means, clear
                 * the cached lack of user, so that an authenticate check can set it
                 * properly.
                 *
                 * This is done because for authentications such as Application
                 * Passwords, we don't want it to be accepted unless the current HTTP
                 * request is a REST API request, which can't always be identified early
                 * enough in evaluation.
                 */
                $current_user = null;
            }

            $jsonp_enabled = apply_filters('rest_jsonp_enabled', true);

            $jsonp_callback = false;
            if(isset($_GET['_jsonp']))
            {
                $jsonp_callback = $_GET['_jsonp'];
            }

            $content_type = ($jsonp_callback && $jsonp_enabled) ? 'application/javascript' : 'application/json';
            $this->send_header('Content-Type', $content_type.'; charset='.get_option('blog_charset'));
            $this->send_header('X-Robots-Tag', 'noindex');

            $api_root = get_rest_url();
            if(! empty($api_root))
            {
                $this->send_header('Link', '<'.sanitize_url($api_root).'>; rel="https://api.w.org/"');
            }

            /*
             * Mitigate possible JSONP Flash attacks.
             *
             * https://miki.it/blog/2014/7/8/abusing-jsonp-with-rosetta-flash/
             */
            $this->send_header('X-Content-Type-Options', 'nosniff');

            $send_no_cache_headers = apply_filters('rest_send_nocache_headers', is_user_logged_in());
            if($send_no_cache_headers)
            {
                foreach(wp_get_nocache_headers() as $header => $header_value)
                {
                    if(empty($header_value))
                    {
                        $this->remove_header($header);
                    }
                    else
                    {
                        $this->send_header($header, $header_value);
                    }
                }
            }

            apply_filters_deprecated('rest_enabled', [true], '4.7.0', 'rest_authentication_errors', sprintf(/* translators: %s: rest_authentication_errors */ __('The REST API can no longer be completely disabled, the %s filter can be used to restrict access to the API, instead.'), 'rest_authentication_errors'));

            if($jsonp_callback)
            {
                if(! $jsonp_enabled)
                {
                    echo $this->json_error('rest_callback_disabled', __('JSONP support is disabled on this site.'), 400);

                    return false;
                }

                if(! wp_check_jsonp_callback($jsonp_callback))
                {
                    echo $this->json_error('rest_callback_invalid', __('Invalid JSONP callback function.'), 400);

                    return false;
                }
            }

            if(empty($path))
            {
                if(isset($_SERVER['PATH_INFO']))
                {
                    $path = $_SERVER['PATH_INFO'];
                }
                else
                {
                    $path = '/';
                }
            }

            $request = new WP_REST_Request($_SERVER['REQUEST_METHOD'], $path);

            $request->set_query_params(wp_unslash($_GET));
            $request->set_body_params(wp_unslash($_POST));
            $request->set_file_params($_FILES);
            $request->set_headers($this->get_headers(wp_unslash($_SERVER)));
            $request->set_body(self::get_raw_data());

            /*
             * HTTP method override for clients that can't use PUT/PATCH/DELETE. First, we check
             * $_GET['_method']. If that is not set, we check for the HTTP_X_HTTP_METHOD_OVERRIDE
             * header.
             */
            if(isset($_GET['_method']))
            {
                $request->set_method($_GET['_method']);
            }
            elseif(isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']))
            {
                $request->set_method($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
            }

            $expose_headers = ['X-WP-Total', 'X-WP-TotalPages', 'Link'];

            $expose_headers = apply_filters('rest_exposed_cors_headers', $expose_headers, $request);

            $this->send_header('Access-Control-Expose-Headers', implode(', ', $expose_headers));

            $allow_headers = [
                'Authorization',
                'X-WP-Nonce',
                'Content-Disposition',
                'Content-MD5',
                'Content-Type',
            ];

            $allow_headers = apply_filters('rest_allowed_cors_headers', $allow_headers, $request);

            $this->send_header('Access-Control-Allow-Headers', implode(', ', $allow_headers));

            $result = $this->check_authentication();

            if(! is_wp_error($result))
            {
                $result = $this->dispatch($request);
            }

            // Normalize to either WP_Error or WP_REST_Response...
            $result = rest_ensure_response($result);

            // ...then convert WP_Error across.
            if(is_wp_error($result))
            {
                $result = $this->error_to_response($result);
            }

            $result = apply_filters('rest_post_dispatch', rest_ensure_response($result), $this, $request);

            // Wrap the response in an envelope if asked for.
            if(isset($_GET['_envelope']))
            {
                $embed = isset($_GET['_embed']) ? rest_parse_embed_param($_GET['_embed']) : false;
                $result = $this->envelope_response($result, $embed);
            }

            // Send extra data from response objects.
            $headers = $result->get_headers();
            $this->send_headers($headers);

            $code = $result->get_status();
            $this->set_status($code);

            $served = apply_filters('rest_pre_serve_request', false, $result, $request, $this);

            if(! $served)
            {
                if('HEAD' === $request->get_method())
                {
                    return null;
                }

                // Embed links inside the request.
                $embed = isset($_GET['_embed']) ? rest_parse_embed_param($_GET['_embed']) : false;
                $result = $this->response_to_data($result, $embed);

                $result = apply_filters('rest_pre_echo_response', $result, $this, $request);

                // The 204 response shouldn't have a body.
                if(204 === $code || null === $result)
                {
                    return null;
                }

                $result = wp_json_encode($result, $this->get_json_encode_options($request));

                $json_error_message = $this->get_json_last_error();

                if($json_error_message)
                {
                    $this->set_status(500);
                    $json_error_obj = new WP_Error('rest_encode_error', $json_error_message, ['status' => 500]);

                    $result = $this->error_to_response($json_error_obj);
                    $result = wp_json_encode($result->data, $this->get_json_encode_options($request));
                }

                if($jsonp_callback)
                {
                    // Prepend ''.$jsonp_callback.'('.$result.')';
                }
                else
                {
                    echo $result;
                }
            }

            return null;
        }

        public function send_header($key, $value)
        {
            /*
             * Sanitize as per RFC2616 (Section 4.2):
             *
             * Any LWS that occurs between field-content MAY be replaced with a
             * single SP before interpreting the field value or forwarding the
             * message downstream.
             */
            $value = preg_replace('/\s+/', ' ', $value);
            header(sprintf('%s: %s', $key, $value));
        }

        public function remove_header($key)
        {
            header_remove($key);
        }

        protected function json_error($code, $message, $status = null)
        {
            if($status)
            {
                $this->set_status($status);
            }

            $error = compact('code', 'message');

            return wp_json_encode($error);
        }

        protected function set_status($code)
        {
            status_header($code);
        }

        public function get_headers($server)
        {
            $headers = [];

            // CONTENT_* headers are not prefixed with HTTP_.
            $additional = [
                'CONTENT_LENGTH' => true,
                'CONTENT_MD5' => true,
                'CONTENT_TYPE' => true,
            ];

            foreach($server as $key => $value)
            {
                if(str_starts_with($key, 'HTTP_'))
                {
                    $headers[substr($key, 5)] = $value;
                }
                elseif('REDIRECT_HTTP_AUTHORIZATION' === $key && empty($server['HTTP_AUTHORIZATION']))
                {
                    /*
                     * In some server configurations, the authorization header is passed in this alternate location.
                     * Since it would not be passed in in both places we do not check for both headers and resolve.
                     */
                    $headers['AUTHORIZATION'] = $value;
                }
                elseif(isset($additional[$key]))
                {
                    $headers[$key] = $value;
                }
            }

            return $headers;
        }

        public static function get_raw_data()
        {
            // phpcs:disable PHPCompatibility.Variables.RemovedPredefinedGlobalVariables.http_raw_post_dataDeprecatedRemoved
            global $HTTP_RAW_POST_DATA;

            // $HTTP_RAW_POST_DATA was deprecated in PHP 5.6 and removed in PHP 7.0.
            if(! isset($HTTP_RAW_POST_DATA))
            {
                $HTTP_RAW_POST_DATA = file_get_contents('php://input');
            }

            return $HTTP_RAW_POST_DATA;
            // phpcs:enable
        }

        public function check_authentication()
        {
            return apply_filters('rest_authentication_errors', null);
        }

        public function dispatch($request)
        {
            $result = apply_filters('rest_pre_dispatch', null, $this, $request);

            if(! empty($result))
            {
                // Normalize to either WP_Error or WP_REST_Response...
                $result = rest_ensure_response($result);

                // ...then convert WP_Error across.
                if(is_wp_error($result))
                {
                    $result = $this->error_to_response($result);
                }

                return $result;
            }

            $error = null;
            $matched = $this->match_request_to_handler($request);

            if(is_wp_error($matched))
            {
                return $this->error_to_response($matched);
            }

            [$route, $handler] = $matched;

            if(! is_callable($handler['callback']))
            {
                $error = new WP_Error('rest_invalid_handler', __('The handler for the route is invalid.'), ['status' => 500]);
            }

            if(! is_wp_error($error))
            {
                $check_required = $request->has_valid_params();
                if(is_wp_error($check_required))
                {
                    $error = $check_required;
                }
                else
                {
                    $check_sanitized = $request->sanitize_params();
                    if(is_wp_error($check_sanitized))
                    {
                        $error = $check_sanitized;
                    }
                }
            }

            return $this->respond_to_request($request, $route, $handler, $error);
        }

        protected function error_to_response($error)
        {
            return rest_convert_error_to_response($error);
        }

        protected function match_request_to_handler($request)
        {
            $method = $request->get_method();
            $path = $request->get_route();

            $with_namespace = [];

            foreach($this->get_namespaces() as $namespace)
            {
                if(str_starts_with(trailingslashit(ltrim($path, '/')), $namespace))
                {
                    $with_namespace[] = $this->get_routes($namespace);
                }
            }

            if($with_namespace)
            {
                $routes = array_merge(...$with_namespace);
            }
            else
            {
                $routes = $this->get_routes();
            }

            foreach($routes as $route => $handlers)
            {
                $match = preg_match('@^'.$route.'$@i', $path, $matches);

                if(! $match)
                {
                    continue;
                }

                $args = [];

                foreach($matches as $param => $value)
                {
                    if(! is_int($param))
                    {
                        $args[$param] = $value;
                    }
                }

                foreach($handlers as $handler)
                {
                    $callback = $handler['callback'];
                    $response = null;

                    // Fallback to GET method if no HEAD method is registered.
                    $checked_method = $method;
                    if('HEAD' === $method && empty($handler['methods']['HEAD']))
                    {
                        $checked_method = 'GET';
                    }
                    if(empty($handler['methods'][$checked_method]))
                    {
                        continue;
                    }

                    if(! is_callable($callback))
                    {
                        return [$route, $handler];
                    }

                    $request->set_url_params($args);
                    $request->set_attributes($handler);

                    $defaults = [];

                    foreach($handler['args'] as $arg => $options)
                    {
                        if(isset($options['default']))
                        {
                            $defaults[$arg] = $options['default'];
                        }
                    }

                    $request->set_default_params($defaults);

                    return [$route, $handler];
                }
            }

            return new WP_Error('rest_no_route', __('No route was found matching the URL and request method.'), ['status' => 404]);
        }

        public function get_namespaces()
        {
            return array_keys($this->namespaces);
        }

        public function get_routes($route_namespace = '')
        {
            $endpoints = $this->endpoints;

            if($route_namespace)
            {
                $endpoints = wp_list_filter($endpoints, ['namespace' => $route_namespace]);
            }

            $endpoints = apply_filters('rest_endpoints', $endpoints);

            // Normalize the endpoints.
            $defaults = [
                'methods' => '',
                'accept_json' => false,
                'accept_raw' => false,
                'show_in_index' => true,
                'args' => [],
            ];

            foreach($endpoints as $route => &$handlers)
            {
                if(isset($handlers['callback']))
                {
                    // Single endpoint, add one deeper.
                    $handlers = [$handlers];
                }

                if(! isset($this->route_options[$route]))
                {
                    $this->route_options[$route] = [];
                }

                foreach($handlers as $key => &$handler)
                {
                    if(! is_numeric($key))
                    {
                        // Route option, move it to the options.
                        $this->route_options[$route][$key] = $handler;
                        unset($handlers[$key]);
                        continue;
                    }

                    $handler = wp_parse_args($handler, $defaults);

                    // Allow comma-separated HTTP methods.
                    if(is_string($handler['methods']))
                    {
                        $methods = explode(',', $handler['methods']);
                    }
                    elseif(is_array($handler['methods']))
                    {
                        $methods = $handler['methods'];
                    }
                    else
                    {
                        $methods = [];
                    }

                    $handler['methods'] = [];

                    foreach($methods as $method)
                    {
                        $method = strtoupper(trim($method));
                        $handler['methods'][$method] = true;
                    }
                }
            }

            return $endpoints;
        }

        protected function respond_to_request($request, $route, $handler, $response)
        {
            $response = apply_filters('rest_request_before_callbacks', $response, $handler, $request);

            // Check permission specified on the route.
            if(! is_wp_error($response) && ! empty($handler['permission_callback']))
            {
                $permission = call_user_func($handler['permission_callback'], $request);

                if(is_wp_error($permission))
                {
                    $response = $permission;
                }
                elseif(false === $permission || null === $permission)
                {
                    $response = new WP_Error('rest_forbidden', __('Sorry, you are not allowed to do that.'), ['status' => rest_authorization_required_code()]);
                }
            }

            if(! is_wp_error($response))
            {
                $dispatch_result = apply_filters('rest_dispatch_request', null, $request, $route, $handler);

                // Allow plugins to halt the request via this filter.
                if(null !== $dispatch_result)
                {
                    $response = $dispatch_result;
                }
                else
                {
                    $response = call_user_func($handler['callback'], $request);
                }
            }

            $response = apply_filters('rest_request_after_callbacks', $response, $handler, $request);

            if(is_wp_error($response))
            {
                $response = $this->error_to_response($response);
            }
            else
            {
                $response = rest_ensure_response($response);
            }

            $response->set_matched_route($route);
            $response->set_matched_handler($handler);

            return $response;
        }

        public function envelope_response($response, $embed)
        {
            $envelope = [
                'body' => $this->response_to_data($response, $embed),
                'status' => $response->get_status(),
                'headers' => $response->get_headers(),
            ];

            $envelope = apply_filters('rest_envelope_response', $envelope, $response);

            // Ensure it's still a response and return.
            return rest_ensure_response($envelope);
        }

        public function response_to_data($response, $embed)
        {
            $data = $response->get_data();
            $links = self::get_compact_response_links($response);

            if(! empty($links))
            {
                // Convert links to part of the data.
                $data['_links'] = $links;
            }

            if($embed)
            {
                $this->embed_cache = [];
                // Determine if this is a numeric array.
                if(wp_is_numeric_array($data))
                {
                    foreach($data as $key => $item)
                    {
                        $data[$key] = $this->embed_links($item, $embed);
                    }
                }
                else
                {
                    $data = $this->embed_links($data, $embed);
                }
                $this->embed_cache = [];
            }

            return $data;
        }

        public static function get_compact_response_links($response)
        {
            $links = self::get_response_links($response);

            if(empty($links))
            {
                return [];
            }

            $curies = $response->get_curies();
            $used_curies = [];

            foreach($links as $rel => $items)
            {
                // Convert $rel URIs to their compact versions if they exist.
                foreach($curies as $curie)
                {
                    $href_prefix = substr($curie['href'], 0, strpos($curie['href'], '{rel}'));
                    if(! str_starts_with($rel, $href_prefix))
                    {
                        continue;
                    }

                    // Relation now changes from '$uri' to '$curie:$relation'.
                    $rel_regex = str_replace('\{rel\}', '(.+)', preg_quote($curie['href'], '!'));
                    preg_match('!'.$rel_regex.'!', $rel, $matches);
                    if($matches)
                    {
                        $new_rel = $curie['name'].':'.$matches[1];
                        $used_curies[$curie['name']] = $curie;
                        $links[$new_rel] = $items;
                        unset($links[$rel]);
                        break;
                    }
                }
            }

            // Push the curies onto the start of the links array.
            if($used_curies)
            {
                $links['curies'] = array_values($used_curies);
            }

            return $links;
        }

        public static function get_response_links($response)
        {
            $links = $response->get_links();

            if(empty($links))
            {
                return [];
            }

            // Convert links to part of the data.
            $data = [];
            foreach($links as $rel => $items)
            {
                $data[$rel] = [];

                foreach($items as $item)
                {
                    $attributes = $item['attributes'];
                    $attributes['href'] = $item['href'];
                    $data[$rel][] = $attributes;
                }
            }

            return $data;
        }

        protected function embed_links($data, $embed = true)
        {
            if(empty($data['_links']))
            {
                return $data;
            }

            $embedded = [];

            foreach($data['_links'] as $rel => $links)
            {
                /*
                 * If a list of relations was specified, and the link relation
                 * is not in the list of allowed relations, don't process the link.
                 */
                if(is_array($embed) && ! in_array($rel, $embed, true))
                {
                    continue;
                }

                $embeds = [];

                foreach($links as $item)
                {
                    // Determine if the link is embeddable.
                    if(empty($item['embeddable']))
                    {
                        // Ensure we keep the same order.
                        $embeds[] = [];
                        continue;
                    }

                    if(! array_key_exists($item['href'], $this->embed_cache))
                    {
                        // Run through our internal routing and serve.
                        $request = WP_REST_Request::from_url($item['href']);
                        if(! $request)
                        {
                            $embeds[] = [];
                            continue;
                        }

                        // Embedded resources get passed context=embed.
                        if(empty($request['context']))
                        {
                            $request['context'] = 'embed';
                        }

                        $response = $this->dispatch($request);

                        $response = apply_filters('rest_post_dispatch', rest_ensure_response($response), $this, $request);

                        $this->embed_cache[$item['href']] = $this->response_to_data($response, false);
                    }

                    $embeds[] = $this->embed_cache[$item['href']];
                }

                // Determine if any real links were found.
                $has_links = count(array_filter($embeds));

                if($has_links)
                {
                    $embedded[$rel] = $embeds;
                }
            }

            if(! empty($embedded))
            {
                $data['_embedded'] = $embedded;
            }

            return $data;
        }

        public function send_headers($headers)
        {
            foreach($headers as $key => $value)
            {
                $this->send_header($key, $value);
            }
        }

        protected function get_json_encode_options(WP_REST_Request $request)
        {
            $options = 0;

            if($request->has_param('_pretty'))
            {
                $options |= JSON_PRETTY_PRINT;
            }

            return apply_filters('rest_json_encode_options', $options, $request);
        }

        protected function get_json_last_error()
        {
            $last_error_code = json_last_error();

            if(JSON_ERROR_NONE === $last_error_code || empty($last_error_code))
            {
                return false;
            }

            return json_last_error_msg();
        }

        public function register_route($route_namespace, $route, $route_args, $override = false)
        {
            if(! isset($this->namespaces[$route_namespace]))
            {
                $this->namespaces[$route_namespace] = [];

                $this->register_route($route_namespace, '/'.$route_namespace, [
                    [
                        'methods' => self::READABLE,
                        'callback' => [$this, 'get_namespace_index'],
                        'args' => [
                            'namespace' => [
                                'default' => $route_namespace,
                            ],
                            'context' => [
                                'default' => 'view',
                            ],
                        ],
                    ],
                ]);
            }

            // Associative to avoid double-registration.
            $this->namespaces[$route_namespace][$route] = true;

            $route_args['namespace'] = $route_namespace;

            if($override || empty($this->endpoints[$route]))
            {
                $this->endpoints[$route] = $route_args;
            }
            else
            {
                $this->endpoints[$route] = array_merge($this->endpoints[$route], $route_args);
            }
        }

        public function get_index($request)
        {
            // General site data.
            $available = [
                'name' => get_option('blogname'),
                'description' => get_option('blogdescription'),
                'url' => get_option('siteurl'),
                'home' => home_url(),
                'gmt_offset' => get_option('gmt_offset'),
                'timezone_string' => get_option('timezone_string'),
                'namespaces' => array_keys($this->namespaces),
                'authentication' => [],
                'routes' => $this->get_data_for_routes($this->get_routes(), $request['context']),
            ];

            $response = new WP_REST_Response($available);

            $fields = isset($request['_fields']) ? $request['_fields'] : '';
            $fields = wp_parse_list($fields);
            if(empty($fields))
            {
                $fields[] = '_links';
            }

            if($request->has_param('_embed'))
            {
                $fields[] = '_embedded';
            }

            if(rest_is_field_included('_links', $fields) || rest_is_field_included('_embedded', $fields))
            {
                $response->add_link('help', 'https://developer.wordpress.org/rest-api/');
                $this->add_active_theme_link_to_index($response);
                $this->add_site_logo_to_index($response);
                $this->add_site_icon_to_index($response);
            }

            return apply_filters('rest_index', $response, $request);
        }

        public function get_data_for_routes($routes, $context = 'view')
        {
            $available = [];

            // Find the available routes.
            foreach($routes as $route => $callbacks)
            {
                $data = $this->get_data_for_route($route, $callbacks, $context);
                if(empty($data))
                {
                    continue;
                }

                $available[$route] = apply_filters('rest_endpoints_description', $data);
            }

            return apply_filters('rest_route_data', $available, $routes);
        }

        public function get_data_for_route($route, $callbacks, $context = 'view')
        {
            $data = [
                'namespace' => '',
                'methods' => [],
                'endpoints' => [],
            ];

            $allow_batch = false;

            if(isset($this->route_options[$route]))
            {
                $options = $this->route_options[$route];

                if(isset($options['namespace']))
                {
                    $data['namespace'] = $options['namespace'];
                }

                $allow_batch = isset($options['allow_batch']) ? $options['allow_batch'] : false;

                if(isset($options['schema']) && 'help' === $context)
                {
                    $data['schema'] = call_user_func($options['schema']);
                }
            }

            $allowed_schema_keywords = array_flip(rest_get_allowed_schema_keywords());

            $route = preg_replace('#\(\?P<(\w+?)>.*?\)#', '{$1}', $route);

            foreach($callbacks as $callback)
            {
                // Skip to the next route if any callback is hidden.
                if(empty($callback['show_in_index']))
                {
                    continue;
                }

                $data['methods'] = array_merge($data['methods'], array_keys($callback['methods']));
                $endpoint_data = [
                    'methods' => array_keys($callback['methods']),
                ];

                $callback_batch = isset($callback['allow_batch']) ? $callback['allow_batch'] : $allow_batch;

                if($callback_batch)
                {
                    $endpoint_data['allow_batch'] = $callback_batch;
                }

                if(isset($callback['args']))
                {
                    $endpoint_data['args'] = [];

                    foreach($callback['args'] as $key => $opts)
                    {
                        if(is_string($opts))
                        {
                            $opts = [$opts => 0];
                        }
                        elseif(! is_array($opts))
                        {
                            $opts = [];
                        }
                        $arg_data = array_intersect_key($opts, $allowed_schema_keywords);
                        $arg_data['required'] = ! empty($opts['required']);

                        $endpoint_data['args'][$key] = $arg_data;
                    }
                }

                $data['endpoints'][] = $endpoint_data;

                // For non-variable routes, generate links.
                if(! str_contains($route, '{'))
                {
                    $data['_links'] = [
                        'self' => [
                            [
                                'href' => rest_url($route),
                            ],
                        ],
                    ];
                }
            }

            if(empty($data['methods']))
            {
                // No methods supported, hide the route.
                return null;
            }

            return $data;
        }

        protected function add_active_theme_link_to_index(WP_REST_Response $response)
        {
            $should_add = current_user_can('switch_themes') || current_user_can('manage_network_themes');

            if(! $should_add && current_user_can('edit_posts'))
            {
                $should_add = true;
            }

            if(! $should_add)
            {
                foreach(get_post_types(['show_in_rest' => true], 'objects') as $post_type)
                {
                    if(current_user_can($post_type->cap->edit_posts))
                    {
                        $should_add = true;
                        break;
                    }
                }
            }

            if($should_add)
            {
                $theme = wp_get_theme();
                $response->add_link('https://api.w.org/active-theme', rest_url('wp/v2/themes/'.$theme->get_stylesheet()));
            }
        }

        protected function add_site_logo_to_index(WP_REST_Response $response)
        {
            $site_logo_id = get_theme_mod('custom_logo', 0);

            $this->add_image_to_index($response, $site_logo_id, 'site_logo');
        }

        protected function add_image_to_index(WP_REST_Response $response, $image_id, $type)
        {
            $response->data[$type] = (int) $image_id;
            if($image_id)
            {
                $response->add_link('https://api.w.org/featuredmedia', rest_url(rest_get_route_for_post($image_id)), [
                    'embeddable' => true,
                    'type' => $type,
                ]);
            }
        }

        protected function add_site_icon_to_index(WP_REST_Response $response)
        {
            $site_icon_id = get_option('site_icon', 0);

            $this->add_image_to_index($response, $site_icon_id, 'site_icon');

            $response->data['site_icon_url'] = get_site_icon_url();
        }

        public function get_namespace_index($request)
        {
            $namespace = $request['namespace'];

            if(! isset($this->namespaces[$namespace]))
            {
                return new WP_Error('rest_invalid_namespace', __('The specified namespace could not be found.'), ['status' => 404]);
            }

            $routes = $this->namespaces[$namespace];
            $endpoints = array_intersect_key($this->get_routes(), $routes);

            $data = [
                'namespace' => $namespace,
                'routes' => $this->get_data_for_routes($endpoints, $request['context']),
            ];
            $response = rest_ensure_response($data);

            // Link to the root index.
            $response->add_link('up', rest_url('/'));

            return apply_filters('rest_namespace_index', $response, $request);
        }

        public function serve_batch_request_v1(WP_REST_Request $batch_request)
        {
            $requests = [];

            foreach($batch_request['requests'] as $args)
            {
                $parsed_url = wp_parse_url($args['path']);

                if(false === $parsed_url)
                {
                    $requests[] = new WP_Error('parse_path_failed', __('Could not parse the path.'), ['status' => 400]);

                    continue;
                }

                $single_request = new WP_REST_Request(isset($args['method']) ? $args['method'] : 'POST', $parsed_url['path']);

                if(! empty($parsed_url['query']))
                {
                    $query_args = null; // Satisfy linter.
                    wp_parse_str($parsed_url['query'], $query_args);
                    $single_request->set_query_params($query_args);
                }

                if(! empty($args['body']))
                {
                    $single_request->set_body_params($args['body']);
                }

                if(! empty($args['headers']))
                {
                    $single_request->set_headers($args['headers']);
                }

                $requests[] = $single_request;
            }

            $matches = [];
            $validation = [];
            $has_error = false;

            foreach($requests as $single_request)
            {
                $match = $this->match_request_to_handler($single_request);
                $matches[] = $match;
                $error = null;

                if(is_wp_error($match))
                {
                    $error = $match;
                }

                if(! $error)
                {
                    [$route, $handler] = $match;

                    if(isset($handler['allow_batch']))
                    {
                        $allow_batch = $handler['allow_batch'];
                    }
                    else
                    {
                        $route_options = $this->get_route_options($route);
                        $allow_batch = isset($route_options['allow_batch']) ? $route_options['allow_batch'] : false;
                    }

                    if(! is_array($allow_batch) || empty($allow_batch['v1']))
                    {
                        $error = new WP_Error('rest_batch_not_allowed', __('The requested route does not support batch requests.'), ['status' => 400]);
                    }
                }

                if(! $error)
                {
                    $check_required = $single_request->has_valid_params();
                    if(is_wp_error($check_required))
                    {
                        $error = $check_required;
                    }
                }

                if(! $error)
                {
                    $check_sanitized = $single_request->sanitize_params();
                    if(is_wp_error($check_sanitized))
                    {
                        $error = $check_sanitized;
                    }
                }

                if($error)
                {
                    $has_error = true;
                    $validation[] = $error;
                }
                else
                {
                    $validation[] = true;
                }
            }

            $responses = [];

            if($has_error && 'require-all-validate' === $batch_request['validation'])
            {
                foreach($validation as $valid)
                {
                    if(is_wp_error($valid))
                    {
                        $responses[] = $this->envelope_response($this->error_to_response($valid), false)->get_data();
                    }
                    else
                    {
                        $responses[] = null;
                    }
                }

                return new WP_REST_Response([
                                                'failed' => 'validation',
                                                'responses' => $responses,
                                            ], WP_Http::MULTI_STATUS);
            }

            foreach($requests as $i => $single_request)
            {
                $clean_request = clone $single_request;
                $clean_request->set_url_params([]);
                $clean_request->set_attributes([]);
                $clean_request->set_default_params([]);

                $result = apply_filters('rest_pre_dispatch', null, $this, $clean_request);

                if(empty($result))
                {
                    $match = $matches[$i];
                    $error = null;

                    if(is_wp_error($validation[$i]))
                    {
                        $error = $validation[$i];
                    }

                    if(is_wp_error($match))
                    {
                        $result = $this->error_to_response($match);
                    }
                    else
                    {
                        [$route, $handler] = $match;

                        if(! $error && ! is_callable($handler['callback']))
                        {
                            $error = new WP_Error('rest_invalid_handler', __('The handler for the route is invalid'), ['status' => 500]);
                        }

                        $result = $this->respond_to_request($single_request, $route, $handler, $error);
                    }
                }

                $result = apply_filters('rest_post_dispatch', rest_ensure_response($result), $this, $single_request);

                $responses[] = $this->envelope_response($result, false)->get_data();
            }

            return new WP_REST_Response(['responses' => $responses], WP_Http::MULTI_STATUS);
        }

        public function get_route_options($route)
        {
            if(! isset($this->route_options[$route]))
            {
                return null;
            }

            return $this->route_options[$route];
        }
    }
