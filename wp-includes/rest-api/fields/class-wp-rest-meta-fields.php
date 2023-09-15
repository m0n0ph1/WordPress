<?php

    #[AllowDynamicProperties]
    abstract class WP_REST_Meta_Fields
    {
        public static function prepare_value($value, $request, $args)
        {
            if($args['single'])
            {
                $schema = $args['schema'];
            }
            else
            {
                $schema = $args['schema']['items'];
            }

            if('' === $value && in_array($schema['type'], ['boolean', 'integer', 'number'], true))
            {
                $value = static::get_empty_value_for_type($schema['type']);
            }

            if(is_wp_error(rest_validate_value_from_schema($value, $schema)))
            {
                return null;
            }

            return rest_sanitize_value_from_schema($value, $schema);
        }

        protected static function get_empty_value_for_type($type)
        {
            switch($type)
            {
                case 'string':
                    return '';
                case 'boolean':
                    return false;
                case 'integer':
                    return 0;
                case 'number':
                    return 0.0;
                case 'array':
                case 'object':
                    return [];
                default:
                    return null;
            }
        }

        public function register_field()
        {
            _deprecated_function(__METHOD__, '5.6.0');

            register_rest_field($this->get_rest_field_type(), 'meta', [
                'get_callback' => [$this, 'get_value'],
                'update_callback' => [$this, 'update_value'],
                'schema' => $this->get_field_schema(),
            ]);
        }

        abstract protected function get_rest_field_type();

        public function get_field_schema()
        {
            $fields = $this->get_registered_fields();

            $schema = [
                'description' => __('Meta fields.'),
                'type' => 'object',
                'context' => ['view', 'edit'],
                'properties' => [],
                'arg_options' => [
                    'sanitize_callback' => null,
                    'validate_callback' => [$this, 'check_meta_is_array'],
                ],
            ];

            foreach($fields as $args)
            {
                $schema['properties'][$args['name']] = $args['schema'];
            }

            return $schema;
        }

        protected function get_registered_fields()
        {
            $registered = [];

            $meta_type = $this->get_meta_type();
            $meta_subtype = $this->get_meta_subtype();

            $meta_keys = get_registered_meta_keys($meta_type);
            if(! empty($meta_subtype))
            {
                $meta_keys = array_merge($meta_keys, get_registered_meta_keys($meta_type, $meta_subtype));
            }

            foreach($meta_keys as $name => $args)
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

                $default_args = [
                    'name' => $name,
                    'single' => $args['single'],
                    'type' => ! empty($args['type']) ? $args['type'] : null,
                    'schema' => [],
                    'prepare_callback' => [$this, 'prepare_value'],
                ];

                $default_schema = [
                    'type' => $default_args['type'],
                    'description' => empty($args['description']) ? '' : $args['description'],
                    'default' => isset($args['default']) ? $args['default'] : null,
                ];

                $rest_args = array_merge($default_args, $rest_args);
                $rest_args['schema'] = array_merge($default_schema, $rest_args['schema']);

                $type = ! empty($rest_args['type']) ? $rest_args['type'] : null;
                $type = ! empty($rest_args['schema']['type']) ? $rest_args['schema']['type'] : $type;

                if(null === $rest_args['schema']['default'])
                {
                    $rest_args['schema']['default'] = static::get_empty_value_for_type($type);
                }

                $rest_args['schema'] = rest_default_additional_properties_to_false($rest_args['schema']);

                if(! in_array($type, ['string', 'boolean', 'integer', 'number', 'array', 'object'], true))
                {
                    continue;
                }

                if(empty($rest_args['single']))
                {
                    $rest_args['schema'] = [
                        'type' => 'array',
                        'items' => $rest_args['schema'],
                    ];
                }

                $registered[$name] = $rest_args;
            }

            return $registered;
        }

        abstract protected function get_meta_type();

        protected function get_meta_subtype()
        {
            return '';
        }

        public function get_value($object_id, $request)
        {
            $fields = $this->get_registered_fields();
            $response = [];

            foreach($fields as $meta_key => $args)
            {
                $name = $args['name'];
                $all_values = get_metadata($this->get_meta_type(), $object_id, $meta_key, false);

                if($args['single'])
                {
                    if(empty($all_values))
                    {
                        $value = $args['schema']['default'];
                    }
                    else
                    {
                        $value = $all_values[0];
                    }

                    $value = $this->prepare_value_for_response($value, $request, $args);
                }
                else
                {
                    $value = [];

                    if(is_array($all_values))
                    {
                        foreach($all_values as $row)
                        {
                            $value[] = $this->prepare_value_for_response($row, $request, $args);
                        }
                    }
                }

                $response[$name] = $value;
            }

            return $response;
        }

        protected function prepare_value_for_response($value, $request, $args)
        {
            if(! empty($args['prepare_callback']))
            {
                $value = call_user_func($args['prepare_callback'], $value, $request, $args);
            }

            return $value;
        }

        public function update_value($meta, $object_id)
        {
            $fields = $this->get_registered_fields();

            foreach($fields as $meta_key => $args)
            {
                $name = $args['name'];
                if(! array_key_exists($name, $meta))
                {
                    continue;
                }

                $value = $meta[$name];

                /*
                 * A null value means reset the field, which is essentially deleting it
                 * from the database and then relying on the default value.
                 *
                 * Non-single meta can also be removed by passing an empty array.
                 */
                if(is_null($value) || ([] === $value && ! $args['single']))
                {
                    $args = $this->get_registered_fields()[$meta_key];

                    if($args['single'])
                    {
                        $current = get_metadata($this->get_meta_type(), $object_id, $meta_key, true);

                        if(is_wp_error(rest_validate_value_from_schema($current, $args['schema'])))
                        {
                            return new WP_Error('rest_invalid_stored_value', /* translators: %s: Custom field key. */ sprintf(__('The %s property has an invalid stored value, and cannot be updated to null.'), $name), ['status' => 500]);
                        }
                    }

                    $result = $this->delete_meta_value($object_id, $meta_key, $name);
                    if(is_wp_error($result))
                    {
                        return $result;
                    }
                    continue;
                }

                if(! $args['single'] && is_array($value) && count(array_filter($value, 'is_null')))
                {
                    return new WP_Error('rest_invalid_stored_value', /* translators: %s: Custom field key. */ sprintf(__('The %s property has an invalid stored value, and cannot be updated to null.'), $name), ['status' => 500]);
                }

                $is_valid = rest_validate_value_from_schema($value, $args['schema'], 'meta.'.$name);
                if(is_wp_error($is_valid))
                {
                    $is_valid->add_data(['status' => 400]);

                    return $is_valid;
                }

                $value = rest_sanitize_value_from_schema($value, $args['schema']);

                if($args['single'])
                {
                    $result = $this->update_meta_value($object_id, $meta_key, $name, $value);
                }
                else
                {
                    $result = $this->update_multi_meta_value($object_id, $meta_key, $name, $value);
                }

                if(is_wp_error($result))
                {
                    return $result;
                }
            }

            return null;
        }

        protected function delete_meta_value($object_id, $meta_key, $name)
        {
            $meta_type = $this->get_meta_type();

            if(! current_user_can("delete_{$meta_type}_meta", $object_id, $meta_key))
            {
                return new WP_Error('rest_cannot_delete', /* translators: %s: Custom field key. */ sprintf(__('Sorry, you are not allowed to edit the %s custom field.'), $name), [
                    'key' => $name,
                    'status' => rest_authorization_required_code(),
                ]);
            }

            if(null === get_metadata_raw($meta_type, $object_id, wp_slash($meta_key)))
            {
                return true;
            }

            if(! delete_metadata($meta_type, $object_id, wp_slash($meta_key)))
            {
                return new WP_Error('rest_meta_database_error', __('Could not delete meta value from database.'), [
                    'key' => $name,
                    'status' => WP_Http::INTERNAL_SERVER_ERROR,
                ]);
            }

            return true;
        }

        protected function update_meta_value($object_id, $meta_key, $name, $value)
        {
            $meta_type = $this->get_meta_type();

            // Do the exact same check for a duplicate value as in update_metadata() to avoid update_metadata() returning false.
            $old_value = get_metadata($meta_type, $object_id, $meta_key);
            $subtype = get_object_subtype($meta_type, $object_id);

            if(is_array($old_value) && 1 === count($old_value) && $this->is_meta_value_same_as_stored_value($meta_key, $subtype, $old_value[0], $value))
            {
                return true;
            }

            if(! current_user_can("edit_{$meta_type}_meta", $object_id, $meta_key))
            {
                return new WP_Error('rest_cannot_update', /* translators: %s: Custom field key. */ sprintf(__('Sorry, you are not allowed to edit the %s custom field.'), $name), [
                    'key' => $name,
                    'status' => rest_authorization_required_code(),
                ]);
            }

            if(! update_metadata($meta_type, $object_id, wp_slash($meta_key), wp_slash($value)))
            {
                return new WP_Error('rest_meta_database_error', /* translators: %s: Custom field key. */ sprintf(__('Could not update the meta value of %s in database.'), $meta_key), [
                    'key' => $name,
                    'status' => WP_Http::INTERNAL_SERVER_ERROR,
                ]);
            }

            return true;
        }

        protected function is_meta_value_same_as_stored_value($meta_key, $subtype, $stored_value, $user_value)
        {
            $args = $this->get_registered_fields()[$meta_key];
            $sanitized = sanitize_meta($meta_key, $user_value, $this->get_meta_type(), $subtype);

            if(in_array($args['type'], ['string', 'number', 'integer', 'boolean'], true))
            {
                // The return value of get_metadata will always be a string for scalar types.
                $sanitized = (string) $sanitized;
            }

            return $sanitized === $stored_value;
        }

        protected function update_multi_meta_value($object_id, $meta_key, $name, $values)
        {
            $meta_type = $this->get_meta_type();

            if(! current_user_can("edit_{$meta_type}_meta", $object_id, $meta_key))
            {
                return new WP_Error('rest_cannot_update', /* translators: %s: Custom field key. */ sprintf(__('Sorry, you are not allowed to edit the %s custom field.'), $name), [
                    'key' => $name,
                    'status' => rest_authorization_required_code(),
                ]);
            }

            $current_values = get_metadata($meta_type, $object_id, $meta_key, false);
            $subtype = get_object_subtype($meta_type, $object_id);

            if(! is_array($current_values))
            {
                $current_values = [];
            }

            $to_remove = $current_values;
            $to_add = $values;

            foreach($to_add as $add_key => $value)
            {
                $remove_keys = array_keys(
                    array_filter($current_values, function($stored_value) use (
                        $meta_key, $subtype, $value
                    )
                    {
                        return $this->is_meta_value_same_as_stored_value($meta_key, $subtype, $stored_value, $value);
                    })
                );

                if(empty($remove_keys))
                {
                    continue;
                }

                if(count($remove_keys) > 1)
                {
                    // To remove, we need to remove first, then add, so don't touch.
                    continue;
                }

                $remove_key = $remove_keys[0];

                unset($to_remove[$remove_key]);
                unset($to_add[$add_key]);
            }

            /*
             * `delete_metadata` removes _all_ instances of the value, so only call once. Otherwise,
             * `delete_metadata` will return false for subsequent calls of the same value.
             * Use serialization to produce a predictable string that can be used by array_unique.
             */
            $to_remove = array_map('maybe_unserialize', array_unique(array_map('maybe_serialize', $to_remove)));

            foreach($to_remove as $value)
            {
                if(! delete_metadata($meta_type, $object_id, wp_slash($meta_key), wp_slash($value)))
                {
                    return new WP_Error('rest_meta_database_error', /* translators: %s: Custom field key. */ sprintf(__('Could not update the meta value of %s in database.'), $meta_key), [
                        'key' => $name,
                        'status' => WP_Http::INTERNAL_SERVER_ERROR,
                    ]);
                }
            }

            foreach($to_add as $value)
            {
                if(! add_metadata($meta_type, $object_id, wp_slash($meta_key), wp_slash($value)))
                {
                    return new WP_Error('rest_meta_database_error', /* translators: %s: Custom field key. */ sprintf(__('Could not update the meta value of %s in database.'), $meta_key), [
                        'key' => $name,
                        'status' => WP_Http::INTERNAL_SERVER_ERROR,
                    ]);
                }
            }

            return true;
        }

        public function check_meta_is_array($value, $request, $param)
        {
            if(! is_array($value))
            {
                return false;
            }

            return $value;
        }

        protected function default_additional_properties_to_false($schema)
        {
            _deprecated_function(__METHOD__, '5.6.0', 'rest_default_additional_properties_to_false()');

            return rest_default_additional_properties_to_false($schema);
        }
    }
