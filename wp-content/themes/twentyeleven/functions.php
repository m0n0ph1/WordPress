<?php

// Set the content width based on the theme's design and stylesheet.
    if(! isset($content_width))
    {
        $content_width = 584;
    }

    /*
     * Tell WordPress to run twentyeleven_setup() when the 'after_setup_theme' hook is run.
     */
    add_action('after_setup_theme', 'twentyeleven_setup');

    if(! function_exists('twentyeleven_setup')) :

        function twentyeleven_setup()
        {
            /*
             * Make Twenty Eleven available for translation.
             * Translations can be added to the /languages/ directory.
             * If you're building a theme based on Twenty Eleven, use
             * a find and replace to change 'twentyeleven' to the name
             * of your theme in all the template files.
             *
             * Manual loading of text domain is not required after the introduction of
             * just in time translation loading in WordPress version 4.6.
             *
             * @ticket 58318
             */
            if(version_compare($GLOBALS['wp_version'], '4.6', '<'))
            {
                load_theme_textdomain('twentyeleven', get_template_directory().'/languages');
            }

            // This theme styles the visual editor with editor-style.css to match the theme style.
            add_editor_style();

            // Load regular editor styles into the new block-based editor.
            add_theme_support('editor-styles');

            // Load default block styles.
            add_theme_support('wp-block-styles');

            // Add support for responsive embeds.
            add_theme_support('responsive-embeds');

            // Add support for custom color scheme.
            add_theme_support('editor-color-palette', [
                [
                    'name' => __('Blue', 'twentyeleven'),
                    'slug' => 'blue',
                    'color' => '#1982d1',
                ],
                [
                    'name' => __('Black', 'twentyeleven'),
                    'slug' => 'black',
                    'color' => '#000',
                ],
                [
                    'name' => __('Dark Gray', 'twentyeleven'),
                    'slug' => 'dark-gray',
                    'color' => '#373737',
                ],
                [
                    'name' => __('Medium Gray', 'twentyeleven'),
                    'slug' => 'medium-gray',
                    'color' => '#666',
                ],
                [
                    'name' => __('Light Gray', 'twentyeleven'),
                    'slug' => 'light-gray',
                    'color' => '#e2e2e2',
                ],
                [
                    'name' => __('White', 'twentyeleven'),
                    'slug' => 'white',
                    'color' => '#fff',
                ],
            ]);

            // Load up our theme options page and related code.
            require get_template_directory().'/inc/theme-options.php';

            // Grab Twenty Eleven's Ephemera widget.
            require get_template_directory().'/inc/widgets.php';

            // Load block patterns.
            require get_template_directory().'/inc/block-patterns.php';

            // Add default posts and comments RSS feed links to <head>.
            add_theme_support('automatic-feed-links');

            // This theme uses wp_nav_menu() in one location.
            register_nav_menu('primary', __('Primary Menu', 'twentyeleven'));

            // Add support for a variety of post formats.
            add_theme_support('post-formats', ['aside', 'link', 'gallery', 'status', 'quote', 'image']);

            $theme_options = twentyeleven_get_theme_options();
            if('dark' === $theme_options['color_scheme'])
            {
                $default_background_color = '1d1d1d';
            }
            else
            {
                $default_background_color = 'e2e2e2';
            }

            // Add support for custom backgrounds.
            add_theme_support('custom-background', [
                /*
                * Let WordPress know what our default background color is.
                * This is dependent on our current color scheme.
                */ 'default-color' => $default_background_color,
            ]);

            // This theme uses Featured Images (also known as post thumbnails) for per-post/per-page Custom Header images.
            add_theme_support('post-thumbnails');

            // Add support for custom headers.
            $custom_header_support = [
                // The default header text color.
                'default-text-color' => '000',
                // The height and width of our custom header.

                'width' => apply_filters('twentyeleven_header_image_width', 1000),

                'height' => apply_filters('twentyeleven_header_image_height', 288),
                // Support flexible heights.
                'flex-height' => true,
                // Random image rotation by default.
                'random-default' => true,
                // Callback for styling the header.
                'wp-head-callback' => 'twentyeleven_header_style',
                // Callback for styling the header preview in the admin.
                'admin-head-callback' => 'twentyeleven_admin_header_style',
                // Callback used to display the header preview in the admin.
                'admin-preview-callback' => 'twentyeleven_admin_header_image',
            ];

            add_theme_support('custom-header', $custom_header_support);

            if(! function_exists('get_custom_header'))
            {
                // This is all for compatibility with versions of WordPress prior to 3.4.
                define('HEADER_TEXTCOLOR', $custom_header_support['default-text-color']);
                define('HEADER_IMAGE', '');
                define('HEADER_IMAGE_WIDTH', $custom_header_support['width']);
                define('HEADER_IMAGE_HEIGHT', $custom_header_support['height']);
                add_custom_image_header($custom_header_support['wp-head-callback'], $custom_header_support['admin-head-callback'], $custom_header_support['admin-preview-callback']);
                add_custom_background();
            }

            /*
             * We'll be using post thumbnails for custom header images on posts and pages.
             * We want them to be the size of the header image that we just defined.
             * Larger images will be auto-cropped to fit, smaller ones will be ignored. See header.php.
             */
            set_post_thumbnail_size($custom_header_support['width'], $custom_header_support['height'], true);

            /*
             * Add Twenty Eleven's custom image sizes.
             * Used for large feature (header) images.
             */
            add_image_size('large-feature', $custom_header_support['width'], $custom_header_support['height'], true);
            // Used for featured posts if a large-feature doesn't exist.
            add_image_size('small-feature', 500, 300);

            // Default custom headers packaged with the theme. %s is a placeholder for the theme template directory URI.
            register_default_headers([
                                         'wheel' => [
                                             'url' => '%s/images/headers/wheel.jpg',
                                             'thumbnail_url' => '%s/images/headers/wheel-thumbnail.jpg',
                                             /* translators: Header image description. */
                                             'description' => __('Wheel', 'twentyeleven'),
                                         ],
                                         'shore' => [
                                             'url' => '%s/images/headers/shore.jpg',
                                             'thumbnail_url' => '%s/images/headers/shore-thumbnail.jpg',
                                             /* translators: Header image description. */
                                             'description' => __('Shore', 'twentyeleven'),
                                         ],
                                         'trolley' => [
                                             'url' => '%s/images/headers/trolley.jpg',
                                             'thumbnail_url' => '%s/images/headers/trolley-thumbnail.jpg',
                                             /* translators: Header image description. */
                                             'description' => __('Trolley', 'twentyeleven'),
                                         ],
                                         'pine-cone' => [
                                             'url' => '%s/images/headers/pine-cone.jpg',
                                             'thumbnail_url' => '%s/images/headers/pine-cone-thumbnail.jpg',
                                             /* translators: Header image description. */
                                             'description' => __('Pine Cone', 'twentyeleven'),
                                         ],
                                         'chessboard' => [
                                             'url' => '%s/images/headers/chessboard.jpg',
                                             'thumbnail_url' => '%s/images/headers/chessboard-thumbnail.jpg',
                                             /* translators: Header image description. */
                                             'description' => __('Chessboard', 'twentyeleven'),
                                         ],
                                         'lanterns' => [
                                             'url' => '%s/images/headers/lanterns.jpg',
                                             'thumbnail_url' => '%s/images/headers/lanterns-thumbnail.jpg',
                                             /* translators: Header image description. */
                                             'description' => __('Lanterns', 'twentyeleven'),
                                         ],
                                         'willow' => [
                                             'url' => '%s/images/headers/willow.jpg',
                                             'thumbnail_url' => '%s/images/headers/willow-thumbnail.jpg',
                                             /* translators: Header image description. */
                                             'description' => __('Willow', 'twentyeleven'),
                                         ],
                                         'hanoi' => [
                                             'url' => '%s/images/headers/hanoi.jpg',
                                             'thumbnail_url' => '%s/images/headers/hanoi-thumbnail.jpg',
                                             /* translators: Header image description. */
                                             'description' => __('Hanoi Plant', 'twentyeleven'),
                                         ],
                                     ]);

            // Indicate widget sidebars can use selective refresh in the Customizer.
            add_theme_support('customize-selective-refresh-widgets');
        }
    endif; // twentyeleven_setup()

    function twentyeleven_scripts_styles()
    {
        // Theme block stylesheet.
        wp_enqueue_style('twentyeleven-block-style', get_template_directory_uri().'/blocks.css', [], '20230122');
    }

    add_action('wp_enqueue_scripts', 'twentyeleven_scripts_styles');

    function twentyeleven_block_editor_styles()
    {
        // Block styles.
        wp_enqueue_style('twentyeleven-block-editor-style', get_template_directory_uri().'/editor-blocks.css', [], '20220927');
    }

    add_action('enqueue_block_editor_assets', 'twentyeleven_block_editor_styles');

    if(! function_exists('twentyeleven_header_style')) :

        function twentyeleven_header_style()
        {
            $text_color = get_header_textcolor();

            // If no custom options for text are set, let's bail.
            if(HEADER_TEXTCOLOR === $text_color)
            {
                return;
            }

            // If we get this far, we have custom styles. Let's do this.
            ?>
            <style type="text/css" id="twentyeleven-header-css">
                <?php
                // Has the text been hidden?
                if ( 'blank' === $text_color ) :
                    ?>
                #site-title,
                #site-description {
                    position: absolute;
                    clip: rect(1px 1px 1px 1px); /* IE6, IE7 */
                    clip: rect(1px, 1px, 1px, 1px);
                }

                <?php
                // If the user has set a custom color for the text, use that.
            else :
                ?>
                #site-title a,
                #site-description {
                    color: #<?php echo $text_color; ?>;
                }

                <?php endif; ?>
            </style>
            <?php
        }
    endif; // twentyeleven_header_style()

    if(! function_exists('twentyeleven_admin_header_style')) :

        function twentyeleven_admin_header_style()
        {
            ?>
            <style type="text/css" id="twentyeleven-admin-header-css">
                .appearance_page_custom-header #headimg {
                    border: none;
                }

                #headimg h1,
                #desc {
                    font-family: "Helvetica Neue", Arial, Helvetica, "Nimbus Sans L", sans-serif;
                }

                #headimg h1 {
                    margin: 0;
                }

                #headimg h1 a {
                    font-size: 32px;
                    line-height: 36px;
                    text-decoration: none;
                }

                #desc {
                    font-size: 14px;
                    line-height: 23px;
                    padding: 0 0 3em;
                }

                <?php
                // If the user has set a custom color for the text, use that.
                if ( get_header_textcolor() !== HEADER_TEXTCOLOR ) :
                    ?>
                #site-title a,
                #site-description {
                    color: #<?php echo get_header_textcolor(); ?>;
                }

                <?php endif; ?>
                #headimg img {
                    max-width: 1000px;
                    height: auto;
                    width: 100%;
                }
            </style>
            <?php
        }
    endif; // twentyeleven_admin_header_style()

    if(! function_exists('twentyeleven_admin_header_image')) :

        function twentyeleven_admin_header_image()
        {
            ?>
            <div id="headimg">
                <?php
                    $color = get_header_textcolor();
                    $image = get_header_image();
                    $style = 'display: none;';
                    if($color && 'blank' !== $color)
                    {
                        $style = 'color: #'.$color.';';
                    }
                ?>
                <h1 class="displaying-header-text"><a id="name"
                                                      style="<?php echo esc_attr($style); ?>"
                                                      onclick="return false;"
                                                      href="<?php echo esc_url(home_url('/')); ?>"
                                                      tabindex="-1"><?php bloginfo('name'); ?></a></h1>
                <div id="desc"
                     class="displaying-header-text"
                     style="<?php echo esc_attr($style); ?>"><?php bloginfo('description'); ?></div>
                <?php if($image) : ?>
                    <img src="<?php echo esc_url($image); ?>" alt=""/>
                <?php endif; ?>
            </div>
            <?php
        }
    endif; // twentyeleven_admin_header_image()

    if(! function_exists('twentyeleven_header_image')) :

        function twentyeleven_header_image()
        {
            $attrs = [
                'alt' => get_bloginfo('name', 'display'),
            ];

            // Compatibility with versions of WordPress prior to 3.4.
            if(function_exists('get_custom_header'))
            {
                $custom_header = get_custom_header();
                $attrs['width'] = $custom_header->width;
                $attrs['height'] = $custom_header->height;
            }
            else
            {
                $attrs['width'] = HEADER_IMAGE_WIDTH;
                $attrs['height'] = HEADER_IMAGE_HEIGHT;
            }

            if(function_exists('the_header_image_tag'))
            {
                the_header_image_tag($attrs);

                return;
            }

            ?>
            <img src="<?php header_image(); ?>"
                 width="<?php echo esc_attr($attrs['width']); ?>"
                 height="<?php echo esc_attr($attrs['height']); ?>"
                 alt="<?php echo esc_attr($attrs['alt']); ?>"/>
            <?php
        }
    endif; // twentyeleven_header_image()

    function twentyeleven_excerpt_length($length)
    {
        return 40;
    }

    add_filter('excerpt_length', 'twentyeleven_excerpt_length');

    if(! function_exists('twentyeleven_continue_reading_link')) :

        function twentyeleven_continue_reading_link()
        {
            return ' <a href="'.esc_url(get_permalink()).'">'.__('Continue reading <span class="meta-nav">&rarr;</span>', 'twentyeleven').'</a>';
        }
    endif; // twentyeleven_continue_reading_link()

    function twentyeleven_auto_excerpt_more($more)
    {
        if(! is_admin())
        {
            return ' &hellip;'.twentyeleven_continue_reading_link();
        }

        return $more;
    }

    add_filter('excerpt_more', 'twentyeleven_auto_excerpt_more');

    function twentyeleven_custom_excerpt_more($output)
    {
        if(has_excerpt() && ! is_attachment() && ! is_admin())
        {
            $output .= twentyeleven_continue_reading_link();
        }

        return $output;
    }

    add_filter('get_the_excerpt', 'twentyeleven_custom_excerpt_more');

    function twentyeleven_page_menu_args($args)
    {
        if(! isset($args['show_home']))
        {
            $args['show_home'] = true;
        }

        return $args;
    }

    add_filter('wp_page_menu_args', 'twentyeleven_page_menu_args');

    function twentyeleven_widgets_init()
    {
        register_widget('Twenty_Eleven_Ephemera_Widget');

        register_sidebar([
                             'name' => __('Main Sidebar', 'twentyeleven'),
                             'id' => 'sidebar-1',
                             'before_widget' => '<aside id="%1$s" class="widget %2$s">',
                             'after_widget' => '</aside>',
                             'before_title' => '<h3 class="widget-title">',
                             'after_title' => '</h3>',
                         ]);

        register_sidebar([
                             'name' => __('Showcase Sidebar', 'twentyeleven'),
                             'id' => 'sidebar-2',
                             'description' => __('The sidebar for the optional Showcase Template', 'twentyeleven'),
                             'before_widget' => '<aside id="%1$s" class="widget %2$s">',
                             'after_widget' => '</aside>',
                             'before_title' => '<h3 class="widget-title">',
                             'after_title' => '</h3>',
                         ]);

        register_sidebar([
                             'name' => __('Footer Area One', 'twentyeleven'),
                             'id' => 'sidebar-3',
                             'description' => __('An optional widget area for your site footer', 'twentyeleven'),
                             'before_widget' => '<aside id="%1$s" class="widget %2$s">',
                             'after_widget' => '</aside>',
                             'before_title' => '<h3 class="widget-title">',
                             'after_title' => '</h3>',
                         ]);

        register_sidebar([
                             'name' => __('Footer Area Two', 'twentyeleven'),
                             'id' => 'sidebar-4',
                             'description' => __('An optional widget area for your site footer', 'twentyeleven'),
                             'before_widget' => '<aside id="%1$s" class="widget %2$s">',
                             'after_widget' => '</aside>',
                             'before_title' => '<h3 class="widget-title">',
                             'after_title' => '</h3>',
                         ]);

        register_sidebar([
                             'name' => __('Footer Area Three', 'twentyeleven'),
                             'id' => 'sidebar-5',
                             'description' => __('An optional widget area for your site footer', 'twentyeleven'),
                             'before_widget' => '<aside id="%1$s" class="widget %2$s">',
                             'after_widget' => '</aside>',
                             'before_title' => '<h3 class="widget-title">',
                             'after_title' => '</h3>',
                         ]);
    }

    add_action('widgets_init', 'twentyeleven_widgets_init');

    if(! function_exists('twentyeleven_content_nav')) :

        function twentyeleven_content_nav($html_id)
        {
            global $wp_query;

            if($wp_query->max_num_pages > 1) :
                ?>
                <nav id="<?php echo esc_attr($html_id); ?>">
                    <h3 class="assistive-text"><?php _e('Post navigation', 'twentyeleven'); ?></h3>
                    <div class="nav-previous"><?php next_posts_link(__('<span class="meta-nav">&larr;</span> Older posts', 'twentyeleven')); ?></div>
                    <div class="nav-next"><?php previous_posts_link(__('Newer posts <span class="meta-nav">&rarr;</span>', 'twentyeleven')); ?></div>
                </nav><!-- #nav-above -->
            <?php
            endif;
        }
    endif; // twentyeleven_content_nav()

    function twentyeleven_get_first_url()
    {
        $content = get_the_content();
        $has_url = function_exists('get_url_in_content') ? get_url_in_content($content) : false;

        if(! $has_url)
        {
            $has_url = twentyeleven_url_grabber();
        }

        return ($has_url) ? $has_url : apply_filters('the_permalink', get_permalink());
    }

    function twentyeleven_url_grabber()
    {
        if(! preg_match('/<a\s[^>]*?href=[\'"](.+?)[\'"]/is', get_the_content(), $matches))
        {
            return false;
        }

        return esc_url_raw($matches[1]);
    }

    function twentyeleven_footer_sidebar_class()
    {
        $count = 0;

        if(is_active_sidebar('sidebar-3'))
        {
            ++$count;
        }

        if(is_active_sidebar('sidebar-4'))
        {
            ++$count;
        }

        if(is_active_sidebar('sidebar-5'))
        {
            ++$count;
        }

        $class = '';

        switch($count)
        {
            case '1':
                $class = 'one';
                break;
            case '2':
                $class = 'two';
                break;
            case '3':
                $class = 'three';
                break;
        }

        if($class)
        {
            echo 'class="'.esc_attr($class).'"';
        }
    }

    if(! function_exists('twentyeleven_comment')) :

        function twentyeleven_comment($comment, $args, $depth)
        {
            $GLOBALS['comment'] = $comment;
            switch($comment->comment_type) :
                case 'pingback':
                case 'trackback':
                    ?>
                    <li class="post pingback">
                    <p><?php _e('Pingback:', 'twentyeleven'); ?><?php comment_author_link(); ?><?php edit_comment_link(__('Edit', 'twentyeleven'), '<span class="edit-link">', '</span>'); ?></p>
                    <?php
                    break;
                default:
                    ?>
                <li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>">
                    <article id="comment-<?php comment_ID(); ?>" class="comment">
                        <footer class="comment-meta">
                            <div class="comment-author vcard">
                                <?php
                                    $avatar_size = 68;

                                    if('0' !== $comment->comment_parent)
                                    {
                                        $avatar_size = 39;
                                    }

                                    echo get_avatar($comment, $avatar_size);

                                    printf(/* translators: 1: Comment author, 2: Date and time. */ __('%1$s on %2$s <span class="says">said:</span>', 'twentyeleven'), sprintf('<span class="fn">%s</span>', get_comment_author_link()), sprintf('<a href="%1$s"><time datetime="%2$s">%3$s</time></a>', esc_url(get_comment_link($comment->comment_ID)), get_comment_time('c'), /* translators: 1: Date, 2: Time. */ sprintf(__('%1$s at %2$s', 'twentyeleven'), get_comment_date(), get_comment_time())));
                                ?>

                                <?php edit_comment_link(__('Edit', 'twentyeleven'), '<span class="edit-link">', '</span>'); ?>
                            </div><!-- .comment-author .vcard -->

                            <?php
                                $commenter = wp_get_current_commenter();
                                if($commenter['comment_author_email'])
                                {
                                    $moderation_note = __('Your comment is awaiting moderation.', 'twentyeleven');
                                }
                                else
                                {
                                    $moderation_note = __('Your comment is awaiting moderation. This is a preview; your comment will be visible after it has been approved.', 'twentyeleven');
                                }
                            ?>

                            <?php if('0' === $comment->comment_approved) : ?>
                                <em class="comment-awaiting-moderation"><?php echo $moderation_note; ?></em>
                                <br/>
                            <?php endif; ?>

                        </footer>

                        <div class="comment-content"><?php comment_text(); ?></div>

                        <div class="reply">
                            <?php
                                comment_reply_link(
                                    array_merge($args, [
                                        'reply_text' => __('Reply <span>&darr;</span>', 'twentyeleven'),
                                        'depth' => $depth,
                                        'max_depth' => $args['max_depth'],
                                    ])
                                );
                            ?>
                        </div><!-- .reply -->
                    </article><!-- #comment-## -->

                    <?php
                    break;
            endswitch;
        }
    endif; // twentyeleven_comment()

    if(! function_exists('twentyeleven_posted_on')) :

        function twentyeleven_posted_on()
        {
            printf(/* translators: 1: The permalink, 2: Time, 3: Date and time, 4: Date and time, 5: Author posts, 6: Author post link text, 7: Author display name. */ __('<span class="sep">Posted on </span><a href="%1$s" title="%2$s" rel="bookmark"><time class="entry-date" datetime="%3$s">%4$s</time></a><span class="by-author"> <span class="sep"> by </span> <span class="author vcard"><a class="url fn n" href="%5$s" title="%6$s" rel="author">%7$s</a></span></span>', 'twentyeleven'), esc_url(get_permalink()), esc_attr(get_the_time()), esc_attr(get_the_date('c')), esc_html(get_the_date()), esc_url(get_author_posts_url(get_the_author_meta('ID'))), /* translators: %s: Author display name. */ esc_attr(sprintf(__('View all posts by %s', 'twentyeleven'), get_the_author())), get_the_author());
        }
    endif;

    function twentyeleven_body_classes($classes)
    {
        if(function_exists('is_multi_author') && ! is_multi_author())
        {
            $classes[] = 'single-author';
        }

        if(is_singular() && ! is_home() && ! is_page_template('showcase.php') && ! is_page_template('sidebar-page.php'))
        {
            $classes[] = 'singular';
        }

        return $classes;
    }

    add_filter('body_class', 'twentyeleven_body_classes');

    function twentyeleven_get_gallery_images()
    {
        $images = [];

        if(function_exists('get_post_galleries'))
        {
            $galleries = get_post_galleries(get_the_ID(), false);
            if(isset($galleries[0]['ids']))
            {
                $images = explode(',', $galleries[0]['ids']);
            }
        }
        else
        {
            $pattern = get_shortcode_regex();
            preg_match("/$pattern/s", get_the_content(), $match);
            $atts = shortcode_parse_atts($match[3]);
            if(isset($atts['ids']))
            {
                $images = explode(',', $atts['ids']);
            }
        }

        if(! $images)
        {
            $images = get_posts([
                                    'fields' => 'ids',
                                    'numberposts' => 999,
                                    'order' => 'ASC',
                                    'orderby' => 'menu_order',
                                    'post_mime_type' => 'image',
                                    'post_parent' => get_the_ID(),
                                    'post_type' => 'attachment',
                                ]);
        }

        return $images;
    }

    function twentyeleven_widget_tag_cloud_args($args)
    {
        $args['largest'] = 22;
        $args['smallest'] = 8;
        $args['unit'] = 'pt';
        $args['format'] = 'list';

        return $args;
    }

    add_filter('widget_tag_cloud_args', 'twentyeleven_widget_tag_cloud_args');

    if(! function_exists('wp_body_open')) :

        function wp_body_open()
        {
            do_action('wp_body_open');
        }
    endif;

    function twentyeleven_skip_link()
    {
        echo '<div class="skip-link"><a class="assistive-text" href="#content">'.esc_html__('Skip to primary content', 'twentyeleven').'</a></div>';
        if(! is_singular())
        {
            echo '<div class="skip-link"><a class="assistive-text" href="#secondary">'.esc_html__('Skip to secondary content', 'twentyeleven').'</a></div>';
        }
    }

    add_action('wp_body_open', 'twentyeleven_skip_link', 5);

    if(! function_exists('wp_get_list_item_separator')) :

        function wp_get_list_item_separator()
        {
            /* translators: Used between list items, there is a space after the comma. */
            return __(', ', 'twentyeleven');
        }
    endif;
