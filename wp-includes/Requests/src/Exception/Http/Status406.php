<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status406 extends Http
    {
        protected $code = 406;

        protected $reason = 'Not Acceptable';
    }
