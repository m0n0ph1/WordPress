<?php

    function get_option($option, $default_value = false)
    {
        global $wpdb;

        if(is_scalar($option))
        {
            $option = trim($option);
        }

        if(empty($option))
        {
            return false;
        }

        /*
	 * Until a proper _deprecated_option() function can be introduced,
	 * redirect requests to deprecated keys to the new, correct ones.
	 */
        $deprecated_keys = [
            'blacklist_keys' => 'disallowed_keys',
            'comment_whitelist' => 'comment_previously_approved',
        ];

        if(isset($deprecated_keys[$option]) && ! wp_installing())
        {
            _deprecated_argument(__FUNCTION__, '5.5.0', sprintf(/* translators: 1: Deprecated option key, 2: New option key. */ __('The "%1$s" option key has been renamed to "%2$s".'), $option, $deprecated_keys[$option]));

            return get_option($deprecated_keys[$option], $default_value);
        }

        $pre = apply_filters("pre_option_{$option}", false, $option, $default_value);

        $pre = apply_filters('pre_option', $pre, $option, $default_value);

        if(false !== $pre)
        {
            return $pre;
        }

        if(defined('WP_SETUP_CONFIG'))
        {
            return false;
        }

        // Distinguish between `false` as a default, and not passing one.
        $passed_default = func_num_args() > 1;

        if(! wp_installing())
        {
            $alloptions = wp_load_alloptions();

            if(isset($alloptions[$option]))
            {
                $value = $alloptions[$option];
            }
            else
            {
                $value = wp_cache_get($option, 'options');

                if(false === $value)
                {
                    // Prevent non-existent options from triggering multiple queries.
                    $notoptions = wp_cache_get('notoptions', 'options');

                    // Prevent non-existent `notoptions` key from triggering multiple key lookups.
                    if(! is_array($notoptions))
                    {
                        $notoptions = [];
                        wp_cache_set('notoptions', $notoptions, 'options');
                    }
                    elseif(isset($notoptions[$option]))
                    {
                        return apply_filters("default_option_{$option}", $default_value, $option, $passed_default);
                    }

                    $row = $wpdb->get_row($wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option));

                    // Has to be get_row() instead of get_var() because of funkiness with 0, false, null values.
                    if(is_object($row))
                    {
                        $value = $row->option_value;
                        wp_cache_add($option, $value, 'options');
                    }
                    else
                    { // Option does not exist, so we must cache its non-existence.
                        $notoptions[$option] = true;
                        wp_cache_set('notoptions', $notoptions, 'options');

                        return apply_filters("default_option_{$option}", $default_value, $option, $passed_default);
                    }
                }
            }
        }
        else
        {
            $suppress = $wpdb->suppress_errors();
            $row = $wpdb->get_row($wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option));
            $wpdb->suppress_errors($suppress);

            if(is_object($row))
            {
                $value = $row->option_value;
            }
            else
            {
                return apply_filters("default_option_{$option}", $default_value, $option, $passed_default);
            }
        }

        // If home is not set, use siteurl.
        if('home' === $option && '' === $value)
        {
            return get_option('siteurl');
        }

        if(in_array($option, ['siteurl', 'home', 'category_base', 'tag_base'], true))
        {
            $value = untrailingslashit($value);
        }

        return apply_filters("option_{$option}", maybe_unserialize($value), $option);
    }

    function prime_options($options)
    {
        $alloptions = wp_load_alloptions();
        $cached_options = wp_cache_get_multiple($options, 'options');

        // Filter options that are not in the cache.
        $options_to_prime = [];
        foreach($options as $option)
        {
            if((! isset($cached_options[$option]) || ! $cached_options[$option]) && ! isset($alloptions[$option]))
            {
                $options_to_prime[] = $option;
            }
        }

        // Bail early if there are no options to be primed.
        if(empty($options_to_prime))
        {
            return;
        }

        global $wpdb;
        $results = $wpdb->get_results($wpdb->prepare(sprintf("SELECT option_name, option_value FROM $wpdb->options WHERE option_name IN (%s)", implode(',', array_fill(0, count($options_to_prime), '%s'))), $options_to_prime));

        $options_found = [];
        foreach($results as $result)
        {
            $options_found[$result->option_name] = maybe_unserialize($result->option_value);
        }
        wp_cache_set_multiple($options_found, 'options');

        // If all options were found, no need to update `notoptions` cache.
        if(count($options_found) === count($options_to_prime))
        {
            return;
        }

        $options_not_found = array_diff($options_to_prime, array_keys($options_found));

        $notoptions = wp_cache_get('notoptions', 'options');

        if(! is_array($notoptions))
        {
            $notoptions = [];
        }

        // Add the options that were not found to the cache.
        $update_notoptions = false;
        foreach($options_not_found as $option_name)
        {
            if(! isset($notoptions[$option_name]))
            {
                $notoptions[$option_name] = true;
                $update_notoptions = true;
            }
        }

        // Only update the cache if it was modified.
        if($update_notoptions)
        {
            wp_cache_set('notoptions', $notoptions, 'options');
        }
    }

    function prime_options_by_group($option_group)
    {
        global $new_allowed_options;

        if(isset($new_allowed_options[$option_group]))
        {
            prime_options($new_allowed_options[$option_group]);
        }
    }

    function get_options($options)
    {
        prime_options($options);

        $result = [];
        foreach($options as $option)
        {
            $result[$option] = get_option($option);
        }

        return $result;
    }

    function wp_set_option_autoload_values(array $options)
    {
        global $wpdb;

        if(! $options)
        {
            return [];
        }

        $grouped_options = [
            'yes' => [],
            'no' => [],
        ];
        $results = [];
        foreach($options as $option => $autoload)
        {
            wp_protect_special_option($option); // Ensure only valid options can be passed.
            if('no' === $autoload || false === $autoload)
            { // Sanitize autoload value and categorize accordingly.
                $grouped_options['no'][] = $option;
            }
            else
            {
                $grouped_options['yes'][] = $option;
            }
            $results[$option] = false; // Initialize result value.
        }

        $where = [];
        $where_args = [];
        foreach($grouped_options as $autoload => $options)
        {
            if(! $options)
            {
                continue;
            }
            $placeholders = implode(',', array_fill(0, count($options), '%s'));
            $where[] = "autoload != '%s' AND option_name IN ($placeholders)";
            $where_args[] = $autoload;
            foreach($options as $option)
            {
                $where_args[] = $option;
            }
        }
        $where = 'WHERE '.implode(' OR ', $where);

        /*
	 * Determine the relevant options that do not already use the given autoload value.
	 * If no options are returned, no need to update.
	 */
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
        $options_to_update = $wpdb->get_col($wpdb->prepare("SELECT option_name FROM $wpdb->options $where", $where_args));
        if(! $options_to_update)
        {
            return $results;
        }

        // Run UPDATE queries as needed (maximum 2) to update the relevant options' autoload values to 'yes' or 'no'.
        foreach($grouped_options as $autoload => $options)
        {
            if(! $options)
            {
                continue;
            }
            $options = array_intersect($options, $options_to_update);
            $grouped_options[$autoload] = $options;
            if(! $grouped_options[$autoload])
            {
                continue;
            }

            // Run query to update autoload value for all the options where it is needed.
            $success = $wpdb->query($wpdb->prepare("UPDATE $wpdb->options SET autoload = %s WHERE option_name IN (".implode(',', array_fill(0, count($grouped_options[$autoload]), '%s')).')', array_merge([$autoload], $grouped_options[$autoload])));
            if(! $success)
            {
                // Set option list to an empty array to indicate no options were updated.
                $grouped_options[$autoload] = [];
                continue;
            }

            // Assume that on success all options were updated, which should be the case given only new values are sent.
            foreach($grouped_options[$autoload] as $option)
            {
                $results[$option] = true;
            }
        }

        /*
	 * If any options were changed to 'yes', delete their individual caches, and delete 'alloptions' cache so that it
	 * is refreshed as needed.
	 * If no options were changed to 'yes' but any options were changed to 'no', delete them from the 'alloptions'
	 * cache. This is not necessary when options were changed to 'yes', since in that situation the entire cache is
	 * deleted anyway.
	 */
        if($grouped_options['yes'])
        {
            wp_cache_delete_multiple($grouped_options['yes'], 'options');
            wp_cache_delete('alloptions', 'options');
        }
        elseif($grouped_options['no'])
        {
            $alloptions = wp_load_alloptions(true);
            foreach($grouped_options['no'] as $option)
            {
                if(isset($alloptions[$option]))
                {
                    unset($alloptions[$option]);
                }
            }
            wp_cache_set('alloptions', $alloptions, 'options');
        }

        return $results;
    }

    function wp_set_options_autoload(array $options, $autoload)
    {
        return wp_set_option_autoload_values(array_fill_keys($options, $autoload));
    }

    function wp_set_option_autoload($option, $autoload)
    {
        $result = wp_set_option_autoload_values([$option => $autoload]);
        if(isset($result[$option]))
        {
            return $result[$option];
        }

        return false;
    }

    function wp_protect_special_option($option)
    {
        if('alloptions' === $option || 'notoptions' === $option)
        {
            wp_die(sprintf(/* translators: %s: Option name. */ __('%s is a protected WP option and may not be modified'), esc_html($option)));
        }
    }

    function form_option($option)
    {
        echo esc_attr(get_option($option));
    }

    function wp_load_alloptions($force_cache = false)
    {
        global $wpdb;

        $alloptions = apply_filters('pre_wp_load_alloptions', null, $force_cache);
        if(is_array($alloptions))
        {
            return $alloptions;
        }

        if(! wp_installing() || ! is_multisite())
        {
            $alloptions = wp_cache_get('alloptions', 'options', $force_cache);
        }
        else
        {
            $alloptions = false;
        }

        if(! $alloptions)
        {
            $suppress = $wpdb->suppress_errors();
            $alloptions_db = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options WHERE autoload = 'yes'");
            if(! $alloptions_db)
            {
                $alloptions_db = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options");
            }
            $wpdb->suppress_errors($suppress);

            $alloptions = [];
            foreach((array) $alloptions_db as $o)
            {
                $alloptions[$o->option_name] = $o->option_value;
            }

            if(! wp_installing() || ! is_multisite())
            {
                $alloptions = apply_filters('pre_cache_alloptions', $alloptions);

                wp_cache_add('alloptions', $alloptions, 'options');
            }
        }

        return apply_filters('alloptions', $alloptions);
    }

    function wp_load_core_site_options($network_id = null)
    {
        global $wpdb;

        if(! is_multisite() || wp_installing())
        {
            return;
        }

        if(empty($network_id))
        {
            $network_id = get_current_network_id();
        }

        $core_options = [
            'site_name',
            'siteurl',
            'active_sitewide_plugins',
            '_site_transient_timeout_theme_roots',
            '_site_transient_theme_roots',
            'site_admins',
            'can_compress_scripts',
            'global_terms_enabled',
            'ms_files_rewriting'
        ];

        if(wp_using_ext_object_cache())
        {
            $cache_keys = [];
            foreach($core_options as $option)
            {
                $cache_keys[] = "{$network_id}:{$option}";
            }
            wp_cache_get_multiple($cache_keys, 'site-options');

            return;
        }

        $core_options_in = "'".implode("', '", $core_options)."'";
        $options = $wpdb->get_results($wpdb->prepare("SELECT meta_key, meta_value FROM $wpdb->sitemeta WHERE meta_key IN ($core_options_in) AND site_id = %d", $network_id));

        $data = [];
        foreach($options as $option)
        {
            $key = $option->meta_key;
            $cache_key = "{$network_id}:$key";
            $option->meta_value = maybe_unserialize($option->meta_value);

            $data[$cache_key] = $option->meta_value;
        }
        wp_cache_set_multiple($data, 'site-options');
    }

    function update_option($option, $value, $autoload = null)
    {
        global $wpdb;

        if(is_scalar($option))
        {
            $option = trim($option);
        }

        if(empty($option))
        {
            return false;
        }

        /*
	 * Until a proper _deprecated_option() function can be introduced,
	 * redirect requests to deprecated keys to the new, correct ones.
	 */
        $deprecated_keys = [
            'blacklist_keys' => 'disallowed_keys',
            'comment_whitelist' => 'comment_previously_approved',
        ];

        if(isset($deprecated_keys[$option]) && ! wp_installing())
        {
            _deprecated_argument(__FUNCTION__, '5.5.0', sprintf(/* translators: 1: Deprecated option key, 2: New option key. */ __('The "%1$s" option key has been renamed to "%2$s".'), $option, $deprecated_keys[$option]));

            return update_option($deprecated_keys[$option], $value, $autoload);
        }

        wp_protect_special_option($option);

        if(is_object($value))
        {
            $value = clone $value;
        }

        $value = sanitize_option($option, $value);
        $old_value = get_option($option);

        $value = apply_filters("pre_update_option_{$option}", $value, $old_value, $option);

        $value = apply_filters('pre_update_option', $value, $option, $old_value);

        /*
	 * If the new and old values are the same, no need to update.
	 *
	 * Unserialized values will be adequate in most cases. If the unserialized
	 * data differs, the (maybe) serialized data is checked to avoid
	 * unnecessary database calls for otherwise identical object instances.
	 *
	 * See https://core.trac.wordpress.org/ticket/38903
	 */
        if($value === $old_value || maybe_serialize($value) === maybe_serialize($old_value))
        {
            return false;
        }

        if(apply_filters("default_option_{$option}", false, $option, false) === $old_value)
        {
            // Default setting for new options is 'yes'.
            if(null === $autoload)
            {
                $autoload = 'yes';
            }

            return add_option($option, $value, '', $autoload);
        }

        $serialized_value = maybe_serialize($value);

        do_action('update_option', $option, $old_value, $value);

        $update_args = [
            'option_value' => $serialized_value,
        ];

        if(null !== $autoload)
        {
            $update_args['autoload'] = ('no' === $autoload || false === $autoload) ? 'no' : 'yes';
        }

        $result = $wpdb->update($wpdb->options, $update_args, ['option_name' => $option]);
        if(! $result)
        {
            return false;
        }

        $notoptions = wp_cache_get('notoptions', 'options');

        if(is_array($notoptions) && isset($notoptions[$option]))
        {
            unset($notoptions[$option]);
            wp_cache_set('notoptions', $notoptions, 'options');
        }

        if(! wp_installing())
        {
            $alloptions = wp_load_alloptions(true);
            if(isset($alloptions[$option]))
            {
                $alloptions[$option] = $serialized_value;
                wp_cache_set('alloptions', $alloptions, 'options');
            }
            else
            {
                wp_cache_set($option, $serialized_value, 'options');
            }
        }

        do_action("update_option_{$option}", $old_value, $value, $option);

        do_action('updated_option', $option, $old_value, $value);

        return true;
    }

    function add_option($option, $value = '', $deprecated = '', $autoload = 'yes')
    {
        global $wpdb;

        if(! empty($deprecated))
        {
            _deprecated_argument(__FUNCTION__, '2.3.0');
        }

        if(is_scalar($option))
        {
            $option = trim($option);
        }

        if(empty($option))
        {
            return false;
        }

        /*
	 * Until a proper _deprecated_option() function can be introduced,
	 * redirect requests to deprecated keys to the new, correct ones.
	 */
        $deprecated_keys = [
            'blacklist_keys' => 'disallowed_keys',
            'comment_whitelist' => 'comment_previously_approved',
        ];

        if(isset($deprecated_keys[$option]) && ! wp_installing())
        {
            _deprecated_argument(__FUNCTION__, '5.5.0', sprintf(/* translators: 1: Deprecated option key, 2: New option key. */ __('The "%1$s" option key has been renamed to "%2$s".'), $option, $deprecated_keys[$option]));

            return add_option($deprecated_keys[$option], $value, $deprecated, $autoload);
        }

        wp_protect_special_option($option);

        if(is_object($value))
        {
            $value = clone $value;
        }

        $value = sanitize_option($option, $value);

        /*
	 * Make sure the option doesn't already exist.
	 * We can check the 'notoptions' cache before we ask for a DB query.
	 */
        $notoptions = wp_cache_get('notoptions', 'options');

        if(! is_array($notoptions) || ! isset($notoptions[$option]))
        {
            if(apply_filters("default_option_{$option}", false, $option, false) !== get_option($option))
            {
                return false;
            }
        }

        $serialized_value = maybe_serialize($value);
        $autoload = ('no' === $autoload || false === $autoload) ? 'no' : 'yes';

        do_action('add_option', $option, $value);

        $result = $wpdb->query($wpdb->prepare("INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)", $option, $serialized_value, $autoload));
        if(! $result)
        {
            return false;
        }

        if(! wp_installing())
        {
            if('yes' === $autoload)
            {
                $alloptions = wp_load_alloptions(true);
                $alloptions[$option] = $serialized_value;
                wp_cache_set('alloptions', $alloptions, 'options');
            }
            else
            {
                wp_cache_set($option, $serialized_value, 'options');
            }
        }

        // This option exists now.
        $notoptions = wp_cache_get('notoptions', 'options'); // Yes, again... we need it to be fresh.

        if(is_array($notoptions) && isset($notoptions[$option]))
        {
            unset($notoptions[$option]);
            wp_cache_set('notoptions', $notoptions, 'options');
        }

        do_action("add_option_{$option}", $option, $value);

        do_action('added_option', $option, $value);

        return true;
    }

    function delete_option($option)
    {
        global $wpdb;

        if(is_scalar($option))
        {
            $option = trim($option);
        }

        if(empty($option))
        {
            return false;
        }

        wp_protect_special_option($option);

        // Get the ID, if no ID then return.
        $row = $wpdb->get_row($wpdb->prepare("SELECT autoload FROM $wpdb->options WHERE option_name = %s", $option));
        if(is_null($row))
        {
            return false;
        }

        do_action('delete_option', $option);

        $result = $wpdb->delete($wpdb->options, ['option_name' => $option]);

        if(! wp_installing())
        {
            if('yes' === $row->autoload)
            {
                $alloptions = wp_load_alloptions(true);
                if(is_array($alloptions) && isset($alloptions[$option]))
                {
                    unset($alloptions[$option]);
                    wp_cache_set('alloptions', $alloptions, 'options');
                }
            }
            else
            {
                wp_cache_delete($option, 'options');
            }
        }

        if($result)
        {
            do_action("delete_option_{$option}", $option);

            do_action('deleted_option', $option);

            return true;
        }

        return false;
    }

    function delete_transient($transient)
    {
        do_action("delete_transient_{$transient}", $transient);

        if(wp_using_ext_object_cache() || wp_installing())
        {
            $result = wp_cache_delete($transient, 'transient');
        }
        else
        {
            $option_timeout = '_transient_timeout_'.$transient;
            $option = '_transient_'.$transient;
            $result = delete_option($option);

            if($result)
            {
                delete_option($option_timeout);
            }
        }

        if($result)
        {
            do_action('deleted_transient', $transient);
        }

        return $result;
    }

    function get_transient($transient)
    {
        $pre = apply_filters("pre_transient_{$transient}", false, $transient);

        if(false !== $pre)
        {
            return $pre;
        }

        if(wp_using_ext_object_cache() || wp_installing())
        {
            $value = wp_cache_get($transient, 'transient');
        }
        else
        {
            $transient_option = '_transient_'.$transient;
            if(! wp_installing())
            {
                // If option is not in alloptions, it is not autoloaded and thus has a timeout.
                $alloptions = wp_load_alloptions();
                if(! isset($alloptions[$transient_option]))
                {
                    $transient_timeout = '_transient_timeout_'.$transient;
                    $timeout = get_option($transient_timeout);
                    if(false !== $timeout && $timeout < time())
                    {
                        delete_option($transient_option);
                        delete_option($transient_timeout);
                        $value = false;
                    }
                }
            }

            if(! isset($value))
            {
                $value = get_option($transient_option);
            }
        }

        return apply_filters("transient_{$transient}", $value, $transient);
    }

    function set_transient($transient, $value, $expiration = 0)
    {
        $expiration = (int) $expiration;

        $value = apply_filters("pre_set_transient_{$transient}", $value, $expiration, $transient);

        $expiration = apply_filters("expiration_of_transient_{$transient}", $expiration, $value, $transient);

        if(wp_using_ext_object_cache() || wp_installing())
        {
            $result = wp_cache_set($transient, $value, 'transient', $expiration);
        }
        else
        {
            $transient_timeout = '_transient_timeout_'.$transient;
            $transient_option = '_transient_'.$transient;

            if(false === get_option($transient_option))
            {
                $autoload = 'yes';
                if($expiration)
                {
                    $autoload = 'no';
                    add_option($transient_timeout, time() + $expiration, '', 'no');
                }
                $result = add_option($transient_option, $value, '', $autoload);
            }
            else
            {
                /*
			 * If expiration is requested, but the transient has no timeout option,
			 * delete, then re-create transient rather than update.
			 */
                $update = true;

                if($expiration)
                {
                    if(false === get_option($transient_timeout))
                    {
                        delete_option($transient_option);
                        add_option($transient_timeout, time() + $expiration, '', 'no');
                        $result = add_option($transient_option, $value, '', 'no');
                        $update = false;
                    }
                    else
                    {
                        update_option($transient_timeout, time() + $expiration);
                    }
                }

                if($update)
                {
                    $result = update_option($transient_option, $value);
                }
            }
        }

        if($result)
        {
            do_action("set_transient_{$transient}", $value, $expiration, $transient);

            do_action('setted_transient', $transient, $value, $expiration);
        }

        return $result;
    }

    function delete_expired_transients($force_db = false)
    {
        global $wpdb;

        if(! $force_db && wp_using_ext_object_cache())
        {
            return;
        }

        $wpdb->query(
            $wpdb->prepare(
                "DELETE a, b FROM {$wpdb->options} a, {$wpdb->options} b
			WHERE a.option_name LIKE %s
			AND a.option_name NOT LIKE %s
			AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
			AND b.option_value < %d", $wpdb->esc_like('_transient_').'%', $wpdb->esc_like('_transient_timeout_').'%', time()
            )
        );

        if(! is_multisite())
        {
            // Single site stores site transients in the options table.
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE a, b FROM {$wpdb->options} a, {$wpdb->options} b
				WHERE a.option_name LIKE %s
				AND a.option_name NOT LIKE %s
				AND b.option_name = CONCAT( '_site_transient_timeout_', SUBSTRING( a.option_name, 17 ) )
				AND b.option_value < %d", $wpdb->esc_like('_site_transient_').'%', $wpdb->esc_like('_site_transient_timeout_').'%', time()
                )
            );
        }
        elseif(is_multisite() && is_main_site() && is_main_network())
        {
            // Multisite stores site transients in the sitemeta table.
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE a, b FROM {$wpdb->sitemeta} a, {$wpdb->sitemeta} b
				WHERE a.meta_key LIKE %s
				AND a.meta_key NOT LIKE %s
				AND b.meta_key = CONCAT( '_site_transient_timeout_', SUBSTRING( a.meta_key, 17 ) )
				AND b.meta_value < %d", $wpdb->esc_like('_site_transient_').'%', $wpdb->esc_like('_site_transient_timeout_').'%', time()
                )
            );
        }
    }

    function wp_user_settings()
    {
        if(! is_admin() || wp_doing_ajax())
        {
            return;
        }

        $user_id = get_current_user_id();
        if(! $user_id)
        {
            return;
        }

        if(! is_user_member_of_blog())
        {
            return;
        }

        $settings = (string) get_user_option('user-settings', $user_id);

        if(isset($_COOKIE['wp-settings-'.$user_id]))
        {
            $cookie = preg_replace('/[^A-Za-z0-9=&_]/', '', $_COOKIE['wp-settings-'.$user_id]);

            // No change or both empty.
            if($cookie === $settings)
            {
                return;
            }

            $last_saved = (int) get_user_option('user-settings-time', $user_id);
            $current = isset($_COOKIE['wp-settings-time-'.$user_id]) ? preg_replace('/[^0-9]/', '', $_COOKIE['wp-settings-time-'.$user_id]) : 0;

            // The cookie is newer than the saved value. Update the user_option and leave the cookie as-is.
            if($current > $last_saved)
            {
                update_user_option($user_id, 'user-settings', $cookie, false);
                update_user_option($user_id, 'user-settings-time', time() - 5, false);

                return;
            }
        }

        // The cookie is not set in the current browser or the saved value is newer.
        $secure = ('https' === parse_url(admin_url(), PHP_URL_SCHEME));
        setcookie('wp-settings-'.$user_id, $settings, time() + YEAR_IN_SECONDS, SITECOOKIEPATH, '', $secure);
        setcookie('wp-settings-time-'.$user_id, time(), time() + YEAR_IN_SECONDS, SITECOOKIEPATH, '', $secure);
        $_COOKIE['wp-settings-'.$user_id] = $settings;
    }

    function get_user_setting($name, $default_value = false)
    {
        $all_user_settings = get_all_user_settings();

        return isset($all_user_settings[$name]) ? $all_user_settings[$name] : $default_value;
    }

    function set_user_setting($name, $value)
    {
        if(headers_sent())
        {
            return false;
        }

        $all_user_settings = get_all_user_settings();
        $all_user_settings[$name] = $value;

        return wp_set_all_user_settings($all_user_settings);
    }

    function delete_user_setting($names)
    {
        if(headers_sent())
        {
            return false;
        }

        $all_user_settings = get_all_user_settings();
        $names = (array) $names;
        $deleted = false;

        foreach($names as $name)
        {
            if(isset($all_user_settings[$name]))
            {
                unset($all_user_settings[$name]);
                $deleted = true;
            }
        }

        if($deleted)
        {
            return wp_set_all_user_settings($all_user_settings);
        }

        return false;
    }

    function get_all_user_settings()
    {
        global $_updated_user_settings;

        $user_id = get_current_user_id();
        if(! $user_id)
        {
            return [];
        }

        if(isset($_updated_user_settings) && is_array($_updated_user_settings))
        {
            return $_updated_user_settings;
        }

        $user_settings = [];

        if(isset($_COOKIE['wp-settings-'.$user_id]))
        {
            $cookie = preg_replace('/[^A-Za-z0-9=&_-]/', '', $_COOKIE['wp-settings-'.$user_id]);

            if(strpos($cookie, '='))
            { // '=' cannot be 1st char.
                parse_str($cookie, $user_settings);
            }
        }
        else
        {
            $option = get_user_option('user-settings', $user_id);

            if($option && is_string($option))
            {
                parse_str($option, $user_settings);
            }
        }

        $_updated_user_settings = $user_settings;

        return $user_settings;
    }

    function wp_set_all_user_settings($user_settings)
    {
        global $_updated_user_settings;

        $user_id = get_current_user_id();
        if(! $user_id)
        {
            return false;
        }

        if(! is_user_member_of_blog())
        {
            return;
        }

        $settings = '';
        foreach($user_settings as $name => $value)
        {
            $_name = preg_replace('/[^A-Za-z0-9_-]+/', '', $name);
            $_value = preg_replace('/[^A-Za-z0-9_-]+/', '', $value);

            if(! empty($_name))
            {
                $settings .= $_name.'='.$_value.'&';
            }
        }

        $settings = rtrim($settings, '&');
        parse_str($settings, $_updated_user_settings);

        update_user_option($user_id, 'user-settings', $settings, false);
        update_user_option($user_id, 'user-settings-time', time(), false);

        return true;
    }

    function delete_all_user_settings()
    {
        $user_id = get_current_user_id();
        if(! $user_id)
        {
            return;
        }

        update_user_option($user_id, 'user-settings', '', false);
        setcookie('wp-settings-'.$user_id, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH);
    }

    function get_site_option($option, $default_value = false, $deprecated = true)
    {
        return get_network_option(null, $option, $default_value);
    }

    function add_site_option($option, $value)
    {
        return add_network_option(null, $option, $value);
    }

    function delete_site_option($option)
    {
        return delete_network_option(null, $option);
    }

    function update_site_option($option, $value)
    {
        return update_network_option(null, $option, $value);
    }

    function get_network_option($network_id, $option, $default_value = false)
    {
        global $wpdb;

        if($network_id && ! is_numeric($network_id))
        {
            return false;
        }

        $network_id = (int) $network_id;

        // Fallback to the current network if a network ID is not specified.
        if(! $network_id)
        {
            $network_id = get_current_network_id();
        }

        $pre = apply_filters("pre_site_option_{$option}", false, $option, $network_id, $default_value);

        if(false !== $pre)
        {
            return $pre;
        }

        // Prevent non-existent options from triggering multiple queries.
        $notoptions_key = "$network_id:notoptions";
        $notoptions = wp_cache_get($notoptions_key, 'site-options');

        if(is_array($notoptions) && isset($notoptions[$option]))
        {
            return apply_filters("default_site_option_{$option}", $default_value, $option, $network_id);
        }

        if(! is_multisite())
        {
            $default_value = apply_filters('default_site_option_'.$option, $default_value, $option, $network_id);
            $value = get_option($option, $default_value);
        }
        else
        {
            $cache_key = "$network_id:$option";
            $value = wp_cache_get($cache_key, 'site-options');

            if(! isset($value) || false === $value)
            {
                $row = $wpdb->get_row($wpdb->prepare("SELECT meta_value FROM $wpdb->sitemeta WHERE meta_key = %s AND site_id = %d", $option, $network_id));

                // Has to be get_row() instead of get_var() because of funkiness with 0, false, null values.
                if(is_object($row))
                {
                    $value = $row->meta_value;
                    $value = maybe_unserialize($value);
                    wp_cache_set($cache_key, $value, 'site-options');
                }
                else
                {
                    if(! is_array($notoptions))
                    {
                        $notoptions = [];
                    }

                    $notoptions[$option] = true;
                    wp_cache_set($notoptions_key, $notoptions, 'site-options');

                    $value = apply_filters('default_site_option_'.$option, $default_value, $option, $network_id);
                }
            }
        }

        if(! is_array($notoptions))
        {
            $notoptions = [];
            wp_cache_set($notoptions_key, $notoptions, 'site-options');
        }

        return apply_filters("site_option_{$option}", $value, $option, $network_id);
    }

    function add_network_option($network_id, $option, $value)
    {
        global $wpdb;

        if($network_id && ! is_numeric($network_id))
        {
            return false;
        }

        $network_id = (int) $network_id;

        // Fallback to the current network if a network ID is not specified.
        if(! $network_id)
        {
            $network_id = get_current_network_id();
        }

        wp_protect_special_option($option);

        $value = apply_filters("pre_add_site_option_{$option}", $value, $option, $network_id);

        $notoptions_key = "$network_id:notoptions";

        if(! is_multisite())
        {
            $result = add_option($option, $value, '', 'no');
        }
        else
        {
            $cache_key = "$network_id:$option";

            /*
		 * Make sure the option doesn't already exist.
		 * We can check the 'notoptions' cache before we ask for a DB query.
		 */
            $notoptions = wp_cache_get($notoptions_key, 'site-options');

            if(! is_array($notoptions) || ! isset($notoptions[$option]))
            {
                if(false !== get_network_option($network_id, $option, false))
                {
                    return false;
                }
            }

            $value = sanitize_option($option, $value);

            $serialized_value = maybe_serialize($value);
            $result = $wpdb->insert($wpdb->sitemeta, [
                'site_id' => $network_id,
                'meta_key' => $option,
                'meta_value' => $serialized_value,
            ]);

            if(! $result)
            {
                return false;
            }

            wp_cache_set($cache_key, $value, 'site-options');

            // This option exists now.
            $notoptions = wp_cache_get($notoptions_key, 'site-options'); // Yes, again... we need it to be fresh.

            if(is_array($notoptions) && isset($notoptions[$option]))
            {
                unset($notoptions[$option]);
                wp_cache_set($notoptions_key, $notoptions, 'site-options');
            }
        }

        if($result)
        {
            do_action("add_site_option_{$option}", $option, $value, $network_id);

            do_action('add_site_option', $option, $value, $network_id);

            return true;
        }

        return false;
    }

    function delete_network_option($network_id, $option)
    {
        global $wpdb;

        if($network_id && ! is_numeric($network_id))
        {
            return false;
        }

        $network_id = (int) $network_id;

        // Fallback to the current network if a network ID is not specified.
        if(! $network_id)
        {
            $network_id = get_current_network_id();
        }

        do_action("pre_delete_site_option_{$option}", $option, $network_id);

        if(! is_multisite())
        {
            $result = delete_option($option);
        }
        else
        {
            $row = $wpdb->get_row($wpdb->prepare("SELECT meta_id FROM {$wpdb->sitemeta} WHERE meta_key = %s AND site_id = %d", $option, $network_id));
            if(is_null($row) || ! $row->meta_id)
            {
                return false;
            }
            $cache_key = "$network_id:$option";
            wp_cache_delete($cache_key, 'site-options');

            $result = $wpdb->delete($wpdb->sitemeta, [
                'meta_key' => $option,
                'site_id' => $network_id,
            ]);
        }

        if($result)
        {
            do_action("delete_site_option_{$option}", $option, $network_id);

            do_action('delete_site_option', $option, $network_id);

            return true;
        }

        return false;
    }

    function update_network_option($network_id, $option, $value)
    {
        global $wpdb;

        if($network_id && ! is_numeric($network_id))
        {
            return false;
        }

        $network_id = (int) $network_id;

        // Fallback to the current network if a network ID is not specified.
        if(! $network_id)
        {
            $network_id = get_current_network_id();
        }

        wp_protect_special_option($option);

        $old_value = get_network_option($network_id, $option, false);

        $value = apply_filters("pre_update_site_option_{$option}", $value, $old_value, $option, $network_id);

        /*
	 * If the new and old values are the same, no need to update.
	 *
	 * Unserialized values will be adequate in most cases. If the unserialized
	 * data differs, the (maybe) serialized data is checked to avoid
	 * unnecessary database calls for otherwise identical object instances.
	 *
	 * See https://core.trac.wordpress.org/ticket/44956
	 */
        if($value === $old_value || maybe_serialize($value) === maybe_serialize($old_value))
        {
            return false;
        }

        if(false === $old_value)
        {
            return add_network_option($network_id, $option, $value);
        }

        $notoptions_key = "$network_id:notoptions";
        $notoptions = wp_cache_get($notoptions_key, 'site-options');

        if(is_array($notoptions) && isset($notoptions[$option]))
        {
            unset($notoptions[$option]);
            wp_cache_set($notoptions_key, $notoptions, 'site-options');
        }

        if(! is_multisite())
        {
            $result = update_option($option, $value, 'no');
        }
        else
        {
            $value = sanitize_option($option, $value);

            $serialized_value = maybe_serialize($value);
            $result = $wpdb->update($wpdb->sitemeta, ['meta_value' => $serialized_value], [
                'site_id' => $network_id,
                'meta_key' => $option,
            ]);

            if($result)
            {
                $cache_key = "$network_id:$option";
                wp_cache_set($cache_key, $value, 'site-options');
            }
        }

        if($result)
        {
            do_action("update_site_option_{$option}", $option, $value, $old_value, $network_id);

            do_action('update_site_option', $option, $value, $old_value, $network_id);

            return true;
        }

        return false;
    }

    function delete_site_transient($transient)
    {
        do_action("delete_site_transient_{$transient}", $transient);

        if(wp_using_ext_object_cache() || wp_installing())
        {
            $result = wp_cache_delete($transient, 'site-transient');
        }
        else
        {
            $option_timeout = '_site_transient_timeout_'.$transient;
            $option = '_site_transient_'.$transient;
            $result = delete_site_option($option);

            if($result)
            {
                delete_site_option($option_timeout);
            }
        }

        if($result)
        {
            do_action('deleted_site_transient', $transient);
        }

        return $result;
    }

    function get_site_transient($transient)
    {
        $pre = apply_filters("pre_site_transient_{$transient}", false, $transient);

        if(false !== $pre)
        {
            return $pre;
        }

        if(wp_using_ext_object_cache() || wp_installing())
        {
            $value = wp_cache_get($transient, 'site-transient');
        }
        else
        {
            // Core transients that do not have a timeout. Listed here so querying timeouts can be avoided.
            $no_timeout = ['update_core', 'update_plugins', 'update_themes'];
            $transient_option = '_site_transient_'.$transient;
            if(! in_array($transient, $no_timeout, true))
            {
                $transient_timeout = '_site_transient_timeout_'.$transient;
                $timeout = get_site_option($transient_timeout);
                if(false !== $timeout && $timeout < time())
                {
                    delete_site_option($transient_option);
                    delete_site_option($transient_timeout);
                    $value = false;
                }
            }

            if(! isset($value))
            {
                $value = get_site_option($transient_option);
            }
        }

        return apply_filters("site_transient_{$transient}", $value, $transient);
    }

    function set_site_transient($transient, $value, $expiration = 0)
    {
        $value = apply_filters("pre_set_site_transient_{$transient}", $value, $transient);

        $expiration = (int) $expiration;

        $expiration = apply_filters("expiration_of_site_transient_{$transient}", $expiration, $value, $transient);

        if(wp_using_ext_object_cache() || wp_installing())
        {
            $result = wp_cache_set($transient, $value, 'site-transient', $expiration);
        }
        else
        {
            $transient_timeout = '_site_transient_timeout_'.$transient;
            $option = '_site_transient_'.$transient;

            if(false === get_site_option($option))
            {
                if($expiration)
                {
                    add_site_option($transient_timeout, time() + $expiration);
                }
                $result = add_site_option($option, $value);
            }
            else
            {
                if($expiration)
                {
                    update_site_option($transient_timeout, time() + $expiration);
                }
                $result = update_site_option($option, $value);
            }
        }

        if($result)
        {
            do_action("set_site_transient_{$transient}", $value, $expiration, $transient);

            do_action('setted_site_transient', $transient, $value, $expiration);
        }

        return $result;
    }

    function register_initial_settings()
    {
        register_setting('general', 'blogname', [
            'show_in_rest' => [
                'name' => 'title',
            ],
            'type' => 'string',
            'description' => __('Site title.'),
        ]);

        register_setting('general', 'blogdescription', [
            'show_in_rest' => [
                'name' => 'description',
            ],
            'type' => 'string',
            'description' => __('Site tagline.'),
        ]);

        if(! is_multisite())
        {
            register_setting('general', 'siteurl', [
                'show_in_rest' => [
                    'name' => 'url',
                    'schema' => [
                        'format' => 'uri',
                    ],
                ],
                'type' => 'string',
                'description' => __('Site URL.'),
            ]);
        }

        if(! is_multisite())
        {
            register_setting('general', 'admin_email', [
                'show_in_rest' => [
                    'name' => 'email',
                    'schema' => [
                        'format' => 'email',
                    ],
                ],
                'type' => 'string',
                'description' => __('This address is used for admin purposes, like new user notification.'),
            ]);
        }

        register_setting('general', 'timezone_string', [
            'show_in_rest' => [
                'name' => 'timezone',
            ],
            'type' => 'string',
            'description' => __('A city in the same timezone as you.'),
        ]);

        register_setting('general', 'date_format', [
            'show_in_rest' => true,
            'type' => 'string',
            'description' => __('A date format for all date strings.'),
        ]);

        register_setting('general', 'time_format', [
            'show_in_rest' => true,
            'type' => 'string',
            'description' => __('A time format for all time strings.'),
        ]);

        register_setting('general', 'start_of_week', [
            'show_in_rest' => true,
            'type' => 'integer',
            'description' => __('A day number of the week that the week should start on.'),
        ]);

        register_setting('general', 'WPLANG', [
            'show_in_rest' => [
                'name' => 'language',
            ],
            'type' => 'string',
            'description' => __('WordPress locale code.'),
            'default' => 'en_US',
        ]);

        register_setting('writing', 'use_smilies', [
            'show_in_rest' => true,
            'type' => 'boolean',
            'description' => __('Convert emoticons like :-) and :-P to graphics on display.'),
            'default' => true,
        ]);

        register_setting('writing', 'default_category', [
            'show_in_rest' => true,
            'type' => 'integer',
            'description' => __('Default post category.'),
        ]);

        register_setting('writing', 'default_post_format', [
            'show_in_rest' => true,
            'type' => 'string',
            'description' => __('Default post format.'),
        ]);

        register_setting('reading', 'posts_per_page', [
            'show_in_rest' => true,
            'type' => 'integer',
            'description' => __('Blog pages show at most.'),
            'default' => 10,
        ]);

        register_setting('reading', 'show_on_front', [
            'show_in_rest' => true,
            'type' => 'string',
            'description' => __('What to show on the front page'),
        ]);

        register_setting('reading', 'page_on_front', [
            'show_in_rest' => true,
            'type' => 'integer',
            'description' => __('The ID of the page that should be displayed on the front page'),
        ]);

        register_setting('reading', 'page_for_posts', [
            'show_in_rest' => true,
            'type' => 'integer',
            'description' => __('The ID of the page that should display the latest posts'),
        ]);

        register_setting('discussion', 'default_ping_status', [
            'show_in_rest' => [
                'schema' => [
                    'enum' => ['open', 'closed'],
                ],
            ],
            'type' => 'string',
            'description' => __('Allow link notifications from other blogs (pingbacks and trackbacks) on new articles.'),
        ]);

        register_setting('discussion', 'default_comment_status', [
            'show_in_rest' => [
                'schema' => [
                    'enum' => ['open', 'closed'],
                ],
            ],
            'type' => 'string',
            'description' => __('Allow people to submit comments on new posts.'),
        ]);
    }

    function register_setting($option_group, $option_name, $args = [])
    {
        global $new_allowed_options, $wp_registered_settings;

        /*
	 * In 5.5.0, the `$new_whitelist_options` global variable was renamed to `$new_allowed_options`.
	 * Please consider writing more inclusive code.
	 */
        $GLOBALS['new_whitelist_options'] = &$new_allowed_options;

        $defaults = [
            'type' => 'string',
            'group' => $option_group,
            'description' => '',
            'sanitize_callback' => null,
            'show_in_rest' => false,
        ];

        // Back-compat: old sanitize callback is added.
        if(is_callable($args))
        {
            $args = [
                'sanitize_callback' => $args,
            ];
        }

        $args = apply_filters('register_setting_args', $args, $defaults, $option_group, $option_name);

        $args = wp_parse_args($args, $defaults);

        // Require an item schema when registering settings with an array type.
        if(false !== $args['show_in_rest'] && 'array' === $args['type'] && (! is_array($args['show_in_rest']) || ! isset($args['show_in_rest']['schema']['items'])))
        {
            _doing_it_wrong(__FUNCTION__, __('When registering an "array" setting to show in the REST API, you must specify the schema for each array item in "show_in_rest.schema.items".'), '5.4.0');
        }

        if(! is_array($wp_registered_settings))
        {
            $wp_registered_settings = [];
        }

        if('misc' === $option_group)
        {
            _deprecated_argument(__FUNCTION__, '3.0.0', sprintf(/* translators: %s: misc */ __('The "%s" options group has been removed. Use another settings group.'), 'misc'));
            $option_group = 'general';
        }

        if('privacy' === $option_group)
        {
            _deprecated_argument(__FUNCTION__, '3.5.0', sprintf(/* translators: %s: privacy */ __('The "%s" options group has been removed. Use another settings group.'), 'privacy'));
            $option_group = 'reading';
        }

        $new_allowed_options[$option_group][] = $option_name;

        if(! empty($args['sanitize_callback']))
        {
            add_filter("sanitize_option_{$option_name}", $args['sanitize_callback']);
        }
        if(array_key_exists('default', $args))
        {
            add_filter("default_option_{$option_name}", 'filter_default_option', 10, 3);
        }

        do_action('register_setting', $option_group, $option_name, $args);

        $wp_registered_settings[$option_name] = $args;
    }

    function unregister_setting($option_group, $option_name, $deprecated = '')
    {
        global $new_allowed_options, $wp_registered_settings;

        /*
	 * In 5.5.0, the `$new_whitelist_options` global variable was renamed to `$new_allowed_options`.
	 * Please consider writing more inclusive code.
	 */
        $GLOBALS['new_whitelist_options'] = &$new_allowed_options;

        if('misc' === $option_group)
        {
            _deprecated_argument(__FUNCTION__, '3.0.0', sprintf(/* translators: %s: misc */ __('The "%s" options group has been removed. Use another settings group.'), 'misc'));
            $option_group = 'general';
        }

        if('privacy' === $option_group)
        {
            _deprecated_argument(__FUNCTION__, '3.5.0', sprintf(/* translators: %s: privacy */ __('The "%s" options group has been removed. Use another settings group.'), 'privacy'));
            $option_group = 'reading';
        }

        $pos = array_search($option_name, (array) $new_allowed_options[$option_group], true);

        if(false !== $pos)
        {
            unset($new_allowed_options[$option_group][$pos]);
        }

        if('' !== $deprecated)
        {
            _deprecated_argument(__FUNCTION__, '4.7.0', sprintf(/* translators: 1: $sanitize_callback, 2: register_setting() */ __('%1$s is deprecated. The callback from %2$s is used instead.'), '<code>$sanitize_callback</code>', '<code>register_setting()</code>'));
            remove_filter("sanitize_option_{$option_name}", $deprecated);
        }

        if(isset($wp_registered_settings[$option_name]))
        {
            // Remove the sanitize callback if one was set during registration.
            if(! empty($wp_registered_settings[$option_name]['sanitize_callback']))
            {
                remove_filter("sanitize_option_{$option_name}", $wp_registered_settings[$option_name]['sanitize_callback']);
            }

            // Remove the default filter if a default was provided during registration.
            if(array_key_exists('default', $wp_registered_settings[$option_name]))
            {
                remove_filter("default_option_{$option_name}", 'filter_default_option', 10);
            }

            do_action('unregister_setting', $option_group, $option_name);

            unset($wp_registered_settings[$option_name]);
        }
    }

    function get_registered_settings()
    {
        global $wp_registered_settings;

        if(! is_array($wp_registered_settings))
        {
            return [];
        }

        return $wp_registered_settings;
    }

    function filter_default_option($default_value, $option, $passed_default)
    {
        if($passed_default)
        {
            return $default_value;
        }

        $registered = get_registered_settings();
        if(empty($registered[$option]))
        {
            return $default_value;
        }

        return $registered[$option]['default'];
    }
