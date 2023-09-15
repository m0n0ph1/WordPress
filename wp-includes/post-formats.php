<?php

    function get_post_format($post = null)
    {
        $post = get_post($post);

        if(! $post)
        {
            return false;
        }

        if(! post_type_supports($post->post_type, 'post-formats'))
        {
            return false;
        }

        $_format = get_the_terms($post->ID, 'post_format');

        if(empty($_format))
        {
            return false;
        }

        $format = reset($_format);

        return str_replace('post-format-', '', $format->slug);
    }

    function has_post_format($format = [], $post = null)
    {
        $prefixed = [];

        if($format)
        {
            foreach((array) $format as $single)
            {
                $prefixed[] = 'post-format-'.sanitize_key($single);
            }
        }

        return has_term($prefixed, 'post_format', $post);
    }

    function set_post_format($post, $format)
    {
        $post = get_post($post);

        if(! $post)
        {
            return new WP_Error('invalid_post', __('Invalid post.'));
        }

        if(! empty($format))
        {
            $format = sanitize_key($format);
            if('standard' === $format || ! in_array($format, get_post_format_slugs(), true))
            {
                $format = '';
            }
            else
            {
                $format = 'post-format-'.$format;
            }
        }

        return wp_set_post_terms($post->ID, $format, 'post_format');
    }

    function get_post_format_strings()
    {
        $strings = [
            'standard' => _x('Standard', 'Post format'),
            // Special case. Any value that evals to false will be considered standard.
            'aside' => _x('Aside', 'Post format'),
            'chat' => _x('Chat', 'Post format'),
            'gallery' => _x('Gallery', 'Post format'),
            'link' => _x('Link', 'Post format'),
            'image' => _x('Image', 'Post format'),
            'quote' => _x('Quote', 'Post format'),
            'status' => _x('Status', 'Post format'),
            'video' => _x('Video', 'Post format'),
            'audio' => _x('Audio', 'Post format'),
        ];

        return $strings;
    }

    function get_post_format_slugs()
    {
        $slugs = array_keys(get_post_format_strings());

        return array_combine($slugs, $slugs);
    }

    function get_post_format_string($slug)
    {
        $strings = get_post_format_strings();
        if(! $slug)
        {
            return $strings['standard'];
        }
        else
        {
            return (isset($strings[$slug])) ? $strings[$slug] : '';
        }
    }

    function get_post_format_link($format)
    {
        $term = get_term_by('slug', 'post-format-'.$format, 'post_format');
        if(! $term || is_wp_error($term))
        {
            return false;
        }

        return get_term_link($term);
    }

    function _post_format_request($qvs)
    {
        if(! isset($qvs['post_format']))
        {
            return $qvs;
        }
        $slugs = get_post_format_slugs();
        if(isset($slugs[$qvs['post_format']]))
        {
            $qvs['post_format'] = 'post-format-'.$slugs[$qvs['post_format']];
        }
        $tax = get_taxonomy('post_format');
        if(! is_admin())
        {
            $qvs['post_type'] = $tax->object_type;
        }

        return $qvs;
    }

    function _post_format_link($link, $term, $taxonomy)
    {
        global $wp_rewrite;
        if('post_format' !== $taxonomy)
        {
            return $link;
        }
        if($wp_rewrite->get_extra_permastruct($taxonomy))
        {
            return str_replace("/{$term->slug}", '/'.str_replace('post-format-', '', $term->slug), $link);
        }
        else
        {
            $link = remove_query_arg('post_format', $link);

            return add_query_arg('post_format', str_replace('post-format-', '', $term->slug), $link);
        }
    }

    function _post_format_get_term($term)
    {
        if(isset($term->slug))
        {
            $term->name = get_post_format_string(str_replace('post-format-', '', $term->slug));
        }

        return $term;
    }

    function _post_format_get_terms($terms, $taxonomies, $args)
    {
        if(in_array('post_format', (array) $taxonomies, true))
        {
            if(isset($args['fields']) && 'names' === $args['fields'])
            {
                foreach($terms as $order => $name)
                {
                    $terms[$order] = get_post_format_string(str_replace('post-format-', '', $name));
                }
            }
            else
            {
                foreach((array) $terms as $order => $term)
                {
                    if(isset($term->taxonomy) && 'post_format' === $term->taxonomy)
                    {
                        $terms[$order]->name = get_post_format_string(str_replace('post-format-', '', $term->slug));
                    }
                }
            }
        }

        return $terms;
    }

    function _post_format_wp_get_object_terms($terms)
    {
        foreach((array) $terms as $order => $term)
        {
            if(isset($term->taxonomy) && 'post_format' === $term->taxonomy)
            {
                $terms[$order]->name = get_post_format_string(str_replace('post-format-', '', $term->slug));
            }
        }

        return $terms;
    }
