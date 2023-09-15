<?php

// If gettext isn't available.
    if(! function_exists('_'))
    {
        function _($message)
        {
            return $message;
        }
    }

    function _wp_can_use_pcre_u($set = null)
    {
        static $utf8_pcre = 'reset';

        if(null !== $set)
        {
            $utf8_pcre = $set;
        }

        if('reset' === $utf8_pcre)
        {
            // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- intentional error generated to detect PCRE/u support.
            $utf8_pcre = @preg_match('/^./u', 'a');
        }

        return $utf8_pcre;
    }

    if(! function_exists('mb_substr')) :

        function mb_substr($string, $start, $length = null, $encoding = null)
        { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.stringFound
            return _mb_substr($string, $start, $length, $encoding);
        }
    endif;

    function _mb_substr($str, $start, $length = null, $encoding = null)
    {
        if(null === $str)
        {
            return '';
        }

        if(null === $encoding)
        {
            $encoding = get_option('blog_charset');
        }

        /*
         * The solution below works only for UTF-8, so in case of a different
         * charset just use built-in substr().
         */
        if(! in_array($encoding, ['utf8', 'utf-8', 'UTF8', 'UTF-8'], true))
        {
            if(is_null($length))
            {
                return substr($str, $start);
            }

            return substr($str, $start, $length);
        }

        if(_wp_can_use_pcre_u())
        {
            // Use the regex unicode support to separate the UTF-8 characters into an array.
            preg_match_all('/./us', $str, $match);
            $chars = is_null($length) ? array_slice($match[0], $start) : array_slice($match[0], $start, $length);

            return implode('', $chars);
        }

        $regex = '/(
		[\x00-\x7F]                  # single-byte sequences   0xxxxxxx
		| [\xC2-\xDF][\x80-\xBF]       # double-byte sequences   110xxxxx 10xxxxxx
		| \xE0[\xA0-\xBF][\x80-\xBF]   # triple-byte sequences   1110xxxx 10xxxxxx * 2
		| [\xE1-\xEC][\x80-\xBF]{2}
		| \xED[\x80-\x9F][\x80-\xBF]
		| [\xEE-\xEF][\x80-\xBF]{2}
		| \xF0[\x90-\xBF][\x80-\xBF]{2} # four-byte sequences   11110xxx 10xxxxxx * 3
		| [\xF1-\xF3][\x80-\xBF]{3}
		| \xF4[\x80-\x8F][\x80-\xBF]{2}
	)/x';

        // Start with 1 element instead of 0 since the first thing we do is pop.
        $chars = [''];

        do
        {
            // We had some string left over from the last round, but we counted it in that last round.
            array_pop($chars);

            /*
             * Split by UTF-8 character, limit to 1000 characters (last array element will contain
             * the rest of the string).
             */
            $pieces = preg_split($regex, $str, 1000, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

            $chars = array_merge($chars, $pieces);
            // If there's anything left over, repeat the loop.
        }
        while(count($pieces) > 1 && $str = array_pop($pieces));

        return implode('', array_slice($chars, $start, $length));
    }

    if(! function_exists('mb_strlen')) :

        function mb_strlen($string, $encoding = null)
        { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.stringFound
            return _mb_strlen($string, $encoding);
        }
    endif;

    function _mb_strlen($str, $encoding = null)
    {
        if(null === $encoding)
        {
            $encoding = get_option('blog_charset');
        }

        /*
         * The solution below works only for UTF-8, so in case of a different charset
         * just use built-in strlen().
         */
        if(! in_array($encoding, ['utf8', 'utf-8', 'UTF8', 'UTF-8'], true))
        {
            return strlen($str);
        }

        if(_wp_can_use_pcre_u())
        {
            // Use the regex unicode support to separate the UTF-8 characters into an array.
            preg_match_all('/./us', $str, $match);

            return count($match[0]);
        }

        $regex = '/(?:
		[\x00-\x7F]                  # single-byte sequences   0xxxxxxx
		| [\xC2-\xDF][\x80-\xBF]       # double-byte sequences   110xxxxx 10xxxxxx
		| \xE0[\xA0-\xBF][\x80-\xBF]   # triple-byte sequences   1110xxxx 10xxxxxx * 2
		| [\xE1-\xEC][\x80-\xBF]{2}
		| \xED[\x80-\x9F][\x80-\xBF]
		| [\xEE-\xEF][\x80-\xBF]{2}
		| \xF0[\x90-\xBF][\x80-\xBF]{2} # four-byte sequences   11110xxx 10xxxxxx * 3
		| [\xF1-\xF3][\x80-\xBF]{3}
		| \xF4[\x80-\x8F][\x80-\xBF]{2}
	)/x';

        // Start at 1 instead of 0 since the first thing we do is decrement.
        $count = 1;

        do
        {
            // We had some string left over from the last round, but we counted it in that last round.
            --$count;

            /*
             * Split by UTF-8 character, limit to 1000 characters (last array element will contain
             * the rest of the string).
             */
            $pieces = preg_split($regex, $str, 1000);

            // Increment.
            $count += count($pieces);
            // If there's anything left over, repeat the loop.
        }
        while($str = array_pop($pieces));

        // Fencepost: preg_split() always returns one extra item in the array.
        return --$count;
    }

    if(! function_exists('hash_hmac')) :

        function hash_hmac($algo, $data, $key, $binary = false)
        {
            return _hash_hmac($algo, $data, $key, $binary);
        }
    endif;

    function _hash_hmac($algo, $data, $key, $binary = false)
    {
        $packs = [
            'md5' => 'H32',
            'sha1' => 'H40',
        ];

        if(! isset($packs[$algo]))
        {
            return false;
        }

        $pack = $packs[$algo];

        if(strlen($key) > 64)
        {
            $key = pack($pack, $algo($key));
        }

        $key = str_pad($key, 64, chr(0));

        $ipad = (substr($key, 0, 64) ^ str_repeat(chr(0x36), 64));
        $opad = (substr($key, 0, 64) ^ str_repeat(chr(0x5C), 64));

        $hmac = $algo($opad.pack($pack, $algo($ipad.$data)));

        if($binary)
        {
            return pack($pack, $hmac);
        }

        return $hmac;
    }

    if(! function_exists('hash_equals')) :

        function hash_equals($known_string, $user_string)
        {
            $known_string_length = strlen($known_string);

            if(strlen($user_string) !== $known_string_length)
            {
                return false;
            }

            $result = 0;

            // Do not attempt to "optimize" this.
            for($i = 0; $i < $known_string_length; $i++)
            {
                $result |= ord($known_string[$i]) ^ ord($user_string[$i]);
            }

            return 0 === $result;
        }
    endif;

