<?php

    class Category
    {
        public $term;

        public $scheme;

        public $label;

        public $type;

        public function __construct($term = null, $scheme = null, $label = null, $type = null)
        {
            $this->term = $term;
            $this->scheme = $scheme;
            $this->label = $label;
            $this->type = $type;
        }

        public function __toString()
        {
            // There is no $this->data here
            return md5(serialize($this));
        }

        public function get_scheme()
        {
            return $this->scheme;
        }

        public function get_label($strict = false)
        {
            if($this->label === null && $strict !== true)
            {
                return $this->get_term();
            }

            return $this->label;
        }

        public function get_term()
        {
            return $this->term;
        }

        public function get_type()
        {
            return $this->type;
        }
    }

