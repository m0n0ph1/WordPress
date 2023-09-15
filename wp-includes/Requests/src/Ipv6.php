<?php

    namespace WpOrg\Requests;

    use WpOrg\Requests\Exception\InvalidArgument;
    use WpOrg\Requests\Utility\InputValidator;

    final class Ipv6
    {
        public static function compress($ip)
        {
            // Prepare the IP to be compressed.
            // Note: Input validation is handled in the `uncompress()` method, which is the first call made in this method.
            $ip = self::uncompress($ip);
            $ip_parts = self::split_v6_v4($ip);

            // Replace all leading zeros
            $ip_parts[0] = preg_replace('/(^|:)0+([0-9])/', '\1\2', $ip_parts[0]);

            // Find bunches of zeros
            if(preg_match_all('/(?:^|:)(?:0(?::|$))+/', $ip_parts[0], $matches, PREG_OFFSET_CAPTURE))
            {
                $max = 0;
                $pos = null;
                foreach($matches[0] as $match)
                {
                    if(strlen($match[0]) > $max)
                    {
                        $max = strlen($match[0]);
                        $pos = $match[1];
                    }
                }

                $ip_parts[0] = substr_replace($ip_parts[0], '::', $pos, $max);
            }

            if($ip_parts[1] !== '')
            {
                return implode(':', $ip_parts);
            }
            else
            {
                return $ip_parts[0];
            }
        }

        public static function uncompress($ip)
        {
            if(InputValidator::is_string_or_stringable($ip) === false)
            {
                throw InvalidArgument::create(1, '$ip', 'string|Stringable', gettype($ip));
            }

            $ip = (string) $ip;

            if(substr_count($ip, '::') !== 1)
            {
                return $ip;
            }

            [$ip1, $ip2] = explode('::', $ip);
            $c1 = ($ip1 === '') ? -1 : substr_count($ip1, ':');
            $c2 = ($ip2 === '') ? -1 : substr_count($ip2, ':');

            if(strpos($ip2, '.') !== false)
            {
                $c2++;
            }

            if($c1 === -1 && $c2 === -1)
            {
                // ::
                $ip = '0:0:0:0:0:0:0:0';
            }
            elseif($c1 === -1)
            {
                // ::xxx
                $fill = str_repeat('0:', 7 - $c2);
                $ip = str_replace('::', $fill, $ip);
            }
            elseif($c2 === -1)
            {
                // xxx::
                $fill = str_repeat(':0', 7 - $c1);
                $ip = str_replace('::', $fill, $ip);
            }
            else
            {
                // xxx::xxx
                $fill = ':'.str_repeat('0:', 6 - $c2 - $c1);
                $ip = str_replace('::', $fill, $ip);
            }

            return $ip;
        }

        private static function split_v6_v4($ip)
        {
            if(strpos($ip, '.') !== false)
            {
                $pos = strrpos($ip, ':');
                $ipv6_part = substr($ip, 0, $pos);
                $ipv4_part = substr($ip, $pos + 1);

                return [$ipv6_part, $ipv4_part];
            }
            else
            {
                return [$ip, ''];
            }
        }

        public static function check_ipv6($ip)
        {
            // Note: Input validation is handled in the `uncompress()` method, which is the first call made in this method.
            $ip = self::uncompress($ip);
            [$ipv6, $ipv4] = self::split_v6_v4($ip);
            $ipv6 = explode(':', $ipv6);
            $ipv4 = explode('.', $ipv4);
            if(count($ipv6) === 8 && count($ipv4) === 1 || count($ipv6) === 6 && count($ipv4) === 4)
            {
                foreach($ipv6 as $ipv6_part)
                {
                    // The section can't be empty
                    // Nor can it be over four characters
                    if($ipv6_part === '' || strlen($ipv6_part) > 4)
                    {
                        return false;
                    }

                    // Remove leading zeros (this is safe because of the above)
                    $ipv6_part = ltrim($ipv6_part, '0');
                    if($ipv6_part === '')
                    {
                        $ipv6_part = '0';
                    }

                    // Check the value is valid
                    $value = hexdec($ipv6_part);
                    if(dechex($value) !== strtolower($ipv6_part) || $value < 0 || $value > 0xFFFF)
                    {
                        return false;
                    }
                }

                if(count($ipv4) === 4)
                {
                    foreach($ipv4 as $ipv4_part)
                    {
                        $value = (int) $ipv4_part;
                        if((string) $value !== $ipv4_part || $value < 0 || $value > 0xFF)
                        {
                            return false;
                        }
                    }
                }

                return true;
            }
            else
            {
                return false;
            }
        }
    }
