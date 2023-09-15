<?php
    /**
     * WordPress Post Thumbnail Template Functions.
     *
     * Support for post thumbnails.
     * Theme's functions.php must call add_theme_support( 'post-thumbnails' ) to use these.
     *
     * @package    WordPress
     * @subpackage Template
     */

    /**
     * Determines whether a post has an image attached.
     *
     * For more information on this and similar theme functions, check out
     * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
     * Conditional Tags} article in the Theme Developer Handbook.
     *
     * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global `$post`.
     *
     * @return bool Whether the post has an image attached.
     * @since 2.9.0
     * @since 4.4.0 `$post` can be a post ID or WP_Post object.
     *
     */
    function has_post_thumbnail($post = null)
    {
        $thumbnail_id = get_post_thumbnail_id($post);
        $has_thumbnail = (bool) $thumbnail_id;

        /**
         * Filters whether a post has a post thumbnail.
         *
         * @param bool             $has_thumbnail true if the post has a post thumbnail, otherwise false.
         * @param int|WP_Post|null $post          Post ID or WP_Post object. Default is global `$post`.
         * @param int|false        $thumbnail_id  Post thumbnail ID or false if the post does not exist.
         *
         * @since 5.1.0
         *
         */
        return (bool) apply_filters('has_post_thumbnail', $has_thumbnail, $post, $thumbnail_id);
    }

    /**
     * Retrieves the post thumbnail ID.
     *
     * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global `$post`.
     *
     * @return int|false Post thumbnail ID (which can be 0 if the thumbnail is not set),
     *                   or false if the post does not exist.
     * @since 5.5.0 The return value for a non-existing post
     *              was changed to false instead of an empty string.
     *
     * @since 2.9.0
     * @since 4.4.0 `$post` can be a post ID or WP_Post object.
     */
    function get_post_thumbnail_id($post = null)
    {
        $post = get_post($post);

        if(! $post)
        {
            return false;
        }

        $thumbnail_id = (int) get_post_meta($post->ID, '_thumbnail_id', true);

        /**
         * Filters the post thumbnail ID.
         *
         * @param int|false        $thumbnail_id Post thumbnail ID or false if the post does not exist.
         * @param int|WP_Post|null $post         Post ID or WP_Post object. Default is global `$post`.
         *
         * @since 5.9.0
         *
         */
        return (int) apply_filters('post_thumbnail_id', $thumbnail_id, $post);
    }

    /**
     * Displays the post thumbnail.
     *
     * When a theme adds 'post-thumbnail' support, a special 'post-thumbnail' image size
     * is registered, which differs from the 'thumbnail' image size managed via the
     * Settings > Media screen.
     *
     * When using the_post_thumbnail() or related functions, the 'post-thumbnail' image
     * size is used by default, though a different size can be specified instead as needed.
     *
     * @param string|int[] $size Optional. Image size. Accepts any registered image size name, or an array of
     *                           width and height values in pixels (in that order). Default 'post-thumbnail'.
     * @param string|array $attr Optional. Query string or array of attributes. Default empty.
     *
     * @since 2.9.0
     *
     * @see   get_the_post_thumbnail()
     *
     */
    function the_post_thumbnail($size = 'post-thumbnail', $attr = '')
    {
        echo get_the_post_thumbnail(null, $size, $attr);
    }

    /**
     * Updates cache for thumbnails in the current loop.
     *
     * @param WP_Query  $wp_query Optional. A WP_Query instance. Defaults to the $wp_query global.
     *
     * @global WP_Query $wp_query WordPress Query object.
     *
     * @since 3.2.0
     *
     */
    function update_post_thumbnail_cache($wp_query = null)
    {
        if(! $wp_query)
        {
            $wp_query = $GLOBALS['wp_query'];
        }

        if($wp_query->thumbnails_cached)
        {
            return;
        }

        $thumb_ids = [];

        foreach($wp_query->posts as $post)
        {
            $id = get_post_thumbnail_id($post->ID);
            if($id)
            {
                $thumb_ids[] = $id;
            }
        }

        if(! empty($thumb_ids))
        {
            _prime_post_caches($thumb_ids, false, true);
        }

        $wp_query->thumbnails_cached = true;
    }

    /**
     * Retrieves the post thumbnail.
     *
     * When a theme adds 'post-thumbnail' support, a special 'post-thumbnail' image size
     * is registered, which differs from the 'thumbnail' image size managed via the
     * Settings > Media screen.
     *
     * When using the_post_thumbnail() or related functions, the 'post-thumbnail' image
     * size is used by default, though a different size can be specified instead as needed.
     *
     * @param int|WP_Post  $post Optional. Post ID or WP_Post object.  Default is global `$post`.
     * @param string|int[] $size Optional. Image size. Accepts any registered image size name, or an array of
     *                           width and height values in pixels (in that order). Default 'post-thumbnail'.
     * @param string|array $attr Optional. Query string or array of attributes. Default empty.
     *
     * @return string The post thumbnail image tag.
     * @since 2.9.0
     * @since 4.4.0 `$post` can be a post ID or WP_Post object.
     *
     */
    function get_the_post_thumbnail($post = null, $size = 'post-thumbnail', $attr = '')
    {
        $post = get_post($post);

        if(! $post)
        {
            return '';
        }

        $post_thumbnail_id = get_post_thumbnail_id($post);

        /**
         * Filters the post thumbnail size.
         *
         * @param string|int[] $size    Requested image size. Can be any registered image size name, or
         *                              an array of width and height values in pixels (in that order).
         * @param int          $post_id The post ID.
         *
         * @since 2.9.0
         * @since 4.9.0 Added the `$post_id` parameter.
         *
         */
        $size = apply_filters('post_thumbnail_size', $size, $post->ID);

        if($post_thumbnail_id)
        {
            /**
             * Fires before fetching the post thumbnail HTML.
             *
             * Provides "just in time" filtering of all filters in wp_get_attachment_image().
             *
             * @param int          $post_id           The post ID.
             * @param int          $post_thumbnail_id The post thumbnail ID.
             * @param string|int[] $size              Requested image size. Can be any registered image size name, or
             *                                        an array of width and height values in pixels (in that order).
             *
             * @since 2.9.0
             *
             */
            do_action('begin_fetch_post_thumbnail_html', $post->ID, $post_thumbnail_id, $size);

            if(in_the_loop())
            {
                update_post_thumbnail_cache();
            }

            $html = wp_get_attachment_image($post_thumbnail_id, $size, false, $attr);

            /**
             * Fires after fetching the post thumbnail HTML.
             *
             * @param int          $post_id           The post ID.
             * @param int          $post_thumbnail_id The post thumbnail ID.
             * @param string|int[] $size              Requested image size. Can be any registered image size name, or
             *                                        an array of width and height values in pixels (in that order).
             *
             * @since 2.9.0
             *
             */
            do_action('end_fetch_post_thumbnail_html', $post->ID, $post_thumbnail_id, $size);
        }
        else
        {
            $html = '';
        }

        /**
         * Filters the post thumbnail HTML.
         *
         * @param string       $html              The post thumbnail HTML.
         * @param int          $post_id           The post ID.
         * @param int          $post_thumbnail_id The post thumbnail ID, or 0 if there isn't one.
         * @param string|int[] $size              Requested image size. Can be any registered image size name, or
         *                                        an array of width and height values in pixels (in that order).
         * @param string|array $attr              Query string or array of attributes.
         *
         * @since 2.9.0
         *
         */
        return apply_filters('post_thumbnail_html', $html, $post->ID, $post_thumbnail_id, $size, $attr);
    }

    /**
     * Returns the post thumbnail URL.
     *
     * @param int|WP_Post  $post Optional. Post ID or WP_Post object.  Default is global `$post`.
     * @param string|int[] $size Optional. Registered image size to retrieve the source for or a flat array
     *                           of height and width dimensions. Default 'post-thumbnail'.
     *
     * @return string|false Post thumbnail URL or false if no image is available. If `$size` does not match
     *                      any registered image size, the original image URL will be returned.
     * @since 4.4.0
     *
     */
    function get_the_post_thumbnail_url($post = null, $size = 'post-thumbnail')
    {
        $post_thumbnail_id = get_post_thumbnail_id($post);

        if(! $post_thumbnail_id)
        {
            return false;
        }

        $thumbnail_url = wp_get_attachment_image_url($post_thumbnail_id, $size);

        /**
         * Filters the post thumbnail URL.
         *
         * @param string|false     $thumbnail_url Post thumbnail URL or false if the post does not exist.
         * @param int|WP_Post|null $post          Post ID or WP_Post object. Default is global `$post`.
         * @param string|int[]     $size          Registered image size to retrieve the source for or a flat array
         *                                        of height and width dimensions. Default 'post-thumbnail'.
         *
         * @since 5.9.0
         *
         */
        return apply_filters('post_thumbnail_url', $thumbnail_url, $post, $size);
    }

    /**
     * Displays the post thumbnail URL.
     *
     * @param string|int[] $size Optional. Image size to use. Accepts any valid image size,
     *                           or an array of width and height values in pixels (in that order).
     *                           Default 'post-thumbnail'.
     *
     * @since 4.4.0
     *
     */
    function the_post_thumbnail_url($size = 'post-thumbnail')
    {
        $url = get_the_post_thumbnail_url(null, $size);

        if($url)
        {
            echo esc_url($url);
        }
    }

    /**
     * Returns the post thumbnail caption.
     *
     * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global `$post`.
     *
     * @return string Post thumbnail caption.
     * @since 4.6.0
     *
     */
    function get_the_post_thumbnail_caption($post = null)
    {
        $post_thumbnail_id = get_post_thumbnail_id($post);

        if(! $post_thumbnail_id)
        {
            return '';
        }

        $caption = wp_get_attachment_caption($post_thumbnail_id);

        if(! $caption)
        {
            $caption = '';
        }

        return $caption;
    }

    /**
     * Displays the post thumbnail caption.
     *
     * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global `$post`.
     *
     * @since 4.6.0
     *
     */
    function the_post_thumbnail_caption($post = null)
    {
        /**
         * Filters the displayed post thumbnail caption.
         *
         * @param string $caption Caption for the given attachment.
         *
         * @since 4.6.0
         *
         */
        echo apply_filters('the_post_thumbnail_caption', get_the_post_thumbnail_caption($post));
    }
