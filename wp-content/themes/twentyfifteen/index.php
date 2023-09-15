<?php

    get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

        <?php if(have_posts()) : ?>

            <?php if(is_home() && ! is_front_page()) : ?>
                <header>
                    <h1 class="page-title screen-reader-text"><?php single_post_title(); ?></h1>
                </header>
            <?php endif; ?>

            <?php
            // Start the loop.
            while(have_posts()) :
                the_post();

                /*
                 * Include the post format-specific template for the content. If you want
                 * to use this in a child theme, then include a file called content-___.php
                 * (where ___ is the post format) and that will be used instead.
                 */
                get_template_part('content', get_post_format());

                // End the loop.
            endwhile;

            // Previous/next page navigation.
            the_posts_pagination([
                                     'prev_text' => __('Previous page', 'twentyfifteen'),
                                     'next_text' => __('Next page', 'twentyfifteen'),
                                     /* translators: Hidden accessibility text. */
                                     'before_page_number' => '<span class="meta-nav screen-reader-text">'.__('Page', 'twentyfifteen').' </span>',
                                 ]);

        // If no content, include the "No posts found" template.
        else :
            get_template_part('content', 'none');

        endif;
        ?>

    </main><!-- .site-main -->
</div><!-- .content-area -->

<?php get_footer(); ?>
