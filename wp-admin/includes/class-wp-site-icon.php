<?php

    #[AllowDynamicProperties]
    class WP_Site_Icon
    {
        public $min_size = 512;

        public $page_crop = 512;

        public $site_icon_sizes = [
            /*
             * Square, medium sized tiles for IE11+.
             *
             * See https://msdn.microsoft.com/library/dn455106(v=vs.85).aspx
             */
            270,

            /*
             * App icon for Android/Chrome.
             *
             * @link https://developers.google.com/web/updates/2014/11/Support-for-theme-color-in-Chrome-39-for-Android
             * @link https://developer.chrome.com/multidevice/android/installtohomescreen
             */
            192,

            /*
             * App icons up to iPhone 6 Plus.
             *
             * See https://developer.apple.com/library/prerelease/ios/documentation/UserExperience/Conceptual/MobileHIG/IconMatrix.html
             */
            180,

            // Our regular Favicon.
            32,
        ];

        public function __construct()
        {
            add_action('delete_attachment', [$this, 'delete_attachment_data']);
            add_filter('get_post_metadata', [$this, 'get_post_metadata'], 10, 4);
        }

        public function create_attachment_object($cropped, $parent_attachment_id)
        {
            $parent = get_post($parent_attachment_id);
            $parent_url = wp_get_attachment_url($parent->ID);
            $url = str_replace(wp_basename($parent_url), wp_basename($cropped), $parent_url);

            $size = wp_getimagesize($cropped);
            $image_type = ($size) ? $size['mime'] : 'image/jpeg';

            $attachment = [
                'ID' => $parent_attachment_id,
                'post_title' => wp_basename($cropped),
                'post_content' => $url,
                'post_mime_type' => $image_type,
                'guid' => $url,
                'context' => 'site-icon',
            ];

            return $attachment;
        }

        public function insert_attachment($attachment, $file)
        {
            $attachment_id = wp_insert_attachment($attachment, $file);
            $metadata = wp_generate_attachment_metadata($attachment_id, $file);

            $metadata = apply_filters('site_icon_attachment_metadata', $metadata);
            wp_update_attachment_metadata($attachment_id, $metadata);

            return $attachment_id;
        }

        public function additional_sizes($sizes = [])
        {
            $only_crop_sizes = [];

            $this->site_icon_sizes = apply_filters('site_icon_image_sizes', $this->site_icon_sizes);

            // Use a natural sort of numbers.
            natsort($this->site_icon_sizes);
            $this->site_icon_sizes = array_reverse($this->site_icon_sizes);

            // Ensure that we only resize the image into sizes that allow cropping.
            foreach($sizes as $name => $size_array)
            {
                if(isset($size_array['crop']))
                {
                    $only_crop_sizes[$name] = $size_array;
                }
            }

            foreach($this->site_icon_sizes as $size)
            {
                if($size < $this->min_size)
                {
                    $only_crop_sizes['site_icon-'.$size] = [
                        'width ' => $size,
                        'height' => $size,
                        'crop' => true,
                    ];
                }
            }

            return $only_crop_sizes;
        }

        public function intermediate_image_sizes($sizes = [])
        {
            $this->site_icon_sizes = apply_filters('site_icon_image_sizes', $this->site_icon_sizes);
            foreach($this->site_icon_sizes as $size)
            {
                $sizes[] = 'site_icon-'.$size;
            }

            return $sizes;
        }

        public function delete_attachment_data($post_id)
        {
            $site_icon_id = (int) get_option('site_icon');

            if($site_icon_id && $post_id === $site_icon_id)
            {
                delete_option('site_icon');
            }
        }

        public function get_post_metadata($value, $post_id, $meta_key, $single)
        {
            if($single && '_wp_attachment_backup_sizes' === $meta_key)
            {
                $site_icon_id = (int) get_option('site_icon');

                if($post_id === $site_icon_id)
                {
                    add_filter('intermediate_image_sizes', [$this, 'intermediate_image_sizes']);
                }
            }

            return $value;
        }
    }
