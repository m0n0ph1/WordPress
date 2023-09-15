<?php /** @noinspection ALL */
    /** @noinspection ALL */

//
// Post Type registration.
//

    function create_initial_post_types()
    {
        WP_Post_Type::reset_default_labels();

        register_post_type('post', [
            'labels' => [
                'name_admin_bar' => _x('Post', 'add new from admin bar'),
            ],
            'public' => true,
            '_builtin' => true,
            /* internal use only. don't use this when registering your own post type. */
            '_edit_link' => 'post.php?post=%d',
            /* internal use only. don't use this when registering your own post type. */
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'menu_position' => 5,
            'menu_icon' => 'dashicons-admin-post',
            'hierarchical' => false,
            'rewrite' => false,
            'query_var' => false,
            'delete_with_user' => true,
            'supports' => [
                'title',
                'editor',
                'author',
                'thumbnail',
                'excerpt',
                'trackbacks',
                'custom-fields',
                'comments',
                'revisions',
                'post-formats'
            ],
            'show_in_rest' => true,
            'rest_base' => 'posts',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        ]);

        register_post_type('page', [
            'labels' => [
                'name_admin_bar' => _x('Page', 'add new from admin bar'),
            ],
            'public' => true,
            'publicly_queryable' => false,
            '_builtin' => true,
            /* internal use only. don't use this when registering your own post type. */
            '_edit_link' => 'post.php?post=%d',
            /* internal use only. don't use this when registering your own post type. */
            'capability_type' => 'page',
            'map_meta_cap' => true,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-admin-page',
            'hierarchical' => true,
            'rewrite' => false,
            'query_var' => false,
            'delete_with_user' => true,
            'supports' => [
                'title',
                'editor',
                'author',
                'thumbnail',
                'page-attributes',
                'custom-fields',
                'comments',
                'revisions'
            ],
            'show_in_rest' => true,
            'rest_base' => 'pages',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        ]);

        register_post_type('attachment', [
            'labels' => [
                'name' => _x('Media', 'post type general name'),
                'name_admin_bar' => _x('Media', 'add new from admin bar'),
                'add_new' => __('Add New Media File'),
                'edit_item' => __('Edit Media'),
                'view_item' => __('View Attachment Page'),
                'attributes' => __('Attachment Attributes'),
            ],
            'public' => true,
            'show_ui' => true,
            '_builtin' => true,
            /* internal use only. don't use this when registering your own post type. */
            '_edit_link' => 'post.php?post=%d',
            /* internal use only. don't use this when registering your own post type. */
            'capability_type' => 'post',
            'capabilities' => [
                'create_posts' => 'upload_files',
            ],
            'map_meta_cap' => true,
            'menu_icon' => 'dashicons-admin-media',
            'hierarchical' => false,
            'rewrite' => false,
            'query_var' => false,
            'show_in_nav_menus' => false,
            'delete_with_user' => true,
            'supports' => ['title', 'author', 'comments'],
            'show_in_rest' => true,
            'rest_base' => 'media',
            'rest_controller_class' => 'WP_REST_Attachments_Controller',
        ]);
        add_post_type_support('attachment:audio', 'thumbnail');
        add_post_type_support('attachment:video', 'thumbnail');

        register_post_type('revision', [
            'labels' => [
                'name' => __('Revisions'),
                'singular_name' => __('Revision'),
            ],
            'public' => false,
            '_builtin' => true,
            /* internal use only. don't use this when registering your own post type. */
            '_edit_link' => 'revision.php?revision=%d',
            /* internal use only. don't use this when registering your own post type. */
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'hierarchical' => false,
            'rewrite' => false,
            'query_var' => false,
            'can_export' => false,
            'delete_with_user' => true,
            'supports' => ['author'],
        ]);

        register_post_type('nav_menu_item', [
            'labels' => [
                'name' => __('Navigation Menu Items'),
                'singular_name' => __('Navigation Menu Item'),
            ],
            'public' => false,
            '_builtin' => true,
            /* internal use only. don't use this when registering your own post type. */ 'hierarchical' => false,
            'rewrite' => false,
            'delete_with_user' => false,
            'query_var' => false,
            'map_meta_cap' => true,
            'capability_type' => ['edit_theme_options', 'edit_theme_options'],
            'capabilities' => [
                // Meta Capabilities.
                'edit_post' => 'edit_post',
                'read_post' => 'read_post',
                'delete_post' => 'delete_post',
                // Primitive Capabilities.
                'edit_posts' => 'edit_theme_options',
                'edit_others_posts' => 'edit_theme_options',
                'delete_posts' => 'edit_theme_options',
                'publish_posts' => 'edit_theme_options',
                'read_private_posts' => 'edit_theme_options',
                'read' => 'read',
                'delete_private_posts' => 'edit_theme_options',
                'delete_published_posts' => 'edit_theme_options',
                'delete_others_posts' => 'edit_theme_options',
                'edit_private_posts' => 'edit_theme_options',
                'edit_published_posts' => 'edit_theme_options',
            ],
            'show_in_rest' => true,
            'rest_base' => 'menu-items',
            'rest_controller_class' => 'WP_REST_Menu_Items_Controller',
        ]);

        register_post_type('custom_css', [
            'labels' => [
                'name' => __('Custom CSS'),
                'singular_name' => __('Custom CSS'),
            ],
            'public' => false,
            'hierarchical' => false,
            'rewrite' => false,
            'query_var' => false,
            'delete_with_user' => false,
            'can_export' => true,
            '_builtin' => true,
            /* internal use only. don't use this when registering your own post type. */
            'supports' => ['title', 'revisions'],
            'capabilities' => [
                'delete_posts' => 'edit_theme_options',
                'delete_post' => 'edit_theme_options',
                'delete_published_posts' => 'edit_theme_options',
                'delete_private_posts' => 'edit_theme_options',
                'delete_others_posts' => 'edit_theme_options',
                'edit_post' => 'edit_css',
                'edit_posts' => 'edit_css',
                'edit_others_posts' => 'edit_css',
                'edit_published_posts' => 'edit_css',
                'read_post' => 'read',
                'read_private_posts' => 'read',
                'publish_posts' => 'edit_theme_options',
            ],
        ]);

        register_post_type('customize_changeset', [
            'labels' => [
                'name' => _x('Changesets', 'post type general name'),
                'singular_name' => _x('Changeset', 'post type singular name'),
                'add_new' => __('Add New Changeset'),
                'add_new_item' => __('Add New Changeset'),
                'new_item' => __('New Changeset'),
                'edit_item' => __('Edit Changeset'),
                'view_item' => __('View Changeset'),
                'all_items' => __('All Changesets'),
                'search_items' => __('Search Changesets'),
                'not_found' => __('No changesets found.'),
                'not_found_in_trash' => __('No changesets found in Trash.'),
            ],
            'public' => false,
            '_builtin' => true,
            /* internal use only. don't use this when registering your own post type. */ 'map_meta_cap' => true,
            'hierarchical' => false,
            'rewrite' => false,
            'query_var' => false,
            'can_export' => false,
            'delete_with_user' => false,
            'supports' => ['title', 'author'],
            'capability_type' => 'customize_changeset',
            'capabilities' => [
                'create_posts' => 'customize',
                'delete_others_posts' => 'customize',
                'delete_post' => 'customize',
                'delete_posts' => 'customize',
                'delete_private_posts' => 'customize',
                'delete_published_posts' => 'customize',
                'edit_others_posts' => 'customize',
                'edit_post' => 'customize',
                'edit_posts' => 'customize',
                'edit_private_posts' => 'customize',
                'edit_published_posts' => 'do_not_allow',
                'publish_posts' => 'customize',
                'read' => 'read',
                'read_post' => 'customize',
                'read_private_posts' => 'customize',
            ],
        ]);

        register_post_type('oembed_cache', [
            'labels' => [
                'name' => __('oEmbed Responses'),
                'singular_name' => __('oEmbed Response'),
            ],
            'public' => false,
            'hierarchical' => false,
            'rewrite' => false,
            'query_var' => false,
            'delete_with_user' => false,
            'can_export' => false,
            '_builtin' => true,
            /* internal use only. don't use this when registering your own post type. */ 'supports' => [],
        ]);

        register_post_type('user_request', [
            'labels' => [
                'name' => __('User Requests'),
                'singular_name' => __('User Request'),
            ],
            'public' => false,
            '_builtin' => true,
            /* internal use only. don't use this when registering your own post type. */ 'hierarchical' => false,
            'rewrite' => false,
            'query_var' => false,
            'can_export' => false,
            'delete_with_user' => false,
            'supports' => [],
        ]);

        register_post_type('wp_block', [
            'labels' => [
                'name' => _x('Patterns', 'post type general name'),
                'singular_name' => _x('Pattern', 'post type singular name'),
                'add_new' => __('Add New Pattern'),
                'add_new_item' => __('Add New Pattern'),
                'new_item' => __('New Pattern'),
                'edit_item' => __('Edit Block Pattern'),
                'view_item' => __('View Pattern'),
                'view_items' => __('View Patterns'),
                'all_items' => __('All Patterns'),
                'search_items' => __('Search Patterns'),
                'not_found' => __('No patterns found.'),
                'not_found_in_trash' => __('No patterns found in Trash.'),
                'filter_items_list' => __('Filter patterns list'),
                'items_list_navigation' => __('Patterns list navigation'),
                'items_list' => __('Patterns list'),
                'item_published' => __('Pattern published.'),
                'item_published_privately' => __('Pattern published privately.'),
                'item_reverted_to_draft' => __('Pattern reverted to draft.'),
                'item_scheduled' => __('Pattern scheduled.'),
                'item_updated' => __('Pattern updated.'),
            ],
            'public' => false,
            '_builtin' => true,
            /* internal use only. don't use this when registering your own post type. */ 'show_ui' => true,
            'show_in_menu' => false,
            'rewrite' => false,
            'show_in_rest' => true,
            'rest_base' => 'blocks',
            'rest_controller_class' => 'WP_REST_Blocks_Controller',
            'capability_type' => 'block',
            'capabilities' => [
                // You need to be able to edit posts, in order to read blocks in their raw form.
                'read' => 'edit_posts',
                // You need to be able to publish posts, in order to create blocks.
                'create_posts' => 'publish_posts',
                'edit_posts' => 'edit_posts',
                'edit_published_posts' => 'edit_published_posts',
                'delete_published_posts' => 'delete_published_posts',
                // Enables trashing draft posts as well.
                'delete_posts' => 'delete_posts',
                'edit_others_posts' => 'edit_others_posts',
                'delete_others_posts' => 'delete_others_posts',
            ],
            'map_meta_cap' => true,
            'supports' => [
                'title',
                'editor',
                'revisions',
                'custom-fields',
            ],
        ]);

        $template_edit_link = 'site-editor.php?'.build_query([
                                                                 'postType' => '%s',
                                                                 'postId' => '%s',
                                                                 'canvas' => 'edit',
                                                             ]);

        register_post_type('wp_template', [
            'labels' => [
                'name' => _x('Templates', 'post type general name'),
                'singular_name' => _x('Template', 'post type singular name'),
                'add_new' => __('Add New Template'),
                'add_new_item' => __('Add New Template'),
                'new_item' => __('New Template'),
                'edit_item' => __('Edit Template'),
                'view_item' => __('View Template'),
                'all_items' => __('Templates'),
                'search_items' => __('Search Templates'),
                'parent_item_colon' => __('Parent Template:'),
                'not_found' => __('No templates found.'),
                'not_found_in_trash' => __('No templates found in Trash.'),
                'archives' => __('Template archives'),
                'insert_into_item' => __('Insert into template'),
                'uploaded_to_this_item' => __('Uploaded to this template'),
                'filter_items_list' => __('Filter templates list'),
                'items_list_navigation' => __('Templates list navigation'),
                'items_list' => __('Templates list'),
            ],
            'description' => __('Templates to include in your theme.'),
            'public' => false,
            '_builtin' => true,
            /* internal use only. don't use this when registering your own post type. */
            '_edit_link' => $template_edit_link,
            /* internal use only. don't use this when registering your own post type. */
            'has_archive' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'show_in_rest' => true,
            'rewrite' => false,
            'rest_base' => 'templates',
            'rest_controller_class' => 'WP_REST_Templates_Controller',
            'capability_type' => ['template', 'templates'],
            'capabilities' => [
                'create_posts' => 'edit_theme_options',
                'delete_posts' => 'edit_theme_options',
                'delete_others_posts' => 'edit_theme_options',
                'delete_private_posts' => 'edit_theme_options',
                'delete_published_posts' => 'edit_theme_options',
                'edit_posts' => 'edit_theme_options',
                'edit_others_posts' => 'edit_theme_options',
                'edit_private_posts' => 'edit_theme_options',
                'edit_published_posts' => 'edit_theme_options',
                'publish_posts' => 'edit_theme_options',
                'read' => 'edit_theme_options',
                'read_private_posts' => 'edit_theme_options',
            ],
            'map_meta_cap' => true,
            'supports' => [
                'title',
                'slug',
                'excerpt',
                'editor',
                'revisions',
                'author',
            ],
        ]);

        register_post_type('wp_template_part', [
            'labels' => [
                'name' => _x('Template Parts', 'post type general name'),
                'singular_name' => _x('Template Part', 'post type singular name'),
                'add_new' => __('Add New Template Part'),
                'add_new_item' => __('Add New Template Part'),
                'new_item' => __('New Template Part'),
                'edit_item' => __('Edit Template Part'),
                'view_item' => __('View Template Part'),
                'all_items' => __('Template Parts'),
                'search_items' => __('Search Template Parts'),
                'parent_item_colon' => __('Parent Template Part:'),
                'not_found' => __('No template parts found.'),
                'not_found_in_trash' => __('No template parts found in Trash.'),
                'archives' => __('Template part archives'),
                'insert_into_item' => __('Insert into template part'),
                'uploaded_to_this_item' => __('Uploaded to this template part'),
                'filter_items_list' => __('Filter template parts list'),
                'items_list_navigation' => __('Template parts list navigation'),
                'items_list' => __('Template parts list'),
            ],
            'description' => __('Template parts to include in your templates.'),
            'public' => false,
            '_builtin' => true,
            /* internal use only. don't use this when registering your own post type. */
            '_edit_link' => $template_edit_link,
            /* internal use only. don't use this when registering your own post type. */
            'has_archive' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'show_in_rest' => true,
            'rewrite' => false,
            'rest_base' => 'template-parts',
            'rest_controller_class' => 'WP_REST_Templates_Controller',
            'map_meta_cap' => true,
            'capabilities' => [
                'create_posts' => 'edit_theme_options',
                'delete_posts' => 'edit_theme_options',
                'delete_others_posts' => 'edit_theme_options',
                'delete_private_posts' => 'edit_theme_options',
                'delete_published_posts' => 'edit_theme_options',
                'edit_posts' => 'edit_theme_options',
                'edit_others_posts' => 'edit_theme_options',
                'edit_private_posts' => 'edit_theme_options',
                'edit_published_posts' => 'edit_theme_options',
                'publish_posts' => 'edit_theme_options',
                'read' => 'edit_theme_options',
                'read_private_posts' => 'edit_theme_options',
            ],
            'supports' => [
                'title',
                'slug',
                'excerpt',
                'editor',
                'revisions',
                'author',
            ],
        ]);

        register_post_type('wp_global_styles', [
            'label' => _x('Global Styles', 'post type general name'),
            'description' => __('Global styles to include in themes.'),
            'public' => false,
            '_builtin' => true,
            /* internal use only. don't use this when registering your own post type. */
            '_edit_link' => '/site-editor.php?canvas=edit',
            /* internal use only. don't use this when registering your own post type. */
            'show_ui' => false,
            'show_in_rest' => false,
            'rewrite' => false,
            'capabilities' => [
                'read' => 'edit_theme_options',
                'create_posts' => 'edit_theme_options',
                'edit_posts' => 'edit_theme_options',
                'edit_published_posts' => 'edit_theme_options',
                'delete_published_posts' => 'edit_theme_options',
                'edit_others_posts' => 'edit_theme_options',
                'delete_others_posts' => 'edit_theme_options',
            ],
            'map_meta_cap' => true,
            'supports' => [
                'title',
                'editor',
                'revisions',
            ],
        ]);

        $navigation_post_edit_link = 'site-editor.php?'.build_query([
                                                                        'postId' => '%s',
                                                                        'postType' => 'wp_navigation',
                                                                        'canvas' => 'edit',
                                                                    ]);

        register_post_type('wp_navigation', [
            'labels' => [
                'name' => _x('Navigation Menus', 'post type general name'),
                'singular_name' => _x('Navigation Menu', 'post type singular name'),
                'add_new' => __('Add New Navigation Menu'),
                'add_new_item' => __('Add New Navigation Menu'),
                'new_item' => __('New Navigation Menu'),
                'edit_item' => __('Edit Navigation Menu'),
                'view_item' => __('View Navigation Menu'),
                'all_items' => __('Navigation Menus'),
                'search_items' => __('Search Navigation Menus'),
                'parent_item_colon' => __('Parent Navigation Menu:'),
                'not_found' => __('No Navigation Menu found.'),
                'not_found_in_trash' => __('No Navigation Menu found in Trash.'),
                'archives' => __('Navigation Menu archives'),
                'insert_into_item' => __('Insert into Navigation Menu'),
                'uploaded_to_this_item' => __('Uploaded to this Navigation Menu'),
                'filter_items_list' => __('Filter Navigation Menu list'),
                'items_list_navigation' => __('Navigation Menus list navigation'),
                'items_list' => __('Navigation Menus list'),
            ],
            'description' => __('Navigation menus that can be inserted into your site.'),
            'public' => false,
            '_builtin' => true,
            /* internal use only. don't use this when registering your own post type. */
            '_edit_link' => $navigation_post_edit_link,
            /* internal use only. don't use this when registering your own post type. */
            'has_archive' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'show_in_admin_bar' => false,
            'show_in_rest' => true,
            'rewrite' => false,
            'map_meta_cap' => true,
            'capabilities' => [
                'edit_others_posts' => 'edit_theme_options',
                'delete_posts' => 'edit_theme_options',
                'publish_posts' => 'edit_theme_options',
                'create_posts' => 'edit_theme_options',
                'read_private_posts' => 'edit_theme_options',
                'delete_private_posts' => 'edit_theme_options',
                'delete_published_posts' => 'edit_theme_options',
                'delete_others_posts' => 'edit_theme_options',
                'edit_private_posts' => 'edit_theme_options',
                'edit_published_posts' => 'edit_theme_options',
                'edit_posts' => 'edit_theme_options',
            ],
            'rest_base' => 'navigation',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'supports' => [
                'title',
                'editor',
                'revisions',
            ],
        ]);

        register_post_status('publish', [
            'label' => _x('Published', 'post status'),
            'public' => true,
            '_builtin' => true,
            /* internal use only. */
            /* translators: %s: Number of published posts. */
            'label_count' => _n_noop('Published <span class="count">(%s)</span>', 'Published <span class="count">(%s)</span>'),
        ]);

        register_post_status('future', [
            'label' => _x('Scheduled', 'post status'),
            'protected' => true,
            '_builtin' => true,
            /* internal use only. */
            /* translators: %s: Number of scheduled posts. */
            'label_count' => _n_noop('Scheduled <span class="count">(%s)</span>', 'Scheduled <span class="count">(%s)</span>'),
        ]);

        register_post_status('draft', [
            'label' => _x('Draft', 'post status'),
            'protected' => true,
            '_builtin' => true,
            /* internal use only. */
            /* translators: %s: Number of draft posts. */
            'label_count' => _n_noop('Draft <span class="count">(%s)</span>', 'Drafts <span class="count">(%s)</span>'),
            'date_floating' => true,
        ]);

        register_post_status('pending', [
            'label' => _x('Pending', 'post status'),
            'protected' => true,
            '_builtin' => true,
            /* internal use only. */
            /* translators: %s: Number of pending posts. */
            'label_count' => _n_noop('Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>'),
            'date_floating' => true,
        ]);

        register_post_status('private', [
            'label' => _x('Private', 'post status'),
            'private' => true,
            '_builtin' => true,
            /* internal use only. */
            /* translators: %s: Number of private posts. */
            'label_count' => _n_noop('Private <span class="count">(%s)</span>', 'Private <span class="count">(%s)</span>'),
        ]);

        register_post_status('trash', [
            'label' => _x('Trash', 'post status'),
            'internal' => true,
            '_builtin' => true,
            /* internal use only. */
            /* translators: %s: Number of trashed posts. */
            'label_count' => _n_noop('Trash <span class="count">(%s)</span>', 'Trash <span class="count">(%s)</span>'),
            'show_in_admin_status_list' => true,
        ]);

        register_post_status('auto-draft', [
            'label' => 'auto-draft',
            'internal' => true,
            '_builtin' => true, /* internal use only. */ 'date_floating' => true,
        ]);

        register_post_status('inherit', [
            'label' => 'inherit',
            'internal' => true,
            '_builtin' => true, /* internal use only. */ 'exclude_from_search' => false,
        ]);

        register_post_status('request-pending', [
            'label' => _x('Pending', 'request status'),
            'internal' => true,
            '_builtin' => true,
            /* internal use only. */
            /* translators: %s: Number of pending requests. */
            'label_count' => _n_noop('Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>'),
            'exclude_from_search' => false,
        ]);

        register_post_status('request-confirmed', [
            'label' => _x('Confirmed', 'request status'),
            'internal' => true,
            '_builtin' => true,
            /* internal use only. */
            /* translators: %s: Number of confirmed requests. */
            'label_count' => _n_noop('Confirmed <span class="count">(%s)</span>', 'Confirmed <span class="count">(%s)</span>'),
            'exclude_from_search' => false,
        ]);

        register_post_status('request-failed', [
            'label' => _x('Failed', 'request status'),
            'internal' => true,
            '_builtin' => true,
            /* internal use only. */
            /* translators: %s: Number of failed requests. */
            'label_count' => _n_noop('Failed <span class="count">(%s)</span>', 'Failed <span class="count">(%s)</span>'),
            'exclude_from_search' => false,
        ]);

        register_post_status('request-completed', [
            'label' => _x('Completed', 'request status'),
            'internal' => true,
            '_builtin' => true,
            /* internal use only. */
            /* translators: %s: Number of completed requests. */
            'label_count' => _n_noop('Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>'),
            'exclude_from_search' => false,
        ]);
    }

    function get_attached_file($attachment_id, $unfiltered = false)
    {
        $file = get_post_meta($attachment_id, '_wp_attached_file', true);

        // If the file is relative, prepend upload dir.
        if($file && ! str_starts_with($file, '/') && ! preg_match('|^.:\\\|', $file))
        {
            $uploads = wp_get_upload_dir();
            if(false === $uploads['error'])
            {
                $file = $uploads['basedir']."/$file";
            }
        }

        if($unfiltered)
        {
            return $file;
        }

        return apply_filters('get_attached_file', $file, $attachment_id);
    }

    function update_attached_file($attachment_id, $file)
    {
        if(! get_post($attachment_id))
        {
            return false;
        }

        $file = apply_filters('update_attached_file', $file, $attachment_id);

        $file = _wp_relative_upload_path($file);
        if($file)
        {
            return update_post_meta($attachment_id, '_wp_attached_file', $file);
        }
        else
        {
            return delete_post_meta($attachment_id, '_wp_attached_file');
        }
    }

    function _wp_relative_upload_path($path)
    {
        $new_path = $path;

        $uploads = wp_get_upload_dir();
        if(str_starts_with($new_path, $uploads['basedir']))
        {
            $new_path = str_replace($uploads['basedir'], '', $new_path);
            $new_path = ltrim($new_path, '/');
        }

        return apply_filters('_wp_relative_upload_path', $new_path, $path);
    }

    function get_children($args = '', $output = OBJECT)
    {
        $kids = [];
        if(empty($args))
        {
            if(isset($GLOBALS['post']))
            {
                $args = ['post_parent' => (int) $GLOBALS['post']->post_parent];
            }
            else
            {
                return $kids;
            }
        }
        elseif(is_object($args))
        {
            $args = ['post_parent' => (int) $args->post_parent];
        }
        elseif(is_numeric($args))
        {
            $args = ['post_parent' => (int) $args];
        }

        $defaults = [
            'numberposts' => -1,
            'post_type' => 'any',
            'post_status' => 'any',
            'post_parent' => 0,
        ];

        $parsed_args = wp_parse_args($args, $defaults);

        $children = get_posts($parsed_args);

        if(! $children)
        {
            return $kids;
        }

        if(! empty($parsed_args['fields']))
        {
            return $children;
        }

        update_post_cache($children);

        foreach($children as $key => $child)
        {
            $kids[$child->ID] = $children[$key];
        }

        if(OBJECT === $output)
        {
            return $kids;
        }
        elseif(ARRAY_A === $output)
        {
            $weeuns = [];
            foreach((array) $kids as $kid)
            {
                $weeuns[$kid->ID] = get_object_vars($kids[$kid->ID]);
            }

            return $weeuns;
        }
        elseif(ARRAY_N === $output)
        {
            $babes = [];
            foreach((array) $kids as $kid)
            {
                $babes[$kid->ID] = array_values(get_object_vars($kids[$kid->ID]));
            }

            return $babes;
        }
        else
        {
            return $kids;
        }
    }

    function get_extended($post)
    {
        // Match the new style more links.
        if(preg_match('/<!--more(.*?)?-->/', $post, $matches))
        {
            [$main, $extended] = explode($matches[0], $post, 2);
            $more_text = $matches[1];
        }
        else
        {
            $main = $post;
            $extended = '';
            $more_text = '';
        }

        // Leading and trailing whitespace.
        $main = preg_replace('/^[\s]*(.*)[\s]*$/', '\\1', $main);
        $extended = preg_replace('/^[\s]*(.*)[\s]*$/', '\\1', $extended);
        $more_text = preg_replace('/^[\s]*(.*)[\s]*$/', '\\1', $more_text);

        return [
            'main' => $main,
            'extended' => $extended,
            'more_text' => $more_text,
        ];
    }

    function get_post($post = null, $output = OBJECT, $filter = 'raw')
    {
        if(empty($post) && isset($GLOBALS['post']))
        {
            $post = $GLOBALS['post'];
        }

        if($post instanceof WP_Post)
        {
            $_post = $post;
        }
        elseif(is_object($post))
        {
            if(empty($post->filter))
            {
                $_post = sanitize_post($post, 'raw');
                $_post = new WP_Post($_post);
            }
            elseif('raw' === $post->filter)
            {
                $_post = new WP_Post($post);
            }
            else
            {
                $_post = WP_Post::get_instance($post->ID);
            }
        }
        else
        {
            $_post = WP_Post::get_instance($post);
        }

        if(! $_post)
        {
            return null;
        }

        $_post = $_post->filter($filter);

        if(ARRAY_A === $output)
        {
            return $_post->to_array();
        }
        elseif(ARRAY_N === $output)
        {
            return array_values($_post->to_array());
        }

        return $_post;
    }

    function get_post_ancestors($post)
    {
        $post = get_post($post);

        if(! $post || empty($post->post_parent) || $post->post_parent == $post->ID)
        {
            return [];
        }

        $ancestors = [];

        $id = $post->post_parent;
        $ancestors[] = $id;

        while($ancestor = get_post($id))
        {
            // Loop detection: If the ancestor has been seen before, break.
            if(empty($ancestor->post_parent) || ($ancestor->post_parent == $post->ID) || in_array($ancestor->post_parent, $ancestors, true))
            {
                break;
            }

            $id = $ancestor->post_parent;
            $ancestors[] = $id;
        }

        return $ancestors;
    }

    function get_post_field($field, $post = null, $context = 'display')
    {
        $post = get_post($post);

        if(! $post || ! isset($post->$field))
        {
            return '';
        }

        return sanitize_post_field($field, $post->$field, $post->ID, $context);
    }

    function get_post_mime_type($post = null)
    {
        $post = get_post($post);

        if(is_object($post))
        {
            return $post->post_mime_type;
        }

        return false;
    }

    function get_post_status($post = null)
    {
        $post = get_post($post);

        if(! is_object($post))
        {
            return false;
        }

        $post_status = $post->post_status;

        if('attachment' === $post->post_type && 'inherit' === $post_status)
        {
            if(0 === $post->post_parent || ! get_post($post->post_parent) || $post->ID === $post->post_parent)
            {
                // Unattached attachments with inherit status are assumed to be published.
                $post_status = 'publish';
            }
            elseif('trash' === get_post_status($post->post_parent))
            {
                // Get parent status prior to trashing.
                $post_status = get_post_meta($post->post_parent, '_wp_trash_meta_status', true);

                if(! $post_status)
                {
                    // Assume publish as above.
                    $post_status = 'publish';
                }
            }
            else
            {
                $post_status = get_post_status($post->post_parent);
            }
        }
        elseif(
            'attachment' === $post->post_type && ! in_array($post_status, [
                'private',
                'trash',
                'auto-draft'
            ],                                              true)
        )
        {
            /*
		 * Ensure uninherited attachments have a permitted status either 'private', 'trash', 'auto-draft'.
		 * This is to match the logic in wp_insert_post().
		 *
		 * Note: 'inherit' is excluded from this check as it is resolved to the parent post's
		 * status in the logic block above.
		 */
            $post_status = 'publish';
        }

        return apply_filters('get_post_status', $post_status, $post);
    }

    function get_post_statuses()
    {
        $status = [
            'draft' => __('Draft'),
            'pending' => __('Pending Review'),
            'private' => __('Private'),
            'publish' => __('Published'),
        ];

        return $status;
    }

    function get_page_statuses()
    {
        $status = [
            'draft' => __('Draft'),
            'private' => __('Private'),
            'publish' => __('Published'),
        ];

        return $status;
    }

    function _wp_privacy_statuses()
    {
        return [
            'request-pending' => _x('Pending', 'request status'),      // Pending confirmation from user.
            'request-confirmed' => _x('Confirmed', 'request status'),    // User has confirmed the action.
            'request-failed' => _x('Failed', 'request status'),       // User failed to confirm the action.
            'request-completed' => _x('Completed', 'request status'),    // Admin has handled the request.
        ];
    }

    function register_post_status($post_status, $args = [])
    {
        global $wp_post_statuses;

        if(! is_array($wp_post_statuses))
        {
            $wp_post_statuses = [];
        }

        // Args prefixed with an underscore are reserved for internal use.
        $defaults = [
            'label' => false,
            'label_count' => false,
            'exclude_from_search' => null,
            '_builtin' => false,
            'public' => null,
            'internal' => null,
            'protected' => null,
            'private' => null,
            'publicly_queryable' => null,
            'show_in_admin_status_list' => null,
            'show_in_admin_all_list' => null,
            'date_floating' => null,
        ];
        $args = wp_parse_args($args, $defaults);
        $args = (object) $args;

        $post_status = sanitize_key($post_status);
        $args->name = $post_status;

        // Set various defaults.
        if(null === $args->public && null === $args->internal && null === $args->protected && null === $args->private)
        {
            $args->internal = true;
        }

        if(null === $args->public)
        {
            $args->public = false;
        }

        if(null === $args->private)
        {
            $args->private = false;
        }

        if(null === $args->protected)
        {
            $args->protected = false;
        }

        if(null === $args->internal)
        {
            $args->internal = false;
        }

        if(null === $args->publicly_queryable)
        {
            $args->publicly_queryable = $args->public;
        }

        if(null === $args->exclude_from_search)
        {
            $args->exclude_from_search = $args->internal;
        }

        if(null === $args->show_in_admin_all_list)
        {
            $args->show_in_admin_all_list = ! $args->internal;
        }

        if(null === $args->show_in_admin_status_list)
        {
            $args->show_in_admin_status_list = ! $args->internal;
        }

        if(null === $args->date_floating)
        {
            $args->date_floating = false;
        }

        if(false === $args->label)
        {
            $args->label = $post_status;
        }

        if(false === $args->label_count)
        {
            // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingle,WordPress.WP.I18n.NonSingularStringLiteralPlural
            $args->label_count = _n_noop($args->label, $args->label);
        }

        $wp_post_statuses[$post_status] = $args;

        return $args;
    }

    function get_post_status_object($post_status)
    {
        global $wp_post_statuses;

        if(empty($wp_post_statuses[$post_status]))
        {
            return null;
        }

        return $wp_post_statuses[$post_status];
    }

    function get_post_stati($args = [], $output = 'names', $operator = 'and')
    {
        global $wp_post_statuses;

        $field = ('names' === $output) ? 'name' : false;

        return wp_filter_object_list($wp_post_statuses, $args, $operator, $field);
    }

    function is_post_type_hierarchical($post_type)
    {
        if(! post_type_exists($post_type))
        {
            return false;
        }

        $post_type = get_post_type_object($post_type);

        return $post_type->hierarchical;
    }

    function post_type_exists($post_type)
    {
        return (bool) get_post_type_object($post_type);
    }

    function get_post_type($post = null)
    {
        $post = get_post($post);
        if($post)
        {
            return $post->post_type;
        }

        return false;
    }

    function get_post_type_object($post_type)
    {
        global $wp_post_types;

        if(! is_scalar($post_type) || empty($wp_post_types[$post_type]))
        {
            return null;
        }

        return $wp_post_types[$post_type];
    }

    function get_post_types($args = [], $output = 'names', $operator = 'and')
    {
        global $wp_post_types;

        $field = ('names' === $output) ? 'name' : false;

        return wp_filter_object_list($wp_post_types, $args, $operator, $field);
    }

    function register_post_type($post_type, $args = [])
    {
        global $wp_post_types;

        if(! is_array($wp_post_types))
        {
            $wp_post_types = [];
        }

        // Sanitize post type name.
        $post_type = sanitize_key($post_type);

        if(empty($post_type) || strlen($post_type) > 20)
        {
            _doing_it_wrong(__FUNCTION__, __('Post type names must be between 1 and 20 characters in length.'), '4.2.0');

            return new WP_Error('post_type_length_invalid', __('Post type names must be between 1 and 20 characters in length.'));
        }

        $post_type_object = new WP_Post_Type($post_type, $args);
        $post_type_object->add_supports();
        $post_type_object->add_rewrite_rules();
        $post_type_object->register_meta_boxes();

        $wp_post_types[$post_type] = $post_type_object;

        $post_type_object->add_hooks();
        $post_type_object->register_taxonomies();

        do_action('registered_post_type', $post_type, $post_type_object);

        do_action("registered_post_type_{$post_type}", $post_type, $post_type_object);

        return $post_type_object;
    }

    function unregister_post_type($post_type)
    {
        global $wp_post_types;

        if(! post_type_exists($post_type))
        {
            return new WP_Error('invalid_post_type', __('Invalid post type.'));
        }

        $post_type_object = get_post_type_object($post_type);

        // Do not allow unregistering internal post types.
        if($post_type_object->_builtin)
        {
            return new WP_Error('invalid_post_type', __('Unregistering a built-in post type is not allowed'));
        }

        $post_type_object->remove_supports();
        $post_type_object->remove_rewrite_rules();
        $post_type_object->unregister_meta_boxes();
        $post_type_object->remove_hooks();
        $post_type_object->unregister_taxonomies();

        unset($wp_post_types[$post_type]);

        do_action('unregistered_post_type', $post_type);

        return true;
    }

    function get_post_type_capabilities($args)
    {
        if(! is_array($args->capability_type))
        {
            $args->capability_type = [$args->capability_type, $args->capability_type.'s'];
        }

        // Singular base for meta capabilities, plural base for primitive capabilities.
        [$singular_base, $plural_base] = $args->capability_type;

        $default_capabilities = [
            // Meta capabilities.
            'edit_post' => 'edit_'.$singular_base,
            'read_post' => 'read_'.$singular_base,
            'delete_post' => 'delete_'.$singular_base,
            // Primitive capabilities used outside of map_meta_cap():
            'edit_posts' => 'edit_'.$plural_base,
            'edit_others_posts' => 'edit_others_'.$plural_base,
            'delete_posts' => 'delete_'.$plural_base,
            'publish_posts' => 'publish_'.$plural_base,
            'read_private_posts' => 'read_private_'.$plural_base,
        ];

        // Primitive capabilities used within map_meta_cap():
        if($args->map_meta_cap)
        {
            $default_capabilities_for_mapping = [
                'read' => 'read',
                'delete_private_posts' => 'delete_private_'.$plural_base,
                'delete_published_posts' => 'delete_published_'.$plural_base,
                'delete_others_posts' => 'delete_others_'.$plural_base,
                'edit_private_posts' => 'edit_private_'.$plural_base,
                'edit_published_posts' => 'edit_published_'.$plural_base,
            ];
            $default_capabilities = array_merge($default_capabilities, $default_capabilities_for_mapping);
        }

        $capabilities = array_merge($default_capabilities, $args->capabilities);

        // Post creation capability simply maps to edit_posts by default:
        if(! isset($capabilities['create_posts']))
        {
            $capabilities['create_posts'] = $capabilities['edit_posts'];
        }

        // Remember meta capabilities for future reference.
        if($args->map_meta_cap)
        {
            _post_type_meta_capabilities($capabilities);
        }

        return (object) $capabilities;
    }

    function _post_type_meta_capabilities($capabilities = null)
    {
        global $post_type_meta_caps;

        foreach($capabilities as $core => $custom)
        {
            if(in_array($core, ['read_post', 'delete_post', 'edit_post'], true))
            {
                $post_type_meta_caps[$custom] = $core;
            }
        }
    }

    function get_post_type_labels($post_type_object)
    {
        $nohier_vs_hier_defaults = WP_Post_Type::get_default_labels();

        $nohier_vs_hier_defaults['menu_name'] = $nohier_vs_hier_defaults['name'];

        $labels = _get_custom_object_labels($post_type_object, $nohier_vs_hier_defaults);

        $post_type = $post_type_object->name;

        $default_labels = clone $labels;

        $labels = apply_filters("post_type_labels_{$post_type}", $labels);

        // Ensure that the filtered labels contain all required default values.
        $labels = (object) array_merge((array) $default_labels, (array) $labels);

        return $labels;
    }

    function _get_custom_object_labels($data_object, $nohier_vs_hier_defaults)
    {
        $data_object->labels = (array) $data_object->labels;

        if(isset($data_object->label) && empty($data_object->labels['name']))
        {
            $data_object->labels['name'] = $data_object->label;
        }

        if(! isset($data_object->labels['singular_name']) && isset($data_object->labels['name']))
        {
            $data_object->labels['singular_name'] = $data_object->labels['name'];
        }

        if(! isset($data_object->labels['name_admin_bar']))
        {
            $data_object->labels['name_admin_bar'] = isset($data_object->labels['singular_name']) ? $data_object->labels['singular_name'] : $data_object->name;
        }

        if(! isset($data_object->labels['menu_name']) && isset($data_object->labels['name']))
        {
            $data_object->labels['menu_name'] = $data_object->labels['name'];
        }

        if(! isset($data_object->labels['all_items']) && isset($data_object->labels['menu_name']))
        {
            $data_object->labels['all_items'] = $data_object->labels['menu_name'];
        }

        if(! isset($data_object->labels['archives']) && isset($data_object->labels['all_items']))
        {
            $data_object->labels['archives'] = $data_object->labels['all_items'];
        }

        $defaults = [];
        foreach($nohier_vs_hier_defaults as $key => $value)
        {
            $defaults[$key] = $data_object->hierarchical ? $value[1] : $value[0];
        }

        $labels = array_merge($defaults, $data_object->labels);
        $data_object->labels = (object) $data_object->labels;

        return (object) $labels;
    }

    function _add_post_type_submenus()
    {
        foreach(get_post_types(['show_ui' => true]) as $ptype)
        {
            $ptype_obj = get_post_type_object($ptype);
            // Sub-menus only.
            if(! $ptype_obj->show_in_menu || true === $ptype_obj->show_in_menu)
            {
                continue;
            }
            add_submenu_page($ptype_obj->show_in_menu, $ptype_obj->labels->name, $ptype_obj->labels->all_items, $ptype_obj->cap->edit_posts, "edit.php?post_type=$ptype");
        }
    }

    function add_post_type_support($post_type, $feature, ...$args)
    {
        global $_wp_post_type_features;

        $features = (array) $feature;
        foreach($features as $feature)
        {
            if($args)
            {
                $_wp_post_type_features[$post_type][$feature] = $args;
            }
            else
            {
                $_wp_post_type_features[$post_type][$feature] = true;
            }
        }
    }

    function remove_post_type_support($post_type, $feature)
    {
        global $_wp_post_type_features;

        unset($_wp_post_type_features[$post_type][$feature]);
    }

    function get_all_post_type_supports($post_type)
    {
        global $_wp_post_type_features;

        if(isset($_wp_post_type_features[$post_type]))
        {
            return $_wp_post_type_features[$post_type];
        }

        return [];
    }

    function post_type_supports($post_type, $feature)
    {
        global $_wp_post_type_features;

        return (isset($_wp_post_type_features[$post_type][$feature]));
    }

    function get_post_types_by_support($feature, $operator = 'and')
    {
        global $_wp_post_type_features;

        $features = array_fill_keys((array) $feature, true);

        return array_keys(wp_filter_object_list($_wp_post_type_features, $features, $operator));
    }

    function set_post_type($post_id = 0, $post_type = 'post')
    {
        global $wpdb;

        $post_type = sanitize_post_field('post_type', $post_type, $post_id, 'db');
        $return = $wpdb->update($wpdb->posts, ['post_type' => $post_type], ['ID' => $post_id]);

        clean_post_cache($post_id);

        return $return;
    }

    function is_post_type_viewable($post_type)
    {
        if(is_scalar($post_type))
        {
            $post_type = get_post_type_object($post_type);

            if(! $post_type)
            {
                return false;
            }
        }

        if(! is_object($post_type))
        {
            return false;
        }

        $is_viewable = $post_type->publicly_queryable || ($post_type->_builtin && $post_type->public);

        return true === apply_filters('is_post_type_viewable', $is_viewable, $post_type);
    }

    function is_post_status_viewable($post_status)
    {
        if(is_scalar($post_status))
        {
            $post_status = get_post_status_object($post_status);

            if(! $post_status)
            {
                return false;
            }
        }

        if(! is_object($post_status) || $post_status->internal || $post_status->protected)
        {
            return false;
        }

        $is_viewable = $post_status->publicly_queryable || ($post_status->_builtin && $post_status->public);

        return true === apply_filters('is_post_status_viewable', $is_viewable, $post_status);
    }

    function is_post_publicly_viewable($post = null)
    {
        $post = get_post($post);

        if(! $post)
        {
            return false;
        }

        $post_type = get_post_type($post);
        $post_status = get_post_status($post);

        return is_post_type_viewable($post_type) && is_post_status_viewable($post_status);
    }

    function get_posts($args = null)
    {
        $defaults = [
            'numberposts' => 5,
            'category' => 0,
            'orderby' => 'date',
            'order' => 'DESC',
            'include' => [],
            'exclude' => [],
            'meta_key' => '',
            'meta_value' => '',
            'post_type' => 'post',
            'suppress_filters' => true,
        ];

        $parsed_args = wp_parse_args($args, $defaults);
        if(empty($parsed_args['post_status']))
        {
            $parsed_args['post_status'] = ('attachment' === $parsed_args['post_type']) ? 'inherit' : 'publish';
        }
        if(! empty($parsed_args['numberposts']) && empty($parsed_args['posts_per_page']))
        {
            $parsed_args['posts_per_page'] = $parsed_args['numberposts'];
        }
        if(! empty($parsed_args['category']))
        {
            $parsed_args['cat'] = $parsed_args['category'];
        }
        if(! empty($parsed_args['include']))
        {
            $incposts = wp_parse_id_list($parsed_args['include']);
            $parsed_args['posts_per_page'] = count($incposts);  // Only the number of posts included.
            $parsed_args['post__in'] = $incposts;
        }
        elseif(! empty($parsed_args['exclude']))
        {
            $parsed_args['post__not_in'] = wp_parse_id_list($parsed_args['exclude']);
        }

        $parsed_args['ignore_sticky_posts'] = true;
        $parsed_args['no_found_rows'] = true;

        $get_posts = new WP_Query();

        return $get_posts->query($parsed_args);
    }

