<?php

    function wp_get_block_default_classname($block_name)
    {
        // Generated HTML classes for blocks follow the `wp-block-{name}` nomenclature.
        // Blocks provided by WordPress drop the prefixes 'core/' or 'core-' (historically used in 'core-embed/').
        $classname = 'wp-block-'.preg_replace('/^core-/', '', str_replace('/', '-', $block_name));

        $classname = apply_filters('block_default_classname', $classname, $block_name);

        return $classname;
    }

    function wp_apply_generated_classname_support($block_type)
    {
        $attributes = [];
        $has_generated_classname_support = block_has_support($block_type, 'className', true);
        if($has_generated_classname_support)
        {
            $block_classname = wp_get_block_default_classname($block_type->name);

            if($block_classname)
            {
                $attributes['class'] = $block_classname;
            }
        }

        return $attributes;
    }

// Register the block support.
    WP_Block_Supports::get_instance()->register('generated-classname', [
        'apply' => 'wp_apply_generated_classname_support',
    ]);
