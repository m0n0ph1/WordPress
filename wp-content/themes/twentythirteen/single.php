<?php

    get_header(); ?>

<div id="primary" class="content-area">
    <div id="content" class="site-content" role="main">

        <?php
            // Start the loop.
            while(have_posts()) :
                the_post();
                ?>

                <?php get_template_part('content', get_post_format()); ?>
                <?php twentythirteen_post_nav(); ?>
                <?php comments_template(); ?>

            <?php endwhile; ?>

    </div><!-- #content -->
</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
