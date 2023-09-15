<?php
    /**
     * Style Engine: WP_Style_Engine_CSS_Declarations class
     *
     * @package    WordPress
     * @subpackage StyleEngine
     * @since      6.1.0
     */

    /**
     * Core class used for style engine CSS declarations.
     *
     * Holds, sanitizes, processes, and prints CSS declarations for the style engine.
     *
     * @since 6.1.0
     */
    #[AllowDynamicProperties]
    class WP_Style_Engine_CSS_Declarations
    {
        /**
         * An array of CSS declarations (property => value pairs).
         *
         * @since 6.1.0
         *
         * @var string[]
         */
        protected $declarations = [];

        /**
         * Constructor for this object.
         *
         * If a `$declarations` array is passed, it will be used to populate
         * the initial `$declarations` prop of the object by calling add_declarations().
         *
         * @param string[] $declarations Optional. An associative array of CSS definitions,
         *                               e.g. `array( "$property" => "$value", "$property" => "$value" )`.
         *                               Default empty array.
         *
         * @since 6.1.0
         *
         */
        public function __construct($declarations = [])
        {
            $this->add_declarations($declarations);
        }

        /**
         * Adds multiple declarations.
         *
         * @param string[] $declarations An array of declarations.
         *
         * @return WP_Style_Engine_CSS_Declarations Returns the object to allow chaining methods.
         * @since 6.1.0
         *
         */
        public function add_declarations($declarations)
        {
            foreach($declarations as $property => $value)
            {
                $this->add_declaration($property, $value);
            }

            return $this;
        }

        /**
         * Adds a single declaration.
         *
         * @param string $property The CSS property.
         * @param string $value    The CSS value.
         *
         * @return WP_Style_Engine_CSS_Declarations Returns the object to allow chaining methods.
         * @since 6.1.0
         *
         */
        public function add_declaration($property, $value)
        {
            // Sanitizes the property.
            $property = $this->sanitize_property($property);
            // Bails early if the property is empty.
            if(empty($property))
            {
                return $this;
            }
            // Trims the value. If empty, bail early.
            $value = trim($value);
            if('' === $value)
            {
                return $this;
            }
            // Adds the declaration property/value pair.
            $this->declarations[$property] = $value;

            return $this;
        }

        /**
         * Sanitizes property names.
         *
         * @param string $property The CSS property.
         *
         * @return string The sanitized property name.
         * @since 6.1.0
         *
         */
        protected function sanitize_property($property)
        {
            return sanitize_key($property);
        }

        /**
         * Removes multiple declarations.
         *
         * @param string[] $properties Optional. An array of properties. Default empty array.
         *
         * @return WP_Style_Engine_CSS_Declarations Returns the object to allow chaining methods.
         * @since 6.1.0
         *
         */
        public function remove_declarations($properties = [])
        {
            foreach($properties as $property)
            {
                $this->remove_declaration($property);
            }

            return $this;
        }

        /**
         * Removes a single declaration.
         *
         * @param string $property The CSS property.
         *
         * @return WP_Style_Engine_CSS_Declarations Returns the object to allow chaining methods.
         * @since 6.1.0
         *
         */
        public function remove_declaration($property)
        {
            unset($this->declarations[$property]);

            return $this;
        }

        /**
         * Filters and compiles the CSS declarations.
         *
         * @param bool $should_prettify Optional. Whether to add spacing, new lines and indents.
         *                              Default false.
         * @param int  $indent_count    Optional. The number of tab indents to apply to the rule.
         *                              Applies if `prettify` is `true`. Default 0.
         *
         * @return string The CSS declarations.
         * @since 6.1.0
         *
         */
        public function get_declarations_string($should_prettify = false, $indent_count = 0)
        {
            $declarations_array = $this->get_declarations();
            $declarations_output = '';
            $indent = $should_prettify ? str_repeat("\t", $indent_count) : '';
            $suffix = $should_prettify ? ' ' : '';
            $suffix = $should_prettify && $indent_count > 0 ? "\n" : $suffix;
            $spacer = $should_prettify ? ' ' : '';
            foreach($declarations_array as $property => $value)
            {
                $filtered_declaration = static::filter_declaration($property, $value, $spacer);
                if($filtered_declaration)
                {
                    $declarations_output .= "{$indent}{$filtered_declaration};$suffix";
                }
            }

            return rtrim($declarations_output);
        }

        /**
         * Gets the declarations array.
         *
         * @return string[] The declarations array.
         * @since 6.1.0
         *
         */
        public function get_declarations()
        {
            return $this->declarations;
        }

        /**
         * Filters a CSS property + value pair.
         *
         * @param string $property The CSS property.
         * @param string $value    The value to be filtered.
         * @param string $spacer   Optional. The spacer between the colon and the value.
         *                         Default empty string.
         *
         * @return string The filtered declaration or an empty string.
         * @since 6.1.0
         *
         */
        protected static function filter_declaration($property, $value, $spacer = '')
        {
            $filtered_value = wp_strip_all_tags($value, true);
            if('' !== $filtered_value)
            {
                return safecss_filter_attr("{$property}:{$spacer}{$filtered_value}");
            }

            return '';
        }
    }
