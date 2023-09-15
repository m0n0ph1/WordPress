<?php

    if(class_exists('ParagonIE_Sodium_Core_ChaCha20_IetfCtx', false))
    {
        return;
    }

    class IetfCtx extends ParagonIE_Sodium_Core_ChaCha20_Ctx
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
                $this->container[12] = self::load_4(self::substr($counter, 0, 4));
            }
            $this->container[13] = self::load_4(self::substr($iv, 0, 4));
            $this->container[14] = self::load_4(self::substr($iv, 4, 4));
            $this->container[15] = self::load_4(self::substr($iv, 8, 4));
        }
    }
