<?php
    /**
     * HTTP API: WP_HTTP_Requests_Response class
     *
     * @package    WordPress
     * @subpackage HTTP
     * @since      4.6.0
     */

    /**
     * Core wrapper object for a WpOrg\Requests\Response for standardisation.
     *
     * @since 4.6.0
     *
     * @see   WP_HTTP_Response
     */
    class WP_HTTP_Requests_Response extends WP_HTTP_Response
    {
        /**
         * Requests Response object.
         *
         * @since 4.6.0
         * @var \WpOrg\Requests\Response
         */
        protected $response;

        /**
         * Filename the response was saved to.
         *
         * @since 4.6.0
         * @var string|null
         */
        protected $filename;

        /**
         * Constructor.
         *
         * @param \WpOrg\Requests\Response $response HTTP response.
         * @param string                   $filename Optional. File name. Default empty.
         *
         * @since 4.6.0
         *
         */
        public function __construct(WpOrg\Requests\Response $response, $filename = '')
        {
            $this->response = $response;
            $this->filename = $filename;
        }

        /**
         * Retrieves the response object for the request.
         *
         * @return WpOrg\Requests\Response HTTP response.
         * @since 4.6.0
         *
         */
        public function get_response_object()
        {
            return $this->response;
        }

        /**
         * Sets all header values.
         *
         * @param array $headers Map of header name to header value.
         *
         * @since 4.6.0
         *
         */
        public function set_headers($headers)
        {
            $this->response->headers = new WpOrg\Requests\Response\Headers($headers);
        }

        /**
         * Sets a single HTTP header.
         *
         * @param string $key     Header name.
         * @param string $value   Header value.
         * @param bool   $replace Optional. Whether to replace an existing header of the same name.
         *                        Default true.
         *
         * @since 4.6.0
         *
         */
        public function header($key, $value, $replace = true)
        {
            if($replace)
            {
                unset($this->response->headers[$key]);
            }

            $this->response->headers[$key] = $value;
        }

        /**
         * Sets the 3-digit HTTP status code.
         *
         * @param int $code HTTP status.
         *
         * @since 4.6.0
         *
         */
        public function set_status($code)
        {
            $this->response->status_code = absint($code);
        }

        /**
         * Sets the response data.
         *
         * @param string $data Response data.
         *
         * @since 4.6.0
         *
         */
        public function set_data($data)
        {
            $this->response->body = $data;
        }

        /**
         * Converts the object to a WP_Http response array.
         *
         * @return array WP_Http response array, per WP_Http::request().
         * @since 4.6.0
         *
         */
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

        /**
         * Retrieves headers associated with the response.
         *
         * @return \WpOrg\Requests\Utility\CaseInsensitiveDictionary Map of header name to header value.
         * @since 4.6.0
         *
         */
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

        /**
         * Retrieves the response data.
         *
         * @return string Response data.
         * @since 4.6.0
         *
         */
        public function get_data()
        {
            return $this->response->body;
        }

        /**
         * Retrieves the HTTP return code for the response.
         *
         * @return int The 3-digit HTTP status code.
         * @since 4.6.0
         *
         */
        public function get_status()
        {
            return $this->response->status_code;
        }

        /**
         * Retrieves cookies from the response.
         *
         * @return WP_HTTP_Cookie[] List of cookie objects.
         * @since 4.6.0
         *
         */
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
