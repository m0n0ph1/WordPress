<?php

    function get_sitestats()
    {
        $stats = [
            'blogs' => get_blog_count(),
            'users' => get_user_count(),
        ];

        return $stats;
    }

    function get_active_blog_for_user($user_id)
    {
        $blogs = get_blogs_of_user($user_id);
        if(empty($blogs))
        {
            return;
        }

        if(! is_multisite())
        {
            return $blogs[get_current_blog_id()];
        }

        $primary_blog = get_user_meta($user_id, 'primary_blog', true);
        $first_blog = current($blogs);
        if(false !== $primary_blog)
        {
            if(isset($blogs[$primary_blog]))
            {
                $primary = get_site($primary_blog);
            }
            else
            {
                update_user_meta($user_id, 'primary_blog', $first_blog->userblog_id);
                $primary = get_site($first_blog->userblog_id);
            }
        }
        else
        {
            // TODO: Review this call to add_user_to_blog too - to get here the user must have a role on this blog?
            $result = add_user_to_blog($first_blog->userblog_id, $user_id, 'subscriber');

            if(! is_wp_error($result))
            {
                update_user_meta($user_id, 'primary_blog', $first_blog->userblog_id);
                $primary = $first_blog;
            }
        }

        if((! is_object($primary)) || (1 == $primary->archived || 1 == $primary->spam || 1 == $primary->deleted))
        {
            $blogs = get_blogs_of_user($user_id, true); // If a user's primary blog is shut down, check their other blogs.
            $ret = false;
            if(is_array($blogs) && count($blogs) > 0)
            {
                foreach((array) $blogs as $blog_id => $blog)
                {
                    if(get_current_network_id() != $blog->site_id)
                    {
                        continue;
                    }
                    $details = get_site($blog_id);
                    if(is_object($details) && 0 == $details->archived && 0 == $details->spam && 0 == $details->deleted)
                    {
                        $ret = $details;
                        if(get_user_meta($user_id, 'primary_blog', true) != $blog_id)
                        {
                            update_user_meta($user_id, 'primary_blog', $blog_id);
                        }
                        if(! get_user_meta($user_id, 'source_domain', true))
                        {
                            update_user_meta($user_id, 'source_domain', $details->domain);
                        }
                        break;
                    }
                }
            }
            else
            {
                return;
            }

            return $ret;
        }
        else
        {
            return $primary;
        }
    }

    function get_blog_count($network_id = null)
    {
        return get_network_option($network_id, 'blog_count');
    }

    function get_blog_post($blog_id, $post_id)
    {
        switch_to_blog($blog_id);
        $post = get_post($post_id);
        restore_current_blog();

        return $post;
    }

    function add_user_to_blog($blog_id, $user_id, $role)
    {
        switch_to_blog($blog_id);

        $user = get_userdata($user_id);

        if(! $user)
        {
            restore_current_blog();

            return new WP_Error('user_does_not_exist', __('The requested user does not exist.'));
        }

        $can_add_user = apply_filters('can_add_user_to_blog', true, $user_id, $role, $blog_id);

        if(true !== $can_add_user)
        {
            restore_current_blog();

            if(is_wp_error($can_add_user))
            {
                return $can_add_user;
            }

            return new WP_Error('user_cannot_be_added', __('User cannot be added to this site.'));
        }

        if(! get_user_meta($user_id, 'primary_blog', true))
        {
            update_user_meta($user_id, 'primary_blog', $blog_id);
            $site = get_site($blog_id);
            update_user_meta($user_id, 'source_domain', $site->domain);
        }

        $user->set_role($role);

        do_action('add_user_to_blog', $user_id, $role, $blog_id);

        clean_user_cache($user_id);
        wp_cache_delete($blog_id.'_user_count', 'blog-details');

        restore_current_blog();

        return true;
    }

    function remove_user_from_blog($user_id, $blog_id = 0, $reassign = 0)
    {
        global $wpdb;

        switch_to_blog($blog_id);
        $user_id = (int) $user_id;

        do_action('remove_user_from_blog', $user_id, $blog_id, $reassign);

        /*
	 * If being removed from the primary blog, set a new primary
	 * if the user is assigned to multiple blogs.
	 */
        $primary_blog = get_user_meta($user_id, 'primary_blog', true);
        if($primary_blog == $blog_id)
        {
            $new_id = '';
            $new_domain = '';
            $blogs = get_blogs_of_user($user_id);
            foreach((array) $blogs as $blog)
            {
                if($blog->userblog_id == $blog_id)
                {
                    continue;
                }
                $new_id = $blog->userblog_id;
                $new_domain = $blog->domain;
                break;
            }

            update_user_meta($user_id, 'primary_blog', $new_id);
            update_user_meta($user_id, 'source_domain', $new_domain);
        }

        $user = get_userdata($user_id);
        if(! $user)
        {
            restore_current_blog();

            return new WP_Error('user_does_not_exist', __('That user does not exist.'));
        }

        $user->remove_all_caps();

        $blogs = get_blogs_of_user($user_id);
        if(count($blogs) === 0)
        {
            update_user_meta($user_id, 'primary_blog', '');
            update_user_meta($user_id, 'source_domain', '');
        }

        if($reassign)
        {
            $reassign = (int) $reassign;
            $post_ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_author = %d", $user_id));
            $link_ids = $wpdb->get_col($wpdb->prepare("SELECT link_id FROM $wpdb->links WHERE link_owner = %d", $user_id));

            if(! empty($post_ids))
            {
                $wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET post_author = %d WHERE post_author = %d", $reassign, $user_id));
                array_walk($post_ids, 'clean_post_cache');
            }

            if(! empty($link_ids))
            {
                $wpdb->query($wpdb->prepare("UPDATE $wpdb->links SET link_owner = %d WHERE link_owner = %d", $reassign, $user_id));
                array_walk($link_ids, 'clean_bookmark_cache');
            }
        }

        clean_user_cache($user_id);
        restore_current_blog();

        return true;
    }

    function get_blog_permalink($blog_id, $post_id)
    {
        switch_to_blog($blog_id);
        $link = get_permalink($post_id);
        restore_current_blog();

        return $link;
    }

    function get_blog_id_from_url($domain, $path = '/')
    {
        $domain = strtolower($domain);
        $path = strtolower($path);
        $id = wp_cache_get(md5($domain.$path), 'blog-id-cache');

        if(-1 == $id)
        { // Blog does not exist.
            return 0;
        }
        elseif($id)
        {
            return (int) $id;
        }

        $args = [
            'domain' => $domain,
            'path' => $path,
            'fields' => 'ids',
            'number' => 1,
            'update_site_meta_cache' => false,
        ];
        $result = get_sites($args);
        $id = array_shift($result);

        if(! $id)
        {
            wp_cache_set(md5($domain.$path), -1, 'blog-id-cache');

            return 0;
        }

        wp_cache_set(md5($domain.$path), $id, 'blog-id-cache');

        return $id;
    }

