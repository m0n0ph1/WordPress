<?php

    function wp_robots()
    {
        $robots = apply_filters('wp_robots', []);

        $robots_strings = [];
        foreach($robots as $directive => $value)
        {
            if(is_string($value))
            {
                // If a string value, include it as value for the directive.
                $robots_strings[] = "{$directive}:{$value}";
            }
            elseif($value)
            {
                // Otherwise, include the directive if it is truthy.
                $robots_strings[] = $directive;
            }
        }

        if(empty($robots_strings))
        {
            return;
        }

        echo "<meta name='robots' content='".esc_attr(implode(', ', $robots_strings))."' />\n";
    }

    function wp_robots_noindex(array $robots)
    {
        if(! get_option('blog_public'))
        {
            return wp_robots_no_robots($robots);
        }

        return $robots;
    }

    function wp_robots_noindex_embeds(array $robots)
    {
        if(is_embed())
        {
            return wp_robots_no_robots($robots);
        }

        return $robots;
    }

    function wp_robots_noindex_search(array $robots)
    {
        if(is_search())
        {
            return wp_robots_no_robots($robots);
        }

        return $robots;
    }

    function wp_robots_no_robots(array $robots)
    {
        $robots['noindex'] = true;

        if(get_option('blog_public'))
        {
            $robots['follow'] = true;
        }
        else
        {
            $robots['nofollow'] = true;
        }

        return $robots;
    }

    function wp_robots_sensitive_page(array $robots)
    {
        $robots['noindex'] = true;
        $robots['noarchive'] = true;

        return $robots;
    }

    function wp_robots_max_image_preview_large(array $robots)
    {
        if(get_option('blog_public'))
        {
            $robots['max-image-preview'] = 'large';
        }

        return $robots;
    }
