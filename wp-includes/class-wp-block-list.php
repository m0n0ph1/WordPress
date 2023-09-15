<?php

    #[AllowDynamicProperties]
    class WP_Block_List implements Iterator, ArrayAccess, Countable
    {
        protected $blocks;

        protected $available_context;

        protected $registry;

        public function __construct($blocks, $available_context = [], $registry = null)
        {
            if(! $registry instanceof WP_Block_Type_Registry)
            {
                $registry = WP_Block_Type_Registry::get_instance();
            }

            $this->blocks = $blocks;
            $this->available_context = $available_context;
            $this->registry = $registry;
        }

        #[ReturnTypeWillChange]
        public function offsetExists($index)
        {
            return isset($this->blocks[$index]);
        }

        #[ReturnTypeWillChange]
        public function offsetSet($index, $value)
        {
            if(is_null($index))
            {
                $this->blocks[] = $value;
            }
            else
            {
                $this->blocks[$index] = $value;
            }
        }

        #[ReturnTypeWillChange]
        public function offsetUnset($index)
        {
            unset($this->blocks[$index]);
        }

        #[ReturnTypeWillChange]
        public function rewind()
        {
            reset($this->blocks);
        }

        #[ReturnTypeWillChange]
        public function current()
        {
            return $this->offsetGet($this->key());
        }

        #[ReturnTypeWillChange]
        public function offsetGet($index)
        {
            $block = $this->blocks[$index];

            if(isset($block) && is_array($block))
            {
                $block = new WP_Block($block, $this->available_context, $this->registry);
                $this->blocks[$index] = $block;
            }

            return $block;
        }

        #[ReturnTypeWillChange]
        public function key()
        {
            return key($this->blocks);
        }

        #[ReturnTypeWillChange]
        public function next()
        {
            next($this->blocks);
        }

        #[ReturnTypeWillChange]
        public function valid()
        {
            return null !== key($this->blocks);
        }

        #[ReturnTypeWillChange]
        public function count()
        {
            return count($this->blocks);
        }
    }
