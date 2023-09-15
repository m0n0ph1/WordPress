<?php

    class WP_Customize_Cropped_Image_Control extends WP_Customize_Image_Control
    {
        public $type = 'cropped_image';

        public $width = 150;

        public $height = 150;

        public $flex_width = false;

        public $flex_height = false;

        public function enqueue()
        {
            wp_enqueue_script('customize-views');

            parent::enqueue();
        }

        public function to_json()
        {
            parent::to_json();

            $this->json['width'] = absint($this->width);
            $this->json['height'] = absint($this->height);
            $this->json['flex_width'] = absint($this->flex_width);
            $this->json['flex_height'] = absint($this->flex_height);
        }
    }
