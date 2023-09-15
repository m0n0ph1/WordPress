<?php

    #[AllowDynamicProperties]
    class WP_Application_Passwords
    {
        public const USERMETA_KEY_APPLICATION_PASSWORDS = '_application_passwords';

        public const OPTION_KEY_IN_USE = 'using_application_passwords';

        public const PW_LENGTH = 24;

        public static function is_in_use()
        {
            $network_id = get_main_network_id();

            return (bool) get_network_option($network_id, self::OPTION_KEY_IN_USE);
        }

        public static function create_new_application_password($user_id, $args = [])
        {
            if(! empty($args['name']))
            {
                $args['name'] = sanitize_text_field($args['name']);
            }

            if(empty($args['name']))
            {
                return new WP_Error('application_password_empty_name', __('An application name is required to create an application password.'), ['status' => 400]);
            }

            if(self::application_name_exists_for_user($user_id, $args['name']))
            {
                return new WP_Error('application_password_duplicate_name', __('Each application name should be unique.'), ['status' => 409]);
            }

            $new_password = wp_generate_password(static::PW_LENGTH, false);
            $hashed_password = wp_hash_password($new_password);

            $new_item = [
                'uuid' => wp_generate_uuid4(),
                'app_id' => empty($args['app_id']) ? '' : $args['app_id'],
                'name' => $args['name'],
                'password' => $hashed_password,
                'created' => time(),
                'last_used' => null,
                'last_ip' => null,
            ];

            $passwords = static::get_user_application_passwords($user_id);
            $passwords[] = $new_item;
            $saved = static::set_user_application_passwords($user_id, $passwords);

            if(! $saved)
            {
                return new WP_Error('db_error', __('Could not save application password.'));
            }

            $network_id = get_main_network_id();
            if(! get_network_option($network_id, self::OPTION_KEY_IN_USE))
            {
                update_network_option($network_id, self::OPTION_KEY_IN_USE, true);
            }

            do_action('wp_create_application_password', $user_id, $new_item, $new_password, $args);

            return [$new_password, $new_item];
        }

        public static function application_name_exists_for_user($user_id, $name)
        {
            $passwords = static::get_user_application_passwords($user_id);

            foreach($passwords as $password)
            {
                if(strtolower($password['name']) === strtolower($name))
                {
                    return true;
                }
            }

            return false;
        }

        public static function get_user_application_passwords($user_id)
        {
            $passwords = get_user_meta($user_id, static::USERMETA_KEY_APPLICATION_PASSWORDS, true);

            if(! is_array($passwords))
            {
                return [];
            }

            $save = false;

            foreach($passwords as $i => $password)
            {
                if(! isset($password['uuid']))
                {
                    $passwords[$i]['uuid'] = wp_generate_uuid4();
                    $save = true;
                }
            }

            if($save)
            {
                static::set_user_application_passwords($user_id, $passwords);
            }

            return $passwords;
        }

        protected static function set_user_application_passwords($user_id, $passwords)
        {
            return update_user_meta($user_id, static::USERMETA_KEY_APPLICATION_PASSWORDS, $passwords);
        }

        public static function get_user_application_password($user_id, $uuid)
        {
            $passwords = static::get_user_application_passwords($user_id);

            foreach($passwords as $password)
            {
                if($password['uuid'] === $uuid)
                {
                    return $password;
                }
            }

            return null;
        }

        public static function update_application_password($user_id, $uuid, $update = [])
        {
            $passwords = static::get_user_application_passwords($user_id);

            foreach($passwords as &$item)
            {
                if($item['uuid'] !== $uuid)
                {
                    continue;
                }

                if(! empty($update['name']))
                {
                    $update['name'] = sanitize_text_field($update['name']);
                }

                $save = false;

                if(! empty($update['name']) && $item['name'] !== $update['name'])
                {
                    $item['name'] = $update['name'];
                    $save = true;
                }

                if($save)
                {
                    $saved = static::set_user_application_passwords($user_id, $passwords);

                    if(! $saved)
                    {
                        return new WP_Error('db_error', __('Could not save application password.'));
                    }
                }

                do_action('wp_update_application_password', $user_id, $item, $update);

                return true;
            }

            return new WP_Error('application_password_not_found', __('Could not find an application password with that id.'));
        }

        public static function record_application_password_usage($user_id, $uuid)
        {
            $passwords = static::get_user_application_passwords($user_id);

            foreach($passwords as &$password)
            {
                if($password['uuid'] !== $uuid)
                {
                    continue;
                }

                // Only record activity once a day.
                if($password['last_used'] + DAY_IN_SECONDS > time())
                {
                    return true;
                }

                $password['last_used'] = time();
                $password['last_ip'] = $_SERVER['REMOTE_ADDR'];

                $saved = static::set_user_application_passwords($user_id, $passwords);

                if(! $saved)
                {
                    return new WP_Error('db_error', __('Could not save application password.'));
                }

                return true;
            }

            // Specified application password not found!
            return new WP_Error('application_password_not_found', __('Could not find an application password with that id.'));
        }

        public static function delete_application_password($user_id, $uuid)
        {
            $passwords = static::get_user_application_passwords($user_id);

            foreach($passwords as $key => $item)
            {
                if($item['uuid'] === $uuid)
                {
                    unset($passwords[$key]);
                    $saved = static::set_user_application_passwords($user_id, $passwords);

                    if(! $saved)
                    {
                        return new WP_Error('db_error', __('Could not delete application password.'));
                    }

                    do_action('wp_delete_application_password', $user_id, $item);

                    return true;
                }
            }

            return new WP_Error('application_password_not_found', __('Could not find an application password with that id.'));
        }

        public static function delete_all_application_passwords($user_id)
        {
            $passwords = static::get_user_application_passwords($user_id);

            if($passwords)
            {
                $saved = static::set_user_application_passwords($user_id, []);

                if(! $saved)
                {
                    return new WP_Error('db_error', __('Could not delete application passwords.'));
                }

                foreach($passwords as $item)
                {
                    do_action('wp_delete_application_password', $user_id, $item);
                }

                return count($passwords);
            }

            return 0;
        }

        public static function chunk_password($raw_password)
        {
            $raw_password = preg_replace('/[^a-z\d]/i', '', $raw_password);

            return trim(chunk_split($raw_password, 4, ' '));
        }
    }
