<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status408 extends Http
    {
        protected $code = 408;

        protected $reason = 'Request Timeout';
    }
