<?php

    namespace WpOrg\Requests\Response;

    use WpOrg\Requests\Exception;
    use WpOrg\Requests\Exception\InvalidArgument;
    use WpOrg\Requests\Utility\CaseInsensitiveDictionary;
    use WpOrg\Requests\Utility\FilteredIterator;

    class Headers extends CaseInsensitiveDictionary
    {
        public function offsetGet($offset)
        {
            if(is_string($offset))
            {
                $offset = strtolower($offset);
            }

            if(! isset($this->data[$offset]))
            {
                return null;
            }

            return $this->flatten($this->data[$offset]);
        }

        public function flatten($value)
        {
            if(is_string($value))
            {
                return $value;
            }

            if(is_array($value))
            {
                return implode(',', $value);
            }

            throw InvalidArgument::create(1, '$value', 'string|array', gettype($value));
        }

        public function offsetSet($offset, $value)
        {
            if($offset === null)
            {
                throw new Exception('Object is a dictionary, not a list', 'invalidset');
            }

            if(is_string($offset))
            {
                $offset = strtolower($offset);
            }

            if(! isset($this->data[$offset]))
            {
                $this->data[$offset] = [];
            }

            $this->data[$offset][] = $value;
        }

        public function getValues($offset)
        {
            if(! is_string($offset) && ! is_int($offset))
            {
                throw InvalidArgument::create(1, '$offset', 'string|int', gettype($offset));
            }

            if(is_string($offset))
            {
                $offset = strtolower($offset);
            }

            if(! isset($this->data[$offset]))
            {
                return null;
            }

            return $this->data[$offset];
        }

        public function getIterator()
        {
            return new FilteredIterator($this->data, [$this, 'flatten']);
        }
    }
