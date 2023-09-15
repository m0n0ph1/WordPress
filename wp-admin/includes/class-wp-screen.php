<?php

    #[AllowDynamicProperties]
    final class WP_Screen
    {
        private static $_old_compat_help = [];

        private static $_registry = [];

        public $action;

        public $base;

        public $id;

        public $is_network;

        public $is_user;

        public $parent_base;

        public $parent_file;

        public $post_type;

        public $taxonomy;

        public $is_block_editor = false;

        protected $in_admin;

        private $columns = 0;

        private $_help_tabs = [];

        private $_help_sidebar = '';

        private $_screen_reader_content = [];

        private $_options = [];

        private $_show_screen_options;

        private $_screen_settings;

        private function __construct() {}

        public static function get($hook_name = '')
        {
            if($hook_name instanceof WP_Screen)
            {
                return $hook_name;
            }

            $id = '';
            $post_type = null;
            $taxonomy = null;
            $in_admin = false;
            $action = '';
            $is_block_editor = false;

            if($hook_name)
            {
                $id = $hook_name;
            }
            elseif(! empty($GLOBALS['hook_suffix']))
            {
                $id = $GLOBALS['hook_suffix'];
            }

            // For those pesky meta boxes.
            if($hook_name && post_type_exists($hook_name))
            {
                $post_type = $id;
                $id = 'post'; // Changes later. Ends up being $base.
            }
            else
            {
                if(str_ends_with($id, '.php'))
                {
                    $id = substr($id, 0, -4);
                }

                if(in_array($id, ['post-new', 'link-add', 'media-new', 'user-new'], true))
                {
                    $id = substr($id, 0, -4);
                    $action = 'add';
                }
            }

            if(! $post_type && $hook_name)
            {
                if(str_ends_with($id, '-network'))
                {
                    $id = substr($id, 0, -8);
                    $in_admin = 'network';
                }
                elseif(str_ends_with($id, '-user'))
                {
                    $id = substr($id, 0, -5);
                    $in_admin = 'user';
                }

                $id = sanitize_key($id);
                if('edit-comments' !== $id && 'edit-tags' !== $id && str_starts_with($id, 'edit-'))
                {
                    $maybe = substr($id, 5);
                    if(taxonomy_exists($maybe))
                    {
                        $id = 'edit-tags';
                        $taxonomy = $maybe;
                    }
                    elseif(post_type_exists($maybe))
                    {
                        $id = 'edit';
                        $post_type = $maybe;
                    }
                }

                if(! $in_admin)
                {
                    $in_admin = 'site';
                }
            }
            else
            {
                if(defined('WP_NETWORK_ADMIN') && WP_NETWORK_ADMIN)
                {
                    $in_admin = 'network';
                }
                elseif(defined('WP_USER_ADMIN') && WP_USER_ADMIN)
                {
                    $in_admin = 'user';
                }
                else
                {
                    $in_admin = 'site';
                }
            }

            if('index' === $id)
            {
                $id = 'dashboard';
            }
            elseif('front' === $id)
            {
                $in_admin = false;
            }

            $base = $id;

            // If this is the current screen, see if we can be more accurate for post types and taxonomies.
            if(! $hook_name)
            {
                if(isset($_REQUEST['post_type']))
                {
                    $post_type = post_type_exists($_REQUEST['post_type']) ? $_REQUEST['post_type'] : false;
                }
                if(isset($_REQUEST['taxonomy']))
                {
                    $taxonomy = taxonomy_exists($_REQUEST['taxonomy']) ? $_REQUEST['taxonomy'] : false;
                }

                switch($base)
                {
                    case 'post':
                        if(isset($_GET['post']) && isset($_POST['post_ID']) && (int) $_GET['post'] !== (int) $_POST['post_ID'])
                        {
                            wp_die(__('A post ID mismatch has been detected.'), __('Sorry, you are not allowed to edit this item.'), 400);
                        }
                        elseif(isset($_GET['post']))
                        {
                            $post_id = (int) $_GET['post'];
                        }
                        elseif(isset($_POST['post_ID']))
                        {
                            $post_id = (int) $_POST['post_ID'];
                        }
                        else
                        {
                            $post_id = 0;
                        }

                        if($post_id)
                        {
                            $post = get_post($post_id);
                            if($post)
                            {
                                $post_type = $post->post_type;

                                $replace_editor = apply_filters('replace_editor', false, $post);

                                if(! $replace_editor)
                                {
                                    $is_block_editor = use_block_editor_for_post($post);
                                }
                            }
                        }
                        break;
                    case 'edit-tags':
                    case 'term':
                        if(null === $post_type && is_object_in_taxonomy('post', $taxonomy ? $taxonomy : 'post_tag'))
                        {
                            $post_type = 'post';
                        }
                        break;
                    case 'upload':
                        $post_type = 'attachment';
                        break;
                }
            }

            switch($base)
            {
                case 'post':
                    if(null === $post_type)
                    {
                        $post_type = 'post';
                    }

                    // When creating a new post, use the default block editor support value for the post type.
                    if(empty($post_id))
                    {
                        $is_block_editor = use_block_editor_for_post_type($post_type);
                    }

                    $id = $post_type;
                    break;
                case 'edit':
                    if(null === $post_type)
                    {
                        $post_type = 'post';
                    }
                    $id .= '-'.$post_type;
                    break;
                case 'edit-tags':
                case 'term':
                    if(null === $taxonomy)
                    {
                        $taxonomy = 'post_tag';
                    }
                    // The edit-tags ID does not contain the post type. Look for it in the request.
                    if(null === $post_type)
                    {
                        $post_type = 'post';
                        if(isset($_REQUEST['post_type']) && post_type_exists($_REQUEST['post_type']))
                        {
                            $post_type = $_REQUEST['post_type'];
                        }
                    }

                    $id = 'edit-'.$taxonomy;
                    break;
            }

            if('network' === $in_admin)
            {
                $id .= '-network';
                $base .= '-network';
            }
            elseif('user' === $in_admin)
            {
                $id .= '-user';
                $base .= '-user';
            }

            if(isset(self::$_registry[$id]))
            {
                $screen = self::$_registry[$id];
                if(get_current_screen() === $screen)
                {
                    return $screen;
                }
            }
            else
            {
                $screen = new self();
                $screen->id = $id;
            }

            $screen->base = $base;
            $screen->action = $action;
            $screen->post_type = (string) $post_type;
            $screen->taxonomy = (string) $taxonomy;
            $screen->is_user = ('user' === $in_admin);
            $screen->is_network = ('network' === $in_admin);
            $screen->in_admin = $in_admin;
            $screen->is_block_editor = $is_block_editor;

            self::$_registry[$id] = $screen;

            return $screen;
        }

        public static function add_old_compat_help($screen, $help)
        {
            self::$_old_compat_help[$screen->id] = $help;
        }

        public function set_current_screen()
        {
            global $current_screen, $taxnow, $typenow;

            $current_screen = $this;
            $typenow = $this->post_type;
            $taxnow = $this->taxonomy;

            do_action('current_screen', $current_screen);
        }

        public function in_admin($admin = null)
        {
            if(empty($admin))
            {
                return (bool) $this->in_admin;
            }

            return ($admin === $this->in_admin);
        }

        public function is_block_editor($set = null)
        {
            if(null !== $set)
            {
                $this->is_block_editor = (bool) $set;
            }

            return $this->is_block_editor;
        }

        public function set_parentage($parent_file)
        {
            $this->parent_file = $parent_file;
            [$this->parent_base] = explode('?', $parent_file);
            $this->parent_base = str_replace('.php', '', $this->parent_base);
        }

        public function remove_option($option)
        {
            unset($this->_options[$option]);
        }

        public function remove_options()
        {
            $this->_options = [];
        }

        public function get_options()
        {
            return $this->_options;
        }

        public function get_help_tab($id)
        {
            if(! isset($this->_help_tabs[$id]))
            {
                return null;
            }

            return $this->_help_tabs[$id];
        }

        public function remove_help_tab($id)
        {
            unset($this->_help_tabs[$id]);
        }

        public function remove_help_tabs()
        {
            $this->_help_tabs = [];
        }

        public function get_screen_reader_content()
        {
            return $this->_screen_reader_content;
        }

        public function set_screen_reader_content($content = [])
        {
            $defaults = [
                'heading_views' => __('Filter items list'),
                'heading_pagination' => __('Items list navigation'),
                'heading_list' => __('Items list'),
            ];
            $content = wp_parse_args($content, $defaults);

            $this->_screen_reader_content = $content;
        }

        public function get_screen_reader_text($key)
        {
            if(! isset($this->_screen_reader_content[$key]))
            {
                return null;
            }

            return $this->_screen_reader_content[$key];
        }

        public function remove_screen_reader_content()
        {
            $this->_screen_reader_content = [];
        }

        public function render_screen_meta()
        {
            self::$_old_compat_help = apply_filters_deprecated('contextual_help_list', [
                self::$_old_compat_help,
                $this,
            ],                                                 '3.3.0', 'get_current_screen()->add_help_tab(), get_current_screen()->remove_help_tab()');

            $old_help = isset(self::$_old_compat_help[$this->id]) ? self::$_old_compat_help[$this->id] : '';

            $old_help = apply_filters_deprecated('contextual_help', [
                $old_help,
                $this->id,
                $this,
            ],                                   '3.3.0', 'get_current_screen()->add_help_tab(), get_current_screen()->remove_help_tab()');

            // Default help only if there is no old-style block of text and no new-style help tabs.
            if(empty($old_help) && ! $this->get_help_tabs())
            {
                $default_help = apply_filters_deprecated('default_contextual_help', [''], '3.3.0', 'get_current_screen()->add_help_tab(), get_current_screen()->remove_help_tab()');
                if($default_help)
                {
                    $old_help = '<p>'.$default_help.'</p>';
                }
            }

            if($old_help)
            {
                $this->add_help_tab([
                                        'id' => 'old-contextual-help',
                                        'title' => __('Overview'),
                                        'content' => $old_help,
                                    ]);
            }

            $help_sidebar = $this->get_help_sidebar();

            $help_class = 'hidden';
            if(! $help_sidebar)
            {
                $help_class .= ' no-sidebar';
            }

            // Time to render!
            ?>
            <div id="screen-meta" class="metabox-prefs">

                <div id="contextual-help-wrap"
                     class="<?php echo esc_attr($help_class); ?>"
                     tabindex="-1"
                     aria-label="<?php esc_attr_e('Contextual Help Tab'); ?>">
                    <div id="contextual-help-back"></div>
                    <div id="contextual-help-columns">
                        <div class="contextual-help-tabs">
                            <ul>
                                <?php
                                    $class = ' class="active"';
                                    foreach($this->get_help_tabs() as $tab) :
                                        $link_id = "tab-link-{$tab['id']}";
                                        $panel_id = "tab-panel-{$tab['id']}";
                                        ?>

                                        <li id="<?php echo esc_attr($link_id); ?>"<?php echo $class; ?>>
                                            <a href="<?php echo esc_url("#$panel_id"); ?>"
                                               aria-controls="<?php echo esc_attr($panel_id); ?>">
                                                <?php echo esc_html($tab['title']); ?>
                                            </a>
                                        </li>
                                        <?php
                                        $class = '';
                                    endforeach;
                                ?>
                            </ul>
                        </div>

                        <?php if($help_sidebar) : ?>
                            <div class="contextual-help-sidebar">
                                <?php echo $help_sidebar; ?>
                            </div>
                        <?php endif; ?>

                        <div class="contextual-help-tabs-wrap">
                            <?php
                                $classes = 'help-tab-content active';
                                foreach($this->get_help_tabs() as $tab) :
                                    $panel_id = "tab-panel-{$tab['id']}";
                                    ?>

                                    <div id="<?php echo esc_attr($panel_id); ?>" class="<?php echo $classes; ?>">
                                        <?php
                                            // Print tab content.
                                            echo $tab['content'];

                                            // If it exists, fire tab callback.
                                            if(! empty($tab['callback']))
                                            {
                                                call_user_func_array($tab['callback'], [$this, $tab]);
                                            }
                                        ?>
                                    </div>
                                    <?php
                                    $classes = 'help-tab-content';
                                endforeach;
                            ?>
                        </div>
                    </div>
                </div>
                <?php
                    // Setup layout columns.

                    $columns = apply_filters('screen_layout_columns', [], $this->id, $this);

                    if(! empty($columns) && isset($columns[$this->id]))
                    {
                        $this->add_option('layout_columns', ['max' => $columns[$this->id]]);
                    }

                    if($this->get_option('layout_columns'))
                    {
                        $this->columns = (int) get_user_option("screen_layout_$this->id");

                        if(! $this->columns && $this->get_option('layout_columns', 'default'))
                        {
                            $this->columns = $this->get_option('layout_columns', 'default');
                        }
                    }
                    $GLOBALS['screen_layout_columns'] = $this->columns; // Set the global for back-compat.

                    // Add screen options.
                    if($this->show_screen_options())
                    {
                        $this->render_screen_options();
                    }
                ?>
            </div>
            <?php
            if(! $this->get_help_tabs() && ! $this->show_screen_options())
            {
                return;
            }
            ?>
            <div id="screen-meta-links">
                <?php if($this->show_screen_options()) : ?>
                    <div id="screen-options-link-wrap" class="hide-if-no-js screen-meta-toggle">
                        <button type="button"
                                id="show-settings-link"
                                class="button show-settings"
                                aria-controls="screen-options-wrap"
                                aria-expanded="false"><?php _e('Screen Options'); ?></button>
                    </div>
                <?php
                endif;
                    if($this->get_help_tabs()) :
                        ?>
                        <div id="contextual-help-link-wrap" class="hide-if-no-js screen-meta-toggle">
                            <button type="button"
                                    id="contextual-help-link"
                                    class="button show-settings"
                                    aria-controls="contextual-help-wrap"
                                    aria-expanded="false"><?php _e('Help'); ?></button>
                        </div>
                    <?php endif; ?>
            </div>
            <?php
        }

        public function get_help_tabs()
        {
            $help_tabs = $this->_help_tabs;

            $priorities = [];
            foreach($help_tabs as $help_tab)
            {
                if(isset($priorities[$help_tab['priority']]))
                {
                    $priorities[$help_tab['priority']][] = $help_tab;
                }
                else
                {
                    $priorities[$help_tab['priority']] = [$help_tab];
                }
            }

            ksort($priorities);

            $sorted = [];
            foreach($priorities as $list)
            {
                foreach($list as $tab)
                {
                    $sorted[$tab['id']] = $tab;
                }
            }

            return $sorted;
        }

        public function add_help_tab($args)
        {
            $defaults = [
                'title' => false,
                'id' => false,
                'content' => '',
                'callback' => false,
                'priority' => 10,
            ];
            $args = wp_parse_args($args, $defaults);

            $args['id'] = sanitize_html_class($args['id']);

            // Ensure we have an ID and title.
            if(! $args['id'] || ! $args['title'])
            {
                return;
            }

            // Allows for overriding an existing tab with that ID.
            $this->_help_tabs[$args['id']] = $args;
        }

        public function get_help_sidebar()
        {
            return $this->_help_sidebar;
        }

        public function set_help_sidebar($content)
        {
            $this->_help_sidebar = $content;
        }

        public function add_option($option, $args = [])
        {
            $this->_options[$option] = $args;
        }

        public function get_option($option, $key = false)
        {
            if(! isset($this->_options[$option]))
            {
                return null;
            }
            if($key)
            {
                if(isset($this->_options[$option][$key]))
                {
                    return $this->_options[$option][$key];
                }

                return null;
            }

            return $this->_options[$option];
        }

        public function show_screen_options()
        {
            global $wp_meta_boxes;

            if(is_bool($this->_show_screen_options))
            {
                return $this->_show_screen_options;
            }

            $columns = get_column_headers($this);

            $show_screen = ! empty($wp_meta_boxes[$this->id]) || $columns || $this->get_option('per_page');

            $this->_screen_settings = '';

            if('post' === $this->base)
            {
                $expand = '<fieldset class="editor-expand hidden"><legend>'.__('Additional settings').'</legend><label for="editor-expand-toggle">';
                $expand .= '<input type="checkbox" id="editor-expand-toggle"'.checked(get_user_setting('editor_expand', 'on'), 'on', false).' />';
                $expand .= __('Enable full-height editor and distraction-free functionality.').'</label></fieldset>';
                $this->_screen_settings = $expand;
            }

            $this->_screen_settings = apply_filters('screen_settings', $this->_screen_settings, $this);

            if($this->_screen_settings || $this->_options)
            {
                $show_screen = true;
            }

            $this->_show_screen_options = apply_filters('screen_options_show_screen', $show_screen, $this);

            return $this->_show_screen_options;
        }

        public function render_screen_options($options = [])
        {
            $options = wp_parse_args($options, [
                'wrap' => true,
            ]);

            $wrapper_start = '';
            $wrapper_end = '';
            $form_start = '';
            $form_end = '';

            // Output optional wrapper.
            if($options['wrap'])
            {
                $wrapper_start = '<div id="screen-options-wrap" class="hidden" tabindex="-1" aria-label="'.esc_attr__('Screen Options Tab').'">';
                $wrapper_end = '</div>';
            }

            // Don't output the form and nonce for the widgets accessibility mode links.
            if('widgets' !== $this->base)
            {
                $form_start = "\n<form id='adv-settings' method='post'>\n";
                $form_end = "\n".wp_nonce_field('screen-options-nonce', 'screenoptionnonce', false, false)."\n</form>\n";
            }

            echo $wrapper_start.$form_start;

            $this->render_meta_boxes_preferences();
            $this->render_list_table_columns_preferences();
            $this->render_screen_layout();
            $this->render_per_page_options();
            $this->render_view_mode();
            echo $this->_screen_settings;

            $show_button = apply_filters('screen_options_show_submit', false, $this);

            if($show_button)
            {
                submit_button(__('Apply'), 'primary', 'screen-options-apply', true);
            }

            echo $form_end.$wrapper_end;
        }

        public function render_meta_boxes_preferences()
        {
            global $wp_meta_boxes;

            if(! isset($wp_meta_boxes[$this->id]))
            {
                return;
            }
            ?>
            <fieldset class="metabox-prefs">
                <legend><?php _e('Screen elements'); ?></legend>
                <p>
                    <?php _e('Some screen elements can be shown or hidden by using the checkboxes.'); ?>
                    <?php _e('Expand or collapse the elements by clicking on their headings, and arrange them by dragging their headings or by clicking on the up and down arrows.'); ?>
                </p>
                <div class="metabox-prefs-container">
                    <?php

                        meta_box_prefs($this);

                        if('dashboard' === $this->id && has_action('welcome_panel') && current_user_can('edit_theme_options'))
                        {
                            if(isset($_GET['welcome']))
                            {
                                $welcome_checked = empty($_GET['welcome']) ? 0 : 1;
                                update_user_meta(get_current_user_id(), 'show_welcome_panel', $welcome_checked);
                            }
                            else
                            {
                                $welcome_checked = (int) get_user_meta(get_current_user_id(), 'show_welcome_panel', true);
                                if(2 === $welcome_checked && wp_get_current_user()->user_email !== get_option('admin_email'))
                                {
                                    $welcome_checked = false;
                                }
                            }
                            echo '<label for="wp_welcome_panel-hide">';
                            echo '<input type="checkbox" id="wp_welcome_panel-hide"'.checked((bool) $welcome_checked, true, false).' />';
                            echo _x('Welcome', 'Welcome panel')."</label>\n";
                        }
                    ?>
                </div>
            </fieldset>
            <?php
        }

        public function render_list_table_columns_preferences()
        {
            $columns = get_column_headers($this);
            $hidden = get_hidden_columns($this);

            if(! $columns)
            {
                return;
            }

            $legend = ! empty($columns['_title']) ? $columns['_title'] : __('Columns');
            ?>
            <fieldset class="metabox-prefs">
                <legend><?php echo $legend; ?></legend>
                <?php
                    $special = ['_title', 'cb', 'comment', 'media', 'name', 'title', 'username', 'blogname'];

                    foreach($columns as $column => $title)
                    {
                        // Can't hide these for they are special.
                        if(in_array($column, $special, true))
                        {
                            continue;
                        }

                        if(empty($title))
                        {
                            continue;
                        }

                        /*
                         * The Comments column uses HTML in the display name with some screen
                         * reader text. Make sure to strip tags from the Comments column
                         * title and any other custom column title plugins might add.
                         */
                        $title = wp_strip_all_tags($title);

                        $id = "$column-hide";
                        echo '<label>';
                        echo '<input class="hide-column-tog" name="'.$id.'" type="checkbox" id="'.$id.'" value="'.$column.'"'.checked(! in_array($column, $hidden, true), true, false).' />';
                        echo "$title</label>\n";
                    }
                ?>
            </fieldset>
            <?php
        }

        public function render_screen_layout()
        {
            if(! $this->get_option('layout_columns'))
            {
                return;
            }

            $screen_layout_columns = $this->get_columns();
            $num = $this->get_option('layout_columns', 'max');

            ?>
            <fieldset class='columns-prefs'>
                <legend class="screen-layout"><?php _e('Layout'); ?></legend>
                <?php for($i = 1; $i <= $num; ++$i) : ?>
                    <label class="columns-prefs-<?php echo $i; ?>">
                        <input type='radio'
                               name='screen_columns'
                               value='<?php echo esc_attr($i); ?>' <?php checked($screen_layout_columns, $i); ?> />
                        <?php
                            printf(/* translators: %s: Number of columns on the page. */ _n('%s column', '%s columns', $i), number_format_i18n($i));
                        ?>
                    </label>
                <?php endfor; ?>
            </fieldset>
            <?php
        }

        public function get_columns()
        {
            return $this->columns;
        }

        public function render_per_page_options()
        {
            if(null === $this->get_option('per_page'))
            {
                return;
            }

            $per_page_label = $this->get_option('per_page', 'label');
            if(null === $per_page_label)
            {
                $per_page_label = __('Number of items per page:');
            }

            $option = $this->get_option('per_page', 'option');
            if(! $option)
            {
                $option = str_replace('-', '_', "{$this->id}_per_page");
            }

            $per_page = (int) get_user_option($option);
            if(empty($per_page) || $per_page < 1)
            {
                $per_page = $this->get_option('per_page', 'default');
                if(! $per_page)
                {
                    $per_page = 20;
                }
            }

            if('edit_comments_per_page' === $option)
            {
                $comment_status = isset($_REQUEST['comment_status']) ? $_REQUEST['comment_status'] : 'all';

                $per_page = apply_filters('comments_per_page', $per_page, $comment_status);
            }
            elseif('categories_per_page' === $option)
            {
                $per_page = apply_filters('edit_categories_per_page', $per_page);
            }
            else
            {
                $per_page = apply_filters("{$option}", $per_page);
            }

            // Back compat.
            if(isset($this->post_type))
            {
                $per_page = apply_filters('edit_posts_per_page', $per_page, $this->post_type);
            }

            // This needs a submit button.
            add_filter('screen_options_show_submit', '__return_true');

            ?>
            <fieldset class="screen-options">
                <legend><?php _e('Pagination'); ?></legend>
                <?php if($per_page_label) : ?>
                    <label for="<?php echo esc_attr($option); ?>"><?php echo $per_page_label; ?></label>
                    <input type="number"
                           step="1"
                           min="1"
                           max="999"
                           class="screen-per-page"
                           name="wp_screen_options[value]"
                           id="<?php echo esc_attr($option); ?>"
                           maxlength="3"
                           value="<?php echo esc_attr($per_page); ?>"/>
                <?php endif; ?>
                <input type="hidden" name="wp_screen_options[option]" value="<?php echo esc_attr($option); ?>"/>
            </fieldset>
            <?php
        }

        public function render_view_mode()
        {
            global $mode;

            $screen = get_current_screen();

            // Currently only enabled for posts and comments lists.
            if('edit' !== $screen->base && 'edit-comments' !== $screen->base)
            {
                return;
            }

            $view_mode_post_types = get_post_types(['show_ui' => true]);

            $view_mode_post_types = apply_filters('view_mode_post_types', $view_mode_post_types);

            if('edit' === $screen->base && ! in_array($this->post_type, $view_mode_post_types, true))
            {
                return;
            }

            if(! isset($mode))
            {
                $mode = get_user_setting('posts_list_mode', 'list');
            }

            // This needs a submit button.
            add_filter('screen_options_show_submit', '__return_true');
            ?>
            <fieldset class="metabox-prefs view-mode">
                <legend><?php _e('View mode'); ?></legend>
                <label for="list-view-mode">
                    <input id="list-view-mode" type="radio" name="mode" value="list" <?php checked('list', $mode); ?> />
                    <?php _e('Compact view'); ?>
                </label>
                <label for="excerpt-view-mode">
                    <input id="excerpt-view-mode" type="radio" name="mode"
                           value="excerpt" <?php checked('excerpt', $mode); ?> />
                    <?php _e('Extended view'); ?>
                </label>
            </fieldset>
            <?php
        }

        public function render_screen_reader_content($key = '', $tag = 'h2')
        {
            if(! isset($this->_screen_reader_content[$key]))
            {
                return;
            }
            echo "<$tag class='screen-reader-text'>".$this->_screen_reader_content[$key]."</$tag>";
        }
    }
