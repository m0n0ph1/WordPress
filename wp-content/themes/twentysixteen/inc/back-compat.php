<?php

    function twentysixteen_switch_theme()
    {
        switch_theme(WP_DEFAULT_THEME, WP_DEFAULT_THEME);

        unset($_GET['activated']);

        add_action('admin_notices', 'twentysixteen_upgrade_notice');
    }

    add_action('after_switch_theme', 'twentysixteen_switch_theme');

    function twentysixteen_upgrade_notice()
    {
        printf('<div class="error"><p>%s</p></div>', sprintf(/* translators: %s: The current WordPress version. */ __('Twenty Sixteen requires at least WordPress version 4.4. You are running version %s. Please upgrade and try again.', 'twentysixteen'), $GLOBALS['wp_version']));
    }

    function twentysixteen_customize()
    {
        wp_die(sprintf(/* translators: %s: The current WordPress version. */ __('Twenty Sixteen requires at least WordPress version 4.4. You are running version %s. Please upgrade and try again.', 'twentysixteen'), $GLOBALS['wp_version']), '', [
            'back_link' => true,
        ]);
    }

    add_action('load-customize.php', 'twentysixteen_customize');

    function twentysixteen_preview()
    {
        if(isset($_GET['preview']))
        {
            wp_die(sprintf(/* translators: %s: The current WordPress version. */ __('Twenty Sixteen requires at least WordPress version 4.4. You are running version %s. Please upgrade and try again.', 'twentysixteen'), $GLOBALS['wp_version']));
        }
    }

    add_action('template_redirect', 'twentysixteen_preview');
