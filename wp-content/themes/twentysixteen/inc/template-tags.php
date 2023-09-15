<?php

    if(! function_exists('twentysixteen_entry_meta')) :

        function twentysixteen_entry_meta()
        {
            if('post' === get_post_type())
            {
                $author_avatar_size = apply_filters('twentysixteen_author_avatar_size', 49);
                printf('<span class="byline"><span class="author vcard">%1$s<span class="screen-reader-text">%2$s </span> <a class="url fn n" href="%3$s">%4$s</a></span></span>', get_avatar(get_the_author_meta('user_email'), $author_avatar_size), /* translators: Hidden accessibility text. */ _x('Author', 'Used before post author name.', 'twentysixteen'), esc_url(get_author_posts_url(get_the_author_meta('ID'))), get_the_author());
            }

            if(in_array(get_post_type(), ['post', 'attachment'], true))
            {
                twentysixteen_entry_date();
            }

            $format = get_post_format();
            if(current_theme_supports('post-formats', $format))
            {
                printf('<span class="entry-format">%1$s<a href="%2$s">%3$s</a></span>', sprintf('<span class="screen-reader-text">%s </span>', /* translators: Hidden accessibility text. */ _x('Format', 'Used before post format.', 'twentysixteen')), esc_url(get_post_format_link($format)), get_post_format_string($format));
            }

            if('post' === get_post_type())
            {
                twentysixteen_entry_taxonomies();
            }

            if(! is_singular() && ! post_password_required() && (comments_open() || get_comments_number()))
            {
                echo '<span class="comments-link">';
                /* translators: %s: Post title. Only visible to screen readers. */
                comments_popup_link(sprintf(__('Leave a comment<span class="screen-reader-text"> on %s</span>', 'twentysixteen'), get_the_title()));
                echo '</span>';
            }
        }
    endif;

    if(! function_exists('twentysixteen_entry_date')) :

        function twentysixteen_entry_date()
        {
            $time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';

            if(get_the_time('U') !== get_the_modified_time('U'))
            {
                $time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated" datetime="%3$s">%4$s</time>';
            }

            $time_string = sprintf($time_string, esc_attr(get_the_date('c')), get_the_date(), esc_attr(get_the_modified_date('c')), get_the_modified_date());

            printf('<span class="posted-on"><span class="screen-reader-text">%1$s </span><a href="%2$s" rel="bookmark">%3$s</a></span>', /* translators: Hidden accessibility text. */ _x('Posted on', 'Used before publish date.', 'twentysixteen'), esc_url(get_permalink()), $time_string);
        }
    endif;

    if(! function_exists('twentysixteen_entry_taxonomies')) :

        function twentysixteen_entry_taxonomies()
        {
            $categories_list = get_the_category_list(_x(', ', 'Used between list items, there is a space after the comma.', 'twentysixteen'));
            if($categories_list && twentysixteen_categorized_blog())
            {
                printf('<span class="cat-links"><span class="screen-reader-text">%1$s </span>%2$s</span>', /* translators: Hidden accessibility text. */ _x('Categories', 'Used before category names.', 'twentysixteen'), $categories_list);
            }

            $tags_list = get_the_tag_list('', _x(', ', 'Used between list items, there is a space after the comma.', 'twentysixteen'));
            if($tags_list && ! is_wp_error($tags_list))
            {
                printf('<span class="tags-links"><span class="screen-reader-text">%1$s </span>%2$s</span>', /* translators: Hidden accessibility text. */ _x('Tags', 'Used before tag names.', 'twentysixteen'), $tags_list);
            }
        }
    endif;

    if(! function_exists('twentysixteen_post_thumbnail')) :

        function twentysixteen_post_thumbnail()
        {
            if(post_password_required() || is_attachment() || ! has_post_thumbnail())
            {
                return;
            }

            if(is_singular()) :
                ?>

                <div class="post-thumbnail">
                    <?php the_post_thumbnail(); ?>
                </div><!-- .post-thumbnail -->

            <?php else : ?>

                <a class="post-thumbnail" href="<?php the_permalink(); ?>" aria-hidden="true">
                    <?php the_post_thumbnail('post-thumbnail', ['alt' => the_title_attribute('echo=0')]); ?>
                </a>

            <?php
            endif; // End is_singular().
        }
    endif;

    if(! function_exists('twentysixteen_excerpt')) :

        function twentysixteen_excerpt($css_class = 'entry-summary')
        {
            $css_class = esc_attr($css_class);

            if(has_excerpt() || is_search()) :
                ?>
                <div class="<?php echo $css_class; ?>">
                    <?php the_excerpt(); ?>
                </div><!-- .<?php echo $css_class; ?> -->
            <?php
            endif;
        }
    endif;

    if(! function_exists('twentysixteen_excerpt_more') && ! is_admin()) :

        function twentysixteen_excerpt_more()
        {
            $link = sprintf('<a href="%1$s" class="more-link">%2$s</a>', esc_url(get_permalink(get_the_ID())), /* translators: %s: Post title. Only visible to screen readers. */ sprintf(__('Continue reading<span class="screen-reader-text"> "%s"</span>', 'twentysixteen'), get_the_title(get_the_ID())));

            return ' &hellip; '.$link;
        }

        add_filter('excerpt_more', 'twentysixteen_excerpt_more');
    endif;

    if(! function_exists('twentysixteen_categorized_blog')) :

        function twentysixteen_categorized_blog()
        {
            $all_the_cool_cats = get_transient('twentysixteen_categories');
            if(false === $all_the_cool_cats)
            {
                // Create an array of all the categories that are attached to posts.
                $all_the_cool_cats = get_categories([
                                                        'fields' => 'ids',
                                                        // We only need to know if there is more than one category.
                                                        'number' => 2,
                                                    ]);

                // Count the number of categories that are attached to the posts.
                $all_the_cool_cats = count($all_the_cool_cats);

                set_transient('twentysixteen_categories', $all_the_cool_cats);
            }

            if($all_the_cool_cats > 1 || is_preview())
            {
                // This blog has more than 1 category so twentysixteen_categorized_blog() should return true.
                return true;
            }
            else
            {
                // This blog has only 1 category so twentysixteen_categorized_blog() should return false.
                return false;
            }
        }
    endif;

    function twentysixteen_category_transient_flusher()
    {
        if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        {
            return;
        }
        // Like, beat it. Dig?
        delete_transient('twentysixteen_categories');
    }

    add_action('edit_category', 'twentysixteen_category_transient_flusher');
    add_action('save_post', 'twentysixteen_category_transient_flusher');

    if(! function_exists('twentysixteen_the_custom_logo')) :

        function twentysixteen_the_custom_logo()
        {
            if(function_exists('the_custom_logo'))
            {
                the_custom_logo();
            }
        }
    endif;

    if(! function_exists('wp_body_open')) :

        function wp_body_open()
        {
            do_action('wp_body_open');
        }
    endif;
