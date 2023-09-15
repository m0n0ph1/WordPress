<?php

    #[AllowDynamicProperties]
    class WP_Rewrite
    {
        public $permalink_structure;

        public $use_trailing_slashes;

        public $author_base = 'author';

        public $author_structure;

        public $date_structure;

        public $page_structure;

        public $search_base = 'search';

        public $search_structure;

        public $comments_base = 'comments';

        public $pagination_base = 'page';

        public $comments_pagination_base = 'comment-page';

        public $feed_base = 'feed';

        public $comment_feed_structure;

        public $feed_structure;

        public $front;

        public $root = '';

        public $index = 'index.php';

        public $matches = '';

        public $rules;

        public $extra_rules = [];

        public $extra_rules_top = [];

        public $non_wp_rules = [];

        public $extra_permastructs = [];

        public $endpoints;

        public $use_verbose_rules = false;

        public $use_verbose_page_rules = true;

        public $rewritecode = [
            '%year%',
            '%monthnum%',
            '%day%',
            '%hour%',
            '%minute%',
            '%second%',
            '%postname%',
            '%post_id%',
            '%author%',
            '%pagename%',
            '%search%',
        ];

        public $rewritereplace = [
            '([0-9]{4})',
            '([0-9]{1,2})',
            '([0-9]{1,2})',
            '([0-9]{1,2})',
            '([0-9]{1,2})',
            '([0-9]{1,2})',
            '([^/]+)',
            '([0-9]+)',
            '([^/]+)',
            '([^/]+?)',
            '(.+)',
        ];

        public $queryreplace = [
            'year=',
            'monthnum=',
            'day=',
            'hour=',
            'minute=',
            'second=',
            'name=',
            'p=',
            'author_name=',
            'pagename=',
            's=',
        ];

        public $feeds = ['feed', 'rdf', 'rss', 'rss2', 'atom'];

        public function __construct()
        {
            $this->init();
        }

        public function init()
        {
            $this->extra_rules = [];
            $this->non_wp_rules = [];
            $this->endpoints = [];
            $this->permalink_structure = get_option('permalink_structure');
            $this->front = substr($this->permalink_structure, 0, strpos($this->permalink_structure, '%'));
            $this->root = '';

            if($this->using_index_permalinks())
            {
                $this->root = $this->index.'/';
            }

            unset($this->author_structure);
            unset($this->date_structure);
            unset($this->page_structure);
            unset($this->search_structure);
            unset($this->feed_structure);
            unset($this->comment_feed_structure);

            $this->use_trailing_slashes = str_ends_with($this->permalink_structure, '/');

            // Enable generic rules for pages if permalink structure doesn't begin with a wildcard.
            if(preg_match('/^[^%]*%(?:postname|category|tag|author)%/', $this->permalink_structure))
            {
                $this->use_verbose_page_rules = true;
            }
            else
            {
                $this->use_verbose_page_rules = false;
            }
        }

        public function using_index_permalinks()
        {
            if(empty($this->permalink_structure))
            {
                return false;
            }

            // If the index is not in the permalink, we're using mod_rewrite.
            return preg_match('#^/*'.$this->index.'#', $this->permalink_structure);
        }

        public function using_mod_rewrite_permalinks()
        {
            return $this->using_permalinks() && ! $this->using_index_permalinks();
        }

        public function using_permalinks()
        {
            return ! empty($this->permalink_structure);
        }

        public function page_uri_index()
        {
            global $wpdb;

            // Get pages in order of hierarchy, i.e. children after parents.
            $pages = $wpdb->get_results("SELECT ID, post_name, post_parent FROM $wpdb->posts WHERE post_type = 'page' AND post_status != 'auto-draft'");
            $posts = get_page_hierarchy($pages);

            // If we have no pages get out quick.
            if(! $posts)
            {
                return [[], []];
            }

            // Now reverse it, because we need parents after children for rewrite rules to work properly.
            $posts = array_reverse($posts, true);

            $page_uris = [];
            $page_attachment_uris = [];

            foreach($posts as $id => $post)
            {
                // URL => page name.
                $uri = get_page_uri($id);
                $attachments = $wpdb->get_results($wpdb->prepare("SELECT ID, post_name, post_parent FROM $wpdb->posts WHERE post_type = 'attachment' AND post_parent = %d", $id));
                if(! empty($attachments))
                {
                    foreach($attachments as $attachment)
                    {
                        $attach_uri = get_page_uri($attachment->ID);
                        $page_attachment_uris[$attach_uri] = $attachment->ID;
                    }
                }

                $page_uris[$uri] = $id;
            }

            return [$page_uris, $page_attachment_uris];
        }

        public function get_year_permastruct()
        {
            $structure = $this->get_date_permastruct();

            if(empty($structure))
            {
                return false;
            }

            $structure = str_replace(array('%monthnum%', '%day%'), '', $structure);
            $structure = preg_replace('#/+#', '/', $structure);

            return $structure;
        }

        public function get_date_permastruct()
        {
            if(isset($this->date_structure))
            {
                return $this->date_structure;
            }

            if(empty($this->permalink_structure))
            {
                $this->date_structure = '';

                return false;
            }

            // The date permalink must have year, month, and day separated by slashes.
            $endians = ['%year%/%monthnum%/%day%', '%day%/%monthnum%/%year%', '%monthnum%/%day%/%year%'];

            $this->date_structure = '';
            $date_endian = '';

            foreach($endians as $endian)
            {
                if(str_contains($this->permalink_structure, $endian))
                {
                    $date_endian = $endian;
                    break;
                }
            }

            if(empty($date_endian))
            {
                $date_endian = '%year%/%monthnum%/%day%';
            }

            /*
             * Do not allow the date tags and %post_id% to overlap in the permalink
             * structure. If they do, move the date tags to $front/date/.
             */
            $front = $this->front;
            preg_match_all('/%.+?%/', $this->permalink_structure, $tokens);
            $tok_index = 1;
            foreach((array) $tokens[0] as $token)
            {
                if('%post_id%' === $token && ($tok_index <= 3))
                {
                    $front = $front.'date/';
                    break;
                }
                ++$tok_index;
            }

            $this->date_structure = $front.$date_endian;

            return $this->date_structure;
        }

        public function get_month_permastruct()
        {
            $structure = $this->get_date_permastruct();

            if(empty($structure))
            {
                return false;
            }

            $structure = str_replace('%day%', '', $structure);
            $structure = preg_replace('#/+#', '/', $structure);

            return $structure;
        }

        public function get_day_permastruct()
        {
            return $this->get_date_permastruct();
        }

        public function get_category_permastruct()
        {
            return $this->get_extra_permastruct('category');
        }

        public function get_extra_permastruct($name)
        {
            if(empty($this->permalink_structure))
            {
                return false;
            }

            if(isset($this->extra_permastructs[$name]))
            {
                return $this->extra_permastructs[$name]['struct'];
            }

            return false;
        }

        public function get_tag_permastruct()
        {
            return $this->get_extra_permastruct('post_tag');
        }

        public function get_feed_permastruct()
        {
            if(isset($this->feed_structure))
            {
                return $this->feed_structure;
            }

            if(empty($this->permalink_structure))
            {
                $this->feed_structure = '';

                return false;
            }

            $this->feed_structure = $this->root.$this->feed_base.'/%feed%';

            return $this->feed_structure;
        }

        public function get_comment_feed_permastruct()
        {
            if(isset($this->comment_feed_structure))
            {
                return $this->comment_feed_structure;
            }

            if(empty($this->permalink_structure))
            {
                $this->comment_feed_structure = '';

                return false;
            }

            $this->comment_feed_structure = $this->root.$this->comments_base.'/'.$this->feed_base.'/%feed%';

            return $this->comment_feed_structure;
        }

        public function remove_rewrite_tag($tag)
        {
            $position = array_search($tag, $this->rewritecode, true);
            if(false !== $position && null !== $position)
            {
                unset($this->rewritecode[$position]);
                unset($this->rewritereplace[$position]);
                unset($this->queryreplace[$position]);
            }
        }

        public function generate_rewrite_rule($permalink_structure, $walk_dirs = false)
        {
            return $this->generate_rewrite_rules($permalink_structure, EP_NONE, false, false, false, $walk_dirs);
        }

        public function generate_rewrite_rules(
            $permalink_structure, $ep_mask = EP_NONE, $paged = true, $feed = true, $forcomments = false, $walk_dirs = true, $endpoints = true
        ) {
            // Build a regex to match the feed section of URLs, something like (feed|atom|rss|rss2)/?
            $feedregex2 = '';
            foreach((array) $this->feeds as $feed_name)
            {
                $feedregex2 .= $feed_name.'|';
            }
            $feedregex2 = '('.trim($feedregex2, '|').')/?$';

            /*
             * $feedregex is identical but with /feed/ added on as well, so URLs like <permalink>/feed/atom
             * and <permalink>/atom are both possible
             */
            $feedregex = $this->feed_base.'/'.$feedregex2;

            // Build a regex to match the trackback and page/xx parts of URLs.
            $trackbackregex = 'trackback/?$';
            $pageregex = $this->pagination_base.'/?([0-9]{1,})/?$';
            $commentregex = $this->comments_pagination_base.'-([0-9]{1,})/?$';
            $embedregex = 'embed/?$';

            // Build up an array of endpoint regexes to append => queries to append.
            if($endpoints)
            {
                $ep_query_append = [];
                foreach((array) $this->endpoints as $endpoint)
                {
                    // Match everything after the endpoint name, but allow for nothing to appear there.
                    $epmatch = $endpoint[1].'(/(.*))?/?$';

                    // This will be appended on to the rest of the query for each dir.
                    $epquery = '&'.$endpoint[2].'=';
                    $ep_query_append[$epmatch] = [$endpoint[0], $epquery];
                }
            }

            // Get everything up to the first rewrite tag.
            $front = substr($permalink_structure, 0, strpos($permalink_structure, '%'));

            // Build an array of the tags (note that said array ends up being in $tokens[0]).
            preg_match_all('/%.+?%/', $permalink_structure, $tokens);

            $num_tokens = count($tokens[0]);

            $index = $this->index; // Probably 'index.php'.
            $feedindex = $index;
            $trackbackindex = $index;
            $embedindex = $index;

            /*
             * Build a list from the rewritecode and queryreplace arrays, that will look something
             * like tagname=$matches[i] where i is the current $i.
             */
            $queries = [];
            for($i = 0; $i < $num_tokens; ++$i)
            {
                if(0 < $i)
                {
                    $queries[$i] = $queries[$i - 1].'&';
                }
                else
                {
                    $queries[$i] = '';
                }

                $query_token = str_replace($this->rewritecode, $this->queryreplace, $tokens[0][$i]).$this->preg_index($i + 1);
                $queries[$i] .= $query_token;
            }

            // Get the structure, minus any cruft (stuff that isn't tags) at the front.
            $structure = $permalink_structure;
            if('/' !== $front)
            {
                $structure = str_replace($front, '', $structure);
            }

            /*
             * Create a list of dirs to walk over, making rewrite rules for each level
             * so for example, a $structure of /%year%/%monthnum%/%postname% would create
             * rewrite rules for /%year%/, /%year%/%monthnum%/ and /%year%/%monthnum%/%postname%
             */
            $structure = trim($structure, '/');
            $dirs = $walk_dirs ? explode('/', $structure) : [$structure];

            // Strip slashes from the front of $front.
            $front = ltrim($front, '/');

            // The main workhorse loop.
            $post_rewrite = [];
            $struct = $front;
            foreach($dirs as $jValue)
            {
                // Get the struct for this dir, and trim slashes off the front.
                $struct .= $jValue.'/'; // Accumulate. see comment near explode('/', $structure) above.
                $struct = ltrim($struct, '/');

                // Replace tags with regexes.
                $match = str_replace($this->rewritecode, $this->rewritereplace, $struct);

                // Make a list of tags, and store how many there are in $num_toks.
                $num_toks = preg_match_all('/%.+?%/', $struct, $toks);

                // Get the 'tagname=$matches[i]'.
                $query = (! empty($num_toks) && isset($queries[$num_toks - 1])) ? $queries[$num_toks - 1] : '';

                // Set up $ep_mask_specific which is used to match more specific URL types.
                switch($jValue)
                {
                    case '%year%':
                        $ep_mask_specific = EP_YEAR;
                        break;
                    case '%monthnum%':
                        $ep_mask_specific = EP_MONTH;
                        break;
                    case '%day%':
                        $ep_mask_specific = EP_DAY;
                        break;
                    default:
                        $ep_mask_specific = EP_NONE;
                }

                // Create query for /page/xx.
                $pagematch = $match.$pageregex;
                $pagequery = $index.'?'.$query.'&paged='.$this->preg_index($num_toks + 1);

                // Create query for /comment-page-xx.
                $commentmatch = $match.$commentregex;
                $commentquery = $index.'?'.$query.'&cpage='.$this->preg_index($num_toks + 1);

                if(get_option('page_on_front'))
                {
                    // Create query for Root /comment-page-xx.
                    $rootcommentmatch = $match.$commentregex;
                    $rootcommentquery = $index.'?'.$query.'&page_id='.get_option('page_on_front').'&cpage='.$this->preg_index($num_toks + 1);
                }

                // Create query for /feed/(feed|atom|rss|rss2|rdf).
                $feedmatch = $match.$feedregex;
                $feedquery = $feedindex.'?'.$query.'&feed='.$this->preg_index($num_toks + 1);

                // Create query for /(feed|atom|rss|rss2|rdf) (see comment near creation of $feedregex).
                $feedmatch2 = $match.$feedregex2;
                $feedquery2 = $feedindex.'?'.$query.'&feed='.$this->preg_index($num_toks + 1);

                // Create query and regex for embeds.
                $embedmatch = $match.$embedregex;
                $embedquery = $embedindex.'?'.$query.'&embed=true';

                // If asked to, turn the feed queries into comment feed ones.
                if($forcomments)
                {
                    $feedquery .= '&withcomments=1';
                    $feedquery2 .= '&withcomments=1';
                }

                // Start creating the array of rewrites for this dir.
                $rewrite = [];

                // ...adding on /feed/ regexes => queries.
                if($feed)
                {
                    $rewrite = [
                        $feedmatch => $feedquery,
                        $feedmatch2 => $feedquery2,
                        $embedmatch => $embedquery,
                    ];
                }

                // ...and /page/xx ones.
                if($paged)
                {
                    $rewrite = array_merge($rewrite, [$pagematch => $pagequery]);
                }

                // Only on pages with comments add ../comment-page-xx/.
                if(EP_PAGES & $ep_mask || EP_PERMALINK & $ep_mask)
                {
                    $rewrite = array_merge($rewrite, [$commentmatch => $commentquery]);
                }
                elseif(EP_ROOT & $ep_mask && get_option('page_on_front'))
                {
                    $rewrite = array_merge($rewrite, [$rootcommentmatch => $rootcommentquery]);
                }

                // Do endpoints.
                if($endpoints)
                {
                    foreach((array) $ep_query_append as $regex => $ep)
                    {
                        // Add the endpoints on if the mask fits.
                        if($ep[0] & $ep_mask || $ep[0] & $ep_mask_specific)
                        {
                            $rewrite[$match.$regex] = $index.'?'.$query.$ep[1].$this->preg_index($num_toks + 2);
                        }
                    }
                }

                // If we've got some tags in this dir.
                if($num_toks)
                {
                    $post = false;
                    $page = false;

                    /*
                     * Check to see if this dir is permalink-level: i.e. the structure specifies an
                     * individual post. Do this by checking it contains at least one of 1) post name,
                     * 2) post ID, 3) page name, 4) timestamp (year, month, day, hour, second and
                     * minute all present). Set these flags now as we need them for the endpoints.
                     */
                    if(str_contains($struct, '%postname%') || str_contains($struct, '%post_id%') || str_contains($struct, '%pagename%') || (str_contains($struct, '%year%') && str_contains($struct, '%monthnum%') && str_contains($struct, '%day%') && str_contains($struct, '%hour%') && str_contains($struct, '%minute%') && str_contains($struct, '%second%')))
                    {
                        $post = true;
                        if(str_contains($struct, '%pagename%'))
                        {
                            $page = true;
                        }
                    }

                    if(! $post)
                    {
                        // For custom post types, we need to add on endpoints as well.
                        foreach(get_post_types(['_builtin' => false]) as $ptype)
                        {
                            if(str_contains($struct, "%$ptype%"))
                            {
                                $post = true;

                                // This is for page style attachment URLs.
                                $page = is_post_type_hierarchical($ptype);
                                break;
                            }
                        }
                    }

                    // If creating rules for a permalink, do all the endpoints like attachments etc.
                    if($post)
                    {
                        // Create query and regex for trackback.
                        $trackbackmatch = $match.$trackbackregex;
                        $trackbackquery = $trackbackindex.'?'.$query.'&tb=1';

                        // Create query and regex for embeds.
                        $embedmatch = $match.$embedregex;
                        $embedquery = $embedindex.'?'.$query.'&embed=true';

                        // Trim slashes from the end of the regex for this dir.
                        $match = rtrim($match, '/');

                        // Get rid of brackets.
                        $submatchbase = str_replace(['(', ')'], '', $match);

                        // Add a rule for at attachments, which take the form of <permalink>/some-text.
                        $sub1 = $submatchbase.'/([^/]+)/';

                        // Add trackback regex <permalink>/trackback/...
                        $sub1tb = $sub1.$trackbackregex;

                        // And <permalink>/feed/(atom|...)
                        $sub1feed = $sub1.$feedregex;

                        // And <permalink>/(feed|atom...)
                        $sub1feed2 = $sub1.$feedregex2;

                        // And <permalink>/comment-page-xx
                        $sub1comment = $sub1.$commentregex;

                        // And <permalink>/embed/...
                        $sub1embed = $sub1.$embedregex;

                        /*
                         * Add another rule to match attachments in the explicit form:
                         * <permalink>/attachment/some-text
                         */
                        $sub2 = $submatchbase.'/attachment/([^/]+)/';

                        // And add trackbacks <permalink>/attachment/trackback.
                        $sub2tb = $sub2.$trackbackregex;

                        // Feeds, <permalink>/attachment/feed/(atom|...)
                        $sub2feed = $sub2.$feedregex;

                        // And feeds again on to this <permalink>/attachment/(feed|atom...)
                        $sub2feed2 = $sub2.$feedregex2;

                        // And <permalink>/comment-page-xx
                        $sub2comment = $sub2.$commentregex;

                        // And <permalink>/embed/...
                        $sub2embed = $sub2.$embedregex;

                        // Create queries for these extra tag-ons we've just dealt with.
                        $subquery = $index.'?attachment='.$this->preg_index(1);
                        $subtbquery = $subquery.'&tb=1';
                        $subfeedquery = $subquery.'&feed='.$this->preg_index(2);
                        $subcommentquery = $subquery.'&cpage='.$this->preg_index(2);
                        $subembedquery = $subquery.'&embed=true';

                        // Do endpoints for attachments.
                        if(! empty($endpoints))
                        {
                            foreach((array) $ep_query_append as $regex => $ep)
                            {
                                if($ep[0] & EP_ATTACHMENT)
                                {
                                    $rewrite[$sub1.$regex] = $subquery.$ep[1].$this->preg_index(3);
                                    $rewrite[$sub2.$regex] = $subquery.$ep[1].$this->preg_index(3);
                                }
                            }
                        }

                        /*
                         * Now we've finished with endpoints, finish off the $sub1 and $sub2 matches
                         * add a ? as we don't have to match that last slash, and finally a $ so we
                         * match to the end of the URL
                         */
                        $sub1 .= '?$';
                        $sub2 .= '?$';

                        /*
                         * Post pagination, e.g. <permalink>/2/
                         * Previously: '(/[0-9]+)?/?$', which produced '/2' for page.
                         * When cast to int, returned 0.
                         */
                        $match = $match.'(?:/([0-9]+))?/?$';
                        $query = $index.'?'.$query.'&page='.$this->preg_index($num_toks + 1);
                        // Not matching a permalink so this is a lot simpler.
                    }
                    else
                    {
                        // Close the match and finalize the query.
                        $match .= '?$';
                        $query = $index.'?'.$query;
                    }

                    /*
                     * Create the final array for this dir by joining the $rewrite array (which currently
                     * only contains rules/queries for trackback, pages etc) to the main regex/query for
                     * this dir
                     */
                    $rewrite = array_merge($rewrite, [$match => $query]);

                    // If we're matching a permalink, add those extras (attachments etc) on.
                    if($post)
                    {
                        // Add trackback.
                        $rewrite = array_merge([$trackbackmatch => $trackbackquery], $rewrite);

                        // Add embed.
                        $rewrite = array_merge([$embedmatch => $embedquery], $rewrite);

                        // Add regexes/queries for attachments, attachment trackbacks and so on.
                        if(! $page)
                        {
                            // Require <permalink>/attachment/stuff form for pages because of confusion with subpages.
                            $rewrite = array_merge($rewrite, [
                                $sub1 => $subquery,
                                $sub1tb => $subtbquery,
                                $sub1feed => $subfeedquery,
                                $sub1feed2 => $subfeedquery,
                                $sub1comment => $subcommentquery,
                                $sub1embed => $subembedquery,
                            ]);
                        }

                        $rewrite = array_merge([
                                                   $sub2 => $subquery,
                                                   $sub2tb => $subtbquery,
                                                   $sub2feed => $subfeedquery,
                                                   $sub2feed2 => $subfeedquery,
                                                   $sub2comment => $subcommentquery,
                                                   $sub2embed => $subembedquery,
                                               ], $rewrite);
                    }
                }
                // Add the rules for this dir to the accumulating $post_rewrite.
                $post_rewrite = array_merge($rewrite, $post_rewrite);
            }

            // The finished rules. phew!
            return $post_rewrite;
        }

        public function preg_index($number)
        {
            $match_prefix = '$';
            $match_suffix = '';

            if(! empty($this->matches))
            {
                $match_prefix = '$'.$this->matches.'[';
                $match_suffix = ']';
            }

            return "$match_prefix$number$match_suffix";
        }

        public function wp_rewrite_rules()
        {
            $this->rules = get_option('rewrite_rules');
            if(empty($this->rules))
            {
                $this->refresh_rewrite_rules();
            }

            return $this->rules;
        }

        private function refresh_rewrite_rules()
        {
            $this->rules = '';
            $this->matches = 'matches';

            $this->rewrite_rules();

            if(did_action('wp_loaded'))
            {
                update_option('rewrite_rules', $this->rules);
            }
            else
            {
                /*
                 * Is not safe to save the results right now, as the rules may be partial.
                 * Need to give all rules the chance to register.
                 */
                add_action('wp_loaded', [$this, 'flush_rules']);
            }
        }

        public function rewrite_rules()
        {
            $rewrite = [];

            if(empty($this->permalink_structure))
            {
                return $rewrite;
            }

            // robots.txt -- only if installed at the root.
            $home_path = parse_url(home_url());
            $robots_rewrite = (empty($home_path['path']) || '/' === $home_path['path']) ? ['robots\.txt$' => $this->index.'?robots=1'] : [];

            // favicon.ico -- only if installed at the root.
            $favicon_rewrite = (empty($home_path['path']) || '/' === $home_path['path']) ? ['favicon\.ico$' => $this->index.'?favicon=1'] : [];

            // Old feed and service files.
            $deprecated_files = [
                '.*wp-(atom|rdf|rss|rss2|feed|commentsrss2)\.php$' => $this->index.'?feed=old',
                '.*wp-app\.php(/.*)?$' => $this->index.'?error=403',
            ];

            // Registration rules.
            $registration_pages = [];
            if(is_multisite() && is_main_site())
            {
                $registration_pages['.*wp-signup.php$'] = $this->index.'?signup=true';
                $registration_pages['.*wp-activate.php$'] = $this->index.'?activate=true';
            }

            // Deprecated.
            $registration_pages['.*wp-register.php$'] = $this->index.'?register=true';

            // Post rewrite rules.
            $post_rewrite = $this->generate_rewrite_rules($this->permalink_structure, EP_PERMALINK);

            $post_rewrite = apply_filters('post_rewrite_rules', $post_rewrite);

            // Date rewrite rules.
            $date_rewrite = $this->generate_rewrite_rules($this->get_date_permastruct(), EP_DATE);

            $date_rewrite = apply_filters('date_rewrite_rules', $date_rewrite);

            // Root-level rewrite rules.
            $root_rewrite = $this->generate_rewrite_rules($this->root.'/', EP_ROOT);

            $root_rewrite = apply_filters('root_rewrite_rules', $root_rewrite);

            // Comments rewrite rules.
            $comments_rewrite = $this->generate_rewrite_rules($this->root.$this->comments_base, EP_COMMENTS, false, true, true, false);

            $comments_rewrite = apply_filters('comments_rewrite_rules', $comments_rewrite);

            // Search rewrite rules.
            $search_structure = $this->get_search_permastruct();
            $search_rewrite = $this->generate_rewrite_rules($search_structure, EP_SEARCH);

            $search_rewrite = apply_filters('search_rewrite_rules', $search_rewrite);

            // Author rewrite rules.
            $author_rewrite = $this->generate_rewrite_rules($this->get_author_permastruct(), EP_AUTHORS);

            $author_rewrite = apply_filters('author_rewrite_rules', $author_rewrite);

            // Pages rewrite rules.
            $page_rewrite = $this->page_rewrite_rules();

            $page_rewrite = apply_filters('page_rewrite_rules', $page_rewrite);

            // Extra permastructs.
            foreach($this->extra_permastructs as $permastructname => $struct)
            {
                if(is_array($struct))
                {
                    if(count($struct) === 2)
                    {
                        $rules = $this->generate_rewrite_rules($struct[0], $struct[1]);
                    }
                    else
                    {
                        $rules = $this->generate_rewrite_rules($struct['struct'], $struct['ep_mask'], $struct['paged'], $struct['feed'], $struct['forcomments'], $struct['walk_dirs'], $struct['endpoints']);
                    }
                }
                else
                {
                    $rules = $this->generate_rewrite_rules($struct);
                }

                $rules = apply_filters("{$permastructname}_rewrite_rules", $rules);

                if('post_tag' === $permastructname)
                {
                    $rules = apply_filters_deprecated('tag_rewrite_rules', [$rules], '3.1.0', 'post_tag_rewrite_rules');
                }

                $this->extra_rules_top = array_merge($this->extra_rules_top, $rules);
            }

            // Put them together.
            if($this->use_verbose_page_rules)
            {
                $this->rules = array_merge($this->extra_rules_top, $robots_rewrite, $favicon_rewrite, $deprecated_files, $registration_pages, $root_rewrite, $comments_rewrite, $search_rewrite, $author_rewrite, $date_rewrite, $page_rewrite, $post_rewrite, $this->extra_rules);
            }
            else
            {
                $this->rules = array_merge($this->extra_rules_top, $robots_rewrite, $favicon_rewrite, $deprecated_files, $registration_pages, $root_rewrite, $comments_rewrite, $search_rewrite, $author_rewrite, $date_rewrite, $post_rewrite, $page_rewrite, $this->extra_rules);
            }

            do_action_ref_array('generate_rewrite_rules', [&$this]);

            $this->rules = apply_filters('rewrite_rules_array', $this->rules);

            return $this->rules;
        }

        public function get_search_permastruct()
        {
            if(isset($this->search_structure))
            {
                return $this->search_structure;
            }

            if(empty($this->permalink_structure))
            {
                $this->search_structure = '';

                return false;
            }

            $this->search_structure = $this->root.$this->search_base.'/%search%';

            return $this->search_structure;
        }

        public function get_author_permastruct()
        {
            if(isset($this->author_structure))
            {
                return $this->author_structure;
            }

            if(empty($this->permalink_structure))
            {
                $this->author_structure = '';

                return false;
            }

            $this->author_structure = $this->front.$this->author_base.'/%author%';

            return $this->author_structure;
        }

        public function page_rewrite_rules()
        {
            // The extra .? at the beginning prevents clashes with other regular expressions in the rules array.
            $this->add_rewrite_tag('%pagename%', '(.?.+?)', 'pagename=');

            return $this->generate_rewrite_rules($this->get_page_permastruct(), EP_PAGES, true, true, false, false);
        }

        public function add_rewrite_tag($tag, $regex, $query)
        {
            $position = array_search($tag, $this->rewritecode, true);
            if(false !== $position && null !== $position)
            {
                $this->rewritereplace[$position] = $regex;
                $this->queryreplace[$position] = $query;
            }
            else
            {
                $this->rewritecode[] = $tag;
                $this->rewritereplace[] = $regex;
                $this->queryreplace[] = $query;
            }
        }

        public function get_page_permastruct()
        {
            if(isset($this->page_structure))
            {
                return $this->page_structure;
            }

            if(empty($this->permalink_structure))
            {
                $this->page_structure = '';

                return false;
            }

            $this->page_structure = $this->root.'%pagename%';

            return $this->page_structure;
        }

        public function mod_rewrite_rules()
        {
            if(! $this->using_permalinks())
            {
                return '';
            }

            $site_root = parse_url(site_url());
            if(isset($site_root['path']))
            {
                $site_root = trailingslashit($site_root['path']);
            }

            $home_root = parse_url(home_url());
            if(isset($home_root['path']))
            {
                $home_root = trailingslashit($home_root['path']);
            }
            else
            {
                $home_root = '/';
            }

            $rules = "<IfModule mod_rewrite.c>\n";
            $rules .= "RewriteEngine On\n";
            $rules .= "RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]\n";
            $rules .= "RewriteBase $home_root\n";

            // Prevent -f checks on index.php.
            $rules .= "RewriteRule ^index\.php$ - [L]\n";

            // Add in the rules that don't redirect to WP's index.php (and thus shouldn't be handled by WP at all).
            foreach((array) $this->non_wp_rules as $match => $query)
            {
                // Apache 1.3 does not support the reluctant (non-greedy) modifier.
                $match = str_replace('.+?', '.+', $match);

                $rules .= 'RewriteRule ^'.$match.' '.$home_root.$query." [QSA,L]\n";
            }

            if($this->use_verbose_rules)
            {
                $this->matches = '';
                $rewrite = $this->rewrite_rules();
                $num_rules = count($rewrite);
                $rules .= "RewriteCond %{REQUEST_FILENAME} -f [OR]\n"."RewriteCond %{REQUEST_FILENAME} -d\n"."RewriteRule ^.*$ - [S=$num_rules]\n";

                foreach((array) $rewrite as $match => $query)
                {
                    // Apache 1.3 does not support the reluctant (non-greedy) modifier.
                    $match = str_replace('.+?', '.+', $match);

                    if(str_contains($query, $this->index))
                    {
                        $rules .= 'RewriteRule ^'.$match.' '.$home_root.$query." [QSA,L]\n";
                    }
                    else
                    {
                        $rules .= 'RewriteRule ^'.$match.' '.$site_root.$query." [QSA,L]\n";
                    }
                }
            }
            else
            {
                $rules .= "RewriteCond %{REQUEST_FILENAME} !-f\n"."RewriteCond %{REQUEST_FILENAME} !-d\n"."RewriteRule . {$home_root}{$this->index} [L]\n";
            }

            $rules .= "</IfModule>\n";

            $rules = apply_filters('mod_rewrite_rules', $rules);

            return apply_filters_deprecated('rewrite_rules', [$rules], '1.5.0', 'mod_rewrite_rules');
        }

        public function iis7_url_rewrite_rules($add_parent_tags = false)
        {
            if(! $this->using_permalinks())
            {
                return '';
            }
            $rules = '';
            if($add_parent_tags)
            {
                $rules .= '<configuration>
	<system.webServer>
		<rewrite>
			<rules>';
            }

            $rules .= '
			<rule name="WordPress: '.esc_attr(home_url()).'" patternSyntax="Wildcard">
				<match url="*" />
					<conditions>
						<add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
						<add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
					</conditions>
				<action type="Rewrite" url="index.php" />
			</rule>';

            if($add_parent_tags)
            {
                $rules .= '
			</rules>
		</rewrite>
	</system.webServer>
</configuration>';
            }

            return apply_filters('iis7_url_rewrite_rules', $rules);
        }

        public function add_rule($regex, $query, $after = 'bottom')
        {
            if(is_array($query))
            {
                $external = false;
                $query = add_query_arg($query, 'index.php');
            }
            else
            {
                $index = ! str_contains($query, '?') ? strlen($query) : strpos($query, '?');
                $front = substr($query, 0, $index);

                $external = $front !== $this->index;
            }

            // "external" = it doesn't correspond to index.php.
            if($external)
            {
                $this->add_external_rule($regex, $query);
            }
            else
            {
                if('bottom' === $after)
                {
                    $this->extra_rules = array_merge($this->extra_rules, [$regex => $query]);
                }
                else
                {
                    $this->extra_rules_top = array_merge($this->extra_rules_top, [$regex => $query]);
                }
            }
        }

        public function add_external_rule($regex, $query)
        {
            $this->non_wp_rules[$regex] = $query;
        }

        public function add_endpoint($name, $places, $query_var = true)
        {
            global $wp;

            // For backward compatibility, if null has explicitly been passed as `$query_var`, assume `true`.
            if(true === $query_var || null === $query_var)
            {
                $query_var = $name;
            }
            $this->endpoints[] = [$places, $name, $query_var];

            if($query_var)
            {
                $wp->add_query_var($query_var);
            }
        }

        public function add_permastruct($name, $struct, $args = [])
        {
            // Back-compat for the old parameters: $with_front and $ep_mask.
            if(! is_array($args))
            {
                $args = ['with_front' => $args];
            }

            if(func_num_args() === 4)
            {
                $args['ep_mask'] = func_get_arg(3);
            }

            $defaults = [
                'with_front' => true,
                'ep_mask' => EP_NONE,
                'paged' => true,
                'feed' => true,
                'forcomments' => false,
                'walk_dirs' => true,
                'endpoints' => true,
            ];

            $args = array_intersect_key($args, $defaults);
            $args = wp_parse_args($args, $defaults);

            if($args['with_front'])
            {
                $struct = $this->front.$struct;
            }
            else
            {
                $struct = $this->root.$struct;
            }

            $args['struct'] = $struct;

            $this->extra_permastructs[$name] = $args;
        }

        public function remove_permastruct($name)
        {
            unset($this->extra_permastructs[$name]);
        }

        public function flush_rules($hard = true)
        {
            static $do_hard_later = null;

            // Prevent this action from running before everyone has registered their rewrites.
            if(! did_action('wp_loaded'))
            {
                add_action('wp_loaded', [$this, 'flush_rules']);
                $do_hard_later = (isset($do_hard_later)) ? $do_hard_later || $hard : $hard;

                return;
            }

            if(isset($do_hard_later))
            {
                $hard = $do_hard_later;
                unset($do_hard_later);
            }

            $this->refresh_rewrite_rules();

            if(! $hard || ! apply_filters('flush_rewrite_rules_hard', true))
            {
                return;
            }
            if(function_exists('save_mod_rewrite_rules'))
            {
                save_mod_rewrite_rules();
            }
            if(function_exists('iis7_save_url_rewrite_rules'))
            {
                iis7_save_url_rewrite_rules();
            }
        }

        public function set_permalink_structure($permalink_structure)
        {
            if($this->permalink_structure !== $permalink_structure)
            {
                $old_permalink_structure = $this->permalink_structure;
                update_option('permalink_structure', $permalink_structure);

                $this->init();

                do_action('permalink_structure_changed', $old_permalink_structure, $permalink_structure);
            }
        }

        public function set_category_base($category_base)
        {
            if(get_option('category_base') !== $category_base)
            {
                update_option('category_base', $category_base);
                $this->init();
            }
        }

        public function set_tag_base($tag_base)
        {
            if(get_option('tag_base') !== $tag_base)
            {
                update_option('tag_base', $tag_base);
                $this->init();
            }
        }
    }
