<?php

    class WP_HTML_Attribute_Token
    {
        public $name;

        public $value_starts_at;

        public $value_length;

        public $start;

        public $end;

        public $is_true;

        public function __construct($name, $value_start, $value_length, $start, $end, $is_true)
        {
            $this->name = $name;
            $this->value_starts_at = $value_start;
            $this->value_length = $value_length;
            $this->start = $start;
            $this->end = $end;
            $this->is_true = $is_true;
        }
    }
