<?php

    if(! class_exists('POMO_Reader', false)) :
        #[AllowDynamicProperties]
        class POMO_Reader
        {
            public $endian = 'little';

            public $_pos;

            public $is_overloaded;

            public function __construct()
            {
                if(function_exists('mb_substr') && ((int) ini_get('mbstring.func_overload') & 2) // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.mbstring_func_overloadDeprecated
                )
                {
                    $this->is_overloaded = true;
                }
                else
                {
                    $this->is_overloaded = false;
                }

                $this->_pos = 0;
            }

            public function POMO_Reader()
            {
                _deprecated_constructor(self::class, '5.4.0', static::class);
                $this->__construct();
            }

            public function setEndian($endian)
            { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
                $this->endian = $endian;
            }

            public function readint32()
            {
                $bytes = $this->read(4);
                if(4 !== $this->strlen($bytes))
                {
                    return false;
                }
                $endian_letter = ('big' === $this->endian) ? 'N' : 'V';
                $int = unpack($endian_letter, $bytes);

                return reset($int);
            }

            public function readint32array($count)
            {
                $bytes = $this->read(4 * $count);
                if(4 * $count !== $this->strlen($bytes))
                {
                    return false;
                }
                $endian_letter = ('big' === $this->endian) ? 'N' : 'V';

                return unpack($endian_letter.$count, $bytes);
            }

            public function substr($input_string, $start, $length)
            {
                if($this->is_overloaded)
                {
                    return mb_substr($input_string, $start, $length, 'ascii');
                }
                else
                {
                    return substr($input_string, $start, $length);
                }
            }

            public function strlen($input_string)
            {
                if($this->is_overloaded)
                {
                    return mb_strlen($input_string, 'ascii');
                }
                else
                {
                    return strlen($input_string);
                }
            }

            public function str_split($input_string, $chunk_size)
            {
                if(function_exists('str_split'))
                {
                    return str_split($input_string, $chunk_size);
                }
                else
                {
                    $length = $this->strlen($input_string);
                    $out = [];
                    for($i = 0; $i < $length; $i += $chunk_size)
                    {
                        $out[] = $this->substr($input_string, $i, $chunk_size);
                    }

                    return $out;
                }
            }

            public function pos()
            {
                return $this->_pos;
            }

            public function is_resource()
            {
                return true;
            }

            public function close()
            {
                return true;
            }
        }
    endif;

    if(! class_exists('POMO_FileReader', false)) :
        class POMO_FileReader extends POMO_Reader
        {
            public $_f;

            public function __construct($filename)
            {
                parent::__construct();
                $this->_f = fopen($filename, 'rb');
            }

            public function POMO_FileReader($filename)
            {
                _deprecated_constructor(self::class, '5.4.0', static::class);
                $this->__construct($filename);
            }

            public function read($bytes)
            {
                return fread($this->_f, $bytes);
            }

            public function seekto($pos)
            {
                if(-1 === fseek($this->_f, $pos, SEEK_SET))
                {
                    return false;
                }
                $this->_pos = $pos;

                return true;
            }

            public function is_resource()
            {
                return is_resource($this->_f);
            }

            public function feof()
            {
                return feof($this->_f);
            }

            public function close()
            {
                return fclose($this->_f);
            }

            public function read_all()
            {
                return stream_get_contents($this->_f);
            }
        }
    endif;

    if(! class_exists('POMO_StringReader', false)) :

        class POMO_StringReader extends POMO_Reader
        {
            public $_str = '';

            public function __construct($str = '')
            {
                parent::__construct();
                $this->_str = $str;
                $this->_pos = 0;
            }

            public function POMO_StringReader($str = '')
            {
                _deprecated_constructor(self::class, '5.4.0', static::class);
                $this->__construct($str);
            }

            public function read($bytes)
            {
                $data = $this->substr($this->_str, $this->_pos, $bytes);
                $this->_pos += $bytes;
                if($this->strlen($this->_str) < $this->_pos)
                {
                    $this->_pos = $this->strlen($this->_str);
                }

                return $data;
            }

            public function seekto($pos)
            {
                $this->_pos = $pos;
                if($this->strlen($this->_str) < $this->_pos)
                {
                    $this->_pos = $this->strlen($this->_str);
                }

                return $this->_pos;
            }

            public function length()
            {
                return $this->strlen($this->_str);
            }

            public function read_all()
            {
                return $this->substr($this->_str, $this->_pos, $this->strlen($this->_str));
            }
        }
    endif;

    if(! class_exists('POMO_CachedFileReader', false)) :

        class POMO_CachedFileReader extends POMO_StringReader
        {
            public function __construct($filename)
            {
                parent::__construct();
                $this->_str = file_get_contents($filename);
                if(false === $this->_str)
                {
                    return false;
                }
                $this->_pos = 0;
            }

            public function POMO_CachedFileReader($filename)
            {
                _deprecated_constructor(self::class, '5.4.0', static::class);
                $this->__construct($filename);
            }
        }
    endif;

    if(! class_exists('POMO_CachedIntFileReader', false)) :

        class POMO_CachedIntFileReader extends POMO_CachedFileReader
        {
            public function __construct($filename)
            {
                parent::__construct($filename);
            }

            public function POMO_CachedIntFileReader($filename)
            {
                _deprecated_constructor(self::class, '5.4.0', static::class);
                $this->__construct($filename);
            }
        }
    endif;
