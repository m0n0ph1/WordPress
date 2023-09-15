<?php

    class WP_HTML_Token
    {
        public $bookmark_name = null;

        public $node_name = null;

        public $has_self_closing_flag = false;

        public $on_destroy = null;

        public function __construct($bookmark_name, $node_name, $has_self_closing_flag, $on_destroy = null)
        {
            $this->bookmark_name = $bookmark_name;
            $this->node_name = $node_name;
            $this->has_self_closing_flag = $has_self_closing_flag;
            $this->on_destroy = $on_destroy;
        }

        public function __destruct()
        {
            if(is_callable($this->on_destroy))
            {
                call_user_func($this->on_destroy, $this->bookmark_name);
            }
        }
    }
