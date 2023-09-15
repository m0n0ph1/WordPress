<?php /** @noinspection ALL */

    namespace WpOrg\Requests;

    use WpOrg\Requests\Auth\Basic;
    use WpOrg\Requests\Cookie\Jar;
    use WpOrg\Requests\Exception\InvalidArgument;
    use WpOrg\Requests\Proxy\Http;
    use WpOrg\Requests\Transport\Curl;
    use WpOrg\Requests\Transport\Fsockopen;
    use WpOrg\Requests\Utility\InputValidator;

    class Requests
    {
        const POST = 'POST';

        const PUT = 'PUT';

        const GET = 'GET';

        const HEAD = 'HEAD';

        const DELETE = 'DELETE';

        const OPTIONS = 'OPTIONS';

        const TRACE = 'TRACE';

        const PATCH = 'PATCH';

        const BUFFER_SIZE = 1160;

        const OPTION_DEFAULTS = [
            'timeout' => 10,
            'connect_timeout' => 10,
            'useragent' => 'php-requests/'.self::VERSION,
            'protocol_version' => 1.1,
            'redirected' => 0,
            'redirects' => 10,
            'follow_redirects' => true,
            'blocking' => true,
            'type' => self::GET,
            'filename' => false,
            'auth' => false,
            'proxy' => false,
            'cookies' => false,
            'max_bytes' => false,
            'idn' => true,
            'hooks' => null,
            'transport' => null,
            'verify' => null,
            'verifyname' => true,
        ];

        const DEFAULT_TRANSPORTS = [
            Curl::class => Curl::class,
            Fsockopen::class => Fsockopen::class,
        ];

        const VERSION = '2.0.8';

        public static $transport = [];

        protected static $transports = [];

        protected static $certificate_path = __DIR__.'/../certificates/cacert.pem';

        private static $magic_compression_headers = [
            "\x1f\x8b" => true, // Gzip marker.
            "\x78\x01" => true, // Zlib marker - level 1.
            "\x78\x5e" => true, // Zlib marker - level 2 to 5.
            "\x78\x9c" => true, // Zlib marker - level 6.
            "\x78\xda" => true, // Zlib marker - level 7 to 9.
        ];

        private function __construct() {}

        public static function add_transport($transport)
        {
            if(empty(self::$transports))
            {
                self::$transports = self::DEFAULT_TRANSPORTS;
            }

            self::$transports[$transport] = $transport;
        }

        public static function has_capabilities(array $capabilities = [])
        {
            return self::get_transport_class($capabilities) !== '';
        }

        protected static function get_transport_class(array $capabilities = [])
        {
            // Caching code, don't bother testing coverage.
            // @codeCoverageIgnoreStart
            // Array of capabilities as a string to be used as an array key.
            ksort($capabilities);
            $cap_string = serialize($capabilities);

            // Don't search for a transport if it's already been done for these $capabilities.
            if(isset(self::$transport[$cap_string]))
            {
                return self::$transport[$cap_string];
            }

            // Ensure we will not run this same check again later on.
            self::$transport[$cap_string] = '';
            // @codeCoverageIgnoreEnd

            if(empty(self::$transports))
            {
                self::$transports = self::DEFAULT_TRANSPORTS;
            }

            // Find us a working transport.
            foreach(self::$transports as $class)
            {
                if(! class_exists($class))
                {
                    continue;
                }

                /** @noinspection NativeMemberUsageInspection */
                $result = $class::test($capabilities);
                if($result === true)
                {
                    self::$transport[$cap_string] = $class;
                    break;
                }
            }

            return self::$transport[$cap_string];
        }

        public static function get($url, $headers = [], $options = [])
        {
            return self::request($url, $headers, null, self::GET, $options);
        }

        public static function request($url, $headers = [], $data = [], $type = self::GET, $options = [])
        {
            if(InputValidator::is_string_or_stringable($url) === false)
            {
                throw InvalidArgument::create(1, '$url', 'string|Stringable', gettype($url));
            }

            if(is_string($type) === false)
            {
                throw InvalidArgument::create(4, '$type', 'string', gettype($type));
            }

            if(is_array($options) === false)
            {
                throw InvalidArgument::create(5, '$options', 'array', gettype($options));
            }

            if(empty($options['type']))
            {
                $options['type'] = $type;
            }

            $options = array_merge(self::get_default_options(), $options);

            self::set_defaults($url, $headers, $data, $type, $options);

            $options['hooks']->dispatch('requests.before_request', [&$url, &$headers, &$data, &$type, &$options]);

            if(! empty($options['transport']))
            {
                $transport = $options['transport'];

                if(is_string($options['transport']))
                {
                    $transport = new $transport();
                }
            }
            else
            {
                $need_ssl = (stripos($url, 'https://') === 0);
                $capabilities = [Capability::SSL => $need_ssl];
                $transport = self::get_transport($capabilities);
            }

            $response = $transport->request($url, $headers, $data, $options);

            $options['hooks']->dispatch('requests.before_parse', [&$response, $url, $headers, $data, $type, $options]);

            return self::parse_response($response, $url, $headers, $data, $options);
        }

        protected static function get_default_options($multirequest = false)
        {
            $defaults = static::OPTION_DEFAULTS;
            $defaults['verify'] = self::$certificate_path;

            if($multirequest !== false)
            {
                $defaults['complete'] = null;
            }

            return $defaults;
        }

        protected static function set_defaults(&$url, &$headers, &$data, &$type, &$options)
        {
            if(! preg_match('/^http(s)?:\/\//i', $url, $matches))
            {
                throw new Exception('Only HTTP(S) requests are handled.', 'nonhttp', $url);
            }

            if(empty($options['hooks']))
            {
                $options['hooks'] = new Hooks();
            }

            if(is_array($options['auth']))
            {
                $options['auth'] = new Basic($options['auth']);
            }

            if($options['auth'] !== false)
            {
                $options['auth']->register($options['hooks']);
            }

            if(is_string($options['proxy']) || is_array($options['proxy']))
            {
                $options['proxy'] = new Http($options['proxy']);
            }

            if($options['proxy'] !== false)
            {
                $options['proxy']->register($options['hooks']);
            }

            if(is_array($options['cookies']))
            {
                $options['cookies'] = new Jar($options['cookies']);
            }
            elseif(empty($options['cookies']))
            {
                $options['cookies'] = new Jar();
            }

            if($options['cookies'] !== false)
            {
                $options['cookies']->register($options['hooks']);
            }

            if($options['idn'] !== false)
            {
                $iri = new Iri($url);
                $iri->host = IdnaEncoder::encode($iri->ihost);
                $url = $iri->uri;
            }

            // Massage the type to ensure we support it.
            $type = strtoupper($type);

            if(! isset($options['data_format']))
            {
                if(in_array($type, [self::HEAD, self::GET, self::DELETE], true))
                {
                    $options['data_format'] = 'query';
                }
                else
                {
                    $options['data_format'] = 'body';
                }
            }
        }

        protected static function get_transport(array $capabilities = [])
        {
            $class = self::get_transport_class($capabilities);

            if($class === '')
            {
                throw new Exception('No working transports found', 'notransport', self::$transports);
            }

            return new $class();
        }

        protected static function parse_response($headers, $url, $req_headers, $req_data, $options)
        {
            $return = new Response();
            if(! $options['blocking'])
            {
                return $return;
            }

            $return->raw = $headers;
            $return->url = (string) $url;
            $return->body = '';

            if(! $options['filename'])
            {
                $pos = strpos($headers, "\r\n\r\n");
                if($pos === false)
                {
                    // Crap!
                    throw new Exception('Missing header/body separator', 'requests.no_crlf_separator');
                }

                $headers = substr($return->raw, 0, $pos);
                // Headers will always be separated from the body by two new lines - `\n\r\n\r`.
                $body = substr($return->raw, $pos + 4);
                if(! empty($body))
                {
                    $return->body = $body;
                }
            }

            // Pretend CRLF = LF for compatibility (RFC 2616, section 19.3)
            $headers = str_replace("\r\n", "\n", $headers);
            // Unfold headers (replace [CRLF] 1*( SP | HT ) with SP) as per RFC 2616 (section 2.2)
            $headers = preg_replace('/\n[ \t]/', ' ', $headers);
            $headers = explode("\n", $headers);
            preg_match('#^HTTP/(1\.\d)[ \t]+(\d+)#i', array_shift($headers), $matches);
            if(empty($matches))
            {
                throw new Exception('Response could not be parsed', 'noversion', $headers);
            }

            $return->protocol_version = (float) $matches[1];
            $return->status_code = (int) $matches[2];
            if($return->status_code >= 200 && $return->status_code < 300)
            {
                $return->success = true;
            }

            foreach($headers as $header)
            {
                [$key, $value] = explode(':', $header, 2);
                $value = trim($value);
                preg_replace('#(\s+)#i', ' ', $value);
                $return->headers[$key] = $value;
            }

            if(isset($return->headers['transfer-encoding']))
            {
                $return->body = self::decode_chunked($return->body);
                unset($return->headers['transfer-encoding']);
            }

            if(isset($return->headers['content-encoding']))
            {
                $return->body = self::decompress($return->body);
            }

            // fsockopen and cURL compatibility
            if(isset($return->headers['connection']))
            {
                unset($return->headers['connection']);
            }

            $options['hooks']->dispatch('requests.before_redirect_check', [
                &$return,
                $req_headers,
                $req_data,
                $options
            ]);

            if($return->is_redirect() && $options['follow_redirects'] === true)
            {
                if(isset($return->headers['location']) && $options['redirected'] < $options['redirects'])
                {
                    if($return->status_code === 303)
                    {
                        $options['type'] = self::GET;
                    }

                    $options['redirected']++;
                    $location = $return->headers['location'];
                    if(strpos($location, 'http://') !== 0 && strpos($location, 'https://') !== 0)
                    {
                        // relative redirect, for compatibility make it absolute
                        $location = Iri::absolutize($url, $location);
                        $location = $location->uri;
                    }

                    $hook_args = [
                        &$location,
                        &$req_headers,
                        &$req_data,
                        &$options,
                        $return,
                    ];
                    $options['hooks']->dispatch('requests.before_redirect', $hook_args);
                    $redirected = self::request($location, $req_headers, $req_data, $options['type'], $options);
                    $redirected->history[] = $return;

                    return $redirected;
                }
                elseif($options['redirected'] >= $options['redirects'])
                {
                    throw new Exception('Too many redirects', 'toomanyredirects', $return);
                }
            }

            $return->redirects = $options['redirected'];

            $options['hooks']->dispatch('requests.after_request', [&$return, $req_headers, $req_data, $options]);

            return $return;
        }

        protected static function decode_chunked($data)
        {
            if(! preg_match('/^([0-9a-f]+)(?:;(?:[\w-]*)(?:=(?:(?:[\w-]*)*|"(?:[^\r\n])*"))?)*\r\n/i', trim($data)))
            {
                return $data;
            }

            $decoded = '';
            $encoded = $data;

            while(true)
            {
                $is_chunked = (bool) preg_match('/^([0-9a-f]+)(?:;(?:[\w-]*)(?:=(?:(?:[\w-]*)*|"(?:[^\r\n])*"))?)*\r\n/i', $encoded, $matches);
                if(! $is_chunked)
                {
                    // Looks like it's not chunked after all
                    return $data;
                }

                $length = hexdec(trim($matches[1]));
                if($length === 0)
                {
                    // Ignore trailer headers
                    return $decoded;
                }

                $chunk_length = strlen($matches[0]);
                $decoded .= substr($encoded, $chunk_length, $length);
                $encoded = substr($encoded, $chunk_length + $length + 2);

                if(trim($encoded) === '0' || empty($encoded))
                {
                    return $decoded;
                }
            }

            // We'll never actually get down here
            // @codeCoverageIgnoreStart
        }

        public static function decompress($data)
        {
            if(is_string($data) === false)
            {
                throw InvalidArgument::create(1, '$data', 'string', gettype($data));
            }

            if(trim($data) === '')
            {
                // Empty body does not need further processing.
                return $data;
            }

            $marker = substr($data, 0, 2);
            if(! isset(self::$magic_compression_headers[$marker]))
            {
                // Not actually compressed. Probably cURL ruining this for us.
                return $data;
            }

            if(function_exists('gzdecode'))
            {
                $decoded = @gzdecode($data);
                if($decoded !== false)
                {
                    return $decoded;
                }
            }

            if(function_exists('gzinflate'))
            {
                $decoded = @gzinflate($data);
                if($decoded !== false)
                {
                    return $decoded;
                }
            }

            $decoded = self::compatible_gzinflate($data);
            if($decoded !== false)
            {
                return $decoded;
            }

            if(function_exists('gzuncompress'))
            {
                $decoded = @gzuncompress($data);
                if($decoded !== false)
                {
                    return $decoded;
                }
            }

            return $data;
        }

        public static function compatible_gzinflate($gz_data)
        {
            if(is_string($gz_data) === false)
            {
                throw InvalidArgument::create(1, '$gz_data', 'string', gettype($gz_data));
            }

            if(trim($gz_data) === '')
            {
                return false;
            }

            // Compressed data might contain a full zlib header, if so strip it for
            // gzinflate()
            if(substr($gz_data, 0, 3) === "\x1f\x8b\x08")
            {
                $i = 10;
                $flg = ord(substr($gz_data, 3, 1));
                if($flg > 0)
                {
                    if($flg & 4)
                    {
                        [$xlen] = unpack('v', substr($gz_data, $i, 2));
                        $i += 2 + $xlen;
                    }

                    if($flg & 8)
                    {
                        $i = strpos($gz_data, "\0", $i) + 1;
                    }

                    if($flg & 16)
                    {
                        $i = strpos($gz_data, "\0", $i) + 1;
                    }

                    if($flg & 2)
                    {
                        $i += 2;
                    }
                }

                $decompressed = self::compatible_gzinflate(substr($gz_data, $i));
                if($decompressed !== false)
                {
                    return $decompressed;
                }
            }

            // If the data is Huffman Encoded, we must first strip the leading 2
            // byte Huffman marker for gzinflate()
            // The response is Huffman coded by many compressors such as
            // java.util.zip.Deflater, Ruby's Zlib::Deflate, and .NET's
            // System.IO.Compression.DeflateStream.
            //
            // See https://decompres.blogspot.com/ for a quick explanation of this
            // data type
            $huffman_encoded = false;

            // low nibble of first byte should be 0x08
            [, $first_nibble] = unpack('h', $gz_data);

            // First 2 bytes should be divisible by 0x1F
            [, $first_two_bytes] = unpack('n', $gz_data);

            if($first_nibble === 0x08 && ($first_two_bytes % 0x1F) === 0)
            {
                $huffman_encoded = true;
            }

            if($huffman_encoded)
            {
                $decompressed = @gzinflate(substr($gz_data, 2));
                if($decompressed !== false)
                {
                    return $decompressed;
                }
            }

            if(substr($gz_data, 0, 4) === "\x50\x4b\x03\x04")
            {
                // ZIP file format header
                // Offset 6: 2 bytes, General-purpose field
                // Offset 26: 2 bytes, filename length
                // Offset 28: 2 bytes, optional field length
                // Offset 30: Filename field, followed by optional field, followed
                // immediately by data
                [, $general_purpose_flag] = unpack('v', substr($gz_data, 6, 2));

                // If the file has been compressed on the fly, 0x08 bit is set of
                // the general purpose field. We can use this to differentiate
                // between a compressed document, and a ZIP file
                $zip_compressed_on_the_fly = ((0x08 & $general_purpose_flag) === 0x08);

                if(! $zip_compressed_on_the_fly)
                {
                    // Don't attempt to decode a compressed zip file
                    return $gz_data;
                }

                // Determine the first byte of data, based on the above ZIP header
                // offsets:
                $first_file_start = array_sum(unpack('v2', substr($gz_data, 26, 4)));
                $decompressed = @gzinflate(substr($gz_data, 30 + $first_file_start));
                if($decompressed !== false)
                {
                    return $decompressed;
                }

                return false;
            }

            // Finally fall back to straight gzinflate
            $decompressed = @gzinflate($gz_data);
            if($decompressed !== false)
            {
                return $decompressed;
            }

            // Fallback for all above failing, not expected, but included for
            // debugging and preventing regressions and to track stats
            $decompressed = @gzinflate(substr($gz_data, 2));
            if($decompressed !== false)
            {
                return $decompressed;
            }

            return false;
        }

        public static function head($url, $headers = [], $options = [])
        {
            return self::request($url, $headers, null, self::HEAD, $options);
        }

        public static function delete($url, $headers = [], $options = [])
        {
            return self::request($url, $headers, null, self::DELETE, $options);
        }

        public static function trace($url, $headers = [], $options = [])
        {
            return self::request($url, $headers, null, self::TRACE, $options);
        }

        public static function post($url, $headers = [], $data = [], $options = [])
        {
            return self::request($url, $headers, $data, self::POST, $options);
        }

        public static function put($url, $headers = [], $data = [], $options = [])
        {
            return self::request($url, $headers, $data, self::PUT, $options);
        }

        public static function options($url, $headers = [], $data = [], $options = [])
        {
            return self::request($url, $headers, $data, self::OPTIONS, $options);
        }

        public static function patch($url, $headers, $data = [], $options = [])
        {
            return self::request($url, $headers, $data, self::PATCH, $options);
        }

        public static function request_multiple($requests, $options = [])
        {
            if(InputValidator::has_array_access($requests) === false || InputValidator::is_iterable($requests) === false)
            {
                throw InvalidArgument::create(1, '$requests', 'array|ArrayAccess&Traversable', gettype($requests));
            }

            if(is_array($options) === false)
            {
                throw InvalidArgument::create(2, '$options', 'array', gettype($options));
            }

            $options = array_merge(self::get_default_options(true), $options);

            if(! empty($options['hooks']))
            {
                $options['hooks']->register('transport.internal.parse_response', [static::class, 'parse_multiple']);
                if(! empty($options['complete']))
                {
                    $options['hooks']->register('multiple.request.complete', $options['complete']);
                }
            }

            foreach($requests as $id => &$request)
            {
                if(! isset($request['headers']))
                {
                    $request['headers'] = [];
                }

                if(! isset($request['data']))
                {
                    $request['data'] = [];
                }

                if(! isset($request['type']))
                {
                    $request['type'] = self::GET;
                }

                if(! isset($request['options']))
                {
                    $request['options'] = $options;
                    $request['options']['type'] = $request['type'];
                }
                else
                {
                    if(empty($request['options']['type']))
                    {
                        $request['options']['type'] = $request['type'];
                    }

                    $request['options'] = array_merge($options, $request['options']);
                }

                self::set_defaults($request['url'], $request['headers'], $request['data'], $request['type'], $request['options']);

                // Ensure we only hook in once
                if($request['options']['hooks'] !== $options['hooks'])
                {
                    $request['options']['hooks']->register('transport.internal.parse_response', [
                        static::class,
                        'parse_multiple'
                    ]);
                    if(! empty($request['options']['complete']))
                    {
                        $request['options']['hooks']->register('multiple.request.complete', $request['options']['complete']);
                    }
                }
            }

            unset($request);

            if(! empty($options['transport']))
            {
                $transport = $options['transport'];

                if(is_string($options['transport']))
                {
                    $transport = new $transport();
                }
            }
            else
            {
                $transport = self::get_transport();
            }

            $responses = $transport->request_multiple($requests, $options);

            foreach($responses as $id => &$response)
            {
                // If our hook got messed with somehow, ensure we end up with the
                // correct response
                if(is_string($response))
                {
                    $request = $requests[$id];
                    self::parse_multiple($response, $request);
                    $request['options']['hooks']->dispatch('multiple.request.complete', [&$response, $id]);
                }
            }

            return $responses;
        }

        public static function parse_multiple(&$response, $request)
        {
            try
            {
                $url = $request['url'];
                $headers = $request['headers'];
                $data = $request['data'];
                $options = $request['options'];
                $response = self::parse_response($response, $url, $headers, $data, $options);
            }
            catch(Exception $e)
            {
                $response = $e;
            }
        }

        // @codeCoverageIgnoreEnd

        public static function get_certificate_path()
        {
            return self::$certificate_path;
        }

        public static function set_certificate_path($path)
        {
            if(InputValidator::is_string_or_stringable($path) === false && is_bool($path) === false)
            {
                throw InvalidArgument::create(1, '$path', 'string|Stringable|bool', gettype($path));
            }

            self::$certificate_path = $path;
        }

        public static function flatten($dictionary)
        {
            if(InputValidator::is_iterable($dictionary) === false)
            {
                throw InvalidArgument::create(1, '$dictionary', 'iterable', gettype($dictionary));
            }

            $return = [];
            foreach($dictionary as $key => $value)
            {
                $return[] = sprintf('%s: %s', $key, $value);
            }

            return $return;
        }
    }
