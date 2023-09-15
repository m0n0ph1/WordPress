<?php

    class WP_REST_Edit_Site_Export_Controller extends WP_REST_Controller
    {
        public function __construct()
        {
            $this->namespace = 'wp-block-editor/v1';
            $this->rest_base = 'export';
        }

        public function register_routes()
        {
            register_rest_route($this->namespace, '/'.$this->rest_base, [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'export'],
                    'permission_callback' => [$this, 'permissions_check'],
                ],
            ]);
        }

        public function permissions_check()
        {
            if(current_user_can('edit_theme_options'))
            {
                return true;
            }

            return new WP_Error('rest_cannot_export_templates', __('Sorry, you are not allowed to export templates and template parts.'), ['status' => rest_authorization_required_code()]);
        }

        public function export()
        {
            // Generate the export file.
            $filename = wp_generate_block_templates_export_file();

            if(is_wp_error($filename))
            {
                $filename->add_data(['status' => 500]);

                return $filename;
            }

            $theme_name = basename(get_stylesheet());
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename='.$theme_name.'.zip');
            header('Content-Length: '.filesize($filename));
            flush();
            readfile($filename);
            unlink($filename);
            exit;
        }
    }
