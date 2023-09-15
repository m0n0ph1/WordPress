<?php

    namespace WpOrg\Requests;

    use WpOrg\Requests\Exception\InvalidArgument;

    final class Port
    {
        public const ACAP = 674;

        public const DICT = 2628;

        public const HTTP = 80;

        public const HTTPS = 443;

        public static function get($type)
        {
            if(! is_string($type))
            {
                throw InvalidArgument::create(1, '$type', 'string', gettype($type));
            }

            $type = strtoupper($type);
            if(! defined("self::{$type}"))
            {
                $message = sprintf('Invalid port type (%s) passed', $type);
                throw new Exception($message, 'portnotsupported');
            }

            return constant("self::{$type}");
        }
    }
