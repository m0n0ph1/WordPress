<?php

    function register_block_core_page_list_item()
    {
        register_block_type_from_metadata(__DIR__.'/page-list-item');
    }

    add_action('init', 'register_block_core_page_list_item');
