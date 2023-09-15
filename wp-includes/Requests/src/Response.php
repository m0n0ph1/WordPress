<?php

    namespace WpOrg\Requests;

    use WpOrg\Requests\Cookie\Jar;
    use WpOrg\Requests\Exception\Http;
    use WpOrg\Requests\Response\Headers;

    class Response
    {
        public $body = '';

        public $raw = '';

        public $headers = [];

        public $status_code = false;

        public $protocol_version = false;

        public $success = false;

        public $redirects = 0;

        public $url = '';

        public $history = [];

        public $cookies = [];

        public function __construct()
        {
            $this->headers = new Headers();
            $this->cookies = new Jar();
        }

        public function throw_for_status($allow_redirects = true)
        {
            if($this->is_redirect())
            {
                if($allow_redirects !== true)
                {
                    throw new Exception('Redirection not allowed', 'response.no_redirects', $this);
                }
            }
            elseif(! $this->success)
            {
                $exception = Http::get_class($this->status_code);
                throw new $exception(null, $this);
            }
        }

        public function is_redirect()
        {
            $code = $this->status_code;

            return in_array($code, [300, 301, 302, 303, 307], true) || $code > 307 && $code < 400;
        }

        public function decode_body($associative = true, $depth = 512, $options = 0)
        {
            $data = json_decode($this->body, $associative, $depth, $options);

            if(json_last_error() !== JSON_ERROR_NONE)
            {
                $last_error = json_last_error_msg();
                throw new Exception('Unable to parse JSON data: '.$last_error, 'response.invalid', $this);
            }

            return $data;
        }
    }
