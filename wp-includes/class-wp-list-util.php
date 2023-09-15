<?php

    #[AllowDynamicProperties]
    class WP_List_Util
    {
        private $input = [];

        private $output = [];

        private $orderby = [];

        public function __construct($input)
        {
            $this->output = $input;
            $this->input = $input;
        }

        public function get_input()
        {
            return $this->input;
        }

        public function get_output()
        {
            return $this->output;
        }

        public function filter($args = [], $operator = 'AND')
        {
            if(empty($args))
            {
                return $this->output;
            }

            $operator = strtoupper($operator);

            if(! in_array($operator, ['AND', 'OR', 'NOT'], true))
            {
                $this->output = [];

                return $this->output;
            }

            $count = count($args);
            $filtered = [];

            foreach($this->output as $key => $obj)
            {
                $matched = 0;

                foreach($args as $m_key => $m_value)
                {
                    if(is_array($obj))
                    {
                        // Treat object as an array.
                        if(array_key_exists($m_key, $obj) && ($m_value == $obj[$m_key]))
                        {
                            ++$matched;
                        }
                    }
                    elseif(is_object($obj))
                    {
                        // Treat object as an object.
                        if(isset($obj->{$m_key}) && ($m_value == $obj->{$m_key}))
                        {
                            ++$matched;
                        }
                    }
                }

                if(('AND' === $operator && $matched === $count) || ('OR' === $operator && $matched > 0) || ('NOT' === $operator && 0 === $matched))
                {
                    $filtered[$key] = $obj;
                }
            }

            $this->output = $filtered;

            return $this->output;
        }

        public function pluck($field, $index_key = null)
        {
            $newlist = [];

            if(! $index_key)
            {
                /*
                 * This is simple. Could at some point wrap array_column()
                 * if we knew we had an array of arrays.
                 */
                foreach($this->output as $key => $value)
                {
                    if(is_object($value))
                    {
                        $newlist[$key] = $value->$field;
                    }
                    elseif(is_array($value))
                    {
                        $newlist[$key] = $value[$field];
                    }
                    else
                    {
                        _doing_it_wrong(__METHOD__, __('Values for the input array must be either objects or arrays.'), '6.2.0');
                    }
                }

                $this->output = $newlist;

                return $this->output;
            }

            /*
             * When index_key is not set for a particular item, push the value
             * to the end of the stack. This is how array_column() behaves.
             */
            foreach($this->output as $value)
            {
                if(is_object($value))
                {
                    if(isset($value->$index_key))
                    {
                        $newlist[$value->$index_key] = $value->$field;
                    }
                    else
                    {
                        $newlist[] = $value->$field;
                    }
                }
                elseif(is_array($value))
                {
                    if(isset($value[$index_key]))
                    {
                        $newlist[$value[$index_key]] = $value[$field];
                    }
                    else
                    {
                        $newlist[] = $value[$field];
                    }
                }
                else
                {
                    _doing_it_wrong(__METHOD__, __('Values for the input array must be either objects or arrays.'), '6.2.0');
                }
            }

            $this->output = $newlist;

            return $this->output;
        }

        public function sort($orderby = [], $order = 'ASC', $preserve_keys = false)
        {
            if(empty($orderby))
            {
                return $this->output;
            }

            if(is_string($orderby))
            {
                $orderby = [$orderby => $order];
            }

            foreach($orderby as $field => $direction)
            {
                $orderby[$field] = 'DESC' === strtoupper($direction) ? 'DESC' : 'ASC';
            }

            $this->orderby = $orderby;

            if($preserve_keys)
            {
                uasort($this->output, [$this, 'sort_callback']);
            }
            else
            {
                usort($this->output, [$this, 'sort_callback']);
            }

            $this->orderby = [];

            return $this->output;
        }

        private function sort_callback($a, $b)
        {
            if(empty($this->orderby))
            {
                return 0;
            }

            $a = (array) $a;
            $b = (array) $b;

            foreach($this->orderby as $field => $direction)
            {
                if(! isset($a[$field]) || ! isset($b[$field]))
                {
                    continue;
                }

                if($a[$field] == $b[$field])
                {
                    continue;
                }

                $results = 'DESC' === $direction ? [1, -1] : [-1, 1];

                if(is_numeric($a[$field]) && is_numeric($b[$field]))
                {
                    return ($a[$field] < $b[$field]) ? $results[0] : $results[1];
                }

                return 0 > strcmp($a[$field], $b[$field]) ? $results[0] : $results[1];
            }

            return 0;
        }
    }
