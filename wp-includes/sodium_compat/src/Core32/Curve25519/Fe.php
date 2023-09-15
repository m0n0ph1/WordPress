<?php

    if(class_exists('ParagonIE_Sodium_Core32_Curve25519_Fe', false))
    {
        return;
    }

    class Fe implements ArrayAccess
    {
        protected $container = [];

        protected $size = 10;

        public static function fromArray($array, $save_indexes = null)
        {
            $count = count($array);
            if($save_indexes)
            {
                $keys = array_keys($array);
            }
            else
            {
                $keys = range(0, $count - 1);
            }
            $array = array_values($array);

            $obj = new ParagonIE_Sodium_Core32_Curve25519_Fe();
            if($save_indexes)
            {
                for($i = 0; $i < $count; ++$i)
                {
                    $array[$i]->overflow = 0;
                    $obj->offsetSet($keys[$i], $array[$i]);
                }
            }
            else
            {
                foreach($array as $i => $iValue)
                {
                    if(! ($iValue instanceof ParagonIE_Sodium_Core32_Int32))
                    {
                        throw new TypeError('Expected ParagonIE_Sodium_Core32_Int32');
                    }
                    $iValue->overflow = 0;
                    $obj->offsetSet($i, $array[$i]);
                }
            }

            return $obj;
        }

        #[ReturnTypeWillChange]
        public function offsetSet($offset, $value)
        {
            if(! ($value instanceof ParagonIE_Sodium_Core32_Int32))
            {
                throw new InvalidArgumentException('Expected an instance of ParagonIE_Sodium_Core32_Int32');
            }
            if(is_null($offset))
            {
                $this->container[] = $value;
            }
            else
            {
                ParagonIE_Sodium_Core32_Util::declareScalarType($offset, 'int', 1);
                $this->container[(int) $offset] = $value;
            }
        }

        public static function fromIntArray($array, $save_indexes = null)
        {
            $count = count($array);
            if($save_indexes)
            {
                $keys = array_keys($array);
            }
            else
            {
                $keys = range(0, $count - 1);
            }
            $array = array_values($array);
            $set = [];

            foreach($array as $i => $v)
            {
                $set[$i] = ParagonIE_Sodium_Core32_Int32::fromInt($v);
            }

            $obj = new ParagonIE_Sodium_Core32_Curve25519_Fe();
            if($save_indexes)
            {
                for($i = 0; $i < $count; ++$i)
                {
                    $set[$i]->overflow = 0;
                    $obj->offsetSet($keys[$i], $set[$i]);
                }
            }
            else
            {
                for($i = 0; $i < $count; ++$i)
                {
                    $set[$i]->overflow = 0;
                    $obj->offsetSet($i, $set[$i]);
                }
            }

            return $obj;
        }

        #[ReturnTypeWillChange]
        public function offsetExists($offset)
        {
            return isset($this->container[$offset]);
        }

        #[ReturnTypeWillChange]
        public function offsetUnset($offset)
        {
            unset($this->container[$offset]);
        }

        #[ReturnTypeWillChange]
        public function offsetGet($offset)
        {
            if(! isset($this->container[$offset]))
            {
                $this->container[(int) $offset] = new ParagonIE_Sodium_Core32_Int32();
            }

            $get = $this->container[$offset];

            return $get;
        }

        public function __debugInfo()
        {
            if(empty($this->container))
            {
                return [];
            }
            $c = [
                (int) ($this->container[0]->toInt()),
                (int) ($this->container[1]->toInt()),
                (int) ($this->container[2]->toInt()),
                (int) ($this->container[3]->toInt()),
                (int) ($this->container[4]->toInt()),
                (int) ($this->container[5]->toInt()),
                (int) ($this->container[6]->toInt()),
                (int) ($this->container[7]->toInt()),
                (int) ($this->container[8]->toInt()),
                (int) ($this->container[9]->toInt())
            ];

            return [implode(', ', $c)];
        }
    }
