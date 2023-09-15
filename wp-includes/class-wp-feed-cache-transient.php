<?php

    #[AllowDynamicProperties]
    class WP_Feed_Cache_Transient
    {
        public $name;

        public $mod_name;

        public $lifetime = 43200;

        public function __construct($location, $filename, $extension)
        {
            $this->name = 'feed_'.$filename;
            $this->mod_name = 'feed_mod_'.$filename;

            $lifetime = $this->lifetime;

            $this->lifetime = apply_filters('wp_feed_cache_transient_lifetime', $lifetime, $filename);
        }

        public function save($data)
        {
            if($data instanceof SimplePie)
            {
                $data = $data->data;
            }

            set_transient($this->name, $data, $this->lifetime);
            set_transient($this->mod_name, time(), $this->lifetime);

            return true;
        }

        public function load()
        {
            return get_transient($this->name);
        }

        public function mtime()
        {
            return get_transient($this->mod_name);
        }

        public function touch()
        {
            return set_transient($this->mod_name, time(), $this->lifetime);
        }

        public function unlink()
        {
            delete_transient($this->name);
            delete_transient($this->mod_name);

            return true;
        }
    }
