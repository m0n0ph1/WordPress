<?php

    #[AllowDynamicProperties]
    class WP_Term_Query
    {
        public $request;

        public $meta_query = false;

        public $query_vars;

        public $query_var_defaults;

        public $terms;

        protected $meta_query_clauses;

        protected $sql_clauses = [
            'select' => '',
            'from' => '',
            'where' => [],
            'orderby' => '',
            'limits' => '',
        ];

        public function __construct($query = '')
        {
            $this->query_var_defaults = [
                'taxonomy' => null,
                'object_ids' => null,
                'orderby' => 'name',
                'order' => 'ASC',
                'hide_empty' => true,
                'include' => [],
                'exclude' => [],
                'exclude_tree' => [],
                'number' => '',
                'offset' => '',
                'fields' => 'all',
                'count' => false,
                'name' => '',
                'slug' => '',
                'term_taxonomy_id' => '',
                'hierarchical' => true,
                'search' => '',
                'name__like' => '',
                'description__like' => '',
                'pad_counts' => false,
                'get' => '',
                'child_of' => 0,
                'parent' => '',
                'childless' => false,
                'cache_domain' => 'core',
                'cache_results' => true,
                'update_term_meta_cache' => true,
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

            return $this->get_terms();
        }

        public function get_terms()
        {
            global $wpdb;

            $this->parse_query($this->query_vars);
            $args = &$this->query_vars;

            // Set up meta_query so it's available to 'pre_get_terms'.
            $this->meta_query = new WP_Meta_Query();
            $this->meta_query->parse_query_vars($args);

            do_action_ref_array('pre_get_terms', [&$this]);

            $taxonomies = (array) $args['taxonomy'];

            // Save queries by not crawling the tree in the case of multiple taxes or a flat tax.
            $has_hierarchical_tax = false;
            if($taxonomies)
            {
                foreach($taxonomies as $_tax)
                {
                    if(is_taxonomy_hierarchical($_tax))
                    {
                        $has_hierarchical_tax = true;
                    }
                }
            }
            else
            {
                // When no taxonomies are provided, assume we have to descend the tree.
                $has_hierarchical_tax = true;
            }

            if(! $has_hierarchical_tax)
            {
                $args['hierarchical'] = false;
                $args['pad_counts'] = false;
            }

            // 'parent' overrides 'child_of'.
            if(0 < (int) $args['parent'])
            {
                $args['child_of'] = false;
            }

            if('all' === $args['get'])
            {
                $args['childless'] = false;
                $args['child_of'] = 0;
                $args['hide_empty'] = 0;
                $args['hierarchical'] = false;
                $args['pad_counts'] = false;
            }

            $args = apply_filters('get_terms_args', $args, $taxonomies);

            // Avoid the query if the queried parent/child_of term has no descendants.
            $child_of = $args['child_of'];
            $parent = $args['parent'];

            if($child_of)
            {
                $_parent = $child_of;
            }
            elseif($parent)
            {
                $_parent = $parent;
            }
            else
            {
                $_parent = false;
            }

            if($_parent)
            {
                $in_hierarchy = false;
                foreach($taxonomies as $_tax)
                {
                    $hierarchy = _get_term_hierarchy($_tax);

                    if(isset($hierarchy[$_parent]))
                    {
                        $in_hierarchy = true;
                    }
                }

                if(! $in_hierarchy)
                {
                    if('count' === $args['fields'])
                    {
                        return 0;
                    }
                    else
                    {
                        $this->terms = [];

                        return $this->terms;
                    }
                }
            }

            // 'term_order' is a legal sort order only when joining the relationship table.
            $_orderby = $this->query_vars['orderby'];
            if('term_order' === $_orderby && empty($this->query_vars['object_ids']))
            {
                $_orderby = 'term_id';
            }

            $orderby = $this->parse_orderby($_orderby);

            if($orderby)
            {
                $orderby = "ORDER BY $orderby";
            }

            $order = $this->parse_order($this->query_vars['order']);

            if($taxonomies)
            {
                $this->sql_clauses['where']['taxonomy'] = "tt.taxonomy IN ('".implode("', '", array_map('esc_sql', $taxonomies))."')";
            }

            if(empty($args['exclude']))
            {
                $args['exclude'] = [];
            }

            if(empty($args['include']))
            {
                $args['include'] = [];
            }

            $exclude = $args['exclude'];
            $exclude_tree = $args['exclude_tree'];
            $include = $args['include'];

            $inclusions = '';
            if(! empty($include))
            {
                $exclude = '';
                $exclude_tree = '';
                $inclusions = implode(',', wp_parse_id_list($include));
            }

            if(! empty($inclusions))
            {
                $this->sql_clauses['where']['inclusions'] = 't.term_id IN ( '.$inclusions.' )';
            }

            $exclusions = [];
            if(! empty($exclude_tree))
            {
                $exclude_tree = wp_parse_id_list($exclude_tree);
                $excluded_children = $exclude_tree;

                foreach($exclude_tree as $extrunk)
                {
                    $excluded_children = array_merge(
                        $excluded_children, (array) get_terms([
                                                                  'taxonomy' => reset($taxonomies),
                                                                  'child_of' => (int) $extrunk,
                                                                  'fields' => 'ids',
                                                                  'hide_empty' => 0,
                                                              ])
                    );
                }

                $exclusions = array_merge($excluded_children, $exclusions);
            }

            if(! empty($exclude))
            {
                $exclusions = array_merge(wp_parse_id_list($exclude), $exclusions);
            }

            // 'childless' terms are those without an entry in the flattened term hierarchy.
            $childless = (bool) $args['childless'];
            if($childless)
            {
                foreach($taxonomies as $_tax)
                {
                    $term_hierarchy = _get_term_hierarchy($_tax);
                    $exclusions = array_merge(array_keys($term_hierarchy), $exclusions);
                }
            }

            if(! empty($exclusions))
            {
                $exclusions = 't.term_id NOT IN ('.implode(',', array_map('intval', $exclusions)).')';
            }
            else
            {
                $exclusions = '';
            }

            $exclusions = apply_filters('list_terms_exclusions', $exclusions, $args, $taxonomies);

            if(! empty($exclusions))
            {
                // Strip leading 'AND'. Must do string manipulation here for backward compatibility with filter.
                $this->sql_clauses['where']['exclusions'] = preg_replace('/^\s*AND\s*/', '', $exclusions);
            }

            if('' === $args['name'])
            {
                $args['name'] = [];
            }
            else
            {
                $args['name'] = (array) $args['name'];
            }

            if(! empty($args['name']))
            {
                $names = $args['name'];

                foreach($names as &$_name)
                {
                    // `sanitize_term_field()` returns slashed data.
                    $_name = stripslashes(sanitize_term_field('name', $_name, 0, reset($taxonomies), 'db'));
                }

                $this->sql_clauses['where']['name'] = "t.name IN ('".implode("', '", array_map('esc_sql', $names))."')";
            }

            if('' === $args['slug'])
            {
                $args['slug'] = [];
            }
            else
            {
                $args['slug'] = array_map('sanitize_title', (array) $args['slug']);
            }

            if(! empty($args['slug']))
            {
                $slug = implode("', '", $args['slug']);

                $this->sql_clauses['where']['slug'] = "t.slug IN ('".$slug."')";
            }

            if('' === $args['term_taxonomy_id'])
            {
                $args['term_taxonomy_id'] = [];
            }
            else
            {
                $args['term_taxonomy_id'] = array_map('intval', (array) $args['term_taxonomy_id']);
            }

            if(! empty($args['term_taxonomy_id']))
            {
                $tt_ids = implode(',', $args['term_taxonomy_id']);

                $this->sql_clauses['where']['term_taxonomy_id'] = "tt.term_taxonomy_id IN ({$tt_ids})";
            }

            if(! empty($args['name__like']))
            {
                $this->sql_clauses['where']['name__like'] = $wpdb->prepare('t.name LIKE %s', '%'.$wpdb->esc_like($args['name__like']).'%');
            }

            if(! empty($args['description__like']))
            {
                $this->sql_clauses['where']['description__like'] = $wpdb->prepare('tt.description LIKE %s', '%'.$wpdb->esc_like($args['description__like']).'%');
            }

            if('' === $args['object_ids'])
            {
                $args['object_ids'] = [];
            }
            else
            {
                $args['object_ids'] = array_map('intval', (array) $args['object_ids']);
            }

            if(! empty($args['object_ids']))
            {
                $object_ids = implode(', ', $args['object_ids']);

                $this->sql_clauses['where']['object_ids'] = "tr.object_id IN ($object_ids)";
            }

            /*
             * When querying for object relationships, the 'count > 0' check
             * added by 'hide_empty' is superfluous.
             */
            if(! empty($args['object_ids']))
            {
                $args['hide_empty'] = false;
            }

            if('' !== $parent)
            {
                $parent = (int) $parent;
                $this->sql_clauses['where']['parent'] = "tt.parent = '$parent'";
            }

            $hierarchical = $args['hierarchical'];
            if('count' === $args['fields'])
            {
                $hierarchical = false;
            }
            if($args['hide_empty'] && ! $hierarchical)
            {
                $this->sql_clauses['where']['count'] = 'tt.count > 0';
            }

            $number = $args['number'];
            $offset = $args['offset'];

            // Don't limit the query results when we have to descend the family tree.
            if($number && ! $hierarchical && ! $child_of && '' === $parent)
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
            else
            {
                $limits = '';
            }

            if(! empty($args['search']))
            {
                $this->sql_clauses['where']['search'] = $this->get_search_sql($args['search']);
            }

            // Meta query support.
            $join = '';
            $distinct = '';

            // Reparse meta_query query_vars, in case they were modified in a 'pre_get_terms' callback.
            $this->meta_query->parse_query_vars($this->query_vars);
            $mq_sql = $this->meta_query->get_sql('term', 't', 'term_id');
            $meta_clauses = $this->meta_query->get_clauses();

            if(! empty($meta_clauses))
            {
                $join .= $mq_sql['join'];

                // Strip leading 'AND'.
                $this->sql_clauses['where']['meta_query'] = preg_replace('/^\s*AND\s*/', '', $mq_sql['where']);

                $distinct .= 'DISTINCT';
            }

            $selects = [];
            switch($args['fields'])
            {
                case 'count':
                    $orderby = '';
                    $order = '';
                    $selects = ['COUNT(*)'];
                    break;
                default:
                    $selects = ['t.term_id'];
                    if('all_with_object_id' === $args['fields'] && ! empty($args['object_ids']))
                    {
                        $selects[] = 'tr.object_id';
                    }
                    break;
            }

            $_fields = $args['fields'];

            $fields = implode(', ', apply_filters('get_terms_fields', $selects, $args, $taxonomies));

            $join .= " INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id";

            if(! empty($this->query_vars['object_ids']))
            {
                $join .= " INNER JOIN {$wpdb->term_relationships} AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id";
                $distinct = 'DISTINCT';
            }

            $where = implode(' AND ', $this->sql_clauses['where']);

            $pieces = ['fields', 'join', 'where', 'distinct', 'orderby', 'order', 'limits'];

            $clauses = apply_filters('terms_clauses', compact('pieces'), $taxonomies, $args);

            $fields = isset($clauses['fields']) ? $clauses['fields'] : '';
            $join = isset($clauses['join']) ? $clauses['join'] : '';
            $where = isset($clauses['where']) ? $clauses['where'] : '';
            $distinct = isset($clauses['distinct']) ? $clauses['distinct'] : '';
            $orderby = isset($clauses['orderby']) ? $clauses['orderby'] : '';
            $order = isset($clauses['order']) ? $clauses['order'] : '';
            $limits = isset($clauses['limits']) ? $clauses['limits'] : '';

            $fields_is_filtered = implode(', ', $selects) !== $fields;

            if($where)
            {
                $where = "WHERE $where";
            }

            $this->sql_clauses['select'] = "SELECT $distinct $fields";
            $this->sql_clauses['from'] = "FROM $wpdb->terms AS t $join";
            $this->sql_clauses['orderby'] = $orderby ? "$orderby $order" : '';
            $this->sql_clauses['limits'] = $limits;

            $this->request = "
			{$this->sql_clauses['select']}
			{$this->sql_clauses['from']}
			{$where}
			{$this->sql_clauses['orderby']}
			{$this->sql_clauses['limits']}
		";

            $this->terms = null;

            $this->terms = apply_filters_ref_array('terms_pre_query', [$this->terms, &$this]);

            if(null !== $this->terms)
            {
                return $this->terms;
            }

            if($args['cache_results'])
            {
                $cache_key = $this->generate_cache_key($args, $this->request);
                $cache = wp_cache_get($cache_key, 'term-queries');

                if(false !== $cache)
                {
                    if('ids' === $_fields)
                    {
                        $cache = array_map('intval', $cache);
                    }
                    elseif('count' !== $_fields)
                    {
                        if(('all_with_object_id' === $_fields && ! empty($args['object_ids'])) || ('all' === $_fields && $args['pad_counts'] || $fields_is_filtered))
                        {
                            $term_ids = wp_list_pluck($cache, 'term_id');
                        }
                        else
                        {
                            $term_ids = array_map('intval', $cache);
                        }

                        _prime_term_caches($term_ids, $args['update_term_meta_cache']);

                        $term_objects = $this->populate_terms($cache);
                        $cache = $this->format_terms($term_objects, $_fields);
                    }

                    $this->terms = $cache;

                    return $this->terms;
                }
            }

            if('count' === $_fields)
            {
                $count = $wpdb->get_var($this->request); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                if($args['cache_results'])
                {
                    wp_cache_set($cache_key, $count, 'term-queries');
                }

                return $count;
            }

            $terms = $wpdb->get_results($this->request); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

            if(empty($terms))
            {
                if($args['cache_results'])
                {
                    wp_cache_add($cache_key, [], 'term-queries');
                }

                return [];
            }

            $term_ids = wp_list_pluck($terms, 'term_id');
            _prime_term_caches($term_ids, false);
            $term_objects = $this->populate_terms($terms);

            if($child_of)
            {
                foreach($taxonomies as $_tax)
                {
                    $children = _get_term_hierarchy($_tax);
                    if(! empty($children))
                    {
                        $term_objects = _get_term_children($child_of, $term_objects, $_tax);
                    }
                }
            }

            // Update term counts to include children.
            if($args['pad_counts'] && 'all' === $_fields)
            {
                foreach($taxonomies as $_tax)
                {
                    _pad_term_counts($term_objects, $_tax);
                }
            }

            // Make sure we show empty categories that have children.
            if($hierarchical && $args['hide_empty'] && is_array($term_objects))
            {
                foreach($term_objects as $k => $term)
                {
                    if(! $term->count)
                    {
                        $children = get_term_children($term->term_id, $term->taxonomy);

                        if(is_array($children))
                        {
                            foreach($children as $child_id)
                            {
                                $child = get_term($child_id, $term->taxonomy);
                                if($child->count)
                                {
                                    continue 2;
                                }
                            }
                        }

                        // It really is empty.
                        unset($term_objects[$k]);
                    }
                }
            }

            // Hierarchical queries are not limited, so 'offset' and 'number' must be handled now.
            if($hierarchical && $number && is_array($term_objects))
            {
                if($offset >= count($term_objects))
                {
                    $term_objects = [];
                }
                else
                {
                    $term_objects = array_slice($term_objects, $offset, $number, true);
                }
            }

            // Prime termmeta cache.
            if($args['update_term_meta_cache'])
            {
                $term_ids = wp_list_pluck($term_objects, 'term_id');
                wp_lazyload_term_meta($term_ids);
            }

            if('all_with_object_id' === $_fields && ! empty($args['object_ids']))
            {
                $term_cache = [];
                foreach($term_objects as $term)
                {
                    $object = new stdClass();
                    $object->term_id = $term->term_id;
                    $object->object_id = $term->object_id;
                    $term_cache[] = $object;
                }
            }
            elseif('all' === $_fields && $args['pad_counts'])
            {
                $term_cache = [];
                foreach($term_objects as $term)
                {
                    $object = new stdClass();
                    $object->term_id = $term->term_id;
                    $object->count = $term->count;
                    $term_cache[] = $object;
                }
            }
            elseif($fields_is_filtered)
            {
                $term_cache = $term_objects;
            }
            else
            {
                $term_cache = wp_list_pluck($term_objects, 'term_id');
            }

            if($args['cache_results'])
            {
                wp_cache_add($cache_key, $term_cache, 'term-queries');
            }

            $this->terms = $this->format_terms($term_objects, $_fields);

            return $this->terms;
        }

        public function parse_query($query = '')
        {
            if(empty($query))
            {
                $query = $this->query_vars;
            }

            $taxonomies = isset($query['taxonomy']) ? (array) $query['taxonomy'] : null;

            $this->query_var_defaults = apply_filters('get_terms_defaults', $this->query_var_defaults, $taxonomies);

            $query = wp_parse_args($query, $this->query_var_defaults);

            $query['number'] = absint($query['number']);
            $query['offset'] = absint($query['offset']);

            // 'parent' overrides 'child_of'.
            if(0 < (int) $query['parent'])
            {
                $query['child_of'] = false;
            }

            if('all' === $query['get'])
            {
                $query['childless'] = false;
                $query['child_of'] = 0;
                $query['hide_empty'] = 0;
                $query['hierarchical'] = false;
                $query['pad_counts'] = false;
            }

            $query['taxonomy'] = $taxonomies;

            $this->query_vars = $query;

            do_action('parse_term_query', $this);
        }

        protected function parse_orderby($orderby_raw)
        {
            $_orderby = strtolower($orderby_raw);
            $maybe_orderby_meta = false;

            if(in_array($_orderby, ['term_id', 'name', 'slug', 'term_group'], true))
            {
                $orderby = "t.$_orderby";
            }
            elseif(in_array($_orderby, ['count', 'parent', 'taxonomy', 'term_taxonomy_id', 'description'], true))
            {
                $orderby = "tt.$_orderby";
            }
            elseif('term_order' === $_orderby)
            {
                $orderby = 'tr.term_order';
            }
            elseif('include' === $_orderby && ! empty($this->query_vars['include']))
            {
                $include = implode(',', wp_parse_id_list($this->query_vars['include']));
                $orderby = "FIELD( t.term_id, $include )";
            }
            elseif('slug__in' === $_orderby && ! empty($this->query_vars['slug']) && is_array($this->query_vars['slug']))
            {
                $slugs = implode("', '", array_map('sanitize_title_for_query', $this->query_vars['slug']));
                $orderby = "FIELD( t.slug, '".$slugs."')";
            }
            elseif('none' === $_orderby)
            {
                $orderby = '';
            }
            elseif(empty($_orderby) || 'id' === $_orderby || 'term_id' === $_orderby)
            {
                $orderby = 't.term_id';
            }
            else
            {
                $orderby = 't.name';

                // This may be a value of orderby related to meta.
                $maybe_orderby_meta = true;
            }

            $orderby = apply_filters('get_terms_orderby', $orderby, $this->query_vars, $this->query_vars['taxonomy']);

            // Run after the 'get_terms_orderby' filter for backward compatibility.
            if($maybe_orderby_meta)
            {
                $maybe_orderby_meta = $this->parse_orderby_meta($_orderby);
                if($maybe_orderby_meta)
                {
                    $orderby = $maybe_orderby_meta;
                }
            }

            return $orderby;
        }

        protected function parse_orderby_meta($orderby_raw)
        {
            $orderby = '';

            // Tell the meta query to generate its SQL, so we have access to table aliases.
            $this->meta_query->get_sql('term', 't', 'term_id');
            $meta_clauses = $this->meta_query->get_clauses();
            if(! $meta_clauses || ! $orderby_raw)
            {
                return $orderby;
            }

            $allowed_keys = [];
            $primary_meta_key = null;
            $primary_meta_query = reset($meta_clauses);
            if(! empty($primary_meta_query['key']))
            {
                $primary_meta_key = $primary_meta_query['key'];
                $allowed_keys[] = $primary_meta_key;
            }
            $allowed_keys[] = 'meta_value';
            $allowed_keys[] = 'meta_value_num';
            $allowed_keys = array_merge($allowed_keys, array_keys($meta_clauses));

            if(! in_array($orderby_raw, $allowed_keys, true))
            {
                return $orderby;
            }

            switch($orderby_raw)
            {
                case $primary_meta_key:
                case 'meta_value':
                    if(! empty($primary_meta_query['type']))
                    {
                        $orderby = "CAST({$primary_meta_query['alias']}.meta_value AS {$primary_meta_query['cast']})";
                    }
                    else
                    {
                        $orderby = "{$primary_meta_query['alias']}.meta_value";
                    }
                    break;

                case 'meta_value_num':
                    $orderby = "{$primary_meta_query['alias']}.meta_value+0";
                    break;

                default:
                    if(array_key_exists($orderby_raw, $meta_clauses))
                    {
                        // $orderby corresponds to a meta_query clause.
                        $meta_clause = $meta_clauses[$orderby_raw];
                        $orderby = "CAST({$meta_clause['alias']}.meta_value AS {$meta_clause['cast']})";
                    }
                    break;
            }

            return $orderby;
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

        protected function get_search_sql($search)
        {
            global $wpdb;

            $like = '%'.$wpdb->esc_like($search).'%';

            return $wpdb->prepare('((t.name LIKE %s) OR (t.slug LIKE %s))', $like, $like);
        }

        protected function generate_cache_key(array $args, $sql)
        {
            global $wpdb;
            // $args can be anything. Only use the args defined in defaults to compute the key.
            $cache_args = wp_array_slice_assoc($args, array_keys($this->query_var_defaults));

            unset($cache_args['cache_results'], $cache_args['update_term_meta_cache']);

            if('count' !== $args['fields'] && 'all_with_object_id' !== $args['fields'])
            {
                $cache_args['fields'] = 'all';
            }
            $taxonomies = (array) $args['taxonomy'];

            // Replace wpdb placeholder in the SQL statement used by the cache key.
            $sql = $wpdb->remove_placeholder_escape($sql);

            $key = md5(serialize($cache_args).serialize($taxonomies).$sql);
            $last_changed = wp_cache_get_last_changed('terms');

            return "get_terms:$key:$last_changed";
        }

        protected function populate_terms($terms)
        {
            $term_objects = [];
            if(! is_array($terms))
            {
                return $term_objects;
            }

            foreach($terms as $key => $term_data)
            {
                if(is_object($term_data) && property_exists($term_data, 'term_id'))
                {
                    $term = get_term($term_data->term_id);
                    if(property_exists($term_data, 'object_id'))
                    {
                        $term->object_id = (int) $term_data->object_id;
                    }
                    if(property_exists($term_data, 'count'))
                    {
                        $term->count = (int) $term_data->count;
                    }
                }
                else
                {
                    $term = get_term($term_data);
                }

                if($term instanceof WP_Term)
                {
                    $term_objects[$key] = $term;
                }
            }

            return $term_objects;
        }

        protected function format_terms($term_objects, $_fields)
        {
            $_terms = [];
            if('id=>parent' === $_fields)
            {
                foreach($term_objects as $term)
                {
                    $_terms[$term->term_id] = $term->parent;
                }
            }
            elseif('ids' === $_fields)
            {
                foreach($term_objects as $term)
                {
                    $_terms[] = (int) $term->term_id;
                }
            }
            elseif('tt_ids' === $_fields)
            {
                foreach($term_objects as $term)
                {
                    $_terms[] = (int) $term->term_taxonomy_id;
                }
            }
            elseif('names' === $_fields)
            {
                foreach($term_objects as $term)
                {
                    $_terms[] = $term->name;
                }
            }
            elseif('slugs' === $_fields)
            {
                foreach($term_objects as $term)
                {
                    $_terms[] = $term->slug;
                }
            }
            elseif('id=>name' === $_fields)
            {
                foreach($term_objects as $term)
                {
                    $_terms[$term->term_id] = $term->name;
                }
            }
            elseif('id=>slug' === $_fields)
            {
                foreach($term_objects as $term)
                {
                    $_terms[$term->term_id] = $term->slug;
                }
            }
            elseif('all' === $_fields || 'all_with_object_id' === $_fields)
            {
                $_terms = $term_objects;
            }

            return $_terms;
        }
    }
