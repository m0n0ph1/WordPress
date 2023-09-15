<?php

    class WP_REST_Settings_Controller extends WP_REST_Controller
    {
        public function __construct()
        {
            $this->namespace = 'wp/v2';
            $this->rest_base = 'settings';
        }

        public function register_routes()
        {
            parent::register_routes();
            register_rest_route($this->namespace, '/'.$this->rest_base, [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_item'],
                    'args' => [],
                    'permission_callback' => [$this, 'get_item_permissions_check'],
                ],
                [
                    'methods' => WP_REST_Server::EDITABLE,
                    'callback' => [$this, 'update_item'],
                    'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE),
                    'permission_callback' => [$this, 'get_item_permissions_check'],
                ],
                'schema' => [$this, 'get_public_item_schema'],
            ]);
        }

        public function get_item_permissions_check($request)
        {
            return current_user_can('manage_options');
        }

        public function update_item($request)
        {
            $options = $this->get_registered_options();

            $params = $request->get_params();

            foreach($options as $name => $args)
            {
                if(! array_key_exists($name, $params))
                {
                    continue;
                }

                $updated = apply_filters('rest_pre_update_setting', false, $name, $request[$name], $args);

                if($updated)
                {
                    continue;
                }

                /*
                 * A null value for an option would have the same effect as
                 * deleting the option from the database, and relying on the
                 * default value.
                 */
                if(is_null($request[$name]))
                {
                    /*
                     * A null value is returned in the response for any option
                     * that has a non-scalar value.
                     *
                     * To protect clients from accidentally including the null
                     * values from a response object in a request, we do not allow
                     * options with values that don't pass validation to be updated to null.
                     * Without this added protection a client could mistakenly
                     * delete all options that have invalid values from the
                     * database.
                     */
                    if(is_wp_error(rest_validate_value_from_schema(get_option($args['option_name'], false), $args['schema'])))
                    {
                        return new WP_Error('rest_invalid_stored_value', /* translators: %s: Property name. */ sprintf(__('The %s property has an invalid stored value, and cannot be updated to null.'), $name), ['status' => 500]);
                    }

                    delete_option($args['option_name']);
                }
                else
                {
                    update_option($args['option_name'], $request[$name]);
                }
            }

            return $this->get_item($request);
        }

        protected function get_registered_options()
        {
            $rest_options = [];

            foreach(get_registered_settings() as $name => $args)
            {
                if(empty($args['show_in_rest']))
                {
                    continue;
                }

                $rest_args = [];

                if(is_array($args['show_in_rest']))
                {
                    $rest_args = $args['show_in_rest'];
                }

                $defaults = [
                    'name' => ! empty($rest_args['name']) ? $rest_args['name'] : $name,
                    'schema' => [],
                ];

                $rest_args = array_merge($defaults, $rest_args);

                $default_schema = [
                    'type' => empty($args['type']) ? null : $args['type'],
                    'description' => empty($args['description']) ? '' : $args['description'],
                    'default' => isset($args['default']) ? $args['default'] : null,
                ];

                $rest_args['schema'] = array_merge($default_schema, $rest_args['schema']);
                $rest_args['option_name'] = $name;

                // Skip over settings that don't have a defined type in the schema.
                if(empty($rest_args['schema']['type']))
                {
                    continue;
                }

                /*
                 * Allow the supported types for settings, as we don't want invalid types
                 * to be updated with arbitrary values that we can't do decent sanitizing for.
                 */
                if(
                    ! in_array($rest_args['schema']['type'], [
                        'number',
                        'integer',
                        'string',
                        'boolean',
                        'array',
                        'object'
                    ],         true)
                )
                {
                    continue;
                }

                $rest_args['schema'] = rest_default_additional_properties_to_false($rest_args['schema']);

                $rest_options[$rest_args['name']] = $rest_args;
            }

            return $rest_options;
        }

        public function get_item($request)
        {
            $options = $this->get_registered_options();
            $response = [];

            foreach($options as $name => $args)
            {
                $response[$name] = apply_filters('rest_pre_get_setting', null, $name, $args);

                if(is_null($response[$name]))
                {
                    // Default to a null value as "null" in the response means "not set".
                    $response[$name] = get_option($args['option_name'], $args['schema']['default']);
                }

                /*
                 * Because get_option() is lossy, we have to
                 * cast values to the type they are registered with.
                 */
                $response[$name] = $this->prepare_value($response[$name], $args['schema']);
            }

            return $response;
        }

        protected function prepare_value($value, $schema)
        {
            /*
             * If the value is not valid by the schema, set the value to null.
             * Null values are specifically non-destructive, so this will not cause
             * overwriting the current invalid value to null.
             */
            if(is_wp_error(rest_validate_value_from_schema($value, $schema)))
            {
                return null;
            }

            return rest_sanitize_value_from_schema($value, $schema);
        }

        public function get_item_schema()
        {
            if($this->schema)
            {
                return $this->add_additional_fields_schema($this->schema);
            }

            $options = $this->get_registered_options();

            $schema = [
                '$schema' => 'http://json-schema.org/draft-04/schema#',
                'title' => 'settings',
                'type' => 'object',
                'properties' => [],
            ];

            foreach($options as $option_name => $option)
            {
                $schema['properties'][$option_name] = $option['schema'];
                $schema['properties'][$option_name]['arg_options'] = [
                    'sanitize_callback' => [$this, 'sanitize_callback'],
                ];
            }

            $this->schema = $schema;

            return $this->add_additional_fields_schema($this->schema);
        }

        public function sanitize_callback($value, $request, $param)
        {
            if(is_null($value))
            {
                return $value;
            }

            return rest_parse_request_arg($value, $request, $param);
        }

        protected function set_additional_properties_to_false($schema)
        {
            _deprecated_function(__METHOD__, '6.1.0', 'rest_default_additional_properties_to_false()');

            return rest_default_additional_properties_to_false($schema);
        }
    }
