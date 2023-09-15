<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status418 extends Http
    {
        protected $code = 418;

        protected $reason = "I'm A Teapot";
    }
