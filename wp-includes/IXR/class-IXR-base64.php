<?php

    class IXR_Base64
    {
        public $data;

        public function IXR_Base64($data)
        {
            $this->__construct($data);
        }

        public function __construct($data)
        {
            $this->data = $data;
        }

        public function getXml()
        {
            return '<base64>'.base64_encode($this->data).'</base64>';
        }
    }
