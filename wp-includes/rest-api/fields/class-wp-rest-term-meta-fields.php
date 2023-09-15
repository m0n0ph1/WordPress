<?php

    class WP_REST_Term_Meta_Fields extends WP_REST_Meta_Fields
    {
        protected $taxonomy;

        public function __construct($taxonomy)
        {
            $this->taxonomy = $taxonomy;
        }

        public function get_rest_field_type()
        {
            return 'post_tag' === $this->taxonomy ? 'tag' : $this->taxonomy;
        }

        protected function get_meta_type()
        {
            return 'term';
        }

        protected function get_meta_subtype()
        {
            return $this->taxonomy;
        }
    }