//
// Post meta functions.
//

    function add_post_meta($post_id, $meta_key, $meta_value, $unique = false)
    {
        // Make sure meta is added to the post, not a revision.
        $the_post = wp_is_post_revision($post_id);
        if($the_post)
        {
            $post_id = $the_post;
        }

        return add_metadata('post', $post_id, $meta_key, $meta_value, $unique);
    }

    function delete_post_meta($post_id, $meta_key, $meta_value = '')
    {
        // Make sure meta is deleted from the post, not from a revision.
        $the_post = wp_is_post_revision($post_id);
        if($the_post)
        {
            $post_id = $the_post;
        }

        return delete_metadata('post', $post_id, $meta_key, $meta_value);
    }

    function get_post_meta($post_id, $key = '', $single = false)
    {
        return get_metadata('post', $post_id, $key, $single);
    }

    function update_post_meta($post_id, $meta_key, $meta_value, $prev_value = '')
    {
        // Make sure meta is updated for the post, not for a revision.
        $the_post = wp_is_post_revision($post_id);
        if($the_post)
        {
            $post_id = $the_post;
        }

        return update_metadata('post', $post_id, $meta_key, $meta_value, $prev_value);
    }

    function delete_post_meta_by_key($post_meta_key)
    {
        return delete_metadata('post', null, $post_meta_key, '', true);
    }

    function register_post_meta($post_type, $meta_key, array $args)
    {
        $args['object_subtype'] = $post_type;

        return register_meta('post', $meta_key, $args);
    }

    function unregister_post_meta($post_type, $meta_key)
    {
        return unregister_meta_key('post', $meta_key, $post_type);
    }

    function get_post_custom($post_id = 0)
    {
        $post_id = absint($post_id);

        if(! $post_id)
        {
            $post_id = get_the_ID();
        }

        return get_post_meta($post_id);
    }

    function get_post_custom_keys($post_id = 0)
    {
        $custom = get_post_custom($post_id);

        if(! is_array($custom))
        {
            return;
        }

        $keys = array_keys($custom);
        if($keys)
        {
            return $keys;
        }
    }

    function get_post_custom_values($key = '', $post_id = 0)
    {
        if(! $key)
        {
            return null;
        }

        $custom = get_post_custom($post_id);

        if(isset($custom[$key]))
        {
            return $custom[$key];
        }

        return null;
    }

    function is_sticky($post_id = 0)
    {
        $post_id = absint($post_id);

        if(! $post_id)
        {
            $post_id = get_the_ID();
        }

        $stickies = get_option('sticky_posts');

        if(is_array($stickies))
        {
            $stickies = array_map('intval', $stickies);
            $is_sticky = in_array($post_id, $stickies, true);
        }
        else
        {
            $is_sticky = false;
        }

        return apply_filters('is_sticky', $is_sticky, $post_id);
    }

    function sanitize_post($post, $context = 'display')
    {
        if(is_object($post))
        {
            // Check if post already filtered for this context.
            if(isset($post->filter) && $context == $post->filter)
            {
                return $post;
            }
            if(! isset($post->ID))
            {
                $post->ID = 0;
            }
            foreach(array_keys(get_object_vars($post)) as $field)
            {
                $post->$field = sanitize_post_field($field, $post->$field, $post->ID, $context);
            }
            $post->filter = $context;
        }
        elseif(is_array($post))
        {
            // Check if post already filtered for this context.
            if(isset($post['filter']) && $context == $post['filter'])
            {
                return $post;
            }
            if(! isset($post['ID']))
            {
                $post['ID'] = 0;
            }
            foreach(array_keys($post) as $field)
            {
                $post[$field] = sanitize_post_field($field, $post[$field], $post['ID'], $context);
            }
            $post['filter'] = $context;
        }

        return $post;
    }

    function sanitize_post_field($field, $value, $post_id, $context = 'display')
    {
        $int_fields = ['ID', 'post_parent', 'menu_order'];
        if(in_array($field, $int_fields, true))
        {
            $value = (int) $value;
        }

        // Fields which contain arrays of integers.
        $array_int_fields = ['ancestors'];
        if(in_array($field, $array_int_fields, true))
        {
            $value = array_map('absint', $value);

            return $value;
        }

        if('raw' === $context)
        {
            return $value;
        }

        $prefixed = false;
        if(str_contains($field, 'post_'))
        {
            $prefixed = true;
            $field_no_prefix = str_replace('post_', '', $field);
        }

        if('edit' === $context)
        {
            $format_to_edit = ['post_content', 'post_excerpt', 'post_title', 'post_password'];

            if($prefixed)
            {
                $value = apply_filters("edit_{$field}", $value, $post_id);

                $value = apply_filters("{$field_no_prefix}_edit_pre", $value, $post_id);
            }
            else
            {
                $value = apply_filters("edit_post_{$field}", $value, $post_id);
            }

            if(in_array($field, $format_to_edit, true))
            {
                if('post_content' === $field)
                {
                    $value = format_to_edit($value, user_can_richedit());
                }
                else
                {
                    $value = format_to_edit($value);
                }
            }
            else
            {
                $value = esc_attr($value);
            }
        }
        elseif('db' === $context)
        {
            if($prefixed)
            {
                $value = apply_filters("pre_{$field}", $value);

                $value = apply_filters("{$field_no_prefix}_save_pre", $value);
            }
            else
            {
                $value = apply_filters("pre_post_{$field}", $value);

                $value = apply_filters("{$field}_pre", $value);
            }
        }
        else
        {
            // Use display filters by default.
            if($prefixed)
            {
                $value = apply_filters("{$field}", $value, $post_id, $context);
            }
            else
            {
                $value = apply_filters("post_{$field}", $value, $post_id, $context);
            }

            if('attribute' === $context)
            {
                $value = esc_attr($value);
            }
            elseif('js' === $context)
            {
                $value = esc_js($value);
            }
        }

        // Restore the type for integer fields after esc_attr().
        if(in_array($field, $int_fields, true))
        {
            $value = (int) $value;
        }

        return $value;
    }

    function stick_post($post_id)
    {
        $post_id = (int) $post_id;
        $stickies = get_option('sticky_posts');
        $updated = false;

        if(! is_array($stickies))
        {
            $stickies = [];
        }
        else
        {
            $stickies = array_unique(array_map('intval', $stickies));
        }

        if(! in_array($post_id, $stickies, true))
        {
            $stickies[] = $post_id;
            $updated = update_option('sticky_posts', array_values($stickies));
        }

        if($updated)
        {
            do_action('post_stuck', $post_id);
        }
    }

    function unstick_post($post_id)
    {
        $post_id = (int) $post_id;
        $stickies = get_option('sticky_posts');

        if(! is_array($stickies))
        {
            return;
        }

        $stickies = array_values(array_unique(array_map('intval', $stickies)));

        if(! in_array($post_id, $stickies, true))
        {
            return;
        }

        $offset = array_search($post_id, $stickies, true);
        if(false === $offset)
        {
            return;
        }

        array_splice($stickies, $offset, 1);

        $updated = update_option('sticky_posts', $stickies);

        if($updated)
        {
            do_action('post_unstuck', $post_id);
        }
    }

    function _count_posts_cache_key($type = 'post', $perm = '')
    {
        $cache_key = 'posts-'.$type;

        if('readable' === $perm && is_user_logged_in())
        {
            $post_type_object = get_post_type_object($type);

            if($post_type_object && ! current_user_can($post_type_object->cap->read_private_posts))
            {
                $cache_key .= '_'.$perm.'_'.get_current_user_id();
            }
        }

        return $cache_key;
    }

    function wp_count_posts($type = 'post', $perm = '')
    {
        global $wpdb;

        if(! post_type_exists($type))
        {
            return new stdClass();
        }

        $cache_key = _count_posts_cache_key($type, $perm);

        $counts = wp_cache_get($cache_key, 'counts');
        if(false !== $counts)
        {
            // We may have cached this before every status was registered.
            foreach(get_post_stati() as $status)
            {
                if(! isset($counts->{$status}))
                {
                    $counts->{$status} = 0;
                }
            }

            return apply_filters('wp_count_posts', $counts, $type, $perm);
        }

        $query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s";

        if('readable' === $perm && is_user_logged_in())
        {
            $post_type_object = get_post_type_object($type);
            if(! current_user_can($post_type_object->cap->read_private_posts))
            {
                $query .= $wpdb->prepare(" AND (post_status != 'private' OR ( post_author = %d AND post_status = 'private' ))", get_current_user_id());
            }
        }

        $query .= ' GROUP BY post_status';

        $results = (array) $wpdb->get_results($wpdb->prepare($query, $type), ARRAY_A);
        $counts = array_fill_keys(get_post_stati(), 0);

        foreach($results as $row)
        {
            $counts[$row['post_status']] = $row['num_posts'];
        }

        $counts = (object) $counts;
        wp_cache_set($cache_key, $counts, 'counts');

        return apply_filters('wp_count_posts', $counts, $type, $perm);
    }

    function wp_count_attachments($mime_type = '')
    {
        global $wpdb;

        $cache_key = sprintf('attachments%s', ! empty($mime_type) ? ':'.str_replace('/', '_', implode('-', (array) $mime_type)) : '');

        $counts = wp_cache_get($cache_key, 'counts');
        if(false == $counts)
        {
            $and = wp_post_mime_type_where($mime_type);
            $count = $wpdb->get_results("SELECT post_mime_type, COUNT( * ) AS num_posts FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status != 'trash' $and GROUP BY post_mime_type", ARRAY_A);

            $counts = [];
            foreach((array) $count as $row)
            {
                $counts[$row['post_mime_type']] = $row['num_posts'];
            }
            $counts['trash'] = $wpdb->get_var("SELECT COUNT( * ) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status = 'trash' $and");

            wp_cache_set($cache_key, (object) $counts, 'counts');
        }

        return apply_filters('wp_count_attachments', (object) $counts, $mime_type);
    }

    function get_post_mime_types()
    {
        $post_mime_types = [   // array( adj, noun )
            'image' => [
                __('Images'),
                __('Manage Images'),
                /* translators: %s: Number of images. */
                _n_noop('Image <span class="count">(%s)</span>', 'Images <span class="count">(%s)</span>'),
            ],
            'audio' => [
                _x('Audio', 'file type group'),
                __('Manage Audio'),
                /* translators: %s: Number of audio files. */
                _n_noop('Audio <span class="count">(%s)</span>', 'Audio <span class="count">(%s)</span>'),
            ],
            'video' => [
                _x('Video', 'file type group'),
                __('Manage Video'),
                /* translators: %s: Number of video files. */
                _n_noop('Video <span class="count">(%s)</span>', 'Video <span class="count">(%s)</span>'),
            ],
            'document' => [
                __('Documents'),
                __('Manage Documents'),
                /* translators: %s: Number of documents. */
                _n_noop('Document <span class="count">(%s)</span>', 'Documents <span class="count">(%s)</span>'),
            ],
            'spreadsheet' => [
                __('Spreadsheets'),
                __('Manage Spreadsheets'),
                /* translators: %s: Number of spreadsheets. */
                _n_noop('Spreadsheet <span class="count">(%s)</span>', 'Spreadsheets <span class="count">(%s)</span>'),
            ],
            'archive' => [
                _x('Archives', 'file type group'),
                __('Manage Archives'),
                /* translators: %s: Number of archives. */
                _n_noop('Archive <span class="count">(%s)</span>', 'Archives <span class="count">(%s)</span>'),
            ],
        ];

        $ext_types = wp_get_ext_types();
        $mime_types = wp_get_mime_types();

        foreach($post_mime_types as $group => $labels)
        {
            if(in_array($group, ['image', 'audio', 'video'], true))
            {
                continue;
            }

            if(! isset($ext_types[$group]))
            {
                unset($post_mime_types[$group]);
                continue;
            }

            $group_mime_types = [];
            foreach($ext_types[$group] as $extension)
            {
                foreach($mime_types as $exts => $mime)
                {
                    if(preg_match('!^('.$exts.')$!i', $extension))
                    {
                        $group_mime_types[] = $mime;
                        break;
                    }
                }
            }
            $group_mime_types = implode(',', array_unique($group_mime_types));

            $post_mime_types[$group_mime_types] = $labels;
            unset($post_mime_types[$group]);
        }

        return apply_filters('post_mime_types', $post_mime_types);
    }

    function wp_match_mime_types($wildcard_mime_types, $real_mime_types)
    {
        $matches = [];
        if(is_string($wildcard_mime_types))
        {
            $wildcard_mime_types = array_map('trim', explode(',', $wildcard_mime_types));
        }
        if(is_string($real_mime_types))
        {
            $real_mime_types = array_map('trim', explode(',', $real_mime_types));
        }

        $patternses = [];
        $wild = '[-._a-z0-9]*';

        foreach((array) $wildcard_mime_types as $type)
        {
            $mimes = array_map('trim', explode(',', $type));
            foreach($mimes as $mime)
            {
                $regex = str_replace('__wildcard__', $wild, preg_quote(str_replace('*', '__wildcard__', $mime)));

                $patternses[][$type] = "^$regex$";

                if(! str_contains($mime, '/'))
                {
                    $patternses[][$type] = "^$regex/";
                    $patternses[][$type] = $regex;
                }
            }
        }
        asort($patternses);

        foreach($patternses as $patterns)
        {
            foreach($patterns as $type => $pattern)
            {
                foreach((array) $real_mime_types as $real)
                {
                    if(preg_match("#$pattern#", $real) && (empty($matches[$type]) || false === array_search($real, $matches[$type], true)))
                    {
                        $matches[$type][] = $real;
                    }
                }
            }
        }

        return $matches;
    }

    function wp_post_mime_type_where($post_mime_types, $table_alias = '')
    {
        $where = '';
        $wildcards = ['', '%', '%/%'];
        if(is_string($post_mime_types))
        {
            $post_mime_types = array_map('trim', explode(',', $post_mime_types));
        }

        $wheres = [];

        foreach((array) $post_mime_types as $mime_type)
        {
            $mime_type = preg_replace('/\s/', '', $mime_type);
            $slashpos = strpos($mime_type, '/');
            if(false !== $slashpos)
            {
                $mime_group = preg_replace('/[^-*.a-zA-Z0-9]/', '', substr($mime_type, 0, $slashpos));
                $mime_subgroup = preg_replace('/[^-*.+a-zA-Z0-9]/', '', substr($mime_type, $slashpos + 1));
                if(empty($mime_subgroup))
                {
                    $mime_subgroup = '*';
                }
                else
                {
                    $mime_subgroup = str_replace('/', '', $mime_subgroup);
                }
                $mime_pattern = "$mime_group/$mime_subgroup";
            }
            else
            {
                $mime_pattern = preg_replace('/[^-*.a-zA-Z0-9]/', '', $mime_type);
                if(! str_contains($mime_pattern, '*'))
                {
                    $mime_pattern .= '/*';
                }
            }

            $mime_pattern = preg_replace('/\*+/', '%', $mime_pattern);

            if(in_array($mime_type, $wildcards, true))
            {
                return '';
            }

            if(str_contains($mime_pattern, '%'))
            {
                $wheres[] = empty($table_alias) ? "post_mime_type LIKE '$mime_pattern'" : "$table_alias.post_mime_type LIKE '$mime_pattern'";
            }
            else
            {
                $wheres[] = empty($table_alias) ? "post_mime_type = '$mime_pattern'" : "$table_alias.post_mime_type = '$mime_pattern'";
            }
        }

        if(! empty($wheres))
        {
            $where = ' AND ('.implode(' OR ', $wheres).') ';
        }

        return $where;
    }

    function wp_delete_post($postid = 0, $force_delete = false)
    {
        global $wpdb;

        $post = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID = %d", $postid));

        if(! $post)
        {
            return $post;
        }

        $post = get_post($post);

        if(! $force_delete && ('post' === $post->post_type || 'page' === $post->post_type) && 'trash' !== get_post_status($postid) && EMPTY_TRASH_DAYS)
        {
            return wp_trash_post($postid);
        }

        if('attachment' === $post->post_type)
        {
            return wp_delete_attachment($postid, $force_delete);
        }

        $check = apply_filters('pre_delete_post', null, $post, $force_delete);
        if(null !== $check)
        {
            return $check;
        }

        do_action('before_delete_post', $postid, $post);

        delete_post_meta($postid, '_wp_trash_meta_status');
        delete_post_meta($postid, '_wp_trash_meta_time');

        wp_delete_object_term_relationships($postid, get_object_taxonomies($post->post_type));

        $parent_data = ['post_parent' => $post->post_parent];
        $parent_where = ['post_parent' => $postid];

        if(is_post_type_hierarchical($post->post_type))
        {
            // Point children of this page to its parent, also clean the cache of affected children.
            $children_query = $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_parent = %d AND post_type = %s", $postid, $post->post_type);
            $children = $wpdb->get_results($children_query);
            if($children)
            {
                $wpdb->update($wpdb->posts, $parent_data, $parent_where + ['post_type' => $post->post_type]);
            }
        }

        // Do raw query. wp_get_post_revisions() is filtered.
        $revision_ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_parent = %d AND post_type = 'revision'", $postid));
        // Use wp_delete_post (via wp_delete_post_revision) again. Ensures any meta/misplaced data gets cleaned up.
        foreach($revision_ids as $revision_id)
        {
            wp_delete_post_revision($revision_id);
        }

        // Point all attachments to this post up one level.
        $wpdb->update($wpdb->posts, $parent_data, $parent_where + ['post_type' => 'attachment']);

        wp_defer_comment_counting(true);

        $comment_ids = $wpdb->get_col($wpdb->prepare("SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID = %d ORDER BY comment_ID DESC", $postid));
        foreach($comment_ids as $comment_id)
        {
            wp_delete_comment($comment_id, true);
        }

        wp_defer_comment_counting(false);

        $post_meta_ids = $wpdb->get_col($wpdb->prepare("SELECT meta_id FROM $wpdb->postmeta WHERE post_id = %d ", $postid));
        foreach($post_meta_ids as $mid)
        {
            delete_metadata_by_mid('post', $mid);
        }

        do_action('delete_post', $postid, $post);

        $result = $wpdb->delete($wpdb->posts, ['ID' => $postid]);
        if(! $result)
        {
            return false;
        }

        do_action('deleted_post', $postid, $post);

        clean_post_cache($post);

        if(is_post_type_hierarchical($post->post_type) && $children)
        {
            foreach($children as $child)
            {
                clean_post_cache($child);
            }
        }

        wp_clear_scheduled_hook('publish_future_post', [$postid]);

        do_action('after_delete_post', $postid, $post);

        return $post;
    }

    function _reset_front_page_settings_for_post($post_id)
    {
        $post = get_post($post_id);

        if('page' === $post->post_type)
        {
            /*
		 * If the page is defined in option page_on_front or post_for_posts,
		 * adjust the corresponding options.
		 */
            if(get_option('page_on_front') == $post->ID)
            {
                update_option('show_on_front', 'posts');
                update_option('page_on_front', 0);
            }
            if(get_option('page_for_posts') == $post->ID)
            {
                update_option('page_for_posts', 0);
            }
        }

        unstick_post($post->ID);
    }

    function wp_trash_post($post_id = 0)
    {
        if(! EMPTY_TRASH_DAYS)
        {
            return wp_delete_post($post_id, true);
        }

        $post = get_post($post_id);

        if(! $post)
        {
            return $post;
        }

        if('trash' === $post->post_status)
        {
            return false;
        }

        $previous_status = $post->post_status;

        $check = apply_filters('pre_trash_post', null, $post, $previous_status);

        if(null !== $check)
        {
            return $check;
        }

        do_action('wp_trash_post', $post_id, $previous_status);

        add_post_meta($post_id, '_wp_trash_meta_status', $previous_status);
        add_post_meta($post_id, '_wp_trash_meta_time', time());

        $post_updated = wp_update_post([
                                           'ID' => $post_id,
                                           'post_status' => 'trash',
                                       ]);

        if(! $post_updated)
        {
            return false;
        }

        wp_trash_post_comments($post_id);

        do_action('trashed_post', $post_id, $previous_status);

        return $post;
    }

    function wp_untrash_post($post_id = 0)
    {
        $post = get_post($post_id);

        if(! $post)
        {
            return $post;
        }

        $post_id = $post->ID;

        if('trash' !== $post->post_status)
        {
            return false;
        }

        $previous_status = get_post_meta($post_id, '_wp_trash_meta_status', true);

        $check = apply_filters('pre_untrash_post', null, $post, $previous_status);
        if(null !== $check)
        {
            return $check;
        }

        do_action('untrash_post', $post_id, $previous_status);

        $new_status = ('attachment' === $post->post_type) ? 'inherit' : 'draft';

        $post_status = apply_filters('wp_untrash_post_status', $new_status, $post_id, $previous_status);

        delete_post_meta($post_id, '_wp_trash_meta_status');
        delete_post_meta($post_id, '_wp_trash_meta_time');

        $post_updated = wp_update_post([
                                           'ID' => $post_id,
                                           'post_status' => $post_status,
                                       ]);

        if(! $post_updated)
        {
            return false;
        }

        wp_untrash_post_comments($post_id);

        do_action('untrashed_post', $post_id, $previous_status);

        return $post;
    }

    function wp_trash_post_comments($post = null)
    {
        global $wpdb;

        $post = get_post($post);

        if(! $post)
        {
            return;
        }

        $post_id = $post->ID;

        do_action('trash_post_comments', $post_id);

        $comments = $wpdb->get_results($wpdb->prepare("SELECT comment_ID, comment_approved FROM $wpdb->comments WHERE comment_post_ID = %d", $post_id));

        if(! $comments)
        {
            return;
        }

        // Cache current status for each comment.
        $statuses = [];
        foreach($comments as $comment)
        {
            $statuses[$comment->comment_ID] = $comment->comment_approved;
        }
        add_post_meta($post_id, '_wp_trash_meta_comments_status', $statuses);

        // Set status for all comments to post-trashed.
        $result = $wpdb->update($wpdb->comments, ['comment_approved' => 'post-trashed'], ['comment_post_ID' => $post_id]);

        clean_comment_cache(array_keys($statuses));

        do_action('trashed_post_comments', $post_id, $statuses);

        return $result;
    }

    function wp_untrash_post_comments($post = null)
    {
        global $wpdb;

        $post = get_post($post);

        if(! $post)
        {
            return;
        }

        $post_id = $post->ID;

        $statuses = get_post_meta($post_id, '_wp_trash_meta_comments_status', true);

        if(! $statuses)
        {
            return true;
        }

        do_action('untrash_post_comments', $post_id);

        // Restore each comment to its original status.
        $group_by_status = [];
        foreach($statuses as $comment_id => $comment_status)
        {
            $group_by_status[$comment_status][] = $comment_id;
        }

        foreach($group_by_status as $status => $comments)
        {
            // Sanity check. This shouldn't happen.
            if('post-trashed' === $status)
            {
                $status = '0';
            }
            $comments_in = implode(', ', array_map('intval', $comments));
            $wpdb->query($wpdb->prepare("UPDATE $wpdb->comments SET comment_approved = %s WHERE comment_ID IN ($comments_in)", $status));
        }

        clean_comment_cache(array_keys($statuses));

        delete_post_meta($post_id, '_wp_trash_meta_comments_status');

        do_action('untrashed_post_comments', $post_id);
    }

    function wp_get_post_categories($post_id = 0, $args = [])
    {
        $post_id = (int) $post_id;

        $defaults = ['fields' => 'ids'];
        $args = wp_parse_args($args, $defaults);

        $cats = wp_get_object_terms($post_id, 'category', $args);

        return $cats;
    }

    function wp_get_post_tags($post_id = 0, $args = [])
    {
        return wp_get_post_terms($post_id, 'post_tag', $args);
    }

    function wp_get_post_terms($post_id = 0, $taxonomy = 'post_tag', $args = [])
    {
        $post_id = (int) $post_id;

        $defaults = ['fields' => 'all'];
        $args = wp_parse_args($args, $defaults);

        $tags = wp_get_object_terms($post_id, $taxonomy, $args);

        return $tags;
    }

    function wp_get_recent_posts($args = [], $output = ARRAY_A)
    {
        if(is_numeric($args))
        {
            _deprecated_argument(__FUNCTION__, '3.1.0', __('Passing an integer number of posts is deprecated. Pass an array of arguments instead.'));
            $args = ['numberposts' => absint($args)];
        }

        // Set default arguments.
        $defaults = [
            'numberposts' => 10,
            'offset' => 0,
            'category' => 0,
            'orderby' => 'post_date',
            'order' => 'DESC',
            'include' => '',
            'exclude' => '',
            'meta_key' => '',
            'meta_value' => '',
            'post_type' => 'post',
            'post_status' => 'draft, publish, future, pending, private',
            'suppress_filters' => true,
        ];

        $parsed_args = wp_parse_args($args, $defaults);

        $results = get_posts($parsed_args);

        // Backward compatibility. Prior to 3.1 expected posts to be returned in array.
        if(ARRAY_A === $output)
        {
            foreach($results as $key => $result)
            {
                $results[$key] = get_object_vars($result);
            }

            if($results)
            {
                return $results;
            }

            return [];
        }

        if($results)
        {
            return $results;
        }

        return false;
    }

    function wp_insert_post($postarr, $wp_error = false, $fire_after_hooks = true)
    {
        global $wpdb;

        // Capture original pre-sanitized array for passing into filters.
        $unsanitized_postarr = $postarr;

        $user_id = get_current_user_id();

        $defaults = [
            'post_author' => $user_id,
            'post_content' => '',
            'post_content_filtered' => '',
            'post_title' => '',
            'post_excerpt' => '',
            'post_status' => 'draft',
            'post_type' => 'post',
            'comment_status' => '',
            'ping_status' => '',
            'post_password' => '',
            'to_ping' => '',
            'pinged' => '',
            'post_parent' => 0,
            'menu_order' => 0,
            'guid' => '',
            'import_id' => 0,
            'context' => '',
            'post_date' => '',
            'post_date_gmt' => '',
        ];

        $postarr = wp_parse_args($postarr, $defaults);

        unset($postarr['filter']);

        $postarr = sanitize_post($postarr, 'db');

        // Are we updating or creating?
        $post_id = 0;
        $update = false;
        $guid = $postarr['guid'];

        if(! empty($postarr['ID']))
        {
            $update = true;

            // Get the post ID and GUID.
            $post_id = $postarr['ID'];
            $post_before = get_post($post_id);

            if(is_null($post_before))
            {
                if($wp_error)
                {
                    return new WP_Error('invalid_post', __('Invalid post ID.'));
                }

                return 0;
            }

            $guid = get_post_field('guid', $post_id);
            $previous_status = get_post_field('post_status', $post_id);
        }
        else
        {
            $previous_status = 'new';
            $post_before = null;
        }

        $post_type = empty($postarr['post_type']) ? 'post' : $postarr['post_type'];

        $post_title = $postarr['post_title'];
        $post_content = $postarr['post_content'];
        $post_excerpt = $postarr['post_excerpt'];

        if(isset($postarr['post_name']))
        {
            $post_name = $postarr['post_name'];
        }
        elseif($update)
        {
            // For an update, don't modify the post_name if it wasn't supplied as an argument.
            $post_name = $post_before->post_name;
        }

        $maybe_empty = 'attachment' !== $post_type && ! $post_content && ! $post_title && ! $post_excerpt && post_type_supports($post_type, 'editor') && post_type_supports($post_type, 'title') && post_type_supports($post_type, 'excerpt');

        if(apply_filters('wp_insert_post_empty_content', $maybe_empty, $postarr))
        {
            if($wp_error)
            {
                return new WP_Error('empty_content', __('Content, title, and excerpt are empty.'));
            }
            else
            {
                return 0;
            }
        }

        $post_status = empty($postarr['post_status']) ? 'draft' : $postarr['post_status'];

        if(
            'attachment' === $post_type && ! in_array($post_status, [
                'inherit',
                'private',
                'trash',
                'auto-draft'
            ],                                        true)
        )
        {
            $post_status = 'inherit';
        }

        if(! empty($postarr['post_category']))
        {
            // Filter out empty terms.
            $post_category = array_filter($postarr['post_category']);
        }
        elseif($update && ! isset($postarr['post_category']))
        {
            $post_category = $post_before->post_category;
        }

        // Make sure we set a valid category.
        if(empty($post_category) || 0 === count($post_category) || ! is_array($post_category))
        {
            // 'post' requires at least one category.
            if('post' === $post_type && 'auto-draft' !== $post_status)
            {
                $post_category = [get_option('default_category')];
            }
            else
            {
                $post_category = [];
            }
        }

        /*
	 * Don't allow contributors to set the post slug for pending review posts.
	 *
	 * For new posts check the primitive capability, for updates check the meta capability.
	 */
        if('pending' === $post_status)
        {
            $post_type_object = get_post_type_object($post_type);

            if(! $update && $post_type_object && ! current_user_can($post_type_object->cap->publish_posts))
            {
                $post_name = '';
            }
            elseif($update && ! current_user_can('publish_post', $post_id))
            {
                $post_name = '';
            }
        }

        /*
	 * Create a valid post name. Drafts and pending posts are allowed to have
	 * an empty post name.
	 */
        if(empty($post_name))
        {
            if(! in_array($post_status, ['draft', 'pending', 'auto-draft'], true))
            {
                $post_name = sanitize_title($post_title);
            }
            else
            {
                $post_name = '';
            }
        }
        else
        {
            // On updates, we need to check to see if it's using the old, fixed sanitization context.
            $check_name = sanitize_title($post_name, '', 'old-save');

            if($update && strtolower(urlencode($post_name)) === $check_name && get_post_field('post_name', $post_id) === $check_name)
            {
                $post_name = $check_name;
            }
            else
            { // New post, or slug has changed.
                $post_name = sanitize_title($post_name);
            }
        }

        /*
	 * Resolve the post date from any provided post date or post date GMT strings;
	 * if none are provided, the date will be set to now.
	 */
        $post_date = wp_resolve_post_date($postarr['post_date'], $postarr['post_date_gmt']);

        if(! $post_date)
        {
            if($wp_error)
            {
                return new WP_Error('invalid_date', __('Invalid date.'));
            }
            else
            {
                return 0;
            }
        }

        if(empty($postarr['post_date_gmt']) || '0000-00-00 00:00:00' === $postarr['post_date_gmt'])
        {
            if(! in_array($post_status, get_post_stati(['date_floating' => true]), true))
            {
                $post_date_gmt = get_gmt_from_date($post_date);
            }
            else
            {
                $post_date_gmt = '0000-00-00 00:00:00';
            }
        }
        else
        {
            $post_date_gmt = $postarr['post_date_gmt'];
        }

        if($update || '0000-00-00 00:00:00' === $post_date)
        {
            $post_modified = current_time('mysql');
            $post_modified_gmt = current_time('mysql', 1);
        }
        else
        {
            $post_modified = $post_date;
            $post_modified_gmt = $post_date_gmt;
        }

        if('attachment' !== $post_type)
        {
            $now = gmdate('Y-m-d H:i:s');

            if('publish' === $post_status)
            {
                if(strtotime($post_date_gmt) - strtotime($now) >= MINUTE_IN_SECONDS)
                {
                    $post_status = 'future';
                }
            }
            elseif('future' === $post_status && strtotime($post_date_gmt) - strtotime($now) < MINUTE_IN_SECONDS)
            {
                $post_status = 'publish';
            }
        }

        // Comment status.
        if(empty($postarr['comment_status']))
        {
            if($update)
            {
                $comment_status = 'closed';
            }
            else
            {
                $comment_status = get_default_comment_status($post_type);
            }
        }
        else
        {
            $comment_status = $postarr['comment_status'];
        }

        // These variables are needed by compact() later.
        $post_content_filtered = $postarr['post_content_filtered'];
        $post_author = isset($postarr['post_author']) ? $postarr['post_author'] : $user_id;
        $ping_status = empty($postarr['ping_status']) ? get_default_comment_status($post_type, 'pingback') : $postarr['ping_status'];
        $to_ping = isset($postarr['to_ping']) ? sanitize_trackback_urls($postarr['to_ping']) : '';
        $pinged = isset($postarr['pinged']) ? $postarr['pinged'] : '';
        $import_id = isset($postarr['import_id']) ? $postarr['import_id'] : 0;

        /*
	 * The 'wp_insert_post_parent' filter expects all variables to be present.
	 * Previously, these variables would have already been extracted
	 */
        if(isset($postarr['menu_order']))
        {
            $menu_order = (int) $postarr['menu_order'];
        }
        else
        {
            $menu_order = 0;
        }

        $post_password = isset($postarr['post_password']) ? $postarr['post_password'] : '';
        if('private' === $post_status)
        {
            $post_password = '';
        }

        if(isset($postarr['post_parent']))
        {
            $post_parent = (int) $postarr['post_parent'];
        }
        else
        {
            $post_parent = 0;
        }

        $new_postarr = array_merge([
                                       'ID' => $post_id,
                                   ], compact());

        $post_parent = apply_filters('wp_insert_post_parent', $post_parent, $post_id, $new_postarr, $postarr);

        /*
	 * If the post is being untrashed and it has a desired slug stored in post meta,
	 * reassign it.
	 */
        if('trash' === $previous_status && 'trash' !== $post_status)
        {
            $desired_post_slug = get_post_meta($post_id, '_wp_desired_post_slug', true);

            if($desired_post_slug)
            {
                delete_post_meta($post_id, '_wp_desired_post_slug');
                $post_name = $desired_post_slug;
            }
        }

        // If a trashed post has the desired slug, change it and let this post have it.
        if('trash' !== $post_status && $post_name)
        {
            $add_trashed_suffix = apply_filters('add_trashed_suffix_to_trashed_posts', true, $post_name, $post_id);

            if($add_trashed_suffix)
            {
                wp_add_trashed_suffix_to_post_name_for_trashed_posts($post_name, $post_id);
            }
        }

        // When trashing an existing post, change its slug to allow non-trashed posts to use it.
        if('trash' === $post_status && 'trash' !== $previous_status && 'new' !== $previous_status)
        {
            $post_name = wp_add_trashed_suffix_to_post_name_for_post($post_id);
        }

        $post_name = wp_unique_post_slug($post_name, $post_id, $post_status, $post_type, $post_parent);

        // Don't unslash.
        $post_mime_type = isset($postarr['post_mime_type']) ? $postarr['post_mime_type'] : '';

        // Expected_slashed (everything!).
        $data = compact('post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_content_filtered', 'post_title', 'post_excerpt', 'post_status', 'post_type', 'comment_status', 'ping_status', 'post_password', 'post_name', 'to_ping', 'pinged', 'post_modified', 'post_modified_gmt', 'post_parent', 'menu_order', 'post_mime_type', 'guid');

        $emoji_fields = ['post_title', 'post_content', 'post_excerpt'];

        foreach($emoji_fields as $emoji_field)
        {
            if(isset($data[$emoji_field]))
            {
                $charset = $wpdb->get_col_charset($wpdb->posts, $emoji_field);

                if('utf8' === $charset)
                {
                    $data[$emoji_field] = wp_encode_emoji($data[$emoji_field]);
                }
            }
        }

        if('attachment' === $post_type)
        {
            $data = apply_filters('wp_insert_attachment_data', $data, $postarr, $unsanitized_postarr, $update);
        }
        else
        {
            $data = apply_filters('wp_insert_post_data', $data, $postarr, $unsanitized_postarr, $update);
        }

        $data = wp_unslash($data);
        $where = ['ID' => $post_id];

        if($update)
        {
            do_action('pre_post_update', $post_id, $data);

            if(false === $wpdb->update($wpdb->posts, $data, $where))
            {
                if($wp_error)
                {
                    if('attachment' === $post_type)
                    {
                        $message = __('Could not update attachment in the database.');
                    }
                    else
                    {
                        $message = __('Could not update post in the database.');
                    }

                    return new WP_Error('db_update_error', $message, $wpdb->last_error);
                }
                else
                {
                    return 0;
                }
            }
        }
        else
        {
            // If there is a suggested ID, use it if not already present.
            if(! empty($import_id))
            {
                $import_id = (int) $import_id;

                if(! $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE ID = %d", $import_id)))
                {
                    $data['ID'] = $import_id;
                }
            }

            if(false === $wpdb->insert($wpdb->posts, $data))
            {
                if($wp_error)
                {
                    if('attachment' === $post_type)
                    {
                        $message = __('Could not insert attachment into the database.');
                    }
                    else
                    {
                        $message = __('Could not insert post into the database.');
                    }

                    return new WP_Error('db_insert_error', $message, $wpdb->last_error);
                }
                else
                {
                    return 0;
                }
            }

            $post_id = (int) $wpdb->insert_id;

            // Use the newly generated $post_id.
            $where = ['ID' => $post_id];
        }

        if(empty($data['post_name']) && ! in_array($data['post_status'], ['draft', 'pending', 'auto-draft'], true))
        {
            $data['post_name'] = wp_unique_post_slug(sanitize_title($data['post_title'], $post_id), $post_id, $data['post_status'], $post_type, $post_parent);

            $wpdb->update($wpdb->posts, ['post_name' => $data['post_name']], $where);
            clean_post_cache($post_id);
        }

        if(is_object_in_taxonomy($post_type, 'category'))
        {
            wp_set_post_categories($post_id, $post_category);
        }

        if(isset($postarr['tags_input']) && is_object_in_taxonomy($post_type, 'post_tag'))
        {
            wp_set_post_tags($post_id, $postarr['tags_input']);
        }

        // Add default term for all associated custom taxonomies.
        if('auto-draft' !== $post_status)
        {
            foreach(get_object_taxonomies($post_type, 'object') as $taxonomy => $tax_object)
            {
                if(! empty($tax_object->default_term))
                {
                    // Filter out empty terms.
                    if(isset($postarr['tax_input'][$taxonomy]) && is_array($postarr['tax_input'][$taxonomy]))
                    {
                        $postarr['tax_input'][$taxonomy] = array_filter($postarr['tax_input'][$taxonomy]);
                    }

                    // Passed custom taxonomy list overwrites the existing list if not empty.
                    $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'ids']);
                    if(! empty($terms) && empty($postarr['tax_input'][$taxonomy]))
                    {
                        $postarr['tax_input'][$taxonomy] = $terms;
                    }

                    if(empty($postarr['tax_input'][$taxonomy]))
                    {
                        $default_term_id = get_option('default_term_'.$taxonomy);
                        if(! empty($default_term_id))
                        {
                            $postarr['tax_input'][$taxonomy] = [(int) $default_term_id];
                        }
                    }
                }
            }
        }

        // New-style support for all custom taxonomies.
        if(! empty($postarr['tax_input']))
        {
            foreach($postarr['tax_input'] as $taxonomy => $tags)
            {
                $taxonomy_obj = get_taxonomy($taxonomy);

                if(! $taxonomy_obj)
                {
                    /* translators: %s: Taxonomy name. */
                    _doing_it_wrong(__FUNCTION__, sprintf(__('Invalid taxonomy: %s.'), $taxonomy), '4.4.0');
                    continue;
                }

                // array = hierarchical, string = non-hierarchical.
                if(is_array($tags))
                {
                    $tags = array_filter($tags);
                }

                if(current_user_can($taxonomy_obj->cap->assign_terms))
                {
                    wp_set_post_terms($post_id, $tags, $taxonomy);
                }
            }
        }

        if(! empty($postarr['meta_input']))
        {
            foreach($postarr['meta_input'] as $field => $value)
            {
                update_post_meta($post_id, $field, $value);
            }
        }

        $current_guid = get_post_field('guid', $post_id);

        // Set GUID.
        if(! $update && '' === $current_guid)
        {
            $wpdb->update($wpdb->posts, ['guid' => get_permalink($post_id)], $where);
        }

        if('attachment' === $postarr['post_type'])
        {
            if(! empty($postarr['file']))
            {
                update_attached_file($post_id, $postarr['file']);
            }

            if(! empty($postarr['context']))
            {
                add_post_meta($post_id, '_wp_attachment_context', $postarr['context'], true);
            }
        }

        // Set or remove featured image.
        if(isset($postarr['_thumbnail_id']))
        {
            $thumbnail_support = current_theme_supports('post-thumbnails', $post_type) && post_type_supports($post_type, 'thumbnail') || 'revision' === $post_type;

            if(! $thumbnail_support && 'attachment' === $post_type && $post_mime_type)
            {
                if(wp_attachment_is('audio', $post_id))
                {
                    $thumbnail_support = post_type_supports('attachment:audio', 'thumbnail') || current_theme_supports('post-thumbnails', 'attachment:audio');
                }
                elseif(wp_attachment_is('video', $post_id))
                {
                    $thumbnail_support = post_type_supports('attachment:video', 'thumbnail') || current_theme_supports('post-thumbnails', 'attachment:video');
                }
            }

            if($thumbnail_support)
            {
                $thumbnail_id = (int) $postarr['_thumbnail_id'];
                if(-1 === $thumbnail_id)
                {
                    delete_post_thumbnail($post_id);
                }
                else
                {
                    set_post_thumbnail($post_id, $thumbnail_id);
                }
            }
        }

        clean_post_cache($post_id);

        $post = get_post($post_id);

        if(! empty($postarr['page_template']))
        {
            $post->page_template = $postarr['page_template'];
            $page_templates = wp_get_theme()->get_page_templates($post);

            if('default' !== $postarr['page_template'] && ! isset($page_templates[$postarr['page_template']]))
            {
                if($wp_error)
                {
                    return new WP_Error('invalid_page_template', __('Invalid page template.'));
                }

                update_post_meta($post_id, '_wp_page_template', 'default');
            }
            else
            {
                update_post_meta($post_id, '_wp_page_template', $postarr['page_template']);
            }
        }

        if('attachment' !== $postarr['post_type'])
        {
            wp_transition_post_status($data['post_status'], $previous_status, $post);
        }
        else
        {
            if($update)
            {
                do_action('edit_attachment', $post_id);

                $post_after = get_post($post_id);

                do_action('attachment_updated', $post_id, $post_after, $post_before);
            }
            else
            {
                do_action('add_attachment', $post_id);
            }

            return $post_id;
        }

        if($update)
        {
            do_action("edit_post_{$post->post_type}", $post_id, $post);

            do_action('edit_post', $post_id, $post);

            $post_after = get_post($post_id);

            do_action('post_updated', $post_id, $post_after, $post_before);
        }

        do_action("save_post_{$post->post_type}", $post_id, $post, $update);

        do_action('save_post', $post_id, $post, $update);

        do_action('wp_insert_post', $post_id, $post, $update);

        if($fire_after_hooks)
        {
            wp_after_insert_post($post, $update, $post_before);
        }

        return $post_id;
    }

    function wp_update_post($postarr = [], $wp_error = false, $fire_after_hooks = true)
    {
        if(is_object($postarr))
        {
            // Non-escaped post was passed.
            $postarr = get_object_vars($postarr);
            $postarr = wp_slash($postarr);
        }

        // First, get all of the original fields.
        $post = get_post($postarr['ID'], ARRAY_A);

        if(is_null($post))
        {
            if($wp_error)
            {
                return new WP_Error('invalid_post', __('Invalid post ID.'));
            }

            return 0;
        }

        // Escape data pulled from DB.
        $post = wp_slash($post);

        // Passed post category list overwrites existing category list if not empty.
        if(isset($postarr['post_category']) && is_array($postarr['post_category']) && count($postarr['post_category']) > 0)
        {
            $post_cats = $postarr['post_category'];
        }
        else
        {
            $post_cats = $post['post_category'];
        }

        // Drafts shouldn't be assigned a date unless explicitly done so by the user.
        if(
            isset($post['post_status']) && in_array($post['post_status'], [
                'draft',
                'pending',
                'auto-draft'
            ],                                      true) && empty($postarr['edit_date']) && ('0000-00-00 00:00:00' === $post['post_date_gmt'])
        )
        {
            $clear_date = true;
        }
        else
        {
            $clear_date = false;
        }

        // Merge old and new fields with new fields overwriting old ones.
        $postarr = array_merge($post, $postarr);
        $postarr['post_category'] = $post_cats;
        if($clear_date)
        {
            $postarr['post_date'] = current_time('mysql');
            $postarr['post_date_gmt'] = '';
        }

        if('attachment' === $postarr['post_type'])
        {
            return wp_insert_attachment($postarr, false, 0, $wp_error);
        }

        // Discard 'tags_input' parameter if it's the same as existing post tags.
        if(isset($postarr['tags_input']) && is_object_in_taxonomy($postarr['post_type'], 'post_tag'))
        {
            $tags = get_the_terms($postarr['ID'], 'post_tag');
            $tag_names = [];

            if($tags && ! is_wp_error($tags))
            {
                $tag_names = wp_list_pluck($tags, 'name');
            }

            if($postarr['tags_input'] === $tag_names)
            {
                unset($postarr['tags_input']);
            }
        }

        return wp_insert_post($postarr, $wp_error, $fire_after_hooks);
    }

    function wp_publish_post($post)
    {
        global $wpdb;

        $post = get_post($post);

        if(! $post || 'publish' === $post->post_status)
        {
            return;
        }

        $post_before = get_post($post->ID);

        // Ensure at least one term is applied for taxonomies with a default term.
        foreach(get_object_taxonomies($post->post_type, 'object') as $taxonomy => $tax_object)
        {
            // Skip taxonomy if no default term is set.
            if('category' !== $taxonomy && empty($tax_object->default_term))
            {
                continue;
            }

            // Do not modify previously set terms.
            if(! empty(get_the_terms($post, $taxonomy)))
            {
                continue;
            }

            if('category' === $taxonomy)
            {
                $default_term_id = (int) get_option('default_category', 0);
            }
            else
            {
                $default_term_id = (int) get_option('default_term_'.$taxonomy, 0);
            }

            if(! $default_term_id)
            {
                continue;
            }
            wp_set_post_terms($post->ID, [$default_term_id], $taxonomy);
        }

        $wpdb->update($wpdb->posts, ['post_status' => 'publish'], ['ID' => $post->ID]);

        clean_post_cache($post->ID);

        $old_status = $post->post_status;
        $post->post_status = 'publish';
        wp_transition_post_status('publish', $old_status, $post);

        do_action("edit_post_{$post->post_type}", $post->ID, $post);

        do_action('edit_post', $post->ID, $post);

        do_action("save_post_{$post->post_type}", $post->ID, $post, true);

        do_action('save_post', $post->ID, $post, true);

        do_action('wp_insert_post', $post->ID, $post, true);

        wp_after_insert_post($post, true, $post_before);
    }

    function check_and_publish_future_post($post)
    {
        $post = get_post($post);

        if(! $post || 'future' !== $post->post_status)
        {
            return;
        }

        $time = strtotime($post->post_date_gmt.' GMT');

        // Uh oh, someone jumped the gun!
        if($time > time())
        {
            wp_clear_scheduled_hook('publish_future_post', [$post->ID]); // Clear anything else in the system.
            wp_schedule_single_event($time, 'publish_future_post', [$post->ID]);

            return;
        }

        // wp_publish_post() returns no meaningful value.
        wp_publish_post($post->ID);
    }

    function wp_resolve_post_date($post_date = '', $post_date_gmt = '')
    {
        // If the date is empty, set the date to now.
        if(empty($post_date) || '0000-00-00 00:00:00' === $post_date)
        {
            if(empty($post_date_gmt) || '0000-00-00 00:00:00' === $post_date_gmt)
            {
                $post_date = current_time('mysql');
            }
            else
            {
                $post_date = get_date_from_gmt($post_date_gmt);
            }
        }

        // Validate the date.
        $month = (int) substr($post_date, 5, 2);
        $day = (int) substr($post_date, 8, 2);
        $year = (int) substr($post_date, 0, 4);

        $valid_date = wp_checkdate($month, $day, $year, $post_date);

        if(! $valid_date)
        {
            return false;
        }

        return $post_date;
    }

    function wp_unique_post_slug($slug, $post_id, $post_status, $post_type, $post_parent)
    {
        if(
            in_array($post_status, [
                'draft',
                'pending',
                'auto-draft'
            ],       true) || ('inherit' === $post_status && 'revision' === $post_type) || 'user_request' === $post_type
        )
        {
            return $slug;
        }

        $override_slug = apply_filters('pre_wp_unique_post_slug', null, $slug, $post_id, $post_status, $post_type, $post_parent);
        if(null !== $override_slug)
        {
            return $override_slug;
        }

        global $wpdb, $wp_rewrite;

        $original_slug = $slug;

        $feeds = $wp_rewrite->feeds;
        if(! is_array($feeds))
        {
            $feeds = [];
        }

        if('attachment' === $post_type)
        {
            // Attachment slugs must be unique across all types.
            $check_sql = "SELECT post_name FROM $wpdb->posts WHERE post_name = %s AND ID != %d LIMIT 1";
            $post_name_check = $wpdb->get_var($wpdb->prepare($check_sql, $slug, $post_id));

            $is_bad_attachment_slug = apply_filters('wp_unique_post_slug_is_bad_attachment_slug', false, $slug);

            if($post_name_check || in_array($slug, $feeds, true) || 'embed' === $slug || $is_bad_attachment_slug)
            {
                $suffix = 2;
                do
                {
                    $alt_post_name = _truncate_post_slug($slug, 200 - (strlen($suffix) + 1))."-$suffix";
                    $post_name_check = $wpdb->get_var($wpdb->prepare($check_sql, $alt_post_name, $post_id));
                    ++$suffix;
                }
                while($post_name_check);
                $slug = $alt_post_name;
            }
        }
        elseif(is_post_type_hierarchical($post_type))
        {
            if('nav_menu_item' === $post_type)
            {
                return $slug;
            }

            /*
		 * Page slugs must be unique within their own trees. Pages are in a separate
		 * namespace than posts so page slugs are allowed to overlap post slugs.
		 */
            $check_sql = "SELECT post_name FROM $wpdb->posts WHERE post_name = %s AND post_type IN ( %s, 'attachment' ) AND ID != %d AND post_parent = %d LIMIT 1";
            $post_name_check = $wpdb->get_var($wpdb->prepare($check_sql, $slug, $post_type, $post_id, $post_parent));

            $is_bad_hierarchical_slug = apply_filters('wp_unique_post_slug_is_bad_hierarchical_slug', false, $slug, $post_type, $post_parent);

            if($post_name_check || in_array($slug, $feeds, true) || 'embed' === $slug || preg_match("@^($wp_rewrite->pagination_base)?\d+$@", $slug) || $is_bad_hierarchical_slug)
            {
                $suffix = 2;
                do
                {
                    $alt_post_name = _truncate_post_slug($slug, 200 - (strlen($suffix) + 1))."-$suffix";
                    $post_name_check = $wpdb->get_var($wpdb->prepare($check_sql, $alt_post_name, $post_type, $post_id, $post_parent));
                    ++$suffix;
                }
                while($post_name_check);
                $slug = $alt_post_name;
            }
        }
        else
        {
            // Post slugs must be unique across all posts.
            $check_sql = "SELECT post_name FROM $wpdb->posts WHERE post_name = %s AND post_type = %s AND ID != %d LIMIT 1";
            $post_name_check = $wpdb->get_var($wpdb->prepare($check_sql, $slug, $post_type, $post_id));

            $post = get_post($post_id);

            // Prevent new post slugs that could result in URLs that conflict with date archives.
            $conflicts_with_date_archive = false;
            if('post' === $post_type && (! $post || $post->post_name !== $slug) && preg_match('/^[0-9]+$/', $slug))
            {
                $slug_num = (int) $slug;

                if($slug_num)
                {
                    $permastructs = array_values(array_filter(explode('/', get_option('permalink_structure'))));
                    $postname_index = array_search('%postname%', $permastructs, true);

                    /*
				* Potential date clashes are as follows:
				*
				* - Any integer in the first permastruct position could be a year.
				* - An integer between 1 and 12 that follows 'year' conflicts with 'monthnum'.
				* - An integer between 1 and 31 that follows 'monthnum' conflicts with 'day'.
				*/
                    if(0 === $postname_index || ($postname_index && '%year%' === $permastructs[$postname_index - 1] && 13 > $slug_num) || ($postname_index && '%monthnum%' === $permastructs[$postname_index - 1] && 32 > $slug_num))
                    {
                        $conflicts_with_date_archive = true;
                    }
                }
            }

            $is_bad_flat_slug = apply_filters('wp_unique_post_slug_is_bad_flat_slug', false, $slug, $post_type);

            if($post_name_check || in_array($slug, $feeds, true) || 'embed' === $slug || $conflicts_with_date_archive || $is_bad_flat_slug)
            {
                $suffix = 2;
                do
                {
                    $alt_post_name = _truncate_post_slug($slug, 200 - (strlen($suffix) + 1))."-$suffix";
                    $post_name_check = $wpdb->get_var($wpdb->prepare($check_sql, $alt_post_name, $post_type, $post_id));
                    ++$suffix;
                }
                while($post_name_check);
                $slug = $alt_post_name;
            }
        }

        return apply_filters('wp_unique_post_slug', $slug, $post_id, $post_status, $post_type, $post_parent, $original_slug);
    }

    function _truncate_post_slug($slug, $length = 200)
    {
        if(strlen($slug) > $length)
        {
            $decoded_slug = urldecode($slug);
            if($decoded_slug === $slug)
            {
                $slug = substr($slug, 0, $length);
            }
            else
            {
                $slug = utf8_uri_encode($decoded_slug, $length, true);
            }
        }

        return rtrim($slug, '-');
    }

    function wp_add_post_tags($post_id = 0, $tags = '')
    {
        return wp_set_post_tags($post_id, $tags, true);
    }

    function wp_set_post_tags($post_id = 0, $tags = '', $append = false)
    {
        return wp_set_post_terms($post_id, $tags, 'post_tag', $append);
    }

    function wp_set_post_terms($post_id = 0, $terms = '', $taxonomy = 'post_tag', $append = false)
    {
        $post_id = (int) $post_id;

        if(! $post_id)
        {
            return false;
        }

        if(empty($terms))
        {
            $terms = [];
        }

        if(! is_array($terms))
        {
            $comma = _x(',', 'tag delimiter');
            if(',' !== $comma)
            {
                $terms = str_replace($comma, ',', $terms);
            }
            $terms = explode(',', trim($terms, " \n\t\r\0\x0B,"));
        }

        /*
	 * Hierarchical taxonomies must always pass IDs rather than names so that
	 * children with the same names but different parents aren't confused.
	 */
        if(is_taxonomy_hierarchical($taxonomy))
        {
            $terms = array_unique(array_map('intval', $terms));
        }

        return wp_set_object_terms($post_id, $terms, $taxonomy, $append);
    }

    function wp_set_post_categories($post_id = 0, $post_categories = [], $append = false)
    {
        $post_id = (int) $post_id;
        $post_type = get_post_type($post_id);
        $post_status = get_post_status($post_id);

        // If $post_categories isn't already an array, make it one.
        $post_categories = (array) $post_categories;

        if(empty($post_categories))
        {
            $default_category_post_types = apply_filters('default_category_post_types', []);

            // Regular posts always require a default category.
            $default_category_post_types = array_merge($default_category_post_types, ['post']);

            if(in_array($post_type, $default_category_post_types, true) && is_object_in_taxonomy($post_type, 'category') && 'auto-draft' !== $post_status)
            {
                $post_categories = [get_option('default_category')];
                $append = false;
            }
            else
            {
                $post_categories = [];
            }
        }
        elseif(1 === count($post_categories) && '' === reset($post_categories))
        {
            return true;
        }

        return wp_set_post_terms($post_id, $post_categories, 'category', $append);
    }

    function wp_transition_post_status($new_status, $old_status, $post)
    {
        do_action('transition_post_status', $new_status, $old_status, $post);

        do_action("{$old_status}_to_{$new_status}", $post);

        do_action("{$new_status}_{$post->post_type}", $post->ID, $post, $old_status);
    }

    function wp_after_insert_post($post, $update, $post_before)
    {
        $post = get_post($post);

        if(! $post)
        {
            return;
        }

        $post_id = $post->ID;

        do_action('wp_after_insert_post', $post_id, $post, $update, $post_before);
    }

