<?php

    class WP_Sitemaps_Posts extends WP_Sitemaps_Provider
    {
        public function __construct()
        {
            $this->name = 'posts';
            $this->object_type = 'post';
        }

        public function get_url_list($page_num, $object_subtype = '')
        {
            // Restores the more descriptive, specific name for use within this method.
            $post_type = $object_subtype;

            // Bail early if the queried post type is not supported.
            $supported_types = $this->get_object_subtypes();

            if(! isset($supported_types[$post_type]))
            {
                return [];
            }

            $url_list = apply_filters('wp_sitemaps_posts_pre_url_list', null, $post_type, $page_num);

            if(null !== $url_list)
            {
                return $url_list;
            }

            $args = $this->get_posts_query_args($post_type);
            $args['paged'] = $page_num;

            $query = new WP_Query($args);

            $url_list = [];

            /*
             * Add a URL for the homepage in the pages sitemap.
             * Shows only on the first page if the reading settings are set to display latest posts.
             */
            if('page' === $post_type && 1 === $page_num && 'posts' === get_option('show_on_front'))
            {
                // Extract the data needed for home URL to add to the array.
                $sitemap_entry = [
                    'loc' => home_url('/'),
                ];

                $sitemap_entry = apply_filters('wp_sitemaps_posts_show_on_front_entry', $sitemap_entry);
                $url_list[] = $sitemap_entry;
            }

            foreach($query->posts as $post)
            {
                $sitemap_entry = [
                    'loc' => get_permalink($post),
                ];

                $sitemap_entry = apply_filters('wp_sitemaps_posts_entry', $sitemap_entry, $post, $post_type);
                $url_list[] = $sitemap_entry;
            }

            return $url_list;
        }

        public function get_object_subtypes()
        {
            $post_types = get_post_types(['public' => true], 'objects');
            unset($post_types['attachment']);

            $post_types = array_filter($post_types, 'is_post_type_viewable');

            return apply_filters('wp_sitemaps_post_types', $post_types);
        }

        protected function get_posts_query_args($post_type)
        {
            $args = apply_filters('wp_sitemaps_posts_query_args', [
                'orderby' => 'ID',
                'order' => 'ASC',
                'post_type' => $post_type,
                'posts_per_page' => wp_sitemaps_get_max_urls($this->object_type),
                'post_status' => ['publish'],
                'no_found_rows' => true,
                'update_post_term_cache' => false,
                'update_post_meta_cache' => false,
                'ignore_sticky_posts' => true,
                // Sticky posts will still appear, but they won't be moved to the front.
            ],                    $post_type);

            return $args;
        }

        public function get_max_num_pages($object_subtype = '')
        {
            if(empty($object_subtype))
            {
                return 0;
            }

            // Restores the more descriptive, specific name for use within this method.
            $post_type = $object_subtype;

            $max_num_pages = apply_filters('wp_sitemaps_posts_pre_max_num_pages', null, $post_type);

            if(null !== $max_num_pages)
            {
                return $max_num_pages;
            }

            $args = $this->get_posts_query_args($post_type);
            $args['fields'] = 'ids';
            $args['no_found_rows'] = false;

            $query = new WP_Query($args);

            $min_num_pages = ('page' === $post_type && 'posts' === get_option('show_on_front')) ? 1 : 0;

            if(isset($query->max_num_pages))
            {
                return max($min_num_pages, $query->max_num_pages);
            }

            return 1;
        }
    }
