<?php
    /**
     * REST API: WP_REST_Term_Meta_Fields class
     *
     * @package    WordPress
     * @subpackage REST_API
     * @since      4.7.0
     */

    /**
     * Core class used to manage meta values for terms via the REST API.
     *
     * @since 4.7.0
     *
     * @see   WP_REST_Meta_Fields
     */
    class WP_REST_Term_Meta_Fields extends WP_REST_Meta_Fields
    {
        /**
         * Taxonomy to register fields for.
         *
         * @since 4.7.0
         * @var string
         */
        protected $taxonomy;

        /**
         * Constructor.
         *
         * @param string $taxonomy Taxonomy to register fields for.
         *
         * @since 4.7.0
         *
         */
        public function __construct($taxonomy)
        {
            $this->taxonomy = $taxonomy;
        }

        /**
         * Retrieves the type for register_rest_field().
         *
         * @return string The REST field type.
         * @since 4.7.0
         *
         */
        public function get_rest_field_type()
        {
            return 'post_tag' === $this->taxonomy ? 'tag' : $this->taxonomy;
        }

        /**
         * Retrieves the term meta type.
         *
         * @return string The meta type.
         * @since 4.7.0
         *
         */
        protected function get_meta_type()
        {
            return 'term';
        }

        /**
         * Retrieves the term meta subtype.
         *
         * @return string Subtype for the meta type, or empty string if no specific subtype.
         * @since 4.9.8
         *
         */
        protected function get_meta_subtype()
        {
            return $this->taxonomy;
        }
    }
