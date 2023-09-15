<?php

// Print the 1st gallery found.
    if(has_block('core/gallery', get_the_content()))
    {
        twenty_twenty_one_print_first_instance_of_block('core/gallery', get_the_content());
    }

    the_excerpt();
