<?php

    function get_bloginfo_rss($show = '')
    {
        $info = strip_tags(get_bloginfo($show));

        return apply_filters('get_bloginfo_rss', convert_chars($info), $show);
    }

    function bloginfo_rss($show = '')
    {
        echo apply_filters('bloginfo_rss', get_bloginfo_rss($show), $show);
    }

    function get_default_feed()
    {
        $default_feed = apply_filters('default_feed', 'rss2');

        if('rss' === $default_feed)
        {
            return 'rss2';
        }

        return $default_feed;
    }

    function get_wp_title_rss($deprecated = '&#8211;')
    {
        if('&#8211;' !== $deprecated)
        {
            /* translators: %s: 'document_title_separator' filter name. */
            _deprecated_argument(__FUNCTION__, '4.4.0', sprintf(__('Use the %s filter instead.'), '<code>document_title_separator</code>'));
        }

        return apply_filters('get_wp_title_rss', wp_get_document_title(), $deprecated);
    }

    function wp_title_rss($deprecated = '&#8211;')
    {
        if('&#8211;' !== $deprecated)
        {
            /* translators: %s: 'document_title_separator' filter name. */
            _deprecated_argument(__FUNCTION__, '4.4.0', sprintf(__('Use the %s filter instead.'), '<code>document_title_separator</code>'));
        }

        echo apply_filters('wp_title_rss', get_wp_title_rss(), $deprecated);
    }

    function get_the_title_rss()
    {
        $title = get_the_title();

        return apply_filters('the_title_rss', $title);
    }

    function the_title_rss()
    {
        echo get_the_title_rss();
    }

    function get_the_content_feed($feed_type = null)
    {
        if(! $feed_type)
        {
            $feed_type = get_default_feed();
        }

        $content = apply_filters('the_content', get_the_content());
        $content = str_replace(']]>', ']]&gt;', $content);

        return apply_filters('the_content_feed', $content, $feed_type);
    }

    function the_content_feed($feed_type = null)
    {
        echo get_the_content_feed($feed_type);
    }

    function the_excerpt_rss()
    {
        $output = get_the_excerpt();

        echo apply_filters('the_excerpt_rss', $output);
    }

    function the_permalink_rss()
    {
        echo esc_url(apply_filters('the_permalink_rss', get_permalink()));
    }

    function comments_link_feed()
    {
        echo esc_url(apply_filters('comments_link_feed', get_comments_link()));
    }

    function comment_guid($comment_id = null)
    {
        echo esc_url(get_comment_guid($comment_id));
    }

    function get_comment_guid($comment_id = null)
    {
        $comment = get_comment($comment_id);

        if(! is_object($comment))
        {
            return false;
        }

        return get_the_guid($comment->comment_post_ID).'#comment-'.$comment->comment_ID;
    }

    function comment_link($comment = null)
    {
        echo esc_url(apply_filters('comment_link', get_comment_link($comment)));
    }

    function get_comment_author_rss()
    {
        return apply_filters('comment_author_rss', get_comment_author());
    }

    function comment_author_rss()
    {
        echo get_comment_author_rss();
    }

    function comment_text_rss()
    {
        $comment_text = get_comment_text();

        $comment_text = apply_filters('comment_text_rss', $comment_text);
        echo $comment_text;
    }

    function get_the_category_rss($type = null)
    {
        if(empty($type))
        {
            $type = get_default_feed();
        }
        $categories = get_the_category();
        $tags = get_the_tags();
        $the_list = '';
        $cat_names = [];

        $filter = 'rss';
        if('atom' === $type)
        {
            $filter = 'raw';
        }

        if(! empty($categories))
        {
            foreach((array) $categories as $category)
            {
                $cat_names[] = sanitize_term_field('name', $category->name, $category->term_id, 'category', $filter);
            }
        }

        if(! empty($tags))
        {
            foreach((array) $tags as $tag)
            {
                $cat_names[] = sanitize_term_field('name', $tag->name, $tag->term_id, 'post_tag', $filter);
            }
        }

        $cat_names = array_unique($cat_names);

        foreach($cat_names as $cat_name)
        {
            if('rdf' === $type)
            {
                $the_list .= "\t\t<dc:subject><![CDATA[$cat_name]]></dc:subject>\n";
            }
            elseif('atom' === $type)
            {
                $the_list .= sprintf('<category scheme="%1$s" term="%2$s" />', esc_attr(get_bloginfo_rss('url')), esc_attr($cat_name));
            }
            else
            {
                $the_list .= "\t\t<category><![CDATA[".html_entity_decode($cat_name, ENT_COMPAT, get_option('blog_charset'))."]]></category>\n";
            }
        }

        return apply_filters('the_category_rss', $the_list, $type);
    }

    function the_category_rss($type = null)
    {
        echo get_the_category_rss($type);
    }

    function html_type_rss()
    {
        $type = get_bloginfo('html_type');
        if(str_contains($type, 'xhtml'))
        {
            $type = 'xhtml';
        }
        else
        {
            $type = 'html';
        }
        echo $type;
    }

    function rss_enclosure()
    {
        if(post_password_required())
        {
            return;
        }

        foreach((array) get_post_custom() as $key => $val)
        {
            if('enclosure' === $key)
            {
                foreach((array) $val as $enc)
                {
                    $enclosure = explode("\n", $enc);

                    // Only get the first element, e.g. 'audio/mpeg' from 'audio/mpeg mpga mp2 mp3'.
                    $t = preg_split('/[ \t]/', trim($enclosure[2]));
                    $type = $t[0];

                    echo apply_filters('rss_enclosure', '<enclosure url="'.esc_url(trim($enclosure[0])).'" length="'.absint(trim($enclosure[1])).'" type="'.esc_attr($type).'" />'."\n");
                }
            }
        }
    }

    function atom_enclosure()
    {
        if(post_password_required())
        {
            return;
        }

        foreach((array) get_post_custom() as $key => $val)
        {
            if('enclosure' === $key)
            {
                foreach((array) $val as $enc)
                {
                    $enclosure = explode("\n", $enc);

                    $url = '';
                    $type = '';
                    $length = 0;

                    $mimes = get_allowed_mime_types();

                    // Parse URL.
                    if(isset($enclosure[0]) && is_string($enclosure[0]))
                    {
                        $url = trim($enclosure[0]);
                    }

                    // Parse length and type.
                    for($i = 1; $i <= 2; $i++)
                    {
                        if(isset($enclosure[$i]))
                        {
                            if(is_numeric($enclosure[$i]))
                            {
                                $length = trim($enclosure[$i]);
                            }
                            elseif(in_array($enclosure[$i], $mimes, true))
                            {
                                $type = trim($enclosure[$i]);
                            }
                        }
                    }

                    $html_link_tag = sprintf("<link href=\"%s\" rel=\"enclosure\" length=\"%d\" type=\"%s\" />\n", esc_url($url), esc_attr($length), esc_attr($type));

                    echo apply_filters('atom_enclosure', $html_link_tag);
                }
            }
        }
    }

    function prep_atom_text_construct($data)
    {
        if(! str_contains($data, '<') && ! str_contains($data, '&'))
        {
            return ['text', $data];
        }

        if(! function_exists('xml_parser_create'))
        {
            trigger_error(__("PHP's XML extension is not available. Please contact your hosting provider to enable PHP's XML extension."));

            return ['html', "<![CDATA[$data]]>"];
        }

        $parser = xml_parser_create();
        xml_parse($parser, '<div>'.$data.'</div>', true);
        $code = xml_get_error_code($parser);
        xml_parser_free($parser);
        unset($parser);

        if(! $code)
        {
            if(str_contains($data, '<'))
            {
                $data = "<div xmlns='http://www.w3.org/1999/xhtml'>$data</div>";

                return ['xhtml', $data];
            }
            else
            {
                return ['text', $data];
            }
        }

        if(str_contains($data, ']]>'))
        {
            return ['html', htmlspecialchars($data)];
        }
        else
        {
            return ['html', "<![CDATA[$data]]>"];
        }
    }

    function atom_site_icon()
    {
        $url = get_site_icon_url(32);
        if($url)
        {
            echo '<icon>'.convert_chars($url)."</icon>\n";
        }
    }

    function rss2_site_icon()
    {
        $rss_title = get_wp_title_rss();
        if(empty($rss_title))
        {
            $rss_title = get_bloginfo_rss('name');
        }

        $url = get_site_icon_url(32);
        if($url)
        {
            echo '
<image>
	<url>'.convert_chars($url).'</url>
	<title>'.$rss_title.'</title>
	<link>'.get_bloginfo_rss('url').'</link>
	<width>32</width>
	<height>32</height>
</image> '."\n";
        }
    }

    function get_self_link()
    {
        $host = parse_url(home_url());

        return set_url_scheme('http://'.$host['host'].wp_unslash($_SERVER['REQUEST_URI']));
    }

    function self_link()
    {
        echo esc_url(apply_filters('self_link', get_self_link()));
    }

    function get_feed_build_date($format)
    {
        global $wp_query;

        $datetime = false;
        $max_modified_time = false;
        $utc = new DateTimeZone('UTC');

        if(! empty($wp_query) && $wp_query->have_posts())
        {
            // Extract the post modified times from the posts.
            $modified_times = wp_list_pluck($wp_query->posts, 'post_modified_gmt');

            // If this is a comment feed, check those objects too.
            if($wp_query->is_comment_feed() && $wp_query->comment_count)
            {
                // Extract the comment modified times from the comments.
                $comment_times = wp_list_pluck($wp_query->comments, 'comment_date_gmt');

                // Add the comment times to the post times for comparison.
                $modified_times = array_merge($modified_times, $comment_times);
            }

            // Determine the maximum modified time.
            $datetime = date_create_immutable_from_format('Y-m-d H:i:s', max($modified_times), $utc);
        }

        if(false === $datetime)
        {
            // Fall back to last time any post was modified or published.
            $datetime = date_create_immutable_from_format('Y-m-d H:i:s', get_lastpostmodified('GMT'), $utc);
        }

        if(false !== $datetime)
        {
            $max_modified_time = $datetime->format($format);
        }

        return apply_filters('get_feed_build_date', $max_modified_time, $format);
    }

    function feed_content_type($type = '')
    {
        if(empty($type))
        {
            $type = get_default_feed();
        }

        $types = [
            'rss' => 'application/rss+xml',
            'rss2' => 'application/rss+xml',
            'rss-http' => 'text/xml',
            'atom' => 'application/atom+xml',
            'rdf' => 'application/rdf+xml',
        ];

        $content_type = (! empty($types[$type])) ? $types[$type] : 'application/octet-stream';

        return apply_filters('feed_content_type', $content_type, $type);
    }

    function fetch_feed($url)
    {
        if(! class_exists('SimplePie', false))
        {
            require_once ABSPATH.WPINC.'/class-simplepie.php';
        }

        require_once ABSPATH.WPINC.'/class-wp-feed-cache-transient.php';
        require_once ABSPATH.WPINC.'/class-wp-simplepie-file.php';
        require_once ABSPATH.WPINC.'/class-wp-simplepie-sanitize-kses.php';

        $feed = new SimplePie();

        $feed->set_sanitize_class('WP_SimplePie_Sanitize_KSES');
        /*
         * We must manually overwrite $feed->sanitize because SimplePie's constructor
         * sets it before we have a chance to set the sanitization class.
         */
        $feed->sanitize = new WP_SimplePie_Sanitize_KSES();

        // Register the cache handler using the recommended method for SimplePie 1.3 or later.
        if(method_exists('SimplePie_Cache', 'register'))
        {
            SimplePie_Cache::register('wp_transient', 'WP_Feed_Cache_Transient');
            $feed->set_cache_location('wp_transient');
        }
        else
        {
            // Back-compat for SimplePie 1.2.x.
            require_once ABSPATH.WPINC.'/class-wp-feed-cache.php';
            $feed->set_cache_class('WP_Feed_Cache');
        }

        $feed->set_file_class('WP_SimplePie_File');

        $feed->set_feed_url($url);

        $feed->set_cache_duration(apply_filters('wp_feed_cache_transient_lifetime', 12 * HOUR_IN_SECONDS, $url));

        do_action_ref_array('wp_feed_options', [&$feed, $url]);

        $feed->init();
        $feed->set_output_encoding(get_option('blog_charset'));

        if($feed->error())
        {
            return new WP_Error('simplepie-error', $feed->error());
        }

        return $feed;
    }
