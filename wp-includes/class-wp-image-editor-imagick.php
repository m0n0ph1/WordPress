<?php

    class WP_Image_Editor_Imagick extends WP_Image_Editor
    {
        protected $image;

        public static function test($args = [])
        {
            // First, test Imagick's extension and classes.
            if(! extension_loaded('imagick') || ! class_exists('Imagick', false) || ! class_exists('ImagickPixel', false))
            {
                return false;
            }

            if(version_compare(phpversion('imagick'), '2.2.0', '<'))
            {
                return false;
            }

            $required_methods = [
                'clear',
                'destroy',
                'valid',
                'getimage',
                'writeimage',
                'getimageblob',
                'getimagegeometry',
                'getimageformat',
                'setimageformat',
                'setimagecompression',
                'setimagecompressionquality',
                'setimagepage',
                'setoption',
                'scaleimage',
                'cropimage',
                'rotateimage',
                'flipimage',
                'flopimage',
                'readimage',
                'readimageblob',
            ];

            // Now, test for deep requirements within Imagick.
            if(! defined('imagick::COMPRESSION_JPEG'))
            {
                return false;
            }

            $class_methods = array_map('strtolower', get_class_methods('Imagick'));
            if(array_diff($required_methods, $class_methods))
            {
                return false;
            }

            return true;
        }

        public static function supports_mime_type($mime_type)
        {
            $imagick_extension = strtoupper(self::get_extension($mime_type));

            if(! $imagick_extension)
            {
                return false;
            }

            /*
             * setIteratorIndex is optional unless mime is an animated format.
             * Here, we just say no if you are missing it and aren't loading a jpeg.
             */
            if(! method_exists('Imagick', 'setIteratorIndex') && 'image/jpeg' !== $mime_type)
            {
                return false;
            }

            try
            {
                // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                return ((bool) @Imagick::queryFormats($imagick_extension));
            }
            catch(Exception $e)
            {
                return false;
            }
        }

        public static function set_imagick_time_limit()
        {
            _deprecated_function(__METHOD__, '6.3.0');

            if(! defined('Imagick::RESOURCETYPE_TIME'))
            {
                return null;
            }

            // Returns PHP_FLOAT_MAX if unset.
            $imagick_timeout = Imagick::getResourceLimit(Imagick::RESOURCETYPE_TIME);

            // Convert to an integer, keeping in mind that: 0 === (int) PHP_FLOAT_MAX.
            $imagick_timeout = $imagick_timeout > PHP_INT_MAX ? PHP_INT_MAX : (int) $imagick_timeout;

            $php_timeout = (int) ini_get('max_execution_time');

            if($php_timeout > 1 && $php_timeout < $imagick_timeout)
            {
                $limit = (float) 0.8 * $php_timeout;
                Imagick::setResourceLimit(Imagick::RESOURCETYPE_TIME, $limit);

                return $limit;
            }
        }

        public function __destruct()
        {
            if($this->image instanceof Imagick)
            {
                // We don't need the original in memory anymore.
                $this->image->clear();
                $this->image->destroy();
            }
        }

        public function load()
        {
            if($this->image instanceof Imagick)
            {
                return true;
            }

            if(! is_file($this->file) && ! wp_is_stream($this->file))
            {
                return new WP_Error('error_loading_image', __('File does not exist?'), $this->file);
            }

            /*
             * Even though Imagick uses less PHP memory than GD, set higher limit
             * for users that have low PHP.ini limits.
             */
            wp_raise_memory_limit('image');

            try
            {
                $this->image = new Imagick();
                $file_extension = strtolower(pathinfo($this->file, PATHINFO_EXTENSION));

                if('pdf' === $file_extension)
                {
                    $pdf_loaded = $this->pdf_load_source();

                    if(is_wp_error($pdf_loaded))
                    {
                        return $pdf_loaded;
                    }
                }
                else
                {
                    if(wp_is_stream($this->file))
                    {
                        // Due to reports of issues with streams with `Imagick::readImageFile()`, uses `Imagick::readImageBlob()` instead.
                        $this->image->readImageBlob(file_get_contents($this->file), $this->file);
                    }
                    else
                    {
                        $this->image->readImage($this->file);
                    }
                }

                if(! $this->image->valid())
                {
                    return new WP_Error('invalid_image', __('File is not an image.'), $this->file);
                }

                // Select the first frame to handle animated images properly.
                if(is_callable([$this->image, 'setIteratorIndex']))
                {
                    $this->image->setIteratorIndex(0);
                }

                if('pdf' === $file_extension)
                {
                    $this->remove_pdf_alpha_channel();
                }

                $this->mime_type = $this->get_mime_type($this->image->getImageFormat());
            }
            catch(Exception $e)
            {
                return new WP_Error('invalid_image', $e->getMessage(), $this->file);
            }

            $updated_size = $this->update_size();

            if(is_wp_error($updated_size))
            {
                return $updated_size;
            }

            return $this->set_quality();
        }

        protected function pdf_load_source()
        {
            $filename = $this->pdf_setup();

            if(is_wp_error($filename))
            {
                return $filename;
            }

            try
            {
                /*
                 * When generating thumbnails from cropped PDF pages, Imagemagick uses the uncropped
                 * area (resulting in unnecessary whitespace) unless the following option is set.
                 */
                $this->image->setOption('pdf:use-cropbox', true);

                /*
                 * Reading image after Imagick instantiation because `setResolution`
                 * only applies correctly before the image is read.
                 */
                $this->image->readImage($filename);
            }
            catch(Exception $e)
            {
                // Attempt to run `gs` without the `use-cropbox` option. See #48853.
                $this->image->setOption('pdf:use-cropbox', false);

                $this->image->readImage($filename);
            }

            return true;
        }

        protected function pdf_setup()
        {
            try
            {
                /*
                 * By default, PDFs are rendered in a very low resolution.
                 * We want the thumbnail to be readable, so increase the rendering DPI.
                 */
                $this->image->setResolution(128, 128);

                // Only load the first page.
                return $this->file.'[0]';
            }
            catch(Exception $e)
            {
                return new WP_Error('pdf_setup_failed', $e->getMessage(), $this->file);
            }
        }

        protected function remove_pdf_alpha_channel()
        {
            $version = Imagick::getVersion();
            // Remove alpha channel if possible to avoid black backgrounds for Ghostscript >= 9.14. RemoveAlphaChannel added in ImageMagick 6.7.5.
            if($version['versionNumber'] >= 0x675)
            {
                try
                {
                    // Imagick::ALPHACHANNEL_REMOVE mapped to RemoveAlphaChannel in PHP imagick 3.2.0b2.
                    $this->image->setImageAlphaChannel(defined('Imagick::ALPHACHANNEL_REMOVE') ? Imagick::ALPHACHANNEL_REMOVE : 12);
                }
                catch(Exception $e)
                {
                    return new WP_Error('pdf_alpha_process_failed', $e->getMessage());
                }
            }
        }

        protected function update_size($width = null, $height = null)
        {
            $size = null;
            if(! $width || ! $height)
            {
                try
                {
                    $size = $this->image->getImageGeometry();
                }
                catch(Exception $e)
                {
                    return new WP_Error('invalid_image', __('Could not read image size.'), $this->file);
                }
            }

            if(! $width)
            {
                $width = $size['width'];
            }

            if(! $height)
            {
                $height = $size['height'];
            }

            return parent::update_size($width, $height);
        }

        public function set_quality($quality = null)
        {
            $quality_result = parent::set_quality($quality);
            if(is_wp_error($quality_result))
            {
                return $quality_result;
            }
            else
            {
                $quality = $this->get_quality();
            }

            try
            {
                switch($this->mime_type)
                {
                    case 'image/jpeg':
                        $this->image->setImageCompressionQuality($quality);
                        $this->image->setImageCompression(imagick::COMPRESSION_JPEG);
                        break;
                    case 'image/webp':
                        $webp_info = wp_get_webp_info($this->file);

                        if('lossless' === $webp_info['type'])
                        {
                            // Use WebP lossless settings.
                            $this->image->setImageCompressionQuality(100);
                            $this->image->setOption('webp:lossless', 'true');
                        }
                        else
                        {
                            $this->image->setImageCompressionQuality($quality);
                        }
                        break;
                    default:
                        $this->image->setImageCompressionQuality($quality);
                }
            }
            catch(Exception $e)
            {
                return new WP_Error('image_quality_error', $e->getMessage());
            }

            return true;
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
            $orig_image = $this->image->getImage();

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

            if(($this->size['width'] === $size_data['width']) && ($this->size['height'] === $size_data['height']))
            {
                return new WP_Error('image_subsize_create_error', __('The image already has the requested size.'));
            }

            $resized = $this->resize($size_data['width'], $size_data['height'], $size_data['crop']);

            if(is_wp_error($resized))
            {
                $saved = $resized;
            }
            else
            {
                $saved = $this->_save($this->image);

                $this->image->clear();
                $this->image->destroy();
                $this->image = null;
            }

            $this->size = $orig_size;
            $this->image = $orig_image;

            if(! is_wp_error($saved))
            {
                unset($saved['path']);
            }

            return $saved;
        }

        public function resize($max_w, $max_h, $crop = false)
        {
            if(($this->size['width'] == $max_w) && ($this->size['height'] == $max_h))
            {
                return true;
            }

            $dims = image_resize_dimensions($this->size['width'], $this->size['height'], $max_w, $max_h, $crop);
            if(! $dims)
            {
                return new WP_Error('error_getting_dimensions', __('Could not calculate resized image dimensions'));
            }

            [$dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h] = $dims;

            if($crop)
            {
                return $this->crop($src_x, $src_y, $src_w, $src_h, $dst_w, $dst_h);
            }

            // Execute the resize.
            $thumb_result = $this->thumbnail_image($dst_w, $dst_h);
            if(is_wp_error($thumb_result))
            {
                return $thumb_result;
            }

            return $this->update_size($dst_w, $dst_h);
        }

        public function crop($src_x, $src_y, $src_w, $src_h, $dst_w = null, $dst_h = null, $src_abs = false)
        {
            if($src_abs)
            {
                $src_w -= $src_x;
                $src_h -= $src_y;
            }

            try
            {
                $this->image->cropImage($src_w, $src_h, $src_x, $src_y);
                $this->image->setImagePage($src_w, $src_h, 0, 0);

                if($dst_w || $dst_h)
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

                    $thumb_result = $this->thumbnail_image($dst_w, $dst_h);
                    if(is_wp_error($thumb_result))
                    {
                        return $thumb_result;
                    }

                    return $this->update_size();
                }
            }
            catch(Exception $e)
            {
                return new WP_Error('image_crop_error', $e->getMessage());
            }

            return $this->update_size();
        }

        protected function thumbnail_image($dst_w, $dst_h, $filter_name = 'FILTER_TRIANGLE', $strip_meta = true)
        {
            $allowed_filters = [
                'FILTER_POINT',
                'FILTER_BOX',
                'FILTER_TRIANGLE',
                'FILTER_HERMITE',
                'FILTER_HANNING',
                'FILTER_HAMMING',
                'FILTER_BLACKMAN',
                'FILTER_GAUSSIAN',
                'FILTER_QUADRATIC',
                'FILTER_CUBIC',
                'FILTER_CATROM',
                'FILTER_MITCHELL',
                'FILTER_LANCZOS',
                'FILTER_BESSEL',
                'FILTER_SINC',
            ];

            if(in_array($filter_name, $allowed_filters, true) && defined('Imagick::'.$filter_name))
            {
                $filter = constant('Imagick::'.$filter_name);
            }
            else
            {
                $filter = defined('Imagick::FILTER_TRIANGLE') ? Imagick::FILTER_TRIANGLE : false;
            }

            if(apply_filters('image_strip_meta', $strip_meta))
            {
                $this->strip_meta(); // Fail silently if not supported.
            }

            try
            {
                /*
                 * To be more efficient, resample large images to 5x the destination size before resizing
                 * whenever the output size is less that 1/3 of the original image size (1/3^2 ~= .111),
                 * unless we would be resampling to a scale smaller than 128x128.
                 */
                if(is_callable([$this->image, 'sampleImage']))
                {
                    $resize_ratio = ($dst_w / $this->size['width']) * ($dst_h / $this->size['height']);
                    $sample_factor = 5;

                    if($resize_ratio < .111 && ($dst_w * $sample_factor > 128 && $dst_h * $sample_factor > 128))
                    {
                        $this->image->sampleImage($dst_w * $sample_factor, $dst_h * $sample_factor);
                    }
                }

                /*
                 * Use resizeImage() when it's available and a valid filter value is set.
                 * Otherwise, fall back to the scaleImage() method for resizing, which
                 * results in better image quality over resizeImage() with default filter
                 * settings and retains backward compatibility with pre 4.5 functionality.
                 */
                if(is_callable([$this->image, 'resizeImage']) && $filter)
                {
                    $this->image->setOption('filter:support', '2.0');
                    $this->image->resizeImage($dst_w, $dst_h, $filter, 1);
                }
                else
                {
                    $this->image->scaleImage($dst_w, $dst_h);
                }

                // Set appropriate quality settings after resizing.
                if('image/jpeg' === $this->mime_type)
                {
                    if(is_callable([$this->image, 'unsharpMaskImage']))
                    {
                        $this->image->unsharpMaskImage(0.25, 0.25, 8, 0.065);
                    }

                    $this->image->setOption('jpeg:fancy-upsampling', 'off');
                }

                if('image/png' === $this->mime_type)
                {
                    $this->image->setOption('png:compression-filter', '5');
                    $this->image->setOption('png:compression-level', '9');
                    $this->image->setOption('png:compression-strategy', '1');
                    $this->image->setOption('png:exclude-chunk', 'all');
                }

                /*
                 * If alpha channel is not defined, set it opaque.
                 *
                 * Note that Imagick::getImageAlphaChannel() is only available if Imagick
                 * has been compiled against ImageMagick version 6.4.0 or newer.
                 */
                if(
                    is_callable([$this->image, 'getImageAlphaChannel']) && is_callable([
                                                                                           $this->image,
                                                                                           'setImageAlphaChannel'
                                                                                       ]) && defined('Imagick::ALPHACHANNEL_UNDEFINED') && defined('Imagick::ALPHACHANNEL_OPAQUE')
                )
                {
                    if($this->image->getImageAlphaChannel() === Imagick::ALPHACHANNEL_UNDEFINED)
                    {
                        $this->image->setImageAlphaChannel(Imagick::ALPHACHANNEL_OPAQUE);
                    }
                }

                // Limit the bit depth of resized images to 8 bits per channel.
                if(is_callable([$this->image, 'getImageDepth']) && is_callable([$this->image, 'setImageDepth']))
                {
                    if(8 < $this->image->getImageDepth())
                    {
                        $this->image->setImageDepth(8);
                    }
                }

                if(is_callable([$this->image, 'setInterlaceScheme']) && defined('Imagick::INTERLACE_NO'))
                {
                    $this->image->setInterlaceScheme(Imagick::INTERLACE_NO);
                }
            }
            catch(Exception $e)
            {
                return new WP_Error('image_resize_error', $e->getMessage());
            }
        }

        protected function strip_meta()
        {
            if(! is_callable([$this->image, 'getImageProfiles']))
            {
                return new WP_Error('image_strip_meta_error', sprintf(/* translators: %s: ImageMagick method name. */ __('%s is required to strip image meta.'), '<code>Imagick::getImageProfiles()</code>'));
            }

            if(! is_callable([$this->image, 'removeImageProfile']))
            {
                return new WP_Error('image_strip_meta_error', sprintf(/* translators: %s: ImageMagick method name. */ __('%s is required to strip image meta.'), '<code>Imagick::removeImageProfile()</code>'));
            }

            /*
             * Protect a few profiles from being stripped for the following reasons:
             *
             * - icc:  Color profile information
             * - icm:  Color profile information
             * - iptc: Copyright data
             * - exif: Orientation data
             * - xmp:  Rights usage data
             */
            $protected_profiles = [
                'icc',
                'icm',
                'iptc',
                'exif',
                'xmp',
            ];

            try
            {
                // Strip profiles.
                foreach($this->image->getImageProfiles('*', true) as $key => $value)
                {
                    if(! in_array($key, $protected_profiles, true))
                    {
                        $this->image->removeImageProfile($key);
                    }
                }
            }
            catch(Exception $e)
            {
                return new WP_Error('image_strip_meta_error', $e->getMessage());
            }

            return true;
        }

        protected function _save($image, $filename = null, $mime_type = null)
        {
            [$filename, $extension, $mime_type] = $this->get_output_format($filename, $mime_type);

            if(! $filename)
            {
                $filename = $this->generate_filename(null, null, $extension);
            }

            try
            {
                // Store initial format.
                $orig_format = $this->image->getImageFormat();

                $this->image->setImageFormat(strtoupper($this->get_extension($mime_type)));
            }
            catch(Exception $e)
            {
                return new WP_Error('image_save_error', $e->getMessage(), $filename);
            }

            $write_image_result = $this->write_image($this->image, $filename);
            if(is_wp_error($write_image_result))
            {
                return $write_image_result;
            }

            try
            {
                // Reset original format.
                $this->image->setImageFormat($orig_format);
            }
            catch(Exception $e)
            {
                return new WP_Error('image_save_error', $e->getMessage(), $filename);
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

        private function write_image($image, $filename)
        {
            if(wp_is_stream($filename))
            {
                /*
                 * Due to reports of issues with streams with `Imagick::writeImageFile()` and `Imagick::writeImage()`, copies the blob instead.
                 * Checks for exact type due to: https://www.php.net/manual/en/function.file-put-contents.php
                 */
                if(file_put_contents($filename, $image->getImageBlob()) === false)
                {
                    return new WP_Error('image_save_error', sprintf(/* translators: %s: PHP function name. */ __('%s failed while writing image to stream.'), '<code>file_put_contents()</code>'), $filename);
                }
                else
                {
                    return true;
                }
            }
            else
            {
                $dirname = dirname($filename);

                if(! wp_mkdir_p($dirname))
                {
                    return new WP_Error('image_save_error', sprintf(/* translators: %s: Directory path. */ __('Unable to create directory %s. Is its parent directory writable by the server?'), esc_html($dirname)));
                }

                try
                {
                    return $image->writeImage($filename);
                }
                catch(Exception $e)
                {
                    return new WP_Error('image_save_error', $e->getMessage(), $filename);
                }
            }
        }

        public function rotate($angle)
        {
            try
            {
                $this->image->rotateImage(new ImagickPixel('none'), 360 - $angle);

                // Normalize EXIF orientation data so that display is consistent across devices.
                if(is_callable([$this->image, 'setImageOrientation']) && defined('Imagick::ORIENTATION_TOPLEFT'))
                {
                    $this->image->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
                }

                // Since this changes the dimensions of the image, update the size.
                $result = $this->update_size();
                if(is_wp_error($result))
                {
                    return $result;
                }

                $this->image->setImagePage($this->size['width'], $this->size['height'], 0, 0);
            }
            catch(Exception $e)
            {
                return new WP_Error('image_rotate_error', $e->getMessage());
            }

            return true;
        }

        public function flip($horz, $vert)
        {
            try
            {
                if($horz)
                {
                    $this->image->flipImage();
                }

                if($vert)
                {
                    $this->image->flopImage();
                }

                // Normalize EXIF orientation data so that display is consistent across devices.
                if(is_callable([$this->image, 'setImageOrientation']) && defined('Imagick::ORIENTATION_TOPLEFT'))
                {
                    $this->image->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
                }
            }
            catch(Exception $e)
            {
                return new WP_Error('image_flip_error', $e->getMessage());
            }

            return true;
        }

        public function maybe_exif_rotate()
        {
            if(is_callable([$this->image, 'setImageOrientation']) && defined('Imagick::ORIENTATION_TOPLEFT'))
            {
                return parent::maybe_exif_rotate();
            }
            else
            {
                return new WP_Error('write_exif_error', __('The image cannot be rotated because the embedded meta data cannot be updated.'));
            }
        }

        public function save($destfilename = null, $mime_type = null)
        {
            $saved = $this->_save($this->image, $destfilename, $mime_type);

            if(! is_wp_error($saved))
            {
                $this->file = $saved['path'];
                $this->mime_type = $saved['mime-type'];

                try
                {
                    $this->image->setImageFormat(strtoupper($this->get_extension($this->mime_type)));
                }
                catch(Exception $e)
                {
                    return new WP_Error('image_save_error', $e->getMessage(), $this->file);
                }
            }

            return $saved;
        }

        public function stream($mime_type = null)
        {
            [$filename, $extension, $mime_type] = $this->get_output_format(null, $mime_type);

            try
            {
                // Temporarily change format for stream.
                $this->image->setImageFormat(strtoupper($extension));

                // Output stream of image content.
                header("Content-Type: $mime_type");
                print $this->image->getImageBlob();

                // Reset image to original format.
                $this->image->setImageFormat($this->get_extension($this->mime_type));
            }
            catch(Exception $e)
            {
                return new WP_Error('image_stream_error', $e->getMessage());
            }

            return true;
        }
    }
