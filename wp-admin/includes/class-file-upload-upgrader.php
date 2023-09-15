<?php

    #[AllowDynamicProperties]
    class File_Upload_Upgrader
    {
        public $package;

        public $filename;

        public $id = 0;

        public function __construct($form, $urlholder)
        {
            if(empty($_FILES[$form]['name']) && empty($_GET[$urlholder]))
            {
                wp_die(__('Please select a file'));
            }

            // Handle a newly uploaded file. Else, assume it's already been uploaded.
            if(! empty($_FILES))
            {
                $overrides = [
                    'test_form' => false,
                    'test_type' => false,
                ];
                $file = wp_handle_upload($_FILES[$form], $overrides);

                if(isset($file['error']))
                {
                    wp_die($file['error']);
                }

                $this->filename = $_FILES[$form]['name'];
                $this->package = $file['file'];

                // Construct the attachment array.
                $attachment = [
                    'post_title' => $this->filename,
                    'post_content' => $file['url'],
                    'post_mime_type' => $file['type'],
                    'guid' => $file['url'],
                    'context' => 'upgrader',
                    'post_status' => 'private',
                ];

                // Save the data.
                $this->id = wp_insert_attachment($attachment, $file['file']);

                // Schedule a cleanup for 2 hours from now in case of failed installation.
                wp_schedule_single_event(time() + 2 * HOUR_IN_SECONDS, 'upgrader_scheduled_cleanup', [$this->id]);
            }
            elseif(is_numeric($_GET[$urlholder]))
            {
                // Numeric Package = previously uploaded file, see above.
                $this->id = (int) $_GET[$urlholder];
                $attachment = get_post($this->id);
                if(empty($attachment))
                {
                    wp_die(__('Please select a file'));
                }

                $this->filename = $attachment->post_title;
                $this->package = get_attached_file($attachment->ID);
            }
            else
            {
                // Else, It's set to something, Back compat for plugins using the old (pre-3.3) File_Uploader handler.
                $uploads = wp_upload_dir();
                if(! ($uploads && false === $uploads['error']))
                {
                    wp_die($uploads['error']);
                }

                $this->filename = sanitize_file_name($_GET[$urlholder]);
                $this->package = $uploads['basedir'].'/'.$this->filename;

                if(! str_starts_with(realpath($this->package), realpath($uploads['basedir'])))
                {
                    wp_die(__('Please select a file'));
                }
            }
        }

        public function cleanup()
        {
            if($this->id)
            {
                wp_delete_attachment($this->id);
            }
            elseif(file_exists($this->package))
            {
                return @unlink($this->package);
            }

            return true;
        }
    }
