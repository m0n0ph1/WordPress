<?php

    if(! class_exists('TwentyTwenty_Script_Loader'))
    {
        class TwentyTwenty_Script_Loader
        {
            public function migrate_legacy_strategy_script_data($to_do)
            {
                foreach($to_do as $handle)
                {
                    foreach(['async', 'defer'] as $strategy)
                    {
                        if(wp_scripts()->get_data($handle, $strategy))
                        {
                            wp_script_add_data($handle, 'strategy', $strategy);
                        }
                    }
                }

                return $to_do;
            }

            public function filter_script_loader_tag($tag, $handle)
            {
                $strategies = [
                    'async' => (bool) wp_scripts()->get_data($handle, 'async'),
                    'defer' => (bool) wp_scripts()->get_data($handle, 'defer'),
                ];
                $strategy = wp_scripts()->get_data($handle, 'strategy');
                if($strategy && isset($strategies[$strategy]))
                {
                    $strategies[$strategy] = true;
                }

                foreach(array_keys(array_filter($strategies)) as $attr)
                {
                    // Prevent adding attribute when already added in #12009.
                    if(! preg_match(":\s$attr(=|>|\s):", $tag))
                    {
                        $tag = preg_replace(':(?=></script>):', " $attr", $tag, 1);
                    }
                    // Only allow async or defer, not both.
                    break;
                }

                return $tag;
            }
        }
    }
