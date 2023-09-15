<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status501 extends Http
    {
        protected $code = 501;

        protected $reason = 'Not Implemented';
    }
