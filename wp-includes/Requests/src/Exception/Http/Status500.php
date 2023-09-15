<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status500 extends Http
    {
        protected $code = 500;

        protected $reason = 'Internal Server Error';
    }
