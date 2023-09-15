<?php

    function wp_sitemaps_get_server()
    {
        global $wp_sitemaps;

        // If there isn't a global instance, set and bootstrap the sitemaps system.
        if(empty($wp_sitemaps))
        {
            $wp_sitemaps = new WP_Sitemaps();
            $wp_sitemaps->init();

            do_action('wp_sitemaps_init', $wp_sitemaps);
        }

        return $wp_sitemaps;
    }

    function wp_get_sitemap_providers()
    {
        $sitemaps = wp_sitemaps_get_server();

        return $sitemaps->registry->get_providers();
    }

    function wp_register_sitemap_provider($name, WP_Sitemaps_Provider $provider)
    {
        $sitemaps = wp_sitemaps_get_server();

        return $sitemaps->registry->add_provider($name, $provider);
    }

    function wp_sitemaps_get_max_urls($object_type)
    {
        return apply_filters('wp_sitemaps_max_urls', 2000, $object_type);
    }

    function get_sitemap_url($name, $subtype_name = '', $page = 1)
    {
        $sitemaps = wp_sitemaps_get_server();

        if(! $sitemaps)
        {
            return false;
        }

        if('index' === $name)
        {
            return $sitemaps->index->get_index_url();
        }

        $provider = $sitemaps->registry->get_provider($name);
        if(! $provider)
        {
            return false;
        }

        if($subtype_name && ! array_key_exists($subtype_name, $provider->get_object_subtypes()))
        {
            return false;
        }

        $page = absint($page);
        if(0 >= $page)
        {
            $page = 1;
        }

        return $provider->get_sitemap_url($subtype_name, $page);
    }
