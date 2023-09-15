<?php

    class Automatic_Upgrader_Skin extends WP_Upgrader_Skin
    {
        protected $messages = [];

        public function request_filesystem_credentials(
            $error = false, $context = '', $allow_relaxed_file_ownership = false
        ) {
            if($context)
            {
                $this->options['context'] = $context;
            }
            /*
             * TODO: Fix up request_filesystem_credentials(), or split it, to allow us to request a no-output version.
             * This will output a credentials form in event of failure. We don't want that, so just hide with a buffer.
             */
            ob_start();
            $result = parent::request_filesystem_credentials($error, $context, $allow_relaxed_file_ownership);
            ob_end_clean();

            return $result;
        }

        public function get_upgrade_messages()
        {
            return $this->messages;
        }

        public function header()
        {
            ob_start();
        }

        public function footer()
        {
            parent::footer();
            $output = ob_get_clean();
            if(! empty($output))
            {
                $this->feedback($output);
            }
        }

        public function feedback($feedback, ...$args)
        {
            parent::feedback($feedback, ...$args);
            if(is_wp_error($feedback))
            {
                $string = $feedback->get_error_message();
            }
            elseif(is_array($feedback))
            {
                return;
            }
            else
            {
                $string = $feedback;
            }

            if(! empty($this->upgrader->strings[$string]))
            {
                $string = $this->upgrader->strings[$string];
            }

            if(str_contains($string, '%') && ! empty($args))
            {
                $string = vsprintf($string, $args);
            }

            $string = trim($string);

            // Only allow basic HTML in the messages, as it'll be used in emails/logs rather than direct browser output.
            $string = wp_kses($string, [
                'a' => [
                    'href' => true,
                ],
                'br' => true,
                'em' => true,
                'strong' => true,
            ]);

            if(empty($string))
            {
                return;
            }

            $this->messages[] = $string;
        }
    }
