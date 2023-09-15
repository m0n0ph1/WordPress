<?php

    namespace WpOrg\Requests\Cookie;

    use ArrayAccess;
    use ArrayIterator;
    use IteratorAggregate;
    use ReturnTypeWillChange;
    use WpOrg\Requests\Cookie;
    use WpOrg\Requests\Exception;
    use WpOrg\Requests\Exception\InvalidArgument;
    use WpOrg\Requests\HookManager;
    use WpOrg\Requests\Iri;
    use WpOrg\Requests\Response;

    class Jar implements ArrayAccess, IteratorAggregate
    {
        protected $cookies = [];

        public function __construct($cookies = [])
        {
            if(is_array($cookies) === false)
            {
                throw InvalidArgument::create(1, '$cookies', 'array', gettype($cookies));
            }

            $this->cookies = $cookies;
        }

        #[ReturnTypeWillChange]
        public function offsetExists($offset)
        {
            return isset($this->cookies[$offset]);
        }

        #[ReturnTypeWillChange]
        public function offsetGet($offset)
        {
            if(! isset($this->cookies[$offset]))
            {
                return null;
            }

            return $this->cookies[$offset];
        }

        #[ReturnTypeWillChange]
        public function offsetSet($offset, $value)
        {
            if($offset === null)
            {
                throw new Exception('Object is a dictionary, not a list', 'invalidset');
            }

            $this->cookies[$offset] = $value;
        }

        #[ReturnTypeWillChange]
        public function offsetUnset($offset)
        {
            unset($this->cookies[$offset]);
        }

        #[ReturnTypeWillChange]
        public function getIterator()
        {
            return new ArrayIterator($this->cookies);
        }

        public function register(HookManager $hooks)
        {
            $hooks->register('requests.before_request', [$this, 'before_request']);
            $hooks->register('requests.before_redirect_check', [$this, 'before_redirect_check']);
        }

        public function before_request($url, &$headers, &$data, &$type, &$options)
        {
            if(! $url instanceof Iri)
            {
                $url = new Iri($url);
            }

            if(! empty($this->cookies))
            {
                $cookies = [];
                foreach($this->cookies as $key => $cookie)
                {
                    $cookie = $this->normalize_cookie($cookie, $key);

                    // Skip expired cookies
                    if($cookie->is_expired())
                    {
                        continue;
                    }

                    if($cookie->domain_matches($url->host))
                    {
                        $cookies[] = $cookie->format_for_header();
                    }
                }

                $headers['Cookie'] = implode('; ', $cookies);
            }
        }

        public function normalize_cookie($cookie, $key = '')
        {
            if($cookie instanceof Cookie)
            {
                return $cookie;
            }

            return Cookie::parse($cookie, $key);
        }

        public function before_redirect_check(Response $response)
        {
            $url = $response->url;
            if(! $url instanceof Iri)
            {
                $url = new Iri($url);
            }

            $cookies = Cookie::parse_from_headers($response->headers, $url);
            $this->cookies = array_merge($this->cookies, $cookies);
            $response->cookies = $this;
        }
    }
