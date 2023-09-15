<?php

    class WP_REST_Application_Passwords_Controller extends WP_REST_Controller
    {
        public function __construct()
        {
            $this->namespace = 'wp/v2';
            $this->rest_base = 'users/(?P<user_id>(?:[\d]+|me))/application-passwords';
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
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'create_item'],
                    'permission_callback' => [$this, 'create_item_permissions_check'],
                    'args' => $this->get_endpoint_args_for_item_schema(),
                ],
                [
                    'methods' => WP_REST_Server::DELETABLE,
                    'callback' => [$this, 'delete_items'],
                    'permission_callback' => [$this, 'delete_items_permissions_check'],
                ],
                'schema' => [$this, 'get_public_item_schema'],
            ]);

            register_rest_route($this->namespace, '/'.$this->rest_base.'/introspect', [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_current_item'],
                    'permission_callback' => [$this, 'get_current_item_permissions_check'],
                    'args' => [
                        'context' => $this->get_context_param(['default' => 'view']),
                    ],
                ],
                'schema' => [$this, 'get_public_item_schema'],
            ]);

            register_rest_route($this->namespace, '/'.$this->rest_base.'/(?P<uuid>[\w\-]+)', [
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
            $user = $this->get_user($request);

            if(is_wp_error($user))
            {
                return $user;
            }

            if(! current_user_can('list_app_passwords', $user->ID))
            {
                return new WP_Error('rest_cannot_list_application_passwords', __('Sorry, you are not allowed to list application passwords for this user.'), ['status' => rest_authorization_required_code()]);
            }

            return true;
        }

        protected function get_user($request)
        {
            if(! wp_is_application_passwords_available())
            {
                return new WP_Error('application_passwords_disabled', __('Application passwords are not available.'), ['status' => 501]);
            }

            $error = new WP_Error('rest_user_invalid_id', __('Invalid user ID.'), ['status' => 404]);

            $id = $request['user_id'];

            if('me' === $id)
            {
                if(! is_user_logged_in())
                {
                    return new WP_Error('rest_not_logged_in', __('You are not currently logged in.'), ['status' => 401]);
                }

                $user = wp_get_current_user();
            }
            else
            {
                $id = (int) $id;

                if($id <= 0)
                {
                    return $error;
                }

                $user = get_userdata($id);
            }

            if(empty($user) || ! $user->exists())
            {
                return $error;
            }

            if(is_multisite() && ! user_can($user->ID, 'manage_sites') && ! is_user_member_of_blog($user->ID))
            {
                return $error;
            }

            if(! wp_is_application_passwords_available_for_user($user))
            {
                return new WP_Error('application_passwords_disabled_for_user', __('Application passwords are not available for your account. Please contact the site administrator for assistance.'), ['status' => 501]);
            }

            return $user;
        }

        public function get_items($request)
        {
            $user = $this->get_user($request);

            if(is_wp_error($user))
            {
                return $user;
            }

            $passwords = WP_Application_Passwords::get_user_application_passwords($user->ID);
            $response = [];

            foreach($passwords as $password)
            {
                $response[] = $this->prepare_response_for_collection($this->prepare_item_for_response($password, $request));
            }

            return new WP_REST_Response($response);
        }

        public function prepare_item_for_response($item, $request)
        {
            $user = $this->get_user($request);

            if(is_wp_error($user))
            {
                return $user;
            }

            $fields = $this->get_fields_for_response($request);

            $prepared = [
                'uuid' => $item['uuid'],
                'app_id' => empty($item['app_id']) ? '' : $item['app_id'],
                'name' => $item['name'],
                'created' => gmdate('Y-m-d\TH:i:s', $item['created']),
                'last_used' => $item['last_used'] ? gmdate('Y-m-d\TH:i:s', $item['last_used']) : null,
                'last_ip' => $item['last_ip'] ? $item['last_ip'] : null,
            ];

            if(isset($item['new_password']))
            {
                $prepared['password'] = $item['new_password'];
            }

            $prepared = $this->add_additional_fields_to_object($prepared, $request);
            $prepared = $this->filter_response_by_context($prepared, $request['context']);

            $response = new WP_REST_Response($prepared);

            if(rest_is_field_included('_links', $fields) || rest_is_field_included('_embedded', $fields))
            {
                $response->add_links($this->prepare_links($user, $item));
            }

            return apply_filters('rest_prepare_application_password', $response, $item, $request);
        }

        protected function prepare_links(WP_User $user, $item)
        {
            return [
                'self' => [
                    'href' => rest_url(sprintf('%s/users/%d/application-passwords/%s', $this->namespace, $user->ID, $item['uuid'])),
                ],
            ];
        }

        public function get_item_permissions_check($request)
        {
            $user = $this->get_user($request);

            if(is_wp_error($user))
            {
                return $user;
            }

            if(! current_user_can('read_app_password', $user->ID, $request['uuid']))
            {
                return new WP_Error('rest_cannot_read_application_password', __('Sorry, you are not allowed to read this application password.'), ['status' => rest_authorization_required_code()]);
            }

            return true;
        }

        public function get_item($request)
        {
            $password = $this->get_application_password($request);

            if(is_wp_error($password))
            {
                return $password;
            }

            return $this->prepare_item_for_response($password, $request);
        }

        protected function get_application_password($request)
        {
            $user = $this->get_user($request);

            if(is_wp_error($user))
            {
                return $user;
            }

            $password = WP_Application_Passwords::get_user_application_password($user->ID, $request['uuid']);

            if(! $password)
            {
                return new WP_Error('rest_application_password_not_found', __('Application password not found.'), ['status' => 404]);
            }

            return $password;
        }

        public function create_item_permissions_check($request)
        {
            $user = $this->get_user($request);

            if(is_wp_error($user))
            {
                return $user;
            }

            if(! current_user_can('create_app_password', $user->ID))
            {
                return new WP_Error('rest_cannot_create_application_passwords', __('Sorry, you are not allowed to create application passwords for this user.'), ['status' => rest_authorization_required_code()]);
            }

            return true;
        }

        public function create_item($request)
        {
            $user = $this->get_user($request);

            if(is_wp_error($user))
            {
                return $user;
            }

            $prepared = $this->prepare_item_for_database($request);

            if(is_wp_error($prepared))
            {
                return $prepared;
            }

            $created = WP_Application_Passwords::create_new_application_password($user->ID, wp_slash((array) $prepared));

            if(is_wp_error($created))
            {
                return $created;
            }

            $password = $created[0];
            $item = WP_Application_Passwords::get_user_application_password($user->ID, $created[1]['uuid']);

            $item['new_password'] = WP_Application_Passwords::chunk_password($password);
            $fields_update = $this->update_additional_fields_for_object($item, $request);

            if(is_wp_error($fields_update))
            {
                return $fields_update;
            }

            do_action('rest_after_insert_application_password', $item, $request, true);

            $request->set_param('context', 'edit');
            $response = $this->prepare_item_for_response($item, $request);

            $response->set_status(201);
            $response->header('Location', $response->get_links()['self'][0]['href']);

            return $response;
        }

        protected function prepare_item_for_database($request)
        {
            $prepared = (object) [
                'name' => $request['name'],
            ];

            if($request['app_id'] && ! $request['uuid'])
            {
                $prepared->app_id = $request['app_id'];
            }

            return apply_filters('rest_pre_insert_application_password', $prepared, $request);
        }

        public function update_item_permissions_check($request)
        {
            $user = $this->get_user($request);

            if(is_wp_error($user))
            {
                return $user;
            }

            if(! current_user_can('edit_app_password', $user->ID, $request['uuid']))
            {
                return new WP_Error('rest_cannot_edit_application_password', __('Sorry, you are not allowed to edit this application password.'), ['status' => rest_authorization_required_code()]);
            }

            return true;
        }

        public function update_item($request)
        {
            $user = $this->get_user($request);

            if(is_wp_error($user))
            {
                return $user;
            }

            $item = $this->get_application_password($request);

            if(is_wp_error($item))
            {
                return $item;
            }

            $prepared = $this->prepare_item_for_database($request);

            if(is_wp_error($prepared))
            {
                return $prepared;
            }

            $saved = WP_Application_Passwords::update_application_password($user->ID, $item['uuid'], wp_slash((array) $prepared));

            if(is_wp_error($saved))
            {
                return $saved;
            }

            $fields_update = $this->update_additional_fields_for_object($item, $request);

            if(is_wp_error($fields_update))
            {
                return $fields_update;
            }

            $item = WP_Application_Passwords::get_user_application_password($user->ID, $item['uuid']);

            do_action('rest_after_insert_application_password', $item, $request, false);

            $request->set_param('context', 'edit');

            return $this->prepare_item_for_response($item, $request);
        }

        public function delete_items_permissions_check($request)
        {
            $user = $this->get_user($request);

            if(is_wp_error($user))
            {
                return $user;
            }

            if(! current_user_can('delete_app_passwords', $user->ID))
            {
                return new WP_Error('rest_cannot_delete_application_passwords', __('Sorry, you are not allowed to delete application passwords for this user.'), ['status' => rest_authorization_required_code()]);
            }

            return true;
        }

        public function delete_items($request)
        {
            $user = $this->get_user($request);

            if(is_wp_error($user))
            {
                return $user;
            }

            $deleted = WP_Application_Passwords::delete_all_application_passwords($user->ID);

            if(is_wp_error($deleted))
            {
                return $deleted;
            }

            return new WP_REST_Response([
                                            'deleted' => true,
                                            'count' => $deleted,
                                        ]);
        }

        public function delete_item_permissions_check($request)
        {
            $user = $this->get_user($request);

            if(is_wp_error($user))
            {
                return $user;
            }

            if(! current_user_can('delete_app_password', $user->ID, $request['uuid']))
            {
                return new WP_Error('rest_cannot_delete_application_password', __('Sorry, you are not allowed to delete this application password.'), ['status' => rest_authorization_required_code()]);
            }

            return true;
        }

        public function delete_item($request)
        {
            $user = $this->get_user($request);

            if(is_wp_error($user))
            {
                return $user;
            }

            $password = $this->get_application_password($request);

            if(is_wp_error($password))
            {
                return $password;
            }

            $request->set_param('context', 'edit');
            $previous = $this->prepare_item_for_response($password, $request);
            $deleted = WP_Application_Passwords::delete_application_password($user->ID, $password['uuid']);

            if(is_wp_error($deleted))
            {
                return $deleted;
            }

            return new WP_REST_Response([
                                            'deleted' => true,
                                            'previous' => $previous->get_data(),
                                        ]);
        }

        public function get_current_item_permissions_check($request)
        {
            $user = $this->get_user($request);

            if(is_wp_error($user))
            {
                return $user;
            }

            if(get_current_user_id() !== $user->ID)
            {
                return new WP_Error('rest_cannot_introspect_app_password_for_non_authenticated_user', __('The authenticated application password can only be introspected for the current user.'), ['status' => rest_authorization_required_code()]);
            }

            return true;
        }

        public function get_current_item($request)
        {
            $user = $this->get_user($request);

            if(is_wp_error($user))
            {
                return $user;
            }

            $uuid = rest_get_authenticated_app_password();

            if(! $uuid)
            {
                return new WP_Error('rest_no_authenticated_app_password', __('Cannot introspect application password.'), ['status' => 404]);
            }

            $password = WP_Application_Passwords::get_user_application_password($user->ID, $uuid);

            if(! $password)
            {
                return new WP_Error('rest_application_password_not_found', __('Application password not found.'), ['status' => 500]);
            }

            return $this->prepare_item_for_response($password, $request);
        }

        public function get_item_schema()
        {
            if($this->schema)
            {
                return $this->add_additional_fields_schema($this->schema);
            }

            $this->schema = [
                '$schema' => 'http://json-schema.org/draft-04/schema#',
                'title' => 'application-password',
                'type' => 'object',
                'properties' => [
                    'uuid' => [
                        'description' => __('The unique identifier for the application password.'),
                        'type' => 'string',
                        'format' => 'uuid',
                        'context' => ['view', 'edit', 'embed'],
                        'readonly' => true,
                    ],
                    'app_id' => [
                        'description' => __('A UUID provided by the application to uniquely identify it. It is recommended to use an UUID v5 with the URL or DNS namespace.'),
                        'type' => 'string',
                        'format' => 'uuid',
                        'context' => ['view', 'edit', 'embed'],
                    ],
                    'name' => [
                        'description' => __('The name of the application password.'),
                        'type' => 'string',
                        'required' => true,
                        'context' => ['view', 'edit', 'embed'],
                        'minLength' => 1,
                        'pattern' => '.*\S.*',
                    ],
                    'password' => [
                        'description' => __('The generated password. Only available after adding an application.'),
                        'type' => 'string',
                        'context' => ['edit'],
                        'readonly' => true,
                    ],
                    'created' => [
                        'description' => __('The GMT date the application password was created.'),
                        'type' => 'string',
                        'format' => 'date-time',
                        'context' => ['view', 'edit'],
                        'readonly' => true,
                    ],
                    'last_used' => [
                        'description' => __('The GMT date the application password was last used.'),
                        'type' => ['string', 'null'],
                        'format' => 'date-time',
                        'context' => ['view', 'edit'],
                        'readonly' => true,
                    ],
                    'last_ip' => [
                        'description' => __('The IP address the application password was last used by.'),
                        'type' => ['string', 'null'],
                        'format' => 'ip',
                        'context' => ['view', 'edit'],
                        'readonly' => true,
                    ],
                ],
            ];

            return $this->add_additional_fields_schema($this->schema);
        }

        protected function do_permissions_check($request)
        {
            _deprecated_function(__METHOD__, '5.7.0');

            $user = $this->get_user($request);

            if(is_wp_error($user))
            {
                return $user;
            }

            if(! current_user_can('edit_user', $user->ID))
            {
                return new WP_Error('rest_cannot_manage_application_passwords', __('Sorry, you are not allowed to manage application passwords for this user.'), ['status' => rest_authorization_required_code()]);
            }

            return true;
        }
    }
