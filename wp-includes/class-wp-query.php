<?php

    #[AllowDynamicProperties]
    class WP_Query
    {
        public $query;

        public $query_vars = [];

        public $tax_query;

        public $meta_query = false;

        public $date_query = false;

        public $queried_object;

        public $queried_object_id;

        public $request;

        public $posts;

        public $post_count = 0;

        public $current_post = -1;

        public $before_loop = true;

        public $in_the_loop = false;

        public $post;

        public $comments;

        public $comment_count = 0;

        public $current_comment = -1;

        public $comment;

        public $found_posts = 0;

        public $max_num_pages = 0;

        public $max_num_comment_pages = 0;

        public $is_single = false;

        public $is_preview = false;

        public $is_page = false;

        public $is_archive = false;

        public $is_date = false;

        public $is_year = false;

        public $is_month = false;

        public $is_day = false;

        public $is_time = false;

        public $is_author = false;

        public $is_category = false;

        public $is_tag = false;

        public $is_tax = false;

        public $is_search = false;

        public $is_feed = false;

        public $is_comment_feed = false;

        public $is_trackback = false;

        public $is_home = false;

        public $is_privacy_policy = false;

        public $is_404 = false;

        public $is_embed = false;

        public $is_paged = false;

        public $is_admin = false;

        public $is_attachment = false;

        public $is_singular = false;

        public $is_robots = false;

        public $is_favicon = false;

        public $is_posts_page = false;

        public $is_post_type_archive = false;

        public $thumbnails_cached = false;

        protected $allow_query_attachment_by_filename = false;

        private $query_vars_hash = false;

        private $query_vars_changed = true;

        private $stopwords;

        private $compat_fields = ['query_vars_hash', 'query_vars_changed'];

        private $compat_methods = ['init_query_flags', 'parse_tax_query'];

        public function __construct($query = '')
        {
            if(! empty($query))
            {
                $this->query($query);
            }
        }

        public function query($query)
        {
            $this->init();
            $this->query = wp_parse_args($query);
            $this->query_vars = $this->query;

            return $this->get_posts();
        }

        public function init()
        {
            unset($this->posts);
            unset($this->query);
            $this->query_vars = [];
            unset($this->queried_object);
            unset($this->queried_object_id);
            $this->post_count = 0;
            $this->current_post = -1;
            $this->in_the_loop = false;
            $this->before_loop = true;
            unset($this->request);
            unset($this->post);
            unset($this->comments);
            unset($this->comment);
            $this->comment_count = 0;
            $this->current_comment = -1;
            $this->found_posts = 0;
            $this->max_num_pages = 0;
            $this->max_num_comment_pages = 0;

            $this->init_query_flags();
        }

        private function init_query_flags()
        {
            $this->is_single = false;
            $this->is_preview = false;
            $this->is_page = false;
            $this->is_archive = false;
            $this->is_date = false;
            $this->is_year = false;
            $this->is_month = false;
            $this->is_day = false;
            $this->is_time = false;
            $this->is_author = false;
            $this->is_category = false;
            $this->is_tag = false;
            $this->is_tax = false;
            $this->is_search = false;
            $this->is_feed = false;
            $this->is_comment_feed = false;
            $this->is_trackback = false;
            $this->is_home = false;
            $this->is_privacy_policy = false;
            $this->is_404 = false;
            $this->is_paged = false;
            $this->is_admin = false;
            $this->is_attachment = false;
            $this->is_singular = false;
            $this->is_robots = false;
            $this->is_favicon = false;
            $this->is_posts_page = false;
            $this->is_post_type_archive = false;
        }

        public function get_posts()
        {
            global $wpdb;

            $this->parse_query();

            do_action_ref_array('pre_get_posts', [&$this]);

            // Shorthand.
            $q = &$this->query_vars;

            // Fill again in case 'pre_get_posts' unset some vars.
            $q = $this->fill_query_vars($q);

            $this->allow_query_attachment_by_filename = apply_filters('wp_allow_query_attachment_by_filename', false);
            remove_all_filters('wp_allow_query_attachment_by_filename');

            // Parse meta query.
            $this->meta_query = new WP_Meta_Query();
            $this->meta_query->parse_query_vars($q);

            // Set a flag if a 'pre_get_posts' hook changed the query vars.
            $hash = md5(serialize($this->query_vars));
            if($hash != $this->query_vars_hash)
            {
                $this->query_vars_changed = true;
                $this->query_vars_hash = $hash;
            }
            unset($hash);

            // First let's clear some variables.
            $distinct = '';
            $whichauthor = '';
            $whichmimetype = '';
            $where = '';
            $limits = '';
            $join = '';
            $search = '';
            $groupby = '';
            $post_status_join = false;
            $page = 1;

            if(isset($q['caller_get_posts']))
            {
                _deprecated_argument('WP_Query', '3.1.0', sprintf(/* translators: 1: caller_get_posts, 2: ignore_sticky_posts */ __('%1$s is deprecated. Use %2$s instead.'), '<code>caller_get_posts</code>', '<code>ignore_sticky_posts</code>'));

                if(! isset($q['ignore_sticky_posts']))
                {
                    $q['ignore_sticky_posts'] = $q['caller_get_posts'];
                }
            }

            if(! isset($q['ignore_sticky_posts']))
            {
                $q['ignore_sticky_posts'] = false;
            }

            if(! isset($q['suppress_filters']))
            {
                $q['suppress_filters'] = false;
            }

            if(! isset($q['cache_results']))
            {
                $q['cache_results'] = true;
            }

            if(! isset($q['update_post_term_cache']))
            {
                $q['update_post_term_cache'] = true;
            }

            if(! isset($q['update_menu_item_cache']))
            {
                $q['update_menu_item_cache'] = false;
            }

            if(! isset($q['lazy_load_term_meta']))
            {
                $q['lazy_load_term_meta'] = $q['update_post_term_cache'];
            }
            elseif($q['lazy_load_term_meta'])
            { // Lazy loading term meta only works if term caches are primed.
                $q['update_post_term_cache'] = true;
            }

            if(! isset($q['update_post_meta_cache']))
            {
                $q['update_post_meta_cache'] = true;
            }

            if(! isset($q['post_type']))
            {
                if($this->is_search)
                {
                    $q['post_type'] = 'any';
                }
                else
                {
                    $q['post_type'] = '';
                }
            }
            $post_type = $q['post_type'];
            if(empty($q['posts_per_page']))
            {
                $q['posts_per_page'] = get_option('posts_per_page');
            }
            if(isset($q['showposts']) && $q['showposts'])
            {
                $q['showposts'] = (int) $q['showposts'];
                $q['posts_per_page'] = $q['showposts'];
            }
            if((isset($q['posts_per_archive_page']) && 0 != $q['posts_per_archive_page']) && ($this->is_archive || $this->is_search))
            {
                $q['posts_per_page'] = $q['posts_per_archive_page'];
            }
            if(! isset($q['nopaging']))
            {
                if(-1 == $q['posts_per_page'])
                {
                    $q['nopaging'] = true;
                }
                else
                {
                    $q['nopaging'] = false;
                }
            }

            if($this->is_feed)
            {
                // This overrides 'posts_per_page'.
                if(! empty($q['posts_per_rss']))
                {
                    $q['posts_per_page'] = $q['posts_per_rss'];
                }
                else
                {
                    $q['posts_per_page'] = get_option('posts_per_rss');
                }
                $q['nopaging'] = false;
            }
            $q['posts_per_page'] = (int) $q['posts_per_page'];
            if($q['posts_per_page'] < -1)
            {
                $q['posts_per_page'] = abs($q['posts_per_page']);
            }
            elseif(0 == $q['posts_per_page'])
            {
                $q['posts_per_page'] = 1;
            }

            if(! isset($q['comments_per_page']) || 0 == $q['comments_per_page'])
            {
                $q['comments_per_page'] = get_option('comments_per_page');
            }

            if($this->is_home && (empty($this->query) || 'true' === $q['preview']) && ('page' === get_option('show_on_front')) && get_option('page_on_front'))
            {
                $this->is_page = true;
                $this->is_home = false;
                $q['page_id'] = get_option('page_on_front');
            }

            if(isset($q['page']))
            {
                $q['page'] = trim($q['page'], '/');
                $q['page'] = absint($q['page']);
            }

            // If true, forcibly turns off SQL_CALC_FOUND_ROWS even when limits are present.
            if(isset($q['no_found_rows']))
            {
                $q['no_found_rows'] = (bool) $q['no_found_rows'];
            }
            else
            {
                $q['no_found_rows'] = false;
            }

            switch($q['fields'])
            {
                case 'ids':
                    $fields = "{$wpdb->posts}.ID";
                    break;
                case 'id=>parent':
                    $fields = "{$wpdb->posts}.ID, {$wpdb->posts}.post_parent";
                    break;
                default:
                    $fields = "{$wpdb->posts}.*";
            }

            if('' !== $q['menu_order'])
            {
                $where .= " AND {$wpdb->posts}.menu_order = ".$q['menu_order'];
            }
            // The "m" parameter is meant for months but accepts datetimes of varying specificity.
            if($q['m'])
            {
                $where .= " AND YEAR({$wpdb->posts}.post_date)=".substr($q['m'], 0, 4);
                if(strlen($q['m']) > 5)
                {
                    $where .= " AND MONTH({$wpdb->posts}.post_date)=".substr($q['m'], 4, 2);
                }
                if(strlen($q['m']) > 7)
                {
                    $where .= " AND DAYOFMONTH({$wpdb->posts}.post_date)=".substr($q['m'], 6, 2);
                }
                if(strlen($q['m']) > 9)
                {
                    $where .= " AND HOUR({$wpdb->posts}.post_date)=".substr($q['m'], 8, 2);
                }
                if(strlen($q['m']) > 11)
                {
                    $where .= " AND MINUTE({$wpdb->posts}.post_date)=".substr($q['m'], 10, 2);
                }
                if(strlen($q['m']) > 13)
                {
                    $where .= " AND SECOND({$wpdb->posts}.post_date)=".substr($q['m'], 12, 2);
                }
            }

            // Handle the other individual date parameters.
            $date_parameters = [];

            if('' !== $q['hour'])
            {
                $date_parameters['hour'] = $q['hour'];
            }

            if('' !== $q['minute'])
            {
                $date_parameters['minute'] = $q['minute'];
            }

            if('' !== $q['second'])
            {
                $date_parameters['second'] = $q['second'];
            }

            if($q['year'])
            {
                $date_parameters['year'] = $q['year'];
            }

            if($q['monthnum'])
            {
                $date_parameters['monthnum'] = $q['monthnum'];
            }

            if($q['w'])
            {
                $date_parameters['week'] = $q['w'];
            }

            if($q['day'])
            {
                $date_parameters['day'] = $q['day'];
            }

            if($date_parameters)
            {
                $date_query = new WP_Date_Query([$date_parameters]);
                $where .= $date_query->get_sql();
            }
            unset($date_parameters, $date_query);

            // Handle complex date queries.
            if(! empty($q['date_query']))
            {
                $this->date_query = new WP_Date_Query($q['date_query']);
                $where .= $this->date_query->get_sql();
            }

            // If we've got a post_type AND it's not "any" post_type.
            if(! empty($q['post_type']) && 'any' !== $q['post_type'])
            {
                foreach((array) $q['post_type'] as $_post_type)
                {
                    $ptype_obj = get_post_type_object($_post_type);
                    if(! $ptype_obj || ! $ptype_obj->query_var || empty($q[$ptype_obj->query_var]))
                    {
                        continue;
                    }

                    if(! $ptype_obj->hierarchical)
                    {
                        // Non-hierarchical post types can directly use 'name'.
                        $q['name'] = $q[$ptype_obj->query_var];
                    }
                    else
                    {
                        // Hierarchical post types will operate through 'pagename'.
                        $q['pagename'] = $q[$ptype_obj->query_var];
                        $q['name'] = '';
                    }

                    // Only one request for a slug is possible, this is why name & pagename are overwritten above.
                    break;
                } // End foreach.
                unset($ptype_obj);
            }

            if('' !== $q['title'])
            {
                $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_title = %s", stripslashes($q['title']));
            }

            // Parameters related to 'post_name'.
            if('' !== $q['name'])
            {
                $q['name'] = sanitize_title_for_query($q['name']);
                $where .= " AND {$wpdb->posts}.post_name = '".$q['name']."'";
            }
            elseif('' !== $q['pagename'])
            {
                if(isset($this->queried_object_id))
                {
                    $reqpage = $this->queried_object_id;
                }
                else
                {
                    if('page' !== $q['post_type'])
                    {
                        foreach((array) $q['post_type'] as $_post_type)
                        {
                            $ptype_obj = get_post_type_object($_post_type);
                            if(! $ptype_obj || ! $ptype_obj->hierarchical)
                            {
                                continue;
                            }

                            $reqpage = get_page_by_path($q['pagename'], OBJECT, $_post_type);
                            if($reqpage)
                            {
                                break;
                            }
                        }
                        unset($ptype_obj);
                    }
                    else
                    {
                        $reqpage = get_page_by_path($q['pagename']);
                    }
                    if(! empty($reqpage))
                    {
                        $reqpage = $reqpage->ID;
                    }
                    else
                    {
                        $reqpage = 0;
                    }
                }

                $page_for_posts = get_option('page_for_posts');
                if(('page' !== get_option('show_on_front')) || empty($page_for_posts) || ($reqpage != $page_for_posts))
                {
                    $q['pagename'] = sanitize_title_for_query(wp_basename($q['pagename']));
                    $q['name'] = $q['pagename'];
                    $where .= " AND ({$wpdb->posts}.ID = '$reqpage')";
                    $reqpage_obj = get_post($reqpage);
                    if(is_object($reqpage_obj) && 'attachment' === $reqpage_obj->post_type)
                    {
                        $this->is_attachment = true;
                        $post_type = 'attachment';
                        $q['post_type'] = 'attachment';
                        $this->is_page = true;
                        $q['attachment_id'] = $reqpage;
                    }
                }
            }
            elseif('' !== $q['attachment'])
            {
                $q['attachment'] = sanitize_title_for_query(wp_basename($q['attachment']));
                $q['name'] = $q['attachment'];
                $where .= " AND {$wpdb->posts}.post_name = '".$q['attachment']."'";
            }
            elseif(is_array($q['post_name__in']) && ! empty($q['post_name__in']))
            {
                $q['post_name__in'] = array_map('sanitize_title_for_query', $q['post_name__in']);
                $post_name__in = "'".implode("','", $q['post_name__in'])."'";
                $where .= " AND {$wpdb->posts}.post_name IN ($post_name__in)";
            }

            // If an attachment is requested by number, let it supersede any post number.
            if($q['attachment_id'])
            {
                $q['p'] = absint($q['attachment_id']);
            }

            // If a post number is specified, load that post.
            if($q['p'])
            {
                $where .= " AND {$wpdb->posts}.ID = ".$q['p'];
            }
            elseif($q['post__in'])
            {
                $post__in = implode(',', array_map('absint', $q['post__in']));
                $where .= " AND {$wpdb->posts}.ID IN ($post__in)";
            }
            elseif($q['post__not_in'])
            {
                $post__not_in = implode(',', array_map('absint', $q['post__not_in']));
                $where .= " AND {$wpdb->posts}.ID NOT IN ($post__not_in)";
            }

            if(is_numeric($q['post_parent']))
            {
                $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_parent = %d ", $q['post_parent']);
            }
            elseif($q['post_parent__in'])
            {
                $post_parent__in = implode(',', array_map('absint', $q['post_parent__in']));
                $where .= " AND {$wpdb->posts}.post_parent IN ($post_parent__in)";
            }
            elseif($q['post_parent__not_in'])
            {
                $post_parent__not_in = implode(',', array_map('absint', $q['post_parent__not_in']));
                $where .= " AND {$wpdb->posts}.post_parent NOT IN ($post_parent__not_in)";
            }

            if($q['page_id'])
            {
                if(('page' !== get_option('show_on_front')) || (get_option('page_for_posts') != $q['page_id']))
                {
                    $q['p'] = $q['page_id'];
                    $where = " AND {$wpdb->posts}.ID = ".$q['page_id'];
                }
            }

            // If a search pattern is specified, load the posts that match.
            if(strlen($q['s']))
            {
                $search = $this->parse_search($q);
            }

            if(! $q['suppress_filters'])
            {
                $search = apply_filters_ref_array('posts_search', [$search, &$this]);
            }

            // Taxonomies.
            if(! $this->is_singular)
            {
                $this->parse_tax_query($q);

                $clauses = $this->tax_query->get_sql($wpdb->posts, 'ID');

                $join .= $clauses['join'];
                $where .= $clauses['where'];
            }

            if($this->is_tax)
            {
                if(empty($post_type))
                {
                    // Do a fully inclusive search for currently registered post types of queried taxonomies.
                    $post_type = [];
                    $taxonomies = array_keys($this->tax_query->queried_terms);
                    foreach(get_post_types(['exclude_from_search' => false]) as $pt)
                    {
                        $object_taxonomies = 'attachment' === $pt ? get_taxonomies_for_attachments() : get_object_taxonomies($pt);
                        if(array_intersect($taxonomies, $object_taxonomies))
                        {
                            $post_type[] = $pt;
                        }
                    }
                    if(! $post_type)
                    {
                        $post_type = 'any';
                    }
                    elseif(count($post_type) === 1)
                    {
                        $post_type = $post_type[0];
                    }

                    $post_status_join = true;
                }
                elseif(in_array('attachment', (array) $post_type, true))
                {
                    $post_status_join = true;
                }
            }

            /*
		 * Ensure that 'taxonomy', 'term', 'term_id', 'cat', and
		 * 'category_name' vars are set for backward compatibility.
		 */
            if(! empty($this->tax_query->queried_terms))
            {
                /*
			 * Set 'taxonomy', 'term', and 'term_id' to the
			 * first taxonomy other than 'post_tag' or 'category'.
			 */
                if(! isset($q['taxonomy']))
                {
                    foreach($this->tax_query->queried_terms as $queried_taxonomy => $queried_items)
                    {
                        if(empty($queried_items['terms'][0]))
                        {
                            continue;
                        }

                        if(! in_array($queried_taxonomy, ['category', 'post_tag'], true))
                        {
                            $q['taxonomy'] = $queried_taxonomy;

                            if('slug' === $queried_items['field'])
                            {
                                $q['term'] = $queried_items['terms'][0];
                            }
                            else
                            {
                                $q['term_id'] = $queried_items['terms'][0];
                            }

                            // Take the first one we find.
                            break;
                        }
                    }
                }

                // 'cat', 'category_name', 'tag_id'.
                foreach($this->tax_query->queried_terms as $queried_taxonomy => $queried_items)
                {
                    if(empty($queried_items['terms'][0]))
                    {
                        continue;
                    }

                    if('category' === $queried_taxonomy)
                    {
                        $the_cat = get_term_by($queried_items['field'], $queried_items['terms'][0], 'category');
                        if($the_cat)
                        {
                            $this->set('cat', $the_cat->term_id);
                            $this->set('category_name', $the_cat->slug);
                        }
                        unset($the_cat);
                    }

                    if('post_tag' === $queried_taxonomy)
                    {
                        $the_tag = get_term_by($queried_items['field'], $queried_items['terms'][0], 'post_tag');
                        if($the_tag)
                        {
                            $this->set('tag_id', $the_tag->term_id);
                        }
                        unset($the_tag);
                    }
                }
            }

            if(! empty($this->tax_query->queries) || ! empty($this->meta_query->queries) || ! empty($this->allow_query_attachment_by_filename))
            {
                $groupby = "{$wpdb->posts}.ID";
            }

            // Author/user stuff.

            if(! empty($q['author']) && '0' != $q['author'])
            {
                $q['author'] = addslashes_gpc(''.urldecode($q['author']));
                $authors = array_unique(array_map('intval', preg_split('/[,\s]+/', $q['author'])));
                foreach($authors as $author)
                {
                    $key = $author > 0 ? 'author__in' : 'author__not_in';
                    $q[$key][] = abs($author);
                }
                $q['author'] = implode(',', $authors);
            }

            if(! empty($q['author__not_in']))
            {
                $author__not_in = implode(',', array_map('absint', array_unique((array) $q['author__not_in'])));
                $where .= " AND {$wpdb->posts}.post_author NOT IN ($author__not_in) ";
            }
            elseif(! empty($q['author__in']))
            {
                $author__in = implode(',', array_map('absint', array_unique((array) $q['author__in'])));
                $where .= " AND {$wpdb->posts}.post_author IN ($author__in) ";
            }

            // Author stuff for nice URLs.

            if('' !== $q['author_name'])
            {
                if(str_contains($q['author_name'], '/'))
                {
                    $q['author_name'] = explode('/', $q['author_name']);
                    if($q['author_name'][count($q['author_name']) - 1])
                    {
                        $q['author_name'] = $q['author_name'][count($q['author_name']) - 1]; // No trailing slash.
                    }
                    else
                    {
                        $q['author_name'] = $q['author_name'][count($q['author_name']) - 2]; // There was a trailing slash.
                    }
                }
                $q['author_name'] = sanitize_title_for_query($q['author_name']);
                $q['author'] = get_user_by('slug', $q['author_name']);
                if($q['author'])
                {
                    $q['author'] = $q['author']->ID;
                }
                $whichauthor .= " AND ({$wpdb->posts}.post_author = ".absint($q['author']).')';
            }

            // Matching by comment count.
            if(isset($q['comment_count']))
            {
                // Numeric comment count is converted to array format.
                if(is_numeric($q['comment_count']))
                {
                    $q['comment_count'] = [
                        'value' => (int) $q['comment_count'],
                    ];
                }

                if(isset($q['comment_count']['value']))
                {
                    $q['comment_count'] = array_merge([
                                                          'compare' => '=',
                                                      ], $q['comment_count']);

                    // Fallback for invalid compare operators is '='.
                    $compare_operators = ['=', '!=', '>', '>=', '<', '<='];
                    if(! in_array($q['comment_count']['compare'], $compare_operators, true))
                    {
                        $q['comment_count']['compare'] = '=';
                    }

                    $where .= $wpdb->prepare(" AND {$wpdb->posts}.comment_count {$q['comment_count']['compare']} %d", $q['comment_count']['value']);
                }
            }

            // MIME-Type stuff for attachment browsing.

            if(isset($q['post_mime_type']) && '' !== $q['post_mime_type'])
            {
                $whichmimetype = wp_post_mime_type_where($q['post_mime_type'], $wpdb->posts);
            }
            $where .= $search.$whichauthor.$whichmimetype;

            if(! empty($this->allow_query_attachment_by_filename))
            {
                $join .= " LEFT JOIN {$wpdb->postmeta} AS sq1 ON ( {$wpdb->posts}.ID = sq1.post_id AND sq1.meta_key = '_wp_attached_file' )";
            }

            if(! empty($this->meta_query->queries))
            {
                $clauses = $this->meta_query->get_sql('post', $wpdb->posts, 'ID', $this);
                $join .= $clauses['join'];
                $where .= $clauses['where'];
            }

            $rand = (isset($q['orderby']) && 'rand' === $q['orderby']);
            if(! isset($q['order']))
            {
                $q['order'] = $rand ? '' : 'DESC';
            }
            else
            {
                $q['order'] = $rand ? '' : $this->parse_order($q['order']);
            }

            // These values of orderby should ignore the 'order' parameter.
            $force_asc = ['post__in', 'post_name__in', 'post_parent__in'];
            if(isset($q['orderby']) && in_array($q['orderby'], $force_asc, true))
            {
                $q['order'] = '';
            }

            // Order by.
            if(empty($q['orderby']))
            {
                /*
			 * Boolean false or empty array blanks out ORDER BY,
			 * while leaving the value unset or otherwise empty sets the default.
			 */
                if(isset($q['orderby']) && (is_array($q['orderby']) || false === $q['orderby']))
                {
                    $orderby = '';
                }
                else
                {
                    $orderby = "{$wpdb->posts}.post_date ".$q['order'];
                }
            }
            elseif('none' === $q['orderby'])
            {
                $orderby = '';
            }
            else
            {
                $orderby_array = [];
                if(is_array($q['orderby']))
                {
                    foreach($q['orderby'] as $_orderby => $order)
                    {
                        $orderby = addslashes_gpc(urldecode($_orderby));
                        $parsed = $this->parse_orderby($orderby);

                        if(! $parsed)
                        {
                            continue;
                        }

                        $orderby_array[] = $parsed.' '.$this->parse_order($order);
                    }
                    $orderby = implode(', ', $orderby_array);
                }
                else
                {
                    $q['orderby'] = urldecode($q['orderby']);
                    $q['orderby'] = addslashes_gpc($q['orderby']);

                    foreach(explode(' ', $q['orderby']) as $i => $orderby)
                    {
                        $parsed = $this->parse_orderby($orderby);
                        // Only allow certain values for safety.
                        if(! $parsed)
                        {
                            continue;
                        }

                        $orderby_array[] = $parsed;
                    }
                    $orderby = implode(' '.$q['order'].', ', $orderby_array);

                    if(empty($orderby))
                    {
                        $orderby = "{$wpdb->posts}.post_date ".$q['order'];
                    }
                    elseif(! empty($q['order']))
                    {
                        $orderby .= " {$q['order']}";
                    }
                }
            }

            // Order search results by relevance only when another "orderby" is not specified in the query.
            if(! empty($q['s']))
            {
                $search_orderby = '';
                if(! empty($q['search_orderby_title']) && (empty($q['orderby']) && ! $this->is_feed) || (isset($q['orderby']) && 'relevance' === $q['orderby']))
                {
                    $search_orderby = $this->parse_search_order($q);
                }

                if(! $q['suppress_filters'])
                {
                    $search_orderby = apply_filters('posts_search_orderby', $search_orderby, $this);
                }

                if($search_orderby)
                {
                    $orderby = $orderby ? $search_orderby.', '.$orderby : $search_orderby;
                }
            }

            if(is_array($post_type) && count($post_type) > 1)
            {
                $post_type_cap = 'multiple_post_type';
            }
            else
            {
                if(is_array($post_type))
                {
                    $post_type = reset($post_type);
                }
                $post_type_object = get_post_type_object($post_type);
                if(empty($post_type_object))
                {
                    $post_type_cap = $post_type;
                }
            }

            if(isset($q['post_password']))
            {
                $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_password = %s", $q['post_password']);
                if(empty($q['perm']))
                {
                    $q['perm'] = 'readable';
                }
            }
            elseif(isset($q['has_password']))
            {
                $where .= sprintf(" AND {$wpdb->posts}.post_password %s ''", $q['has_password'] ? '!=' : '=');
            }

            if(! empty($q['comment_status']))
            {
                $where .= $wpdb->prepare(" AND {$wpdb->posts}.comment_status = %s ", $q['comment_status']);
            }

            if(! empty($q['ping_status']))
            {
                $where .= $wpdb->prepare(" AND {$wpdb->posts}.ping_status = %s ", $q['ping_status']);
            }

            $skip_post_status = false;
            if('any' === $post_type)
            {
                $in_search_post_types = get_post_types(['exclude_from_search' => false]);
                if(empty($in_search_post_types))
                {
                    $post_type_where = ' AND 1=0 ';
                    $skip_post_status = true;
                }
                else
                {
                    $post_type_where = " AND {$wpdb->posts}.post_type IN ('".implode("', '", array_map('esc_sql', $in_search_post_types))."')";
                }
            }
            elseif(! empty($post_type) && is_array($post_type))
            {
                $post_type_where = " AND {$wpdb->posts}.post_type IN ('".implode("', '", esc_sql($post_type))."')";
            }
            elseif(! empty($post_type))
            {
                $post_type_where = $wpdb->prepare(" AND {$wpdb->posts}.post_type = %s", $post_type);
                $post_type_object = get_post_type_object($post_type);
            }
            elseif($this->is_attachment)
            {
                $post_type_where = " AND {$wpdb->posts}.post_type = 'attachment'";
                $post_type_object = get_post_type_object('attachment');
            }
            elseif($this->is_page)
            {
                $post_type_where = " AND {$wpdb->posts}.post_type = 'page'";
                $post_type_object = get_post_type_object('page');
            }
            else
            {
                $post_type_where = " AND {$wpdb->posts}.post_type = 'post'";
                $post_type_object = get_post_type_object('post');
            }

            $edit_cap = 'edit_post';
            $read_cap = 'read_post';

            if(! empty($post_type_object))
            {
                $edit_others_cap = $post_type_object->cap->edit_others_posts;
                $read_private_cap = $post_type_object->cap->read_private_posts;
            }
            else
            {
                $edit_others_cap = 'edit_others_'.$post_type_cap.'s';
                $read_private_cap = 'read_private_'.$post_type_cap.'s';
            }

            $user_id = get_current_user_id();

            $q_status = [];
            if($skip_post_status)
            {
                $where .= $post_type_where;
            }
            elseif(! empty($q['post_status']))
            {
                $where .= $post_type_where;

                $statuswheres = [];
                $q_status = $q['post_status'];
                if(! is_array($q_status))
                {
                    $q_status = explode(',', $q_status);
                }
                $r_status = [];
                $p_status = [];
                $e_status = [];
                if(in_array('any', $q_status, true))
                {
                    foreach(get_post_stati(['exclude_from_search' => true]) as $status)
                    {
                        if(! in_array($status, $q_status, true))
                        {
                            $e_status[] = "{$wpdb->posts}.post_status <> '$status'";
                        }
                    }
                }
                else
                {
                    foreach(get_post_stati() as $status)
                    {
                        if(in_array($status, $q_status, true))
                        {
                            if('private' === $status)
                            {
                                $p_status[] = "{$wpdb->posts}.post_status = '$status'";
                            }
                            else
                            {
                                $r_status[] = "{$wpdb->posts}.post_status = '$status'";
                            }
                        }
                    }
                }

                if(empty($q['perm']) || 'readable' !== $q['perm'])
                {
                    $r_status = array_merge($r_status, $p_status);
                    unset($p_status);
                }

                if(! empty($e_status))
                {
                    $statuswheres[] = '('.implode(' AND ', $e_status).')';
                }
                if(! empty($r_status))
                {
                    if(! empty($q['perm']) && 'editable' === $q['perm'] && ! current_user_can($edit_others_cap))
                    {
                        $statuswheres[] = "({$wpdb->posts}.post_author = $user_id ".'AND ('.implode(' OR ', $r_status).'))';
                    }
                    else
                    {
                        $statuswheres[] = '('.implode(' OR ', $r_status).')';
                    }
                }
                if(! empty($p_status))
                {
                    if(! empty($q['perm']) && 'readable' === $q['perm'] && ! current_user_can($read_private_cap))
                    {
                        $statuswheres[] = "({$wpdb->posts}.post_author = $user_id ".'AND ('.implode(' OR ', $p_status).'))';
                    }
                    else
                    {
                        $statuswheres[] = '('.implode(' OR ', $p_status).')';
                    }
                }
                if($post_status_join)
                {
                    $join .= " LEFT JOIN {$wpdb->posts} AS p2 ON ({$wpdb->posts}.post_parent = p2.ID) ";
                    foreach($statuswheres as $index => $statuswhere)
                    {
                        $statuswheres[$index] = "($statuswhere OR ({$wpdb->posts}.post_status = 'inherit' AND ".str_replace($wpdb->posts, 'p2', $statuswhere).'))';
                    }
                }
                $where_status = implode(' OR ', $statuswheres);
                if(! empty($where_status))
                {
                    $where .= " AND ($where_status)";
                }
            }
            elseif(! $this->is_singular)
            {
                if('any' === $post_type)
                {
                    $queried_post_types = get_post_types(['exclude_from_search' => false]);
                }
                elseif(is_array($post_type))
                {
                    $queried_post_types = $post_type;
                }
                elseif(! empty($post_type))
                {
                    $queried_post_types = [$post_type];
                }
                else
                {
                    $queried_post_types = ['post'];
                }

                if(! empty($queried_post_types))
                {
                    $status_type_clauses = [];

                    foreach($queried_post_types as $queried_post_type)
                    {
                        $queried_post_type_object = get_post_type_object($queried_post_type);

                        $type_where = '('.$wpdb->prepare("{$wpdb->posts}.post_type = %s AND (", $queried_post_type);

                        // Public statuses.
                        $public_statuses = get_post_stati(['public' => true]);
                        $status_clauses = [];
                        foreach($public_statuses as $public_status)
                        {
                            $status_clauses[] = "{$wpdb->posts}.post_status = '$public_status'";
                        }
                        $type_where .= implode(' OR ', $status_clauses);

                        // Add protected states that should show in the admin all list.
                        if($this->is_admin)
                        {
                            $admin_all_statuses = get_post_stati([
                                                                     'protected' => true,
                                                                     'show_in_admin_all_list' => true,
                                                                 ]);
                            foreach($admin_all_statuses as $admin_all_status)
                            {
                                $type_where .= " OR {$wpdb->posts}.post_status = '$admin_all_status'";
                            }
                        }

                        // Add private states that are visible to current user.
                        if(is_user_logged_in() && $queried_post_type_object instanceof WP_Post_Type)
                        {
                            $read_private_cap = $queried_post_type_object->cap->read_private_posts;
                            $private_statuses = get_post_stati(['private' => true]);
                            foreach($private_statuses as $private_status)
                            {
                                $type_where .= current_user_can($read_private_cap) ? " \nOR {$wpdb->posts}.post_status = '$private_status'" : " \nOR ({$wpdb->posts}.post_author = $user_id AND {$wpdb->posts}.post_status = '$private_status')";
                            }
                        }

                        $type_where .= '))';

                        $status_type_clauses[] = $type_where;
                    }

                    if(! empty($status_type_clauses))
                    {
                        $where .= ' AND ('.implode(' OR ', $status_type_clauses).')';
                    }
                }
                else
                {
                    $where .= ' AND 1=0 ';
                }
            }
            else
            {
                $where .= $post_type_where;
            }

            /*
		 * Apply filters on where and join prior to paging so that any
		 * manipulations to them are reflected in the paging by day queries.
		 */
            if(! $q['suppress_filters'])
            {
                $where = apply_filters_ref_array('posts_where', [$where, &$this]);

                $join = apply_filters_ref_array('posts_join', [$join, &$this]);
            }

            // Paging.
            if(empty($q['nopaging']) && ! $this->is_singular)
            {
                $page = absint($q['paged']);
                if(! $page)
                {
                    $page = 1;
                }

                // If 'offset' is provided, it takes precedence over 'paged'.
                if(isset($q['offset']) && is_numeric($q['offset']))
                {
                    $q['offset'] = absint($q['offset']);
                    $pgstrt = $q['offset'].', ';
                }
                else
                {
                    $pgstrt = absint(($page - 1) * $q['posts_per_page']).', ';
                }
                $limits = 'LIMIT '.$pgstrt.$q['posts_per_page'];
            }

            // Comments feeds.
            if($this->is_comment_feed && ! $this->is_singular)
            {
                if($this->is_archive || $this->is_search)
                {
                    $cjoin = "JOIN {$wpdb->posts} ON ( {$wpdb->comments}.comment_post_ID = {$wpdb->posts}.ID ) $join ";
                    $cwhere = "WHERE comment_approved = '1' $where";
                    $cgroupby = "{$wpdb->comments}.comment_id";
                }
                else
                { // Other non-singular, e.g. front.
                    $cjoin = "JOIN {$wpdb->posts} ON ( {$wpdb->comments}.comment_post_ID = {$wpdb->posts}.ID )";
                    $cwhere = "WHERE ( post_status = 'publish' OR ( post_status = 'inherit' AND post_type = 'attachment' ) ) AND comment_approved = '1'";
                    $cgroupby = '';
                }

                if(! $q['suppress_filters'])
                {
                    $cjoin = apply_filters_ref_array('comment_feed_join', [$cjoin, &$this]);

                    $cwhere = apply_filters_ref_array('comment_feed_where', [$cwhere, &$this]);

                    $cgroupby = apply_filters_ref_array('comment_feed_groupby', [$cgroupby, &$this]);

                    $corderby = apply_filters_ref_array('comment_feed_orderby', ['comment_date_gmt DESC', &$this]);

                    $climits = apply_filters_ref_array('comment_feed_limits', [
                        'LIMIT '.get_option('posts_per_rss'),
                        &$this
                    ]);
                }

                $cgroupby = (! empty($cgroupby)) ? 'GROUP BY '.$cgroupby : '';
                $corderby = (! empty($corderby)) ? 'ORDER BY '.$corderby : '';
                $climits = (! empty($climits)) ? $climits : '';

                $comments_request = "SELECT $distinct {$wpdb->comments}.comment_ID FROM {$wpdb->comments} $cjoin $cwhere $cgroupby $corderby $climits";

                $key = md5($comments_request);
                $last_changed = wp_cache_get_last_changed('comment').':'.wp_cache_get_last_changed('posts');

                $cache_key = "comment_feed:$key:$last_changed";
                $comment_ids = wp_cache_get($cache_key, 'comment-queries');
                if(false === $comment_ids)
                {
                    $comment_ids = $wpdb->get_col($comments_request);
                    wp_cache_add($cache_key, $comment_ids, 'comment-queries');
                }
                _prime_comment_caches($comment_ids);

                // Convert to WP_Comment.

                $this->comments = array_map('get_comment', $comment_ids);
                $this->comment_count = count($this->comments);

                $post_ids = [];

                foreach($this->comments as $comment)
                {
                    $post_ids[] = (int) $comment->comment_post_ID;
                }

                $post_ids = implode(',', $post_ids);
                $join = '';
                if($post_ids)
                {
                    $where = "AND {$wpdb->posts}.ID IN ($post_ids) ";
                }
                else
                {
                    $where = 'AND 0';
                }
            }

            $pieces = ['where', 'groupby', 'join', 'orderby', 'distinct', 'fields', 'limits'];

            /*
		 * Apply post-paging filters on where and join. Only plugins that
		 * manipulate paging queries should use these hooks.
		 */
            if(! $q['suppress_filters'])
            {
                $where = apply_filters_ref_array('posts_where_paged', [$where, &$this]);

                $groupby = apply_filters_ref_array('posts_groupby', [$groupby, &$this]);

                $join = apply_filters_ref_array('posts_join_paged', [$join, &$this]);

                $orderby = apply_filters_ref_array('posts_orderby', [$orderby, &$this]);

                $distinct = apply_filters_ref_array('posts_distinct', [$distinct, &$this]);

                $limits = apply_filters_ref_array('post_limits', [$limits, &$this]);

                $fields = apply_filters_ref_array('posts_fields', [$fields, &$this]);

                $clauses = (array) apply_filters_ref_array('posts_clauses', [compact($pieces), &$this]);

                $where = isset($clauses['where']) ? $clauses['where'] : '';
                $groupby = isset($clauses['groupby']) ? $clauses['groupby'] : '';
                $join = isset($clauses['join']) ? $clauses['join'] : '';
                $orderby = isset($clauses['orderby']) ? $clauses['orderby'] : '';
                $distinct = isset($clauses['distinct']) ? $clauses['distinct'] : '';
                $fields = isset($clauses['fields']) ? $clauses['fields'] : '';
                $limits = isset($clauses['limits']) ? $clauses['limits'] : '';
            }

            do_action('posts_selection', $where.$groupby.$orderby.$limits.$join);

            /*
		 * Filters again for the benefit of caching plugins.
		 * Regular plugins should use the hooks above.
		 */
            if(! $q['suppress_filters'])
            {
                $where = apply_filters_ref_array('posts_where_request', [$where, &$this]);

                $groupby = apply_filters_ref_array('posts_groupby_request', [$groupby, &$this]);

                $join = apply_filters_ref_array('posts_join_request', [$join, &$this]);

                $orderby = apply_filters_ref_array('posts_orderby_request', [$orderby, &$this]);

                $distinct = apply_filters_ref_array('posts_distinct_request', [$distinct, &$this]);

                $fields = apply_filters_ref_array('posts_fields_request', [$fields, &$this]);

                $limits = apply_filters_ref_array('post_limits_request', [$limits, &$this]);

                $clauses = (array) apply_filters_ref_array('posts_clauses_request', [compact($pieces), &$this]);

                $where = isset($clauses['where']) ? $clauses['where'] : '';
                $groupby = isset($clauses['groupby']) ? $clauses['groupby'] : '';
                $join = isset($clauses['join']) ? $clauses['join'] : '';
                $orderby = isset($clauses['orderby']) ? $clauses['orderby'] : '';
                $distinct = isset($clauses['distinct']) ? $clauses['distinct'] : '';
                $fields = isset($clauses['fields']) ? $clauses['fields'] : '';
                $limits = isset($clauses['limits']) ? $clauses['limits'] : '';
            }

            if(! empty($groupby))
            {
                $groupby = 'GROUP BY '.$groupby;
            }
            if(! empty($orderby))
            {
                $orderby = 'ORDER BY '.$orderby;
            }

            $found_rows = '';
            if(! $q['no_found_rows'] && ! empty($limits))
            {
                $found_rows = 'SQL_CALC_FOUND_ROWS';
            }

            $old_request = "
			SELECT $found_rows $distinct $fields
			FROM {$wpdb->posts} $join
			WHERE 1=1 $where
			$groupby
			$orderby
			$limits
		";

            $this->request = $old_request;

            if(! $q['suppress_filters'])
            {
                $this->request = apply_filters_ref_array('posts_request', [$this->request, &$this]);
            }

            $this->posts = apply_filters_ref_array('posts_pre_query', [null, &$this]);

            /*
		 * Ensure the ID database query is able to be cached.
		 *
		 * Random queries are expected to have unpredictable results and
		 * cannot be cached. Note the space before `RAND` in the string
		 * search, that to ensure against a collision with another
		 * function.
		 *
		 * If `$fields` has been modified by the `posts_fields`,
		 * `posts_fields_request`, `post_clauses` or `posts_clauses_request`
		 * filters, then caching is disabled to prevent caching collisions.
		 */
            $id_query_is_cacheable = ! str_contains(strtoupper($orderby), ' RAND(');

            $cacheable_field_values = [
                "{$wpdb->posts}.*",
                "{$wpdb->posts}.ID, {$wpdb->posts}.post_parent",
                "{$wpdb->posts}.ID",
            ];

            if(! in_array($fields, $cacheable_field_values, true))
            {
                $id_query_is_cacheable = false;
            }

            if($q['cache_results'] && $id_query_is_cacheable)
            {
                $new_request = str_replace($fields, "{$wpdb->posts}.*", $this->request);
                $cache_key = $this->generate_cache_key($q, $new_request);

                $cache_found = false;
                if(null === $this->posts)
                {
                    $cached_results = wp_cache_get($cache_key, 'post-queries', false, $cache_found);

                    if($cached_results)
                    {
                        if('ids' === $q['fields'])
                        {
                            $this->posts = array_map('intval', $cached_results['posts']);
                        }
                        else
                        {
                            _prime_post_caches($cached_results['posts'], $q['update_post_term_cache'], $q['update_post_meta_cache']);

                            $this->posts = array_map('get_post', $cached_results['posts']);
                        }

                        $this->post_count = count($this->posts);
                        $this->found_posts = $cached_results['found_posts'];
                        $this->max_num_pages = $cached_results['max_num_pages'];

                        if('ids' === $q['fields'])
                        {
                            return $this->posts;
                        }
                        elseif('id=>parent' === $q['fields'])
                        {
                            $post_parents = [];

                            foreach($this->posts as $key => $post)
                            {
                                $obj = new stdClass();
                                $obj->ID = (int) $post->ID;
                                $obj->post_parent = (int) $post->post_parent;

                                $this->posts[$key] = $obj;

                                $post_parents[$obj->ID] = $obj->post_parent;
                            }

                            return $post_parents;
                        }
                    }
                }
            }

            if('ids' === $q['fields'])
            {
                if(null === $this->posts)
                {
                    $this->posts = $wpdb->get_col($this->request);
                }

                $this->posts = array_map('intval', $this->posts);
                $this->post_count = count($this->posts);
                $this->set_found_posts($q, $limits);

                if($q['cache_results'] && $id_query_is_cacheable)
                {
                    $cache_value = [
                        'posts' => $this->posts,
                        'found_posts' => $this->found_posts,
                        'max_num_pages' => $this->max_num_pages,
                    ];

                    wp_cache_set($cache_key, $cache_value, 'post-queries');
                }

                return $this->posts;
            }

            if('id=>parent' === $q['fields'])
            {
                if(null === $this->posts)
                {
                    $this->posts = $wpdb->get_results($this->request);
                }

                $this->post_count = count($this->posts);
                $this->set_found_posts($q, $limits);

                $post_parents = [];
                $post_ids = [];

                foreach($this->posts as $key => $post)
                {
                    $this->posts[$key]->ID = (int) $post->ID;
                    $this->posts[$key]->post_parent = (int) $post->post_parent;

                    $post_parents[(int) $post->ID] = (int) $post->post_parent;
                    $post_ids[] = (int) $post->ID;
                }

                if($q['cache_results'] && $id_query_is_cacheable)
                {
                    $cache_value = [
                        'posts' => $post_ids,
                        'found_posts' => $this->found_posts,
                        'max_num_pages' => $this->max_num_pages,
                    ];

                    wp_cache_set($cache_key, $cache_value, 'post-queries');
                }

                return $post_parents;
            }

            if(null === $this->posts)
            {
                $split_the_query = ($old_request == $this->request && "{$wpdb->posts}.*" === $fields && (wp_using_ext_object_cache() || (! empty($limits) && $q['posts_per_page'] < 500)));

                $split_the_query = apply_filters('split_the_query', $split_the_query, $this);

                if($split_the_query)
                {
                    // First get the IDs and then fill in the objects.

                    $this->request = "
					SELECT $found_rows $distinct {$wpdb->posts}.ID
					FROM {$wpdb->posts} $join
					WHERE 1=1 $where
					$groupby
					$orderby
					$limits
				";

                    $this->request = apply_filters('posts_request_ids', $this->request, $this);

                    $post_ids = $wpdb->get_col($this->request);

                    if($post_ids)
                    {
                        $this->posts = $post_ids;
                        $this->set_found_posts($q, $limits);
                        _prime_post_caches($post_ids, $q['update_post_term_cache'], $q['update_post_meta_cache']);
                    }
                    else
                    {
                        $this->posts = [];
                    }
                }
                else
                {
                    $this->posts = $wpdb->get_results($this->request);
                    $this->set_found_posts($q, $limits);
                }
            }

            // Convert to WP_Post objects.
            if($this->posts)
            {
                $this->posts = array_map('get_post', $this->posts);
            }

            if($q['cache_results'] && $id_query_is_cacheable && ! $cache_found)
            {
                $post_ids = wp_list_pluck($this->posts, 'ID');

                $cache_value = [
                    'posts' => $post_ids,
                    'found_posts' => $this->found_posts,
                    'max_num_pages' => $this->max_num_pages,
                ];

                wp_cache_set($cache_key, $cache_value, 'post-queries');
            }

            if(! $q['suppress_filters'])
            {
                $this->posts = apply_filters_ref_array('posts_results', [$this->posts, &$this]);
            }

            if(! empty($this->posts) && $this->is_comment_feed && $this->is_singular)
            {
                $cjoin = apply_filters_ref_array('comment_feed_join', ['', &$this]);

                $cwhere = apply_filters_ref_array('comment_feed_where', [
                    "WHERE comment_post_ID = '{$this->posts[0]->ID}' AND comment_approved = '1'",
                    &$this
                ]);

                $cgroupby = apply_filters_ref_array('comment_feed_groupby', ['', &$this]);
                $cgroupby = (! empty($cgroupby)) ? 'GROUP BY '.$cgroupby : '';

                $corderby = apply_filters_ref_array('comment_feed_orderby', ['comment_date_gmt DESC', &$this]);
                $corderby = (! empty($corderby)) ? 'ORDER BY '.$corderby : '';

                $climits = apply_filters_ref_array('comment_feed_limits', [
                    'LIMIT '.get_option('posts_per_rss'),
                    &$this
                ]);

                $comments_request = "SELECT {$wpdb->comments}.comment_ID FROM {$wpdb->comments} $cjoin $cwhere $cgroupby $corderby $climits";

                $comment_key = md5($comments_request);
                $comment_last_changed = wp_cache_get_last_changed('comment');

                $comment_cache_key = "comment_feed:$comment_key:$comment_last_changed";
                $comment_ids = wp_cache_get($comment_cache_key, 'comment-queries');
                if(false === $comment_ids)
                {
                    $comment_ids = $wpdb->get_col($comments_request);
                    wp_cache_add($comment_cache_key, $comment_ids, 'comment-queries');
                }
                _prime_comment_caches($comment_ids);

                // Convert to WP_Comment.

                $this->comments = array_map('get_comment', $comment_ids);
                $this->comment_count = count($this->comments);
            }

            // Check post status to determine if post should be displayed.
            if(! empty($this->posts) && ($this->is_single || $this->is_page))
            {
                $status = get_post_status($this->posts[0]);

                if('attachment' === $this->posts[0]->post_type && 0 === (int) $this->posts[0]->post_parent)
                {
                    $this->is_page = false;
                    $this->is_single = true;
                    $this->is_attachment = true;
                }

                // If the post_status was specifically requested, let it pass through.
                if(! in_array($status, $q_status, true))
                {
                    $post_status_obj = get_post_status_object($status);

                    if($post_status_obj && ! $post_status_obj->public)
                    {
                        if(! is_user_logged_in())
                        {
                            // User must be logged in to view unpublished posts.
                            $this->posts = [];
                        }
                        else
                        {
                            if($post_status_obj->protected)
                            {
                                // User must have edit permissions on the draft to preview.
                                if(! current_user_can($edit_cap, $this->posts[0]->ID))
                                {
                                    $this->posts = [];
                                }
                                else
                                {
                                    $this->is_preview = true;
                                    if('future' !== $status)
                                    {
                                        $this->posts[0]->post_date = current_time('mysql');
                                    }
                                }
                            }
                            elseif($post_status_obj->private)
                            {
                                if(! current_user_can($read_cap, $this->posts[0]->ID))
                                {
                                    $this->posts = [];
                                }
                            }
                            else
                            {
                                $this->posts = [];
                            }
                        }
                    }
                    elseif(! $post_status_obj)
                    {
                        // Post status is not registered, assume it's not public.
                        if(! current_user_can($edit_cap, $this->posts[0]->ID))
                        {
                            $this->posts = [];
                        }
                    }
                }

                if($this->is_preview && $this->posts && current_user_can($edit_cap, $this->posts[0]->ID))
                {
                    $this->posts[0] = get_post(apply_filters_ref_array('the_preview', [$this->posts[0], &$this]));
                }
            }

            // Put sticky posts at the top of the posts array.
            $sticky_posts = get_option('sticky_posts');
            if($this->is_home && $page <= 1 && is_array($sticky_posts) && ! empty($sticky_posts) && ! $q['ignore_sticky_posts'])
            {
                $num_posts = count($this->posts);
                $sticky_offset = 0;
                // Loop over posts and relocate stickies to the front.
                for($i = 0; $i < $num_posts; $i++)
                {
                    if(in_array($this->posts[$i]->ID, $sticky_posts, true))
                    {
                        $sticky_post = $this->posts[$i];
                        // Remove sticky from current position.
                        array_splice($this->posts, $i, 1);
                        // Move to front, after other stickies.
                        array_splice($this->posts, $sticky_offset, 0, [$sticky_post]);
                        // Increment the sticky offset. The next sticky will be placed at this offset.
                        ++$sticky_offset;
                        // Remove post from sticky posts array.
                        $offset = array_search($sticky_post->ID, $sticky_posts, true);
                        unset($sticky_posts[$offset]);
                    }
                }

                // If any posts have been excluded specifically, Ignore those that are sticky.
                if(! empty($sticky_posts) && ! empty($q['post__not_in']))
                {
                    $sticky_posts = array_diff($sticky_posts, $q['post__not_in']);
                }

                // Fetch sticky posts that weren't in the query results.
                if(! empty($sticky_posts))
                {
                    $stickies = get_posts([
                                              'post__in' => $sticky_posts,
                                              'post_type' => $post_type,
                                              'post_status' => 'publish',
                                              'posts_per_page' => count($sticky_posts),
                                              'suppress_filters' => $q['suppress_filters'],
                                              'cache_results' => $q['cache_results'],
                                              'update_post_meta_cache' => $q['update_post_meta_cache'],
                                              'update_post_term_cache' => $q['update_post_term_cache'],
                                              'lazy_load_term_meta' => $q['lazy_load_term_meta'],
                                          ]);

                    foreach($stickies as $sticky_post)
                    {
                        array_splice($this->posts, $sticky_offset, 0, [$sticky_post]);
                        ++$sticky_offset;
                    }
                }
            }

            if(! $q['suppress_filters'])
            {
                $this->posts = apply_filters_ref_array('the_posts', [$this->posts, &$this]);
            }

            /*
		 * Ensure that any posts added/modified via one of the filters above are
		 * of the type WP_Post and are filtered.
		 */
            if($this->posts)
            {
                $this->post_count = count($this->posts);

                $this->posts = array_map('get_post', $this->posts);

                if($q['cache_results'])
                {
                    update_post_caches($this->posts, $post_type, $q['update_post_term_cache'], $q['update_post_meta_cache']);
                }

                $this->post = reset($this->posts);
            }
            else
            {
                $this->post_count = 0;
                $this->posts = [];
            }

            if(! empty($this->posts) && $q['update_menu_item_cache'])
            {
                update_menu_item_cache($this->posts);
            }

            if($q['lazy_load_term_meta'])
            {
                wp_queue_posts_for_term_meta_lazyload($this->posts);
            }

            return $this->posts;
        }

        public function parse_query($query = '')
        {
            if(! empty($query))
            {
                $this->init();
                $this->query = wp_parse_args($query);
                $this->query_vars = $this->query;
            }
            elseif(! isset($this->query))
            {
                $this->query = $this->query_vars;
            }

            $this->query_vars = $this->fill_query_vars($this->query_vars);
            $qv = &$this->query_vars;
            $this->query_vars_changed = true;

            if(! empty($qv['robots']))
            {
                $this->is_robots = true;
            }
            elseif(! empty($qv['favicon']))
            {
                $this->is_favicon = true;
            }

            if(! is_scalar($qv['p']) || (int) $qv['p'] < 0)
            {
                $qv['p'] = 0;
                $qv['error'] = '404';
            }
            else
            {
                $qv['p'] = (int) $qv['p'];
            }

            $qv['page_id'] = is_scalar($qv['page_id']) ? absint($qv['page_id']) : 0;
            $qv['year'] = is_scalar($qv['year']) ? absint($qv['year']) : 0;
            $qv['monthnum'] = is_scalar($qv['monthnum']) ? absint($qv['monthnum']) : 0;
            $qv['day'] = is_scalar($qv['day']) ? absint($qv['day']) : 0;
            $qv['w'] = is_scalar($qv['w']) ? absint($qv['w']) : 0;
            $qv['m'] = is_scalar($qv['m']) ? preg_replace('|[^0-9]|', '', $qv['m']) : '';
            $qv['paged'] = is_scalar($qv['paged']) ? absint($qv['paged']) : 0;
            $qv['cat'] = preg_replace('|[^0-9,-]|', '', $qv['cat']); // Array or comma-separated list of positive or negative integers.
            $qv['author'] = is_scalar($qv['author']) ? preg_replace('|[^0-9,-]|', '', $qv['author']) : ''; // Comma-separated list of positive or negative integers.
            $qv['pagename'] = is_scalar($qv['pagename']) ? trim($qv['pagename']) : '';
            $qv['name'] = is_scalar($qv['name']) ? trim($qv['name']) : '';
            $qv['title'] = is_scalar($qv['title']) ? trim($qv['title']) : '';

            if(is_scalar($qv['hour']) && '' !== $qv['hour'])
            {
                $qv['hour'] = absint($qv['hour']);
            }
            else
            {
                $qv['hour'] = '';
            }

            if(is_scalar($qv['minute']) && '' !== $qv['minute'])
            {
                $qv['minute'] = absint($qv['minute']);
            }
            else
            {
                $qv['minute'] = '';
            }

            if(is_scalar($qv['second']) && '' !== $qv['second'])
            {
                $qv['second'] = absint($qv['second']);
            }
            else
            {
                $qv['second'] = '';
            }

            if(is_scalar($qv['menu_order']) && '' !== $qv['menu_order'])
            {
                $qv['menu_order'] = absint($qv['menu_order']);
            }
            else
            {
                $qv['menu_order'] = '';
            }

            // Fairly large, potentially too large, upper bound for search string lengths.
            if(! is_scalar($qv['s']) || (! empty($qv['s']) && strlen($qv['s']) > 1600))
            {
                $qv['s'] = '';
            }

            // Compat. Map subpost to attachment.
            if(is_scalar($qv['subpost']) && '' != $qv['subpost'])
            {
                $qv['attachment'] = $qv['subpost'];
            }
            if(is_scalar($qv['subpost_id']) && '' != $qv['subpost_id'])
            {
                $qv['attachment_id'] = $qv['subpost_id'];
            }

            $qv['attachment_id'] = is_scalar($qv['attachment_id']) ? absint($qv['attachment_id']) : 0;

            if(('' !== $qv['attachment']) || ! empty($qv['attachment_id']))
            {
                $this->is_single = true;
                $this->is_attachment = true;
            }
            elseif('' !== $qv['name'])
            {
                $this->is_single = true;
            }
            elseif($qv['p'])
            {
                $this->is_single = true;
            }
            elseif('' !== $qv['pagename'] || ! empty($qv['page_id']))
            {
                $this->is_page = true;
                $this->is_single = false;
            }
            else
            {
                // Look for archive queries. Dates, categories, authors, search, post type archives.

                if(isset($this->query['s']))
                {
                    $this->is_search = true;
                }

                if('' !== $qv['second'])
                {
                    $this->is_time = true;
                    $this->is_date = true;
                }

                if('' !== $qv['minute'])
                {
                    $this->is_time = true;
                    $this->is_date = true;
                }

                if('' !== $qv['hour'])
                {
                    $this->is_time = true;
                    $this->is_date = true;
                }

                if($qv['day'])
                {
                    if(! $this->is_date)
                    {
                        $date = sprintf('%04d-%02d-%02d', $qv['year'], $qv['monthnum'], $qv['day']);
                        if($qv['monthnum'] && $qv['year'] && ! wp_checkdate($qv['monthnum'], $qv['day'], $qv['year'], $date))
                        {
                            $qv['error'] = '404';
                        }
                        else
                        {
                            $this->is_day = true;
                            $this->is_date = true;
                        }
                    }
                }

                if($qv['monthnum'])
                {
                    if(! $this->is_date)
                    {
                        if(12 < $qv['monthnum'])
                        {
                            $qv['error'] = '404';
                        }
                        else
                        {
                            $this->is_month = true;
                            $this->is_date = true;
                        }
                    }
                }

                if($qv['year'])
                {
                    if(! $this->is_date)
                    {
                        $this->is_year = true;
                        $this->is_date = true;
                    }
                }

                if($qv['m'])
                {
                    $this->is_date = true;
                    if(strlen($qv['m']) > 9)
                    {
                        $this->is_time = true;
                    }
                    elseif(strlen($qv['m']) > 7)
                    {
                        $this->is_day = true;
                    }
                    elseif(strlen($qv['m']) > 5)
                    {
                        $this->is_month = true;
                    }
                    else
                    {
                        $this->is_year = true;
                    }
                }

                if($qv['w'])
                {
                    $this->is_date = true;
                }

                $this->query_vars_hash = false;
                $this->parse_tax_query($qv);

                foreach($this->tax_query->queries as $tax_query)
                {
                    if(! is_array($tax_query))
                    {
                        continue;
                    }

                    if(isset($tax_query['operator']) && 'NOT IN' !== $tax_query['operator'])
                    {
                        switch($tax_query['taxonomy'])
                        {
                            case 'category':
                                $this->is_category = true;
                                break;
                            case 'post_tag':
                                $this->is_tag = true;
                                break;
                            default:
                                $this->is_tax = true;
                        }
                    }
                }
                unset($tax_query);

                if(empty($qv['author']) || ('0' == $qv['author']))
                {
                    $this->is_author = false;
                }
                else
                {
                    $this->is_author = true;
                }

                if('' !== $qv['author_name'])
                {
                    $this->is_author = true;
                }

                if(! empty($qv['post_type']) && ! is_array($qv['post_type']))
                {
                    $post_type_obj = get_post_type_object($qv['post_type']);
                    if(! empty($post_type_obj->has_archive))
                    {
                        $this->is_post_type_archive = true;
                    }
                }

                if($this->is_post_type_archive || $this->is_date || $this->is_author || $this->is_category || $this->is_tag || $this->is_tax)
                {
                    $this->is_archive = true;
                }
            }

            if('' != $qv['feed'])
            {
                $this->is_feed = true;
            }

            if('' != $qv['embed'])
            {
                $this->is_embed = true;
            }

            if('' != $qv['tb'])
            {
                $this->is_trackback = true;
            }

            if('' != $qv['paged'] && ((int) $qv['paged'] > 1))
            {
                $this->is_paged = true;
            }

            // If we're previewing inside the write screen.
            if('' != $qv['preview'])
            {
                $this->is_preview = true;
            }

            if(is_admin())
            {
                $this->is_admin = true;
            }

            if(str_contains($qv['feed'], 'comments-'))
            {
                $qv['feed'] = str_replace('comments-', '', $qv['feed']);
                $qv['withcomments'] = 1;
            }

            $this->is_singular = $this->is_single || $this->is_page || $this->is_attachment;

            if($this->is_feed && (! empty($qv['withcomments']) || (empty($qv['withoutcomments']) && $this->is_singular)))
            {
                $this->is_comment_feed = true;
            }

            if(! ($this->is_singular || $this->is_archive || $this->is_search || $this->is_feed || (defined('REST_REQUEST') && REST_REQUEST && $this->is_main_query()) || $this->is_trackback || $this->is_404 || $this->is_admin || $this->is_robots || $this->is_favicon))
            {
                $this->is_home = true;
            }

            // Correct `is_*` for 'page_on_front' and 'page_for_posts'.
            if($this->is_home && 'page' === get_option('show_on_front') && get_option('page_on_front'))
            {
                $_query = wp_parse_args($this->query);
                // 'pagename' can be set and empty depending on matched rewrite rules. Ignore an empty 'pagename'.
                if(isset($_query['pagename']) && '' === $_query['pagename'])
                {
                    unset($_query['pagename']);
                }

                unset($_query['embed']);

                if(empty($_query) || ! array_diff(array_keys($_query), ['preview', 'page', 'paged', 'cpage']))
                {
                    $this->is_page = true;
                    $this->is_home = false;
                    $qv['page_id'] = get_option('page_on_front');
                    // Correct <!--nextpage--> for 'page_on_front'.
                    if(! empty($qv['paged']))
                    {
                        $qv['page'] = $qv['paged'];
                        unset($qv['paged']);
                    }
                }
            }

            if('' !== $qv['pagename'])
            {
                $this->queried_object = get_page_by_path($qv['pagename']);

                if($this->queried_object && 'attachment' === $this->queried_object->post_type)
                {
                    if(preg_match('/^[^%]*%(?:postname)%/', get_option('permalink_structure')))
                    {
                        // See if we also have a post with the same slug.
                        $post = get_page_by_path($qv['pagename'], OBJECT, 'post');
                        if($post)
                        {
                            $this->queried_object = $post;
                            $this->is_page = false;
                            $this->is_single = true;
                        }
                    }
                }

                if(! empty($this->queried_object))
                {
                    $this->queried_object_id = (int) $this->queried_object->ID;
                }
                else
                {
                    unset($this->queried_object);
                }

                if('page' === get_option('show_on_front') && isset($this->queried_object_id) && get_option('page_for_posts') == $this->queried_object_id)
                {
                    $this->is_page = false;
                    $this->is_home = true;
                    $this->is_posts_page = true;
                }

                if(isset($this->queried_object_id) && get_option('wp_page_for_privacy_policy') == $this->queried_object_id)
                {
                    $this->is_privacy_policy = true;
                }
            }

            if($qv['page_id'])
            {
                if('page' === get_option('show_on_front') && get_option('page_for_posts') == $qv['page_id'])
                {
                    $this->is_page = false;
                    $this->is_home = true;
                    $this->is_posts_page = true;
                }

                if(get_option('wp_page_for_privacy_policy') == $qv['page_id'])
                {
                    $this->is_privacy_policy = true;
                }
            }

            if(! empty($qv['post_type']))
            {
                if(is_array($qv['post_type']))
                {
                    $qv['post_type'] = array_map('sanitize_key', $qv['post_type']);
                }
                else
                {
                    $qv['post_type'] = sanitize_key($qv['post_type']);
                }
            }

            if(! empty($qv['post_status']))
            {
                if(is_array($qv['post_status']))
                {
                    $qv['post_status'] = array_map('sanitize_key', $qv['post_status']);
                }
                else
                {
                    $qv['post_status'] = preg_replace('|[^a-z0-9_,-]|', '', $qv['post_status']);
                }
            }

            if($this->is_posts_page && (! isset($qv['withcomments']) || ! $qv['withcomments']))
            {
                $this->is_comment_feed = false;
            }

            $this->is_singular = $this->is_single || $this->is_page || $this->is_attachment;
            // Done correcting `is_*` for 'page_on_front' and 'page_for_posts'.

            if('404' == $qv['error'])
            {
                $this->set_404();
            }

            $this->is_embed = $this->is_embed && ($this->is_singular || $this->is_404);

            $this->query_vars_hash = md5(serialize($this->query_vars));
            $this->query_vars_changed = false;

            do_action_ref_array('parse_query', [&$this]);
        }

        public function fill_query_vars($query_vars)
        {
            $keys = [
                'error',
                'm',
                'p',
                'post_parent',
                'subpost',
                'subpost_id',
                'attachment',
                'attachment_id',
                'name',
                'pagename',
                'page_id',
                'second',
                'minute',
                'hour',
                'day',
                'monthnum',
                'year',
                'w',
                'category_name',
                'tag',
                'cat',
                'tag_id',
                'author',
                'author_name',
                'feed',
                'tb',
                'paged',
                'meta_key',
                'meta_value',
                'preview',
                's',
                'sentence',
                'title',
                'fields',
                'menu_order',
                'embed',
            ];

            foreach($keys as $key)
            {
                if(! isset($query_vars[$key]))
                {
                    $query_vars[$key] = '';
                }
            }

            $array_keys = [
                'category__in',
                'category__not_in',
                'category__and',
                'post__in',
                'post__not_in',
                'post_name__in',
                'tag__in',
                'tag__not_in',
                'tag__and',
                'tag_slug__in',
                'tag_slug__and',
                'post_parent__in',
                'post_parent__not_in',
                'author__in',
                'author__not_in',
                'search_columns',
            ];

            foreach($array_keys as $key)
            {
                if(! isset($query_vars[$key]))
                {
                    $query_vars[$key] = [];
                }
            }

            return $query_vars;
        }

        public function parse_tax_query(&$q)
        {
            if(! empty($q['tax_query']) && is_array($q['tax_query']))
            {
                $tax_query = $q['tax_query'];
            }
            else
            {
                $tax_query = [];
            }

            if(! empty($q['taxonomy']) && ! empty($q['term']))
            {
                $tax_query[] = [
                    'taxonomy' => $q['taxonomy'],
                    'terms' => [$q['term']],
                    'field' => 'slug',
                ];
            }

            foreach(get_taxonomies([], 'objects') as $taxonomy => $t)
            {
                if('post_tag' === $taxonomy)
                {
                    continue; // Handled further down in the $q['tag'] block.
                }

                if($t->query_var && ! empty($q[$t->query_var]))
                {
                    $tax_query_defaults = [
                        'taxonomy' => $taxonomy,
                        'field' => 'slug',
                    ];

                    if(! empty($t->rewrite['hierarchical']))
                    {
                        $q[$t->query_var] = wp_basename($q[$t->query_var]);
                    }

                    $term = $q[$t->query_var];

                    if(is_array($term))
                    {
                        $term = implode(',', $term);
                    }

                    if(str_contains($term, '+'))
                    {
                        $terms = preg_split('/[+]+/', $term);
                        foreach($terms as $term)
                        {
                            $tax_query[] = array_merge($tax_query_defaults, [
                                'terms' => [$term],
                            ]);
                        }
                    }
                    else
                    {
                        $tax_query[] = array_merge($tax_query_defaults, [
                            'terms' => preg_split('/[,]+/', $term),
                        ]);
                    }
                }
            }

            // If query string 'cat' is an array, implode it.
            if(is_array($q['cat']))
            {
                $q['cat'] = implode(',', $q['cat']);
            }

            // Category stuff.

            if(! empty($q['cat']) && ! $this->is_singular)
            {
                $cat_in = [];
                $cat_not_in = [];

                $cat_array = preg_split('/[,\s]+/', urldecode($q['cat']));
                $cat_array = array_map('intval', $cat_array);
                $q['cat'] = implode(',', $cat_array);

                foreach($cat_array as $cat)
                {
                    if($cat > 0)
                    {
                        $cat_in[] = $cat;
                    }
                    elseif($cat < 0)
                    {
                        $cat_not_in[] = abs($cat);
                    }
                }

                if(! empty($cat_in))
                {
                    $tax_query[] = [
                        'taxonomy' => 'category',
                        'terms' => $cat_in,
                        'field' => 'term_id',
                        'include_children' => true,
                    ];
                }

                if(! empty($cat_not_in))
                {
                    $tax_query[] = [
                        'taxonomy' => 'category',
                        'terms' => $cat_not_in,
                        'field' => 'term_id',
                        'operator' => 'NOT IN',
                        'include_children' => true,
                    ];
                }
                unset($cat_array, $cat_in, $cat_not_in);
            }

            if(! empty($q['category__and']) && 1 === count((array) $q['category__and']))
            {
                $q['category__and'] = (array) $q['category__and'];
                if(! isset($q['category__in']))
                {
                    $q['category__in'] = [];
                }
                $q['category__in'][] = absint(reset($q['category__and']));
                unset($q['category__and']);
            }

            if(! empty($q['category__in']))
            {
                $q['category__in'] = array_map('absint', array_unique((array) $q['category__in']));
                $tax_query[] = [
                    'taxonomy' => 'category',
                    'terms' => $q['category__in'],
                    'field' => 'term_id',
                    'include_children' => false,
                ];
            }

            if(! empty($q['category__not_in']))
            {
                $q['category__not_in'] = array_map('absint', array_unique((array) $q['category__not_in']));
                $tax_query[] = [
                    'taxonomy' => 'category',
                    'terms' => $q['category__not_in'],
                    'operator' => 'NOT IN',
                    'include_children' => false,
                ];
            }

            if(! empty($q['category__and']))
            {
                $q['category__and'] = array_map('absint', array_unique((array) $q['category__and']));
                $tax_query[] = [
                    'taxonomy' => 'category',
                    'terms' => $q['category__and'],
                    'field' => 'term_id',
                    'operator' => 'AND',
                    'include_children' => false,
                ];
            }

            // If query string 'tag' is array, implode it.
            if(is_array($q['tag']))
            {
                $q['tag'] = implode(',', $q['tag']);
            }

            // Tag stuff.

            if('' !== $q['tag'] && ! $this->is_singular && $this->query_vars_changed)
            {
                if(str_contains($q['tag'], ','))
                {
                    $tags = preg_split('/[,\r\n\t ]+/', $q['tag']);
                    foreach((array) $tags as $tag)
                    {
                        $tag = sanitize_term_field('slug', $tag, 0, 'post_tag', 'db');
                        $q['tag_slug__in'][] = $tag;
                    }
                }
                elseif(preg_match('/[+\r\n\t ]+/', $q['tag']) || ! empty($q['cat']))
                {
                    $tags = preg_split('/[+\r\n\t ]+/', $q['tag']);
                    foreach((array) $tags as $tag)
                    {
                        $tag = sanitize_term_field('slug', $tag, 0, 'post_tag', 'db');
                        $q['tag_slug__and'][] = $tag;
                    }
                }
                else
                {
                    $q['tag'] = sanitize_term_field('slug', $q['tag'], 0, 'post_tag', 'db');
                    $q['tag_slug__in'][] = $q['tag'];
                }
            }

            if(! empty($q['tag_id']))
            {
                $q['tag_id'] = absint($q['tag_id']);
                $tax_query[] = [
                    'taxonomy' => 'post_tag',
                    'terms' => $q['tag_id'],
                ];
            }

            if(! empty($q['tag__in']))
            {
                $q['tag__in'] = array_map('absint', array_unique((array) $q['tag__in']));
                $tax_query[] = [
                    'taxonomy' => 'post_tag',
                    'terms' => $q['tag__in'],
                ];
            }

            if(! empty($q['tag__not_in']))
            {
                $q['tag__not_in'] = array_map('absint', array_unique((array) $q['tag__not_in']));
                $tax_query[] = [
                    'taxonomy' => 'post_tag',
                    'terms' => $q['tag__not_in'],
                    'operator' => 'NOT IN',
                ];
            }

            if(! empty($q['tag__and']))
            {
                $q['tag__and'] = array_map('absint', array_unique((array) $q['tag__and']));
                $tax_query[] = [
                    'taxonomy' => 'post_tag',
                    'terms' => $q['tag__and'],
                    'operator' => 'AND',
                ];
            }

            if(! empty($q['tag_slug__in']))
            {
                $q['tag_slug__in'] = array_map('sanitize_title_for_query', array_unique((array) $q['tag_slug__in']));
                $tax_query[] = [
                    'taxonomy' => 'post_tag',
                    'terms' => $q['tag_slug__in'],
                    'field' => 'slug',
                ];
            }

            if(! empty($q['tag_slug__and']))
            {
                $q['tag_slug__and'] = array_map('sanitize_title_for_query', array_unique((array) $q['tag_slug__and']));
                $tax_query[] = [
                    'taxonomy' => 'post_tag',
                    'terms' => $q['tag_slug__and'],
                    'field' => 'slug',
                    'operator' => 'AND',
                ];
            }

            $this->tax_query = new WP_Tax_Query($tax_query);

            do_action('parse_tax_query', $this);
        }

        public function is_main_query()
        {
            global $wp_the_query;

            return $wp_the_query === $this;
        }

        public function set_404()
        {
            $is_feed = $this->is_feed;

            $this->init_query_flags();
            $this->is_404 = true;

            $this->is_feed = $is_feed;

            do_action_ref_array('set_404', [$this]);
        }

        public function parse_query_vars()
        {
            $this->parse_query();
        }

        protected function parse_search(&$q)
        {
            global $wpdb;

            $search = '';

            // Added slashes screw with quote grouping when done early, so done later.
            $q['s'] = stripslashes($q['s']);
            if(empty($_GET['s']) && $this->is_main_query())
            {
                $q['s'] = urldecode($q['s']);
            }
            // There are no line breaks in <input /> fields.
            $q['s'] = str_replace(["\r", "\n"], '', $q['s']);
            $q['search_terms_count'] = 1;
            if(! empty($q['sentence']))
            {
                $q['search_terms'] = [$q['s']];
            }
            else
            {
                if(preg_match_all('/".*?("|$)|((?<=[\t ",+])|^)[^\t ",+]+/', $q['s'], $matches))
                {
                    $q['search_terms_count'] = count($matches[0]);
                    $q['search_terms'] = $this->parse_search_terms($matches[0]);
                    // If the search string has only short terms or stopwords, or is 10+ terms long, match it as sentence.
                    if(empty($q['search_terms']) || count($q['search_terms']) > 9)
                    {
                        $q['search_terms'] = [$q['s']];
                    }
                }
                else
                {
                    $q['search_terms'] = [$q['s']];
                }
            }

            $n = ! empty($q['exact']) ? '' : '%';
            $searchand = '';
            $q['search_orderby_title'] = [];

            $default_search_columns = ['post_title', 'post_excerpt', 'post_content'];
            $search_columns = ! empty($q['search_columns']) ? $q['search_columns'] : $default_search_columns;
            if(! is_array($search_columns))
            {
                $search_columns = [$search_columns];
            }

            $search_columns = (array) apply_filters('post_search_columns', $search_columns, $q['s'], $this);

            // Use only supported search columns.
            $search_columns = array_intersect($search_columns, $default_search_columns);
            if(empty($search_columns))
            {
                $search_columns = $default_search_columns;
            }

            $exclusion_prefix = apply_filters('wp_query_search_exclusion_prefix', '-');

            foreach($q['search_terms'] as $term)
            {
                // If there is an $exclusion_prefix, terms prefixed with it should be excluded.
                $exclude = $exclusion_prefix && str_starts_with($term, $exclusion_prefix);
                if($exclude)
                {
                    $like_op = 'NOT LIKE';
                    $andor_op = 'AND';
                    $term = substr($term, 1);
                }
                else
                {
                    $like_op = 'LIKE';
                    $andor_op = 'OR';
                }

                if($n && ! $exclude)
                {
                    $like = '%'.$wpdb->esc_like($term).'%';
                    $q['search_orderby_title'][] = $wpdb->prepare("{$wpdb->posts}.post_title LIKE %s", $like);
                }

                $like = $n.$wpdb->esc_like($term).$n;

                $search_columns_parts = [];
                foreach($search_columns as $search_column)
                {
                    $search_columns_parts[$search_column] = $wpdb->prepare("({$wpdb->posts}.$search_column $like_op %s)", $like);
                }

                if(! empty($this->allow_query_attachment_by_filename))
                {
                    $search_columns_parts['attachment'] = $wpdb->prepare("(sq1.meta_value $like_op %s)", $like);
                }

                $search .= "$searchand(".implode(" $andor_op ", $search_columns_parts).')';

                $searchand = ' AND ';
            }

            if(! empty($search))
            {
                $search = " AND ({$search}) ";
                if(! is_user_logged_in())
                {
                    $search .= " AND ({$wpdb->posts}.post_password = '') ";
                }
            }

            return $search;
        }

        protected function parse_search_terms($terms)
        {
            $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
            $checked = [];

            $stopwords = $this->get_search_stopwords();

            foreach($terms as $term)
            {
                // Keep before/after spaces when term is for exact match.
                if(preg_match('/^".+"$/', $term))
                {
                    $term = trim($term, "\"'");
                }
                else
                {
                    $term = trim($term, "\"' ");
                }

                // Avoid single A-Z and single dashes.
                if(! $term || (1 === strlen($term) && preg_match('/^[a-z\-]$/i', $term)))
                {
                    continue;
                }

                if(in_array(call_user_func($strtolower, $term), $stopwords, true))
                {
                    continue;
                }

                $checked[] = $term;
            }

            return $checked;
        }

        protected function get_search_stopwords()
        {
            if(isset($this->stopwords))
            {
                return $this->stopwords;
            }

            /*
		 * translators: This is a comma-separated list of very common words that should be excluded from a search,
		 * like a, an, and the. These are usually called "stopwords". You should not simply translate these individual
		 * words into your language. Instead, look for and provide commonly accepted stopwords in your language.
		 */
            $words = explode(',', _x('about,an,are,as,at,be,by,com,for,from,how,in,is,it,of,on,or,that,the,this,to,was,what,when,where,who,will,with,www', 'Comma-separated list of search stopwords in your language'));

            $stopwords = [];
            foreach($words as $word)
            {
                $word = trim($word, "\r\n\t ");
                if($word)
                {
                    $stopwords[] = $word;
                }
            }

            $this->stopwords = apply_filters('wp_search_stopwords', $stopwords);

            return $this->stopwords;
        }

        public function set($query_var, $value)
        {
            $this->query_vars[$query_var] = $value;
        }

        protected function parse_order($order)
        {
            if(! is_string($order) || empty($order))
            {
                return 'DESC';
            }

            if('ASC' === strtoupper($order))
            {
                return 'ASC';
            }
            else
            {
                return 'DESC';
            }
        }

        protected function parse_orderby($orderby)
        {
            global $wpdb;

            // Used to filter values.
            $allowed_keys = [
                'post_name',
                'post_author',
                'post_date',
                'post_title',
                'post_modified',
                'post_parent',
                'post_type',
                'name',
                'author',
                'date',
                'title',
                'modified',
                'parent',
                'type',
                'ID',
                'menu_order',
                'comment_count',
                'rand',
                'post__in',
                'post_parent__in',
                'post_name__in',
            ];

            $primary_meta_key = '';
            $primary_meta_query = false;
            $meta_clauses = $this->meta_query->get_clauses();
            if(! empty($meta_clauses))
            {
                $primary_meta_query = reset($meta_clauses);

                if(! empty($primary_meta_query['key']))
                {
                    $primary_meta_key = $primary_meta_query['key'];
                    $allowed_keys[] = $primary_meta_key;
                }

                $allowed_keys[] = 'meta_value';
                $allowed_keys[] = 'meta_value_num';
                $allowed_keys = array_merge($allowed_keys, array_keys($meta_clauses));
            }

            // If RAND() contains a seed value, sanitize and add to allowed keys.
            $rand_with_seed = false;
            if(preg_match('/RAND\(([0-9]+)\)/i', $orderby, $matches))
            {
                $orderby = sprintf('RAND(%s)', (int) $matches[1]);
                $allowed_keys[] = $orderby;
                $rand_with_seed = true;
            }

            if(! in_array($orderby, $allowed_keys, true))
            {
                return false;
            }

            $orderby_clause = '';

            switch($orderby)
            {
                case 'post_name':
                case 'post_author':
                case 'post_date':
                case 'post_title':
                case 'post_modified':
                case 'post_parent':
                case 'post_type':
                case 'ID':
                case 'menu_order':
                case 'comment_count':
                    $orderby_clause = "{$wpdb->posts}.{$orderby}";
                    break;
                case 'rand':
                    $orderby_clause = 'RAND()';
                    break;
                case $primary_meta_key:
                case 'meta_value':
                    if(! empty($primary_meta_query['type']))
                    {
                        $orderby_clause = "CAST({$primary_meta_query['alias']}.meta_value AS {$primary_meta_query['cast']})";
                    }
                    else
                    {
                        $orderby_clause = "{$primary_meta_query['alias']}.meta_value";
                    }
                    break;
                case 'meta_value_num':
                    $orderby_clause = "{$primary_meta_query['alias']}.meta_value+0";
                    break;
                case 'post__in':
                    if(! empty($this->query_vars['post__in']))
                    {
                        $orderby_clause = "FIELD({$wpdb->posts}.ID,".implode(',', array_map('absint', $this->query_vars['post__in'])).')';
                    }
                    break;
                case 'post_parent__in':
                    if(! empty($this->query_vars['post_parent__in']))
                    {
                        $orderby_clause = "FIELD( {$wpdb->posts}.post_parent,".implode(', ', array_map('absint', $this->query_vars['post_parent__in'])).' )';
                    }
                    break;
                case 'post_name__in':
                    if(! empty($this->query_vars['post_name__in']))
                    {
                        $post_name__in = array_map('sanitize_title_for_query', $this->query_vars['post_name__in']);
                        $post_name__in_string = "'".implode("','", $post_name__in)."'";
                        $orderby_clause = "FIELD( {$wpdb->posts}.post_name,".$post_name__in_string.' )';
                    }
                    break;
                default:
                    if(array_key_exists($orderby, $meta_clauses))
                    {
                        // $orderby corresponds to a meta_query clause.
                        $meta_clause = $meta_clauses[$orderby];
                        $orderby_clause = "CAST({$meta_clause['alias']}.meta_value AS {$meta_clause['cast']})";
                    }
                    elseif($rand_with_seed)
                    {
                        $orderby_clause = $orderby;
                    }
                    else
                    {
                        // Default: order by post field.
                        $orderby_clause = "{$wpdb->posts}.post_".sanitize_key($orderby);
                    }

                    break;
            }

            return $orderby_clause;
        }

        protected function parse_search_order(&$q)
        {
            global $wpdb;

            if($q['search_terms_count'] > 1)
            {
                $num_terms = count($q['search_orderby_title']);

                // If the search terms contain negative queries, don't bother ordering by sentence matches.
                $like = '';
                if(! preg_match('/(?:\s|^)\-/', $q['s']))
                {
                    $like = '%'.$wpdb->esc_like($q['s']).'%';
                }

                $search_orderby = '';

                // Sentence match in 'post_title'.
                if($like)
                {
                    $search_orderby .= $wpdb->prepare("WHEN {$wpdb->posts}.post_title LIKE %s THEN 1 ", $like);
                }

                /*
			 * Sanity limit, sort as sentence when more than 6 terms
			 * (few searches are longer than 6 terms and most titles are not).
			 */
                if($num_terms < 7)
                {
                    // All words in title.
                    $search_orderby .= 'WHEN '.implode(' AND ', $q['search_orderby_title']).' THEN 2 ';
                    // Any word in title, not needed when $num_terms == 1.
                    if($num_terms > 1)
                    {
                        $search_orderby .= 'WHEN '.implode(' OR ', $q['search_orderby_title']).' THEN 3 ';
                    }
                }

                // Sentence match in 'post_content' and 'post_excerpt'.
                if($like)
                {
                    $search_orderby .= $wpdb->prepare("WHEN {$wpdb->posts}.post_excerpt LIKE %s THEN 4 ", $like);
                    $search_orderby .= $wpdb->prepare("WHEN {$wpdb->posts}.post_content LIKE %s THEN 5 ", $like);
                }

                if($search_orderby)
                {
                    $search_orderby = '(CASE '.$search_orderby.'ELSE 6 END)';
                }
            }
            else
            {
                // Single word or sentence search.
                $search_orderby = reset($q['search_orderby_title']).' DESC';
            }

            return $search_orderby;
        }

        protected function generate_cache_key(array $args, $sql)
        {
            global $wpdb;

            unset($args['cache_results'], $args['fields'], $args['lazy_load_term_meta'], $args['update_post_meta_cache'], $args['update_post_term_cache'], $args['update_menu_item_cache'], $args['suppress_filters']);

            $placeholder = $wpdb->placeholder_escape();
            array_walk_recursive(
                $args, /*
			 * Replace wpdb placeholders with the string used in the database
			 * query to avoid unreachable cache keys. This is necessary because
			 * the placeholder is randomly generated in each request.
			 *
			 * $value is passed by reference to allow it to be modified.
			 * array_walk_recursive() does not return an array.
			 */ static function(&$value) use ($wpdb, $placeholder)
            {
                if(is_string($value) && str_contains($value, $placeholder))
                {
                    $value = $wpdb->remove_placeholder_escape($value);
                }
            }
            );

            // Replace wpdb placeholder in the SQL statement used by the cache key.
            $sql = $wpdb->remove_placeholder_escape($sql);
            $key = md5(serialize($args).$sql);

            $last_changed = wp_cache_get_last_changed('posts');
            if(! empty($this->tax_query->queries))
            {
                $last_changed .= wp_cache_get_last_changed('terms');
            }

            return "wp_query:$key:$last_changed";
        }

        private function set_found_posts($q, $limits)
        {
            global $wpdb;

            /*
		 * Bail if posts is an empty array. Continue if posts is an empty string,
		 * null, or false to accommodate caching plugins that fill posts later.
		 */
            if($q['no_found_rows'] || (is_array($this->posts) && ! $this->posts))
            {
                return;
            }

            if(! empty($limits))
            {
                $found_posts_query = apply_filters_ref_array('found_posts_query', ['SELECT FOUND_ROWS()', &$this]);

                $this->found_posts = (int) $wpdb->get_var($found_posts_query);
            }
            else
            {
                if(is_array($this->posts))
                {
                    $this->found_posts = count($this->posts);
                }
                else
                {
                    if(null === $this->posts)
                    {
                        $this->found_posts = 0;
                    }
                    else
                    {
                        $this->found_posts = 1;
                    }
                }
            }

            $this->found_posts = (int) apply_filters_ref_array('found_posts', [$this->found_posts, &$this]);

            if(! empty($limits))
            {
                $this->max_num_pages = ceil($this->found_posts / $q['posts_per_page']);
            }
        }

        public function the_post()
        {
            global $post;

            if(! $this->in_the_loop)
            {
                // Only prime the post cache for queries limited to the ID field.
                $post_ids = array_filter($this->posts, 'is_numeric');
                // Exclude any falsey values, such as 0.
                $post_ids = array_filter($post_ids);
                if($post_ids)
                {
                    _prime_post_caches($post_ids, $this->query_vars['update_post_term_cache'], $this->query_vars['update_post_meta_cache']);
                }
                $post_objects = array_map('get_post', $this->posts);
                update_post_author_caches($post_objects);
            }

            $this->in_the_loop = true;
            $this->before_loop = false;

            if(-1 == $this->current_post)
            { // Loop has just started.

                do_action_ref_array('loop_start', [&$this]);
            }

            $post = $this->next_post();
            $this->setup_postdata($post);
        }

        public function next_post()
        {
            ++$this->current_post;

            $this->post = $this->posts[$this->current_post];

            return $this->post;
        }

        public function setup_postdata($post)
        {
            global $id, $authordata, $currentday, $currentmonth, $page, $pages, $multipage, $more, $numpages;

            if(! ($post instanceof WP_Post))
            {
                $post = get_post($post);
            }

            if(! $post)
            {
                return;
            }

            $elements = $this->generate_postdata($post);
            if(false === $elements)
            {
                return;
            }

            $id = $elements['id'];
            $authordata = $elements['authordata'];
            $currentday = $elements['currentday'];
            $currentmonth = $elements['currentmonth'];
            $page = $elements['page'];
            $pages = $elements['pages'];
            $multipage = $elements['multipage'];
            $more = $elements['more'];
            $numpages = $elements['numpages'];

            do_action_ref_array('the_post', [&$post, &$this]);

            return true;
        }

        public function generate_postdata($post)
        {
            if(! ($post instanceof WP_Post))
            {
                $post = get_post($post);
            }

            if(! $post)
            {
                return false;
            }

            $id = (int) $post->ID;

            $authordata = get_userdata($post->post_author);

            $currentday = false;
            $currentmonth = false;

            $post_date = $post->post_date;
            if(! empty($post_date) && '0000-00-00 00:00:00' !== $post_date)
            {
                // Avoid using mysql2date for performance reasons.
                $currentmonth = substr($post_date, 5, 2);
                $day = substr($post_date, 8, 2);
                $year = substr($post_date, 2, 2);

                $currentday = sprintf('%s.%s.%s', $day, $currentmonth, $year);
            }

            $numpages = 1;
            $multipage = 0;
            $page = $this->get('page');
            if(! $page)
            {
                $page = 1;
            }

            /*
		 * Force full post content when viewing the permalink for the $post,
		 * or when on an RSS feed. Otherwise respect the 'more' tag.
		 */
            if(get_queried_object_id() === $post->ID && ($this->is_page() || $this->is_single()))
            {
                $more = 1;
            }
            elseif($this->is_feed())
            {
                $more = 1;
            }
            else
            {
                $more = 0;
            }

            $content = $post->post_content;
            if(str_contains($content, '<!--nextpage-->'))
            {
                $content = str_replace("\n<!--nextpage-->\n", '<!--nextpage-->', $content);
                $content = str_replace("\n<!--nextpage-->", '<!--nextpage-->', $content);
                $content = str_replace("<!--nextpage-->\n", '<!--nextpage-->', $content);

                // Remove the nextpage block delimiters, to avoid invalid block structures in the split content.
                $content = str_replace('<!-- wp:nextpage -->', '', $content);
                $content = str_replace('<!-- /wp:nextpage -->', '', $content);

                // Ignore nextpage at the beginning of the content.
                if(str_starts_with($content, '<!--nextpage-->'))
                {
                    $content = substr($content, 15);
                }

                $pages = explode('<!--nextpage-->', $content);
            }
            else
            {
                $pages = [$post->post_content];
            }

            $pages = apply_filters('content_pagination', $pages, $post);

            $numpages = count($pages);

            if($numpages > 1)
            {
                if($page > 1)
                {
                    $more = 1;
                }
                $multipage = 1;
            }
            else
            {
                $multipage = 0;
            }

            $elements = compact('id', 'authordata', 'currentday', 'currentmonth', 'page', 'pages', 'multipage', 'more', 'numpages');

            return $elements;
        }

        public function get($query_var, $default_value = '')
        {
            if(isset($this->query_vars[$query_var]))
            {
                return $this->query_vars[$query_var];
            }

            return $default_value;
        }

        public function is_page($page = '')
        {
            if(! $this->is_page)
            {
                return false;
            }

            if(empty($page))
            {
                return true;
            }

            $page_obj = $this->get_queried_object();
            if(! $page_obj)
            {
                return false;
            }

            $page = array_map('strval', (array) $page);

            if(in_array((string) $page_obj->ID, $page, true))
            {
                return true;
            }
            elseif(in_array($page_obj->post_title, $page, true))
            {
                return true;
            }
            elseif(in_array($page_obj->post_name, $page, true))
            {
                return true;
            }
            else
            {
                foreach($page as $pagepath)
                {
                    if(! strpos($pagepath, '/'))
                    {
                        continue;
                    }
                    $pagepath_obj = get_page_by_path($pagepath);

                    if($pagepath_obj && ($pagepath_obj->ID == $page_obj->ID))
                    {
                        return true;
                    }
                }
            }

            return false;
        }

        public function get_queried_object()
        {
            if(isset($this->queried_object))
            {
                return $this->queried_object;
            }

            $this->queried_object = null;
            $this->queried_object_id = null;

            if($this->is_category || $this->is_tag || $this->is_tax)
            {
                if($this->is_category)
                {
                    $cat = $this->get('cat');
                    $category_name = $this->get('category_name');

                    if($cat)
                    {
                        $term = get_term($cat, 'category');
                    }
                    elseif($category_name)
                    {
                        $term = get_term_by('slug', $category_name, 'category');
                    }
                }
                elseif($this->is_tag)
                {
                    $tag_id = $this->get('tag_id');
                    $tag = $this->get('tag');

                    if($tag_id)
                    {
                        $term = get_term($tag_id, 'post_tag');
                    }
                    elseif($tag)
                    {
                        $term = get_term_by('slug', $tag, 'post_tag');
                    }
                }
                else
                {
                    // For other tax queries, grab the first term from the first clause.
                    if(! empty($this->tax_query->queried_terms))
                    {
                        $queried_taxonomies = array_keys($this->tax_query->queried_terms);
                        $matched_taxonomy = reset($queried_taxonomies);
                        $query = $this->tax_query->queried_terms[$matched_taxonomy];

                        if(! empty($query['terms']))
                        {
                            if('term_id' === $query['field'])
                            {
                                $term = get_term(reset($query['terms']), $matched_taxonomy);
                            }
                            else
                            {
                                $term = get_term_by($query['field'], reset($query['terms']), $matched_taxonomy);
                            }
                        }
                    }
                }

                if(! empty($term) && ! is_wp_error($term))
                {
                    $this->queried_object = $term;
                    $this->queried_object_id = (int) $term->term_id;

                    if($this->is_category && 'category' === $this->queried_object->taxonomy)
                    {
                        _make_cat_compat($this->queried_object);
                    }
                }
            }
            elseif($this->is_post_type_archive)
            {
                $post_type = $this->get('post_type');

                if(is_array($post_type))
                {
                    $post_type = reset($post_type);
                }

                $this->queried_object = get_post_type_object($post_type);
            }
            elseif($this->is_posts_page)
            {
                $page_for_posts = get_option('page_for_posts');

                $this->queried_object = get_post($page_for_posts);
                $this->queried_object_id = (int) $this->queried_object->ID;
            }
            elseif($this->is_singular && ! empty($this->post))
            {
                $this->queried_object = $this->post;
                $this->queried_object_id = (int) $this->post->ID;
            }
            elseif($this->is_author)
            {
                $author = (int) $this->get('author');
                $author_name = $this->get('author_name');

                if($author)
                {
                    $this->queried_object_id = $author;
                }
                elseif($author_name)
                {
                    $user = get_user_by('slug', $author_name);

                    if($user)
                    {
                        $this->queried_object_id = $user->ID;
                    }
                }

                $this->queried_object = get_userdata($this->queried_object_id);
            }

            return $this->queried_object;
        }

        public function is_single($post = '')
        {
            if(! $this->is_single)
            {
                return false;
            }

            if(empty($post))
            {
                return true;
            }

            $post_obj = $this->get_queried_object();
            if(! $post_obj)
            {
                return false;
            }

            $post = array_map('strval', (array) $post);

            if(in_array((string) $post_obj->ID, $post, true))
            {
                return true;
            }
            elseif(in_array($post_obj->post_title, $post, true))
            {
                return true;
            }
            elseif(in_array($post_obj->post_name, $post, true))
            {
                return true;
            }
            else
            {
                foreach($post as $postpath)
                {
                    if(! strpos($postpath, '/'))
                    {
                        continue;
                    }
                    $postpath_obj = get_page_by_path($postpath, OBJECT, $post_obj->post_type);

                    if($postpath_obj && ($postpath_obj->ID == $post_obj->ID))
                    {
                        return true;
                    }
                }
            }

            return false;
        }

        public function is_feed($feeds = '')
        {
            if(empty($feeds) || ! $this->is_feed)
            {
                return (bool) $this->is_feed;
            }

            $qv = $this->get('feed');
            if('feed' === $qv)
            {
                $qv = get_default_feed();
            }

            return in_array($qv, (array) $feeds, true);
        }

        public function have_posts()
        {
            if($this->current_post + 1 < $this->post_count)
            {
                return true;
            }
            elseif($this->current_post + 1 == $this->post_count && $this->post_count > 0)
            {
                do_action_ref_array('loop_end', [&$this]);
                // Do some cleaning up after the loop.
                $this->rewind_posts();
            }
            elseif(0 === $this->post_count)
            {
                $this->before_loop = false;

                do_action('loop_no_results', $this);
            }

            $this->in_the_loop = false;

            return false;
        }

        public function rewind_posts()
        {
            $this->current_post = -1;
            if($this->post_count > 0)
            {
                $this->post = $this->posts[0];
            }
        }

        public function the_comment()
        {
            global $comment;

            $comment = $this->next_comment();

            if(0 == $this->current_comment)
            {
                do_action('comment_loop_start');
            }
        }

        public function next_comment()
        {
            ++$this->current_comment;

            $this->comment = $this->comments[$this->current_comment];

            return $this->comment;
        }

        public function have_comments()
        {
            if($this->current_comment + 1 < $this->comment_count)
            {
                return true;
            }
            elseif($this->current_comment + 1 == $this->comment_count)
            {
                $this->rewind_comments();
            }

            return false;
        }

        public function rewind_comments()
        {
            $this->current_comment = -1;
            if($this->comment_count > 0)
            {
                $this->comment = $this->comments[0];
            }
        }

        public function get_queried_object_id()
        {
            $this->get_queried_object();

            if(isset($this->queried_object_id))
            {
                return $this->queried_object_id;
            }

            return 0;
        }

        public function __get($name)
        {
            if(in_array($name, $this->compat_fields, true))
            {
                return $this->$name;
            }
        }

        public function __isset($name)
        {
            if(in_array($name, $this->compat_fields, true))
            {
                return isset($this->$name);
            }
        }

        public function __call($name, $arguments)
        {
            if(in_array($name, $this->compat_methods, true))
            {
                return $this->$name(...$arguments);
            }

            return false;
        }

        public function is_archive()
        {
            return (bool) $this->is_archive;
        }

        public function is_post_type_archive($post_types = '')
        {
            if(empty($post_types) || ! $this->is_post_type_archive)
            {
                return (bool) $this->is_post_type_archive;
            }

            $post_type = $this->get('post_type');
            if(is_array($post_type))
            {
                $post_type = reset($post_type);
            }
            $post_type_object = get_post_type_object($post_type);

            if(! $post_type_object)
            {
                return false;
            }

            return in_array($post_type_object->name, (array) $post_types, true);
        }

        public function is_attachment($attachment = '')
        {
            if(! $this->is_attachment)
            {
                return false;
            }

            if(empty($attachment))
            {
                return true;
            }

            $attachment = array_map('strval', (array) $attachment);

            $post_obj = $this->get_queried_object();
            if(! $post_obj)
            {
                return false;
            }

            if(in_array((string) $post_obj->ID, $attachment, true))
            {
                return true;
            }
            elseif(in_array($post_obj->post_title, $attachment, true))
            {
                return true;
            }
            elseif(in_array($post_obj->post_name, $attachment, true))
            {
                return true;
            }

            return false;
        }

        public function is_author($author = '')
        {
            if(! $this->is_author)
            {
                return false;
            }

            if(empty($author))
            {
                return true;
            }

            $author_obj = $this->get_queried_object();
            if(! $author_obj)
            {
                return false;
            }

            $author = array_map('strval', (array) $author);

            if(in_array((string) $author_obj->ID, $author, true))
            {
                return true;
            }
            elseif(in_array($author_obj->nickname, $author, true))
            {
                return true;
            }
            elseif(in_array($author_obj->user_nicename, $author, true))
            {
                return true;
            }

            return false;
        }

        public function is_category($category = '')
        {
            if(! $this->is_category)
            {
                return false;
            }

            if(empty($category))
            {
                return true;
            }

            $cat_obj = $this->get_queried_object();
            if(! $cat_obj)
            {
                return false;
            }

            $category = array_map('strval', (array) $category);

            if(in_array((string) $cat_obj->term_id, $category, true))
            {
                return true;
            }
            elseif(in_array($cat_obj->name, $category, true))
            {
                return true;
            }
            elseif(in_array($cat_obj->slug, $category, true))
            {
                return true;
            }

            return false;
        }

        public function is_tag($tag = '')
        {
            if(! $this->is_tag)
            {
                return false;
            }

            if(empty($tag))
            {
                return true;
            }

            $tag_obj = $this->get_queried_object();
            if(! $tag_obj)
            {
                return false;
            }

            $tag = array_map('strval', (array) $tag);

            if(in_array((string) $tag_obj->term_id, $tag, true))
            {
                return true;
            }
            elseif(in_array($tag_obj->name, $tag, true))
            {
                return true;
            }
            elseif(in_array($tag_obj->slug, $tag, true))
            {
                return true;
            }

            return false;
        }

        public function is_tax($taxonomy = '', $term = '')
        {
            global $wp_taxonomies;

            if(! $this->is_tax)
            {
                return false;
            }

            if(empty($taxonomy))
            {
                return true;
            }

            $queried_object = $this->get_queried_object();
            $tax_array = array_intersect(array_keys($wp_taxonomies), (array) $taxonomy);
            $term_array = (array) $term;

            // Check that the taxonomy matches.
            if(! (isset($queried_object->taxonomy) && count($tax_array) && in_array($queried_object->taxonomy, $tax_array, true)))
            {
                return false;
            }

            // Only a taxonomy provided.
            if(empty($term))
            {
                return true;
            }

            return isset($queried_object->term_id) && count(
                    array_intersect([
                                        $queried_object->term_id,
                                        $queried_object->name,
                                        $queried_object->slug
                                    ], $term_array)
                );
        }

        public function is_comments_popup()
        {
            _deprecated_function(__FUNCTION__, '4.5.0');

            return false;
        }

        public function is_date()
        {
            return (bool) $this->is_date;
        }

        public function is_day()
        {
            return (bool) $this->is_day;
        }

        public function is_comment_feed()
        {
            return (bool) $this->is_comment_feed;
        }

        public function is_front_page()
        {
            // Most likely case.
            if('posts' === get_option('show_on_front') && $this->is_home())
            {
                return true;
            }
            elseif('page' === get_option('show_on_front') && get_option('page_on_front') && $this->is_page(get_option('page_on_front')))
            {
                return true;
            }
            else
            {
                return false;
            }
        }

        public function is_home()
        {
            return (bool) $this->is_home;
        }

        public function is_privacy_policy()
        {
            if(get_option('wp_page_for_privacy_policy') && $this->is_page(get_option('wp_page_for_privacy_policy')))
            {
                return true;
            }
            else
            {
                return false;
            }
        }

        public function is_month()
        {
            return (bool) $this->is_month;
        }

        public function is_paged()
        {
            return (bool) $this->is_paged;
        }

        public function is_preview()
        {
            return (bool) $this->is_preview;
        }

        public function is_robots()
        {
            return (bool) $this->is_robots;
        }

        public function is_favicon()
        {
            return (bool) $this->is_favicon;
        }

        public function is_search()
        {
            return (bool) $this->is_search;
        }

        public function is_singular($post_types = '')
        {
            if(empty($post_types) || ! $this->is_singular)
            {
                return (bool) $this->is_singular;
            }

            $post_obj = $this->get_queried_object();
            if(! $post_obj)
            {
                return false;
            }

            return in_array($post_obj->post_type, (array) $post_types, true);
        }

        public function is_time()
        {
            return (bool) $this->is_time;
        }

        public function is_trackback()
        {
            return (bool) $this->is_trackback;
        }

        public function is_year()
        {
            return (bool) $this->is_year;
        }

        public function is_404()
        {
            return (bool) $this->is_404;
        }

        public function is_embed()
        {
            return (bool) $this->is_embed;
        }

        public function reset_postdata()
        {
            if(! empty($this->post))
            {
                $GLOBALS['post'] = $this->post;
                $this->setup_postdata($this->post);
            }
        }

        public function lazyload_term_meta($check, $term_id)
        {
            _deprecated_function(__METHOD__, '4.5.0');

            return $check;
        }

        public function lazyload_comment_meta($check, $comment_id)
        {
            _deprecated_function(__METHOD__, '4.5.0');

            return $check;
        }
    }
