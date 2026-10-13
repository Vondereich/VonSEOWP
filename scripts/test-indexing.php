<?php
declare(strict_types=1);
define('ABSPATH', __DIR__);
$options = array('blog_public' => 1, 'vonseowp_indexnow_key' => 'fixture-key', 'vonseowp_indexnow_file' => 'fixture-key.txt');
$posts = array();
$meta = array();
$requests = array();
$events = array();
$actions = array();
$assertions = 0;
/** @param mixed $default @return mixed */
function get_option(string $name, $default = false) { return $GLOBALS['options'][$name] ?? $default; }
function add_action(string $hook, callable $callback, int $priority = 10, int $args = 1): void { $GLOBALS['actions'][$hook] = array($callback, $priority, $args); }
function add_filter(string $hook, callable $callback, int $priority = 10, int $args = 1): void {}
/** @return array<string, object> */
function get_post_types(array $args = array(), string $output = 'names'): array {
    return array('post' => (object) array('_builtin' => true), 'page' => (object) array('_builtin' => true),
        'book' => (object) array('_builtin' => false, 'public' => true, 'publicly_queryable' => true));
}
function sanitize_key(string $key): string { return strtolower(preg_replace('/[^a-z0-9_-]/i', '', $key)); }
/** @return object|null */
function get_post(int $id) { return $GLOBALS['posts'][$id] ?? null; }
/** @return mixed */
function get_post_meta(int $id, string $key, bool $single = false) { return $GLOBALS['meta'][$id][$key] ?? ''; }
function wp_is_post_revision(int $id): bool { return isset($GLOBALS['posts'][$id]) && $GLOBALS['posts'][$id]->post_type === 'revision'; }
function wp_is_post_autosave(int $id): bool { return !empty($GLOBALS['posts'][$id]->autosave); }
/** @return string|false */
function get_permalink(int $id) { return isset($GLOBALS['posts'][$id]) ? 'https://example.test/post-' . $id . '/' : false; }
function url_to_postid(string $url): int {
    if (parse_url($url, PHP_URL_HOST) !== 'example.test') return 0;
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
    if (isset($query['p']) && is_scalar($query['p'])) return (int) $query['p'];
    return preg_match('~/post-(\d+)/~', $url, $match) ? (int) $match[1] : 0;
}
function home_url(string $path = ''): string { return 'https://example.test' . $path; }
/** @return mixed */
function wp_parse_url(string $url, int $component = -1) { return parse_url($url, $component); }
/** @return string|false */
function wp_json_encode(array $value) { return json_encode($value); }
function wp_remote_post(string $url, array $args): array { $GLOBALS['requests'][] = array($url, $args); return array(); }
/** @return int|false */
function wp_next_scheduled(string $hook, array $args = array()) { return $GLOBALS['events'][$hook][$args[0]] ?? false; }
function wp_schedule_single_event(int $time, string $hook, array $args = array()): bool { $GLOBALS['events'][$hook][$args[0]] = $time; return true; }
function wp_clear_scheduled_hook(string $hook, array $args = array()): int { unset($GLOBALS['events'][$hook][$args[0]]); return 1; }
/** @return mixed */
function get_query_var(string $name, $default = '') { return $name === 'vonseowp_llm' ? 1 : $default; }
function status_header(int $code): void { if ($code !== 404) throw new RuntimeException('Expected native 404 branch.'); }
function nocache_headers(): void {}
function get_query_template(string $name): string { return __DIR__ . '/fixtures/vonseowp-qc-llm-404.php'; }
function vonseowp_indexing_check(bool $condition, string $message): void { $GLOBALS['assertions']++; if (!$condition) throw new RuntimeException($message); }
require_once dirname(__DIR__) . '/includes/class-vonseowp-content-types.php';
require_once dirname(__DIR__) . '/includes/class-vonseowp-indexnow.php';
require_once dirname(__DIR__) . '/includes/class-vonseowp-llm.php';

