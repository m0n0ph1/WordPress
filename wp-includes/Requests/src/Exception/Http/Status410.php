<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status410 extends Http
    {
        protected $code = 410;

        protected $reason = 'Gone';
    }
