<?php

    if(function_exists('_deprecated_file'))
    {
        // Note: WPINC may not be defined yet, so 'wp-includes' is used here.
        _deprecated_file(basename(__FILE__), '6.1.0', 'wp-includes/class-wpdb.php');
    }

    require_once __DIR__.'/class-wpdb.php';
