<?php

    #[AllowDynamicProperties]
    class WP_Sitemaps_Renderer
    {
        protected $stylesheet = '';

        protected $stylesheet_index = '';

        public function __construct()
        {
            $stylesheet_url = $this->get_sitemap_stylesheet_url();

            if($stylesheet_url)
            {
                $this->stylesheet = '<?xml-stylesheet type="text/xsl" href="'.esc_url($stylesheet_url).'" ?>';
            }

            $stylesheet_index_url = $this->get_sitemap_index_stylesheet_url();

            if($stylesheet_index_url)
            {
                $this->stylesheet_index = '<?xml-stylesheet type="text/xsl" href="'.esc_url($stylesheet_index_url).'" ?>';
            }
        }

        public function get_sitemap_stylesheet_url()
        {
            global $wp_rewrite;

            $sitemap_url = home_url('/wp-sitemap.xsl');

            if(! $wp_rewrite->using_permalinks())
            {
                $sitemap_url = home_url('/?sitemap-stylesheet=sitemap');
            }

            return apply_filters('wp_sitemaps_stylesheet_url', $sitemap_url);
        }

        public function get_sitemap_index_stylesheet_url()
        {
            global $wp_rewrite;

            $sitemap_url = home_url('/wp-sitemap-index.xsl');

            if(! $wp_rewrite->using_permalinks())
            {
                $sitemap_url = home_url('/?sitemap-stylesheet=index');
            }

            return apply_filters('wp_sitemaps_stylesheet_index_url', $sitemap_url);
        }

        public function render_index($sitemaps)
        {
            header('Content-Type: application/xml; charset=UTF-8');

            $this->check_for_simple_xml_availability();

            $index_xml = $this->get_sitemap_index_xml($sitemaps);

            if(! empty($index_xml))
            {
                // All output is escaped within get_sitemap_index_xml().
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $index_xml;
            }
        }

        private function check_for_simple_xml_availability()
        {
            if(! class_exists('SimpleXMLElement'))
            {
                add_filter('wp_die_handler', static function()
                {
                    return '_xml_wp_die_handler';
                });

                wp_die(sprintf(/* translators: %s: SimpleXML */ esc_xml(__('Could not generate XML sitemap due to missing %s extension')), 'SimpleXML'), esc_xml(__('WordPress &rsaquo; Error')), [
                    'response' => 501, // "Not implemented".
                ]);
            }
        }

        public function get_sitemap_index_xml($sitemaps)
        {
            $sitemap_index = new SimpleXMLElement(sprintf('%1$s%2$s%3$s', '<?xml version="1.0" encoding="UTF-8" ?>', $this->stylesheet_index, '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" />'));

            foreach($sitemaps as $entry)
            {
                $sitemap = $sitemap_index->addChild('sitemap');

                // Add each element as a child node to the <sitemap> entry.
                foreach($entry as $name => $value)
                {
                    if('loc' === $name)
                    {
                        $sitemap->addChild($name, esc_url($value));
                    }
                    elseif('lastmod' === $name)
                    {
                        $sitemap->addChild($name, esc_xml($value));
                    }
                    else
                    {
                        _doing_it_wrong(
                            __METHOD__, sprintf(/* translators: %s: List of element names. */ __('Fields other than %s are not currently supported for the sitemap index.'), implode(',', [
                                                                                                                                                                               'loc',
                                                                                                                                                                               'lastmod'
                                                                                                                                                                           ])
                        ),  '5.5.0'
                        );
                    }
                }
            }

            return $sitemap_index->asXML();
        }

        public function render_sitemap($url_list)
        {
            header('Content-Type: application/xml; charset=UTF-8');

            $this->check_for_simple_xml_availability();

            $sitemap_xml = $this->get_sitemap_xml($url_list);

            if(! empty($sitemap_xml))
            {
                // All output is escaped within get_sitemap_xml().
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $sitemap_xml;
            }
        }

        public function get_sitemap_xml($url_list)
        {
            $urlset = new SimpleXMLElement(sprintf('%1$s%2$s%3$s', '<?xml version="1.0" encoding="UTF-8" ?>', $this->stylesheet, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" />'));

            foreach($url_list as $url_item)
            {
                $url = $urlset->addChild('url');

                // Add each element as a child node to the <url> entry.
                foreach($url_item as $name => $value)
                {
                    if('loc' === $name)
                    {
                        $url->addChild($name, esc_url($value));
                    }
                    elseif(in_array($name, ['lastmod', 'changefreq', 'priority'], true))
                    {
                        $url->addChild($name, esc_xml($value));
                    }
                    else
                    {
                        _doing_it_wrong(
                            __METHOD__, sprintf(/* translators: %s: List of element names. */ __('Fields other than %s are not currently supported for sitemaps.'), implode(',', [
                                                                                                                                                                      'loc',
                                                                                                                                                                      'lastmod',
                                                                                                                                                                      'changefreq',
                                                                                                                                                                      'priority'
                                                                                                                                                                  ])
                        ),  '5.5.0'
                        );
                    }
                }
            }

            return $urlset->asXML();
        }
    }
