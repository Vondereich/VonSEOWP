<?php
declare(strict_types=1);
define('ABSPATH', __DIR__);
define('HOUR_IN_SECONDS', 3600);
$options = array('vonseowp_settings' => array('seo_post_types' => array('post', 'page', 'book')));
$posts = array(
    101 => (object) array('ID' => 101, 'post_type' => 'book'),
    102 => (object) array('ID' => 102, 'post_type' => 'book'),
    103 => (object) array('ID' => 103, 'post_type' => 'post'),
);
$allowed_ids = array(101);
$edit_posts = false;
$nonce_valid = true;
$network_calls = 0;
$cache_reads = 0;
$assertions = 0;
class VonSEOWP_Test_Json_Response extends RuntimeException {
    public bool $success;
    public array $data;
    public function __construct(bool $success, array $data) {
        parent::__construct('Captured JSON response');
        $this->success = $success;
        $this->data = $data;
    }
}
/** @param callable $callback */
function add_action(string $hook, $callback): void {}
function __(string $text, string $domain = ''): string { return $text; }
function check_ajax_referer(string $action, string $field): void {
    if (!$GLOBALS['nonce_valid']) throw new RuntimeException('Invalid nonce');
}
function current_user_can(string $capability, int $id = 0): bool {
    return $capability === 'edit_post' ? in_array($id, $GLOBALS['allowed_ids'], true) : $GLOBALS['edit_posts'];
}
/** @param mixed $default */
function get_option(string $name, $default = false) { return $GLOBALS['options'][$name] ?? $default; }
function get_post(int $id = 0): ?object { return $GLOBALS['posts'][$id] ?? null; }
function get_post_types(array $args = array(), string $output = 'names'): array {
    return array('post' => (object) array(), 'page' => (object) array(),
        'book' => (object) array('_builtin' => false, 'public' => true, 'publicly_queryable' => true));
}
function sanitize_key(string $key): string { return strtolower(preg_replace('/[^a-z0-9_\-]/i', '', $key)); }
/** @param mixed $value */
function wp_unslash($value) { return is_array($value) ? array_map('wp_unslash', $value) : (is_string($value) ? stripslashes($value) : $value); }
function esc_url_raw(string $url): string { return $url; }
function sanitize_text_field(string $text): string { return trim(strip_tags($text)); }
function wp_send_json_error(array $data): void { throw new VonSEOWP_Test_Json_Response(false, $data); }
function wp_send_json_success(array $data): void { throw new VonSEOWP_Test_Json_Response(true, $data); }
/** @return array|false */
function get_transient(string $key) {
    $GLOBALS['cache_reads']++;
    $GLOBALS['cache_keys'][] = $key;
    return $GLOBALS['cache_miss'] ?? false ? false : array('title' => 'Cached competitor fixture');
}
/** @param mixed $value */
function set_transient(string $key, $value, int $expiry): bool { $GLOBALS['cache_writes'][] = $key; return true; }
/** @return array */
function wp_safe_remote_get(string $url, array $args) {
    $GLOBALS['network_calls']++;
    $GLOBALS['fetched'][] = $url;
    if (($args['redirection'] ?? null) !== 0) throw new RuntimeException('Redirects must be followed by the plugin.');
    if (!isset($GLOBALS['remote'])) throw new RuntimeException('Unexpected network request');
    return is_callable($GLOBALS['remote']) ? call_user_func($GLOBALS['remote'], $url) : $GLOBALS['remote'];
}
/** @return mixed */
function wp_parse_url(string $url, int $component = -1) { return parse_url($url, $component); }
class WP_Http {
    public static function make_absolute_url(string $location, string $base): string {
        if (preg_match('#^https?://#i', $location)) return $location;
        $parts = parse_url($base);
        return $parts['scheme'] . '://' . $parts['host'] . '/' . ltrim($location, '/');
    }
}
class WP_Error {
    private string $message;
    public function __construct(string $code, string $message) { $this->message = $message; }
    public function get_error_message(): string { return $this->message; }
}
/** @param mixed $value */
function is_wp_error($value): bool { return $value instanceof WP_Error; }
function wp_remote_retrieve_response_code(array $response): int { return $response['code']; }
function wp_remote_retrieve_header(array $response, string $name): string { return $name === 'location' ? ($response['location'] ?? '') : 'text/html; charset=UTF-8'; }
function wp_remote_retrieve_body(array $response): string { return $response['body']; }
require_once dirname(__DIR__) . '/includes/class-vonseowp-content-types.php';
require_once dirname(__DIR__) . '/includes/class-vonseowp-competitors.php';
function vonseowp_comp_check(bool $condition, string $message): void {
    $GLOBALS['assertions']++;
    if (!$condition) throw new RuntimeException($message);
}
function vonseowp_comp_request(array $input): VonSEOWP_Test_Json_Response {
    $_POST = $input;
    try { (new VonSEOWP_Competitors())->ajax_scan_competitor(); }
    catch (VonSEOWP_Test_Json_Response $response) { return $response; }
    throw new RuntimeException('No JSON response');
}
$valid = array('post_id' => '101', 'url' => 'https://competitor.invalid/');
$response = vonseowp_comp_request($valid);
vonseowp_comp_check($response->success && $response->data['title'] === 'Cached competitor fixture',
    'A selected CPT-only editor must be allowed without edit_posts.');
