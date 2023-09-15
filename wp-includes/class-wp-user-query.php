<?php

    #[AllowDynamicProperties]
    class WP_User_Query
    {
        public $query_vars = [];

        public $meta_query = false;

        public $request;

        public $query_fields;

        public $query_from;

        public $query_where;

        // SQL clauses.
        public $query_orderby;

        public $query_limit;

        private $results;

        private $total_users = 0;

        private $compat_fields = ['results', 'total_users'];

        public function __construct($query = null)
        {
            if(! empty($query))
            {
                $this->prepare_query($query);
                $this->query();
            }
        }

        public function prepare_query($query = [])
        {
            global $wpdb, $wp_roles;

            if(empty($this->query_vars) || ! empty($query))
            {
                $this->query_limit = null;
                $this->query_vars = $this->fill_query_vars($query);
            }

            do_action_ref_array('pre_get_users', [&$this]);

            // Ensure that query vars are filled after 'pre_get_users'.
            $qv =& $this->query_vars;
            $qv = $this->fill_query_vars($qv);

            $allowed_fields = [
                'id',
                'user_login',
                'user_pass',
                'user_nicename',
                'user_email',
                'user_url',
                'user_registered',
                'user_activation_key',
                'user_status',
                'display_name',
            ];
            if(is_multisite())
            {
                $allowed_fields[] = 'spam';
                $allowed_fields[] = 'deleted';
            }

            if(is_array($qv['fields']))
            {
                $qv['fields'] = array_map('strtolower', $qv['fields']);
                $qv['fields'] = array_intersect(array_unique($qv['fields']), $allowed_fields);

                if(empty($qv['fields']))
                {
                    $qv['fields'] = ['id'];
                }

                $this->query_fields = [];
                foreach($qv['fields'] as $field)
                {
                    $field = 'id' === $field ? 'ID' : sanitize_key($field);
                    $this->query_fields[] = "$wpdb->users.$field";
                }
                $this->query_fields = implode(',', $this->query_fields);
            }
            elseif('all_with_meta' === $qv['fields'] || 'all' === $qv['fields'] || ! in_array($qv['fields'], $allowed_fields, true))
            {
                $this->query_fields = "$wpdb->users.ID";
            }
            else
            {
                $field = 'id' === strtolower($qv['fields']) ? 'ID' : sanitize_key($qv['fields']);
                $this->query_fields = "$wpdb->users.$field";
            }

            if(isset($qv['count_total']) && $qv['count_total'])
            {
                $this->query_fields = 'SQL_CALC_FOUND_ROWS '.$this->query_fields;
            }

            $this->query_from = "FROM $wpdb->users";
            $this->query_where = 'WHERE 1=1';

            // Parse and sanitize 'include', for use by 'orderby' as well as 'include' below.
            if(! empty($qv['include']))
            {
                $include = wp_parse_id_list($qv['include']);
            }
            else
            {
                $include = false;
            }

            $blog_id = 0;
            if(isset($qv['blog_id']))
            {
                $blog_id = absint($qv['blog_id']);
            }

            if($qv['has_published_posts'] && $blog_id)
            {
                if(true === $qv['has_published_posts'])
                {
                    $post_types = get_post_types(['public' => true]);
                }
                else
                {
                    $post_types = (array) $qv['has_published_posts'];
                }

                foreach($post_types as &$post_type)
                {
                    $post_type = $wpdb->prepare('%s', $post_type);
                }

                $posts_table = $wpdb->get_blog_prefix($blog_id).'posts';
                $this->query_where .= " AND $wpdb->users.ID IN ( SELECT DISTINCT $posts_table.post_author FROM $posts_table WHERE $posts_table.post_status = 'publish' AND $posts_table.post_type IN ( ".implode(', ', $post_types).' ) )';
            }

            // nicename
            if('' !== $qv['nicename'])
            {
                $this->query_where .= $wpdb->prepare(' AND user_nicename = %s', $qv['nicename']);
            }

            if(! empty($qv['nicename__in']))
            {
                $sanitized_nicename__in = array_map('esc_sql', $qv['nicename__in']);
                $nicename__in = implode("','", $sanitized_nicename__in);
                $this->query_where .= " AND user_nicename IN ( '$nicename__in' )";
            }

            if(! empty($qv['nicename__not_in']))
            {
                $sanitized_nicename__not_in = array_map('esc_sql', $qv['nicename__not_in']);
                $nicename__not_in = implode("','", $sanitized_nicename__not_in);
                $this->query_where .= " AND user_nicename NOT IN ( '$nicename__not_in' )";
            }

            // login
            if('' !== $qv['login'])
            {
                $this->query_where .= $wpdb->prepare(' AND user_login = %s', $qv['login']);
            }

            if(! empty($qv['login__in']))
            {
                $sanitized_login__in = array_map('esc_sql', $qv['login__in']);
                $login__in = implode("','", $sanitized_login__in);
                $this->query_where .= " AND user_login IN ( '$login__in' )";
            }

            if(! empty($qv['login__not_in']))
            {
                $sanitized_login__not_in = array_map('esc_sql', $qv['login__not_in']);
                $login__not_in = implode("','", $sanitized_login__not_in);
                $this->query_where .= " AND user_login NOT IN ( '$login__not_in' )";
            }

            // Meta query.
            $this->meta_query = new WP_Meta_Query();
            $this->meta_query->parse_query_vars($qv);

            if(isset($qv['who']) && 'authors' === $qv['who'] && $blog_id)
            {
                _deprecated_argument('WP_User_Query', '5.9.0', sprintf(/* translators: 1: who, 2: capability */ __('%1$s is deprecated. Use %2$s instead.'), '<code>who</code>', '<code>capability</code>'));

                $who_query = [
                    'key' => $wpdb->get_blog_prefix($blog_id).'user_level',
                    'value' => 0,
                    'compare' => '!=',
                ];

                // Prevent extra meta query.
                $qv['blog_id'] = 0;
                $blog_id = 0;

                if(empty($this->meta_query->queries))
                {
                    $this->meta_query->queries = [$who_query];
                }
                else
                {
                    // Append the cap query to the original queries and reparse the query.
                    $this->meta_query->queries = [
                        'relation' => 'AND',
                        [$this->meta_query->queries, $who_query],
                    ];
                }

                $this->meta_query->parse_query_vars($this->meta_query->queries);
            }

            // Roles.
            $roles = [];
            if(isset($qv['role']))
            {
                if(is_array($qv['role']))
                {
                    $roles = $qv['role'];
                }
                elseif(is_string($qv['role']) && ! empty($qv['role']))
                {
                    $roles = array_map('trim', explode(',', $qv['role']));
                }
            }

            $role__in = [];
            if(isset($qv['role__in']))
            {
                $role__in = (array) $qv['role__in'];
            }

            $role__not_in = [];
            if(isset($qv['role__not_in']))
            {
                $role__not_in = (array) $qv['role__not_in'];
            }

            // Capabilities.
            $available_roles = [];

            if(! empty($qv['capability']) || ! empty($qv['capability__in']) || ! empty($qv['capability__not_in']))
            {
                $wp_roles->for_site($blog_id);
                $available_roles = $wp_roles->roles;
            }

            $capabilities = [];
            if(! empty($qv['capability']))
            {
                if(is_array($qv['capability']))
                {
                    $capabilities = $qv['capability'];
                }
                elseif(is_string($qv['capability']))
                {
                    $capabilities = array_map('trim', explode(',', $qv['capability']));
                }
            }

            $capability__in = [];
            if(! empty($qv['capability__in']))
            {
                $capability__in = (array) $qv['capability__in'];
            }

            $capability__not_in = [];
            if(! empty($qv['capability__not_in']))
            {
                $capability__not_in = (array) $qv['capability__not_in'];
            }

            // Keep track of all capabilities and the roles they're added on.
            $caps_with_roles = [];

            foreach($available_roles as $role => $role_data)
            {
                $role_caps = array_keys(array_filter($role_data['capabilities']));

                foreach($capabilities as $cap)
                {
                    if(in_array($cap, $role_caps, true))
                    {
                        $caps_with_roles[$cap][] = $role;
                        break;
                    }
                }

                foreach($capability__in as $cap)
                {
                    if(in_array($cap, $role_caps, true))
                    {
                        $role__in[] = $role;
                        break;
                    }
                }

                foreach($capability__not_in as $cap)
                {
                    if(in_array($cap, $role_caps, true))
                    {
                        $role__not_in[] = $role;
                        break;
                    }
                }
            }

            $role__in = array_merge($role__in, $capability__in);
            $role__not_in = array_merge($role__not_in, $capability__not_in);

            $roles = array_unique($roles);
            $role__in = array_unique($role__in);
            $role__not_in = array_unique($role__not_in);

            // Support querying by capabilities added directly to users.
            if($blog_id && ! empty($capabilities))
            {
                $capabilities_clauses = ['relation' => 'AND'];

                foreach($capabilities as $cap)
                {
                    $clause = ['relation' => 'OR'];

                    $clause[] = [
                        'key' => $wpdb->get_blog_prefix($blog_id).'capabilities',
                        'value' => '"'.$cap.'"',
                        'compare' => 'LIKE',
                    ];

                    if(! empty($caps_with_roles[$cap]))
                    {
                        foreach($caps_with_roles[$cap] as $role)
                        {
                            $clause[] = [
                                'key' => $wpdb->get_blog_prefix($blog_id).'capabilities',
                                'value' => '"'.$role.'"',
                                'compare' => 'LIKE',
                            ];
                        }
                    }

                    $capabilities_clauses[] = $clause;
                }

                $role_queries[] = $capabilities_clauses;

                if(empty($this->meta_query->queries))
                {
                    $this->meta_query->queries[] = $capabilities_clauses;
                }
                else
                {
                    // Append the cap query to the original queries and reparse the query.
                    $this->meta_query->queries = [
                        'relation' => 'AND',
                        [$this->meta_query->queries, [$capabilities_clauses]],
                    ];
                }

                $this->meta_query->parse_query_vars($this->meta_query->queries);
            }

            if($blog_id && (! empty($roles) || ! empty($role__in) || ! empty($role__not_in) || is_multisite()))
            {
                $role_queries = [];

                $roles_clauses = ['relation' => 'AND'];
                if(! empty($roles))
                {
                    foreach($roles as $role)
                    {
                        $roles_clauses[] = [
                            'key' => $wpdb->get_blog_prefix($blog_id).'capabilities',
                            'value' => '"'.$role.'"',
                            'compare' => 'LIKE',
                        ];
                    }

                    $role_queries[] = $roles_clauses;
                }

                $role__in_clauses = ['relation' => 'OR'];
                if(! empty($role__in))
                {
                    foreach($role__in as $role)
                    {
                        $role__in_clauses[] = [
                            'key' => $wpdb->get_blog_prefix($blog_id).'capabilities',
                            'value' => '"'.$role.'"',
                            'compare' => 'LIKE',
                        ];
                    }

                    $role_queries[] = $role__in_clauses;
                }

                $role__not_in_clauses = ['relation' => 'AND'];
                if(! empty($role__not_in))
                {
                    foreach($role__not_in as $role)
                    {
                        $role__not_in_clauses[] = [
                            'key' => $wpdb->get_blog_prefix($blog_id).'capabilities',
                            'value' => '"'.$role.'"',
                            'compare' => 'NOT LIKE',
                        ];
                    }

                    $role_queries[] = $role__not_in_clauses;
                }

                // If there are no specific roles named, make sure the user is a member of the site.
                if(empty($role_queries))
                {
                    $role_queries[] = [
                        'key' => $wpdb->get_blog_prefix($blog_id).'capabilities',
                        'compare' => 'EXISTS',
                    ];
                }

                // Specify that role queries should be joined with AND.
                $role_queries['relation'] = 'AND';

                if(empty($this->meta_query->queries))
                {
                    $this->meta_query->queries = $role_queries;
                }
                else
                {
                    // Append the cap query to the original queries and reparse the query.
                    $this->meta_query->queries = [
                        'relation' => 'AND',
                        [$this->meta_query->queries, $role_queries],
                    ];
                }

                $this->meta_query->parse_query_vars($this->meta_query->queries);
            }

            if(! empty($this->meta_query->queries))
            {
                $clauses = $this->meta_query->get_sql('user', $wpdb->users, 'ID', $this);
                $this->query_from .= $clauses['join'];
                $this->query_where .= $clauses['where'];

                if($this->meta_query->has_or_relation())
                {
                    $this->query_fields = 'DISTINCT '.$this->query_fields;
                }
            }

            // Sorting.
            $qv['order'] = isset($qv['order']) ? strtoupper($qv['order']) : '';
            $order = $this->parse_order($qv['order']);

            if(empty($qv['orderby']))
            {
                // Default order is by 'user_login'.
                $ordersby = ['user_login' => $order];
            }
            elseif(is_array($qv['orderby']))
            {
                $ordersby = $qv['orderby'];
            }
            else
            {
                // 'orderby' values may be a comma- or space-separated list.
                $ordersby = preg_split('/[,\s]+/', $qv['orderby']);
            }

            $orderby_array = [];
            foreach($ordersby as $_key => $_value)
            {
                if(! $_value)
                {
                    continue;
                }

                if(is_int($_key))
                {
                    // Integer key means this is a flat array of 'orderby' fields.
                    $_orderby = $_value;
                    $_order = $order;
                }
                else
                {
                    // Non-integer key means this the key is the field and the value is ASC/DESC.
                    $_orderby = $_key;
                    $_order = $_value;
                }

                $parsed = $this->parse_orderby($_orderby);

                if(! $parsed)
                {
                    continue;
                }

                if('nicename__in' === $_orderby || 'login__in' === $_orderby)
                {
                    $orderby_array[] = $parsed;
                }
                else
                {
                    $orderby_array[] = $parsed.' '.$this->parse_order($_order);
                }
            }

            // If no valid clauses were found, order by user_login.
            if(empty($orderby_array))
            {
                $orderby_array[] = "user_login $order";
            }

            $this->query_orderby = 'ORDER BY '.implode(', ', $orderby_array);

            // Limit.
            if(isset($qv['number']) && $qv['number'] > 0)
            {
                if($qv['offset'])
                {
                    $this->query_limit = $wpdb->prepare('LIMIT %d, %d', $qv['offset'], $qv['number']);
                }
                else
                {
                    $this->query_limit = $wpdb->prepare('LIMIT %d, %d', $qv['number'] * ($qv['paged'] - 1), $qv['number']);
                }
            }

            $search = '';
            if(isset($qv['search']))
            {
                $search = trim($qv['search']);
            }

            if($search)
            {
                $leading_wild = (ltrim($search, '*') !== $search);
                $trailing_wild = (rtrim($search, '*') !== $search);
                if($leading_wild && $trailing_wild)
                {
                    $wild = 'both';
                }
                elseif($leading_wild)
                {
                    $wild = 'leading';
                }
                elseif($trailing_wild)
                {
                    $wild = 'trailing';
                }
                else
                {
                    $wild = false;
                }
                if($wild)
                {
                    $search = trim($search, '*');
                }

                $search_columns = [];
                if($qv['search_columns'])
                {
                    $search_columns = array_intersect($qv['search_columns'], [
                        'ID',
                        'user_login',
                        'user_email',
                        'user_url',
                        'user_nicename',
                        'display_name'
                    ]);
                }
                if(! $search_columns)
                {
                    if(str_contains($search, '@'))
                    {
                        $search_columns = ['user_email'];
                    }
                    elseif(is_numeric($search))
                    {
                        $search_columns = ['user_login', 'ID'];
                    }
                    elseif(preg_match('|^https?://|', $search) && ! (is_multisite() && wp_is_large_network('users')))
                    {
                        $search_columns = ['user_url'];
                    }
                    else
                    {
                        $search_columns = ['user_login', 'user_url', 'user_email', 'user_nicename', 'display_name'];
                    }
                }

                $search_columns = apply_filters('user_search_columns', $search_columns, $search, $this);

                $this->query_where .= $this->get_search_sql($search, $search_columns, $wild);
            }

            if(! empty($include))
            {
                // Sanitized earlier.
                $ids = implode(',', $include);
                $this->query_where .= " AND $wpdb->users.ID IN ($ids)";
            }
            elseif(! empty($qv['exclude']))
            {
                $ids = implode(',', wp_parse_id_list($qv['exclude']));
                $this->query_where .= " AND $wpdb->users.ID NOT IN ($ids)";
            }

            // Date queries are allowed for the user_registered field.
            if(! empty($qv['date_query']) && is_array($qv['date_query']))
            {
                $date_query = new WP_Date_Query($qv['date_query'], 'user_registered');
                $this->query_where .= $date_query->get_sql();
            }

            do_action_ref_array('pre_user_query', [&$this]);
        }

        public static function fill_query_vars($args)
        {
            $defaults = [
                'blog_id' => get_current_blog_id(),
                'role' => '',
                'role__in' => [],
                'role__not_in' => [],
                'capability' => '',
                'capability__in' => [],
                'capability__not_in' => [],
                'meta_key' => '',
                'meta_value' => '',
                'meta_compare' => '',
                'include' => [],
                'exclude' => [],
                'search' => '',
                'search_columns' => [],
                'orderby' => 'login',
                'order' => 'ASC',
                'offset' => '',
                'number' => '',
                'paged' => 1,
                'count_total' => true,
                'fields' => 'all',
                'who' => '',
                'has_published_posts' => null,
                'nicename' => '',
                'nicename__in' => [],
                'nicename__not_in' => [],
                'login' => '',
                'login__in' => [],
                'login__not_in' => [],
                'cache_results' => true,
            ];

            return wp_parse_args($args, $defaults);
        }

        protected function parse_order($order)
        {
            if(! is_string($order) || empty($order))
            {
                return 'DESC';
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

            $meta_query_clauses = $this->meta_query->get_clauses();

            $_orderby = '';
            if(in_array($orderby, ['login', 'nicename', 'email', 'url', 'registered'], true))
            {
                $_orderby = 'user_'.$orderby;
            }
            elseif(
                in_array($orderby, [
                    'user_login',
                    'user_nicename',
                    'user_email',
                    'user_url',
                    'user_registered'
                ],       true)
            )
            {
                $_orderby = $orderby;
            }
            elseif('name' === $orderby || 'display_name' === $orderby)
            {
                $_orderby = 'display_name';
            }
            elseif('post_count' === $orderby)
            {
                // @todo Avoid the JOIN.
                $where = get_posts_by_author_sql('post');
                $this->query_from .= " LEFT OUTER JOIN (
				SELECT post_author, COUNT(*) as post_count
				FROM $wpdb->posts
				$where
				GROUP BY post_author
			) p ON ({$wpdb->users}.ID = p.post_author)";
                $_orderby = 'post_count';
            }
            elseif('ID' === $orderby || 'id' === $orderby)
            {
                $_orderby = 'ID';
            }
            elseif('meta_value' === $orderby || $this->get('meta_key') === $orderby)
            {
                $_orderby = "$wpdb->usermeta.meta_value";
            }
            elseif('meta_value_num' === $orderby)
            {
                $_orderby = "$wpdb->usermeta.meta_value+0";
            }
            elseif('include' === $orderby && ! empty($this->query_vars['include']))
            {
                $include = wp_parse_id_list($this->query_vars['include']);
                $include_sql = implode(',', $include);
                $_orderby = "FIELD( $wpdb->users.ID, $include_sql )";
            }
            elseif('nicename__in' === $orderby)
            {
                $sanitized_nicename__in = array_map('esc_sql', $this->query_vars['nicename__in']);
                $nicename__in = implode("','", $sanitized_nicename__in);
                $_orderby = "FIELD( user_nicename, '$nicename__in' )";
            }
            elseif('login__in' === $orderby)
            {
                $sanitized_login__in = array_map('esc_sql', $this->query_vars['login__in']);
                $login__in = implode("','", $sanitized_login__in);
                $_orderby = "FIELD( user_login, '$login__in' )";
            }
            elseif(isset($meta_query_clauses[$orderby]))
            {
                $meta_clause = $meta_query_clauses[$orderby];
                $_orderby = sprintf('CAST(%s.meta_value AS %s)', esc_sql($meta_clause['alias']), esc_sql($meta_clause['cast']));
            }

            return $_orderby;
        }

        public function get($query_var)
        {
            if(isset($this->query_vars[$query_var]))
            {
                return $this->query_vars[$query_var];
            }

            return null;
        }

        protected function get_search_sql($search, $columns, $wild = false)
        {
            global $wpdb;

            $searches = [];
            $leading_wild = ('leading' === $wild || 'both' === $wild) ? '%' : '';
            $trailing_wild = ('trailing' === $wild || 'both' === $wild) ? '%' : '';
            $like = $leading_wild.$wpdb->esc_like($search).$trailing_wild;

            foreach($columns as $column)
            {
                if('ID' === $column)
                {
                    $searches[] = $wpdb->prepare("$column = %s", $search);
                }
                else
                {
                    $searches[] = $wpdb->prepare("$column LIKE %s", $like);
                }
            }

            return ' AND ('.implode(' OR ', $searches).')';
        }

        public function query()
        {
            global $wpdb;

            if(! did_action('plugins_loaded'))
            {
                _doing_it_wrong('WP_User_Query::query', sprintf(/* translators: %s: plugins_loaded */ __('User queries should not be run before the %s hook.'), '<code>plugins_loaded</code>'), '6.1.1');
            }

            $qv =& $this->query_vars;

            // Do not cache results if more than 3 fields are requested.
            if(is_array($qv['fields']) && count($qv['fields']) > 3)
            {
                $qv['cache_results'] = false;
            }

            $this->results = apply_filters_ref_array('users_pre_query', [null, &$this]);

            if(null === $this->results)
            {
                $this->request = "
				SELECT {$this->query_fields}
				{$this->query_from}
				{$this->query_where}
				{$this->query_orderby}
				{$this->query_limit}
			";
                $cache_value = false;
                $cache_key = $this->generate_cache_key($qv, $this->request);
                $cache_group = 'user-queries';
                if($qv['cache_results'])
                {
                    $cache_value = wp_cache_get($cache_key, $cache_group);
                }
                if(false !== $cache_value)
                {
                    $this->results = $cache_value['user_data'];
                    $this->total_users = $cache_value['total_users'];
                }
                else
                {
                    if(is_array($qv['fields']))
                    {
                        $this->results = $wpdb->get_results($this->request);
                    }
                    else
                    {
                        $this->results = $wpdb->get_col($this->request);
                    }

                    if(isset($qv['count_total']) && $qv['count_total'])
                    {
                        $found_users_query = apply_filters('found_users_query', 'SELECT FOUND_ROWS()', $this);

                        $this->total_users = (int) $wpdb->get_var($found_users_query);
                    }

                    if($qv['cache_results'])
                    {
                        $cache_value = [
                            'user_data' => $this->results,
                            'total_users' => $this->total_users,
                        ];
                        wp_cache_add($cache_key, $cache_value, $cache_group);
                    }
                }
            }

            if(! $this->results)
            {
                return;
            }
            if(is_array($qv['fields']) && isset($this->results[0]->ID))
            {
                foreach($this->results as $result)
                {
                    $result->id = $result->ID;
                }
            }
            elseif('all_with_meta' === $qv['fields'] || 'all' === $qv['fields'])
            {
                if(function_exists('cache_users'))
                {
                    cache_users($this->results);
                }

                $r = [];
                foreach($this->results as $userid)
                {
                    if('all_with_meta' === $qv['fields'])
                    {
                        $r[$userid] = new WP_User($userid, '', $qv['blog_id']);
                    }
                    else
                    {
                        $r[] = new WP_User($userid, '', $qv['blog_id']);
                    }
                }

                $this->results = $r;
            }
        }

        protected function generate_cache_key(array $args, $sql)
        {
            global $wpdb;

            // Replace wpdb placeholder in the SQL statement used by the cache key.
            $sql = $wpdb->remove_placeholder_escape($sql);

            $key = md5($sql);
            $last_changed = wp_cache_get_last_changed('users');

            if(empty($args['orderby']))
            {
                // Default order is by 'user_login'.
                $ordersby = ['user_login' => ''];
            }
            elseif(is_array($args['orderby']))
            {
                $ordersby = $args['orderby'];
            }
            else
            {
                // 'orderby' values may be a comma- or space-separated list.
                $ordersby = preg_split('/[,\s]+/', $args['orderby']);
            }

            $blog_id = 0;
            if(isset($args['blog_id']))
            {
                $blog_id = absint($args['blog_id']);
            }

            if($args['has_published_posts'] || in_array('post_count', $ordersby, true))
            {
                $switch = $blog_id && get_current_blog_id() !== $blog_id;
                if($switch)
                {
                    switch_to_blog($blog_id);
                }

                $last_changed .= wp_cache_get_last_changed('posts');

                if($switch)
                {
                    restore_current_blog();
                }
            }

            return "get_users:$key:$last_changed";
        }

        public function get_results()
        {
            return $this->results;
        }

        public function set($query_var, $value)
        {
            $this->query_vars[$query_var] = $value;
        }

        public function get_total()
        {
            return $this->total_users;
        }

        public function __get($name)
        {
            if(in_array($name, $this->compat_fields, true))
            {
                return $this->$name;
            }

            wp_trigger_error(__METHOD__, "The property `{$name}` is not declared. Getting a dynamic property is ".'deprecated since version 6.4.0! Instead, declare the property on the class.', E_USER_DEPRECATED);

            return null;
        }

        public function __set($name, $value)
        {
            if(in_array($name, $this->compat_fields, true))
            {
                $this->$name = $value;

                return;
            }

            wp_trigger_error(__METHOD__, "The property `{$name}` is not declared. Setting a dynamic property is ".'deprecated since version 6.4.0! Instead, declare the property on the class.', E_USER_DEPRECATED);
        }

        public function __isset($name)
        {
            if(in_array($name, $this->compat_fields, true))
            {
                return isset($this->$name);
            }

            wp_trigger_error(__METHOD__, "The property `{$name}` is not declared. Checking `isset()` on a dynamic property ".'is deprecated since version 6.4.0! Instead, declare the property on the class.', E_USER_DEPRECATED);

            return false;
        }

        public function __unset($name)
        {
            if(in_array($name, $this->compat_fields, true))
            {
                unset($this->$name);

                return;
            }

            wp_trigger_error(__METHOD__, "A property `{$name}` is not declared. Unsetting a dynamic property is ".'deprecated since version 6.4.0! Instead, declare the property on the class.', E_USER_DEPRECATED);
        }

        public function __call($name, $arguments)
        {
            if('get_search_sql' === $name)
            {
                return $this->get_search_sql(...$arguments);
            }

            return false;
        }
    }
