<?php

    abstract class WP_Widget_Media extends WP_Widget
    {
        protected static $default_description = '';

        protected static $l10n_defaults = [];

        public $l10n = [
            'add_to_widget' => '',
            'replace_media' => '',
            'edit_media' => '',
            'media_library_state_multi' => '',
            'media_library_state_single' => '',
            'missing_attachment' => '',
            'no_media_selected' => '',
            'add_media' => '',
        ];

        protected $registered = false;

        public function __construct($id_base, $name, $widget_options = [], $control_options = [])
        {
            $widget_opts = wp_parse_args($widget_options, [
                'description' => self::get_default_description(),
                'customize_selective_refresh' => true,
                'show_instance_in_rest' => true,
                'mime_type' => '',
            ]);

            $control_opts = wp_parse_args($control_options, []);

            $this->l10n = array_merge(self::get_l10n_defaults(), array_filter($this->l10n));

            parent::__construct($id_base, $name, $widget_opts, $control_opts);
        }

        protected static function get_default_description()
        {
            if(self::$default_description)
            {
                return self::$default_description;
            }

            self::$default_description = __('A media item.');

            return self::$default_description;
        }

        protected static function get_l10n_defaults()
        {
            if(! empty(self::$l10n_defaults))
            {
                return self::$l10n_defaults;
            }

            self::$l10n_defaults = [
                'no_media_selected' => __('No media selected'),
                'add_media' => _x('Add Media', 'label for button in the media widget'),
                'replace_media' => _x('Replace Media', 'label for button in the media widget; should preferably not be longer than ~13 characters long'),
                'edit_media' => _x('Edit Media', 'label for button in the media widget; should preferably not be longer than ~13 characters long'),
                'add_to_widget' => __('Add to Widget'),
                'missing_attachment' => sprintf(/* translators: %s: URL to media library. */ __('That file cannot be found. Check your <a href="%s">media library</a> and make sure it was not deleted.'), esc_url(admin_url('upload.php'))),
                /* translators: %d: Widget count. */
                'media_library_state_multi' => _n_noop('Media Widget (%d)', 'Media Widget (%d)'),
                'media_library_state_single' => __('Media Widget'),
                'unsupported_file_type' => __('Looks like this is not the correct kind of file. Please link to an appropriate file instead.'),
            ];

            return self::$l10n_defaults;
        }

        public static function reset_default_labels()
        {
            self::$default_description = '';
            self::$l10n_defaults = [];
        }

        public function _register_one($number = -1)
        {
            parent::_register_one($number);
            if($this->registered)
            {
                return;
            }
            $this->registered = true;

            /*
             * Note that the widgets component in the customizer will also do
             * the 'admin_print_scripts-widgets.php' action in WP_Customize_Widgets::print_scripts().
             */
            add_action('admin_print_scripts-widgets.php', [$this, 'enqueue_admin_scripts']);

            if($this->is_preview())
            {
                add_action('wp_enqueue_scripts', [$this, 'enqueue_preview_scripts']);
            }

            /*
             * Note that the widgets component in the customizer will also do
             * the 'admin_footer-widgets.php' action in WP_Customize_Widgets::print_footer_scripts().
             */
            add_action('admin_footer-widgets.php', [$this, 'render_control_template_scripts']);

            add_filter('display_media_states', [$this, 'display_media_state'], 10, 2);
        }

        public function is_attachment_with_mime_type($attachment, $mime_type)
        {
            if(empty($attachment))
            {
                return false;
            }
            $attachment = get_post($attachment);
            if(! $attachment)
            {
                return false;
            }
            if('attachment' !== $attachment->post_type)
            {
                return false;
            }

            return wp_attachment_is($mime_type, $attachment);
        }

        public function sanitize_token_list($tokens)
        {
            if(is_string($tokens))
            {
                $tokens = preg_split('/\s+/', trim($tokens));
            }
            $tokens = array_map('sanitize_html_class', $tokens);
            $tokens = array_filter($tokens);

            return implode(' ', $tokens);
        }

        public function widget($args, $instance)
        {
            $instance = wp_parse_args($instance, wp_list_pluck($this->get_instance_schema(), 'default'));

            // Short-circuit if no media is selected.
            if(! $this->has_content($instance))
            {
                return;
            }

            echo $args['before_widget'];

            $title = apply_filters('widget_title', $instance['title'], $instance, $this->id_base);

            if($title)
            {
                echo $args['before_title'].$title.$args['after_title'];
            }

            $instance = apply_filters("widget_{$this->id_base}_instance", $instance, $args, $this);

            $this->render_media($instance);

            echo $args['after_widget'];
        }

        public function get_instance_schema()
        {
            $schema = [
                'attachment_id' => [
                    'type' => 'integer',
                    'default' => 0,
                    'minimum' => 0,
                    'description' => __('Attachment post ID'),
                    'media_prop' => 'id',
                ],
                'url' => [
                    'type' => 'string',
                    'default' => '',
                    'format' => 'uri',
                    'description' => __('URL to the media file'),
                ],
                'title' => [
                    'type' => 'string',
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field',
                    'description' => __('Title for the widget'),
                    'should_preview_update' => false,
                ],
            ];

            $schema = apply_filters("widget_{$this->id_base}_instance_schema", $schema, $this);

            return $schema;
        }

        protected function has_content($instance)
        {
            return ($instance['attachment_id'] && 'attachment' === get_post_type($instance['attachment_id'])) || $instance['url'];
        }

        abstract public function render_media($instance);

        public function update($new_instance, $old_instance)
        {
            $schema = $this->get_instance_schema();
            foreach($schema as $field => $field_schema)
            {
                if(! array_key_exists($field, $new_instance))
                {
                    continue;
                }
                $value = $new_instance[$field];

                /*
                 * Workaround for rest_validate_value_from_schema() due to the fact that
                 * rest_is_boolean( '' ) === false, while rest_is_boolean( '1' ) is true.
                 */
                if('boolean' === $field_schema['type'] && '' === $value)
                {
                    $value = false;
                }

                if(true !== rest_validate_value_from_schema($value, $field_schema, $field))
                {
                    continue;
                }

                $value = rest_sanitize_value_from_schema($value, $field_schema);

                // @codeCoverageIgnoreStart
                if(is_wp_error($value))
                {
                    continue; // Handle case when rest_sanitize_value_from_schema() ever returns WP_Error as its phpdoc @return tag indicates.
                }

                // @codeCoverageIgnoreEnd
                if(isset($field_schema['sanitize_callback']))
                {
                    $value = call_user_func($field_schema['sanitize_callback'], $value);
                }
                if(is_wp_error($value))
                {
                    continue;
                }
                $old_instance[$field] = $value;
            }

            return $old_instance;
        }

        final public function form($instance)
        {
            $instance_schema = $this->get_instance_schema();
            $instance = wp_array_slice_assoc(wp_parse_args((array) $instance, wp_list_pluck($instance_schema, 'default')), array_keys($instance_schema));

            foreach($instance as $name => $value) : ?>
                <input
                        type="hidden"
                        data-property="<?php echo esc_attr($name); ?>"
                        class="media-widget-instance-property"
                        name="<?php echo esc_attr($this->get_field_name($name)); ?>"
                        id="<?php echo esc_attr($this->get_field_id($name)); // Needed specifically by wpWidgets.appendTitle(). ?>"
                        value="<?php echo esc_attr(is_array($value) ? implode(',', $value) : (string) $value); ?>"
                />
            <?php
            endforeach;
        }

        public function display_media_state($states, $post = null)
        {
            if(! $post)
            {
                $post = get_post();
            }

            // Count how many times this attachment is used in widgets.
            $use_count = 0;
            foreach($this->get_settings() as $instance)
            {
                if(isset($instance['attachment_id']) && $instance['attachment_id'] === $post->ID)
                {
                    ++$use_count;
                }
            }

            if(1 === $use_count)
            {
                $states[] = $this->l10n['media_library_state_single'];
            }
            elseif($use_count > 0)
            {
                $states[] = sprintf(translate_nooped_plural($this->l10n['media_library_state_multi'], $use_count), number_format_i18n($use_count));
            }

            return $states;
        }

        public function enqueue_preview_scripts() {}

        public function enqueue_admin_scripts()
        {
            wp_enqueue_media();
            wp_enqueue_script('media-widgets');
        }

        public function render_control_template_scripts()
        {
            ?>
            <script type="text/html" id="tmpl-widget-media-<?php echo esc_attr($this->id_base); ?>-control">
                <# var elementIdPrefix = 'el' + String( Math.random() ) + '_' #>
                <p>
                    <label for="{{ elementIdPrefix }}title"><?php esc_html_e('Title:'); ?></label>
                    <input id="{{ elementIdPrefix }}title" type="text" class="widefat title">
                </p>
                <div class="media-widget-preview <?php echo esc_attr($this->id_base); ?>">
                    <div class="attachment-media-view">
                        <button type="button" class="select-media button-add-media not-selected">
                            <?php echo esc_html($this->l10n['add_media']); ?>
                        </button>
                    </div>
                </div>
                <p class="media-widget-buttons">
                    <button type="button" class="button edit-media selected">
                        <?php echo esc_html($this->l10n['edit_media']); ?>
                    </button>
                    <?php if(! empty($this->l10n['replace_media'])) : ?>
                        <button type="button" class="button change-media select-media selected">
                            <?php echo esc_html($this->l10n['replace_media']); ?>
                        </button>
                    <?php endif; ?>
                </p>
                <div class="media-widget-fields">
                </div>
            </script>
            <?php
        }
    }
