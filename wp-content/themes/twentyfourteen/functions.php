<?php

    if(! isset($content_width))
    {
        $content_width = 474;
    }

    if(version_compare($GLOBALS['wp_version'], '3.6', '<'))
    {
        require get_template_directory().'/inc/back-compat.php';
    }

    if(! function_exists('twentyfourteen_setup')) :

        function twentyfourteen_setup()
        {
            /*
             * Make Twenty Fourteen available for translation.
             *
             * Translations can be filed at WordPress.org. See: https://translate.wordpress.org/projects/wp-themes/twentyfourteen
             * If you're building a theme based on Twenty Fourteen, use a find and
             * replace to change 'twentyfourteen' to the name of your theme in all
             * template files.
             *
             * Manual loading of text domain is not required after the introduction of
             * just in time translation loading in WordPress version 4.6.
             *
             * @ticket 58318
             */
            if(version_compare($GLOBALS['wp_version'], '4.6', '<'))
            {
                load_theme_textdomain('twentyfourteen');
            }

            /*
             * This theme styles the visual editor to resemble the theme style.
             * When fonts are self-hosted, the theme directory needs to be removed first.
             */
            $font_stylesheet = str_replace([
                                               get_template_directory_uri().'/',
                                               get_stylesheet_directory_uri().'/',
                                           ], '', twentyfourteen_font_url());
            add_editor_style(['css/editor-style.css', $font_stylesheet, 'genericons/genericons.css']);

            // Load regular editor styles into the new block-based editor.
            add_theme_support('editor-styles');

            // Load default block styles.
            add_theme_support('wp-block-styles');

            // Add support for responsive embeds.
            add_theme_support('responsive-embeds');

            // Add support for custom color scheme.
            add_theme_support('editor-color-palette', [
                [
                    'name' => __('Green', 'twentyfourteen'),
                    'slug' => 'green',
                    'color' => '#24890d',
                ],
                [
                    'name' => __('Black', 'twentyfourteen'),
                    'slug' => 'black',
                    'color' => '#000',
                ],
                [
                    'name' => __('Dark Gray', 'twentyfourteen'),
                    'slug' => 'dark-gray',
                    'color' => '#2b2b2b',
                ],
                [
                    'name' => __('Medium Gray', 'twentyfourteen'),
                    'slug' => 'medium-gray',
                    'color' => '#767676',
                ],
                [
                    'name' => __('Light Gray', 'twentyfourteen'),
                    'slug' => 'light-gray',
                    'color' => '#f5f5f5',
                ],
                [
                    'name' => __('White', 'twentyfourteen'),
                    'slug' => 'white',
                    'color' => '#fff',
                ],
            ]);

            // Add RSS feed links to <head> for posts and comments.
            add_theme_support('automatic-feed-links');

            // Enable support for Post Thumbnails, and declare two sizes.
            add_theme_support('post-thumbnails');
            set_post_thumbnail_size(672, 372, true);
            add_image_size('twentyfourteen-full-width', 1038, 576, true);

            // This theme uses wp_nav_menu() in two locations.
            register_nav_menus([
                                   'primary' => __('Top primary menu', 'twentyfourteen'),
                                   'secondary' => __('Secondary menu in left sidebar', 'twentyfourteen'),
                               ]);

            /*
             * Switch default core markup for search form, comment form, and comments
             * to output valid HTML5.
             */
            add_theme_support('html5', [
                'search-form',
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
                'script',
                'style',
                'navigation-widgets',
            ]);

            /*
             * Enable support for Post Formats.
             * See https://wordpress.org/documentation/article/post-formats/
             */
            add_theme_support('post-formats', [
                'aside',
                'image',
                'video',
                'audio',
                'quote',
                'link',
                'gallery',
            ]);

            // This theme allows users to set a custom background.
            add_theme_support(
                'custom-background', apply_filters('twentyfourteen_custom_background_args', [
                                       'default-color' => 'f5f5f5',
                                   ])
            );

            // Add support for featured content.
            add_theme_support('featured-content', [
                'featured_content_filter' => 'twentyfourteen_get_featured_posts',
                'max_posts' => 6,
            ]);

            // This theme uses its own gallery styles.
            add_filter('use_default_gallery_style', '__return_false');

            // Indicate widget sidebars can use selective refresh in the Customizer.
            add_theme_support('customize-selective-refresh-widgets');
        }
    endif; // twentyfourteen_setup()
    add_action('after_setup_theme', 'twentyfourteen_setup');

    function twentyfourteen_content_width()
    {
        if(is_attachment() && wp_attachment_is_image())
        {
            $GLOBALS['content_width'] = 810;
        }
    }

    add_action('template_redirect', 'twentyfourteen_content_width');

    function twentyfourteen_get_featured_posts()
    {
        return apply_filters('twentyfourteen_get_featured_posts', []);
    }

    function twentyfourteen_has_featured_posts()
    {
        return ! is_paged() && (bool) twentyfourteen_get_featured_posts();
    }

    function twentyfourteen_widgets_init()
    {
        require get_template_directory().'/inc/widgets.php';
        register_widget('Twenty_Fourteen_Ephemera_Widget');

        register_sidebar([
                             'name' => __('Primary Sidebar', 'twentyfourteen'),
                             'id' => 'sidebar-1',
                             'description' => __('Main sidebar that appears on the left.', 'twentyfourteen'),
                             'before_widget' => '<aside id="%1$s" class="widget %2$s">',
                             'after_widget' => '</aside>',
                             'before_title' => '<h1 class="widget-title">',
                             'after_title' => '</h1>',
                         ]);
        register_sidebar([
                             'name' => __('Content Sidebar', 'twentyfourteen'),
                             'id' => 'sidebar-2',
                             'description' => __('Additional sidebar that appears on the right.', 'twentyfourteen'),
                             'before_widget' => '<aside id="%1$s" class="widget %2$s">',
                             'after_widget' => '</aside>',
                             'before_title' => '<h1 class="widget-title">',
                             'after_title' => '</h1>',
                         ]);
        register_sidebar([
                             'name' => __('Footer Widget Area', 'twentyfourteen'),
                             'id' => 'sidebar-3',
                             'description' => __('Appears in the footer section of the site.', 'twentyfourteen'),
                             'before_widget' => '<aside id="%1$s" class="widget %2$s">',
                             'after_widget' => '</aside>',
                             'before_title' => '<h1 class="widget-title">',
                             'after_title' => '</h1>',
                         ]);
    }

    add_action('widgets_init', 'twentyfourteen_widgets_init');

    if(! function_exists('twentyfourteen_font_url')) :

        function twentyfourteen_font_url()
        {
            $font_url = '';
            /*
             * translators: If there are characters in your language that are not supported
             * by Lato, translate this to 'off'. Do not translate into your own language.
             */
            if('off' !== _x('on', 'Lato font: on or off', 'twentyfourteen'))
            {
                $font_url = get_template_directory_uri().'/fonts/font-lato.css';
            }

            return $font_url;
        }
    endif;

    function twentyfourteen_scripts()
    {
        // Add Lato font, used in the main stylesheet.
        $font_version = (0 === strpos((string) twentyfourteen_font_url(), get_template_directory_uri().'/')) ? '20230328' : null;
        wp_enqueue_style('twentyfourteen-lato', twentyfourteen_font_url(), [], $font_version);

        // Add Genericons font, used in the main stylesheet.
        wp_enqueue_style('genericons', get_template_directory_uri().'/genericons/genericons.css', [], '3.0.3');

        // Load our main stylesheet.
        wp_enqueue_style('twentyfourteen-style', get_stylesheet_uri(), [], '20230808');

        // Theme block stylesheet.
        wp_enqueue_style('twentyfourteen-block-style', get_template_directory_uri().'/css/blocks.css', ['twentyfourteen-style'], '20230630');

        // Load the Internet Explorer specific stylesheet.
        wp_enqueue_style('twentyfourteen-ie', get_template_directory_uri().'/css/ie.css', ['twentyfourteen-style'], '20140711');
        wp_style_add_data('twentyfourteen-ie', 'conditional', 'lt IE 9');

        if(is_singular() && comments_open() && get_option('thread_comments'))
        {
            wp_enqueue_script('comment-reply');
        }

        if(is_singular() && wp_attachment_is_image())
        {
            wp_enqueue_script('twentyfourteen-keyboard-image-navigation', get_template_directory_uri().'/js/keyboard-image-navigation.js', ['jquery'], '20150120');
        }

        if(is_active_sidebar('sidebar-3'))
        {
            wp_enqueue_script('jquery-masonry');
        }

        if(is_front_page() && 'slider' === get_theme_mod('featured_content_layout'))
        {
            wp_enqueue_script('twentyfourteen-slider', get_template_directory_uri().'/js/slider.js', ['jquery'], '20150120', [
                'in_footer' => false, // Because involves header.
                'strategy' => 'defer',
            ]);
            wp_localize_script('twentyfourteen-slider', 'featuredSliderDefaults', [
                'prevText' => __('Previous', 'twentyfourteen'),
                'nextText' => __('Next', 'twentyfourteen'),
            ]);
        }

        wp_enqueue_script('twentyfourteen-script', get_template_directory_uri().'/js/functions.js', ['jquery'], '20230526', [
            'in_footer' => false, // Because involves header.
            'strategy' => 'defer',
        ]);
    }

    add_action('wp_enqueue_scripts', 'twentyfourteen_scripts');

    function twentyfourteen_admin_fonts()
    {
        $font_version = (0 === strpos((string) twentyfourteen_font_url(), get_template_directory_uri().'/')) ? '20230328' : null;
        wp_enqueue_style('twentyfourteen-lato', twentyfourteen_font_url(), [], $font_version);
    }

    add_action('admin_print_scripts-appearance_page_custom-header', 'twentyfourteen_admin_fonts');

    function twentyfourteen_resource_hints($urls, $relation_type)
    {
        if(wp_style_is('twentyfourteen-lato', 'queue') && 'preconnect' === $relation_type)
        {
            if(version_compare($GLOBALS['wp_version'], '4.7-alpha', '>='))
            {
                $urls[] = [
                    'href' => 'https://fonts.gstatic.com',
                    'crossorigin',
                ];
            }
            else
            {
                $urls[] = 'https://fonts.gstatic.com';
            }
        }

        return $urls;
    }

