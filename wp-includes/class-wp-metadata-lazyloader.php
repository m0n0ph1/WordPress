<?php

    #[AllowDynamicProperties]
    class WP_Metadata_Lazyloader
    {
        protected $pending_objects;

        protected $settings = [];

        public function __construct()
        {
            $this->settings = [
                'term' => [
                    'filter' => 'get_term_metadata',
                    'callback' => [$this, 'lazyload_meta_callback'],
                ],
                'comment' => [
                    'filter' => 'get_comment_metadata',
                    'callback' => [$this, 'lazyload_meta_callback'],
                ],
                'blog' => [
                    'filter' => 'get_blog_metadata',
                    'callback' => [$this, 'lazyload_meta_callback'],
                ],
            ];
        }

        public function queue_objects($object_type, $object_ids)
        {
            if(! isset($this->settings[$object_type]))
            {
                return new WP_Error('invalid_object_type', __('Invalid object type.'));
            }

            $type_settings = $this->settings[$object_type];

            if(! isset($this->pending_objects[$object_type]))
            {
                $this->pending_objects[$object_type] = [];
            }

            foreach($object_ids as $object_id)
            {
                // Keyed by ID for faster lookup.
                if(! isset($this->pending_objects[$object_type][$object_id]))
                {
                    $this->pending_objects[$object_type][$object_id] = 1;
                }
            }

            add_filter($type_settings['filter'], $type_settings['callback'], 10, 5);

            do_action('metadata_lazyloader_queued_objects', $object_ids, $object_type, $this);
        }

        public function lazyload_term_meta($check)
        {
            _deprecated_function(__METHOD__, '6.3.0', 'WP_Metadata_Lazyloader::lazyload_meta_callback');

            return $this->lazyload_meta_callback($check, 0, '', false, 'term');
        }

        public function lazyload_meta_callback($check, $object_id, $meta_key, $single, $meta_type)
        {
            if(empty($this->pending_objects[$meta_type]))
            {
                return $check;
            }

            $object_ids = array_keys($this->pending_objects[$meta_type]);
            if($object_id && ! in_array($object_id, $object_ids, true))
            {
                $object_ids[] = $object_id;
            }

            update_meta_cache($meta_type, $object_ids);

            // No need to run again for this set of objects.
            $this->reset_queue($meta_type);

            return $check;
        }

        public function reset_queue($object_type)
        {
            if(! isset($this->settings[$object_type]))
            {
                return new WP_Error('invalid_object_type', __('Invalid object type.'));
            }

            $type_settings = $this->settings[$object_type];

            $this->pending_objects[$object_type] = [];
            remove_filter($type_settings['filter'], $type_settings['callback']);
        }

        public function lazyload_comment_meta($check)
        {
            _deprecated_function(__METHOD__, '6.3.0', 'WP_Metadata_Lazyloader::lazyload_meta_callback');

            return $this->lazyload_meta_callback($check, 0, '', false, 'comment');
        }
    }
