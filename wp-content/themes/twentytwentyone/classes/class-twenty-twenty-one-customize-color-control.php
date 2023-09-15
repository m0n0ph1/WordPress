<?php

    class Twenty_Twenty_One_Customize_Color_Control extends WP_Customize_Color_Control
    {
        public $type = 'twenty-twenty-one-color';

        public $palette;

        public function enqueue()
        {
            parent::enqueue();

            // Enqueue the script.
            wp_enqueue_script('twentytwentyone-control-color', get_theme_file_uri('assets/js/palette-colorpicker.js'), [
                'customize-controls',
                'jquery',
                'customize-base',
                'wp-color-picker',
            ],                wp_get_theme()->get('Version'), false);
        }

        public function to_json()
        {
            parent::to_json();
            $this->json['palette'] = $this->palette;
        }
    }
