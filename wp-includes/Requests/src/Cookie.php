<?php

    namespace WpOrg\Requests;

    use WpOrg\Requests\Exception\InvalidArgument;
    use WpOrg\Requests\Response\Headers;
    use WpOrg\Requests\Utility\CaseInsensitiveDictionary;
    use WpOrg\Requests\Utility\InputValidator;

    class Cookie
    {
        public $name;

        public $value;

        public $attributes = [];

        public $flags = [];

        public $reference_time = 0;

        public function __construct($name, $value, $attributes = [], $flags = [], $reference_time = null)
        {
            if(is_string($name) === false)
            {
                throw InvalidArgument::create(1, '$name', 'string', gettype($name));
            }

            if(is_string($value) === false)
            {
                throw InvalidArgument::create(2, '$value', 'string', gettype($value));
            }

            if(InputValidator::has_array_access($attributes) === false || InputValidator::is_iterable($attributes) === false)
            {
                throw InvalidArgument::create(3, '$attributes', 'array|ArrayAccess&Traversable', gettype($attributes));
            }

            if(is_array($flags) === false)
            {
                throw InvalidArgument::create(4, '$flags', 'array', gettype($flags));
            }

            if($reference_time !== null && is_int($reference_time) === false)
            {
                throw InvalidArgument::create(5, '$reference_time', 'integer|null', gettype($reference_time));
            }

            $this->name = $name;
            $this->value = $value;
            $this->attributes = $attributes;
            $default_flags = [
                'creation' => time(),
                'last-access' => time(),
                'persistent' => false,
                'host-only' => true,
            ];
            $this->flags = array_merge($default_flags, $flags);

            $this->reference_time = time();
            if($reference_time !== null)
            {
                $this->reference_time = $reference_time;
            }

            $this->normalize();
        }

        public function normalize()
        {
            foreach($this->attributes as $key => $value)
            {
                $orig_value = $value;

                if(is_string($key))
                {
                    $value = $this->normalize_attribute($key, $value);
                }

                if($value === null)
                {
                    unset($this->attributes[$key]);
                    continue;
                }

                if($value !== $orig_value)
                {
                    $this->attributes[$key] = $value;
                }
            }

            return true;
        }

        protected function normalize_attribute($name, $value)
        {
            switch(strtolower($name))
            {
                case 'expires':
                    // Expiration parsing, as per RFC 6265 section 5.2.1
                    if(is_int($value))
                    {
                        return $value;
                    }

                    $expiry_time = strtotime($value);
                    if($expiry_time === false)
                    {
                        return null;
                    }

                    return $expiry_time;

                case 'max-age':
                    // Expiration parsing, as per RFC 6265 section 5.2.2
                    if(is_int($value))
                    {
                        return $value;
                    }

                    // Check that we have a valid age
                    if(! preg_match('/^-?\d+$/', $value))
                    {
                        return null;
                    }

                    $delta_seconds = (int) $value;
                    if($delta_seconds <= 0)
                    {
                        $expiry_time = 0;
                    }
                    else
                    {
                        $expiry_time = $this->reference_time + $delta_seconds;
                    }

                    return $expiry_time;

                case 'domain':
                    // Domains are not required as per RFC 6265 section 5.2.3
                    if(empty($value))
                    {
                        return null;
                    }

                    // Domain normalization, as per RFC 6265 section 5.2.3
                    if($value[0] === '.')
                    {
                        $value = substr($value, 1);
                    }

                    return $value;

                default:
                    return $value;
            }
        }

        public static function parse_from_headers(Headers $headers, Iri $origin = null, $time = null)
        {
            $cookie_headers = $headers->getValues('Set-Cookie');
            if(empty($cookie_headers))
            {
                return [];
            }

            $cookies = [];
            foreach($cookie_headers as $header)
            {
                $parsed = self::parse($header, '', $time);

                // Default domain/path attributes
                if(empty($parsed->attributes['domain']) && $origin !== null)
                {
                    $parsed->attributes['domain'] = $origin->host;
                    $parsed->flags['host-only'] = true;
                }
                else
                {
                    $parsed->flags['host-only'] = false;
                }

                $path_is_valid = (! empty($parsed->attributes['path']) && $parsed->attributes['path'][0] === '/');
                if(! $path_is_valid && $origin !== null)
                {
                    $path = $origin->path;

                    // Default path normalization as per RFC 6265 section 5.1.4
                    if(strpos($path, '/') !== 0)
                    {
                        // If the uri-path is empty or if the first character of
                        // the uri-path is not a %x2F ("/") character, output
                        // %x2F ("/") and skip the remaining steps.
                        $path = '/';
                    }
                    elseif(substr_count($path, '/') === 1)
                    {
                        // If the uri-path contains no more than one %x2F ("/")
                        // character, output %x2F ("/") and skip the remaining
                        // step.
                        $path = '/';
                    }
                    else
                    {
                        // Output the characters of the uri-path from the first
                        // character up to, but not including, the right-most
                        // %x2F ("/").
                        $path = substr($path, 0, strrpos($path, '/'));
                    }

                    $parsed->attributes['path'] = $path;
                }

                // Reject invalid cookie domains
                if($origin !== null && ! $parsed->domain_matches($origin->host))
                {
                    continue;
                }

                $cookies[$parsed->name] = $parsed;
            }

            return $cookies;
        }

        public static function parse($cookie_header, $name = '', $reference_time = null)
        {
            if(is_string($cookie_header) === false)
            {
                throw InvalidArgument::create(1, '$cookie_header', 'string', gettype($cookie_header));
            }

            if(is_string($name) === false)
            {
                throw InvalidArgument::create(2, '$name', 'string', gettype($name));
            }

            $parts = explode(';', $cookie_header);
            $kvparts = array_shift($parts);

            if(! empty($name))
            {
                $value = $cookie_header;
            }
            elseif(strpos($kvparts, '=') !== false)
            {
                [$name, $value] = explode('=', $kvparts, 2);
            }
            else
            {
                // Some sites might only have a value without the equals separator.
                // Deviate from RFC 6265 and pretend it was actually a blank name
                // (`=foo`)
                //
                // https://bugzilla.mozilla.org/show_bug.cgi?id=169091
                $name = '';
                $value = $kvparts;
            }

            $name = trim($name);
            $value = trim($value);

            // Attribute keys are handled case-insensitively
            $attributes = new CaseInsensitiveDictionary();

            if(! empty($parts))
            {
                foreach($parts as $part)
                {
                    if(strpos($part, '=') !== false)
                    {
                        [$part_key, $part_value] = explode('=', $part, 2);
                        $part_value = trim($part_value);
                    }
                    else
                    {
                        $part_key = $part;
                        $part_value = true;
                    }

                    $part_key = trim($part_key);
                    $attributes[$part_key] = $part_value;
                }
            }

            return new static($name, $value, $attributes, [], $reference_time);
        }

        public function domain_matches($domain)
        {
            if(is_string($domain) === false)
            {
                return false;
            }

            if(! isset($this->attributes['domain']))
            {
                // Cookies created manually; cookies created by Requests will set
                // the domain to the requested domain
                return true;
            }

            $cookie_domain = $this->attributes['domain'];
            if($cookie_domain === $domain)
            {
                // The cookie domain and the passed domain are identical.
                return true;
            }

            // If the cookie is marked as host-only and we don't have an exact
            // match, reject the cookie
            if($this->flags['host-only'] === true || strlen($domain) <= strlen($cookie_domain) || substr($domain, -1 * strlen($cookie_domain)) !== $cookie_domain)
            {
                // The cookie domain should be a suffix of the passed domain.
                return false;
            }

            $prefix = substr($domain, 0, strlen($domain) - strlen($cookie_domain));
            if(substr($prefix, -1) !== '.')
            {
                // The last character of the passed domain that is not included in the
                // domain string should be a %x2E (".") character.
                return false;
            }

            // The passed domain should be a host name (i.e., not an IP address).
            return ! preg_match('#^(.+\.)\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#', $domain);
        }

        public function __toString()
        {
            return $this->value;
        }

        public function is_expired()
        {
            // RFC6265, s. 4.1.2.2:
            // If a cookie has both the Max-Age and the Expires attribute, the Max-
            // Age attribute has precedence and controls the expiration date of the
            // cookie.
            if(isset($this->attributes['max-age']))
            {
                $max_age = $this->attributes['max-age'];

                return $max_age < $this->reference_time;
            }

            if(isset($this->attributes['expires']))
            {
                $expires = $this->attributes['expires'];

                return $expires < $this->reference_time;
            }

            return false;
        }

        public function uri_matches(Iri $uri)
        {
            if(! $this->domain_matches($uri->host) || ! $this->path_matches($uri->path))
            {
                return false;
            }

            return empty($this->attributes['secure']) || $uri->scheme === 'https';
        }

        public function path_matches($request_path)
        {
            if(empty($request_path))
            {
                // Normalize empty path to root
                $request_path = '/';
            }

            if(! isset($this->attributes['path']))
            {
                // Cookies created manually; cookies created by Requests will set
                // the path to the requested path
                return true;
            }

            if(is_scalar($request_path) === false)
            {
                return false;
            }

            $cookie_path = $this->attributes['path'];

            if($cookie_path === $request_path)
            {
                // The cookie-path and the request-path are identical.
                return true;
            }

            if(strlen($request_path) > strlen($cookie_path) && strpos($request_path, $cookie_path) === 0)
            {
                if(substr($cookie_path, -1) === '/' || substr($request_path, strlen($cookie_path), 1) === '/')
                {
                    // The cookie-path is a prefix of the request-path, and the
                    // first character of the request-path that is not included in
                    // the cookie-path is a %x2F ("/") character.
                    return true;
                }
            }

            return false;
        }

        public function format_for_set_cookie()
        {
            $header_value = $this->format_for_header();
            if(! empty($this->attributes))
            {
                $parts = [];
                foreach($this->attributes as $key => $value)
                {
                    // Ignore non-associative attributes
                    if(is_numeric($key))
                    {
                        $parts[] = $value;
                    }
                    else
                    {
                        $parts[] = sprintf('%s=%s', $key, $value);
                    }
                }

                $header_value .= '; '.implode('; ', $parts);
            }

            return $header_value;
        }

        public function format_for_header()
        {
            return sprintf('%s=%s', $this->name, $this->value);
        }
    }
