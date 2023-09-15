<?php

    class WP_REST_Navigation_Fallback_Controller extends WP_REST_Controller
    {
        private $post_type;

        public function __construct()
        {
            $this->namespace = 'wp-block-editor/v1';
            $this->rest_base = 'navigation-fallback';
            $this->post_type = 'wp_navigation';
        }

        public function register_routes()
        {
            // Lists a single nav item based on the given id or slug.
            parent::register_routes();
            register_rest_route($this->namespace, '/'.$this->rest_base, [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_item'],
                    'permission_callback' => [$this, 'get_item_permissions_check'],
                    'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::READABLE),
                ],
                'schema' => [$this, 'get_item_schema'],
            ]);
        }

        public function get_item_permissions_check($request)
        {
            $post_type = get_post_type_object($this->post_type);

            // Getting fallbacks requires creating and reading `wp_navigation` posts.
            if(! current_user_can($post_type->cap->create_posts) || ! current_user_can('edit_theme_options') || ! current_user_can('edit_posts'))
            {
                return new WP_Error('rest_cannot_create', __('Sorry, you are not allowed to create Navigation Menus as this user.'), ['status' => rest_authorization_required_code()]);
            }

            if('edit' === $request['context'] && ! current_user_can($post_type->cap->edit_posts))
            {
                return new WP_Error('rest_forbidden_context', __('Sorry, you are not allowed to edit Navigation Menus as this user.'), ['status' => rest_authorization_required_code()]);
            }

            return true;
        }

        public function get_item($request)
        {
            $post = WP_Navigation_Fallback::get_fallback();

            if(empty($post))
            {
                return rest_ensure_response(new WP_Error('no_fallback_menu', __('No fallback menu found.'), ['status' => 404]));
            }

            $response = $this->prepare_item_for_response($post, $request);

            return $response;
        }

        public function prepare_item_for_response($item, $request)
        {
            $data = [];

            $fields = $this->get_fields_for_response($request);

            if(rest_is_field_included('id', $fields))
            {
                $data['id'] = (int) $item->ID;
            }

            $context = ! empty($request['context']) ? $request['context'] : 'view';
            $data = $this->add_additional_fields_to_object($data, $request);
            $data = $this->filter_response_by_context($data, $context);

            $response = rest_ensure_response($data);

            if(rest_is_field_included('_links', $fields) || rest_is_field_included('_embedded', $fields))
            {
                $links = $this->prepare_links($item);
                $response->add_links($links);
            }

            return $response;
        }

        private function prepare_links($post)
        {
            return [
                'self' => [
                    'href' => rest_url(rest_get_route_for_post($post->ID)),
                    'embeddable' => true,
                ],
            ];
        }

        public function get_item_schema()
        {
            if($this->schema)
            {
                return $this->add_additional_fields_schema($this->schema);
            }

            $this->schema = [
                '$schema' => 'http://json-schema.org/draft-04/schema#',
                'title' => 'navigation-fallback',
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'description' => __('The unique identifier for the Navigation Menu.'),
                        'type' => 'integer',
                        'context' => ['view', 'edit', 'embed'],
                        'readonly' => true,
                    ],
                ],
            ];

            return $this->add_additional_fields_schema($this->schema);
        }
    }
