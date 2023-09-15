<?php

    class WP_REST_Blocks_Controller extends WP_REST_Posts_Controller
    {
        public function check_read_permission($post)
        {
            // By default the read_post capability is mapped to edit_posts.
            if(! current_user_can('read_post', $post->ID))
            {
                return false;
            }

            return parent::check_read_permission($post);
        }

        public function filter_response_by_context($data, $context)
        {
            $data = parent::filter_response_by_context($data, $context);

            /*
             * Remove `title.rendered` and `content.rendered` from the response. It
             * doesn't make sense for a reusable block to have rendered content on its
             * own, since rendering a block requires it to be inside a post or a page.
             */
            unset($data['title']['rendered']);
            unset($data['content']['rendered']);

            // Add the core wp_pattern_sync_status meta as top level property to the response.
            $data['wp_pattern_sync_status'] = isset($data['meta']['wp_pattern_sync_status']) ? $data['meta']['wp_pattern_sync_status'] : '';
            unset($data['meta']['wp_pattern_sync_status']);

            return $data;
        }

        public function get_item_schema()
        {
            if($this->schema)
            {
                return $this->add_additional_fields_schema($this->schema);
            }

            $schema = parent::get_item_schema();

            /*
             * Allow all contexts to access `title.raw` and `content.raw`. Clients always
             * need the raw markup of a reusable block to do anything useful, e.g. parse
             * it or display it in an editor.
             */
            $schema['properties']['title']['properties']['raw']['context'] = ['view', 'edit'];
            $schema['properties']['content']['properties']['raw']['context'] = ['view', 'edit'];

            /*
             * Remove `title.rendered` and `content.rendered` from the schema. It doesn’t
             * make sense for a reusable block to have rendered content on its own, since
             * rendering a block requires it to be inside a post or a page.
             */
            unset($schema['properties']['title']['properties']['rendered']);
            unset($schema['properties']['content']['properties']['rendered']);

            $this->schema = $schema;

            return $this->add_additional_fields_schema($this->schema);
        }
    }
