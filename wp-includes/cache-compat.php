<?php

    if(! function_exists('wp_cache_add_multiple')) :

        function wp_cache_add_multiple(array $data, $group = '', $expire = 0)
        {
            $values = [];

            foreach($data as $key => $value)
            {
                $values[$key] = wp_cache_add($key, $value, $group, $expire);
            }

            return $values;
        }
    endif;

    if(! function_exists('wp_cache_set_multiple')) :

        function wp_cache_set_multiple(array $data, $group = '', $expire = 0)
        {
            $values = [];

            foreach($data as $key => $value)
            {
                $values[$key] = wp_cache_set($key, $value, $group, $expire);
            }

            return $values;
        }
    endif;

    if(! function_exists('wp_cache_get_multiple')) :

        function wp_cache_get_multiple($keys, $group = '', $force = false)
        {
            $values = [];

            foreach($keys as $key)
            {
                $values[$key] = wp_cache_get($key, $group, $force);
            }

            return $values;
        }
    endif;

    if(! function_exists('wp_cache_delete_multiple')) :

        function wp_cache_delete_multiple(array $keys, $group = '')
        {
            $values = [];

            foreach($keys as $key)
            {
                $values[$key] = wp_cache_delete($key, $group);
            }

            return $values;
        }
    endif;

    if(! function_exists('wp_cache_flush_runtime')) :

        function wp_cache_flush_runtime()
        {
            if(! wp_cache_supports('flush_runtime'))
            {
                _doing_it_wrong(__FUNCTION__, __('Your object cache implementation does not support flushing the in-memory runtime cache.'), '6.1.0');

                return false;
            }

            return wp_cache_flush();
        }
    endif;

    if(! function_exists('wp_cache_flush_group')) :

        function wp_cache_flush_group($group)
        {
            global $wp_object_cache;

            if(! wp_cache_supports('flush_group'))
            {
                _doing_it_wrong(__FUNCTION__, __('Your object cache implementation does not support flushing individual groups.'), '6.1.0');

                return false;
            }

            return $wp_object_cache->flush_group($group);
        }
    endif;

    if(! function_exists('wp_cache_supports')) :

        function wp_cache_supports($feature)
        {
            return false;
        }
    endif;
