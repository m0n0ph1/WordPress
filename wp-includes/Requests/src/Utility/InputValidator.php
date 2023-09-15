<?php

    namespace WpOrg\Requests\Utility;

    use ArrayAccess;
    use CurlHandle;
    use Traversable;

    final class InputValidator
    {
        public static function is_string_or_stringable($input)
        {
            return is_string($input) || self::is_stringable_object($input);
        }

        public static function is_stringable_object($input)
        {
            return is_object($input) && method_exists($input, '__toString');
        }

        public static function is_numeric_array_key($input)
        {
            if(is_int($input))
            {
                return true;
            }

            if(! is_string($input))
            {
                return false;
            }

            return (bool) preg_match('`^-?[0-9]+$`', $input);
        }

        public static function has_array_access($input)
        {
            return is_array($input) || $input instanceof ArrayAccess;
        }

        public static function is_iterable($input)
        {
            return is_array($input) || $input instanceof Traversable;
        }

        public static function is_curl_handle($input)
        {
            if(is_resource($input))
            {
                return get_resource_type($input) === 'curl';
            }

            if(is_object($input))
            {
                return $input instanceof CurlHandle;
            }

            return false;
        }
    }
