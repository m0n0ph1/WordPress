<?php

    function is_subdomain_install()
    {
        if(defined('SUBDOMAIN_INSTALL'))
        {
            return SUBDOMAIN_INSTALL;
        }

        return (defined('VHOST') && 'yes' === VHOST);
    }

    function wp_get_active_network_plugins()
    {
        $active_plugins = (array) get_site_option('active_sitewide_plugins', []);
        if(empty($active_plugins))
        {
            return [];
        }

        $plugins = [];
        $active_plugins = array_keys($active_plugins);
        sort($active_plugins);

        foreach($active_plugins as $plugin)
        {
            if(
                ! validate_file($plugin)                     // $plugin must validate as file.
                && str_ends_with($plugin, '.php')             // $plugin must end with '.php'.
                && file_exists(WP_PLUGIN_DIR.'/'.$plugin) // $plugin must exist.
            )
            {
                $plugins[] = WP_PLUGIN_DIR.'/'.$plugin;
            }
        }

        return $plugins;
    }

    function ms_site_check()
    {
        $check = apply_filters('ms_site_check', null);
        if(null !== $check)
        {
            return true;
        }

        // Allow super admins to see blocked sites.
        if(is_super_admin())
        {
            return true;
        }

        $blog = get_site();

        if('1' == $blog->deleted)
        {
            if(file_exists(WP_CONTENT_DIR.'/blog-deleted.php'))
            {
                return WP_CONTENT_DIR.'/blog-deleted.php';
            }
            else
            {
                wp_die(__('This site is no longer available.'), '', ['response' => 410]);
            }
        }

        if('2' == $blog->deleted)
        {
            if(file_exists(WP_CONTENT_DIR.'/blog-inactive.php'))
            {
                return WP_CONTENT_DIR.'/blog-inactive.php';
            }
            else
            {
                $admin_email = str_replace('@', ' AT ', get_site_option('admin_email', 'support@'.get_network()->domain));
                wp_die(sprintf(/* translators: %s: Admin email link. */ __('This site has not been activated yet. If you are having problems activating your site, please contact %s.'), sprintf('<a href="mailto:%1$s">%1$s</a>', $admin_email)));
            }
        }

        if('1' == $blog->archived || '1' == $blog->spam)
        {
            if(file_exists(WP_CONTENT_DIR.'/blog-suspended.php'))
            {
                return WP_CONTENT_DIR.'/blog-suspended.php';
            }
            else
            {
                wp_die(__('This site has been archived or suspended.'), '', ['response' => 410]);
            }
        }

        return true;
    }

    function get_network_by_path($domain, $path, $segments = null)
    {
        return WP_Network::get_by_path($domain, $path, $segments);
    }

    function get_site_by_path($domain, $path, $segments = null)
    {
        $path_segments = array_filter(explode('/', trim($path, '/')));

        $segments = apply_filters('site_by_path_segments_count', $segments, $domain, $path);

        if(null !== $segments && count($path_segments) > $segments)
        {
            $path_segments = array_slice($path_segments, 0, $segments);
        }

        $paths = [];

        while(count($path_segments))
        {
            $paths[] = '/'.implode('/', $path_segments).'/';
            array_pop($path_segments);
        }

        $paths[] = '/';

        $pre = apply_filters('pre_get_site_by_path', null, $domain, $path, $segments, $paths);
        if(null !== $pre)
        {
            if(false !== $pre && ! $pre instanceof WP_Site)
            {
                $pre = new WP_Site($pre);
            }

            return $pre;
        }

        /*
         * @todo
         * Caching, etc. Consider alternative optimization routes,
         * perhaps as an opt-in for plugins, rather than using the pre_* filter.
         * For example: The segments filter can expand or ignore paths.
         * If persistent caching is enabled, we could query the DB for a path <> '/'
         * then cache whether we can just always ignore paths.
         */

        /*
         * Either www or non-www is supported, not both. If a www domain is requested,
         * query for both to provide the proper redirect.
         */
        $domains = [$domain];
        if(str_starts_with($domain, 'www.'))
        {
            $domains[] = substr($domain, 4);
        }

        $args = [
            'number' => 1,
            'update_site_meta_cache' => false,
        ];

        if(count($domains) > 1)
        {
            $args['domain__in'] = $domains;
            $args['orderby']['domain_length'] = 'DESC';
        }
        else
        {
            $args['domain'] = array_shift($domains);
        }

        if(count($paths) > 1)
        {
            $args['path__in'] = $paths;
            $args['orderby']['path_length'] = 'DESC';
        }
        else
        {
            $args['path'] = array_shift($paths);
        }

        $result = get_sites($args);
        $site = array_shift($result);

        if($site)
        {
            return $site;
        }

        return false;
    }

    function ms_load_current_site_and_network($domain, $path, $subdomain = false)
    {
        global $current_site, $current_blog;

        // If the network is defined in wp-config.php, we can simply use that.
        if(defined('DOMAIN_CURRENT_SITE') && defined('PATH_CURRENT_SITE'))
        {
            $current_site = new stdClass();
            $current_site->id = defined('SITE_ID_CURRENT_SITE') ? SITE_ID_CURRENT_SITE : 1;
            $current_site->domain = DOMAIN_CURRENT_SITE;
            $current_site->path = PATH_CURRENT_SITE;
            if(defined('BLOG_ID_CURRENT_SITE'))
            {
                $current_site->blog_id = BLOG_ID_CURRENT_SITE;
            }
            elseif(defined('BLOGID_CURRENT_SITE'))
            { // Deprecated.
                $current_site->blog_id = BLOGID_CURRENT_SITE;
            }

            if(0 === strcasecmp($current_site->domain, $domain) && 0 === strcasecmp($current_site->path, $path))
            {
                $current_blog = get_site_by_path($domain, $path);
            }
            elseif('/' !== $current_site->path && 0 === strcasecmp($current_site->domain, $domain) && 0 === stripos($path, $current_site->path))
            {
                /*
                 * If the current network has a path and also matches the domain and path of the request,
                 * we need to look for a site using the first path segment following the network's path.
                 */
                $current_blog = get_site_by_path($domain, $path, 1 + count(explode('/', trim($current_site->path, '/'))));
            }
            else
            {
                // Otherwise, use the first path segment (as usual).
                $current_blog = get_site_by_path($domain, $path, 1);
            }
        }
        elseif(! $subdomain)
        {
            /*
             * A "subdomain" installation can be re-interpreted to mean "can support any domain".
             * If we're not dealing with one of these installations, then the important part is determining
             * the network first, because we need the network's path to identify any sites.
             */
            $current_site = wp_cache_get('current_network', 'site-options');
            if(! $current_site)
            {
                // Are there even two networks installed?
                $networks = get_networks(['number' => 2]);
                if(count($networks) === 1)
                {
                    $current_site = array_shift($networks);
                    wp_cache_add('current_network', $current_site, 'site-options');
                }
                elseif(empty($networks))
                {
                    // A network not found hook should fire here.
                    return false;
                }
            }

            if(empty($current_site))
            {
                $current_site = WP_Network::get_by_path($domain, $path, 1);
            }

            if(empty($current_site))
            {
                do_action('ms_network_not_found', $domain, $path);

                return false;
            }
            elseif($path === $current_site->path)
            {
                $current_blog = get_site_by_path($domain, $path);
            }
            else
            {
                // Search the network path + one more path segment (on top of the network path).
                $current_blog = get_site_by_path($domain, $path, substr_count($current_site->path, '/'));
            }
        }
        else
        {
            // Find the site by the domain and at most the first path segment.
            $current_blog = get_site_by_path($domain, $path, 1);
            if($current_blog)
            {
                $current_site = WP_Network::get_instance($current_blog->site_id ? $current_blog->site_id : 1);
            }
            else
            {
                // If you don't have a site with the same domain/path as a network, you're pretty screwed, but:
                $current_site = WP_Network::get_by_path($domain, $path, 1);
            }
        }

        // The network declared by the site trumps any constants.
        if($current_blog && $current_blog->site_id != $current_site->id)
        {
            $current_site = WP_Network::get_instance($current_blog->site_id);
        }

        // No network has been found, bail.
        if(empty($current_site))
        {
            do_action('ms_network_not_found', $domain, $path);

            return false;
        }

        // During activation of a new subdomain, the requested site does not yet exist.
        if(empty($current_blog) && wp_installing())
        {
            $current_blog = new stdClass();
            $current_blog->blog_id = 1;
            $blog_id = 1;
            $current_blog->public = 1;
        }

        // No site has been found, bail.
        if(empty($current_blog))
        {
            // We're going to redirect to the network URL, with some possible modifications.
            $scheme = is_ssl() ? 'https' : 'http';
            $destination = "$scheme://{$current_site->domain}{$current_site->path}";

            do_action('ms_site_not_found', $current_site, $domain, $path);

            if($subdomain && ! defined('NOBLOGREDIRECT'))
            {
                // For a "subdomain" installation, redirect to the signup form specifically.
                $destination .= 'wp-signup.php?new='.str_replace('.'.$current_site->domain, '', $domain);
            }
            elseif($subdomain)
            {
                /*
                 * For a "subdomain" installation, the NOBLOGREDIRECT constant
                 * can be used to avoid a redirect to the signup form.
                 * Using the ms_site_not_found action is preferred to the constant.
                 */
                if('%siteurl%' !== NOBLOGREDIRECT)
                {
                    $destination = NOBLOGREDIRECT;
                }
            }
            elseif(0 === strcasecmp($current_site->domain, $domain))
            {
                /*
                 * If the domain we were searching for matches the network's domain,
                 * it's no use redirecting back to ourselves -- it'll cause a loop.
                 * As we couldn't find a site, we're simply not installed.
                 */
                return false;
            }

            return $destination;
        }

        // Figure out the current network's main site.
        if(empty($current_site->blog_id))
        {
            $current_site->blog_id = get_main_site_id($current_site->id);
        }

        return true;
    }

    function ms_not_installed($domain, $path)
    {
        global $wpdb;

        if(! is_admin())
        {
            dead_db();
        }

        wp_load_translations_early();

        $title = __('Error establishing a database connection');

        $msg = '<h1>'.$title.'</h1>';
        $msg .= '<p>'.__('If your site does not display, please contact the owner of this network.').'';
        $msg .= ' '.__('If you are the owner of this network please check that your host&#8217;s database server is running properly and all tables are error free.').'</p>';
        $query = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($wpdb->site));
        if(! $wpdb->get_var($query))
        {
            $msg .= '<p>'.sprintf(/* translators: %s: Table name. */ __('<strong>Database tables are missing.</strong> This means that your host&#8217;s database server is not running, WordPress was not installed properly, or someone deleted %s. You really should look at your database now.'), '<code>'.$wpdb->site.'</code>').'</p>';
        }
        else
        {
            $msg .= '<p>'.sprintf(/* translators: 1: Site URL, 2: Table name, 3: Database name. */ __('<strong>Could not find site %1$s.</strong> Searched for table %2$s in database %3$s. Is that right?'), '<code>'.rtrim($domain.$path, '/').'</code>', '<code>'.$wpdb->blogs.'</code>', '<code>'.DB_NAME.'</code>').'</p>';
        }
        $msg .= '<p><strong>'.__('What do I do now?').'</strong> ';
        $msg .= sprintf(/* translators: %s: Documentation URL. */ __('Read the <a href="%s" target="_blank">Debugging a WordPress Network</a> article. Some of the suggestions there may help you figure out what went wrong.'), __('https://wordpress.org/documentation/article/debugging-a-wordpress-network/'));
        $msg .= ' '.__('If you are still stuck with this message, then check that your database contains the following tables:').'</p><ul>';
        foreach($wpdb->tables('global') as $t => $table)
        {
            if('sitecategories' === $t)
            {
                continue;
            }
            $msg .= '<li>'.$table.'</li>';
        }
        $msg .= '</ul>';

        wp_die($msg, $title, ['response' => 500]);
    }

    function get_current_site_name($current_site)
    {
        _deprecated_function(__FUNCTION__, '3.9.0', 'get_current_site()');

        return $current_site;
    }

    function wpmu_current_site()
    {
        global $current_site;
        _deprecated_function(__FUNCTION__, '3.9.0');

        return $current_site;
    }

    function wp_get_network($network)
    {
        _deprecated_function(__FUNCTION__, '4.7.0', 'get_network()');

        $network = get_network($network);
        if(null === $network)
        {
            return false;
        }

        return $network;
    }
