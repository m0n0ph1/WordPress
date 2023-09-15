<?php

// If there are paragraph blocks, print up to two.
// Otherwise this is legacy content, so print the excerpt.
    if(has_block('core/paragraph', get_the_content()))
    {
        twenty_twenty_one_print_first_instance_of_block('core/paragraph', get_the_content(), 2);
    }
    else
    {
        the_excerpt();
    }
