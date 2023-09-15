<?php

    get_header(); ?>

<section id="primary" class="content-area">
    <main id="main" class="site-main">

        <?php if(have_posts()) : ?>

            <header class="page-header">
                <h1 class="page-title">
                    <?php
                        /* translators: %s: The search query. */
                        printf(__('Search Results for: %s', 'twentysixteen'), '<span>'.esc_html(get_search_query()).'</span>');
                    ?>
                </h1>
            </header><!-- .page-header -->

            <?php
            // Start the loop.
            while(have_posts()) :
                the_post();

                get_template_part('template-parts/content', 'search');

                // End the loop.
            endwhile;

            // Previous/next page navigation.
            the_posts_pagination([
                                     'prev_text' => __('Previous page', 'twentysixteen'),
                                     'next_text' => __('Next page', 'twentysixteen'),
                                     /* translators: Hidden accessibility text. */
                                     'before_page_number' => '<span class="meta-nav screen-reader-text">'.__('Page', 'twentysixteen').' </span>',
                                 ]);

        // If no content, include the "No posts found" template.
        else :
            get_template_part('template-parts/content', 'none');

        endif;
        ?>

    </main><!-- .site-main -->
</section><!-- .content-area -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
