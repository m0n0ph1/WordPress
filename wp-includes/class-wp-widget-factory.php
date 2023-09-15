<?php

    #[AllowDynamicProperties]
    class WP_Widget_Factory
    {
        public $widgets = [];

        public function WP_Widget_Factory()
        {
            _deprecated_constructor('WP_Widget_Factory', '4.3.0');
            $this->__construct();
        }

        public function __construct()
        {
            add_action('widgets_init', [$this, '_register_widgets'], 100);
        }

        public function register($widget)
        {
            if($widget instanceof WP_Widget)
            {
                $this->widgets[spl_object_hash($widget)] = $widget;
            }
            else
            {
                $this->widgets[$widget] = new $widget();
            }
        }

        public function unregister($widget)
        {
            if($widget instanceof WP_Widget)
            {
                unset($this->widgets[spl_object_hash($widget)]);
            }
            else
            {
                unset($this->widgets[$widget]);
            }
        }

        public function _register_widgets()
        {
            global $wp_registered_widgets;
            $keys = array_keys($this->widgets);
            $registered = array_keys($wp_registered_widgets);
            $registered = array_map('_get_widget_id_base', $registered);

            foreach($keys as $key)
            {
                // Don't register new widget if old widget with the same id is already registered.
                if(in_array($this->widgets[$key]->id_base, $registered, true))
                {
                    unset($this->widgets[$key]);
                    continue;
                }

                $this->widgets[$key]->_register();
            }
        }

        public function get_widget_object($id_base)
        {
            $key = $this->get_widget_key($id_base);
            if('' === $key)
            {
                return null;
            }

            return $this->widgets[$key];
        }

        public function get_widget_key($id_base)
        {
            foreach($this->widgets as $key => $widget_object)
            {
                if($widget_object->id_base === $id_base)
                {
                    return $key;
                }
            }

            return '';
        }
    }
