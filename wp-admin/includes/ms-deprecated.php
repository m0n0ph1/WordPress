<?php

    function wpmu_menu()
    {
        _deprecated_function(__FUNCTION__, '3.0.0');
        // Deprecated. See #11763.
    }

    function wpmu_checkAvailableSpace()
    {
        _deprecated_function(__FUNCTION__, '3.0.0', 'is_upload_space_available()');

        if(! is_upload_space_available())
        {
            wp_die(sprintf(/* translators: %s: Allowed space allocation. */ __('Sorry, you have used your space allocation of %s. Please delete some files to upload more files.'), size_format(get_space_allowed() * MB_IN_BYTES)));
        }
    }

    function mu_options($options)
    {
        _deprecated_function(__FUNCTION__, '3.0.0');

        return $options;
    }

    function activate_sitewide_plugin()
    {
        _deprecated_function(__FUNCTION__, '3.0.0', 'activate_plugin()');

        return false;
    }

    function deactivate_sitewide_plugin($plugin = false)
    {
        _deprecated_function(__FUNCTION__, '3.0.0', 'deactivate_plugin()');
    }

    function is_wpmu_sitewide_plugin($file)
    {
        _deprecated_function(__FUNCTION__, '3.0.0', 'is_network_only_plugin()');

        return is_network_only_plugin($file);
    }

    function get_site_allowed_themes()
    {
        _deprecated_function(__FUNCTION__, '3.4.0', 'WP_Theme::get_allowed_on_network()');

        return array_map('intval', WP_Theme::get_allowed_on_network());
    }

    function wpmu_get_blog_allowedthemes($blog_id = 0)
    {
        _deprecated_function(__FUNCTION__, '3.4.0', 'WP_Theme::get_allowed_on_site()');

        return array_map('intval', WP_Theme::get_allowed_on_site($blog_id));
    }

    function ms_deprecated_blogs_file() {}

    if(! function_exists('install_global_terms')) :

        function install_global_terms()
        {
            _deprecated_function(__FUNCTION__, '6.1.0');
        }
    endif;

    function sync_category_tag_slugs($term, $taxonomy)
    {
        _deprecated_function(__FUNCTION__, '6.1.0');

        return $term;
    }
