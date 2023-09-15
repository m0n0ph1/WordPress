<?php
    if(! class_exists('SimplePie', false)) :

// Load classes we will need.
        require ABSPATH.WPINC.'/SimplePie/Misc.php';
        require ABSPATH.WPINC.'/SimplePie/Cache.php';
        require ABSPATH.WPINC.'/SimplePie/File.php';
        require ABSPATH.WPINC.'/SimplePie/Sanitize.php';
        require ABSPATH.WPINC.'/SimplePie/Registry.php';
        require ABSPATH.WPINC.'/SimplePie/IRI.php';
        require ABSPATH.WPINC.'/SimplePie/Locator.php';
        require ABSPATH.WPINC.'/SimplePie/Content/Type/Sniffer.php';
        require ABSPATH.WPINC.'/SimplePie/XML/Declaration/Parser.php';
        require ABSPATH.WPINC.'/SimplePie/Parser.php';
        require ABSPATH.WPINC.'/SimplePie/Item.php';
        require ABSPATH.WPINC.'/SimplePie/Parse/Date.php';
        require ABSPATH.WPINC.'/SimplePie/Author.php';

        function wp_simplepie_autoload($class)
        {
            if(! str_starts_with($class, 'SimplePie_'))
            {
                return;
            }

            $file = ABSPATH.WPINC.'/'.str_replace('_', '/', $class).'.php';
            include $file;
        }

        spl_autoload_register('wp_simplepie_autoload');

        define('SIMPLEPIE_NAME', 'SimplePie');

        define('SIMPLEPIE_VERSION', '1.5.8');

        define('SIMPLEPIE_BUILD', gmdate('YmdHis', SimplePie_Misc::get_build()));

        define('SIMPLEPIE_URL', 'http://simplepie.org');

        define('SIMPLEPIE_USERAGENT', SIMPLEPIE_NAME.'/'.SIMPLEPIE_VERSION.' (Feed Parser; '.SIMPLEPIE_URL.'; Allow like Gecko) Build/'.SIMPLEPIE_BUILD);

        define('SIMPLEPIE_LINKBACK', '<a href="'.SIMPLEPIE_URL.'" title="'.SIMPLEPIE_NAME.' '.SIMPLEPIE_VERSION.'">'.SIMPLEPIE_NAME.'</a>');

        define('SIMPLEPIE_LOCATOR_NONE', 0);

        define('SIMPLEPIE_LOCATOR_AUTODISCOVERY', 1);

        define('SIMPLEPIE_LOCATOR_LOCAL_EXTENSION', 2);

        define('SIMPLEPIE_LOCATOR_LOCAL_BODY', 4);

        define('SIMPLEPIE_LOCATOR_REMOTE_EXTENSION', 8);

        define('SIMPLEPIE_LOCATOR_REMOTE_BODY', 16);

        define('SIMPLEPIE_LOCATOR_ALL', 31);

        define('SIMPLEPIE_TYPE_NONE', 0);

        define('SIMPLEPIE_TYPE_RSS_090', 1);

        define('SIMPLEPIE_TYPE_RSS_091_NETSCAPE', 2);

        define('SIMPLEPIE_TYPE_RSS_091_USERLAND', 4);

        define('SIMPLEPIE_TYPE_RSS_091', 6);

        define('SIMPLEPIE_TYPE_RSS_092', 8);

        define('SIMPLEPIE_TYPE_RSS_093', 16);

        define('SIMPLEPIE_TYPE_RSS_094', 32);

        define('SIMPLEPIE_TYPE_RSS_10', 64);

        define('SIMPLEPIE_TYPE_RSS_20', 128);

        define('SIMPLEPIE_TYPE_RSS_RDF', 65);

        define('SIMPLEPIE_TYPE_RSS_SYNDICATION', 190);

        define('SIMPLEPIE_TYPE_RSS_ALL', 255);

        define('SIMPLEPIE_TYPE_ATOM_03', 256);

        define('SIMPLEPIE_TYPE_ATOM_10', 512);

        define('SIMPLEPIE_TYPE_ATOM_ALL', 768);

        define('SIMPLEPIE_TYPE_ALL', 1023);

        define('SIMPLEPIE_CONSTRUCT_NONE', 0);

        define('SIMPLEPIE_CONSTRUCT_TEXT', 1);

        define('SIMPLEPIE_CONSTRUCT_HTML', 2);

        define('SIMPLEPIE_CONSTRUCT_XHTML', 4);

        define('SIMPLEPIE_CONSTRUCT_BASE64', 8);

        define('SIMPLEPIE_CONSTRUCT_IRI', 16);

        define('SIMPLEPIE_CONSTRUCT_MAYBE_HTML', 32);

        define('SIMPLEPIE_CONSTRUCT_ALL', 63);

        define('SIMPLEPIE_SAME_CASE', 1);

        define('SIMPLEPIE_LOWERCASE', 2);

        define('SIMPLEPIE_UPPERCASE', 4);

        define('SIMPLEPIE_PCRE_HTML_ATTRIBUTE', '((?:[\x09\x0A\x0B\x0C\x0D\x20]+[^\x09\x0A\x0B\x0C\x0D\x20\x2F\x3E][^\x09\x0A\x0B\x0C\x0D\x20\x2F\x3D\x3E]*(?:[\x09\x0A\x0B\x0C\x0D\x20]*=[\x09\x0A\x0B\x0C\x0D\x20]*(?:"(?:[^"]*)"|\'(?:[^\']*)\'|(?:[^\x09\x0A\x0B\x0C\x0D\x20\x22\x27\x3E][^\x09\x0A\x0B\x0C\x0D\x20\x3E]*)?))?)*)[\x09\x0A\x0B\x0C\x0D\x20]*');

        define('SIMPLEPIE_PCRE_XML_ATTRIBUTE', '((?:\s+(?:(?:[^\s:]+:)?[^\s:]+)\s*=\s*(?:"(?:[^"]*)"|\'(?:[^\']*)\'))*)\s*');

        define('SIMPLEPIE_NAMESPACE_XML', 'http://www.w3.org/XML/1998/namespace');

        define('SIMPLEPIE_NAMESPACE_ATOM_10', 'http://www.w3.org/2005/Atom');

        define('SIMPLEPIE_NAMESPACE_ATOM_03', 'http://purl.org/atom/ns#');

        define('SIMPLEPIE_NAMESPACE_RDF', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');

        define('SIMPLEPIE_NAMESPACE_RSS_090', 'http://my.netscape.com/rdf/simple/0.9/');

        define('SIMPLEPIE_NAMESPACE_RSS_10', 'http://purl.org/rss/1.0/');

        define('SIMPLEPIE_NAMESPACE_RSS_10_MODULES_CONTENT', 'http://purl.org/rss/1.0/modules/content/');

        define('SIMPLEPIE_NAMESPACE_RSS_20', '');

        define('SIMPLEPIE_NAMESPACE_DC_10', 'http://purl.org/dc/elements/1.0/');

        define('SIMPLEPIE_NAMESPACE_DC_11', 'http://purl.org/dc/elements/1.1/');

        define('SIMPLEPIE_NAMESPACE_W3C_BASIC_GEO', 'http://www.w3.org/2003/01/geo/wgs84_pos#');

        define('SIMPLEPIE_NAMESPACE_GEORSS', 'http://www.georss.org/georss');

        define('SIMPLEPIE_NAMESPACE_MEDIARSS', 'http://search.yahoo.com/mrss/');

        define('SIMPLEPIE_NAMESPACE_MEDIARSS_WRONG', 'http://search.yahoo.com/mrss');

        define('SIMPLEPIE_NAMESPACE_MEDIARSS_WRONG2', 'http://video.search.yahoo.com/mrss');

        define('SIMPLEPIE_NAMESPACE_MEDIARSS_WRONG3', 'http://video.search.yahoo.com/mrss/');

        define('SIMPLEPIE_NAMESPACE_MEDIARSS_WRONG4', 'http://www.rssboard.org/media-rss');

        define('SIMPLEPIE_NAMESPACE_MEDIARSS_WRONG5', 'http://www.rssboard.org/media-rss/');

        define('SIMPLEPIE_NAMESPACE_ITUNES', 'http://www.itunes.com/dtds/podcast-1.0.dtd');

        define('SIMPLEPIE_NAMESPACE_XHTML', 'http://www.w3.org/1999/xhtml');

        define('SIMPLEPIE_IANA_LINK_RELATIONS_REGISTRY', 'http://www.iana.org/assignments/relation/');

        define('SIMPLEPIE_FILE_SOURCE_NONE', 0);

        define('SIMPLEPIE_FILE_SOURCE_REMOTE', 1);

        define('SIMPLEPIE_FILE_SOURCE_LOCAL', 2);

        define('SIMPLEPIE_FILE_SOURCE_FSOCKOPEN', 4);

        define('SIMPLEPIE_FILE_SOURCE_CURL', 8);

        define('SIMPLEPIE_FILE_SOURCE_FILE_GET_CONTENTS', 16);

        class SimplePie
        {
            public $data = [];

            public $error;

            public $status_code;

            public $sanitize;

            public $useragent = SIMPLEPIE_USERAGENT;

            public $feed_url;

            public $permanent_url = null;

            public $file;

            public $raw_data;

            public $timeout = 10;

            public $curl_options = [];

            public $force_fsockopen = false;

            public $force_feed = false;

            public $cache = true;

            public $force_cache_fallback = false;

            public $cache_duration = 3600;

            public $autodiscovery_cache_duration = 604800; // 7 Days.

            public $cache_location = './cache';

            public $cache_name_function = 'md5';

            public $order_by_date = true;

            public $input_encoding = false;

            public $autodiscovery = SIMPLEPIE_LOCATOR_ALL;

            public $registry;

            public $max_checked_feeds = 10;

            public $all_discovered_feeds = [];

            public $image_handler = '';

            public $multifeed_url = [];

            public $multifeed_objects = [];

            public $config_settings = null;

            public $item_limit = 0;

            public $check_modified = false;

            public $strip_attributes = [
                'bgsound',
                'class',
                'expr',
                'id',
                'style',
                'onclick',
                'onerror',
                'onfinish',
                'onmouseover',
                'onmouseout',
                'onfocus',
                'onblur',
                'lowsrc',
                'dynsrc'
            ];

            public $add_attributes = [
                'audio' => ['preload' => 'none'],
                'iframe' => ['sandbox' => 'allow-scripts allow-same-origin'],
                'video' => ['preload' => 'none']
            ];

            public $strip_htmltags = [
                'base',
                'blink',
                'body',
                'doctype',
                'embed',
                'font',
                'form',
                'frame',
                'frameset',
                'html',
                'iframe',
                'input',
                'marquee',
                'meta',
                'noscript',
                'object',
                'param',
                'script',
                'style'
            ];

            public $enable_exceptions = false;

            public function __construct()
            {
                if(version_compare(PHP_VERSION, '5.6', '<'))
                {
                    trigger_error('Please upgrade to PHP 5.6 or newer.');
                    die();
                }

                // Other objects, instances created here so we can set options on them
                $this->sanitize = new SimplePie_Sanitize();
                $this->registry = new SimplePie_Registry();

                if(func_num_args() > 0)
                {
                    $level = defined('E_USER_DEPRECATED') ? E_USER_DEPRECATED : E_USER_WARNING;
                    trigger_error('Passing parameters to the constructor is no longer supported. Please use set_feed_url(), set_cache_location(), and set_cache_duration() directly.', $level);

                    $args = func_get_args();
                    switch(count($args))
                    {
                        case 3:
                            $this->set_cache_duration($args[2]);
                        case 2:
                            $this->set_cache_location($args[1]);
                        case 1:
                            $this->set_feed_url($args[0]);
                            $this->init();
                    }
                }
            }

            public function __toString()
            {
                return md5(serialize($this->data));
            }

            public function __destruct()
            {
                if(! gc_enabled())
                {
                    if(! empty($this->data['items']))
                    {
                        foreach($this->data['items'] as $item)
                        {
                            $item->__destruct();
                        }
                        unset($item, $this->data['items']);
                    }
                    if(! empty($this->data['ordered_items']))
                    {
                        foreach($this->data['ordered_items'] as $item)
                        {
                            $item->__destruct();
                        }
                        unset($item, $this->data['ordered_items']);
                    }
                }
            }

            public function force_feed($enable = false)
            {
                $this->force_feed = (bool) $enable;
            }

            public function set_feed_url($url)
            {
                $this->multifeed_url = [];
                if(is_array($url))
                {
                    foreach($url as $value)
                    {
                        $this->multifeed_url[] = $this->registry->call('Misc', 'fix_protocol', [$value, 1]);
                    }
                }
                else
                {
                    $this->feed_url = $this->registry->call('Misc', 'fix_protocol', [$url, 1]);
                    $this->permanent_url = $this->feed_url;
                }
            }

            public function set_file(&$file)
            {
                if($file instanceof SimplePie_File)
                {
                    $this->feed_url = $file->url;
                    $this->permanent_url = $this->feed_url;
                    $this->file =& $file;

                    return true;
                }

                return false;
            }

            public function set_raw_data($data)
            {
                $this->raw_data = $data;
            }

            public function set_timeout($timeout = 10)
            {
                $this->timeout = (int) $timeout;
            }

            public function set_curl_options(array $curl_options = [])
            {
                $this->curl_options = $curl_options;
            }

            public function force_fsockopen($enable = false)
            {
                $this->force_fsockopen = (bool) $enable;
            }

            public function enable_cache($enable = true)
            {
                $this->cache = (bool) $enable;
            }

            public function force_cache_fallback($enable = false)
            {
                $this->force_cache_fallback = (bool) $enable;
            }

            public function set_cache_duration($seconds = 3600)
            {
                $this->cache_duration = (int) $seconds;
            }

            public function set_autodiscovery_cache_duration($seconds = 604800)
            {
                $this->autodiscovery_cache_duration = (int) $seconds;
            }

            public function set_cache_location($location = './cache')
            {
                $this->cache_location = (string) $location;
            }

            public function get_cache_filename($url)
            {
                // Append custom parameters to the URL to avoid cache pollution in case of multiple calls with different parameters.
                $url .= $this->force_feed ? '#force_feed' : '';
                $options = [];
                if($this->timeout != 10)
                {
                    $options[CURLOPT_TIMEOUT] = $this->timeout;
                }
                if($this->useragent !== SIMPLEPIE_USERAGENT)
                {
                    $options[CURLOPT_USERAGENT] = $this->useragent;
                }
                if(! empty($this->curl_options))
                {
                    foreach($this->curl_options as $k => $v)
                    {
                        $options[$k] = $v;
                    }
                }
                if(! empty($options))
                {
                    ksort($options);
                    $url .= '#'.urlencode(var_export($options, true));
                }

                return call_user_func($this->cache_name_function, $url);
            }

            public function enable_order_by_date($enable = true)
            {
                $this->order_by_date = (bool) $enable;
            }

            public function set_input_encoding($encoding = false)
            {
                if($encoding)
                {
                    $this->input_encoding = (string) $encoding;
                }
                else
                {
                    $this->input_encoding = false;
                }
            }

            public function set_autodiscovery_level($level = SIMPLEPIE_LOCATOR_ALL)
            {
                $this->autodiscovery = (int) $level;
            }

            public function &get_registry()
            {
                return $this->registry;
            }

            public function set_cache_class($class = 'SimplePie_Cache')
            {
                return $this->registry->register('Cache', $class, true);
            }

            public function set_locator_class($class = 'SimplePie_Locator')
            {
                return $this->registry->register('Locator', $class, true);
            }

            public function set_parser_class($class = 'SimplePie_Parser')
            {
                return $this->registry->register('Parser', $class, true);
            }

            public function set_file_class($class = 'SimplePie_File')
            {
                return $this->registry->register('File', $class, true);
            }

            public function set_sanitize_class($class = 'SimplePie_Sanitize')
            {
                return $this->registry->register('Sanitize', $class, true);
            }

            public function set_item_class($class = 'SimplePie_Item')
            {
                return $this->registry->register('Item', $class, true);
            }

            public function set_author_class($class = 'SimplePie_Author')
            {
                return $this->registry->register('Author', $class, true);
            }

            public function set_category_class($class = 'SimplePie_Category')
            {
                return $this->registry->register('Category', $class, true);
            }

            public function set_enclosure_class($class = 'SimplePie_Enclosure')
            {
                return $this->registry->register('Enclosure', $class, true);
            }

            public function set_caption_class($class = 'SimplePie_Caption')
            {
                return $this->registry->register('Caption', $class, true);
            }

            public function set_copyright_class($class = 'SimplePie_Copyright')
            {
                return $this->registry->register('Copyright', $class, true);
            }

            public function set_credit_class($class = 'SimplePie_Credit')
            {
                return $this->registry->register('Credit', $class, true);
            }

            public function set_rating_class($class = 'SimplePie_Rating')
            {
                return $this->registry->register('Rating', $class, true);
            }

            public function set_restriction_class($class = 'SimplePie_Restriction')
            {
                return $this->registry->register('Restriction', $class, true);
            }

            public function set_content_type_sniffer_class($class = 'SimplePie_Content_Type_Sniffer')
            {
                return $this->registry->register('Content_Type_Sniffer', $class, true);
            }

            public function set_source_class($class = 'SimplePie_Source')
            {
                return $this->registry->register('Source', $class, true);
            }

            public function set_useragent($ua = SIMPLEPIE_USERAGENT)
            {
                $this->useragent = (string) $ua;
            }

            public function set_cache_name_function($function = 'md5')
            {
                if(is_callable($function))
                {
                    $this->cache_name_function = $function;
                }
            }

            public function set_stupidly_fast($set = false)
            {
                if($set)
                {
                    $this->enable_order_by_date(false);
                    $this->remove_div(false);
                    $this->strip_comments(false);
                    $this->strip_htmltags(false);
                    $this->strip_attributes(false);
                    $this->add_attributes(false);
                    $this->set_image_handler(false);
                    $this->set_https_domains([]);
                }
            }

            public function set_max_checked_feeds($max = 10)
            {
                $this->max_checked_feeds = (int) $max;
            }

            public function remove_div($enable = true)
            {
                $this->sanitize->remove_div($enable);
            }

            public function strip_htmltags($tags = '', $encode = null)
            {
                if($tags === '')
                {
                    $tags = $this->strip_htmltags;
                }
                $this->sanitize->strip_htmltags($tags);
                if($encode !== null)
                {
                    $this->sanitize->encode_instead_of_strip($tags);
                }
            }

            public function encode_instead_of_strip($enable = true)
            {
                $this->sanitize->encode_instead_of_strip($enable);
            }

            public function strip_attributes($attribs = '')
            {
                if($attribs === '')
                {
                    $attribs = $this->strip_attributes;
                }
                $this->sanitize->strip_attributes($attribs);
            }

            public function add_attributes($attribs = '')
            {
                if($attribs === '')
                {
                    $attribs = $this->add_attributes;
                }
                $this->sanitize->add_attributes($attribs);
            }

            public function set_output_encoding($encoding = 'UTF-8')
            {
                $this->sanitize->set_output_encoding($encoding);
            }

            public function strip_comments($strip = false)
            {
                $this->sanitize->strip_comments($strip);
            }

            public function set_url_replacements($element_attribute = null)
            {
                $this->sanitize->set_url_replacements($element_attribute);
            }

            public function set_https_domains($domains = [])
            {
                if(is_array($domains))
                {
                    $this->sanitize->set_https_domains($domains);
                }
            }

            public function set_image_handler($page = false, $qs = 'i')
            {
                if($page !== false)
                {
                    $this->sanitize->set_image_handler($page.'?'.$qs.'=');
                }
                else
                {
                    $this->image_handler = '';
                }
            }

            public function set_item_limit($limit = 0)
            {
                $this->item_limit = (int) $limit;
            }

            public function enable_exceptions($enable = true)
            {
                $this->enable_exceptions = $enable;
            }

            public function init()
            {
                // Check absolute bare minimum requirements.
                if(! extension_loaded('xml') || ! extension_loaded('pcre'))
                {
                    $this->error = 'XML or PCRE extensions not loaded!';

                    return false;
                } // Then check the xml extension is sane (i.e., libxml 2.7.x issue on PHP < 5.2.9 and libxml 2.7.0 to 2.7.2 on any version) if we don't have xmlreader.
                elseif(! extension_loaded('xmlreader'))
                {
                    static $xml_is_sane = null;
                    if($xml_is_sane === null)
                    {
                        $parser_check = xml_parser_create();
                        xml_parse_into_struct($parser_check, '<foo>&amp;</foo>', $values);
                        xml_parser_free($parser_check);
                        $xml_is_sane = isset($values[0]['value']);
                    }
                    if(! $xml_is_sane)
                    {
                        return false;
                    }
                }

                // The default sanitize class gets set in the constructor, check if it has
                // changed.
                if($this->registry->get_class('Sanitize') !== 'SimplePie_Sanitize')
                {
                    $this->sanitize = $this->registry->create('Sanitize');
                }
                if(method_exists($this->sanitize, 'set_registry'))
                {
                    $this->sanitize->set_registry($this->registry);
                }

                // Pass whatever was set with config options over to the sanitizer.
                // Pass the classes in for legacy support; new classes should use the registry instead
                $this->sanitize->pass_cache_data($this->cache, $this->cache_location, $this->cache_name_function, $this->registry->get_class('Cache'));
                $this->sanitize->pass_file_data($this->registry->get_class('File'), $this->timeout, $this->useragent, $this->force_fsockopen, $this->curl_options);

                if(! empty($this->multifeed_url))
                {
                    $i = 0;
                    $success = 0;
                    $this->multifeed_objects = [];
                    $this->error = [];
                    foreach($this->multifeed_url as $url)
                    {
                        $this->multifeed_objects[$i] = clone $this;
                        $this->multifeed_objects[$i]->set_feed_url($url);
                        $single_success = $this->multifeed_objects[$i]->init();
                        $success |= $single_success;
                        if(! $single_success)
                        {
                            $this->error[$i] = $this->multifeed_objects[$i]->error();
                        }
                        $i++;
                    }

                    return (bool) $success;
                }
                elseif($this->feed_url === null && $this->raw_data === null)
                {
                    return false;
                }

                $this->error = null;
                $this->data = [];
                $this->check_modified = false;
                $this->multifeed_objects = [];
                $cache = false;

                if($this->feed_url !== null)
                {
                    $parsed_feed_url = $this->registry->call('Misc', 'parse_url', [$this->feed_url]);

                    // Decide whether to enable caching
                    if($this->cache && $parsed_feed_url['scheme'] !== '')
                    {
                        $filename = $this->get_cache_filename($this->feed_url);
                        $cache = $this->registry->call('Cache', 'get_handler', [
                            $this->cache_location,
                            $filename,
                            'spc'
                        ]);
                    }

                    // Fetch the data via SimplePie_File into $this->raw_data
                    if(($fetched = $this->fetch_data($cache)) === true)
                    {
                        return true;
                    }
                    elseif($fetched === false)
                    {
                        return false;
                    }

                    [$headers, $sniffed] = $fetched;
                }

                // Empty response check
                if(empty($this->raw_data))
                {
                    $this->error = "A feed could not be found at `$this->feed_url`. Empty body.";
                    $this->registry->call('Misc', 'error', [$this->error, E_USER_NOTICE, __FILE__, __LINE__]);

                    return false;
                }

                // Set up array of possible encodings
                $encodings = [];

                // First check to see if input has been overridden.
                if($this->input_encoding !== false)
                {
                    $encodings[] = strtoupper($this->input_encoding);
                }

                $application_types = [
                    'application/xml',
                    'application/xml-dtd',
                    'application/xml-external-parsed-entity'
                ];
                $text_types = ['text/xml', 'text/xml-external-parsed-entity'];

                // RFC 3023 (only applies to sniffed content)
                if(isset($sniffed))
                {
                    if(in_array($sniffed, $application_types) || substr($sniffed, 0, 12) === 'application/' && substr($sniffed, -4) === '+xml')
                    {
                        if(isset($headers['content-type']) && preg_match('/;\x20?charset=([^;]*)/i', $headers['content-type'], $charset))
                        {
                            $encodings[] = strtoupper($charset[1]);
                        }
                        $encodings = array_merge(
                            $encodings, $this->registry->call('Misc', 'xml_encoding', [
                            $this->raw_data,
                            &$this->registry
                        ])
                        );
                        $encodings[] = 'UTF-8';
                    }
                    elseif(in_array($sniffed, $text_types) || substr($sniffed, 0, 5) === 'text/' && substr($sniffed, -4) === '+xml')
                    {
                        if(isset($headers['content-type']) && preg_match('/;\x20?charset=([^;]*)/i', $headers['content-type'], $charset))
                        {
                            $encodings[] = strtoupper($charset[1]);
                        }
                        $encodings[] = 'US-ASCII';
                    } // Text MIME-type default
                    elseif(substr($sniffed, 0, 5) === 'text/')
                    {
                        $encodings[] = 'UTF-8';
                    }
                }

                // Fallback to XML 1.0 Appendix F.1/UTF-8/ISO-8859-1
                $encodings = array_merge(
                    $encodings, $this->registry->call('Misc', 'xml_encoding', [
                    $this->raw_data,
                    &$this->registry
                ])
                );
                $encodings[] = 'UTF-8';
                $encodings[] = 'ISO-8859-1';

                // There's no point in trying an encoding twice
                $encodings = array_unique($encodings);

                // Loop through each possible encoding, till we return something, or run out of possibilities
                foreach($encodings as $encoding)
                {
                    // Change the encoding to UTF-8 (as we always use UTF-8 internally)
                    if(
                        $utf8_data = $this->registry->call('Misc', 'change_encoding', [
                            $this->raw_data,
                            $encoding,
                            'UTF-8'
                        ])
                    )
                    {
                        // Create new parser
                        $parser = $this->registry->create('Parser');

                        // If it's parsed fine
                        if($parser->parse($utf8_data, 'UTF-8', $this->permanent_url))
                        {
                            $this->data = $parser->get_data();
                            if(! ($this->get_type() & ~SIMPLEPIE_TYPE_NONE))
                            {
                                $this->error = "A feed could not be found at `$this->feed_url`. This does not appear to be a valid RSS or Atom feed.";
                                $this->registry->call('Misc', 'error', [
                                    $this->error,
                                    E_USER_NOTICE,
                                    __FILE__,
                                    __LINE__
                                ]);

                                return false;
                            }

                            if(isset($headers))
                            {
                                $this->data['headers'] = $headers;
                            }
                            $this->data['build'] = SIMPLEPIE_BUILD;

                            // Cache the file if caching is enabled
                            if($cache && ! $cache->save($this))
                            {
                                trigger_error("$this->cache_location is not writable. Make sure you've set the correct relative or absolute path, and that the location is server-writable.", E_USER_WARNING);
                            }

                            return true;
                        }
                    }
                }

                if(isset($parser))
                {
                    // We have an error, just set SimplePie_Misc::error to it and quit
                    $this->error = $this->feed_url;
                    $this->error .= sprintf(' is invalid XML, likely due to invalid characters. XML error: %s at line %d, column %d', $parser->get_error_string(), $parser->get_current_line(), $parser->get_current_column());
                }
                else
                {
                    $this->error = 'The data could not be converted to UTF-8.';
                    if(! extension_loaded('mbstring') && ! extension_loaded('iconv') && ! class_exists('\UConverter'))
                    {
                        $this->error .= ' You MUST have either the iconv, mbstring or intl (PHP 5.5+) extension installed and enabled.';
                    }
                    else
                    {
                        $missingExtensions = [];
                        if(! extension_loaded('iconv'))
                        {
                            $missingExtensions[] = 'iconv';
                        }
                        if(! extension_loaded('mbstring'))
                        {
                            $missingExtensions[] = 'mbstring';
                        }
                        if(! class_exists('\UConverter'))
                        {
                            $missingExtensions[] = 'intl (PHP 5.5+)';
                        }
                        $this->error .= ' Try installing/enabling the '.implode(' or ', $missingExtensions).' extension.';
                    }
                }

                $this->registry->call('Misc', 'error', [$this->error, E_USER_NOTICE, __FILE__, __LINE__]);

                return false;
            }

            protected function fetch_data(&$cache)
            {
                // If it's enabled, use the cache
                if($cache)
                {
                    // Load the Cache
                    $this->data = $cache->load();
                    if(! empty($this->data))
                    {
                        // If the cache is for an outdated build of SimplePie
                        if(! isset($this->data['build']) || $this->data['build'] !== SIMPLEPIE_BUILD)
                        {
                            $cache->unlink();
                            $this->data = [];
                        } // If we've hit a collision just rerun it with caching disabled
                        elseif(isset($this->data['url']) && $this->data['url'] !== $this->feed_url)
                        {
                            $cache = false;
                            $this->data = [];
                        } // If we've got a non feed_url stored (if the page isn't actually a feed, or is a redirect) use that URL.
                        elseif(isset($this->data['feed_url']))
                        {
                            // If the autodiscovery cache is still valid use it.
                            if($cache->mtime() + $this->autodiscovery_cache_duration > time())
                            {
                                // Do not need to do feed autodiscovery yet.
                                if($this->data['feed_url'] !== $this->data['url'])
                                {
                                    $this->set_feed_url($this->data['feed_url']);

                                    return $this->init();
                                }

                                $cache->unlink();
                                $this->data = [];
                            }
                        } // Check if the cache has been updated
                        elseif($cache->mtime() + $this->cache_duration < time())
                        {
                            // Want to know if we tried to send last-modified and/or etag headers
                            // when requesting this file. (Note that it's up to the file to
                            // support this, but we don't always send the headers either.)
                            $this->check_modified = true;
                            if(isset($this->data['headers']['last-modified']) || isset($this->data['headers']['etag']))
                            {
                                $headers = [
                                    'Accept' => 'application/atom+xml, application/rss+xml, application/rdf+xml;q=0.9, application/xml;q=0.8, text/xml;q=0.8, text/html;q=0.7, unknown/unknown;q=0.1, application/unknown;q=0.1, */*;q=0.1',
                                ];
                                if(isset($this->data['headers']['last-modified']))
                                {
                                    $headers['if-modified-since'] = $this->data['headers']['last-modified'];
                                }
                                if(isset($this->data['headers']['etag']))
                                {
                                    $headers['if-none-match'] = $this->data['headers']['etag'];
                                }

                                $file = $this->registry->create('File', [
                                    $this->feed_url,
                                    $this->timeout / 10,
                                    5,
                                    $headers,
                                    $this->useragent,
                                    $this->force_fsockopen,
                                    $this->curl_options
                                ]);
                                $this->status_code = $file->status_code;

                                if($file->success)
                                {
                                    if($file->status_code === 304)
                                    {
                                        // Set raw_data to false here too, to signify that the cache
                                        // is still valid.
                                        $this->raw_data = false;
                                        $cache->touch();

                                        return true;
                                    }
                                }
                                else
                                {
                                    $this->check_modified = false;
                                    if($this->force_cache_fallback)
                                    {
                                        $cache->touch();

                                        return true;
                                    }

                                    unset($file);
                                }
                            }
                        } // If the cache is still valid, just return true
                        else
                        {
                            $this->raw_data = false;

                            return true;
                        }
                    } // If the cache is empty, delete it
                    else
                    {
                        $cache->unlink();
                        $this->data = [];
                    }
                }
                // If we don't already have the file (it'll only exist if we've opened it to check if the cache has been modified), open it.
                if(! isset($file))
                {
                    if($this->file instanceof SimplePie_File && $this->file->url === $this->feed_url)
                    {
                        $file =& $this->file;
                    }
                    else
                    {
                        $headers = [
                            'Accept' => 'application/atom+xml, application/rss+xml, application/rdf+xml;q=0.9, application/xml;q=0.8, text/xml;q=0.8, text/html;q=0.7, unknown/unknown;q=0.1, application/unknown;q=0.1, */*;q=0.1',
                        ];
                        $file = $this->registry->create('File', [
                            $this->feed_url,
                            $this->timeout,
                            5,
                            $headers,
                            $this->useragent,
                            $this->force_fsockopen,
                            $this->curl_options
                        ]);
                    }
                }
                $this->status_code = $file->status_code;

                // If the file connection has an error, set SimplePie::error to that and quit
                if(! $file->success && ! ($file->method & SIMPLEPIE_FILE_SOURCE_REMOTE === 0 || ($file->status_code === 200 || $file->status_code > 206 && $file->status_code < 300)))
                {
                    $this->error = $file->error;

                    return ! empty($this->data);
                }

                if(! $this->force_feed)
                {
                    // Check if the supplied URL is a feed, if it isn't, look for it.
                    $locate = $this->registry->create('Locator', [
                        &$file,
                        $this->timeout,
                        $this->useragent,
                        $this->max_checked_feeds,
                        $this->force_fsockopen,
                        $this->curl_options
                    ]);

                    if(! $locate->is_feed($file))
                    {
                        $copyStatusCode = $file->status_code;
                        $copyContentType = $file->headers['content-type'];
                        try
                        {
                            $microformats = false;
                            if(class_exists('DOMXpath') && function_exists('Mf2\parse'))
                            {
                                $doc = new DOMDocument();
                                @$doc->loadHTML($file->body);
                                $xpath = new DOMXpath($doc);
                                // Check for both h-feed and h-entry, as both a feed with no entries
                                // and a list of entries without an h-feed wrapper are both valid.
                                $query = '//*[contains(concat(" ", @class, " "), " h-feed ") or '.'contains(concat(" ", @class, " "), " h-entry ")]';
                                $result = $xpath->query($query);
                                $microformats = $result->length !== 0;
                            }
                            // Now also do feed discovery, but if microformats were found don't
                            // overwrite the current value of file.
                            $discovered = $locate->find($this->autodiscovery, $this->all_discovered_feeds);
                            if($microformats)
                            {
                                if($hub = $locate->get_rel_link('hub'))
                                {
                                    $self = $locate->get_rel_link('self');
                                    $this->store_links($file, $hub, $self);
                                }
                                // Push the current file onto all_discovered feeds so the user can
                                // be shown this as one of the options.
                                if(isset($this->all_discovered_feeds))
                                {
                                    $this->all_discovered_feeds[] = $file;
                                }
                            }
                            else
                            {
                                if($discovered)
                                {
                                    $file = $discovered;
                                }
                                else
                                {
                                    // We need to unset this so that if SimplePie::set_file() has
                                    // been called that object is untouched
                                    unset($file);
                                    $this->error = "A feed could not be found at `$this->feed_url`; the status code is `$copyStatusCode` and content-type is `$copyContentType`";
                                    $this->registry->call('Misc', 'error', [
                                        $this->error,
                                        E_USER_NOTICE,
                                        __FILE__,
                                        __LINE__
                                    ]);

                                    return false;
                                }
                            }
                        }
                        catch(SimplePie_Exception $e)
                        {
                            // We need to unset this so that if SimplePie::set_file() has been called that object is untouched
                            unset($file);
                            // This is usually because DOMDocument doesn't exist
                            $this->error = $e->getMessage();
                            $this->registry->call('Misc', 'error', [
                                $this->error,
                                E_USER_NOTICE,
                                $e->getFile(),
                                $e->getLine()
                            ]);

                            return false;
                        }
                        if($cache)
                        {
                            $this->data = [
                                'url' => $this->feed_url,
                                'feed_url' => $file->url,
                                'build' => SIMPLEPIE_BUILD
                            ];
                            if(! $cache->save($this))
                            {
                                trigger_error("$this->cache_location is not writable. Make sure you've set the correct relative or absolute path, and that the location is server-writable.", E_USER_WARNING);
                            }
                            $cache = $this->registry->call('Cache', 'get_handler', [
                                $this->cache_location,
                                call_user_func($this->cache_name_function, $file->url),
                                'spc'
                            ]);
                        }
                    }
                    $this->feed_url = $file->url;
                    $locate = null;
                }

                $this->raw_data = $file->body;
                $this->permanent_url = $file->permanent_url;
                $headers = $file->headers;
                $sniffer = $this->registry->create('Content_Type_Sniffer', [&$file]);
                $sniffed = $sniffer->get_type();

                return [$headers, $sniffed];
            }

            public function error()
            {
                return $this->error;
            }

            public function status_code()
            {
                return $this->status_code;
            }

            public function get_raw_data()
            {
                return $this->raw_data;
            }

            public function get_encoding()
            {
                return $this->sanitize->output_encoding;
            }

            public function handle_content_type($mime = 'text/html')
            {
                if(! headers_sent())
                {
                    $header = "Content-Type: $mime;";
                    if($this->get_encoding())
                    {
                        $header .= ' charset='.$this->get_encoding();
                    }
                    else
                    {
                        $header .= ' charset=UTF-8';
                    }
                    header($header);
                }
            }

            public function get_type()
            {
                if(! isset($this->data['type']))
                {
                    $this->data['type'] = SIMPLEPIE_TYPE_ALL;
                    if(isset($this->data['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['feed']))
                    {
                        $this->data['type'] &= SIMPLEPIE_TYPE_ATOM_10;
                    }
                    elseif(isset($this->data['child'][SIMPLEPIE_NAMESPACE_ATOM_03]['feed']))
                    {
                        $this->data['type'] &= SIMPLEPIE_TYPE_ATOM_03;
                    }
                    elseif(isset($this->data['child'][SIMPLEPIE_NAMESPACE_RDF]['RDF']))
                    {
                        if(isset($this->data['child'][SIMPLEPIE_NAMESPACE_RDF]['RDF'][0]['child'][SIMPLEPIE_NAMESPACE_RSS_10]['channel']) || isset($this->data['child'][SIMPLEPIE_NAMESPACE_RDF]['RDF'][0]['child'][SIMPLEPIE_NAMESPACE_RSS_10]['image']) || isset($this->data['child'][SIMPLEPIE_NAMESPACE_RDF]['RDF'][0]['child'][SIMPLEPIE_NAMESPACE_RSS_10]['item']) || isset($this->data['child'][SIMPLEPIE_NAMESPACE_RDF]['RDF'][0]['child'][SIMPLEPIE_NAMESPACE_RSS_10]['textinput']))
                        {
                            $this->data['type'] &= SIMPLEPIE_TYPE_RSS_10;
                        }
                        if(isset($this->data['child'][SIMPLEPIE_NAMESPACE_RDF]['RDF'][0]['child'][SIMPLEPIE_NAMESPACE_RSS_090]['channel']) || isset($this->data['child'][SIMPLEPIE_NAMESPACE_RDF]['RDF'][0]['child'][SIMPLEPIE_NAMESPACE_RSS_090]['image']) || isset($this->data['child'][SIMPLEPIE_NAMESPACE_RDF]['RDF'][0]['child'][SIMPLEPIE_NAMESPACE_RSS_090]['item']) || isset($this->data['child'][SIMPLEPIE_NAMESPACE_RDF]['RDF'][0]['child'][SIMPLEPIE_NAMESPACE_RSS_090]['textinput']))
                        {
                            $this->data['type'] &= SIMPLEPIE_TYPE_RSS_090;
                        }
                    }
                    elseif(isset($this->data['child'][SIMPLEPIE_NAMESPACE_RSS_20]['rss']))
                    {
                        $this->data['type'] &= SIMPLEPIE_TYPE_RSS_ALL;
                        if(isset($this->data['child'][SIMPLEPIE_NAMESPACE_RSS_20]['rss'][0]['attribs']['']['version']))
                        {
                            switch(trim($this->data['child'][SIMPLEPIE_NAMESPACE_RSS_20]['rss'][0]['attribs']['']['version']))
                            {
                                case '0.91':
                                    $this->data['type'] &= SIMPLEPIE_TYPE_RSS_091;
                                    if(isset($this->data['child'][SIMPLEPIE_NAMESPACE_RSS_20]['rss'][0]['child'][SIMPLEPIE_NAMESPACE_RSS_20]['skiphours']['hour'][0]['data']))
                                    {
                                        switch(trim($this->data['child'][SIMPLEPIE_NAMESPACE_RSS_20]['rss'][0]['child'][SIMPLEPIE_NAMESPACE_RSS_20]['skiphours']['hour'][0]['data']))
                                        {
                                            case '0':
                                                $this->data['type'] &= SIMPLEPIE_TYPE_RSS_091_NETSCAPE;
                                                break;

                                            case '24':
                                                $this->data['type'] &= SIMPLEPIE_TYPE_RSS_091_USERLAND;
                                                break;
                                        }
                                    }
                                    break;

                                case '0.92':
                                    $this->data['type'] &= SIMPLEPIE_TYPE_RSS_092;
                                    break;

                                case '0.93':
                                    $this->data['type'] &= SIMPLEPIE_TYPE_RSS_093;
                                    break;

                                case '0.94':
                                    $this->data['type'] &= SIMPLEPIE_TYPE_RSS_094;
                                    break;

                                case '2.0':
                                    $this->data['type'] &= SIMPLEPIE_TYPE_RSS_20;
                                    break;
                            }
                        }
                    }
                    else
                    {
                        $this->data['type'] = SIMPLEPIE_TYPE_NONE;
                    }
                }

                return $this->data['type'];
            }

            public function subscribe_url($permanent = false)
            {
                if($permanent)
                {
                    if($this->permanent_url !== null)
                    {
                        // sanitize encodes ampersands which are required when used in a url.
                        return str_replace('&amp;', '&', $this->sanitize($this->permanent_url, SIMPLEPIE_CONSTRUCT_IRI));
                    }
                }
                else
                {
                    if($this->feed_url !== null)
                    {
                        return str_replace('&amp;', '&', $this->sanitize($this->feed_url, SIMPLEPIE_CONSTRUCT_IRI));
                    }
                }

                return null;
            }

            public function get_feed_tags($namespace, $tag)
            {
                $type = $this->get_type();
                if($type & SIMPLEPIE_TYPE_ATOM_10)
                {
                    if(isset($this->data['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['feed'][0]['child'][$namespace][$tag]))
                    {
                        return $this->data['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['feed'][0]['child'][$namespace][$tag];
                    }
                }
                if($type & SIMPLEPIE_TYPE_ATOM_03)
                {
                    if(isset($this->data['child'][SIMPLEPIE_NAMESPACE_ATOM_03]['feed'][0]['child'][$namespace][$tag]))
                    {
                        return $this->data['child'][SIMPLEPIE_NAMESPACE_ATOM_03]['feed'][0]['child'][$namespace][$tag];
                    }
                }
                if($type & SIMPLEPIE_TYPE_RSS_RDF)
                {
                    if(isset($this->data['child'][SIMPLEPIE_NAMESPACE_RDF]['RDF'][0]['child'][$namespace][$tag]))
                    {
                        return $this->data['child'][SIMPLEPIE_NAMESPACE_RDF]['RDF'][0]['child'][$namespace][$tag];
                    }
                }
                if($type & SIMPLEPIE_TYPE_RSS_SYNDICATION)
                {
                    if(isset($this->data['child'][SIMPLEPIE_NAMESPACE_RSS_20]['rss'][0]['child'][$namespace][$tag]))
                    {
                        return $this->data['child'][SIMPLEPIE_NAMESPACE_RSS_20]['rss'][0]['child'][$namespace][$tag];
                    }
                }

                return null;
            }

            public function get_channel_tags($namespace, $tag)
            {
                $type = $this->get_type();
                if($type & SIMPLEPIE_TYPE_ATOM_ALL)
                {
                    if($return = $this->get_feed_tags($namespace, $tag))
                    {
                        return $return;
                    }
                }
                if($type & SIMPLEPIE_TYPE_RSS_10)
                {
                    if($channel = $this->get_feed_tags(SIMPLEPIE_NAMESPACE_RSS_10, 'channel'))
                    {
                        if(isset($channel[0]['child'][$namespace][$tag]))
                        {
                            return $channel[0]['child'][$namespace][$tag];
                        }
                    }
                }
                if($type & SIMPLEPIE_TYPE_RSS_090)
                {
                    if($channel = $this->get_feed_tags(SIMPLEPIE_NAMESPACE_RSS_090, 'channel'))
                    {
                        if(isset($channel[0]['child'][$namespace][$tag]))
                        {
                            return $channel[0]['child'][$namespace][$tag];
                        }
                    }
                }
                if($type & SIMPLEPIE_TYPE_RSS_SYNDICATION)
                {
                    if($channel = $this->get_feed_tags(SIMPLEPIE_NAMESPACE_RSS_20, 'channel'))
                    {
                        if(isset($channel[0]['child'][$namespace][$tag]))
                        {
                            return $channel[0]['child'][$namespace][$tag];
                        }
                    }
                }

                return null;
            }

            public function get_image_tags($namespace, $tag)
            {
                $type = $this->get_type();
                if($type & SIMPLEPIE_TYPE_RSS_10)
                {
                    if($image = $this->get_feed_tags(SIMPLEPIE_NAMESPACE_RSS_10, 'image'))
                    {
                        if(isset($image[0]['child'][$namespace][$tag]))
                        {
                            return $image[0]['child'][$namespace][$tag];
                        }
                    }
                }
                if($type & SIMPLEPIE_TYPE_RSS_090)
                {
                    if($image = $this->get_feed_tags(SIMPLEPIE_NAMESPACE_RSS_090, 'image'))
                    {
                        if(isset($image[0]['child'][$namespace][$tag]))
                        {
                            return $image[0]['child'][$namespace][$tag];
                        }
                    }
                }
                if($type & SIMPLEPIE_TYPE_RSS_SYNDICATION)
                {
                    if($image = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_RSS_20, 'image'))
                    {
                        if(isset($image[0]['child'][$namespace][$tag]))
                        {
                            return $image[0]['child'][$namespace][$tag];
                        }
                    }
                }

                return null;
            }

            public function get_base($element = [])
            {
                if(! ($this->get_type() & SIMPLEPIE_TYPE_RSS_SYNDICATION) && ! empty($element['xml_base_explicit']) && isset($element['xml_base']))
                {
                    return $element['xml_base'];
                }
                elseif($this->get_link() !== null)
                {
                    return $this->get_link();
                }

                return $this->subscribe_url();
            }

            public function sanitize($data, $type, $base = '')
            {
                try
                {
                    return $this->sanitize->sanitize($data, $type, $base);
                }
                catch(SimplePie_Exception $e)
                {
                    if(! $this->enable_exceptions)
                    {
                        $this->error = $e->getMessage();
                        $this->registry->call('Misc', 'error', [
                            $this->error,
                            E_USER_WARNING,
                            $e->getFile(),
                            $e->getLine()
                        ]);

                        return '';
                    }

                    throw $e;
                }
            }

            public function get_title()
            {
                if($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'title'))
                {
                    return $this->sanitize($return[0]['data'], $this->registry->call('Misc', 'atom_10_construct_type', [$return[0]['attribs']]), $this->get_base($return[0]));
                }
                elseif($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_ATOM_03, 'title'))
                {
                    return $this->sanitize($return[0]['data'], $this->registry->call('Misc', 'atom_03_construct_type', [$return[0]['attribs']]), $this->get_base($return[0]));
                }
                elseif($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_RSS_10, 'title'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_MAYBE_HTML, $this->get_base($return[0]));
                }
                elseif($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_RSS_090, 'title'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_MAYBE_HTML, $this->get_base($return[0]));
                }
                elseif($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_RSS_20, 'title'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_MAYBE_HTML, $this->get_base($return[0]));
                }
                elseif($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_DC_11, 'title'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_TEXT);
                }
                elseif($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_DC_10, 'title'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_TEXT);
                }

                return null;
            }

            public function get_category($key = 0)
            {
                $categories = $this->get_categories();
                if(isset($categories[$key]))
                {
                    return $categories[$key];
                }

                return null;
            }

            public function get_categories()
            {
                $categories = [];

                foreach((array) $this->get_channel_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'category') as $category)
                {
                    $term = null;
                    $scheme = null;
                    $label = null;
                    if(isset($category['attribs']['']['term']))
                    {
                        $term = $this->sanitize($category['attribs']['']['term'], SIMPLEPIE_CONSTRUCT_TEXT);
                    }
                    if(isset($category['attribs']['']['scheme']))
                    {
                        $scheme = $this->sanitize($category['attribs']['']['scheme'], SIMPLEPIE_CONSTRUCT_TEXT);
                    }
                    if(isset($category['attribs']['']['label']))
                    {
                        $label = $this->sanitize($category['attribs']['']['label'], SIMPLEPIE_CONSTRUCT_TEXT);
                    }
                    $categories[] = $this->registry->create('Category', [$term, $scheme, $label]);
                }
                foreach((array) $this->get_channel_tags(SIMPLEPIE_NAMESPACE_RSS_20, 'category') as $category)
                {
                    // This is really the label, but keep this as the term also for BC.
                    // Label will also work on retrieving because that falls back to term.
                    $term = $this->sanitize($category['data'], SIMPLEPIE_CONSTRUCT_TEXT);
                    if(isset($category['attribs']['']['domain']))
                    {
                        $scheme = $this->sanitize($category['attribs']['']['domain'], SIMPLEPIE_CONSTRUCT_TEXT);
                    }
                    else
                    {
                        $scheme = null;
                    }
                    $categories[] = $this->registry->create('Category', [$term, $scheme, null]);
                }
                foreach((array) $this->get_channel_tags(SIMPLEPIE_NAMESPACE_DC_11, 'subject') as $category)
                {
                    $categories[] = $this->registry->create('Category', [
                        $this->sanitize($category['data'], SIMPLEPIE_CONSTRUCT_TEXT),
                        null,
                        null
                    ]);
                }
                foreach((array) $this->get_channel_tags(SIMPLEPIE_NAMESPACE_DC_10, 'subject') as $category)
                {
                    $categories[] = $this->registry->create('Category', [
                        $this->sanitize($category['data'], SIMPLEPIE_CONSTRUCT_TEXT),
                        null,
                        null
                    ]);
                }

                if(! empty($categories))
                {
                    return array_unique($categories);
                }

                return null;
            }

            public function get_author($key = 0)
            {
                $authors = $this->get_authors();
                if(isset($authors[$key]))
                {
                    return $authors[$key];
                }

                return null;
            }

            public function get_authors()
            {
                $authors = [];
                foreach((array) $this->get_channel_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'author') as $author)
                {
                    $name = null;
                    $uri = null;
                    $email = null;
                    if(isset($author['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['name'][0]['data']))
                    {
                        $name = $this->sanitize($author['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['name'][0]['data'], SIMPLEPIE_CONSTRUCT_TEXT);
                    }
                    if(isset($author['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['uri'][0]['data']))
                    {
                        $uri = $this->sanitize($author['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['uri'][0]['data'], SIMPLEPIE_CONSTRUCT_IRI, $this->get_base($author['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['uri'][0]));
                    }
                    if(isset($author['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['email'][0]['data']))
                    {
                        $email = $this->sanitize($author['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['email'][0]['data'], SIMPLEPIE_CONSTRUCT_TEXT);
                    }
                    if($name !== null || $email !== null || $uri !== null)
                    {
                        $authors[] = $this->registry->create('Author', [$name, $uri, $email]);
                    }
                }
                if($author = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_ATOM_03, 'author'))
                {
                    $name = null;
                    $url = null;
                    $email = null;
                    if(isset($author[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_03]['name'][0]['data']))
                    {
                        $name = $this->sanitize($author[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_03]['name'][0]['data'], SIMPLEPIE_CONSTRUCT_TEXT);
                    }
                    if(isset($author[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_03]['url'][0]['data']))
                    {
                        $url = $this->sanitize($author[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_03]['url'][0]['data'], SIMPLEPIE_CONSTRUCT_IRI, $this->get_base($author[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_03]['url'][0]));
                    }
                    if(isset($author[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_03]['email'][0]['data']))
                    {
                        $email = $this->sanitize($author[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_03]['email'][0]['data'], SIMPLEPIE_CONSTRUCT_TEXT);
                    }
                    if($name !== null || $email !== null || $url !== null)
                    {
                        $authors[] = $this->registry->create('Author', [$name, $url, $email]);
                    }
                }
                foreach((array) $this->get_channel_tags(SIMPLEPIE_NAMESPACE_DC_11, 'creator') as $author)
                {
                    $authors[] = $this->registry->create('Author', [
                        $this->sanitize($author['data'], SIMPLEPIE_CONSTRUCT_TEXT),
                        null,
                        null
                    ]);
                }
                foreach((array) $this->get_channel_tags(SIMPLEPIE_NAMESPACE_DC_10, 'creator') as $author)
                {
                    $authors[] = $this->registry->create('Author', [
                        $this->sanitize($author['data'], SIMPLEPIE_CONSTRUCT_TEXT),
                        null,
                        null
                    ]);
                }
                foreach((array) $this->get_channel_tags(SIMPLEPIE_NAMESPACE_ITUNES, 'author') as $author)
                {
                    $authors[] = $this->registry->create('Author', [
                        $this->sanitize($author['data'], SIMPLEPIE_CONSTRUCT_TEXT),
                        null,
                        null
                    ]);
                }

                if(! empty($authors))
                {
                    return array_unique($authors);
                }

                return null;
            }

            public function get_contributor($key = 0)
            {
                $contributors = $this->get_contributors();
                if(isset($contributors[$key]))
                {
                    return $contributors[$key];
                }

                return null;
            }

            public function get_contributors()
            {
                $contributors = [];
                foreach((array) $this->get_channel_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'contributor') as $contributor)
                {
                    $name = null;
                    $uri = null;
                    $email = null;
                    if(isset($contributor['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['name'][0]['data']))
                    {
                        $name = $this->sanitize($contributor['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['name'][0]['data'], SIMPLEPIE_CONSTRUCT_TEXT);
                    }
                    if(isset($contributor['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['uri'][0]['data']))
                    {
                        $uri = $this->sanitize($contributor['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['uri'][0]['data'], SIMPLEPIE_CONSTRUCT_IRI, $this->get_base($contributor['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['uri'][0]));
                    }
                    if(isset($contributor['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['email'][0]['data']))
                    {
                        $email = $this->sanitize($contributor['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['email'][0]['data'], SIMPLEPIE_CONSTRUCT_TEXT);
                    }
                    if($name !== null || $email !== null || $uri !== null)
                    {
                        $contributors[] = $this->registry->create('Author', [$name, $uri, $email]);
                    }
                }
                foreach((array) $this->get_channel_tags(SIMPLEPIE_NAMESPACE_ATOM_03, 'contributor') as $contributor)
                {
                    $name = null;
                    $url = null;
                    $email = null;
                    if(isset($contributor['child'][SIMPLEPIE_NAMESPACE_ATOM_03]['name'][0]['data']))
                    {
                        $name = $this->sanitize($contributor['child'][SIMPLEPIE_NAMESPACE_ATOM_03]['name'][0]['data'], SIMPLEPIE_CONSTRUCT_TEXT);
                    }
                    if(isset($contributor['child'][SIMPLEPIE_NAMESPACE_ATOM_03]['url'][0]['data']))
                    {
                        $url = $this->sanitize($contributor['child'][SIMPLEPIE_NAMESPACE_ATOM_03]['url'][0]['data'], SIMPLEPIE_CONSTRUCT_IRI, $this->get_base($contributor['child'][SIMPLEPIE_NAMESPACE_ATOM_03]['url'][0]));
                    }
                    if(isset($contributor['child'][SIMPLEPIE_NAMESPACE_ATOM_03]['email'][0]['data']))
                    {
                        $email = $this->sanitize($contributor['child'][SIMPLEPIE_NAMESPACE_ATOM_03]['email'][0]['data'], SIMPLEPIE_CONSTRUCT_TEXT);
                    }
                    if($name !== null || $email !== null || $url !== null)
                    {
                        $contributors[] = $this->registry->create('Author', [$name, $url, $email]);
                    }
                }

                if(! empty($contributors))
                {
                    return array_unique($contributors);
                }

                return null;
            }

            public function get_link($key = 0, $rel = 'alternate')
            {
                $links = $this->get_links($rel);
                if(isset($links[$key]))
                {
                    return $links[$key];
                }

                return null;
            }

            public function get_permalink()
            {
                return $this->get_link(0);
            }

            public function get_links($rel = 'alternate')
            {
                if(! isset($this->data['links']))
                {
                    $this->data['links'] = [];
                    if($links = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'link'))
                    {
                        foreach($links as $link)
                        {
                            if(isset($link['attribs']['']['href']))
                            {
                                $link_rel = (isset($link['attribs']['']['rel'])) ? $link['attribs']['']['rel'] : 'alternate';
                                $this->data['links'][$link_rel][] = $this->sanitize($link['attribs']['']['href'], SIMPLEPIE_CONSTRUCT_IRI, $this->get_base($link));
                            }
                        }
                    }
                    if($links = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_ATOM_03, 'link'))
                    {
                        foreach($links as $link)
                        {
                            if(isset($link['attribs']['']['href']))
                            {
                                $link_rel = (isset($link['attribs']['']['rel'])) ? $link['attribs']['']['rel'] : 'alternate';
                                $this->data['links'][$link_rel][] = $this->sanitize($link['attribs']['']['href'], SIMPLEPIE_CONSTRUCT_IRI, $this->get_base($link));
                            }
                        }
                    }
                    if($links = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_RSS_10, 'link'))
                    {
                        $this->data['links']['alternate'][] = $this->sanitize($links[0]['data'], SIMPLEPIE_CONSTRUCT_IRI, $this->get_base($links[0]));
                    }
                    if($links = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_RSS_090, 'link'))
                    {
                        $this->data['links']['alternate'][] = $this->sanitize($links[0]['data'], SIMPLEPIE_CONSTRUCT_IRI, $this->get_base($links[0]));
                    }
                    if($links = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_RSS_20, 'link'))
                    {
                        $this->data['links']['alternate'][] = $this->sanitize($links[0]['data'], SIMPLEPIE_CONSTRUCT_IRI, $this->get_base($links[0]));
                    }

                    $keys = array_keys($this->data['links']);
                    foreach($keys as $key)
                    {
                        if($this->registry->call('Misc', 'is_isegment_nz_nc', [$key]))
                        {
                            if(isset($this->data['links'][SIMPLEPIE_IANA_LINK_RELATIONS_REGISTRY.$key]))
                            {
                                $this->data['links'][SIMPLEPIE_IANA_LINK_RELATIONS_REGISTRY.$key] = array_merge($this->data['links'][$key], $this->data['links'][SIMPLEPIE_IANA_LINK_RELATIONS_REGISTRY.$key]);
                                $this->data['links'][$key] =& $this->data['links'][SIMPLEPIE_IANA_LINK_RELATIONS_REGISTRY.$key];
                            }
                            else
                            {
                                $this->data['links'][SIMPLEPIE_IANA_LINK_RELATIONS_REGISTRY.$key] =& $this->data['links'][$key];
                            }
                        }
                        elseif(substr($key, 0, 41) === SIMPLEPIE_IANA_LINK_RELATIONS_REGISTRY)
                        {
                            $this->data['links'][substr($key, 41)] =& $this->data['links'][$key];
                        }
                        $this->data['links'][$key] = array_unique($this->data['links'][$key]);
                    }
                }

                if(isset($this->data['headers']['link']))
                {
                    $link_headers = $this->data['headers']['link'];
                    if(is_string($link_headers))
                    {
                        $link_headers = [$link_headers];
                    }
                    $matches = preg_filter('/<([^>]+)>; rel='.preg_quote($rel).'/', '$1', $link_headers);
                    if(! empty($matches))
                    {
                        return $matches;
                    }
                }

                if(isset($this->data['links'][$rel]))
                {
                    return $this->data['links'][$rel];
                }

                return null;
            }

            public function get_all_discovered_feeds()
            {
                return $this->all_discovered_feeds;
            }

            public function get_description()
            {
                if($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'subtitle'))
                {
                    return $this->sanitize($return[0]['data'], $this->registry->call('Misc', 'atom_10_construct_type', [$return[0]['attribs']]), $this->get_base($return[0]));
                }
                elseif($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_ATOM_03, 'tagline'))
                {
                    return $this->sanitize($return[0]['data'], $this->registry->call('Misc', 'atom_03_construct_type', [$return[0]['attribs']]), $this->get_base($return[0]));
                }
                elseif($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_RSS_10, 'description'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_MAYBE_HTML, $this->get_base($return[0]));
                }
                elseif($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_RSS_090, 'description'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_MAYBE_HTML, $this->get_base($return[0]));
                }
                elseif($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_RSS_20, 'description'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_HTML, $this->get_base($return[0]));
                }
                elseif($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_DC_11, 'description'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_TEXT);
                }
                elseif($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_DC_10, 'description'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_TEXT);
                }
                elseif($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_ITUNES, 'summary'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_HTML, $this->get_base($return[0]));
                }
                elseif($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_ITUNES, 'subtitle'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_HTML, $this->get_base($return[0]));
                }

                return null;
            }

            public function get_copyright()
            {
                if($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'rights'))
                {
                    return $this->sanitize($return[0]['data'], $this->registry->call('Misc', 'atom_10_construct_type', [$return[0]['attribs']]), $this->get_base($return[0]));
                }
                elseif($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_ATOM_03, 'copyright'))
                {
                    return $this->sanitize($return[0]['data'], $this->registry->call('Misc', 'atom_03_construct_type', [$return[0]['attribs']]), $this->get_base($return[0]));
                }
                elseif($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_RSS_20, 'copyright'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_TEXT);
                }
                elseif($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_DC_11, 'rights'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_TEXT);
                }
                elseif($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_DC_10, 'rights'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_TEXT);
                }

                return null;
            }

            public function get_language()
            {
                if($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_RSS_20, 'language'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_TEXT);
                }
                elseif($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_DC_11, 'language'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_TEXT);
                }
                elseif($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_DC_10, 'language'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_TEXT);
                }
                elseif(isset($this->data['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['feed'][0]['xml_lang']))
                {
                    return $this->sanitize($this->data['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['feed'][0]['xml_lang'], SIMPLEPIE_CONSTRUCT_TEXT);
                }
                elseif(isset($this->data['child'][SIMPLEPIE_NAMESPACE_ATOM_03]['feed'][0]['xml_lang']))
                {
                    return $this->sanitize($this->data['child'][SIMPLEPIE_NAMESPACE_ATOM_03]['feed'][0]['xml_lang'], SIMPLEPIE_CONSTRUCT_TEXT);
                }
                elseif(isset($this->data['child'][SIMPLEPIE_NAMESPACE_RDF]['RDF'][0]['xml_lang']))
                {
                    return $this->sanitize($this->data['child'][SIMPLEPIE_NAMESPACE_RDF]['RDF'][0]['xml_lang'], SIMPLEPIE_CONSTRUCT_TEXT);
                }
                elseif(isset($this->data['headers']['content-language']))
                {
                    return $this->sanitize($this->data['headers']['content-language'], SIMPLEPIE_CONSTRUCT_TEXT);
                }

                return null;
            }

            public function get_latitude()
            {
                if($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_W3C_BASIC_GEO, 'lat'))
                {
                    return (float) $return[0]['data'];
                }
                elseif(($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_GEORSS, 'point')) && preg_match('/^((?:-)?[0-9]+(?:\.[0-9]+)) ((?:-)?[0-9]+(?:\.[0-9]+))$/', trim($return[0]['data']), $match))
                {
                    return (float) $match[1];
                }

                return null;
            }

            public function get_longitude()
            {
                if($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_W3C_BASIC_GEO, 'long'))
                {
                    return (float) $return[0]['data'];
                }
                elseif($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_W3C_BASIC_GEO, 'lon'))
                {
                    return (float) $return[0]['data'];
                }
                elseif(($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_GEORSS, 'point')) && preg_match('/^((?:-)?[0-9]+(?:\.[0-9]+)) ((?:-)?[0-9]+(?:\.[0-9]+))$/', trim($return[0]['data']), $match))
                {
                    return (float) $match[2];
                }

                return null;
            }

            public function get_image_title()
            {
                if($return = $this->get_image_tags(SIMPLEPIE_NAMESPACE_RSS_10, 'title'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_TEXT);
                }
                elseif($return = $this->get_image_tags(SIMPLEPIE_NAMESPACE_RSS_090, 'title'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_TEXT);
                }
                elseif($return = $this->get_image_tags(SIMPLEPIE_NAMESPACE_RSS_20, 'title'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_TEXT);
                }
                elseif($return = $this->get_image_tags(SIMPLEPIE_NAMESPACE_DC_11, 'title'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_TEXT);
                }
                elseif($return = $this->get_image_tags(SIMPLEPIE_NAMESPACE_DC_10, 'title'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_TEXT);
                }

                return null;
            }

            public function get_image_url()
            {
                if($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_ITUNES, 'image'))
                {
                    return $this->sanitize($return[0]['attribs']['']['href'], SIMPLEPIE_CONSTRUCT_IRI);
                }
                elseif($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'logo'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_IRI, $this->get_base($return[0]));
                }
                elseif($return = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'icon'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_IRI, $this->get_base($return[0]));
                }
                elseif($return = $this->get_image_tags(SIMPLEPIE_NAMESPACE_RSS_10, 'url'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_IRI, $this->get_base($return[0]));
                }
                elseif($return = $this->get_image_tags(SIMPLEPIE_NAMESPACE_RSS_090, 'url'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_IRI, $this->get_base($return[0]));
                }
                elseif($return = $this->get_image_tags(SIMPLEPIE_NAMESPACE_RSS_20, 'url'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_IRI, $this->get_base($return[0]));
                }

                return null;
            }

            public function get_image_link()
            {
                if($return = $this->get_image_tags(SIMPLEPIE_NAMESPACE_RSS_10, 'link'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_IRI, $this->get_base($return[0]));
                }
                elseif($return = $this->get_image_tags(SIMPLEPIE_NAMESPACE_RSS_090, 'link'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_IRI, $this->get_base($return[0]));
                }
                elseif($return = $this->get_image_tags(SIMPLEPIE_NAMESPACE_RSS_20, 'link'))
                {
                    return $this->sanitize($return[0]['data'], SIMPLEPIE_CONSTRUCT_IRI, $this->get_base($return[0]));
                }

                return null;
            }

            public function get_image_width()
            {
                if($return = $this->get_image_tags(SIMPLEPIE_NAMESPACE_RSS_20, 'width'))
                {
                    return round($return[0]['data']);
                }
                elseif($this->get_type() & SIMPLEPIE_TYPE_RSS_SYNDICATION && $this->get_image_tags(SIMPLEPIE_NAMESPACE_RSS_20, 'url'))
                {
                    return 88.0;
                }

                return null;
            }

            public function get_image_height()
            {
                if($return = $this->get_image_tags(SIMPLEPIE_NAMESPACE_RSS_20, 'height'))
                {
                    return round($return[0]['data']);
                }
                elseif($this->get_type() & SIMPLEPIE_TYPE_RSS_SYNDICATION && $this->get_image_tags(SIMPLEPIE_NAMESPACE_RSS_20, 'url'))
                {
                    return 31.0;
                }

                return null;
            }

            public function get_item_quantity($max = 0)
            {
                $max = (int) $max;
                $qty = count($this->get_items());
                if($max === 0)
                {
                    return $qty;
                }

                return ($qty > $max) ? $max : $qty;
            }

            public function get_item($key = 0)
            {
                $items = $this->get_items();
                if(isset($items[$key]))
                {
                    return $items[$key];
                }

                return null;
            }

            public function get_items($start = 0, $end = 0)
            {
                if(! isset($this->data['items']))
                {
                    if(! empty($this->multifeed_objects))
                    {
                        $this->data['items'] = SimplePie::merge_items($this->multifeed_objects, $start, $end, $this->item_limit);
                        if(empty($this->data['items']))
                        {
                            return [];
                        }

                        return $this->data['items'];
                    }
                    $this->data['items'] = [];
                    if($items = $this->get_feed_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'entry'))
                    {
                        $keys = array_keys($items);
                        foreach($keys as $key)
                        {
                            $this->data['items'][] = $this->registry->create('Item', [$this, $items[$key]]);
                        }
                    }
                    if($items = $this->get_feed_tags(SIMPLEPIE_NAMESPACE_ATOM_03, 'entry'))
                    {
                        $keys = array_keys($items);
                        foreach($keys as $key)
                        {
                            $this->data['items'][] = $this->registry->create('Item', [$this, $items[$key]]);
                        }
                    }
                    if($items = $this->get_feed_tags(SIMPLEPIE_NAMESPACE_RSS_10, 'item'))
                    {
                        $keys = array_keys($items);
                        foreach($keys as $key)
                        {
                            $this->data['items'][] = $this->registry->create('Item', [$this, $items[$key]]);
                        }
                    }
                    if($items = $this->get_feed_tags(SIMPLEPIE_NAMESPACE_RSS_090, 'item'))
                    {
                        $keys = array_keys($items);
                        foreach($keys as $key)
                        {
                            $this->data['items'][] = $this->registry->create('Item', [$this, $items[$key]]);
                        }
                    }
                    if($items = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_RSS_20, 'item'))
                    {
                        $keys = array_keys($items);
                        foreach($keys as $key)
                        {
                            $this->data['items'][] = $this->registry->create('Item', [$this, $items[$key]]);
                        }
                    }
                }

                if(empty($this->data['items']))
                {
                    return [];
                }

                if($this->order_by_date)
                {
                    if(! isset($this->data['ordered_items']))
                    {
                        $this->data['ordered_items'] = $this->data['items'];
                        usort($this->data['ordered_items'], [get_class($this), 'sort_items']);
                    }
                    $items = $this->data['ordered_items'];
                }
                else
                {
                    $items = $this->data['items'];
                }
                // Slice the data as desired
                if($end === 0)
                {
                    return array_slice($items, $start);
                }

                return array_slice($items, $start, $end);
            }

            public function set_favicon_handler($page = false, $qs = 'i')
            {
                $level = defined('E_USER_DEPRECATED') ? E_USER_DEPRECATED : E_USER_WARNING;
                trigger_error('Favicon handling has been removed, please use your own handling', $level);

                return false;
            }

            public function get_favicon()
            {
                $level = defined('E_USER_DEPRECATED') ? E_USER_DEPRECATED : E_USER_WARNING;
                trigger_error('Favicon handling has been removed, please use your own handling', $level);

                if(($url = $this->get_link()) !== null)
                {
                    return 'https://www.google.com/s2/favicons?domain='.urlencode($url);
                }

                return false;
            }

            public function __call($method, $args)
            {
                if(strpos($method, 'subscribe_') === 0)
                {
                    $level = defined('E_USER_DEPRECATED') ? E_USER_DEPRECATED : E_USER_WARNING;
                    trigger_error('subscribe_*() has been deprecated, implement the callback yourself', $level);

                    return '';
                }
                if($method === 'enable_xml_dump')
                {
                    $level = defined('E_USER_DEPRECATED') ? E_USER_DEPRECATED : E_USER_WARNING;
                    trigger_error('enable_xml_dump() has been deprecated, use get_raw_data() instead', $level);

                    return false;
                }

                $class = get_class($this);
                $trace = debug_backtrace(); // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
                $file = $trace[0]['file'];
                $line = $trace[0]['line'];
                trigger_error("Call to undefined method $class::$method() in $file on line $line", E_USER_ERROR);
            }

            public static function sort_items($a, $b)
            {
                $a_date = $a->get_date('U');
                $b_date = $b->get_date('U');
                if($a_date && $b_date)
                {
                    return $a_date > $b_date ? -1 : 1;
                }
                // Sort items without dates to the top.
                if($a_date)
                {
                    return 1;
                }
                if($b_date)
                {
                    return -1;
                }

                return 0;
            }

            public static function merge_items($urls, $start = 0, $end = 0, $limit = 0)
            {
                if(is_array($urls) && sizeof($urls) > 0)
                {
                    $items = [];
                    foreach($urls as $arg)
                    {
                        if($arg instanceof SimplePie)
                        {
                            $items = array_merge($items, $arg->get_items(0, $limit));
                        }
                        else
                        {
                            trigger_error('Arguments must be SimplePie objects', E_USER_WARNING);
                        }
                    }

                    usort($items, [get_class($urls[0]), 'sort_items']);

                    if($end === 0)
                    {
                        return array_slice($items, $start);
                    }

                    return array_slice($items, $start, $end);
                }

                trigger_error('Cannot merge zero SimplePie objects', E_USER_WARNING);

                return [];
            }

            private function store_links(&$file, $hub, $self)
            {
                if(isset($file->headers['link']['hub']) || (isset($file->headers['link']) && preg_match('/rel=hub/', $file->headers['link'])))
                {
                    return;
                }

                if($hub)
                {
                    if(isset($file->headers['link']))
                    {
                        if($file->headers['link'] !== '')
                        {
                            $file->headers['link'] = ', ';
                        }
                    }
                    else
                    {
                        $file->headers['link'] = '';
                    }
                    $file->headers['link'] .= '<'.$hub.'>; rel=hub';
                    if($self)
                    {
                        $file->headers['link'] .= ', <'.$self.'>; rel=self';
                    }
                }
            }
        }
    endif;
