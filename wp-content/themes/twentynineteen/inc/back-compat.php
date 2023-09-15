<?php

    function twentynineteen_switch_theme()
    {
        switch_theme(WP_DEFAULT_THEME);
        unset($_GET['activated']);
        add_action('admin_notices', 'twentynineteen_upgrade_notice');
    }

    add_action('after_switch_theme', 'twentynineteen_switch_theme');

    function twentynineteen_upgrade_notice()
    {
        printf('<div class="error"><p>%s</p></div>', sprintf(/* translators: %s: WordPress version. */ __('Twenty Nineteen requires at least WordPress version 4.7. You are running version %s. Please upgrade and try again.', 'twentynineteen'), $GLOBALS['wp_version']));
    }

    function twentynineteen_customize()
    {
        wp_die(sprintf(/* translators: %s: WordPress version. */ __('Twenty Nineteen requires at least WordPress version 4.7. You are running version %s. Please upgrade and try again.', 'twentynineteen'), $GLOBALS['wp_version']), '', [
            'back_link' => true,
        ]);
    }

    add_action('load-customize.php', 'twentynineteen_customize');

    function twentynineteen_preview()
    {
        if(isset($_GET['preview']))
        {
            wp_die(sprintf(/* translators: %s: WordPress version. */ __('Twenty Nineteen requires at least WordPress version 4.7. You are running version %s. Please upgrade and try again.', 'twentynineteen'), $GLOBALS['wp_version']));
        }
    }

    add_action('template_redirect', 'twentynineteen_preview');
