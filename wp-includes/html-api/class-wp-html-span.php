<?php

    class WP_HTML_Span
    {
        public $start;

        public $end;

        public function __construct($start, $end)
        {
            $this->start = $start;
            $this->end = $end;
        }
    }
