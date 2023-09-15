<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status411 extends Http
    {
        protected $code = 411;

        protected $reason = 'Length Required';
    }
