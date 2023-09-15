<?php

    class WP_HTML_Processor_State
    {
        /*
         * Insertion mode constants.
         *
         * These constants exist and are named to make it easier to
         * discover and recognize the supported insertion modes in
         * the parser.
         *
         * Out of all the possible insertion modes, only those
         * supported by the parser are listed here. As support
         * is added to the parser for more modes, add them here
         * following the same naming and value pattern.
         *
         * @see https://html.spec.whatwg.org/#the-insertion-mode
         */

        public const INSERTION_MODE_INITIAL = 'insertion-mode-initial';

        public const INSERTION_MODE_IN_BODY = 'insertion-mode-in-body';

        public $stack_of_open_elements = null;

        public $active_formatting_elements = null;

        public $current_token = null;

        public $insertion_mode = self::INSERTION_MODE_INITIAL;

        public $context_node = null;

        public $frameset_ok = true;

        public function __construct()
        {
            $this->stack_of_open_elements = new WP_HTML_Open_Elements();
            $this->active_formatting_elements = new WP_HTML_Active_Formatting_Elements();
        }
    }
