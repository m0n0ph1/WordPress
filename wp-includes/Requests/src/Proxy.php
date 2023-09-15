<?php

    namespace WpOrg\Requests;

    interface Proxy
    {
        public function register(Hooks $hooks);
    }
