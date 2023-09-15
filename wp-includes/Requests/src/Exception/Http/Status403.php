<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status403 extends Http
    {
        protected $code = 403;

        protected $reason = 'Forbidden';
    }
