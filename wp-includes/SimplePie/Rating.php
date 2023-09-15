<?php

    class Rating
    {
        public $scheme;

        public $value;

        public function __construct($scheme = null, $value = null)
        {
            $this->scheme = $scheme;
            $this->value = $value;
        }

        public function __toString()
        {
            // There is no $this->data here
            return md5(serialize($this));
        }

        public function get_scheme()
        {
            if($this->scheme !== null)
            {
                return $this->scheme;
            }

            return null;
        }

        public function get_value()
        {
            if($this->value !== null)
            {
                return $this->value;
            }

            return null;
        }
    }
