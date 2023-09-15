<?php

    if(! class_exists('Translation_Entry', false)) :

        #[AllowDynamicProperties]
        class Translation_Entry
        {
            public $is_plural = false;

            public $context = null;

            public $singular = null;

            public $plural = null;

            public $translations = [];

            public $translator_comments = '';

            public $extracted_comments = '';

            public $references = [];

            public $flags = [];

            public function __construct($args = [])
            {
                // If no singular -- empty object.
                if(! isset($args['singular']))
                {
                    return;
                }
                // Get member variable values from args hash.
                foreach($args as $varname => $value)
                {
                    $this->$varname = $value;
                }
                if(isset($args['plural']) && $args['plural'])
                {
                    $this->is_plural = true;
                }
                if(! is_array($this->translations))
                {
                    $this->translations = [];
                }
                if(! is_array($this->references))
                {
                    $this->references = [];
                }
                if(! is_array($this->flags))
                {
                    $this->flags = [];
                }
            }

            public function Translation_Entry($args = [])
            {
                _deprecated_constructor(self::class, '5.4.0', static::class);
                self::__construct($args);
            }

            public function key()
            {
                if(null === $this->singular)
                {
                    return false;
                }

                // Prepend context and EOT, like in MO files.
                $key = ! $this->context ? $this->singular : $this->context."\4".$this->singular;
                // Standardize on \n line endings.
                $key = str_replace(["\r\n", "\r"], "\n", $key);

                return $key;
            }

            public function merge_with(&$other)
            {
                $this->flags = array_unique(array_merge($this->flags, $other->flags));
                $this->references = array_unique(array_merge($this->references, $other->references));
                if($this->extracted_comments !== $other->extracted_comments)
                {
                    $this->extracted_comments .= $other->extracted_comments;
                }
            }
        }
    endif;
