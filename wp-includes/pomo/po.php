<?php

    require_once __DIR__.'/translations.php';

    if(! defined('PO_MAX_LINE_LEN'))
    {
        define('PO_MAX_LINE_LEN', 79);
    }

    /*
     * The `auto_detect_line_endings` setting has been deprecated in PHP 8.1,
     * but will continue to work until PHP 9.0.
     * For now, we're silencing the deprecation notice as there may still be
     * translation files around which haven't been updated in a long time and
     * which still use the old MacOS standalone `\r` as a line ending.
     * This fix should be revisited when PHP 9.0 is in alpha/beta.
     */
    @ini_set('auto_detect_line_endings', 1); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

    if(! class_exists('PO', false)) :
        class PO extends Gettext_Translations
        {
            public $comments_before_headers = '';

            public function export_headers()
            {
                $header_string = '';
                foreach($this->headers as $header => $value)
                {
                    $header_string .= "$header: $value\n";
                }
                $poified = PO::poify($header_string);
                if($this->comments_before_headers)
                {
                    $before_headers = $this->prepend_each_line(rtrim($this->comments_before_headers)."\n", '# ');
                }
                else
                {
                    $before_headers = '';
                }

                return rtrim("{$before_headers}msgid \"\"\nmsgstr $poified");
            }

            public function export_entries()
            {
                // TODO: Sorting.
                return implode("\n\n", array_map(['PO', 'export_entry'], $this->entries));
            }

            public function export($include_headers = true)
            {
                $res = '';
                if($include_headers)
                {
                    $res .= $this->export_headers();
                    $res .= "\n\n";
                }
                $res .= $this->export_entries();

                return $res;
            }

            public function export_to_file($filename, $include_headers = true)
            {
                $fh = fopen($filename, 'w');
                if(false === $fh)
                {
                    return false;
                }
                $export = $this->export($include_headers);
                $res = fwrite($fh, $export);
                if(false === $res)
                {
                    return false;
                }

                return fclose($fh);
            }

            public function set_comment_before_headers($text)
            {
                $this->comments_before_headers = $text;
            }

            public static function poify($input_string)
            {
                $quote = '"';
                $slash = '\\';
                $newline = "\n";

                $replaces = [
                    "$slash" => "$slash$slash",
                    "$quote" => "$slash$quote",
                    "\t" => '\t',
                ];

                $input_string = str_replace(array_keys($replaces), array_values($replaces), $input_string);

                $po = $quote.implode("{$slash}n{$quote}{$newline}{$quote}", explode($newline, $input_string)).$quote;
                // Add empty string on first line for readbility.
                if(str_contains($input_string, $newline) && (substr_count($input_string, $newline) > 1 || substr($input_string, -strlen($newline)) !== $newline))
                {
                    $po = "$quote$quote$newline$po";
                }
                // Remove empty strings.
                $po = str_replace("$newline$quote$quote", '', $po);

                return $po;
            }

            public static function unpoify($input_string)
            {
                $escapes = [
                    't' => "\t",
                    'n' => "\n",
                    'r' => "\r",
                    '\\' => '\\',
                ];
                $lines = array_map('trim', explode("\n", $input_string));
                $lines = array_map(['PO', 'trim_quotes'], $lines);
                $unpoified = '';
                $previous_is_backslash = false;
                foreach($lines as $line)
                {
                    preg_match_all('/./u', $line, $chars);
                    $chars = $chars[0];
                    foreach($chars as $char)
                    {
                        if($previous_is_backslash)
                        {
                            $previous_is_backslash = false;
                            $unpoified .= isset($escapes[$char]) ? $escapes[$char] : $char;
                        }
                        else
                        {
                            if('\\' === $char)
                            {
                                $previous_is_backslash = true;
                            }
                            else
                            {
                                $unpoified .= $char;
                            }
                        }
                    }
                }

                // Standardize the line endings on imported content, technically PO files shouldn't contain \r.
                $unpoified = str_replace(["\r\n", "\r"], "\n", $unpoified);

                return $unpoified;
            }

            public static function prepend_each_line($input_string, $with)
            {
                $lines = explode("\n", $input_string);
                $append = '';
                if("\n" === substr($input_string, -1) && '' === end($lines))
                {
                    /*
                     * Last line might be empty because $input_string was terminated
                     * with a newline, remove it from the $lines array,
                     * we'll restore state by re-terminating the string at the end.
                     */
                    array_pop($lines);
                    $append = "\n";
                }
                foreach($lines as &$line)
                {
                    $line = $with.$line;
                }
                unset($line);

                return implode("\n", $lines).$append;
            }

            public static function comment_block($text, $char = ' ')
            {
                $text = wordwrap($text, PO_MAX_LINE_LEN - 3);

                return PO::prepend_each_line($text, "#$char ");
            }

            public static function export_entry($entry)
            {
                if(null === $entry->singular || '' === $entry->singular)
                {
                    return false;
                }
                $po = [];
                if(! empty($entry->translator_comments))
                {
                    $po[] = PO::comment_block($entry->translator_comments);
                }
                if(! empty($entry->extracted_comments))
                {
                    $po[] = PO::comment_block($entry->extracted_comments, '.');
                }
                if(! empty($entry->references))
                {
                    $po[] = PO::comment_block(implode(' ', $entry->references), ':');
                }
                if(! empty($entry->flags))
                {
                    $po[] = PO::comment_block(implode(', ', $entry->flags), ',');
                }
                if($entry->context)
                {
                    $po[] = 'msgctxt '.PO::poify($entry->context);
                }
                $po[] = 'msgid '.PO::poify($entry->singular);
                if($entry->is_plural)
                {
                    $po[] = 'msgid_plural '.PO::poify($entry->plural);
                    $translations = empty($entry->translations) ? ['', ''] : $entry->translations;
                    foreach($translations as $i => $translation)
                    {
                        $translation = PO::match_begin_and_end_newlines($translation, $entry->plural);
                        $po[] = "msgstr[$i] ".PO::poify($translation);
                    }
                }
                else
                {
                    $translation = empty($entry->translations) ? '' : $entry->translations[0];
                    $translation = PO::match_begin_and_end_newlines($translation, $entry->singular);
                    $po[] = 'msgstr '.PO::poify($translation);
                }

                return implode("\n", $po);
            }

            public static function match_begin_and_end_newlines($translation, $original)
            {
                if('' === $translation)
                {
                    return $translation;
                }

                $original_begin = strpos($original, "\n") === 0;
                $original_end = "\n" === substr($original, -1);
                $translation_begin = strpos($translation, "\n") === 0;
                $translation_end = "\n" === substr($translation, -1);

                if($original_begin)
                {
                    if(! $translation_begin)
                    {
                        $translation = "\n".$translation;
                    }
                }
                elseif($translation_begin)
                {
                    $translation = ltrim($translation, "\n");
                }

                if($original_end)
                {
                    if(! $translation_end)
                    {
                        $translation .= "\n";
                    }
                }
                elseif($translation_end)
                {
                    $translation = rtrim($translation, "\n");
                }

                return $translation;
            }

            public function import_from_file($filename)
            {
                $f = fopen($filename, 'r');
                if(! $f)
                {
                    return false;
                }
                $lineno = 0;
                while(true)
                {
                    $res = $this->read_entry($f, $lineno);
                    if(! $res)
                    {
                        break;
                    }
                    if('' === $res['entry']->singular)
                    {
                        $this->set_headers($this->make_headers($res['entry']->translations[0]));
                    }
                    else
                    {
                        $this->add_entry($res['entry']);
                    }
                }
                $this->read_line($f, 'clear');
                if(false === $res)
                {
                    return false;
                }
                if(! $this->headers && ! $this->entries)
                {
                    return false;
                }

                return true;
            }

            protected static function is_final($context)
            {
                return ('msgstr' === $context) || ('msgstr_plural' === $context);
            }

            public function read_entry($f, $lineno = 0)
            {
                $entry = new Translation_Entry();
                // Where were we in the last step.
                // Can be: comment, msgctxt, msgid, msgid_plural, msgstr, msgstr_plural.
                $context = '';
                $msgstr_index = 0;
                while(true)
                {
                    ++$lineno;
                    $line = $this->read_line($f);
                    if(! $line)
                    {
                        if(feof($f))
                        {
                            if(self::is_final($context))
                            {
                                break;
                            }
                            elseif($context)
                            {
                                return false;
                            }
                            else
                            { // We haven't read a line and EOF came.
                                return null;
                            }
                        }
                        else
                        {
                            return false;
                        }
                    }
                    if("\n" === $line)
                    {
                        continue;
                    }
                    $line = trim($line);
                    if(preg_match('/^#/', $line, $m))
                    {
                        // The comment is the start of a new entry.
                        if(self::is_final($context))
                        {
                            $this->read_line($f, 'put-back');
                            --$lineno;
                            break;
                        }
                        // Comments have to be at the beginning.
                        if($context && 'comment' !== $context)
                        {
                            return false;
                        }
                        // Add comment.
                        $this->add_comment_to_entry($entry, $line);
                    }
                    elseif(preg_match('/^msgctxt\s+(".*")/', $line, $m))
                    {
                        if(self::is_final($context))
                        {
                            $this->read_line($f, 'put-back');
                            --$lineno;
                            break;
                        }
                        if($context && 'comment' !== $context)
                        {
                            return false;
                        }
                        $context = 'msgctxt';
                        $entry->context .= PO::unpoify($m[1]);
                    }
                    elseif(preg_match('/^msgid\s+(".*")/', $line, $m))
                    {
                        if(self::is_final($context))
                        {
                            $this->read_line($f, 'put-back');
                            --$lineno;
                            break;
                        }
                        if($context && 'msgctxt' !== $context && 'comment' !== $context)
                        {
                            return false;
                        }
                        $context = 'msgid';
                        $entry->singular .= PO::unpoify($m[1]);
                    }
                    elseif(preg_match('/^msgid_plural\s+(".*")/', $line, $m))
                    {
                        if('msgid' !== $context)
                        {
                            return false;
                        }
                        $context = 'msgid_plural';
                        $entry->is_plural = true;
                        $entry->plural .= PO::unpoify($m[1]);
                    }
                    elseif(preg_match('/^msgstr\s+(".*")/', $line, $m))
                    {
                        if('msgid' !== $context)
                        {
                            return false;
                        }
                        $context = 'msgstr';
                        $entry->translations = [PO::unpoify($m[1])];
                    }
                    elseif(preg_match('/^msgstr\[(\d+)\]\s+(".*")/', $line, $m))
                    {
                        if('msgid_plural' !== $context && 'msgstr_plural' !== $context)
                        {
                            return false;
                        }
                        $context = 'msgstr_plural';
                        $msgstr_index = $m[1];
                        $entry->translations[$m[1]] = PO::unpoify($m[2]);
                    }
                    elseif(preg_match('/^".*"$/', $line))
                    {
                        $unpoified = PO::unpoify($line);
                        switch($context)
                        {
                            case 'msgid':
                                $entry->singular .= $unpoified;
                                break;
                            case 'msgctxt':
                                $entry->context .= $unpoified;
                                break;
                            case 'msgid_plural':
                                $entry->plural .= $unpoified;
                                break;
                            case 'msgstr':
                                $entry->translations[0] .= $unpoified;
                                break;
                            case 'msgstr_plural':
                                $entry->translations[$msgstr_index] .= $unpoified;
                                break;
                            default:
                                return false;
                        }
                    }
                    else
                    {
                        return false;
                    }
                }

                $have_translations = false;
                foreach($entry->translations as $t)
                {
                    if($t || ('0' === $t))
                    {
                        $have_translations = true;
                        break;
                    }
                }
                if(false === $have_translations)
                {
                    $entry->translations = [];
                }

                return compact('entry', 'lineno');
            }

            public function read_line($f, $action = 'read')
            {
                static $last_line = '';
                static $use_last_line = false;
                if('clear' === $action)
                {
                    $last_line = '';

                    return true;
                }
                if('put-back' === $action)
                {
                    $use_last_line = true;

                    return true;
                }
                $line = $use_last_line ? $last_line : fgets($f);
                $line = ("\r\n" === substr($line, -2)) ? rtrim($line, "\r\n")."\n" : $line;
                $last_line = $line;
                $use_last_line = false;

                return $line;
            }

            public function add_comment_to_entry(&$entry, $po_comment_line)
            {
                $first_two = substr($po_comment_line, 0, 2);
                $comment = trim(substr($po_comment_line, 2));
                if('#:' === $first_two)
                {
                    $entry->references = array_merge($entry->references, preg_split('/\s+/', $comment));
                }
                elseif('#.' === $first_two)
                {
                    $entry->extracted_comments = trim($entry->extracted_comments."\n".$comment);
                }
                elseif('#,' === $first_two)
                {
                    $entry->flags = array_merge($entry->flags, preg_split('/,\s*/', $comment));
                }
                else
                {
                    $entry->translator_comments = trim($entry->translator_comments."\n".$comment);
                }
            }

            public static function trim_quotes($s)
            {
                if(str_starts_with($s, '"'))
                {
                    $s = substr($s, 1);
                }
                if(str_ends_with($s, '"'))
                {
                    $s = substr($s, 0, -1);
                }

                return $s;
            }
        }
    endif;
