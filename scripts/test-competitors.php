<?php
declare(strict_types=1);
define('ABSPATH', __DIR__);
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
function get_transient(string $key): array {
    $GLOBALS['cache_reads']++;
    return array('title' => 'Cached competitor fixture');
}
function wp_safe_remote_get(string $url, array $args): void {
    $GLOBALS['network_calls']++;
    throw new RuntimeException('Unexpected network request');
}
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
echo 'Competitor permission tests passed (' . $assertions . ' assertions)' . PHP_EOL;
