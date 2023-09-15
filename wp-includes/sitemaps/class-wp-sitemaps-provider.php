<?php

    #[AllowDynamicProperties]
    abstract class WP_Sitemaps_Provider
    {
        protected $name = '';

        protected $object_type = '';

        abstract public function get_url_list($page_num, $object_subtype = '');

        public function get_sitemap_entries()
        {
            $sitemaps = [];

            $sitemap_types = $this->get_sitemap_type_data();

            foreach($sitemap_types as $type)
            {
                for($page = 1; $page <= $type['pages']; $page++)
                {
                    $sitemap_entry = [
                        'loc' => $this->get_sitemap_url($type['name'], $page),
                    ];

                    $sitemap_entry = apply_filters('wp_sitemaps_index_entry', $sitemap_entry, $this->object_type, $type['name'], $page);

                    $sitemaps[] = $sitemap_entry;
                }
            }

            return $sitemaps;
        }

        public function get_sitemap_type_data()
        {
            $sitemap_data = [];

            $object_subtypes = $this->get_object_subtypes();

            /*
             * If there are no object subtypes, include a single sitemap for the
             * entire object type.
             */
            if(empty($object_subtypes))
            {
                $sitemap_data[] = [
                    'name' => '',
                    'pages' => $this->get_max_num_pages(),
                ];

                return $sitemap_data;
            }

            // Otherwise, include individual sitemaps for every object subtype.
            foreach($object_subtypes as $object_subtype_name => $data)
            {
                $object_subtype_name = (string) $object_subtype_name;

                $sitemap_data[] = [
                    'name' => $object_subtype_name,
                    'pages' => $this->get_max_num_pages($object_subtype_name),
                ];
            }

            return $sitemap_data;
        }

        public function get_object_subtypes()
        {
            return [];
        }

        abstract public function get_max_num_pages($object_subtype = '');

        public function get_sitemap_url($name, $page)
        {
            global $wp_rewrite;

            // Accounts for cases where name is not included, ex: sitemaps-users-1.xml.
            $params = array_filter([
                                       'sitemap' => $this->name,
                                       'sitemap-subtype' => $name,
                                       'paged' => $page,
                                   ]);

            $basename = sprintf('/wp-sitemap-%1$s.xml', implode('-', $params));

            if(! $wp_rewrite->using_permalinks())
            {
                $basename = '/?'.http_build_query($params, '', '&');
            }

            return home_url($basename);
        }
    }
