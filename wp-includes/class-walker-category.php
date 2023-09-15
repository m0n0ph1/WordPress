<?php

    class Walker_Category extends Walker
    {
        public $tree_type = 'category';

        public $db_fields = [
            'parent' => 'parent',
            'id' => 'term_id',
        ];

        public function start_lvl(&$output, $depth = 0, $args = [])
        {
            if('list' !== $args['style'])
            {
                return;
            }

            $indent = str_repeat("\t", $depth);
            $output .= "$indent<ul class='children'>\n";
        }

        public function end_lvl(&$output, $depth = 0, $args = [])
        {
            if('list' !== $args['style'])
            {
                return;
            }

            $indent = str_repeat("\t", $depth);
            $output .= "$indent</ul>\n";
        }

        public function start_el(&$output, $data_object, $depth = 0, $args = [], $current_object_id = 0)
        {
            // Restores the more descriptive, specific name for use within this method.
            $category = $data_object;

            $cat_name = apply_filters('list_cats', esc_attr($category->name), $category);

            // Don't generate an element if the category name is empty.
            if('' === $cat_name)
            {
                return;
            }

            $atts = [];
            $atts['href'] = get_term_link($category);

            if($args['use_desc_for_title'] && ! empty($category->description))
            {
                $atts['title'] = strip_tags(apply_filters('category_description', $category->description, $category));
            }

            $atts = apply_filters('category_list_link_attributes', $atts, $category, $depth, $args, $current_object_id);

            $attributes = '';
            foreach($atts as $attr => $value)
            {
                if(is_scalar($value) && '' !== $value && false !== $value)
                {
                    $value = ('href' === $attr) ? esc_url($value) : esc_attr($value);
                    $attributes .= ' '.$attr.'="'.$value.'"';
                }
            }

            $link = sprintf('<a%s>%s</a>', $attributes, $cat_name);

            if(! empty($args['feed_image']) || ! empty($args['feed']))
            {
                $link .= ' ';

                if(empty($args['feed_image']))
                {
                    $link .= '(';
                }

                $link .= '<a href="'.esc_url(get_term_feed_link($category, $category->taxonomy, $args['feed_type'])).'"';

                if(empty($args['feed']))
                {
                    /* translators: %s: Category name. */
                    $alt = ' alt="'.sprintf(__('Feed for all posts filed under %s'), $cat_name).'"';
                }
                else
                {
                    $alt = ' alt="'.$args['feed'].'"';
                    $name = $args['feed'];
                    $link .= empty($args['title']) ? '' : $args['title'];
                }

                $link .= '>';

                if(empty($args['feed_image']))
                {
                    $link .= $name;
                }
                else
                {
                    $link .= "<img src='".esc_url($args['feed_image'])."'$alt".' />';
                }

                $link .= '</a>';

                if(empty($args['feed_image']))
                {
                    $link .= ')';
                }
            }

            if(! empty($args['show_count']))
            {
                $link .= ' ('.number_format_i18n($category->count).')';
            }

            if('list' === $args['style'])
            {
                $output .= "\t<li";
                $css_classes = [
                    'cat-item',
                    'cat-item-'.$category->term_id,
                ];

                if(! empty($args['current_category']))
                {
                    // 'current_category' can be an array, so we use `get_terms()`.
                    $_current_terms = get_terms([
                                                    'taxonomy' => $category->taxonomy,
                                                    'include' => $args['current_category'],
                                                    'hide_empty' => false,
                                                ]);

                    foreach($_current_terms as $_current_term)
                    {
                        if($category->term_id === $_current_term->term_id)
                        {
                            $css_classes[] = 'current-cat';
                            $link = str_replace('<a', '<a aria-current="page"', $link);
                        }
                        elseif($category->term_id === $_current_term->parent)
                        {
                            $css_classes[] = 'current-cat-parent';
                        }

                        while($_current_term->parent)
                        {
                            if($category->term_id === $_current_term->parent)
                            {
                                $css_classes[] = 'current-cat-ancestor';
                                break;
                            }

                            $_current_term = get_term($_current_term->parent, $category->taxonomy);
                        }
                    }
                }

                $css_classes = implode(' ', apply_filters('category_css_class', $css_classes, $category, $depth, $args));
                $css_classes = $css_classes ? ' class="'.esc_attr($css_classes).'"' : '';

                $output .= $css_classes;
                $output .= ">$link\n";
            }
            elseif(isset($args['separator']))
            {
                $output .= "\t$link".$args['separator']."\n";
            }
            else
            {
                $output .= "\t$link<br />\n";
            }
        }

        public function end_el(&$output, $data_object, $depth = 0, $args = [])
        {
            if('list' !== $args['style'])
            {
                return;
            }

            $output .= "</li>\n";
        }
    }
