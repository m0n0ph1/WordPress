<?php

    #[AllowDynamicProperties]
    class WP_Object_Cache
    {
        public $cache_hits = 0;

        public $cache_misses = 0;

        protected $global_groups = [];

        private $cache = [];

        private $blog_prefix;

        private $multisite;

        public function __construct()
        {
            $this->multisite = is_multisite();
            $this->blog_prefix = $this->multisite ? get_current_blog_id().':' : '';
        }

        public function __get($name)
        {
            return $this->$name;
        }

        public function __set($name, $value)
        {
            return $this->$name = $value;
        }

        public function __isset($name)
        {
            return isset($this->$name);
        }

        public function __unset($name)
        {
            unset($this->$name);
        }

        public function add_multiple(array $data, $group = '', $expire = 0)
        {
            $values = [];

            foreach($data as $key => $value)
            {
                $values[$key] = $this->add($key, $value, $group, $expire);
            }

            return $values;
        }

        public function add($key, $data, $group = 'default', $expire = 0)
        {
            if(wp_suspend_cache_addition())
            {
                return false;
            }

            if(! $this->is_valid_key($key))
            {
                return false;
            }

            if(empty($group))
            {
                $group = 'default';
            }

            $id = $key;
            if($this->multisite && ! isset($this->global_groups[$group]))
            {
                $id = $this->blog_prefix.$key;
            }

            if($this->_exists($id, $group))
            {
                return false;
            }

            return $this->set($key, $data, $group, (int) $expire);
        }

        protected function is_valid_key($key)
        {
            if(is_int($key))
            {
                return true;
            }

            if(is_string($key) && trim($key) !== '')
            {
                return true;
            }

            $type = gettype($key);

            if(! function_exists('__'))
            {
                wp_load_translations_early();
            }

            $message = is_string($key) ? __('Cache key must not be an empty string.') /* translators: %s: The type of the given cache key. */ : sprintf(__('Cache key must be an integer or a non-empty string, %s given.'), $type);

            _doing_it_wrong(sprintf('%s::%s', __CLASS__, debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function']), $message, '6.1.0');

            return false;
        }

        protected function _exists($key, $group)
        {
            return isset($this->cache[$group]) && (isset($this->cache[$group][$key]) || array_key_exists($key, $this->cache[$group]));
        }

        public function set($key, $data, $group = 'default', $expire = 0)
        {
            if(! $this->is_valid_key($key))
            {
                return false;
            }

            if(empty($group))
            {
                $group = 'default';
            }

            if($this->multisite && ! isset($this->global_groups[$group]))
            {
                $key = $this->blog_prefix.$key;
            }

            if(is_object($data))
            {
                $data = clone $data;
            }

            $this->cache[$group][$key] = $data;

            return true;
        }

        public function replace($key, $data, $group = 'default', $expire = 0)
        {
            if(! $this->is_valid_key($key))
            {
                return false;
            }

            if(empty($group))
            {
                $group = 'default';
            }

            $id = $key;
            if($this->multisite && ! isset($this->global_groups[$group]))
            {
                $id = $this->blog_prefix.$key;
            }

            if(! $this->_exists($id, $group))
            {
                return false;
            }

            return $this->set($key, $data, $group, (int) $expire);
        }

        public function set_multiple(array $data, $group = '', $expire = 0)
        {
            $values = [];

            foreach($data as $key => $value)
            {
                $values[$key] = $this->set($key, $value, $group, $expire);
            }

            return $values;
        }

        public function get_multiple($keys, $group = 'default', $force = false)
        {
            $values = [];

            foreach($keys as $key)
            {
                $values[$key] = $this->get($key, $group, $force);
            }

            return $values;
        }

        public function get($key, $group = 'default', $force = false, &$found = null)
        {
            if(! $this->is_valid_key($key))
            {
                return false;
            }

            if(empty($group))
            {
                $group = 'default';
            }

            if($this->multisite && ! isset($this->global_groups[$group]))
            {
                $key = $this->blog_prefix.$key;
            }

            if($this->_exists($key, $group))
            {
                $found = true;
                $this->cache_hits += 1;
                if(is_object($this->cache[$group][$key]))
                {
                    return clone $this->cache[$group][$key];
                }
                else
                {
                    return $this->cache[$group][$key];
                }
            }

            $found = false;
            $this->cache_misses += 1;

            return false;
        }

        public function delete_multiple(array $keys, $group = '')
        {
            $values = [];

            foreach($keys as $key)
            {
                $values[$key] = $this->delete($key, $group);
            }

            return $values;
        }

        public function delete($key, $group = 'default', $deprecated = false)
        {
            if(! $this->is_valid_key($key))
            {
                return false;
            }

            if(empty($group))
            {
                $group = 'default';
            }

            if($this->multisite && ! isset($this->global_groups[$group]))
            {
                $key = $this->blog_prefix.$key;
            }

            if(! $this->_exists($key, $group))
            {
                return false;
            }

            unset($this->cache[$group][$key]);

            return true;
        }

        public function incr($key, $offset = 1, $group = 'default')
        {
            if(! $this->is_valid_key($key))
            {
                return false;
            }

            if(empty($group))
            {
                $group = 'default';
            }

            if($this->multisite && ! isset($this->global_groups[$group]))
            {
                $key = $this->blog_prefix.$key;
            }

            if(! $this->_exists($key, $group))
            {
                return false;
            }

            if(! is_numeric($this->cache[$group][$key]))
            {
                $this->cache[$group][$key] = 0;
            }

            $offset = (int) $offset;

            $this->cache[$group][$key] += $offset;

            if($this->cache[$group][$key] < 0)
            {
                $this->cache[$group][$key] = 0;
            }

            return $this->cache[$group][$key];
        }

        public function decr($key, $offset = 1, $group = 'default')
        {
            if(! $this->is_valid_key($key))
            {
                return false;
            }

            if(empty($group))
            {
                $group = 'default';
            }

            if($this->multisite && ! isset($this->global_groups[$group]))
            {
                $key = $this->blog_prefix.$key;
            }

            if(! $this->_exists($key, $group))
            {
                return false;
            }

            if(! is_numeric($this->cache[$group][$key]))
            {
                $this->cache[$group][$key] = 0;
            }

            $offset = (int) $offset;

            $this->cache[$group][$key] -= $offset;

            if($this->cache[$group][$key] < 0)
            {
                $this->cache[$group][$key] = 0;
            }

            return $this->cache[$group][$key];
        }

        public function flush()
        {
            $this->cache = [];

            return true;
        }

        public function flush_group($group)
        {
            unset($this->cache[$group]);

            return true;
        }

        public function add_global_groups($groups)
        {
            $groups = (array) $groups;

            $groups = array_fill_keys($groups, true);
            $this->global_groups = array_merge($this->global_groups, $groups);
        }

        public function switch_to_blog($blog_id)
        {
            $blog_id = (int) $blog_id;
            $this->blog_prefix = $this->multisite ? $blog_id.':' : '';
        }

        public function reset()
        {
            _deprecated_function(__FUNCTION__, '3.5.0', 'WP_Object_Cache::switch_to_blog()');

            // Clear out non-global caches since the blog ID has changed.
            foreach(array_keys($this->cache) as $group)
            {
                if(! isset($this->global_groups[$group]))
                {
                    unset($this->cache[$group]);
                }
            }
        }

        public function stats()
        {
            echo '<p>';
            echo "<strong>Cache Hits:</strong> {$this->cache_hits}<br />";
            echo "<strong>Cache Misses:</strong> {$this->cache_misses}<br />";
            echo '</p>';
            echo '<ul>';
            foreach($this->cache as $group => $cache)
            {
                echo '<li><strong>Group:</strong> '.esc_html($group).' - ( '.number_format(strlen(serialize($cache)) / KB_IN_BYTES, 2).'k )</li>';
            }
            echo '</ul>';
        }
    }
