<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status405 extends Http
    {
        protected $code = 405;

        protected $reason = 'Method Not Allowed';
    }