//
// Admin functions.
//

    function is_email_address_unsafe($user_email)
    {
        $banned_names = get_site_option('banned_email_domains');
        if($banned_names && ! is_array($banned_names))
        {
            $banned_names = explode("\n", $banned_names);
        }

        $is_email_address_unsafe = false;

        if($banned_names && is_array($banned_names) && false !== strpos($user_email, '@', 1))
        {
            $banned_names = array_map('strtolower', $banned_names);
            $normalized_email = strtolower($user_email);

            [$email_local_part, $email_domain] = explode('@', $normalized_email);

            foreach($banned_names as $banned_domain)
            {
                if(! $banned_domain)
                {
                    continue;
                }

                if($email_domain === $banned_domain)
                {
                    $is_email_address_unsafe = true;
                    break;
                }

                if(str_ends_with($normalized_email, ".$banned_domain"))
                {
                    $is_email_address_unsafe = true;
                    break;
                }
            }
        }

        return apply_filters('is_email_address_unsafe', $is_email_address_unsafe, $user_email);
    }

    function wpmu_validate_user_signup($user_name, $user_email)
    {
        global $wpdb;

        $errors = new WP_Error();

        $orig_username = $user_name;
        $user_name = preg_replace('/\s+/', '', sanitize_user($user_name, true));

        if($user_name != $orig_username || preg_match('/[^a-z0-9]/', $user_name))
        {
            $errors->add('user_name', __('Usernames can only contain lowercase letters (a-z) and numbers.'));
            $user_name = $orig_username;
        }

        $user_email = sanitize_email($user_email);

        if(empty($user_name))
        {
            $errors->add('user_name', __('Please enter a username.'));
        }

        $illegal_names = get_site_option('illegal_names');
        if(! is_array($illegal_names))
        {
            $illegal_names = ['www', 'web', 'root', 'admin', 'main', 'invite', 'administrator'];
            add_site_option('illegal_names', $illegal_names);
        }
        if(in_array($user_name, $illegal_names, true))
        {
            $errors->add('user_name', __('Sorry, that username is not allowed.'));
        }

        $illegal_logins = (array) apply_filters('illegal_user_logins', []);

        if(in_array(strtolower($user_name), array_map('strtolower', $illegal_logins), true))
        {
            $errors->add('user_name', __('Sorry, that username is not allowed.'));
        }

        if(! is_email($user_email))
        {
            $errors->add('user_email', __('Please enter a valid email address.'));
        }
        elseif(is_email_address_unsafe($user_email))
        {
            $errors->add('user_email', __('You cannot use that email address to signup. There are problems with them blocking some emails from WordPress. Please use another email provider.'));
        }

        if(strlen($user_name) < 4)
        {
            $errors->add('user_name', __('Username must be at least 4 characters.'));
        }

        if(strlen($user_name) > 60)
        {
            $errors->add('user_name', __('Username may not be longer than 60 characters.'));
        }

        // All numeric?
        if(preg_match('/^[0-9]*$/', $user_name))
        {
            $errors->add('user_name', __('Sorry, usernames must have letters too!'));
        }

        $limited_email_domains = get_site_option('limited_email_domains');
        if(is_array($limited_email_domains) && ! empty($limited_email_domains))
        {
            $limited_email_domains = array_map('strtolower', $limited_email_domains);
            $emaildomain = strtolower(substr($user_email, 1 + strpos($user_email, '@')));
            if(! in_array($emaildomain, $limited_email_domains, true))
            {
                $errors->add('user_email', __('Sorry, that email address is not allowed!'));
            }
        }

        // Check if the username has been used already.
        if(username_exists($user_name))
        {
            $errors->add('user_name', __('Sorry, that username already exists!'));
        }

        // Check if the email address has been used already.
        if(email_exists($user_email))
        {
            $errors->add('user_email', sprintf(/* translators: %s: Link to the login page. */ __('<strong>Error:</strong> This email address is already registered. <a href="%s">Log in</a> with this address or choose another one.'), wp_login_url()));
        }

        // Has someone already signed up for this username?
        $signup = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->signups WHERE user_login = %s", $user_name));
        if($signup instanceof stdClass)
        {
            $registered_at = mysql2date('U', $signup->registered);
            $now = time();
            $diff = $now - $registered_at;
            // If registered more than two days ago, cancel registration and let this signup go through.
            if($diff > 2 * DAY_IN_SECONDS)
            {
                $wpdb->delete($wpdb->signups, ['user_login' => $user_name]);
            }
            else
            {
                $errors->add('user_name', __('That username is currently reserved but may be available in a couple of days.'));
            }
        }

        $signup = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->signups WHERE user_email = %s", $user_email));
        if($signup instanceof stdClass)
        {
            $diff = time() - mysql2date('U', $signup->registered);
            // If registered more than two days ago, cancel registration and let this signup go through.
            if($diff > 2 * DAY_IN_SECONDS)
            {
                $wpdb->delete($wpdb->signups, ['user_email' => $user_email]);
            }
            else
            {
                $errors->add('user_email', __('That email address has already been used. Please check your inbox for an activation email. It will become available in a couple of days if you do nothing.'));
            }
        }

        $result = compact('user_name', 'orig_username', 'user_email', 'errors');

        return apply_filters('wpmu_validate_user_signup', $result);
    }

    function wpmu_validate_blog_signup($blogname, $blog_title, $user = '')
    {
        global $wpdb, $domain;

        $current_network = get_network();
        $base = $current_network->path;

        $blog_title = strip_tags($blog_title);

        $errors = new WP_Error();
        $illegal_names = get_site_option('illegal_names');
        if(false == $illegal_names)
        {
            $illegal_names = ['www', 'web', 'root', 'admin', 'main', 'invite', 'administrator'];
            add_site_option('illegal_names', $illegal_names);
        }

        /*
	 * On sub dir installations, some names are so illegal, only a filter can
	 * spring them from jail.
	 */
        if(! is_subdomain_install())
        {
            $illegal_names = array_merge($illegal_names, get_subdirectory_reserved_names());
        }

        if(empty($blogname))
        {
            $errors->add('blogname', __('Please enter a site name.'));
        }

        if(preg_match('/[^a-z0-9]+/', $blogname))
        {
            $errors->add('blogname', __('Site names can only contain lowercase letters (a-z) and numbers.'));
        }

        if(in_array($blogname, $illegal_names, true))
        {
            $errors->add('blogname', __('That name is not allowed.'));
        }

        $minimum_site_name_length = apply_filters('minimum_site_name_length', 4);

        if(strlen($blogname) < $minimum_site_name_length)
        {
            /* translators: %s: Minimum site name length. */
            $errors->add('blogname', sprintf(_n('Site name must be at least %s character.', 'Site name must be at least %s characters.', $minimum_site_name_length), number_format_i18n($minimum_site_name_length)));
        }

        // Do not allow users to create a site that conflicts with a page on the main blog.
        if(! is_subdomain_install() && $wpdb->get_var($wpdb->prepare('SELECT post_name FROM '.$wpdb->get_blog_prefix($current_network->site_id)."posts WHERE post_type = 'page' AND post_name = %s", $blogname)))
        {
            $errors->add('blogname', __('Sorry, you may not use that site name.'));
        }

        // All numeric?
        if(preg_match('/^[0-9]*$/', $blogname))
        {
            $errors->add('blogname', __('Sorry, site names must have letters too!'));
        }

        $blogname = apply_filters('newblogname', $blogname);

        $blog_title = wp_unslash($blog_title);

        if(empty($blog_title))
        {
            $errors->add('blog_title', __('Please enter a site title.'));
        }

        // Check if the domain/path has been used already.
        if(is_subdomain_install())
        {
            $mydomain = $blogname.'.'.preg_replace('|^www\.|', '', $domain);
            $path = $base;
        }
        else
        {
            $mydomain = $domain;
            $path = $base.$blogname.'/';
        }
        if(domain_exists($mydomain, $path, $current_network->id))
        {
            $errors->add('blogname', __('Sorry, that site already exists!'));
        }

        /*
	 * Do not allow users to create a site that matches an existing user's login name,
	 * unless it's the user's own username.
	 */
        if(username_exists($blogname))
        {
            if(! is_object($user) || (is_object($user) && ($user->user_login != $blogname)))
            {
                $errors->add('blogname', __('Sorry, that site is reserved!'));
            }
        }

        /*
	 * Has someone already signed up for this domain?
	 * TODO: Check email too?
	 */
        $signup = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->signups WHERE domain = %s AND path = %s", $mydomain, $path));
        if($signup instanceof stdClass)
        {
            $diff = time() - mysql2date('U', $signup->registered);
            // If registered more than two days ago, cancel registration and let this signup go through.
            if($diff > 2 * DAY_IN_SECONDS)
            {
                $wpdb->delete($wpdb->signups, [
                    'domain' => $mydomain,
                    'path' => $path,
                ]);
            }
            else
            {
                $errors->add('blogname', __('That site is currently reserved but may be available in a couple days.'));
            }
        }

        $result = [
            'domain' => $mydomain,
            'path' => $path,
            'blogname' => $blogname,
            'blog_title' => $blog_title,
            'user' => $user,
            'errors' => $errors,
        ];

        return apply_filters('wpmu_validate_blog_signup', $result);
    }

    function wpmu_signup_blog($domain, $path, $title, $user, $user_email, $meta = [])
    {
        global $wpdb;

        $key = substr(md5(time().wp_rand().$domain), 0, 16);

        $meta = apply_filters('signup_site_meta', $meta, $domain, $path, $title, $user, $user_email, $key);

        $wpdb->insert($wpdb->signups, [
            'domain' => $domain,
            'path' => $path,
            'title' => $title,
            'user_login' => $user,
            'user_email' => $user_email,
            'registered' => current_time('mysql', true),
            'activation_key' => $key,
            'meta' => serialize($meta),
        ]);

        do_action('after_signup_site', $domain, $path, $title, $user, $user_email, $key, $meta);
    }

    function wpmu_signup_user($user, $user_email, $meta = [])
    {
        global $wpdb;

        // Format data.
        $user = preg_replace('/\s+/', '', sanitize_user($user, true));
        $user_email = sanitize_email($user_email);
        $key = substr(md5(time().wp_rand().$user_email), 0, 16);

        $meta = apply_filters('signup_user_meta', $meta, $user, $user_email, $key);

        $wpdb->insert($wpdb->signups, [
            'domain' => '',
            'path' => '',
            'title' => '',
            'user_login' => $user,
            'user_email' => $user_email,
            'registered' => current_time('mysql', true),
            'activation_key' => $key,
            'meta' => serialize($meta),
        ]);

        do_action('after_signup_user', $user, $user_email, $key, $meta);
    }

    function wpmu_signup_blog_notification($domain, $path, $title, $user_login, $user_email, $key, $meta = [])
    {
        if(! apply_filters('wpmu_signup_blog_notification', $domain, $path, $title, $user_login, $user_email, $key, $meta))
        {
            return false;
        }

        // Send email with activation link.
        if(! is_subdomain_install() || get_current_network_id() != 1)
        {
            $activate_url = network_site_url("wp-activate.php?key=$key");
        }
        else
        {
            $activate_url = "http://{$domain}{$path}wp-activate.php?key=$key"; // @todo Use *_url() API.
        }

        $activate_url = esc_url($activate_url);

        $admin_email = get_site_option('admin_email');

        if('' === $admin_email)
        {
            $admin_email = 'support@'.wp_parse_url(network_home_url(), PHP_URL_HOST);
        }

        $from_name = ('' !== get_site_option('site_name')) ? esc_html(get_site_option('site_name')) : 'WordPress';
        $message_headers = "From: \"{$from_name}\" <{$admin_email}>\n".'Content-Type: text/plain; charset="'.get_option('blog_charset')."\"\n";

        $user = get_user_by('login', $user_login);
        $switched_locale = $user && switch_to_user_locale($user->ID);

        $message = sprintf(
            apply_filters('wpmu_signup_blog_notification_email', /* translators: New site notification email. 1: Activation URL, 2: New site URL. */ __("To activate your site, please click the following link:\n\n%1\$s\n\nAfter you activate, you will receive *another email* with your login.\n\nAfter you activate, you can visit your site here:\n\n%2\$s"), $domain, $path, $title, $user_login, $user_email, $key, $meta), $activate_url, esc_url("http://{$domain}{$path}"), $key
        );

        $subject = sprintf(
            apply_filters('wpmu_signup_blog_notification_subject', /* translators: New site notification email subject. 1: Network title, 2: New site URL. */ _x('[%1$s] Activate %2$s', 'New site notification email subject'), $domain, $path, $title, $user_login, $user_email, $key, $meta), $from_name, esc_url('http://'.$domain.$path)
        );

        wp_mail($user_email, wp_specialchars_decode($subject), $message, $message_headers);

        if($switched_locale)
        {
            restore_previous_locale();
        }

        return true;
    }

    function wpmu_signup_user_notification($user_login, $user_email, $key, $meta = [])
    {
        if(! apply_filters('wpmu_signup_user_notification', $user_login, $user_email, $key, $meta))
        {
            return false;
        }

        $user = get_user_by('login', $user_login);
        $switched_locale = $user && switch_to_user_locale($user->ID);

        // Send email with activation link.
        $admin_email = get_site_option('admin_email');

        if('' === $admin_email)
        {
            $admin_email = 'support@'.wp_parse_url(network_home_url(), PHP_URL_HOST);
        }

        $from_name = ('' !== get_site_option('site_name')) ? esc_html(get_site_option('site_name')) : 'WordPress';
        $message_headers = "From: \"{$from_name}\" <{$admin_email}>\n".'Content-Type: text/plain; charset="'.get_option('blog_charset')."\"\n";
        $message = sprintf(
            apply_filters('wpmu_signup_user_notification_email', /* translators: New user notification email. %s: Activation URL. */ __("To activate your user, please click the following link:\n\n%s\n\nAfter you activate, you will receive *another email* with your login."), $user_login, $user_email, $key, $meta), site_url("wp-activate.php?key=$key")
        );

        $subject = sprintf(
            apply_filters('wpmu_signup_user_notification_subject', /* translators: New user notification email subject. 1: Network title, 2: New user login. */ _x('[%1$s] Activate %2$s', 'New user notification email subject'), $user_login, $user_email, $key, $meta), $from_name, $user_login
        );

        wp_mail($user_email, wp_specialchars_decode($subject), $message, $message_headers);

        if($switched_locale)
        {
            restore_previous_locale();
        }

        return true;
    }

    function wpmu_activate_signup($key)
    {
        global $wpdb;

        $signup = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->signups WHERE activation_key = %s", $key));

        if(empty($signup))
        {
            return new WP_Error('invalid_key', __('Invalid activation key.'));
        }

        if($signup->active)
        {
            if(empty($signup->domain))
            {
                return new WP_Error('already_active', __('The user is already active.'), $signup);
            }
            else
            {
                return new WP_Error('already_active', __('The site is already active.'), $signup);
            }
        }

        $meta = maybe_unserialize($signup->meta);
        $password = wp_generate_password(12, false);

        $user_id = username_exists($signup->user_login);

        if($user_id)
        {
            $user_already_exists = true;
        }
        else
        {
            $user_id = wpmu_create_user($signup->user_login, $password, $signup->user_email);
        }

        if(! $user_id)
        {
            return new WP_Error('create_user', __('Could not create user'), $signup);
        }

        $now = current_time('mysql', true);

        if(empty($signup->domain))
        {
            $wpdb->update($wpdb->signups, [
                'active' => 1,
                'activated' => $now,
            ],            ['activation_key' => $key]);

            if(isset($user_already_exists))
            {
                return new WP_Error('user_already_exists', __('That username is already activated.'), $signup);
            }

            do_action('wpmu_activate_user', $user_id, $password, $meta);

            return compact('user_id', 'password', 'meta');
        }

        $blog_id = wpmu_create_blog($signup->domain, $signup->path, $signup->title, $user_id, $meta, get_current_network_id());

        // TODO: What to do if we create a user but cannot create a blog?
        if(is_wp_error($blog_id))
        {
            /*
		 * If blog is taken, that means a previous attempt to activate this blog
		 * failed in between creating the blog and setting the activation flag.
		 * Let's just set the active flag and instruct the user to reset their password.
		 */
            if('blog_taken' === $blog_id->get_error_code())
            {
                $blog_id->add_data($signup);
                $wpdb->update($wpdb->signups, [
                    'active' => 1,
                    'activated' => $now,
                ],            ['activation_key' => $key]);
            }

            return $blog_id;
        }

        $wpdb->update($wpdb->signups, [
            'active' => 1,
            'activated' => $now,
        ],            ['activation_key' => $key]);

        do_action('wpmu_activate_blog', $blog_id, $user_id, $password, $signup->title, $meta);

        return [
            'blog_id' => $blog_id,
            'user_id' => $user_id,
            'password' => $password,
            'title' => $signup->title,
            'meta' => $meta,
        ];
    }

    function wp_delete_signup_on_user_delete($id, $reassign, $user)
    {
        global $wpdb;

        $wpdb->delete($wpdb->signups, ['user_login' => $user->user_login]);
    }

    function wpmu_create_user($user_name, $password, $email)
    {
        $user_name = preg_replace('/\s+/', '', sanitize_user($user_name, true));

        $user_id = wp_create_user($user_name, $password, $email);
        if(is_wp_error($user_id))
        {
            return false;
        }

        // Newly created users have no roles or caps until they are added to a blog.
        delete_user_option($user_id, 'capabilities');
        delete_user_option($user_id, 'user_level');

        do_action('wpmu_new_user', $user_id);

        return $user_id;
    }

    function wpmu_create_blog($domain, $path, $title, $user_id, $options = [], $network_id = 1)
    {
        $defaults = [
            'public' => 0,
        ];
        $options = wp_parse_args($options, $defaults);

        $title = strip_tags($title);
        $user_id = (int) $user_id;

        // Check if the domain has been used already. We should return an error message.
        if(domain_exists($domain, $path, $network_id))
        {
            return new WP_Error('blog_taken', __('Sorry, that site already exists!'));
        }

        if(! wp_installing())
        {
            wp_installing(true);
        }

        $allowed_data_fields = ['public', 'archived', 'mature', 'spam', 'deleted', 'lang_id'];

        $site_data = array_merge(compact('domain', 'path', 'network_id'), array_intersect_key($options, array_flip($allowed_data_fields)));

        // Data to pass to wp_initialize_site().
        $site_initialization_data = [
            'title' => $title,
            'user_id' => $user_id,
            'options' => array_diff_key($options, array_flip($allowed_data_fields)),
        ];

        $blog_id = wp_insert_site(array_merge($site_data, $site_initialization_data));

        if(is_wp_error($blog_id))
        {
            return $blog_id;
        }

        wp_cache_set_sites_last_changed();

        return $blog_id;
    }

    function newblog_notify_siteadmin($blog_id, $deprecated = '')
    {
        if(is_object($blog_id))
        {
            $blog_id = $blog_id->blog_id;
        }

        if('yes' !== get_site_option('registrationnotification'))
        {
            return false;
        }

        $email = get_site_option('admin_email');

        if(is_email($email) == false)
        {
            return false;
        }

        $options_site_url = esc_url(network_admin_url('settings.php'));

        switch_to_blog($blog_id);
        $blogname = get_option('blogname');
        $siteurl = site_url();
        restore_current_blog();

        $msg = sprintf(/* translators: New site notification email. 1: Site URL, 2: User IP address, 3: URL to Network Settings screen. */ __(
                                                                                                                                               'New Site: %1$s
URL: %2$s
Remote IP address: %3$s

Disable these notifications: %4$s'
                                                                                                                                           ), $blogname, $siteurl, wp_unslash($_SERVER['REMOTE_ADDR']), $options_site_url
        );

        $msg = apply_filters('newblog_notify_siteadmin', $msg, $blog_id);

        /* translators: New site notification email subject. %s: New site URL. */
        wp_mail($email, sprintf(__('New Site Registration: %s'), $siteurl), $msg);

        return true;
    }

    function newuser_notify_siteadmin($user_id)
    {
        if('yes' !== get_site_option('registrationnotification'))
        {
            return false;
        }

        $email = get_site_option('admin_email');

        if(is_email($email) == false)
        {
            return false;
        }

        $user = get_userdata($user_id);

        $options_site_url = esc_url(network_admin_url('settings.php'));

        $msg = sprintf(/* translators: New user notification email. 1: User login, 2: User IP address, 3: URL to Network Settings screen. */ __(
                                                                                                                                                 'New User: %1$s
Remote IP address: %2$s

Disable these notifications: %3$s'
                                                                                                                                             ), $user->user_login, wp_unslash($_SERVER['REMOTE_ADDR']), $options_site_url
        );

        $msg = apply_filters('newuser_notify_siteadmin', $msg, $user);

        /* translators: New user notification email subject. %s: User login. */
        wp_mail($email, sprintf(__('New User Registration: %s'), $user->user_login), $msg);

        return true;
    }

    function domain_exists($domain, $path, $network_id = 1)
    {
        $path = trailingslashit($path);
        $args = [
            'network_id' => $network_id,
            'domain' => $domain,
            'path' => $path,
            'fields' => 'ids',
            'number' => 1,
            'update_site_meta_cache' => false,
        ];
        $result = get_sites($args);
        $result = array_shift($result);

        return apply_filters('domain_exists', $result, $domain, $path, $network_id);
    }

    function wpmu_welcome_notification($blog_id, $user_id, $password, $title, $meta = [])
    {
        $current_network = get_network();

        if(! apply_filters('wpmu_welcome_notification', $blog_id, $user_id, $password, $title, $meta))
        {
            return false;
        }

        $user = get_userdata($user_id);

        $switched_locale = switch_to_user_locale($user_id);

        $welcome_email = get_site_option('welcome_email');
        if(false == $welcome_email)
        {
            /* translators: Do not translate USERNAME, SITE_NAME, BLOG_URL, PASSWORD: those are placeholders. */
            $welcome_email = __(
                'Howdy USERNAME,

Your new SITE_NAME site has been successfully set up at:
BLOG_URL

You can log in to the administrator account with the following information:

Username: USERNAME
Password: PASSWORD
Log in here: BLOG_URLwp-login.php

We hope you enjoy your new site. Thanks!

--The Team @ SITE_NAME'
            );
        }

        $url = get_blogaddress_by_id($blog_id);

        $welcome_email = str_replace('SITE_NAME', $current_network->site_name, $welcome_email);
        $welcome_email = str_replace(array('BLOG_TITLE', 'BLOG_URL'), array($title, $url), $welcome_email);
        $welcome_email = str_replace(array('USERNAME', 'PASSWORD'), array(
            $user->user_login,
            $password
        ),                           $welcome_email);

        $welcome_email = apply_filters('update_welcome_email', $welcome_email, $blog_id, $user_id, $password, $title, $meta);

        $admin_email = get_site_option('admin_email');

        if('' === $admin_email)
        {
            $admin_email = 'support@'.wp_parse_url(network_home_url(), PHP_URL_HOST);
        }

        $from_name = ('' !== get_site_option('site_name')) ? esc_html(get_site_option('site_name')) : 'WordPress';
        $message_headers = "From: \"{$from_name}\" <{$admin_email}>\n".'Content-Type: text/plain; charset="'.get_option('blog_charset')."\"\n";
        $message = $welcome_email;

        if(empty($current_network->site_name))
        {
            $current_network->site_name = 'WordPress';
        }

        /* translators: New site notification email subject. 1: Network title, 2: New site title. */
        $subject = __('New %1$s Site: %2$s');

        $subject = apply_filters('update_welcome_subject', sprintf($subject, $current_network->site_name, wp_unslash($title)));

        wp_mail($user->user_email, wp_specialchars_decode($subject), $message, $message_headers);

        if($switched_locale)
        {
            restore_previous_locale();
        }

        return true;
    }

    function wpmu_new_site_admin_notification($site_id, $user_id)
    {
        $site = get_site($site_id);
        $user = get_userdata($user_id);
        $email = get_site_option('admin_email');

        if(! $site || ! $user || ! $email || ! apply_filters('send_new_site_email', true, $site, $user))
        {
            return false;
        }

        $switched_locale = false;
        $network_admin = get_user_by('email', $email);

        if($network_admin)
        {
            // If the network admin email address corresponds to a user, switch to their locale.
            $switched_locale = switch_to_user_locale($network_admin->ID);
        }
        else
        {
            // Otherwise switch to the locale of the current site.
            $switched_locale = switch_to_locale(get_locale());
        }

        $subject = sprintf(/* translators: New site notification email subject. %s: Network title. */ __('[%s] New Site Created'), get_network()->site_name);

        $message = sprintf(/* translators: New site notification email. 1: User login, 2: Site URL, 3: Site title. */ __(
                                                                                                                          'New site created by %1$s

Address: %2$s
Name: %3$s'
                                                                                                                      ), $user->user_login, get_site_url($site->id), get_blog_option($site->id, 'blogname')
        );

        $header = sprintf('From: "%1$s" <%2$s>', _x('Site Admin', 'email "From" field'), $email);

        $new_site_email = [
            'to' => $email,
            'subject' => $subject,
            'message' => $message,
            'headers' => $header,
        ];

        $new_site_email = apply_filters('new_site_email', $new_site_email, $site, $user);

        wp_mail($new_site_email['to'], wp_specialchars_decode($new_site_email['subject']), $new_site_email['message'], $new_site_email['headers']);

        if($switched_locale)
        {
            restore_previous_locale();
        }

        return true;
    }

    function wpmu_welcome_user_notification($user_id, $password, $meta = [])
    {
        $current_network = get_network();

        if(! apply_filters('wpmu_welcome_user_notification', $user_id, $password, $meta))
        {
            return false;
        }

        $welcome_email = get_site_option('welcome_user_email');

        $user = get_userdata($user_id);

        $switched_locale = switch_to_user_locale($user_id);

        $welcome_email = apply_filters('update_welcome_user_email', $welcome_email, $user_id, $password, $meta);
        $welcome_email = str_replace(array('SITE_NAME', 'USERNAME'), array(
            $current_network->site_name,
            $user->user_login
        ),                           $welcome_email);
        $welcome_email = str_replace(array('PASSWORD', 'LOGINLINK'), array($password, wp_login_url()), $welcome_email);

        $admin_email = get_site_option('admin_email');

        if('' === $admin_email)
        {
            $admin_email = 'support@'.wp_parse_url(network_home_url(), PHP_URL_HOST);
        }

        $from_name = ('' !== get_site_option('site_name')) ? esc_html(get_site_option('site_name')) : 'WordPress';
        $message_headers = "From: \"{$from_name}\" <{$admin_email}>\n".'Content-Type: text/plain; charset="'.get_option('blog_charset')."\"\n";
        $message = $welcome_email;

        if(empty($current_network->site_name))
        {
            $current_network->site_name = 'WordPress';
        }

        /* translators: New user notification email subject. 1: Network title, 2: New user login. */
        $subject = __('New %1$s User: %2$s');

        $subject = apply_filters('update_welcome_user_subject', sprintf($subject, $current_network->site_name, $user->user_login));

        wp_mail($user->user_email, wp_specialchars_decode($subject), $message, $message_headers);

        if($switched_locale)
        {
            restore_previous_locale();
        }

        return true;
    }

    function get_current_site()
    {
        global $current_site;

        return $current_site;
    }

    function get_most_recent_post_of_user($user_id)
    {
        global $wpdb;

        $user_blogs = get_blogs_of_user((int) $user_id);
        $most_recent_post = [];

        /*
	 * Walk through each blog and get the most recent post
	 * published by $user_id.
	 */
        foreach((array) $user_blogs as $blog)
        {
            $prefix = $wpdb->get_blog_prefix($blog->userblog_id);
            $recent_post = $wpdb->get_row($wpdb->prepare("SELECT ID, post_date_gmt FROM {$prefix}posts WHERE post_author = %d AND post_type = 'post' AND post_status = 'publish' ORDER BY post_date_gmt DESC LIMIT 1", $user_id), ARRAY_A);

            // Make sure we found a post.
            if(isset($recent_post['ID']))
            {
                $post_gmt_ts = strtotime($recent_post['post_date_gmt']);

                /*
			 * If this is the first post checked
			 * or if this post is newer than the current recent post,
			 * make it the new most recent post.
			 */
                if(! isset($most_recent_post['post_gmt_ts']) || ($post_gmt_ts > $most_recent_post['post_gmt_ts']))
                {
                    $most_recent_post = [
                        'blog_id' => $blog->userblog_id,
                        'post_id' => $recent_post['ID'],
                        'post_date_gmt' => $recent_post['post_date_gmt'],
                        'post_gmt_ts' => $post_gmt_ts,
                    ];
                }
            }
        }

        return $most_recent_post;
    }

