<?php

    if(! defined('WP_ADMIN'))
    {
        /*
         * This file is being included from a file other than wp-admin/admin.php, so
         * some setup was skipped. Make sure the admin message catalog is loaded since
         * load_default_textdomain() will not have done so in this context.
         */
        $admin_locale = get_locale();
        load_textdomain('default', WP_LANG_DIR.'/admin-'.$admin_locale.'.mo', $admin_locale);
        unset($admin_locale);
    }

    require_once ABSPATH.'wp-admin/includes/admin-filters.php';

    require_once ABSPATH.'wp-admin/includes/bookmark.php';

    require_once ABSPATH.'wp-admin/includes/comment.php';

    require_once ABSPATH.'wp-admin/includes/file.php';

    require_once ABSPATH.'wp-admin/includes/image.php';

    require_once ABSPATH.'wp-admin/includes/media.php';

    require_once ABSPATH.'wp-admin/includes/import.php';

    require_once ABSPATH.'wp-admin/includes/misc.php';

    require_once ABSPATH.'wp-admin/includes/class-wp-privacy-policy-content.php';

    require_once ABSPATH.'wp-admin/includes/options.php';

    require_once ABSPATH.'wp-admin/includes/plugin.php';

    require_once ABSPATH.'wp-admin/includes/post.php';

    require_once ABSPATH.'wp-admin/includes/class-wp-screen.php';
    require_once ABSPATH.'wp-admin/includes/screen.php';

    require_once ABSPATH.'wp-admin/includes/taxonomy.php';

    require_once ABSPATH.'wp-admin/includes/template.php';

    require_once ABSPATH.'wp-admin/includes/class-wp-list-table.php';
    require_once ABSPATH.'wp-admin/includes/class-wp-list-table-compat.php';
    require_once ABSPATH.'wp-admin/includes/list-table.php';

    require_once ABSPATH.'wp-admin/includes/theme.php';

    require_once ABSPATH.'wp-admin/includes/privacy-tools.php';

// Previously in wp-admin/includes/user.php. Need to be loaded for backward compatibility.
    require_once ABSPATH.'wp-admin/includes/class-wp-privacy-requests-table.php';
    require_once ABSPATH.'wp-admin/includes/class-wp-privacy-data-export-requests-list-table.php';
    require_once ABSPATH.'wp-admin/includes/class-wp-privacy-data-removal-requests-list-table.php';

    require_once ABSPATH.'wp-admin/includes/user.php';

    require_once ABSPATH.'wp-admin/includes/class-wp-site-icon.php';

    require_once ABSPATH.'wp-admin/includes/update.php';

    require_once ABSPATH.'wp-admin/includes/deprecated.php';

    if(is_multisite())
    {
        require_once ABSPATH.'wp-admin/includes/ms-admin-filters.php';
        require_once ABSPATH.'wp-admin/includes/ms.php';
        require_once ABSPATH.'wp-admin/includes/ms-deprecated.php';
    }
