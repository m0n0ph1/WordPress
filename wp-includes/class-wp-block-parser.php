<?php

    class WP_Block_Parser
    {
        public $document;

        public $offset;

        public $output;

        public $stack;

        public $empty_attrs;

        public function parse($document)
        {
            $this->document = $document;
            $this->offset = 0;
            $this->output = [];
            $this->stack = [];
            $this->empty_attrs = json_decode('{}', true);

            while($this->proceed())
            {
                continue;
            }

            return $this->output;
        }

        public function proceed()
        {
            $next_token = $this->next_token();
            [$token_type, $block_name, $attrs, $start_offset, $token_length] = $next_token;
            $stack_depth = count($this->stack);

            // we may have some HTML soup before the next block.
            $leading_html_start = $start_offset > $this->offset ? $this->offset : null;

            switch($token_type)
            {
                case 'no-more-tokens':
                    // if not in a block then flush output.
                    if(0 === $stack_depth)
                    {
                        $this->add_freeform();

                        return false;
                    }

                    /*
                     * Otherwise we have a problem
                     * This is an error
                     *
                     * we have options
                     * - treat it all as freeform text
                     * - assume an implicit closer (easiest when not nesting)
                     */

                    // for the easy case we'll assume an implicit closer.
                    if(1 === $stack_depth)
                    {
                        $this->add_block_from_stack();

                        return false;
                    }

                    /*
                     * for the nested case where it's more difficult we'll
                     * have to assume that multiple closers are missing
                     * and so we'll collapse the whole stack piecewise
                     */
                    while(0 < count($this->stack))
                    {
                        $this->add_block_from_stack();
                    }

                    return false;

                case 'void-block':
                    /*
                     * easy case is if we stumbled upon a void block
                     * in the top-level of the document
                     */ if(0 === $stack_depth)
                {
                    if(isset($leading_html_start))
                    {
                        $this->output[] = (array) $this->freeform(substr($this->document, $leading_html_start, $start_offset - $leading_html_start));
                    }

                    $this->output[] = (array) new WP_Block_Parser_Block($block_name, $attrs, [], '', []);
                    $this->offset = $start_offset + $token_length;

                    return true;
                }

                    // otherwise we found an inner block.
                    $this->add_inner_block(new WP_Block_Parser_Block($block_name, $attrs, [], '', []), $start_offset, $token_length);
                    $this->offset = $start_offset + $token_length;

                    return true;

                case 'block-opener':
                    // track all newly-opened blocks on the stack.
                    $this->stack[] = new WP_Block_Parser_Frame(new WP_Block_Parser_Block($block_name, $attrs, [], '', []), $start_offset, $token_length, $start_offset + $token_length, $leading_html_start);
                    $this->offset = $start_offset + $token_length;

                    return true;

                case 'block-closer':
                    /*
                     * if we're missing an opener we're in trouble
                     * This is an error
                     */ if(0 === $stack_depth)
                {
                    /*
                     * we have options
                     * - assume an implicit opener
                     * - assume _this_ is the opener
                     * - give up and close out the document
                     */
                    $this->add_freeform();

                    return false;
                }

                    // if we're not nesting then this is easy - close the block.
                    if(1 === $stack_depth)
                    {
                        $this->add_block_from_stack($start_offset);
                        $this->offset = $start_offset + $token_length;

                        return true;
                    }

                    /*
                     * otherwise we're nested and we have to close out the current
                     * block and add it as a new innerBlock to the parent
                     */
                    $stack_top = array_pop($this->stack);
                    $html = substr($this->document, $stack_top->prev_offset, $start_offset - $stack_top->prev_offset);
                    $stack_top->block->innerHTML .= $html;
                    $stack_top->block->innerContent[] = $html;
                    $stack_top->prev_offset = $start_offset + $token_length;

                    $this->add_inner_block($stack_top->block, $stack_top->token_start, $stack_top->token_length, $start_offset + $token_length);
                    $this->offset = $start_offset + $token_length;

                    return true;

                default:
                    // This is an error.
                    $this->add_freeform();

                    return false;
            }
        }

        public function next_token()
        {
            $matches = null;

            /*
             * aye the magic
             * we're using a single RegExp to tokenize the block comment delimiters
             * we're also using a trick here because the only difference between a
             * block opener and a block closer is the leading `/` before `wp:` (and
             * a closer has no attributes). we can trap them both and process the
             * match back in PHP to see which one it was.
             */
            $has_match = preg_match('/<!--\s+(?P<closer>\/)?wp:(?P<namespace>[a-z][a-z0-9_-]*\/)?(?P<name>[a-z][a-z0-9_-]*)\s+(?P<attrs>{(?:(?:[^}]+|}+(?=})|(?!}\s+\/?-->).)*+)?}\s+)?(?P<void>\/)?-->/s', $this->document, $matches, PREG_OFFSET_CAPTURE, $this->offset);

            // if we get here we probably have catastrophic backtracking or out-of-memory in the PCRE.
            // we have no more tokens.
            if(false === $has_match || 0 === $has_match)
            {
                return ['no-more-tokens', null, null, null, null];
            }

            [$match, $started_at] = $matches[0];

            $length = strlen($match);
            $is_closer = isset($matches['closer']) && -1 !== $matches['closer'][1];
            $is_void = isset($matches['void']) && -1 !== $matches['void'][1];
            $namespace = $matches['namespace'];
            $namespace = (isset($namespace) && -1 !== $namespace[1]) ? $namespace[0] : 'core/';
            $name = $namespace.$matches['name'][0];
            $has_attrs = isset($matches['attrs']) && -1 !== $matches['attrs'][1];

            /*
             * Fun fact! It's not trivial in PHP to create "an empty associative array" since all arrays
             * are associative arrays. If we use `array()` we get a JSON `[]`
             */
            $attrs = $has_attrs ? json_decode($matches['attrs'][0], /* as-associative */ true) : $this->empty_attrs;

            /*
             * This state isn't allowed
             * This is an error
             */
            if($is_closer && ($is_void || $has_attrs))
            {
                // we can ignore them since they don't hurt anything.
            }

            if($is_void)
            {
                return ['void-block', $name, $attrs, $started_at, $length];
            }

            if($is_closer)
            {
                return ['block-closer', $name, null, $started_at, $length];
            }

            return ['block-opener', $name, $attrs, $started_at, $length];
        }

        public function add_freeform($length = null)
        {
            $length = $length ? $length : strlen($this->document) - $this->offset;

            if(0 === $length)
            {
                return;
            }

            $this->output[] = (array) $this->freeform(substr($this->document, $this->offset, $length));
        }

        public function freeform($inner_html)
        {
            return new WP_Block_Parser_Block(null, $this->empty_attrs, [], $inner_html, [$inner_html]);
        }

        public function add_block_from_stack($end_offset = null)
        {
            $stack_top = array_pop($this->stack);
            $prev_offset = $stack_top->prev_offset;

            $html = isset($end_offset) ? substr($this->document, $prev_offset, $end_offset - $prev_offset) : substr($this->document, $prev_offset);

            if(! empty($html))
            {
                $stack_top->block->innerHTML .= $html;
                $stack_top->block->innerContent[] = $html;
            }

            if(isset($stack_top->leading_html_start))
            {
                $this->output[] = (array) $this->freeform(substr($this->document, $stack_top->leading_html_start, $stack_top->token_start - $stack_top->leading_html_start));
            }

            $this->output[] = (array) $stack_top->block;
        }

        public function add_inner_block(WP_Block_Parser_Block $block, $token_start, $token_length, $last_offset = null)
        {
            $parent = $this->stack[count($this->stack) - 1];
            $parent->block->innerBlocks[] = (array) $block;
            $html = substr($this->document, $parent->prev_offset, $token_start - $parent->prev_offset);

            if(! empty($html))
            {
                $parent->block->innerHTML .= $html;
                $parent->block->innerContent[] = $html;
            }

            $parent->block->innerContent[] = null;
            $parent->prev_offset = $last_offset ? $last_offset : $token_start + $token_length;
        }
    }

    require_once __DIR__.'/class-wp-block-parser-block.php';

    require_once __DIR__.'/class-wp-block-parser-frame.php';
