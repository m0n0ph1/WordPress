<?php

    class WP_REST_Menu_Locations_Controller extends WP_REST_Controller
    {
        public function __construct()
        {
            $this->namespace = 'wp/v2';
            $this->rest_base = 'menu-locations';
        }

        public function register_routes()
        {
            register_rest_route($this->namespace, '/'.$this->rest_base, [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_items'],
                    'permission_callback' => [$this, 'get_items_permissions_check'],
                    'args' => $this->get_collection_params(),
                ],
                'schema' => [$this, 'get_public_item_schema'],
            ]);

            register_rest_route($this->namespace, '/'.$this->rest_base.'/(?P<location>[\w-]+)', [
                'args' => [
                    'location' => [
                        'description' => __('An alphanumeric identifier for the menu location.'),
                        'type' => 'string',
                    ],
                ],
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_item'],
                    'permission_callback' => [$this, 'get_item_permissions_check'],
                    'args' => [
                        'context' => $this->get_context_param(['default' => 'view']),
                    ],
                ],
                'schema' => [$this, 'get_public_item_schema'],
            ]);
        }

        public function get_collection_params()
        {
            return [
                'context' => $this->get_context_param(['default' => 'view']),
            ];
        }

        public function get_items_permissions_check($request)
        {
            if(! current_user_can('edit_theme_options'))
            {
                return new WP_Error('rest_cannot_view', __('Sorry, you are not allowed to view menu locations.'), ['status' => rest_authorization_required_code()]);
            }

            return true;
        }

        public function get_items($request)
        {
            $data = [];

            foreach(get_registered_nav_menus() as $name => $description)
            {
                $location = new stdClass();
                $location->name = $name;
                $location->description = $description;

                $location = $this->prepare_item_for_response($location, $request);
                $data[$name] = $this->prepare_response_for_collection($location);
            }

            return rest_ensure_response($data);
        }

        public function prepare_item_for_response($item, $request)
        {
            // Restores the more descriptive, specific name for use within this method.
            $location = $item;

            $locations = get_nav_menu_locations();
            $menu = isset($locations[$location->name]) ? $locations[$location->name] : 0;

            $fields = $this->get_fields_for_response($request);
            $data = [];

            if(rest_is_field_included('name', $fields))
            {
                $data['name'] = $location->name;
            }

            if(rest_is_field_included('description', $fields))
            {
                $data['description'] = $location->description;
            }

            if(rest_is_field_included('menu', $fields))
            {
                $data['menu'] = (int) $menu;
            }

            $context = ! empty($request['context']) ? $request['context'] : 'view';
            $data = $this->add_additional_fields_to_object($data, $request);
            $data = $this->filter_response_by_context($data, $context);

            $response = rest_ensure_response($data);

            if(rest_is_field_included('_links', $fields) || rest_is_field_included('_embedded', $fields))
            {
                $response->add_links($this->prepare_links($location));
            }

            return apply_filters('rest_prepare_menu_location', $response, $location, $request);
        }

        protected function prepare_links($location)
        {
            $base = sprintf('%s/%s', $this->namespace, $this->rest_base);

            // Entity meta.
            $links = [
                'self' => [
                    'href' => rest_url(trailingslashit($base).$location->name),
                ],
                'collection' => [
                    'href' => rest_url($base),
                ],
            ];

            $locations = get_nav_menu_locations();
            $menu = isset($locations[$location->name]) ? $locations[$location->name] : 0;
            if($menu)
            {
                $path = rest_get_route_for_term($menu);
                if($path)
                {
                    $url = rest_url($path);

                    $links['https://api.w.org/menu'][] = [
                        'href' => $url,
                        'embeddable' => true,
                    ];
                }
            }

            return $links;
        }

        public function get_item_permissions_check($request)
        {
            if(! current_user_can('edit_theme_options'))
            {
                return new WP_Error('rest_cannot_view', __('Sorry, you are not allowed to view menu locations.'), ['status' => rest_authorization_required_code()]);
            }

            return true;
        }

        public function get_item($request)
        {
            $registered_menus = get_registered_nav_menus();
            if(! array_key_exists($request['location'], $registered_menus))
            {
                return new WP_Error('rest_menu_location_invalid', __('Invalid menu location.'), ['status' => 404]);
            }

            $location = new stdClass();
            $location->name = $request['location'];
            $location->description = $registered_menus[$location->name];

            $data = $this->prepare_item_for_response($location, $request);

            return rest_ensure_response($data);
        }

        public function get_item_schema()
        {
            if($this->schema)
            {
                return $this->add_additional_fields_schema($this->schema);
            }

            $this->schema = [
                '$schema' => 'http://json-schema.org/draft-04/schema#',
                'title' => 'menu-location',
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'description' => __('The name of the menu location.'),
                        'type' => 'string',
                        'context' => ['embed', 'view', 'edit'],
                        'readonly' => true,
                    ],
                    'description' => [
                        'description' => __('The description of the menu location.'),
                        'type' => 'string',
                        'context' => ['embed', 'view', 'edit'],
                        'readonly' => true,
                    ],
                    'menu' => [
                        'description' => __('The ID of the assigned menu.'),
                        'type' => 'integer',
                        'context' => ['embed', 'view', 'edit'],
                        'readonly' => true,
                    ],
                ],
            ];

            return $this->add_additional_fields_schema($this->schema);
        }
    }
