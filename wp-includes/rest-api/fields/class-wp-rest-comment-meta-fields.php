<?php
    /**
     * REST API: WP_REST_Comment_Meta_Fields class
     *
     * @package    WordPress
     * @subpackage REST_API
     * @since      4.7.0
     */

    /**
     * Core class to manage comment meta via the REST API.
     *
     * @since 4.7.0
     *
     * @see   WP_REST_Meta_Fields
     */
    class WP_REST_Comment_Meta_Fields extends WP_REST_Meta_Fields
    {
        /**
         * Retrieves the type for register_rest_field() in the context of comments.
         *
         * @return string The REST field type.
         * @since 4.7.0
         *
         */
        public function get_rest_field_type()
        {
            return 'comment';
        }

        /**
         * Retrieves the comment type for comment meta.
         *
         * @return string The meta type.
         * @since 4.7.0
         *
         */
        protected function get_meta_type()
        {
            return 'comment';
        }

        /**
         * Retrieves the comment meta subtype.
         *
         * @return string 'comment' There are no subtypes.
         * @since 4.9.8
         *
         */
        protected function get_meta_subtype()
        {
            return 'comment';
        }
    }
