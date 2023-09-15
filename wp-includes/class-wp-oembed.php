<?php

    #[AllowDynamicProperties]
    class WP_oEmbed
    {
        public static $early_providers = [];

        public $providers = [];

        private $compat_methods = ['_fetch_with_format', '_parse_json', '_parse_xml', '_parse_xml_body'];

        public function __construct()
        {
            $host = urlencode(home_url());
            $providers = [
                '#https?://((m|www)\.)?youtube\.com/watch.*#i' => ['https://www.youtube.com/oembed', true],
                '#https?://((m|www)\.)?youtube\.com/playlist.*#i' => ['https://www.youtube.com/oembed', true],
                '#https?://((m|www)\.)?youtube\.com/shorts/*#i' => ['https://www.youtube.com/oembed', true],
                '#https?://((m|www)\.)?youtube\.com/live/*#i' => ['https://www.youtube.com/oembed', true],
                '#https?://youtu\.be/.*#i' => ['https://www.youtube.com/oembed', true],
                '#https?://(.+\.)?vimeo\.com/.*#i' => ['https://vimeo.com/api/oembed.{format}', true],
                '#https?://(www\.)?dailymotion\.com/.*#i' => ['https://www.dailymotion.com/services/oembed', true],
                '#https?://dai\.ly/.*#i' => ['https://www.dailymotion.com/services/oembed', true],
                '#https?://(www\.)?flickr\.com/.*#i' => ['https://www.flickr.com/services/oembed/', true],
                '#https?://flic\.kr/.*#i' => ['https://www.flickr.com/services/oembed/', true],
                '#https?://(.+\.)?smugmug\.com/.*#i' => ['https://api.smugmug.com/services/oembed/', true],
                '#https?://(www\.)?scribd\.com/(doc|document)/.*#i' => ['https://www.scribd.com/services/oembed', true],
                '#https?://wordpress\.tv/.*#i' => ['https://wordpress.tv/oembed/', true],
                '#https?://(.+\.)?crowdsignal\.net/.*#i' => ['https://api.crowdsignal.com/oembed', true],
                '#https?://(.+\.)?polldaddy\.com/.*#i' => ['https://api.crowdsignal.com/oembed', true],
                '#https?://poll\.fm/.*#i' => ['https://api.crowdsignal.com/oembed', true],
                '#https?://(.+\.)?survey\.fm/.*#i' => ['https://api.crowdsignal.com/oembed', true],
                '#https?://(www\.)?twitter\.com/\w{1,15}/status(es)?/.*#i' => [
                    'https://publish.twitter.com/oembed',
                    true
                ],
                '#https?://(www\.)?twitter\.com/\w{1,15}$#i' => ['https://publish.twitter.com/oembed', true],
                '#https?://(www\.)?twitter\.com/\w{1,15}/likes$#i' => ['https://publish.twitter.com/oembed', true],
                '#https?://(www\.)?twitter\.com/\w{1,15}/lists/.*#i' => ['https://publish.twitter.com/oembed', true],
                '#https?://(www\.)?twitter\.com/\w{1,15}/timelines/.*#i' => [
                    'https://publish.twitter.com/oembed',
                    true
                ],
                '#https?://(www\.)?twitter\.com/i/moments/.*#i' => ['https://publish.twitter.com/oembed', true],
                '#https?://(www\.)?soundcloud\.com/.*#i' => ['https://soundcloud.com/oembed', true],
                '#https?://(.+?\.)?slideshare\.net/.*#i' => ['https://www.slideshare.net/api/oembed/2', true],
                '#https?://(open|play)\.spotify\.com/.*#i' => ['https://embed.spotify.com/oembed/', true],
                '#https?://(.+\.)?imgur\.com/.*#i' => ['https://api.imgur.com/oembed', true],
                '#https?://(www\.)?issuu\.com/.+/docs/.+#i' => ['https://issuu.com/oembed_wp', true],
                '#https?://(www\.)?mixcloud\.com/.*#i' => ['https://app.mixcloud.com/oembed/', true],
                '#https?://(www\.|embed\.)?ted\.com/talks/.*#i' => [
                    'https://www.ted.com/services/v1/oembed.{format}',
                    true
                ],
                '#https?://(www\.)?(animoto|video214)\.com/play/.*#i' => ['https://animoto.com/oembeds/create', true],
                '#https?://(.+)\.tumblr\.com/.*#i' => ['https://www.tumblr.com/oembed/1.0', true],
                '#https?://(www\.)?kickstarter\.com/projects/.*#i' => [
                    'https://www.kickstarter.com/services/oembed',
                    true
                ],
                '#https?://kck\.st/.*#i' => ['https://www.kickstarter.com/services/oembed', true],
                '#https?://cloudup\.com/.*#i' => ['https://cloudup.com/oembed', true],
                '#https?://(www\.)?reverbnation\.com/.*#i' => ['https://www.reverbnation.com/oembed', true],
                '#https?://videopress\.com/v/.*#' => ['https://public-api.wordpress.com/oembed/?for='.$host, true],
                '#https?://(www\.)?reddit\.com/r/[^/]+/comments/.*#i' => ['https://www.reddit.com/oembed', true],
                '#https?://(www\.)?speakerdeck\.com/.*#i' => ['https://speakerdeck.com/oembed.{format}', true],
                '#https?://(www\.)?screencast\.com/.*#i' => ['https://api.screencast.com/external/oembed', true],
                '#https?://([a-z0-9-]+\.)?amazon\.(com|com\.mx|com\.br|ca)/.*#i' => [
                    'https://read.amazon.com/kp/api/oembed',
                    true
                ],
                '#https?://([a-z0-9-]+\.)?amazon\.(co\.uk|de|fr|it|es|in|nl|ru)/.*#i' => [
                    'https://read.amazon.co.uk/kp/api/oembed',
                    true
                ],
                '#https?://([a-z0-9-]+\.)?amazon\.(co\.jp|com\.au)/.*#i' => [
                    'https://read.amazon.com.au/kp/api/oembed',
                    true
                ],
                '#https?://([a-z0-9-]+\.)?amazon\.cn/.*#i' => ['https://read.amazon.cn/kp/api/oembed', true],
                '#https?://(www\.)?a\.co/.*#i' => ['https://read.amazon.com/kp/api/oembed', true],
                '#https?://(www\.)?amzn\.to/.*#i' => ['https://read.amazon.com/kp/api/oembed', true],
                '#https?://(www\.)?amzn\.eu/.*#i' => ['https://read.amazon.co.uk/kp/api/oembed', true],
                '#https?://(www\.)?amzn\.in/.*#i' => ['https://read.amazon.in/kp/api/oembed', true],
                '#https?://(www\.)?amzn\.asia/.*#i' => ['https://read.amazon.com.au/kp/api/oembed', true],
                '#https?://(www\.)?z\.cn/.*#i' => ['https://read.amazon.cn/kp/api/oembed', true],
                '#https?://www\.someecards\.com/.+-cards/.+#i' => ['https://www.someecards.com/v2/oembed/', true],
                '#https?://www\.someecards\.com/usercards/viewcard/.+#i' => [
                    'https://www.someecards.com/v2/oembed/',
                    true
                ],
                '#https?://some\.ly\/.+#i' => ['https://www.someecards.com/v2/oembed/', true],
                '#https?://(www\.)?tiktok\.com/.*/video/.*#i' => ['https://www.tiktok.com/oembed', true],
                '#https?://(www\.)?tiktok\.com/@.*#i' => ['https://www.tiktok.com/oembed', true],
                '#https?://([a-z]{2}|www)\.pinterest\.com(\.(au|mx))?/.*#i' => [
                    'https://www.pinterest.com/oembed.json',
                    true
                ],
                '#https?://(www\.)?wolframcloud\.com/obj/.+#i' => ['https://www.wolframcloud.com/oembed', true],
                '#https?://pca\.st/.+#i' => ['https://pca.st/oembed.json', true],
                '#https?://((play|www)\.)?anghami\.com/.*#i' => ['https://api.anghami.com/rest/v1/oembed.view', true],
            ];

            if(! empty(self::$early_providers['add']))
            {
                foreach(self::$early_providers['add'] as $format => $data)
                {
                    $providers[$format] = $data;
                }
            }

            if(! empty(self::$early_providers['remove']))
            {
                foreach(self::$early_providers['remove'] as $format)
                {
                    unset($providers[$format]);
                }
            }

            self::$early_providers = [];

            $this->providers = apply_filters('oembed_providers', $providers);

            // Fix any embeds that contain new lines in the middle of the HTML which breaks wpautop().
            add_filter('oembed_dataparse', [$this, '_strip_newlines'], 10, 3);
        }

        public static function _add_provider_early($format, $provider, $regex = false)
        {
            if(empty(self::$early_providers['add']))
            {
                self::$early_providers['add'] = [];
            }

            self::$early_providers['add'][$format] = [$provider, $regex];
        }

        public static function _remove_provider_early($format)
        {
            if(empty(self::$early_providers['remove']))
            {
                self::$early_providers['remove'] = [];
            }

            self::$early_providers['remove'][] = $format;
        }

        public function __call($name, $arguments)
        {
            if(in_array($name, $this->compat_methods, true))
            {
                return $this->$name(...$arguments);
            }

            return false;
        }

        public function get_html($url, $args = '')
        {
            $pre = apply_filters('pre_oembed_result', null, $url, $args);

            if(null !== $pre)
            {
                return $pre;
            }

            $data = $this->get_data($url, $args);

            if(false === $data)
            {
                return false;
            }

            return apply_filters('oembed_result', $this->data2html($data, $url), $url, $args);
        }

        public function get_data($url, $args = '')
        {
            $args = wp_parse_args($args);

            $provider = $this->get_provider($url, $args);

            if(! $provider)
            {
                return false;
            }

            $data = $this->fetch($provider, $url, $args);

            if(false === $data)
            {
                return false;
            }

            return $data;
        }

        public function get_provider($url, $args = '')
        {
            $args = wp_parse_args($args);

            $provider = false;

            if(! isset($args['discover']))
            {
                $args['discover'] = true;
            }

            foreach($this->providers as $matchmask => $data)
            {
                [$providerurl, $regex] = $data;

                // Turn the asterisk-type provider URLs into regex.
                if(! $regex)
                {
                    $matchmask = '#'.str_replace('___wildcard___', '(.+)', preg_quote(str_replace('*', '___wildcard___', $matchmask), '#')).'#i';
                    $matchmask = preg_replace('|^#http\\\://|', '#https?\://', $matchmask);
                }

                if(preg_match($matchmask, $url))
                {
                    $provider = str_replace('{format}', 'json', $providerurl); // JSON is easier to deal with than XML.
                    break;
                }
            }

            if(! $provider && $args['discover'])
            {
                $provider = $this->discover($url);
            }

            return $provider;
        }

        public function discover($url)
        {
            $providers = [];
            $args = [
                'limit_response_size' => 153600, // 150 KB
            ];

            $args = apply_filters('oembed_remote_get_args', $args, $url);

            // Fetch URL content.
            $request = wp_safe_remote_get($url, $args);
            $html = wp_remote_retrieve_body($request);
            if($html)
            {
                $linktypes = apply_filters('oembed_linktypes', [
                    'application/json+oembed' => 'json',
                    'text/xml+oembed' => 'xml',
                    'application/xml+oembed' => 'xml',
                ]);

                // Strip <body>.
                $html_head_end = stripos($html, '</head>');
                if($html_head_end)
                {
                    $html = substr($html, 0, $html_head_end);
                }

                // Do a quick check.
                $tagfound = false;
                foreach($linktypes as $linktype => $format)
                {
                    if(stripos($html, $linktype))
                    {
                        $tagfound = true;
                        break;
                    }
                }

                if($tagfound && preg_match_all('#<link([^<>]+)/?>#iU', $html, $links))
                {
                    foreach($links[1] as $link)
                    {
                        $atts = shortcode_parse_atts($link);

                        if(! empty($atts['type']) && ! empty($linktypes[$atts['type']]) && ! empty($atts['href']))
                        {
                            $providers[$linktypes[$atts['type']]] = htmlspecialchars_decode($atts['href']);

                            // Stop here if it's JSON (that's all we need).
                            if('json' === $linktypes[$atts['type']])
                            {
                                break;
                            }
                        }
                    }
                }
            }

            // JSON is preferred to XML.
            if(! empty($providers['json']))
            {
                return $providers['json'];
            }
            elseif(! empty($providers['xml']))
            {
                return $providers['xml'];
            }
            else
            {
                return false;
            }
        }

        public function fetch($provider, $url, $args = '')
        {
            $args = wp_parse_args($args, wp_embed_defaults($url));

            $provider = add_query_arg('maxwidth', (int) $args['width'], $provider);
            $provider = add_query_arg('maxheight', (int) $args['height'], $provider);
            $provider = add_query_arg('url', urlencode($url), $provider);
            $provider = add_query_arg('dnt', 1, $provider);

            $provider = apply_filters('oembed_fetch_url', $provider, $url, $args);

            foreach(['json', 'xml'] as $format)
            {
                $result = $this->_fetch_with_format($provider, $format);
                if(is_wp_error($result) && 'not-implemented' === $result->get_error_code())
                {
                    continue;
                }

                return ($result && ! is_wp_error($result)) ? $result : false;
            }

            return false;
        }

        private function _fetch_with_format($provider_url_with_args, $format)
        {
            $provider_url_with_args = add_query_arg('format', $format, $provider_url_with_args);

            $args = apply_filters('oembed_remote_get_args', [], $provider_url_with_args);

            $response = wp_safe_remote_get($provider_url_with_args, $args);

            if(501 === wp_remote_retrieve_response_code($response))
            {
                return new WP_Error('not-implemented');
            }

            $body = wp_remote_retrieve_body($response);
            if(! $body)
            {
                return false;
            }

            $parse_method = "_parse_$format";

            return $this->$parse_method($body);
        }

        public function data2html($data, $url)
        {
            if(! is_object($data) || empty($data->type))
            {
                return false;
            }

            $return = false;

            switch($data->type)
            {
                case 'photo':
                    if(empty($data->url) || empty($data->width) || empty($data->height))
                    {
                        break;
                    }
                    if(! is_string($data->url) || ! is_numeric($data->width) || ! is_numeric($data->height))
                    {
                        break;
                    }

                    $title = ! empty($data->title) && is_string($data->title) ? $data->title : '';
                    $return = '<a href="'.esc_url($url).'"><img src="'.esc_url($data->url).'" alt="'.esc_attr($title).'" width="'.esc_attr($data->width).'" height="'.esc_attr($data->height).'" /></a>';
                    break;

                case 'video':
                case 'rich':
                    if(! empty($data->html) && is_string($data->html))
                    {
                        $return = $data->html;
                    }
                    break;

                case 'link':
                    if(! empty($data->title) && is_string($data->title))
                    {
                        $return = '<a href="'.esc_url($url).'">'.esc_html($data->title).'</a>';
                    }
                    break;

                default:
                    $return = false;
            }

            return apply_filters('oembed_dataparse', $return, $data, $url);
        }

        public function _strip_newlines($html, $data, $url)
        {
            if(! str_contains($html, "\n"))
            {
                return $html;
            }

            $count = 1;
            $found = [];
            $token = '__PRE__';
            $search = ["\t", "\n", "\r", ' '];
            $replace = ['__TAB__', '__NL__', '__CR__', '__SPACE__'];
            $tokenized = str_replace($search, $replace, $html);

            preg_match_all('#(<pre[^>]*>.+?</pre>)#i', $tokenized, $matches, PREG_SET_ORDER);
            foreach($matches as $i => $match)
            {
                $tag_html = str_replace($replace, $search, $match[0]);
                $tag_token = $token.$i;

                $found[$tag_token] = $tag_html;
                $html = str_replace($tag_html, $tag_token, $html, $count);
            }

            $replaced = str_replace($replace, $search, $html);
            $stripped = str_replace(["\r\n", "\n"], '', $replaced);
            $pre = array_values($found);
            $tokens = array_keys($found);

            return str_replace($tokens, $pre, $stripped);
        }

        private function _parse_json($response_body)
        {
            $data = json_decode(trim($response_body));

            return ($data && is_object($data)) ? $data : false;
        }

        private function _parse_xml($response_body)
        {
            if(! function_exists('libxml_disable_entity_loader'))
            {
                return false;
            }

            if(PHP_VERSION_ID < 80000)
            {
                /*
                 * This function has been deprecated in PHP 8.0 because in libxml 2.9.0, external entity loading
                 * is disabled by default, so this function is no longer needed to protect against XXE attacks.
                 */
                $loader = libxml_disable_entity_loader(true);
            }

            $errors = libxml_use_internal_errors(true);

            $return = $this->_parse_xml_body($response_body);

            libxml_use_internal_errors($errors);

            if(PHP_VERSION_ID < 80000 && isset($loader))
            {
                // phpcs:ignore PHPCompatibility.FunctionUse.RemovedFunctions.libxml_disable_entity_loaderDeprecated
                libxml_disable_entity_loader($loader);
            }

            return $return;
        }

        private function _parse_xml_body($response_body)
        {
            if(! function_exists('simplexml_import_dom') || ! class_exists('DOMDocument', false))
            {
                return false;
            }

            $dom = new DOMDocument();
            $success = $dom->loadXML($response_body);
            if(! $success)
            {
                return false;
            }

            if(isset($dom->doctype))
            {
                return false;
            }

            foreach($dom->childNodes as $child)
            {
                if(XML_DOCUMENT_TYPE_NODE === $child->nodeType)
                {
                    return false;
                }
            }

            $xml = simplexml_import_dom($dom);
            if(! $xml)
            {
                return false;
            }

            $return = new stdClass();
            foreach($xml as $key => $value)
            {
                $return->$key = (string) $value;
            }

            return $return;
        }
    }
