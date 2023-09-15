<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status412 extends Http
    {
        protected $code = 412;

        protected $reason = 'Precondition Failed';
    }
