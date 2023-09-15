<?php
    /**
     * Helper functions for displaying a list of items in an ajaxified HTML table.
     *
     * @package    WordPress
     * @subpackage List_Table
     * @since      4.7.0
     */

    /**
     * Helper class to be used only by back compat functions.
     *
     * @since 3.1.0
     */
    class _WP_List_Table_Compat extends WP_List_Table
    {
        public $_screen;

        public $_columns;

        /**
         * Constructor.
         *
         * @param string|WP_Screen $screen  The screen hook name or screen object.
         * @param string[]         $columns An array of columns with column IDs as the keys
         *                                  and translated column names as the values.
         *
         * @since 3.1.0
         *
         */
        public function __construct($screen, $columns = [])
        {
            if(is_string($screen))
            {
                $screen = convert_to_screen($screen);
            }

            $this->_screen = $screen;

            if(! empty($columns))
            {
                $this->_columns = $columns;
                add_filter('manage_'.$screen->id.'_columns', [$this, 'get_columns'], 0);
            }
        }

        /**
         * Gets a list of columns.
         *
         * @return array
         * @since 3.1.0
         *
         */
        public function get_columns()
        {
            return $this->_columns;
        }

        /**
         * Gets a list of all, hidden, and sortable columns.
         *
         * @return array
         * @since 3.1.0
         *
         */
        protected function get_column_info()
        {
            $columns = get_column_headers($this->_screen);
            $hidden = get_hidden_columns($this->_screen);
            $sortable = [];
            $primary = $this->get_default_primary_column_name();

            return [$columns, $hidden, $sortable, $primary];
        }
    }
