<?php

?>

</div><!-- .site-content -->

<footer id="colophon" class="site-footer">
    <?php if(has_nav_menu('primary')) : ?>
        <nav class="main-navigation" aria-label="<?php esc_attr_e('Footer Primary Menu', 'twentysixteen'); ?>">
            <?php
                wp_nav_menu([
                                'theme_location' => 'primary',
                                'menu_class' => 'primary-menu',
                            ]);
            ?>
        </nav><!-- .main-navigation -->
    <?php endif; ?>

    <?php if(has_nav_menu('social')) : ?>
        <nav class="social-navigation" aria-label="<?php esc_attr_e('Footer Social Links Menu', 'twentysixteen'); ?>">
            <?php
                wp_nav_menu([
                                'theme_location' => 'social',
                                'menu_class' => 'social-links-menu',
                                'depth' => 1,
                                'link_before' => '<span class="screen-reader-text">',
                                'link_after' => '</span>',
                            ]);
            ?>
        </nav><!-- .social-navigation -->
    <?php endif; ?>

    <div class="site-info">
        <?php

            do_action('twentysixteen_credits');
        ?>
        <span class="site-title"><a href="<?php echo esc_url(home_url('/')); ?>"
                                    rel="home"><?php bloginfo('name'); ?></a></span>
        <?php
            if(function_exists('the_privacy_policy_link'))
            {
                the_privacy_policy_link('', '<span role="separator" aria-hidden="true"></span>');
            }
        ?>
        <a href="<?php echo esc_url(__('https://wordpress.org/', 'twentysixteen')); ?>" class="imprint">
            <?php
                /* translators: %s: WordPress */
                printf(__('Proudly powered by %s', 'twentysixteen'), 'WordPress');
            ?>
        </a>
    </div><!-- .site-info -->
</footer><!-- .site-footer -->
</div><!-- .site-inner -->
</div><!-- .site -->

<?php wp_footer(); ?>
</body>
</html>
