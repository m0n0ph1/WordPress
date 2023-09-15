<?php

    if(! class_exists('Plural_Forms', false)) :
        #[AllowDynamicProperties]
        class Plural_Forms
        {
            public const OP_CHARS = '|&><!=%?:';

            public const NUM_CHARS = '0123456789';

            protected static $op_precedence = [
                '%' => 6,

                '<' => 5,
                '<=' => 5,
                '>' => 5,
                '>=' => 5,

                '==' => 4,
                '!=' => 4,

                '&&' => 3,

                '||' => 2,

                '?:' => 1,
                '?' => 1,

                '(' => 0,
                ')' => 0,
            ];

            protected $tokens = [];

            protected $cache = [];

            public function __construct($str)
            {
                $this->parse($str);
            }

            protected function parse($str)
            {
                $pos = 0;
                $len = strlen($str);

                // Convert infix operators to postfix using the shunting-yard algorithm.
                $output = [];
                $stack = [];
                while($pos < $len)
                {
                    $next = substr($str, $pos, 1);

                    switch($next)
                    {
                        // Ignore whitespace.
                        case ' ':
                        case "\t":
                            ++$pos;
                            break;

                        // Variable (n).
                        case 'n':
                            $output[] = ['var'];
                            ++$pos;
                            break;

                        // Parentheses.
                        case '(':
                            $stack[] = $next;
                            ++$pos;
                            break;

                        case ')':
                            $found = false;
                            while(! empty($stack))
                            {
                                $o2 = $stack[count($stack) - 1];
                                if('(' !== $o2)
                                {
                                    $output[] = ['op', array_pop($stack)];
                                    continue;
                                }

                                // Discard open paren.
                                array_pop($stack);
                                $found = true;
                                break;
                            }

                            if(! $found)
                            {
                                throw new \RuntimeException('Mismatched parentheses');
                            }

                            ++$pos;
                            break;

                        // Operators.
                        case '|':
                        case '&':
                        case '>':
                        case '<':
                        case '!':
                        case '=':
                        case '%':
                        case '?':
                            $end_operator = strspn($str, self::OP_CHARS, $pos);
                            $operator = substr($str, $pos, $end_operator);
                            if(! array_key_exists($operator, self::$op_precedence))
                            {
                                throw new \RuntimeException(sprintf('Unknown operator "%s"', $operator));
                            }

                            while(! empty($stack))
                            {
                                $o2 = $stack[count($stack) - 1];

                                // Ternary is right-associative in C.
                                if('?:' === $operator || '?' === $operator)
                                {
                                    if(self::$op_precedence[$operator] >= self::$op_precedence[$o2])
                                    {
                                        break;
                                    }
                                }
                                elseif(self::$op_precedence[$operator] > self::$op_precedence[$o2])
                                {
                                    break;
                                }

                                $output[] = ['op', array_pop($stack)];
                            }
                            $stack[] = $operator;

                            $pos += $end_operator;
                            break;

                        // Ternary "else".
                        case ':':
                            $found = false;
                            $s_pos = count($stack) - 1;
                            while($s_pos >= 0)
                            {
                                $o2 = $stack[$s_pos];
                                if('?' !== $o2)
                                {
                                    $output[] = ['op', array_pop($stack)];
                                    --$s_pos;
                                    continue;
                                }

                                // Replace.
                                $stack[$s_pos] = '?:';
                                $found = true;
                                break;
                            }

                            if(! $found)
                            {
                                throw new \RuntimeException('Missing starting "?" ternary operator');
                            }
                            ++$pos;
                            break;

                        // Default - number or invalid.
                        default:
                            if($next >= '0' && $next <= '9')
                            {
                                $span = strspn($str, self::NUM_CHARS, $pos);
                                $output[] = ['value', intval(substr($str, $pos, $span))];
                                $pos += $span;
                                break;
                            }

                            throw new \RuntimeException(sprintf('Unknown symbol "%s"', $next));
                    }
                }

                while(! empty($stack))
                {
                    $o2 = array_pop($stack);
                    if('(' === $o2 || ')' === $o2)
                    {
                        throw new \RuntimeException('Mismatched parentheses');
                    }

                    $output[] = ['op', $o2];
                }

                $this->tokens = $output;
            }

            public function get($num)
            {
                if(isset($this->cache[$num]))
                {
                    return $this->cache[$num];
                }
                $this->cache[$num] = $this->execute($num);

                return $this->cache[$num];
            }

            public function execute($n)
            {
                $stack = [];
                $i = 0;
                $total = count($this->tokens);
                while($i < $total)
                {
                    $next = $this->tokens[$i];
                    ++$i;
                    if('var' === $next[0])
                    {
                        $stack[] = $n;
                        continue;
                    }
                    elseif('value' === $next[0])
                    {
                        $stack[] = $next[1];
                        continue;
                    }

                    // Only operators left.
                    switch($next[1])
                    {
                        case '%':
                            $v2 = array_pop($stack);
                            $v1 = array_pop($stack);
                            $stack[] = $v1 % $v2;
                            break;

                        case '||':
                            $v2 = array_pop($stack);
                            $v1 = array_pop($stack);
                            $stack[] = $v1 || $v2;
                            break;

                        case '&&':
                            $v2 = array_pop($stack);
                            $v1 = array_pop($stack);
                            $stack[] = $v1 && $v2;
                            break;

                        case '<':
                            $v2 = array_pop($stack);
                            $v1 = array_pop($stack);
                            $stack[] = $v1 < $v2;
                            break;

                        case '<=':
                            $v2 = array_pop($stack);
                            $v1 = array_pop($stack);
                            $stack[] = $v1 <= $v2;
                            break;

                        case '>':
                            $v2 = array_pop($stack);
                            $v1 = array_pop($stack);
                            $stack[] = $v1 > $v2;
                            break;

                        case '>=':
                            $v2 = array_pop($stack);
                            $v1 = array_pop($stack);
                            $stack[] = $v1 >= $v2;
                            break;

                        case '!=':
                            $v2 = array_pop($stack);
                            $v1 = array_pop($stack);
                            $stack[] = $v1 != $v2;
                            break;

                        case '==':
                            $v2 = array_pop($stack);
                            $v1 = array_pop($stack);
                            $stack[] = $v1 == $v2;
                            break;

                        case '?:':
                            $v3 = array_pop($stack);
                            $v2 = array_pop($stack);
                            $v1 = array_pop($stack);
                            $stack[] = $v1 ? $v2 : $v3;
                            break;

                        default:
                            throw new \RuntimeException(sprintf('Unknown operator "%s"', $next[1]));
                    }
                }

                if(count($stack) !== 1)
                {
                    throw new \RuntimeException('Too many values remaining on the stack');
                }

                return (int) $stack[0];
            }
        }
    endif;
