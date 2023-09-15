<?php

    class WP_REST_Search_Controller extends WP_REST_Controller
    {
        public const PROP_ID = 'id';

        public const PROP_TITLE = 'title';

        public const PROP_URL = 'url';

        public const PROP_TYPE = 'type';

        public const PROP_SUBTYPE = 'subtype';

        public const TYPE_ANY = 'any';

        protected $search_handlers = [];

        public function __construct(array $search_handlers)
        {
            $this->namespace = 'wp/v2';
            $this->rest_base = 'search';

            foreach($search_handlers as $search_handler)
            {
                if(! $search_handler instanceof WP_REST_Search_Handler)
                {
                    _doing_it_wrong(__METHOD__, /* translators: %s: PHP class name. */ sprintf(__('REST search handlers must extend the %s class.'), 'WP_REST_Search_Handler'), '5.0.0');
                    continue;
                }

                $this->search_handlers[$search_handler->get_type()] = $search_handler;
            }
        }

        public function register_routes()
        {
            parent::register_routes();
            register_rest_route($this->namespace, '/'.$this->rest_base, [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_items'],
                    'permission_callback' => [$this, 'get_items_permission_check'],
                    'args' => $this->get_collection_params(),
                ],
                'schema' => [$this, 'get_public_item_schema'],
            ]);
        }

        public function get_collection_params()
        {
            $types = [];
            $subtypes = [];

            foreach($this->search_handlers as $search_handler)
            {
                $types[] = $search_handler->get_type();
                $subtypes = array_merge($subtypes, $search_handler->get_subtypes());
            }

            $types = array_unique($types);
            $subtypes = array_unique($subtypes);

            $query_params = parent::get_collection_params();

            $query_params['context']['default'] = 'view';

            $query_params[self::PROP_TYPE] = [
                'default' => $types[0],
                'description' => __('Limit results to items of an object type.'),
                'type' => 'string',
                'enum' => $types,
            ];

            $query_params[self::PROP_SUBTYPE] = [
                'default' => self::TYPE_ANY,
                'description' => __('Limit results to items of one or more object subtypes.'),
                'type' => 'array',
                'items' => [
                    'enum' => array_merge($subtypes, [self::TYPE_ANY]),
                    'type' => 'string',
                ],
                'sanitize_callback' => [$this, 'sanitize_subtypes'],
            ];

            $query_params['exclude'] = [
                'description' => __('Ensure result set excludes specific IDs.'),
                'type' => 'array',
                'items' => [
                    'type' => 'integer',
                ],
                'default' => [],
            ];

            $query_params['include'] = [
                'description' => __('Limit result set to specific IDs.'),
                'type' => 'array',
                'items' => [
                    'type' => 'integer',
                ],
                'default' => [],
            ];

            return $query_params;
        }

        public function get_items_permission_check($request)
        {
            return true;
        }

        public function get_items($request)
        {
            $handler = $this->get_search_handler($request);
            if(is_wp_error($handler))
            {
                return $handler;
            }

            $result = $handler->search_items($request);

            if(! isset($result[WP_REST_Search_Handler::RESULT_IDS]) || ! is_array($result[WP_REST_Search_Handler::RESULT_IDS]) || ! isset($result[WP_REST_Search_Handler::RESULT_TOTAL]))
            {
                return new WP_Error('rest_search_handler_error', __('Internal search handler error.'), ['status' => 500]);
            }

            $ids = $result[WP_REST_Search_Handler::RESULT_IDS];

            $results = [];

            foreach($ids as $id)
            {
                $data = $this->prepare_item_for_response($id, $request);
                $results[] = $this->prepare_response_for_collection($data);
            }

            $total = (int) $result[WP_REST_Search_Handler::RESULT_TOTAL];
            $page = (int) $request['page'];
            $per_page = (int) $request['per_page'];
            $max_pages = ceil($total / $per_page);

            if($page > $max_pages && $total > 0)
            {
                return new WP_Error('rest_search_invalid_page_number', __('The page number requested is larger than the number of pages available.'), ['status' => 400]);
            }

            $response = rest_ensure_response($results);
            $response->header('X-WP-Total', $total);
            $response->header('X-WP-TotalPages', $max_pages);

            $request_params = $request->get_query_params();
            $base = add_query_arg(urlencode_deep($request_params), rest_url(sprintf('%s/%s', $this->namespace, $this->rest_base)));

            if($page > 1)
            {
                $prev_link = add_query_arg('page', $page - 1, $base);
                $response->link_header('prev', $prev_link);
            }
            if($page < $max_pages)
            {
                $next_link = add_query_arg('page', $page + 1, $base);
                $response->link_header('next', $next_link);
            }

            return $response;
        }

        protected function get_search_handler($request)
        {
            $type = $request->get_param(self::PROP_TYPE);

            if(! $type || ! isset($this->search_handlers[$type]))
            {
                return new WP_Error('rest_search_invalid_type', __('Invalid type parameter.'), ['status' => 400]);
            }

            return $this->search_handlers[$type];
        }

        public function prepare_item_for_response($item, $request)
        {
            // Restores the more descriptive, specific name for use within this method.
            $item_id = $item;

            $handler = $this->get_search_handler($request);
            if(is_wp_error($handler))
            {
                return new WP_REST_Response();
            }

            $fields = $this->get_fields_for_response($request);

            $data = $handler->prepare_item($item_id, $fields);
            $data = $this->add_additional_fields_to_object($data, $request);

            $context = ! empty($request['context']) ? $request['context'] : 'view';
            $data = $this->filter_response_by_context($data, $context);

            $response = rest_ensure_response($data);

            if(rest_is_field_included('_links', $fields) || rest_is_field_included('_embedded', $fields))
            {
                $links = $handler->prepare_item_links($item_id);
                $links['collection'] = [
                    'href' => rest_url(sprintf('%s/%s', $this->namespace, $this->rest_base)),
                ];
                $response->add_links($links);
            }

            return $response;
        }

        public function get_item_schema()
        {
            if($this->schema)
            {
                return $this->add_additional_fields_schema($this->schema);
            }

            $types = [];
            $subtypes = [];

            foreach($this->search_handlers as $search_handler)
            {
                $types[] = $search_handler->get_type();
                $subtypes = array_merge($subtypes, $search_handler->get_subtypes());
            }

            $types = array_unique($types);
            $subtypes = array_unique($subtypes);

            $schema = [
                '$schema' => 'http://json-schema.org/draft-04/schema#',
                'title' => 'search-result',
                'type' => 'object',
                'properties' => [
                    self::PROP_ID => [
                        'description' => __('Unique identifier for the object.'),
                        'type' => ['integer', 'string'],
                        'context' => ['view', 'embed'],
                        'readonly' => true,
                    ],
                    self::PROP_TITLE => [
                        'description' => __('The title for the object.'),
                        'type' => 'string',
                        'context' => ['view', 'embed'],
                        'readonly' => true,
                    ],
                    self::PROP_URL => [
                        'description' => __('URL to the object.'),
                        'type' => 'string',
                        'format' => 'uri',
                        'context' => ['view', 'embed'],
                        'readonly' => true,
                    ],
                    self::PROP_TYPE => [
                        'description' => __('Object type.'),
                        'type' => 'string',
                        'enum' => $types,
                        'context' => ['view', 'embed'],
                        'readonly' => true,
                    ],
                    self::PROP_SUBTYPE => [
                        'description' => __('Object subtype.'),
                        'type' => 'string',
                        'enum' => $subtypes,
                        'context' => ['view', 'embed'],
                        'readonly' => true,
                    ],
                ],
            ];

            $this->schema = $schema;

            return $this->add_additional_fields_schema($this->schema);
        }

        public function sanitize_subtypes($subtypes, $request, $parameter)
        {
            $subtypes = wp_parse_slug_list($subtypes);

            $subtypes = rest_parse_request_arg($subtypes, $request, $parameter);
            if(is_wp_error($subtypes))
            {
                return $subtypes;
            }

            // 'any' overrides any other subtype.
            if(in_array(self::TYPE_ANY, $subtypes, true))
            {
                return [self::TYPE_ANY];
            }

            $handler = $this->get_search_handler($request);
            if(is_wp_error($handler))
            {
                return $handler;
            }

            return array_intersect($subtypes, $handler->get_subtypes());
        }
    }
