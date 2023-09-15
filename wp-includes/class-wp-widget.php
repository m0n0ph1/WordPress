<?php

    #[AllowDynamicProperties]
    class WP_Widget
    {
        public $id_base;

        public $name;

        public $option_name;

        public $alt_option_name;

        public $widget_options;

        public $control_options;

        public $number = false;

        public $id = false;

        public $updated = false;

        //
        // Member functions that must be overridden by subclasses.
        //

        public function WP_Widget($id_base, $name, $widget_options = [], $control_options = [])
        {
            _deprecated_constructor('WP_Widget', '4.3.0', get_class($this));
            WP_Widget::__construct($id_base, $name, $widget_options, $control_options);
        }

        public function __construct($id_base, $name, $widget_options = [], $control_options = [])
        {
            if(! empty($id_base))
            {
                $id_base = strtolower($id_base);
            }
            else
            {
                $id_base = preg_replace('/(wp_)?widget_/', '', strtolower(get_class($this)));
            }

            $this->id_base = $id_base;
            $this->name = $name;
            $this->option_name = 'widget_'.$this->id_base;
            $this->widget_options = wp_parse_args($widget_options, [
                'classname' => str_replace('\\', '_', $this->option_name),
                'customize_selective_refresh' => false,
            ]);
            $this->control_options = wp_parse_args($control_options, ['id_base' => $this->id_base]);
        }

        public function get_field_name($field_name)
        {
            $pos = strpos($field_name, '[');

            if(false !== $pos)
            {
                // Replace the first occurrence of '[' with ']['.
                $field_name = '['.substr_replace($field_name, '][', $pos, strlen('['));
            }
            else
            {
                $field_name = '['.$field_name.']';
            }

            return 'widget-'.$this->id_base.'['.$this->number.']'.$field_name;
        }

        // Functions you'll need to call.

        public function get_field_id($field_name)
        {
            $field_name = str_replace(['[]', '[', ']'], ['', '-', ''], $field_name);
            $field_name = trim($field_name, '-');

            return 'widget-'.$this->id_base.'-'.$this->number.'-'.$field_name;
        }

        public function _register()
        {
            $settings = $this->get_settings();
            $empty = true;

            // When $settings is an array-like object, get an intrinsic array for use with array_keys().
            if($settings instanceof ArrayObject || $settings instanceof ArrayIterator)
            {
                $settings = $settings->getArrayCopy();
            }

            if(is_array($settings))
            {
                foreach(array_keys($settings) as $number)
                {
                    if(is_numeric($number))
                    {
                        $this->_set($number);
                        $this->_register_one($number);
                        $empty = false;
                    }
                }
            }

            if($empty)
            {
                // If there are none, we register the widget's existence with a generic template.
                $this->_set(1);
                $this->_register_one();
            }
        }

        public function get_settings()
        {
            $settings = get_option($this->option_name);

            if(false === $settings)
            {
                $settings = [];
                if(isset($this->alt_option_name))
                {
                    // Get settings from alternative (legacy) option.
                    $settings = get_option($this->alt_option_name, []);

                    // Delete the alternative (legacy) option as the new option will be created using `$this->option_name`.
                    delete_option($this->alt_option_name);
                }
                // Save an option so it can be autoloaded next time.
                $this->save_settings($settings);
            }

            if(! is_array($settings) && ! ($settings instanceof ArrayObject || $settings instanceof ArrayIterator))
            {
                $settings = [];
            }

            if(! empty($settings) && ! isset($settings['_multiwidget']))
            {
                // Old format, convert if single widget.
                $settings = wp_convert_widget_settings($this->id_base, $this->option_name, $settings);
            }

            unset($settings['_multiwidget'], $settings['__i__']);

            return $settings;
        }

        public function save_settings($settings)
        {
            $settings['_multiwidget'] = 1;
            update_option($this->option_name, $settings);
        }

        public function _set($number)
        {
            $this->number = $number;
            $this->id = $this->id_base.'-'.$number;
        }

        public function _register_one($number = -1)
        {
            wp_register_sidebar_widget($this->id, $this->name, $this->_get_display_callback(), $this->widget_options, ['number' => $number]);

            _register_widget_update_callback($this->id_base, $this->_get_update_callback(), $this->control_options, ['number' => -1]);

            _register_widget_form_callback($this->id, $this->name, $this->_get_form_callback(), $this->control_options, ['number' => $number]);
        }

        public function _get_display_callback()
        {
            return [$this, 'display_callback'];
        }

        public function _get_update_callback()
        {
            return [$this, 'update_callback'];
        }

        public function _get_form_callback()
        {
            return [$this, 'form_callback'];
        }

        public function display_callback($args, $widget_args = 1)
        {
            if(is_numeric($widget_args))
            {
                $widget_args = ['number' => $widget_args];
            }

            $widget_args = wp_parse_args($widget_args, ['number' => -1]);
            $this->_set($widget_args['number']);
            $instances = $this->get_settings();

            if(isset($instances[$this->number]))
            {
                $instance = $instances[$this->number];

                $instance = apply_filters('widget_display_callback', $instance, $this, $args);

                if(false === $instance)
                {
                    return;
                }

                $was_cache_addition_suspended = wp_suspend_cache_addition();
                if($this->is_preview() && ! $was_cache_addition_suspended)
                {
                    wp_suspend_cache_addition(true);
                }

                $this->widget($args, $instance);

                if($this->is_preview())
                {
                    wp_suspend_cache_addition($was_cache_addition_suspended);
                }
            }
        }

        public function is_preview()
        {
            global $wp_customize;

            return (isset($wp_customize) && $wp_customize->is_preview());
        }

        public function widget($args, $instance)
        {
            die('function WP_Widget::widget() must be overridden in a subclass.');
        }

        public function update_callback($deprecated = 1)
        {
            global $wp_registered_widgets;

            $all_instances = $this->get_settings();

            // We need to update the data.
            if($this->updated)
            {
                return;
            }

            if(isset($_POST['delete_widget']) && $_POST['delete_widget'])
            {
                // Delete the settings for this instance of the widget.
                if(isset($_POST['the-widget-id']))
                {
                    $del_id = $_POST['the-widget-id'];
                }
                else
                {
                    return;
                }

                if(isset($wp_registered_widgets[$del_id]['params'][0]['number']))
                {
                    $number = $wp_registered_widgets[$del_id]['params'][0]['number'];

                    if($this->id_base.'-'.$number === $del_id)
                    {
                        unset($all_instances[$number]);
                    }
                }
            }
            else
            {
                if(isset($_POST['widget-'.$this->id_base]) && is_array($_POST['widget-'.$this->id_base]))
                {
                    $settings = $_POST['widget-'.$this->id_base];
                }
                elseif(isset($_POST['id_base']) && $_POST['id_base'] === $this->id_base)
                {
                    $num = $_POST['multi_number'] ? (int) $_POST['multi_number'] : (int) $_POST['widget_number'];
                    $settings = [$num => []];
                }
                else
                {
                    return;
                }

                foreach($settings as $number => $new_instance)
                {
                    $new_instance = stripslashes_deep($new_instance);
                    $this->_set($number);

                    $old_instance = isset($all_instances[$number]) ? $all_instances[$number] : [];

                    $was_cache_addition_suspended = wp_suspend_cache_addition();
                    if($this->is_preview() && ! $was_cache_addition_suspended)
                    {
                        wp_suspend_cache_addition(true);
                    }

                    $instance = $this->update($new_instance, $old_instance);

                    if($this->is_preview())
                    {
                        wp_suspend_cache_addition($was_cache_addition_suspended);
                    }

                    $instance = apply_filters('widget_update_callback', $instance, $new_instance, $old_instance, $this);

                    if(false !== $instance)
                    {
                        $all_instances[$number] = $instance;
                    }

                    break; // Run only once.
                }
            }

            $this->save_settings($all_instances);
            $this->updated = true;
        }

        public function update($new_instance, $old_instance)
        {
            return $new_instance;
        }

        public function form_callback($widget_args = 1)
        {
            if(is_numeric($widget_args))
            {
                $widget_args = ['number' => $widget_args];
            }

            $widget_args = wp_parse_args($widget_args, ['number' => -1]);
            $all_instances = $this->get_settings();

            if(-1 === $widget_args['number'])
            {
                // We echo out a form where 'number' can be set later.
                $this->_set('__i__');
                $instance = [];
            }
            else
            {
                $this->_set($widget_args['number']);
                $instance = $all_instances[$widget_args['number']];
            }

            $instance = apply_filters('widget_form_callback', $instance, $this);

            $return = null;

            if(false !== $instance)
            {
                $return = $this->form($instance);

                do_action_ref_array('in_widget_form', [&$this, &$return, $instance]);
            }

            return $return;
        }

        public function form($instance)
        {
            echo '<p class="no-options-widget">'.__('There are no options for this widget.').'</p>';

            return 'noform';
        }
    }
