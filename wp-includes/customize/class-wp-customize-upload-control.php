<?php

    class WP_Customize_Upload_Control extends WP_Customize_Media_Control
    {
        public $type = 'upload';

        public $mime_type = '';

        public $button_labels = [];

        public $removed = '';         // Unused.

        public $context;              // Unused.

        public $extensions = []; // Unused.

        public function to_json()
        {
            parent::to_json();

            $value = $this->value();
            if($value)
            {
                // Get the attachment model for the existing file.
                $attachment_id = attachment_url_to_postid($value);
                if($attachment_id)
                {
                    $this->json['attachment'] = wp_prepare_attachment_for_js($attachment_id);
                }
            }
        }
    }
