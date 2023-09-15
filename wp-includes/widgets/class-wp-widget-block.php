<?php

    class WP_Widget_Block extends WP_Widget
    {
        protected $default_instance = [
            'content' => '',
        ];

        public function __construct()
        {
            $widget_ops = [
                'classname' => 'widget_block',
                'description' => __('A widget containing a block.'),
                'customize_selective_refresh' => true,
                'show_instance_in_rest' => true,
            ];
            $control_ops = [
                'width' => 400,
                'height' => 350,
            ];
            parent::__construct('block', __('Block'), $widget_ops, $control_ops);

            add_filter('is_wide_widget_in_customizer', [$this, 'set_is_wide_widget_in_customizer'], 10, 2);
        }

        public function widget($args, $instance)
        {
            $instance = wp_parse_args($instance, $this->default_instance);

            echo str_replace('widget_block', $this->get_dynamic_classname($instance['content']), $args['before_widget']);

            echo apply_filters('widget_block_content', $instance['content'], $instance, $this);

            echo $args['after_widget'];
        }

        private function get_dynamic_classname($content)
        {
            $blocks = parse_blocks($content);

            $block_name = isset($blocks[0]) ? $blocks[0]['blockName'] : null;

            switch($block_name)
            {
                case 'core/paragraph':
                    $classname = 'widget_block widget_text';
                    break;
                case 'core/calendar':
                    $classname = 'widget_block widget_calendar';
                    break;
                case 'core/search':
                    $classname = 'widget_block widget_search';
                    break;
                case 'core/html':
                    $classname = 'widget_block widget_custom_html';
                    break;
                case 'core/archives':
                    $classname = 'widget_block widget_archive';
                    break;
                case 'core/latest-posts':
                    $classname = 'widget_block widget_recent_entries';
                    break;
                case 'core/latest-comments':
                    $classname = 'widget_block widget_recent_comments';
                    break;
                case 'core/tag-cloud':
                    $classname = 'widget_block widget_tag_cloud';
                    break;
                case 'core/categories':
                    $classname = 'widget_block widget_categories';
                    break;
                case 'core/audio':
                    $classname = 'widget_block widget_media_audio';
                    break;
                case 'core/video':
                    $classname = 'widget_block widget_media_video';
                    break;
                case 'core/image':
                    $classname = 'widget_block widget_media_image';
                    break;
                case 'core/gallery':
                    $classname = 'widget_block widget_media_gallery';
                    break;
                case 'core/rss':
                    $classname = 'widget_block widget_rss';
                    break;
                default:
                    $classname = 'widget_block';
            }

            return apply_filters('widget_block_dynamic_classname', $classname, $block_name);
        }

        public function update($new_instance, $old_instance)
        {
            $instance = array_merge($this->default_instance, $old_instance);

            if(current_user_can('unfiltered_html'))
            {
                $instance['content'] = $new_instance['content'];
            }
            else
            {
                $instance['content'] = wp_kses_post($new_instance['content']);
            }

            return $instance;
        }

        public function form($instance)
        {
            $instance = wp_parse_args((array) $instance, $this->default_instance);
            ?>
            <p>
                <label for="<?php echo $this->get_field_id('content'); ?>">
                    <?php
                        /* translators: HTML code of the block, not an option that blocks HTML. */
                        _e('Block HTML:');
                    ?>
                </label>
                <textarea id="<?php echo $this->get_field_id('content'); ?>"
                          name="<?php echo $this->get_field_name('content'); ?>"
                          rows="6"
                          cols="50"
                          class="widefat code"><?php echo esc_textarea($instance['content']); ?></textarea>
            </p>
            <?php
        }

        public function set_is_wide_widget_in_customizer($is_wide, $widget_id)
        {
            if(str_starts_with($widget_id, 'block-'))
            {
                return false;
            }

            return $is_wide;
        }
    }
