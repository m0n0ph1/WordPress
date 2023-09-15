<?php

    class WP_Widget_RSS extends WP_Widget
    {
        public function __construct()
        {
            $widget_ops = [
                'description' => __('Entries from any RSS or Atom feed.'),
                'customize_selective_refresh' => true,
                'show_instance_in_rest' => true,

            ];
            $control_ops = [
                'width' => 400,
                'height' => 200,
            ];
            parent::__construct('rss', __('RSS'), $widget_ops, $control_ops);
        }

        public function widget($args, $instance)
        {
            parent::widget($args, $instance);
            if(isset($instance['error']) && $instance['error'])
            {
                return;
            }

            $url = ! empty($instance['url']) ? $instance['url'] : '';
            while(! empty($url) && stristr($url, 'http') !== $url)
            {
                $url = substr($url, 1);
            }

            // Self-URL destruction sequence.
            if(empty($url) || in_array(untrailingslashit($url), [site_url(), home_url()], true))
            {
                return;
            }

            $rss = fetch_feed($url);
            $title = $instance['title'];
            $desc = '';
            $link = '';

            if(! is_wp_error($rss))
            {
                $desc = esc_attr(strip_tags(html_entity_decode($rss->get_description(), ENT_QUOTES, get_option('blog_charset'))));
                if(empty($title))
                {
                    $title = strip_tags($rss->get_title());
                }
                $link = strip_tags($rss->get_permalink());
                while(! empty($link) && stristr($link, 'http') !== $link)
                {
                    $link = substr($link, 1);
                }
            }

            if(empty($title))
            {
                $title = ! empty($desc) ? $desc : __('Unknown Feed');
            }

            $title = apply_filters('widget_title', $title, $instance, $this->id_base);

            if($title)
            {
                $feed_link = '';
                $feed_url = strip_tags($url);
                $feed_icon = includes_url('images/rss.png');
                $feed_link = sprintf('<a class="rsswidget rss-widget-feed" href="%1$s"><img class="rss-widget-icon" style="border:0" width="14" height="14" src="%2$s" alt="%3$s"%4$s /></a> ', esc_url($feed_url), esc_url($feed_icon), esc_attr__('RSS'), (wp_lazy_loading_enabled('img', 'rss_widget_feed_icon') ? ' loading="lazy"' : ''));

                $feed_link = apply_filters('rss_widget_feed_link', $feed_link, $instance);

                $title = $feed_link.'<a class="rsswidget rss-widget-title" href="'.esc_url($link).'">'.esc_html($title).'</a>';
            }

            echo $args['before_widget'];
            if($title)
            {
                echo $args['before_title'].$title.$args['after_title'];
            }

            $format = current_theme_supports('html5', 'navigation-widgets') ? 'html5' : 'xhtml';

            $format = apply_filters('navigation_widgets_format', $format);

            if('html5' === $format)
            {
                // The title may be filtered: Strip out HTML and make sure the aria-label is never empty.
                $title = trim(strip_tags($title));
                $aria_label = $title ? $title : __('RSS Feed');
                echo '<nav aria-label="'.esc_attr($aria_label).'">';
            }

            wp_widget_rss_output($rss, $instance);

            if('html5' === $format)
            {
                echo '</nav>';
            }

            echo $args['after_widget'];

            if(! is_wp_error($rss))
            {
                $rss->__destruct();
            }
            unset($rss);
        }

        public function update($new_instance, $old_instance)
        {
            $testurl = (isset($new_instance['url']) && (! isset($old_instance['url']) || ($new_instance['url'] !== $old_instance['url'])));

            return wp_widget_rss_process($new_instance, $testurl);
        }

        public function form($instance)
        {
            parent::form($instance);
            if(empty($instance))
            {
                $instance = [
                    'title' => '',
                    'url' => '',
                    'items' => 10,
                    'error' => false,
                    'show_summary' => 0,
                    'show_author' => 0,
                    'show_date' => 0,
                ];
            }
            $instance['number'] = $this->number;

            wp_widget_rss_form($instance);
        }
    }
