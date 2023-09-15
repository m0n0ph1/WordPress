<?php

    class ParagonIE_Sodium_Core32_Int64
    {
        public $limbs = [0, 0, 0, 0];

        public $overflow = 0;

        public $unsignedInt = false;

        public function __construct($array = [0, 0, 0, 0], $unsignedInt = false)
        {
            $this->limbs = [
                (int) $array[0],
                (int) $array[1],
                (int) $array[2],
                (int) $array[3]
            ];
            $this->overflow = 0;
            $this->unsignedInt = $unsignedInt;
        }

        public static function fromInts($low, $high)
        {
            ParagonIE_Sodium_Core32_Util::declareScalarType($low, 'int', 1);
            ParagonIE_Sodium_Core32_Util::declareScalarType($high, 'int', 2);

            $high = (int) $high;
            $low = (int) $low;

            return new ParagonIE_Sodium_Core32_Int64([
                                                         (int) (($high >> 16) & 0xffff),
                                                         (int) ($high & 0xffff),
                                                         (int) (($low >> 16) & 0xffff),
                                                         (int) ($low & 0xffff)
                                                     ]);
        }

        public static function fromInt($low)
        {
            ParagonIE_Sodium_Core32_Util::declareScalarType($low, 'int', 1);
            $low = (int) $low;

            return new ParagonIE_Sodium_Core32_Int64([
                                                         0,
                                                         0,
                                                         (int) (($low >> 16) & 0xffff),
                                                         (int) ($low & 0xffff)
                                                     ]);
        }

        public static function fromString($string)
        {
            ParagonIE_Sodium_Core32_Util::declareScalarType($string, 'string', 1);
            $string = (string) $string;
            if(ParagonIE_Sodium_Core32_Util::strlen($string) !== 8)
            {
                throw new RangeException('String must be 8 bytes; '.ParagonIE_Sodium_Core32_Util::strlen($string).' given.');
            }
            $return = new ParagonIE_Sodium_Core32_Int64();

            $return->limbs[0] = (int) ((ParagonIE_Sodium_Core32_Util::chrToInt($string[0]) & 0xff) << 8);
            $return->limbs[0] |= (ParagonIE_Sodium_Core32_Util::chrToInt($string[1]) & 0xff);
            $return->limbs[1] = (int) ((ParagonIE_Sodium_Core32_Util::chrToInt($string[2]) & 0xff) << 8);
            $return->limbs[1] |= (ParagonIE_Sodium_Core32_Util::chrToInt($string[3]) & 0xff);
            $return->limbs[2] = (int) ((ParagonIE_Sodium_Core32_Util::chrToInt($string[4]) & 0xff) << 8);
            $return->limbs[2] |= (ParagonIE_Sodium_Core32_Util::chrToInt($string[5]) & 0xff);
            $return->limbs[3] = (int) ((ParagonIE_Sodium_Core32_Util::chrToInt($string[6]) & 0xff) << 8);
            $return->limbs[3] |= (ParagonIE_Sodium_Core32_Util::chrToInt($string[7]) & 0xff);

            return $return;
        }

        public static function fromReverseString($string)
        {
            ParagonIE_Sodium_Core32_Util::declareScalarType($string, 'string', 1);
            $string = (string) $string;
            if(ParagonIE_Sodium_Core32_Util::strlen($string) !== 8)
            {
                throw new RangeException('String must be 8 bytes; '.ParagonIE_Sodium_Core32_Util::strlen($string).' given.');
            }
            $return = new ParagonIE_Sodium_Core32_Int64();

            $return->limbs[0] = (int) ((ParagonIE_Sodium_Core32_Util::chrToInt($string[7]) & 0xff) << 8);
            $return->limbs[0] |= (ParagonIE_Sodium_Core32_Util::chrToInt($string[6]) & 0xff);
            $return->limbs[1] = (int) ((ParagonIE_Sodium_Core32_Util::chrToInt($string[5]) & 0xff) << 8);
            $return->limbs[1] |= (ParagonIE_Sodium_Core32_Util::chrToInt($string[4]) & 0xff);
            $return->limbs[2] = (int) ((ParagonIE_Sodium_Core32_Util::chrToInt($string[3]) & 0xff) << 8);
            $return->limbs[2] |= (ParagonIE_Sodium_Core32_Util::chrToInt($string[2]) & 0xff);
            $return->limbs[3] = (int) ((ParagonIE_Sodium_Core32_Util::chrToInt($string[1]) & 0xff) << 8);
            $return->limbs[3] |= (ParagonIE_Sodium_Core32_Util::chrToInt($string[0]) & 0xff);

            return $return;
        }

        public function addInt64(ParagonIE_Sodium_Core32_Int64 $addend)
        {
            $i0 = $this->limbs[0];
            $i1 = $this->limbs[1];
            $i2 = $this->limbs[2];
            $i3 = $this->limbs[3];
            $j0 = $addend->limbs[0];
            $j1 = $addend->limbs[1];
            $j2 = $addend->limbs[2];
            $j3 = $addend->limbs[3];

            $r3 = $i3 + ($j3 & 0xffff);
            $carry = $r3 >> 16;

            $r2 = $i2 + ($j2 & 0xffff) + $carry;
            $carry = $r2 >> 16;

            $r1 = $i1 + ($j1 & 0xffff) + $carry;
            $carry = $r1 >> 16;

            $r0 = $i0 + ($j0 & 0xffff) + $carry;
            $carry = $r0 >> 16;

            $r0 &= 0xffff;
            $r1 &= 0xffff;
            $r2 &= 0xffff;
            $r3 &= 0xffff;

            $return = new ParagonIE_Sodium_Core32_Int64([$r0, $r1, $r2, $r3]);
            $return->overflow = $carry;
            $return->unsignedInt = $this->unsignedInt;

            return $return;
        }

        public function addInt($int)
        {
            ParagonIE_Sodium_Core32_Util::declareScalarType($int, 'int', 1);

            $int = (int) $int;

            $i0 = $this->limbs[0];
            $i1 = $this->limbs[1];
            $i2 = $this->limbs[2];
            $i3 = $this->limbs[3];

            $r3 = $i3 + ($int & 0xffff);
            $carry = $r3 >> 16;

            $r2 = $i2 + (($int >> 16) & 0xffff) + $carry;
            $carry = $r2 >> 16;

            $r1 = $i1 + $carry;
            $carry = $r1 >> 16;

            $r0 = $i0 + $carry;
            $carry = $r0 >> 16;

            $r0 &= 0xffff;
            $r1 &= 0xffff;
            $r2 &= 0xffff;
            $r3 &= 0xffff;
            $return = new ParagonIE_Sodium_Core32_Int64([$r0, $r1, $r2, $r3]);
            $return->overflow = $carry;
            $return->unsignedInt = $this->unsignedInt;

            return $return;
        }

        public function isGreaterThan($b = 0)
        {
            return $this->compareInt($b) > 0;
        }

        public function compareInt($b = 0)
        {
            $gt = 0;
            $eq = 1;

            $i = 4;
            $j = 0;
            while($i > 0)
            {
                --$i;

                $x1 = $this->limbs[$i];

                $x2 = ($b >> ($j << 4)) & 0xffff;

                $gt |= (($x2 - $x1) >> 8) & $eq;

                $eq &= (($x2 ^ $x1) - 1) >> 8;
            }

            return ($gt + $gt - $eq) + 1;
        }

        public function isLessThanInt($b = 0)
        {
            return $this->compareInt($b) < 0;
        }

        public function mulInt64(ParagonIE_Sodium_Core32_Int64 $int, $size = 0)
        {
            if(ParagonIE_Sodium_Compat::$fastMult)
            {
                return $this->mulInt64Fast($int);
            }
            ParagonIE_Sodium_Core32_Util::declareScalarType($size, 'int', 2);
            if(! $size)
            {
                $size = 63;
            }
            [$a, $b] = self::ctSelect($this, $int);

            $return = new ParagonIE_Sodium_Core32_Int64();
            $return->unsignedInt = $this->unsignedInt;

            // Initialize:
            $ret0 = 0;
            $ret1 = 0;
            $ret2 = 0;
            $ret3 = 0;
            $a0 = $a->limbs[0];
            $a1 = $a->limbs[1];
            $a2 = $a->limbs[2];
            $a3 = $a->limbs[3];
            $b0 = $b->limbs[0];
            $b1 = $b->limbs[1];
            $b2 = $b->limbs[2];
            $b3 = $b->limbs[3];

            for($i = (int) $size; $i >= 0; --$i)
            {
                $mask = -($b3 & 1);
                $x0 = $a0 & $mask;
                $x1 = $a1 & $mask;
                $x2 = $a2 & $mask;
                $x3 = $a3 & $mask;

                $ret3 += $x3;
                $c = $ret3 >> 16;

                $ret2 += $x2 + $c;
                $c = $ret2 >> 16;

                $ret1 += $x1 + $c;
                $c = $ret1 >> 16;

                $ret0 += $x0 + $c;

                $ret0 &= 0xffff;
                $ret1 &= 0xffff;
                $ret2 &= 0xffff;
                $ret3 &= 0xffff;

                $a3 = $a3 << 1;
                $x3 = $a3 >> 16;
                $a2 = ($a2 << 1) | $x3;
                $x2 = $a2 >> 16;
                $a1 = ($a1 << 1) | $x2;
                $x1 = $a1 >> 16;
                $a0 = ($a0 << 1) | $x1;
                $a0 &= 0xffff;
                $a1 &= 0xffff;
                $a2 &= 0xffff;
                $a3 &= 0xffff;

                $x0 = ($b0 & 1) << 16;
                $x1 = ($b1 & 1) << 16;
                $x2 = ($b2 & 1) << 16;

                $b0 = ($b0 >> 1);
                $b1 = (($b1 | $x0) >> 1);
                $b2 = (($b2 | $x1) >> 1);
                $b3 = (($b3 | $x2) >> 1);

                $b0 &= 0xffff;
                $b1 &= 0xffff;
                $b2 &= 0xffff;
                $b3 &= 0xffff;
            }
            $return->limbs[0] = $ret0;
            $return->limbs[1] = $ret1;
            $return->limbs[2] = $ret2;
            $return->limbs[3] = $ret3;

            return $return;
        }

        public function mulInt64Fast(ParagonIE_Sodium_Core32_Int64 $right)
        {
            $aNeg = ($this->limbs[0] >> 15) & 1;
            $bNeg = ($right->limbs[0] >> 15) & 1;

            $a = array_reverse($this->limbs);
            $b = array_reverse($right->limbs);
            if($aNeg)
            {
                for($i = 0; $i < 4; ++$i)
                {
                    $a[$i] = ($a[$i] ^ 0xffff) & 0xffff;
                }
                ++$a[0];
            }
            if($bNeg)
            {
                for($i = 0; $i < 4; ++$i)
                {
                    $b[$i] = ($b[$i] ^ 0xffff) & 0xffff;
                }
                ++$b[0];
            }
            $res = $this->multiplyLong($a, $b);
            if($aNeg !== $bNeg)
            {
                if($aNeg !== $bNeg)
                {
                    for($i = 0; $i < 4; ++$i)
                    {
                        $res[$i] = ($res[$i] ^ 0xffff) & 0xffff;
                    }
                    $c = 1;
                    for($i = 0; $i < 4; ++$i)
                    {
                        $res[$i] += $c;
                        $c = $res[$i] >> 16;
                        $res[$i] &= 0xffff;
                    }
                }
            }
            $return = new ParagonIE_Sodium_Core32_Int64();
            $return->limbs = [
                $res[3] & 0xffff,
                $res[2] & 0xffff,
                $res[1] & 0xffff,
                $res[0] & 0xffff
            ];
            if(count($res) > 4)
            {
                $return->overflow = $res[4];
            }

            return $return;
        }

        public function multiplyLong(array $a, array $b, $baseLog2 = 16)
        {
            $a_l = count($a);
            $b_l = count($b);

            $r = array_fill(0, $a_l + $b_l + 1, 0);
            $base = 1 << $baseLog2;
            for($i = 0; $i < $a_l; ++$i)
            {
                $a_i = $a[$i];
                for($j = 0; $j < $a_l; ++$j)
                {
                    $b_j = $b[$j];
                    $product = (($a_i * $b_j) + $r[$i + $j]);
                    $carry = (((int) $product >> $baseLog2) & 0xffff);
                    $r[$i + $j] = ((int) $product - (int) ($carry * $base)) & 0xffff;
                    $r[$i + $j + 1] += $carry;
                }
            }

            return array_slice($r, 0, 5);
        }

        public static function ctSelect(
            ParagonIE_Sodium_Core32_Int64 $A, ParagonIE_Sodium_Core32_Int64 $B
        ) {
            $a = clone $A;
            $b = clone $B;

            $aNeg = ($a->limbs[0] >> 15) & 1;

            $bNeg = ($b->limbs[0] >> 15) & 1;

            $m = (-($aNeg & $bNeg)) | 1;

            $swap = $bNeg & ~$aNeg;

            $d = -$swap;

            /*
            if ($bNeg && !$aNeg) {
                $a = clone $int;
                $b = clone $this;
            } elseif($bNeg && $aNeg) {
                $a = $this->mulInt(-1);
                $b = $int->mulInt(-1);
            }
             */
            $x = $a->xorInt64($b)->mask64($d, $d);

            return [
                $a->xorInt64($x)->mulInt($m),
                $b->xorInt64($x)->mulInt($m)
            ];
        }

        public function mask64($hi = 0, $lo = 0)
        {
            $a = ($hi >> 16) & 0xffff;

            $b = ($hi) & 0xffff;

            $c = ($lo >> 16) & 0xffff;

            $d = ($lo & 0xffff);

            return new ParagonIE_Sodium_Core32_Int64([
                                                         $this->limbs[0] & $a,
                                                         $this->limbs[1] & $b,
                                                         $this->limbs[2] & $c,
                                                         $this->limbs[3] & $d
                                                     ], $this->unsignedInt);
        }

        public function xorInt64(ParagonIE_Sodium_Core32_Int64 $b)
        {
            $return = new ParagonIE_Sodium_Core32_Int64();
            $return->unsignedInt = $this->unsignedInt;
            $return->limbs = [
                (int) ($this->limbs[0] ^ $b->limbs[0]),
                (int) ($this->limbs[1] ^ $b->limbs[1]),
                (int) ($this->limbs[2] ^ $b->limbs[2]),
                (int) ($this->limbs[3] ^ $b->limbs[3])
            ];

            return $return;
        }

        public function mulInt($int = 0, $size = 0)
        {
            if(ParagonIE_Sodium_Compat::$fastMult)
            {
                return $this->mulIntFast($int);
            }
            ParagonIE_Sodium_Core32_Util::declareScalarType($int, 'int', 1);
            ParagonIE_Sodium_Core32_Util::declareScalarType($size, 'int', 2);

            $int = (int) $int;

            $size = (int) $size;

            if(! $size)
            {
                $size = 63;
            }

            $a = clone $this;
            $return = new ParagonIE_Sodium_Core32_Int64();
            $return->unsignedInt = $this->unsignedInt;

            // Initialize:
            $ret0 = 0;
            $ret1 = 0;
            $ret2 = 0;
            $ret3 = 0;
            $a0 = $a->limbs[0];
            $a1 = $a->limbs[1];
            $a2 = $a->limbs[2];
            $a3 = $a->limbs[3];

            for($i = $size; $i >= 0; --$i)
            {
                $mask = -($int & 1);
                $x0 = $a0 & $mask;
                $x1 = $a1 & $mask;
                $x2 = $a2 & $mask;
                $x3 = $a3 & $mask;

                $ret3 += $x3;
                $c = $ret3 >> 16;

                $ret2 += $x2 + $c;
                $c = $ret2 >> 16;

                $ret1 += $x1 + $c;
                $c = $ret1 >> 16;

                $ret0 += $x0 + $c;

                $ret0 &= 0xffff;
                $ret1 &= 0xffff;
                $ret2 &= 0xffff;
                $ret3 &= 0xffff;

                $a3 = $a3 << 1;
                $x3 = $a3 >> 16;
                $a2 = ($a2 << 1) | $x3;
                $x2 = $a2 >> 16;
                $a1 = ($a1 << 1) | $x2;
                $x1 = $a1 >> 16;
                $a0 = ($a0 << 1) | $x1;
                $a0 &= 0xffff;
                $a1 &= 0xffff;
                $a2 &= 0xffff;
                $a3 &= 0xffff;

                $int >>= 1;
            }
            $return->limbs[0] = $ret0;
            $return->limbs[1] = $ret1;
            $return->limbs[2] = $ret2;
            $return->limbs[3] = $ret3;

            return $return;
        }

        public function mulIntFast($int)
        {
            // Handle negative numbers
            $aNeg = ($this->limbs[0] >> 15) & 1;
            $bNeg = ($int >> 31) & 1;
            $a = array_reverse($this->limbs);
            $b = [
                $int & 0xffff,
                ($int >> 16) & 0xffff,
                -$bNeg & 0xffff,
                -$bNeg & 0xffff
            ];
            if($aNeg)
            {
                for($i = 0; $i < 4; ++$i)
                {
                    $a[$i] = ($a[$i] ^ 0xffff) & 0xffff;
                }
                ++$a[0];
            }
            if($bNeg)
            {
                for($i = 0; $i < 4; ++$i)
                {
                    $b[$i] = ($b[$i] ^ 0xffff) & 0xffff;
                }
                ++$b[0];
            }
            // Multiply
            $res = $this->multiplyLong($a, $b);

            // Re-apply negation to results
            if($aNeg !== $bNeg)
            {
                for($i = 0; $i < 4; ++$i)
                {
                    $res[$i] = (0xffff ^ $res[$i]) & 0xffff;
                }
                // Handle integer overflow
                $c = 1;
                for($i = 0; $i < 4; ++$i)
                {
                    $res[$i] += $c;
                    $c = $res[$i] >> 16;
                    $res[$i] &= 0xffff;
                }
            }

            // Return our values
            $return = new ParagonIE_Sodium_Core32_Int64();
            $return->limbs = [
                $res[3] & 0xffff,
                $res[2] & 0xffff,
                $res[1] & 0xffff,
                $res[0] & 0xffff
            ];
            if(count($res) > 4)
            {
                $return->overflow = $res[4] & 0xffff;
            }
            $return->unsignedInt = $this->unsignedInt;

            return $return;
        }

        public function orInt64(ParagonIE_Sodium_Core32_Int64 $b)
        {
            $return = new ParagonIE_Sodium_Core32_Int64();
            $return->unsignedInt = $this->unsignedInt;
            $return->limbs = [
                (int) ($this->limbs[0] | $b->limbs[0]),
                (int) ($this->limbs[1] | $b->limbs[1]),
                (int) ($this->limbs[2] | $b->limbs[2]),
                (int) ($this->limbs[3] | $b->limbs[3])
            ];

            return $return;
        }

        public function rotateLeft($c = 0)
        {
            ParagonIE_Sodium_Core32_Util::declareScalarType($c, 'int', 1);

            $c = (int) $c;

            $return = new ParagonIE_Sodium_Core32_Int64();
            $return->unsignedInt = $this->unsignedInt;
            $c &= 63;
            if($c === 0)
            {
                // NOP, but we want a copy.
                $return->limbs = $this->limbs;
            }
            else
            {
                $limbs =& $return->limbs;

                $myLimbs =& $this->limbs;

                $idx_shift = ($c >> 4) & 3;

                $sub_shift = $c & 15;

                for($i = 3; $i >= 0; --$i)
                {
                    $j = ($i + $idx_shift) & 3;

                    $k = ($i + $idx_shift + 1) & 3;
                    $limbs[$i] = (int) ((((int) ($myLimbs[$j]) << $sub_shift) | ((int) ($myLimbs[$k]) >> (16 - $sub_shift))) & 0xffff);
                }
            }

            return $return;
        }

        public function rotateRight($c = 0)
        {
            ParagonIE_Sodium_Core32_Util::declareScalarType($c, 'int', 1);

            $c = (int) $c;

            $return = new ParagonIE_Sodium_Core32_Int64();
            $return->unsignedInt = $this->unsignedInt;
            $c &= 63;

            if($c === 0)
            {
                // NOP, but we want a copy.
                $return->limbs = $this->limbs;
            }
            else
            {
                $limbs =& $return->limbs;

                $myLimbs =& $this->limbs;

                $idx_shift = ($c >> 4) & 3;

                $sub_shift = $c & 15;

                for($i = 3; $i >= 0; --$i)
                {
                    $j = ($i - $idx_shift) & 3;

                    $k = ($i - $idx_shift - 1) & 3;
                    $limbs[$i] = (int) ((((int) ($myLimbs[$j]) >> (int) ($sub_shift)) | ((int) ($myLimbs[$k]) << (16 - (int) ($sub_shift)))) & 0xffff);
                }
            }

            return $return;
        }

        public function shiftLeft($c = 0)
        {
            ParagonIE_Sodium_Core32_Util::declareScalarType($c, 'int', 1);

            $c = (int) $c;

            $return = new ParagonIE_Sodium_Core32_Int64();
            $return->unsignedInt = $this->unsignedInt;
            $c &= 63;

            if($c >= 16)
            {
                if($c >= 48)
                {
                    $return->limbs = [
                        $this->limbs[3],
                        0,
                        0,
                        0
                    ];
                }
                elseif($c >= 32)
                {
                    $return->limbs = [
                        $this->limbs[2],
                        $this->limbs[3],
                        0,
                        0
                    ];
                }
                else
                {
                    $return->limbs = [
                        $this->limbs[1],
                        $this->limbs[2],
                        $this->limbs[3],
                        0
                    ];
                }

                return $return->shiftLeft($c & 15);
            }
            if($c === 0)
            {
                $return->limbs = $this->limbs;
            }
            elseif($c < 0)
            {
                return $this->shiftRight(-$c);
            }
            else
            {
                if(! is_int($c))
                {
                    throw new TypeError();
                }

                $carry = 0;
                for($i = 3; $i >= 0; --$i)
                {
                    $tmp = ($this->limbs[$i] << $c) | ($carry & 0xffff);
                    $return->limbs[$i] = (int) ($tmp & 0xffff);

                    $carry = $tmp >> 16;
                }
            }

            return $return;
        }

        public function shiftRight($c = 0)
        {
            ParagonIE_Sodium_Core32_Util::declareScalarType($c, 'int', 1);
            $c = (int) $c;

            $return = new ParagonIE_Sodium_Core32_Int64();
            $return->unsignedInt = $this->unsignedInt;
            $c &= 63;

            $negative = -(($this->limbs[0] >> 15) & 1);
            if($c >= 16)
            {
                if($c >= 48)
                {
                    $return->limbs = [
                        (int) ($negative & 0xffff),
                        (int) ($negative & 0xffff),
                        (int) ($negative & 0xffff),
                        (int) $this->limbs[0]
                    ];
                }
                elseif($c >= 32)
                {
                    $return->limbs = [
                        (int) ($negative & 0xffff),
                        (int) ($negative & 0xffff),
                        (int) $this->limbs[0],
                        (int) $this->limbs[1]
                    ];
                }
                else
                {
                    $return->limbs = [
                        (int) ($negative & 0xffff),
                        (int) $this->limbs[0],
                        (int) $this->limbs[1],
                        (int) $this->limbs[2]
                    ];
                }

                return $return->shiftRight($c & 15);
            }

            if($c === 0)
            {
                $return->limbs = $this->limbs;
            }
            elseif($c < 0)
            {
                return $this->shiftLeft(-$c);
            }
            else
            {
                if(! is_int($c))
                {
                    throw new TypeError();
                }

                $carryRight = ($negative & 0xffff);
                $mask = (int) (((1 << ($c + 1)) - 1) & 0xffff);
                for($i = 0; $i < 4; ++$i)
                {
                    $return->limbs[$i] = (int) ((($this->limbs[$i] >> $c) | ($carryRight << (16 - $c))) & 0xffff);
                    $carryRight = (int) ($this->limbs[$i] & $mask);
                }
            }

            return $return;
        }

        public function subInt($int)
        {
            ParagonIE_Sodium_Core32_Util::declareScalarType($int, 'int', 1);
            $int = (int) $int;

            $return = new ParagonIE_Sodium_Core32_Int64();
            $return->unsignedInt = $this->unsignedInt;

            $carry = 0;
            for($i = 3; $i >= 0; --$i)
            {
                $tmp = $this->limbs[$i] - (($int >> 16) & 0xffff) + $carry;

                $carry = $tmp >> 16;
                $return->limbs[$i] = (int) ($tmp & 0xffff);
            }

            return $return;
        }

        public function subInt64(ParagonIE_Sodium_Core32_Int64 $b)
        {
            $return = new ParagonIE_Sodium_Core32_Int64();
            $return->unsignedInt = $this->unsignedInt;

            $carry = 0;
            for($i = 3; $i >= 0; --$i)
            {
                $tmp = $this->limbs[$i] - $b->limbs[$i] + $carry;

                $carry = ($tmp >> 16);
                $return->limbs[$i] = (int) ($tmp & 0xffff);
            }

            return $return;
        }

        public function toInt()
        {
            return (int) ((($this->limbs[2] & 0xffff) << 16) | ($this->limbs[3] & 0xffff));
        }

        public function toArray()
        {
            return [
                (int) ((($this->limbs[0] & 0xffff) << 16) | ($this->limbs[1] & 0xffff)),
                (int) ((($this->limbs[2] & 0xffff) << 16) | ($this->limbs[3] & 0xffff))
            ];
        }

        public function toInt32()
        {
            $return = new ParagonIE_Sodium_Core32_Int32();
            $return->limbs[0] = (int) ($this->limbs[2]);
            $return->limbs[1] = (int) ($this->limbs[3]);
            $return->unsignedInt = $this->unsignedInt;
            $return->overflow = (int) (ParagonIE_Sodium_Core32_Util::abs($this->limbs[1], 16) & 0xffff);

            return $return;
        }

        public function toInt64()
        {
            $return = new ParagonIE_Sodium_Core32_Int64();
            $return->limbs[0] = (int) ($this->limbs[0]);
            $return->limbs[1] = (int) ($this->limbs[1]);
            $return->limbs[2] = (int) ($this->limbs[2]);
            $return->limbs[3] = (int) ($this->limbs[3]);
            $return->unsignedInt = $this->unsignedInt;
            $return->overflow = ParagonIE_Sodium_Core32_Util::abs($this->overflow);

            return $return;
        }

        public function setUnsignedInt($bool = false)
        {
            $this->unsignedInt = ! empty($bool);

            return $this;
        }

        public function toReverseString()
        {
            return ParagonIE_Sodium_Core32_Util::intToChr($this->limbs[3] & 0xff).ParagonIE_Sodium_Core32_Util::intToChr(($this->limbs[3] >> 8) & 0xff).ParagonIE_Sodium_Core32_Util::intToChr($this->limbs[2] & 0xff).ParagonIE_Sodium_Core32_Util::intToChr(($this->limbs[2] >> 8) & 0xff).ParagonIE_Sodium_Core32_Util::intToChr($this->limbs[1] & 0xff).ParagonIE_Sodium_Core32_Util::intToChr(($this->limbs[1] >> 8) & 0xff).ParagonIE_Sodium_Core32_Util::intToChr($this->limbs[0] & 0xff).ParagonIE_Sodium_Core32_Util::intToChr(($this->limbs[0] >> 8) & 0xff);
        }

        public function __toString()
        {
            try
            {
                return $this->toString();
            }
            catch(TypeError $ex)
            {
                // PHP engine can't handle exceptions from __toString()
                return '';
            }
        }

        public function toString()
        {
            return ParagonIE_Sodium_Core32_Util::intToChr(($this->limbs[0] >> 8) & 0xff).ParagonIE_Sodium_Core32_Util::intToChr($this->limbs[0] & 0xff).ParagonIE_Sodium_Core32_Util::intToChr(($this->limbs[1] >> 8) & 0xff).ParagonIE_Sodium_Core32_Util::intToChr($this->limbs[1] & 0xff).ParagonIE_Sodium_Core32_Util::intToChr(($this->limbs[2] >> 8) & 0xff).ParagonIE_Sodium_Core32_Util::intToChr($this->limbs[2] & 0xff).ParagonIE_Sodium_Core32_Util::intToChr(($this->limbs[3] >> 8) & 0xff).ParagonIE_Sodium_Core32_Util::intToChr($this->limbs[3] & 0xff);
        }
    }
