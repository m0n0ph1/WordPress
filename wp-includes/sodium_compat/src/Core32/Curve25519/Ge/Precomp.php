<?php

    if(class_exists('ParagonIE_Sodium_Core32_Curve25519_Ge_Precomp', false))
    {
        return;
    }

    class ParagonIE_Sodium_Core32_Curve25519_Ge_Precomp
    {
        public $yplusx;

        public $yminusx;

        public $xy2d;

        public function __construct(
            ParagonIE_Sodium_Core32_Curve25519_Fe $yplusx = null, ParagonIE_Sodium_Core32_Curve25519_Fe $yminusx = null, ParagonIE_Sodium_Core32_Curve25519_Fe $xy2d = null
        ) {
            if($yplusx === null)
            {
                $yplusx = ParagonIE_Sodium_Core32_Curve25519::fe_0();
            }
            $this->yplusx = $yplusx;
            if($yminusx === null)
            {
                $yminusx = ParagonIE_Sodium_Core32_Curve25519::fe_0();
            }
            $this->yminusx = $yminusx;
            if($xy2d === null)
            {
                $xy2d = ParagonIE_Sodium_Core32_Curve25519::fe_0();
            }
            $this->xy2d = $xy2d;
        }
    }
