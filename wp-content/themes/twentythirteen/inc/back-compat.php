<?php

    function twentythirteen_switch_theme()
    {
        switch_theme(WP_DEFAULT_THEME, WP_DEFAULT_THEME);
        unset($_GET['activated']);
        add_action('admin_notices', 'twentythirteen_upgrade_notice');
    }

    add_action('after_switch_theme', 'twentythirteen_switch_theme');

    function twentythirteen_upgrade_notice()
    {
        printf('<div class="error"><p>%s</p></div>', sprintf(/* translators: %s: WordPress version. */ __('Twenty Thirteen requires at least WordPress version 3.6. You are running version %s. Please upgrade and try again.', 'twentythirteen'), $GLOBALS['wp_version']));
    }

    function twentythirteen_customize()
    {
        wp_die(sprintf(/* translators: %s: WordPress version. */ __('Twenty Thirteen requires at least WordPress version 3.6. You are running version %s. Please upgrade and try again.', 'twentythirteen'), $GLOBALS['wp_version']), '', [
            'back_link' => true,
        ]);
    }

    add_action('load-customize.php', 'twentythirteen_customize');

    function twentythirteen_preview()
    {
        if(isset($_GET['preview']))
        {
            wp_die(sprintf(/* translators: %s: WordPress version. */ __('Twenty Thirteen requires at least WordPress version 3.6. You are running version %s. Please upgrade and try again.', 'twentythirteen'), $GLOBALS['wp_version']));
        }
    }

    add_action('template_redirect', 'twentythirteen_preview');
