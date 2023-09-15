<?php

    if(class_exists('ParagonIE_Sodium_Core_ChaCha20_IetfCtx', false))
    {
        return;
    }

    class ParagonIE_Sodium_Core32_ChaCha20_IetfCtx extends ParagonIE_Sodium_Core32_ChaCha20_Ctx
    {
        public function __construct($key = '', $iv = '', $counter = '')
        {
            if(self::strlen($iv) !== 12)
            {
                throw new InvalidArgumentException('ChaCha20 expects a 96-bit nonce in IETF mode.');
            }
            parent::__construct($key, self::substr($iv, 0, 8), $counter);

            if(! empty($counter))
            {
                $this->container[12] = ParagonIE_Sodium_Core32_Int32::fromReverseString(self::substr($counter, 0, 4));
            }
            $this->container[13] = ParagonIE_Sodium_Core32_Int32::fromReverseString(self::substr($iv, 0, 4));
            $this->container[14] = ParagonIE_Sodium_Core32_Int32::fromReverseString(self::substr($iv, 4, 4));
            $this->container[15] = ParagonIE_Sodium_Core32_Int32::fromReverseString(self::substr($iv, 8, 4));
        }
    }
