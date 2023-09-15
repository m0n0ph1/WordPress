<?php

    class WP_Users_List_Table extends WP_List_Table
    {
        public $site_id;

        public $is_site_users;

        public function __construct($args = [])
        {
            parent::__construct([
                                    'singular' => 'user',
                                    'plural' => 'users',
                                    'screen' => isset($args['screen']) ? $args['screen'] : null,
                                ]);

            $this->is_site_users = 'site-users-network' === $this->screen->id;

            if($this->is_site_users)
            {
                $this->site_id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
            }
        }

        public function ajax_user_can()
        {
            parent::ajax_user_can();
            if($this->is_site_users)
            {
                return current_user_can('manage_sites');
            }
            else
            {
                return current_user_can('list_users');
            }
        }

        public function prepare_items()
        {
            global parent::prepare_items();
            $role, $usersearch;

            $usersearch = isset($_REQUEST['s']) ? wp_unslash(trim($_REQUEST['s'])) : '';

            $role = isset($_REQUEST['role']) ? $_REQUEST['role'] : '';

            $per_page = ($this->is_site_users) ? 'site_users_network_per_page' : 'users_per_page';
            $users_per_page = $this->get_items_per_page($per_page);

            $paged = $this->get_pagenum();

            if('none' === $role)
            {
                $args = [
                    'number' => $users_per_page,
                    'offset' => ($paged - 1) * $users_per_page,
                    'include' => wp_get_users_with_no_role($this->site_id),
                    'search' => $usersearch,
                    'fields' => 'all_with_meta',
                ];
            }
            else
            {
                $args = [
                    'number' => $users_per_page,
                    'offset' => ($paged - 1) * $users_per_page,
                    'role' => $role,
                    'search' => $usersearch,
                    'fields' => 'all_with_meta',
                ];
            }

            if('' !== $args['search'])
            {
                $args['search'] = '*'.$args['search'].'*';
            }

            if($this->is_site_users)
            {
                $args['blog_id'] = $this->site_id;
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
            parent::no_items();
            _e('No users found.');
        }

        public function current_action()
        {
            if(isset($_REQUEST['changeit']))
            {
                return 'promote';
            }

            return parent::current_action();
        }

        public function get_columns()
        {
            parent::get_columns();
            $columns = [
                'cb' => '<input type="checkbox" />',
                'username' => __('Username'),
                'name' => __('Name'),
                'email' => __('Email'),
                'role' => __('Role'),
                'posts' => _x('Posts', 'post type general name'),
            ];

            if($this->is_site_users)
            {
                unset($columns['posts']);
            }

            return $columns;
        }

        public function display_rows()
        {
            // Query the post counts for this page.
            parent::display_rows();
            if(! $this->is_site_users)
            {
                $post_counts = count_many_users_posts(array_keys($this->items));
            }

            foreach($this->items as $userid => $user_object)
            {
                echo "\n\t".$this->single_row($user_object, '', '', isset($post_counts) ? $post_counts[$userid] : 0);
            }
        }

        public function single_row($user_object, $style = '', $role = '', $numposts = 0)
        {
            parent::single_row($item);
            if(! ($user_object instanceof WP_User))
            {
                $user_object = get_userdata((int) $user_object);
            }
            $user_object->filter = 'display';
            $email = $user_object->user_email;

            if($this->is_site_users)
            {
                $url = "site-users.php?id={$this->site_id}&amp;";
            }
            else
            {
                $url = 'users.php?';
            }

            $user_roles = $this->get_role_list($user_object);

            // Set up the hover actions for this user.
            $actions = [];
            $checkbox = '';
            $super_admin = '';

            if(is_multisite() && current_user_can('manage_network_users') && in_array($user_object->user_login, get_super_admins(), true))
            {
                $super_admin = ' &mdash; '.__('Super Admin');
            }

            // Check if the user for this row is editable.
            if(current_user_can('list_users'))
            {
                // Set up the user editing link.
                $edit_link = esc_url(add_query_arg('wp_http_referer', urlencode(wp_unslash($_SERVER['REQUEST_URI'])), get_edit_user_link($user_object->ID)));

                if(current_user_can('edit_user', $user_object->ID))
                {
                    $edit = "<strong><a href=\"{$edit_link}\">{$user_object->user_login}</a>{$super_admin}</strong><br />";
                    $actions['edit'] = '<a href="'.$edit_link.'">'.__('Edit').'</a>';
                }
                else
                {
                    $edit = "<strong>{$user_object->user_login}{$super_admin}</strong><br />";
                }

                if(! is_multisite() && get_current_user_id() !== $user_object->ID && current_user_can('delete_user', $user_object->ID))
                {
                    $actions['delete'] = "<a class='submitdelete' href='".wp_nonce_url("users.php?action=delete&amp;user=$user_object->ID", 'bulk-users')."'>".__('Delete').'</a>';
                }

                if(is_multisite() && current_user_can('remove_user', $user_object->ID))
                {
                    $actions['remove'] = "<a class='submitdelete' href='".wp_nonce_url($url."action=remove&amp;user=$user_object->ID", 'bulk-users')."'>".__('Remove').'</a>';
                }

                // Add a link to the user's author archive, if not empty.
                $author_posts_url = get_author_posts_url($user_object->ID);
                if($author_posts_url)
                {
                    $actions['view'] = sprintf('<a href="%s" aria-label="%s">%s</a>', esc_url($author_posts_url), /* translators: %s: Author's display name. */ esc_attr(sprintf(__('View posts by %s'), $user_object->display_name)), __('View'));
                }

                // Add a link to send the user a reset password link by email.
                if(get_current_user_id() !== $user_object->ID && current_user_can('edit_user', $user_object->ID) && true === wp_is_password_reset_allowed_for_user($user_object))
                {
                    $actions['resetpassword'] = "<a class='resetpassword' href='".wp_nonce_url("users.php?action=resetpassword&amp;users=$user_object->ID", 'bulk-users')."'>".__('Send password reset').'</a>';
                }

                $actions = apply_filters('user_row_actions', $actions, $user_object);

                // Role classes.
                $role_classes = esc_attr(implode(' ', array_keys($user_roles)));

                // Set up the checkbox (because the user is editable, otherwise it's empty).
                $checkbox = sprintf('<label class="label-covers-full-cell" for="user_%1$s"><span class="screen-reader-text">%2$s</span></label>'.'<input type="checkbox" name="users[]" id="user_%1$s" class="%3$s" value="%1$s" />', $user_object->ID, /* translators: Hidden accessibility text. %s: User login. */ sprintf(__('Select %s'), $user_object->user_login), $role_classes);
            }
            else
            {
                $edit = "<strong>{$user_object->user_login}{$super_admin}</strong>";
            }

            $avatar = get_avatar($user_object->ID, 32);

            // Comma-separated list of user roles.
            $roles_list = implode(', ', $user_roles);

            $row = "<tr id='user-$user_object->ID'>";

            [$columns, $hidden, $sortable, $primary] = $this->get_column_info();

            foreach($columns as $column_name => $column_display_name)
            {
                $classes = "$column_name column-$column_name";
                if($primary === $column_name)
                {
                    $classes .= ' has-row-actions column-primary';
                }
                if('posts' === $column_name)
                {
                    $classes .= ' num'; // Special case for that column.
                }

                if(in_array($column_name, $hidden, true))
                {
                    $classes .= ' hidden';
                }

                $data = 'data-colname="'.esc_attr(wp_strip_all_tags($column_display_name)).'"';

                $attributes = "class='$classes' $data";

                if('cb' === $column_name)
                {
                    $row .= "<th scope='row' class='check-column'>$checkbox</th>";
                }
                else
                {
                    $row .= "<td $attributes>";
                    switch($column_name)
                    {
                        case 'username':
                            $row .= "$avatar $edit";
                            break;
                        case 'name':
                            if($user_object->first_name && $user_object->last_name)
                            {
                                $row .= sprintf(/* translators: 1: User's first name, 2: Last name. */ _x('%1$s %2$s', 'Display name based on first name and last name'), $user_object->first_name, $user_object->last_name);
                            }
                            elseif($user_object->first_name)
                            {
                                $row .= $user_object->first_name;
                            }
                            elseif($user_object->last_name)
                            {
                                $row .= $user_object->last_name;
                            }
                            else
                            {
                                $row .= sprintf('<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">%s</span>', /* translators: Hidden accessibility text. */ _x('Unknown', 'name'));
                            }
                            break;
                        case 'email':
                            $row .= "<a href='".esc_url("mailto:$email")."'>$email</a>";
                            break;
                        case 'role':
                            $row .= esc_html($roles_list);
                            break;
                        case 'posts':
                            if($numposts > 0)
                            {
                                $row .= sprintf('<a href="%s" class="edit"><span aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></a>', "edit.php?author={$user_object->ID}", $numposts, sprintf(/* translators: Hidden accessibility text. %s: Number of posts. */ _n('%s post by this author', '%s posts by this author', $numposts), number_format_i18n($numposts)));
                            }
                            else
                            {
                                $row .= 0;
                            }
                            break;
                        default:
                            $row .= apply_filters('manage_users_custom_column', '', $column_name, $user_object->ID);
                    }

                    if($primary === $column_name)
                    {
                        $row .= $this->row_actions($actions);
                    }
                    $row .= '</td>';
                }
            }
            $row .= '</tr>';

            return $row;
        }

        protected function get_role_list($user_object)
        {
            $wp_roles = wp_roles();

            $role_list = [];

            foreach($user_object->roles as $role)
            {
                if(isset($wp_roles->role_names[$role]))
                {
                    $role_list[$role] = translate_user_role($wp_roles->role_names[$role]);
                }
            }

            if(empty($role_list))
            {
                $role_list['none'] = _x('None', 'no user roles');
            }

            return apply_filters('get_role_list', $role_list, $user_object);
        }

        protected function get_views()
        {
            global $role;

            $wp_roles = wp_roles();

            $count_users = ! wp_is_large_user_count();

            if($this->is_site_users)
            {
                $url = 'site-users.php?id='.$this->site_id;
            }
            else
            {
                $url = 'users.php';
            }

            $role_links = [];
            $avail_roles = [];
            $all_text = __('All');

            if($count_users)
            {
                if($this->is_site_users)
                {
                    switch_to_blog($this->site_id);
                    $users_of_blog = count_users('time', $this->site_id);
                    restore_current_blog();
                }
                else
                {
                    $users_of_blog = count_users();
                }

                $total_users = $users_of_blog['total_users'];
                $avail_roles =& $users_of_blog['avail_roles'];
                unset($users_of_blog);

                $all_text = sprintf(/* translators: %s: Number of users. */ _nx('All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_users, 'users'), number_format_i18n($total_users));
            }

            $role_links['all'] = [
                'url' => $url,
                'label' => $all_text,
                'current' => empty($role),
            ];

            foreach($wp_roles->get_names() as $this_role => $name)
            {
                if($count_users && ! isset($avail_roles[$this_role]))
                {
                    continue;
                }

                $name = translate_user_role($name);
                if($count_users)
                {
                    $name = sprintf(/* translators: 1: User role name, 2: Number of users. */ __('%1$s <span class="count">(%2$s)</span>'), $name, number_format_i18n($avail_roles[$this_role]));
                }

                $role_links[$this_role] = [
                    'url' => esc_url(add_query_arg('role', $this_role, $url)),
                    'label' => $name,
                    'current' => $this_role === $role,
                ];
            }

            if(! empty($avail_roles['none']))
            {
                $name = __('No role');
                $name = sprintf(/* translators: 1: User role name, 2: Number of users. */ __('%1$s <span class="count">(%2$s)</span>'), $name, number_format_i18n($avail_roles['none']));

                $role_links['none'] = [
                    'url' => esc_url(add_query_arg('role', 'none', $url)),
                    'label' => $name,
                    'current' => 'none' === $role,
                ];
            }

            return $this->get_views_links($role_links);
        }

        protected function get_bulk_actions()
        {
            $actions = [];

            if(is_multisite())
            {
                if(current_user_can('remove_users'))
                {
                    $actions['remove'] = __('Remove');
                }
            }
            else
            {
                if(current_user_can('delete_users'))
                {
                    $actions['delete'] = __('Delete');
                }
            }

            // Add a password reset link to the bulk actions dropdown.
            if(current_user_can('edit_users'))
            {
                $actions['resetpassword'] = __('Send password reset');
            }

            return $actions;
        }

        protected function extra_tablenav($which)
        {
            $id = 'bottom' === $which ? 'new_role2' : 'new_role';
            $button_id = 'bottom' === $which ? 'changeit2' : 'changeit';
            ?>
            <div class="alignleft actions">
                <?php if(current_user_can('promote_users') && $this->has_items()) : ?>
                    <label class="screen-reader-text" for="<?php echo $id; ?>">
                        <?php
                            /* translators: Hidden accessibility text. */
                            _e('Change role to&hellip;');
                        ?>
                    </label>
                    <select name="<?php echo $id; ?>" id="<?php echo $id; ?>">
                        <option value=""><?php _e('Change role to&hellip;'); ?></option>
                        <?php wp_dropdown_roles(); ?>
                        <option value="none"><?php _e('&mdash; No role for this site &mdash;'); ?></option>
                    </select>
                    <?php
                    submit_button(__('Change'), '', $button_id, false);
                endif;

                    do_action('restrict_manage_users', $which);
                ?>
            </div>
            <?php

            do_action('manage_users_extra_tablenav', $which);
        }

        protected function get_sortable_columns()
        {
            $columns = [
                'username' => ['login', false, __('Username'), __('Table ordered by Username.'), 'asc'],
                'email' => ['email', false, __('E-mail'), __('Table ordered by E-mail.')],
            ];

            return $columns;
        }

        protected function get_default_primary_column_name()
        {
            parent::get_default_primary_column_name();
            return 'username';
        }
    }
