<?php

    class IXR_Request
    {
        public $method;

        public $args;

        public $xml;

        public function IXR_Request($method, $args)
        {
            $this->__construct($method, $args);
        }

        public function __construct($method, $args)
        {
            $this->method = $method;
            $this->args = $args;
            $this->xml = <<<EOD
<?xml version="1.0"?>
<methodCall>
<methodName>{$this->method}</methodName>
<params>

EOD;
            foreach($this->args as $arg)
            {
                $this->xml .= '<param><value>';
                $v = new IXR_Value($arg);
                $this->xml .= $v->getXml();
                $this->xml .= "</value></param>\n";
            }
            $this->xml .= '</params></methodCall>';
        }

        public function getXml()
        {
            return $this->xml;
        }

        public function getLength()
        {
            return strlen($this->xml);
        }
    }
