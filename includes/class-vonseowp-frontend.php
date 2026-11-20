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
        add_filter('wp_title', array($this, 'filter_legacy_title'), 15, 3);
        add_filter('document_title_separator', array($this, 'filter_title_separator'), 15);
        // The WordPress "AI" plugin prints its own description tag unless it recognises an SEO plugin.
        add_filter('wpai_meta_description', array($this, 'suppress_external_description'), 99);
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
        $taxonomy = $this->get_taxonomy_metadata();
        $noindex = !get_option('blog_public') || is_search() || is_404()
            || ($taxonomy['noindex'] ?? '') === '1'
            || (is_singular() && $post && VonSEOWP_Content_Types::is_enabled((string) $post->post_type)
                && get_post_meta($post->ID, '_vonseowp_noindex', true) === '1');
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

        $paged = max(1, (int) get_query_var('paged'));
        if ($paged > 1) {
            return (string) get_pagenum_link($paged, false);
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

        return (string) get_pagenum_link($paged, false);
    }

    public function filter_document_title(string $title): string {
        global $post;
        $options = get_option('vonseowp_settings', array());

        $taxonomy = $this->get_taxonomy_metadata();
        if ($taxonomy && $taxonomy['title'] !== '') {
            $paged = max(1, (int) get_query_var('paged'));
            /* translators: %d: archive page number. */
            return $taxonomy['title'] . ($paged > 1 ? ' - ' . sprintf(__('Page %d', 'vonseo'), $paged) : '');
        }
        
        // A separate posts page (static front page setup) is its own page: use its SEO title, else the native title.
        if ($this->is_separate_posts_page()) {
            $posts_title = $this->get_posts_page_meta('_vonseowp_title');
            return $posts_title !== '' ? $posts_title : $title;
        }

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

    /**
     * Apply the saved title separator to modern document-title assembly. The dash is WordPress's own
     * default (and the setting's default), so it leaves any theme-provided separator untouched.
     */
    public function filter_title_separator(string $separator): string {
        $options = get_option('vonseowp_settings', array());
        $chosen = is_array($options) && isset($options['separator']) ? $options['separator'] : '';
        if (!is_string($chosen) || $chosen === '-' || !class_exists('VonSEOWP_Admin')
            || !in_array($chosen, VonSEOWP_Admin::title_separators(), true)) {
            return $separator;
        }
        return $chosen;
    }

    public function filter_legacy_title(string $title, string $separator, string $separator_location): string {
        $taxonomy = $this->get_taxonomy_metadata();
        // Legacy themes such as Twenty Eleven append their own pagination suffix.
        $custom_title = $taxonomy && $taxonomy['title'] !== '' ? $taxonomy['title'] : $this->filter_document_title('');
        if ($custom_title === '') return $title;

        // Legacy themes may append the site name themselves; keep core separator placement.
        $custom_title = esc_html($custom_title);
        if ((is_front_page() || is_home()) && $custom_title === esc_html(get_bloginfo('name'))) {
            return $title;
        }
        if ($separator === '') return $custom_title;
        $separator = ' ' . esc_html($separator) . ' ';
        return $separator_location === 'right' ? $custom_title . $separator : $separator . $custom_title;
    }

    private function is_separate_posts_page(): bool {
        return is_home() && !is_front_page() && (int) get_option('page_for_posts') > 0;
    }

    /** Saved SEO field of the separate posts page, respecting content-type selection and password state. */
    private function get_posts_page_meta(string $key): string {
        $page = get_post((int) get_option('page_for_posts'));
        if (!$page || $page->post_status !== 'publish'
            || !VonSEOWP_Content_Types::is_enabled((string) $page->post_type) || post_password_required($page)) {
            return '';
        }
        $value = get_post_meta($page->ID, $key, true);
        return is_string($value) ? trim($value) : '';
    }

    /**
     * VonSEO owns the description tag, so the AI plugin's duplicate is suppressed; its generated text is
     * still used as a fallback by get_external_description().
     *
     * @param mixed $description
     */
    public function suppress_external_description($description): string {
        return '';
    }

    /** Description generated by the WordPress "AI" plugin, used when no VonSEO description is saved. */
    public static function get_external_description(int $post_id): string {
        $value = get_post_meta($post_id, 'wpai_meta_description', true);
        return is_string($value) ? trim(wp_strip_all_tags($value)) : '';
    }

    /** @param WP_Post|null $post */
    private function can_output_post_metadata($post): bool {
        // Direct post/meta reads must respect WordPress's current password-cookie state.
        return is_singular() && $post
            && VonSEOWP_Content_Types::is_enabled((string) $post->post_type)
            && !post_password_required($post);
    }

    private function get_taxonomy_metadata(): array {
        if (!class_exists('VonSEOWP_Taxonomies')) return array();
        $term = VonSEOWP_Taxonomies::get_current();
        if (!$term) return array();
        $metadata = VonSEOWP_Taxonomies::get_metadata($term);
        $metadata['description'] = $metadata['description'] !== '' ? $metadata['description']
            : self::get_content_fallback_description((string) $term->description);
        $metadata['url'] = get_term_link($term);
        if (is_wp_error($metadata['url'])) $metadata['url'] = '';
        return $metadata;
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
            $custom_desc = get_post_meta($post->ID, '_vonseowp_description', true) ?: self::get_external_description($post->ID);
            $custom_keywords = get_post_meta($post->ID, '_vonseowp_keywords', true);
            $custom_image = self::http_url(get_post_meta($post->ID, '_vonseowp_image', true));

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

        if ($this->is_separate_posts_page()) {
            $posts_desc = $this->get_posts_page_meta('_vonseowp_description');
            if ($posts_desc !== '') $desc = $posts_desc;
            $social_title = $this->get_posts_page_meta('_vonseowp_social_title');
            $social_desc = $this->get_posts_page_meta('_vonseowp_social_desc');
            $posts_image = self::http_url($this->get_posts_page_meta('_vonseowp_image'));
            if ($posts_image !== '') $image = $posts_image;
        }

        $taxonomy = $this->get_taxonomy_metadata();
        if ($taxonomy) {
            $desc = $taxonomy['description'];
            $keywords = '';
            $social_desc = $taxonomy['social_desc'];
            if ((int) get_query_var('paged') < 2 && $taxonomy['url'] !== '') $url = $taxonomy['url'];
        }

        // Sanitize
        // Social cards carry the brand in og:site_name. For content, use its SEO title or post title instead of
        // core's assembled document title, which can differ from a legacy theme's <title> (separator, and
        // capital_P_dangit rewriting the site name).
        $title = '';
        if ($this->can_output_post_metadata($post)) {
            $title = trim((string) get_post_meta($post->ID, '_vonseowp_title', true));
            if ($title === '') $title = trim(wp_strip_all_tags((string) get_the_title($post->ID)));
        }
        if ($title === '') $title = wp_get_document_title();
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
        $canonical_query = (string) wp_parse_url($url, PHP_URL_QUERY);
        $canonical = !empty($options['canonical_host'])
            ? trailingslashit($options['canonical_host']) . $canonical_path . ($canonical_query !== '' ? '?' . $canonical_query : '')
            : $url;
        // A first-page explicit override must never collapse paginated archives onto page one.
        if ($taxonomy && (int) get_query_var('paged') < 2 && $taxonomy['canonical'] !== '') {
            $canonical = $taxonomy['canonical'];
        }

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
        $site_name = self::decode_site_text((string) get_bloginfo('name'));
        $entity_name = isset($options['home_title']) && is_string($options['home_title']) ? trim($options['home_title']) : '';
        $entity = array(
            '@type' => $schema_type,
            '@id' => home_url('/#' . strtolower($schema_type)),
            // A cleared Site Title field is stored as '', which must still fall back to the site name.
            'name' => $entity_name !== '' ? self::decode_site_text($entity_name) : $site_name,
            'url' => home_url(),
        );
        $same_as = array();
        if (!empty($options['facebook_url']) && is_string($options['facebook_url'])
            && preg_match('#^https?://#i', $options['facebook_url'])) {
            $same_as[] = $options['facebook_url'];
        }
        $twitter = isset($options['twitter_username']) && is_string($options['twitter_username'])
            ? ltrim(trim($options['twitter_username']), '@') : '';
        if (preg_match('/^[A-Za-z0-9_]{1,15}$/', $twitter)) {
            $same_as[] = 'https://x.com/' . $twitter;
        }
        if ($same_as) {
            $entity['sameAs'] = $same_as;
        }

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
            'name' => $site_name,
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
            $custom_desc = get_post_meta($post->ID, '_vonseowp_description', true) ?: self::get_external_description($post->ID);
            $desc = $custom_desc ?: self::get_content_fallback_description((string) $post->post_content);
            $image_url = '';
            
            $custom_image = self::http_url(get_post_meta($post->ID, '_vonseowp_image', true));
            if ($custom_image) {
                $image_url = $custom_image;
            } elseif (has_post_thumbnail($post->ID)) {
                $image_url = get_the_post_thumbnail_url($post->ID, 'large');
            }

            $schema['@graph'][] = $this->build_content_schema(
                $post, home_url('/#' . strtolower($schema_type)), (string) $desc, (string) $image_url
            );

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
            if (!empty($video) && is_array($video) && !empty($video['url']) && is_string($video['url'])) {
                $video_schema = self::build_video_schema($video, $post, (string) $image_url);
                if ($video_schema) $schema['@graph'][] = $video_schema;
            }
        }

        // 4. Breadcrumbs: same trail (and vonseowp_breadcrumb_items filter) as the visible breadcrumbs.
        // Noindex search and 404 responses get the visible trail only, not structured data.
        $breadcrumbs = self::build_breadcrumb_schema(
            class_exists('VonSEOWP_Breadcrumbs') && !is_404() && !is_search() ? VonSEOWP_Breadcrumbs::get_items() : array()
        );
        if ($breadcrumbs) {
            $schema['@graph'][] = $breadcrumbs;
        }

        echo '<script type="application/ld+json" class="vonseowp-schema">' . "\n";
        // Hex-escape < > & ' so no stored value can ever close the script element.
        echo wp_json_encode($schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        echo "\n</script>\n";
    }

    /**
     * A one-item trail (e.g. the front page) is not a useful BreadcrumbList, so it is omitted.
     *
     * @param array<int, array{label?: mixed, url?: mixed}> $items
     */
    public static function build_breadcrumb_schema(array $items): array {
        $elements = array();
        foreach ($items as $item) {
            $label = isset($item['label']) && is_string($item['label'])
                ? trim(wp_strip_all_tags(self::decode_site_text($item['label']))) : '';
            if ($label === '') continue;
            $element = array('@type' => 'ListItem', 'position' => count($elements) + 1, 'name' => $label);
            if (isset($item['url']) && is_string($item['url']) && $item['url'] !== '') {
                $element['item'] = $item['url'];
            }
            $elements[] = $element;
        }
        // Every ListItem except the current page needs a URL.
        foreach (array_slice($elements, 0, -1) as $element) {
            if (!isset($element['item'])) return array();
        }
        return count($elements) < 2 ? array() : array('@type' => 'BreadcrumbList', 'itemListElement' => $elements);
    }

    /**
     * VideoObject (SCH-02): contentUrl is the media file itself, embedUrl a player page. A direct media
     * file uses contentUrl; YouTube/Vimeo and other pages use embedUrl.
     *
     * @param array<string, mixed> $video
     */
    public static function build_video_schema(array $video, object $post, string $image_url): array {
        $url = trim((string) ($video['url'] ?? ''));
        $parts = wp_parse_url($url);
        if (!is_array($parts) || empty($parts['host']) || !in_array(strtolower($parts['scheme'] ?? ''), array('http', 'https'), true)) {
            return array();
        }
        $host = strtolower((string) preg_replace('/^(www\.|m\.)/', '', $parts['host']));
        $path = (string) ($parts['path'] ?? '');
        $youtube_id = '';
        if ($host === 'youtu.be') {
            $youtube_id = trim($path, '/');
        } elseif (in_array($host, array('youtube.com', 'youtube-nocookie.com'), true)) {
            $query = array();
            wp_parse_str((string) ($parts['query'] ?? ''), $query);
            $youtube_id = isset($query['v']) && is_string($query['v']) ? $query['v']
                : (preg_match('#^/(?:embed|shorts|live)/([^/?]+)#', $path, $match) ? $match[1] : '');
        }
        $youtube_id = preg_match('/^[A-Za-z0-9_-]{6,20}$/', $youtube_id) ? $youtube_id : '';
        $is_file = (bool) preg_match('/\.(mp4|m4v|webm|ogv|ogg|mov|mpe?g)$/i', $path);

        $thumbnail = $image_url;
        if ($thumbnail === '' && $youtube_id !== '') $thumbnail = 'https://i.ytimg.com/vi/' . $youtube_id . '/hqdefault.jpg';
        if ($thumbnail === '' && has_site_icon()) $thumbnail = (string) get_site_icon_url(512);

        $name = isset($video['name']) && is_string($video['name']) && trim($video['name']) !== ''
            ? $video['name'] : wp_strip_all_tags(self::decode_site_text((string) get_the_title($post->ID)));
        $description = isset($video['desc']) && is_string($video['desc']) && trim($video['desc']) !== ''
            ? $video['desc'] : self::get_content_fallback_description((string) $post->post_content);

        $node = array(
            '@type' => 'VideoObject',
            'name' => wp_strip_all_tags($name),
            'description' => wp_strip_all_tags($description !== '' ? $description : $name),
            'uploadDate' => get_the_date('c', $post),
        );
        if ($thumbnail !== '') $node['thumbnailUrl'] = $thumbnail;
        if ($is_file) {
            $node['contentUrl'] = $url;
        } else {
            $node['embedUrl'] = $youtube_id !== '' ? 'https://www.youtube.com/embed/' . $youtube_id : $url;
        }
        return $node;
    }

    /**
     * Values saved before 2.5.3 were text-sanitized only; publish a stored URL only when it is http(s).
     *
     * @param mixed $value
     */
    private static function http_url($value): string {
        $value = is_string($value) ? trim($value) : '';
        return preg_match('#^https?://[^\s"<>]+$#i', $value) ? $value : '';
    }

    private static function decode_site_text(string $text): string {
        // Core stores titles/site names HTML-escaped; JSON-LD text must contain the literal characters.
        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function build_content_schema(object $post, string $publisher_id, string $description, string $image_url): array {
        $custom_type = get_post_meta($post->ID, '_vonseowp_schema_type', true);
        $types = array('Article', 'NewsArticle', 'BlogPosting', 'WebPage', 'Product', 'Review', 'Service');
        $type = in_array($custom_type, $types, true) ? $custom_type : (is_page() ? 'WebPage' : 'BlogPosting');
        $name = wp_strip_all_tags(get_the_title($post->ID));
        // wp_head can run before the Loop has populated its author globals.
        $author_id = (int) ($post->post_author ?? 0);
        $author_name = $author_id > 0 ? wp_strip_all_tags((string) get_the_author_meta('display_name', $author_id)) : '';
        $rating = get_post_meta($post->ID, '_vonseowp_rating', true);
        $rating_valid = is_numeric($rating) && (float) $rating >= 1 && (float) $rating <= 5;
        $count = get_post_meta($post->ID, '_vonseowp_rating_count', true);
        $count_valid = (is_int($count) || (is_string($count) && ctype_digit($count))) && (int) $count > 0;
        $reviewed_product = get_post_meta($post->ID, '_vonseowp_reviewed_product', true);
        $reviewed_product = is_string($reviewed_product) ? trim(wp_strip_all_tags($reviewed_product)) : '';

        // Keep saved choices/data, but do not publish incomplete rated objects or invent missing fields.
        if (($type === 'Product' && (!$rating_valid || !$count_valid || trim($name) === ''))
            || ($type === 'Review' && (!$rating_valid || $reviewed_product === '' || trim($author_name) === ''))) {
            $type = 'WebPage';
        }
        $anchors = array('Product' => 'product', 'Review' => 'review', 'Service' => 'service', 'WebPage' => 'webpage');
        $url = get_permalink($post->ID);
        $node = array(
            '@type' => $type,
            '@id' => $url . '#' . ($anchors[$type] ?? 'article'),
            'description' => wp_strip_all_tags($description),
            'url' => $url,
            'mainEntityOfPage' => array('@type' => 'WebPage', '@id' => $url)
        );
        if ($type === 'Product') {
            $node['name'] = $name;
            $node['aggregateRating'] = array(
                '@type' => 'AggregateRating', 'ratingValue' => (float) $rating,
                'bestRating' => 5, 'worstRating' => 1, 'ratingCount' => (int) $count
            );
        } elseif ($type === 'Service') {
            $node['name'] = $name;
            $node['provider'] = array('@id' => $publisher_id);
        } else {
            $node[$type === 'Review' ? 'name' : 'headline'] = $name;
            $node['datePublished'] = get_the_date('c');
            $node['dateModified'] = get_the_modified_date('c');
            if (trim($author_name) !== '') {
                $node['author'] = array(
                    '@type' => 'Person', 'name' => $author_name,
                    'url' => get_author_posts_url($author_id)
                );
            }
            $node['publisher'] = array('@id' => $publisher_id);
            if ($type === 'Review') {
                $node['itemReviewed'] = array('@type' => 'Product', 'name' => $reviewed_product);
                $node['reviewRating'] = array(
                    '@type' => 'Rating', 'ratingValue' => (float) $rating, 'bestRating' => 5, 'worstRating' => 1
                );
            }
        }
        if ($image_url !== '') $node['image'] = array('@type' => 'ImageObject', 'url' => $image_url);
        return $node;
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
