<?php

    function wp_should_replace_insecure_home_url()
    {
        $should_replace_insecure_home_url = wp_is_using_https() && get_option('https_migration_required')
            // For automatic replacement, both 'home' and 'siteurl' need to not only use HTTPS, they also need to be using
            // the same domain.
            && wp_parse_url(home_url(), PHP_URL_HOST) === wp_parse_url(site_url(), PHP_URL_HOST);

        return apply_filters('wp_should_replace_insecure_home_url', $should_replace_insecure_home_url);
    }

    function wp_replace_insecure_home_url($content)
    {
        if(! wp_should_replace_insecure_home_url())
        {
            return $content;
        }

        $https_url = home_url('', 'https');
        $http_url = str_replace('https://', 'http://', $https_url);

        // Also replace potentially escaped URL.
        $escaped_https_url = str_replace('/', '\/', $https_url);
        $escaped_http_url = str_replace('/', '\/', $http_url);

        return str_replace([
                               $http_url,
                               $escaped_http_url,
                           ], [
                               $https_url,
                               $escaped_https_url,
                           ], $content);
    }

    function wp_update_urls_to_https()
    {
        // Get current URL options.
        $orig_home = get_option('home');
        $orig_siteurl = get_option('siteurl');

        // Get current URL options, replacing HTTP with HTTPS.
        $home = str_replace('http://', 'https://', $orig_home);
        $siteurl = str_replace('http://', 'https://', $orig_siteurl);

        // Update the options.
        update_option('home', $home);
        update_option('siteurl', $siteurl);

        if(! wp_is_using_https())
        {
            /*
             * If this did not result in the site recognizing HTTPS as being used,
             * revert the change and return false.
             */
            update_option('home', $orig_home);
            update_option('siteurl', $orig_siteurl);

            return false;
        }

        // Otherwise the URLs were successfully changed to use HTTPS.
        return true;
    }

    function wp_update_https_migration_required($old_url, $new_url)
    {
        // Do nothing if WordPress is being installed.
        if(wp_installing())
        {
            return;
        }

        // Delete/reset the option if the new URL is not the HTTPS version of the old URL.
        if(untrailingslashit((string) $old_url) !== str_replace('https://', 'http://', untrailingslashit((string) $new_url)))
        {
            delete_option('https_migration_required');

            return;
        }

        // If this is a fresh site, there is no content to migrate, so do not require migration.
        $https_migration_required = get_option('fresh_site') ? false : true;

        update_option('https_migration_required', $https_migration_required);
    }
