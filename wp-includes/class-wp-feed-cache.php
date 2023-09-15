<?php

    _deprecated_file(basename(__FILE__), '5.6.0', '', __('This file is only loaded for backward compatibility with SimplePie 1.2.x. Please consider switching to a recent SimplePie version.'));

    #[AllowDynamicProperties]
    class WP_Feed_Cache extends SimplePie_Cache
    {
        public function create($location, $filename, $extension)
        {
            return new WP_Feed_Cache_Transient($location, $filename, $extension);
        }
    }
