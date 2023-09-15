<?php

    class _WP_List_Table_Compat extends WP_List_Table
    {
        public $_screen;

        public $_columns;

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

        public function get_columns()
        {
            return $this->_columns;
        }

        protected function get_column_info()
        {
            $columns = get_column_headers($this->_screen);
            $hidden = get_hidden_columns($this->_screen);
            $sortable = [];
            $primary = $this->get_default_primary_column_name();

            return [$columns, $hidden, $sortable, $primary];
        }
    }