if (($argv[1] ?? '') === 'llm-denied') {
    $options['blog_public'] = 0;
    $options['vonseowp_settings'] = array('enable_llms_txt' => 1, 'llms_txt_content' => 'ProtectedCuratedSentinel');
    $GLOBALS['wp_query'] = new class { public function set_404(): void {} };
    (new VonSEOWP_LLM())->render_llms_txt();
    throw new RuntimeException('The renderer must terminate the denied response.');
}
$posts[1] = (object) array('ID' => 1, 'post_type' => 'post', 'post_status' => 'publish', 'post_password' => '');
$engine = new VonSEOWP_IndexNow();
(new VonSEOWP_LLM());
vonseowp_indexing_check($actions['template_redirect'][1] < 10, 'Serve the literal llms.txt route before native canonical slash redirects.');
// Legitimate explicit enablement must still send only the canonical URL.
$options['vonseowp_settings'] = array('enable_indexnow' => 1);
$engine->submit_url('https://example.test/post-1/');
vonseowp_indexing_check(count($requests) === 2, 'An eligible explicit public post must notify both endpoints.');
$requests = array();
$options['vonseowp_settings'] = array();
$engine->auto_submit_url(1, $posts[1], true);
vonseowp_indexing_check(!$requests && !$events, 'Absent enablement must not send or queue external publication.');
foreach (array(0, '0', false, 'yes', 2, array(1)) as $value) {
    $options['vonseowp_settings'] = array('enable_indexnow' => $value);
    $engine->submit_url('https://example.test/post-1/');
    vonseowp_indexing_check(!$requests, 'Malformed or disabled enablement must not send.');
}
$options['vonseowp_settings'] = array('enable_indexnow' => 1);
vonseowp_indexing_check(isset($actions['wp_after_insert_post']) && !isset($actions['save_post']), 'Automatic submission must run after persisted post metadata.');
$engine->auto_submit_url(1, $posts[1], true);
vonseowp_indexing_check(!$requests && count($events['vonseowp_indexnow_submit']) === 1, 'Automatic publication must be deferred.');
$time = $events['vonseowp_indexnow_submit'][1];
vonseowp_indexing_check($time >= time() + 59, 'Give legacy block-editor metadata a separate save window.');
$engine->auto_submit_url(1, $posts[1], true);
vonseowp_indexing_check(count($events['vonseowp_indexnow_submit']) === 1 && $events['vonseowp_indexnow_submit'][1] === $time, 'Repeated saves must deduplicate queued work.');
$engine->submit_post(1);
vonseowp_indexing_check(count($requests) === 2, 'Eligible queued work must retain the two endpoint notifications.');
$payload = json_decode($requests[0][1]['body'], true);
vonseowp_indexing_check($payload['urlList'] === array(get_permalink(1)) && $requests[0][1]['blocking'] === false, 'Retain the asynchronous canonical payload.');
$requests = array();
foreach (array('password' => 'secret', 'zero_password' => '0', 'noindex' => '1', 'private' => 'private', 'draft' => 'draft',
    'disabled_cpt' => 'book', 'revision' => 'revision', 'autosave' => true, 'empty_selection' => array(), 'private_site' => 0) as $case => $value) {
    $post = clone $posts[1];
    $options['blog_public'] = 1;
    $options['vonseowp_settings'] = array('enable_indexnow' => 1);
    $meta = array();
    if ($case === 'password' || $case === 'zero_password') $post->post_password = $value;
    if ($case === 'noindex') $meta[1]['_vonseowp_noindex'] = $value;
    if ($case === 'private' || $case === 'draft') $post->post_status = $value;
    if ($case === 'disabled_cpt' || $case === 'revision') $post->post_type = $value;
    if ($case === 'autosave') $post->autosave = $value;
    if ($case === 'empty_selection') $options['vonseowp_settings']['seo_post_types'] = $value;
    if ($case === 'private_site') $options['blog_public'] = $value;
    $posts[1] = $post;
    $events['vonseowp_indexnow_submit'][1] = time() + 60;
    $engine->auto_submit_url(1, $post, true);
    $engine->submit_post(1);
    $engine->submit_url('https://example.test/?p=1&private-token=sentinel#private');
    vonseowp_indexing_check(!$requests && !isset($events['vonseowp_indexnow_submit'][1]), 'All entry points must block and cancel ineligible work: ' . $case);
    $posts[1] = (object) array('ID' => 1, 'post_type' => 'post', 'post_status' => 'publish', 'post_password' => '');
}
$options['blog_public'] = 1;
$meta = array();
$options['vonseowp_settings'] = array('enable_indexnow' => '1', 'seo_post_types' => array('book'), 'sitemap_posts' => 0);
$posts[1]->post_type = 'book';
$engine->submit_url('https://example.test/?p=1&private-token=sentinel#private');
vonseowp_indexing_check(count($requests) === 2 && json_decode($requests[0][1]['body'], true)['urlList'] === array(get_permalink(1)), 'Opted-in CPT aliases must send canonical URLs without attached query secrets.');
$requests = array();
foreach (array('https://external.test/?p=1', 'https://example.test/unknown/', 'https://example.test/?p=999', '') as $url) {
    $engine->submit_url($url);
    vonseowp_indexing_check(!$requests, 'Unknown, external and empty URLs must not reach the sink.');
}
$process = proc_open(array(PHP_BINARY, __FILE__, 'llm-denied'), array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
if (!is_resource($process)) throw new RuntimeException('Cannot run LLM denied-response test.');
fclose($pipes[0]);
$output = stream_get_contents($pipes[1]);
$errors = stream_get_contents($pipes[2]);
fclose($pipes[1]); fclose($pipes[2]);
vonseowp_indexing_check(proc_close($process) === 0 && $errors === '' && $output === 'QC_404_ONLY', 'Private LLM output must terminate in the 404 template without curated content.');
echo 'Indexing policy tests passed (' . $assertions . ' assertions)' . PHP_EOL;
