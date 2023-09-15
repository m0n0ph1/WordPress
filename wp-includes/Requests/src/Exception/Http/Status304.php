<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status304 extends Http
    {
        protected $code = 304;

        protected $reason = 'Not Modified';
    }
