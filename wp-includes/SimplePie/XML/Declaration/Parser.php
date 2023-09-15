<?php

    class Parser
    {
        public $version = '1.0';

        public $encoding = 'UTF-8';

        public $standalone = false;

        public $state = 'before_version_name';

        public $data = '';

        public $data_length = 0;

        public $position = 0;

        public function __construct($data)
        {
            $this->data = $data;
            $this->data_length = strlen($this->data);
        }

        public function parse()
        {
            while($this->state && $this->state !== 'emit' && $this->has_data())
            {
                $state = $this->state;
                $this->$state();
            }
            $this->data = '';
            if($this->state === 'emit')
            {
                return true;
            }

            $this->version = '';
            $this->encoding = '';
            $this->standalone = '';

            return false;
        }

        public function has_data()
        {
            return (bool) ($this->position < $this->data_length);
        }

        public function before_version_name()
        {
            if($this->skip_whitespace())
            {
                $this->state = 'version_name';
            }
            else
            {
                $this->state = false;
            }
        }

        public function skip_whitespace()
        {
            $whitespace = strspn($this->data, "\x09\x0A\x0D\x20", $this->position);
            $this->position += $whitespace;

            return $whitespace;
        }

        public function version_name()
        {
            if(substr($this->data, $this->position, 7) === 'version')
            {
                $this->position += 7;
                $this->skip_whitespace();
                $this->state = 'version_equals';
            }
            else
            {
                $this->state = false;
            }
        }

        public function version_equals()
        {
            if(substr($this->data, $this->position, 1) === '=')
            {
                $this->position++;
                $this->skip_whitespace();
                $this->state = 'version_value';
            }
            else
            {
                $this->state = false;
            }
        }

        public function version_value()
        {
            if($this->version = $this->get_value())
            {
                $this->skip_whitespace();
                if($this->has_data())
                {
                    $this->state = 'encoding_name';
                }
                else
                {
                    $this->state = 'emit';
                }
            }
            else
            {
                $this->state = false;
            }
        }

        public function get_value()
        {
            $quote = substr($this->data, $this->position, 1);
            if($quote === '"' || $quote === "'")
            {
                $this->position++;
                $len = strcspn($this->data, $quote, $this->position);
                if($this->has_data())
                {
                    $value = substr($this->data, $this->position, $len);
                    $this->position += $len + 1;

                    return $value;
                }
            }

            return false;
        }

        public function encoding_name()
        {
            if(substr($this->data, $this->position, 8) === 'encoding')
            {
                $this->position += 8;
                $this->skip_whitespace();
                $this->state = 'encoding_equals';
            }
            else
            {
                $this->state = 'standalone_name';
            }
        }

        public function encoding_equals()
        {
            if(substr($this->data, $this->position, 1) === '=')
            {
                $this->position++;
                $this->skip_whitespace();
                $this->state = 'encoding_value';
            }
            else
            {
                $this->state = false;
            }
        }

        public function encoding_value()
        {
            if($this->encoding = $this->get_value())
            {
                $this->skip_whitespace();
                if($this->has_data())
                {
                    $this->state = 'standalone_name';
                }
                else
                {
                    $this->state = 'emit';
                }
            }
            else
            {
                $this->state = false;
            }
        }

        public function standalone_name()
        {
            if(substr($this->data, $this->position, 10) === 'standalone')
            {
                $this->position += 10;
                $this->skip_whitespace();
                $this->state = 'standalone_equals';
            }
            else
            {
                $this->state = false;
            }
        }

        public function standalone_equals()
        {
            if(substr($this->data, $this->position, 1) === '=')
            {
                $this->position++;
                $this->skip_whitespace();
                $this->state = 'standalone_value';
            }
            else
            {
                $this->state = false;
            }
        }

        public function standalone_value()
        {
            if($standalone = $this->get_value())
            {
                switch($standalone)
                {
                    case 'yes':
                        $this->standalone = true;
                        break;

                    case 'no':
                        $this->standalone = false;
                        break;

                    default:
                        $this->state = false;

                        return;
                }

                $this->skip_whitespace();
                if($this->has_data())
                {
                    $this->state = false;
                }
                else
                {
                    $this->state = 'emit';
                }
            }
            else
            {
                $this->state = false;
            }
        }
    }
