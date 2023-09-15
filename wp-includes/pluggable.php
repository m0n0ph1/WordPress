<?php

    if(! function_exists('wp_set_current_user')) :

        function wp_set_current_user($id, $name = '')
        {
            global $current_user;

            // If `$id` matches the current user, there is nothing to do.
            if(isset($current_user) && ($current_user instanceof WP_User) && ($id == $current_user->ID) && (null !== $id))
            {
                return $current_user;
            }

            $current_user = new WP_User($id, $name);

            setup_userdata($current_user->ID);

            do_action('set_current_user');

            return $current_user;
        }
    endif;

    if(! function_exists('wp_get_current_user')) :

        function wp_get_current_user()
        {
            return _wp_get_current_user();
        }
    endif;

    if(! function_exists('get_userdata')) :

        function get_userdata($user_id)
        {
            return get_user_by('id', $user_id);
        }
    endif;

    if(! function_exists('get_user_by')) :

        function get_user_by($field, $value)
        {
            $userdata = WP_User::get_data_by($field, $value);

            if(! $userdata)
            {
                return false;
            }

            $user = new WP_User();
            $user->init($userdata);

            return $user;
        }
    endif;

    if(! function_exists('cache_users')) :

        function cache_users($user_ids)
        {
            global $wpdb;

            update_meta_cache('user', $user_ids);

            $clean = _get_non_cached_ids($user_ids, 'users');

            if(empty($clean))
            {
                return;
            }

            $list = implode(',', $clean);

            $users = $wpdb->get_results("SELECT * FROM $wpdb->users WHERE ID IN ($list)");

            foreach($users as $user)
            {
                update_user_caches($user);
            }
        }
    endif;

    if(! function_exists('wp_mail')) :

        function wp_mail($to, $subject, $message, $headers = '', $attachments = [])
        {
            // Compact the input, apply the filters, and extract them back out.

            $atts = apply_filters('wp_mail', compact('to', 'subject', 'message', 'headers', 'attachments'));

            $pre_wp_mail = apply_filters('pre_wp_mail', null, $atts);

            if(null !== $pre_wp_mail)
            {
                return $pre_wp_mail;
            }

            if(isset($atts['to']))
            {
                $to = $atts['to'];
            }

            if(! is_array($to))
            {
                $to = explode(',', $to);
            }

            if(isset($atts['subject']))
            {
                $subject = $atts['subject'];
            }

            if(isset($atts['message']))
            {
                $message = $atts['message'];
            }

            if(isset($atts['headers']))
            {
                $headers = $atts['headers'];
            }

            if(isset($atts['attachments']))
            {
                $attachments = $atts['attachments'];
            }

            if(! is_array($attachments))
            {
                $attachments = explode("\n", str_replace("\r\n", "\n", $attachments));
            }
            global $phpmailer;

            // (Re)create it, if it's gone missing.
            if(! ($phpmailer instanceof PHPMailer\PHPMailer\PHPMailer))
            {
                require_once ABSPATH.WPINC.'/PHPMailer/PHPMailer.php';
                require_once ABSPATH.WPINC.'/PHPMailer/SMTP.php';
                require_once ABSPATH.WPINC.'/PHPMailer/Exception.php';
                $phpmailer = new PHPMailer\PHPMailer\PHPMailer(true);

                $phpmailer::$validator = static function($email)
                {
                    return (bool) is_email($email);
                };
            }

            // Headers.
            $cc = [];
            $bcc = [];
            $reply_to = [];

            if(empty($headers))
            {
                $headers = [];
            }
            else
            {
                if(! is_array($headers))
                {
                    /*
				 * Explode the headers out, so this function can take
				 * both string headers and an array of headers.
				 */
                    $tempheaders = explode("\n", str_replace("\r\n", "\n", $headers));
                }
                else
                {
                    $tempheaders = $headers;
                }
                $headers = [];

                // If it's actually got contents.
                if(! empty($tempheaders))
                {
                    // Iterate through the raw headers.
                    foreach((array) $tempheaders as $header)
                    {
                        if(! str_contains($header, ':'))
                        {
                            if(false !== stripos($header, 'boundary='))
                            {
                                $parts = preg_split('/boundary=/i', trim($header));
                                $boundary = trim(str_replace(["'", '"'], '', $parts[1]));
                            }
                            continue;
                        }
                        // Explode them out.
                        [$name, $content] = explode(':', trim($header), 2);

                        // Cleanup crew.
                        $name = trim($name);
                        $content = trim($content);

                        switch(strtolower($name))
                        {
                            // Mainly for legacy -- process a "From:" header if it's there.
                            case 'from':
                                $bracket_pos = strpos($content, '<');
                                if(false !== $bracket_pos)
                                {
                                    // Text before the bracketed email is the "From" name.
                                    if($bracket_pos > 0)
                                    {
                                        $from_name = substr($content, 0, $bracket_pos);
                                        $from_name = str_replace('"', '', $from_name);
                                        $from_name = trim($from_name);
                                    }

                                    $from_email = substr($content, $bracket_pos + 1);
                                    $from_email = str_replace('>', '', $from_email);
                                    $from_email = trim($from_email);
                                    // Avoid setting an empty $from_email.
                                }
                                elseif('' !== trim($content))
                                {
                                    $from_email = trim($content);
                                }
                                break;
                            case 'content-type':
                                if(str_contains($content, ';'))
                                {
                                    [$type, $charset_content] = explode(';', $content);
                                    $content_type = trim($type);
                                    if(false !== stripos($charset_content, 'charset='))
                                    {
                                        $charset = trim(str_replace(['charset=', '"'], '', $charset_content));
                                    }
                                    elseif(false !== stripos($charset_content, 'boundary='))
                                    {
                                        $boundary = trim(
                                            str_replace([
                                                            'BOUNDARY=',
                                                            'boundary=',
                                                            '"'
                                                        ], '', $charset_content)
                                        );
                                        $charset = '';
                                    }
                                    // Avoid setting an empty $content_type.
                                }
                                elseif('' !== trim($content))
                                {
                                    $content_type = trim($content);
                                }
                                break;
                            case 'cc':
                                $cc = array_merge((array) $cc, explode(',', $content));
                                break;
                            case 'bcc':
                                $bcc = array_merge((array) $bcc, explode(',', $content));
                                break;
                            case 'reply-to':
                                $reply_to = array_merge((array) $reply_to, explode(',', $content));
                                break;
                            default:
                                // Add it to our grand headers array.
                                $headers[trim($name)] = trim($content);
                                break;
                        }
                    }
                }
            }

            // Empty out the values that may be set.
            $phpmailer->clearAllRecipients();
            $phpmailer->clearAttachments();
            $phpmailer->clearCustomHeaders();
            $phpmailer->clearReplyTos();
            $phpmailer->Body = '';
            $phpmailer->AltBody = '';

            // Set "From" name and email.

            // If we don't have a name from the input headers.
            if(! isset($from_name))
            {
                $from_name = 'WordPress';
            }

            /*
		 * If we don't have an email from the input headers, default to wordpress@$sitename
		 * Some hosts will block outgoing mail from this address if it doesn't exist,
		 * but there's no easy alternative. Defaulting to admin_email might appear to be
		 * another option, but some hosts may refuse to relay mail from an unknown domain.
		 * See https://core.trac.wordpress.org/ticket/5007.
		 */
            if(! isset($from_email))
            {
                // Get the site domain and get rid of www.
                $sitename = wp_parse_url(network_home_url(), PHP_URL_HOST);
                $from_email = 'wordpress@';

                if(null !== $sitename)
                {
                    if(str_starts_with($sitename, 'www.'))
                    {
                        $sitename = substr($sitename, 4);
                    }

                    $from_email .= $sitename;
                }
            }

            $from_email = apply_filters('wp_mail_from', $from_email);

            $from_name = apply_filters('wp_mail_from_name', $from_name);

            try
            {
                $phpmailer->setFrom($from_email, $from_name, false);
            }
            catch(PHPMailer\PHPMailer\Exception $e)
            {
                $mail_error_data = compact('to', 'subject', 'message', 'headers', 'attachments');
                $mail_error_data['phpmailer_exception_code'] = $e->getCode();

                do_action('wp_mail_failed', new WP_Error('wp_mail_failed', $e->getMessage(), $mail_error_data));

                return false;
            }

            // Set mail's subject and body.
            $phpmailer->Subject = $subject;
            $phpmailer->Body = $message;

            // Set destination addresses, using appropriate methods for handling addresses.
            $address_headers = compact('to', 'cc', 'bcc', 'reply_to');

            foreach($address_headers as $address_header => $addresses)
            {
                if(empty($addresses))
                {
                    continue;
                }

                foreach((array) $addresses as $address)
                {
                    try
                    {
                        // Break $recipient into name and address parts if in the format "Foo <bar@baz.com>".
                        $recipient_name = '';

                        if(preg_match('/(.*)<(.+)>/', $address, $matches))
                        {
                            if(count($matches) === 3)
                            {
                                $recipient_name = $matches[1];
                                $address = $matches[2];
                            }
                        }

                        switch($address_header)
                        {
                            case 'to':
                                $phpmailer->addAddress($address, $recipient_name);
                                break;
                            case 'cc':
                                $phpmailer->addCc($address, $recipient_name);
                                break;
                            case 'bcc':
                                $phpmailer->addBcc($address, $recipient_name);
                                break;
                            case 'reply_to':
                                $phpmailer->addReplyTo($address, $recipient_name);
                                break;
                        }
                    }
                    catch(PHPMailer\PHPMailer\Exception $e)
                    {
                        continue;
                    }
                }
            }

            // Set to use PHP's mail().
            $phpmailer->isMail();

            // Set Content-Type and charset.

            // If we don't have a Content-Type from the input headers.
            if(! isset($content_type))
            {
                $content_type = 'text/plain';
            }

            $content_type = apply_filters('wp_mail_content_type', $content_type);

            $phpmailer->ContentType = $content_type;

            // Set whether it's plaintext, depending on $content_type.
            if('text/html' === $content_type)
            {
                $phpmailer->isHTML(true);
            }

            // If we don't have a charset from the input headers.
            if(! isset($charset))
            {
                $charset = get_bloginfo('charset');
            }

            $phpmailer->CharSet = apply_filters('wp_mail_charset', $charset);

            // Set custom headers.
            if(! empty($headers))
            {
                foreach((array) $headers as $name => $content)
                {
                    // Only add custom headers not added automatically by PHPMailer.
                    if(! in_array($name, ['MIME-Version', 'X-Mailer'], true))
                    {
                        try
                        {
                            $phpmailer->addCustomHeader(sprintf('%1$s: %2$s', $name, $content));
                        }
                        catch(PHPMailer\PHPMailer\Exception $e)
                        {
                            continue;
                        }
                    }
                }

                if(false !== stripos($content_type, 'multipart') && ! empty($boundary))
                {
                    $phpmailer->addCustomHeader(sprintf('Content-Type: %s; boundary="%s"', $content_type, $boundary));
                }
            }

            if(! empty($attachments))
            {
                foreach($attachments as $filename => $attachment)
                {
                    $filename = is_string($filename) ? $filename : '';

                    try
                    {
                        $phpmailer->addAttachment($attachment, $filename);
                    }
                    catch(PHPMailer\PHPMailer\Exception $e)
                    {
                        continue;
                    }
                }
            }

            do_action_ref_array('phpmailer_init', [&$phpmailer]);

            $mail_data = compact('to', 'subject', 'message', 'headers', 'attachments');

            // Send!
            try
            {
                $send = $phpmailer->send();

                do_action('wp_mail_succeeded', $mail_data);

                return $send;
            }
            catch(PHPMailer\PHPMailer\Exception $e)
            {
                $mail_data['phpmailer_exception_code'] = $e->getCode();

                do_action('wp_mail_failed', new WP_Error('wp_mail_failed', $e->getMessage(), $mail_data));

                return false;
            }
        }
    endif;

    if(! function_exists('wp_authenticate')) :

        function wp_authenticate($username, $password)
        {
            $username = sanitize_user($username);
            $password = trim($password);

            $user = apply_filters('authenticate', null, $username, $password);

            if(null == $user)
            {
                /*
			 * TODO: What should the error message be? (Or would these even happen?)
			 * Only needed if all authentication handlers fail to return anything.
			 */
                $user = new WP_Error('authentication_failed', __('<strong>Error:</strong> Invalid username, email address or incorrect password.'));
            }

            $ignore_codes = ['empty_username', 'empty_password'];

            if(is_wp_error($user) && ! in_array($user->get_error_code(), $ignore_codes, true))
            {
                $error = $user;

                do_action('wp_login_failed', $username, $error);
            }

            return $user;
        }
    endif;

    if(! function_exists('wp_logout')) :

        function wp_logout()
        {
            $user_id = get_current_user_id();

            wp_destroy_current_session();
            wp_clear_auth_cookie();
            wp_set_current_user(0);

            do_action('wp_logout', $user_id);
        }
    endif;

    if(! function_exists('wp_validate_auth_cookie')) :

        function wp_validate_auth_cookie($cookie = '', $scheme = '')
        {
            $cookie_elements = wp_parse_auth_cookie($cookie, $scheme);
            if(! $cookie_elements)
            {
                do_action('auth_cookie_malformed', $cookie, $scheme);

                return false;
            }

            $scheme = $cookie_elements['scheme'];
            $username = $cookie_elements['username'];
            $hmac = $cookie_elements['hmac'];
            $token = $cookie_elements['token'];
            $expired = $cookie_elements['expiration'];
            $expiration = $cookie_elements['expiration'];

            // Allow a grace period for POST and Ajax requests.
            if(wp_doing_ajax() || 'POST' === $_SERVER['REQUEST_METHOD'])
            {
                $expired += HOUR_IN_SECONDS;
            }

            // Quick check to see if an honest cookie has expired.
            if($expired < time())
            {
                do_action('auth_cookie_expired', $cookie_elements);

                return false;
            }

            $user = get_user_by('login', $username);
            if(! $user)
            {
                do_action('auth_cookie_bad_username', $cookie_elements);

                return false;
            }

            $pass_frag = substr($user->user_pass, 8, 4);

            $key = wp_hash($username.'|'.$pass_frag.'|'.$expiration.'|'.$token, $scheme);

            // If ext/hash is not present, compat.php's hash_hmac() does not support sha256.
            $algo = function_exists('hash') ? 'sha256' : 'sha1';
            $hash = hash_hmac($algo, $username.'|'.$expiration.'|'.$token, $key);

            if(! hash_equals($hash, $hmac))
            {
                do_action('auth_cookie_bad_hash', $cookie_elements);

                return false;
            }

            $manager = WP_Session_Tokens::get_instance($user->ID);
            if(! $manager->verify($token))
            {
                do_action('auth_cookie_bad_session_token', $cookie_elements);

                return false;
            }

            // Ajax/POST grace period set above.
            if($expiration < time())
            {
                $GLOBALS['login_grace_period'] = 1;
            }

            do_action('auth_cookie_valid', $cookie_elements, $user);

            return $user->ID;
        }
    endif;

    if(! function_exists('wp_generate_auth_cookie')) :

        function wp_generate_auth_cookie($user_id, $expiration, $scheme = 'auth', $token = '')
        {
            $user = get_userdata($user_id);
            if(! $user)
            {
                return '';
            }

            if(! $token)
            {
                $manager = WP_Session_Tokens::get_instance($user_id);
                $token = $manager->create($expiration);
            }

            $pass_frag = substr($user->user_pass, 8, 4);

            $key = wp_hash($user->user_login.'|'.$pass_frag.'|'.$expiration.'|'.$token, $scheme);

            // If ext/hash is not present, compat.php's hash_hmac() does not support sha256.
            $algo = function_exists('hash') ? 'sha256' : 'sha1';
            $hash = hash_hmac($algo, $user->user_login.'|'.$expiration.'|'.$token, $key);

            $cookie = $user->user_login.'|'.$expiration.'|'.$token.'|'.$hash;

            return apply_filters('auth_cookie', $cookie, $user_id, $expiration, $scheme, $token);
        }
    endif;

    if(! function_exists('wp_parse_auth_cookie')) :

        function wp_parse_auth_cookie($cookie = '', $scheme = '')
        {
            if(empty($cookie))
            {
                switch($scheme)
                {
                    case 'auth':
                        $cookie_name = AUTH_COOKIE;
                        break;
                    case 'secure_auth':
                        $cookie_name = SECURE_AUTH_COOKIE;
                        break;
                    case 'logged_in':
                        $cookie_name = LOGGED_IN_COOKIE;
                        break;
                    default:
                        if(is_ssl())
                        {
                            $cookie_name = SECURE_AUTH_COOKIE;
                            $scheme = 'secure_auth';
                        }
                        else
                        {
                            $cookie_name = AUTH_COOKIE;
                            $scheme = 'auth';
                        }
                }

                if(empty($_COOKIE[$cookie_name]))
                {
                    return false;
                }
                $cookie = $_COOKIE[$cookie_name];
            }

            $cookie_elements = explode('|', $cookie);
            if(count($cookie_elements) !== 4)
            {
                return false;
            }

            [$username, $expiration, $token, $hmac] = $cookie_elements;

            return compact('username', 'expiration', 'token', 'hmac', 'scheme');
        }
    endif;

    if(! function_exists('wp_set_auth_cookie')) :

        function wp_set_auth_cookie($user_id, $remember = false, $secure = '', $token = '')
        {
            if($remember)
            {
                $expiration = time() + apply_filters('auth_cookie_expiration', 14 * DAY_IN_SECONDS, $user_id, $remember);

                /*
			 * Ensure the browser will continue to send the cookie after the expiration time is reached.
			 * Needed for the login grace period in wp_validate_auth_cookie().
			 */
                $expire = $expiration + (12 * HOUR_IN_SECONDS);
            }
            else
            {
                $expiration = time() + apply_filters('auth_cookie_expiration', 2 * DAY_IN_SECONDS, $user_id, $remember);
                $expire = 0;
            }

            if('' === $secure)
            {
                $secure = is_ssl();
            }

            // Front-end cookie is secure when the auth cookie is secure and the site's home URL uses HTTPS.
            $secure_logged_in_cookie = $secure && 'https' === parse_url(get_option('home'), PHP_URL_SCHEME);

            $secure = apply_filters('secure_auth_cookie', $secure, $user_id);

            $secure_logged_in_cookie = apply_filters('secure_logged_in_cookie', $secure_logged_in_cookie, $user_id, $secure);

            if($secure)
            {
                $auth_cookie_name = SECURE_AUTH_COOKIE;
                $scheme = 'secure_auth';
            }
            else
            {
                $auth_cookie_name = AUTH_COOKIE;
                $scheme = 'auth';
            }

            if('' === $token)
            {
                $manager = WP_Session_Tokens::get_instance($user_id);
                $token = $manager->create($expiration);
            }

            $auth_cookie = wp_generate_auth_cookie($user_id, $expiration, $scheme, $token);
            $logged_in_cookie = wp_generate_auth_cookie($user_id, $expiration, 'logged_in', $token);

            do_action('set_auth_cookie', $auth_cookie, $expire, $expiration, $user_id, $scheme, $token);

            do_action('set_logged_in_cookie', $logged_in_cookie, $expire, $expiration, $user_id, 'logged_in', $token);

            if(! apply_filters('send_auth_cookies', true, $expire, $expiration, $user_id, $scheme, $token))
            {
                return;
            }

            setcookie($auth_cookie_name, $auth_cookie, $expire, PLUGINS_COOKIE_PATH, COOKIE_DOMAIN, $secure, true);
            setcookie($auth_cookie_name, $auth_cookie, $expire, ADMIN_COOKIE_PATH, COOKIE_DOMAIN, $secure, true);
            setcookie(LOGGED_IN_COOKIE, $logged_in_cookie, $expire, COOKIEPATH, COOKIE_DOMAIN, $secure_logged_in_cookie, true);
            if(COOKIEPATH != SITECOOKIEPATH)
            {
                setcookie(LOGGED_IN_COOKIE, $logged_in_cookie, $expire, SITECOOKIEPATH, COOKIE_DOMAIN, $secure_logged_in_cookie, true);
            }
        }
    endif;

    if(! function_exists('wp_clear_auth_cookie')) :

        function wp_clear_auth_cookie()
        {
            do_action('clear_auth_cookie');

            if(! apply_filters('send_auth_cookies', true, 0, 0, 0, '', ''))
            {
                return;
            }

            // Auth cookies.
            setcookie(AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, ADMIN_COOKIE_PATH, COOKIE_DOMAIN);
            setcookie(SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, ADMIN_COOKIE_PATH, COOKIE_DOMAIN);
            setcookie(AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, PLUGINS_COOKIE_PATH, COOKIE_DOMAIN);
            setcookie(SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, PLUGINS_COOKIE_PATH, COOKIE_DOMAIN);
            setcookie(LOGGED_IN_COOKIE, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
            setcookie(LOGGED_IN_COOKIE, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN);

            // Settings cookies.
            setcookie('wp-settings-'.get_current_user_id(), ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH);
            setcookie('wp-settings-time-'.get_current_user_id(), ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH);

            // Old cookies.
            setcookie(AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
            setcookie(AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN);
            setcookie(SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
            setcookie(SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN);

            // Even older cookies.
            setcookie(USER_COOKIE, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
            setcookie(PASS_COOKIE, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
            setcookie(USER_COOKIE, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN);
            setcookie(PASS_COOKIE, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN);

            // Post password cookie.
            setcookie('wp-postpass_'.COOKIEHASH, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
        }
    endif;

    if(! function_exists('is_user_logged_in')) :

        function is_user_logged_in()
        {
            $user = wp_get_current_user();

            return $user->exists();
        }
    endif;

    if(! function_exists('auth_redirect')) :

        function auth_redirect()
        {
            $secure = (is_ssl() || force_ssl_admin());

            $secure = apply_filters('secure_auth_redirect', $secure);

            // If https is required and request is http, redirect.
            if($secure && ! is_ssl() && str_contains($_SERVER['REQUEST_URI'], 'wp-admin'))
            {
                if(str_starts_with($_SERVER['REQUEST_URI'], 'http'))
                {
                    wp_redirect(set_url_scheme($_SERVER['REQUEST_URI'], 'https'));
                    exit;
                }
                else
                {
                    wp_redirect('https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
                    exit;
                }
            }

            $scheme = apply_filters('auth_redirect_scheme', '');

            $user_id = wp_validate_auth_cookie('', $scheme);
            if($user_id)
            {
                do_action('auth_redirect', $user_id);

                // If the user wants ssl but the session is not ssl, redirect.
                if(! $secure && get_user_option('use_ssl', $user_id) && str_contains($_SERVER['REQUEST_URI'], 'wp-admin'))
                {
                    if(str_starts_with($_SERVER['REQUEST_URI'], 'http'))
                    {
                        wp_redirect(set_url_scheme($_SERVER['REQUEST_URI'], 'https'));
                        exit;
                    }
                    else
                    {
                        wp_redirect('https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
                        exit;
                    }
                }

                return; // The cookie is good, so we're done.
            }

            // The cookie is no good, so force login.
            nocache_headers();

            if(str_contains($_SERVER['REQUEST_URI'], '/options.php') && wp_get_referer())
            {
                $redirect = wp_get_referer();
            }
            else
            {
                $redirect = set_url_scheme('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
            }

            $login_url = wp_login_url($redirect, true);

            wp_redirect($login_url);
            exit;
        }
    endif;

    if(! function_exists('check_admin_referer')) :

        function check_admin_referer($action = -1, $query_arg = '_wpnonce')
        {
            if(-1 === $action)
            {
                _doing_it_wrong(__FUNCTION__, __('You should specify an action to be verified by using the first parameter.'), '3.2.0');
            }

            $adminurl = strtolower(admin_url());
            $referer = strtolower(wp_get_referer());
            $result = isset($_REQUEST[$query_arg]) ? wp_verify_nonce($_REQUEST[$query_arg], $action) : false;

            do_action('check_admin_referer', $action, $result);

            if(! $result && ! (-1 === $action && str_starts_with($referer, $adminurl)))
            {
                wp_nonce_ays($action);
                die();
            }

            return $result;
        }
    endif;

    if(! function_exists('check_ajax_referer')) :

        function check_ajax_referer($action = -1, $query_arg = false, $stop = true)
        {
            if(-1 == $action)
            {
                _doing_it_wrong(__FUNCTION__, __('You should specify an action to be verified by using the first parameter.'), '4.7.0');
            }

            $nonce = '';

            if($query_arg && isset($_REQUEST[$query_arg]))
            {
                $nonce = $_REQUEST[$query_arg];
            }
            elseif(isset($_REQUEST['_ajax_nonce']))
            {
                $nonce = $_REQUEST['_ajax_nonce'];
            }
            elseif(isset($_REQUEST['_wpnonce']))
            {
                $nonce = $_REQUEST['_wpnonce'];
            }

            $result = wp_verify_nonce($nonce, $action);

            do_action('check_ajax_referer', $action, $result);

            if($stop && false === $result)
            {
                if(wp_doing_ajax())
                {
                    wp_die(-1, 403);
                }
                else
                {
                    die('-1');
                }
            }

            return $result;
        }
    endif;

    if(! function_exists('wp_redirect')) :

        function wp_redirect($location, $status = 302, $x_redirect_by = 'WordPress')
        {
            global $is_IIS;

            $location = apply_filters('wp_redirect', $location, $status);

            $status = apply_filters('wp_redirect_status', $status, $location);

            if(! $location)
            {
                return false;
            }

            if($status < 300 || 399 < $status)
            {
                wp_die(__('HTTP redirect status code must be a redirection code, 3xx.'));
            }

            $location = wp_sanitize_redirect($location);

            if(! $is_IIS && 'cgi-fcgi' !== PHP_SAPI)
            {
                status_header($status); // This causes problems on IIS and some FastCGI setups.
            }

            $x_redirect_by = apply_filters('x_redirect_by', $x_redirect_by, $status, $location);
            if(is_string($x_redirect_by))
            {
                header("X-Redirect-By: $x_redirect_by");
            }

            header("Location: $location", true, $status);

            return true;
        }
    endif;

    if(! function_exists('wp_sanitize_redirect')) :

        function wp_sanitize_redirect($location)
        {
            // Encode spaces.
            $location = str_replace(' ', '%20', $location);

            $regex = '/
		(
			(?: [\xC2-\xDF][\x80-\xBF]        # double-byte sequences   110xxxxx 10xxxxxx
			|   \xE0[\xA0-\xBF][\x80-\xBF]    # triple-byte sequences   1110xxxx 10xxxxxx * 2
			|   [\xE1-\xEC][\x80-\xBF]{2}
			|   \xED[\x80-\x9F][\x80-\xBF]
			|   [\xEE-\xEF][\x80-\xBF]{2}
			|   \xF0[\x90-\xBF][\x80-\xBF]{2} # four-byte sequences   11110xxx 10xxxxxx * 3
			|   [\xF1-\xF3][\x80-\xBF]{3}
			|   \xF4[\x80-\x8F][\x80-\xBF]{2}
		){1,40}                              # ...one or more times
		)/x';
            $location = preg_replace_callback($regex, '_wp_sanitize_utf8_in_redirect', $location);
            $location = preg_replace('|[^a-z0-9-~+_.?#=&;,/:%!*\[\]()@]|i', '', $location);
            $location = wp_kses_no_null($location);

            // Remove %0D and %0A from location.
            $strip = ['%0d', '%0a', '%0D', '%0A'];

            return _deep_replace($strip, $location);
        }

        function _wp_sanitize_utf8_in_redirect($matches)
        {
            return urlencode($matches[0]);
        }
    endif;

    if(! function_exists('wp_safe_redirect')) :

        function wp_safe_redirect($location, $status = 302, $x_redirect_by = 'WordPress')
        {
            // Need to look at the URL the way it will end up in wp_redirect().
            $location = wp_sanitize_redirect($location);

            $fallback_url = apply_filters('wp_safe_redirect_fallback', admin_url(), $status);

            $location = wp_validate_redirect($location, $fallback_url);

            return wp_redirect($location, $status, $x_redirect_by);
        }
    endif;

    if(! function_exists('wp_validate_redirect')) :

        function wp_validate_redirect($location, $fallback_url = '')
        {
            $location = wp_sanitize_redirect(trim($location, " \t\n\r\0\x08\x0B"));
            // Browsers will assume 'http' is your protocol, and will obey a redirect to a URL starting with '//'.
            if(str_starts_with($location, '//'))
            {
                $location = 'http:'.$location;
            }

            /*
		 * In PHP 5 parse_url() may fail if the URL query part contains 'http://'.
		 * See https://bugs.php.net/bug.php?id=38143
		 */
            $cut = strpos($location, '?');
            $test = $cut ? substr($location, 0, $cut) : $location;

            $lp = parse_url($test);

            // Give up if malformed URL.
            if(false === $lp)
            {
                return $fallback_url;
            }

            // Allow only 'http' and 'https' schemes. No 'data:', etc.
            if(isset($lp['scheme']) && ! ('http' === $lp['scheme'] || 'https' === $lp['scheme']))
            {
                return $fallback_url;
            }

            if(! isset($lp['host']) && ! empty($lp['path']) && '/' !== $lp['path'][0])
            {
                $path = '';
                if(! empty($_SERVER['REQUEST_URI']))
                {
                    $path = dirname(parse_url('http://placeholder'.$_SERVER['REQUEST_URI'], PHP_URL_PATH).'?');
                    $path = wp_normalize_path($path);
                }
                $location = '/'.ltrim($path.'/', '/').$location;
            }

            /*
		 * Reject if certain components are set but host is not.
		 * This catches URLs like https:host.com for which parse_url() does not set the host field.
		 */
            if(! isset($lp['host']) && (isset($lp['scheme']) || isset($lp['user']) || isset($lp['pass']) || isset($lp['port'])))
            {
                return $fallback_url;
            }

            // Reject malformed components parse_url() can return on odd inputs.
            foreach(['user', 'pass', 'host'] as $component)
            {
                if(isset($lp[$component]) && strpbrk($lp[$component], ':/?#@'))
                {
                    return $fallback_url;
                }
            }

            $wpp = parse_url(home_url());

            $allowed_hosts = (array) apply_filters('allowed_redirect_hosts', [$wpp['host']], isset($lp['host']) ? $lp['host'] : '');

            if(isset($lp['host']) && (! in_array($lp['host'], $allowed_hosts, true) && strtolower($wpp['host']) !== $lp['host']))
            {
                $location = $fallback_url;
            }

            return $location;
        }
    endif;

    if(! function_exists('wp_notify_postauthor')) :

        function wp_notify_postauthor($comment_id, $deprecated = null)
        {
            if(null !== $deprecated)
            {
                _deprecated_argument(__FUNCTION__, '3.8.0');
            }

            $comment = get_comment($comment_id);
            if(empty($comment) || empty($comment->comment_post_ID))
            {
                return false;
            }

            $post = get_post($comment->comment_post_ID);
            $author = get_userdata($post->post_author);

            // Who to notify? By default, just the post author, but others can be added.
            $emails = [];
            if($author)
            {
                $emails[] = $author->user_email;
            }

            $emails = apply_filters('comment_notification_recipients', $emails, $comment->comment_ID);
            $emails = array_filter($emails);

            // If there are no addresses to send the comment to, bail.
            if(! count($emails))
            {
                return false;
            }

            // Facilitate unsetting below without knowing the keys.
            $emails = array_flip($emails);

            $notify_author = apply_filters('comment_notification_notify_author', false, $comment->comment_ID);

            // The comment was left by the author.
            if($author && ! $notify_author && $comment->user_id == $post->post_author)
            {
                unset($emails[$author->user_email]);
            }

            // The author moderated a comment on their own post.
            if($author && ! $notify_author && get_current_user_id() == $post->post_author)
            {
                unset($emails[$author->user_email]);
            }

            // The post author is no longer a member of the blog.
            if($author && ! $notify_author && ! user_can($post->post_author, 'read_post', $post->ID))
            {
                unset($emails[$author->user_email]);
            }

            // If there's no email to send the comment to, bail, otherwise flip array back around for use below.
            if(! count($emails))
            {
                return false;
            }
            else
            {
                $emails = array_flip($emails);
            }

            $switched_locale = switch_to_locale(get_locale());

            $comment_author_domain = '';
            if(WP_Http::is_ip_address($comment->comment_author_IP))
            {
                $comment_author_domain = gethostbyaddr($comment->comment_author_IP);
            }

            /*
		 * The blogname option is escaped with esc_html() on the way into the database in sanitize_option().
		 * We want to reverse this for the plain text arena of emails.
		 */
            $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
            $comment_content = wp_specialchars_decode($comment->comment_content);

            switch($comment->comment_type)
            {
                case 'trackback':
                    /* translators: %s: Post title. */ $notify_message = sprintf(__('New trackback on your post "%s"'), $post->post_title)."\r\n";
                    /* translators: 1: Trackback/pingback website name, 2: Website IP address, 3: Website hostname. */
                    $notify_message .= sprintf(__('Website: %1$s (IP address: %2$s, %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain)."\r\n";
                    /* translators: %s: Trackback/pingback/comment author URL. */
                    $notify_message .= sprintf(__('URL: %s'), $comment->comment_author_url)."\r\n";
                    /* translators: %s: Comment text. */
                    $notify_message .= sprintf(__('Comment: %s'), "\r\n".$comment_content)."\r\n\r\n";
                    $notify_message .= __('You can see all trackbacks on this post here:')."\r\n";
                    /* translators: Trackback notification email subject. 1: Site title, 2: Post title. */
                    $subject = sprintf(__('[%1$s] Trackback: "%2$s"'), $blogname, $post->post_title);
                    break;

                case 'pingback':
                    /* translators: %s: Post title. */ $notify_message = sprintf(__('New pingback on your post "%s"'), $post->post_title)."\r\n";
                    /* translators: 1: Trackback/pingback website name, 2: Website IP address, 3: Website hostname. */
                    $notify_message .= sprintf(__('Website: %1$s (IP address: %2$s, %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain)."\r\n";
                    /* translators: %s: Trackback/pingback/comment author URL. */
                    $notify_message .= sprintf(__('URL: %s'), $comment->comment_author_url)."\r\n";
                    /* translators: %s: Comment text. */
                    $notify_message .= sprintf(__('Comment: %s'), "\r\n".$comment_content)."\r\n\r\n";
                    $notify_message .= __('You can see all pingbacks on this post here:')."\r\n";
                    /* translators: Pingback notification email subject. 1: Site title, 2: Post title. */
                    $subject = sprintf(__('[%1$s] Pingback: "%2$s"'), $blogname, $post->post_title);
                    break;

                default: // Comments.
                    /* translators: %s: Post title. */ $notify_message = sprintf(__('New comment on your post "%s"'), $post->post_title)."\r\n";
                    /* translators: 1: Comment author's name, 2: Comment author's IP address, 3: Comment author's hostname. */
                    $notify_message .= sprintf(__('Author: %1$s (IP address: %2$s, %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain)."\r\n";
                    /* translators: %s: Comment author email. */
                    $notify_message .= sprintf(__('Email: %s'), $comment->comment_author_email)."\r\n";
                    /* translators: %s: Trackback/pingback/comment author URL. */
                    $notify_message .= sprintf(__('URL: %s'), $comment->comment_author_url)."\r\n";

                    if($comment->comment_parent && user_can($post->post_author, 'edit_comment', $comment->comment_parent))
                    {
                        /* translators: Comment moderation. %s: Parent comment edit URL. */
                        $notify_message .= sprintf(__('In reply to: %s'), admin_url("comment.php?action=editcomment&c={$comment->comment_parent}#wpbody-content"))."\r\n";
                    }

                    /* translators: %s: Comment text. */
                    $notify_message .= sprintf(__('Comment: %s'), "\r\n".$comment_content)."\r\n\r\n";
                    $notify_message .= __('You can see all comments on this post here:')."\r\n";
                    /* translators: Comment notification email subject. 1: Site title, 2: Post title. */
                    $subject = sprintf(__('[%1$s] Comment: "%2$s"'), $blogname, $post->post_title);
                    break;
            }

            $notify_message .= get_permalink($comment->comment_post_ID)."#comments\r\n\r\n";
            /* translators: %s: Comment URL. */
            $notify_message .= sprintf(__('Permalink: %s'), get_comment_link($comment))."\r\n";

            if(user_can($post->post_author, 'edit_comment', $comment->comment_ID))
            {
                if(EMPTY_TRASH_DAYS)
                {
                    /* translators: Comment moderation. %s: Comment action URL. */
                    $notify_message .= sprintf(__('Trash it: %s'), admin_url("comment.php?action=trash&c={$comment->comment_ID}#wpbody-content"))."\r\n";
                }
                else
                {
                    /* translators: Comment moderation. %s: Comment action URL. */
                    $notify_message .= sprintf(__('Delete it: %s'), admin_url("comment.php?action=delete&c={$comment->comment_ID}#wpbody-content"))."\r\n";
                }
                /* translators: Comment moderation. %s: Comment action URL. */
                $notify_message .= sprintf(__('Spam it: %s'), admin_url("comment.php?action=spam&c={$comment->comment_ID}#wpbody-content"))."\r\n";
            }

            $wp_email = 'wordpress@'.preg_replace('#^www\.#', '', wp_parse_url(network_home_url(), PHP_URL_HOST));

            if('' === $comment->comment_author)
            {
                $from = "From: \"$blogname\" <$wp_email>";
                if('' !== $comment->comment_author_email)
                {
                    $reply_to = "Reply-To: $comment->comment_author_email";
                }
            }
            else
            {
                $from = "From: \"$comment->comment_author\" <$wp_email>";
                if('' !== $comment->comment_author_email)
                {
                    $reply_to = "Reply-To: \"$comment->comment_author_email\" <$comment->comment_author_email>";
                }
            }

            $message_headers = "$from\n".'Content-Type: text/plain; charset="'.get_option('blog_charset')."\"\n";

            if(isset($reply_to))
            {
                $message_headers .= $reply_to."\n";
            }

            $notify_message = apply_filters('comment_notification_text', $notify_message, $comment->comment_ID);

            $subject = apply_filters('comment_notification_subject', $subject, $comment->comment_ID);

            $message_headers = apply_filters('comment_notification_headers', $message_headers, $comment->comment_ID);

            foreach($emails as $email)
            {
                wp_mail($email, wp_specialchars_decode($subject), $notify_message, $message_headers);
            }

            if($switched_locale)
            {
                restore_previous_locale();
            }

            return true;
        }
    endif;

    if(! function_exists('wp_notify_moderator')) :

        function wp_notify_moderator($comment_id)
        {
            global $wpdb;

            $maybe_notify = get_option('moderation_notify');

            $maybe_notify = apply_filters('notify_moderator', $maybe_notify, $comment_id);

            if(! $maybe_notify)
            {
                return true;
            }

            $comment = get_comment($comment_id);
            $post = get_post($comment->comment_post_ID);
            $user = get_userdata($post->post_author);
            // Send to the administration and to the post author if the author can modify the comment.
            $emails = [get_option('admin_email')];
            if($user && user_can($user->ID, 'edit_comment', $comment_id) && ! empty($user->user_email))
            {
                if(0 !== strcasecmp($user->user_email, get_option('admin_email')))
                {
                    $emails[] = $user->user_email;
                }
            }

            $switched_locale = switch_to_locale(get_locale());

            $comment_author_domain = '';
            if(WP_Http::is_ip_address($comment->comment_author_IP))
            {
                $comment_author_domain = gethostbyaddr($comment->comment_author_IP);
            }

            $comments_waiting = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_approved = '0'");

            /*
		 * The blogname option is escaped with esc_html() on the way into the database in sanitize_option().
		 * We want to reverse this for the plain text arena of emails.
		 */
            $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
            $comment_content = wp_specialchars_decode($comment->comment_content);

            switch($comment->comment_type)
            {
                case 'trackback':
                    /* translators: %s: Post title. */ $notify_message = sprintf(__('A new trackback on the post "%s" is waiting for your approval'), $post->post_title)."\r\n";
                    $notify_message .= get_permalink($comment->comment_post_ID)."\r\n\r\n";
                    /* translators: 1: Trackback/pingback website name, 2: Website IP address, 3: Website hostname. */
                    $notify_message .= sprintf(__('Website: %1$s (IP address: %2$s, %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain)."\r\n";
                    /* translators: %s: Trackback/pingback/comment author URL. */
                    $notify_message .= sprintf(__('URL: %s'), $comment->comment_author_url)."\r\n";
                    $notify_message .= __('Trackback excerpt: ')."\r\n".$comment_content."\r\n\r\n";
                    break;

                case 'pingback':
                    /* translators: %s: Post title. */ $notify_message = sprintf(__('A new pingback on the post "%s" is waiting for your approval'), $post->post_title)."\r\n";
                    $notify_message .= get_permalink($comment->comment_post_ID)."\r\n\r\n";
                    /* translators: 1: Trackback/pingback website name, 2: Website IP address, 3: Website hostname. */
                    $notify_message .= sprintf(__('Website: %1$s (IP address: %2$s, %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain)."\r\n";
                    /* translators: %s: Trackback/pingback/comment author URL. */
                    $notify_message .= sprintf(__('URL: %s'), $comment->comment_author_url)."\r\n";
                    $notify_message .= __('Pingback excerpt: ')."\r\n".$comment_content."\r\n\r\n";
                    break;

                default: // Comments.
                    /* translators: %s: Post title. */ $notify_message = sprintf(__('A new comment on the post "%s" is waiting for your approval'), $post->post_title)."\r\n";
                    $notify_message .= get_permalink($comment->comment_post_ID)."\r\n\r\n";
                    /* translators: 1: Comment author's name, 2: Comment author's IP address, 3: Comment author's hostname. */
                    $notify_message .= sprintf(__('Author: %1$s (IP address: %2$s, %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain)."\r\n";
                    /* translators: %s: Comment author email. */
                    $notify_message .= sprintf(__('Email: %s'), $comment->comment_author_email)."\r\n";
                    /* translators: %s: Trackback/pingback/comment author URL. */
                    $notify_message .= sprintf(__('URL: %s'), $comment->comment_author_url)."\r\n";

                    if($comment->comment_parent)
                    {
                        /* translators: Comment moderation. %s: Parent comment edit URL. */
                        $notify_message .= sprintf(__('In reply to: %s'), admin_url("comment.php?action=editcomment&c={$comment->comment_parent}#wpbody-content"))."\r\n";
                    }

                    /* translators: %s: Comment text. */
                    $notify_message .= sprintf(__('Comment: %s'), "\r\n".$comment_content)."\r\n\r\n";
                    break;
            }

            /* translators: Comment moderation. %s: Comment action URL. */
            $notify_message .= sprintf(__('Approve it: %s'), admin_url("comment.php?action=approve&c={$comment_id}#wpbody-content"))."\r\n";

            if(EMPTY_TRASH_DAYS)
            {
                /* translators: Comment moderation. %s: Comment action URL. */
                $notify_message .= sprintf(__('Trash it: %s'), admin_url("comment.php?action=trash&c={$comment_id}#wpbody-content"))."\r\n";
            }
            else
            {
                /* translators: Comment moderation. %s: Comment action URL. */
                $notify_message .= sprintf(__('Delete it: %s'), admin_url("comment.php?action=delete&c={$comment_id}#wpbody-content"))."\r\n";
            }

            /* translators: Comment moderation. %s: Comment action URL. */
            $notify_message .= sprintf(__('Spam it: %s'), admin_url("comment.php?action=spam&c={$comment_id}#wpbody-content"))."\r\n";

            $notify_message .= sprintf(/* translators: Comment moderation. %s: Number of comments awaiting approval. */ _n('Currently %s comment is waiting for approval. Please visit the moderation panel:', 'Currently %s comments are waiting for approval. Please visit the moderation panel:', $comments_waiting), number_format_i18n($comments_waiting))."\r\n";
            $notify_message .= admin_url('edit-comments.php?comment_status=moderated#wpbody-content')."\r\n";

            /* translators: Comment moderation notification email subject. 1: Site title, 2: Post title. */
            $subject = sprintf(__('[%1$s] Please moderate: "%2$s"'), $blogname, $post->post_title);
            $message_headers = '';

            $emails = apply_filters('comment_moderation_recipients', $emails, $comment_id);

            $notify_message = apply_filters('comment_moderation_text', $notify_message, $comment_id);

            $subject = apply_filters('comment_moderation_subject', $subject, $comment_id);

            $message_headers = apply_filters('comment_moderation_headers', $message_headers, $comment_id);

            foreach($emails as $email)
            {
                wp_mail($email, wp_specialchars_decode($subject), $notify_message, $message_headers);
            }

            if($switched_locale)
            {
                restore_previous_locale();
            }

            return true;
        }
    endif;

    if(! function_exists('wp_password_change_notification')) :

        function wp_password_change_notification($user)
        {
            /*
		 * Send a copy of password change notification to the admin,
		 * but check to see if it's the admin whose password we're changing, and skip this.
		 */
            if(0 !== strcasecmp($user->user_email, get_option('admin_email')))
            {
                /* translators: %s: User name. */
                $message = sprintf(__('Password changed for user: %s'), $user->user_login)."\r\n";
                /*
			 * The blogname option is escaped with esc_html() on the way into the database in sanitize_option().
			 * We want to reverse this for the plain text arena of emails.
			 */
                $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

                $wp_password_change_notification_email = [
                    'to' => get_option('admin_email'),
                    /* translators: Password change notification email subject. %s: Site title. */
                    'subject' => __('[%s] Password Changed'),
                    'message' => $message,
                    'headers' => '',
                ];

                $wp_password_change_notification_email = apply_filters('wp_password_change_notification_email', $wp_password_change_notification_email, $user, $blogname);

                wp_mail($wp_password_change_notification_email['to'], wp_specialchars_decode(sprintf($wp_password_change_notification_email['subject'], $blogname)), $wp_password_change_notification_email['message'], $wp_password_change_notification_email['headers']);
            }
        }
    endif;

    if(! function_exists('wp_new_user_notification')) :

        function wp_new_user_notification($user_id, $deprecated = null, $notify = '')
        {
            if(null !== $deprecated)
            {
                _deprecated_argument(__FUNCTION__, '4.3.1');
            }

            // Accepts only 'user', 'admin' , 'both' or default '' as $notify.
            if(! in_array($notify, ['user', 'admin', 'both', ''], true))
            {
                return;
            }

            $user = get_userdata($user_id);

            /*
		 * The blogname option is escaped with esc_html() on the way into the database in sanitize_option().
		 * We want to reverse this for the plain text arena of emails.
		 */
            $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

            $send_notification_to_admin = apply_filters('wp_send_new_user_notification_to_admin', true, $user);

            if('user' !== $notify && true === $send_notification_to_admin)
            {
                $switched_locale = switch_to_locale(get_locale());

                /* translators: %s: Site title. */
                $message = sprintf(__('New user registration on your site %s:'), $blogname)."\r\n\r\n";
                /* translators: %s: User login. */
                $message .= sprintf(__('Username: %s'), $user->user_login)."\r\n\r\n";
                /* translators: %s: User email address. */
                $message .= sprintf(__('Email: %s'), $user->user_email)."\r\n";

                $wp_new_user_notification_email_admin = [
                    'to' => get_option('admin_email'),
                    /* translators: New user registration notification email subject. %s: Site title. */
                    'subject' => __('[%s] New User Registration'),
                    'message' => $message,
                    'headers' => '',
                ];

                $wp_new_user_notification_email_admin = apply_filters('wp_new_user_notification_email_admin', $wp_new_user_notification_email_admin, $user, $blogname);

                wp_mail($wp_new_user_notification_email_admin['to'], wp_specialchars_decode(sprintf($wp_new_user_notification_email_admin['subject'], $blogname)), $wp_new_user_notification_email_admin['message'], $wp_new_user_notification_email_admin['headers']);

                if($switched_locale)
                {
                    restore_previous_locale();
                }
            }

            $send_notification_to_user = apply_filters('wp_send_new_user_notification_to_user', true, $user);

            // `$deprecated` was pre-4.3 `$plaintext_pass`. An empty `$plaintext_pass` didn't sent a user notification.
            if('admin' === $notify || true !== $send_notification_to_user || (empty($deprecated) && empty($notify)))
            {
                return;
            }

            $key = get_password_reset_key($user);
            if(is_wp_error($key))
            {
                return;
            }

            $switched_locale = switch_to_user_locale($user_id);

            /* translators: %s: User login. */
            $message = sprintf(__('Username: %s'), $user->user_login)."\r\n\r\n";
            $message .= __('To set your password, visit the following address:')."\r\n\r\n";
            $message .= network_site_url("wp-login.php?action=rp&key=$key&login=".rawurlencode($user->user_login), 'login')."\r\n\r\n";

            $message .= wp_login_url()."\r\n";

            $wp_new_user_notification_email = [
                'to' => $user->user_email,
                /* translators: Login details notification email subject. %s: Site title. */
                'subject' => __('[%s] Login Details'),
                'message' => $message,
                'headers' => '',
            ];

            $wp_new_user_notification_email = apply_filters('wp_new_user_notification_email', $wp_new_user_notification_email, $user, $blogname);

            wp_mail($wp_new_user_notification_email['to'], wp_specialchars_decode(sprintf($wp_new_user_notification_email['subject'], $blogname)), $wp_new_user_notification_email['message'], $wp_new_user_notification_email['headers']);

            if($switched_locale)
            {
                restore_previous_locale();
            }
        }
    endif;

    if(! function_exists('wp_nonce_tick')) :

        function wp_nonce_tick($action = -1)
        {
            $nonce_life = apply_filters('nonce_life', DAY_IN_SECONDS, $action);

            return ceil(time() / ($nonce_life / 2));
        }
    endif;

    if(! function_exists('wp_verify_nonce')) :

        function wp_verify_nonce($nonce, $action = -1)
        {
            $nonce = (string) $nonce;
            $user = wp_get_current_user();
            $uid = (int) $user->ID;
            if(! $uid)
            {
                $uid = apply_filters('nonce_user_logged_out', $uid, $action);
            }

            if(empty($nonce))
            {
                return false;
            }

            $token = wp_get_session_token();
            $i = wp_nonce_tick($action);

            // Nonce generated 0-12 hours ago.
            $expected = substr(wp_hash($i.'|'.$action.'|'.$uid.'|'.$token, 'nonce'), -12, 10);
            if(hash_equals($expected, $nonce))
            {
                return 1;
            }

            // Nonce generated 12-24 hours ago.
            $expected = substr(wp_hash(($i - 1).'|'.$action.'|'.$uid.'|'.$token, 'nonce'), -12, 10);
            if(hash_equals($expected, $nonce))
            {
                return 2;
            }

            do_action('wp_verify_nonce_failed', $nonce, $action, $user, $token);

            // Invalid nonce.
            return false;
        }
    endif;

    if(! function_exists('wp_create_nonce')) :

        function wp_create_nonce($action = -1)
        {
            $user = wp_get_current_user();
            $uid = (int) $user->ID;
            if(! $uid)
            {
                $uid = apply_filters('nonce_user_logged_out', $uid, $action);
            }

            $token = wp_get_session_token();
            $i = wp_nonce_tick($action);

            return substr(wp_hash($i.'|'.$action.'|'.$uid.'|'.$token, 'nonce'), -12, 10);
        }
    endif;

    if(! function_exists('wp_salt')) :

        function wp_salt($scheme = 'auth')
        {
            static $cached_salts = [];
            if(isset($cached_salts[$scheme]))
            {
                return apply_filters('salt', $cached_salts[$scheme], $scheme);
            }

            static $duplicated_keys;
            if(null === $duplicated_keys)
            {
                $duplicated_keys = [
                    'put your unique phrase here' => true,
                ];

                /*
			 * translators: This string should only be translated if wp-config-sample.php is localized.
			 * You can check the localized release package or
			 * https://i18n.svn.wordpress.org/<locale code>/branches/<wp version>/dist/wp-config-sample.php
			 */
                $duplicated_keys[__('put your unique phrase here')] = true;

                foreach(['AUTH', 'SECURE_AUTH', 'LOGGED_IN', 'NONCE', 'SECRET'] as $first)
                {
                    foreach(['KEY', 'SALT'] as $second)
                    {
                        if(! defined("{$first}_{$second}"))
                        {
                            continue;
                        }
                        $value = constant("{$first}_{$second}");
                        $duplicated_keys[$value] = isset($duplicated_keys[$value]);
                    }
                }
            }

            $values = [
                'key' => '',
                'salt' => '',
            ];
            if(defined('SECRET_KEY') && SECRET_KEY && empty($duplicated_keys[SECRET_KEY]))
            {
                $values['key'] = SECRET_KEY;
            }
            if('auth' === $scheme && defined('SECRET_SALT') && SECRET_SALT && empty($duplicated_keys[SECRET_SALT]))
            {
                $values['salt'] = SECRET_SALT;
            }

            if(in_array($scheme, ['auth', 'secure_auth', 'logged_in', 'nonce'], true))
            {
                foreach(['key', 'salt'] as $type)
                {
                    $const = strtoupper("{$scheme}_{$type}");
                    if(defined($const) && constant($const) && empty($duplicated_keys[constant($const)]))
                    {
                        $values[$type] = constant($const);
                    }
                    elseif(! $values[$type])
                    {
                        $values[$type] = get_site_option("{$scheme}_{$type}");
                        if(! $values[$type])
                        {
                            $values[$type] = wp_generate_password(64, true, true);
                            update_site_option("{$scheme}_{$type}", $values[$type]);
                        }
                    }
                }
            }
            else
            {
                if(! $values['key'])
                {
                    $values['key'] = get_site_option('secret_key');
                    if(! $values['key'])
                    {
                        $values['key'] = wp_generate_password(64, true, true);
                        update_site_option('secret_key', $values['key']);
                    }
                }
                $values['salt'] = hash_hmac('md5', $scheme, $values['key']);
            }

            $cached_salts[$scheme] = $values['key'].$values['salt'];

            return apply_filters('salt', $cached_salts[$scheme], $scheme);
        }
    endif;

    if(! function_exists('wp_hash')) :

        function wp_hash($data, $scheme = 'auth')
        {
            $salt = wp_salt($scheme);

            return hash_hmac('md5', $data, $salt);
        }
    endif;

    if(! function_exists('wp_hash_password')) :

        function wp_hash_password($password)
        {
            global $wp_hasher;

            if(empty($wp_hasher))
            {
                require_once ABSPATH.WPINC.'/class-phpass.php';
                // By default, use the portable hash from phpass.
                $wp_hasher = new PasswordHash(8, true);
            }

            return $wp_hasher->HashPassword(trim($password));
        }
    endif;

    if(! function_exists('wp_check_password')) :

        function wp_check_password($password, $hash, $user_id = '')
        {
            global $wp_hasher;

            // If the hash is still md5...
            if(strlen($hash) <= 32)
            {
                $check = hash_equals($hash, md5($password));
                if($check && $user_id)
                {
                    // Rehash using new hash.
                    wp_set_password($password, $user_id);
                    $hash = wp_hash_password($password);
                }

                return apply_filters('check_password', $check, $password, $hash, $user_id);
            }

            /*
		 * If the stored hash is longer than an MD5,
		 * presume the new style phpass portable hash.
		 */
            if(empty($wp_hasher))
            {
                require_once ABSPATH.WPINC.'/class-phpass.php';
                // By default, use the portable hash from phpass.
                $wp_hasher = new PasswordHash(8, true);
            }

            $check = $wp_hasher->CheckPassword($password, $hash);

            return apply_filters('check_password', $check, $password, $hash, $user_id);
        }
    endif;

    if(! function_exists('wp_generate_password')) :

        function wp_generate_password($length = 12, $special_chars = true, $extra_special_chars = false)
        {
            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            if($special_chars)
            {
                $chars .= '!@#$%^&*()';
            }
            if($extra_special_chars)
            {
                $chars .= '-_ []{}<>~`+=,.;:/?|';
            }

            $password = '';
            for($i = 0; $i < $length; $i++)
            {
                $password .= substr($chars, wp_rand(0, strlen($chars) - 1), 1);
            }

            return apply_filters('random_password', $password, $length, $special_chars, $extra_special_chars);
        }
    endif;

    if(! function_exists('wp_rand')) :

        function wp_rand($min = null, $max = null)
        {
            global $rnd_value;

            /*
		 * Some misconfigured 32-bit environments (Entropy PHP, for example)
		 * truncate integers larger than PHP_INT_MAX to PHP_INT_MAX rather than overflowing them to floats.
		 */
            $max_random_number = 3000000000 === 2147483647 ? (float) '4294967295' : 4294967295; // 4294967295 = 0xffffffff

            if(null === $min)
            {
                $min = 0;
            }

            if(null === $max)
            {
                $max = $max_random_number;
            }

            // We only handle ints, floats are truncated to their integer value.
            $min = (int) $min;
            $max = (int) $max;

            // Use PHP's CSPRNG, or a compatible method.
            static $use_random_int_functionality = true;
            if($use_random_int_functionality)
            {
                try
                {
                    // wp_rand() can accept arguments in either order, PHP cannot.
                    $_max = max($min, $max);
                    $_min = min($min, $max);
                    $val = random_int($_min, $_max);
                    if(false !== $val)
                    {
                        return absint($val);
                    }
                    else
                    {
                        $use_random_int_functionality = false;
                    }
                }
                catch(Error $e)
                {
                    $use_random_int_functionality = false;
                }
                catch(Exception $e)
                {
                    $use_random_int_functionality = false;
                }
            }

            /*
		 * Reset $rnd_value after 14 uses.
		 * 32 (md5) + 40 (sha1) + 40 (sha1) / 8 = 14 random numbers from $rnd_value.
		 */
            if(strlen($rnd_value) < 8)
            {
                if(defined('WP_SETUP_CONFIG'))
                {
                    static $seed = '';
                }
                else
                {
                    $seed = get_transient('random_seed');
                }
                $rnd_value = md5(uniqid(microtime().mt_rand(), true).$seed);
                $rnd_value .= sha1($rnd_value);
                $rnd_value .= sha1($rnd_value.$seed);
                $seed = md5($seed.$rnd_value);
                if(! defined('WP_SETUP_CONFIG') && ! defined('WP_INSTALLING'))
                {
                    set_transient('random_seed', $seed);
                }
            }

            // Take the first 8 digits for our value.
            $value = substr($rnd_value, 0, 8);

            // Strip the first eight, leaving the remainder for the next call to wp_rand().
            $rnd_value = substr($rnd_value, 8);

            $value = abs(hexdec($value));

            // Reduce the value to be within the min - max range.
            $value = $min + ($max - $min + 1) * $value / ($max_random_number + 1);

            return abs((int) $value);
        }
    endif;

    if(! function_exists('wp_set_password')) :

        function wp_set_password($password, $user_id)
        {
            global $wpdb;

            $hash = wp_hash_password($password);
            $wpdb->update($wpdb->users, [
                'user_pass' => $hash,
                'user_activation_key' => '',
            ],            ['ID' => $user_id]);

            clean_user_cache($user_id);

            do_action('wp_set_password', $password, $user_id);
        }
    endif;

    if(! function_exists('get_avatar')) :

        function get_avatar($id_or_email, $size = 96, $default_value = '', $alt = '', $args = null)
        {
            $defaults = [
                // get_avatar_data() args.
                'size' => 96,
                'height' => null,
                'width' => null,
                'default' => get_option('avatar_default', 'mystery'),
                'force_default' => false,
                'rating' => get_option('avatar_rating'),
                'scheme' => null,
                'alt' => '',
                'class' => null,
                'force_display' => false,
                'loading' => null,
                'fetchpriority' => null,
                'extra_attr' => '',
                'decoding' => 'async',
            ];

            if(empty($args))
            {
                $args = [];
            }

            $args['size'] = (int) $size;
            $args['default'] = $default_value;
            $args['alt'] = $alt;

            $args = wp_parse_args($args, $defaults);

            if(empty($args['height']))
            {
                $args['height'] = $args['size'];
            }
            if(empty($args['width']))
            {
                $args['width'] = $args['size'];
            }

            // Update args with loading optimized attributes.
            $loading_optimization_attr = wp_get_loading_optimization_attributes('img', $args, 'get_avatar');

            $args = array_merge($args, $loading_optimization_attr);

            if(is_object($id_or_email) && isset($id_or_email->comment_ID))
            {
                $id_or_email = get_comment($id_or_email);
            }

            $avatar = apply_filters('pre_get_avatar', null, $id_or_email, $args);

            if(! is_null($avatar))
            {
                return apply_filters('get_avatar', $avatar, $id_or_email, $args['size'], $args['default'], $args['alt'], $args);
            }

            if(! $args['force_display'] && ! get_option('show_avatars'))
            {
                return false;
            }

            $url2x = get_avatar_url($id_or_email, array_merge($args, ['size' => $args['size'] * 2]));

            $args = get_avatar_data($id_or_email, $args);

            $url = $args['url'];

            if(! $url || is_wp_error($url))
            {
                return false;
            }

            $class = ['avatar', 'avatar-'.(int) $args['size'], 'photo'];

            if(! $args['found_avatar'] || $args['force_default'])
            {
                $class[] = 'avatar-default';
            }

            if($args['class'])
            {
                if(is_array($args['class']))
                {
                    $class = array_merge($class, $args['class']);
                }
                else
                {
                    $class[] = $args['class'];
                }
            }

            // Add `loading`, `fetchpriority` and `decoding` attributes.
            $extra_attr = $args['extra_attr'];

            if(in_array($args['loading'], ['lazy', 'eager'], true) && ! preg_match('/\bloading\s*=/', $extra_attr))
            {
                if(! empty($extra_attr))
                {
                    $extra_attr .= ' ';
                }

                $extra_attr .= "loading='{$args['loading']}'";
            }

            if(
                in_array($args['decoding'], [
                    'async',
                    'sync',
                    'auto'
                ],       true) && ! preg_match('/\bdecoding\s*=/', $extra_attr)
            )
            {
                if(! empty($extra_attr))
                {
                    $extra_attr .= ' ';
                }

                $extra_attr .= "decoding='{$args['decoding']}'";
            }

            // Add support for `fetchpriority`.
            if(
                in_array($args['fetchpriority'], [
                    'high',
                    'low',
                    'auto'
                ],       true) && ! preg_match('/\bfetchpriority\s*=/', $extra_attr)
            )
            {
                if(! empty($extra_attr))
                {
                    $extra_attr .= ' ';
                }

                $extra_attr .= "fetchpriority='{$args['fetchpriority']}'";
            }

            $avatar = sprintf("<img alt='%s' src='%s' srcset='%s' class='%s' height='%d' width='%d' %s/>", esc_attr($args['alt']), esc_url($url), esc_url($url2x).' 2x', esc_attr(implode(' ', $class)), (int) $args['height'], (int) $args['width'], $extra_attr);

            return apply_filters('get_avatar', $avatar, $id_or_email, $args['size'], $args['default'], $args['alt'], $args);
        }
    endif;

    if(! function_exists('wp_text_diff')) :

        function wp_text_diff($left_string, $right_string, $args = null)
        {
            $defaults = [
                'title' => '',
                'title_left' => '',
                'title_right' => '',
                'show_split_view' => true,
            ];
            $args = wp_parse_args($args, $defaults);

            if(! class_exists('WP_Text_Diff_Renderer_Table', false))
            {
                require ABSPATH.WPINC.'/wp-diff.php';
            }

            $left_string = normalize_whitespace($left_string);
            $right_string = normalize_whitespace($right_string);

            $left_lines = explode("\n", $left_string);
            $right_lines = explode("\n", $right_string);
            $text_diff = new Text_Diff($left_lines, $right_lines);
            $renderer = new WP_Text_Diff_Renderer_Table($args);
            $diff = $renderer->render($text_diff);

            if(! $diff)
            {
                return '';
            }

            $is_split_view = ! empty($args['show_split_view']);
            $is_split_view_class = $is_split_view ? ' is-split-view' : '';

            $r = "<table class='diff$is_split_view_class'>\n";

            if($args['title'])
            {
                $r .= "<caption class='diff-title'>$args[title]</caption>\n";
            }

            if($args['title_left'] || $args['title_right'])
            {
                $r .= '<thead>';
            }

            if($args['title_left'] || $args['title_right'])
            {
                $th_or_td_left = empty($args['title_left']) ? 'td' : 'th';
                $th_or_td_right = empty($args['title_right']) ? 'td' : 'th';

                $r .= "<tr class='diff-sub-title'>\n";
                $r .= "\t<$th_or_td_left>$args[title_left]</$th_or_td_left>\n";
                if($is_split_view)
                {
                    $r .= "\t<$th_or_td_right>$args[title_right]</$th_or_td_right>\n";
                }
                $r .= "</tr>\n";
            }

            if($args['title_left'] || $args['title_right'])
            {
                $r .= "</thead>\n";
            }

            $r .= "<tbody>\n$diff\n</tbody>\n";
            $r .= '</table>';

            return $r;
        }
    endif;
