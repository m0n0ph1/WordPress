<?php

    #[AllowDynamicProperties]
    class WP_MatchesMapRegex
    {
        public $output;

        public $_pattern = '(\$matches\[[1-9]+[0-9]*\])';

        private $_matches;

        private $_subject; // Magic number.

        public function __construct($subject, $matches)
        {
            $this->_subject = $subject;
            $this->_matches = $matches;
            $this->output = $this->_map();
        }

        private function _map()
        {
            $callback = [$this, 'callback'];

            return preg_replace_callback($this->_pattern, $callback, $this->_subject);
        }

        public static function apply($subject, $matches)
        {
            $oSelf = new WP_MatchesMapRegex($subject, $matches);

            return $oSelf->output;
        }

        public function callback($matches)
        {
            $index = (int) substr($matches[0], 9, -1);

            return (isset($this->_matches[$index]) ? urlencode($this->_matches[$index]) : '');
        }
    }
