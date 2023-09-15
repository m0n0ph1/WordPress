<?php

    get_header(); ?>

<div id="primary" class="content-area">
    <div id="content" class="site-content" role="main">

        <?php if(have_posts()) : ?>
            <header class="archive-header">
                <h1 class="archive-title">
                    <?php
                        /* translators: %s: Post format name. */
                        printf(__('%s Archives', 'twentythirteen'), '<span>'.esc_html(get_post_format_string(get_post_format())).'</span>');
                    ?>
                </h1>
            </header><!-- .archive-header -->

            <?php
            // Start the loop.
            while(have_posts()) :
                the_post();
                ?>
                <?php get_template_part('content', get_post_format()); ?>
            <?php endwhile; ?>

            <?php twentythirteen_paging_nav(); ?>

        <?php else : ?>
            <?php get_template_part('content', 'none'); ?>
        <?php endif; ?>

    </div><!-- #content -->
</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
