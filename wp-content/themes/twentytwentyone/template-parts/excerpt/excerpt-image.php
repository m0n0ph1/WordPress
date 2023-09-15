<?php

// If there is no featured-image, print the first image block found.
    if(! twenty_twenty_one_can_show_post_thumbnail() && has_block('core/image', get_the_content()))
    {
        twenty_twenty_one_print_first_instance_of_block('core/image', get_the_content());
    }

    the_excerpt();