//
// Comment, trackback, and pingback functions.
//

    function add_ping($post, $uri)
    {
        global $wpdb;

        $post = get_post($post);

        if(! $post)
        {
            return false;
        }

        $pung = trim($post->pinged);
        $pung = preg_split('/\s/', $pung);

        if(is_array($uri))
        {
            $pung = array_merge($pung, $uri);
        }
        else
        {
            $pung[] = $uri;
        }
        $new = implode("\n", $pung);

        $new = apply_filters('add_ping', $new);

        $return = $wpdb->update($wpdb->posts, ['pinged' => $new], ['ID' => $post->ID]);
        clean_post_cache($post->ID);

        return $return;
    }

    function get_enclosed($post_id)
    {
        $custom_fields = get_post_custom($post_id);
        $pung = [];
        if(! is_array($custom_fields))
        {
            return $pung;
        }

        foreach($custom_fields as $key => $val)
        {
            if('enclosure' !== $key || ! is_array($val))
            {
                continue;
            }
            foreach($val as $enc)
            {
                $enclosure = explode("\n", $enc);
                $pung[] = trim($enclosure[0]);
            }
        }

        return apply_filters('get_enclosed', $pung, $post_id);
    }

    function get_pung($post)
    {
        $post = get_post($post);

        if(! $post)
        {
            return false;
        }

        $pung = trim($post->pinged);
        $pung = preg_split('/\s/', $pung);

        return apply_filters('get_pung', $pung);
    }

    function get_to_ping($post)
    {
        $post = get_post($post);

        if(! $post)
        {
            return false;
        }

        $to_ping = sanitize_trackback_urls($post->to_ping);
        $to_ping = preg_split('/\s/', $to_ping, -1, PREG_SPLIT_NO_EMPTY);

        return apply_filters('get_to_ping', $to_ping);
    }

    function trackback_url_list($tb_list, $post_id)
    {
        if(! empty($tb_list))
        {
            // Get post data.
            $postdata = get_post($post_id, ARRAY_A);

            // Form an excerpt.
            $excerpt = strip_tags($postdata['post_excerpt'] ? $postdata['post_excerpt'] : $postdata['post_content']);

            if(strlen($excerpt) > 255)
            {
                $excerpt = substr($excerpt, 0, 252).'&hellip;';
            }

            $trackback_urls = explode(',', $tb_list);
            foreach((array) $trackback_urls as $tb_url)
            {
                $tb_url = trim($tb_url);
                trackback($tb_url, wp_unslash($postdata['post_title']), $excerpt, $post_id);
            }
        }
    }

