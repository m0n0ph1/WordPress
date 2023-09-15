<?php

    if(! defined('CUSTOM_TAGS'))
    {
        define('CUSTOM_TAGS', false);
    }

// Ensure that these variables are added to the global namespace
// (e.g. if using namespaces / autoload in the current PHP environment).
    global $allowedposttags, $allowedtags, $allowedentitynames, $allowedxmlentitynames;

    if(! CUSTOM_TAGS)
    {
        $allowedposttags = [
            'address' => [],
            'a' => [
                'href' => true,
                'rel' => true,
                'rev' => true,
                'name' => true,
                'target' => true,
                'download' => [
                    'valueless' => 'y',
                ],
            ],
            'abbr' => [],
            'acronym' => [],
            'area' => [
                'alt' => true,
                'coords' => true,
                'href' => true,
                'nohref' => true,
                'shape' => true,
                'target' => true,
            ],
            'article' => [
                'align' => true,
            ],
            'aside' => [
                'align' => true,
            ],
            'audio' => [
                'autoplay' => true,
                'controls' => true,
                'loop' => true,
                'muted' => true,
                'preload' => true,
                'src' => true,
            ],
            'b' => [],
            'bdo' => [],
            'big' => [],
            'blockquote' => [
                'cite' => true,
            ],
            'br' => [],
            'button' => [
                'disabled' => true,
                'name' => true,
                'type' => true,
                'value' => true,
            ],
            'caption' => [
                'align' => true,
            ],
            'cite' => [],
            'code' => [],
            'col' => [
                'align' => true,
                'char' => true,
                'charoff' => true,
                'span' => true,
                'valign' => true,
                'width' => true,
            ],
            'colgroup' => [
                'align' => true,
                'char' => true,
                'charoff' => true,
                'span' => true,
                'valign' => true,
                'width' => true,
            ],
            'del' => [
                'datetime' => true,
            ],
            'dd' => [],
            'dfn' => [],
            'details' => [
                'align' => true,
                'open' => true,
            ],
            'div' => [
                'align' => true,
            ],
            'dl' => [],
            'dt' => [],
            'em' => [],
            'fieldset' => [],
            'figure' => [
                'align' => true,
            ],
            'figcaption' => [
                'align' => true,
            ],
            'font' => [
                'color' => true,
                'face' => true,
                'size' => true,
            ],
            'footer' => [
                'align' => true,
            ],
            'h1' => [
                'align' => true,
            ],
            'h2' => [
                'align' => true,
            ],
            'h3' => [
                'align' => true,
            ],
            'h4' => [
                'align' => true,
            ],
            'h5' => [
                'align' => true,
            ],
            'h6' => [
                'align' => true,
            ],
            'header' => [
                'align' => true,
            ],
            'hgroup' => [
                'align' => true,
            ],
            'hr' => [
                'align' => true,
                'noshade' => true,
                'size' => true,
                'width' => true,
            ],
            'i' => [],
            'img' => [
                'alt' => true,
                'align' => true,
                'border' => true,
                'height' => true,
                'hspace' => true,
                'loading' => true,
                'longdesc' => true,
                'vspace' => true,
                'src' => true,
                'usemap' => true,
                'width' => true,
            ],
            'ins' => [
                'datetime' => true,
                'cite' => true,
            ],
            'kbd' => [],
            'label' => [
                'for' => true,
            ],
            'legend' => [
                'align' => true,
            ],
            'li' => [
                'align' => true,
                'value' => true,
            ],
            'main' => [
                'align' => true,
            ],
            'map' => [
                'name' => true,
            ],
            'mark' => [],
            'menu' => [
                'type' => true,
            ],
            'nav' => [
                'align' => true,
            ],
            'object' => [
                'data' => [
                    'required' => true,
                    'value_callback' => '_wp_kses_allow_pdf_objects',
                ],
                'type' => [
                    'required' => true,
                    'values' => ['application/pdf'],
                ],
            ],
            'p' => [
                'align' => true,
            ],
            'pre' => [
                'width' => true,
            ],
            'q' => [
                'cite' => true,
            ],
            'rb' => [],
            'rp' => [],
            'rt' => [],
            'rtc' => [],
            'ruby' => [],
            's' => [],
            'samp' => [],
            'span' => [
                'align' => true,
            ],
            'section' => [
                'align' => true,
            ],
            'small' => [],
            'strike' => [],
            'strong' => [],
            'sub' => [],
            'summary' => [
                'align' => true,
            ],
            'sup' => [],
            'table' => [
                'align' => true,
                'bgcolor' => true,
                'border' => true,
                'cellpadding' => true,
                'cellspacing' => true,
                'rules' => true,
                'summary' => true,
                'width' => true,
            ],
            'tbody' => [
                'align' => true,
                'char' => true,
                'charoff' => true,
                'valign' => true,
            ],
            'td' => [
                'abbr' => true,
                'align' => true,
                'axis' => true,
                'bgcolor' => true,
                'char' => true,
                'charoff' => true,
                'colspan' => true,
                'headers' => true,
                'height' => true,
                'nowrap' => true,
                'rowspan' => true,
                'scope' => true,
                'valign' => true,
                'width' => true,
            ],
            'textarea' => [
                'cols' => true,
                'rows' => true,
                'disabled' => true,
                'name' => true,
                'readonly' => true,
            ],
            'tfoot' => [
                'align' => true,
                'char' => true,
                'charoff' => true,
                'valign' => true,
            ],
            'th' => [
                'abbr' => true,
                'align' => true,
                'axis' => true,
                'bgcolor' => true,
                'char' => true,
                'charoff' => true,
                'colspan' => true,
                'headers' => true,
                'height' => true,
                'nowrap' => true,
                'rowspan' => true,
                'scope' => true,
                'valign' => true,
                'width' => true,
            ],
            'thead' => [
                'align' => true,
                'char' => true,
                'charoff' => true,
                'valign' => true,
            ],
            'title' => [],
            'tr' => [
                'align' => true,
                'bgcolor' => true,
                'char' => true,
                'charoff' => true,
                'valign' => true,
            ],
            'track' => [
                'default' => true,
                'kind' => true,
                'label' => true,
                'src' => true,
                'srclang' => true,
            ],
            'tt' => [],
            'u' => [],
            'ul' => [
                'type' => true,
            ],
            'ol' => [
                'start' => true,
                'type' => true,
                'reversed' => true,
            ],
            'var' => [],
            'video' => [
                'autoplay' => true,
                'controls' => true,
                'height' => true,
                'loop' => true,
                'muted' => true,
                'playsinline' => true,
                'poster' => true,
                'preload' => true,
                'src' => true,
                'width' => true,
            ],
        ];

        $allowedtags = [
            'a' => [
                'href' => true,
                'title' => true,
            ],
            'abbr' => [
                'title' => true,
            ],
            'acronym' => [
                'title' => true,
            ],
            'b' => [],
            'blockquote' => [
                'cite' => true,
            ],
            'cite' => [],
            'code' => [],
            'del' => [
                'datetime' => true,
            ],
            'em' => [],
            'i' => [],
            'q' => [
                'cite' => true,
            ],
            's' => [],
            'strike' => [],
            'strong' => [],
        ];

        $allowedentitynames = [
            'nbsp',
            'iexcl',
            'cent',
            'pound',
            'curren',
            'yen',
            'brvbar',
            'sect',
            'uml',
            'copy',
            'ordf',
            'laquo',
            'not',
            'shy',
            'reg',
            'macr',
            'deg',
            'plusmn',
            'acute',
            'micro',
            'para',
            'middot',
            'cedil',
            'ordm',
            'raquo',
            'iquest',
            'Agrave',
            'Aacute',
            'Acirc',
            'Atilde',
            'Auml',
            'Aring',
            'AElig',
            'Ccedil',
            'Egrave',
            'Eacute',
            'Ecirc',
            'Euml',
            'Igrave',
            'Iacute',
            'Icirc',
            'Iuml',
            'ETH',
            'Ntilde',
            'Ograve',
            'Oacute',
            'Ocirc',
            'Otilde',
            'Ouml',
            'times',
            'Oslash',
            'Ugrave',
            'Uacute',
            'Ucirc',
            'Uuml',
            'Yacute',
            'THORN',
            'szlig',
            'agrave',
            'aacute',
            'acirc',
            'atilde',
            'auml',
            'aring',
            'aelig',
            'ccedil',
            'egrave',
            'eacute',
            'ecirc',
            'euml',
            'igrave',
            'iacute',
            'icirc',
            'iuml',
            'eth',
            'ntilde',
            'ograve',
            'oacute',
            'ocirc',
            'otilde',
            'ouml',
            'divide',
            'oslash',
            'ugrave',
            'uacute',
            'ucirc',
            'uuml',
            'yacute',
            'thorn',
            'yuml',
            'quot',
            'amp',
            'lt',
            'gt',
            'apos',
            'OElig',
            'oelig',
            'Scaron',
            'scaron',
            'Yuml',
            'circ',
            'tilde',
            'ensp',
            'emsp',
            'thinsp',
            'zwnj',
            'zwj',
            'lrm',
            'rlm',
            'ndash',
            'mdash',
            'lsquo',
            'rsquo',
            'sbquo',
            'ldquo',
            'rdquo',
            'bdquo',
            'dagger',
            'Dagger',
            'permil',
            'lsaquo',
            'rsaquo',
            'euro',
            'fnof',
            'Alpha',
            'Beta',
            'Gamma',
            'Delta',
            'Epsilon',
            'Zeta',
            'Eta',
            'Theta',
            'Iota',
            'Kappa',
            'Lambda',
            'Mu',
            'Nu',
            'Xi',
            'Omicron',
            'Pi',
            'Rho',
            'Sigma',
            'Tau',
            'Upsilon',
            'Phi',
            'Chi',
            'Psi',
            'Omega',
            'alpha',
            'beta',
            'gamma',
            'delta',
            'epsilon',
            'zeta',
            'eta',
            'theta',
            'iota',
            'kappa',
            'lambda',
            'mu',
            'nu',
            'xi',
            'omicron',
            'pi',
            'rho',
            'sigmaf',
            'sigma',
            'tau',
            'upsilon',
            'phi',
            'chi',
            'psi',
            'omega',
            'thetasym',
            'upsih',
            'piv',
            'bull',
            'hellip',
            'prime',
            'Prime',
            'oline',
            'frasl',
            'weierp',
            'image',
            'real',
            'trade',
            'alefsym',
            'larr',
            'uarr',
            'rarr',
            'darr',
            'harr',
            'crarr',
            'lArr',
            'uArr',
            'rArr',
            'dArr',
            'hArr',
            'forall',
            'part',
            'exist',
            'empty',
            'nabla',
            'isin',
            'notin',
            'ni',
            'prod',
            'sum',
            'minus',
            'lowast',
            'radic',
            'prop',
            'infin',
            'ang',
            'and',
            'or',
            'cap',
            'cup',
            'int',
            'sim',
            'cong',
            'asymp',
            'ne',
            'equiv',
            'le',
            'ge',
            'sub',
            'sup',
            'nsub',
            'sube',
            'supe',
            'oplus',
            'otimes',
            'perp',
            'sdot',
            'lceil',
            'rceil',
            'lfloor',
            'rfloor',
            'lang',
            'rang',
            'loz',
            'spades',
            'clubs',
            'hearts',
            'diams',
            'sup1',
            'sup2',
            'sup3',
            'frac14',
            'frac12',
            'frac34',
            'there4',
        ];

        $allowedxmlentitynames = [
            'amp',
            'lt',
            'gt',
            'apos',
            'quot',
        ];

        $allowedposttags = array_map('_wp_add_global_attributes', $allowedposttags);
    }
    else
    {
        $required_kses_globals = [
            'allowedposttags',
            'allowedtags',
            'allowedentitynames',
            'allowedxmlentitynames',
        ];
        $missing_kses_globals = [];

        foreach($required_kses_globals as $global_name)
        {
            if(! isset($GLOBALS[$global_name]) || ! is_array($GLOBALS[$global_name]))
            {
                $missing_kses_globals[] = '<code>$'.$global_name.'</code>';
            }
        }

        if($missing_kses_globals)
        {
            _doing_it_wrong('wp_kses_allowed_html', sprintf(/* translators: 1: CUSTOM_TAGS, 2: Global variable names. */ __('When using the %1$s constant, make sure to set these globals to an array: %2$s.'), '<code>CUSTOM_TAGS</code>', implode(', ', $missing_kses_globals)), '6.2.0');
        }

        $allowedtags = wp_kses_array_lc($allowedtags);
        $allowedposttags = wp_kses_array_lc($allowedposttags);
    }

    function wp_kses($content, $allowed_html, $allowed_protocols = [])
    {
        if(empty($allowed_protocols))
        {
            $allowed_protocols = wp_allowed_protocols();
        }

        $content = wp_kses_no_null($content, ['slash_zero' => 'keep']);
        $content = wp_kses_normalize_entities($content);
        $content = wp_kses_hook($content, $allowed_html, $allowed_protocols);

        return wp_kses_split($content, $allowed_html, $allowed_protocols);
    }

    function wp_kses_one_attr($attr, $element)
    {
        $uris = wp_kses_uri_attributes();
        $allowed_html = wp_kses_allowed_html('post');
        $allowed_protocols = wp_allowed_protocols();
        $attr = wp_kses_no_null($attr, ['slash_zero' => 'keep']);

        // Preserve leading and trailing whitespace.
        $matches = [];
        preg_match('/^\s*/', $attr, $matches);
        $lead = $matches[0];
        preg_match('/\s*$/', $attr, $matches);
        $trail = $matches[0];
        if(empty($trail))
        {
            $attr = substr($attr, strlen($lead));
        }
        else
        {
            $attr = substr($attr, strlen($lead), -strlen($trail));
        }

        // Parse attribute name and value from input.
        $split = preg_split('/\s*=\s*/', $attr, 2);
        $name = $split[0];
        if(count($split) === 2)
        {
            $value = $split[1];

            /*
		 * Remove quotes surrounding $value.
		 * Also guarantee correct quoting in $attr for this one attribute.
		 */
            if('' === $value)
            {
                $quote = '';
            }
            else
            {
                $quote = $value[0];
            }
            if('"' === $quote || "'" === $quote)
            {
                if(! str_ends_with($value, $quote))
                {
                    return '';
                }
                $value = substr($value, 1, -1);
            }
            else
            {
                $quote = '"';
            }

            // Sanitize quotes, angle braces, and entities.
            $value = esc_attr($value);

            // Sanitize URI values.
            if(in_array(strtolower($name), $uris, true))
            {
                $value = wp_kses_bad_protocol($value, $allowed_protocols);
            }

            $attr = "$name=$quote$value$quote";
            $vless = 'n';
        }
        else
        {
            $value = '';
            $vless = 'y';
        }

        // Sanitize attribute by name.
        wp_kses_attr_check($name, $value, $attr, $vless, $element, $allowed_html);

        // Restore whitespace.
        return $lead.$attr.$trail;
    }

    function wp_kses_allowed_html($context = '')
    {
        global $allowedposttags, $allowedtags, $allowedentitynames;

        if(is_array($context))
        {
            // When `$context` is an array it's actually an array of allowed HTML elements and attributes.
            $html = $context;
            $context = 'explicit';

            return apply_filters('wp_kses_allowed_html', $html, $context);
        }

        switch($context)
        {
            case 'post':
                $tags = apply_filters('wp_kses_allowed_html', $allowedposttags, $context);

                // 5.0.1 removed the `<form>` tag, allow it if a filter is allowing it's sub-elements `<input>` or `<select>`.
                if(! CUSTOM_TAGS && ! isset($tags['form']) && (isset($tags['input']) || isset($tags['select'])))
                {
                    $tags = $allowedposttags;

                    $tags['form'] = [
                        'action' => true,
                        'accept' => true,
                        'accept-charset' => true,
                        'enctype' => true,
                        'method' => true,
                        'name' => true,
                        'target' => true,
                    ];

                    $tags = apply_filters('wp_kses_allowed_html', $tags, $context);
                }

                return $tags;

            case 'user_description':
            case 'pre_user_description':
                $tags = $allowedtags;
                $tags['a']['rel'] = true;

                return apply_filters('wp_kses_allowed_html', $tags, $context);

            case 'strip':
                return apply_filters('wp_kses_allowed_html', [], $context);

            case 'entities':
                return apply_filters('wp_kses_allowed_html', $allowedentitynames, $context);

            case 'data':
            default:
                return apply_filters('wp_kses_allowed_html', $allowedtags, $context);
        }
    }

    function wp_kses_hook($content, $allowed_html, $allowed_protocols)
    {
        return apply_filters('pre_kses', $content, $allowed_html, $allowed_protocols);
    }

    function wp_kses_version()
    {
        return '0.2.2';
    }

    function wp_kses_split($content, $allowed_html, $allowed_protocols)
    {
        global $pass_allowed_html, $pass_allowed_protocols;

        $pass_allowed_html = $allowed_html;
        $pass_allowed_protocols = $allowed_protocols;

        return preg_replace_callback('%(<!--.*?(-->|$))|(<[^>]*(>|$)|>)%', '_wp_kses_split_callback', $content);
    }

    function wp_kses_uri_attributes()
    {
        $uri_attributes = [
            'action',
            'archive',
            'background',
            'cite',
            'classid',
            'codebase',
            'data',
            'formaction',
            'href',
            'icon',
            'longdesc',
            'manifest',
            'poster',
            'profile',
            'src',
            'usemap',
            'xmlns',
        ];

        $uri_attributes = apply_filters('wp_kses_uri_attributes', $uri_attributes);

        return $uri_attributes;
    }

    function _wp_kses_split_callback($matches)
    {
        global $pass_allowed_html, $pass_allowed_protocols;

        return wp_kses_split2($matches[0], $pass_allowed_html, $pass_allowed_protocols);
    }

    function wp_kses_split2($content, $allowed_html, $allowed_protocols)
    {
        $content = wp_kses_stripslashes($content);

        // It matched a ">" character.
        if(! str_starts_with($content, '<'))
        {
            return '&gt;';
        }

        // Allow HTML comments.
        if(str_starts_with($content, '<!--'))
        {
            $content = str_replace(['<!--', '-->'], '', $content);

            while(($newstring = wp_kses($content, $allowed_html, $allowed_protocols)) !== $content)
            {
                $content = $newstring;
            }

            if('' === $content)
            {
                return '';
            }

            // Prevent multiple dashes in comments.
            $content = preg_replace('/--+/', '-', $content);
            // Prevent three dashes closing a comment.
            $content = preg_replace('/-$/', '', $content);

            return "<!--{$content}-->";
        }

        // It's seriously malformed.
        if(! preg_match('%^<\s*(/\s*)?([a-zA-Z0-9-]+)([^>]*)>?$%', $content, $matches))
        {
            return '';
        }

        $slash = trim($matches[1]);
        $elem = $matches[2];
        $attrlist = $matches[3];

        if(! is_array($allowed_html))
        {
            $allowed_html = wp_kses_allowed_html($allowed_html);
        }

        // They are using a not allowed HTML element.
        if(! isset($allowed_html[strtolower($elem)]))
        {
            return '';
        }

        // No attributes are allowed for closing elements.
        if('' !== $slash)
        {
            return "</$elem>";
        }

        return wp_kses_attr($elem, $attrlist, $allowed_html, $allowed_protocols);
    }

    function wp_kses_attr($element, $attr, $allowed_html, $allowed_protocols)
    {
        if(! is_array($allowed_html))
        {
            $allowed_html = wp_kses_allowed_html($allowed_html);
        }

        // Is there a closing XHTML slash at the end of the attributes?
        $xhtml_slash = '';
        if(preg_match('%\s*/\s*$%', $attr))
        {
            $xhtml_slash = ' /';
        }

        // Are any attributes allowed at all for this element?
        $element_low = strtolower($element);
        if(empty($allowed_html[$element_low]) || true === $allowed_html[$element_low])
        {
            return "<$element$xhtml_slash>";
        }

        // Split it.
        $attrarr = wp_kses_hair($attr, $allowed_protocols);

        // Check if there are attributes that are required.
        $required_attrs = array_filter($allowed_html[$element_low], static function($required_attr_limits)
        {
            return isset($required_attr_limits['required']) && true === $required_attr_limits['required'];
        });

        /*
	 * If a required attribute check fails, we can return nothing for a self-closing tag,
	 * but for a non-self-closing tag the best option is to return the element with attributes,
	 * as KSES doesn't handle matching the relevant closing tag.
	 */
        $stripped_tag = '';
        if(empty($xhtml_slash))
        {
            $stripped_tag = "<$element>";
        }

        // Go through $attrarr, and save the allowed attributes for this element in $attr2.
        $attr2 = '';
        foreach($attrarr as $arreach)
        {
            // Check if this attribute is required.
            $required = isset($required_attrs[strtolower($arreach['name'])]);

            if(wp_kses_attr_check($arreach['name'], $arreach['value'], $arreach['whole'], $arreach['vless'], $element, $allowed_html))
            {
                $attr2 .= ' '.$arreach['whole'];

                // If this was a required attribute, we can mark it as found.
                if($required)
                {
                    unset($required_attrs[strtolower($arreach['name'])]);
                }
            }
            elseif($required)
            {
                // This attribute was required, but didn't pass the check. The entire tag is not allowed.
                return $stripped_tag;
            }
        }

        // If some required attributes weren't set, the entire tag is not allowed.
        if(! empty($required_attrs))
        {
            return $stripped_tag;
        }

        // Remove any "<" or ">" characters.
        $attr2 = preg_replace('/[<>]/', '', $attr2);

        return "<$element$attr2$xhtml_slash>";
    }

    function wp_kses_attr_check(&$name, &$value, &$whole, $vless, $element, $allowed_html)
    {
        $name_low = strtolower($name);
        $element_low = strtolower($element);

        if(! isset($allowed_html[$element_low]))
        {
            $name = '';
            $value = '';
            $whole = '';

            return false;
        }

        $allowed_attr = $allowed_html[$element_low];

        if(! isset($allowed_attr[$name_low]) || '' === $allowed_attr[$name_low])
        {
            /*
		 * Allow `data-*` attributes.
		 *
		 * When specifying `$allowed_html`, the attribute name should be set as
		 * `data-*` (not to be mixed with the HTML 4.0 `data` attribute, see
		 * https://www.w3.org/TR/html40/struct/objects.html#adef-data).
		 *
		 * Note: the attribute name should only contain `A-Za-z0-9_-` chars,
		 * double hyphens `--` are not accepted by WordPress.
		 */
            if(str_starts_with($name_low, 'data-') && ! empty($allowed_attr['data-*']) && preg_match('/^data(?:-[a-z0-9_]+)+$/', $name_low, $match))
            {
                /*
			 * Add the whole attribute name to the allowed attributes and set any restrictions
			 * for the `data-*` attribute values for the current element.
			 */
                $allowed_attr[$match[0]] = $allowed_attr['data-*'];
            }
            else
            {
                $name = '';
                $value = '';
                $whole = '';

                return false;
            }
        }

        if('style' === $name_low)
        {
            $new_value = safecss_filter_attr($value);

            if(empty($new_value))
            {
                $name = '';
                $value = '';
                $whole = '';

                return false;
            }

            $whole = str_replace($value, $new_value, $whole);
            $value = $new_value;
        }

        if(is_array($allowed_attr[$name_low]))
        {
            // There are some checks.
            foreach($allowed_attr[$name_low] as $currkey => $currval)
            {
                if(! wp_kses_check_attr_val($value, $vless, $currkey, $currval))
                {
                    $name = '';
                    $value = '';
                    $whole = '';

                    return false;
                }
            }
        }

        return true;
    }

    function wp_kses_hair($attr, $allowed_protocols)
    {
        $attrarr = [];
        $mode = 0;
        $attrname = '';
        $uris = wp_kses_uri_attributes();

        // Loop through the whole attribute list.

        while(strlen($attr) !== 0)
        {
            $working = 0; // Was the last operation successful?

            switch($mode)
            {
                case 0:
                    if(preg_match('/^([_a-zA-Z][-_a-zA-Z0-9:.]*)/', $attr, $match))
                    {
                        $attrname = $match[1];
                        $working = 1;
                        $mode = 1;
                        $attr = preg_replace('/^[_a-zA-Z][-_a-zA-Z0-9:.]*/', '', $attr);
                    }

                    break;

                case 1:
                    if(preg_match('/^\s*=\s*/', $attr))
                    { // Equals sign.
                        $working = 1;
                        $mode = 2;
                        $attr = preg_replace('/^\s*=\s*/', '', $attr);
                        break;
                    }

                    if(preg_match('/^\s+/', $attr))
                    { // Valueless.
                        $working = 1;
                        $mode = 0;

                        if(false === array_key_exists($attrname, $attrarr))
                        {
                            $attrarr[$attrname] = [
                                'name' => $attrname,
                                'value' => '',
                                'whole' => $attrname,
                                'vless' => 'y',
                            ];
                        }

                        $attr = preg_replace('/^\s+/', '', $attr);
                    }

                    break;

                case 2:
                    if(preg_match('%^"([^"]*)"(\s+|/?$)%', $attr, $match))
                    {
                        // "value"
                        $thisval = $match[1];
                        if(in_array(strtolower($attrname), $uris, true))
                        {
                            $thisval = wp_kses_bad_protocol($thisval, $allowed_protocols);
                        }

                        if(false === array_key_exists($attrname, $attrarr))
                        {
                            $attrarr[$attrname] = [
                                'name' => $attrname,
                                'value' => $thisval,
                                'whole' => "$attrname=\"$thisval\"",
                                'vless' => 'n',
                            ];
                        }

                        $working = 1;
                        $mode = 0;
                        $attr = preg_replace('/^"[^"]*"(\s+|$)/', '', $attr);
                        break;
                    }

                    if(preg_match("%^'([^']*)'(\s+|/?$)%", $attr, $match))
                    {
                        // 'value'
                        $thisval = $match[1];
                        if(in_array(strtolower($attrname), $uris, true))
                        {
                            $thisval = wp_kses_bad_protocol($thisval, $allowed_protocols);
                        }

                        if(false === array_key_exists($attrname, $attrarr))
                        {
                            $attrarr[$attrname] = [
                                'name' => $attrname,
                                'value' => $thisval,
                                'whole' => "$attrname='$thisval'",
                                'vless' => 'n',
                            ];
                        }

                        $working = 1;
                        $mode = 0;
                        $attr = preg_replace("/^'[^']*'(\s+|$)/", '', $attr);
                        break;
                    }

                    if(preg_match("%^([^\s\"']+)(\s+|/?$)%", $attr, $match))
                    {
                        // value
                        $thisval = $match[1];
                        if(in_array(strtolower($attrname), $uris, true))
                        {
                            $thisval = wp_kses_bad_protocol($thisval, $allowed_protocols);
                        }

                        if(false === array_key_exists($attrname, $attrarr))
                        {
                            $attrarr[$attrname] = [
                                'name' => $attrname,
                                'value' => $thisval,
                                'whole' => "$attrname=\"$thisval\"",
                                'vless' => 'n',
                            ];
                        }

                        // We add quotes to conform to W3C's HTML spec.
                        $working = 1;
                        $mode = 0;
                        $attr = preg_replace("%^[^\s\"']+(\s+|$)%", '', $attr);
                    }

                    break;
            } // End switch.

            if(0 === $working)
            { // Not well-formed, remove and try again.
                $attr = wp_kses_html_error($attr);
                $mode = 0;
            }
        } // End while.

        if(1 === $mode && false === array_key_exists($attrname, $attrarr))
        {
            /*
		 * Special case, for when the attribute list ends with a valueless
		 * attribute like "selected".
		 */
            $attrarr[$attrname] = [
                'name' => $attrname,
                'value' => '',
                'whole' => $attrname,
                'vless' => 'y',
            ];
        }

        return $attrarr;
    }

    function wp_kses_attr_parse($element)
    {
        $valid = preg_match('%^(<\s*)(/\s*)?([a-zA-Z0-9]+\s*)([^>]*)(>?)$%', $element, $matches);
        if(1 !== $valid)
        {
            return false;
        }

        $begin = $matches[1];
        $slash = $matches[2];
        $elname = $matches[3];
        $attr = $matches[4];
        $end = $matches[5];

        if('' !== $slash)
        {
            // Closing elements do not get parsed.
            return false;
        }

        // Is there a closing XHTML slash at the end of the attributes?
        if(1 === preg_match('%\s*/\s*$%', $attr, $matches))
        {
            $xhtml_slash = $matches[0];
            $attr = substr($attr, 0, -strlen($xhtml_slash));
        }
        else
        {
            $xhtml_slash = '';
        }

        // Split it.
        $attrarr = wp_kses_hair_parse($attr);
        if(false === $attrarr)
        {
            return false;
        }

        // Make sure all input is returned by adding front and back matter.
        array_unshift($attrarr, $begin.$slash.$elname);
        array_push($attrarr, $xhtml_slash.$end);

        return $attrarr;
    }

    function wp_kses_hair_parse($attr)
    {
        if('' === $attr)
        {
            return [];
        }

        // phpcs:disable Squiz.Strings.ConcatenationSpacing.PaddingFound -- don't remove regex indentation
        $regex = '(?:'.'[_a-zA-Z][-_a-zA-Z0-9:.]*' // Attribute name.
            .'|'.'\[\[?[^\[\]]+\]\]?'        // Shortcode in the name position implies unfiltered_html.
            .')'.'(?:'               // Attribute value.
            .'\s*=\s*'       // All values begin with '='.
            .'(?:'.'"[^"]*"'   // Double-quoted.
            .'|'."'[^']*'"   // Single-quoted.
            .'|'.'[^\s"\']+' // Non-quoted.
            .'(?:\s|$)'  // Must have a space.
            .')'.'|'.'(?:\s|$)'      // If attribute has no value, space is required.
            .')'.'\s*';              // Trailing space is optional except as mentioned above.
        // phpcs:enable

        /*
	 * Although it is possible to reduce this procedure to a single regexp,
	 * we must run that regexp twice to get exactly the expected result.
	 */

        $validation = "%^($regex)+$%";
        $extraction = "%$regex%";

        if(1 === preg_match($validation, $attr))
        {
            preg_match_all($extraction, $attr, $attrarr);

            return $attrarr[0];
        }
        else
        {
            return false;
        }
    }

    function wp_kses_check_attr_val($value, $vless, $checkname, $checkvalue)
    {
        $ok = true;

        switch(strtolower($checkname))
        {
            case 'maxlen':
                /*
			 * The maxlen check makes sure that the attribute value has a length not
			 * greater than the given value. This can be used to avoid Buffer Overflows
			 * in WWW clients and various Internet servers.
			 */

                if(strlen($value) > $checkvalue)
                {
                    $ok = false;
                }
                break;

            case 'minlen':
                /*
			 * The minlen check makes sure that the attribute value has a length not
			 * smaller than the given value.
			 */

                if(strlen($value) < $checkvalue)
                {
                    $ok = false;
                }
                break;

            case 'maxval':
                /*
			 * The maxval check does two things: it checks that the attribute value is
			 * an integer from 0 and up, without an excessive amount of zeroes or
			 * whitespace (to avoid Buffer Overflows). It also checks that the attribute
			 * value is not greater than the given value.
			 * This check can be used to avoid Denial of Service attacks.
			 */

                if(! preg_match('/^\s{0,6}[0-9]{1,6}\s{0,6}$/', $value))
                {
                    $ok = false;
                }
                if($value > $checkvalue)
                {
                    $ok = false;
                }
                break;

            case 'minval':
                /*
			 * The minval check makes sure that the attribute value is a positive integer,
			 * and that it is not smaller than the given value.
			 */

                if(! preg_match('/^\s{0,6}[0-9]{1,6}\s{0,6}$/', $value))
                {
                    $ok = false;
                }
                if($value < $checkvalue)
                {
                    $ok = false;
                }
                break;

            case 'valueless':
                /*
			 * The valueless check makes sure if the attribute has a value
			 * (like `<a href="blah">`) or not (`<option selected>`). If the given value
			 * is a "y" or a "Y", the attribute must not have a value.
			 * If the given value is an "n" or an "N", the attribute must have a value.
			 */

                if(strtolower($checkvalue) !== $vless)
                {
                    $ok = false;
                }
                break;

            case 'values':
                /*
			 * The values check is used when you want to make sure that the attribute
			 * has one of the given values.
			 */

                if(false === array_search(strtolower($value), $checkvalue, true))
                {
                    $ok = false;
                }
                break;

            case 'value_callback':
                /*
			 * The value_callback check is used when you want to make sure that the attribute
			 * value is accepted by the callback function.
			 */

                if(! call_user_func($checkvalue, $value))
                {
                    $ok = false;
                }
                break;
        } // End switch.

        return $ok;
    }

    function wp_kses_bad_protocol($content, $allowed_protocols)
    {
        $content = wp_kses_no_null($content);

        // Short-circuit if the string starts with `https://` or `http://`. Most common cases.
        if((str_starts_with($content, 'https://') && in_array('https', $allowed_protocols, true)) || (str_starts_with($content, 'http://') && in_array('http', $allowed_protocols, true)))
        {
            return $content;
        }

        $iterations = 0;

        do
        {
            $original_content = $content;
            $content = wp_kses_bad_protocol_once($content, $allowed_protocols);
        }
        while($original_content !== $content && ++$iterations < 6);

        if($original_content !== $content)
        {
            return '';
        }

        return $content;
    }

    function wp_kses_no_null($content, $options = null)
    {
        if(! isset($options['slash_zero']))
        {
            $options = ['slash_zero' => 'remove'];
        }

        $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $content);
        if('remove' === $options['slash_zero'])
        {
            $content = preg_replace('/\\\\+0+/', '', $content);
        }

        return $content;
    }

    function wp_kses_stripslashes($content)
    {
        return preg_replace('%\\\\"%', '"', $content);
    }

    function wp_kses_array_lc($inarray)
    {
        $outarray = [];

        foreach((array) $inarray as $inkey => $inval)
        {
            $outkey = strtolower($inkey);
            $outarray[$outkey] = [];

            foreach((array) $inval as $inkey2 => $inval2)
            {
                $outkey2 = strtolower($inkey2);
                $outarray[$outkey][$outkey2] = $inval2;
            }
        }

        return $outarray;
    }

    function wp_kses_html_error($attr)
    {
        return preg_replace('/^("[^"]*("|$)|\'[^\']*(\'|$)|\S)*\s*/', '', $attr);
    }

    function wp_kses_bad_protocol_once($content, $allowed_protocols, $count = 1)
    {
        $content = preg_replace('/(&#0*58(?![;0-9])|&#x0*3a(?![;a-f0-9]))/i', '$1;', $content);
        $content2 = preg_split('/:|&#0*58;|&#x0*3a;|&colon;/i', $content, 2);

        if(isset($content2[1]) && ! preg_match('%/\?%', $content2[0]))
        {
            $content = trim($content2[1]);
            $protocol = wp_kses_bad_protocol_once2($content2[0], $allowed_protocols);
            if('feed:' === $protocol)
            {
                if($count > 2)
                {
                    return '';
                }
                $content = wp_kses_bad_protocol_once($content, $allowed_protocols, ++$count);
                if(empty($content))
                {
                    return $content;
                }
            }
            $content = $protocol.$content;
        }

        return $content;
    }

    function wp_kses_bad_protocol_once2($scheme, $allowed_protocols)
    {
        $scheme = wp_kses_decode_entities($scheme);
        $scheme = preg_replace('/\s/', '', $scheme);
        $scheme = wp_kses_no_null($scheme);
        $scheme = strtolower($scheme);

        $allowed = false;
        foreach((array) $allowed_protocols as $one_protocol)
        {
            if(strtolower($one_protocol) === $scheme)
            {
                $allowed = true;
                break;
            }
        }

        if($allowed)
        {
            return "$scheme:";
        }
        else
        {
            return '';
        }
    }

    function wp_kses_normalize_entities($content, $context = 'html')
    {
        // Disarm all entities by converting & to &amp;
        $content = str_replace('&', '&amp;', $content);

        // Change back the allowed entities in our list of allowed entities.
        if('xml' === $context)
        {
            $content = preg_replace_callback('/&amp;([A-Za-z]{2,8}[0-9]{0,2});/', 'wp_kses_xml_named_entities', $content);
        }
        else
        {
            $content = preg_replace_callback('/&amp;([A-Za-z]{2,8}[0-9]{0,2});/', 'wp_kses_named_entities', $content);
        }
        $content = preg_replace_callback('/&amp;#(0*[0-9]{1,7});/', 'wp_kses_normalize_entities2', $content);
        $content = preg_replace_callback('/&amp;#[Xx](0*[0-9A-Fa-f]{1,6});/', 'wp_kses_normalize_entities3', $content);

        return $content;
    }

    function wp_kses_named_entities($matches)
    {
        global $allowedentitynames;

        if(empty($matches[1]))
        {
            return '';
        }

        $i = $matches[1];

        return (! in_array($i, $allowedentitynames, true)) ? "&amp;$i;" : "&$i;";
    }

    function wp_kses_xml_named_entities($matches)
    {
        global $allowedentitynames, $allowedxmlentitynames;

        if(empty($matches[1]))
        {
            return '';
        }

        $i = $matches[1];

        if(in_array($i, $allowedxmlentitynames, true))
        {
            return "&$i;";
        }
        elseif(in_array($i, $allowedentitynames, true))
        {
            return html_entity_decode("&$i;", ENT_HTML5);
        }

        return "&amp;$i;";
    }

    function wp_kses_normalize_entities2($matches)
    {
        if(empty($matches[1]))
        {
            return '';
        }

        $i = $matches[1];

        if(valid_unicode($i))
        {
            $i = str_pad(ltrim($i, '0'), 3, '0', STR_PAD_LEFT);
            $i = "&#$i;";
        }
        else
        {
            $i = "&amp;#$i;";
        }

        return $i;
    }

    function wp_kses_normalize_entities3($matches)
    {
        if(empty($matches[1]))
        {
            return '';
        }

        $hexchars = $matches[1];

        return (! valid_unicode(hexdec($hexchars))) ? "&amp;#x$hexchars;" : '&#x'.ltrim($hexchars, '0').';';
    }

    function valid_unicode($i)
    {
        $i = (int) $i;

        return (0x9 === $i || 0xa === $i || 0xd === $i || (0x20 <= $i && $i <= 0xd7ff) || (0xe000 <= $i && $i <= 0xfffd) || (0x10000 <= $i && $i <= 0x10ffff));
    }

    function wp_kses_decode_entities($content)
    {
        $content = preg_replace_callback('/&#([0-9]+);/', '_wp_kses_decode_entities_chr', $content);
        $content = preg_replace_callback('/&#[Xx]([0-9A-Fa-f]+);/', '_wp_kses_decode_entities_chr_hexdec', $content);

        return $content;
    }

    function _wp_kses_decode_entities_chr($matches)
    {
        return chr($matches[1]);
    }

    function _wp_kses_decode_entities_chr_hexdec($matches)
    {
        return chr(hexdec($matches[1]));
    }

    function wp_filter_kses($data)
    {
        return addslashes(wp_kses(stripslashes($data), current_filter()));
    }

    function wp_kses_data($data)
    {
        return wp_kses($data, current_filter());
    }

    function wp_filter_post_kses($data)
    {
        return addslashes(wp_kses(stripslashes($data), 'post'));
    }

    function wp_filter_global_styles_post($data)
    {
        $decoded_data = json_decode(wp_unslash($data), true);
        $json_decoding_error = json_last_error();
        if(JSON_ERROR_NONE === $json_decoding_error && is_array($decoded_data) && isset($decoded_data['isGlobalStylesUserThemeJSON']) && $decoded_data['isGlobalStylesUserThemeJSON'])
        {
            unset($decoded_data['isGlobalStylesUserThemeJSON']);

            $data_to_encode = WP_Theme_JSON::remove_insecure_properties($decoded_data);

            $data_to_encode['isGlobalStylesUserThemeJSON'] = true;

            return wp_slash(wp_json_encode($data_to_encode));
        }

        return $data;
    }

    function wp_kses_post($data)
    {
        return wp_kses($data, 'post');
    }

    function wp_kses_post_deep($data)
    {
        return map_deep($data, 'wp_kses_post');
    }

    function wp_filter_nohtml_kses($data)
    {
        return addslashes(wp_kses(stripslashes($data), 'strip'));
    }

    function kses_init_filters()
    {
        // Normal filtering.
        add_filter('title_save_pre', 'wp_filter_kses');

        // Comment filtering.
        if(current_user_can('unfiltered_html'))
        {
            add_filter('pre_comment_content', 'wp_filter_post_kses');
        }
        else
        {
            add_filter('pre_comment_content', 'wp_filter_kses');
        }

        // Global Styles filtering: Global Styles filters should be executed before normal post_kses HTML filters.
        add_filter('content_save_pre', 'wp_filter_global_styles_post', 9);
        add_filter('content_filtered_save_pre', 'wp_filter_global_styles_post', 9);

        // Post filtering.
        add_filter('content_save_pre', 'wp_filter_post_kses');
        add_filter('excerpt_save_pre', 'wp_filter_post_kses');
        add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
    }

    function kses_remove_filters()
    {
        // Normal filtering.
        remove_filter('title_save_pre', 'wp_filter_kses');

        // Comment filtering.
        remove_filter('pre_comment_content', 'wp_filter_post_kses');
        remove_filter('pre_comment_content', 'wp_filter_kses');

        // Global Styles filtering.
        remove_filter('content_save_pre', 'wp_filter_global_styles_post', 9);
        remove_filter('content_filtered_save_pre', 'wp_filter_global_styles_post', 9);

        // Post filtering.
        remove_filter('content_save_pre', 'wp_filter_post_kses');
        remove_filter('excerpt_save_pre', 'wp_filter_post_kses');
        remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
    }

    function kses_init()
    {
        kses_remove_filters();

        if(! current_user_can('unfiltered_html'))
        {
            kses_init_filters();
        }
    }

    function safecss_filter_attr($css, $deprecated = '')
    {
        if(! empty($deprecated))
        {
            _deprecated_argument(__FUNCTION__, '2.8.1'); // Never implemented.
        }

        $css = wp_kses_no_null($css);
        $css = str_replace(["\n", "\r", "\t"], '', $css);

        $allowed_protocols = wp_allowed_protocols();

        $css_array = explode(';', trim($css));

        $allowed_attr = apply_filters('safe_style_css', [
            'background',
            'background-color',
            'background-image',
            'background-position',
            'background-size',
            'background-attachment',
            'background-blend-mode',

            'border',
            'border-radius',
            'border-width',
            'border-color',
            'border-style',
            'border-right',
            'border-right-color',
            'border-right-style',
            'border-right-width',
            'border-bottom',
            'border-bottom-color',
            'border-bottom-left-radius',
            'border-bottom-right-radius',
            'border-bottom-style',
            'border-bottom-width',
            'border-bottom-right-radius',
            'border-bottom-left-radius',
            'border-left',
            'border-left-color',
            'border-left-style',
            'border-left-width',
            'border-top',
            'border-top-color',
            'border-top-left-radius',
            'border-top-right-radius',
            'border-top-style',
            'border-top-width',
            'border-top-left-radius',
            'border-top-right-radius',

            'border-spacing',
            'border-collapse',
            'caption-side',

            'columns',
            'column-count',
            'column-fill',
            'column-gap',
            'column-rule',
            'column-span',
            'column-width',

            'color',
            'filter',
            'font',
            'font-family',
            'font-size',
            'font-style',
            'font-variant',
            'font-weight',
            'letter-spacing',
            'line-height',
            'text-align',
            'text-decoration',
            'text-indent',
            'text-transform',

            'height',
            'min-height',
            'max-height',

            'width',
            'min-width',
            'max-width',

            'margin',
            'margin-right',
            'margin-bottom',
            'margin-left',
            'margin-top',
            'margin-block-start',
            'margin-block-end',
            'margin-inline-start',
            'margin-inline-end',

            'padding',
            'padding-right',
            'padding-bottom',
            'padding-left',
            'padding-top',
            'padding-block-start',
            'padding-block-end',
            'padding-inline-start',
            'padding-inline-end',

            'flex',
            'flex-basis',
            'flex-direction',
            'flex-flow',
            'flex-grow',
            'flex-shrink',
            'flex-wrap',

            'gap',
            'column-gap',
            'row-gap',

            'grid-template-columns',
            'grid-auto-columns',
            'grid-column-start',
            'grid-column-end',
            'grid-column-gap',
            'grid-template-rows',
            'grid-auto-rows',
            'grid-row-start',
            'grid-row-end',
            'grid-row-gap',
            'grid-gap',

            'justify-content',
            'justify-items',
            'justify-self',
            'align-content',
            'align-items',
            'align-self',

            'clear',
            'cursor',
            'direction',
            'float',
            'list-style-type',
            'object-fit',
            'object-position',
            'overflow',
            'vertical-align',

            'position',
            'top',
            'right',
            'bottom',
            'left',
            'z-index',
            'box-shadow',
            'aspect-ratio',

            // Custom CSS properties.
            '--*',
        ]);

        /*
	 * CSS attributes that accept URL data types.
	 *
	 * This is in accordance to the CSS spec and unrelated to
	 * the sub-set of supported attributes above.
	 *
	 * See: https://developer.mozilla.org/en-US/docs/Web/CSS/url
	 */
        $css_url_data_types = [
            'background',
            'background-image',

            'cursor',
            'filter',

            'list-style',
            'list-style-image',
        ];

        /*
	 * CSS attributes that accept gradient data types.
	 *
	 */
        $css_gradient_data_types = [
            'background',
            'background-image',
        ];

        if(empty($allowed_attr))
        {
            return $css;
        }

        $css = '';
        foreach($css_array as $css_item)
        {
            if('' === $css_item)
            {
                continue;
            }

            $css_item = trim($css_item);
            $css_test_string = $css_item;
            $found = false;
            $url_attr = false;
            $gradient_attr = false;
            $is_custom_var = false;

            if(! str_contains($css_item, ':'))
            {
                $found = true;
            }
            else
            {
                $parts = explode(':', $css_item, 2);
                $css_selector = trim($parts[0]);

                // Allow assigning values to CSS variables.
                if(in_array('--*', $allowed_attr, true) && preg_match('/^--[a-zA-Z0-9-_]+$/', $css_selector))
                {
                    $allowed_attr[] = $css_selector;
                    $is_custom_var = true;
                }

                if(in_array($css_selector, $allowed_attr, true))
                {
                    $found = true;
                    $url_attr = in_array($css_selector, $css_url_data_types, true);
                    $gradient_attr = in_array($css_selector, $css_gradient_data_types, true);
                }

                if($is_custom_var)
                {
                    $css_value = trim($parts[1]);
                    $url_attr = str_starts_with($css_value, 'url(');
                    $gradient_attr = str_contains($css_value, '-gradient(');
                }
            }

            if($found && $url_attr)
            {
                // Simplified: matches the sequence `url(*)`.
                preg_match_all('/url\([^)]+\)/', $parts[1], $url_matches);

                foreach($url_matches[0] as $url_match)
                {
                    // Clean up the URL from each of the matches above.
                    preg_match('/^url\(\s*([\'\"]?)(.*)(\g1)\s*\)$/', $url_match, $url_pieces);

                    if(empty($url_pieces[2]))
                    {
                        $found = false;
                        break;
                    }

                    $url = trim($url_pieces[2]);

                    if(empty($url) || wp_kses_bad_protocol($url, $allowed_protocols) !== $url)
                    {
                        $found = false;
                        break;
                    }
                    else
                    {
                        // Remove the whole `url(*)` bit that was matched above from the CSS.
                        $css_test_string = str_replace($url_match, '', $css_test_string);
                    }
                }
            }

            if($found && $gradient_attr)
            {
                $css_value = trim($parts[1]);
                if(preg_match('/^(repeating-)?(linear|radial|conic)-gradient\(([^()]|rgb[a]?\([^()]*\))*\)$/', $css_value))
                {
                    // Remove the whole `gradient` bit that was matched above from the CSS.
                    $css_test_string = str_replace($css_value, '', $css_test_string);
                }
            }

            if($found)
            {
                /*
			 * Allow CSS functions like var(), calc(), etc. by removing them from the test string.
			 * Nested functions and parentheses are also removed, so long as the parentheses are balanced.
			 */
                $css_test_string = preg_replace('/\b(?:var|calc|min|max|minmax|clamp|repeat)(\((?:[^()]|(?1))*\))/', '', $css_test_string);

                /*
			 * Disallow CSS containing \ ( & } = or comments, except for within url(), var(), calc(), etc.
			 * which were removed from the test string above.
			 */
                $allow_css = ! preg_match('%[\\\(&=}]|/\*%', $css_test_string);

                $allow_css = apply_filters('safecss_filter_attr_allow_css', $allow_css, $css_test_string);

                // Only add the CSS part if it passes the regex check.
                if($allow_css)
                {
                    if('' !== $css)
                    {
                        $css .= ';';
                    }

                    $css .= $css_item;
                }
            }
        }

        return $css;
    }

    function _wp_add_global_attributes($value)
    {
        $global_attributes = [
            'aria-controls' => true,
            'aria-current' => true,
            'aria-describedby' => true,
            'aria-details' => true,
            'aria-expanded' => true,
            'aria-label' => true,
            'aria-labelledby' => true,
            'aria-hidden' => true,
            'class' => true,
            'data-*' => true,
            'dir' => true,
            'id' => true,
            'lang' => true,
            'style' => true,
            'title' => true,
            'role' => true,
            'xml:lang' => true,
        ];

        if(true === $value)
        {
            $value = [];
        }

        if(is_array($value))
        {
            return array_merge($value, $global_attributes);
        }

        return $value;
    }

    function _wp_kses_allow_pdf_objects($url)
    {
        // We're not interested in URLs that contain query strings or fragments.
        if(str_contains($url, '?') || str_contains($url, '#'))
        {
            return false;
        }

        // If it doesn't have a PDF extension, it's not safe.
        if(! str_ends_with($url, '.pdf'))
        {
            return false;
        }

        // If the URL host matches the current site's media URL, it's safe.
        $upload_info = wp_upload_dir(null, false);
        $parsed_url = wp_parse_url($upload_info['url']);
        $upload_host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $upload_port = isset($parsed_url['port']) ? ':'.$parsed_url['port'] : '';

        if(str_starts_with($url, "http://$upload_host$upload_port/") || str_starts_with($url, "https://$upload_host$upload_port/"))
        {
            return true;
        }

        return false;
    }
