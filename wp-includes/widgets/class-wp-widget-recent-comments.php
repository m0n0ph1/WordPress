<?php

    class WP_Widget_Recent_Comments extends WP_Widget
    {
        public function __construct()
        {
            $widget_ops = [
                'classname' => 'widget_recent_comments',
                'description' => __('Your site&#8217;s most recent comments.'),
                'customize_selective_refresh' => true,
                'show_instance_in_rest' => true,
            ];
            parent::__construct('recent-comments', __('Recent Comments'), $widget_ops);
            $this->alt_option_name = 'widget_recent_comments';

            if(is_active_widget(false, false, $this->id_base) || is_customize_preview())
            {
                add_action('wp_head', [$this, 'recent_comments_style']);
            }
        }

        public function recent_comments_style()
        {
            if(
                ! current_theme_supports('widgets') // Temp hack #14876.
                || ! apply_filters('show_recent_comments_widget_style', true, $this->id_base)
            )
            {
                return;
            }

            $type_attr = current_theme_supports('html5', 'style') ? '' : ' type="text/css"';

            printf('<style%s>.recentcomments a{display:inline !important;padding:0 !important;margin:0 !important;}</style>', $type_attr);
        }

        public function widget($args, $instance)
        {
            parent::widget($args, $instance);
            static $first_instance = true;

            if(! isset($args['widget_id']))
            {
                $args['widget_id'] = $this->id;
            }

            $output = '';

            $default_title = __('Recent Comments');
            $title = (! empty($instance['title'])) ? $instance['title'] : $default_title;

            $title = apply_filters('widget_title', $title, $instance, $this->id_base);

            $number = (! empty($instance['number'])) ? absint($instance['number']) : 5;
            if(! $number)
            {
                $number = 5;
            }

            $comments = get_comments(
                apply_filters('widget_comments_args', [
                    'number' => $number,
                    'status' => 'approve',
                    'post_status' => 'publish',
                ],            $instance)
            );

            $output .= $args['before_widget'];
            if($title)
            {
                $output .= $args['before_title'].$title.$args['after_title'];
            }

            $recent_comments_id = ($first_instance) ? 'recentcomments' : "recentcomments-{$this->number}";
            $first_instance = false;

            $format = current_theme_supports('html5', 'navigation-widgets') ? 'html5' : 'xhtml';

            $format = apply_filters('navigation_widgets_format', $format);

            if('html5' === $format)
            {
                // The title may be filtered: Strip out HTML and make sure the aria-label is never empty.
                $title = trim(strip_tags($title));
                $aria_label = $title ? $title : $default_title;
                $output .= '<nav aria-label="'.esc_attr($aria_label).'">';
            }

            $output .= '<ul id="'.esc_attr($recent_comments_id).'">';
            if(is_array($comments) && $comments)
            {
                // Prime cache for associated posts. (Prime post term cache if we need it for permalinks.)
                $post_ids = array_unique(wp_list_pluck($comments, 'comment_post_ID'));
                _prime_post_caches($post_ids, strpos(get_option('permalink_structure'), '%category%'), false);

                foreach((array) $comments as $comment)
                {
                    $output .= '<li class="recentcomments">';
                    $output .= sprintf(/* translators: Comments widget. 1: Comment author, 2: Post link. */ _x('%1$s on %2$s', 'widgets'), '<span class="comment-author-link">'.get_comment_author_link($comment).'</span>', '<a href="'.esc_url(get_comment_link($comment)).'">'.get_the_title($comment->comment_post_ID).'</a>');
                    $output .= '</li>';
                }
            }
            $output .= '</ul>';

            if('html5' === $format)
            {
                $output .= '</nav>';
            }

            $output .= $args['after_widget'];

            echo $output;
        }

        public function update($new_instance, $old_instance)
        {
            $instance = $old_instance;
            $instance['title'] = sanitize_text_field($new_instance['title']);
            $instance['number'] = absint($new_instance['number']);

            return $instance;
        }

        public function form($instance)
        {
            parent::form($instance);
            $title = isset($instance['title']) ? $instance['title'] : '';
            $number = isset($instance['number']) ? absint($instance['number']) : 5;
            ?>
            <p>
                <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
                <input class="widefat"
                       id="<?php echo $this->get_field_id('title'); ?>"
                       name="<?php echo $this->get_field_name('title'); ?>"
                       type="text"
                       value="<?php echo esc_attr($title); ?>"/>
            </p>

            <p>
                <label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of comments to show:'); ?></label>
                <input class="tiny-text"
                       id="<?php echo $this->get_field_id('number'); ?>"
                       name="<?php echo $this->get_field_name('number'); ?>"
                       type="number"
                       step="1"
                       min="1"
                       value="<?php echo $number; ?>"
                       size="3"/>
            </p>
            <?php
        }

        public function flush_widget_cache()
        {
            _deprecated_function(__METHOD__, '4.4.0');
        }
    }
