<?php

    #[AllowDynamicProperties]
    class WP_HTTP_Requests_Hooks extends WpOrg\Requests\Hooks
    {
        protected $url;

        protected $request = [];

        public function __construct($url, $request)
        {
            $this->url = $url;
            $this->request = $request;
        }

        public function dispatch($hook, $parameters = [])
        {
            $result = parent::dispatch($hook, $parameters);

            // Handle back-compat actions.
            switch($hook)
            {
                case 'curl.before_send':
                    do_action_ref_array('http_api_curl', [
                        &$parameters[0],
                        $this->request,
                        $this->url
                    ]);
                    break;
            }

            do_action_ref_array("requests-{$hook}", $parameters, $this->request, $this->url); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

            return $result;
        }
    }
