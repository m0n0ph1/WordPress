<?php

    namespace WpOrg\Requests;

    use WpOrg\Requests\Exception\InvalidArgument;
    use WpOrg\Requests\Utility\InputValidator;

    final class Ssl
    {
        public static function verify_certificate($host, $cert)
        {
            if(InputValidator::is_string_or_stringable($host) === false)
            {
                throw InvalidArgument::create(1, '$host', 'string|Stringable', gettype($host));
            }

            if(InputValidator::has_array_access($cert) === false)
            {
                throw InvalidArgument::create(2, '$cert', 'array|ArrayAccess', gettype($cert));
            }

            $has_dns_alt = false;

            // Check the subjectAltName
            if(! empty($cert['extensions']['subjectAltName']))
            {
                $altnames = explode(',', $cert['extensions']['subjectAltName']);
                foreach($altnames as $altname)
                {
                    $altname = trim($altname);
                    if(strncmp($altname, 'DNS:', 4) !== 0)
                    {
                        continue;
                    }

                    $has_dns_alt = true;

                    // Strip the 'DNS:' prefix and trim whitespace
                    $altname = trim(substr($altname, 4));

                    // Check for a match
                    if(self::match_domain($host, $altname) === true)
                    {
                        return true;
                    }
                }

                if($has_dns_alt === true)
                {
                    return false;
                }
            }

            // Fall back to checking the common name if we didn't get any dNSName
            // alt names, as per RFC2818
            if(! empty($cert['subject']['CN']))
            {
                // Check for a match
                return (self::match_domain($host, $cert['subject']['CN']) === true);
            }

            return false;
        }

        public static function match_domain($host, $reference)
        {
            if(InputValidator::is_string_or_stringable($host) === false)
            {
                throw InvalidArgument::create(1, '$host', 'string|Stringable', gettype($host));
            }

            // Check if the reference is blocklisted first
            if(self::verify_reference_name($reference) !== true)
            {
                return false;
            }

            // Check for a direct match
            if((string) $host === (string) $reference)
            {
                return true;
            }

            // Calculate the valid wildcard match if the host is not an IP address
            // Also validates that the host has 3 parts or more, as per Firefox's ruleset,
            // as a wildcard reference is only allowed with 3 parts or more, so the
            // comparison will never match if host doesn't contain 3 parts or more as well.
            if(ip2long($host) === false)
            {
                $parts = explode('.', $host);
                $parts[0] = '*';
                $wildcard = implode('.', $parts);
                if($wildcard === (string) $reference)
                {
                    return true;
                }
            }

            return false;
        }

        public static function verify_reference_name($reference)
        {
            if(InputValidator::is_string_or_stringable($reference) === false)
            {
                throw InvalidArgument::create(1, '$reference', 'string|Stringable', gettype($reference));
            }

            if($reference === '' || preg_match('`\s`', $reference) > 0)
            {
                // Whitespace detected. This can never be a dNSName.
                return false;
            }

            $parts = explode('.', $reference);
            if($parts !== array_filter($parts))
            {
                // DNSName cannot contain two dots next to each other.
                return false;
            }

            // Check the first part of the name
            $first = array_shift($parts);

            if(strpos($first, '*') !== false)
            {
                // Check that the wildcard is the full part
                // Check that we have at least 3 components (including first)
                if($first !== '*' || count($parts) < 2)
                {
                    return false;
                }
            }

            // Check the remaining parts
            foreach($parts as $part)
            {
                if(strpos($part, '*') !== false)
                {
                    return false;
                }
            }

            // Nothing found, verified!
            return true;
        }
    }