foreach (array(null, '', '0', '-101', '101junk', array('101'), '999', '102') as $id) {
    $input = $valid;
    if ($id === null) unset($input['post_id']); else $input['post_id'] = $id;
    $reads = $cache_reads;
    vonseowp_comp_check(!vonseowp_comp_request($input)->success && $cache_reads === $reads,
        'Missing/invalid/uneditable post IDs must be rejected before cache/network access.');
}
$edit_posts = true;
$allowed_ids = array();
vonseowp_comp_check(!vonseowp_comp_request($valid)->success, 'edit_posts alone must not authorize an uneditable CPT.');
$allowed_ids = array(101);
$options['vonseowp_settings']['seo_post_types'] = array('post', 'page');
vonseowp_comp_check(!vonseowp_comp_request($valid)->success, 'Disabled types must not use editor tools.');
$options['vonseowp_settings']['seo_post_types'][] = 'book';
foreach (array('', array('https://competitor.invalid/')) as $url) {
    vonseowp_comp_check(!vonseowp_comp_request(array('post_id' => '101', 'url' => $url))->success,
        'Malformed URL fields must produce a controlled JSON error.');
}
$nonce_valid = false;
try { vonseowp_comp_request($valid); throw new LogicException('Invalid nonce was accepted'); }
catch (RuntimeException $error) { vonseowp_comp_check($error->getMessage() === 'Invalid nonce', 'Nonce must still be required.'); }
$nonce_valid = true;
$allowed_ids = array(103);
vonseowp_comp_check(vonseowp_comp_request(array('post_id' => '103', 'url' => $valid['url']))->success,
    'An authorized core-post editor must retain access.');
vonseowp_comp_check($network_calls === 0, 'Authorization regression tests must not make network requests.');
// Non-2xx pages must never be analyzed or cached (MET-02).
$allowed_ids = array(101);
$cache_miss = true;
$cache_keys = array();
$cache_writes = array();
$page = '<html><head><title>T</title><script>var hidden = "script words";</script></head><body><h1>Head</h1><p>Visible words here.</p><p>Second block</p></body></html>';
foreach (array(404, 500, 301) as $code) {
    $remote = array('code' => $code, 'body' => $page);
    $response = vonseowp_comp_request($valid);
    vonseowp_comp_check(!$response->success && strpos($response->data["message"] ?? "", (string) $code) !== false && !$cache_writes, json_encode($response->data) .
        'HTTP ' . $code . ' competitor pages must be rejected without caching.');
}
$remote = array('code' => 200, 'body' => $page);
$response = vonseowp_comp_request($valid);
vonseowp_comp_check($response->success && $response->data['word_count'] === 6 && $response->data['h1_count'] === 1,
    'Successful pages must count visible words only and keep adjacent blocks separate: ' . json_encode($response->data));
vonseowp_comp_check(strpos(end($cache_keys), 'vonseowp_comp_v2_') === 0 && count($cache_writes) === 1,
    'Metric changes must use a new cache key version.');
