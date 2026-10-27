<?php
/** Portable document-title and legacy-theme regressions; no database/network. */
declare(strict_types=1);
define('ABSPATH', __DIR__);
$post = (object) array('ID' => 1, 'post_type' => 'post');
$settings = array();
$meta = array('_vonseowp_title' => 'Custom SEO & title');
$locked = false;
$singular = true;
$front = false;
$home = false;
$site_name = 'Site & Name';
$paged = 1;
$posts_page_id = 0;
$pagination_url = 'https://example.invalid/subdir/page/2/';
$hooks = array();
$assertions = 0;
function add_action(string $hook, callable $callback, int $priority = 10, int $args = 1): void {}
function add_filter(string $hook, callable $callback, int $priority = 10, int $args = 1): void { $GLOBALS['hooks'][$hook] = array($callback, $priority, $args); }
/** @param callable|string $callback */
function remove_action(string $hook, $callback, int $priority = 10): void {}
/** @param mixed $default @return mixed */
function get_option(string $name, $default = false) {
    if ($name === 'vonseowp_settings') return $GLOBALS['settings'];
    return $name === 'page_for_posts' ? $GLOBALS['posts_page_id'] : $default;
}
function home_url(string $path = ''): string { return 'https://example.invalid/subdir' . $path; }
function get_permalink(int $id): string { return 'https://example.invalid/subdir/' . ($id === 2 ? 'blog/' : 'article/'); }
function get_query_var(string $name): int { return $name === 'paged' ? $GLOBALS['paged'] : 0; }
function get_pagenum_link(int $number = 1, bool $escape = true): string { return $GLOBALS['pagination_url']; }
function get_post_types(array $args = array(), string $output = 'names'): array { return array('post' => (object) array('_builtin' => true), 'page' => (object) array('_builtin' => true), 'book' => (object) array('_builtin' => false, 'public' => true, 'publicly_queryable' => true)); }
function sanitize_key(string $key): string { return strtolower($key); }
function is_singular(): bool { return $GLOBALS['singular']; }
function is_front_page(): bool { return $GLOBALS['front']; }
function is_home(): bool { return $GLOBALS['home']; }
function get_bloginfo(string $show = ''): string { return $GLOBALS['site_name']; }
function post_password_required(object $post): bool { return $GLOBALS['locked']; }
/** @return mixed */
function get_post_meta(int $id, string $key, bool $single = false) { return $GLOBALS['meta'][$key] ?? ''; }
function esc_html(string $text): string { return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false); }
require dirname(__DIR__) . '/includes/class-vonseowp-content-types.php';
require dirname(__DIR__) . '/includes/class-vonseowp-frontend.php';
function vonseowp_title_check(bool $condition, string $message): void {
    $GLOBALS['assertions']++;
    if (!$condition) throw new RuntimeException($message);
}
$frontend = new VonSEOWP_Frontend();
vonseowp_title_check(isset($hooks['wp_title']) && $hooks['wp_title'][2] === 3, 'Legacy wp_title must register all three filter arguments.');
vonseowp_title_check($frontend->filter_document_title('Native title') === 'Custom SEO & title', 'Modern document-title behavior must survive.');
vonseowp_title_check($frontend->filter_legacy_title('Native title | ', '|', 'right') === 'Custom SEO &amp; title | ', 'Legacy right separator must preserve theme site-name assembly.');
vonseowp_title_check($frontend->filter_legacy_title(' | Native title', '|', 'left') === ' | Custom SEO &amp; title', 'Legacy left separator must survive.');
vonseowp_title_check($frontend->filter_legacy_title('Native title', '', 'right') === 'Custom SEO &amp; title', 'Empty separator must not add whitespace.');
vonseowp_title_check($frontend->filter_legacy_title('Native title', '&raquo;', '') === ' &raquo; Custom SEO &amp; title', 'Entity separator and default location must survive without double encoding.');
$meta['_vonseowp_title'] = '</title><script>alert(1)</script> & already &amp;';
$escaped = $frontend->filter_legacy_title('Native title', '<', 'right');
vonseowp_title_check(strpos($escaped, '<') === false && strpos($escaped, '&amp;amp;') === false, 'Legacy filter must escape HTML without double-encoding entities.');
$meta['_vonseowp_title'] = '';
vonseowp_title_check($frontend->filter_legacy_title('Native title | ', '|', 'right') === 'Native title | ', 'Empty saved title must preserve native output byte for byte.');
$meta['_vonseowp_title'] = 'ProtectedTitleSentinel';
$locked = true;
vonseowp_title_check($frontend->filter_legacy_title('Protected: Native | ', '|', 'right') === 'Protected: Native | ', 'Locked posts must not reveal private SEO titles.');
$locked = false;
vonseowp_title_check($frontend->filter_legacy_title('Native | ', '|', 'right') === 'ProtectedTitleSentinel | ', 'Unlocked posts may use their saved SEO title.');
$settings['seo_post_types'] = array('page');
vonseowp_title_check($frontend->filter_legacy_title('Native | ', '|', 'right') === 'Native | ', 'Unselected content types must not override native titles.');
foreach (array('page', 'book') as $type) {
    $post->post_type = $type;
    $settings['seo_post_types'] = array($type);
    vonseowp_title_check($frontend->filter_legacy_title('Native | ', '|', 'right') === 'ProtectedTitleSentinel | ', 'Selected pages/CPTs must receive saved titles.');
}
$singular = false;
vonseowp_title_check($frontend->filter_legacy_title('Archive | ', '|', 'right') === 'Archive | ', 'Archive/search/404 contexts must retain their native title.');
foreach (array('front', 'home') as $context) {
    $GLOBALS[$context] = true;
    $settings['home_title'] = 'Home & SEO';
    vonseowp_title_check($frontend->filter_legacy_title('', '|', 'right') === 'Home &amp; SEO | ', 'Front/posts-page overrides must use the shared title policy.');
    $settings['home_title'] = '';
    vonseowp_title_check($frontend->filter_legacy_title('', '|', 'right') === '', 'Empty home override must preserve theme-owned assembly.');
    $settings['home_title'] = $site_name;
    vonseowp_title_check($frontend->filter_legacy_title('', '|', 'right') === '', 'A home override equal to the site name must not duplicate the theme-owned site name.');
    vonseowp_title_check($frontend->filter_document_title('Native') === $site_name, 'Modern home title must keep the saved override.');
    vonseowp_title_check($frontend->filter_legacy_title('Posts | ', '|', 'right') === 'Posts | ', 'Matching home/site names must preserve a native posts-page title.');
    $settings['home_title'] = 'Site &amp; Name';
    vonseowp_title_check($frontend->filter_legacy_title('', '|', 'left') === '', 'Equivalent escaped site names must not duplicate or add a separator.');
    $assembled = $frontend->filter_legacy_title('', '|', 'right') . esc_html($site_name) . ' | Tagline | Page 2';
    vonseowp_title_check($assembled === 'Site &amp; Name | Tagline | Page 2', 'Twenty Eleven must retain one site name, tagline and pagination suffix.');
    $GLOBALS[$context] = false;
}
$singular = true;
$meta['_vonseowp_title'] = $site_name;
vonseowp_title_check($frontend->filter_legacy_title('Native | ', '|', 'right') === 'Site &amp; Name | ', 'Site-name deduplication must not alter singular overrides.');
$canonical = new ReflectionMethod($frontend, 'get_canonical_url');
$canonical->setAccessible(true);
vonseowp_title_check($canonical->invoke($frontend) === 'https://example.invalid/subdir/article/', 'Singular canonicals must retain their permalink.');
$singular = false;
$front = $home = true;
vonseowp_title_check($canonical->invoke($frontend) === home_url('/'), 'First homepage must retain its root canonical.');
$paged = 2;
vonseowp_title_check($canonical->invoke($frontend) === $pagination_url, 'Paginated homepage must not canonicalize to page one.');
$front = false;
$posts_page_id = 2;
$pagination_url = 'https://example.invalid/subdir/blog/page/2/';
vonseowp_title_check($canonical->invoke($frontend) === $pagination_url, 'Separate posts-page pagination must retain its page number.');
$pagination_url = 'https://example.invalid/subdir/?page_id=2&paged=2';
vonseowp_title_check($canonical->invoke($frontend) === $pagination_url, 'Plain posts-page pagination must retain its identity and page number.');
$paged = 1;
vonseowp_title_check($canonical->invoke($frontend) === get_permalink(2), 'First separate posts page must retain its permalink.');
$home = false;
$paged = 2;
$pagination_url = 'https://example.invalid/subdir/category/news/page/2/';
vonseowp_title_check($canonical->invoke($frontend) === $pagination_url, 'Other archive pagination must remain unchanged.');
$singular = true;
vonseowp_title_check($canonical->invoke($frontend) === 'https://example.invalid/subdir/article/', 'Archive pagination state must not replace a singular permalink.');
echo 'Title tests passed (' . $assertions . ' assertions).' . PHP_EOL;
