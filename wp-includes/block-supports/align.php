<?php

    function wp_register_alignment_support($block_type)
    {
        $has_align_support = block_has_support($block_type, 'align', false);
        if($has_align_support)
        {
            if(! $block_type->attributes)
            {
                $block_type->attributes = [];
            }

            if(! array_key_exists('align', $block_type->attributes))
            {
                $block_type->attributes['align'] = [
                    'type' => 'string',
                    'enum' => ['left', 'center', 'right', 'wide', 'full', ''],
                ];
            }
        }
    }

    function wp_apply_alignment_support($block_type, $block_attributes)
    {
        $attributes = [];
        $has_align_support = block_has_support($block_type, 'align', false);
        if($has_align_support)
        {
            $has_block_alignment = array_key_exists('align', $block_attributes);

            if($has_block_alignment)
            {
                $attributes['class'] = sprintf('align%s', $block_attributes['align']);
            }
        }

        return $attributes;
    }

// Register the block support.
    WP_Block_Supports::get_instance()->register('align', [
        'register_attribute' => 'wp_register_alignment_support',
        'apply' => 'wp_apply_alignment_support',
    ]);
