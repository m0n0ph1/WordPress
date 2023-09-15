<?php

    class WP_Ajax_Upgrader_Skin extends Automatic_Upgrader_Skin
    {
        public $plugin_info = [];

        public $theme_info = false;

        protected $errors = null;

        public function __construct($args = [])
        {
            parent::__construct($args);

            $this->errors = new WP_Error();
        }

        public function get_errors()
        {
            return $this->errors;
        }

        public function get_error_messages()
        {
            $messages = [];

            foreach($this->errors->get_error_codes() as $error_code)
            {
                $error_data = $this->errors->get_error_data($error_code);

                if($error_data && is_string($error_data))
                {
                    $messages[] = $this->errors->get_error_message($error_code).' '.esc_html(strip_tags($error_data));
                }
                else
                {
                    $messages[] = $this->errors->get_error_message($error_code);
                }
            }

            return implode(', ', $messages);
        }

        public function error($errors, ...$args)
        {
            if(is_string($errors))
            {
                $string = $errors;
                if(! empty($this->upgrader->strings[$string]))
                {
                    $string = $this->upgrader->strings[$string];
                }

                if(str_contains($string, '%') && ! empty($args))
                {
                    $string = vsprintf($string, $args);
                }

                // Count existing errors to generate a unique error code.
                $errors_count = count($this->errors->get_error_codes());
                $this->errors->add('unknown_upgrade_error_'.($errors_count + 1), $string);
            }
            elseif(is_wp_error($errors))
            {
                foreach($errors->get_error_codes() as $error_code)
                {
                    $this->errors->add($error_code, $errors->get_error_message($error_code), $errors->get_error_data($error_code));
                }
            }

            parent::error($errors, ...$args);
        }

        public function feedback($feedback, ...$args)
        {
            if(is_wp_error($feedback))
            {
                foreach($feedback->get_error_codes() as $error_code)
                {
                    $this->errors->add($error_code, $feedback->get_error_message($error_code), $feedback->get_error_data($error_code));
                }
            }

            parent::feedback($feedback, ...$args);
        }
    }
