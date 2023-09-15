<?php

    if(class_exists('ParagonIE_Sodium_Core_Poly1305', false))
    {
        return;
    }

    abstract class Poly1305 extends ParagonIE_Sodium_Core_Util
    {
        public const BLOCK_SIZE = 16;

        public static function onetimeauth($m, $key)
        {
            if(self::strlen($key) < 32)
            {
                throw new InvalidArgumentException('Key must be 32 bytes long.');
            }
            $state = new ParagonIE_Sodium_Core_Poly1305_State(self::substr($key, 0, 32));

            return $state->update($m)->finish();
        }

        public static function onetimeauth_verify($mac, $m, $key)
        {
            if(self::strlen($key) < 32)
            {
                throw new InvalidArgumentException('Key must be 32 bytes long.');
            }
            $state = new ParagonIE_Sodium_Core_Poly1305_State(self::substr($key, 0, 32));
            $calc = $state->update($m)->finish();

            return self::verify_16($calc, $mac);
        }
    }
