<?php
    /**
     * Authentication provider interface
     *
     * @package Requests\Authentication
     */

    namespace WpOrg\Requests;

    /**
     * Authentication provider interface
     *
     * Implement this interface to act as an authentication provider.
     *
     * Parameters should be passed via the constructor where possible, as this
     * makes it much easier for users to use your provider.
     *
     * @see     \WpOrg\Requests\Hooks
     *
     * @package Requests\Authentication
     */
    interface Auth
    {
        /**
         * Register hooks as needed
         *
         * This method is called in {@see \WpOrg\Requests\Requests::request()} when the user
         * has set an instance as the 'auth' option. Use this callback to register all the
         * hooks you'll need.
         *
         * @param \WpOrg\Requests\Hooks $hooks Hook system
         *
         * @see \WpOrg\Requests\Hooks::register()
         */
        public function register(Hooks $hooks);
    }
