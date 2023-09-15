<?php

    function map_meta_cap($cap, $user_id, ...$args)
    {
        $caps = [];

        switch($cap)
        {
            case 'remove_user':
                // In multisite the user must be a super admin to remove themselves.
                if(isset($args[0]) && $user_id == $args[0] && ! is_super_admin($user_id))
                {
                    $caps[] = 'do_not_allow';
                }
                else
                {
                    $caps[] = 'remove_users';
                }
                break;
            case 'promote_user':
            case 'add_users':
                $caps[] = 'promote_users';
                break;
            case 'edit_user':
            case 'edit_users':
                // Allow user to edit themselves.
                if('edit_user' === $cap && isset($args[0]) && $user_id == $args[0])
                {
                    break;
                }

                // In multisite the user must have manage_network_users caps. If editing a super admin, the user must be a super admin.
                if(is_multisite() && ((! is_super_admin($user_id) && 'edit_user' === $cap && is_super_admin($args[0])) || ! user_can($user_id, 'manage_network_users')))
                {
                    $caps[] = 'do_not_allow';
                }
                else
                {
                    $caps[] = 'edit_users'; // edit_user maps to edit_users.
                }
                break;
            case 'delete_post':
            case 'delete_page':
                if(! isset($args[0]))
                {
                    if('delete_post' === $cap)
                    {
                        /* translators: %s: Capability name. */
                        $message = __('When checking for the %s capability, you must always check it against a specific post.');
                    }
                    else
                    {
                        /* translators: %s: Capability name. */
                        $message = __('When checking for the %s capability, you must always check it against a specific page.');
                    }

                    _doing_it_wrong(__FUNCTION__, sprintf($message, '<code>'.$cap.'</code>'), '6.1.0');

                    $caps[] = 'do_not_allow';
                    break;
                }

                $post = get_post($args[0]);
                if(! $post)
                {
                    $caps[] = 'do_not_allow';
                    break;
                }

                if('revision' === $post->post_type)
                {
                    $caps[] = 'do_not_allow';
                    break;
                }

                if((get_option('page_for_posts') == $post->ID) || (get_option('page_on_front') == $post->ID))
                {
                    $caps[] = 'manage_options';
                    break;
                }

                $post_type = get_post_type_object($post->post_type);
                if(! $post_type)
                {
                    /* translators: 1: Post type, 2: Capability name. */
                    $message = __('The post type %1$s is not registered, so it may not be reliable to check the capability %2$s against a post of that type.');

                    _doing_it_wrong(__FUNCTION__, sprintf($message, '<code>'.$post->post_type.'</code>', '<code>'.$cap.'</code>'), '4.4.0');

                    $caps[] = 'edit_others_posts';
                    break;
                }

                if(! $post_type->map_meta_cap)
                {
                    $caps[] = $post_type->cap->$cap;
                    // Prior to 3.1 we would re-call map_meta_cap here.
                    if('delete_post' === $cap)
                    {
                        $cap = $post_type->cap->$cap;
                    }
                    break;
                }

                // If the post author is set and the user is the author...
                if($post->post_author && $user_id == $post->post_author)
                {
                    // If the post is published or scheduled...
                    if(in_array($post->post_status, ['publish', 'future'], true))
                    {
                        $caps[] = $post_type->cap->delete_published_posts;
                    }
                    elseif('trash' === $post->post_status)
                    {
                        $status = get_post_meta($post->ID, '_wp_trash_meta_status', true);
                        if(in_array($status, ['publish', 'future'], true))
                        {
                            $caps[] = $post_type->cap->delete_published_posts;
                        }
                        else
                        {
                            $caps[] = $post_type->cap->delete_posts;
                        }
                    }
                    else
                    {
                        // If the post is draft...
                        $caps[] = $post_type->cap->delete_posts;
                    }
                }
                else
                {
                    // The user is trying to edit someone else's post.
                    $caps[] = $post_type->cap->delete_others_posts;
                    // The post is published or scheduled, extra cap required.
                    if(in_array($post->post_status, ['publish', 'future'], true))
                    {
                        $caps[] = $post_type->cap->delete_published_posts;
                    }
                    elseif('private' === $post->post_status)
                    {
                        $caps[] = $post_type->cap->delete_private_posts;
                    }
                }

                /*
                 * Setting the privacy policy page requires `manage_privacy_options`,
                 * so deleting it should require that too.
                 */
                if((int) get_option('wp_page_for_privacy_policy') === $post->ID)
                {
                    $caps = array_merge($caps, map_meta_cap('manage_privacy_options', $user_id));
                }

                break;
            /*
             * edit_post breaks down to edit_posts, edit_published_posts, or
             * edit_others_posts.
             */ case 'edit_post':
            case 'edit_page':
                if(! isset($args[0]))
                {
                    if('edit_post' === $cap)
                    {
                        /* translators: %s: Capability name. */
                        $message = __('When checking for the %s capability, you must always check it against a specific post.');
                    }
                    else
                    {
                        /* translators: %s: Capability name. */
                        $message = __('When checking for the %s capability, you must always check it against a specific page.');
                    }

                    _doing_it_wrong(__FUNCTION__, sprintf($message, '<code>'.$cap.'</code>'), '6.1.0');

                    $caps[] = 'do_not_allow';
                    break;
                }

                $post = get_post($args[0]);
                if(! $post)
                {
                    $caps[] = 'do_not_allow';
                    break;
                }

                if('revision' === $post->post_type)
                {
                    $post = get_post($post->post_parent);
                    if(! $post)
                    {
                        $caps[] = 'do_not_allow';
                        break;
                    }
                }

                $post_type = get_post_type_object($post->post_type);
                if(! $post_type)
                {
                    /* translators: 1: Post type, 2: Capability name. */
                    $message = __('The post type %1$s is not registered, so it may not be reliable to check the capability %2$s against a post of that type.');

                    _doing_it_wrong(__FUNCTION__, sprintf($message, '<code>'.$post->post_type.'</code>', '<code>'.$cap.'</code>'), '4.4.0');

                    $caps[] = 'edit_others_posts';
                    break;
                }

                if(! $post_type->map_meta_cap)
                {
                    $caps[] = $post_type->cap->$cap;
                    // Prior to 3.1 we would re-call map_meta_cap here.
                    if('edit_post' === $cap)
                    {
                        $cap = $post_type->cap->$cap;
                    }
                    break;
                }

                // If the post author is set and the user is the author...
                if($post->post_author && $user_id == $post->post_author)
                {
                    // If the post is published or scheduled...
                    if(in_array($post->post_status, ['publish', 'future'], true))
                    {
                        $caps[] = $post_type->cap->edit_published_posts;
                    }
                    elseif('trash' === $post->post_status)
                    {
                        $status = get_post_meta($post->ID, '_wp_trash_meta_status', true);
                        if(in_array($status, ['publish', 'future'], true))
                        {
                            $caps[] = $post_type->cap->edit_published_posts;
                        }
                        else
                        {
                            $caps[] = $post_type->cap->edit_posts;
                        }
                    }
                    else
                    {
                        // If the post is draft...
                        $caps[] = $post_type->cap->edit_posts;
                    }
                }
                else
                {
                    // The user is trying to edit someone else's post.
                    $caps[] = $post_type->cap->edit_others_posts;
                    // The post is published or scheduled, extra cap required.
                    if(in_array($post->post_status, ['publish', 'future'], true))
                    {
                        $caps[] = $post_type->cap->edit_published_posts;
                    }
                    elseif('private' === $post->post_status)
                    {
                        $caps[] = $post_type->cap->edit_private_posts;
                    }
                }

                /*
                 * Setting the privacy policy page requires `manage_privacy_options`,
                 * so editing it should require that too.
                 */
                if((int) get_option('wp_page_for_privacy_policy') === $post->ID)
                {
                    $caps = array_merge($caps, map_meta_cap('manage_privacy_options', $user_id));
                }

                break;
            case 'read_post':
            case 'read_page':
                if(! isset($args[0]))
                {
                    if('read_post' === $cap)
                    {
                        /* translators: %s: Capability name. */
                        $message = __('When checking for the %s capability, you must always check it against a specific post.');
                    }
                    else
                    {
                        /* translators: %s: Capability name. */
                        $message = __('When checking for the %s capability, you must always check it against a specific page.');
                    }

                    _doing_it_wrong(__FUNCTION__, sprintf($message, '<code>'.$cap.'</code>'), '6.1.0');

                    $caps[] = 'do_not_allow';
                    break;
                }

                $post = get_post($args[0]);
                if(! $post)
                {
                    $caps[] = 'do_not_allow';
                    break;
                }

                if('revision' === $post->post_type)
                {
                    $post = get_post($post->post_parent);
                    if(! $post)
                    {
                        $caps[] = 'do_not_allow';
                        break;
                    }
                }

                $post_type = get_post_type_object($post->post_type);
                if(! $post_type)
                {
                    /* translators: 1: Post type, 2: Capability name. */
                    $message = __('The post type %1$s is not registered, so it may not be reliable to check the capability %2$s against a post of that type.');

                    _doing_it_wrong(__FUNCTION__, sprintf($message, '<code>'.$post->post_type.'</code>', '<code>'.$cap.'</code>'), '4.4.0');

                    $caps[] = 'edit_others_posts';
                    break;
                }

                if(! $post_type->map_meta_cap)
                {
                    $caps[] = $post_type->cap->$cap;
                    // Prior to 3.1 we would re-call map_meta_cap here.
                    if('read_post' === $cap)
                    {
                        $cap = $post_type->cap->$cap;
                    }
                    break;
                }

                $status_obj = get_post_status_object(get_post_status($post));
                if(! $status_obj)
                {
                    /* translators: 1: Post status, 2: Capability name. */
                    $message = __('The post status %1$s is not registered, so it may not be reliable to check the capability %2$s against a post with that status.');

                    _doing_it_wrong(__FUNCTION__, sprintf($message, '<code>'.get_post_status($post).'</code>', '<code>'.$cap.'</code>'), '5.4.0');

                    $caps[] = 'edit_others_posts';
                    break;
                }

                if($status_obj->public)
                {
                    $caps[] = $post_type->cap->read;
                    break;
                }

                if($post->post_author && $user_id == $post->post_author)
                {
                    $caps[] = $post_type->cap->read;
                }
                elseif($status_obj->private)
                {
                    $caps[] = $post_type->cap->read_private_posts;
                }
                else
                {
                    $caps = map_meta_cap('edit_post', $user_id, $post->ID);
                }
                break;
            case 'publish_post':
                if(! isset($args[0]))
                {
                    /* translators: %s: Capability name. */
                    $message = __('When checking for the %s capability, you must always check it against a specific post.');

                    _doing_it_wrong(__FUNCTION__, sprintf($message, '<code>'.$cap.'</code>'), '6.1.0');

                    $caps[] = 'do_not_allow';
                    break;
                }

                $post = get_post($args[0]);
                if(! $post)
                {
                    $caps[] = 'do_not_allow';
                    break;
                }

                $post_type = get_post_type_object($post->post_type);
                if(! $post_type)
                {
                    /* translators: 1: Post type, 2: Capability name. */
                    $message = __('The post type %1$s is not registered, so it may not be reliable to check the capability %2$s against a post of that type.');

                    _doing_it_wrong(__FUNCTION__, sprintf($message, '<code>'.$post->post_type.'</code>', '<code>'.$cap.'</code>'), '4.4.0');

                    $caps[] = 'edit_others_posts';
                    break;
                }

                $caps[] = $post_type->cap->publish_posts;
                break;
            case 'edit_post_meta':
            case 'delete_post_meta':
            case 'add_post_meta':
            case 'edit_comment_meta':
            case 'delete_comment_meta':
            case 'add_comment_meta':
            case 'edit_term_meta':
            case 'delete_term_meta':
            case 'add_term_meta':
            case 'edit_user_meta':
            case 'delete_user_meta':
            case 'add_user_meta':
                $object_type = explode('_', $cap)[1];

                if(! isset($args[0]))
                {
                    if('post' === $object_type)
                    {
                        /* translators: %s: Capability name. */
                        $message = __('When checking for the %s capability, you must always check it against a specific post.');
                    }
                    elseif('comment' === $object_type)
                    {
                        /* translators: %s: Capability name. */
                        $message = __('When checking for the %s capability, you must always check it against a specific comment.');
                    }
                    elseif('term' === $object_type)
                    {
                        /* translators: %s: Capability name. */
                        $message = __('When checking for the %s capability, you must always check it against a specific term.');
                    }
                    else
                    {
                        /* translators: %s: Capability name. */
                        $message = __('When checking for the %s capability, you must always check it against a specific user.');
                    }

                    _doing_it_wrong(__FUNCTION__, sprintf($message, '<code>'.$cap.'</code>'), '6.1.0');

                    $caps[] = 'do_not_allow';
                    break;
                }

                $object_id = (int) $args[0];

                $object_subtype = get_object_subtype($object_type, $object_id);

                if(empty($object_subtype))
                {
                    $caps[] = 'do_not_allow';
                    break;
                }

                $caps = map_meta_cap("edit_{$object_type}", $user_id, $object_id);

                $meta_key = isset($args[1]) ? $args[1] : false;

                if($meta_key)
                {
                    $allowed = ! is_protected_meta($meta_key, $object_type);

                    if(! empty($object_subtype) && has_filter("auth_{$object_type}_meta_{$meta_key}_for_{$object_subtype}"))
                    {
                        $allowed = apply_filters("auth_{$object_type}_meta_{$meta_key}_for_{$object_subtype}", $allowed, $meta_key, $object_id, $user_id, $cap, $caps);
                    }
                    else
                    {
                        $allowed = apply_filters("auth_{$object_type}_meta_{$meta_key}", $allowed, $meta_key, $object_id, $user_id, $cap, $caps);
                    }

                    if(! empty($object_subtype))
                    {
                        $allowed = apply_filters_deprecated("auth_{$object_type}_{$object_subtype}_meta_{$meta_key}", [
                            $allowed,
                            $meta_key,
                            $object_id,
                            $user_id,
                            $cap,
                            $caps
                        ],                                  '4.9.8', "auth_{$object_type}_meta_{$meta_key}_for_{$object_subtype}");
                    }

                    if(! $allowed)
                    {
                        $caps[] = $cap;
                    }
                }
                break;
            case 'edit_comment':
                if(! isset($args[0]))
                {
                    /* translators: %s: Capability name. */
                    $message = __('When checking for the %s capability, you must always check it against a specific comment.');

                    _doing_it_wrong(__FUNCTION__, sprintf($message, '<code>'.$cap.'</code>'), '6.1.0');

                    $caps[] = 'do_not_allow';
                    break;
                }

                $comment = get_comment($args[0]);
                if(! $comment)
                {
                    $caps[] = 'do_not_allow';
                    break;
                }

                $post = get_post($comment->comment_post_ID);

                /*
                 * If the post doesn't exist, we have an orphaned comment.
                 * Fall back to the edit_posts capability, instead.
                 */
                if($post)
                {
                    $caps = map_meta_cap('edit_post', $user_id, $post->ID);
                }
                else
                {
                    $caps = map_meta_cap('edit_posts', $user_id);
                }
                break;
            case 'unfiltered_upload':
                if(defined('ALLOW_UNFILTERED_UPLOADS') && ALLOW_UNFILTERED_UPLOADS && (! is_multisite() || is_super_admin($user_id)))
                {
                    $caps[] = $cap;
                }
                else
                {
                    $caps[] = 'do_not_allow';
                }
                break;
            case 'edit_css':
            case 'unfiltered_html':
                // Disallow unfiltered_html for all users, even admins and super admins.
                if(defined('DISALLOW_UNFILTERED_HTML') && DISALLOW_UNFILTERED_HTML)
                {
                    $caps[] = 'do_not_allow';
                }
                elseif(is_multisite() && ! is_super_admin($user_id))
                {
                    $caps[] = 'do_not_allow';
                }
                else
                {
                    $caps[] = 'unfiltered_html';
                }
                break;
            case 'edit_files':
            case 'edit_plugins':
            case 'edit_themes':
                // Disallow the file editors.
                if(defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT)
                {
                    $caps[] = 'do_not_allow';
                }
                elseif(! wp_is_file_mod_allowed('capability_edit_themes'))
                {
                    $caps[] = 'do_not_allow';
                }
                elseif(is_multisite() && ! is_super_admin($user_id))
                {
                    $caps[] = 'do_not_allow';
                }
                else
                {
                    $caps[] = $cap;
                }
                break;
            case 'update_plugins':
            case 'delete_plugins':
            case 'install_plugins':
            case 'upload_plugins':
            case 'update_themes':
            case 'delete_themes':
            case 'install_themes':
            case 'upload_themes':
            case 'update_core':
                /*
                 * Disallow anything that creates, deletes, or updates core, plugin, or theme files.
                 * Files in uploads are excepted.
                 */ if(! wp_is_file_mod_allowed('capability_update_core'))
            {
                $caps[] = 'do_not_allow';
            }
            elseif(is_multisite() && ! is_super_admin($user_id))
            {
                $caps[] = 'do_not_allow';
            }
            elseif('upload_themes' === $cap)
            {
                $caps[] = 'install_themes';
            }
            elseif('upload_plugins' === $cap)
            {
                $caps[] = 'install_plugins';
            }
            else
            {
                $caps[] = $cap;
            }
                break;
            case 'install_languages':
            case 'update_languages':
                if(! wp_is_file_mod_allowed('can_install_language_pack'))
                {
                    $caps[] = 'do_not_allow';
                }
                elseif(is_multisite() && ! is_super_admin($user_id))
                {
                    $caps[] = 'do_not_allow';
                }
                else
                {
                    $caps[] = 'install_languages';
                }
                break;
            case 'activate_plugins':
            case 'deactivate_plugins':
            case 'activate_plugin':
            case 'deactivate_plugin':
                $caps[] = 'activate_plugins';
                if(is_multisite())
                {
                    // update_, install_, and delete_ are handled above with is_super_admin().
                    $menu_perms = get_site_option('menu_items', []);
                    if(empty($menu_perms['plugins']))
                    {
                        $caps[] = 'manage_network_plugins';
                    }
                }
                break;
            case 'resume_plugin':
                $caps[] = 'resume_plugins';
                break;
            case 'resume_theme':
                $caps[] = 'resume_themes';
                break;
            case 'delete_user':
            case 'delete_users':
                // If multisite only super admins can delete users.
                if(is_multisite() && ! is_super_admin($user_id))
                {
                    $caps[] = 'do_not_allow';
                }
                else
                {
                    $caps[] = 'delete_users'; // delete_user maps to delete_users.
                }
                break;
            case 'create_users':
                if(! is_multisite())
                {
                    $caps[] = $cap;
                }
                elseif(is_super_admin($user_id) || get_site_option('add_new_users'))
                {
                    $caps[] = $cap;
                }
                else
                {
                    $caps[] = 'do_not_allow';
                }
                break;
            case 'manage_links':
                if(get_option('link_manager_enabled'))
                {
                    $caps[] = $cap;
                }
                else
                {
                    $caps[] = 'do_not_allow';
                }
                break;
            case 'customize':
                $caps[] = 'edit_theme_options';
                break;
            case 'delete_site':
                if(is_multisite())
                {
                    $caps[] = 'manage_options';
                }
                else
                {
                    $caps[] = 'do_not_allow';
                }
                break;
            case 'edit_term':
            case 'delete_term':
            case 'assign_term':
                if(! isset($args[0]))
                {
                    /* translators: %s: Capability name. */
                    $message = __('When checking for the %s capability, you must always check it against a specific term.');

                    _doing_it_wrong(__FUNCTION__, sprintf($message, '<code>'.$cap.'</code>'), '6.1.0');

                    $caps[] = 'do_not_allow';
                    break;
                }

                $term_id = (int) $args[0];
                $term = get_term($term_id);
                if(! $term || is_wp_error($term))
                {
                    $caps[] = 'do_not_allow';
                    break;
                }

                $tax = get_taxonomy($term->taxonomy);
                if(! $tax)
                {
                    $caps[] = 'do_not_allow';
                    break;
                }

                if('delete_term' === $cap && (get_option('default_'.$term->taxonomy) == $term->term_id || get_option('default_term_'.$term->taxonomy) == $term->term_id))
                {
                    $caps[] = 'do_not_allow';
                    break;
                }

                $taxo_cap = $cap.'s';

                $caps = map_meta_cap($tax->cap->$taxo_cap, $user_id, $term_id);

                break;
            case 'manage_post_tags':
            case 'edit_categories':
            case 'edit_post_tags':
            case 'delete_categories':
            case 'delete_post_tags':
                $caps[] = 'manage_categories';
                break;
            case 'assign_categories':
            case 'assign_post_tags':
                $caps[] = 'edit_posts';
                break;
            case 'create_sites':
            case 'delete_sites':
            case 'manage_network':
            case 'manage_sites':
            case 'manage_network_users':
            case 'manage_network_plugins':
            case 'manage_network_themes':
            case 'manage_network_options':
            case 'upgrade_network':
                $caps[] = $cap;
                break;
            case 'setup_network':
                if(is_multisite())
                {
                    $caps[] = 'manage_network_options';
                }
                else
                {
                    $caps[] = 'manage_options';
                }
                break;
            case 'update_php':
                if(is_multisite() && ! is_super_admin($user_id))
                {
                    $caps[] = 'do_not_allow';
                }
                else
                {
                    $caps[] = 'update_core';
                }
                break;
            case 'update_https':
                if(is_multisite() && ! is_super_admin($user_id))
                {
                    $caps[] = 'do_not_allow';
                }
                else
                {
                    $caps[] = 'manage_options';
                    $caps[] = 'update_core';
                }
                break;
            case 'export_others_personal_data':
            case 'erase_others_personal_data':
            case 'manage_privacy_options':
                $caps[] = is_multisite() ? 'manage_network' : 'manage_options';
                break;
            case 'create_app_password':
            case 'list_app_passwords':
            case 'read_app_password':
            case 'edit_app_password':
            case 'delete_app_passwords':
            case 'delete_app_password':
                $caps = map_meta_cap('edit_user', $user_id, $args[0]);
                break;
            default:
                // Handle meta capabilities for custom post types.
                global $post_type_meta_caps;
                if(isset($post_type_meta_caps[$cap]))
                {
                    return map_meta_cap($post_type_meta_caps[$cap], $user_id, ...$args);
                }

                // Block capabilities map to their post equivalent.
                $block_caps = [
                    'edit_blocks',
                    'edit_others_blocks',
                    'publish_blocks',
                    'read_private_blocks',
                    'delete_blocks',
                    'delete_private_blocks',
                    'delete_published_blocks',
                    'delete_others_blocks',
                    'edit_private_blocks',
                    'edit_published_blocks',
                ];
                if(in_array($cap, $block_caps, true))
                {
                    $cap = str_replace('_blocks', '_posts', $cap);
                }

                // If no meta caps match, return the original cap.
                $caps[] = $cap;
        }

        return apply_filters('map_meta_cap', $caps, $cap, $user_id, $args);
    }

    function current_user_can($capability, ...$args)
    {
        return user_can(wp_get_current_user(), $capability, ...$args);
    }

    function current_user_can_for_blog($blog_id, $capability, ...$args)
    {
        $switched = is_multisite() ? switch_to_blog($blog_id) : false;

        $can = current_user_can($capability, ...$args);

        if($switched)
        {
            restore_current_blog();
        }

        return $can;
    }

    function author_can($post, $capability, ...$args)
    {
        $post = get_post($post);
        if(! $post)
        {
            return false;
        }

        $author = get_userdata($post->post_author);

        if(! $author)
        {
            return false;
        }

        return $author->has_cap($capability, ...$args);
    }

    function user_can($user, $capability, ...$args)
    {
        if(! is_object($user))
        {
            $user = get_userdata($user);
        }

        if(empty($user))
        {
            // User is logged out, create anonymous user object.
            $user = new WP_User(0);
            $user->init(new stdClass());
        }

        return $user->has_cap($capability, ...$args);
    }

    function wp_roles()
    {
        global $wp_roles;

        if(! isset($wp_roles))
        {
            $wp_roles = new WP_Roles();
        }

        return $wp_roles;
    }

    function get_role($role)
    {
        return wp_roles()->get_role($role);
    }

    function add_role($role, $display_name, $capabilities = [])
    {
        if(empty($role))
        {
            return;
        }

        return wp_roles()->add_role($role, $display_name, $capabilities);
    }

    function remove_role($role)
    {
        wp_roles()->remove_role($role);
    }

    function get_super_admins()
    {
        global $super_admins;

        if(isset($super_admins))
        {
            return $super_admins;
        }
        else
        {
            return get_site_option('site_admins', ['admin']);
        }
    }

    function is_super_admin($user_id = false)
    {
        if(! $user_id)
        {
            $user = wp_get_current_user();
        }
        else
        {
            $user = get_userdata($user_id);
        }

        if(! $user || ! $user->exists())
        {
            return false;
        }

        if(is_multisite())
        {
            $super_admins = get_super_admins();
            if(is_array($super_admins) && in_array($user->user_login, $super_admins, true))
            {
                return true;
            }
        }
        else
        {
            if($user->has_cap('delete_users'))
            {
                return true;
            }
        }

        return false;
    }

    function grant_super_admin($user_id)
    {
        // If global super_admins override is defined, there is nothing to do here.
        if(isset($GLOBALS['super_admins']) || ! is_multisite())
        {
            return false;
        }

        do_action('grant_super_admin', $user_id);

        // Directly fetch site_admins instead of using get_super_admins().
        $super_admins = get_site_option('site_admins', ['admin']);

        $user = get_userdata($user_id);
        if($user && ! in_array($user->user_login, $super_admins, true))
        {
            $super_admins[] = $user->user_login;
            update_site_option('site_admins', $super_admins);

            do_action('granted_super_admin', $user_id);

            return true;
        }

        return false;
    }

    function revoke_super_admin($user_id)
    {
        // If global super_admins override is defined, there is nothing to do here.
        if(isset($GLOBALS['super_admins']) || ! is_multisite())
        {
            return false;
        }

        do_action('revoke_super_admin', $user_id);

        // Directly fetch site_admins instead of using get_super_admins().
        $super_admins = get_site_option('site_admins', ['admin']);

        $user = get_userdata($user_id);
        if($user && 0 !== strcasecmp($user->user_email, get_site_option('admin_email')))
        {
            $key = array_search($user->user_login, $super_admins, true);
            if(false !== $key)
            {
                unset($super_admins[$key]);
                update_site_option('site_admins', $super_admins);

                do_action('revoked_super_admin', $user_id);

                return true;
            }
        }

        return false;
    }

    function wp_maybe_grant_install_languages_cap($allcaps)
    {
        if(! empty($allcaps['update_core']) || ! empty($allcaps['install_plugins']) || ! empty($allcaps['install_themes']))
        {
            $allcaps['install_languages'] = true;
        }

        return $allcaps;
    }

    function wp_maybe_grant_resume_extensions_caps($allcaps)
    {
        // Even in a multisite, regular administrators should be able to resume plugins.
        if(! empty($allcaps['activate_plugins']))
        {
            $allcaps['resume_plugins'] = true;
        }

        // Even in a multisite, regular administrators should be able to resume themes.
        if(! empty($allcaps['switch_themes']))
        {
            $allcaps['resume_themes'] = true;
        }

        return $allcaps;
    }

    function wp_maybe_grant_site_health_caps($allcaps, $caps, $args, $user)
    {
        if(! empty($allcaps['install_plugins']) && (! is_multisite() || is_super_admin($user->ID)))
        {
            $allcaps['view_site_health_checks'] = true;
        }

        return $allcaps;
    }

    return;

// Dummy gettext calls to get strings in the catalog.
    /* translators: User role for administrators. */
    _x('Administrator', 'User role');
    /* translators: User role for editors. */
    _x('Editor', 'User role');
    /* translators: User role for authors. */
    _x('Author', 'User role');
    /* translators: User role for contributors. */
    _x('Contributor', 'User role');
    /* translators: User role for subscribers. */
    _x('Subscriber', 'User role');
