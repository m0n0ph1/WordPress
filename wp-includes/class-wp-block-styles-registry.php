<?php

    #[AllowDynamicProperties]
    final class WP_Block_Styles_Registry
    {
        private static $instance = null;

        private $registered_block_styles = [];

        public static function get_instance()
        {
            if(null === self::$instance)
            {
                self::$instance = new self();
            }

            return self::$instance;
        }

        public function register($block_name, $style_properties)
        {
            if(! isset($block_name) || ! is_string($block_name))
            {
                _doing_it_wrong(__METHOD__, __('Block name must be a string.'), '5.3.0');

                return false;
            }

            if(! isset($style_properties['name']) || ! is_string($style_properties['name']))
            {
                _doing_it_wrong(__METHOD__, __('Block style name must be a string.'), '5.3.0');

                return false;
            }

            if(str_contains($style_properties['name'], ' '))
            {
                _doing_it_wrong(__METHOD__, __('Block style name must not contain any spaces.'), '5.9.0');

                return false;
            }

            $block_style_name = $style_properties['name'];

            if(! isset($this->registered_block_styles[$block_name]))
            {
                $this->registered_block_styles[$block_name] = [];
            }
            $this->registered_block_styles[$block_name][$block_style_name] = $style_properties;

            return true;
        }

        public function unregister($block_name, $block_style_name)
        {
            if(! $this->is_registered($block_name, $block_style_name))
            {
                _doing_it_wrong(__METHOD__, /* translators: 1: Block name, 2: Block style name. */ sprintf(__('Block "%1$s" does not contain a style named "%2$s".'), $block_name, $block_style_name), '5.3.0');

                return false;
            }

            unset($this->registered_block_styles[$block_name][$block_style_name]);

            return true;
        }

        public function is_registered($block_name, $block_style_name)
        {
            return isset($this->registered_block_styles[$block_name][$block_style_name]);
        }

        public function get_registered($block_name, $block_style_name)
        {
            if(! $this->is_registered($block_name, $block_style_name))
            {
                return null;
            }

            return $this->registered_block_styles[$block_name][$block_style_name];
        }

        public function get_all_registered()
        {
            return $this->registered_block_styles;
        }

        public function get_registered_styles_for_block($block_name)
        {
            if(isset($this->registered_block_styles[$block_name]))
            {
                return $this->registered_block_styles[$block_name];
            }

            return [];
        }
    }
