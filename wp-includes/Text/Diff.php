<?php

    class Text_Diff
    {
        var $_edits;

        static function trimNewlines(&$line, $key)
        {
            $line = str_replace(["\n", "\r"], '', $line);
        }

        static function _getTempDir()
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
            if(! strlen($tmp))
            {
                $tmp = getenv('TMPDIR');
            }

            /* If we still cannot determine a value, then cycle through a list of
             * preset possibilities. */
            while(! strlen($tmp) && count($tmp_locations))
            {
                $tmp_check = array_shift($tmp_locations);
                if(@is_dir($tmp_check))
                {
                    $tmp = $tmp_check;
                }
            }

            /* If it is still empty, we have failed, so return false; otherwise
             * return the directory determined. */

            return strlen($tmp) ? $tmp : false;
        }

        public function Text_Diff($engine, $params)
        {
            self::__construct($engine, $params);
        }

        function __construct($engine, $params)
        {
            // Backward compatibility workaround.
            if(! is_string($engine))
            {
                $params = [$engine, $params];
                $engine = 'auto';
            }

            if($engine == 'auto')
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

        function getDiff()
        {
            return $this->_edits;
        }

        function countAddedLines()
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

        function countDeletedLines()
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

        function isEmpty()
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

        function lcs()
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

        function _check($from_lines, $to_lines)
        {
            if(serialize($from_lines) != serialize($this->getOriginal()))
            {
                trigger_error("Reconstructed original does not match", E_USER_ERROR);
            }
            if(serialize($to_lines) != serialize($this->getFinal()))
            {
                trigger_error("Reconstructed final does not match", E_USER_ERROR);
            }

            $rev = $this->reverse();
            if(serialize($to_lines) != serialize($rev->getOriginal()))
            {
                trigger_error("Reversed original does not match", E_USER_ERROR);
            }
            if(serialize($from_lines) != serialize($rev->getFinal()))
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

        function getOriginal()
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

        function getFinal()
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

        function reverse()
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
            self::__construct($from_lines, $to_lines, $mapped_from_lines, $mapped_to_lines);
        }

        function __construct(
            $from_lines, $to_lines, $mapped_from_lines, $mapped_to_lines
        ) {
            assert(count($from_lines) == count($mapped_from_lines));
            assert(count($to_lines) == count($mapped_to_lines));

            parent::Text_Diff($mapped_from_lines, $mapped_to_lines);

            $xi = $yi = 0;
            for($i = 0; $i < count($this->_edits); $i++)
            {
                $orig = &$this->_edits[$i]->orig;
                if(is_array($orig))
                {
                    $orig = array_slice($from_lines, $xi, count($orig));
                    $xi += count($orig);
                }

                $final = &$this->_edits[$i]->final;
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
        var $orig;

        var $final;

        function &reverse()
        {
            trigger_error('Abstract method', E_USER_ERROR);
        }

        function norig()
        {
            return $this->orig ? count($this->orig) : 0;
        }

        function nfinal()
        {
            return $this->final ? count($this->final) : 0;
        }
    }

    class Text_Diff_Op_copy extends Text_Diff_Op
    {
        public function Text_Diff_Op_copy($orig, $final = false)
        {
            self::__construct($orig, $final);
        }

        function __construct($orig, $final = false)
        {
            if(! is_array($final))
            {
                $final = $orig;
            }
            $this->orig = $orig;
            $this->final = $final;
        }

        function &reverse()
        {
            $reverse = new Text_Diff_Op_copy($this->final, $this->orig);

            return $reverse;
        }
    }

    class Text_Diff_Op_delete extends Text_Diff_Op
    {
        public function Text_Diff_Op_delete($lines)
        {
            self::__construct($lines);
        }

        function __construct($lines)
        {
            $this->orig = $lines;
            $this->final = false;
        }

        function &reverse()
        {
            $reverse = new Text_Diff_Op_add($this->orig);

            return $reverse;
        }
    }

    class Text_Diff_Op_add extends Text_Diff_Op
    {
        public function Text_Diff_Op_add($lines)
        {
            self::__construct($lines);
        }

        function __construct($lines)
        {
            $this->final = $lines;
            $this->orig = false;
        }

        function &reverse()
        {
            $reverse = new Text_Diff_Op_delete($this->final);

            return $reverse;
        }
    }

    class Text_Diff_Op_change extends Text_Diff_Op
    {
        public function Text_Diff_Op_change($orig, $final)
        {
            self::__construct($orig, $final);
        }

        function __construct($orig, $final)
        {
            $this->orig = $orig;
            $this->final = $final;
        }

        function &reverse()
        {
            $reverse = new Text_Diff_Op_change($this->final, $this->orig);

            return $reverse;
        }
    }
