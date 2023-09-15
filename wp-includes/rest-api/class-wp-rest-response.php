<?php

    class WP_REST_Response extends WP_HTTP_Response
    {
        protected $links = [];

        protected $matched_route = '';

        protected $matched_handler = null;

        public function remove_link($rel, $href = null)
        {
            if(! isset($this->links[$rel]))
            {
                return;
            }

            if($href)
            {
                $this->links[$rel] = wp_list_filter($this->links[$rel], ['href' => $href], 'NOT');
            }
            else
            {
                $this->links[$rel] = [];
            }

            if(! $this->links[$rel])
            {
                unset($this->links[$rel]);
            }
        }

        public function add_links($links)
        {
            foreach($links as $rel => $set)
            {
                // If it's a single link, wrap with an array for consistent handling.
                if(isset($set['href']))
                {
                    $set = [$set];
                }

                foreach($set as $attributes)
                {
                    $this->add_link($rel, $attributes['href'], $attributes);
                }
            }
        }

        public function add_link($rel, $href, $attributes = [])
        {
            if(empty($this->links[$rel]))
            {
                $this->links[$rel] = [];
            }

            if(isset($attributes['href']))
            {
                // Remove the href attribute, as it's used for the main URL.
                unset($attributes['href']);
            }

            $this->links[$rel][] = compact('href', 'attributes');
        }

        public function get_links()
        {
            return $this->links;
        }

        public function link_header($rel, $link, $other = [])
        {
            $header = '<'.$link.'>; rel="'.$rel.'"';

            foreach($other as $key => $value)
            {
                if('title' === $key)
                {
                    $value = '"'.$value.'"';
                }

                $header .= '; '.$key.'='.$value;
            }
            $this->header('Link', $header, false);
        }

        public function get_matched_route()
        {
            return $this->matched_route;
        }

        public function set_matched_route($route)
        {
            $this->matched_route = $route;
        }

        public function get_matched_handler()
        {
            return $this->matched_handler;
        }

        public function set_matched_handler($handler)
        {
            $this->matched_handler = $handler;
        }

        public function as_error()
        {
            if(! $this->is_error())
            {
                return null;
            }

            $error = new WP_Error();

            if(is_array($this->get_data()))
            {
                $data = $this->get_data();
                $error->add($data['code'], $data['message'], $data['data']);

                if(! empty($data['additional_errors']))
                {
                    foreach($data['additional_errors'] as $err)
                    {
                        $error->add($err['code'], $err['message'], $err['data']);
                    }
                }
            }
            else
            {
                $error->add($this->get_status(), '', ['status' => $this->get_status()]);
            }

            return $error;
        }

        public function is_error()
        {
            return $this->get_status() >= 400;
        }

        public function get_curies()
        {
            $curies = [
                [
                    'name' => 'wp',
                    'href' => 'https://api.w.org/{rel}',
                    'templated' => true,
                ],
            ];

            $additional = apply_filters('rest_response_link_curies', []);

            return array_merge($curies, $additional);
        }
    }
