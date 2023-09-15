<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status400 extends Http
    {
        protected $code = 400;

        protected $reason = 'Bad Request';
    }
