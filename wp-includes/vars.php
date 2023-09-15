<?php

    global $pagenow, $is_lynx, $is_gecko, $is_winIE, $is_macIE, $is_opera, $is_NS4, $is_safari, $is_chrome, $is_iphone, $is_IE, $is_edge, $is_apache, $is_IIS, $is_iis7, $is_nginx;

// On which page are we?
    if(is_admin())
    {
        // wp-admin pages are checked more carefully.
        if(is_network_admin())
        {
            preg_match('#/wp-admin/network/?(.*?)$#i', $_SERVER['PHP_SELF'], $self_matches);
        }
        elseif(is_user_admin())
        {
            preg_match('#/wp-admin/user/?(.*?)$#i', $_SERVER['PHP_SELF'], $self_matches);
        }
        else
        {
            preg_match('#/wp-admin/?(.*?)$#i', $_SERVER['PHP_SELF'], $self_matches);
        }

        $pagenow = ! empty($self_matches[1]) ? $self_matches[1] : '';
        $pagenow = trim($pagenow, '/');
        $pagenow = preg_replace('#\?.*?$#', '', $pagenow);

        if('' === $pagenow || 'index' === $pagenow || 'index.php' === $pagenow)
        {
            $pagenow = 'index.php';
        }
        else
        {
            preg_match('#(.*?)(/|$)#', $pagenow, $self_matches);
            $pagenow = strtolower($self_matches[1]);
            if(! str_ends_with($pagenow, '.php'))
            {
                $pagenow .= '.php'; // For `Options +Multiviews`: /wp-admin/themes/index.php (themes.php is queried).
            }
        }
    }
    else
    {
        if(preg_match('#([^/]+\.php)([?/].*?)?$#i', $_SERVER['PHP_SELF'], $self_matches))
        {
            $pagenow = strtolower($self_matches[1]);
        }
        else
        {
            $pagenow = 'index.php';
        }
    }
    unset($self_matches);

// Simple browser detection.
    $is_lynx = false;
    $is_gecko = false;
    $is_winIE = false;
    $is_macIE = false;
    $is_opera = false;
    $is_NS4 = false;
    $is_safari = false;
    $is_chrome = false;
    $is_iphone = false;
    $is_edge = false;

    if(isset($_SERVER['HTTP_USER_AGENT']))
    {
        if(str_contains($_SERVER['HTTP_USER_AGENT'], 'Lynx'))
        {
            $is_lynx = true;
        }
        elseif(str_contains($_SERVER['HTTP_USER_AGENT'], 'Edg'))
        {
            $is_edge = true;
        }
        elseif(str_contains($_SERVER['HTTP_USER_AGENT'], 'Opera') || str_contains($_SERVER['HTTP_USER_AGENT'], 'OPR/'))
        {
            $is_opera = true;
        }
        elseif(stripos($_SERVER['HTTP_USER_AGENT'], 'chrome') !== false)
        {
            if(stripos($_SERVER['HTTP_USER_AGENT'], 'chromeframe') !== false)
            {
                $is_admin = is_admin();

                $is_chrome = apply_filters('use_google_chrome_frame', $is_admin);
                if($is_chrome)
                {
                    header('X-UA-Compatible: chrome=1');
                }
                $is_winIE = ! $is_chrome;
            }
            else
            {
                $is_chrome = true;
            }
        }
        elseif(stripos($_SERVER['HTTP_USER_AGENT'], 'safari') !== false)
        {
            $is_safari = true;
        }
        elseif((str_contains($_SERVER['HTTP_USER_AGENT'], 'MSIE') || str_contains($_SERVER['HTTP_USER_AGENT'], 'Trident')) && str_contains($_SERVER['HTTP_USER_AGENT'], 'Win'))
        {
            $is_winIE = true;
        }
        elseif(str_contains($_SERVER['HTTP_USER_AGENT'], 'MSIE') && str_contains($_SERVER['HTTP_USER_AGENT'], 'Mac'))
        {
            $is_macIE = true;
        }
        elseif(str_contains($_SERVER['HTTP_USER_AGENT'], 'Gecko'))
        {
            $is_gecko = true;
        }
        elseif(str_contains($_SERVER['HTTP_USER_AGENT'], 'Nav') && str_contains($_SERVER['HTTP_USER_AGENT'], 'Mozilla/4.'))
        {
            $is_NS4 = true;
        }
    }

    if($is_safari && stripos($_SERVER['HTTP_USER_AGENT'], 'mobile') !== false)
    {
        $is_iphone = true;
    }

    $is_IE = ($is_macIE || $is_winIE);

// Server detection.

    $is_apache = (str_contains($_SERVER['SERVER_SOFTWARE'], 'Apache') || str_contains($_SERVER['SERVER_SOFTWARE'], 'LiteSpeed'));

    $is_nginx = (str_contains($_SERVER['SERVER_SOFTWARE'], 'nginx'));

    $is_IIS = ! $is_apache && (str_contains($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') || str_contains($_SERVER['SERVER_SOFTWARE'], 'ExpressionDevServer'));

    $is_iis7 = $is_IIS && (int) substr($_SERVER['SERVER_SOFTWARE'], strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS/') + 14) >= 7;

    function wp_is_mobile()
    {
        if(empty($_SERVER['HTTP_USER_AGENT']))
        {
            $is_mobile = false;
        }
        elseif(
            str_contains($_SERVER['HTTP_USER_AGENT'], 'Mobile') // Many mobile devices (all iPhone, iPad, etc.)
            || str_contains($_SERVER['HTTP_USER_AGENT'], 'Android') || str_contains($_SERVER['HTTP_USER_AGENT'], 'Silk/') || str_contains($_SERVER['HTTP_USER_AGENT'], 'Kindle') || str_contains($_SERVER['HTTP_USER_AGENT'], 'BlackBerry') || str_contains($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') || str_contains($_SERVER['HTTP_USER_AGENT'], 'Opera Mobi')
        )
        {
            $is_mobile = true;
        }
        else
        {
            $is_mobile = false;
        }

        return apply_filters('wp_is_mobile', $is_mobile);
    }
