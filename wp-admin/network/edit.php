<?php

    require_once __DIR__.'/admin.php';

    $action = (isset($_GET['action'])) ? $_GET['action'] : '';

    if(empty($action))
    {
        wp_redirect(network_admin_url());
        exit;
    }

    do_action('wpmuadminedit');

    do_action("network_admin_edit_{$action}");

    wp_redirect(network_admin_url());
    exit;
