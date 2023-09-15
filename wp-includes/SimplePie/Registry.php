<?php

    class Registry
    {
        protected $default = [
            'Cache' => 'SimplePie_Cache',
            'Locator' => 'SimplePie_Locator',
            'Parser' => 'SimplePie_Parser',
            'File' => 'SimplePie_File',
            'Sanitize' => 'SimplePie_Sanitize',
            'Item' => 'SimplePie_Item',
            'Author' => 'SimplePie_Author',
            'Category' => 'SimplePie_Category',
            'Enclosure' => 'SimplePie_Enclosure',
            'Caption' => 'SimplePie_Caption',
            'Copyright' => 'SimplePie_Copyright',
            'Credit' => 'SimplePie_Credit',
            'Rating' => 'SimplePie_Rating',
            'Restriction' => 'SimplePie_Restriction',
            'Content_Type_Sniffer' => 'SimplePie_Content_Type_Sniffer',
            'Source' => 'SimplePie_Source',
            'Misc' => 'SimplePie_Misc',
            'XML_Declaration_Parser' => 'SimplePie_XML_Declaration_Parser',
            'Parse_Date' => 'SimplePie_Parse_Date',
        ];

        protected $classes = [];

        protected $legacy = [];

        public function __construct() {}

        public function register($type, $class, $legacy = false)
        {
            if(! @is_subclass_of($class, $this->default[$type]))
            {
                return false;
            }

            $this->classes[$type] = $class;

            if($legacy)
            {
                $this->legacy[] = $class;
            }

            return true;
        }

        public function &create($type, $parameters = [])
        {
            $class = $this->get_class($type);

            if(in_array($class, $this->legacy))
            {
                switch($type)
                {
                    case 'locator':
                        // Legacy: file, timeout, useragent, file_class, max_checked_feeds, content_type_sniffer_class
                        // Specified: file, timeout, useragent, max_checked_feeds
                        $replacement = [
                            $this->get_class('file'),
                            $parameters[3],
                            $this->get_class('content_type_sniffer')
                        ];
                        array_splice($parameters, 3, 1, $replacement);
                        break;
                }
            }

            if(method_exists($class, '__construct'))
            {
                $reflector = new ReflectionClass($class);
                $instance = $reflector->newInstanceArgs($parameters);
            }
            else
            {
                $instance = new $class();
            }

            if(method_exists($instance, 'set_registry'))
            {
                $instance->set_registry($this);
            }

            return $instance;
        }

        public function get_class($type)
        {
            if(! empty($this->classes[$type]))
            {
                return $this->classes[$type];
            }
            if(! empty($this->default[$type]))
            {
                return $this->default[$type];
            }

            return null;
        }

        public function &call($type, $method, $parameters = [])
        {
            $class = $this->get_class($type);

            if(in_array($class, $this->legacy))
            {
                switch($type)
                {
                    case 'Cache':
                        // For backwards compatibility with old non-static
                        // Cache::create() methods in PHP < 8.0.
                        // No longer supported as of PHP 8.0.
                        if($method === 'get_handler')
                        {
                            $result = @call_user_func_array([$class, 'create'], $parameters);

                            return $result;
                        }
                        break;
                }
            }

            $result = call_user_func_array([$class, $method], $parameters);

            return $result;
        }
    }
