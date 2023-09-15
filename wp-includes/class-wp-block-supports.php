<?php

    #[AllowDynamicProperties]
    class WP_Block_Supports
    {
        public static $block_to_render = null;

        private static $instance = null;

        private $block_supports = [];

        public static function init()
        {
            $instance = self::get_instance();
            $instance->register_attributes();
        }

        public static function get_instance()
        {
            if(null === self::$instance)
            {
                self::$instance = new self();
            }

            return self::$instance;
        }

        private function register_attributes()
        {
            $block_registry = WP_Block_Type_Registry::get_instance();
            $registered_block_types = $block_registry->get_all_registered();
            foreach($registered_block_types as $block_type)
            {
                if(! property_exists($block_type, 'supports'))
                {
                    continue;
                }
                if(! $block_type->attributes)
                {
                    $block_type->attributes = [];
                }

                foreach($this->block_supports as $block_support_config)
                {
                    if(! isset($block_support_config['register_attribute']))
                    {
                        continue;
                    }

                    call_user_func($block_support_config['register_attribute'], $block_type);
                }
            }
        }

        public function register($block_support_name, $block_support_config)
        {
            $this->block_supports[$block_support_name] = array_merge($block_support_config, ['name' => $block_support_name]);
        }

        public function apply_block_supports()
        {
            $block_type = WP_Block_Type_Registry::get_instance()->get_registered(self::$block_to_render['blockName']);

            // If no render_callback, assume styles have been previously handled.
            if(! $block_type || empty($block_type))
            {
                return [];
            }

            $block_attributes = array_key_exists('attrs', self::$block_to_render) ? self::$block_to_render['attrs'] : [];

            $output = [];
            foreach($this->block_supports as $block_support_config)
            {
                if(! isset($block_support_config['apply']))
                {
                    continue;
                }

                $new_attributes = call_user_func($block_support_config['apply'], $block_type, $block_attributes);

                if(! empty($new_attributes))
                {
                    foreach($new_attributes as $attribute_name => $attribute_value)
                    {
                        if(empty($output[$attribute_name]))
                        {
                            $output[$attribute_name] = $attribute_value;
                        }
                        else
                        {
                            $output[$attribute_name] .= " $attribute_value";
                        }
                    }
                }
            }

            return $output;
        }
    }

    function get_block_wrapper_attributes($extra_attributes = [])
    {
        $new_attributes = WP_Block_Supports::get_instance()->apply_block_supports();

        if(empty($new_attributes) && empty($extra_attributes))
        {
            return '';
        }

        // This is hardcoded on purpose.
        // We only support a fixed list of attributes.
        $attributes_to_merge = ['style', 'class', 'id'];
        $attributes = [];
        foreach($attributes_to_merge as $attribute_name)
        {
            if(empty($new_attributes[$attribute_name]) && empty($extra_attributes[$attribute_name]))
            {
                continue;
            }

            if(empty($new_attributes[$attribute_name]))
            {
                $attributes[$attribute_name] = $extra_attributes[$attribute_name];
                continue;
            }

            if(empty($extra_attributes[$attribute_name]))
            {
                $attributes[$attribute_name] = $new_attributes[$attribute_name];
                continue;
            }

            $attributes[$attribute_name] = $extra_attributes[$attribute_name].' '.$new_attributes[$attribute_name];
        }

        foreach($extra_attributes as $attribute_name => $value)
        {
            if(! in_array($attribute_name, $attributes_to_merge, true))
            {
                $attributes[$attribute_name] = $value;
            }
        }

        if(empty($attributes))
        {
            return '';
        }

        $normalized_attributes = [];
        foreach($attributes as $key => $value)
        {
            $normalized_attributes[] = $key.'="'.esc_attr($value).'"';
        }

        return implode(' ', $normalized_attributes);
    }
