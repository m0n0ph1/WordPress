<?php

    #[AllowDynamicProperties]
    class WP_SimplePie_File extends SimplePie_File
    {
        public $timeout = 10;

        public function __construct(
            $url, $timeout = 10, $redirects = 5, $headers = null, $useragent = null, $force_fsockopen = false
        ) {
            $this->url = $url;
            $this->timeout = $timeout;
            $this->redirects = $redirects;
            $this->headers = $headers;
            $this->useragent = $useragent;

            $this->method = SIMPLEPIE_FILE_SOURCE_REMOTE;

            if(preg_match('/^http(s)?:\/\//i', $url))
            {
                $args = [
                    'timeout' => $this->timeout,
                    'redirection' => $this->redirects,
                ];

                if(! empty($this->headers))
                {
                    $args['headers'] = $this->headers;
                }

                if(SIMPLEPIE_USERAGENT !== $this->useragent)
                { // Use default WP user agent unless custom has been specified.
                    $args['user-agent'] = $this->useragent;
                }

                $res = wp_safe_remote_request($url, $args);

                if(is_wp_error($res))
                {
                    $this->error = 'WP HTTP Error: '.$res->get_error_message();
                    $this->success = false;
                }
                else
                {
                    $this->headers = wp_remote_retrieve_headers($res);

                    /*
                     * SimplePie expects multiple headers to be stored as a comma-separated string,
                     * but `wp_remote_retrieve_headers()` returns them as an array, so they need
                     * to be converted.
                     *
                     * The only exception to that is the `content-type` header, which should ignore
                     * any previous values and only use the last one.
                     *
                     * @see SimplePie_HTTP_Parser::new_line().
                     */
                    foreach($this->headers as $name => $value)
                    {
                        if(! is_array($value))
                        {
                            continue;
                        }

                        if('content-type' === $name)
                        {
                            $this->headers[$name] = array_pop($value);
                        }
                        else
                        {
                            $this->headers[$name] = implode(', ', $value);
                        }
                    }

                    $this->body = wp_remote_retrieve_body($res);
                    $this->status_code = wp_remote_retrieve_response_code($res);
                }
            }
            else
            {
                $this->error = '';
                $this->success = false;
            }
        }
    }
