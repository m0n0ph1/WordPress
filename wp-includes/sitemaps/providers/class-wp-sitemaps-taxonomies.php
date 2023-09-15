<?php

    class WP_Sitemaps_Taxonomies extends WP_Sitemaps_Provider
    {
        public function __construct()
        {
            $this->name = 'taxonomies';
            $this->object_type = 'term';
        }

        public function get_url_list($page_num, $object_subtype = '')
        {
            // Restores the more descriptive, specific name for use within this method.
            $taxonomy = $object_subtype;

            $supported_types = $this->get_object_subtypes();

            // Bail early if the queried taxonomy is not supported.
            if(! isset($supported_types[$taxonomy]))
            {
                return [];
            }

            $url_list = apply_filters('wp_sitemaps_taxonomies_pre_url_list', null, $taxonomy, $page_num);

            if(null !== $url_list)
            {
                return $url_list;
            }

            $url_list = [];

            // Offset by how many terms should be included in previous pages.
            $offset = ($page_num - 1) * wp_sitemaps_get_max_urls($this->object_type);

            $args = $this->get_taxonomies_query_args($taxonomy);
            $args['fields'] = 'all';
            $args['offset'] = $offset;

            $taxonomy_terms = new WP_Term_Query($args);

            if(! empty($taxonomy_terms->terms))
            {
                foreach($taxonomy_terms->terms as $term)
                {
                    $term_link = get_term_link($term, $taxonomy);

                    if(is_wp_error($term_link))
                    {
                        continue;
                    }

                    $sitemap_entry = [
                        'loc' => $term_link,
                    ];

                    $sitemap_entry = apply_filters('wp_sitemaps_taxonomies_entry', $sitemap_entry, $term->term_id, $taxonomy, $term);
                    $url_list[] = $sitemap_entry;
                }
            }

            return $url_list;
        }

        public function get_object_subtypes()
        {
            $taxonomies = get_taxonomies(['public' => true], 'objects');

            $taxonomies = array_filter($taxonomies, 'is_taxonomy_viewable');

            return apply_filters('wp_sitemaps_taxonomies', $taxonomies);
        }

        protected function get_taxonomies_query_args($taxonomy)
        {
            $args = apply_filters('wp_sitemaps_taxonomies_query_args', [
                'taxonomy' => $taxonomy,
                'orderby' => 'term_order',
                'number' => wp_sitemaps_get_max_urls($this->object_type),
                'hide_empty' => true,
                'hierarchical' => false,
                'update_term_meta_cache' => false,
            ],                    $taxonomy);

            return $args;
        }

        public function get_max_num_pages($object_subtype = '')
        {
            if(empty($object_subtype))
            {
                return 0;
            }

            // Restores the more descriptive, specific name for use within this method.
            $taxonomy = $object_subtype;

            $max_num_pages = apply_filters('wp_sitemaps_taxonomies_pre_max_num_pages', null, $taxonomy);

            if(null !== $max_num_pages)
            {
                return $max_num_pages;
            }

            $term_count = wp_count_terms($this->get_taxonomies_query_args($taxonomy));

            return (int) ceil($term_count / wp_sitemaps_get_max_urls($this->object_type));
        }
    }
