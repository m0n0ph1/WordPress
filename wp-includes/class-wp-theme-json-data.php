<?php

    #[AllowDynamicProperties]
    class WP_Theme_JSON_Data
    {
        private $theme_json = null;

        private $origin = '';

        public function __construct($data = [], $origin = 'theme')
        {
            $this->origin = $origin;
            $this->theme_json = new WP_Theme_JSON($data, $this->origin);
        }

        public function update_with($new_data)
        {
            $this->theme_json->merge(new WP_Theme_JSON($new_data, $this->origin));

            return $this;
        }

        public function get_data()
        {
            return $this->theme_json->get_raw_data();
        }
    }
