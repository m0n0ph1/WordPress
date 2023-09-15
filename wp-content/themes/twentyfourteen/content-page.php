<?php

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <?php
        // Page thumbnail and title.
        twentyfourteen_post_thumbnail();
        the_title('<header class="entry-header"><h1 class="entry-title">', '</h1></header><!-- .entry-header -->');
    ?>

    <div class="entry-content">
        <?php
            the_content();
            wp_link_pages([
                              'before' => '<div class="page-links"><span class="page-links-title">'.__('Pages:', 'twentyfourteen').'</span>',
                              'after' => '</div>',
                              'link_before' => '<span>',
                              'link_after' => '</span>',
                          ]);

            edit_post_link(__('Edit', 'twentyfourteen'), '<span class="edit-link">', '</span>');
        ?>
    </div><!-- .entry-content -->
</article><!-- #post-<?php the_ID(); ?> -->
