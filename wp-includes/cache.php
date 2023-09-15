<?php

    require_once ABSPATH.WPINC.'/class-wp-object-cache.php';

    function wp_cache_init()
    {
        $GLOBALS['wp_object_cache'] = new WP_Object_Cache();
    }

    function wp_cache_add($key, $data, $group = '', $expire = 0)
    {
        global $wp_object_cache;

        return $wp_object_cache->add($key, $data, $group, (int) $expire);
    }

    function wp_cache_add_multiple(array $data, $group = '', $expire = 0)
    {
        global $wp_object_cache;

        return $wp_object_cache->add_multiple($data, $group, $expire);
    }

    function wp_cache_replace($key, $data, $group = '', $expire = 0)
    {
        global $wp_object_cache;

        return $wp_object_cache->replace($key, $data, $group, (int) $expire);
    }

    function wp_cache_set($key, $data, $group = '', $expire = 0)
    {
        global $wp_object_cache;

        return $wp_object_cache->set($key, $data, $group, (int) $expire);
    }

    function wp_cache_set_multiple(array $data, $group = '', $expire = 0)
    {
        global $wp_object_cache;

        return $wp_object_cache->set_multiple($data, $group, $expire);
    }

    function wp_cache_get($key, $group = '', $force = false, &$found = null)
    {
        global $wp_object_cache;

        return $wp_object_cache->get($key, $group, $force, $found);
    }

    function wp_cache_get_multiple($keys, $group = '', $force = false)
    {
        global $wp_object_cache;

        return $wp_object_cache->get_multiple($keys, $group, $force);
    }

    function wp_cache_delete($key, $group = '')
    {
        global $wp_object_cache;

        return $wp_object_cache->delete($key, $group);
    }

    function wp_cache_delete_multiple(array $keys, $group = '')
    {
        global $wp_object_cache;

        return $wp_object_cache->delete_multiple($keys, $group);
    }

    function wp_cache_incr($key, $offset = 1, $group = '')
    {
        global $wp_object_cache;

        return $wp_object_cache->incr($key, $offset, $group);
    }

    function wp_cache_decr($key, $offset = 1, $group = '')
    {
        global $wp_object_cache;

        return $wp_object_cache->decr($key, $offset, $group);
    }

    function wp_cache_flush()
    {
        global $wp_object_cache;

        return $wp_object_cache->flush();
    }

    function wp_cache_flush_runtime()
    {
        return wp_cache_flush();
    }

    function wp_cache_flush_group($group)
    {
        global $wp_object_cache;

        return $wp_object_cache->flush_group($group);
    }

    function wp_cache_supports($feature)
    {
        switch($feature)
        {
            case 'add_multiple':
            case 'set_multiple':
            case 'get_multiple':
            case 'delete_multiple':
            case 'flush_runtime':
            case 'flush_group':
                return true;

            default:
                return false;
        }
    }

    function wp_cache_close()
    {
        return true;
    }

    function wp_cache_add_global_groups($groups)
    {
        global $wp_object_cache;

        $wp_object_cache->add_global_groups($groups);
    }

    function wp_cache_add_non_persistent_groups($groups)
    {
        // Default cache doesn't persist so nothing to do here.
    }

    function wp_cache_switch_to_blog($blog_id)
    {
        global $wp_object_cache;

        $wp_object_cache->switch_to_blog($blog_id);
    }

    function wp_cache_reset()
    {
        _deprecated_function(__FUNCTION__, '3.5.0', 'wp_cache_switch_to_blog()');

        global $wp_object_cache;

        $wp_object_cache->reset();
    }
