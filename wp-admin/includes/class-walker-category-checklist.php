<?php

    class Walker_Category_Checklist extends Walker
    {
        public $tree_type = 'category';

        public $db_fields = [
            'parent' => 'parent',
            'id' => 'term_id',
        ]; // TODO: Decouple this.

        public function start_lvl(&$output, $depth = 0, $args = [])
        {
            $indent = str_repeat("\t", $depth);
            $output .= "$indent<ul class='children'>\n";
        }

        public function end_lvl(&$output, $depth = 0, $args = [])
        {
            $indent = str_repeat("\t", $depth);
            $output .= "$indent</ul>\n";
        }

        public function start_el(&$output, $data_object, $depth = 0, $args = [], $current_object_id = 0)
        {
            // Restores the more descriptive, specific name for use within this method.
            $category = $data_object;

            if(empty($args['taxonomy']))
            {
                $taxonomy = 'category';
            }
            else
            {
                $taxonomy = $args['taxonomy'];
            }

            if('category' === $taxonomy)
            {
                $name = 'post_category';
            }
            else
            {
                $name = 'tax_input['.$taxonomy.']';
            }

            $args['popular_cats'] = ! empty($args['popular_cats']) ? array_map('intval', $args['popular_cats']) : [];

            $class = in_array($category->term_id, $args['popular_cats'], true) ? ' class="popular-category"' : '';

            $args['selected_cats'] = ! empty($args['selected_cats']) ? array_map('intval', $args['selected_cats']) : [];

            if(! empty($args['list_only']))
            {
                $aria_checked = 'false';
                $inner_class = 'category';

                if(in_array($category->term_id, $args['selected_cats'], true))
                {
                    $inner_class .= ' selected';
                    $aria_checked = 'true';
                }

                $output .= "\n".'<li'.$class.'>'.'<div class="'.$inner_class.'" data-term-id='.$category->term_id.' tabindex="0" role="checkbox" aria-checked="'.$aria_checked.'">'.esc_html(apply_filters('the_category', $category->name, '', '')).'</div>';
            }
            else
            {
                $is_selected = in_array($category->term_id, $args['selected_cats'], true);
                $is_disabled = ! empty($args['disabled']);

                $output .= "\n<li id='{$taxonomy}-{$category->term_id}'$class>".'<label class="selectit"><input value="'.$category->term_id.'" type="checkbox" name="'.$name.'[]" id="in-'.$taxonomy.'-'.$category->term_id.'"'.checked($is_selected, true, false).disabled($is_disabled, true, false).' /> '.esc_html(apply_filters('the_category', $category->name, '', '')).'</label>';
            }
        }

        public function end_el(&$output, $data_object, $depth = 0, $args = [])
        {
            $output .= "</li>\n";
        }
    }
