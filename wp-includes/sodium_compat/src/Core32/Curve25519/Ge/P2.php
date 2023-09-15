<?php

    if(class_exists('ParagonIE_Sodium_Core32_Curve25519_Ge_P2', false))
    {
        return;
    }

    class ParagonIE_Sodium_Core32_Curve25519_Ge_P2
    {
        public $X;

        public $Y;

        public $Z;

        public function __construct(
            ParagonIE_Sodium_Core32_Curve25519_Fe $x = null, ParagonIE_Sodium_Core32_Curve25519_Fe $y = null, ParagonIE_Sodium_Core32_Curve25519_Fe $z = null
        ) {
            if($x === null)
            {
                $x = new ParagonIE_Sodium_Core32_Curve25519_Fe();
            }
            $this->X = $x;
            if($y === null)
            {
                $y = new ParagonIE_Sodium_Core32_Curve25519_Fe();
            }
            $this->Y = $y;
            if($z === null)
            {
                $z = new ParagonIE_Sodium_Core32_Curve25519_Fe();
            }
            $this->Z = $z;
        }
    }
