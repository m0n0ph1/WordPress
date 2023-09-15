<?php

    function block_core_heading_render($attributes, $content)
    {
        if(! $content)
        {
            return $content;
        }

        $p = new WP_HTML_Tag_Processor($content);

        $header_tags = ['H1', 'H2', 'H3', 'H4', 'H5', 'H6'];
        while($p->next_tag())
        {
            if(in_array($p->get_tag(), $header_tags, true))
            {
                $p->add_class('wp-block-heading');
                break;
            }
        }

        return $p->get_updated_html();
    }

    function register_block_core_heading()
    {
        register_block_type_from_metadata(__DIR__.'/heading', [
            'render_callback' => 'block_core_heading_render',
        ]);
    }

    add_action('init', 'register_block_core_heading');
