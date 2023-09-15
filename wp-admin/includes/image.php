<?php

    function wp_crop_image($src, $src_x, $src_y, $src_w, $src_h, $dst_w, $dst_h, $src_abs = false, $dst_file = false)
    {
        $src_file = $src;
        if(is_numeric($src))
        { // Handle int as attachment ID.
            $src_file = get_attached_file($src);

            if(! file_exists($src_file))
            {
                /*
                 * If the file doesn't exist, attempt a URL fopen on the src link.
                 * This can occur with certain file replication plugins.
                 */
                $src = _load_image_to_edit_path($src, 'full');
            }
            else
            {
                $src = $src_file;
            }
        }

        $editor = wp_get_image_editor($src);
        if(is_wp_error($editor))
        {
            return $editor;
        }

        $src = $editor->crop($src_x, $src_y, $src_w, $src_h, $dst_w, $dst_h, $src_abs);
        if(is_wp_error($src))
        {
            return $src;
        }

        if(! $dst_file)
        {
            $dst_file = str_replace(wp_basename($src_file), 'cropped-'.wp_basename($src_file), $src_file);
        }

        /*
         * The directory containing the original file may no longer exist when
         * using a replication plugin.
         */
        wp_mkdir_p(dirname($dst_file));

        $dst_file = dirname($dst_file).'/'.wp_unique_filename(dirname($dst_file), wp_basename($dst_file));

        $result = $editor->save($dst_file);
        if(is_wp_error($result))
        {
            return $result;
        }

        if(! empty($result['path']))
        {
            return $result['path'];
        }

        return $dst_file;
    }

    function wp_get_missing_image_subsizes($attachment_id)
    {
        if(! wp_attachment_is_image($attachment_id))
        {
            return [];
        }

        $registered_sizes = wp_get_registered_image_subsizes();
        $image_meta = wp_get_attachment_metadata($attachment_id);

        // Meta error?
        if(empty($image_meta))
        {
            return $registered_sizes;
        }

        // Use the originally uploaded image dimensions as full_width and full_height.
        if(! empty($image_meta['original_image']))
        {
            $image_file = wp_get_original_image_path($attachment_id);
            $imagesize = wp_getimagesize($image_file);
        }

        if(! empty($imagesize))
        {
            $full_width = $imagesize[0];
            $full_height = $imagesize[1];
        }
        else
        {
            $full_width = (int) $image_meta['width'];
            $full_height = (int) $image_meta['height'];
        }

        $possible_sizes = [];

        // Skip registered sizes that are too large for the uploaded image.
        foreach($registered_sizes as $size_name => $size_data)
        {
            if(image_resize_dimensions($full_width, $full_height, $size_data['width'], $size_data['height'], $size_data['crop']))
            {
                $possible_sizes[$size_name] = $size_data;
            }
        }

        if(empty($image_meta['sizes']))
        {
            $image_meta['sizes'] = [];
        }

        /*
         * Remove sizes that already exist. Only checks for matching "size names".
         * It is possible that the dimensions for a particular size name have changed.
         * For example the user has changed the values on the Settings -> Media screen.
         * However we keep the old sub-sizes with the previous dimensions
         * as the image may have been used in an older post.
         */
        $missing_sizes = array_diff_key($possible_sizes, $image_meta['sizes']);

        return apply_filters('wp_get_missing_image_subsizes', $missing_sizes, $image_meta, $attachment_id);
    }

    function wp_update_image_subsizes($attachment_id)
    {
        $image_meta = wp_get_attachment_metadata($attachment_id);
        $image_file = wp_get_original_image_path($attachment_id);

        if(empty($image_meta) || ! is_array($image_meta))
        {
            /*
             * Previously failed upload?
             * If there is an uploaded file, make all sub-sizes and generate all of the attachment meta.
             */
            if(! empty($image_file))
            {
                $image_meta = wp_create_image_subsizes($image_file, $attachment_id);
            }
            else
            {
                return new WP_Error('invalid_attachment', __('The attached file cannot be found.'));
            }
        }
        else
        {
            $missing_sizes = wp_get_missing_image_subsizes($attachment_id);

            if(empty($missing_sizes))
            {
                return $image_meta;
            }

            // This also updates the image meta.
            $image_meta = _wp_make_subsizes($missing_sizes, $image_file, $image_meta, $attachment_id);
        }

        $image_meta = apply_filters('wp_generate_attachment_metadata', $image_meta, $attachment_id, 'update');

        // Save the updated metadata.
        wp_update_attachment_metadata($attachment_id, $image_meta);

        return $image_meta;
    }

    function _wp_image_meta_replace_original($saved_data, $original_file, $image_meta, $attachment_id)
    {
        $new_file = $saved_data['path'];

        // Update the attached file meta.
        update_attached_file($attachment_id, $new_file);

        // Width and height of the new image.
        $image_meta['width'] = $saved_data['width'];
        $image_meta['height'] = $saved_data['height'];

        // Make the file path relative to the upload dir.
        $image_meta['file'] = _wp_relative_upload_path($new_file);

        // Add image file size.
        $image_meta['filesize'] = wp_filesize($new_file);

        // Store the original image file name in image_meta.
        $image_meta['original_image'] = wp_basename($original_file);

        return $image_meta;
    }

    function wp_create_image_subsizes($file, $attachment_id)
    {
        $imagesize = wp_getimagesize($file);

        if(empty($imagesize))
        {
            // File is not an image.
            return [];
        }

        // Default image meta.
        $image_meta = [
            'width' => $imagesize[0],
            'height' => $imagesize[1],
            'file' => _wp_relative_upload_path($file),
            'filesize' => wp_filesize($file),
            'sizes' => [],
        ];

        // Fetch additional metadata from EXIF/IPTC.
        $exif_meta = wp_read_image_metadata($file);

        if($exif_meta)
        {
            $image_meta['image_meta'] = $exif_meta;
        }

        // Do not scale (large) PNG images. May result in sub-sizes that have greater file size than the original. See #48736.
        if('image/png' !== $imagesize['mime'])
        {
            $threshold = (int) apply_filters('big_image_size_threshold', 2560, $imagesize, $file, $attachment_id);

            /*
             * If the original image's dimensions are over the threshold,
             * scale the image and use it as the "full" size.
             */
            if($threshold && ($image_meta['width'] > $threshold || $image_meta['height'] > $threshold))
            {
                $editor = wp_get_image_editor($file);

                if(is_wp_error($editor))
                {
                    // This image cannot be edited.
                    return $image_meta;
                }

                // Resize the image.
                $resized = $editor->resize($threshold, $threshold);
                $rotated = null;

                // If there is EXIF data, rotate according to EXIF Orientation.
                if(! is_wp_error($resized) && is_array($exif_meta))
                {
                    $resized = $editor->maybe_exif_rotate();
                    $rotated = $resized;
                }

                if(! is_wp_error($resized))
                {
                    /*
                     * Append "-scaled" to the image file name. It will look like "my_image-scaled.jpg".
                     * This doesn't affect the sub-sizes names as they are generated from the original image (for best quality).
                     */
                    $saved = $editor->save($editor->generate_filename('scaled'));

                    if(! is_wp_error($saved))
                    {
                        $image_meta = _wp_image_meta_replace_original($saved, $file, $image_meta, $attachment_id);

                        // If the image was rotated update the stored EXIF data.
                        if(true === $rotated && ! empty($image_meta['image_meta']['orientation']))
                        {
                            $image_meta['image_meta']['orientation'] = 1;
                        }
                    }
                    else
                    {
                        // TODO: Log errors.
                    }
                }
                else
                {
                    // TODO: Log errors.
                }
            }
            elseif(! empty($exif_meta['orientation']) && 1 !== (int) $exif_meta['orientation'])
            {
                // Rotate the whole original image if there is EXIF data and "orientation" is not 1.

                $editor = wp_get_image_editor($file);

                if(is_wp_error($editor))
                {
                    // This image cannot be edited.
                    return $image_meta;
                }

                // Rotate the image.
                $rotated = $editor->maybe_exif_rotate();

                if(true === $rotated)
                {
                    // Append `-rotated` to the image file name.
                    $saved = $editor->save($editor->generate_filename('rotated'));

                    if(! is_wp_error($saved))
                    {
                        $image_meta = _wp_image_meta_replace_original($saved, $file, $image_meta, $attachment_id);

                        // Update the stored EXIF data.
                        if(! empty($image_meta['image_meta']['orientation']))
                        {
                            $image_meta['image_meta']['orientation'] = 1;
                        }
                    }
                    else
                    {
                        // TODO: Log errors.
                    }
                }
            }
        }

        /*
         * Initial save of the new metadata.
         * At this point the file was uploaded and moved to the uploads directory
         * but the image sub-sizes haven't been created yet and the `sizes` array is empty.
         */
        wp_update_attachment_metadata($attachment_id, $image_meta);

        $new_sizes = wp_get_registered_image_subsizes();

        $new_sizes = apply_filters('intermediate_image_sizes_advanced', $new_sizes, $image_meta, $attachment_id);

        return _wp_make_subsizes($new_sizes, $file, $image_meta, $attachment_id);
    }

    function _wp_make_subsizes($new_sizes, $file, $image_meta, $attachment_id)
    {
        if(empty($image_meta) || ! is_array($image_meta))
        {
            // Not an image attachment.
            return [];
        }

        // Check if any of the new sizes already exist.
        if(isset($image_meta['sizes']) && is_array($image_meta['sizes']))
        {
            foreach($image_meta['sizes'] as $size_name => $size_meta)
            {
                /*
                 * Only checks "size name" so we don't override existing images even if the dimensions
                 * don't match the currently defined size with the same name.
                 * To change the behavior, unset changed/mismatched sizes in the `sizes` array in image meta.
                 */
                if(array_key_exists($size_name, $new_sizes))
                {
                    unset($new_sizes[$size_name]);
                }
            }
        }
        else
        {
            $image_meta['sizes'] = [];
        }

        if(empty($new_sizes))
        {
            // Nothing to do...
            return $image_meta;
        }

        /*
         * Sort the image sub-sizes in order of priority when creating them.
         * This ensures there is an appropriate sub-size the user can access immediately
         * even when there was an error and not all sub-sizes were created.
         */
        $priority = [
            'medium' => null,
            'large' => null,
            'thumbnail' => null,
            'medium_large' => null,
        ];

        $new_sizes = array_filter(array_merge($priority, $new_sizes));

        $editor = wp_get_image_editor($file);

        if(is_wp_error($editor))
        {
            // The image cannot be edited.
            return $image_meta;
        }

        // If stored EXIF data exists, rotate the source image before creating sub-sizes.
        if(! empty($image_meta['image_meta']))
        {
            $rotated = $editor->maybe_exif_rotate();

            if(is_wp_error($rotated))
            {
                // TODO: Log errors.
            }
        }

        if(method_exists($editor, 'make_subsize'))
        {
            foreach($new_sizes as $new_size_name => $new_size_data)
            {
                $new_size_meta = $editor->make_subsize($new_size_data);

                if(is_wp_error($new_size_meta))
                {
                    // TODO: Log errors.
                }
                else
                {
                    // Save the size meta value.
                    $image_meta['sizes'][$new_size_name] = $new_size_meta;
                    wp_update_attachment_metadata($attachment_id, $image_meta);
                }
            }
        }
        else
        {
            // Fall back to `$editor->multi_resize()`.
            $created_sizes = $editor->multi_resize($new_sizes);

            if(! empty($created_sizes))
            {
                $image_meta['sizes'] = array_merge($image_meta['sizes'], $created_sizes);
                wp_update_attachment_metadata($attachment_id, $image_meta);
            }
        }

        return $image_meta;
    }

    function wp_generate_attachment_metadata($attachment_id, $file)
    {
        $attachment = get_post($attachment_id);

        $metadata = [];
        $support = false;
        $mime_type = get_post_mime_type($attachment);

        if(preg_match('!^image/!', $mime_type) && file_is_displayable_image($file))
        {
            // Make thumbnails and other intermediate sizes.
            $metadata = wp_create_image_subsizes($file, $attachment_id);
        }
        elseif(wp_attachment_is('video', $attachment))
        {
            $metadata = wp_read_video_metadata($file);
            $support = current_theme_supports('post-thumbnails', 'attachment:video') || post_type_supports('attachment:video', 'thumbnail');
        }
        elseif(wp_attachment_is('audio', $attachment))
        {
            $metadata = wp_read_audio_metadata($file);
            $support = current_theme_supports('post-thumbnails', 'attachment:audio') || post_type_supports('attachment:audio', 'thumbnail');
        }

        /*
         * wp_read_video_metadata() and wp_read_audio_metadata() return `false`
         * if the attachment does not exist in the local filesystem,
         * so make sure to convert the value to an array.
         */
        if(! is_array($metadata))
        {
            $metadata = [];
        }

        if($support && ! empty($metadata['image']['data']))
        {
            // Check for existing cover.
            $hash = md5($metadata['image']['data']);
            $posts = get_posts([
                                   'fields' => 'ids',
                                   'post_type' => 'attachment',
                                   'post_mime_type' => $metadata['image']['mime'],
                                   'post_status' => 'inherit',
                                   'posts_per_page' => 1,
                                   'meta_key' => '_cover_hash',
                                   'meta_value' => $hash,
                               ]);
            $exists = reset($posts);

            if(! empty($exists))
            {
                update_post_meta($attachment_id, '_thumbnail_id', $exists);
            }
            else
            {
                $ext = '.jpg';
                switch($metadata['image']['mime'])
                {
                    case 'image/gif':
                        $ext = '.gif';
                        break;
                    case 'image/png':
                        $ext = '.png';
                        break;
                    case 'image/webp':
                        $ext = '.webp';
                        break;
                }
                $basename = str_replace('.', '-', wp_basename($file)).'-image'.$ext;
                $uploaded = wp_upload_bits($basename, '', $metadata['image']['data']);
                if(false === $uploaded['error'])
                {
                    $image_attachment = [
                        'post_mime_type' => $metadata['image']['mime'],
                        'post_type' => 'attachment',
                        'post_content' => '',
                    ];

                    $image_attachment = apply_filters('attachment_thumbnail_args', $image_attachment, $metadata, $uploaded);

                    $sub_attachment_id = wp_insert_attachment($image_attachment, $uploaded['file']);
                    add_post_meta($sub_attachment_id, '_cover_hash', $hash);
                    $attach_data = wp_generate_attachment_metadata($sub_attachment_id, $uploaded['file']);
                    wp_update_attachment_metadata($sub_attachment_id, $attach_data);
                    update_post_meta($attachment_id, '_thumbnail_id', $sub_attachment_id);
                }
            }
        }
        elseif('application/pdf' === $mime_type)
        {
            // Try to create image thumbnails for PDFs.

            $fallback_sizes = [
                'thumbnail',
                'medium',
                'large',
            ];

            $fallback_sizes = apply_filters('fallback_intermediate_image_sizes', $fallback_sizes, $metadata);

            $registered_sizes = wp_get_registered_image_subsizes();
            $merged_sizes = array_intersect_key($registered_sizes, array_flip($fallback_sizes));

            // Force thumbnails to be soft crops.
            if(isset($merged_sizes['thumbnail']) && is_array($merged_sizes['thumbnail']))
            {
                $merged_sizes['thumbnail']['crop'] = false;
            }

            // Only load PDFs in an image editor if we're processing sizes.
            if(! empty($merged_sizes))
            {
                $editor = wp_get_image_editor($file);

                if(! is_wp_error($editor))
                { // No support for this type of file.
                    /*
                     * PDFs may have the same file filename as JPEGs.
                     * Ensure the PDF preview image does not overwrite any JPEG images that already exist.
                     */
                    $dirname = dirname($file).'/';
                    $ext = '.'.pathinfo($file, PATHINFO_EXTENSION);
                    $preview_file = $dirname.wp_unique_filename($dirname, wp_basename($file, $ext).'-pdf.jpg');

                    $uploaded = $editor->save($preview_file, 'image/jpeg');
                    unset($editor);

                    // Resize based on the full size image, rather than the source.
                    if(! is_wp_error($uploaded))
                    {
                        $image_file = $uploaded['path'];
                        unset($uploaded['path']);

                        $metadata['sizes'] = [
                            'full' => $uploaded,
                        ];

                        // Save the meta data before any image post-processing errors could happen.
                        wp_update_attachment_metadata($attachment_id, $metadata);

                        // Create sub-sizes saving the image meta after each.
                        $metadata = _wp_make_subsizes($merged_sizes, $image_file, $metadata, $attachment_id);
                    }
                }
            }
        }

        // Remove the blob of binary data from the array.
        unset($metadata['image']['data']);

        // Capture file size for cases where it has not been captured yet, such as PDFs.
        if(! isset($metadata['filesize']) && file_exists($file))
        {
            $metadata['filesize'] = wp_filesize($file);
        }

        return apply_filters('wp_generate_attachment_metadata', $metadata, $attachment_id, 'create');
    }

    function wp_exif_frac2dec($str)
    {
        if(! is_scalar($str) || is_bool($str))
        {
            return 0;
        }

        if(! is_string($str))
        {
            return $str; // This can only be an integer or float, so this is fine.
        }

        // Fractions passed as a string must contain a single `/`.
        if(substr_count($str, '/') !== 1)
        {
            if(is_numeric($str))
            {
                return (float) $str;
            }

            return 0;
        }

        [$numerator, $denominator] = explode('/', $str);

        // Both the numerator and the denominator must be numbers.
        if(! is_numeric($numerator) || ! is_numeric($denominator))
        {
            return 0;
        }

        // The denominator must not be zero.
        if(0 == $denominator)
        { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison -- Deliberate loose comparison.
            return 0;
        }

        return $numerator / $denominator;
    }

    function wp_exif_date2ts($str)
    {
        [$date, $time] = explode(' ', trim($str));
        [$y, $m, $d] = explode(':', $date);

        return strtotime("{$y}-{$m}-{$d} {$time}");
    }

    function wp_read_image_metadata($file)
    {
        if(! file_exists($file))
        {
            return false;
        }

        [, , $image_type] = wp_getimagesize($file);

        /*
         * EXIF contains a bunch of data we'll probably never need formatted in ways
         * that are difficult to use. We'll normalize it and just extract the fields
         * that are likely to be useful. Fractions and numbers are converted to
         * floats, dates to unix timestamps, and everything else to strings.
         */
        $meta = [
            'aperture' => 0,
            'credit' => '',
            'camera' => '',
            'caption' => '',
            'created_timestamp' => 0,
            'copyright' => '',
            'focal_length' => 0,
            'iso' => 0,
            'shutter_speed' => 0,
            'title' => '',
            'orientation' => 0,
            'keywords' => [],
        ];

        $iptc = [];
        $info = [];
        /*
         * Read IPTC first, since it might contain data not available in exif such
         * as caption, description etc.
         */
        if(is_callable('iptcparse'))
        {
            wp_getimagesize($file, $info);

            if(! empty($info['APP13']))
            {
                // Don't silence errors when in debug mode, unless running unit tests.
                if(defined('WP_DEBUG') && WP_DEBUG && ! defined('WP_RUN_CORE_TESTS'))
                {
                    $iptc = iptcparse($info['APP13']);
                }
                else
                {
                    // phpcs:ignore WordPress.PHP.NoSilencedErrors -- Silencing notice and warning is intentional. See https://core.trac.wordpress.org/ticket/42480
                    $iptc = @iptcparse($info['APP13']);
                }

                if(! is_array($iptc))
                {
                    $iptc = [];
                }

                // Headline, "A brief synopsis of the caption".
                if(! empty($iptc['2#105'][0]))
                {
                    $meta['title'] = trim($iptc['2#105'][0]);
                    /*
                    * Title, "Many use the Title field to store the filename of the image,
                    * though the field may be used in many ways".
                    */
                }
                elseif(! empty($iptc['2#005'][0]))
                {
                    $meta['title'] = trim($iptc['2#005'][0]);
                }

                if(! empty($iptc['2#120'][0]))
                { // Description / legacy caption.
                    $caption = trim($iptc['2#120'][0]);

                    mbstring_binary_safe_encoding();
                    $caption_length = strlen($caption);
                    reset_mbstring_encoding();

                    if(empty($meta['title']) && $caption_length < 80)
                    {
                        // Assume the title is stored in 2:120 if it's short.
                        $meta['title'] = $caption;
                    }

                    $meta['caption'] = $caption;
                }

                if(! empty($iptc['2#110'][0]))
                { // Credit.
                    $meta['credit'] = trim($iptc['2#110'][0]);
                }
                elseif(! empty($iptc['2#080'][0]))
                { // Creator / legacy byline.
                    $meta['credit'] = trim($iptc['2#080'][0]);
                }

                if(! empty($iptc['2#055'][0]) && ! empty($iptc['2#060'][0]))
                { // Created date and time.
                    $meta['created_timestamp'] = strtotime($iptc['2#055'][0].' '.$iptc['2#060'][0]);
                }

                if(! empty($iptc['2#116'][0]))
                { // Copyright.
                    $meta['copyright'] = trim($iptc['2#116'][0]);
                }

                if(! empty($iptc['2#025'][0]))
                { // Keywords array.
                    $meta['keywords'] = array_values($iptc['2#025']);
                }
            }
        }

        $exif = [];

        $exif_image_types = apply_filters('wp_read_image_metadata_types', [
            IMAGETYPE_JPEG,
            IMAGETYPE_TIFF_II,
            IMAGETYPE_TIFF_MM,
        ]);

        if(is_callable('exif_read_data') && in_array($image_type, $exif_image_types, true))
        {
            // Don't silence errors when in debug mode, unless running unit tests.
            if(defined('WP_DEBUG') && WP_DEBUG && ! defined('WP_RUN_CORE_TESTS'))
            {
                $exif = exif_read_data($file);
            }
            else
            {
                // phpcs:ignore WordPress.PHP.NoSilencedErrors -- Silencing notice and warning is intentional. See https://core.trac.wordpress.org/ticket/42480
                $exif = @exif_read_data($file);
            }

            if(! is_array($exif))
            {
                $exif = [];
            }

            if(! empty($exif['ImageDescription']))
            {
                mbstring_binary_safe_encoding();
                $description_length = strlen($exif['ImageDescription']);
                reset_mbstring_encoding();

                if(empty($meta['title']) && $description_length < 80)
                {
                    // Assume the title is stored in ImageDescription.
                    $meta['title'] = trim($exif['ImageDescription']);
                }

                if(empty($meta['caption']) && ! empty($exif['COMPUTED']['UserComment']))
                {
                    $meta['caption'] = trim($exif['COMPUTED']['UserComment']);
                }

                if(empty($meta['caption']))
                {
                    $meta['caption'] = trim($exif['ImageDescription']);
                }
            }
            elseif(empty($meta['caption']) && ! empty($exif['Comments']))
            {
                $meta['caption'] = trim($exif['Comments']);
            }

            if(empty($meta['credit']))
            {
                if(! empty($exif['Artist']))
                {
                    $meta['credit'] = trim($exif['Artist']);
                }
                elseif(! empty($exif['Author']))
                {
                    $meta['credit'] = trim($exif['Author']);
                }
            }

            if(empty($meta['copyright']) && ! empty($exif['Copyright']))
            {
                $meta['copyright'] = trim($exif['Copyright']);
            }
            if(! empty($exif['FNumber']) && is_scalar($exif['FNumber']))
            {
                $meta['aperture'] = round(wp_exif_frac2dec($exif['FNumber']), 2);
            }
            if(! empty($exif['Model']))
            {
                $meta['camera'] = trim($exif['Model']);
            }
            if(empty($meta['created_timestamp']) && ! empty($exif['DateTimeDigitized']))
            {
                $meta['created_timestamp'] = wp_exif_date2ts($exif['DateTimeDigitized']);
            }
            if(! empty($exif['FocalLength']))
            {
                $meta['focal_length'] = (string) $exif['FocalLength'];
                if(is_scalar($exif['FocalLength']))
                {
                    $meta['focal_length'] = (string) wp_exif_frac2dec($exif['FocalLength']);
                }
            }
            if(! empty($exif['ISOSpeedRatings']))
            {
                $meta['iso'] = is_array($exif['ISOSpeedRatings']) ? reset($exif['ISOSpeedRatings']) : $exif['ISOSpeedRatings'];
                $meta['iso'] = trim($meta['iso']);
            }
            if(! empty($exif['ExposureTime']))
            {
                $meta['shutter_speed'] = (string) $exif['ExposureTime'];
                if(is_scalar($exif['ExposureTime']))
                {
                    $meta['shutter_speed'] = (string) wp_exif_frac2dec($exif['ExposureTime']);
                }
            }
            if(! empty($exif['Orientation']))
            {
                $meta['orientation'] = $exif['Orientation'];
            }
        }

        foreach(['title', 'caption', 'credit', 'copyright', 'camera', 'iso'] as $key)
        {
            if($meta[$key] && ! seems_utf8($meta[$key]))
            {
                $meta[$key] = utf8_encode($meta[$key]);
            }
        }

        foreach($meta['keywords'] as $key => $keyword)
        {
            if(! seems_utf8($keyword))
            {
                $meta['keywords'][$key] = utf8_encode($keyword);
            }
        }

        $meta = wp_kses_post_deep($meta);

        return apply_filters('wp_read_image_metadata', $meta, $file, $image_type, $iptc, $exif);
    }

    function file_is_valid_image($path)
    {
        $size = wp_getimagesize($path);

        return ! empty($size);
    }

    function file_is_displayable_image($path)
    {
        $displayable_image_types = [
            IMAGETYPE_GIF,
            IMAGETYPE_JPEG,
            IMAGETYPE_PNG,
            IMAGETYPE_BMP,
            IMAGETYPE_ICO,
            IMAGETYPE_WEBP,
        ];

        $info = wp_getimagesize($path);
        if(empty($info))
        {
            $result = false;
        }
        elseif(! in_array($info[2], $displayable_image_types, true))
        {
            $result = false;
        }
        else
        {
            $result = true;
        }

        return apply_filters('file_is_displayable_image', $result, $path);
    }

    function load_image_to_edit($attachment_id, $mime_type, $size = 'full')
    {
        $filepath = _load_image_to_edit_path($attachment_id, $size);
        if(empty($filepath))
        {
            return false;
        }

        switch($mime_type)
        {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($filepath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($filepath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($filepath);
                break;
            case 'image/webp':
                $image = false;
                if(function_exists('imagecreatefromwebp'))
                {
                    $image = imagecreatefromwebp($filepath);
                }
                break;
            default:
                $image = false;
                break;
        }

        if(is_gd_image($image))
        {
            $image = apply_filters('load_image_to_edit', $image, $attachment_id, $size);

            if(function_exists('imagealphablending') && function_exists('imagesavealpha'))
            {
                imagealphablending($image, false);
                imagesavealpha($image, true);
            }
        }

        return $image;
    }

    function _load_image_to_edit_path($attachment_id, $size = 'full')
    {
        $filepath = get_attached_file($attachment_id);

        if($filepath && file_exists($filepath))
        {
            if('full' !== $size)
            {
                $data = image_get_intermediate_size($attachment_id, $size);

                if($data)
                {
                    $filepath = path_join(dirname($filepath), $data['file']);

                    $filepath = apply_filters('load_image_to_edit_filesystempath', $filepath, $attachment_id, $size);
                }
            }
        }
        elseif(function_exists('fopen') && ini_get('allow_url_fopen'))
        {
            $filepath = apply_filters('load_image_to_edit_attachmenturl', wp_get_attachment_url($attachment_id), $attachment_id, $size);
        }

        return apply_filters('load_image_to_edit_path', $filepath, $attachment_id, $size);
    }

    function _copy_image_file($attachment_id)
    {
        $dst_file = get_attached_file($attachment_id);
        $src_file = $dst_file;

        if(! file_exists($src_file))
        {
            $src_file = _load_image_to_edit_path($attachment_id);
        }

        if($src_file)
        {
            $dst_file = str_replace(wp_basename($dst_file), 'copy-'.wp_basename($dst_file), $dst_file);
            $dst_file = dirname($dst_file).'/'.wp_unique_filename(dirname($dst_file), wp_basename($dst_file));

            /*
             * The directory containing the original file may no longer
             * exist when using a replication plugin.
             */
            wp_mkdir_p(dirname($dst_file));

            if(! copy($src_file, $dst_file))
            {
                $dst_file = false;
            }
        }
        else
        {
            $dst_file = false;
        }

        return $dst_file;
    }
