<?php

    /*
     * Deprecated functions come here to die.
     */

    if(! function_exists('set_current_user')) :

        function set_current_user($id, $name = '')
        {
            _deprecated_function(__FUNCTION__, '3.0.0', 'wp_set_current_user()');

            return wp_set_current_user($id, $name);
        }
    endif;

    if(! function_exists('get_currentuserinfo')) :

        function get_currentuserinfo()
        {
            _deprecated_function(__FUNCTION__, '4.5.0', 'wp_get_current_user()');

            return _wp_get_current_user();
        }
    endif;

    if(! function_exists('get_userdatabylogin')) :

        function get_userdatabylogin($user_login)
        {
            _deprecated_function(__FUNCTION__, '3.3.0', "get_user_by('login')");

            return get_user_by('login', $user_login);
        }
    endif;

    if(! function_exists('get_user_by_email')) :

        function get_user_by_email($email)
        {
            _deprecated_function(__FUNCTION__, '3.3.0', "get_user_by('email')");

            return get_user_by('email', $email);
        }
    endif;

    if(function_exists('wp_setcookie')) :

        _deprecated_function('wp_setcookie', '2.5.0', 'wp_set_auth_cookie()');
    else :
        function wp_setcookie(
            $username, $password = '', $already_md5 = false, $home = '', $siteurl = '', $remember = false
        ) {
            _deprecated_function(__FUNCTION__, '2.5.0', 'wp_set_auth_cookie()');
            $user = get_user_by('login', $username);
            wp_set_auth_cookie($user->ID, $remember);
        }
    endif;

    if(function_exists('wp_clearcookie')) :

        _deprecated_function('wp_clearcookie', '2.5.0', 'wp_clear_auth_cookie()');
    else :
        function wp_clearcookie()
        {
            _deprecated_function(__FUNCTION__, '2.5.0', 'wp_clear_auth_cookie()');
            wp_clear_auth_cookie();
        }
    endif;

    if(function_exists('wp_get_cookie_login')):

        _deprecated_function('wp_get_cookie_login', '2.5.0');
    else :
        function wp_get_cookie_login()
        {
            _deprecated_function(__FUNCTION__, '2.5.0');

            return false;
        }
    endif;

    if(function_exists('wp_login')) :

        _deprecated_function('wp_login', '2.5.0', 'wp_signon()');
    else :
        function wp_login($username, $password, $deprecated = '')
        {
            _deprecated_function(__FUNCTION__, '2.5.0', 'wp_signon()');
            global $error;

            $user = wp_authenticate($username, $password);

            if(! is_wp_error($user))
            {
                return true;
            }

            $error = $user->get_error_message();

            return false;
        }
    endif;

    if(! class_exists('wp_atom_server', false))
    {
        class wp_atom_server
        {
            public function __call($name, $arguments)
            {
                _deprecated_function(__CLASS__.'::'.$name, '3.5.0', 'the Atom Publishing Protocol plugin');
            }

            public static function __callStatic($name, $arguments)
            {
                _deprecated_function(__CLASS__.'::'.$name, '3.5.0', 'the Atom Publishing Protocol plugin');
            }
        }
    }
