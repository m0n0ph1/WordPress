<?php

    class gzdecode
    {
        public $compressed_data;

        public $compressed_size;

        public $min_compressed_size = 18;

        public $position = 0;

        public $flags;

        public $data;

        public $MTIME;

        public $XFL;

        public $OS;

        public $SI1;

        public $SI2;

        public $extra_field;

        public $filename;

        public $comment;

        public function __construct($data)
        {
            $this->compressed_data = $data;
            $this->compressed_size = strlen($data);
        }

        public function __set($name, $value)
        {
            trigger_error("Cannot write property $name", E_USER_ERROR);
        }

        public function parse()
        {
            if($this->compressed_size >= $this->min_compressed_size)
            {
                // Check ID1, ID2, and CM
                if(strpos($this->compressed_data, "\x1F\x8B\x08") !== 0)
                {
                    return false;
                }

                // Get the FLG (FLaGs)
                $this->flags = ord($this->compressed_data[3]);

                // FLG bits above (1 << 4) are reserved
                if($this->flags > 0x1F)
                {
                    return false;
                }

                // Advance the pointer after the above
                $this->position += 4;

                // MTIME
                $mtime = substr($this->compressed_data, $this->position, 4);
                // Reverse the string if we're on a big-endian arch because l is the only signed long and is machine endianness
                if(current(unpack('S', "\x00\x01")) === 1)
                {
                    $mtime = strrev($mtime);
                }
                $this->MTIME = current(unpack('l', $mtime));
                $this->position += 4;

                // Get the XFL (eXtra FLags)
                $this->XFL = ord($this->compressed_data[$this->position++]);

                // Get the OS (Operating System)
                $this->OS = ord($this->compressed_data[$this->position++]);

                // Parse the FEXTRA
                if($this->flags & 4)
                {
                    // Read subfield IDs
                    $this->SI1 = $this->compressed_data[$this->position++];
                    $this->SI2 = $this->compressed_data[$this->position++];

                    // SI2 set to zero is reserved for future use
                    if($this->SI2 === "\x00")
                    {
                        return false;
                    }

                    // Get the length of the extra field
                    $len = current(unpack('v', substr($this->compressed_data, $this->position, 2)));
                    $this->position += 2;

                    // Check the length of the string is still valid
                    $this->min_compressed_size += $len + 4;
                    if($this->compressed_size >= $this->min_compressed_size)
                    {
                        // Set the extra field to the given data
                        $this->extra_field = substr($this->compressed_data, $this->position, $len);
                        $this->position += $len;
                    }
                    else
                    {
                        return false;
                    }
                }

                // Parse the FNAME
                if($this->flags & 8)
                {
                    // Get the length of the filename
                    $len = strcspn($this->compressed_data, "\x00", $this->position);

                    // Check the length of the string is still valid
                    $this->min_compressed_size += $len + 1;
                    if($this->compressed_size >= $this->min_compressed_size)
                    {
                        // Set the original filename to the given string
                        $this->filename = substr($this->compressed_data, $this->position, $len);
                        $this->position += $len + 1;
                    }
                    else
                    {
                        return false;
                    }
                }

                // Parse the FCOMMENT
                if($this->flags & 16)
                {
                    // Get the length of the comment
                    $len = strcspn($this->compressed_data, "\x00", $this->position);

                    // Check the length of the string is still valid
                    $this->min_compressed_size += $len + 1;
                    if($this->compressed_size >= $this->min_compressed_size)
                    {
                        // Set the original comment to the given string
                        $this->comment = substr($this->compressed_data, $this->position, $len);
                        $this->position += $len + 1;
                    }
                    else
                    {
                        return false;
                    }
                }

                // Parse the FHCRC
                if($this->flags & 2)
                {
                    // Check the length of the string is still valid
                    $this->min_compressed_size += $len + 2;
                    if($this->compressed_size >= $this->min_compressed_size)
                    {
                        // Read the CRC
                        $crc = current(unpack('v', substr($this->compressed_data, $this->position, 2)));

                        // Check the CRC matches
                        if((crc32(substr($this->compressed_data, 0, $this->position)) & 0xFFFF) === $crc)
                        {
                            $this->position += 2;
                        }
                        else
                        {
                            return false;
                        }
                    }
                    else
                    {
                        return false;
                    }
                }

                // Decompress the actual data
                if(($this->data = gzinflate(substr($this->compressed_data, $this->position, -8))) === false)
                {
                    return false;
                }

                $this->position = $this->compressed_size - 8;

                // Check CRC of data
                $crc = current(unpack('V', substr($this->compressed_data, $this->position, 4)));
                $this->position += 4;
                /*if (extension_loaded('hash') && sprintf('%u', current(unpack('V', hash('crc32b', $this->data)))) !== sprintf('%u', $crc))
                {
                    return false;
                }*/

                // Check ISIZE of data
                $isize = current(unpack('V', substr($this->compressed_data, $this->position, 4)));
                $this->position += 4;

                return sprintf('%u', strlen($this->data) & 0xFFFFFFFF) === sprintf('%u', $isize);
            }

            return false;
        }
    }
