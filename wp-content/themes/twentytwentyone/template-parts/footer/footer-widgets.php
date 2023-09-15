<?php

    if(is_active_sidebar('sidebar-1')) : ?>

        <aside class="widget-area">
            <?php dynamic_sidebar('sidebar-1'); ?>
        </aside><!-- .widget-area -->

    <?php
    endif;
