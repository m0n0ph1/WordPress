<?php

?>

<section class="no-results not-found">
    <header class="page-header">
        <h1 class="page-title"><?php _e('Nothing Found', 'twentyseventeen'); ?></h1>
    </header>
    <div class="page-content">
        <?php
            if(is_home() && current_user_can('publish_posts')) :
                ?>

                <p>
                    <?php
                        /* translators: %s: Post editor URL. */
                        printf(__('Ready to publish your first post? <a href="%s">Get started here</a>.', 'twentyseventeen'), esc_url(admin_url('post-new.php')));
                    ?>
                </p>

            <?php else : ?>

                <p><?php _e('It seems we can&rsquo;t find what you&rsquo;re looking for. Perhaps searching can help.', 'twentyseventeen'); ?></p>
                <?php
                get_search_form();

            endif;
        ?>
    </div><!-- .page-content -->
</section><!-- .no-results -->
