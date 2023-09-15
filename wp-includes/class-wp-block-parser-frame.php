<?php

    class WP_Block_Parser_Frame
    {
        public $block;

        public $token_start;

        public $token_length;

        public $prev_offset;

        public $leading_html_start;

        public function __construct(
            $block, $token_start, $token_length, $prev_offset = null, $leading_html_start = null
        ) {
            $this->block = $block;
            $this->token_start = $token_start;
            $this->token_length = $token_length;
            $this->prev_offset = isset($prev_offset) ? $prev_offset : $token_start + $token_length;
            $this->leading_html_start = $leading_html_start;
        }
    }
