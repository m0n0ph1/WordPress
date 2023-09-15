<?php

    function twentyfourteen_switch_theme()
    {
        switch_theme(WP_DEFAULT_THEME, WP_DEFAULT_THEME);
        unset($_GET['activated']);
        add_action('admin_notices', 'twentyfourteen_upgrade_notice');
    }

    add_action('after_switch_theme', 'twentyfourteen_switch_theme');

    function twentyfourteen_upgrade_notice()
    {
        printf('<div class="error"><p>%s</p></div>', sprintf(/* translators: %s: WordPress version. */ __('Twenty Fourteen requires at least WordPress version 3.6. You are running version %s. Please upgrade and try again.', 'twentyfourteen'), $GLOBALS['wp_version']));
    }

    function twentyfourteen_customize()
    {
        wp_die(sprintf(/* translators: %s: WordPress version. */ __('Twenty Fourteen requires at least WordPress version 3.6. You are running version %s. Please upgrade and try again.', 'twentyfourteen'), $GLOBALS['wp_version']), '', [
            'back_link' => true,
        ]);
    }

    add_action('load-customize.php', 'twentyfourteen_customize');

    function twentyfourteen_preview()
    {
        if(isset($_GET['preview']))
        {
            wp_die(sprintf(/* translators: %s: WordPress version. */ __('Twenty Fourteen requires at least WordPress version 3.6. You are running version %s. Please upgrade and try again.', 'twentyfourteen'), $GLOBALS['wp_version']));
        }
    }

    add_action('template_redirect', 'twentyfourteen_preview');
