<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status429 extends Http
    {
        protected $code = 429;

        protected $reason = 'Too Many Requests';
    }
