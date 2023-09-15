<?php

    function get_locale()
    {
        global $locale, $wp_local_package;

        if(isset($locale))
        {
            return apply_filters('locale', $locale);
        }

        if(isset($wp_local_package))
        {
            $locale = $wp_local_package;
        }

        // WPLANG was defined in wp-config.
        if(defined('WPLANG'))
        {
            $locale = WPLANG;
        }

        // If multisite, check options.
        if(is_multisite())
        {
            // Don't check blog option when installing.
            if(wp_installing())
            {
                $ms_locale = get_site_option('WPLANG');
            }
            else
            {
                $ms_locale = get_option('WPLANG');
                if(false === $ms_locale)
                {
                    $ms_locale = get_site_option('WPLANG');
                }
            }

            if(false !== $ms_locale)
            {
                $locale = $ms_locale;
            }
        }
        else
        {
            $db_locale = get_option('WPLANG');
            if(false !== $db_locale)
            {
                $locale = $db_locale;
            }
        }

        if(empty($locale))
        {
            $locale = 'en_US';
        }

        return apply_filters('locale', $locale);
    }

    function get_user_locale($user = 0)
    {
        $user_object = false;

        if(0 === $user && function_exists('wp_get_current_user'))
        {
            $user_object = wp_get_current_user();
        }
        elseif($user instanceof WP_User)
        {
            $user_object = $user;
        }
        elseif($user && is_numeric($user))
        {
            $user_object = get_user_by('id', $user);
        }

        if(! $user_object)
        {
            return get_locale();
        }

        $locale = $user_object->locale;

        return $locale ? $locale : get_locale();
    }

    function determine_locale()
    {
        $determined_locale = apply_filters('pre_determine_locale', null);

        if($determined_locale && is_string($determined_locale))
        {
            return $determined_locale;
        }

        if(isset($GLOBALS['pagenow']) && 'wp-login.php' === $GLOBALS['pagenow'] && (! empty($_GET['wp_lang']) || ! empty($_COOKIE['wp_lang'])))
        {
            if(! empty($_GET['wp_lang']))
            {
                $determined_locale = sanitize_locale_name($_GET['wp_lang']);
            }
            else
            {
                $determined_locale = sanitize_locale_name($_COOKIE['wp_lang']);
            }
        }
        elseif(is_admin() || (isset($_GET['_locale']) && 'user' === $_GET['_locale'] && wp_is_json_request()))
        {
            $determined_locale = get_user_locale();
        }

        if(! $determined_locale)
        {
            $determined_locale = get_locale();
        }

        return apply_filters('determine_locale', $determined_locale);
    }

    function translate($text, $domain = 'default')
    {
        $translations = get_translations_for_domain($domain);
        $translation = $translations->translate($text);

        $translation = apply_filters('gettext', $translation, $text, $domain);

        $translation = apply_filters("gettext_{$domain}", $translation, $text, $domain);

        return $translation;
    }

    function before_last_bar($text)
    {
        $last_bar = strrpos($text, '|');
        if(false === $last_bar)
        {
            return $text;
        }
        else
        {
            return substr($text, 0, $last_bar);
        }
    }

    function translate_with_gettext_context($text, $context, $domain = 'default')
    {
        $translations = get_translations_for_domain($domain);
        $translation = $translations->translate($text, $context);

        $translation = apply_filters('gettext_with_context', $translation, $text, $context, $domain);

        $translation = apply_filters("gettext_with_context_{$domain}", $translation, $text, $context, $domain);

        return $translation;
    }

    function __($text, $domain = 'default')
    {
        return translate($text, $domain);
    }

    function esc_attr__($text, $domain = 'default')
    {
        return esc_attr(translate($text, $domain));
    }

    function esc_html__($text, $domain = 'default')
    {
        return esc_html(translate($text, $domain));
    }

    function _e($text, $domain = 'default')
    {
        echo translate($text, $domain);
    }

    function esc_attr_e($text, $domain = 'default')
    {
        echo esc_attr(translate($text, $domain));
    }

    function esc_html_e($text, $domain = 'default')
    {
        echo esc_html(translate($text, $domain));
    }

    function _x($text, $context, $domain = 'default')
    {
        return translate_with_gettext_context($text, $context, $domain);
    }

    function _ex($text, $context, $domain = 'default')
    {
        echo _x($text, $context, $domain);
    }

    function esc_attr_x($text, $context, $domain = 'default')
    {
        return esc_attr(translate_with_gettext_context($text, $context, $domain));
    }

    function esc_html_x($text, $context, $domain = 'default')
    {
        return esc_html(translate_with_gettext_context($text, $context, $domain));
    }

    function _n($single, $plural, $number, $domain = 'default')
    {
        $translations = get_translations_for_domain($domain);
        $translation = $translations->translate_plural($single, $plural, $number);

        $translation = apply_filters('ngettext', $translation, $single, $plural, $number, $domain);

        $translation = apply_filters("ngettext_{$domain}", $translation, $single, $plural, $number, $domain);

        return $translation;
    }

    function _nx($single, $plural, $number, $context, $domain = 'default')
    {
        $translations = get_translations_for_domain($domain);
        $translation = $translations->translate_plural($single, $plural, $number, $context);

        $translation = apply_filters('ngettext_with_context', $translation, $single, $plural, $number, $context, $domain);

        $translation = apply_filters("ngettext_with_context_{$domain}", $translation, $single, $plural, $number, $context, $domain);

        return $translation;
    }

    function _n_noop($singular, $plural, $domain = null)
    {
        return [
            0 => $singular,
            1 => $plural,
            'singular' => $singular,
            'plural' => $plural,
            'context' => null,
            'domain' => $domain,
        ];
    }

    function _nx_noop($singular, $plural, $context, $domain = null)
    {
        return [
            0 => $singular,
            1 => $plural,
            2 => $context,
            'singular' => $singular,
            'plural' => $plural,
            'context' => $context,
            'domain' => $domain,
        ];
    }

    function translate_nooped_plural($nooped_plural, $count, $domain = 'default')
    {
        if($nooped_plural['domain'])
        {
            $domain = $nooped_plural['domain'];
        }

        if($nooped_plural['context'])
        {
            return _nx($nooped_plural['singular'], $nooped_plural['plural'], $count, $nooped_plural['context'], $domain);
        }
        else
        {
            return _n($nooped_plural['singular'], $nooped_plural['plural'], $count, $domain);
        }
    }

    function load_textdomain($domain, $mofile, $locale = null)
    {
        global $l10n, $l10n_unloaded, $wp_textdomain_registry;

        $l10n_unloaded = (array) $l10n_unloaded;

        $loaded = apply_filters('pre_load_textdomain', null, $domain, $mofile, $locale);
        if(null !== $loaded)
        {
            if(true === $loaded)
            {
                unset($l10n_unloaded[$domain]);
            }

            return $loaded;
        }

        $plugin_override = apply_filters('override_load_textdomain', false, $domain, $mofile, $locale);

        if(true === (bool) $plugin_override)
        {
            unset($l10n_unloaded[$domain]);

            return true;
        }

        do_action('load_textdomain', $domain, $mofile);

        $mofile = apply_filters('load_textdomain_mofile', $mofile, $domain);

        if(! is_readable($mofile))
        {
            return false;
        }

        if(! $locale)
        {
            $locale = determine_locale();
        }

        $mo = new MO();
        if(! $mo->import_from_file($mofile))
        {
            $wp_textdomain_registry->set($domain, $locale, false);

            return false;
        }

        if(isset($l10n[$domain]))
        {
            $mo->merge_with($l10n[$domain]);
        }

        unset($l10n_unloaded[$domain]);

        $l10n[$domain] = &$mo;

        $wp_textdomain_registry->set($domain, $locale, dirname($mofile));

        return true;
    }

    function unload_textdomain($domain, $reloadable = false)
    {
        global $l10n, $l10n_unloaded;

        $l10n_unloaded = (array) $l10n_unloaded;

        $plugin_override = apply_filters('override_unload_textdomain', false, $domain, $reloadable);

        if($plugin_override)
        {
            if(! $reloadable)
            {
                $l10n_unloaded[$domain] = true;
            }

            return true;
        }

        do_action('unload_textdomain', $domain, $reloadable);

        if(isset($l10n[$domain]))
        {
            if($l10n[$domain] instanceof NOOP_Translations)
            {
                unset($l10n[$domain]);

                return false;
            }

            unset($l10n[$domain]);

            if(! $reloadable)
            {
                $l10n_unloaded[$domain] = true;
            }

            return true;
        }

        return false;
    }

    function load_default_textdomain($locale = null)
    {
        if(null === $locale)
        {
            $locale = determine_locale();
        }

        // Unload previously loaded strings so we can switch translations.
        unload_textdomain('default');

        $return = load_textdomain('default', WP_LANG_DIR."/$locale.mo", $locale);

        if((is_multisite() || (defined('WP_INSTALLING_NETWORK') && WP_INSTALLING_NETWORK)) && ! file_exists(WP_LANG_DIR."/admin-$locale.mo"))
        {
            load_textdomain('default', WP_LANG_DIR."/ms-$locale.mo", $locale);

            return $return;
        }

        if(is_admin() || wp_installing() || (defined('WP_REPAIRING') && WP_REPAIRING))
        {
            load_textdomain('default', WP_LANG_DIR."/admin-$locale.mo", $locale);
        }

        if(is_network_admin() || (defined('WP_INSTALLING_NETWORK') && WP_INSTALLING_NETWORK))
        {
            load_textdomain('default', WP_LANG_DIR."/admin-network-$locale.mo", $locale);
        }

        return $return;
    }

    function load_plugin_textdomain($domain, $deprecated = false, $plugin_rel_path = false)
    {
        global $wp_textdomain_registry;

        $locale = apply_filters('plugin_locale', determine_locale(), $domain);

        $mofile = $domain.'-'.$locale.'.mo';

        // Try to load from the languages directory first.
        if(load_textdomain($domain, WP_LANG_DIR.'/plugins/'.$mofile, $locale))
        {
            return true;
        }

        if(false !== $plugin_rel_path)
        {
            $path = WP_PLUGIN_DIR.'/'.trim($plugin_rel_path, '/');
        }
        elseif(false !== $deprecated)
        {
            _deprecated_argument(__FUNCTION__, '2.7.0');
            $path = ABSPATH.trim($deprecated, '/');
        }
        else
        {
            $path = WP_PLUGIN_DIR;
        }

        $wp_textdomain_registry->set_custom_path($domain, $path);

        return load_textdomain($domain, $path.'/'.$mofile, $locale);
    }

    function load_muplugin_textdomain($domain, $mu_plugin_rel_path = '')
    {
        global $wp_textdomain_registry;

        $locale = apply_filters('plugin_locale', determine_locale(), $domain);

        $mofile = $domain.'-'.$locale.'.mo';

        // Try to load from the languages directory first.
        if(load_textdomain($domain, WP_LANG_DIR.'/plugins/'.$mofile, $locale))
        {
            return true;
        }

        $path = WPMU_PLUGIN_DIR.'/'.ltrim($mu_plugin_rel_path, '/');

        $wp_textdomain_registry->set_custom_path($domain, $path);

        return load_textdomain($domain, $path.'/'.$mofile, $locale);
    }

    function load_theme_textdomain($domain, $path = false)
    {
        global $wp_textdomain_registry;

        $locale = apply_filters('theme_locale', determine_locale(), $domain);

        $mofile = $domain.'-'.$locale.'.mo';

        // Try to load from the languages directory first.
        if(load_textdomain($domain, WP_LANG_DIR.'/themes/'.$mofile, $locale))
        {
            return true;
        }

        if(! $path)
        {
            $path = get_template_directory();
        }

        $wp_textdomain_registry->set_custom_path($domain, $path);

        return load_textdomain($domain, $path.'/'.$locale.'.mo', $locale);
    }

    function load_child_theme_textdomain($domain, $path = false)
    {
        if(! $path)
        {
            $path = get_stylesheet_directory();
        }

        return load_theme_textdomain($domain, $path);
    }

    function load_script_textdomain($handle, $domain = 'default', $path = '')
    {
        $wp_scripts = wp_scripts();

        if(! isset($wp_scripts->registered[$handle]))
        {
            return false;
        }

        $path = untrailingslashit($path);
        $locale = determine_locale();

        // If a path was given and the handle file exists simply return it.
        $file_base = 'default' === $domain ? $locale : $domain.'-'.$locale;
        $handle_filename = $file_base.'-'.$handle.'.json';

        if($path)
        {
            $translations = load_script_translations($path.'/'.$handle_filename, $handle, $domain);

            if($translations)
            {
                return $translations;
            }
        }

        $src = $wp_scripts->registered[$handle]->src;

        if(! preg_match('|^(https?:)?//|', $src) && ! ($wp_scripts->content_url && str_starts_with($src, $wp_scripts->content_url)))
        {
            $src = $wp_scripts->base_url.$src;
        }

        $relative = false;
        $languages_path = WP_LANG_DIR;

        $src_url = wp_parse_url($src);
        $content_url = wp_parse_url(content_url());
        $plugins_url = wp_parse_url(plugins_url());
        $site_url = wp_parse_url(site_url());

        // If the host is the same or it's a relative URL.
        if((! isset($content_url['path']) || str_starts_with($src_url['path'], $content_url['path'])) && (! isset($src_url['host']) || ! isset($content_url['host']) || $src_url['host'] === $content_url['host']))
        {
            // Make the src relative the specific plugin or theme.
            if(isset($content_url['path']))
            {
                $relative = substr($src_url['path'], strlen($content_url['path']));
            }
            else
            {
                $relative = $src_url['path'];
            }
            $relative = trim($relative, '/');
            $relative = explode('/', $relative);

            $languages_path = WP_LANG_DIR.'/'.$relative[0];

            $relative = array_slice($relative, 2); // Remove plugins/<plugin name> or themes/<theme name>.
            $relative = implode('/', $relative);
        }
        elseif((! isset($plugins_url['path']) || str_starts_with($src_url['path'], $plugins_url['path'])) && (! isset($src_url['host']) || ! isset($plugins_url['host']) || $src_url['host'] === $plugins_url['host']))
        {
            // Make the src relative the specific plugin.
            if(isset($plugins_url['path']))
            {
                $relative = substr($src_url['path'], strlen($plugins_url['path']));
            }
            else
            {
                $relative = $src_url['path'];
            }
            $relative = trim($relative, '/');
            $relative = explode('/', $relative);

            $languages_path = WP_LANG_DIR.'/plugins';

            $relative = array_slice($relative, 1); // Remove <plugin name>.
            $relative = implode('/', $relative);
        }
        elseif(! isset($src_url['host']) || ! isset($site_url['host']) || $src_url['host'] === $site_url['host'])
        {
            if(! isset($site_url['path']))
            {
                $relative = trim($src_url['path'], '/');
            }
            elseif(str_starts_with($src_url['path'], trailingslashit($site_url['path'])))
            {
                // Make the src relative to the WP root.
                $relative = substr($src_url['path'], strlen($site_url['path']));
                $relative = trim($relative, '/');
            }
        }

        $relative = apply_filters('load_script_textdomain_relative_path', $relative, $src);

        // If the source is not from WP.
        if(false === $relative)
        {
            return load_script_translations(false, $handle, $domain);
        }

        // Translations are always based on the unminified filename.
        if(str_ends_with($relative, '.min.js'))
        {
            $relative = substr($relative, 0, -7).'.js';
        }

        $md5_filename = $file_base.'-'.md5($relative).'.json';

        if($path)
        {
            $translations = load_script_translations($path.'/'.$md5_filename, $handle, $domain);

            if($translations)
            {
                return $translations;
            }
        }

        $translations = load_script_translations($languages_path.'/'.$md5_filename, $handle, $domain);

        if($translations)
        {
            return $translations;
        }

        return load_script_translations(false, $handle, $domain);
    }

    function load_script_translations($file, $handle, $domain)
    {
        $translations = apply_filters('pre_load_script_translations', null, $file, $handle, $domain);

        if(null !== $translations)
        {
            return $translations;
        }

        $file = apply_filters('load_script_translation_file', $file, $handle, $domain);

        if(! $file || ! is_readable($file))
        {
            return false;
        }

        $translations = file_get_contents($file);

        return apply_filters('load_script_translations', $translations, $file, $handle, $domain);
    }

    function _load_textdomain_just_in_time($domain)
    {
        global $l10n_unloaded, $wp_textdomain_registry;

        $l10n_unloaded = (array) $l10n_unloaded;

        // Short-circuit if domain is 'default' which is reserved for core.
        if('default' === $domain || isset($l10n_unloaded[$domain]))
        {
            return false;
        }

        if(! $wp_textdomain_registry->has($domain))
        {
            return false;
        }

        $locale = determine_locale();
        $path = $wp_textdomain_registry->get($domain, $locale);
        if(! $path)
        {
            return false;
        }
        // Themes with their language directory outside of WP_LANG_DIR have a different file name.
        $template_directory = trailingslashit(get_template_directory());
        $stylesheet_directory = trailingslashit(get_stylesheet_directory());
        if(str_starts_with($path, $template_directory) || str_starts_with($path, $stylesheet_directory))
        {
            $mofile = "{$path}{$locale}.mo";
        }
        else
        {
            $mofile = "{$path}{$domain}-{$locale}.mo";
        }

        return load_textdomain($domain, $mofile, $locale);
    }

    function get_translations_for_domain($domain)
    {
        global $l10n;
        if(isset($l10n[$domain]) || (_load_textdomain_just_in_time($domain) && isset($l10n[$domain])))
        {
            return $l10n[$domain];
        }

        static $noop_translations = null;
        if(null === $noop_translations)
        {
            $noop_translations = new NOOP_Translations();
        }

        $l10n[$domain] = &$noop_translations;

        return $noop_translations;
    }

    function is_textdomain_loaded($domain)
    {
        global $l10n;

        return isset($l10n[$domain]) && ! $l10n[$domain] instanceof NOOP_Translations;
    }

    function translate_user_role($name, $domain = 'default')
    {
        return translate_with_gettext_context(before_last_bar($name), 'User role', $domain);
    }

    function get_available_languages($dir = null)
    {
        $languages = [];

        $lang_files = glob((is_null($dir) ? WP_LANG_DIR : $dir).'/*.mo');
        if($lang_files)
        {
            foreach($lang_files as $lang_file)
            {
                $lang_file = basename($lang_file, '.mo');
                if(! str_starts_with($lang_file, 'continents-cities') && ! str_starts_with($lang_file, 'ms-') && ! str_starts_with($lang_file, 'admin-'))
                {
                    $languages[] = $lang_file;
                }
            }
        }

        return apply_filters('get_available_languages', $languages, $dir);
    }

    function wp_get_installed_translations($type)
    {
        if('themes' !== $type && 'plugins' !== $type && 'core' !== $type)
        {
            return [];
        }

        $dir = 'core' === $type ? '' : "/$type";

        if(! is_dir(WP_LANG_DIR))
        {
            return [];
        }

        if($dir && ! is_dir(WP_LANG_DIR.$dir))
        {
            return [];
        }

        $files = scandir(WP_LANG_DIR.$dir);
        if(! $files)
        {
            return [];
        }

        $language_data = [];

        foreach($files as $file)
        {
            if('.' === $file[0] || is_dir(WP_LANG_DIR."$dir/$file"))
            {
                continue;
            }
            if(! str_ends_with($file, '.po'))
            {
                continue;
            }
            if(! preg_match('/(?:(.+)-)?([a-z]{2,3}(?:_[A-Z]{2})?(?:_[a-z0-9]+)?).po/', $file, $match))
            {
                continue;
            }
            if(! in_array(substr($file, 0, -3).'.mo', $files, true))
            {
                continue;
            }

            [, $textdomain, $language] = $match;
            if('' === $textdomain)
            {
                $textdomain = 'default';
            }
            $language_data[$textdomain][$language] = wp_get_pomo_file_data(WP_LANG_DIR."$dir/$file");
        }

        return $language_data;
    }

    function wp_get_pomo_file_data($po_file)
    {
        $headers = get_file_data($po_file, [
            'POT-Creation-Date' => '"POT-Creation-Date',
            'PO-Revision-Date' => '"PO-Revision-Date',
            'Project-Id-Version' => '"Project-Id-Version',
            'X-Generator' => '"X-Generator',
        ]);
        foreach($headers as $header => $value)
        {
            // Remove possible contextual '\n' and closing double quote.
            $headers[$header] = preg_replace('~(\\\n)?"$~', '', $value);
        }

        return $headers;
    }

    function wp_dropdown_languages($args = [])
    {
        $parsed_args = wp_parse_args($args, [
            'id' => 'locale',
            'name' => 'locale',
            'languages' => [],
            'translations' => [],
            'selected' => '',
            'echo' => 1,
            'show_available_translations' => true,
            'show_option_site_default' => false,
            'show_option_en_us' => true,
            'explicit_option_en_us' => false,
        ]);

        // Bail if no ID or no name.
        if(! $parsed_args['id'] || ! $parsed_args['name'])
        {
            return;
        }

        // English (United States) uses an empty string for the value attribute.
        if('en_US' === $parsed_args['selected'] && ! $parsed_args['explicit_option_en_us'])
        {
            $parsed_args['selected'] = '';
        }

        $translations = $parsed_args['translations'];
        if(empty($translations))
        {
            require_once ABSPATH.'wp-admin/includes/translation-install.php';
            $translations = wp_get_available_translations();
        }

        /*
         * $parsed_args['languages'] should only contain the locales. Find the locale in
         * $translations to get the native name. Fall back to locale.
         */
        $languages = [];
        foreach($parsed_args['languages'] as $locale)
        {
            if(isset($translations[$locale]))
            {
                $translation = $translations[$locale];
                $languages[] = [
                    'language' => $translation['language'],
                    'native_name' => $translation['native_name'],
                    'lang' => current($translation['iso']),
                ];

                // Remove installed language from available translations.
                unset($translations[$locale]);
            }
            else
            {
                $languages[] = [
                    'language' => $locale,
                    'native_name' => $locale,
                    'lang' => '',
                ];
            }
        }

        $translations_available = (! empty($translations) && $parsed_args['show_available_translations']);

        // Holds the HTML markup.
        $structure = [];

        // List installed languages.
        if($translations_available)
        {
            $structure[] = '<optgroup label="'.esc_attr_x('Installed', 'translations').'">';
        }

        // Site default.
        if($parsed_args['show_option_site_default'])
        {
            $structure[] = sprintf('<option value="site-default" data-installed="1"%s>%s</option>', selected('site-default', $parsed_args['selected'], false), _x('Site Default', 'default site language'));
        }

        if($parsed_args['show_option_en_us'])
        {
            $value = ($parsed_args['explicit_option_en_us']) ? 'en_US' : '';
            $structure[] = sprintf('<option value="%s" lang="en" data-installed="1"%s>English (United States)</option>', esc_attr($value), selected('', $parsed_args['selected'], false));
        }

        // List installed languages.
        foreach($languages as $language)
        {
            $structure[] = sprintf('<option value="%s" lang="%s"%s data-installed="1">%s</option>', esc_attr($language['language']), esc_attr($language['lang']), selected($language['language'], $parsed_args['selected'], false), esc_html($language['native_name']));
        }
        if($translations_available)
        {
            $structure[] = '</optgroup>';
        }

        // List available translations.
        if($translations_available)
        {
            $structure[] = '<optgroup label="'.esc_attr_x('Available', 'translations').'">';
            foreach($translations as $translation)
            {
                $structure[] = sprintf('<option value="%s" lang="%s"%s>%s</option>', esc_attr($translation['language']), esc_attr(current($translation['iso'])), selected($translation['language'], $parsed_args['selected'], false), esc_html($translation['native_name']));
            }
            $structure[] = '</optgroup>';
        }

        // Combine the output string.
        $output = sprintf('<select name="%s" id="%s">', esc_attr($parsed_args['name']), esc_attr($parsed_args['id']));
        $output .= implode("\n", $structure);
        $output .= '</select>';

        if($parsed_args['echo'])
        {
            echo $output;
        }

        return $output;
    }

    function is_rtl()
    {
        global $wp_locale;
        if(! ($wp_locale instanceof WP_Locale))
        {
            return false;
        }

        return $wp_locale->is_rtl();
    }

    function switch_to_locale($locale)
    {
        /* @var WP_Locale_Switcher $wp_locale_switcher */ global $wp_locale_switcher;

        if(! $wp_locale_switcher)
        {
            return false;
        }

        return $wp_locale_switcher->switch_to_locale($locale);
    }

    function switch_to_user_locale($user_id)
    {
        /* @var WP_Locale_Switcher $wp_locale_switcher */ global $wp_locale_switcher;

        if(! $wp_locale_switcher)
        {
            return false;
        }

        return $wp_locale_switcher->switch_to_user_locale($user_id);
    }

    function restore_previous_locale()
    {
        /* @var WP_Locale_Switcher $wp_locale_switcher */ global $wp_locale_switcher;

        if(! $wp_locale_switcher)
        {
            return false;
        }

        return $wp_locale_switcher->restore_previous_locale();
    }

    function restore_current_locale()
    {
        /* @var WP_Locale_Switcher $wp_locale_switcher */ global $wp_locale_switcher;

        if(! $wp_locale_switcher)
        {
            return false;
        }

        return $wp_locale_switcher->restore_current_locale();
    }

    function is_locale_switched()
    {
        /* @var WP_Locale_Switcher $wp_locale_switcher */ global $wp_locale_switcher;

        return $wp_locale_switcher->is_switched();
    }

    function translate_settings_using_i18n_schema($i18n_schema, $settings, $textdomain)
    {
        if(empty($i18n_schema) || empty($settings) || empty($textdomain))
        {
            return $settings;
        }

        if(is_string($i18n_schema) && is_string($settings))
        {
            return translate_with_gettext_context($settings, $i18n_schema, $textdomain);
        }
        if(is_array($i18n_schema) && is_array($settings))
        {
            $translated_settings = [];
            foreach($settings as $value)
            {
                $translated_settings[] = translate_settings_using_i18n_schema($i18n_schema[0], $value, $textdomain);
            }

            return $translated_settings;
        }
        if(is_object($i18n_schema) && is_array($settings))
        {
            $group_key = '*';
            $translated_settings = [];
            foreach($settings as $key => $value)
            {
                if(isset($i18n_schema->$key))
                {
                    $translated_settings[$key] = translate_settings_using_i18n_schema($i18n_schema->$key, $value, $textdomain);
                }
                elseif(isset($i18n_schema->$group_key))
                {
                    $translated_settings[$key] = translate_settings_using_i18n_schema($i18n_schema->$group_key, $value, $textdomain);
                }
                else
                {
                    $translated_settings[$key] = $value;
                }
            }

            return $translated_settings;
        }

        return $settings;
    }

    function wp_get_list_item_separator()
    {
        global $wp_locale;

        if(! ($wp_locale instanceof WP_Locale))
        {
            // Default value of WP_Locale::get_list_item_separator().
            /* translators: Used between list items, there is a space after the comma. */
            return __(', ');
        }

        return $wp_locale->get_list_item_separator();
    }

    function wp_get_word_count_type()
    {
        global $wp_locale;

        if(! ($wp_locale instanceof WP_Locale))
        {
            // Default value of WP_Locale::get_word_count_type().
            return 'words';
        }

        return $wp_locale->get_word_count_type();
    }
