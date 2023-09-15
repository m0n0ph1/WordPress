<?php

    #[AllowDynamicProperties]
    final class WP_Taxonomy
    {
        protected static $default_labels = [];

        public $name;

        public $label;

        public $labels;

        public $description = '';

        public $public = true;

        public $publicly_queryable = true;

        public $hierarchical = false;

        public $show_ui = true;

        public $show_in_menu = true;

        public $show_in_nav_menus = true;

        public $show_tagcloud = true;

        public $show_in_quick_edit = true;

        public $show_admin_column = false;

        public $meta_box_cb = null;

        public $meta_box_sanitize_cb = null;

        public $object_type = null;

        public $cap;

        public $rewrite;

        public $query_var;

        public $update_count_callback;

        public $show_in_rest;

        public $rest_base;

        public $rest_namespace;

        public $rest_controller_class;

        public $rest_controller;

        public $default_term;

        public $sort = null;

        public $args = null;

        public $_builtin;

        public function __construct($taxonomy, $object_type, $args = [])
        {
            $this->name = $taxonomy;

            $this->set_props($object_type, $args);
        }

        public function set_props($object_type, $args)
        {
            $args = wp_parse_args($args);

            $args = apply_filters('register_taxonomy_args', $args, $this->name, (array) $object_type);

            $taxonomy = $this->name;

            $args = apply_filters("register_{$taxonomy}_taxonomy_args", $args, $this->name, (array) $object_type);

            $defaults = [
                'labels' => [],
                'description' => '',
                'public' => true,
                'publicly_queryable' => null,
                'hierarchical' => false,
                'show_ui' => null,
                'show_in_menu' => null,
                'show_in_nav_menus' => null,
                'show_tagcloud' => null,
                'show_in_quick_edit' => null,
                'show_admin_column' => false,
                'meta_box_cb' => null,
                'meta_box_sanitize_cb' => null,
                'capabilities' => [],
                'rewrite' => true,
                'query_var' => $this->name,
                'update_count_callback' => '',
                'show_in_rest' => false,
                'rest_base' => false,
                'rest_namespace' => false,
                'rest_controller_class' => false,
                'default_term' => null,
                'sort' => null,
                'args' => null,
                '_builtin' => false,
            ];

            $args = array_merge($defaults, $args);

            // If not set, default to the setting for 'public'.
            if(null === $args['publicly_queryable'])
            {
                $args['publicly_queryable'] = $args['public'];
            }

            if(false !== $args['query_var'] && (is_admin() || false !== $args['publicly_queryable']))
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
            else
            {
                // Force 'query_var' to false for non-public taxonomies.
                $args['query_var'] = false;
            }

            if(false !== $args['rewrite'] && (is_admin() || get_option('permalink_structure')))
            {
                $args['rewrite'] = wp_parse_args($args['rewrite'], [
                    'with_front' => true,
                    'hierarchical' => false,
                    'ep_mask' => EP_NONE,
                ]);

                if(empty($args['rewrite']['slug']))
                {
                    $args['rewrite']['slug'] = sanitize_title_with_dashes($this->name);
                }
            }

            // If not set, default to the setting for 'public'.
            if(null === $args['show_ui'])
            {
                $args['show_ui'] = $args['public'];
            }

            // If not set, default to the setting for 'show_ui'.
            if(null === $args['show_in_menu'] || ! $args['show_ui'])
            {
                $args['show_in_menu'] = $args['show_ui'];
            }

            // If not set, default to the setting for 'public'.
            if(null === $args['show_in_nav_menus'])
            {
                $args['show_in_nav_menus'] = $args['public'];
            }

            // If not set, default to the setting for 'show_ui'.
            if(null === $args['show_tagcloud'])
            {
                $args['show_tagcloud'] = $args['show_ui'];
            }

            // If not set, default to the setting for 'show_ui'.
            if(null === $args['show_in_quick_edit'])
            {
                $args['show_in_quick_edit'] = $args['show_ui'];
            }

            // If not set, default rest_namespace to wp/v2 if show_in_rest is true.
            if(false === $args['rest_namespace'] && ! empty($args['show_in_rest']))
            {
                $args['rest_namespace'] = 'wp/v2';
            }

            $default_caps = [
                'manage_terms' => 'manage_categories',
                'edit_terms' => 'manage_categories',
                'delete_terms' => 'manage_categories',
                'assign_terms' => 'edit_posts',
            ];

            $args['cap'] = (object) array_merge($default_caps, $args['capabilities']);
            unset($args['capabilities']);

            $args['object_type'] = array_unique((array) $object_type);

            // If not set, use the default meta box.
            if(null === $args['meta_box_cb'])
            {
                if($args['hierarchical'])
                {
                    $args['meta_box_cb'] = 'post_categories_meta_box';
                }
                else
                {
                    $args['meta_box_cb'] = 'post_tags_meta_box';
                }
            }

            $args['name'] = $this->name;

            // Default meta box sanitization callback depends on the value of 'meta_box_cb'.
            if(null === $args['meta_box_sanitize_cb'])
            {
                switch($args['meta_box_cb'])
                {
                    case 'post_categories_meta_box':
                        $args['meta_box_sanitize_cb'] = 'taxonomy_meta_box_sanitize_cb_checkboxes';
                        break;

                    case 'post_tags_meta_box':
                    default:
                        $args['meta_box_sanitize_cb'] = 'taxonomy_meta_box_sanitize_cb_input';
                        break;
                }
            }

            // Default taxonomy term.
            if(! empty($args['default_term']))
            {
                if(! is_array($args['default_term']))
                {
                    $args['default_term'] = ['name' => $args['default_term']];
                }
                $args['default_term'] = wp_parse_args($args['default_term'], [
                    'name' => '',
                    'slug' => '',
                    'description' => '',
                ]);
            }

            foreach($args as $property_name => $property_value)
            {
                $this->$property_name = $property_value;
            }

            $this->labels = get_taxonomy_labels($this);
            $this->label = $this->labels->name;
        }

        public static function get_default_labels()
        {
            if(! empty(self::$default_labels))
            {
                return self::$default_labels;
            }

            $name_field_description = __('The name is how it appears on your site.');
            $slug_field_description = __('The &#8220;slug&#8221; is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.');
            $parent_field_description = __('Assign a parent term to create a hierarchy. The term Jazz, for example, would be the parent of Bebop and Big Band.');
            $desc_field_description = __('The description is not prominent by default; however, some themes may show it.');

            self::$default_labels = [
                'name' => [_x('Tags', 'taxonomy general name'), _x('Categories', 'taxonomy general name')],
                'singular_name' => [_x('Tag', 'taxonomy singular name'), _x('Category', 'taxonomy singular name')],
                'search_items' => [__('Search Tags'), __('Search Categories')],
                'popular_items' => [__('Popular Tags'), null],
                'all_items' => [__('All Tags'), __('All Categories')],
                'parent_item' => [null, __('Parent Category')],
                'parent_item_colon' => [null, __('Parent Category:')],
                'name_field_description' => [$name_field_description, $name_field_description],
                'slug_field_description' => [$slug_field_description, $slug_field_description],
                'parent_field_description' => [null, $parent_field_description],
                'desc_field_description' => [$desc_field_description, $desc_field_description],
                'edit_item' => [__('Edit Tag'), __('Edit Category')],
                'view_item' => [__('View Tag'), __('View Category')],
                'update_item' => [__('Update Tag'), __('Update Category')],
                'add_new_item' => [__('Add New Tag'), __('Add New Category')],
                'new_item_name' => [__('New Tag Name'), __('New Category Name')],
                'separate_items_with_commas' => [__('Separate tags with commas'), null],
                'add_or_remove_items' => [__('Add or remove tags'), null],
                'choose_from_most_used' => [__('Choose from the most used tags'), null],
                'not_found' => [__('No tags found.'), __('No categories found.')],
                'no_terms' => [__('No tags'), __('No categories')],
                'filter_by_item' => [null, __('Filter by category')],
                'items_list_navigation' => [__('Tags list navigation'), __('Categories list navigation')],
                'items_list' => [__('Tags list'), __('Categories list')],
                /* translators: Tab heading when selecting from the most used terms. */
                'most_used' => [_x('Most Used', 'tags'), _x('Most Used', 'categories')],
                'back_to_items' => [__('&larr; Go to Tags'), __('&larr; Go to Categories')],
                'item_link' => [
                    _x('Tag Link', 'navigation link block title'),
                    _x('Category Link', 'navigation link block title'),
                ],
                'item_link_description' => [
                    _x('A link to a tag.', 'navigation link block description'),
                    _x('A link to a category.', 'navigation link block description'),
                ],
            ];

            return self::$default_labels;
        }

        public static function reset_default_labels()
        {
            self::$default_labels = [];
        }

        public function add_rewrite_rules()
        {
            /* @var WP $wp */ global $wp;

            // Non-publicly queryable taxonomies should not register query vars, except in the admin.
            if(false !== $this->query_var && $wp)
            {
                $wp->add_query_var($this->query_var);
            }

            if(false !== $this->rewrite && (is_admin() || get_option('permalink_structure')))
            {
                if($this->hierarchical && $this->rewrite['hierarchical'])
                {
                    $tag = '(.+?)';
                }
                else
                {
                    $tag = '([^/]+)';
                }

                add_rewrite_tag("%$this->name%", $tag, $this->query_var ? "{$this->query_var}=" : "taxonomy=$this->name&term=");
                add_permastruct($this->name, "{$this->rewrite['slug']}/%$this->name%", $this->rewrite);
            }
        }

        public function remove_rewrite_rules()
        {
            /* @var WP $wp */ global $wp;

            // Remove query var.
            if(false !== $this->query_var)
            {
                $wp->remove_query_var($this->query_var);
            }

            // Remove rewrite tags and permastructs.
            if(false !== $this->rewrite)
            {
                remove_rewrite_tag("%$this->name%");
                remove_permastruct($this->name);
            }
        }

        public function add_hooks()
        {
            add_filter('wp_ajax_add-'.$this->name, '_wp_ajax_add_hierarchical_term');
        }

        public function remove_hooks()
        {
            remove_filter('wp_ajax_add-'.$this->name, '_wp_ajax_add_hierarchical_term');
        }

        public function get_rest_controller()
        {
            if(! $this->show_in_rest)
            {
                return null;
            }

            $class = $this->rest_controller_class ? $this->rest_controller_class : WP_REST_Terms_Controller::class;

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
