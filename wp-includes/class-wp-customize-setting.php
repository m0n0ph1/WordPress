<?php

    #[AllowDynamicProperties]
    class WP_Customize_Setting
    {
        protected static $aggregated_multidimensionals = [];

        public $manager;

        public $id;

        public $type = 'theme_mod';

        public $capability = 'edit_theme_options';

        public $theme_supports = '';

        public $default = '';

        public $transport = 'refresh';

        public $validate_callback = '';

        public $sanitize_callback = '';

        public $sanitize_js_callback = '';

        public $dirty = false;

        protected $id_data = [];

        protected $is_previewed = false;

        protected $is_multidimensional_aggregated = false;

        protected $_previewed_blog_id;

        protected $_original_value;

        public function __construct($manager, $id, $args = [])
        {
            $keys = array_keys(get_object_vars($this));
            foreach($keys as $key)
            {
                if(isset($args[$key]))
                {
                    $this->$key = $args[$key];
                }
            }

            $this->manager = $manager;
            $this->id = $id;

            // Parse the ID for array keys.
            $this->id_data['keys'] = preg_split('/\[/', str_replace(']', '', $this->id));
            $this->id_data['base'] = array_shift($this->id_data['keys']);

            // Rebuild the ID.
            $this->id = $this->id_data['base'];
            if(! empty($this->id_data['keys']))
            {
                $this->id .= '['.implode('][', $this->id_data['keys']).']';
            }

            if($this->validate_callback)
            {
                add_filter("customize_validate_{$this->id}", $this->validate_callback, 10, 3);
            }
            if($this->sanitize_callback)
            {
                add_filter("customize_sanitize_{$this->id}", $this->sanitize_callback, 10, 2);
            }
            if($this->sanitize_js_callback)
            {
                add_filter("customize_sanitize_js_{$this->id}", $this->sanitize_js_callback, 10, 2);
            }

            if('option' === $this->type || 'theme_mod' === $this->type)
            {
                // Other setting types can opt-in to aggregate multidimensional explicitly.
                $this->aggregate_multidimensional();

                // Allow option settings to indicate whether they should be autoloaded.
                if('option' === $this->type && isset($args['autoload']))
                {
                    self::$aggregated_multidimensionals[$this->type][$this->id_data['base']]['autoload'] = $args['autoload'];
                }
            }
        }

        protected function aggregate_multidimensional()
        {
            $id_base = $this->id_data['base'];
            if(! isset(self::$aggregated_multidimensionals[$this->type]))
            {
                self::$aggregated_multidimensionals[$this->type] = [];
            }
            if(! isset(self::$aggregated_multidimensionals[$this->type][$id_base]))
            {
                self::$aggregated_multidimensionals[$this->type][$id_base] = [
                    'previewed_instances' => [],
                    // Calling preview() will add the $setting to the array.
                    'preview_applied_instances' => [],
                    // Flags for which settings have had their values applied.
                    'root_value' => $this->get_root_value([]),
                    // Root value for initial state, manipulated by preview and update calls.
                ];
            }

            if(! empty($this->id_data['keys']))
            {
                // Note the preview-applied flag is cleared at priority 9 to ensure it is cleared before a deferred-preview runs.
                add_action("customize_post_value_set_{$this->id}", [
                    $this,
                    '_clear_aggregated_multidimensional_preview_applied_flag'
                ],         9);
                $this->is_multidimensional_aggregated = true;
            }
        }

        protected function get_root_value($default_value = null)
        {
            $id_base = $this->id_data['base'];
            if('option' === $this->type)
            {
                return get_option($id_base, $default_value);
            }
            elseif('theme_mod' === $this->type)
            {
                return get_theme_mod($id_base, $default_value);
            }
            else
            {
                /*
                 * Any WP_Customize_Setting subclass implementing aggregate multidimensional
                 * will need to override this method to obtain the data from the appropriate
                 * location.
                 */
                return $default_value;
            }
        }

        public static function reset_aggregated_multidimensionals()
        {
            self::$aggregated_multidimensionals = [];
        }

        final public function id_data()
        {
            return $this->id_data;
        }

        public function preview()
        {
            if(! isset($this->_previewed_blog_id))
            {
                $this->_previewed_blog_id = get_current_blog_id();
            }

            // Prevent re-previewing an already-previewed setting.
            if($this->is_previewed)
            {
                return true;
            }

            $id_base = $this->id_data['base'];
            $is_multidimensional = ! empty($this->id_data['keys']);
            $multidimensional_filter = [$this, '_multidimensional_preview_filter'];

            /*
             * Check if the setting has a pre-existing value (an isset check),
             * and if doesn't have any incoming post value. If both checks are true,
             * then the preview short-circuits because there is nothing that needs
             * to be previewed.
             */
            $undefined = new stdClass();
            $needs_preview = ($undefined !== $this->post_value($undefined));
            $value = null;

            // Since no post value was defined, check if we have an initial value set.
            if(! $needs_preview)
            {
                if($this->is_multidimensional_aggregated)
                {
                    $root = self::$aggregated_multidimensionals[$this->type][$id_base]['root_value'];
                    $value = $this->multidimensional_get($root, $this->id_data['keys'], $undefined);
                }
                else
                {
                    $default = $this->default;
                    $this->default = $undefined; // Temporarily set default to undefined so we can detect if existing value is set.
                    $value = $this->value();
                    $this->default = $default;
                }
                $needs_preview = ($undefined === $value); // Because the default needs to be supplied.
            }

            // If the setting does not need previewing now, defer to when it has a value to preview.
            if(! $needs_preview)
            {
                if(! has_action("customize_post_value_set_{$this->id}", [$this, 'preview']))
                {
                    add_action("customize_post_value_set_{$this->id}", [$this, 'preview']);
                }

                return false;
            }

            switch($this->type)
            {
                case 'theme_mod':
                    if(! $is_multidimensional)
                    {
                        add_filter("theme_mod_{$id_base}", [$this, '_preview_filter']);
                    }
                    else
                    {
                        if(empty(self::$aggregated_multidimensionals[$this->type][$id_base]['previewed_instances']))
                        {
                            // Only add this filter once for this ID base.
                            add_filter("theme_mod_{$id_base}", $multidimensional_filter);
                        }
                        self::$aggregated_multidimensionals[$this->type][$id_base]['previewed_instances'][$this->id] = $this;
                    }
                    break;
                case 'option':
                    if(! $is_multidimensional)
                    {
                        add_filter("pre_option_{$id_base}", [$this, '_preview_filter']);
                    }
                    else
                    {
                        if(empty(self::$aggregated_multidimensionals[$this->type][$id_base]['previewed_instances']))
                        {
                            // Only add these filters once for this ID base.
                            add_filter("option_{$id_base}", $multidimensional_filter);
                            add_filter("default_option_{$id_base}", $multidimensional_filter);
                        }
                        self::$aggregated_multidimensionals[$this->type][$id_base]['previewed_instances'][$this->id] = $this;
                    }
                    break;
                default:
                    do_action("customize_preview_{$this->id}", $this);

                    do_action("customize_preview_{$this->type}", $this);
            }

            $this->is_previewed = true;

            return true;
        }

        final public function post_value($default_value = null)
        {
            return $this->manager->post_value($this, $default_value);
        }

        final protected function multidimensional_get($root, $keys, $default_value = null)
        {
            if(empty($keys))
            { // If there are no keys, test the root.
                return isset($root) ? $root : $default_value;
            }

            $result = $this->multidimensional($root, $keys);

            return isset($result) ? $result['node'][$result['key']] : $default_value;
        }

        final protected function multidimensional(&$root, $keys, $create = false)
        {
            if($create && empty($root))
            {
                $root = [];
            }

            if(! isset($root) || empty($keys))
            {
                return;
            }

            $last = array_pop($keys);
            $node = &$root;

            foreach($keys as $key)
            {
                if($create && ! isset($node[$key]))
                {
                    $node[$key] = [];
                }

                if(! is_array($node) || ! isset($node[$key]))
                {
                    return;
                }

                $node = &$node[$key];
            }

            if($create)
            {
                if(! is_array($node))
                {
                    // Account for an array overriding a string or object value.
                    $node = [];
                }
                if(! isset($node[$last]))
                {
                    $node[$last] = [];
                }
            }

            if(! isset($node[$last]))
            {
                return;
            }

            return [
                'root' => &$root,
                'node' => &$node,
                'key' => $last,
            ];
        }

        public function value()
        {
            $id_base = $this->id_data['base'];
            $is_core_type = ('option' === $this->type || 'theme_mod' === $this->type);

            if(! $is_core_type && ! $this->is_multidimensional_aggregated)
            {
                // Use post value if previewed and a post value is present.
                if($this->is_previewed)
                {
                    $value = $this->post_value(null);
                    if(null !== $value)
                    {
                        return $value;
                    }
                }

                $value = $this->get_root_value($this->default);

                $value = apply_filters("customize_value_{$id_base}", $value, $this);
            }
            elseif($this->is_multidimensional_aggregated)
            {
                $root_value = self::$aggregated_multidimensionals[$this->type][$id_base]['root_value'];
                $value = $this->multidimensional_get($root_value, $this->id_data['keys'], $this->default);

                // Ensure that the post value is used if the setting is previewed, since preview filters aren't applying on cached $root_value.
                if($this->is_previewed)
                {
                    $value = $this->post_value($value);
                }
            }
            else
            {
                $value = $this->get_root_value($this->default);
            }

            return $value;
        }

        final public function _clear_aggregated_multidimensional_preview_applied_flag()
        {
            unset(self::$aggregated_multidimensionals[$this->type][$this->id_data['base']]['preview_applied_instances'][$this->id]);
        }

        public function _preview_filter($original)
        {
            if(! $this->is_current_blog_previewed())
            {
                return $original;
            }

            $undefined = new stdClass(); // Symbol hack.
            $post_value = $this->post_value($undefined);
            if($undefined !== $post_value)
            {
                $value = $post_value;
            }
            else
            {
                /*
                 * Note that we don't use $original here because preview() will
                 * not add the filter in the first place if it has an initial value
                 * and there is no post value.
                 */
                $value = $this->default;
            }

            return $value;
        }

        public function is_current_blog_previewed()
        {
            if(! isset($this->_previewed_blog_id))
            {
                return false;
            }

            return (get_current_blog_id() === $this->_previewed_blog_id);
        }

        final public function _multidimensional_preview_filter($original)
        {
            if(! $this->is_current_blog_previewed())
            {
                return $original;
            }

            $id_base = $this->id_data['base'];

            // If no settings have been previewed yet (which should not be the case, since $this is), just pass through the original value.
            if(empty(self::$aggregated_multidimensionals[$this->type][$id_base]['previewed_instances']))
            {
                return $original;
            }

            foreach(self::$aggregated_multidimensionals[$this->type][$id_base]['previewed_instances'] as $previewed_setting)
            {
                // Skip applying previewed value for any settings that have already been applied.
                if(! empty(self::$aggregated_multidimensionals[$this->type][$id_base]['preview_applied_instances'][$previewed_setting->id]))
                {
                    continue;
                }

                // Do the replacements of the posted/default sub value into the root value.
                $value = $previewed_setting->post_value($previewed_setting->default);
                $root = self::$aggregated_multidimensionals[$previewed_setting->type][$id_base]['root_value'];
                $root = $previewed_setting->multidimensional_replace($root, $previewed_setting->id_data['keys'], $value);
                self::$aggregated_multidimensionals[$previewed_setting->type][$id_base]['root_value'] = $root;

                // Mark this setting having been applied so that it will be skipped when the filter is called again.
                self::$aggregated_multidimensionals[$previewed_setting->type][$id_base]['preview_applied_instances'][$previewed_setting->id] = true;
            }

            return self::$aggregated_multidimensionals[$this->type][$id_base]['root_value'];
        }

        final protected function multidimensional_replace($root, $keys, $value)
        {
            if(! isset($value))
            {
                return $root;
            }
            elseif(empty($keys))
            { // If there are no keys, we're replacing the root.
                return $value;
            }

            $result = $this->multidimensional($root, $keys, true);

            if(isset($result))
            {
                $result['node'][$result['key']] = $value;
            }

            return $root;
        }

        final public function save()
        {
            $value = $this->post_value();

            if(! $this->check_capabilities() || ! isset($value))
            {
                return false;
            }

            $id_base = $this->id_data['base'];

            do_action("customize_save_{$id_base}", $this);

            $this->update($value);
        }

        final public function check_capabilities()
        {
            if($this->capability && ! current_user_can($this->capability))
            {
                return false;
            }

            if($this->theme_supports && ! current_theme_supports(...(array) $this->theme_supports))
            {
                return false;
            }

            return true;
        }

        protected function update($value)
        {
            $id_base = $this->id_data['base'];
            if('option' === $this->type || 'theme_mod' === $this->type)
            {
                if(! $this->is_multidimensional_aggregated)
                {
                    return $this->set_root_value($value);
                }
                else
                {
                    $root = self::$aggregated_multidimensionals[$this->type][$id_base]['root_value'];
                    $root = $this->multidimensional_replace($root, $this->id_data['keys'], $value);
                    self::$aggregated_multidimensionals[$this->type][$id_base]['root_value'] = $root;

                    return $this->set_root_value($root);
                }
            }
            else
            {
                do_action("customize_update_{$this->type}", $value, $this);

                return has_action("customize_update_{$this->type}");
            }
        }

        protected function set_root_value($value)
        {
            $id_base = $this->id_data['base'];
            if('option' === $this->type)
            {
                $autoload = true;
                if(isset(self::$aggregated_multidimensionals[$this->type][$this->id_data['base']]['autoload']))
                {
                    $autoload = self::$aggregated_multidimensionals[$this->type][$this->id_data['base']]['autoload'];
                }

                return update_option($id_base, $value, $autoload);
            }
            elseif('theme_mod' === $this->type)
            {
                set_theme_mod($id_base, $value);

                return true;
            }
            else
            {
                /*
                 * Any WP_Customize_Setting subclass implementing aggregate multidimensional
                 * will need to override this method to obtain the data from the appropriate
                 * location.
                 */
                return false;
            }
        }

        public function sanitize($value)
        {
            return apply_filters("customize_sanitize_{$this->id}", $value, $this);
        }

        public function validate($value)
        {
            if(is_wp_error($value))
            {
                return $value;
            }
            if(is_null($value))
            {
                return new WP_Error('invalid_value', __('Invalid value.'));
            }

            $validity = new WP_Error();

            $validity = apply_filters("customize_validate_{$this->id}", $validity, $value, $this);

            if(is_wp_error($validity) && ! $validity->has_errors())
            {
                $validity = true;
            }

            return $validity;
        }

        public function json()
        {
            return [
                'value' => $this->js_value(),
                'transport' => $this->transport,
                'dirty' => $this->dirty,
                'type' => $this->type,
            ];
        }

        public function js_value()
        {
            $value = apply_filters("customize_sanitize_js_{$this->id}", $this->value(), $this);

            if(is_string($value))
            {
                return html_entity_decode($value, ENT_QUOTES, 'UTF-8');
            }

            return $value;
        }

        protected function _update_theme_mod()
        {
            _deprecated_function(__METHOD__, '4.4.0', __CLASS__.'::update()');
        }

        protected function _update_option()
        {
            _deprecated_function(__METHOD__, '4.4.0', __CLASS__.'::update()');
        }

        final protected function multidimensional_isset($root, $keys)
        {
            $result = $this->multidimensional_get($root, $keys);

            return isset($result);
        }
    }

    require_once ABSPATH.WPINC.'/customize/class-wp-customize-filter-setting.php';

    require_once ABSPATH.WPINC.'/customize/class-wp-customize-header-image-setting.php';

    require_once ABSPATH.WPINC.'/customize/class-wp-customize-background-image-setting.php';

    require_once ABSPATH.WPINC.'/customize/class-wp-customize-nav-menu-item-setting.php';

    require_once ABSPATH.WPINC.'/customize/class-wp-customize-nav-menu-setting.php';
