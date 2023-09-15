<?php

    global $current_site, $current_blog, $domain, $path, $site_id, $public;

    require_once ABSPATH.WPINC.'/class-wp-network.php';

    require_once ABSPATH.WPINC.'/class-wp-site.php';

    require_once ABSPATH.WPINC.'/ms-load.php';

    require_once ABSPATH.WPINC.'/ms-default-constants.php';

    if(defined('SUNRISE'))
    {
        include_once WP_CONTENT_DIR.'/sunrise.php';
    }

    ms_subdomain_constants();

// This block will process a request if the current network or current site objects
// have not been populated in the global scope through something like `sunrise.php`.
    if(! isset($current_site) || ! isset($current_blog))
    {
        $domain = strtolower(stripslashes($_SERVER['HTTP_HOST']));
        if(str_ends_with($domain, ':80'))
        {
            $domain = substr($domain, 0, -3);
            $_SERVER['HTTP_HOST'] = substr($_SERVER['HTTP_HOST'], 0, -3);
        }
        elseif(str_ends_with($domain, ':443'))
        {
            $domain = substr($domain, 0, -4);
            $_SERVER['HTTP_HOST'] = substr($_SERVER['HTTP_HOST'], 0, -4);
        }

        $path = stripslashes($_SERVER['REQUEST_URI']);
        if(is_admin())
        {
            $path = preg_replace('#(.*)/wp-admin/.*#', '$1/', $path);
        }
        [$path] = explode('?', $path);

        $bootstrap_result = ms_load_current_site_and_network($domain, $path, is_subdomain_install());

        if(true === $bootstrap_result)
        {
            // `$current_blog` and `$current_site are now populated.
        }
        elseif(false !== $bootstrap_result)
        {
            header('Location: '.$bootstrap_result);
            exit;
        }
        else
        {
            ms_not_installed($domain, $path);
        }
        unset($bootstrap_result);

        $blog_id = $current_blog->blog_id;
        $public = $current_blog->public;

        if(empty($current_blog->site_id))
        {
            // This dates to [MU134] and shouldn't be relevant anymore,
            // but it could be possible for arguments passed to insert_blog() etc.
            $current_blog->site_id = 1;
        }

        $site_id = $current_blog->site_id;
        wp_load_core_site_options($site_id);
    }

    $wpdb->set_prefix($table_prefix, false); // $table_prefix can be set in sunrise.php.
    $wpdb->set_blog_id($current_blog->blog_id, $current_blog->site_id);
    $table_prefix = $wpdb->get_blog_prefix();
    $_wp_switched_stack = [];
    $switched = false;

// Need to init cache again after blog_id is set.
    wp_start_object_cache();

    if(! $current_site instanceof WP_Network)
    {
        $current_site = new WP_Network($current_site);
    }

    if(! $current_blog instanceof WP_Site)
    {
        $current_blog = new WP_Site($current_blog);
    }

// Define upload directory constants.
    ms_upload_constants();

    do_action('ms_loaded');
