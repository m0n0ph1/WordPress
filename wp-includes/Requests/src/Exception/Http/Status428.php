<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status428 extends Http
    {
        protected $code = 428;

        protected $reason = 'Precondition Required';
    }
