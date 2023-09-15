<?php

    class WP_MS_Themes_List_Table extends WP_List_Table
    {
        public $site_id;

        public $is_site_themes;

        protected $show_autoupdates = true;

        private $has_items;

        public function __construct($args = [])
        {
            global $status, $page;

            parent::__construct([
                                    'plural' => 'themes',
                                    'screen' => isset($args['screen']) ? $args['screen'] : null,
                                ]);

            $status = isset($_REQUEST['theme_status']) ? $_REQUEST['theme_status'] : 'all';
            if(
                ! in_array($status, [
                    'all',
                    'enabled',
                    'disabled',
                    'upgrade',
                    'search',
                    'broken',
                    'auto-update-enabled',
                    'auto-update-disabled',
                ],         true)
            )
            {
                $status = 'all';
            }

            $page = $this->get_pagenum();

            $this->is_site_themes = 'site-themes-network' === $this->screen->id;

            if($this->is_site_themes)
            {
                $this->site_id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
            }

            $this->show_autoupdates = wp_is_auto_update_enabled_for_type('theme') && ! $this->is_site_themes && current_user_can('update_themes');
        }

        public function ajax_user_can()
        {
            parent::ajax_user_can();
            if($this->is_site_themes)
            {
                return current_user_can('manage_sites');
            }
            else
            {
                return current_user_can('manage_network_themes');
            }
        }

        public function prepare_items()
        {
            global parent::prepare_items();
            $status, $totals, $page, $orderby, $order, $s;

            wp_reset_vars(['orderby', 'order', 's']);

            $themes = [

                'all' => apply_filters('all_themes', wp_get_themes()),
                'search' => [],
                'enabled' => [],
                'disabled' => [],
                'upgrade' => [],
                'broken' => $this->is_site_themes ? [] : wp_get_themes(['errors' => true]),
            ];

            if($this->show_autoupdates)
            {
                $auto_updates = (array) get_site_option('auto_update_themes', []);

                $themes['auto-update-enabled'] = [];
                $themes['auto-update-disabled'] = [];
            }

            if($this->is_site_themes)
            {
                $themes_per_page = $this->get_items_per_page('site_themes_network_per_page');
                $allowed_where = 'site';
            }
            else
            {
                $themes_per_page = $this->get_items_per_page('themes_network_per_page');
                $allowed_where = 'network';
            }

            $current = get_site_transient('update_themes');
            $maybe_update = current_user_can('update_themes') && ! $this->is_site_themes && $current;

            foreach((array) $themes['all'] as $key => $theme)
            {
                if($this->is_site_themes && $theme->is_allowed('network'))
                {
                    unset($themes['all'][$key]);
                    continue;
                }

                if($maybe_update && isset($current->response[$key]))
                {
                    $themes['all'][$key]->update = true;
                    $themes['upgrade'][$key] = $themes['all'][$key];
                }

                $filter = $theme->is_allowed($allowed_where, $this->site_id) ? 'enabled' : 'disabled';
                $themes[$filter][$key] = $themes['all'][$key];

                $theme_data = [
                    'update_supported' => isset($theme->update_supported) ? $theme->update_supported : true,
                ];

                // Extra info if known. array_merge() ensures $theme_data has precedence if keys collide.
                if(isset($current->response[$key]))
                {
                    $theme_data = array_merge((array) $current->response[$key], $theme_data);
                }
                elseif(isset($current->no_update[$key]))
                {
                    $theme_data = array_merge((array) $current->no_update[$key], $theme_data);
                }
                else
                {
                    $theme_data['update_supported'] = false;
                }

                $theme->update_supported = $theme_data['update_supported'];

                /*
                 * Create the expected payload for the auto_update_theme filter, this is the same data
                 * as contained within $updates or $no_updates but used when the Theme is not known.
                 */
                $filter_payload = [
                    'theme' => $key,
                    'new_version' => '',
                    'url' => '',
                    'package' => '',
                    'requires' => '',
                    'requires_php' => '',
                ];

                $filter_payload = (object) array_merge($filter_payload, array_intersect_key($theme_data, $filter_payload));

                $auto_update_forced = wp_is_auto_update_forced_for_item('theme', null, $filter_payload);

                if(! is_null($auto_update_forced))
                {
                    $theme->auto_update_forced = $auto_update_forced;
                }

                if($this->show_autoupdates)
                {
                    $enabled = in_array($key, $auto_updates, true) && $theme->update_supported;
                    if(isset($theme->auto_update_forced))
                    {
                        $enabled = (bool) $theme->auto_update_forced;
                    }

                    if($enabled)
                    {
                        $themes['auto-update-enabled'][$key] = $theme;
                    }
                    else
                    {
                        $themes['auto-update-disabled'][$key] = $theme;
                    }
                }
            }

            if($s)
            {
                $status = 'search';
                $themes['search'] = array_filter(array_merge($themes['all'], $themes['broken']), [
                    $this,
                    '_search_callback',
                ]);
            }

            $totals = [];
            $js_themes = [];
            foreach($themes as $type => $list)
            {
                $totals[$type] = count($list);
                $js_themes[$type] = array_keys($list);
            }

            if(empty($themes[$status]) && ! in_array($status, ['all', 'search'], true))
            {
                $status = 'all';
            }

            $this->items = $themes[$status];
            WP_Theme::sort_by_name($this->items);

            $this->has_items = ! empty($themes['all']);
            $total_this_page = $totals[$status];

            wp_localize_script('updates', '_wpUpdatesItemCounts', [
                'themes' => $js_themes,
                'totals' => wp_get_update_data(),
            ]);

            if($orderby)
            {
                $orderby = ucfirst($orderby);
                $order = strtoupper($order);

                if('Name' === $orderby)
                {
                    if('ASC' === $order)
                    {
                        $this->items = array_reverse($this->items);
                    }
                }
                else
                {
                    uasort($this->items, [$this, '_order_callback']);
                }
            }

            $start = ($page - 1) * $themes_per_page;

            if($total_this_page > $themes_per_page)
            {
                $this->items = array_slice($this->items, $start, $themes_per_page, true);
            }

            $this->set_pagination_args([
                                           'total_items' => $total_this_page,
                                           'per_page' => $themes_per_page,
                                       ]);
        }

        public function _search_callback($theme)
        {
            static $term = null;
            if(is_null($term))
            {
                $term = wp_unslash($_REQUEST['s']);
            }

            foreach(['Name', 'Description', 'Author', 'Author', 'AuthorURI'] as $field)
            {
                // Don't mark up; Do translate.
                if(false !== stripos($theme->display($field, false, true), $term))
                {
                    return true;
                }
            }

            return false !== stripos($theme->get_stylesheet(), $term) || false !== stripos($theme->get_template(), $term);
        }

        public function _order_callback($theme_a, $theme_b)
        {
            global $orderby, $order;

            $a = $theme_a[$orderby];
            $b = $theme_b[$orderby];

            if($a === $b)
            {
                return 0;
            }

            if('DESC' === $order)
            {
                if($a < $b)
                {
                    return 1;
                }

                return -1;
            }
            else
            {
                if($a < $b)
                {
                    return -1;
                }

                return 1;
            }
        }

        // Not used by any core columns.

        public function no_items()
        {
            parent::no_items();
            if($this->has_items)
            {
                _e('No themes found.');
            }
            else
            {
                _e('No themes are currently available.');
            }
        }

        public function get_columns()
        {
            parent::get_columns();
            $columns = [
                'cb' => '<input type="checkbox" />',
                'name' => __('Theme'),
                'description' => __('Description'),
            ];

            if($this->show_autoupdates)
            {
                $columns['auto-updates'] = __('Automatic Updates');
            }

            return $columns;
        }

        public function display_rows()
        {
            parent::display_rows();
            foreach($this->items as $theme)
            {
                $this->single_row($theme);
            }
        }

        public function single_row($theme)
        {
            global parent::single_row($item);
            $status, $totals;

            if($this->is_site_themes)
            {
                $allowed = $theme->is_allowed('site', $this->site_id);
            }
            else
            {
                $allowed = $theme->is_allowed('network');
            }

            $stylesheet = $theme->get_stylesheet();

            $class = ! $allowed ? 'inactive' : 'active';
            if(! empty($totals['upgrade']) && ! empty($theme->update))
            {
                $class .= ' update';
            }

            printf('<tr class="%s" data-slug="%s">', esc_attr($class), esc_attr($stylesheet));

            $this->single_row_columns($theme);

            echo '</tr>';

            if($this->is_site_themes)
            {
                remove_action("after_theme_row_$stylesheet", 'wp_theme_update_row');
            }

            do_action('after_theme_row', $stylesheet, $theme, $status);

            do_action("after_theme_row_{$stylesheet}", $stylesheet, $theme, $status);
        }

        public function single_row_columns($item)
        {
            [$columns, $hidden, $sortable, $primary] = $this->get_column_info();

            foreach($columns as $column_name => $column_display_name)
            {
                $extra_classes = '';
                if(in_array($column_name, $hidden, true))
                {
                    $extra_classes .= ' hidden';
                }

                switch($column_name)
                {
                    case 'cb':
                        echo '<th scope="row" class="check-column">';

                        $this->column_cb($item);

                        echo '</th>';
                        break;

                    case 'name':
                        $active_theme_label = '';

                        /* The presence of the site_id property means that this is a subsite view and a label for the active theme needs to be added */
                        if(! empty($this->site_id))
                        {
                            $stylesheet = get_blog_option($this->site_id, 'stylesheet');
                            $template = get_blog_option($this->site_id, 'template');

                            /* Add a label for the active template */
                            if($item->get_template() === $template)
                            {
                                $active_theme_label = ' &mdash; '.__('Active Theme');
                            }

                            /* In case this is a child theme, label it properly */
                            if($stylesheet !== $template && $item->get_stylesheet() === $stylesheet)
                            {
                                $active_theme_label = ' &mdash; '.__('Active Child Theme');
                            }
                        }

                        echo "<td class='theme-title column-primary{$extra_classes}'><strong>".$item->display('Name').$active_theme_label.'</strong>';

                        $this->column_name($item);

                        echo '</td>';
                        break;

                    case 'description':
                        echo "<td class='column-description desc{$extra_classes}'>";

                        $this->column_description($item);

                        echo '</td>';
                        break;

                    case 'auto-updates':
                        echo "<td class='column-auto-updates{$extra_classes}'>";

                        $this->column_autoupdates($item);

                        echo '</td>';
                        break;
                    default:
                        echo "<td class='$column_name column-$column_name{$extra_classes}'>";

                        $this->column_default($item, $column_name);

                        echo '</td>';
                        break;
                }
            }
        }

        public function column_cb($item)
        {
            // Restores the more descriptive, specific name for use within this method.
            $theme = $item;

            $checkbox_id = 'checkbox_'.md5($theme->get('Name'));
            ?>
            <label class="label-covers-full-cell" for="<?php echo $checkbox_id; ?>">
			<span class="screen-reader-text">
			<?php
                printf(/* translators: Hidden accessibility text. %s: Theme name */ __('Select %s'), $theme->display('Name'));
            ?>
			</span>
            </label>
            <input type="checkbox"
                   name="checked[]"
                   value="<?php echo esc_attr($theme->get_stylesheet()); ?>"
                   id="<?php echo $checkbox_id; ?>"/>
            <?php
        }

        public function column_name($theme)
        {
            global $status, $page, $s;

            $context = $status;

            if($this->is_site_themes)
            {
                $url = "site-themes.php?id={$this->site_id}&amp;";
                $allowed = $theme->is_allowed('site', $this->site_id);
            }
            else
            {
                $url = 'themes.php?';
                $allowed = $theme->is_allowed('network');
            }

            // Pre-order.
            $actions = [
                'enable' => '',
                'disable' => '',
                'delete' => '',
            ];

            $stylesheet = $theme->get_stylesheet();
            $theme_key = urlencode($stylesheet);

            if($allowed)
            {
                $url = add_query_arg([
                                         'action' => 'disable',
                                         'theme' => $theme_key,
                                         'paged' => $page,
                                         's' => $s,
                                     ], $url);

                if($this->is_site_themes)
                {
                    /* translators: %s: Theme name. */
                    $aria_label = sprintf(__('Disable %s'), $theme->display('Name'));
                }
                else
                {
                    /* translators: %s: Theme name. */
                    $aria_label = sprintf(__('Network Disable %s'), $theme->display('Name'));
                }

                $actions['disable'] = sprintf('<a href="%s" aria-label="%s">%s</a>', esc_url(wp_nonce_url($url, 'disable-theme_'.$stylesheet)), esc_attr($aria_label), ($this->is_site_themes ? __('Disable') : __('Network Disable')));
            }
            else
            {
                if(! $theme->errors())
                {
                    $url = add_query_arg([
                                             'action' => 'enable',
                                             'theme' => $theme_key,
                                             'paged' => $page,
                                             's' => $s,
                                         ], $url);

                    if($this->is_site_themes)
                    {
                        /* translators: %s: Theme name. */
                        $aria_label = sprintf(__('Enable %s'), $theme->display('Name'));
                    }
                    else
                    {
                        /* translators: %s: Theme name. */
                        $aria_label = sprintf(__('Network Enable %s'), $theme->display('Name'));
                    }

                    $actions['enable'] = sprintf('<a href="%s" class="edit" aria-label="%s">%s</a>', esc_url(wp_nonce_url($url, 'enable-theme_'.$stylesheet)), esc_attr($aria_label), ($this->is_site_themes ? __('Enable') : __('Network Enable')));
                }
            }

            if(! $allowed && ! $this->is_site_themes && current_user_can('delete_themes') && get_option('stylesheet') !== $stylesheet && get_option('template') !== $stylesheet)
            {
                $url = add_query_arg([
                                         'action' => 'delete-selected',
                                         'checked[]' => $theme_key,
                                         'theme_status' => $context,
                                         'paged' => $page,
                                         's' => $s,
                                     ], 'themes.php');

                /* translators: %s: Theme name. */
                $aria_label = sprintf(_x('Delete %s', 'theme'), $theme->display('Name'));

                $actions['delete'] = sprintf('<a href="%s" class="delete" aria-label="%s">%s</a>', esc_url(wp_nonce_url($url, 'bulk-themes')), esc_attr($aria_label), __('Delete'));
            }

            $actions = apply_filters('theme_action_links', array_filter($actions), $theme, $context);

            $actions = apply_filters("theme_action_links_{$stylesheet}", $actions, $theme, $context);

            echo $this->row_actions($actions, true);
        }

        public function column_description($theme)
        {
            global $status, $totals;

            if($theme->errors())
            {
                $pre = 'broken' === $status ? __('Broken Theme:').' ' : '';
                echo '<p><strong class="error-message">'.$pre.$theme->errors()->get_error_message().'</strong></p>';
            }

            if($this->is_site_themes)
            {
                $allowed = $theme->is_allowed('site', $this->site_id);
            }
            else
            {
                $allowed = $theme->is_allowed('network');
            }

            $class = ! $allowed ? 'inactive' : 'active';
            if(! empty($totals['upgrade']) && ! empty($theme->update))
            {
                $class .= ' update';
            }

            echo "<div class='theme-description'><p>".$theme->display('Description')."</p></div>
			<div class='$class second theme-version-author-uri'>";

            $stylesheet = $theme->get_stylesheet();
            $theme_meta = [];

            if($theme->get('Version'))
            {
                /* translators: %s: Theme version. */
                $theme_meta[] = sprintf(__('Version %s'), $theme->display('Version'));
            }

            /* translators: %s: Theme author. */
            $theme_meta[] = sprintf(__('By %s'), $theme->display('Author'));

            if($theme->get('ThemeURI'))
            {
                /* translators: %s: Theme name. */
                $aria_label = sprintf(__('Visit theme site for %s'), $theme->display('Name'));

                $theme_meta[] = sprintf('<a href="%s" aria-label="%s">%s</a>', $theme->display('ThemeURI'), esc_attr($aria_label), __('Visit Theme Site'));
            }

            if($theme->parent())
            {
                $theme_meta[] = sprintf(/* translators: %s: Theme name. */ __('Child theme of %s'), '<strong>'.$theme->parent()->display('Name').'</strong>');
            }

            $theme_meta = apply_filters('theme_row_meta', $theme_meta, $stylesheet, $theme, $status);

            echo implode(' | ', $theme_meta);

            echo '</div>';
        }

        public function column_autoupdates($theme)
        {
            global $status, $page;

            static $auto_updates, $available_updates;

            if(! $auto_updates)
            {
                $auto_updates = (array) get_site_option('auto_update_themes', []);
            }
            if(! $available_updates)
            {
                $available_updates = get_site_transient('update_themes');
            }

            $stylesheet = $theme->get_stylesheet();

            if(isset($theme->auto_update_forced))
            {
                if($theme->auto_update_forced)
                {
                    // Forced on.
                    $text = __('Auto-updates enabled');
                }
                else
                {
                    $text = __('Auto-updates disabled');
                }
                $action = 'unavailable';
                $time_class = ' hidden';
            }
            elseif(empty($theme->update_supported))
            {
                $text = '';
                $action = 'unavailable';
                $time_class = ' hidden';
            }
            elseif(in_array($stylesheet, $auto_updates, true))
            {
                $text = __('Disable auto-updates');
                $action = 'disable';
                $time_class = '';
            }
            else
            {
                $text = __('Enable auto-updates');
                $action = 'enable';
                $time_class = ' hidden';
            }

            $query_args = [
                'action' => "{$action}-auto-update",
                'theme' => $stylesheet,
                'paged' => $page,
                'theme_status' => $status,
            ];

            $url = add_query_arg($query_args, 'themes.php');

            if('unavailable' === $action)
            {
                $html[] = '<span class="label">'.$text.'</span>';
            }
            else
            {
                $html[] = sprintf('<a href="%s" class="toggle-auto-update aria-button-if-js" data-wp-action="%s">', wp_nonce_url($url, 'updates'), $action);

                $html[] = '<span class="dashicons dashicons-update spin hidden" aria-hidden="true"></span>';
                $html[] = '<span class="label">'.$text.'</span>';
                $html[] = '</a>';
            }

            if(isset($available_updates->response[$stylesheet]))
            {
                $html[] = sprintf('<div class="auto-update-time%s">%s</div>', $time_class, wp_get_auto_update_message());
            }

            $html = implode('', $html);

            echo apply_filters('theme_auto_update_setting_html', $html, $stylesheet, $theme);

            wp_admin_notice('', [
                'type' => 'error',
                'additional_classes' => ['notice-alt', 'inline', 'hidden'],
            ]);
        }

        public function column_default($item, $column_name)
        {
            // Restores the more descriptive, specific name for use within this method.
            $theme = $item;

            $stylesheet = $theme->get_stylesheet();

            do_action('manage_themes_custom_column', $column_name, $stylesheet, $theme);
        }

        protected function get_table_classes()
        {
            // @todo Remove and add CSS for .themes.
            return ['widefat', 'plugins'];
        }

        protected function get_sortable_columns()
        {
            return [
                'name' => ['name', false, __('Theme'), __('Table ordered by Theme Name.'), 'asc'],
            ];
        }

        protected function get_primary_column_name()
        {
            parent::get_primary_column_name();
            return 'name';
        }

        protected function get_views()
        {
            global $totals, $status;

            $status_links = [];
            foreach($totals as $type => $count)
            {
                if(! $count)
                {
                    continue;
                }

                switch($type)
                {
                    case 'all':
                        /* translators: %s: Number of themes. */ $text = _nx('All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $count, 'themes');
                        break;
                    case 'enabled':
                        /* translators: %s: Number of themes. */ $text = _nx('Enabled <span class="count">(%s)</span>', 'Enabled <span class="count">(%s)</span>', $count, 'themes');
                        break;
                    case 'disabled':
                        /* translators: %s: Number of themes. */ $text = _nx('Disabled <span class="count">(%s)</span>', 'Disabled <span class="count">(%s)</span>', $count, 'themes');
                        break;
                    case 'upgrade':
                        /* translators: %s: Number of themes. */ $text = _nx('Update Available <span class="count">(%s)</span>', 'Update Available <span class="count">(%s)</span>', $count, 'themes');
                        break;
                    case 'broken':
                        /* translators: %s: Number of themes. */ $text = _nx('Broken <span class="count">(%s)</span>', 'Broken <span class="count">(%s)</span>', $count, 'themes');
                        break;
                    case 'auto-update-enabled':
                        /* translators: %s: Number of themes. */ $text = _n('Auto-updates Enabled <span class="count">(%s)</span>', 'Auto-updates Enabled <span class="count">(%s)</span>', $count);
                        break;
                    case 'auto-update-disabled':
                        /* translators: %s: Number of themes. */ $text = _n('Auto-updates Disabled <span class="count">(%s)</span>', 'Auto-updates Disabled <span class="count">(%s)</span>', $count);
                        break;
                }

                if($this->is_site_themes)
                {
                    $url = 'site-themes.php?id='.$this->site_id;
                }
                else
                {
                    $url = 'themes.php';
                }

                if('search' !== $type)
                {
                    $status_links[$type] = [
                        'url' => esc_url(add_query_arg('theme_status', $type, $url)),
                        'label' => sprintf($text, number_format_i18n($count)),
                        'current' => $type === $status,
                    ];
                }
            }

            return $this->get_views_links($status_links);
        }

        protected function get_bulk_actions()
        {
            global $status;

            $actions = [];
            if('enabled' !== $status)
            {
                $actions['enable-selected'] = $this->is_site_themes ? __('Enable') : __('Network Enable');
            }
            if('disabled' !== $status)
            {
                $actions['disable-selected'] = $this->is_site_themes ? __('Disable') : __('Network Disable');
            }
            if(! $this->is_site_themes)
            {
                if(current_user_can('update_themes'))
                {
                    $actions['update-selected'] = __('Update');
                }
                if(current_user_can('delete_themes'))
                {
                    $actions['delete-selected'] = __('Delete');
                }
            }

            if($this->show_autoupdates)
            {
                if('auto-update-enabled' !== $status)
                {
                    $actions['enable-auto-update-selected'] = __('Enable Auto-updates');
                }

                if('auto-update-disabled' !== $status)
                {
                    $actions['disable-auto-update-selected'] = __('Disable Auto-updates');
                }
            }

            return $actions;
        }
    }
