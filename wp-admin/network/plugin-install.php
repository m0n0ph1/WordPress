<?php

    if(isset($_GET['tab']) && ('plugin-information' === $_GET['tab']))
    {
        define('IFRAME_REQUEST', true);
    }

    require_once __DIR__.'/admin.php';

    require ABSPATH.'wp-admin/plugin-install.php';
