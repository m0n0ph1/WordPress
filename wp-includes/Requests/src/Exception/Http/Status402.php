<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status402 extends Http
    {
        protected $code = 402;

        protected $reason = 'Payment Required';
    }
