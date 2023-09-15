<?php

    class WP_MS_Users_List_Table extends WP_List_Table
    {
        public function ajax_user_can()
        {
            return current_user_can('manage_network_users');
        }

        public function prepare_items()
        {
            global $mode, $usersearch, $role;

            if(! empty($_REQUEST['mode']))
            {
                $mode = 'excerpt' === $_REQUEST['mode'] ? 'excerpt' : 'list';
                set_user_setting('network_users_list_mode', $mode);
            }
            else
            {
                $mode = get_user_setting('network_users_list_mode', 'list');
            }

            $usersearch = isset($_REQUEST['s']) ? wp_unslash(trim($_REQUEST['s'])) : '';

            $users_per_page = $this->get_items_per_page('users_network_per_page');

            $role = isset($_REQUEST['role']) ? $_REQUEST['role'] : '';

            $paged = $this->get_pagenum();

            $args = [
                'number' => $users_per_page,
                'offset' => ($paged - 1) * $users_per_page,
                'search' => $usersearch,
                'blog_id' => 0,
                'fields' => 'all_with_meta',
            ];

            if(wp_is_large_network('users'))
            {
                $args['search'] = ltrim($args['search'], '*');
            }
            elseif('' !== $args['search'])
            {
                $args['search'] = trim($args['search'], '*');
                $args['search'] = '*'.$args['search'].'*';
            }

            if('super' === $role)
            {
                $args['login__in'] = get_super_admins();
            }

            /*
             * If the network is large and a search is not being performed,
             * show only the latest users with no paging in order to avoid
             * expensive count queries.
             */
            if(! $usersearch && wp_is_large_network('users'))
            {
                if(! isset($_REQUEST['orderby']))
                {
                    $_GET['orderby'] = 'id';
                    $_REQUEST['orderby'] = 'id';
                }
                if(! isset($_REQUEST['order']))
                {
                    $_GET['order'] = 'DESC';
                    $_REQUEST['order'] = 'DESC';
                }
                $args['count_total'] = false;
            }

            if(isset($_REQUEST['orderby']))
            {
                $args['orderby'] = $_REQUEST['orderby'];
            }

            if(isset($_REQUEST['order']))
            {
                $args['order'] = $_REQUEST['order'];
            }

            $args = apply_filters('users_list_table_query_args', $args);

            // Query the user IDs for this page.
            $wp_user_search = new WP_User_Query($args);

            $this->items = $wp_user_search->get_results();

            $this->set_pagination_args([
                                           'total_items' => $wp_user_search->get_total(),
                                           'per_page' => $users_per_page,
                                       ]);
        }

        public function no_items()
        {
            _e('No users found.');
        }

        public function get_columns()
        {
            $users_columns = [
                'cb' => '<input type="checkbox" />',
                'username' => __('Username'),
                'name' => __('Name'),
                'email' => __('Email'),
                'registered' => _x('Registered', 'user'),
                'blogs' => __('Sites'),
            ];

            return apply_filters('wpmu_users_columns', $users_columns);
        }

        public function column_cb($item)
        {
            // Restores the more descriptive, specific name for use within this method.
            $user = $item;

            if(is_super_admin($user->ID))
            {
                return;
            }
            ?>
            <label class="label-covers-full-cell" for="blog_<?php echo $user->ID; ?>">
			<span class="screen-reader-text">
			<?php
                /* translators: Hidden accessibility text. %s: User login. */
                printf(__('Select %s'), $user->user_login);
            ?>
			</span>
            </label>
            <input type="checkbox"
                   id="blog_<?php echo $user->ID; ?>"
                   name="allusers[]"
                   value="<?php echo esc_attr($user->ID); ?>"/>
            <?php
        }

        public function column_id($user)
        {
            echo $user->ID;
        }

        public function column_username($user)
        {
            $super_admins = get_super_admins();
            $avatar = get_avatar($user->user_email, 32);

            echo $avatar;

            if(current_user_can('edit_user', $user->ID))
            {
                $edit_link = esc_url(add_query_arg('wp_http_referer', urlencode(wp_unslash($_SERVER['REQUEST_URI'])), get_edit_user_link($user->ID)));
                $edit = "<a href=\"{$edit_link}\">{$user->user_login}</a>";
            }
            else
            {
                $edit = $user->user_login;
            }

            ?>
            <strong>
                <?php
                    echo $edit;

                    if(in_array($user->user_login, $super_admins, true))
                    {
                        echo ' &mdash; '.__('Super Admin');
                    }
                ?>
            </strong>
            <?php
        }

        public function column_name($user)
        {
            if($user->first_name && $user->last_name)
            {
                printf(/* translators: 1: User's first name, 2: Last name. */ _x('%1$s %2$s', 'Display name based on first name and last name'), $user->first_name, $user->last_name);
            }
            elseif($user->first_name)
            {
                echo $user->first_name;
            }
            elseif($user->last_name)
            {
                echo $user->last_name;
            }
            else
            {
                echo '<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">'./* translators: Hidden accessibility text. */ _x('Unknown', 'name').'</span>';
            }
        }

        public function column_email($user)
        {
            echo "<a href='".esc_url("mailto:$user->user_email")."'>$user->user_email</a>";
        }

        public function column_registered($user)
        {
            global $mode;
            if('list' === $mode)
            {
                $date = __('Y/m/d');
            }
            else
            {
                $date = __('Y/m/d g:i:s a');
            }
            echo mysql2date($date, $user->user_registered);
        }

        public function column_default($item, $column_name)
        {
            // Restores the more descriptive, specific name for use within this method.
            $user = $item;

            echo apply_filters('manage_users_custom_column', '', $column_name, $user->ID);
        }

        public function display_rows()
        {
            foreach($this->items as $user)
            {
                $class = '';

                $status_list = [
                    'spam' => 'site-spammed',
                    'deleted' => 'site-deleted',
                ];

                foreach($status_list as $status => $col)
                {
                    if($user->$status)
                    {
                        $class .= " $col";
                    }
                }

                ?>
                <tr class="<?php echo trim($class); ?>">
                    <?php $this->single_row_columns($user); ?>
                </tr>
                <?php
            }
        }

        protected function get_bulk_actions()
        {
            $actions = [];
            if(current_user_can('delete_users'))
            {
                $actions['delete'] = __('Delete');
            }
            $actions['spam'] = _x('Mark as spam', 'user');
            $actions['notspam'] = _x('Not spam', 'user');

            return $actions;
        }

        protected function get_views()
        {
            global $role;

            $total_users = get_user_count();
            $super_admins = get_super_admins();
            $total_admins = count($super_admins);

            $role_links = [];
            $role_links['all'] = [
                'url' => network_admin_url('users.php'),
                'label' => sprintf(/* translators: Number of users. */ _nx('All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_users, 'users'), number_format_i18n($total_users)),
                'current' => 'super' !== $role,
            ];

            $role_links['super'] = [
                'url' => network_admin_url('users.php?role=super'),
                'label' => sprintf(/* translators: Number of users. */ _n('Super Admin <span class="count">(%s)</span>', 'Super Admins <span class="count">(%s)</span>', $total_admins), number_format_i18n($total_admins)),
                'current' => 'super' === $role,
            ];

            return $this->get_views_links($role_links);
        }

        protected function pagination($which)
        {
            global $mode;

            parent::pagination($which);

            if('top' === $which)
            {
                $this->view_switcher($mode);
            }
        }

        protected function get_sortable_columns()
        {
            return [
                'username' => ['login', false, __('Username'), __('Table ordered by Username.'), 'asc'],
                'name' => ['name', false, __('Name'), __('Table ordered by Name.')],
                'email' => ['email', false, __('E-mail'), __('Table ordered by E-mail.')],
                'registered' => ['id', false, _x('Registered', 'user'), __('Table ordered by User Registered Date.')],
            ];
        }

        protected function _column_blogs($user, $classes, $data, $primary)
        {
            echo '<td class="', $classes, ' has-row-actions" ', $data, '>';
            echo $this->column_blogs($user);
            echo $this->handle_row_actions($user, 'blogs', $primary);
            echo '</td>';
        }

        public function column_blogs($user)
        {
            $blogs = get_blogs_of_user($user->ID, true);
            if(! is_array($blogs))
            {
                return;
            }

            foreach($blogs as $site)
            {
                if(! can_edit_network($site->site_id))
                {
                    continue;
                }

                $path = ('/' === $site->path) ? '' : $site->path;
                $site_classes = ['site-'.$site->site_id];

                $site_classes = apply_filters('ms_user_list_site_class', $site_classes, $site->userblog_id, $site->site_id, $user);
                if(is_array($site_classes) && ! empty($site_classes))
                {
                    $site_classes = array_map('sanitize_html_class', array_unique($site_classes));
                    echo '<span class="'.esc_attr(implode(' ', $site_classes)).'">';
                }
                else
                {
                    echo '<span>';
                }
                echo '<a href="'.esc_url(network_admin_url('site-info.php?id='.$site->userblog_id)).'">'.str_replace('.'.get_network()->domain, '', $site->domain.$path).'</a>';
                echo ' <small class="row-actions">';
                $actions = [];
                $actions['edit'] = '<a href="'.esc_url(network_admin_url('site-info.php?id='.$site->userblog_id)).'">'.__('Edit').'</a>';

                $class = '';
                if(1 === (int) $site->spam)
                {
                    $class .= 'site-spammed ';
                }
                if(1 === (int) $site->mature)
                {
                    $class .= 'site-mature ';
                }
                if(1 === (int) $site->deleted)
                {
                    $class .= 'site-deleted ';
                }
                if(1 === (int) $site->archived)
                {
                    $class .= 'site-archived ';
                }

                $actions['view'] = '<a class="'.$class.'" href="'.esc_url(get_home_url($site->userblog_id)).'">'.__('View').'</a>';

                $actions = apply_filters('ms_user_list_site_actions', $actions, $site->userblog_id);

                $action_count = count($actions);

                $i = 0;

                foreach($actions as $action => $link)
                {
                    ++$i;

                    $separator = ($i < $action_count) ? ' | ' : '';

                    echo "<span class='$action'>{$link}{$separator}</span>";
                }

                echo '</small></span><br />';
            }
        }

        protected function handle_row_actions($item, $column_name, $primary)
        {
            if($primary !== $column_name)
            {
                return '';
            }

            // Restores the more descriptive, specific name for use within this method.
            $user = $item;

            $super_admins = get_super_admins();
            $actions = [];

            if(current_user_can('edit_user', $user->ID))
            {
                $edit_link = esc_url(add_query_arg('wp_http_referer', urlencode(wp_unslash($_SERVER['REQUEST_URI'])), get_edit_user_link($user->ID)));
                $actions['edit'] = '<a href="'.$edit_link.'">'.__('Edit').'</a>';
            }

            if(current_user_can('delete_user', $user->ID) && ! in_array($user->user_login, $super_admins, true))
            {
                $actions['delete'] = '<a href="'.esc_url(network_admin_url(add_query_arg('_wp_http_referer', urlencode(wp_unslash($_SERVER['REQUEST_URI'])), wp_nonce_url('users.php', 'deleteuser').'&amp;action=deleteuser&amp;id='.$user->ID))).'" class="delete">'.__('Delete').'</a>';
            }

            $actions = apply_filters('ms_user_row_actions', $actions, $user);

            return $this->row_actions($actions);
        }

        protected function get_default_primary_column_name()
        {
            return 'username';
        }
    }
