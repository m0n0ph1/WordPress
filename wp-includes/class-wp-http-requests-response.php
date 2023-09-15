<?php

    class WP_HTTP_Requests_Response extends WP_HTTP_Response
    {
        protected $response;

        protected $filename;

        public function __construct(WpOrg\Requests\Response $response, $filename = '')
        {
            $this->response = $response;
            $this->filename = $filename;
        }

        public function get_response_object()
        {
            return $this->response;
        }

        public function set_headers($headers)
        {
            $this->response->headers = new WpOrg\Requests\Response\Headers($headers);
        }

        public function header($key, $value, $replace = true)
        {
            if($replace)
            {
                unset($this->response->headers[$key]);
            }

            $this->response->headers[$key] = $value;
        }

        public function set_status($code)
        {
            $this->response->status_code = absint($code);
        }

        public function set_data($data)
        {
            $this->response->body = $data;
        }

        public function to_array()
        {
            return [
                'headers' => $this->get_headers(),
                'body' => $this->get_data(),
                'response' => [
                    'code' => $this->get_status(),
                    'message' => get_status_header_desc($this->get_status()),
                ],
                'cookies' => $this->get_cookies(),
                'filename' => $this->filename,
            ];
        }

        public function get_headers()
        {
            // Ensure headers remain case-insensitive.
            $converted = new WpOrg\Requests\Utility\CaseInsensitiveDictionary();

            foreach($this->response->headers->getAll() as $key => $value)
            {
                if(count($value) === 1)
                {
                    $converted[$key] = $value[0];
                }
                else
                {
                    $converted[$key] = $value;
                }
            }

            return $converted;
        }

        public function get_data()
        {
            return $this->response->body;
        }

        public function get_status()
        {
            return $this->response->status_code;
        }

        public function get_cookies()
        {
            $cookies = [];
            foreach($this->response->cookies as $cookie)
            {
                $cookies[] = new WP_Http_Cookie([
                                                    'name' => $cookie->name,
                                                    'value' => urldecode($cookie->value),
                                                    'expires' => isset($cookie->attributes['expires']) ? $cookie->attributes['expires'] : null,
                                                    'path' => isset($cookie->attributes['path']) ? $cookie->attributes['path'] : null,
                                                    'domain' => isset($cookie->attributes['domain']) ? $cookie->attributes['domain'] : null,
                                                    'host_only' => isset($cookie->flags['host-only']) ? $cookie->flags['host-only'] : null,
                                                ]);
            }

            return $cookies;
        }
    }