unset($remote);
$cache_miss = false;
$network_calls = 0;
// Shared metric parity with the editor (admin/js/vonseowp-analyzer.js textMetrics()).
$samples = array('', '   ', 'One.', 'The quick brown fox jumps over the lazy dog. It was a fine day!',
    'Saya suka makan nasi lemak. Sedap sekali!', 'Café déjà vu — naïve résumé... 2026 is here?', "Line one
line two	three. Done");
$node = getenv('NODE_BINARY') ?: 'node';
$script = 'const a=require(' . json_encode(dirname(__DIR__) . '/admin/js/vonseowp-analyzer.js') . ');'
    . 'process.stdout.write(JSON.stringify(JSON.parse(require("fs").readFileSync(0,"utf8")).map(t=>a.textMetrics(t))));';
$process = proc_open(array($node, '-e', $script), array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
if (!is_resource($process)) throw new RuntimeException('Cannot run Node for metric parity.');
fwrite($pipes[0], (string) json_encode($samples));
fclose($pipes[0]);
$js = json_decode((string) stream_get_contents($pipes[1]), true);
$js_errors = (string) stream_get_contents($pipes[2]);
fclose($pipes[1]); fclose($pipes[2]); proc_close($process);
vonseowp_comp_check(is_array($js) && count($js) === count($samples), 'Node metric run failed: ' . $js_errors);
foreach ($samples as $index => $sample) {
    $php = VonSEOWP_Competitors::text_metrics($sample);
    vonseowp_comp_check($php['word_count'] === $js[$index]['wordCount'] && $php['reading_ease'] === $js[$index]['readingEase'],
        'PHP and editor metrics must match for: ' . json_encode($sample) . ' php=' . json_encode($php) . ' js=' . json_encode($js[$index]));
}
vonseowp_comp_check(VonSEOWP_Competitors::text_metrics('a an the is')['word_count'] === 4, 'Short words must count as words.');
function vonseowp_comp_visible(string $html): string {
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8"><html><body>' . $html . '</body></html>');
    libxml_clear_errors();
    return VonSEOWP_Competitors::visible_text($dom);
}
foreach (array(
    '<p>One <b>Wor</b>d here</p>' => 3, '<p>a</p><p>b</p>' => 2, 'x<br>y' => 2, '<ul><li>a</li><li>b</li></ul>' => 2,
    '<p>Visible</p><script>hidden words</script><style>.x{}</style>' => 1, '<span>in</span><em>line</em>' => 1,
) as $html => $words) {
    vonseowp_comp_check(VonSEOWP_Competitors::text_metrics(vonseowp_comp_visible($html))['word_count'] === $words,
        'Visible text must split only at block boundaries: ' . $html);
}
// The editor-side block list must match the server-side list exactly.
$metabox = (string) file_get_contents(dirname(__DIR__) . '/admin/js/vonseowp-metabox.js');
preg_match('/const BLOCK_TAGS =\s*((?:"[^"]*"\s*\+?\s*)+);/', $metabox, $js_tags);
$js_list = array_map('trim', explode(',', implode('', array_map(static function ($part) { return trim($part, '"'); },
    preg_split('/"\s*\+\s*"/', trim($js_tags[1] ?? '', " \n")) ?: array()))));
$js_list = array_values(array_filter($js_list, 'strlen'));
vonseowp_comp_check($js_list === VonSEOWP_Competitors::BLOCK_TAGS, 'JS and PHP block tag lists diverged: ' . json_encode($js_list));
// Reserved networks (older WordPress does not block link-local/CGNAT) and every redirect hop.
foreach (array('http://169.254.169.254/latest/meta-data/', 'http://100.64.0.1/', 'http://10.0.0.5/', 'http://127.0.0.1/',
    'http://[::1]/', 'http://[fd00::1]/', 'http://localhost/', 'http://app.localhost/', 'ftp://example.com/', 'http://224.0.0.1/') as $blocked) {
    vonseowp_comp_check(!VonSEOWP_Competitors::is_allowed_target($blocked), 'Reserved targets must be rejected: ' . $blocked);
}
foreach (array('http://8.8.8.8/', 'https://93.184.215.14/page') as $public) {
    vonseowp_comp_check(VonSEOWP_Competitors::is_allowed_target($public), 'Public IPv4 targets must be allowed: ' . $public);
}
foreach (array('fe80::1' => true, 'fc12::1' => true, '::ffff:169.254.1.1' => true, '::ffff:8.8.8.8' => false, '2001:4860:4860::8888' => false) as $ip => $reserved) {
    vonseowp_comp_check(VonSEOWP_Competitors::is_reserved_ipv6($ip) === $reserved, 'IPv6 classification failed: ' . $ip);
}
$cache_miss = true;
$cache_writes = array();
$fetched = array();
$remote = static function (string $url): array {
    return $url === 'http://8.8.8.8/start' ? array('code' => 302, 'location' => 'http://169.254.169.254/latest/', 'body' => '')
        : array('code' => 200, 'body' => '<html><body><p>Should not be reached</p></body></html>');
};
$response = vonseowp_comp_request(array('post_id' => '101', 'url' => 'http://8.8.8.8/start'));
vonseowp_comp_check(!$response->success && $fetched === array('http://8.8.8.8/start') && !$cache_writes,
    'A redirect into a reserved network must be refused before the second request.');
$fetched = array();
$remote = static function (string $url): array {
    return $url === 'http://8.8.8.8/a' ? array('code' => 301, 'location' => '/b', 'body' => '')
        : array('code' => 200, 'body' => '<html><body><p>Two words</p></body></html>');
};
$response = vonseowp_comp_request(array('post_id' => '101', 'url' => 'http://8.8.8.8/a'));
vonseowp_comp_check($response->success && $fetched === array('http://8.8.8.8/a', 'http://8.8.8.8/b') && $response->data['word_count'] === 2,
    'Safe relative redirects must still be followed.');
$fetched = array();
$remote = static function (string $url): array { return array('code' => 302, 'location' => $url . 'x', 'body' => ''); };
$response = vonseowp_comp_request(array('post_id' => '101', 'url' => 'http://8.8.8.8/loop'));
vonseowp_comp_check(!$response->success && count($fetched) === 4, 'Redirect chains must stop after three hops.');
unset($remote);
$cache_miss = false;
$network_calls = 0;
echo 'Competitor permission tests passed (' . $assertions . ' assertions)' . PHP_EOL;
