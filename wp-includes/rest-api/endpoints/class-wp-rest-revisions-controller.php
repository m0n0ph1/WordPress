<?php

    class WP_REST_Revisions_Controller extends WP_REST_Controller
    {
        private $parent_post_type;

        private $parent_controller;

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
            $this->rest_base = 'revisions';
            $this->parent_base = ! empty($post_type_object->rest_base) ? $post_type_object->rest_base : $post_type_object->name;
            $this->namespace = ! empty($post_type_object->rest_namespace) ? $post_type_object->rest_namespace : 'wp/v2';
        }

        public function register_routes()
        {
            parent::register_routes();
            register_rest_route($this->namespace, '/'.$this->parent_base.'/(?P<parent>[\d]+)/'.$this->rest_base, [
                'args' => [
                    'parent' => [
                        'description' => __('The ID for the parent of the revision.'),
                        'type' => 'integer',
                    ],
                ],
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_items'],
                    'permission_callback' => [$this, 'get_items_permissions_check'],
                    'args' => $this->get_collection_params(),
                ],
                'schema' => [$this, 'get_public_item_schema'],
            ]);

            register_rest_route($this->namespace, '/'.$this->parent_base.'/(?P<parent>[\d]+)/'.$this->rest_base.'/(?P<id>[\d]+)', [
                'args' => [
                    'parent' => [
                        'description' => __('The ID for the parent of the revision.'),
                        'type' => 'integer',
                    ],
                    'id' => [
                        'description' => __('Unique identifier for the revision.'),
                        'type' => 'integer',
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
                [
                    'methods' => WP_REST_Server::DELETABLE,
                    'callback' => [$this, 'delete_item'],
                    'permission_callback' => [$this, 'delete_item_permissions_check'],
                    'args' => [
                        'force' => [
                            'type' => 'boolean',
                            'default' => false,
                            'description' => __('Required to be true, as revisions do not support trashing.'),
                        ],
                    ],
                ],
                'schema' => [$this, 'get_public_item_schema'],
            ]);
        }

        public function get_collection_params()
        {
            $query_params = parent::get_collection_params();

            $query_params['context']['default'] = 'view';

            unset($query_params['per_page']['default']);

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

            $query_params['offset'] = [
                'description' => __('Offset the result set by a specific number of items.'),
                'type' => 'integer',
            ];

            $query_params['order'] = [
                'description' => __('Order sort attribute ascending or descending.'),
                'type' => 'string',
                'default' => 'desc',
                'enum' => ['asc', 'desc'],
            ];

            $query_params['orderby'] = [
                'description' => __('Sort collection by object attribute.'),
                'type' => 'string',
                'default' => 'date',
                'enum' => [
                    'date',
                    'id',
                    'include',
                    'relevance',
                    'slug',
                    'include_slugs',
                    'title',
                ],
            ];

            return $query_params;
        }

        public function get_items($request)
        {
            $parent = $this->get_parent($request['parent']);
            if(is_wp_error($parent))
            {
                return $parent;
            }

            // Ensure a search string is set in case the orderby is set to 'relevance'.
            if(! empty($request['orderby']) && 'relevance' === $request['orderby'] && empty($request['search']))
            {
                return new WP_Error('rest_no_search_term_defined', __('You need to define a search term to order by relevance.'), ['status' => 400]);
            }

            // Ensure an include parameter is set in case the orderby is set to 'include'.
            if(! empty($request['orderby']) && 'include' === $request['orderby'] && empty($request['include']))
            {
                return new WP_Error('rest_orderby_include_missing_include', __('You need to define an include parameter to order by include.'), ['status' => 400]);
            }

            if(wp_revisions_enabled($parent))
            {
                $registered = $this->get_collection_params();
                $args = [
                    'post_parent' => $parent->ID,
                    'post_type' => 'revision',
                    'post_status' => 'inherit',
                    'posts_per_page' => -1,
                    'orderby' => 'date ID',
                    'order' => 'DESC',
                    'suppress_filters' => true,
                ];

                $parameter_mappings = [
                    'exclude' => 'post__not_in',
                    'include' => 'post__in',
                    'offset' => 'offset',
                    'order' => 'order',
                    'orderby' => 'orderby',
                    'page' => 'paged',
                    'per_page' => 'posts_per_page',
                    'search' => 's',
                ];

                foreach($parameter_mappings as $api_param => $wp_param)
                {
                    if(isset($registered[$api_param], $request[$api_param]))
                    {
                        $args[$wp_param] = $request[$api_param];
                    }
                }

                // For backward-compatibility, 'date' needs to resolve to 'date ID'.
                if(isset($args['orderby']) && 'date' === $args['orderby'])
                {
                    $args['orderby'] = 'date ID';
                }

                $args = apply_filters('rest_revision_query', $args, $request);
                $query_args = $this->prepare_items_query($args, $request);

                $revisions_query = new WP_Query();
                $revisions = $revisions_query->query($query_args);
                $offset = isset($query_args['offset']) ? (int) $query_args['offset'] : 0;
                $page = (int) $query_args['paged'];
                $total_revisions = $revisions_query->found_posts;

                if($total_revisions < 1)
                {
                    // Out-of-bounds, run the query again without LIMIT for total count.
                    unset($query_args['paged'], $query_args['offset']);

                    $count_query = new WP_Query();
                    $count_query->query($query_args);

                    $total_revisions = $count_query->found_posts;
                }

                if($revisions_query->query_vars['posts_per_page'] > 0)
                {
                    $max_pages = ceil($total_revisions / (int) $revisions_query->query_vars['posts_per_page']);
                }
                else
                {
                    $max_pages = $total_revisions > 0 ? 1 : 0;
                }

                if($total_revisions > 0)
                {
                    if($offset >= $total_revisions)
                    {
                        return new WP_Error('rest_revision_invalid_offset_number', __('The offset number requested is larger than or equal to the number of available revisions.'), ['status' => 400]);
                    }
                    elseif(! $offset && $page > $max_pages)
                    {
                        return new WP_Error('rest_revision_invalid_page_number', __('The page number requested is larger than the number of pages available.'), ['status' => 400]);
                    }
                }
            }
            else
            {
                $revisions = [];
                $total_revisions = 0;
                $max_pages = 0;
                $page = (int) $request['page'];
            }

            $response = [];

            foreach($revisions as $revision)
            {
                $data = $this->prepare_item_for_response($revision, $request);
                $response[] = $this->prepare_response_for_collection($data);
            }

            $response = rest_ensure_response($response);

            $response->header('X-WP-Total', (int) $total_revisions);
            $response->header('X-WP-TotalPages', (int) $max_pages);

            $request_params = $request->get_query_params();
            $base_path = rest_url(sprintf('%s/%s/%d/%s', $this->namespace, $this->parent_base, $request['parent'], $this->rest_base));
            $base = add_query_arg(urlencode_deep($request_params), $base_path);

            if($page > 1)
            {
                $prev_page = $page - 1;

                if($prev_page > $max_pages)
                {
                    $prev_page = $max_pages;
                }

                $prev_link = add_query_arg('page', $prev_page, $base);
                $response->link_header('prev', $prev_link);
            }
            if($max_pages > $page)
            {
                $next_page = $page + 1;
                $next_link = add_query_arg('page', $next_page, $base);

                $response->link_header('next', $next_link);
            }

            return $response;
        }

        protected function get_parent($parent_post_id)
        {
            $error = new WP_Error('rest_post_invalid_parent', __('Invalid post parent ID.'), ['status' => 404]);

            if((int) $parent_post_id <= 0)
            {
                return $error;
            }

            $parent_post = get_post((int) $parent_post_id);

            if(empty($parent_post) || empty($parent_post->ID) || $this->parent_post_type !== $parent_post->post_type)
            {
                return $error;
            }

            return $parent_post;
        }

        protected function prepare_items_query($prepared_args = [], $request = null)
        {
            $query_args = [];

            foreach($prepared_args as $key => $value)
            {
                $query_args[$key] = apply_filters("rest_query_var-{$key}", $value); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
            }

            // Map to proper WP_Query orderby param.
            if(isset($query_args['orderby']) && isset($request['orderby']))
            {
                $orderby_mappings = [
                    'id' => 'ID',
                    'include' => 'post__in',
                    'slug' => 'post_name',
                    'include_slugs' => 'post_name__in',
                ];

                if(isset($orderby_mappings[$request['orderby']]))
                {
                    $query_args['orderby'] = $orderby_mappings[$request['orderby']];
                }
            }

            return $query_args;
        }

        public function prepare_item_for_response($item, $request)
        {
            // Restores the more descriptive, specific name for use within this method.
            $post = $item;

            $GLOBALS['post'] = $post;

            setup_postdata($post);

            $fields = $this->get_fields_for_response($request);
            $data = [];

            if(in_array('author', $fields, true))
            {
                $data['author'] = (int) $post->post_author;
            }

            if(in_array('date', $fields, true))
            {
                $data['date'] = $this->prepare_date_response($post->post_date_gmt, $post->post_date);
            }

            if(in_array('date_gmt', $fields, true))
            {
                $data['date_gmt'] = $this->prepare_date_response($post->post_date_gmt);
            }

            if(in_array('id', $fields, true))
            {
                $data['id'] = $post->ID;
            }

            if(in_array('modified', $fields, true))
            {
                $data['modified'] = $this->prepare_date_response($post->post_modified_gmt, $post->post_modified);
            }

            if(in_array('modified_gmt', $fields, true))
            {
                $data['modified_gmt'] = $this->prepare_date_response($post->post_modified_gmt);
            }

            if(in_array('parent', $fields, true))
            {
                $data['parent'] = (int) $post->post_parent;
            }

            if(in_array('slug', $fields, true))
            {
                $data['slug'] = $post->post_name;
            }

            if(in_array('guid', $fields, true))
            {
                $data['guid'] = [

                    'rendered' => apply_filters('get_the_guid', $post->guid, $post->ID),
                    'raw' => $post->guid,
                ];
            }

            if(in_array('title', $fields, true))
            {
                $data['title'] = [
                    'raw' => $post->post_title,
                    'rendered' => get_the_title($post->ID),
                ];
            }

            if(in_array('content', $fields, true))
            {
                $data['content'] = [
                    'raw' => $post->post_content,

                    'rendered' => apply_filters('the_content', $post->post_content),
                ];
            }

            if(in_array('excerpt', $fields, true))
            {
                $data['excerpt'] = [
                    'raw' => $post->post_excerpt,
                    'rendered' => $this->prepare_excerpt_response($post->post_excerpt, $post),
                ];
            }

            $context = ! empty($request['context']) ? $request['context'] : 'view';
            $data = $this->add_additional_fields_to_object($data, $request);
            $data = $this->filter_response_by_context($data, $context);
            $response = rest_ensure_response($data);

            if(! empty($data['parent']))
            {
                $response->add_link('parent', rest_url(rest_get_route_for_post($data['parent'])));
            }

            return apply_filters('rest_prepare_revision', $response, $post, $request);
        }

        protected function prepare_date_response($date_gmt, $date = null)
        {
            if('0000-00-00 00:00:00' === $date_gmt)
            {
                return null;
            }

            if(isset($date))
            {
                return mysql_to_rfc3339($date);
            }

            return mysql_to_rfc3339($date_gmt);
        }

        protected function prepare_excerpt_response($excerpt, $post)
        {
            $excerpt = apply_filters('the_excerpt', $excerpt, $post);

            if(empty($excerpt))
            {
                return '';
            }

            return $excerpt;
        }

        public function get_item_permissions_check($request)
        {
            return $this->get_items_permissions_check($request);
        }

        public function get_items_permissions_check($request)
        {
            $parent = $this->get_parent($request['parent']);
            if(is_wp_error($parent))
            {
                return $parent;
            }

            if(! current_user_can('edit_post', $parent->ID))
            {
                return new WP_Error('rest_cannot_read', __('Sorry, you are not allowed to view revisions of this post.'), ['status' => rest_authorization_required_code()]);
            }

            return true;
        }

        public function get_item($request)
        {
            $parent = $this->get_parent($request['parent']);
            if(is_wp_error($parent))
            {
                return $parent;
            }

            $revision = $this->get_revision($request['id']);
            if(is_wp_error($revision))
            {
                return $revision;
            }

            $response = $this->prepare_item_for_response($revision, $request);

            return rest_ensure_response($response);
        }

        protected function get_revision($id)
        {
            $error = new WP_Error('rest_post_invalid_id', __('Invalid revision ID.'), ['status' => 404]);

            if((int) $id <= 0)
            {
                return $error;
            }

            $revision = get_post((int) $id);
            if(empty($revision) || empty($revision->ID) || 'revision' !== $revision->post_type)
            {
                return $error;
            }

            return $revision;
        }

        public function delete_item_permissions_check($request)
        {
            $parent = $this->get_parent($request['parent']);
            if(is_wp_error($parent))
            {
                return $parent;
            }

            if(! current_user_can('delete_post', $parent->ID))
            {
                return new WP_Error('rest_cannot_delete', __('Sorry, you are not allowed to delete revisions of this post.'), ['status' => rest_authorization_required_code()]);
            }

            $revision = $this->get_revision($request['id']);
            if(is_wp_error($revision))
            {
                return $revision;
            }

            $response = $this->get_items_permissions_check($request);
            if(! $response || is_wp_error($response))
            {
                return $response;
            }

            if(! current_user_can('delete_post', $revision->ID))
            {
                return new WP_Error('rest_cannot_delete', __('Sorry, you are not allowed to delete this revision.'), ['status' => rest_authorization_required_code()]);
            }

            return true;
        }

        public function delete_item($request)
        {
            $revision = $this->get_revision($request['id']);
            if(is_wp_error($revision))
            {
                return $revision;
            }

            $force = isset($request['force']) ? (bool) $request['force'] : false;

            // We don't support trashing for revisions.
            if(! $force)
            {
                return new WP_Error('rest_trash_not_supported', /* translators: %s: force=true */ sprintf(__("Revisions do not support trashing. Set '%s' to delete."), 'force=true'), ['status' => 501]);
            }

            $previous = $this->prepare_item_for_response($revision, $request);

            $result = wp_delete_post($request['id'], true);

            do_action('rest_delete_revision', $result, $request);

            if(! $result)
            {
                return new WP_Error('rest_cannot_delete', __('The post cannot be deleted.'), ['status' => 500]);
            }

            $response = new WP_REST_Response();
            $response->set_data([
                                    'deleted' => true,
                                    'previous' => $previous->get_data(),
                                ]);

            return $response;
        }

        public function get_item_schema()
        {
            if($this->schema)
            {
                return $this->add_additional_fields_schema($this->schema);
            }

            $schema = [
                '$schema' => 'http://json-schema.org/draft-04/schema#',
                'title' => "{$this->parent_post_type}-revision",
                'type' => 'object',
                // Base properties for every Revision.
                'properties' => [
                    'author' => [
                        'description' => __('The ID for the author of the revision.'),
                        'type' => 'integer',
                        'context' => ['view', 'edit', 'embed'],
                    ],
                    'date' => [
                        'description' => __("The date the revision was published, in the site's timezone."),
                        'type' => 'string',
                        'format' => 'date-time',
                        'context' => ['view', 'edit', 'embed'],
                    ],
                    'date_gmt' => [
                        'description' => __('The date the revision was published, as GMT.'),
                        'type' => 'string',
                        'format' => 'date-time',
                        'context' => ['view', 'edit'],
                    ],
                    'guid' => [
                        'description' => __('GUID for the revision, as it exists in the database.'),
                        'type' => 'string',
                        'context' => ['view', 'edit'],
                    ],
                    'id' => [
                        'description' => __('Unique identifier for the revision.'),
                        'type' => 'integer',
                        'context' => ['view', 'edit', 'embed'],
                    ],
                    'modified' => [
                        'description' => __("The date the revision was last modified, in the site's timezone."),
                        'type' => 'string',
                        'format' => 'date-time',
                        'context' => ['view', 'edit'],
                    ],
                    'modified_gmt' => [
                        'description' => __('The date the revision was last modified, as GMT.'),
                        'type' => 'string',
                        'format' => 'date-time',
                        'context' => ['view', 'edit'],
                    ],
                    'parent' => [
                        'description' => __('The ID for the parent of the revision.'),
                        'type' => 'integer',
                        'context' => ['view', 'edit', 'embed'],
                    ],
                    'slug' => [
                        'description' => __('An alphanumeric identifier for the revision unique to its type.'),
                        'type' => 'string',
                        'context' => ['view', 'edit', 'embed'],
                    ],
                ],
            ];

            $parent_schema = $this->parent_controller->get_item_schema();

            if(! empty($parent_schema['properties']['title']))
            {
                $schema['properties']['title'] = $parent_schema['properties']['title'];
            }

            if(! empty($parent_schema['properties']['content']))
            {
                $schema['properties']['content'] = $parent_schema['properties']['content'];
            }

            if(! empty($parent_schema['properties']['excerpt']))
            {
                $schema['properties']['excerpt'] = $parent_schema['properties']['excerpt'];
            }

            if(! empty($parent_schema['properties']['guid']))
            {
                $schema['properties']['guid'] = $parent_schema['properties']['guid'];
            }

            $this->schema = $schema;

            return $this->add_additional_fields_schema($this->schema);
        }
    }
