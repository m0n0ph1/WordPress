<?php

    function wp_is_using_https()
    {
        if(! wp_is_home_url_using_https())
        {
            return false;
        }

        return wp_is_site_url_using_https();
    }

    function wp_is_home_url_using_https()
    {
        return 'https' === wp_parse_url(home_url(), PHP_URL_SCHEME);
    }

    function wp_is_site_url_using_https()
    {
        /*
         * Use direct option access for 'siteurl' and manually run the 'site_url'
         * filter because `site_url()` will adjust the scheme based on what the
         * current request is using.
         */

        $site_url = apply_filters('site_url', get_option('siteurl'), '', null, null);

        return 'https' === wp_parse_url($site_url, PHP_URL_SCHEME);
    }

    function wp_is_https_supported()
    {
        $https_detection_errors = get_option('https_detection_errors');

        // If option has never been set by the Cron hook before, run it on-the-fly as fallback.
        if(false === $https_detection_errors)
        {
            wp_update_https_detection_errors();

            $https_detection_errors = get_option('https_detection_errors');
        }

        // If there are no detection errors, HTTPS is supported.
        return empty($https_detection_errors);
    }

    function wp_update_https_detection_errors()
    {
        $support_errors = apply_filters('pre_wp_update_https_detection_errors', null);
        if(is_wp_error($support_errors))
        {
            update_option('https_detection_errors', $support_errors->errors);

            return;
        }

        $support_errors = new WP_Error();

        $response = wp_remote_request(home_url('/', 'https'), [
            'headers' => [
                'Cache-Control' => 'no-cache',
            ],
            'sslverify' => true,
        ]);

        if(is_wp_error($response))
        {
            $unverified_response = wp_remote_request(home_url('/', 'https'), [
                'headers' => [
                    'Cache-Control' => 'no-cache',
                ],
                'sslverify' => false,
            ]);

            if(is_wp_error($unverified_response))
            {
                $support_errors->add('https_request_failed', __('HTTPS request failed.'));
            }
            else
            {
                $support_errors->add('ssl_verification_failed', __('SSL verification failed.'));
            }

            $response = $unverified_response;
        }

        if(! is_wp_error($response))
        {
            if(200 !== wp_remote_retrieve_response_code($response))
            {
                $support_errors->add('bad_response_code', wp_remote_retrieve_response_message($response));
            }
            elseif(false === wp_is_local_html_output(wp_remote_retrieve_body($response)))
            {
                $support_errors->add('bad_response_source', __('It looks like the response did not come from this site.'));
            }
        }

        update_option('https_detection_errors', $support_errors->errors);
    }

    function wp_schedule_https_detection()
    {
        if(wp_installing())
        {
            return;
        }

        if(! wp_next_scheduled('wp_https_detection'))
        {
            wp_schedule_event(time(), 'twicedaily', 'wp_https_detection');
        }
    }

    function wp_cron_conditionally_prevent_sslverify($request)
    {
        if('https' === wp_parse_url($request['url'], PHP_URL_SCHEME))
        {
            $request['args']['sslverify'] = false;
        }

        return $request;
    }

    function wp_is_local_html_output($html)
    {
        // 1. Check if HTML includes the site's Really Simple Discovery link.
        if(has_action('wp_head', 'rsd_link'))
        {
            $pattern = preg_replace('#^https?:(?=//)#', '', esc_url(site_url('xmlrpc.php?rsd', 'rpc'))); // See rsd_link().

            return str_contains($html, $pattern);
        }

        // 2. Check if HTML includes the site's REST API link.
        if(has_action('wp_head', 'rest_output_link_wp_head'))
        {
            // Try both HTTPS and HTTP since the URL depends on context.
            $pattern = preg_replace('#^https?:(?=//)#', '', esc_url(get_rest_url())); // See rest_output_link_wp_head().

            return str_contains($html, $pattern);
        }

        // Otherwise the result cannot be determined.
        return null;
    }
