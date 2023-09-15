<?php

    #[AllowDynamicProperties]
    class WP_Meta_Query
    {
        public $queries = [];

        public $relation;

        public $meta_table;

        public $meta_id_column;

        public $primary_table;

        public $primary_id_column;

        protected $table_aliases = [];

        protected $clauses = [];

        protected $has_or_relation = false;

        public function parse_query_vars($qv)
        {
            $meta_query = [];

            /*
             * For orderby=meta_value to work correctly, simple query needs to be
             * first (so that its table join is against an unaliased meta table) and
             * needs to be its own clause (so it doesn't interfere with the logic of
             * the rest of the meta_query).
             */
            $primary_meta_query = [];
            foreach(['key', 'compare', 'type', 'compare_key', 'type_key'] as $key)
            {
                if(! empty($qv["meta_$key"]))
                {
                    $primary_meta_query[$key] = $qv["meta_$key"];
                }
            }

            // WP_Query sets 'meta_value' = '' by default.
            if(isset($qv['meta_value']) && '' !== $qv['meta_value'] && (! is_array($qv['meta_value']) || $qv['meta_value']))
            {
                $primary_meta_query['value'] = $qv['meta_value'];
            }

            $existing_meta_query = isset($qv['meta_query']) && is_array($qv['meta_query']) ? $qv['meta_query'] : [];

            if(! empty($primary_meta_query) && ! empty($existing_meta_query))
            {
                $meta_query = [
                    'relation' => 'AND',
                    $primary_meta_query,
                    $existing_meta_query,
                ];
            }
            elseif(! empty($primary_meta_query))
            {
                $meta_query = [
                    $primary_meta_query,
                ];
            }
            elseif(! empty($existing_meta_query))
            {
                $meta_query = $existing_meta_query;
            }

            $this->__construct($meta_query);
        }

        public function __construct($meta_query = false)
        {
            if(! $meta_query)
            {
                return;
            }

            if(isset($meta_query['relation']) && 'OR' === strtoupper($meta_query['relation']))
            {
                $this->relation = 'OR';
            }
            else
            {
                $this->relation = 'AND';
            }

            $this->queries = $this->sanitize_query($meta_query);
        }

        public function sanitize_query($queries)
        {
            $clean_queries = [];

            if(! is_array($queries))
            {
                return $clean_queries;
            }

            foreach($queries as $key => $query)
            {
                if('relation' === $key)
                {
                    $relation = $query;
                }
                elseif(! is_array($query))
                {
                    continue;
                    // First-order clause.
                }
                elseif($this->is_first_order_clause($query))
                {
                    if(isset($query['value']) && [] === $query['value'])
                    {
                        unset($query['value']);
                    }

                    $clean_queries[$key] = $query;
                    // Otherwise, it's a nested query, so we recurse.
                }
                else
                {
                    $cleaned_query = $this->sanitize_query($query);

                    if(! empty($cleaned_query))
                    {
                        $clean_queries[$key] = $cleaned_query;
                    }
                }
            }

            if(empty($clean_queries))
            {
                return $clean_queries;
            }

            // Sanitize the 'relation' key provided in the query.
            if(isset($relation) && 'OR' === strtoupper($relation))
            {
                $clean_queries['relation'] = 'OR';
                $this->has_or_relation = true;
                /*
                * If there is only a single clause, call the relation 'OR'.
                * This value will not actually be used to join clauses, but it
                * simplifies the logic around combining key-only queries.
                */
            }
            elseif(1 === count($clean_queries))
            {
                $clean_queries['relation'] = 'OR';
                // Default to AND.
            }
            else
            {
                $clean_queries['relation'] = 'AND';
            }

            return $clean_queries;
        }

        protected function is_first_order_clause($query)
        {
            return isset($query['key']) || isset($query['value']);
        }

        public function get_sql($type, $primary_table, $primary_id_column, $context = null)
        {
            $meta_table = _get_meta_table($type);
            if(! $meta_table)
            {
                return false;
            }

            $this->table_aliases = [];

            $this->meta_table = $meta_table;
            $this->meta_id_column = sanitize_key($type.'_id');

            $this->primary_table = $primary_table;
            $this->primary_id_column = $primary_id_column;

            $sql = $this->get_sql_clauses();

            /*
             * If any JOINs are LEFT JOINs (as in the case of NOT EXISTS), then all JOINs should
             * be LEFT. Otherwise posts with no metadata will be excluded from results.
             */
            if(str_contains($sql['join'], 'LEFT JOIN'))
            {
                $sql['join'] = str_replace('INNER JOIN', 'LEFT JOIN', $sql['join']);
            }

            return apply_filters_ref_array('get_meta_sql', [
                $sql,
                $this->queries,
                $type,
                $primary_table,
                $primary_id_column,
                $context
            ]);
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
                        $clause_sql = $this->get_sql_for_clause($clause, $query, $key);

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

        public function get_sql_for_clause(&$clause, $parent_query, $clause_key = '')
        {
            global $wpdb;

            $sql_chunks = [
                'where' => [],
                'join' => [],
            ];

            if(isset($clause['compare']))
            {
                $clause['compare'] = strtoupper($clause['compare']);
            }
            else
            {
                $clause['compare'] = isset($clause['value']) && is_array($clause['value']) ? 'IN' : '=';
            }

            $non_numeric_operators = [
                '=',
                '!=',
                'LIKE',
                'NOT LIKE',
                'IN',
                'NOT IN',
                'EXISTS',
                'NOT EXISTS',
                'RLIKE',
                'REGEXP',
                'NOT REGEXP',
            ];

            $numeric_operators = [
                '>',
                '>=',
                '<',
                '<=',
                'BETWEEN',
                'NOT BETWEEN',
            ];

            if(! in_array($clause['compare'], $non_numeric_operators, true) && ! in_array($clause['compare'], $numeric_operators, true))
            {
                $clause['compare'] = '=';
            }

            if(isset($clause['compare_key']))
            {
                $clause['compare_key'] = strtoupper($clause['compare_key']);
            }
            else
            {
                $clause['compare_key'] = isset($clause['key']) && is_array($clause['key']) ? 'IN' : '=';
            }

            if(! in_array($clause['compare_key'], $non_numeric_operators, true))
            {
                $clause['compare_key'] = '=';
            }

            $meta_compare = $clause['compare'];
            $meta_compare_key = $clause['compare_key'];

            // First build the JOIN clause, if one is required.
            $join = '';

            // We prefer to avoid joins if possible. Look for an existing join compatible with this clause.
            $alias = $this->find_compatible_table_alias($clause, $parent_query);
            if(false === $alias)
            {
                $i = count($this->table_aliases);
                $alias = $i ? 'mt'.$i : $this->meta_table;

                // JOIN clauses for NOT EXISTS have their own syntax.
                if('NOT EXISTS' === $meta_compare)
                {
                    $join .= " LEFT JOIN $this->meta_table";
                    $join .= $i ? " AS $alias" : '';

                    if('LIKE' === $meta_compare_key)
                    {
                        $join .= $wpdb->prepare(" ON ( $this->primary_table.$this->primary_id_column = $alias.$this->meta_id_column AND $alias.meta_key LIKE %s )", '%'.$wpdb->esc_like($clause['key']).'%');
                    }
                    else
                    {
                        $join .= $wpdb->prepare(" ON ( $this->primary_table.$this->primary_id_column = $alias.$this->meta_id_column AND $alias.meta_key = %s )", $clause['key']);
                    }
                    // All other JOIN clauses.
                }
                else
                {
                    $join .= " INNER JOIN $this->meta_table";
                    $join .= $i ? " AS $alias" : '';
                    $join .= " ON ( $this->primary_table.$this->primary_id_column = $alias.$this->meta_id_column )";
                }

                $this->table_aliases[] = $alias;
                $sql_chunks['join'][] = $join;
            }

            // Save the alias to this clause, for future siblings to find.
            $clause['alias'] = $alias;

            // Determine the data type.
            $_meta_type = isset($clause['type']) ? $clause['type'] : '';
            $meta_type = $this->get_cast_for_type($_meta_type);
            $clause['cast'] = $meta_type;

            // Fallback for clause keys is the table alias. Key must be a string.
            if(is_int($clause_key) || ! $clause_key)
            {
                $clause_key = $clause['alias'];
            }

            // Ensure unique clause keys, so none are overwritten.
            $iterator = 1;
            $clause_key_base = $clause_key;
            while(isset($this->clauses[$clause_key]))
            {
                $clause_key = $clause_key_base.'-'.$iterator;
                ++$iterator;
            }

            // Store the clause in our flat array.
            $this->clauses[$clause_key] =& $clause;

            // Next, build the WHERE clause.

            // meta_key.
            if(array_key_exists('key', $clause))
            {
                if('NOT EXISTS' === $meta_compare)
                {
                    $sql_chunks['where'][] = $alias.'.'.$this->meta_id_column.' IS NULL';
                }
                else
                {
                    if(in_array($meta_compare_key, ['!=', 'NOT IN', 'NOT LIKE', 'NOT EXISTS', 'NOT REGEXP'], true))
                    {
                        // Negative clauses may be reused.
                        $i = count($this->table_aliases);
                        $subquery_alias = $i ? 'mt'.$i : $this->meta_table;
                        $this->table_aliases[] = $subquery_alias;

                        $meta_compare_string_start = 'NOT EXISTS (';
                        $meta_compare_string_start .= "SELECT 1 FROM $wpdb->postmeta $subquery_alias ";
                        $meta_compare_string_start .= "WHERE $subquery_alias.post_ID = $alias.post_ID ";
                        $meta_compare_string_end = 'LIMIT 1';
                        $meta_compare_string_end .= ')';
                    }

                    switch($meta_compare_key)
                    {
                        case '=':
                        case 'EXISTS':
                            $where = $wpdb->prepare("$alias.meta_key = %s", trim($clause['key'])); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                            break;
                        case 'LIKE':
                            $meta_compare_value = '%'.$wpdb->esc_like(trim($clause['key'])).'%';
                            $where = $wpdb->prepare("$alias.meta_key LIKE %s", $meta_compare_value); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                            break;
                        case 'IN':
                            $meta_compare_string = "$alias.meta_key IN (".substr(str_repeat(',%s', count($clause['key'])), 1).')';
                            $where = $wpdb->prepare($meta_compare_string, $clause['key']); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                            break;
                        case 'RLIKE':
                        case 'REGEXP':
                            $operator = $meta_compare_key;
                            if(isset($clause['type_key']) && 'BINARY' === strtoupper($clause['type_key']))
                            {
                                $cast = 'BINARY';
                                $meta_key = "CAST($alias.meta_key AS BINARY)";
                            }
                            else
                            {
                                $cast = '';
                                $meta_key = "$alias.meta_key";
                            }
                            $where = $wpdb->prepare("$meta_key $operator $cast %s", trim($clause['key'])); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                            break;

                        case '!=':
                        case 'NOT EXISTS':
                            $meta_compare_string = $meta_compare_string_start."AND $subquery_alias.meta_key = %s ".$meta_compare_string_end;
                            $where = $wpdb->prepare($meta_compare_string, $clause['key']); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                            break;
                        case 'NOT LIKE':
                            $meta_compare_string = $meta_compare_string_start."AND $subquery_alias.meta_key LIKE %s ".$meta_compare_string_end;

                            $meta_compare_value = '%'.$wpdb->esc_like(trim($clause['key'])).'%';
                            $where = $wpdb->prepare($meta_compare_string, $meta_compare_value); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                            break;
                        case 'NOT IN':
                            $array_subclause = '('.substr(str_repeat(',%s', count($clause['key'])), 1).') ';
                            $meta_compare_string = $meta_compare_string_start."AND $subquery_alias.meta_key IN ".$array_subclause.$meta_compare_string_end;
                            $where = $wpdb->prepare($meta_compare_string, $clause['key']); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                            break;
                        case 'NOT REGEXP':
                            $operator = $meta_compare_key;
                            if(isset($clause['type_key']) && 'BINARY' === strtoupper($clause['type_key']))
                            {
                                $cast = 'BINARY';
                                $meta_key = "CAST($subquery_alias.meta_key AS BINARY)";
                            }
                            else
                            {
                                $cast = '';
                                $meta_key = "$subquery_alias.meta_key";
                            }

                            $meta_compare_string = $meta_compare_string_start."AND $meta_key REGEXP $cast %s ".$meta_compare_string_end;
                            $where = $wpdb->prepare($meta_compare_string, $clause['key']); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                            break;
                    }

                    $sql_chunks['where'][] = $where;
                }
            }

            // meta_value.
            if(array_key_exists('value', $clause))
            {
                $meta_value = $clause['value'];

                if(in_array($meta_compare, ['IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'], true))
                {
                    if(! is_array($meta_value))
                    {
                        $meta_value = preg_split('/[,\s]+/', $meta_value);
                    }
                }
                elseif(is_string($meta_value))
                {
                    $meta_value = trim($meta_value);
                }

                switch($meta_compare)
                {
                    case 'IN':
                    case 'NOT IN':
                        $meta_compare_string = '('.substr(str_repeat(',%s', count($meta_value)), 1).')';
                        $where = $wpdb->prepare($meta_compare_string, $meta_value);
                        break;

                    case 'BETWEEN':
                    case 'NOT BETWEEN':
                        $where = $wpdb->prepare('%s AND %s', $meta_value[0], $meta_value[1]);
                        break;

                    case 'LIKE':
                    case 'NOT LIKE':
                        $meta_value = '%'.$wpdb->esc_like($meta_value).'%';
                        $where = $wpdb->prepare('%s', $meta_value);
                        break;

                    // EXISTS with a value is interpreted as '='.
                    case 'EXISTS':
                        $meta_compare = '=';
                        $where = $wpdb->prepare('%s', $meta_value);
                        break;

                    // 'value' is ignored for NOT EXISTS.
                    case 'NOT EXISTS':
                        $where = '';
                        break;

                    default:
                        $where = $wpdb->prepare('%s', $meta_value);
                        break;
                }

                if($where)
                {
                    if('CHAR' === $meta_type)
                    {
                        $sql_chunks['where'][] = "$alias.meta_value {$meta_compare} {$where}";
                    }
                    else
                    {
                        $sql_chunks['where'][] = "CAST($alias.meta_value AS {$meta_type}) {$meta_compare} {$where}";
                    }
                }
            }

            /*
             * Multiple WHERE clauses (for meta_key and meta_value) should
             * be joined in parentheses.
             */
            if(1 < count($sql_chunks['where']))
            {
                $sql_chunks['where'] = ['( '.implode(' AND ', $sql_chunks['where']).' )'];
            }

            return $sql_chunks;
        }

        protected function find_compatible_table_alias($clause, $parent_query)
        {
            $alias = false;

            foreach($parent_query as $sibling)
            {
                // If the sibling has no alias yet, there's nothing to check.
                if(empty($sibling['alias']))
                {
                    continue;
                }

                // We're only interested in siblings that are first-order clauses.
                if(! is_array($sibling) || ! $this->is_first_order_clause($sibling))
                {
                    continue;
                }

                $compatible_compares = [];

                // Clauses connected by OR can share joins as long as they have "positive" operators.
                if('OR' === $parent_query['relation'])
                {
                    $compatible_compares = ['=', 'IN', 'BETWEEN', 'LIKE', 'REGEXP', 'RLIKE', '>', '>=', '<', '<='];
                    // Clauses joined by AND with "negative" operators share a join only if they also share a key.
                }
                elseif(isset($sibling['key']) && isset($clause['key']) && $sibling['key'] === $clause['key'])
                {
                    $compatible_compares = ['!=', 'NOT IN', 'NOT LIKE'];
                }

                $clause_compare = strtoupper($clause['compare']);
                $sibling_compare = strtoupper($sibling['compare']);
                if(in_array($clause_compare, $compatible_compares, true) && in_array($sibling_compare, $compatible_compares, true))
                {
                    $alias = preg_replace('/\W/', '_', $sibling['alias']);
                    break;
                }
            }

            return apply_filters('meta_query_find_compatible_table_alias', $alias, $clause, $parent_query, $this);
        }

        public function get_cast_for_type($type = '')
        {
            if(empty($type))
            {
                return 'CHAR';
            }

            $meta_type = strtoupper($type);

            if(! preg_match('/^(?:BINARY|CHAR|DATE|DATETIME|SIGNED|UNSIGNED|TIME|NUMERIC(?:\(\d+(?:,\s?\d+)?\))?|DECIMAL(?:\(\d+(?:,\s?\d+)?\))?)$/', $meta_type))
            {
                return 'CHAR';
            }

            if('NUMERIC' === $meta_type)
            {
                $meta_type = 'SIGNED';
            }

            return $meta_type;
        }

        public function get_clauses()
        {
            return $this->clauses;
        }

        public function has_or_relation()
        {
            return $this->has_or_relation;
        }
    }
