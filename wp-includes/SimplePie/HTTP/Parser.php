<?php

    class SimplePie_HTTP_Parser
    {
        public $http_version = 0.0;

        public $status_code = 0;

        public $reason = '';

        public $headers = [];

        public $body = '';

        protected $state = 'http_version';

        protected $data = '';

        protected $data_length = 0;

        protected $position = 0;

        protected $name = '';

        protected $value = '';

        public function __construct($data)
        {
            $this->data = $data;
            $this->data_length = strlen($this->data);
        }

        static public function prepareHeaders($headers, $count = 1)
        {
            $data = explode("\r\n\r\n", $headers, $count);
            $data = array_pop($data);
            if(false !== stripos($data, "HTTP/1.0 200 Connection established\r\n"))
            {
                $exploded = explode("\r\n\r\n", $data, 2);
                $data = end($exploded);
            }
            if(false !== stripos($data, "HTTP/1.1 200 Connection established\r\n"))
            {
                $exploded = explode("\r\n\r\n", $data, 2);
                $data = end($exploded);
            }

            return $data;
        }

        public function parse()
        {
            while($this->state && $this->state !== 'emit' && $this->has_data())
            {
                $state = $this->state;
                $this->$state();
            }
            $this->data = '';
            if($this->state === 'emit' || $this->state === 'body')
            {
                return true;
            }

            $this->http_version = '';
            $this->status_code = '';
            $this->reason = '';
            $this->headers = [];
            $this->body = '';

            return false;
        }

        protected function has_data()
        {
            return (bool) ($this->position < $this->data_length);
        }

        protected function http_version()
        {
            if(strpos($this->data, "\x0A") !== false && strtoupper(substr($this->data, 0, 5)) === 'HTTP/')
            {
                $len = strspn($this->data, '0123456789.', 5);
                $this->http_version = substr($this->data, 5, $len);
                $this->position += 5 + $len;
                if(substr_count($this->http_version, '.') <= 1)
                {
                    $this->http_version = (float) $this->http_version;
                    $this->position += strspn($this->data, "\x09\x20", $this->position);
                    $this->state = 'status';
                }
                else
                {
                    $this->state = false;
                }
            }
            else
            {
                $this->state = false;
            }
        }

        protected function status()
        {
            if($len = strspn($this->data, '0123456789', $this->position))
            {
                $this->status_code = (int) substr($this->data, $this->position, $len);
                $this->position += $len;
                $this->state = 'reason';
            }
            else
            {
                $this->state = false;
            }
        }

        protected function reason()
        {
            $len = strcspn($this->data, "\x0A", $this->position);
            $this->reason = trim(substr($this->data, $this->position, $len), "\x09\x0D\x20");
            $this->position += $len + 1;
            $this->state = 'new_line';
        }

        protected function new_line()
        {
            $this->value = trim($this->value, "\x0D\x20");
            if($this->name !== '' && $this->value !== '')
            {
                $this->name = strtolower($this->name);
                // We should only use the last Content-Type header. c.f. issue #1
                if(isset($this->headers[$this->name]) && $this->name !== 'content-type')
                {
                    $this->headers[$this->name] .= ', '.$this->value;
                }
                else
                {
                    $this->headers[$this->name] = $this->value;
                }
            }
            $this->name = '';
            $this->value = '';
            if(substr($this->data[$this->position], 0, 2) === "\x0D\x0A")
            {
                $this->position += 2;
                $this->state = 'body';
            }
            elseif($this->data[$this->position] === "\x0A")
            {
                $this->position++;
                $this->state = 'body';
            }
            else
            {
                $this->state = 'name';
            }
        }

        protected function name()
        {
            $len = strcspn($this->data, "\x0A:", $this->position);
            if(isset($this->data[$this->position + $len]))
            {
                if($this->data[$this->position + $len] === "\x0A")
                {
                    $this->position += $len;
                    $this->state = 'new_line';
                }
                else
                {
                    $this->name = substr($this->data, $this->position, $len);
                    $this->position += $len + 1;
                    $this->state = 'value';
                }
            }
            else
            {
                $this->state = false;
            }
        }

        protected function value()
        {
            if($this->is_linear_whitespace())
            {
                $this->linear_whitespace();
            }
            else
            {
                switch($this->data[$this->position])
                {
                    case '"':
                        // Workaround for ETags: we have to include the quotes as
                        // part of the tag.
                        if(strtolower($this->name) === 'etag')
                        {
                            $this->value .= '"';
                            $this->position++;
                            $this->state = 'value_char';
                            break;
                        }
                        $this->position++;
                        $this->state = 'quote';
                        break;

                    case "\x0A":
                        $this->position++;
                        $this->state = 'new_line';
                        break;

                    default:
                        $this->state = 'value_char';
                        break;
                }
            }
        }

        protected function is_linear_whitespace()
        {
            return (bool) ($this->data[$this->position] === "\x09" || $this->data[$this->position] === "\x20" || ($this->data[$this->position] === "\x0A" && isset($this->data[$this->position + 1]) && ($this->data[$this->position + 1] === "\x09" || $this->data[$this->position + 1] === "\x20")));
        }

        protected function linear_whitespace()
        {
            do
            {
                if(substr($this->data, $this->position, 2) === "\x0D\x0A")
                {
                    $this->position += 2;
                }
                elseif($this->data[$this->position] === "\x0A")
                {
                    $this->position++;
                }
                $this->position += strspn($this->data, "\x09\x20", $this->position);
            }
            while($this->has_data() && $this->is_linear_whitespace());
            $this->value .= "\x20";
        }

        protected function value_char()
        {
            $len = strcspn($this->data, "\x09\x20\x0A\"", $this->position);
            $this->value .= substr($this->data, $this->position, $len);
            $this->position += $len;
            $this->state = 'value';
        }

        protected function quote()
        {
            if($this->is_linear_whitespace())
            {
                $this->linear_whitespace();
            }
            else
            {
                switch($this->data[$this->position])
                {
                    case '"':
                        $this->position++;
                        $this->state = 'value';
                        break;

                    case "\x0A":
                        $this->position++;
                        $this->state = 'new_line';
                        break;

                    case '\\':
                        $this->position++;
                        $this->state = 'quote_escaped';
                        break;

                    default:
                        $this->state = 'quote_char';
                        break;
                }
            }
        }

        protected function quote_char()
        {
            $len = strcspn($this->data, "\x09\x20\x0A\"\\", $this->position);
            $this->value .= substr($this->data, $this->position, $len);
            $this->position += $len;
            $this->state = 'value';
        }

        protected function quote_escaped()
        {
            $this->value .= $this->data[$this->position];
            $this->position++;
            $this->state = 'quote';
        }

        protected function body()
        {
            $this->body = substr($this->data, $this->position);
            if(! empty($this->headers['transfer-encoding']))
            {
                unset($this->headers['transfer-encoding']);
                $this->state = 'chunked';
            }
            else
            {
                $this->state = 'emit';
            }
        }

        protected function chunked()
        {
            if(! preg_match('/^([0-9a-f]+)[^\r\n]*\r\n/i', trim($this->body)))
            {
                $this->state = 'emit';

                return;
            }

            $decoded = '';
            $encoded = $this->body;

            while(true)
            {
                $is_chunked = (bool) preg_match('/^([0-9a-f]+)[^\r\n]*\r\n/i', $encoded, $matches);
                if(! $is_chunked)
                {
                    // Looks like it's not chunked after all
                    $this->state = 'emit';

                    return;
                }

                $length = hexdec(trim($matches[1]));
                if($length === 0)
                {
                    // Ignore trailer headers
                    $this->state = 'emit';
                    $this->body = $decoded;

                    return;
                }

                $chunk_length = strlen($matches[0]);
                $decoded .= $part = substr($encoded, $chunk_length, $length);
                $encoded = substr($encoded, $chunk_length + $length + 2);

                if(trim($encoded) === '0' || empty($encoded))
                {
                    $this->state = 'emit';
                    $this->body = $decoded;

                    return;
                }
            }
        }
    }
