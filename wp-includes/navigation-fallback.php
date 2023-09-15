<?php

    function wp_add_fields_to_navigation_fallback_embedded_links($schema)
    {
        // Expose top level fields.
        $schema['properties']['status']['context'] = array_merge($schema['properties']['status']['context'], ['embed']);
        $schema['properties']['content']['context'] = array_merge($schema['properties']['content']['context'], ['embed']);

        /*
         * Exposes sub properties of content field.
         * These sub properties aren't exposed by the posts controller by default,
         * for requests where context is `embed`.
         *
         * @see WP_REST_Posts_Controller::get_item_schema()
         */
        $schema['properties']['content']['properties']['raw']['context'] = array_merge($schema['properties']['content']['properties']['raw']['context'], ['embed']);
        $schema['properties']['content']['properties']['rendered']['context'] = array_merge($schema['properties']['content']['properties']['rendered']['context'], ['embed']);
        $schema['properties']['content']['properties']['block_version']['context'] = array_merge($schema['properties']['content']['properties']['block_version']['context'], ['embed']);

        /*
         * Exposes sub properties of title field.
         * These sub properties aren't exposed by the posts controller by default,
         * for requests where context is `embed`.
         *
         * @see WP_REST_Posts_Controller::get_item_schema()
         */
        $schema['properties']['title']['properties']['raw']['context'] = array_merge($schema['properties']['title']['properties']['raw']['context'], ['embed']);

        return $schema;
    }

    add_filter('rest_wp_navigation_item_schema', 'wp_add_fields_to_navigation_fallback_embedded_links');
