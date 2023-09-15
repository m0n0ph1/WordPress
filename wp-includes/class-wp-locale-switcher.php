<?php

    #[AllowDynamicProperties]
    class WP_Locale_Switcher
    {
        private $stack = [];

        private $original_locale;

        private $available_languages;

        public function __construct()
        {
            $this->original_locale = determine_locale();
            $this->available_languages = array_merge(['en_US'], get_available_languages());
        }

        public function init()
        {
            add_filter('locale', [$this, 'filter_locale']);
            add_filter('determine_locale', [$this, 'filter_locale']);
        }

        public function switch_to_user_locale($user_id)
        {
            $locale = get_user_locale($user_id);

            return $this->switch_to_locale($locale, $user_id);
        }

        public function switch_to_locale($locale, $user_id = false)
        {
            $current_locale = determine_locale();
            if($current_locale === $locale || ! in_array($locale, $this->available_languages, true))
            {
                return false;
            }

            $this->stack[] = [$locale, $user_id];

            $this->change_locale($locale);

            do_action('switch_locale', $locale, $user_id);

            return true;
        }

        private function change_locale($locale)
        {
            global $wp_locale;

            $this->load_translations($locale);

            $wp_locale = new WP_Locale();

            do_action('change_locale', $locale);
        }

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

        public function restore_current_locale()
        {
            if(empty($this->stack))
            {
                return false;
            }

            $this->stack = [[$this->original_locale, false]];

            return $this->restore_previous_locale();
        }

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

            do_action('restore_previous_locale', $locale, $previous_locale[0]);

            return $locale;
        }

        public function is_switched()
        {
            return ! empty($this->stack);
        }

        public function get_switched_user_id()
        {
            $entry = end($this->stack);

            if($entry)
            {
                return $entry[1];
            }

            return false;
        }

        public function filter_locale($locale)
        {
            $switched_locale = $this->get_switched_locale();

            if($switched_locale)
            {
                return $switched_locale;
            }

            return $locale;
        }

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
