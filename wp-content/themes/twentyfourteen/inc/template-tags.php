<?php

    if(! function_exists('twentyfourteen_paging_nav')) :

        function twentyfourteen_paging_nav()
        {
            global $wp_query, $wp_rewrite;

            // Don't print empty markup if there's only one page.
            if($wp_query->max_num_pages < 2)
            {
                return;
            }

            $paged = get_query_var('paged') ? (int) get_query_var('paged') : 1;
            $pagenum_link = html_entity_decode(get_pagenum_link());
            $query_args = [];
            $url_parts = explode('?', $pagenum_link);

            if(isset($url_parts[1]))
            {
                wp_parse_str($url_parts[1], $query_args);
            }

            $pagenum_link = remove_query_arg(array_keys($query_args), $pagenum_link);
            $pagenum_link = trailingslashit($pagenum_link).'%_%';

            $format = $wp_rewrite->using_index_permalinks() && ! strpos($pagenum_link, 'index.php') ? 'index.php/' : '';
            $format .= $wp_rewrite->using_permalinks() ? user_trailingslashit($wp_rewrite->pagination_base.'/%#%', 'paged') : '?paged=%#%';

            // Set up paginated links.
            $links = paginate_links([
                                        'base' => $pagenum_link,
                                        'format' => $format,
                                        'total' => $wp_query->max_num_pages,
                                        'current' => $paged,
                                        'mid_size' => 1,
                                        'add_args' => array_map('urlencode', $query_args),
                                        'prev_text' => __('&larr; Previous', 'twentyfourteen'),
                                        'next_text' => __('Next &rarr;', 'twentyfourteen'),
                                    ]);

            if($links) :

                ?>
                <nav class="navigation paging-navigation">
                    <h1 class="screen-reader-text">
                        <?php
                            /* translators: Hidden accessibility text. */
                            _e('Posts navigation', 'twentyfourteen');
                        ?>
                    </h1>
                    <div class="pagination loop-pagination">
                        <?php echo $links; ?>
                    </div><!-- .pagination -->
                </nav><!-- .navigation -->
            <?php
            endif;
        }
    endif;

    if(! function_exists('twentyfourteen_post_nav')) :

        function twentyfourteen_post_nav()
        {
            // Don't print empty markup if there's nowhere to navigate.
            $previous = (is_attachment()) ? get_post(get_post()->post_parent) : get_adjacent_post(false, '', true);
            $next = get_adjacent_post(false, '', false);

            if(! $next && ! $previous)
            {
                return;
            }

            ?>
            <nav class="navigation post-navigation">
                <h1 class="screen-reader-text">
                    <?php
                        /* translators: Hidden accessibility text. */
                        _e('Post navigation', 'twentyfourteen');
                    ?>
                </h1>
                <div class="nav-links">
                    <?php
                        if(is_attachment()) :
                            previous_post_link('%link', __('<span class="meta-nav">Published In</span>%title', 'twentyfourteen'));
                        else :
                            previous_post_link('%link', __('<span class="meta-nav">Previous Post</span>%title', 'twentyfourteen'));
                            next_post_link('%link', __('<span class="meta-nav">Next Post</span>%title', 'twentyfourteen'));
                        endif;
                    ?>
                </div><!-- .nav-links -->
            </nav><!-- .navigation -->
            <?php
        }
    endif;

    if(! function_exists('twentyfourteen_posted_on')) :

        function twentyfourteen_posted_on()
        {
            if(is_sticky() && is_home() && ! is_paged())
            {
                echo '<span class="featured-post">'.__('Sticky', 'twentyfourteen').'</span>';
            }

            // Set up and print post meta information.
            printf('<span class="entry-date"><a href="%1$s" rel="bookmark"><time class="entry-date" datetime="%2$s">%3$s</time></a></span> <span class="byline"><span class="author vcard"><a class="url fn n" href="%4$s" rel="author">%5$s</a></span></span>', esc_url(get_permalink()), esc_attr(get_the_date('c')), esc_html(get_the_date()), esc_url(get_author_posts_url(get_the_author_meta('ID'))), get_the_author());
        }
    endif;

    function twentyfourteen_categorized_blog()
    {
        $all_the_cool_cats = get_transient('twentyfourteen_category_count');
        if(false === $all_the_cool_cats)
        {
            // Create an array of all the categories that are attached to posts.
            $all_the_cool_cats = get_categories([
                                                    'hide_empty' => 1,
                                                ]);

            // Count the number of categories that are attached to the posts.
            $all_the_cool_cats = count($all_the_cool_cats);

            set_transient('twentyfourteen_category_count', $all_the_cool_cats);
        }

        if($all_the_cool_cats > 1 || is_preview())
        {
            // This blog has more than 1 category so twentyfourteen_categorized_blog() should return true.
            return true;
        }
        else
        {
            // This blog has only 1 category so twentyfourteen_categorized_blog() should return false.
            return false;
        }
    }

    function twentyfourteen_category_transient_flusher()
    {
        // Like, beat it. Dig?
        delete_transient('twentyfourteen_category_count');
    }

    add_action('edit_category', 'twentyfourteen_category_transient_flusher');
    add_action('save_post', 'twentyfourteen_category_transient_flusher');

    if(! function_exists('twentyfourteen_post_thumbnail')) :

        function twentyfourteen_post_thumbnail()
        {
            if(post_password_required() || is_attachment() || ! has_post_thumbnail())
            {
                return;
            }

            if(is_singular()) :
                ?>

                <div class="post-thumbnail">
                    <?php
                        if((! is_active_sidebar('sidebar-2') || is_page_template('page-templates/full-width.php')))
                        {
                            the_post_thumbnail('twentyfourteen-full-width');
                        }
                        else
                        {
                            the_post_thumbnail();
                        }
                    ?>
                </div>

            <?php else : ?>

                <a class="post-thumbnail" href="<?php the_permalink(); ?>" aria-hidden="true">
                    <?php
                        if((! is_active_sidebar('sidebar-2') || is_page_template('page-templates/full-width.php')))
                        {
                            the_post_thumbnail('twentyfourteen-full-width');
                        }
                        else
                        {
                            the_post_thumbnail('post-thumbnail', ['alt' => get_the_title()]);
                        }
                    ?>
                </a>

            <?php
            endif; // End is_singular().
        }
    endif;

    if(! function_exists('twentyfourteen_excerpt_more') && ! is_admin()) :

        function twentyfourteen_excerpt_more($more)
        {
            $link = sprintf('<a href="%1$s" class="more-link">%2$s</a>', esc_url(get_permalink(get_the_ID())), /* translators: %s: Post title. Only visible to screen readers. */ sprintf(__('Continue reading %s <span class="meta-nav">&rarr;</span>', 'twentyfourteen'), '<span class="screen-reader-text">'.get_the_title(get_the_ID()).'</span>'));

            return ' &hellip; '.$link;
        }

        add_filter('excerpt_more', 'twentyfourteen_excerpt_more');
    endif;

    if(! function_exists('wp_body_open')) :

        function wp_body_open()
        {
            do_action('wp_body_open');
        }
    endif;
