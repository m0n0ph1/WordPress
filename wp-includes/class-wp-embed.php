<?php

    #[AllowDynamicProperties]
    class WP_Embed
    {
        public $handlers = [];

        public $post_ID;

        public $usecache = true;

        public $linkifunknown = true;

        public $last_attr = [];

        public $last_url = '';

        public $return_false_on_fail = false;

        public function __construct()
        {
            // Hack to get the [embed] shortcode to run before wpautop().
            add_filter('the_content', [$this, 'run_shortcode'], 8);
            add_filter('widget_text_content', [$this, 'run_shortcode'], 8);
            add_filter('widget_block_content', [$this, 'run_shortcode'], 8);

            // Shortcode placeholder for strip_shortcodes().
            add_shortcode('embed', '__return_false');

            // Attempts to embed all URLs in a post.
            add_filter('the_content', [$this, 'autoembed'], 8);
            add_filter('widget_text_content', [$this, 'autoembed'], 8);
            add_filter('widget_block_content', [$this, 'autoembed'], 8);

            // After a post is saved, cache oEmbed items via Ajax.
            add_action('edit_form_advanced', [$this, 'maybe_run_ajax_cache']);
            add_action('edit_page_form', [$this, 'maybe_run_ajax_cache']);
        }

        public function maybe_run_ajax_cache()
        {
            $post = get_post();

            if(! $post || empty($_GET['message']))
            {
                return;
            }
            ?>
            <script type="text/javascript">
                jQuery(function ($) {
                    $.get("<?php echo esc_url(admin_url('admin-ajax.php', 'relative')).'?action=oembed-cache&post='.$post->ID; ?>");
                });
            </script>
            <?php
        }

        public function register_handler($id, $regex, $callback, $priority = 10)
        {
            $this->handlers[$priority][$id] = compact('regex', 'callback');
        }

        public function unregister_handler($id, $priority = 10)
        {
            unset($this->handlers[$priority][$id]);
        }

        public function delete_oembed_caches($post_id)
        {
            $post_metas = get_post_custom_keys($post_id);
            if(empty($post_metas))
            {
                return;
            }

            foreach($post_metas as $post_meta_key)
            {
                if(str_starts_with($post_meta_key, '_oembed_'))
                {
                    delete_post_meta($post_id, $post_meta_key);
                }
            }
        }

        public function cache_oembed($post_id)
        {
            $post = get_post($post_id);

            $post_types = get_post_types(['show_ui' => true]);

            $cache_oembed_types = apply_filters('embed_cache_oembed_types', $post_types);

            if(empty($post->ID) || ! in_array($post->post_type, $cache_oembed_types, true))
            {
                return;
            }

            // Trigger a caching.
            if(! empty($post->post_content))
            {
                $this->post_ID = $post->ID;
                $this->usecache = false;

                $content = $this->run_shortcode($post->post_content);
                $this->autoembed($content);

                $this->usecache = true;
            }
        }

        public function run_shortcode($content)
        {
            global $shortcode_tags;

            // Back up current registered shortcodes and clear them all out.
            $orig_shortcode_tags = $shortcode_tags;
            remove_all_shortcodes();

            add_shortcode('embed', [$this, 'shortcode']);

            // Do the shortcode (only the [embed] one is registered).
            $content = do_shortcode($content, true);

            // Put the original shortcodes back.
            $shortcode_tags = $orig_shortcode_tags;

            return $content;
        }

        public function autoembed($content)
        {
            // Replace line breaks from all HTML elements with placeholders.
            $content = wp_replace_in_html_tags($content, ["\n" => '<!-- wp-line-break -->']);

            if(preg_match('#(^|\s|>)https?://#i', $content))
            {
                // Find URLs on their own line.
                $content = preg_replace_callback('|^(\s*)(https?://[^\s<>"]+)(\s*)$|im', [
                    $this,
                    'autoembed_callback'
                ],                               $content);
                // Find URLs in their own paragraph.
                $content = preg_replace_callback('|(<p(?: [^>]*)?>\s*)(https?://[^\s<>"]+)(\s*<\/p>)|i', [
                    $this,
                    'autoembed_callback'
                ],                               $content);
            }

            // Put the line breaks back.
            return str_replace('<!-- wp-line-break -->', "\n", $content);
        }

        public function autoembed_callback($matches)
        {
            $oldval = $this->linkifunknown;
            $this->linkifunknown = false;
            $return = $this->shortcode([], $matches[2]);
            $this->linkifunknown = $oldval;

            return $matches[1].$return.$matches[3];
        }

        public function shortcode($attr, $url = '')
        {
            $post = get_post();

            if(empty($url) && ! empty($attr['src']))
            {
                $url = $attr['src'];
            }

            $this->last_url = $url;

            if(empty($url))
            {
                $this->last_attr = $attr;

                return '';
            }

            $rawattr = $attr;
            $attr = wp_parse_args($attr, wp_embed_defaults($url));

            $this->last_attr = $attr;

            /*
             * KSES converts & into &amp; and we need to undo this.
             * See https://core.trac.wordpress.org/ticket/11311
             */
            $url = str_replace('&amp;', '&', $url);

            // Look for known internal handlers.
            $embed_handler_html = $this->get_embed_handler_html($rawattr, $url);
            if(false !== $embed_handler_html)
            {
                return $embed_handler_html;
            }

            $post_id = (! empty($post->ID)) ? $post->ID : null;

            // Potentially set by WP_Embed::cache_oembed().
            if(! empty($this->post_ID))
            {
                $post_id = $this->post_ID;
            }

            // Check for a cached result (stored as custom post or in the post meta).
            $key_suffix = md5($url.serialize($attr));
            $cachekey = '_oembed_'.$key_suffix;
            $cachekey_time = '_oembed_time_'.$key_suffix;

            $ttl = apply_filters('oembed_ttl', DAY_IN_SECONDS, $url, $attr, $post_id);

            $cache = '';
            $cache_time = 0;

            $cached_post_id = $this->find_oembed_post_id($key_suffix);

            if($post_id)
            {
                $cache = get_post_meta($post_id, $cachekey, true);
                $cache_time = get_post_meta($post_id, $cachekey_time, true);

                if(! $cache_time)
                {
                    $cache_time = 0;
                }
            }
            elseif($cached_post_id)
            {
                $cached_post = get_post($cached_post_id);

                $cache = $cached_post->post_content;
                $cache_time = strtotime($cached_post->post_modified_gmt);
            }

            $cached_recently = (time() - $cache_time) < $ttl;

            if($this->usecache || $cached_recently)
            {
                // Failures are cached. Serve one if we're using the cache.
                if('{{unknown}}' === $cache)
                {
                    return $this->maybe_make_link($url);
                }

                if(! empty($cache))
                {
                    return apply_filters('embed_oembed_html', $cache, $url, $attr, $post_id);
                }
            }

            $attr['discover'] = apply_filters('embed_oembed_discover', true);

            // Use oEmbed to get the HTML.
            $html = wp_oembed_get($url, $attr);

            if($post_id)
            {
                if($html)
                {
                    update_post_meta($post_id, $cachekey, $html);
                    update_post_meta($post_id, $cachekey_time, time());
                }
                elseif(! $cache)
                {
                    update_post_meta($post_id, $cachekey, '{{unknown}}');
                }
            }
            else
            {
                $has_kses = false !== has_filter('content_save_pre', 'wp_filter_post_kses');

                if($has_kses)
                {
                    // Prevent KSES from corrupting JSON in post_content.
                    kses_remove_filters();
                }

                $insert_post_args = [
                    'post_name' => $key_suffix,
                    'post_status' => 'publish',
                    'post_type' => 'oembed_cache',
                ];

                if($html)
                {
                    if($cached_post_id)
                    {
                        wp_update_post(
                            wp_slash([
                                         'ID' => $cached_post_id,
                                         'post_content' => $html,
                                     ])
                        );
                    }
                    else
                    {
                        wp_insert_post(
                            wp_slash(
                                array_merge($insert_post_args, [
                                    'post_content' => $html,
                                ])
                            )
                        );
                    }
                }
                elseif(! $cache)
                {
                    wp_insert_post(
                        wp_slash(
                            array_merge($insert_post_args, [
                                'post_content' => '{{unknown}}',
                            ])
                        )
                    );
                }

                if($has_kses)
                {
                    kses_init_filters();
                }
            }

            // If there was a result, return it.
            if($html)
            {
                return apply_filters('embed_oembed_html', $html, $url, $attr, $post_id);
            }

            // Still unknown.
            return $this->maybe_make_link($url);
        }

        public function get_embed_handler_html($attr, $url)
        {
            $rawattr = $attr;
            $attr = wp_parse_args($attr, wp_embed_defaults($url));

            ksort($this->handlers);
            foreach($this->handlers as $priority => $handlers)
            {
                foreach($handlers as $id => $handler)
                {
                    if(preg_match($handler['regex'], $url, $matches) && is_callable($handler['callback']))
                    {
                        $return = call_user_func($handler['callback'], $matches, $attr, $url, $rawattr);
                        if(false !== $return)
                        {
                            return apply_filters('embed_handler_html', $return, $url, $attr);
                        }
                    }
                }
            }

            return false;
        }

        public function find_oembed_post_id($cache_key)
        {
            $cache_group = 'oembed_cache_post';
            $oembed_post_id = wp_cache_get($cache_key, $cache_group);

            if($oembed_post_id && 'oembed_cache' === get_post_type($oembed_post_id))
            {
                return $oembed_post_id;
            }

            $oembed_post_query = new WP_Query([
                                                  'post_type' => 'oembed_cache',
                                                  'post_status' => 'publish',
                                                  'name' => $cache_key,
                                                  'posts_per_page' => 1,
                                                  'no_found_rows' => true,
                                                  'cache_results' => true,
                                                  'update_post_meta_cache' => false,
                                                  'update_post_term_cache' => false,
                                                  'lazy_load_term_meta' => false,
                                              ]);

            if(! empty($oembed_post_query->posts))
            {
                // Note: 'fields' => 'ids' is not being used in order to cache the post object as it will be needed.
                $oembed_post_id = $oembed_post_query->posts[0]->ID;
                wp_cache_set($cache_key, $oembed_post_id, $cache_group);

                return $oembed_post_id;
            }

            return null;
        }

        public function maybe_make_link($url)
        {
            if($this->return_false_on_fail)
            {
                return false;
            }

            $output = ($this->linkifunknown) ? '<a href="'.esc_url($url).'">'.esc_html($url).'</a>' : $url;

            return apply_filters('embed_maybe_make_link', $output, $url);
        }
    }
