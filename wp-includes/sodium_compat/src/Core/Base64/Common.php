<?php

    abstract class ParagonIE_Sodium_Core_Base64_Common
    {
        public static function encode($src)
        {
            return self::doEncode($src, true);
        }

        protected static function doEncode($src, $pad = true)
        {
            $dest = '';
            $srcLen = ParagonIE_Sodium_Core_Util::strlen($src);
            // Main loop (no padding):
            for($i = 0; $i + 3 <= $srcLen; $i += 3)
            {
                $chunk = unpack('C*', ParagonIE_Sodium_Core_Util::substr($src, $i, 3));
                $b0 = $chunk[1];
                $b1 = $chunk[2];
                $b2 = $chunk[3];

                $dest .= self::encode6Bits($b0 >> 2).self::encode6Bits((($b0 << 4) | ($b1 >> 4)) & 63).self::encode6Bits((($b1 << 2) | ($b2 >> 6)) & 63).self::encode6Bits($b2 & 63);
            }
            // The last chunk, which may have padding:
            if($i < $srcLen)
            {
                $chunk = unpack('C*', ParagonIE_Sodium_Core_Util::substr($src, $i, $srcLen - $i));
                $b0 = $chunk[1];
                if($i + 1 < $srcLen)
                {
                    $b1 = $chunk[2];
                    $dest .= self::encode6Bits($b0 >> 2).self::encode6Bits((($b0 << 4) | ($b1 >> 4)) & 63).self::encode6Bits(($b1 << 2) & 63);
                    if($pad)
                    {
                        $dest .= '=';
                    }
                }
                else
                {
                    $dest .= self::encode6Bits($b0 >> 2).self::encode6Bits(($b0 << 4) & 63);
                    if($pad)
                    {
                        $dest .= '==';
                    }
                }
            }

            return $dest;
        }

        abstract protected static function encode6Bits($src);

        public static function encodeUnpadded($src)
        {
            return self::doEncode($src, false);
        }

        public static function decode($src, $strictPadding = false)
        {
            // Remove padding
            $srcLen = ParagonIE_Sodium_Core_Util::strlen($src);
            if($srcLen === 0)
            {
                return '';
            }

            if($strictPadding)
            {
                if(($srcLen & 3) === 0)
                {
                    if($src[$srcLen - 1] === '=')
                    {
                        $srcLen--;
                        if($src[$srcLen - 1] === '=')
                        {
                            $srcLen--;
                        }
                    }
                }
                if(($srcLen & 3) === 1)
                {
                    throw new RangeException('Incorrect padding');
                }
                if($src[$srcLen - 1] === '=')
                {
                    throw new RangeException('Incorrect padding');
                }
            }
            else
            {
                $src = rtrim($src, '=');
                $srcLen = ParagonIE_Sodium_Core_Util::strlen($src);
            }

            $err = 0;
            $dest = '';
            // Main loop (no padding):
            for($i = 0; $i + 4 <= $srcLen; $i += 4)
            {
                $chunk = unpack('C*', ParagonIE_Sodium_Core_Util::substr($src, $i, 4));
                $c0 = self::decode6Bits($chunk[1]);
                $c1 = self::decode6Bits($chunk[2]);
                $c2 = self::decode6Bits($chunk[3]);
                $c3 = self::decode6Bits($chunk[4]);

                $dest .= pack('CCC', ((($c0 << 2) | ($c1 >> 4)) & 0xff), ((($c1 << 4) | ($c2 >> 2)) & 0xff), ((($c2 << 6) | $c3) & 0xff));
                $err |= ($c0 | $c1 | $c2 | $c3) >> 8;
            }
            // The last chunk, which may have padding:
            if($i < $srcLen)
            {
                $chunk = unpack('C*', ParagonIE_Sodium_Core_Util::substr($src, $i, $srcLen - $i));
                $c0 = self::decode6Bits($chunk[1]);

                if($i + 2 < $srcLen)
                {
                    $c1 = self::decode6Bits($chunk[2]);
                    $c2 = self::decode6Bits($chunk[3]);
                    $dest .= pack('CC', ((($c0 << 2) | ($c1 >> 4)) & 0xff), ((($c1 << 4) | ($c2 >> 2)) & 0xff));
                    $err |= ($c0 | $c1 | $c2) >> 8;
                }
                elseif($i + 1 < $srcLen)
                {
                    $c1 = self::decode6Bits($chunk[2]);
                    $dest .= pack('C', ((($c0 << 2) | ($c1 >> 4)) & 0xff));
                    $err |= ($c0 | $c1) >> 8;
                }
                elseif($i < $srcLen && $strictPadding)
                {
                    $err |= 1;
                }
            }

            $check = ($err === 0);
            if(! $check)
            {
                throw new RangeException('Base64::decode() only expects characters in the correct base64 alphabet');
            }

            return $dest;
        }

        abstract protected static function decode6Bits($src);
    }
