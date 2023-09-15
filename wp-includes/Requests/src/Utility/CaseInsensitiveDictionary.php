<?php

    namespace WpOrg\Requests\Utility;

    use ArrayAccess;
    use ArrayIterator;
    use IteratorAggregate;
    use ReturnTypeWillChange;
    use WpOrg\Requests\Exception;

    class CaseInsensitiveDictionary implements ArrayAccess, IteratorAggregate
    {
        protected $data = [];

        public function __construct(array $data = [])
        {
            foreach($data as $offset => $value)
            {
                $this->offsetSet($offset, $value);
            }
        }

        #[ReturnTypeWillChange]
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

            $this->data[$offset] = $value;
        }

        #[ReturnTypeWillChange]
        public function offsetExists($offset)
        {
            if(is_string($offset))
            {
                $offset = strtolower($offset);
            }

            return isset($this->data[$offset]);
        }

        #[ReturnTypeWillChange]
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

            return $this->data[$offset];
        }

        #[ReturnTypeWillChange]
        public function offsetUnset($offset)
        {
            if(is_string($offset))
            {
                $offset = strtolower($offset);
            }

            unset($this->data[$offset]);
        }

        #[ReturnTypeWillChange]
        public function getIterator()
        {
            return new ArrayIterator($this->data);
        }

        public function getAll()
        {
            return $this->data;
        }
    }
