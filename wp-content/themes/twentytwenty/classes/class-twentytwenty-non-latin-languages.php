<?php

    if(! class_exists('TwentyTwenty_Non_Latin_Languages'))
    {
        class TwentyTwenty_Non_Latin_Languages
        {
            public static function get_non_latin_css($type = 'front-end')
            {
                // Fetch site locale.
                $locale = get_bloginfo('language');

                $font_family = apply_filters('twentytwenty_get_localized_font_family_types', [

                    // Arabic.
                    'ar' => ['Tahoma', 'Arial', 'sans-serif'],
                    'ary' => ['Tahoma', 'Arial', 'sans-serif'],
                    'azb' => ['Tahoma', 'Arial', 'sans-serif'],
                    'ckb' => ['Tahoma', 'Arial', 'sans-serif'],
                    'fa-IR' => ['Tahoma', 'Arial', 'sans-serif'],
                    'haz' => ['Tahoma', 'Arial', 'sans-serif'],
                    'ps' => ['Tahoma', 'Arial', 'sans-serif'],

                    // Chinese Simplified (China) - Noto Sans SC.
                    'zh-CN' => [
                        '\'PingFang SC\'',
                        '\'Helvetica Neue\'',
                        '\'Microsoft YaHei New\'',
                        '\'STHeiti Light\'',
                        'sans-serif',
                    ],

                    // Chinese Traditional (Taiwan) - Noto Sans TC.
                    'zh-TW' => [
                        '\'PingFang TC\'',
                        '\'Helvetica Neue\'',
                        '\'Microsoft YaHei New\'',
                        '\'STHeiti Light\'',
                        'sans-serif',
                    ],

                    // Chinese (Hong Kong) - Noto Sans HK.
                    'zh-HK' => [
                        '\'PingFang HK\'',
                        '\'Helvetica Neue\'',
                        '\'Microsoft YaHei New\'',
                        '\'STHeiti Light\'',
                        'sans-serif',
                    ],

                    // Cyrillic.
                    'bel' => ['\'Helvetica Neue\'', 'Helvetica', '\'Segoe UI\'', 'Arial', 'sans-serif'],
                    'bg-BG' => ['\'Helvetica Neue\'', 'Helvetica', '\'Segoe UI\'', 'Arial', 'sans-serif'],
                    'kk' => ['\'Helvetica Neue\'', 'Helvetica', '\'Segoe UI\'', 'Arial', 'sans-serif'],
                    'mk-MK' => ['\'Helvetica Neue\'', 'Helvetica', '\'Segoe UI\'', 'Arial', 'sans-serif'],
                    'mn' => ['\'Helvetica Neue\'', 'Helvetica', '\'Segoe UI\'', 'Arial', 'sans-serif'],
                    'ru-RU' => ['\'Helvetica Neue\'', 'Helvetica', '\'Segoe UI\'', 'Arial', 'sans-serif'],
                    'sah' => ['\'Helvetica Neue\'', 'Helvetica', '\'Segoe UI\'', 'Arial', 'sans-serif'],
                    'sr-RS' => ['\'Helvetica Neue\'', 'Helvetica', '\'Segoe UI\'', 'Arial', 'sans-serif'],
                    'tt-RU' => ['\'Helvetica Neue\'', 'Helvetica', '\'Segoe UI\'', 'Arial', 'sans-serif'],
                    'uk' => ['\'Helvetica Neue\'', 'Helvetica', '\'Segoe UI\'', 'Arial', 'sans-serif'],

                    // Devanagari.
                    'bn-BD' => ['Arial', 'sans-serif'],
                    'hi-IN' => ['Arial', 'sans-serif'],
                    'mr' => ['Arial', 'sans-serif'],
                    'ne-NP' => ['Arial', 'sans-serif'],

                    // Greek.
                    'el' => ['\'Helvetica Neue\', Helvetica, Arial, sans-serif'],

                    // Gujarati.
                    'gu' => ['Arial', 'sans-serif'],

                    // Hebrew.
                    'he-IL' => ['\'Arial Hebrew\'', 'Arial', 'sans-serif'],

                    // Japanese.
                    'ja' => ['sans-serif'],

                    // Korean.
                    'ko-KR' => [
                        '\'Apple SD Gothic Neo\'',
                        '\'Malgun Gothic\'',
                        '\'Nanum Gothic\'',
                        'Dotum',
                        'sans-serif',
                    ],

                    // Thai.
                    'th' => ['\'Sukhumvit Set\'', '\'Helvetica Neue\'', 'Helvetica', 'Arial', 'sans-serif'],

                    // Vietnamese.
                    'vi' => ['\'Libre Franklin\'', 'sans-serif'],

                ]);

                // Return if the selected language has no fallback fonts.
                if(empty($font_family[$locale]))
                {
                    return null;
                }

                $elements = apply_filters('twentytwenty_get_localized_font_family_elements', [
                    'front-end' => [
                        'body',
                        'input',
                        'textarea',
                        'button',
                        '.button',
                        '.faux-button',
                        '.faux-button.more-link',
                        '.wp-block-button__link',
                        '.wp-block-file__button',
                        '.has-drop-cap:not(:focus)::first-letter',
                        '.entry-content .wp-block-archives',
                        '.entry-content .wp-block-categories',
                        '.entry-content .wp-block-cover-image',
                        '.entry-content .wp-block-cover-image p',
                        '.entry-content .wp-block-latest-comments',
                        '.entry-content .wp-block-latest-posts',
                        '.entry-content .wp-block-pullquote',
                        '.entry-content .wp-block-quote.is-large',
                        '.entry-content .wp-block-quote.is-style-large',
                        '.entry-content .wp-block-archives *',
                        '.entry-content .wp-block-categories *',
                        '.entry-content .wp-block-latest-posts *',
                        '.entry-content .wp-block-latest-comments *',
                        '.entry-content',
                        '.entry-content h1',
                        '.entry-content h2',
                        '.entry-content h3',
                        '.entry-content h4',
                        '.entry-content h5',
                        '.entry-content h6',
                        '.entry-content p',
                        '.entry-content ol',
                        '.entry-content ul',
                        '.entry-content dl',
                        '.entry-content dt',
                        '.entry-content cite',
                        '.entry-content figcaption',
                        '.entry-content table',
                        '.entry-content address',
                        '.entry-content .wp-caption-text',
                        '.entry-content .wp-block-file',
                        '.comment-content p',
                        '.comment-content ol',
                        '.comment-content ul',
                        '.comment-content dl',
                        '.comment-content dt',
                        '.comment-content cite',
                        '.comment-content figcaption',
                        '.comment-content .wp-caption-text',
                        '.widget_text p',
                        '.widget_text ol',
                        '.widget_text ul',
                        '.widget_text dl',
                        '.widget_text dt',
                        '.widget-content .rssSummary',
                        '.widget-content cite',
                        '.widget-content figcaption',
                        '.widget-content .wp-caption-text',
                    ],
                    'block-editor' => [
                        '.editor-styles-wrapper > *',
                        '.editor-styles-wrapper p',
                        '.editor-styles-wrapper ol',
                        '.editor-styles-wrapper ul',
                        '.editor-styles-wrapper dl',
                        '.editor-styles-wrapper dt',
                        '.editor-post-title__block .editor-post-title__input',
                        '.editor-styles-wrapper .wp-block-post-title',
                        '.editor-styles-wrapper h1',
                        '.editor-styles-wrapper h2',
                        '.editor-styles-wrapper h3',
                        '.editor-styles-wrapper h4',
                        '.editor-styles-wrapper h5',
                        '.editor-styles-wrapper h6',
                        '.editor-styles-wrapper .has-drop-cap:not(:focus)::first-letter',
                        '.editor-styles-wrapper cite',
                        '.editor-styles-wrapper figcaption',
                        '.editor-styles-wrapper .wp-caption-text',
                    ],
                    'classic-editor' => [
                        'body#tinymce.wp-editor',
                        'body#tinymce.wp-editor p',
                        'body#tinymce.wp-editor ol',
                        'body#tinymce.wp-editor ul',
                        'body#tinymce.wp-editor dl',
                        'body#tinymce.wp-editor dt',
                        'body#tinymce.wp-editor figcaption',
                        'body#tinymce.wp-editor .wp-caption-text',
                        'body#tinymce.wp-editor .wp-caption-dd',
                        'body#tinymce.wp-editor cite',
                        'body#tinymce.wp-editor table',
                    ],
                ]);

                // Return if the specified type doesn't exist.
                if(empty($elements[$type]))
                {
                    return null;
                }

                // Return the specified styles.
                return twentytwenty_generate_css(implode(',', $elements[$type]), 'font-family', implode(',', $font_family[$locale]), null, null, false);
            }
        }
    }
