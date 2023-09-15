<?php

    #[AllowDynamicProperties]
    class WP_Error
    {
        public $errors = [];

        public $error_data = [];

        protected $additional_data = [];

        public function __construct($code = '', $message = '', $data = '')
        {
            if(empty($code))
            {
                return;
            }

            $this->add($code, $message, $data);
        }

        public function add($code, $message, $data = '')
        {
            $this->errors[$code][] = $message;

            if(! empty($data))
            {
                $this->add_data($data, $code);
            }

            do_action('wp_error_added', $code, $message, $data, $this);
        }

        public function add_data($data, $code = '')
        {
            if(empty($code))
            {
                $code = $this->get_error_code();
            }

            if(isset($this->error_data[$code]))
            {
                $this->additional_data[$code][] = $this->error_data[$code];
            }

            $this->error_data[$code] = $data;
        }

        public function get_error_code()
        {
            $codes = $this->get_error_codes();

            if(empty($codes))
            {
                return '';
            }

            return $codes[0];
        }

        public function get_error_codes()
        {
            if(! $this->has_errors())
            {
                return [];
            }

            return array_keys($this->errors);
        }

        public function has_errors()
        {
            if(! empty($this->errors))
            {
                return true;
            }

            return false;
        }

        public function get_error_message($code = '')
        {
            if(empty($code))
            {
                $code = $this->get_error_code();
            }
            $messages = $this->get_error_messages($code);
            if(empty($messages))
            {
                return '';
            }

            return $messages[0];
        }

        public function get_error_messages($code = '')
        {
            // Return all messages if no code specified.
            if(empty($code))
            {
                $all_messages = [];
                foreach((array) $this->errors as $code => $messages)
                {
                    $all_messages = array_merge($all_messages, $messages);
                }

                return $all_messages;
            }

            if(isset($this->errors[$code]))
            {
                return $this->errors[$code];
            }
            else
            {
                return [];
            }
        }

        public function get_error_data($code = '')
        {
            if(empty($code))
            {
                $code = $this->get_error_code();
            }

            if(isset($this->error_data[$code]))
            {
                return $this->error_data[$code];
            }
        }

        public function remove($code)
        {
            unset($this->errors[$code]);
            unset($this->error_data[$code]);
            unset($this->additional_data[$code]);
        }

        public function merge_from(WP_Error $error)
        {
            static::copy_errors($error, $this);
        }

        protected static function copy_errors(WP_Error $from, WP_Error $to)
        {
            foreach($from->get_error_codes() as $code)
            {
                foreach($from->get_error_messages($code) as $error_message)
                {
                    $to->add($code, $error_message);
                }

                foreach($from->get_all_error_data($code) as $data)
                {
                    $to->add_data($data, $code);
                }
            }
        }

        public function get_all_error_data($code = '')
        {
            if(empty($code))
            {
                $code = $this->get_error_code();
            }

            $data = [];

            if(isset($this->additional_data[$code]))
            {
                $data = $this->additional_data[$code];
            }

            if(isset($this->error_data[$code]))
            {
                $data[] = $this->error_data[$code];
            }

            return $data;
        }

        public function export_to(WP_Error $error)
        {
            static::copy_errors($this, $error);
        }
    }
