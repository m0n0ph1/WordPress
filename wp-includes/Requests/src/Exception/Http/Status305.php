<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status305 extends Http
    {
        protected $code = 305;

        protected $reason = 'Use Proxy';
    }
