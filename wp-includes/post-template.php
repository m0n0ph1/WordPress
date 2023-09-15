<?php

    function the_ID()
    { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
        echo get_the_ID();
    }

    function get_the_ID()
    { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
        $post = get_post();

        return ! empty($post) ? $post->ID : false;
    }

    function the_title($before = '', $after = '', $display = true)
    {
        $title = get_the_title();

        if(strlen($title) === 0)
        {
            return;
        }

        $title = $before.$title.$after;

        if($display)
        {
            echo $title;
        }
        else
        {
            return $title;
        }
    }

    function the_title_attribute($args = '')
    {
        $defaults = [
            'before' => '',
            'after' => '',
            'echo' => true,
            'post' => get_post(),
        ];
        $parsed_args = wp_parse_args($args, $defaults);

        $title = get_the_title($parsed_args['post']);

        if(strlen($title) === 0)
        {
            return;
        }

        $title = $parsed_args['before'].$title.$parsed_args['after'];
        $title = esc_attr(strip_tags($title));

        if($parsed_args['echo'])
        {
            echo $title;
        }
        else
        {
            return $title;
        }
    }

    function get_the_title($post = 0)
    {
        $post = get_post($post);

        $post_title = isset($post->post_title) ? $post->post_title : '';
        $post_id = isset($post->ID) ? $post->ID : 0;

        if(! is_admin())
        {
            if(! empty($post->post_password))
            {
                /* translators: %s: Protected post title. */
                $prepend = __('Protected: %s');

                $protected_title_format = apply_filters('protected_title_format', $prepend, $post);

                $post_title = sprintf($protected_title_format, $post_title);
            }
            elseif(isset($post->post_status) && 'private' === $post->post_status)
            {
                /* translators: %s: Private post title. */
                $prepend = __('Private: %s');

                $private_title_format = apply_filters('private_title_format', $prepend, $post);

                $post_title = sprintf($private_title_format, $post_title);
            }
        }

        return apply_filters('the_title', $post_title, $post_id);
    }

    function the_guid($post = 0)
    {
        $post = get_post($post);

        $post_guid = isset($post->guid) ? get_the_guid($post) : '';
        $post_id = isset($post->ID) ? $post->ID : 0;

        echo apply_filters('the_guid', $post_guid, $post_id);
    }

    function get_the_guid($post = 0)
    {
        $post = get_post($post);

        $post_guid = isset($post->guid) ? $post->guid : '';
        $post_id = isset($post->ID) ? $post->ID : 0;

        return apply_filters('get_the_guid', $post_guid, $post_id);
    }

    function the_content($more_link_text = null, $strip_teaser = false)
    {
        $content = get_the_content($more_link_text, $strip_teaser);

        $content = apply_filters('the_content', $content);
        $content = str_replace(']]>', ']]&gt;', $content);
        echo $content;
    }

    function get_the_content($more_link_text = null, $strip_teaser = false, $post = null)
    {
        global $page, $more, $preview, $pages, $multipage;

        $_post = get_post($post);

        if(! ($_post instanceof WP_Post))
        {
            return '';
        }

        /*
	 * Use the globals if the $post parameter was not specified,
	 * but only after they have been set up in setup_postdata().
	 */
        if(null === $post && did_action('the_post'))
        {
            $elements = compact('page', 'more', 'preview', 'pages', 'multipage');
        }
        else
        {
            $elements = generate_postdata($_post);
        }

        if(null === $more_link_text)
        {
            $more_link_text = sprintf(
                '<span aria-label="%1$s">%2$s</span>', sprintf(/* translators: %s: Post title. */ __('Continue reading %s'), the_title_attribute([
                                                                                                                                                     'echo' => false,
                                                                                                                                                     'post' => $_post,
                                                                                                                                                 ])
            ),  __('(more&hellip;)')
            );
        }

        $output = '';
        $has_teaser = false;

        // If post password required and it doesn't match the cookie.
        if(post_password_required($_post))
        {
            return get_the_password_form($_post);
        }

        // If the requested page doesn't exist.
        if($elements['page'] > count($elements['pages']))
        {
            // Give them the highest numbered page that DOES exist.
            $elements['page'] = count($elements['pages']);
        }

        $page_no = $elements['page'];
        $content = $elements['pages'][$page_no - 1];
        if(preg_match('/<!--more(.*?)?-->/', $content, $matches))
        {
            if(has_block('more', $content))
            {
                // Remove the core/more block delimiters. They will be left over after $content is split up.
                $content = preg_replace('/<!-- \/?wp:more(.*?) -->/', '', $content);
            }

            $content = explode($matches[0], $content, 2);

            if(! empty($matches[1]) && ! empty($more_link_text))
            {
                $more_link_text = strip_tags(wp_kses_no_null(trim($matches[1])));
            }

            $has_teaser = true;
        }
        else
        {
            $content = [$content];
        }

        if(str_contains($_post->post_content, '<!--noteaser-->') && (! $elements['multipage'] || 1 == $elements['page']))
        {
            $strip_teaser = true;
        }

        $teaser = $content[0];

        if($elements['more'] && $strip_teaser && $has_teaser)
        {
            $teaser = '';
        }

        $output .= $teaser;

        if(count($content) > 1)
        {
            if($elements['more'])
            {
                $output .= '<span id="more-'.$_post->ID.'"></span>'.$content[1];
            }
            else
            {
                if(! empty($more_link_text))
                {
                    $output .= apply_filters('the_content_more_link', ' <a href="'.get_permalink($_post)."#more-{$_post->ID}\" class=\"more-link\">$more_link_text</a>", $more_link_text);
                }
                $output = force_balance_tags($output);
            }
        }

        return $output;
    }

    function the_excerpt()
    {
        echo apply_filters('the_excerpt', get_the_excerpt());
    }

    function get_the_excerpt($post = null)
    {
        if(is_bool($post))
        {
            _deprecated_argument(__FUNCTION__, '2.3.0');
        }

        $post = get_post($post);
        if(empty($post))
        {
            return '';
        }

        if(post_password_required($post))
        {
            return __('There is no excerpt because this is a protected post.');
        }

        return apply_filters('get_the_excerpt', $post->post_excerpt, $post);
    }

    function has_excerpt($post = 0)
    {
        $post = get_post($post);

        return (! empty($post->post_excerpt));
    }

    function post_class($css_class = '', $post = null)
    {
        // Separates classes with a single space, collates classes for post DIV.
        echo 'class="'.esc_attr(implode(' ', get_post_class($css_class, $post))).'"';
    }

    function get_post_class($css_class = '', $post = null)
    {
        $post = get_post($post);

        $classes = [];

        if($css_class)
        {
            if(! is_array($css_class))
            {
                $css_class = preg_split('#\s+#', $css_class);
            }
            $classes = array_map('esc_attr', $css_class);
        }
        else
        {
            // Ensure that we always coerce class to being an array.
            $css_class = [];
        }

        if(! $post)
        {
            return $classes;
        }

        $classes[] = 'post-'.$post->ID;
        if(! is_admin())
        {
            $classes[] = $post->post_type;
        }
        $classes[] = 'type-'.$post->post_type;
        $classes[] = 'status-'.$post->post_status;

        // Post Format.
        if(post_type_supports($post->post_type, 'post-formats'))
        {
            $post_format = get_post_format($post->ID);

            if($post_format && ! is_wp_error($post_format))
            {
                $classes[] = 'format-'.sanitize_html_class($post_format);
            }
            else
            {
                $classes[] = 'format-standard';
            }
        }

        $post_password_required = post_password_required($post->ID);

        // Post requires password.
        if($post_password_required)
        {
            $classes[] = 'post-password-required';
        }
        elseif(! empty($post->post_password))
        {
            $classes[] = 'post-password-protected';
        }

        // Post thumbnails.
        if(current_theme_supports('post-thumbnails') && has_post_thumbnail($post->ID) && ! is_attachment($post) && ! $post_password_required)
        {
            $classes[] = 'has-post-thumbnail';
        }

        // Sticky for Sticky Posts.
        if(is_sticky($post->ID))
        {
            if(is_home() && ! is_paged())
            {
                $classes[] = 'sticky';
            }
            elseif(is_admin())
            {
                $classes[] = 'status-sticky';
            }
        }

        // hentry for hAtom compliance.
        $classes[] = 'hentry';

        // All public taxonomies.
        $taxonomies = get_taxonomies(['public' => true]);

        $taxonomies = apply_filters('post_class_taxonomies', $taxonomies, $post->ID, $classes, $css_class);

        foreach((array) $taxonomies as $taxonomy)
        {
            if(is_object_in_taxonomy($post->post_type, $taxonomy))
            {
                foreach((array) get_the_terms($post->ID, $taxonomy) as $term)
                {
                    if(empty($term->slug))
                    {
                        continue;
                    }

                    $term_class = sanitize_html_class($term->slug, $term->term_id);
                    if(is_numeric($term_class) || ! trim($term_class, '-'))
                    {
                        $term_class = $term->term_id;
                    }

                    // 'post_tag' uses the 'tag' prefix for backward compatibility.
                    if('post_tag' === $taxonomy)
                    {
                        $classes[] = 'tag-'.$term_class;
                    }
                    else
                    {
                        $classes[] = sanitize_html_class($taxonomy.'-'.$term_class, $taxonomy.'-'.$term->term_id);
                    }
                }
            }
        }

        $classes = array_map('esc_attr', $classes);

        $classes = apply_filters('post_class', $classes, $css_class, $post->ID);

        return array_unique($classes);
    }

    function body_class($css_class = '')
    {
        // Separates class names with a single space, collates class names for body element.
        echo 'class="'.esc_attr(implode(' ', get_body_class($css_class))).'"';
    }

    function get_body_class($css_class = '')
    {
        global $wp_query;

        $classes = [];

        if(is_rtl())
        {
            $classes[] = 'rtl';
        }

        if(is_front_page())
        {
            $classes[] = 'home';
        }
        if(is_home())
        {
            $classes[] = 'blog';
        }
        if(is_privacy_policy())
        {
            $classes[] = 'privacy-policy';
        }
        if(is_archive())
        {
            $classes[] = 'archive';
        }
        if(is_date())
        {
            $classes[] = 'date';
        }
        if(is_search())
        {
            $classes[] = 'search';
            $classes[] = $wp_query->posts ? 'search-results' : 'search-no-results';
        }
        if(is_paged())
        {
            $classes[] = 'paged';
        }
        if(is_attachment())
        {
            $classes[] = 'attachment';
        }
        if(is_404())
        {
            $classes[] = 'error404';
        }

        if(is_singular())
        {
            $post = $wp_query->get_queried_object();
            $post_id = $post->ID;
            $post_type = $post->post_type;

            if(is_page_template())
            {
                $classes[] = "{$post_type}-template";

                $template_slug = get_page_template_slug($post_id);
                $template_parts = explode('/', $template_slug);

                foreach($template_parts as $part)
                {
                    $classes[] = "{$post_type}-template-".sanitize_html_class(
                            str_replace([
                                            '.',
                                            '/'
                                        ], '-', basename($part, '.php'))
                        );
                }
                $classes[] = "{$post_type}-template-".sanitize_html_class(str_replace('.', '-', $template_slug));
            }
            else
            {
                $classes[] = "{$post_type}-template-default";
            }

            if(is_single())
            {
                $classes[] = 'single';
                if(isset($post->post_type))
                {
                    $classes[] = 'single-'.sanitize_html_class($post->post_type, $post_id);
                    $classes[] = 'postid-'.$post_id;

                    // Post Format.
                    if(post_type_supports($post->post_type, 'post-formats'))
                    {
                        $post_format = get_post_format($post->ID);

                        if($post_format && ! is_wp_error($post_format))
                        {
                            $classes[] = 'single-format-'.sanitize_html_class($post_format);
                        }
                        else
                        {
                            $classes[] = 'single-format-standard';
                        }
                    }
                }
            }

            if(is_attachment())
            {
                $mime_type = get_post_mime_type($post_id);
                $mime_prefix = ['application/', 'image/', 'text/', 'audio/', 'video/', 'music/'];
                $classes[] = 'attachmentid-'.$post_id;
                $classes[] = 'attachment-'.str_replace($mime_prefix, '', $mime_type);
            }
            elseif(is_page())
            {
                $classes[] = 'page';
                $classes[] = 'page-id-'.$post_id;

                if(
                    get_pages([
                                  'parent' => $post_id,
                                  'number' => 1,
                              ])
                )
                {
                    $classes[] = 'page-parent';
                }

                if($post->post_parent)
                {
                    $classes[] = 'page-child';
                    $classes[] = 'parent-pageid-'.$post->post_parent;
                }
            }
        }
        elseif(is_archive())
        {
            if(is_post_type_archive())
            {
                $classes[] = 'post-type-archive';
                $post_type = get_query_var('post_type');
                if(is_array($post_type))
                {
                    $post_type = reset($post_type);
                }
                $classes[] = 'post-type-archive-'.sanitize_html_class($post_type);
            }
            elseif(is_author())
            {
                $author = $wp_query->get_queried_object();
                $classes[] = 'author';
                if(isset($author->user_nicename))
                {
                    $classes[] = 'author-'.sanitize_html_class($author->user_nicename, $author->ID);
                    $classes[] = 'author-'.$author->ID;
                }
            }
            elseif(is_category())
            {
                $cat = $wp_query->get_queried_object();
                $classes[] = 'category';
                if(isset($cat->term_id))
                {
                    $cat_class = sanitize_html_class($cat->slug, $cat->term_id);
                    if(is_numeric($cat_class) || ! trim($cat_class, '-'))
                    {
                        $cat_class = $cat->term_id;
                    }

                    $classes[] = 'category-'.$cat_class;
                    $classes[] = 'category-'.$cat->term_id;
                }
            }
            elseif(is_tag())
            {
                $tag = $wp_query->get_queried_object();
                $classes[] = 'tag';
                if(isset($tag->term_id))
                {
                    $tag_class = sanitize_html_class($tag->slug, $tag->term_id);
                    if(is_numeric($tag_class) || ! trim($tag_class, '-'))
                    {
                        $tag_class = $tag->term_id;
                    }

                    $classes[] = 'tag-'.$tag_class;
                    $classes[] = 'tag-'.$tag->term_id;
                }
            }
            elseif(is_tax())
            {
                $term = $wp_query->get_queried_object();
                if(isset($term->term_id))
                {
                    $term_class = sanitize_html_class($term->slug, $term->term_id);
                    if(is_numeric($term_class) || ! trim($term_class, '-'))
                    {
                        $term_class = $term->term_id;
                    }

                    $classes[] = 'tax-'.sanitize_html_class($term->taxonomy);
                    $classes[] = 'term-'.$term_class;
                    $classes[] = 'term-'.$term->term_id;
                }
            }
        }

        if(is_user_logged_in())
        {
            $classes[] = 'logged-in';
        }

        if(is_admin_bar_showing())
        {
            $classes[] = 'admin-bar';
            $classes[] = 'no-customize-support';
        }

        if(current_theme_supports('custom-background') && (get_background_color() !== get_theme_support('custom-background', 'default-color') || get_background_image()))
        {
            $classes[] = 'custom-background';
        }

        if(has_custom_logo())
        {
            $classes[] = 'wp-custom-logo';
        }

        if(current_theme_supports('responsive-embeds'))
        {
            $classes[] = 'wp-embed-responsive';
        }

        $page = $wp_query->get('page');

        if(! $page || $page < 2)
        {
            $page = $wp_query->get('paged');
        }

        if($page && $page > 1 && ! is_404())
        {
            $classes[] = 'paged-'.$page;

            if(is_single())
            {
                $classes[] = 'single-paged-'.$page;
            }
            elseif(is_page())
            {
                $classes[] = 'page-paged-'.$page;
            }
            elseif(is_category())
            {
                $classes[] = 'category-paged-'.$page;
            }
            elseif(is_tag())
            {
                $classes[] = 'tag-paged-'.$page;
            }
            elseif(is_date())
            {
                $classes[] = 'date-paged-'.$page;
            }
            elseif(is_author())
            {
                $classes[] = 'author-paged-'.$page;
            }
            elseif(is_search())
            {
                $classes[] = 'search-paged-'.$page;
            }
            elseif(is_post_type_archive())
            {
                $classes[] = 'post-type-paged-'.$page;
            }
        }

        if(! empty($css_class))
        {
            if(! is_array($css_class))
            {
                $css_class = preg_split('#\s+#', $css_class);
            }
            $classes = array_merge($classes, $css_class);
        }
        else
        {
            // Ensure that we always coerce class to being an array.
            $css_class = [];
        }

        $classes = array_map('esc_attr', $classes);

        $classes = apply_filters('body_class', $classes, $css_class);

        return array_unique($classes);
    }

    function post_password_required($post = null)
    {
        $post = get_post($post);

        if(empty($post->post_password))
        {
            return apply_filters('post_password_required', false, $post);
        }

        if(! isset($_COOKIE['wp-postpass_'.COOKIEHASH]))
        {
            return apply_filters('post_password_required', true, $post);
        }

        require_once ABSPATH.WPINC.'/class-phpass.php';
        $hasher = new PasswordHash(8, true);

        $hash = wp_unslash($_COOKIE['wp-postpass_'.COOKIEHASH]);
        if(! str_starts_with($hash, '$P$B'))
        {
            $required = true;
        }
        else
        {
            $required = ! $hasher->CheckPassword($post->post_password, $hash);
        }

        return apply_filters('post_password_required', $required, $post);
    }

