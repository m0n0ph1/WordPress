<?php

    define('EP_NONE', 0);

    define('EP_PERMALINK', 1);

    define('EP_ATTACHMENT', 2);

    define('EP_DATE', 4);

    define('EP_YEAR', 8);

    define('EP_MONTH', 16);

    define('EP_DAY', 32);

    define('EP_ROOT', 64);

    define('EP_COMMENTS', 128);

    define('EP_SEARCH', 256);

    define('EP_CATEGORIES', 512);

    define('EP_TAGS', 1024);

    define('EP_AUTHORS', 2048);

    define('EP_PAGES', 4096);

    define('EP_ALL_ARCHIVES', EP_DATE | EP_YEAR | EP_MONTH | EP_DAY | EP_CATEGORIES | EP_TAGS | EP_AUTHORS);

    define('EP_ALL', EP_PERMALINK | EP_ATTACHMENT | EP_ROOT | EP_COMMENTS | EP_SEARCH | EP_PAGES | EP_ALL_ARCHIVES);

    function add_rewrite_rule($regex, $query, $after = 'bottom')
    {
        global $wp_rewrite;

        $wp_rewrite->add_rule($regex, $query, $after);
    }

    function add_rewrite_tag($tag, $regex, $query = '')
    {
        // Validate the tag's name.
        if(strlen($tag) < 3 || '%' !== $tag[0] || '%' !== $tag[strlen($tag) - 1])
        {
            return;
        }

        global $wp_rewrite, $wp;

        if(empty($query))
        {
            $qv = trim($tag, '%');
            $wp->add_query_var($qv);
            $query = $qv.'=';
        }

        $wp_rewrite->add_rewrite_tag($tag, $regex, $query);
    }

    function remove_rewrite_tag($tag)
    {
        global $wp_rewrite;
        $wp_rewrite->remove_rewrite_tag($tag);
    }

    function add_permastruct($name, $struct, $args = [])
    {
        global $wp_rewrite;

        // Back-compat for the old parameters: $with_front and $ep_mask.
        if(! is_array($args))
        {
            $args = ['with_front' => $args];
        }

        if(func_num_args() === 4)
        {
            $args['ep_mask'] = func_get_arg(3);
        }

        $wp_rewrite->add_permastruct($name, $struct, $args);
    }

    function remove_permastruct($name)
    {
        global $wp_rewrite;

        $wp_rewrite->remove_permastruct($name);
    }

    function add_feed($feedname, $callback)
    {
        global $wp_rewrite;

        if(! in_array($feedname, $wp_rewrite->feeds, true))
        {
            $wp_rewrite->feeds[] = $feedname;
        }

        $hook = 'do_feed_'.$feedname;

        // Remove default function hook.
        remove_action($hook, $hook);

        add_action($hook, $callback, 10, 2);

        return $hook;
    }

    function flush_rewrite_rules($hard = true)
    {
        global $wp_rewrite;

        if(is_callable([$wp_rewrite, 'flush_rules']))
        {
            $wp_rewrite->flush_rules($hard);
        }
    }

    function add_rewrite_endpoint($name, $places, $query_var = true)
    {
        global $wp_rewrite;
        $wp_rewrite->add_endpoint($name, $places, $query_var);
    }

    function _wp_filter_taxonomy_base($base)
    {
        if(! empty($base))
        {
            $base = preg_replace('|^/index\.php/|', '', $base);
            $base = trim($base, '/');
        }

        return $base;
    }

    function wp_resolve_numeric_slug_conflicts($query_vars = [])
    {
        if(! isset($query_vars['year']) && ! isset($query_vars['monthnum']) && ! isset($query_vars['day']))
        {
            return $query_vars;
        }

        // Identify the 'postname' position in the permastruct array.
        $permastructs = array_values(array_filter(explode('/', get_option('permalink_structure'))));
        $postname_index = array_search('%postname%', $permastructs, true);

        if(false === $postname_index)
        {
            return $query_vars;
        }

        /*
         * A numeric slug could be confused with a year, month, or day, depending on position. To account for
         * the possibility of post pagination (eg 2015/2 for the second page of a post called '2015'), our
         * `is_*` checks are generous: check for year-slug clashes when `is_year` *or* `is_month`, and check
         * for month-slug clashes when `is_month` *or* `is_day`.
         */
        $compare = '';
        if(0 === $postname_index && (isset($query_vars['year']) || isset($query_vars['monthnum'])))
        {
            $compare = 'year';
        }
        elseif($postname_index && '%year%' === $permastructs[$postname_index - 1] && (isset($query_vars['monthnum']) || isset($query_vars['day'])))
        {
            $compare = 'monthnum';
        }
        elseif($postname_index && '%monthnum%' === $permastructs[$postname_index - 1] && isset($query_vars['day']))
        {
            $compare = 'day';
        }

        if(! $compare)
        {
            return $query_vars;
        }

        // This is the potentially clashing slug.
        $value = '';
        if($compare && array_key_exists($compare, $query_vars))
        {
            $value = $query_vars[$compare];
        }

        $post = get_page_by_path($value, OBJECT, 'post');
        if(! ($post instanceof WP_Post))
        {
            return $query_vars;
        }

        // If the date of the post doesn't match the date specified in the URL, resolve to the date archive.
        if(preg_match('/^([0-9]{4})\-([0-9]{2})/', $post->post_date, $matches) && isset($query_vars['year']) && ('monthnum' === $compare || 'day' === $compare))
        {
            // $matches[1] is the year the post was published.
            if((int) $query_vars['year'] !== (int) $matches[1])
            {
                return $query_vars;
            }

            // $matches[2] is the month the post was published.
            if('day' === $compare && isset($query_vars['monthnum']) && (int) $query_vars['monthnum'] !== (int) $matches[2])
            {
                return $query_vars;
            }
        }

        /*
         * If the located post contains nextpage pagination, then the URL chunk following postname may be
         * intended as the page number. Verify that it's a valid page before resolving to it.
         */
        $maybe_page = '';
        if('year' === $compare && isset($query_vars['monthnum']))
        {
            $maybe_page = $query_vars['monthnum'];
        }
        elseif('monthnum' === $compare && isset($query_vars['day']))
        {
            $maybe_page = $query_vars['day'];
        }
        // Bug found in #11694 - 'page' was returning '/4'.
        $maybe_page = (int) trim($maybe_page, '/');

        $post_page_count = substr_count($post->post_content, '<!--nextpage-->') + 1;

        // If the post doesn't have multiple pages, but a 'page' candidate is found, resolve to the date archive.
        if(1 === $post_page_count && $maybe_page)
        {
            return $query_vars;
        }

        // If the post has multiple pages and the 'page' number isn't valid, resolve to the date archive.
        if($post_page_count > 1 && $maybe_page > $post_page_count)
        {
            return $query_vars;
        }

        // If we've gotten to this point, we have a slug/date clash. First, adjust for nextpage.
        if('' !== $maybe_page)
        {
            $query_vars['page'] = (int) $maybe_page;
        }

        // Next, unset autodetected date-related query vars.
        unset($query_vars['year']);
        unset($query_vars['monthnum']);
        unset($query_vars['day']);

        // Then, set the identified post.
        $query_vars['name'] = $post->post_name;

        // Finally, return the modified query vars.
        return $query_vars;
    }

    function url_to_postid($url)
    {
        global $wp_rewrite;

        $url = apply_filters('url_to_postid', $url);

        $url_host = parse_url($url, PHP_URL_HOST);

        if(is_string($url_host))
        {
            $url_host = str_replace('www.', '', $url_host);
        }
        else
        {
            $url_host = '';
        }

        $home_url_host = parse_url(home_url(), PHP_URL_HOST);

        if(is_string($home_url_host))
        {
            $home_url_host = str_replace('www.', '', $home_url_host);
        }
        else
        {
            $home_url_host = '';
        }

        // Bail early if the URL does not belong to this site.
        if($url_host && $url_host !== $home_url_host)
        {
            return 0;
        }

        // First, check to see if there is a 'p=N' or 'page_id=N' to match against.
        if(preg_match('#[?&](p|page_id|attachment_id)=(\d+)#', $url, $values))
        {
            $id = absint($values[2]);
            if($id)
            {
                return $id;
            }
        }

        // Get rid of the #anchor.
        $url_split = explode('#', $url);
        $url = $url_split[0];

        // Get rid of URL ?query=string.
        $url_split = explode('?', $url);
        $url = $url_split[0];

        // Set the correct URL scheme.
        $scheme = parse_url(home_url(), PHP_URL_SCHEME);
        $url = set_url_scheme($url, $scheme);

        // Add 'www.' if it is absent and should be there.
        if(str_contains(home_url(), '://www.') && ! str_contains($url, '://www.'))
        {
            $url = str_replace('://', '://www.', $url);
        }

        // Strip 'www.' if it is present and shouldn't be.
        if(! str_contains(home_url(), '://www.'))
        {
            $url = str_replace('://www.', '://', $url);
        }

        if(trim($url, '/') === home_url() && 'page' === get_option('show_on_front'))
        {
            $page_on_front = get_option('page_on_front');

            if($page_on_front && get_post($page_on_front) instanceof WP_Post)
            {
                return (int) $page_on_front;
            }
        }

        // Check to see if we are using rewrite rules.
        $rewrite = $wp_rewrite->wp_rewrite_rules();

        // Not using rewrite rules, and 'p=N' and 'page_id=N' methods failed, so we're out of options.
        if(empty($rewrite))
        {
            return 0;
        }

        // Strip 'index.php/' if we're not using path info permalinks.
        if(! $wp_rewrite->using_index_permalinks())
        {
            $url = str_replace($wp_rewrite->index.'/', '', $url);
        }

        if(str_contains(trailingslashit($url), home_url('/')))
        {
            // Chop off http://domain.com/[path].
            $url = str_replace(home_url(), '', $url);
        }
        else
        {
            // Chop off /path/to/blog.
            $home_path = parse_url(home_url('/'));
            $home_path = isset($home_path['path']) ? $home_path['path'] : '';
            $url = preg_replace(sprintf('#^%s#', preg_quote($home_path)), '', trailingslashit($url));
        }

        // Trim leading and lagging slashes.
        $url = trim($url, '/');

        $request = $url;
        $post_type_query_vars = [];

        foreach(get_post_types([], 'objects') as $post_type => $t)
        {
            if(! empty($t->query_var))
            {
                $post_type_query_vars[$t->query_var] = $post_type;
            }
        }

        // Look for matches.
        $request_match = $request;
        foreach((array) $rewrite as $match => $query)
        {
            /*
             * If the requesting file is the anchor of the match,
             * prepend it to the path info.
             */
            if(! empty($url) && ($url !== $request) && str_starts_with($match, $url))
            {
                $request_match = $url.'/'.$request;
            }

            if(preg_match("#^$match#", $request_match, $matches))
            {
                if($wp_rewrite->use_verbose_page_rules && preg_match('/pagename=\$matches\[([0-9]+)\]/', $query, $varmatch))
                {
                    // This is a verbose page match, let's check to be sure about it.
                    $page = get_page_by_path($matches[$varmatch[1]]);
                    if(! $page)
                    {
                        continue;
                    }

                    $post_status_obj = get_post_status_object($page->post_status);
                    if(! $post_status_obj->public && ! $post_status_obj->protected && ! $post_status_obj->private && $post_status_obj->exclude_from_search)
                    {
                        continue;
                    }
                }

                /*
                 * Got a match.
                 * Trim the query of everything up to the '?'.
                 */
                $query = preg_replace('!^.+\?!', '', $query);

                // Substitute the substring matches into the query.
                $query = addslashes(WP_MatchesMapRegex::apply($query, $matches));

                // Filter out non-public query vars.
                global $wp;
                parse_str($query, $query_vars);
                $query = [];
                foreach((array) $query_vars as $key => $value)
                {
                    if(in_array((string) $key, $wp->public_query_vars, true))
                    {
                        $query[$key] = $value;
                        if(isset($post_type_query_vars[$key]))
                        {
                            $query['post_type'] = $post_type_query_vars[$key];
                            $query['name'] = $value;
                        }
                    }
                }

                // Resolve conflicts between posts with numeric slugs and date archive queries.
                $query = wp_resolve_numeric_slug_conflicts($query);

                // Do the query.
                $query = new WP_Query($query);
                if(! empty($query->posts) && $query->is_singular)
                {
                    return $query->post->ID;
                }
                else
                {
                    return 0;
                }
            }
        }

        return 0;
    }
