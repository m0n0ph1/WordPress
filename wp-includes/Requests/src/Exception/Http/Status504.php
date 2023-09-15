<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status504 extends Http
    {
        protected $code = 504;

        protected $reason = 'Gateway Timeout';
    }
