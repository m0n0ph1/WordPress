<?php

    namespace WpOrg\Requests\Exception;

    use InvalidArgumentException;

    final class InvalidArgument extends InvalidArgumentException
    {
        public static function create($position, $name, $expected, $received)
        {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
            $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

            return new self(sprintf('%s::%s(): Argument #%d (%s) must be of type %s, %s given', $stack[1]['class'], $stack[1]['function'], $position, $name, $expected, $received));
        }
    }
