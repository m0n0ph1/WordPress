<?php

    class WP_REST_Autosaves_Controller extends WP_REST_Revisions_Controller
    {
        private $parent_post_type;

        private $parent_controller;

        private $revisions_controller;

        private $parent_base;

        public function __construct($parent_post_type)
        {
            $this->parent_post_type = $parent_post_type;
            $post_type_object = get_post_type_object($parent_post_type);
            $parent_controller = $post_type_object->get_rest_controller();

            if(! $parent_controller)
            {
                $parent_controller = new WP_REST_Posts_Controller($parent_post_type);
            }

            $this->parent_controller = $parent_controller;
            $this->revisions_controller = new WP_REST_Revisions_Controller($parent_post_type);
            $this->rest_base = 'autosaves';
            $this->parent_base = ! empty($post_type_object->rest_base) ? $post_type_object->rest_base : $post_type_object->name;
            $this->namespace = ! empty($post_type_object->rest_namespace) ? $post_type_object->rest_namespace : 'wp/v2';
        }

        public function register_routes()
        {
            register_rest_route($this->namespace, '/'.$this->parent_base.'/(?P<id>[\d]+)/'.$this->rest_base, [
                'args' => [
                    'parent' => [
                        'description' => __('The ID for the parent of the autosave.'),
                        'type' => 'integer',
                    ],
                ],
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
                    'args' => $this->parent_controller->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE),
                ],
                'schema' => [$this, 'get_public_item_schema'],
            ]);

            register_rest_route($this->namespace, '/'.$this->parent_base.'/(?P<parent>[\d]+)/'.$this->rest_base.'/(?P<id>[\d]+)', [
                'args' => [
                    'parent' => [
                        'description' => __('The ID for the parent of the autosave.'),
                        'type' => 'integer',
                    ],
                    'id' => [
                        'description' => __('The ID for the autosave.'),
                        'type' => 'integer',
                    ],
                ],
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_item'],
                    'permission_callback' => [$this->revisions_controller, 'get_item_permissions_check'],
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
            $parent = $this->get_parent($request['id']);
            if(is_wp_error($parent))
            {
                return $parent;
            }

            if(! current_user_can('edit_post', $parent->ID))
            {
                return new WP_Error('rest_cannot_read', __('Sorry, you are not allowed to view autosaves of this post.'), ['status' => rest_authorization_required_code()]);
            }

            return true;
        }

        protected function get_parent($parent_id)
        {
            return $this->revisions_controller->get_parent($parent_id);
        }

        public function create_item_permissions_check($request)
        {
            $id = $request->get_param('id');

            if(empty($id))
            {
                return new WP_Error('rest_post_invalid_id', __('Invalid item ID.'), ['status' => 404]);
            }

            return $this->parent_controller->update_item_permissions_check($request);
        }

        public function create_item($request)
        {
            if(! defined('DOING_AUTOSAVE'))
            {
                define('DOING_AUTOSAVE', true);
            }

            $post = get_post($request['id']);

            if(is_wp_error($post))
            {
                return $post;
            }

            $prepared_post = $this->parent_controller->prepare_item_for_database($request);
            $prepared_post->ID = $post->ID;
            $user_id = get_current_user_id();

            // We need to check post lock to ensure the original author didn't leave their browser tab open.
            if(! function_exists('wp_check_post_lock'))
            {
                require_once ABSPATH.'wp-admin/includes/post.php';
            }

            $post_lock = wp_check_post_lock($post->ID);
            $is_draft = 'draft' === $post->post_status || 'auto-draft' === $post->post_status;

            if($is_draft && (int) $post->post_author === $user_id && ! $post_lock)
            {
                /*
                 * Draft posts for the same author: autosaving updates the post and does not create a revision.
                 * Convert the post object to an array and add slashes, wp_update_post() expects escaped array.
                 */
                $autosave_id = wp_update_post(wp_slash((array) $prepared_post), true);
            }
            else
            {
                // Non-draft posts: create or update the post autosave.
                $autosave_id = $this->create_post_autosave((array) $prepared_post);
            }

            if(is_wp_error($autosave_id))
            {
                return $autosave_id;
            }

            $autosave = get_post($autosave_id);
            $request->set_param('context', 'edit');

            $response = $this->prepare_item_for_response($autosave, $request);
            $response = rest_ensure_response($response);

            return $response;
        }

        public function create_post_autosave($post_data)
        {
            $post_id = (int) $post_data['ID'];
            $post = get_post($post_id);

            if(is_wp_error($post))
            {
                return $post;
            }

            // Only create an autosave when it is different from the saved post.
            $autosave_is_different = false;
            $new_autosave = _wp_post_revision_data($post_data, true);

            foreach(array_intersect(array_keys($new_autosave), array_keys(_wp_post_revision_fields($post))) as $field)
            {
                if(normalize_whitespace($new_autosave[$field]) !== normalize_whitespace($post->$field))
                {
                    $autosave_is_different = true;
                    break;
                }
            }

            $user_id = get_current_user_id();

            // Store one autosave per author. If there is already an autosave, overwrite it.
            $old_autosave = wp_get_post_autosave($post_id, $user_id);

            if(! $autosave_is_different && $old_autosave)
            {
                // Nothing to save, return the existing autosave.
                return $old_autosave->ID;
            }

            if($old_autosave)
            {
                $new_autosave['ID'] = $old_autosave->ID;
                $new_autosave['post_author'] = $user_id;

                do_action('wp_creating_autosave', $new_autosave);

                // wp_update_post() expects escaped array.
                return wp_update_post(wp_slash($new_autosave));
            }

            // Create the new autosave as a special post revision.
            return _wp_put_post_revision($post_data, true);
        }

        public function prepare_item_for_response($item, $request)
        {
            // Restores the more descriptive, specific name for use within this method.
            $post = $item;

            $response = $this->revisions_controller->prepare_item_for_response($post, $request);
            $fields = $this->get_fields_for_response($request);

            if(in_array('preview_link', $fields, true))
            {
                $parent_id = wp_is_post_autosave($post);
                $preview_post_id = false === $parent_id ? $post->ID : $parent_id;
                $preview_query_args = [];

                if(false !== $parent_id)
                {
                    $preview_query_args['preview_id'] = $parent_id;
                    $preview_query_args['preview_nonce'] = wp_create_nonce('post_preview_'.$parent_id);
                }

                $response->data['preview_link'] = get_preview_post_link($preview_post_id, $preview_query_args);
            }

            $context = ! empty($request['context']) ? $request['context'] : 'view';
            $response->data = $this->add_additional_fields_to_object($response->data, $request);
            $response->data = $this->filter_response_by_context($response->data, $context);

            return apply_filters('rest_prepare_autosave', $response, $post, $request);
        }

        public function get_item($request)
        {
            $parent_id = (int) $request->get_param('parent');

            if($parent_id <= 0)
            {
                return new WP_Error('rest_post_invalid_id', __('Invalid post parent ID.'), ['status' => 404]);
            }

            $autosave = wp_get_post_autosave($parent_id);

            if(! $autosave)
            {
                return new WP_Error('rest_post_no_autosave', __('There is no autosave revision for this post.'), ['status' => 404]);
            }

            $response = $this->prepare_item_for_response($autosave, $request);

            return $response;
        }

        public function get_items($request)
        {
            $parent = $this->get_parent($request['id']);
            if(is_wp_error($parent))
            {
                return $parent;
            }

            $response = [];
            $parent_id = $parent->ID;
            $revisions = wp_get_post_revisions($parent_id, ['check_enabled' => false]);

            foreach($revisions as $revision)
            {
                if(str_contains($revision->post_name, "{$parent_id}-autosave"))
                {
                    $data = $this->prepare_item_for_response($revision, $request);
                    $response[] = $this->prepare_response_for_collection($data);
                }
            }

            return rest_ensure_response($response);
        }

        public function get_item_schema()
        {
            if($this->schema)
            {
                return $this->add_additional_fields_schema($this->schema);
            }

            $schema = $this->revisions_controller->get_item_schema();

            $schema['properties']['preview_link'] = [
                'description' => __('Preview link for the post.'),
                'type' => 'string',
                'format' => 'uri',
                'context' => ['edit'],
                'readonly' => true,
            ];

            $this->schema = $schema;

            return $this->add_additional_fields_schema($this->schema);
        }
    }
