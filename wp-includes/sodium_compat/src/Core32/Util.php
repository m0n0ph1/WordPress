<?php

    if(class_exists('ParagonIE_Sodium_Core32_Util', false))
    {
        return;
    }

    abstract class Util extends ParagonIE_Sodium_Core_Util
    {
    }
