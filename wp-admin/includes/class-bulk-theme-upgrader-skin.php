<?php

    class Bulk_Theme_Upgrader_Skin extends Bulk_Upgrader_Skin
    {
        public $theme_info = false;

        public function add_strings()
        {
            parent::add_strings();
            /* translators: 1: Theme name, 2: Number of the theme, 3: Total number of themes being updated. */
            $this->upgrader->strings['skin_before_update_header'] = __('Updating Theme %1$s (%2$d/%3$d)');
        }

        public function before($title = '')
        {
            parent::before($this->theme_info->display('Name'));
        }

        public function after($title = '')
        {
            parent::after($this->theme_info->display('Name'));
            $this->decrement_update_count('theme');
        }

        public function bulk_footer()
        {
            parent::bulk_footer();

            $update_actions = [
                'themes_page' => sprintf('<a href="%s" target="_parent">%s</a>', self_admin_url('themes.php'), __('Go to Themes page')),
                'updates_page' => sprintf('<a href="%s" target="_parent">%s</a>', self_admin_url('update-core.php'), __('Go to WordPress Updates page')),
            ];

            if(! current_user_can('switch_themes') && ! current_user_can('edit_theme_options'))
            {
                unset($update_actions['themes_page']);
            }

            $update_actions = apply_filters('update_bulk_theme_complete_actions', $update_actions, $this->theme_info);

            if(! empty($update_actions))
            {
                $this->feedback(implode(' | ', (array) $update_actions));
            }
        }
    }
