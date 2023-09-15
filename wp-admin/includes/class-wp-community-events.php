<?php

    #[AllowDynamicProperties]
    class WP_Community_Events
    {
        protected $user_id = 0;

        protected $user_location = false;

        public function __construct($user_id, $user_location = false)
        {
            $this->user_id = absint($user_id);
            $this->user_location = $user_location;
        }

        public function get_events($location_search = '', $timezone = '')
        {
            $cached_events = $this->get_cached_events();

            if(! $location_search && $cached_events)
            {
                return $cached_events;
            }

            // Include an unmodified $wp_version.
            require ABSPATH.WPINC.'/version.php';

            $api_url = 'http://api.wordpress.org/events/1.0/';
            $request_args = $this->get_request_args($location_search, $timezone);
            $request_args['user-agent'] = 'WordPress/'.$wp_version.'; '.home_url('/');

            if(wp_http_supports(['ssl']))
            {
                $api_url = set_url_scheme($api_url, 'https');
            }

            $response = wp_remote_get($api_url, $request_args);
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = json_decode(wp_remote_retrieve_body($response), true);
            $response_error = null;

            if(is_wp_error($response))
            {
                $response_error = $response;
            }
            elseif(200 !== $response_code)
            {
                $response_error = new WP_Error('api-error', /* translators: %d: Numeric HTTP status code, e.g. 400, 403, 500, 504, etc. */ sprintf(__('Invalid API response code (%d).'), $response_code));
            }
            elseif(! isset($response_body['location'], $response_body['events']))
            {
                $response_error = new WP_Error('api-invalid-response', isset($response_body['error']) ? $response_body['error'] : __('Unknown API error.'));
            }

            if(is_wp_error($response_error))
            {
                return $response_error;
            }
            else
            {
                $expiration = false;

                if(isset($response_body['ttl']))
                {
                    $expiration = $response_body['ttl'];
                    unset($response_body['ttl']);
                }

                /*
                 * The IP in the response is usually the same as the one that was sent
                 * in the request, but in some cases it is different. In those cases,
                 * it's important to reset it back to the IP from the request.
                 *
                 * For example, if the IP sent in the request is private (e.g., 192.168.1.100),
                 * then the API will ignore that and use the corresponding public IP instead,
                 * and the public IP will get returned. If the public IP were saved, though,
                 * then get_cached_events() would always return `false`, because the transient
                 * would be generated based on the public IP when saving the cache, but generated
                 * based on the private IP when retrieving the cache.
                 */
                if(! empty($response_body['location']['ip']))
                {
                    $response_body['location']['ip'] = $request_args['body']['ip'];
                }

                /*
                 * The API doesn't return a description for latitude/longitude requests,
                 * but the description is already saved in the user location, so that
                 * one can be used instead.
                 */
                if($this->coordinates_match($request_args['body'], $response_body['location']) && empty($response_body['location']['description']))
                {
                    $response_body['location']['description'] = $this->user_location['description'];
                }

                /*
                 * Store the raw response, because events will expire before the cache does.
                 * The response will need to be processed every page load.
                 */
                $this->cache_events($response_body, $expiration);

                $response_body['events'] = $this->trim_events($response_body['events']);

                return $response_body;
            }
        }

        public function get_cached_events()
        {
            $transient_key = $this->get_events_transient_key($this->user_location);
            if(! $transient_key)
            {
                return false;
            }

            $cached_response = get_site_transient($transient_key);
            if(isset($cached_response['events']))
            {
                $cached_response['events'] = $this->trim_events($cached_response['events']);
            }

            return $cached_response;
        }

        protected function get_events_transient_key($location)
        {
            $key = false;

            if(isset($location['ip']))
            {
                $key = 'community-events-'.md5($location['ip']);
            }
            elseif(isset($location['latitude'], $location['longitude']))
            {
                $key = 'community-events-'.md5($location['latitude'].$location['longitude']);
            }

            return $key;
        }

        protected function trim_events(array $events)
        {
            $future_events = [];

            foreach($events as $event)
            {
                /*
                 * The API's `date` and `end_date` fields are in the _event's_ local timezone, but UTC is needed so
                 * it can be converted to the _user's_ local time.
                 */
                $end_time = (int) $event['end_unix_timestamp'];

                if(time() < $end_time)
                {
                    // Decode HTML entities from the event title.
                    $event['title'] = html_entity_decode($event['title'], ENT_QUOTES, 'UTF-8');

                    $future_events[] = $event;
                }
            }

            $future_wordcamps = array_filter($future_events, static function($wordcamp)
            {
                return 'wordcamp' === $wordcamp['type'];
            });

            $future_wordcamps = array_values($future_wordcamps); // Remove gaps in indices.
            $trimmed_events = array_slice($future_events, 0, 3);
            $trimmed_event_types = wp_list_pluck($trimmed_events, 'type');

            // Make sure the soonest upcoming WordCamp is pinned in the list.
            if($future_wordcamps && ! in_array('wordcamp', $trimmed_event_types, true))
            {
                array_pop($trimmed_events);
                $trimmed_events[] = $future_wordcamps[0];
            }

            return $trimmed_events;
        }

        protected function get_request_args($search = '', $timezone = '')
        {
            $args = [
                'number' => 5, // Get more than three in case some get trimmed out.
                'ip' => self::get_unsafe_client_ip(),
            ];

            /*
             * Include the minimal set of necessary arguments, in order to increase the
             * chances of a cache-hit on the API side.
             */
            if(empty($search) && isset($this->user_location['latitude'], $this->user_location['longitude']))
            {
                $args['latitude'] = $this->user_location['latitude'];
                $args['longitude'] = $this->user_location['longitude'];
            }
            else
            {
                $args['locale'] = get_user_locale($this->user_id);

                if($timezone)
                {
                    $args['timezone'] = $timezone;
                }

                if($search)
                {
                    $args['location'] = $search;
                }
            }

            // Wrap the args in an array compatible with the second parameter of `wp_remote_get()`.
            return [
                'body' => $args,
            ];
        }

        public static function get_unsafe_client_ip()
        {
            $client_ip = false;

            // In order of preference, with the best ones for this purpose first.
            $address_headers = [
                'HTTP_CLIENT_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_FORWARDED',
                'HTTP_X_CLUSTER_CLIENT_IP',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED',
                'REMOTE_ADDR',
            ];

            foreach($address_headers as $header)
            {
                if(array_key_exists($header, $_SERVER))
                {
                    /*
                     * HTTP_X_FORWARDED_FOR can contain a chain of comma-separated
                     * addresses. The first one is the original client. It can't be
                     * trusted for authenticity, but we don't need to for this purpose.
                     */
                    $address_chain = explode(',', $_SERVER[$header]);
                    $client_ip = trim($address_chain[0]);

                    break;
                }
            }

            if(! $client_ip)
            {
                return false;
            }

            $anon_ip = wp_privacy_anonymize_ip($client_ip, true);

            if('0.0.0.0' === $anon_ip || '::' === $anon_ip)
            {
                return false;
            }

            return $anon_ip;
        }

        protected function coordinates_match($a, $b)
        {
            if(! isset($a['latitude'], $a['longitude'], $b['latitude'], $b['longitude']))
            {
                return false;
            }

            return $a['latitude'] === $b['latitude'] && $a['longitude'] === $b['longitude'];
        }

        protected function cache_events($events, $expiration = false)
        {
            $set = false;
            $transient_key = $this->get_events_transient_key($events['location']);
            $cache_expiration = $expiration ? absint($expiration) : HOUR_IN_SECONDS * 12;

            if($transient_key)
            {
                $set = set_site_transient($transient_key, $events, $cache_expiration);
            }

            return $set;
        }

        protected function format_event_data_time($response_body)
        {
            _deprecated_function(__METHOD__, '5.5.2', 'This is no longer used by core, and only kept for backward compatibility.');

            if(isset($response_body['events']))
            {
                foreach($response_body['events'] as $key => $event)
                {
                    $timestamp = strtotime($event['date']);

                    /*
                     * The `date_format` option is not used because it's important
                     * in this context to keep the day of the week in the formatted date,
                     * so that users can tell at a glance if the event is on a day they
                     * are available, without having to open the link.
                     */
                    /* translators: Date format for upcoming events on the dashboard. Include the day of the week. See https://www.php.net/manual/datetime.format.php */
                    $formatted_date = date_i18n(__('l, M j, Y'), $timestamp);
                    $formatted_time = date_i18n(get_option('time_format'), $timestamp);

                    if(isset($event['end_date']))
                    {
                        $end_timestamp = strtotime($event['end_date']);
                        $formatted_end_date = date_i18n(__('l, M j, Y'), $end_timestamp);

                        if('meetup' !== $event['type'] && $formatted_end_date !== $formatted_date)
                        {
                            /* translators: Upcoming events month format. See https://www.php.net/manual/datetime.format.php */
                            $start_month = date_i18n(_x('F', 'upcoming events month format'), $timestamp);
                            $end_month = date_i18n(_x('F', 'upcoming events month format'), $end_timestamp);

                            if($start_month === $end_month)
                            {
                                $formatted_date = sprintf(/* translators: Date string for upcoming events. 1: Month, 2: Starting day, 3: Ending day, 4: Year. */ __('%1$s %2$d–%3$d, %4$d'), $start_month, /* translators: Upcoming events day format. See https://www.php.net/manual/datetime.format.php */ date_i18n(_x('j', 'upcoming events day format'), $timestamp), date_i18n(_x('j', 'upcoming events day format'), $end_timestamp), /* translators: Upcoming events year format. See https://www.php.net/manual/datetime.format.php */ date_i18n(_x('Y', 'upcoming events year format'), $timestamp));
                            }
                            else
                            {
                                $formatted_date = sprintf(/* translators: Date string for upcoming events. 1: Starting month, 2: Starting day, 3: Ending month, 4: Ending day, 5: Year. */ __('%1$s %2$d – %3$s %4$d, %5$d'), $start_month, date_i18n(_x('j', 'upcoming events day format'), $timestamp), $end_month, date_i18n(_x('j', 'upcoming events day format'), $end_timestamp), date_i18n(_x('Y', 'upcoming events year format'), $timestamp));
                            }

                            $formatted_date = wp_maybe_decline_date($formatted_date, 'F j, Y');
                        }
                    }

                    $response_body['events'][$key]['formatted_date'] = $formatted_date;
                    $response_body['events'][$key]['formatted_time'] = $formatted_time;
                }
            }

            return $response_body;
        }

        protected function maybe_log_events_response($message, $details)
        {
            _deprecated_function(__METHOD__, '4.9.0');

            if(! WP_DEBUG_LOG)
            {
                return;
            }
        }
    }
