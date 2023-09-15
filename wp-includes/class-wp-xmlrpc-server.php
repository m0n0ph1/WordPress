<?php

    #[AllowDynamicProperties]
    class wp_xmlrpc_server extends IXR_Server
    {
        public $methods;

        public $blog_options;

        public $error;

        protected $auth_failed = false;

        private $is_enabled;

        public function __construct()
        {
            $this->methods = [
                // WordPress API.
                'wp.getUsersBlogs' => 'this:wp_getUsersBlogs',
                'wp.newPost' => 'this:wp_newPost',
                'wp.editPost' => 'this:wp_editPost',
                'wp.deletePost' => 'this:wp_deletePost',
                'wp.getPost' => 'this:wp_getPost',
                'wp.getPosts' => 'this:wp_getPosts',
                'wp.newTerm' => 'this:wp_newTerm',
                'wp.editTerm' => 'this:wp_editTerm',
                'wp.deleteTerm' => 'this:wp_deleteTerm',
                'wp.getTerm' => 'this:wp_getTerm',
                'wp.getTerms' => 'this:wp_getTerms',
                'wp.getTaxonomy' => 'this:wp_getTaxonomy',
                'wp.getTaxonomies' => 'this:wp_getTaxonomies',
                'wp.getUser' => 'this:wp_getUser',
                'wp.getUsers' => 'this:wp_getUsers',
                'wp.getProfile' => 'this:wp_getProfile',
                'wp.editProfile' => 'this:wp_editProfile',
                'wp.getPage' => 'this:wp_getPage',
                'wp.getPages' => 'this:wp_getPages',
                'wp.newPage' => 'this:wp_newPage',
                'wp.deletePage' => 'this:wp_deletePage',
                'wp.editPage' => 'this:wp_editPage',
                'wp.getPageList' => 'this:wp_getPageList',
                'wp.getAuthors' => 'this:wp_getAuthors',
                'wp.getCategories' => 'this:mw_getCategories',     // Alias.
                'wp.getTags' => 'this:wp_getTags',
                'wp.newCategory' => 'this:wp_newCategory',
                'wp.deleteCategory' => 'this:wp_deleteCategory',
                'wp.suggestCategories' => 'this:wp_suggestCategories',
                'wp.uploadFile' => 'this:mw_newMediaObject',    // Alias.
                'wp.deleteFile' => 'this:wp_deletePost',        // Alias.
                'wp.getCommentCount' => 'this:wp_getCommentCount',
                'wp.getPostStatusList' => 'this:wp_getPostStatusList',
                'wp.getPageStatusList' => 'this:wp_getPageStatusList',
                'wp.getPageTemplates' => 'this:wp_getPageTemplates',
                'wp.getOptions' => 'this:wp_getOptions',
                'wp.setOptions' => 'this:wp_setOptions',
                'wp.getComment' => 'this:wp_getComment',
                'wp.getComments' => 'this:wp_getComments',
                'wp.deleteComment' => 'this:wp_deleteComment',
                'wp.editComment' => 'this:wp_editComment',
                'wp.newComment' => 'this:wp_newComment',
                'wp.getCommentStatusList' => 'this:wp_getCommentStatusList',
                'wp.getMediaItem' => 'this:wp_getMediaItem',
                'wp.getMediaLibrary' => 'this:wp_getMediaLibrary',
                'wp.getPostFormats' => 'this:wp_getPostFormats',
                'wp.getPostType' => 'this:wp_getPostType',
                'wp.getPostTypes' => 'this:wp_getPostTypes',
                'wp.getRevisions' => 'this:wp_getRevisions',
                'wp.restoreRevision' => 'this:wp_restoreRevision',

                // Blogger API.
                'blogger.getUsersBlogs' => 'this:blogger_getUsersBlogs',
                'blogger.getUserInfo' => 'this:blogger_getUserInfo',
                'blogger.getPost' => 'this:blogger_getPost',
                'blogger.getRecentPosts' => 'this:blogger_getRecentPosts',
                'blogger.newPost' => 'this:blogger_newPost',
                'blogger.editPost' => 'this:blogger_editPost',
                'blogger.deletePost' => 'this:blogger_deletePost',

                // MetaWeblog API (with MT extensions to structs).
                'metaWeblog.newPost' => 'this:mw_newPost',
                'metaWeblog.editPost' => 'this:mw_editPost',
                'metaWeblog.getPost' => 'this:mw_getPost',
                'metaWeblog.getRecentPosts' => 'this:mw_getRecentPosts',
                'metaWeblog.getCategories' => 'this:mw_getCategories',
                'metaWeblog.newMediaObject' => 'this:mw_newMediaObject',

                /*
			 * MetaWeblog API aliases for Blogger API.
			 * See http://www.xmlrpc.com/stories/storyReader$2460
			 */
                'metaWeblog.deletePost' => 'this:blogger_deletePost',
                'metaWeblog.getUsersBlogs' => 'this:blogger_getUsersBlogs',

                // MovableType API.
                'mt.getCategoryList' => 'this:mt_getCategoryList',
                'mt.getRecentPostTitles' => 'this:mt_getRecentPostTitles',
                'mt.getPostCategories' => 'this:mt_getPostCategories',
                'mt.setPostCategories' => 'this:mt_setPostCategories',
                'mt.supportedMethods' => 'this:mt_supportedMethods',
                'mt.supportedTextFilters' => 'this:mt_supportedTextFilters',
                'mt.getTrackbackPings' => 'this:mt_getTrackbackPings',
                'mt.publishPost' => 'this:mt_publishPost',

                // Pingback.
                'pingback.ping' => 'this:pingback_ping',
                'pingback.extensions.getPingbacks' => 'this:pingback_extensions_getPingbacks',

                'demo.sayHello' => 'this:sayHello',
                'demo.addTwoNumbers' => 'this:addTwoNumbers',
            ];

            $this->initialise_blog_option_info();

            $this->methods = apply_filters('xmlrpc_methods', $this->methods);

            $this->set_is_enabled();
        }

        public function initialise_blog_option_info()
        {
            $this->blog_options = [
                // Read-only options.
                'software_name' => [
                    'desc' => __('Software Name'),
                    'readonly' => true,
                    'value' => 'WordPress',
                ],
                'software_version' => [
                    'desc' => __('Software Version'),
                    'readonly' => true,
                    'value' => get_bloginfo('version'),
                ],
                'blog_url' => [
                    'desc' => __('WordPress Address (URL)'),
                    'readonly' => true,
                    'option' => 'siteurl',
                ],
                'home_url' => [
                    'desc' => __('Site Address (URL)'),
                    'readonly' => true,
                    'option' => 'home',
                ],
                'login_url' => [
                    'desc' => __('Login Address (URL)'),
                    'readonly' => true,
                    'value' => wp_login_url(),
                ],
                'admin_url' => [
                    'desc' => __('The URL to the admin area'),
                    'readonly' => true,
                    'value' => get_admin_url(),
                ],
                'image_default_link_type' => [
                    'desc' => __('Image default link type'),
                    'readonly' => true,
                    'option' => 'image_default_link_type',
                ],
                'image_default_size' => [
                    'desc' => __('Image default size'),
                    'readonly' => true,
                    'option' => 'image_default_size',
                ],
                'image_default_align' => [
                    'desc' => __('Image default align'),
                    'readonly' => true,
                    'option' => 'image_default_align',
                ],
                'template' => [
                    'desc' => __('Template'),
                    'readonly' => true,
                    'option' => 'template',
                ],
                'stylesheet' => [
                    'desc' => __('Stylesheet'),
                    'readonly' => true,
                    'option' => 'stylesheet',
                ],
                'post_thumbnail' => [
                    'desc' => __('Post Thumbnail'),
                    'readonly' => true,
                    'value' => current_theme_supports('post-thumbnails'),
                ],

                // Updatable options.
                'time_zone' => [
                    'desc' => __('Time Zone'),
                    'readonly' => false,
                    'option' => 'gmt_offset',
                ],
                'blog_title' => [
                    'desc' => __('Site Title'),
                    'readonly' => false,
                    'option' => 'blogname',
                ],
                'blog_tagline' => [
                    'desc' => __('Site Tagline'),
                    'readonly' => false,
                    'option' => 'blogdescription',
                ],
                'date_format' => [
                    'desc' => __('Date Format'),
                    'readonly' => false,
                    'option' => 'date_format',
                ],
                'time_format' => [
                    'desc' => __('Time Format'),
                    'readonly' => false,
                    'option' => 'time_format',
                ],
                'users_can_register' => [
                    'desc' => __('Allow new users to sign up'),
                    'readonly' => false,
                    'option' => 'users_can_register',
                ],
                'thumbnail_size_w' => [
                    'desc' => __('Thumbnail Width'),
                    'readonly' => false,
                    'option' => 'thumbnail_size_w',
                ],
                'thumbnail_size_h' => [
                    'desc' => __('Thumbnail Height'),
                    'readonly' => false,
                    'option' => 'thumbnail_size_h',
                ],
                'thumbnail_crop' => [
                    'desc' => __('Crop thumbnail to exact dimensions'),
                    'readonly' => false,
                    'option' => 'thumbnail_crop',
                ],
                'medium_size_w' => [
                    'desc' => __('Medium size image width'),
                    'readonly' => false,
                    'option' => 'medium_size_w',
                ],
                'medium_size_h' => [
                    'desc' => __('Medium size image height'),
                    'readonly' => false,
                    'option' => 'medium_size_h',
                ],
                'medium_large_size_w' => [
                    'desc' => __('Medium-Large size image width'),
                    'readonly' => false,
                    'option' => 'medium_large_size_w',
                ],
                'medium_large_size_h' => [
                    'desc' => __('Medium-Large size image height'),
                    'readonly' => false,
                    'option' => 'medium_large_size_h',
                ],
                'large_size_w' => [
                    'desc' => __('Large size image width'),
                    'readonly' => false,
                    'option' => 'large_size_w',
                ],
                'large_size_h' => [
                    'desc' => __('Large size image height'),
                    'readonly' => false,
                    'option' => 'large_size_h',
                ],
                'default_comment_status' => [
                    'desc' => __('Allow people to submit comments on new posts.'),
                    'readonly' => false,
                    'option' => 'default_comment_status',
                ],
                'default_ping_status' => [
                    'desc' => __('Allow link notifications from other blogs (pingbacks and trackbacks) on new posts.'),
                    'readonly' => false,
                    'option' => 'default_ping_status',
                ],
            ];

            $this->blog_options = apply_filters('xmlrpc_blog_options', $this->blog_options);
        }

        private function set_is_enabled()
        {
            /*
		 * Respect old get_option() filters left for back-compat when the 'enable_xmlrpc'
		 * option was deprecated in 3.5.0. Use the {@see 'xmlrpc_enabled'} hook instead.
		 */
            $is_enabled = apply_filters('pre_option_enable_xmlrpc', false);
            if(false === $is_enabled)
            {
                $is_enabled = apply_filters('option_enable_xmlrpc', true);
            }

            $this->is_enabled = apply_filters('xmlrpc_enabled', $is_enabled);
        }

        public function __call($name, $arguments)
        {
            if('_multisite_getUsersBlogs' === $name)
            {
                return $this->_multisite_getUsersBlogs(...$arguments);
            }

            return false;
        }

        protected function _multisite_getUsersBlogs($args)
        {
            $current_blog = get_site();

            $domain = $current_blog->domain;
            $path = $current_blog->path.'xmlrpc.php';

            $blogs = $this->wp_getUsersBlogs($args);
            if($blogs instanceof IXR_Error)
            {
                return $blogs;
            }

            if($_SERVER['HTTP_HOST'] == $domain && $_SERVER['REQUEST_URI'] == $path)
            {
                return $blogs;
            }
            else
            {
                foreach((array) $blogs as $blog)
                {
                    if(str_contains($blog['url'], $_SERVER['HTTP_HOST']))
                    {
                        return [$blog];
                    }
                }

                return [];
            }
        }

        public function wp_getUsersBlogs($args)
        {
            if(! $this->minimum_args($args, 2))
            {
                return $this->error;
            }

            // If this isn't on WPMU then just use blogger_getUsersBlogs().
            if(! is_multisite())
            {
                array_unshift($args, 1);

                return $this->blogger_getUsersBlogs($args);
            }

            $this->escape($args);

            $username = $args[0];
            $password = $args[1];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.getUsersBlogs', $args, $this);

            $blogs = (array) get_blogs_of_user($user->ID);
            $struct = [];
            $primary_blog_id = 0;
            $active_blog = get_active_blog_for_user($user->ID);
            if($active_blog)
            {
                $primary_blog_id = (int) $active_blog->blog_id;
            }

            foreach($blogs as $blog)
            {
                // Don't include blogs that aren't hosted at this site.
                if(get_current_network_id() != $blog->site_id)
                {
                    continue;
                }

                $blog_id = $blog->userblog_id;

                switch_to_blog($blog_id);

                $is_admin = current_user_can('manage_options');
                $is_primary = ((int) $blog_id === $primary_blog_id);

                $struct[] = [
                    'isAdmin' => $is_admin,
                    'isPrimary' => $is_primary,
                    'url' => home_url('/'),
                    'blogid' => (string) $blog_id,
                    'blogName' => get_option('blogname'),
                    'xmlrpc' => site_url('xmlrpc.php', 'rpc'),
                ];

                restore_current_blog();
            }

            return $struct;
        }

        protected function minimum_args($args, $count)
        {
            if(! is_array($args) || count($args) < $count)
            {
                $this->error = new IXR_Error(400, __('Insufficient arguments passed to this XML-RPC method.'));

                return false;
            }

            return true;
        }

        public function blogger_getUsersBlogs($args)
        {
            if(! $this->minimum_args($args, 3))
            {
                return $this->error;
            }

            if(is_multisite())
            {
                return $this->_multisite_getUsersBlogs($args);
            }

            $this->escape($args);

            $username = $args[1];
            $password = $args[2];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'blogger.getUsersBlogs', $args, $this);

            $is_admin = current_user_can('manage_options');

            $struct = [
                'isAdmin' => $is_admin,
                'url' => get_option('home').'/',
                'blogid' => '1',
                'blogName' => get_option('blogname'),
                'xmlrpc' => site_url('xmlrpc.php', 'rpc'),
            ];

            return [$struct];
        }

        public function escape(&$data)
        {
            if(! is_array($data))
            {
                return wp_slash($data);
            }

            foreach($data as &$v)
            {
                if(is_array($v))
                {
                    $this->escape($v);
                }
                elseif(! is_object($v))
                {
                    $v = wp_slash($v);
                }
            }
        }

        public function login($username, $password)
        {
            if(! $this->is_enabled)
            {
                $this->error = new IXR_Error(405, sprintf(__('XML-RPC services are disabled on this site.')));

                return false;
            }

            if($this->auth_failed)
            {
                $user = new WP_Error('login_prevented');
            }
            else
            {
                $user = wp_authenticate($username, $password);
            }

            if(is_wp_error($user))
            {
                $this->error = new IXR_Error(403, __('Incorrect username or password.'));

                // Flag that authentication has failed once on this wp_xmlrpc_server instance.
                $this->auth_failed = true;

                $this->error = apply_filters('xmlrpc_login_error', $this->error, $user);

                return false;
            }

            wp_set_current_user($user->ID);

            return $user;
        }

        public function serve_request()
        {
            $this->IXR_Server($this->methods);
        }

        public function sayHello()
        {
            return 'Hello!';
        }

        public function addTwoNumbers($args)
        {
            $number1 = $args[0];
            $number2 = $args[1];

            return $number1 + $number2;
        }

        public function login_pass_ok($username, $password)
        {
            return (bool) $this->login($username, $password);
        }

        public function error($error, $message = false)
        {
            // Accepts either an error object or an error code and message
            if($message && ! is_object($error))
            {
                $error = new IXR_Error($error, $message);
            }

            if(! $this->is_enabled)
            {
                status_header($error->code);
            }

            $this->output($error->getXml());
        }

        public function wp_newPost($args)
        {
            if(! $this->minimum_args($args, 4))
            {
                return $this->error;
            }

            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $content_struct = $args[3];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            // Convert the date field back to IXR form.
            if(isset($content_struct['post_date']) && ! ($content_struct['post_date'] instanceof IXR_Date))
            {
                $content_struct['post_date'] = $this->_convert_date($content_struct['post_date']);
            }

            /*
		 * Ignore the existing GMT date if it is empty or a non-GMT date was supplied in $content_struct,
		 * since _insert_post() will ignore the non-GMT date if the GMT date is set.
		 */
            if(isset($content_struct['post_date_gmt']) && ! ($content_struct['post_date_gmt'] instanceof IXR_Date))
            {
                if('0000-00-00 00:00:00' === $content_struct['post_date_gmt'] || isset($content_struct['post_date']))
                {
                    unset($content_struct['post_date_gmt']);
                }
                else
                {
                    $content_struct['post_date_gmt'] = $this->_convert_date($content_struct['post_date_gmt']);
                }
            }

            do_action('xmlrpc_call', 'wp.newPost', $args, $this);

            unset($content_struct['ID']);

            return $this->_insert_post($user, $content_struct);
        }

        protected function _convert_date($date)
        {
            if('0000-00-00 00:00:00' === $date)
            {
                return new IXR_Date('00000000T00:00:00Z');
            }

            return new IXR_Date(mysql2date('Ymd\TH:i:s', $date, false));
        }

        protected function _insert_post($user, $content_struct)
        {
            $defaults = [
                'post_status' => 'draft',
                'post_type' => 'post',
                'post_author' => 0,
                'post_password' => '',
                'post_excerpt' => '',
                'post_content' => '',
                'post_title' => '',
                'post_date' => '',
                'post_date_gmt' => '',
                'post_format' => null,
                'post_name' => null,
                'post_thumbnail' => null,
                'post_parent' => 0,
                'ping_status' => '',
                'comment_status' => '',
                'custom_fields' => null,
                'terms_names' => null,
                'terms' => null,
                'sticky' => null,
                'enclosure' => null,
                'ID' => null,
            ];

            $post_data = wp_parse_args(array_intersect_key($content_struct, $defaults), $defaults);

            $post_type = get_post_type_object($post_data['post_type']);
            if(! $post_type)
            {
                return new IXR_Error(403, __('Invalid post type.'));
            }

            $update = ! empty($post_data['ID']);

            if($update)
            {
                if(! get_post($post_data['ID']))
                {
                    return new IXR_Error(401, __('Invalid post ID.'));
                }
                if(! current_user_can('edit_post', $post_data['ID']))
                {
                    return new IXR_Error(401, __('Sorry, you are not allowed to edit this post.'));
                }
                if(get_post_type($post_data['ID']) !== $post_data['post_type'])
                {
                    return new IXR_Error(401, __('The post type may not be changed.'));
                }
            }
            else
            {
                if(! current_user_can($post_type->cap->create_posts) || ! current_user_can($post_type->cap->edit_posts))
                {
                    return new IXR_Error(401, __('Sorry, you are not allowed to post on this site.'));
                }
            }

            switch($post_data['post_status'])
            {
                case 'draft':
                case 'pending':
                    break;
                case 'private':
                    if(! current_user_can($post_type->cap->publish_posts))
                    {
                        return new IXR_Error(401, __('Sorry, you are not allowed to create private posts in this post type.'));
                    }
                    break;
                case 'publish':
                case 'future':
                    if(! current_user_can($post_type->cap->publish_posts))
                    {
                        return new IXR_Error(401, __('Sorry, you are not allowed to publish posts in this post type.'));
                    }
                    break;
                default:
                    if(! get_post_status_object($post_data['post_status']))
                    {
                        $post_data['post_status'] = 'draft';
                    }
                    break;
            }

            if(! empty($post_data['post_password']) && ! current_user_can($post_type->cap->publish_posts))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to create password protected posts in this post type.'));
            }

            $post_data['post_author'] = absint($post_data['post_author']);
            if(! empty($post_data['post_author']) && $post_data['post_author'] != $user->ID)
            {
                if(! current_user_can($post_type->cap->edit_others_posts))
                {
                    return new IXR_Error(401, __('Sorry, you are not allowed to create posts as this user.'));
                }

                $author = get_userdata($post_data['post_author']);

                if(! $author)
                {
                    return new IXR_Error(404, __('Invalid author ID.'));
                }
            }
            else
            {
                $post_data['post_author'] = $user->ID;
            }

            if('open' !== $post_data['comment_status'] && 'closed' !== $post_data['comment_status'])
            {
                unset($post_data['comment_status']);
            }

            if('open' !== $post_data['ping_status'] && 'closed' !== $post_data['ping_status'])
            {
                unset($post_data['ping_status']);
            }

            // Do some timestamp voodoo.
            if(! empty($post_data['post_date_gmt']))
            {
                // We know this is supposed to be GMT, so we're going to slap that Z on there by force.
                $dateCreated = rtrim($post_data['post_date_gmt']->getIso(), 'Z').'Z';
            }
            elseif(! empty($post_data['post_date']))
            {
                $dateCreated = $post_data['post_date']->getIso();
            }

            // Default to not flagging the post date to be edited unless it's intentional.
            $post_data['edit_date'] = false;

            if(! empty($dateCreated))
            {
                $post_data['post_date'] = iso8601_to_datetime($dateCreated);
                $post_data['post_date_gmt'] = iso8601_to_datetime($dateCreated, 'gmt');

                // Flag the post date to be edited.
                $post_data['edit_date'] = true;
            }

            if(! isset($post_data['ID']))
            {
                $post_data['ID'] = get_default_post_to_edit($post_data['post_type'], true)->ID;
            }
            $post_id = $post_data['ID'];

            if('post' === $post_data['post_type'])
            {
                $error = $this->_toggle_sticky($post_data, $update);
                if($error)
                {
                    return $error;
                }
            }

            if(isset($post_data['post_thumbnail']))
            {
                // Empty value deletes, non-empty value adds/updates.
                if(! $post_data['post_thumbnail'])
                {
                    delete_post_thumbnail($post_id);
                }
                elseif(! get_post(absint($post_data['post_thumbnail'])))
                {
                    return new IXR_Error(404, __('Invalid attachment ID.'));
                }
                set_post_thumbnail($post_id, $post_data['post_thumbnail']);
                unset($content_struct['post_thumbnail']);
            }

            if(isset($post_data['custom_fields']))
            {
                $this->set_custom_fields($post_id, $post_data['custom_fields']);
            }

            if(isset($post_data['terms']) || isset($post_data['terms_names']))
            {
                $post_type_taxonomies = get_object_taxonomies($post_data['post_type'], 'objects');

                // Accumulate term IDs from terms and terms_names.
                $terms = [];

                // First validate the terms specified by ID.
                if(isset($post_data['terms']) && is_array($post_data['terms']))
                {
                    $taxonomies = array_keys($post_data['terms']);

                    // Validating term IDs.
                    foreach($taxonomies as $taxonomy)
                    {
                        if(! array_key_exists($taxonomy, $post_type_taxonomies))
                        {
                            return new IXR_Error(401, __('Sorry, one of the given taxonomies is not supported by the post type.'));
                        }

                        if(! current_user_can($post_type_taxonomies[$taxonomy]->cap->assign_terms))
                        {
                            return new IXR_Error(401, __('Sorry, you are not allowed to assign a term to one of the given taxonomies.'));
                        }

                        $term_ids = $post_data['terms'][$taxonomy];
                        $terms[$taxonomy] = [];
                        foreach($term_ids as $term_id)
                        {
                            $term = get_term_by('id', $term_id, $taxonomy);

                            if(! $term)
                            {
                                return new IXR_Error(403, __('Invalid term ID.'));
                            }

                            $terms[$taxonomy][] = (int) $term_id;
                        }
                    }
                }

                // Now validate terms specified by name.
                if(isset($post_data['terms_names']) && is_array($post_data['terms_names']))
                {
                    $taxonomies = array_keys($post_data['terms_names']);

                    foreach($taxonomies as $taxonomy)
                    {
                        if(! array_key_exists($taxonomy, $post_type_taxonomies))
                        {
                            return new IXR_Error(401, __('Sorry, one of the given taxonomies is not supported by the post type.'));
                        }

                        if(! current_user_can($post_type_taxonomies[$taxonomy]->cap->assign_terms))
                        {
                            return new IXR_Error(401, __('Sorry, you are not allowed to assign a term to one of the given taxonomies.'));
                        }

                        /*
					 * For hierarchical taxonomies, we can't assign a term when multiple terms
					 * in the hierarchy share the same name.
					 */
                        $ambiguous_terms = [];
                        if(is_taxonomy_hierarchical($taxonomy))
                        {
                            $tax_term_names = get_terms([
                                                            'taxonomy' => $taxonomy,
                                                            'fields' => 'names',
                                                            'hide_empty' => false,
                                                        ]);

                            // Count the number of terms with the same name.
                            $tax_term_names_count = array_count_values($tax_term_names);

                            // Filter out non-ambiguous term names.
                            $ambiguous_tax_term_counts = array_filter($tax_term_names_count, [
                                $this,
                                '_is_greater_than_one'
                            ]);

                            $ambiguous_terms = array_keys($ambiguous_tax_term_counts);
                        }

                        $term_names = $post_data['terms_names'][$taxonomy];
                        foreach($term_names as $term_name)
                        {
                            if(in_array($term_name, $ambiguous_terms, true))
                            {
                                return new IXR_Error(401, __('Ambiguous term name used in a hierarchical taxonomy. Please use term ID instead.'));
                            }

                            $term = get_term_by('name', $term_name, $taxonomy);

                            if(! $term)
                            {
                                // Term doesn't exist, so check that the user is allowed to create new terms.
                                if(! current_user_can($post_type_taxonomies[$taxonomy]->cap->edit_terms))
                                {
                                    return new IXR_Error(401, __('Sorry, you are not allowed to add a term to one of the given taxonomies.'));
                                }

                                // Create the new term.
                                $term_info = wp_insert_term($term_name, $taxonomy);
                                if(is_wp_error($term_info))
                                {
                                    return new IXR_Error(500, $term_info->get_error_message());
                                }

                                $terms[$taxonomy][] = (int) $term_info['term_id'];
                            }
                            else
                            {
                                $terms[$taxonomy][] = (int) $term->term_id;
                            }
                        }
                    }
                }

                $post_data['tax_input'] = $terms;
                unset($post_data['terms'], $post_data['terms_names']);
            }

            if(isset($post_data['post_format']))
            {
                $format = set_post_format($post_id, $post_data['post_format']);

                if(is_wp_error($format))
                {
                    return new IXR_Error(500, $format->get_error_message());
                }

                unset($post_data['post_format']);
            }

            // Handle enclosures.
            $enclosure = isset($post_data['enclosure']) ? $post_data['enclosure'] : null;
            $this->add_enclosure_if_new($post_id, $enclosure);

            $this->attach_uploads($post_id, $post_data['post_content']);

            $post_data = apply_filters('xmlrpc_wp_insert_post_data', $post_data, $content_struct);

            // Remove all null values to allow for using the insert/update post default values for those keys instead.
            $post_data = array_filter($post_data, static function($value)
            {
                return null !== $value;
            });

            $post_id = $update ? wp_update_post($post_data, true) : wp_insert_post($post_data, true);
            if(is_wp_error($post_id))
            {
                return new IXR_Error(500, $post_id->get_error_message());
            }

            if(! $post_id)
            {
                if($update)
                {
                    return new IXR_Error(401, __('Sorry, the post could not be updated.'));
                }
                else
                {
                    return new IXR_Error(401, __('Sorry, the post could not be created.'));
                }
            }

            return (string) $post_id;
        }

        private function _toggle_sticky($post_data, $update = false)
        {
            $post_type = get_post_type_object($post_data['post_type']);

            // Private and password-protected posts cannot be stickied.
            if('private' === $post_data['post_status'] || ! empty($post_data['post_password']))
            {
                // Error if the client tried to stick the post, otherwise, silently unstick.
                if(! empty($post_data['sticky']))
                {
                    return new IXR_Error(401, __('Sorry, you cannot stick a private post.'));
                }

                if($update)
                {
                    unstick_post($post_data['ID']);
                }
            }
            elseif(isset($post_data['sticky']))
            {
                if(! current_user_can($post_type->cap->edit_others_posts))
                {
                    return new IXR_Error(401, __('Sorry, you are not allowed to make posts sticky.'));
                }

                $sticky = wp_validate_boolean($post_data['sticky']);
                if($sticky)
                {
                    stick_post($post_data['ID']);
                }
                else
                {
                    unstick_post($post_data['ID']);
                }
            }
        }

        public function set_custom_fields($post_id, $fields)
        {
            $post_id = (int) $post_id;

            foreach((array) $fields as $meta)
            {
                if(isset($meta['id']))
                {
                    $meta['id'] = (int) $meta['id'];
                    $pmeta = get_metadata_by_mid('post', $meta['id']);

                    if(! $pmeta || $pmeta->post_id != $post_id)
                    {
                        continue;
                    }

                    if(isset($meta['key']))
                    {
                        $meta['key'] = wp_unslash($meta['key']);
                        if($meta['key'] !== $pmeta->meta_key)
                        {
                            continue;
                        }
                        $meta['value'] = wp_unslash($meta['value']);
                        if(current_user_can('edit_post_meta', $post_id, $meta['key']))
                        {
                            update_metadata_by_mid('post', $meta['id'], $meta['value']);
                        }
                    }
                    elseif(current_user_can('delete_post_meta', $post_id, $pmeta->meta_key))
                    {
                        delete_metadata_by_mid('post', $meta['id']);
                    }
                }
                elseif(current_user_can('add_post_meta', $post_id, wp_unslash($meta['key'])))
                {
                    add_post_meta($post_id, $meta['key'], $meta['value']);
                }
            }
        }

        public function add_enclosure_if_new($post_id, $enclosure)
        {
            if(is_array($enclosure) && isset($enclosure['url']) && isset($enclosure['length']) && isset($enclosure['type']))
            {
                $encstring = $enclosure['url']."\n".$enclosure['length']."\n".$enclosure['type']."\n";
                $found = false;
                $enclosures = get_post_meta($post_id, 'enclosure');
                if($enclosures)
                {
                    foreach($enclosures as $enc)
                    {
                        // This method used to omit the trailing new line. #23219
                        if(rtrim($enc, "\n") === rtrim($encstring, "\n"))
                        {
                            $found = true;
                            break;
                        }
                    }
                }
                if(! $found)
                {
                    add_post_meta($post_id, 'enclosure', $encstring);
                }
            }
        }

        public function attach_uploads($post_id, $post_content)
        {
            global $wpdb;

            // Find any unattached files.
            $attachments = $wpdb->get_results("SELECT ID, guid FROM {$wpdb->posts} WHERE post_parent = '0' AND post_type = 'attachment'");
            if(is_array($attachments))
            {
                foreach($attachments as $file)
                {
                    if(! empty($file->guid) && str_contains($post_content, $file->guid))
                    {
                        $wpdb->update($wpdb->posts, ['post_parent' => $post_id], ['ID' => $file->ID]);
                    }
                }
            }
        }

        public function wp_editPost($args)
        {
            if(! $this->minimum_args($args, 5))
            {
                return $this->error;
            }

            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $post_id = (int) $args[3];
            $content_struct = $args[4];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.editPost', $args, $this);

            $post = get_post($post_id, ARRAY_A);

            if(empty($post['ID']))
            {
                return new IXR_Error(404, __('Invalid post ID.'));
            }

            if(isset($content_struct['if_not_modified_since']))
            {
                // If the post has been modified since the date provided, return an error.
                if(mysql2date('U', $post['post_modified_gmt']) > $content_struct['if_not_modified_since']->getTimestamp())
                {
                    return new IXR_Error(409, __('There is a revision of this post that is more recent.'));
                }
            }

            // Convert the date field back to IXR form.
            $post['post_date'] = $this->_convert_date($post['post_date']);

            /*
		 * Ignore the existing GMT date if it is empty or a non-GMT date was supplied in $content_struct,
		 * since _insert_post() will ignore the non-GMT date if the GMT date is set.
		 */
            if('0000-00-00 00:00:00' === $post['post_date_gmt'] || isset($content_struct['post_date']))
            {
                unset($post['post_date_gmt']);
            }
            else
            {
                $post['post_date_gmt'] = $this->_convert_date($post['post_date_gmt']);
            }

            /*
		 * If the API client did not provide 'post_date', then we must not perpetuate the value that
		 * was stored in the database, or it will appear to be an intentional edit. Conveying it here
		 * as if it was coming from the API client will cause an otherwise zeroed out 'post_date_gmt'
		 * to get set with the value that was originally stored in the database when the draft was created.
		 */
            if(! isset($content_struct['post_date']))
            {
                unset($post['post_date']);
            }

            $this->escape($post);
            $merged_content_struct = array_merge($post, $content_struct);

            $retval = $this->_insert_post($user, $merged_content_struct);
            if($retval instanceof IXR_Error)
            {
                return $retval;
            }

            return true;
        }

        public function wp_deletePost($args)
        {
            if(! $this->minimum_args($args, 4))
            {
                return $this->error;
            }

            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $post_id = (int) $args[3];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.deletePost', $args, $this);

            $post = get_post($post_id, ARRAY_A);
            if(empty($post['ID']))
            {
                return new IXR_Error(404, __('Invalid post ID.'));
            }

            if(! current_user_can('delete_post', $post_id))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to delete this post.'));
            }

            $result = wp_delete_post($post_id);

            if(! $result)
            {
                return new IXR_Error(500, __('Sorry, the post could not be deleted.'));
            }

            return true;
        }

        public function wp_getPost($args)
        {
            if(! $this->minimum_args($args, 4))
            {
                return $this->error;
            }

            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $post_id = (int) $args[3];

            if(isset($args[4]))
            {
                $fields = $args[4];
            }
            else
            {
                $fields = apply_filters('xmlrpc_default_post_fields', ['post', 'terms', 'custom_fields'], 'wp.getPost');
            }

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.getPost', $args, $this);

            $post = get_post($post_id, ARRAY_A);

            if(empty($post['ID']))
            {
                return new IXR_Error(404, __('Invalid post ID.'));
            }

            if(! current_user_can('edit_post', $post_id))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to edit this post.'));
            }

            return $this->_prepare_post($post, $fields);
        }

        protected function _prepare_post($post, $fields)
        {
            // Holds the data for this post. built up based on $fields.
            $_post = ['post_id' => (string) $post['ID']];

            // Prepare common post fields.
            $post_fields = [
                'post_title' => $post['post_title'],
                'post_date' => $this->_convert_date($post['post_date']),
                'post_date_gmt' => $this->_convert_date_gmt($post['post_date_gmt'], $post['post_date']),
                'post_modified' => $this->_convert_date($post['post_modified']),
                'post_modified_gmt' => $this->_convert_date_gmt($post['post_modified_gmt'], $post['post_modified']),
                'post_status' => $post['post_status'],
                'post_type' => $post['post_type'],
                'post_name' => $post['post_name'],
                'post_author' => $post['post_author'],
                'post_password' => $post['post_password'],
                'post_excerpt' => $post['post_excerpt'],
                'post_content' => $post['post_content'],
                'post_parent' => (string) $post['post_parent'],
                'post_mime_type' => $post['post_mime_type'],
                'link' => get_permalink($post['ID']),
                'guid' => $post['guid'],
                'menu_order' => (int) $post['menu_order'],
                'comment_status' => $post['comment_status'],
                'ping_status' => $post['ping_status'],
                'sticky' => ('post' === $post['post_type'] && is_sticky($post['ID'])),
            ];

            // Thumbnail.
            $post_fields['post_thumbnail'] = [];
            $thumbnail_id = get_post_thumbnail_id($post['ID']);
            if($thumbnail_id)
            {
                $thumbnail_size = current_theme_supports('post-thumbnail') ? 'post-thumbnail' : 'thumbnail';
                $post_fields['post_thumbnail'] = $this->_prepare_media_item(get_post($thumbnail_id), $thumbnail_size);
            }

            // Consider future posts as published.
            if('future' === $post_fields['post_status'])
            {
                $post_fields['post_status'] = 'publish';
            }

            // Fill in blank post format.
            $post_fields['post_format'] = get_post_format($post['ID']);
            if(empty($post_fields['post_format']))
            {
                $post_fields['post_format'] = 'standard';
            }

            // Merge requested $post_fields fields into $_post.
            if(in_array('post', $fields, true))
            {
                $_post = array_merge($_post, $post_fields);
            }
            else
            {
                $requested_fields = array_intersect_key($post_fields, array_flip($fields));
                $_post = array_merge($_post, $requested_fields);
            }

            $all_taxonomy_fields = in_array('taxonomies', $fields, true);

            if($all_taxonomy_fields || in_array('terms', $fields, true))
            {
                $post_type_taxonomies = get_object_taxonomies($post['post_type'], 'names');
                $terms = wp_get_object_terms($post['ID'], $post_type_taxonomies);
                $_post['terms'] = [];
                foreach($terms as $term)
                {
                    $_post['terms'][] = $this->_prepare_term($term);
                }
            }

            if(in_array('custom_fields', $fields, true))
            {
                $_post['custom_fields'] = $this->get_custom_fields($post['ID']);
            }

            if(in_array('enclosure', $fields, true))
            {
                $_post['enclosure'] = [];
                $enclosures = (array) get_post_meta($post['ID'], 'enclosure');
                if(! empty($enclosures))
                {
                    $encdata = explode("\n", $enclosures[0]);
                    $_post['enclosure']['url'] = trim(htmlspecialchars($encdata[0]));
                    $_post['enclosure']['length'] = (int) trim($encdata[1]);
                    $_post['enclosure']['type'] = trim($encdata[2]);
                }
            }

            return apply_filters('xmlrpc_prepare_post', $_post, $post, $fields);
        }

        protected function _convert_date_gmt($date_gmt, $date)
        {
            if('0000-00-00 00:00:00' !== $date && '0000-00-00 00:00:00' === $date_gmt)
            {
                return new IXR_Date(get_gmt_from_date(mysql2date('Y-m-d H:i:s', $date, false), 'Ymd\TH:i:s'));
            }

            return $this->_convert_date($date_gmt);
        }

        protected function _prepare_media_item($media_item, $thumbnail_size = 'thumbnail')
        {
            $_media_item = [
                'attachment_id' => (string) $media_item->ID,
                'date_created_gmt' => $this->_convert_date_gmt($media_item->post_date_gmt, $media_item->post_date),
                'parent' => $media_item->post_parent,
                'link' => wp_get_attachment_url($media_item->ID),
                'title' => $media_item->post_title,
                'caption' => $media_item->post_excerpt,
                'description' => $media_item->post_content,
                'metadata' => wp_get_attachment_metadata($media_item->ID),
                'type' => $media_item->post_mime_type,
            ];

            $thumbnail_src = image_downsize($media_item->ID, $thumbnail_size);
            if($thumbnail_src)
            {
                $_media_item['thumbnail'] = $thumbnail_src[0];
            }
            else
            {
                $_media_item['thumbnail'] = $_media_item['link'];
            }

            return apply_filters('xmlrpc_prepare_media_item', $_media_item, $media_item, $thumbnail_size);
        }

        protected function _prepare_term($term)
        {
            $_term = $term;
            if(! is_array($_term))
            {
                $_term = get_object_vars($_term);
            }

            // For integers which may be larger than XML-RPC supports ensure we return strings.
            $_term['term_id'] = (string) $_term['term_id'];
            $_term['term_group'] = (string) $_term['term_group'];
            $_term['term_taxonomy_id'] = (string) $_term['term_taxonomy_id'];
            $_term['parent'] = (string) $_term['parent'];

            // Count we are happy to return as an integer because people really shouldn't use terms that much.
            $_term['count'] = (int) $_term['count'];

            // Get term meta.
            $_term['custom_fields'] = $this->get_term_custom_fields($_term['term_id']);

            return apply_filters('xmlrpc_prepare_term', $_term, $term);
        }

        public function get_term_custom_fields($term_id)
        {
            $term_id = (int) $term_id;

            $custom_fields = [];

            foreach((array) has_term_meta($term_id) as $meta)
            {
                if(! current_user_can('edit_term_meta', $term_id))
                {
                    continue;
                }

                $custom_fields[] = [
                    'id' => $meta['meta_id'],
                    'key' => $meta['meta_key'],
                    'value' => $meta['meta_value'],
                ];
            }

            return $custom_fields;
        }

        public function get_custom_fields($post_id)
        {
            $post_id = (int) $post_id;

            $custom_fields = [];

            foreach((array) has_meta($post_id) as $meta)
            {
                // Don't expose protected fields.
                if(! current_user_can('edit_post_meta', $post_id, $meta['meta_key']))
                {
                    continue;
                }

                $custom_fields[] = [
                    'id' => $meta['meta_id'],
                    'key' => $meta['meta_key'],
                    'value' => $meta['meta_value'],
                ];
            }

            return $custom_fields;
        }

        public function wp_getPosts($args)
        {
            if(! $this->minimum_args($args, 3))
            {
                return $this->error;
            }

            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $filter = isset($args[3]) ? $args[3] : [];

            if(isset($args[4]))
            {
                $fields = $args[4];
            }
            else
            {
                $fields = apply_filters('xmlrpc_default_post_fields', [
                    'post',
                    'terms',
                    'custom_fields'
                ],                      'wp.getPosts');
            }

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.getPosts', $args, $this);

            $query = [];

            if(isset($filter['post_type']))
            {
                $post_type = get_post_type_object($filter['post_type']);
                if(! ((bool) $post_type))
                {
                    return new IXR_Error(403, __('Invalid post type.'));
                }
            }
            else
            {
                $post_type = get_post_type_object('post');
            }

            if(! current_user_can($post_type->cap->edit_posts))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to edit posts in this post type.'));
            }

            $query['post_type'] = $post_type->name;

            if(isset($filter['post_status']))
            {
                $query['post_status'] = $filter['post_status'];
            }

            if(isset($filter['number']))
            {
                $query['numberposts'] = absint($filter['number']);
            }

            if(isset($filter['offset']))
            {
                $query['offset'] = absint($filter['offset']);
            }

            if(isset($filter['orderby']))
            {
                $query['orderby'] = $filter['orderby'];

                if(isset($filter['order']))
                {
                    $query['order'] = $filter['order'];
                }
            }

            if(isset($filter['s']))
            {
                $query['s'] = $filter['s'];
            }

            $posts_list = wp_get_recent_posts($query);

            if(! $posts_list)
            {
                return [];
            }

            // Holds all the posts data.
            $struct = [];

            foreach($posts_list as $post)
            {
                if(! current_user_can('edit_post', $post['ID']))
                {
                    continue;
                }

                $struct[] = $this->_prepare_post($post, $fields);
            }

            return $struct;
        }

        public function wp_newTerm($args)
        {
            if(! $this->minimum_args($args, 4))
            {
                return $this->error;
            }

            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $content_struct = $args[3];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.newTerm', $args, $this);

            if(! taxonomy_exists($content_struct['taxonomy']))
            {
                return new IXR_Error(403, __('Invalid taxonomy.'));
            }

            $taxonomy = get_taxonomy($content_struct['taxonomy']);

            if(! current_user_can($taxonomy->cap->edit_terms))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to create terms in this taxonomy.'));
            }

            $taxonomy = (array) $taxonomy;

            // Hold the data of the term.
            $term_data = [];

            $term_data['name'] = trim($content_struct['name']);
            if(empty($term_data['name']))
            {
                return new IXR_Error(403, __('The term name cannot be empty.'));
            }

            if(isset($content_struct['parent']))
            {
                if(! $taxonomy['hierarchical'])
                {
                    return new IXR_Error(403, __('This taxonomy is not hierarchical.'));
                }

                $parent_term_id = (int) $content_struct['parent'];
                $parent_term = get_term($parent_term_id, $taxonomy['name']);

                if(is_wp_error($parent_term))
                {
                    return new IXR_Error(500, $parent_term->get_error_message());
                }

                if(! $parent_term)
                {
                    return new IXR_Error(403, __('Parent term does not exist.'));
                }

                $term_data['parent'] = $content_struct['parent'];
            }

            if(isset($content_struct['description']))
            {
                $term_data['description'] = $content_struct['description'];
            }

            if(isset($content_struct['slug']))
            {
                $term_data['slug'] = $content_struct['slug'];
            }

            $term = wp_insert_term($term_data['name'], $taxonomy['name'], $term_data);

            if(is_wp_error($term))
            {
                return new IXR_Error(500, $term->get_error_message());
            }

            if(! $term)
            {
                return new IXR_Error(500, __('Sorry, the term could not be created.'));
            }

            // Add term meta.
            if(isset($content_struct['custom_fields']))
            {
                $this->set_term_custom_fields($term['term_id'], $content_struct['custom_fields']);
            }

            return (string) $term['term_id'];
        }

        public function set_term_custom_fields($term_id, $fields)
        {
            $term_id = (int) $term_id;

            foreach((array) $fields as $meta)
            {
                if(isset($meta['id']))
                {
                    $meta['id'] = (int) $meta['id'];
                    $pmeta = get_metadata_by_mid('term', $meta['id']);
                    if(isset($meta['key']))
                    {
                        $meta['key'] = wp_unslash($meta['key']);
                        if($meta['key'] !== $pmeta->meta_key)
                        {
                            continue;
                        }
                        $meta['value'] = wp_unslash($meta['value']);
                        if(current_user_can('edit_term_meta', $term_id))
                        {
                            update_metadata_by_mid('term', $meta['id'], $meta['value']);
                        }
                    }
                    elseif(current_user_can('delete_term_meta', $term_id))
                    {
                        delete_metadata_by_mid('term', $meta['id']);
                    }
                }
                elseif(current_user_can('add_term_meta', $term_id))
                {
                    add_term_meta($term_id, $meta['key'], $meta['value']);
                }
            }
        }

        public function wp_editTerm($args)
        {
            if(! $this->minimum_args($args, 5))
            {
                return $this->error;
            }

            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $term_id = (int) $args[3];
            $content_struct = $args[4];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.editTerm', $args, $this);

            if(! taxonomy_exists($content_struct['taxonomy']))
            {
                return new IXR_Error(403, __('Invalid taxonomy.'));
            }

            $taxonomy = get_taxonomy($content_struct['taxonomy']);

            $taxonomy = (array) $taxonomy;

            // Hold the data of the term.
            $term_data = [];

            $term = get_term($term_id, $content_struct['taxonomy']);

            if(is_wp_error($term))
            {
                return new IXR_Error(500, $term->get_error_message());
            }

            if(! $term)
            {
                return new IXR_Error(404, __('Invalid term ID.'));
            }

            if(! current_user_can('edit_term', $term_id))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to edit this term.'));
            }

            if(isset($content_struct['name']))
            {
                $term_data['name'] = trim($content_struct['name']);

                if(empty($term_data['name']))
                {
                    return new IXR_Error(403, __('The term name cannot be empty.'));
                }
            }

            if(! empty($content_struct['parent']))
            {
                if(! $taxonomy['hierarchical'])
                {
                    return new IXR_Error(403, __('Cannot set parent term, taxonomy is not hierarchical.'));
                }

                $parent_term_id = (int) $content_struct['parent'];
                $parent_term = get_term($parent_term_id, $taxonomy['name']);

                if(is_wp_error($parent_term))
                {
                    return new IXR_Error(500, $parent_term->get_error_message());
                }

                if(! $parent_term)
                {
                    return new IXR_Error(403, __('Parent term does not exist.'));
                }

                $term_data['parent'] = $content_struct['parent'];
            }

            if(isset($content_struct['description']))
            {
                $term_data['description'] = $content_struct['description'];
            }

            if(isset($content_struct['slug']))
            {
                $term_data['slug'] = $content_struct['slug'];
            }

            $term = wp_update_term($term_id, $taxonomy['name'], $term_data);

            if(is_wp_error($term))
            {
                return new IXR_Error(500, $term->get_error_message());
            }

            if(! $term)
            {
                return new IXR_Error(500, __('Sorry, editing the term failed.'));
            }

            // Update term meta.
            if(isset($content_struct['custom_fields']))
            {
                $this->set_term_custom_fields($term_id, $content_struct['custom_fields']);
            }

            return true;
        }

        public function wp_deleteTerm($args)
        {
            if(! $this->minimum_args($args, 5))
            {
                return $this->error;
            }

            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $taxonomy = $args[3];
            $term_id = (int) $args[4];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.deleteTerm', $args, $this);

            if(! taxonomy_exists($taxonomy))
            {
                return new IXR_Error(403, __('Invalid taxonomy.'));
            }

            $taxonomy = get_taxonomy($taxonomy);
            $term = get_term($term_id, $taxonomy->name);

            if(is_wp_error($term))
            {
                return new IXR_Error(500, $term->get_error_message());
            }

            if(! $term)
            {
                return new IXR_Error(404, __('Invalid term ID.'));
            }

            if(! current_user_can('delete_term', $term_id))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to delete this term.'));
            }

            $result = wp_delete_term($term_id, $taxonomy->name);

            if(is_wp_error($result))
            {
                return new IXR_Error(500, $term->get_error_message());
            }

            if(! $result)
            {
                return new IXR_Error(500, __('Sorry, deleting the term failed.'));
            }

            return $result;
        }

        public function wp_getTerm($args)
        {
            if(! $this->minimum_args($args, 5))
            {
                return $this->error;
            }

            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $taxonomy = $args[3];
            $term_id = (int) $args[4];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.getTerm', $args, $this);

            if(! taxonomy_exists($taxonomy))
            {
                return new IXR_Error(403, __('Invalid taxonomy.'));
            }

            $taxonomy = get_taxonomy($taxonomy);

            $term = get_term($term_id, $taxonomy->name, ARRAY_A);

            if(is_wp_error($term))
            {
                return new IXR_Error(500, $term->get_error_message());
            }

            if(! $term)
            {
                return new IXR_Error(404, __('Invalid term ID.'));
            }

            if(! current_user_can('assign_term', $term_id))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to assign this term.'));
            }

            return $this->_prepare_term($term);
        }

        public function wp_getTerms($args)
        {
            if(! $this->minimum_args($args, 4))
            {
                return $this->error;
            }

            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $taxonomy = $args[3];
            $filter = isset($args[4]) ? $args[4] : [];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.getTerms', $args, $this);

            if(! taxonomy_exists($taxonomy))
            {
                return new IXR_Error(403, __('Invalid taxonomy.'));
            }

            $taxonomy = get_taxonomy($taxonomy);

            if(! current_user_can($taxonomy->cap->assign_terms))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to assign terms in this taxonomy.'));
            }

            $query = ['taxonomy' => $taxonomy->name];

            if(isset($filter['number']))
            {
                $query['number'] = absint($filter['number']);
            }

            if(isset($filter['offset']))
            {
                $query['offset'] = absint($filter['offset']);
            }

            if(isset($filter['orderby']))
            {
                $query['orderby'] = $filter['orderby'];

                if(isset($filter['order']))
                {
                    $query['order'] = $filter['order'];
                }
            }

            if(isset($filter['hide_empty']))
            {
                $query['hide_empty'] = $filter['hide_empty'];
            }
            else
            {
                $query['get'] = 'all';
            }

            if(isset($filter['search']))
            {
                $query['search'] = $filter['search'];
            }

            $terms = get_terms($query);

            if(is_wp_error($terms))
            {
                return new IXR_Error(500, $terms->get_error_message());
            }

            $struct = [];

            foreach($terms as $term)
            {
                $struct[] = $this->_prepare_term($term);
            }

            return $struct;
        }

        public function wp_getTaxonomy($args)
        {
            if(! $this->minimum_args($args, 4))
            {
                return $this->error;
            }

            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $taxonomy = $args[3];

            if(isset($args[4]))
            {
                $fields = $args[4];
            }
            else
            {
                $fields = apply_filters('xmlrpc_default_taxonomy_fields', [
                    'labels',
                    'cap',
                    'object_type'
                ],                      'wp.getTaxonomy');
            }

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.getTaxonomy', $args, $this);

            if(! taxonomy_exists($taxonomy))
            {
                return new IXR_Error(403, __('Invalid taxonomy.'));
            }

            $taxonomy = get_taxonomy($taxonomy);

            if(! current_user_can($taxonomy->cap->assign_terms))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to assign terms in this taxonomy.'));
            }

            return $this->_prepare_taxonomy($taxonomy, $fields);
        }

        protected function _prepare_taxonomy($taxonomy, $fields)
        {
            $_taxonomy = [
                'name' => $taxonomy->name,
                'label' => $taxonomy->label,
                'hierarchical' => (bool) $taxonomy->hierarchical,
                'public' => (bool) $taxonomy->public,
                'show_ui' => (bool) $taxonomy->show_ui,
                '_builtin' => (bool) $taxonomy->_builtin,
            ];

            if(in_array('labels', $fields, true))
            {
                $_taxonomy['labels'] = (array) $taxonomy->labels;
            }

            if(in_array('cap', $fields, true))
            {
                $_taxonomy['cap'] = (array) $taxonomy->cap;
            }

            if(in_array('menu', $fields, true))
            {
                $_taxonomy['show_in_menu'] = (bool) $taxonomy->show_in_menu;
            }

            if(in_array('object_type', $fields, true))
            {
                $_taxonomy['object_type'] = array_unique((array) $taxonomy->object_type);
            }

            return apply_filters('xmlrpc_prepare_taxonomy', $_taxonomy, $taxonomy, $fields);
        }

        public function wp_getTaxonomies($args)
        {
            if(! $this->minimum_args($args, 3))
            {
                return $this->error;
            }

            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $filter = isset($args[3]) ? $args[3] : ['public' => true];

            if(isset($args[4]))
            {
                $fields = $args[4];
            }
            else
            {
                $fields = apply_filters('xmlrpc_default_taxonomy_fields', [
                    'labels',
                    'cap',
                    'object_type'
                ],                      'wp.getTaxonomies');
            }

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.getTaxonomies', $args, $this);

            $taxonomies = get_taxonomies($filter, 'objects');

            // Holds all the taxonomy data.
            $struct = [];

            foreach($taxonomies as $taxonomy)
            {
                // Capability check for post types.
                if(! current_user_can($taxonomy->cap->assign_terms))
                {
                    continue;
                }

                $struct[] = $this->_prepare_taxonomy($taxonomy, $fields);
            }

            return $struct;
        }

        public function wp_getUser($args)
        {
            if(! $this->minimum_args($args, 4))
            {
                return $this->error;
            }

            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $user_id = (int) $args[3];

            if(isset($args[4]))
            {
                $fields = $args[4];
            }
            else
            {
                $fields = apply_filters('xmlrpc_default_user_fields', ['all'], 'wp.getUser');
            }

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.getUser', $args, $this);

            if(! current_user_can('edit_user', $user_id))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to edit this user.'));
            }

            $user_data = get_userdata($user_id);

            if(! $user_data)
            {
                return new IXR_Error(404, __('Invalid user ID.'));
            }

            return $this->_prepare_user($user_data, $fields);
        }

        protected function _prepare_user($user, $fields)
        {
            $_user = ['user_id' => (string) $user->ID];

            $user_fields = [
                'username' => $user->user_login,
                'first_name' => $user->user_firstname,
                'last_name' => $user->user_lastname,
                'registered' => $this->_convert_date($user->user_registered),
                'bio' => $user->user_description,
                'email' => $user->user_email,
                'nickname' => $user->nickname,
                'nicename' => $user->user_nicename,
                'url' => $user->user_url,
                'display_name' => $user->display_name,
                'roles' => $user->roles,
            ];

            if(in_array('all', $fields, true))
            {
                $_user = array_merge($_user, $user_fields);
            }
            else
            {
                if(in_array('basic', $fields, true))
                {
                    $basic_fields = ['username', 'email', 'registered', 'display_name', 'nicename'];
                    $fields = array_merge($fields, $basic_fields);
                }
                $requested_fields = array_intersect_key($user_fields, array_flip($fields));
                $_user = array_merge($_user, $requested_fields);
            }

            return apply_filters('xmlrpc_prepare_user', $_user, $user, $fields);
        }

        public function wp_getUsers($args)
        {
            if(! $this->minimum_args($args, 3))
            {
                return $this->error;
            }

            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $filter = isset($args[3]) ? $args[3] : [];

            if(isset($args[4]))
            {
                $fields = $args[4];
            }
            else
            {
                $fields = apply_filters('xmlrpc_default_user_fields', ['all'], 'wp.getUsers');
            }

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.getUsers', $args, $this);

            if(! current_user_can('list_users'))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to list users.'));
            }

            $query = ['fields' => 'all_with_meta'];

            $query['number'] = (isset($filter['number'])) ? absint($filter['number']) : 50;
            $query['offset'] = (isset($filter['offset'])) ? absint($filter['offset']) : 0;

            if(isset($filter['orderby']))
            {
                $query['orderby'] = $filter['orderby'];

                if(isset($filter['order']))
                {
                    $query['order'] = $filter['order'];
                }
            }

            if(isset($filter['role']))
            {
                if(get_role($filter['role']) === null)
                {
                    return new IXR_Error(403, __('Invalid role.'));
                }

                $query['role'] = $filter['role'];
            }

            if(isset($filter['who']))
            {
                $query['who'] = $filter['who'];
            }

            $users = get_users($query);

            $_users = [];
            foreach($users as $user_data)
            {
                if(current_user_can('edit_user', $user_data->ID))
                {
                    $_users[] = $this->_prepare_user($user_data, $fields);
                }
            }

            return $_users;
        }

        public function wp_getProfile($args)
        {
            if(! $this->minimum_args($args, 3))
            {
                return $this->error;
            }

            $this->escape($args);

            $username = $args[1];
            $password = $args[2];

            if(isset($args[3]))
            {
                $fields = $args[3];
            }
            else
            {
                $fields = apply_filters('xmlrpc_default_user_fields', ['all'], 'wp.getProfile');
            }

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.getProfile', $args, $this);

            if(! current_user_can('edit_user', $user->ID))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to edit your profile.'));
            }

            $user_data = get_userdata($user->ID);

            return $this->_prepare_user($user_data, $fields);
        }

        public function wp_editProfile($args)
        {
            if(! $this->minimum_args($args, 4))
            {
                return $this->error;
            }

            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $content_struct = $args[3];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.editProfile', $args, $this);

            if(! current_user_can('edit_user', $user->ID))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to edit your profile.'));
            }

            // Holds data of the user.
            $user_data = [];
            $user_data['ID'] = $user->ID;

            // Only set the user details if they were given.
            if(isset($content_struct['first_name']))
            {
                $user_data['first_name'] = $content_struct['first_name'];
            }

            if(isset($content_struct['last_name']))
            {
                $user_data['last_name'] = $content_struct['last_name'];
            }

            if(isset($content_struct['url']))
            {
                $user_data['user_url'] = $content_struct['url'];
            }

            if(isset($content_struct['display_name']))
            {
                $user_data['display_name'] = $content_struct['display_name'];
            }

            if(isset($content_struct['nickname']))
            {
                $user_data['nickname'] = $content_struct['nickname'];
            }

            if(isset($content_struct['nicename']))
            {
                $user_data['user_nicename'] = $content_struct['nicename'];
            }

            if(isset($content_struct['bio']))
            {
                $user_data['description'] = $content_struct['bio'];
            }

            $result = wp_update_user($user_data);

            if(is_wp_error($result))
            {
                return new IXR_Error(500, $result->get_error_message());
            }

            if(! $result)
            {
                return new IXR_Error(500, __('Sorry, the user could not be updated.'));
            }

            return true;
        }

        public function wp_getPage($args)
        {
            $this->escape($args);

            $page_id = (int) $args[1];
            $username = $args[2];
            $password = $args[3];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            $page = get_post($page_id);
            if(! $page)
            {
                return new IXR_Error(404, __('Invalid post ID.'));
            }

            if(! current_user_can('edit_page', $page_id))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to edit this page.'));
            }

            do_action('xmlrpc_call', 'wp.getPage', $args, $this);

            // If we found the page then format the data.
            if($page->ID && ('page' === $page->post_type))
            {
                return $this->_prepare_page($page);
            }
            else
            {
                // If the page doesn't exist, indicate that.
                return new IXR_Error(404, __('Sorry, no such page.'));
            }
        }

        protected function _prepare_page($page)
        {
            // Get all of the page content and link.
            $full_page = get_extended($page->post_content);
            $link = get_permalink($page->ID);

            // Get info the page parent if there is one.
            $parent_title = '';
            if(! empty($page->post_parent))
            {
                $parent = get_post($page->post_parent);
                $parent_title = $parent->post_title;
            }

            // Determine comment and ping settings.
            $allow_comments = comments_open($page->ID) ? 1 : 0;
            $allow_pings = pings_open($page->ID) ? 1 : 0;

            // Format page date.
            $page_date = $this->_convert_date($page->post_date);
            $page_date_gmt = $this->_convert_date_gmt($page->post_date_gmt, $page->post_date);

            // Pull the categories info together.
            $categories = [];
            if(is_object_in_taxonomy('page', 'category'))
            {
                foreach(wp_get_post_categories($page->ID) as $cat_id)
                {
                    $categories[] = get_cat_name($cat_id);
                }
            }

            // Get the author info.
            $author = get_userdata($page->post_author);

            $page_template = get_page_template_slug($page->ID);
            if(empty($page_template))
            {
                $page_template = 'default';
            }

            $_page = [
                'dateCreated' => $page_date,
                'userid' => $page->post_author,
                'page_id' => $page->ID,
                'page_status' => $page->post_status,
                'description' => $full_page['main'],
                'title' => $page->post_title,
                'link' => $link,
                'permaLink' => $link,
                'categories' => $categories,
                'excerpt' => $page->post_excerpt,
                'text_more' => $full_page['extended'],
                'mt_allow_comments' => $allow_comments,
                'mt_allow_pings' => $allow_pings,
                'wp_slug' => $page->post_name,
                'wp_password' => $page->post_password,
                'wp_author' => $author->display_name,
                'wp_page_parent_id' => $page->post_parent,
                'wp_page_parent_title' => $parent_title,
                'wp_page_order' => $page->menu_order,
                'wp_author_id' => (string) $author->ID,
                'wp_author_display_name' => $author->display_name,
                'date_created_gmt' => $page_date_gmt,
                'custom_fields' => $this->get_custom_fields($page->ID),
                'wp_page_template' => $page_template,
            ];

            return apply_filters('xmlrpc_prepare_page', $_page, $page);
        }

        public function wp_getPages($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $num_pages = isset($args[3]) ? (int) $args[3] : 10;

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            if(! current_user_can('edit_pages'))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to edit pages.'));
            }

            do_action('xmlrpc_call', 'wp.getPages', $args, $this);

            $pages = get_posts([
                                   'post_type' => 'page',
                                   'post_status' => 'any',
                                   'numberposts' => $num_pages,
                               ]);
            $num_pages = count($pages);

            // If we have pages, put together their info.
            if($num_pages >= 1)
            {
                $pages_struct = [];

                foreach($pages as $page)
                {
                    if(current_user_can('edit_page', $page->ID))
                    {
                        $pages_struct[] = $this->_prepare_page($page);
                    }
                }

                return $pages_struct;
            }

            return [];
        }

        public function wp_newPage($args)
        {
            // Items not escaped here will be escaped in wp_newPost().
            $username = $this->escape($args[1]);
            $password = $this->escape($args[2]);

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.newPage', $args, $this);

            // Mark this as content for a page.
            $args[3]['post_type'] = 'page';

            // Let mw_newPost() do all of the heavy lifting.
            return $this->mw_newPost($args);
        }

        public function mw_newPost($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $content_struct = $args[3];
            $publish = isset($args[4]) ? $args[4] : 0;

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'metaWeblog.newPost', $args, $this);

            $page_template = '';
            if(! empty($content_struct['post_type']))
            {
                if('page' === $content_struct['post_type'])
                {
                    if($publish)
                    {
                        $cap = 'publish_pages';
                    }
                    elseif(isset($content_struct['page_status']) && 'publish' === $content_struct['page_status'])
                    {
                        $cap = 'publish_pages';
                    }
                    else
                    {
                        $cap = 'edit_pages';
                    }
                    $error_message = __('Sorry, you are not allowed to publish pages on this site.');
                    $post_type = 'page';
                    if(! empty($content_struct['wp_page_template']))
                    {
                        $page_template = $content_struct['wp_page_template'];
                    }
                }
                elseif('post' === $content_struct['post_type'])
                {
                    if($publish)
                    {
                        $cap = 'publish_posts';
                    }
                    elseif(isset($content_struct['post_status']) && 'publish' === $content_struct['post_status'])
                    {
                        $cap = 'publish_posts';
                    }
                    else
                    {
                        $cap = 'edit_posts';
                    }
                    $error_message = __('Sorry, you are not allowed to publish posts on this site.');
                    $post_type = 'post';
                }
                else
                {
                    // No other 'post_type' values are allowed here.
                    return new IXR_Error(401, __('Invalid post type.'));
                }
            }
            else
            {
                if($publish)
                {
                    $cap = 'publish_posts';
                }
                elseif(isset($content_struct['post_status']) && 'publish' === $content_struct['post_status'])
                {
                    $cap = 'publish_posts';
                }
                else
                {
                    $cap = 'edit_posts';
                }
                $error_message = __('Sorry, you are not allowed to publish posts on this site.');
                $post_type = 'post';
            }

            if(! current_user_can(get_post_type_object($post_type)->cap->create_posts))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to publish posts on this site.'));
            }
            if(! current_user_can($cap))
            {
                return new IXR_Error(401, $error_message);
            }

            // Check for a valid post format if one was given.
            if(isset($content_struct['wp_post_format']))
            {
                $content_struct['wp_post_format'] = sanitize_key($content_struct['wp_post_format']);
                if(! array_key_exists($content_struct['wp_post_format'], get_post_format_strings()))
                {
                    return new IXR_Error(404, __('Invalid post format.'));
                }
            }

            // Let WordPress generate the 'post_name' (slug) unless
            // one has been provided.
            $post_name = null;
            if(isset($content_struct['wp_slug']))
            {
                $post_name = $content_struct['wp_slug'];
            }

            // Only use a password if one was given.
            $post_password = '';
            if(isset($content_struct['wp_password']))
            {
                $post_password = $content_struct['wp_password'];
            }

            // Only set a post parent if one was given.
            $post_parent = 0;
            if(isset($content_struct['wp_page_parent_id']))
            {
                $post_parent = $content_struct['wp_page_parent_id'];
            }

            // Only set the 'menu_order' if it was given.
            $menu_order = 0;
            if(isset($content_struct['wp_page_order']))
            {
                $menu_order = $content_struct['wp_page_order'];
            }

            $post_author = $user->ID;

            // If an author id was provided then use it instead.
            if(isset($content_struct['wp_author_id']) && ($user->ID != $content_struct['wp_author_id']))
            {
                switch($post_type)
                {
                    case 'post':
                        if(! current_user_can('edit_others_posts'))
                        {
                            return new IXR_Error(401, __('Sorry, you are not allowed to create posts as this user.'));
                        }
                        break;
                    case 'page':
                        if(! current_user_can('edit_others_pages'))
                        {
                            return new IXR_Error(401, __('Sorry, you are not allowed to create pages as this user.'));
                        }
                        break;
                    default:
                        return new IXR_Error(401, __('Invalid post type.'));
                }
                $author = get_userdata($content_struct['wp_author_id']);
                if(! $author)
                {
                    return new IXR_Error(404, __('Invalid author ID.'));
                }
                $post_author = $content_struct['wp_author_id'];
            }

            $post_title = isset($content_struct['title']) ? $content_struct['title'] : '';
            $post_content = isset($content_struct['description']) ? $content_struct['description'] : '';

            $post_status = $publish ? 'publish' : 'draft';

            if(isset($content_struct["{$post_type}_status"]))
            {
                switch($content_struct["{$post_type}_status"])
                {
                    case 'draft':
                    case 'pending':
                    case 'private':
                    case 'publish':
                        $post_status = $content_struct["{$post_type}_status"];
                        break;
                    default:
                        // Deliberably left empty.
                        break;
                }
            }

            $post_excerpt = isset($content_struct['mt_excerpt']) ? $content_struct['mt_excerpt'] : '';
            $post_more = isset($content_struct['mt_text_more']) ? $content_struct['mt_text_more'] : '';

            $tags_input = isset($content_struct['mt_keywords']) ? $content_struct['mt_keywords'] : [];

            if(isset($content_struct['mt_allow_comments']))
            {
                if(! is_numeric($content_struct['mt_allow_comments']))
                {
                    switch($content_struct['mt_allow_comments'])
                    {
                        case 'closed':
                            $comment_status = 'closed';
                            break;
                        case 'open':
                            $comment_status = 'open';
                            break;
                        default:
                            $comment_status = get_default_comment_status($post_type);
                            break;
                    }
                }
                else
                {
                    switch((int) $content_struct['mt_allow_comments'])
                    {
                        case 0:
                        case 2:
                            $comment_status = 'closed';
                            break;
                        case 1:
                            $comment_status = 'open';
                            break;
                        default:
                            $comment_status = get_default_comment_status($post_type);
                            break;
                    }
                }
            }
            else
            {
                $comment_status = get_default_comment_status($post_type);
            }

            if(isset($content_struct['mt_allow_pings']))
            {
                if(! is_numeric($content_struct['mt_allow_pings']))
                {
                    switch($content_struct['mt_allow_pings'])
                    {
                        case 'closed':
                            $ping_status = 'closed';
                            break;
                        case 'open':
                            $ping_status = 'open';
                            break;
                        default:
                            $ping_status = get_default_comment_status($post_type, 'pingback');
                            break;
                    }
                }
                else
                {
                    switch((int) $content_struct['mt_allow_pings'])
                    {
                        case 0:
                            $ping_status = 'closed';
                            break;
                        case 1:
                            $ping_status = 'open';
                            break;
                        default:
                            $ping_status = get_default_comment_status($post_type, 'pingback');
                            break;
                    }
                }
            }
            else
            {
                $ping_status = get_default_comment_status($post_type, 'pingback');
            }

            if($post_more)
            {
                $post_content .= '<!--more-->'.$post_more;
            }

            $to_ping = '';
            if(isset($content_struct['mt_tb_ping_urls']))
            {
                $to_ping = $content_struct['mt_tb_ping_urls'];
                if(is_array($to_ping))
                {
                    $to_ping = implode(' ', $to_ping);
                }
            }

            // Do some timestamp voodoo.
            if(! empty($content_struct['date_created_gmt']))
            {
                // We know this is supposed to be GMT, so we're going to slap that Z on there by force.
                $dateCreated = rtrim($content_struct['date_created_gmt']->getIso(), 'Z').'Z';
            }
            elseif(! empty($content_struct['dateCreated']))
            {
                $dateCreated = $content_struct['dateCreated']->getIso();
            }

            $post_date = '';
            $post_date_gmt = '';
            if(! empty($dateCreated))
            {
                $post_date = iso8601_to_datetime($dateCreated);
                $post_date_gmt = iso8601_to_datetime($dateCreated, 'gmt');
            }

            $post_category = [];
            if(isset($content_struct['categories']))
            {
                $catnames = $content_struct['categories'];

                if(is_array($catnames))
                {
                    foreach($catnames as $cat)
                    {
                        $post_category[] = get_cat_ID($cat);
                    }
                }
            }

            $postdata = compact('post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_category', 'post_status', 'post_excerpt', 'comment_status', 'ping_status', 'to_ping', 'post_type', 'post_name', 'post_password', 'post_parent', 'menu_order', 'tags_input', 'page_template');

            $post_id = get_default_post_to_edit($post_type, true)->ID;
            $postdata['ID'] = $post_id;

            // Only posts can be sticky.
            if('post' === $post_type && isset($content_struct['sticky']))
            {
                $data = $postdata;
                $data['sticky'] = $content_struct['sticky'];
                $error = $this->_toggle_sticky($data);
                if($error)
                {
                    return $error;
                }
            }

            if(isset($content_struct['custom_fields']))
            {
                $this->set_custom_fields($post_id, $content_struct['custom_fields']);
            }

            if(isset($content_struct['wp_post_thumbnail']))
            {
                if(set_post_thumbnail($post_id, $content_struct['wp_post_thumbnail']) === false)
                {
                    return new IXR_Error(404, __('Invalid attachment ID.'));
                }

                unset($content_struct['wp_post_thumbnail']);
            }

            // Handle enclosures.
            $thisEnclosure = isset($content_struct['enclosure']) ? $content_struct['enclosure'] : null;
            $this->add_enclosure_if_new($post_id, $thisEnclosure);

            $this->attach_uploads($post_id, $post_content);

            /*
		 * Handle post formats if assigned, value is validated earlier
		 * in this function.
		 */
            if(isset($content_struct['wp_post_format']))
            {
                set_post_format($post_id, $content_struct['wp_post_format']);
            }

            $post_id = wp_insert_post($postdata, true);
            if(is_wp_error($post_id))
            {
                return new IXR_Error(500, $post_id->get_error_message());
            }

            if(! $post_id)
            {
                return new IXR_Error(500, __('Sorry, the post could not be created.'));
            }

            do_action('xmlrpc_call_success_mw_newPost', $post_id, $args); // phpcs:ignore WordPress.NamingConventions.ValidHookName.NotLowercase

            return (string) $post_id;
        }

        public function wp_deletePage($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $page_id = (int) $args[3];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.deletePage', $args, $this);

            /*
		 * Get the current page based on the 'page_id' and
		 * make sure it is a page and not a post.
		 */
            $actual_page = get_post($page_id, ARRAY_A);
            if(! $actual_page || ('page' !== $actual_page['post_type']))
            {
                return new IXR_Error(404, __('Sorry, no such page.'));
            }

            // Make sure the user can delete pages.
            if(! current_user_can('delete_page', $page_id))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to delete this page.'));
            }

            // Attempt to delete the page.
            $result = wp_delete_post($page_id);
            if(! $result)
            {
                return new IXR_Error(500, __('Failed to delete the page.'));
            }

            do_action('xmlrpc_call_success_wp_deletePage', $page_id, $args); // phpcs:ignore WordPress.NamingConventions.ValidHookName.NotLowercase

            return true;
        }

        public function wp_editPage($args)
        {
            // Items will be escaped in mw_editPost().
            $page_id = (int) $args[1];
            $username = $args[2];
            $password = $args[3];
            $content = $args[4];
            $publish = $args[5];

            $escaped_username = $this->escape($username);
            $escaped_password = $this->escape($password);

            $user = $this->login($escaped_username, $escaped_password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.editPage', $args, $this);

            // Get the page data and make sure it is a page.
            $actual_page = get_post($page_id, ARRAY_A);
            if(! $actual_page || ('page' !== $actual_page['post_type']))
            {
                return new IXR_Error(404, __('Sorry, no such page.'));
            }

            // Make sure the user is allowed to edit pages.
            if(! current_user_can('edit_page', $page_id))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to edit this page.'));
            }

            // Mark this as content for a page.
            $content['post_type'] = 'page';

            // Arrange args in the way mw_editPost() understands.
            $args = [
                $page_id,
                $username,
                $password,
                $content,
                $publish,
            ];

            // Let mw_editPost() do all of the heavy lifting.
            return $this->mw_editPost($args);
        }

        public function mw_editPost($args)
        {
            $this->escape($args);

            $post_id = (int) $args[0];
            $username = $args[1];
            $password = $args[2];
            $content_struct = $args[3];
            $publish = isset($args[4]) ? $args[4] : 0;

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'metaWeblog.editPost', $args, $this);

            $postdata = get_post($post_id, ARRAY_A);

            /*
		 * If there is no post data for the give post ID, stop now and return an error.
		 * Otherwise a new post will be created (which was the old behavior).
		 */
            if(! $postdata || empty($postdata['ID']))
            {
                return new IXR_Error(404, __('Invalid post ID.'));
            }

            if(! current_user_can('edit_post', $post_id))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to edit this post.'));
            }

            // Use wp.editPost to edit post types other than post and page.
            if(! in_array($postdata['post_type'], ['post', 'page'], true))
            {
                return new IXR_Error(401, __('Invalid post type.'));
            }

            // Thwart attempt to change the post type.
            if(! empty($content_struct['post_type']) && ($content_struct['post_type'] != $postdata['post_type']))
            {
                return new IXR_Error(401, __('The post type may not be changed.'));
            }

            // Check for a valid post format if one was given.
            if(isset($content_struct['wp_post_format']))
            {
                $content_struct['wp_post_format'] = sanitize_key($content_struct['wp_post_format']);
                if(! array_key_exists($content_struct['wp_post_format'], get_post_format_strings()))
                {
                    return new IXR_Error(404, __('Invalid post format.'));
                }
            }

            $this->escape($postdata);

            $ID = $postdata['ID'];
            $post_content = $postdata['post_content'];
            $post_title = $postdata['post_title'];
            $post_excerpt = $postdata['post_excerpt'];
            $post_password = $postdata['post_password'];
            $post_parent = $postdata['post_parent'];
            $post_type = $postdata['post_type'];
            $menu_order = $postdata['menu_order'];
            $ping_status = $postdata['ping_status'];
            $comment_status = $postdata['comment_status'];

            // Let WordPress manage slug if none was provided.
            $post_name = $postdata['post_name'];
            if(isset($content_struct['wp_slug']))
            {
                $post_name = $content_struct['wp_slug'];
            }

            // Only use a password if one was given.
            if(isset($content_struct['wp_password']))
            {
                $post_password = $content_struct['wp_password'];
            }

            // Only set a post parent if one was given.
            if(isset($content_struct['wp_page_parent_id']))
            {
                $post_parent = $content_struct['wp_page_parent_id'];
            }

            // Only set the 'menu_order' if it was given.
            if(isset($content_struct['wp_page_order']))
            {
                $menu_order = $content_struct['wp_page_order'];
            }

            $page_template = '';
            if(! empty($content_struct['wp_page_template']) && 'page' === $post_type)
            {
                $page_template = $content_struct['wp_page_template'];
            }

            $post_author = $postdata['post_author'];

            // If an author id was provided then use it instead.
            if(isset($content_struct['wp_author_id']))
            {
                // Check permissions if attempting to switch author to or from another user.
                if($user->ID != $content_struct['wp_author_id'] || $user->ID != $post_author)
                {
                    switch($post_type)
                    {
                        case 'post':
                            if(! current_user_can('edit_others_posts'))
                            {
                                return new IXR_Error(401, __('Sorry, you are not allowed to change the post author as this user.'));
                            }
                            break;
                        case 'page':
                            if(! current_user_can('edit_others_pages'))
                            {
                                return new IXR_Error(401, __('Sorry, you are not allowed to change the page author as this user.'));
                            }
                            break;
                        default:
                            return new IXR_Error(401, __('Invalid post type.'));
                    }
                    $post_author = $content_struct['wp_author_id'];
                }
            }

            if(isset($content_struct['mt_allow_comments']))
            {
                if(! is_numeric($content_struct['mt_allow_comments']))
                {
                    switch($content_struct['mt_allow_comments'])
                    {
                        case 'closed':
                            $comment_status = 'closed';
                            break;
                        case 'open':
                            $comment_status = 'open';
                            break;
                        default:
                            $comment_status = get_default_comment_status($post_type);
                            break;
                    }
                }
                else
                {
                    switch((int) $content_struct['mt_allow_comments'])
                    {
                        case 0:
                        case 2:
                            $comment_status = 'closed';
                            break;
                        case 1:
                            $comment_status = 'open';
                            break;
                        default:
                            $comment_status = get_default_comment_status($post_type);
                            break;
                    }
                }
            }

            if(isset($content_struct['mt_allow_pings']))
            {
                if(! is_numeric($content_struct['mt_allow_pings']))
                {
                    switch($content_struct['mt_allow_pings'])
                    {
                        case 'closed':
                            $ping_status = 'closed';
                            break;
                        case 'open':
                            $ping_status = 'open';
                            break;
                        default:
                            $ping_status = get_default_comment_status($post_type, 'pingback');
                            break;
                    }
                }
                else
                {
                    switch((int) $content_struct['mt_allow_pings'])
                    {
                        case 0:
                            $ping_status = 'closed';
                            break;
                        case 1:
                            $ping_status = 'open';
                            break;
                        default:
                            $ping_status = get_default_comment_status($post_type, 'pingback');
                            break;
                    }
                }
            }

            if(isset($content_struct['title']))
            {
                $post_title = $content_struct['title'];
            }

            if(isset($content_struct['description']))
            {
                $post_content = $content_struct['description'];
            }

            $post_category = [];
            if(isset($content_struct['categories']))
            {
                $catnames = $content_struct['categories'];
                if(is_array($catnames))
                {
                    foreach($catnames as $cat)
                    {
                        $post_category[] = get_cat_ID($cat);
                    }
                }
            }

            if(isset($content_struct['mt_excerpt']))
            {
                $post_excerpt = $content_struct['mt_excerpt'];
            }

            $post_more = isset($content_struct['mt_text_more']) ? $content_struct['mt_text_more'] : '';

            $post_status = $publish ? 'publish' : 'draft';
            if(isset($content_struct["{$post_type}_status"]))
            {
                switch($content_struct["{$post_type}_status"])
                {
                    case 'draft':
                    case 'pending':
                    case 'private':
                    case 'publish':
                        $post_status = $content_struct["{$post_type}_status"];
                        break;
                    default:
                        $post_status = $publish ? 'publish' : 'draft';
                        break;
                }
            }

            $tags_input = isset($content_struct['mt_keywords']) ? $content_struct['mt_keywords'] : [];

            if('publish' === $post_status || 'private' === $post_status)
            {
                if('page' === $post_type && ! current_user_can('publish_pages'))
                {
                    return new IXR_Error(401, __('Sorry, you are not allowed to publish this page.'));
                }
                elseif(! current_user_can('publish_posts'))
                {
                    return new IXR_Error(401, __('Sorry, you are not allowed to publish this post.'));
                }
            }

            if($post_more)
            {
                $post_content = $post_content.'<!--more-->'.$post_more;
            }

            $to_ping = '';
            if(isset($content_struct['mt_tb_ping_urls']))
            {
                $to_ping = $content_struct['mt_tb_ping_urls'];
                if(is_array($to_ping))
                {
                    $to_ping = implode(' ', $to_ping);
                }
            }

            // Do some timestamp voodoo.
            if(! empty($content_struct['date_created_gmt']))
            {
                // We know this is supposed to be GMT, so we're going to slap that Z on there by force.
                $dateCreated = rtrim($content_struct['date_created_gmt']->getIso(), 'Z').'Z';
            }
            elseif(! empty($content_struct['dateCreated']))
            {
                $dateCreated = $content_struct['dateCreated']->getIso();
            }

            // Default to not flagging the post date to be edited unless it's intentional.
            $edit_date = false;

            if(! empty($dateCreated))
            {
                $post_date = iso8601_to_datetime($dateCreated);
                $post_date_gmt = iso8601_to_datetime($dateCreated, 'gmt');

                // Flag the post date to be edited.
                $edit_date = true;
            }
            else
            {
                $post_date = $postdata['post_date'];
                $post_date_gmt = $postdata['post_date_gmt'];
            }

            // We've got all the data -- post it.
            $newpost = compact('ID', 'post_content', 'post_title', 'post_category', 'post_status', 'post_excerpt', 'comment_status', 'ping_status', 'edit_date', 'post_date', 'post_date_gmt', 'to_ping', 'post_name', 'post_password', 'post_parent', 'menu_order', 'post_author', 'tags_input', 'page_template');

            $result = wp_update_post($newpost, true);
            if(is_wp_error($result))
            {
                return new IXR_Error(500, $result->get_error_message());
            }

            if(! $result)
            {
                return new IXR_Error(500, __('Sorry, the post could not be updated.'));
            }

            // Only posts can be sticky.
            if('post' === $post_type && isset($content_struct['sticky']))
            {
                $data = $newpost;
                $data['sticky'] = $content_struct['sticky'];
                $data['post_type'] = 'post';
                $error = $this->_toggle_sticky($data, true);
                if($error)
                {
                    return $error;
                }
            }

            if(isset($content_struct['custom_fields']))
            {
                $this->set_custom_fields($post_id, $content_struct['custom_fields']);
            }

            if(isset($content_struct['wp_post_thumbnail']))
            {
                // Empty value deletes, non-empty value adds/updates.
                if(empty($content_struct['wp_post_thumbnail']))
                {
                    delete_post_thumbnail($post_id);
                }
                else
                {
                    if(set_post_thumbnail($post_id, $content_struct['wp_post_thumbnail']) === false)
                    {
                        return new IXR_Error(404, __('Invalid attachment ID.'));
                    }
                }
                unset($content_struct['wp_post_thumbnail']);
            }

            // Handle enclosures.
            $thisEnclosure = isset($content_struct['enclosure']) ? $content_struct['enclosure'] : null;
            $this->add_enclosure_if_new($post_id, $thisEnclosure);

            $this->attach_uploads($ID, $post_content);

            // Handle post formats if assigned, validation is handled earlier in this function.
            if(isset($content_struct['wp_post_format']))
            {
                set_post_format($post_id, $content_struct['wp_post_format']);
            }

            do_action('xmlrpc_call_success_mw_editPost', $post_id, $args); // phpcs:ignore WordPress.NamingConventions.ValidHookName.NotLowercase

            return true;
        }

        public function wp_getPageList($args)
        {
            global $wpdb;

            $this->escape($args);

            $username = $args[1];
            $password = $args[2];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            if(! current_user_can('edit_pages'))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to edit pages.'));
            }

            do_action('xmlrpc_call', 'wp.getPageList', $args, $this);

            // Get list of page IDs and titles.
            $page_list = $wpdb->get_results(
                "
			SELECT ID page_id,
				post_title page_title,
				post_parent page_parent_id,
				post_date_gmt,
				post_date,
				post_status
			FROM {$wpdb->posts}
			WHERE post_type = 'page'
			ORDER BY ID
		"
            );

            // The date needs to be formatted properly.
            $num_pages = count($page_list);
            for($i = 0; $i < $num_pages; $i++)
            {
                $page_list[$i]->dateCreated = $this->_convert_date($page_list[$i]->post_date);
                $page_list[$i]->date_created_gmt = $this->_convert_date_gmt($page_list[$i]->post_date_gmt, $page_list[$i]->post_date);

                unset($page_list[$i]->post_date_gmt);
                unset($page_list[$i]->post_date);
                unset($page_list[$i]->post_status);
            }

            return $page_list;
        }

        public function wp_getAuthors($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            if(! current_user_can('edit_posts'))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to edit posts.'));
            }

            do_action('xmlrpc_call', 'wp.getAuthors', $args, $this);

            $authors = [];
            foreach(get_users(['fields' => ['ID', 'user_login', 'display_name']]) as $user)
            {
                $authors[] = [
                    'user_id' => $user->ID,
                    'user_login' => $user->user_login,
                    'display_name' => $user->display_name,
                ];
            }

            return $authors;
        }

        public function wp_getTags($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            if(! current_user_can('edit_posts'))
            {
                return new IXR_Error(401, __('Sorry, you must be able to edit posts on this site in order to view tags.'));
            }

            do_action('xmlrpc_call', 'wp.getKeywords', $args, $this);

            $tags = [];

            $all_tags = get_tags();
            if($all_tags)
            {
                foreach((array) $all_tags as $tag)
                {
                    $struct = [];
                    $struct['tag_id'] = $tag->term_id;
                    $struct['name'] = $tag->name;
                    $struct['count'] = $tag->count;
                    $struct['slug'] = $tag->slug;
                    $struct['html_url'] = esc_html(get_tag_link($tag->term_id));
                    $struct['rss_url'] = esc_html(get_tag_feed_link($tag->term_id));

                    $tags[] = $struct;
                }
            }

            return $tags;
        }

        public function wp_newCategory($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $category = $args[3];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.newCategory', $args, $this);

            // Make sure the user is allowed to add a category.
            if(! current_user_can('manage_categories'))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to add a category.'));
            }

            /*
		 * If no slug was provided, make it empty
		 * so that WordPress will generate one.
		 */
            if(empty($category['slug']))
            {
                $category['slug'] = '';
            }

            /*
		 * If no parent_id was provided, make it empty
		 * so that it will be a top-level page (no parent).
		 */
            if(! isset($category['parent_id']))
            {
                $category['parent_id'] = '';
            }

            // If no description was provided, make it empty.
            if(empty($category['description']))
            {
                $category['description'] = '';
            }

            $new_category = [
                'cat_name' => $category['name'],
                'category_nicename' => $category['slug'],
                'category_parent' => $category['parent_id'],
                'category_description' => $category['description'],
            ];

            $cat_id = wp_insert_category($new_category, true);
            if(is_wp_error($cat_id))
            {
                if('term_exists' === $cat_id->get_error_code())
                {
                    return (int) $cat_id->get_error_data();
                }
                else
                {
                    return new IXR_Error(500, __('Sorry, the category could not be created.'));
                }
            }
            elseif(! $cat_id)
            {
                return new IXR_Error(500, __('Sorry, the category could not be created.'));
            }

            do_action('xmlrpc_call_success_wp_newCategory', $cat_id, $args); // phpcs:ignore WordPress.NamingConventions.ValidHookName.NotLowercase

            return $cat_id;
        }

        public function wp_deleteCategory($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $category_id = (int) $args[3];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.deleteCategory', $args, $this);

            if(! current_user_can('delete_term', $category_id))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to delete this category.'));
            }

            $status = wp_delete_term($category_id, 'category');

            if(true == $status)
            {
                do_action('xmlrpc_call_success_wp_deleteCategory', $category_id, $args); // phpcs:ignore WordPress.NamingConventions.ValidHookName.NotLowercase
            }

            return $status;
        }

        public function wp_suggestCategories($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $category = $args[3];
            $max_results = (int) $args[4];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            if(! current_user_can('edit_posts'))
            {
                return new IXR_Error(401, __('Sorry, you must be able to edit posts on this site in order to view categories.'));
            }

            do_action('xmlrpc_call', 'wp.suggestCategories', $args, $this);

            $category_suggestions = [];
            $args = [
                'get' => 'all',
                'number' => $max_results,
                'name__like' => $category,
            ];
            foreach((array) get_categories($args) as $cat)
            {
                $category_suggestions[] = [
                    'category_id' => $cat->term_id,
                    'category_name' => $cat->name,
                ];
            }

            return $category_suggestions;
        }

        public function wp_getComment($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $comment_id = (int) $args[3];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.getComment', $args, $this);

            $comment = get_comment($comment_id);
            if(! $comment)
            {
                return new IXR_Error(404, __('Invalid comment ID.'));
            }

            if(! current_user_can('edit_comment', $comment_id))
            {
                return new IXR_Error(403, __('Sorry, you are not allowed to moderate or edit this comment.'));
            }

            return $this->_prepare_comment($comment);
        }

        protected function _prepare_comment($comment)
        {
            // Format page date.
            $comment_date_gmt = $this->_convert_date_gmt($comment->comment_date_gmt, $comment->comment_date);

            if('0' == $comment->comment_approved)
            {
                $comment_status = 'hold';
            }
            elseif('spam' === $comment->comment_approved)
            {
                $comment_status = 'spam';
            }
            elseif('1' == $comment->comment_approved)
            {
                $comment_status = 'approve';
            }
            else
            {
                $comment_status = $comment->comment_approved;
            }
            $_comment = [
                'date_created_gmt' => $comment_date_gmt,
                'user_id' => $comment->user_id,
                'comment_id' => $comment->comment_ID,
                'parent' => $comment->comment_parent,
                'status' => $comment_status,
                'content' => $comment->comment_content,
                'link' => get_comment_link($comment),
                'post_id' => $comment->comment_post_ID,
                'post_title' => get_the_title($comment->comment_post_ID),
                'author' => $comment->comment_author,
                'author_url' => $comment->comment_author_url,
                'author_email' => $comment->comment_author_email,
                'author_ip' => $comment->comment_author_IP,
                'type' => $comment->comment_type,
            ];

            return apply_filters('xmlrpc_prepare_comment', $_comment, $comment);
        }

        public function wp_getComments($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $struct = isset($args[3]) ? $args[3] : [];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.getComments', $args, $this);

            if(isset($struct['status']))
            {
                $status = $struct['status'];
            }
            else
            {
                $status = '';
            }

            if(! current_user_can('moderate_comments') && 'approve' !== $status)
            {
                return new IXR_Error(401, __('Invalid comment status.'));
            }

            $post_id = '';
            if(isset($struct['post_id']))
            {
                $post_id = absint($struct['post_id']);
            }

            $post_type = '';
            if(isset($struct['post_type']))
            {
                $post_type_object = get_post_type_object($struct['post_type']);
                if(! $post_type_object || ! post_type_supports($post_type_object->name, 'comments'))
                {
                    return new IXR_Error(404, __('Invalid post type.'));
                }
                $post_type = $struct['post_type'];
            }

            $offset = 0;
            if(isset($struct['offset']))
            {
                $offset = absint($struct['offset']);
            }

            $number = 10;
            if(isset($struct['number']))
            {
                $number = absint($struct['number']);
            }

            $comments = get_comments([
                                         'status' => $status,
                                         'post_id' => $post_id,
                                         'offset' => $offset,
                                         'number' => $number,
                                         'post_type' => $post_type,
                                     ]);

            $comments_struct = [];
            if(is_array($comments))
            {
                foreach($comments as $comment)
                {
                    $comments_struct[] = $this->_prepare_comment($comment);
                }
            }

            return $comments_struct;
        }

        public function wp_deleteComment($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $comment_id = (int) $args[3];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            if(! get_comment($comment_id))
            {
                return new IXR_Error(404, __('Invalid comment ID.'));
            }

            if(! current_user_can('edit_comment', $comment_id))
            {
                return new IXR_Error(403, __('Sorry, you are not allowed to delete this comment.'));
            }

            do_action('xmlrpc_call', 'wp.deleteComment', $args, $this);

            $status = wp_delete_comment($comment_id);

            if($status)
            {
                do_action('xmlrpc_call_success_wp_deleteComment', $comment_id, $args); // phpcs:ignore WordPress.NamingConventions.ValidHookName.NotLowercase
            }

            return $status;
        }

        public function wp_editComment($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $comment_id = (int) $args[3];
            $content_struct = $args[4];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            if(! get_comment($comment_id))
            {
                return new IXR_Error(404, __('Invalid comment ID.'));
            }

            if(! current_user_can('edit_comment', $comment_id))
            {
                return new IXR_Error(403, __('Sorry, you are not allowed to moderate or edit this comment.'));
            }

            do_action('xmlrpc_call', 'wp.editComment', $args, $this);
            $comment = [
                'comment_ID' => $comment_id,
            ];

            if(isset($content_struct['status']))
            {
                $statuses = get_comment_statuses();
                $statuses = array_keys($statuses);

                if(! in_array($content_struct['status'], $statuses, true))
                {
                    return new IXR_Error(401, __('Invalid comment status.'));
                }

                $comment['comment_approved'] = $content_struct['status'];
            }

            // Do some timestamp voodoo.
            if(! empty($content_struct['date_created_gmt']))
            {
                // We know this is supposed to be GMT, so we're going to slap that Z on there by force.
                $dateCreated = rtrim($content_struct['date_created_gmt']->getIso(), 'Z').'Z';
                $comment['comment_date'] = get_date_from_gmt($dateCreated);
                $comment['comment_date_gmt'] = iso8601_to_datetime($dateCreated, 'gmt');
            }

            if(isset($content_struct['content']))
            {
                $comment['comment_content'] = $content_struct['content'];
            }

            if(isset($content_struct['author']))
            {
                $comment['comment_author'] = $content_struct['author'];
            }

            if(isset($content_struct['author_url']))
            {
                $comment['comment_author_url'] = $content_struct['author_url'];
            }

            if(isset($content_struct['author_email']))
            {
                $comment['comment_author_email'] = $content_struct['author_email'];
            }

            $result = wp_update_comment($comment, true);
            if(is_wp_error($result))
            {
                return new IXR_Error(500, $result->get_error_message());
            }

            if(! $result)
            {
                return new IXR_Error(500, __('Sorry, the comment could not be updated.'));
            }

            do_action('xmlrpc_call_success_wp_editComment', $comment_id, $args); // phpcs:ignore WordPress.NamingConventions.ValidHookName.NotLowercase

            return true;
        }

        public function wp_newComment($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $post = $args[3];
            $content_struct = $args[4];

            $allow_anon = apply_filters('xmlrpc_allow_anonymous_comments', false);

            $user = $this->login($username, $password);

            if(! $user)
            {
                $logged_in = false;
                if($allow_anon && get_option('comment_registration'))
                {
                    return new IXR_Error(403, __('Sorry, you must be logged in to comment.'));
                }
                elseif(! $allow_anon)
                {
                    return $this->error;
                }
            }
            else
            {
                $logged_in = true;
            }

            if(is_numeric($post))
            {
                $post_id = absint($post);
            }
            else
            {
                $post_id = url_to_postid($post);
            }

            if(! $post_id)
            {
                return new IXR_Error(404, __('Invalid post ID.'));
            }

            if(! get_post($post_id))
            {
                return new IXR_Error(404, __('Invalid post ID.'));
            }

            if(! comments_open($post_id))
            {
                return new IXR_Error(403, __('Sorry, comments are closed for this item.'));
            }

            if('publish' === get_post_status($post_id) && ! current_user_can('edit_post', $post_id) && post_password_required($post_id))
            {
                return new IXR_Error(403, __('Sorry, you are not allowed to comment on this post.'));
            }

            if('private' === get_post_status($post_id) && ! current_user_can('read_post', $post_id))
            {
                return new IXR_Error(403, __('Sorry, you are not allowed to comment on this post.'));
            }

            $comment = [
                'comment_post_ID' => $post_id,
                'comment_content' => trim($content_struct['content']),
            ];

            if($logged_in)
            {
                $display_name = $user->display_name;
                $user_email = $user->user_email;
                $user_url = $user->user_url;

                $comment['comment_author'] = $this->escape($display_name);
                $comment['comment_author_email'] = $this->escape($user_email);
                $comment['comment_author_url'] = $this->escape($user_url);
                $comment['user_id'] = $user->ID;
            }
            else
            {
                $comment['comment_author'] = '';
                if(isset($content_struct['author']))
                {
                    $comment['comment_author'] = $content_struct['author'];
                }

                $comment['comment_author_email'] = '';
                if(isset($content_struct['author_email']))
                {
                    $comment['comment_author_email'] = $content_struct['author_email'];
                }

                $comment['comment_author_url'] = '';
                if(isset($content_struct['author_url']))
                {
                    $comment['comment_author_url'] = $content_struct['author_url'];
                }

                $comment['user_id'] = 0;

                if(get_option('require_name_email'))
                {
                    if(strlen($comment['comment_author_email']) < 6 || '' === $comment['comment_author'])
                    {
                        return new IXR_Error(403, __('Comment author name and email are required.'));
                    }
                    elseif(! is_email($comment['comment_author_email']))
                    {
                        return new IXR_Error(403, __('A valid email address is required.'));
                    }
                }
            }

            $comment['comment_parent'] = isset($content_struct['comment_parent']) ? absint($content_struct['comment_parent']) : 0;

            $allow_empty = apply_filters('allow_empty_comment', false, $comment);

            if(! $allow_empty && '' === $comment['comment_content'])
            {
                return new IXR_Error(403, __('Comment is required.'));
            }

            do_action('xmlrpc_call', 'wp.newComment', $args, $this);

            $comment_id = wp_new_comment($comment, true);
            if(is_wp_error($comment_id))
            {
                return new IXR_Error(403, $comment_id->get_error_message());
            }

            if(! $comment_id)
            {
                return new IXR_Error(403, __('Something went wrong.'));
            }

            do_action('xmlrpc_call_success_wp_newComment', $comment_id, $args); // phpcs:ignore WordPress.NamingConventions.ValidHookName.NotLowercase

            return $comment_id;
        }

        public function wp_getCommentStatusList($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            if(! current_user_can('publish_posts'))
            {
                return new IXR_Error(403, __('Sorry, you are not allowed to access details about this site.'));
            }

            do_action('xmlrpc_call', 'wp.getCommentStatusList', $args, $this);

            return get_comment_statuses();
        }

        public function wp_getCommentCount($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $post_id = (int) $args[3];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            $post = get_post($post_id, ARRAY_A);
            if(empty($post['ID']))
            {
                return new IXR_Error(404, __('Invalid post ID.'));
            }

            if(! current_user_can('edit_post', $post_id))
            {
                return new IXR_Error(403, __('Sorry, you are not allowed to access details of this post.'));
            }

            do_action('xmlrpc_call', 'wp.getCommentCount', $args, $this);

            $count = wp_count_comments($post_id);

            return [
                'approved' => $count->approved,
                'awaiting_moderation' => $count->moderated,
                'spam' => $count->spam,
                'total_comments' => $count->total_comments,
            ];
        }

        public function wp_getPostStatusList($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            if(! current_user_can('edit_posts'))
            {
                return new IXR_Error(403, __('Sorry, you are not allowed to access details about this site.'));
            }

            do_action('xmlrpc_call', 'wp.getPostStatusList', $args, $this);

            return get_post_statuses();
        }

        public function wp_getPageStatusList($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            if(! current_user_can('edit_pages'))
            {
                return new IXR_Error(403, __('Sorry, you are not allowed to access details about this site.'));
            }

            do_action('xmlrpc_call', 'wp.getPageStatusList', $args, $this);

            return get_page_statuses();
        }

        public function wp_getPageTemplates($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            if(! current_user_can('edit_pages'))
            {
                return new IXR_Error(403, __('Sorry, you are not allowed to access details about this site.'));
            }

            $templates = get_page_templates();
            $templates['Default'] = 'default';

            return $templates;
        }

        public function wp_getOptions($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $options = isset($args[3]) ? (array) $args[3] : [];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            // If no specific options where asked for, return all of them.
            if(count($options) === 0)
            {
                $options = array_keys($this->blog_options);
            }

            return $this->_getOptions($options);
        }

        public function _getOptions($options)
        {
            $data = [];
            $can_manage = current_user_can('manage_options');
            foreach($options as $option)
            {
                if(array_key_exists($option, $this->blog_options))
                {
                    $data[$option] = $this->blog_options[$option];
                    // Is the value static or dynamic?
                    if(isset($data[$option]['option']))
                    {
                        $data[$option]['value'] = get_option($data[$option]['option']);
                        unset($data[$option]['option']);
                    }

                    if(! $can_manage)
                    {
                        $data[$option]['readonly'] = true;
                    }
                }
            }

            return $data;
        }

        public function wp_setOptions($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $options = (array) $args[3];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            if(! current_user_can('manage_options'))
            {
                return new IXR_Error(403, __('Sorry, you are not allowed to update options.'));
            }

            $option_names = [];
            foreach($options as $o_name => $o_value)
            {
                $option_names[] = $o_name;
                if(! array_key_exists($o_name, $this->blog_options))
                {
                    continue;
                }

                if(true == $this->blog_options[$o_name]['readonly'])
                {
                    continue;
                }

                update_option($this->blog_options[$o_name]['option'], wp_unslash($o_value));
            }

            // Now return the updated values.
            return $this->_getOptions($option_names);
        }

        public function wp_getMediaItem($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $attachment_id = (int) $args[3];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            if(! current_user_can('upload_files'))
            {
                return new IXR_Error(403, __('Sorry, you are not allowed to upload files.'));
            }

            do_action('xmlrpc_call', 'wp.getMediaItem', $args, $this);

            $attachment = get_post($attachment_id);
            if(! $attachment || 'attachment' !== $attachment->post_type)
            {
                return new IXR_Error(404, __('Invalid attachment ID.'));
            }

            return $this->_prepare_media_item($attachment);
        }

        public function wp_getMediaLibrary($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $struct = isset($args[3]) ? $args[3] : [];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            if(! current_user_can('upload_files'))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to upload files.'));
            }

            do_action('xmlrpc_call', 'wp.getMediaLibrary', $args, $this);

            $parent_id = (isset($struct['parent_id'])) ? absint($struct['parent_id']) : '';
            $mime_type = (isset($struct['mime_type'])) ? $struct['mime_type'] : '';
            $offset = (isset($struct['offset'])) ? absint($struct['offset']) : 0;
            $number = (isset($struct['number'])) ? absint($struct['number']) : -1;

            $attachments = get_posts([
                                         'post_type' => 'attachment',
                                         'post_parent' => $parent_id,
                                         'offset' => $offset,
                                         'numberposts' => $number,
                                         'post_mime_type' => $mime_type,
                                     ]);

            $attachments_struct = [];

            foreach($attachments as $attachment)
            {
                $attachments_struct[] = $this->_prepare_media_item($attachment);
            }

            return $attachments_struct;
        }

        public function wp_getPostFormats($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            if(! current_user_can('edit_posts'))
            {
                return new IXR_Error(403, __('Sorry, you are not allowed to access details about this site.'));
            }

            do_action('xmlrpc_call', 'wp.getPostFormats', $args, $this);

            $formats = get_post_format_strings();

            // Find out if they want a list of currently supports formats.
            if(isset($args[3]) && is_array($args[3]))
            {
                if($args[3]['show-supported'])
                {
                    if(current_theme_supports('post-formats'))
                    {
                        $supported = get_theme_support('post-formats');

                        $data = [];
                        $data['all'] = $formats;
                        $data['supported'] = $supported[0];

                        $formats = $data;
                    }
                }
            }

            return $formats;
        }

        /*
	 * Blogger API functions.
	 * Specs on http://plant.blogger.com/api and https://groups.yahoo.com/group/bloggerDev/
	 */

        public function wp_getPostType($args)
        {
            if(! $this->minimum_args($args, 4))
            {
                return $this->error;
            }

            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $post_type_name = $args[3];

            if(isset($args[4]))
            {
                $fields = $args[4];
            }
            else
            {
                $fields = apply_filters('xmlrpc_default_posttype_fields', [
                    'labels',
                    'cap',
                    'taxonomies'
                ],                      'wp.getPostType');
            }

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.getPostType', $args, $this);

            if(! post_type_exists($post_type_name))
            {
                return new IXR_Error(403, __('Invalid post type.'));
            }

            $post_type = get_post_type_object($post_type_name);

            if(! current_user_can($post_type->cap->edit_posts))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to edit posts in this post type.'));
            }

            return $this->_prepare_post_type($post_type, $fields);
        }

        protected function _prepare_post_type($post_type, $fields)
        {
            $_post_type = [
                'name' => $post_type->name,
                'label' => $post_type->label,
                'hierarchical' => (bool) $post_type->hierarchical,
                'public' => (bool) $post_type->public,
                'show_ui' => (bool) $post_type->show_ui,
                '_builtin' => (bool) $post_type->_builtin,
                'has_archive' => (bool) $post_type->has_archive,
                'supports' => get_all_post_type_supports($post_type->name),
            ];

            if(in_array('labels', $fields, true))
            {
                $_post_type['labels'] = (array) $post_type->labels;
            }

            if(in_array('cap', $fields, true))
            {
                $_post_type['cap'] = (array) $post_type->cap;
                $_post_type['map_meta_cap'] = (bool) $post_type->map_meta_cap;
            }

            if(in_array('menu', $fields, true))
            {
                $_post_type['menu_position'] = (int) $post_type->menu_position;
                $_post_type['menu_icon'] = $post_type->menu_icon;
                $_post_type['show_in_menu'] = (bool) $post_type->show_in_menu;
            }

            if(in_array('taxonomies', $fields, true))
            {
                $_post_type['taxonomies'] = get_object_taxonomies($post_type->name, 'names');
            }

            return apply_filters('xmlrpc_prepare_post_type', $_post_type, $post_type);
        }

        public function wp_getPostTypes($args)
        {
            if(! $this->minimum_args($args, 3))
            {
                return $this->error;
            }

            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $filter = isset($args[3]) ? $args[3] : ['public' => true];

            if(isset($args[4]))
            {
                $fields = $args[4];
            }
            else
            {
                $fields = apply_filters('xmlrpc_default_posttype_fields', [
                    'labels',
                    'cap',
                    'taxonomies'
                ],                      'wp.getPostTypes');
            }

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.getPostTypes', $args, $this);

            $post_types = get_post_types($filter, 'objects');

            $struct = [];

            foreach($post_types as $post_type)
            {
                if(! current_user_can($post_type->cap->edit_posts))
                {
                    continue;
                }

                $struct[$post_type->name] = $this->_prepare_post_type($post_type, $fields);
            }

            return $struct;
        }

        public function wp_getRevisions($args)
        {
            if(! $this->minimum_args($args, 4))
            {
                return $this->error;
            }

            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $post_id = (int) $args[3];

            if(isset($args[4]))
            {
                $fields = $args[4];
            }
            else
            {
                $fields = apply_filters('xmlrpc_default_revision_fields', [
                    'post_date',
                    'post_date_gmt'
                ],                      'wp.getRevisions');
            }

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.getRevisions', $args, $this);

            $post = get_post($post_id);
            if(! $post)
            {
                return new IXR_Error(404, __('Invalid post ID.'));
            }

            if(! current_user_can('edit_post', $post_id))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to edit posts.'));
            }

            // Check if revisions are enabled.
            if(! wp_revisions_enabled($post))
            {
                return new IXR_Error(401, __('Sorry, revisions are disabled.'));
            }

            $revisions = wp_get_post_revisions($post_id);

            if(! $revisions)
            {
                return [];
            }

            $struct = [];

            foreach($revisions as $revision)
            {
                if(! current_user_can('read_post', $revision->ID))
                {
                    continue;
                }

                // Skip autosaves.
                if(wp_is_post_autosave($revision))
                {
                    continue;
                }

                $struct[] = $this->_prepare_post(get_object_vars($revision), $fields);
            }

            return $struct;
        }

        public function wp_restoreRevision($args)
        {
            if(! $this->minimum_args($args, 3))
            {
                return $this->error;
            }

            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            $revision_id = (int) $args[3];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'wp.restoreRevision', $args, $this);

            $revision = wp_get_post_revision($revision_id);
            if(! $revision)
            {
                return new IXR_Error(404, __('Invalid post ID.'));
            }

            if(wp_is_post_autosave($revision))
            {
                return new IXR_Error(404, __('Invalid post ID.'));
            }

            $post = get_post($revision->post_parent);
            if(! $post)
            {
                return new IXR_Error(404, __('Invalid post ID.'));
            }

            if(! current_user_can('edit_post', $revision->post_parent))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to edit this post.'));
            }

            // Check if revisions are disabled.
            if(! wp_revisions_enabled($post))
            {
                return new IXR_Error(401, __('Sorry, revisions are disabled.'));
            }

            $post = wp_restore_post_revision($revision_id);

            return (bool) $post;
        }

        public function blogger_getUserInfo($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            if(! current_user_can('edit_posts'))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to access user data on this site.'));
            }

            do_action('xmlrpc_call', 'blogger.getUserInfo', $args, $this);

            $struct = [
                'nickname' => $user->nickname,
                'userid' => $user->ID,
                'url' => $user->user_url,
                'lastname' => $user->last_name,
                'firstname' => $user->first_name,
            ];

            return $struct;
        }

        public function blogger_getPost($args)
        {
            $this->escape($args);

            $post_id = (int) $args[1];
            $username = $args[2];
            $password = $args[3];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            $post_data = get_post($post_id, ARRAY_A);
            if(! $post_data)
            {
                return new IXR_Error(404, __('Invalid post ID.'));
            }

            if(! current_user_can('edit_post', $post_id))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to edit this post.'));
            }

            do_action('xmlrpc_call', 'blogger.getPost', $args, $this);

            $categories = implode(',', wp_get_post_categories($post_id));

            $content = '<title>'.wp_unslash($post_data['post_title']).'</title>';
            $content .= '<category>'.$categories.'</category>';
            $content .= wp_unslash($post_data['post_content']);

            $struct = [
                'userid' => $post_data['post_author'],
                'dateCreated' => $this->_convert_date($post_data['post_date']),
                'content' => $content,
                'postid' => (string) $post_data['ID'],
            ];

            return $struct;
        }

        public function blogger_getRecentPosts($args)
        {
            $this->escape($args);

            // $args[0] = appkey - ignored.
            $username = $args[2];
            $password = $args[3];
            if(isset($args[4]))
            {
                $query = ['numberposts' => absint($args[4])];
            }
            else
            {
                $query = [];
            }

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            if(! current_user_can('edit_posts'))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to edit posts.'));
            }

            do_action('xmlrpc_call', 'blogger.getRecentPosts', $args, $this);

            $posts_list = wp_get_recent_posts($query);

            if(! $posts_list)
            {
                $this->error = new IXR_Error(500, __('Either there are no posts, or something went wrong.'));

                return $this->error;
            }

            $recent_posts = [];
            foreach($posts_list as $entry)
            {
                if(! current_user_can('edit_post', $entry['ID']))
                {
                    continue;
                }

                $post_date = $this->_convert_date($entry['post_date']);
                $categories = implode(',', wp_get_post_categories($entry['ID']));

                $content = '<title>'.wp_unslash($entry['post_title']).'</title>';
                $content .= '<category>'.$categories.'</category>';
                $content .= wp_unslash($entry['post_content']);

                $recent_posts[] = [
                    'userid' => $entry['post_author'],
                    'dateCreated' => $post_date,
                    'content' => $content,
                    'postid' => (string) $entry['ID'],
                ];
            }

            return $recent_posts;
        }

        public function blogger_getTemplate($args)
        {
            return new IXR_Error(403, __('Sorry, this method is not supported.'));
        }

        public function blogger_setTemplate($args)
        {
            return new IXR_Error(403, __('Sorry, this method is not supported.'));
        }

        /*
	 * MetaWeblog API functions.
	 * Specs on wherever Dave Winer wants them to be.
	 */

        public function blogger_newPost($args)
        {
            $this->escape($args);

            $username = $args[2];
            $password = $args[3];
            $content = $args[4];
            $publish = $args[5];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'blogger.newPost', $args, $this);

            $cap = ($publish) ? 'publish_posts' : 'edit_posts';
            if(! current_user_can(get_post_type_object('post')->cap->create_posts) || ! current_user_can($cap))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to post on this site.'));
            }

            $post_status = ($publish) ? 'publish' : 'draft';

            $post_author = $user->ID;

            $post_title = xmlrpc_getposttitle($content);
            $post_category = xmlrpc_getpostcategory($content);
            $post_content = xmlrpc_removepostdata($content);

            $post_date = current_time('mysql');
            $post_date_gmt = current_time('mysql', 1);

            $post_data = compact('post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_category', 'post_status');

            $post_id = wp_insert_post($post_data);
            if(is_wp_error($post_id))
            {
                return new IXR_Error(500, $post_id->get_error_message());
            }

            if(! $post_id)
            {
                return new IXR_Error(500, __('Sorry, the post could not be created.'));
            }

            $this->attach_uploads($post_id, $post_content);

            do_action('xmlrpc_call_success_blogger_newPost', $post_id, $args); // phpcs:ignore WordPress.NamingConventions.ValidHookName.NotLowercase

            return $post_id;
        }

        public function blogger_editPost($args)
        {
            $this->escape($args);

            $post_id = (int) $args[1];
            $username = $args[2];
            $password = $args[3];
            $content = $args[4];
            $publish = $args[5];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'blogger.editPost', $args, $this);

            $actual_post = get_post($post_id, ARRAY_A);

            if(! $actual_post || 'post' !== $actual_post['post_type'])
            {
                return new IXR_Error(404, __('Sorry, no such post.'));
            }

            $this->escape($actual_post);

            if(! current_user_can('edit_post', $post_id))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to edit this post.'));
            }
            if('publish' === $actual_post['post_status'] && ! current_user_can('publish_posts'))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to publish this post.'));
            }

            $postdata = [];
            $postdata['ID'] = $actual_post['ID'];
            $postdata['post_content'] = xmlrpc_removepostdata($content);
            $postdata['post_title'] = xmlrpc_getposttitle($content);
            $postdata['post_category'] = xmlrpc_getpostcategory($content);
            $postdata['post_status'] = $actual_post['post_status'];
            $postdata['post_excerpt'] = $actual_post['post_excerpt'];
            $postdata['post_status'] = $publish ? 'publish' : 'draft';

            $result = wp_update_post($postdata);

            if(! $result)
            {
                return new IXR_Error(500, __('Sorry, the post could not be updated.'));
            }
            $this->attach_uploads($actual_post['ID'], $postdata['post_content']);

            do_action('xmlrpc_call_success_blogger_editPost', $post_id, $args); // phpcs:ignore WordPress.NamingConventions.ValidHookName.NotLowercase

            return true;
        }

        public function blogger_deletePost($args)
        {
            $this->escape($args);

            $post_id = (int) $args[1];
            $username = $args[2];
            $password = $args[3];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'blogger.deletePost', $args, $this);

            $actual_post = get_post($post_id, ARRAY_A);

            if(! $actual_post || 'post' !== $actual_post['post_type'])
            {
                return new IXR_Error(404, __('Sorry, no such post.'));
            }

            if(! current_user_can('delete_post', $post_id))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to delete this post.'));
            }

            $result = wp_delete_post($post_id);

            if(! $result)
            {
                return new IXR_Error(500, __('Sorry, the post could not be deleted.'));
            }

            do_action('xmlrpc_call_success_blogger_deletePost', $post_id, $args); // phpcs:ignore WordPress.NamingConventions.ValidHookName.NotLowercase

            return true;
        }

        public function mw_getPost($args)
        {
            $this->escape($args);

            $post_id = (int) $args[0];
            $username = $args[1];
            $password = $args[2];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            $postdata = get_post($post_id, ARRAY_A);
            if(! $postdata)
            {
                return new IXR_Error(404, __('Invalid post ID.'));
            }

            if(! current_user_can('edit_post', $post_id))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to edit this post.'));
            }

            do_action('xmlrpc_call', 'metaWeblog.getPost', $args, $this);

            if('' !== $postdata['post_date'])
            {
                $post_date = $this->_convert_date($postdata['post_date']);
                $post_date_gmt = $this->_convert_date_gmt($postdata['post_date_gmt'], $postdata['post_date']);
                $post_modified = $this->_convert_date($postdata['post_modified']);
                $post_modified_gmt = $this->_convert_date_gmt($postdata['post_modified_gmt'], $postdata['post_modified']);

                $categories = [];
                $catids = wp_get_post_categories($post_id);
                foreach($catids as $catid)
                {
                    $categories[] = get_cat_name($catid);
                }

                $tagnames = [];
                $tags = wp_get_post_tags($post_id);
                if(! empty($tags))
                {
                    foreach($tags as $tag)
                    {
                        $tagnames[] = $tag->name;
                    }
                    $tagnames = implode(', ', $tagnames);
                }
                else
                {
                    $tagnames = '';
                }

                $post = get_extended($postdata['post_content']);
                $link = get_permalink($postdata['ID']);

                // Get the author info.
                $author = get_userdata($postdata['post_author']);

                $allow_comments = ('open' === $postdata['comment_status']) ? 1 : 0;
                $allow_pings = ('open' === $postdata['ping_status']) ? 1 : 0;

                // Consider future posts as published.
                if('future' === $postdata['post_status'])
                {
                    $postdata['post_status'] = 'publish';
                }

                // Get post format.
                $post_format = get_post_format($post_id);
                if(empty($post_format))
                {
                    $post_format = 'standard';
                }

                $sticky = false;
                if(is_sticky($post_id))
                {
                    $sticky = true;
                }

                $enclosure = [];
                foreach((array) get_post_custom($post_id) as $key => $val)
                {
                    if('enclosure' === $key)
                    {
                        foreach((array) $val as $enc)
                        {
                            $encdata = explode("\n", $enc);
                            $enclosure['url'] = trim(htmlspecialchars($encdata[0]));
                            $enclosure['length'] = (int) trim($encdata[1]);
                            $enclosure['type'] = trim($encdata[2]);
                            break 2;
                        }
                    }
                }

                $resp = [
                    'dateCreated' => $post_date,
                    'userid' => $postdata['post_author'],
                    'postid' => $postdata['ID'],
                    'description' => $post['main'],
                    'title' => $postdata['post_title'],
                    'link' => $link,
                    'permaLink' => $link,
                    // Commented out because no other tool seems to use this.
                    // 'content' => $entry['post_content'],
                    'categories' => $categories,
                    'mt_excerpt' => $postdata['post_excerpt'],
                    'mt_text_more' => $post['extended'],
                    'wp_more_text' => $post['more_text'],
                    'mt_allow_comments' => $allow_comments,
                    'mt_allow_pings' => $allow_pings,
                    'mt_keywords' => $tagnames,
                    'wp_slug' => $postdata['post_name'],
                    'wp_password' => $postdata['post_password'],
                    'wp_author_id' => (string) $author->ID,
                    'wp_author_display_name' => $author->display_name,
                    'date_created_gmt' => $post_date_gmt,
                    'post_status' => $postdata['post_status'],
                    'custom_fields' => $this->get_custom_fields($post_id),
                    'wp_post_format' => $post_format,
                    'sticky' => $sticky,
                    'date_modified' => $post_modified,
                    'date_modified_gmt' => $post_modified_gmt,
                ];

                if(! empty($enclosure))
                {
                    $resp['enclosure'] = $enclosure;
                }

                $resp['wp_post_thumbnail'] = get_post_thumbnail_id($postdata['ID']);

                return $resp;
            }
            else
            {
                return new IXR_Error(404, __('Sorry, no such post.'));
            }
        }

        public function mw_getRecentPosts($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            if(isset($args[3]))
            {
                $query = ['numberposts' => absint($args[3])];
            }
            else
            {
                $query = [];
            }

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            if(! current_user_can('edit_posts'))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to edit posts.'));
            }

            do_action('xmlrpc_call', 'metaWeblog.getRecentPosts', $args, $this);

            $posts_list = wp_get_recent_posts($query);

            if(! $posts_list)
            {
                return [];
            }

            $recent_posts = [];
            foreach($posts_list as $entry)
            {
                if(! current_user_can('edit_post', $entry['ID']))
                {
                    continue;
                }

                $post_date = $this->_convert_date($entry['post_date']);
                $post_date_gmt = $this->_convert_date_gmt($entry['post_date_gmt'], $entry['post_date']);
                $post_modified = $this->_convert_date($entry['post_modified']);
                $post_modified_gmt = $this->_convert_date_gmt($entry['post_modified_gmt'], $entry['post_modified']);

                $categories = [];
                $catids = wp_get_post_categories($entry['ID']);
                foreach($catids as $catid)
                {
                    $categories[] = get_cat_name($catid);
                }

                $tagnames = [];
                $tags = wp_get_post_tags($entry['ID']);
                if(! empty($tags))
                {
                    foreach($tags as $tag)
                    {
                        $tagnames[] = $tag->name;
                    }
                    $tagnames = implode(', ', $tagnames);
                }
                else
                {
                    $tagnames = '';
                }

                $post = get_extended($entry['post_content']);
                $link = get_permalink($entry['ID']);

                // Get the post author info.
                $author = get_userdata($entry['post_author']);

                $allow_comments = ('open' === $entry['comment_status']) ? 1 : 0;
                $allow_pings = ('open' === $entry['ping_status']) ? 1 : 0;

                // Consider future posts as published.
                if('future' === $entry['post_status'])
                {
                    $entry['post_status'] = 'publish';
                }

                // Get post format.
                $post_format = get_post_format($entry['ID']);
                if(empty($post_format))
                {
                    $post_format = 'standard';
                }

                $recent_posts[] = [
                    'dateCreated' => $post_date,
                    'userid' => $entry['post_author'],
                    'postid' => (string) $entry['ID'],
                    'description' => $post['main'],
                    'title' => $entry['post_title'],
                    'link' => $link,
                    'permaLink' => $link,
                    // Commented out because no other tool seems to use this.
                    // 'content' => $entry['post_content'],
                    'categories' => $categories,
                    'mt_excerpt' => $entry['post_excerpt'],
                    'mt_text_more' => $post['extended'],
                    'wp_more_text' => $post['more_text'],
                    'mt_allow_comments' => $allow_comments,
                    'mt_allow_pings' => $allow_pings,
                    'mt_keywords' => $tagnames,
                    'wp_slug' => $entry['post_name'],
                    'wp_password' => $entry['post_password'],
                    'wp_author_id' => (string) $author->ID,
                    'wp_author_display_name' => $author->display_name,
                    'date_created_gmt' => $post_date_gmt,
                    'post_status' => $entry['post_status'],
                    'custom_fields' => $this->get_custom_fields($entry['ID']),
                    'wp_post_format' => $post_format,
                    'date_modified' => $post_modified,
                    'date_modified_gmt' => $post_modified_gmt,
                    'sticky' => ('post' === $entry['post_type'] && is_sticky($entry['ID'])),
                    'wp_post_thumbnail' => get_post_thumbnail_id($entry['ID']),
                ];
            }

            return $recent_posts;
        }

        public function mw_getCategories($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            if(! current_user_can('edit_posts'))
            {
                return new IXR_Error(401, __('Sorry, you must be able to edit posts on this site in order to view categories.'));
            }

            do_action('xmlrpc_call', 'metaWeblog.getCategories', $args, $this);

            $categories_struct = [];

            $cats = get_categories(['get' => 'all']);
            if($cats)
            {
                foreach($cats as $cat)
                {
                    $struct = [];
                    $struct['categoryId'] = $cat->term_id;
                    $struct['parentId'] = $cat->parent;
                    $struct['description'] = $cat->name;
                    $struct['categoryDescription'] = $cat->description;
                    $struct['categoryName'] = $cat->name;
                    $struct['htmlUrl'] = esc_html(get_category_link($cat->term_id));
                    $struct['rssUrl'] = esc_html(get_category_feed_link($cat->term_id, 'rss2'));

                    $categories_struct[] = $struct;
                }
            }

            return $categories_struct;
        }

        public function mw_newMediaObject($args)
        {
            $username = $this->escape($args[1]);
            $password = $this->escape($args[2]);
            $data = $args[3];

            $name = sanitize_file_name($data['name']);
            $type = $data['type'];
            $bits = $data['bits'];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'metaWeblog.newMediaObject', $args, $this);

            if(! current_user_can('upload_files'))
            {
                $this->error = new IXR_Error(401, __('Sorry, you are not allowed to upload files.'));

                return $this->error;
            }

            if(is_multisite() && upload_is_user_over_quota(false))
            {
                $this->error = new IXR_Error(401, sprintf(/* translators: %s: Allowed space allocation. */ __('Sorry, you have used your space allocation of %s. Please delete some files to upload more files.'), size_format(get_space_allowed() * MB_IN_BYTES)));

                return $this->error;
            }

            $upload_err = apply_filters('pre_upload_error', false);
            if($upload_err)
            {
                return new IXR_Error(500, $upload_err);
            }

            $upload = wp_upload_bits($name, null, $bits);
            if(! empty($upload['error']))
            {
                /* translators: 1: File name, 2: Error message. */
                $errorString = sprintf(__('Could not write file %1$s (%2$s).'), $name, $upload['error']);

                return new IXR_Error(500, $errorString);
            }
            // Construct the attachment array.
            $post_id = 0;
            if(! empty($data['post_id']))
            {
                $post_id = (int) $data['post_id'];

                if(! current_user_can('edit_post', $post_id))
                {
                    return new IXR_Error(401, __('Sorry, you are not allowed to edit this post.'));
                }
            }
            $attachment = [
                'post_title' => $name,
                'post_content' => '',
                'post_type' => 'attachment',
                'post_parent' => $post_id,
                'post_mime_type' => $type,
                'guid' => $upload['url'],
            ];

            // Save the data.
            $id = wp_insert_attachment($attachment, $upload['file'], $post_id);
            wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $upload['file']));

            do_action('xmlrpc_call_success_mw_newMediaObject', $id, $args); // phpcs:ignore WordPress.NamingConventions.ValidHookName.NotLowercase

            $struct = $this->_prepare_media_item(get_post($id));

            // Deprecated values.
            $struct['id'] = $struct['attachment_id'];
            $struct['file'] = $struct['title'];
            $struct['url'] = $struct['link'];

            return $struct;
        }

        public function mt_getRecentPostTitles($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];
            if(isset($args[3]))
            {
                $query = ['numberposts' => absint($args[3])];
            }
            else
            {
                $query = [];
            }

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'mt.getRecentPostTitles', $args, $this);

            $posts_list = wp_get_recent_posts($query);

            if(! $posts_list)
            {
                $this->error = new IXR_Error(500, __('Either there are no posts, or something went wrong.'));

                return $this->error;
            }

            $recent_posts = [];

            foreach($posts_list as $entry)
            {
                if(! current_user_can('edit_post', $entry['ID']))
                {
                    continue;
                }

                $post_date = $this->_convert_date($entry['post_date']);
                $post_date_gmt = $this->_convert_date_gmt($entry['post_date_gmt'], $entry['post_date']);

                $recent_posts[] = [
                    'dateCreated' => $post_date,
                    'userid' => $entry['post_author'],
                    'postid' => (string) $entry['ID'],
                    'title' => $entry['post_title'],
                    'post_status' => $entry['post_status'],
                    'date_created_gmt' => $post_date_gmt,
                ];
            }

            return $recent_posts;
        }

        /*
	 * MovableType API functions.
	 * Specs archive on http://web.archive.org/web/20050220091302/http://www.movabletype.org:80/docs/mtmanual_programmatic.html
	 */

        public function mt_getCategoryList($args)
        {
            $this->escape($args);

            $username = $args[1];
            $password = $args[2];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            if(! current_user_can('edit_posts'))
            {
                return new IXR_Error(401, __('Sorry, you must be able to edit posts on this site in order to view categories.'));
            }

            do_action('xmlrpc_call', 'mt.getCategoryList', $args, $this);

            $categories_struct = [];

            $cats = get_categories([
                                       'hide_empty' => 0,
                                       'hierarchical' => 0,
                                   ]);
            if($cats)
            {
                foreach($cats as $cat)
                {
                    $struct = [];
                    $struct['categoryId'] = $cat->term_id;
                    $struct['categoryName'] = $cat->name;

                    $categories_struct[] = $struct;
                }
            }

            return $categories_struct;
        }

        public function mt_getPostCategories($args)
        {
            $this->escape($args);

            $post_id = (int) $args[0];
            $username = $args[1];
            $password = $args[2];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            if(! get_post($post_id))
            {
                return new IXR_Error(404, __('Invalid post ID.'));
            }

            if(! current_user_can('edit_post', $post_id))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to edit this post.'));
            }

            do_action('xmlrpc_call', 'mt.getPostCategories', $args, $this);

            $categories = [];
            $catids = wp_get_post_categories((int) $post_id);
            // First listed category will be the primary category.
            $isPrimary = true;
            foreach($catids as $catid)
            {
                $categories[] = [
                    'categoryName' => get_cat_name($catid),
                    'categoryId' => (string) $catid,
                    'isPrimary' => $isPrimary,
                ];
                $isPrimary = false;
            }

            return $categories;
        }

        public function mt_setPostCategories($args)
        {
            $this->escape($args);

            $post_id = (int) $args[0];
            $username = $args[1];
            $password = $args[2];
            $categories = $args[3];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'mt.setPostCategories', $args, $this);

            if(! get_post($post_id))
            {
                return new IXR_Error(404, __('Invalid post ID.'));
            }

            if(! current_user_can('edit_post', $post_id))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to edit this post.'));
            }

            $catids = [];
            foreach($categories as $cat)
            {
                $catids[] = $cat['categoryId'];
            }

            wp_set_post_categories($post_id, $catids);

            return true;
        }

        public function mt_supportedMethods()
        {
            do_action('xmlrpc_call', 'mt.supportedMethods', [], $this);

            return array_keys($this->methods);
        }

        public function mt_supportedTextFilters()
        {
            do_action('xmlrpc_call', 'mt.supportedTextFilters', [], $this);

            return apply_filters('xmlrpc_text_filters', []);
        }

        public function mt_getTrackbackPings($post_id)
        {
            global $wpdb;

            do_action('xmlrpc_call', 'mt.getTrackbackPings', $post_id, $this);

            $actual_post = get_post($post_id, ARRAY_A);

            if(! $actual_post)
            {
                return new IXR_Error(404, __('Sorry, no such post.'));
            }

            $comments = $wpdb->get_results($wpdb->prepare("SELECT comment_author_url, comment_content, comment_author_IP, comment_type FROM $wpdb->comments WHERE comment_post_ID = %d", $post_id));

            if(! $comments)
            {
                return [];
            }

            $trackback_pings = [];
            foreach($comments as $comment)
            {
                if('trackback' === $comment->comment_type)
                {
                    $content = $comment->comment_content;
                    $title = substr($content, 8, (strpos($content, '</strong>') - 8));
                    $trackback_pings[] = [
                        'pingTitle' => $title,
                        'pingURL' => $comment->comment_author_url,
                        'pingIP' => $comment->comment_author_IP,
                    ];
                }
            }

            return $trackback_pings;
        }

        public function mt_publishPost($args)
        {
            $this->escape($args);

            $post_id = (int) $args[0];
            $username = $args[1];
            $password = $args[2];

            $user = $this->login($username, $password);
            if(! $user)
            {
                return $this->error;
            }

            do_action('xmlrpc_call', 'mt.publishPost', $args, $this);

            $postdata = get_post($post_id, ARRAY_A);
            if(! $postdata)
            {
                return new IXR_Error(404, __('Invalid post ID.'));
            }

            if(! current_user_can('publish_posts') || ! current_user_can('edit_post', $post_id))
            {
                return new IXR_Error(401, __('Sorry, you are not allowed to publish this post.'));
            }

            $postdata['post_status'] = 'publish';

            // Retain old categories.
            $postdata['post_category'] = wp_get_post_categories($post_id);
            $this->escape($postdata);

            return wp_update_post($postdata);
        }

        public function pingback_ping($args)
        {
            global $wpdb;

            do_action('xmlrpc_call', 'pingback.ping', $args, $this);

            $this->escape($args);

            $pagelinkedfrom = str_replace('&amp;', '&', $args[0]);
            $pagelinkedto = str_replace('&amp;', '&', $args[1]);
            $pagelinkedto = str_replace('&', '&amp;', $pagelinkedto);

            $pagelinkedfrom = apply_filters('pingback_ping_source_uri', $pagelinkedfrom, $pagelinkedto);

            if(! $pagelinkedfrom)
            {
                return $this->pingback_error(0, __('A valid URL was not provided.'));
            }

            // Check if the page linked to is on our site.
            $pos1 = strpos(
                $pagelinkedto, str_replace([
                                               'http://www.',
                                               'http://',
                                               'https://www.',
                                               'https://'
                                           ], '', get_option('home'))
            );
            if(! $pos1)
            {
                return $this->pingback_error(0, __('Is there no link to us?'));
            }

            /*
		 * Let's find which post is linked to.
		 * FIXME: Does url_to_postid() cover all these cases already?
		 * If so, then let's use it and drop the old code.
		 */
            $urltest = parse_url($pagelinkedto);
            $post_id = url_to_postid($pagelinkedto);
            if($post_id)
            {
                // $way
            }
            elseif(isset($urltest['path']) && preg_match('#p/[0-9]{1,}#', $urltest['path'], $match))
            {
                // The path defines the post_ID (archives/p/XXXX).
                $blah = explode('/', $match[0]);
                $post_id = (int) $blah[1];
            }
            elseif(isset($urltest['query']) && preg_match('#p=[0-9]{1,}#', $urltest['query'], $match))
            {
                // The query string defines the post_ID (?p=XXXX).
                $blah = explode('=', $match[0]);
                $post_id = (int) $blah[1];
            }
            elseif(isset($urltest['fragment']))
            {
                // An #anchor is there, it's either...
                if((int) $urltest['fragment'])
                {
                    // ...an integer #XXXX (simplest case),
                    $post_id = (int) $urltest['fragment'];
                }
                elseif(preg_match('/post-[0-9]+/', $urltest['fragment']))
                {
                    // ...a post ID in the form 'post-###',
                    $post_id = preg_replace('/[^0-9]+/', '', $urltest['fragment']);
                }
                elseif(is_string($urltest['fragment']))
                {
                    // ...or a string #title, a little more complicated.
                    $title = preg_replace('/[^a-z0-9]/i', '.', $urltest['fragment']);
                    $sql = $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title RLIKE %s", $title);
                    $post_id = $wpdb->get_var($sql);
                    if(! $post_id)
                    {
                        // Returning unknown error '0' is better than die()'ing.
                        return $this->pingback_error(0, '');
                    }
                }
            }
            else
            {
                // TODO: Attempt to extract a post ID from the given URL.
                return $this->pingback_error(33, __('The specified target URL cannot be used as a target. It either does not exist, or it is not a pingback-enabled resource.'));
            }
            $post_id = (int) $post_id;

            $post = get_post($post_id);

            if(! $post)
            { // Post not found.
                return $this->pingback_error(33, __('The specified target URL cannot be used as a target. It either does not exist, or it is not a pingback-enabled resource.'));
            }

            if(url_to_postid($pagelinkedfrom) == $post_id)
            {
                return $this->pingback_error(0, __('The source URL and the target URL cannot both point to the same resource.'));
            }

            // Check if pings are on.
            if(! pings_open($post))
            {
                return $this->pingback_error(33, __('The specified target URL cannot be used as a target. It either does not exist, or it is not a pingback-enabled resource.'));
            }

            // Let's check that the remote site didn't already pingback this entry.
            if($wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_author_url = %s", $post_id, $pagelinkedfrom)))
            {
                return $this->pingback_error(48, __('The pingback has already been registered.'));
            }

            // Very stupid, but gives time to the 'from' server to publish!
            sleep(1);

            $remote_ip = preg_replace('/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR']);

            $user_agent = apply_filters('http_headers_useragent', 'WordPress/'.get_bloginfo('version').'; '.get_bloginfo('url'), $pagelinkedfrom);

            // Let's check the remote site.
            $http_api_args = [
                'timeout' => 10,
                'redirection' => 0,
                'limit_response_size' => 153600, // 150 KB
                'user-agent' => "$user_agent; verifying pingback from $remote_ip",
                'headers' => [
                    'X-Pingback-Forwarded-For' => $remote_ip,
                ],
            ];

            $request = wp_safe_remote_get($pagelinkedfrom, $http_api_args);
            $remote_source = wp_remote_retrieve_body($request);
            $remote_source_original = $remote_source;

            if(! $remote_source)
            {
                return $this->pingback_error(16, __('The source URL does not exist.'));
            }

            $remote_source = apply_filters('pre_remote_source', $remote_source, $pagelinkedto);

            // Work around bug in strip_tags():
            $remote_source = str_replace('<!DOC', '<DOC', $remote_source);
            $remote_source = preg_replace('/[\r\n\t ]+/', ' ', $remote_source); // normalize spaces
            $remote_source = preg_replace('/<\/*(h1|h2|h3|h4|h5|h6|p|th|td|li|dt|dd|pre|caption|input|textarea|button|body)[^>]*>/', "\n\n", $remote_source);

            preg_match('|<title>([^<]*?)</title>|is', $remote_source, $matchtitle);
            $title = isset($matchtitle[1]) ? $matchtitle[1] : '';
            if(empty($title))
            {
                return $this->pingback_error(32, __('A title on that page cannot be found.'));
            }

            // Remove all script and style tags including their content.
            $remote_source = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $remote_source);
            // Just keep the tag we need.
            $remote_source = strip_tags($remote_source, '<a>');

            $p = explode("\n\n", $remote_source);

            $preg_target = preg_quote($pagelinkedto, '|');

            foreach($p as $para)
            {
                if(str_contains($para, $pagelinkedto))
                { // It exists, but is it a link?
                    preg_match('|<a[^>]+?'.$preg_target.'[^>]*>([^>]+?)</a>|', $para, $context);

                    // If the URL isn't in a link context, keep looking.
                    if(empty($context))
                    {
                        continue;
                    }

                    /*
				 * We're going to use this fake tag to mark the context in a bit.
				 * The marker is needed in case the link text appears more than once in the paragraph.
				 */
                    $excerpt = preg_replace('|\</?wpcontext\>|', '', $para);

                    // prevent really long link text
                    if(strlen($context[1]) > 100)
                    {
                        $context[1] = substr($context[1], 0, 100).'&#8230;';
                    }

                    $marker = '<wpcontext>'.$context[1].'</wpcontext>';  // Set up our marker.
                    $excerpt = str_replace($context[0], $marker, $excerpt); // Swap out the link for our marker.
                    $excerpt = strip_tags($excerpt, '<wpcontext>');         // Strip all tags but our context marker.
                    $excerpt = trim($excerpt);
                    $preg_marker = preg_quote($marker, '|');
                    $excerpt = preg_replace("|.*?\s(.{0,100}$preg_marker.{0,100})\s.*|s", '$1', $excerpt);
                    $excerpt = strip_tags($excerpt); // YES, again, to remove the marker wrapper.
                    break;
                }
            }

            if(empty($context))
            { // Link to target not found.
                return $this->pingback_error(17, __('The source URL does not contain a link to the target URL, and so cannot be used as a source.'));
            }

            $pagelinkedfrom = str_replace('&', '&amp;', $pagelinkedfrom);

            $context = '[&#8230;] '.esc_html($excerpt).' [&#8230;]';
            $pagelinkedfrom = $this->escape($pagelinkedfrom);

            $comment_post_id = (int) $post_id;
            $comment_author = $title;
            $comment_author_email = '';
            $this->escape($comment_author);
            $comment_author_url = $pagelinkedfrom;
            $comment_content = $context;
            $this->escape($comment_content);
            $comment_type = 'pingback';

            $commentdata = [
                'comment_post_ID' => $comment_post_id,
            ];

            $commentdata += compact('comment_author', 'comment_author_url', 'comment_author_email', 'comment_content', 'comment_type', 'remote_source', 'remote_source_original');

            $comment_id = wp_new_comment($commentdata);

            if(is_wp_error($comment_id))
            {
                return $this->pingback_error(0, $comment_id->get_error_message());
            }

            do_action('pingback_post', $comment_id);

            /* translators: 1: URL of the page linked from, 2: URL of the page linked to. */

            return sprintf(__('Pingback from %1$s to %2$s registered. Keep the web talking! :-)'), $pagelinkedfrom, $pagelinkedto);
        }

        /*
	 * Pingback functions.
	 * Specs on www.hixie.ch/specs/pingback/pingback
	 */

        protected function pingback_error($code, $message)
        {
            return apply_filters('xmlrpc_pingback_error', new IXR_Error($code, $message));
        }

        public function pingback_extensions_getPingbacks($url)
        {
            global $wpdb;

            do_action('xmlrpc_call', 'pingback.extensions.getPingbacks', $url, $this);

            $url = $this->escape($url);

            $post_id = url_to_postid($url);
            if(! $post_id)
            {
                // We aren't sure that the resource is available and/or pingback enabled.
                return $this->pingback_error(33, __('The specified target URL cannot be used as a target. It either does not exist, or it is not a pingback-enabled resource.'));
            }

            $actual_post = get_post($post_id, ARRAY_A);

            if(! $actual_post)
            {
                // No such post = resource not found.
                return $this->pingback_error(32, __('The specified target URL does not exist.'));
            }

            $comments = $wpdb->get_results($wpdb->prepare("SELECT comment_author_url, comment_content, comment_author_IP, comment_type FROM $wpdb->comments WHERE comment_post_ID = %d", $post_id));

            if(! $comments)
            {
                return [];
            }

            $pingbacks = [];
            foreach($comments as $comment)
            {
                if('pingback' === $comment->comment_type)
                {
                    $pingbacks[] = $comment->comment_author_url;
                }
            }

            return $pingbacks;
        }

        private function _is_greater_than_one($count)
        {
            return $count > 1;
        }
    }
