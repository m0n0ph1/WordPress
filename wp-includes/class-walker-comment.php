<?php

    class Walker_Comment extends Walker
    {
        public $tree_type = 'comment';

        public $db_fields = [
            'parent' => 'comment_parent',
            'id' => 'comment_ID',
        ];

        public function start_lvl(&$output, $depth = 0, $args = [])
        {
            $GLOBALS['comment_depth'] = $depth + 1;

            switch($args['style'])
            {
                case 'div':
                    break;
                case 'ol':
                    $output .= '<ol class="children">'."\n";
                    break;
                case 'ul':
                default:
                    $output .= '<ul class="children">'."\n";
                    break;
            }
        }

        public function end_lvl(&$output, $depth = 0, $args = [])
        {
            $GLOBALS['comment_depth'] = $depth + 1;

            switch($args['style'])
            {
                case 'div':
                    break;
                case 'ol':
                    $output .= "</ol><!-- .children -->\n";
                    break;
                case 'ul':
                default:
                    $output .= "</ul><!-- .children -->\n";
                    break;
            }
        }

        public function display_element($element, &$children_elements, $max_depth, $depth, $args, &$output)
        {
            if(! $element)
            {
                return;
            }

            $id_field = $this->db_fields['id'];
            $id = $element->$id_field;

            parent::display_element($element, $children_elements, $max_depth, $depth, $args, $output);

            /*
             * If at the max depth, and the current element still has children, loop over those
             * and display them at this level. This is to prevent them being orphaned to the end
             * of the list.
             */
            if($max_depth <= $depth + 1 && isset($children_elements[$id]))
            {
                foreach($children_elements[$id] as $child)
                {
                    $this->display_element($child, $children_elements, $max_depth, $depth, $args, $output);
                }

                unset($children_elements[$id]);
            }
        }

        public function start_el(&$output, $data_object, $depth = 0, $args = [], $current_object_id = 0)
        {
            // Restores the more descriptive, specific name for use within this method.
            $comment = $data_object;

            ++$depth;
            $GLOBALS['comment_depth'] = $depth;
            $GLOBALS['comment'] = $comment;

            if(! empty($args['callback']))
            {
                ob_start();
                call_user_func($args['callback'], $comment, $args, $depth);
                $output .= ob_get_clean();

                return;
            }

            if('comment' === $comment->comment_type)
            {
                add_filter('comment_text', [$this, 'filter_comment_text'], 40, 2);
            }

            if(('pingback' === $comment->comment_type || 'trackback' === $comment->comment_type) && $args['short_ping'])
            {
                ob_start();
                $this->ping($comment, $depth, $args);
                $output .= ob_get_clean();
            }
            elseif('html5' === $args['format'])
            {
                ob_start();
                $this->html5_comment($comment, $depth, $args);
                $output .= ob_get_clean();
            }
            else
            {
                ob_start();
                $this->comment($comment, $depth, $args);
                $output .= ob_get_clean();
            }

            if('comment' === $comment->comment_type)
            {
                remove_filter('comment_text', [$this, 'filter_comment_text'], 40);
            }
        }

        protected function ping($comment, $depth, $args)
        {
            $tag = ('div' === $args['style']) ? 'div' : 'li';
            ?>
            <<?php echo $tag; ?> id="comment-<?php comment_ID(); ?>" <?php comment_class('', $comment); ?>>
            <div class="comment-body">
                <?php _e('Pingback:'); ?><?php comment_author_link($comment); ?><?php edit_comment_link(__('Edit'), '<span class="edit-link">', '</span>'); ?>
            </div>
            <?php
        }

        protected function html5_comment($comment, $depth, $args)
        {
            $tag = ('div' === $args['style']) ? 'div' : 'li';

            $commenter = wp_get_current_commenter();
            $show_pending_links = ! empty($commenter['comment_author']);

            if($commenter['comment_author_email'])
            {
                $moderation_note = __('Your comment is awaiting moderation.');
            }
            else
            {
                $moderation_note = __('Your comment is awaiting moderation. This is a preview; your comment will be visible after it has been approved.');
            }
            ?>
            <<?php echo $tag; ?> id="comment-<?php comment_ID(); ?>" <?php comment_class($this->has_children ? 'parent' : '', $comment); ?>>
            <article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
                <footer class="comment-meta">
                    <div class="comment-author vcard">
                        <?php
                            if(0 != $args['avatar_size'])
                            {
                                echo get_avatar($comment, $args['avatar_size']);
                            }
                        ?>
                        <?php
                            $comment_author = get_comment_author_link($comment);

                            if('0' == $comment->comment_approved && ! $show_pending_links)
                            {
                                $comment_author = get_comment_author($comment);
                            }

                            printf(/* translators: %s: Comment author link. */ __('%s <span class="says">says:</span>'), sprintf('<b class="fn">%s</b>', $comment_author));
                        ?>
                    </div><!-- .comment-author -->

                    <div class="comment-metadata">
                        <?php
                            printf('<a href="%s"><time datetime="%s">%s</time></a>', esc_url(get_comment_link($comment, $args)), get_comment_time('c'), sprintf(/* translators: 1: Comment date, 2: Comment time. */ __('%1$s at %2$s'), get_comment_date('', $comment), get_comment_time()));

                            edit_comment_link(__('Edit'), ' <span class="edit-link">', '</span>');
                        ?>
                    </div><!-- .comment-metadata -->

                    <?php if('0' == $comment->comment_approved) : ?>
                        <em class="comment-awaiting-moderation"><?php echo $moderation_note; ?></em>
                    <?php endif; ?>
                </footer><!-- .comment-meta -->

                <div class="comment-content">
                    <?php comment_text(); ?>
                </div><!-- .comment-content -->

                <?php
                    if('1' == $comment->comment_approved || $show_pending_links)
                    {
                        comment_reply_link(
                            array_merge($args, [
                                'add_below' => 'div-comment',
                                'depth' => $depth,
                                'max_depth' => $args['max_depth'],
                                'before' => '<div class="reply">',
                                'after' => '</div>',
                            ])
                        );
                    }
                ?>
            </article><!-- .comment-body -->
            <?php
        }

        protected function comment($comment, $depth, $args)
        {
            if('div' === $args['style'])
            {
                $tag = 'div';
                $add_below = 'comment';
            }
            else
            {
                $tag = 'li';
                $add_below = 'div-comment';
            }

            $commenter = wp_get_current_commenter();
            $show_pending_links = isset($commenter['comment_author']) && $commenter['comment_author'];

            if($commenter['comment_author_email'])
            {
                $moderation_note = __('Your comment is awaiting moderation.');
            }
            else
            {
                $moderation_note = __('Your comment is awaiting moderation. This is a preview; your comment will be visible after it has been approved.');
            }
            ?>
            <<?php echo $tag; ?><?php comment_class($this->has_children ? 'parent' : '', $comment); ?> id="comment-<?php comment_ID(); ?>">
            <?php if('div' !== $args['style']) : ?>
            <div id="div-comment-<?php comment_ID(); ?>" class="comment-body">
        <?php endif; ?>
            <div class="comment-author vcard">
                <?php
                    if(0 != $args['avatar_size'])
                    {
                        echo get_avatar($comment, $args['avatar_size']);
                    }
                ?>
                <?php
                    $comment_author = get_comment_author_link($comment);

                    if('0' == $comment->comment_approved && ! $show_pending_links)
                    {
                        $comment_author = get_comment_author($comment);
                    }

                    printf(/* translators: %s: Comment author link. */ __('%s <span class="says">says:</span>'), sprintf('<cite class="fn">%s</cite>', $comment_author));
                ?>
            </div>
            <?php if('0' == $comment->comment_approved) : ?>
            <em class="comment-awaiting-moderation"><?php echo $moderation_note; ?></em>
            <br/>
        <?php endif; ?>

            <div class="comment-meta commentmetadata">
                <?php
                    printf('<a href="%s">%s</a>', esc_url(get_comment_link($comment, $args)), sprintf(/* translators: 1: Comment date, 2: Comment time. */ __('%1$s at %2$s'), get_comment_date('', $comment), get_comment_time()));

                    edit_comment_link(__('(Edit)'), ' &nbsp;&nbsp;', '');
                ?>
            </div>

            <?php
            comment_text(
                $comment, array_merge($args, [
                            'add_below' => $add_below,
                            'depth' => $depth,
                            'max_depth' => $args['max_depth'],
                        ])
            );
            ?>

            <?php
            comment_reply_link(
                array_merge($args, [
                    'add_below' => $add_below,
                    'depth' => $depth,
                    'max_depth' => $args['max_depth'],
                    'before' => '<div class="reply">',
                    'after' => '</div>',
                ])
            );
            ?>

            <?php if('div' !== $args['style']) : ?>
            </div>
        <?php endif; ?>
            <?php
        }

        public function end_el(&$output, $data_object, $depth = 0, $args = [])
        {
            if(! empty($args['end-callback']))
            {
                ob_start();
                call_user_func(
                    $args['end-callback'], $data_object, // The current comment object.
                    $args, $depth
                );
                $output .= ob_get_clean();

                return;
            }
            if('div' === $args['style'])
            {
                $output .= "</div><!-- #comment-## -->\n";
            }
            else
            {
                $output .= "</li><!-- #comment-## -->\n";
            }
        }

        public function filter_comment_text($comment_text, $comment)
        {
            $commenter = wp_get_current_commenter();
            $show_pending_links = ! empty($commenter['comment_author']);

            if($comment && '0' == $comment->comment_approved && ! $show_pending_links)
            {
                $comment_text = wp_kses($comment_text, []);
            }

            return $comment_text;
        }
    }
