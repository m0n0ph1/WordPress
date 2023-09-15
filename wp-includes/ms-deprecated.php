<?php

    /*
     * Deprecated functions come here to die.
     */

    function get_dashboard_blog()
    {
        _deprecated_function(__FUNCTION__, '3.1.0', 'get_site()');
        if($blog = get_site_option('dashboard_blog'))
        {
            return get_site($blog);
        }

        return get_site(get_network()->site_id);
    }

    function generate_random_password($len = 8)
    {
        _deprecated_function(__FUNCTION__, '3.0.0', 'wp_generate_password()');

        return wp_generate_password($len);
    }

    function is_site_admin($user_login = '')
    {
        _deprecated_function(__FUNCTION__, '3.0.0', 'is_super_admin()');

        if(empty($user_login))
        {
            $user_id = get_current_user_id();
            if(! $user_id)
            {
                return false;
            }
        }
        else
        {
            $user = get_user_by('login', $user_login);
            if(! $user->exists())
            {
                return false;
            }
            $user_id = $user->ID;
        }

        return is_super_admin($user_id);
    }

    if(! function_exists('graceful_fail')) :

        function graceful_fail($message)
        {
            _deprecated_function(__FUNCTION__, '3.0.0', 'wp_die()');
            $message = apply_filters('graceful_fail', $message);
            $message_template = apply_filters(
                'graceful_fail_template', '<!DOCTYPE html>
<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Error!</title>
<style type="text/css">
img {
	border: 0;
}
body {
line-height: 1.6em; font-family: Georgia, serif; width: 390px; margin: auto;
text-align: center;
}
.message {
	font-size: 22px;
	width: 350px;
	margin: auto;
}
</style>
</head>
<body>
<p class="message">%s</p>
</body>
</html>'
            );
            die(sprintf($message_template, $message));
        }
    endif;

    function get_user_details($username)
    {
        _deprecated_function(__FUNCTION__, '3.0.0', 'get_user_by()');

        return get_user_by('login', $username);
    }

    function clear_global_post_cache($post_id)
    {
        _deprecated_function(__FUNCTION__, '3.0.0', 'clean_post_cache()');
    }

    function is_main_blog()
    {
        _deprecated_function(__FUNCTION__, '3.0.0', 'is_main_site()');

        return is_main_site();
    }

    function validate_email($email, $check_domain = true)
    {
        _deprecated_function(__FUNCTION__, '3.0.0', 'is_email()');

        return is_email($email, $check_domain);
    }

    function get_blog_list($start = 0, $num = 10, $deprecated = '')
    {
        _deprecated_function(__FUNCTION__, '3.0.0', 'wp_get_sites()');

        global $wpdb;
        $blogs = $wpdb->get_results($wpdb->prepare("SELECT blog_id, domain, path FROM $wpdb->blogs WHERE site_id = %d AND public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0' ORDER BY registered DESC", get_current_network_id()), ARRAY_A);

        $blog_list = [];
        foreach((array) $blogs as $details)
        {
            $blog_list[$details['blog_id']] = $details;
            $blog_list[$details['blog_id']]['postcount'] = $wpdb->get_var("SELECT COUNT(ID) FROM ".$wpdb->get_blog_prefix($details['blog_id'])."posts WHERE post_status='publish' AND post_type='post'");
        }

        if(! $blog_list)
        {
            return [];
        }

        if('all' === $num)
        {
            return array_slice($blog_list, $start, count($blog_list));
        }
        else
        {
            return array_slice($blog_list, $start, $num);
        }
    }

    function get_most_active_blogs($num = 10, $display = true)
    {
        _deprecated_function(__FUNCTION__, '3.0.0');

        $blogs = get_blog_list(0, 'all', false); // $blog_id -> $details
        if(is_array($blogs))
        {
            reset($blogs);
            $most_active = [];
            $blog_list = [];
            foreach((array) $blogs as $key => $details)
            {
                $most_active[$details['blog_id']] = $details['postcount'];
                $blog_list[$details['blog_id']] = $details; // array_slice() removes keys!
            }
            arsort($most_active);
            reset($most_active);
            $t = [];
            foreach((array) $most_active as $key => $details)
            {
                $t[$key] = $blog_list[$key];
            }
            unset($most_active);
            $most_active = $t;
        }

        if($display && is_array($most_active))
        {
            reset($most_active);
            foreach((array) $most_active as $key => $details)
            {
                $url = esc_url('http://'.$details['domain'].$details['path']);
                echo '<li>'.$details['postcount']." <a href='$url'>$url</a></li>";
            }
        }

        return array_slice($most_active, 0, $num);
    }

    function wpmu_admin_do_redirect($url = '')
    {
        _deprecated_function(__FUNCTION__, '3.3.0', 'wp_redirect()');

        $ref = '';
        if(isset($_GET['ref']) && isset($_POST['ref']) && $_GET['ref'] !== $_POST['ref'])
        {
            wp_die(__('A variable mismatch has been detected.'), __('Sorry, you are not allowed to view this item.'), 400);
        }
        elseif(isset($_POST['ref']))
        {
            $ref = $_POST['ref'];
        }
        elseif(isset($_GET['ref']))
        {
            $ref = $_GET['ref'];
        }

        if($ref)
        {
            $ref = wpmu_admin_redirect_add_updated_param($ref);
            wp_redirect($ref);
            exit;
        }
        if(! empty($_SERVER['HTTP_REFERER']))
        {
            wp_redirect($_SERVER['HTTP_REFERER']);
            exit;
        }

        $url = wpmu_admin_redirect_add_updated_param($url);
        if(isset($_GET['redirect']) && isset($_POST['redirect']) && $_GET['redirect'] !== $_POST['redirect'])
        {
            wp_die(__('A variable mismatch has been detected.'), __('Sorry, you are not allowed to view this item.'), 400);
        }
        elseif(isset($_GET['redirect']))
        {
            if(str_starts_with($_GET['redirect'], 's_'))
            {
                $url .= '&action=blogs&s='.esc_html(substr($_GET['redirect'], 2));
            }
        }
        elseif(isset($_POST['redirect']))
        {
            $url = wpmu_admin_redirect_add_updated_param($_POST['redirect']);
        }
        wp_redirect($url);
        exit;
    }

    function wpmu_admin_redirect_add_updated_param($url = '')
    {
        _deprecated_function(__FUNCTION__, '3.3.0', 'add_query_arg()');

        if(! str_contains($url, 'updated=true'))
        {
            if(str_contains($url, '?'))
            {
                return $url.'&updated=true';
            }
            else
            {
                return $url.'?updated=true';
            }
        }

        return $url;
    }

    function get_user_id_from_string($email_or_login)
    {
        _deprecated_function(__FUNCTION__, '3.6.0', 'get_user_by()');

        if(is_email($email_or_login))
        {
            $user = get_user_by('email', $email_or_login);
        }
        elseif(is_numeric($email_or_login))
        {
            return $email_or_login;
        }
        else
        {
            $user = get_user_by('login', $email_or_login);
        }

        if($user)
        {
            return $user->ID;
        }

        return 0;
    }

    function get_blogaddress_by_domain($domain, $path)
    {
        _deprecated_function(__FUNCTION__, '3.7.0');

        if(is_subdomain_install())
        {
            $url = "http://".$domain.$path;
        }
        else
        {
            if($domain != $_SERVER['HTTP_HOST'])
            {
                $blogname = substr($domain, 0, strpos($domain, '.'));
                $url = 'http://'.substr($domain, strpos($domain, '.') + 1).$path;
                // We're not installing the main blog.
                if('www.' !== $blogname)
                {
                    $url .= $blogname.'/';
                }
            }
            else
            { // Main blog.
                $url = 'http://'.$domain.$path;
            }
        }

        return sanitize_url($url);
    }

    function create_empty_blog($domain, $path, $weblog_title, $site_id = 1)
    {
        _deprecated_function(__FUNCTION__, '4.4.0');

        if(empty($path))
        {
            $path = '/';
        }

        // Check if the domain has been used already. We should return an error message.
        if(domain_exists($domain, $path, $site_id))
        {
            return __('<strong>Error:</strong> Site URL you&#8217;ve entered is already taken.');
        }

        /*
         * Need to back up wpdb table names, and create a new wp_blogs entry for new blog.
         * Need to get blog_id from wp_blogs, and create new table names.
         * Must restore table names at the end of function.
         */

        if(! $blog_id = insert_blog($domain, $path, $site_id))
        {
            return __('<strong>Error:</strong> There was a problem creating site entry.');
        }

        switch_to_blog($blog_id);
        install_blog($blog_id);
        restore_current_blog();

        return $blog_id;
    }

    function get_admin_users_for_domain($domain = '', $path = '')
    {
        _deprecated_function(__FUNCTION__, '4.4.0');

        global $wpdb;

        if($domain)
        {
            $_networks = get_networks([
                                          'fields' => 'ids',
                                          'number' => 1,
                                          'domain' => $domain,
                                          'path' => $path,
                                      ]);
            $network_id = ! empty($_networks) ? array_shift($_networks) : 0;
        }
        else
        {
            $network_id = get_current_network_id();
        }

        if($network_id)
        {
            return $wpdb->get_results($wpdb->prepare("SELECT u.ID, u.user_login, u.user_pass FROM $wpdb->users AS u, $wpdb->sitemeta AS sm WHERE sm.meta_key = 'admin_user_id' AND u.ID = sm.meta_value AND sm.site_id = %d", $network_id), ARRAY_A);
        }

        return false;
    }

    function wp_get_sites($args = [])
    {
        _deprecated_function(__FUNCTION__, '4.6.0', 'get_sites()');

        if(wp_is_large_network())
        {
            return [];
        }

        $defaults = [
            'network_id' => get_current_network_id(),
            'public' => null,
            'archived' => null,
            'mature' => null,
            'spam' => null,
            'deleted' => null,
            'limit' => 100,
            'offset' => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        // Backward compatibility.
        if(is_array($args['network_id']))
        {
            $args['network__in'] = $args['network_id'];
            $args['network_id'] = null;
        }

        if(is_numeric($args['limit']))
        {
            $args['number'] = $args['limit'];
            $args['limit'] = null;
        }
        elseif(! $args['limit'])
        {
            $args['number'] = 0;
            $args['limit'] = null;
        }

        // Make sure count is disabled.
        $args['count'] = false;

        $_sites = get_sites($args);

        $results = [];

        foreach($_sites as $_site)
        {
            $_site = get_site($_site);
            $results[] = $_site->to_array();
        }

        return $results;
    }

    function is_user_option_local($key, $user_id = 0, $blog_id = 0)
    {
        global $wpdb;

        _deprecated_function(__FUNCTION__, '4.9.0');

        $current_user = wp_get_current_user();
        if($blog_id == 0)
        {
            $blog_id = get_current_blog_id();
        }
        $local_key = $wpdb->get_blog_prefix($blog_id).$key;

        return isset($current_user->$local_key);
    }

    function insert_blog($domain, $path, $site_id)
    {
        _deprecated_function(__FUNCTION__, '5.1.0', 'wp_insert_site()');

        $data = compact('domain', 'path', 'site_id');

        $site_id = wp_insert_site($data);
        if(is_wp_error($site_id))
        {
            return false;
        }

        clean_blog_cache($site_id);

        return $site_id;
    }

    function install_blog($blog_id, $blog_title = '')
    {
        global $wpdb, $wp_roles;

        _deprecated_function(__FUNCTION__, '5.1.0');

        // Cast for security.
        $blog_id = (int) $blog_id;

        require_once ABSPATH.'wp-admin/includes/upgrade.php';

        $suppress = $wpdb->suppress_errors();
        if($wpdb->get_results("DESCRIBE {$wpdb->posts}"))
        {
            die('<h1>'.__('Already Installed').'</h1><p>'.__('You appear to have already installed WordPress. To reinstall please clear your old database tables first.').'</p></body></html>');
        }
        $wpdb->suppress_errors($suppress);

        $url = get_blogaddress_by_id($blog_id);

        // Set everything up.
        make_db_current_silent('blog');
        populate_options();
        populate_roles();

        // populate_roles() clears previous role definitions so we start over.
        $wp_roles = new WP_Roles();

        $home = untrailingslashit($url);
        $siteurl = $home;

        if(! is_subdomain_install())
        {
            if('https' === parse_url(get_site_option('siteurl'), PHP_URL_SCHEME))
            {
                $siteurl = set_url_scheme($siteurl, 'https');
            }
            if('https' === parse_url(get_home_url(get_network()->site_id), PHP_URL_SCHEME))
            {
                $home = set_url_scheme($home, 'https');
            }
        }

        update_option('siteurl', $siteurl);
        update_option('home', $home);

        if(get_site_option('ms_files_rewriting'))
        {
            update_option('upload_path', UPLOADBLOGSDIR."/$blog_id/files");
        }
        else
        {
            update_option('upload_path', get_blog_option(get_network()->site_id, 'upload_path'));
        }

        update_option('blogname', wp_unslash($blog_title));
        update_option('admin_email', '');

        // Remove all permissions.
        $table_prefix = $wpdb->get_blog_prefix();
        delete_metadata('user', 0, $table_prefix.'user_level', null, true);   // Delete all.
        delete_metadata('user', 0, $table_prefix.'capabilities', null, true); // Delete all.
    }

    function install_blog_defaults($blog_id, $user_id)
    {
        global $wpdb;

        _deprecated_function(__FUNCTION__, 'MU');

        require_once ABSPATH.'wp-admin/includes/upgrade.php';

        $suppress = $wpdb->suppress_errors();

        wp_install_defaults($user_id);

        $wpdb->suppress_errors($suppress);
    }

    function update_user_status($id, $pref, $value, $deprecated = null)
    {
        global $wpdb;

        _deprecated_function(__FUNCTION__, '5.3.0', 'wp_update_user()');

        if(null !== $deprecated)
        {
            _deprecated_argument(__FUNCTION__, '3.0.2');
        }

        $wpdb->update($wpdb->users, [sanitize_key($pref) => $value], ['ID' => $id]);

        $user = new WP_User($id);
        clean_user_cache($user);

        if('spam' === $pref)
        {
            if($value == 1)
            {
                do_action('make_spam_user', $id);
            }
            else
            {
                do_action('make_ham_user', $id);
            }
        }

        return $value;
    }

    function global_terms($term_id, $deprecated = '')
    {
        _deprecated_function(__FUNCTION__, '6.1.0');

        return $term_id;
    }
