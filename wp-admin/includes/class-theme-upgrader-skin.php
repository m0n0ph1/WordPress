<?php

    class Theme_Upgrader_Skin extends WP_Upgrader_Skin
    {
        public $theme = '';

        public function __construct($args = [])
        {
            $defaults = [
                'url' => '',
                'theme' => '',
                'nonce' => '',
                'title' => __('Update Theme'),
            ];
            $args = wp_parse_args($args, $defaults);

            $this->theme = $args['theme'];

            parent::__construct($args);
        }

        public function after()
        {
            $this->decrement_update_count('theme');

            $update_actions = [];
            $theme_info = $this->upgrader->theme_info();
            if($theme_info)
            {
                $name = $theme_info->display('Name');
                $stylesheet = $this->upgrader->result['destination_name'];
                $template = $theme_info->get_template();

                $activate_link = add_query_arg([
                                                   'action' => 'activate',
                                                   'template' => urlencode($template),
                                                   'stylesheet' => urlencode($stylesheet),
                                               ], admin_url('themes.php'));
                $activate_link = wp_nonce_url($activate_link, 'switch-theme_'.$stylesheet);

                $customize_url = add_query_arg([
                                                   'theme' => urlencode($stylesheet),
                                                   'return' => urlencode(admin_url('themes.php')),
                                               ], admin_url('customize.php'));

                if(get_stylesheet() === $stylesheet)
                {
                    if(current_user_can('edit_theme_options') && current_user_can('customize'))
                    {
                        $update_actions['preview'] = sprintf('<a href="%s" class="hide-if-no-customize load-customize">'.'<span aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></a>', esc_url($customize_url), __('Customize'), /* translators: Hidden accessibility text. %s: Theme name. */ sprintf(__('Customize &#8220;%s&#8221;'), $name));
                    }
                }
                elseif(current_user_can('switch_themes'))
                {
                    if(current_user_can('edit_theme_options') && current_user_can('customize'))
                    {
                        $update_actions['preview'] = sprintf('<a href="%s" class="hide-if-no-customize load-customize">'.'<span aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></a>', esc_url($customize_url), __('Live Preview'), /* translators: Hidden accessibility text. %s: Theme name. */ sprintf(__('Live Preview &#8220;%s&#8221;'), $name));
                    }

                    $update_actions['activate'] = sprintf('<a href="%s" class="activatelink">'.'<span aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></a>', esc_url($activate_link), __('Activate'), /* translators: Hidden accessibility text. %s: Theme name. */ sprintf(_x('Activate &#8220;%s&#8221;', 'theme'), $name));
                }

                if(! $this->result || is_wp_error($this->result) || is_network_admin())
                {
                    unset($update_actions['preview'], $update_actions['activate']);
                }
            }

            $update_actions['themes_page'] = sprintf('<a href="%s" target="_parent">%s</a>', self_admin_url('themes.php'), __('Go to Themes page'));

            $update_actions = apply_filters('update_theme_complete_actions', $update_actions, $this->theme);

            if(! empty($update_actions))
            {
                $this->feedback(implode(' | ', (array) $update_actions));
            }
        }
    }
