<?php

    class Walker_Page extends Walker
    {
        public $tree_type = 'page';

        public $db_fields = [
            'parent' => 'post_parent',
            'id' => 'ID',
        ];

        public function start_lvl(&$output, $depth = 0, $args = [])
        {
            if(isset($args['item_spacing']) && 'preserve' === $args['item_spacing'])
            {
                $t = "\t";
                $n = "\n";
            }
            else
            {
                $t = '';
                $n = '';
            }
            $indent = str_repeat($t, $depth);
            $output .= "{$n}{$indent}<ul class='children'>{$n}";
        }

        public function end_lvl(&$output, $depth = 0, $args = [])
        {
            if(isset($args['item_spacing']) && 'preserve' === $args['item_spacing'])
            {
                $t = "\t";
                $n = "\n";
            }
            else
            {
                $t = '';
                $n = '';
            }
            $indent = str_repeat($t, $depth);
            $output .= "{$indent}</ul>{$n}";
        }

        public function start_el(&$output, $data_object, $depth = 0, $args = [], $current_object_id = 0)
        {
            // Restores the more descriptive, specific name for use within this method.
            $page = $data_object;

            $current_page_id = $current_object_id;

            if(isset($args['item_spacing']) && 'preserve' === $args['item_spacing'])
            {
                $t = "\t";
                $n = "\n";
            }
            else
            {
                $t = '';
                $n = '';
            }
            if($depth)
            {
                $indent = str_repeat($t, $depth);
            }
            else
            {
                $indent = '';
            }

            $css_class = ['page_item', 'page-item-'.$page->ID];

            if(isset($args['pages_with_children'][$page->ID]))
            {
                $css_class[] = 'page_item_has_children';
            }

            if(! empty($current_page_id))
            {
                $_current_page = get_post($current_page_id);

                if($_current_page && in_array($page->ID, $_current_page->ancestors, true))
                {
                    $css_class[] = 'current_page_ancestor';
                }

                if($page->ID === (int) $current_page_id)
                {
                    $css_class[] = 'current_page_item';
                }
                elseif($_current_page && $page->ID === $_current_page->post_parent)
                {
                    $css_class[] = 'current_page_parent';
                }
            }
            elseif((int) get_option('page_for_posts') === $page->ID)
            {
                $css_class[] = 'current_page_parent';
            }

            $css_classes = implode(' ', apply_filters('page_css_class', $css_class, $page, $depth, $args, $current_page_id));
            $css_classes = $css_classes ? ' class="'.esc_attr($css_classes).'"' : '';

            if('' === $page->post_title)
            {
                /* translators: %d: ID of a post. */
                $page->post_title = sprintf(__('#%d (no title)'), $page->ID);
            }

            $args['link_before'] = empty($args['link_before']) ? '' : $args['link_before'];
            $args['link_after'] = empty($args['link_after']) ? '' : $args['link_after'];

            $atts = [];
            $atts['href'] = get_permalink($page->ID);
            $atts['aria-current'] = ($page->ID === (int) $current_page_id) ? 'page' : '';

            $atts = apply_filters('page_menu_link_attributes', $atts, $page, $depth, $args, $current_page_id);

            $attributes = '';
            foreach($atts as $attr => $value)
            {
                if(is_scalar($value) && '' !== $value && false !== $value)
                {
                    $value = ('href' === $attr) ? esc_url($value) : esc_attr($value);
                    $attributes .= ' '.$attr.'="'.$value.'"';
                }
            }

            $output .= $indent.sprintf('<li%s><a%s>%s%s%s</a>', $css_classes, $attributes, $args['link_before'], apply_filters('the_title', $page->post_title, $page->ID), $args['link_after']);

            if(! empty($args['show_date']))
            {
                if('modified' === $args['show_date'])
                {
                    $time = $page->post_modified;
                }
                else
                {
                    $time = $page->post_date;
                }

                $date_format = empty($args['date_format']) ? '' : $args['date_format'];
                $output .= ' '.mysql2date($date_format, $time);
            }
        }

        public function end_el(&$output, $data_object, $depth = 0, $args = [])
        {
            if(isset($args['item_spacing']) && 'preserve' === $args['item_spacing'])
            {
                $t = "\t";
                $n = "\n";
            }
            else
            {
                $t = '';
                $n = '';
            }
            $output .= "</li>{$n}";
        }
    }
