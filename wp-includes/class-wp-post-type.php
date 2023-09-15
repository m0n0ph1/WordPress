<?php

    #[AllowDynamicProperties]
    final class WP_Post_Type
    {
        protected static $default_labels = [];

        public $name;

        public $label;

        public $labels;

        public $description = '';

        public $public = false;

        public $hierarchical = false;

        public $exclude_from_search = null;

        public $publicly_queryable = null;

        public $show_ui = null;

        public $show_in_menu = null;

        public $show_in_nav_menus = null;

        public $show_in_admin_bar = null;

        public $menu_position = null;

        public $menu_icon = null;

        public $capability_type = 'post';

        public $map_meta_cap = false;

        public $register_meta_box_cb = null;

        public $taxonomies = [];

        public $has_archive = false;

        public $query_var;

        public $can_export = true;

        public $delete_with_user = null;

        public $template = [];

        public $template_lock = false;

        public $_builtin = false;

        public $_edit_link = 'post.php?post=%d';

        public $cap;

        public $rewrite;

        public $supports;

        public $show_in_rest;

        public $rest_base;

        public $rest_namespace;

        public $rest_controller_class;

        public $rest_controller;

        public function __construct($post_type, $args = [])
        {
            $this->name = $post_type;

            $this->set_props($args);
        }

        public function set_props($args)
        {
            $args = wp_parse_args($args);

            $args = apply_filters('register_post_type_args', $args, $this->name);

            $post_type = $this->name;

            $args = apply_filters("register_{$post_type}_post_type_args", $args, $this->name);

            $has_edit_link = ! empty($args['_edit_link']);

            // Args prefixed with an underscore are reserved for internal use.
            $defaults = [
                'labels' => [],
                'description' => '',
                'public' => false,
                'hierarchical' => false,
                'exclude_from_search' => null,
                'publicly_queryable' => null,
                'show_ui' => null,
                'show_in_menu' => null,
                'show_in_nav_menus' => null,
                'show_in_admin_bar' => null,
                'menu_position' => null,
                'menu_icon' => null,
                'capability_type' => 'post',
                'capabilities' => [],
                'map_meta_cap' => null,
                'supports' => [],
                'register_meta_box_cb' => null,
                'taxonomies' => [],
                'has_archive' => false,
                'rewrite' => true,
                'query_var' => true,
                'can_export' => true,
                'delete_with_user' => null,
                'show_in_rest' => false,
                'rest_base' => false,
                'rest_namespace' => false,
                'rest_controller_class' => false,
                'template' => [],
                'template_lock' => false,
                '_builtin' => false,
                '_edit_link' => 'post.php?post=%d',
            ];

            $args = array_merge($defaults, $args);

            $args['name'] = $this->name;

            // If not set, default to the setting for 'public'.
            if(null === $args['publicly_queryable'])
            {
                $args['publicly_queryable'] = $args['public'];
            }

            // If not set, default to the setting for 'public'.
            if(null === $args['show_ui'])
            {
                $args['show_ui'] = $args['public'];
            }

            // If not set, default rest_namespace to wp/v2 if show_in_rest is true.
            if(false === $args['rest_namespace'] && ! empty($args['show_in_rest']))
            {
                $args['rest_namespace'] = 'wp/v2';
            }

            // If not set, default to the setting for 'show_ui'.
            if(null === $args['show_in_menu'] || ! $args['show_ui'])
            {
                $args['show_in_menu'] = $args['show_ui'];
            }

            // If not set, default to the setting for 'show_in_menu'.
            if(null === $args['show_in_admin_bar'])
            {
                $args['show_in_admin_bar'] = (bool) $args['show_in_menu'];
            }

            // If not set, default to the setting for 'public'.
            if(null === $args['show_in_nav_menus'])
            {
                $args['show_in_nav_menus'] = $args['public'];
            }

            // If not set, default to true if not public, false if public.
            if(null === $args['exclude_from_search'])
            {
                $args['exclude_from_search'] = ! $args['public'];
            }

            // Back compat with quirky handling in version 3.0. #14122.
            if(
                empty($args['capabilities']) && null === $args['map_meta_cap'] && in_array($args['capability_type'], [
                    'post',
                    'page'
                ],                                                                         true)
            )
            {
                $args['map_meta_cap'] = true;
            }

            // If not set, default to false.
            if(null === $args['map_meta_cap'])
            {
                $args['map_meta_cap'] = false;
            }

            // If there's no specified edit link and no UI, remove the edit link.
            if(! $args['show_ui'] && ! $has_edit_link)
            {
                $args['_edit_link'] = '';
            }

            $this->cap = get_post_type_capabilities((object) $args);
            unset($args['capabilities']);

            if(is_array($args['capability_type']))
            {
                $args['capability_type'] = $args['capability_type'][0];
            }

            if(false !== $args['query_var'])
            {
                if(true === $args['query_var'])
                {
                    $args['query_var'] = $this->name;
                }
                else
                {
                    $args['query_var'] = sanitize_title_with_dashes($args['query_var']);
                }
            }

            if(false !== $args['rewrite'] && (is_admin() || get_option('permalink_structure')))
            {
                if(! is_array($args['rewrite']))
                {
                    $args['rewrite'] = [];
                }
                if(empty($args['rewrite']['slug']))
                {
                    $args['rewrite']['slug'] = $this->name;
                }
                if(! isset($args['rewrite']['with_front']))
                {
                    $args['rewrite']['with_front'] = true;
                }
                if(! isset($args['rewrite']['pages']))
                {
                    $args['rewrite']['pages'] = true;
                }
                if(! isset($args['rewrite']['feeds']) || ! $args['has_archive'])
                {
                    $args['rewrite']['feeds'] = (bool) $args['has_archive'];
                }
                if(! isset($args['rewrite']['ep_mask']))
                {
                    if(isset($args['permalink_epmask']))
                    {
                        $args['rewrite']['ep_mask'] = $args['permalink_epmask'];
                    }
                    else
                    {
                        $args['rewrite']['ep_mask'] = EP_PERMALINK;
                    }
                }
            }

            foreach($args as $property_name => $property_value)
            {
                $this->$property_name = $property_value;
            }

            $this->labels = get_post_type_labels($this);
            $this->label = $this->labels->name;
        }

        public static function get_default_labels()
        {
            if(! empty(self::$default_labels))
            {
                return self::$default_labels;
            }

            self::$default_labels = [
                'name' => [_x('Posts', 'post type general name'), _x('Pages', 'post type general name')],
                'singular_name' => [_x('Post', 'post type singular name'), _x('Page', 'post type singular name')],
                'add_new' => [__('Add New Post'), __('Add New Page')],
                'add_new_item' => [__('Add New Post'), __('Add New Page')],
                'edit_item' => [__('Edit Post'), __('Edit Page')],
                'new_item' => [__('New Post'), __('New Page')],
                'view_item' => [__('View Post'), __('View Page')],
                'view_items' => [__('View Posts'), __('View Pages')],
                'search_items' => [__('Search Posts'), __('Search Pages')],
                'not_found' => [__('No posts found.'), __('No pages found.')],
                'not_found_in_trash' => [__('No posts found in Trash.'), __('No pages found in Trash.')],
                'parent_item_colon' => [null, __('Parent Page:')],
                'all_items' => [__('All Posts'), __('All Pages')],
                'archives' => [__('Post Archives'), __('Page Archives')],
                'attributes' => [__('Post Attributes'), __('Page Attributes')],
                'insert_into_item' => [__('Insert into post'), __('Insert into page')],
                'uploaded_to_this_item' => [__('Uploaded to this post'), __('Uploaded to this page')],
                'featured_image' => [_x('Featured image', 'post'), _x('Featured image', 'page')],
                'set_featured_image' => [_x('Set featured image', 'post'), _x('Set featured image', 'page')],
                'remove_featured_image' => [_x('Remove featured image', 'post'), _x('Remove featured image', 'page')],
                'use_featured_image' => [_x('Use as featured image', 'post'), _x('Use as featured image', 'page')],
                'filter_items_list' => [__('Filter posts list'), __('Filter pages list')],
                'filter_by_date' => [__('Filter by date'), __('Filter by date')],
                'items_list_navigation' => [__('Posts list navigation'), __('Pages list navigation')],
                'items_list' => [__('Posts list'), __('Pages list')],
                'item_published' => [__('Post published.'), __('Page published.')],
                'item_published_privately' => [__('Post published privately.'), __('Page published privately.')],
                'item_reverted_to_draft' => [__('Post reverted to draft.'), __('Page reverted to draft.')],
                'item_trashed' => [__('Post trashed.'), __('Page trashed.')],
                'item_scheduled' => [__('Post scheduled.'), __('Page scheduled.')],
                'item_updated' => [__('Post updated.'), __('Page updated.')],
                'item_link' => [
                    _x('Post Link', 'navigation link block title'),
                    _x('Page Link', 'navigation link block title'),
                ],
                'item_link_description' => [
                    _x('A link to a post.', 'navigation link block description'),
                    _x('A link to a page.', 'navigation link block description'),
                ],
            ];

            return self::$default_labels;
        }

        public static function reset_default_labels()
        {
            self::$default_labels = [];
        }

        public function add_supports()
        {
            if(! empty($this->supports))
            {
                foreach($this->supports as $feature => $args)
                {
                    if(is_array($args))
                    {
                        add_post_type_support($this->name, $feature, $args);
                    }
                    else
                    {
                        add_post_type_support($this->name, $args);
                    }
                }
                unset($this->supports);
            }
            elseif(false !== $this->supports)
            {
                // Add default features.
                add_post_type_support($this->name, ['title', 'editor']);
            }
        }

        public function add_rewrite_rules()
        {
            global $wp_rewrite, $wp;

            if(false !== $this->query_var && $wp && is_post_type_viewable($this))
            {
                $wp->add_query_var($this->query_var);
            }

            if(false !== $this->rewrite && (is_admin() || get_option('permalink_structure')))
            {
                if($this->hierarchical)
                {
                    add_rewrite_tag("%$this->name%", '(.+?)', $this->query_var ? "{$this->query_var}=" : "post_type=$this->name&pagename=");
                }
                else
                {
                    add_rewrite_tag("%$this->name%", '([^/]+)', $this->query_var ? "{$this->query_var}=" : "post_type=$this->name&name=");
                }

                if($this->has_archive)
                {
                    $archive_slug = true === $this->has_archive ? $this->rewrite['slug'] : $this->has_archive;
                    if($this->rewrite['with_front'])
                    {
                        $archive_slug = substr($wp_rewrite->front, 1).$archive_slug;
                    }
                    else
                    {
                        $archive_slug = $wp_rewrite->root.$archive_slug;
                    }

                    add_rewrite_rule("{$archive_slug}/?$", "index.php?post_type=$this->name", 'top');
                    if($this->rewrite['feeds'] && $wp_rewrite->feeds)
                    {
                        $feeds = '('.trim(implode('|', $wp_rewrite->feeds)).')';
                        add_rewrite_rule("{$archive_slug}/feed/$feeds/?$", "index.php?post_type=$this->name".'&feed=$matches[1]', 'top');
                        add_rewrite_rule("{$archive_slug}/$feeds/?$", "index.php?post_type=$this->name".'&feed=$matches[1]', 'top');
                    }
                    if($this->rewrite['pages'])
                    {
                        add_rewrite_rule("{$archive_slug}/{$wp_rewrite->pagination_base}/([0-9]{1,})/?$", "index.php?post_type=$this->name".'&paged=$matches[1]', 'top');
                    }
                }

                $permastruct_args = $this->rewrite;
                $permastruct_args['feed'] = $permastruct_args['feeds'];
                add_permastruct($this->name, "{$this->rewrite['slug']}/%$this->name%", $permastruct_args);
            }
        }

        public function register_meta_boxes()
        {
            if($this->register_meta_box_cb)
            {
                add_action('add_meta_boxes_'.$this->name, $this->register_meta_box_cb, 10, 1);
            }
        }

        public function add_hooks()
        {
            add_action('future_'.$this->name, '_future_post_hook', 5, 2);
        }

        public function register_taxonomies()
        {
            foreach($this->taxonomies as $taxonomy)
            {
                register_taxonomy_for_object_type($taxonomy, $this->name);
            }
        }

        public function remove_supports()
        {
            global $_wp_post_type_features;

            unset($_wp_post_type_features[$this->name]);
        }

        public function remove_rewrite_rules()
        {
            global $wp, $wp_rewrite, $post_type_meta_caps;

            // Remove query var.
            if(false !== $this->query_var)
            {
                $wp->remove_query_var($this->query_var);
            }

            // Remove any rewrite rules, permastructs, and rules.
            if(false !== $this->rewrite)
            {
                remove_rewrite_tag("%$this->name%");
                remove_permastruct($this->name);
                foreach($wp_rewrite->extra_rules_top as $regex => $query)
                {
                    if(str_contains($query, "index.php?post_type=$this->name"))
                    {
                        unset($wp_rewrite->extra_rules_top[$regex]);
                    }
                }
            }

            // Remove registered custom meta capabilities.
            foreach($this->cap as $cap)
            {
                unset($post_type_meta_caps[$cap]);
            }
        }

        public function unregister_meta_boxes()
        {
            if($this->register_meta_box_cb)
            {
                remove_action('add_meta_boxes_'.$this->name, $this->register_meta_box_cb, 10);
            }
        }

        public function unregister_taxonomies()
        {
            foreach(get_object_taxonomies($this->name) as $taxonomy)
            {
                unregister_taxonomy_for_object_type($taxonomy, $this->name);
            }
        }

        public function remove_hooks()
        {
            remove_action('future_'.$this->name, '_future_post_hook', 5);
        }

        public function get_rest_controller()
        {
            if(! $this->show_in_rest)
            {
                return null;
            }

            $class = $this->rest_controller_class ? $this->rest_controller_class : WP_REST_Posts_Controller::class;

            if(! class_exists($class) || ! is_subclass_of($class, WP_REST_Controller::class))
            {
                return null;
            }

            if(! $this->rest_controller)
            {
                $this->rest_controller = new $class($this->name);
            }

            if(! ($this->rest_controller instanceof $class))
            {
                return null;
            }

            return $this->rest_controller;
        }
    }
