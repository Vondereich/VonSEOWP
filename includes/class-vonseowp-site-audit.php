<?php
/**
 * Manual, local-only site audit for published posts and pages.
 */
if (!defined('ABSPATH')) exit;

if (false) {
    require_once dirname(__DIR__) . '/_wp_stubs.php';
}

class VonSEOWP_Site_Audit {

    private const TRANSIENT_KEY = 'vonseowp_site_audit_results';
    private const MAX_POSTS = 25;

    /** @var string */
    private $page_hook = '';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_post_vonseowp_run_site_audit', array($this, 'handle_run_audit'));
    }

    public function add_menu(): void {
        $this->page_hook = add_submenu_page(
            'vonseo',
            __('Site SEO Audit', 'vonseo'),
            __('SEO Audit', 'vonseo'),
            'manage_options',
            'vonseo-audit',
            array($this, 'render_page')
        );
    }

    public function enqueue_assets(string $hook): void {
        if ($hook !== $this->page_hook) {
            return;
        }

        wp_enqueue_style('vonseo-admin-css', VONSEOWP_URL . 'admin/css/vonseowp-admin.css', array(), VONSEOWP_VERSION);
    }

    public function handle_run_audit(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to run the VonSEO site audit.', 'vonseo'), '', array('response' => 403));
        }

        check_admin_referer('vonseowp_run_site_audit');

        $requested_page = isset($_POST['vonseowp_audit_page'])
            ? absint(wp_unslash($_POST['vonseowp_audit_page']))
            : 1;
        $results = $this->run_audit(max(1, $requested_page));

        set_transient(self::TRANSIENT_KEY, $results, 12 * HOUR_IN_SECONDS);

        $redirect_url = add_query_arg(
            array(
                'page'                  => 'vonseo-audit',
                'vonseowp_audit'        => 'complete',
                'vonseowp_audit_page'   => (int) $results['page'],
            ),
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
    }

    public function run_audit(int $page = 1): array {
        $total = $this->get_published_total();
        $pages = max(1, (int) ceil($total / self::MAX_POSTS));
        $page = max(1, min($page, $pages));

        $post_ids = get_posts(array(
            'post_type'           => array('post', 'page'),
            'post_status'         => 'publish',
            'posts_per_page'      => self::MAX_POSTS,
            'paged'                => $page,
            'orderby'             => 'modified',
            'order'               => 'DESC',
            'fields'              => 'ids',
            'no_found_rows'       => true,
            'ignore_sticky_posts' => true,
        ));

        if (!is_array($post_ids)) {
            $post_ids = array();
        }

        $scanned = count($post_ids);
        $range_start = $scanned > 0 ? (($page - 1) * self::MAX_POSTS) + 1 : 0;
        $range_end = $scanned > 0 ? min($total, $range_start + $scanned - 1) : 0;

        $issue_counts = array_fill_keys(array_keys($this->get_issue_labels()), 0);
        $items = array();

        foreach ($post_ids as $post_id) {
            $post_id = (int) $post_id;
            $issues = $this->analyze_post($post_id);

            foreach ($issues as $issue) {
                if (isset($issue_counts[$issue])) {
                    $issue_counts[$issue]++;
                }
            }

            if (empty($issues)) {
                continue;
            }

            $post = get_post($post_id);
            if (!$post || !is_object($post)) {
                continue;
            }

            $items[] = array(
                'id'        => $post_id,
                'title'     => isset($post->post_title) ? (string) $post->post_title : '',
                'post_type' => isset($post->post_type) ? (string) $post->post_type : 'post',
                'issues'    => $issues,
            );
        }

        return array(
            'version'      => VONSEOWP_VERSION,
            'generated_at' => time(),
            'scanned'      => $scanned,
            'total'        => $total,
            'limit'        => self::MAX_POSTS,
            'page'         => $page,
            'pages'        => $pages,
            'range_start'  => $range_start,
            'range_end'    => $range_end,
            'technical'    => $this->get_technical_checks(),
            'issue_counts' => $issue_counts,
            'items'        => $items,
        );
    }

    public function analyze_post(int $post_id): array {
        $post = get_post($post_id);
        if (!$post || !is_object($post)) {
            return array();
        }

        $custom_title = trim((string) get_post_meta($post_id, '_vonseowp_title', true));
        $custom_description = trim((string) get_post_meta($post_id, '_vonseowp_description', true));
        $keyword = $this->get_focus_keyword((string) get_post_meta($post_id, '_vonseowp_keywords', true));
        $content = isset($post->post_content) ? (string) $post->post_content : '';
        $post_title = isset($post->post_title) ? trim((string) $post->post_title) : '';
        $effective_title = $custom_title !== '' ? $custom_title : $post_title;
        $effective_description = $this->get_effective_description($post, $custom_description);
        $issues = array();

        if ($custom_title === '') {
            $issues[] = 'title_fallback';
        }
        if ($effective_title === '') {
            $issues[] = 'missing_effective_title';
        } elseif (!$this->is_title_length_usable($effective_title)) {
            $issues[] = 'seo_title_length';
        }

        if ($custom_description === '') {
            $issues[] = $effective_description === '' ? 'missing_effective_description' : 'description_fallback';
        }
        if ($effective_description !== '' && !$this->is_description_length_usable($effective_description)) {
            $issues[] = 'meta_description_length';
        }

        if ($keyword === '') {
            $issues[] = 'missing_focus_keyword';
        }

        if ((string) get_post_meta($post_id, '_vonseowp_noindex', true) === '1') {
            $issues[] = 'noindex';
        }

        if ($this->has_image_without_alt($content)) {
            $issues[] = 'missing_image_alt';
        }

        $links = $this->get_link_state($content);
        if (!$links['internal']) {
            $issues[] = 'missing_internal_link';
        }
        if (!$links['external']) {
            $issues[] = 'missing_external_link';
        }

        return $issues;
    }

    public function get_issue_labels(): array {
        return array(
            'title_fallback'                => __('Using WordPress title fallback', 'vonseo'),
            'missing_effective_title'       => __('No effective page title', 'vonseo'),
            'seo_title_length'              => __('Effective title length needs review', 'vonseo'),
            'description_fallback'          => __('Using excerpt or content description fallback', 'vonseo'),
            'missing_effective_description' => __('No effective meta description', 'vonseo'),
            'meta_description_length'       => __('Effective description length needs review', 'vonseo'),
            'missing_focus_keyword'         => __('Focus keyword not set', 'vonseo'),
            'noindex'                       => __('Marked noindex', 'vonseo'),
            'missing_image_alt'             => __('Image ALT text missing', 'vonseo'),
            'missing_internal_link'         => __('No internal link found', 'vonseo'),
            'missing_external_link'         => __('No external link found', 'vonseo'),
        );
    }

    public function render_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $vonseowp_audit_results = get_transient(self::TRANSIENT_KEY);
        if (!is_array($vonseowp_audit_results)
            || !isset($vonseowp_audit_results['version'])
            || $vonseowp_audit_results['version'] !== VONSEOWP_VERSION
        ) {
            $vonseowp_audit_results = array();
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only notice after the nonce-protected audit action.
        $vonseowp_audit_notice = isset($_GET['vonseowp_audit'])
            ? sanitize_key(wp_unslash($_GET['vonseowp_audit']))
            : '';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        $vonseowp_issue_labels = $this->get_issue_labels();

        require VONSEOWP_PATH . 'admin/partials/vonseowp-site-audit-display.php';
    }

    private function get_published_total(): int {
        $total = 0;

        foreach (array('post', 'page') as $post_type) {
            $counts = wp_count_posts($post_type);
            if (is_object($counts) && isset($counts->publish)) {
                $total += (int) $counts->publish;
            }
        }

        return $total;
    }

    private function get_technical_checks(): array {
        $options = get_option('vonseowp_settings', array());
        if (!is_array($options)) {
            $options = array();
        }

        $site_public = (bool) get_option('blog_public');
        $permalink_structure = trim((string) get_option('permalink_structure'));
        $sitemap_enabled = !isset($options['enable_sitemap']) || (int) $options['enable_sitemap'] === 1;
        $sitemap_has_content = (!isset($options['sitemap_posts']) || (int) $options['sitemap_posts'] === 1)
            || (!isset($options['sitemap_pages']) || (int) $options['sitemap_pages'] === 1);
        $home_title = isset($options['home_title']) ? trim((string) $options['home_title']) : '';
        $home_description = isset($options['home_desc']) ? trim((string) $options['home_desc']) : '';
        if ($home_title === '') {
            $home_title = trim((string) get_bloginfo('name'));
        }
        if ($home_description === '') {
            $home_description = trim((string) get_bloginfo('description'));
        }
        $robots = isset($options['robots_txt']) ? (string) $options['robots_txt'] : '';
        $robots_has_root_disallow = $this->has_wildcard_root_disallow($robots);

        if (!$sitemap_enabled || !$sitemap_has_content) {
            $sitemap_detail = __('Sitemap output is disabled or has no selected content types.', 'vonseo');
        } elseif (!$site_public) {
            $sitemap_detail = __('Sitemap output is suppressed while search visibility is disabled.', 'vonseo');
        } elseif ($permalink_structure === '') {
            $sitemap_detail = __('Pretty permalinks are required for the VonSEO sitemap route.', 'vonseo');
        } else {
            $sitemap_detail = home_url('/sitemap.xml');
        }

        return array(
            array(
                'label'  => __('Search visibility', 'vonseo'),
                'passed' => $site_public,
                'detail' => $site_public
                    ? __('Search engines are allowed to index this site.', 'vonseo')
                    : __('WordPress is discouraging search engines from indexing this site.', 'vonseo'),
            ),
            array(
                'label'  => __('Permalink structure', 'vonseo'),
                'passed' => $permalink_structure !== '',
                'detail' => $permalink_structure !== ''
                    ? $permalink_structure
                    : __('Plain permalinks are active.', 'vonseo'),
            ),
            array(
                'label'  => __('XML sitemap', 'vonseo'),
                'passed' => $sitemap_enabled && $sitemap_has_content && $permalink_structure !== '' && $site_public,
                'detail' => $sitemap_detail,
            ),
            array(
                'label'  => __('Homepage metadata', 'vonseo'),
                'passed' => $home_title !== '' && $home_description !== '',
                'detail' => ($home_title !== '' && $home_description !== '')
                    ? __('Homepage title and description are available.', 'vonseo')
                    : __('Add a homepage title and description.', 'vonseo'),
            ),
            array(
                'label'  => __('Robots rules', 'vonseo'),
                'passed' => !$robots_has_root_disallow,
                'detail' => $robots_has_root_disallow
                    ? __('The wildcard crawler group contains an exact Disallow: / directive. Review any Allow exceptions manually.', 'vonseo')
                    : __('No exact Disallow: / directive was found for wildcard crawlers.', 'vonseo'),
            ),
        );
    }

    private function get_focus_keyword(string $keywords): string {
        $parts = explode(',', $keywords);
        return trim($parts[0] ?? '');
    }

    /** @param WP_Post $post */
    private function get_effective_description($post, string $custom_description): string {
        if ($custom_description !== '') {
            return $custom_description;
        }

        $excerpt = isset($post->post_excerpt) ? trim((string) $post->post_excerpt) : '';
        if ($excerpt !== '') {
            return $excerpt;
        }

        $content = isset($post->post_content) ? (string) $post->post_content : '';
        if (class_exists('VonSEOWP_Frontend')) {
            return VonSEOWP_Frontend::get_content_fallback_description($content);
        }

        return trim(wp_strip_all_tags(wp_trim_words(strip_shortcodes($content), 30, '...')));
    }

    private function is_title_length_usable(string $title): bool {
        $length = $this->get_text_length(trim(wp_strip_all_tags($title)));
        return $length >= 30 && $length <= 70;
    }

    private function is_description_length_usable(string $description): bool {
        $length = $this->get_text_length(trim(wp_strip_all_tags($description)));
        return $length >= 90 && $length <= 175;
    }

    private function get_text_length(string $text): int {
        return function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
    }

    private function has_wildcard_root_disallow(string $robots): bool {
        $applies_to_all = false;
        $group_has_rules = false;
        $lines = preg_split('/\r\n|\r|\n/', $robots);

        if (!is_array($lines)) {
            return false;
        }

        foreach ($lines as $line) {
            $line = trim((string) preg_replace('/\s*#.*$/', '', $line));
            if ($line === '' || strpos($line, ':') === false) {
                continue;
            }

            list($directive, $value) = array_map('trim', explode(':', $line, 2));
            $directive = strtolower($directive);

            if ($directive === 'user-agent') {
                if ($group_has_rules) {
                    $applies_to_all = false;
                    $group_has_rules = false;
                }
                if ($value === '*') {
                    $applies_to_all = true;
                }
                continue;
            }

            $group_has_rules = true;
            if ($applies_to_all && $directive === 'disallow' && $value === '/') {
                return true;
            }
        }

        return false;
    }

    private function has_image_without_alt(string $content): bool {
        if (!preg_match_all('/<img\b[^>]*>/i', $content, $images)) {
            return false;
        }

        foreach ($images[0] as $image) {
            if (!preg_match('/\balt\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $image, $alt_match)) {
                return true;
            }

            $alt = '';
            foreach (array(1, 2, 3) as $index) {
                if (isset($alt_match[$index]) && $alt_match[$index] !== '') {
                    $alt = $alt_match[$index];
                    break;
                }
            }

            if (trim(wp_strip_all_tags(html_entity_decode($alt, ENT_QUOTES, 'UTF-8'))) === '') {
                return true;
            }
        }

        return false;
    }

    private function get_link_state(string $content): array {
        $state = array('internal' => false, 'external' => false);

        if (!preg_match_all('/<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1/is', $content, $links)) {
            return $state;
        }

        $site_host = $this->normalize_host((string) wp_parse_url(home_url('/'), PHP_URL_HOST));

        foreach ($links[2] as $raw_href) {
            $href = trim(html_entity_decode((string) $raw_href, ENT_QUOTES, 'UTF-8'));
            if ($href === '' || $href[0] === '#' || preg_match('/^(mailto|tel|javascript):/i', $href)) {
                continue;
            }

            if ($href[0] === '/' && substr($href, 0, 2) !== '//') {
                $state['internal'] = true;
                continue;
            }

            $parsed_href = substr($href, 0, 2) === '//' ? 'https:' . $href : $href;
            $link_host = $this->normalize_host((string) wp_parse_url($parsed_href, PHP_URL_HOST));

            if ($link_host === '' || $link_host === $site_host) {
                $state['internal'] = true;
            } else {
                $state['external'] = true;
            }

            if ($state['internal'] && $state['external']) {
                break;
            }
        }

        return $state;
    }

    private function normalize_host(string $host): string {
        $host = strtolower(trim($host));
        return substr($host, 0, 4) === 'www.' ? substr($host, 4) : $host;
    }
}
