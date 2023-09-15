<?php

    class Memcache implements SimplePie_Cache_Base
    {
        protected $cache;

        protected $options;

        protected $name;

        public function __construct($location, $name, $type)
        {
            $this->options = [
                'host' => '127.0.0.1',
                'port' => 11211,
                'extras' => [
                    'timeout' => 3600, // one hour
                    'prefix' => 'simplepie_',
                ],
            ];
            $this->options = SimplePie_Misc::array_merge_recursive($this->options, SimplePie_Cache::parse_URL($location));

            $this->name = $this->options['extras']['prefix'].md5("$name:$type");

            $this->cache = new Memcache();
            $this->cache->addServer($this->options['host'], (int) $this->options['port']);
        }

        public function save($data)
        {
            if($data instanceof SimplePie)
            {
                $data = $data->data;
            }

            return $this->cache->set($this->name, serialize($data), MEMCACHE_COMPRESSED, (int) $this->options['extras']['timeout']);
        }

        public function load()
        {
            $data = $this->cache->get($this->name);

            if($data !== false)
            {
                return unserialize($data);
            }

            return false;
        }

        public function mtime()
        {
            $data = $this->cache->get($this->name);

            if($data !== false)
            {
                // essentially ignore the mtime because Memcache expires on its own
                return time();
            }

            return false;
        }

        public function touch()
        {
            $data = $this->cache->get($this->name);

            if($data !== false)
            {
                return $this->cache->set($this->name, $data, MEMCACHE_COMPRESSED, (int) $this->options['extras']['timeout']);
            }

            return false;
        }

        public function unlink()
        {
            return $this->cache->delete($this->name, 0);
        }
    }
