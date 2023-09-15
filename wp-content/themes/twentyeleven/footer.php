<?php

?>

</div><!-- #main -->

<footer id="colophon">

    <?php
        /*
         * A sidebar in the footer? Yep. You can customize
         * your footer with three columns of widgets.
         */
        if(! is_404())
        {
            get_sidebar('footer');
        }
    ?>

    <div id="site-generator">
        <?php do_action('twentyeleven_credits'); ?>
        <?php
            if(function_exists('the_privacy_policy_link'))
            {
                the_privacy_policy_link('', '<span role="separator" aria-hidden="true"></span>');
            }
        ?>
        <a href="<?php echo esc_url(__('https://wordpress.org/', 'twentyeleven')); ?>"
           class="imprint"
           title="<?php esc_attr_e('Semantic Personal Publishing Platform', 'twentyeleven'); ?>">
            <?php
                /* translators: %s: WordPress */
                printf(__('Proudly powered by %s', 'twentyeleven'), 'WordPress');
            ?>
        </a>
    </div>
</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
