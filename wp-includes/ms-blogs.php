<?php

    require_once ABSPATH.WPINC.'/ms-site.php';
    require_once ABSPATH.WPINC.'/ms-network.php';

    function wpmu_update_blogs_date()
    {
        $site_id = get_current_blog_id();

        update_blog_details($site_id, ['last_updated' => current_time('mysql', true)]);

        do_action('wpmu_blog_updated', $site_id);
    }

    function get_blogaddress_by_id($blog_id)
    {
        $bloginfo = get_site((int) $blog_id);

        if(empty($bloginfo))
        {
            return '';
        }

        $scheme = parse_url($bloginfo->home, PHP_URL_SCHEME);
        $scheme = empty($scheme) ? 'http' : $scheme;

        return esc_url($scheme.'://'.$bloginfo->domain.$bloginfo->path);
    }

    function get_blogaddress_by_name($blogname)
    {
        if(is_subdomain_install())
        {
            if('main' === $blogname)
            {
                $blogname = 'www';
            }
            $url = rtrim(network_home_url(), '/');
            if(! empty($blogname))
            {
                $url = preg_replace('|^([^\.]+://)|', '${1}'.$blogname.'.', $url);
            }
        }
        else
        {
            $url = network_home_url($blogname);
        }

        return esc_url($url.'/');
    }

    function get_id_from_blogname($slug)
    {
        $current_network = get_network();
        $slug = trim($slug, '/');

        if(is_subdomain_install())
        {
            $domain = $slug.'.'.preg_replace('|^www\.|', '', $current_network->domain);
            $path = $current_network->path;
        }
        else
        {
            $domain = $current_network->domain;
            $path = $current_network->path.$slug.'/';
        }

        $site_ids = get_sites([
                                  'number' => 1,
                                  'fields' => 'ids',
                                  'domain' => $domain,
                                  'path' => $path,
                                  'update_site_meta_cache' => false,
                              ]);

        if(empty($site_ids))
        {
            return null;
        }

        return array_shift($site_ids);
    }

    function get_blog_details($fields = null, $get_all = true)
    {
        global $wpdb;

        if(is_array($fields))
        {
            if(isset($fields['blog_id']))
            {
                $blog_id = $fields['blog_id'];
            }
            elseif(isset($fields['domain']) && isset($fields['path']))
            {
                $key = md5($fields['domain'].$fields['path']);
                $blog = wp_cache_get($key, 'blog-lookup');
                if(false !== $blog)
                {
                    return $blog;
                }
                if(str_starts_with($fields['domain'], 'www.'))
                {
                    $nowww = substr($fields['domain'], 4);
                    $blog = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->blogs WHERE domain IN (%s,%s) AND path = %s ORDER BY CHAR_LENGTH(domain) DESC", $nowww, $fields['domain'], $fields['path']));
                }
                else
                {
                    $blog = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->blogs WHERE domain = %s AND path = %s", $fields['domain'], $fields['path']));
                }
                if($blog)
                {
                    wp_cache_set($blog->blog_id.'short', $blog, 'blog-details');
                    $blog_id = $blog->blog_id;
                }
                else
                {
                    return false;
                }
            }
            elseif(isset($fields['domain']) && is_subdomain_install())
            {
                $key = md5($fields['domain']);
                $blog = wp_cache_get($key, 'blog-lookup');
                if(false !== $blog)
                {
                    return $blog;
                }
                if(str_starts_with($fields['domain'], 'www.'))
                {
                    $nowww = substr($fields['domain'], 4);
                    $blog = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->blogs WHERE domain IN (%s,%s) ORDER BY CHAR_LENGTH(domain) DESC", $nowww, $fields['domain']));
                }
                else
                {
                    $blog = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->blogs WHERE domain = %s", $fields['domain']));
                }
                if($blog)
                {
                    wp_cache_set($blog->blog_id.'short', $blog, 'blog-details');
                    $blog_id = $blog->blog_id;
                }
                else
                {
                    return false;
                }
            }
            else
            {
                return false;
            }
        }
        else
        {
            if(! $fields)
            {
                $blog_id = get_current_blog_id();
            }
            elseif(is_numeric($fields))
            {
                $blog_id = $fields;
            }
            else
            {
                $blog_id = get_id_from_blogname($fields);
            }
        }

        $blog_id = (int) $blog_id;

        $all = $get_all ? '' : 'short';
        $details = wp_cache_get($blog_id.$all, 'blog-details');

        if($details)
        {
            if(is_object($details))
            {
                return $details;
            }
            else
            {
                if(-1 == $details)
                {
                    return false;
                }
                else
                {
                    // Clear old pre-serialized objects. Cache clients do better with that.
                    wp_cache_delete($blog_id.$all, 'blog-details');
                    unset($details);
                }
            }
        }

        // Try the other cache.
        if($get_all)
        {
            $details = wp_cache_get($blog_id.'short', 'blog-details');
        }
        else
        {
            $details = wp_cache_get($blog_id, 'blog-details');
            // If short was requested and full cache is set, we can return.
            if($details)
            {
                if(is_object($details))
                {
                    return $details;
                }
                else
                {
                    if(-1 == $details)
                    {
                        return false;
                    }
                    else
                    {
                        // Clear old pre-serialized objects. Cache clients do better with that.
                        wp_cache_delete($blog_id, 'blog-details');
                        unset($details);
                    }
                }
            }
        }

        if(empty($details))
        {
            $details = WP_Site::get_instance($blog_id);
            if(! $details)
            {
                // Set the full cache.
                wp_cache_set($blog_id, -1, 'blog-details');

                return false;
            }
        }

        if(! $details instanceof WP_Site)
        {
            $details = new WP_Site($details);
        }

        if(! $get_all)
        {
            wp_cache_set($blog_id.$all, $details, 'blog-details');

            return $details;
        }

        $switched_blog = false;

        if(get_current_blog_id() !== $blog_id)
        {
            switch_to_blog($blog_id);
            $switched_blog = true;
        }

        $details->blogname = get_option('blogname');
        $details->siteurl = get_option('siteurl');
        $details->post_count = get_option('post_count');
        $details->home = get_option('home');

        if($switched_blog)
        {
            restore_current_blog();
        }

        $details = apply_filters_deprecated('blog_details', [$details], '4.7.0', 'site_details');

        wp_cache_set($blog_id.$all, $details, 'blog-details');

        $key = md5($details->domain.$details->path);
        wp_cache_set($key, $details, 'blog-lookup');

        return $details;
    }

    function refresh_blog_details($blog_id = 0)
    {
        $blog_id = (int) $blog_id;
        if(! $blog_id)
        {
            $blog_id = get_current_blog_id();
        }

        clean_blog_cache($blog_id);
    }

    function update_blog_details($blog_id, $details = [])
    {
        global $wpdb;

        if(empty($details))
        {
            return false;
        }

        if(is_object($details))
        {
            $details = get_object_vars($details);
        }

        $site = wp_update_site($blog_id, $details);

        if(is_wp_error($site))
        {
            return false;
        }

        return true;
    }

    function clean_site_details_cache($site_id = 0)
    {
        $site_id = (int) $site_id;
        if(! $site_id)
        {
            $site_id = get_current_blog_id();
        }

        wp_cache_delete($site_id, 'site-details');
        wp_cache_delete($site_id, 'blog-details');
    }

    function get_blog_option($id, $option, $default_value = false)
    {
        $id = (int) $id;

        if(empty($id))
        {
            $id = get_current_blog_id();
        }

        if(get_current_blog_id() == $id)
        {
            return get_option($option, $default_value);
        }

        switch_to_blog($id);
        $value = get_option($option, $default_value);
        restore_current_blog();

        return apply_filters("blog_option_{$option}", $value, $id);
    }

    function add_blog_option($id, $option, $value)
    {
        $id = (int) $id;

        if(empty($id))
        {
            $id = get_current_blog_id();
        }

        if(get_current_blog_id() == $id)
        {
            return add_option($option, $value);
        }

        switch_to_blog($id);
        $return = add_option($option, $value);
        restore_current_blog();

        return $return;
    }

    function delete_blog_option($id, $option)
    {
        $id = (int) $id;

        if(empty($id))
        {
            $id = get_current_blog_id();
        }

        if(get_current_blog_id() == $id)
        {
            return delete_option($option);
        }

        switch_to_blog($id);
        $return = delete_option($option);
        restore_current_blog();

        return $return;
    }

    function update_blog_option($id, $option, $value, $deprecated = null)
    {
        $id = (int) $id;

        if(null !== $deprecated)
        {
            _deprecated_argument(__FUNCTION__, '3.1.0');
        }

        if(get_current_blog_id() == $id)
        {
            return update_option($option, $value);
        }

        switch_to_blog($id);
        $return = update_option($option, $value);
        restore_current_blog();

        return $return;
    }

    function switch_to_blog($new_blog_id, $deprecated = null)
    {
        global $wpdb;

        $prev_blog_id = get_current_blog_id();
        if(empty($new_blog_id))
        {
            $new_blog_id = $prev_blog_id;
        }

        $GLOBALS['_wp_switched_stack'][] = $prev_blog_id;

        /*
         * If we're switching to the same blog id that we're on,
         * set the right vars, do the associated actions, but skip
         * the extra unnecessary work
         */
        if($new_blog_id == $prev_blog_id)
        {
            do_action('switch_blog', $new_blog_id, $prev_blog_id, 'switch');

            $GLOBALS['switched'] = true;

            return true;
        }

        $wpdb->set_blog_id($new_blog_id);
        $GLOBALS['table_prefix'] = $wpdb->get_blog_prefix();
        $GLOBALS['blog_id'] = $new_blog_id;

        if(function_exists('wp_cache_switch_to_blog'))
        {
            wp_cache_switch_to_blog($new_blog_id);
        }
        else
        {
            global $wp_object_cache;

            if(is_object($wp_object_cache) && isset($wp_object_cache->global_groups))
            {
                $global_groups = $wp_object_cache->global_groups;
            }
            else
            {
                $global_groups = false;
            }

            wp_cache_init();

            if(function_exists('wp_cache_add_global_groups'))
            {
                if(is_array($global_groups))
                {
                    wp_cache_add_global_groups($global_groups);
                }
                else
                {
                    wp_cache_add_global_groups([
                                                   'blog-details',
                                                   'blog-id-cache',
                                                   'blog-lookup',
                                                   'blog_meta',
                                                   'global-posts',
                                                   'networks',
                                                   'network-queries',
                                                   'sites',
                                                   'site-details',
                                                   'site-options',
                                                   'site-queries',
                                                   'site-transient',
                                                   'rss',
                                                   'users',
                                                   'user-queries',
                                                   'user_meta',
                                                   'useremail',
                                                   'userlogins',
                                                   'userslugs',
                                               ]);
                }

                wp_cache_add_non_persistent_groups(['counts', 'plugins', 'theme_json']);
            }
        }

        do_action('switch_blog', $new_blog_id, $prev_blog_id, 'switch');

        $GLOBALS['switched'] = true;

        return true;
    }

    function restore_current_blog()
    {
        global $wpdb;

        if(empty($GLOBALS['_wp_switched_stack']))
        {
            return false;
        }

        $new_blog_id = array_pop($GLOBALS['_wp_switched_stack']);
        $prev_blog_id = get_current_blog_id();

        if($new_blog_id == $prev_blog_id)
        {
            do_action('switch_blog', $new_blog_id, $prev_blog_id, 'restore');

            // If we still have items in the switched stack, consider ourselves still 'switched'.
            $GLOBALS['switched'] = ! empty($GLOBALS['_wp_switched_stack']);

            return true;
        }

        $wpdb->set_blog_id($new_blog_id);
        $GLOBALS['blog_id'] = $new_blog_id;
        $GLOBALS['table_prefix'] = $wpdb->get_blog_prefix();

        if(function_exists('wp_cache_switch_to_blog'))
        {
            wp_cache_switch_to_blog($new_blog_id);
        }
        else
        {
            global $wp_object_cache;

            if(is_object($wp_object_cache) && isset($wp_object_cache->global_groups))
            {
                $global_groups = $wp_object_cache->global_groups;
            }
            else
            {
                $global_groups = false;
            }

            wp_cache_init();

            if(function_exists('wp_cache_add_global_groups'))
            {
                if(is_array($global_groups))
                {
                    wp_cache_add_global_groups($global_groups);
                }
                else
                {
                    wp_cache_add_global_groups([
                                                   'blog-details',
                                                   'blog-id-cache',
                                                   'blog-lookup',
                                                   'blog_meta',
                                                   'global-posts',
                                                   'networks',
                                                   'network-queries',
                                                   'sites',
                                                   'site-details',
                                                   'site-options',
                                                   'site-queries',
                                                   'site-transient',
                                                   'rss',
                                                   'users',
                                                   'user-queries',
                                                   'user_meta',
                                                   'useremail',
                                                   'userlogins',
                                                   'userslugs',
                                               ]);
                }

                wp_cache_add_non_persistent_groups(['counts', 'plugins', 'theme_json']);
            }
        }

        do_action('switch_blog', $new_blog_id, $prev_blog_id, 'restore');

        // If we still have items in the switched stack, consider ourselves still 'switched'.
        $GLOBALS['switched'] = ! empty($GLOBALS['_wp_switched_stack']);

        return true;
    }

    function wp_switch_roles_and_user($new_site_id, $old_site_id)
    {
        if($new_site_id == $old_site_id || ! did_action('init'))
        {
            return;
        }

        wp_roles()->for_site($new_site_id);
        wp_get_current_user()->for_site($new_site_id);
    }

    function ms_is_switched()
    {
        return ! empty($GLOBALS['_wp_switched_stack']);
    }

    function is_archived($id)
    {
        return get_blog_status($id, 'archived');
    }

    function update_archived($id, $archived)
    {
        update_blog_status($id, 'archived', $archived);

        return $archived;
    }

    function update_blog_status($blog_id, $pref, $value, $deprecated = null)
    {
        global $wpdb;

        if(null !== $deprecated)
        {
            _deprecated_argument(__FUNCTION__, '3.1.0');
        }

        $allowed_field_names = [
            'site_id',
            'domain',
            'path',
            'registered',
            'last_updated',
            'public',
            'archived',
            'mature',
            'spam',
            'deleted',
            'lang_id'
        ];

        if(! in_array($pref, $allowed_field_names, true))
        {
            return $value;
        }

        $result = wp_update_site($blog_id, [
            $pref => $value,
        ]);

        if(is_wp_error($result))
        {
            return false;
        }

        return $value;
    }

    function get_blog_status($id, $pref)
    {
        global $wpdb;

        $details = get_site($id);
        if($details)
        {
            return $details->$pref;
        }

        return $wpdb->get_var($wpdb->prepare("SELECT %s FROM {$wpdb->blogs} WHERE blog_id = %d", $pref, $id));
    }

    function get_last_updated($deprecated = '', $start = 0, $quantity = 40)
    {
        global $wpdb;

        if(! empty($deprecated))
        {
            _deprecated_argument(__FUNCTION__, 'MU'); // Never used.
        }

        return $wpdb->get_results($wpdb->prepare("SELECT blog_id, domain, path FROM $wpdb->blogs WHERE site_id = %d AND public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0' AND last_updated != '0000-00-00 00:00:00' ORDER BY last_updated DESC limit %d, %d", get_current_network_id(), $start, $quantity), ARRAY_A);
    }

    function _update_blog_date_on_post_publish($new_status, $old_status, $post)
    {
        $post_type_obj = get_post_type_object($post->post_type);
        if(! $post_type_obj || ! $post_type_obj->public)
        {
            return;
        }

        if('publish' !== $new_status && 'publish' !== $old_status)
        {
            return;
        }

        // Post was freshly published, published post was saved, or published post was unpublished.

        wpmu_update_blogs_date();
    }

    function _update_blog_date_on_post_delete($post_id)
    {
        $post = get_post($post_id);

        $post_type_obj = get_post_type_object($post->post_type);
        if(! $post_type_obj || ! $post_type_obj->public || 'publish' !== $post->post_status)
        {
            return;
        }

        wpmu_update_blogs_date();
    }

    function _update_posts_count_on_delete($post_id, $post)
    {
        if(! $post || 'publish' !== $post->post_status || 'post' !== $post->post_type)
        {
            return;
        }

        update_posts_count();
    }

    function _update_posts_count_on_transition_post_status($new_status, $old_status, $post = null)
    {
        if($new_status === $old_status || 'post' !== get_post_type($post))
        {
            return;
        }

        if('publish' !== $new_status && 'publish' !== $old_status)
        {
            return;
        }

        update_posts_count();
    }

    function wp_count_sites($network_id = null)
    {
        if(empty($network_id))
        {
            $network_id = get_current_network_id();
        }

        $counts = [];
        $args = [
            'network_id' => $network_id,
            'number' => 1,
            'fields' => 'ids',
            'no_found_rows' => false,
        ];

        $q = new WP_Site_Query($args);
        $counts['all'] = $q->found_sites;

        $_args = $args;
        $statuses = ['public', 'archived', 'mature', 'spam', 'deleted'];

        foreach($statuses as $status)
        {
            $_args = $args;
            $_args[$status] = 1;

            $q = new WP_Site_Query($_args);
            $counts[$status] = $q->found_sites;
        }

        return $counts;
    }
