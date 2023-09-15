<?php

    class Caption
    {
        public $type;

        public $lang;

        public $startTime;

        public $endTime;

        public $text;

        public function __construct($type = null, $lang = null, $startTime = null, $endTime = null, $text = null)
        {
            $this->type = $type;
            $this->lang = $lang;
            $this->startTime = $startTime;
            $this->endTime = $endTime;
            $this->text = $text;
        }

        public function __toString()
        {
            // There is no $this->data here
            return md5(serialize($this));
        }

        public function get_endtime()
        {
            if($this->endTime !== null)
            {
                return $this->endTime;
            }

            return null;
        }

        public function get_language()
        {
            if($this->lang !== null)
            {
                return $this->lang;
            }

            return null;
        }

        public function get_starttime()
        {
            if($this->startTime !== null)
            {
                return $this->startTime;
            }

            return null;
        }

        public function get_text()
        {
            if($this->text !== null)
            {
                return $this->text;
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
    }
