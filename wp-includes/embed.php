<?php

    function wp_embed_register_handler($id, $regex, $callback, $priority = 10)
    {
        global $wp_embed;
        $wp_embed->register_handler($id, $regex, $callback, $priority);
    }

    function wp_embed_unregister_handler($id, $priority = 10)
    {
        global $wp_embed;
        $wp_embed->unregister_handler($id, $priority);
    }

    function wp_embed_defaults($url = '')
    {
        if(! empty($GLOBALS['content_width']))
        {
            $width = (int) $GLOBALS['content_width'];
        }

        if(empty($width))
        {
            $width = 500;
        }

        $height = min(ceil($width * 1.5), 1000);

        return apply_filters('embed_defaults', compact('width', 'height'), $url);
    }

    function wp_oembed_get($url, $args = '')
    {
        $oembed = _wp_oembed_get_object();

        return $oembed->get_html($url, $args);
    }

    function _wp_oembed_get_object()
    {
        static $wp_oembed = null;

        if(is_null($wp_oembed))
        {
            $wp_oembed = new WP_oEmbed();
        }

        return $wp_oembed;
    }

    function wp_oembed_add_provider($format, $provider, $regex = false)
    {
        if(did_action('plugins_loaded'))
        {
            $oembed = _wp_oembed_get_object();
            $oembed->providers[$format] = [$provider, $regex];
        }
        else
        {
            WP_oEmbed::_add_provider_early($format, $provider, $regex);
        }
    }

    function wp_oembed_remove_provider($format)
    {
        if(did_action('plugins_loaded'))
        {
            $oembed = _wp_oembed_get_object();

            if(isset($oembed->providers[$format]))
            {
                unset($oembed->providers[$format]);

                return true;
            }
        }
        else
        {
            WP_oEmbed::_remove_provider_early($format);
        }

        return false;
    }

    function wp_maybe_load_embeds()
    {
        if(! apply_filters('load_default_embeds', true))
        {
            return;
        }

        wp_embed_register_handler('youtube_embed_url', '#https?://(www.)?youtube\.com/(?:v|embed)/([^/]+)#i', 'wp_embed_handler_youtube');

        wp_embed_register_handler('audio', '#^https?://.+?\.('.implode('|', wp_get_audio_extensions()).')$#i', apply_filters('wp_audio_embed_handler', 'wp_embed_handler_audio'), 9999);

        wp_embed_register_handler('video', '#^https?://.+?\.('.implode('|', wp_get_video_extensions()).')$#i', apply_filters('wp_video_embed_handler', 'wp_embed_handler_video'), 9999);
    }

    function wp_embed_handler_youtube($matches, $attr, $url, $rawattr)
    {
        global $wp_embed;
        $embed = $wp_embed->autoembed(sprintf('https://youtube.com/watch?v=%s', urlencode($matches[2])));

        return apply_filters('wp_embed_handler_youtube', $embed, $attr, $url, $rawattr);
    }

    function wp_embed_handler_audio($matches, $attr, $url, $rawattr)
    {
        $audio = sprintf('[audio src="%s" /]', esc_url($url));

        return apply_filters('wp_embed_handler_audio', $audio, $attr, $url, $rawattr);
    }

    function wp_embed_handler_video($matches, $attr, $url, $rawattr)
    {
        $dimensions = '';
        if(! empty($rawattr['width']) && ! empty($rawattr['height']))
        {
            $dimensions .= sprintf('width="%d" ', (int) $rawattr['width']);
            $dimensions .= sprintf('height="%d" ', (int) $rawattr['height']);
        }
        $video = sprintf('[video %s src="%s" /]', $dimensions, esc_url($url));

        return apply_filters('wp_embed_handler_video', $video, $attr, $url, $rawattr);
    }

    function wp_oembed_register_route()
    {
        $controller = new WP_oEmbed_Controller();
        $controller->register_routes();
    }

    function wp_oembed_add_discovery_links()
    {
        $output = '';

        if(is_singular())
        {
            $output .= '<link rel="alternate" type="application/json+oembed" href="'.esc_url(get_oembed_endpoint_url(get_permalink())).'" />'."\n";

            if(class_exists('SimpleXMLElement'))
            {
                $output .= '<link rel="alternate" type="text/xml+oembed" href="'.esc_url(get_oembed_endpoint_url(get_permalink(), 'xml')).'" />'."\n";
            }
        }

        echo apply_filters('oembed_discovery_links', $output);
    }

    function wp_oembed_add_host_js() {}

    function wp_maybe_enqueue_oembed_host_js($html)
    {
        if(has_action('wp_head', 'wp_oembed_add_host_js') && preg_match('/<blockquote\s[^>]*?wp-embedded-content/', $html))
        {
            wp_enqueue_script('wp-embed');
        }

        return $html;
    }

    function get_post_embed_url($post = null)
    {
        $post = get_post($post);

        if(! $post)
        {
            return false;
        }

        $embed_url = trailingslashit(get_permalink($post)).user_trailingslashit('embed');
        $path_conflict = get_page_by_path(str_replace(home_url(), '', $embed_url), OBJECT, get_post_types(['public' => true]));

        if(! get_option('permalink_structure') || $path_conflict)
        {
            $embed_url = add_query_arg(['embed' => 'true'], get_permalink($post));
        }

        return sanitize_url(apply_filters('post_embed_url', $embed_url, $post));
    }

    function get_oembed_endpoint_url($permalink = '', $format = 'json')
    {
        $url = rest_url('oembed/1.0/embed');

        if('' !== $permalink)
        {
            $url = add_query_arg([
                                     'url' => urlencode($permalink),
                                     'format' => ('json' !== $format) ? $format : false,
                                 ], $url);
        }

        return apply_filters('oembed_endpoint_url', $url, $permalink, $format);
    }

    function get_post_embed_html($width, $height, $post = null)
    {
        $post = get_post($post);

        if(! $post)
        {
            return false;
        }

        $embed_url = get_post_embed_url($post);

        $secret = wp_generate_password(10, false);
        $embed_url .= "#?secret={$secret}";

        $output = sprintf('<blockquote class="wp-embedded-content" data-secret="%1$s"><a href="%2$s">%3$s</a></blockquote>', esc_attr($secret), esc_url(get_permalink($post)), get_the_title($post));

        $output .= sprintf('<iframe sandbox="allow-scripts" security="restricted" src="%1$s" width="%2$d" height="%3$d" title="%4$s" data-secret="%5$s" frameborder="0" marginwidth="0" marginheight="0" scrolling="no" class="wp-embedded-content"></iframe>', esc_url($embed_url), absint($width), absint($height), esc_attr(sprintf(/* translators: 1: Post title, 2: Site title. */ __('&#8220;%1$s&#8221; &#8212; %2$s'), get_the_title($post), get_bloginfo('name'))), esc_attr($secret));

        /*
         * Note that the script must be placed after the <blockquote> and <iframe> due to a regexp parsing issue in
         * `wp_filter_oembed_result()`. Because of the regex pattern starts with `|(<blockquote>.*?</blockquote>)?.*|`
         * wherein the <blockquote> is marked as being optional, if it is not at the beginning of the string then the group
         * will fail to match and everything will be matched by `.*` and not included in the group. This regex issue goes
         * back to WordPress 4.4, so in order to not break older installs this script must come at the end.
         */
        $output .= wp_get_inline_script_tag(file_get_contents(ABSPATH.WPINC.'/js/wp-embed'.wp_scripts_get_suffix().'.js'));

        return apply_filters('embed_html', $output, $post, $width, $height);
    }

    function get_oembed_response_data($post, $width)
    {
        $post = get_post($post);
        $width = absint($width);

        if(! $post)
        {
            return false;
        }

        if(! is_post_publicly_viewable($post))
        {
            return false;
        }

        $min_max_width = apply_filters('oembed_min_max_width', [
            'min' => 200,
            'max' => 600,
        ]);

        $width = min(max($min_max_width['min'], $width), $min_max_width['max']);
        $height = max(ceil($width / 16 * 9), 200);

        $data = [
            'version' => '1.0',
            'provider_name' => get_bloginfo('name'),
            'provider_url' => get_home_url(),
            'author_name' => get_bloginfo('name'),
            'author_url' => get_home_url(),
            'title' => get_the_title($post),
            'type' => 'link',
        ];

        $author = get_userdata($post->post_author);

        if($author)
        {
            $data['author_name'] = $author->display_name;
            $data['author_url'] = get_author_posts_url($author->ID);
        }

        return apply_filters('oembed_response_data', $data, $post, $width, $height);
    }

    function get_oembed_response_data_for_url($url, $args)
    {
        $switched_blog = false;

        if(is_multisite())
        {
            $url_parts = wp_parse_args(wp_parse_url($url), [
                'host' => '',
                'path' => '/',
            ]);

            $qv = [
                'domain' => $url_parts['host'],
                'path' => '/',
                'update_site_meta_cache' => false,
            ];

            // In case of subdirectory configs, set the path.
            if(! is_subdomain_install())
            {
                $path = explode('/', ltrim($url_parts['path'], '/'));
                $path = reset($path);

                if($path)
                {
                    $qv['path'] = get_network()->path.$path.'/';
                }
            }

            $sites = get_sites($qv);
            $site = reset($sites);

            // Do not allow embeds for deleted/archived/spam sites.
            if(! empty($site->deleted) || ! empty($site->spam) || ! empty($site->archived))
            {
                return false;
            }

            if($site && get_current_blog_id() !== (int) $site->blog_id)
            {
                switch_to_blog($site->blog_id);
                $switched_blog = true;
            }
        }

        $post_id = url_to_postid($url);

        $post_id = apply_filters('oembed_request_post_id', $post_id, $url);

        if(! $post_id)
        {
            if($switched_blog)
            {
                restore_current_blog();
            }

            return false;
        }

        $width = isset($args['width']) ? $args['width'] : 0;

        $data = get_oembed_response_data($post_id, $width);

        if($switched_blog)
        {
            restore_current_blog();
        }

        return $data ? (object) $data : false;
    }

    function get_oembed_response_data_rich($data, $post, $width, $height)
    {
        $data['width'] = absint($width);
        $data['height'] = absint($height);
        $data['type'] = 'rich';
        $data['html'] = get_post_embed_html($width, $height, $post);

        // Add post thumbnail to response if available.
        $thumbnail_id = false;

        if(has_post_thumbnail($post->ID))
        {
            $thumbnail_id = get_post_thumbnail_id($post->ID);
        }

        if('attachment' === get_post_type($post))
        {
            if(wp_attachment_is_image($post))
            {
                $thumbnail_id = $post->ID;
            }
            elseif(wp_attachment_is('video', $post))
            {
                $thumbnail_id = get_post_thumbnail_id($post);
                $data['type'] = 'video';
            }
        }

        if($thumbnail_id)
        {
            [$thumbnail_url, $thumbnail_width, $thumbnail_height] = wp_get_attachment_image_src($thumbnail_id, [
                $width,
                99999
            ]);
            $data['thumbnail_url'] = $thumbnail_url;
            $data['thumbnail_width'] = $thumbnail_width;
            $data['thumbnail_height'] = $thumbnail_height;
        }

        return $data;
    }

    function wp_oembed_ensure_format($format)
    {
        if(! in_array($format, ['json', 'xml'], true))
        {
            return 'json';
        }

        return $format;
    }

    function _oembed_rest_pre_serve_request($served, $result, $request, $server)
    {
        $params = $request->get_params();

        if('/oembed/1.0/embed' !== $request->get_route() || 'GET' !== $request->get_method())
        {
            return $served;
        }

        if(! isset($params['format']) || 'xml' !== $params['format'])
        {
            return $served;
        }

        // Embed links inside the request.
        $data = $server->response_to_data($result, false);

        if(! class_exists('SimpleXMLElement'))
        {
            status_header(501);
            die(get_status_header_desc(501));
        }

        $result = _oembed_create_xml($data);

        // Bail if there's no XML.
        if(! $result)
        {
            status_header(501);

            return get_status_header_desc(501);
        }

        if(! headers_sent())
        {
            $server->send_header('Content-Type', 'text/xml; charset='.get_option('blog_charset'));
        }

        echo $result;

        return true;
    }

    function _oembed_create_xml($data, $node = null)
    {
        if(! is_array($data) || empty($data))
        {
            return false;
        }

        if(null === $node)
        {
            $node = new SimpleXMLElement('<oembed></oembed>');
        }

        foreach($data as $key => $value)
        {
            if(is_numeric($key))
            {
                $key = 'oembed';
            }

            if(is_array($value))
            {
                $item = $node->addChild($key);
                _oembed_create_xml($value, $item);
            }
            else
            {
                $node->addChild($key, esc_html($value));
            }
        }

        return $node->asXML();
    }

    function wp_filter_oembed_iframe_title_attribute($result, $data, $url)
    {
        if(false === $result || ! in_array($data->type, ['rich', 'video'], true))
        {
            return $result;
        }

        $title = ! empty($data->title) ? $data->title : '';

        $pattern = '`<iframe([^>]*)>`i';
        if(preg_match($pattern, $result, $matches))
        {
            $attrs = wp_kses_hair($matches[1], wp_allowed_protocols());

            foreach($attrs as $attr => $item)
            {
                $lower_attr = strtolower($attr);
                if($lower_attr === $attr)
                {
                    continue;
                }
                if(! isset($attrs[$lower_attr]))
                {
                    $attrs[$lower_attr] = $item;
                    unset($attrs[$attr]);
                }
            }
        }

        if(! empty($attrs['title']['value']))
        {
            $title = $attrs['title']['value'];
        }

        $title = apply_filters('oembed_iframe_title_attribute', $title, $result, $data, $url);

        if('' === $title)
        {
            return $result;
        }

        if(isset($attrs['title']))
        {
            unset($attrs['title']);
            $attr_string = implode(' ', wp_list_pluck($attrs, 'whole'));
            $result = str_replace($matches[0], '<iframe '.trim($attr_string).'>', $result);
        }

        return str_ireplace('<iframe ', sprintf('<iframe title="%s" ', esc_attr($title)), $result);
    }

    function wp_filter_oembed_result($result, $data, $url)
    {
        if(false === $result || ! in_array($data->type, ['rich', 'video'], true))
        {
            return $result;
        }

        $wp_oembed = _wp_oembed_get_object();

        // Don't modify the HTML for trusted providers.
        if(false !== $wp_oembed->get_provider($url, ['discover' => false]))
        {
            return $result;
        }

        $allowed_html = [
            'a' => [
                'href' => true,
            ],
            'blockquote' => [],
            'iframe' => [
                'src' => true,
                'width' => true,
                'height' => true,
                'frameborder' => true,
                'marginwidth' => true,
                'marginheight' => true,
                'scrolling' => true,
                'title' => true,
            ],
        ];

        $html = wp_kses($result, $allowed_html);

        preg_match('|(<blockquote>.*?</blockquote>)?.*(<iframe.*?></iframe>)|ms', $html, $content);
        // We require at least the iframe to exist.
        if(empty($content[2]))
        {
            return false;
        }
        $html = $content[1].$content[2];

        preg_match('/ src=([\'"])(.*?)\1/', $html, $results);

        if(! empty($results))
        {
            $secret = wp_generate_password(10, false);

            $url = esc_url("{$results[2]}#?secret=$secret");
            $q = $results[1];

            $html = str_replace($results[0], ' src='.$q.$url.$q.' data-secret='.$q.$secret.$q, $html);
            $html = str_replace('<blockquote', "<blockquote data-secret=\"$secret\"", $html);
        }

        $allowed_html['blockquote']['data-secret'] = true;
        $allowed_html['iframe']['data-secret'] = true;

        $html = wp_kses($html, $allowed_html);

        if(! empty($content[1]))
        {
            // We have a blockquote to fall back on. Hide the iframe by default.
            $html = str_replace('<iframe', '<iframe style="position: absolute; clip: rect(1px, 1px, 1px, 1px);"', $html);
            $html = str_replace('<blockquote', '<blockquote class="wp-embedded-content"', $html);
        }

        $html = str_ireplace('<iframe', '<iframe class="wp-embedded-content" sandbox="allow-scripts" security="restricted"', $html);

        return $html;
    }

    function wp_embed_excerpt_more($more_string)
    {
        if(! is_embed())
        {
            return $more_string;
        }

        $link = sprintf('<a href="%1$s" class="wp-embed-more" target="_top">%2$s</a>', esc_url(get_permalink()), /* translators: %s: Post title. */ sprintf(__('Continue reading %s'), '<span class="screen-reader-text">'.get_the_title().'</span>'));

        return ' &hellip; '.$link;
    }

    function the_excerpt_embed()
    {
        $output = get_the_excerpt();

        echo apply_filters('the_excerpt_embed', $output);
    }

    function wp_embed_excerpt_attachment($content)
    {
        if(is_attachment())
        {
            return prepend_attachment('');
        }

        return $content;
    }

    function enqueue_embed_scripts()
    {
        wp_enqueue_style('wp-embed-template-ie');

        do_action('enqueue_embed_scripts');
    }

    function print_embed_styles()
    {
        $type_attr = current_theme_supports('html5', 'style') ? '' : ' type="text/css"';
        $suffix = SCRIPT_DEBUG ? '' : '.min';
        ?>
        <style<?php echo $type_attr; ?>>
            <?php echo file_get_contents( ABSPATH . WPINC . "/css/wp-embed-template$suffix.css" ); ?>
        </style>
        <?php
    }

    function print_embed_scripts()
    {
        wp_print_inline_script_tag(file_get_contents(ABSPATH.WPINC.'/js/wp-embed-template'.wp_scripts_get_suffix().'.js'));
    }

    function _oembed_filter_feed_content($content)
    {
        return str_replace('<iframe class="wp-embedded-content" sandbox="allow-scripts" security="restricted" style="position: absolute; clip: rect(1px, 1px, 1px, 1px);"', '<iframe class="wp-embedded-content" sandbox="allow-scripts" security="restricted"', $content);
    }

    function print_embed_comments_button()
    {
        if(is_404() || ! (get_comments_number() || comments_open()))
        {
            return;
        }
        ?>
        <div class="wp-embed-comments">
            <a href="<?php comments_link(); ?>" target="_top">
                <span class="dashicons dashicons-admin-comments"></span>
                <?php
                    printf(/* translators: %s: Number of comments. */ _n('%s <span class="screen-reader-text">Comment</span>', '%s <span class="screen-reader-text">Comments</span>', get_comments_number()), number_format_i18n(get_comments_number()));
                ?>
            </a>
        </div>
        <?php
    }

    function print_embed_sharing_button()
    {
        if(is_404())
        {
            return;
        }
        ?>
        <div class="wp-embed-share">
            <button type="button"
                    class="wp-embed-share-dialog-open"
                    aria-label="<?php esc_attr_e('Open sharing dialog'); ?>">
                <span class="dashicons dashicons-share"></span>
            </button>
        </div>
        <?php
    }

    function print_embed_sharing_dialog()
    {
        if(is_404())
        {
            return;
        }

        $unique_suffix = get_the_ID().'-'.wp_rand();
        $share_tab_wordpress_id = 'wp-embed-share-tab-wordpress-'.$unique_suffix;
        $share_tab_html_id = 'wp-embed-share-tab-html-'.$unique_suffix;
        $description_wordpress_id = 'wp-embed-share-description-wordpress-'.$unique_suffix;
        $description_html_id = 'wp-embed-share-description-html-'.$unique_suffix;
        ?>
        <div class="wp-embed-share-dialog hidden" role="dialog" aria-label="<?php esc_attr_e('Sharing options'); ?>">
            <div class="wp-embed-share-dialog-content">
                <div class="wp-embed-share-dialog-text">
                    <ul class="wp-embed-share-tabs" role="tablist">
                        <li class="wp-embed-share-tab-button wp-embed-share-tab-button-wordpress" role="presentation">
                            <button type="button"
                                    role="tab"
                                    aria-controls="<?php echo $share_tab_wordpress_id; ?>"
                                    aria-selected="true"
                                    tabindex="0"><?php esc_html_e('WordPress Embed'); ?></button>
                        </li>
                        <li class="wp-embed-share-tab-button wp-embed-share-tab-button-html" role="presentation">
                            <button type="button"
                                    role="tab"
                                    aria-controls="<?php echo $share_tab_html_id; ?>"
                                    aria-selected="false"
                                    tabindex="-1"><?php esc_html_e('HTML Embed'); ?></button>
                        </li>
                    </ul>
                    <div id="<?php echo $share_tab_wordpress_id; ?>"
                         class="wp-embed-share-tab"
                         role="tabpanel"
                         aria-hidden="false">
                        <input type="text"
                               value="<?php the_permalink(); ?>"
                               class="wp-embed-share-input"
                               aria-label="<?php esc_attr_e('URL'); ?>"
                               aria-describedby="<?php echo $description_wordpress_id; ?>"
                               tabindex="0"
                               readonly/>

                        <p class="wp-embed-share-description" id="<?php echo $description_wordpress_id; ?>">
                            <?php _e('Copy and paste this URL into your WordPress site to embed'); ?>
                        </p>
                    </div>
                    <div id="<?php echo $share_tab_html_id; ?>"
                         class="wp-embed-share-tab"
                         role="tabpanel"
                         aria-hidden="true">
                        <textarea class="wp-embed-share-input"
                                  aria-label="<?php esc_attr_e('HTML'); ?>"
                                  aria-describedby="<?php echo $description_html_id; ?>"
                                  tabindex="0"
                                  readonly><?php echo esc_textarea(get_post_embed_html(600, 400)); ?></textarea>

                        <p class="wp-embed-share-description" id="<?php echo $description_html_id; ?>">
                            <?php _e('Copy and paste this code into your site to embed'); ?>
                        </p>
                    </div>
                </div>

                <button type="button"
                        class="wp-embed-share-dialog-close"
                        aria-label="<?php esc_attr_e('Close sharing dialog'); ?>">
                    <span class="dashicons dashicons-no"></span>
                </button>
            </div>
        </div>
        <?php
    }

    function the_embed_site_title()
    {
        $site_title = sprintf('<a href="%s" target="_top"><img src="%s" srcset="%s 2x" width="32" height="32" alt="" class="wp-embed-site-icon" /><span>%s</span></a>', esc_url(home_url()), esc_url(get_site_icon_url(32, includes_url('images/w-logo-blue.png'))), esc_url(get_site_icon_url(64, includes_url('images/w-logo-blue.png'))), esc_html(get_bloginfo('name')));

        $site_title = '<div class="wp-embed-site-title">'.$site_title.'</div>';

        echo apply_filters('embed_site_title_html', $site_title);
    }

    function wp_filter_pre_oembed_result($result, $url, $args)
    {
        $data = get_oembed_response_data_for_url($url, $args);

        if($data)
        {
            return _wp_oembed_get_object()->data2html($data, $url);
        }

        return $result;
    }
