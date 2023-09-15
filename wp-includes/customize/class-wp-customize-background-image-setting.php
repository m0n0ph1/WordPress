<?php

    final class WP_Customize_Background_Image_Setting extends WP_Customize_Setting
    {
        public $id = 'background_image_thumb';

        public function update($value)
        {
            remove_theme_mod('background_image_thumb');
        }
    }
