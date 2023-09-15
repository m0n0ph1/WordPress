<?php

    #[AllowDynamicProperties]
    final class WP_Recovery_Mode_Key_Service
    {
        private $option_name = 'recovery_keys';

        public function generate_recovery_mode_token()
        {
            return wp_generate_password(22, false);
        }

        public function generate_and_store_recovery_mode_key($token)
        {
            global $wp_hasher;

            $key = wp_generate_password(22, false);

            if(empty($wp_hasher))
            {
                require_once ABSPATH.WPINC.'/class-phpass.php';
                $wp_hasher = new PasswordHash(8, true);
            }

            $hashed = $wp_hasher->HashPassword($key);

            $records = $this->get_keys();

            $records[$token] = [
                'hashed_key' => $hashed,
                'created_at' => time(),
            ];

            $this->update_keys($records);

            do_action('generate_recovery_mode_key', $token, $key);

            return $key;
        }

        private function get_keys()
        {
            return (array) get_option($this->option_name, []);
        }

        private function update_keys(array $keys)
        {
            return update_option($this->option_name, $keys);
        }

        public function validate_recovery_mode_key($token, $key, $ttl)
        {
            global $wp_hasher;

            $records = $this->get_keys();

            if(! isset($records[$token]))
            {
                return new WP_Error('token_not_found', __('Recovery Mode not initialized.'));
            }

            $record = $records[$token];

            $this->remove_key($token);

            if(! is_array($record) || ! isset($record['hashed_key'], $record['created_at']))
            {
                return new WP_Error('invalid_recovery_key_format', __('Invalid recovery key format.'));
            }

            if(empty($wp_hasher))
            {
                require_once ABSPATH.WPINC.'/class-phpass.php';
                $wp_hasher = new PasswordHash(8, true);
            }

            if(! $wp_hasher->CheckPassword($key, $record['hashed_key']))
            {
                return new WP_Error('hash_mismatch', __('Invalid recovery key.'));
            }

            if(time() > $record['created_at'] + $ttl)
            {
                return new WP_Error('key_expired', __('Recovery key expired.'));
            }

            return true;
        }

        private function remove_key($token)
        {
            $records = $this->get_keys();

            if(! isset($records[$token]))
            {
                return;
            }

            unset($records[$token]);

            $this->update_keys($records);
        }

        public function clean_expired_keys($ttl)
        {
            $records = $this->get_keys();

            foreach($records as $key => $record)
            {
                if(! isset($record['created_at']) || time() > $record['created_at'] + $ttl)
                {
                    unset($records[$key]);
                }
            }

            $this->update_keys($records);
        }
    }
