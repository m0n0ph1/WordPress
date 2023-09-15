<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;

    final class Status416 extends Http
    {
        protected $code = 416;

        protected $reason = 'Requested Range Not Satisfiable';
    }
