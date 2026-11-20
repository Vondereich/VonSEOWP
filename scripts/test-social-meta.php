<?php
/**
 * Behavioural <head> regressions: renders VonSEOWP_Frontend::output_meta_tags() against modeled WordPress
 * state and checks the actual description, og:title and og:image output. No database or network.
 */
declare(strict_types=1);
define('ABSPATH', __DIR__);

$state = array();
$assertions = 0;

function vonseowp_social_reset(array $overrides = array()): void {
    $GLOBALS['state'] = array_merge(array(
        'settings' => array('home_title' => 'Brand Home', 'home_desc' => 'Site wide description', 'seo_post_types' => array('post', 'page')),
        'show_on_front' => 'posts', 'page_on_front' => 0, 'page_for_posts' => 0,
        'singular' => true, 'front' => false, 'home' => false, 'search' => false, 'is404' => false,
        'paged' => 0, 'locked' => array(), 'ssl' => true,
        'posts' => array(),
        'meta' => array(),
        'excerpts' => array(),
        'document_title' => 'Assembled – WordPress Site',
        'current' => 0,
    ), $overrides);
    $GLOBALS['post'] = $GLOBALS['state']['posts'][$GLOBALS['state']['current']] ?? null;
}

function add_action(string $hook, callable $callback, int $priority = 10, int $args = 1): void {}
function add_filter(string $hook, callable $callback, int $priority = 10, int $args = 1): void {}
/** @param callable|string $callback */
function remove_action(string $hook, $callback, int $priority = 10): void {}
/** @param mixed $value @return mixed */
function apply_filters(string $hook, $value) { return $value; }
function __(string $text, string $domain = ''): string { return $text; }
/** @param mixed $default @return mixed */
function get_option(string $name, $default = false) {
    $s = $GLOBALS['state'];
    switch ($name) {
        case 'vonseowp_settings': return $s['settings'];
        case 'show_on_front': return $s['show_on_front'];
        case 'page_on_front': return $s['page_on_front'];
        case 'page_for_posts': return $s['page_for_posts'];
        case 'blog_public': return 1;
    }
    return $default;
}
function get_post_types(array $args = array(), string $output = 'names'): array {
    return array('post' => (object) array('_builtin' => true), 'page' => (object) array('_builtin' => true),
        'book' => (object) array('_builtin' => false, 'public' => true, 'publicly_queryable' => true));
}
function sanitize_key(string $key): string { return strtolower($key); }
function is_singular(): bool { return $GLOBALS['state']['singular']; }
function is_front_page(): bool { return $GLOBALS['state']['front']; }
function is_home(): bool { return $GLOBALS['state']['home']; }
function is_search(): bool { return $GLOBALS['state']['search']; }
function is_404(): bool { return $GLOBALS['state']['is404']; }
function get_queried_object_id(): int { return $GLOBALS['state']['queried'] ?? 0; }
/** @return object|null */
function get_post(int $id = 0) { return $GLOBALS['state']['posts'][$id] ?? null; }
function post_password_required(object $post): bool { return in_array($post->ID, $GLOBALS['state']['locked'], true); }
/** @return mixed */
function get_post_meta(int $id, string $key, bool $single = false) { return $GLOBALS['state']['meta'][$id][$key] ?? ''; }
function has_excerpt(int $id): bool { return isset($GLOBALS['state']['excerpts'][$id]); }
function get_the_excerpt(int $id): string { return $GLOBALS['state']['excerpts'][$id] ?? ''; }
function has_post_thumbnail(int $id): bool { return false; }
function get_the_post_thumbnail_url(int $id, string $size): string { return ''; }
function get_permalink(int $id = 0): string { return 'https://example.invalid/p/' . $id . '/'; }
/** @return mixed */
function get_query_var(string $name, $default = '') { return $name === 'paged' ? $GLOBALS['state']['paged'] : $default; }
function get_pagenum_link(int $number = 1, bool $escape = true): string { return 'https://example.invalid/page/' . $number . '/'; }
function home_url(string $path = ''): string { return 'https://example.invalid' . $path; }
/** @return mixed */
function wp_parse_url(string $url, int $component = -1) { return parse_url($url, $component); }
function get_bloginfo(string $key = ''): string { return $key === 'charset' ? 'UTF-8' : ($key === 'description' ? 'Tagline' : 'Example Site'); }
function wp_get_document_title(): string { return $GLOBALS['state']['document_title']; }
function wp_strip_all_tags(string $text): string { return trim(strip_tags($text)); }
function strip_shortcodes(string $text): string { return $text; }
function has_site_icon(): bool { return false; }
function get_site_icon_url(int $size = 512): string { return ''; }
function esc_attr(string $text): string { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8', false); }
function esc_url(string $url): string { return htmlspecialchars($url, ENT_QUOTES, 'UTF-8', false); }
function esc_html(string $text): string { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8', false); }
function get_locale(): string { return 'en_US'; }
/** @param mixed $post */
function get_the_date(string $format = '', $post = null): string { return '2026-11-20T00:00:00+00:00'; }
/** @param mixed $post */
function get_the_modified_date(string $format = '', $post = null): string { return '2026-11-20T00:00:00+00:00'; }
/** @return string */
function get_the_author_meta(string $field, int $id = 0) { return 'Author'; }
/** @param mixed $post */
function get_the_title($post = 0): string { $id = is_object($post) ? $post->ID : (int) $post; return $GLOBALS['state']['posts'][$id]->post_title ?? ''; }
function trailingslashit(string $value): string { return rtrim($value, '/') . '/'; }
function is_ssl(): bool { return $GLOBALS['state']['ssl']; }

require dirname(__DIR__) . '/includes/class-vonseowp-content-types.php';
require dirname(__DIR__) . '/includes/class-vonseowp-frontend.php';
require dirname(__DIR__) . '/includes/class-vonseowp-breadcrumbs.php';

function vonseowp_social_check(bool $condition, string $message): void {
    $GLOBALS['assertions']++;
    if (!$condition) throw new RuntimeException($message);
}
/** @return array<string, string> */
function vonseowp_social_head(): array {
    unset($GLOBALS['vonseowp_meta_tags_done']);
    ob_start();
    (new VonSEOWP_Frontend())->output_meta_tags();
    $html = (string) ob_get_clean();
    $tags = array();
    preg_match_all('/<meta (?:name|property)="([^"]+)" content="([^"]*)"/', $html, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) $tags[$match[1]] = html_entity_decode($match[2], ENT_QUOTES, 'UTF-8');
    $tags['__count_description'] = (string) substr_count($html, 'name="description"');
    return $tags;
}
function vonseowp_social_post(int $id, string $type, string $title, string $content = 'Body text of the post.'): object {
    return (object) array('ID' => $id, 'post_type' => $type, 'post_title' => $title, 'post_content' => $content,
        'post_status' => 'publish', 'post_author' => 1, 'post_password' => '');
}

// 1. Static front page: og:title keeps the homepage title (regression found in audit).
vonseowp_social_reset(array('show_on_front' => 'page', 'page_on_front' => 5, 'front' => true,
    'posts' => array(5 => vonseowp_social_post(5, 'page', 'Home')), 'current' => 5));
$head = vonseowp_social_head();
vonseowp_social_check($head['og:title'] === 'Brand Home' && $head['twitter:title'] === 'Brand Home',
    'Static front page og:title must use the homepage title, got: ' . $head['og:title']);

// 2. Posts: SEO title, else post title; never the assembled document title.
vonseowp_social_reset(array('posts' => array(7 => vonseowp_social_post(7, 'post', 'Post &#8211; Title')), 'current' => 7,
    'meta' => array(7 => array('_vonseowp_title' => 'SEO Title'))));
vonseowp_social_check(vonseowp_social_head()['og:title'] === 'SEO Title', 'Saved SEO title must be the og:title.');
$state['meta'][7]['_vonseowp_title'] = '';
vonseowp_social_check(vonseowp_social_head()['og:title'] === "Post \u{2013} Title", 'Post title must be the og:title without an SEO title.');

// 3. Description chain: saved > excerpt > AI plugin > content; exactly one description tag.
$state['meta'][7] = array('_vonseowp_description' => 'Saved', 'wpai_meta_description' => 'Generated');
$state['excerpts'][7] = 'Excerpt';
$head = vonseowp_social_head();
vonseowp_social_check($head['description'] === 'Saved' && $head['__count_description'] === '1', 'Saved description must win.');
$state['meta'][7]['_vonseowp_description'] = '';
vonseowp_social_check(vonseowp_social_head()['description'] === 'Excerpt', 'A hand-written excerpt must outrank the AI plugin.');
unset($state['excerpts'][7]);
vonseowp_social_check(vonseowp_social_head()['description'] === 'Generated', 'The AI plugin description must be the next fallback.');
$state['meta'][7] = array();
vonseowp_social_check(vonseowp_social_head()['description'] === 'Body text of the post.', 'Content must be the last fallback.');

// 4. Post types VonSEO does not manage keep the AI description; protected posts never expose it.
vonseowp_social_reset(array('posts' => array(8 => vonseowp_social_post(8, 'book', 'Book')), 'current' => 8,
    'meta' => array(8 => array('wpai_meta_description' => 'Generated book'))));
vonseowp_social_check(vonseowp_social_head()['description'] === 'Generated book', 'Unmanaged types must keep the AI description.');
$state['locked'] = array(8);
vonseowp_social_check(vonseowp_social_head()['description'] === 'Site wide description', 'Protected posts must not expose the AI description.');
vonseowp_social_check((new VonSEOWP_Frontend())->suppress_external_description('x') === '', 'The AI plugin tag stays suppressed.');

// 5. Separate posts page: own title with page suffix, home description fallback when it has none.
vonseowp_social_reset(array('show_on_front' => 'page', 'page_on_front' => 3, 'page_for_posts' => 4, 'singular' => false, 'home' => true,
    'queried' => 4, 'posts' => array(3 => vonseowp_social_post(3, 'page', 'Home'), 4 => vonseowp_social_post(4, 'page', 'Blog')),
    'meta' => array(4 => array('_vonseowp_title' => 'Latest articles'))));
$head = vonseowp_social_head();
vonseowp_social_check($head['og:title'] === 'Latest articles' && $head['description'] === 'Site wide description',
    'Posts page must use its SEO title and fall back to the site description.');
$state['paged'] = 2;
vonseowp_social_check((new VonSEOWP_Frontend())->filter_document_title('Blog') === 'Latest articles - Page 2', 'Paginated posts pages need a page suffix.');
$state['posts'][4]->post_status = 'private';
$state['paged'] = 0;
vonseowp_social_check((new VonSEOWP_Frontend())->filter_document_title('Blog') === 'Blog', 'A private posts page must not publish its SEO fields.');

// 6. "A static page" selected without a homepage: the site root is still the homepage.
vonseowp_social_reset(array('show_on_front' => 'page', 'page_on_front' => 0, 'page_for_posts' => 4, 'singular' => false, 'home' => true,
    'posts' => array(4 => vonseowp_social_post(4, 'page', 'Blog')), 'meta' => array(4 => array('_vonseowp_title' => 'Latest articles'))));
vonseowp_social_check((new VonSEOWP_Frontend())->filter_document_title('Root') === 'Brand Home', 'Root without a chosen homepage keeps the homepage title.');

// 7. Legacy relative and protocol-relative social images are published as absolute URLs.
vonseowp_social_reset(array('posts' => array(9 => vonseowp_social_post(9, 'post', 'Image')), 'current' => 9,
    'meta' => array(9 => array('_vonseowp_image' => '/wp-content/uploads/a.jpg'))));
vonseowp_social_check(vonseowp_social_head()['og:image'] === 'https://example.invalid/wp-content/uploads/a.jpg', 'Root-relative images must become absolute.');
$state['meta'][9]['_vonseowp_image'] = '//cdn.example.invalid/b.jpg';
vonseowp_social_check(vonseowp_social_head()['og:image'] === 'https://cdn.example.invalid/b.jpg', 'Protocol-relative images must get a scheme.');
$state['meta'][9]['_vonseowp_image'] = 'javascript:alert(1)';
vonseowp_social_check(!isset(vonseowp_social_head()['og:image']), 'Non-http images must not be published.');

// 8. Front page edge cases from the re-audit.
vonseowp_social_reset(array('show_on_front' => 'page', 'page_on_front' => 5, 'front' => true,
    'settings' => array('home_title' => '', 'home_desc' => 'Site wide description', 'seo_post_types' => array('post')),
    'posts' => array(5 => vonseowp_social_post(5, 'page', 'Home')), 'current' => 5,
    'meta' => array(5 => array('wpai_meta_description' => 'Generated home'))));
$head = vonseowp_social_head();
vonseowp_social_check($head['og:title'] === 'Assembled – WordPress Site', 'A cleared Site Title keeps the assembled front-page title, not the page title.');
vonseowp_social_check($head['description'] === 'Site wide description', 'An unmanaged static front page keeps the homepage description.');
// 9. "A static page" without a chosen homepage: no posts-page breadcrumb trail at the root.
vonseowp_social_reset(array('show_on_front' => 'page', 'page_on_front' => 0, 'page_for_posts' => 4, 'singular' => false, 'home' => true,
    'posts' => array(4 => vonseowp_social_post(4, 'page', 'Blog'))));
vonseowp_social_check(VonSEOWP_Breadcrumbs::get_items() === array(), 'The root without a chosen homepage is the homepage, not Home > Blog.');
// 10. Same setup, but on the posts page URL itself (core's is_posts_page): it is still the posts page.
$state['queried'] = 4;
$state['meta'][4] = array('_vonseowp_title' => 'Latest articles');
vonseowp_social_check((new VonSEOWP_Frontend())->filter_document_title('Blog') === 'Latest articles',
    'The posts page URL keeps its own title even when no homepage is chosen.');
$state['queried'] = 0;
vonseowp_social_check((new VonSEOWP_Frontend())->filter_document_title('Root') === 'Brand Home', 'The root stays the homepage.');

echo 'Social meta tests passed (' . $assertions . ' assertions; rendered head output)' . PHP_EOL;
