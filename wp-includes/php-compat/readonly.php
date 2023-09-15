<?php

    function readonly($readonly_value, $current = true, $display = true)
    {
        _deprecated_function(__FUNCTION__, '5.9.0', 'wp_readonly()');

        return wp_readonly($readonly_value, $current, $display);
    }
