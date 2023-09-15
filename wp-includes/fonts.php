<?php

    function wp_print_font_faces($fonts = [])
    {
        if(empty($fonts))
        {
            $fonts = WP_Font_Face_Resolver::get_fonts_from_theme_json();
        }

        if(empty($fonts))
        {
            return;
        }

        $wp_font_face = new WP_Font_Face();
        $wp_font_face->generate_and_print($fonts);
    }
