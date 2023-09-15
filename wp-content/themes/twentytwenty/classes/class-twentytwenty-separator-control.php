<?php

    if(class_exists('WP_Customize_Control') && ! class_exists('TwentyTwenty_Separator_Control'))
    {
        class TwentyTwenty_Separator_Control extends WP_Customize_Control
        {
            public function render_content()
            {
                parent::render_content();
                echo '<hr/>';
            }
        }
    }
