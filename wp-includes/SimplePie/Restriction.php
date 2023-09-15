<?php

    class Restriction
    {
        public $relationship;

        public $type;

        public $value;

        public function __construct($relationship = null, $type = null, $value = null)
        {
            $this->relationship = $relationship;
            $this->type = $type;
            $this->value = $value;
        }

        public function __toString()
        {
            // There is no $this->data here
            return md5(serialize($this));
        }

        public function get_relationship()
        {
            if($this->relationship !== null)
            {
                return $this->relationship;
            }

            return null;
        }

        public function get_type()
        {
            if($this->type !== null)
            {
                return $this->type;
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
