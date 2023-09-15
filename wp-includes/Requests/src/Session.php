<?php

    namespace WpOrg\Requests;

    use WpOrg\Requests\Cookie\Jar;
    use WpOrg\Requests\Exception\InvalidArgument;
    use WpOrg\Requests\Utility\InputValidator;

    class Session
    {
        public $url = null;

        public $headers = [];

        public $data = [];

        public $options = [];

        public function __construct($url = null, $headers = [], $data = [], $options = [])
        {
            if($url !== null && InputValidator::is_string_or_stringable($url) === false)
            {
                throw InvalidArgument::create(1, '$url', 'string|Stringable|null', gettype($url));
            }

            if(is_array($headers) === false)
            {
                throw InvalidArgument::create(2, '$headers', 'array', gettype($headers));
            }

            if(is_array($data) === false)
            {
                throw InvalidArgument::create(3, '$data', 'array', gettype($data));
            }

            if(is_array($options) === false)
            {
                throw InvalidArgument::create(4, '$options', 'array', gettype($options));
            }

            $this->url = $url;
            $this->headers = $headers;
            $this->data = $data;
            $this->options = $options;

            if(empty($this->options['cookies']))
            {
                $this->options['cookies'] = new Jar();
            }
        }

        public function __get($name)
        {
            if(isset($this->options[$name]))
            {
                return $this->options[$name];
            }

            return null;
        }

        public function __set($name, $value)
        {
            $this->options[$name] = $value;
        }

        public function __isset($name)
        {
            return isset($this->options[$name]);
        }

        public function __unset($name)
        {
            unset($this->options[$name]);
        }

        public function get($url, $headers = [], $options = [])
        {
            return $this->request($url, $headers, null, Requests::GET, $options);
        }

        public function request($url, $headers = [], $data = [], $type = Requests::GET, $options = [])
        {
            $request = $this->merge_request(compact('url', 'headers', 'data', 'options'));

            return Requests::request($request['url'], $request['headers'], $request['data'], $type, $request['options']);
        }

        protected function merge_request($request, $merge_options = true)
        {
            if($this->url !== null)
            {
                $request['url'] = Iri::absolutize($this->url, $request['url']);
                $request['url'] = $request['url']->uri;
            }

            if(empty($request['headers']))
            {
                $request['headers'] = [];
            }

            $request['headers'] = array_merge($this->headers, $request['headers']);

            if(empty($request['data']))
            {
                if(is_array($this->data))
                {
                    $request['data'] = $this->data;
                }
            }
            elseif(is_array($request['data']) && is_array($this->data))
            {
                $request['data'] = array_merge($this->data, $request['data']);
            }

            if($merge_options === true)
            {
                $request['options'] = array_merge($this->options, $request['options']);

                // Disallow forcing the type, as that's a per request setting
                unset($request['options']['type']);
            }

            return $request;
        }

        public function head($url, $headers = [], $options = [])
        {
            return $this->request($url, $headers, null, Requests::HEAD, $options);
        }

        public function delete($url, $headers = [], $options = [])
        {
            return $this->request($url, $headers, null, Requests::DELETE, $options);
        }

        public function post($url, $headers = [], $data = [], $options = [])
        {
            return $this->request($url, $headers, $data, Requests::POST, $options);
        }

        public function put($url, $headers = [], $data = [], $options = [])
        {
            return $this->request($url, $headers, $data, Requests::PUT, $options);
        }

        public function patch($url, $headers, $data = [], $options = [])
        {
            return $this->request($url, $headers, $data, Requests::PATCH, $options);
        }

        public function request_multiple($requests, $options = [])
        {
            if(InputValidator::has_array_access($requests) === false || InputValidator::is_iterable($requests) === false)
            {
                throw InvalidArgument::create(1, '$requests', 'array|ArrayAccess&Traversable', gettype($requests));
            }

            if(is_array($options) === false)
            {
                throw InvalidArgument::create(2, '$options', 'array', gettype($options));
            }

            foreach($requests as $key => $request)
            {
                $requests[$key] = $this->merge_request($request, false);
            }

            $options = array_merge($this->options, $options);

            // Disallow forcing the type, as that's a per request setting
            unset($options['type']);

            return Requests::request_multiple($requests, $options);
        }
    }
