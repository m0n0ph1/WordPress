<?php

    class WP_Image_Editor_GD extends WP_Image_Editor
    {
        protected $image;

        public static function test($args = [])
        {
            if(! extension_loaded('gd') || ! function_exists('gd_info'))
            {
                return false;
            }

            // On some setups GD library does not provide imagerotate() - Ticket #11536.
            if(isset($args['methods']) && in_array('rotate', $args['methods'], true) && ! function_exists('imagerotate'))
            {
                return false;
            }

            return true;
        }

        public static function supports_mime_type($mime_type)
        {
            $image_types = imagetypes();
            switch($mime_type)
            {
                case 'image/jpeg':
                    return ($image_types & IMG_JPG) != 0;
                case 'image/png':
                    return ($image_types & IMG_PNG) != 0;
                case 'image/gif':
                    return ($image_types & IMG_GIF) != 0;
                case 'image/webp':
                    return ($image_types & IMG_WEBP) != 0;
            }

            return false;
        }

        public function __destruct()
        {
            if($this->image)
            {
                // We don't need the original in memory anymore.
                imagedestroy($this->image);
            }
        }

        public function load()
        {
            if($this->image)
            {
                return true;
            }

            if(! is_file($this->file) && ! preg_match('|^https?://|', $this->file))
            {
                return new WP_Error('error_loading_image', __('File does not exist?'), $this->file);
            }

            // Set artificially high because GD uses uncompressed images in memory.
            wp_raise_memory_limit('image');

            $file_contents = @file_get_contents($this->file);

            if(! $file_contents)
            {
                return new WP_Error('error_loading_image', __('File does not exist?'), $this->file);
            }

            // WebP may not work with imagecreatefromstring().
            if(function_exists('imagecreatefromwebp') && ('image/webp' === wp_get_image_mime($this->file)))
            {
                $this->image = @imagecreatefromwebp($this->file);
            }
            else
            {
                $this->image = @imagecreatefromstring($file_contents);
            }

            if(! is_gd_image($this->image))
            {
                return new WP_Error('invalid_image', __('File is not an image.'), $this->file);
            }

            $size = wp_getimagesize($this->file);

            if(! $size)
            {
                return new WP_Error('invalid_image', __('Could not read image size.'), $this->file);
            }

            if(function_exists('imagealphablending') && function_exists('imagesavealpha'))
            {
                imagealphablending($this->image, false);
                imagesavealpha($this->image, true);
            }

            $this->update_size($size[0], $size[1]);
            $this->mime_type = $size['mime'];

            return $this->set_quality();
        }

        protected function update_size($width = false, $height = false)
        {
            if(! $width)
            {
                $width = imagesx($this->image);
            }

            if(! $height)
            {
                $height = imagesy($this->image);
            }

            return parent::update_size($width, $height);
        }

        public function resize($max_w, $max_h, $crop = false)
        {
            if(($this->size['width'] == $max_w) && ($this->size['height'] == $max_h))
            {
                return true;
            }

            $resized = $this->_resize($max_w, $max_h, $crop);

            if(is_gd_image($resized))
            {
                imagedestroy($this->image);
                $this->image = $resized;

                return true;
            }
            elseif(is_wp_error($resized))
            {
                return $resized;
            }

            return new WP_Error('image_resize_error', __('Image resize failed.'), $this->file);
        }

        protected function _resize($max_w, $max_h, $crop = false)
        {
            $dims = image_resize_dimensions($this->size['width'], $this->size['height'], $max_w, $max_h, $crop);

            if(! $dims)
            {
                return new WP_Error('error_getting_dimensions', __('Could not calculate resized image dimensions'), $this->file);
            }

            [$dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h] = $dims;

            $resized = wp_imagecreatetruecolor($dst_w, $dst_h);
            imagecopyresampled($resized, $this->image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

            if(is_gd_image($resized))
            {
                $this->update_size($dst_w, $dst_h);

                return $resized;
            }

            return new WP_Error('image_resize_error', __('Image resize failed.'), $this->file);
        }

        public function multi_resize($sizes)
        {
            $metadata = [];

            foreach($sizes as $size => $size_data)
            {
                $meta = $this->make_subsize($size_data);

                if(! is_wp_error($meta))
                {
                    $metadata[$size] = $meta;
                }
            }

            return $metadata;
        }

        public function make_subsize($size_data)
        {
            if(! isset($size_data['width']) && ! isset($size_data['height']))
            {
                return new WP_Error('image_subsize_create_error', __('Cannot resize the image. Both width and height are not set.'));
            }

            $orig_size = $this->size;

            if(! isset($size_data['width']))
            {
                $size_data['width'] = null;
            }

            if(! isset($size_data['height']))
            {
                $size_data['height'] = null;
            }

            if(! isset($size_data['crop']))
            {
                $size_data['crop'] = false;
            }

            $resized = $this->_resize($size_data['width'], $size_data['height'], $size_data['crop']);

            if(is_wp_error($resized))
            {
                $saved = $resized;
            }
            else
            {
                $saved = $this->_save($resized);
                imagedestroy($resized);
            }

            $this->size = $orig_size;

            if(! is_wp_error($saved))
            {
                unset($saved['path']);
            }

            return $saved;
        }

        protected function _save($image, $filename = null, $mime_type = null)
        {
            [$filename, $extension, $mime_type] = $this->get_output_format($filename, $mime_type);

            if(! $filename)
            {
                $filename = $this->generate_filename(null, null, $extension);
            }

            if('image/gif' === $mime_type)
            {
                if(! $this->make_image($filename, 'imagegif', [$image, $filename]))
                {
                    return new WP_Error('image_save_error', __('Image Editor Save Failed'));
                }
            }
            elseif('image/png' === $mime_type)
            {
                // Convert from full colors to index colors, like original PNG.
                if(function_exists('imageistruecolor') && ! imageistruecolor($image))
                {
                    imagetruecolortopalette($image, false, imagecolorstotal($image));
                }

                if(! $this->make_image($filename, 'imagepng', [$image, $filename]))
                {
                    return new WP_Error('image_save_error', __('Image Editor Save Failed'));
                }
            }
            elseif('image/jpeg' === $mime_type)
            {
                if(! $this->make_image($filename, 'imagejpeg', [$image, $filename, $this->get_quality()]))
                {
                    return new WP_Error('image_save_error', __('Image Editor Save Failed'));
                }
            }
            elseif('image/webp' == $mime_type)
            {
                if(
                    ! function_exists('imagewebp') || ! $this->make_image($filename, 'imagewebp', [
                        $image,
                        $filename,
                        $this->get_quality()
                    ])
                )
                {
                    return new WP_Error('image_save_error', __('Image Editor Save Failed'));
                }
            }
            else
            {
                return new WP_Error('image_save_error', __('Image Editor Save Failed'));
            }

            // Set correct file permissions.
            $stat = stat(dirname($filename));
            $perms = $stat['mode'] & 0000666; // Same permissions as parent folder, strip off the executable bits.
            chmod($filename, $perms);

            return [
                'path' => $filename,
                'file' => wp_basename(apply_filters('image_make_intermediate_size', $filename)),
                'width' => $this->size['width'],
                'height' => $this->size['height'],
                'mime-type' => $mime_type,
                'filesize' => wp_filesize($filename),
            ];
        }

        protected function make_image($filename, $callback, $arguments)
        {
            if(wp_is_stream($filename))
            {
                $arguments[1] = null;
            }

            return parent::make_image($filename, $callback, $arguments);
        }

        public function crop($src_x, $src_y, $src_w, $src_h, $dst_w = null, $dst_h = null, $src_abs = false)
        {
            /*
             * If destination width/height isn't specified,
             * use same as width/height from source.
             */
            if(! $dst_w)
            {
                $dst_w = $src_w;
            }
            if(! $dst_h)
            {
                $dst_h = $src_h;
            }

            foreach([$src_w, $src_h, $dst_w, $dst_h] as $value)
            {
                if(! is_numeric($value) || (int) $value <= 0)
                {
                    return new WP_Error('image_crop_error', __('Image crop failed.'), $this->file);
                }
            }

            $dst = wp_imagecreatetruecolor((int) $dst_w, (int) $dst_h);

            if($src_abs)
            {
                $src_w -= $src_x;
                $src_h -= $src_y;
            }

            if(function_exists('imageantialias'))
            {
                imageantialias($dst, true);
            }

            imagecopyresampled($dst, $this->image, 0, 0, (int) $src_x, (int) $src_y, (int) $dst_w, (int) $dst_h, (int) $src_w, (int) $src_h);

            if(is_gd_image($dst))
            {
                imagedestroy($this->image);
                $this->image = $dst;
                $this->update_size();

                return true;
            }

            return new WP_Error('image_crop_error', __('Image crop failed.'), $this->file);
        }

        public function rotate($angle)
        {
            if(function_exists('imagerotate'))
            {
                $transparency = imagecolorallocatealpha($this->image, 255, 255, 255, 127);
                $rotated = imagerotate($this->image, $angle, $transparency);

                if(is_gd_image($rotated))
                {
                    imagealphablending($rotated, true);
                    imagesavealpha($rotated, true);
                    imagedestroy($this->image);
                    $this->image = $rotated;
                    $this->update_size();

                    return true;
                }
            }

            return new WP_Error('image_rotate_error', __('Image rotate failed.'), $this->file);
        }

        public function flip($horz, $vert)
        {
            $w = $this->size['width'];
            $h = $this->size['height'];
            $dst = wp_imagecreatetruecolor($w, $h);

            if(is_gd_image($dst))
            {
                $sx = $vert ? ($w - 1) : 0;
                $sy = $horz ? ($h - 1) : 0;
                $sw = $vert ? -$w : $w;
                $sh = $horz ? -$h : $h;

                if(imagecopyresampled($dst, $this->image, 0, 0, $sx, $sy, $w, $h, $sw, $sh))
                {
                    imagedestroy($this->image);
                    $this->image = $dst;

                    return true;
                }
            }

            return new WP_Error('image_flip_error', __('Image flip failed.'), $this->file);
        }

        public function save($destfilename = null, $mime_type = null)
        {
            $saved = $this->_save($this->image, $destfilename, $mime_type);

            if(! is_wp_error($saved))
            {
                $this->file = $saved['path'];
                $this->mime_type = $saved['mime-type'];
            }

            return $saved;
        }

        public function stream($mime_type = null)
        {
            [$filename, $extension, $mime_type] = $this->get_output_format(null, $mime_type);

            switch($mime_type)
            {
                case 'image/png':
                    header('Content-Type: image/png');

                    return imagepng($this->image);
                case 'image/gif':
                    header('Content-Type: image/gif');

                    return imagegif($this->image);
                case 'image/webp':
                    if(function_exists('imagewebp'))
                    {
                        header('Content-Type: image/webp');

                        return imagewebp($this->image, null, $this->get_quality());
                    }
                // Fall back to the default if webp isn't supported.
                default:
                    header('Content-Type: image/jpeg');

                    return imagejpeg($this->image, null, $this->get_quality());
            }
        }
    }
