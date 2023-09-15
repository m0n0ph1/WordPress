<?php

    class Sniffer
    {
        public $file;

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
                $official = strtolower(trim($official));

                if($official === 'unknown/unknown' || $official === 'application/unknown')
                {
                    return $this->unknown();
                }
                elseif(substr($official, -4) === '+xml' || $official === 'text/xml' || $official === 'application/xml')
                {
                }
                elseif(strpos($official, 'image/') === 0)
                {
                    if($return = $this->image())
                    {
                        return $return;
                    }
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
            if(strpos($this->file->body, "\xFE\xFF") === 0 || strpos($this->file->body, "\xFF\xFE") === 0 || strpos($this->file->body, "\x00\x00\xFE\xFF") === 0 || strpos($this->file->body, "\xEF\xBB\xBF") === 0)
            {
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
            elseif(strpos($this->file->body, '%PDF-') === 0)
            {
                return 'application/pdf';
            }
            elseif(strpos($this->file->body, '%!PS-Adobe-') === 0)
            {
                return 'application/postscript';
            }
            elseif(strpos($this->file->body, 'GIF87a') === 0 || strpos($this->file->body, 'GIF89a') === 0)
            {
                return 'image/gif';
            }
            elseif(strpos($this->file->body, "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A") === 0)
            {
                return 'image/png';
            }
            elseif(strpos($this->file->body, "\xFF\xD8\xFF") === 0)
            {
                return 'image/jpeg';
            }
            elseif(strpos($this->file->body, "\x42\x4D") === 0)
            {
                return 'image/bmp';
            }
            elseif(strpos($this->file->body, "\x00\x00\x01\x00") === 0)
            {
                return 'image/vnd.microsoft.icon';
            }

            return $this->text_or_binary();
        }

        public function image()
        {
            if(strpos($this->file->body, 'GIF87a') === 0 || strpos($this->file->body, 'GIF89a') === 0)
            {
                return 'image/gif';
            }
            elseif(strpos($this->file->body, "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A") === 0)
            {
                return 'image/png';
            }
            elseif(strpos($this->file->body, "\xFF\xD8\xFF") === 0)
            {
                return 'image/jpeg';
            }
            elseif(strpos($this->file->body, "\x42\x4D") === 0)
            {
                return 'image/bmp';
            }
            elseif(strpos($this->file->body, "\x00\x00\x01\x00") === 0)
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
