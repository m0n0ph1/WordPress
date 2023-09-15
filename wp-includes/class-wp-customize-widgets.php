<?php

    #[AllowDynamicProperties]
    final class WP_Customize_Widgets
    {
        public $manager;

        protected $core_widget_id_bases = [
            'archives',
            'calendar',
            'categories',
            'custom_html',
            'links',
            'media_audio',
            'media_image',
            'media_video',
            'meta',
            'nav_menu',
            'pages',
            'recent-comments',
            'recent-posts',
            'rss',
            'search',
            'tag_cloud',
            'text',
        ];

        protected $rendered_sidebars = [];

        protected $rendered_widgets = [];

        protected $old_sidebars_widgets = [];

        protected $selective_refreshable_widgets;

        protected $setting_id_patterns = [
            'widget_instance' => '/^widget_(?P<id_base>.+?)(?:\[(?P<widget_number>\d+)\])?$/',
            'sidebar_widgets' => '/^sidebars_widgets\[(?P<sidebar_id>.+?)\]$/',
        ];

        protected $before_widget_tags_seen = [];

        protected $sidebar_instance_count = [];

        protected $context_sidebar_instance_number;

        protected $current_dynamic_sidebar_id_stack = [];

        protected $rendering_widget_id;

        protected $rendering_sidebar_id;

        protected $_captured_options = [];

        protected $_is_capturing_option_updates = false;

        public function __construct($manager)
        {
            $this->manager = $manager;

            // See https://github.com/xwp/wp-customize-snapshots/blob/962586659688a5b1fd9ae93618b7ce2d4e7a421c/php/class-customize-snapshot-manager.php#L420-L449
            add_filter('customize_dynamic_setting_args', [$this, 'filter_customize_dynamic_setting_args'], 10, 2);
            add_action('widgets_init', [$this, 'register_settings'], 95);
            add_action('customize_register', [$this, 'schedule_customize_register'], 1);

            // Skip remaining hooks when the user can't manage widgets anyway.
            if(! current_user_can('edit_theme_options'))
            {
                return;
            }

            add_action('wp_loaded', [$this, 'override_sidebars_widgets_for_theme_switch']);
            add_action('customize_controls_init', [$this, 'customize_controls_init']);
            add_action('customize_controls_enqueue_scripts', [$this, 'enqueue_scripts']);
            add_action('customize_controls_print_styles', [$this, 'print_styles']);
            add_action('customize_controls_print_scripts', [$this, 'print_scripts']);
            add_action('customize_controls_print_footer_scripts', [$this, 'print_footer_scripts']);
            add_action('customize_controls_print_footer_scripts', [$this, 'output_widget_control_templates']);
            add_action('customize_preview_init', [$this, 'customize_preview_init']);
            add_filter('customize_refresh_nonces', [$this, 'refresh_nonces']);
            add_filter('should_load_block_editor_scripts_and_styles', [
                $this,
                'should_load_block_editor_scripts_and_styles'
            ]);

            add_action('dynamic_sidebar', [$this, 'tally_rendered_widgets']);
            add_filter('is_active_sidebar', [$this, 'tally_sidebars_via_is_active_sidebar_calls'], 10, 2);
            add_filter('dynamic_sidebar_has_widgets', [$this, 'tally_sidebars_via_dynamic_sidebar_calls'], 10, 2);

            // Selective Refresh.
            add_filter('customize_dynamic_partial_args', [$this, 'customize_dynamic_partial_args'], 10, 2);
            add_action('customize_preview_init', [$this, 'selective_refresh_init']);
        }

        public function register_settings()
        {
            $widget_setting_ids = [];
            $incoming_setting_ids = array_keys($this->manager->unsanitized_post_values());
            foreach($incoming_setting_ids as $setting_id)
            {
                if(! is_null($this->get_setting_type($setting_id)))
                {
                    $widget_setting_ids[] = $setting_id;
                }
            }
            if($this->manager->doing_ajax('update-widget') && isset($_REQUEST['widget-id']))
            {
                $widget_setting_ids[] = $this->get_setting_id(wp_unslash($_REQUEST['widget-id']));
            }

            $settings = $this->manager->add_dynamic_settings(array_unique($widget_setting_ids));

            if($this->manager->settings_previewed())
            {
                foreach($settings as $setting)
                {
                    $setting->preview();
                }
            }
        }

        protected function get_setting_type($setting_id)
        {
            static $cache = [];
            if(isset($cache[$setting_id]))
            {
                return $cache[$setting_id];
            }
            foreach($this->setting_id_patterns as $type => $pattern)
            {
                if(preg_match($pattern, $setting_id))
                {
                    $cache[$setting_id] = $type;

                    return $type;
                }
            }
        }

        public function get_setting_id($widget_id)
        {
            $parsed_widget_id = $this->parse_widget_id($widget_id);
            $setting_id = sprintf('widget_%s', $parsed_widget_id['id_base']);

            if(! is_null($parsed_widget_id['number']))
            {
                $setting_id .= sprintf('[%d]', $parsed_widget_id['number']);
            }

            return $setting_id;
        }

        public function parse_widget_id($widget_id)
        {
            $parsed = [
                'number' => null,
                'id_base' => null,
            ];

            if(preg_match('/^(.+)-(\d+)$/', $widget_id, $matches))
            {
                $parsed['id_base'] = $matches[1];
                $parsed['number'] = (int) $matches[2];
            }
            else
            {
                // Likely an old single widget.
                $parsed['id_base'] = $widget_id;
            }

            return $parsed;
        }

        public function filter_customize_dynamic_setting_args($args, $setting_id)
        {
            if($this->get_setting_type($setting_id))
            {
                $args = $this->get_setting_args($setting_id);
            }

            return $args;
        }

        public function get_setting_args($id, $overrides = [])
        {
            $args = [
                'type' => 'option',
                'capability' => 'edit_theme_options',
                'default' => [],
            ];

            if(preg_match($this->setting_id_patterns['sidebar_widgets'], $id, $matches))
            {
                $args['sanitize_callback'] = [$this, 'sanitize_sidebar_widgets'];
                $args['sanitize_js_callback'] = [$this, 'sanitize_sidebar_widgets_js_instance'];
                $args['transport'] = current_theme_supports('customize-selective-refresh-widgets') ? 'postMessage' : 'refresh';
            }
            elseif(preg_match($this->setting_id_patterns['widget_instance'], $id, $matches))
            {
                $id_base = $matches['id_base'];
                $args['sanitize_callback'] = function($value) use ($id_base)
                {
                    return $this->sanitize_widget_instance($value, $id_base);
                };
                $args['sanitize_js_callback'] = function($value) use ($id_base)
                {
                    return $this->sanitize_widget_js_instance($value, $id_base);
                };
                $args['transport'] = $this->is_widget_selective_refreshable($matches['id_base']) ? 'postMessage' : 'refresh';
            }

            $args = array_merge($args, $overrides);

            return apply_filters('widget_customizer_setting_args', $args, $id);
        }

        public function sanitize_widget_instance($value, $id_base = null)
        {
            global $wp_widget_factory;

            if([] === $value)
            {
                return $value;
            }

            if(isset($value['raw_instance']) && $id_base && wp_use_widgets_block_editor())
            {
                $widget_object = $wp_widget_factory->get_widget_object($id_base);
                if(! empty($widget_object->widget_options['show_instance_in_rest']))
                {
                    if('block' === $id_base && ! current_user_can('unfiltered_html'))
                    {
                        /*
					 * The content of the 'block' widget is not filtered on the fly while editing.
					 * Filter the content here to prevent vulnerabilities.
					 */
                        $value['raw_instance']['content'] = wp_kses_post($value['raw_instance']['content']);
                    }

                    return $value['raw_instance'];
                }
            }

            if(empty($value['is_widget_customizer_js_value']) || empty($value['instance_hash_key']) || empty($value['encoded_serialized_instance']))
            {
                return;
            }

            $decoded = base64_decode($value['encoded_serialized_instance'], true);
            if(false === $decoded || ! hash_equals($this->get_instance_hash_key($decoded), $value['instance_hash_key']))
            {
                return;
            }

            $instance = unserialize($decoded);
            if(false === $instance)
            {
                return;
            }

            return $instance;
        }

        protected function get_instance_hash_key($serialized_instance)
        {
            return wp_hash($serialized_instance);
        }

        public function sanitize_widget_js_instance($value, $id_base = null)
        {
            global $wp_widget_factory;

            if(empty($value['is_widget_customizer_js_value']))
            {
                $serialized = serialize($value);

                $js_value = [
                    'encoded_serialized_instance' => base64_encode($serialized),
                    'title' => empty($value['title']) ? '' : $value['title'],
                    'is_widget_customizer_js_value' => true,
                    'instance_hash_key' => $this->get_instance_hash_key($serialized),
                ];

                if($id_base && wp_use_widgets_block_editor())
                {
                    $widget_object = $wp_widget_factory->get_widget_object($id_base);
                    if(! empty($widget_object->widget_options['show_instance_in_rest']))
                    {
                        $js_value['raw_instance'] = (object) $value;
                    }
                }

                return $js_value;
            }

            return $value;
        }

        public function is_widget_selective_refreshable($id_base)
        {
            $selective_refreshable_widgets = $this->get_selective_refreshable_widgets();

            return ! empty($selective_refreshable_widgets[$id_base]);
        }

        public function get_selective_refreshable_widgets()
        {
            global $wp_widget_factory;
            if(! current_theme_supports('customize-selective-refresh-widgets'))
            {
                return [];
            }
            if(! isset($this->selective_refreshable_widgets))
            {
                $this->selective_refreshable_widgets = [];
                foreach($wp_widget_factory->widgets as $wp_widget)
                {
                    $this->selective_refreshable_widgets[$wp_widget->id_base] = ! empty($wp_widget->widget_options['customize_selective_refresh']);
                }
            }

            return $this->selective_refreshable_widgets;
        }

        public function override_sidebars_widgets_for_theme_switch()
        {
            global $sidebars_widgets;

            if($this->manager->doing_ajax() || $this->manager->is_theme_active())
            {
                return;
            }

            $this->old_sidebars_widgets = wp_get_sidebars_widgets();
            add_filter('customize_value_old_sidebars_widgets_data', [
                $this,
                'filter_customize_value_old_sidebars_widgets_data'
            ]);
            $this->manager->set_post_value('old_sidebars_widgets_data', $this->old_sidebars_widgets); // Override any value cached in changeset.

            // retrieve_widgets() looks at the global $sidebars_widgets.
            $sidebars_widgets = $this->old_sidebars_widgets;
            $sidebars_widgets = retrieve_widgets('customize');
            add_filter('option_sidebars_widgets', [$this, 'filter_option_sidebars_widgets_for_theme_switch'], 1);
            // Reset global cache var used by wp_get_sidebars_widgets().
            unset($GLOBALS['_wp_sidebars_widgets']);
        }

        public function filter_customize_value_old_sidebars_widgets_data($old_sidebars_widgets)
        {
            return $this->old_sidebars_widgets;
        }

        public function filter_option_sidebars_widgets_for_theme_switch($sidebars_widgets)
        {
            $sidebars_widgets = $GLOBALS['sidebars_widgets'];
            $sidebars_widgets['array_version'] = 3;

            return $sidebars_widgets;
        }

        public function customize_controls_init()
        {
            do_action('load-widgets.php'); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

            do_action('widgets.php'); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

            do_action('sidebar_admin_setup');
        }

        public function schedule_customize_register()
        {
            if(is_admin())
            {
                $this->customize_register();
            }
            else
            {
                add_action('wp', [$this, 'customize_register']);
            }
        }

        public function customize_register()
        {
            global $wp_registered_widgets, $wp_registered_widget_controls, $wp_registered_sidebars;

            $use_widgets_block_editor = wp_use_widgets_block_editor();

            add_filter('sidebars_widgets', [$this, 'preview_sidebars_widgets'], 1);

            $sidebars_widgets = array_merge(['wp_inactive_widgets' => []], array_fill_keys(array_keys($wp_registered_sidebars), []), wp_get_sidebars_widgets());

            $new_setting_ids = [];

            /*
		 * Register a setting for all widgets, including those which are active,
		 * inactive, and orphaned since a widget may get suppressed from a sidebar
		 * via a plugin (like Widget Visibility).
		 */
            foreach(array_keys($wp_registered_widgets) as $widget_id)
            {
                $setting_id = $this->get_setting_id($widget_id);
                $setting_args = $this->get_setting_args($setting_id);
                if(! $this->manager->get_setting($setting_id))
                {
                    $this->manager->add_setting($setting_id, $setting_args);
                }
                $new_setting_ids[] = $setting_id;
            }

            /*
		 * Add a setting which will be supplied for the theme's sidebars_widgets
		 * theme_mod when the theme is switched.
		 */
            if(! $this->manager->is_theme_active())
            {
                $setting_id = 'old_sidebars_widgets_data';
                $setting_args = $this->get_setting_args($setting_id, [
                    'type' => 'global_variable',
                    'dirty' => true,
                ]);
                $this->manager->add_setting($setting_id, $setting_args);
            }

            $this->manager->add_panel('widgets', [
                'type' => 'widgets',
                'title' => __('Widgets'),
                'description' => __('Widgets are independent sections of content that can be placed into widgetized areas provided by your theme (commonly called sidebars).'),
                'priority' => 110,
                'active_callback' => [$this, 'is_panel_active'],
                'auto_expand_sole_section' => true,
                'theme_supports' => 'widgets',
            ]);

            foreach($sidebars_widgets as $sidebar_id => $sidebar_widget_ids)
            {
                if(empty($sidebar_widget_ids))
                {
                    $sidebar_widget_ids = [];
                }

                $is_registered_sidebar = is_registered_sidebar($sidebar_id);
                $is_inactive_widgets = ('wp_inactive_widgets' === $sidebar_id);
                $is_active_sidebar = ($is_registered_sidebar && ! $is_inactive_widgets);

                // Add setting for managing the sidebar's widgets.
                if($is_registered_sidebar || $is_inactive_widgets)
                {
                    $setting_id = sprintf('sidebars_widgets[%s]', $sidebar_id);
                    $setting_args = $this->get_setting_args($setting_id);
                    if(! $this->manager->get_setting($setting_id))
                    {
                        if(! $this->manager->is_theme_active())
                        {
                            $setting_args['dirty'] = true;
                        }
                        $this->manager->add_setting($setting_id, $setting_args);
                    }
                    $new_setting_ids[] = $setting_id;

                    // Add section to contain controls.
                    $section_id = sprintf('sidebar-widgets-%s', $sidebar_id);
                    if($is_active_sidebar)
                    {
                        $section_args = [
                            'title' => $wp_registered_sidebars[$sidebar_id]['name'],
                            'priority' => array_search($sidebar_id, array_keys($wp_registered_sidebars), true),
                            'panel' => 'widgets',
                            'sidebar_id' => $sidebar_id,
                        ];

                        if($use_widgets_block_editor)
                        {
                            $section_args['description'] = '';
                        }
                        else
                        {
                            $section_args['description'] = $wp_registered_sidebars[$sidebar_id]['description'];
                        }

                        $section_args = apply_filters('customizer_widgets_section_args', $section_args, $section_id, $sidebar_id);

                        $section = new WP_Customize_Sidebar_Section($this->manager, $section_id, $section_args);
                        $this->manager->add_section($section);

                        if($use_widgets_block_editor)
                        {
                            $control = new WP_Sidebar_Block_Editor_Control($this->manager, $setting_id, [
                                'section' => $section_id,
                                'sidebar_id' => $sidebar_id,
                                'label' => $section_args['title'],
                                'description' => $section_args['description'],
                            ]);
                        }
                        else
                        {
                            $control = new WP_Widget_Area_Customize_Control($this->manager, $setting_id, [
                                'section' => $section_id,
                                'sidebar_id' => $sidebar_id,
                                'priority' => count($sidebar_widget_ids),
                                // place 'Add Widget' and 'Reorder' buttons at end.
                            ]);
                        }

                        $this->manager->add_control($control);

                        $new_setting_ids[] = $setting_id;
                    }
                }

                if(! $use_widgets_block_editor)
                {
                    // Add a control for each active widget (located in a sidebar).
                    foreach($sidebar_widget_ids as $i => $widget_id)
                    {
                        // Skip widgets that may have gone away due to a plugin being deactivated.
                        if(! $is_active_sidebar || ! isset($wp_registered_widgets[$widget_id]))
                        {
                            continue;
                        }

                        $registered_widget = $wp_registered_widgets[$widget_id];
                        $setting_id = $this->get_setting_id($widget_id);
                        $id_base = $wp_registered_widget_controls[$widget_id]['id_base'];

                        $control = new WP_Widget_Form_Customize_Control($this->manager, $setting_id, [
                            'label' => $registered_widget['name'],
                            'section' => $section_id,
                            'sidebar_id' => $sidebar_id,
                            'widget_id' => $widget_id,
                            'widget_id_base' => $id_base,
                            'priority' => $i,
                            'width' => $wp_registered_widget_controls[$widget_id]['width'],
                            'height' => $wp_registered_widget_controls[$widget_id]['height'],
                            'is_wide' => $this->is_wide_widget($widget_id),
                        ]);
                        $this->manager->add_control($control);
                    }
                }
            }

            if($this->manager->settings_previewed())
            {
                foreach($new_setting_ids as $new_setting_id)
                {
                    $this->manager->get_setting($new_setting_id)->preview();
                }
            }
        }

        public function is_wide_widget($widget_id)
        {
            global $wp_registered_widget_controls;

            $parsed_widget_id = $this->parse_widget_id($widget_id);
            $width = $wp_registered_widget_controls[$widget_id]['width'];
            $is_core = in_array($parsed_widget_id['id_base'], $this->core_widget_id_bases, true);
            $is_wide = ($width > 250 && ! $is_core);

            return apply_filters('is_wide_widget_in_customizer', $is_wide, $widget_id);
        }

        public function is_panel_active()
        {
            global $wp_registered_sidebars;

            return ! empty($wp_registered_sidebars);
        }

        public function parse_widget_setting_id($setting_id)
        {
            if(! preg_match('/^(widget_(.+?))(?:\[(\d+)\])?$/', $setting_id, $matches))
            {
                return new WP_Error('widget_setting_invalid_id');
            }

            $id_base = $matches[2];
            $number = isset($matches[3]) ? (int) $matches[3] : null;

            return compact('id_base', 'number');
        }

        public function print_styles()
        {
            do_action('admin_print_styles-widgets.php'); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

            do_action('admin_print_styles');
        }

        public function print_scripts()
        {
            do_action('admin_print_scripts-widgets.php'); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

            do_action('admin_print_scripts');
        }

        public function enqueue_scripts()
        {
            global $wp_scripts, $wp_registered_sidebars, $wp_registered_widgets;

            wp_enqueue_style('customize-widgets');
            wp_enqueue_script('customize-widgets');

            do_action('admin_enqueue_scripts', 'widgets.php');

            /*
		 * Export available widgets with control_tpl removed from model
		 * since plugins need templates to be in the DOM.
		 */
            $available_widgets = [];

            foreach($this->get_available_widgets() as $available_widget)
            {
                unset($available_widget['control_tpl']);
                $available_widgets[] = $available_widget;
            }

            $widget_reorder_nav_tpl = sprintf('<div class="widget-reorder-nav"><span class="move-widget" tabindex="0">%1$s</span><span class="move-widget-down" tabindex="0">%2$s</span><span class="move-widget-up" tabindex="0">%3$s</span></div>', __('Move to another area&hellip;'), __('Move down'), __('Move up'));

            $move_widget_area_tpl = str_replace(['{description}', '{btn}'], [
                __('Select an area to move this widget into:'),
                _x('Move', 'Move widget'),
            ],                                  '<div class="move-widget-area">
				<p class="description">{description}</p>
				<ul class="widget-area-select">
					<% _.each( sidebars, function ( sidebar ){ %>
						<li class="" data-id="<%- sidebar.id %>" title="<%- sidebar.description %>" tabindex="0"><%- sidebar.name %></li>
					<% }); %>
				</ul>
				<div class="move-widget-actions">
					<button class="move-widget-btn button" type="button">{btn}</button>
				</div>
			</div>');

            /*
		 * Gather all strings in PHP that may be needed by JS on the client.
		 * Once JS i18n is implemented (in #20491), this can be removed.
		 */
            $some_non_rendered_areas_messages = [];
            $some_non_rendered_areas_messages[1] = html_entity_decode(__('Your theme has 1 other widget area, but this particular page does not display it.'), ENT_QUOTES, get_bloginfo('charset'));
            $registered_sidebar_count = count($wp_registered_sidebars);
            for($non_rendered_count = 2; $non_rendered_count < $registered_sidebar_count; $non_rendered_count++)
            {
                $some_non_rendered_areas_messages[$non_rendered_count] = html_entity_decode(sprintf(/* translators: %s: The number of other widget areas registered but not rendered. */ _n('Your theme has %s other widget area, but this particular page does not display it.', 'Your theme has %s other widget areas, but this particular page does not display them.', $non_rendered_count), number_format_i18n($non_rendered_count)), ENT_QUOTES, get_bloginfo('charset'));
            }

            if(1 === $registered_sidebar_count)
            {
                $no_areas_shown_message = html_entity_decode(sprintf(__('Your theme has 1 widget area, but this particular page does not display it.')), ENT_QUOTES, get_bloginfo('charset'));
            }
            else
            {
                $no_areas_shown_message = html_entity_decode(sprintf(/* translators: %s: The total number of widget areas registered. */ _n('Your theme has %s widget area, but this particular page does not display it.', 'Your theme has %s widget areas, but this particular page does not display them.', $registered_sidebar_count), number_format_i18n($registered_sidebar_count)), ENT_QUOTES, get_bloginfo('charset'));
            }

            $settings = [
                'registeredSidebars' => array_values($wp_registered_sidebars),
                'registeredWidgets' => $wp_registered_widgets,
                'availableWidgets' => $available_widgets, // @todo Merge this with registered_widgets.
                'l10n' => [
                    'saveBtnLabel' => __('Apply'),
                    'saveBtnTooltip' => __('Save and preview changes before publishing them.'),
                    'removeBtnLabel' => __('Remove'),
                    'removeBtnTooltip' => __('Keep widget settings and move it to the inactive widgets'),
                    'error' => __('An error has occurred. Please reload the page and try again.'),
                    'widgetMovedUp' => __('Widget moved up'),
                    'widgetMovedDown' => __('Widget moved down'),
                    'navigatePreview' => __('You can navigate to other pages on your site while using the Customizer to view and edit the widgets displayed on those pages.'),
                    'someAreasShown' => $some_non_rendered_areas_messages,
                    'noAreasShown' => $no_areas_shown_message,
                    'reorderModeOn' => __('Reorder mode enabled'),
                    'reorderModeOff' => __('Reorder mode closed'),
                    'reorderLabelOn' => esc_attr__('Reorder widgets'),
                    /* translators: %d: The number of widgets found. */
                    'widgetsFound' => __('Number of widgets found: %d'),
                    'noWidgetsFound' => __('No widgets found.'),
                ],
                'tpl' => [
                    'widgetReorderNav' => $widget_reorder_nav_tpl,
                    'moveWidgetArea' => $move_widget_area_tpl,
                ],
                'selectiveRefreshableWidgets' => $this->get_selective_refreshable_widgets(),
            ];

            foreach($settings['registeredWidgets'] as &$registered_widget)
            {
                unset($registered_widget['callback']); // May not be JSON-serializeable.
            }

            $wp_scripts->add_data('customize-widgets', 'data', sprintf('var _wpCustomizeWidgetsSettings = %s;', wp_json_encode($settings)));

            /*
		 * TODO: Update 'wp-customize-widgets' to not rely so much on things in
		 * 'customize-widgets'. This will let us skip most of the above and not
		 * enqueue 'customize-widgets' which saves bytes.
		 */

            if(wp_use_widgets_block_editor())
            {
                $block_editor_context = new WP_Block_Editor_Context([
                                                                        'name' => 'core/customize-widgets',
                                                                    ]);

                $editor_settings = get_block_editor_settings(get_legacy_widget_block_editor_settings(), $block_editor_context);

                wp_add_inline_script(
                    'wp-customize-widgets', sprintf(
                                              'wp.domReady( function() {
					   wp.customizeWidgets.initialize( "widgets-customizer", %s );
					} );', wp_json_encode($editor_settings)
                                          )
                );

                // Preload server-registered block schemas.
                wp_add_inline_script('wp-blocks', 'wp.blocks.unstable__bootstrapServerSideBlockDefinitions('.wp_json_encode(get_block_editor_server_block_settings()).');');

                wp_add_inline_script('wp-blocks', sprintf('wp.blocks.setCategories( %s );', wp_json_encode(get_block_categories($block_editor_context))), 'after');

                wp_enqueue_script('wp-customize-widgets');
                wp_enqueue_style('wp-customize-widgets');

                do_action('enqueue_block_editor_assets');
            }
        }

        public function get_available_widgets()
        {
            static $available_widgets = [];
            if(! empty($available_widgets))
            {
                return $available_widgets;
            }

            global $wp_registered_widgets, $wp_registered_widget_controls;
            require_once ABSPATH.'wp-admin/includes/widgets.php'; // For next_widget_id_number().

            $sort = $wp_registered_widgets;
            usort($sort, [$this, '_sort_name_callback']);
            $done = [];

            foreach($sort as $widget)
            {
                if(in_array($widget['callback'], $done, true))
                { // We already showed this multi-widget.
                    continue;
                }

                $sidebar = is_active_widget($widget['callback'], $widget['id'], false, false);
                $done[] = $widget['callback'];

                if(! isset($widget['params'][0]))
                {
                    $widget['params'][0] = [];
                }

                $available_widget = $widget;
                unset($available_widget['callback']); // Not serializable to JSON.

                $args = [
                    'widget_id' => $widget['id'],
                    'widget_name' => $widget['name'],
                    '_display' => 'template',
                ];

                $is_disabled = false;
                $is_multi_widget = (isset($wp_registered_widget_controls[$widget['id']]['id_base']) && isset($widget['params'][0]['number']));
                if($is_multi_widget)
                {
                    $id_base = $wp_registered_widget_controls[$widget['id']]['id_base'];
                    $args['_temp_id'] = "$id_base-__i__";
                    $args['_multi_num'] = next_widget_id_number($id_base);
                    $args['_add'] = 'multi';
                }
                else
                {
                    $args['_add'] = 'single';

                    if($sidebar && 'wp_inactive_widgets' !== $sidebar)
                    {
                        $is_disabled = true;
                    }
                    $id_base = $widget['id'];
                }

                $list_widget_controls_args = wp_list_widget_controls_dynamic_sidebar([
                                                                                         0 => $args,
                                                                                         1 => $widget['params'][0],
                                                                                     ]);
                $control_tpl = $this->get_widget_control($list_widget_controls_args);

                // The properties here are mapped to the Backbone Widget model.
                $available_widget = array_merge($available_widget, [
                    'temp_id' => isset($args['_temp_id']) ? $args['_temp_id'] : null,
                    'is_multi' => $is_multi_widget,
                    'control_tpl' => $control_tpl,
                    'multi_number' => ('multi' === $args['_add']) ? $args['_multi_num'] : false,
                    'is_disabled' => $is_disabled,
                    'id_base' => $id_base,
                    'transport' => $this->is_widget_selective_refreshable($id_base) ? 'postMessage' : 'refresh',
                    'width' => $wp_registered_widget_controls[$widget['id']]['width'],
                    'height' => $wp_registered_widget_controls[$widget['id']]['height'],
                    'is_wide' => $this->is_wide_widget($widget['id']),
                ]);

                $available_widgets[] = $available_widget;
            }

            return $available_widgets;
        }

        public function get_widget_control($args)
        {
            $args[0]['before_form'] = '<div class="form">';
            $args[0]['after_form'] = '</div><!-- .form -->';
            $args[0]['before_widget_content'] = '<div class="widget-content">';
            $args[0]['after_widget_content'] = '</div><!-- .widget-content -->';
            ob_start();
            wp_widget_control(...$args);
            $control_tpl = ob_get_clean();

            return $control_tpl;
        }

        public function output_widget_control_templates()
        {
            ?>
            <div id="widgets-left"><!-- compatibility with JS which looks for widget templates here -->
                <div id="available-widgets">
                    <div class="customize-section-title">
                        <button class="customize-section-back" tabindex="-1">
					<span class="screen-reader-text">
						<?php
                            /* translators: Hidden accessibility text. */
                            _e('Back');
                        ?>
					</span>
                        </button>
                        <h3>
					<span class="customize-action">
					<?php
                        /* translators: &#9656; is the unicode right-pointing triangle. %s: Section title in the Customizer. */
                        printf(__('Customizing &#9656; %s'), esc_html($this->manager->get_panel('widgets')->title));
                    ?>
					</span>
                            <?php _e('Add a Widget'); ?>
                        </h3>
                    </div>
                    <div id="available-widgets-filter">
                        <label class="screen-reader-text" for="widgets-search">
                            <?php
                                /* translators: Hidden accessibility text. */
                                _e('Search Widgets');
                            ?>
                        </label>
                        <input type="text"
                               id="widgets-search"
                               placeholder="<?php esc_attr_e('Search widgets&hellip;'); ?>"
                               aria-describedby="widgets-search-desc"/>
                        <div class="search-icon" aria-hidden="true"></div>
                        <button type="button" class="clear-results"><span class="screen-reader-text">
					<?php
                        /* translators: Hidden accessibility text. */
                        _e('Clear Results');
                    ?>
				</span></button>
                        <p class="screen-reader-text" id="widgets-search-desc">
                            <?php
                                /* translators: Hidden accessibility text. */
                                _e('The search results will be updated as you type.');
                            ?>
                        </p>
                    </div>
                    <div id="available-widgets-list">
                        <?php foreach($this->get_available_widgets() as $available_widget) : ?>
                            <div id="widget-tpl-<?php echo esc_attr($available_widget['id']); ?>"
                                 data-widget-id="<?php echo esc_attr($available_widget['id']); ?>"
                                 class="widget-tpl <?php echo esc_attr($available_widget['id']); ?>"
                                 tabindex="0">
                                <?php echo $available_widget['control_tpl']; ?>
                            </div>
                        <?php endforeach; ?>
                        <p class="no-widgets-found-message"><?php _e('No widgets found.'); ?></p>
                    </div><!-- #available-widgets-list -->
                </div><!-- #available-widgets -->
            </div><!-- #widgets-left -->
            <?php
        }

        public function print_footer_scripts()
        {
            do_action('admin_print_footer_scripts-widgets.php'); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

            do_action('admin_print_footer_scripts');

            do_action('admin_footer-widgets.php'); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
        }

        public function sanitize_sidebar_widgets($widget_ids)
        {
            $widget_ids = array_map('strval', (array) $widget_ids);
            $sanitized_widget_ids = [];
            foreach($widget_ids as $widget_id)
            {
                $sanitized_widget_ids[] = preg_replace('/[^a-z0-9_\-]/', '', $widget_id);
            }

            return $sanitized_widget_ids;
        }

        public function get_widget_control_parts($args)
        {
            $args[0]['before_widget_content'] = '<div class="widget-content">';
            $args[0]['after_widget_content'] = '</div><!-- .widget-content -->';
            $control_markup = $this->get_widget_control($args);

            $content_start_pos = strpos($control_markup, $args[0]['before_widget_content']);
            $content_end_pos = strrpos($control_markup, $args[0]['after_widget_content']);

            $control = substr($control_markup, 0, $content_start_pos + strlen($args[0]['before_widget_content']));
            $control .= substr($control_markup, $content_end_pos);
            $content = trim(substr($control_markup, $content_start_pos + strlen($args[0]['before_widget_content']), $content_end_pos - $content_start_pos - strlen($args[0]['before_widget_content'])));

            return compact('control', 'content');
        }

        public function customize_preview_init()
        {
            add_action('wp_enqueue_scripts', [$this, 'customize_preview_enqueue']);
            add_action('wp_print_styles', [$this, 'print_preview_css'], 1);
            add_action('wp_footer', [$this, 'export_preview_data'], 20);
        }

        public function refresh_nonces($nonces)
        {
            $nonces['update-widget'] = wp_create_nonce('update-widget');

            return $nonces;
        }

        public function should_load_block_editor_scripts_and_styles($is_block_editor_screen)
        {
            if(wp_use_widgets_block_editor())
            {
                return true;
            }

            return $is_block_editor_screen;
        }

        public function preview_sidebars_widgets($sidebars_widgets)
        {
            $sidebars_widgets = get_option('sidebars_widgets', []);

            unset($sidebars_widgets['array_version']);

            return $sidebars_widgets;
        }

        public function customize_preview_enqueue()
        {
            wp_enqueue_script('customize-preview-widgets');
        }

        public function print_preview_css()
        {
            ?>
            <style>
                .widget-customizer-highlighted-widget {
                    outline: none;
                    -webkit-box-shadow: 0 0 2px rgba(30, 140, 190, 0.8);
                    box-shadow: 0 0 2px rgba(30, 140, 190, 0.8);
                    position: relative;
                    z-index: 1;
                }
            </style>
            <?php
        }

        public function export_preview_data()
        {
            global $wp_registered_sidebars, $wp_registered_widgets;

            $switched_locale = switch_to_user_locale(get_current_user_id());

            $l10n = [
                'widgetTooltip' => __('Shift-click to edit this widget.'),
            ];

            if($switched_locale)
            {
                restore_previous_locale();
            }

            $rendered_sidebars = array_filter($this->rendered_sidebars);
            $rendered_widgets = array_filter($this->rendered_widgets);

            // Prepare Customizer settings to pass to JavaScript.
            $settings = [
                'renderedSidebars' => array_fill_keys(array_keys($rendered_sidebars), true),
                'renderedWidgets' => array_fill_keys(array_keys($rendered_widgets), true),
                'registeredSidebars' => array_values($wp_registered_sidebars),
                'registeredWidgets' => $wp_registered_widgets,
                'l10n' => $l10n,
                'selectiveRefreshableWidgets' => $this->get_selective_refreshable_widgets(),
            ];

            foreach($settings['registeredWidgets'] as &$registered_widget)
            {
                unset($registered_widget['callback']); // May not be JSON-serializeable.
            }

            ?>
            <script type="text/javascript">
                var _wpWidgetCustomizerPreviewSettings = <?php echo wp_json_encode($settings); ?>;
            </script>
            <?php
        }

        public function tally_rendered_widgets($widget)
        {
            $this->rendered_widgets[$widget['id']] = true;
        }

        public function is_widget_rendered($widget_id)
        {
            return ! empty($this->rendered_widgets[$widget_id]);
        }

        /*
	 * Selective Refresh Methods
	 */

        public function is_sidebar_rendered($sidebar_id)
        {
            return ! empty($this->rendered_sidebars[$sidebar_id]);
        }

        public function tally_sidebars_via_is_active_sidebar_calls($is_active, $sidebar_id)
        {
            if(is_registered_sidebar($sidebar_id))
            {
                $this->rendered_sidebars[$sidebar_id] = true;
            }

            /*
		 * We may need to force this to true, and also force-true the value
		 * for 'dynamic_sidebar_has_widgets' if we want to ensure that there
		 * is an area to drop widgets into, if the sidebar is empty.
		 */

            return $is_active;
        }

        public function tally_sidebars_via_dynamic_sidebar_calls($has_widgets, $sidebar_id)
        {
            if(is_registered_sidebar($sidebar_id))
            {
                $this->rendered_sidebars[$sidebar_id] = true;
            }

            /*
		 * We may need to force this to true, and also force-true the value
		 * for 'is_active_sidebar' if we want to ensure there is an area to
		 * drop widgets into, if the sidebar is empty.
		 */

            return $has_widgets;
        }

        public function sanitize_sidebar_widgets_js_instance($widget_ids)
        {
            global $wp_registered_widgets;
            $widget_ids = array_values(array_intersect($widget_ids, array_keys($wp_registered_widgets)));

            return $widget_ids;
        }

        public function wp_ajax_update_widget()
        {
            if(! is_user_logged_in())
            {
                wp_die(0);
            }

            check_ajax_referer('update-widget', 'nonce');

            if(! current_user_can('edit_theme_options'))
            {
                wp_die(-1);
            }

            if(empty($_POST['widget-id']))
            {
                wp_send_json_error('missing_widget-id');
            }

            do_action('load-widgets.php'); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

            do_action('widgets.php'); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

            do_action('sidebar_admin_setup');

            $widget_id = $this->get_post_value('widget-id');
            $parsed_id = $this->parse_widget_id($widget_id);
            $id_base = $parsed_id['id_base'];

            $is_updating_widget_template = (isset($_POST['widget-'.$id_base]) && is_array($_POST['widget-'.$id_base]) && preg_match('/__i__|%i%/', key($_POST['widget-'.$id_base])));
            if($is_updating_widget_template)
            {
                wp_send_json_error('template_widget_not_updatable');
            }

            $updated_widget = $this->call_widget_update($widget_id); // => {instance,form}
            if(is_wp_error($updated_widget))
            {
                wp_send_json_error($updated_widget->get_error_code());
            }

            $form = $updated_widget['form'];
            $instance = $this->sanitize_widget_js_instance($updated_widget['instance'], $id_base);

            wp_send_json_success(compact('form', 'instance'));
        }

        protected function get_post_value($name, $default_value = null)
        {
            if(! isset($_POST[$name]))
            {
                return $default_value;
            }

            return wp_unslash($_POST[$name]);
        }

        public function call_widget_update($widget_id)
        {
            global $wp_registered_widget_updates, $wp_registered_widget_controls;

            $setting_id = $this->get_setting_id($widget_id);

            /*
		 * Make sure that other setting changes have previewed since this widget
		 * may depend on them (e.g. Menus being present for Navigation Menu widget).
		 */
            if(! did_action('customize_preview_init'))
            {
                foreach($this->manager->settings() as $setting)
                {
                    if($setting->id !== $setting_id)
                    {
                        $setting->preview();
                    }
                }
            }

            $this->start_capturing_option_updates();
            $parsed_id = $this->parse_widget_id($widget_id);
            $option_name = 'widget_'.$parsed_id['id_base'];

            /*
		 * If a previously-sanitized instance is provided, populate the input vars
		 * with its values so that the widget update callback will read this instance
		 */
            $added_input_vars = [];
            if(! empty($_POST['sanitized_widget_setting']))
            {
                $sanitized_widget_setting = json_decode($this->get_post_value('sanitized_widget_setting'), true);
                if(false === $sanitized_widget_setting)
                {
                    $this->stop_capturing_option_updates();

                    return new WP_Error('widget_setting_malformed');
                }

                $instance = $this->sanitize_widget_instance($sanitized_widget_setting, $parsed_id['id_base']);
                if(is_null($instance))
                {
                    $this->stop_capturing_option_updates();

                    return new WP_Error('widget_setting_unsanitized');
                }

                if(is_null($parsed_id['number']))
                {
                    foreach($instance as $key => $value)
                    {
                        $_REQUEST[$key] = wp_slash($value);
                        $_POST[$key] = $_REQUEST[$key];
                        $added_input_vars[] = $key;
                    }
                }
                else
                {
                    $value = [];
                    $value[$parsed_id['number']] = $instance;
                    $key = 'widget-'.$parsed_id['id_base'];
                    $_REQUEST[$key] = wp_slash($value);
                    $_POST[$key] = $_REQUEST[$key];
                    $added_input_vars[] = $key;
                }
            }

            // Invoke the widget update callback.
            foreach((array) $wp_registered_widget_updates as $name => $control)
            {
                if($name === $parsed_id['id_base'] && is_callable($control['callback']))
                {
                    ob_start();
                    call_user_func_array($control['callback'], $control['params']);
                    ob_end_clean();
                    break;
                }
            }

            // Clean up any input vars that were manually added.
            foreach($added_input_vars as $key)
            {
                unset($_POST[$key]);
                unset($_REQUEST[$key]);
            }

            // Make sure the expected option was updated.
            if(0 !== $this->count_captured_options())
            {
                if($this->count_captured_options() > 1)
                {
                    $this->stop_capturing_option_updates();

                    return new WP_Error('widget_setting_too_many_options');
                }

                $updated_option_name = key($this->get_captured_options());
                if($updated_option_name !== $option_name)
                {
                    $this->stop_capturing_option_updates();

                    return new WP_Error('widget_setting_unexpected_option');
                }
            }

            // Obtain the widget instance.
            $option = $this->get_captured_option($option_name);
            if(null !== $parsed_id['number'])
            {
                $instance = $option[$parsed_id['number']];
            }
            else
            {
                $instance = $option;
            }

            /*
		 * Override the incoming $_POST['customized'] for a newly-created widget's
		 * setting with the new $instance so that the preview filter currently
		 * in place from WP_Customize_Setting::preview() will use this value
		 * instead of the default widget instance value (an empty array).
		 */
            $this->manager->set_post_value($setting_id, $this->sanitize_widget_js_instance($instance, $parsed_id['id_base']));

            // Obtain the widget control with the updated instance in place.
            ob_start();
            $form = $wp_registered_widget_controls[$widget_id];
            if($form)
            {
                call_user_func_array($form['callback'], $form['params']);
            }
            $form = ob_get_clean();

            $this->stop_capturing_option_updates();

            return compact('instance', 'form');
        }

        protected function start_capturing_option_updates()
        {
            if($this->_is_capturing_option_updates)
            {
                return;
            }

            $this->_is_capturing_option_updates = true;

            add_filter('pre_update_option', [$this, 'capture_filter_pre_update_option'], 10, 3);
        }

        protected function stop_capturing_option_updates()
        {
            if(! $this->_is_capturing_option_updates)
            {
                return;
            }

            remove_filter('pre_update_option', [$this, 'capture_filter_pre_update_option'], 10);

            foreach(array_keys($this->_captured_options) as $option_name)
            {
                remove_filter("pre_option_{$option_name}", [$this, 'capture_filter_pre_get_option']);
            }

            $this->_captured_options = [];
            $this->_is_capturing_option_updates = false;
        }

        protected function count_captured_options()
        {
            return count($this->_captured_options);
        }

        protected function get_captured_options()
        {
            return $this->_captured_options;
        }

        protected function get_captured_option($option_name, $default_value = false)
        {
            if(array_key_exists($option_name, $this->_captured_options))
            {
                $value = $this->_captured_options[$option_name];
            }
            else
            {
                $value = $default_value;
            }

            return $value;
        }

        public function customize_dynamic_partial_args($partial_args, $partial_id)
        {
            if(! current_theme_supports('customize-selective-refresh-widgets'))
            {
                return $partial_args;
            }

            if(preg_match('/^widget\[(?P<widget_id>.+)\]$/', $partial_id, $matches))
            {
                if(false === $partial_args)
                {
                    $partial_args = [];
                }
                $partial_args = array_merge($partial_args, [
                    'type' => 'widget',
                    'render_callback' => [$this, 'render_widget_partial'],
                    'container_inclusive' => true,
                    'settings' => [$this->get_setting_id($matches['widget_id'])],
                    'capability' => 'edit_theme_options',
                ]);
            }

            return $partial_args;
        }

        public function selective_refresh_init()
        {
            if(! current_theme_supports('customize-selective-refresh-widgets'))
            {
                return;
            }
            add_filter('dynamic_sidebar_params', [$this, 'filter_dynamic_sidebar_params']);
            add_filter('wp_kses_allowed_html', [$this, 'filter_wp_kses_allowed_data_attributes']);
            add_action('dynamic_sidebar_before', [$this, 'start_dynamic_sidebar']);
            add_action('dynamic_sidebar_after', [$this, 'end_dynamic_sidebar']);
        }

        //
        // Option Update Capturing.
        //

        public function filter_dynamic_sidebar_params($params)
        {
            $sidebar_args = array_merge([
                                            'before_widget' => '',
                                            'after_widget' => '',
                                        ], $params[0]);

            // Skip widgets not in a registered sidebar or ones which lack a proper wrapper element to attach the data-* attributes to.
            $matches = [];
            $is_valid = (isset($sidebar_args['id']) && is_registered_sidebar($sidebar_args['id']) && (isset($this->current_dynamic_sidebar_id_stack[0]) && $this->current_dynamic_sidebar_id_stack[0] === $sidebar_args['id']) && preg_match('#^<(?P<tag_name>\w+)#', $sidebar_args['before_widget'], $matches));
            if(! $is_valid)
            {
                return $params;
            }
            $this->before_widget_tags_seen[$matches['tag_name']] = true;

            $context = [
                'sidebar_id' => $sidebar_args['id'],
            ];
            if(isset($this->context_sidebar_instance_number))
            {
                $context['sidebar_instance_number'] = $this->context_sidebar_instance_number;
            }
            elseif(isset($sidebar_args['id']) && isset($this->sidebar_instance_count[$sidebar_args['id']]))
            {
                $context['sidebar_instance_number'] = $this->sidebar_instance_count[$sidebar_args['id']];
            }

            $attributes = sprintf(' data-customize-partial-id="%s"', esc_attr('widget['.$sidebar_args['widget_id'].']'));
            $attributes .= ' data-customize-partial-type="widget"';
            $attributes .= sprintf(' data-customize-partial-placement-context="%s"', esc_attr(wp_json_encode($context)));
            $attributes .= sprintf(' data-customize-widget-id="%s"', esc_attr($sidebar_args['widget_id']));
            $sidebar_args['before_widget'] = preg_replace('#^(<\w+)#', '$1 '.$attributes, $sidebar_args['before_widget']);

            $params[0] = $sidebar_args;

            return $params;
        }

        public function filter_wp_kses_allowed_data_attributes($allowed_html)
        {
            foreach(array_keys($this->before_widget_tags_seen) as $tag_name)
            {
                if(! isset($allowed_html[$tag_name]))
                {
                    $allowed_html[$tag_name] = [];
                }
                $allowed_html[$tag_name] = array_merge(
                    $allowed_html[$tag_name], array_fill_keys([
                                                                  'data-customize-partial-id',
                                                                  'data-customize-partial-type',
                                                                  'data-customize-partial-placement-context',
                                                                  'data-customize-partial-widget-id',
                                                                  'data-customize-partial-options',
                                                              ], true)
                );
            }

            return $allowed_html;
        }

        public function start_dynamic_sidebar($index)
        {
            array_unshift($this->current_dynamic_sidebar_id_stack, $index);
            if(! isset($this->sidebar_instance_count[$index]))
            {
                $this->sidebar_instance_count[$index] = 0;
            }
            ++$this->sidebar_instance_count[$index];
            if(! $this->manager->selective_refresh->is_render_partials_request())
            {
                printf("\n<!--dynamic_sidebar_before:%s:%d-->\n", esc_html($index), (int) $this->sidebar_instance_count[$index]);
            }
        }

        public function end_dynamic_sidebar($index)
        {
            array_shift($this->current_dynamic_sidebar_id_stack);
            if(! $this->manager->selective_refresh->is_render_partials_request())
            {
                printf("\n<!--dynamic_sidebar_after:%s:%d-->\n", esc_html($index), (int) $this->sidebar_instance_count[$index]);
            }
        }

        public function filter_sidebars_widgets_for_rendering_widget($sidebars_widgets)
        {
            $sidebars_widgets[$this->rendering_sidebar_id] = [$this->rendering_widget_id];

            return $sidebars_widgets;
        }

        public function render_widget_partial($partial, $context)
        {
            $id_data = $partial->id_data();
            $widget_id = array_shift($id_data['keys']);

            if(! is_array($context) || empty($context['sidebar_id']) || ! is_registered_sidebar($context['sidebar_id']))
            {
                return false;
            }

            $this->rendering_sidebar_id = $context['sidebar_id'];

            if(isset($context['sidebar_instance_number']))
            {
                $this->context_sidebar_instance_number = (int) $context['sidebar_instance_number'];
            }

            // Filter sidebars_widgets so that only the queried widget is in the sidebar.
            $this->rendering_widget_id = $widget_id;

            $filter_callback = [$this, 'filter_sidebars_widgets_for_rendering_widget'];
            add_filter('sidebars_widgets', $filter_callback, 1000);

            // Render the widget.
            ob_start();
            $this->rendering_sidebar_id = $context['sidebar_id'];
            dynamic_sidebar($this->rendering_sidebar_id);
            $container = ob_get_clean();

            // Reset variables for next partial render.
            remove_filter('sidebars_widgets', $filter_callback, 1000);

            $this->context_sidebar_instance_number = null;
            $this->rendering_sidebar_id = null;
            $this->rendering_widget_id = null;

            return $container;
        }

        public function capture_filter_pre_update_option($new_value, $option_name, $old_value)
        {
            if($this->is_option_capture_ignored($option_name))
            {
                return $new_value;
            }

            if(! isset($this->_captured_options[$option_name]))
            {
                add_filter("pre_option_{$option_name}", [$this, 'capture_filter_pre_get_option']);
            }

            $this->_captured_options[$option_name] = $new_value;

            return $old_value;
        }

        protected function is_option_capture_ignored($option_name)
        {
            return (str_starts_with($option_name, '_transient_'));
        }

        public function capture_filter_pre_get_option($value)
        {
            $option_name = preg_replace('/^pre_option_/', '', current_filter());

            if(isset($this->_captured_options[$option_name]))
            {
                $value = $this->_captured_options[$option_name];

                $value = apply_filters('option_'.$option_name, $value, $option_name);
            }

            return $value;
        }

        public function setup_widget_addition_previews()
        {
            _deprecated_function(__METHOD__, '4.2.0', 'customize_dynamic_setting_args');
        }

        public function prepreview_added_sidebars_widgets()
        {
            _deprecated_function(__METHOD__, '4.2.0', 'customize_dynamic_setting_args');
        }

        public function prepreview_added_widget_instance()
        {
            _deprecated_function(__METHOD__, '4.2.0', 'customize_dynamic_setting_args');
        }

        public function remove_prepreview_filters()
        {
            _deprecated_function(__METHOD__, '4.2.0', 'customize_dynamic_setting_args');
        }

        protected function _sort_name_callback($widget_a, $widget_b)
        {
            return strnatcasecmp($widget_a['name'], $widget_b['name']);
        }
    }
