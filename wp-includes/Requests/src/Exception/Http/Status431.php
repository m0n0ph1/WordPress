<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status431 extends Http
    {
        protected $code = 431;

        protected $reason = 'Request Header Fields Too Large';
    }
