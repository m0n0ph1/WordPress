<?php

    class SimplePie_Enclosure
    {
        var $bitrate;

        var $captions;

        var $categories;

        var $channels;

        var $copyright;

        var $credits;

        var $description;

        var $duration;

        var $expression;

        var $framerate;

        var $handler;

        var $hashes;

        var $height;

        var $javascript;

        var $keywords;

        var $lang;

        var $length;

        var $link;

        var $medium;

        var $player;

        var $ratings;

        var $restrictions;

        var $samplingrate;

        var $thumbnails;

        var $title;

        var $type;

        var $width;

        public function __construct(
            $link = null, $type = null, $length = null, $javascript = null, $bitrate = null, $captions = null, $categories = null, $channels = null, $copyright = null, $credits = null, $description = null, $duration = null, $expression = null, $framerate = null, $hashes = null, $height = null, $keywords = null, $lang = null, $medium = null, $player = null, $ratings = null, $restrictions = null, $samplingrate = null, $thumbnails = null, $title = null, $width = null
        ) {
            $this->bitrate = $bitrate;
            $this->captions = $captions;
            $this->categories = $categories;
            $this->channels = $channels;
            $this->copyright = $copyright;
            $this->credits = $credits;
            $this->description = $description;
            $this->duration = $duration;
            $this->expression = $expression;
            $this->framerate = $framerate;
            $this->hashes = $hashes;
            $this->height = $height;
            $this->keywords = $keywords;
            $this->lang = $lang;
            $this->length = $length;
            $this->link = $link;
            $this->medium = $medium;
            $this->player = $player;
            $this->ratings = $ratings;
            $this->restrictions = $restrictions;
            $this->samplingrate = $samplingrate;
            $this->thumbnails = $thumbnails;
            $this->title = $title;
            $this->type = $type;
            $this->width = $width;

            if(class_exists('idna_convert'))
            {
                $idn = new idna_convert();
                $parsed = SimplePie_Misc::parse_url($link);
                $this->link = SimplePie_Misc::compress_parse_url($parsed['scheme'], $idn->encode($parsed['authority']), $parsed['path'], $parsed['query'], $parsed['fragment']);
            }
            $this->handler = $this->get_handler(); // Needs to load last
        }

        public function get_handler()
        {
            return $this->get_real_type(true);
        }

        public function get_real_type($find_handler = false)
        {
            // Mime-types by handler.
            $types_flash = ['application/x-shockwave-flash', 'application/futuresplash']; // Flash
            $types_fmedia = ['video/flv', 'video/x-flv', 'flv-application/octet-stream']; // Flash Media Player
            $types_quicktime = [
                'audio/3gpp',
                'audio/3gpp2',
                'audio/aac',
                'audio/x-aac',
                'audio/aiff',
                'audio/x-aiff',
                'audio/mid',
                'audio/midi',
                'audio/x-midi',
                'audio/mp4',
                'audio/m4a',
                'audio/x-m4a',
                'audio/wav',
                'audio/x-wav',
                'video/3gpp',
                'video/3gpp2',
                'video/m4v',
                'video/x-m4v',
                'video/mp4',
                'video/mpeg',
                'video/x-mpeg',
                'video/quicktime',
                'video/sd-video'
            ]; // QuickTime
            $types_wmedia = [
                'application/asx',
                'application/x-mplayer2',
                'audio/x-ms-wma',
                'audio/x-ms-wax',
                'video/x-ms-asf-plugin',
                'video/x-ms-asf',
                'video/x-ms-wm',
                'video/x-ms-wmv',
                'video/x-ms-wvx'
            ]; // Windows Media
            $types_mp3 = ['audio/mp3', 'audio/x-mp3', 'audio/mpeg', 'audio/x-mpeg']; // MP3

            if($this->get_type() !== null)
            {
                $type = strtolower($this->type);
            }
            else
            {
                $type = null;
            }

            // If we encounter an unsupported mime-type, check the file extension and guess intelligently.
            if(! in_array($type, array_merge($types_flash, $types_fmedia, $types_quicktime, $types_wmedia, $types_mp3)))
            {
                $extension = $this->get_extension();
                if($extension === null)
                {
                    return null;
                }

                switch(strtolower($extension))
                {
                    // Audio mime-types
                    case 'aac':
                    case 'adts':
                        $type = 'audio/acc';
                        break;

                    case 'aif':
                    case 'aifc':
                    case 'aiff':
                    case 'cdda':
                        $type = 'audio/aiff';
                        break;

                    case 'bwf':
                        $type = 'audio/wav';
                        break;

                    case 'kar':
                    case 'mid':
                    case 'midi':
                    case 'smf':
                        $type = 'audio/midi';
                        break;

                    case 'm4a':
                        $type = 'audio/x-m4a';
                        break;

                    case 'mp3':
                    case 'swa':
                        $type = 'audio/mp3';
                        break;

                    case 'wav':
                        $type = 'audio/wav';
                        break;

                    case 'wax':
                        $type = 'audio/x-ms-wax';
                        break;

                    case 'wma':
                        $type = 'audio/x-ms-wma';
                        break;

                    // Video mime-types
                    case '3gp':
                    case '3gpp':
                        $type = 'video/3gpp';
                        break;

                    case '3g2':
                    case '3gp2':
                        $type = 'video/3gpp2';
                        break;

                    case 'asf':
                        $type = 'video/x-ms-asf';
                        break;

                    case 'flv':
                        $type = 'video/x-flv';
                        break;

                    case 'm1a':
                    case 'm1s':
                    case 'm1v':
                    case 'm15':
                    case 'm75':
                    case 'mp2':
                    case 'mpa':
                    case 'mpeg':
                    case 'mpg':
                    case 'mpm':
                    case 'mpv':
                        $type = 'video/mpeg';
                        break;

                    case 'm4v':
                        $type = 'video/x-m4v';
                        break;

                    case 'mov':
                    case 'qt':
                        $type = 'video/quicktime';
                        break;

                    case 'mp4':
                    case 'mpg4':
                        $type = 'video/mp4';
                        break;

                    case 'sdv':
                        $type = 'video/sd-video';
                        break;

                    case 'wm':
                        $type = 'video/x-ms-wm';
                        break;

                    case 'wmv':
                        $type = 'video/x-ms-wmv';
                        break;

                    case 'wvx':
                        $type = 'video/x-ms-wvx';
                        break;

                    // Flash mime-types
                    case 'spl':
                        $type = 'application/futuresplash';
                        break;

                    case 'swf':
                        $type = 'application/x-shockwave-flash';
                        break;
                }
            }

            if($find_handler)
            {
                if(in_array($type, $types_flash))
                {
                    return 'flash';
                }
                elseif(in_array($type, $types_fmedia))
                {
                    return 'fmedia';
                }
                elseif(in_array($type, $types_quicktime))
                {
                    return 'quicktime';
                }
                elseif(in_array($type, $types_wmedia))
                {
                    return 'wmedia';
                }
                elseif(in_array($type, $types_mp3))
                {
                    return 'mp3';
                }

                return null;
            }

            return $type;
        }

        public function get_type()
        {
            if($this->type !== null)
            {
                return $this->type;
            }

            return null;
        }

        public function get_extension()
        {
            if($this->link !== null)
            {
                $url = SimplePie_Misc::parse_url($this->link);
                if($url['path'] !== '')
                {
                    return pathinfo($url['path'], PATHINFO_EXTENSION);
                }
            }

            return null;
        }

        public function __toString()
        {
            // There is no $this->data here
            return md5(serialize($this));
        }

        public function get_bitrate()
        {
            if($this->bitrate !== null)
            {
                return $this->bitrate;
            }

            return null;
        }

        public function get_caption($key = 0)
        {
            $captions = $this->get_captions();
            if(isset($captions[$key]))
            {
                return $captions[$key];
            }

            return null;
        }

        public function get_captions()
        {
            if($this->captions !== null)
            {
                return $this->captions;
            }

            return null;
        }

        public function get_category($key = 0)
        {
            $categories = $this->get_categories();
            if(isset($categories[$key]))
            {
                return $categories[$key];
            }

            return null;
        }

        public function get_categories()
        {
            if($this->categories !== null)
            {
                return $this->categories;
            }

            return null;
        }

        public function get_channels()
        {
            if($this->channels !== null)
            {
                return $this->channels;
            }

            return null;
        }

        public function get_copyright()
        {
            if($this->copyright !== null)
            {
                return $this->copyright;
            }

            return null;
        }

        public function get_credit($key = 0)
        {
            $credits = $this->get_credits();
            if(isset($credits[$key]))
            {
                return $credits[$key];
            }

            return null;
        }

        public function get_credits()
        {
            if($this->credits !== null)
            {
                return $this->credits;
            }

            return null;
        }

        public function get_description()
        {
            if($this->description !== null)
            {
                return $this->description;
            }

            return null;
        }

        public function get_duration($convert = false)
        {
            if($this->duration !== null)
            {
                if($convert)
                {
                    $time = SimplePie_Misc::time_hms($this->duration);

                    return $time;
                }

                return $this->duration;
            }

            return null;
        }

        public function get_expression()
        {
            if($this->expression !== null)
            {
                return $this->expression;
            }

            return 'full';
        }

        public function get_framerate()
        {
            if($this->framerate !== null)
            {
                return $this->framerate;
            }

            return null;
        }

        public function get_hash($key = 0)
        {
            $hashes = $this->get_hashes();
            if(isset($hashes[$key]))
            {
                return $hashes[$key];
            }

            return null;
        }

        public function get_hashes()
        {
            if($this->hashes !== null)
            {
                return $this->hashes;
            }

            return null;
        }

        public function get_height()
        {
            if($this->height !== null)
            {
                return $this->height;
            }

            return null;
        }

        public function get_language()
        {
            if($this->lang !== null)
            {
                return $this->lang;
            }

            return null;
        }

        public function get_keyword($key = 0)
        {
            $keywords = $this->get_keywords();
            if(isset($keywords[$key]))
            {
                return $keywords[$key];
            }

            return null;
        }

        public function get_keywords()
        {
            if($this->keywords !== null)
            {
                return $this->keywords;
            }

            return null;
        }

        public function get_medium()
        {
            if($this->medium !== null)
            {
                return $this->medium;
            }

            return null;
        }

        public function get_player()
        {
            if($this->player !== null)
            {
                return $this->player;
            }

            return null;
        }

        public function get_rating($key = 0)
        {
            $ratings = $this->get_ratings();
            if(isset($ratings[$key]))
            {
                return $ratings[$key];
            }

            return null;
        }

        public function get_ratings()
        {
            if($this->ratings !== null)
            {
                return $this->ratings;
            }

            return null;
        }

        public function get_restriction($key = 0)
        {
            $restrictions = $this->get_restrictions();
            if(isset($restrictions[$key]))
            {
                return $restrictions[$key];
            }

            return null;
        }

        public function get_restrictions()
        {
            if($this->restrictions !== null)
            {
                return $this->restrictions;
            }

            return null;
        }

        public function get_sampling_rate()
        {
            if($this->samplingrate !== null)
            {
                return $this->samplingrate;
            }

            return null;
        }

        public function get_size()
        {
            $length = $this->get_length();
            if($length !== null)
            {
                return round($length / 1048576, 2);
            }

            return null;
        }

        public function get_length()
        {
            if($this->length !== null)
            {
                return $this->length;
            }

            return null;
        }

        public function get_thumbnail($key = 0)
        {
            $thumbnails = $this->get_thumbnails();
            if(isset($thumbnails[$key]))
            {
                return $thumbnails[$key];
            }

            return null;
        }

        public function get_thumbnails()
        {
            if($this->thumbnails !== null)
            {
                return $this->thumbnails;
            }

            return null;
        }

        public function get_title()
        {
            if($this->title !== null)
            {
                return $this->title;
            }

            return null;
        }

        public function get_width()
        {
            if($this->width !== null)
            {
                return $this->width;
            }

            return null;
        }

        public function native_embed($options = '')
        {
            return $this->embed($options, true);
        }

        public function embed($options = '', $native = false)
        {
            // Set up defaults
            $audio = '';
            $video = '';
            $alt = '';
            $altclass = '';
            $loop = 'false';
            $width = 'auto';
            $height = 'auto';
            $bgcolor = '#ffffff';
            $mediaplayer = '';
            $widescreen = false;
            $handler = $this->get_handler();
            $type = $this->get_real_type();

            // Process options and reassign values as necessary
            if(is_array($options))
            {
                extract($options);
            }
            else
            {
                $options = explode(',', $options);
                foreach($options as $option)
                {
                    $opt = explode(':', $option, 2);
                    if(isset($opt[0], $opt[1]))
                    {
                        $opt[0] = trim($opt[0]);
                        $opt[1] = trim($opt[1]);
                        switch($opt[0])
                        {
                            case 'audio':
                                $audio = $opt[1];
                                break;

                            case 'video':
                                $video = $opt[1];
                                break;

                            case 'alt':
                                $alt = $opt[1];
                                break;

                            case 'altclass':
                                $altclass = $opt[1];
                                break;

                            case 'loop':
                                $loop = $opt[1];
                                break;

                            case 'width':
                                $width = $opt[1];
                                break;

                            case 'height':
                                $height = $opt[1];
                                break;

                            case 'bgcolor':
                                $bgcolor = $opt[1];
                                break;

                            case 'mediaplayer':
                                $mediaplayer = $opt[1];
                                break;

                            case 'widescreen':
                                $widescreen = $opt[1];
                                break;
                        }
                    }
                }
            }

            $mime = explode('/', $type, 2);
            $mime = $mime[0];

            // Process values for 'auto'
            if($width === 'auto')
            {
                if($mime === 'video')
                {
                    if($height === 'auto')
                    {
                        $width = 480;
                    }
                    elseif($widescreen)
                    {
                        $width = round((intval($height) / 9) * 16);
                    }
                    else
                    {
                        $width = round((intval($height) / 3) * 4);
                    }
                }
                else
                {
                    $width = '100%';
                }
            }

            if($height === 'auto')
            {
                if($mime === 'audio')
                {
                    $height = 0;
                }
                elseif($mime === 'video')
                {
                    if($width === 'auto')
                    {
                        if($widescreen)
                        {
                            $height = 270;
                        }
                        else
                        {
                            $height = 360;
                        }
                    }
                    elseif($widescreen)
                    {
                        $height = round((intval($width) / 16) * 9);
                    }
                    else
                    {
                        $height = round((intval($width) / 4) * 3);
                    }
                }
                else
                {
                    $height = 376;
                }
            }
            elseif($mime === 'audio')
            {
                $height = 0;
            }

            // Set proper placeholder value
            if($mime === 'audio')
            {
                $placeholder = $audio;
            }
            elseif($mime === 'video')
            {
                $placeholder = $video;
            }

            $embed = '';

            // Flash
            if($handler === 'flash')
            {
                if($native)
                {
                    $embed .= "<embed src=\"".$this->get_link()."\" pluginspage=\"http://adobe.com/go/getflashplayer\" type=\"$type\" quality=\"high\" width=\"$width\" height=\"$height\" bgcolor=\"$bgcolor\" loop=\"$loop\"></embed>";
                }
                else
                {
                    $embed .= "<script type='text/javascript'>embed_flash('$bgcolor', '$width', '$height', '".$this->get_link()."', '$loop', '$type');</script>";
                }
            }

            // Flash Media Player file types.
            // Preferred handler for MP3 file types.
            elseif($handler === 'fmedia' || ($handler === 'mp3' && $mediaplayer !== ''))
            {
                $height += 20;
                if($native)
                {
                    $embed .= "<embed src=\"$mediaplayer\" pluginspage=\"http://adobe.com/go/getflashplayer\" type=\"application/x-shockwave-flash\" quality=\"high\" width=\"$width\" height=\"$height\" wmode=\"transparent\" flashvars=\"file=".rawurlencode($this->get_link().'?file_extension=.'.$this->get_extension())."&autostart=false&repeat=$loop&showdigits=true&showfsbutton=false\"></embed>";
                }
                else
                {
                    $embed .= "<script type='text/javascript'>embed_flv('$width', '$height', '".rawurlencode($this->get_link().'?file_extension=.'.$this->get_extension())."', '$placeholder', '$loop', '$mediaplayer');</script>";
                }
            }

            // QuickTime 7 file types.  Need to test with QuickTime 6.
            // Only handle MP3's if the Flash Media Player is not present.
            elseif($handler === 'quicktime' || ($handler === 'mp3' && $mediaplayer === ''))
            {
                $height += 16;
                if($native)
                {
                    if($placeholder !== '')
                    {
                        $embed .= "<embed type=\"$type\" style=\"cursor:hand; cursor:pointer;\" href=\"".$this->get_link()."\" src=\"$placeholder\" width=\"$width\" height=\"$height\" autoplay=\"false\" target=\"myself\" controller=\"false\" loop=\"$loop\" scale=\"aspect\" bgcolor=\"$bgcolor\" pluginspage=\"http://apple.com/quicktime/download/\"></embed>";
                    }
                    else
                    {
                        $embed .= "<embed type=\"$type\" style=\"cursor:hand; cursor:pointer;\" src=\"".$this->get_link()."\" width=\"$width\" height=\"$height\" autoplay=\"false\" target=\"myself\" controller=\"true\" loop=\"$loop\" scale=\"aspect\" bgcolor=\"$bgcolor\" pluginspage=\"http://apple.com/quicktime/download/\"></embed>";
                    }
                }
                else
                {
                    $embed .= "<script type='text/javascript'>embed_quicktime('$type', '$bgcolor', '$width', '$height', '".$this->get_link()."', '$placeholder', '$loop');</script>";
                }
            } // Windows Media
            elseif($handler === 'wmedia')
            {
                $height += 45;
                if($native)
                {
                    $embed .= "<embed type=\"application/x-mplayer2\" src=\"".$this->get_link()."\" autosize=\"1\" width=\"$width\" height=\"$height\" showcontrols=\"1\" showstatusbar=\"0\" showdisplay=\"0\" autostart=\"0\"></embed>";
                }
                else
                {
                    $embed .= "<script type='text/javascript'>embed_wmedia('$width', '$height', '".$this->get_link()."');</script>";
                }
            } // Everything else
            else
            {
                $embed .= '<a href="'.$this->get_link().'" class="'.$altclass.'">'.$alt.'</a>';
            }

            return $embed;
        }

        public function get_link()
        {
            if($this->link !== null)
            {
                return urldecode($this->link);
            }

            return null;
        }
    }
