<?php

    function twentyseventeen_switch_theme()
    {
        switch_theme(WP_DEFAULT_THEME);
        unset($_GET['activated']);
        add_action('admin_notices', 'twentyseventeen_upgrade_notice');
    }

    add_action('after_switch_theme', 'twentyseventeen_switch_theme');

    function twentyseventeen_upgrade_notice()
    {
        printf('<div class="error"><p>%s</p></div>', sprintf(/* translators: %s: The current WordPress version. */ __('Twenty Seventeen requires at least WordPress version 4.7. You are running version %s. Please upgrade and try again.', 'twentyseventeen'), $GLOBALS['wp_version']));
    }

    function twentyseventeen_customize()
    {
        wp_die(sprintf(/* translators: %s: The current WordPress version. */ __('Twenty Seventeen requires at least WordPress version 4.7. You are running version %s. Please upgrade and try again.', 'twentyseventeen'), $GLOBALS['wp_version']), '', [
            'back_link' => true,
        ]);
    }

    add_action('load-customize.php', 'twentyseventeen_customize');

    function twentyseventeen_preview()
    {
        if(isset($_GET['preview']))
        {
            wp_die(sprintf(/* translators: %s: The current WordPress version. */ __('Twenty Seventeen requires at least WordPress version 4.7. You are running version %s. Please upgrade and try again.', 'twentyseventeen'), $GLOBALS['wp_version']));
        }
    }

    add_action('template_redirect', 'twentyseventeen_preview');
