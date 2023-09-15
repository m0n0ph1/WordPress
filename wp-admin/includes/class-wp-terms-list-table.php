<?php

    class WP_Terms_List_Table extends WP_List_Table
    {
        public $callback_args;

        private $level;

        public function __construct($args = [])
        {
            global $post_type, $taxonomy, $action, $tax;

            parent::__construct([
                                    'plural' => 'tags',
                                    'singular' => 'tag',
                                    'screen' => isset($args['screen']) ? $args['screen'] : null,
                                ]);

            $action = $this->screen->action;
            $post_type = $this->screen->post_type;
            $taxonomy = $this->screen->taxonomy;

            if(empty($taxonomy))
            {
                $taxonomy = 'post_tag';
            }

            if(! taxonomy_exists($taxonomy))
            {
                wp_die(__('Invalid taxonomy.'));
            }

            $tax = get_taxonomy($taxonomy);

            // @todo Still needed? Maybe just the show_ui part.
            if(empty($post_type) || ! in_array($post_type, get_post_types(['show_ui' => true]), true))
            {
                $post_type = 'post';
            }
        }

        public function ajax_user_can()
        {
            return current_user_can(get_taxonomy($this->screen->taxonomy)->cap->manage_terms);
        }

        public function prepare_items()
        {
            $taxonomy = $this->screen->taxonomy;

            $tags_per_page = $this->get_items_per_page("edit_{$taxonomy}_per_page");

            if('post_tag' === $taxonomy)
            {
                $tags_per_page = apply_filters('edit_tags_per_page', $tags_per_page);

                $tags_per_page = apply_filters_deprecated('tagsperpage', [$tags_per_page], '2.8.0', 'edit_tags_per_page');
            }
            elseif('category' === $taxonomy)
            {
                $tags_per_page = apply_filters('edit_categories_per_page', $tags_per_page);
            }

            $search = ! empty($_REQUEST['s']) ? trim(wp_unslash($_REQUEST['s'])) : '';

            $args = [
                'taxonomy' => $taxonomy,
                'search' => $search,
                'page' => $this->get_pagenum(),
                'number' => $tags_per_page,
                'hide_empty' => 0,
            ];

            if(! empty($_REQUEST['orderby']))
            {
                $args['orderby'] = trim(wp_unslash($_REQUEST['orderby']));
            }

            if(! empty($_REQUEST['order']))
            {
                $args['order'] = trim(wp_unslash($_REQUEST['order']));
            }

            $args['offset'] = ($args['page'] - 1) * $args['number'];

            // Save the values because 'number' and 'offset' can be subsequently overridden.
            $this->callback_args = $args;

            if(is_taxonomy_hierarchical($taxonomy) && ! isset($args['orderby']))
            {
                // We'll need the full set of terms then.
                $args['number'] = 0;
                $args['offset'] = $args['number'];
            }

            $this->items = get_terms($args);

            $this->set_pagination_args([
                                           'total_items' => wp_count_terms([
                                                                               'taxonomy' => $taxonomy,
                                                                               'search' => $search,
                                                                           ]),
                                           'per_page' => $tags_per_page,
                                       ]);
        }

        public function current_action()
        {
            if(isset($_REQUEST['action']) && isset($_REQUEST['delete_tags']) && 'delete' === $_REQUEST['action'])
            {
                return 'bulk-delete';
            }

            return parent::current_action();
        }

        public function get_columns()
        {
            $columns = [
                'cb' => '<input type="checkbox" />',
                'name' => _x('Name', 'term name'),
                'description' => __('Description'),
                'slug' => __('Slug'),
            ];

            if('link_category' === $this->screen->taxonomy)
            {
                $columns['links'] = __('Links');
            }
            else
            {
                $columns['posts'] = _x('Count', 'Number/count of items');
            }

            return $columns;
        }

        public function display_rows_or_placeholder()
        {
            $taxonomy = $this->screen->taxonomy;

            $number = $this->callback_args['number'];
            $offset = $this->callback_args['offset'];

            // Convert it to table rows.
            $count = 0;

            if(empty($this->items) || ! is_array($this->items))
            {
                echo '<tr class="no-items"><td class="colspanchange" colspan="'.$this->get_column_count().'">';
                $this->no_items();
                echo '</td></tr>';

                return;
            }

            if(is_taxonomy_hierarchical($taxonomy) && ! isset($this->callback_args['orderby']))
            {
                if(! empty($this->callback_args['search']))
                {// Ignore children on searches.
                    $children = [];
                }
                else
                {
                    $children = _get_term_hierarchy($taxonomy);
                }

                /*
                 * Some funky recursion to get the job done (paging & parents mainly) is contained within.
                 * Skip it for non-hierarchical taxonomies for performance sake.
                 */
                $this->_rows($taxonomy, $this->items, $children, $offset, $number, $count);
            }
            else
            {
                foreach($this->items as $term)
                {
                    $this->single_row($term);
                }
            }
        }

        public function no_items()
        {
            echo get_taxonomy($this->screen->taxonomy)->labels->not_found;
        }

        private function _rows($taxonomy, $terms, &$children, $start, $per_page, &$count, $parent_term = 0, $level = 0)
        {
            $end = $start + $per_page;

            foreach($terms as $key => $term)
            {
                if($count >= $end)
                {
                    break;
                }

                if($term->parent !== $parent_term && empty($_REQUEST['s']))
                {
                    continue;
                }

                // If the page starts in a subtree, print the parents.
                if($count === $start && $term->parent > 0 && empty($_REQUEST['s']))
                {
                    $my_parents = [];
                    $parent_ids = [];
                    $p = $term->parent;

                    while($p)
                    {
                        $my_parent = get_term($p, $taxonomy);
                        $my_parents[] = $my_parent;
                        $p = $my_parent->parent;

                        if(in_array($p, $parent_ids, true))
                        { // Prevent parent loops.
                            break;
                        }

                        $parent_ids[] = $p;
                    }

                    unset($parent_ids);

                    $num_parents = count($my_parents);

                    while($my_parent = array_pop($my_parents))
                    {
                        echo "\t";
                        $this->single_row($my_parent, $level - $num_parents);
                        --$num_parents;
                    }
                }

                if($count >= $start)
                {
                    echo "\t";
                    $this->single_row($term, $level);
                }

                ++$count;

                unset($terms[$key]);

                if(isset($children[$term->term_id]) && empty($_REQUEST['s']))
                {
                    $this->_rows($taxonomy, $terms, $children, $start, $per_page, $count, $term->term_id, $level + 1);
                }
            }
        }

        public function single_row($tag, $level = 0)
        {
            global $taxonomy;
            $tag = sanitize_term($tag, $taxonomy);

            $this->level = $level;

            if($tag->parent)
            {
                $count = count(get_ancestors($tag->term_id, $taxonomy, 'taxonomy'));
                $level = 'level-'.$count;
            }
            else
            {
                $level = 'level-0';
            }

            echo '<tr id="tag-'.$tag->term_id.'" class="'.$level.'">';
            $this->single_row_columns($tag);
            echo '</tr>';
        }

        public function column_cb($item)
        {
            // Restores the more descriptive, specific name for use within this method.
            $tag = $item;

            if(current_user_can('delete_term', $tag->term_id))
            {
                return sprintf('<label class="label-covers-full-cell" for="cb-select-%1$s"><span class="screen-reader-text">%2$s</span></label>'.'<input type="checkbox" name="delete_tags[]" value="%1$s" id="cb-select-%1$s" />', $tag->term_id, /* translators: Hidden accessibility text. %s: Taxonomy term name. */ sprintf(__('Select %s'), $tag->name));
            }

            return '&nbsp;';
        }

        public function column_name($tag)
        {
            $taxonomy = $this->screen->taxonomy;

            $pad = str_repeat('&#8212; ', max(0, $this->level));

            $name = apply_filters('term_name', $pad.' '.$tag->name, $tag);

            $qe_data = get_term($tag->term_id, $taxonomy, OBJECT, 'edit');

            $uri = wp_doing_ajax() ? wp_get_referer() : $_SERVER['REQUEST_URI'];

            $edit_link = get_edit_term_link($tag, $taxonomy, $this->screen->post_type);

            if($edit_link)
            {
                $edit_link = add_query_arg('wp_http_referer', urlencode(wp_unslash($uri)), $edit_link);
                $name = sprintf('<a class="row-title" href="%s" aria-label="%s">%s</a>', esc_url($edit_link), /* translators: %s: Taxonomy term name. */ esc_attr(sprintf(__('&#8220;%s&#8221; (Edit)'), $tag->name)), $name);
            }

            $output = sprintf('<strong>%s</strong><br />', $name);

            $output .= '<div class="hidden" id="inline_'.$qe_data->term_id.'">';
            $output .= '<div class="name">'.$qe_data->name.'</div>';

            $output .= '<div class="slug">'.apply_filters('editable_slug', $qe_data->slug, $qe_data).'</div>';
            $output .= '<div class="parent">'.$qe_data->parent.'</div></div>';

            return $output;
        }

        public function column_description($tag)
        {
            if($tag->description)
            {
                return $tag->description;
            }
            else
            {
                return '<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">'./* translators: Hidden accessibility text. */ __('No description').'</span>';
            }
        }

        public function column_slug($tag)
        {
            return apply_filters('editable_slug', $tag->slug, $tag);
        }

        public function column_posts($tag)
        {
            $count = number_format_i18n($tag->count);

            $tax = get_taxonomy($this->screen->taxonomy);

            $ptype_object = get_post_type_object($this->screen->post_type);
            if(! $ptype_object->show_ui)
            {
                return $count;
            }

            if($tax->query_var)
            {
                $args = [$tax->query_var => $tag->slug];
            }
            else
            {
                $args = [
                    'taxonomy' => $tax->name,
                    'term' => $tag->slug,
                ];
            }

            if('post' !== $this->screen->post_type)
            {
                $args['post_type'] = $this->screen->post_type;
            }

            if('attachment' === $this->screen->post_type)
            {
                return "<a href='".esc_url(add_query_arg($args, 'upload.php'))."'>$count</a>";
            }

            return "<a href='".esc_url(add_query_arg($args, 'edit.php'))."'>$count</a>";
        }

        public function column_links($tag)
        {
            $count = number_format_i18n($tag->count);

            if($count)
            {
                $count = "<a href='link-manager.php?cat_id=$tag->term_id'>$count</a>";
            }

            return $count;
        }

        public function column_default($item, $column_name)
        {
            // Restores the more descriptive, specific name for use within this method.
            $tag = $item;

            return apply_filters("manage_{$this->screen->taxonomy}_custom_column", '', $column_name, $tag->term_id);
        }

        public function inline_edit()
        {
            $tax = get_taxonomy($this->screen->taxonomy);

            if(! current_user_can($tax->cap->edit_terms))
            {
                return;
            }
            ?>

            <form method="get">
                <table style="display: none">
                    <tbody id="inlineedit">

                    <tr id="inline-edit" class="inline-edit-row" style="display: none">
                        <td colspan="<?php echo $this->get_column_count(); ?>" class="colspanchange">
                            <div class="inline-edit-wrapper">

                                <fieldset>
                                    <legend class="inline-edit-legend"><?php _e('Quick Edit'); ?></legend>
                                    <div class="inline-edit-col">
                                        <label>
                                            <span class="title"><?php _ex('Name', 'term name'); ?></span>
                                            <span class="input-text-wrap"><input type="text"
                                                                                 name="name"
                                                                                 class="ptitle"
                                                                                 value=""/></span>
                                        </label>

                                        <label>
                                            <span class="title"><?php _e('Slug'); ?></span>
                                            <span class="input-text-wrap"><input type="text"
                                                                                 name="slug"
                                                                                 class="ptitle"
                                                                                 value=""/></span>
                                        </label>
                                    </div>
                                </fieldset>

                                <?php
                                    $core_columns = [
                                        'cb' => true,
                                        'description' => true,
                                        'name' => true,
                                        'slug' => true,
                                        'posts' => true,
                                    ];

                                    [$columns] = $this->get_column_info();

                                    foreach($columns as $column_name => $column_display_name)
                                    {
                                        if(isset($core_columns[$column_name]))
                                        {
                                            continue;
                                        }

                                        do_action('quick_edit_custom_box', $column_name, 'edit-tags', $this->screen->taxonomy);
                                    }
                                ?>

                                <div class="inline-edit-save submit">
                                    <button type="button"
                                            class="save button button-primary"><?php echo $tax->labels->update_item; ?></button>
                                    <button type="button" class="cancel button"><?php _e('Cancel'); ?></button>
                                    <span class="spinner"></span>

                                    <?php wp_nonce_field('taxinlineeditnonce', '_inline_edit', false); ?>
                                    <input type="hidden"
                                           name="taxonomy"
                                           value="<?php echo esc_attr($this->screen->taxonomy); ?>"/>
                                    <input type="hidden"
                                           name="post_type"
                                           value="<?php echo esc_attr($this->screen->post_type); ?>"/>

                                    <?php
                                        wp_admin_notice('<p class="error"></p>', [
                                            'type' => 'error',
                                            'additional_classes' => ['notice-alt', 'inline', 'hidden'],
                                            'paragraph_wrap' => false,
                                        ]);
                                    ?>
                                </div>
                            </div>

                        </td>
                    </tr>

                    </tbody>
                </table>
            </form>
            <?php
        }

        protected function get_bulk_actions()
        {
            $actions = [];

            if(current_user_can(get_taxonomy($this->screen->taxonomy)->cap->delete_terms))
            {
                $actions['delete'] = __('Delete');
            }

            return $actions;
        }

        protected function get_sortable_columns()
        {
            $taxonomy = $this->screen->taxonomy;

            if(! isset($_GET['orderby']) && is_taxonomy_hierarchical($taxonomy))
            {
                $name_orderby_text = __('Table ordered hierarchically.');
            }
            else
            {
                $name_orderby_text = __('Table ordered by Name.');
            }

            return [
                'name' => ['name', false, _x('Name', 'term name'), $name_orderby_text, 'asc'],
                'description' => ['description', false, __('Description'), __('Table ordered by Description.')],
                'slug' => ['slug', false, __('Slug'), __('Table ordered by Slug.')],
                'posts' => ['count', false, _x('Count', 'Number/count of items'), __('Table ordered by Posts Count.')],
                'links' => ['count', false, __('Links'), __('Table ordered by Links.')],
            ];
        }

        protected function get_default_primary_column_name()
        {
            return 'name';
        }

        protected function handle_row_actions($item, $column_name, $primary)
        {
            if($primary !== $column_name)
            {
                return '';
            }

            // Restores the more descriptive, specific name for use within this method.
            $tag = $item;

            $taxonomy = $this->screen->taxonomy;
            $uri = wp_doing_ajax() ? wp_get_referer() : $_SERVER['REQUEST_URI'];

            $edit_link = add_query_arg('wp_http_referer', urlencode(wp_unslash($uri)), get_edit_term_link($tag, $taxonomy, $this->screen->post_type));

            $actions = [];

            if(current_user_can('edit_term', $tag->term_id))
            {
                $actions['edit'] = sprintf('<a href="%s" aria-label="%s">%s</a>', esc_url($edit_link), /* translators: %s: Taxonomy term name. */ esc_attr(sprintf(__('Edit &#8220;%s&#8221;'), $tag->name)), __('Edit'));
                $actions['inline hide-if-no-js'] = sprintf('<button type="button" class="button-link editinline" aria-label="%s" aria-expanded="false">%s</button>', /* translators: %s: Taxonomy term name. */ esc_attr(sprintf(__('Quick edit &#8220;%s&#8221; inline'), $tag->name)), __('Quick&nbsp;Edit'));
            }

            if(current_user_can('delete_term', $tag->term_id))
            {
                $actions['delete'] = sprintf('<a href="%s" class="delete-tag aria-button-if-js" aria-label="%s">%s</a>', wp_nonce_url("edit-tags.php?action=delete&amp;taxonomy=$taxonomy&amp;tag_ID=$tag->term_id", 'delete-tag_'.$tag->term_id), /* translators: %s: Taxonomy term name. */ esc_attr(sprintf(__('Delete &#8220;%s&#8221;'), $tag->name)), __('Delete'));
            }

            if(is_term_publicly_viewable($tag))
            {
                $actions['view'] = sprintf('<a href="%s" aria-label="%s">%s</a>', get_term_link($tag), /* translators: %s: Taxonomy term name. */ esc_attr(sprintf(__('View &#8220;%s&#8221; archive'), $tag->name)), __('View'));
            }

            $actions = apply_filters('tag_row_actions', $actions, $tag);

            $actions = apply_filters("{$taxonomy}_row_actions", $actions, $tag);

            return $this->row_actions($actions);
        }
    }