//
// Page Template Functions for usage in Themes.
//

    function wp_link_pages($args = '')
    {
        global $page, $numpages, $multipage, $more;

        $defaults = [
            'before' => '<p class="post-nav-links">'.__('Pages:'),
            'after' => '</p>',
            'link_before' => '',
            'link_after' => '',
            'aria_current' => 'page',
            'next_or_number' => 'number',
            'separator' => ' ',
            'nextpagelink' => __('Next page'),
            'previouspagelink' => __('Previous page'),
            'pagelink' => '%',
            'echo' => 1,
        ];

        $parsed_args = wp_parse_args($args, $defaults);

        $parsed_args = apply_filters('wp_link_pages_args', $parsed_args);

        $output = '';
        if($multipage)
        {
            if('number' === $parsed_args['next_or_number'])
            {
                $output .= $parsed_args['before'];
                for($i = 1; $i <= $numpages; $i++)
                {
                    $link = $parsed_args['link_before'].str_replace('%', $i, $parsed_args['pagelink']).$parsed_args['link_after'];
                    if($i != $page || ! $more && 1 == $page)
                    {
                        $link = _wp_link_page($i).$link.'</a>';
                    }
                    elseif($i === $page)
                    {
                        $link = '<span class="post-page-numbers current" aria-current="'.esc_attr($parsed_args['aria_current']).'">'.$link.'</span>';
                    }

                    $link = apply_filters('wp_link_pages_link', $link, $i);

                    // Use the custom links separator beginning with the second link.
                    $output .= (1 === $i) ? ' ' : $parsed_args['separator'];
                    $output .= $link;
                }
                $output .= $parsed_args['after'];
            }
            elseif($more)
            {
                $output .= $parsed_args['before'];
                $prev = $page - 1;
                if($prev > 0)
                {
                    $link = _wp_link_page($prev).$parsed_args['link_before'].$parsed_args['previouspagelink'].$parsed_args['link_after'].'</a>';

                    $output .= apply_filters('wp_link_pages_link', $link, $prev);
                }
                $next = $page + 1;
                if($next <= $numpages)
                {
                    if($prev)
                    {
                        $output .= $parsed_args['separator'];
                    }
                    $link = _wp_link_page($next).$parsed_args['link_before'].$parsed_args['nextpagelink'].$parsed_args['link_after'].'</a>';

                    $output .= apply_filters('wp_link_pages_link', $link, $next);
                }
                $output .= $parsed_args['after'];
            }
        }

        $html = apply_filters('wp_link_pages', $output, $args);

        if($parsed_args['echo'])
        {
            echo $html;
        }

        return $html;
    }

    function _wp_link_page($i)
    {
        global $wp_rewrite;
        $post = get_post();
        $query_args = [];

        if(1 == $i)
        {
            $url = get_permalink();
        }
        else
        {
            if(! get_option('permalink_structure') || in_array($post->post_status, ['draft', 'pending'], true))
            {
                $url = add_query_arg('page', $i, get_permalink());
            }
            elseif('page' === get_option('show_on_front') && get_option('page_on_front') == $post->ID)
            {
                $url = trailingslashit(get_permalink()).user_trailingslashit("$wp_rewrite->pagination_base/".$i, 'single_paged');
            }
            else
            {
                $url = trailingslashit(get_permalink()).user_trailingslashit($i, 'single_paged');
            }
        }

        if(is_preview())
        {
            if(('draft' !== $post->post_status) && isset($_GET['preview_id'], $_GET['preview_nonce']))
            {
                $query_args['preview_id'] = wp_unslash($_GET['preview_id']);
                $query_args['preview_nonce'] = wp_unslash($_GET['preview_nonce']);
            }

            $url = get_preview_post_link($post, $query_args, $url);
        }

        return '<a href="'.esc_url($url).'" class="post-page-numbers">';
    }

