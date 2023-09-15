<?php

    if(class_exists('ParagonIE_Sodium_Core_Util', false))
    {
        return;
    }

    abstract class Util
    {
        public static function abs($integer, $size = 0)
        {
            $realSize = (PHP_INT_SIZE << 3) - 1;
            if($size)
            {
                --$size;
            }
            else
            {
                $size = $realSize;
            }

            $negative = -(($integer >> $size) & 1);

            return (int) (($integer ^ $negative) + (($negative >> $realSize) & 1));
        }

        public static function bin2hex($binaryString)
        {
            /* Type checks: */
            if(! is_string($binaryString))
            {
                throw new TypeError('Argument 1 must be a string, '.gettype($binaryString).' given.');
            }

            $hex = '';
            $len = self::strlen($binaryString);
            for($i = 0; $i < $len; ++$i)
            {
                $chunk = unpack('C', $binaryString[$i]);

                $c = $chunk[1] & 0xf;

                $b = $chunk[1] >> 4;
                $hex .= pack('CC', (87 + $b + ((($b - 10) >> 8) & ~38)), (87 + $c + ((($c - 10) >> 8) & ~38)));
            }

            return $hex;
        }

        public static function strlen($str)
        {
            /* Type checks: */
            if(! is_string($str))
            {
                throw new TypeError('String expected');
            }

            return (int) (self::isMbStringOverride() ? mb_strlen($str, '8bit') : strlen($str));
        }

        protected static function isMbStringOverride()
        {
            static $mbstring = null;

            if($mbstring === null)
            {
                if(! defined('MB_OVERLOAD_STRING'))
                {
                    $mbstring = false;

                    return $mbstring;
                }
                $mbstring = extension_loaded('mbstring') && defined('MB_OVERLOAD_STRING') && ((int) (ini_get('mbstring.func_overload')) & 2);
                // MB_OVERLOAD_STRING === 2
            }

            return $mbstring;
        }

        public static function bin2hexUpper($bin_string)
        {
            $hex = '';
            $len = self::strlen($bin_string);
            for($i = 0; $i < $len; ++$i)
            {
                $chunk = unpack('C', $bin_string[$i]);

                $c = $chunk[1] & 0xf;

                $b = $chunk[1] >> 4;

                $hex .= pack('CC', (55 + $b + ((($b - 10) >> 8) & ~6)), (55 + $c + ((($c - 10) >> 8) & ~6)));
            }

            return $hex;
        }

        public static function compare($left, $right, $len = null)
        {
            $leftLen = self::strlen($left);
            $rightLen = self::strlen($right);
            if($len === null)
            {
                $len = max($leftLen, $rightLen);
                $left = str_pad($left, $len, "\x00", STR_PAD_RIGHT);
                $right = str_pad($right, $len, "\x00", STR_PAD_RIGHT);
            }

            $gt = 0;
            $eq = 1;
            $i = $len;
            while($i !== 0)
            {
                --$i;
                $gt |= ((self::chrToInt($right[$i]) - self::chrToInt($left[$i])) >> 8) & $eq;
                $eq &= ((self::chrToInt($right[$i]) ^ self::chrToInt($left[$i])) - 1) >> 8;
            }

            return ($gt + $gt + $eq) - 1;
        }

        public static function chrToInt($chr)
        {
            /* Type checks: */
            if(! is_string($chr))
            {
                throw new TypeError('Argument 1 must be a string, '.gettype($chr).' given.');
            }
            if(self::strlen($chr) !== 1)
            {
                throw new SodiumException('chrToInt() expects a string that is exactly 1 character long');
            }

            $chunk = unpack('C', $chr);

            return (int) ($chunk[1]);
        }

        public static function declareScalarType(&$mixedVar = null, $type = 'void', $argumentIndex = 0)
        {
            if(func_num_args() === 0)
            {
                /* Tautology, by default */
                return;
            }
            if(func_num_args() === 1)
            {
                throw new TypeError('Declared void, but passed a variable');
            }
            $realType = strtolower(gettype($mixedVar));
            $type = strtolower($type);
            switch($type)
            {
                case 'null':
                    if($mixedVar !== null)
                    {
                        throw new TypeError('Argument '.$argumentIndex.' must be null, '.$realType.' given.');
                    }
                    break;
                case 'integer':
                case 'int':
                    $allow = ['int', 'integer'];
                    if(! in_array($type, $allow))
                    {
                        throw new TypeError('Argument '.$argumentIndex.' must be an integer, '.$realType.' given.');
                    }
                    $mixedVar = (int) $mixedVar;
                    break;
                case 'boolean':
                case 'bool':
                    $allow = ['bool', 'boolean'];
                    if(! in_array($type, $allow))
                    {
                        throw new TypeError('Argument '.$argumentIndex.' must be a boolean, '.$realType.' given.');
                    }
                    $mixedVar = (bool) $mixedVar;
                    break;
                case 'string':
                    if(! is_string($mixedVar))
                    {
                        throw new TypeError('Argument '.$argumentIndex.' must be a string, '.$realType.' given.');
                    }
                    $mixedVar = (string) $mixedVar;
                    break;
                case 'decimal':
                case 'double':
                case 'float':
                    $allow = ['decimal', 'double', 'float'];
                    if(! in_array($type, $allow))
                    {
                        throw new TypeError('Argument '.$argumentIndex.' must be a float, '.$realType.' given.');
                    }
                    $mixedVar = (float) $mixedVar;
                    break;
                case 'object':
                    if(! is_object($mixedVar))
                    {
                        throw new TypeError('Argument '.$argumentIndex.' must be an object, '.$realType.' given.');
                    }
                    break;
                case 'array':
                    if(! is_array($mixedVar))
                    {
                        if(is_object($mixedVar) && $mixedVar instanceof ArrayAccess)
                        {
                            return;
                        }
                        throw new TypeError('Argument '.$argumentIndex.' must be an array, '.$realType.' given.');
                    }
                    break;
                default:
                    throw new SodiumException('Unknown type ('.$realType.') does not match expect type ('.$type.')');
            }
        }

        public static function hex2bin($hexString, $ignore = '', $strictPadding = false)
        {
            /* Type checks: */
            if(! is_string($hexString))
            {
                throw new TypeError('Argument 1 must be a string, '.gettype($hexString).' given.');
            }
            if(! is_string($ignore))
            {
                throw new TypeError('Argument 2 must be a string, '.gettype($hexString).' given.');
            }

            $hex_pos = 0;
            $bin = '';
            $c_acc = 0;
            $hex_len = self::strlen($hexString);
            $state = 0;
            if(($hex_len & 1) !== 0)
            {
                if($strictPadding)
                {
                    throw new RangeException('Expected an even number of hexadecimal characters');
                }
                else
                {
                    $hexString = '0'.$hexString;
                    ++$hex_len;
                }
            }

            $chunk = unpack('C*', $hexString);
            while($hex_pos < $hex_len)
            {
                ++$hex_pos;

                $c = $chunk[$hex_pos];
                $c_num = $c ^ 48;
                $c_num0 = ($c_num - 10) >> 8;
                $c_alpha = ($c & ~32) - 55;
                $c_alpha0 = (($c_alpha - 10) ^ ($c_alpha - 16)) >> 8;
                if(($c_num0 | $c_alpha0) === 0)
                {
                    if($ignore && $state === 0 && strpos($ignore, self::intToChr($c)) !== false)
                    {
                        continue;
                    }
                    throw new RangeException('hex2bin() only expects hexadecimal characters');
                }
                $c_val = ($c_num0 & $c_num) | ($c_alpha & $c_alpha0);
                if($state === 0)
                {
                    $c_acc = $c_val * 16;
                }
                else
                {
                    $bin .= pack('C', $c_acc | $c_val);
                }
                $state ^= 1;
            }

            return $bin;
        }

        public static function intToChr($int)
        {
            return pack('C', $int);
        }

        public static function intArrayToString(array $ints)
        {
            $args = $ints;
            foreach($args as $i => $v)
            {
                $args[$i] = (int) ($v & 0xff);
            }
            array_unshift($args, str_repeat('C', count($ints)));

            return (string) (call_user_func_array('pack', $args));
        }

        public static function load_3($string)
        {
            /* Type checks: */
            if(! is_string($string))
            {
                throw new TypeError('Argument 1 must be a string, '.gettype($string).' given.');
            }

            /* Input validation: */
            if(self::strlen($string) < 3)
            {
                throw new RangeException('String must be 3 bytes or more; '.self::strlen($string).' given.');
            }

            $unpacked = unpack('V', $string."\0");

            return (int) ($unpacked[1] & 0xffffff);
        }

        public static function load_4($string)
        {
            /* Type checks: */
            if(! is_string($string))
            {
                throw new TypeError('Argument 1 must be a string, '.gettype($string).' given.');
            }

            /* Input validation: */
            if(self::strlen($string) < 4)
            {
                throw new RangeException('String must be 4 bytes or more; '.self::strlen($string).' given.');
            }

            $unpacked = unpack('V', $string);

            return (int) $unpacked[1];
        }

        public static function load64_le($string)
        {
            /* Type checks: */
            if(! is_string($string))
            {
                throw new TypeError('Argument 1 must be a string, '.gettype($string).' given.');
            }

            /* Input validation: */
            if(self::strlen($string) < 4)
            {
                throw new RangeException('String must be 4 bytes or more; '.self::strlen($string).' given.');
            }
            if(PHP_VERSION_ID >= 50603 && PHP_INT_SIZE === 8)
            {
                $unpacked = unpack('P', $string);

                return (int) $unpacked[1];
            }

            $result = (self::chrToInt($string[0]) & 0xff);
            $result |= (self::chrToInt($string[1]) & 0xff) << 8;
            $result |= (self::chrToInt($string[2]) & 0xff) << 16;
            $result |= (self::chrToInt($string[3]) & 0xff) << 24;
            $result |= (self::chrToInt($string[4]) & 0xff) << 32;
            $result |= (self::chrToInt($string[5]) & 0xff) << 40;
            $result |= (self::chrToInt($string[6]) & 0xff) << 48;
            $result |= (self::chrToInt($string[7]) & 0xff) << 56;

            return (int) $result;
        }

        public static function memcmp($left, $right)
        {
            if(self::hashEquals($left, $right))
            {
                return 0;
            }

            return -1;
        }

        public static function hashEquals($left, $right)
        {
            /* Type checks: */
            if(! is_string($left))
            {
                throw new TypeError('Argument 1 must be a string, '.gettype($left).' given.');
            }
            if(! is_string($right))
            {
                throw new TypeError('Argument 2 must be a string, '.gettype($right).' given.');
            }

            if(is_callable('hash_equals'))
            {
                return hash_equals($left, $right);
            }
            $d = 0;

            $len = self::strlen($left);
            if($len !== self::strlen($right))
            {
                return false;
            }
            for($i = 0; $i < $len; ++$i)
            {
                $d |= self::chrToInt($left[$i]) ^ self::chrToInt($right[$i]);
            }

            if($d !== 0)
            {
                return false;
            }

            return $left === $right;
        }

        public static function mul($a, $b, $size = 0)
        {
            if(ParagonIE_Sodium_Compat::$fastMult)
            {
                return (int) ($a * $b);
            }

            static $defaultSize = null;

            if(! $defaultSize)
            {
                $defaultSize = (PHP_INT_SIZE << 3) - 1;
            }
            if($size < 1)
            {
                $size = $defaultSize;
            }

            $c = 0;

            $mask = -(($b >> ((int) $defaultSize)) & 1);

            $b = ($b & ~$mask) | ($mask & -$b);

            for($i = $size; $i >= 0; --$i)
            {
                $c += (int) ($a & -($b & 1));
                $a <<= 1;
                $b >>= 1;
            }
            $c = (int) @($c & -1);

            return (int) (($c & ~$mask) | ($mask & -$c));
        }

        public static function store_3($int)
        {
            /* Type checks: */
            if(! is_int($int))
            {
                if(is_numeric($int))
                {
                    $int = (int) $int;
                }
                else
                {
                    throw new TypeError('Argument 1 must be an integer, '.gettype($int).' given.');
                }
            }

            $packed = pack('N', $int);

            return self::substr($packed, 1, 3);
        }

        public static function substr($str, $start = 0, $length = null)
        {
            /* Type checks: */
            if(! is_string($str))
            {
                throw new TypeError('String expected');
            }

            if($length === 0)
            {
                return '';
            }

            if(self::isMbStringOverride())
            {
                if(PHP_VERSION_ID < 50400 && $length === null)
                {
                    $length = self::strlen($str);
                }
                $sub = (string) mb_substr($str, $start, $length, '8bit');
            }
            elseif($length === null)
            {
                $sub = (string) substr($str, $start);
            }
            else
            {
                $sub = (string) substr($str, $start, $length);
            }
            if($sub !== '')
            {
                return $sub;
            }

            return '';
        }

        public static function store32_le($int)
        {
            /* Type checks: */
            if(! is_int($int))
            {
                if(is_numeric($int))
                {
                    $int = (int) $int;
                }
                else
                {
                    throw new TypeError('Argument 1 must be an integer, '.gettype($int).' given.');
                }
            }

            $packed = pack('V', $int);

            return $packed;
        }

        public static function store_4($int)
        {
            /* Type checks: */
            if(! is_int($int))
            {
                if(is_numeric($int))
                {
                    $int = (int) $int;
                }
                else
                {
                    throw new TypeError('Argument 1 must be an integer, '.gettype($int).' given.');
                }
            }

            $packed = pack('N', $int);

            return $packed;
        }

        public static function store64_le($int)
        {
            /* Type checks: */
            if(! is_int($int))
            {
                if(is_numeric($int))
                {
                    $int = (int) $int;
                }
                else
                {
                    throw new TypeError('Argument 1 must be an integer, '.gettype($int).' given.');
                }
            }

            if(PHP_INT_SIZE === 8)
            {
                if(PHP_VERSION_ID >= 50603)
                {
                    $packed = pack('P', $int);

                    return $packed;
                }

                return self::intToChr($int & 0xff).self::intToChr(($int >> 8) & 0xff).self::intToChr(($int >> 16) & 0xff).self::intToChr(($int >> 24) & 0xff).self::intToChr(($int >> 32) & 0xff).self::intToChr(($int >> 40) & 0xff).self::intToChr(($int >> 48) & 0xff).self::intToChr(($int >> 56) & 0xff);
            }
            if($int > PHP_INT_MAX)
            {
                [$hiB, $int] = self::numericTo64BitInteger($int);
            }
            else
            {
                $hiB = 0;
            }

            return self::intToChr(($int) & 0xff).self::intToChr(($int >> 8) & 0xff).self::intToChr(($int >> 16) & 0xff).self::intToChr(($int >> 24) & 0xff).self::intToChr($hiB & 0xff).self::intToChr(($hiB >> 8) & 0xff).self::intToChr(($hiB >> 16) & 0xff).self::intToChr(($hiB >> 24) & 0xff);
        }

        public static function numericTo64BitInteger($num)
        {
            $high = 0;

            if(PHP_INT_SIZE === 4)
            {
                $low = (int) $num;
            }
            else
            {
                $low = $num & 0xffffffff;
            }

            if((+(abs($num))) >= 1)
            {
                if($num > 0)
                {
                    $high = min((+(floor($num / 4294967296))), 4294967295);
                }
                else
                {
                    $high = ~~((+(ceil(($num - (+((~~($num))))) / 4294967296))));
                }
            }

            return [(int) $high, (int) $low];
        }

        public static function stringToIntArray($string)
        {
            if(! is_string($string))
            {
                throw new TypeError('String expected');
            }

            $values = array_values(unpack('C*', $string));

            return $values;
        }

        public static function verify_16($a, $b)
        {
            /* Type checks: */
            if(! is_string($a) || ! is_string($b))
            {
                throw new TypeError('String expected');
            }

            return self::hashEquals(self::substr($a, 0, 16), self::substr($b, 0, 16));
        }

        public static function verify_32($a, $b)
        {
            /* Type checks: */
            if(! is_string($a) || ! is_string($b))
            {
                throw new TypeError('String expected');
            }

            return self::hashEquals(self::substr($a, 0, 32), self::substr($b, 0, 32));
        }

        public static function xorStrings($a, $b)
        {
            /* Type checks: */
            if(! is_string($a))
            {
                throw new TypeError('Argument 1 must be a string');
            }
            if(! is_string($b))
            {
                throw new TypeError('Argument 2 must be a string');
            }

            return (string) ($a ^ $b);
        }

        protected static function hash_update(&$hs, $data)
        {
            if(! hash_update($hs, $data))
            {
                throw new SodiumException('hash_update() failed');
            }
        }
    }
