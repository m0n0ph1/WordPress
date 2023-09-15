<?php

    class string
    {
        public function diff($diff, $mode = 'autodetect')
        {
            // Detect line breaks.
            $lnbr = "\n";
            if(strpos($diff, "\r\n") !== false)
            {
                $lnbr = "\r\n";
            }
            elseif(strpos($diff, "\r") !== false)
            {
                $lnbr = "\r";
            }

            // Make sure we have a line break at the EOF.
            if(substr($diff, -strlen($lnbr)) != $lnbr)
            {
                $diff .= $lnbr;
            }

            if($mode != 'autodetect' && $mode != 'context' && $mode != 'unified')
            {
                return PEAR::raiseError('Type of diff is unsupported');
            }

            if($mode == 'autodetect')
            {
                $context = strpos($diff, '***');
                $unified = strpos($diff, '---');
                if($context === $unified)
                {
                    return PEAR::raiseError('Type of diff could not be detected');
                }
                elseif($context === false || $unified === false)
                {
                    $mode = $context !== false ? 'context' : 'unified';
                }
                else
                {
                    $mode = $context < $unified ? 'context' : 'unified';
                }
            }

            // Split by new line and remove the diff header, if there is one.
            $diff = explode($lnbr, $diff);
            if(($mode == 'context' && strncmp($diff[0], '***', 3) === 0) || ($mode == 'unified' && strncmp($diff[0], '---', 3) === 0))
            {
                array_shift($diff);
                array_shift($diff);
            }

            if($mode == 'context')
            {
                return $this->parseContextDiff($diff);
            }
            else
            {
                return $this->parseUnifiedDiff($diff);
            }
        }

        public function parseContextDiff(&$diff)
        {
            $edits = [];
            $max_j = 0;
            $j = 0;
            $max_i = 0;
            $i = 0;
            $end = count($diff) - 1;
            while($i < $end && $j < $end)
            {
                while($i >= $max_i && $j >= $max_j)
                {
                    // Find the boundaries of the diff output of the two files
                    for($i = $j; $i < $end && strpos($diff[$i], '***') === 0; $i++)
                    {
                        ;
                    }
                    for($max_i = $i; $max_i < $end && strpos($diff[$max_i], '---') !== 0; $max_i++)
                    {
                        ;
                    }
                    for($j = $max_i; $j < $end && strpos($diff[$j], '---') === 0; $j++)
                    {
                        ;
                    }
                    for($max_j = $j; $max_j < $end && strpos($diff[$max_j], '***') !== 0; $max_j++)
                    {
                        ;
                    }
                }

                // find what hasn't been changed
                $array = [];
                while($i < $max_i && $j < $max_j && strcmp($diff[$i], $diff[$j]) == 0)
                {
                    $array[] = substr($diff[$i], 2);
                    $i++;
                    $j++;
                }

                while($i < $max_i && ($max_j - $j) <= 1)
                {
                    if($diff[$i] != '' && strpos($diff[$i], ' ') !== 0)
                    {
                        break;
                    }
                    $array[] = substr($diff[$i++], 2);
                }

                while($j < $max_j && ($max_i - $i) <= 1)
                {
                    if($diff[$j] != '' && strpos($diff[$j], ' ') !== 0)
                    {
                        break;
                    }
                    $array[] = substr($diff[$j++], 2);
                }
                if(count($array) > 0)
                {
                    $edits[] = new Text_Diff_Op_copy($array);
                }

                if($i < $max_i)
                {
                    $diff1 = [];
                    switch(substr($diff[$i], 0, 1))
                    {
                        case '!':
                            $diff2 = [];
                            do
                            {
                                $diff1[] = substr($diff[$i], 2);
                                if($j < $max_j && strpos($diff[$j], '!') === 0)
                                {
                                    $diff2[] = substr($diff[$j++], 2);
                                }
                            }
                            while(++$i < $max_i && strpos($diff[$i], '!') === 0);
                            $edits[] = new Text_Diff_Op_change($diff1, $diff2);
                            break;

                        case '+':
                            do
                            {
                                $diff1[] = substr($diff[$i], 2);
                            }
                            while(++$i < $max_i && strpos($diff[$i], '+') === 0);
                            $edits[] = new Text_Diff_Op_add($diff1);
                            break;

                        case '-':
                            do
                            {
                                $diff1[] = substr($diff[$i], 2);
                            }
                            while(++$i < $max_i && strpos($diff[$i], '-') === 0);
                            $edits[] = new Text_Diff_Op_delete($diff1);
                            break;
                    }
                }

                if($j < $max_j)
                {
                    $diff2 = [];
                    switch(substr($diff[$j], 0, 1))
                    {
                        case '+':
                            do
                            {
                                $diff2[] = substr($diff[$j++], 2);
                            }
                            while($j < $max_j && strpos($diff[$j], '+') === 0);
                            $edits[] = new Text_Diff_Op_add($diff2);
                            break;

                        case '-':
                            do
                            {
                                $diff2[] = substr($diff[$j++], 2);
                            }
                            while($j < $max_j && strpos($diff[$j], '-') === 0);
                            $edits[] = new Text_Diff_Op_delete($diff2);
                            break;
                    }
                }
            }

            return $edits;
        }

        public function parseUnifiedDiff($diff)
        {
            $edits = [];
            $end = count($diff) - 1;
            for($i = 0; $i < $end;)
            {
                $diff1 = [];
                switch(substr($diff[$i], 0, 1))
                {
                    case ' ':
                        do
                        {
                            $diff1[] = substr($diff[$i], 1);
                        }
                        while(++$i < $end && strpos($diff[$i], ' ') === 0);
                        $edits[] = new Text_Diff_Op_copy($diff1);
                        break;

                    case '+':
                        // get all new lines
                        do
                        {
                            $diff1[] = substr($diff[$i], 1);
                        }
                        while(++$i < $end && strpos($diff[$i], '+') === 0);
                        $edits[] = new Text_Diff_Op_add($diff1);
                        break;

                    case '-':
                        // get changed or removed lines
                        $diff2 = [];
                        do
                        {
                            $diff1[] = substr($diff[$i], 1);
                        }
                        while(++$i < $end && strpos($diff[$i], '-') === 0);

                        while($i < $end && strpos($diff[$i], '+') === 0)
                        {
                            $diff2[] = substr($diff[$i++], 1);
                        }
                        if(count($diff2) == 0)
                        {
                            $edits[] = new Text_Diff_Op_delete($diff1);
                        }
                        else
                        {
                            $edits[] = new Text_Diff_Op_change($diff1, $diff2);
                        }
                        break;

                    default:
                        $i++;
                        break;
                }
            }

            return $edits;
        }
    }
