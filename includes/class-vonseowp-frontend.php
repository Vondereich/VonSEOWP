<?php
/**
 * Frontend Meta Output (Head Tags + JSON-LD)
 */
if (!defined('ABSPATH')) exit;




class VonSEOWP_Frontend {
    private static $has_run_meta = false;
    private static $has_run_json = false;

    public function __construct() {
        add_filter('pre_get_document_title', array($this, 'filter_document_title'), 15);
        add_action('wp_head', array($this, 'output_meta_tags'), 1);
        add_action('wp_head', array($this, 'output_json_ld'), 99);
        add_filter('robots_txt', array($this, 'handle_robots_txt'), 99, 2);
        add_action('init', array($this, 'add_robots_rewrite_rule'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_filter('wp_robots', array($this, 'filter_robots'), 99);
        remove_action('wp_head', 'rel_canonical');
        remove_action('wp_head', 'wp_generator');
    }

    public function filter_robots(array $robots): array {
        $post = get_post();
        $noindex = !get_option('blog_public') || is_search() || is_404()
            || (is_singular() && $post && get_post_meta($post->ID, '_vonseowp_noindex', true) === '1');
        if ($noindex) {
            $robots['noindex'] = true;
            $robots['nofollow'] = true;
        }
        if (!empty($robots['noindex'])) {
            unset($robots['index']);
        } else {
            $robots['index'] = true;
            if (!isset($robots['max-image-preview']) && empty($robots['noimageindex'])) {
                $robots['max-image-preview'] = 'large';
            }
        }
        if (!empty($robots['nofollow'])) {
            unset($robots['follow']);
        } elseif (!isset($robots['follow']) && empty($robots['noindex'])) {
            $robots['follow'] = true;
        }
        return $robots;
    }

    public function add_robots_rewrite_rule(): void {
        add_rewrite_rule('robots\.txt$', 'index.php?robots=1', 'top');
    }

    public function enqueue_assets(): void {
        global $wp_query;
        $options = get_option('vonseowp_settings', array());
        $post = get_post();
        $automatic = is_singular() && $post && !empty($options['enable_toc'])
            && get_post_meta($post->ID, '_vonseowp_disable_toc', true) !== '1';
        $manual = $post && has_shortcode($post->post_content, 'vonseo_toc');
        if (!$manual && isset($wp_query->posts)) {
            foreach ($wp_query->posts as $query_post) {
                if (isset($query_post->post_content) && has_shortcode($query_post->post_content, 'vonseo_toc')) {
                    $manual = true;
                    break;
                }
            }
        }
        if (!$automatic && !$manual) return;
        wp_enqueue_style('vonseo-public-css', VONSEOWP_URL . 'public/css/vonseowp-public.css', array(), VONSEOWP_VERSION);
        wp_enqueue_script('vonseo-public-js', VONSEOWP_URL . 'public/js/vonseowp-public.js', array(), VONSEOWP_VERSION, true);
    }

    private function get_canonical_url(): string {
        global $post;

        if (is_singular() && $post) {
            return (string) get_permalink($post->ID);
        }

        if (is_front_page()) {
            return home_url('/');
        }

        if (is_home()) {
            $posts_page_id = (int) get_option('page_for_posts');
            if ($posts_page_id > 0) {
                return (string) get_permalink($posts_page_id);
            }

            return home_url('/');
        }

        $paged = max(1, (int) get_query_var('paged'));
        return (string) get_pagenum_link($paged);
    }

    public function filter_document_title(string $title): string {
        global $post;
        $options = get_option('vonseowp_settings', array());
        
        // Homepage Override
        if (is_front_page() || is_home()) {
            if (!empty($options['home_title'])) {
                return $options['home_title'];
            }
        }

        // Single Post/Page Override
        if ($this->can_output_post_metadata($post)) {
            $custom_title = get_post_meta($post->ID, '_vonseowp_title', true);
            if (!empty($custom_title)) {
                return $custom_title;
            }
        }

        return $title;
    }

    /** @param WP_Post|null $post */
    private function can_output_post_metadata($post): bool {
        // Direct post/meta reads must respect WordPress's current password-cookie state.
        return is_singular() && $post && !post_password_required($post);
    }

    public function output_meta_tags() {
        if (isset($GLOBALS['vonseowp_meta_tags_done'])) {
            return;
        }
        $GLOBALS['vonseowp_meta_tags_done'] = true;

        global $post;
        $options = get_option('vonseowp_settings', array());
        
        // Defaults
        // Title handled by filter_document_title
        $home_desc = isset($options['home_desc']) ? trim((string) $options['home_desc']) : '';
        $desc = $home_desc !== '' ? $home_desc : get_bloginfo('description');
        $keywords = $options['keywords'] ?? '';
        $image = $options['default_image'] ?? '';
        $image_is_site_icon = false;
        $url = $this->get_canonical_url();
        $type = 'website';

        $social_enabled = !isset($options['enable_og']) || (int) $options['enable_og'] === 1;

        // Per-post overrides
        if ($this->can_output_post_metadata($post)) {
            // $custom_title handled by filter
            $custom_desc = get_post_meta($post->ID, '_vonseowp_description', true);
            $custom_keywords = get_post_meta($post->ID, '_vonseowp_keywords', true);
            $custom_image = get_post_meta($post->ID, '_vonseowp_image', true);

            if ($custom_desc) {
                $desc = $custom_desc;
            } elseif (has_excerpt($post->ID)) {
                $desc = get_the_excerpt($post->ID);
            } else {
                $desc = self::get_content_fallback_description((string) $post->post_content);
            }
            if ($custom_keywords) $keywords = $custom_keywords;
            if ($custom_image) {
                $image = $custom_image;
            } elseif (has_post_thumbnail($post->ID)) {
                $image = get_the_post_thumbnail_url($post->ID, 'large');
            }

            $type = 'article';
            $url = get_permalink($post->ID);
        }

        // Social Overrides
        $social_title = '';
        $social_desc = '';
        if ($this->can_output_post_metadata($post)) {
            $social_title = get_post_meta($post->ID, '_vonseowp_social_title', true);
            $social_desc = get_post_meta($post->ID, '_vonseowp_social_desc', true);
        }

        // Sanitize
        $title = wp_get_document_title(); // Get the filtered title for OG tags
        $desc = trim(wp_strip_all_tags($desc));
        if (!$image && has_site_icon()) {
            $image = get_site_icon_url(512);
            $image_is_site_icon = true;
        }
        
        $parsed_path = wp_parse_url($url, PHP_URL_PATH);
        $home_path = wp_parse_url(home_url('/'), PHP_URL_PATH);
        if ($parsed_path && $home_path && '/' !== $home_path && strpos($parsed_path, $home_path) === 0) {
            $parsed_path = substr($parsed_path, strlen($home_path));
        }
        $canonical_path = $parsed_path ? ltrim($parsed_path, '/') : '';
        $canonical = !empty($options['canonical_host']) ? trailingslashit($options['canonical_host']) . $canonical_path : $url;

        echo '<!-- Optimized by VonSEO -->' . "\n";

        // Basic Meta
        if ($desc !== '') echo '<meta name="description" content="' . esc_attr($desc) . '" />' . "\n";
        if ($keywords) echo '<meta name="keywords" content="' . esc_attr($keywords) . '" />' . "\n";
        echo '<link rel="canonical" href="' . esc_url($canonical) . '" />' . "\n";
        
        if (!empty($options['google_verify'])) echo '<meta name="google-site-verification" content="' . esc_attr($options['google_verify']) . '" />' . "\n";
        if (!empty($options['bing_verify'])) echo '<meta name="msvalidate.01" content="' . esc_attr($options['bing_verify']) . '" />' . "\n";
        
        if ($social_enabled) {
            // Open Graph
            echo '<meta property="og:locale" content="' . esc_attr(get_locale()) . '" />' . "\n";
            echo '<meta property="og:type" content="' . esc_attr($type) . '" />' . "\n";
            echo '<meta property="og:title" content="' . esc_attr($social_title ?: $title) . '" />' . "\n";
            $og_desc = trim(wp_strip_all_tags($social_desc ?: $desc));
            if ($og_desc !== '') echo '<meta property="og:description" content="' . esc_attr($og_desc) . '" />' . "\n";
            echo '<meta property="og:url" content="' . esc_url($canonical) . '" />' . "\n";
            echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '" />' . "\n";
            if ($image) echo '<meta property="og:image" content="' . esc_url($image) . '" />' . "\n";

            if (is_singular() && $post) {
                echo '<meta property="article:published_time" content="' . esc_attr(get_the_date('c', $post)) . '" />' . "\n";
                echo '<meta property="article:modified_time" content="' . esc_attr(get_the_modified_date('c', $post)) . '" />' . "\n";
            }

            // Twitter Cards
            echo '<meta name="twitter:card" content="' . ($image && !$image_is_site_icon ? 'summary_large_image' : 'summary') . '" />' . "\n";
            echo '<meta name="twitter:title" content="' . esc_attr($social_title ?: $title) . '" />' . "\n";
            if ($og_desc !== '') echo '<meta name="twitter:description" content="' . esc_attr($og_desc) . '" />' . "\n";
            if ($image) echo '<meta name="twitter:image" content="' . esc_url($image) . '" />' . "\n";
            if (!empty($options['twitter_username'])) {
                $tw = ltrim($options['twitter_username'], '@');
                echo '<meta name="twitter:site" content="@' . esc_attr($tw) . '" />' . "\n";
                echo '<meta name="twitter:creator" content="@' . esc_attr($tw) . '" />' . "\n";
            }
        }
        // LinkedIn (Uses OG, but specific tags help)
        $author = is_singular() && $post ? get_the_author_meta('display_name', (int) $post->post_author) : get_bloginfo('name');
        if ($author) echo '<meta name="author" content="' . esc_attr($author) . '" />' . "\n";
        echo '<!-- /VonSEO -->' . "\n";
    }

    public function output_json_ld() {
        if (isset($GLOBALS['vonseowp_json_ld_done'])) {
            return;
        }
        $GLOBALS['vonseowp_json_ld_done'] = true;

        global $post;
        $options = get_option('vonseowp_settings', array());
        $schema_type = $options['schema_org_type'] ?? 'Organization';
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@graph' => array()
        );

        // 1. Organization / Person
        $entity = array(
            '@type' => $schema_type,
            '@id' => home_url('/#' . strtolower($schema_type)),
            'name' => $options['home_title'] ?? get_bloginfo('name'),
            'url' => home_url(),
        );

        // Local SEO Data
        if (!empty($options['business_name'])) {
            $entity['name'] = $options['business_name'];
        }
        
        if (!empty($options['address'])) {
            $entity['address'] = array(
                '@type' => 'PostalAddress',
                'streetAddress' => $options['address'],
                'addressLocality' => $options['city'] ?? '',
                'addressRegion' => $options['state'] ?? '',
                'postalCode' => $options['zip'] ?? '',
                'addressCountry' => $options['country'] ?? ''
            );
        }

        if (!empty($options['phone'])) {
            $entity['telephone'] = $options['phone'];
            $entity['contactPoint'] = array(
                '@type' => 'ContactPoint',
                'telephone' => $options['phone'],
                'contactType' => 'customer service'
            );
        }

        if (has_site_icon()) {
            $entity['logo'] = array(
                '@type' => 'ImageObject',
                'url' => get_site_icon_url(512)
            );
        }
        $schema['@graph'][] = $entity;

        // 2. WebSite with SearchAction
        $schema['@graph'][] = array(
            '@type' => 'WebSite',
            '@id' => home_url('/#website'),
            'url' => home_url(),
            'name' => get_bloginfo('name'),
            'publisher' => array('@id' => home_url('/#' . strtolower($schema_type))),
            'potentialAction' => array(
                '@type' => 'SearchAction',
                'target' => array(
                    '@type' => 'EntryPoint',
                    'urlTemplate' => home_url('/?s={search_term_string}')
                ),
                'query-input' => 'required name=search_term_string'
            )
        );

        // 3. BlogPosting (for singular posts/pages)
        if ($this->can_output_post_metadata($post)) {
            $custom_desc = get_post_meta($post->ID, '_vonseowp_description', true);
            $desc = $custom_desc ?: self::get_content_fallback_description((string) $post->post_content);
            $image_url = '';
            
            $custom_image = get_post_meta($post->ID, '_vonseowp_image', true);
            if ($custom_image) {
                $image_url = $custom_image;
            } elseif (has_post_thumbnail($post->ID)) {
                $image_url = get_the_post_thumbnail_url($post->ID, 'large');
            }

            // Custom Schema Type
            $custom_type = get_post_meta($post->ID, '_vonseowp_schema_type', true);
            $type = !empty($custom_type) ? $custom_type : (is_page() ? 'WebPage' : 'BlogPosting');
            
            // Custom ID anchor
            $anchor = is_page() ? 'webpage' : 'article';
            if ($type === 'Product') $anchor = 'product';
            if ($type === 'Review') $anchor = 'review';
            if ($type === 'Service') $anchor = 'service';

            $article = array(
                '@type' => $type,
                '@id' => get_permalink() . '#' . $anchor,
                'headline' => wp_strip_all_tags(get_the_title()),
                'description' => esc_attr(wp_strip_all_tags($desc)),
                'datePublished' => get_the_date('c'),
                'dateModified' => get_the_modified_date('c'),
                'author' => array(
                    '@type' => 'Person',
                    'name' => wp_strip_all_tags(get_the_author()),
                    'url' => get_author_posts_url(get_the_author_meta('ID'))
                ),
                'publisher' => array('@id' => home_url('/#' . strtolower($schema_type))),
                'mainEntityOfPage' => array(
                    '@type' => 'WebPage',
                    '@id' => get_permalink()
                )
            );

            // Add Rating for Reviews/Products
            $rating = get_post_meta($post->ID, '_vonseowp_rating', true);
            if (is_numeric($rating) && $rating > 0) {
                $rating_count = get_post_meta($post->ID, '_vonseowp_rating_count', true);
                if (!$rating_count || !is_numeric($rating_count)) {
                    $rating_count = 1; // Fallback
                }
                
                $article['aggregateRating'] = array(
                    '@type' => 'AggregateRating',
                    'ratingValue' => $rating,
                    'bestRating' => '5',
                    'worstRating' => '1',
                    'ratingCount' => (int) $rating_count
                );
            }

            if ($image_url) {
                $article['image'] = array(
                    '@type' => 'ImageObject',
                    'url' => $image_url
                );
            }

            $schema['@graph'][] = $article;

            // 3.1 FAQ Schema
            $faqs = get_post_meta($post->ID, '_vonseowp_faq', true);
            if (!empty($faqs) && is_array($faqs)) {
                $faq_schema = array(
                    '@type' => 'FAQPage',
                    'mainEntity' => array()
                );
                foreach ($faqs as $faq) {
                    $faq_schema['mainEntity'][] = array(
                        '@type' => 'Question',
                        'name' => wp_strip_all_tags($faq['q']),
                        'acceptedAnswer' => array(
                            '@type' => 'Answer',
                            'text' => wp_strip_all_tags($faq['a'])
                        )
                    );
                }
                $schema['@graph'][] = $faq_schema;
            }

            // 3.2 Video Schema
            $video = get_post_meta($post->ID, '_vonseowp_video', true);
            if (!empty($video) && is_array($video) && !empty($video['url'])) {
                $video_schema = array(
                    '@type' => 'VideoObject',
                    'name' => $video['name'] ?: get_the_title(),
                    'description' => $video['desc'] ?: wp_trim_words($post->post_content, 30),
                    'thumbnailUrl' => $image_url ?: home_url('/favicon.ico'),
                    'uploadDate' => get_the_date('c'),
                    'contentUrl' => $video['url'],
                    'embedUrl' => $video['url']
                );
                $schema['@graph'][] = $video_schema;
            }
        }

        // 4. Breadcrumbs
        $breadcrumbs = array(
            '@type' => 'BreadcrumbList',
            'itemListElement' => array(
                array(
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => __('Home', 'vonseo'),
                    'item' => home_url()
                )
            )
        );

        if (is_singular() && $post) {
            // Add category for posts
            if (is_single()) {
                $categories = get_the_category($post->ID);
                if (!empty($categories)) {
                    $breadcrumbs['itemListElement'][] = array(
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => $categories[0]->name,
                        'item' => get_category_link($categories[0]->term_id)
                    );
                    $breadcrumbs['itemListElement'][] = array(
                        '@type' => 'ListItem',
                        'position' => 3,
                        'name' => get_the_title(),
                        'item' => get_permalink()
                    );
                }
            } else {
                $breadcrumbs['itemListElement'][] = array(
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => get_the_title(),
                    'item' => get_permalink()
                );
            }
        }

        $schema['@graph'][] = $breadcrumbs;

        echo '<script type="application/ld+json" class="vonseowp-schema">' . "\n";
        echo wp_json_encode($schema);
        echo "\n</script>\n";
    }

    /**
     * Handle Robots.txt output
     */
    public function handle_robots_txt(string $output, bool $public): string {
        if (!$public) {
            return $output;
        }

        $options = get_option('vonseowp_settings', array());
        if (!empty($options['robots_txt'])) {
            return $options['robots_txt'];
        }

        $sitemap_enabled = !isset($options['enable_sitemap']) || (int) $options['enable_sitemap'] === 1;
        if ($sitemap_enabled) {
            return self::get_default_robots_txt();
        }

        return $output;
    }

    public static function get_default_robots_txt(): string {
        $home_path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
        $prefix = '/' === $home_path ? '' : untrailingslashit($home_path);

        return implode("\n", array(
            'User-agent: *',
            'Disallow: ' . $prefix . '/wp-admin/',
            'Allow: ' . $prefix . '/wp-admin/admin-ajax.php',
            'Disallow: ' . $prefix . '/wp-login.php',
            'Disallow: ' . $prefix . '/wp-register.php',
            'Disallow: ' . $prefix . '/?s=',
            'Disallow: ' . $prefix . '/search/',
            'Disallow: ' . $prefix . '/feed/',
            'Disallow: ' . $prefix . '/comments/feed/',
            'Disallow: ' . $prefix . '/xmlrpc.php',
            '',
            'Sitemap: ' . home_url('/sitemap.xml'),
        ));
    }

    public static function get_content_fallback_description(string $content): string {
        $text = wp_strip_all_tags(strip_shortcodes($content));
        $charset = (string) get_bloginfo('charset');
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, $charset !== '' ? $charset : 'UTF-8');
        $normalized = preg_replace('/\s+/u', ' ', $text);
        $text = trim(is_string($normalized) ? $normalized : $text);
        $max_length = 160;
        $length = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);

        if ($length <= $max_length) {
            return $text;
        }

        $slice_length = $max_length - 3;
        $slice = function_exists('mb_substr')
            ? mb_substr($text, 0, $slice_length, 'UTF-8')
            : substr($text, 0, $slice_length);
        $last_space = function_exists('mb_strrpos')
            ? mb_strrpos($slice, ' ', 0, 'UTF-8')
            : strrpos($slice, ' ');

        if (false !== $last_space && $last_space >= (int) floor($max_length * 0.6)) {
            $slice = function_exists('mb_substr')
                ? mb_substr($slice, 0, $last_space, 'UTF-8')
                : substr($slice, 0, $last_space);
        }

        return rtrim($slice) . '...';
    }
}
