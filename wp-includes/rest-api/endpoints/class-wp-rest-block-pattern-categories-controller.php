<?php

    class WP_REST_Block_Pattern_Categories_Controller extends WP_REST_Controller
    {
        public function __construct()
        {
            $this->namespace = 'wp/v2';
            $this->rest_base = 'block-patterns/categories';
        }

        public function register_routes()
        {
            parent::register_routes();
            register_rest_route($this->namespace, '/'.$this->rest_base, [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_items'],
                    'permission_callback' => [$this, 'get_items_permissions_check'],
                ],
                'schema' => [$this, 'get_public_item_schema'],
            ]);
        }

        public function get_items_permissions_check($request)
        {
            if(current_user_can('edit_posts'))
            {
                return true;
            }

            foreach(get_post_types(['show_in_rest' => true], 'objects') as $post_type)
            {
                if(current_user_can($post_type->cap->edit_posts))
                {
                    return true;
                }
            }

            return new WP_Error('rest_cannot_view', __('Sorry, you are not allowed to view the registered block pattern categories.'), ['status' => rest_authorization_required_code()]);
        }

        public function get_items($request)
        {
            $response = [];
            $categories = WP_Block_Pattern_Categories_Registry::get_instance()->get_all_registered();
            foreach($categories as $category)
            {
                $prepared_category = $this->prepare_item_for_response($category, $request);
                $response[] = $this->prepare_response_for_collection($prepared_category);
            }

            return rest_ensure_response($response);
        }

        public function prepare_item_for_response($item, $request)
        {
            $fields = $this->get_fields_for_response($request);
            $keys = ['name', 'label', 'description'];
            $data = [];
            foreach($keys as $key)
            {
                if(isset($item[$key]) && rest_is_field_included($key, $fields))
                {
                    $data[$key] = $item[$key];
                }
            }

            $context = ! empty($request['context']) ? $request['context'] : 'view';
            $data = $this->add_additional_fields_to_object($data, $request);
            $data = $this->filter_response_by_context($data, $context);

            return rest_ensure_response($data);
        }

        public function get_item_schema()
        {
            if($this->schema)
            {
                return $this->add_additional_fields_schema($this->schema);
            }

            $schema = [
                '$schema' => 'http://json-schema.org/draft-04/schema#',
                'title' => 'block-pattern-category',
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'description' => __('The category name.'),
                        'type' => 'string',
                        'readonly' => true,
                        'context' => ['view', 'edit', 'embed'],
                    ],
                    'label' => [
                        'description' => __('The category label, in human readable format.'),
                        'type' => 'string',
                        'readonly' => true,
                        'context' => ['view', 'edit', 'embed'],
                    ],
                    'description' => [
                        'description' => __('The category description, in human readable format.'),
                        'type' => 'string',
                        'readonly' => true,
                        'context' => ['view', 'edit', 'embed'],
                    ],
                ],
            ];

            $this->schema = $schema;

            return $this->add_additional_fields_schema($this->schema);
        }
    }
