<?php

    class WP_HTML_Text_Replacement
    {
        public $start;

        public $end;

        public $text;

        public function __construct($start, $end, $text)
        {
            $this->start = $start;
            $this->end = $end;
            $this->text = $text;
        }
    }
