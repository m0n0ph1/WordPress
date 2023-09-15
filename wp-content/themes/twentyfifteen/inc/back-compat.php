<?php

    function twentyfifteen_switch_theme()
    {
        switch_theme(WP_DEFAULT_THEME, WP_DEFAULT_THEME);
        unset($_GET['activated']);
        add_action('admin_notices', 'twentyfifteen_upgrade_notice');
    }

    add_action('after_switch_theme', 'twentyfifteen_switch_theme');

    function twentyfifteen_upgrade_notice()
    {
        printf('<div class="error"><p>%s</p></div>', sprintf(/* translators: %s: WordPress version. */ __('Twenty Fifteen requires at least WordPress version 4.1. You are running version %s. Please upgrade and try again.', 'twentyfifteen'), $GLOBALS['wp_version']));
    }

    function twentyfifteen_customize()
    {
        wp_die(sprintf(/* translators: %s: WordPress version. */ __('Twenty Fifteen requires at least WordPress version 4.1. You are running version %s. Please upgrade and try again.', 'twentyfifteen'), $GLOBALS['wp_version']), '', [
            'back_link' => true,
        ]);
    }

    add_action('load-customize.php', 'twentyfifteen_customize');

    function twentyfifteen_preview()
    {
        if(isset($_GET['preview']))
        {
            wp_die(sprintf(/* translators: %s: WordPress version. */ __('Twenty Fifteen requires at least WordPress version 4.1. You are running version %s. Please upgrade and try again.', 'twentyfifteen'), $GLOBALS['wp_version']));
        }
    }

    add_action('template_redirect', 'twentyfifteen_preview');
