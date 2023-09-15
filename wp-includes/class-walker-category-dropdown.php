<?php

    class Walker_CategoryDropdown extends Walker
    {
        public $tree_type = 'category';

        public $db_fields = [
            'parent' => 'parent',
            'id' => 'term_id',
        ];

        public function start_el(&$output, $data_object, $depth = 0, $args = [], $current_object_id = 0)
        {
            // Restores the more descriptive, specific name for use within this method.
            $category = $data_object;

            $pad = str_repeat('&nbsp;', $depth * 3);

            $cat_name = apply_filters('list_cats', $category->name, $category);

            if(isset($args['value_field']) && isset($category->{$args['value_field']}))
            {
                $value_field = $args['value_field'];
            }
            else
            {
                $value_field = 'term_id';
            }

            $output .= "\t<option class=\"level-$depth\" value=\"".esc_attr($category->{$value_field}).'"';

            // Type-juggling causes false matches, so we force everything to a string.
            if((string) $category->{$value_field} === (string) $args['selected'])
            {
                $output .= ' selected="selected"';
            }
            $output .= '>';
            $output .= $pad.$cat_name;
            if($args['show_count'])
            {
                $output .= '&nbsp;&nbsp;('.number_format_i18n($category->count).')';
            }
            $output .= "</option>\n";
        }
    }
