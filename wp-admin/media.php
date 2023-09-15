<?php

    require_once __DIR__.'/admin.php';

    $parent_file = 'upload.php';
    $submenu_file = 'upload.php';

    wp_reset_vars(['action']);

    switch($action)
    {
        case 'editattachment':
        case 'edit':
            if(empty($_GET['attachment_id']))
            {
                wp_redirect(admin_url('upload.php?error=deprecated'));
                exit;
            }
            $att_id = (int) $_GET['attachment_id'];

            wp_redirect(admin_url("upload.php?item={$att_id}&error=deprecated"));
            exit;

        default:
            wp_redirect(admin_url('upload.php?error=deprecated'));
            exit;
    }
