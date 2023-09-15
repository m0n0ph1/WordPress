<?php

    $content = get_the_content();

    if(has_block('core/video', $content))
    {
        twenty_twenty_one_print_first_instance_of_block('core/video', $content);
    }
    elseif(has_block('core/embed', $content))
    {
        twenty_twenty_one_print_first_instance_of_block('core/embed', $content);
    }
    else
    {
        twenty_twenty_one_print_first_instance_of_block('core-embed/*', $content);
    }

// Add the excerpt.
    the_excerpt();
