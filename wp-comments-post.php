<?php

    if('POST' !== $_SERVER['REQUEST_METHOD'])
    {
        $protocol = $_SERVER['SERVER_PROTOCOL'];
        if(! in_array($protocol, ['HTTP/1.1', 'HTTP/2', 'HTTP/2.0', 'HTTP/3'], true))
        {
            $protocol = 'HTTP/1.0';
        }

        header('Allow: POST');
        header("$protocol 405 Method Not Allowed");
        header('Content-Type: text/plain');
        exit;
    }

    require __DIR__.'/wp-load.php';

    nocache_headers();

    $comment = wp_handle_comment_submission(wp_unslash($_POST));
    if(is_wp_error($comment))
    {
        $data = (int) $comment->get_error_data();
        if(! empty($data))
        {
            wp_die('<p>'.$comment->get_error_message().'</p>', __('Comment Submission Failure'), [
                'response' => $data,
                'back_link' => true,
            ]);
        }
        else
        {
            exit;
        }
    }

    $user = wp_get_current_user();
    $cookies_consent = (isset($_POST['wp-comment-cookies-consent']));

    do_action('set_comment_cookies', $comment, $user, $cookies_consent);

    $location = empty($_POST['redirect_to']) ? get_comment_link($comment) : $_POST['redirect_to'].'#comment-'.$comment->comment_ID;

// If user didn't consent to cookies, add specific query arguments to display the awaiting moderation message.
    if(! $cookies_consent && 'unapproved' === wp_get_comment_status($comment) && ! empty($comment->comment_author_email))
    {
        $location = add_query_arg([
                                      'unapproved' => $comment->comment_ID,
                                      'moderation-hash' => wp_hash($comment->comment_date_gmt),
                                  ], $location);
    }

    $location = apply_filters('comment_post_redirect', $location, $comment);

    wp_safe_redirect($location);
    exit;
