<?php

    class IXR_ClientMulticall extends IXR_Client
    {
        public $calls = [];

        public function IXR_ClientMulticall($server, $path = false, $port = 80)
        {
            $this->__construct($server, $path, $port);
        }

        public function __construct($server, $path = false, $port = 80)
        {
            parent::__construct($server, $path, $port, null);
            parent::IXR_Client($server, $path, $port);
            $this->useragent = 'The Incutio XML-RPC PHP Library (multicall client)';
        }

        public function addCall(...$args)
        {
            $methodName = array_shift($args);
            $struct = [
                'methodName' => $methodName,
                'params' => $args
            ];
            $this->calls[] = $struct;
        }

        public function query(...$args)
        {
            // Prepare multicall, then call the parent::query() method
            return parent::query('system.multicall', $this->calls);
        }
    }
