<?php

    if(class_exists('ParagonIE_Sodium_Core_Curve25519_Ge_Precomp', false))
    {
        return;
    }

    class Precomp
    {
        public $yplusx;

        public $yminusx;

        public $xy2d;

        public function __construct(
            ParagonIE_Sodium_Core_Curve25519_Fe $yplusx = null, ParagonIE_Sodium_Core_Curve25519_Fe $yminusx = null, ParagonIE_Sodium_Core_Curve25519_Fe $xy2d = null
        ) {
            if($yplusx === null)
            {
                $yplusx = new ParagonIE_Sodium_Core_Curve25519_Fe();
            }
            $this->yplusx = $yplusx;
            if($yminusx === null)
            {
                $yminusx = new ParagonIE_Sodium_Core_Curve25519_Fe();
            }
            $this->yminusx = $yminusx;
            if($xy2d === null)
            {
                $xy2d = new ParagonIE_Sodium_Core_Curve25519_Fe();
            }
            $this->xy2d = $xy2d;
        }
    }
