<?php

    class WP_Customize_Sidebar_Section extends WP_Customize_Section
    {
        public $type = 'sidebar';

        public $sidebar_id;

        public function json()
        {
            $json = parent::json();
            $json['sidebarId'] = $this->sidebar_id;

            return $json;
        }

        public function active_callback()
        {
            return $this->manager->widgets->is_sidebar_rendered($this->sidebar_id);
        }
    }
