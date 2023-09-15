<?php

    final class WP_Customize_Custom_CSS_Setting extends WP_Customize_Setting
    {
        public $type = 'custom_css';

        public $transport = 'postMessage';

        public $capability = 'edit_css';

        public $stylesheet = '';

        public function __construct($manager, $id, $args = [])
        {
            parent::__construct($manager, $id, $args);
            if('custom_css' !== $this->id_data['base'])
            {
                throw new \RuntimeException('Expected custom_css id_base.');
            }
            if(1 !== count($this->id_data['keys']) || empty($this->id_data['keys'][0]))
            {
                throw new \RuntimeException('Expected single stylesheet key.');
            }
            $this->stylesheet = $this->id_data['keys'][0];
        }

        public function preview()
        {
            if($this->is_previewed)
            {
                return false;
            }
            $this->is_previewed = true;
            add_filter('wp_get_custom_css', [$this, 'filter_previewed_wp_get_custom_css'], 9, 2);

            return true;
        }

        public function filter_previewed_wp_get_custom_css($css, $stylesheet)
        {
            if($stylesheet === $this->stylesheet)
            {
                $customized_value = $this->post_value(null);
                if(! is_null($customized_value))
                {
                    $css = $customized_value;
                }
            }

            return $css;
        }

        public function value()
        {
            if($this->is_previewed)
            {
                $post_value = $this->post_value(null);
                if(null !== $post_value)
                {
                    return $post_value;
                }
            }
            $id_base = $this->id_data['base'];
            $value = '';
            $post = wp_get_custom_css_post($this->stylesheet);
            if($post)
            {
                $value = $post->post_content;
            }
            if(empty($value))
            {
                $value = $this->default;
            }

            $value = apply_filters("customize_value_{$id_base}", $value, $this);

            return $value;
        }

        public function validate($value)
        {
            // Restores the more descriptive, specific name for use within this method.
            $css = $value;

            $validity = new WP_Error();

            if(preg_match('#</?\w+#', $css))
            {
                $validity->add('illegal_markup', __('Markup is not allowed in CSS.'));
            }

            if(! $validity->has_errors())
            {
                $validity = parent::validate($css);
            }

            return $validity;
        }

        public function update($value)
        {
            // Restores the more descriptive, specific name for use within this method.
            $css = $value;

            if(empty($css))
            {
                $css = '';
            }

            $r = wp_update_custom_css_post($css, [
                'stylesheet' => $this->stylesheet,
            ]);

            if($r instanceof WP_Error)
            {
                return false;
            }
            $post_id = $r->ID;

            // Cache post ID in theme mod for performance to avoid additional DB query.
            if($this->manager->get_stylesheet() === $this->stylesheet)
            {
                set_theme_mod('custom_css_post_id', $post_id);
            }

            return $post_id;
        }
    }
