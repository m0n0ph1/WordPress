<?php

    #[AllowDynamicProperties]
    class WP_Network_Query
    {
        public $request;

        public $query_vars;

        public $query_var_defaults;

        public $networks;

        public $found_networks = 0;

        public $max_num_pages = 0;

        protected $sql_clauses = [
            'select' => '',
            'from' => '',
            'where' => [],
            'groupby' => '',
            'orderby' => '',
            'limits' => '',
        ];

        public function __construct($query = '')
        {
            $this->query_var_defaults = [
                'network__in' => '',
                'network__not_in' => '',
                'count' => false,
                'fields' => '',
                'number' => '',
                'offset' => '',
                'no_found_rows' => true,
                'orderby' => 'id',
                'order' => 'ASC',
                'domain' => '',
                'domain__in' => '',
                'domain__not_in' => '',
                'path' => '',
                'path__in' => '',
                'path__not_in' => '',
                'search' => '',
                'update_network_cache' => true,
            ];

            if(! empty($query))
            {
                $this->query($query);
            }
        }

        public function query($query)
        {
            $this->query_vars = wp_parse_args($query);

            return $this->get_networks();
        }

        public function get_networks()
        {
            $this->parse_query();

            do_action_ref_array('pre_get_networks', [&$this]);

            $network_data = null;

            $network_data = apply_filters_ref_array('networks_pre_query', [$network_data, &$this]);

            if(null !== $network_data)
            {
                if(is_array($network_data) && ! $this->query_vars['count'])
                {
                    $this->networks = $network_data;
                }

                return $network_data;
            }

            // $args can include anything. Only use the args defined in the query_var_defaults to compute the key.
            $_args = wp_array_slice_assoc($this->query_vars, array_keys($this->query_var_defaults));

            // Ignore the $fields, $update_network_cache arguments as the queried result will be the same regardless.
            unset($_args['fields'], $_args['update_network_cache']);

            $key = md5(serialize($_args));
            $last_changed = wp_cache_get_last_changed('networks');

            $cache_key = "get_network_ids:$key:$last_changed";
            $cache_value = wp_cache_get($cache_key, 'network-queries');

            if(false !== $cache_value)
            {
                $network_ids = $cache_value['network_ids'];
                $this->found_networks = $cache_value['found_networks'];
            }
            else
            {
                $network_ids = $this->get_network_ids();
                if($network_ids)
                {
                    $this->set_found_networks();
                }

                $cache_value = [
                    'network_ids' => $network_ids,
                    'found_networks' => $this->found_networks,
                ];
                wp_cache_add($cache_key, $cache_value, 'network-queries');
            }

            if($this->found_networks && $this->query_vars['number'])
            {
                $this->max_num_pages = ceil($this->found_networks / $this->query_vars['number']);
            }

            // If querying for a count only, there's nothing more to do.
            if($this->query_vars['count'])
            {
                // $network_ids is actually a count in this case.
                return (int) $network_ids;
            }

            $network_ids = array_map('intval', $network_ids);

            if('ids' === $this->query_vars['fields'])
            {
                $this->networks = $network_ids;

                return $this->networks;
            }

            if($this->query_vars['update_network_cache'])
            {
                _prime_network_caches($network_ids);
            }

            // Fetch full network objects from the primed cache.
            $_networks = [];
            foreach($network_ids as $network_id)
            {
                $_network = get_network($network_id);
                if($_network)
                {
                    $_networks[] = $_network;
                }
            }

            $_networks = apply_filters_ref_array('the_networks', [$_networks, &$this]);

            // Convert to WP_Network instances.
            $this->networks = array_map('get_network', $_networks);

            return $this->networks;
        }

        public function parse_query($query = '')
        {
            if(empty($query))
            {
                $query = $this->query_vars;
            }

            $this->query_vars = wp_parse_args($query, $this->query_var_defaults);

            do_action_ref_array('parse_network_query', [&$this]);
        }

        protected function get_network_ids()
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

                    if('network__in' === $_orderby)
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
                $orderby = "$wpdb->site.id $order";
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
                $fields = "$wpdb->site.id";
            }

            // Parse network IDs for an IN clause.
            if(! empty($this->query_vars['network__in']))
            {
                $this->sql_clauses['where']['network__in'] = "$wpdb->site.id IN ( ".implode(',', wp_parse_id_list($this->query_vars['network__in'])).' )';
            }

            // Parse network IDs for a NOT IN clause.
            if(! empty($this->query_vars['network__not_in']))
            {
                $this->sql_clauses['where']['network__not_in'] = "$wpdb->site.id NOT IN ( ".implode(',', wp_parse_id_list($this->query_vars['network__not_in'])).' )';
            }

            if(! empty($this->query_vars['domain']))
            {
                $this->sql_clauses['where']['domain'] = $wpdb->prepare("$wpdb->site.domain = %s", $this->query_vars['domain']);
            }

            // Parse network domain for an IN clause.
            if(is_array($this->query_vars['domain__in']))
            {
                $this->sql_clauses['where']['domain__in'] = "$wpdb->site.domain IN ( '".implode("', '", $wpdb->_escape($this->query_vars['domain__in']))."' )";
            }

            // Parse network domain for a NOT IN clause.
            if(is_array($this->query_vars['domain__not_in']))
            {
                $this->sql_clauses['where']['domain__not_in'] = "$wpdb->site.domain NOT IN ( '".implode("', '", $wpdb->_escape($this->query_vars['domain__not_in']))."' )";
            }

            if(! empty($this->query_vars['path']))
            {
                $this->sql_clauses['where']['path'] = $wpdb->prepare("$wpdb->site.path = %s", $this->query_vars['path']);
            }

            // Parse network path for an IN clause.
            if(is_array($this->query_vars['path__in']))
            {
                $this->sql_clauses['where']['path__in'] = "$wpdb->site.path IN ( '".implode("', '", $wpdb->_escape($this->query_vars['path__in']))."' )";
            }

            // Parse network path for a NOT IN clause.
            if(is_array($this->query_vars['path__not_in']))
            {
                $this->sql_clauses['where']['path__not_in'] = "$wpdb->site.path NOT IN ( '".implode("', '", $wpdb->_escape($this->query_vars['path__not_in']))."' )";
            }

            // Falsey search strings are ignored.
            if($this->query_vars['search'] != '')
            {
                $this->sql_clauses['where']['search'] = $this->get_search_sql($this->query_vars['search'], [
                    "$wpdb->site.domain",
                    "$wpdb->site.path"
                ]);
            }

            $join = '';

            $where = implode(' AND ', $this->sql_clauses['where']);

            $groupby = '';

            $pieces = ['fields', 'join', 'where', 'orderby', 'limits', 'groupby'];

            $clauses = apply_filters_ref_array('networks_clauses', [compact('pieces'), &$this]);

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
            $this->sql_clauses['from'] = "FROM $wpdb->site $join";
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

            $network_ids = $wpdb->get_col($this->request);

            return array_map('intval', $network_ids);
        }

        protected function parse_order($order)
        {
            if(! is_string($order) || empty($order) || 'ASC' === strtoupper($order))
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

            $allowed_keys = [
                'id',
                'domain',
                'path',
            ];

            $parsed = false;
            if('network__in' === $orderby)
            {
                $network__in = implode(',', array_map('absint', $this->query_vars['network__in']));
                $parsed = "FIELD( {$wpdb->site}.id, $network__in )";
            }
            elseif('domain_length' === $orderby || 'path_length' === $orderby)
            {
                $field = substr($orderby, 0, -7);
                $parsed = "CHAR_LENGTH($wpdb->site.$field)";
            }
            elseif(in_array($orderby, $allowed_keys, true))
            {
                $parsed = "$wpdb->site.$orderby";
            }

            return $parsed;
        }

        protected function get_search_sql($search, $columns)
        {
            global $wpdb;

            $like = '%'.$wpdb->esc_like($search).'%';

            $searches = [];
            foreach($columns as $column)
            {
                $searches[] = $wpdb->prepare("$column LIKE %s", $like);
            }

            return '('.implode(' OR ', $searches).')';
        }

        private function set_found_networks()
        {
            global $wpdb;

            if($this->query_vars['number'] && ! $this->query_vars['no_found_rows'])
            {
                $found_networks_query = apply_filters('found_networks_query', 'SELECT FOUND_ROWS()', $this);

                $this->found_networks = (int) $wpdb->get_var($found_networks_query);
            }
        }
    }
