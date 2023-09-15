<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status401 extends Http
    {
        protected $code = 401;

        protected $reason = 'Unauthorized';
    }
