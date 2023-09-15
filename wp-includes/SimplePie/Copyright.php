<?php

    class Copyright
    {
        public $url;

        public $label;

        public function __construct($url = null, $label = null)
        {
            $this->url = $url;
            $this->label = $label;
        }

        public function __toString()
        {
            // There is no $this->data here
            return md5(serialize($this));
        }

        public function get_url()
        {
            if($this->url !== null)
            {
                return $this->url;
            }

            return null;
        }

        public function get_attribution()
        {
            if($this->label !== null)
            {
                return $this->label;
            }

            return null;
        }
    }
