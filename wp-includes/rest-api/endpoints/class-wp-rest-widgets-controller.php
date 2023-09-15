<?php

    class WP_REST_Widgets_Controller extends WP_REST_Controller
    {
        protected $widgets_retrieved = false;

        protected $allow_batch = ['v1' => true];

        public function __construct()
        {
            $this->namespace = 'wp/v2';
            $this->rest_base = 'widgets';
        }

        public function register_routes()
        {
            register_rest_route($this->namespace, $this->rest_base, [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_items'],
                    'permission_callback' => [$this, 'get_items_permissions_check'],
                    'args' => $this->get_collection_params(),
                ],
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'create_item'],
                    'permission_callback' => [$this, 'create_item_permissions_check'],
                    'args' => $this->get_endpoint_args_for_item_schema(),
                ],
                'allow_batch' => $this->allow_batch,
                'schema' => [$this, 'get_public_item_schema'],
            ]);

            register_rest_route($this->namespace, $this->rest_base.'/(?P<id>[\w\-]+)', [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_item'],
                    'permission_callback' => [$this, 'get_item_permissions_check'],
                    'args' => [
                        'context' => $this->get_context_param(['default' => 'view']),
                    ],
                ],
                [
                    'methods' => WP_REST_Server::EDITABLE,
                    'callback' => [$this, 'update_item'],
                    'permission_callback' => [$this, 'update_item_permissions_check'],
                    'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE),
                ],
                [
                    'methods' => WP_REST_Server::DELETABLE,
                    'callback' => [$this, 'delete_item'],
                    'permission_callback' => [$this, 'delete_item_permissions_check'],
                    'args' => [
                        'force' => [
                            'description' => __('Whether to force removal of the widget, or move it to the inactive sidebar.'),
                            'type' => 'boolean',
                        ],
                    ],
                ],
                'allow_batch' => $this->allow_batch,
                'schema' => [$this, 'get_public_item_schema'],
            ]);
        }

        public function get_collection_params()
        {
            return [
                'context' => $this->get_context_param(['default' => 'view']),
                'sidebar' => [
                    'description' => __('The sidebar to return widgets for.'),
                    'type' => 'string',
                ],
            ];
        }

        public function get_items_permissions_check($request)
        {
            $this->retrieve_widgets();
            if(isset($request['sidebar']) && $this->check_read_sidebar_permission($request['sidebar']))
            {
                return true;
            }

            foreach(wp_get_sidebars_widgets() as $sidebar_id => $widget_ids)
            {
                if($this->check_read_sidebar_permission($sidebar_id))
                {
                    return true;
                }
            }

            return $this->permissions_check($request);
        }

        protected function retrieve_widgets()
        {
            if(! $this->widgets_retrieved)
            {
                retrieve_widgets();
                $this->widgets_retrieved = true;
            }
        }

        protected function check_read_sidebar_permission($sidebar_id)
        {
            $sidebar = wp_get_sidebar($sidebar_id);

            return ! empty($sidebar['show_in_rest']);
        }

        protected function permissions_check($request)
        {
            if(! current_user_can('edit_theme_options'))
            {
                return new WP_Error('rest_cannot_manage_widgets', __('Sorry, you are not allowed to manage widgets on this site.'), [
                    'status' => rest_authorization_required_code(),
                ]);
            }

            return true;
        }

        public function get_items($request)
        {
            $this->retrieve_widgets();

            $prepared = [];
            $permissions_check = $this->permissions_check($request);

            foreach(wp_get_sidebars_widgets() as $sidebar_id => $widget_ids)
            {
                if(isset($request['sidebar']) && $sidebar_id !== $request['sidebar'])
                {
                    continue;
                }

                if(is_wp_error($permissions_check) && ! $this->check_read_sidebar_permission($sidebar_id))
                {
                    continue;
                }

                foreach($widget_ids as $widget_id)
                {
                    $response = $this->prepare_item_for_response(compact('sidebar_id', 'widget_id'), $request);

                    if(! is_wp_error($response))
                    {
                        $prepared[] = $this->prepare_response_for_collection($response);
                    }
                }
            }

            return new WP_REST_Response($prepared);
        }

        public function prepare_item_for_response($item, $request)
        {
            global $wp_widget_factory, $wp_registered_widgets;

            $widget_id = $item['widget_id'];
            $sidebar_id = $item['sidebar_id'];

            if(! isset($wp_registered_widgets[$widget_id]))
            {
                return new WP_Error('rest_invalid_widget', __('The requested widget is invalid.'), ['status' => 500]);
            }

            $widget = $wp_registered_widgets[$widget_id];
            $parsed_id = wp_parse_widget_id($widget_id);
            $fields = $this->get_fields_for_response($request);

            $prepared = [
                'id' => $widget_id,
                'id_base' => $parsed_id['id_base'],
                'sidebar' => $sidebar_id,
                'rendered' => '',
                'rendered_form' => null,
                'instance' => null,
            ];

            if(rest_is_field_included('rendered', $fields) && 'wp_inactive_widgets' !== $sidebar_id)
            {
                $prepared['rendered'] = trim(wp_render_widget($widget_id, $sidebar_id));
            }

            if(rest_is_field_included('rendered_form', $fields))
            {
                $rendered_form = wp_render_widget_control($widget_id);
                if(! is_null($rendered_form))
                {
                    $prepared['rendered_form'] = trim($rendered_form);
                }
            }

            if(rest_is_field_included('instance', $fields))
            {
                $widget_object = $wp_widget_factory->get_widget_object($parsed_id['id_base']);
                if($widget_object && isset($parsed_id['number']))
                {
                    $all_instances = $widget_object->get_settings();
                    $instance = $all_instances[$parsed_id['number']];
                    $serialized_instance = serialize($instance);
                    $prepared['instance']['encoded'] = base64_encode($serialized_instance);
                    $prepared['instance']['hash'] = wp_hash($serialized_instance);

                    if(! empty($widget_object->widget_options['show_instance_in_rest']))
                    {
                        // Use new stdClass so that JSON result is {} and not [].
                        $prepared['instance']['raw'] = empty($instance) ? new stdClass() : $instance;
                    }
                }
            }

            $context = ! empty($request['context']) ? $request['context'] : 'view';
            $prepared = $this->add_additional_fields_to_object($prepared, $request);
            $prepared = $this->filter_response_by_context($prepared, $context);

            $response = rest_ensure_response($prepared);

            if(rest_is_field_included('_links', $fields) || rest_is_field_included('_embedded', $fields))
            {
                $response->add_links($this->prepare_links($prepared));
            }

            return apply_filters('rest_prepare_widget', $response, $widget, $request);
        }

        protected function prepare_links($prepared)
        {
            $id_base = ! empty($prepared['id_base']) ? $prepared['id_base'] : $prepared['id'];

            return [
                'self' => [
                    'href' => rest_url(sprintf('%s/%s/%s', $this->namespace, $this->rest_base, $prepared['id'])),
                ],
                'collection' => [
                    'href' => rest_url(sprintf('%s/%s', $this->namespace, $this->rest_base)),
                ],
                'about' => [
                    'href' => rest_url(sprintf('wp/v2/widget-types/%s', $id_base)),
                    'embeddable' => true,
                ],
                'https://api.w.org/sidebar' => [
                    'href' => rest_url(sprintf('wp/v2/sidebars/%s/', $prepared['sidebar'])),
                ],
            ];
        }

        public function get_item_permissions_check($request)
        {
            $this->retrieve_widgets();

            $widget_id = $request['id'];
            $sidebar_id = wp_find_widgets_sidebar($widget_id);

            if($sidebar_id && $this->check_read_sidebar_permission($sidebar_id))
            {
                return true;
            }

            return $this->permissions_check($request);
        }

        public function get_item($request)
        {
            $this->retrieve_widgets();

            $widget_id = $request['id'];
            $sidebar_id = wp_find_widgets_sidebar($widget_id);

            if(is_null($sidebar_id))
            {
                return new WP_Error('rest_widget_not_found', __('No widget was found with that id.'), ['status' => 404]);
            }

            return $this->prepare_item_for_response(compact('widget_id', 'sidebar_id'), $request);
        }

        public function create_item_permissions_check($request)
        {
            return $this->permissions_check($request);
        }

        public function create_item($request)
        {
            $sidebar_id = $request['sidebar'];

            $widget_id = $this->save_widget($request, $sidebar_id);

            if(is_wp_error($widget_id))
            {
                return $widget_id;
            }

            wp_assign_widget_to_sidebar($widget_id, $sidebar_id);

            $request['context'] = 'edit';

            $response = $this->prepare_item_for_response(compact('sidebar_id', 'widget_id'), $request);

            if(is_wp_error($response))
            {
                return $response;
            }

            $response->set_status(201);

            return $response;
        }

        protected function save_widget($request, $sidebar_id)
        {
            global $wp_widget_factory, $wp_registered_widget_updates;

            require_once ABSPATH.'wp-admin/includes/widgets.php'; // For next_widget_id_number().

            if(isset($request['id']))
            {
                // Saving an existing widget.
                $id = $request['id'];
                $parsed_id = wp_parse_widget_id($id);
                $id_base = $parsed_id['id_base'];
                $number = isset($parsed_id['number']) ? $parsed_id['number'] : null;
                $widget_object = $wp_widget_factory->get_widget_object($id_base);
                $creating = false;
            }
            elseif($request['id_base'])
            {
                // Saving a new widget.
                $id_base = $request['id_base'];
                $widget_object = $wp_widget_factory->get_widget_object($id_base);
                $number = $widget_object ? next_widget_id_number($id_base) : null;
                $id = $widget_object ? $id_base.'-'.$number : $id_base;
                $creating = true;
            }
            else
            {
                return new WP_Error('rest_invalid_widget', __('Widget type (id_base) is required.'), ['status' => 400]);
            }

            if(! isset($wp_registered_widget_updates[$id_base]))
            {
                return new WP_Error('rest_invalid_widget', __('The provided widget type (id_base) cannot be updated.'), ['status' => 400]);
            }

            if(isset($request['instance']))
            {
                if(! $widget_object)
                {
                    return new WP_Error('rest_invalid_widget', __('Cannot set instance on a widget that does not extend WP_Widget.'), ['status' => 400]);
                }

                if(isset($request['instance']['raw']))
                {
                    if(empty($widget_object->widget_options['show_instance_in_rest']))
                    {
                        return new WP_Error('rest_invalid_widget', __('Widget type does not support raw instances.'), ['status' => 400]);
                    }
                    $instance = $request['instance']['raw'];
                }
                elseif(isset($request['instance']['encoded'], $request['instance']['hash']))
                {
                    $serialized_instance = base64_decode($request['instance']['encoded']);
                    if(! hash_equals(wp_hash($serialized_instance), $request['instance']['hash']))
                    {
                        return new WP_Error('rest_invalid_widget', __('The provided instance is malformed.'), ['status' => 400]);
                    }
                    $instance = unserialize($serialized_instance);
                }
                else
                {
                    return new WP_Error('rest_invalid_widget', __('The provided instance is invalid. Must contain raw OR encoded and hash.'), ['status' => 400]);
                }

                $form_data = [
                    "widget-$id_base" => [
                        $number => $instance,
                    ],
                    'sidebar' => $sidebar_id,
                ];
            }
            elseif(isset($request['form_data']))
            {
                $form_data = $request['form_data'];
            }
            else
            {
                $form_data = [];
            }

            $original_post = $_POST;
            $original_request = $_REQUEST;

            foreach($form_data as $key => $value)
            {
                $slashed_value = wp_slash($value);
                $_POST[$key] = $slashed_value;
                $_REQUEST[$key] = $slashed_value;
            }

            $callback = $wp_registered_widget_updates[$id_base]['callback'];
            $params = $wp_registered_widget_updates[$id_base]['params'];

            if(is_callable($callback))
            {
                ob_start();
                call_user_func_array($callback, $params);
                ob_end_clean();
            }

            $_POST = $original_post;
            $_REQUEST = $original_request;

            if($widget_object)
            {
                // Register any multi-widget that the update callback just created.
                $widget_object->_set($number);
                $widget_object->_register_one($number);

                /*
                 * WP_Widget sets `updated = true` after an update to prevent more than one widget
                 * from being saved per request. This isn't what we want in the REST API, though,
                 * as we support batch requests.
                 */
                $widget_object->updated = false;
            }

            do_action('rest_after_save_widget', $id, $sidebar_id, $request, $creating);

            return $id;
        }

        public function update_item_permissions_check($request)
        {
            return $this->permissions_check($request);
        }

        public function update_item($request)
        {
            global $wp_widget_factory;

            /*
             * retrieve_widgets() contains logic to move "hidden" or "lost" widgets to the
             * wp_inactive_widgets sidebar based on the contents of the $sidebars_widgets global.
             *
             * When batch requests are processed, this global is not properly updated by previous
             * calls, resulting in widgets incorrectly being moved to the wp_inactive_widgets
             * sidebar.
             *
             * See https://core.trac.wordpress.org/ticket/53657.
             */
            wp_get_sidebars_widgets();
            $this->retrieve_widgets();

            $widget_id = $request['id'];
            $sidebar_id = wp_find_widgets_sidebar($widget_id);

            // Allow sidebar to be unset or missing when widget is not a WP_Widget.
            $parsed_id = wp_parse_widget_id($widget_id);
            $widget_object = $wp_widget_factory->get_widget_object($parsed_id['id_base']);
            if(is_null($sidebar_id) && $widget_object)
            {
                return new WP_Error('rest_widget_not_found', __('No widget was found with that id.'), ['status' => 404]);
            }

            if($request->has_param('instance') || $request->has_param('form_data'))
            {
                $maybe_error = $this->save_widget($request, $sidebar_id);
                if(is_wp_error($maybe_error))
                {
                    return $maybe_error;
                }
            }

            if($request->has_param('sidebar'))
            {
                if($sidebar_id !== $request['sidebar'])
                {
                    $sidebar_id = $request['sidebar'];
                    wp_assign_widget_to_sidebar($widget_id, $sidebar_id);
                }
            }

            $request['context'] = 'edit';

            return $this->prepare_item_for_response(compact('widget_id', 'sidebar_id'), $request);
        }

        public function delete_item_permissions_check($request)
        {
            return $this->permissions_check($request);
        }

        public function delete_item($request)
        {
            global $wp_widget_factory, $wp_registered_widget_updates;

            /*
             * retrieve_widgets() contains logic to move "hidden" or "lost" widgets to the
             * wp_inactive_widgets sidebar based on the contents of the $sidebars_widgets global.
             *
             * When batch requests are processed, this global is not properly updated by previous
             * calls, resulting in widgets incorrectly being moved to the wp_inactive_widgets
             * sidebar.
             *
             * See https://core.trac.wordpress.org/ticket/53657.
             */
            wp_get_sidebars_widgets();
            $this->retrieve_widgets();

            $widget_id = $request['id'];
            $sidebar_id = wp_find_widgets_sidebar($widget_id);

            if(is_null($sidebar_id))
            {
                return new WP_Error('rest_widget_not_found', __('No widget was found with that id.'), ['status' => 404]);
            }

            $request['context'] = 'edit';

            if($request['force'])
            {
                $response = $this->prepare_item_for_response(compact('widget_id', 'sidebar_id'), $request);

                $parsed_id = wp_parse_widget_id($widget_id);
                $id_base = $parsed_id['id_base'];

                $original_post = $_POST;
                $original_request = $_REQUEST;

                $_POST = [
                    'sidebar' => $sidebar_id,
                    "widget-$id_base" => [],
                    'the-widget-id' => $widget_id,
                    'delete_widget' => '1',
                ];
                $_REQUEST = $_POST;

                do_action('delete_widget', $widget_id, $sidebar_id, $id_base);

                $callback = $wp_registered_widget_updates[$id_base]['callback'];
                $params = $wp_registered_widget_updates[$id_base]['params'];

                if(is_callable($callback))
                {
                    ob_start();
                    call_user_func_array($callback, $params);
                    ob_end_clean();
                }

                $_POST = $original_post;
                $_REQUEST = $original_request;

                $widget_object = $wp_widget_factory->get_widget_object($id_base);

                if($widget_object)
                {
                    /*
                     * WP_Widget sets `updated = true` after an update to prevent more than one widget
                     * from being saved per request. This isn't what we want in the REST API, though,
                     * as we support batch requests.
                     */
                    $widget_object->updated = false;
                }

                wp_assign_widget_to_sidebar($widget_id, '');

                $response->set_data([
                                        'deleted' => true,
                                        'previous' => $response->get_data(),
                                    ]);
            }
            else
            {
                wp_assign_widget_to_sidebar($widget_id, 'wp_inactive_widgets');

                $response = $this->prepare_item_for_response([
                                                                 'sidebar_id' => 'wp_inactive_widgets',
                                                                 'widget_id' => $widget_id,
                                                             ], $request);
            }

            do_action('rest_delete_widget', $widget_id, $sidebar_id, $response, $request);

            return $response;
        }

        public function get_item_schema()
        {
            if($this->schema)
            {
                return $this->add_additional_fields_schema($this->schema);
            }

            $this->schema = [
                '$schema' => 'http://json-schema.org/draft-04/schema#',
                'title' => 'widget',
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'description' => __('Unique identifier for the widget.'),
                        'type' => 'string',
                        'context' => ['view', 'edit', 'embed'],
                    ],
                    'id_base' => [
                        'description' => __('The type of the widget. Corresponds to ID in widget-types endpoint.'),
                        'type' => 'string',
                        'context' => ['view', 'edit', 'embed'],
                    ],
                    'sidebar' => [
                        'description' => __('The sidebar the widget belongs to.'),
                        'type' => 'string',
                        'default' => 'wp_inactive_widgets',
                        'required' => true,
                        'context' => ['view', 'edit', 'embed'],
                    ],
                    'rendered' => [
                        'description' => __('HTML representation of the widget.'),
                        'type' => 'string',
                        'context' => ['view', 'edit', 'embed'],
                        'readonly' => true,
                    ],
                    'rendered_form' => [
                        'description' => __('HTML representation of the widget admin form.'),
                        'type' => 'string',
                        'context' => ['edit'],
                        'readonly' => true,
                    ],
                    'instance' => [
                        'description' => __('Instance settings of the widget, if supported.'),
                        'type' => 'object',
                        'context' => ['edit'],
                        'default' => null,
                        'properties' => [
                            'encoded' => [
                                'description' => __('Base64 encoded representation of the instance settings.'),
                                'type' => 'string',
                                'context' => ['edit'],
                            ],
                            'hash' => [
                                'description' => __('Cryptographic hash of the instance settings.'),
                                'type' => 'string',
                                'context' => ['edit'],
                            ],
                            'raw' => [
                                'description' => __('Unencoded instance settings, if supported.'),
                                'type' => 'object',
                                'context' => ['edit'],
                            ],
                        ],
                    ],
                    'form_data' => [
                        'description' => __('URL-encoded form data from the widget admin form. Used to update a widget that does not support instance. Write only.'),
                        'type' => 'string',
                        'context' => [],
                        'arg_options' => [
                            'sanitize_callback' => static function($form_data)
                            {
                                $array = [];
                                wp_parse_str($form_data, $array);

                                return $array;
                            },
                        ],
                    ],
                ],
            ];

            return $this->add_additional_fields_schema($this->schema);
        }
    }
