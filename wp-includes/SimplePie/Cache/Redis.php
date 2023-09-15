<?php

    class Redis implements SimplePie_Cache_Base
    {
        protected $cache;

        protected $options;

        protected $name;

        protected $data;

        public function __construct($location, $name, $options = null)
        {
            //$this->cache = \flow\simple\cache\Redis::getRedisClientInstance();
            $parsed = SimplePie_Cache::parse_URL($location);
            $redis = new Redis();
            $redis->connect($parsed['host'], $parsed['port']);
            if(isset($parsed['pass']))
            {
                $redis->auth($parsed['pass']);
            }
            if(isset($parsed['path']))
            {
                $redis->select((int) substr($parsed['path'], 1));
            }
            $this->cache = $redis;

            if(! is_null($options) && is_array($options))
            {
                $this->options = $options;
            }
            else
            {
                $this->options = [
                    'prefix' => 'rss:simple_primary:',
                    'expire' => 0,
                ];
            }

            $this->name = $this->options['prefix'].$name;
        }

        public function setRedisClient(\Redis $cache)
        {
            $this->cache = $cache;
        }

        public function save($data)
        {
            if($data instanceof SimplePie)
            {
                $data = $data->data;
            }
            $response = $this->cache->set($this->name, serialize($data));
            if($this->options['expire'])
            {
                $this->cache->expire($this->name, $this->options['expire']);
            }

            return $response;
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
                return time();
            }

            return false;
        }

        public function touch()
        {
            $data = $this->cache->get($this->name);

            if($data !== false)
            {
                $return = $this->cache->set($this->name, $data);
                if($this->options['expire'])
                {
                    return $this->cache->expire($this->name, $this->options['expire']);
                }

                return $return;
            }

            return false;
        }

        public function unlink()
        {
            return $this->cache->set($this->name, null);
        }
    }
