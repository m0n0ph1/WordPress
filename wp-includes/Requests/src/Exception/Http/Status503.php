<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status503 extends Http
    {
        protected $code = 503;

        protected $reason = 'Service Unavailable';
    }
