<?php

    if(isset($_GET['tab']) && ('theme-information' === $_GET['tab']))
    {
        define('IFRAME_REQUEST', true);
    }

    require_once __DIR__.'/admin.php';

    require ABSPATH.'wp-admin/theme-install.php';
