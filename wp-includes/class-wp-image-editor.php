<?php

    #[AllowDynamicProperties]
    abstract class WP_Image_Editor
    {
        protected $file = null;

        protected $size = null;

        protected $mime_type = null;

        protected $output_mime_type = null;

        protected $default_mime_type = 'image/jpeg';

        protected $quality = false;

        // Deprecated since 5.8.1. See get_default_quality() below.
        protected $default_quality = 82;

        public function __construct($file)
        {
            $this->file = $file;
        }

        public static function test($args = [])
        {
            return false;
        }

        abstract public function load();

        abstract public function save($destfilename = null, $mime_type = null);

        abstract public function resize($max_w, $max_h, $crop = false);

        abstract public function multi_resize($sizes);

        abstract public function crop($src_x, $src_y, $src_w, $src_h, $dst_w = null, $dst_h = null, $src_abs = false);

        abstract public function stream($mime_type = null);

        public function get_quality()
        {
            if(! $this->quality)
            {
                $this->set_quality();
            }

            return $this->quality;
        }

        public function set_quality($quality = null)
        {
            // Use the output mime type if present. If not, fall back to the input/initial mime type.
            $mime_type = ! empty($this->output_mime_type) ? $this->output_mime_type : $this->mime_type;
            // Get the default quality setting for the mime type.
            $default_quality = $this->get_default_quality($mime_type);

            if(null === $quality)
            {
                $quality = apply_filters('wp_editor_set_quality', $default_quality, $mime_type);

                if('image/jpeg' === $mime_type)
                {
                    $quality = apply_filters('jpeg_quality', $quality, 'image_resize');
                }

                if($quality < 0 || $quality > 100)
                {
                    $quality = $default_quality;
                }
            }

            // Allow 0, but squash to 1 due to identical images in GD, and for backward compatibility.
            if(0 === $quality)
            {
                $quality = 1;
            }

            if(($quality >= 1) && ($quality <= 100))
            {
                $this->quality = $quality;

                return true;
            }
            else
            {
                return new WP_Error('invalid_image_quality', __('Attempted to set image quality outside of the range [1,100].'));
            }
        }

        public function generate_filename($suffix = null, $dest_path = null, $extension = null)
        {
            // $suffix will be appended to the destination filename, just before the extension.
            if(! $suffix)
            {
                $suffix = $this->get_suffix();
            }

            $dir = pathinfo($this->file, PATHINFO_DIRNAME);
            $ext = pathinfo($this->file, PATHINFO_EXTENSION);

            $name = wp_basename($this->file, ".$ext");
            $new_ext = strtolower($extension ? $extension : $ext);

            if(! is_null($dest_path))
            {
                if(wp_is_stream($dest_path))
                {
                    $dir = $dest_path;
                }
                else
                {
                    $_dest_path = realpath($dest_path);
                    if($_dest_path)
                    {
                        $dir = $_dest_path;
                    }
                }
            }

            return trailingslashit($dir)."{$name}-{$suffix}.{$new_ext}";
        }

        public function get_suffix()
        {
            if(! $this->get_size())
            {
                return false;
            }

            return "{$this->size['width']}x{$this->size['height']}";
        }

        public function get_size()
        {
            return $this->size;
        }

        public function maybe_exif_rotate()
        {
            $orientation = null;

            if(is_callable('exif_read_data') && 'image/jpeg' === $this->mime_type)
            {
                $exif_data = @exif_read_data($this->file);

                if(! empty($exif_data['Orientation']))
                {
                    $orientation = (int) $exif_data['Orientation'];
                }
            }

            $orientation = apply_filters('wp_image_maybe_exif_rotate', $orientation, $this->file);

            if(! $orientation || 1 === $orientation)
            {
                return false;
            }

            switch($orientation)
            {
                case 2:
                    // Flip horizontally.
                    $result = $this->flip(false, true);
                    break;
                case 3:
                    /*
                     * Rotate 180 degrees or flip horizontally and vertically.
                     * Flipping seems faster and uses less resources.
                     */ $result = $this->flip(true, true);
                    break;
                case 4:
                    // Flip vertically.
                    $result = $this->flip(true, false);
                    break;
                case 5:
                    // Rotate 90 degrees counter-clockwise and flip vertically.
                    $result = $this->rotate(90);

                    if(! is_wp_error($result))
                    {
                        $result = $this->flip(true, false);
                    }

                    break;
                case 6:
                    // Rotate 90 degrees clockwise (270 counter-clockwise).
                    $result = $this->rotate(270);
                    break;
                case 7:
                    // Rotate 90 degrees counter-clockwise and flip horizontally.
                    $result = $this->rotate(90);

                    if(! is_wp_error($result))
                    {
                        $result = $this->flip(false, true);
                    }

                    break;
                case 8:
                    // Rotate 90 degrees counter-clockwise.
                    $result = $this->rotate(90);
                    break;
            }

            return $result;
        }

        abstract public function flip($horz, $vert);

        abstract public function rotate($angle);

        protected function update_size($width = null, $height = null)
        {
            $this->size = [
                'width' => (int) $width,
                'height' => (int) $height,
            ];

            return true;
        }

        protected function get_default_quality($mime_type)
        {
            switch($mime_type)
            {
                case 'image/webp':
                    $quality = 86;
                    break;
                case 'image/jpeg':
                default:
                    $quality = $this->default_quality;
            }

            return $quality;
        }

        protected function get_output_format($filename = null, $mime_type = null)
        {
            $new_ext = null;

            // By default, assume specified type takes priority.
            if($mime_type)
            {
                $new_ext = $this->get_extension($mime_type);
            }

            if($filename)
            {
                $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $file_mime = $this->get_mime_type($file_ext);
            }
            else
            {
                // If no file specified, grab editor's current extension and mime-type.
                $file_ext = strtolower(pathinfo($this->file, PATHINFO_EXTENSION));
                $file_mime = $this->mime_type;
            }

            /*
             * Check to see if specified mime-type is the same as type implied by
             * file extension. If so, prefer extension from file.
             */
            if(! $mime_type || ($file_mime === $mime_type))
            {
                $mime_type = $file_mime;
                $new_ext = $file_ext;
            }

            $output_format = apply_filters('image_editor_output_format', [], $filename, $mime_type);

            if(isset($output_format[$mime_type]) && $this->supports_mime_type($output_format[$mime_type]))
            {
                $mime_type = $output_format[$mime_type];
                $new_ext = $this->get_extension($mime_type);
            }

            /*
             * Double-check that the mime-type selected is supported by the editor.
             * If not, choose a default instead.
             */
            if(! $this->supports_mime_type($mime_type))
            {
                $mime_type = apply_filters('image_editor_default_mime_type', $this->default_mime_type);
                $new_ext = $this->get_extension($mime_type);
            }

            /*
             * Ensure both $filename and $new_ext are not empty.
             * $this->get_extension() returns false on error which would effectively remove the extension
             * from $filename. That shouldn't happen, files without extensions are not supported.
             */
            if($filename && $new_ext)
            {
                $dir = pathinfo($filename, PATHINFO_DIRNAME);
                $ext = pathinfo($filename, PATHINFO_EXTENSION);

                $filename = trailingslashit($dir).wp_basename($filename, ".$ext").".{$new_ext}";
            }

            if($mime_type && ($mime_type !== $this->mime_type))
            {
                // The image will be converted when saving. Set the quality for the new mime-type if not already set.
                if($mime_type !== $this->output_mime_type)
                {
                    $this->output_mime_type = $mime_type;
                }
                $this->set_quality();
            }
            elseif(! empty($this->output_mime_type))
            {
                // Reset output_mime_type and quality.
                $this->output_mime_type = null;
                $this->set_quality();
            }

            return [$filename, $new_ext, $mime_type];
        }

        protected static function get_extension($mime_type = null)
        {
            if(empty($mime_type))
            {
                return false;
            }

            return wp_get_default_extension_for_mime_type($mime_type);
        }

        protected static function get_mime_type($extension = null)
        {
            if(! $extension)
            {
                return false;
            }

            $mime_types = wp_get_mime_types();
            $extensions = array_keys($mime_types);

            foreach($extensions as $_extension)
            {
                if(preg_match("/{$extension}/i", $_extension))
                {
                    return $mime_types[$_extension];
                }
            }

            return false;
        }

        public static function supports_mime_type($mime_type)
        {
            return false;
        }

        protected function make_image($filename, $callback, $arguments)
        {
            $stream = wp_is_stream($filename);
            if($stream)
            {
                ob_start();
            }
            else
            {
                // The directory containing the original file may no longer exist when using a replication plugin.
                wp_mkdir_p(dirname($filename));
            }

            $result = call_user_func_array($callback, $arguments);

            if($result && $stream)
            {
                $contents = ob_get_contents();

                $fp = fopen($filename, 'w');

                if(! $fp)
                {
                    ob_end_clean();

                    return false;
                }

                fwrite($fp, $contents);
                fclose($fp);
            }

            if($stream)
            {
                ob_end_clean();
            }

            return $result;
        }
    }
