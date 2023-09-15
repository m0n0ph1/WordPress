<?php

    class WP_Scripts extends WP_Dependencies
    {
        public $base_url;

        public $content_url;

        public $default_version;

        public $in_footer = [];

        public $concat = '';

        public $concat_version = '';

        public $do_concat = false;

        public $print_html = '';

        public $print_code = '';

        public $ext_handles = '';

        public $ext_version = '';

        public $default_dirs;

        private $type_attr = '';

        private $dependents_map = [];

        private $delayed_strategies = ['defer', 'async'];

        public function __construct()
        {
            $this->init();
            add_action('init', [$this, 'init'], 0);
        }

        public function init()
        {
            if(function_exists('is_admin') && ! is_admin() && function_exists('current_theme_supports') && ! current_theme_supports('html5', 'script'))
            {
                $this->type_attr = " type='text/javascript'";
            }

            do_action_ref_array('wp_default_scripts', [&$this]);
        }

        public function print_scripts($handles = false, $group = false)
        {
            return $this->do_items($handles, $group);
        }

        public function print_scripts_l10n($handle, $display = true)
        {
            _deprecated_function(__FUNCTION__, '3.3.0', 'WP_Scripts::print_extra_script()');

            return $this->print_extra_script($handle, $display);
        }

        public function print_extra_script($handle, $display = true)
        {
            $output = $this->get_data($handle, 'data');
            if(! $output)
            {
                return;
            }

            if(! $display)
            {
                return $output;
            }

            printf("<script%s id='%s-js-extra'>\n", $this->type_attr, esc_attr($handle));

            // CDATA is not needed for HTML 5.
            if($this->type_attr)
            {
                echo "/* <![CDATA[ */\n";
            }

            echo "$output\n";

            if($this->type_attr)
            {
                echo "/* ]]> */\n";
            }

            echo "</script>\n";

            return true;
        }

        public function do_item($handle, $group = false)
        {
            if(! parent::do_item($handle))
            {
                return false;
            }

            if(0 === $group && $this->groups[$handle] > 0)
            {
                $this->in_footer[] = $handle;

                return false;
            }

            if(false === $group && in_array($handle, $this->in_footer, true))
            {
                $this->in_footer = array_diff($this->in_footer, (array) $handle);
            }

            $obj = $this->registered[$handle];

            if(null === $obj->ver)
            {
                $ver = '';
            }
            else
            {
                $ver = $obj->ver ? $obj->ver : $this->default_version;
            }

            if(isset($this->args[$handle]))
            {
                $ver = $ver ? $ver.'&amp;'.$this->args[$handle] : $this->args[$handle];
            }

            $src = $obj->src;
            $strategy = $this->get_eligible_loading_strategy($handle);
            $intended_strategy = (string) $this->get_data($handle, 'strategy');
            $cond_before = '';
            $cond_after = '';
            $conditional = isset($obj->extra['conditional']) ? $obj->extra['conditional'] : '';

            if(! $this->is_delayed_strategy($intended_strategy))
            {
                $intended_strategy = '';
            }

            if($conditional)
            {
                $cond_before = "<!--[if {$conditional}]>\n";
                $cond_after = "<![endif]-->\n";
            }

            $before_script = $this->get_inline_script_tag($handle, 'before');
            $after_script = $this->get_inline_script_tag($handle, 'after');

            if($before_script || $after_script)
            {
                $inline_script_tag = $cond_before.$before_script.$after_script.$cond_after;
            }
            else
            {
                $inline_script_tag = '';
            }

            /*
             * Prevent concatenation of scripts if the text domain is defined
             * to ensure the dependency order is respected.
             */
            $translations_stop_concat = ! empty($obj->textdomain);

            $translations = $this->print_translations($handle, false);
            if($translations)
            {
                $translations = sprintf("<script%s id='%s-js-translations'>\n%s\n</script>\n", $this->type_attr, esc_attr($handle), $translations);
            }

            if($this->do_concat)
            {
                $srce = apply_filters('script_loader_src', $src, $handle);

                if($this->in_default_dir($srce) && ($before_script || $after_script || $translations_stop_concat || $this->is_delayed_strategy($strategy)))
                {
                    $this->do_concat = false;

                    // Have to print the so-far concatenated scripts right away to maintain the right order.
                    _print_scripts();
                    $this->reset();
                }
                elseif($this->in_default_dir($srce) && ! $conditional)
                {
                    $this->print_code .= $this->print_extra_script($handle, false);
                    $this->concat .= "$handle,";
                    $this->concat_version .= "$handle$ver";

                    return true;
                }
                else
                {
                    $this->ext_handles .= "$handle,";
                    $this->ext_version .= "$handle$ver";
                }
            }

            $has_conditional_data = $conditional && $this->get_data($handle, 'data');

            if($has_conditional_data)
            {
                echo $cond_before;
            }

            $this->print_extra_script($handle);

            if($has_conditional_data)
            {
                echo $cond_after;
            }

            // A single item may alias a set of items, by having dependencies, but no source.
            if(! $src)
            {
                if($inline_script_tag)
                {
                    if($this->do_concat)
                    {
                        $this->print_html .= $inline_script_tag;
                    }
                    else
                    {
                        echo $inline_script_tag;
                    }
                }

                return true;
            }

            if(! preg_match('|^(https?:)?//|', $src) && ! ($this->content_url && str_starts_with($src, $this->content_url)))
            {
                $src = $this->base_url.$src;
            }

            if(! empty($ver))
            {
                $src = add_query_arg('ver', $ver, $src);
            }

            $src = esc_url(apply_filters('script_loader_src', $src, $handle));

            if(! $src)
            {
                return true;
            }

            $tag = $translations.$cond_before.$before_script;
            $tag .= sprintf(
                "<script%s src='%s' id='%s-js'%s%s></script>\n", $this->type_attr, $src, // Value is escaped above.
                esc_attr($handle), $strategy ? " {$strategy}" : '', $intended_strategy ? " data-wp-strategy='{$intended_strategy}'" : ''
            );
            $tag .= $after_script.$cond_after;

            $tag = apply_filters('script_loader_tag', $tag, $handle, $src);

            if($this->do_concat)
            {
                $this->print_html .= $tag;
            }
            else
            {
                echo $tag;
            }

            return true;
        }

        private function get_eligible_loading_strategy($handle)
        {
            $intended = (string) $this->get_data($handle, 'strategy');

            // Bail early if there is no intended strategy.
            if(! $intended)
            {
                return '';
            }

            /*
             * If the intended strategy is 'defer', limit the initial list of eligible
             * strategies, since 'async' can fallback to 'defer', but not vice-versa.
             */
            $initial = ('defer' === $intended) ? ['defer'] : null;

            $eligible = $this->filter_eligible_strategies($handle, $initial);

            // Return early once we know the eligible strategy is blocking.
            if(empty($eligible))
            {
                return '';
            }

            return in_array('async', $eligible, true) ? 'async' : 'defer';
        }

        private function filter_eligible_strategies($handle, $eligible = null, $checked = [])
        {
            // If no strategies are being passed, all strategies are eligible.
            if(null === $eligible)
            {
                $eligible = $this->delayed_strategies;
            }

            // If this handle was already checked, return early.
            if(isset($checked[$handle]))
            {
                return $eligible;
            }

            // Mark this handle as checked.
            $checked[$handle] = true;

            // If this handle isn't registered, don't filter anything and return.
            if(! isset($this->registered[$handle]))
            {
                return $eligible;
            }

            // If the handle is not enqueued, don't filter anything and return.
            if(! $this->query($handle, 'enqueued'))
            {
                return $eligible;
            }

            $is_alias = (bool) ! $this->registered[$handle]->src;
            $intended_strategy = $this->get_data($handle, 'strategy');

            // For non-alias handles, an empty intended strategy filters all strategies.
            if(! $is_alias && empty($intended_strategy))
            {
                return [];
            }

            // Handles with inline scripts attached in the 'after' position cannot be delayed.
            if($this->has_inline_script($handle, 'after'))
            {
                return [];
            }

            // If the intended strategy is 'defer', filter out 'async'.
            if('defer' === $intended_strategy)
            {
                $eligible = ['defer'];
            }

            $dependents = $this->get_dependents($handle);

            // Recursively filter eligible strategies for dependents.
            foreach($dependents as $dependent)
            {
                // Bail early once we know the eligible strategy is blocking.
                if(empty($eligible))
                {
                    return [];
                }

                $eligible = $this->filter_eligible_strategies($dependent, $eligible, $checked);
            }

            return $eligible;
        }

        private function has_inline_script($handle, $position = null)
        {
            if($position && in_array($position, ['before', 'after'], true))
            {
                return (bool) $this->get_data($handle, $position);
            }

            return (bool) ($this->get_data($handle, 'before') || $this->get_data($handle, 'after'));
        }

        private function get_dependents($handle)
        {
            // Check if dependents map for the handle in question is present. If so, use it.
            if(isset($this->dependents_map[$handle]))
            {
                return $this->dependents_map[$handle];
            }

            $dependents = [];

            // Iterate over all registered scripts, finding dependents of the script passed to this method.
            foreach($this->registered as $registered_handle => $args)
            {
                if(in_array($handle, $args->deps, true))
                {
                    $dependents[] = $registered_handle;
                }
            }

            // Add the handles dependents to the map to ease future lookups.
            $this->dependents_map[$handle] = $dependents;

            return $dependents;
        }

        private function is_delayed_strategy($strategy)
        {
            return in_array($strategy, $this->delayed_strategies, true);
        }

        public function get_inline_script_tag($handle, $position = 'after')
        {
            $js = $this->get_inline_script_data($handle, $position);
            if(empty($js))
            {
                return '';
            }

            $id = "{$handle}-js-{$position}";

            return wp_get_inline_script_tag($js, compact('id'));
        }

        public function get_inline_script_data($handle, $position = 'after')
        {
            $data = $this->get_data($handle, $position);
            if(empty($data) || ! is_array($data))
            {
                return '';
            }

            return trim(implode("\n", $data), "\n");
        }

        public function print_translations($handle, $display = true)
        {
            if(! isset($this->registered[$handle]) || empty($this->registered[$handle]->textdomain))
            {
                return false;
            }

            $domain = $this->registered[$handle]->textdomain;
            $path = '';

            if(isset($this->registered[$handle]->translations_path))
            {
                $path = $this->registered[$handle]->translations_path;
            }

            $json_translations = load_script_textdomain($handle, $domain, $path);

            if(! $json_translations)
            {
                return false;
            }

            $output = <<<JS
( function( domain, translations ) {
	var localeData = translations.locale_data[ domain ] || translations.locale_data.messages;
	localeData[""].domain = domain;
	wp.i18n.setLocaleData( localeData, domain );
} )( "{$domain}", {$json_translations} );
JS;

            if($display)
            {
                printf("<script%s id='%s-js-translations'>\n%s\n</script>\n", $this->type_attr, esc_attr($handle), $output);
            }

            return $output;
        }

        public function in_default_dir($src)
        {
            if(! $this->default_dirs)
            {
                return true;
            }

            if(str_starts_with($src, '/'.WPINC.'/js/l10n'))
            {
                return false;
            }

            foreach((array) $this->default_dirs as $test)
            {
                if(str_starts_with($src, $test))
                {
                    return true;
                }
            }

            return false;
        }

        public function reset()
        {
            $this->do_concat = false;
            $this->print_code = '';
            $this->concat = '';
            $this->concat_version = '';
            $this->print_html = '';
            $this->ext_version = '';
            $this->ext_handles = '';
        }

        public function add_inline_script($handle, $data, $position = 'after')
        {
            if(! $data)
            {
                return false;
            }

            if('after' !== $position)
            {
                $position = 'before';
            }

            $script = (array) $this->get_data($handle, $position);
            $script[] = $data;

            return $this->add_data($handle, $position, $script);
        }

        public function add_data($handle, $key, $value)
        {
            if(! isset($this->registered[$handle]))
            {
                return false;
            }

            if('strategy' === $key)
            {
                if(! empty($value) && ! $this->is_delayed_strategy($value))
                {
                    _doing_it_wrong(__METHOD__, sprintf(/* translators: 1: $strategy, 2: $handle */ __('Invalid strategy `%1$s` defined for `%2$s` during script registration.'), $value, $handle), '6.3.0');

                    return false;
                }
                elseif(! $this->registered[$handle]->src && $this->is_delayed_strategy($value))
                {
                    _doing_it_wrong(__METHOD__, sprintf(/* translators: 1: $strategy, 2: $handle */ __('Cannot supply a strategy `%1$s` for script `%2$s` because it is an alias (it lacks a `src` value).'), $value, $handle), '6.3.0');

                    return false;
                }
            }

            return parent::add_data($handle, $key, $value);
        }

        public function print_inline_script($handle, $position = 'after', $display = true)
        {
            _deprecated_function(__METHOD__, '6.3.0', 'WP_Scripts::get_inline_script_data() or WP_Scripts::get_inline_script_tag()');

            $output = $this->get_inline_script_data($handle, $position);
            if(empty($output))
            {
                return false;
            }

            if($display)
            {
                echo $this->get_inline_script_tag($handle, $position);
            }

            return $output;
        }

        public function localize($handle, $object_name, $l10n)
        {
            if('jquery' === $handle)
            {
                $handle = 'jquery-core';
            }

            if(is_array($l10n) && isset($l10n['l10n_print_after']))
            { // back compat, preserve the code in 'l10n_print_after' if present.
                $after = $l10n['l10n_print_after'];
                unset($l10n['l10n_print_after']);
            }

            if(! is_array($l10n))
            {
                _doing_it_wrong(__METHOD__, sprintf(/* translators: 1: $l10n, 2: wp_add_inline_script() */ __('The %1$s parameter must be an array. To pass arbitrary data to scripts, use the %2$s function instead.'), '<code>$l10n</code>', '<code>wp_add_inline_script()</code>'), '5.7.0');

                if(false === $l10n)
                {
                    // This should really not be needed, but is necessary for backward compatibility.
                    $l10n = [$l10n];
                }
            }

            if(is_string($l10n))
            {
                $l10n = html_entity_decode($l10n, ENT_QUOTES, 'UTF-8');
            }
            elseif(is_array($l10n))
            {
                foreach($l10n as $key => $value)
                {
                    if(! is_scalar($value))
                    {
                        continue;
                    }

                    $l10n[$key] = html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
                }
            }

            $script = "var $object_name = ".wp_json_encode($l10n).';';

            if(! empty($after))
            {
                $script .= "\n$after;";
            }

            $data = $this->get_data($handle, 'data');

            if(! empty($data))
            {
                $script = "$data\n$script";
            }

            return $this->add_data($handle, 'data', $script);
        }

        public function set_group($handle, $recursion, $group = false)
        {
            if(isset($this->registered[$handle]->args) && 1 === $this->registered[$handle]->args)
            {
                $grp = 1;
            }
            else
            {
                $grp = (int) $this->get_data($handle, 'group');
            }

            if(false !== $group && $grp > $group)
            {
                $grp = $group;
            }

            return parent::set_group($handle, $recursion, $grp);
        }

        public function set_translations($handle, $domain = 'default', $path = '')
        {
            if(! isset($this->registered[$handle]))
            {
                return false;
            }

            $obj = $this->registered[$handle];

            if(! in_array('wp-i18n', $obj->deps, true))
            {
                $obj->deps[] = 'wp-i18n';
            }

            return $obj->set_translations($domain, $path);
        }

        public function all_deps($handles, $recursion = false, $group = false)
        {
            $r = parent::all_deps($handles, $recursion, $group);
            if(! $recursion)
            {
                $this->to_do = apply_filters('print_scripts_array', $this->to_do);
            }

            return $r;
        }

        public function do_head_items()
        {
            $this->do_items(false, 0);

            return $this->done;
        }

        public function do_footer_items()
        {
            $this->do_items(false, 1);

            return $this->done;
        }

        private function get_unaliased_deps(array $deps)
        {
            $flattened = [];
            foreach($deps as $dep)
            {
                if(! isset($this->registered[$dep]))
                {
                    continue;
                }

                if($this->registered[$dep]->src)
                {
                    $flattened[] = $dep;
                }
                elseif($this->registered[$dep]->deps)
                {
                    array_push($flattened, ...$this->get_unaliased_deps($this->registered[$dep]->deps));
                }
            }

            return $flattened;
        }
    }
