<?php

    namespace WpOrg\Requests;

    interface Capability
    {
        const SSL = 'ssl';

        const ALL = [
            self::SSL,
        ];
    }
