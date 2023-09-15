<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status409 extends Http
    {
        protected $code = 409;

        protected $reason = 'Conflict';
    }
