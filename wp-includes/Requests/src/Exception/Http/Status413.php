<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status413 extends Http
    {
        protected $code = 413;

        protected $reason = 'Request Entity Too Large';
    }
