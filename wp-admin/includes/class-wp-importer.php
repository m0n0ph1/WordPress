<?php

    #[AllowDynamicProperties]
    class WP_Importer
    {
        public function __construct() {}

        public function get_imported_posts($importer_name, $blog_id)
        {
            global $wpdb;

            $hashtable = [];

            $limit = 100;
            $offset = 0;

            // Grab all posts in chunks.
            do
            {
                $meta_key = $importer_name.'_'.$blog_id.'_permalink';
                $sql = $wpdb->prepare("SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = %s LIMIT %d,%d", $meta_key, $offset, $limit);
                $results = $wpdb->get_results($sql);

                // Increment offset.
                $offset = ($limit + $offset);

                if(! empty($results))
                {
                    foreach($results as $r)
                    {
                        // Set permalinks into array.
                        $hashtable[$r->meta_value] = (int) $r->post_id;
                    }
                }
            }
            while(count($results) === $limit);

            return $hashtable;
        }

        public function count_imported_posts($importer_name, $blog_id)
        {
            global $wpdb;

            $count = 0;

            // Get count of permalinks.
            $meta_key = $importer_name.'_'.$blog_id.'_permalink';
            $sql = $wpdb->prepare("SELECT COUNT( post_id ) AS cnt FROM $wpdb->postmeta WHERE meta_key = %s", $meta_key);

            $result = $wpdb->get_results($sql);

            if(! empty($result))
            {
                $count = (int) $result[0]->cnt;
            }

            return $count;
        }

        public function get_imported_comments($blog_id)
        {
            global $wpdb;

            $hashtable = [];

            $limit = 100;
            $offset = 0;

            // Grab all comments in chunks.
            do
            {
                $sql = $wpdb->prepare("SELECT comment_ID, comment_agent FROM $wpdb->comments LIMIT %d,%d", $offset, $limit);
                $results = $wpdb->get_results($sql);

                // Increment offset.
                $offset = ($limit + $offset);

                if(! empty($results))
                {
                    foreach($results as $r)
                    {
                        // Explode comment_agent key.
                        [$comment_agent_blog_id, $source_comment_id] = explode('-', $r->comment_agent);

                        $source_comment_id = (int) $source_comment_id;

                        // Check if this comment came from this blog.
                        if((int) $blog_id === (int) $comment_agent_blog_id)
                        {
                            $hashtable[$source_comment_id] = (int) $r->comment_ID;
                        }
                    }
                }
            }
            while(count($results) === $limit);

            return $hashtable;
        }

        public function set_blog($blog_id)
        {
            if(is_numeric($blog_id))
            {
                $blog_id = (int) $blog_id;
            }
            else
            {
                $blog = 'http://'.preg_replace('#^https?://#', '', $blog_id);
                $parsed = parse_url($blog);
                if(! $parsed || empty($parsed['host']))
                {
                    fwrite(STDERR, "Error: can not determine blog_id from $blog_id\n");
                    exit;
                }
                if(empty($parsed['path']))
                {
                    $parsed['path'] = '/';
                }
                $blogs = get_sites([
                                       'domain' => $parsed['host'],
                                       'number' => 1,
                                       'path' => $parsed['path'],
                                   ]);
                if(! $blogs)
                {
                    fwrite(STDERR, "Error: Could not find blog\n");
                    exit;
                }
                $blog = array_shift($blogs);
                $blog_id = (int) $blog->blog_id;
            }

            if(function_exists('is_multisite') && is_multisite())
            {
                switch_to_blog($blog_id);
            }

            return $blog_id;
        }

        public function set_user($user_id)
        {
            if(is_numeric($user_id))
            {
                $user_id = (int) $user_id;
            }
            else
            {
                $user_id = (int) username_exists($user_id);
            }

            if(! $user_id || ! wp_set_current_user($user_id))
            {
                fwrite(STDERR, "Error: can not find user\n");
                exit;
            }

            return $user_id;
        }

        public function cmpr_strlen($a, $b)
        {
            return strlen($b) - strlen($a);
        }

        public function get_page($url, $username = '', $password = '', $head = false)
        {
            // Increase the timeout.
            add_filter('http_request_timeout', [$this, 'bump_request_timeout']);

            $headers = [];
            $args = [];
            if(true === $head)
            {
                $args['method'] = 'HEAD';
            }
            if(! empty($username) && ! empty($password))
            {
                $headers['Authorization'] = 'Basic '.base64_encode("$username:$password");
            }

            $args['headers'] = $headers;

            return wp_safe_remote_request($url, $args);
        }

        public function bump_request_timeout($val)
        {
            return 60;
        }

        public function is_user_over_quota()
        {
            return function_exists('upload_is_user_over_quota') && upload_is_user_over_quota();
        }

        public function min_whitespace($text)
        {
            return preg_replace('|[\r\n\t ]+|', ' ', $text);
        }

        public function stop_the_insanity()
        {
            global $wpdb, $wp_actions;
            // Or define( 'WP_IMPORTING', true );
            $wpdb->queries = [];
            // Reset $wp_actions to keep it from growing out of control.
            $wp_actions = [];
        }
    }

    function get_cli_args($param, $required = false)
    {
        $args = $_SERVER['argv'];
        if(! is_array($args))
        {
            $args = [];
        }

        $out = [];

        $last_arg = null;
        $return = null;

        $il = count($args);

        for($i = 1, $il; $i < $il; $i++)
        {
            if((bool) preg_match('/^--(.+)/', $args[$i], $match))
            {
                $parts = explode('=', $match[1]);
                $key = preg_replace('/[^a-z0-9]+/', '', $parts[0]);

                if(isset($parts[1]))
                {
                    $out[$key] = $parts[1];
                }
                else
                {
                    $out[$key] = true;
                }

                $last_arg = $key;
            }
            elseif((bool) preg_match('/^-([a-zA-Z0-9]+)/', $args[$i], $match))
            {
                for($j = 0, $jl = strlen($match[1]); $j < $jl; $j++)
                {
                    $key = $match[1][$j];
                    $out[$key] = true;
                }

                $last_arg = $key;
            }
            elseif(null !== $last_arg)
            {
                $out[$last_arg] = $args[$i];
            }
        }

        // Check array for specified param.
        if(isset($out[$param]))
        {
            // Set return value.
            $return = $out[$param];
        }

        // Check for missing required param.
        if(! isset($out[$param]) && $required)
        {
            // Display message and exit.
            echo "\"$param\" parameter is required but was not specified\n";
            exit;
        }

        return $return;
    }
