<?php

    #[AllowDynamicProperties]
    class WP_SimplePie_Sanitize_KSES extends SimplePie_Sanitize
    {
        public function sanitize($data, $type, $base = '')
        {
            $data = trim($data);
            if($type & SIMPLEPIE_CONSTRUCT_MAYBE_HTML)
            {
                if(preg_match('/(&(#(x[0-9a-fA-F]+|[0-9]+)|[a-zA-Z0-9]+)|<\/[A-Za-z][^\x09\x0A\x0B\x0C\x0D\x20\x2F\x3E]*'.SIMPLEPIE_PCRE_HTML_ATTRIBUTE.'>)/', $data))
                {
                    $type |= SIMPLEPIE_CONSTRUCT_HTML;
                }
                else
                {
                    $type |= SIMPLEPIE_CONSTRUCT_TEXT;
                }
            }
            if($type & SIMPLEPIE_CONSTRUCT_BASE64)
            {
                $data = base64_decode($data);
            }
            if($type & (SIMPLEPIE_CONSTRUCT_HTML | SIMPLEPIE_CONSTRUCT_XHTML))
            {
                $data = wp_kses_post($data);
                if('UTF-8' !== $this->output_encoding)
                {
                    $data = $this->registry->call('Misc', 'change_encoding', [$data, 'UTF-8', $this->output_encoding]);
                }

                return $data;
            }
            else
            {
                return parent::sanitize($data, $type, $base);
            }
        }
    }
