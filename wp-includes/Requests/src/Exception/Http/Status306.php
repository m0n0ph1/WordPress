<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status306 extends Http
    {
        protected $code = 306;

        protected $reason = 'Switch Proxy';
    }
