<?php

    class WP_Widget_Calendar extends WP_Widget
    {
        private static $instance = 0;

        public function __construct()
        {
            $widget_ops = [
                'classname' => 'widget_calendar',
                'description' => __('A calendar of your site’s posts.'),
                'customize_selective_refresh' => true,
                'show_instance_in_rest' => true,
            ];
            parent::__construct('calendar', __('Calendar'), $widget_ops);
        }

        public function widget($args, $instance)
        {
            $title = ! empty($instance['title']) ? $instance['title'] : '';

            $title = apply_filters('widget_title', $title, $instance, $this->id_base);

            echo $args['before_widget'];
            if($title)
            {
                echo $args['before_title'].$title.$args['after_title'];
            }
            if(0 === self::$instance)
            {
                echo '<div id="calendar_wrap" class="calendar_wrap">';
            }
            else
            {
                echo '<div class="calendar_wrap">';
            }
            get_calendar();
            echo '</div>';
            echo $args['after_widget'];

            ++self::$instance;
        }

        public function update($new_instance, $old_instance)
        {
            $instance = $old_instance;
            $instance['title'] = sanitize_text_field($new_instance['title']);

            return $instance;
        }

        public function form($instance)
        {
            $instance = wp_parse_args((array) $instance, ['title' => '']);
            ?>
            <p>
                <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
                <input class="widefat"
                       id="<?php echo $this->get_field_id('title'); ?>"
                       name="<?php echo $this->get_field_name('title'); ?>"
                       type="text"
                       value="<?php echo esc_attr($instance['title']); ?>"/>
            </p>
            <?php
        }
    }
