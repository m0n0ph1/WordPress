<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status502 extends Http
    {
        protected $code = 502;

        protected $reason = 'Bad Gateway';
    }
