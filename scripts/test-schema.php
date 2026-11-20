<?php
/** Actual schema builder with portable WordPress fixtures; no database/network. */
declare(strict_types=1);
define('ABSPATH', __DIR__);
$post = (object) array('ID' => 1, 'post_author' => 7, 'post_type' => 'post', 'post_content' => 'Public fixture.', 'post_password' => '');
$meta = array();
$assertions = 0;
$locked = false;
$authors = array(7 => 'Public author', 9 => 'Unrelated Loop author');
$loop_author_id = 0;
function add_action(string $hook, callable $callback, int $priority = 10, int $args = 1): void {}
function add_filter(string $hook, callable $callback, int $priority = 10, int $args = 1): void {}
/** @param callable|string $callback */
function remove_action(string $hook, $callback, int $priority = 10): void {}
/** @param mixed $default @return mixed */
function get_option(string $name, $default = false) { return $name === 'blog_public' ? 1 : ($name === 'vonseowp_settings' ? ($GLOBALS['settings'] ?? array()) : $default); }
function get_post_types(array $args = array(), string $output = 'names'): array { return array('post' => (object) array('_builtin' => true), 'page' => (object) array('_builtin' => true)); }
function sanitize_key(string $key): string { return strtolower($key); }
function is_singular(): bool { return true; }
function is_single(): bool { return $GLOBALS['post']->post_type !== 'page'; }
function is_page(): bool { return !is_single(); }
function post_password_required(object $post): bool { return $GLOBALS['locked']; }
/** @return mixed */
function get_post_meta(int $id, string $key, bool $single = false) { return $GLOBALS['meta'][$key] ?? ''; }
function home_url(string $path = ''): string { return 'https://example.invalid' . $path; }
function get_bloginfo(string $key): string { return $GLOBALS['site_name'] ?? 'Public site'; }
function has_site_icon(): bool { return false; }
function has_post_thumbnail(int $id): bool { return false; }
function get_permalink(int $id = 0): string { return 'https://example.invalid/item/'; }
function get_the_title(int $id = 0): string { return 'Public item & title'; }
function wp_strip_all_tags(string $text): string { return strip_tags($text); }
function strip_shortcodes(string $text): string { return $text; }
function esc_attr(string $text): string { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
function get_the_date(string $format): string { return '2026-10-13T00:00:00+00:00'; }
function get_the_modified_date(string $format): string { return get_the_date($format); }
function get_the_author(): string { return $GLOBALS['authors'][$GLOBALS['loop_author_id']] ?? ''; }
/** @param int|string $id */
function get_author_posts_url($id): string { return 'https://example.invalid/author/' . (int) $id . '/'; }
/** @return int|string */
function get_the_author_meta(string $key, ?int $id = null) {
    $id = empty($id) ? $GLOBALS['loop_author_id'] : $id;
    if (!isset($GLOBALS['authors'][$id])) return '';
    return $key === 'ID' ? $id : $GLOBALS['authors'][$id];
}
function get_the_category(int $id): array { return array(); }
function __(string $text, string $domain = ''): string { return $text; }
/** @return mixed */
function wp_parse_url(string $url, int $component = -1) { return parse_url($url, $component); }
function wp_parse_str(string $query, array &$result): void { parse_str($query, $result); }
function get_site_icon_url(int $size = 512): string { return 'https://example.invalid/icon.png'; }
function wp_json_encode(array $data, int $flags = 0): string { $GLOBALS['json_flags'] = $flags; return (string) json_encode($data, $flags); }
/** @return object|null */
function get_post(int $id) { return $GLOBALS['ancestor_posts'][$id] ?? null; }
function is_404(): bool { return false; }
function is_search(): bool { return false; }
function is_front_page(): bool { return false; }
function is_home(): bool { return false; }
function get_queried_object_id(): int { return 1; }
function get_post_ancestors(int $id): array { return array(); }
/** @param mixed $value @return mixed */
function apply_filters(string $hook, $value) { return $value; }
require dirname(__DIR__) . '/includes/class-vonseowp-content-types.php';
require dirname(__DIR__) . '/includes/class-vonseowp-frontend.php';
require dirname(__DIR__) . '/includes/class-vonseowp-admin.php';
require dirname(__DIR__) . '/includes/class-vonseowp-breadcrumbs.php';
function vonseowp_schema_check(bool $condition, string $message): void {
    $GLOBALS['assertions']++;
    if (!$condition) throw new RuntimeException($message);
}
function vonseowp_schema_graph(string $type, array $extra = array()): array {
    $GLOBALS['meta'] = array_merge(array('_vonseowp_description' => 'Public & unescaped description.', '_vonseowp_schema_type' => $type), $extra);
    unset($GLOBALS['vonseowp_json_ld_done']);
    ob_start();
    (new VonSEOWP_Frontend())->output_json_ld();
    $html = (string) ob_get_clean();
    if (!preg_match('/<script[^>]*>(.*)<\/script>/s', $html, $match)) throw new RuntimeException('Schema script absent.');
    $schema = json_decode($match[1], true);
    if (!is_array($schema)) throw new RuntimeException('Invalid schema JSON.');
    return $schema['@graph'];
}
function vonseowp_schema_node(string $type, array $extra = array()): array {
    $graph = vonseowp_schema_graph($type, $extra);
    foreach ($graph as $node) if (strpos($node['@id'] ?? '', 'https://example.invalid/item/#') === 0) return $node;
    throw new RuntimeException('Expected singular content node.');
}
$rating = array('_vonseowp_rating' => '4.5', '_vonseowp_rating_count' => '2');
$product = vonseowp_schema_node('Product', $rating);
vonseowp_schema_check(($product['name'] ?? '') === 'Public item & title', 'Product requires a real name, not an article headline.');
vonseowp_schema_check(!isset($product['headline']) && !isset($product['author']) && !isset($product['datePublished']), 'Product must not reuse article-only properties.');
vonseowp_schema_check($product['aggregateRating']['ratingCount'] === 2 && $product['aggregateRating']['ratingValue'] === 4.5, 'Product must preserve actual rating/count.');
vonseowp_schema_check($product['description'] === 'Public & unescaped description.', 'JSON text must not contain HTML attribute entities.');
$review = vonseowp_schema_node('Review', array_merge($rating, array('_vonseowp_reviewed_product' => 'Actual product')));
vonseowp_schema_check($review['@type'] === 'Review', 'Valid Review must not fall back before the Loop starts.');
vonseowp_schema_check($review['itemReviewed'] === array('@type' => 'Product', 'name' => 'Actual product'), 'Review must identify the actual reviewed product.');
vonseowp_schema_check($review['reviewRating']['@type'] === 'Rating' && $review['reviewRating']['ratingValue'] === 4.5 && !isset($review['aggregateRating']), 'Single Review uses reviewRating, not aggregateRating.');
vonseowp_schema_check($review['author']['name'] === 'Public author', 'Review must keep the post author before the Loop starts.');
vonseowp_schema_check($review['author']['url'] === 'https://example.invalid/author/7/', 'Review archive must use the explicit post author ID.');
foreach (array(0, 9) as $loop_author_id) {
    foreach (array('Review', 'Article', 'NewsArticle', 'BlogPosting', 'WebPage') as $type) {
        $node = vonseowp_schema_node($type, array_merge($rating, array('_vonseowp_reviewed_product' => 'Actual product')));
        vonseowp_schema_check($node['@type'] === $type, 'Valid schema must survive missing or unrelated Loop state: ' . $type);
        vonseowp_schema_check($node['author'] === array('@type' => 'Person', 'name' => 'Public author', 'url' => 'https://example.invalid/author/7/'), 'Schema must not borrow a Loop author: ' . $type);
    }
}
$loop_author_id = 0;
foreach (array('Article', 'NewsArticle', 'BlogPosting', 'WebPage', 'Service') as $type) {
    $node = vonseowp_schema_node($type, $rating);
    vonseowp_schema_check($node['@type'] === $type && !isset($node['aggregateRating']) && !isset($node['reviewRating']), 'Unrelated types must not receive ratings: ' . $type);
    if ($type === 'Service') vonseowp_schema_check(isset($node['name'], $node['provider']) && !isset($node['headline']) && !isset($node['author']), 'Service requires its own shape.');
}
foreach (array('', '0', '-1', '5.1', 'not-number', array(4)) as $value) {
    foreach (array('Product', 'Review') as $type) {
        $node = vonseowp_schema_node($type, array_merge($rating, array('_vonseowp_rating' => $value, '_vonseowp_reviewed_product' => 'Actual product')));
        vonseowp_schema_check($node['@type'] === 'WebPage' && !isset($node['aggregateRating']) && !isset($node['reviewRating']), 'Incomplete/invalid rated schema must fall back without fabricating a rating.');
    }
}
foreach (array('', '0', '-2', '1.5', 'not-number', array(2)) as $value) {
    $node = vonseowp_schema_node('Product', array_merge($rating, array('_vonseowp_rating_count' => $value)));
    vonseowp_schema_check($node['@type'] === 'WebPage' && !isset($node['aggregateRating']), 'Product must never default or coerce a missing/invalid count to 1.');
}
vonseowp_schema_check(vonseowp_schema_node('Review', $rating)['@type'] === 'WebPage', 'Legacy incomplete Review must fall back without inventing itemReviewed.');
$loop_author_id = 9;
foreach (array(0, 999) as $missing_author_id) {
    $post->post_author = $missing_author_id;
    $node = vonseowp_schema_node('Review', array_merge($rating, array('_vonseowp_reviewed_product' => 'Actual product')));
    vonseowp_schema_check($node['@type'] === 'WebPage' && !isset($node['author']), 'A missing real author must not be replaced with the Loop author or an empty Person.');
}
$post->post_author = 7;
$loop_author_id = 0;
vonseowp_schema_check(vonseowp_schema_node('MadeUpType')['@type'] === 'BlogPosting', 'Unknown stored schema types must use the default.');
$post->post_type = 'page';
vonseowp_schema_check(vonseowp_schema_node('')['@type'] === 'WebPage', 'Default page schema must survive.');
$post->post_type = 'post';
$locked = true;
foreach (vonseowp_schema_graph('Review', array_merge($rating, array('_vonseowp_reviewed_product' => 'PrivateProductSentinel'))) as $node) {
    vonseowp_schema_check(strpos((string) json_encode($node), 'PrivateProductSentinel') === false && strpos($node['@id'] ?? '', '/item/#') === false, 'Password gate must suppress singular schema fields.');
}
$locked = false;
function vonseowp_schema_entity(): array {
    foreach (vonseowp_schema_graph('') as $node) if (($node['@type'] ?? '') === 'Organization') return $node;
    throw new RuntimeException('Organization node absent.');
}
// Core stores the site title HTML-escaped; a cleared Site Title field is saved as ''.
$site_name = 'Tom &amp; Jerry';
foreach (array(array(), array('home_title' => ''), array('home_title' => '  ')) as $settings) {
    vonseowp_schema_check(vonseowp_schema_entity()['name'] === 'Tom & Jerry', 'Organization name must fall back to the decoded site name.');
}
$settings = array('home_title' => 'Brand &amp; Co');
vonseowp_schema_check(vonseowp_schema_entity()['name'] === 'Brand & Co', 'Saved homepage title must name the Organization.');
$settings = array('facebook_url' => 'https://www.facebook.com/example', 'twitter_username' => '@example_1');
vonseowp_schema_check(vonseowp_schema_entity()['sameAs'] === array('https://www.facebook.com/example', 'https://x.com/example_1'),
    'Saved social profiles must be published as Organization sameAs.');
$settings = array('facebook_url' => 'javascript:alert(1)', 'twitter_username' => 'not a handle!');
vonseowp_schema_check(!isset(vonseowp_schema_entity()['sameAs']), 'Invalid social profiles must not be published.');
$settings = array();
$site_name = 'Public site';
$trail = VonSEOWP_Frontend::build_breadcrumb_schema(array(
    array('label' => 'Home', 'url' => 'https://example.invalid/'),
    array('label' => 'Parent &amp; child', 'url' => 'https://example.invalid/parent/'),
    array('label' => 'Current &#8217;post', 'url' => 'https://example.invalid/parent/current/'),
));
vonseowp_schema_check(array_column($trail['itemListElement'], 'position') === array(1, 2, 3)
    && $trail['itemListElement'][1]['name'] === 'Parent & child' && $trail['itemListElement'][2]['name'] === "Current \u{2019}post",
    'Breadcrumb schema must follow the full visible trail with decoded names.');
vonseowp_schema_check(VonSEOWP_Frontend::build_breadcrumb_schema(array(array('label' => 'Home', 'url' => 'https://example.invalid/'))) === array(),
    'A one-item breadcrumb trail must not be published.');
$search = VonSEOWP_Frontend::build_breadcrumb_schema(array(array('label' => 'Home', 'url' => 'https://example.invalid/'), array('label' => 'Search results', 'url' => '')));
vonseowp_schema_check(!isset($search['itemListElement'][1]['item']) && count($search['itemListElement']) === 2, 'The current crumb may omit its URL.');
vonseowp_schema_check(VonSEOWP_Frontend::build_breadcrumb_schema(array(array('label' => 'Home', 'url' => ''), array('label' => 'Post', 'url' => 'https://example.invalid/p/'))) === array(),
    'Intermediate crumbs without a URL must not produce invalid ListItems.');
$frontend = new VonSEOWP_Frontend();
foreach (array(array(), array('separator' => '-'), array('separator' => '<b>')) as $settings) {
    vonseowp_schema_check($frontend->filter_title_separator('&raquo;') === '&raquo;', 'Default or invalid separators must keep the theme separator.');
}
$settings = array('separator' => '|');
vonseowp_schema_check($frontend->filter_title_separator('-') === '|', 'Saved separator must apply to document titles.');
$settings = array();
// VideoObject (SCH-02): files use contentUrl, player pages use embedUrl.
$video_post = (object) array('ID' => 1, 'post_content' => 'Video post body.');
$youtube = VonSEOWP_Frontend::build_video_schema(array('url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=1', 'name' => '', 'desc' => ''), $video_post, '');
vonseowp_schema_check(($youtube['embedUrl'] ?? '') === 'https://www.youtube.com/embed/dQw4w9WgXcQ' && !isset($youtube['contentUrl'])
    && $youtube['thumbnailUrl'] === 'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg' && $youtube['name'] === 'Public item & title'
    && $youtube['description'] === 'Video post body.', 'YouTube pages must use embedUrl with a real thumbnail and decoded fallbacks.');
foreach (array('https://youtu.be/dQw4w9WgXcQ', 'https://www.youtube.com/shorts/dQw4w9WgXcQ') as $short) {
    vonseowp_schema_check((VonSEOWP_Frontend::build_video_schema(array('url' => $short), $video_post, '')['embedUrl'] ?? '') === 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'Short YouTube URLs must resolve to the embed player: ' . $short);
}
$file = VonSEOWP_Frontend::build_video_schema(array('url' => 'https://cdn.example.invalid/clip.mp4', 'name' => 'Clip', 'desc' => 'About'), $video_post, 'https://example.invalid/poster.jpg');
vonseowp_schema_check(($file['contentUrl'] ?? '') === 'https://cdn.example.invalid/clip.mp4' && !isset($file['embedUrl'])
    && $file['thumbnailUrl'] === 'https://example.invalid/poster.jpg', 'Media files must use contentUrl and the post image.');
$vimeo = VonSEOWP_Frontend::build_video_schema(array('url' => 'https://vimeo.com/123456'), $video_post, '');
vonseowp_schema_check(($vimeo['embedUrl'] ?? '') === 'https://vimeo.com/123456' && !isset($vimeo['contentUrl']) && !isset($vimeo['thumbnailUrl']),
    'Other player pages use embedUrl and never a fabricated favicon thumbnail.');
foreach (array('javascript:alert(1)', 'not a url', '//example.invalid/v.mp4') as $bad) {
    vonseowp_schema_check(VonSEOWP_Frontend::build_video_schema(array('url' => $bad), $video_post, '') === array(), 'Invalid video URLs must be skipped: ' . $bad);
}
// JSON-LD must hex-escape markup characters, so no stored value can close the script element.
$json_flags = 0;
$html_out = (function (): string {
    $GLOBALS['meta'] = array('_vonseowp_description' => 'Close </script><script>alert(1)</script> & "quote"', '_vonseowp_schema_type' => 'Article');
    unset($GLOBALS['vonseowp_json_ld_done']);
    ob_start();
    (new VonSEOWP_Frontend())->output_json_ld();
    return (string) ob_get_clean();
})();
$required_flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
vonseowp_schema_check(($json_flags & $required_flags) === $required_flags, 'JSON-LD must use JSON_HEX_TAG|AMP|APOS|QUOT.');
vonseowp_schema_check(substr_count($html_out, '<script') === 1 && substr_count($html_out, '</script>') === 1,
    'Stored values must not open or close script elements inside JSON-LD.');
// Breadcrumb ancestors: only published parents may appear in a public trail.
$ancestor_posts = array(
    10 => (object) array('ID' => 10, 'post_status' => 'publish', 'post_password' => ''),
    11 => (object) array('ID' => 11, 'post_status' => 'private', 'post_password' => ''),
    12 => (object) array('ID' => 12, 'post_status' => 'draft', 'post_password' => ''),
    13 => (object) array('ID' => 13, 'post_status' => 'publish', 'post_password' => 'secret'),
);
vonseowp_schema_check(VonSEOWP_Breadcrumbs::is_public_ancestor(10) && VonSEOWP_Breadcrumbs::is_public_ancestor(13),
    'Published parents stay in the trail (protected titles are already public in WordPress).');
vonseowp_schema_check(!VonSEOWP_Breadcrumbs::is_public_ancestor(11) && !VonSEOWP_Breadcrumbs::is_public_ancestor(12)
    && !VonSEOWP_Breadcrumbs::is_public_ancestor(999), 'Private, draft and missing parents must be hidden.');
// WordPress "AI" plugin: its duplicate description tag is suppressed, its text is a fallback.
vonseowp_schema_check((new VonSEOWP_Frontend())->suppress_external_description('Generated text') === '', 'The AI plugin description tag must be suppressed.');
$ai_node = vonseowp_schema_node('Article', array('_vonseowp_description' => '', 'wpai_meta_description' => 'Generated <b>summary</b>'));
vonseowp_schema_check($ai_node['description'] === 'Generated summary', 'AI plugin descriptions must be the fallback when VonSEO has none.');
$own_node = vonseowp_schema_node('Article', array('_vonseowp_description' => 'Own text', 'wpai_meta_description' => 'Generated'));
vonseowp_schema_check($own_node['description'] === 'Own text', 'A saved VonSEO description must win over the AI plugin.');
echo 'Schema tests passed (' . $assertions . ' assertions; actual builder, modeled WordPress state)' . PHP_EOL;
