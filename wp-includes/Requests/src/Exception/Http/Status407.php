<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status407 extends Http
    {
        protected $code = 407;

        protected $reason = 'Proxy Authentication Required';
    }
