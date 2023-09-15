<?php

    function wp_get_additional_image_sizes()
    {
        global $_wp_additional_image_sizes;

        if(! $_wp_additional_image_sizes)
        {
            $_wp_additional_image_sizes = [];
        }

        return $_wp_additional_image_sizes;
    }

    function image_constrain_size_for_editor($width, $height, $size = 'medium', $context = null)
    {
        global $content_width;

        $_wp_additional_image_sizes = wp_get_additional_image_sizes();

        if(! $context)
        {
            $context = is_admin() ? 'edit' : 'display';
        }

        if(is_array($size))
        {
            $max_width = $size[0];
            $max_height = $size[1];
        }
        elseif('thumb' === $size || 'thumbnail' === $size)
        {
            $max_width = (int) get_option('thumbnail_size_w');
            $max_height = (int) get_option('thumbnail_size_h');
            // Last chance thumbnail size defaults.
            if(! $max_width && ! $max_height)
            {
                $max_width = 128;
                $max_height = 96;
            }
        }
        elseif('medium' === $size)
        {
            $max_width = (int) get_option('medium_size_w');
            $max_height = (int) get_option('medium_size_h');
        }
        elseif('medium_large' === $size)
        {
            $max_width = (int) get_option('medium_large_size_w');
            $max_height = (int) get_option('medium_large_size_h');

            if((int) $content_width > 0)
            {
                $max_width = min((int) $content_width, $max_width);
            }
        }
        elseif('large' === $size)
        {
            /*
		 * We're inserting a large size image into the editor. If it's a really
		 * big image we'll scale it down to fit reasonably within the editor
		 * itself, and within the theme's content width if it's known. The user
		 * can resize it in the editor if they wish.
		 */
            $max_width = (int) get_option('large_size_w');
            $max_height = (int) get_option('large_size_h');

            if((int) $content_width > 0)
            {
                $max_width = min((int) $content_width, $max_width);
            }
        }
        elseif(! empty($_wp_additional_image_sizes) && in_array($size, array_keys($_wp_additional_image_sizes), true))
        {
            $max_width = (int) $_wp_additional_image_sizes[$size]['width'];
            $max_height = (int) $_wp_additional_image_sizes[$size]['height'];
            // Only in admin. Assume that theme authors know what they're doing.
            if((int) $content_width > 0 && 'edit' === $context)
            {
                $max_width = min((int) $content_width, $max_width);
            }
        }
        else
        { // $size === 'full' has no constraint.
            $max_width = $width;
            $max_height = $height;
        }

        [$max_width, $max_height] = apply_filters('editor_max_image_size', [$max_width, $max_height], $size, $context);

        return wp_constrain_dimensions($width, $height, $max_width, $max_height);
    }

    function image_hwstring($width, $height)
    {
        $out = '';
        if($width)
        {
            $out .= 'width="'.(int) $width.'" ';
        }
        if($height)
        {
            $out .= 'height="'.(int) $height.'" ';
        }

        return $out;
    }

    function image_downsize($id, $size = 'medium')
    {
        $is_image = wp_attachment_is_image($id);

        $out = apply_filters('image_downsize', false, $id, $size);

        if($out)
        {
            return $out;
        }

        $img_url = wp_get_attachment_url($id);
        $meta = wp_get_attachment_metadata($id);
        $width = 0;
        $height = 0;
        $is_intermediate = false;
        $img_url_basename = wp_basename($img_url);

        /*
	 * If the file isn't an image, attempt to replace its URL with a rendered image from its meta.
	 * Otherwise, a non-image type could be returned.
	 */
        if(! $is_image)
        {
            if(! empty($meta['sizes']['full']))
            {
                $img_url = str_replace($img_url_basename, $meta['sizes']['full']['file'], $img_url);
                $img_url_basename = $meta['sizes']['full']['file'];
                $width = $meta['sizes']['full']['width'];
                $height = $meta['sizes']['full']['height'];
            }
            else
            {
                return false;
            }
        }

        // Try for a new style intermediate size.
        $intermediate = image_get_intermediate_size($id, $size);

        if($intermediate)
        {
            $img_url = str_replace($img_url_basename, $intermediate['file'], $img_url);
            $width = $intermediate['width'];
            $height = $intermediate['height'];
            $is_intermediate = true;
        }
        elseif('thumbnail' === $size && ! empty($meta['thumb']) && is_string($meta['thumb']))
        {
            // Fall back to the old thumbnail.
            $imagefile = get_attached_file($id);
            $thumbfile = str_replace(wp_basename($imagefile), wp_basename($meta['thumb']), $imagefile);

            if(file_exists($thumbfile))
            {
                $info = wp_getimagesize($thumbfile);

                if($info)
                {
                    $img_url = str_replace($img_url_basename, wp_basename($thumbfile), $img_url);
                    $width = $info[0];
                    $height = $info[1];
                    $is_intermediate = true;
                }
            }
        }

        if(! $width && ! $height && isset($meta['width'], $meta['height']))
        {
            // Any other type: use the real image.
            $width = $meta['width'];
            $height = $meta['height'];
        }

        if($img_url)
        {
            // We have the actual image size, but might need to further constrain it if content_width is narrower.
            [$width, $height] = image_constrain_size_for_editor($width, $height, $size);

            return [$img_url, $width, $height, $is_intermediate];
        }

        return false;
    }

    function add_image_size($name, $width = 0, $height = 0, $crop = false)
    {
        global $_wp_additional_image_sizes;

        $_wp_additional_image_sizes[$name] = [
            'width' => absint($width),
            'height' => absint($height),
            'crop' => $crop,
        ];
    }

    function has_image_size($name)
    {
        $sizes = wp_get_additional_image_sizes();

        return isset($sizes[$name]);
    }

    function remove_image_size($name)
    {
        global $_wp_additional_image_sizes;

        if(isset($_wp_additional_image_sizes[$name]))
        {
            unset($_wp_additional_image_sizes[$name]);

            return true;
        }

        return false;
    }

    function set_post_thumbnail_size($width = 0, $height = 0, $crop = false)
    {
        add_image_size('post-thumbnail', $width, $height, $crop);
    }

    function get_image_tag($id, $alt, $title, $align, $size = 'medium')
    {
        [$img_src, $width, $height] = image_downsize($id, $size);
        $hwstring = image_hwstring($width, $height);

        $title = $title ? 'title="'.esc_attr($title).'" ' : '';

        $size_class = is_array($size) ? implode('x', $size) : $size;
        $class = 'align'.esc_attr($align).' size-'.esc_attr($size_class).' wp-image-'.$id;

        $class = apply_filters('get_image_tag_class', $class, $id, $align, $size);

        $html = '<img src="'.esc_url($img_src).'" alt="'.esc_attr($alt).'" '.$title.$hwstring.'class="'.$class.'" />';

        return apply_filters('get_image_tag', $html, $id, $alt, $title, $align, $size);
    }

    function wp_constrain_dimensions($current_width, $current_height, $max_width = 0, $max_height = 0)
    {
        if(! $max_width && ! $max_height)
        {
            return [$current_width, $current_height];
        }

        $width_ratio = 1.0;
        $height_ratio = 1.0;
        $did_width = false;
        $did_height = false;

        if($max_width > 0 && $current_width > 0 && $current_width > $max_width)
        {
            $width_ratio = $max_width / $current_width;
            $did_width = true;
        }

        if($max_height > 0 && $current_height > 0 && $current_height > $max_height)
        {
            $height_ratio = $max_height / $current_height;
            $did_height = true;
        }

        // Calculate the larger/smaller ratios.
        $smaller_ratio = min($width_ratio, $height_ratio);
        $larger_ratio = max($width_ratio, $height_ratio);

        if((int) round($current_width * $larger_ratio) > $max_width || (int) round($current_height * $larger_ratio) > $max_height)
        {
            // The larger ratio is too big. It would result in an overflow.
            $ratio = $smaller_ratio;
        }
        else
        {
            // The larger ratio fits, and is likely to be a more "snug" fit.
            $ratio = $larger_ratio;
        }

        // Very small dimensions may result in 0, 1 should be the minimum.
        $w = max(1, (int) round($current_width * $ratio));
        $h = max(1, (int) round($current_height * $ratio));

        /*
	 * Sometimes, due to rounding, we'll end up with a result like this:
	 * 465x700 in a 177x177 box is 117x176... a pixel short.
	 * We also have issues with recursive calls resulting in an ever-changing result.
	 * Constraining to the result of a constraint should yield the original result.
	 * Thus we look for dimensions that are one pixel shy of the max value and bump them up.
	 */

        // Note: $did_width means it is possible $smaller_ratio == $width_ratio.
        if($did_width && $w === $max_width - 1)
        {
            $w = $max_width; // Round it up.
        }

        // Note: $did_height means it is possible $smaller_ratio == $height_ratio.
        if($did_height && $h === $max_height - 1)
        {
            $h = $max_height; // Round it up.
        }

        return apply_filters('wp_constrain_dimensions', [
            $w,
            $h
        ],                   $current_width, $current_height, $max_width, $max_height);
    }

    function image_resize_dimensions($orig_w, $orig_h, $dest_w, $dest_h, $crop = false)
    {
        if($orig_w <= 0 || $orig_h <= 0)
        {
            return false;
        }
        // At least one of $dest_w or $dest_h must be specific.
        if($dest_w <= 0 && $dest_h <= 0)
        {
            return false;
        }

        $output = apply_filters('image_resize_dimensions', null, $orig_w, $orig_h, $dest_w, $dest_h, $crop);

        if(null !== $output)
        {
            return $output;
        }

        // Stop if the destination size is larger than the original image dimensions.
        if(empty($dest_h))
        {
            if($orig_w < $dest_w)
            {
                return false;
            }
        }
        elseif(empty($dest_w))
        {
            if($orig_h < $dest_h)
            {
                return false;
            }
        }
        else
        {
            if($orig_w < $dest_w && $orig_h < $dest_h)
            {
                return false;
            }
        }

        if($crop)
        {
            /*
		 * Crop the largest possible portion of the original image that we can size to $dest_w x $dest_h.
		 * Note that the requested crop dimensions are used as a maximum bounding box for the original image.
		 * If the original image's width or height is less than the requested width or height
		 * only the greater one will be cropped.
		 * For example when the original image is 600x300, and the requested crop dimensions are 400x400,
		 * the resulting image will be 400x300.
		 */
            $aspect_ratio = $orig_w / $orig_h;
            $new_w = min($dest_w, $orig_w);
            $new_h = min($dest_h, $orig_h);

            if(! $new_w)
            {
                $new_w = (int) round($new_h * $aspect_ratio);
            }

            if(! $new_h)
            {
                $new_h = (int) round($new_w / $aspect_ratio);
            }

            $size_ratio = max($new_w / $orig_w, $new_h / $orig_h);

            $crop_w = round($new_w / $size_ratio);
            $crop_h = round($new_h / $size_ratio);

            if(! is_array($crop) || count($crop) !== 2)
            {
                $crop = ['center', 'center'];
            }

            [$x, $y] = $crop;

            if('left' === $x)
            {
                $s_x = 0;
            }
            elseif('right' === $x)
            {
                $s_x = $orig_w - $crop_w;
            }
            else
            {
                $s_x = floor(($orig_w - $crop_w) / 2);
            }

            if('top' === $y)
            {
                $s_y = 0;
            }
            elseif('bottom' === $y)
            {
                $s_y = $orig_h - $crop_h;
            }
            else
            {
                $s_y = floor(($orig_h - $crop_h) / 2);
            }
        }
        else
        {
            // Resize using $dest_w x $dest_h as a maximum bounding box.
            $crop_w = $orig_w;
            $crop_h = $orig_h;

            $s_x = 0;
            $s_y = 0;

            [$new_w, $new_h] = wp_constrain_dimensions($orig_w, $orig_h, $dest_w, $dest_h);
        }

        if(wp_fuzzy_number_match($new_w, $orig_w) && wp_fuzzy_number_match($new_h, $orig_h))
        {
            // The new size has virtually the same dimensions as the original image.

            $proceed = (bool) apply_filters('wp_image_resize_identical_dimensions', false, $orig_w, $orig_h);

            if(! $proceed)
            {
                return false;
            }
        }

        /*
	 * The return array matches the parameters to imagecopyresampled().
	 * int dst_x, int dst_y, int src_x, int src_y, int dst_w, int dst_h, int src_w, int src_h
	 */

        return [0, 0, (int) $s_x, (int) $s_y, (int) $new_w, (int) $new_h, (int) $crop_w, (int) $crop_h];
    }

    function image_make_intermediate_size($file, $width, $height, $crop = false)
    {
        if($width || $height)
        {
            $editor = wp_get_image_editor($file);

            if(is_wp_error($editor) || is_wp_error($editor->resize($width, $height, $crop)))
            {
                return false;
            }

            $resized_file = $editor->save();

            if(! is_wp_error($resized_file) && $resized_file)
            {
                unset($resized_file['path']);

                return $resized_file;
            }
        }

        return false;
    }

    function wp_image_matches_ratio($source_width, $source_height, $target_width, $target_height)
    {
        /*
	 * To test for varying crops, we constrain the dimensions of the larger image
	 * to the dimensions of the smaller image and see if they match.
	 */
        if($source_width > $target_width)
        {
            $constrained_size = wp_constrain_dimensions($source_width, $source_height, $target_width);
            $expected_size = [$target_width, $target_height];
        }
        else
        {
            $constrained_size = wp_constrain_dimensions($target_width, $target_height, $source_width);
            $expected_size = [$source_width, $source_height];
        }

        // If the image dimensions are within 1px of the expected size, we consider it a match.
        $matched = (wp_fuzzy_number_match($constrained_size[0], $expected_size[0]) && wp_fuzzy_number_match($constrained_size[1], $expected_size[1]));

        return $matched;
    }

    function image_get_intermediate_size($post_id, $size = 'thumbnail')
    {
        $imagedata = wp_get_attachment_metadata($post_id);

        if(! $size || ! is_array($imagedata) || empty($imagedata['sizes']))
        {
            return false;
        }

        $data = [];

        // Find the best match when '$size' is an array.
        if(is_array($size))
        {
            $candidates = [];

            if(! isset($imagedata['file']) && isset($imagedata['sizes']['full']))
            {
                $imagedata['height'] = $imagedata['sizes']['full']['height'];
                $imagedata['width'] = $imagedata['sizes']['full']['width'];
            }

            foreach($imagedata['sizes'] as $_size => $data)
            {
                // If there's an exact match to an existing image size, short circuit.
                if((int) $data['width'] === (int) $size[0] && (int) $data['height'] === (int) $size[1])
                {
                    $candidates[$data['width'] * $data['height']] = $data;
                    break;
                }

                // If it's not an exact match, consider larger sizes with the same aspect ratio.
                if($data['width'] >= $size[0] && $data['height'] >= $size[1])
                {
                    // If '0' is passed to either size, we test ratios against the original file.
                    if(0 === $size[0] || 0 === $size[1])
                    {
                        $same_ratio = wp_image_matches_ratio($data['width'], $data['height'], $imagedata['width'], $imagedata['height']);
                    }
                    else
                    {
                        $same_ratio = wp_image_matches_ratio($data['width'], $data['height'], $size[0], $size[1]);
                    }

                    if($same_ratio)
                    {
                        $candidates[$data['width'] * $data['height']] = $data;
                    }
                }
            }

            if(! empty($candidates))
            {
                // Sort the array by size if we have more than one candidate.
                if(1 < count($candidates))
                {
                    ksort($candidates);
                }

                $data = array_shift($candidates);
                /*
			* When the size requested is smaller than the thumbnail dimensions, we
			* fall back to the thumbnail size to maintain backward compatibility with
			* pre 4.6 versions of WordPress.
			*/
            }
            elseif(! empty($imagedata['sizes']['thumbnail']) && $imagedata['sizes']['thumbnail']['width'] >= $size[0] && $imagedata['sizes']['thumbnail']['width'] >= $size[1])
            {
                $data = $imagedata['sizes']['thumbnail'];
            }
            else
            {
                return false;
            }

            // Constrain the width and height attributes to the requested values.
            [$data['width'], $data['height']] = image_constrain_size_for_editor($data['width'], $data['height'], $size);
        }
        elseif(! empty($imagedata['sizes'][$size]))
        {
            $data = $imagedata['sizes'][$size];
        }

        // If we still don't have a match at this point, return false.
        if(empty($data))
        {
            return false;
        }

        // Include the full filesystem path of the intermediate file.
        if(empty($data['path']) && ! empty($data['file']) && ! empty($imagedata['file']))
        {
            $file_url = wp_get_attachment_url($post_id);
            $data['path'] = path_join(dirname($imagedata['file']), $data['file']);
            $data['url'] = path_join(dirname($file_url), $data['file']);
        }

        return apply_filters('image_get_intermediate_size', $data, $post_id, $size);
    }

    function get_intermediate_image_sizes()
    {
        $default_sizes = ['thumbnail', 'medium', 'medium_large', 'large'];
        $additional_sizes = wp_get_additional_image_sizes();

        if(! empty($additional_sizes))
        {
            $default_sizes = array_merge($default_sizes, array_keys($additional_sizes));
        }

        return apply_filters('intermediate_image_sizes', $default_sizes);
    }

    function wp_get_registered_image_subsizes()
    {
        $additional_sizes = wp_get_additional_image_sizes();
        $all_sizes = [];

        foreach(get_intermediate_image_sizes() as $size_name)
        {
            $size_data = [
                'width' => 0,
                'height' => 0,
                'crop' => false,
            ];

            if(isset($additional_sizes[$size_name]['width']))
            {
                // For sizes added by plugins and themes.
                $size_data['width'] = (int) $additional_sizes[$size_name]['width'];
            }
            else
            {
                // For default sizes set in options.
                $size_data['width'] = (int) get_option("{$size_name}_size_w");
            }

            if(isset($additional_sizes[$size_name]['height']))
            {
                $size_data['height'] = (int) $additional_sizes[$size_name]['height'];
            }
            else
            {
                $size_data['height'] = (int) get_option("{$size_name}_size_h");
            }

            if(empty($size_data['width']) && empty($size_data['height']))
            {
                // This size isn't set.
                continue;
            }

            if(isset($additional_sizes[$size_name]['crop']))
            {
                $size_data['crop'] = $additional_sizes[$size_name]['crop'];
            }
            else
            {
                $size_data['crop'] = get_option("{$size_name}_crop");
            }

            if(! is_array($size_data['crop']) || empty($size_data['crop']))
            {
                $size_data['crop'] = (bool) $size_data['crop'];
            }

            $all_sizes[$size_name] = $size_data;
        }

        return $all_sizes;
    }

    function wp_get_attachment_image_src($attachment_id, $size = 'thumbnail', $icon = false)
    {
        // Get a thumbnail or intermediate image if there is one.
        $image = image_downsize($attachment_id, $size);
        if(! $image)
        {
            $src = false;

            if($icon)
            {
                $src = wp_mime_type_icon($attachment_id);

                if($src)
                {
                    $icon_dir = apply_filters('icon_dir', ABSPATH.WPINC.'/images/media');

                    $src_file = $icon_dir.'/'.wp_basename($src);
                    [$width, $height] = wp_getimagesize($src_file);
                }
            }

            if($src && $width && $height)
            {
                $image = [$src, $width, $height, false];
            }
        }

        return apply_filters('wp_get_attachment_image_src', $image, $attachment_id, $size, $icon);
    }

    function wp_get_attachment_image($attachment_id, $size = 'thumbnail', $icon = false, $attr = '')
    {
        $html = '';
        $image = wp_get_attachment_image_src($attachment_id, $size, $icon);

        if($image)
        {
            [$src, $width, $height] = $image;

            $attachment = get_post($attachment_id);
            $hwstring = image_hwstring($width, $height);
            $size_class = $size;

            if(is_array($size_class))
            {
                $size_class = implode('x', $size_class);
            }

            $default_attr = [
                'src' => $src,
                'class' => "attachment-$size_class size-$size_class",
                'alt' => trim(strip_tags(get_post_meta($attachment_id, '_wp_attachment_image_alt', true))),
                'decoding' => 'async',
            ];

            $context = apply_filters('wp_get_attachment_image_context', 'wp_get_attachment_image');
            $attr = wp_parse_args($attr, $default_attr);

            $loading_attr = $attr;
            $loading_attr['width'] = $width;
            $loading_attr['height'] = $height;
            $loading_optimization_attr = wp_get_loading_optimization_attributes('img', $loading_attr, $context);

            // Add loading optimization attributes if not available.
            $attr = array_merge($attr, $loading_optimization_attr);

            // Omit the `decoding` attribute if the value is invalid according to the spec.
            if(empty($attr['decoding']) || ! in_array($attr['decoding'], ['async', 'sync', 'auto'], true))
            {
                unset($attr['decoding']);
            }

            /*
		 * If the default value of `lazy` for the `loading` attribute is overridden
		 * to omit the attribute for this image, ensure it is not included.
		 */
            if(isset($attr['loading']) && ! $attr['loading'])
            {
                unset($attr['loading']);
            }

            // If the `fetchpriority` attribute is overridden and set to false or an empty string.
            if(isset($attr['fetchpriority']) && ! $attr['fetchpriority'])
            {
                unset($attr['fetchpriority']);
            }

            // Generate 'srcset' and 'sizes' if not already present.
            if(empty($attr['srcset']))
            {
                $image_meta = wp_get_attachment_metadata($attachment_id);

                if(is_array($image_meta))
                {
                    $size_array = [absint($width), absint($height)];
                    $srcset = wp_calculate_image_srcset($size_array, $src, $image_meta, $attachment_id);
                    $sizes = wp_calculate_image_sizes($size_array, $src, $image_meta, $attachment_id);

                    if($srcset && ($sizes || ! empty($attr['sizes'])))
                    {
                        $attr['srcset'] = $srcset;

                        if(empty($attr['sizes']))
                        {
                            $attr['sizes'] = $sizes;
                        }
                    }
                }
            }

            $attr = apply_filters('wp_get_attachment_image_attributes', $attr, $attachment, $size);

            $attr = array_map('esc_attr', $attr);
            $html = rtrim("<img $hwstring");

            foreach($attr as $name => $value)
            {
                $html .= " $name=".'"'.$value.'"';
            }

            $html .= ' />';
        }

        return apply_filters('wp_get_attachment_image', $html, $attachment_id, $size, $icon, $attr);
    }

    function wp_get_attachment_image_url($attachment_id, $size = 'thumbnail', $icon = false)
    {
        $image = wp_get_attachment_image_src($attachment_id, $size, $icon);

        return isset($image[0]) ? $image[0] : false;
    }

    function _wp_get_attachment_relative_path($file)
    {
        $dirname = dirname($file);

        if('.' === $dirname)
        {
            return '';
        }

        if(str_contains($dirname, 'wp-content/uploads'))
        {
            // Get the directory name relative to the upload directory (back compat for pre-2.7 uploads).
            $dirname = substr($dirname, strpos($dirname, 'wp-content/uploads') + 18);
            $dirname = ltrim($dirname, '/');
        }

        return $dirname;
    }

    function _wp_get_image_size_from_meta($size_name, $image_meta)
    {
        if('full' === $size_name)
        {
            return [
                absint($image_meta['width']),
                absint($image_meta['height']),
            ];
        }
        elseif(! empty($image_meta['sizes'][$size_name]))
        {
            return [
                absint($image_meta['sizes'][$size_name]['width']),
                absint($image_meta['sizes'][$size_name]['height']),
            ];
        }

        return false;
    }

    function wp_get_attachment_image_srcset($attachment_id, $size = 'medium', $image_meta = null)
    {
        $image = wp_get_attachment_image_src($attachment_id, $size);

        if(! $image)
        {
            return false;
        }

        if(! is_array($image_meta))
        {
            $image_meta = wp_get_attachment_metadata($attachment_id);
        }

        $image_src = $image[0];
        $size_array = [
            absint($image[1]),
            absint($image[2]),
        ];

        return wp_calculate_image_srcset($size_array, $image_src, $image_meta, $attachment_id);
    }

    function wp_calculate_image_srcset($size_array, $image_src, $image_meta, $attachment_id = 0)
    {
        $image_meta = apply_filters('wp_calculate_image_srcset_meta', $image_meta, $size_array, $image_src, $attachment_id);

        if(empty($image_meta['sizes']) || ! isset($image_meta['file']) || strlen($image_meta['file']) < 4)
        {
            return false;
        }

        $image_sizes = $image_meta['sizes'];

        // Get the width and height of the image.
        $image_width = (int) $size_array[0];
        $image_height = (int) $size_array[1];

        // Bail early if error/no width.
        if($image_width < 1)
        {
            return false;
        }

        $image_basename = wp_basename($image_meta['file']);

        /*
	 * WordPress flattens animated GIFs into one frame when generating intermediate sizes.
	 * To avoid hiding animation in user content, if src is a full size GIF, a srcset attribute is not generated.
	 * If src is an intermediate size GIF, the full size is excluded from srcset to keep a flattened GIF from becoming animated.
	 */
        if(! isset($image_sizes['thumbnail']['mime-type']) || 'image/gif' !== $image_sizes['thumbnail']['mime-type'])
        {
            $image_sizes[] = [
                'width' => $image_meta['width'],
                'height' => $image_meta['height'],
                'file' => $image_basename,
            ];
        }
        elseif(str_contains($image_src, $image_meta['file']))
        {
            return false;
        }

        // Retrieve the uploads sub-directory from the full size image.
        $dirname = _wp_get_attachment_relative_path($image_meta['file']);

        if($dirname)
        {
            $dirname = trailingslashit($dirname);
        }

        $upload_dir = wp_get_upload_dir();
        $image_baseurl = trailingslashit($upload_dir['baseurl']).$dirname;

        /*
	 * If currently on HTTPS, prefer HTTPS URLs when we know they're supported by the domain
	 * (which is to say, when they share the domain name of the current request).
	 */
        if(is_ssl() && ! str_starts_with($image_baseurl, 'https') && parse_url($image_baseurl, PHP_URL_HOST) === $_SERVER['HTTP_HOST'])
        {
            $image_baseurl = set_url_scheme($image_baseurl, 'https');
        }

        /*
	 * Images that have been edited in WordPress after being uploaded will
	 * contain a unique hash. Look for that hash and use it later to filter
	 * out images that are leftovers from previous versions.
	 */
        $image_edited = preg_match('/-e[0-9]{13}/', wp_basename($image_src), $image_edit_hash);

        $max_srcset_image_width = apply_filters('max_srcset_image_width', 2048, $size_array);

        // Array to hold URL candidates.
        $sources = [];

        $src_matched = false;

        /*
	 * Loop through available images. Only use images that are resized
	 * versions of the same edit.
	 */
        foreach($image_sizes as $image)
        {
            $is_src = false;

            // Check if image meta isn't corrupted.
            if(! is_array($image))
            {
                continue;
            }

            // If the file name is part of the `src`, we've confirmed a match.
            if(! $src_matched && str_contains($image_src, $dirname.$image['file']))
            {
                $src_matched = true;
                $is_src = true;
            }

            // Filter out images that are from previous edits.
            if($image_edited && ! strpos($image['file'], $image_edit_hash[0]))
            {
                continue;
            }

            /*
		 * Filters out images that are wider than '$max_srcset_image_width' unless
		 * that file is in the 'src' attribute.
		 */
            if($max_srcset_image_width && $image['width'] > $max_srcset_image_width && ! $is_src)
            {
                continue;
            }

            // If the image dimensions are within 1px of the expected size, use it.
            if(wp_image_matches_ratio($image_width, $image_height, $image['width'], $image['height']))
            {
                // Add the URL, descriptor, and value to the sources array to be returned.
                $source = [
                    'url' => $image_baseurl.$image['file'],
                    'descriptor' => 'w',
                    'value' => $image['width'],
                ];

                // The 'src' image has to be the first in the 'srcset', because of a bug in iOS8. See #35030.
                if($is_src)
                {
                    $sources = [$image['width'] => $source] + $sources;
                }
                else
                {
                    $sources[$image['width']] = $source;
                }
            }
        }

        $sources = apply_filters('wp_calculate_image_srcset', $sources, $size_array, $image_src, $image_meta, $attachment_id);

        // Only return a 'srcset' value if there is more than one source.
        if(! $src_matched || ! is_array($sources) || count($sources) < 2)
        {
            return false;
        }

        $srcset = '';

        foreach($sources as $source)
        {
            $srcset .= str_replace(' ', '%20', $source['url']).' '.$source['value'].$source['descriptor'].', ';
        }

        return rtrim($srcset, ', ');
    }

    function wp_get_attachment_image_sizes($attachment_id, $size = 'medium', $image_meta = null)
    {
        $image = wp_get_attachment_image_src($attachment_id, $size);

        if(! $image)
        {
            return false;
        }

        if(! is_array($image_meta))
        {
            $image_meta = wp_get_attachment_metadata($attachment_id);
        }

        $image_src = $image[0];
        $size_array = [
            absint($image[1]),
            absint($image[2]),
        ];

        return wp_calculate_image_sizes($size_array, $image_src, $image_meta, $attachment_id);
    }

    function wp_calculate_image_sizes($size, $image_src = null, $image_meta = null, $attachment_id = 0)
    {
        $width = 0;

        if(is_array($size))
        {
            $width = absint($size[0]);
        }
        elseif(is_string($size))
        {
            if(! $image_meta && $attachment_id)
            {
                $image_meta = wp_get_attachment_metadata($attachment_id);
            }

            if(is_array($image_meta))
            {
                $size_array = _wp_get_image_size_from_meta($size, $image_meta);
                if($size_array)
                {
                    $width = absint($size_array[0]);
                }
            }
        }

        if(! $width)
        {
            return false;
        }

        // Setup the default 'sizes' attribute.
        $sizes = sprintf('(max-width: %1$dpx) 100vw, %1$dpx', $width);

        return apply_filters('wp_calculate_image_sizes', $sizes, $size, $image_src, $image_meta, $attachment_id);
    }

    function wp_image_file_matches_image_meta($image_location, $image_meta, $attachment_id = 0)
    {
        $match = false;

        // Ensure the $image_meta is valid.
        if(isset($image_meta['file']) && strlen($image_meta['file']) > 4)
        {
            // Remove query args in image URI.
            [$image_location] = explode('?', $image_location);

            // Check if the relative image path from the image meta is at the end of $image_location.
            if(strrpos($image_location, $image_meta['file']) === strlen($image_location) - strlen($image_meta['file']))
            {
                $match = true;
            }
            else
            {
                // Retrieve the uploads sub-directory from the full size image.
                $dirname = _wp_get_attachment_relative_path($image_meta['file']);

                if($dirname)
                {
                    $dirname = trailingslashit($dirname);
                }

                if(! empty($image_meta['original_image']))
                {
                    $relative_path = $dirname.$image_meta['original_image'];

                    if(strrpos($image_location, $relative_path) === strlen($image_location) - strlen($relative_path))
                    {
                        $match = true;
                    }
                }

                if(! $match && ! empty($image_meta['sizes']))
                {
                    foreach($image_meta['sizes'] as $image_size_data)
                    {
                        $relative_path = $dirname.$image_size_data['file'];

                        if(strrpos($image_location, $relative_path) === strlen($image_location) - strlen($relative_path))
                        {
                            $match = true;
                            break;
                        }
                    }
                }
            }
        }

        return apply_filters('wp_image_file_matches_image_meta', $match, $image_location, $image_meta, $attachment_id);
    }

    function wp_image_src_get_dimensions($image_src, $image_meta, $attachment_id = 0)
    {
        $dimensions = false;

        // Is it a full size image?
        if(isset($image_meta['file']) && str_contains($image_src, wp_basename($image_meta['file'])))
        {
            $dimensions = [
                (int) $image_meta['width'],
                (int) $image_meta['height'],
            ];
        }

        if(! $dimensions && ! empty($image_meta['sizes']))
        {
            $src_filename = wp_basename($image_src);

            foreach($image_meta['sizes'] as $image_size_data)
            {
                if($src_filename === $image_size_data['file'])
                {
                    $dimensions = [
                        (int) $image_size_data['width'],
                        (int) $image_size_data['height'],
                    ];

                    break;
                }
            }
        }

        return apply_filters('wp_image_src_get_dimensions', $dimensions, $image_src, $image_meta, $attachment_id);
    }

    function wp_image_add_srcset_and_sizes($image, $image_meta, $attachment_id)
    {
        // Ensure the image meta exists.
        if(empty($image_meta['sizes']))
        {
            return $image;
        }

        $image_src = preg_match('/src="([^"]+)"/', $image, $match_src) ? $match_src[1] : '';
        [$image_src] = explode('?', $image_src);

        // Return early if we couldn't get the image source.
        if(! $image_src)
        {
            return $image;
        }

        // Bail early if an image has been inserted and later edited.
        if(preg_match('/-e[0-9]{13}/', $image_meta['file'], $img_edit_hash) && ! str_contains(wp_basename($image_src), $img_edit_hash[0]))
        {
            return $image;
        }

        $width = preg_match('/ width="([0-9]+)"/', $image, $match_width) ? (int) $match_width[1] : 0;
        $height = preg_match('/ height="([0-9]+)"/', $image, $match_height) ? (int) $match_height[1] : 0;

        if($width && $height)
        {
            $size_array = [$width, $height];
        }
        else
        {
            $size_array = wp_image_src_get_dimensions($image_src, $image_meta, $attachment_id);
            if(! $size_array)
            {
                return $image;
            }
        }

        $srcset = wp_calculate_image_srcset($size_array, $image_src, $image_meta, $attachment_id);

        if($srcset)
        {
            // Check if there is already a 'sizes' attribute.
            $sizes = strpos($image, ' sizes=');

            if(! $sizes)
            {
                $sizes = wp_calculate_image_sizes($size_array, $image_src, $image_meta, $attachment_id);
            }
        }

        if($srcset && $sizes)
        {
            // Format the 'srcset' and 'sizes' string and escape attributes.
            $attr = sprintf(' srcset="%s"', esc_attr($srcset));

            if(is_string($sizes))
            {
                $attr .= sprintf(' sizes="%s"', esc_attr($sizes));
            }

            // Add the srcset and sizes attributes to the image markup.
            return preg_replace('/<img ([^>]+?)[\/ ]*>/', '<img $1'.$attr.' />', $image);
        }

        return $image;
    }

    function wp_lazy_loading_enabled($tag_name, $context)
    {
        /*
	 * By default add to all 'img' and 'iframe' tags.
	 * See https://html.spec.whatwg.org/multipage/embedded-content.html#attr-img-loading
	 * See https://html.spec.whatwg.org/multipage/iframe-embed-object.html#attr-iframe-loading
	 */
        $default = ('img' === $tag_name || 'iframe' === $tag_name);

        return (bool) apply_filters('wp_lazy_loading_enabled', $default, $tag_name, $context);
    }

    function wp_filter_content_tags($content, $context = null)
    {
        if(null === $context)
        {
            $context = current_filter();
        }

        $add_iframe_loading_attr = wp_lazy_loading_enabled('iframe', $context);

        if(! preg_match_all('/<(img|iframe)\s[^>]+>/', $content, $matches, PREG_SET_ORDER))
        {
            return $content;
        }

        // List of the unique `img` tags found in $content.
        $images = [];

        // List of the unique `iframe` tags found in $content.
        $iframes = [];

        foreach($matches as $match)
        {
            [$tag, $tag_name] = $match;

            switch($tag_name)
            {
                case 'img':
                    if(preg_match('/wp-image-([0-9]+)/i', $tag, $class_id))
                    {
                        $attachment_id = absint($class_id[1]);

                        if($attachment_id)
                        {
                            /*
						 * If exactly the same image tag is used more than once, overwrite it.
						 * All identical tags will be replaced later with 'str_replace()'.
						 */
                            $images[$tag] = $attachment_id;
                            break;
                        }
                    }
                    $images[$tag] = 0;
                    break;
                case 'iframe':
                    $iframes[$tag] = 0;
                    break;
            }
        }

        // Reduce the array to unique attachment IDs.
        $attachment_ids = array_unique(array_filter(array_values($images)));

        if(count($attachment_ids) > 1)
        {
            /*
		 * Warm the object cache with post and meta information for all found
		 * images to avoid making individual database calls.
		 */
            _prime_post_caches($attachment_ids, false, true);
        }

        // Iterate through the matches in order of occurrence as it is relevant for whether or not to lazy-load.
        foreach($matches as $match)
        {
            // Filter an image match.
            if(isset($images[$match[0]]))
            {
                $filtered_image = $match[0];
                $attachment_id = $images[$match[0]];

                // Add 'width' and 'height' attributes if applicable.
                if($attachment_id > 0 && ! str_contains($filtered_image, ' width=') && ! str_contains($filtered_image, ' height='))
                {
                    $filtered_image = wp_img_tag_add_width_and_height_attr($filtered_image, $context, $attachment_id);
                }

                // Add 'srcset' and 'sizes' attributes if applicable.
                if($attachment_id > 0 && ! str_contains($filtered_image, ' srcset='))
                {
                    $filtered_image = wp_img_tag_add_srcset_and_sizes_attr($filtered_image, $context, $attachment_id);
                }

                // Add loading optimization attributes if applicable.
                $filtered_image = wp_img_tag_add_loading_optimization_attrs($filtered_image, $context);

                // Add 'decoding=async' attribute unless a 'decoding' attribute is already present.
                if(! str_contains($filtered_image, ' decoding='))
                {
                    $filtered_image = wp_img_tag_add_decoding_attr($filtered_image, $context);
                }

                $filtered_image = apply_filters('wp_content_img_tag', $filtered_image, $context, $attachment_id);

                if($filtered_image !== $match[0])
                {
                    $content = str_replace($match[0], $filtered_image, $content);
                }

                /*
			 * Unset image lookup to not run the same logic again unnecessarily if the same image tag is used more than
			 * once in the same blob of content.
			 */
                unset($images[$match[0]]);
            }

            // Filter an iframe match.
            if(isset($iframes[$match[0]]))
            {
                $filtered_iframe = $match[0];

                // Add 'loading' attribute if applicable.
                if($add_iframe_loading_attr && ! str_contains($filtered_iframe, ' loading='))
                {
                    $filtered_iframe = wp_iframe_tag_add_loading_attr($filtered_iframe, $context);
                }

                if($filtered_iframe !== $match[0])
                {
                    $content = str_replace($match[0], $filtered_iframe, $content);
                }

                /*
			 * Unset iframe lookup to not run the same logic again unnecessarily if the same iframe tag is used more
			 * than once in the same blob of content.
			 */
                unset($iframes[$match[0]]);
            }
        }

        return $content;
    }

    function wp_img_tag_add_loading_optimization_attrs($image, $context)
    {
        $width = preg_match('/ width=["\']([0-9]+)["\']/', $image, $match_width) ? (int) $match_width[1] : null;
        $height = preg_match('/ height=["\']([0-9]+)["\']/', $image, $match_height) ? (int) $match_height[1] : null;
        $loading_val = preg_match('/ loading=["\']([A-Za-z]+)["\']/', $image, $match_loading) ? $match_loading[1] : null;
        $fetchpriority_val = preg_match('/ fetchpriority=["\']([A-Za-z]+)["\']/', $image, $match_fetchpriority) ? $match_fetchpriority[1] : null;

        /*
	 * Get loading optimization attributes to use.
	 * This must occur before the conditional check below so that even images
	 * that are ineligible for being lazy-loaded are considered.
	 */
        $optimization_attrs = wp_get_loading_optimization_attributes('img', [
            'width' => $width,
            'height' => $height,
            'loading' => $loading_val,
            'fetchpriority' => $fetchpriority_val,
        ],                                                           $context);

        // Images should have source and dimension attributes for the loading optimization attributes to be added.
        if(! str_contains($image, ' src="') || ! str_contains($image, ' width="') || ! str_contains($image, ' height="'))
        {
            return $image;
        }

        // Retained for backward compatibility.
        $loading_attrs_enabled = wp_lazy_loading_enabled('img', $context);

        if(empty($loading_val) && $loading_attrs_enabled)
        {
            $filtered_loading_attr = apply_filters('wp_img_tag_add_loading_attr', isset($optimization_attrs['loading']) ? $optimization_attrs['loading'] : false, $image, $context);

            // Validate the values after filtering.
            if(isset($optimization_attrs['loading']) && ! $filtered_loading_attr)
            {
                // Unset `loading` attributes if `$filtered_loading_attr` is set to `false`.
                unset($optimization_attrs['loading']);
            }
            elseif(in_array($filtered_loading_attr, ['lazy', 'eager'], true))
            {
                /*
			 * If the filter changed the loading attribute to "lazy" when a fetchpriority attribute
			 * with value "high" is already present, trigger a warning since those two attribute
			 * values should be mutually exclusive.
			 *
			 * The same warning is present in `wp_get_loading_optimization_attributes()`, and here it
			 * is only intended for the specific scenario where the above filtered caused the problem.
			 */
                if(isset($optimization_attrs['fetchpriority']) && 'high' === $optimization_attrs['fetchpriority'] && (isset($optimization_attrs['loading']) ? $optimization_attrs['loading'] : false) !== $filtered_loading_attr && 'lazy' === $filtered_loading_attr)
                {
                    _doing_it_wrong(__FUNCTION__, __('An image should not be lazy-loaded and marked as high priority at the same time.'), '6.3.0');
                }

                // The filtered value will still be respected.
                $optimization_attrs['loading'] = $filtered_loading_attr;
            }

            if(! empty($optimization_attrs['loading']))
            {
                $image = str_replace('<img', '<img loading="'.esc_attr($optimization_attrs['loading']).'"', $image);
            }
        }

        if(empty($fetchpriority_val) && ! empty($optimization_attrs['fetchpriority']))
        {
            $image = str_replace('<img', '<img fetchpriority="'.esc_attr($optimization_attrs['fetchpriority']).'"', $image);
        }

        return $image;
    }

    function wp_img_tag_add_decoding_attr($image, $context)
    {
        /*
	 * Only apply the decoding attribute to images that have a src attribute that
	 * starts with a double quote, ensuring escaped JSON is also excluded.
	 */
        if(! str_contains($image, ' src="'))
        {
            return $image;
        }

        $value = apply_filters('wp_img_tag_add_decoding_attr', 'async', $image, $context);

        if(in_array($value, ['async', 'sync', 'auto'], true))
        {
            $image = str_replace('<img ', '<img decoding="'.esc_attr($value).'" ', $image);
        }

        return $image;
    }

    function wp_img_tag_add_width_and_height_attr($image, $context, $attachment_id)
    {
        $image_src = preg_match('/src="([^"]+)"/', $image, $match_src) ? $match_src[1] : '';
        [$image_src] = explode('?', $image_src);

        // Return early if we couldn't get the image source.
        if(! $image_src)
        {
            return $image;
        }

        $add = apply_filters('wp_img_tag_add_width_and_height_attr', true, $image, $context, $attachment_id);

        if(true === $add)
        {
            $image_meta = wp_get_attachment_metadata($attachment_id);
            $size_array = wp_image_src_get_dimensions($image_src, $image_meta, $attachment_id);

            if($size_array)
            {
                $hw = trim(image_hwstring($size_array[0], $size_array[1]));

                return str_replace('<img', "<img {$hw}", $image);
            }
        }

        return $image;
    }

    function wp_img_tag_add_srcset_and_sizes_attr($image, $context, $attachment_id)
    {
        $add = apply_filters('wp_img_tag_add_srcset_and_sizes_attr', true, $image, $context, $attachment_id);

        if(true === $add)
        {
            $image_meta = wp_get_attachment_metadata($attachment_id);

            return wp_image_add_srcset_and_sizes($image, $image_meta, $attachment_id);
        }

        return $image;
    }

    function wp_iframe_tag_add_loading_attr($iframe, $context)
    {
        /*
	 * Iframes with fallback content (see `wp_filter_oembed_result()`) should not be lazy-loaded because they are
	 * visually hidden initially.
	 */
        if(str_contains($iframe, ' data-secret="'))
        {
            return $iframe;
        }

        /*
	 * Get loading attribute value to use. This must occur before the conditional check below so that even iframes that
	 * are ineligible for being lazy-loaded are considered.
	 */
        $optimization_attrs = wp_get_loading_optimization_attributes('iframe', [
            /*
         * The concrete values for width and height are not important here for now
         * since fetchpriority is not yet supported for iframes.
         * TODO: Use WP_HTML_Tag_Processor to extract actual values once support is
         * added.
         */ 'width' => str_contains($iframe, ' width="') ? 100 : null,
            'height' => str_contains($iframe, ' height="') ? 100 : null,
            // This function is never called when a 'loading' attribute is already present.
            'loading' => null,
        ],                                                           $context);

        // Iframes should have source and dimension attributes for the `loading` attribute to be added.
        if(! str_contains($iframe, ' src="') || ! str_contains($iframe, ' width="') || ! str_contains($iframe, ' height="'))
        {
            return $iframe;
        }

        $value = isset($optimization_attrs['loading']) ? $optimization_attrs['loading'] : false;

        $value = apply_filters('wp_iframe_tag_add_loading_attr', $value, $iframe, $context);

        if($value)
        {
            if(! in_array($value, ['lazy', 'eager'], true))
            {
                $value = 'lazy';
            }

            return str_replace('<iframe', '<iframe loading="'.esc_attr($value).'"', $iframe);
        }

        return $iframe;
    }

    function _wp_post_thumbnail_class_filter($attr)
    {
        $attr['class'] .= ' wp-post-image';

        return $attr;
    }

    function _wp_post_thumbnail_class_filter_add($attr)
    {
        add_filter('wp_get_attachment_image_attributes', '_wp_post_thumbnail_class_filter');
    }

    function _wp_post_thumbnail_class_filter_remove($attr)
    {
        remove_filter('wp_get_attachment_image_attributes', '_wp_post_thumbnail_class_filter');
    }

    function _wp_post_thumbnail_context_filter($context)
    {
        return 'the_post_thumbnail';
    }

    function _wp_post_thumbnail_context_filter_add()
    {
        add_filter('wp_get_attachment_image_context', '_wp_post_thumbnail_context_filter');
    }

    function _wp_post_thumbnail_context_filter_remove()
    {
        remove_filter('wp_get_attachment_image_context', '_wp_post_thumbnail_context_filter');
    }

    add_shortcode('wp_caption', 'img_caption_shortcode');
    add_shortcode('caption', 'img_caption_shortcode');

    function img_caption_shortcode($attr, $content = '')
    {
        if(! $attr)
        {
            $attr = [];
        }

        // New-style shortcode with the caption inside the shortcode with the link and image tags.
        if(! isset($attr['caption']))
        {
            if(preg_match('#((?:<a [^>]+>\s*)?<img [^>]+>(?:\s*</a>)?)(.*)#is', $content, $matches))
            {
                $content = $matches[1];
                $attr['caption'] = trim($matches[2]);
            }
        }
        elseif(str_contains($attr['caption'], '<'))
        {
            $attr['caption'] = wp_kses($attr['caption'], 'post');
        }

        $output = apply_filters('img_caption_shortcode', '', $attr, $content);

        if(! empty($output))
        {
            return $output;
        }

        $atts = shortcode_atts([
                                   'id' => '',
                                   'caption_id' => '',
                                   'align' => 'alignnone',
                                   'width' => '',
                                   'caption' => '',
                                   'class' => '',
                               ], $attr, 'caption');

        $atts['width'] = (int) $atts['width'];

        if($atts['width'] < 1 || empty($atts['caption']))
        {
            return $content;
        }

        $id = '';
        $caption_id = '';
        $describedby = '';

        if($atts['id'])
        {
            $atts['id'] = sanitize_html_class($atts['id']);
            $id = 'id="'.esc_attr($atts['id']).'" ';
        }

        if($atts['caption_id'])
        {
            $atts['caption_id'] = sanitize_html_class($atts['caption_id']);
        }
        elseif($atts['id'])
        {
            $atts['caption_id'] = 'caption-'.str_replace('_', '-', $atts['id']);
        }

        if($atts['caption_id'])
        {
            $caption_id = 'id="'.esc_attr($atts['caption_id']).'" ';
            $describedby = 'aria-describedby="'.esc_attr($atts['caption_id']).'" ';
        }

        $class = trim('wp-caption '.$atts['align'].' '.$atts['class']);

        $html5 = current_theme_supports('html5', 'caption');
        // HTML5 captions never added the extra 10px to the image width.
        $width = $html5 ? $atts['width'] : (10 + $atts['width']);

        $caption_width = apply_filters('img_caption_shortcode_width', $width, $atts, $content);

        $style = '';

        if($caption_width)
        {
            $style = 'style="width: '.(int) $caption_width.'px" ';
        }

        if($html5)
        {
            $html = sprintf('<figure %s%s%sclass="%s">%s%s</figure>', $id, $describedby, $style, esc_attr($class), do_shortcode($content), sprintf('<figcaption %sclass="wp-caption-text">%s</figcaption>', $caption_id, $atts['caption']));
        }
        else
        {
            $html = sprintf('<div %s%sclass="%s">%s%s</div>', $id, $style, esc_attr($class), str_replace('<img ', '<img '.$describedby, do_shortcode($content)), sprintf('<p %sclass="wp-caption-text">%s</p>', $caption_id, $atts['caption']));
        }

        return $html;
    }

    add_shortcode('gallery', 'gallery_shortcode');

    function gallery_shortcode($attr)
    {
        $post = get_post();

        static $instance = 0;
        ++$instance;

        if(! empty($attr['ids']))
        {
            // 'ids' is explicitly ordered, unless you specify otherwise.
            if(empty($attr['orderby']))
            {
                $attr['orderby'] = 'post__in';
            }
            $attr['include'] = $attr['ids'];
        }

        $output = apply_filters('post_gallery', '', $attr, $instance);

        if(! empty($output))
        {
            return $output;
        }

        $html5 = current_theme_supports('html5', 'gallery');
        $atts = shortcode_atts([
                                   'order' => 'ASC',
                                   'orderby' => 'menu_order ID',
                                   'id' => $post ? $post->ID : 0,
                                   'itemtag' => $html5 ? 'figure' : 'dl',
                                   'icontag' => $html5 ? 'div' : 'dt',
                                   'captiontag' => $html5 ? 'figcaption' : 'dd',
                                   'columns' => 3,
                                   'size' => 'thumbnail',
                                   'include' => '',
                                   'exclude' => '',
                                   'link' => '',
                               ], $attr, 'gallery');

        $id = (int) $atts['id'];

        if(! empty($atts['include']))
        {
            $_attachments = get_posts([
                                          'include' => $atts['include'],
                                          'post_status' => 'inherit',
                                          'post_type' => 'attachment',
                                          'post_mime_type' => 'image',
                                          'order' => $atts['order'],
                                          'orderby' => $atts['orderby'],
                                      ]);

            $attachments = [];
            foreach($_attachments as $key => $val)
            {
                $attachments[$val->ID] = $_attachments[$key];
            }
        }
        elseif(! empty($atts['exclude']))
        {
            $attachments = get_children([
                                            'post_parent' => $id,
                                            'exclude' => $atts['exclude'],
                                            'post_status' => 'inherit',
                                            'post_type' => 'attachment',
                                            'post_mime_type' => 'image',
                                            'order' => $atts['order'],
                                            'orderby' => $atts['orderby'],
                                        ]);
        }
        else
        {
            $attachments = get_children([
                                            'post_parent' => $id,
                                            'post_status' => 'inherit',
                                            'post_type' => 'attachment',
                                            'post_mime_type' => 'image',
                                            'order' => $atts['order'],
                                            'orderby' => $atts['orderby'],
                                        ]);
        }

        if(empty($attachments))
        {
            return '';
        }

        if(is_feed())
        {
            $output = "\n";
            foreach($attachments as $att_id => $attachment)
            {
                if(! empty($atts['link']))
                {
                    if('none' === $atts['link'])
                    {
                        $output .= wp_get_attachment_image($att_id, $atts['size'], false, $attr);
                    }
                    else
                    {
                        $output .= wp_get_attachment_link($att_id, $atts['size'], false);
                    }
                }
                else
                {
                    $output .= wp_get_attachment_link($att_id, $atts['size'], true);
                }
                $output .= "\n";
            }

            return $output;
        }

        $itemtag = tag_escape($atts['itemtag']);
        $captiontag = tag_escape($atts['captiontag']);
        $icontag = tag_escape($atts['icontag']);
        $valid_tags = wp_kses_allowed_html('post');
        if(! isset($valid_tags[$itemtag]))
        {
            $itemtag = 'dl';
        }
        if(! isset($valid_tags[$captiontag]))
        {
            $captiontag = 'dd';
        }
        if(! isset($valid_tags[$icontag]))
        {
            $icontag = 'dt';
        }

        $columns = (int) $atts['columns'];
        $itemwidth = $columns > 0 ? floor(100 / $columns) : 100;
        $float = is_rtl() ? 'right' : 'left';

        $selector = "gallery-{$instance}";

        $gallery_style = '';

        if(apply_filters('use_default_gallery_style', ! $html5))
        {
            $type_attr = current_theme_supports('html5', 'style') ? '' : ' type="text/css"';

            $gallery_style = "
		<style{$type_attr}>
			#{$selector} {
				margin: auto;
			}
			#{$selector} .gallery-item {
				float: {$float};
				margin-top: 10px;
				text-align: center;
				width: {$itemwidth}%;
			}
			#{$selector} img {
				border: 2px solid #cfcfcf;
			}
			#{$selector} .gallery-caption {
				margin-left: 0;
			}
			/* see gallery_shortcode() in wp-includes/media.php */
		</style>\n\t\t";
        }

        $size_class = sanitize_html_class(is_array($atts['size']) ? implode('x', $atts['size']) : $atts['size']);
        $gallery_div = "<div id='$selector' class='gallery galleryid-{$id} gallery-columns-{$columns} gallery-size-{$size_class}'>";

        $output = apply_filters('gallery_style', $gallery_style.$gallery_div);

        $i = 0;

        foreach($attachments as $id => $attachment)
        {
            $attr = (trim($attachment->post_excerpt)) ? ['aria-describedby' => "$selector-$id"] : '';

            if(! empty($atts['link']) && 'file' === $atts['link'])
            {
                $image_output = wp_get_attachment_link($id, $atts['size'], false, false, false, $attr);
            }
            elseif(! empty($atts['link']) && 'none' === $atts['link'])
            {
                $image_output = wp_get_attachment_image($id, $atts['size'], false, $attr);
            }
            else
            {
                $image_output = wp_get_attachment_link($id, $atts['size'], true, false, false, $attr);
            }

            $image_meta = wp_get_attachment_metadata($id);

            $orientation = '';

            if(isset($image_meta['height'], $image_meta['width']))
            {
                $orientation = ($image_meta['height'] > $image_meta['width']) ? 'portrait' : 'landscape';
            }

            $output .= "<{$itemtag} class='gallery-item'>";
            $output .= "
			<{$icontag} class='gallery-icon {$orientation}'>
				$image_output
			</{$icontag}>";

            if($captiontag && trim($attachment->post_excerpt))
            {
                $output .= "
				<{$captiontag} class='wp-caption-text gallery-caption' id='$selector-$id'>
				".wptexturize($attachment->post_excerpt)."
				</{$captiontag}>";
            }

            $output .= "</{$itemtag}>";

            if(! $html5 && $columns > 0 && 0 === ++$i % $columns)
            {
                $output .= '<br style="clear: both" />';
            }
        }

        if(! $html5 && $columns > 0 && 0 !== $i % $columns)
        {
            $output .= "
			<br style='clear: both' />";
        }

        $output .= "
		</div>\n";

        return $output;
    }

    function wp_underscore_playlist_templates()
    {
        ?>
        <script type="text/html" id="tmpl-wp-playlist-current-item">
            <# if ( data.thumb && data.thumb.src ) { #>
            <img src="{{ data.thumb.src }}" alt=""/>
            <# } #>
            <div class="wp-playlist-caption">
		<span class="wp-playlist-item-meta wp-playlist-item-title">
			<# if ( data.meta.album || data.meta.artist ) { #>
				<?php
                    /* translators: %s: Playlist item title. */
                    printf(_x('&#8220;%s&#8221;', 'playlist item title'), '{{ data.title }}');
                ?>
			<# } else { #>
				{{ data.title }}
			<# } #>
		</span>
                <# if ( data.meta.album ) { #><span class="wp-playlist-item-meta wp-playlist-item-album">{{ data.meta.album }}</span><#
                } #>
                <# if ( data.meta.artist ) { #><span class="wp-playlist-item-meta wp-playlist-item-artist">{{ data.meta.artist }}</span><#
                } #>
            </div>
        </script>
        <script type="text/html" id="tmpl-wp-playlist-item">
            <div class="wp-playlist-item">
                <a class="wp-playlist-caption" href="{{ data.src }}">
                    {{ data.index ? ( data.index + '. ' ) : '' }}
                    <# if ( data.caption ) { #>
                    {{ data.caption }}
                    <# } else { #>
                    <# if ( data.artists && data.meta.artist ) { #>
                    <span class="wp-playlist-item-title">
						<?php
                            /* translators: %s: Playlist item title. */
                            printf(_x('&#8220;%s&#8221;', 'playlist item title'), '{{{ data.title }}}');
                        ?>
					</span>
                    <span class="wp-playlist-item-artist"> &mdash; {{ data.meta.artist }}</span>
                    <# } else { #>
                    <span class="wp-playlist-item-title">{{{ data.title }}}</span>
                    <# } #>
                    <# } #>
                </a>
                <# if ( data.meta.length_formatted ) { #>
                <div class="wp-playlist-item-length">{{ data.meta.length_formatted }}</div>
                <# } #>
            </div>
        </script>
        <?php
    }

    function wp_playlist_scripts($type)
    {
        wp_enqueue_style('wp-mediaelement');
        wp_enqueue_script('wp-playlist');
        ?>
        <!--[if lt IE 9]><script>document.createElement('<?php echo esc_js($type); ?>');</script><![endif]-->
        <?php
        add_action('wp_footer', 'wp_underscore_playlist_templates', 0);
        add_action('admin_footer', 'wp_underscore_playlist_templates', 0);
    }

    function wp_playlist_shortcode($attr)
    {
        global $content_width;
        $post = get_post();

        static $instance = 0;
        ++$instance;

        if(! empty($attr['ids']))
        {
            // 'ids' is explicitly ordered, unless you specify otherwise.
            if(empty($attr['orderby']))
            {
                $attr['orderby'] = 'post__in';
            }
            $attr['include'] = $attr['ids'];
        }

        $output = apply_filters('post_playlist', '', $attr, $instance);

        if(! empty($output))
        {
            return $output;
        }

        $atts = shortcode_atts([
                                   'type' => 'audio',
                                   'order' => 'ASC',
                                   'orderby' => 'menu_order ID',
                                   'id' => $post ? $post->ID : 0,
                                   'include' => '',
                                   'exclude' => '',
                                   'style' => 'light',
                                   'tracklist' => true,
                                   'tracknumbers' => true,
                                   'images' => true,
                                   'artists' => true,
                               ], $attr, 'playlist');

        $id = (int) $atts['id'];

        if('audio' !== $atts['type'])
        {
            $atts['type'] = 'video';
        }

        $args = [
            'post_status' => 'inherit',
            'post_type' => 'attachment',
            'post_mime_type' => $atts['type'],
            'order' => $atts['order'],
            'orderby' => $atts['orderby'],
        ];

        if(! empty($atts['include']))
        {
            $args['include'] = $atts['include'];
            $_attachments = get_posts($args);

            $attachments = [];
            foreach($_attachments as $key => $val)
            {
                $attachments[$val->ID] = $_attachments[$key];
            }
        }
        elseif(! empty($atts['exclude']))
        {
            $args['post_parent'] = $id;
            $args['exclude'] = $atts['exclude'];
            $attachments = get_children($args);
        }
        else
        {
            $args['post_parent'] = $id;
            $attachments = get_children($args);
        }

        if(empty($attachments))
        {
            return '';
        }

        if(is_feed())
        {
            $output = "\n";
            foreach($attachments as $att_id => $attachment)
            {
                $output .= wp_get_attachment_link($att_id)."\n";
            }

            return $output;
        }

        $outer = 22; // Default padding and border of wrapper.

        $default_width = 640;
        $default_height = 360;

        $theme_width = empty($content_width) ? $default_width : ($content_width - $outer);
        $theme_height = empty($content_width) ? $default_height : round(($default_height * $theme_width) / $default_width);

        $data = [
            'type' => $atts['type'],
            // Don't pass strings to JSON, will be truthy in JS.
            'tracklist' => wp_validate_boolean($atts['tracklist']),
            'tracknumbers' => wp_validate_boolean($atts['tracknumbers']),
            'images' => wp_validate_boolean($atts['images']),
            'artists' => wp_validate_boolean($atts['artists']),
        ];

        $tracks = [];
        foreach($attachments as $attachment)
        {
            $url = wp_get_attachment_url($attachment->ID);
            $ftype = wp_check_filetype($url, wp_get_mime_types());
            $track = [
                'src' => $url,
                'type' => $ftype['type'],
                'title' => $attachment->post_title,
                'caption' => $attachment->post_excerpt,
                'description' => $attachment->post_content,
            ];

            $track['meta'] = [];
            $meta = wp_get_attachment_metadata($attachment->ID);
            if(! empty($meta))
            {
                foreach(wp_get_attachment_id3_keys($attachment) as $key => $label)
                {
                    if(! empty($meta[$key]))
                    {
                        $track['meta'][$key] = $meta[$key];
                    }
                }

                if('video' === $atts['type'])
                {
                    if(! empty($meta['width']) && ! empty($meta['height']))
                    {
                        $width = $meta['width'];
                        $height = $meta['height'];
                        $theme_height = round(($height * $theme_width) / $width);
                    }
                    else
                    {
                        $width = $default_width;
                        $height = $default_height;
                    }

                    $track['dimensions'] = [
                        'original' => compact('width', 'height'),
                        'resized' => [
                            'width' => $theme_width,
                            'height' => $theme_height,
                        ],
                    ];
                }
            }

            if($atts['images'])
            {
                $thumb_id = get_post_thumbnail_id($attachment->ID);
                if(! empty($thumb_id))
                {
                    [$src, $width, $height] = wp_get_attachment_image_src($thumb_id, 'full');
                    $track['image'] = compact('src', 'width', 'height');
                    [$src, $width, $height] = wp_get_attachment_image_src($thumb_id, 'thumbnail');
                    $track['thumb'] = compact('src', 'width', 'height');
                }
                else
                {
                    $src = wp_mime_type_icon($attachment->ID);
                    $width = 48;
                    $height = 64;
                    $track['image'] = compact('src', 'width', 'height');
                    $track['thumb'] = compact('src', 'width', 'height');
                }
            }

            $tracks[] = $track;
        }
        $data['tracks'] = $tracks;

        $safe_type = esc_attr($atts['type']);
        $safe_style = esc_attr($atts['style']);

        ob_start();

        if(1 === $instance)
        {
            do_action('wp_playlist_scripts', $atts['type'], $atts['style']);
        }
        ?>
        <div class="wp-playlist wp-<?php echo $safe_type; ?>-playlist wp-playlist-<?php echo $safe_style; ?>">
            <?php if('audio' === $atts['type']) : ?>
                <div class="wp-playlist-current-item"></div>
            <?php endif; ?>
            <<?php echo $safe_type; ?> controls="controls" preload="none" width="<?php echo (int) $theme_width; ?>"
            <?php
                if('video' === $safe_type)
                {
                    echo ' height="', (int) $theme_height, '"';
                }
            ?>
            >
        </<?php echo $safe_type; ?>>
        <div class="wp-playlist-next"></div>
        <div class="wp-playlist-prev"></div>
        <noscript>
            <ol>
                <?php
                    foreach($attachments as $att_id => $attachment)
                    {
                        printf('<li>%s</li>', wp_get_attachment_link($att_id));
                    }
                ?>
            </ol>
        </noscript>
        <script type="application/json" class="wp-playlist-script"><?php echo wp_json_encode($data); ?></script>
        </div>
        <?php
        return ob_get_clean();
    }

    add_shortcode('playlist', 'wp_playlist_shortcode');

    function wp_mediaelement_fallback($url)
    {
        return apply_filters('wp_mediaelement_fallback', sprintf('<a href="%1$s">%1$s</a>', esc_url($url)), $url);
    }

    function wp_get_audio_extensions()
    {
        return apply_filters('wp_audio_extensions', ['mp3', 'ogg', 'flac', 'm4a', 'wav']);
    }

    function wp_get_attachment_id3_keys($attachment, $context = 'display')
    {
        $fields = [
            'artist' => __('Artist'),
            'album' => __('Album'),
        ];

        if('display' === $context)
        {
            $fields['genre'] = __('Genre');
            $fields['year'] = __('Year');
            $fields['length_formatted'] = _x('Length', 'video or audio');
        }
        elseif('js' === $context)
        {
            $fields['bitrate'] = __('Bitrate');
            $fields['bitrate_mode'] = __('Bitrate Mode');
        }

        return apply_filters('wp_get_attachment_id3_keys', $fields, $attachment, $context);
    }

    function wp_audio_shortcode($attr, $content = '')
    {
        $post_id = get_post() ? get_the_ID() : 0;

        static $instance = 0;
        ++$instance;

        $override = apply_filters('wp_audio_shortcode_override', '', $attr, $content, $instance);

        if('' !== $override)
        {
            return $override;
        }

        $audio = null;

        $default_types = wp_get_audio_extensions();
        $defaults_atts = [
            'src' => '',
            'loop' => '',
            'autoplay' => '',
            'preload' => 'none',
            'class' => 'wp-audio-shortcode',
            'style' => 'width: 100%;',
        ];
        foreach($default_types as $type)
        {
            $defaults_atts[$type] = '';
        }

        $atts = shortcode_atts($defaults_atts, $attr, 'audio');

        $primary = false;
        if(! empty($atts['src']))
        {
            $type = wp_check_filetype($atts['src'], wp_get_mime_types());

            if(! in_array(strtolower($type['ext']), $default_types, true))
            {
                return sprintf('<a class="wp-embedded-audio" href="%s">%s</a>', esc_url($atts['src']), esc_html($atts['src']));
            }

            $primary = true;
            array_unshift($default_types, 'src');
        }
        else
        {
            foreach($default_types as $ext)
            {
                if(! empty($atts[$ext]))
                {
                    $type = wp_check_filetype($atts[$ext], wp_get_mime_types());

                    if(strtolower($type['ext']) === $ext)
                    {
                        $primary = true;
                    }
                }
            }
        }

        if(! $primary)
        {
            $audios = get_attached_media('audio', $post_id);

            if(empty($audios))
            {
                return;
            }

            $audio = reset($audios);
            $atts['src'] = wp_get_attachment_url($audio->ID);

            if(empty($atts['src']))
            {
                return;
            }

            array_unshift($default_types, 'src');
        }

        $library = apply_filters('wp_audio_shortcode_library', 'mediaelement');

        if('mediaelement' === $library && did_action('init'))
        {
            wp_enqueue_style('wp-mediaelement');
            wp_enqueue_script('wp-mediaelement');
        }

        $atts['class'] = apply_filters('wp_audio_shortcode_class', $atts['class'], $atts);

        $html_atts = [
            'class' => $atts['class'],
            'id' => sprintf('audio-%d-%d', $post_id, $instance),
            'loop' => wp_validate_boolean($atts['loop']),
            'autoplay' => wp_validate_boolean($atts['autoplay']),
            'preload' => $atts['preload'],
            'style' => $atts['style'],
        ];

        // These ones should just be omitted altogether if they are blank.
        foreach(['loop', 'autoplay', 'preload'] as $a)
        {
            if(empty($html_atts[$a]))
            {
                unset($html_atts[$a]);
            }
        }

        $attr_strings = [];

        foreach($html_atts as $k => $v)
        {
            $attr_strings[] = $k.'="'.esc_attr($v).'"';
        }

        $html = '';

        if('mediaelement' === $library && 1 === $instance)
        {
            $html .= "<!--[if lt IE 9]><script>document.createElement('audio');</script><![endif]-->\n";
        }

        $html .= sprintf('<audio %s controls="controls">', implode(' ', $attr_strings));

        $fileurl = '';
        $source = '<source type="%s" src="%s" />';

        foreach($default_types as $fallback)
        {
            if(! empty($atts[$fallback]))
            {
                if(empty($fileurl))
                {
                    $fileurl = $atts[$fallback];
                }

                $type = wp_check_filetype($atts[$fallback], wp_get_mime_types());
                $url = add_query_arg('_', $instance, $atts[$fallback]);
                $html .= sprintf($source, $type['type'], esc_url($url));
            }
        }

        if('mediaelement' === $library)
        {
            $html .= wp_mediaelement_fallback($fileurl);
        }

        $html .= '</audio>';

        return apply_filters('wp_audio_shortcode', $html, $atts, $audio, $post_id, $library);
    }

    add_shortcode('audio', 'wp_audio_shortcode');

    function wp_get_video_extensions()
    {
        return apply_filters('wp_video_extensions', ['mp4', 'm4v', 'webm', 'ogv', 'flv']);
    }

    function wp_video_shortcode($attr, $content = '')
    {
        global $content_width;
        $post_id = get_post() ? get_the_ID() : 0;

        static $instance = 0;
        ++$instance;

        $override = apply_filters('wp_video_shortcode_override', '', $attr, $content, $instance);

        if('' !== $override)
        {
            return $override;
        }

        $video = null;

        $default_types = wp_get_video_extensions();
        $defaults_atts = [
            'src' => '',
            'poster' => '',
            'loop' => '',
            'autoplay' => '',
            'muted' => 'false',
            'preload' => 'metadata',
            'width' => 640,
            'height' => 360,
            'class' => 'wp-video-shortcode',
        ];

        foreach($default_types as $type)
        {
            $defaults_atts[$type] = '';
        }

        $atts = shortcode_atts($defaults_atts, $attr, 'video');

        if(is_admin())
        {
            // Shrink the video so it isn't huge in the admin.
            if($atts['width'] > $defaults_atts['width'])
            {
                $atts['height'] = round(($atts['height'] * $defaults_atts['width']) / $atts['width']);
                $atts['width'] = $defaults_atts['width'];
            }
        }
        else
        {
            // If the video is bigger than the theme.
            if(! empty($content_width) && $atts['width'] > $content_width)
            {
                $atts['height'] = round(($atts['height'] * $content_width) / $atts['width']);
                $atts['width'] = $content_width;
            }
        }

        $is_vimeo = false;
        $is_youtube = false;
        $yt_pattern = '#^https?://(?:www\.)?(?:youtube\.com/watch|youtu\.be/)#';
        $vimeo_pattern = '#^https?://(.+\.)?vimeo\.com/.*#';

        $primary = false;
        if(! empty($atts['src']))
        {
            $is_vimeo = (preg_match($vimeo_pattern, $atts['src']));
            $is_youtube = (preg_match($yt_pattern, $atts['src']));

            if(! $is_youtube && ! $is_vimeo)
            {
                $type = wp_check_filetype($atts['src'], wp_get_mime_types());

                if(! in_array(strtolower($type['ext']), $default_types, true))
                {
                    return sprintf('<a class="wp-embedded-video" href="%s">%s</a>', esc_url($atts['src']), esc_html($atts['src']));
                }
            }

            if($is_vimeo)
            {
                wp_enqueue_script('mediaelement-vimeo');
            }

            $primary = true;
            array_unshift($default_types, 'src');
        }
        else
        {
            foreach($default_types as $ext)
            {
                if(! empty($atts[$ext]))
                {
                    $type = wp_check_filetype($atts[$ext], wp_get_mime_types());
                    if(strtolower($type['ext']) === $ext)
                    {
                        $primary = true;
                    }
                }
            }
        }

        if(! $primary)
        {
            $videos = get_attached_media('video', $post_id);
            if(empty($videos))
            {
                return;
            }

            $video = reset($videos);
            $atts['src'] = wp_get_attachment_url($video->ID);
            if(empty($atts['src']))
            {
                return;
            }

            array_unshift($default_types, 'src');
        }

        $library = apply_filters('wp_video_shortcode_library', 'mediaelement');
        if('mediaelement' === $library && did_action('init'))
        {
            wp_enqueue_style('wp-mediaelement');
            wp_enqueue_script('wp-mediaelement');
            wp_enqueue_script('mediaelement-vimeo');
        }

        /*
	 * MediaElement.js has issues with some URL formats for Vimeo and YouTube,
	 * so update the URL to prevent the ME.js player from breaking.
	 */
        if('mediaelement' === $library)
        {
            if($is_youtube)
            {
                // Remove `feature` query arg and force SSL - see #40866.
                $atts['src'] = remove_query_arg('feature', $atts['src']);
                $atts['src'] = set_url_scheme($atts['src'], 'https');
            }
            elseif($is_vimeo)
            {
                // Remove all query arguments and force SSL - see #40866.
                $parsed_vimeo_url = wp_parse_url($atts['src']);
                $vimeo_src = 'https://'.$parsed_vimeo_url['host'].$parsed_vimeo_url['path'];

                // Add loop param for mejs bug - see #40977, not needed after #39686.
                $loop = $atts['loop'] ? '1' : '0';
                $atts['src'] = add_query_arg('loop', $loop, $vimeo_src);
            }
        }

        $atts['class'] = apply_filters('wp_video_shortcode_class', $atts['class'], $atts);

        $html_atts = [
            'class' => $atts['class'],
            'id' => sprintf('video-%d-%d', $post_id, $instance),
            'width' => absint($atts['width']),
            'height' => absint($atts['height']),
            'poster' => esc_url($atts['poster']),
            'loop' => wp_validate_boolean($atts['loop']),
            'autoplay' => wp_validate_boolean($atts['autoplay']),
            'muted' => wp_validate_boolean($atts['muted']),
            'preload' => $atts['preload'],
        ];

        // These ones should just be omitted altogether if they are blank.
        foreach(['poster', 'loop', 'autoplay', 'preload', 'muted'] as $a)
        {
            if(empty($html_atts[$a]))
            {
                unset($html_atts[$a]);
            }
        }

        $attr_strings = [];
        foreach($html_atts as $k => $v)
        {
            $attr_strings[] = $k.'="'.esc_attr($v).'"';
        }

        $html = '';

        if('mediaelement' === $library && 1 === $instance)
        {
            $html .= "<!--[if lt IE 9]><script>document.createElement('video');</script><![endif]-->\n";
        }

        $html .= sprintf('<video %s controls="controls">', implode(' ', $attr_strings));

        $fileurl = '';
        $source = '<source type="%s" src="%s" />';

        foreach($default_types as $fallback)
        {
            if(! empty($atts[$fallback]))
            {
                if(empty($fileurl))
                {
                    $fileurl = $atts[$fallback];
                }
                if('src' === $fallback && $is_youtube)
                {
                    $type = ['type' => 'video/youtube'];
                }
                elseif('src' === $fallback && $is_vimeo)
                {
                    $type = ['type' => 'video/vimeo'];
                }
                else
                {
                    $type = wp_check_filetype($atts[$fallback], wp_get_mime_types());
                }
                $url = add_query_arg('_', $instance, $atts[$fallback]);
                $html .= sprintf($source, $type['type'], esc_url($url));
            }
        }

        if(! empty($content))
        {
            if(str_contains($content, "\n"))
            {
                $content = str_replace(["\r\n", "\n", "\t"], '', $content);
            }
            $html .= trim($content);
        }

        if('mediaelement' === $library)
        {
            $html .= wp_mediaelement_fallback($fileurl);
        }
        $html .= '</video>';

        $width_rule = '';
        if(! empty($atts['width']))
        {
            $width_rule = sprintf('width: %dpx;', $atts['width']);
        }
        $output = sprintf('<div style="%s" class="wp-video">%s</div>', $width_rule, $html);

        return apply_filters('wp_video_shortcode', $output, $atts, $video, $post_id, $library);
    }

    add_shortcode('video', 'wp_video_shortcode');

    function get_previous_image_link($size = 'thumbnail', $text = false)
    {
        return get_adjacent_image_link(true, $size, $text);
    }

    function previous_image_link($size = 'thumbnail', $text = false)
    {
        echo get_previous_image_link($size, $text);
    }

    function get_next_image_link($size = 'thumbnail', $text = false)
    {
        return get_adjacent_image_link(false, $size, $text);
    }

    function next_image_link($size = 'thumbnail', $text = false)
    {
        echo get_next_image_link($size, $text);
    }

    function get_adjacent_image_link($prev = true, $size = 'thumbnail', $text = false)
    {
        $post = get_post();
        $attachments = array_values(
            get_children([
                             'post_parent' => $post->post_parent,
                             'post_status' => 'inherit',
                             'post_type' => 'attachment',
                             'post_mime_type' => 'image',
                             'order' => 'ASC',
                             'orderby' => 'menu_order ID',
                         ])
        );

        foreach($attachments as $k => $attachment)
        {
            if((int) $attachment->ID === (int) $post->ID)
            {
                break;
            }
        }

        $output = '';
        $attachment_id = 0;

        if($attachments)
        {
            $k = $prev ? $k - 1 : $k + 1;

            if(isset($attachments[$k]))
            {
                $attachment_id = $attachments[$k]->ID;
                $attr = ['alt' => get_the_title($attachment_id)];
                $output = wp_get_attachment_link($attachment_id, $size, true, false, $text, $attr);
            }
        }

        $adjacent = $prev ? 'previous' : 'next';

        return apply_filters("{$adjacent}_image_link", $output, $attachment_id, $size, $text);
    }

    function adjacent_image_link($prev = true, $size = 'thumbnail', $text = false)
    {
        echo get_adjacent_image_link($prev, $size, $text);
    }

    function get_attachment_taxonomies($attachment, $output = 'names')
    {
        if(is_int($attachment))
        {
            $attachment = get_post($attachment);
        }
        elseif(is_array($attachment))
        {
            $attachment = (object) $attachment;
        }

        if(! is_object($attachment))
        {
            return [];
        }

        $file = get_attached_file($attachment->ID);
        $filename = wp_basename($file);

        $objects = ['attachment'];

        if(str_contains($filename, '.'))
        {
            $objects[] = 'attachment:'.substr($filename, strrpos($filename, '.') + 1);
        }

        if(! empty($attachment->post_mime_type))
        {
            $objects[] = 'attachment:'.$attachment->post_mime_type;

            if(str_contains($attachment->post_mime_type, '/'))
            {
                foreach(explode('/', $attachment->post_mime_type) as $token)
                {
                    if(! empty($token))
                    {
                        $objects[] = "attachment:$token";
                    }
                }
            }
        }

        $taxonomies = [];

        foreach($objects as $object)
        {
            $taxes = get_object_taxonomies($object, $output);

            if($taxes)
            {
                $taxonomies = array_merge($taxonomies, $taxes);
            }
        }

        if('names' === $output)
        {
            $taxonomies = array_unique($taxonomies);
        }

        return $taxonomies;
    }

    function get_taxonomies_for_attachments($output = 'names')
    {
        $taxonomies = [];

        foreach(get_taxonomies([], 'objects') as $taxonomy)
        {
            foreach($taxonomy->object_type as $object_type)
            {
                if('attachment' === $object_type || str_starts_with($object_type, 'attachment:'))
                {
                    if('names' === $output)
                    {
                        $taxonomies[] = $taxonomy->name;
                    }
                    else
                    {
                        $taxonomies[$taxonomy->name] = $taxonomy;
                    }
                    break;
                }
            }
        }

        return $taxonomies;
    }

    function is_gd_image($image)
    {
        if($image instanceof GdImage || is_resource($image) && 'gd' === get_resource_type($image))
        {
            return true;
        }

        return false;
    }

    function wp_imagecreatetruecolor($width, $height)
    {
        $img = imagecreatetruecolor($width, $height);

        if(is_gd_image($img) && function_exists('imagealphablending') && function_exists('imagesavealpha'))
        {
            imagealphablending($img, false);
            imagesavealpha($img, true);
        }

        return $img;
    }

    function wp_expand_dimensions($example_width, $example_height, $max_width, $max_height)
    {
        $example_width = (int) $example_width;
        $example_height = (int) $example_height;
        $max_width = (int) $max_width;
        $max_height = (int) $max_height;

        return wp_constrain_dimensions($example_width * 1000000, $example_height * 1000000, $max_width, $max_height);
    }

    function wp_max_upload_size()
    {
        $u_bytes = wp_convert_hr_to_bytes(ini_get('upload_max_filesize'));
        $p_bytes = wp_convert_hr_to_bytes(ini_get('post_max_size'));

        return apply_filters('upload_size_limit', min($u_bytes, $p_bytes), $u_bytes, $p_bytes);
    }

    function wp_get_image_editor($path, $args = [])
    {
        $args['path'] = $path;

        // If the mime type is not set in args, try to extract and set it from the file.
        if(! isset($args['mime_type']))
        {
            $file_info = wp_check_filetype($args['path']);

            /*
		 * If $file_info['type'] is false, then we let the editor attempt to
		 * figure out the file type, rather than forcing a failure based on extension.
		 */
            if(isset($file_info) && $file_info['type'])
            {
                $args['mime_type'] = $file_info['type'];
            }
        }

        // Check and set the output mime type mapped to the input type.
        if(isset($args['mime_type']))
        {
            $output_format = apply_filters('image_editor_output_format', [], $path, $args['mime_type']);
            if(isset($output_format[$args['mime_type']]))
            {
                $args['output_mime_type'] = $output_format[$args['mime_type']];
            }
        }

        $implementation = _wp_image_editor_choose($args);

        if($implementation)
        {
            $editor = new $implementation($path);
            $loaded = $editor->load();

            if(is_wp_error($loaded))
            {
                return $loaded;
            }

            return $editor;
        }

        return new WP_Error('image_no_editor', __('No editor could be selected.'));
    }

    function wp_image_editor_supports($args = [])
    {
        return (bool) _wp_image_editor_choose($args);
    }

    function _wp_image_editor_choose($args = [])
    {
        require_once ABSPATH.WPINC.'/class-wp-image-editor.php';
        require_once ABSPATH.WPINC.'/class-wp-image-editor-gd.php';
        require_once ABSPATH.WPINC.'/class-wp-image-editor-imagick.php';

        $implementations = apply_filters('wp_image_editors', ['WP_Image_Editor_Imagick', 'WP_Image_Editor_GD']);
        $supports_input = false;

        foreach($implementations as $implementation)
        {
            if(! call_user_func([$implementation, 'test'], $args))
            {
                continue;
            }

            // Implementation should support the passed mime type.
            if(
                isset($args['mime_type']) && ! call_user_func([
                                                                  $implementation,
                                                                  'supports_mime_type'
                                                              ], $args['mime_type'])
            )
            {
                continue;
            }

            // Implementation should support requested methods.
            if(isset($args['methods']) && array_diff($args['methods'], get_class_methods($implementation)))
            {
                continue;
            }

            // Implementation should ideally support the output mime type as well if set and different than the passed type.
            if(
                isset($args['mime_type']) && isset($args['output_mime_type']) && $args['mime_type'] !== $args['output_mime_type'] && ! call_user_func([
                                                                                                                                                          $implementation,
                                                                                                                                                          'supports_mime_type'
                                                                                                                                                      ], $args['output_mime_type'])
            )
            {
                /*
			 * This implementation supports the imput type but not the output type.
			 * Keep looking to see if we can find an implementation that supports both.
			 */
                $supports_input = $implementation;
                continue;
            }

            // Favor the implementation that supports both input and output mime types.
            return $implementation;
        }

        return $supports_input;
    }

    function wp_plupload_default_settings()
    {
        $wp_scripts = wp_scripts();

        $data = $wp_scripts->get_data('wp-plupload', 'data');
        if($data && str_contains($data, '_wpPluploadSettings'))
        {
            return;
        }

        $max_upload_size = wp_max_upload_size();
        $allowed_extensions = array_keys(get_allowed_mime_types());
        $extensions = [];
        foreach($allowed_extensions as $extension)
        {
            $extensions = array_merge($extensions, explode('|', $extension));
        }

        /*
	 * Since 4.9 the `runtimes` setting is hardcoded in our version of Plupload to `html5,html4`,
	 * and the `flash_swf_url` and `silverlight_xap_url` are not used.
	 */
        $defaults = [
            'file_data_name' => 'async-upload', // Key passed to $_FILE.
            'url' => admin_url('async-upload.php', 'relative'),
            'filters' => [
                'max_file_size' => $max_upload_size.'b',
                'mime_types' => [['extensions' => implode(',', $extensions)]],
            ],
        ];

        /*
	 * Currently only iOS Safari supports multiple files uploading,
	 * but iOS 7.x has a bug that prevents uploading of videos when enabled.
	 * See #29602.
	 */
        if(wp_is_mobile() && str_contains($_SERVER['HTTP_USER_AGENT'], 'OS 7_') && str_contains($_SERVER['HTTP_USER_AGENT'], 'like Mac OS X'))
        {
            $defaults['multi_selection'] = false;
        }

        // Check if WebP images can be edited.
        if(! wp_image_editor_supports(['mime_type' => 'image/webp']))
        {
            $defaults['webp_upload_error'] = true;
        }

        $defaults = apply_filters('plupload_default_settings', $defaults);

        $params = [
            'action' => 'upload-attachment',
        ];

        $params = apply_filters('plupload_default_params', $params);

        $params['_wpnonce'] = wp_create_nonce('media-form');

        $defaults['multipart_params'] = $params;

        $settings = [
            'defaults' => $defaults,
            'browser' => [
                'mobile' => wp_is_mobile(),
                'supported' => _device_can_upload(),
            ],
            'limitExceeded' => is_multisite() && ! is_upload_space_available(),
        ];

        $script = 'var _wpPluploadSettings = '.wp_json_encode($settings).';';

        if($data)
        {
            $script = "$data\n$script";
        }

        $wp_scripts->add_data('wp-plupload', 'data', $script);
    }

    function wp_prepare_attachment_for_js($attachment)
    {
        $attachment = get_post($attachment);

        if(! $attachment)
        {
            return;
        }

        if('attachment' !== $attachment->post_type)
        {
            return;
        }

        $meta = wp_get_attachment_metadata($attachment->ID);
        if(str_contains($attachment->post_mime_type, '/'))
        {
            [$type, $subtype] = explode('/', $attachment->post_mime_type);
        }
        else
        {
            [$type, $subtype] = [$attachment->post_mime_type, ''];
        }

        $attachment_url = wp_get_attachment_url($attachment->ID);
        $base_url = str_replace(wp_basename($attachment_url), '', $attachment_url);

        $response = [
            'id' => $attachment->ID,
            'title' => $attachment->post_title,
            'filename' => wp_basename(get_attached_file($attachment->ID)),
            'url' => $attachment_url,
            'link' => get_attachment_link($attachment->ID),
            'alt' => get_post_meta($attachment->ID, '_wp_attachment_image_alt', true),
            'author' => $attachment->post_author,
            'description' => $attachment->post_content,
            'caption' => $attachment->post_excerpt,
            'name' => $attachment->post_name,
            'status' => $attachment->post_status,
            'uploadedTo' => $attachment->post_parent,
            'date' => strtotime($attachment->post_date_gmt) * 1000,
            'modified' => strtotime($attachment->post_modified_gmt) * 1000,
            'menuOrder' => $attachment->menu_order,
            'mime' => $attachment->post_mime_type,
            'type' => $type,
            'subtype' => $subtype,
            'icon' => wp_mime_type_icon($attachment->ID),
            'dateFormatted' => mysql2date(__('F j, Y'), $attachment->post_date),
            'nonces' => [
                'update' => false,
                'delete' => false,
                'edit' => false,
            ],
            'editLink' => false,
            'meta' => false,
        ];

        $author = new WP_User($attachment->post_author);

        if($author->exists())
        {
            $author_name = $author->display_name ? $author->display_name : $author->nickname;
            $response['authorName'] = html_entity_decode($author_name, ENT_QUOTES, get_bloginfo('charset'));
            $response['authorLink'] = get_edit_user_link($author->ID);
        }
        else
        {
            $response['authorName'] = __('(no author)');
        }

        if($attachment->post_parent)
        {
            $post_parent = get_post($attachment->post_parent);
            if($post_parent)
            {
                $response['uploadedToTitle'] = $post_parent->post_title ? $post_parent->post_title : __('(no title)');
                $response['uploadedToLink'] = get_edit_post_link($attachment->post_parent, 'raw');
            }
        }

        $attached_file = get_attached_file($attachment->ID);

        if(isset($meta['filesize']))
        {
            $bytes = $meta['filesize'];
        }
        elseif(file_exists($attached_file))
        {
            $bytes = wp_filesize($attached_file);
        }
        else
        {
            $bytes = '';
        }

        if($bytes)
        {
            $response['filesizeInBytes'] = $bytes;
            $response['filesizeHumanReadable'] = size_format($bytes);
        }

        $context = get_post_meta($attachment->ID, '_wp_attachment_context', true);
        $response['context'] = ($context) ? $context : '';

        if(current_user_can('edit_post', $attachment->ID))
        {
            $response['nonces']['update'] = wp_create_nonce('update-post_'.$attachment->ID);
            $response['nonces']['edit'] = wp_create_nonce('image_editor-'.$attachment->ID);
            $response['editLink'] = get_edit_post_link($attachment->ID, 'raw');
        }

        if(current_user_can('delete_post', $attachment->ID))
        {
            $response['nonces']['delete'] = wp_create_nonce('delete-post_'.$attachment->ID);
        }

        if($meta && ('image' === $type || ! empty($meta['sizes'])))
        {
            $sizes = [];

            $possible_sizes = apply_filters('image_size_names_choose', [
                'thumbnail' => __('Thumbnail'),
                'medium' => __('Medium'),
                'large' => __('Large'),
                'full' => __('Full Size'),
            ]);
            unset($possible_sizes['full']);

            /*
		 * Loop through all potential sizes that may be chosen. Try to do this with some efficiency.
		 * First: run the image_downsize filter. If it returns something, we can use its data.
		 * If the filter does not return something, then image_downsize() is just an expensive way
		 * to check the image metadata, which we do second.
		 */
            foreach($possible_sizes as $size => $label)
            {
                $downsize = apply_filters('image_downsize', false, $attachment->ID, $size);

                if($downsize)
                {
                    if(empty($downsize[3]))
                    {
                        continue;
                    }

                    $sizes[$size] = [
                        'height' => $downsize[2],
                        'width' => $downsize[1],
                        'url' => $downsize[0],
                        'orientation' => $downsize[2] > $downsize[1] ? 'portrait' : 'landscape',
                    ];
                }
                elseif(isset($meta['sizes'][$size]))
                {
                    // Nothing from the filter, so consult image metadata if we have it.
                    $size_meta = $meta['sizes'][$size];

                    /*
				 * We have the actual image size, but might need to further constrain it if content_width is narrower.
				 * Thumbnail, medium, and full sizes are also checked against the site's height/width options.
				 */
                    [
                        $width,
                        $height
                    ] = image_constrain_size_for_editor($size_meta['width'], $size_meta['height'], $size, 'edit');

                    $sizes[$size] = [
                        'height' => $height,
                        'width' => $width,
                        'url' => $base_url.$size_meta['file'],
                        'orientation' => $height > $width ? 'portrait' : 'landscape',
                    ];
                }
            }

            if('image' === $type)
            {
                if(! empty($meta['original_image']))
                {
                    $response['originalImageURL'] = wp_get_original_image_url($attachment->ID);
                    $response['originalImageName'] = wp_basename(wp_get_original_image_path($attachment->ID));
                }

                $sizes['full'] = ['url' => $attachment_url];

                if(isset($meta['height'], $meta['width']))
                {
                    $sizes['full']['height'] = $meta['height'];
                    $sizes['full']['width'] = $meta['width'];
                    $sizes['full']['orientation'] = $meta['height'] > $meta['width'] ? 'portrait' : 'landscape';
                }

                $response = array_merge($response, $sizes['full']);
            }
            elseif($meta['sizes']['full']['file'])
            {
                $sizes['full'] = [
                    'url' => $base_url.$meta['sizes']['full']['file'],
                    'height' => $meta['sizes']['full']['height'],
                    'width' => $meta['sizes']['full']['width'],
                    'orientation' => $meta['sizes']['full']['height'] > $meta['sizes']['full']['width'] ? 'portrait' : 'landscape',
                ];
            }

            $response = array_merge($response, ['sizes' => $sizes]);
        }

        if($meta && 'video' === $type)
        {
            if(isset($meta['width']))
            {
                $response['width'] = (int) $meta['width'];
            }
            if(isset($meta['height']))
            {
                $response['height'] = (int) $meta['height'];
            }
        }

        if($meta && ('audio' === $type || 'video' === $type))
        {
            if(isset($meta['length_formatted']))
            {
                $response['fileLength'] = $meta['length_formatted'];
                $response['fileLengthHumanReadable'] = human_readable_duration($meta['length_formatted']);
            }

            $response['meta'] = [];
            foreach(wp_get_attachment_id3_keys($attachment, 'js') as $key => $label)
            {
                $response['meta'][$key] = false;

                if(! empty($meta[$key]))
                {
                    $response['meta'][$key] = $meta[$key];
                }
            }

            $id = get_post_thumbnail_id($attachment->ID);
            if(! empty($id))
            {
                [$src, $width, $height] = wp_get_attachment_image_src($id, 'full');
                $response['image'] = compact('src', 'width', 'height');
                [$src, $width, $height] = wp_get_attachment_image_src($id, 'thumbnail');
                $response['thumb'] = compact('src', 'width', 'height');
            }
            else
            {
                $src = wp_mime_type_icon($attachment->ID);
                $width = 48;
                $height = 64;
                $response['image'] = compact('src', 'width', 'height');
                $response['thumb'] = compact('src', 'width', 'height');
            }
        }

        if(function_exists('get_compat_media_markup'))
        {
            $response['compat'] = get_compat_media_markup($attachment->ID, ['in_modal' => true]);
        }

        if(function_exists('get_media_states'))
        {
            $media_states = get_media_states($attachment);
            if(! empty($media_states))
            {
                $response['mediaStates'] = implode(', ', $media_states);
            }
        }

        return apply_filters('wp_prepare_attachment_for_js', $response, $attachment, $meta);
    }

    function wp_enqueue_media($args = [])
    {
        // Enqueue me just once per page, please.
        if(did_action('wp_enqueue_media'))
        {
            return;
        }

        global $content_width, $wpdb, $wp_locale;

        $defaults = [
            'post' => null,
        ];
        $args = wp_parse_args($args, $defaults);

        /*
	 * We're going to pass the old thickbox media tabs to `media_upload_tabs`
	 * to ensure plugins will work. We will then unset those tabs.
	 */
        $tabs = [
            // handler action suffix => tab label
            'type' => '',
            'type_url' => '',
            'gallery' => '',
            'library' => '',
        ];

        $tabs = apply_filters('media_upload_tabs', $tabs);
        unset($tabs['type'], $tabs['type_url'], $tabs['gallery'], $tabs['library']);

        $props = [
            'link' => get_option('image_default_link_type'), // DB default is 'file'.
            'align' => get_option('image_default_align'),     // Empty default.
            'size' => get_option('image_default_size'),      // Empty default.
        ];

        $exts = array_merge(wp_get_audio_extensions(), wp_get_video_extensions());
        $mimes = get_allowed_mime_types();
        $ext_mimes = [];
        foreach($exts as $ext)
        {
            foreach($mimes as $ext_preg => $mime_match)
            {
                if(preg_match('#'.$ext.'#i', $ext_preg))
                {
                    $ext_mimes[$ext] = $mime_match;
                    break;
                }
            }
        }

        $show_audio_playlist = apply_filters('media_library_show_audio_playlist', true);
        if(null === $show_audio_playlist)
        {
            $show_audio_playlist = $wpdb->get_var(
                "SELECT ID
			FROM $wpdb->posts
			WHERE post_type = 'attachment'
			AND post_mime_type LIKE 'audio%'
			LIMIT 1"
            );
        }

        $show_video_playlist = apply_filters('media_library_show_video_playlist', true);
        if(null === $show_video_playlist)
        {
            $show_video_playlist = $wpdb->get_var(
                "SELECT ID
			FROM $wpdb->posts
			WHERE post_type = 'attachment'
			AND post_mime_type LIKE 'video%'
			LIMIT 1"
            );
        }

        $months = apply_filters('media_library_months_with_files', null);
        if(! is_array($months))
        {
            $months = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
				FROM $wpdb->posts
				WHERE post_type = %s
				ORDER BY post_date DESC", 'attachment'
                )
            );
        }
        foreach($months as $month_year)
        {
            $month_year->text = sprintf(/* translators: 1: Month, 2: Year. */ __('%1$s %2$d'), $wp_locale->get_month($month_year->month), $month_year->year);
        }

        $infinite_scrolling = apply_filters('media_library_infinite_scrolling', false);

        $settings = [
            'tabs' => $tabs,
            'tabUrl' => add_query_arg(['chromeless' => true], admin_url('media-upload.php')),
            'mimeTypes' => wp_list_pluck(get_post_mime_types(), 0),

            'captions' => ! apply_filters('disable_captions', ''),
            'nonce' => [
                'sendToEditor' => wp_create_nonce('media-send-to-editor'),
                'setAttachmentThumbnail' => wp_create_nonce('set-attachment-thumbnail'),
            ],
            'post' => [
                'id' => 0,
            ],
            'defaultProps' => $props,
            'attachmentCounts' => [
                'audio' => ($show_audio_playlist) ? 1 : 0,
                'video' => ($show_video_playlist) ? 1 : 0,
            ],
            'oEmbedProxyUrl' => rest_url('oembed/1.0/proxy'),
            'embedExts' => $exts,
            'embedMimes' => $ext_mimes,
            'contentWidth' => $content_width,
            'months' => $months,
            'mediaTrash' => MEDIA_TRASH ? 1 : 0,
            'infiniteScrolling' => ($infinite_scrolling) ? 1 : 0,
        ];

        $post = null;
        if(isset($args['post']))
        {
            $post = get_post($args['post']);
            $settings['post'] = [
                'id' => $post->ID,
                'nonce' => wp_create_nonce('update-post_'.$post->ID),
            ];

            $thumbnail_support = current_theme_supports('post-thumbnails', $post->post_type) && post_type_supports($post->post_type, 'thumbnail');
            if(! $thumbnail_support && 'attachment' === $post->post_type && $post->post_mime_type)
            {
                if(wp_attachment_is('audio', $post))
                {
                    $thumbnail_support = post_type_supports('attachment:audio', 'thumbnail') || current_theme_supports('post-thumbnails', 'attachment:audio');
                }
                elseif(wp_attachment_is('video', $post))
                {
                    $thumbnail_support = post_type_supports('attachment:video', 'thumbnail') || current_theme_supports('post-thumbnails', 'attachment:video');
                }
            }

            if($thumbnail_support)
            {
                $featured_image_id = get_post_meta($post->ID, '_thumbnail_id', true);
                $settings['post']['featuredImageId'] = $featured_image_id ? $featured_image_id : -1;
            }
        }

        if($post)
        {
            $post_type_object = get_post_type_object($post->post_type);
        }
        else
        {
            $post_type_object = get_post_type_object('post');
        }

        $strings = [
            // Generic.
            'mediaFrameDefaultTitle' => __('Media'),
            'url' => __('URL'),
            'addMedia' => __('Add media'),
            'search' => __('Search'),
            'select' => __('Select'),
            'cancel' => __('Cancel'),
            'update' => __('Update'),
            'replace' => __('Replace'),
            'remove' => __('Remove'),
            'back' => __('Back'),
            /*
		 * translators: This is a would-be plural string used in the media manager.
		 * If there is not a word you can use in your language to avoid issues with the
		 * lack of plural support here, turn it into "selected: %d" then translate it.
		 */
            'selected' => __('%d selected'),
            'dragInfo' => __('Drag and drop to reorder media files.'),

            // Upload.
            'uploadFilesTitle' => __('Upload files'),
            'uploadImagesTitle' => __('Upload images'),

            // Library.
            'mediaLibraryTitle' => __('Media Library'),
            'insertMediaTitle' => __('Add media'),
            'createNewGallery' => __('Create a new gallery'),
            'createNewPlaylist' => __('Create a new playlist'),
            'createNewVideoPlaylist' => __('Create a new video playlist'),
            'returnToLibrary' => __('&#8592; Go to library'),
            'allMediaItems' => __('All media items'),
            'allDates' => __('All dates'),
            'noItemsFound' => __('No items found.'),
            'insertIntoPost' => $post_type_object->labels->insert_into_item,
            'unattached' => _x('Unattached', 'media items'),
            'mine' => _x('Mine', 'media items'),
            'trash' => _x('Trash', 'noun'),
            'uploadedToThisPost' => $post_type_object->labels->uploaded_to_this_item,
            'warnDelete' => __("You are about to permanently delete this item from your site.\nThis action cannot be undone.\n 'Cancel' to stop, 'OK' to delete."),
            'warnBulkDelete' => __("You are about to permanently delete these items from your site.\nThis action cannot be undone.\n 'Cancel' to stop, 'OK' to delete."),
            'warnBulkTrash' => __("You are about to trash these items.\n  'Cancel' to stop, 'OK' to delete."),
            'bulkSelect' => __('Bulk select'),
            'trashSelected' => __('Move to Trash'),
            'restoreSelected' => __('Restore from Trash'),
            'deletePermanently' => __('Delete permanently'),
            'errorDeleting' => __('Error in deleting the attachment.'),
            'apply' => __('Apply'),
            'filterByDate' => __('Filter by date'),
            'filterByType' => __('Filter by type'),
            'searchLabel' => __('Search'),
            'searchMediaLabel' => __('Search media'),
            // Backward compatibility pre-5.3.
            'searchMediaPlaceholder' => __('Search media items...'),
            // Placeholder (no ellipsis), backward compatibility pre-5.3.
            /* translators: %d: Number of attachments found in a search. */
            'mediaFound' => __('Number of media items found: %d'),
            'noMedia' => __('No media items found.'),
            'noMediaTryNewSearch' => __('No media items found. Try a different search.'),

            // Library Details.
            'attachmentDetails' => __('Attachment details'),

            // From URL.
            'insertFromUrlTitle' => __('Insert from URL'),

            // Featured Images.
            'setFeaturedImageTitle' => $post_type_object->labels->featured_image,
            'setFeaturedImage' => $post_type_object->labels->set_featured_image,

            // Gallery.
            'createGalleryTitle' => __('Create gallery'),
            'editGalleryTitle' => __('Edit gallery'),
            'cancelGalleryTitle' => __('&#8592; Cancel gallery'),
            'insertGallery' => __('Insert gallery'),
            'updateGallery' => __('Update gallery'),
            'addToGallery' => __('Add to gallery'),
            'addToGalleryTitle' => __('Add to gallery'),
            'reverseOrder' => __('Reverse order'),

            // Edit Image.
            'imageDetailsTitle' => __('Image details'),
            'imageReplaceTitle' => __('Replace image'),
            'imageDetailsCancel' => __('Cancel edit'),
            'editImage' => __('Edit image'),

            // Crop Image.
            'chooseImage' => __('Choose image'),
            'selectAndCrop' => __('Select and crop'),
            'skipCropping' => __('Skip cropping'),
            'cropImage' => __('Crop image'),
            'cropYourImage' => __('Crop your image'),
            'cropping' => __('Cropping&hellip;'),
            /* translators: 1: Suggested width number, 2: Suggested height number. */
            'suggestedDimensions' => __('Suggested image dimensions: %1$s by %2$s pixels.'),
            'cropError' => __('There has been an error cropping your image.'),

            // Edit Audio.
            'audioDetailsTitle' => __('Audio details'),
            'audioReplaceTitle' => __('Replace audio'),
            'audioAddSourceTitle' => __('Add audio source'),
            'audioDetailsCancel' => __('Cancel edit'),

            // Edit Video.
            'videoDetailsTitle' => __('Video details'),
            'videoReplaceTitle' => __('Replace video'),
            'videoAddSourceTitle' => __('Add video source'),
            'videoDetailsCancel' => __('Cancel edit'),
            'videoSelectPosterImageTitle' => __('Select poster image'),
            'videoAddTrackTitle' => __('Add subtitles'),

            // Playlist.
            'playlistDragInfo' => __('Drag and drop to reorder tracks.'),
            'createPlaylistTitle' => __('Create audio playlist'),
            'editPlaylistTitle' => __('Edit audio playlist'),
            'cancelPlaylistTitle' => __('&#8592; Cancel audio playlist'),
            'insertPlaylist' => __('Insert audio playlist'),
            'updatePlaylist' => __('Update audio playlist'),
            'addToPlaylist' => __('Add to audio playlist'),
            'addToPlaylistTitle' => __('Add to Audio Playlist'),

            // Video Playlist.
            'videoPlaylistDragInfo' => __('Drag and drop to reorder videos.'),
            'createVideoPlaylistTitle' => __('Create video playlist'),
            'editVideoPlaylistTitle' => __('Edit video playlist'),
            'cancelVideoPlaylistTitle' => __('&#8592; Cancel video playlist'),
            'insertVideoPlaylist' => __('Insert video playlist'),
            'updateVideoPlaylist' => __('Update video playlist'),
            'addToVideoPlaylist' => __('Add to video playlist'),
            'addToVideoPlaylistTitle' => __('Add to video Playlist'),

            // Headings.
            'filterAttachments' => __('Filter media'),
            'attachmentsList' => __('Media list'),
        ];

        $settings = apply_filters('media_view_settings', $settings, $post);

        $strings = apply_filters('media_view_strings', $strings, $post);

        $strings['settings'] = $settings;

        /*
	 * Ensure we enqueue media-editor first, that way media-views
	 * is registered internally before we try to localize it. See #24724.
	 */
        wp_enqueue_script('media-editor');
        wp_localize_script('media-views', '_wpMediaViewsL10n', $strings);

        wp_enqueue_script('media-audiovideo');
        wp_enqueue_style('media-views');
        if(is_admin())
        {
            wp_enqueue_script('mce-view');
            wp_enqueue_script('image-edit');
        }
        wp_enqueue_style('imgareaselect');
        wp_plupload_default_settings();

        require_once ABSPATH.WPINC.'/media-template.php';
        add_action('admin_footer', 'wp_print_media_templates');
        add_action('wp_footer', 'wp_print_media_templates');
        add_action('customize_controls_print_footer_scripts', 'wp_print_media_templates');

        do_action('wp_enqueue_media');
    }

    function get_attached_media($type, $post = 0)
    {
        $post = get_post($post);

        if(! $post)
        {
            return [];
        }

        $args = [
            'post_parent' => $post->ID,
            'post_type' => 'attachment',
            'post_mime_type' => $type,
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ];

        $args = apply_filters('get_attached_media_args', $args, $type, $post);

        $children = get_children($args);

        return (array) apply_filters('get_attached_media', $children, $type, $post);
    }

    function get_media_embedded_in_content($content, $types = null)
    {
        $html = [];

        $allowed_media_types = apply_filters('media_embedded_in_content_allowed_types', [
            'audio',
            'video',
            'object',
            'embed',
            'iframe'
        ]);

        if(! empty($types))
        {
            if(! is_array($types))
            {
                $types = [$types];
            }

            $allowed_media_types = array_intersect($allowed_media_types, $types);
        }

        $tags = implode('|', $allowed_media_types);

        if(preg_match_all('#<(?P<tag>'.$tags.')[^<]*?(?:>[\s\S]*?<\/(?P=tag)>|\s*\/>)#', $content, $matches))
        {
            foreach($matches[0] as $match)
            {
                $html[] = $match;
            }
        }

        return $html;
    }

    function get_post_galleries($post, $html = true)
    {
        $post = get_post($post);

        if(! $post)
        {
            return [];
        }

        if(! has_shortcode($post->post_content, 'gallery') && ! has_block('gallery', $post->post_content))
        {
            return [];
        }

        $galleries = [];
        if(preg_match_all('/'.get_shortcode_regex().'/s', $post->post_content, $matches, PREG_SET_ORDER))
        {
            foreach($matches as $shortcode)
            {
                if('gallery' === $shortcode[2])
                {
                    $srcs = [];

                    $shortcode_attrs = shortcode_parse_atts($shortcode[3]);
                    if(! is_array($shortcode_attrs))
                    {
                        $shortcode_attrs = [];
                    }

                    // Specify the post ID of the gallery we're viewing if the shortcode doesn't reference another post already.
                    if(! isset($shortcode_attrs['id']))
                    {
                        $shortcode[3] .= ' id="'.(int) $post->ID.'"';
                    }

                    $gallery = do_shortcode_tag($shortcode);
                    if($html)
                    {
                        $galleries[] = $gallery;
                    }
                    else
                    {
                        preg_match_all('#src=([\'"])(.+?)\1#is', $gallery, $src, PREG_SET_ORDER);
                        if(! empty($src))
                        {
                            foreach($src as $s)
                            {
                                $srcs[] = $s[2];
                            }
                        }

                        $galleries[] = array_merge($shortcode_attrs, [
                            'src' => array_values(array_unique($srcs)),
                        ]);
                    }
                }
            }
        }

        if(has_block('gallery', $post->post_content))
        {
            $post_blocks = parse_blocks($post->post_content);

            while($block = array_shift($post_blocks))
            {
                $has_inner_blocks = ! empty($block['innerBlocks']);

                // Skip blocks with no blockName and no innerHTML.
                if(! $block['blockName'])
                {
                    continue;
                }

                // Skip non-Gallery blocks.
                if('core/gallery' !== $block['blockName'])
                {
                    // Move inner blocks into the root array before skipping.
                    if($has_inner_blocks)
                    {
                        array_push($post_blocks, ...$block['innerBlocks']);
                    }
                    continue;
                }

                // New Gallery block format as HTML.
                if($has_inner_blocks && $html)
                {
                    $block_html = wp_list_pluck($block['innerBlocks'], 'innerHTML');
                    $galleries[] = '<figure>'.implode(' ', $block_html).'</figure>';
                    continue;
                }

                $srcs = [];

                // New Gallery block format as an array.
                if($has_inner_blocks)
                {
                    $attrs = wp_list_pluck($block['innerBlocks'], 'attrs');
                    $ids = wp_list_pluck($attrs, 'id');

                    foreach($ids as $id)
                    {
                        $url = wp_get_attachment_url($id);

                        if(is_string($url) && ! in_array($url, $srcs, true))
                        {
                            $srcs[] = $url;
                        }
                    }

                    $galleries[] = [
                        'ids' => implode(',', $ids),
                        'src' => $srcs,
                    ];

                    continue;
                }

                // Old Gallery block format as HTML.
                if($html)
                {
                    $galleries[] = $block['innerHTML'];
                    continue;
                }

                // Old Gallery block format as an array.
                $ids = ! empty($block['attrs']['ids']) ? $block['attrs']['ids'] : [];

                // If present, use the image IDs from the JSON blob as canonical.
                if(! empty($ids))
                {
                    foreach($ids as $id)
                    {
                        $url = wp_get_attachment_url($id);

                        if(is_string($url) && ! in_array($url, $srcs, true))
                        {
                            $srcs[] = $url;
                        }
                    }

                    $galleries[] = [
                        'ids' => implode(',', $ids),
                        'src' => $srcs,
                    ];

                    continue;
                }

                // Otherwise, extract srcs from the innerHTML.
                preg_match_all('#src=([\'"])(.+?)\1#is', $block['innerHTML'], $found_srcs, PREG_SET_ORDER);

                if(! empty($found_srcs[0]))
                {
                    foreach($found_srcs as $src)
                    {
                        if(isset($src[2]) && ! in_array($src[2], $srcs, true))
                        {
                            $srcs[] = $src[2];
                        }
                    }
                }

                $galleries[] = ['src' => $srcs];
            }
        }

        return apply_filters('get_post_galleries', $galleries, $post);
    }

    function get_post_gallery($post = 0, $html = true)
    {
        $galleries = get_post_galleries($post, $html);
        $gallery = reset($galleries);

        return apply_filters('get_post_gallery', $gallery, $post, $galleries);
    }

    function get_post_galleries_images($post = 0)
    {
        $galleries = get_post_galleries($post, false);

        return wp_list_pluck($galleries, 'src');
    }

    function get_post_gallery_images($post = 0)
    {
        $gallery = get_post_gallery($post, false);

        return empty($gallery['src']) ? [] : $gallery['src'];
    }

    function wp_maybe_generate_attachment_metadata($attachment)
    {
        if(empty($attachment) || empty($attachment->ID))
        {
            return;
        }

        $attachment_id = (int) $attachment->ID;
        $file = get_attached_file($attachment_id);
        $meta = wp_get_attachment_metadata($attachment_id);

        if(empty($meta) && file_exists($file))
        {
            $_meta = get_post_meta($attachment_id);
            $_lock = 'wp_generating_att_'.$attachment_id;

            if(! array_key_exists('_wp_attachment_metadata', $_meta) && ! get_transient($_lock))
            {
                set_transient($_lock, $file);
                wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $file));
                delete_transient($_lock);
            }
        }
    }

    function attachment_url_to_postid($url)
    {
        global $wpdb;

        $dir = wp_get_upload_dir();
        $path = $url;

        $site_url = parse_url($dir['url']);
        $image_path = parse_url($path);

        // Force the protocols to match if needed.
        if(isset($image_path['scheme']) && ($image_path['scheme'] !== $site_url['scheme']))
        {
            $path = str_replace($image_path['scheme'], $site_url['scheme'], $path);
        }

        if(str_starts_with($path, $dir['baseurl'].'/'))
        {
            $path = substr($path, strlen($dir['baseurl'].'/'));
        }

        $sql = $wpdb->prepare("SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value = %s", $path);

        $results = $wpdb->get_results($sql);
        $post_id = null;

        if($results)
        {
            // Use the first available result, but prefer a case-sensitive match, if exists.
            $post_id = reset($results)->post_id;

            if(count($results) > 1)
            {
                foreach($results as $result)
                {
                    if($path === $result->meta_value)
                    {
                        $post_id = $result->post_id;
                        break;
                    }
                }
            }
        }

        return (int) apply_filters('attachment_url_to_postid', $post_id, $url);
    }

    function wpview_media_sandbox_styles()
    {
        $version = 'ver='.get_bloginfo('version');
        $mediaelement = includes_url("js/mediaelement/mediaelementplayer-legacy.min.css?$version");
        $wpmediaelement = includes_url("js/mediaelement/wp-mediaelement.css?$version");

        return [$mediaelement, $wpmediaelement];
    }

    function wp_register_media_personal_data_exporter($exporters)
    {
        $exporters['wordpress-media'] = [
            'exporter_friendly_name' => __('WordPress Media'),
            'callback' => 'wp_media_personal_data_exporter',
        ];

        return $exporters;
    }

    function wp_media_personal_data_exporter($email_address, $page = 1)
    {
        // Limit us to 50 attachments at a time to avoid timing out.
        $number = 50;
        $page = (int) $page;

        $data_to_export = [];

        $user = get_user_by('email', $email_address);
        if(false === $user)
        {
            return [
                'data' => $data_to_export,
                'done' => true,
            ];
        }

        $post_query = new WP_Query([
                                       'author' => $user->ID,
                                       'posts_per_page' => $number,
                                       'paged' => $page,
                                       'post_type' => 'attachment',
                                       'post_status' => 'any',
                                       'orderby' => 'ID',
                                       'order' => 'ASC',
                                   ]);

        foreach((array) $post_query->posts as $post)
        {
            $attachment_url = wp_get_attachment_url($post->ID);

            if($attachment_url)
            {
                $post_data_to_export = [
                    [
                        'name' => __('URL'),
                        'value' => $attachment_url,
                    ],
                ];

                $data_to_export[] = [
                    'group_id' => 'media',
                    'group_label' => __('Media'),
                    'group_description' => __('User&#8217;s media data.'),
                    'item_id' => "post-{$post->ID}",
                    'data' => $post_data_to_export,
                ];
            }
        }

        $done = $post_query->max_num_pages <= $page;

        return [
            'data' => $data_to_export,
            'done' => $done,
        ];
    }

    function _wp_add_additional_image_sizes()
    {
        // 2x medium_large size.
        add_image_size('1536x1536', 1536, 1536);
        // 2x large size.
        add_image_size('2048x2048', 2048, 2048);
    }

    function wp_show_heic_upload_error($plupload_settings)
    {
        $plupload_settings['heic_upload_error'] = true;

        return $plupload_settings;
    }

    function wp_getimagesize($filename, array &$image_info = null)
    {
        // Don't silence errors when in debug mode, unless running unit tests.
        if(defined('WP_DEBUG') && WP_DEBUG && ! defined('WP_RUN_CORE_TESTS'))
        {
            if(2 === func_num_args())
            {
                $info = getimagesize($filename, $image_info);
            }
            else
            {
                $info = getimagesize($filename);
            }
        }
        else
        {
            /*
		 * Silencing notice and warning is intentional.
		 *
		 * getimagesize() has a tendency to generate errors, such as
		 * "corrupt JPEG data: 7191 extraneous bytes before marker",
		 * even when it's able to provide image size information.
		 *
		 * See https://core.trac.wordpress.org/ticket/42480
		 */
            if(2 === func_num_args())
            {
                // phpcs:ignore WordPress.PHP.NoSilencedErrors
                $info = @getimagesize($filename, $image_info);
            }
            else
            {
                // phpcs:ignore WordPress.PHP.NoSilencedErrors
                $info = @getimagesize($filename);
            }
        }

        if(false !== $info)
        {
            return $info;
        }

        /*
	 * For PHP versions that don't support WebP images,
	 * extract the image size info from the file headers.
	 */
        if('image/webp' === wp_get_image_mime($filename))
        {
            $webp_info = wp_get_webp_info($filename);
            $width = $webp_info['width'];
            $height = $webp_info['height'];

            // Mimic the native return format.
            if($width && $height)
            {
                return [
                    $width,
                    $height,
                    IMAGETYPE_WEBP,
                    sprintf('width="%d" height="%d"', $width, $height),
                    'mime' => 'image/webp',
                ];
            }
        }

        // The image could not be parsed.
        return false;
    }

    function wp_get_webp_info($filename)
    {
        $width = false;
        $height = false;
        $type = false;

        if('image/webp' !== wp_get_image_mime($filename))
        {
            return compact('width', 'height', 'type');
        }

        $magic = file_get_contents($filename, false, null, 0, 40);

        if(false === $magic)
        {
            return compact('width', 'height', 'type');
        }

        // Make sure we got enough bytes.
        if(strlen($magic) < 40)
        {
            return compact('width', 'height', 'type');
        }

        /*
	 * The headers are a little different for each of the three formats.
	 * Header values based on WebP docs, see https://developers.google.com/speed/webp/docs/riff_container.
	 */
        switch(substr($magic, 12, 4))
        {
            // Lossy WebP.
            case 'VP8 ':
                $parts = unpack('v2', substr($magic, 26, 4));
                $width = (int) ($parts[1] & 0x3FFF);
                $height = (int) ($parts[2] & 0x3FFF);
                $type = 'lossy';
                break;
            // Lossless WebP.
            case 'VP8L':
                $parts = unpack('C4', substr($magic, 21, 4));
                $width = (int) ($parts[1] | (($parts[2] & 0x3F) << 8)) + 1;
                $height = (int) ((($parts[2] & 0xC0) >> 6) | ($parts[3] << 2) | (($parts[4] & 0x03) << 10)) + 1;
                $type = 'lossless';
                break;
            // Animated/alpha WebP.
            case 'VP8X':
                // Pad 24-bit int.
                $width = unpack('V', substr($magic, 24, 3)."\x00");
                $width = (int) ($width[1] & 0xFFFFFF) + 1;
                // Pad 24-bit int.
                $height = unpack('V', substr($magic, 27, 3)."\x00");
                $height = (int) ($height[1] & 0xFFFFFF) + 1;
                $type = 'animated-alpha';
                break;
        }

        return compact('width', 'height', 'type');
    }

    function wp_get_loading_optimization_attributes($tag_name, $attr, $context)
    {
        global $wp_query;

        $loading_attrs = [];

        /*
	 * Skip lazy-loading for the overall block template, as it is handled more granularly.
	 * The skip is also applicable for `fetchpriority`.
	 */
        if('template' === $context)
        {
            return $loading_attrs;
        }

        // For now this function only supports images and iframes.
        if('img' !== $tag_name && 'iframe' !== $tag_name)
        {
            return $loading_attrs;
        }

        // For any resources, width and height must be provided, to avoid layout shifts.
        if(! isset($attr['width'], $attr['height']))
        {
            return $loading_attrs;
        }

        /*
	 * Skip programmatically created images within post content as they need to be handled together with the other
	 * images within the post content.
	 * Without this clause, they would already be considered within their own context which skews the image count and
	 * can result in the first post content image being lazy-loaded or an image further down the page being marked as a
	 * high priority.
	 */
        switch($context)
        {
            case 'the_post_thumbnail':
            case 'wp_get_attachment_image':
            case 'widget_media_image':
                if(doing_filter('the_content'))
                {
                    return $loading_attrs;
                }
        }

        /*
	 * The key function logic starts here.
	 */
        $maybe_in_viewport = null;
        $increase_count = false;
        $maybe_increase_count = false;

        // Logic to handle a `loading` attribute that is already provided.
        if(isset($attr['loading']))
        {
            /*
		 * Interpret "lazy" as not in viewport. Any other value can be
		 * interpreted as in viewport (realistically only "eager" or `false`
		 * to force-omit the attribute are other potential values).
		 */
            if('lazy' === $attr['loading'])
            {
                $maybe_in_viewport = false;
            }
            else
            {
                $maybe_in_viewport = true;
            }
        }

        // Logic to handle a `fetchpriority` attribute that is already provided.
        if(isset($attr['fetchpriority']) && 'high' === $attr['fetchpriority'])
        {
            /*
		 * If the image was already determined to not be in the viewport (e.g.
		 * from an already provided `loading` attribute), trigger a warning.
		 * Otherwise, the value can be interpreted as in viewport, since only
		 * the most important in-viewport image should have `fetchpriority` set
		 * to "high".
		 */
            if(false === $maybe_in_viewport)
            {
                _doing_it_wrong(__FUNCTION__, __('An image should not be lazy-loaded and marked as high priority at the same time.'), '6.3.0');
                /*
			 * Set `fetchpriority` here for backward-compatibility as we should
			 * not override what a developer decided, even though it seems
			 * incorrect.
			 */
                $loading_attrs['fetchpriority'] = 'high';
            }
            else
            {
                $maybe_in_viewport = true;
            }
        }

        if(null === $maybe_in_viewport)
        {
            switch($context)
            {
                // Consider elements with these header-specific contexts to be in viewport.
                case 'template_part_'.WP_TEMPLATE_PART_AREA_HEADER:
                case 'get_header_image_tag':
                    $maybe_in_viewport = true;
                    $maybe_increase_count = true;
                    break;
                // Count main content elements and detect whether in viewport.
                case 'the_content':
                case 'the_post_thumbnail':
                case 'do_shortcode':
                    // Only elements within the main query loop have special handling.
                    if(! is_admin() && in_the_loop() && is_main_query())
                    {
                        /*
					 * Get the content media count, since this is a main query
					 * content element. This is accomplished by "increasing"
					 * the count by zero, as the only way to get the count is
					 * to call this function.
					 * The actual count increase happens further below, based
					 * on the `$increase_count` flag set here.
					 */
                        $content_media_count = wp_increase_content_media_count(0);
                        $increase_count = true;

                        // If the count so far is below the threshold, `loading` attribute is omitted.
                        if($content_media_count < wp_omit_loading_attr_threshold())
                        {
                            $maybe_in_viewport = true;
                        }
                        else
                        {
                            $maybe_in_viewport = false;
                        }
                    }
                    /*
				 * For the 'the_post_thumbnail' context, the following case
				 * clause needs to be considered as well, therefore skip the
				 * break statement here if the viewport has not been
				 * determined.
				 */
                    if('the_post_thumbnail' !== $context || null !== $maybe_in_viewport)
                    {
                        break;
                    }
                // phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect
                // Consider elements before the loop as being in viewport.
                case 'wp_get_attachment_image':
                case 'widget_media_image':
                    if(// Only apply for main query but before the loop.
                        $wp_query->before_loop && $wp_query->is_main_query() /*
					 * Any image before the loop, but after the header has started should not be lazy-loaded,
					 * except when the footer has already started which can happen when the current template
					 * does not include any loop.
					 */ && did_action('get_header') && ! did_action('get_footer')
                    )
                    {
                        $maybe_in_viewport = true;
                        $maybe_increase_count = true;
                    }
                    break;
            }
        }

        /*
	 * If the element is in the viewport (`true`), potentially add
	 * `fetchpriority` with a value of "high". Otherwise, i.e. if the element
	 * is not not in the viewport (`false`) or it is unknown (`null`), add
	 * `loading` with a value of "lazy".
	 */
        if($maybe_in_viewport)
        {
            $loading_attrs = wp_maybe_add_fetchpriority_high_attr($loading_attrs, $tag_name, $attr);
        }
        else
        {
            // Only add `loading="lazy"` if the feature is enabled.
            if(wp_lazy_loading_enabled($tag_name, $context))
            {
                $loading_attrs['loading'] = 'lazy';
            }
        }

        /*
	 * If flag was set based on contextual logic above, increase the content
	 * media count, either unconditionally, or based on whether the image size
	 * is larger than the threshold.
	 */
        if($increase_count)
        {
            wp_increase_content_media_count();
        }
        elseif($maybe_increase_count)
        {
            $wp_min_priority_img_pixels = apply_filters('wp_min_priority_img_pixels', 50000);

            if($wp_min_priority_img_pixels <= $attr['width'] * $attr['height'])
            {
                wp_increase_content_media_count();
            }
        }

        return $loading_attrs;
    }

    function wp_omit_loading_attr_threshold($force = false)
    {
        static $omit_threshold;

        // This function may be called multiple times. Run the filter only once per page load.
        if(! isset($omit_threshold) || $force)
        {
            $omit_threshold = apply_filters('wp_omit_loading_attr_threshold', 3);
        }

        return $omit_threshold;
    }

    function wp_increase_content_media_count($amount = 1)
    {
        static $content_media_count = 0;

        $content_media_count += $amount;

        return $content_media_count;
    }

    function wp_maybe_add_fetchpriority_high_attr($loading_attrs, $tag_name, $attr)
    {
        // For now, adding `fetchpriority="high"` is only supported for images.
        if('img' !== $tag_name)
        {
            return $loading_attrs;
        }

        if(isset($attr['fetchpriority']))
        {
            /*
		 * While any `fetchpriority` value could be set in `$loading_attrs`,
		 * for consistency we only do it for `fetchpriority="high"` since that
		 * is the only possible value that WordPress core would apply on its
		 * own.
		 */
            if('high' === $attr['fetchpriority'])
            {
                $loading_attrs['fetchpriority'] = 'high';
                wp_high_priority_element_flag(false);
            }

            return $loading_attrs;
        }

        // Lazy-loading and `fetchpriority="high"` are mutually exclusive.
        if(isset($loading_attrs['loading']) && 'lazy' === $loading_attrs['loading'])
        {
            return $loading_attrs;
        }

        if(! wp_high_priority_element_flag())
        {
            return $loading_attrs;
        }

        $wp_min_priority_img_pixels = apply_filters('wp_min_priority_img_pixels', 50000);

        if($wp_min_priority_img_pixels <= $attr['width'] * $attr['height'])
        {
            $loading_attrs['fetchpriority'] = 'high';
            wp_high_priority_element_flag(false);
        }

        return $loading_attrs;
    }

    function wp_high_priority_element_flag($value = null)
    {
        static $high_priority_element = true;

        if(is_bool($value))
        {
            $high_priority_element = $value;
        }

        return $high_priority_element;
    }
