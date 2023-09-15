<?php

    #[AllowDynamicProperties]
    final class WP_Theme implements ArrayAccess
    {
        private static $file_headers = [
            'Name' => 'Theme Name',
            'ThemeURI' => 'Theme URI',
            'Description' => 'Description',
            'Author' => 'Author',
            'AuthorURI' => 'Author URI',
            'Version' => 'Version',
            'Template' => 'Template',
            'Status' => 'Status',
            'Tags' => 'Tags',
            'TextDomain' => 'Text Domain',
            'DomainPath' => 'Domain Path',
            'RequiresWP' => 'Requires at least',
            'RequiresPHP' => 'Requires PHP',
            'UpdateURI' => 'Update URI',
        ];

        private static $default_themes = [
            'classic' => 'WordPress Classic',
            'default' => 'WordPress Default',
            'twentyten' => 'Twenty Ten',
            'twentyeleven' => 'Twenty Eleven',
            'twentytwelve' => 'Twenty Twelve',
            'twentythirteen' => 'Twenty Thirteen',
            'twentyfourteen' => 'Twenty Fourteen',
            'twentyfifteen' => 'Twenty Fifteen',
            'twentysixteen' => 'Twenty Sixteen',
            'twentyseventeen' => 'Twenty Seventeen',
            'twentynineteen' => 'Twenty Nineteen',
            'twentytwenty' => 'Twenty Twenty',
            'twentytwentyone' => 'Twenty Twenty-One',
            'twentytwentytwo' => 'Twenty Twenty-Two',
            'twentytwentythree' => 'Twenty Twenty-Three',
        ];

        private static $tag_map = [
            'fixed-width' => 'fixed-layout',
            'flexible-width' => 'fluid-layout',
        ];

        private static $persistently_cache;

        private static $cache_expiration = 1800;

        public $update = false;

        private $theme_root;

        private $headers = [];

        private $headers_sanitized;

        private $block_theme;

        private $name_translated;

        private $errors;

        private $stylesheet;

        private $template;

        private $parent;

        private $theme_root_uri;

        private $textdomain_loaded;

        private $cache_hash;

        public static function get_core_default_theme()
        {
            foreach(array_reverse(self::$default_themes) as $slug => $name)
            {
                $theme = wp_get_theme($slug);
                if($theme->exists())
                {
                    return $theme;
                }
            }

            return false;
        }

        public function exists()
        {
            return ! ($this->errors() && in_array('theme_not_found', $this->errors()->get_error_codes(), true));
        }

        public function errors()
        {
            if(is_wp_error($this->errors))
            {
                return $this->errors;
            }

            return false;
        }

        public static function get_allowed($blog_id = null)
        {
            $network = (array) apply_filters('network_allowed_themes', self::get_allowed_on_network(), $blog_id);

            return $network + self::get_allowed_on_site($blog_id);
        }

        public static function get_allowed_on_network()
        {
            static $allowed_themes;
            if(! isset($allowed_themes))
            {
                $allowed_themes = (array) get_site_option('allowedthemes');
            }

            $allowed_themes = apply_filters('allowed_themes', $allowed_themes);

            return $allowed_themes;
        }

        public static function get_allowed_on_site($blog_id = null)
        {
            static $allowed_themes = [];

            if(! $blog_id || ! is_multisite())
            {
                $blog_id = get_current_blog_id();
            }

            if(isset($allowed_themes[$blog_id]))
            {
                return (array) apply_filters('site_allowed_themes', $allowed_themes[$blog_id], $blog_id);
            }

            $current = get_current_blog_id() == $blog_id;

            if($current)
            {
                $allowed_themes[$blog_id] = get_option('allowedthemes');
            }
            else
            {
                switch_to_blog($blog_id);
                $allowed_themes[$blog_id] = get_option('allowedthemes');
                restore_current_blog();
            }

            /*
             * This is all super old MU back compat joy.
             * 'allowedthemes' keys things by stylesheet. 'allowed_themes' keyed things by name.
             */
            if(false === $allowed_themes[$blog_id])
            {
                if($current)
                {
                    $allowed_themes[$blog_id] = get_option('allowed_themes');
                }
                else
                {
                    switch_to_blog($blog_id);
                    $allowed_themes[$blog_id] = get_option('allowed_themes');
                    restore_current_blog();
                }

                if(! is_array($allowed_themes[$blog_id]) || empty($allowed_themes[$blog_id]))
                {
                    $allowed_themes[$blog_id] = [];
                }
                else
                {
                    $converted = [];
                    $themes = wp_get_themes();
                    foreach($themes as $stylesheet => $theme_data)
                    {
                        if(isset($allowed_themes[$blog_id][$theme_data->get('Name')]))
                        {
                            $converted[$stylesheet] = true;
                        }
                    }
                    $allowed_themes[$blog_id] = $converted;
                }
                // Set the option so we never have to go through this pain again.
                if(is_admin() && $allowed_themes[$blog_id])
                {
                    if($current)
                    {
                        update_option('allowedthemes', $allowed_themes[$blog_id]);
                        delete_option('allowed_themes');
                    }
                    else
                    {
                        switch_to_blog($blog_id);
                        update_option('allowedthemes', $allowed_themes[$blog_id]);
                        delete_option('allowed_themes');
                        restore_current_blog();
                    }
                }
            }

            return (array) apply_filters('site_allowed_themes', $allowed_themes[$blog_id], $blog_id);
        }

        public function get($header)
        {
            if(! isset($this->headers[$header]))
            {
                return false;
            }

            if(! isset($this->headers_sanitized))
            {
                $this->headers_sanitized = $this->cache_get('headers');
                if(! is_array($this->headers_sanitized))
                {
                    $this->headers_sanitized = [];
                }
            }

            if(isset($this->headers_sanitized[$header]))
            {
                return $this->headers_sanitized[$header];
            }

            // If themes are a persistent group, sanitize everything and cache it. One cache add is better than many cache sets.
            if(self::$persistently_cache)
            {
                foreach(array_keys($this->headers) as $_header)
                {
                    $this->headers_sanitized[$_header] = $this->sanitize_header($_header, $this->headers[$_header]);
                }
                $this->cache_add('headers', $this->headers_sanitized);
            }
            else
            {
                $this->headers_sanitized[$header] = $this->sanitize_header($header, $this->headers[$header]);
            }

            return $this->headers_sanitized[$header];
        }

        private function cache_get($key)
        {
            return wp_cache_get($key.'-'.$this->cache_hash, 'themes');
        }

        private function sanitize_header($header, $value)
        {
            switch($header)
            {
                case 'Status':
                    if(! $value)
                    {
                        $value = 'publish';
                        break;
                    }
                // Fall through otherwise.
                case 'Name':
                    static $header_tags = [
                        'abbr' => ['title' => true],
                        'acronym' => ['title' => true],
                        'code' => true,
                        'em' => true,
                        'strong' => true,
                    ];

                    $value = wp_kses($value, $header_tags);
                    break;
                case 'Author':
                    // There shouldn't be anchor tags in Author, but some themes like to be challenging.
                case 'Description':
                    static $header_tags_with_a = [
                        'a' => [
                            'href' => true,
                            'title' => true,
                        ],
                        'abbr' => ['title' => true],
                        'acronym' => ['title' => true],
                        'code' => true,
                        'em' => true,
                        'strong' => true,
                    ];

                    $value = wp_kses($value, $header_tags_with_a);
                    break;
                case 'ThemeURI':
                case 'AuthorURI':
                    $value = sanitize_url($value);
                    break;
                case 'Tags':
                    $value = array_filter(array_map('trim', explode(',', strip_tags($value))));
                    break;
                case 'Version':
                case 'RequiresWP':
                case 'RequiresPHP':
                case 'UpdateURI':
                    $value = strip_tags($value);
                    break;
            }

            return $value;
        }

        private function cache_add($key, $data)
        {
            return wp_cache_add($key.'-'.$this->cache_hash, $data, 'themes', self::$cache_expiration);
        }

        public static function network_enable_theme($stylesheets)
        {
            if(! is_multisite())
            {
                return;
            }

            if(! is_array($stylesheets))
            {
                $stylesheets = [$stylesheets];
            }

            $allowed_themes = get_site_option('allowedthemes');
            foreach($stylesheets as $stylesheet)
            {
                $allowed_themes[$stylesheet] = true;
            }

            update_site_option('allowedthemes', $allowed_themes);
        }

        public static function network_disable_theme($stylesheets)
        {
            if(! is_multisite())
            {
                return;
            }

            if(! is_array($stylesheets))
            {
                $stylesheets = [$stylesheets];
            }

            $allowed_themes = get_site_option('allowedthemes');
            foreach($stylesheets as $stylesheet)
            {
                if(isset($allowed_themes[$stylesheet]))
                {
                    unset($allowed_themes[$stylesheet]);
                }
            }

            update_site_option('allowedthemes', $allowed_themes);
        }

        public static function sort_by_name(&$themes)
        {
            if(str_starts_with(get_user_locale(), 'en_'))
            {
                uasort($themes, ['WP_Theme', '_name_sort']);
            }
            else
            {
                foreach($themes as $key => $theme)
                {
                    $theme->translate_header('Name', $theme->headers['Name']);
                }
                uasort($themes, ['WP_Theme', '_name_sort_i18n']);
            }
        }

        private function translate_header($header, $value)
        {
            switch($header)
            {
                case 'Name':
                    // Cached for sorting reasons.
                    if(isset($this->name_translated))
                    {
                        return $this->name_translated;
                    }

                    // phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction,WordPress.WP.I18n.NonSingularStringLiteralText,WordPress.WP.I18n.NonSingularStringLiteralDomain
                    $this->name_translated = translate($value, $this->get('TextDomain'));

                    return $this->name_translated;
                case 'Tags':
                    if(empty($value) || ! function_exists('get_theme_feature_list'))
                    {
                        return $value;
                    }

                    static $tags_list;
                    if(! isset($tags_list))
                    {
                        $tags_list = [
                            // As of 4.6, deprecated tags which are only used to provide translation for older themes.
                            'black' => __('Black'),
                            'blue' => __('Blue'),
                            'brown' => __('Brown'),
                            'gray' => __('Gray'),
                            'green' => __('Green'),
                            'orange' => __('Orange'),
                            'pink' => __('Pink'),
                            'purple' => __('Purple'),
                            'red' => __('Red'),
                            'silver' => __('Silver'),
                            'tan' => __('Tan'),
                            'white' => __('White'),
                            'yellow' => __('Yellow'),
                            'dark' => _x('Dark', 'color scheme'),
                            'light' => _x('Light', 'color scheme'),
                            'fixed-layout' => __('Fixed Layout'),
                            'fluid-layout' => __('Fluid Layout'),
                            'responsive-layout' => __('Responsive Layout'),
                            'blavatar' => __('Blavatar'),
                            'photoblogging' => __('Photoblogging'),
                            'seasonal' => __('Seasonal'),
                        ];

                        $feature_list = get_theme_feature_list(false); // No API.

                        foreach($feature_list as $tags)
                        {
                            $tags_list += $tags;
                        }
                    }

                    foreach($value as &$tag)
                    {
                        if(isset($tags_list[$tag]))
                        {
                            $tag = $tags_list[$tag];
                        }
                        elseif(isset(self::$tag_map[$tag]))
                        {
                            $tag = $tags_list[self::$tag_map[$tag]];
                        }
                    }

                    return $value;

                default:
                    // phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction,WordPress.WP.I18n.NonSingularStringLiteralText,WordPress.WP.I18n.NonSingularStringLiteralDomain
                    $value = translate($value, $this->get('TextDomain'));
            }

            return $value;
        }

        private static function _name_sort($a, $b)
        {
            return strnatcasecmp($a->headers['Name'], $b->headers['Name']);
        }

        private static function _name_sort_i18n($a, $b)
        {
            return strnatcasecmp($a->name_translated, $b->name_translated);
        }

        public function __toString()
        {
            return (string) $this->display('Name');
        }

        public function display($header, $markup = true, $translate = true)
        {
            $value = $this->get($header);
            if(false === $value)
            {
                return false;
            }

            if($translate && (empty($value) || ! $this->load_textdomain()))
            {
                $translate = false;
            }

            if($translate)
            {
                $value = $this->translate_header($header, $value);
            }

            if($markup)
            {
                $value = $this->markup_header($header, $value, $translate);
            }

            return $value;
        }

        public function load_textdomain()
        {
            if(isset($this->textdomain_loaded))
            {
                return $this->textdomain_loaded;
            }

            $textdomain = $this->get('TextDomain');
            if(! $textdomain)
            {
                $this->textdomain_loaded = false;

                return false;
            }

            if(is_textdomain_loaded($textdomain))
            {
                $this->textdomain_loaded = true;

                return true;
            }

            $path = $this->get_stylesheet_directory();
            $domainpath = $this->get('DomainPath');
            if($domainpath)
            {
                $path .= $domainpath;
            }
            else
            {
                $path .= '/languages';
            }

            $this->textdomain_loaded = load_theme_textdomain($textdomain, $path);

            return $this->textdomain_loaded;
        }

        public function get_stylesheet_directory()
        {
            if($this->errors() && in_array('theme_root_missing', $this->errors()->get_error_codes(), true))
            {
                return '';
            }

            return $this->theme_root.'/'.$this->stylesheet;
        }

        private function markup_header($header, $value, $translate)
        {
            switch($header)
            {
                case 'Name':
                    if(empty($value))
                    {
                        $value = esc_html($this->get_stylesheet());
                    }
                    break;
                case 'Description':
                    $value = wptexturize($value);
                    break;
                case 'Author':
                    if($this->get('AuthorURI'))
                    {
                        $value = sprintf('<a href="%1$s">%2$s</a>', $this->display('AuthorURI', true, $translate), $value);
                    }
                    elseif(! $value)
                    {
                        $value = __('Anonymous');
                    }
                    break;
                case 'Tags':
                    static $comma = null;
                    if(! isset($comma))
                    {
                        $comma = wp_get_list_item_separator();
                    }
                    $value = implode($comma, $value);
                    break;
                case 'ThemeURI':
                case 'AuthorURI':
                    $value = esc_url($value);
                    break;
            }

            return $value;
        }

        public function get_stylesheet()
        {
            return $this->stylesheet;
        }

        public function __isset($offset)
        {
            static $properties = [
                'name',
                'title',
                'version',
                'parent_theme',
                'template_dir',
                'stylesheet_dir',
                'template',
                'stylesheet',
                'screenshot',
                'description',
                'author',
                'tags',
                'theme_root',
                'theme_root_uri',
            ];

            return in_array($offset, $properties, true);
        }

        public function __get($offset)
        {
            switch($offset)
            {
                case 'name':
                case 'title':
                    return $this->get('Name');
                case 'version':
                    return $this->get('Version');
                case 'parent_theme':
                    if($this->parent())
                    {
                        return $this->parent()->get('Name');
                    }

                    return '';
                case 'template_dir':
                    return $this->get_template_directory();
                case 'stylesheet_dir':
                    return $this->get_stylesheet_directory();
                case 'template':
                    return $this->get_template();
                case 'stylesheet':
                    return $this->get_stylesheet();
                case 'screenshot':
                    return $this->get_screenshot('relative');
                // 'author' and 'description' did not previously return translated data.
                case 'description':
                    return $this->display('Description');
                case 'author':
                    return $this->display('Author');
                case 'tags':
                    return $this->get('Tags');
                case 'theme_root':
                    return $this->get_theme_root();
                case 'theme_root_uri':
                    return $this->get_theme_root_uri();
                // For cases where the array was converted to an object.
                default:
                    return $this->offsetGet($offset);
            }
        }

        public function parent()
        {
            if(isset($this->parent))
            {
                return $this->parent;
            }

            return false;
        }

        public function get_template_directory()
        {
            if($this->parent())
            {
                $theme_root = $this->parent()->theme_root;
            }
            else
            {
                $theme_root = $this->theme_root;
            }

            return $theme_root.'/'.$this->template;
        }

        public function get_template()
        {
            return $this->template;
        }

        public function get_screenshot($uri = 'uri')
        {
            $screenshot = $this->cache_get('screenshot');
            if($screenshot)
            {
                if('relative' === $uri)
                {
                    return $screenshot;
                }

                return $this->get_stylesheet_directory_uri().'/'.$screenshot;
            }
            elseif(0 === $screenshot)
            {
                return false;
            }

            foreach(['png', 'gif', 'jpg', 'jpeg', 'webp'] as $ext)
            {
                if(file_exists($this->get_stylesheet_directory()."/screenshot.$ext"))
                {
                    $this->cache_add('screenshot', 'screenshot.'.$ext);
                    if('relative' === $uri)
                    {
                        return 'screenshot.'.$ext;
                    }

                    return $this->get_stylesheet_directory_uri().'/'.'screenshot.'.$ext;
                }
            }

            $this->cache_add('screenshot', 0);

            return false;
        }

        public function get_stylesheet_directory_uri()
        {
            return $this->get_theme_root_uri().'/'.str_replace('%2F', '/', rawurlencode($this->stylesheet));
        }

        public function get_theme_root_uri()
        {
            if(! isset($this->theme_root_uri))
            {
                $this->theme_root_uri = get_theme_root_uri($this->stylesheet, $this->theme_root);
            }

            return $this->theme_root_uri;
        }

        public function get_theme_root()
        {
            return $this->theme_root;
        }

        #[ReturnTypeWillChange]
        public function offsetGet($offset)
        {
            switch($offset)
            {
                case 'Name':
                case 'Title':
                    /*
                     * See note above about using translated data. get() is not ideal.
                     * It is only for backward compatibility. Use display().
                     */ return $this->get('Name');
                case 'Author':
                    return $this->display('Author');
                case 'Author Name':
                    return $this->display('Author', false);
                case 'Author URI':
                    return $this->display('AuthorURI');
                case 'Description':
                    return $this->display('Description');
                case 'Version':
                case 'Status':
                    return $this->get($offset);
                case 'Template':
                    return $this->get_template();
                case 'Stylesheet':
                    return $this->get_stylesheet();
                case 'Template Files':
                    return $this->get_files('php', 1, true);
                case 'Stylesheet Files':
                    return $this->get_files('css', 0, false);
                case 'Template Dir':
                    return $this->get_template_directory();
                case 'Stylesheet Dir':
                    return $this->get_stylesheet_directory();
                case 'Screenshot':
                    return $this->get_screenshot('relative');
                case 'Tags':
                    return $this->get('Tags');
                case 'Theme Root':
                    return $this->get_theme_root();
                case 'Theme Root URI':
                    return $this->get_theme_root_uri();
                case 'Parent Theme':
                    if($this->parent())
                    {
                        return $this->parent()->get('Name');
                    }

                    return '';
                default:
                    return null;
            }
        }

        public function get_files($type = null, $depth = 0, $search_parent = false)
        {
            $files = (array) self::scandir($this->get_stylesheet_directory(), $type, $depth);

            if($search_parent && $this->parent())
            {
                $files += (array) self::scandir($this->get_template_directory(), $type, $depth);
            }

            return array_filter($files);
        }

        private static function scandir($path, $extensions = null, $depth = 0, $relative_path = '')
        {
            if(! is_dir($path))
            {
                return false;
            }

            if($extensions)
            {
                $extensions = (array) $extensions;
                $_extensions = implode('|', $extensions);
            }

            $relative_path = trailingslashit($relative_path);
            if('/' === $relative_path)
            {
                $relative_path = '';
            }

            $results = scandir($path);
            $files = [];

            $exclusions = (array) apply_filters('theme_scandir_exclusions', [
                'CVS',
                'node_modules',
                'vendor',
                'bower_components'
            ]);

            foreach($results as $result)
            {
                if('.' === $result[0] || in_array($result, $exclusions, true))
                {
                    continue;
                }
                if(is_dir($path.'/'.$result))
                {
                    if(! $depth)
                    {
                        continue;
                    }
                    $found = self::scandir($path.'/'.$result, $extensions, $depth - 1, $relative_path.$result);
                    $files = array_merge_recursive($files, $found);
                }
                elseif(! $extensions || preg_match('~\.('.$_extensions.')$~', $result))
                {
                    $files[$relative_path.$result] = $path.'/'.$result;
                }
            }

            return $files;
        }

        #[ReturnTypeWillChange]
        public function offsetSet($offset, $value) {}

        #[ReturnTypeWillChange]
        public function offsetUnset($offset) {}

        #[ReturnTypeWillChange]
        public function offsetExists($offset)
        {
            static $keys = [
                'Name',
                'Version',
                'Status',
                'Title',
                'Author',
                'Author Name',
                'Author URI',
                'Description',
                'Template',
                'Stylesheet',
                'Template Files',
                'Stylesheet Files',
                'Template Dir',
                'Stylesheet Dir',
                'Screenshot',
                'Tags',
                'Theme Root',
                'Theme Root URI',
                'Parent Theme',
            ];

            return in_array($offset, $keys, true);
        }

        public function cache_delete()
        {
            foreach(['theme', 'screenshot', 'headers', 'post_templates'] as $key)
            {
                wp_cache_delete($key.'-'.$this->cache_hash, 'themes');
            }
            $this->template = null;
            $this->textdomain_loaded = null;
            $this->theme_root_uri = null;
            $this->parent = null;
            $this->errors = null;
            $this->headers_sanitized = null;
            $this->name_translated = null;
            $this->block_theme = null;
            $this->headers = [];
            $this->__construct($this->stylesheet, $this->theme_root);
        }

        public function __construct($theme_dir, $theme_root, $_child = null)
        {
            global $wp_theme_directories;

            // Initialize caching on first run.
            if(! isset(self::$persistently_cache))
            {
                self::$persistently_cache = apply_filters('wp_cache_themes_persistently', false, 'WP_Theme');
                if(self::$persistently_cache)
                {
                    wp_cache_add_global_groups('themes');
                    if(is_int(self::$persistently_cache))
                    {
                        self::$cache_expiration = self::$persistently_cache;
                    }
                }
                else
                {
                    wp_cache_add_non_persistent_groups('themes');
                }
            }

            // Handle a numeric theme directory as a string.
            $theme_dir = (string) $theme_dir;

            $this->theme_root = $theme_root;
            $this->stylesheet = $theme_dir;

            // Correct a situation where the theme is 'some-directory/some-theme' but 'some-directory' was passed in as part of the theme root instead.
            if(! in_array($theme_root, (array) $wp_theme_directories, true) && in_array(dirname($theme_root), (array) $wp_theme_directories, true))
            {
                $this->stylesheet = basename($this->theme_root).'/'.$this->stylesheet;
                $this->theme_root = dirname($theme_root);
            }

            $this->cache_hash = md5($this->theme_root.'/'.$this->stylesheet);
            $theme_file = $this->stylesheet.'/style.css';

            $cache = $this->cache_get('theme');

            if(is_array($cache))
            {
                foreach(['block_theme', 'errors', 'headers', 'template'] as $key)
                {
                    if(isset($cache[$key]))
                    {
                        $this->$key = $cache[$key];
                    }
                }
                if($this->errors)
                {
                    return;
                }
                if(isset($cache['theme_root_template']))
                {
                    $theme_root_template = $cache['theme_root_template'];
                }
            }
            elseif(! file_exists($this->theme_root.'/'.$theme_file))
            {
                $this->headers['Name'] = $this->stylesheet;
                if(file_exists($this->theme_root.'/'.$this->stylesheet))
                {
                    $this->errors = new WP_Error('theme_no_stylesheet', __('Stylesheet is missing.'));
                }
                else
                {
                    $this->errors = new WP_Error('theme_not_found', sprintf(/* translators: %s: Theme directory name. */ __('The theme directory "%s" does not exist.'), esc_html($this->stylesheet)));
                }
                $this->template = $this->stylesheet;
                $this->block_theme = false;
                $this->cache_add('theme', [
                    'block_theme' => $this->block_theme,
                    'headers' => $this->headers,
                    'errors' => $this->errors,
                    'stylesheet' => $this->stylesheet,
                    'template' => $this->template,
                ]);
                if(! file_exists($this->theme_root))
                { // Don't cache this one.
                    $this->errors->add('theme_root_missing', __('<strong>Error:</strong> The themes directory is either empty or does not exist. Please check your installation.'));
                }

                return;
            }
            elseif(is_readable($this->theme_root.'/'.$theme_file))
            {
                $this->headers = get_file_data($this->theme_root.'/'.$theme_file, self::$file_headers, 'theme');
                /*
                 * Default themes always trump their pretenders.
                 * Properly identify default themes that are inside a directory within wp-content/themes.
                 */
                $default_theme_slug = array_search($this->headers['Name'], self::$default_themes, true);
                if($default_theme_slug && basename($this->stylesheet) != $default_theme_slug)
                {
                    $this->headers['Name'] .= '/'.$this->stylesheet;
                }
            }
            else
            {
                $this->headers['Name'] = $this->stylesheet;
                $this->errors = new WP_Error('theme_stylesheet_not_readable', __('Stylesheet is not readable.'));
                $this->template = $this->stylesheet;
                $this->block_theme = false;
                $this->cache_add('theme', [
                    'block_theme' => $this->block_theme,
                    'headers' => $this->headers,
                    'errors' => $this->errors,
                    'stylesheet' => $this->stylesheet,
                    'template' => $this->template,
                ]);

                return;
            }

            if(! $this->template && $this->stylesheet === $this->headers['Template'])
            {
                $this->errors = new WP_Error('theme_child_invalid', sprintf(/* translators: %s: Template. */ __('The theme defines itself as its parent theme. Please check the %s header.'), '<code>Template</code>'));
                $this->cache_add('theme', [
                    'block_theme' => $this->is_block_theme(),
                    'headers' => $this->headers,
                    'errors' => $this->errors,
                    'stylesheet' => $this->stylesheet,
                ]);

                return;
            }

            // (If template is set from cache [and there are no errors], we know it's good.)
            if(! $this->template)
            {
                $this->template = $this->headers['Template'];
            }

            if(! $this->template)
            {
                $this->template = $this->stylesheet;
                $theme_path = $this->theme_root.'/'.$this->stylesheet;

                if(! $this->is_block_theme() && ! file_exists($theme_path.'/index.php'))
                {
                    $error_message = sprintf(/* translators: 1: templates/index.html, 2: index.php, 3: Documentation URL, 4: Template, 5: style.css */ __('Template is missing. Standalone themes need to have a %1$s or %2$s template file. <a href="%3$s">Child themes</a> need to have a %4$s header in the %5$s stylesheet.'), '<code>templates/index.html</code>', '<code>index.php</code>', __('https://developer.wordpress.org/themes/advanced-topics/child-themes/'), '<code>Template</code>', '<code>style.css</code>');
                    $this->errors = new WP_Error('theme_no_index', $error_message);
                    $this->cache_add('theme', [
                        'block_theme' => $this->block_theme,
                        'headers' => $this->headers,
                        'errors' => $this->errors,
                        'stylesheet' => $this->stylesheet,
                        'template' => $this->template,
                    ]);

                    return;
                }
            }

            // If we got our data from cache, we can assume that 'template' is pointing to the right place.
            if(! is_array($cache) && $this->template != $this->stylesheet && ! file_exists($this->theme_root.'/'.$this->template.'/index.php'))
            {
                /*
                 * If we're in a directory of themes inside /themes, look for the parent nearby.
                 * wp-content/themes/directory-of-themes/*
                 */
                $parent_dir = dirname($this->stylesheet);
                $directories = search_theme_directories();

                if('.' !== $parent_dir && file_exists($this->theme_root.'/'.$parent_dir.'/'.$this->template.'/index.php'))
                {
                    $this->template = $parent_dir.'/'.$this->template;
                }
                elseif($directories && isset($directories[$this->template]))
                {
                    /*
                     * Look for the template in the search_theme_directories() results, in case it is in another theme root.
                     * We don't look into directories of themes, just the theme root.
                     */
                    $theme_root_template = $directories[$this->template]['theme_root'];
                }
                else
                {
                    // Parent theme is missing.
                    $this->errors = new WP_Error('theme_no_parent', sprintf(/* translators: %s: Theme directory name. */ __('The parent theme is missing. Please install the "%s" parent theme.'), esc_html($this->template)));
                    $this->cache_add('theme', [
                        'block_theme' => $this->is_block_theme(),
                        'headers' => $this->headers,
                        'errors' => $this->errors,
                        'stylesheet' => $this->stylesheet,
                        'template' => $this->template,
                    ]);
                    $this->parent = new WP_Theme($this->template, $this->theme_root, $this);

                    return;
                }
            }

            // Set the parent, if we're a child theme.
            if($this->template != $this->stylesheet)
            {
                // If we are a parent, then there is a problem. Only two generations allowed! Cancel things out.
                if($_child instanceof WP_Theme && $_child->template == $this->stylesheet)
                {
                    $_child->parent = null;
                    $_child->errors = new WP_Error('theme_parent_invalid', sprintf(/* translators: %s: Theme directory name. */ __('The "%s" theme is not a valid parent theme.'), esc_html($_child->template)));
                    $_child->cache_add('theme', [
                        'block_theme' => $_child->is_block_theme(),
                        'headers' => $_child->headers,
                        'errors' => $_child->errors,
                        'stylesheet' => $_child->stylesheet,
                        'template' => $_child->template,
                    ]);
                    // The two themes actually reference each other with the Template header.
                    if($_child->stylesheet == $this->template)
                    {
                        $this->errors = new WP_Error('theme_parent_invalid', sprintf(/* translators: %s: Theme directory name. */ __('The "%s" theme is not a valid parent theme.'), esc_html($this->template)));
                        $this->cache_add('theme', [
                            'block_theme' => $this->is_block_theme(),
                            'headers' => $this->headers,
                            'errors' => $this->errors,
                            'stylesheet' => $this->stylesheet,
                            'template' => $this->template,
                        ]);
                    }

                    return;
                }
                // Set the parent. Pass the current instance so we can do the crazy checks above and assess errors.
                $this->parent = new WP_Theme($this->template, isset($theme_root_template) ? $theme_root_template : $this->theme_root, $this);
            }

            if(wp_paused_themes()->get($this->stylesheet) && (! is_wp_error($this->errors) || ! isset($this->errors->errors['theme_paused'])))
            {
                $this->errors = new WP_Error('theme_paused', __('This theme failed to load properly and was paused within the admin backend.'));
            }

            // We're good. If we didn't retrieve from cache, set it.
            if(! is_array($cache))
            {
                $cache = [
                    'block_theme' => $this->is_block_theme(),
                    'headers' => $this->headers,
                    'errors' => $this->errors,
                    'stylesheet' => $this->stylesheet,
                    'template' => $this->template,
                ];
                // If the parent theme is in another root, we'll want to cache this. Avoids an entire branch of filesystem calls above.
                if(isset($theme_root_template))
                {
                    $cache['theme_root_template'] = $theme_root_template;
                }
                $this->cache_add('theme', $cache);
            }
        }

        public function is_block_theme()
        {
            if(isset($this->block_theme))
            {
                return $this->block_theme;
            }

            $paths_to_index_block_template = [
                $this->get_file_path('/templates/index.html'),
                $this->get_file_path('/block-templates/index.html'),
            ];

            $this->block_theme = false;

            foreach($paths_to_index_block_template as $path_to_index_block_template)
            {
                if(is_file($path_to_index_block_template) && is_readable($path_to_index_block_template))
                {
                    $this->block_theme = true;
                    break;
                }
            }

            return $this->block_theme;
        }

        public function get_file_path($file = '')
        {
            $file = ltrim($file, '/');

            $stylesheet_directory = $this->get_stylesheet_directory();
            $template_directory = $this->get_template_directory();

            if(empty($file))
            {
                $path = $stylesheet_directory;
            }
            elseif($stylesheet_directory !== $template_directory && file_exists($stylesheet_directory.'/'.$file))
            {
                $path = $stylesheet_directory.'/'.$file;
            }
            else
            {
                $path = $template_directory.'/'.$file;
            }

            return apply_filters('theme_file_path', $path, $file);
        }

        public function get_template_directory_uri()
        {
            if($this->parent())
            {
                $theme_root_uri = $this->parent()->get_theme_root_uri();
            }
            else
            {
                $theme_root_uri = $this->get_theme_root_uri();
            }

            return $theme_root_uri.'/'.str_replace('%2F', '/', rawurlencode($this->template));
        }

        public function get_page_templates($post = null, $post_type = 'page')
        {
            if($post)
            {
                $post_type = get_post_type($post);
            }

            $post_templates = $this->get_post_templates();
            $post_templates = isset($post_templates[$post_type]) ? $post_templates[$post_type] : [];

            $post_templates = (array) apply_filters('theme_templates', $post_templates, $this, $post, $post_type);

            $post_templates = (array) apply_filters("theme_{$post_type}_templates", $post_templates, $this, $post, $post_type);

            return $post_templates;
        }

        public function get_post_templates()
        {
            // If you screw up your active theme and we invalidate your parent, most things still work. Let it slide.
            if($this->errors() && $this->errors()->get_error_codes() !== ['theme_parent_invalid'])
            {
                return [];
            }

            $post_templates = $this->cache_get('post_templates');

            if(! is_array($post_templates))
            {
                $post_templates = [];

                $files = (array) $this->get_files('php', 1, true);

                foreach($files as $file => $full_path)
                {
                    if(! preg_match('|Template Name:(.*)$|mi', file_get_contents($full_path), $header))
                    {
                        continue;
                    }

                    $types = ['page'];
                    if(preg_match('|Template Post Type:(.*)$|mi', file_get_contents($full_path), $type))
                    {
                        $types = explode(',', _cleanup_header_comment($type[1]));
                    }

                    foreach($types as $type)
                    {
                        $type = sanitize_key($type);
                        if(! isset($post_templates[$type]))
                        {
                            $post_templates[$type] = [];
                        }

                        $post_templates[$type][$file] = _cleanup_header_comment($header[1]);
                    }
                }

                $this->cache_add('post_templates', $post_templates);
            }

            if(current_theme_supports('block-templates'))
            {
                $block_templates = get_block_templates([], 'wp_template');
                foreach(get_post_types(['public' => true]) as $type)
                {
                    foreach($block_templates as $block_template)
                    {
                        if(! $block_template->is_custom)
                        {
                            continue;
                        }

                        if(isset($block_template->post_types) && ! in_array($type, $block_template->post_types, true))
                        {
                            continue;
                        }

                        $post_templates[$type][$block_template->slug] = $block_template->title;
                    }
                }
            }

            if($this->load_textdomain())
            {
                foreach($post_templates as &$post_type)
                {
                    foreach($post_type as &$post_template)
                    {
                        $post_template = $this->translate_header('Template Name', $post_template);
                    }
                }
            }

            return $post_templates;
        }

        public function is_allowed($check = 'both', $blog_id = null)
        {
            if(! is_multisite())
            {
                return true;
            }

            if('both' === $check || 'network' === $check)
            {
                $allowed = self::get_allowed_on_network();
                if(! empty($allowed[$this->get_stylesheet()]))
                {
                    return true;
                }
            }

            if('both' === $check || 'site' === $check)
            {
                $allowed = self::get_allowed_on_site($blog_id);
                if(! empty($allowed[$this->get_stylesheet()]))
                {
                    return true;
                }
            }

            return false;
        }
    }
