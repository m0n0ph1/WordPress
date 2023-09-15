<?php

    class WP_Widget_Media_Audio extends WP_Widget_Media
    {
        public function __construct()
        {
            parent::__construct('media_audio', __('Audio'), [
                'description' => __('Displays an audio player.'),
                'mime_type' => 'audio',
            ]);

            $this->l10n = array_merge($this->l10n, [
                'no_media_selected' => __('No audio selected'),
                'add_media' => _x('Add Audio', 'label for button in the audio widget'),
                'replace_media' => _x('Replace Audio', 'label for button in the audio widget; should preferably not be longer than ~13 characters long'),
                'edit_media' => _x('Edit Audio', 'label for button in the audio widget; should preferably not be longer than ~13 characters long'),
                'missing_attachment' => sprintf(/* translators: %s: URL to media library. */ __('That audio file cannot be found. Check your <a href="%s">media library</a> and make sure it was not deleted.'), esc_url(admin_url('upload.php'))),
                /* translators: %d: Widget count. */
                'media_library_state_multi' => _n_noop('Audio Widget (%d)', 'Audio Widget (%d)'),
                'media_library_state_single' => __('Audio Widget'),
                'unsupported_file_type' => __('Looks like this is not the correct kind of file. Please link to an audio file instead.'),
            ]);
        }

        public function render_media($instance)
        {
            $instance = array_merge(wp_list_pluck($this->get_instance_schema(), 'default'), $instance);
            $attachment = null;

            if($this->is_attachment_with_mime_type($instance['attachment_id'], $this->widget_options['mime_type']))
            {
                $attachment = get_post($instance['attachment_id']);
            }

            if($attachment)
            {
                $src = wp_get_attachment_url($attachment->ID);
            }
            else
            {
                $src = $instance['url'];
            }

            echo wp_audio_shortcode(array_merge($instance, compact('src')));
        }

        public function get_instance_schema()
        {
            $schema = [
                'preload' => [
                    'type' => 'string',
                    'enum' => ['none', 'auto', 'metadata'],
                    'default' => 'none',
                    'description' => __('Preload'),
                ],
                'loop' => [
                    'type' => 'boolean',
                    'default' => false,
                    'description' => __('Loop'),
                ],
            ];

            foreach(wp_get_audio_extensions() as $audio_extension)
            {
                $schema[$audio_extension] = [
                    'type' => 'string',
                    'default' => '',
                    'format' => 'uri',
                    /* translators: %s: Audio extension. */
                    'description' => sprintf(__('URL to the %s audio source file'), $audio_extension),
                ];
            }

            return array_merge($schema, parent::get_instance_schema());
        }

        public function enqueue_preview_scripts()
        {
            if('mediaelement' === apply_filters('wp_audio_shortcode_library', 'mediaelement'))
            {
                wp_enqueue_style('wp-mediaelement');
                wp_enqueue_script('wp-mediaelement');
            }
        }

        public function enqueue_admin_scripts()
        {
            parent::enqueue_admin_scripts();

            wp_enqueue_style('wp-mediaelement');
            wp_enqueue_script('wp-mediaelement');

            $handle = 'media-audio-widget';
            wp_enqueue_script($handle);

            $exported_schema = [];
            foreach($this->get_instance_schema() as $field => $field_schema)
            {
                $exported_schema[$field] = wp_array_slice_assoc($field_schema, [
                    'type',
                    'default',
                    'enum',
                    'minimum',
                    'format',
                    'media_prop',
                    'should_preview_update'
                ]);
            }
            wp_add_inline_script($handle, sprintf('wp.mediaWidgets.modelConstructors[ %s ].prototype.schema = %s;', wp_json_encode($this->id_base), wp_json_encode($exported_schema)));

            wp_add_inline_script(
                $handle, sprintf(
                           '
					wp.mediaWidgets.controlConstructors[ %1$s ].prototype.mime_type = %2$s;
					wp.mediaWidgets.controlConstructors[ %1$s ].prototype.l10n = _.extend( {}, wp.mediaWidgets.controlConstructors[ %1$s ].prototype.l10n, %3$s );
				', wp_json_encode($this->id_base), wp_json_encode($this->widget_options['mime_type']), wp_json_encode($this->l10n)
                       )
            );
        }

        public function render_control_template_scripts()
        {
            parent::render_control_template_scripts()
            ?>
            <script type="text/html" id="tmpl-wp-media-widget-audio-preview">
                <# if ( data.error && 'missing_attachment' === data.error ) { #>
                <div class="notice notice-error notice-alt notice-missing-attachment">
                    <p><?php echo $this->l10n['missing_attachment']; ?></p>
                </div>
                <# } else if ( data.error ) { #>
                <div class="notice notice-error notice-alt">
                    <p><?php _e('Unable to preview media due to an unknown error.'); ?></p>
                </div>
                <# } else if ( data.model && data.model.src ) { #>
                <?php wp_underscore_audio_template(); ?>
                <# } #>
            </script>
            <?php
        }
    }
