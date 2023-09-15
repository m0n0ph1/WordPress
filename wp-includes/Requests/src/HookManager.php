<?php

    namespace WpOrg\Requests;

    interface HookManager
    {
        public function register($hook, $callback, $priority = 0);

        public function dispatch($hook, $parameters = []);
    }
