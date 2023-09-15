<?php

    class WP_Customize_Background_Image_Control extends WP_Customize_Image_Control
    {
        public $type = 'background';

        public function __construct($manager)
        {
            parent::__construct($manager, 'background_image', [
                'label' => __('Background Image'),
                'section' => 'background_image',
            ]);
        }

        public function enqueue()
        {
            parent::enqueue();

            $custom_background = get_theme_support('custom-background');
            wp_localize_script('customize-controls', '_wpCustomizeBackground', [
                'defaults' => ! empty($custom_background[0]) ? $custom_background[0] : [],
                'nonces' => [
                    'add' => wp_create_nonce('background-add'),
                ],
            ]);
        }
    }
