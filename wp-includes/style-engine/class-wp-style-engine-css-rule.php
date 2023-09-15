<?php

    #[AllowDynamicProperties]
    class WP_Style_Engine_CSS_Rule
    {
        protected $selector;

        protected $declarations;

        public function __construct($selector = '', $declarations = [])
        {
            $this->set_selector($selector);
            $this->add_declarations($declarations);
        }

        public function add_declarations($declarations)
        {
            $is_declarations_object = ! is_array($declarations);
            $declarations_array = $is_declarations_object ? $declarations->get_declarations() : $declarations;

            if(null === $this->declarations)
            {
                if($is_declarations_object)
                {
                    $this->declarations = $declarations;

                    return $this;
                }
                $this->declarations = new WP_Style_Engine_CSS_Declarations($declarations_array);
            }
            $this->declarations->add_declarations($declarations_array);

            return $this;
        }

        public function get_declarations()
        {
            return $this->declarations;
        }

        public function get_css($should_prettify = false, $indent_count = 0)
        {
            $rule_indent = $should_prettify ? str_repeat("\t", $indent_count) : '';
            $declarations_indent = $should_prettify ? $indent_count + 1 : 0;
            $suffix = $should_prettify ? "\n" : '';
            $spacer = $should_prettify ? ' ' : '';
            $selector = $should_prettify ? str_replace(',', ",\n", $this->get_selector()) : $this->get_selector();
            $css_declarations = $this->declarations->get_declarations_string($should_prettify, $declarations_indent);

            if(empty($css_declarations))
            {
                return '';
            }

            return "{$rule_indent}{$selector}{$spacer}{{$suffix}{$css_declarations}{$suffix}{$rule_indent}}";
        }

        public function get_selector()
        {
            return $this->selector;
        }

        public function set_selector($selector)
        {
            $this->selector = $selector;

            return $this;
        }
    }
