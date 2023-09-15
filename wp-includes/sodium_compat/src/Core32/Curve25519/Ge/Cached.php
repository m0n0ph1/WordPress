<?php

    if(class_exists('ParagonIE_Sodium_Core32_Curve25519_Ge_Cached', false))
    {
        return;
    }

    class Cached
    {
        public $YplusX;

        public $YminusX;

        public $Z;

        public $T2d;

        public function __construct(
            ParagonIE_Sodium_Core32_Curve25519_Fe $YplusX = null, ParagonIE_Sodium_Core32_Curve25519_Fe $YminusX = null, ParagonIE_Sodium_Core32_Curve25519_Fe $Z = null, ParagonIE_Sodium_Core32_Curve25519_Fe $T2d = null
        ) {
            if($YplusX === null)
            {
                $YplusX = new ParagonIE_Sodium_Core32_Curve25519_Fe();
            }
            $this->YplusX = $YplusX;
            if($YminusX === null)
            {
                $YminusX = new ParagonIE_Sodium_Core32_Curve25519_Fe();
            }
            $this->YminusX = $YminusX;
            if($Z === null)
            {
                $Z = new ParagonIE_Sodium_Core32_Curve25519_Fe();
            }
            $this->Z = $Z;
            if($T2d === null)
            {
                $T2d = new ParagonIE_Sodium_Core32_Curve25519_Fe();
            }
            $this->T2d = $T2d;
        }
    }
