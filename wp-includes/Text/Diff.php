<?php

    class Text_Diff
    {
        public $_edits;

        public static function trimNewlines(&$line, $key)
        {
            $line = str_replace(["\n", "\r"], '', $line);
        }

        public static function _getTempDir()
        {
            $tmp_locations = [
                '/tmp',
                '/var/tmp',
                'c:\WUTemp',
                'c:\temp',
                'c:\windows\temp',
                'c:\winnt\temp'
            ];

            /* Try PHP's upload_tmp_dir directive. */
            $tmp = ini_get('upload_tmp_dir');

            /* Otherwise, try to determine the TMPDIR environment variable. */
            if($tmp == '')
            {
                $tmp = getenv('TMPDIR');
            }

            /* If we still cannot determine a value, then cycle through a list of
             * preset possibilities. */
            while($tmp == '' && count($tmp_locations))
            {
                $tmp_check = array_shift($tmp_locations);
                if(@is_dir($tmp_check))
                {
                    $tmp = $tmp_check;
                }
            }

            /* If it is still empty, we have failed, so return false; otherwise
             * return the directory determined. */

            if($tmp != '')
            {
                return $tmp;
            }

            return false;
        }

        public function Text_Diff($engine, $params)
        {
            $this->__construct($engine, $params);
        }

        public function __construct($engine, $params)
        {
            // Backward compatibility workaround.
            if(! is_string($engine))
            {
                $params = [$engine, $params];
                $engine = 'auto';
            }

            if($engine === 'auto')
            {
                $engine = extension_loaded('xdiff') ? 'xdiff' : 'native';
            }
            else
            {
                $engine = basename($engine);
            }

            // WP #7391
            require_once dirname(__FILE__).'/Diff/Engine/'.$engine.'.php';
            $class = 'Text_Diff_Engine_'.$engine;
            $diff_engine = new $class();

            $this->_edits = call_user_func_array([$diff_engine, 'diff'], $params);
        }

        public function getDiff()
        {
            return $this->_edits;
        }

        public function countAddedLines()
        {
            $count = 0;
            foreach($this->_edits as $edit)
            {
                if(is_a($edit, 'Text_Diff_Op_add') || is_a($edit, 'Text_Diff_Op_change'))
                {
                    $count += $edit->nfinal();
                }
            }

            return $count;
        }

        public function countDeletedLines()
        {
            $count = 0;
            foreach($this->_edits as $edit)
            {
                if(is_a($edit, 'Text_Diff_Op_delete') || is_a($edit, 'Text_Diff_Op_change'))
                {
                    $count += $edit->norig();
                }
            }

            return $count;
        }

        public function isEmpty()
        {
            foreach($this->_edits as $edit)
            {
                if(! is_a($edit, 'Text_Diff_Op_copy'))
                {
                    return false;
                }
            }

            return true;
        }

        public function lcs()
        {
            $lcs = 0;
            foreach($this->_edits as $edit)
            {
                if(is_a($edit, 'Text_Diff_Op_copy'))
                {
                    $lcs += count($edit->orig);
                }
            }

            return $lcs;
        }

        public function _check($from_lines, $to_lines)
        {
            if(serialize($from_lines) !== serialize($this->getOriginal()))
            {
                trigger_error("Reconstructed original does not match", E_USER_ERROR);
            }
            if(serialize($to_lines) !== serialize($this->getFinal()))
            {
                trigger_error("Reconstructed final does not match", E_USER_ERROR);
            }

            $rev = $this->reverse();
            if(serialize($to_lines) !== serialize($rev->getOriginal()))
            {
                trigger_error("Reversed original does not match", E_USER_ERROR);
            }
            if(serialize($from_lines) !== serialize($rev->getFinal()))
            {
                trigger_error("Reversed final does not match", E_USER_ERROR);
            }

            $prevtype = null;
            foreach($this->_edits as $edit)
            {
                if($edit instanceof $prevtype)
                {
                    trigger_error("Edit sequence is non-optimal", E_USER_ERROR);
                }
                $prevtype = get_class($edit);
            }

            return true;
        }

        public function getOriginal()
        {
            $lines = [];
            foreach($this->_edits as $edit)
            {
                if($edit->orig)
                {
                    array_splice($lines, count($lines), 0, $edit->orig);
                }
            }

            return $lines;
        }

        public function getFinal()
        {
            $lines = [];
            foreach($this->_edits as $edit)
            {
                if($edit->final)
                {
                    array_splice($lines, count($lines), 0, $edit->final);
                }
            }

            return $lines;
        }

        public function reverse()
        {
            if(version_compare(zend_version(), '2', '>'))
            {
                $rev = clone($this);
            }
            else
            {
                $rev = $this;
            }
            $rev->_edits = [];
            foreach($this->_edits as $edit)
            {
                $rev->_edits[] = $edit->reverse();
            }

            return $rev;
        }
    }

    class Text_MappedDiff extends Text_Diff
    {
        public function Text_MappedDiff(
            $from_lines, $to_lines, $mapped_from_lines, $mapped_to_lines
        ) {
            $this->__construct($from_lines, $to_lines, $mapped_from_lines, $mapped_to_lines);
        }

        public function __construct(
            $from_lines, $to_lines, $mapped_from_lines, $mapped_to_lines
        ) {
            parent::__construct($engine, $params);
            assert(count($from_lines) === count($mapped_from_lines));
            assert(count($to_lines) === count($mapped_to_lines));

            parent::Text_Diff($mapped_from_lines, $mapped_to_lines);

            $yi = 0;
            $xi = 0;
            foreach($this->_edits as $iValue)
            {
                $orig = &$iValue->orig;
                if(is_array($orig))
                {
                    $orig = array_slice($from_lines, $xi, count($orig));
                    $xi += count($orig);
                }

                $final = &$iValue->final;
                if(is_array($final))
                {
                    $final = array_slice($to_lines, $yi, count($final));
                    $yi += count($final);
                }
            }
        }
    }

    class Text_Diff_Op
    {
        public $orig;

        public $final;

        public function &reverse()
        {
            trigger_error('Abstract method', E_USER_ERROR);
        }

        public function norig()
        {
            if($this->orig)
            {
                return count($this->orig);
            }

            return 0;
        }

        public function nfinal()
        {
            if($this->final)
            {
                return count($this->final);
            }

            return 0;
        }
    }

    class Text_Diff_Op_copy extends Text_Diff_Op
    {
        public function Text_Diff_Op_copy($orig, $final = false)
        {
            $this->__construct($orig, $final);
        }

        public function __construct($orig, $final = false)
        {
            if(! is_array($final))
            {
                $final = $orig;
            }
            $this->orig = $orig;
            $this->final = $final;
        }

        public function &reverse()
        {
            $reverse = new Text_Diff_Op_copy($this->final, $this->orig);

            return $reverse;
        }
    }

    class Text_Diff_Op_delete extends Text_Diff_Op
    {
        public function Text_Diff_Op_delete($lines)
        {
            $this->__construct($lines);
        }

        public function __construct($lines)
        {
            $this->orig = $lines;
            $this->final = false;
        }

        public function &reverse()
        {
            $reverse = new Text_Diff_Op_add($this->orig);

            return $reverse;
        }
    }

    class Text_Diff_Op_add extends Text_Diff_Op
    {
        public function Text_Diff_Op_add($lines)
        {
            $this->__construct($lines);
        }

        public function __construct($lines)
        {
            $this->final = $lines;
            $this->orig = false;
        }

        public function &reverse()
        {
            $reverse = new Text_Diff_Op_delete($this->final);

            return $reverse;
        }
    }

    class Text_Diff_Op_change extends Text_Diff_Op
    {
        public function Text_Diff_Op_change($orig, $final)
        {
            $this->__construct($orig, $final);
        }

        public function __construct($orig, $final)
        {
            $this->orig = $orig;
            $this->final = $final;
        }

        public function &reverse()
        {
            $reverse = new Text_Diff_Op_change($this->final, $this->orig);

            return $reverse;
        }
    }
