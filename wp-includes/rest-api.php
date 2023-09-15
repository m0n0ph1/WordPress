<?php

    define('REST_API_VERSION', '2.0');

    function register_rest_route($route_namespace, $route, $args = [], $override = false)
    {
        if(empty($route_namespace))
        {
            /*
		 * Non-namespaced routes are not allowed, with the exception of the main
		 * and namespace indexes. If you really need to register a
		 * non-namespaced route, call `WP_REST_Server::register_route` directly.
		 */
            _doing_it_wrong('register_rest_route', __('Routes must be namespaced with plugin or theme name and version.'), '4.4.0');

            return false;
        }
        elseif(empty($route))
        {
            _doing_it_wrong('register_rest_route', __('Route must be specified.'), '4.4.0');

            return false;
        }

        $clean_namespace = trim($route_namespace, '/');

        if($clean_namespace !== $route_namespace)
        {
            _doing_it_wrong(__FUNCTION__, __('Namespace must not start or end with a slash.'), '5.4.2');
        }

        if(! did_action('rest_api_init'))
        {
            _doing_it_wrong('register_rest_route', sprintf(/* translators: %s: rest_api_init */ __('REST API routes must be registered on the %s action.'), '<code>rest_api_init</code>'), '5.1.0');
        }

        if(isset($args['args']))
        {
            $common_args = $args['args'];
            unset($args['args']);
        }
        else
        {
            $common_args = [];
        }

        if(isset($args['callback']))
        {
            // Upgrade a single set to multiple.
            $args = [$args];
        }

        $defaults = [
            'methods' => 'GET',
            'callback' => null,
            'args' => [],
        ];

        foreach($args as $key => &$arg_group)
        {
            if(! is_numeric($key))
            {
                // Route option, skip here.
                continue;
            }

            $arg_group = array_merge($defaults, $arg_group);
            $arg_group['args'] = array_merge($common_args, $arg_group['args']);

            if(! isset($arg_group['permission_callback']))
            {
                _doing_it_wrong(__FUNCTION__, sprintf(/* translators: 1: The REST API route being registered, 2: The argument name, 3: The suggested function name. */ __('The REST API route definition for %1$s is missing the required %2$s argument. For REST API routes that are intended to be public, use %3$s as the permission callback.'), '<code>'.$clean_namespace.'/'.trim($route, '/').'</code>', '<code>permission_callback</code>', '<code>__return_true</code>'), '5.5.0');
            }

            foreach($arg_group['args'] as $arg)
            {
                if(! is_array($arg))
                {
                    _doing_it_wrong(__FUNCTION__, sprintf(/* translators: 1: $args, 2: The REST API route being registered. */ __('REST API %1$s should be an array of arrays. Non-array value detected for %2$s.'), '<code>$args</code>', '<code>'.$clean_namespace.'/'.trim($route, '/').'</code>'), '6.1.0');
                    break; // Leave the foreach loop once a non-array argument was found.
                }
            }
        }

        $full_route = '/'.$clean_namespace.'/'.trim($route, '/');
        rest_get_server()->register_route($clean_namespace, $full_route, $args, $override);

        return true;
    }

    function register_rest_field($object_type, $attribute, $args = [])
    {
        global $wp_rest_additional_fields;

        $defaults = [
            'get_callback' => null,
            'update_callback' => null,
            'schema' => null,
        ];

        $args = wp_parse_args($args, $defaults);

        $object_types = (array) $object_type;

        foreach($object_types as $object_type)
        {
            $wp_rest_additional_fields[$object_type][$attribute] = $args;
        }
    }

    function rest_api_init()
    {
        rest_api_register_rewrites();

        global $wp;
        $wp->add_query_var('rest_route');
    }

    function rest_api_register_rewrites()
    {
        global $wp_rewrite;

        add_rewrite_rule('^'.rest_get_url_prefix().'/?$', 'index.php?rest_route=/', 'top');
        add_rewrite_rule('^'.rest_get_url_prefix().'/(.*)?', 'index.php?rest_route=/$matches[1]', 'top');
        add_rewrite_rule('^'.$wp_rewrite->index.'/'.rest_get_url_prefix().'/?$', 'index.php?rest_route=/', 'top');
        add_rewrite_rule('^'.$wp_rewrite->index.'/'.rest_get_url_prefix().'/(.*)?', 'index.php?rest_route=/$matches[1]', 'top');
    }

    function rest_api_default_filters()
    {
        if(defined('REST_REQUEST') && REST_REQUEST)
        {
            // Deprecated reporting.
            add_action('deprecated_function_run', 'rest_handle_deprecated_function', 10, 3);
            add_filter('deprecated_function_trigger_error', '__return_false');
            add_action('deprecated_argument_run', 'rest_handle_deprecated_argument', 10, 3);
            add_filter('deprecated_argument_trigger_error', '__return_false');
            add_action('doing_it_wrong_run', 'rest_handle_doing_it_wrong', 10, 3);
            add_filter('doing_it_wrong_trigger_error', '__return_false');
        }

        // Default serving.
        add_filter('rest_pre_serve_request', 'rest_send_cors_headers');
        add_filter('rest_post_dispatch', 'rest_send_allow_header', 10, 3);
        add_filter('rest_post_dispatch', 'rest_filter_response_fields', 10, 3);

        add_filter('rest_pre_dispatch', 'rest_handle_options_request', 10, 3);
        add_filter('rest_index', 'rest_add_application_passwords_to_index');
    }

    function create_initial_rest_routes()
    {
        foreach(get_post_types(['show_in_rest' => true], 'objects') as $post_type)
        {
            $controller = $post_type->get_rest_controller();

            if(! $controller)
            {
                continue;
            }

            $controller->register_routes();

            if(post_type_supports($post_type->name, 'revisions'))
            {
                $revisions_controller = new WP_REST_Revisions_Controller($post_type->name);
                $revisions_controller->register_routes();
            }

            if('attachment' !== $post_type->name)
            {
                $autosaves_controller = new WP_REST_Autosaves_Controller($post_type->name);
                $autosaves_controller->register_routes();
            }
        }

        // Post types.
        $controller = new WP_REST_Post_Types_Controller();
        $controller->register_routes();

        // Post statuses.
        $controller = new WP_REST_Post_Statuses_Controller();
        $controller->register_routes();

        // Taxonomies.
        $controller = new WP_REST_Taxonomies_Controller();
        $controller->register_routes();

        // Terms.
        foreach(get_taxonomies(['show_in_rest' => true], 'object') as $taxonomy)
        {
            $controller = $taxonomy->get_rest_controller();

            if(! $controller)
            {
                continue;
            }

            $controller->register_routes();
        }

        // Users.
        $controller = new WP_REST_Users_Controller();
        $controller->register_routes();

        // Application Passwords
        $controller = new WP_REST_Application_Passwords_Controller();
        $controller->register_routes();

        // Comments.
        $controller = new WP_REST_Comments_Controller();
        $controller->register_routes();

        $search_handlers = [
            new WP_REST_Post_Search_Handler(),
            new WP_REST_Term_Search_Handler(),
            new WP_REST_Post_Format_Search_Handler(),
        ];

        $search_handlers = apply_filters('wp_rest_search_handlers', $search_handlers);

        $controller = new WP_REST_Search_Controller($search_handlers);
        $controller->register_routes();

        // Block Renderer.
        $controller = new WP_REST_Block_Renderer_Controller();
        $controller->register_routes();

        // Block Types.
        $controller = new WP_REST_Block_Types_Controller();
        $controller->register_routes();

        // Global Styles revisions.
        $controller = new WP_REST_Global_Styles_Revisions_Controller();
        $controller->register_routes();

        // Global Styles.
        $controller = new WP_REST_Global_Styles_Controller();
        $controller->register_routes();

        // Settings.
        $controller = new WP_REST_Settings_Controller();
        $controller->register_routes();

        // Themes.
        $controller = new WP_REST_Themes_Controller();
        $controller->register_routes();

        // Plugins.
        $controller = new WP_REST_Plugins_Controller();
        $controller->register_routes();

        // Sidebars.
        $controller = new WP_REST_Sidebars_Controller();
        $controller->register_routes();

        // Widget Types.
        $controller = new WP_REST_Widget_Types_Controller();
        $controller->register_routes();

        // Widgets.
        $controller = new WP_REST_Widgets_Controller();
        $controller->register_routes();

        // Block Directory.
        $controller = new WP_REST_Block_Directory_Controller();
        $controller->register_routes();

        // Pattern Directory.
        $controller = new WP_REST_Pattern_Directory_Controller();
        $controller->register_routes();

        // Block Patterns.
        $controller = new WP_REST_Block_Patterns_Controller();
        $controller->register_routes();

        // Block Pattern Categories.
        $controller = new WP_REST_Block_Pattern_Categories_Controller();
        $controller->register_routes();

        // Site Health.
        $site_health = WP_Site_Health::get_instance();
        $controller = new WP_REST_Site_Health_Controller($site_health);
        $controller->register_routes();

        // URL Details.
        $controller = new WP_REST_URL_Details_Controller();
        $controller->register_routes();

        // Menu Locations.
        $controller = new WP_REST_Menu_Locations_Controller();
        $controller->register_routes();

        // Site Editor Export.
        $controller = new WP_REST_Edit_Site_Export_Controller();
        $controller->register_routes();

        // Navigation Fallback.
        $controller = new WP_REST_Navigation_Fallback_Controller();
        $controller->register_routes();
    }

    function rest_api_loaded()
    {
        if(empty($GLOBALS['wp']->query_vars['rest_route']))
        {
            return;
        }

        define('REST_REQUEST', true);

        // Initialize the server.
        $server = rest_get_server();

        // Fire off the request.
        $route = untrailingslashit($GLOBALS['wp']->query_vars['rest_route']);
        if(empty($route))
        {
            $route = '/';
        }
        $server->serve_request($route);

        // We're done.
        die();
    }

    function rest_get_url_prefix()
    {
        return apply_filters('rest_url_prefix', 'wp-json');
    }

    function get_rest_url($blog_id = null, $path = '/', $scheme = 'rest')
    {
        if(empty($path))
        {
            $path = '/';
        }

        $path = '/'.ltrim($path, '/');

        if(is_multisite() && get_blog_option($blog_id, 'permalink_structure') || get_option('permalink_structure'))
        {
            global $wp_rewrite;

            if($wp_rewrite->using_index_permalinks())
            {
                $url = get_home_url($blog_id, $wp_rewrite->index.'/'.rest_get_url_prefix(), $scheme);
            }
            else
            {
                $url = get_home_url($blog_id, rest_get_url_prefix(), $scheme);
            }

            $url .= $path;
        }
        else
        {
            $url = trailingslashit(get_home_url($blog_id, '', $scheme));
            /*
		 * nginx only allows HTTP/1.0 methods when redirecting from / to /index.php.
		 * To work around this, we manually add index.php to the URL, avoiding the redirect.
		 */
            if(! str_ends_with($url, 'index.php'))
            {
                $url .= 'index.php';
            }

            $url = add_query_arg('rest_route', $path, $url);
        }

        if(is_ssl() && isset($_SERVER['SERVER_NAME']) && parse_url(get_home_url($blog_id), PHP_URL_HOST) === $_SERVER['SERVER_NAME'])
        {
            $url = set_url_scheme($url, 'https');
        }

        if(is_admin() && force_ssl_admin())
        {
            /*
		 * In this situation the home URL may be http:, and `is_ssl()` may be false,
		 * but the admin is served over https: (one way or another), so REST API usage
		 * will be blocked by browsers unless it is also served over HTTPS.
		 */
            $url = set_url_scheme($url, 'https');
        }

        return apply_filters('rest_url', $url, $path, $blog_id, $scheme);
    }

    function rest_url($path = '', $scheme = 'rest')
    {
        return get_rest_url(null, $path, $scheme);
    }

    function rest_do_request($request)
    {
        $request = rest_ensure_request($request);

        return rest_get_server()->dispatch($request);
    }

    function rest_get_server()
    {
        /* @var WP_REST_Server $wp_rest_server */ global $wp_rest_server;

        if($wp_rest_server === null)
        {
            $wp_rest_server_class = apply_filters('wp_rest_server_class', 'WP_REST_Server');
            $wp_rest_server = new $wp_rest_server_class();

            do_action('rest_api_init', $wp_rest_server);
        }

        return $wp_rest_server;
    }

    function rest_ensure_request($request)
    {
        if($request instanceof WP_REST_Request)
        {
            return $request;
        }

        if(is_string($request))
        {
            return new WP_REST_Request('GET', $request);
        }

        return new WP_REST_Request('GET', '', $request);
    }

    function rest_ensure_response($response)
    {
        if(is_wp_error($response) || $response instanceof WP_REST_Response)
        {
            return $response;
        }

        /*
	 * While WP_HTTP_Response is the base class of WP_REST_Response, it doesn't provide
	 * all the required methods used in WP_REST_Server::dispatch().
	 */
        if($response instanceof WP_HTTP_Response)
        {
            return new WP_REST_Response($response->get_data(), $response->get_status(), $response->get_headers());
        }

        return new WP_REST_Response($response);
    }

    function rest_handle_deprecated_function($function_name, $replacement, $version)
    {
        if(! WP_DEBUG || headers_sent())
        {
            return;
        }
        if(! empty($replacement))
        {
            /* translators: 1: Function name, 2: WordPress version number, 3: New function name. */
            $string = sprintf(__('%1$s (since %2$s; use %3$s instead)'), $function_name, $version, $replacement);
        }
        else
        {
            /* translators: 1: Function name, 2: WordPress version number. */
            $string = sprintf(__('%1$s (since %2$s; no alternative available)'), $function_name, $version);
        }

        header(sprintf('X-WP-DeprecatedFunction: %s', $string));
    }

    function rest_handle_deprecated_argument($function_name, $message, $version)
    {
        if(! WP_DEBUG || headers_sent())
        {
            return;
        }
        if($message)
        {
            /* translators: 1: Function name, 2: WordPress version number, 3: Error message. */
            $string = sprintf(__('%1$s (since %2$s; %3$s)'), $function_name, $version, $message);
        }
        else
        {
            /* translators: 1: Function name, 2: WordPress version number. */
            $string = sprintf(__('%1$s (since %2$s; no alternative available)'), $function_name, $version);
        }

        header(sprintf('X-WP-DeprecatedParam: %s', $string));
    }

    function rest_handle_doing_it_wrong($function_name, $message, $version)
    {
        if(! WP_DEBUG || headers_sent())
        {
            return;
        }

        if($version)
        {
            /* translators: Developer debugging message. 1: PHP function name, 2: WordPress version number, 3: Explanatory message. */
            $string = __('%1$s (since %2$s; %3$s)');
            $string = sprintf($string, $function_name, $version, $message);
        }
        else
        {
            /* translators: Developer debugging message. 1: PHP function name, 2: Explanatory message. */
            $string = __('%1$s (%2$s)');
            $string = sprintf($string, $function_name, $message);
        }

        header(sprintf('X-WP-DoingItWrong: %s', $string));
    }

    function rest_send_cors_headers($value)
    {
        $origin = get_http_origin();

        if($origin)
        {
            // Requests from file:// and data: URLs send "Origin: null".
            if('null' !== $origin)
            {
                $origin = sanitize_url($origin);
            }
            header('Access-Control-Allow-Origin: '.$origin);
            header('Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT, PATCH, DELETE');
            header('Access-Control-Allow-Credentials: true');
            header('Vary: Origin', false);
        }
        elseif(! headers_sent() && 'GET' === $_SERVER['REQUEST_METHOD'] && ! is_user_logged_in())
        {
            header('Vary: Origin', false);
        }

        return $value;
    }

    function rest_handle_options_request($response, $handler, $request)
    {
        if(! empty($response) || $request->get_method() !== 'OPTIONS')
        {
            return $response;
        }

        $response = new WP_REST_Response();
        $data = [];

        foreach($handler->get_routes() as $route => $endpoints)
        {
            $match = preg_match('@^'.$route.'$@i', $request->get_route(), $matches);

            if(! $match)
            {
                continue;
            }

            $args = [];
            foreach($matches as $param => $value)
            {
                if(! is_int($param))
                {
                    $args[$param] = $value;
                }
            }

            foreach($endpoints as $endpoint)
            {
                // Remove the redundant preg_match() argument.
                unset($args[0]);

                $request->set_url_params($args);
                $request->set_attributes($endpoint);
            }

            $data = $handler->get_data_for_route($route, $endpoints, 'help');
            $response->set_matched_route($route);
            break;
        }

        $response->set_data($data);

        return $response;
    }

    function rest_send_allow_header($response, $server, $request)
    {
        $matched_route = $response->get_matched_route();

        if(! $matched_route)
        {
            return $response;
        }

        $routes = $server->get_routes();

        $allowed_methods = [];

        // Get the allowed methods across the routes.
        foreach($routes[$matched_route] as $_handler)
        {
            foreach($_handler['methods'] as $handler_method => $value)
            {
                if(! empty($_handler['permission_callback']))
                {
                    $permission = call_user_func($_handler['permission_callback'], $request);

                    $allowed_methods[$handler_method] = true === $permission;
                }
                else
                {
                    $allowed_methods[$handler_method] = true;
                }
            }
        }

        // Strip out all the methods that are not allowed (false values).
        $allowed_methods = array_filter($allowed_methods);

        if($allowed_methods)
        {
            $response->header('Allow', implode(', ', array_map('strtoupper', array_keys($allowed_methods))));
        }

        return $response;
    }

    function _rest_array_intersect_key_recursive($array1, $array2)
    {
        $array1 = array_intersect_key($array1, $array2);
        foreach($array1 as $key => $value)
        {
            if(is_array($value) && is_array($array2[$key]))
            {
                $array1[$key] = _rest_array_intersect_key_recursive($value, $array2[$key]);
            }
        }

        return $array1;
    }

    function rest_filter_response_fields($response, $server, $request)
    {
        if(! isset($request['_fields']) || $response->is_error())
        {
            return $response;
        }

        $data = $response->get_data();

        $fields = wp_parse_list($request['_fields']);

        if(0 === count($fields))
        {
            return $response;
        }

        // Trim off outside whitespace from the comma delimited list.
        $fields = array_map('trim', $fields);

        // Create nested array of accepted field hierarchy.
        $fields_as_keyed = [];
        foreach($fields as $field)
        {
            $parts = explode('.', $field);
            $ref = &$fields_as_keyed;
            while(count($parts) > 1)
            {
                $next = array_shift($parts);
                if(isset($ref[$next]) && true === $ref[$next])
                {
                    // Skip any sub-properties if their parent prop is already marked for inclusion.
                    break 2;
                }
                $ref[$next] = isset($ref[$next]) ? $ref[$next] : [];
                $ref = &$ref[$next];
            }
            $last = array_shift($parts);
            $ref[$last] = true;
        }

        if(wp_is_numeric_array($data))
        {
            $new_data = [];
            foreach($data as $item)
            {
                $new_data[] = _rest_array_intersect_key_recursive($item, $fields_as_keyed);
            }
        }
        else
        {
            $new_data = _rest_array_intersect_key_recursive($data, $fields_as_keyed);
        }

        $response->set_data($new_data);

        return $response;
    }

    function rest_is_field_included($field, $fields)
    {
        if(in_array($field, $fields, true))
        {
            return true;
        }

        foreach($fields as $accepted_field)
        {
            /*
		 * Check to see if $field is the parent of any item in $fields.
		 * A field "parent" should be accepted if "parent.child" is accepted.
		 */
            /*
		 * Conversely, if "parent" is accepted, all "parent.child" fields
		 * should also be accepted.
		 */
            if(str_starts_with($accepted_field, "$field.") || str_starts_with($field, "$accepted_field."))
            {
                return true;
            }
        }

        return false;
    }

    function rest_output_rsd()
    {
        $api_root = get_rest_url();

        if(empty($api_root))
        {
            return;
        }
        ?>
        <api name="WP-API" blogID="1" preferred="false" apiLink="<?php echo esc_url($api_root); ?>"/>
        <?php
    }

    function rest_output_link_wp_head()
    {
        $api_root = get_rest_url();

        if(empty($api_root))
        {
            return;
        }

        printf('<link rel="https://api.w.org/" href="%s" />', esc_url($api_root));

        $resource = rest_get_queried_resource_route();

        if($resource)
        {
            printf('<link rel="alternate" type="application/json" href="%s" />', esc_url(rest_url($resource)));
        }
    }

    function rest_output_link_header()
    {
        if(headers_sent())
        {
            return;
        }

        $api_root = get_rest_url();

        if(empty($api_root))
        {
            return;
        }

        header(sprintf('Link: <%s>; rel="https://api.w.org/"', sanitize_url($api_root)), false);

        $resource = rest_get_queried_resource_route();

        if($resource)
        {
            header(sprintf('Link: <%s>; rel="alternate"; type="application/json"', sanitize_url(rest_url($resource))), false);
        }
    }

    function rest_cookie_check_errors($result)
    {
        if(! empty($result))
        {
            return $result;
        }

        global $wp_rest_auth_cookie;

        /*
	 * Is cookie authentication being used? (If we get an auth
	 * error, but we're still logged in, another authentication
	 * must have been used).
	 */
        if(true !== $wp_rest_auth_cookie && is_user_logged_in())
        {
            return $result;
        }

        // Determine if there is a nonce.
        $nonce = null;

        if(isset($_REQUEST['_wpnonce']))
        {
            $nonce = $_REQUEST['_wpnonce'];
        }
        elseif(isset($_SERVER['HTTP_X_WP_NONCE']))
        {
            $nonce = $_SERVER['HTTP_X_WP_NONCE'];
        }

        if(null === $nonce)
        {
            // No nonce at all, so act as if it's an unauthenticated request.
            wp_set_current_user(0);

            return true;
        }

        // Check the nonce.
        $result = wp_verify_nonce($nonce, 'wp_rest');

        if(! $result)
        {
            return new WP_Error('rest_cookie_invalid_nonce', __('Cookie check failed'), ['status' => 403]);
        }

        // Send a refreshed nonce in header.
        rest_get_server()->send_header('X-WP-Nonce', wp_create_nonce('wp_rest'));

        return true;
    }

    function rest_cookie_collect_status()
    {
        global $wp_rest_auth_cookie;

        $status_type = current_action();

        if('auth_cookie_valid' !== $status_type)
        {
            $wp_rest_auth_cookie = substr($status_type, 12);

            return;
        }

        $wp_rest_auth_cookie = true;
    }

    function rest_application_password_collect_status($user_or_error, $app_password = [])
    {
        global $wp_rest_application_password_status, $wp_rest_application_password_uuid;

        $wp_rest_application_password_status = $user_or_error;

        if(empty($app_password['uuid']))
        {
            $wp_rest_application_password_uuid = null;
        }
        else
        {
            $wp_rest_application_password_uuid = $app_password['uuid'];
        }
    }

    function rest_get_authenticated_app_password()
    {
        global $wp_rest_application_password_uuid;

        return $wp_rest_application_password_uuid;
    }

    function rest_application_password_check_errors($result)
    {
        global $wp_rest_application_password_status;

        if(! empty($result))
        {
            return $result;
        }

        if(is_wp_error($wp_rest_application_password_status))
        {
            $data = $wp_rest_application_password_status->get_error_data();

            if(! isset($data['status']))
            {
                $data['status'] = 401;
            }

            $wp_rest_application_password_status->add_data($data);

            return $wp_rest_application_password_status;
        }

        if($wp_rest_application_password_status instanceof WP_User)
        {
            return true;
        }

        return $result;
    }

    function rest_add_application_passwords_to_index($response)
    {
        if(! wp_is_application_passwords_available())
        {
            return $response;
        }

        $response->data['authentication']['application-passwords'] = [
            'endpoints' => [
                'authorization' => admin_url('authorize-application.php'),
            ],
        ];

        return $response;
    }

    function rest_get_avatar_urls($id_or_email)
    {
        $avatar_sizes = rest_get_avatar_sizes();

        $urls = [];
        foreach($avatar_sizes as $size)
        {
            $urls[$size] = get_avatar_url($id_or_email, ['size' => $size]);
        }

        return $urls;
    }

    function rest_get_avatar_sizes()
    {
        return apply_filters('rest_avatar_sizes', [24, 48, 96]);
    }

    function rest_parse_date($date, $force_utc = false)
    {
        if($force_utc)
        {
            $date = preg_replace('/[+-]\d+:?\d+$/', '+00:00', $date);
        }

        $regex = '#^\d{4}-\d{2}-\d{2}[Tt ]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}(?::\d{2})?)?$#';

        if(! preg_match($regex, $date, $matches))
        {
            return false;
        }

        return strtotime($date);
    }

    function rest_parse_hex_color($color)
    {
        $regex = '|^#([A-Fa-f0-9]{3}){1,2}$|';
        if(! preg_match($regex, $color, $matches))
        {
            return false;
        }

        return $color;
    }

    function rest_get_date_with_gmt($date, $is_utc = false)
    {
        /*
	 * Whether or not the original date actually has a timezone string
	 * changes the way we need to do timezone conversion.
	 * Store this info before parsing the date, and use it later.
	 */
        $has_timezone = preg_match('#(Z|[+-]\d{2}(:\d{2})?)$#', $date);

        $date = rest_parse_date($date);

        if(empty($date))
        {
            return null;
        }

        /*
	 * At this point $date could either be a local date (if we were passed
	 * a *local* date without a timezone offset) or a UTC date (otherwise).
	 * Timezone conversion needs to be handled differently between these two cases.
	 */
        if(! $is_utc && ! $has_timezone)
        {
            $local = gmdate('Y-m-d H:i:s', $date);
            $utc = get_gmt_from_date($local);
        }
        else
        {
            $utc = gmdate('Y-m-d H:i:s', $date);
            $local = get_date_from_gmt($utc);
        }

        return [$local, $utc];
    }

    function rest_authorization_required_code()
    {
        if(is_user_logged_in())
        {
            return 403;
        }

        return 401;
    }

    function rest_validate_request_arg($value, $request, $param)
    {
        $attributes = $request->get_attributes();
        if(! isset($attributes['args'][$param]) || ! is_array($attributes['args'][$param]))
        {
            return true;
        }
        $args = $attributes['args'][$param];

        return rest_validate_value_from_schema($value, $args, $param);
    }

    function rest_sanitize_request_arg($value, $request, $param)
    {
        $attributes = $request->get_attributes();
        if(! isset($attributes['args'][$param]) || ! is_array($attributes['args'][$param]))
        {
            return $value;
        }
        $args = $attributes['args'][$param];

        return rest_sanitize_value_from_schema($value, $args, $param);
    }

    function rest_parse_request_arg($value, $request, $param)
    {
        $is_valid = rest_validate_request_arg($value, $request, $param);

        if(is_wp_error($is_valid))
        {
            return $is_valid;
        }

        $value = rest_sanitize_request_arg($value, $request, $param);

        return $value;
    }

    function rest_is_ip_address($ip)
    {
        $ipv4_pattern = '/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/';

        if(! preg_match($ipv4_pattern, $ip) && ! WpOrg\Requests\Ipv6::check_ipv6($ip))
        {
            return false;
        }

        return $ip;
    }

    function rest_sanitize_boolean($value)
    {
        // String values are translated to `true`; make sure 'false' is false.
        if(is_string($value))
        {
            $value = strtolower($value);
            if(in_array($value, ['false', '0'], true))
            {
                $value = false;
            }
        }

        // Everything else will map nicely to boolean.
        return (bool) $value;
    }

    function rest_is_boolean($maybe_bool)
    {
        if(is_bool($maybe_bool))
        {
            return true;
        }

        if(is_string($maybe_bool))
        {
            $maybe_bool = strtolower($maybe_bool);

            $valid_boolean_values = [
                'false',
                'true',
                '0',
                '1',
            ];

            return in_array($maybe_bool, $valid_boolean_values, true);
        }

        if(is_int($maybe_bool))
        {
            return in_array($maybe_bool, [0, 1], true);
        }

        return false;
    }

    function rest_is_integer($maybe_integer)
    {
        return is_numeric($maybe_integer) && round((float) $maybe_integer) === (float) $maybe_integer;
    }

    function rest_is_array($maybe_array)
    {
        if(is_scalar($maybe_array))
        {
            $maybe_array = wp_parse_list($maybe_array);
        }

        return wp_is_numeric_array($maybe_array);
    }

    function rest_sanitize_array($maybe_array)
    {
        if(is_scalar($maybe_array))
        {
            return wp_parse_list($maybe_array);
        }

        if(! is_array($maybe_array))
        {
            return [];
        }

        // Normalize to numeric array so nothing unexpected is in the keys.
        return array_values($maybe_array);
    }

    function rest_is_object($maybe_object)
    {
        if('' === $maybe_object || $maybe_object instanceof stdClass)
        {
            return true;
        }

        if($maybe_object instanceof JsonSerializable)
        {
            $maybe_object = $maybe_object->jsonSerialize();
        }

        return is_array($maybe_object);
    }

    function rest_sanitize_object($maybe_object)
    {
        if('' === $maybe_object)
        {
            return [];
        }

        if($maybe_object instanceof stdClass)
        {
            return (array) $maybe_object;
        }

        if($maybe_object instanceof JsonSerializable)
        {
            $maybe_object = $maybe_object->jsonSerialize();
        }

        if(! is_array($maybe_object))
        {
            return [];
        }

        return $maybe_object;
    }

    function rest_get_best_type_for_value($value, $types)
    {
        static $checks = [
            'array' => 'rest_is_array',
            'object' => 'rest_is_object',
            'integer' => 'rest_is_integer',
            'number' => 'is_numeric',
            'boolean' => 'rest_is_boolean',
            'string' => 'is_string',
            'null' => 'is_null',
        ];

        /*
	 * Both arrays and objects allow empty strings to be converted to their types.
	 * But the best answer for this type is a string.
	 */
        if('' === $value && in_array('string', $types, true))
        {
            return 'string';
        }

        foreach($types as $type)
        {
            if(isset($checks[$type]) && $checks[$type]($value))
            {
                return $type;
            }
        }

        return '';
    }

    function rest_handle_multi_type_schema($value, $args, $param = '')
    {
        $allowed_types = ['array', 'object', 'string', 'number', 'integer', 'boolean', 'null'];
        $invalid_types = array_diff($args['type'], $allowed_types);

        if($invalid_types)
        {
            _doing_it_wrong(__FUNCTION__, /* translators: 1: Parameter, 2: List of allowed types. */ wp_sprintf(__('The "type" schema keyword for %1$s can only contain the built-in types: %2$l.'), $param, $allowed_types), '5.5.0');
        }

        $best_type = rest_get_best_type_for_value($value, $args['type']);

        if(! $best_type)
        {
            if(! $invalid_types)
            {
                return '';
            }

            // Backward compatibility for previous behavior which allowed the value if there was an invalid type used.
            $best_type = reset($invalid_types);
        }

        return $best_type;
    }

    function rest_validate_array_contains_unique_items($input_array)
    {
        $seen = [];

        foreach($input_array as $item)
        {
            $stabilized = rest_stabilize_value($item);
            $key = serialize($stabilized);

            if(! isset($seen[$key]))
            {
                $seen[$key] = true;

                continue;
            }

            return false;
        }

        return true;
    }

    function rest_stabilize_value($value)
    {
        if(is_scalar($value) || is_null($value))
        {
            return $value;
        }

        if(is_object($value))
        {
            _doing_it_wrong(__FUNCTION__, __('Cannot stabilize objects. Convert the object to an array first.'), '5.5.0');

            return $value;
        }

        ksort($value);

        foreach($value as $k => $v)
        {
            $value[$k] = rest_stabilize_value($v);
        }

        return $value;
    }

    function rest_validate_json_schema_pattern($pattern, $value)
    {
        $escaped_pattern = str_replace('#', '\\#', $pattern);

        return 1 === preg_match('#'.$escaped_pattern.'#u', $value);
    }

    function rest_find_matching_pattern_property_schema($property, $args)
    {
        if(isset($args['patternProperties']))
        {
            foreach($args['patternProperties'] as $pattern => $child_schema)
            {
                if(rest_validate_json_schema_pattern($pattern, $property))
                {
                    return $child_schema;
                }
            }
        }

        return null;
    }

    function rest_format_combining_operation_error($param, $error)
    {
        $position = $error['index'];
        $reason = $error['error_object']->get_error_message();

        if(isset($error['schema']['title']))
        {
            $title = $error['schema']['title'];

            return new WP_Error('rest_no_matching_schema', /* translators: 1: Parameter, 2: Schema title, 3: Reason. */ sprintf(__('%1$s is not a valid %2$s. Reason: %3$s'), $param, $title, $reason), ['position' => $position]);
        }

        return new WP_Error('rest_no_matching_schema', /* translators: 1: Parameter, 2: Reason. */ sprintf(__('%1$s does not match the expected format. Reason: %2$s'), $param, $reason), ['position' => $position]);
    }

    function rest_get_combining_operation_error($value, $param, $errors)
    {
        // If there is only one error, simply return it.
        if(1 === count($errors))
        {
            return rest_format_combining_operation_error($param, $errors[0]);
        }

        // Filter out all errors related to type validation.
        $filtered_errors = [];
        foreach($errors as $error)
        {
            $error_code = $error['error_object']->get_error_code();
            $error_data = $error['error_object']->get_error_data();

            if('rest_invalid_type' !== $error_code || (isset($error_data['param']) && $param !== $error_data['param']))
            {
                $filtered_errors[] = $error;
            }
        }

        // If there is only one error left, simply return it.
        if(1 === count($filtered_errors))
        {
            return rest_format_combining_operation_error($param, $filtered_errors[0]);
        }

        // If there are only errors related to object validation, try choosing the most appropriate one.
        if(count($filtered_errors) > 1 && 'object' === $filtered_errors[0]['schema']['type'])
        {
            $result = null;
            $number = 0;

            foreach($filtered_errors as $error)
            {
                if(isset($error['schema']['properties']))
                {
                    $n = count(array_intersect_key($error['schema']['properties'], $value));
                    if($n > $number)
                    {
                        $result = $error;
                        $number = $n;
                    }
                }
            }

            if(null !== $result)
            {
                return rest_format_combining_operation_error($param, $result);
            }
        }

        // If each schema has a title, include those titles in the error message.
        $schema_titles = [];
        foreach($errors as $error)
        {
            if(isset($error['schema']['title']))
            {
                $schema_titles[] = $error['schema']['title'];
            }
        }

        if(count($schema_titles) === count($errors))
        {
            /* translators: 1: Parameter, 2: Schema titles. */
            return new WP_Error('rest_no_matching_schema', wp_sprintf(__('%1$s is not a valid %2$l.'), $param, $schema_titles));
        }

        /* translators: %s: Parameter. */

        return new WP_Error('rest_no_matching_schema', sprintf(__('%s does not match any of the expected formats.'), $param));
    }

    function rest_find_any_matching_schema($value, $args, $param)
    {
        $errors = [];

        foreach($args['anyOf'] as $index => $schema)
        {
            if(! isset($schema['type']) && isset($args['type']))
            {
                $schema['type'] = $args['type'];
            }

            $is_valid = rest_validate_value_from_schema($value, $schema, $param);
            if(! is_wp_error($is_valid))
            {
                return $schema;
            }

            $errors[] = [
                'error_object' => $is_valid,
                'schema' => $schema,
                'index' => $index,
            ];
        }

        return rest_get_combining_operation_error($value, $param, $errors);
    }

    function rest_find_one_matching_schema($value, $args, $param, $stop_after_first_match = false)
    {
        $matching_schemas = [];
        $errors = [];

        foreach($args['oneOf'] as $index => $schema)
        {
            if(! isset($schema['type']) && isset($args['type']))
            {
                $schema['type'] = $args['type'];
            }

            $is_valid = rest_validate_value_from_schema($value, $schema, $param);
            if(is_wp_error($is_valid))
            {
                $errors[] = [
                    'error_object' => $is_valid,
                    'schema' => $schema,
                    'index' => $index,
                ];
            }
            else
            {
                if($stop_after_first_match)
                {
                    return $schema;
                }

                $matching_schemas[] = [
                    'schema_object' => $schema,
                    'index' => $index,
                ];
            }
        }

        if(! $matching_schemas)
        {
            return rest_get_combining_operation_error($value, $param, $errors);
        }

        if(count($matching_schemas) > 1)
        {
            $schema_positions = [];
            $schema_titles = [];

            foreach($matching_schemas as $schema)
            {
                $schema_positions[] = $schema['index'];

                if(isset($schema['schema_object']['title']))
                {
                    $schema_titles[] = $schema['schema_object']['title'];
                }
            }

            // If each schema has a title, include those titles in the error message.
            if(count($schema_titles) === count($matching_schemas))
            {
                return new WP_Error('rest_one_of_multiple_matches', /* translators: 1: Parameter, 2: Schema titles. */ wp_sprintf(__('%1$s matches %2$l, but should match only one.'), $param, $schema_titles), ['positions' => $schema_positions]);
            }

            return new WP_Error('rest_one_of_multiple_matches', /* translators: %s: Parameter. */ sprintf(__('%s matches more than one of the expected formats.'), $param), ['positions' => $schema_positions]);
        }

        return $matching_schemas[0]['schema_object'];
    }

    function rest_are_values_equal($value1, $value2)
    {
        if(is_array($value1) && is_array($value2))
        {
            if(count($value1) !== count($value2))
            {
                return false;
            }

            foreach($value1 as $index => $value)
            {
                if(! array_key_exists($index, $value2) || ! rest_are_values_equal($value, $value2[$index]))
                {
                    return false;
                }
            }

            return true;
        }

        if(is_int($value1) && is_float($value2) || is_float($value1) && is_int($value2))
        {
            return (float) $value1 === (float) $value2;
        }

        return $value1 === $value2;
    }

    function rest_validate_enum($value, $args, $param)
    {
        $sanitized_value = rest_sanitize_value_from_schema($value, $args, $param);
        if(is_wp_error($sanitized_value))
        {
            return $sanitized_value;
        }

        foreach($args['enum'] as $enum_value)
        {
            if(rest_are_values_equal($sanitized_value, $enum_value))
            {
                return true;
            }
        }

        $encoded_enum_values = [];
        foreach($args['enum'] as $enum_value)
        {
            $encoded_enum_values[] = is_scalar($enum_value) ? $enum_value : wp_json_encode($enum_value);
        }

        if(count($encoded_enum_values) === 1)
        {
            /* translators: 1: Parameter, 2: Valid values. */
            return new WP_Error('rest_not_in_enum', wp_sprintf(__('%1$s is not %2$s.'), $param, $encoded_enum_values[0]));
        }

        /* translators: 1: Parameter, 2: List of valid values. */

        return new WP_Error('rest_not_in_enum', wp_sprintf(__('%1$s is not one of %2$l.'), $param, $encoded_enum_values));
    }

    function rest_get_allowed_schema_keywords()
    {
        return [
            'title',
            'description',
            'default',
            'type',
            'format',
            'enum',
            'items',
            'properties',
            'additionalProperties',
            'patternProperties',
            'minProperties',
            'maxProperties',
            'minimum',
            'maximum',
            'exclusiveMinimum',
            'exclusiveMaximum',
            'multipleOf',
            'minLength',
            'maxLength',
            'pattern',
            'minItems',
            'maxItems',
            'uniqueItems',
            'anyOf',
            'oneOf',
        ];
    }

    function rest_validate_value_from_schema($value, $args, $param = '')
    {
        if(isset($args['anyOf']))
        {
            $matching_schema = rest_find_any_matching_schema($value, $args, $param);
            if(is_wp_error($matching_schema))
            {
                return $matching_schema;
            }

            if(! isset($args['type']) && isset($matching_schema['type']))
            {
                $args['type'] = $matching_schema['type'];
            }
        }

        if(isset($args['oneOf']))
        {
            $matching_schema = rest_find_one_matching_schema($value, $args, $param);
            if(is_wp_error($matching_schema))
            {
                return $matching_schema;
            }

            if(! isset($args['type']) && isset($matching_schema['type']))
            {
                $args['type'] = $matching_schema['type'];
            }
        }

        $allowed_types = ['array', 'object', 'string', 'number', 'integer', 'boolean', 'null'];

        if(! isset($args['type']))
        {
            /* translators: %s: Parameter. */
            _doing_it_wrong(__FUNCTION__, sprintf(__('The "type" schema keyword for %s is required.'), $param), '5.5.0');
        }

        if(is_array($args['type']))
        {
            $best_type = rest_handle_multi_type_schema($value, $args, $param);

            if(! $best_type)
            {
                return new WP_Error('rest_invalid_type', /* translators: 1: Parameter, 2: List of types. */ sprintf(__('%1$s is not of type %2$s.'), $param, implode(',', $args['type'])), ['param' => $param]);
            }

            $args['type'] = $best_type;
        }

        if(! in_array($args['type'], $allowed_types, true))
        {
            _doing_it_wrong(__FUNCTION__, /* translators: 1: Parameter, 2: The list of allowed types. */ wp_sprintf(__('The "type" schema keyword for %1$s can only be one of the built-in types: %2$l.'), $param, $allowed_types), '5.5.0');
        }

        switch($args['type'])
        {
            case 'null':
                $is_valid = rest_validate_null_value_from_schema($value, $param);
                break;
            case 'boolean':
                $is_valid = rest_validate_boolean_value_from_schema($value, $param);
                break;
            case 'object':
                $is_valid = rest_validate_object_value_from_schema($value, $args, $param);
                break;
            case 'array':
                $is_valid = rest_validate_array_value_from_schema($value, $args, $param);
                break;
            case 'number':
                $is_valid = rest_validate_number_value_from_schema($value, $args, $param);
                break;
            case 'string':
                $is_valid = rest_validate_string_value_from_schema($value, $args, $param);
                break;
            case 'integer':
                $is_valid = rest_validate_integer_value_from_schema($value, $args, $param);
                break;
            default:
                $is_valid = true;
                break;
        }

        if(is_wp_error($is_valid))
        {
            return $is_valid;
        }

        if(! empty($args['enum']))
        {
            $enum_contains_value = rest_validate_enum($value, $args, $param);
            if(is_wp_error($enum_contains_value))
            {
                return $enum_contains_value;
            }
        }

        /*
	 * The "format" keyword should only be applied to strings. However, for backward compatibility,
	 * we allow the "format" keyword if the type keyword was not specified, or was set to an invalid value.
	 */
        if(isset($args['format']) && (! isset($args['type']) || 'string' === $args['type'] || ! in_array($args['type'], $allowed_types, true)))
        {
            switch($args['format'])
            {
                case 'hex-color':
                    if(! rest_parse_hex_color($value))
                    {
                        return new WP_Error('rest_invalid_hex_color', __('Invalid hex color.'));
                    }
                    break;

                case 'date-time':
                    if(! rest_parse_date($value))
                    {
                        return new WP_Error('rest_invalid_date', __('Invalid date.'));
                    }
                    break;

                case 'email':
                    if(! is_email($value))
                    {
                        return new WP_Error('rest_invalid_email', __('Invalid email address.'));
                    }
                    break;
                case 'ip':
                    if(! rest_is_ip_address($value))
                    {
                        /* translators: %s: IP address. */
                        return new WP_Error('rest_invalid_ip', sprintf(__('%s is not a valid IP address.'), $param));
                    }
                    break;
                case 'uuid':
                    if(! wp_is_uuid($value))
                    {
                        /* translators: %s: The name of a JSON field expecting a valid UUID. */
                        return new WP_Error('rest_invalid_uuid', sprintf(__('%s is not a valid UUID.'), $param));
                    }
                    break;
            }
        }

        return true;
    }

    function rest_validate_null_value_from_schema($value, $param)
    {
        if(null !== $value)
        {
            return new WP_Error('rest_invalid_type', /* translators: 1: Parameter, 2: Type name. */ sprintf(__('%1$s is not of type %2$s.'), $param, 'null'), ['param' => $param]);
        }

        return true;
    }

    function rest_validate_boolean_value_from_schema($value, $param)
    {
        if(! rest_is_boolean($value))
        {
            return new WP_Error('rest_invalid_type', /* translators: 1: Parameter, 2: Type name. */ sprintf(__('%1$s is not of type %2$s.'), $param, 'boolean'), ['param' => $param]);
        }

        return true;
    }

    function rest_validate_object_value_from_schema($value, $args, $param)
    {
        if(! rest_is_object($value))
        {
            return new WP_Error('rest_invalid_type', /* translators: 1: Parameter, 2: Type name. */ sprintf(__('%1$s is not of type %2$s.'), $param, 'object'), ['param' => $param]);
        }

        $value = rest_sanitize_object($value);

        if(isset($args['required']) && is_array($args['required']))
        { // schema version 4
            foreach($args['required'] as $name)
            {
                if(! array_key_exists($name, $value))
                {
                    return new WP_Error('rest_property_required', /* translators: 1: Property of an object, 2: Parameter. */ sprintf(__('%1$s is a required property of %2$s.'), $name, $param));
                }
            }
        }
        elseif(isset($args['properties']))
        { // schema version 3
            foreach($args['properties'] as $name => $property)
            {
                if(isset($property['required']) && true === $property['required'] && ! array_key_exists($name, $value))
                {
                    return new WP_Error('rest_property_required', /* translators: 1: Property of an object, 2: Parameter. */ sprintf(__('%1$s is a required property of %2$s.'), $name, $param));
                }
            }
        }

        foreach($value as $property => $v)
        {
            if(isset($args['properties'][$property]))
            {
                $is_valid = rest_validate_value_from_schema($v, $args['properties'][$property], $param.'['.$property.']');
                if(is_wp_error($is_valid))
                {
                    return $is_valid;
                }
                continue;
            }

            $pattern_property_schema = rest_find_matching_pattern_property_schema($property, $args);
            if(null !== $pattern_property_schema)
            {
                $is_valid = rest_validate_value_from_schema($v, $pattern_property_schema, $param.'['.$property.']');
                if(is_wp_error($is_valid))
                {
                    return $is_valid;
                }
                continue;
            }

            if(isset($args['additionalProperties']))
            {
                if(false === $args['additionalProperties'])
                {
                    return new WP_Error('rest_additional_properties_forbidden', /* translators: %s: Property of an object. */ sprintf(__('%1$s is not a valid property of Object.'), $property));
                }

                if(is_array($args['additionalProperties']))
                {
                    $is_valid = rest_validate_value_from_schema($v, $args['additionalProperties'], $param.'['.$property.']');
                    if(is_wp_error($is_valid))
                    {
                        return $is_valid;
                    }
                }
            }
        }

        if(isset($args['minProperties']) && count($value) < $args['minProperties'])
        {
            return new WP_Error('rest_too_few_properties', sprintf(/* translators: 1: Parameter, 2: Number. */ _n('%1$s must contain at least %2$s property.', '%1$s must contain at least %2$s properties.', $args['minProperties']), $param, number_format_i18n($args['minProperties'])));
        }

        if(isset($args['maxProperties']) && count($value) > $args['maxProperties'])
        {
            return new WP_Error('rest_too_many_properties', sprintf(/* translators: 1: Parameter, 2: Number. */ _n('%1$s must contain at most %2$s property.', '%1$s must contain at most %2$s properties.', $args['maxProperties']), $param, number_format_i18n($args['maxProperties'])));
        }

        return true;
    }

    function rest_validate_array_value_from_schema($value, $args, $param)
    {
        if(! rest_is_array($value))
        {
            return new WP_Error('rest_invalid_type', /* translators: 1: Parameter, 2: Type name. */ sprintf(__('%1$s is not of type %2$s.'), $param, 'array'), ['param' => $param]);
        }

        $value = rest_sanitize_array($value);

        if(isset($args['items']))
        {
            foreach($value as $index => $v)
            {
                $is_valid = rest_validate_value_from_schema($v, $args['items'], $param.'['.$index.']');
                if(is_wp_error($is_valid))
                {
                    return $is_valid;
                }
            }
        }

        if(isset($args['minItems']) && count($value) < $args['minItems'])
        {
            return new WP_Error('rest_too_few_items', sprintf(/* translators: 1: Parameter, 2: Number. */ _n('%1$s must contain at least %2$s item.', '%1$s must contain at least %2$s items.', $args['minItems']), $param, number_format_i18n($args['minItems'])));
        }

        if(isset($args['maxItems']) && count($value) > $args['maxItems'])
        {
            return new WP_Error('rest_too_many_items', sprintf(/* translators: 1: Parameter, 2: Number. */ _n('%1$s must contain at most %2$s item.', '%1$s must contain at most %2$s items.', $args['maxItems']), $param, number_format_i18n($args['maxItems'])));
        }

        if(! empty($args['uniqueItems']) && ! rest_validate_array_contains_unique_items($value))
        {
            /* translators: %s: Parameter. */
            return new WP_Error('rest_duplicate_items', sprintf(__('%s has duplicate items.'), $param));
        }

        return true;
    }

    function rest_validate_number_value_from_schema($value, $args, $param)
    {
        if(! is_numeric($value))
        {
            return new WP_Error('rest_invalid_type', /* translators: 1: Parameter, 2: Type name. */ sprintf(__('%1$s is not of type %2$s.'), $param, $args['type']), ['param' => $param]);
        }

        if(isset($args['multipleOf']) && fmod($value, $args['multipleOf']) !== 0.0)
        {
            return new WP_Error('rest_invalid_multiple', /* translators: 1: Parameter, 2: Multiplier. */ sprintf(__('%1$s must be a multiple of %2$s.'), $param, $args['multipleOf']));
        }

        if(isset($args['minimum']) && ! isset($args['maximum']))
        {
            if(! empty($args['exclusiveMinimum']) && $value <= $args['minimum'])
            {
                return new WP_Error('rest_out_of_bounds', /* translators: 1: Parameter, 2: Minimum number. */ sprintf(__('%1$s must be greater than %2$d'), $param, $args['minimum']));
            }

            if(empty($args['exclusiveMinimum']) && $value < $args['minimum'])
            {
                return new WP_Error('rest_out_of_bounds', /* translators: 1: Parameter, 2: Minimum number. */ sprintf(__('%1$s must be greater than or equal to %2$d'), $param, $args['minimum']));
            }
        }

        if(isset($args['maximum']) && ! isset($args['minimum']))
        {
            if(! empty($args['exclusiveMaximum']) && $value >= $args['maximum'])
            {
                return new WP_Error('rest_out_of_bounds', /* translators: 1: Parameter, 2: Maximum number. */ sprintf(__('%1$s must be less than %2$d'), $param, $args['maximum']));
            }

            if(empty($args['exclusiveMaximum']) && $value > $args['maximum'])
            {
                return new WP_Error('rest_out_of_bounds', /* translators: 1: Parameter, 2: Maximum number. */ sprintf(__('%1$s must be less than or equal to %2$d'), $param, $args['maximum']));
            }
        }

        if(isset($args['minimum'], $args['maximum']))
        {
            if(! empty($args['exclusiveMinimum']) && ! empty($args['exclusiveMaximum']))
            {
                if($value >= $args['maximum'] || $value <= $args['minimum'])
                {
                    return new WP_Error('rest_out_of_bounds', sprintf(/* translators: 1: Parameter, 2: Minimum number, 3: Maximum number. */ __('%1$s must be between %2$d (exclusive) and %3$d (exclusive)'), $param, $args['minimum'], $args['maximum']));
                }
            }

            if(! empty($args['exclusiveMinimum']) && empty($args['exclusiveMaximum']))
            {
                if($value > $args['maximum'] || $value <= $args['minimum'])
                {
                    return new WP_Error('rest_out_of_bounds', sprintf(/* translators: 1: Parameter, 2: Minimum number, 3: Maximum number. */ __('%1$s must be between %2$d (exclusive) and %3$d (inclusive)'), $param, $args['minimum'], $args['maximum']));
                }
            }

            if(! empty($args['exclusiveMaximum']) && empty($args['exclusiveMinimum']))
            {
                if($value >= $args['maximum'] || $value < $args['minimum'])
                {
                    return new WP_Error('rest_out_of_bounds', sprintf(/* translators: 1: Parameter, 2: Minimum number, 3: Maximum number. */ __('%1$s must be between %2$d (inclusive) and %3$d (exclusive)'), $param, $args['minimum'], $args['maximum']));
                }
            }

            if(empty($args['exclusiveMinimum']) && empty($args['exclusiveMaximum']))
            {
                if($value > $args['maximum'] || $value < $args['minimum'])
                {
                    return new WP_Error('rest_out_of_bounds', sprintf(/* translators: 1: Parameter, 2: Minimum number, 3: Maximum number. */ __('%1$s must be between %2$d (inclusive) and %3$d (inclusive)'), $param, $args['minimum'], $args['maximum']));
                }
            }
        }

        return true;
    }

    function rest_validate_string_value_from_schema($value, $args, $param)
    {
        if(! is_string($value))
        {
            return new WP_Error('rest_invalid_type', /* translators: 1: Parameter, 2: Type name. */ sprintf(__('%1$s is not of type %2$s.'), $param, 'string'), ['param' => $param]);
        }

        if(isset($args['minLength']) && mb_strlen($value) < $args['minLength'])
        {
            return new WP_Error('rest_too_short', sprintf(/* translators: 1: Parameter, 2: Number of characters. */ _n('%1$s must be at least %2$s character long.', '%1$s must be at least %2$s characters long.', $args['minLength']), $param, number_format_i18n($args['minLength'])));
        }

        if(isset($args['maxLength']) && mb_strlen($value) > $args['maxLength'])
        {
            return new WP_Error('rest_too_long', sprintf(/* translators: 1: Parameter, 2: Number of characters. */ _n('%1$s must be at most %2$s character long.', '%1$s must be at most %2$s characters long.', $args['maxLength']), $param, number_format_i18n($args['maxLength'])));
        }

        if(isset($args['pattern']) && ! rest_validate_json_schema_pattern($args['pattern'], $value))
        {
            return new WP_Error('rest_invalid_pattern', /* translators: 1: Parameter, 2: Pattern. */ sprintf(__('%1$s does not match pattern %2$s.'), $param, $args['pattern']));
        }

        return true;
    }

    function rest_validate_integer_value_from_schema($value, $args, $param)
    {
        $is_valid_number = rest_validate_number_value_from_schema($value, $args, $param);
        if(is_wp_error($is_valid_number))
        {
            return $is_valid_number;
        }

        if(! rest_is_integer($value))
        {
            return new WP_Error('rest_invalid_type', /* translators: 1: Parameter, 2: Type name. */ sprintf(__('%1$s is not of type %2$s.'), $param, 'integer'), ['param' => $param]);
        }

        return true;
    }

    function rest_sanitize_value_from_schema($value, $args, $param = '')
    {
        if(isset($args['anyOf']))
        {
            $matching_schema = rest_find_any_matching_schema($value, $args, $param);
            if(is_wp_error($matching_schema))
            {
                return $matching_schema;
            }

            if(! isset($args['type']))
            {
                $args['type'] = $matching_schema['type'];
            }

            $value = rest_sanitize_value_from_schema($value, $matching_schema, $param);
        }

        if(isset($args['oneOf']))
        {
            $matching_schema = rest_find_one_matching_schema($value, $args, $param);
            if(is_wp_error($matching_schema))
            {
                return $matching_schema;
            }

            if(! isset($args['type']))
            {
                $args['type'] = $matching_schema['type'];
            }

            $value = rest_sanitize_value_from_schema($value, $matching_schema, $param);
        }

        $allowed_types = ['array', 'object', 'string', 'number', 'integer', 'boolean', 'null'];

        if(! isset($args['type']))
        {
            /* translators: %s: Parameter. */
            _doing_it_wrong(__FUNCTION__, sprintf(__('The "type" schema keyword for %s is required.'), $param), '5.5.0');
        }

        if(is_array($args['type']))
        {
            $best_type = rest_handle_multi_type_schema($value, $args, $param);

            if(! $best_type)
            {
                return null;
            }

            $args['type'] = $best_type;
        }

        if(! in_array($args['type'], $allowed_types, true))
        {
            _doing_it_wrong(__FUNCTION__, /* translators: 1: Parameter, 2: The list of allowed types. */ wp_sprintf(__('The "type" schema keyword for %1$s can only be one of the built-in types: %2$l.'), $param, $allowed_types), '5.5.0');
        }

        if('array' === $args['type'])
        {
            $value = rest_sanitize_array($value);

            if(! empty($args['items']))
            {
                foreach($value as $index => $v)
                {
                    $value[$index] = rest_sanitize_value_from_schema($v, $args['items'], $param.'['.$index.']');
                }
            }

            if(! empty($args['uniqueItems']) && ! rest_validate_array_contains_unique_items($value))
            {
                /* translators: %s: Parameter. */
                return new WP_Error('rest_duplicate_items', sprintf(__('%s has duplicate items.'), $param));
            }

            return $value;
        }

        if('object' === $args['type'])
        {
            $value = rest_sanitize_object($value);

            foreach($value as $property => $v)
            {
                if(isset($args['properties'][$property]))
                {
                    $value[$property] = rest_sanitize_value_from_schema($v, $args['properties'][$property], $param.'['.$property.']');
                    continue;
                }

                $pattern_property_schema = rest_find_matching_pattern_property_schema($property, $args);
                if(null !== $pattern_property_schema)
                {
                    $value[$property] = rest_sanitize_value_from_schema($v, $pattern_property_schema, $param.'['.$property.']');
                    continue;
                }

                if(isset($args['additionalProperties']))
                {
                    if(false === $args['additionalProperties'])
                    {
                        unset($value[$property]);
                    }
                    elseif(is_array($args['additionalProperties']))
                    {
                        $value[$property] = rest_sanitize_value_from_schema($v, $args['additionalProperties'], $param.'['.$property.']');
                    }
                }
            }

            return $value;
        }

        if('null' === $args['type'])
        {
            return null;
        }

        if('integer' === $args['type'])
        {
            return (int) $value;
        }

        if('number' === $args['type'])
        {
            return (float) $value;
        }

        if('boolean' === $args['type'])
        {
            return rest_sanitize_boolean($value);
        }

        // This behavior matches rest_validate_value_from_schema().
        if(isset($args['format']) && (! isset($args['type']) || 'string' === $args['type'] || ! in_array($args['type'], $allowed_types, true)))
        {
            switch($args['format'])
            {
                case 'hex-color':
                    return (string) sanitize_hex_color($value);

                case 'date-time':
                    return sanitize_text_field($value);

                case 'email':
                    // sanitize_email() validates, which would be unexpected.
                    return sanitize_text_field($value);

                case 'uri':
                    return sanitize_url($value);

                case 'ip':
                    return sanitize_text_field($value);

                case 'uuid':
                    return sanitize_text_field($value);

                case 'text-field':
                    return sanitize_text_field($value);

                case 'textarea-field':
                    return sanitize_textarea_field($value);
            }
        }

        if('string' === $args['type'])
        {
            return (string) $value;
        }

        return $value;
    }

    function rest_preload_api_request($memo, $path)
    {
        /*
	 * array_reduce() doesn't support passing an array in PHP 5.2,
	 * so we need to make sure we start with one.
	 */
        if(! is_array($memo))
        {
            $memo = [];
        }

        if(empty($path))
        {
            return $memo;
        }

        $method = 'GET';
        if(is_array($path) && 2 === count($path))
        {
            $method = end($path);
            $path = reset($path);

            if(! in_array($method, ['GET', 'OPTIONS'], true))
            {
                $method = 'GET';
            }
        }

        $path = untrailingslashit($path);
        if(empty($path))
        {
            $path = '/';
        }

        $path_parts = parse_url($path);
        if(false === $path_parts)
        {
            return $memo;
        }

        $request = new WP_REST_Request($method, $path_parts['path']);
        if(! empty($path_parts['query']))
        {
            parse_str($path_parts['query'], $query_params);
            $request->set_query_params($query_params);
        }

        $response = rest_do_request($request);
        if(200 === $response->status)
        {
            $server = rest_get_server();

            $response = apply_filters('rest_post_dispatch', rest_ensure_response($response), $server, $request);
            $embed = $request->has_param('_embed') ? rest_parse_embed_param($request['_embed']) : false;
            $data = (array) $server->response_to_data($response, $embed);

            if('OPTIONS' === $method)
            {
                $memo[$method][$path] = [
                    'body' => $data,
                    'headers' => $response->headers,
                ];
            }
            else
            {
                $memo[$path] = [
                    'body' => $data,
                    'headers' => $response->headers,
                ];
            }
        }

        return $memo;
    }

    function rest_parse_embed_param($embed)
    {
        if(! $embed || 'true' === $embed || '1' === $embed)
        {
            return true;
        }

        $rels = wp_parse_list($embed);

        if(! $rels)
        {
            return true;
        }

        return $rels;
    }

    function rest_filter_response_by_context($response_data, $schema, $context)
    {
        if(isset($schema['anyOf']))
        {
            $matching_schema = rest_find_any_matching_schema($response_data, $schema, '');
            if(! is_wp_error($matching_schema))
            {
                if(! isset($schema['type']))
                {
                    $schema['type'] = $matching_schema['type'];
                }

                $response_data = rest_filter_response_by_context($response_data, $matching_schema, $context);
            }
        }

        if(isset($schema['oneOf']))
        {
            $matching_schema = rest_find_one_matching_schema($response_data, $schema, '', true);
            if(! is_wp_error($matching_schema))
            {
                if(! isset($schema['type']))
                {
                    $schema['type'] = $matching_schema['type'];
                }

                $response_data = rest_filter_response_by_context($response_data, $matching_schema, $context);
            }
        }

        if(! is_array($response_data) && ! is_object($response_data))
        {
            return $response_data;
        }

        if(isset($schema['type']))
        {
            $type = $schema['type'];
        }
        elseif(isset($schema['properties']))
        {
            $type = 'object'; // Back compat if a developer accidentally omitted the type.
        }
        else
        {
            return $response_data;
        }

        $is_array_type = 'array' === $type || (is_array($type) && in_array('array', $type, true));
        $is_object_type = 'object' === $type || (is_array($type) && in_array('object', $type, true));

        if($is_array_type && $is_object_type)
        {
            if(rest_is_array($response_data))
            {
                $is_object_type = false;
            }
            else
            {
                $is_array_type = false;
            }
        }

        $has_additional_properties = $is_object_type && isset($schema['additionalProperties']) && is_array($schema['additionalProperties']);

        foreach($response_data as $key => $value)
        {
            $check = [];

            if($is_array_type)
            {
                $check = isset($schema['items']) ? $schema['items'] : [];
            }
            elseif($is_object_type)
            {
                if(isset($schema['properties'][$key]))
                {
                    $check = $schema['properties'][$key];
                }
                else
                {
                    $pattern_property_schema = rest_find_matching_pattern_property_schema($key, $schema);
                    if(null !== $pattern_property_schema)
                    {
                        $check = $pattern_property_schema;
                    }
                    elseif($has_additional_properties)
                    {
                        $check = $schema['additionalProperties'];
                    }
                }
            }

            if(! isset($check['context']))
            {
                continue;
            }

            if(! in_array($context, $check['context'], true))
            {
                if($is_array_type)
                {
                    // All array items share schema, so there's no need to check each one.
                    $response_data = [];
                    break;
                }

                if(is_object($response_data))
                {
                    unset($response_data->$key);
                }
                else
                {
                    unset($response_data[$key]);
                }
            }
            elseif(is_array($value) || is_object($value))
            {
                $new_value = rest_filter_response_by_context($value, $check, $context);

                if(is_object($response_data))
                {
                    $response_data->$key = $new_value;
                }
                else
                {
                    $response_data[$key] = $new_value;
                }
            }
        }

        return $response_data;
    }

    function rest_default_additional_properties_to_false($schema)
    {
        $type = (array) $schema['type'];

        if(in_array('object', $type, true))
        {
            if(isset($schema['properties']))
            {
                foreach($schema['properties'] as $key => $child_schema)
                {
                    $schema['properties'][$key] = rest_default_additional_properties_to_false($child_schema);
                }
            }

            if(isset($schema['patternProperties']))
            {
                foreach($schema['patternProperties'] as $key => $child_schema)
                {
                    $schema['patternProperties'][$key] = rest_default_additional_properties_to_false($child_schema);
                }
            }

            if(! isset($schema['additionalProperties']))
            {
                $schema['additionalProperties'] = false;
            }
        }

        if(in_array('array', $type, true) && isset($schema['items']))
        {
            $schema['items'] = rest_default_additional_properties_to_false($schema['items']);
        }

        return $schema;
    }

    function rest_get_route_for_post($post)
    {
        $post = get_post($post);

        if(! $post instanceof WP_Post)
        {
            return '';
        }

        $post_type_route = rest_get_route_for_post_type_items($post->post_type);
        if(! $post_type_route)
        {
            return '';
        }

        $route = sprintf('%s/%d', $post_type_route, $post->ID);

        return apply_filters('rest_route_for_post', $route, $post);
    }

    function rest_get_route_for_post_type_items($post_type)
    {
        $post_type = get_post_type_object($post_type);
        if(! $post_type || ! $post_type->show_in_rest)
        {
            return '';
        }

        $namespace = ! empty($post_type->rest_namespace) ? $post_type->rest_namespace : 'wp/v2';
        $rest_base = ! empty($post_type->rest_base) ? $post_type->rest_base : $post_type->name;
        $route = sprintf('/%s/%s', $namespace, $rest_base);

        return apply_filters('rest_route_for_post_type_items', $route, $post_type);
    }

    function rest_get_route_for_term($term)
    {
        $term = get_term($term);

        if(! $term instanceof WP_Term)
        {
            return '';
        }

        $taxonomy_route = rest_get_route_for_taxonomy_items($term->taxonomy);
        if(! $taxonomy_route)
        {
            return '';
        }

        $route = sprintf('%s/%d', $taxonomy_route, $term->term_id);

        return apply_filters('rest_route_for_term', $route, $term);
    }

    function rest_get_route_for_taxonomy_items($taxonomy)
    {
        $taxonomy = get_taxonomy($taxonomy);
        if(! $taxonomy || ! $taxonomy->show_in_rest)
        {
            return '';
        }

        $namespace = ! empty($taxonomy->rest_namespace) ? $taxonomy->rest_namespace : 'wp/v2';
        $rest_base = ! empty($taxonomy->rest_base) ? $taxonomy->rest_base : $taxonomy->name;
        $route = sprintf('/%s/%s', $namespace, $rest_base);

        return apply_filters('rest_route_for_taxonomy_items', $route, $taxonomy);
    }

    function rest_get_queried_resource_route()
    {
        if(is_singular())
        {
            $route = rest_get_route_for_post(get_queried_object());
        }
        elseif(is_category() || is_tag() || is_tax())
        {
            $route = rest_get_route_for_term(get_queried_object());
        }
        elseif(is_author())
        {
            $route = '/wp/v2/users/'.get_queried_object_id();
        }
        else
        {
            $route = '';
        }

        return apply_filters('rest_queried_resource_route', $route);
    }

    function rest_get_endpoint_args_for_schema($schema, $method = WP_REST_Server::CREATABLE)
    {
        $schema_properties = ! empty($schema['properties']) ? $schema['properties'] : [];
        $endpoint_args = [];
        $valid_schema_properties = rest_get_allowed_schema_keywords();
        $valid_schema_properties = array_diff($valid_schema_properties, ['default', 'required']);

        foreach($schema_properties as $field_id => $params)
        {
            // Arguments specified as `readonly` are not allowed to be set.
            if(! empty($params['readonly']))
            {
                continue;
            }

            $endpoint_args[$field_id] = [
                'validate_callback' => 'rest_validate_request_arg',
                'sanitize_callback' => 'rest_sanitize_request_arg',
            ];

            if(WP_REST_Server::CREATABLE === $method && isset($params['default']))
            {
                $endpoint_args[$field_id]['default'] = $params['default'];
            }

            if(WP_REST_Server::CREATABLE === $method && ! empty($params['required']))
            {
                $endpoint_args[$field_id]['required'] = true;
            }

            foreach($valid_schema_properties as $schema_prop)
            {
                if(isset($params[$schema_prop]))
                {
                    $endpoint_args[$field_id][$schema_prop] = $params[$schema_prop];
                }
            }

            // Merge in any options provided by the schema property.
            if(isset($params['arg_options']))
            {
                // Only use required / default from arg_options on CREATABLE endpoints.
                if(WP_REST_Server::CREATABLE !== $method)
                {
                    $params['arg_options'] = array_diff_key($params['arg_options'], [
                        'required' => '',
                        'default' => '',
                    ]);
                }

                $endpoint_args[$field_id] = array_merge($endpoint_args[$field_id], $params['arg_options']);
            }
        }

        return $endpoint_args;
    }

    function rest_convert_error_to_response($error)
    {
        $status = array_reduce($error->get_all_error_data(), static function($status, $error_data)
        {
            if(is_array($error_data) && isset($error_data['status']))
            {
                return $error_data['status'];
            }

            return $status;
        },                     500);

        $errors = [];

        foreach((array) $error->errors as $code => $messages)
        {
            $all_data = $error->get_all_error_data($code);
            $last_data = array_pop($all_data);

            foreach((array) $messages as $message)
            {
                $formatted = [
                    'code' => $code,
                    'message' => $message,
                    'data' => $last_data,
                ];

                if($all_data)
                {
                    $formatted['additional_data'] = $all_data;
                }

                $errors[] = $formatted;
            }
        }

        $data = $errors[0];
        if(count($errors) > 1)
        {
            // Remove the primary error.
            array_shift($errors);
            $data['additional_errors'] = $errors;
        }

        return new WP_REST_Response($data, $status);
    }
