<?php

    class WP_HTML_Processor extends WP_HTML_Tag_Processor
    {
        const MAX_BOOKMARKS = 100;

        const VISIT_EVERYTHING = ['tag_closers' => 'visit'];

        const PROCESS_NEXT_NODE = 'process-next-node';

        const REPROCESS_CURRENT_NODE = 'reprocess-current-node';

        const ERROR_UNSUPPORTED = 'unsupported';

        const ERROR_EXCEEDED_MAX_BOOKMARKS = 'exceeded-max-bookmarks';

        /*
         * Public Interface Functions
         */

        const CONSTRUCTOR_UNLOCK_CODE = 'Use WP_HTML_Processor::createFragment instead of calling the class constructor directly.';

        private $state = null;

        private $bookmark_counter = 0;

        private $last_error = null;

        private $release_internal_bookmark_on_destruct = null;

        public function __construct($html, $use_the_static_create_methods_instead = null)
        {
            parent::__construct($html);

            if(self::CONSTRUCTOR_UNLOCK_CODE !== $use_the_static_create_methods_instead)
            {
                _doing_it_wrong(__METHOD__, sprintf(/* translators: %s: WP_HTML_Processor::createFragment. */ __('Call %s to create an HTML Processor instead of calling the constructor directly.'), '<code>WP_HTML_Processor::createFragment</code>'), '6.4.0');
            }

            $this->state = new WP_HTML_Processor_State();

            /*
             * Create this wrapper so that it's possible to pass
             * a private method into WP_HTML_Token classes without
             * exposing it to any public API.
             */
            $this->release_internal_bookmark_on_destruct = function($name)
            {
                parent::release_bookmark($name);
            };
        }

        public function release_bookmark($bookmark_name)
        {
            return parent::release_bookmark("_{$bookmark_name}");
        }

        /*
         * Internal helpers
         */

        public static function createFragment($html, $context = '<body>', $encoding = 'UTF-8')
        {
            if('<body>' !== $context || 'UTF-8' !== $encoding)
            {
                return null;
            }

            $p = new self($html, self::CONSTRUCTOR_UNLOCK_CODE);
            $p->state->context_node = ['BODY', []];
            $p->state->insertion_mode = WP_HTML_Processor_State::INSERTION_MODE_IN_BODY;

            // @TODO: Create "fake" bookmarks for non-existent but implied nodes.
            $p->bookmarks['root-node'] = new WP_HTML_Span(0, 0);
            $p->bookmarks['context-node'] = new WP_HTML_Span(0, 0);

            $p->state->stack_of_open_elements->push(new WP_HTML_Token('root-node', 'HTML', false));

            $p->state->stack_of_open_elements->push(new WP_HTML_Token('context-node', $p->state->context_node[0], false));

            return $p;
        }

        /*
         * HTML semantic overrides for Tag Processor
         */

        public function get_last_error()
        {
            return $this->last_error;
        }

        public function next_tag($query = null)
        {
            if(null === $query)
            {
                while($this->step())
                {
                    if(! $this->is_tag_closer())
                    {
                        return true;
                    }
                }

                return false;
            }

            if(is_string($query))
            {
                $query = ['breadcrumbs' => [$query]];
            }

            if(! is_array($query))
            {
                _doing_it_wrong(__METHOD__, __('Please pass a query array to this function.'), '6.4.0');

                return false;
            }

            if(! (array_key_exists('breadcrumbs', $query) && is_array($query['breadcrumbs'])))
            {
                while($this->step())
                {
                    if(! $this->is_tag_closer())
                    {
                        return true;
                    }
                }

                return false;
            }

            if(isset($query['tag_closers']) && 'visit' === $query['tag_closers'])
            {
                _doing_it_wrong(__METHOD__, __('Cannot visit tag closers in HTML Processor.'), '6.4.0');

                return false;
            }

            $breadcrumbs = $query['breadcrumbs'];
            $match_offset = isset($query['match_offset']) ? (int) $query['match_offset'] : 1;

            $crumb = end($breadcrumbs);
            $target = strtoupper($crumb);
            while($match_offset > 0 && $this->step())
            {
                if($target !== $this->get_tag())
                {
                    continue;
                }

                // Look up the stack to see if the breadcrumbs match.
                foreach($this->state->stack_of_open_elements->walk_up() as $node)
                {
                    if(strtoupper($crumb) !== $node->node_name)
                    {
                        break;
                    }

                    $crumb = prev($breadcrumbs);
                    if(false === $crumb && 0 === --$match_offset && ! $this->is_tag_closer())
                    {
                        return true;
                    }
                }

                $crumb = end($breadcrumbs);
            }

            return false;
        }

        public function step($node_to_process = self::PROCESS_NEXT_NODE)
        {
            // Refuse to proceed if there was a previous error.
            if(null !== $this->last_error)
            {
                return false;
            }

            if(self::PROCESS_NEXT_NODE === $node_to_process)
            {
                /*
                 * Void elements still hop onto the stack of open elements even though
                 * there's no corresponding closing tag. This is important for managing
                 * stack-based operations such as "navigate to parent node" or checking
                 * on an element's breadcrumbs.
                 *
                 * When moving on to the next node, therefore, if the bottom-most element
                 * on the stack is a void element, it must be closed.
                 *
                 * @TODO: Once self-closing foreign elements and BGSOUND are supported,
                 *        they must also be implicitly closed here too. BGSOUND is
                 *        special since it's only self-closing if the self-closing flag
                 *        is provided in the opening tag, otherwise it expects a tag closer.
                 */
                $top_node = $this->state->stack_of_open_elements->current_node();
                if($top_node && self::is_void($top_node->node_name))
                {
                    $this->state->stack_of_open_elements->pop();
                }

                parent::next_tag(self::VISIT_EVERYTHING);
            }

            // Finish stepping when there are no more tokens in the document.
            if(null === $this->get_tag())
            {
                return false;
            }

            $this->state->current_token = new WP_HTML_Token($this->bookmark_tag(), $this->get_tag(), $this->is_tag_closer(), $this->release_internal_bookmark_on_destruct);

            try
            {
                switch($this->state->insertion_mode)
                {
                    case WP_HTML_Processor_State::INSERTION_MODE_IN_BODY:
                        return $this->step_in_body();

                    default:
                        $this->last_error = self::ERROR_UNSUPPORTED;
                        throw new WP_HTML_Unsupported_Exception("No support for parsing in the '{$this->state->insertion_mode}' state.");
                }
            }
            catch(WP_HTML_Unsupported_Exception $e)
            {
                /*
                 * Exceptions are used in this class to escape deep call stacks that
                 * otherwise might involve messier calling and return conventions.
                 */
                return false;
            }
        }

        public static function is_void($tag_name)
        {
            $tag_name = strtoupper($tag_name);

            return ('AREA' === $tag_name || 'BASE' === $tag_name || 'BR' === $tag_name || 'COL' === $tag_name || 'EMBED' === $tag_name || 'HR' === $tag_name || 'IMG' === $tag_name || 'INPUT' === $tag_name || 'LINK' === $tag_name || 'META' === $tag_name || 'SOURCE' === $tag_name || 'TRACK' === $tag_name || 'WBR' === $tag_name);
        }

        /*
         * HTML Parsing Algorithms
         */

        public function get_tag()
        {
            if(null !== $this->last_error)
            {
                return null;
            }

            $tag_name = parent::get_tag();

            switch($tag_name)
            {
                case 'IMAGE':
                    /*
                     * > A start tag whose tag name is "image"
                     * > Change the token's tag name to "img" and reprocess it. (Don't ask.)
                     */ return 'IMG';

                default:
                    return $tag_name;
            }
        }

        private function bookmark_tag()
        {
            if(! $this->get_tag())
            {
                return false;
            }

            if(! parent::set_bookmark(++$this->bookmark_counter))
            {
                $this->last_error = self::ERROR_EXCEEDED_MAX_BOOKMARKS;
                throw new Exception('could not allocate bookmark');
            }

            return "{$this->bookmark_counter}";
        }

        public function set_bookmark($bookmark_name)
        {
            return parent::set_bookmark("_{$bookmark_name}");
        }

        private function step_in_body()
        {
            $tag_name = $this->get_tag();
            $op_sigil = $this->is_tag_closer() ? '-' : '+';
            $op = "{$op_sigil}{$tag_name}";

            switch($op)
            {
                /*
                 * > A start tag whose tag name is "button"
                 */ case '+BUTTON':
                if($this->state->stack_of_open_elements->has_element_in_scope('BUTTON'))
                {
                    // @TODO: Indicate a parse error once it's possible. This error does not impact the logic here.
                    $this->generate_implied_end_tags();
                    $this->state->stack_of_open_elements->pop_until('BUTTON');
                }

                $this->reconstruct_active_formatting_elements();
                $this->insert_html_element($this->state->current_token);
                $this->state->frameset_ok = false;

                return true;

                /*
                 * > A start tag whose tag name is one of: "address", "article", "aside",
                 * > "blockquote", "center", "details", "dialog", "dir", "div", "dl",
                 * > "fieldset", "figcaption", "figure", "footer", "header", "hgroup",
                 * > "main", "menu", "nav", "ol", "p", "search", "section", "summary", "ul"
                 */ case '+BLOCKQUOTE':
                case '+DIV':
                case '+FIGCAPTION':
                case '+FIGURE':
                case '+P':
                    if($this->state->stack_of_open_elements->has_p_in_button_scope())
                    {
                        $this->close_a_p_element();
                    }

                    $this->insert_html_element($this->state->current_token);

                    return true;

                /*
                 * > An end tag whose tag name is one of: "address", "article", "aside", "blockquote",
                 * > "button", "center", "details", "dialog", "dir", "div", "dl", "fieldset",
                 * > "figcaption", "figure", "footer", "header", "hgroup", "listing", "main",
                 * > "menu", "nav", "ol", "pre", "search", "section", "summary", "ul"
                 */ case '-BLOCKQUOTE':
                case '-BUTTON':
                case '-DIV':
                case '-FIGCAPTION':
                case '-FIGURE':
                    if(! $this->state->stack_of_open_elements->has_element_in_scope($tag_name))
                    {
                        // @TODO: Report parse error.
                        // Ignore the token.
                        return $this->step();
                    }

                    $this->generate_implied_end_tags();
                    if($this->state->stack_of_open_elements->current_node()->node_name !== $tag_name)
                    {
                        // @TODO: Record parse error: this error doesn't impact parsing.
                    }
                    $this->state->stack_of_open_elements->pop_until($tag_name);

                    return true;

                /*
                 * > An end tag whose tag name is "p"
                 */ case '-P':
                if(! $this->state->stack_of_open_elements->has_p_in_button_scope())
                {
                    $this->insert_html_element($this->state->current_token);
                }

                $this->close_a_p_element();

                return true;

                // > A start tag whose tag name is "a"
                case '+A':
                    foreach($this->state->active_formatting_elements->walk_up() as $item)
                    {
                        switch($item->node_name)
                        {
                            case 'marker':
                                break;

                            case 'A':
                                $this->run_adoption_agency_algorithm();
                                $this->state->active_formatting_elements->remove_node($item);
                                $this->state->stack_of_open_elements->remove_node($item);
                                break;
                        }
                    }

                    $this->reconstruct_active_formatting_elements();
                    $this->insert_html_element($this->state->current_token);
                    $this->state->active_formatting_elements->push($this->state->current_token);

                    return true;

                /*
                 * > A start tag whose tag name is one of: "b", "big", "code", "em", "font", "i",
                 * > "s", "small", "strike", "strong", "tt", "u"
                 */ case '+B':
                case '+BIG':
                case '+CODE':
                case '+EM':
                case '+FONT':
                case '+I':
                case '+S':
                case '+SMALL':
                case '+STRIKE':
                case '+STRONG':
                case '+TT':
                case '+U':
                    $this->reconstruct_active_formatting_elements();
                    $this->insert_html_element($this->state->current_token);
                    $this->state->active_formatting_elements->push($this->state->current_token);

                    return true;

                /*
                 * > An end tag whose tag name is one of: "a", "b", "big", "code", "em", "font", "i",
                 * > "nobr", "s", "small", "strike", "strong", "tt", "u"
                 */ case '-A':
                case '-B':
                case '-BIG':
                case '-CODE':
                case '-EM':
                case '-FONT':
                case '-I':
                case '-S':
                case '-SMALL':
                case '-STRIKE':
                case '-STRONG':
                case '-TT':
                case '-U':
                    $this->run_adoption_agency_algorithm();

                    return true;

                /*
                 * > A start tag whose tag name is one of: "area", "br", "embed", "img", "keygen", "wbr"
                 */ case '+IMG':
                $this->reconstruct_active_formatting_elements();
                $this->insert_html_element($this->state->current_token);

                return true;

                /*
                 * > Any other start tag
                 */ case '+SPAN':
                $this->reconstruct_active_formatting_elements();
                $this->insert_html_element($this->state->current_token);

                return true;

                /*
                 * Any other end tag
                 */ case '-SPAN':
                foreach($this->state->stack_of_open_elements->walk_up() as $item)
                {
                    // > If node is an HTML element with the same tag name as the token, then:
                    if($item->node_name === $tag_name)
                    {
                        $this->generate_implied_end_tags($tag_name);

                        // > If node is not the current node, then this is a parse error.

                        $this->state->stack_of_open_elements->pop_until($tag_name);

                        return true;
                    }

                    // > Otherwise, if node is in the special category, then this is a parse error; ignore the token, and return.
                    if(self::is_special($item->node_name))
                    {
                        return $this->step();
                    }
                }

                // Execution should not reach here; if it does then something went wrong.
                return false;

                default:
                    $this->last_error = self::ERROR_UNSUPPORTED;
                    throw new WP_HTML_Unsupported_Exception("Cannot process {$tag_name} element.");
            }
        }

        private function generate_implied_end_tags($except_for_this_element = null)
        {
            $elements_with_implied_end_tags = [
                'P',
            ];

            $current_node = $this->state->stack_of_open_elements->current_node();
            while($current_node && $current_node->node_name !== $except_for_this_element && in_array($this->state->stack_of_open_elements->current_node(), $elements_with_implied_end_tags, true))
            {
                $this->state->stack_of_open_elements->pop();
            }
        }

        private function reconstruct_active_formatting_elements()
        {
            /*
             * > If there are no entries in the list of active formatting elements, then there is nothing
             * > to reconstruct; stop this algorithm.
             */
            if(0 === $this->state->active_formatting_elements->count())
            {
                return false;
            }

            $last_entry = $this->state->active_formatting_elements->current_node();
            if(

                /*
                 * > If the last (most recently added) entry in the list of active formatting elements is a marker;
                 * > stop this algorithm.
                 */ 'marker' === $last_entry->node_name ||

                /*
                 * > If the last (most recently added) entry in the list of active formatting elements is an
                 * > element that is in the stack of open elements, then there is nothing to reconstruct;
                 * > stop this algorithm.
                 */ $this->state->stack_of_open_elements->contains_node($last_entry)
            )
            {
                return false;
            }

            $this->last_error = self::ERROR_UNSUPPORTED;
            throw new WP_HTML_Unsupported_Exception('Cannot reconstruct active formatting elements when advancing and rewinding is required.');
        }

        /*
         * HTML Specification Helpers
         */

        private function insert_html_element($token)
        {
            $this->state->stack_of_open_elements->push($token);
        }

        private function close_a_p_element()
        {
            $this->generate_implied_end_tags('P');
            $this->state->stack_of_open_elements->pop_until('P');
        }

        /*
         * Constants that would pollute the top of the class if they were found there.
         */

        private function run_adoption_agency_algorithm()
        {
            $budget = 1000;
            $subject = $this->get_tag();
            $current_node = $this->state->stack_of_open_elements->current_node();

            if(// > If the current node is an HTML element whose tag name is subject
                $current_node && $subject === $current_node->node_name && // > the current node is not in the list of active formatting elements
                ! $this->state->active_formatting_elements->contains_node($current_node)
            )
            {
                $this->state->stack_of_open_elements->pop();

                return;
            }

            $outer_loop_counter = 0;
            while($budget-- > 0)
            {
                if($outer_loop_counter++ >= 8)
                {
                    return;
                }

                /*
                 * > Let formatting element be the last element in the list of active formatting elements that:
                 * >   - is between the end of the list and the last marker in the list,
                 * >     if any, or the start of the list otherwise,
                 * >   - and has the tag name subject.
                 */
                $formatting_element = null;
                foreach($this->state->active_formatting_elements->walk_up() as $item)
                {
                    if('marker' === $item->node_name)
                    {
                        break;
                    }

                    if($subject === $item->node_name)
                    {
                        $formatting_element = $item;
                        break;
                    }
                }

                // > If there is no such element, then return and instead act as described in the "any other end tag" entry above.
                if(null === $formatting_element)
                {
                    $this->last_error = self::ERROR_UNSUPPORTED;
                    throw new WP_HTML_Unsupported_Exception('Cannot run adoption agency when "any other end tag" is required.');
                }

                // > If formatting element is not in the stack of open elements, then this is a parse error; remove the element from the list, and return.
                if(! $this->state->stack_of_open_elements->contains_node($formatting_element))
                {
                    $this->state->active_formatting_elements->remove_node($formatting_element->bookmark_name);

                    return;
                }

                // > If formatting element is in the stack of open elements, but the element is not in scope, then this is a parse error; return.
                if(! $this->state->stack_of_open_elements->has_element_in_scope($formatting_element->node_name))
                {
                    return;
                }

                /*
                 * > Let furthest block be the topmost node in the stack of open elements that is lower in the stack
                 * > than formatting element, and is an element in the special category. There might not be one.
                 */
                $is_above_formatting_element = true;
                $furthest_block = null;
                foreach($this->state->stack_of_open_elements->walk_down() as $item)
                {
                    if($is_above_formatting_element && $formatting_element->bookmark_name !== $item->bookmark_name)
                    {
                        continue;
                    }

                    if($is_above_formatting_element)
                    {
                        $is_above_formatting_element = false;
                        continue;
                    }

                    if(self::is_special($item->node_name))
                    {
                        $furthest_block = $item;
                        break;
                    }
                }

                /*
                 * > If there is no furthest block, then the UA must first pop all the nodes from the bottom of the
                 * > stack of open elements, from the current node up to and including formatting element, then
                 * > remove formatting element from the list of active formatting elements, and finally return.
                 */
                if(null === $furthest_block)
                {
                    foreach($this->state->stack_of_open_elements->walk_up() as $item)
                    {
                        $this->state->stack_of_open_elements->pop();

                        if($formatting_element->bookmark_name === $item->bookmark_name)
                        {
                            $this->state->active_formatting_elements->remove_node($formatting_element);

                            return;
                        }
                    }
                }

                $this->last_error = self::ERROR_UNSUPPORTED;
                throw new WP_HTML_Unsupported_Exception('Cannot extract common ancestor in adoption agency algorithm.');
            }

            $this->last_error = self::ERROR_UNSUPPORTED;
            throw new WP_HTML_Unsupported_Exception('Cannot run adoption agency when looping required.');
        }

        public static function is_special($tag_name)
        {
            $tag_name = strtoupper($tag_name);

            return ('ADDRESS' === $tag_name || 'APPLET' === $tag_name || 'AREA' === $tag_name || 'ARTICLE' === $tag_name || 'ASIDE' === $tag_name || 'BASE' === $tag_name || 'BASEFONT' === $tag_name || 'BGSOUND' === $tag_name || 'BLOCKQUOTE' === $tag_name || 'BODY' === $tag_name || 'BR' === $tag_name || 'BUTTON' === $tag_name || 'CAPTION' === $tag_name || 'CENTER' === $tag_name || 'COL' === $tag_name || 'COLGROUP' === $tag_name || 'DD' === $tag_name || 'DETAILS' === $tag_name || 'DIR' === $tag_name || 'DIV' === $tag_name || 'DL' === $tag_name || 'DT' === $tag_name || 'EMBED' === $tag_name || 'FIELDSET' === $tag_name || 'FIGCAPTION' === $tag_name || 'FIGURE' === $tag_name || 'FOOTER' === $tag_name || 'FORM' === $tag_name || 'FRAME' === $tag_name || 'FRAMESET' === $tag_name || 'H1' === $tag_name || 'H2' === $tag_name || 'H3' === $tag_name || 'H4' === $tag_name || 'H5' === $tag_name || 'H6' === $tag_name || 'HEAD' === $tag_name || 'HEADER' === $tag_name || 'HGROUP' === $tag_name || 'HR' === $tag_name || 'HTML' === $tag_name || 'IFRAME' === $tag_name || 'IMG' === $tag_name || 'INPUT' === $tag_name || 'KEYGEN' === $tag_name || 'LI' === $tag_name || 'LINK' === $tag_name || 'LISTING' === $tag_name || 'MAIN' === $tag_name || 'MARQUEE' === $tag_name || 'MENU' === $tag_name || 'META' === $tag_name || 'NAV' === $tag_name || 'NOEMBED' === $tag_name || 'NOFRAMES' === $tag_name || 'NOSCRIPT' === $tag_name || 'OBJECT' === $tag_name || 'OL' === $tag_name || 'P' === $tag_name || 'PARAM' === $tag_name || 'PLAINTEXT' === $tag_name || 'PRE' === $tag_name || 'SCRIPT' === $tag_name || 'SEARCH' === $tag_name || 'SECTION' === $tag_name || 'SELECT' === $tag_name || 'SOURCE' === $tag_name || 'STYLE' === $tag_name || 'SUMMARY' === $tag_name || 'TABLE' === $tag_name || 'TBODY' === $tag_name || 'TD' === $tag_name || 'TEMPLATE' === $tag_name || 'TEXTAREA' === $tag_name || 'TFOOT' === $tag_name || 'TH' === $tag_name || 'THEAD' === $tag_name || 'TITLE' === $tag_name || 'TR' === $tag_name || 'TRACK' === $tag_name || 'UL' === $tag_name || 'WBR' === $tag_name || 'XMP' === $tag_name ||

                // MathML.
                'MI' === $tag_name || 'MO' === $tag_name || 'MN' === $tag_name || 'MS' === $tag_name || 'MTEXT' === $tag_name || 'ANNOTATION-XML' === $tag_name ||

                // SVG.
                'FOREIGNOBJECT' === $tag_name || 'DESC' === $tag_name || 'TITLE' === $tag_name);
        }

        public function get_breadcrumbs()
        {
            if(! $this->get_tag())
            {
                return null;
            }

            $breadcrumbs = [];
            foreach($this->state->stack_of_open_elements->walk_down() as $stack_item)
            {
                $breadcrumbs[] = $stack_item->node_name;
            }

            return $breadcrumbs;
        }

        public function seek($bookmark_name)
        {
            $actual_bookmark_name = "_{$bookmark_name}";
            $processor_started_at = $this->state->current_token ? $this->bookmarks[$this->state->current_token->bookmark_name]->start : 0;
            $bookmark_starts_at = $this->bookmarks[$actual_bookmark_name]->start;
            $direction = $bookmark_starts_at > $processor_started_at ? 'forward' : 'backward';

            switch($direction)
            {
                case 'forward':
                    // When moving forwards, re-parse the document until reaching the same location as the original bookmark.
                    while($this->step())
                    {
                        if($bookmark_starts_at === $this->bookmarks[$this->state->current_token->bookmark_name]->start)
                        {
                            return true;
                        }
                    }

                    return false;

                case 'backward':
                    /*
                     * When moving backwards, clear out all existing stack entries which appear after the destination
                     * bookmark. These could be stored for later retrieval, but doing so would require additional
                     * memory overhead and also demand that references and bookmarks are updated as the document
                     * changes. In time this could be a valuable optimization, but it's okay to give up that
                     * optimization in exchange for more CPU time to recompute the stack, to re-parse the
                     * document that may have already been parsed once.
                     */ foreach($this->state->stack_of_open_elements->walk_up() as $item)
                {
                    if($bookmark_starts_at >= $this->bookmarks[$item->bookmark_name]->start)
                    {
                        break;
                    }

                    $this->state->stack_of_open_elements->remove_node($item);
                }

                    foreach($this->state->active_formatting_elements->walk_up() as $item)
                    {
                        if($bookmark_starts_at >= $this->bookmarks[$item->bookmark_name]->start)
                        {
                            break;
                        }

                        $this->state->active_formatting_elements->remove_node($item);
                    }

                    return parent::seek($actual_bookmark_name);
            }
        }

        private function generate_implied_end_tags_thoroughly()
        {
            $elements_with_implied_end_tags = [
                'P',
            ];

            while(in_array($this->state->stack_of_open_elements->current_node(), $elements_with_implied_end_tags, true))
            {
                $this->state->stack_of_open_elements->pop();
            }
        }
    }
