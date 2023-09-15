<?php

    class WP_REST_Term_Search_Handler extends WP_REST_Search_Handler
    {
        public function __construct()
        {
            $this->type = 'term';

            $this->subtypes = array_values(
                get_taxonomies([
                                   'public' => true,
                                   'show_in_rest' => true,
                               ], 'names')
            );
        }

        public function search_items(WP_REST_Request $request)
        {
            $taxonomies = $request[WP_REST_Search_Controller::PROP_SUBTYPE];
            if(in_array(WP_REST_Search_Controller::TYPE_ANY, $taxonomies, true))
            {
                $taxonomies = $this->subtypes;
            }

            $page = (int) $request['page'];
            $per_page = (int) $request['per_page'];

            $query_args = [
                'taxonomy' => $taxonomies,
                'hide_empty' => false,
                'offset' => ($page - 1) * $per_page,
                'number' => $per_page,
            ];

            if(! empty($request['search']))
            {
                $query_args['search'] = $request['search'];
            }

            if(! empty($request['exclude']))
            {
                $query_args['exclude'] = $request['exclude'];
            }

            if(! empty($request['include']))
            {
                $query_args['include'] = $request['include'];
            }

            $query_args = apply_filters('rest_term_search_query', $query_args, $request);

            $query = new WP_Term_Query();
            $found_terms = $query->query($query_args);
            $found_ids = wp_list_pluck($found_terms, 'term_id');

            unset($query_args['offset'], $query_args['number']);

            $total = wp_count_terms($query_args);

            // wp_count_terms() can return a falsey value when the term has no children.
            if(! $total)
            {
                $total = 0;
            }

            return [
                self::RESULT_IDS => $found_ids,
                self::RESULT_TOTAL => $total,
            ];
        }

        public function prepare_item($id, array $fields)
        {
            $term = get_term($id);

            $data = [];

            if(in_array(WP_REST_Search_Controller::PROP_ID, $fields, true))
            {
                $data[WP_REST_Search_Controller::PROP_ID] = (int) $id;
            }
            if(in_array(WP_REST_Search_Controller::PROP_TITLE, $fields, true))
            {
                $data[WP_REST_Search_Controller::PROP_TITLE] = $term->name;
            }
            if(in_array(WP_REST_Search_Controller::PROP_URL, $fields, true))
            {
                $data[WP_REST_Search_Controller::PROP_URL] = get_term_link($id);
            }
            if(in_array(WP_REST_Search_Controller::PROP_TYPE, $fields, true))
            {
                $data[WP_REST_Search_Controller::PROP_TYPE] = $term->taxonomy;
            }

            return $data;
        }

        public function prepare_item_links($id)
        {
            $term = get_term($id);

            $links = [];

            $item_route = rest_get_route_for_term($term);
            if($item_route)
            {
                $links['self'] = [
                    'href' => rest_url($item_route),
                    'embeddable' => true,
                ];
            }

            $links['about'] = [
                'href' => rest_url(sprintf('wp/v2/taxonomies/%s', $term->taxonomy)),
            ];

            return $links;
        }
    }
