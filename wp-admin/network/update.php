<?php

    if(
        isset($_GET['action']) && in_array($_GET['action'], [
            'update-selected',
            'activate-plugin',
            'update-selected-themes',
        ],                                 true)
    )
    {
        define('IFRAME_REQUEST', true);
    }

    require_once __DIR__.'/admin.php';

    require ABSPATH.'wp-admin/update.php';
