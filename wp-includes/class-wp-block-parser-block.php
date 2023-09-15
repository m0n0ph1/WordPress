<?php

    class WP_Block_Parser_Block
    {
        public $blockName; // phpcs:ignore WordPress.NamingConventions.ValidVariableName

        public $attrs;

        public $innerBlocks; // phpcs:ignore WordPress.NamingConventions.ValidVariableName

        public $innerHTML; // phpcs:ignore WordPress.NamingConventions.ValidVariableName

        public $innerContent; // phpcs:ignore WordPress.NamingConventions.ValidVariableName

        public function __construct($name, $attrs, $inner_blocks, $inner_html, $inner_content)
        {
            $this->blockName = $name;          // phpcs:ignore WordPress.NamingConventions.ValidVariableName
            $this->attrs = $attrs;
            $this->innerBlocks = $inner_blocks;  // phpcs:ignore WordPress.NamingConventions.ValidVariableName
            $this->innerHTML = $inner_html;    // phpcs:ignore WordPress.NamingConventions.ValidVariableName
            $this->innerContent = $inner_content; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
        }
    }
