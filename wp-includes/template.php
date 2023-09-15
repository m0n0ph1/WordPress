<?php

    function get_query_template($type, $templates = [])
    {
        $type = preg_replace('|[^a-z0-9-]+|', '', $type);

        if(empty($templates))
        {
            $templates = ["{$type}.php"];
        }

        $templates = apply_filters("{$type}_template_hierarchy", $templates);

        $template = locate_template($templates);

        $template = locate_block_template($template, $type, $templates);

        return apply_filters("{$type}_template", $template, $type, $templates);
    }

    function get_index_template()
    {
        return get_query_template('index');
    }

    function get_404_template()
    {
        return get_query_template('404');
    }

    function get_archive_template()
    {
        $post_types = array_filter((array) get_query_var('post_type'));

        $templates = [];

        if(count($post_types) === 1)
        {
            $post_type = reset($post_types);
            $templates[] = "archive-{$post_type}.php";
        }
        $templates[] = 'archive.php';

        return get_query_template('archive', $templates);
    }

    function get_post_type_archive_template()
    {
        $post_type = get_query_var('post_type');
        if(is_array($post_type))
        {
            $post_type = reset($post_type);
        }

        $obj = get_post_type_object($post_type);
        if(! ($obj instanceof WP_Post_Type) || ! $obj->has_archive)
        {
            return '';
        }

        return get_archive_template();
    }

    function get_author_template()
    {
        $author = get_queried_object();

        $templates = [];

        if($author instanceof WP_User)
        {
            $templates[] = "author-{$author->user_nicename}.php";
            $templates[] = "author-{$author->ID}.php";
        }
        $templates[] = 'author.php';

        return get_query_template('author', $templates);
    }

    function get_category_template()
    {
        $category = get_queried_object();

        $templates = [];

        if(! empty($category->slug))
        {
            $slug_decoded = urldecode($category->slug);
            if($slug_decoded !== $category->slug)
            {
                $templates[] = "category-{$slug_decoded}.php";
            }

            $templates[] = "category-{$category->slug}.php";
            $templates[] = "category-{$category->term_id}.php";
        }
        $templates[] = 'category.php';

        return get_query_template('category', $templates);
    }

    function get_tag_template()
    {
        $tag = get_queried_object();

        $templates = [];

        if(! empty($tag->slug))
        {
            $slug_decoded = urldecode($tag->slug);
            if($slug_decoded !== $tag->slug)
            {
                $templates[] = "tag-{$slug_decoded}.php";
            }

            $templates[] = "tag-{$tag->slug}.php";
            $templates[] = "tag-{$tag->term_id}.php";
        }
        $templates[] = 'tag.php';

        return get_query_template('tag', $templates);
    }

    function get_taxonomy_template()
    {
        $term = get_queried_object();

        $templates = [];

        if(! empty($term->slug))
        {
            $taxonomy = $term->taxonomy;

            $slug_decoded = urldecode($term->slug);
            if($slug_decoded !== $term->slug)
            {
                $templates[] = "taxonomy-$taxonomy-{$slug_decoded}.php";
            }

            $templates[] = "taxonomy-$taxonomy-{$term->slug}.php";
            $templates[] = "taxonomy-$taxonomy.php";
        }
        $templates[] = 'taxonomy.php';

        return get_query_template('taxonomy', $templates);
    }

    function get_date_template()
    {
        return get_query_template('date');
    }

    function get_home_template()
    {
        $templates = ['home.php', 'index.php'];

        return get_query_template('home', $templates);
    }

    function get_front_page_template()
    {
        $templates = ['front-page.php'];

        return get_query_template('frontpage', $templates);
    }

    function get_privacy_policy_template()
    {
        $templates = ['privacy-policy.php'];

        return get_query_template('privacypolicy', $templates);
    }

    function get_page_template()
    {
        $id = get_queried_object_id();
        $template = get_page_template_slug();
        $pagename = get_query_var('pagename');

        if(! $pagename && $id)
        {
            /*
             * If a static page is set as the front page, $pagename will not be set.
             * Retrieve it from the queried object.
             */
            $post = get_queried_object();
            if($post)
            {
                $pagename = $post->post_name;
            }
        }

        $templates = [];
        if($template && 0 === validate_file($template))
        {
            $templates[] = $template;
        }
        if($pagename)
        {
            $pagename_decoded = urldecode($pagename);
            if($pagename_decoded !== $pagename)
            {
                $templates[] = "page-{$pagename_decoded}.php";
            }
            $templates[] = "page-{$pagename}.php";
        }
        if($id)
        {
            $templates[] = "page-{$id}.php";
        }
        $templates[] = 'page.php';

        return get_query_template('page', $templates);
    }

    function get_search_template()
    {
        return get_query_template('search');
    }

    function get_single_template()
    {
        $object = get_queried_object();

        $templates = [];

        if(! empty($object->post_type))
        {
            $template = get_page_template_slug($object);
            if($template && 0 === validate_file($template))
            {
                $templates[] = $template;
            }

            $name_decoded = urldecode($object->post_name);
            if($name_decoded !== $object->post_name)
            {
                $templates[] = "single-{$object->post_type}-{$name_decoded}.php";
            }

            $templates[] = "single-{$object->post_type}-{$object->post_name}.php";
            $templates[] = "single-{$object->post_type}.php";
        }

        $templates[] = 'single.php';

        return get_query_template('single', $templates);
    }

    function get_embed_template()
    {
        $object = get_queried_object();

        $templates = [];

        if(! empty($object->post_type))
        {
            $post_format = get_post_format($object);
            if($post_format)
            {
                $templates[] = "embed-{$object->post_type}-{$post_format}.php";
            }
            $templates[] = "embed-{$object->post_type}.php";
        }

        $templates[] = 'embed.php';

        return get_query_template('embed', $templates);
    }

    function get_singular_template()
    {
        return get_query_template('singular');
    }

    function get_attachment_template()
    {
        $attachment = get_queried_object();

        $templates = [];

        if($attachment)
        {
            if(str_contains($attachment->post_mime_type, '/'))
            {
                [$type, $subtype] = explode('/', $attachment->post_mime_type);
            }
            else
            {
                [$type, $subtype] = [$attachment->post_mime_type, ''];
            }

            if(! empty($subtype))
            {
                $templates[] = "{$type}-{$subtype}.php";
                $templates[] = "{$subtype}.php";
            }
            $templates[] = "{$type}.php";
        }
        $templates[] = 'attachment.php';

        return get_query_template('attachment', $templates);
    }

    function locate_template($template_names, $load = false, $load_once = true, $args = [])
    {
        $located = '';
        foreach((array) $template_names as $template_name)
        {
            if(! $template_name)
            {
                continue;
            }
            if(file_exists(STYLESHEETPATH.'/'.$template_name))
            {
                $located = STYLESHEETPATH.'/'.$template_name;
                break;
            }
            elseif(is_child_theme() && file_exists(TEMPLATEPATH.'/'.$template_name))
            {
                $located = TEMPLATEPATH.'/'.$template_name;
                break;
            }
            elseif(file_exists(ABSPATH.WPINC.'/theme-compat/'.$template_name))
            {
                $located = ABSPATH.WPINC.'/theme-compat/'.$template_name;
                break;
            }
        }

        if($load && '' !== $located)
        {
            load_template($located, $load_once, $args);
        }

        return $located;
    }

    function load_template($_template_file, $load_once = true, $args = [])
    {
        global $posts, $post, $wp_did_header, $wp_query, $wp_rewrite, $wpdb, $wp_version, $wp, $id, $comment, $user_ID;

        if(is_array($wp_query->query_vars))
        {
            /*
             * This use of extract() cannot be removed. There are many possible ways that
             * templates could depend on variables that it creates existing, and no way to
             * detect and deprecate it.
             *
             * Passing the EXTR_SKIP flag is the safest option, ensuring globals and
             * function variables cannot be overwritten.
             */
            // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
            extract($wp_query->query_vars, EXTR_SKIP);
        }

        if(isset($s))
        {
            $s = esc_attr($s);
        }

        do_action('wp_before_load_template', $_template_file, $load_once, $args);

        if($load_once)
        {
            require_once $_template_file;
        }
        else
        {
            require $_template_file;
        }

        do_action('wp_after_load_template', $_template_file, $load_once, $args);
    }
