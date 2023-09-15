<?php

    #[AllowDynamicProperties]
    class WP_Http_Encoding
    {
        public static function compress($raw, $level = 9, $supports = null)
        {
            return gzdeflate($raw, $level);
        }

        public static function decompress($compressed, $length = null)
        {
            if(empty($compressed))
            {
                return $compressed;
            }

            $decompressed = @gzinflate($compressed);
            if(false !== $decompressed)
            {
                return $decompressed;
            }

            $decompressed = self::compatible_gzinflate($compressed);
            if(false !== $decompressed)
            {
                return $decompressed;
            }

            $decompressed = @gzuncompress($compressed);
            if(false !== $decompressed)
            {
                return $decompressed;
            }

            if(function_exists('gzdecode'))
            {
                $decompressed = @gzdecode($compressed);

                if(false !== $decompressed)
                {
                    return $decompressed;
                }
            }

            return $compressed;
        }

        public static function compatible_gzinflate($gz_data)
        {
            // Compressed data might contain a full header, if so strip it for gzinflate().
            if(str_starts_with($gz_data, "\x1f\x8b\x08"))
            {
                $i = 10;
                $flg = ord(substr($gz_data, 3, 1));
                if($flg > 0)
                {
                    if($flg & 4)
                    {
                        [$xlen] = unpack('v', substr($gz_data, $i, 2));
                        $i = $i + 2 + $xlen;
                    }
                    if($flg & 8)
                    {
                        $i = strpos($gz_data, "\0", $i) + 1;
                    }
                    if($flg & 16)
                    {
                        $i = strpos($gz_data, "\0", $i) + 1;
                    }
                    if($flg & 2)
                    {
                        $i = $i + 2;
                    }
                }
                $decompressed = @gzinflate(substr($gz_data, $i, -8));
                if(false !== $decompressed)
                {
                    return $decompressed;
                }
            }

            // Compressed data from java.util.zip.Deflater amongst others.
            $decompressed = @gzinflate(substr($gz_data, 2));
            if(false !== $decompressed)
            {
                return $decompressed;
            }

            return false;
        }

        public static function accept_encoding($url, $args)
        {
            $type = [];
            $compression_enabled = self::is_available();

            if(! $args['decompress'])
            { // Decompression specifically disabled.
                $compression_enabled = false;
            }
            elseif($args['stream'])
            { // Disable when streaming to file.
                $compression_enabled = false;
            }
            elseif(isset($args['limit_response_size']))
            { // If only partial content is being requested, we won't be able to decompress it.
                $compression_enabled = false;
            }

            if($compression_enabled)
            {
                if(function_exists('gzinflate'))
                {
                    $type[] = 'deflate;q=1.0';
                }

                if(function_exists('gzuncompress'))
                {
                    $type[] = 'compress;q=0.5';
                }

                if(function_exists('gzdecode'))
                {
                    $type[] = 'gzip;q=0.5';
                }
            }

            $type = apply_filters('wp_http_accept_encoding', $type, $url, $args);

            return implode(', ', $type);
        }

        public static function is_available()
        {
            return (function_exists('gzuncompress') || function_exists('gzdeflate') || function_exists('gzinflate'));
        }

        public static function content_encoding()
        {
            return 'deflate';
        }

        public static function should_decode($headers)
        {
            if(is_array($headers))
            {
                if(array_key_exists('content-encoding', $headers) && ! empty($headers['content-encoding']))
                {
                    return true;
                }
            }
            elseif(is_string($headers))
            {
                return (stripos($headers, 'content-encoding:') !== false);
            }

            return false;
        }
    }
