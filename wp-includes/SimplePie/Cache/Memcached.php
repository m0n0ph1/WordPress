<?php

    class SimplePie_Cache_Memcached implements SimplePie_Cache_Base
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

            $this->cache = new Memcached();
            $this->cache->addServer($this->options['host'], (int) $this->options['port']);
        }

        public function save($data)
        {
            if($data instanceof SimplePie)
            {
                $data = $data->data;
            }

            return $this->setData(serialize($data));
        }

        private function setData($data)
        {
            if($data !== false)
            {
                $this->cache->set($this->name.'_mtime', time(), (int) $this->options['extras']['timeout']);

                return $this->cache->set($this->name, $data, (int) $this->options['extras']['timeout']);
            }

            return false;
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
            $data = $this->cache->get($this->name.'_mtime');

            return (int) $data;
        }

        public function touch()
        {
            $data = $this->cache->get($this->name);

            return $this->setData($data);
        }

        public function unlink()
        {
            return $this->cache->delete($this->name, 0);
        }
    }