//
// Misc functions.
//

    function check_upload_mimes($mimes)
    {
        $site_exts = explode(' ', get_site_option('upload_filetypes', 'jpg jpeg png gif'));
        $site_mimes = [];
        foreach($site_exts as $ext)
        {
            foreach($mimes as $ext_pattern => $mime)
            {
                if('' !== $ext && str_contains($ext_pattern, $ext))
                {
                    $site_mimes[$ext_pattern] = $mime;
                }
            }
        }

        return $site_mimes;
    }

    function update_posts_count($deprecated = '')
    {
        global $wpdb;
        update_option('post_count', (int) $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_status = 'publish' and post_type = 'post'"));
    }

    function wpmu_log_new_registrations($blog_id, $user_id)
    {
        global $wpdb;

        if(is_object($blog_id))
        {
            $blog_id = $blog_id->blog_id;
        }

        if(is_array($user_id))
        {
            $user_id = ! empty($user_id['user_id']) ? $user_id['user_id'] : 0;
        }

        $user = get_userdata((int) $user_id);
        if($user)
        {
            $wpdb->insert($wpdb->registration_log, [
                'email' => $user->user_email,
                'IP' => preg_replace('/[^0-9., ]/', '', wp_unslash($_SERVER['REMOTE_ADDR'])),
                'blog_id' => $blog_id,
                'date_registered' => current_time('mysql'),
            ]);
        }
    }

    function redirect_this_site($deprecated = '')
    {
        return [get_network()->domain];
    }

    function upload_is_file_too_big($upload)
    {
        if(! is_array($upload) || defined('WP_IMPORTING') || get_site_option('upload_space_check_disabled'))
        {
            return $upload;
        }

        if(strlen($upload['bits']) > (KB_IN_BYTES * get_site_option('fileupload_maxk', 1500)))
        {
            /* translators: %s: Maximum allowed file size in kilobytes. */
            return sprintf(__('This file is too big. Files must be less than %s KB in size.').'<br />', get_site_option('fileupload_maxk', 1500));
        }

        return $upload;
    }

    function signup_nonce_fields()
    {
        $id = random_int();
        echo "<input type='hidden' name='signup_form_id' value='{$id}' />";
        wp_nonce_field('signup_form_'.$id, '_signup_form', false);
    }

    function signup_nonce_check($result)
    {
        if(! strpos($_SERVER['PHP_SELF'], 'wp-signup.php'))
        {
            return $result;
        }

        if(! wp_verify_nonce($_POST['_signup_form'], 'signup_form_'.$_POST['signup_form_id']))
        {
            $result['errors']->add('invalid_nonce', __('Unable to submit this form, please try again.'));
        }

        return $result;
    }

    function maybe_redirect_404()
    {
        if(is_main_site() && is_404() && defined('NOBLOGREDIRECT'))
        {
            $destination = apply_filters('blog_redirect_404', NOBLOGREDIRECT);

            if($destination)
            {
                if('%siteurl%' === $destination)
                {
                    $destination = network_home_url();
                }

                wp_redirect($destination);
                exit;
            }
        }
    }

    function maybe_add_existing_user_to_blog()
    {
        if(! str_contains($_SERVER['REQUEST_URI'], '/newbloguser/'))
        {
            return;
        }

        $parts = explode('/', $_SERVER['REQUEST_URI']);
        $key = array_pop($parts);

        if('' === $key)
        {
            $key = array_pop($parts);
        }

        $details = get_option('new_user_'.$key);
        if(! empty($details))
        {
            delete_option('new_user_'.$key);
        }

        if(empty($details) || is_wp_error(add_existing_user_to_blog($details)))
        {
            wp_die(sprintf(/* translators: %s: Home URL. */ __('An error occurred adding you to this site. Go to the <a href="%s">homepage</a>.'), home_url()));
        }

        wp_die(sprintf(/* translators: 1: Home URL, 2: Admin URL. */ __('You have been added to this site. Please visit the <a href="%1$s">homepage</a> or <a href="%2$s">log in</a> using your username and password.'), home_url(), admin_url()), __('WordPress &rsaquo; Success'), ['response' => 200]);
    }

    function add_existing_user_to_blog($details = false)
    {
        if(is_array($details))
        {
            $blog_id = get_current_blog_id();
            $result = add_user_to_blog($blog_id, $details['user_id'], $details['role']);

            do_action('added_existing_user', $details['user_id'], $result);

            return $result;
        }
    }

    function add_new_user_to_blog($user_id, $password, $meta)
    {
        if(! empty($meta['add_to_blog']))
        {
            $blog_id = $meta['add_to_blog'];
            $role = $meta['new_role'];
            remove_user_from_blog($user_id, get_network()->site_id); // Remove user from main blog.

            $result = add_user_to_blog($blog_id, $user_id, $role);

            if(! is_wp_error($result))
            {
                update_user_meta($user_id, 'primary_blog', $blog_id);
            }
        }
    }

    function fix_phpmailer_messageid($phpmailer)
    {
        $phpmailer->Hostname = get_network()->domain;
    }

    function is_user_spammy($user = null)
    {
        if(! ($user instanceof WP_User))
        {
            if($user)
            {
                $user = get_user_by('login', $user);
            }
            else
            {
                $user = wp_get_current_user();
            }
        }

        return $user && isset($user->spam) && 1 == $user->spam;
    }

    function update_blog_public($old_value, $value)
    {
        update_blog_status(get_current_blog_id(), 'public', (int) $value);
    }

    function users_can_register_signup_filter()
    {
        $registration = get_site_option('registration');

        return ('all' === $registration || 'user' === $registration);
    }

    function welcome_user_msg_filter($text)
    {
        if(! $text)
        {
            remove_filter('site_option_welcome_user_email', 'welcome_user_msg_filter');

            /* translators: Do not translate USERNAME, PASSWORD, LOGINLINK, SITE_NAME: those are placeholders. */
            $text = __(
                'Howdy USERNAME,

Your new account is set up.

You can log in with the following information:
Username: USERNAME
Password: PASSWORD
LOGINLINK

Thanks!

--The Team @ SITE_NAME'
            );
            update_site_option('welcome_user_email', $text);
        }

        return $text;
    }

    function force_ssl_content($force = '')
    {
        static $forced_content = false;

        if(! $force)
        {
            $old_forced = $forced_content;
            $forced_content = $force;

            return $old_forced;
        }

        return $forced_content;
    }

    function filter_SSL($url)
    {  // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
        if(! is_string($url))
        {
            return get_bloginfo('url'); // Return home site URL with proper scheme.
        }

        if(force_ssl_content() && is_ssl())
        {
            $url = set_url_scheme($url, 'https');
        }

        return $url;
    }

    function wp_schedule_update_network_counts()
    {
        if(! is_main_site())
        {
            return;
        }

        if(! wp_next_scheduled('update_network_counts') && ! wp_installing())
        {
            wp_schedule_event(time(), 'twicedaily', 'update_network_counts');
        }
    }

    function wp_update_network_counts($network_id = null)
    {
        wp_update_network_user_counts($network_id);
        wp_update_network_site_counts($network_id);
    }

    function wp_maybe_update_network_site_counts($network_id = null)
    {
        $is_small_network = ! wp_is_large_network('sites', $network_id);

        if(! apply_filters('enable_live_network_counts', $is_small_network, 'sites'))
        {
            return;
        }

        wp_update_network_site_counts($network_id);
    }

    function wp_maybe_update_network_user_counts($network_id = null)
    {
        $is_small_network = ! wp_is_large_network('users', $network_id);

        if(! apply_filters('enable_live_network_counts', $is_small_network, 'users'))
        {
            return;
        }

        wp_update_network_user_counts($network_id);
    }

    function wp_update_network_site_counts($network_id = null)
    {
        $network_id = (int) $network_id;
        if(! $network_id)
        {
            $network_id = get_current_network_id();
        }

        $count = get_sites([
                               'network_id' => $network_id,
                               'spam' => 0,
                               'deleted' => 0,
                               'archived' => 0,
                               'count' => true,
                               'update_site_meta_cache' => false,
                           ]);

        update_network_option($network_id, 'blog_count', $count);
    }

    function wp_update_network_user_counts($network_id = null)
    {
        wp_update_user_counts($network_id);
    }

    function get_space_used()
    {
        $space_used = apply_filters('pre_get_space_used', false);

        if(false === $space_used)
        {
            $upload_dir = wp_upload_dir();
            $space_used = get_dirsize($upload_dir['basedir']) / MB_IN_BYTES;
        }

        return $space_used;
    }

    function get_space_allowed()
    {
        $space_allowed = get_option('blog_upload_space');

        if(! is_numeric($space_allowed))
        {
            $space_allowed = get_site_option('blog_upload_space');
        }

        if(! is_numeric($space_allowed))
        {
            $space_allowed = 100;
        }

        return apply_filters('get_space_allowed', $space_allowed);
    }

    function get_upload_space_available()
    {
        $allowed = get_space_allowed();
        if($allowed < 0)
        {
            $allowed = 0;
        }
        $space_allowed = $allowed * MB_IN_BYTES;
        if(get_site_option('upload_space_check_disabled'))
        {
            return $space_allowed;
        }

        $space_used = get_space_used() * MB_IN_BYTES;

        if(($space_allowed - $space_used) <= 0)
        {
            return 0;
        }

        return $space_allowed - $space_used;
    }

    function is_upload_space_available()
    {
        if(get_site_option('upload_space_check_disabled'))
        {
            return true;
        }

        return (bool) get_upload_space_available();
    }

    function upload_size_limit_filter($size)
    {
        $fileupload_maxk = (int) get_site_option('fileupload_maxk', 1500);
        $max_fileupload_in_bytes = KB_IN_BYTES * $fileupload_maxk;

        if(get_site_option('upload_space_check_disabled'))
        {
            return min($size, $max_fileupload_in_bytes);
        }

        return min($size, $max_fileupload_in_bytes, get_upload_space_available());
    }

    function wp_is_large_network($using = 'sites', $network_id = null)
    {
        $network_id = (int) $network_id;
        if(! $network_id)
        {
            $network_id = get_current_network_id();
        }

        if('users' === $using)
        {
            $count = get_user_count($network_id);

            $is_large_network = wp_is_large_user_count($network_id);

            return apply_filters('wp_is_large_network', $is_large_network, 'users', $count, $network_id);
        }

        $count = get_blog_count($network_id);

        return apply_filters('wp_is_large_network', $count > 10000, 'sites', $count, $network_id);
    }

    function get_subdirectory_reserved_names()
    {
        $names = [
            'page',
            'comments',
            'blog',
            'files',
            'feed',
            'wp-admin',
            'wp-content',
            'wp-includes',
            'wp-json',
            'embed',
        ];

        return apply_filters('subdirectory_reserved_names', $names);
    }

    function update_network_option_new_admin_email($old_value, $value)
    {
        if(get_site_option('admin_email') === $value || ! is_email($value))
        {
            return;
        }

        $hash = md5($value.time().random_int());
        $new_admin_email = [
            'hash' => $hash,
            'newemail' => $value,
        ];
        update_site_option('network_admin_hash', $new_admin_email);

        $switched_locale = switch_to_user_locale(get_current_user_id());

        /* translators: Do not translate USERNAME, ADMIN_URL, EMAIL, SITENAME, SITEURL: those are placeholders. */
        $email_text = __(
            'Howdy ###USERNAME###,

You recently requested to have the network admin email address on
your network changed.

If this is correct, please click on the following link to change it:
###ADMIN_URL###

You can safely ignore and delete this email if you do not want to
take this action.

This email has been sent to ###EMAIL###

Regards,
All at ###SITENAME###
###SITEURL###'
        );

        $content = apply_filters('new_network_admin_email_content', $email_text, $new_admin_email);

        $current_user = wp_get_current_user();
        $content = str_replace('###USERNAME###', $current_user->user_login, $content);
        $content = str_replace(array(
                                   '###ADMIN_URL###',
                                   '###EMAIL###'
                               ), array(
                                   esc_url(network_admin_url('settings.php?network_admin_hash='.$hash)),
                                   $value
                               ), $content);
        $content = str_replace(array(
                                   '###SITENAME###',
                                   '###SITEURL###'
                               ), array(
                                   wp_specialchars_decode(get_site_option('site_name'), ENT_QUOTES),
                                   network_home_url()
                               ), $content);

        wp_mail($value, sprintf(/* translators: Email change notification email subject. %s: Network title. */ __('[%s] Network Admin Email Change Request'), wp_specialchars_decode(get_site_option('site_name'), ENT_QUOTES)), $content);

        if($switched_locale)
        {
            restore_previous_locale();
        }
    }

    function wp_network_admin_email_change_notification($option_name, $new_email, $old_email, $network_id)
    {
        $send = true;

        // Don't send the notification to the default 'admin_email' value.
        if('you@example.com' === $old_email)
        {
            $send = false;
        }

        $send = apply_filters('send_network_admin_email_change_email', $send, $old_email, $new_email, $network_id);

        if(! $send)
        {
            return;
        }

        /* translators: Do not translate OLD_EMAIL, NEW_EMAIL, SITENAME, SITEURL: those are placeholders. */
        $email_change_text = __(
            'Hi,

This notice confirms that the network admin email address was changed on ###SITENAME###.

The new network admin email address is ###NEW_EMAIL###.

This email has been sent to ###OLD_EMAIL###

Regards,
All at ###SITENAME###
###SITEURL###'
        );

        $email_change_email = [
            'to' => $old_email,
            /* translators: Network admin email change notification email subject. %s: Network title. */
            'subject' => __('[%s] Network Admin Email Changed'),
            'message' => $email_change_text,
            'headers' => '',
        ];
        // Get network name.
        $network_name = wp_specialchars_decode(get_site_option('site_name'), ENT_QUOTES);

        $email_change_email = apply_filters('network_admin_email_change_email', $email_change_email, $old_email, $new_email, $network_id);

        $email_change_email['message'] = str_replace('###OLD_EMAIL###', $old_email, $email_change_email['message']);
        $email_change_email['message'] = str_replace('###NEW_EMAIL###', $new_email, $email_change_email['message']);
        $email_change_email['message'] = str_replace('###SITENAME###', $network_name, $email_change_email['message']);
        $email_change_email['message'] = str_replace('###SITEURL###', home_url(), $email_change_email['message']);

        wp_mail($email_change_email['to'], sprintf($email_change_email['subject'], $network_name), $email_change_email['message'], $email_change_email['headers']);
    }
