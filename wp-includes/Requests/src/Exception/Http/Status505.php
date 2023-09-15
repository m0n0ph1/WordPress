<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status505 extends Http
    {
        protected $code = 505;

        protected $reason = 'HTTP Version Not Supported';
    }