//
// Post-meta: Custom per-post fields.
//

    function post_custom($key = '')
    {
        $custom = get_post_custom();

        if(! isset($custom[$key]))
        {
            return false;
        }
        elseif(1 === count($custom[$key]))
        {
            return $custom[$key][0];
        }
        else
        {
            return $custom[$key];
        }
    }

    function the_meta()
    {
        _deprecated_function(__FUNCTION__, '6.0.2', 'get_post_meta()');
        $keys = get_post_custom_keys();
        if($keys)
        {
            $li_html = '';
            foreach((array) $keys as $key)
            {
                $keyt = trim($key);
                if(is_protected_meta($keyt, 'post'))
                {
                    continue;
                }

                $values = array_map('trim', get_post_custom_values($key));
                $value = implode(', ', $values);

                $html = sprintf("<li><span class='post-meta-key'>%s</span> %s</li>\n", /* translators: %s: Post custom field name. */ esc_html(sprintf(_x('%s:', 'Post custom field name'), $key)), esc_html($value));

                $li_html .= apply_filters('the_meta_key', $html, $key, $value);
            }

            if($li_html)
            {
                echo "<ul class='post-meta'>\n{$li_html}</ul>\n";
            }
        }
    }

//
// Pages.
//

    function wp_dropdown_pages($args = '')
    {
        $defaults = [
            'depth' => 0,
            'child_of' => 0,
            'selected' => 0,
            'echo' => 1,
            'name' => 'page_id',
            'id' => '',
            'class' => '',
            'show_option_none' => '',
            'show_option_no_change' => '',
            'option_none_value' => '',
            'value_field' => 'ID',
        ];

        $parsed_args = wp_parse_args($args, $defaults);

        $pages = get_pages($parsed_args);
        $output = '';
        // Back-compat with old system where both id and name were based on $name argument.
        if(empty($parsed_args['id']))
        {
            $parsed_args['id'] = $parsed_args['name'];
        }

        if(! empty($pages))
        {
            $class = '';
            if(! empty($parsed_args['class']))
            {
                $class = " class='".esc_attr($parsed_args['class'])."'";
            }

            $output = "<select name='".esc_attr($parsed_args['name'])."'".$class." id='".esc_attr($parsed_args['id'])."'>\n";
            if($parsed_args['show_option_no_change'])
            {
                $output .= "\t<option value=\"-1\">".$parsed_args['show_option_no_change']."</option>\n";
            }
            if($parsed_args['show_option_none'])
            {
                $output .= "\t<option value=\"".esc_attr($parsed_args['option_none_value']).'">'.$parsed_args['show_option_none']."</option>\n";
            }
            $output .= walk_page_dropdown_tree($pages, $parsed_args['depth'], $parsed_args);
            $output .= "</select>\n";
        }

        $html = apply_filters('wp_dropdown_pages', $output, $parsed_args, $pages);

        if($parsed_args['echo'])
        {
            echo $html;
        }

        return $html;
    }

    function wp_list_pages($args = '')
    {
        $defaults = [
            'depth' => 0,
            'show_date' => '',
            'date_format' => get_option('date_format'),
            'child_of' => 0,
            'exclude' => '',
            'title_li' => __('Pages'),
            'echo' => 1,
            'authors' => '',
            'sort_column' => 'menu_order, post_title',
            'link_before' => '',
            'link_after' => '',
            'item_spacing' => 'preserve',
            'walker' => '',
        ];

        $parsed_args = wp_parse_args($args, $defaults);

        if(! in_array($parsed_args['item_spacing'], ['preserve', 'discard'], true))
        {
            // Invalid value, fall back to default.
            $parsed_args['item_spacing'] = $defaults['item_spacing'];
        }

        $output = '';
        $current_page = 0;

        // Sanitize, mostly to keep spaces out.
        $parsed_args['exclude'] = preg_replace('/[^0-9,]/', '', $parsed_args['exclude']);

        // Allow plugins to filter an array of excluded pages (but don't put a nullstring into the array).
        $exclude_array = ($parsed_args['exclude']) ? explode(',', $parsed_args['exclude']) : [];

        $parsed_args['exclude'] = implode(',', apply_filters('wp_list_pages_excludes', $exclude_array));

        $parsed_args['hierarchical'] = 0;

        // Query pages.
        $pages = get_pages($parsed_args);

        if(! empty($pages))
        {
            if($parsed_args['title_li'])
            {
                $output .= '<li class="pagenav">'.$parsed_args['title_li'].'<ul>';
            }
            global $wp_query;
            if(is_page() || is_attachment() || $wp_query->is_posts_page)
            {
                $current_page = get_queried_object_id();
            }
            elseif(is_singular())
            {
                $queried_object = get_queried_object();
                if(is_post_type_hierarchical($queried_object->post_type))
                {
                    $current_page = $queried_object->ID;
                }
            }

            $output .= walk_page_tree($pages, $parsed_args['depth'], $current_page, $parsed_args);

            if($parsed_args['title_li'])
            {
                $output .= '</ul></li>';
            }
        }

        $html = apply_filters('wp_list_pages', $output, $parsed_args, $pages);

        if($parsed_args['echo'])
        {
            echo $html;
        }
        else
        {
            return $html;
        }
    }

    function wp_page_menu($args = [])
    {
        $defaults = [
            'sort_column' => 'menu_order, post_title',
            'menu_id' => '',
            'menu_class' => 'menu',
            'container' => 'div',
            'echo' => true,
            'link_before' => '',
            'link_after' => '',
            'before' => '<ul>',
            'after' => '</ul>',
            'item_spacing' => 'discard',
            'walker' => '',
        ];
        $args = wp_parse_args($args, $defaults);

        if(! in_array($args['item_spacing'], ['preserve', 'discard'], true))
        {
            // Invalid value, fall back to default.
            $args['item_spacing'] = $defaults['item_spacing'];
        }

        if('preserve' === $args['item_spacing'])
        {
            $t = "\t";
            $n = "\n";
        }
        else
        {
            $t = '';
            $n = '';
        }

        $args = apply_filters('wp_page_menu_args', $args);

        $menu = '';

        $list_args = $args;

        // Show Home in the menu.
        if(! empty($args['show_home']))
        {
            if(true === $args['show_home'] || '1' === $args['show_home'] || 1 === $args['show_home'])
            {
                $text = __('Home');
            }
            else
            {
                $text = $args['show_home'];
            }
            $class = '';
            if(is_front_page() && ! is_paged())
            {
                $class = 'class="current_page_item"';
            }
            $menu .= '<li '.$class.'><a href="'.esc_url(home_url('/')).'">'.$args['link_before'].$text.$args['link_after'].'</a></li>';
            // If the front page is a page, add it to the exclude list.
            if('page' === get_option('show_on_front'))
            {
                if(! empty($list_args['exclude']))
                {
                    $list_args['exclude'] .= ',';
                }
                else
                {
                    $list_args['exclude'] = '';
                }
                $list_args['exclude'] .= get_option('page_on_front');
            }
        }

        $list_args['echo'] = false;
        $list_args['title_li'] = '';
        $menu .= wp_list_pages($list_args);

        $container = sanitize_text_field($args['container']);

        // Fallback in case `wp_nav_menu()` was called without a container.
        if(empty($container))
        {
            $container = 'div';
        }

        if($menu)
        {
            // wp_nav_menu() doesn't set before and after.
            if(isset($args['fallback_cb']) && 'wp_page_menu' === $args['fallback_cb'] && 'ul' !== $container)
            {
                $args['before'] = "<ul>{$n}";
                $args['after'] = '</ul>';
            }

            $menu = $args['before'].$menu.$args['after'];
        }

        $attrs = '';
        if(! empty($args['menu_id']))
        {
            $attrs .= ' id="'.esc_attr($args['menu_id']).'"';
        }

        if(! empty($args['menu_class']))
        {
            $attrs .= ' class="'.esc_attr($args['menu_class']).'"';
        }

        $menu = "<{$container}{$attrs}>".$menu."</{$container}>{$n}";

        $menu = apply_filters('wp_page_menu', $menu, $args);

        if($args['echo'])
        {
            echo $menu;
        }
        else
        {
            return $menu;
        }
    }