//
// Page functions.
//

    function get_all_page_ids()
    {
        global $wpdb;

        $page_ids = wp_cache_get('all_page_ids', 'posts');
        if(! is_array($page_ids))
        {
            $page_ids = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = 'page'");
            wp_cache_add('all_page_ids', $page_ids, 'posts');
        }

        return $page_ids;
    }

    function get_page($page, $output = OBJECT, $filter = 'raw')
    {
        return get_post($page, $output, $filter);
    }

    function get_page_by_path($page_path, $output = OBJECT, $post_type = 'page')
    {
        global $wpdb;

        $last_changed = wp_cache_get_last_changed('posts');

        $hash = md5($page_path.serialize($post_type));
        $cache_key = "get_page_by_path:$hash:$last_changed";
        $cached = wp_cache_get($cache_key, 'post-queries');
        if(false !== $cached)
        {
            // Special case: '0' is a bad `$page_path`.
            if('0' === $cached || 0 === $cached)
            {
                return;
            }
            else
            {
                return get_post($cached, $output);
            }
        }

        $page_path = rawurlencode(urldecode($page_path));
        $page_path = str_replace('%2F', '/', $page_path);
        $page_path = str_replace('%20', ' ', $page_path);
        $parts = explode('/', trim($page_path, '/'));
        $parts = array_map('sanitize_title_for_query', $parts);
        $escaped_parts = esc_sql($parts);

        $in_string = "'".implode("','", $escaped_parts)."'";

        if(is_array($post_type))
        {
            $post_types = $post_type;
        }
        else
        {
            $post_types = [$post_type, 'attachment'];
        }

        $post_types = esc_sql($post_types);
        $post_type_in_string = "'".implode("','", $post_types)."'";
        $sql = "
		SELECT ID, post_name, post_parent, post_type
		FROM $wpdb->posts
		WHERE post_name IN ($in_string)
		AND post_type IN ($post_type_in_string)
	";

        $pages = $wpdb->get_results($sql, OBJECT_K);

        $revparts = array_reverse($parts);

        $foundid = 0;
        foreach((array) $pages as $page)
        {
            if($page->post_name == $revparts[0])
            {
                $count = 0;
                $p = $page;

                /*
			 * Loop through the given path parts from right to left,
			 * ensuring each matches the post ancestry.
			 */
                while(0 != $p->post_parent && isset($pages[$p->post_parent]))
                {
                    ++$count;
                    $parent = $pages[$p->post_parent];
                    if(! isset($revparts[$count]) || $parent->post_name != $revparts[$count])
                    {
                        break;
                    }
                    $p = $parent;
                }

                if(0 == $p->post_parent && count($revparts) === $count + 1 && $p->post_name == $revparts[$count])
                {
                    $foundid = $page->ID;
                    if($page->post_type == $post_type)
                    {
                        break;
                    }
                }
            }
        }

        // We cache misses as well as hits.
        wp_cache_set($cache_key, $foundid, 'post-queries');

        if($foundid)
        {
            return get_post($foundid, $output);
        }

        return null;
    }

    function get_page_children($page_id, $pages)
    {
        // Build a hash of ID -> children.
        $children = [];
        foreach((array) $pages as $page)
        {
            $children[(int) $page->post_parent][] = $page;
        }

        $page_list = [];

        // Start the search by looking at immediate children.
        if(isset($children[$page_id]))
        {
            // Always start at the end of the stack in order to preserve original `$pages` order.
            $to_look = array_reverse($children[$page_id]);

            while($to_look)
            {
                $p = array_pop($to_look);
                $page_list[] = $p;
                if(isset($children[$p->ID]))
                {
                    foreach(array_reverse($children[$p->ID]) as $child)
                    {
                        // Append to the `$to_look` stack to descend the tree.
                        $to_look[] = $child;
                    }
                }
            }
        }

        return $page_list;
    }

    function get_page_hierarchy(&$pages, $page_id = 0)
    {
        if(empty($pages))
        {
            return [];
        }

        $children = [];
        foreach((array) $pages as $p)
        {
            $parent_id = (int) $p->post_parent;
            $children[$parent_id][] = $p;
        }

        $result = [];
        _page_traverse_name($page_id, $children, $result);

        return $result;
    }

    function _page_traverse_name($page_id, &$children, &$result)
    {
        if(isset($children[$page_id]))
        {
            foreach((array) $children[$page_id] as $child)
            {
                $result[$child->ID] = $child->post_name;
                _page_traverse_name($child->ID, $children, $result);
            }
        }
    }

    function get_page_uri($page = 0)
    {
        if(! $page instanceof WP_Post)
        {
            $page = get_post($page);
        }

        if(! $page)
        {
            return false;
        }

        /** @noinspection NativeMemberUsageInspection */
        $uri = $page->post_name;

        /** @noinspection NativeMemberUsageInspection */
        foreach($page->ancestors as $parent)
        {
            $parent = get_post($parent);
            if($parent && $parent->post_name)
            {
                $uri = $parent->post_name.'/'.$uri;
            }
        }

        return apply_filters('get_page_uri', $uri, $page);
    }

    function get_pages($args = [])
    {
        $defaults = [
            'child_of' => 0,
            'sort_order' => 'ASC',
            'sort_column' => 'post_title',
            'hierarchical' => 1,
            'exclude' => [],
            'include' => [],
            'meta_key' => '',
            'meta_value' => '',
            'authors' => '',
            'parent' => -1,
            'exclude_tree' => [],
            'number' => '',
            'offset' => 0,
            'post_type' => 'page',
            'post_status' => 'publish',
        ];

        $parsed_args = wp_parse_args($args, $defaults);

        $number = (int) $parsed_args['number'];
        $offset = (int) $parsed_args['offset'];
        $child_of = (int) $parsed_args['child_of'];
        $hierarchical = $parsed_args['hierarchical'];
        $exclude = $parsed_args['exclude'];
        $meta_key = $parsed_args['meta_key'];
        $meta_value = $parsed_args['meta_value'];
        $parent = $parsed_args['parent'];
        $post_status = $parsed_args['post_status'];

        // Make sure the post type is hierarchical.
        $hierarchical_post_types = get_post_types(['hierarchical' => true]);
        if(! in_array($parsed_args['post_type'], $hierarchical_post_types, true))
        {
            return false;
        }

        if($parent > 0 && ! $child_of)
        {
            $hierarchical = false;
        }

        // Make sure we have a valid post status.
        if(! is_array($post_status))
        {
            $post_status = explode(',', $post_status);
        }
        if(array_diff($post_status, get_post_stati()))
        {
            return false;
        }

        $query_args = [
            'orderby' => 'post_title',
            'order' => 'ASC',
            'post__not_in' => wp_parse_id_list($exclude),
            'meta_key' => $meta_key,
            'meta_value' => $meta_value,
            'posts_per_page' => -1,
            'offset' => $offset,
            'post_type' => $parsed_args['post_type'],
            'post_status' => $post_status,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
        ];

        if(! empty($parsed_args['include']))
        {
            $child_of = 0; // Ignore child_of, parent, exclude, meta_key, and meta_value params if using include.
            $parent = -1;
            unset($query_args['post__not_in'], $query_args['meta_key'], $query_args['meta_value']);
            $hierarchical = false;
            $query_args['post__in'] = wp_parse_id_list($parsed_args['include']);
        }

        if(! empty($parsed_args['authors']))
        {
            $post_authors = wp_parse_list($parsed_args['authors']);

            if(! empty($post_authors))
            {
                $query_args['author__in'] = [];
                foreach($post_authors as $post_author)
                {
                    // Do we have an author id or an author login?
                    if(0 === (int) $post_author)
                    {
                        $post_author = get_user_by('login', $post_author);
                        if(empty($post_author))
                        {
                            continue;
                        }
                        if(empty($post_author->ID))
                        {
                            continue;
                        }
                        $post_author = $post_author->ID;
                    }
                    $query_args['author__in'][] = (int) $post_author;
                }
            }
        }

        if(is_array($parent))
        {
            $post_parent__in = array_map('absint', (array) $parent);
            if(! empty($post_parent__in))
            {
                $query_args['post_parent__in'] = $post_parent__in;
            }
        }
        elseif($parent >= 0)
        {
            $query_args['post_parent'] = $parent;
        }

        /*
	 * Maintain backward compatibility for `sort_column` key.
	 * Additionally to `WP_Query`, it has been supporting the `post_modified_gmt` field, so this logic will translate
	 * it to `post_modified` which should result in the same order given the two dates in the fields match.
	 */
        $orderby = wp_parse_list($parsed_args['sort_column']);
        $orderby = array_map(static function($orderby_field)
        {
            $orderby_field = trim($orderby_field);
            if('post_modified_gmt' === $orderby_field || 'modified_gmt' === $orderby_field)
            {
                $orderby_field = str_replace('_gmt', '', $orderby_field);
            }

            return $orderby_field;
        }, $orderby);
        if($orderby)
        {
            $query_args['orderby'] = array_fill_keys($orderby, $parsed_args['sort_order']);
        }

        $order = $parsed_args['sort_order'];
        if($order)
        {
            $query_args['order'] = $order;
        }

        if(! empty($number))
        {
            $query_args['posts_per_page'] = $number;
        }

        $query_args = apply_filters('get_pages_query_args', $query_args, $parsed_args);

        $pages = new WP_Query();
        $pages = $pages->query($query_args);

        if($child_of || $hierarchical)
        {
            $pages = get_page_children($child_of, $pages);
        }

        if(! empty($parsed_args['exclude_tree']))
        {
            $exclude = wp_parse_id_list($parsed_args['exclude_tree']);
            foreach($exclude as $id)
            {
                $children = get_page_children($id, $pages);
                foreach($children as $child)
                {
                    $exclude[] = $child->ID;
                }
            }

            $num_pages = count($pages);
            for($i = 0; $i < $num_pages; $i++)
            {
                if(in_array($pages[$i]->ID, $exclude, true))
                {
                    unset($pages[$i]);
                }
            }
        }

        return apply_filters('get_pages', $pages, $parsed_args);
    }

