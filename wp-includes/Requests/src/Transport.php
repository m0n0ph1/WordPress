<?php

    namespace WpOrg\Requests;

    interface Transport
    {
        public static function test($capabilities = []);

        public function request($url, $headers = [], $data = [], $options = []);

        public function request_multiple($requests, $options);
    }
