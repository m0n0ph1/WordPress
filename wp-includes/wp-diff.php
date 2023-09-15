<?php

    if(! class_exists('Text_Diff', false))
    {
        require ABSPATH.WPINC.'/Text/Diff.php';

        require ABSPATH.WPINC.'/Text/Diff/Renderer.php';

        require ABSPATH.WPINC.'/Text/Diff/Renderer/inline.php';
    }

    require ABSPATH.WPINC.'/class-wp-text-diff-renderer-table.php';
    require ABSPATH.WPINC.'/class-wp-text-diff-renderer-inline.php';
