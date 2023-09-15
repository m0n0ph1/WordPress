<?php

    #[AllowDynamicProperties]
    class WP_Block
    {
        public $parsed_block;

        public $name;

        public $block_type;

        public $context = [];

        public $inner_blocks = [];

        public $inner_html = '';

        public $inner_content = [];

        protected $available_context;

        protected $registry;

        public function __construct($block, $available_context = [], $registry = null)
        {
            $this->parsed_block = $block;
            $this->name = $block['blockName'];

            if(is_null($registry))
            {
                $registry = WP_Block_Type_Registry::get_instance();
            }

            $this->registry = $registry;

            $this->block_type = $registry->get_registered($this->name);

            $this->available_context = $available_context;

            if(! empty($this->block_type->uses_context))
            {
                foreach($this->block_type->uses_context as $context_name)
                {
                    if(array_key_exists($context_name, $this->available_context))
                    {
                        $this->context[$context_name] = $this->available_context[$context_name];
                    }
                }
            }

            if(! empty($block['innerBlocks']))
            {
                $child_context = $this->available_context;

                if(! empty($this->block_type->provides_context))
                {
                    foreach($this->block_type->provides_context as $context_name => $attribute_name)
                    {
                        if(array_key_exists($attribute_name, $this->attributes))
                        {
                            $child_context[$context_name] = $this->attributes[$attribute_name];
                        }
                    }
                }

                $this->inner_blocks = new WP_Block_List($block['innerBlocks'], $child_context, $registry);
            }

            if(! empty($block['innerHTML']))
            {
                $this->inner_html = $block['innerHTML'];
            }

            if(! empty($block['innerContent']))
            {
                $this->inner_content = $block['innerContent'];
            }
        }

        public function __get($name)
        {
            if('attributes' === $name)
            {
                $this->attributes = isset($this->parsed_block['attrs']) ? $this->parsed_block['attrs'] : [];

                if(! is_null($this->block_type))
                {
                    $this->attributes = $this->block_type->prepare_attributes_for_render($this->attributes);
                }

                return $this->attributes;
            }

            return null;
        }

        public function render($options = [])
        {
            global $post;
            $options = wp_parse_args($options, [
                'dynamic' => true,
            ]);

            $is_dynamic = $options['dynamic'] && $this->name && null !== $this->block_type && $this->block_type->is_dynamic();
            $block_content = '';

            if(! $options['dynamic'] || empty($this->block_type->skip_inner_blocks))
            {
                $index = 0;

                foreach($this->inner_content as $chunk)
                {
                    if(is_string($chunk))
                    {
                        $block_content .= $chunk;
                    }
                    else
                    {
                        $inner_block = $this->inner_blocks[$index];
                        $parent_block = $this;

                        $pre_render = apply_filters('pre_render_block', null, $inner_block->parsed_block, $parent_block);

                        if(! is_null($pre_render))
                        {
                            $block_content .= $pre_render;
                        }
                        else
                        {
                            $source_block = $inner_block->parsed_block;

                            $inner_block->parsed_block = apply_filters('render_block_data', $inner_block->parsed_block, $source_block, $parent_block);

                            $inner_block->context = apply_filters('render_block_context', $inner_block->context, $inner_block->parsed_block, $parent_block);

                            $block_content .= $inner_block->render();
                        }

                        ++$index;
                    }
                }
            }

            if($is_dynamic)
            {
                $global_post = $post;
                $parent = WP_Block_Supports::$block_to_render;

                WP_Block_Supports::$block_to_render = $this->parsed_block;

                $block_content = (string) call_user_func($this->block_type->render_callback, $this->attributes, $block_content, $this);

                WP_Block_Supports::$block_to_render = $parent;

                $post = $global_post;
            }

            if((! empty($this->block_type->script_handles)))
            {
                foreach($this->block_type->script_handles as $script_handle)
                {
                    wp_enqueue_script($script_handle);
                }
            }

            if(! empty($this->block_type->view_script_handles))
            {
                foreach($this->block_type->view_script_handles as $view_script_handle)
                {
                    wp_enqueue_script($view_script_handle);
                }
            }

            if((! empty($this->block_type->style_handles)))
            {
                foreach($this->block_type->style_handles as $style_handle)
                {
                    wp_enqueue_style($style_handle);
                }
            }

            $block_content = apply_filters('render_block', $block_content, $this->parsed_block, $this);

            $block_content = apply_filters("render_block_{$this->name}", $block_content, $this->parsed_block, $this);

            return $block_content;
        }
    }
