<?php

    function _get_list_table($class_name, $args = [])
    {
        $core_classes = [
            // Site Admin.
            'WP_Posts_List_Table' => 'posts',
            'WP_Media_List_Table' => 'media',
            'WP_Terms_List_Table' => 'terms',
            'WP_Users_List_Table' => 'users',
            'WP_Comments_List_Table' => 'comments',
            'WP_Post_Comments_List_Table' => ['comments', 'post-comments'],
            'WP_Links_List_Table' => 'links',
            'WP_Plugin_Install_List_Table' => 'plugin-install',
            'WP_Themes_List_Table' => 'themes',
            'WP_Theme_Install_List_Table' => ['themes', 'theme-install'],
            'WP_Plugins_List_Table' => 'plugins',
            'WP_Application_Passwords_List_Table' => 'application-passwords',

            // Network Admin.
            'WP_MS_Sites_List_Table' => 'ms-sites',
            'WP_MS_Users_List_Table' => 'ms-users',
            'WP_MS_Themes_List_Table' => 'ms-themes',

            // Privacy requests tables.
            'WP_Privacy_Data_Export_Requests_List_Table' => 'privacy-data-export-requests',
            'WP_Privacy_Data_Removal_Requests_List_Table' => 'privacy-data-removal-requests',
        ];

        if(isset($core_classes[$class_name]))
        {
            foreach((array) $core_classes[$class_name] as $required)
            {
                require_once ABSPATH.'wp-admin/includes/class-wp-'.$required.'-list-table.php';
            }

            if(isset($args['screen']))
            {
                $args['screen'] = convert_to_screen($args['screen']);
            }
            elseif(isset($GLOBALS['hook_suffix']))
            {
                $args['screen'] = get_current_screen();
            }
            else
            {
                $args['screen'] = null;
            }

            $custom_class_name = apply_filters('wp_list_table_class_name', $class_name, $args);

            if(is_string($custom_class_name) && class_exists($custom_class_name))
            {
                $class_name = $custom_class_name;
            }

            return new $class_name($args);
        }

        return false;
    }

    function register_column_headers($screen, $columns)
    {
        new _WP_List_Table_Compat($screen, $columns);
    }

    function print_column_headers($screen, $with_id = true)
    {
        $wp_list_table = new _WP_List_Table_Compat($screen);

        $wp_list_table->print_column_headers($with_id);
    }
