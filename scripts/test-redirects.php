<?php
declare(strict_types=1);
define('ABSPATH', __DIR__);
$options = array('permalink_structure' => '/%postname%/');
$posts = array(
    101 => (object) array('ID' => 101, 'post_type' => 'post', 'post_status' => 'publish', 'post_name' => 'foo'),
    102 => (object) array('ID' => 102, 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'page'),
    103 => (object) array('ID' => 103, 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_name' => 'image'),
    104 => (object) array('ID' => 104, 'post_type' => 'book', 'post_status' => 'publish', 'post_name' => 'book'),
    105 => (object) array('ID' => 105, 'post_type' => 'post', 'post_status' => 'publish', 'post_name' => 'other'),
);
$permalinks = array(101 => '/subdir/foo/', 102 => '/subdir/page/', 103 => '/subdir/image/',
    104 => '/subdir/book/', 105 => '/subdir/other/');
$assertions = 0;
$current_post = 101;
$wp = (object) array('public_query_vars' => array('p', 'page_id', 'attachment_id', 'name', 'post_type',
    'page', 'paged', 'feed', 'cpage', 'embed', 'preview', 's', 'book'));
$_SERVER['HTTP_HOST'] = 'example.invalid';
$_SERVER['HTTPS'] = 'off';
/** @param callable $callback */
function add_action(string $hook, $callback, int $priority = 10): void {}
/** @param mixed $default */
function get_option(string $name, $default = false) { return $GLOBALS['options'][$name] ?? $default; }
function home_url(string $path = ''): string { return 'http://example.invalid/subdir' . $path; }
function get_post(int $id = 0): ?object { return $GLOBALS['posts'][$id ?: $GLOBALS['current_post']] ?? null; }
function get_permalink(int $id = 0): string { return 'http://example.invalid' . $GLOBALS['permalinks'][$id]; }
function is_singular(): bool { return $GLOBALS['current_post'] > 0; }
function wp_parse_url(string $url, int $component = -1) { return parse_url($url, $component); }
function wp_parse_str(string $query, array &$result): void { parse_str($query, $result); }
function wp_sanitize_redirect(string $url): string { return str_replace(array("\r", "\n"), '', $url); }
function wp_validate_redirect(string $url, string $fallback): string {
    $parts = parse_url($url);
    if ($parts === false || (isset($parts['scheme']) && !in_array($parts['scheme'], array('http', 'https'), true))) return $fallback;
    return isset($parts['host']) && $parts['host'] !== 'example.invalid' ? $fallback : $url;
}
function sanitize_text_field(string $text): string { return trim(strip_tags($text)); }
function wp_unslash(string $text): string { return stripslashes($text); }
function is_ssl(): bool { return $_SERVER['HTTPS'] === 'on'; }
function trailingslashit(string $path): string { return rtrim($path, '/\\') . '/'; }
function untrailingslashit(string $path): string { return rtrim($path, '/\\'); }
require_once dirname(__DIR__) . '/includes/class-vonseowp-redirects.php';
function vonseowp_redirect_check(bool $condition, string $message): void {
    $GLOBALS['assertions']++;
    if (!$condition) throw new RuntimeException($message);
}
function vonseowp_redirect_target(string $request, string $target): string {
    $GLOBALS['options']['vonseowp_settings'] = array('redirects_list' => parse_url($request, PHP_URL_PATH) . ' -> ' . $target);
    return (new VonSEOWP_Redirects())->get_redirect_target($request);
}
foreach (array(
    array('/subdir/foo/', '/subdir/foo/?p=101'),
    array('/subdir/foo/', '/subdir/foo/?p=101&post_type=post'),
    array('/subdir/foo/', '/subdir/foo/?p=101&name=foo'),
    array('/subdir/foo/', '/subdir/foo/?name=foo&p=101&post_type=post'),
    array('/subdir/foo/?x=1', '/subdir/foo/?p=101&x=2'),
    array('/subdir/foo/', '/subdir/wrong-path/?p=101'),
    array('/subdir/page/', '/subdir/page/?page_id=102'),
    array('/subdir/image/', '/subdir/image/?attachment_id=103'),
    array('/subdir/book/', '/subdir/book/?p=104&post_type=book'),
    array('/subdir/foo/', '/subdir/foo/?name=foo'),
) as $case) {
    vonseowp_redirect_check(vonseowp_redirect_target($case[0], $case[1]) === '', 'Canonical rebound must be rejected: ' . $case[1]);
}
foreach (array(
    array('/subdir/foo/', '/subdir/foo/?x=2'),
    array('/subdir/foo/?x=1', '/subdir/foo/?x=2'),
    array('/subdir/foo/', '/subdir/foo/?p=105'),
    array('/subdir/foo/', '/subdir/foo/?name=other'),
    array('/subdir/foo/', '/subdir/foo/?p=999'),
    array('/subdir/foo/', '/subdir/foo/?p[]=101'),
    array('/subdir/foo/', '/subdir/foo/?p=101&page=2'),
    array('/subdir/foo/', '/subdir/foo/?p=101&paged=2'),
    array('/subdir/foo/', '/subdir/foo/?p=101&feed=rss2'),
    array('/subdir/foo/', '/subdir/foo/?p=101&cpage=2'),
    array('/subdir/foo/', '/subdir/foo/?p=101&embed=true'),
    array('/subdir/foo/', '/subdir/foo/?p=101&preview=true'),
    array('/subdir/foo/', '/subdir/foo/?p=101&s=query'),
    array('/subdir/foo/', '/subdir/foo/?p=101&book=other'),
    array('/subdir/foo/', '/subdir/foo/?p=101&page_id=102'),
    array('/subdir/foo/', '/subdir/foo/?p=101&name=other'),
    array('/subdir/foo/', '/subdir/foo/?p=101&name[]=foo'),
    array('/subdir/foo/', '/subdir/foo/?p=101&name=foo&page=2'),
    array('/subdir/foo/', '/subdir/foo/?p=101&attachment_id=103'),
    array('/subdir/foo/', '/subdir/foo/?p=101&post_type=book'),
    array('/subdir/foo/', 'https://example.invalid/subdir/foo/?p=101'),
    array('/subdir/foo/', 'http://example.invalid:8080/subdir/foo/?p=101'),
    array('/old/', '/subdir/foo/?p=101'),
) as $case) {
    vonseowp_redirect_check(vonseowp_redirect_target($case[0], $case[1]) === $case[1], 'A non-rebounding redirect must survive: ' . $case[1]);
}
foreach (array('/subdir/foo', '/subdir/./foo/', '/subdir/other/../foo/', '#section') as $target) {
    vonseowp_redirect_check(vonseowp_redirect_target('/subdir/foo/', $target) === '', 'Direct/slash/dot/fragment self redirects must remain blocked.');
}
vonseowp_redirect_check(vonseowp_redirect_target('/subdir/foo/', 'https://evil.invalid/') === '', 'External-host safety must remain intact.');
$posts[104]->post_name = 'foo';
$permalinks[104] = '/subdir/books/foo/';
$permalinks[101] = '/subdir/articles/foo/';
$current_post = 104;
$destination = '/subdir/articles/foo/?name=foo';
vonseowp_redirect_check(vonseowp_redirect_target('/subdir/books/foo/', $destination) === $destination,
    'A name target on another path must not be inferred from the source CPT with the same slug.');
vonseowp_redirect_check(vonseowp_redirect_target('/subdir/books/foo/', '/subdir/books/foo/?name=foo') === '',
    'A same-path name target must still be checked against the resolved source post.');
$options['permalink_structure'] = '';
vonseowp_redirect_check(vonseowp_redirect_target('/subdir/foo/', '/subdir/foo/?p=101') === '/subdir/foo/?p=101',
    'Do not predict permalink canonical rebound when pretty permalinks are disabled.');
// Optional 404 log: repeat hits on a logged URL are written at most once a minute, never autoloaded.
$is_404 = true;
$writes = array();
function is_404(): bool { return $GLOBALS['is_404']; }
function esc_url_raw(string $url): string { return $url; }
function current_time(string $type): string { return gmdate('Y-m-d H:i:s'); }
/** @param mixed $value */
function update_option(string $name, $value, $autoload = null): bool {
    $GLOBALS['writes'][] = array($name, $autoload);
    $GLOBALS['options'][$name] = $value;
    return true;
}
$options['vonseowp_settings'] = array('enable_404_log' => 1);
$logger = new VonSEOWP_Redirects();
$_SERVER['REQUEST_URI'] = '/subdir/missing/';
$logger->log_404_error();
$logger->log_404_error();
$logger->log_404_error();
vonseowp_redirect_check(count($writes) === 1 && $writes[0] === array('vonseowp_404_log', false)
    && $options['vonseowp_404_log']['/subdir/missing/']['count'] === 1, 'A 404 flood on one URL must cause one non-autoloaded write per minute.');
$options['vonseowp_404_log']['/subdir/missing/']['ts'] -= 61;
$logger->log_404_error();
vonseowp_redirect_check(count($writes) === 2 && $options['vonseowp_404_log']['/subdir/missing/']['count'] === 2, 'Repeat hits after the throttle window must be counted.');
$_SERVER['REQUEST_URI'] = '/subdir/other-missing/';
$logger->log_404_error();
vonseowp_redirect_check(count($writes) === 3 && isset($options['vonseowp_404_log']['/subdir/other-missing/']), 'New URLs must always be logged.');
$options['vonseowp_404_log'] = array('/subdir/legacy/' => array('count' => 4, 'last_hit' => '2026-01-01 00:00:00'));
$_SERVER['REQUEST_URI'] = '/subdir/legacy/';
$logger->log_404_error();
vonseowp_redirect_check($options['vonseowp_404_log']['/subdir/legacy/']['count'] === 5, 'Entries saved before the throttle must keep counting.');
$is_404 = false;
echo 'Redirect tests passed (' . $assertions . ' assertions)' . PHP_EOL;
