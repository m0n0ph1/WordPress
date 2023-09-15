<?php

//
// Taxonomy registration.
//

    function create_initial_taxonomies()
    {
        global $wp_rewrite;

        WP_Taxonomy::reset_default_labels();

        if(did_action('init'))
        {
            $post_format_base = apply_filters('post_format_rewrite_base', 'type');
            $rewrite = [
                'category' => [
                    'hierarchical' => true,
                    'slug' => get_option('category_base') ? get_option('category_base') : 'category',
                    'with_front' => ! get_option('category_base') || $wp_rewrite->using_index_permalinks(),
                    'ep_mask' => EP_CATEGORIES,
                ],
                'post_tag' => [
                    'hierarchical' => false,
                    'slug' => get_option('tag_base') ? get_option('tag_base') : 'tag',
                    'with_front' => ! get_option('tag_base') || $wp_rewrite->using_index_permalinks(),
                    'ep_mask' => EP_TAGS,
                ],
                'post_format' => $post_format_base ? ['slug' => $post_format_base] : false,
            ];
        }
        else
        {
            $rewrite = [
                'category' => false,
                'post_tag' => false,
                'post_format' => false,
            ];
        }

        register_taxonomy('category', 'post', [
            'hierarchical' => true,
            'query_var' => 'category_name',
            'rewrite' => $rewrite['category'],
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            '_builtin' => true,
            'capabilities' => [
                'manage_terms' => 'manage_categories',
                'edit_terms' => 'edit_categories',
                'delete_terms' => 'delete_categories',
                'assign_terms' => 'assign_categories',
            ],
            'show_in_rest' => true,
            'rest_base' => 'categories',
            'rest_controller_class' => 'WP_REST_Terms_Controller',
        ]);

        register_taxonomy('post_tag', 'post', [
            'hierarchical' => false,
            'query_var' => 'tag',
            'rewrite' => $rewrite['post_tag'],
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            '_builtin' => true,
            'capabilities' => [
                'manage_terms' => 'manage_post_tags',
                'edit_terms' => 'edit_post_tags',
                'delete_terms' => 'delete_post_tags',
                'assign_terms' => 'assign_post_tags',
            ],
            'show_in_rest' => true,
            'rest_base' => 'tags',
            'rest_controller_class' => 'WP_REST_Terms_Controller',
        ]);

        register_taxonomy('nav_menu', 'nav_menu_item', [
            'public' => false,
            'hierarchical' => false,
            'labels' => [
                'name' => __('Navigation Menus'),
                'singular_name' => __('Navigation Menu'),
            ],
            'query_var' => false,
            'rewrite' => false,
            'show_ui' => false,
            '_builtin' => true,
            'show_in_nav_menus' => false,
            'capabilities' => [
                'manage_terms' => 'edit_theme_options',
                'edit_terms' => 'edit_theme_options',
                'delete_terms' => 'edit_theme_options',
                'assign_terms' => 'edit_theme_options',
            ],
            'show_in_rest' => true,
            'rest_base' => 'menus',
            'rest_controller_class' => 'WP_REST_Menus_Controller',
        ]);

        register_taxonomy('link_category', 'link', [
            'hierarchical' => false,
            'labels' => [
                'name' => __('Link Categories'),
                'singular_name' => __('Link Category'),
                'search_items' => __('Search Link Categories'),
                'popular_items' => null,
                'all_items' => __('All Link Categories'),
                'edit_item' => __('Edit Link Category'),
                'update_item' => __('Update Link Category'),
                'add_new_item' => __('Add New Link Category'),
                'new_item_name' => __('New Link Category Name'),
                'separate_items_with_commas' => null,
                'add_or_remove_items' => null,
                'choose_from_most_used' => null,
                'back_to_items' => __('&larr; Go to Link Categories'),
            ],
            'capabilities' => [
                'manage_terms' => 'manage_links',
                'edit_terms' => 'manage_links',
                'delete_terms' => 'manage_links',
                'assign_terms' => 'manage_links',
            ],
            'query_var' => false,
            'rewrite' => false,
            'public' => false,
            'show_ui' => true,
            '_builtin' => true,
        ]);

        register_taxonomy('post_format', 'post', [
            'public' => true,
            'hierarchical' => false,
            'labels' => [
                'name' => _x('Formats', 'post format'),
                'singular_name' => _x('Format', 'post format'),
            ],
            'query_var' => true,
            'rewrite' => $rewrite['post_format'],
            'show_ui' => false,
            '_builtin' => true,
            'show_in_nav_menus' => current_theme_supports('post-formats'),
        ]);

        register_taxonomy('wp_theme', ['wp_template', 'wp_template_part', 'wp_global_styles'], [
            'public' => false,
            'hierarchical' => false,
            'labels' => [
                'name' => __('Themes'),
                'singular_name' => __('Theme'),
            ],
            'query_var' => false,
            'rewrite' => false,
            'show_ui' => false,
            '_builtin' => true,
            'show_in_nav_menus' => false,
            'show_in_rest' => false,
        ]);

        register_taxonomy('wp_template_part_area', ['wp_template_part'], [
            'public' => false,
            'hierarchical' => false,
            'labels' => [
                'name' => __('Template Part Areas'),
                'singular_name' => __('Template Part Area'),
            ],
            'query_var' => false,
            'rewrite' => false,
            'show_ui' => false,
            '_builtin' => true,
            'show_in_nav_menus' => false,
            'show_in_rest' => false,
        ]);
    }

    function get_taxonomies($args = [], $output = 'names', $operator = 'and')
    {
        global $wp_taxonomies;

        $field = ('names' === $output) ? 'name' : false;

        return wp_filter_object_list($wp_taxonomies, $args, $operator, $field);
    }

    function get_object_taxonomies($object_type, $output = 'names')
    {
        global $wp_taxonomies;

        if(is_object($object_type))
        {
            if('attachment' === $object_type->post_type)
            {
                return get_attachment_taxonomies($object_type, $output);
            }
            $object_type = $object_type->post_type;
        }

        $object_type = (array) $object_type;

        $taxonomies = [];
        foreach((array) $wp_taxonomies as $tax_name => $tax_obj)
        {
            if(array_intersect($object_type, (array) $tax_obj->object_type))
            {
                if('names' === $output)
                {
                    $taxonomies[] = $tax_name;
                }
                else
                {
                    $taxonomies[$tax_name] = $tax_obj;
                }
            }
        }

        return $taxonomies;
    }

    function get_taxonomy($taxonomy)
    {
        global $wp_taxonomies;

        if(! taxonomy_exists($taxonomy))
        {
            return false;
        }

        return $wp_taxonomies[$taxonomy];
    }

    function taxonomy_exists($taxonomy)
    {
        global $wp_taxonomies;

        return is_string($taxonomy) && isset($wp_taxonomies[$taxonomy]);
    }

    function is_taxonomy_hierarchical($taxonomy)
    {
        if(! taxonomy_exists($taxonomy))
        {
            return false;
        }

        $taxonomy = get_taxonomy($taxonomy);

        return $taxonomy->hierarchical;
    }

    function register_taxonomy($taxonomy, $object_type, $args = [])
    {
        global $wp_taxonomies;

        if(! is_array($wp_taxonomies))
        {
            $wp_taxonomies = [];
        }

        $args = wp_parse_args($args);

        if(empty($taxonomy) || strlen($taxonomy) > 32)
        {
            _doing_it_wrong(__FUNCTION__, __('Taxonomy names must be between 1 and 32 characters in length.'), '4.2.0');

            return new WP_Error('taxonomy_length_invalid', __('Taxonomy names must be between 1 and 32 characters in length.'));
        }

        $taxonomy_object = new WP_Taxonomy($taxonomy, $object_type, $args);
        $taxonomy_object->add_rewrite_rules();

        $wp_taxonomies[$taxonomy] = $taxonomy_object;

        $taxonomy_object->add_hooks();

        // Add default term.
        if(! empty($taxonomy_object->default_term))
        {
            $term = term_exists($taxonomy_object->default_term['name'], $taxonomy);
            if($term)
            {
                update_option('default_term_'.$taxonomy_object->name, $term['term_id']);
            }
            else
            {
                $term = wp_insert_term($taxonomy_object->default_term['name'], $taxonomy, [
                    'slug' => sanitize_title($taxonomy_object->default_term['slug']),
                    'description' => $taxonomy_object->default_term['description'],
                ]);

                // Update `term_id` in options.
                if(! is_wp_error($term))
                {
                    update_option('default_term_'.$taxonomy_object->name, $term['term_id']);
                }
            }
        }

        do_action('registered_taxonomy', $taxonomy, $object_type, (array) $taxonomy_object);

        do_action("registered_taxonomy_{$taxonomy}", $taxonomy, $object_type, (array) $taxonomy_object);

        return $taxonomy_object;
    }

    function unregister_taxonomy($taxonomy)
    {
        global $wp_taxonomies;

        if(! taxonomy_exists($taxonomy))
        {
            return new WP_Error('invalid_taxonomy', __('Invalid taxonomy.'));
        }

        $taxonomy_object = get_taxonomy($taxonomy);

        // Do not allow unregistering internal taxonomies.
        if($taxonomy_object->_builtin)
        {
            return new WP_Error('invalid_taxonomy', __('Unregistering a built-in taxonomy is not allowed.'));
        }

        $taxonomy_object->remove_rewrite_rules();
        $taxonomy_object->remove_hooks();

        // Remove the taxonomy.
        unset($wp_taxonomies[$taxonomy]);

        do_action('unregistered_taxonomy', $taxonomy);

        return true;
    }

    function get_taxonomy_labels($tax)
    {
        $tax->labels = (array) $tax->labels;

        if(isset($tax->helps) && empty($tax->labels['separate_items_with_commas']))
        {
            $tax->labels['separate_items_with_commas'] = $tax->helps;
        }

        if(isset($tax->no_tagcloud) && empty($tax->labels['not_found']))
        {
            $tax->labels['not_found'] = $tax->no_tagcloud;
        }

        $nohier_vs_hier_defaults = WP_Taxonomy::get_default_labels();

        $nohier_vs_hier_defaults['menu_name'] = $nohier_vs_hier_defaults['name'];

        $labels = _get_custom_object_labels($tax, $nohier_vs_hier_defaults);

        $taxonomy = $tax->name;

        $default_labels = clone $labels;

        $labels = apply_filters("taxonomy_labels_{$taxonomy}", $labels);

        // Ensure that the filtered labels contain all required default values.
        $labels = (object) array_merge((array) $default_labels, (array) $labels);

        return $labels;
    }

    function register_taxonomy_for_object_type($taxonomy, $object_type)
    {
        global $wp_taxonomies;

        if(! isset($wp_taxonomies[$taxonomy]) || ! get_post_type_object($object_type))
        {
            return false;
        }

        if(! in_array($object_type, $wp_taxonomies[$taxonomy]->object_type, true))
        {
            $wp_taxonomies[$taxonomy]->object_type[] = $object_type;
        }

        // Filter out empties.
        $wp_taxonomies[$taxonomy]->object_type = array_filter($wp_taxonomies[$taxonomy]->object_type);

        do_action('registered_taxonomy_for_object_type', $taxonomy, $object_type);

        return true;
    }

    function unregister_taxonomy_for_object_type($taxonomy, $object_type)
    {
        global $wp_taxonomies;

        if(! isset($wp_taxonomies[$taxonomy]) || ! get_post_type_object($object_type))
        {
            return false;
        }

        $key = array_search($object_type, $wp_taxonomies[$taxonomy]->object_type, true);
        if(false === $key)
        {
            return false;
        }

        unset($wp_taxonomies[$taxonomy]->object_type[$key]);

        do_action('unregistered_taxonomy_for_object_type', $taxonomy, $object_type);

        return true;
    }

