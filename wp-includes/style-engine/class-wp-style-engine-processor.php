<?php

    #[AllowDynamicProperties]
    class WP_Style_Engine_Processor
    {
        protected $stores = [];

        protected $css_rules = [];

        public function add_store($store)
        {
            if(! $store instanceof WP_Style_Engine_CSS_Rules_Store)
            {
                _doing_it_wrong(__METHOD__, __('$store must be an instance of WP_Style_Engine_CSS_Rules_Store'), '6.1.0');

                return $this;
            }

            $this->stores[$store->get_name()] = $store;

            return $this;
        }

        public function get_css($options = [])
        {
            $defaults = [
                'optimize' => false,
                'prettify' => defined('SCRIPT_DEBUG') && SCRIPT_DEBUG,
            ];
            $options = wp_parse_args($options, $defaults);

            // If we have stores, get the rules from them.
            foreach($this->stores as $store)
            {
                $this->add_rules($store->get_all_rules());
            }

            // Combine CSS selectors that have identical declarations.
            if(true === $options['optimize'])
            {
                $this->combine_rules_selectors();
            }

            // Build the CSS.
            $css = '';
            foreach($this->css_rules as $rule)
            {
                $css .= $rule->get_css($options['prettify']);
                $css .= $options['prettify'] ? "\n" : '';
            }

            return $css;
        }

        public function add_rules($css_rules)
        {
            if(! is_array($css_rules))
            {
                $css_rules = [$css_rules];
            }

            foreach($css_rules as $rule)
            {
                $selector = $rule->get_selector();
                if(isset($this->css_rules[$selector]))
                {
                    $this->css_rules[$selector]->add_declarations($rule->get_declarations());
                    continue;
                }
                $this->css_rules[$rule->get_selector()] = $rule;
            }

            return $this;
        }

        private function combine_rules_selectors()
        {
            // Build an array of selectors along with the JSON-ified styles to make comparisons easier.
            $selectors_json = [];
            foreach($this->css_rules as $rule)
            {
                $declarations = $rule->get_declarations()->get_declarations();
                ksort($declarations);
                $selectors_json[$rule->get_selector()] = wp_json_encode($declarations);
            }

            // Combine selectors that have the same styles.
            foreach($selectors_json as $selector => $json)
            {
                // Get selectors that use the same styles.
                $duplicates = array_keys($selectors_json, $json, true);
                // Skip if there are no duplicates.
                if(1 >= count($duplicates))
                {
                    continue;
                }

                $declarations = $this->css_rules[$selector]->get_declarations();

                foreach($duplicates as $key)
                {
                    // Unset the duplicates from the $selectors_json array to avoid looping through them as well.
                    unset($selectors_json[$key]);
                    // Remove the rules from the rules collection.
                    unset($this->css_rules[$key]);
                }
                // Create a new rule with the combined selectors.
                $duplicate_selectors = implode(',', $duplicates);
                $this->css_rules[$duplicate_selectors] = new WP_Style_Engine_CSS_Rule($duplicate_selectors, $declarations);
            }
        }
    }
