<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status511 extends Http
    {
        protected $code = 511;

        protected $reason = 'Network Authentication Required';
    }