//
// Attachment functions.
//

    function is_local_attachment($url)
    {
        if(! str_contains($url, home_url()))
        {
            return false;
        }
        if(str_contains($url, home_url('/?attachment_id=')))
        {
            return true;
        }

        $id = url_to_postid($url);
        if($id)
        {
            $post = get_post($id);
            if('attachment' === $post->post_type)
            {
                return true;
            }
        }

        return false;
    }

    function wp_insert_attachment(
        $args, $file = false, $parent_post_id = 0, $wp_error = false, $fire_after_hooks = true
    ) {
        $defaults = [
            'file' => $file,
            'post_parent' => 0,
        ];

        $data = wp_parse_args($args, $defaults);

        if(! empty($parent_post_id))
        {
            $data['post_parent'] = $parent_post_id;
        }

        $data['post_type'] = 'attachment';

        return wp_insert_post($data, $wp_error, $fire_after_hooks);
    }

    function wp_delete_attachment($post_id, $force_delete = false)
    {
        global $wpdb;

        $post = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID = %d", $post_id));

        if(! $post)
        {
            return $post;
        }

        $post = get_post($post);

        if('attachment' !== $post->post_type)
        {
            return false;
        }

        if(! $force_delete && EMPTY_TRASH_DAYS && MEDIA_TRASH && 'trash' !== $post->post_status)
        {
            return wp_trash_post($post_id);
        }

        $check = apply_filters('pre_delete_attachment', null, $post, $force_delete);
        if(null !== $check)
        {
            return $check;
        }

        delete_post_meta($post_id, '_wp_trash_meta_status');
        delete_post_meta($post_id, '_wp_trash_meta_time');

        $meta = wp_get_attachment_metadata($post_id);
        $backup_sizes = get_post_meta($post->ID, '_wp_attachment_backup_sizes', true);
        $file = get_attached_file($post_id);

        if(is_multisite() && is_string($file) && ! empty($file))
        {
            clean_dirsize_cache($file);
        }

        do_action('delete_attachment', $post_id, $post);

        wp_delete_object_term_relationships($post_id, ['category', 'post_tag']);
        wp_delete_object_term_relationships($post_id, get_object_taxonomies($post->post_type));

        // Delete all for any posts.
        delete_metadata('post', null, '_thumbnail_id', $post_id, true);

        wp_defer_comment_counting(true);

        $comment_ids = $wpdb->get_col($wpdb->prepare("SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID = %d ORDER BY comment_ID DESC", $post_id));
        foreach($comment_ids as $comment_id)
        {
            wp_delete_comment($comment_id, true);
        }

        wp_defer_comment_counting(false);

        $post_meta_ids = $wpdb->get_col($wpdb->prepare("SELECT meta_id FROM $wpdb->postmeta WHERE post_id = %d ", $post_id));
        foreach($post_meta_ids as $mid)
        {
            delete_metadata_by_mid('post', $mid);
        }

        do_action('delete_post', $post_id, $post);
        $result = $wpdb->delete($wpdb->posts, ['ID' => $post_id]);
        if(! $result)
        {
            return false;
        }

        do_action('deleted_post', $post_id, $post);

        wp_delete_attachment_files($post_id, $meta, $backup_sizes, $file);

        clean_post_cache($post);

        return $post;
    }

    function wp_delete_attachment_files($post_id, $meta, $backup_sizes, $file)
    {
        global $wpdb;

        $uploadpath = wp_get_upload_dir();
        $deleted = true;

        if(! empty($meta['thumb']) && ! $wpdb->get_row($wpdb->prepare("SELECT meta_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attachment_metadata' AND meta_value LIKE %s AND post_id <> %d", '%'.$wpdb->esc_like($meta['thumb']).'%', $post_id)))
        {
            $thumbfile = str_replace(wp_basename($file), $meta['thumb'], $file);

            if(! empty($thumbfile))
            {
                $thumbfile = path_join($uploadpath['basedir'], $thumbfile);
                $thumbdir = path_join($uploadpath['basedir'], dirname($file));

                if(! wp_delete_file_from_directory($thumbfile, $thumbdir))
                {
                    $deleted = false;
                }
            }
        }

        // Remove intermediate and backup images if there are any.
        if(isset($meta['sizes']) && is_array($meta['sizes']))
        {
            $intermediate_dir = path_join($uploadpath['basedir'], dirname($file));

            foreach($meta['sizes'] as $size => $sizeinfo)
            {
                $intermediate_file = str_replace(wp_basename($file), $sizeinfo['file'], $file);

                if(! empty($intermediate_file))
                {
                    $intermediate_file = path_join($uploadpath['basedir'], $intermediate_file);

                    if(! wp_delete_file_from_directory($intermediate_file, $intermediate_dir))
                    {
                        $deleted = false;
                    }
                }
            }
        }

        if(! empty($meta['original_image']))
        {
            if(empty($intermediate_dir))
            {
                $intermediate_dir = path_join($uploadpath['basedir'], dirname($file));
            }

            $original_image = str_replace(wp_basename($file), $meta['original_image'], $file);

            if(! empty($original_image))
            {
                $original_image = path_join($uploadpath['basedir'], $original_image);

                if(! wp_delete_file_from_directory($original_image, $intermediate_dir))
                {
                    $deleted = false;
                }
            }
        }

        if(is_array($backup_sizes))
        {
            $del_dir = path_join($uploadpath['basedir'], dirname($meta['file']));

            foreach($backup_sizes as $size)
            {
                $del_file = path_join(dirname($meta['file']), $size['file']);

                if(! empty($del_file))
                {
                    $del_file = path_join($uploadpath['basedir'], $del_file);

                    if(! wp_delete_file_from_directory($del_file, $del_dir))
                    {
                        $deleted = false;
                    }
                }
            }
        }

        if(! wp_delete_file_from_directory($file, $uploadpath['basedir']))
        {
            $deleted = false;
        }

        return $deleted;
    }

    function wp_get_attachment_metadata($attachment_id = 0, $unfiltered = false)
    {
        $attachment_id = (int) $attachment_id;

        if(! $attachment_id)
        {
            $post = get_post();

            if(! $post)
            {
                return false;
            }

            $attachment_id = $post->ID;
        }

        $data = get_post_meta($attachment_id, '_wp_attachment_metadata', true);

        if(! $data)
        {
            return false;
        }

        if($unfiltered)
        {
            return $data;
        }

        return apply_filters('wp_get_attachment_metadata', $data, $attachment_id);
    }

    function wp_update_attachment_metadata($attachment_id, $data)
    {
        $attachment_id = (int) $attachment_id;

        $post = get_post($attachment_id);

        if(! $post)
        {
            return false;
        }

        $data = apply_filters('wp_update_attachment_metadata', $data, $post->ID);
        if($data)
        {
            return update_post_meta($post->ID, '_wp_attachment_metadata', $data);
        }
        else
        {
            return delete_post_meta($post->ID, '_wp_attachment_metadata');
        }
    }

    function wp_get_attachment_url($attachment_id = 0)
    {
        global $pagenow;

        $attachment_id = (int) $attachment_id;

        $post = get_post($attachment_id);

        if(! $post || 'attachment' !== $post->post_type)
        {
            return false;
        }

        $url = '';
        // Get attached file.
        $file = get_post_meta($post->ID, '_wp_attached_file', true);
        if($file)
        {
            // Get upload directory.
            $uploads = wp_get_upload_dir();
            if($uploads && false === $uploads['error'])
            {
                // Check that the upload base exists in the file location.
                if(str_starts_with($file, $uploads['basedir']))
                {
                    // Replace file location with url location.
                    $url = str_replace($uploads['basedir'], $uploads['baseurl'], $file);
                }
                elseif(str_contains($file, 'wp-content/uploads'))
                {
                    // Get the directory name relative to the basedir (back compat for pre-2.7 uploads).
                    $url = trailingslashit($uploads['baseurl'].'/'._wp_get_attachment_relative_path($file)).wp_basename($file);
                }
                else
                {
                    // It's a newly-uploaded file, therefore $file is relative to the basedir.
                    $url = $uploads['baseurl']."/$file";
                }
            }
        }

        /*
	 * If any of the above options failed, Fallback on the GUID as used pre-2.7,
	 * not recommended to rely upon this.
	 */
        if(! $url)
        {
            $url = get_the_guid($post->ID);
        }

        // On SSL front end, URLs should be HTTPS.
        if(is_ssl() && ! is_admin() && 'wp-login.php' !== $pagenow)
        {
            $url = set_url_scheme($url);
        }

        $url = apply_filters('wp_get_attachment_url', $url, $post->ID);

        if(! $url)
        {
            return false;
        }

        return $url;
    }

    function wp_get_attachment_caption($post_id = 0)
    {
        $post_id = (int) $post_id;
        $post = get_post($post_id);

        if(! $post || 'attachment' !== $post->post_type)
        {
            return false;
        }

        $caption = $post->post_excerpt;

        return apply_filters('wp_get_attachment_caption', $caption, $post->ID);
    }

    function wp_get_attachment_thumb_url($post_id = 0)
    {
        $post_id = (int) $post_id;

        /*
	 * This uses image_downsize() which also looks for the (very) old format $image_meta['thumb']
	 * when the newer format $image_meta['sizes']['thumbnail'] doesn't exist.
	 */
        $thumbnail_url = wp_get_attachment_image_url($post_id, 'thumbnail');

        if(empty($thumbnail_url))
        {
            return false;
        }

        return apply_filters('wp_get_attachment_thumb_url', $thumbnail_url, $post_id);
    }

    function wp_attachment_is($type, $post = null)
    {
        $post = get_post($post);

        if(! $post)
        {
            return false;
        }

        $file = get_attached_file($post->ID);

        if(! $file)
        {
            return false;
        }

        if(str_starts_with($post->post_mime_type, $type.'/'))
        {
            return true;
        }

        $check = wp_check_filetype($file);

        if(empty($check['ext']))
        {
            return false;
        }

        $ext = $check['ext'];

        if('import' !== $post->post_mime_type)
        {
            return $type === $ext;
        }

        switch($type)
        {
            case 'image':
                $image_exts = ['jpg', 'jpeg', 'jpe', 'gif', 'png', 'webp'];

                return in_array($ext, $image_exts, true);

            case 'audio':
                return in_array($ext, wp_get_audio_extensions(), true);

            case 'video':
                return in_array($ext, wp_get_video_extensions(), true);

            default:
                return $type === $ext;
        }
    }

    function wp_attachment_is_image($post = null)
    {
        return wp_attachment_is('image', $post);
    }

    function wp_mime_type_icon($mime = 0)
    {
        if(! is_numeric($mime))
        {
            $icon = wp_cache_get("mime_type_icon_$mime");
        }

        $post_id = 0;
        if(empty($icon))
        {
            $post_mimes = [];
            if(is_numeric($mime))
            {
                $mime = (int) $mime;
                $post = get_post($mime);
                if($post)
                {
                    $post_id = (int) $post->ID;
                    $file = get_attached_file($post_id);
                    $ext = preg_replace('/^.+?\.([^.]+)$/', '$1', $file);
                    if(! empty($ext))
                    {
                        $post_mimes[] = $ext;
                        $ext_type = wp_ext2type($ext);
                        if($ext_type)
                        {
                            $post_mimes[] = $ext_type;
                        }
                    }
                    $mime = $post->post_mime_type;
                }
                else
                {
                    $mime = 0;
                }
            }
            else
            {
                $post_mimes[] = $mime;
            }

            $icon_files = wp_cache_get('icon_files');

            if(! is_array($icon_files))
            {
                $icon_dir = apply_filters('icon_dir', ABSPATH.WPINC.'/images/media');

                $icon_dir_uri = apply_filters('icon_dir_uri', includes_url('images/media'));

                $dirs = apply_filters('icon_dirs', [$icon_dir => $icon_dir_uri]);
                $icon_files = [];
                while($dirs)
                {
                    $keys = array_keys($dirs);
                    $dir = array_shift($keys);
                    $uri = array_shift($dirs);
                    $dh = opendir($dir);
                    if($dh)
                    {
                        while(false !== $file = readdir($dh))
                        {
                            $file = wp_basename($file);
                            if(str_starts_with($file, '.'))
                            {
                                continue;
                            }

                            $ext = strtolower(substr($file, -4));
                            if(! in_array($ext, ['.png', '.gif', '.jpg'], true))
                            {
                                if(is_dir("$dir/$file"))
                                {
                                    $dirs["$dir/$file"] = "$uri/$file";
                                }
                                continue;
                            }
                            $icon_files["$dir/$file"] = "$uri/$file";
                        }
                        closedir($dh);
                    }
                }
                wp_cache_add('icon_files', $icon_files, 'default', 600);
            }

            $types = [];
            // Icon wp_basename - extension = MIME wildcard.
            foreach($icon_files as $file => $uri)
            {
                $types[preg_replace('/^([^.]*).*$/', '$1', wp_basename($file))] =& $icon_files[$file];
            }

            if(! empty($mime))
            {
                $post_mimes[] = substr($mime, 0, strpos($mime, '/'));
                $post_mimes[] = substr($mime, strpos($mime, '/') + 1);
                $post_mimes[] = str_replace('/', '_', $mime);
            }

            $matches = wp_match_mime_types(array_keys($types), $post_mimes);
            $matches['default'] = ['default'];

            foreach($matches as $match => $wilds)
            {
                foreach($wilds as $wild)
                {
                    if(! isset($types[$wild]))
                    {
                        continue;
                    }

                    $icon = $types[$wild];
                    if(! is_numeric($mime))
                    {
                        wp_cache_add("mime_type_icon_$mime", $icon);
                    }
                    break 2;
                }
            }
        }

        return apply_filters('wp_mime_type_icon', $icon, $mime, $post_id);
    }

    function wp_check_for_changed_slugs($post_id, $post, $post_before)
    {
        // Don't bother if it hasn't changed.
        // We're only concerned with published, non-hierarchical objects.
        if($post->post_name == $post_before->post_name || ! ('publish' === $post->post_status || ('attachment' === get_post_type($post) && 'inherit' === $post->post_status)) || is_post_type_hierarchical($post->post_type))
        {
            return;
        }

        $old_slugs = (array) get_post_meta($post_id, '_wp_old_slug');

        // If we haven't added this old slug before, add it now.
        if(! empty($post_before->post_name) && ! in_array($post_before->post_name, $old_slugs, true))
        {
            add_post_meta($post_id, '_wp_old_slug', $post_before->post_name);
        }

        // If the new slug was used previously, delete it from the list.
        if(in_array($post->post_name, $old_slugs, true))
        {
            delete_post_meta($post_id, '_wp_old_slug', $post->post_name);
        }
    }

    function wp_check_for_changed_dates($post_id, $post, $post_before)
    {
        $previous_date = gmdate('Y-m-d', strtotime($post_before->post_date));
        $new_date = gmdate('Y-m-d', strtotime($post->post_date));

        // Don't bother if it hasn't changed.
        // We're only concerned with published, non-hierarchical objects.
        if($new_date == $previous_date || ! ('publish' === $post->post_status || ('attachment' === get_post_type($post) && 'inherit' === $post->post_status)) || is_post_type_hierarchical($post->post_type))
        {
            return;
        }

        $old_dates = (array) get_post_meta($post_id, '_wp_old_date');

        // If we haven't added this old date before, add it now.
        if(! empty($previous_date) && ! in_array($previous_date, $old_dates, true))
        {
            add_post_meta($post_id, '_wp_old_date', $previous_date);
        }

        // If the new slug was used previously, delete it from the list.
        if(in_array($new_date, $old_dates, true))
        {
            delete_post_meta($post_id, '_wp_old_date', $new_date);
        }
    }

    function get_private_posts_cap_sql($post_type)
    {
        return get_posts_by_author_sql($post_type, false);
    }

    function get_posts_by_author_sql($post_type, $full = true, $post_author = null, $public_only = false)
    {
        global $wpdb;

        if(is_array($post_type))
        {
            $post_types = $post_type;
        }
        else
        {
            $post_types = [$post_type];
        }

        $post_type_clauses = [];
        foreach($post_types as $post_type)
        {
            $post_type_obj = get_post_type_object($post_type);

            if(! $post_type_obj)
            {
                continue;
            }

            $cap = apply_filters_deprecated('pub_priv_sql_capability', [''], '3.2.0');

            if(! $cap)
            {
                $cap = current_user_can($post_type_obj->cap->read_private_posts);
            }

            // Only need to check the cap if $public_only is false.
            $post_status_sql = "post_status = 'publish'";

            if(false === $public_only)
            {
                if($cap)
                {
                    // Does the user have the capability to view private posts? Guess so.
                    $post_status_sql .= " OR post_status = 'private'";
                }
                elseif(is_user_logged_in())
                {
                    // Users can view their own private posts.
                    $id = get_current_user_id();
                    if(null === $post_author || ! $full)
                    {
                        $post_status_sql .= " OR post_status = 'private' AND post_author = $id";
                    }
                    elseif($id == (int) $post_author)
                    {
                        $post_status_sql .= " OR post_status = 'private'";
                    } // Else none.
                } // Else none.
            }

            $post_type_clauses[] = "( post_type = '".$post_type."' AND ( $post_status_sql ) )";
        }

        if(empty($post_type_clauses))
        {
            if($full)
            {
                return 'WHERE 1 = 0';
            }

            return '1 = 0';
        }

        $sql = '( '.implode(' OR ', $post_type_clauses).' )';

        if(null !== $post_author)
        {
            $sql .= $wpdb->prepare(' AND post_author = %d', $post_author);
        }

        if($full)
        {
            $sql = 'WHERE '.$sql;
        }

        return $sql;
    }

    function get_lastpostdate($timezone = 'server', $post_type = 'any')
    {
        $lastpostdate = _get_last_post_time($timezone, 'date', $post_type);

        return apply_filters('get_lastpostdate', $lastpostdate, $timezone, $post_type);
    }

    function get_lastpostmodified($timezone = 'server', $post_type = 'any')
    {
        $lastpostmodified = apply_filters('pre_get_lastpostmodified', false, $timezone, $post_type);

        if(false !== $lastpostmodified)
        {
            return $lastpostmodified;
        }

        $lastpostmodified = _get_last_post_time($timezone, 'modified', $post_type);
        $lastpostdate = get_lastpostdate($timezone, $post_type);

        if($lastpostdate > $lastpostmodified)
        {
            $lastpostmodified = $lastpostdate;
        }

        return apply_filters('get_lastpostmodified', $lastpostmodified, $timezone, $post_type);
    }

    function _get_last_post_time($timezone, $field, $post_type = 'any')
    {
        global $wpdb;

        if(! in_array($field, ['date', 'modified'], true))
        {
            return false;
        }

        $timezone = strtolower($timezone);

        $key = "lastpost{$field}:$timezone";
        if('any' !== $post_type)
        {
            $key .= ':'.sanitize_key($post_type);
        }

        $date = wp_cache_get($key, 'timeinfo');
        if(false !== $date)
        {
            return $date;
        }

        if('any' === $post_type)
        {
            $post_types = get_post_types(['public' => true]);
            array_walk($post_types, [$wpdb, 'escape_by_ref']);
            $post_types = "'".implode("', '", $post_types)."'";
        }
        else
        {
            $post_types = "'".sanitize_key($post_type)."'";
        }

        switch($timezone)
        {
            case 'gmt':
                $date = $wpdb->get_var("SELECT post_{$field}_gmt FROM $wpdb->posts WHERE post_status = 'publish' AND post_type IN ({$post_types}) ORDER BY post_{$field}_gmt DESC LIMIT 1");
                break;
            case 'blog':
                $date = $wpdb->get_var("SELECT post_{$field} FROM $wpdb->posts WHERE post_status = 'publish' AND post_type IN ({$post_types}) ORDER BY post_{$field}_gmt DESC LIMIT 1");
                break;
            case 'server':
                $add_seconds_server = gmdate('Z');
                $date = $wpdb->get_var("SELECT DATE_ADD(post_{$field}_gmt, INTERVAL '$add_seconds_server' SECOND) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type IN ({$post_types}) ORDER BY post_{$field}_gmt DESC LIMIT 1");
                break;
        }

        if($date)
        {
            wp_cache_set($key, $date, 'timeinfo');

            return $date;
        }

        return false;
    }

    function update_post_cache(&$posts)
    {
        if(! $posts)
        {
            return;
        }

        $data = [];
        foreach($posts as $post)
        {
            if(empty($post->filter) || 'raw' !== $post->filter)
            {
                $post = sanitize_post($post, 'raw');
            }
            $data[$post->ID] = $post;
        }
        wp_cache_add_multiple($data, 'posts');
    }

    function clean_post_cache($post)
    {
        global $_wp_suspend_cache_invalidation;

        if(! empty($_wp_suspend_cache_invalidation))
        {
            return;
        }

        $post = get_post($post);

        if(! $post)
        {
            return;
        }

        wp_cache_delete($post->ID, 'posts');
        wp_cache_delete($post->ID, 'post_meta');

        clean_object_term_cache($post->ID, $post->post_type);

        wp_cache_delete('wp_get_archives', 'general');

        do_action('clean_post_cache', $post->ID, $post);

        if('page' === $post->post_type)
        {
            wp_cache_delete('all_page_ids', 'posts');

            do_action('clean_page_cache', $post->ID);
        }

        wp_cache_set_posts_last_changed();
    }

    function update_post_caches(&$posts, $post_type = 'post', $update_term_cache = true, $update_meta_cache = true)
    {
        // No point in doing all this work if we didn't match any posts.
        if(! $posts)
        {
            return;
        }

        update_post_cache($posts);

        $post_ids = [];
        foreach($posts as $post)
        {
            $post_ids[] = $post->ID;
        }

        if(! $post_type)
        {
            $post_type = 'any';
        }

        if($update_term_cache)
        {
            if(is_array($post_type))
            {
                $ptypes = $post_type;
            }
            elseif('any' === $post_type)
            {
                $ptypes = [];
                // Just use the post_types in the supplied posts.
                foreach($posts as $post)
                {
                    $ptypes[] = $post->post_type;
                }
                $ptypes = array_unique($ptypes);
            }
            else
            {
                $ptypes = [$post_type];
            }

            if(! empty($ptypes))
            {
                update_object_term_cache($post_ids, $ptypes);
            }
        }

        if($update_meta_cache)
        {
            update_postmeta_cache($post_ids);
        }
    }

    function update_post_author_caches($posts)
    {
        /*
	 * cache_users() is a pluggable function so is not available prior
	 * to the `plugins_loaded` hook firing. This is to ensure against
	 * fatal errors when the function is not available.
	 */
        if(! function_exists('cache_users'))
        {
            return;
        }

        $author_ids = wp_list_pluck($posts, 'post_author');
        $author_ids = array_map('absint', $author_ids);
        $author_ids = array_unique(array_filter($author_ids));

        cache_users($author_ids);
    }

    function update_post_parent_caches($posts)
    {
        $parent_ids = wp_list_pluck($posts, 'post_parent');
        $parent_ids = array_map('absint', $parent_ids);
        $parent_ids = array_unique(array_filter($parent_ids));

        if(! empty($parent_ids))
        {
            _prime_post_caches($parent_ids, false);
        }
    }

    function update_postmeta_cache($post_ids)
    {
        return update_meta_cache('post', $post_ids);
    }

    function clean_attachment_cache($id, $clean_terms = false)
    {
        global $_wp_suspend_cache_invalidation;

        if(! empty($_wp_suspend_cache_invalidation))
        {
            return;
        }

        $id = (int) $id;

        wp_cache_delete($id, 'posts');
        wp_cache_delete($id, 'post_meta');

        if($clean_terms)
        {
            clean_object_term_cache($id, 'attachment');
        }

        do_action('clean_attachment_cache', $id);
    }

