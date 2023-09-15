<?php

    namespace WpOrg\Requests;

    interface Capability
    {
        public const SSL = 'ssl';

        public const ALL = [
            self::SSL,
        ];
    }
