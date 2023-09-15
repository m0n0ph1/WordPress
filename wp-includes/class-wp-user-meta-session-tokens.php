<?php

    class WP_User_Meta_Session_Tokens extends WP_Session_Tokens
    {
        public static function drop_sessions()
        {
            delete_metadata('user', 0, 'session_tokens', false, true);
        }

        protected function prepare_session($session)
        {
            if(is_int($session))
            {
                return ['expiration' => $session];
            }

            return $session;
        }

        protected function update_session($verifier, $session = null)
        {
            $sessions = $this->get_sessions();

            if($session)
            {
                $sessions[$verifier] = $session;
            }
            else
            {
                unset($sessions[$verifier]);
            }

            $this->update_sessions($sessions);
        }

        protected function get_sessions()
        {
            $sessions = get_user_meta($this->user_id, 'session_tokens', true);

            if(! is_array($sessions))
            {
                return [];
            }

            $sessions = array_map([$this, 'prepare_session'], $sessions);

            return array_filter($sessions, [$this, 'is_still_valid']);
        }

        protected function update_sessions($sessions)
        {
            if($sessions)
            {
                update_user_meta($this->user_id, 'session_tokens', $sessions);
            }
            else
            {
                delete_user_meta($this->user_id, 'session_tokens');
            }
        }

        protected function destroy_other_sessions($verifier)
        {
            $session = $this->get_session($verifier);
            $this->update_sessions([$verifier => $session]);
        }

        protected function get_session($verifier)
        {
            $sessions = $this->get_sessions();

            if(isset($sessions[$verifier]))
            {
                return $sessions[$verifier];
            }

            return null;
        }

        protected function destroy_all_sessions()
        {
            $this->update_sessions([]);
        }
    }
