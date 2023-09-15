<?php
    /**
     * Locale API: WP_Locale_Switcher class
     *
     * @package    WordPress
     * @subpackage i18n
     * @since      4.7.0
     */

    /**
     * Core class used for switching locales.
     *
     * @since 4.7.0
     */
    #[AllowDynamicProperties]
    class WP_Locale_Switcher
    {
        /**
         * Locale switching stack.
         *
         * @since 6.2.0
         * @var array
         */
        private $stack = [];

        /**
         * Original locale.
         *
         * @since 4.7.0
         * @var string
         */
        private $original_locale;

        /**
         * Holds all available languages.
         *
         * @since 4.7.0
         * @var string[] An array of language codes (file names without the .mo extension).
         */
        private $available_languages;

        /**
         * Constructor.
         *
         * Stores the original locale as well as a list of all available languages.
         *
         * @since 4.7.0
         */
        public function __construct()
        {
            $this->original_locale = determine_locale();
            $this->available_languages = array_merge(['en_US'], get_available_languages());
        }

        /**
         * Initializes the locale switcher.
         *
         * Hooks into the {@see 'locale'} and {@see 'determine_locale'} filters
         * to change the locale on the fly.
         *
         * @since 4.7.0
         */
        public function init()
        {
            add_filter('locale', [$this, 'filter_locale']);
            add_filter('determine_locale', [$this, 'filter_locale']);
        }

        /**
         * Switches the translations according to the given user's locale.
         *
         * @param int $user_id User ID.
         *
         * @return bool True on success, false on failure.
         * @since 6.2.0
         *
         */
        public function switch_to_user_locale($user_id)
        {
            $locale = get_user_locale($user_id);

            return $this->switch_to_locale($locale, $user_id);
        }

        /**
         * Switches the translations according to the given locale.
         *
         * @param string    $locale  The locale to switch to.
         * @param int|false $user_id Optional. User ID as context. Default false.
         *
         * @return bool True on success, false on failure.
         * @since 4.7.0
         *
         */
        public function switch_to_locale($locale, $user_id = false)
        {
            $current_locale = determine_locale();
            if($current_locale === $locale)
            {
                return false;
            }
            if(! in_array($locale, $this->available_languages, true))
            {
                return false;
            }
            $this->stack[] = [$locale, $user_id];
            $this->change_locale($locale);
            /**
             * Fires when the locale is switched.
             *
             * @param string    $locale  The new locale.
             * @param false|int $user_id User ID for context if available.
             *
             * @since 4.7.0
             * @since 6.2.0 The `$user_id` parameter was added.
             *
             */
            do_action('switch_locale', $locale, $user_id);

            return true;
        }

        /**
         * Changes the site's locale to the given one.
         *
         * Loads the translations, changes the global `$wp_locale` object and updates
         * all post type labels.
         *
         * @param string     $locale    The locale to change to.
         *
         * @global WP_Locale $wp_locale WordPress date and time locale object.
         *
         * @since 4.7.0
         *
         */
        private function change_locale($locale)
        {
            global $wp_locale;
            $this->load_translations($locale);
            $wp_locale = new WP_Locale();
            /**
             * Fires when the locale is switched to or restored.
             *
             * @param string $locale The new locale.
             *
             * @since 4.7.0
             *
             */
            do_action('change_locale', $locale);
        }

        /**
         * Load translations for a given locale.
         *
         * When switching to a locale, translations for this locale must be loaded from scratch.
         *
         * @param string $locale The locale to load translations for.
         *
         * @global Mo[]  $l10n   An array of all currently loaded text domains.
         *
         * @since 4.7.0
         *
         */
        private function load_translations($locale)
        {
            global $l10n;
            $domains = $l10n ? array_keys($l10n) : [];
            load_default_textdomain($locale);
            foreach($domains as $domain)
            {
                // The default text domain is handled by `load_default_textdomain()`.
                if('default' === $domain)
                {
                    continue;
                }
                /*
                 * Unload current text domain but allow them to be reloaded
                 * after switching back or to another locale.
                 */
                unload_textdomain($domain, true);
                get_translations_for_domain($domain);
            }
        }

        /**
         * Restores the translations according to the original locale.
         *
         * @return string|false Locale on success, false on failure.
         * @since 4.7.0
         *
         */
        public function restore_current_locale()
        {
            if(empty($this->stack))
            {
                return false;
            }
            $this->stack = [[$this->original_locale, false]];

            return $this->restore_previous_locale();
        }

        /**
         * Restores the translations according to the previous locale.
         *
         * @return string|false Locale on success, false on failure.
         * @since 4.7.0
         *
         */
        public function restore_previous_locale()
        {
            $previous_locale = array_pop($this->stack);
            if(null === $previous_locale)
            {
                // The stack is empty, bail.
                return false;
            }
            $entry = end($this->stack);
            $locale = is_array($entry) ? $entry[0] : false;
            if(! $locale)
            {
                // There's nothing left in the stack: go back to the original locale.
                $locale = $this->original_locale;
            }
            $this->change_locale($locale);
            /**
             * Fires when the locale is restored to the previous one.
             *
             * @param string $locale          The new locale.
             * @param string $previous_locale The previous locale.
             *
             * @since 4.7.0
             *
             */
            do_action('restore_previous_locale', $locale, $previous_locale[0]);

            return $locale;
        }

        /**
         * Whether switch_to_locale() is in effect.
         *
         * @return bool True if the locale has been switched, false otherwise.
         * @since 4.7.0
         *
         */
        public function is_switched()
        {
            return ! empty($this->stack);
        }

        /**
         * Returns the user ID related to the currently switched locale.
         *
         * @return int|false User ID if set and if the locale has been switched, false otherwise.
         * @since 6.2.0
         *
         */
        public function get_switched_user_id()
        {
            $entry = end($this->stack);
            if($entry)
            {
                return $entry[1];
            }

            return false;
        }

        /**
         * Filters the locale of the WordPress installation.
         *
         * @param string $locale The locale of the WordPress installation.
         *
         * @return string The locale currently being switched to.
         * @since 4.7.0
         *
         */
        public function filter_locale($locale)
        {
            $switched_locale = $this->get_switched_locale();
            if($switched_locale)
            {
                return $switched_locale;
            }

            return $locale;
        }

        /**
         * Returns the locale currently switched to.
         *
         * @return string|false Locale if the locale has been switched, false otherwise.
         * @since 6.2.0
         *
         */
        public function get_switched_locale()
        {
            $entry = end($this->stack);
            if($entry)
            {
                return $entry[0];
            }

            return false;
        }
    }
