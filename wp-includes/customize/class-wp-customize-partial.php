<?php

    #[AllowDynamicProperties]
    class WP_Customize_Partial
    {
        public $component;

        public $id;

        public $type = 'default';

        public $selector;

        public $settings;

        public $primary_setting;

        public $capability;

        public $render_callback;

        public $container_inclusive = false;

        public $fallback_refresh = true;

        protected $id_data = [];

        public function __construct(WP_Customize_Selective_Refresh $component, $id, $args = [])
        {
            $keys = array_keys(get_object_vars($this));
            foreach($keys as $key)
            {
                if(isset($args[$key]))
                {
                    $this->$key = $args[$key];
                }
            }

            $this->component = $component;
            $this->id = $id;
            $this->id_data['keys'] = explode("\[", str_replace(']', '', $this->id));
            $this->id_data['base'] = array_shift($this->id_data['keys']);

            if(empty($this->render_callback))
            {
                $this->render_callback = [$this, 'render_callback'];
            }

            // Process settings.
            if(! isset($this->settings))
            {
                $this->settings = [$id];
            }
            elseif(is_string($this->settings))
            {
                $this->settings = [$this->settings];
            }

            if(empty($this->primary_setting))
            {
                $this->primary_setting = current($this->settings);
            }
        }

        final public function id_data()
        {
            return $this->id_data;
        }

        final public function render($container_context = [])
        {
            $partial = $this;
            $rendered = false;

            if(! empty($this->render_callback))
            {
                ob_start();
                $return_render = call_user_func($this->render_callback, $this, $container_context);
                $ob_render = ob_get_clean();

                if(null !== $return_render && '' !== $ob_render)
                {
                    _doing_it_wrong(__FUNCTION__, __('Partial render must echo the content or return the content string (or array), but not both.'), '4.5.0');
                }

                /*
                 * Note that the string return takes precedence because the $ob_render may just\
                 * include PHP warnings or notices.
                 */
                $rendered = null !== $return_render ? $return_render : $ob_render;
            }

            $rendered = apply_filters('customize_partial_render', $rendered, $partial, $container_context);

            $rendered = apply_filters("customize_partial_render_{$partial->id}", $rendered, $partial, $container_context);

            return $rendered;
        }

        public function render_callback(WP_Customize_Partial $partial, $context = [])
        {
            unset($partial, $context);

            return false;
        }

        public function json()
        {
            $exports = [
                'settings' => $this->settings,
                'primarySetting' => $this->primary_setting,
                'selector' => $this->selector,
                'type' => $this->type,
                'fallbackRefresh' => $this->fallback_refresh,
                'containerInclusive' => $this->container_inclusive,
            ];

            return $exports;
        }

        final public function check_capabilities()
        {
            if(! empty($this->capability) && ! current_user_can($this->capability))
            {
                return false;
            }
            foreach($this->settings as $setting_id)
            {
                $setting = $this->component->manager->get_setting($setting_id);
                if(! $setting || ! $setting->check_capabilities())
                {
                    return false;
                }
            }

            return true;
        }
    }
