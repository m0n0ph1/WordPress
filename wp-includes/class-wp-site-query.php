<?php

    #[AllowDynamicProperties]
    class WP_Site_Query
    {
        public $request;

        public $meta_query = false;

        public $date_query = false;

        public $query_vars;

        public $query_var_defaults;

        public $sites;

        public $found_sites = 0;

        public $max_num_pages = 0;

        protected $sql_clauses = [
            'select' => '',
            'from' => '',
            'where' => [],
            'groupby' => '',
            'orderby' => '',
            'limits' => '',
        ];

        protected $meta_query_clauses;

        public function __construct($query = '')
        {
            $this->query_var_defaults = [
                'fields' => '',
                'ID' => '',
                'site__in' => '',
                'site__not_in' => '',
                'number' => 100,
                'offset' => '',
                'no_found_rows' => true,
                'orderby' => 'id',
                'order' => 'ASC',
                'network_id' => 0,
                'network__in' => '',
                'network__not_in' => '',
                'domain' => '',
                'domain__in' => '',
                'domain__not_in' => '',
                'path' => '',
                'path__in' => '',
                'path__not_in' => '',
                'public' => null,
                'archived' => null,
                'mature' => null,
                'spam' => null,
                'deleted' => null,
                'lang_id' => null,
                'lang__in' => '',
                'lang__not_in' => '',
                'search' => '',
                'search_columns' => [],
                'count' => false,
                'date_query' => null, // See WP_Date_Query.
                'update_site_cache' => true,
                'update_site_meta_cache' => true,
                'meta_query' => '',
                'meta_key' => '',
                'meta_value' => '',
                'meta_type' => '',
                'meta_compare' => '',
            ];

            if(! empty($query))
            {
                $this->query($query);
            }
        }

        public function query($query)
        {
            $this->query_vars = wp_parse_args($query);

            return $this->get_sites();
        }

        public function get_sites()
        {
            global $wpdb;

            $this->parse_query();

            // Parse meta query.
            $this->meta_query = new WP_Meta_Query();
            $this->meta_query->parse_query_vars($this->query_vars);

            do_action_ref_array('pre_get_sites', [&$this]);

            // Reparse query vars, in case they were modified in a 'pre_get_sites' callback.
            $this->meta_query->parse_query_vars($this->query_vars);
            if(! empty($this->meta_query->queries))
            {
                $this->meta_query_clauses = $this->meta_query->get_sql('blog', $wpdb->blogs, 'blog_id', $this);
            }

            $site_data = null;

            $site_data = apply_filters_ref_array('sites_pre_query', [$site_data, &$this]);

            if(null !== $site_data)
            {
                if(is_array($site_data) && ! $this->query_vars['count'])
                {
                    $this->sites = $site_data;
                }

                return $site_data;
            }

            // $args can include anything. Only use the args defined in the query_var_defaults to compute the key.
            $_args = wp_array_slice_assoc($this->query_vars, array_keys($this->query_var_defaults));

            // Ignore the $fields, $update_site_cache, $update_site_meta_cache argument as the queried result will be the same regardless.
            unset($_args['fields'], $_args['update_site_cache'], $_args['update_site_meta_cache']);

            $key = md5(serialize($_args));
            $last_changed = wp_cache_get_last_changed('sites');

            $cache_key = "get_sites:$key:$last_changed";
            $cache_value = wp_cache_get($cache_key, 'site-queries');

            if(false === $cache_value)
            {
                $site_ids = $this->get_site_ids();
                if($site_ids)
                {
                    $this->set_found_sites();
                }

                $cache_value = [
                    'site_ids' => $site_ids,
                    'found_sites' => $this->found_sites,
                ];
                wp_cache_add($cache_key, $cache_value, 'site-queries');
            }
            else
            {
                $site_ids = $cache_value['site_ids'];
                $this->found_sites = $cache_value['found_sites'];
            }

            if($this->found_sites && $this->query_vars['number'])
            {
                $this->max_num_pages = ceil($this->found_sites / $this->query_vars['number']);
            }

            // If querying for a count only, there's nothing more to do.
            if($this->query_vars['count'])
            {
                // $site_ids is actually a count in this case.
                return (int) $site_ids;
            }

            $site_ids = array_map('intval', $site_ids);

            if($this->query_vars['update_site_meta_cache'])
            {
                wp_lazyload_site_meta($site_ids);
            }

            if('ids' === $this->query_vars['fields'])
            {
                $this->sites = $site_ids;

                return $this->sites;
            }

            // Prime site network caches.
            if($this->query_vars['update_site_cache'])
            {
                _prime_site_caches($site_ids, false);
            }

            // Fetch full site objects from the primed cache.
            $_sites = [];
            foreach($site_ids as $site_id)
            {
                $_site = get_site($site_id);
                if($_site)
                {
                    $_sites[] = $_site;
                }
            }

            $_sites = apply_filters_ref_array('the_sites', [$_sites, &$this]);

            // Convert to WP_Site instances.
            $this->sites = array_map('get_site', $_sites);

            return $this->sites;
        }

        public function parse_query($query = '')
        {
            if(empty($query))
            {
                $query = $this->query_vars;
            }

            $this->query_vars = wp_parse_args($query, $this->query_var_defaults);

            do_action_ref_array('parse_site_query', [&$this]);
        }

        protected function get_site_ids()
        {
            global $wpdb;

            $order = $this->parse_order($this->query_vars['order']);

            // Disable ORDER BY with 'none', an empty array, or boolean false.
            if(in_array($this->query_vars['orderby'], ['none', [], false], true))
            {
                $orderby = '';
            }
            elseif(! empty($this->query_vars['orderby']))
            {
                $ordersby = is_array($this->query_vars['orderby']) ? $this->query_vars['orderby'] : preg_split('/[,\s]/', $this->query_vars['orderby']);

                $orderby_array = [];
                foreach($ordersby as $_key => $_value)
                {
                    if(! $_value)
                    {
                        continue;
                    }

                    if(is_int($_key))
                    {
                        $_orderby = $_value;
                        $_order = $order;
                    }
                    else
                    {
                        $_orderby = $_key;
                        $_order = $_value;
                    }

                    $parsed = $this->parse_orderby($_orderby);

                    if(! $parsed)
                    {
                        continue;
                    }

                    if('site__in' === $_orderby || 'network__in' === $_orderby)
                    {
                        $orderby_array[] = $parsed;
                        continue;
                    }

                    $orderby_array[] = $parsed.' '.$this->parse_order($_order);
                }

                $orderby = implode(', ', $orderby_array);
            }
            else
            {
                $orderby = "{$wpdb->blogs}.blog_id $order";
            }

            $number = absint($this->query_vars['number']);
            $offset = absint($this->query_vars['offset']);
            $limits = '';

            if(! empty($number))
            {
                if($offset)
                {
                    $limits = 'LIMIT '.$offset.','.$number;
                }
                else
                {
                    $limits = 'LIMIT '.$number;
                }
            }

            if($this->query_vars['count'])
            {
                $fields = 'COUNT(*)';
            }
            else
            {
                $fields = "{$wpdb->blogs}.blog_id";
            }

            // Parse site IDs for an IN clause.
            $site_id = absint($this->query_vars['ID']);
            if(! empty($site_id))
            {
                $this->sql_clauses['where']['ID'] = $wpdb->prepare("{$wpdb->blogs}.blog_id = %d", $site_id);
            }

            // Parse site IDs for an IN clause.
            if(! empty($this->query_vars['site__in']))
            {
                $this->sql_clauses['where']['site__in'] = "{$wpdb->blogs}.blog_id IN ( ".implode(',', wp_parse_id_list($this->query_vars['site__in'])).' )';
            }

            // Parse site IDs for a NOT IN clause.
            if(! empty($this->query_vars['site__not_in']))
            {
                $this->sql_clauses['where']['site__not_in'] = "{$wpdb->blogs}.blog_id NOT IN ( ".implode(',', wp_parse_id_list($this->query_vars['site__not_in'])).' )';
            }

            $network_id = absint($this->query_vars['network_id']);

            if(! empty($network_id))
            {
                $this->sql_clauses['where']['network_id'] = $wpdb->prepare('site_id = %d', $network_id);
            }

            // Parse site network IDs for an IN clause.
            if(! empty($this->query_vars['network__in']))
            {
                $this->sql_clauses['where']['network__in'] = 'site_id IN ( '.implode(',', wp_parse_id_list($this->query_vars['network__in'])).' )';
            }

            // Parse site network IDs for a NOT IN clause.
            if(! empty($this->query_vars['network__not_in']))
            {
                $this->sql_clauses['where']['network__not_in'] = 'site_id NOT IN ( '.implode(',', wp_parse_id_list($this->query_vars['network__not_in'])).' )';
            }

            if(! empty($this->query_vars['domain']))
            {
                $this->sql_clauses['where']['domain'] = $wpdb->prepare('domain = %s', $this->query_vars['domain']);
            }

            // Parse site domain for an IN clause.
            if(is_array($this->query_vars['domain__in']))
            {
                $this->sql_clauses['where']['domain__in'] = "domain IN ( '".implode("', '", $wpdb->_escape($this->query_vars['domain__in']))."' )";
            }

            // Parse site domain for a NOT IN clause.
            if(is_array($this->query_vars['domain__not_in']))
            {
                $this->sql_clauses['where']['domain__not_in'] = "domain NOT IN ( '".implode("', '", $wpdb->_escape($this->query_vars['domain__not_in']))."' )";
            }

            if(! empty($this->query_vars['path']))
            {
                $this->sql_clauses['where']['path'] = $wpdb->prepare('path = %s', $this->query_vars['path']);
            }

            // Parse site path for an IN clause.
            if(is_array($this->query_vars['path__in']))
            {
                $this->sql_clauses['where']['path__in'] = "path IN ( '".implode("', '", $wpdb->_escape($this->query_vars['path__in']))."' )";
            }

            // Parse site path for a NOT IN clause.
            if(is_array($this->query_vars['path__not_in']))
            {
                $this->sql_clauses['where']['path__not_in'] = "path NOT IN ( '".implode("', '", $wpdb->_escape($this->query_vars['path__not_in']))."' )";
            }

            if(is_numeric($this->query_vars['archived']))
            {
                $archived = absint($this->query_vars['archived']);
                $this->sql_clauses['where']['archived'] = $wpdb->prepare('archived = %s ', absint($archived));
            }

            if(is_numeric($this->query_vars['mature']))
            {
                $mature = absint($this->query_vars['mature']);
                $this->sql_clauses['where']['mature'] = $wpdb->prepare('mature = %d ', $mature);
            }

            if(is_numeric($this->query_vars['spam']))
            {
                $spam = absint($this->query_vars['spam']);
                $this->sql_clauses['where']['spam'] = $wpdb->prepare('spam = %d ', $spam);
            }

            if(is_numeric($this->query_vars['deleted']))
            {
                $deleted = absint($this->query_vars['deleted']);
                $this->sql_clauses['where']['deleted'] = $wpdb->prepare('deleted = %d ', $deleted);
            }

            if(is_numeric($this->query_vars['public']))
            {
                $public = absint($this->query_vars['public']);
                $this->sql_clauses['where']['public'] = $wpdb->prepare('public = %d ', $public);
            }

            if(is_numeric($this->query_vars['lang_id']))
            {
                $lang_id = absint($this->query_vars['lang_id']);
                $this->sql_clauses['where']['lang_id'] = $wpdb->prepare('lang_id = %d ', $lang_id);
            }

            // Parse site language IDs for an IN clause.
            if(! empty($this->query_vars['lang__in']))
            {
                $this->sql_clauses['where']['lang__in'] = 'lang_id IN ( '.implode(',', wp_parse_id_list($this->query_vars['lang__in'])).' )';
            }

            // Parse site language IDs for a NOT IN clause.
            if(! empty($this->query_vars['lang__not_in']))
            {
                $this->sql_clauses['where']['lang__not_in'] = 'lang_id NOT IN ( '.implode(',', wp_parse_id_list($this->query_vars['lang__not_in'])).' )';
            }

            // Falsey search strings are ignored.
            if(strlen($this->query_vars['search']))
            {
                $search_columns = [];

                if($this->query_vars['search_columns'])
                {
                    $search_columns = array_intersect($this->query_vars['search_columns'], ['domain', 'path']);
                }

                if(! $search_columns)
                {
                    $search_columns = ['domain', 'path'];
                }

                $search_columns = apply_filters('site_search_columns', $search_columns, $this->query_vars['search'], $this);

                $this->sql_clauses['where']['search'] = $this->get_search_sql($this->query_vars['search'], $search_columns);
            }

            $date_query = $this->query_vars['date_query'];
            if(! empty($date_query) && is_array($date_query))
            {
                $this->date_query = new WP_Date_Query($date_query, 'registered');

                // Strip leading 'AND'.
                $this->sql_clauses['where']['date_query'] = preg_replace('/^\s*AND\s*/', '', $this->date_query->get_sql());
            }

            $join = '';
            $groupby = '';

            if(! empty($this->meta_query_clauses))
            {
                $join .= $this->meta_query_clauses['join'];

                // Strip leading 'AND'.
                $this->sql_clauses['where']['meta_query'] = preg_replace('/^\s*AND\s*/', '', $this->meta_query_clauses['where']);

                if(! $this->query_vars['count'])
                {
                    $groupby = "{$wpdb->blogs}.blog_id";
                }
            }

            $where = implode(' AND ', $this->sql_clauses['where']);

            $pieces = ['fields', 'join', 'where', 'orderby', 'limits', 'groupby'];

            $clauses = apply_filters_ref_array('sites_clauses', [compact($pieces), &$this]);

            $fields = isset($clauses['fields']) ? $clauses['fields'] : '';
            $join = isset($clauses['join']) ? $clauses['join'] : '';
            $where = isset($clauses['where']) ? $clauses['where'] : '';
            $orderby = isset($clauses['orderby']) ? $clauses['orderby'] : '';
            $limits = isset($clauses['limits']) ? $clauses['limits'] : '';
            $groupby = isset($clauses['groupby']) ? $clauses['groupby'] : '';

            if($where)
            {
                $where = 'WHERE '.$where;
            }

            if($groupby)
            {
                $groupby = 'GROUP BY '.$groupby;
            }

            if($orderby)
            {
                $orderby = "ORDER BY $orderby";
            }

            $found_rows = '';
            if(! $this->query_vars['no_found_rows'])
            {
                $found_rows = 'SQL_CALC_FOUND_ROWS';
            }

            $this->sql_clauses['select'] = "SELECT $found_rows $fields";
            $this->sql_clauses['from'] = "FROM $wpdb->blogs $join";
            $this->sql_clauses['groupby'] = $groupby;
            $this->sql_clauses['orderby'] = $orderby;
            $this->sql_clauses['limits'] = $limits;

            $this->request = "
			{$this->sql_clauses['select']}
			{$this->sql_clauses['from']}
			{$where}
			{$this->sql_clauses['groupby']}
			{$this->sql_clauses['orderby']}
			{$this->sql_clauses['limits']}
		";

            if($this->query_vars['count'])
            {
                return (int) $wpdb->get_var($this->request);
            }

            $site_ids = $wpdb->get_col($this->request);

            return array_map('intval', $site_ids);
        }

        protected function parse_order($order)
        {
            if(! is_string($order) || empty($order))
            {
                return 'ASC';
            }

            if('ASC' === strtoupper($order))
            {
                return 'ASC';
            }
            else
            {
                return 'DESC';
            }
        }

        protected function parse_orderby($orderby)
        {
            global $wpdb;

            $parsed = false;

            switch($orderby)
            {
                case 'site__in':
                    $site__in = implode(',', array_map('absint', $this->query_vars['site__in']));
                    $parsed = "FIELD( {$wpdb->blogs}.blog_id, $site__in )";
                    break;
                case 'network__in':
                    $network__in = implode(',', array_map('absint', $this->query_vars['network__in']));
                    $parsed = "FIELD( {$wpdb->blogs}.site_id, $network__in )";
                    break;
                case 'domain':
                case 'last_updated':
                case 'path':
                case 'registered':
                case 'deleted':
                case 'spam':
                case 'mature':
                case 'archived':
                case 'public':
                    $parsed = $orderby;
                    break;
                case 'network_id':
                    $parsed = 'site_id';
                    break;
                case 'domain_length':
                    $parsed = 'CHAR_LENGTH(domain)';
                    break;
                case 'path_length':
                    $parsed = 'CHAR_LENGTH(path)';
                    break;
                case 'id':
                    $parsed = "{$wpdb->blogs}.blog_id";
                    break;
            }

            if(! empty($parsed) || empty($this->meta_query_clauses))
            {
                return $parsed;
            }

            $meta_clauses = $this->meta_query->get_clauses();
            if(empty($meta_clauses))
            {
                return $parsed;
            }

            $primary_meta_query = reset($meta_clauses);
            if(! empty($primary_meta_query['key']) && $primary_meta_query['key'] === $orderby)
            {
                $orderby = 'meta_value';
            }

            switch($orderby)
            {
                case 'meta_value':
                    if(! empty($primary_meta_query['type']))
                    {
                        $parsed = "CAST({$primary_meta_query['alias']}.meta_value AS {$primary_meta_query['cast']})";
                    }
                    else
                    {
                        $parsed = "{$primary_meta_query['alias']}.meta_value";
                    }
                    break;
                case 'meta_value_num':
                    $parsed = "{$primary_meta_query['alias']}.meta_value+0";
                    break;
                default:
                    if(isset($meta_clauses[$orderby]))
                    {
                        $meta_clause = $meta_clauses[$orderby];
                        $parsed = "CAST({$meta_clause['alias']}.meta_value AS {$meta_clause['cast']})";
                    }
            }

            return $parsed;
        }

        protected function get_search_sql($search, $columns)
        {
            global $wpdb;

            if(str_contains($search, '*'))
            {
                $like = '%'.implode('%', array_map([$wpdb, 'esc_like'], explode('*', $search))).'%';
            }
            else
            {
                $like = '%'.$wpdb->esc_like($search).'%';
            }

            $searches = [];
            foreach($columns as $column)
            {
                $searches[] = $wpdb->prepare("$column LIKE %s", $like);
            }

            return '('.implode(' OR ', $searches).')';
        }

        private function set_found_sites()
        {
            global $wpdb;

            if($this->query_vars['number'] && ! $this->query_vars['no_found_rows'])
            {
                $found_sites_query = apply_filters('found_sites_query', 'SELECT FOUND_ROWS()', $this);

                $this->found_sites = (int) $wpdb->get_var($found_sites_query);
            }
        }
    }
