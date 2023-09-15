<?php

    class Bulk_Plugin_Upgrader_Skin extends Bulk_Upgrader_Skin
    {
        public $plugin_info = [];

        public function add_strings()
        {
            parent::add_strings();
            /* translators: 1: Plugin name, 2: Number of the plugin, 3: Total number of plugins being updated. */
            $this->upgrader->strings['skin_before_update_header'] = __('Updating Plugin %1$s (%2$d/%3$d)');
        }

        public function before($title = '')
        {
            parent::before($this->plugin_info['Title']);
        }

        public function after($title = '')
        {
            parent::after($this->plugin_info['Title']);
            $this->decrement_update_count('plugin');
        }

        public function bulk_footer()
        {
            parent::bulk_footer();

            $update_actions = [
                'plugins_page' => sprintf('<a href="%s" target="_parent">%s</a>', self_admin_url('plugins.php'), __('Go to Plugins page')),
                'updates_page' => sprintf('<a href="%s" target="_parent">%s</a>', self_admin_url('update-core.php'), __('Go to WordPress Updates page')),
            ];

            if(! current_user_can('activate_plugins'))
            {
                unset($update_actions['plugins_page']);
            }

            $update_actions = apply_filters('update_bulk_plugins_complete_actions', $update_actions, $this->plugin_info);

            if(! empty($update_actions))
            {
                $this->feedback(implode(' | ', (array) $update_actions));
            }
        }
    }
