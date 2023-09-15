<?php
    /**
     * REST API: WP_REST_User_Meta_Fields class
     *
     * @package    WordPress
     * @subpackage REST_API
     * @since      4.7.0
     */

    /**
     * Core class used to manage meta values for users via the REST API.
     *
     * @since 4.7.0
     *
     * @see   WP_REST_Meta_Fields
     */
    class WP_REST_User_Meta_Fields extends WP_REST_Meta_Fields
    {
        /**
         * Retrieves the type for register_rest_field().
         *
         * @return string The user REST field type.
         * @since 4.7.0
         *
         */
        public function get_rest_field_type()
        {
            return 'user';
        }

        /**
         * Retrieves the user meta type.
         *
         * @return string The user meta type.
         * @since 4.7.0
         *
         */
        protected function get_meta_type()
        {
            return 'user';
        }

        /**
         * Retrieves the user meta subtype.
         *
         * @return string 'user' There are no subtypes.
         * @since 4.9.8
         *
         */
        protected function get_meta_subtype()
        {
            return 'user';
        }
    }
