<?php

    #[AllowDynamicProperties]
    final class WP_Internal_Pointers
    {
        public static function enqueue_scripts($hook_suffix)
        {
            /*
             * Register feature pointers
             *
             * Format:
             *     array(
             *         hook_suffix => pointer callback
             *     )
             *
             * Example:
             *     array(
             *         'themes.php' => 'wp390_widgets'
             *     )
             */
            $registered_pointers = [// None currently.
            ];

            // Check if screen related pointer is registered.
            if(empty($registered_pointers[$hook_suffix]))
            {
                return;
            }

            $pointers = (array) $registered_pointers[$hook_suffix];

            /*
             * Specify required capabilities for feature pointers
             *
             * Format:
             *     array(
             *         pointer callback => Array of required capabilities
             *     )
             *
             * Example:
             *     array(
             *         'wp390_widgets' => array( 'edit_theme_options' )
             *     )
             */
            $caps_required = [// None currently.
            ];

            // Get dismissed pointers.
            $dismissed = explode(',', (string) get_user_meta(get_current_user_id(), 'dismissed_wp_pointers', true));

            $got_pointers = false;
            foreach(array_diff($pointers, $dismissed) as $pointer)
            {
                if(isset($caps_required[$pointer]))
                {
                    foreach($caps_required[$pointer] as $cap)
                    {
                        if(! current_user_can($cap))
                        {
                            continue 2;
                        }
                    }
                }

                // Bind pointer print function.
                add_action('admin_print_footer_scripts', ['WP_Internal_Pointers', 'pointer_'.$pointer]);
                $got_pointers = true;
            }

            if(! $got_pointers)
            {
                return;
            }

            // Add pointers script and style to queue.
            wp_enqueue_style('wp-pointer');
            wp_enqueue_script('wp-pointer');
        }

        public static function pointer_wp330_toolbar() {}

        public static function pointer_wp330_media_uploader() {}

        public static function pointer_wp330_saving_widgets() {}

        public static function pointer_wp340_customize_current_theme_link() {}

        public static function pointer_wp340_choose_image_from_library() {}

        public static function pointer_wp350_media() {}

        public static function pointer_wp360_revisions() {}

        public static function pointer_wp360_locks() {}

        public static function pointer_wp390_widgets() {}

        public static function pointer_wp410_dfw() {}

        public static function pointer_wp496_privacy() {}

        public static function dismiss_pointers_for_new_users($user_id)
        {
            add_user_meta($user_id, 'dismissed_wp_pointers', '');
        }

        private static function print_js($pointer_id, $selector, $args)
        {
            if(empty($pointer_id) || empty($selector) || empty($args) || empty($args['content']))
            {
                return;
            }

            ?>
            <script type="text/javascript">
                (function ($) {
                    var options = <?php echo wp_json_encode($args); ?>, setup;

                    if (!options)
                        return;

                    options = $.extend(options, {
                        close: function () {
                            $.post(ajaxurl, {
                                pointer: '<?php echo $pointer_id; ?>',
                                action: 'dismiss-wp-pointer'
                            });
                        }
                    });

                    setup = function () {
                        $('<?php echo $selector; ?>').first().pointer(options).pointer('open');
                    };

                    if (options.position && options.position.defer_loading)
                        $(window).bind('load.wp-pointers', setup);
                    else
                        $(function () {
                            setup();
                        });

                })(jQuery);
            </script>
            <?php
        }
    }
