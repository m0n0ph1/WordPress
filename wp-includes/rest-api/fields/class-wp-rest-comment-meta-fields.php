<?php

    class WP_REST_Comment_Meta_Fields extends WP_REST_Meta_Fields
    {
        public function get_rest_field_type()
        {
            return 'comment';
        }

        protected function get_meta_type()
        {
            return 'comment';
        }

        protected function get_meta_subtype()
        {
            return 'comment';
        }
    }
