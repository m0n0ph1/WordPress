<?php

    #[AllowDynamicProperties]
    class WP_Ajax_Response
    {
        public $responses = [];

        public function __construct($args = '')
        {
            if(! empty($args))
            {
                $this->add($args);
            }
        }

        public function add($args = '')
        {
            $defaults = [
                'what' => 'object',
                'action' => false,
                'id' => '0',
                'old_id' => false,
                'position' => 1,
                'data' => '',
                'supplemental' => [],
            ];

            $parsed_args = wp_parse_args($args, $defaults);

            $position = preg_replace('/[^a-z0-9:_-]/i', '', $parsed_args['position']);
            $id = $parsed_args['id'];
            $what = $parsed_args['what'];
            $action = $parsed_args['action'];
            $old_id = $parsed_args['old_id'];
            $data = $parsed_args['data'];

            if(is_wp_error($id))
            {
                $data = $id;
                $id = 0;
            }

            $response = '';
            if(is_wp_error($data))
            {
                foreach((array) $data->get_error_codes() as $code)
                {
                    $response .= "<wp_error code='$code'><![CDATA[".$data->get_error_message($code).']]></wp_error>';
                    $error_data = $data->get_error_data($code);
                    if(! $error_data)
                    {
                        continue;
                    }
                    $class = '';
                    if(is_object($error_data))
                    {
                        $class = ' class="'.get_class($error_data).'"';
                        $error_data = get_object_vars($error_data);
                    }

                    $response .= "<wp_error_data code='$code'$class>";

                    if(is_scalar($error_data))
                    {
                        $response .= "<![CDATA[$error_data]]>";
                    }
                    elseif(is_array($error_data))
                    {
                        foreach($error_data as $k => $v)
                        {
                            $response .= "<$k><![CDATA[$v]]></$k>";
                        }
                    }

                    $response .= '</wp_error_data>';
                }
            }
            else
            {
                $response = "<response_data><![CDATA[$data]]></response_data>";
            }

            $s = '';
            if(is_array($parsed_args['supplemental']))
            {
                foreach($parsed_args['supplemental'] as $k => $v)
                {
                    $s .= "<$k><![CDATA[$v]]></$k>";
                }
                $s = "<supplemental>$s</supplemental>";
            }

            if(false === $action)
            {
                $action = $_POST['action'];
            }
            $x = '';
            $x .= "<response action='{$action}_$id'>"; // The action attribute in the xml output is formatted like a nonce action.
            $x .= "<$what id='$id' ".(false === $old_id ? '' : "old_id='$old_id' ")."position='$position'>";
            $x .= $response;
            $x .= $s;
            $x .= "</$what>";
            $x .= '</response>';

            $this->responses[] = $x;

            return $x;
        }

        public function send()
        {
            header('Content-Type: text/xml; charset='.get_option('blog_charset'));
            echo "<?xml version='1.0' encoding='".get_option('blog_charset')."' standalone='yes'?><wp_ajax>";
            foreach((array) $this->responses as $response)
            {
                echo $response;
            }
            echo '</wp_ajax>';
            if(wp_doing_ajax())
            {
                wp_die();
            }
            else
            {
                die();
            }
        }
    }