// sodium_crypto_box() was introduced in PHP 7.2.
    if(! function_exists('sodium_crypto_box'))
    {
        require ABSPATH.WPINC.'/sodium_compat/autoload.php';
    }

    if(! function_exists('is_countable'))
    {
        function is_countable($value)
        {
            return (is_array($value) || $value instanceof Countable || $value instanceof SimpleXMLElement || $value instanceof ResourceBundle);
        }
    }

    if(! function_exists('is_iterable'))
    {
        function is_iterable($value)
        {
            return (is_array($value) || $value instanceof Traversable);
        }
    }

    if(! function_exists('array_key_first'))
    {
        function array_key_first(array $array)
        { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.arrayFound
            foreach($array as $key => $value)
            {
                return $key;
            }
        }
    }

    if(! function_exists('array_key_last'))
    {
        function array_key_last(array $array)
        { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.arrayFound
            if(empty($array))
            {
                return null;
            }

            end($array);

            return key($array);
        }
    }

    if(! function_exists('str_contains'))
    {
        function str_contains($haystack, $needle)
        {
            if('' === $needle)
            {
                return true;
            }

            return false !== strpos($haystack, $needle);
        }
    }

    if(! function_exists('str_starts_with'))
    {
        function str_starts_with($haystack, $needle)
        {
            if('' === $needle)
            {
                return true;
            }

            return 0 === strpos($haystack, $needle);
        }
    }

    if(! function_exists('str_ends_with'))
    {
        function str_ends_with($haystack, $needle)
        {
            if('' === $haystack)
            {
                return '' === $needle;
            }

            $len = strlen($needle);

            return substr($haystack, -$len, $len) === $needle;
        }
    }

// IMAGETYPE_WEBP constant is only defined in PHP 7.1 or later.
    if(! defined('IMAGETYPE_WEBP'))
    {
        define('IMAGETYPE_WEBP', 18);
    }

// IMG_WEBP constant is only defined in PHP 7.0.10 or later.
    if(! defined('IMG_WEBP'))
    {
        define('IMG_WEBP', IMAGETYPE_WEBP);
    }
