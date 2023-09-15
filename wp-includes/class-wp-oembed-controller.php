<?php

    #[AllowDynamicProperties]
    final class WP_oEmbed_Controller
    {
        public function register_routes()
        {
            $maxwidth = apply_filters('oembed_default_width', 600);

            register_rest_route('oembed/1.0', '/embed', [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_item'],
                    'permission_callback' => '__return_true',
                    'args' => [
                        'url' => [
                            'description' => __('The URL of the resource for which to fetch oEmbed data.'),
                            'required' => true,
                            'type' => 'string',
                            'format' => 'uri',
                        ],
                        'format' => [
                            'default' => 'json',
                            'sanitize_callback' => 'wp_oembed_ensure_format',
                        ],
                        'maxwidth' => [
                            'default' => $maxwidth,
                            'sanitize_callback' => 'absint',
                        ],
                    ],
                ],
            ]);

            register_rest_route('oembed/1.0', '/proxy', [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_proxy_item'],
                    'permission_callback' => [$this, 'get_proxy_item_permissions_check'],
                    'args' => [
                        'url' => [
                            'description' => __('The URL of the resource for which to fetch oEmbed data.'),
                            'required' => true,
                            'type' => 'string',
                            'format' => 'uri',
                        ],
                        'format' => [
                            'description' => __('The oEmbed format to use.'),
                            'type' => 'string',
                            'default' => 'json',
                            'enum' => [
                                'json',
                                'xml',
                            ],
                        ],
                        'maxwidth' => [
                            'description' => __('The maximum width of the embed frame in pixels.'),
                            'type' => 'integer',
                            'default' => $maxwidth,
                            'sanitize_callback' => 'absint',
                        ],
                        'maxheight' => [
                            'description' => __('The maximum height of the embed frame in pixels.'),
                            'type' => 'integer',
                            'sanitize_callback' => 'absint',
                        ],
                        'discover' => [
                            'description' => __('Whether to perform an oEmbed discovery request for unsanctioned providers.'),
                            'type' => 'boolean',
                            'default' => true,
                        ],
                    ],
                ],
            ]);
        }

        public function get_item($request)
        {
            $post_id = url_to_postid($request['url']);

            $post_id = apply_filters('oembed_request_post_id', $post_id, $request['url']);

            $data = get_oembed_response_data($post_id, $request['maxwidth']);

            if(! $data)
            {
                return new WP_Error('oembed_invalid_url', get_status_header_desc(404), ['status' => 404]);
            }

            return $data;
        }

        public function get_proxy_item_permissions_check()
        {
            if(! current_user_can('edit_posts'))
            {
                return new WP_Error('rest_forbidden', __('Sorry, you are not allowed to make proxied oEmbed requests.'), ['status' => rest_authorization_required_code()]);
            }

            return true;
        }

        public function get_proxy_item($request)
        {
            global $wp_embed, $wp_scripts;

            $args = $request->get_params();

            // Serve oEmbed data from cache if set.
            unset($args['_wpnonce']);
            $cache_key = 'oembed_'.md5(serialize($args));
            $data = get_transient($cache_key);
            if(! empty($data))
            {
                return $data;
            }

            $url = $request['url'];
            unset($args['url']);

            // Copy maxwidth/maxheight to width/height since WP_oEmbed::fetch() uses these arg names.
            if(isset($args['maxwidth']))
            {
                $args['width'] = $args['maxwidth'];
            }
            if(isset($args['maxheight']))
            {
                $args['height'] = $args['maxheight'];
            }

            // Short-circuit process for URLs belonging to the current site.
            $data = get_oembed_response_data_for_url($url, $args);

            if($data)
            {
                return $data;
            }

            $data = _wp_oembed_get_object()->get_data($url, $args);

            if(false === $data)
            {
                // Try using a classic embed, instead.
                /* @var WP_Embed $wp_embed */
                $html = $wp_embed->get_embed_handler_html($args, $url);

                if($html)
                {
                    // Check if any scripts were enqueued by the shortcode, and include them in the response.
                    $enqueued_scripts = [];

                    foreach($wp_scripts->queue as $script)
                    {
                        $enqueued_scripts[] = $wp_scripts->registered[$script]->src;
                    }

                    return (object) [
                        'provider_name' => __('Embed Handler'),
                        'html' => $html,
                        'scripts' => $enqueued_scripts,
                    ];
                }

                return new WP_Error('oembed_invalid_url', get_status_header_desc(404), ['status' => 404]);
            }

            $data->html = apply_filters('oembed_result', _wp_oembed_get_object()->data2html((object) $data, $url), $url, $args);

            $ttl = apply_filters('rest_oembed_ttl', DAY_IN_SECONDS, $url, $args);

            set_transient($cache_key, $data, $ttl);

            return $data;
        }
    }