// add_filter( 'wp_resource_hints', 'twentyfourteen_resource_hints', 10, 2 );

    function twentyfourteen_block_editor_styles()
    {
        // Block styles.
        wp_enqueue_style('twentyfourteen-block-editor-style', get_template_directory_uri().'/css/editor-blocks.css', [], '20230623');
        // Add custom fonts.
        $font_version = (0 === strpos((string) twentyfourteen_font_url(), get_template_directory_uri().'/')) ? '20230328' : null;
        wp_enqueue_style('twentyfourteen-fonts', twentyfourteen_font_url(), [], $font_version);
    }

    add_action('enqueue_block_editor_assets', 'twentyfourteen_block_editor_styles');

    if(! function_exists('twentyfourteen_the_attached_image')) :

        function twentyfourteen_the_attached_image()
        {
            $post = get_post();

            $attachment_size = apply_filters('twentyfourteen_attachment_size', [810, 810]);
            $next_attachment_url = wp_get_attachment_url();

            /*
             * Grab the IDs of all the image attachments in a gallery so we can get the URL
             * of the next adjacent image in a gallery, or the first image (if we're
             * looking at the last image in a gallery), or, in a gallery of one, just the
             * link to that image file.
             */
            $attachment_ids = get_posts([
                                            'post_parent' => $post->post_parent,
                                            'fields' => 'ids',
                                            'numberposts' => -1,
                                            'post_status' => 'inherit',
                                            'post_type' => 'attachment',
                                            'post_mime_type' => 'image',
                                            'order' => 'ASC',
                                            'orderby' => 'menu_order ID',
                                        ]);

            // If there is more than 1 attachment in a gallery...
            if(count($attachment_ids) > 1)
            {
                foreach($attachment_ids as $idx => $attachment_id)
                {
                    if($attachment_id === $post->ID)
                    {
                        $next_id = $attachment_ids[($idx + 1) % count($attachment_ids)];
                        break;
                    }
                }

                if($next_id)
                {
                    // ...get the URL of the next image attachment.
                    $next_attachment_url = get_attachment_link($next_id);
                }
                else
                {
                    // ...or get the URL of the first image attachment.
                    $next_attachment_url = get_attachment_link(reset($attachment_ids));
                }
            }

            printf('<a href="%1$s" rel="attachment">%2$s</a>', esc_url($next_attachment_url), wp_get_attachment_image($post->ID, $attachment_size));
        }
    endif;

    if(! function_exists('twentyfourteen_list_authors')) :

        function twentyfourteen_list_authors()
        {
            $args = [
                'fields' => 'ID',
                'orderby' => 'post_count',
                'order' => 'DESC',
                'capability' => ['edit_posts'],
            ];

            // Capability queries were only introduced in WP 5.9.
            if(version_compare($GLOBALS['wp_version'], '5.9-alpha', '<'))
            {
                $args['who'] = 'authors';
                unset($args['capability']);
            }

            $args = apply_filters('twentyfourteen_list_authors_query_args', $args);

            $contributor_ids = get_users($args);

            foreach($contributor_ids as $contributor_id) :
                $post_count = count_user_posts($contributor_id);

                // Move on if user has not published a post (yet).
                if(! $post_count)
                {
                    continue;
                }
                ?>

                <div class="contributor">
                    <div class="contributor-info">
                        <div class="contributor-avatar"><?php echo get_avatar($contributor_id, 132); ?></div>
                        <div class="contributor-summary">
                            <h2 class="contributor-name"><?php echo get_the_author_meta('display_name', $contributor_id); ?></h2>
                            <p class="contributor-bio">
                                <?php echo get_the_author_meta('description', $contributor_id); ?>
                            </p>
                            <a class="button contributor-posts-link"
                               href="<?php echo esc_url(get_author_posts_url($contributor_id)); ?>">
                                <?php
                                    /* translators: %d: Post count. */
                                    printf(_n('%d Article', '%d Articles', $post_count, 'twentyfourteen'), $post_count);
                                ?>
                            </a>
                        </div><!-- .contributor-summary -->
                    </div><!-- .contributor-info -->
                </div><!-- .contributor -->

            <?php
            endforeach;
        }
    endif;

    function twentyfourteen_body_classes($classes)
    {
        if(is_multi_author())
        {
            $classes[] = 'group-blog';
        }

        if(get_header_image())
        {
            $classes[] = 'header-image';
        }
        elseif(! in_array($GLOBALS['pagenow'], ['wp-activate.php', 'wp-signup.php'], true))
        {
            $classes[] = 'masthead-fixed';
        }

        if(is_archive() || is_search() || is_home())
        {
            $classes[] = 'list-view';
        }

        if((! is_active_sidebar('sidebar-2')) || is_page_template('page-templates/full-width.php') || is_page_template('page-templates/contributors.php') || is_attachment())
        {
            $classes[] = 'full-width';
        }

        if(is_active_sidebar('sidebar-3'))
        {
            $classes[] = 'footer-widgets';
        }

        if(is_singular() && ! is_front_page())
        {
            $classes[] = 'singular';
        }

        if(is_front_page() && 'slider' === get_theme_mod('featured_content_layout'))
        {
            $classes[] = 'slider';
        }
        elseif(is_front_page())
        {
            $classes[] = 'grid';
        }

        return $classes;
    }

    add_filter('body_class', 'twentyfourteen_body_classes');

    function twentyfourteen_post_classes($classes)
    {
        if(! post_password_required() && ! is_attachment() && has_post_thumbnail())
        {
            $classes[] = 'has-post-thumbnail';
        }

        return $classes;
    }

    add_filter('post_class', 'twentyfourteen_post_classes');

    function twentyfourteen_wp_title($title, $sep)
    {
        global $paged, $page;

        if(is_feed())
        {
            return $title;
        }

        // Add the site name.
        $title .= get_bloginfo('name', 'display');

        // Add the site description for the home/front page.
        $site_description = get_bloginfo('description', 'display');
        if($site_description && (is_home() || is_front_page()))
        {
            $title = "$title $sep $site_description";
        }

        // Add a page number if necessary.
        if(($paged >= 2 || $page >= 2) && ! is_404())
        {
            /* translators: %s: Page number. */
            $title = "$title $sep ".sprintf(__('Page %s', 'twentyfourteen'), max($paged, $page));
        }

        return $title;
    }

    add_filter('wp_title', 'twentyfourteen_wp_title', 10, 2);

    function twentyfourteen_widget_tag_cloud_args($args)
    {
        $args['largest'] = 22;
        $args['smallest'] = 8;
        $args['unit'] = 'pt';
        $args['format'] = 'list';

        return $args;
    }

    add_filter('widget_tag_cloud_args', 'twentyfourteen_widget_tag_cloud_args');

// Implement Custom Header features.
    require get_template_directory().'/inc/custom-header.php';

// Custom template tags for this theme.
    require get_template_directory().'/inc/template-tags.php';

// Add Customizer functionality.
    require get_template_directory().'/inc/customizer.php';

// Add support for block patterns.
    require get_template_directory().'/inc/block-patterns.php';

    /*
     * Add Featured Content functionality.
     *
     * To overwrite in a plugin, define your own Featured_Content class on or
     * before the 'setup_theme' hook.
     */
    if(! class_exists('Featured_Content') && 'plugins.php' !== $GLOBALS['pagenow'])
    {
        require get_template_directory().'/inc/featured-content.php';
    }

    if(! function_exists('is_customize_preview')) :
        function is_customize_preview()
        {
            global $wp_customize;

            return ($wp_customize instanceof WP_Customize_Manager) && $wp_customize->is_preview();
        }
    endif;
