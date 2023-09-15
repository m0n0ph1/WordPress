<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status417 extends Http
    {
        protected $code = 417;

        protected $reason = 'Expectation Failed';
    }
