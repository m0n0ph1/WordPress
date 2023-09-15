<?php

    function _wp_admin_bar_init()
    {
        global $wp_admin_bar;

        if(! is_admin_bar_showing())
        {
            return false;
        }

        /* Load the admin bar class code ready for instantiation */
        require_once ABSPATH.WPINC.'/class-wp-admin-bar.php';

        /* Instantiate the admin bar */

        $admin_bar_class = apply_filters('wp_admin_bar_class', 'WP_Admin_Bar');
        if(class_exists($admin_bar_class))
        {
            $wp_admin_bar = new $admin_bar_class();
        }
        else
        {
            return false;
        }

        $wp_admin_bar->initialize();
        $wp_admin_bar->add_menus();

        return true;
    }

    function wp_admin_bar_render()
    {
        global $wp_admin_bar;
        static $rendered = false;

        if($rendered)
        {
            return;
        }

        if(! is_admin_bar_showing() || ! is_object($wp_admin_bar))
        {
            return;
        }

        do_action_ref_array('admin_bar_menu', [&$wp_admin_bar]);

        do_action('wp_before_admin_bar_render');

        $wp_admin_bar->render();

        do_action('wp_after_admin_bar_render');

        $rendered = true;
    }

    function wp_admin_bar_wp_menu($wp_admin_bar)
    {
        if(current_user_can('read'))
        {
            $about_url = self_admin_url('about.php');
            $contribute_url = self_admin_url('contribute.php');
        }
        elseif(is_multisite())
        {
            $about_url = get_dashboard_url(get_current_user_id(), 'about.php');
            $contribute_url = get_dashboard_url(get_current_user_id(), 'contribute.php');
        }
        else
        {
            $about_url = false;
            $contribute_url = false;
        }

        $wp_logo_menu_args = [
            'id' => 'wp-logo',
            'title' => '<span class="ab-icon" aria-hidden="true"></span><span class="screen-reader-text">'./* translators: Hidden accessibility text. */
                __('About WordPress').'</span>',
            'href' => $about_url,
        ];

        // Set tabindex="0" to make sub menus accessible when no URL is available.
        if(! $about_url)
        {
            $wp_logo_menu_args['meta'] = [
                'tabindex' => 0,
            ];
        }

        $wp_admin_bar->add_node($wp_logo_menu_args);

        if($about_url)
        {
            // Add "About WordPress" link.
            $wp_admin_bar->add_node([
                                        'parent' => 'wp-logo',
                                        'id' => 'about',
                                        'title' => __('About WordPress'),
                                        'href' => $about_url,
                                    ]);
        }

        if($contribute_url)
        {
            // Add contribute link.
            $wp_admin_bar->add_node([
                                        'parent' => 'wp-logo',
                                        'id' => 'contribute',
                                        'title' => __('Get Involved'),
                                        'href' => $contribute_url,
                                    ]);
        }

        // Add WordPress.org link.
        $wp_admin_bar->add_node([
                                    'parent' => 'wp-logo-external',
                                    'id' => 'wporg',
                                    'title' => __('WordPress.org'),
                                    'href' => __('https://wordpress.org/'),
                                ]);

        // Add documentation link.
        $wp_admin_bar->add_node([
                                    'parent' => 'wp-logo-external',
                                    'id' => 'documentation',
                                    'title' => __('Documentation'),
                                    'href' => __('https://wordpress.org/documentation/'),
                                ]);

        // Add forums link.
        $wp_admin_bar->add_node([
                                    'parent' => 'wp-logo-external',
                                    'id' => 'support-forums',
                                    'title' => __('Support'),
                                    'href' => __('https://wordpress.org/support/forums/'),
                                ]);

        // Add feedback link.
        $wp_admin_bar->add_node([
                                    'parent' => 'wp-logo-external',
                                    'id' => 'feedback',
                                    'title' => __('Feedback'),
                                    'href' => __('https://wordpress.org/support/forum/requests-and-feedback'),
                                ]);
    }

    function wp_admin_bar_sidebar_toggle($wp_admin_bar)
    {
        if(is_admin())
        {
            $wp_admin_bar->add_node([
                                        'id' => 'menu-toggle',
                                        'title' => '<span class="ab-icon" aria-hidden="true"></span><span class="screen-reader-text">'./* translators: Hidden accessibility text. */ __('Menu').'</span>',
                                        'href' => '#',
                                    ]);
        }
    }

    function wp_admin_bar_my_account_item($wp_admin_bar)
    {
        $user_id = get_current_user_id();
        $current_user = wp_get_current_user();

        if(! $user_id)
        {
            return;
        }

        if(current_user_can('read'))
        {
            $profile_url = get_edit_profile_url($user_id);
        }
        elseif(is_multisite())
        {
            $profile_url = get_dashboard_url($user_id, 'profile.php');
        }
        else
        {
            $profile_url = false;
        }

        $avatar = get_avatar($user_id, 26);
        /* translators: %s: Current user's display name. */
        $howdy = sprintf(__('Howdy, %s'), '<span class="display-name">'.$current_user->display_name.'</span>');
        $class = empty($avatar) ? '' : 'with-avatar';

        $wp_admin_bar->add_node([
                                    'id' => 'my-account',
                                    'parent' => 'top-secondary',
                                    'title' => $howdy.$avatar,
                                    'href' => $profile_url,
                                    'meta' => [
                                        'class' => $class,
                                    ],
                                ]);
    }

    function wp_admin_bar_my_account_menu($wp_admin_bar)
    {
        $user_id = get_current_user_id();
        $current_user = wp_get_current_user();

        if(! $user_id)
        {
            return;
        }

        if(current_user_can('read'))
        {
            $profile_url = get_edit_profile_url($user_id);
        }
        elseif(is_multisite())
        {
            $profile_url = get_dashboard_url($user_id, 'profile.php');
        }
        else
        {
            $profile_url = false;
        }

        $wp_admin_bar->add_group([
                                     'parent' => 'my-account',
                                     'id' => 'user-actions',
                                 ]);

        $user_info = get_avatar($user_id, 64);
        $user_info .= "<span class='display-name'>{$current_user->display_name}</span>";

        if($current_user->display_name !== $current_user->user_login)
        {
            $user_info .= "<span class='username'>{$current_user->user_login}</span>";
        }

        $wp_admin_bar->add_node([
                                    'parent' => 'user-actions',
                                    'id' => 'user-info',
                                    'title' => $user_info,
                                    'href' => $profile_url,
                                    'meta' => [
                                        'tabindex' => -1,
                                    ],
                                ]);

        if(false !== $profile_url)
        {
            $wp_admin_bar->add_node([
                                        'parent' => 'user-actions',
                                        'id' => 'edit-profile',
                                        'title' => __('Edit Profile'),
                                        'href' => $profile_url,
                                    ]);
        }

        $wp_admin_bar->add_node([
                                    'parent' => 'user-actions',
                                    'id' => 'logout',
                                    'title' => __('Log Out'),
                                    'href' => wp_logout_url(),
                                ]);
    }

    function wp_admin_bar_site_menu($wp_admin_bar)
    {
        // Don't show for logged out users.
        if(! is_user_logged_in())
        {
            return;
        }

        // Show only when the user is a member of this site, or they're a super admin.
        if(! is_user_member_of_blog() && ! current_user_can('manage_network'))
        {
            return;
        }

        $blogname = get_bloginfo('name');

        if(! $blogname)
        {
            $blogname = preg_replace('#^(https?://)?(www.)?#', '', get_home_url());
        }

        if(is_network_admin())
        {
            /* translators: %s: Site title. */
            $blogname = sprintf(__('Network Admin: %s'), esc_html(get_network()->site_name));
        }
        elseif(is_user_admin())
        {
            /* translators: %s: Site title. */
            $blogname = sprintf(__('User Dashboard: %s'), esc_html(get_network()->site_name));
        }

        $title = wp_html_excerpt($blogname, 40, '&hellip;');

        $wp_admin_bar->add_node([
                                    'id' => 'site-name',
                                    'title' => $title,
                                    'href' => (is_admin() || ! current_user_can('read')) ? home_url('/') : admin_url(),
                                ]);

        // Create submenu items.

        if(is_admin())
        {
            // Add an option to visit the site.
            $wp_admin_bar->add_node([
                                        'parent' => 'site-name',
                                        'id' => 'view-site',
                                        'title' => __('Visit Site'),
                                        'href' => home_url('/'),
                                    ]);

            if(is_blog_admin() && is_multisite() && current_user_can('manage_sites'))
            {
                $wp_admin_bar->add_node([
                                            'parent' => 'site-name',
                                            'id' => 'edit-site',
                                            'title' => __('Edit Site'),
                                            'href' => network_admin_url('site-info.php?id='.get_current_blog_id()),
                                        ]);
            }
        }
        elseif(current_user_can('read'))
        {
            // We're on the front end, link to the Dashboard.
            $wp_admin_bar->add_node([
                                        'parent' => 'site-name',
                                        'id' => 'dashboard',
                                        'title' => __('Dashboard'),
                                        'href' => admin_url(),
                                    ]);

            // Add the appearance submenu items.
            wp_admin_bar_appearance_menu($wp_admin_bar);
        }
    }

    function wp_admin_bar_edit_site_menu($wp_admin_bar)
    {
        global $_wp_current_template_id;

        // Don't show if a block theme is not activated.
        if(! wp_is_block_theme())
        {
            return;
        }

        // Don't show for users who can't edit theme options or when in the admin.
        if(! current_user_can('edit_theme_options') || is_admin())
        {
            return;
        }

        $wp_admin_bar->add_node([
                                    'id' => 'site-editor',
                                    'title' => __('Edit site'),
                                    'href' => add_query_arg([
                                                                'postType' => 'wp_template',
                                                                'postId' => $_wp_current_template_id,
                                                            ], admin_url('site-editor.php')),
                                ]);
    }

    function wp_admin_bar_customize_menu($wp_admin_bar)
    {
        global $wp_customize;

        // Don't show if a block theme is activated and no plugins use the customizer.
        if(wp_is_block_theme() && ! has_action('customize_register'))
        {
            return;
        }

        // Don't show for users who can't access the customizer or when in the admin.
        if(! current_user_can('customize') || is_admin())
        {
            return;
        }

        // Don't show if the user cannot edit a given customize_changeset post currently being previewed.
        if(is_customize_preview() && $wp_customize->changeset_post_id() && ! current_user_can(get_post_type_object('customize_changeset')->cap->edit_post, $wp_customize->changeset_post_id()))
        {
            return;
        }

        $current_url = (is_ssl() ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        if(is_customize_preview() && $wp_customize->changeset_uuid())
        {
            $current_url = remove_query_arg('customize_changeset_uuid', $current_url);
        }

        $customize_url = add_query_arg('url', urlencode($current_url), wp_customize_url());
        if(is_customize_preview())
        {
            $customize_url = add_query_arg(['changeset_uuid' => $wp_customize->changeset_uuid()], $customize_url);
        }

        $wp_admin_bar->add_node([
                                    'id' => 'customize',
                                    'title' => __('Customize'),
                                    'href' => $customize_url,
                                    'meta' => [
                                        'class' => 'hide-if-no-customize',
                                    ],
                                ]);
        add_action('wp_before_admin_bar_render', 'wp_customize_support_script');
    }

    function wp_admin_bar_my_sites_menu($wp_admin_bar)
    {
        // Don't show for logged out users or single site mode.
        if(! is_user_logged_in() || ! is_multisite())
        {
            return;
        }

        // Show only when the user has at least one site, or they're a super admin.
        if(count($wp_admin_bar->user->blogs) < 1 && ! current_user_can('manage_network'))
        {
            return;
        }

        if($wp_admin_bar->user->active_blog)
        {
            $my_sites_url = get_admin_url($wp_admin_bar->user->active_blog->blog_id, 'my-sites.php');
        }
        else
        {
            $my_sites_url = admin_url('my-sites.php');
        }

        $wp_admin_bar->add_node([
                                    'id' => 'my-sites',
                                    'title' => __('My Sites'),
                                    'href' => $my_sites_url,
                                ]);

        if(current_user_can('manage_network'))
        {
            $wp_admin_bar->add_group([
                                         'parent' => 'my-sites',
                                         'id' => 'my-sites-super-admin',
                                     ]);

            $wp_admin_bar->add_node([
                                        'parent' => 'my-sites-super-admin',
                                        'id' => 'network-admin',
                                        'title' => __('Network Admin'),
                                        'href' => network_admin_url(),
                                    ]);

            $wp_admin_bar->add_node([
                                        'parent' => 'network-admin',
                                        'id' => 'network-admin-d',
                                        'title' => __('Dashboard'),
                                        'href' => network_admin_url(),
                                    ]);

            if(current_user_can('manage_sites'))
            {
                $wp_admin_bar->add_node([
                                            'parent' => 'network-admin',
                                            'id' => 'network-admin-s',
                                            'title' => __('Sites'),
                                            'href' => network_admin_url('sites.php'),
                                        ]);
            }

            if(current_user_can('manage_network_users'))
            {
                $wp_admin_bar->add_node([
                                            'parent' => 'network-admin',
                                            'id' => 'network-admin-u',
                                            'title' => __('Users'),
                                            'href' => network_admin_url('users.php'),
                                        ]);
            }

            if(current_user_can('manage_network_themes'))
            {
                $wp_admin_bar->add_node([
                                            'parent' => 'network-admin',
                                            'id' => 'network-admin-t',
                                            'title' => __('Themes'),
                                            'href' => network_admin_url('themes.php'),
                                        ]);
            }

            if(current_user_can('manage_network_plugins'))
            {
                $wp_admin_bar->add_node([
                                            'parent' => 'network-admin',
                                            'id' => 'network-admin-p',
                                            'title' => __('Plugins'),
                                            'href' => network_admin_url('plugins.php'),
                                        ]);
            }

            if(current_user_can('manage_network_options'))
            {
                $wp_admin_bar->add_node([
                                            'parent' => 'network-admin',
                                            'id' => 'network-admin-o',
                                            'title' => __('Settings'),
                                            'href' => network_admin_url('settings.php'),
                                        ]);
            }
        }

        // Add site links.
        $wp_admin_bar->add_group([
                                     'parent' => 'my-sites',
                                     'id' => 'my-sites-list',
                                     'meta' => [
                                         'class' => current_user_can('manage_network') ? 'ab-sub-secondary' : '',
                                     ],
                                 ]);

        $show_site_icons = apply_filters('wp_admin_bar_show_site_icons', true);

        foreach((array) $wp_admin_bar->user->blogs as $blog)
        {
            switch_to_blog($blog->userblog_id);

            if(true === $show_site_icons && has_site_icon())
            {
                $blavatar = sprintf('<img class="blavatar" src="%s" srcset="%s 2x" alt="" width="16" height="16"%s />', esc_url(get_site_icon_url(16)), esc_url(get_site_icon_url(32)), (wp_lazy_loading_enabled('img', 'site_icon_in_toolbar') ? ' loading="lazy"' : ''));
            }
            else
            {
                $blavatar = '<div class="blavatar"></div>';
            }

            $blogname = $blog->blogname;

            if(! $blogname)
            {
                $blogname = preg_replace('#^(https?://)?(www.)?#', '', get_home_url());
            }

            $menu_id = 'blog-'.$blog->userblog_id;

            if(current_user_can('read'))
            {
                $wp_admin_bar->add_node([
                                            'parent' => 'my-sites-list',
                                            'id' => $menu_id,
                                            'title' => $blavatar.$blogname,
                                            'href' => admin_url(),
                                        ]);

                $wp_admin_bar->add_node([
                                            'parent' => $menu_id,
                                            'id' => $menu_id.'-d',
                                            'title' => __('Dashboard'),
                                            'href' => admin_url(),
                                        ]);
            }
            else
            {
                $wp_admin_bar->add_node([
                                            'parent' => 'my-sites-list',
                                            'id' => $menu_id,
                                            'title' => $blavatar.$blogname,
                                            'href' => home_url(),
                                        ]);
            }

            if(current_user_can(get_post_type_object('post')->cap->create_posts))
            {
                $wp_admin_bar->add_node([
                                            'parent' => $menu_id,
                                            'id' => $menu_id.'-n',
                                            'title' => get_post_type_object('post')->labels->new_item,
                                            'href' => admin_url('post-new.php'),
                                        ]);
            }

            if(current_user_can('edit_posts'))
            {
                $wp_admin_bar->add_node([
                                            'parent' => $menu_id,
                                            'id' => $menu_id.'-c',
                                            'title' => __('Manage Comments'),
                                            'href' => admin_url('edit-comments.php'),
                                        ]);
            }

            $wp_admin_bar->add_node([
                                        'parent' => $menu_id,
                                        'id' => $menu_id.'-v',
                                        'title' => __('Visit Site'),
                                        'href' => home_url('/'),
                                    ]);

            restore_current_blog();
        }
    }

    function wp_admin_bar_shortlink_menu($wp_admin_bar)
    {
        $short = wp_get_shortlink(0, 'query');
        $id = 'get-shortlink';

        if(empty($short))
        {
            return;
        }

        $html = '<input class="shortlink-input" type="text" readonly="readonly" value="'.esc_attr($short).'" aria-label="'.__('Shortlink').'" />';

        $wp_admin_bar->add_node([
                                    'id' => $id,
                                    'title' => __('Shortlink'),
                                    'href' => $short,
                                    'meta' => ['html' => $html],
                                ]);
    }

    function wp_admin_bar_edit_menu($wp_admin_bar)
    {
        global $tag, $wp_the_query, $user_id, $post_id;

        if(is_admin())
        {
            $current_screen = get_current_screen();
            $post = get_post();
            $post_type_object = null;

            if('post' === $current_screen->base)
            {
                $post_type_object = get_post_type_object($post->post_type);
            }
            elseif('edit' === $current_screen->base)
            {
                $post_type_object = get_post_type_object($current_screen->post_type);
            }
            elseif('edit-comments' === $current_screen->base && $post_id)
            {
                $post = get_post($post_id);
                if($post)
                {
                    $post_type_object = get_post_type_object($post->post_type);
                }
            }

            if(('post' === $current_screen->base || 'edit-comments' === $current_screen->base) && 'add' !== $current_screen->action && ($post_type_object) && current_user_can('read_post', $post->ID) && ($post_type_object->public) && ($post_type_object->show_in_admin_bar))
            {
                if('draft' === $post->post_status)
                {
                    $preview_link = get_preview_post_link($post);
                    $wp_admin_bar->add_node([
                                                'id' => 'preview',
                                                'title' => $post_type_object->labels->view_item,
                                                'href' => esc_url($preview_link),
                                                'meta' => ['target' => 'wp-preview-'.$post->ID],
                                            ]);
                }
                else
                {
                    $wp_admin_bar->add_node([
                                                'id' => 'view',
                                                'title' => $post_type_object->labels->view_item,
                                                'href' => get_permalink($post->ID),
                                            ]);
                }
            }
            elseif('edit' === $current_screen->base && ($post_type_object) && ($post_type_object->public) && ($post_type_object->show_in_admin_bar) && (get_post_type_archive_link($post_type_object->name)) && ! ('post' === $post_type_object->name && 'posts' === get_option('show_on_front')))
            {
                $wp_admin_bar->add_node([
                                            'id' => 'archive',
                                            'title' => $post_type_object->labels->view_items,
                                            'href' => get_post_type_archive_link($current_screen->post_type),
                                        ]);
            }
            elseif('term' === $current_screen->base && isset($tag) && is_object($tag) && ! is_wp_error($tag))
            {
                $tax = get_taxonomy($tag->taxonomy);
                if(is_term_publicly_viewable($tag))
                {
                    $wp_admin_bar->add_node([
                                                'id' => 'view',
                                                'title' => $tax->labels->view_item,
                                                'href' => get_term_link($tag),
                                            ]);
                }
            }
            elseif('user-edit' === $current_screen->base && isset($user_id))
            {
                $user_object = get_userdata($user_id);
                $view_link = get_author_posts_url($user_object->ID);
                if($user_object->exists() && $view_link)
                {
                    $wp_admin_bar->add_node([
                                                'id' => 'view',
                                                'title' => __('View User'),
                                                'href' => $view_link,
                                            ]);
                }
            }
        }
        else
        {
            $current_object = $wp_the_query->get_queried_object();

            if(empty($current_object))
            {
                return;
            }

            if(! empty($current_object->post_type))
            {
                $post_type_object = get_post_type_object($current_object->post_type);
                $edit_post_link = get_edit_post_link($current_object->ID);
                if($post_type_object && $edit_post_link && current_user_can('edit_post', $current_object->ID) && $post_type_object->show_in_admin_bar)
                {
                    $wp_admin_bar->add_node([
                                                'id' => 'edit',
                                                'title' => $post_type_object->labels->edit_item,
                                                'href' => $edit_post_link,
                                            ]);
                }
            }
            elseif(! empty($current_object->taxonomy))
            {
                $tax = get_taxonomy($current_object->taxonomy);
                $edit_term_link = get_edit_term_link($current_object->term_id, $current_object->taxonomy);
                if($tax && $edit_term_link && current_user_can('edit_term', $current_object->term_id))
                {
                    $wp_admin_bar->add_node([
                                                'id' => 'edit',
                                                'title' => $tax->labels->edit_item,
                                                'href' => $edit_term_link,
                                            ]);
                }
            }
            elseif($current_object instanceof WP_User && current_user_can('edit_user', $current_object->ID))
            {
                $edit_user_link = get_edit_user_link($current_object->ID);
                if($edit_user_link)
                {
                    $wp_admin_bar->add_node([
                                                'id' => 'edit',
                                                'title' => __('Edit User'),
                                                'href' => $edit_user_link,
                                            ]);
                }
            }
        }
    }

    function wp_admin_bar_new_content_menu($wp_admin_bar)
    {
        $actions = [];

        $cpts = (array) get_post_types(['show_in_admin_bar' => true], 'objects');

        if(isset($cpts['post']) && current_user_can($cpts['post']->cap->create_posts))
        {
            $actions['post-new.php'] = [$cpts['post']->labels->name_admin_bar, 'new-post'];
        }

        if(isset($cpts['attachment']) && current_user_can('upload_files'))
        {
            $actions['media-new.php'] = [$cpts['attachment']->labels->name_admin_bar, 'new-media'];
        }

        if(current_user_can('manage_links'))
        {
            $actions['link-add.php'] = [_x('Link', 'add new from admin bar'), 'new-link'];
        }

        if(isset($cpts['page']) && current_user_can($cpts['page']->cap->create_posts))
        {
            $actions['post-new.php?post_type=page'] = [$cpts['page']->labels->name_admin_bar, 'new-page'];
        }

        unset($cpts['post'], $cpts['page'], $cpts['attachment']);

        // Add any additional custom post types.
        foreach($cpts as $cpt)
        {
            if(! current_user_can($cpt->cap->create_posts))
            {
                continue;
            }

            $key = 'post-new.php?post_type='.$cpt->name;
            $actions[$key] = [$cpt->labels->name_admin_bar, 'new-'.$cpt->name];
        }
        // Avoid clash with parent node and a 'content' post type.
        if(isset($actions['post-new.php?post_type=content']))
        {
            $actions['post-new.php?post_type=content'][1] = 'add-new-content';
        }

        if(current_user_can('create_users') || (is_multisite() && current_user_can('promote_users')))
        {
            $actions['user-new.php'] = [_x('User', 'add new from admin bar'), 'new-user'];
        }

        if(! $actions)
        {
            return;
        }

        $title = '<span class="ab-icon" aria-hidden="true"></span><span class="ab-label">'._x('New', 'admin bar menu group label').'</span>';

        $wp_admin_bar->add_node([
                                    'id' => 'new-content',
                                    'title' => $title,
                                    'href' => admin_url(current(array_keys($actions))),
                                ]);

        foreach($actions as $link => $action)
        {
            [$title, $id] = $action;

            $wp_admin_bar->add_node([
                                        'parent' => 'new-content',
                                        'id' => $id,
                                        'title' => $title,
                                        'href' => admin_url($link),
                                    ]);
        }
    }

    function wp_admin_bar_comments_menu($wp_admin_bar)
    {
        if(! current_user_can('edit_posts'))
        {
            return;
        }

        $awaiting_mod = wp_count_comments();
        $awaiting_mod = $awaiting_mod->moderated;
        $awaiting_text = sprintf(/* translators: Hidden accessibility text. %s: Number of comments. */ _n('%s Comment in moderation', '%s Comments in moderation', $awaiting_mod), number_format_i18n($awaiting_mod));

        $icon = '<span class="ab-icon" aria-hidden="true"></span>';
        $title = '<span class="ab-label awaiting-mod pending-count count-'.$awaiting_mod.'" aria-hidden="true">'.number_format_i18n($awaiting_mod).'</span>';
        $title .= '<span class="screen-reader-text comments-in-moderation-text">'.$awaiting_text.'</span>';

        $wp_admin_bar->add_node([
                                    'id' => 'comments',
                                    'title' => $icon.$title,
                                    'href' => admin_url('edit-comments.php'),
                                ]);
    }

    function wp_admin_bar_appearance_menu($wp_admin_bar)
    {
        $wp_admin_bar->add_group([
                                     'parent' => 'site-name',
                                     'id' => 'appearance',
                                 ]);

        if(current_user_can('switch_themes'))
        {
            $wp_admin_bar->add_node([
                                        'parent' => 'appearance',
                                        'id' => 'themes',
                                        'title' => __('Themes'),
                                        'href' => admin_url('themes.php'),
                                    ]);
        }

        if(! current_user_can('edit_theme_options'))
        {
            return;
        }

        if(current_theme_supports('widgets'))
        {
            $wp_admin_bar->add_node([
                                        'parent' => 'appearance',
                                        'id' => 'widgets',
                                        'title' => __('Widgets'),
                                        'href' => admin_url('widgets.php'),
                                    ]);
        }

        if(current_theme_supports('menus') || current_theme_supports('widgets'))
        {
            $wp_admin_bar->add_node([
                                        'parent' => 'appearance',
                                        'id' => 'menus',
                                        'title' => __('Menus'),
                                        'href' => admin_url('nav-menus.php'),
                                    ]);
        }

        if(current_theme_supports('custom-background'))
        {
            $wp_admin_bar->add_node([
                                        'parent' => 'appearance',
                                        'id' => 'background',
                                        'title' => _x('Background', 'custom background'),
                                        'href' => admin_url('themes.php?page=custom-background'),
                                        'meta' => [
                                            'class' => 'hide-if-customize',
                                        ],
                                    ]);
        }

        if(current_theme_supports('custom-header'))
        {
            $wp_admin_bar->add_node([
                                        'parent' => 'appearance',
                                        'id' => 'header',
                                        'title' => _x('Header', 'custom image header'),
                                        'href' => admin_url('themes.php?page=custom-header'),
                                        'meta' => [
                                            'class' => 'hide-if-customize',
                                        ],
                                    ]);
        }
    }

    function wp_admin_bar_updates_menu($wp_admin_bar)
    {
        $update_data = wp_get_update_data();

        if(! $update_data['counts']['total'])
        {
            return;
        }

        $updates_text = sprintf(/* translators: Hidden accessibility text. %s: Total number of updates available. */ _n('%s update available', '%s updates available', $update_data['counts']['total']), number_format_i18n($update_data['counts']['total']));

        $icon = '<span class="ab-icon" aria-hidden="true"></span>';
        $title = '<span class="ab-label" aria-hidden="true">'.number_format_i18n($update_data['counts']['total']).'</span>';
        $title .= '<span class="screen-reader-text updates-available-text">'.$updates_text.'</span>';

        $wp_admin_bar->add_node([
                                    'id' => 'updates',
                                    'title' => $icon.$title,
                                    'href' => network_admin_url('update-core.php'),
                                ]);
    }

    function wp_admin_bar_search_menu($wp_admin_bar)
    {
        if(is_admin())
        {
            return;
        }

        $form = '<form action="'.esc_url(home_url('/')).'" method="get" id="adminbarsearch">';
        $form .= '<input class="adminbar-input" name="s" id="adminbar-search" type="text" value="" maxlength="150" />';
        $form .= '<label for="adminbar-search" class="screen-reader-text">'./* translators: Hidden accessibility text. */
            __('Search').'</label>';
        $form .= '<input type="submit" class="adminbar-button" value="'.__('Search').'" />';
        $form .= '</form>';

        $wp_admin_bar->add_node([
                                    'parent' => 'top-secondary',
                                    'id' => 'search',
                                    'title' => $form,
                                    'meta' => [
                                        'class' => 'admin-bar-search',
                                        'tabindex' => -1,
                                    ],
                                ]);
    }

    function wp_admin_bar_recovery_mode_menu($wp_admin_bar)
    {
        if(! wp_is_recovery_mode())
        {
            return;
        }

        $url = wp_login_url();
        $url = add_query_arg('action', WP_Recovery_Mode::EXIT_ACTION, $url);
        $url = wp_nonce_url($url, WP_Recovery_Mode::EXIT_ACTION);

        $wp_admin_bar->add_node([
                                    'parent' => 'top-secondary',
                                    'id' => 'recovery-mode',
                                    'title' => __('Exit Recovery Mode'),
                                    'href' => $url,
                                ]);
    }

    function wp_admin_bar_add_secondary_groups($wp_admin_bar)
    {
        $wp_admin_bar->add_group([
                                     'id' => 'top-secondary',
                                     'meta' => [
                                         'class' => 'ab-top-secondary',
                                     ],
                                 ]);

        $wp_admin_bar->add_group([
                                     'parent' => 'wp-logo',
                                     'id' => 'wp-logo-external',
                                     'meta' => [
                                         'class' => 'ab-sub-secondary',
                                     ],
                                 ]);
    }

    function wp_admin_bar_header()
    {
        $type_attr = current_theme_supports('html5', 'style') ? '' : ' type="text/css"';
        ?>
        <style<?php echo $type_attr; ?> media="print">#wpadminbar {
                display: none;
            }</style>
        <?php
    }

    function _admin_bar_bump_cb()
    {
        $type_attr = current_theme_supports('html5', 'style') ? '' : ' type="text/css"';
        ?>
        <style<?php echo $type_attr; ?> media="screen">
            html {
                margin-top: 32px !important;
            }

            @media screen and ( max-width: 782px ) {
                html {
                    margin-top: 46px !important;
                }
            }
        </style>
        <?php
    }

    function show_admin_bar($show)
    {
        global $show_admin_bar;
        $show_admin_bar = (bool) $show;
    }

    function is_admin_bar_showing()
    {
        global $show_admin_bar, $pagenow;

        // For all these types of requests, we never want an admin bar.
        if(defined('XMLRPC_REQUEST') || defined('DOING_AJAX') || defined('IFRAME_REQUEST') || wp_is_json_request())
        {
            return false;
        }

        if(is_embed())
        {
            return false;
        }

        // Integrated into the admin.
        if(is_admin())
        {
            return true;
        }

        if(! isset($show_admin_bar))
        {
            if(! is_user_logged_in() || 'wp-login.php' === $pagenow)
            {
                $show_admin_bar = false;
            }
            else
            {
                $show_admin_bar = _get_admin_bar_pref();
            }
        }

        $show_admin_bar = apply_filters('show_admin_bar', $show_admin_bar);

        return $show_admin_bar;
    }

    function _get_admin_bar_pref($context = 'front', $user = 0)
    {
        $pref = get_user_option("show_admin_bar_{$context}", $user);
        if(false === $pref)
        {
            return true;
        }

        return 'true' === $pref;
    }
