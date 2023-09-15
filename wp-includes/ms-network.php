<?php

    function get_network($network = null)
    {
        global $current_site;
        if(empty($network) && isset($current_site))
        {
            $network = $current_site;
        }

        if($network instanceof WP_Network)
        {
            $_network = $network;
        }
        elseif(is_object($network))
        {
            $_network = new WP_Network($network);
        }
        else
        {
            $_network = WP_Network::get_instance($network);
        }

        if(! $_network)
        {
            return null;
        }

        $_network = apply_filters('get_network', $_network);

        return $_network;
    }

    function get_networks($args = [])
    {
        $query = new WP_Network_Query();

        return $query->query($args);
    }

    function clean_network_cache($ids)
    {
        global $_wp_suspend_cache_invalidation;

        if(! empty($_wp_suspend_cache_invalidation))
        {
            return;
        }

        $network_ids = (array) $ids;
        wp_cache_delete_multiple($network_ids, 'networks');

        foreach($network_ids as $id)
        {
            do_action('clean_network_cache', $id);
        }

        wp_cache_set_last_changed('networks');
    }

    function update_network_cache($networks)
    {
        $data = [];
        foreach((array) $networks as $network)
        {
            $data[$network->id] = $network;
        }
        wp_cache_add_multiple($data, 'networks');
    }

    function _prime_network_caches($network_ids)
    {
        global $wpdb;

        $non_cached_ids = _get_non_cached_ids($network_ids, 'networks');
        if(! empty($non_cached_ids))
        {
            $fresh_networks = $wpdb->get_results(sprintf("SELECT $wpdb->site.* FROM $wpdb->site WHERE id IN (%s)", implode(',', array_map('intval', $non_cached_ids)))); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

            update_network_cache($fresh_networks);
        }
    }
