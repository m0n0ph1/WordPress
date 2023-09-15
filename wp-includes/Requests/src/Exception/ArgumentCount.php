<?php

    namespace WpOrg\Requests\Exception;

    use WpOrg\Requests\Exception;

    final class ArgumentCount extends Exception
    {
        public static function create($expected, $received, $type)
        {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
            $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

            return new self(sprintf('%s::%s() expects %s, %d given', $stack[1]['class'], $stack[1]['function'], $expected, $received), $type);
        }
    }
