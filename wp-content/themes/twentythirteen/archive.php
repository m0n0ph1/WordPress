<?php

    get_header(); ?>

<div id="primary" class="content-area">
    <div id="content" class="site-content" role="main">

        <?php if(have_posts()) : ?>
            <header class="archive-header">
                <h1 class="archive-title">
                    <?php
                        if(is_day())
                        {
                            /* translators: %s: Date. */
                            printf(__('Daily Archives: %s', 'twentythirteen'), get_the_date());
                        }
                        elseif(is_month())
                        {
                            /* translators: %s: Date. */
                            printf(__('Monthly Archives: %s', 'twentythirteen'), get_the_date(_x('F Y', 'monthly archives date format', 'twentythirteen')));
                        }
                        elseif(is_year())
                        {
                            /* translators: %s: Date. */
                            printf(__('Yearly Archives: %s', 'twentythirteen'), get_the_date(_x('Y', 'yearly archives date format', 'twentythirteen')));
                        }
                        else
                        {
                            _e('Archives', 'twentythirteen');
                        }
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
