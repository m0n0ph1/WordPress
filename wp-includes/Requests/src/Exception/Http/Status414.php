<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status414 extends Http
    {
        protected $code = 414;

        protected $reason = 'Request-URI Too Large';
    }
