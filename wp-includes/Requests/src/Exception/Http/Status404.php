<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status404 extends Http
    {
        protected $code = 404;

        protected $reason = 'Not Found';
    }
