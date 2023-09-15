<?php

    class WP_Sitemaps_Users extends WP_Sitemaps_Provider
    {
        public function __construct()
        {
            $this->name = 'users';
            $this->object_type = 'user';
        }

        public function get_url_list($page_num, $object_subtype = '')
        {
            $url_list = apply_filters('wp_sitemaps_users_pre_url_list', null, $page_num);

            if(null !== $url_list)
            {
                return $url_list;
            }

            $args = $this->get_users_query_args();
            $args['paged'] = $page_num;

            $query = new WP_User_Query($args);
            $users = $query->get_results();
            $url_list = [];

            foreach($users as $user)
            {
                $sitemap_entry = [
                    'loc' => get_author_posts_url($user->ID),
                ];

                $sitemap_entry = apply_filters('wp_sitemaps_users_entry', $sitemap_entry, $user);
                $url_list[] = $sitemap_entry;
            }

            return $url_list;
        }

        protected function get_users_query_args()
        {
            $public_post_types = get_post_types([
                                                    'public' => true,
                                                ]);

            // We're not supporting sitemaps for author pages for attachments.
            unset($public_post_types['attachment']);

            $args = apply_filters('wp_sitemaps_users_query_args', [
                'has_published_posts' => array_keys($public_post_types),
                'number' => wp_sitemaps_get_max_urls($this->object_type),
            ]);

            return $args;
        }

        public function get_max_num_pages($object_subtype = '')
        {
            $max_num_pages = apply_filters('wp_sitemaps_users_pre_max_num_pages', null);

            if(null !== $max_num_pages)
            {
                return $max_num_pages;
            }

            $args = $this->get_users_query_args();
            $query = new WP_User_Query($args);

            $total_users = $query->get_total();

            return (int) ceil($total_users / wp_sitemaps_get_max_urls($this->object_type));
        }
    }
