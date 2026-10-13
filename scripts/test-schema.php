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
function get_option(string $name, $default = false) { return $name === 'blog_public' ? 1 : ($name === 'vonseowp_settings' ? array() : $default); }
function get_post_types(array $args = array(), string $output = 'names'): array { return array('post' => (object) array('_builtin' => true), 'page' => (object) array('_builtin' => true)); }
function sanitize_key(string $key): string { return strtolower($key); }
function is_singular(): bool { return true; }
function is_single(): bool { return $GLOBALS['post']->post_type !== 'page'; }
function is_page(): bool { return !is_single(); }
function post_password_required(object $post): bool { return $GLOBALS['locked']; }
/** @return mixed */
function get_post_meta(int $id, string $key, bool $single = false) { return $GLOBALS['meta'][$key] ?? ''; }
function home_url(string $path = ''): string { return 'https://example.invalid' . $path; }
function get_bloginfo(string $key): string { return 'Public site'; }
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
function wp_json_encode(array $data): string { return (string) json_encode($data); }
require dirname(__DIR__) . '/includes/class-vonseowp-content-types.php';
require dirname(__DIR__) . '/includes/class-vonseowp-frontend.php';
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
echo 'Schema tests passed (' . $assertions . ' assertions; actual builder, modeled WordPress state)' . PHP_EOL;
