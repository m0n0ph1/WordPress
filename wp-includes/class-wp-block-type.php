<?php

    #[AllowDynamicProperties]
    class WP_Block_Type
    {
        public const GLOBAL_ATTRIBUTES = [
            'lock' => ['type' => 'object'],
        ];

        public $api_version = 1;

        public $name;

        public $title = '';

        public $category = null;

        public $parent = null;

        public $ancestor = null;

        public $icon = null;

        public $description = '';

        public $keywords = [];

        public $textdomain = null;

        public $styles = [];

        public $variations = [];

        public $selectors = [];

        public $supports = null;

        public $example = null;

        public $render_callback = null;

        public $attributes = null;

        public $uses_context = [];

        public $provides_context = null;

        public $block_hooks = [];

        public $editor_script_handles = [];

        public $script_handles = [];

        public $view_script_handles = [];

        public $editor_style_handles = [];

        public $style_handles = [];

        private $deprecated_properties = [
            'editor_script',
            'script',
            'view_script',
            'editor_style',
            'style',
        ];

        public function __construct($block_type, $args = [])
        {
            $this->name = $block_type;

            $this->set_props($args);
        }

        public function set_props($args)
        {
            $args = wp_parse_args($args, [
                'render_callback' => null,
            ]);

            $args['name'] = $this->name;

            // Setup attributes if needed.
            if(! isset($args['attributes']) || ! is_array($args['attributes']))
            {
                $args['attributes'] = [];
            }

            // Register core attributes.
            foreach(static::GLOBAL_ATTRIBUTES as $attr_key => $attr_schema)
            {
                if(! array_key_exists($attr_key, $args['attributes']))
                {
                    $args['attributes'][$attr_key] = $attr_schema;
                }
            }

            $args = apply_filters('register_block_type_args', $args, $this->name);

            foreach($args as $property_name => $property_value)
            {
                $this->$property_name = $property_value;
            }
        }

        public function __get($name)
        {
            if(! in_array($name, $this->deprecated_properties, true))
            {
                return;
            }

            $new_name = $name.'_handles';

            if(! property_exists($this, $new_name) || ! is_array($this->{$new_name}))
            {
                return null;
            }

            if(count($this->{$new_name}) > 1)
            {
                return $this->{$new_name};
            }

            if(isset($this->{$new_name}[0]))
            {
                return $this->{$new_name}[0];
            }

            return null;
        }

        public function __set($name, $value)
        {
            if(! in_array($name, $this->deprecated_properties, true))
            {
                $this->{$name} = $value;

                return;
            }

            $new_name = $name.'_handles';

            if(is_array($value))
            {
                $filtered = array_filter($value, 'is_string');

                if(count($filtered) !== count($value))
                {
                    _doing_it_wrong(__METHOD__, sprintf(/* translators: %s: The '$value' argument. */ __('The %s argument must be a string or a string array.'), '<code>$value</code>'), '6.1.0');
                }

                $this->{$new_name} = array_values($filtered);

                return;
            }

            if(! is_string($value))
            {
                return;
            }

            $this->{$new_name} = [$value];
        }

        public function __isset($name)
        {
            if(! in_array($name, $this->deprecated_properties, true))
            {
                return false;
            }

            $new_name = $name.'_handles';

            return isset($this->{$new_name}[0]);
        }

        public function render($attributes = [], $content = '')
        {
            if(! $this->is_dynamic())
            {
                return '';
            }

            $attributes = $this->prepare_attributes_for_render($attributes);

            return (string) call_user_func($this->render_callback, $attributes, $content);
        }

        public function is_dynamic()
        {
            return is_callable($this->render_callback);
        }

        public function prepare_attributes_for_render($attributes)
        {
            // If there are no attribute definitions for the block type, skip
            // processing and return verbatim.
            if(! isset($this->attributes))
            {
                return $attributes;
            }

            foreach($attributes as $attribute_name => $value)
            {
                // If the attribute is not defined by the block type, it cannot be
                // validated.
                if(! isset($this->attributes[$attribute_name]))
                {
                    continue;
                }

                $schema = $this->attributes[$attribute_name];

                // Validate value by JSON schema. An invalid value should revert to
                // its default, if one exists. This occurs by virtue of the missing
                // attributes loop immediately following. If there is not a default
                // assigned, the attribute value should remain unset.
                $is_valid = rest_validate_value_from_schema($value, $schema, $attribute_name);
                if(is_wp_error($is_valid))
                {
                    unset($attributes[$attribute_name]);
                }
            }

            // Populate values of any missing attributes for which the block type
            // defines a default.
            $missing_schema_attributes = array_diff_key($this->attributes, $attributes);
            foreach($missing_schema_attributes as $attribute_name => $schema)
            {
                if(isset($schema['default']))
                {
                    $attributes[$attribute_name] = $schema['default'];
                }
            }

            return $attributes;
        }

        public function get_attributes()
        {
            if(is_array($this->attributes))
            {
                return $this->attributes;
            }

            return [];
        }
    }