//
// Hooks.
//

    function _transition_post_status($new_status, $old_status, $post)
    {
        global $wpdb;

        if('publish' !== $old_status && 'publish' === $new_status)
        {
            // Reset GUID if transitioning to publish and it is empty.
            if('' === get_the_guid($post->ID))
            {
                $wpdb->update($wpdb->posts, ['guid' => get_permalink($post->ID)], ['ID' => $post->ID]);
            }

            do_action_deprecated('private_to_published', [$post->ID], '2.3.0', 'private_to_publish');
        }

        // If published posts changed clear the lastpostmodified cache.
        if('publish' === $new_status || 'publish' === $old_status)
        {
            foreach(['server', 'gmt', 'blog'] as $timezone)
            {
                wp_cache_delete("lastpostmodified:$timezone", 'timeinfo');
                wp_cache_delete("lastpostdate:$timezone", 'timeinfo');
                wp_cache_delete("lastpostdate:$timezone:{$post->post_type}", 'timeinfo');
            }
        }

        if($new_status !== $old_status)
        {
            wp_cache_delete(_count_posts_cache_key($post->post_type), 'counts');
            wp_cache_delete(_count_posts_cache_key($post->post_type, 'readable'), 'counts');
        }

        // Always clears the hook in case the post status bounced from future to draft.
        wp_clear_scheduled_hook('publish_future_post', [$post->ID]);
    }

    function _future_post_hook($deprecated, $post)
    {
        wp_clear_scheduled_hook('publish_future_post', [$post->ID]);
        wp_schedule_single_event(strtotime(get_gmt_from_date($post->post_date).' GMT'), 'publish_future_post', [$post->ID]);
    }

    function _publish_post_hook($post_id)
    {
        if(defined('XMLRPC_REQUEST'))
        {
            do_action('xmlrpc_publish_post', $post_id);
        }

        if(defined('WP_IMPORTING'))
        {
            return;
        }

        if(get_option('default_pingback_flag'))
        {
            add_post_meta($post_id, '_pingme', '1', true);
        }
        add_post_meta($post_id, '_encloseme', '1', true);

        $to_ping = get_to_ping($post_id);
        if(! empty($to_ping))
        {
            add_post_meta($post_id, '_trackbackme', '1');
        }

        if(! wp_next_scheduled('do_pings'))
        {
            wp_schedule_single_event(time(), 'do_pings');
        }
    }

    function wp_get_post_parent_id($post = null)
    {
        $post = get_post($post);

        if(! $post || is_wp_error($post))
        {
            return false;
        }

        return (int) $post->post_parent;
    }

    function wp_check_post_hierarchy_for_loops($post_parent, $post_id)
    {
        // Nothing fancy here - bail.
        if(! $post_parent)
        {
            return 0;
        }

        // New post can't cause a loop.
        if(! $post_id)
        {
            return $post_parent;
        }

        // Can't be its own parent.
        if($post_parent == $post_id)
        {
            return 0;
        }

        // Now look for larger loops.
        $loop = wp_find_hierarchy_loop('wp_get_post_parent_id', $post_id, $post_parent);
        if(! $loop)
        {
            return $post_parent; // No loop.
        }

        // Setting $post_parent to the given value causes a loop.
        if(isset($loop[$post_id]))
        {
            return 0;
        }

        // There's a loop, but it doesn't contain $post_id. Break the loop.
        foreach(array_keys($loop) as $loop_member)
        {
            wp_update_post([
                               'ID' => $loop_member,
                               'post_parent' => 0,
                           ]);
        }

        return $post_parent;
    }

    function set_post_thumbnail($post, $thumbnail_id)
    {
        $post = get_post($post);
        $thumbnail_id = absint($thumbnail_id);
        if($post && $thumbnail_id && get_post($thumbnail_id))
        {
            if(wp_get_attachment_image($thumbnail_id, 'thumbnail'))
            {
                return update_post_meta($post->ID, '_thumbnail_id', $thumbnail_id);
            }
            else
            {
                return delete_post_meta($post->ID, '_thumbnail_id');
            }
        }

        return false;
    }

    function delete_post_thumbnail($post)
    {
        $post = get_post($post);
        if($post)
        {
            return delete_post_meta($post->ID, '_thumbnail_id');
        }

        return false;
    }

    function wp_delete_auto_drafts()
    {
        global $wpdb;

        // Cleanup old auto-drafts more than 7 days old.
        $old_posts = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_status = 'auto-draft' AND DATE_SUB( NOW(), INTERVAL 7 DAY ) > post_date");
        foreach((array) $old_posts as $delete)
        {
            // Force delete.
            wp_delete_post($delete, true);
        }
    }

    function wp_queue_posts_for_term_meta_lazyload($posts)
    {
        $post_type_taxonomies = [];
        $prime_post_terms = [];
        foreach($posts as $post)
        {
            if(! ($post instanceof WP_Post))
            {
                continue;
            }

            if(! isset($post_type_taxonomies[$post->post_type]))
            {
                $post_type_taxonomies[$post->post_type] = get_object_taxonomies($post->post_type);
            }

            foreach($post_type_taxonomies[$post->post_type] as $taxonomy)
            {
                $prime_post_terms[$taxonomy][] = $post->ID;
            }
        }

        $term_ids = [];
        if($prime_post_terms)
        {
            foreach($prime_post_terms as $taxonomy => $post_ids)
            {
                $cached_term_ids = wp_cache_get_multiple($post_ids, "{$taxonomy}_relationships");
                if(is_array($cached_term_ids))
                {
                    $cached_term_ids = array_filter($cached_term_ids);
                    foreach($cached_term_ids as $_term_ids)
                    {
                        // Backward compatibility for if a plugin is putting objects into the cache, rather than IDs.
                        foreach($_term_ids as $term_id)
                        {
                            if(is_numeric($term_id))
                            {
                                $term_ids[] = (int) $term_id;
                            }
                            elseif(isset($term_id->term_id))
                            {
                                $term_ids[] = (int) $term_id->term_id;
                            }
                        }
                    }
                }
            }
            $term_ids = array_unique($term_ids);
        }

        wp_lazyload_term_meta($term_ids);
    }

    function _update_term_count_on_transition_post_status($new_status, $old_status, $post)
    {
        // Update counts for the post's terms.
        foreach((array) get_object_taxonomies($post->post_type) as $taxonomy)
        {
            $tt_ids = wp_get_object_terms($post->ID, $taxonomy, ['fields' => 'tt_ids']);
            wp_update_term_count($tt_ids, $taxonomy);
        }
    }

    function _prime_post_caches($ids, $update_term_cache = true, $update_meta_cache = true)
    {
        global $wpdb;

        $non_cached_ids = _get_non_cached_ids($ids, 'posts');
        if(! empty($non_cached_ids))
        {
            $fresh_posts = $wpdb->get_results(sprintf("SELECT $wpdb->posts.* FROM $wpdb->posts WHERE ID IN (%s)", implode(',', $non_cached_ids)));

            if($fresh_posts)
            {
                // Despite the name, update_post_cache() expects an array rather than a single post.
                update_post_cache($fresh_posts);
            }
        }

        if($update_meta_cache)
        {
            update_postmeta_cache($ids);
        }

        if($update_term_cache)
        {
            $post_types = array_map('get_post_type', $ids);
            $post_types = array_unique($post_types);
            update_object_term_cache($ids, $post_types);
        }
    }

    function wp_add_trashed_suffix_to_post_name_for_trashed_posts($post_name, $post_id = 0)
    {
        $trashed_posts_with_desired_slug = get_posts([
                                                         'name' => $post_name,
                                                         'post_status' => 'trash',
                                                         'post_type' => 'any',
                                                         'nopaging' => true,
                                                         'post__not_in' => [$post_id],
                                                     ]);

        if(! empty($trashed_posts_with_desired_slug))
        {
            foreach($trashed_posts_with_desired_slug as $_post)
            {
                wp_add_trashed_suffix_to_post_name_for_post($_post);
            }
        }
    }

    function wp_add_trashed_suffix_to_post_name_for_post($post)
    {
        global $wpdb;

        $post = get_post($post);

        if(str_ends_with($post->post_name, '__trashed'))
        {
            return $post->post_name;
        }
        add_post_meta($post->ID, '_wp_desired_post_slug', $post->post_name);
        $post_name = _truncate_post_slug($post->post_name, 191).'__trashed';
        $wpdb->update($wpdb->posts, ['post_name' => $post_name], ['ID' => $post->ID]);
        clean_post_cache($post->ID);

        return $post_name;
    }

    function wp_cache_set_posts_last_changed()
    {
        wp_cache_set_last_changed('posts');
    }

    function get_available_post_mime_types($type = 'attachment')
    {
        global $wpdb;

        $mime_types = apply_filters('get_available_post_mime_types', null, $type);

        if(! is_array($mime_types))
        {
            $mime_types = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT post_mime_type FROM $wpdb->posts WHERE post_type = %s", $type));
        }

        return $mime_types;
    }

    function wp_get_original_image_path($attachment_id, $unfiltered = false)
    {
        if(! wp_attachment_is_image($attachment_id))
        {
            return false;
        }

        $image_meta = wp_get_attachment_metadata($attachment_id);
        $image_file = get_attached_file($attachment_id, $unfiltered);

        if(empty($image_meta['original_image']))
        {
            $original_image = $image_file;
        }
        else
        {
            $original_image = path_join(dirname($image_file), $image_meta['original_image']);
        }

        return apply_filters('wp_get_original_image_path', $original_image, $attachment_id);
    }

    function wp_get_original_image_url($attachment_id)
    {
        if(! wp_attachment_is_image($attachment_id))
        {
            return false;
        }

        $image_url = wp_get_attachment_url($attachment_id);

        if(! $image_url)
        {
            return false;
        }

        $image_meta = wp_get_attachment_metadata($attachment_id);

        if(empty($image_meta['original_image']))
        {
            $original_image_url = $image_url;
        }
        else
        {
            $original_image_url = path_join(dirname($image_url), $image_meta['original_image']);
        }

        return apply_filters('wp_get_original_image_url', $original_image_url, $attachment_id);
    }

    function wp_untrash_post_set_previous_status($new_status, $post_id, $previous_status)
    {
        return $previous_status;
    }

    function use_block_editor_for_post($post)
    {
        $post = get_post($post);

        if(! $post)
        {
            return false;
        }

        // We're in the meta box loader, so don't use the block editor.
        if(is_admin() && isset($_GET['meta-box-loader']))
        {
            check_admin_referer('meta-box-loader', 'meta-box-loader-nonce');

            return false;
        }

        $use_block_editor = use_block_editor_for_post_type($post->post_type);

        return apply_filters('use_block_editor_for_post', $use_block_editor, $post);
    }

    function use_block_editor_for_post_type($post_type)
    {
        if(! post_type_exists($post_type) || ! post_type_supports($post_type, 'editor'))
        {
            return false;
        }

        $post_type_object = get_post_type_object($post_type);
        if($post_type_object && ! $post_type_object->show_in_rest)
        {
            return false;
        }

        return apply_filters('use_block_editor_for_post_type', true, $post_type);
    }

    function wp_create_initial_post_meta()
    {
        register_post_meta('wp_block', 'wp_pattern_sync_status', [
            'sanitize_callback' => 'sanitize_text_field',
            'single' => true,
            'type' => 'string',
            'show_in_rest' => [
                'schema' => [
                    'type' => 'string',
                    'enum' => ['partial', 'unsynced'],
                ],
            ],
        ]);
    }
