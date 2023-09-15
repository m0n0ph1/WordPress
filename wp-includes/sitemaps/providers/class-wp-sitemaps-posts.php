<?php
    /**
     * Sitemaps: WP_Sitemaps_Posts class
     *
     * Builds the sitemaps for the 'post' object type.
     *
     * @package    WordPress
     * @subpackage Sitemaps
     * @since      5.5.0
     */

    /**
     * Posts XML sitemap provider.
     *
     * @since 5.5.0
     */
    class WP_Sitemaps_Posts extends WP_Sitemaps_Provider
    {
        /**
         * WP_Sitemaps_Posts constructor.
         *
         * @since 5.5.0
         */
        public function __construct()
        {
            $this->name = 'posts';
            $this->object_type = 'post';
        }

        /**
         * Gets a URL list for a post type sitemap.
         *
         * @param int    $page_num       Page of results.
         * @param string $object_subtype Optional. Post type name. Default empty.
         *
         * @return array[] Array of URL information for a sitemap.
         * @since 5.9.0 Renamed `$post_type` to `$object_subtype` to match parent class
         *              for PHP 8 named parameter support.
         *
         * @since 5.5.0
         */
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

            /**
             * Filters the posts URL list before it is generated.
             *
             * Returning a non-null value will effectively short-circuit the generation,
             * returning that value instead.
             *
             * @param array[]|null $url_list  The URL list. Default null.
             * @param string       $post_type Post type name.
             * @param int          $page_num  Page of results.
             *
             * @since 5.5.0
             *
             */
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

                /**
                 * Filters the sitemap entry for the home page when the 'show_on_front' option equals 'posts'.
                 *
                 * @param array $sitemap_entry Sitemap entry for the home page.
                 *
                 * @since 5.5.0
                 *
                 */
                $sitemap_entry = apply_filters('wp_sitemaps_posts_show_on_front_entry', $sitemap_entry);
                $url_list[] = $sitemap_entry;
            }

            foreach($query->posts as $post)
            {
                $sitemap_entry = [
                    'loc' => get_permalink($post),
                ];

                /**
                 * Filters the sitemap entry for an individual post.
                 *
                 * @param array   $sitemap_entry Sitemap entry for the post.
                 * @param WP_Post $post          Post object.
                 * @param string  $post_type     Name of the post_type.
                 *
                 * @since 5.5.0
                 *
                 */
                $sitemap_entry = apply_filters('wp_sitemaps_posts_entry', $sitemap_entry, $post, $post_type);
                $url_list[] = $sitemap_entry;
            }

            return $url_list;
        }

        /**
         * Returns the public post types, which excludes nav_items and similar types.
         * Attachments are also excluded. This includes custom post types with public = true.
         *
         * @return WP_Post_Type[] Array of registered post type objects keyed by their name.
         * @since 5.5.0
         *
         */
        public function get_object_subtypes()
        {
            $post_types = get_post_types(['public' => true], 'objects');
            unset($post_types['attachment']);

            $post_types = array_filter($post_types, 'is_post_type_viewable');

            /**
             * Filters the list of post object sub types available within the sitemap.
             *
             * @param WP_Post_Type[] $post_types Array of registered post type objects keyed by their name.
             *
             * @since 5.5.0
             *
             */
            return apply_filters('wp_sitemaps_post_types', $post_types);
        }

        /**
         * Returns the query args for retrieving posts to list in the sitemap.
         *
         * @param string $post_type Post type name.
         *
         * @return array Array of WP_Query arguments.
         * @since 5.5.0
         * @since 6.1.0 Added `ignore_sticky_posts` default parameter.
         *
         */
        protected function get_posts_query_args($post_type)
        {
            /**
             * Filters the query arguments for post type sitemap queries.
             *
             * @param array  $args      Array of WP_Query arguments.
             * @param string $post_type Post type name.
             *
             * @since 6.1.0 Added `ignore_sticky_posts` default parameter.
             *
             * @see   WP_Query for a full list of arguments.
             *
             * @since 5.5.0
             */
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

        /**
         * Gets the max number of pages available for the object type.
         *
         * @param string $object_subtype Optional. Post type name. Default empty.
         *
         * @return int Total number of pages.
         * @since 5.5.0
         * @since 5.9.0 Renamed `$post_type` to `$object_subtype` to match parent class
         *              for PHP 8 named parameter support.
         *
         */
        public function get_max_num_pages($object_subtype = '')
        {
            if(empty($object_subtype))
            {
                return 0;
            }

            // Restores the more descriptive, specific name for use within this method.
            $post_type = $object_subtype;

            /**
             * Filters the max number of pages before it is generated.
             *
             * Passing a non-null value will short-circuit the generation,
             * returning that value instead.
             *
             * @param int|null $max_num_pages The maximum number of pages. Default null.
             * @param string   $post_type     Post type name.
             *
             * @since 5.5.0
             *
             */
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

            return isset($query->max_num_pages) ? max($min_num_pages, $query->max_num_pages) : 1;
        }
    }
