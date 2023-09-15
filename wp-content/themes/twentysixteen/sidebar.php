<?php

?>

<?php if(is_active_sidebar('sidebar-1')) : ?>
    <aside id="secondary" class="sidebar widget-area">
        <?php dynamic_sidebar('sidebar-1'); ?>
    </aside><!-- .sidebar .widget-area -->
<?php endif; ?>
