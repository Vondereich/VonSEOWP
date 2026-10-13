<?php
/** Native canonical code with modeled query state; no live WordPress bootstrap or database. */
declare(strict_types=1);
$root = rtrim($argv[1] ?? '', '/\\');
foreach (array('compat.php', 'class-wp.php', 'pluggable.php', 'canonical.php') as $file) {
    if (!is_file($root . '/wp-includes/' . $file)) throw new RuntimeException('Pass a WordPress source root.');
}
define('ABSPATH', $root . DIRECTORY_SEPARATOR);
define('WPINC', 'wp-includes');
$options = array('permalink_structure' => '/%postname%/',
    'vonseowp_settings' => array('redirects_list' => '/subdir/foo/ -> /subdir/foo/?p=101'));
$query_state = array('p' => 101);
$current_post_id = 101;
$post_fixtures = array(
    101 => (object) array('ID' => 101, 'post_status' => 'publish', 'post_type' => 'post', 'post_name' => 'foo'),
    104 => (object) array('ID' => 104, 'post_status' => 'publish', 'post_type' => 'book', 'post_name' => 'foo'),
);
$permalinks = array(101 => 'http://example.invalid/subdir/foo/', 104 => 'http://example.invalid/subdir/books/foo/');
/** @param mixed $default */
function get_option(string $key, $default = false) { return $GLOBALS['options'][$key] ?? $default; }
/** @param callable $callback */
function add_action(string $hook, $callback, int $priority = 10): void {}
function wp_parse_url(string $url, int $component = -1) { return parse_url($url, $component); }
function wp_parse_str(string $query, array &$result): void { parse_str($query, $result); }
function untrailingslashit(string $path): string { return rtrim($path, '/\\'); }
function trailingslashit(string $path): string { return untrailingslashit($path) . '/'; }
function user_trailingslashit(string $path, string $type = ''): string { return trailingslashit($path); }
function sanitize_text_field(string $text): string { return trim(strip_tags($text)); }
function wp_unslash(string $text): string { return $text; }
function home_url(string $path = ''): string { return 'http://example.invalid/subdir' . ($path !== '' ? '/' . ltrim($path, '/') : ''); }
function is_ssl(): bool { return false; }
/** @param mixed $value @param mixed ...$args */
function apply_filters(string $hook, $value, ...$args) { return $value; }
function wp_normalize_path(string $path): string { return str_replace('\\', '/', $path); }
function wp_kses_no_null(string $text): string { return str_replace("\0", '', $text); }
/** @param string|string[] $search */
function _deep_replace($search, string $subject): string {
    do { $old = $subject; $subject = str_replace($search, '', $subject); } while ($subject !== $old);
    return $subject;
}
function is_preview(): bool { return false; }
function is_admin(): bool { return false; }
function is_search(): bool { return false; }
function is_trackback(): bool { return false; }
function is_favicon(): bool { return false; }
function is_singular(): bool { return true; }
function is_single(): bool { return true; }
function is_attachment(): bool { return false; }
function is_404(): bool { return false; }
function is_feed(): bool { return false; }
function is_front_page(): bool { return false; }
function is_robots(): bool { return false; }
/** @param mixed $default */
function get_query_var(string $name, $default = 0) { return $GLOBALS['query_state'][$name] ?? $default; }
/** @param int|WP_Post|null $post */
function get_permalink($post = null): string {
    $id = $post instanceof WP_Post ? $post->ID : ($post ?: $GLOBALS['current_post_id']);
    return $GLOBALS['permalinks'][$id];
}
class WP_Post {
    public int $ID;
    public string $post_status;
    public string $post_type;
    public string $post_name;
    public function __construct(object $post) {
        $this->ID = $post->ID;
        $this->post_status = $post->post_status;
        $this->post_type = $post->post_type;
        $this->post_name = $post->post_name;
    }
}
/** @param int|WP_Post|null $post */
function get_post($post = null): WP_Post {
    $id = $post instanceof WP_Post ? $post->ID : ($post ?: $GLOBALS['current_post_id']);
    return new WP_Post($GLOBALS['post_fixtures'][$id]);
}
/** @param int|WP_Post $post */
function get_post_status($post): string { return 'publish'; }
function get_post_status_object(string $status): object { return (object) array('private' => false); }
function is_post_publicly_viewable(WP_Post $post): bool { return true; }
/** @param string|string[] $keys */
function remove_query_arg($keys, string $query): string {
    parse_str(ltrim($query, '?'), $args);
    foreach ((array) $keys as $key) unset($args[$key]);
    return http_build_query($args);
}
/** @param mixed $value @return mixed */
function rawurlencode_deep($value) {
    return is_array($value) ? array_map('rawurlencode_deep', $value) : rawurlencode((string) $value);
}
/** @param mixed ...$args */
function add_query_arg(...$args): string {
    $query = $args[0];
    $url = $args[1];
    parse_str((string) parse_url($url, PHP_URL_QUERY), $existing);
    $base = explode('?', $url, 2)[0];
    return $base . '?' . http_build_query(array_merge($existing, $query));
}
$wp_rewrite = new class {
    public string $index = 'index.php';
    public string $permalink_structure = '/%postname%/';
    public function using_permalinks(): bool { return true; }
    public function using_index_permalinks(): bool { return false; }
};
$wp_query = new class {
    public int $post_count = 1;
    public function get_queried_object_id(): int { return $GLOBALS['current_post_id']; }
};
$is_IIS = false;
$_SERVER = array('HTTP_HOST' => 'example.invalid', 'REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/subdir/foo/');
require $root . '/wp-includes/compat.php';
require $root . '/wp-includes/class-wp.php';
require $root . '/wp-includes/pluggable.php';
require $root . '/wp-includes/canonical.php';
require dirname(__DIR__) . '/includes/class-vonseowp-redirects.php';
$wp = new WP();
$wp->query_vars = array('p' => 101, 'name' => 'foo');
$redirects = new VonSEOWP_Redirects();
$destination = '/subdir/foo/?p=101';
$first = $redirects->get_redirect_target('/subdir/foo/');
$_GET = array('p' => '101');
$_SERVER['REQUEST_URI'] = $destination;
$canonical = redirect_canonical('http://example.invalid' . $destination, false);
if ($canonical !== 'http://example.invalid/subdir/foo/') throw new RuntimeException('Native canonical rebound was not reproduced.');
$_GET = array();
$_SERVER['REQUEST_URI'] = '/subdir/foo/';
if ($first !== '' || $redirects->get_redirect_target('/subdir/foo/') !== '') {
    throw new RuntimeException('The plugin must not redirect into the native canonical cycle.');
}
if ($redirects->get_redirect_target($destination) !== '') throw new RuntimeException('The exact-target guard must survive.');
$destination = '/subdir/foo/?p=101&name=foo';
$options['vonseowp_settings']['redirects_list'] = '/subdir/foo/ -> ' . $destination;
$query_state = array('p' => 101, 'name' => 'foo');
$wp->query_vars = $query_state;
$redirects = new VonSEOWP_Redirects();
if ($redirects->get_redirect_target('/subdir/foo/') !== '') throw new RuntimeException('A coherent p+name identity must not start a canonical cycle.');
$_GET = array('p' => '101', 'name' => 'foo');
$_SERVER['REQUEST_URI'] = $destination;
$canonical = redirect_canonical('http://example.invalid' . $destination, false);
if ($canonical !== 'http://example.invalid/subdir/foo/?name=foo') throw new RuntimeException('Native multiple-identity canonical hop was not reproduced.');
if ($redirects->get_redirect_target('/subdir/foo/?name=foo') !== '') throw new RuntimeException('The remaining name parameter must not restart the rule.');
$destination = '/subdir/foo/?p=101&page=2';
$options['vonseowp_settings']['redirects_list'] = '/subdir/foo/ -> ' . $destination;
$query_state = array('p' => 101, 'page' => 2);
$wp->query_vars = $query_state;
$redirects = new VonSEOWP_Redirects();
if ($redirects->get_redirect_target('/subdir/foo/') !== $destination) throw new RuntimeException('Valid multipage redirects must survive.');
$_GET = array('p' => '101', 'page' => '2');
$_SERVER['REQUEST_URI'] = $destination;
$canonical = redirect_canonical('http://example.invalid' . $destination, false);
if ($canonical !== 'http://example.invalid/subdir/foo/2/') throw new RuntimeException('Native multipage canonical path was not reproduced.');
if ($redirects->get_redirect_target('/subdir/foo/2/') !== '') throw new RuntimeException('The multipage canonical path must not restart the rule.');
$source = '/subdir/books/foo/';
$destination = '/subdir/articles/foo/?name=foo';
$permalinks[101] = 'http://example.invalid/subdir/articles/foo/';
$current_post_id = 104;
$options['vonseowp_settings']['redirects_list'] = $source . ' -> ' . $destination;
$query_state = array('name' => 'foo', 'post_type' => 'book');
$wp->query_vars = $query_state;
$_GET = array();
$_SERVER['REQUEST_URI'] = $source;
$redirects = new VonSEOWP_Redirects();
if ($redirects->get_redirect_target($source) !== $destination) throw new RuntimeException('Cross-type same-slug redirects must survive.');
$current_post_id = 101;
$query_state = array('name' => 'foo');
$wp->query_vars = $query_state;
$_GET = array('name' => 'foo');
$_SERVER['REQUEST_URI'] = $destination;
$canonical = redirect_canonical('http://example.invalid' . $destination, false);
if ($canonical !== 'http://example.invalid/subdir/articles/foo/') throw new RuntimeException('Native cross-type canonical path was not reproduced.');
if ($redirects->get_redirect_target('/subdir/articles/foo/') !== '') throw new RuntimeException('Cross-type canonical destination must not restart the source rule.');
echo 'Native canonical rebound/multipage/cross-type regressions passed (PHP ' . PHP_VERSION . '; actual core, modeled query state)' . PHP_EOL;