//
// Page helpers.
//

    function walk_page_tree($pages, $depth, $current_page, $args)
    {
        if(empty($args['walker']))
        {
            $walker = new Walker_Page();
        }
        else
        {
            $walker = $args['walker'];
        }

        foreach((array) $pages as $page)
        {
            if($page->post_parent)
            {
                $args['pages_with_children'][$page->post_parent] = true;
            }
        }

        return $walker->walk($pages, $depth, $args, $current_page);
    }

    function walk_page_dropdown_tree(...$args)
    {
        if(empty($args[2]['walker']))
        { // The user's options are the third parameter.
            $walker = new Walker_PageDropdown();
        }
        else
        {
            $walker = $args[2]['walker'];
        }

        return $walker->walk(...$args);
    }

//
// Attachments.
//

    function the_attachment_link($post = 0, $fullsize = false, $deprecated = false, $permalink = false)
    {
        if(! empty($deprecated))
        {
            _deprecated_argument(__FUNCTION__, '2.5.0');
        }

        if($fullsize)
        {
            echo wp_get_attachment_link($post, 'full', $permalink);
        }
        else
        {
            echo wp_get_attachment_link($post, 'thumbnail', $permalink);
        }
    }

    function wp_get_attachment_link(
        $post = 0, $size = 'thumbnail', $permalink = false, $icon = false, $text = false, $attr = ''
    ) {
        $_post = get_post($post);

        if(empty($_post) || ('attachment' !== $_post->post_type) || ! wp_get_attachment_url($_post->ID))
        {
            return __('Missing Attachment');
        }

        $url = wp_get_attachment_url($_post->ID);

        if($permalink)
        {
            $url = get_attachment_link($_post->ID);
        }

        if($text)
        {
            $link_text = $text;
        }
        elseif($size && 'none' !== $size)
        {
            $link_text = wp_get_attachment_image($_post->ID, $size, $icon, $attr);
        }
        else
        {
            $link_text = '';
        }

        if('' === trim($link_text))
        {
            $link_text = $_post->post_title;
        }

        if('' === trim($link_text))
        {
            $link_text = esc_html(pathinfo(get_attached_file($_post->ID), PATHINFO_FILENAME));
        }

        $attributes = apply_filters('wp_get_attachment_link_attributes', ['href' => $url], $_post->ID);

        $link_attributes = '';
        foreach($attributes as $name => $value)
        {
            $value = 'href' === $name ? esc_url($value) : esc_attr($value);
            $link_attributes .= ' '.esc_attr($name)."='".$value."'";
        }

        $link_html = "<a$link_attributes>$link_text</a>";

        return apply_filters('wp_get_attachment_link', $link_html, $post, $size, $permalink, $icon, $text, $attr);
    }

    function prepend_attachment($content)
    {
        $post = get_post();

        if(empty($post->post_type) || 'attachment' !== $post->post_type)
        {
            return $content;
        }

        if(wp_attachment_is('video', $post))
        {
            $meta = wp_get_attachment_metadata(get_the_ID());
            $atts = ['src' => wp_get_attachment_url()];
            if(! empty($meta['width']) && ! empty($meta['height']))
            {
                $atts['width'] = (int) $meta['width'];
                $atts['height'] = (int) $meta['height'];
            }
            if(has_post_thumbnail())
            {
                $atts['poster'] = wp_get_attachment_url(get_post_thumbnail_id());
            }
            $p = wp_video_shortcode($atts);
        }
        elseif(wp_attachment_is('audio', $post))
        {
            $p = wp_audio_shortcode(['src' => wp_get_attachment_url()]);
        }
        else
        {
            $p = '<p class="attachment">';
            // Show the medium sized image representation of the attachment if available, and link to the raw file.
            $p .= wp_get_attachment_link(0, 'medium', false);
            $p .= '</p>';
        }

        $p = apply_filters('prepend_attachment', $p);

        return "$p\n$content";
    }

