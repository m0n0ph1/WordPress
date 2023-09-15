<?php

    class WP_Styles extends WP_Dependencies
    {
        public $base_url;

        public $content_url;

        public $default_version;

        public $text_direction = 'ltr';

        public $concat = '';

        public $concat_version = '';

        public $do_concat = false;

        public $print_html = '';

        public $print_code = '';

        public $default_dirs;

        private $type_attr = '';

        public function __construct()
        {
            if(function_exists('is_admin') && ! is_admin() && function_exists('current_theme_supports') && ! current_theme_supports('html5', 'style'))
            {
                $this->type_attr = " type='text/css'";
            }

            do_action_ref_array('wp_default_styles', [&$this]);
        }

        public function do_item($handle, $group = false)
        {
            if(! parent::do_item($handle))
            {
                return false;
            }

            $obj = $this->registered[$handle];

            if(null === $obj->ver)
            {
                $ver = '';
            }
            else
            {
                $ver = $obj->ver ? $obj->ver : $this->default_version;
            }

            if(isset($this->args[$handle]))
            {
                $ver = $ver ? $ver.'&amp;'.$this->args[$handle] : $this->args[$handle];
            }

            $src = $obj->src;
            $cond_before = '';
            $cond_after = '';
            $conditional = isset($obj->extra['conditional']) ? $obj->extra['conditional'] : '';

            if($conditional)
            {
                $cond_before = "<!--[if {$conditional}]>\n";
                $cond_after = "<![endif]-->\n";
            }

            $inline_style = $this->print_inline_style($handle, false);

            if($inline_style)
            {
                $inline_style_tag = sprintf("<style id='%s-inline-css'%s>\n%s\n</style>\n", esc_attr($handle), $this->type_attr, $inline_style);
            }
            else
            {
                $inline_style_tag = '';
            }

            if($this->do_concat && $this->in_default_dir($src) && ! $conditional && ! isset($obj->extra['alt']))
            {
                $this->concat .= "$handle,";
                $this->concat_version .= "$handle$ver";

                $this->print_code .= $inline_style;

                return true;
            }

            if(isset($obj->args))
            {
                $media = esc_attr($obj->args);
            }
            else
            {
                $media = 'all';
            }

            // A single item may alias a set of items, by having dependencies, but no source.
            if(! $src)
            {
                if($inline_style_tag)
                {
                    if($this->do_concat)
                    {
                        $this->print_html .= $inline_style_tag;
                    }
                    else
                    {
                        echo $inline_style_tag;
                    }
                }

                return true;
            }

            $href = $this->_css_href($src, $ver, $handle);
            if(! $href)
            {
                return true;
            }

            $rel = isset($obj->extra['alt']) && $obj->extra['alt'] ? 'alternate stylesheet' : 'stylesheet';
            $title = isset($obj->extra['title']) ? sprintf(" title='%s'", esc_attr($obj->extra['title'])) : '';

            $tag = sprintf("<link rel='%s' id='%s-css'%s href='%s'%s media='%s' />\n", $rel, $handle, $title, $href, $this->type_attr, $media);

            $tag = apply_filters('style_loader_tag', $tag, $handle, $href, $media);

            if('rtl' === $this->text_direction && isset($obj->extra['rtl']) && $obj->extra['rtl'])
            {
                if(is_bool($obj->extra['rtl']) || 'replace' === $obj->extra['rtl'])
                {
                    $suffix = isset($obj->extra['suffix']) ? $obj->extra['suffix'] : '';
                    $rtl_href = str_replace("{$suffix}.css", "-rtl{$suffix}.css", $this->_css_href($src, $ver, "$handle-rtl"));
                }
                else
                {
                    $rtl_href = $this->_css_href($obj->extra['rtl'], $ver, "$handle-rtl");
                }

                $rtl_tag = sprintf("<link rel='%s' id='%s-rtl-css'%s href='%s'%s media='%s' />\n", $rel, $handle, $title, $rtl_href, $this->type_attr, $media);

                $rtl_tag = apply_filters('style_loader_tag', $rtl_tag, $handle, $rtl_href, $media);

                if('replace' === $obj->extra['rtl'])
                {
                    $tag = $rtl_tag;
                }
                else
                {
                    $tag .= $rtl_tag;
                }
            }

            if($this->do_concat)
            {
                $this->print_html .= $cond_before;
                $this->print_html .= $tag;
                if($inline_style_tag)
                {
                    $this->print_html .= $inline_style_tag;
                }
                $this->print_html .= $cond_after;
            }
            else
            {
                echo $cond_before;
                echo $tag;
                $this->print_inline_style($handle);
                echo $cond_after;
            }

            return true;
        }

        public function print_inline_style($handle, $display = true)
        {
            $output = $this->get_data($handle, 'after');

            if(empty($output))
            {
                return false;
            }

            $output = implode("\n", $output);

            if(! $display)
            {
                return $output;
            }

            printf("<style id='%s-inline-css'%s>\n%s\n</style>\n", esc_attr($handle), $this->type_attr, $output);

            return true;
        }

        public function in_default_dir($src)
        {
            if(! $this->default_dirs)
            {
                return true;
            }

            foreach((array) $this->default_dirs as $test)
            {
                if(str_starts_with($src, $test))
                {
                    return true;
                }
            }

            return false;
        }

        public function _css_href($src, $ver, $handle)
        {
            if(! is_bool($src) && ! preg_match('|^(https?:)?//|', $src) && ! ($this->content_url && str_starts_with($src, $this->content_url)))
            {
                $src = $this->base_url.$src;
            }

            if(! empty($ver))
            {
                $src = add_query_arg('ver', $ver, $src);
            }

            $src = apply_filters('style_loader_src', $src, $handle);

            return esc_url($src);
        }

        public function add_inline_style($handle, $code)
        {
            if(! $code)
            {
                return false;
            }

            $after = $this->get_data($handle, 'after');
            if(! $after)
            {
                $after = [];
            }

            $after[] = $code;

            return $this->add_data($handle, 'after', $after);
        }

        public function all_deps($handles, $recursion = false, $group = false)
        {
            $r = parent::all_deps($handles, $recursion, $group);
            if(! $recursion)
            {
                $this->to_do = apply_filters('print_styles_array', $this->to_do);
            }

            return $r;
        }

        public function do_footer_items()
        {
            $this->do_items(false, 1);

            return $this->done;
        }

        public function reset()
        {
            $this->do_concat = false;
            $this->concat = '';
            $this->concat_version = '';
            $this->print_html = '';
        }
    }
