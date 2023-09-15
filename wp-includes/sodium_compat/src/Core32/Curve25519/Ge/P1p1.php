<?php

    if(class_exists('ParagonIE_Sodium_Core32_Curve25519_Ge_P1p1', false))
    {
        return;
    }

    class P1p1
    {
        public $X;

        public $Y;

        public $Z;

        public $T;

        public function __construct(
            ParagonIE_Sodium_Core32_Curve25519_Fe $x = null, ParagonIE_Sodium_Core32_Curve25519_Fe $y = null, ParagonIE_Sodium_Core32_Curve25519_Fe $z = null, ParagonIE_Sodium_Core32_Curve25519_Fe $t = null
        ) {
            if($x === null)
            {
                $x = ParagonIE_Sodium_Core32_Curve25519::fe_0();
            }
            $this->X = $x;
            if($y === null)
            {
                $y = ParagonIE_Sodium_Core32_Curve25519::fe_0();
            }
            $this->Y = $y;
            if($z === null)
            {
                $z = ParagonIE_Sodium_Core32_Curve25519::fe_0();
            }
            $this->Z = $z;
            if($t === null)
            {
                $t = ParagonIE_Sodium_Core32_Curve25519::fe_0();
            }
            $this->T = $t;
        }
    }
