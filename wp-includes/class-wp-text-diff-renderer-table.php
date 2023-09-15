<?php

    #[AllowDynamicProperties]
    class WP_Text_Diff_Renderer_Table extends Text_Diff_Renderer
    {
        public $_leading_context_lines = 10000;

        public $_trailing_context_lines = 10000;

        protected $_diff_threshold = 0.6;

        protected $inline_diff_renderer = 'WP_Text_Diff_Renderer_inline';

        protected $_show_split_view = true;

        protected $compat_fields = ['_show_split_view', 'inline_diff_renderer', '_diff_threshold'];

        protected $count_cache = [];

        protected $difference_cache = [];

        public function __construct($params = [])
        {
            parent::__construct($params);
            if(isset($params['show_split_view']))
            {
                $this->_show_split_view = $params['show_split_view'];
            }
        }

        public function _startBlock($header)
        {
            return '';
        }

        public function _lines($lines, $prefix = ' ') {}

        public function _context($lines, $encode = true)
        {
            $r = '';
            foreach($lines as $line)
            {
                if($encode)
                {
                    $processed_line = htmlspecialchars($line);

                    $line = apply_filters('process_text_diff_html', $processed_line, $line, 'unchanged');
                }
                if($this->_show_split_view)
                {
                    $r .= '<tr>'.$this->contextLine($line).$this->contextLine($line)."</tr>\n";
                }
                else
                {
                    $r .= '<tr>'.$this->contextLine($line)."</tr>\n";
                }
            }

            return $r;
        }

        public function contextLine($line)
        {
            return "<td class='diff-context'><span class='screen-reader-text'>"./* translators: Hidden accessibility text. */ __('Unchanged:')." </span>{$line}</td>";
        }

        public function _changed($orig, $final)
        { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.finalFound
            $r = '';

            /*
             * Does the aforementioned additional processing:
             * *_matches tell what rows are "the same" in orig and final. Those pairs will be diffed to get word changes.
             * - match is numeric: an index in other column.
             * - match is 'X': no match. It is a new row.
             * *_rows are column vectors for the orig column and the final column.
             * - row >= 0: an index of the $orig or $final array.
             * - row < 0: a blank row for that column.
             */
            [$orig_matches, $final_matches, $orig_rows, $final_rows] = $this->interleave_changed_lines($orig, $final);

            // These will hold the word changes as determined by an inline diff.
            $orig_diffs = [];
            $final_diffs = [];

            // Compute word diffs for each matched pair using the inline diff.
            foreach($orig_matches as $o => $f)
            {
                if(is_numeric($o) && is_numeric($f))
                {
                    $text_diff = new Text_Diff('auto', [[$orig[$o]], [$final[$f]]]);
                    $renderer = new $this->inline_diff_renderer();
                    $diff = $renderer->render($text_diff);

                    // If they're too different, don't include any <ins> or <del>'s.
                    if(preg_match_all('!(<ins>.*?</ins>|<del>.*?</del>)!', $diff, $diff_matches))
                    {
                        // Length of all text between <ins> or <del>.
                        $stripped_matches = strlen(strip_tags(implode(' ', $diff_matches[0])));
                        /*
                         * Since we count length of text between <ins> or <del> (instead of picking just one),
                         * we double the length of chars not in those tags.
                         */
                        $stripped_diff = strlen(strip_tags($diff)) * 2 - $stripped_matches;
                        $diff_ratio = $stripped_matches / $stripped_diff;
                        if($diff_ratio > $this->_diff_threshold)
                        {
                            continue; // Too different. Don't save diffs.
                        }
                    }

                    // Un-inline the diffs by removing <del> or <ins>.
                    $orig_diffs[$o] = preg_replace('|<ins>.*?</ins>|', '', $diff);
                    $final_diffs[$f] = preg_replace('|<del>.*?</del>|', '', $diff);
                }
            }

            foreach(array_keys($orig_rows) as $row)
            {
                // Both columns have blanks. Ignore them.
                if($orig_rows[$row] < 0 && $final_rows[$row] < 0)
                {
                    continue;
                }

                // If we have a word based diff, use it. Otherwise, use the normal line.
                if(isset($orig_diffs[$orig_rows[$row]]))
                {
                    $orig_line = $orig_diffs[$orig_rows[$row]];
                }
                elseif(isset($orig[$orig_rows[$row]]))
                {
                    $orig_line = htmlspecialchars($orig[$orig_rows[$row]]);
                }
                else
                {
                    $orig_line = '';
                }

                if(isset($final_diffs[$final_rows[$row]]))
                {
                    $final_line = $final_diffs[$final_rows[$row]];
                }
                elseif(isset($final[$final_rows[$row]]))
                {
                    $final_line = htmlspecialchars($final[$final_rows[$row]]);
                }
                else
                {
                    $final_line = '';
                }

                if($orig_rows[$row] < 0)
                { // Orig is blank. This is really an added row.
                    $r .= $this->_added([$final_line], false);
                }
                elseif($final_rows[$row] < 0)
                { // Final is blank. This is really a deleted row.
                    $r .= $this->_deleted([$orig_line], false);
                }
                else
                { // A true changed row.
                    if($this->_show_split_view)
                    {
                        $r .= '<tr>'.$this->deletedLine($orig_line).$this->addedLine($final_line)."</tr>\n";
                    }
                    else
                    {
                        $r .= '<tr>'.$this->deletedLine($orig_line).'</tr><tr>'.$this->addedLine($final_line)."</tr>\n";
                    }
                }
            }

            return $r;
        }

        public function interleave_changed_lines($orig, $final)
        { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.finalFound

            // Contains all pairwise string comparisons. Keys are such that this need only be a one dimensional array.
            $matches = [];
            foreach(array_keys($orig) as $o)
            {
                foreach(array_keys($final) as $f)
                {
                    $matches["$o,$f"] = $this->compute_string_distance($orig[$o], $final[$f]);
                }
            }
            asort($matches); // Order by string distance.

            $orig_matches = [];
            $final_matches = [];

            foreach($matches as $keys => $difference)
            {
                [$o, $f] = explode(',', $keys);
                $o = (int) $o;
                $f = (int) $f;

                // Already have better matches for these guys.
                if(isset($orig_matches[$o]) && isset($final_matches[$f]))
                {
                    continue;
                }

                // First match for these guys. Must be best match.
                if(! isset($orig_matches[$o]) && ! isset($final_matches[$f]))
                {
                    $orig_matches[$o] = $f;
                    $final_matches[$f] = $o;
                    continue;
                }

                // Best match of this final is already taken? Must mean this final is a new row.
                if(isset($orig_matches[$o]))
                {
                    $final_matches[$f] = 'x';
                }
                elseif(isset($final_matches[$f]))
                {
                    // Best match of this orig is already taken? Must mean this orig is a deleted row.
                    $orig_matches[$o] = 'x';
                }
            }

            // We read the text in this order.
            ksort($orig_matches);
            ksort($final_matches);

            // Stores rows and blanks for each column.
            $orig_rows = array_keys($orig_matches);
            $orig_rows_copy = $orig_rows;
            $final_rows = array_keys($final_matches);

            /*
             * Interleaves rows with blanks to keep matches aligned.
             * We may end up with some extraneous blank rows, but we'll just ignore them later.
             */
            foreach($orig_rows_copy as $orig_row)
            {
                $final_pos = array_search($orig_matches[$orig_row], $final_rows, true);
                $orig_pos = (int) array_search($orig_row, $orig_rows, true);

                if(false === $final_pos)
                { // This orig is paired with a blank final.
                    array_splice($final_rows, $orig_pos, 0, -1);
                }
                elseif($final_pos < $orig_pos)
                { // This orig's match is up a ways. Pad final with blank rows.
                    $diff_array = range(-1, $final_pos - $orig_pos);
                    array_splice($final_rows, $orig_pos, 0, $diff_array);
                }
                elseif($final_pos > $orig_pos)
                { // This orig's match is down a ways. Pad orig with blank rows.
                    $diff_array = range(-1, $orig_pos - $final_pos);
                    array_splice($orig_rows, $orig_pos, 0, $diff_array);
                }
            }

            // Pad the ends with blank rows if the columns aren't the same length.
            $diff_count = count($orig_rows) - count($final_rows);
            if($diff_count < 0)
            {
                while($diff_count < 0)
                {
                    array_push($orig_rows, $diff_count++);
                }
            }
            elseif($diff_count > 0)
            {
                $diff_count = -1 * $diff_count;
                while($diff_count < 0)
                {
                    array_push($final_rows, $diff_count++);
                }
            }

            return [$orig_matches, $final_matches, $orig_rows, $final_rows];
        }

        public function compute_string_distance($string1, $string2)
        {
            // Use an md5 hash of the strings for a count cache, as it's fast to generate, and collisions aren't a concern.
            $count_key1 = md5($string1);
            $count_key2 = md5($string2);

            // Cache vectors containing character frequency for all chars in each string.
            if(! isset($this->count_cache[$count_key1]))
            {
                $this->count_cache[$count_key1] = count_chars($string1);
            }
            if(! isset($this->count_cache[$count_key2]))
            {
                $this->count_cache[$count_key2] = count_chars($string2);
            }

            $chars1 = $this->count_cache[$count_key1];
            $chars2 = $this->count_cache[$count_key2];

            $difference_key = md5(implode(',', $chars1).':'.implode(',', $chars2));
            if(! isset($this->difference_cache[$difference_key]))
            {
                // L1-norm of difference vector.
                $this->difference_cache[$difference_key] = array_sum(
                    array_map([
                                  $this,
                                  'difference'
                              ], $chars1, $chars2)
                );
            }

            $difference = $this->difference_cache[$difference_key];

            // $string1 has zero length? Odd. Give huge penalty by not dividing.
            if(! $string1)
            {
                return $difference;
            }

            // Return distance per character (of string1).
            return $difference / strlen($string1);
        }

        public function _added($lines, $encode = true)
        {
            $r = '';
            foreach($lines as $line)
            {
                if($encode)
                {
                    $processed_line = htmlspecialchars($line);

                    $line = apply_filters('process_text_diff_html', $processed_line, $line, 'added');
                }

                if($this->_show_split_view)
                {
                    $r .= '<tr>'.$this->emptyLine().$this->addedLine($line)."</tr>\n";
                }
                else
                {
                    $r .= '<tr>'.$this->addedLine($line)."</tr>\n";
                }
            }

            return $r;
        }

        public function emptyLine()
        {
            return '<td>&nbsp;</td>';
        }

        public function addedLine($line)
        {
            return "<td class='diff-addedline'><span aria-hidden='true' class='dashicons dashicons-plus'></span><span class='screen-reader-text'>"./* translators: Hidden accessibility text. */ __('Added:')." </span>{$line}</td>";
        }

        public function _deleted($lines, $encode = true)
        {
            $r = '';
            foreach($lines as $line)
            {
                if($encode)
                {
                    $processed_line = htmlspecialchars($line);

                    $line = apply_filters('process_text_diff_html', $processed_line, $line, 'deleted');
                }
                if($this->_show_split_view)
                {
                    $r .= '<tr>'.$this->deletedLine($line).$this->emptyLine()."</tr>\n";
                }
                else
                {
                    $r .= '<tr>'.$this->deletedLine($line)."</tr>\n";
                }
            }

            return $r;
        }

        public function deletedLine($line)
        {
            return "<td class='diff-deletedline'><span aria-hidden='true' class='dashicons dashicons-minus'></span><span class='screen-reader-text'>"./* translators: Hidden accessibility text. */ __('Deleted:')." </span>{$line}</td>";
        }

        public function difference($a, $b)
        {
            return abs($a - $b);
        }

        public function __get($name)
        {
            if(in_array($name, $this->compat_fields, true))
            {
                return $this->$name;
            }

            wp_trigger_error(__METHOD__, "The property `{$name}` is not declared. Getting a dynamic property is ".'deprecated since version 6.4.0! Instead, declare the property on the class.', E_USER_DEPRECATED);

            return null;
        }

        public function __set($name, $value)
        {
            if(in_array($name, $this->compat_fields, true))
            {
                $this->$name = $value;

                return;
            }

            wp_trigger_error(__METHOD__, "The property `{$name}` is not declared. Setting a dynamic property is ".'deprecated since version 6.4.0! Instead, declare the property on the class.', E_USER_DEPRECATED);
        }

        public function __isset($name)
        {
            if(in_array($name, $this->compat_fields, true))
            {
                return isset($this->$name);
            }

            wp_trigger_error(__METHOD__, "The property `{$name}` is not declared. Checking `isset()` on a dynamic property ".'is deprecated since version 6.4.0! Instead, declare the property on the class.', E_USER_DEPRECATED);

            return false;
        }

        public function __unset($name)
        {
            if(in_array($name, $this->compat_fields, true))
            {
                unset($this->$name);

                return;
            }

            wp_trigger_error(__METHOD__, "A property `{$name}` is not declared. Unsetting a dynamic property is ".'deprecated since version 6.4.0! Instead, declare the property on the class.', E_USER_DEPRECATED);
        }
    }
