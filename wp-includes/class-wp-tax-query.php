<?php

    #[AllowDynamicProperties]
    class WP_Tax_Query
    {
        private static $no_results = [
            'join' => [''],
            'where' => ['0 = 1'],
        ];

        public $queries = [];

        public $relation;

        public $queried_terms = [];

        public $primary_table;

        public $primary_id_column;

        protected $table_aliases = [];

        public function __construct($tax_query)
        {
            if(isset($tax_query['relation']))
            {
                $this->relation = $this->sanitize_relation($tax_query['relation']);
            }
            else
            {
                $this->relation = 'AND';
            }

            $this->queries = $this->sanitize_query($tax_query);
        }

        public function sanitize_relation($relation)
        {
            if('OR' === strtoupper($relation))
            {
                return 'OR';
            }
            else
            {
                return 'AND';
            }
        }

        public function sanitize_query($queries)
        {
            $cleaned_query = [];

            $defaults = [
                'taxonomy' => '',
                'terms' => [],
                'field' => 'term_id',
                'operator' => 'IN',
                'include_children' => true,
            ];

            foreach($queries as $key => $query)
            {
                if('relation' === $key)
                {
                    $cleaned_query['relation'] = $this->sanitize_relation($query);
                    // First-order clause.
                }
                elseif(self::is_first_order_clause($query))
                {
                    $cleaned_clause = array_merge($defaults, $query);
                    $cleaned_clause['terms'] = (array) $cleaned_clause['terms'];
                    $cleaned_query[] = $cleaned_clause;

                    /*
                     * Keep a copy of the clause in the flate
                     * $queried_terms array, for use in WP_Query.
                     */
                    if(! empty($cleaned_clause['taxonomy']) && 'NOT IN' !== $cleaned_clause['operator'])
                    {
                        $taxonomy = $cleaned_clause['taxonomy'];
                        if(! isset($this->queried_terms[$taxonomy]))
                        {
                            $this->queried_terms[$taxonomy] = [];
                        }

                        /*
                         * Backward compatibility: Only store the first
                         * 'terms' and 'field' found for a given taxonomy.
                         */
                        if(! empty($cleaned_clause['terms']) && ! isset($this->queried_terms[$taxonomy]['terms']))
                        {
                            $this->queried_terms[$taxonomy]['terms'] = $cleaned_clause['terms'];
                        }

                        if(! empty($cleaned_clause['field']) && ! isset($this->queried_terms[$taxonomy]['field']))
                        {
                            $this->queried_terms[$taxonomy]['field'] = $cleaned_clause['field'];
                        }
                    }
                    // Otherwise, it's a nested query, so we recurse.
                }
                elseif(is_array($query))
                {
                    $cleaned_subquery = $this->sanitize_query($query);

                    if(! empty($cleaned_subquery))
                    {
                        // All queries with children must have a relation.
                        if(! isset($cleaned_subquery['relation']))
                        {
                            $cleaned_subquery['relation'] = 'AND';
                        }

                        $cleaned_query[] = $cleaned_subquery;
                    }
                }
            }

            return $cleaned_query;
        }

        protected static function is_first_order_clause($query)
        {
            return is_array($query) && (empty($query) || array_key_exists('terms', $query) || array_key_exists('taxonomy', $query) || array_key_exists('include_children', $query) || array_key_exists('field', $query) || array_key_exists('operator', $query));
        }

        public function get_sql($primary_table, $primary_id_column)
        {
            $this->primary_table = $primary_table;
            $this->primary_id_column = $primary_id_column;

            return $this->get_sql_clauses();
        }

        protected function get_sql_clauses()
        {
            /*
             * $queries are passed by reference to get_sql_for_query() for recursion.
             * To keep $this->queries unaltered, pass a copy.
             */
            $queries = $this->queries;
            $sql = $this->get_sql_for_query($queries);

            if(! empty($sql['where']))
            {
                $sql['where'] = ' AND '.$sql['where'];
            }

            return $sql;
        }

        protected function get_sql_for_query(&$query, $depth = 0)
        {
            $sql_chunks = [
                'join' => [],
                'where' => [],
            ];

            $sql = [
                'join' => '',
                'where' => '',
            ];

            $indent = '';
            for($i = 0; $i < $depth; $i++)
            {
                $indent .= '  ';
            }

            foreach($query as $key => &$clause)
            {
                if('relation' === $key)
                {
                    $relation = $query['relation'];
                }
                elseif(is_array($clause))
                {
                    // This is a first-order clause.
                    if($this->is_first_order_clause($clause))
                    {
                        $clause_sql = $this->get_sql_for_clause($clause, $query);

                        $where_count = count($clause_sql['where']);
                        if(! $where_count)
                        {
                            $sql_chunks['where'][] = '';
                        }
                        elseif(1 === $where_count)
                        {
                            $sql_chunks['where'][] = $clause_sql['where'][0];
                        }
                        else
                        {
                            $sql_chunks['where'][] = '( '.implode(' AND ', $clause_sql['where']).' )';
                        }

                        $sql_chunks['join'] = array_merge($sql_chunks['join'], $clause_sql['join']);
                        // This is a subquery, so we recurse.
                    }
                    else
                    {
                        $clause_sql = $this->get_sql_for_query($clause, $depth + 1);

                        $sql_chunks['where'][] = $clause_sql['where'];
                        $sql_chunks['join'][] = $clause_sql['join'];
                    }
                }
            }

            // Filter to remove empties.
            $sql_chunks['join'] = array_filter($sql_chunks['join']);
            $sql_chunks['where'] = array_filter($sql_chunks['where']);

            if(empty($relation))
            {
                $relation = 'AND';
            }

            // Filter duplicate JOIN clauses and combine into a single string.
            if(! empty($sql_chunks['join']))
            {
                $sql['join'] = implode(' ', array_unique($sql_chunks['join']));
            }

            // Generate a single WHERE clause with proper brackets and indentation.
            if(! empty($sql_chunks['where']))
            {
                $sql['where'] = '( '."\n  ".$indent.implode(' '."\n  ".$indent.$relation.' '."\n  ".$indent, $sql_chunks['where'])."\n".$indent.')';
            }

            return $sql;
        }

        public function get_sql_for_clause(&$clause, $parent_query)
        {
            global $wpdb;

            $sql = [
                'where' => [],
                'join' => [],
            ];

            $join = '';
            $where = '';

            $this->clean_query($clause);

            if(is_wp_error($clause))
            {
                return self::$no_results;
            }

            $terms = $clause['terms'];
            $operator = strtoupper($clause['operator']);

            if('IN' === $operator)
            {
                if(empty($terms))
                {
                    return self::$no_results;
                }

                $terms = implode(',', $terms);

                /*
                 * Before creating another table join, see if this clause has a
                 * sibling with an existing join that can be shared.
                 */
                $alias = $this->find_compatible_table_alias($clause, $parent_query);
                if(false === $alias)
                {
                    $i = count($this->table_aliases);
                    $alias = $i ? 'tt'.$i : $wpdb->term_relationships;

                    // Store the alias as part of a flat array to build future iterators.
                    $this->table_aliases[] = $alias;

                    // Store the alias with this clause, so later siblings can use it.
                    $clause['alias'] = $alias;

                    $join .= " LEFT JOIN $wpdb->term_relationships";
                    $join .= $i ? " AS $alias" : '';
                    $join .= " ON ($this->primary_table.$this->primary_id_column = $alias.object_id)";
                }

                $where = "$alias.term_taxonomy_id $operator ($terms)";
            }
            elseif('NOT IN' === $operator)
            {
                if(empty($terms))
                {
                    return $sql;
                }

                $terms = implode(',', $terms);

                $where = "$this->primary_table.$this->primary_id_column NOT IN (
				SELECT object_id
				FROM $wpdb->term_relationships
				WHERE term_taxonomy_id IN ($terms)
			)";
            }
            elseif('AND' === $operator)
            {
                if(empty($terms))
                {
                    return $sql;
                }

                $num_terms = count($terms);

                $terms = implode(',', $terms);

                $where = "(
				SELECT COUNT(1)
				FROM $wpdb->term_relationships
				WHERE term_taxonomy_id IN ($terms)
				AND object_id = $this->primary_table.$this->primary_id_column
			) = $num_terms";
            }
            elseif('NOT EXISTS' === $operator || 'EXISTS' === $operator)
            {
                $where = $wpdb->prepare(
                    "$operator (
					SELECT 1
					FROM $wpdb->term_relationships
					INNER JOIN $wpdb->term_taxonomy
					ON $wpdb->term_taxonomy.term_taxonomy_id = $wpdb->term_relationships.term_taxonomy_id
					WHERE $wpdb->term_taxonomy.taxonomy = %s
					AND $wpdb->term_relationships.object_id = $this->primary_table.$this->primary_id_column
				)", $clause['taxonomy']
                );
            }

            $sql['join'][] = $join;
            $sql['where'][] = $where;

            return $sql;
        }

        private function clean_query(&$query)
        {
            if(empty($query['taxonomy']))
            {
                if('term_taxonomy_id' !== $query['field'])
                {
                    $query = new WP_Error('invalid_taxonomy', __('Invalid taxonomy.'));

                    return;
                }

                // So long as there are shared terms, 'include_children' requires that a taxonomy is set.
                $query['include_children'] = false;
            }
            elseif(! taxonomy_exists($query['taxonomy']))
            {
                $query = new WP_Error('invalid_taxonomy', __('Invalid taxonomy.'));

                return;
            }

            if('slug' === $query['field'] || 'name' === $query['field'])
            {
                $query['terms'] = array_unique((array) $query['terms']);
            }
            else
            {
                $query['terms'] = wp_parse_id_list($query['terms']);
            }

            if(is_taxonomy_hierarchical($query['taxonomy']) && $query['include_children'])
            {
                $this->transform_query($query, 'term_id');

                if(is_wp_error($query))
                {
                    return;
                }

                $children = [];
                foreach($query['terms'] as $term)
                {
                    $children = array_merge($children, get_term_children($term, $query['taxonomy']));
                    $children[] = $term;
                }
                $query['terms'] = $children;
            }

            $this->transform_query($query, 'term_taxonomy_id');
        }

        public function transform_query(&$query, $resulting_field)
        {
            if(empty($query['terms']) || $query['field'] === $resulting_field)
            {
                return;
            }

            $resulting_field = sanitize_key($resulting_field);

            // Empty 'terms' always results in a null transformation.
            $terms = array_filter($query['terms']);
            if(empty($terms))
            {
                $query['terms'] = [];
                $query['field'] = $resulting_field;

                return;
            }

            $args = [
                'get' => 'all',
                'number' => 0,
                'taxonomy' => $query['taxonomy'],
                'update_term_meta_cache' => false,
                'orderby' => 'none',
            ];

            // Term query parameter name depends on the 'field' being searched on.
            switch($query['field'])
            {
                case 'slug':
                    $args['slug'] = $terms;
                    break;
                case 'name':
                    $args['name'] = $terms;
                    break;
                case 'term_taxonomy_id':
                    $args['term_taxonomy_id'] = $terms;
                    break;
                default:
                    $args['include'] = wp_parse_id_list($terms);
                    break;
            }

            if(! is_taxonomy_hierarchical($query['taxonomy']))
            {
                $args['number'] = count($terms);
            }

            $term_query = new WP_Term_Query();
            $term_list = $term_query->query($args);

            if(is_wp_error($term_list))
            {
                $query = $term_list;

                return;
            }

            if('AND' === $query['operator'] && count($term_list) < count($query['terms']))
            {
                $query = new WP_Error('inexistent_terms', __('Inexistent terms.'));

                return;
            }

            $query['terms'] = wp_list_pluck($term_list, $resulting_field);
            $query['field'] = $resulting_field;
        }

        protected function find_compatible_table_alias($clause, $parent_query)
        {
            $alias = false;

            // Sanity check. Only IN queries use the JOIN syntax.
            // Since we're only checking IN queries, we're only concerned with OR relations.
            if(! isset($clause['operator']) || 'IN' !== $clause['operator'] || ! isset($parent_query['relation']) || 'OR' !== $parent_query['relation'])
            {
                return $alias;
            }

            $compatible_operators = ['IN'];

            foreach($parent_query as $sibling)
            {
                if(! is_array($sibling) || ! $this->is_first_order_clause($sibling))
                {
                    continue;
                }

                if(empty($sibling['alias']) || empty($sibling['operator']))
                {
                    continue;
                }

                // The sibling must both have compatible operator to share its alias.
                if(in_array(strtoupper($sibling['operator']), $compatible_operators, true))
                {
                    $alias = preg_replace('/\W/', '_', $sibling['alias']);
                    break;
                }
            }

            return $alias;
        }
    }
