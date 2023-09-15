<?php

    require ABSPATH.WPINC.'/class-wp-metadata-lazyloader.php';

    function add_metadata($meta_type, $object_id, $meta_key, $meta_value, $unique = false)
    {
        global $wpdb;

        if(! $meta_type || ! $meta_key || ! is_numeric($object_id))
        {
            return false;
        }

        $object_id = absint($object_id);
        if(! $object_id)
        {
            return false;
        }

        $table = _get_meta_table($meta_type);
        if(! $table)
        {
            return false;
        }

        $meta_subtype = get_object_subtype($meta_type, $object_id);

        $column = sanitize_key($meta_type.'_id');

        // expected_slashed ($meta_key)
        $meta_key = wp_unslash($meta_key);
        $meta_value = wp_unslash($meta_value);
        $meta_value = sanitize_meta($meta_key, $meta_value, $meta_type, $meta_subtype);

        $check = apply_filters("add_{$meta_type}_metadata", null, $object_id, $meta_key, $meta_value, $unique);
        if(null !== $check)
        {
            return $check;
        }

        if($unique && $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE meta_key = %s AND $column = %d", $meta_key, $object_id)))
        {
            return false;
        }

        $_meta_value = $meta_value;
        $meta_value = maybe_serialize($meta_value);

        do_action("add_{$meta_type}_meta", $object_id, $meta_key, $_meta_value);

        $result = $wpdb->insert($table, [
            $column => $object_id,
            'meta_key' => $meta_key,
            'meta_value' => $meta_value,
        ]);

        if(! $result)
        {
            return false;
        }

        $mid = (int) $wpdb->insert_id;

        wp_cache_delete($object_id, $meta_type.'_meta');

        do_action("added_{$meta_type}_meta", $mid, $object_id, $meta_key, $_meta_value);

        return $mid;
    }

    function update_metadata($meta_type, $object_id, $meta_key, $meta_value, $prev_value = '')
    {
        global $wpdb;

        if(! $meta_type || ! $meta_key || ! is_numeric($object_id))
        {
            return false;
        }

        $object_id = absint($object_id);
        if(! $object_id)
        {
            return false;
        }

        $table = _get_meta_table($meta_type);
        if(! $table)
        {
            return false;
        }

        $meta_subtype = get_object_subtype($meta_type, $object_id);

        $column = sanitize_key($meta_type.'_id');
        $id_column = ('user' === $meta_type) ? 'umeta_id' : 'meta_id';

        // expected_slashed ($meta_key)
        $raw_meta_key = $meta_key;
        $meta_key = wp_unslash($meta_key);
        $passed_value = $meta_value;
        $meta_value = wp_unslash($meta_value);
        $meta_value = sanitize_meta($meta_key, $meta_value, $meta_type, $meta_subtype);

        $check = apply_filters("update_{$meta_type}_metadata", null, $object_id, $meta_key, $meta_value, $prev_value);
        if(null !== $check)
        {
            return (bool) $check;
        }

        // Compare existing value to new value if no prev value given and the key exists only once.
        if(empty($prev_value))
        {
            $old_value = get_metadata_raw($meta_type, $object_id, $meta_key);
            if(is_countable($old_value) && count($old_value) === 1)
            {
                if($old_value[0] === $meta_value)
                {
                    return false;
                }
            }
        }

        $meta_ids = $wpdb->get_col($wpdb->prepare("SELECT $id_column FROM $table WHERE meta_key = %s AND $column = %d", $meta_key, $object_id));
        if(empty($meta_ids))
        {
            return add_metadata($meta_type, $object_id, $raw_meta_key, $passed_value);
        }

        $_meta_value = $meta_value;
        $meta_value = maybe_serialize($meta_value);

        $data = compact('meta_value');
        $where = [
            $column => $object_id,
            'meta_key' => $meta_key,
        ];

        if(! empty($prev_value))
        {
            $prev_value = maybe_serialize($prev_value);
            $where['meta_value'] = $prev_value;
        }

        foreach($meta_ids as $meta_id)
        {
            do_action("update_{$meta_type}_meta", $meta_id, $object_id, $meta_key, $_meta_value);

            if('post' === $meta_type)
            {
                do_action('update_postmeta', $meta_id, $object_id, $meta_key, $meta_value);
            }
        }

        $result = $wpdb->update($table, $data, $where);
        if(! $result)
        {
            return false;
        }

        wp_cache_delete($object_id, $meta_type.'_meta');

        foreach($meta_ids as $meta_id)
        {
            do_action("updated_{$meta_type}_meta", $meta_id, $object_id, $meta_key, $_meta_value);

            if('post' === $meta_type)
            {
                do_action('updated_postmeta', $meta_id, $object_id, $meta_key, $meta_value);
            }
        }

        return true;
    }

    function delete_metadata($meta_type, $object_id, $meta_key, $meta_value = '', $delete_all = false)
    {
        global $wpdb;

        if(! $meta_type || ! $meta_key || ! is_numeric($object_id) && ! $delete_all)
        {
            return false;
        }

        $object_id = absint($object_id);
        if(! $object_id && ! $delete_all)
        {
            return false;
        }

        $table = _get_meta_table($meta_type);
        if(! $table)
        {
            return false;
        }

        $type_column = sanitize_key($meta_type.'_id');
        $id_column = ('user' === $meta_type) ? 'umeta_id' : 'meta_id';

        // expected_slashed ($meta_key)
        $meta_key = wp_unslash($meta_key);
        $meta_value = wp_unslash($meta_value);

        $check = apply_filters("delete_{$meta_type}_metadata", null, $object_id, $meta_key, $meta_value, $delete_all);
        if(null !== $check)
        {
            return (bool) $check;
        }

        $_meta_value = $meta_value;
        $meta_value = maybe_serialize($meta_value);

        $query = $wpdb->prepare("SELECT $id_column FROM $table WHERE meta_key = %s", $meta_key);

        if(! $delete_all)
        {
            $query .= $wpdb->prepare(" AND $type_column = %d", $object_id);
        }

        if('' !== $meta_value && null !== $meta_value && false !== $meta_value)
        {
            $query .= $wpdb->prepare(' AND meta_value = %s', $meta_value);
        }

        $meta_ids = $wpdb->get_col($query);
        if(! count($meta_ids))
        {
            return false;
        }

        if($delete_all)
        {
            if('' !== $meta_value && null !== $meta_value && false !== $meta_value)
            {
                $object_ids = $wpdb->get_col($wpdb->prepare("SELECT $type_column FROM $table WHERE meta_key = %s AND meta_value = %s", $meta_key, $meta_value));
            }
            else
            {
                $object_ids = $wpdb->get_col($wpdb->prepare("SELECT $type_column FROM $table WHERE meta_key = %s", $meta_key));
            }
        }

        do_action("delete_{$meta_type}_meta", $meta_ids, $object_id, $meta_key, $_meta_value);

        // Old-style action.
        if('post' === $meta_type)
        {
            do_action('delete_postmeta', $meta_ids);
        }

        $query = "DELETE FROM $table WHERE $id_column IN( ".implode(',', $meta_ids).' )';

        $count = $wpdb->query($query);

        if(! $count)
        {
            return false;
        }

        if($delete_all)
        {
            $data = (array) $object_ids;
        }
        else
        {
            $data = [$object_id];
        }
        wp_cache_delete_multiple($data, $meta_type.'_meta');

        do_action("deleted_{$meta_type}_meta", $meta_ids, $object_id, $meta_key, $_meta_value);

        // Old-style action.
        if('post' === $meta_type)
        {
            do_action('deleted_postmeta', $meta_ids);
        }

        return true;
    }

    function get_metadata($meta_type, $object_id, $meta_key = '', $single = false)
    {
        $value = get_metadata_raw($meta_type, $object_id, $meta_key, $single);
        if(! is_null($value))
        {
            return $value;
        }

        return get_metadata_default($meta_type, $object_id, $meta_key, $single);
    }

    function get_metadata_raw($meta_type, $object_id, $meta_key = '', $single = false)
    {
        if(! $meta_type || ! is_numeric($object_id))
        {
            return false;
        }

        $object_id = absint($object_id);
        if(! $object_id)
        {
            return false;
        }

        $check = apply_filters("get_{$meta_type}_metadata", null, $object_id, $meta_key, $single, $meta_type);
        if(null !== $check)
        {
            if($single && is_array($check))
            {
                return $check[0];
            }
            else
            {
                return $check;
            }
        }

        $meta_cache = wp_cache_get($object_id, $meta_type.'_meta');

        if(! $meta_cache)
        {
            $meta_cache = update_meta_cache($meta_type, [$object_id]);
            if(isset($meta_cache[$object_id]))
            {
                $meta_cache = $meta_cache[$object_id];
            }
            else
            {
                $meta_cache = null;
            }
        }

        if(! $meta_key)
        {
            return $meta_cache;
        }

        if(isset($meta_cache[$meta_key]))
        {
            if($single)
            {
                return maybe_unserialize($meta_cache[$meta_key][0]);
            }
            else
            {
                return array_map('maybe_unserialize', $meta_cache[$meta_key]);
            }
        }

        return null;
    }

    function get_metadata_default($meta_type, $object_id, $meta_key, $single = false)
    {
        if($single)
        {
            $value = '';
        }
        else
        {
            $value = [];
        }

        $value = apply_filters("default_{$meta_type}_metadata", $value, $object_id, $meta_key, $single, $meta_type);

        if(! $single && ! wp_is_numeric_array($value))
        {
            $value = [$value];
        }

        return $value;
    }

    function metadata_exists($meta_type, $object_id, $meta_key)
    {
        if(! $meta_type || ! is_numeric($object_id))
        {
            return false;
        }

        $object_id = absint($object_id);
        if(! $object_id)
        {
            return false;
        }

        $check = apply_filters("get_{$meta_type}_metadata", null, $object_id, $meta_key, true, $meta_type);
        if(null !== $check)
        {
            return (bool) $check;
        }

        $meta_cache = wp_cache_get($object_id, $meta_type.'_meta');

        if(! $meta_cache)
        {
            $meta_cache = update_meta_cache($meta_type, [$object_id]);
            $meta_cache = $meta_cache[$object_id];
        }

        if(isset($meta_cache[$meta_key]))
        {
            return true;
        }

        return false;
    }

    function get_metadata_by_mid($meta_type, $meta_id)
    {
        global $wpdb;

        if(! $meta_type || ! is_numeric($meta_id) || floor($meta_id) != $meta_id)
        {
            return false;
        }

        $meta_id = (int) $meta_id;
        if($meta_id <= 0)
        {
            return false;
        }

        $table = _get_meta_table($meta_type);
        if(! $table)
        {
            return false;
        }

        $check = apply_filters("get_{$meta_type}_metadata_by_mid", null, $meta_id);
        if(null !== $check)
        {
            return $check;
        }

        $id_column = ('user' === $meta_type) ? 'umeta_id' : 'meta_id';

        $meta = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE $id_column = %d", $meta_id));

        if(empty($meta))
        {
            return false;
        }

        if(isset($meta->meta_value))
        {
            $meta->meta_value = maybe_unserialize($meta->meta_value);
        }

        return $meta;
    }

    function update_metadata_by_mid($meta_type, $meta_id, $meta_value, $meta_key = false)
    {
        global $wpdb;

        // Make sure everything is valid.
        if(! $meta_type || ! is_numeric($meta_id) || floor($meta_id) != $meta_id)
        {
            return false;
        }

        $meta_id = (int) $meta_id;
        if($meta_id <= 0)
        {
            return false;
        }

        $table = _get_meta_table($meta_type);
        if(! $table)
        {
            return false;
        }

        $column = sanitize_key($meta_type.'_id');
        $id_column = ('user' === $meta_type) ? 'umeta_id' : 'meta_id';

        $check = apply_filters("update_{$meta_type}_metadata_by_mid", null, $meta_id, $meta_value, $meta_key);
        if(null !== $check)
        {
            return (bool) $check;
        }

        // Fetch the meta and go on if it's found.
        $meta = get_metadata_by_mid($meta_type, $meta_id);
        if($meta)
        {
            $original_key = $meta->meta_key;
            $object_id = $meta->{$column};

            /*
             * If a new meta_key (last parameter) was specified, change the meta key,
             * otherwise use the original key in the update statement.
             */
            if(false === $meta_key)
            {
                $meta_key = $original_key;
            }
            elseif(! is_string($meta_key))
            {
                return false;
            }

            $meta_subtype = get_object_subtype($meta_type, $object_id);

            // Sanitize the meta.
            $_meta_value = $meta_value;
            $meta_value = sanitize_meta($meta_key, $meta_value, $meta_type, $meta_subtype);
            $meta_value = maybe_serialize($meta_value);

            // Format the data query arguments.
            $data = [
                'meta_key' => $meta_key,
                'meta_value' => $meta_value,
            ];

            // Format the where query arguments.
            $where = [];
            $where[$id_column] = $meta_id;

            do_action("update_{$meta_type}_meta", $meta_id, $object_id, $meta_key, $_meta_value);

            if('post' === $meta_type)
            {
                do_action('update_postmeta', $meta_id, $object_id, $meta_key, $meta_value);
            }

            // Run the update query, all fields in $data are %s, $where is a %d.
            $result = $wpdb->update($table, $data, $where, '%s', '%d');
            if(! $result)
            {
                return false;
            }

            // Clear the caches.
            wp_cache_delete($object_id, $meta_type.'_meta');

            do_action("updated_{$meta_type}_meta", $meta_id, $object_id, $meta_key, $_meta_value);

            if('post' === $meta_type)
            {
                do_action('updated_postmeta', $meta_id, $object_id, $meta_key, $meta_value);
            }

            return true;
        }

        // And if the meta was not found.
        return false;
    }

    function delete_metadata_by_mid($meta_type, $meta_id)
    {
        global $wpdb;

        // Make sure everything is valid.
        if(! $meta_type || ! is_numeric($meta_id) || floor($meta_id) != $meta_id)
        {
            return false;
        }

        $meta_id = (int) $meta_id;
        if($meta_id <= 0)
        {
            return false;
        }

        $table = _get_meta_table($meta_type);
        if(! $table)
        {
            return false;
        }

        // Object and ID columns.
        $column = sanitize_key($meta_type.'_id');
        $id_column = ('user' === $meta_type) ? 'umeta_id' : 'meta_id';

        $check = apply_filters("delete_{$meta_type}_metadata_by_mid", null, $meta_id);
        if(null !== $check)
        {
            return (bool) $check;
        }

        // Fetch the meta and go on if it's found.
        $meta = get_metadata_by_mid($meta_type, $meta_id);
        if($meta)
        {
            $object_id = (int) $meta->{$column};

            do_action("delete_{$meta_type}_meta", (array) $meta_id, $object_id, $meta->meta_key, $meta->meta_value);

            // Old-style action.
            if('post' === $meta_type || 'comment' === $meta_type)
            {
                do_action("delete_{$meta_type}meta", $meta_id);
            }

            // Run the query, will return true if deleted, false otherwise.
            $result = (bool) $wpdb->delete($table, [$id_column => $meta_id]);

            // Clear the caches.
            wp_cache_delete($object_id, $meta_type.'_meta');

            do_action("deleted_{$meta_type}_meta", (array) $meta_id, $object_id, $meta->meta_key, $meta->meta_value);

            // Old-style action.
            if('post' === $meta_type || 'comment' === $meta_type)
            {
                do_action("deleted_{$meta_type}meta", $meta_id);
            }

            return $result;
        }

        // Meta ID was not found.
        return false;
    }

    function update_meta_cache($meta_type, $object_ids)
    {
        global $wpdb;

        if(! $meta_type || ! $object_ids)
        {
            return false;
        }

        $table = _get_meta_table($meta_type);
        if(! $table)
        {
            return false;
        }

        $column = sanitize_key($meta_type.'_id');

        if(! is_array($object_ids))
        {
            $object_ids = preg_replace('|[^0-9,]|', '', $object_ids);
            $object_ids = explode(',', $object_ids);
        }

        $object_ids = array_map('intval', $object_ids);

        $check = apply_filters("update_{$meta_type}_metadata_cache", null, $object_ids);
        if(null !== $check)
        {
            return (bool) $check;
        }

        $cache_key = $meta_type.'_meta';
        $non_cached_ids = [];
        $cache = [];
        $cache_values = wp_cache_get_multiple($object_ids, $cache_key);

        foreach($cache_values as $id => $cached_object)
        {
            if(false === $cached_object)
            {
                $non_cached_ids[] = $id;
            }
            else
            {
                $cache[$id] = $cached_object;
            }
        }

        if(empty($non_cached_ids))
        {
            return $cache;
        }

        // Get meta info.
        $id_list = implode(',', $non_cached_ids);
        $id_column = ('user' === $meta_type) ? 'umeta_id' : 'meta_id';

        $meta_list = $wpdb->get_results("SELECT $column, meta_key, meta_value FROM $table WHERE $column IN ($id_list) ORDER BY $id_column ASC", ARRAY_A);

        if(! empty($meta_list))
        {
            foreach($meta_list as $metarow)
            {
                $mpid = (int) $metarow[$column];
                $mkey = $metarow['meta_key'];
                $mval = $metarow['meta_value'];

                // Force subkeys to be array type.
                if(! isset($cache[$mpid]) || ! is_array($cache[$mpid]))
                {
                    $cache[$mpid] = [];
                }
                if(! isset($cache[$mpid][$mkey]) || ! is_array($cache[$mpid][$mkey]))
                {
                    $cache[$mpid][$mkey] = [];
                }

                // Add a value to the current pid/key.
                $cache[$mpid][$mkey][] = $mval;
            }
        }

        $data = [];
        foreach($non_cached_ids as $id)
        {
            if(! isset($cache[$id]))
            {
                $cache[$id] = [];
            }
            $data[$id] = $cache[$id];
        }
        wp_cache_add_multiple($data, $cache_key);

        return $cache;
    }

    function wp_metadata_lazyloader()
    {
        static $wp_metadata_lazyloader;

        if(null === $wp_metadata_lazyloader)
        {
            $wp_metadata_lazyloader = new WP_Metadata_Lazyloader();
        }

        return $wp_metadata_lazyloader;
    }

    function get_meta_sql($meta_query, $type, $primary_table, $primary_id_column, $context = null)
    {
        $meta_query_obj = new WP_Meta_Query($meta_query);

        return $meta_query_obj->get_sql($type, $primary_table, $primary_id_column, $context);
    }

    function _get_meta_table($type)
    {
        global $wpdb;

        $table_name = $type.'meta';

        if(empty($wpdb->$table_name))
        {
            return false;
        }

        return $wpdb->$table_name;
    }

    function is_protected_meta($meta_key, $meta_type = '')
    {
        $sanitized_key = preg_replace("/[^\x20-\x7E\p{L}]/", '', $meta_key);
        $protected = strlen($sanitized_key) > 0 && ('_' === $sanitized_key[0]);

        return apply_filters('is_protected_meta', $protected, $meta_key, $meta_type);
    }

    function sanitize_meta($meta_key, $meta_value, $object_type, $object_subtype = '')
    {
        if(! empty($object_subtype) && has_filter("sanitize_{$object_type}_meta_{$meta_key}_for_{$object_subtype}"))
        {
            return apply_filters("sanitize_{$object_type}_meta_{$meta_key}_for_{$object_subtype}", $meta_value, $meta_key, $object_type, $object_subtype);
        }

        return apply_filters("sanitize_{$object_type}_meta_{$meta_key}", $meta_value, $meta_key, $object_type);
    }

    function register_meta($object_type, $meta_key, $args, $deprecated = null)
    {
        global $wp_meta_keys;

        if(! is_array($wp_meta_keys))
        {
            $wp_meta_keys = [];
        }

        $defaults = [
            'object_subtype' => '',
            'type' => 'string',
            'description' => '',
            'default' => '',
            'single' => false,
            'sanitize_callback' => null,
            'auth_callback' => null,
            'show_in_rest' => false,
        ];

        // There used to be individual args for sanitize and auth callbacks.
        $has_old_sanitize_cb = false;
        $has_old_auth_cb = false;

        if(is_callable($args))
        {
            $args = [
                'sanitize_callback' => $args,
            ];

            $has_old_sanitize_cb = true;
        }
        else
        {
            $args = (array) $args;
        }

        if(is_callable($deprecated))
        {
            $args['auth_callback'] = $deprecated;
            $has_old_auth_cb = true;
        }

        $args = apply_filters('register_meta_args', $args, $defaults, $object_type, $meta_key);
        unset($defaults['default']);
        $args = wp_parse_args($args, $defaults);

        // Require an item schema when registering array meta.
        if(false !== $args['show_in_rest'] && 'array' === $args['type'])
        {
            if(! is_array($args['show_in_rest']) || ! isset($args['show_in_rest']['schema']['items']))
            {
                _doing_it_wrong(__FUNCTION__, __('When registering an "array" meta type to show in the REST API, you must specify the schema for each array item in "show_in_rest.schema.items".'), '5.3.0');

                return false;
            }
        }

        $object_subtype = ! empty($args['object_subtype']) ? $args['object_subtype'] : '';

        // If `auth_callback` is not provided, fall back to `is_protected_meta()`.
        if(empty($args['auth_callback']))
        {
            if(is_protected_meta($meta_key, $object_type))
            {
                $args['auth_callback'] = '__return_false';
            }
            else
            {
                $args['auth_callback'] = '__return_true';
            }
        }

        // Back-compat: old sanitize and auth callbacks are applied to all of an object type.
        if(is_callable($args['sanitize_callback']))
        {
            if(! empty($object_subtype))
            {
                add_filter("sanitize_{$object_type}_meta_{$meta_key}_for_{$object_subtype}", $args['sanitize_callback'], 10, 4);
            }
            else
            {
                add_filter("sanitize_{$object_type}_meta_{$meta_key}", $args['sanitize_callback'], 10, 3);
            }
        }

        if(is_callable($args['auth_callback']))
        {
            if(! empty($object_subtype))
            {
                add_filter("auth_{$object_type}_meta_{$meta_key}_for_{$object_subtype}", $args['auth_callback'], 10, 6);
            }
            else
            {
                add_filter("auth_{$object_type}_meta_{$meta_key}", $args['auth_callback'], 10, 6);
            }
        }

        if(array_key_exists('default', $args))
        {
            $schema = $args;
            if(is_array($args['show_in_rest']) && isset($args['show_in_rest']['schema']))
            {
                $schema = array_merge($schema, $args['show_in_rest']['schema']);
            }

            $check = rest_validate_value_from_schema($args['default'], $schema);
            if(is_wp_error($check))
            {
                _doing_it_wrong(__FUNCTION__, __('When registering a default meta value the data must match the type provided.'), '5.5.0');

                return false;
            }

            if(! has_filter("default_{$object_type}_metadata", 'filter_default_metadata'))
            {
                add_filter("default_{$object_type}_metadata", 'filter_default_metadata', 10, 5);
            }
        }

        // Global registry only contains meta keys registered with the array of arguments added in 4.6.0.
        if(! $has_old_auth_cb && ! $has_old_sanitize_cb)
        {
            unset($args['object_subtype']);

            $wp_meta_keys[$object_type][$object_subtype][$meta_key] = $args;

            return true;
        }

        return false;
    }

    function filter_default_metadata($value, $object_id, $meta_key, $single, $meta_type)
    {
        global $wp_meta_keys;

        if(wp_installing())
        {
            return $value;
        }

        if(! is_array($wp_meta_keys) || ! isset($wp_meta_keys[$meta_type]))
        {
            return $value;
        }

        $defaults = [];
        foreach($wp_meta_keys[$meta_type] as $sub_type => $meta_data)
        {
            foreach($meta_data as $_meta_key => $args)
            {
                if($_meta_key === $meta_key && array_key_exists('default', $args))
                {
                    $defaults[$sub_type] = $args;
                }
            }
        }

        if(! $defaults)
        {
            return $value;
        }

        // If this meta type does not have subtypes, then the default is keyed as an empty string.
        if(isset($defaults['']))
        {
            $metadata = $defaults[''];
        }
        else
        {
            $sub_type = get_object_subtype($meta_type, $object_id);
            if(! isset($defaults[$sub_type]))
            {
                return $value;
            }
            $metadata = $defaults[$sub_type];
        }

        if($single)
        {
            $value = $metadata['default'];
        }
        else
        {
            $value = [$metadata['default']];
        }

        return $value;
    }

    function registered_meta_key_exists($object_type, $meta_key, $object_subtype = '')
    {
        $meta_keys = get_registered_meta_keys($object_type, $object_subtype);

        return isset($meta_keys[$meta_key]);
    }

    function unregister_meta_key($object_type, $meta_key, $object_subtype = '')
    {
        global $wp_meta_keys;

        if(! registered_meta_key_exists($object_type, $meta_key, $object_subtype))
        {
            return false;
        }

        $args = $wp_meta_keys[$object_type][$object_subtype][$meta_key];

        if(isset($args['sanitize_callback']) && is_callable($args['sanitize_callback']))
        {
            if(! empty($object_subtype))
            {
                remove_filter("sanitize_{$object_type}_meta_{$meta_key}_for_{$object_subtype}", $args['sanitize_callback']);
            }
            else
            {
                remove_filter("sanitize_{$object_type}_meta_{$meta_key}", $args['sanitize_callback']);
            }
        }

        if(isset($args['auth_callback']) && is_callable($args['auth_callback']))
        {
            if(! empty($object_subtype))
            {
                remove_filter("auth_{$object_type}_meta_{$meta_key}_for_{$object_subtype}", $args['auth_callback']);
            }
            else
            {
                remove_filter("auth_{$object_type}_meta_{$meta_key}", $args['auth_callback']);
            }
        }

        unset($wp_meta_keys[$object_type][$object_subtype][$meta_key]);

        // Do some clean up.
        if(empty($wp_meta_keys[$object_type][$object_subtype]))
        {
            unset($wp_meta_keys[$object_type][$object_subtype]);
        }
        if(empty($wp_meta_keys[$object_type]))
        {
            unset($wp_meta_keys[$object_type]);
        }

        return true;
    }

    function get_registered_meta_keys($object_type, $object_subtype = '')
    {
        global $wp_meta_keys;

        if(! is_array($wp_meta_keys) || ! isset($wp_meta_keys[$object_type]) || ! isset($wp_meta_keys[$object_type][$object_subtype]))
        {
            return [];
        }

        return $wp_meta_keys[$object_type][$object_subtype];
    }

    function get_registered_metadata($object_type, $object_id, $meta_key = '')
    {
        $object_subtype = get_object_subtype($object_type, $object_id);

        if(! empty($meta_key))
        {
            if(! empty($object_subtype) && ! registered_meta_key_exists($object_type, $meta_key, $object_subtype))
            {
                $object_subtype = '';
            }

            if(! registered_meta_key_exists($object_type, $meta_key, $object_subtype))
            {
                return false;
            }

            $meta_keys = get_registered_meta_keys($object_type, $object_subtype);
            $meta_key_data = $meta_keys[$meta_key];

            $data = get_metadata($object_type, $object_id, $meta_key, $meta_key_data['single']);

            return $data;
        }

        $data = get_metadata($object_type, $object_id);
        if(! $data)
        {
            return [];
        }

        $meta_keys = get_registered_meta_keys($object_type);
        if(! empty($object_subtype))
        {
            $meta_keys = array_merge($meta_keys, get_registered_meta_keys($object_type, $object_subtype));
        }

        return array_intersect_key($data, $meta_keys);
    }

    function _wp_register_meta_args_allowed_list($args, $default_args)
    {
        return array_intersect_key($args, $default_args);
    }

    function get_object_subtype($object_type, $object_id)
    {
        $object_id = (int) $object_id;
        $object_subtype = '';

        switch($object_type)
        {
            case 'post':
                $post_type = get_post_type($object_id);

                if(! empty($post_type))
                {
                    $object_subtype = $post_type;
                }
                break;

            case 'term':
                $term = get_term($object_id);
                if(! $term instanceof WP_Term)
                {
                    break;
                }

                $object_subtype = $term->taxonomy;
                break;

            case 'comment':
                $comment = get_comment($object_id);
                if(! $comment)
                {
                    break;
                }

                $object_subtype = 'comment';
                break;

            case 'user':
                $user = get_user_by('id', $object_id);
                if(! $user)
                {
                    break;
                }

                $object_subtype = 'user';
                break;
        }

        return apply_filters("get_object_subtype_{$object_type}", $object_subtype, $object_id);
    }
