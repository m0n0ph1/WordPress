<?php

    class IXR_ClientMulticall extends IXR_Client
    {
        var $calls = [];

        public function IXR_ClientMulticall($server, $path = false, $port = 80)
        {
            self::__construct($server, $path, $port);
        }

        function __construct($server, $path = false, $port = 80)
        {
            parent::IXR_Client($server, $path, $port);
            $this->useragent = 'The Incutio XML-RPC PHP Library (multicall client)';
        }

        function addCall(...$args)
        {
            $methodName = array_shift($args);
            $struct = [
                'methodName' => $methodName,
                'params' => $args
            ];
            $this->calls[] = $struct;
        }

        function query(...$args)
        {
            // Prepare multicall, then call the parent::query() method
            return parent::query('system.multicall', $this->calls);
        }
    }
