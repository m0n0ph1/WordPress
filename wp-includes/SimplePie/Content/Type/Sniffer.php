<?php

    class SimplePie_Content_Type_Sniffer
    {
        var $file;

        public function __construct($file)
        {
            $this->file = $file;
        }

        public function get_type()
        {
            if(isset($this->file->headers['content-type']))
            {
                if(! isset($this->file->headers['content-encoding']) && ($this->file->headers['content-type'] === 'text/plain' || $this->file->headers['content-type'] === 'text/plain; charset=ISO-8859-1' || $this->file->headers['content-type'] === 'text/plain; charset=iso-8859-1' || $this->file->headers['content-type'] === 'text/plain; charset=UTF-8'))
                {
                    return $this->text_or_binary();
                }

                if(($pos = strpos($this->file->headers['content-type'], ';')) !== false)
                {
                    $official = substr($this->file->headers['content-type'], 0, $pos);
                }
                else
                {
                    $official = $this->file->headers['content-type'];
                }
                $official = trim(strtolower($official));

                if($official === 'unknown/unknown' || $official === 'application/unknown')
                {
                    return $this->unknown();
                }
                elseif(substr($official, -4) === '+xml' || $official === 'text/xml' || $official === 'application/xml')
                {
                    return $official;
                }
                elseif(substr($official, 0, 6) === 'image/')
                {
                    if($return = $this->image())
                    {
                        return $return;
                    }

                    return $official;
                }
                elseif($official === 'text/html')
                {
                    return $this->feed_or_html();
                }

                return $official;
            }

            return $this->unknown();
        }

        public function text_or_binary()
        {
            if(substr($this->file->body, 0, 2) === "\xFE\xFF" || substr($this->file->body, 0, 2) === "\xFF\xFE" || substr($this->file->body, 0, 4) === "\x00\x00\xFE\xFF" || substr($this->file->body, 0, 3) === "\xEF\xBB\xBF")
            {
                return 'text/plain';
            }
            elseif(preg_match('/[\x00-\x08\x0E-\x1A\x1C-\x1F]/', $this->file->body))
            {
                return 'application/octet-stream';
            }

            return 'text/plain';
        }

        public function unknown()
        {
            $ws = strspn($this->file->body, "\x09\x0A\x0B\x0C\x0D\x20");
            if(strtolower(substr($this->file->body, $ws, 14)) === '<!doctype html' || strtolower(substr($this->file->body, $ws, 5)) === '<html' || strtolower(substr($this->file->body, $ws, 7)) === '<script')
            {
                return 'text/html';
            }
            elseif(substr($this->file->body, 0, 5) === '%PDF-')
            {
                return 'application/pdf';
            }
            elseif(substr($this->file->body, 0, 11) === '%!PS-Adobe-')
            {
                return 'application/postscript';
            }
            elseif(substr($this->file->body, 0, 6) === 'GIF87a' || substr($this->file->body, 0, 6) === 'GIF89a')
            {
                return 'image/gif';
            }
            elseif(substr($this->file->body, 0, 8) === "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A")
            {
                return 'image/png';
            }
            elseif(substr($this->file->body, 0, 3) === "\xFF\xD8\xFF")
            {
                return 'image/jpeg';
            }
            elseif(substr($this->file->body, 0, 2) === "\x42\x4D")
            {
                return 'image/bmp';
            }
            elseif(substr($this->file->body, 0, 4) === "\x00\x00\x01\x00")
            {
                return 'image/vnd.microsoft.icon';
            }

            return $this->text_or_binary();
        }

        public function image()
        {
            if(substr($this->file->body, 0, 6) === 'GIF87a' || substr($this->file->body, 0, 6) === 'GIF89a')
            {
                return 'image/gif';
            }
            elseif(substr($this->file->body, 0, 8) === "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A")
            {
                return 'image/png';
            }
            elseif(substr($this->file->body, 0, 3) === "\xFF\xD8\xFF")
            {
                return 'image/jpeg';
            }
            elseif(substr($this->file->body, 0, 2) === "\x42\x4D")
            {
                return 'image/bmp';
            }
            elseif(substr($this->file->body, 0, 4) === "\x00\x00\x01\x00")
            {
                return 'image/vnd.microsoft.icon';
            }

            return false;
        }

        public function feed_or_html()
        {
            $len = strlen($this->file->body);
            $pos = strspn($this->file->body, "\x09\x0A\x0D\x20\xEF\xBB\xBF");

            while($pos < $len)
            {
                switch($this->file->body[$pos])
                {
                    case "\x09":
                    case "\x0A":
                    case "\x0D":
                    case "\x20":
                        $pos += strspn($this->file->body, "\x09\x0A\x0D\x20", $pos);
                        continue 2;

                    case '<':
                        $pos++;
                        break;

                    default:
                        return 'text/html';
                }

                if(substr($this->file->body, $pos, 3) === '!--')
                {
                    $pos += 3;
                    if($pos < $len && ($pos = strpos($this->file->body, '-->', $pos)) !== false)
                    {
                        $pos += 3;
                    }
                    else
                    {
                        return 'text/html';
                    }
                }
                elseif(substr($this->file->body, $pos, 1) === '!')
                {
                    if($pos < $len && ($pos = strpos($this->file->body, '>', $pos)) !== false)
                    {
                        $pos++;
                    }
                    else
                    {
                        return 'text/html';
                    }
                }
                elseif(substr($this->file->body, $pos, 1) === '?')
                {
                    if($pos < $len && ($pos = strpos($this->file->body, '?>', $pos)) !== false)
                    {
                        $pos += 2;
                    }
                    else
                    {
                        return 'text/html';
                    }
                }
                elseif(substr($this->file->body, $pos, 3) === 'rss' || substr($this->file->body, $pos, 7) === 'rdf:RDF')
                {
                    return 'application/rss+xml';
                }
                elseif(substr($this->file->body, $pos, 4) === 'feed')
                {
                    return 'application/atom+xml';
                }
                else
                {
                    return 'text/html';
                }
            }

            return 'text/html';
        }
    }
