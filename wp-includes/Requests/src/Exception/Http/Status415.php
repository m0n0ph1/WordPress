<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status415 extends Http
    {
        protected $code = 415;

        protected $reason = 'Unsupported Media Type';
    }
