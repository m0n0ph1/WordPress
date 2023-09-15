<?php

    #[AllowDynamicProperties]
    class WP_Http_Curl
    {
        private $headers = '';

        private $body = '';

        private $max_body_length = false;

        private $stream_handle = false;

        private $bytes_written_total = 0;

        public static function test($args = [])
        {
            if(! function_exists('curl_init') || ! function_exists('curl_exec'))
            {
                return false;
            }

            $is_ssl = isset($args['ssl']) && $args['ssl'];

            if($is_ssl)
            {
                $curl_version = curl_version();
                // Check whether this cURL version support SSL requests.
                if(! (CURL_VERSION_SSL & $curl_version['features']))
                {
                    return false;
                }
            }

            return apply_filters('use_curl_transport', true, $args);
        }

        public function request($url, $args = [])
        {
            $defaults = [
                'method' => 'GET',
                'timeout' => 5,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => [],
                'body' => null,
                'cookies' => [],
                'decompress' => false,
                'stream' => false,
                'filename' => null,
            ];

            $parsed_args = wp_parse_args($args, $defaults);

            if(isset($parsed_args['headers']['User-Agent']))
            {
                $parsed_args['user-agent'] = $parsed_args['headers']['User-Agent'];
                unset($parsed_args['headers']['User-Agent']);
            }
            elseif(isset($parsed_args['headers']['user-agent']))
            {
                $parsed_args['user-agent'] = $parsed_args['headers']['user-agent'];
                unset($parsed_args['headers']['user-agent']);
            }

            // Construct Cookie: header if any cookies are set.
            WP_Http::buildCookieHeader($parsed_args);

            $handle = curl_init();

            // cURL offers really easy proxy support.
            $proxy = new WP_HTTP_Proxy();

            if($proxy->is_enabled() && $proxy->send_through_proxy($url))
            {
                curl_setopt($handle, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                curl_setopt($handle, CURLOPT_PROXY, $proxy->host());
                curl_setopt($handle, CURLOPT_PROXYPORT, $proxy->port());

                if($proxy->use_authentication())
                {
                    curl_setopt($handle, CURLOPT_PROXYAUTH, CURLAUTH_ANY);
                    curl_setopt($handle, CURLOPT_PROXYUSERPWD, $proxy->authentication());
                }
            }

            $is_local = isset($parsed_args['local']) && $parsed_args['local'];
            $ssl_verify = isset($parsed_args['sslverify']) && $parsed_args['sslverify'];
            if($is_local)
            {
                $ssl_verify = apply_filters('https_local_ssl_verify', $ssl_verify, $url);
            }
            elseif(! $is_local)
            {
                $ssl_verify = apply_filters('https_ssl_verify', $ssl_verify, $url);
            }

            /*
             * CURLOPT_TIMEOUT and CURLOPT_CONNECTTIMEOUT expect integers. Have to use ceil since.
             * a value of 0 will allow an unlimited timeout.
             */
            $timeout = (int) ceil($parsed_args['timeout']);
            curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($handle, CURLOPT_TIMEOUT, $timeout);

            curl_setopt($handle, CURLOPT_URL, $url);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, (true === $ssl_verify) ? 2 : false);
            curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, $ssl_verify);

            if($ssl_verify)
            {
                curl_setopt($handle, CURLOPT_CAINFO, $parsed_args['sslcertificates']);
            }

            curl_setopt($handle, CURLOPT_USERAGENT, $parsed_args['user-agent']);

            /*
             * The option doesn't work with safe mode or when open_basedir is set, and there's
             * a bug #17490 with redirected POST requests, so handle redirections outside Curl.
             */
            curl_setopt($handle, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($handle, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);

            switch($parsed_args['method'])
            {
                case 'HEAD':
                    curl_setopt($handle, CURLOPT_NOBODY, true);
                    break;
                case 'POST':
                    curl_setopt($handle, CURLOPT_POST, true);
                    curl_setopt($handle, CURLOPT_POSTFIELDS, $parsed_args['body']);
                    break;
                case 'PUT':
                    curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PUT');
                    curl_setopt($handle, CURLOPT_POSTFIELDS, $parsed_args['body']);
                    break;
                default:
                    curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $parsed_args['method']);
                    if(! is_null($parsed_args['body']))
                    {
                        curl_setopt($handle, CURLOPT_POSTFIELDS, $parsed_args['body']);
                    }
                    break;
            }

            if(true === $parsed_args['blocking'])
            {
                curl_setopt($handle, CURLOPT_HEADERFUNCTION, [$this, 'stream_headers']);
                curl_setopt($handle, CURLOPT_WRITEFUNCTION, [$this, 'stream_body']);
            }

            curl_setopt($handle, CURLOPT_HEADER, false);

            if(isset($parsed_args['limit_response_size']))
            {
                $this->max_body_length = (int) $parsed_args['limit_response_size'];
            }
            else
            {
                $this->max_body_length = false;
            }

            // If streaming to a file open a file handle, and setup our curl streaming handler.
            if($parsed_args['stream'])
            {
                if(WP_DEBUG)
                {
                    $this->stream_handle = fopen($parsed_args['filename'], 'w+');
                }
                else
                {
                    $this->stream_handle = @fopen($parsed_args['filename'], 'w+');
                }
                if(! $this->stream_handle)
                {
                    return new WP_Error('http_request_failed', sprintf(/* translators: 1: fopen(), 2: File name. */ __('Could not open handle for %1$s to %2$s.'), 'fopen()', $parsed_args['filename']));
                }
            }
            else
            {
                $this->stream_handle = false;
            }

            if(! empty($parsed_args['headers']))
            {
                // cURL expects full header strings in each element.
                $headers = [];
                foreach($parsed_args['headers'] as $name => $value)
                {
                    $headers[] = "{$name}: $value";
                }
                curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
            }

            if('1.0' === $parsed_args['httpversion'])
            {
                curl_setopt($handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
            }
            else
            {
                curl_setopt($handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            }

            do_action_ref_array('http_api_curl', [&$handle, $parsed_args, $url]);

            // We don't need to return the body, so don't. Just execute request and return.
            if(! $parsed_args['blocking'])
            {
                curl_exec($handle);

                $curl_error = curl_error($handle);
                if($curl_error)
                {
                    curl_close($handle);

                    return new WP_Error('http_request_failed', $curl_error);
                }
                if(in_array(curl_getinfo($handle, CURLINFO_HTTP_CODE), [301, 302], true))
                {
                    curl_close($handle);

                    return new WP_Error('http_request_failed', __('Too many redirects.'));
                }

                curl_close($handle);

                return [
                    'headers' => [],
                    'body' => '',
                    'response' => [
                        'code' => false,
                        'message' => false,
                    ],
                    'cookies' => [],
                ];
            }

            curl_exec($handle);

            $processed_headers = WP_Http::processHeaders($this->headers, $url);
            $body = $this->body;
            $bytes_written_total = $this->bytes_written_total;

            $this->headers = '';
            $this->body = '';
            $this->bytes_written_total = 0;

            $curl_error = curl_errno($handle);

            // If an error occurred, or, no response.
            if($curl_error || ($body === '' && empty($processed_headers['headers'])))
            {
                if(CURLE_WRITE_ERROR /* 23 */ === $curl_error)
                {
                    if(! $this->max_body_length || $this->max_body_length !== $bytes_written_total)
                    {
                        if($parsed_args['stream'])
                        {
                            curl_close($handle);
                            fclose($this->stream_handle);

                            return new WP_Error('http_request_failed', __('Failed to write request to temporary file.'));
                        }
                        else
                        {
                            curl_close($handle);

                            return new WP_Error('http_request_failed', curl_error($handle));
                        }
                    }
                }
                else
                {
                    $curl_error = curl_error($handle);
                    if($curl_error)
                    {
                        curl_close($handle);

                        return new WP_Error('http_request_failed', $curl_error);
                    }
                }
                if(in_array(curl_getinfo($handle, CURLINFO_HTTP_CODE), [301, 302], true))
                {
                    curl_close($handle);

                    return new WP_Error('http_request_failed', __('Too many redirects.'));
                }
            }

            curl_close($handle);

            if($parsed_args['stream'])
            {
                fclose($this->stream_handle);
            }

            $response = [
                'headers' => $processed_headers['headers'],
                'body' => null,
                'response' => $processed_headers['response'],
                'cookies' => $processed_headers['cookies'],
                'filename' => $parsed_args['filename'],
            ];

            // Handle redirects.
            $redirect_response = WP_Http::handle_redirects($url, $parsed_args, $response);
            if(false !== $redirect_response)
            {
                return $redirect_response;
            }

            if(true === $parsed_args['decompress'] && true === WP_Http_Encoding::should_decode($processed_headers['headers']))
            {
                $body = WP_Http_Encoding::decompress($body);
            }

            $response['body'] = $body;

            return $response;
        }

        private function stream_headers($handle, $headers)
        {
            $this->headers .= $headers;

            return strlen($headers);
        }

        private function stream_body($handle, $data)
        {
            $data_length = strlen($data);

            if($this->max_body_length && ($this->bytes_written_total + $data_length) > $this->max_body_length)
            {
                $data_length = ($this->max_body_length - $this->bytes_written_total);
                $data = substr($data, 0, $data_length);
            }

            if($this->stream_handle)
            {
                $bytes_written = fwrite($this->stream_handle, $data);
            }
            else
            {
                $this->body .= $data;
                $bytes_written = $data_length;
            }

            $this->bytes_written_total += $bytes_written;

            // Upon event of this function returning less than strlen( $data ) curl will error with CURLE_WRITE_ERROR.
            return $bytes_written;
        }
    }
