<?php

    class WP_REST_Post_Search_Handler extends WP_REST_Search_Handler
    {
        public function __construct()
        {
            $this->type = 'post';

            // Support all public post types except attachments.
            $this->subtypes = array_diff(
                array_values(
                    get_post_types([
                                       'public' => true,
                                       'show_in_rest' => true,
                                   ], 'names')
                ), ['attachment']
            );
        }

        public function search_items(WP_REST_Request $request)
        {
            // Get the post types to search for the current request.
            $post_types = $request[WP_REST_Search_Controller::PROP_SUBTYPE];
            if(in_array(WP_REST_Search_Controller::TYPE_ANY, $post_types, true))
            {
                $post_types = $this->subtypes;
            }

            $query_args = [
                'post_type' => $post_types,
                'post_status' => 'publish',
                'paged' => (int) $request['page'],
                'posts_per_page' => (int) $request['per_page'],
                'ignore_sticky_posts' => true,
            ];

            if(! empty($request['search']))
            {
                $query_args['s'] = $request['search'];
            }

            if(! empty($request['exclude']))
            {
                $query_args['post__not_in'] = $request['exclude'];
            }

            if(! empty($request['include']))
            {
                $query_args['post__in'] = $request['include'];
            }

            $query_args = apply_filters('rest_post_search_query', $query_args, $request);

            $query = new WP_Query();
            $posts = $query->query($query_args);
            // Querying the whole post object will warm the object cache, avoiding an extra query per result.
            $found_ids = wp_list_pluck($posts, 'ID');
            $total = $query->found_posts;

            return [
                self::RESULT_IDS => $found_ids,
                self::RESULT_TOTAL => $total,
            ];
        }

        public function prepare_item($id, array $fields)
        {
            $post = get_post($id);

            $data = [];

            if(in_array(WP_REST_Search_Controller::PROP_ID, $fields, true))
            {
                $data[WP_REST_Search_Controller::PROP_ID] = (int) $post->ID;
            }

            if(in_array(WP_REST_Search_Controller::PROP_TITLE, $fields, true))
            {
                if(post_type_supports($post->post_type, 'title'))
                {
                    add_filter('protected_title_format', [$this, 'protected_title_format']);
                    $data[WP_REST_Search_Controller::PROP_TITLE] = get_the_title($post->ID);
                    remove_filter('protected_title_format', [$this, 'protected_title_format']);
                }
                else
                {
                    $data[WP_REST_Search_Controller::PROP_TITLE] = '';
                }
            }

            if(in_array(WP_REST_Search_Controller::PROP_URL, $fields, true))
            {
                $data[WP_REST_Search_Controller::PROP_URL] = get_permalink($post->ID);
            }

            if(in_array(WP_REST_Search_Controller::PROP_TYPE, $fields, true))
            {
                $data[WP_REST_Search_Controller::PROP_TYPE] = $this->type;
            }

            if(in_array(WP_REST_Search_Controller::PROP_SUBTYPE, $fields, true))
            {
                $data[WP_REST_Search_Controller::PROP_SUBTYPE] = $post->post_type;
            }

            return $data;
        }

        public function prepare_item_links($id)
        {
            $post = get_post($id);

            $links = [];

            $item_route = rest_get_route_for_post($post);
            if(! empty($item_route))
            {
                $links['self'] = [
                    'href' => rest_url($item_route),
                    'embeddable' => true,
                ];
            }

            $links['about'] = [
                'href' => rest_url('wp/v2/types/'.$post->post_type),
            ];

            return $links;
        }

        public function protected_title_format()
        {
            return '%s';
        }

        protected function detect_rest_item_route($post)
        {
            _deprecated_function(__METHOD__, '5.5.0', 'rest_get_route_for_post()');

            return rest_get_route_for_post($post);
        }
    }
