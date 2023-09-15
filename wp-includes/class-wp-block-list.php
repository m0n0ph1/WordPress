<?php
    /**
     * Blocks API: WP_Block_List class
     *
     * @package WordPress
     * @since   5.5.0
     */

    /**
     * Class representing a list of block instances.
     *
     * @since 5.5.0
     */
    #[AllowDynamicProperties]
    class WP_Block_List implements Iterator, ArrayAccess, Countable
    {
        /**
         * Original array of parsed block data, or block instances.
         *
         * @since  5.5.0
         * @var array[]|WP_Block[]
         * @access protected
         */
        protected $blocks;

        /**
         * All available context of the current hierarchy.
         *
         * @since  5.5.0
         * @var array
         * @access protected
         */
        protected $available_context;

        /**
         * Block type registry to use in constructing block instances.
         *
         * @since  5.5.0
         * @var WP_Block_Type_Registry
         * @access protected
         */
        protected $registry;

        /**
         * Constructor.
         *
         * Populates object properties from the provided block instance argument.
         *
         * @param array[]|WP_Block[]     $blocks            Array of parsed block data, or block instances.
         * @param array                  $available_context Optional array of ancestry context values.
         * @param WP_Block_Type_Registry $registry          Optional block type registry.
         *
         * @since 5.5.0
         *
         */
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

        /**
         * Returns true if a block exists by the specified block index, or false
         * otherwise.
         *
         * @param string $index Index of block to check.
         *
         * @return bool Whether block exists.
         * @since 5.5.0
         *
         * @link  https://www.php.net/manual/en/arrayaccess.offsetexists.php
         *
         */
        #[ReturnTypeWillChange]
        public function offsetExists($index)
        {
            return isset($this->blocks[$index]);
        }

        /**
         * Assign a block value by the specified block index.
         *
         * @param string $index Index of block value to set.
         * @param mixed  $value Block value.
         *
         * @since 5.5.0
         *
         * @link  https://www.php.net/manual/en/arrayaccess.offsetset.php
         *
         */
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

        /**
         * Unset a block.
         *
         * @param string $index Index of block value to unset.
         *
         * @link  https://www.php.net/manual/en/arrayaccess.offsetunset.php
         *
         * @since 5.5.0
         *
         */
        #[ReturnTypeWillChange]
        public function offsetUnset($index)
        {
            unset($this->blocks[$index]);
        }

        /**
         * Rewinds back to the first element of the Iterator.
         *
         * @since 5.5.0
         *
         * @link  https://www.php.net/manual/en/iterator.rewind.php
         */
        #[ReturnTypeWillChange]
        public function rewind()
        {
            reset($this->blocks);
        }

        /**
         * Returns the current element of the block list.
         *
         * @return mixed Current element.
         * @link  https://www.php.net/manual/en/iterator.current.php
         *
         * @since 5.5.0
         *
         */
        #[ReturnTypeWillChange]
        public function current()
        {
            return $this->offsetGet($this->key());
        }

        /**
         * Returns the value by the specified block index.
         *
         * @param string $index Index of block value to retrieve.
         *
         * @return mixed|null Block value if exists, or null.
         * @since 5.5.0
         *
         * @link  https://www.php.net/manual/en/arrayaccess.offsetget.php
         *
         */
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

        /**
         * Returns the key of the current element of the block list.
         *
         * @return mixed Key of the current element.
         * @link  https://www.php.net/manual/en/iterator.key.php
         *
         * @since 5.5.0
         *
         */
        #[ReturnTypeWillChange]
        public function key()
        {
            return key($this->blocks);
        }

        /**
         * Moves the current position of the block list to the next element.
         *
         * @since 5.5.0
         *
         * @link  https://www.php.net/manual/en/iterator.next.php
         */
        #[ReturnTypeWillChange]
        public function next()
        {
            next($this->blocks);
        }

        /**
         * Checks if current position is valid.
         *
         * @since 5.5.0
         *
         * @link  https://www.php.net/manual/en/iterator.valid.php
         */
        #[ReturnTypeWillChange]
        public function valid()
        {
            return null !== key($this->blocks);
        }

        /**
         * Returns the count of blocks in the list.
         *
         * @return int Block count.
         * @link  https://www.php.net/manual/en/countable.count.php
         *
         * @since 5.5.0
         *
         */
        #[ReturnTypeWillChange]
        public function count()
        {
            return count($this->blocks);
        }
    }
