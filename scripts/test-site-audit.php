<?php
declare(strict_types=1);

define('ABSPATH', __DIR__);
define('VONSEOWP_VERSION', 'test');

$vonseowp_test_posts = array();
$vonseowp_test_meta = array();
$vonseowp_test_options = array();
$vonseowp_test_post_pages = array();
$vonseowp_test_last_query = array();
$vonseowp_test_publish_counts = array('post' => 0, 'page' => 0);

function __(string $text, string $domain = 'default'): string {
    return $text;
}

function get_post($post_id) {
    global $vonseowp_test_posts;
    return $vonseowp_test_posts[$post_id] ?? null;
}

function get_post_meta(int $post_id, string $key = '', bool $single = false) {
    global $vonseowp_test_meta;
    return $vonseowp_test_meta[$post_id][$key] ?? '';
}

function get_option(string $key, $default = false) {
    global $vonseowp_test_options;
    return array_key_exists($key, $vonseowp_test_options) ? $vonseowp_test_options[$key] : $default;
}

function get_bloginfo(string $show = '', string $filter = 'raw'): string {
    if ($show === 'name') {
        return 'Fallback Site Title';
    }
    if ($show === 'description') {
        return 'Fallback site description for homepage metadata.';
    }
    return '';
}

function wp_strip_all_tags(string $string, bool $remove_breaks = false): string {
    return strip_tags($string);
}

function strip_shortcodes(string $content): string {
    return $content;
}

function wp_trim_words(string $text, int $num_words = 55, ?string $more = null): string {
    $words = preg_split('/\s+/', trim(wp_strip_all_tags($text)));
    $words = is_array($words) ? $words : array();
    $trimmed = array_slice($words, 0, $num_words);
    return implode(' ', $trimmed) . (count($words) > $num_words ? ($more ?? '&hellip;') : '');
}

function home_url(string $path = ''): string {
    return 'https://example.com' . $path;
}

function wp_parse_url(string $url, int $component = -1) {
    return parse_url($url, $component);
}

function get_posts(array $args = array()): array {
    global $vonseowp_test_post_pages, $vonseowp_test_last_query;
    $vonseowp_test_last_query = $args;
    $page = isset($args['paged']) ? (int) $args['paged'] : 1;
    return $vonseowp_test_post_pages[$page] ?? array();
}

function wp_count_posts(string $post_type = 'post'): object {
    global $vonseowp_test_publish_counts;
    return (object) array('publish' => $vonseowp_test_publish_counts[$post_type] ?? 0);
}

function get_post_types(array $args = array(), string $output = 'names'): array {
    return array('post' => (object) array(), 'page' => (object) array());
}
function sanitize_key(string $key): string { return strtolower(preg_replace('/[^a-z0-9_\-]/i', '', $key)); }
require_once dirname(__DIR__) . '/includes/class-vonseowp-content-types.php';
require_once dirname(__DIR__) . '/includes/class-vonseowp-site-audit.php';

