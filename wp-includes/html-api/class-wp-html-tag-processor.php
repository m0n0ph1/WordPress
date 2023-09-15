<?php

    class WP_HTML_Tag_Processor
    {
        const MAX_BOOKMARKS = 10;

        const MAX_SEEK_OPS = 1000;

        const ADD_CLASS = true;

        const REMOVE_CLASS = false;

        const SKIP_CLASS = null;

        protected $html;

        protected $bookmarks = [];

        protected $lexical_updates = [];

        protected $seek_count = 0;

        private $last_query;

        private $sought_tag_name;

        private $sought_class_name;

        private $sought_match_offset;

        private $stop_on_tag_closers;

        private $bytes_already_parsed = 0;

        private $tag_name_starts_at;

        private $tag_name_length;

        private $tag_ends_at;

        private $is_closing_tag;

        private $attributes = [];

        private $classname_updates = [];

        public function __construct($html)
        {
            $this->html = $html;
        }

        private static function sort_start_ascending($a, $b)
        {
            $by_start = $a->start - $b->start;
            if(0 !== $by_start)
            {
                return $by_start;
            }

            $by_text = isset($a->text, $b->text) ? strcmp($a->text, $b->text) : 0;
            if(0 !== $by_text)
            {
                return $by_text;
            }

            /*
             * This code should be unreachable, because it implies the two replacements
             * start at the same location and contain the same text.
             */

            return $a->end - $b->end;
        }

        public function set_bookmark($name)
        {
            if(null === $this->tag_name_starts_at)
            {
                return false;
            }

            if(! array_key_exists($name, $this->bookmarks) && count($this->bookmarks) >= static::MAX_BOOKMARKS)
            {
                _doing_it_wrong(__METHOD__, __('Too many bookmarks: cannot create any more.'), '6.2.0');

                return false;
            }

            $this->bookmarks[$name] = new WP_HTML_Span($this->tag_name_starts_at - ($this->is_closing_tag ? 2 : 1), $this->tag_ends_at);

            return true;
        }

        public function has_bookmark($bookmark_name)
        {
            return array_key_exists($bookmark_name, $this->bookmarks);
        }

        public function seek($bookmark_name)
        {
            if(! array_key_exists($bookmark_name, $this->bookmarks))
            {
                _doing_it_wrong(__METHOD__, __('Unknown bookmark name.'), '6.2.0');

                return false;
            }

            if(++$this->seek_count > static::MAX_SEEK_OPS)
            {
                _doing_it_wrong(__METHOD__, __('Too many calls to seek() - this can lead to performance issues.'), '6.2.0');

                return false;
            }

            // Flush out any pending updates to the document.
            $this->get_updated_html();

            // Point this tag processor before the sought tag opener and consume it.
            $this->bytes_already_parsed = $this->bookmarks[$bookmark_name]->start;

            return $this->next_tag(['tag_closers' => 'visit']);
        }

        public function get_updated_html()
        {
            $requires_no_updating = 0 === count($this->classname_updates) && 0 === count($this->lexical_updates);

            /*
             * When there is nothing more to update and nothing has already been
             * updated, return the original document and avoid a string copy.
             */
            if($requires_no_updating)
            {
                return $this->html;
            }

            /*
             * Keep track of the position right before the current tag. This will
             * be necessary for reparsing the current tag after updating the HTML.
             */
            $before_current_tag = $this->tag_name_starts_at - 1;

            /*
             * 1. Apply the enqueued edits and update all the pointers to reflect those changes.
             */
            $this->class_name_updates_to_attributes_updates();
            $before_current_tag += $this->apply_attributes_updates($before_current_tag);

            /*
             * 2. Rewind to before the current tag and reparse to get updated attributes.
             *
             * At this point the internal cursor points to the end of the tag name.
             * Rewind before the tag name starts so that it's as if the cursor didn't
             * move; a call to `next_tag()` will reparse the recently-updated attributes
             * and additional calls to modify the attributes will apply at this same
             * location.
             *
             * <p>Previous HTML<em>More HTML</em></p>
             *                 ^  | back up by the length of the tag name plus the opening <
             *                 \<-/ back up by strlen("em") + 1 ==> 3
             */

            // Store existing state so it can be restored after reparsing.
            $previous_parsed_byte_count = $this->bytes_already_parsed;
            $previous_query = $this->last_query;

            // Reparse attributes.
            $this->bytes_already_parsed = $before_current_tag;
            $this->next_tag();

            // Restore previous state.
            $this->bytes_already_parsed = $previous_parsed_byte_count;
            $this->parse_query($previous_query);

            return $this->html;
        }

        private function class_name_updates_to_attributes_updates()
        {
            if(count($this->classname_updates) === 0)
            {
                return;
            }

            $existing_class = $this->get_enqueued_attribute_value('class');
            if(null === $existing_class || true === $existing_class)
            {
                $existing_class = '';
            }

            if(false === $existing_class && isset($this->attributes['class']))
            {
                $existing_class = substr($this->html, $this->attributes['class']->value_starts_at, $this->attributes['class']->value_length);
            }

            if(false === $existing_class)
            {
                $existing_class = '';
            }

            $class = '';

            $at = 0;

            $modified = false;

            // Remove unwanted classes by only copying the new ones.
            $existing_class_length = strlen($existing_class);
            while($at < $existing_class_length)
            {
                // Skip to the first non-whitespace character.
                $ws_at = $at;
                $ws_length = strspn($existing_class, " \t\f\r\n", $ws_at);
                $at += $ws_length;

                // Capture the class name – it's everything until the next whitespace.
                $name_length = strcspn($existing_class, " \t\f\r\n", $at);
                if(0 === $name_length)
                {
                    // If no more class names are found then that's the end.
                    break;
                }

                $name = substr($existing_class, $at, $name_length);
                $at += $name_length;

                // If this class is marked for removal, start processing the next one.
                $remove_class = (isset($this->classname_updates[$name]) && self::REMOVE_CLASS === $this->classname_updates[$name]);

                // If a class has already been seen then skip it; it should not be added twice.
                if(! $remove_class)
                {
                    $this->classname_updates[$name] = self::SKIP_CLASS;
                }

                if($remove_class)
                {
                    $modified = true;
                    continue;
                }

                /*
                 * Otherwise, append it to the new "class" attribute value.
                 *
                 * There are options for handling whitespace between tags.
                 * Preserving the existing whitespace produces fewer changes
                 * to the HTML content and should clarify the before/after
                 * content when debugging the modified output.
                 *
                 * This approach contrasts normalizing the inter-class
                 * whitespace to a single space, which might appear cleaner
                 * in the output HTML but produce a noisier change.
                 */
                $class .= substr($existing_class, $ws_at, $ws_length);
                $class .= $name;
            }

            // Add new classes by appending those which haven't already been seen.
            foreach($this->classname_updates as $name => $operation)
            {
                if(self::ADD_CLASS === $operation)
                {
                    $modified = true;

                    $class .= strlen($class) > 0 ? ' ' : '';
                    $class .= $name;
                }
            }

            $this->classname_updates = [];
            if(! $modified)
            {
                return;
            }

            if(strlen($class) > 0)
            {
                $this->set_attribute('class', $class);
            }
            else
            {
                $this->remove_attribute('class');
            }
        }

        private function get_enqueued_attribute_value($comparable_name)
        {
            if(! isset($this->lexical_updates[$comparable_name]))
            {
                return false;
            }

            $enqueued_text = $this->lexical_updates[$comparable_name]->text;

            // Removed attributes erase the entire span.
            if('' === $enqueued_text)
            {
                return null;
            }

            /*
             * Boolean attribute updates are just the attribute name without a corresponding value.
             *
             * This value might differ from the given comparable name in that there could be leading
             * or trailing whitespace, and that the casing follows the name given in `set_attribute`.
             *
             * Example:
             *
             *     $p->set_attribute( 'data-TEST-id', 'update' );
             *     'update' === $p->get_enqueued_attribute_value( 'data-test-id' );
             *
             * Detect this difference based on the absence of the `=`, which _must_ exist in any
             * attribute containing a value, e.g. `<input type="text" enabled />`.
             *                                            ¹           ²
             *                                       1. Attribute with a string value.
             *                                       2. Boolean attribute whose value is `true`.
             */
            $equals_at = strpos($enqueued_text, '=');
            if(false === $equals_at)
            {
                return true;
            }

            /*
             * Finally, a normal update's value will appear after the `=` and
             * be double-quoted, as performed incidentally by `set_attribute`.
             *
             * e.g. `type="text"`
             *           ¹²    ³
             *        1. Equals is here.
             *        2. Double-quoting starts one after the equals sign.
             *        3. Double-quoting ends at the last character in the update.
             */
            $enqueued_value = substr($enqueued_text, $equals_at + 2, -1);

            return html_entity_decode($enqueued_value);
        }

        public function set_attribute($name, $value)
        {
            if($this->is_closing_tag || null === $this->tag_name_starts_at)
            {
                return false;
            }

            /*
             * WordPress rejects more characters than are strictly forbidden
             * in HTML5. This is to prevent additional security risks deeper
             * in the WordPress and plugin stack. Specifically the
             * less-than (<) greater-than (>) and ampersand (&) aren't allowed.
             *
             * The use of a PCRE match enables looking for specific Unicode
             * code points without writing a UTF-8 decoder. Whereas scanning
             * for one-byte characters is trivial (with `strcspn`), scanning
             * for the longer byte sequences would be more complicated. Given
             * that this shouldn't be in the hot path for execution, it's a
             * reasonable compromise in efficiency without introducing a
             * noticeable impact on the overall system.
             *
             * @see https://html.spec.whatwg.org/#attributes-2
             *
             * @TODO as the only regex pattern maybe we should take it out? are
             *       Unicode patterns available broadly in Core?
             */
            if(
                preg_match(
                    '~['.// Syntax-like characters.
                    '"\'>&</ ='.// Control characters.
                    '\x{00}-\x{1F}'.// HTML noncharacters.
                    '\x{FDD0}-\x{FDEF}'.'\x{FFFE}\x{FFFF}\x{1FFFE}\x{1FFFF}\x{2FFFE}\x{2FFFF}\x{3FFFE}\x{3FFFF}'.'\x{4FFFE}\x{4FFFF}\x{5FFFE}\x{5FFFF}\x{6FFFE}\x{6FFFF}\x{7FFFE}\x{7FFFF}'.'\x{8FFFE}\x{8FFFF}\x{9FFFE}\x{9FFFF}\x{AFFFE}\x{AFFFF}\x{BFFFE}\x{BFFFF}'.'\x{CFFFE}\x{CFFFF}\x{DFFFE}\x{DFFFF}\x{EFFFE}\x{EFFFF}\x{FFFFE}\x{FFFFF}'.'\x{10FFFE}\x{10FFFF}'.']~Ssu', $name
                )
            )
            {
                _doing_it_wrong(__METHOD__, __('Invalid attribute name.'), '6.2.0');

                return false;
            }

            /*
             * > The values "true" and "false" are not allowed on boolean attributes.
             * > To represent a false value, the attribute has to be omitted altogether.
             *     - HTML5 spec, https://html.spec.whatwg.org/#boolean-attributes
             */
            if(false === $value)
            {
                return $this->remove_attribute($name);
            }

            if(true === $value)
            {
                $updated_attribute = $name;
            }
            else
            {
                $escaped_new_value = esc_attr($value);
                $updated_attribute = "{$name}=\"{$escaped_new_value}\"";
            }

            /*
             * > There must never be two or more attributes on
             * > the same start tag whose names are an ASCII
             * > case-insensitive match for each other.
             *     - HTML 5 spec
             *
             * @see https://html.spec.whatwg.org/multipage/syntax.html#attributes-2:ascii-case-insensitive
             */
            $comparable_name = strtolower($name);

            if(isset($this->attributes[$comparable_name]))
            {
                /*
                 * Update an existing attribute.
                 *
                 * Example – set attribute id to "new" in <div id="initial_id" />:
                 *
                 *     <div id="initial_id"/>
                 *          ^-------------^
                 *          start         end
                 *     replacement: `id="new"`
                 *
                 *     Result: <div id="new"/>
                 */
                $existing_attribute = $this->attributes[$comparable_name];
                $this->lexical_updates[$comparable_name] = new WP_HTML_Text_Replacement($existing_attribute->start, $existing_attribute->end, $updated_attribute);
            }
            else
            {
                /*
                 * Create a new attribute at the tag's name end.
                 *
                 * Example – add attribute id="new" to <div />:
                 *
                 *     <div/>
                 *         ^
                 *         start and end
                 *     replacement: ` id="new"`
                 *
                 *     Result: <div id="new"/>
                 */
                $this->lexical_updates[$comparable_name] = new WP_HTML_Text_Replacement($this->tag_name_starts_at + $this->tag_name_length, $this->tag_name_starts_at + $this->tag_name_length, ' '.$updated_attribute);
            }

            /*
             * Any calls to update the `class` attribute directly should wipe out any
             * enqueued class changes from `add_class` and `remove_class`.
             */
            if('class' === $comparable_name && ! empty($this->classname_updates))
            {
                $this->classname_updates = [];
            }

            return true;
        }

        public function remove_attribute($name)
        {
            if($this->is_closing_tag)
            {
                return false;
            }

            /*
             * > There must never be two or more attributes on
             * > the same start tag whose names are an ASCII
             * > case-insensitive match for each other.
             *     - HTML 5 spec
             *
             * @see https://html.spec.whatwg.org/multipage/syntax.html#attributes-2:ascii-case-insensitive
             */
            $name = strtolower($name);

            /*
             * Any calls to update the `class` attribute directly should wipe out any
             * enqueued class changes from `add_class` and `remove_class`.
             */
            if('class' === $name && count($this->classname_updates) !== 0)
            {
                $this->classname_updates = [];
            }

            /*
             * If updating an attribute that didn't exist in the input
             * document, then remove the enqueued update and move on.
             *
             * For example, this might occur when calling `remove_attribute()`
             * after calling `set_attribute()` for the same attribute
             * and when that attribute wasn't originally present.
             */
            if(! isset($this->attributes[$name]))
            {
                if(isset($this->lexical_updates[$name]))
                {
                    unset($this->lexical_updates[$name]);
                }

                return false;
            }

            /*
             * Removes an existing tag attribute.
             *
             * Example – remove the attribute id from <div id="main"/>:
             *    <div id="initial_id"/>
             *         ^-------------^
             *         start         end
             *    replacement: ``
             *
             *    Result: <div />
             */
            $this->lexical_updates[$name] = new WP_HTML_Text_Replacement($this->attributes[$name]->start, $this->attributes[$name]->end, '');

            return true;
        }

        private function apply_attributes_updates($shift_this_point = 0)
        {
            if(! count($this->lexical_updates))
            {
                return 0;
            }

            $accumulated_shift_for_given_point = 0;

            /*
             * Attribute updates can be enqueued in any order but updates
             * to the document must occur in lexical order; that is, each
             * replacement must be made before all others which follow it
             * at later string indices in the input document.
             *
             * Sorting avoid making out-of-order replacements which
             * can lead to mangled output, partially-duplicated
             * attributes, and overwritten attributes.
             */
            usort($this->lexical_updates, [self::class, 'sort_start_ascending']);

            $bytes_already_copied = 0;
            $output_buffer = '';
            foreach($this->lexical_updates as $diff)
            {
                $shift = strlen($diff->text) - ($diff->end - $diff->start);

                // Adjust the cursor position by however much an update affects it.
                if($diff->start <= $this->bytes_already_parsed)
                {
                    $this->bytes_already_parsed += $shift;
                }

                // Accumulate shift of the given pointer within this function call.
                if($diff->start <= $shift_this_point)
                {
                    $accumulated_shift_for_given_point += $shift;
                }

                $output_buffer .= substr($this->html, $bytes_already_copied, $diff->start - $bytes_already_copied);
                $output_buffer .= $diff->text;
                $bytes_already_copied = $diff->end;
            }

            $this->html = $output_buffer.substr($this->html, $bytes_already_copied);

            /*
             * Adjust bookmark locations to account for how the text
             * replacements adjust offsets in the input document.
             */
            foreach($this->bookmarks as $bookmark_name => $bookmark)
            {
                /*
                 * Each lexical update which appears before the bookmark's endpoints
                 * might shift the offsets for those endpoints. Loop through each change
                 * and accumulate the total shift for each bookmark, then apply that
                 * shift after tallying the full delta.
                 */
                $head_delta = 0;
                $tail_delta = 0;

                foreach($this->lexical_updates as $diff)
                {
                    if($bookmark->start < $diff->start && $bookmark->end < $diff->start)
                    {
                        break;
                    }

                    if($bookmark->start >= $diff->start && $bookmark->end < $diff->end)
                    {
                        $this->release_bookmark($bookmark_name);
                        continue 2;
                    }

                    $delta = strlen($diff->text) - ($diff->end - $diff->start);

                    if($bookmark->start >= $diff->start)
                    {
                        $head_delta += $delta;
                    }

                    if($bookmark->end >= $diff->end)
                    {
                        $tail_delta += $delta;
                    }
                }

                $bookmark->start += $head_delta;
                $bookmark->end += $tail_delta;
            }

            $this->lexical_updates = [];

            return $accumulated_shift_for_given_point;
        }

        public function release_bookmark($name)
        {
            if(! array_key_exists($name, $this->bookmarks))
            {
                return false;
            }

            unset($this->bookmarks[$name]);

            return true;
        }

        public function next_tag($query = null)
        {
            $this->parse_query($query);
            $already_found = 0;

            do
            {
                if($this->bytes_already_parsed >= strlen($this->html))
                {
                    return false;
                }

                // Find the next tag if it exists.
                if(false === $this->parse_next_tag())
                {
                    $this->bytes_already_parsed = strlen($this->html);

                    return false;
                }

                // Parse all of its attributes.
                while($this->parse_next_attribute())
                {
                    continue;
                }

                // Ensure that the tag closes before the end of the document.
                if($this->bytes_already_parsed >= strlen($this->html))
                {
                    return false;
                }

                $tag_ends_at = strpos($this->html, '>', $this->bytes_already_parsed);
                if(false === $tag_ends_at)
                {
                    return false;
                }
                $this->tag_ends_at = $tag_ends_at;
                $this->bytes_already_parsed = $tag_ends_at;

                // Finally, check if the parsed tag and its attributes match the search query.
                if($this->matches())
                {
                    ++$already_found;
                }

                /*
                 * For non-DATA sections which might contain text that looks like HTML tags but
                 * isn't, scan with the appropriate alternative mode. Looking at the first letter
                 * of the tag name as a pre-check avoids a string allocation when it's not needed.
                 */
                $t = $this->html[$this->tag_name_starts_at];
                if(! $this->is_closing_tag && ('i' === $t || 'I' === $t || 'n' === $t || 'N' === $t || 's' === $t || 'S' === $t || 't' === $t || 'T' === $t))
                {
                    $tag_name = $this->get_tag();

                    if('SCRIPT' === $tag_name && ! $this->skip_script_data())
                    {
                        $this->bytes_already_parsed = strlen($this->html);

                        return false;
                    }
                    elseif(('TEXTAREA' === $tag_name || 'TITLE' === $tag_name) && ! $this->skip_rcdata($tag_name))
                    {
                        $this->bytes_already_parsed = strlen($this->html);

                        return false;
                    }
                    elseif(('IFRAME' === $tag_name || 'NOEMBED' === $tag_name || 'NOFRAMES' === $tag_name || 'NOSCRIPT' === $tag_name || 'STYLE' === $tag_name) && ! $this->skip_rawtext($tag_name))
                    {
                        /*
                         * "XMP" should be here too but its rules are more complicated and require the
                         * complexity of the HTML Processor (it needs to close out any open P element,
                         * meaning it can't be skipped here or else the HTML Processor will lose its
                         * place). For now, it can be ignored as it's a rare HTML tag in practice and
                         * any normative HTML should be using PRE instead.
                         */
                        $this->bytes_already_parsed = strlen($this->html);

                        return false;
                    }
                }
            }
            while($already_found < $this->sought_match_offset);

            return true;
        }

        private function parse_query($query)
        {
            if(null !== $query && $query === $this->last_query)
            {
                return;
            }

            $this->last_query = $query;
            $this->sought_tag_name = null;
            $this->sought_class_name = null;
            $this->sought_match_offset = 1;
            $this->stop_on_tag_closers = false;

            // A single string value means "find the tag of this name".
            if(is_string($query))
            {
                $this->sought_tag_name = $query;

                return;
            }

            // An empty query parameter applies no restrictions on the search.
            if(null === $query)
            {
                return;
            }

            // If not using the string interface, an associative array is required.
            if(! is_array($query))
            {
                _doing_it_wrong(__METHOD__, __('The query argument must be an array or a tag name.'), '6.2.0');

                return;
            }

            if(isset($query['tag_name']) && is_string($query['tag_name']))
            {
                $this->sought_tag_name = $query['tag_name'];
            }

            if(isset($query['class_name']) && is_string($query['class_name']))
            {
                $this->sought_class_name = $query['class_name'];
            }

            if(isset($query['match_offset']) && is_int($query['match_offset']) && 0 < $query['match_offset'])
            {
                $this->sought_match_offset = $query['match_offset'];
            }

            if(isset($query['tag_closers']))
            {
                $this->stop_on_tag_closers = 'visit' === $query['tag_closers'];
            }
        }

        private function parse_next_tag()
        {
            $this->after_tag();

            $html = $this->html;
            $doc_length = strlen($html);
            $at = $this->bytes_already_parsed;

            while(false !== $at && $at < $doc_length)
            {
                $at = strpos($html, '<', $at);
                if(false === $at)
                {
                    return false;
                }

                if('/' === $this->html[$at + 1])
                {
                    $this->is_closing_tag = true;
                    ++$at;
                }
                else
                {
                    $this->is_closing_tag = false;
                }

                /*
                 * HTML tag names must start with [a-zA-Z] otherwise they are not tags.
                 * For example, "<3" is rendered as text, not a tag opener. If at least
                 * one letter follows the "<" then _it is_ a tag, but if the following
                 * character is anything else it _is not a tag_.
                 *
                 * It's not uncommon to find non-tags starting with `<` in an HTML
                 * document, so it's good for performance to make this pre-check before
                 * continuing to attempt to parse a tag name.
                 *
                 * Reference:
                 * * https://html.spec.whatwg.org/multipage/parsing.html#data-state
                 * * https://html.spec.whatwg.org/multipage/parsing.html#tag-open-state
                 */
                $tag_name_prefix_length = strspn($html, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', $at + 1);
                if($tag_name_prefix_length > 0)
                {
                    ++$at;
                    $this->tag_name_length = $tag_name_prefix_length + strcspn($html, " \t\f\r\n/>", $at + $tag_name_prefix_length);
                    $this->tag_name_starts_at = $at;
                    $this->bytes_already_parsed = $at + $this->tag_name_length;

                    return true;
                }

                /*
                 * Abort if no tag is found before the end of
                 * the document. There is nothing left to parse.
                 */
                if($at + 1 >= strlen($html))
                {
                    return false;
                }

                /*
                 * <! transitions to markup declaration open state
                 * https://html.spec.whatwg.org/multipage/parsing.html#markup-declaration-open-state
                 */
                if('!' === $html[$at + 1])
                {
                    /*
                     * <!-- transitions to a bogus comment state – skip to the nearest -->
                     * https://html.spec.whatwg.org/multipage/parsing.html#tag-open-state
                     */
                    if(strlen($html) > $at + 3 && '-' === $html[$at + 2] && '-' === $html[$at + 3])
                    {
                        $closer_at = $at + 4;
                        // If it's not possible to close the comment then there is nothing more to scan.
                        if(strlen($html) <= $closer_at)
                        {
                            return false;
                        }

                        // Abruptly-closed empty comments are a sequence of dashes followed by `>`.
                        $span_of_dashes = strspn($html, '-', $closer_at);
                        if('>' === $html[$closer_at + $span_of_dashes])
                        {
                            $at = $closer_at + $span_of_dashes + 1;
                            continue;
                        }

                        /*
                         * Comments may be closed by either a --> or an invalid --!>.
                         * The first occurrence closes the comment.
                         *
                         * See https://html.spec.whatwg.org/#parse-error-incorrectly-closed-comment
                         */
                        --$closer_at; // Pre-increment inside condition below reduces risk of accidental infinite looping.
                        while(++$closer_at < strlen($html))
                        {
                            $closer_at = strpos($html, '--', $closer_at);
                            if(false === $closer_at)
                            {
                                return false;
                            }

                            if($closer_at + 2 < strlen($html) && '>' === $html[$closer_at + 2])
                            {
                                $at = $closer_at + 3;
                                continue 2;
                            }

                            if($closer_at + 3 < strlen($html) && '!' === $html[$closer_at + 2] && '>' === $html[$closer_at + 3])
                            {
                                $at = $closer_at + 4;
                                continue 2;
                            }
                        }
                    }

                    /*
                     * <![CDATA[ transitions to CDATA section state – skip to the nearest ]]>
                     * The CDATA is case-sensitive.
                     * https://html.spec.whatwg.org/multipage/parsing.html#tag-open-state
                     */
                    if(strlen($html) > $at + 8 && '[' === $html[$at + 2] && 'C' === $html[$at + 3] && 'D' === $html[$at + 4] && 'A' === $html[$at + 5] && 'T' === $html[$at + 6] && 'A' === $html[$at + 7] && '[' === $html[$at + 8])
                    {
                        $closer_at = strpos($html, ']]>', $at + 9);
                        if(false === $closer_at)
                        {
                            return false;
                        }

                        $at = $closer_at + 3;
                        continue;
                    }

                    /*
                     * <!DOCTYPE transitions to DOCTYPE state – skip to the nearest >
                     * These are ASCII-case-insensitive.
                     * https://html.spec.whatwg.org/multipage/parsing.html#tag-open-state
                     */
                    if(strlen($html) > $at + 8 && ('D' === $html[$at + 2] || 'd' === $html[$at + 2]) && ('O' === $html[$at + 3] || 'o' === $html[$at + 3]) && ('C' === $html[$at + 4] || 'c' === $html[$at + 4]) && ('T' === $html[$at + 5] || 't' === $html[$at + 5]) && ('Y' === $html[$at + 6] || 'y' === $html[$at + 6]) && ('P' === $html[$at + 7] || 'p' === $html[$at + 7]) && ('E' === $html[$at + 8] || 'e' === $html[$at + 8]))
                    {
                        $closer_at = strpos($html, '>', $at + 9);
                        if(false === $closer_at)
                        {
                            return false;
                        }

                        $at = $closer_at + 1;
                        continue;
                    }

                    /*
                     * Anything else here is an incorrectly-opened comment and transitions
                     * to the bogus comment state - skip to the nearest >.
                     */
                    $at = strpos($html, '>', $at + 1);
                    continue;
                }

                /*
                 * </> is a missing end tag name, which is ignored.
                 *
                 * See https://html.spec.whatwg.org/#parse-error-missing-end-tag-name
                 */
                if('>' === $html[$at + 1])
                {
                    ++$at;
                    continue;
                }

                /*
                 * <? transitions to a bogus comment state – skip to the nearest >
                 * See https://html.spec.whatwg.org/multipage/parsing.html#tag-open-state
                 */
                if('?' === $html[$at + 1])
                {
                    $closer_at = strpos($html, '>', $at + 2);
                    if(false === $closer_at)
                    {
                        return false;
                    }

                    $at = $closer_at + 1;
                    continue;
                }

                /*
                 * If a non-alpha starts the tag name in a tag closer it's a comment.
                 * Find the first `>`, which closes the comment.
                 *
                 * See https://html.spec.whatwg.org/#parse-error-invalid-first-character-of-tag-name
                 */
                if($this->is_closing_tag)
                {
                    $closer_at = strpos($html, '>', $at + 3);
                    if(false === $closer_at)
                    {
                        return false;
                    }

                    $at = $closer_at + 1;
                    continue;
                }

                ++$at;
            }

            return false;
        }

        private function after_tag()
        {
            $this->get_updated_html();
            $this->tag_name_starts_at = null;
            $this->tag_name_length = null;
            $this->tag_ends_at = null;
            $this->is_closing_tag = null;
            $this->attributes = [];
        }

        private function parse_next_attribute()
        {
            // Skip whitespace and slashes.
            $this->bytes_already_parsed += strspn($this->html, " \t\f\r\n/", $this->bytes_already_parsed);
            if($this->bytes_already_parsed >= strlen($this->html))
            {
                return false;
            }

            /*
             * Treat the equal sign as a part of the attribute
             * name if it is the first encountered byte.
             *
             * @see https://html.spec.whatwg.org/multipage/parsing.html#before-attribute-name-state
             */
            $name_length = '=' === $this->html[$this->bytes_already_parsed] ? 1 + strcspn($this->html, "=/> \t\f\r\n", $this->bytes_already_parsed + 1) : strcspn($this->html, "=/> \t\f\r\n", $this->bytes_already_parsed);

            // No attribute, just tag closer.
            if(0 === $name_length || $this->bytes_already_parsed + $name_length >= strlen($this->html))
            {
                return false;
            }

            $attribute_start = $this->bytes_already_parsed;
            $attribute_name = substr($this->html, $attribute_start, $name_length);
            $this->bytes_already_parsed += $name_length;
            if($this->bytes_already_parsed >= strlen($this->html))
            {
                return false;
            }

            $this->skip_whitespace();
            if($this->bytes_already_parsed >= strlen($this->html))
            {
                return false;
            }

            $has_value = '=' === $this->html[$this->bytes_already_parsed];
            if($has_value)
            {
                ++$this->bytes_already_parsed;
                $this->skip_whitespace();
                if($this->bytes_already_parsed >= strlen($this->html))
                {
                    return false;
                }

                switch($this->html[$this->bytes_already_parsed])
                {
                    case "'":
                    case '"':
                        $quote = $this->html[$this->bytes_already_parsed];
                        $value_start = $this->bytes_already_parsed + 1;
                        $value_length = strcspn($this->html, $quote, $value_start);
                        $attribute_end = $value_start + $value_length + 1;
                        $this->bytes_already_parsed = $attribute_end;
                        break;

                    default:
                        $value_start = $this->bytes_already_parsed;
                        $value_length = strcspn($this->html, "> \t\f\r\n", $value_start);
                        $attribute_end = $value_start + $value_length;
                        $this->bytes_already_parsed = $attribute_end;
                }
            }
            else
            {
                $value_start = $this->bytes_already_parsed;
                $value_length = 0;
                $attribute_end = $attribute_start + $name_length;
            }

            if($attribute_end >= strlen($this->html))
            {
                return false;
            }

            if($this->is_closing_tag)
            {
                return true;
            }

            /*
             * > There must never be two or more attributes on
             * > the same start tag whose names are an ASCII
             * > case-insensitive match for each other.
             *     - HTML 5 spec
             *
             * @see https://html.spec.whatwg.org/multipage/syntax.html#attributes-2:ascii-case-insensitive
             */
            $comparable_name = strtolower($attribute_name);

            // If an attribute is listed many times, only use the first declaration and ignore the rest.
            if(! array_key_exists($comparable_name, $this->attributes))
            {
                $this->attributes[$comparable_name] = new WP_HTML_Attribute_Token($attribute_name, $value_start, $value_length, $attribute_start, $attribute_end, ! $has_value);
            }

            return true;
        }

        private function skip_whitespace()
        {
            $this->bytes_already_parsed += strspn($this->html, " \t\f\r\n", $this->bytes_already_parsed);
        }

        private function matches()
        {
            if($this->is_closing_tag && ! $this->stop_on_tag_closers)
            {
                return false;
            }

            // Does the tag name match the requested tag name in a case-insensitive manner?
            if(null !== $this->sought_tag_name)
            {
                /*
                 * String (byte) length lookup is fast. If they aren't the
                 * same length then they can't be the same string values.
                 */
                if(strlen($this->sought_tag_name) !== $this->tag_name_length)
                {
                    return false;
                }

                /*
                 * Check each character to determine if they are the same.
                 * Defer calls to `strtoupper()` to avoid them when possible.
                 * Calling `strcasecmp()` here tested slowed than comparing each
                 * character, so unless benchmarks show otherwise, it should
                 * not be used.
                 *
                 * It's expected that most of the time that this runs, a
                 * lower-case tag name will be supplied and the input will
                 * contain lower-case tag names, thus normally bypassing
                 * the case comparison code.
                 */
                for($i = 0; $i < $this->tag_name_length; $i++)
                {
                    $html_char = $this->html[$this->tag_name_starts_at + $i];
                    $tag_char = $this->sought_tag_name[$i];

                    if($html_char !== $tag_char && strtoupper($html_char) !== $tag_char)
                    {
                        return false;
                    }
                }
            }

            $needs_class_name = null !== $this->sought_class_name;

            if($needs_class_name && ! isset($this->attributes['class']))
            {
                return false;
            }

            /*
             * Match byte-for-byte (case-sensitive and encoding-form-sensitive) on the class name.
             *
             * This will overlook certain classes that exist in other lexical variations
             * than was supplied to the search query, but requires more complicated searching.
             */
            if($needs_class_name)
            {
                $class_start = $this->attributes['class']->value_starts_at;
                $class_end = $class_start + $this->attributes['class']->value_length;
                $class_at = $class_start;

                /*
                 * Ensure that boundaries surround the class name to avoid matching on
                 * substrings of a longer name. For example, the sequence "not-odd"
                 * should not match for the class "odd" even though "odd" is found
                 * within the class attribute text.
                 *
                 * See https://html.spec.whatwg.org/#attributes-3
                 * See https://html.spec.whatwg.org/#space-separated-tokens
                 */
                while(// phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
                    false !== ($class_at = strpos($this->html, $this->sought_class_name, $class_at)) && $class_at < $class_end)
                {
                    /*
                     * Verify this class starts at a boundary.
                     */
                    if($class_at > $class_start)
                    {
                        $character = $this->html[$class_at - 1];

                        if(' ' !== $character && "\t" !== $character && "\f" !== $character && "\r" !== $character && "\n" !== $character)
                        {
                            $class_at += strlen($this->sought_class_name);
                            continue;
                        }
                    }

                    /*
                     * Verify this class ends at a boundary as well.
                     */
                    if($class_at + strlen($this->sought_class_name) < $class_end)
                    {
                        $character = $this->html[$class_at + strlen($this->sought_class_name)];

                        if(' ' !== $character && "\t" !== $character && "\f" !== $character && "\r" !== $character && "\n" !== $character)
                        {
                            $class_at += strlen($this->sought_class_name);
                            continue;
                        }
                    }

                    return true;
                }

                return false;
            }

            return true;
        }

        public function get_tag()
        {
            if(null === $this->tag_name_starts_at)
            {
                return null;
            }

            $tag_name = substr($this->html, $this->tag_name_starts_at, $this->tag_name_length);

            return strtoupper($tag_name);
        }

        private function skip_script_data()
        {
            $state = 'unescaped';
            $html = $this->html;
            $doc_length = strlen($html);
            $at = $this->bytes_already_parsed;

            while(false !== $at && $at < $doc_length)
            {
                $at += strcspn($html, '-<', $at);

                /*
                 * For all script states a "-->"  transitions
                 * back into the normal unescaped script mode,
                 * even if that's the current state.
                 */
                if($at + 2 < $doc_length && '-' === $html[$at] && '-' === $html[$at + 1] && '>' === $html[$at + 2])
                {
                    $at += 3;
                    $state = 'unescaped';
                    continue;
                }

                // Everything of interest past here starts with "<".
                if($at + 1 >= $doc_length || '<' !== $html[$at++])
                {
                    continue;
                }

                /*
                 * Unlike with "-->", the "<!--" only transitions
                 * into the escaped mode if not already there.
                 *
                 * Inside the escaped modes it will be ignored; and
                 * should never break out of the double-escaped
                 * mode and back into the escaped mode.
                 *
                 * While this requires a mode change, it does not
                 * impact the parsing otherwise, so continue
                 * parsing after updating the state.
                 */
                if($at + 2 < $doc_length && '!' === $html[$at] && '-' === $html[$at + 1] && '-' === $html[$at + 2])
                {
                    $at += 3;
                    $state = 'unescaped' === $state ? 'escaped' : $state;
                    continue;
                }

                if('/' === $html[$at])
                {
                    $closer_potentially_starts_at = $at - 1;
                    $is_closing = true;
                    ++$at;
                }
                else
                {
                    $is_closing = false;
                }

                /*
                 * At this point the only remaining state-changes occur with the
                 * <script> and </script> tags; unless one of these appears next,
                 * proceed scanning to the next potential token in the text.
                 */
                if(! ($at + 6 < $doc_length && ('s' === $html[$at] || 'S' === $html[$at]) && ('c' === $html[$at + 1] || 'C' === $html[$at + 1]) && ('r' === $html[$at + 2] || 'R' === $html[$at + 2]) && ('i' === $html[$at + 3] || 'I' === $html[$at + 3]) && ('p' === $html[$at + 4] || 'P' === $html[$at + 4]) && ('t' === $html[$at + 5] || 'T' === $html[$at + 5])))
                {
                    ++$at;
                    continue;
                }

                /*
                 * Ensure that the script tag terminates to avoid matching on
                 * substrings of a non-match. For example, the sequence
                 * "<script123" should not end a script region even though
                 * "<script" is found within the text.
                 */
                if($at + 6 >= $doc_length)
                {
                    continue;
                }
                $at += 6;
                $c = $html[$at];
                if(' ' !== $c && "\t" !== $c && "\r" !== $c && "\n" !== $c && '/' !== $c && '>' !== $c)
                {
                    ++$at;
                    continue;
                }

                if('escaped' === $state && ! $is_closing)
                {
                    $state = 'double-escaped';
                    continue;
                }

                if('double-escaped' === $state && $is_closing)
                {
                    $state = 'escaped';
                    continue;
                }

                if($is_closing)
                {
                    $this->bytes_already_parsed = $closer_potentially_starts_at;
                    if($this->bytes_already_parsed >= $doc_length)
                    {
                        return false;
                    }

                    while($this->parse_next_attribute())
                    {
                        continue;
                    }

                    if('>' === $html[$this->bytes_already_parsed])
                    {
                        $this->bytes_already_parsed = $closer_potentially_starts_at;

                        return true;
                    }
                }

                ++$at;
            }

            return false;
        }

        private function skip_rcdata($tag_name)
        {
            $html = $this->html;
            $doc_length = strlen($html);
            $tag_length = strlen($tag_name);

            $at = $this->bytes_already_parsed;

            while(false !== $at && $at < $doc_length)
            {
                $at = strpos($this->html, '</', $at);

                // If there is no possible tag closer then fail.
                if(false === $at || ($at + $tag_length) >= $doc_length)
                {
                    $this->bytes_already_parsed = $doc_length;

                    return false;
                }

                $closer_potentially_starts_at = $at;
                $at += 2;

                /*
                 * Find a case-insensitive match to the tag name.
                 *
                 * Because tag names are limited to US-ASCII there is no
                 * need to perform any kind of Unicode normalization when
                 * comparing; any character which could be impacted by such
                 * normalization could not be part of a tag name.
                 */
                for($i = 0; $i < $tag_length; $i++)
                {
                    $tag_char = $tag_name[$i];
                    $html_char = $html[$at + $i];

                    if($html_char !== $tag_char && strtoupper($html_char) !== $tag_char)
                    {
                        $at += $i;
                        continue 2;
                    }
                }

                $at += $tag_length;
                $this->bytes_already_parsed = $at;

                /*
                 * Ensure that the tag name terminates to avoid matching on
                 * substrings of a longer tag name. For example, the sequence
                 * "</textarearug" should not match for "</textarea" even
                 * though "textarea" is found within the text.
                 */
                $c = $html[$at];
                if(' ' !== $c && "\t" !== $c && "\r" !== $c && "\n" !== $c && '/' !== $c && '>' !== $c)
                {
                    continue;
                }

                while($this->parse_next_attribute())
                {
                    continue;
                }
                $at = $this->bytes_already_parsed;
                if($at >= strlen($this->html))
                {
                    return false;
                }

                if('>' === $html[$at] || '/' === $html[$at])
                {
                    $this->bytes_already_parsed = $closer_potentially_starts_at;

                    return true;
                }
            }

            return false;
        }

        private function skip_rawtext($tag_name)
        {
            /*
             * These two functions distinguish themselves on whether character references are
             * decoded, and since functionality to read the inner markup isn't supported, it's
             * not necessary to implement these two functions separately.
             */
            return $this->skip_rcdata($tag_name);
        }

        public function get_attribute($name)
        {
            if(null === $this->tag_name_starts_at)
            {
                return null;
            }

            $comparable = strtolower($name);

            /*
             * For every attribute other than `class` it's possible to perform a quick check if
             * there's an enqueued lexical update whose value takes priority over what's found in
             * the input document.
             *
             * The `class` attribute is special though because of the exposed helpers `add_class`
             * and `remove_class`. These form a builder for the `class` attribute, so an additional
             * check for enqueued class changes is required in addition to the check for any enqueued
             * attribute values. If any exist, those enqueued class changes must first be flushed out
             * into an attribute value update.
             */
            if('class' === $name)
            {
                $this->class_name_updates_to_attributes_updates();
            }

            // Return any enqueued attribute value updates if they exist.
            $enqueued_value = $this->get_enqueued_attribute_value($comparable);
            if(false !== $enqueued_value)
            {
                return $enqueued_value;
            }

            if(! isset($this->attributes[$comparable]))
            {
                return null;
            }

            $attribute = $this->attributes[$comparable];

            /*
             * This flag distinguishes an attribute with no value
             * from an attribute with an empty string value. For
             * unquoted attributes this could look very similar.
             * It refers to whether an `=` follows the name.
             *
             * e.g. <div boolean-attribute empty-attribute=></div>
             *           ¹                 ²
             *        1. Attribute `boolean-attribute` is `true`.
             *        2. Attribute `empty-attribute` is `""`.
             */
            if(true === $attribute->is_true)
            {
                return true;
            }

            $raw_value = substr($this->html, $attribute->value_starts_at, $attribute->value_length);

            return html_entity_decode($raw_value);
        }

        public function get_attribute_names_with_prefix($prefix)
        {
            if($this->is_closing_tag || null === $this->tag_name_starts_at)
            {
                return null;
            }

            $comparable = strtolower($prefix);

            $matches = [];
            foreach(array_keys($this->attributes) as $attr_name)
            {
                if(str_starts_with($attr_name, $comparable))
                {
                    $matches[] = $attr_name;
                }
            }

            return $matches;
        }

        public function has_self_closing_flag()
        {
            if(! $this->tag_name_starts_at)
            {
                return false;
            }

            return '/' === $this->html[$this->tag_ends_at - 1];
        }

        public function is_tag_closer()
        {
            return $this->is_closing_tag;
        }

        public function add_class($class_name)
        {
            if($this->is_closing_tag)
            {
                return false;
            }

            if(null !== $this->tag_name_starts_at)
            {
                $this->classname_updates[$class_name] = self::ADD_CLASS;
            }

            return true;
        }

        public function remove_class($class_name)
        {
            if($this->is_closing_tag)
            {
                return false;
            }

            if(null !== $this->tag_name_starts_at)
            {
                $this->classname_updates[$class_name] = self::REMOVE_CLASS;
            }

            return true;
        }

        public function __toString()
        {
            return $this->get_updated_html();
        }
    }
