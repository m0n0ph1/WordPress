<?php

    class Walker_PageDropdown extends Walker
    {
        public $tree_type = 'page';

        public $db_fields = [
            'parent' => 'post_parent',
            'id' => 'ID',
        ];

        public function start_el(&$output, $data_object, $depth = 0, $args = [], $current_object_id = 0)
        {
            // Restores the more descriptive, specific name for use within this method.
            $page = $data_object;

            $pad = str_repeat('&nbsp;', $depth * 3);

            if(! isset($args['value_field']) || ! isset($page->{$args['value_field']}))
            {
                $args['value_field'] = 'ID';
            }

            $output .= "\t<option class=\"level-$depth\" value=\"".esc_attr($page->{$args['value_field']}).'"';
            if($page->ID === (int) $args['selected'])
            {
                $output .= ' selected="selected"';
            }
            $output .= '>';

            $title = $page->post_title;
            if('' === $title)
            {
                /* translators: %d: ID of a post. */
                $title = sprintf(__('#%d (no title)'), $page->ID);
            }

            $title = apply_filters('list_pages', $title, $page);

            $output .= $pad.esc_html($title);
            $output .= "</option>\n";
        }
    }
