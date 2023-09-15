<?php

//
// Category.
//

    function category_exists($cat_name, $category_parent = null)
    {
        $id = term_exists($cat_name, 'category', $category_parent);
        if(is_array($id))
        {
            $id = $id['term_id'];
        }

        return $id;
    }

    function get_category_to_edit($id)
    {
        $category = get_term($id, 'category', OBJECT, 'edit');
        _make_cat_compat($category);

        return $category;
    }

    function wp_create_category($cat_name, $category_parent = 0)
    {
        $id = category_exists($cat_name, $category_parent);
        if($id)
        {
            return $id;
        }

        return wp_insert_category(compact('cat_name', 'category_parent'));
    }

    function wp_create_categories($categories, $post_id = '')
    {
        $cat_ids = [];
        foreach($categories as $category)
        {
            $id = category_exists($category);
            if($id)
            {
                $cat_ids[] = $id;
            }
            else
            {
                $id = wp_create_category($category);
                if($id)
                {
                    $cat_ids[] = $id;
                }
            }
        }

        if($post_id)
        {
            wp_set_post_categories($post_id, $cat_ids);
        }

        return $cat_ids;
    }

    function wp_insert_category($catarr, $wp_error = false)
    {
        $cat_defaults = [
            'cat_ID' => 0,
            'taxonomy' => 'category',
            'cat_name' => '',
            'category_description' => '',
            'category_nicename' => '',
            'category_parent' => '',
        ];
        $catarr = wp_parse_args($catarr, $cat_defaults);

        if('' === trim($catarr['cat_name']))
        {
            if($wp_error)
            {
                return new WP_Error('cat_name', __('You did not enter a category name.'));
            }
            else
            {
                return 0;
            }
        }

        $catarr['cat_ID'] = (int) $catarr['cat_ID'];

        // Are we updating or creating?
        $update = ! empty($catarr['cat_ID']);

        $name = $catarr['cat_name'];
        $description = $catarr['category_description'];
        $slug = $catarr['category_nicename'];
        $parent = (int) $catarr['category_parent'];
        if($parent < 0)
        {
            $parent = 0;
        }

        if(empty($parent) || ! term_exists($parent, $catarr['taxonomy']) || ($catarr['cat_ID'] && term_is_ancestor_of($catarr['cat_ID'], $parent, $catarr['taxonomy'])))
        {
            $parent = 0;
        }

        $args = compact('name', 'slug', 'parent', 'description');

        if($update)
        {
            $catarr['cat_ID'] = wp_update_term($catarr['cat_ID'], $catarr['taxonomy'], $args);
        }
        else
        {
            $catarr['cat_ID'] = wp_insert_term($catarr['cat_name'], $catarr['taxonomy'], $args);
        }

        if(is_wp_error($catarr['cat_ID']))
        {
            if($wp_error)
            {
                return $catarr['cat_ID'];
            }
            else
            {
                return 0;
            }
        }

        return $catarr['cat_ID']['term_id'];
    }

    function wp_update_category($catarr)
    {
        $cat_id = (int) $catarr['cat_ID'];

        if(isset($catarr['category_parent']) && ($cat_id === (int) $catarr['category_parent']))
        {
            return false;
        }

        // First, get all of the original fields.
        $category = get_term($cat_id, 'category', ARRAY_A);
        _make_cat_compat($category);

        // Escape data pulled from DB.
        $category = wp_slash($category);

        // Merge old and new fields with new fields overwriting old ones.
        $catarr = array_merge($category, $catarr);

        return wp_insert_category($catarr);
    }

//
// Tags.
//

    function tag_exists($tag_name)
    {
        return term_exists($tag_name, 'post_tag');
    }

    function wp_create_tag($tag_name)
    {
        return wp_create_term($tag_name, 'post_tag');
    }

    function get_tags_to_edit($post_id, $taxonomy = 'post_tag')
    {
        return get_terms_to_edit($post_id, $taxonomy);
    }

    function get_terms_to_edit($post_id, $taxonomy = 'post_tag')
    {
        $post_id = (int) $post_id;
        if(! $post_id)
        {
            return false;
        }

        $terms = get_object_term_cache($post_id, $taxonomy);
        if(false === $terms)
        {
            $terms = wp_get_object_terms($post_id, $taxonomy);
            wp_cache_add($post_id, wp_list_pluck($terms, 'term_id'), $taxonomy.'_relationships');
        }

        if(! $terms)
        {
            return false;
        }
        if(is_wp_error($terms))
        {
            return $terms;
        }
        $term_names = [];
        foreach($terms as $term)
        {
            $term_names[] = $term->name;
        }

        $terms_to_edit = esc_attr(implode(',', $term_names));

        $terms_to_edit = apply_filters('terms_to_edit', $terms_to_edit, $taxonomy);

        return $terms_to_edit;
    }

    function wp_create_term($tag_name, $taxonomy = 'post_tag')
    {
        $id = term_exists($tag_name, $taxonomy);
        if($id)
        {
            return $id;
        }

        return wp_insert_term($tag_name, $taxonomy);
    }
