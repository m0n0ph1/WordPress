<?php

    #[AllowDynamicProperties]
    class _WP_Dependency
    {
        public $handle;

        public $src;

        public $deps = [];

        public $ver = false;

        public $args = null;  // Custom property, such as $in_footer or $media.

        public $extra = [];

        public $textdomain;

        public $translations_path;

        public function __construct(...$args)
        {
            [$this->handle, $this->src, $this->deps, $this->ver, $this->args] = $args;
            if(! is_array($this->deps))
            {
                $this->deps = [];
            }
        }

        public function add_data($name, $data)
        {
            if(! is_scalar($name))
            {
                return false;
            }
            $this->extra[$name] = $data;

            return true;
        }

        public function set_translations($domain, $path = '')
        {
            if(! is_string($domain))
            {
                return false;
            }
            $this->textdomain = $domain;
            $this->translations_path = $path;

            return true;
        }
    }
