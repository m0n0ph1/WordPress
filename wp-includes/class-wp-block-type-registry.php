<?php

    #[AllowDynamicProperties]
    final class WP_Block_Type_Registry
    {
        private static $instance = null;

        private $registered_block_types = [];

        public static function get_instance()
        {
            if(null === self::$instance)
            {
                self::$instance = new self();
            }

            return self::$instance;
        }

        public function register($name, $args = [])
        {
            $block_type = null;
            if($name instanceof WP_Block_Type)
            {
                $block_type = $name;
                $name = $block_type->name;
            }

            if(! is_string($name))
            {
                _doing_it_wrong(__METHOD__, __('Block type names must be strings.'), '5.0.0');

                return false;
            }

            if(preg_match('/[A-Z]+/', $name))
            {
                _doing_it_wrong(__METHOD__, __('Block type names must not contain uppercase characters.'), '5.0.0');

                return false;
            }

            $name_matcher = '/^[a-z0-9-]+\/[a-z0-9-]+$/';
            if(! preg_match($name_matcher, $name))
            {
                _doing_it_wrong(__METHOD__, __('Block type names must contain a namespace prefix. Example: my-plugin/my-custom-block-type'), '5.0.0');

                return false;
            }

            if($this->is_registered($name))
            {
                _doing_it_wrong(__METHOD__, /* translators: %s: Block name. */ sprintf(__('Block type "%s" is already registered.'), $name), '5.0.0');

                return false;
            }

            if(! $block_type)
            {
                $block_type = new WP_Block_Type($name, $args);
            }

            $this->registered_block_types[$name] = $block_type;

            return $block_type;
        }

        public function is_registered($name)
        {
            return isset($this->registered_block_types[$name]);
        }

        public function unregister($name)
        {
            if($name instanceof WP_Block_Type)
            {
                $name = $name->name;
            }

            if(! $this->is_registered($name))
            {
                _doing_it_wrong(__METHOD__, /* translators: %s: Block name. */ sprintf(__('Block type "%s" is not registered.'), $name), '5.0.0');

                return false;
            }

            $unregistered_block_type = $this->registered_block_types[$name];
            unset($this->registered_block_types[$name]);

            return $unregistered_block_type;
        }

        public function get_registered($name)
        {
            if(! $this->is_registered($name))
            {
                return null;
            }

            return $this->registered_block_types[$name];
        }

        public function get_all_registered()
        {
            return $this->registered_block_types;
        }
    }
