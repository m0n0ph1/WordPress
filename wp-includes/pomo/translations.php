<?php

    require_once __DIR__.'/plural-forms.php';
    require_once __DIR__.'/entry.php';

    if(! class_exists('Translations', false)) :
        #[AllowDynamicProperties]
        class Translations
        {
            public $entries = [];

            public $headers = [];

            public function add_entry($entry)
            {
                if(is_array($entry))
                {
                    $entry = new Translation_Entry($entry);
                }
                $key = $entry->key();
                if(false === $key)
                {
                    return false;
                }
                $this->entries[$key] = &$entry;

                return true;
            }

            public function add_entry_or_merge($entry)
            {
                if(is_array($entry))
                {
                    $entry = new Translation_Entry($entry);
                }
                $key = $entry->key();
                if(false === $key)
                {
                    return false;
                }
                if(isset($this->entries[$key]))
                {
                    $this->entries[$key]->merge_with($entry);
                }
                else
                {
                    $this->entries[$key] = &$entry;
                }

                return true;
            }

            public function set_header($header, $value)
            {
                $this->headers[$header] = $value;
            }

            public function set_headers($headers)
            {
                foreach($headers as $header => $value)
                {
                    $this->set_header($header, $value);
                }
            }

            public function get_header($header)
            {
                if(isset($this->headers[$header]))
                {
                    return $this->headers[$header];
                }

                return false;
            }

            public function translate_entry(&$entry)
            {
                $key = $entry->key();

                if(isset($this->entries[$key]))
                {
                    return $this->entries[$key];
                }

                return false;
            }

            public function translate($singular, $context = null)
            {
                $entry = new Translation_Entry(compact('singular', 'context'));
                $translated = $this->translate_entry($entry);

                if($translated && ! empty($translated->translations))
                {
                    return $translated->translations[0];
                }

                return $singular;
            }

            public function select_plural_form($count)
            {
                if(1 === (int) $count)
                {
                    return 0;
                }

                return 1;
            }

            public function get_plural_forms_count()
            {
                return 2;
            }

            public function translate_plural($singular, $plural, $count, $context = null)
            {
                $entry = new Translation_Entry(compact('singular', 'plural', 'context'));
                $translated = $this->translate_entry($entry);
                $index = $this->select_plural_form($count);
                $total_plural_forms = $this->get_plural_forms_count();
                if($translated && 0 <= $index && $index < $total_plural_forms && is_array($translated->translations) && isset($translated->translations[$index]))
                {
                    return $translated->translations[$index];
                }
                else
                {
                    if(1 === (int) $count)
                    {
                        return $singular;
                    }

                    return $plural;
                }
            }

            public function merge_with(&$other)
            {
                foreach($other->entries as $entry)
                {
                    $this->entries[$entry->key()] = $entry;
                }
            }

            public function merge_originals_with(&$other)
            {
                foreach($other->entries as $entry)
                {
                    if(isset($this->entries[$entry->key()]))
                    {
                        $this->entries[$entry->key()]->merge_with($entry);
                    }
                    else
                    {
                        $this->entries[$entry->key()] = $entry;
                    }
                }
            }
        }

        class Gettext_Translations extends Translations
        {
            public $_nplurals;

            public $_gettext_select_plural_form;

            public function gettext_select_plural_form($count)
            {
                if(! isset($this->_gettext_select_plural_form) || is_null($this->_gettext_select_plural_form))
                {
                    [
                        $nplurals,
                        $expression
                    ] = $this->nplurals_and_expression_from_header($this->get_header('Plural-Forms'));
                    $this->_nplurals = $nplurals;
                    $this->_gettext_select_plural_form = $this->make_plural_form_function($nplurals, $expression);
                }

                return call_user_func($this->_gettext_select_plural_form, $count);
            }

            public function nplurals_and_expression_from_header($header)
            {
                if(preg_match('/^\s*nplurals\s*=\s*(\d+)\s*;\s+plural\s*=\s*(.+)$/', $header, $matches))
                {
                    $nplurals = (int) $matches[1];
                    $expression = trim($matches[2]);

                    return [$nplurals, $expression];
                }
                else
                {
                    return [2, 'n != 1'];
                }
            }

            public function make_plural_form_function($nplurals, $expression)
            {
                try
                {
                    $handler = new Plural_Forms(rtrim($expression, ';'));

                    return [$handler, 'get'];
                }
                catch(Exception $e)
                {
                    // Fall back to default plural-form function.
                    return $this->make_plural_form_function(2, 'n != 1');
                }
            }

            public function parenthesize_plural_exression($expression)
            {
                $expression .= ';';
                $res = '';
                $depth = 0;
                for($i = 0, $iMax = strlen($expression); $i < $iMax; ++$i)
                {
                    $char = $expression[$i];
                    switch($char)
                    {
                        case '?':
                            $res .= ' ? (';
                            ++$depth;
                            break;
                        case ':':
                            $res .= ') : (';
                            break;
                        case ';':
                            $res .= str_repeat(')', $depth).';';
                            $depth = 0;
                            break;
                        default:
                            $res .= $char;
                    }
                }

                return rtrim($res, ';');
            }

            public function make_headers($translation)
            {
                $headers = [];
                // Sometimes \n's are used instead of real new lines.
                $translation = str_replace('\n', "\n", $translation);
                $lines = explode("\n", $translation);
                foreach($lines as $line)
                {
                    $parts = explode(':', $line, 2);
                    if(! isset($parts[1]))
                    {
                        continue;
                    }
                    $headers[trim($parts[0])] = trim($parts[1]);
                }

                return $headers;
            }

            public function set_header($header, $value)
            {
                parent::set_header($header, $value);
                if('Plural-Forms' === $header)
                {
                    [
                        $nplurals,
                        $expression
                    ] = $this->nplurals_and_expression_from_header($this->get_header('Plural-Forms'));
                    $this->_nplurals = $nplurals;
                    $this->_gettext_select_plural_form = $this->make_plural_form_function($nplurals, $expression);
                }
            }
        }
    endif;

    if(! class_exists('NOOP_Translations', false)) :

        #[AllowDynamicProperties]
        class NOOP_Translations
        {
            public $entries = [];

            public $headers = [];

            public function add_entry($entry)
            {
                return true;
            }

            public function set_header($header, $value) {}

            public function set_headers($headers) {}

            public function get_header($header)
            {
                return false;
            }

            public function translate_entry(&$entry)
            {
                return false;
            }

            public function translate($singular, $context = null)
            {
                return $singular;
            }

            public function select_plural_form($count)
            {
                if(1 === (int) $count)
                {
                    return 0;
                }

                return 1;
            }

            public function get_plural_forms_count()
            {
                return 2;
            }

            public function translate_plural($singular, $plural, $count, $context = null)
            {
                if(1 === (int) $count)
                {
                    return $singular;
                }

                return $plural;
            }

            public function merge_with(&$other) {}
        }
    endif;
