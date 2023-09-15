<?php

    #[AllowDynamicProperties]
    final class WP_User_Request
    {
        public $ID = 0;

        public $user_id = 0;

        public $email = '';

        public $action_name = '';

        public $status = '';

        public $created_timestamp = null;

        public $modified_timestamp = null;

        public $confirmed_timestamp = null;

        public $completed_timestamp = null;

        public $request_data = [];

        public $confirm_key = '';

        public function __construct($post)
        {
            $this->ID = $post->ID;
            $this->user_id = $post->post_author;
            $this->email = $post->post_title;
            $this->action_name = $post->post_name;
            $this->status = $post->post_status;
            $this->created_timestamp = strtotime($post->post_date_gmt);
            $this->modified_timestamp = strtotime($post->post_modified_gmt);
            $this->confirmed_timestamp = (int) get_post_meta($post->ID, '_wp_user_request_confirmed_timestamp', true);
            $this->completed_timestamp = (int) get_post_meta($post->ID, '_wp_user_request_completed_timestamp', true);
            $this->request_data = json_decode($post->post_content, true);
            $this->confirm_key = $post->post_password;
        }
    }
