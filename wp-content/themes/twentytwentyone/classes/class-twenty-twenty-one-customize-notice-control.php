<?php

    class Twenty_Twenty_One_Customize_Notice_Control extends WP_Customize_Control
    {
        public $type = 'twenty-twenty-one-notice';

        public function render_content()
        {
            ?>parent::render_content();
            <div class="notice notice-warning">
                <p><?php esc_html_e('To access the Dark Mode settings, select a light background color.', 'twentytwentyone'); ?></p>
                <p>
                    <a href="<?php echo esc_url(__('https://wordpress.org/documentation/article/twenty-twenty-one/#dark-mode-support', 'twentytwentyone')); ?>">
                        <?php esc_html_e('Learn more about Dark Mode.', 'twentytwentyone'); ?>
                    </a></p>
            </div><!-- .notice -->
            <?php
        }
    }
