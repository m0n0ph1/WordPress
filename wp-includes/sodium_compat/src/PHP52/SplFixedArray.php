<?php

    if(class_exists('SplFixedArray'))
    {
        return;
    }

    class SplFixedArray implements Iterator, ArrayAccess, Countable
    {
        private $internalArray = [];

        private $size = 0;

        public function __construct($size = 0)
        {
            $this->size = $size;
            $this->internalArray = [];
        }

        public static function fromArray(array $array, $save_indexes = true)
        {
            $self = new SplFixedArray(count($array));
            if($save_indexes)
            {
                foreach($array as $key => $value)
                {
                    $self[(int) $key] = $value;
                }
            }
            else
            {
                $i = 0;
                foreach(array_values($array) as $value)
                {
                    $self[$i] = $value;
                    $i++;
                }
            }

            return $self;
        }

        public function count()
        {
            return count($this->internalArray);
        }

        public function toArray()
        {
            ksort($this->internalArray);

            return (array) $this->internalArray;
        }

        public function getSize()
        {
            return $this->size;
        }

        public function setSize($size)
        {
            $this->size = $size;

            return true;
        }

        public function offsetExists($index)
        {
            return array_key_exists((int) $index, $this->internalArray);
        }

        public function offsetGet($index)
        {
            return $this->internalArray[(int) $index];
        }

        public function offsetSet($index, $newval)
        {
            $this->internalArray[(int) $index] = $newval;
        }

        public function offsetUnset($index)
        {
            unset($this->internalArray[(int) $index]);
        }

        public function rewind()
        {
            reset($this->internalArray);
        }

        public function current()
        {
            return current($this->internalArray);
        }

        public function key()
        {
            return key($this->internalArray);
        }

        public function next()
        {
            next($this->internalArray);
        }

        public function valid()
        {
            if(empty($this->internalArray))
            {
                return false;
            }
            $result = next($this->internalArray) !== false;
            prev($this->internalArray);

            return $result;
        }

        public function __wakeup()
        {
            // NOP
        }
    }