function vonseowp_audit_assert(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

function vonseowp_audit_post(int $post_id, string $content, array $meta, string $title = 'Audit test post', string $excerpt = ''): array {
    global $vonseowp_test_posts, $vonseowp_test_meta;

    $vonseowp_test_posts[$post_id] = (object) array(
        'ID'           => $post_id,
        'post_title'   => $title,
        'post_type'    => 'post',
        'post_excerpt' => $excerpt,
        'post_content' => $content,
    );
    $vonseowp_test_meta[$post_id] = $meta;

    $audit = (new ReflectionClass('VonSEOWP_Site_Audit'))->newInstanceWithoutConstructor();
    return $audit->analyze_post($post_id);
}

$healthy_description = 'This practical lightweight SEO guide helps WordPress publishers improve metadata, internal navigation, and useful source links without adding heavy services.';
$healthy_issues = vonseowp_audit_post(
    2001,
    '<p>Useful content with <a href="/guides/">an internal guide</a> and <a href="https://wordpress.org/">an external source</a>.</p><img src="photo.jpg" alt="Editorial SEO checklist">',
    array(
        '_vonseowp_title'       => 'Lightweight SEO checklist for WordPress publishers',
        '_vonseowp_description' => $healthy_description,
        '_vonseowp_keywords'    => 'lightweight seo',
        '_vonseowp_noindex'     => '0',
    )
);

vonseowp_audit_assert($healthy_issues === array(), 'Expected a healthy post to have no audit findings.');

$fallback_content = '<p>This published article has enough useful text to provide a real frontend description fallback for visitors and search previews, but it still has no useful links.</p><img src="photo.jpg" alt="">';
$fallback_issues = vonseowp_audit_post(
    2002,
    $fallback_content,
    array(
        '_vonseowp_title'       => '',
        '_vonseowp_description' => '',
        '_vonseowp_keywords'    => '',
        '_vonseowp_noindex'     => '1',
    ),
    'A useful WordPress title that remains available as fallback'
);

foreach (array(
    'title_fallback',
    'description_fallback',
    'missing_focus_keyword',
    'noindex',
    'missing_image_alt',
    'missing_internal_link',
    'missing_external_link',
) as $expected_issue) {
    vonseowp_audit_assert(
        in_array($expected_issue, $fallback_issues, true),
        'Expected fallback post finding: ' . $expected_issue
    );
}

vonseowp_audit_assert(
    !in_array('missing_effective_title', $fallback_issues, true)
        && !in_array('missing_effective_description', $fallback_issues, true),
    'Expected WordPress title and content description fallbacks to be recognized.'
);

$empty_issues = vonseowp_audit_post(
    2003,
    '',
    array(
        '_vonseowp_title'       => '',
        '_vonseowp_description' => '',
        '_vonseowp_keywords'    => 'empty page',
        '_vonseowp_noindex'     => '0',
    ),
    ''
);

vonseowp_audit_assert(
    in_array('missing_effective_title', $empty_issues, true)
        && in_array('missing_effective_description', $empty_issues, true),
    'Expected empty content to report missing effective metadata.'
);

$relative_link_issues = vonseowp_audit_post(
    2004,
    '<p><a href="guides/on-page-seo">Relative internal link</a> and <a href="//docs.example.net/seo">external protocol-relative link</a>.</p>',
    array(
        '_vonseowp_title'       => 'Practical on-page SEO checks for WordPress content',
        '_vonseowp_description' => $healthy_description,
        '_vonseowp_keywords'    => 'on-page seo',
        '_vonseowp_noindex'     => '0',
    )
);

vonseowp_audit_assert(
    !in_array('missing_internal_link', $relative_link_issues, true)
        && !in_array('missing_external_link', $relative_link_issues, true),
    'Expected relative and protocol-relative links to be classified correctly.'
);

$audit = (new ReflectionClass('VonSEOWP_Site_Audit'))->newInstanceWithoutConstructor();
$robots_method = new ReflectionMethod('VonSEOWP_Site_Audit', 'has_wildcard_root_disallow');
$robots_method->setAccessible(true);

vonseowp_audit_assert(
    $robots_method->invoke($audit, "User-agent: BadBot\nDisallow: /") === false,
    'Expected a bot-specific Disallow rule not to be reported as a site-wide block.'
);
vonseowp_audit_assert(
    $robots_method->invoke($audit, "User-agent: *\nDisallow: /") === true,
    'Expected an exact wildcard Disallow directive to be detected.'
);
vonseowp_audit_assert(
    $robots_method->invoke($audit, "User-agent: *\nDisallow: /\nAllow: /public/") === true,
    'Expected an exact wildcard Disallow directive to be flagged for manual exception review.'
);

$vonseowp_test_options = array(
    'vonseowp_settings'   => array('home_title' => '', 'home_desc' => ''),
    'blog_public'         => 1,
    'permalink_structure' => '/%postname%/',
);
$technical_method = new ReflectionMethod('VonSEOWP_Site_Audit', 'get_technical_checks');
$technical_method->setAccessible(true);
$technical_checks = $technical_method->invoke($audit);

vonseowp_audit_assert(
    isset($technical_checks[3]['passed']) && $technical_checks[3]['passed'] === true,
    'Expected WordPress site title and tagline to satisfy empty VonSEO homepage fields.'
);

$vonseowp_test_publish_counts = array('post' => 50, 'page' => 10);
$vonseowp_test_post_pages = array(3 => range(3051, 3060));
foreach ($vonseowp_test_post_pages[3] as $post_id) {
    $vonseowp_test_posts[$post_id] = (object) array(
        'ID'           => $post_id,
        'post_title'   => 'Healthy batched audit title for published content',
        'post_type'    => 'post',
        'post_excerpt' => '',
        'post_content' => '<p>Useful content with <a href="/guide/">an internal guide</a> and <a href="https://wordpress.org/">an external source</a>.</p>',
    );
    $vonseowp_test_meta[$post_id] = array(
        '_vonseowp_title'       => 'Healthy batched audit title for published content',
        '_vonseowp_description' => $healthy_description,
        '_vonseowp_keywords'    => 'batched audit',
        '_vonseowp_noindex'     => '0',
    );
}

$last_batch = $audit->run_audit(9999);
vonseowp_audit_assert(
    $last_batch['page'] === 3
        && $last_batch['pages'] === 3
        && $last_batch['range_start'] === 51
        && $last_batch['range_end'] === 60
        && $last_batch['scanned'] === 10,
    'Expected oversized batch requests to clamp to the final 25-item batch.'
);
vonseowp_audit_assert(
    isset($vonseowp_test_last_query['paged'], $vonseowp_test_last_query['posts_per_page'])
        && $vonseowp_test_last_query['paged'] === 3
        && $vonseowp_test_last_query['posts_per_page'] === 25
        && !empty($vonseowp_test_last_query['no_found_rows']),
    'Expected the content query to remain bounded and use the clamped batch page.'
);

$pagination_css = (string) file_get_contents(dirname(__DIR__) . '/admin/css/vonseowp-admin.css');
foreach (array(
    '.von-audit-pagination .button.von-audit-page-button .dashicons {',
    '.von-audit-pagination .button.von-audit-page-button .dashicons::before {',
) as $selector) {
    vonseowp_audit_assert(
        preg_match('/' . preg_quote($selector, '/') . '([^}]+)}/s', $pagination_css, $rule) === 1
            && strpos($rule[1], 'line-height: 16px;') !== false,
        'Expected scoped arrow/glyph line-height to override native WordPress button styles.'
    );
}

echo 'Site audit tests passed' . PHP_EOL;
