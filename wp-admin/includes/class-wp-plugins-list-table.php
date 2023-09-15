<?php

    class WP_Plugins_List_Table extends WP_List_Table
    {
        protected $show_autoupdates = true;

        public function __construct($args = [])
        {
            global $status, $page;

            parent::__construct([
                                    'plural' => 'plugins',
                                    'screen' => isset($args['screen']) ? $args['screen'] : null,
                                ]);

            $allowed_statuses = [
                'active',
                'inactive',
                'recently_activated',
                'upgrade',
                'mustuse',
                'dropins',
                'search',
                'paused',
                'auto-update-enabled',
                'auto-update-disabled',
            ];

            $status = 'all';
            if(isset($_REQUEST['plugin_status']) && in_array($_REQUEST['plugin_status'], $allowed_statuses, true))
            {
                $status = $_REQUEST['plugin_status'];
            }

            if(isset($_REQUEST['s']))
            {
                $_SERVER['REQUEST_URI'] = add_query_arg('s', wp_unslash($_REQUEST['s']));
            }

            $page = $this->get_pagenum();

            $this->show_autoupdates = wp_is_auto_update_enabled_for_type('plugin') && current_user_can('update_plugins') && (! is_multisite() || $this->screen->in_admin('network'));
        }

        public function ajax_user_can()
        {
            return current_user_can('activate_plugins');
        }

        public function prepare_items()
        {
            global $status, $plugins, $totals, $page, $orderby, $order, $s;

            wp_reset_vars(['orderby', 'order']);

            $all_plugins = apply_filters('all_plugins', get_plugins());

            $plugins = [
                'all' => $all_plugins,
                'search' => [],
                'active' => [],
                'inactive' => [],
                'recently_activated' => [],
                'upgrade' => [],
                'mustuse' => [],
                'dropins' => [],
                'paused' => [],
            ];
            if($this->show_autoupdates)
            {
                $auto_updates = (array) get_site_option('auto_update_plugins', []);

                $plugins['auto-update-enabled'] = [];
                $plugins['auto-update-disabled'] = [];
            }

            $screen = $this->screen;

            if(! is_multisite() || ($screen->in_admin('network') && current_user_can('manage_network_plugins')))
            {
                if(apply_filters('show_advanced_plugins', true, 'mustuse'))
                {
                    $plugins['mustuse'] = get_mu_plugins();
                }

                if(apply_filters('show_advanced_plugins', true, 'dropins'))
                {
                    $plugins['dropins'] = get_dropins();
                }

                if(current_user_can('update_plugins'))
                {
                    $current = get_site_transient('update_plugins');
                    foreach((array) $plugins['all'] as $plugin_file => $plugin_data)
                    {
                        if(isset($current->response[$plugin_file]))
                        {
                            $plugins['all'][$plugin_file]['update'] = true;
                            $plugins['upgrade'][$plugin_file] = $plugins['all'][$plugin_file];
                        }
                    }
                }
            }

            if(! $screen->in_admin('network'))
            {
                $show = current_user_can('manage_network_plugins');

                $show_network_active = apply_filters('show_network_active_plugins', $show);
            }

            if($screen->in_admin('network'))
            {
                $recently_activated = get_site_option('recently_activated', []);
            }
            else
            {
                $recently_activated = get_option('recently_activated', []);
            }

            foreach($recently_activated as $key => $time)
            {
                if($time + WEEK_IN_SECONDS < time())
                {
                    unset($recently_activated[$key]);
                }
            }

            if($screen->in_admin('network'))
            {
                update_site_option('recently_activated', $recently_activated);
            }
            else
            {
                update_option('recently_activated', $recently_activated);
            }

            $plugin_info = get_site_transient('update_plugins');

            foreach((array) $plugins['all'] as $plugin_file => $plugin_data)
            {
                // Extra info if known. array_merge() ensures $plugin_data has precedence if keys collide.
                if(isset($plugin_info->response[$plugin_file]))
                {
                    $plugin_data = array_merge((array) $plugin_info->response[$plugin_file], ['update-supported' => true], $plugin_data);
                }
                elseif(isset($plugin_info->no_update[$plugin_file]))
                {
                    $plugin_data = array_merge((array) $plugin_info->no_update[$plugin_file], ['update-supported' => true], $plugin_data);
                }
                elseif(empty($plugin_data['update-supported']))
                {
                    $plugin_data['update-supported'] = false;
                }

                /*
                 * Create the payload that's used for the auto_update_plugin filter.
                 * This is the same data contained within $plugin_info->(response|no_update) however
                 * not all plugins will be contained in those keys, this avoids unexpected warnings.
                 */
                $filter_payload = [
                    'id' => $plugin_file,
                    'slug' => '',
                    'plugin' => $plugin_file,
                    'new_version' => '',
                    'url' => '',
                    'package' => '',
                    'icons' => [],
                    'banners' => [],
                    'banners_rtl' => [],
                    'tested' => '',
                    'requires_php' => '',
                    'compatibility' => new stdClass(),
                ];

                $filter_payload = (object) wp_parse_args($plugin_data, $filter_payload);

                $auto_update_forced = wp_is_auto_update_forced_for_item('plugin', null, $filter_payload);

                if(! is_null($auto_update_forced))
                {
                    $plugin_data['auto-update-forced'] = $auto_update_forced;
                }

                $plugins['all'][$plugin_file] = $plugin_data;
                // Make sure that $plugins['upgrade'] also receives the extra info since it is used on ?plugin_status=upgrade.
                if(isset($plugins['upgrade'][$plugin_file]))
                {
                    $plugins['upgrade'][$plugin_file] = $plugin_data;
                }

                // Filter into individual sections.
                if(is_multisite() && ! $screen->in_admin('network') && is_network_only_plugin($plugin_file) && ! is_plugin_active($plugin_file))
                {
                    if($show_network_active)
                    {
                        // On the non-network screen, show inactive network-only plugins if allowed.
                        $plugins['inactive'][$plugin_file] = $plugin_data;
                    }
                    else
                    {
                        // On the non-network screen, filter out network-only plugins as long as they're not individually active.
                        unset($plugins['all'][$plugin_file]);
                    }
                }
                elseif(! $screen->in_admin('network') && is_plugin_active_for_network($plugin_file))
                {
                    if($show_network_active)
                    {
                        // On the non-network screen, show network-active plugins if allowed.
                        $plugins['active'][$plugin_file] = $plugin_data;
                    }
                    else
                    {
                        // On the non-network screen, filter out network-active plugins.
                        unset($plugins['all'][$plugin_file]);
                    }
                }
                elseif((! $screen->in_admin('network') && is_plugin_active($plugin_file)) || ($screen->in_admin('network') && is_plugin_active_for_network($plugin_file)))
                {
                    /*
                     * On the non-network screen, populate the active list with plugins that are individually activated.
                     * On the network admin screen, populate the active list with plugins that are network-activated.
                     */
                    $plugins['active'][$plugin_file] = $plugin_data;

                    if(! $screen->in_admin('network') && is_plugin_paused($plugin_file))
                    {
                        $plugins['paused'][$plugin_file] = $plugin_data;
                    }
                }
                else
                {
                    if(isset($recently_activated[$plugin_file]))
                    {
                        // Populate the recently activated list with plugins that have been recently activated.
                        $plugins['recently_activated'][$plugin_file] = $plugin_data;
                    }
                    // Populate the inactive list with plugins that aren't activated.
                    $plugins['inactive'][$plugin_file] = $plugin_data;
                }

                if($this->show_autoupdates)
                {
                    $enabled = in_array($plugin_file, $auto_updates, true) && $plugin_data['update-supported'];
                    if(isset($plugin_data['auto-update-forced']))
                    {
                        $enabled = (bool) $plugin_data['auto-update-forced'];
                    }

                    if($enabled)
                    {
                        $plugins['auto-update-enabled'][$plugin_file] = $plugin_data;
                    }
                    else
                    {
                        $plugins['auto-update-disabled'][$plugin_file] = $plugin_data;
                    }
                }
            }

            if(strlen($s))
            {
                $status = 'search';
                $plugins['search'] = array_filter($plugins['all'], [$this, '_search_callback']);
            }

            $plugins = apply_filters('plugins_list', $plugins);

            $totals = [];
            foreach($plugins as $type => $list)
            {
                $totals[$type] = count($list);
            }

            if(empty($plugins[$status]) && ! in_array($status, ['all', 'search'], true))
            {
                $status = 'all';
            }

            $this->items = [];
            foreach($plugins[$status] as $plugin_file => $plugin_data)
            {
                // Translate, don't apply markup, sanitize HTML.
                $this->items[$plugin_file] = _get_plugin_data_markup_translate($plugin_file, $plugin_data, false, true);
            }

            $total_this_page = $totals[$status];

            $js_plugins = [];
            foreach($plugins as $key => $list)
            {
                $js_plugins[$key] = array_keys($list);
            }

            wp_localize_script('updates', '_wpUpdatesItemCounts', [
                'plugins' => $js_plugins,
                'totals' => wp_get_update_data(),
            ]);

            if(! $orderby)
            {
                $orderby = 'Name';
            }
            else
            {
                $orderby = ucfirst($orderby);
            }

            $order = strtoupper($order);

            uasort($this->items, [$this, '_order_callback']);

            $plugins_per_page = $this->get_items_per_page(str_replace('-', '_', $screen->id.'_per_page'), 999);

            $start = ($page - 1) * $plugins_per_page;

            if($total_this_page > $plugins_per_page)
            {
                $this->items = array_slice($this->items, $start, $plugins_per_page);
            }

            $this->set_pagination_args([
                                           'total_items' => $total_this_page,
                                           'per_page' => $plugins_per_page,
                                       ]);
        }

        public function _search_callback($plugin)
        {
            global $s;

            foreach($plugin as $value)
            {
                if(is_string($value) && false !== stripos(strip_tags($value), urldecode($s)))
                {
                    return true;
                }
            }

            return false;
        }

        public function _order_callback($plugin_a, $plugin_b)
        {
            global $orderby, $order;

            $a = $plugin_a[$orderby];
            $b = $plugin_b[$orderby];

            if($a === $b)
            {
                return 0;
            }

            if('DESC' === $order)
            {
                return strcasecmp($b, $a);
            }
            else
            {
                return strcasecmp($a, $b);
            }
        }

        public function no_items()
        {
            global $plugins;

            if(! empty($_REQUEST['s']))
            {
                $s = esc_html(urldecode(wp_unslash($_REQUEST['s'])));

                /* translators: %s: Plugin search term. */
                printf(__('No plugins found for: %s.'), '<strong>'.$s.'</strong>');

                // We assume that somebody who can install plugins in multisite is experienced enough to not need this helper link.
                if(! is_multisite() && current_user_can('install_plugins'))
                {
                    echo ' <a href="'.esc_url(admin_url('plugin-install.php?tab=search&s='.urlencode($s))).'">'.__('Search for plugins in the WordPress Plugin Directory.').'</a>';
                }
            }
            elseif(! empty($plugins['all']))
            {
                _e('No plugins found.');
            }
            else
            {
                _e('No plugins are currently available.');
            }
        }

        public function search_box($text, $input_id)
        {
            if(empty($_REQUEST['s']) && ! $this->has_items())
            {
                return;
            }

            $input_id = $input_id.'-search-input';

            if(! empty($_REQUEST['orderby']))
            {
                echo '<input type="hidden" name="orderby" value="'.esc_attr($_REQUEST['orderby']).'" />';
            }
            if(! empty($_REQUEST['order']))
            {
                echo '<input type="hidden" name="order" value="'.esc_attr($_REQUEST['order']).'" />';
            }
            ?>
            <p class="search-box">
                <label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php echo $text; ?>:</label>
                <input type="search"
                       id="<?php echo esc_attr($input_id); ?>"
                       class="wp-filter-search"
                       name="s"
                       value="<?php _admin_search_query(); ?>"
                       placeholder="<?php esc_attr_e('Search installed plugins...'); ?>"/>
                <?php submit_button($text, 'hide-if-js', '', false, ['id' => 'search-submit']); ?>
            </p>
            <?php
        }

        public function get_columns()
        {
            global $status;

            $columns = [
                'cb' => ! in_array($status, ['mustuse', 'dropins'], true) ? '<input type="checkbox" />' : '',
                'name' => __('Plugin'),
                'description' => __('Description'),
            ];

            if($this->show_autoupdates && ! in_array($status, ['mustuse', 'dropins'], true))
            {
                $columns['auto-updates'] = __('Automatic Updates');
            }

            return $columns;
        }

        public function bulk_actions($which = '')
        {
            global $status;

            if(in_array($status, ['mustuse', 'dropins'], true))
            {
                return;
            }

            parent::bulk_actions($which);
        }

        public function current_action()
        {
            if(isset($_POST['clear-recent-list']))
            {
                return 'clear-recent-list';
            }

            return parent::current_action();
        }

        public function display_rows()
        {
            global $status;

            if(
                is_multisite() && ! $this->screen->in_admin('network') && in_array($status, [
                    'mustuse',
                    'dropins'
                ],                                                                 true)
            )
            {
                return;
            }

            foreach($this->items as $plugin_file => $plugin_data)
            {
                $this->single_row([$plugin_file, $plugin_data]);
            }
        }

        public function single_row($item)
        {
            global $status, $page, $s, $totals;
            static $plugin_id_attrs = [];

            [$plugin_file, $plugin_data] = $item;

            $plugin_slug = isset($plugin_data['slug']) ? $plugin_data['slug'] : sanitize_title($plugin_data['Name']);
            $plugin_id_attr = $plugin_slug;

            // Ensure the ID attribute is unique.
            $suffix = 2;
            while(in_array($plugin_id_attr, $plugin_id_attrs, true))
            {
                $plugin_id_attr = "$plugin_slug-$suffix";
                ++$suffix;
            }

            $plugin_id_attrs[] = $plugin_id_attr;

            $context = $status;
            $screen = $this->screen;

            // Pre-order.
            $actions = [
                'deactivate' => '',
                'activate' => '',
                'details' => '',
                'delete' => '',
            ];

            // Do not restrict by default.
            $restrict_network_active = false;
            $restrict_network_only = false;

            $requires_php = isset($plugin_data['RequiresPHP']) ? $plugin_data['RequiresPHP'] : null;
            $requires_wp = isset($plugin_data['RequiresWP']) ? $plugin_data['RequiresWP'] : null;

            $compatible_php = is_php_version_compatible($requires_php);
            $compatible_wp = is_wp_version_compatible($requires_wp);

            if('mustuse' === $context)
            {
                $is_active = true;
            }
            elseif('dropins' === $context)
            {
                $dropins = _get_dropins();
                $plugin_name = $plugin_file;

                if($plugin_file !== $plugin_data['Name'])
                {
                    $plugin_name .= '<br />'.$plugin_data['Name'];
                }

                if(true === ($dropins[$plugin_file][1]))
                { // Doesn't require a constant.
                    $is_active = true;
                    $description = '<p><strong>'.$dropins[$plugin_file][0].'</strong></p>';
                }
                elseif(defined($dropins[$plugin_file][1]) && constant($dropins[$plugin_file][1]))
                { // Constant is true.
                    $is_active = true;
                    $description = '<p><strong>'.$dropins[$plugin_file][0].'</strong></p>';
                }
                else
                {
                    $is_active = false;
                    $description = '<p><strong>'.$dropins[$plugin_file][0].' <span class="error-message">'.__('Inactive:').'</span></strong> '.sprintf(/* translators: 1: Drop-in constant name, 2: wp-config.php */ __('Requires %1$s in %2$s file.'), "<code>define('".$dropins[$plugin_file][1]."', true);</code>", '<code>wp-config.php</code>').'</p>';
                }

                if($plugin_data['Description'])
                {
                    $description .= '<p>'.$plugin_data['Description'].'</p>';
                }
            }
            else
            {
                if($screen->in_admin('network'))
                {
                    $is_active = is_plugin_active_for_network($plugin_file);
                }
                else
                {
                    $is_active = is_plugin_active($plugin_file);
                    $restrict_network_active = (is_multisite() && is_plugin_active_for_network($plugin_file));
                    $restrict_network_only = (is_multisite() && is_network_only_plugin($plugin_file) && ! $is_active);
                }

                if($screen->in_admin('network'))
                {
                    if($is_active)
                    {
                        if(current_user_can('manage_network_plugins'))
                        {
                            $actions['deactivate'] = sprintf('<a href="%s" id="deactivate-%s" aria-label="%s">%s</a>', wp_nonce_url('plugins.php?action=deactivate&amp;plugin='.urlencode($plugin_file).'&amp;plugin_status='.$context.'&amp;paged='.$page.'&amp;s='.$s, 'deactivate-plugin_'.$plugin_file), esc_attr($plugin_id_attr), /* translators: %s: Plugin name. */ esc_attr(sprintf(_x('Network Deactivate %s', 'plugin'), $plugin_data['Name'])), __('Network Deactivate'));
                        }
                    }
                    else
                    {
                        if(current_user_can('manage_network_plugins'))
                        {
                            if($compatible_php && $compatible_wp)
                            {
                                $actions['activate'] = sprintf('<a href="%s" id="activate-%s" class="edit" aria-label="%s">%s</a>', wp_nonce_url('plugins.php?action=activate&amp;plugin='.urlencode($plugin_file).'&amp;plugin_status='.$context.'&amp;paged='.$page.'&amp;s='.$s, 'activate-plugin_'.$plugin_file), esc_attr($plugin_id_attr), /* translators: %s: Plugin name. */ esc_attr(sprintf(_x('Network Activate %s', 'plugin'), $plugin_data['Name'])), __('Network Activate'));
                            }
                            else
                            {
                                $actions['activate'] = sprintf('<span>%s</span>', _x('Cannot Activate', 'plugin'));
                            }
                        }

                        if(current_user_can('delete_plugins') && ! is_plugin_active($plugin_file))
                        {
                            $actions['delete'] = sprintf('<a href="%s" id="delete-%s" class="delete" aria-label="%s">%s</a>', wp_nonce_url('plugins.php?action=delete-selected&amp;checked[]='.urlencode($plugin_file).'&amp;plugin_status='.$context.'&amp;paged='.$page.'&amp;s='.$s, 'bulk-plugins'), esc_attr($plugin_id_attr), /* translators: %s: Plugin name. */ esc_attr(sprintf(_x('Delete %s', 'plugin'), $plugin_data['Name'])), __('Delete'));
                        }
                    }
                }
                else
                {
                    if($restrict_network_active)
                    {
                        $actions = [
                            'network_active' => __('Network Active'),
                        ];
                    }
                    elseif($restrict_network_only)
                    {
                        $actions = [
                            'network_only' => __('Network Only'),
                        ];
                    }
                    elseif($is_active)
                    {
                        if(current_user_can('deactivate_plugin', $plugin_file))
                        {
                            $actions['deactivate'] = sprintf('<a href="%s" id="deactivate-%s" aria-label="%s">%s</a>', wp_nonce_url('plugins.php?action=deactivate&amp;plugin='.urlencode($plugin_file).'&amp;plugin_status='.$context.'&amp;paged='.$page.'&amp;s='.$s, 'deactivate-plugin_'.$plugin_file), esc_attr($plugin_id_attr), /* translators: %s: Plugin name. */ esc_attr(sprintf(_x('Deactivate %s', 'plugin'), $plugin_data['Name'])), __('Deactivate'));
                        }

                        if(current_user_can('resume_plugin', $plugin_file) && is_plugin_paused($plugin_file))
                        {
                            $actions['resume'] = sprintf('<a href="%s" id="resume-%s" class="resume-link" aria-label="%s">%s</a>', wp_nonce_url('plugins.php?action=resume&amp;plugin='.urlencode($plugin_file).'&amp;plugin_status='.$context.'&amp;paged='.$page.'&amp;s='.$s, 'resume-plugin_'.$plugin_file), esc_attr($plugin_id_attr), /* translators: %s: Plugin name. */ esc_attr(sprintf(_x('Resume %s', 'plugin'), $plugin_data['Name'])), __('Resume'));
                        }
                    }
                    else
                    {
                        if(current_user_can('activate_plugin', $plugin_file))
                        {
                            if($compatible_php && $compatible_wp)
                            {
                                $actions['activate'] = sprintf('<a href="%s" id="activate-%s" class="edit" aria-label="%s">%s</a>', wp_nonce_url('plugins.php?action=activate&amp;plugin='.urlencode($plugin_file).'&amp;plugin_status='.$context.'&amp;paged='.$page.'&amp;s='.$s, 'activate-plugin_'.$plugin_file), esc_attr($plugin_id_attr), /* translators: %s: Plugin name. */ esc_attr(sprintf(_x('Activate %s', 'plugin'), $plugin_data['Name'])), __('Activate'));
                            }
                            else
                            {
                                $actions['activate'] = sprintf('<span>%s</span>', _x('Cannot Activate', 'plugin'));
                            }
                        }

                        if(! is_multisite() && current_user_can('delete_plugins'))
                        {
                            $actions['delete'] = sprintf('<a href="%s" id="delete-%s" class="delete" aria-label="%s">%s</a>', wp_nonce_url('plugins.php?action=delete-selected&amp;checked[]='.urlencode($plugin_file).'&amp;plugin_status='.$context.'&amp;paged='.$page.'&amp;s='.$s, 'bulk-plugins'), esc_attr($plugin_id_attr), /* translators: %s: Plugin name. */ esc_attr(sprintf(_x('Delete %s', 'plugin'), $plugin_data['Name'])), __('Delete'));
                        }
                    } // End if $is_active.
                } // End if $screen->in_admin( 'network' ).
            } // End if $context.

            $actions = array_filter($actions);

            if($screen->in_admin('network'))
            {
                $actions = apply_filters('network_admin_plugin_action_links', $actions, $plugin_file, $plugin_data, $context);

                $actions = apply_filters("network_admin_plugin_action_links_{$plugin_file}", $actions, $plugin_file, $plugin_data, $context);
            }
            else
            {
                $actions = apply_filters('plugin_action_links', $actions, $plugin_file, $plugin_data, $context);

                $actions = apply_filters("plugin_action_links_{$plugin_file}", $actions, $plugin_file, $plugin_data, $context);
            }

            $class = $is_active ? 'active' : 'inactive';
            $checkbox_id = 'checkbox_'.md5($plugin_file);

            if(
                $restrict_network_active || $restrict_network_only || in_array($status, [
                    'mustuse',
                    'dropins',
                ],                                                             true) || ! $compatible_php
            )
            {
                $checkbox = '';
            }
            else
            {
                $checkbox = sprintf('<label class="label-covers-full-cell" for="%1$s"><span class="screen-reader-text">%2$s</span></label>'.'<input type="checkbox" name="checked[]" value="%3$s" id="%1$s" />', $checkbox_id, /* translators: Hidden accessibility text. %s: Plugin name. */ sprintf(__('Select %s'), $plugin_data['Name']), esc_attr($plugin_file));
            }

            if('dropins' !== $context)
            {
                $description = '<p>'.($plugin_data['Description'] ? $plugin_data['Description'] : '&nbsp;').'</p>';
                $plugin_name = $plugin_data['Name'];
            }

            if(! empty($totals['upgrade']) && ! empty($plugin_data['update']) || ! $compatible_php || ! $compatible_wp)
            {
                $class .= ' update';
            }

            $paused = ! $screen->in_admin('network') && is_plugin_paused($plugin_file);

            if($paused)
            {
                $class .= ' paused';
            }

            if(is_uninstallable_plugin($plugin_file))
            {
                $class .= ' is-uninstallable';
            }

            printf('<tr class="%s" data-slug="%s" data-plugin="%s">', esc_attr($class), esc_attr($plugin_slug), esc_attr($plugin_file));

            [$columns, $hidden, $sortable, $primary] = $this->get_column_info();

            $auto_updates = (array) get_site_option('auto_update_plugins', []);

            foreach($columns as $column_name => $column_display_name)
            {
                $extra_classes = '';
                if(in_array($column_name, $hidden, true))
                {
                    $extra_classes = ' hidden';
                }

                switch($column_name)
                {
                    case 'cb':
                        echo "<th scope='row' class='check-column'>$checkbox</th>";
                        break;
                    case 'name':
                        echo "<td class='plugin-title column-primary'><strong>$plugin_name</strong>";
                        echo $this->row_actions($actions, true);
                        echo '</td>';
                        break;
                    case 'description':
                        $classes = 'column-description desc';

                        echo "<td class='$classes{$extra_classes}'>
						<div class='plugin-description'>$description</div>
						<div class='$class second plugin-version-author-uri'>";

                        $plugin_meta = [];
                        if(! empty($plugin_data['Version']))
                        {
                            /* translators: %s: Plugin version number. */
                            $plugin_meta[] = sprintf(__('Version %s'), $plugin_data['Version']);
                        }
                        if(! empty($plugin_data['Author']))
                        {
                            $author = $plugin_data['Author'];
                            if(! empty($plugin_data['AuthorURI']))
                            {
                                $author = '<a href="'.$plugin_data['AuthorURI'].'">'.$plugin_data['Author'].'</a>';
                            }
                            /* translators: %s: Plugin author name. */
                            $plugin_meta[] = sprintf(__('By %s'), $author);
                        }

                        // Details link using API info, if available.
                        if(isset($plugin_data['slug']) && current_user_can('install_plugins'))
                        {
                            $plugin_meta[] = sprintf('<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s" data-title="%s">%s</a>', esc_url(network_admin_url('plugin-install.php?tab=plugin-information&plugin='.$plugin_data['slug'].'&TB_iframe=true&width=600&height=550')), /* translators: %s: Plugin name. */ esc_attr(sprintf(__('More information about %s'), $plugin_name)), esc_attr($plugin_name), __('View details'));
                        }
                        elseif(! empty($plugin_data['PluginURI']))
                        {
                            /* translators: %s: Plugin name. */
                            $aria_label = sprintf(__('Visit plugin site for %s'), $plugin_name);

                            $plugin_meta[] = sprintf('<a href="%s" aria-label="%s">%s</a>', esc_url($plugin_data['PluginURI']), esc_attr($aria_label), __('Visit plugin site'));
                        }

                        $plugin_meta = apply_filters('plugin_row_meta', $plugin_meta, $plugin_file, $plugin_data, $status);

                        echo implode(' | ', $plugin_meta);

                        echo '</div>';

                        if($paused)
                        {
                            $notice_text = __('This plugin failed to load properly and is paused during recovery mode.');

                            printf('<p><span class="dashicons dashicons-warning"></span> <strong>%s</strong></p>', $notice_text);

                            $error = wp_get_plugin_error($plugin_file);

                            if(false !== $error)
                            {
                                printf('<div class="error-display"><p>%s</p></div>', wp_get_extension_error_description($error));
                            }
                        }

                        echo '</td>';
                        break;
                    case 'auto-updates':
                        if(! $this->show_autoupdates || in_array($status, ['mustuse', 'dropins'], true))
                        {
                            break;
                        }

                        echo "<td class='column-auto-updates{$extra_classes}'>";

                        $html = [];

                        if(isset($plugin_data['auto-update-forced']))
                        {
                            if($plugin_data['auto-update-forced'])
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
                        elseif(empty($plugin_data['update-supported']))
                        {
                            $text = '';
                            $action = 'unavailable';
                            $time_class = ' hidden';
                        }
                        elseif(in_array($plugin_file, $auto_updates, true))
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
                            'plugin' => $plugin_file,
                            'paged' => $page,
                            'plugin_status' => $status,
                        ];

                        $url = add_query_arg($query_args, 'plugins.php');

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

                        if(! empty($plugin_data['update']))
                        {
                            $html[] = sprintf('<div class="auto-update-time%s">%s</div>', $time_class, wp_get_auto_update_message());
                        }

                        $html = implode('', $html);

                        echo apply_filters('plugin_auto_update_setting_html', $html, $plugin_file, $plugin_data);

                        wp_admin_notice('', [
                            'type' => 'error',
                            'additional_classes' => ['notice-alt', 'inline', 'hidden'],
                        ]);

                        echo '</td>';

                        break;
                    default:
                        $classes = "$column_name column-$column_name $class";

                        echo "<td class='$classes{$extra_classes}'>";

                        do_action('manage_plugins_custom_column', $column_name, $plugin_file, $plugin_data);

                        echo '</td>';
                }
            }

            echo '</tr>';

            if(! $compatible_php || ! $compatible_wp)
            {
                printf('<tr class="plugin-update-tr">'.'<td colspan="%s" class="plugin-update colspanchange">'.'<div class="update-message notice inline notice-error notice-alt"><p>', esc_attr($this->get_column_count()));

                if(! $compatible_php && ! $compatible_wp)
                {
                    _e('This plugin does not work with your versions of WordPress and PHP.');
                    if(current_user_can('update_core') && current_user_can('update_php'))
                    {
                        printf(/* translators: 1: URL to WordPress Updates screen, 2: URL to Update PHP page. */ ' '.__('<a href="%1$s">Please update WordPress</a>, and then <a href="%2$s">learn more about updating PHP</a>.'), self_admin_url('update-core.php'), esc_url(wp_get_update_php_url()));
                        wp_update_php_annotation('</p><p><em>', '</em>');
                    }
                    elseif(current_user_can('update_core'))
                    {
                        printf(/* translators: %s: URL to WordPress Updates screen. */ ' '.__('<a href="%s">Please update WordPress</a>.'), self_admin_url('update-core.php'));
                    }
                    elseif(current_user_can('update_php'))
                    {
                        printf(/* translators: %s: URL to Update PHP page. */ ' '.__('<a href="%s">Learn more about updating PHP</a>.'), esc_url(wp_get_update_php_url()));
                        wp_update_php_annotation('</p><p><em>', '</em>');
                    }
                }
                elseif(! $compatible_wp)
                {
                    _e('This plugin does not work with your version of WordPress.');
                    if(current_user_can('update_core'))
                    {
                        printf(/* translators: %s: URL to WordPress Updates screen. */ ' '.__('<a href="%s">Please update WordPress</a>.'), self_admin_url('update-core.php'));
                    }
                }
                elseif(! $compatible_php)
                {
                    _e('This plugin does not work with your version of PHP.');
                    if(current_user_can('update_php'))
                    {
                        printf(/* translators: %s: URL to Update PHP page. */ ' '.__('<a href="%s">Learn more about updating PHP</a>.'), esc_url(wp_get_update_php_url()));
                        wp_update_php_annotation('</p><p><em>', '</em>');
                    }
                }

                echo '</p></div></td></tr>';
            }

            do_action('after_plugin_row', $plugin_file, $plugin_data, $status);

            do_action("after_plugin_row_{$plugin_file}", $plugin_file, $plugin_data, $status);
        }

        protected function get_table_classes()
        {
            return ['widefat', $this->_args['plural']];
        }

        protected function get_sortable_columns()
        {
            return [];
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
                        /* translators: %s: Number of plugins. */ $text = _nx('All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $count, 'plugins');
                        break;
                    case 'active':
                        /* translators: %s: Number of plugins. */ $text = _n('Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', $count);
                        break;
                    case 'recently_activated':
                        /* translators: %s: Number of plugins. */ $text = _n('Recently Active <span class="count">(%s)</span>', 'Recently Active <span class="count">(%s)</span>', $count);
                        break;
                    case 'inactive':
                        /* translators: %s: Number of plugins. */ $text = _n('Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', $count);
                        break;
                    case 'mustuse':
                        /* translators: %s: Number of plugins. */ $text = _n('Must-Use <span class="count">(%s)</span>', 'Must-Use <span class="count">(%s)</span>', $count);
                        break;
                    case 'dropins':
                        /* translators: %s: Number of plugins. */ $text = _n('Drop-in <span class="count">(%s)</span>', 'Drop-ins <span class="count">(%s)</span>', $count);
                        break;
                    case 'paused':
                        /* translators: %s: Number of plugins. */ $text = _n('Paused <span class="count">(%s)</span>', 'Paused <span class="count">(%s)</span>', $count);
                        break;
                    case 'upgrade':
                        /* translators: %s: Number of plugins. */ $text = _n('Update Available <span class="count">(%s)</span>', 'Update Available <span class="count">(%s)</span>', $count);
                        break;
                    case 'auto-update-enabled':
                        /* translators: %s: Number of plugins. */ $text = _n('Auto-updates Enabled <span class="count">(%s)</span>', 'Auto-updates Enabled <span class="count">(%s)</span>', $count);
                        break;
                    case 'auto-update-disabled':
                        /* translators: %s: Number of plugins. */ $text = _n('Auto-updates Disabled <span class="count">(%s)</span>', 'Auto-updates Disabled <span class="count">(%s)</span>', $count);
                        break;
                }

                if('search' !== $type)
                {
                    $status_links[$type] = [
                        'url' => add_query_arg('plugin_status', $type, 'plugins.php'),
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

            if('active' !== $status)
            {
                $actions['activate-selected'] = $this->screen->in_admin('network') ? __('Network Activate') : __('Activate');
            }

            if('inactive' !== $status && 'recent' !== $status)
            {
                $actions['deactivate-selected'] = $this->screen->in_admin('network') ? __('Network Deactivate') : __('Deactivate');
            }

            if(! is_multisite() || $this->screen->in_admin('network'))
            {
                if(current_user_can('update_plugins'))
                {
                    $actions['update-selected'] = __('Update');
                }

                if(current_user_can('delete_plugins') && ('active' !== $status))
                {
                    $actions['delete-selected'] = __('Delete');
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
            }

            return $actions;
        }

        protected function extra_tablenav($which)
        {
            global $status;

            if(! in_array($status, ['recently_activated', 'mustuse', 'dropins'], true))
            {
                return;
            }

            echo '<div class="alignleft actions">';

            if('recently_activated' === $status)
            {
                submit_button(__('Clear List'), '', 'clear-recent-list', false);
            }
            elseif('top' === $which && 'mustuse' === $status)
            {
                echo '<p>'.sprintf(/* translators: %s: mu-plugins directory name. */ __('Files in the %s directory are executed automatically.'), '<code>'.str_replace(ABSPATH, '/', WPMU_PLUGIN_DIR).'</code>').'</p>';
            }
            elseif('top' === $which && 'dropins' === $status)
            {
                echo '<p>'.sprintf(/* translators: %s: wp-content directory name. */ __('Drop-ins are single files, found in the %s directory, that replace or enhance WordPress features in ways that are not possible for traditional plugins.'), '<code>'.str_replace(ABSPATH, '', WP_CONTENT_DIR).'</code>').'</p>';
            }
            echo '</div>';
        }

        protected function get_primary_column_name()
        {
            return 'name';
        }
    }
