<?php

    class TwentyNineteen_Walker_Comment extends Walker_Comment
    {
        protected function html5_comment($comment, $depth, $args)
        {
            parent::html5_comment($comment, $depth, $args);
            $tag = ('div' === $args['style']) ? 'div' : 'li';

            ?>
            <<?php echo $tag; ?> id="comment-<?php comment_ID(); ?>" <?php comment_class($this->has_children ? 'parent' : '', $comment); ?>>
            <article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
                <footer class="comment-meta">
                    <div class="comment-author vcard">
                        <?php
                            $comment_author_url = get_comment_author_url($comment);
                            $comment_author = get_comment_author($comment);
                            $avatar = get_avatar($comment, $args['avatar_size']);
                            if(0 !== (int) $args['avatar_size'])
                            {
                                if(empty($comment_author_url))
                                {
                                    echo $avatar;
                                }
                                else
                                {
                                    printf('<a href="%s" rel="external nofollow" class="url">', $comment_author_url);
                                    echo $avatar;
                                }
                            }

                            /*
                             * Using the `check` icon instead of `check_circle`, since we can't add a
                             * fill color to the inner check shape when in circle form.
                             */
                            if(twentynineteen_is_comment_by_post_author($comment))
                            {
                                printf('<span class="post-author-badge" aria-hidden="true">%s</span>', twentynineteen_get_icon_svg('check', 24));
                            }

                            printf(
                                wp_kses(/* translators: %s: Comment author link. */ __('%s <span class="screen-reader-text says">says:</span>', 'twentynineteen'), [
                                    'span' => [
                                        'class' => [],
                                    ],
                                ]), '<b class="fn">'.$comment_author.'</b>'
                            );

                            if(! empty($comment_author_url))
                            {
                                echo '</a>';
                            }
                        ?>
                    </div><!-- .comment-author -->

                    <div class="comment-metadata">
                        <?php
                            /* translators: 1: Comment date, 2: Comment time. */
                            $comment_timestamp = sprintf(__('%1$s at %2$s', 'twentynineteen'), get_comment_date('', $comment), get_comment_time());

                            printf('<a href="%s"><time datetime="%s">%s</time></a>', esc_url(get_comment_link($comment, $args)), get_comment_time('c'), $comment_timestamp);

                            $edit_comment_icon = twentynineteen_get_icon_svg('edit', 16);
                            edit_comment_link(__('Edit', 'twentynineteen'), ' <span class="edit-link-sep">&mdash;</span> <span class="edit-link">'.$edit_comment_icon, '</span>');
                        ?>
                    </div><!-- .comment-metadata -->

                    <?php
                        $commenter = wp_get_current_commenter();
                        if($commenter['comment_author_email'])
                        {
                            $moderation_note = __('Your comment is awaiting moderation.', 'twentynineteen');
                        }
                        else
                        {
                            $moderation_note = __('Your comment is awaiting moderation. This is a preview; your comment will be visible after it has been approved.', 'twentynineteen');
                        }
                    ?>

                    <?php if('0' === $comment->comment_approved) : ?>
                        <p class="comment-awaiting-moderation"><?php echo $moderation_note; ?></p>
                    <?php endif; ?>

                </footer><!-- .comment-meta -->

                <div class="comment-content">
                    <?php comment_text(); ?>
                </div><!-- .comment-content -->

            </article><!-- .comment-body -->

            <?php
            comment_reply_link(
                array_merge($args, [
                    'add_below' => 'div-comment',
                    'depth' => $depth,
                    'max_depth' => $args['max_depth'],
                    'before' => '<div class="comment-reply">',
                    'after' => '</div>',
                ])
            );
            ?>
            <?php
        }
    }
