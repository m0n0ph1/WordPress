<?php

    function get_header($name = null, $args = [])
    {
        do_action('get_header', $name, $args);

        $templates = [];
        $name = (string) $name;
        if('' !== $name)
        {
            $templates[] = "header-{$name}.php";
        }

        $templates[] = 'header.php';

        if(! locate_template($templates, true, true, $args))
        {
            return false;
        }
    }

    function get_footer($name = null, $args = [])
    {
        do_action('get_footer', $name, $args);

        $templates = [];
        $name = (string) $name;
        if('' !== $name)
        {
            $templates[] = "footer-{$name}.php";
        }

        $templates[] = 'footer.php';

        if(! locate_template($templates, true, true, $args))
        {
            return false;
        }
    }

    function get_sidebar($name = null, $args = [])
    {
        do_action('get_sidebar', $name, $args);

        $templates = [];
        $name = (string) $name;
        if('' !== $name)
        {
            $templates[] = "sidebar-{$name}.php";
        }

        $templates[] = 'sidebar.php';

        if(! locate_template($templates, true, true, $args))
        {
            return false;
        }
    }

    function get_template_part($slug, $name = null, $args = [])
    {
        do_action("get_template_part_{$slug}", $slug, $name, $args);

        $templates = [];
        $name = (string) $name;
        if('' !== $name)
        {
            $templates[] = "{$slug}-{$name}.php";
        }

        $templates[] = "{$slug}.php";

        do_action('get_template_part', $slug, $name, $templates, $args);

        if(! locate_template($templates, true, false, $args))
        {
            return false;
        }
    }

    function get_search_form($args = [])
    {
        do_action('pre_get_search_form', $args);

        $echo = true;

        if(! is_array($args))
        {
            /*
		 * Back compat: to ensure previous uses of get_search_form() continue to
		 * function as expected, we handle a value for the boolean $echo param removed
		 * in 5.2.0. Then we deal with the $args array and cast its defaults.
		 */
            $echo = (bool) $args;

            // Set an empty array and allow default arguments to take over.
            $args = [];
        }

        // Defaults are to echo and to output no custom label on the form.
        $defaults = [
            'echo' => $echo,
            'aria_label' => '',
        ];

        $args = wp_parse_args($args, $defaults);

        $args = apply_filters('search_form_args', $args);

        // Ensure that the filtered arguments contain all required default values.
        $args = array_merge($defaults, $args);

        $format = current_theme_supports('html5', 'search-form') ? 'html5' : 'xhtml';

        $format = apply_filters('search_form_format', $format, $args);

        $search_form_template = locate_template('searchform.php');

        if('' !== $search_form_template)
        {
            ob_start();
            require $search_form_template;
            $form = ob_get_clean();
        }
        else
        {
            // Build a string containing an aria-label to use for the search form.
            if($args['aria_label'])
            {
                $aria_label = 'aria-label="'.esc_attr($args['aria_label']).'" ';
            }
            else
            {
                /*
			 * If there's no custom aria-label, we can set a default here. At the
			 * moment it's empty as there's uncertainty about what the default should be.
			 */
                $aria_label = '';
            }

            if('html5' === $format)
            {
                $form = '<form role="search" '.$aria_label.'method="get" class="search-form" action="'.esc_url(home_url('/')).'">
				<label>
					<span class="screen-reader-text">'./* translators: Hidden accessibility text. */
                    _x('Search for:', 'label').'</span>
					<input type="search" class="search-field" placeholder="'.esc_attr_x('Search &hellip;', 'placeholder').'" value="'.get_search_query().'" name="s" />
				</label>
				<input type="submit" class="search-submit" value="'.esc_attr_x('Search', 'submit button').'" />
			</form>';
            }
            else
            {
                $form = '<form role="search" '.$aria_label.'method="get" id="searchform" class="searchform" action="'.esc_url(home_url('/')).'">
				<div>
					<label class="screen-reader-text" for="s">'./* translators: Hidden accessibility text. */
                    _x('Search for:', 'label').'</label>
					<input type="text" value="'.get_search_query().'" name="s" id="s" />
					<input type="submit" id="searchsubmit" value="'.esc_attr_x('Search', 'submit button').'" />
				</div>
			</form>';
            }
        }

        $result = apply_filters('get_search_form', $form, $args);

        if(null === $result)
        {
            $result = $form;
        }

        if($args['echo'])
        {
            echo $result;
        }
        else
        {
            return $result;
        }
    }

    function wp_loginout($redirect = '', $display = true)
    {
        if(! is_user_logged_in())
        {
            $link = '<a href="'.esc_url(wp_login_url($redirect)).'">'.__('Log in').'</a>';
        }
        else
        {
            $link = '<a href="'.esc_url(wp_logout_url($redirect)).'">'.__('Log out').'</a>';
        }

        if($display)
        {
            echo apply_filters('loginout', $link);
        }
        else
        {
            return apply_filters('loginout', $link);
        }
    }

    function wp_logout_url($redirect = '')
    {
        $args = [];
        if(! empty($redirect))
        {
            $args['redirect_to'] = urlencode($redirect);
        }

        $logout_url = add_query_arg($args, site_url('wp-login.php?action=logout', 'login'));
        $logout_url = wp_nonce_url($logout_url, 'log-out');

        return apply_filters('logout_url', $logout_url, $redirect);
    }

    function wp_login_url($redirect = '', $force_reauth = false)
    {
        $login_url = site_url('wp-login.php', 'login');

        if(! empty($redirect))
        {
            $login_url = add_query_arg('redirect_to', urlencode($redirect), $login_url);
        }

        if($force_reauth)
        {
            $login_url = add_query_arg('reauth', '1', $login_url);
        }

        return apply_filters('login_url', $login_url, $redirect, $force_reauth);
    }

    function wp_registration_url()
    {
        return apply_filters('register_url', site_url('wp-login.php?action=register', 'login'));
    }

    function wp_login_form($args = [])
    {
        $defaults = [
            'echo' => true,
            // Default 'redirect' value takes the user back to the request URI.
            'redirect' => (is_ssl() ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],
            'form_id' => 'loginform',
            'label_username' => __('Username or Email Address'),
            'label_password' => __('Password'),
            'label_remember' => __('Remember Me'),
            'label_log_in' => __('Log In'),
            'id_username' => 'user_login',
            'id_password' => 'user_pass',
            'id_remember' => 'rememberme',
            'id_submit' => 'wp-submit',
            'remember' => true,
            'value_username' => '',
            // Set 'value_remember' to true to default the "Remember me" checkbox to checked.
            'value_remember' => false,
        ];

        $args = wp_parse_args($args, apply_filters('login_form_defaults', $defaults));

        $login_form_top = apply_filters('login_form_top', '', $args);

        $login_form_middle = apply_filters('login_form_middle', '', $args);

        $login_form_bottom = apply_filters('login_form_bottom', '', $args);

        $form = sprintf('<form name="%1$s" id="%1$s" action="%2$s" method="post">', esc_attr($args['form_id']), esc_url(site_url('wp-login.php', 'login_post'))).$login_form_top.sprintf(
                '<p class="login-username">
				<label for="%1$s">%2$s</label>
				<input type="text" name="log" id="%1$s" autocomplete="username" class="input" value="%3$s" size="20" />
			</p>', esc_attr($args['id_username']), esc_html($args['label_username']), esc_attr($args['value_username'])
            ).sprintf(
                '<p class="login-password">
				<label for="%1$s">%2$s</label>
				<input type="password" name="pwd" id="%1$s" autocomplete="current-password" spellcheck="false" class="input" value="" size="20" />
			</p>', esc_attr($args['id_password']), esc_html($args['label_password'])
            ).$login_form_middle.($args['remember'] ? sprintf('<p class="login-remember"><label><input name="rememberme" type="checkbox" id="%1$s" value="forever"%2$s /> %3$s</label></p>', esc_attr($args['id_remember']), ($args['value_remember'] ? ' checked="checked"' : ''), esc_html($args['label_remember'])) : '').sprintf(
                '<p class="login-submit">
				<input type="submit" name="wp-submit" id="%1$s" class="button button-primary" value="%2$s" />
				<input type="hidden" name="redirect_to" value="%3$s" />
			</p>', esc_attr($args['id_submit']), esc_attr($args['label_log_in']), esc_url($args['redirect'])
            ).$login_form_bottom.'</form>';

        if($args['echo'])
        {
            echo $form;
        }
        else
        {
            return $form;
        }
    }

    function wp_lostpassword_url($redirect = '')
    {
        $args = [
            'action' => 'lostpassword',
        ];

        if(! empty($redirect))
        {
            $args['redirect_to'] = urlencode($redirect);
        }

        if(is_multisite())
        {
            $blog_details = get_site();
            $wp_login_path = $blog_details->path.'wp-login.php';
        }
        else
        {
            $wp_login_path = 'wp-login.php';
        }

        $lostpassword_url = add_query_arg($args, network_site_url($wp_login_path, 'login'));

        return apply_filters('lostpassword_url', $lostpassword_url, $redirect);
    }

    function wp_register($before = '<li>', $after = '</li>', $display = true)
    {
        if(! is_user_logged_in())
        {
            if(get_option('users_can_register'))
            {
                $link = $before.'<a href="'.esc_url(wp_registration_url()).'">'.__('Register').'</a>'.$after;
            }
            else
            {
                $link = '';
            }
        }
        elseif(current_user_can('read'))
        {
            $link = $before.'<a href="'.admin_url().'">'.__('Site Admin').'</a>'.$after;
        }
        else
        {
            $link = '';
        }

        $link = apply_filters('register', $link);

        if($display)
        {
            echo $link;
        }
        else
        {
            return $link;
        }
    }

    function wp_meta()
    {
        do_action('wp_meta');
    }

    function bloginfo($show = '')
    {
        echo get_bloginfo($show, 'display');
    }

    function get_bloginfo($show = '', $filter = 'raw')
    {
        switch($show)
        {
            case 'home':    // Deprecated.
            case 'siteurl': // Deprecated.
                _deprecated_argument(__FUNCTION__, '2.2.0', sprintf(/* translators: 1: 'siteurl'/'home' argument, 2: bloginfo() function name, 3: 'url' argument. */ __('The %1$s option is deprecated for the family of %2$s functions. Use the %3$s option instead.'), '<code>'.$show.'</code>', '<code>bloginfo()</code>', '<code>url</code>'));
            // Intentional fall-through to be handled by the 'url' case.
            case 'url':
                $output = home_url();
                break;
            case 'wpurl':
                $output = site_url();
                break;
            case 'description':
                $output = get_option('blogdescription');
                break;
            case 'rdf_url':
                $output = get_feed_link('rdf');
                break;
            case 'rss_url':
                $output = get_feed_link('rss');
                break;
            case 'rss2_url':
                $output = get_feed_link('rss2');
                break;
            case 'atom_url':
                $output = get_feed_link('atom');
                break;
            case 'comments_atom_url':
                $output = get_feed_link('comments_atom');
                break;
            case 'comments_rss2_url':
                $output = get_feed_link('comments_rss2');
                break;
            case 'pingback_url':
                $output = site_url('xmlrpc.php');
                break;
            case 'stylesheet_url':
                $output = get_stylesheet_uri();
                break;
            case 'stylesheet_directory':
                $output = get_stylesheet_directory_uri();
                break;
            case 'template_directory':
            case 'template_url':
                $output = get_template_directory_uri();
                break;
            case 'admin_email':
                $output = get_option('admin_email');
                break;
            case 'charset':
                $output = get_option('blog_charset');
                if('' === $output)
                {
                    $output = 'UTF-8';
                }
                break;
            case 'html_type':
                $output = get_option('html_type');
                break;
            case 'version':
                global $wp_version;
                $output = $wp_version;
                break;
            case 'language':
                /*
			 * translators: Translate this to the correct language tag for your locale,
			 * see https://www.w3.org/International/articles/language-tags/ for reference.
			 * Do not translate into your own language.
			 */ $output = __('html_lang_attribute');
                if('html_lang_attribute' === $output || preg_match('/[^a-zA-Z0-9-]/', $output))
                {
                    $output = determine_locale();
                    $output = str_replace('_', '-', $output);
                }
                break;
            case 'text_direction':
                _deprecated_argument(__FUNCTION__, '2.2.0', sprintf(/* translators: 1: 'text_direction' argument, 2: bloginfo() function name, 3: is_rtl() function name. */ __('The %1$s option is deprecated for the family of %2$s functions. Use the %3$s function instead.'), '<code>'.$show.'</code>', '<code>bloginfo()</code>', '<code>is_rtl()</code>'));
                if(function_exists('is_rtl'))
                {
                    $output = is_rtl() ? 'rtl' : 'ltr';
                }
                else
                {
                    $output = 'ltr';
                }
                break;
            case 'name':
            default:
                $output = get_option('blogname');
                break;
        }

        $url = true;

        if(! str_contains($show, 'url') && ! str_contains($show, 'directory') && ! str_contains($show, 'home'))
        {
            $url = false;
        }

        if('display' === $filter)
        {
            if($url)
            {
                $output = apply_filters('bloginfo_url', $output, $show);
            }
            else
            {
                $output = apply_filters('bloginfo', $output, $show);
            }
        }

        return $output;
    }

    function get_site_icon_url($size = 512, $url = '', $blog_id = 0)
    {
        $switched_blog = false;

        if(is_multisite() && ! empty($blog_id) && get_current_blog_id() !== (int) $blog_id)
        {
            switch_to_blog($blog_id);
            $switched_blog = true;
        }

        $site_icon_id = (int) get_option('site_icon');

        if($site_icon_id)
        {
            if($size >= 512)
            {
                $size_data = 'full';
            }
            else
            {
                $size_data = [$size, $size];
            }
            $url = wp_get_attachment_image_url($site_icon_id, $size_data);
        }

        if($switched_blog)
        {
            restore_current_blog();
        }

        return apply_filters('get_site_icon_url', $url, $size, $blog_id);
    }

    function site_icon_url($size = 512, $url = '', $blog_id = 0)
    {
        echo esc_url(get_site_icon_url($size, $url, $blog_id));
    }

    function has_site_icon($blog_id = 0)
    {
        return (bool) get_site_icon_url(512, '', $blog_id);
    }

    function has_custom_logo($blog_id = 0)
    {
        $switched_blog = false;

        if(is_multisite() && ! empty($blog_id) && get_current_blog_id() !== (int) $blog_id)
        {
            switch_to_blog($blog_id);
            $switched_blog = true;
        }

        $custom_logo_id = get_theme_mod('custom_logo');

        if($switched_blog)
        {
            restore_current_blog();
        }

        return (bool) $custom_logo_id;
    }

    function get_custom_logo($blog_id = 0)
    {
        $html = '';
        $switched_blog = false;

        if(is_multisite() && ! empty($blog_id) && get_current_blog_id() !== (int) $blog_id)
        {
            switch_to_blog($blog_id);
            $switched_blog = true;
        }

        $custom_logo_id = get_theme_mod('custom_logo');

        // We have a logo. Logo is go.
        if($custom_logo_id)
        {
            $custom_logo_attr = [
                'class' => 'custom-logo',
                'loading' => false,
            ];

            $unlink_homepage_logo = (bool) get_theme_support('custom-logo', 'unlink-homepage-logo');

            if($unlink_homepage_logo && is_front_page() && ! is_paged())
            {
                /*
			 * If on the home page, set the logo alt attribute to an empty string,
			 * as the image is decorative and doesn't need its purpose to be described.
			 */
                $custom_logo_attr['alt'] = '';
            }
            else
            {
                /*
			 * If the logo alt attribute is empty, get the site title and explicitly pass it
			 * to the attributes used by wp_get_attachment_image().
			 */
                $image_alt = get_post_meta($custom_logo_id, '_wp_attachment_image_alt', true);
                if(empty($image_alt))
                {
                    $custom_logo_attr['alt'] = get_bloginfo('name', 'display');
                }
            }

            $custom_logo_attr = apply_filters('get_custom_logo_image_attributes', $custom_logo_attr, $custom_logo_id, $blog_id);

            /*
		 * If the alt attribute is not empty, there's no need to explicitly pass it
		 * because wp_get_attachment_image() already adds the alt attribute.
		 */
            $image = wp_get_attachment_image($custom_logo_id, 'full', false, $custom_logo_attr);

            if($unlink_homepage_logo && is_front_page() && ! is_paged())
            {
                // If on the home page, don't link the logo to home.
                $html = sprintf('<span class="custom-logo-link">%1$s</span>', $image);
            }
            else
            {
                $aria_current = is_front_page() && ! is_paged() ? ' aria-current="page"' : '';

                $html = sprintf('<a href="%1$s" class="custom-logo-link" rel="home"%2$s>%3$s</a>', esc_url(home_url('/')), $aria_current, $image);
            }
        }
        elseif(is_customize_preview())
        {
            // If no logo is set but we're in the Customizer, leave a placeholder (needed for the live preview).
            $html = sprintf('<a href="%1$s" class="custom-logo-link" style="display:none;"><img class="custom-logo" alt="" /></a>', esc_url(home_url('/')));
        }

        if($switched_blog)
        {
            restore_current_blog();
        }

        return apply_filters('get_custom_logo', $html, $blog_id);
    }

    function the_custom_logo($blog_id = 0)
    {
        echo get_custom_logo($blog_id);
    }

    function wp_get_document_title()
    {
        $title = apply_filters('pre_get_document_title', '');
        if(! empty($title))
        {
            return $title;
        }

        global $page, $paged;

        $title = [
            'title' => '',
        ];

        // If it's a 404 page, use a "Page not found" title.
        if(is_404())
        {
            $title['title'] = __('Page not found');
            // If it's a search, use a dynamic search results title.
        }
        elseif(is_search())
        {
            /* translators: %s: Search query. */
            $title['title'] = sprintf(__('Search Results for &#8220;%s&#8221;'), get_search_query());
            // If on the front page, use the site title.
        }
        elseif(is_front_page())
        {
            $title['title'] = get_bloginfo('name', 'display');
            // If on a post type archive, use the post type archive title.
        }
        elseif(is_post_type_archive())
        {
            $title['title'] = post_type_archive_title('', false);
            // If on a taxonomy archive, use the term title.
        }
        elseif(is_tax())
        {
            $title['title'] = single_term_title('', false);
            /*
		* If we're on the blog page that is not the homepage
		* or a single post of any post type, use the post title.
		*/
        }
        elseif(is_home() || is_singular())
        {
            $title['title'] = single_post_title('', false);
            // If on a category or tag archive, use the term title.
        }
        elseif(is_category() || is_tag())
        {
            $title['title'] = single_term_title('', false);
            // If on an author archive, use the author's display name.
        }
        elseif(is_author() && get_queried_object())
        {
            $author = get_queried_object();
            $title['title'] = $author->display_name;
            // If it's a date archive, use the date as the title.
        }
        elseif(is_year())
        {
            $title['title'] = get_the_date(_x('Y', 'yearly archives date format'));
        }
        elseif(is_month())
        {
            $title['title'] = get_the_date(_x('F Y', 'monthly archives date format'));
        }
        elseif(is_day())
        {
            $title['title'] = get_the_date();
        }

        // Add a page number if necessary.
        if(($paged >= 2 || $page >= 2) && ! is_404())
        {
            /* translators: %s: Page number. */
            $title['page'] = sprintf(__('Page %s'), max($paged, $page));
        }

        // Append the description or site title to give context.
        if(is_front_page())
        {
            $title['tagline'] = get_bloginfo('description', 'display');
        }
        else
        {
            $title['site'] = get_bloginfo('name', 'display');
        }

        $sep = apply_filters('document_title_separator', '-');

        $title = apply_filters('document_title_parts', $title);

        $title = implode(" $sep ", array_filter($title));

        $title = apply_filters('document_title', $title);

        return $title;
    }

    function _wp_render_title_tag()
    {
        if(! current_theme_supports('title-tag'))
        {
            return;
        }

        echo '<title>'.wp_get_document_title().'</title>'."\n";
    }

    function wp_title($sep = '&raquo;', $display = true, $seplocation = '')
    {
        global $wp_locale;

        $m = get_query_var('m');
        $year = get_query_var('year');
        $monthnum = get_query_var('monthnum');
        $day = get_query_var('day');
        $search = get_query_var('s');
        $title = '';

        $t_sep = '%WP_TITLE_SEP%'; // Temporary separator, for accurate flipping, if necessary.

        // If there is a post.
        if(is_single() || (is_home() && ! is_front_page()) || (is_page() && ! is_front_page()))
        {
            $title = single_post_title('', false);
        }

        // If there's a post type archive.
        if(is_post_type_archive())
        {
            $post_type = get_query_var('post_type');
            if(is_array($post_type))
            {
                $post_type = reset($post_type);
            }
            $post_type_object = get_post_type_object($post_type);
            if(! $post_type_object->has_archive)
            {
                $title = post_type_archive_title('', false);
            }
        }

        // If there's a category or tag.
        if(is_category() || is_tag())
        {
            $title = single_term_title('', false);
        }

        // If there's a taxonomy.
        if(is_tax())
        {
            $term = get_queried_object();
            if($term)
            {
                $tax = get_taxonomy($term->taxonomy);
                $title = single_term_title($tax->labels->name.$t_sep, false);
            }
        }

        // If there's an author.
        if(is_author() && ! is_post_type_archive())
        {
            $author = get_queried_object();
            if($author)
            {
                $title = $author->display_name;
            }
        }

        // Post type archives with has_archive should override terms.
        if(is_post_type_archive() && $post_type_object->has_archive)
        {
            $title = post_type_archive_title('', false);
        }

        // If there's a month.
        if(is_archive() && ! empty($m))
        {
            $my_year = substr($m, 0, 4);
            $my_month = substr($m, 4, 2);
            $my_day = (int) substr($m, 6, 2);
            $title = $my_year.($my_month ? $t_sep.$wp_locale->get_month($my_month) : '').($my_day ? $t_sep.$my_day : '');
        }

        // If there's a year.
        if(is_archive() && ! empty($year))
        {
            $title = $year;
            if(! empty($monthnum))
            {
                $title .= $t_sep.$wp_locale->get_month($monthnum);
            }
            if(! empty($day))
            {
                $title .= $t_sep.zeroise($day, 2);
            }
        }

        // If it's a search.
        if(is_search())
        {
            /* translators: 1: Separator, 2: Search query. */
            $title = sprintf(__('Search Results %1$s %2$s'), $t_sep, strip_tags($search));
        }

        // If it's a 404 page.
        if(is_404())
        {
            $title = __('Page not found');
        }

        $prefix = '';
        if(! empty($title))
        {
            $prefix = " $sep ";
        }

        $title_array = apply_filters('wp_title_parts', explode($t_sep, $title));

        // Determines position of the separator and direction of the breadcrumb.
        if('right' === $seplocation)
        { // Separator on right, so reverse the order.
            $title_array = array_reverse($title_array);
            $title = implode(" $sep ", $title_array).$prefix;
        }
        else
        {
            $title = $prefix.implode(" $sep ", $title_array);
        }

        $title = apply_filters('wp_title', $title, $sep, $seplocation);

        // Send it out.
        if($display)
        {
            echo $title;
        }
        else
        {
            return $title;
        }
    }

    function single_post_title($prefix = '', $display = true)
    {
        $_post = get_queried_object();

        if(! isset($_post->post_title))
        {
            return;
        }

        $title = apply_filters('single_post_title', $_post->post_title, $_post);
        if($display)
        {
            echo $prefix.$title;
        }
        else
        {
            return $prefix.$title;
        }
    }

    function post_type_archive_title($prefix = '', $display = true)
    {
        if(! is_post_type_archive())
        {
            return;
        }

        $post_type = get_query_var('post_type');
        if(is_array($post_type))
        {
            $post_type = reset($post_type);
        }

        $post_type_obj = get_post_type_object($post_type);

        $title = apply_filters('post_type_archive_title', $post_type_obj->labels->name, $post_type);

        if($display)
        {
            echo $prefix.$title;
        }
        else
        {
            return $prefix.$title;
        }
    }

    function single_cat_title($prefix = '', $display = true)
    {
        return single_term_title($prefix, $display);
    }

    function single_tag_title($prefix = '', $display = true)
    {
        return single_term_title($prefix, $display);
    }

    function single_term_title($prefix = '', $display = true)
    {
        $term = get_queried_object();

        if(! $term)
        {
            return;
        }

        if(is_category())
        {
            $term_name = apply_filters('single_cat_title', $term->name);
        }
        elseif(is_tag())
        {
            $term_name = apply_filters('single_tag_title', $term->name);
        }
        elseif(is_tax())
        {
            $term_name = apply_filters('single_term_title', $term->name);
        }
        else
        {
            return;
        }

        if(empty($term_name))
        {
            return;
        }

        if($display)
        {
            echo $prefix.$term_name;
        }
        else
        {
            return $prefix.$term_name;
        }
    }

    function single_month_title($prefix = '', $display = true)
    {
        global $wp_locale;

        $m = get_query_var('m');
        $year = get_query_var('year');
        $monthnum = get_query_var('monthnum');

        if(! empty($monthnum) && ! empty($year))
        {
            $my_year = $year;
            $my_month = $wp_locale->get_month($monthnum);
        }
        elseif(! empty($m))
        {
            $my_year = substr($m, 0, 4);
            $my_month = $wp_locale->get_month(substr($m, 4, 2));
        }

        if(empty($my_month))
        {
            return false;
        }

        $result = $prefix.$my_month.$prefix.$my_year;

        if(! $display)
        {
            return $result;
        }
        echo $result;
    }

    function the_archive_title($before = '', $after = '')
    {
        $title = get_the_archive_title();

        if(! empty($title))
        {
            echo $before.$title.$after;
        }
    }

    function get_the_archive_title()
    {
        $title = __('Archives');
        $prefix = '';

        if(is_category())
        {
            $title = single_cat_title('', false);
            $prefix = _x('Category:', 'category archive title prefix');
        }
        elseif(is_tag())
        {
            $title = single_tag_title('', false);
            $prefix = _x('Tag:', 'tag archive title prefix');
        }
        elseif(is_author())
        {
            $title = get_the_author();
            $prefix = _x('Author:', 'author archive title prefix');
        }
        elseif(is_year())
        {
            $title = get_the_date(_x('Y', 'yearly archives date format'));
            $prefix = _x('Year:', 'date archive title prefix');
        }
        elseif(is_month())
        {
            $title = get_the_date(_x('F Y', 'monthly archives date format'));
            $prefix = _x('Month:', 'date archive title prefix');
        }
        elseif(is_day())
        {
            $title = get_the_date(_x('F j, Y', 'daily archives date format'));
            $prefix = _x('Day:', 'date archive title prefix');
        }
        elseif(is_tax('post_format'))
        {
            if(is_tax('post_format', 'post-format-aside'))
            {
                $title = _x('Asides', 'post format archive title');
            }
            elseif(is_tax('post_format', 'post-format-gallery'))
            {
                $title = _x('Galleries', 'post format archive title');
            }
            elseif(is_tax('post_format', 'post-format-image'))
            {
                $title = _x('Images', 'post format archive title');
            }
            elseif(is_tax('post_format', 'post-format-video'))
            {
                $title = _x('Videos', 'post format archive title');
            }
            elseif(is_tax('post_format', 'post-format-quote'))
            {
                $title = _x('Quotes', 'post format archive title');
            }
            elseif(is_tax('post_format', 'post-format-link'))
            {
                $title = _x('Links', 'post format archive title');
            }
            elseif(is_tax('post_format', 'post-format-status'))
            {
                $title = _x('Statuses', 'post format archive title');
            }
            elseif(is_tax('post_format', 'post-format-audio'))
            {
                $title = _x('Audio', 'post format archive title');
            }
            elseif(is_tax('post_format', 'post-format-chat'))
            {
                $title = _x('Chats', 'post format archive title');
            }
        }
        elseif(is_post_type_archive())
        {
            $title = post_type_archive_title('', false);
            $prefix = _x('Archives:', 'post type archive title prefix');
        }
        elseif(is_tax())
        {
            $queried_object = get_queried_object();
            if($queried_object)
            {
                $tax = get_taxonomy($queried_object->taxonomy);
                $title = single_term_title('', false);
                $prefix = sprintf(/* translators: %s: Taxonomy singular name. */ _x('%s:', 'taxonomy term archive title prefix'), $tax->labels->singular_name);
            }
        }

        $original_title = $title;

        $prefix = apply_filters('get_the_archive_title_prefix', $prefix);
        if($prefix)
        {
            $title = sprintf(/* translators: 1: Title prefix. 2: Title. */ _x('%1$s %2$s', 'archive title'), $prefix, '<span>'.$title.'</span>');
        }

        return apply_filters('get_the_archive_title', $title, $original_title, $prefix);
    }

    function the_archive_description($before = '', $after = '')
    {
        $description = get_the_archive_description();
        if($description)
        {
            echo $before.$description.$after;
        }
    }

    function get_the_archive_description()
    {
        if(is_author())
        {
            $description = get_the_author_meta('description');
        }
        elseif(is_post_type_archive())
        {
            $description = get_the_post_type_description();
        }
        else
        {
            $description = term_description();
        }

        return apply_filters('get_the_archive_description', $description);
    }

    function get_the_post_type_description()
    {
        $post_type = get_query_var('post_type');

        if(is_array($post_type))
        {
            $post_type = reset($post_type);
        }

        $post_type_obj = get_post_type_object($post_type);

        // Check if a description is set.
        if(isset($post_type_obj->description))
        {
            $description = $post_type_obj->description;
        }
        else
        {
            $description = '';
        }

        return apply_filters('get_the_post_type_description', $description, $post_type_obj);
    }

    function get_archives_link($url, $text, $format = 'html', $before = '', $after = '', $selected = false)
    {
        $text = wptexturize($text);
        $url = esc_url($url);
        $aria_current = $selected ? ' aria-current="page"' : '';

        if('link' === $format)
        {
            $link_html = "\t<link rel='archives' title='".esc_attr($text)."' href='$url' />\n";
        }
        elseif('option' === $format)
        {
            $selected_attr = $selected ? " selected='selected'" : '';
            $link_html = "\t<option value='$url'$selected_attr>$before $text $after</option>\n";
        }
        elseif('html' === $format)
        {
            $link_html = "\t<li>$before<a href='$url'$aria_current>$text</a>$after</li>\n";
        }
        else
        { // Custom.
            $link_html = "\t$before<a href='$url'$aria_current>$text</a>$after\n";
        }

        return apply_filters('get_archives_link', $link_html, $url, $text, $format, $before, $after, $selected);
    }

    function wp_get_archives($args = '')
    {
        global $wpdb, $wp_locale;

        $defaults = [
            'type' => 'monthly',
            'limit' => '',
            'format' => 'html',
            'before' => '',
            'after' => '',
            'show_post_count' => false,
            'echo' => 1,
            'order' => 'DESC',
            'post_type' => 'post',
            'year' => get_query_var('year'),
            'monthnum' => get_query_var('monthnum'),
            'day' => get_query_var('day'),
            'w' => get_query_var('w'),
        ];

        $parsed_args = wp_parse_args($args, $defaults);

        $post_type_object = get_post_type_object($parsed_args['post_type']);
        if(! is_post_type_viewable($post_type_object))
        {
            return;
        }

        $parsed_args['post_type'] = $post_type_object->name;

        if('' === $parsed_args['type'])
        {
            $parsed_args['type'] = 'monthly';
        }

        if(! empty($parsed_args['limit']))
        {
            $parsed_args['limit'] = absint($parsed_args['limit']);
            $parsed_args['limit'] = ' LIMIT '.$parsed_args['limit'];
        }

        $order = strtoupper($parsed_args['order']);
        if('ASC' !== $order)
        {
            $order = 'DESC';
        }

        // This is what will separate dates on weekly archive links.
        $archive_week_separator = '&#8211;';

        $sql_where = $wpdb->prepare("WHERE post_type = %s AND post_status = 'publish'", $parsed_args['post_type']);

        $where = apply_filters('getarchives_where', $sql_where, $parsed_args);

        $join = apply_filters('getarchives_join', '', $parsed_args);

        $output = '';

        $last_changed = wp_cache_get_last_changed('posts');

        $limit = $parsed_args['limit'];

        if('monthly' === $parsed_args['type'])
        {
            $query = "SELECT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date), MONTH(post_date) ORDER BY post_date $order $limit";
            $key = md5($query);
            $key = "wp_get_archives:$key:$last_changed";
            $results = wp_cache_get($key, 'post-queries');
            if(! $results)
            {
                $results = $wpdb->get_results($query);
                wp_cache_set($key, $results, 'post-queries');
            }
            if($results)
            {
                $after = $parsed_args['after'];
                foreach((array) $results as $result)
                {
                    $url = get_month_link($result->year, $result->month);
                    if('post' !== $parsed_args['post_type'])
                    {
                        $url = add_query_arg('post_type', $parsed_args['post_type'], $url);
                    }
                    /* translators: 1: Month name, 2: 4-digit year. */
                    $text = sprintf(__('%1$s %2$d'), $wp_locale->get_month($result->month), $result->year);
                    if($parsed_args['show_post_count'])
                    {
                        $parsed_args['after'] = '&nbsp;('.$result->posts.')'.$after;
                    }
                    $selected = is_archive() && (string) $parsed_args['year'] === $result->year && (string) $parsed_args['monthnum'] === $result->month;
                    $output .= get_archives_link($url, $text, $parsed_args['format'], $parsed_args['before'], $parsed_args['after'], $selected);
                }
            }
        }
        elseif('yearly' === $parsed_args['type'])
        {
            $query = "SELECT YEAR(post_date) AS `year`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date) ORDER BY post_date $order $limit";
            $key = md5($query);
            $key = "wp_get_archives:$key:$last_changed";
            $results = wp_cache_get($key, 'post-queries');
            if(! $results)
            {
                $results = $wpdb->get_results($query);
                wp_cache_set($key, $results, 'post-queries');
            }
            if($results)
            {
                $after = $parsed_args['after'];
                foreach((array) $results as $result)
                {
                    $url = get_year_link($result->year);
                    if('post' !== $parsed_args['post_type'])
                    {
                        $url = add_query_arg('post_type', $parsed_args['post_type'], $url);
                    }
                    $text = sprintf('%d', $result->year);
                    if($parsed_args['show_post_count'])
                    {
                        $parsed_args['after'] = '&nbsp;('.$result->posts.')'.$after;
                    }
                    $selected = is_archive() && (string) $parsed_args['year'] === $result->year;
                    $output .= get_archives_link($url, $text, $parsed_args['format'], $parsed_args['before'], $parsed_args['after'], $selected);
                }
            }
        }
        elseif('daily' === $parsed_args['type'])
        {
            $query = "SELECT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, DAYOFMONTH(post_date) AS `dayofmonth`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date), MONTH(post_date), DAYOFMONTH(post_date) ORDER BY post_date $order $limit";
            $key = md5($query);
            $key = "wp_get_archives:$key:$last_changed";
            $results = wp_cache_get($key, 'post-queries');
            if(! $results)
            {
                $results = $wpdb->get_results($query);
                wp_cache_set($key, $results, 'post-queries');
            }
            if($results)
            {
                $after = $parsed_args['after'];
                foreach((array) $results as $result)
                {
                    $url = get_day_link($result->year, $result->month, $result->dayofmonth);
                    if('post' !== $parsed_args['post_type'])
                    {
                        $url = add_query_arg('post_type', $parsed_args['post_type'], $url);
                    }
                    $date = sprintf('%1$d-%2$02d-%3$02d 00:00:00', $result->year, $result->month, $result->dayofmonth);
                    $text = mysql2date(get_option('date_format'), $date);
                    if($parsed_args['show_post_count'])
                    {
                        $parsed_args['after'] = '&nbsp;('.$result->posts.')'.$after;
                    }
                    $selected = is_archive() && (string) $parsed_args['year'] === $result->year && (string) $parsed_args['monthnum'] === $result->month && (string) $parsed_args['day'] === $result->dayofmonth;
                    $output .= get_archives_link($url, $text, $parsed_args['format'], $parsed_args['before'], $parsed_args['after'], $selected);
                }
            }
        }
        elseif('weekly' === $parsed_args['type'])
        {
            $week = _wp_mysql_week('`post_date`');
            $query = "SELECT DISTINCT $week AS `week`, YEAR( `post_date` ) AS `yr`, DATE_FORMAT( `post_date`, '%Y-%m-%d' ) AS `yyyymmdd`, count( `ID` ) AS `posts` FROM `$wpdb->posts` $join $where GROUP BY $week, YEAR( `post_date` ) ORDER BY `post_date` $order $limit";
            $key = md5($query);
            $key = "wp_get_archives:$key:$last_changed";
            $results = wp_cache_get($key, 'post-queries');
            if(! $results)
            {
                $results = $wpdb->get_results($query);
                wp_cache_set($key, $results, 'post-queries');
            }
            $arc_w_last = '';
            if($results)
            {
                $after = $parsed_args['after'];
                foreach((array) $results as $result)
                {
                    if($result->week != $arc_w_last)
                    {
                        $arc_year = $result->yr;
                        $arc_w_last = $result->week;
                        $arc_week = get_weekstartend($result->yyyymmdd, get_option('start_of_week'));
                        $arc_week_start = date_i18n(get_option('date_format'), $arc_week['start']);
                        $arc_week_end = date_i18n(get_option('date_format'), $arc_week['end']);
                        $url = add_query_arg([
                                                 'm' => $arc_year,
                                                 'w' => $result->week,
                                             ], home_url('/'));
                        if('post' !== $parsed_args['post_type'])
                        {
                            $url = add_query_arg('post_type', $parsed_args['post_type'], $url);
                        }
                        $text = $arc_week_start.$archive_week_separator.$arc_week_end;
                        if($parsed_args['show_post_count'])
                        {
                            $parsed_args['after'] = '&nbsp;('.$result->posts.')'.$after;
                        }
                        $selected = is_archive() && (string) $parsed_args['year'] === $result->yr && (string) $parsed_args['w'] === $result->week;
                        $output .= get_archives_link($url, $text, $parsed_args['format'], $parsed_args['before'], $parsed_args['after'], $selected);
                    }
                }
            }
        }
        elseif(('postbypost' === $parsed_args['type']) || ('alpha' === $parsed_args['type']))
        {
            $orderby = ('alpha' === $parsed_args['type']) ? 'post_title ASC ' : 'post_date DESC, ID DESC ';
            $query = "SELECT * FROM $wpdb->posts $join $where ORDER BY $orderby $limit";
            $key = md5($query);
            $key = "wp_get_archives:$key:$last_changed";
            $results = wp_cache_get($key, 'post-queries');
            if(! $results)
            {
                $results = $wpdb->get_results($query);
                wp_cache_set($key, $results, 'post-queries');
            }
            if($results)
            {
                foreach((array) $results as $result)
                {
                    if('0000-00-00 00:00:00' !== $result->post_date)
                    {
                        $url = get_permalink($result);
                        if($result->post_title)
                        {
                            $text = strip_tags(apply_filters('the_title', $result->post_title, $result->ID));
                        }
                        else
                        {
                            $text = $result->ID;
                        }
                        $selected = get_the_ID() === $result->ID;
                        $output .= get_archives_link($url, $text, $parsed_args['format'], $parsed_args['before'], $parsed_args['after'], $selected);
                    }
                }
            }
        }

        if($parsed_args['echo'])
        {
            echo $output;
        }
        else
        {
            return $output;
        }
    }

    function calendar_week_mod($num)
    {
        $base = 7;

        return ($num - $base * floor($num / $base));
    }

    function get_calendar($initial = true, $display = true)
    {
        global $wpdb, $m, $monthnum, $year, $wp_locale, $posts;

        $key = md5($m.$monthnum.$year);
        $cache = wp_cache_get('get_calendar', 'calendar');

        if($cache && is_array($cache) && isset($cache[$key]))
        {
            $output = apply_filters('get_calendar', $cache[$key]);

            if($display)
            {
                echo $output;

                return;
            }

            return $output;
        }

        if(! is_array($cache))
        {
            $cache = [];
        }

        // Quick check. If we have no posts at all, abort!
        if(! $posts)
        {
            $gotsome = $wpdb->get_var("SELECT 1 as test FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' LIMIT 1");
            if(! $gotsome)
            {
                $cache[$key] = '';
                wp_cache_set('get_calendar', $cache, 'calendar');

                return;
            }
        }

        if(isset($_GET['w']))
        {
            $w = (int) $_GET['w'];
        }
        // week_begins = 0 stands for Sunday.
        $week_begins = (int) get_option('start_of_week');

        // Let's figure out when we are.
        if(! empty($monthnum) && ! empty($year))
        {
            $thismonth = zeroise((int) $monthnum, 2);
            $thisyear = (int) $year;
        }
        elseif(! empty($w))
        {
            // We need to get the month from MySQL.
            $thisyear = (int) substr($m, 0, 4);
            // It seems MySQL's weeks disagree with PHP's.
            $d = (($w - 1) * 7) + 6;
            $thismonth = $wpdb->get_var("SELECT DATE_FORMAT((DATE_ADD('{$thisyear}0101', INTERVAL $d DAY) ), '%m')");
        }
        elseif(! empty($m))
        {
            $thisyear = (int) substr($m, 0, 4);
            if(strlen($m) < 6)
            {
                $thismonth = '01';
            }
            else
            {
                $thismonth = zeroise((int) substr($m, 4, 2), 2);
            }
        }
        else
        {
            $thisyear = current_time('Y');
            $thismonth = current_time('m');
        }

        $unixmonth = mktime(0, 0, 0, $thismonth, 1, $thisyear);
        $last_day = gmdate('t', $unixmonth);

        // Get the next and previous month and year with at least one post.
        $previous = $wpdb->get_row(
            "SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
		FROM $wpdb->posts
		WHERE post_date < '$thisyear-$thismonth-01'
		AND post_type = 'post' AND post_status = 'publish'
		ORDER BY post_date DESC
		LIMIT 1"
        );
        $next = $wpdb->get_row(
            "SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
		FROM $wpdb->posts
		WHERE post_date > '$thisyear-$thismonth-{$last_day} 23:59:59'
		AND post_type = 'post' AND post_status = 'publish'
		ORDER BY post_date ASC
		LIMIT 1"
        );

        /* translators: Calendar caption: 1: Month name, 2: 4-digit year. */
        $calendar_caption = _x('%1$s %2$s', 'calendar caption');
        $calendar_output = '<table id="wp-calendar" class="wp-calendar-table">
	<caption>'.sprintf($calendar_caption, $wp_locale->get_month($thismonth), gmdate('Y', $unixmonth)).'</caption>
	<thead>
	<tr>';

        $myweek = [];

        for($wdcount = 0; $wdcount <= 6; $wdcount++)
        {
            $myweek[] = $wp_locale->get_weekday(($wdcount + $week_begins) % 7);
        }

        foreach($myweek as $wd)
        {
            $day_name = $initial ? $wp_locale->get_weekday_initial($wd) : $wp_locale->get_weekday_abbrev($wd);
            $wd = esc_attr($wd);
            $calendar_output .= "\n\t\t<th scope=\"col\" title=\"$wd\">$day_name</th>";
        }

        $calendar_output .= '
	</tr>
	</thead>
	<tbody>
	<tr>';

        $daywithpost = [];

        // Get days with posts.
        $dayswithposts = $wpdb->get_results(
            "SELECT DISTINCT DAYOFMONTH(post_date)
		FROM $wpdb->posts WHERE post_date >= '{$thisyear}-{$thismonth}-01 00:00:00'
		AND post_type = 'post' AND post_status = 'publish'
		AND post_date <= '{$thisyear}-{$thismonth}-{$last_day} 23:59:59'", ARRAY_N
        );

        if($dayswithposts)
        {
            foreach((array) $dayswithposts as $daywith)
            {
                $daywithpost[] = (int) $daywith[0];
            }
        }

        // See how much we should pad in the beginning.
        $pad = calendar_week_mod(gmdate('w', $unixmonth) - $week_begins);
        if(0 != $pad)
        {
            $calendar_output .= "\n\t\t".'<td colspan="'.esc_attr($pad).'" class="pad">&nbsp;</td>';
        }

        $newrow = false;
        $daysinmonth = (int) gmdate('t', $unixmonth);

        for($day = 1; $day <= $daysinmonth; ++$day)
        {
            if(isset($newrow) && $newrow)
            {
                $calendar_output .= "\n\t</tr>\n\t<tr>\n\t\t";
            }
            $newrow = false;

            if(current_time('j') == $day && current_time('m') == $thismonth && current_time('Y') == $thisyear)
            {
                $calendar_output .= '<td id="today">';
            }
            else
            {
                $calendar_output .= '<td>';
            }

            if(in_array($day, $daywithpost, true))
            {
                // Any posts today?
                $date_format = gmdate(_x('F j, Y', 'daily archives date format'), strtotime("{$thisyear}-{$thismonth}-{$day}"));
                /* translators: Post calendar label. %s: Date. */
                $label = sprintf(__('Posts published on %s'), $date_format);
                $calendar_output .= sprintf('<a href="%s" aria-label="%s">%s</a>', get_day_link($thisyear, $thismonth, $day), esc_attr($label), $day);
            }
            else
            {
                $calendar_output .= $day;
            }

            $calendar_output .= '</td>';

            if(6 == calendar_week_mod(gmdate('w', mktime(0, 0, 0, $thismonth, $day, $thisyear)) - $week_begins))
            {
                $newrow = true;
            }
        }

        $pad = 7 - calendar_week_mod(gmdate('w', mktime(0, 0, 0, $thismonth, $day, $thisyear)) - $week_begins);
        if(0 != $pad && 7 != $pad)
        {
            $calendar_output .= "\n\t\t".'<td class="pad" colspan="'.esc_attr($pad).'">&nbsp;</td>';
        }

        $calendar_output .= "\n\t</tr>\n\t</tbody>";

        $calendar_output .= "\n\t</table>";

        $calendar_output .= '<nav aria-label="'.__('Previous and next months').'" class="wp-calendar-nav">';

        if($previous)
        {
            $calendar_output .= "\n\t\t".'<span class="wp-calendar-nav-prev"><a href="'.get_month_link($previous->year, $previous->month).'">&laquo; '.$wp_locale->get_month_abbrev($wp_locale->get_month($previous->month)).'</a></span>';
        }
        else
        {
            $calendar_output .= "\n\t\t".'<span class="wp-calendar-nav-prev">&nbsp;</span>';
        }

        $calendar_output .= "\n\t\t".'<span class="pad">&nbsp;</span>';

        if($next)
        {
            $calendar_output .= "\n\t\t".'<span class="wp-calendar-nav-next"><a href="'.get_month_link($next->year, $next->month).'">'.$wp_locale->get_month_abbrev($wp_locale->get_month($next->month)).' &raquo;</a></span>';
        }
        else
        {
            $calendar_output .= "\n\t\t".'<span class="wp-calendar-nav-next">&nbsp;</span>';
        }

        $calendar_output .= '
	</nav>';

        $cache[$key] = $calendar_output;
        wp_cache_set('get_calendar', $cache, 'calendar');

        if($display)
        {
            echo apply_filters('get_calendar', $calendar_output);

            return;
        }

        return apply_filters('get_calendar', $calendar_output);
    }

    function delete_get_calendar_cache()
    {
        wp_cache_delete('get_calendar', 'calendar');
    }

    function allowed_tags()
    {
        global $allowedtags;
        $allowed = '';
        foreach((array) $allowedtags as $tag => $attributes)
        {
            $allowed .= '<'.$tag;
            if(0 < count($attributes))
            {
                foreach($attributes as $attribute => $limits)
                {
                    $allowed .= ' '.$attribute.'=""';
                }
            }
            $allowed .= '> ';
        }

        return htmlentities($allowed);
    }

    function the_date_xml()
    {
        echo mysql2date('Y-m-d', get_post()->post_date, false);
    }

    function the_date($format = '', $before = '', $after = '', $display = true)
    {
        global $currentday, $previousday;

        $the_date = '';

        if(is_new_day())
        {
            $the_date = $before.get_the_date($format).$after;
            $previousday = $currentday;
        }

        $the_date = apply_filters('the_date', $the_date, $format, $before, $after);

        if($display)
        {
            echo $the_date;
        }
        else
        {
            return $the_date;
        }
    }

    function get_the_date($format = '', $post = null)
    {
        $post = get_post($post);

        if(! $post)
        {
            return false;
        }

        $_format = ! empty($format) ? $format : get_option('date_format');

        $the_date = get_post_time($_format, false, $post, true);

        return apply_filters('get_the_date', $the_date, $format, $post);
    }

    function the_modified_date($format = '', $before = '', $after = '', $display = true)
    {
        $the_modified_date = $before.get_the_modified_date($format).$after;

        $the_modified_date = apply_filters('the_modified_date', $the_modified_date, $format, $before, $after);

        if($display)
        {
            echo $the_modified_date;
        }
        else
        {
            return $the_modified_date;
        }
    }

    function get_the_modified_date($format = '', $post = null)
    {
        $post = get_post($post);

        if(! $post)
        {
            // For backward compatibility, failures go through the filter below.
            $the_time = false;
        }
        else
        {
            $_format = ! empty($format) ? $format : get_option('date_format');

            $the_time = get_post_modified_time($_format, false, $post, true);
        }

        return apply_filters('get_the_modified_date', $the_time, $format, $post);
    }

    function the_time($format = '')
    {
        echo apply_filters('the_time', get_the_time($format), $format);
    }

    function get_the_time($format = '', $post = null)
    {
        $post = get_post($post);

        if(! $post)
        {
            return false;
        }

        $_format = ! empty($format) ? $format : get_option('time_format');

        $the_time = get_post_time($_format, false, $post, true);

        return apply_filters('get_the_time', $the_time, $format, $post);
    }

    function get_post_time($format = 'U', $gmt = false, $post = null, $translate = false)
    {
        $post = get_post($post);

        if(! $post)
        {
            return false;
        }

        $source = ($gmt) ? 'gmt' : 'local';
        $datetime = get_post_datetime($post, 'date', $source);

        if(false === $datetime)
        {
            return false;
        }

        if('U' === $format || 'G' === $format)
        {
            $time = $datetime->getTimestamp();

            // Returns a sum of timestamp with timezone offset. Ideally should never be used.
            if(! $gmt)
            {
                $time += $datetime->getOffset();
            }
        }
        elseif($translate)
        {
            $time = wp_date($format, $datetime->getTimestamp(), $gmt ? new DateTimeZone('UTC') : null);
        }
        else
        {
            if($gmt)
            {
                $datetime = $datetime->setTimezone(new DateTimeZone('UTC'));
            }

            $time = $datetime->format($format);
        }

        return apply_filters('get_post_time', $time, $format, $gmt);
    }

    function get_post_datetime($post = null, $field = 'date', $source = 'local')
    {
        $post = get_post($post);

        if(! $post)
        {
            return false;
        }

        $wp_timezone = wp_timezone();

        if('gmt' === $source)
        {
            $time = ('modified' === $field) ? $post->post_modified_gmt : $post->post_date_gmt;
            $timezone = new DateTimeZone('UTC');
        }
        else
        {
            $time = ('modified' === $field) ? $post->post_modified : $post->post_date;
            $timezone = $wp_timezone;
        }

        if(empty($time) || '0000-00-00 00:00:00' === $time)
        {
            return false;
        }

        $datetime = date_create_immutable_from_format('Y-m-d H:i:s', $time, $timezone);

        if(false === $datetime)
        {
            return false;
        }

        return $datetime->setTimezone($wp_timezone);
    }

    function get_post_timestamp($post = null, $field = 'date')
    {
        $datetime = get_post_datetime($post, $field);

        if(false === $datetime)
        {
            return false;
        }

        return $datetime->getTimestamp();
    }

    function the_modified_time($format = '')
    {
        echo apply_filters('the_modified_time', get_the_modified_time($format), $format);
    }

    function get_the_modified_time($format = '', $post = null)
    {
        $post = get_post($post);

        if(! $post)
        {
            // For backward compatibility, failures go through the filter below.
            $the_time = false;
        }
        else
        {
            $_format = ! empty($format) ? $format : get_option('time_format');

            $the_time = get_post_modified_time($_format, false, $post, true);
        }

        return apply_filters('get_the_modified_time', $the_time, $format, $post);
    }

    function get_post_modified_time($format = 'U', $gmt = false, $post = null, $translate = false)
    {
        $post = get_post($post);

        if(! $post)
        {
            return false;
        }

        $source = ($gmt) ? 'gmt' : 'local';
        $datetime = get_post_datetime($post, 'modified', $source);

        if(false === $datetime)
        {
            return false;
        }

        if('U' === $format || 'G' === $format)
        {
            $time = $datetime->getTimestamp();

            // Returns a sum of timestamp with timezone offset. Ideally should never be used.
            if(! $gmt)
            {
                $time += $datetime->getOffset();
            }
        }
        elseif($translate)
        {
            $time = wp_date($format, $datetime->getTimestamp(), $gmt ? new DateTimeZone('UTC') : null);
        }
        else
        {
            if($gmt)
            {
                $datetime = $datetime->setTimezone(new DateTimeZone('UTC'));
            }

            $time = $datetime->format($format);
        }

        return apply_filters('get_post_modified_time', $time, $format, $gmt);
    }

    function the_weekday()
    {
        global $wp_locale;

        $post = get_post();

        if(! $post)
        {
            return;
        }

        $the_weekday = $wp_locale->get_weekday(get_post_time('w', false, $post));

        echo apply_filters('the_weekday', $the_weekday);
    }

    function the_weekday_date($before = '', $after = '')
    {
        global $wp_locale, $currentday, $previousweekday;

        $post = get_post();

        if(! $post)
        {
            return;
        }

        $the_weekday_date = '';

        if($currentday !== $previousweekday)
        {
            $the_weekday_date .= $before;
            $the_weekday_date .= $wp_locale->get_weekday(get_post_time('w', false, $post));
            $the_weekday_date .= $after;
            $previousweekday = $currentday;
        }

        echo apply_filters('the_weekday_date', $the_weekday_date, $before, $after);
    }

    function wp_head()
    {
        do_action('wp_head');
    }

    function wp_footer()
    {
        do_action('wp_footer');
    }

    function wp_body_open()
    {
        do_action('wp_body_open');
    }

    function feed_links($args = [])
    {
        if(! current_theme_supports('automatic-feed-links'))
        {
            return;
        }

        $defaults = [
            /* translators: Separator between site name and feed type in feed links. */
            'separator' => _x('&raquo;', 'feed link'),
            /* translators: 1: Site title, 2: Separator (raquo). */
            'feedtitle' => __('%1$s %2$s Feed'),
            /* translators: 1: Site title, 2: Separator (raquo). */
            'comstitle' => __('%1$s %2$s Comments Feed'),
        ];

        $args = wp_parse_args($args, $defaults);

        if(apply_filters('feed_links_show_posts_feed', true))
        {
            printf('<link rel="alternate" type="%s" title="%s" href="%s" />'."\n", feed_content_type(), esc_attr(sprintf($args['feedtitle'], get_bloginfo('name'), $args['separator'])), esc_url(get_feed_link()));
        }

        if(apply_filters('feed_links_show_comments_feed', true))
        {
            printf('<link rel="alternate" type="%s" title="%s" href="%s" />'."\n", feed_content_type(), esc_attr(sprintf($args['comstitle'], get_bloginfo('name'), $args['separator'])), esc_url(get_feed_link('comments_'.get_default_feed())));
        }
    }

    function feed_links_extra($args = [])
    {
        $defaults = [
            /* translators: Separator between site name and feed type in feed links. */
            'separator' => _x('&raquo;', 'feed link'),
            /* translators: 1: Site name, 2: Separator (raquo), 3: Post title. */
            'singletitle' => __('%1$s %2$s %3$s Comments Feed'),
            /* translators: 1: Site name, 2: Separator (raquo), 3: Category name. */
            'cattitle' => __('%1$s %2$s %3$s Category Feed'),
            /* translators: 1: Site name, 2: Separator (raquo), 3: Tag name. */
            'tagtitle' => __('%1$s %2$s %3$s Tag Feed'),
            /* translators: 1: Site name, 2: Separator (raquo), 3: Term name, 4: Taxonomy singular name. */
            'taxtitle' => __('%1$s %2$s %3$s %4$s Feed'),
            /* translators: 1: Site name, 2: Separator (raquo), 3: Author name. */
            'authortitle' => __('%1$s %2$s Posts by %3$s Feed'),
            /* translators: 1: Site name, 2: Separator (raquo), 3: Search query. */
            'searchtitle' => __('%1$s %2$s Search Results for &#8220;%3$s&#8221; Feed'),
            /* translators: 1: Site name, 2: Separator (raquo), 3: Post type name. */
            'posttypetitle' => __('%1$s %2$s %3$s Feed'),
        ];

        $args = wp_parse_args($args, $defaults);

        if(is_singular())
        {
            $id = 0;
            $post = get_post($id);

            $show_comments_feed = apply_filters('feed_links_show_comments_feed', true);

            $show_post_comments_feed = apply_filters('feed_links_extra_show_post_comments_feed', $show_comments_feed);

            if($show_post_comments_feed && (comments_open() || pings_open() || $post->comment_count > 0))
            {
                $title = sprintf($args['singletitle'], get_bloginfo('name'), $args['separator'], the_title_attribute(['echo' => false]));

                $feed_link = get_post_comments_feed_link($post->ID);

                if($feed_link)
                {
                    $href = $feed_link;
                }
            }
        }
        elseif(is_post_type_archive())
        {
            $show_post_type_archive_feed = apply_filters('feed_links_extra_show_post_type_archive_feed', true);

            if($show_post_type_archive_feed)
            {
                $post_type = get_query_var('post_type');

                if(is_array($post_type))
                {
                    $post_type = reset($post_type);
                }

                $post_type_obj = get_post_type_object($post_type);

                $title = sprintf($args['posttypetitle'], get_bloginfo('name'), $args['separator'], $post_type_obj->labels->name);

                $href = get_post_type_archive_feed_link($post_type_obj->name);
            }
        }
        elseif(is_category())
        {
            $show_category_feed = apply_filters('feed_links_extra_show_category_feed', true);

            if($show_category_feed)
            {
                $term = get_queried_object();

                if($term)
                {
                    $title = sprintf($args['cattitle'], get_bloginfo('name'), $args['separator'], $term->name);

                    $href = get_category_feed_link($term->term_id);
                }
            }
        }
        elseif(is_tag())
        {
            $show_tag_feed = apply_filters('feed_links_extra_show_tag_feed', true);

            if($show_tag_feed)
            {
                $term = get_queried_object();

                if($term)
                {
                    $title = sprintf($args['tagtitle'], get_bloginfo('name'), $args['separator'], $term->name);

                    $href = get_tag_feed_link($term->term_id);
                }
            }
        }
        elseif(is_tax())
        {
            $show_tax_feed = apply_filters('feed_links_extra_show_tax_feed', true);

            if($show_tax_feed)
            {
                $term = get_queried_object();

                if($term)
                {
                    $tax = get_taxonomy($term->taxonomy);

                    $title = sprintf($args['taxtitle'], get_bloginfo('name'), $args['separator'], $term->name, $tax->labels->singular_name);

                    $href = get_term_feed_link($term->term_id, $term->taxonomy);
                }
            }
        }
        elseif(is_author())
        {
            $show_author_feed = apply_filters('feed_links_extra_show_author_feed', true);

            if($show_author_feed)
            {
                $author_id = (int) get_query_var('author');

                $title = sprintf($args['authortitle'], get_bloginfo('name'), $args['separator'], get_the_author_meta('display_name', $author_id));

                $href = get_author_feed_link($author_id);
            }
        }
        elseif(is_search())
        {
            $show_search_feed = apply_filters('feed_links_extra_show_search_feed', true);

            if($show_search_feed)
            {
                $title = sprintf($args['searchtitle'], get_bloginfo('name'), $args['separator'], get_search_query(false));

                $href = get_search_feed_link();
            }
        }

        if(isset($title) && isset($href))
        {
            printf('<link rel="alternate" type="%s" title="%s" href="%s" />'."\n", feed_content_type(), esc_attr($title), esc_url($href));
        }
    }

    function rsd_link()
    {
        printf('<link rel="EditURI" type="application/rsd+xml" title="RSD" href="%s" />'."\n", esc_url(site_url('xmlrpc.php?rsd', 'rpc')));
    }

    function wp_strict_cross_origin_referrer()
    {
        ?>
        <meta name='referrer' content='strict-origin-when-cross-origin'/>
        <?php
    }

    function wp_site_icon()
    {
        if(! has_site_icon() && ! is_customize_preview())
        {
            return;
        }

        $meta_tags = [];
        $icon_32 = get_site_icon_url(32);
        if(empty($icon_32) && is_customize_preview())
        {
            $icon_32 = '/favicon.ico'; // Serve default favicon URL in customizer so element can be updated for preview.
        }
        if($icon_32)
        {
            $meta_tags[] = sprintf('<link rel="icon" href="%s" sizes="32x32" />', esc_url($icon_32));
        }
        $icon_192 = get_site_icon_url(192);
        if($icon_192)
        {
            $meta_tags[] = sprintf('<link rel="icon" href="%s" sizes="192x192" />', esc_url($icon_192));
        }
        $icon_180 = get_site_icon_url(180);
        if($icon_180)
        {
            $meta_tags[] = sprintf('<link rel="apple-touch-icon" href="%s" />', esc_url($icon_180));
        }
        $icon_270 = get_site_icon_url(270);
        if($icon_270)
        {
            $meta_tags[] = sprintf('<meta name="msapplication-TileImage" content="%s" />', esc_url($icon_270));
        }

        $meta_tags = apply_filters('site_icon_meta_tags', $meta_tags);
        $meta_tags = array_filter($meta_tags);

        foreach($meta_tags as $meta_tag)
        {
            echo "$meta_tag\n";
        }
    }

    function wp_resource_hints()
    {
        $hints = [
            'dns-prefetch' => wp_dependencies_unique_hosts(),
            'preconnect' => [],
            'prefetch' => [],
            'prerender' => [],
        ];

        foreach($hints as $relation_type => $urls)
        {
            $unique_urls = [];

            $urls = apply_filters('wp_resource_hints', $urls, $relation_type);

            foreach($urls as $key => $url)
            {
                $atts = [];

                if(is_array($url))
                {
                    if(isset($url['href']))
                    {
                        $atts = $url;
                        $url = $url['href'];
                    }
                    else
                    {
                        continue;
                    }
                }

                $url = esc_url($url, ['http', 'https']);

                if(! $url)
                {
                    continue;
                }

                if(isset($unique_urls[$url]))
                {
                    continue;
                }

                if(in_array($relation_type, ['preconnect', 'dns-prefetch'], true))
                {
                    $parsed = wp_parse_url($url);

                    if(empty($parsed['host']))
                    {
                        continue;
                    }

                    if('preconnect' === $relation_type && ! empty($parsed['scheme']))
                    {
                        $url = $parsed['scheme'].'://'.$parsed['host'];
                    }
                    else
                    {
                        // Use protocol-relative URLs for dns-prefetch or if scheme is missing.
                        $url = '//'.$parsed['host'];
                    }
                }

                $atts['rel'] = $relation_type;
                $atts['href'] = $url;

                $unique_urls[$url] = $atts;
            }

            foreach($unique_urls as $atts)
            {
                $html = '';

                foreach($atts as $attr => $value)
                {
                    if(
                        ! is_scalar($value) || (! in_array($attr, [
                                'as',
                                'crossorigin',
                                'href',
                                'pr',
                                'rel',
                                'type'
                            ],                             true) && ! is_numeric($attr))
                    )
                    {
                        continue;
                    }

                    $value = ('href' === $attr) ? esc_url($value) : esc_attr($value);

                    if(! is_string($attr))
                    {
                        $html .= " $value";
                    }
                    else
                    {
                        $html .= " $attr='$value'";
                    }
                }

                $html = trim($html);

                echo "<link $html />\n";
            }
        }
    }

    function wp_preload_resources()
    {
        $preload_resources = apply_filters('wp_preload_resources', []);

        if(! is_array($preload_resources))
        {
            return;
        }

        $unique_resources = [];

        // Parse the complete resource list and extract unique resources.
        foreach($preload_resources as $resource)
        {
            if(! is_array($resource))
            {
                continue;
            }

            $attributes = $resource;
            if(isset($resource['href']))
            {
                $href = $resource['href'];
                if(isset($unique_resources[$href]))
                {
                    continue;
                }
                $unique_resources[$href] = $attributes;
                // Media can use imagesrcset and not href.
            }
            elseif(('image' === $resource['as']) && (isset($resource['imagesrcset']) || isset($resource['imagesizes'])))
            {
                if(isset($unique_resources[$resource['imagesrcset']]))
                {
                    continue;
                }
                $unique_resources[$resource['imagesrcset']] = $attributes;
            }
            else
            {
                continue;
            }
        }

        // Build and output the HTML for each unique resource.
        foreach($unique_resources as $unique_resource)
        {
            $html = '';

            foreach($unique_resource as $resource_key => $resource_value)
            {
                if(! is_scalar($resource_value))
                {
                    continue;
                }

                // Ignore non-supported attributes.
                $non_supported_attributes = ['as', 'crossorigin', 'href', 'imagesrcset', 'imagesizes', 'type', 'media'];
                if(! in_array($resource_key, $non_supported_attributes, true) && ! is_numeric($resource_key))
                {
                    continue;
                }

                // imagesrcset only usable when preloading image, ignore otherwise.
                if(('imagesrcset' === $resource_key) && (! isset($unique_resource['as']) || ('image' !== $unique_resource['as'])))
                {
                    continue;
                }

                // imagesizes only usable when preloading image and imagesrcset present, ignore otherwise.
                if(('imagesizes' === $resource_key) && (! isset($unique_resource['as']) || ('image' !== $unique_resource['as']) || ! isset($unique_resource['imagesrcset'])))
                {
                    continue;
                }

                $resource_value = ('href' === $resource_key) ? esc_url($resource_value, [
                    'http',
                    'https'
                ]) : esc_attr($resource_value);

                if(! is_string($resource_key))
                {
                    $html .= " $resource_value";
                }
                else
                {
                    $html .= " $resource_key='$resource_value'";
                }
            }
            $html = trim($html);

            printf("<link rel='preload' %s />\n", $html);
        }
    }

    function wp_dependencies_unique_hosts()
    {
        global $wp_scripts, $wp_styles;

        $unique_hosts = [];

        foreach([$wp_scripts, $wp_styles] as $dependencies)
        {
            if($dependencies instanceof WP_Dependencies && ! empty($dependencies->queue))
            {
                foreach($dependencies->queue as $handle)
                {
                    if(! isset($dependencies->registered[$handle]))
                    {
                        continue;
                    }

                    /* @var _WP_Dependency $dependency */
                    $dependency = $dependencies->registered[$handle];
                    $parsed = wp_parse_url($dependency->src);

                    if(! empty($parsed['host']) && ! in_array($parsed['host'], $unique_hosts, true) && $parsed['host'] !== $_SERVER['SERVER_NAME'])
                    {
                        $unique_hosts[] = $parsed['host'];
                    }
                }
            }
        }

        return $unique_hosts;
    }

    function user_can_richedit()
    {
        global $wp_rich_edit, $is_gecko, $is_opera, $is_safari, $is_chrome, $is_IE, $is_edge;

        if(! isset($wp_rich_edit))
        {
            $wp_rich_edit = false;

            if('true' === get_user_option('rich_editing') || ! is_user_logged_in())
            { // Default to 'true' for logged out users.
                if($is_safari)
                {
                    $wp_rich_edit = ! wp_is_mobile() || (preg_match('!AppleWebKit/(\d+)!', $_SERVER['HTTP_USER_AGENT'], $match) && (int) $match[1] >= 534);
                }
                elseif($is_IE)
                {
                    $wp_rich_edit = str_contains($_SERVER['HTTP_USER_AGENT'], 'Trident/7.0;');
                }
                elseif($is_gecko || $is_chrome || $is_edge || ($is_opera && ! wp_is_mobile()))
                {
                    $wp_rich_edit = true;
                }
            }
        }

        return apply_filters('user_can_richedit', $wp_rich_edit);
    }

    function wp_default_editor()
    {
        $r = user_can_richedit() ? 'tinymce' : 'html'; // Defaults.
        if(wp_get_current_user())
        { // Look for cookie.
            $ed = get_user_setting('editor', 'tinymce');
            $r = (in_array($ed, ['tinymce', 'html', 'test'], true)) ? $ed : $r;
        }

        return apply_filters('wp_default_editor', $r);
    }

    function wp_editor($content, $editor_id, $settings = [])
    {
        if(! class_exists('_WP_Editors', false))
        {
            require ABSPATH.WPINC.'/class-wp-editor.php';
        }
        _WP_Editors::editor($content, $editor_id, $settings);
    }

    function wp_enqueue_editor()
    {
        if(! class_exists('_WP_Editors', false))
        {
            require ABSPATH.WPINC.'/class-wp-editor.php';
        }

        _WP_Editors::enqueue_default_editor();
    }

    function wp_enqueue_code_editor($args)
    {
        if(is_user_logged_in() && 'false' === wp_get_current_user()->syntax_highlighting)
        {
            return false;
        }

        $settings = wp_get_code_editor_settings($args);

        if(empty($settings) || empty($settings['codemirror']))
        {
            return false;
        }

        wp_enqueue_script('code-editor');
        wp_enqueue_style('code-editor');

        if(isset($settings['codemirror']['mode']))
        {
            $mode = $settings['codemirror']['mode'];
            if(is_string($mode))
            {
                $mode = [
                    'name' => $mode,
                ];
            }

            if(! empty($settings['codemirror']['lint']))
            {
                switch($mode['name'])
                {
                    case 'css':
                    case 'text/css':
                    case 'text/x-scss':
                    case 'text/x-less':
                        wp_enqueue_script('csslint');
                        break;
                    case 'htmlmixed':
                    case 'text/html':
                    case 'php':
                    case 'application/x-httpd-php':
                    case 'text/x-php':
                        wp_enqueue_script('htmlhint');
                        wp_enqueue_script('csslint');
                        wp_enqueue_script('jshint');
                        if(! current_user_can('unfiltered_html'))
                        {
                            wp_enqueue_script('htmlhint-kses');
                        }
                        break;
                    case 'javascript':
                    case 'application/ecmascript':
                    case 'application/json':
                    case 'application/javascript':
                    case 'application/ld+json':
                    case 'text/typescript':
                    case 'application/typescript':
                        wp_enqueue_script('jshint');
                        wp_enqueue_script('jsonlint');
                        break;
                }
            }
        }

        wp_add_inline_script('code-editor', sprintf('jQuery.extend( wp.codeEditor.defaultSettings, %s );', wp_json_encode($settings)));

        do_action('wp_enqueue_code_editor', $settings);

        return $settings;
    }

    function wp_get_code_editor_settings($args)
    {
        $settings = [
            'codemirror' => [
                'indentUnit' => 4,
                'indentWithTabs' => true,
                'inputStyle' => 'contenteditable',
                'lineNumbers' => true,
                'lineWrapping' => true,
                'styleActiveLine' => true,
                'continueComments' => true,
                'extraKeys' => [
                    'Ctrl-Space' => 'autocomplete',
                    'Ctrl-/' => 'toggleComment',
                    'Cmd-/' => 'toggleComment',
                    'Alt-F' => 'findPersistent',
                    'Ctrl-F' => 'findPersistent',
                    'Cmd-F' => 'findPersistent',
                ],
                'direction' => 'ltr', // Code is shown in LTR even in RTL languages.
                'gutters' => [],
            ],
            'csslint' => [
                'errors' => true, // Parsing errors.
                'box-model' => true,
                'display-property-grouping' => true,
                'duplicate-properties' => true,
                'known-properties' => true,
                'outline-none' => true,
            ],
            'jshint' => [
                // The following are copied from <https://github.com/WordPress/wordpress-develop/blob/4.8.1/.jshintrc>.
                'boss' => true,
                'curly' => true,
                'eqeqeq' => true,
                'eqnull' => true,
                'es3' => true,
                'expr' => true,
                'immed' => true,
                'noarg' => true,
                'nonbsp' => true,
                'onevar' => true,
                'quotmark' => 'single',
                'trailing' => true,
                'undef' => true,
                'unused' => true,

                'browser' => true,

                'globals' => [
                    '_' => false,
                    'Backbone' => false,
                    'jQuery' => false,
                    'JSON' => false,
                    'wp' => false,
                ],
            ],
            'htmlhint' => [
                'tagname-lowercase' => true,
                'attr-lowercase' => true,
                'attr-value-double-quotes' => false,
                'doctype-first' => false,
                'tag-pair' => true,
                'spec-char-escape' => true,
                'id-unique' => true,
                'src-not-empty' => true,
                'attr-no-duplication' => true,
                'alt-require' => true,
                'space-tab-mixed-disabled' => 'tab',
                'attr-unsafe-chars' => true,
            ],
        ];

        $type = '';
        if(isset($args['type']))
        {
            $type = $args['type'];

            // Remap MIME types to ones that CodeMirror modes will recognize.
            if('application/x-patch' === $type || 'text/x-patch' === $type)
            {
                $type = 'text/x-diff';
            }
        }
        elseif(isset($args['file']) && str_contains(basename($args['file']), '.'))
        {
            $extension = strtolower(pathinfo($args['file'], PATHINFO_EXTENSION));
            foreach(wp_get_mime_types() as $exts => $mime)
            {
                if(preg_match('!^('.$exts.')$!i', $extension))
                {
                    $type = $mime;
                    break;
                }
            }

            // Supply any types that are not matched by wp_get_mime_types().
            if(empty($type))
            {
                switch($extension)
                {
                    case 'conf':
                        $type = 'text/nginx';
                        break;
                    case 'css':
                        $type = 'text/css';
                        break;
                    case 'diff':
                    case 'patch':
                        $type = 'text/x-diff';
                        break;
                    case 'html':
                    case 'htm':
                        $type = 'text/html';
                        break;
                    case 'http':
                        $type = 'message/http';
                        break;
                    case 'js':
                        $type = 'text/javascript';
                        break;
                    case 'json':
                        $type = 'application/json';
                        break;
                    case 'jsx':
                        $type = 'text/jsx';
                        break;
                    case 'less':
                        $type = 'text/x-less';
                        break;
                    case 'md':
                        $type = 'text/x-gfm';
                        break;
                    case 'php':
                    case 'phtml':
                    case 'php3':
                    case 'php4':
                    case 'php5':
                    case 'php7':
                    case 'phps':
                        $type = 'application/x-httpd-php';
                        break;
                    case 'scss':
                        $type = 'text/x-scss';
                        break;
                    case 'sass':
                        $type = 'text/x-sass';
                        break;
                    case 'sh':
                    case 'bash':
                        $type = 'text/x-sh';
                        break;
                    case 'sql':
                        $type = 'text/x-sql';
                        break;
                    case 'svg':
                        $type = 'application/svg+xml';
                        break;
                    case 'xml':
                        $type = 'text/xml';
                        break;
                    case 'yml':
                    case 'yaml':
                        $type = 'text/x-yaml';
                        break;
                    case 'txt':
                    default:
                        $type = 'text/plain';
                        break;
                }
            }
        }

        if(in_array($type, ['text/css', 'text/x-scss', 'text/x-less', 'text/x-sass'], true))
        {
            $settings['codemirror'] = array_merge($settings['codemirror'], [
                'mode' => $type,
                'lint' => false,
                'autoCloseBrackets' => true,
                'matchBrackets' => true,
            ]);
        }
        elseif('text/x-diff' === $type)
        {
            $settings['codemirror'] = array_merge($settings['codemirror'], [
                'mode' => 'diff',
            ]);
        }
        elseif('text/html' === $type)
        {
            $settings['codemirror'] = array_merge($settings['codemirror'], [
                'mode' => 'htmlmixed',
                'lint' => true,
                'autoCloseBrackets' => true,
                'autoCloseTags' => true,
                'matchTags' => [
                    'bothTags' => true,
                ],
            ]);

            if(! current_user_can('unfiltered_html'))
            {
                $settings['htmlhint']['kses'] = wp_kses_allowed_html('post');
            }
        }
        elseif('text/x-gfm' === $type)
        {
            $settings['codemirror'] = array_merge($settings['codemirror'], [
                'mode' => 'gfm',
                'highlightFormatting' => true,
            ]);
        }
        elseif('application/javascript' === $type || 'text/javascript' === $type)
        {
            $settings['codemirror'] = array_merge($settings['codemirror'], [
                'mode' => 'javascript',
                'lint' => true,
                'autoCloseBrackets' => true,
                'matchBrackets' => true,
            ]);
        }
        elseif(str_contains($type, 'json'))
        {
            $settings['codemirror'] = array_merge($settings['codemirror'], [
                'mode' => [
                    'name' => 'javascript',
                ],
                'lint' => true,
                'autoCloseBrackets' => true,
                'matchBrackets' => true,
            ]);
            if('application/ld+json' === $type)
            {
                $settings['codemirror']['mode']['jsonld'] = true;
            }
            else
            {
                $settings['codemirror']['mode']['json'] = true;
            }
        }
        elseif(str_contains($type, 'jsx'))
        {
            $settings['codemirror'] = array_merge($settings['codemirror'], [
                'mode' => 'jsx',
                'autoCloseBrackets' => true,
                'matchBrackets' => true,
            ]);
        }
        elseif('text/x-markdown' === $type)
        {
            $settings['codemirror'] = array_merge($settings['codemirror'], [
                'mode' => 'markdown',
                'highlightFormatting' => true,
            ]);
        }
        elseif('text/nginx' === $type)
        {
            $settings['codemirror'] = array_merge($settings['codemirror'], [
                'mode' => 'nginx',
            ]);
        }
        elseif('application/x-httpd-php' === $type)
        {
            $settings['codemirror'] = array_merge($settings['codemirror'], [
                'mode' => 'php',
                'autoCloseBrackets' => true,
                'autoCloseTags' => true,
                'matchBrackets' => true,
                'matchTags' => [
                    'bothTags' => true,
                ],
            ]);
        }
        elseif('text/x-sql' === $type || 'text/x-mysql' === $type)
        {
            $settings['codemirror'] = array_merge($settings['codemirror'], [
                'mode' => 'sql',
                'autoCloseBrackets' => true,
                'matchBrackets' => true,
            ]);
        }
        elseif(str_contains($type, 'xml'))
        {
            $settings['codemirror'] = array_merge($settings['codemirror'], [
                'mode' => 'xml',
                'autoCloseBrackets' => true,
                'autoCloseTags' => true,
                'matchTags' => [
                    'bothTags' => true,
                ],
            ]);
        }
        elseif('text/x-yaml' === $type)
        {
            $settings['codemirror'] = array_merge($settings['codemirror'], [
                'mode' => 'yaml',
            ]);
        }
        else
        {
            $settings['codemirror']['mode'] = $type;
        }

        if(! empty($settings['codemirror']['lint']))
        {
            $settings['codemirror']['gutters'][] = 'CodeMirror-lint-markers';
        }

        // Let settings supplied via args override any defaults.
        foreach(wp_array_slice_assoc($args, ['codemirror', 'csslint', 'jshint', 'htmlhint']) as $key => $value)
        {
            $settings[$key] = array_merge($settings[$key], $value);
        }

        return apply_filters('wp_code_editor_settings', $settings, $args);
    }

    function get_search_query($escaped = true)
    {
        $query = apply_filters('get_search_query', get_query_var('s'));

        if($escaped)
        {
            $query = esc_attr($query);
        }

        return $query;
    }

    function the_search_query()
    {
        echo esc_attr(apply_filters('the_search_query', get_search_query(false)));
    }

    function get_language_attributes($doctype = 'html')
    {
        $attributes = [];

        if(function_exists('is_rtl') && is_rtl())
        {
            $attributes[] = 'dir="rtl"';
        }

        $lang = get_bloginfo('language');
        if($lang)
        {
            if('text/html' === get_option('html_type') || 'html' === $doctype)
            {
                $attributes[] = 'lang="'.esc_attr($lang).'"';
            }

            if('text/html' !== get_option('html_type') || 'xhtml' === $doctype)
            {
                $attributes[] = 'xml:lang="'.esc_attr($lang).'"';
            }
        }

        $output = implode(' ', $attributes);

        return apply_filters('language_attributes', $output, $doctype);
    }

    function language_attributes($doctype = 'html')
    {
        echo get_language_attributes($doctype);
    }

    function paginate_links($args = '')
    {
        global $wp_query, $wp_rewrite;

        // Setting up default values based on the current URL.
        $pagenum_link = html_entity_decode(get_pagenum_link());
        $url_parts = explode('?', $pagenum_link);

        // Get max pages and current page out of the current query, if available.
        $total = isset($wp_query->max_num_pages) ? $wp_query->max_num_pages : 1;
        $current = get_query_var('paged') ? (int) get_query_var('paged') : 1;

        // Append the format placeholder to the base URL.
        $pagenum_link = trailingslashit($url_parts[0]).'%_%';

        // URL base depends on permalink settings.
        $format = $wp_rewrite->using_index_permalinks() && ! strpos($pagenum_link, 'index.php') ? 'index.php/' : '';
        $format .= $wp_rewrite->using_permalinks() ? user_trailingslashit($wp_rewrite->pagination_base.'/%#%', 'paged') : '?paged=%#%';

        $defaults = [
            'base' => $pagenum_link, // http://example.com/all_posts.php%_% : %_% is replaced by format (below).
            'format' => $format, // ?page=%#% : %#% is replaced by the page number.
            'total' => $total,
            'current' => $current,
            'aria_current' => 'page',
            'show_all' => false,
            'prev_next' => true,
            'prev_text' => __('&laquo; Previous'),
            'next_text' => __('Next &raquo;'),
            'end_size' => 1,
            'mid_size' => 2,
            'type' => 'plain',
            'add_args' => [], // Array of query args to add.
            'add_fragment' => '',
            'before_page_number' => '',
            'after_page_number' => '',
        ];

        $args = wp_parse_args($args, $defaults);

        if(! is_array($args['add_args']))
        {
            $args['add_args'] = [];
        }

        // Merge additional query vars found in the original URL into 'add_args' array.
        if(isset($url_parts[1]))
        {
            // Find the format argument.
            $format = explode('?', str_replace('%_%', $args['format'], $args['base']));
            $format_query = isset($format[1]) ? $format[1] : '';
            wp_parse_str($format_query, $format_args);

            // Find the query args of the requested URL.
            wp_parse_str($url_parts[1], $url_query_args);

            // Remove the format argument from the array of query arguments, to avoid overwriting custom format.
            foreach($format_args as $format_arg => $format_arg_value)
            {
                unset($url_query_args[$format_arg]);
            }

            $args['add_args'] = array_merge($args['add_args'], urlencode_deep($url_query_args));
        }

        // Who knows what else people pass in $args.
        $total = (int) $args['total'];
        if($total < 2)
        {
            return;
        }
        $current = (int) $args['current'];
        $end_size = (int) $args['end_size']; // Out of bounds? Make it the default.
        if($end_size < 1)
        {
            $end_size = 1;
        }
        $mid_size = (int) $args['mid_size'];
        if($mid_size < 0)
        {
            $mid_size = 2;
        }

        $add_args = $args['add_args'];
        $r = '';
        $page_links = [];
        $dots = false;

        if($args['prev_next'] && $current && 1 < $current) :
            $link = str_replace('%_%', 2 == $current ? '' : $args['format'], $args['base']);
            $link = str_replace('%#%', $current - 1, $link);
            if($add_args)
            {
                $link = add_query_arg($add_args, $link);
            }
            $link .= $args['add_fragment'];

            $page_links[] = sprintf(
                '<a class="prev page-numbers" href="%s">%s</a>', esc_url(apply_filters('paginate_links', $link)), $args['prev_text']
            );
        endif;

        for($n = 1; $n <= $total; $n++) :
            if($n == $current) :
                $page_links[] = sprintf('<span aria-current="%s" class="page-numbers current">%s</span>', esc_attr($args['aria_current']), $args['before_page_number'].number_format_i18n($n).$args['after_page_number']);

                $dots = true;
            else :
                if($args['show_all'] || ($n <= $end_size || ($current && $n >= $current - $mid_size && $n <= $current + $mid_size) || $n > $total - $end_size)) :
                    $link = str_replace('%_%', 1 == $n ? '' : $args['format'], $args['base']);
                    $link = str_replace('%#%', $n, $link);
                    if($add_args)
                    {
                        $link = add_query_arg($add_args, $link);
                    }
                    $link .= $args['add_fragment'];

                    $page_links[] = sprintf('<a class="page-numbers" href="%s">%s</a>', esc_url(apply_filters('paginate_links', $link)), $args['before_page_number'].number_format_i18n($n).$args['after_page_number']);

                    $dots = true;
                elseif($dots && ! $args['show_all']) :
                    $page_links[] = '<span class="page-numbers dots">'.__('&hellip;').'</span>';

                    $dots = false;
                endif;
            endif;
        endfor;

        if($args['prev_next'] && $current && $current < $total) :
            $link = str_replace('%_%', $args['format'], $args['base']);
            $link = str_replace('%#%', $current + 1, $link);
            if($add_args)
            {
                $link = add_query_arg($add_args, $link);
            }
            $link .= $args['add_fragment'];

            $page_links[] = sprintf('<a class="next page-numbers" href="%s">%s</a>', esc_url(apply_filters('paginate_links', $link)), $args['next_text']);
        endif;

        switch($args['type'])
        {
            case 'array':
                return $page_links;

            case 'list':
                $r .= "<ul class='page-numbers'>\n\t<li>";
                $r .= implode("</li>\n\t<li>", $page_links);
                $r .= "</li>\n</ul>\n";
                break;

            default:
                $r = implode("\n", $page_links);
                break;
        }

        $r = apply_filters('paginate_links_output', $r, $args);

        return $r;
    }

    function wp_admin_css_color($key, $name, $url, $colors = [], $icons = [])
    {
        global $_wp_admin_css_colors;

        if(! isset($_wp_admin_css_colors))
        {
            $_wp_admin_css_colors = [];
        }

        $_wp_admin_css_colors[$key] = (object) [
            'name' => $name,
            'url' => $url,
            'colors' => $colors,
            'icon_colors' => $icons,
        ];
    }

    function register_admin_color_schemes()
    {
        $suffix = is_rtl() ? '-rtl' : '';
        $suffix .= SCRIPT_DEBUG ? '' : '.min';

        wp_admin_css_color('fresh', _x('Default', 'admin color scheme'), false, [
            '#1d2327',
            '#2c3338',
            '#2271b1',
            '#72aee6'
        ],                 [
                               'base' => '#a7aaad',
                               'focus' => '#72aee6',
                               'current' => '#fff',
                           ]);

        wp_admin_css_color('light', _x('Light', 'admin color scheme'), admin_url("css/colors/light/colors$suffix.css"), [
            '#e5e5e5',
            '#999',
            '#d64e07',
            '#04a4cc'
        ],                 [
                               'base' => '#999',
                               'focus' => '#ccc',
                               'current' => '#ccc',
                           ]);

        wp_admin_css_color('modern', _x('Modern', 'admin color scheme'), admin_url("css/colors/modern/colors$suffix.css"), [
            '#1e1e1e',
            '#3858e9',
            '#33f078'
        ],                 [
                               'base' => '#f3f1f1',
                               'focus' => '#fff',
                               'current' => '#fff',
                           ]);

        wp_admin_css_color('blue', _x('Blue', 'admin color scheme'), admin_url("css/colors/blue/colors$suffix.css"), [
            '#096484',
            '#4796b3',
            '#52accc',
            '#74B6CE'
        ],                 [
                               'base' => '#e5f8ff',
                               'focus' => '#fff',
                               'current' => '#fff',
                           ]);

        wp_admin_css_color('midnight', _x('Midnight', 'admin color scheme'), admin_url("css/colors/midnight/colors$suffix.css"), [
            '#25282b',
            '#363b3f',
            '#69a8bb',
            '#e14d43'
        ],                 [
                               'base' => '#f1f2f3',
                               'focus' => '#fff',
                               'current' => '#fff',
                           ]);

        wp_admin_css_color('sunrise', _x('Sunrise', 'admin color scheme'), admin_url("css/colors/sunrise/colors$suffix.css"), [
            '#b43c38',
            '#cf4944',
            '#dd823b',
            '#ccaf0b'
        ],                 [
                               'base' => '#f3f1f1',
                               'focus' => '#fff',
                               'current' => '#fff',
                           ]);

        wp_admin_css_color('ectoplasm', _x('Ectoplasm', 'admin color scheme'), admin_url("css/colors/ectoplasm/colors$suffix.css"), [
            '#413256',
            '#523f6d',
            '#a3b745',
            '#d46f15'
        ],                 [
                               'base' => '#ece6f6',
                               'focus' => '#fff',
                               'current' => '#fff',
                           ]);

        wp_admin_css_color('ocean', _x('Ocean', 'admin color scheme'), admin_url("css/colors/ocean/colors$suffix.css"), [
            '#627c83',
            '#738e96',
            '#9ebaa0',
            '#aa9d88'
        ],                 [
                               'base' => '#f2fcff',
                               'focus' => '#fff',
                               'current' => '#fff',
                           ]);

        wp_admin_css_color('coffee', _x('Coffee', 'admin color scheme'), admin_url("css/colors/coffee/colors$suffix.css"), [
            '#46403c',
            '#59524c',
            '#c7a589',
            '#9ea476'
        ],                 [
                               'base' => '#f3f2f1',
                               'focus' => '#fff',
                               'current' => '#fff',
                           ]);
    }

    function wp_admin_css_uri($file = 'wp-admin')
    {
        if(defined('WP_INSTALLING'))
        {
            $_file = "./$file.css";
        }
        else
        {
            $_file = admin_url("$file.css");
        }
        $_file = add_query_arg('version', get_bloginfo('version'), $_file);

        return apply_filters('wp_admin_css_uri', $_file, $file);
    }

    function wp_admin_css($file = 'wp-admin', $force_echo = false)
    {
        // For backward compatibility.
        $handle = str_starts_with($file, 'css/') ? substr($file, 4) : $file;

        if(wp_styles()->query($handle))
        {
            if($force_echo || did_action('wp_print_styles'))
            {
                // We already printed the style queue. Print this one immediately.
                wp_print_styles($handle);
            }
            else
            {
                // Add to style queue.
                wp_enqueue_style($handle);
            }

            return;
        }

        $stylesheet_link = sprintf("<link rel='stylesheet' href='%s' type='text/css' />\n", esc_url(wp_admin_css_uri($file)));

        echo apply_filters('wp_admin_css', $stylesheet_link, $file);

        if(function_exists('is_rtl') && is_rtl())
        {
            $rtl_stylesheet_link = sprintf("<link rel='stylesheet' href='%s' type='text/css' />\n", esc_url(wp_admin_css_uri("$file-rtl")));

            echo apply_filters('wp_admin_css', $rtl_stylesheet_link, "$file-rtl");
        }
    }

    function add_thickbox()
    {
        wp_enqueue_script('thickbox');
        wp_enqueue_style('thickbox');

        if(is_network_admin())
        {
            add_action('admin_head', '_thickbox_path_admin_subfolder');
        }
    }

    function wp_generator()
    {
        the_generator(apply_filters('wp_generator_type', 'xhtml'));
    }

    function the_generator($type)
    {
        echo apply_filters('the_generator', get_the_generator($type), $type)."\n";
    }

    function get_the_generator($type = '')
    {
        if(empty($type))
        {
            $current_filter = current_filter();
            if(empty($current_filter))
            {
                return;
            }

            switch($current_filter)
            {
                case 'rss2_head':
                case 'commentsrss2_head':
                    $type = 'rss2';
                    break;
                case 'rss_head':
                case 'opml_head':
                    $type = 'comment';
                    break;
                case 'rdf_header':
                    $type = 'rdf';
                    break;
                case 'atom_head':
                case 'comments_atom_head':
                case 'app_head':
                    $type = 'atom';
                    break;
            }
        }

        switch($type)
        {
            case 'html':
                $gen = '<meta name="generator" content="WordPress '.esc_attr(get_bloginfo('version')).'">';
                break;
            case 'xhtml':
                $gen = '<meta name="generator" content="WordPress '.esc_attr(get_bloginfo('version')).'" />';
                break;
            case 'atom':
                $gen = '<generator uri="https://wordpress.org/" version="'.esc_attr(get_bloginfo_rss('version')).'">WordPress</generator>';
                break;
            case 'rss2':
                $gen = '<generator>'.sanitize_url('https://wordpress.org/?v='.get_bloginfo_rss('version')).'</generator>';
                break;
            case 'rdf':
                $gen = '<admin:generatorAgent rdf:resource="'.sanitize_url('https://wordpress.org/?v='.get_bloginfo_rss('version')).'" />';
                break;
            case 'comment':
                $gen = '<!-- generator="WordPress/'.esc_attr(get_bloginfo('version')).'" -->';
                break;
            case 'export':
                $gen = '<!-- generator="WordPress/'.esc_attr(get_bloginfo_rss('version')).'" created="'.gmdate('Y-m-d H:i').'" -->';
                break;
        }

        return apply_filters("get_the_generator_{$type}", $gen, $type);
    }

    function checked($checked, $current = true, $display = true)
    {
        return __checked_selected_helper($checked, $current, $display, 'checked');
    }

    function selected($selected, $current = true, $display = true)
    {
        return __checked_selected_helper($selected, $current, $display, 'selected');
    }

    function disabled($disabled, $current = true, $display = true)
    {
        return __checked_selected_helper($disabled, $current, $display, 'disabled');
    }

    function wp_readonly($readonly_value, $current = true, $display = true)
    {
        return __checked_selected_helper($readonly_value, $current, $display, 'readonly');
    }

    /*
 * Include a compat `readonly()` function on PHP < 8.1. Since PHP 8.1,
 * `readonly` is a reserved keyword and cannot be used as a function name.
 * In order to avoid PHP parser errors, this function was extracted
 * to a separate file and is only included conditionally on PHP < 8.1.
 */
    if(PHP_VERSION_ID < 80100)
    {
        require_once __DIR__.'/php-compat/readonly.php';
    }

    function __checked_selected_helper($helper, $current, $display, $type)
    { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionDoubleUnderscore,PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore
        if((string) $helper === (string) $current)
        {
            $result = " $type='$type'";
        }
        else
        {
            $result = '';
        }

        if($display)
        {
            echo $result;
        }

        return $result;
    }

    function wp_required_field_indicator()
    {
        /* translators: Character to identify required form fields. */
        $glyph = __('*');
        $indicator = '<span class="required">'.esc_html($glyph).'</span>';

        return apply_filters('wp_required_field_indicator', $indicator);
    }

    function wp_required_field_message()
    {
        $message = sprintf('<span class="required-field-message">%s</span>', /* translators: %s: Asterisk symbol (*). */ sprintf(__('Required fields are marked %s'), wp_required_field_indicator()));

        return apply_filters('wp_required_field_message', $message);
    }

    function wp_heartbeat_settings($settings)
    {
        if(! is_admin())
        {
            $settings['ajaxurl'] = admin_url('admin-ajax.php', 'relative');
        }

        if(is_user_logged_in())
        {
            $settings['nonce'] = wp_create_nonce('heartbeat-nonce');
        }

        return $settings;
    }