//
// Term API.
//

    function get_objects_in_term($term_ids, $taxonomies, $args = [])
    {
        global $wpdb;

        if(! is_array($term_ids))
        {
            $term_ids = [$term_ids];
        }
        if(! is_array($taxonomies))
        {
            $taxonomies = [$taxonomies];
        }
        foreach((array) $taxonomies as $taxonomy)
        {
            if(! taxonomy_exists($taxonomy))
            {
                return new WP_Error('invalid_taxonomy', __('Invalid taxonomy.'));
            }
        }

        $defaults = ['order' => 'ASC'];
        $args = wp_parse_args($args, $defaults);

        $order = ('desc' === strtolower($args['order'])) ? 'DESC' : 'ASC';

        $term_ids = array_map('intval', $term_ids);

        $taxonomies = "'".implode("', '", array_map('esc_sql', $taxonomies))."'";
        $term_ids = "'".implode("', '", $term_ids)."'";

        $sql = "SELECT tr.object_id FROM $wpdb->term_relationships AS tr INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy IN ($taxonomies) AND tt.term_id IN ($term_ids) ORDER BY tr.object_id $order";

        $last_changed = wp_cache_get_last_changed('terms');
        $cache_key = 'get_objects_in_term:'.md5($sql).":$last_changed";
        $cache = wp_cache_get($cache_key, 'term-queries');
        if(false !== $cache)
        {
            $object_ids = (array) $cache;
        }
        else
        {
            $object_ids = $wpdb->get_col($sql);
            wp_cache_set($cache_key, $object_ids, 'term-queries');
        }

        if(! $object_ids)
        {
            return [];
        }

        return $object_ids;
    }

    function get_tax_sql($tax_query, $primary_table, $primary_id_column)
    {
        $tax_query_obj = new WP_Tax_Query($tax_query);

        return $tax_query_obj->get_sql($primary_table, $primary_id_column);
    }

    function get_term($term, $taxonomy = '', $output = OBJECT, $filter = 'raw')
    {
        if(empty($term))
        {
            return new WP_Error('invalid_term', __('Empty Term.'));
        }

        if($taxonomy && ! taxonomy_exists($taxonomy))
        {
            return new WP_Error('invalid_taxonomy', __('Invalid taxonomy.'));
        }

        if($term instanceof WP_Term)
        {
            $_term = $term;
        }
        elseif(is_object($term))
        {
            if(empty($term->filter) || 'raw' === $term->filter)
            {
                $_term = sanitize_term($term, $taxonomy, 'raw');
                $_term = new WP_Term($_term);
            }
            else
            {
                $_term = WP_Term::get_instance($term->term_id);
            }
        }
        else
        {
            $_term = WP_Term::get_instance($term, $taxonomy);
        }

        if(is_wp_error($_term))
        {
            return $_term;
        }
        elseif(! $_term)
        {
            return null;
        }

        // Ensure for filters that this is not empty.
        $taxonomy = $_term->taxonomy;

        $_term = apply_filters('get_term', $_term, $taxonomy);

        $_term = apply_filters("get_{$taxonomy}", $_term, $taxonomy);

        // Bail if a filter callback has changed the type of the `$_term` object.
        if(! ($_term instanceof WP_Term))
        {
            return $_term;
        }

        // Sanitize term, according to the specified filter.
        $_term->filter($filter);

        if(ARRAY_A === $output)
        {
            return $_term->to_array();
        }
        elseif(ARRAY_N === $output)
        {
            return array_values($_term->to_array());
        }

        return $_term;
    }

    function get_term_by($field, $value, $taxonomy = '', $output = OBJECT, $filter = 'raw')
    {
        // 'term_taxonomy_id' lookups don't require taxonomy checks.
        if('term_taxonomy_id' !== $field && ! taxonomy_exists($taxonomy))
        {
            return false;
        }

        // No need to perform a query for empty 'slug' or 'name'.
        if('slug' === $field || 'name' === $field)
        {
            $value = (string) $value;

            if($value === '')
            {
                return false;
            }
        }

        if('id' === $field || 'ID' === $field || 'term_id' === $field)
        {
            $term = get_term((int) $value, $taxonomy, $output, $filter);
            if(is_wp_error($term) || null === $term)
            {
                $term = false;
            }

            return $term;
        }

        $args = [
            'get' => 'all',
            'number' => 1,
            'taxonomy' => $taxonomy,
            'update_term_meta_cache' => false,
            'orderby' => 'none',
            'suppress_filter' => true,
        ];

        switch($field)
        {
            case 'slug':
                $args['slug'] = $value;
                break;
            case 'name':
                $args['name'] = $value;
                break;
            case 'term_taxonomy_id':
                $args['term_taxonomy_id'] = $value;
                unset($args['taxonomy']);
                break;
            default:
                return false;
        }

        $terms = get_terms($args);
        if(is_wp_error($terms) || empty($terms))
        {
            return false;
        }

        $term = array_shift($terms);

        // In the case of 'term_taxonomy_id', override the provided `$taxonomy` with whatever we find in the DB.
        if('term_taxonomy_id' === $field)
        {
            $taxonomy = $term->taxonomy;
        }

        return get_term($term, $taxonomy, $output, $filter);
    }

    function get_term_children($term_id, $taxonomy)
    {
        if(! taxonomy_exists($taxonomy))
        {
            return new WP_Error('invalid_taxonomy', __('Invalid taxonomy.'));
        }

        $term_id = (int) $term_id;

        $terms = _get_term_hierarchy($taxonomy);

        if(! isset($terms[$term_id]))
        {
            return [];
        }

        $children = $terms[$term_id];

        foreach((array) $terms[$term_id] as $child)
        {
            if($term_id === $child)
            {
                continue;
            }

            if(isset($terms[$child]))
            {
                $children = array_merge($children, get_term_children($child, $taxonomy));
            }
        }

        return $children;
    }

    function get_term_field($field, $term, $taxonomy = '', $context = 'display')
    {
        $term = get_term($term, $taxonomy);
        if(is_wp_error($term))
        {
            return $term;
        }

        if(! is_object($term) || ! isset($term->$field))
        {
            return '';
        }

        return sanitize_term_field($field, $term->$field, $term->term_id, $term->taxonomy, $context);
    }

    function get_term_to_edit($id, $taxonomy)
    {
        $term = get_term($id, $taxonomy);

        if(is_wp_error($term))
        {
            return $term;
        }

        if(! is_object($term))
        {
            return '';
        }

        return sanitize_term($term, $taxonomy, 'edit');
    }

    function get_terms($args = [], $deprecated = '')
    {
        $term_query = new WP_Term_Query();

        $defaults = [
            'suppress_filter' => false,
        ];

        /*
	 * Legacy argument format ($taxonomy, $args) takes precedence.
	 *
	 * We detect legacy argument format by checking if
	 * (a) a second non-empty parameter is passed, or
	 * (b) the first parameter shares no keys with the default array (ie, it's a list of taxonomies)
	 */
        $_args = wp_parse_args($args);
        $key_intersect = array_intersect_key($term_query->query_var_defaults, (array) $_args);
        $do_legacy_args = $deprecated || empty($key_intersect);

        if($do_legacy_args)
        {
            $taxonomies = (array) $args;
            $args = wp_parse_args($deprecated, $defaults);
            $args['taxonomy'] = $taxonomies;
        }
        else
        {
            $args = wp_parse_args($args, $defaults);
            if(isset($args['taxonomy']) && null !== $args['taxonomy'])
            {
                $args['taxonomy'] = (array) $args['taxonomy'];
            }
        }

        if(! empty($args['taxonomy']))
        {
            foreach($args['taxonomy'] as $taxonomy)
            {
                if(! taxonomy_exists($taxonomy))
                {
                    return new WP_Error('invalid_taxonomy', __('Invalid taxonomy.'));
                }
            }
        }

        // Don't pass suppress_filter to WP_Term_Query.
        $suppress_filter = $args['suppress_filter'];
        unset($args['suppress_filter']);

        $terms = $term_query->query($args);

        // Count queries are not filtered, for legacy reasons.
        if(! is_array($terms) || $suppress_filter)
        {
            return $terms;
        }

        return apply_filters('get_terms', $terms, $term_query->query_vars['taxonomy'], $term_query->query_vars, $term_query);
    }

    function add_term_meta($term_id, $meta_key, $meta_value, $unique = false)
    {
        if(wp_term_is_shared($term_id))
        {
            return new WP_Error('ambiguous_term_id', __('Term meta cannot be added to terms that are shared between taxonomies.'), $term_id);
        }

        return add_metadata('term', $term_id, $meta_key, $meta_value, $unique);
    }

    function delete_term_meta($term_id, $meta_key, $meta_value = '')
    {
        return delete_metadata('term', $term_id, $meta_key, $meta_value);
    }

    function get_term_meta($term_id, $key = '', $single = false)
    {
        return get_metadata('term', $term_id, $key, $single);
    }

    function update_term_meta($term_id, $meta_key, $meta_value, $prev_value = '')
    {
        if(wp_term_is_shared($term_id))
        {
            return new WP_Error('ambiguous_term_id', __('Term meta cannot be added to terms that are shared between taxonomies.'), $term_id);
        }

        return update_metadata('term', $term_id, $meta_key, $meta_value, $prev_value);
    }

    function update_termmeta_cache($term_ids)
    {
        return update_meta_cache('term', $term_ids);
    }

    function wp_lazyload_term_meta(array $term_ids)
    {
        if(empty($term_ids))
        {
            return;
        }
        $lazyloader = wp_metadata_lazyloader();
        $lazyloader->queue_objects('term', $term_ids);
    }

    function has_term_meta($term_id)
    {
        $check = wp_check_term_meta_support_prefilter(null);
        if(null !== $check)
        {
            return $check;
        }

        global $wpdb;

        return $wpdb->get_results($wpdb->prepare("SELECT meta_key, meta_value, meta_id, term_id FROM $wpdb->termmeta WHERE term_id = %d ORDER BY meta_key,meta_id", $term_id), ARRAY_A);
    }

    function register_term_meta($taxonomy, $meta_key, array $args)
    {
        $args['object_subtype'] = $taxonomy;

        return register_meta('term', $meta_key, $args);
    }

    function unregister_term_meta($taxonomy, $meta_key)
    {
        return unregister_meta_key('term', $meta_key, $taxonomy);
    }

    function term_exists($term, $taxonomy = '', $parent_term = null)
    {
        global $_wp_suspend_cache_invalidation;

        if(null === $term)
        {
            return null;
        }

        $defaults = [
            'get' => 'all',
            'fields' => 'ids',
            'number' => 1,
            'update_term_meta_cache' => false,
            'order' => 'ASC',
            'orderby' => 'term_id',
            'suppress_filter' => true,
        ];

        // Ensure that while importing, queries are not cached.
        if(! empty($_wp_suspend_cache_invalidation))
        {
            $defaults['cache_results'] = false;
        }

        if(! empty($taxonomy))
        {
            $defaults['taxonomy'] = $taxonomy;
            $defaults['fields'] = 'all';
        }

        $defaults = apply_filters('term_exists_default_query_args', $defaults, $term, $taxonomy, $parent_term);

        if(is_int($term))
        {
            if(0 === $term)
            {
                return 0;
            }
            $args = wp_parse_args(['include' => [$term]], $defaults);
            $terms = get_terms($args);
        }
        else
        {
            $term = trim(wp_unslash($term));
            if('' === $term)
            {
                return null;
            }

            if(! empty($taxonomy) && is_numeric($parent_term))
            {
                $defaults['parent'] = (int) $parent_term;
            }

            $args = wp_parse_args(['slug' => sanitize_title($term)], $defaults);
            $terms = get_terms($args);
            if(empty($terms) || is_wp_error($terms))
            {
                $args = wp_parse_args(['name' => $term], $defaults);
                $terms = get_terms($args);
            }
        }

        if(empty($terms) || is_wp_error($terms))
        {
            return null;
        }

        $_term = array_shift($terms);

        if(! empty($taxonomy))
        {
            return [
                'term_id' => (string) $_term->term_id,
                'term_taxonomy_id' => (string) $_term->term_taxonomy_id,
            ];
        }

        return (string) $_term;
    }

    function term_is_ancestor_of($term1, $term2, $taxonomy)
    {
        if(! isset($term1->term_id))
        {
            $term1 = get_term($term1, $taxonomy);
        }
        if(! isset($term2->parent))
        {
            $term2 = get_term($term2, $taxonomy);
        }

        if(empty($term1->term_id) || empty($term2->parent))
        {
            return false;
        }
        if($term2->parent === $term1->term_id)
        {
            return true;
        }

        return term_is_ancestor_of($term1, get_term($term2->parent, $taxonomy), $taxonomy);
    }

    function sanitize_term($term, $taxonomy, $context = 'display')
    {
        $fields = [
            'term_id',
            'name',
            'description',
            'slug',
            'count',
            'parent',
            'term_group',
            'term_taxonomy_id',
            'object_id'
        ];

        $do_object = is_object($term);

        $term_id = $do_object ? $term->term_id : (isset($term['term_id']) ? $term['term_id'] : 0);

        foreach((array) $fields as $field)
        {
            if($do_object)
            {
                if(isset($term->$field))
                {
                    $term->$field = sanitize_term_field($field, $term->$field, $term_id, $taxonomy, $context);
                }
            }
            else
            {
                if(isset($term[$field]))
                {
                    $term[$field] = sanitize_term_field($field, $term[$field], $term_id, $taxonomy, $context);
                }
            }
        }

        if($do_object)
        {
            $term->filter = $context;
        }
        else
        {
            $term['filter'] = $context;
        }

        return $term;
    }

    function sanitize_term_field($field, $value, $term_id, $taxonomy, $context)
    {
        $int_fields = ['parent', 'term_id', 'count', 'term_group', 'term_taxonomy_id', 'object_id'];
        if(in_array($field, $int_fields, true))
        {
            $value = (int) $value;
            if($value < 0)
            {
                $value = 0;
            }
        }

        $context = strtolower($context);

        if('raw' === $context)
        {
            return $value;
        }

        if('edit' === $context)
        {
            $value = apply_filters("edit_term_{$field}", $value, $term_id, $taxonomy);

            $value = apply_filters("edit_{$taxonomy}_{$field}", $value, $term_id);

            if('description' === $field)
            {
                $value = esc_html($value); // textarea_escaped
            }
            else
            {
                $value = esc_attr($value);
            }
        }
        elseif('db' === $context)
        {
            $value = apply_filters("pre_term_{$field}", $value, $taxonomy);

            $value = apply_filters("pre_{$taxonomy}_{$field}", $value);

            // Back compat filters.
            if('slug' === $field)
            {
                $value = apply_filters('pre_category_nicename', $value);
            }
        }
        elseif('rss' === $context)
        {
            $value = apply_filters("term_{$field}_rss", $value, $taxonomy);

            $value = apply_filters("{$taxonomy}_{$field}_rss", $value);
        }
        else
        {
            // Use display filters by default.

            $value = apply_filters("term_{$field}", $value, $term_id, $taxonomy, $context);

            $value = apply_filters("{$taxonomy}_{$field}", $value, $term_id, $context);
        }

        if('attribute' === $context)
        {
            $value = esc_attr($value);
        }
        elseif('js' === $context)
        {
            $value = esc_js($value);
        }

        // Restore the type for integer fields after esc_attr().
        if(in_array($field, $int_fields, true))
        {
            $value = (int) $value;
        }

        return $value;
    }

    function wp_count_terms($args = [], $deprecated = '')
    {
        $use_legacy_args = false;

        // Check whether function is used with legacy signature: `$taxonomy` and `$args`.
        if($args && (is_string($args) && taxonomy_exists($args) || is_array($args) && wp_is_numeric_array($args)))
        {
            $use_legacy_args = true;
        }

        $defaults = ['hide_empty' => false];

        if($use_legacy_args)
        {
            $defaults['taxonomy'] = $args;
            $args = $deprecated;
        }

        $args = wp_parse_args($args, $defaults);

        // Backward compatibility.
        if(isset($args['ignore_empty']))
        {
            $args['hide_empty'] = $args['ignore_empty'];
            unset($args['ignore_empty']);
        }

        $args['fields'] = 'count';

        return get_terms($args);
    }

    function wp_delete_object_term_relationships($object_id, $taxonomies)
    {
        $object_id = (int) $object_id;

        if(! is_array($taxonomies))
        {
            $taxonomies = [$taxonomies];
        }

        foreach((array) $taxonomies as $taxonomy)
        {
            $term_ids = wp_get_object_terms($object_id, $taxonomy, ['fields' => 'ids']);
            $term_ids = array_map('intval', $term_ids);
            wp_remove_object_terms($object_id, $term_ids, $taxonomy);
        }
    }

    function wp_delete_term($term, $taxonomy, $args = [])
    {
        global $wpdb;

        $term = (int) $term;

        $ids = term_exists($term, $taxonomy);
        if(! $ids)
        {
            return false;
        }
        if(is_wp_error($ids))
        {
            return $ids;
        }

        $tt_id = $ids['term_taxonomy_id'];

        $defaults = [];

        if('category' === $taxonomy)
        {
            $defaults['default'] = (int) get_option('default_category');
            if($defaults['default'] === $term)
            {
                return 0; // Don't delete the default category.
            }
        }

        // Don't delete the default custom taxonomy term.
        $taxonomy_object = get_taxonomy($taxonomy);
        if(! empty($taxonomy_object->default_term))
        {
            $defaults['default'] = (int) get_option('default_term_'.$taxonomy);
            if($defaults['default'] === $term)
            {
                return 0;
            }
        }

        $args = wp_parse_args($args, $defaults);

        if(isset($args['default']))
        {
            $default = (int) $args['default'];
            if(! term_exists($default, $taxonomy))
            {
                unset($default);
            }
        }

        if(isset($args['force_default']))
        {
            $force_default = $args['force_default'];
        }

        do_action('pre_delete_term', $term, $taxonomy);

        // Update children to point to new parent.
        if(is_taxonomy_hierarchical($taxonomy))
        {
            $term_obj = get_term($term, $taxonomy);
            if(is_wp_error($term_obj))
            {
                return $term_obj;
            }
            $parent = $term_obj->parent;

            $edit_ids = $wpdb->get_results("SELECT term_id, term_taxonomy_id FROM $wpdb->term_taxonomy WHERE `parent` = ".(int) $term_obj->term_id);
            $edit_tt_ids = wp_list_pluck($edit_ids, 'term_taxonomy_id');

            do_action('edit_term_taxonomies', $edit_tt_ids);

            $wpdb->update($wpdb->term_taxonomy, compact('parent'), ['parent' => $term_obj->term_id] + compact('taxonomy'));

            // Clean the cache for all child terms.
            $edit_term_ids = wp_list_pluck($edit_ids, 'term_id');
            clean_term_cache($edit_term_ids, $taxonomy);

            do_action('edited_term_taxonomies', $edit_tt_ids);
        }

        // Get the term before deleting it or its term relationships so we can pass to actions below.
        $deleted_term = get_term($term, $taxonomy);

        $object_ids = (array) $wpdb->get_col($wpdb->prepare("SELECT object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d", $tt_id));

        foreach($object_ids as $object_id)
        {
            if(! isset($default))
            {
                wp_remove_object_terms($object_id, $term, $taxonomy);
                continue;
            }

            $terms = wp_get_object_terms($object_id, $taxonomy, [
                'fields' => 'ids',
                'orderby' => 'none',
            ]);

            if(1 === count($terms) && isset($default))
            {
                $terms = [$default];
            }
            else
            {
                $terms = array_diff($terms, [$term]);
                if(isset($default) && isset($force_default) && $force_default)
                {
                    $terms = array_merge($terms, [$default]);
                }
            }

            $terms = array_map('intval', $terms);
            wp_set_object_terms($object_id, $terms, $taxonomy);
        }

        // Clean the relationship caches for all object types using this term.
        $tax_object = get_taxonomy($taxonomy);
        foreach($tax_object->object_type as $object_type)
        {
            clean_object_term_cache($object_ids, $object_type);
        }

        $term_meta_ids = $wpdb->get_col($wpdb->prepare("SELECT meta_id FROM $wpdb->termmeta WHERE term_id = %d ", $term));
        foreach($term_meta_ids as $mid)
        {
            delete_metadata_by_mid('term', $mid);
        }

        do_action('delete_term_taxonomy', $tt_id);

        $wpdb->delete($wpdb->term_taxonomy, ['term_taxonomy_id' => $tt_id]);

        do_action('deleted_term_taxonomy', $tt_id);

        // Delete the term if no taxonomies use it.
        if(! $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->term_taxonomy WHERE term_id = %d", $term)))
        {
            $wpdb->delete($wpdb->terms, ['term_id' => $term]);
        }

        clean_term_cache($term, $taxonomy);

        do_action('delete_term', $term, $tt_id, $taxonomy, $deleted_term, $object_ids);

        do_action("delete_{$taxonomy}", $term, $tt_id, $deleted_term, $object_ids);

        return true;
    }

    function wp_delete_category($cat_id)
    {
        return wp_delete_term($cat_id, 'category');
    }

    function wp_get_object_terms($object_ids, $taxonomies, $args = [])
    {
        if(empty($object_ids) || empty($taxonomies))
        {
            return [];
        }

        if(! is_array($taxonomies))
        {
            $taxonomies = [$taxonomies];
        }

        foreach($taxonomies as $taxonomy)
        {
            if(! taxonomy_exists($taxonomy))
            {
                return new WP_Error('invalid_taxonomy', __('Invalid taxonomy.'));
            }
        }

        if(! is_array($object_ids))
        {
            $object_ids = [$object_ids];
        }
        $object_ids = array_map('intval', $object_ids);

        $defaults = [
            'update_term_meta_cache' => false,
        ];

        $args = wp_parse_args($args, $defaults);

        $args = apply_filters('wp_get_object_terms_args', $args, $object_ids, $taxonomies);

        /*
	 * When one or more queried taxonomies is registered with an 'args' array,
	 * those params override the `$args` passed to this function.
	 */
        $terms = [];
        if(count($taxonomies) > 1)
        {
            foreach($taxonomies as $index => $taxonomy)
            {
                $t = get_taxonomy($taxonomy);
                if(isset($t->args) && is_array($t->args) && array_merge($args, $t->args) != $args)
                {
                    unset($taxonomies[$index]);
                    $terms = array_merge($terms, wp_get_object_terms($object_ids, $taxonomy, array_merge($args, $t->args)));
                }
            }
        }
        else
        {
            $t = get_taxonomy($taxonomies[0]);
            if(isset($t->args) && is_array($t->args))
            {
                $args = array_merge($args, $t->args);
            }
        }

        $args['taxonomy'] = $taxonomies;
        $args['object_ids'] = $object_ids;

        // Taxonomies registered without an 'args' param are handled here.
        if(! empty($taxonomies))
        {
            $terms_from_remaining_taxonomies = get_terms($args);

            // Array keys should be preserved for values of $fields that use term_id for keys.
            if(! empty($args['fields']) && str_starts_with($args['fields'], 'id=>'))
            {
                $terms = $terms + $terms_from_remaining_taxonomies;
            }
            else
            {
                $terms = array_merge($terms, $terms_from_remaining_taxonomies);
            }
        }

        $terms = apply_filters('get_object_terms', $terms, $object_ids, $taxonomies, $args);

        $object_ids = implode(',', $object_ids);
        $taxonomies = "'".implode("', '", array_map('esc_sql', $taxonomies))."'";

        return apply_filters('wp_get_object_terms', $terms, $object_ids, $taxonomies, $args);
    }

    function wp_insert_term($term, $taxonomy, $args = [])
    {
        global $wpdb;

        if(! taxonomy_exists($taxonomy))
        {
            return new WP_Error('invalid_taxonomy', __('Invalid taxonomy.'));
        }

        $term = apply_filters('pre_insert_term', $term, $taxonomy, $args);

        if(is_wp_error($term))
        {
            return $term;
        }

        if(is_int($term) && 0 === $term)
        {
            return new WP_Error('invalid_term_id', __('Invalid term ID.'));
        }

        if('' === trim($term))
        {
            return new WP_Error('empty_term_name', __('A name is required for this term.'));
        }

        $defaults = [
            'alias_of' => '',
            'description' => '',
            'parent' => 0,
            'slug' => '',
        ];
        $args = wp_parse_args($args, $defaults);

        if((int) $args['parent'] > 0 && ! term_exists((int) $args['parent']))
        {
            return new WP_Error('missing_parent', __('Parent term does not exist.'));
        }

        $args['name'] = $term;
        $args['taxonomy'] = $taxonomy;

        // Coerce null description to strings, to avoid database errors.
        $args['description'] = (string) $args['description'];

        $args = sanitize_term($args, $taxonomy, 'db');

        // expected_slashed ($name)
        $name = wp_unslash($args['name']);
        $description = wp_unslash($args['description']);
        $parent = (int) $args['parent'];

        $slug_provided = ! empty($args['slug']);
        if($slug_provided)
        {
            $slug = $args['slug'];
        }
        else
        {
            $slug = sanitize_title($name);
        }

        $term_group = 0;
        if($args['alias_of'])
        {
            $alias = get_term_by('slug', $args['alias_of'], $taxonomy);
            if(! empty($alias->term_group))
            {
                // The alias we want is already in a group, so let's use that one.
                $term_group = $alias->term_group;
            }
            elseif(! empty($alias->term_id))
            {
                /*
			 * The alias is not in a group, so we create a new one
			 * and add the alias to it.
			 */
                $term_group = $wpdb->get_var("SELECT MAX(term_group) FROM $wpdb->terms") + 1;

                wp_update_term($alias->term_id, $taxonomy, [
                    'term_group' => $term_group,
                ]);
            }
        }

        /*
	 * Prevent the creation of terms with duplicate names at the same level of a taxonomy hierarchy,
	 * unless a unique slug has been explicitly provided.
	 */
        $name_matches = get_terms([
                                      'taxonomy' => $taxonomy,
                                      'name' => $name,
                                      'hide_empty' => false,
                                      'parent' => $args['parent'],
                                      'update_term_meta_cache' => false,
                                  ]);

        /*
	 * The `name` match in `get_terms()` doesn't differentiate accented characters,
	 * so we do a stricter comparison here.
	 */
        $name_match = null;
        if($name_matches)
        {
            foreach($name_matches as $_match)
            {
                if(strtolower($name) === strtolower($_match->name))
                {
                    $name_match = $_match;
                    break;
                }
            }
        }

        if($name_match)
        {
            $slug_match = get_term_by('slug', $slug, $taxonomy);
            if(! $slug_provided || $name_match->slug === $slug || $slug_match)
            {
                if(is_taxonomy_hierarchical($taxonomy))
                {
                    $siblings = get_terms([
                                              'taxonomy' => $taxonomy,
                                              'get' => 'all',
                                              'parent' => $parent,
                                              'update_term_meta_cache' => false,
                                          ]);

                    $existing_term = null;
                    $sibling_names = wp_list_pluck($siblings, 'name');
                    $sibling_slugs = wp_list_pluck($siblings, 'slug');

                    if((! $slug_provided || $name_match->slug === $slug) && in_array($name, $sibling_names, true))
                    {
                        $existing_term = $name_match;
                    }
                    elseif($slug_match && in_array($slug, $sibling_slugs, true))
                    {
                        $existing_term = $slug_match;
                    }

                    if($existing_term)
                    {
                        return new WP_Error('term_exists', __('A term with the name provided already exists with this parent.'), $existing_term->term_id);
                    }
                }
                else
                {
                    return new WP_Error('term_exists', __('A term with the name provided already exists in this taxonomy.'), $name_match->term_id);
                }
            }
        }

        $slug = wp_unique_term_slug($slug, (object) $args);

        $data = compact('name', 'slug', 'term_group');

        $data = apply_filters('wp_insert_term_data', $data, $taxonomy, $args);

        if(false === $wpdb->insert($wpdb->terms, $data))
        {
            return new WP_Error('db_insert_error', __('Could not insert term into the database.'), $wpdb->last_error);
        }

        $term_id = (int) $wpdb->insert_id;

        // Seems unreachable. However, is used in the case that a term name is provided, which sanitizes to an empty string.
        if(empty($slug))
        {
            $slug = sanitize_title($slug, $term_id);

            do_action('edit_terms', $term_id, $taxonomy);
            $wpdb->update($wpdb->terms, compact('slug'), compact('term_id'));

            do_action('edited_terms', $term_id, $taxonomy);
        }

        $tt_id = $wpdb->get_var($wpdb->prepare("SELECT tt.term_taxonomy_id FROM $wpdb->term_taxonomy AS tt INNER JOIN $wpdb->terms AS t ON tt.term_id = t.term_id WHERE tt.taxonomy = %s AND t.term_id = %d", $taxonomy, $term_id));

        if(! empty($tt_id))
        {
            return [
                'term_id' => $term_id,
                'term_taxonomy_id' => $tt_id,
            ];
        }

        if(false === $wpdb->insert($wpdb->term_taxonomy, compact('term_id', 'taxonomy', 'description', 'parent') + ['count' => 0]))
        {
            return new WP_Error('db_insert_error', __('Could not insert term taxonomy into the database.'), $wpdb->last_error);
        }

        $tt_id = (int) $wpdb->insert_id;

        /*
	 * Sanity check: if we just created a term with the same parent + taxonomy + slug but a higher term_id than
	 * an existing term, then we have unwittingly created a duplicate term. Delete the dupe, and use the term_id
	 * and term_taxonomy_id of the older term instead. Then return out of the function so that the "create" hooks
	 * are not fired.
	 */
        $duplicate_term = $wpdb->get_row($wpdb->prepare("SELECT t.term_id, t.slug, tt.term_taxonomy_id, tt.taxonomy FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON ( tt.term_id = t.term_id ) WHERE t.slug = %s AND tt.parent = %d AND tt.taxonomy = %s AND t.term_id < %d AND tt.term_taxonomy_id != %d", $slug, $parent, $taxonomy, $term_id, $tt_id));

        $duplicate_term = apply_filters('wp_insert_term_duplicate_term_check', $duplicate_term, $term, $taxonomy, $args, $tt_id);

        if($duplicate_term)
        {
            $wpdb->delete($wpdb->terms, ['term_id' => $term_id]);
            $wpdb->delete($wpdb->term_taxonomy, ['term_taxonomy_id' => $tt_id]);

            $term_id = (int) $duplicate_term->term_id;
            $tt_id = (int) $duplicate_term->term_taxonomy_id;

            clean_term_cache($term_id, $taxonomy);

            return [
                'term_id' => $term_id,
                'term_taxonomy_id' => $tt_id,
            ];
        }

        do_action('create_term', $term_id, $tt_id, $taxonomy, $args);

        do_action("create_{$taxonomy}", $term_id, $tt_id, $args);

        $term_id = apply_filters('term_id_filter', $term_id, $tt_id, $args);

        clean_term_cache($term_id, $taxonomy);

        do_action('created_term', $term_id, $tt_id, $taxonomy, $args);

        do_action("created_{$taxonomy}", $term_id, $tt_id, $args);

        do_action('saved_term', $term_id, $tt_id, $taxonomy, false, $args);

        do_action("saved_{$taxonomy}", $term_id, $tt_id, false, $args);

        return [
            'term_id' => $term_id,
            'term_taxonomy_id' => $tt_id,
        ];
    }

    function wp_set_object_terms($object_id, $terms, $taxonomy, $append = false)
    {
        global $wpdb;

        $object_id = (int) $object_id;

        if(! taxonomy_exists($taxonomy))
        {
            return new WP_Error('invalid_taxonomy', __('Invalid taxonomy.'));
        }

        if(empty($terms))
        {
            $terms = [];
        }
        elseif(! is_array($terms))
        {
            $terms = [$terms];
        }

        if($append)
        {
            $old_tt_ids = [];
        }
        else
        {
            $old_tt_ids = wp_get_object_terms($object_id, $taxonomy, [
                'fields' => 'tt_ids',
                'orderby' => 'none',
                'update_term_meta_cache' => false,
            ]);
        }

        $tt_ids = [];
        $term_ids = [];
        $new_tt_ids = [];

        foreach((array) $terms as $term)
        {
            if('' === trim($term))
            {
                continue;
            }

            $term_info = term_exists($term, $taxonomy);

            if(! $term_info)
            {
                // Skip if a non-existent term ID is passed.
                if(is_int($term))
                {
                    continue;
                }

                $term_info = wp_insert_term($term, $taxonomy);
            }

            if(is_wp_error($term_info))
            {
                return $term_info;
            }

            $term_ids[] = $term_info['term_id'];
            $tt_id = $term_info['term_taxonomy_id'];
            $tt_ids[] = $tt_id;

            if($wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM $wpdb->term_relationships WHERE object_id = %d AND term_taxonomy_id = %d", $object_id, $tt_id)))
            {
                continue;
            }

            do_action('add_term_relationship', $object_id, $tt_id, $taxonomy);

            $wpdb->insert($wpdb->term_relationships, [
                'object_id' => $object_id,
                'term_taxonomy_id' => $tt_id,
            ]);

            do_action('added_term_relationship', $object_id, $tt_id, $taxonomy);

            $new_tt_ids[] = $tt_id;
        }

        if($new_tt_ids)
        {
            wp_update_term_count($new_tt_ids, $taxonomy);
        }

        if(! $append)
        {
            $delete_tt_ids = array_diff($old_tt_ids, $tt_ids);

            if($delete_tt_ids)
            {
                $in_delete_tt_ids = "'".implode("', '", $delete_tt_ids)."'";
                $delete_term_ids = $wpdb->get_col($wpdb->prepare("SELECT tt.term_id FROM $wpdb->term_taxonomy AS tt WHERE tt.taxonomy = %s AND tt.term_taxonomy_id IN ($in_delete_tt_ids)", $taxonomy));
                $delete_term_ids = array_map('intval', $delete_term_ids);

                $remove = wp_remove_object_terms($object_id, $delete_term_ids, $taxonomy);
                if(is_wp_error($remove))
                {
                    return $remove;
                }
            }
        }

        $t = get_taxonomy($taxonomy);

        if(! $append && isset($t->sort) && $t->sort)
        {
            $values = [];
            $term_order = 0;

            $final_tt_ids = wp_get_object_terms($object_id, $taxonomy, [
                'fields' => 'tt_ids',
                'update_term_meta_cache' => false,
            ]);

            foreach($tt_ids as $tt_id)
            {
                if(in_array((int) $tt_id, $final_tt_ids, true))
                {
                    $values[] = $wpdb->prepare('(%d, %d, %d)', $object_id, $tt_id, ++$term_order);
                }
            }

            if($values && false === $wpdb->query("INSERT INTO $wpdb->term_relationships (object_id, term_taxonomy_id, term_order) VALUES ".implode(',', $values).' ON DUPLICATE KEY UPDATE term_order = VALUES(term_order)'))
            {
                return new WP_Error('db_insert_error', __('Could not insert term relationship into the database.'), $wpdb->last_error);
            }
        }

        wp_cache_delete($object_id, $taxonomy.'_relationships');
        wp_cache_set_terms_last_changed();

        do_action('set_object_terms', $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids);

        return $tt_ids;
    }

    function wp_add_object_terms($object_id, $terms, $taxonomy)
    {
        return wp_set_object_terms($object_id, $terms, $taxonomy, true);
    }

    function wp_remove_object_terms($object_id, $terms, $taxonomy)
    {
        global $wpdb;

        $object_id = (int) $object_id;

        if(! taxonomy_exists($taxonomy))
        {
            return new WP_Error('invalid_taxonomy', __('Invalid taxonomy.'));
        }

        if(! is_array($terms))
        {
            $terms = [$terms];
        }

        $tt_ids = [];

        foreach((array) $terms as $term)
        {
            if('' === trim($term))
            {
                continue;
            }

            $term_info = term_exists($term, $taxonomy);
            if(! $term_info && is_int($term))
            {
                continue;
            }

            if(is_wp_error($term_info))
            {
                return $term_info;
            }

            $tt_ids[] = $term_info['term_taxonomy_id'];
        }

        if($tt_ids)
        {
            $in_tt_ids = "'".implode("', '", $tt_ids)."'";

            do_action('delete_term_relationships', $object_id, $tt_ids, $taxonomy);

            $deleted = $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->term_relationships WHERE object_id = %d AND term_taxonomy_id IN ($in_tt_ids)", $object_id));

            wp_cache_delete($object_id, $taxonomy.'_relationships');
            wp_cache_set_terms_last_changed();

            do_action('deleted_term_relationships', $object_id, $tt_ids, $taxonomy);

            wp_update_term_count($tt_ids, $taxonomy);

            return (bool) $deleted;
        }

        return false;
    }

    function wp_unique_term_slug($slug, $term)
    {
        global $wpdb;

        $needs_suffix = true;
        $original_slug = $slug;

        // As of 4.1, duplicate slugs are allowed as long as they're in different taxonomies.
        if(! term_exists($slug) || get_option('db_version') >= 30133 && ! get_term_by('slug', $slug, $term->taxonomy))
        {
            $needs_suffix = false;
        }

        /*
	 * If the taxonomy supports hierarchy and the term has a parent, make the slug unique
	 * by incorporating parent slugs.
	 */
        $parent_suffix = '';
        if($needs_suffix && is_taxonomy_hierarchical($term->taxonomy) && ! empty($term->parent))
        {
            $the_parent = $term->parent;
            while(! empty($the_parent))
            {
                $parent_term = get_term($the_parent, $term->taxonomy);
                if(is_wp_error($parent_term) || empty($parent_term))
                {
                    break;
                }
                $parent_suffix .= '-'.$parent_term->slug;
                if(! term_exists($slug.$parent_suffix))
                {
                    break;
                }

                if(empty($parent_term->parent))
                {
                    break;
                }
                $the_parent = $parent_term->parent;
            }
        }

        // If we didn't get a unique slug, try appending a number to make it unique.

        if(apply_filters('wp_unique_term_slug_is_bad_slug', $needs_suffix, $slug, $term))
        {
            if($parent_suffix)
            {
                $slug .= $parent_suffix;
            }

            if(! empty($term->term_id))
            {
                $query = $wpdb->prepare("SELECT slug FROM $wpdb->terms WHERE slug = %s AND term_id != %d", $slug, $term->term_id);
            }
            else
            {
                $query = $wpdb->prepare("SELECT slug FROM $wpdb->terms WHERE slug = %s", $slug);
            }

            if($wpdb->get_var($query))
            { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $num = 2;
                do
                {
                    $alt_slug = $slug."-$num";
                    ++$num;
                    $slug_check = $wpdb->get_var($wpdb->prepare("SELECT slug FROM $wpdb->terms WHERE slug = %s", $alt_slug));
                }
                while($slug_check);
                $slug = $alt_slug;
            }
        }

        return apply_filters('wp_unique_term_slug', $slug, $term, $original_slug);
    }

    function wp_update_term($term_id, $taxonomy, $args = [])
    {
        global $wpdb;

        if(! taxonomy_exists($taxonomy))
        {
            return new WP_Error('invalid_taxonomy', __('Invalid taxonomy.'));
        }

        $term_id = (int) $term_id;

        // First, get all of the original args.
        $term = get_term($term_id, $taxonomy);

        if(is_wp_error($term))
        {
            return $term;
        }

        if(! $term)
        {
            return new WP_Error('invalid_term', __('Empty Term.'));
        }

        $term = (array) $term->data;

        // Escape data pulled from DB.
        $term = wp_slash($term);

        // Merge old and new args with new args overwriting old ones.
        $args = array_merge($term, $args);

        $defaults = [
            'alias_of' => '',
            'description' => '',
            'parent' => 0,
            'slug' => '',
        ];
        $args = wp_parse_args($args, $defaults);
        $args = sanitize_term($args, $taxonomy, 'db');
        $parsed_args = $args;

        // expected_slashed ($name)
        $name = wp_unslash($args['name']);
        $description = wp_unslash($args['description']);

        $parsed_args['name'] = $name;
        $parsed_args['description'] = $description;

        if('' === trim($name))
        {
            return new WP_Error('empty_term_name', __('A name is required for this term.'));
        }

        if((int) $parsed_args['parent'] > 0 && ! term_exists((int) $parsed_args['parent']))
        {
            return new WP_Error('missing_parent', __('Parent term does not exist.'));
        }

        $empty_slug = false;
        if(empty($args['slug']))
        {
            $empty_slug = true;
            $slug = sanitize_title($name);
        }
        else
        {
            $slug = $args['slug'];
        }

        $parsed_args['slug'] = $slug;

        $term_group = isset($parsed_args['term_group']) ? $parsed_args['term_group'] : 0;
        if($args['alias_of'])
        {
            $alias = get_term_by('slug', $args['alias_of'], $taxonomy);
            if(! empty($alias->term_group))
            {
                // The alias we want is already in a group, so let's use that one.
                $term_group = $alias->term_group;
            }
            elseif(! empty($alias->term_id))
            {
                /*
			 * The alias is not in a group, so we create a new one
			 * and add the alias to it.
			 */
                $term_group = $wpdb->get_var("SELECT MAX(term_group) FROM $wpdb->terms") + 1;

                wp_update_term($alias->term_id, $taxonomy, [
                    'term_group' => $term_group,
                ]);
            }

            $parsed_args['term_group'] = $term_group;
        }

        $parent = (int) apply_filters('wp_update_term_parent', $args['parent'], $term_id, $taxonomy, $parsed_args, $args);

        // Check for duplicate slug.
        $duplicate = get_term_by('slug', $slug, $taxonomy);
        if($duplicate && $duplicate->term_id !== $term_id)
        {
            /*
		 * If an empty slug was passed or the parent changed, reset the slug to something unique.
		 * Otherwise, bail.
		 */
            if($empty_slug || ($parent !== (int) $term['parent']))
            {
                $slug = wp_unique_term_slug($slug, (object) $args);
            }
            else
            {
                /* translators: %s: Taxonomy term slug. */
                return new WP_Error('duplicate_term_slug', sprintf(__('The slug &#8220;%s&#8221; is already in use by another term.'), $slug));
            }
        }

        $tt_id = (int) $wpdb->get_var($wpdb->prepare("SELECT tt.term_taxonomy_id FROM $wpdb->term_taxonomy AS tt INNER JOIN $wpdb->terms AS t ON tt.term_id = t.term_id WHERE tt.taxonomy = %s AND t.term_id = %d", $taxonomy, $term_id));

        // Check whether this is a shared term that needs splitting.
        $_term_id = _split_shared_term($term_id, $tt_id);
        if(! is_wp_error($_term_id))
        {
            $term_id = $_term_id;
        }

        do_action('edit_terms', $term_id, $taxonomy, $args);

        $data = compact('name', 'slug', 'term_group');

        $data = apply_filters('wp_update_term_data', $data, $term_id, $taxonomy, $args);

        $wpdb->update($wpdb->terms, $data, compact('term_id'));

        if(empty($slug))
        {
            $slug = sanitize_title($name, $term_id);
            $wpdb->update($wpdb->terms, compact('slug'), compact('term_id'));
        }

        do_action('edited_terms', $term_id, $taxonomy, $args);

        do_action('edit_term_taxonomy', $tt_id, $taxonomy, $args);

        $wpdb->update($wpdb->term_taxonomy, compact('term_id', 'taxonomy', 'description', 'parent'), ['term_taxonomy_id' => $tt_id]);

        do_action('edited_term_taxonomy', $tt_id, $taxonomy, $args);

        do_action('edit_term', $term_id, $tt_id, $taxonomy, $args);

        do_action("edit_{$taxonomy}", $term_id, $tt_id, $args);

        $term_id = apply_filters('term_id_filter', $term_id, $tt_id);

        clean_term_cache($term_id, $taxonomy);

        do_action('edited_term', $term_id, $tt_id, $taxonomy, $args);

        do_action("edited_{$taxonomy}", $term_id, $tt_id, $args);

        do_action('saved_term', $term_id, $tt_id, $taxonomy, true, $args);

        do_action("saved_{$taxonomy}", $term_id, $tt_id, true, $args);

        return [
            'term_id' => $term_id,
            'term_taxonomy_id' => $tt_id,
        ];
    }

    function wp_defer_term_counting($defer = null)
    {
        static $_defer = false;

        if(is_bool($defer))
        {
            $_defer = $defer;
            // Flush any deferred counts.
            if(! $defer)
            {
                wp_update_term_count(null, null, true);
            }
        }

        return $_defer;
    }

    function wp_update_term_count($terms, $taxonomy, $do_deferred = false)
    {
        static $_deferred = [];

        if($do_deferred)
        {
            foreach((array) array_keys($_deferred) as $tax)
            {
                wp_update_term_count_now($_deferred[$tax], $tax);
                unset($_deferred[$tax]);
            }
        }

        if(empty($terms))
        {
            return false;
        }

        if(! is_array($terms))
        {
            $terms = [$terms];
        }

        if(wp_defer_term_counting())
        {
            if(! isset($_deferred[$taxonomy]))
            {
                $_deferred[$taxonomy] = [];
            }
            $_deferred[$taxonomy] = array_unique(array_merge($_deferred[$taxonomy], $terms));

            return true;
        }

        return wp_update_term_count_now($terms, $taxonomy);
    }

    function wp_update_term_count_now($terms, $taxonomy)
    {
        $terms = array_map('intval', $terms);

        $taxonomy = get_taxonomy($taxonomy);
        if(! empty($taxonomy->update_count_callback))
        {
            call_user_func($taxonomy->update_count_callback, $terms, $taxonomy);
        }
        else
        {
            $object_types = (array) $taxonomy->object_type;
            foreach($object_types as &$object_type)
            {
                if(str_starts_with($object_type, 'attachment:'))
                {
                    [$object_type] = explode(':', $object_type);
                }
            }

            if(array_filter($object_types, 'post_type_exists') == $object_types)
            {
                // Only post types are attached to this taxonomy.
                _update_post_term_count($terms, $taxonomy);
            }
            else
            {
                // Default count updater.
                _update_generic_term_count($terms, $taxonomy);
            }
        }

        clean_term_cache($terms, '', false);

        return true;
    }

//
// Cache.
//

    function clean_object_term_cache($object_ids, $object_type)
    {
        global $_wp_suspend_cache_invalidation;

        if(! empty($_wp_suspend_cache_invalidation))
        {
            return;
        }

        if(! is_array($object_ids))
        {
            $object_ids = [$object_ids];
        }

        $taxonomies = get_object_taxonomies($object_type);

        foreach($taxonomies as $taxonomy)
        {
            wp_cache_delete_multiple($object_ids, "{$taxonomy}_relationships");
        }

        wp_cache_set_terms_last_changed();

        do_action('clean_object_term_cache', $object_ids, $object_type);
    }

    function clean_term_cache($ids, $taxonomy = '', $clean_taxonomy = true)
    {
        global $wpdb, $_wp_suspend_cache_invalidation;

        if(! empty($_wp_suspend_cache_invalidation))
        {
            return;
        }

        if(! is_array($ids))
        {
            $ids = [$ids];
        }

        $taxonomies = [];
        // If no taxonomy, assume tt_ids.
        if(empty($taxonomy))
        {
            $tt_ids = array_map('intval', $ids);
            $tt_ids = implode(', ', $tt_ids);
            $terms = $wpdb->get_results("SELECT term_id, taxonomy FROM $wpdb->term_taxonomy WHERE term_taxonomy_id IN ($tt_ids)");
            $ids = [];

            foreach((array) $terms as $term)
            {
                $taxonomies[] = $term->taxonomy;
                $ids[] = $term->term_id;
            }
            wp_cache_delete_multiple($ids, 'terms');
            $taxonomies = array_unique($taxonomies);
        }
        else
        {
            wp_cache_delete_multiple($ids, 'terms');
            $taxonomies = [$taxonomy];
        }

        foreach($taxonomies as $taxonomy)
        {
            if($clean_taxonomy)
            {
                clean_taxonomy_cache($taxonomy);
            }

            do_action('clean_term_cache', $ids, $taxonomy, $clean_taxonomy);
        }

        wp_cache_set_terms_last_changed();
    }

    function clean_taxonomy_cache($taxonomy)
    {
        wp_cache_delete('all_ids', $taxonomy);
        wp_cache_delete('get', $taxonomy);
        wp_cache_set_terms_last_changed();

        // Regenerate cached hierarchy.
        delete_option("{$taxonomy}_children");
        _get_term_hierarchy($taxonomy);

        do_action('clean_taxonomy_cache', $taxonomy);
    }

    function get_object_term_cache($id, $taxonomy)
    {
        $_term_ids = wp_cache_get($id, "{$taxonomy}_relationships");

        // We leave the priming of relationship caches to upstream functions.
        if(false === $_term_ids)
        {
            return false;
        }

        // Backward compatibility for if a plugin is putting objects into the cache, rather than IDs.
        $term_ids = [];
        foreach($_term_ids as $term_id)
        {
            if(is_numeric($term_id))
            {
                $term_ids[] = (int) $term_id;
            }
            elseif(isset($term_id->term_id))
            {
                $term_ids[] = (int) $term_id->term_id;
            }
        }

        // Fill the term objects.
        _prime_term_caches($term_ids);

        $terms = [];
        foreach($term_ids as $term_id)
        {
            $term = get_term($term_id, $taxonomy);
            if(is_wp_error($term))
            {
                return $term;
            }

            $terms[] = $term;
        }

        return $terms;
    }

    function update_object_term_cache($object_ids, $object_type)
    {
        if(empty($object_ids))
        {
            return;
        }

        if(! is_array($object_ids))
        {
            $object_ids = explode(',', $object_ids);
        }

        $object_ids = array_map('intval', $object_ids);
        $non_cached_ids = [];

        $taxonomies = get_object_taxonomies($object_type);

        foreach($taxonomies as $taxonomy)
        {
            $cache_values = wp_cache_get_multiple((array) $object_ids, "{$taxonomy}_relationships");

            foreach($cache_values as $id => $value)
            {
                if(false === $value)
                {
                    $non_cached_ids[] = $id;
                }
            }
        }

        if(empty($non_cached_ids))
        {
            return false;
        }

        $non_cached_ids = array_unique($non_cached_ids);

        $terms = wp_get_object_terms($non_cached_ids, $taxonomies, [
            'fields' => 'all_with_object_id',
            'orderby' => 'name',
            'update_term_meta_cache' => false,
        ]);

        $object_terms = [];
        foreach((array) $terms as $term)
        {
            $object_terms[$term->object_id][$term->taxonomy][] = $term->term_id;
        }

        foreach($non_cached_ids as $id)
        {
            foreach($taxonomies as $taxonomy)
            {
                if(! isset($object_terms[$id][$taxonomy]))
                {
                    if(! isset($object_terms[$id]))
                    {
                        $object_terms[$id] = [];
                    }
                    $object_terms[$id][$taxonomy] = [];
                }
            }
        }

        $cache_values = [];
        foreach($object_terms as $id => $value)
        {
            foreach($value as $taxonomy => $terms)
            {
                $cache_values[$taxonomy][$id] = $terms;
            }
        }
        foreach($cache_values as $taxonomy => $data)
        {
            wp_cache_add_multiple($data, "{$taxonomy}_relationships");
        }
    }

    function update_term_cache($terms, $taxonomy = '')
    {
        $data = [];
        foreach((array) $terms as $term)
        {
            // Create a copy in case the array was passed by reference.
            $_term = clone $term;

            // Object ID should not be cached.
            unset($_term->object_id);

            $data[$term->term_id] = $_term;
        }
        wp_cache_add_multiple($data, 'terms');
    }

//
// Private.
//

    function _get_term_hierarchy($taxonomy)
    {
        if(! is_taxonomy_hierarchical($taxonomy))
        {
            return [];
        }
        $children = get_option("{$taxonomy}_children");

        if(is_array($children))
        {
            return $children;
        }
        $children = [];
        $terms = get_terms([
                               'taxonomy' => $taxonomy,
                               'get' => 'all',
                               'orderby' => 'id',
                               'fields' => 'id=>parent',
                               'update_term_meta_cache' => false,
                           ]);
        foreach($terms as $term_id => $parent)
        {
            if($parent > 0)
            {
                $children[$parent][] = $term_id;
            }
        }
        update_option("{$taxonomy}_children", $children);

        return $children;
    }

    function _get_term_children($term_id, $terms, $taxonomy, &$ancestors = [])
    {
        $empty_array = [];
        if(empty($terms))
        {
            return $empty_array;
        }

        $term_id = (int) $term_id;
        $term_list = [];
        $has_children = _get_term_hierarchy($taxonomy);

        if($term_id && ! isset($has_children[$term_id]))
        {
            return $empty_array;
        }

        // Include the term itself in the ancestors array, so we can properly detect when a loop has occurred.
        if(empty($ancestors))
        {
            $ancestors[$term_id] = 1;
        }

        foreach((array) $terms as $term)
        {
            $use_id = false;
            if(! is_object($term))
            {
                $term = get_term($term, $taxonomy);
                if(is_wp_error($term))
                {
                    return $term;
                }
                $use_id = true;
            }

            // Don't recurse if we've already identified the term as a child - this indicates a loop.
            if(isset($ancestors[$term->term_id]))
            {
                continue;
            }

            if((int) $term->parent === $term_id)
            {
                if($use_id)
                {
                    $term_list[] = $term->term_id;
                }
                else
                {
                    $term_list[] = $term;
                }

                if(! isset($has_children[$term->term_id]))
                {
                    continue;
                }

                $ancestors[$term->term_id] = 1;

                $children = _get_term_children($term->term_id, $terms, $taxonomy, $ancestors);
                if($children)
                {
                    $term_list = array_merge($term_list, $children);
                }
            }
        }

        return $term_list;
    }

    function _pad_term_counts(&$terms, $taxonomy)
    {
        global $wpdb;

        // This function only works for hierarchical taxonomies like post categories.
        if(! is_taxonomy_hierarchical($taxonomy))
        {
            return;
        }

        $term_hier = _get_term_hierarchy($taxonomy);

        if(empty($term_hier))
        {
            return;
        }

        $term_items = [];
        $terms_by_id = [];
        $term_ids = [];

        foreach((array) $terms as $key => $term)
        {
            $terms_by_id[$term->term_id] = &$terms[$key];
            $term_ids[$term->term_taxonomy_id] = $term->term_id;
        }

        // Get the object and term IDs and stick them in a lookup table.
        $tax_obj = get_taxonomy($taxonomy);
        $object_types = esc_sql($tax_obj->object_type);
        $results = $wpdb->get_results("SELECT object_id, term_taxonomy_id FROM $wpdb->term_relationships INNER JOIN $wpdb->posts ON object_id = ID WHERE term_taxonomy_id IN (".implode(',', array_keys($term_ids)).") AND post_type IN ('".implode("', '", $object_types)."') AND post_status = 'publish'");

        foreach($results as $row)
        {
            $id = $term_ids[$row->term_taxonomy_id];

            $term_items[$id][$row->object_id] = isset($term_items[$id][$row->object_id]) ? ++$term_items[$id][$row->object_id] : 1;
        }

        // Touch every ancestor's lookup row for each post in each term.
        foreach($term_ids as $term_id)
        {
            $child = $term_id;
            $ancestors = [];
            while(! empty($terms_by_id[$child]) && $parent = $terms_by_id[$child]->parent)
            {
                $ancestors[] = $child;

                if(! empty($term_items[$term_id]))
                {
                    foreach($term_items[$term_id] as $item_id => $touches)
                    {
                        $term_items[$parent][$item_id] = isset($term_items[$parent][$item_id]) ? ++$term_items[$parent][$item_id] : 1;
                    }
                }

                $child = $parent;

                if(in_array($parent, $ancestors, true))
                {
                    break;
                }
            }
        }

        // Transfer the touched cells.
        foreach((array) $term_items as $id => $items)
        {
            if(isset($terms_by_id[$id]))
            {
                $terms_by_id[$id]->count = count($items);
            }
        }
    }

    function _prime_term_caches($term_ids, $update_meta_cache = true)
    {
        global $wpdb;

        $non_cached_ids = _get_non_cached_ids($term_ids, 'terms');
        if(! empty($non_cached_ids))
        {
            $fresh_terms = $wpdb->get_results(sprintf("SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE t.term_id IN (%s)", implode(',', array_map('intval', $non_cached_ids))));

            update_term_cache($fresh_terms);
        }

        if($update_meta_cache)
        {
            wp_lazyload_term_meta($term_ids);
        }
    }

//
// Default callbacks.
//

    function _update_post_term_count($terms, $taxonomy)
    {
        global $wpdb;

        $object_types = (array) $taxonomy->object_type;

        foreach($object_types as &$object_type)
        {
            [$object_type] = explode(':', $object_type);
        }

        $object_types = array_unique($object_types);

        $check_attachments = array_search('attachment', $object_types, true);
        if(false !== $check_attachments)
        {
            unset($object_types[$check_attachments]);
            $check_attachments = true;
        }

        if($object_types)
        {
            $object_types = esc_sql(array_filter($object_types, 'post_type_exists'));
        }

        $post_statuses = ['publish'];

        $post_statuses = esc_sql(apply_filters('update_post_term_count_statuses', $post_statuses, $taxonomy));

        foreach((array) $terms as $term)
        {
            $count = 0;

            // Attachments can be 'inherit' status, we need to base count off the parent's status if so.
            if($check_attachments)
            {
                // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.QuotedDynamicPlaceholderGeneration
                $count += (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts p1 WHERE p1.ID = $wpdb->term_relationships.object_id AND ( post_status IN ('".implode("', '", $post_statuses)."') OR ( post_status = 'inherit' AND post_parent > 0 AND ( SELECT post_status FROM $wpdb->posts WHERE ID = p1.post_parent ) IN ('".implode("', '", $post_statuses)."') ) ) AND post_type = 'attachment' AND term_taxonomy_id = %d", $term));
            }

            if($object_types)
            {
                // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.QuotedDynamicPlaceholderGeneration
                $count += (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts WHERE $wpdb->posts.ID = $wpdb->term_relationships.object_id AND post_status IN ('".implode("', '", $post_statuses)."') AND post_type IN ('".implode("', '", $object_types)."') AND term_taxonomy_id = %d", $term));
            }

            do_action('edit_term_taxonomy', $term, $taxonomy->name);
            $wpdb->update($wpdb->term_taxonomy, compact('count'), ['term_taxonomy_id' => $term]);

            do_action('edited_term_taxonomy', $term, $taxonomy->name);
        }
    }

    function _update_generic_term_count($terms, $taxonomy)
    {
        global $wpdb;

        foreach((array) $terms as $term)
        {
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d", $term));

            do_action('edit_term_taxonomy', $term, $taxonomy->name);
            $wpdb->update($wpdb->term_taxonomy, compact('count'), ['term_taxonomy_id' => $term]);

            do_action('edited_term_taxonomy', $term, $taxonomy->name);
        }
    }

    function _split_shared_term($term_id, $term_taxonomy_id, $record = true)
    {
        global $wpdb;

        if(is_object($term_id))
        {
            $shared_term = $term_id;
            $term_id = (int) $shared_term->term_id;
        }

        if(is_object($term_taxonomy_id))
        {
            $term_taxonomy = $term_taxonomy_id;
            $term_taxonomy_id = (int) $term_taxonomy->term_taxonomy_id;
        }

        // If there are no shared term_taxonomy rows, there's nothing to do here.
        $shared_tt_count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->term_taxonomy tt WHERE tt.term_id = %d AND tt.term_taxonomy_id != %d", $term_id, $term_taxonomy_id));

        if(! $shared_tt_count)
        {
            return $term_id;
        }

        /*
	 * Verify that the term_taxonomy_id passed to the function is actually associated with the term_id.
	 * If there's a mismatch, it may mean that the term is already split. Return the actual term_id from the db.
	 */
        $check_term_id = (int) $wpdb->get_var($wpdb->prepare("SELECT term_id FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = %d", $term_taxonomy_id));
        if($check_term_id !== $term_id)
        {
            return $check_term_id;
        }

        // Pull up data about the currently shared slug, which we'll use to populate the new one.
        if(empty($shared_term))
        {
            $shared_term = $wpdb->get_row($wpdb->prepare("SELECT t.* FROM $wpdb->terms t WHERE t.term_id = %d", $term_id));
        }

        $new_term_data = [
            'name' => $shared_term->name,
            'slug' => $shared_term->slug,
            'term_group' => $shared_term->term_group,
        ];

        if(false === $wpdb->insert($wpdb->terms, $new_term_data))
        {
            return new WP_Error('db_insert_error', __('Could not split shared term.'), $wpdb->last_error);
        }

        $new_term_id = (int) $wpdb->insert_id;

        // Update the existing term_taxonomy to point to the newly created term.
        $wpdb->update($wpdb->term_taxonomy, ['term_id' => $new_term_id], ['term_taxonomy_id' => $term_taxonomy_id]);

        // Reassign child terms to the new parent.
        if(empty($term_taxonomy))
        {
            $term_taxonomy = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = %d", $term_taxonomy_id));
        }

        $children_tt_ids = $wpdb->get_col($wpdb->prepare("SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE parent = %d AND taxonomy = %s", $term_id, $term_taxonomy->taxonomy));
        if(! empty($children_tt_ids))
        {
            foreach($children_tt_ids as $child_tt_id)
            {
                $wpdb->update($wpdb->term_taxonomy, ['parent' => $new_term_id], ['term_taxonomy_id' => $child_tt_id]);
                clean_term_cache((int) $child_tt_id, '', false);
            }
        }
        else
        {
            // If the term has no children, we must force its taxonomy cache to be rebuilt separately.
            clean_term_cache($new_term_id, $term_taxonomy->taxonomy, false);
        }

        clean_term_cache($term_id, $term_taxonomy->taxonomy, false);

        /*
	 * Taxonomy cache clearing is delayed to avoid race conditions that may occur when
	 * regenerating the taxonomy's hierarchy tree.
	 */
        $taxonomies_to_clean = [$term_taxonomy->taxonomy];

        // Clean the cache for term taxonomies formerly shared with the current term.
        $shared_term_taxonomies = $wpdb->get_col($wpdb->prepare("SELECT taxonomy FROM $wpdb->term_taxonomy WHERE term_id = %d", $term_id));
        $taxonomies_to_clean = array_merge($taxonomies_to_clean, $shared_term_taxonomies);

        foreach($taxonomies_to_clean as $taxonomy_to_clean)
        {
            clean_taxonomy_cache($taxonomy_to_clean);
        }

        // Keep a record of term_ids that have been split, keyed by old term_id. See wp_get_split_term().
        if($record)
        {
            $split_term_data = get_option('_split_terms', []);
            if(! isset($split_term_data[$term_id]))
            {
                $split_term_data[$term_id] = [];
            }

            $split_term_data[$term_id][$term_taxonomy->taxonomy] = $new_term_id;
            update_option('_split_terms', $split_term_data);
        }

        // If we've just split the final shared term, set the "finished" flag.
        $shared_terms_exist = $wpdb->get_results(
            "SELECT tt.term_id, t.*, count(*) as term_tt_count FROM {$wpdb->term_taxonomy} tt
		 LEFT JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
		 GROUP BY t.term_id
		 HAVING term_tt_count > 1
		 LIMIT 1"
        );
        if(! $shared_terms_exist)
        {
            update_option('finished_splitting_shared_terms', true);
        }

        do_action('split_shared_term', $term_id, $new_term_id, $term_taxonomy_id, $term_taxonomy->taxonomy);

        return $new_term_id;
    }

    function _wp_batch_split_terms()
    {
        global $wpdb;

        $lock_name = 'term_split.lock';

        // Try to lock.
        $lock_result = $wpdb->query($wpdb->prepare("INSERT IGNORE INTO `$wpdb->options` ( `option_name`, `option_value`, `autoload` ) VALUES (%s, %s, 'no') /* LOCK */", $lock_name, time()));

        if(! $lock_result)
        {
            $lock_result = get_option($lock_name);

            // Bail if we were unable to create a lock, or if the existing lock is still valid.
            if(! $lock_result || ($lock_result > (time() - HOUR_IN_SECONDS)))
            {
                wp_schedule_single_event(time() + (5 * MINUTE_IN_SECONDS), 'wp_split_shared_term_batch');

                return;
            }
        }

        // Update the lock, as by this point we've definitely got a lock, just need to fire the actions.
        update_option($lock_name, time());

        // Get a list of shared terms (those with more than one associated row in term_taxonomy).
        $shared_terms = $wpdb->get_results(
            "SELECT tt.term_id, t.*, count(*) as term_tt_count FROM {$wpdb->term_taxonomy} tt
		 LEFT JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
		 GROUP BY t.term_id
		 HAVING term_tt_count > 1
		 LIMIT 10"
        );

        // No more terms, we're done here.
        if(! $shared_terms)
        {
            update_option('finished_splitting_shared_terms', true);
            delete_option($lock_name);

            return;
        }

        // Shared terms found? We'll need to run this script again.
        wp_schedule_single_event(time() + (2 * MINUTE_IN_SECONDS), 'wp_split_shared_term_batch');

        // Rekey shared term array for faster lookups.
        $_shared_terms = [];
        foreach($shared_terms as $shared_term)
        {
            $term_id = (int) $shared_term->term_id;
            $_shared_terms[$term_id] = $shared_term;
        }
        $shared_terms = $_shared_terms;

        // Get term taxonomy data for all shared terms.
        $shared_term_ids = implode(',', array_keys($shared_terms));
        $shared_tts = $wpdb->get_results("SELECT * FROM {$wpdb->term_taxonomy} WHERE `term_id` IN ({$shared_term_ids})");

        // Split term data recording is slow, so we do it just once, outside the loop.
        $split_term_data = get_option('_split_terms', []);
        $skipped_first_term = [];
        $taxonomies = [];
        foreach($shared_tts as $shared_tt)
        {
            $term_id = (int) $shared_tt->term_id;

            // Don't split the first tt belonging to a given term_id.
            if(! isset($skipped_first_term[$term_id]))
            {
                $skipped_first_term[$term_id] = 1;
                continue;
            }

            if(! isset($split_term_data[$term_id]))
            {
                $split_term_data[$term_id] = [];
            }

            // Keep track of taxonomies whose hierarchies need flushing.
            if(! isset($taxonomies[$shared_tt->taxonomy]))
            {
                $taxonomies[$shared_tt->taxonomy] = 1;
            }

            // Split the term.
            $split_term_data[$term_id][$shared_tt->taxonomy] = _split_shared_term($shared_terms[$term_id], $shared_tt, false);
        }

        // Rebuild the cached hierarchy for each affected taxonomy.
        foreach(array_keys($taxonomies) as $tax)
        {
            delete_option("{$tax}_children");
            _get_term_hierarchy($tax);
        }

        update_option('_split_terms', $split_term_data);

        delete_option($lock_name);
    }

    function _wp_check_for_scheduled_split_terms()
    {
        if(! get_option('finished_splitting_shared_terms') && ! wp_next_scheduled('wp_split_shared_term_batch'))
        {
            wp_schedule_single_event(time() + MINUTE_IN_SECONDS, 'wp_split_shared_term_batch');
        }
    }

    function _wp_check_split_default_terms($term_id, $new_term_id, $term_taxonomy_id, $taxonomy)
    {
        if('category' !== $taxonomy)
        {
            return;
        }

        foreach(['default_category', 'default_link_category', 'default_email_category'] as $option)
        {
            if((int) get_option($option, -1) === $term_id)
            {
                update_option($option, $new_term_id);
            }
        }
    }

    function _wp_check_split_terms_in_menus($term_id, $new_term_id, $term_taxonomy_id, $taxonomy)
    {
        global $wpdb;
        $post_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT m1.post_id
		FROM {$wpdb->postmeta} AS m1
			INNER JOIN {$wpdb->postmeta} AS m2 ON ( m2.post_id = m1.post_id )
			INNER JOIN {$wpdb->postmeta} AS m3 ON ( m3.post_id = m1.post_id )
		WHERE ( m1.meta_key = '_menu_item_type' AND m1.meta_value = 'taxonomy' )
			AND ( m2.meta_key = '_menu_item_object' AND m2.meta_value = %s )
			AND ( m3.meta_key = '_menu_item_object_id' AND m3.meta_value = %d )", $taxonomy, $term_id
            )
        );

        if($post_ids)
        {
            foreach($post_ids as $post_id)
            {
                update_post_meta($post_id, '_menu_item_object_id', $new_term_id, $term_id);
            }
        }
    }

    function _wp_check_split_nav_menu_terms($term_id, $new_term_id, $term_taxonomy_id, $taxonomy)
    {
        if('nav_menu' !== $taxonomy)
        {
            return;
        }

        // Update menu locations.
        $locations = get_nav_menu_locations();
        foreach($locations as $location => $menu_id)
        {
            if($term_id === $menu_id)
            {
                $locations[$location] = $new_term_id;
            }
        }
        set_theme_mod('nav_menu_locations', $locations);
    }

    function wp_get_split_terms($old_term_id)
    {
        $split_terms = get_option('_split_terms', []);

        $terms = [];
        if(isset($split_terms[$old_term_id]))
        {
            $terms = $split_terms[$old_term_id];
        }

        return $terms;
    }

    function wp_get_split_term($old_term_id, $taxonomy)
    {
        $split_terms = wp_get_split_terms($old_term_id);

        $term_id = false;
        if(isset($split_terms[$taxonomy]))
        {
            $term_id = (int) $split_terms[$taxonomy];
        }

        return $term_id;
    }

    function wp_term_is_shared($term_id)
    {
        global $wpdb;

        if(get_option('finished_splitting_shared_terms'))
        {
            return false;
        }

        $tt_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->term_taxonomy WHERE term_id = %d", $term_id));

        return $tt_count > 1;
    }

    function get_term_link($term, $taxonomy = '')
    {
        global $wp_rewrite;

        if(! is_object($term))
        {
            if(is_int($term))
            {
                $term = get_term($term, $taxonomy);
            }
            else
            {
                $term = get_term_by('slug', $term, $taxonomy);
            }
        }

        if(! is_object($term))
        {
            $term = new WP_Error('invalid_term', __('Empty Term.'));
        }

        if(is_wp_error($term))
        {
            return $term;
        }

        $taxonomy = $term->taxonomy;

        $termlink = $wp_rewrite->get_extra_permastruct($taxonomy);

        $termlink = apply_filters('pre_term_link', $termlink, $term);

        $slug = $term->slug;
        $t = get_taxonomy($taxonomy);

        if(empty($termlink))
        {
            if('category' === $taxonomy)
            {
                $termlink = '?cat='.$term->term_id;
            }
            elseif($t->query_var)
            {
                $termlink = "?$t->query_var=$slug";
            }
            else
            {
                $termlink = "?taxonomy=$taxonomy&term=$slug";
            }
            $termlink = home_url($termlink);
        }
        else
        {
            if(! empty($t->rewrite['hierarchical']))
            {
                $hierarchical_slugs = [];
                $ancestors = get_ancestors($term->term_id, $taxonomy, 'taxonomy');
                foreach((array) $ancestors as $ancestor)
                {
                    $ancestor_term = get_term($ancestor, $taxonomy);
                    $hierarchical_slugs[] = $ancestor_term->slug;
                }
                $hierarchical_slugs = array_reverse($hierarchical_slugs);
                $hierarchical_slugs[] = $slug;
                $termlink = str_replace("%$taxonomy%", implode('/', $hierarchical_slugs), $termlink);
            }
            else
            {
                $termlink = str_replace("%$taxonomy%", $slug, $termlink);
            }
            $termlink = home_url(user_trailingslashit($termlink, 'category'));
        }

        // Back compat filters.
        if('post_tag' === $taxonomy)
        {
            $termlink = apply_filters('tag_link', $termlink, $term->term_id);
        }
        elseif('category' === $taxonomy)
        {
            $termlink = apply_filters('category_link', $termlink, $term->term_id);
        }

        return apply_filters('term_link', $termlink, $term, $taxonomy);
    }

    function the_taxonomies($args = [])
    {
        $defaults = [
            'post' => 0,
            'before' => '',
            'sep' => ' ',
            'after' => '',
        ];

        $parsed_args = wp_parse_args($args, $defaults);

        echo $parsed_args['before'].implode($parsed_args['sep'], get_the_taxonomies($parsed_args['post'], $parsed_args)).$parsed_args['after'];
    }

    function get_the_taxonomies($post = 0, $args = [])
    {
        $post = get_post($post);

        $args = wp_parse_args($args, [
            /* translators: %s: Taxonomy label, %l: List of terms formatted as per $term_template. */
            'template' => __('%s: %l.'),
            'term_template' => '<a href="%1$s">%2$s</a>',
        ]);

        $taxonomies = [];

        if(! $post)
        {
            return $taxonomies;
        }

        foreach(get_object_taxonomies($post) as $taxonomy)
        {
            $t = (array) get_taxonomy($taxonomy);
            if(empty($t['label']))
            {
                $t['label'] = $taxonomy;
            }
            if(empty($t['args']))
            {
                $t['args'] = [];
            }
            if(empty($t['template']))
            {
                $t['template'] = $args['template'];
            }
            if(empty($t['term_template']))
            {
                $t['term_template'] = $args['term_template'];
            }

            $terms = get_object_term_cache($post->ID, $taxonomy);
            if(false === $terms)
            {
                $terms = wp_get_object_terms($post->ID, $taxonomy, $t['args']);
            }
            $links = [];

            foreach($terms as $term)
            {
                $links[] = wp_sprintf($t['term_template'], esc_attr(get_term_link($term)), $term->name);
            }
            if($links)
            {
                $taxonomies[$taxonomy] = wp_sprintf($t['template'], $t['label'], $links, $terms);
            }
        }

        return $taxonomies;
    }

    function get_post_taxonomies($post = 0)
    {
        $post = get_post($post);

        return get_object_taxonomies($post);
    }

    function is_object_in_term($object_id, $taxonomy, $terms = null)
    {
        $object_id = (int) $object_id;
        if(! $object_id)
        {
            return new WP_Error('invalid_object', __('Invalid object ID.'));
        }

        $object_terms = get_object_term_cache($object_id, $taxonomy);
        if(false === $object_terms)
        {
            $object_terms = wp_get_object_terms($object_id, $taxonomy, ['update_term_meta_cache' => false]);
            if(is_wp_error($object_terms))
            {
                return $object_terms;
            }

            wp_cache_set($object_id, wp_list_pluck($object_terms, 'term_id'), "{$taxonomy}_relationships");
        }

        if(is_wp_error($object_terms))
        {
            return $object_terms;
        }
        if(empty($object_terms))
        {
            return false;
        }
        if(empty($terms))
        {
            return (! empty($object_terms));
        }

        $terms = (array) $terms;

        $ints = array_filter($terms, 'is_int');
        if($ints)
        {
            $strs = array_diff($terms, $ints);
        }
        else
        {
            $strs =& $terms;
        }

        foreach($object_terms as $object_term)
        {
            // If term is an int, check against term_ids only.
            if($ints && in_array($object_term->term_id, $ints, true))
            {
                return true;
            }

            if($strs)
            {
                // Only check numeric strings against term_id, to avoid false matches due to type juggling.
                $numeric_strs = array_map('intval', array_filter($strs, 'is_numeric'));
                if(in_array($object_term->term_id, $numeric_strs, true) || in_array($object_term->name, $strs, true) || in_array($object_term->slug, $strs, true))
                {
                    return true;
                }
            }
        }

        return false;
    }

    function is_object_in_taxonomy($object_type, $taxonomy)
    {
        $taxonomies = get_object_taxonomies($object_type);
        if(empty($taxonomies))
        {
            return false;
        }

        return in_array($taxonomy, $taxonomies, true);
    }

    function get_ancestors($object_id = 0, $object_type = '', $resource_type = '')
    {
        $object_id = (int) $object_id;

        $ancestors = [];

        if(empty($object_id))
        {
            return apply_filters('get_ancestors', $ancestors, $object_id, $object_type, $resource_type);
        }

        if(! $resource_type)
        {
            if(is_taxonomy_hierarchical($object_type))
            {
                $resource_type = 'taxonomy';
            }
            elseif(post_type_exists($object_type))
            {
                $resource_type = 'post_type';
            }
        }

        if('taxonomy' === $resource_type)
        {
            $term = get_term($object_id, $object_type);
            while(! is_wp_error($term) && ! empty($term->parent) && ! in_array($term->parent, $ancestors, true))
            {
                $ancestors[] = (int) $term->parent;
                $term = get_term($term->parent, $object_type);
            }
        }
        elseif('post_type' === $resource_type)
        {
            $ancestors = get_post_ancestors($object_id);
        }

        return apply_filters('get_ancestors', $ancestors, $object_id, $object_type, $resource_type);
    }

    function wp_get_term_taxonomy_parent_id($term_id, $taxonomy)
    {
        $term = get_term($term_id, $taxonomy);
        if(! $term || is_wp_error($term))
        {
            return false;
        }

        return (int) $term->parent;
    }

    function wp_check_term_hierarchy_for_loops($parent_term, $term_id, $taxonomy)
    {
        // Nothing fancy here - bail.
        // Can't be its own parent.
        if(! $parent_term || $parent_term === $term_id)
        {
            return 0;
        }

        // Now look for larger loops.
        $loop = wp_find_hierarchy_loop('wp_get_term_taxonomy_parent_id', $term_id, $parent_term, [$taxonomy]);
        if(! $loop)
        {
            return $parent_term; // No loop.
        }

        // Setting $parent_term to the given value causes a loop.
        if(isset($loop[$term_id]))
        {
            return 0;
        }

        // There's a loop, but it doesn't contain $term_id. Break the loop.
        foreach(array_keys($loop) as $loop_member)
        {
            wp_update_term($loop_member, $taxonomy, ['parent' => 0]);
        }

        return $parent_term;
    }

    function is_taxonomy_viewable($taxonomy)
    {
        if(is_scalar($taxonomy))
        {
            $taxonomy = get_taxonomy($taxonomy);
            if(! $taxonomy)
            {
                return false;
            }
        }

        return $taxonomy->publicly_queryable;
    }

    function is_term_publicly_viewable($term)
    {
        $term = get_term($term);

        if(! $term)
        {
            return false;
        }

        return is_taxonomy_viewable($term->taxonomy);
    }

    function wp_cache_set_terms_last_changed()
    {
        wp_cache_set_last_changed('terms');
    }

    function wp_check_term_meta_support_prefilter($check)
    {
        if(get_option('db_version') < 34370)
        {
            return false;
        }

        return $check;
    }
