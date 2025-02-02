<?php
    /**
     * WP_Theme_JSON_Data class
     *
     * @package    WordPress
     * @subpackage Theme
     * @since      6.1.0
     */

    /**
     * Class to provide access to update a theme.json structure.
     */
    #[AllowDynamicProperties]
    class WP_Theme_JSON_Data
    {
        /**
         * Container of the data to update.
         *
         * @since 6.1.0
         * @var WP_Theme_JSON
         */
        private $theme_json = null;

        /**
         * The origin of the data: default, theme, user, etc.
         *
         * @since 6.1.0
         * @var string
         */
        private $origin = '';

        /**
         * Constructor.
         *
         * @param array  $data   Array following the theme.json specification.
         * @param string $origin The origin of the data: default, theme, user.
         *
         * @since 6.1.0
         *
         * @link  https://developer.wordpress.org/block-editor/reference-guides/theme-json-reference/
         *
         */
        public function __construct($data = [], $origin = 'theme')
        {
            $this->origin = $origin;
            $this->theme_json = new WP_Theme_JSON($data, $this->origin);
        }

        /**
         * Updates the theme.json with the the given data.
         *
         * @param array $new_data Array following the theme.json specification.
         *
         * @return WP_Theme_JSON_Data The own instance with access to the modified data.
         * @since 6.1.0
         *
         */
        public function update_with($new_data)
        {
            $this->theme_json->merge(new WP_Theme_JSON($new_data, $this->origin));

            return $this;
        }

        /**
         * Returns an array containing the underlying data
         * following the theme.json specification.
         *
         * @return array
         * @since 6.1.0
         *
         */
        public function get_data()
        {
            return $this->theme_json->get_raw_data();
        }
    }
