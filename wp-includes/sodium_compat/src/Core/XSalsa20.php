<?php

    if(class_exists('ParagonIE_Sodium_Core_XSalsa20', false))
    {
        return;
    }

    abstract class ParagonIE_Sodium_Core_XSalsa20 extends ParagonIE_Sodium_Core_HSalsa20
    {
        public static function xsalsa20_xor($message, $nonce, $key)
        {
            return self::xorStrings($message, self::xsalsa20(self::strlen($message), $nonce, $key));
        }

        public static function xsalsa20($len, $nonce, $key)
        {
            $ret = self::salsa20($len, self::substr($nonce, 16, 8), self::hsalsa20($nonce, $key));

            return $ret;
        }
    }
