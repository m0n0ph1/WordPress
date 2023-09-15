<?php /** @noinspection ALL */
    /** @noinspection ALL */
    _deprecated_file(basename(__FILE__), '5.3.0', '', 'The PHP native JSON extension is now a requirement.');

    if(! class_exists('Services_JSON')) :
        /* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

        define('SERVICES_JSON_SLICE', 1);

        define('SERVICES_JSON_IN_STR', 2);

        define('SERVICES_JSON_IN_ARR', 3);

        define('SERVICES_JSON_IN_OBJ', 4);

        define('SERVICES_JSON_IN_CMT', 5);

        define('SERVICES_JSON_LOOSE_TYPE', 16);

        define('SERVICES_JSON_SUPPRESS_ERRORS', 32);

        define('SERVICES_JSON_USE_TO_JSON', 64);

        class Services_JSON
        {
            public $use;

            // private - cache the mbstring lookup results..
            var $_mb_strlen = false;

            var $_mb_substr = false;

            var $_mb_convert_encoding = false;

            function __construct($use = 0)
            {
                _deprecated_function(__METHOD__, '5.3.0', 'The PHP native JSON extension');

                $this->use = $use;
                $this->_mb_strlen = function_exists('mb_strlen');
                $this->_mb_convert_encoding = function_exists('mb_convert_encoding');
                $this->_mb_substr = function_exists('mb_substr');
            }

            public function Services_JSON($use = 0)
            {
                _deprecated_constructor('Services_JSON', '5.3.0', get_class($this));
                self::__construct($use);
            }

            function utf162utf8($utf16)
            {
                _deprecated_function(__METHOD__, '5.3.0', 'The PHP native JSON extension');

                // oh please oh please oh please oh please oh please
                if($this->_mb_convert_encoding)
                {
                    return mb_convert_encoding($utf16, 'UTF-8', 'UTF-16');
                }

                $bytes = (ord($utf16[0]) << 8) | ord($utf16[1]);

                switch(true)
                {
                    case ((0x7F & $bytes) == $bytes):
                        // this case should never be reached, because we are in ASCII range
                        // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                        return chr(0x7F & $bytes);

                    case (0x07FF & $bytes) == $bytes:
                        // return a 2-byte UTF-8 character
                        // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                        return chr(0xC0 | (($bytes >> 6) & 0x1F)).chr(0x80 | ($bytes & 0x3F));

                    case (0xFFFF & $bytes) == $bytes:
                        // return a 3-byte UTF-8 character
                        // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                        return chr(0xE0 | (($bytes >> 12) & 0x0F)).chr(0x80 | (($bytes >> 6) & 0x3F)).chr(0x80 | ($bytes & 0x3F));
                }

                // ignoring UTF-32 for now, sorry
                return '';
            }

            function utf82utf16($utf8)
            {
                _deprecated_function(__METHOD__, '5.3.0', 'The PHP native JSON extension');

                // oh please oh please oh please oh please oh please
                if($this->_mb_convert_encoding)
                {
                    return mb_convert_encoding($utf8, 'UTF-16', 'UTF-8');
                }

                switch($this->strlen8($utf8))
                {
                    case 1:
                        // this case should never be reached, because we are in ASCII range
                        // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                        return $utf8;

                    case 2:
                        // return a UTF-16 character from a 2-byte UTF-8 char
                        // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                        return chr(0x07 & (ord($utf8[0]) >> 2)).chr((0xC0 & (ord($utf8[0]) << 6)) | (0x3F & ord($utf8[1])));

                    case 3:
                        // return a UTF-16 character from a 3-byte UTF-8 char
                        // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                        return chr((0xF0 & (ord($utf8[0]) << 4)) | (0x0F & (ord($utf8[1]) >> 2))).chr((0xC0 & (ord($utf8[1]) << 6)) | (0x7F & ord($utf8[2])));
                }

                // ignoring UTF-32 for now, sorry
                return '';
            }

            function encode($var)
            {
                _deprecated_function(__METHOD__, '5.3.0', 'The PHP native JSON extension');

                header('Content-Type: application/json');

                return $this->encodeUnsafe($var);
            }

            function encodeUnsafe($var)
            {
                _deprecated_function(__METHOD__, '5.3.0', 'The PHP native JSON extension');

                // see bug #16908 - regarding numeric locale printing
                $lc = setlocale(LC_NUMERIC, 0);
                setlocale(LC_NUMERIC, 'C');
                $ret = $this->_encode($var);
                setlocale(LC_NUMERIC, $lc);

                return $ret;
            }

            function _encode($var)
            {
                _deprecated_function(__METHOD__, '5.3.0', 'The PHP native JSON extension');

                switch(gettype($var))
                {
                    case 'boolean':
                        if($var)
                        {
                            return 'true';
                        }

                        return 'false';

                    case 'NULL':
                        return 'null';

                    case 'integer':
                        return (int) $var;

                    case 'double':
                    case 'float':
                        return (float) $var;

                    case 'string':
                        // STRINGS ARE EXPECTED TO BE IN ASCII OR UTF-8 FORMAT
                        $ascii = '';
                        $strlen_var = $this->strlen8($var);

                        /*
                         * Iterate over every character in the string,
                         * escaping with a slash or encoding to UTF-8 where necessary
                         */
                        for($c = 0; $c < $strlen_var; ++$c)
                        {
                            $ord_var_c = ord($var[$c]);

                            switch(true)
                            {
                                case $ord_var_c == 0x08:
                                    $ascii .= '\b';
                                    break;
                                case $ord_var_c == 0x09:
                                    $ascii .= '\t';
                                    break;
                                case $ord_var_c == 0x0A:
                                    $ascii .= '\n';
                                    break;
                                case $ord_var_c == 0x0C:
                                    $ascii .= '\f';
                                    break;
                                case $ord_var_c == 0x0D:
                                    $ascii .= '\r';
                                    break;

                                case $ord_var_c == 0x22:
                                case $ord_var_c == 0x2F:
                                case $ord_var_c == 0x5C:
                                    // double quote, slash, slosh
                                    $ascii .= '\\'.$var[$c];
                                    break;

                                case (($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)):
                                    // characters U-00000000 - U-0000007F (same as ASCII)
                                    $ascii .= $var[$c];
                                    break;

                                case (($ord_var_c & 0xE0) === 0xC0):
                                    // characters U-00000080 - U-000007FF, mask 110XXXXX
                                    // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                    if($c + 1 >= $strlen_var)
                                    {
                                        $c += 1;
                                        $ascii .= '?';
                                        break;
                                    }

                                    $char = pack('C*', $ord_var_c, ord($var[$c + 1]));
                                    $c += 1;
                                    $utf16 = $this->utf82utf16($char);
                                    $ascii .= sprintf('\u%04s', bin2hex($utf16));
                                    break;

                                case (($ord_var_c & 0xF0) === 0xE0):
                                    if($c + 2 >= $strlen_var)
                                    {
                                        $c += 2;
                                        $ascii .= '?';
                                        break;
                                    }
                                    // characters U-00000800 - U-0000FFFF, mask 1110XXXX
                                    // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                    $char = pack('C*', $ord_var_c, @ord($var[$c + 1]), @ord($var[$c + 2]));
                                    $c += 2;
                                    $utf16 = $this->utf82utf16($char);
                                    $ascii .= sprintf('\u%04s', bin2hex($utf16));
                                    break;

                                case (($ord_var_c & 0xF8) === 0xF0):
                                    if($c + 3 >= $strlen_var)
                                    {
                                        $c += 3;
                                        $ascii .= '?';
                                        break;
                                    }
                                    // characters U-00010000 - U-001FFFFF, mask 11110XXX
                                    // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                    $char = pack('C*', $ord_var_c, ord($var[$c + 1]), ord($var[$c + 2]), ord($var[$c + 3]));
                                    $c += 3;
                                    $utf16 = $this->utf82utf16($char);
                                    $ascii .= sprintf('\u%04s', bin2hex($utf16));
                                    break;

                                case (($ord_var_c & 0xFC) === 0xF8):
                                    // characters U-00200000 - U-03FFFFFF, mask 111110XX
                                    // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                    if($c + 4 >= $strlen_var)
                                    {
                                        $c += 4;
                                        $ascii .= '?';
                                        break;
                                    }
                                    $char = pack('C*', $ord_var_c, ord($var[$c + 1]), ord($var[$c + 2]), ord($var[$c + 3]), ord($var[$c + 4]));
                                    $c += 4;
                                    $utf16 = $this->utf82utf16($char);
                                    $ascii .= sprintf('\u%04s', bin2hex($utf16));
                                    break;

                                case (($ord_var_c & 0xFE) === 0xFC):
                                    if($c + 5 >= $strlen_var)
                                    {
                                        $c += 5;
                                        $ascii .= '?';
                                        break;
                                    }
                                    // characters U-04000000 - U-7FFFFFFF, mask 1111110X
                                    // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                    $char = pack('C*', $ord_var_c, ord($var[$c + 1]), ord($var[$c + 2]), ord($var[$c + 3]), ord($var[$c + 4]), ord($var[$c + 5]));
                                    $c += 5;
                                    $utf16 = $this->utf82utf16($char);
                                    $ascii .= sprintf('\u%04s', bin2hex($utf16));
                                    break;
                            }
                        }

                        return '"'.$ascii.'"';

                    case 'array':
                        /*
                         * As per JSON spec if any array key is not an integer
                         * we must treat the whole array as an object. We
                         * also try to catch a sparsely populated associative
                         * array with numeric keys here because some JS engines
                         * will create an array with empty indexes up to
                         * max_index which can cause memory issues and because
                         * the keys, which may be relevant, will be remapped
                         * otherwise.
                         *
                         * As per the ECMA and JSON specification an object may
                         * have any string as a property. Unfortunately due to
                         * a hole in the ECMA specification if the key is a
                         * ECMA reserved word or starts with a digit the
                         * parameter is only accessible using ECMAScript's
                         * bracket notation.
                         */

                        // treat as a JSON object
                        if(is_array($var) && count($var) && (array_keys($var) !== range(0, sizeof($var) - 1)))
                        {
                            $properties = array_map([$this, 'name_value'], array_keys($var), array_values($var));

                            foreach($properties as $property)
                            {
                                if(Services_JSON::isError($property))
                                {
                                    return $property;
                                }
                            }

                            return '{'.join(',', $properties).'}';
                        }

                        // treat it like a regular array
                        $elements = array_map([$this, '_encode'], $var);

                        foreach($elements as $element)
                        {
                            if(Services_JSON::isError($element))
                            {
                                return $element;
                            }
                        }

                        return '['.join(',', $elements).']';

                    case 'object':

                        // support toJSON methods.
                        if(($this->use & SERVICES_JSON_USE_TO_JSON) && method_exists($var, 'toJSON'))
                        {
                            // this may end up allowing unlimited recursion
                            // so we check the return value to make sure it's not got the same method.
                            $recode = $var->toJSON();

                            if(method_exists($recode, 'toJSON'))
                            {
                                if($this->use & SERVICES_JSON_SUPPRESS_ERRORS)
                                {
                                    return 'null';
                                }

                                return new Services_JSON_Error(get_class($var)." toJSON returned an object with a toJSON method.");
                            }

                            return $this->_encode($recode);
                        }

                        $vars = get_object_vars($var);

                        $properties = array_map([$this, 'name_value'], array_keys($vars), array_values($vars));

                        foreach($properties as $property)
                        {
                            if(Services_JSON::isError($property))
                            {
                                return $property;
                            }
                        }

                        return '{'.join(',', $properties).'}';

                    default:
                        if($this->use & SERVICES_JSON_SUPPRESS_ERRORS)
                        {
                            return 'null';
                        }

                        return new Services_JSON_Error(gettype($var)." can not be encoded as JSON string");
                }
            }

            function name_value($name, $value)
            {
                _deprecated_function(__METHOD__, '5.3.0', 'The PHP native JSON extension');

                $encoded_value = $this->_encode($value);

                if(Services_JSON::isError($encoded_value))
                {
                    return $encoded_value;
                }

                return $this->_encode((string) $name).':'.$encoded_value;
            }

            function reduce_string($str)
            {
                _deprecated_function(__METHOD__, '5.3.0', 'The PHP native JSON extension');

                $str = preg_replace([

                                        // eliminate single line comments in '// ...' form
                                        '#^\s*//(.+)$#m',

                                        // eliminate multi-line comments in '/* ... */' form, at start of string
                                        '#^\s*/\*(.+)\*/#Us',

                                        // eliminate multi-line comments in '/* ... */' form, at end of string
                                        '#/\*(.+)\*/\s*$#Us'

                                    ], '', $str);

                // eliminate extraneous space
                return trim($str);
            }

            function decode($str)
            {
                _deprecated_function(__METHOD__, '5.3.0', 'The PHP native JSON extension');

                $str = $this->reduce_string($str);

                switch(strtolower($str))
                {
                    case 'true':
                        return true;

                    case 'false':
                        return false;

                    case 'null':
                        return null;

                    default:
                        $m = [];

                        if(is_numeric($str))
                        {
                            // Lookie-loo, it's a number

                            // This would work on its own, but I'm trying to be
                            // good about returning integers where appropriate:
                            // return (float)$str;

                            // Return float or int, as appropriate
                            if((float) $str == (integer) $str)
                            {
                                return (integer) $str;
                            }

                            return (float) $str;
                        }
                        elseif(preg_match('/^("|\').*(\1)$/s', $str, $m) && $m[1] == $m[2])
                        {
                            // STRINGS RETURNED IN UTF-8 FORMAT
                            $delim = $this->substr8($str, 0, 1);
                            $chrs = $this->substr8($str, 1, -1);
                            $utf8 = '';
                            $strlen_chrs = $this->strlen8($chrs);

                            for($c = 0; $c < $strlen_chrs; ++$c)
                            {
                                $substr_chrs_c_2 = $this->substr8($chrs, $c, 2);
                                $ord_chrs_c = ord($chrs[$c]);

                                switch(true)
                                {
                                    case $substr_chrs_c_2 == '\b':
                                        $utf8 .= chr(0x08);
                                        ++$c;
                                        break;
                                    case $substr_chrs_c_2 == '\t':
                                        $utf8 .= chr(0x09);
                                        ++$c;
                                        break;
                                    case $substr_chrs_c_2 == '\n':
                                        $utf8 .= chr(0x0A);
                                        ++$c;
                                        break;
                                    case $substr_chrs_c_2 == '\f':
                                        $utf8 .= chr(0x0C);
                                        ++$c;
                                        break;
                                    case $substr_chrs_c_2 == '\r':
                                        $utf8 .= chr(0x0D);
                                        ++$c;
                                        break;

                                    case $substr_chrs_c_2 == '\\"':
                                    case $substr_chrs_c_2 == '\\\'':
                                    case $substr_chrs_c_2 == '\\\\':
                                    case $substr_chrs_c_2 == '\\/':
                                        if(($delim == '"' && $substr_chrs_c_2 != '\\\'') || ($delim == "'" && $substr_chrs_c_2 != '\\"'))
                                        {
                                            $utf8 .= $chrs[++$c];
                                        }
                                        break;

                                    case preg_match('/\\\u[0-9A-F]{4}/i', $this->substr8($chrs, $c, 6)):
                                        // single, escaped unicode character
                                        $utf16 = chr(hexdec($this->substr8($chrs, ($c + 2), 2))).chr(hexdec($this->substr8($chrs, ($c + 4), 2)));
                                        $utf8 .= $this->utf162utf8($utf16);
                                        $c += 5;
                                        break;

                                    case ($ord_chrs_c >= 0x20) && ($ord_chrs_c <= 0x7F):
                                        $utf8 .= $chrs[$c];
                                        break;

                                    case ($ord_chrs_c & 0xE0) === 0xC0:
                                        // characters U-00000080 - U-000007FF, mask 110XXXXX
                                        // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                        $utf8 .= $this->substr8($chrs, $c, 2);
                                        ++$c;
                                        break;

                                    case ($ord_chrs_c & 0xF0) === 0xE0:
                                        // characters U-00000800 - U-0000FFFF, mask 1110XXXX
                                        // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                        $utf8 .= $this->substr8($chrs, $c, 3);
                                        $c += 2;
                                        break;

                                    case ($ord_chrs_c & 0xF8) === 0xF0:
                                        // characters U-00010000 - U-001FFFFF, mask 11110XXX
                                        // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                        $utf8 .= $this->substr8($chrs, $c, 4);
                                        $c += 3;
                                        break;

                                    case ($ord_chrs_c & 0xFC) === 0xF8:
                                        // characters U-00200000 - U-03FFFFFF, mask 111110XX
                                        // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                        $utf8 .= $this->substr8($chrs, $c, 5);
                                        $c += 4;
                                        break;

                                    case ($ord_chrs_c & 0xFE) === 0xFC:
                                        // characters U-04000000 - U-7FFFFFFF, mask 1111110X
                                        // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                        $utf8 .= $this->substr8($chrs, $c, 6);
                                        $c += 5;
                                        break;
                                }
                            }

                            return $utf8;
                        }
                        elseif(preg_match('/^\[.*\]$/s', $str) || preg_match('/^\{.*\}$/s', $str))
                        {
                            // array, or object notation

                            if($str[0] == '[')
                            {
                                $stk = [SERVICES_JSON_IN_ARR];
                                $arr = [];
                            }
                            else
                            {
                                if($this->use & SERVICES_JSON_LOOSE_TYPE)
                                {
                                    $stk = [SERVICES_JSON_IN_OBJ];
                                    $obj = [];
                                }
                                else
                                {
                                    $stk = [SERVICES_JSON_IN_OBJ];
                                    $obj = new stdClass();
                                }
                            }

                            array_push($stk, [
                                'what' => SERVICES_JSON_SLICE,
                                'where' => 0,
                                'delim' => false
                            ]);

                            $chrs = $this->substr8($str, 1, -1);
                            $chrs = $this->reduce_string($chrs);

                            if($chrs == '')
                            {
                                if(reset($stk) == SERVICES_JSON_IN_ARR)
                                {
                                    return $arr;
                                }
                                else
                                {
                                    return $obj;
                                }
                            }

                            // print("\nparsing {$chrs}\n");

                            $strlen_chrs = $this->strlen8($chrs);

                            for($c = 0; $c <= $strlen_chrs; ++$c)
                            {
                                $top = end($stk);
                                $substr_chrs_c_2 = $this->substr8($chrs, $c, 2);

                                if(($c == $strlen_chrs) || (($chrs[$c] == ',') && ($top['what'] == SERVICES_JSON_SLICE)))
                                {
                                    // found a comma that is not inside a string, array, etc.,
                                    // OR we've reached the end of the character list
                                    $slice = $this->substr8($chrs, $top['where'], ($c - $top['where']));
                                    array_push($stk, [
                                        'what' => SERVICES_JSON_SLICE,
                                        'where' => ($c + 1),
                                        'delim' => false
                                    ]);
                                    // print("Found split at {$c}: ".$this->substr8($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                                    if(reset($stk) == SERVICES_JSON_IN_ARR)
                                    {
                                        // we are in an array, so just push an element onto the stack
                                        array_push($arr, $this->decode($slice));
                                    }
                                    elseif(reset($stk) == SERVICES_JSON_IN_OBJ)
                                    {
                                        // we are in an object, so figure
                                        // out the property name and set an
                                        // element in an associative array,
                                        // for now
                                        $parts = [];

                                        if(preg_match('/^\s*(["\'].*[^\\\]["\'])\s*:/Uis', $slice, $parts))
                                        {
                                            // "name":value pair
                                            $key = $this->decode($parts[1]);
                                            $val = $this->decode(trim(substr($slice, strlen($parts[0])), ", \t\n\r\0\x0B"));
                                            if($this->use & SERVICES_JSON_LOOSE_TYPE)
                                            {
                                                $obj[$key] = $val;
                                            }
                                            else
                                            {
                                                /** @noinspection NativeMemberUsageInspection */
                                                $obj->$key = $val;
                                            }
                                        }
                                        elseif(preg_match('/^\s*(\w+)\s*:/Uis', $slice, $parts))
                                        {
                                            // name:value pair, where name is unquoted
                                            $key = $parts[1];
                                            $val = $this->decode(trim(substr($slice, strlen($parts[0])), ", \t\n\r\0\x0B"));

                                            if($this->use & SERVICES_JSON_LOOSE_TYPE)
                                            {
                                                $obj[$key] = $val;
                                            }
                                            else
                                            {
                                                /** @noinspection NativeMemberUsageInspection */
                                                $obj->$key = $val;
                                            }
                                        }
                                    }
                                }
                                elseif((($chrs[$c] == '"') || ($chrs[$c] == "'")) && ($top['what'] != SERVICES_JSON_IN_STR))
                                {
                                    // found a quote, and we are not inside a string
                                    array_push($stk, [
                                        'what' => SERVICES_JSON_IN_STR,
                                        'where' => $c,
                                        'delim' => $chrs[$c]
                                    ]);
                                    // print("Found start of string at {$c}\n");

                                }
                                elseif(($chrs[$c] == $top['delim']) && ($top['what'] == SERVICES_JSON_IN_STR) && (($this->strlen8($this->substr8($chrs, 0, $c)) - $this->strlen8(rtrim($this->substr8($chrs, 0, $c), '\\'))) % 2 !== 1))
                                {
                                    // found a quote, we're in a string, and it's not escaped
                                    // we know that it's not escaped because there is _not_ an
                                    // odd number of backslashes at the end of the string so far
                                    array_pop($stk);
                                    // print("Found end of string at {$c}: ".$this->substr8($chrs, $top['where'], (1 + 1 + $c - $top['where']))."\n");

                                }
                                elseif(
                                    ($chrs[$c] == '[') && in_array($top['what'], [
                                        SERVICES_JSON_SLICE,
                                        SERVICES_JSON_IN_ARR,
                                        SERVICES_JSON_IN_OBJ
                                    ])
                                )
                                {
                                    // found a left-bracket, and we are in an array, object, or slice
                                    array_push($stk, ['what' => SERVICES_JSON_IN_ARR, 'where' => $c, 'delim' => false]);
                                    // print("Found start of array at {$c}\n");

                                }
                                elseif(($chrs[$c] == ']') && ($top['what'] == SERVICES_JSON_IN_ARR))
                                {
                                    // found a right-bracket, and we're in an array
                                    array_pop($stk);
                                    // print("Found end of array at {$c}: ".$this->substr8($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                                }
                                elseif(
                                    ($chrs[$c] == '{') && in_array($top['what'], [
                                        SERVICES_JSON_SLICE,
                                        SERVICES_JSON_IN_ARR,
                                        SERVICES_JSON_IN_OBJ
                                    ])
                                )
                                {
                                    // found a left-brace, and we are in an array, object, or slice
                                    array_push($stk, ['what' => SERVICES_JSON_IN_OBJ, 'where' => $c, 'delim' => false]);
                                    // print("Found start of object at {$c}\n");

                                }
                                elseif(($chrs[$c] == '}') && ($top['what'] == SERVICES_JSON_IN_OBJ))
                                {
                                    // found a right-brace, and we're in an object
                                    array_pop($stk);
                                    // print("Found end of object at {$c}: ".$this->substr8($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                                }
                                elseif(
                                    ($substr_chrs_c_2 == '/*') && in_array($top['what'], [
                                        SERVICES_JSON_SLICE,
                                        SERVICES_JSON_IN_ARR,
                                        SERVICES_JSON_IN_OBJ
                                    ])
                                )
                                {
                                    // found a comment start, and we are in an array, object, or slice
                                    array_push($stk, ['what' => SERVICES_JSON_IN_CMT, 'where' => $c, 'delim' => false]);
                                    $c++;
                                    // print("Found start of comment at {$c}\n");

                                }
                                elseif(($substr_chrs_c_2 == '*/') && ($top['what'] == SERVICES_JSON_IN_CMT))
                                {
                                    // found a comment end, and we're in one now
                                    array_pop($stk);
                                    $c++;

                                    for($i = $top['where']; $i <= $c; ++$i)
                                    {
                                        $chrs = substr_replace($chrs, ' ', $i, 1);
                                    }
                                    // print("Found end of comment at {$c}: ".$this->substr8($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                                }
                            }

                            if(reset($stk) == SERVICES_JSON_IN_ARR)
                            {
                                return $arr;
                            }
                            elseif(reset($stk) == SERVICES_JSON_IN_OBJ)
                            {
                                return $obj;
                            }
                        }
                }
            }

            function isError($data, $code = null)
            {
                _deprecated_function(__METHOD__, '5.3.0', 'The PHP native JSON extension');

                if(class_exists('pear'))
                {
                    return PEAR::isError($data, $code);
                }
                elseif(is_object($data) && ($data instanceof services_json_error || is_subclass_of($data, 'services_json_error')))
                {
                    return true;
                }

                return false;
            }

            function strlen8($str)
            {
                _deprecated_function(__METHOD__, '5.3.0', 'The PHP native JSON extension');

                if($this->_mb_strlen)
                {
                    return mb_strlen($str, "8bit");
                }

                return strlen($str);
            }

            function substr8($string, $start, $length = false)
            {
                _deprecated_function(__METHOD__, '5.3.0', 'The PHP native JSON extension');

                if($length === false)
                {
                    $length = $this->strlen8($string) - $start;
                }
                if($this->_mb_substr)
                {
                    return mb_substr($string, $start, $length, "8bit");
                }

                return substr($string, $start, $length);
            }
        }

        if(class_exists('PEAR_Error'))
        {
            class Services_JSON_Error extends PEAR_Error
            {
                function __construct(
                    $message = 'unknown error', $code = null, $mode = null, $options = null, $userinfo = null
                ) {
                    _deprecated_function(__METHOD__, '5.3.0', 'The PHP native JSON extension');

                    parent::PEAR_Error($message, $code, $mode, $options, $userinfo);
                }

                public function Services_JSON_Error(
                    $message = 'unknown error', $code = null, $mode = null, $options = null, $userinfo = null
                ) {
                    _deprecated_constructor('Services_JSON_Error', '5.3.0', get_class($this));
                    self::__construct($message, $code, $mode, $options, $userinfo);
                }
            }
        }
        else
        {
            class Services_JSON_Error
            {
                function __construct(
                    $message = 'unknown error', $code = null, $mode = null, $options = null, $userinfo = null
                ) {
                    _deprecated_function(__METHOD__, '5.3.0', 'The PHP native JSON extension');
                }

                public function Services_JSON_Error(
                    $message = 'unknown error', $code = null, $mode = null, $options = null, $userinfo = null
                ) {
                    _deprecated_constructor('Services_JSON_Error', '5.3.0', get_class($this));
                    self::__construct($message, $code, $mode, $options, $userinfo);
                }
            }
        }

    endif;