//
// Misc.
//

    function get_the_password_form($post = 0)
    {
        $post = get_post($post);
        $label = 'pwbox-'.(empty($post->ID) ? rand() : $post->ID);
        $output = '<form action="'.esc_url(site_url('wp-login.php?action=postpass', 'login_post')).'" class="post-password-form" method="post">
	<p>'.__('This content is password protected. To view it please enter your password below:').'</p>
	<p><label for="'.$label.'">'.__('Password:').' <input name="post_password" id="'.$label.'" type="password" spellcheck="false" size="20" /></label> <input type="submit" name="Submit" value="'.esc_attr_x('Enter', 'post password form').'" /></p></form>
	';

        return apply_filters('the_password_form', $output, $post);
    }

    function is_page_template($template = '')
    {
        if(! is_singular())
        {
            return false;
        }

        $page_template = get_page_template_slug(get_queried_object_id());

        if(empty($template))
        {
            return (bool) $page_template;
        }

        if($template == $page_template)
        {
            return true;
        }

        if(is_array($template))
        {
            if((in_array('default', $template, true) && ! $page_template) || in_array($page_template, $template, true))
            {
                return true;
            }
        }

        return ('default' === $template && ! $page_template);
    }

    function get_page_template_slug($post = null)
    {
        $post = get_post($post);

        if(! $post)
        {
            return false;
        }

        $template = get_post_meta($post->ID, '_wp_page_template', true);

        if(! $template || 'default' === $template)
        {
            return '';
        }

        return $template;
    }

    function wp_post_revision_title($revision, $link = true)
    {
        $revision = get_post($revision);

        if(! $revision)
        {
            return $revision;
        }

        if(! in_array($revision->post_type, ['post', 'page', 'revision'], true))
        {
            return false;
        }

        /* translators: Revision date format, see https://www.php.net/manual/datetime.format.php */
        $datef = _x('F j, Y @ H:i:s', 'revision date format');
        /* translators: %s: Revision date. */
        $autosavef = __('%s [Autosave]');
        /* translators: %s: Revision date. */
        $currentf = __('%s [Current Revision]');

        $date = date_i18n($datef, strtotime($revision->post_modified));
        $edit_link = get_edit_post_link($revision->ID);
        if($link && current_user_can('edit_post', $revision->ID) && $edit_link)
        {
            $date = "<a href='$edit_link'>$date</a>";
        }

        if(! wp_is_post_revision($revision))
        {
            $date = sprintf($currentf, $date);
        }
        elseif(wp_is_post_autosave($revision))
        {
            $date = sprintf($autosavef, $date);
        }

        return $date;
    }

    function wp_post_revision_title_expanded($revision, $link = true)
    {
        $revision = get_post($revision);

        if(! $revision)
        {
            return $revision;
        }

        if(! in_array($revision->post_type, ['post', 'page', 'revision'], true))
        {
            return false;
        }

        $author = get_the_author_meta('display_name', $revision->post_author);
        /* translators: Revision date format, see https://www.php.net/manual/datetime.format.php */
        $datef = _x('F j, Y @ H:i:s', 'revision date format');

        $gravatar = get_avatar($revision->post_author, 24);

        $date = date_i18n($datef, strtotime($revision->post_modified));
        $edit_link = get_edit_post_link($revision->ID);
        if($link && current_user_can('edit_post', $revision->ID) && $edit_link)
        {
            $date = "<a href='$edit_link'>$date</a>";
        }

        $revision_date_author = sprintf(/* translators: Post revision title. 1: Author avatar, 2: Author name, 3: Time ago, 4: Date. */ __('%1$s %2$s, %3$s ago (%4$s)'), $gravatar, $author, human_time_diff(strtotime($revision->post_modified_gmt)), $date);

        /* translators: %s: Revision date with author avatar. */
        $autosavef = __('%s [Autosave]');
        /* translators: %s: Revision date with author avatar. */
        $currentf = __('%s [Current Revision]');

        if(! wp_is_post_revision($revision))
        {
            $revision_date_author = sprintf($currentf, $revision_date_author);
        }
        elseif(wp_is_post_autosave($revision))
        {
            $revision_date_author = sprintf($autosavef, $revision_date_author);
        }

        return apply_filters('wp_post_revision_title_expanded', $revision_date_author, $revision, $link);
    }

    function wp_list_post_revisions($post = 0, $type = 'all')
    {
        $post = get_post($post);

        if(! $post)
        {
            return;
        }

        // $args array with (parent, format, right, left, type) deprecated since 3.6.
        if(is_array($type))
        {
            $type = ! empty($type['type']) ? $type['type'] : $type;
            _deprecated_argument(__FUNCTION__, '3.6.0');
        }

        $revisions = wp_get_post_revisions($post->ID);

        if(! $revisions)
        {
            return;
        }

        $rows = '';
        foreach($revisions as $revision)
        {
            if(! current_user_can('read_post', $revision->ID))
            {
                continue;
            }

            $is_autosave = wp_is_post_autosave($revision);
            if(('revision' === $type && $is_autosave) || ('autosave' === $type && ! $is_autosave))
            {
                continue;
            }

            $rows .= "\t<li>".wp_post_revision_title_expanded($revision)."</li>\n";
        }

        echo "<div class='hide-if-js'><p>".__('JavaScript must be enabled to use this feature.')."</p></div>\n";

        echo "<ul class='post-revisions hide-if-no-js'>\n";
        echo $rows;
        echo '</ul>';
    }

    function get_post_parent($post = null)
    {
        $wp_post = get_post($post);

        return ! empty($wp_post->post_parent) ? get_post($wp_post->post_parent) : null;
    }

    function has_post_parent($post = null)
    {
        return (bool) get_post_parent($post);
    }
