<?php
/** Portable regressions, or native APIs on a marker-owned disposable WordPress root. */
declare(strict_types=1);
$root = rtrim($argv[1] ?? '', '/\\');
$native = $root !== '';
if ($native) {
    $owner = is_file($root . '/qc-owner.json') ? json_decode((string) file_get_contents($root . '/qc-owner.json'), true) : null;
    if (!is_array($owner) || ($owner['root'] ?? '') !== realpath($root) || !preg_match('/^vonseoqc250_[a-f0-9]{8}$/D', $owner['database'] ?? '')) throw new RuntimeException('Use a marker-owned disposable WordPress installation.');
    require_once $root . '/wp-includes/plugin.php';
    add_filter('option_active_plugins', static function (array $plugins): array { return array_values(array_diff($plugins, array('vonseo/vonseo.php'))); });
    require $root . '/wp-load.php';
    if (DB_NAME !== $owner['database']) throw new RuntimeException('Database ownership mismatch.');
    wp_set_current_user(1);
} else {
    define('ABSPATH', __DIR__);
    $options = array('blogname' => 'Original title', 'blogdescription' => 'Original tagline');
    $filters = $actions = $transients = array();
    $user_id = 1;
    class WP_Error {
        private $code;
        private $message;
        public function __construct(string $code, string $message = '') { $this->code = $code; $this->message = $message; }
        public function get_error_code(): string { return $this->code; }
        public function get_error_message(): string { return $this->message; }
    }
    function is_wp_error($value): bool { return $value instanceof WP_Error; }
    function __(string $text, string $domain = ''): string { return $text; }
    function esc_html__(string $text, string $domain = ''): string { return $text; }
    function esc_html(string $text): string { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
    function add_action(string $hook, callable $callback, int $priority = 10, int $accepted = 1): void { $GLOBALS['actions'][$hook][$priority][] = array($callback, $accepted); }
    function remove_action(string $hook, callable $callback, int $priority = 10): void {
        foreach ($GLOBALS['actions'][$hook][$priority] ?? array() as $key => $entry) if ($entry[0] === $callback) unset($GLOBALS['actions'][$hook][$priority][$key]);
    }
    function add_filter(string $hook, callable $callback, int $priority = 10, int $accepted = 1): void { $GLOBALS['filters'][$hook][$priority][] = array($callback, $accepted); }
    function remove_filter(string $hook, callable $callback, int $priority = 10): void {
        foreach ($GLOBALS['filters'][$hook][$priority] ?? array() as $key => $entry) if ($entry[0] === $callback) unset($GLOBALS['filters'][$hook][$priority][$key]);
    }
    function has_filter(string $hook, callable $callback) {
        foreach ($GLOBALS['filters'][$hook] ?? array() as $priority => $entries) foreach ($entries as $entry) if ($entry[0] === $callback) return $priority;
        return false;
    }
    function apply_filters(string $hook, $value, ...$args) {
        $priorities = $GLOBALS['filters'][$hook] ?? array(); ksort($priorities);
        foreach ($priorities as $entries) foreach ($entries as $entry) $value = call_user_func_array($entry[0], array_slice(array_merge(array($value), $args), 0, $entry[1]));
        return $value;
    }
    function do_action(string $hook, ...$args): void {
        foreach ($GLOBALS['actions'][$hook] ?? array() as $entries) foreach ($entries as $entry) call_user_func_array($entry[0], array_slice($args, 0, $entry[1]));
    }
    function get_option(string $name, $default = false) { return $GLOBALS['options'][$name] ?? $default; }
    function update_option(string $name, $value): bool {
        $old = get_option($name);
        $exists = array_key_exists($name, $GLOBALS['options']);
        $value = apply_filters('sanitize_option_' . $name, $value);
        $value = apply_filters('pre_update_option_' . $name, $value, $old);
        if ($old === $value) return false;
        $GLOBALS['options'][$name] = $value;
        if ($exists) do_action('update_option_' . $name, $old, $value, $name);
        return true;
    }
    function delete_option(string $name): void { unset($GLOBALS['options'][$name]); }
    function register_setting(string $group, string $name, array $args): void { add_filter('sanitize_option_' . $name, $args['sanitize_callback']); }
    function sanitize_option(string $name, $value) { return apply_filters('sanitize_option_' . $name, $value); }
    function get_current_user_id(): int { return $GLOBALS['user_id']; }
    function wp_set_current_user(int $id): void { $GLOBALS['user_id'] = $id; }
    function current_user_can(string $capability): bool { return get_current_user_id() === 1 || get_current_user_id() === 2; }
    function get_post_types(array $args = array(), string $output = 'names'): array { return array_fill_keys(array('post', 'page'), (object) array('_builtin' => true)); }
    function get_taxonomies(array $args = array(), string $output = 'names'): array { return array_fill_keys(array('category', 'post_tag'), (object) array('_builtin' => true, 'public' => true, 'publicly_queryable' => true)); }
    function sanitize_key(string $text): string { return strtolower(preg_replace('/[^a-z0-9_\-]/i', '', $text)); }
    function sanitize_text_field(string $text): string { return trim(strip_tags($text)); }
    function sanitize_textarea_field(string $text): string { return sanitize_text_field($text); }
    function wp_slash($value) { return is_array($value) ? array_map('wp_slash', $value) : (is_string($value) ? addslashes($value) : $value); }
    function wp_unslash($value) { return is_array($value) ? array_map('wp_unslash', $value) : (is_string($value) ? stripslashes($value) : $value); }
    function wp_json_encode($value, int $flags = 0) { return json_encode($value, $flags); }
    function wp_parse_url(string $url) { return parse_url($url); }
    function esc_url_raw(string $url, array $protocols = array()): string { return $url; }
    function wp_generate_password(int $length, bool $special = true, bool $extra = false): string { return substr(bin2hex(random_bytes($length)), 0, $length); }
    function set_transient(string $key, $value, int $expiry): bool { $GLOBALS['transients'][$key] = $value; return true; }
    function get_transient(string $key) { return $GLOBALS['transients'][$key] ?? false; }
    function delete_transient(string $key): void { unset($GLOBALS['transients'][$key]); }
    function check_admin_referer(string $action): void { if (($GLOBALS['valid_nonce'] ?? false) !== true) throw new RuntimeException('nonce denied'); }
    function wp_die(string $message, string $title = '', array $args = array()): void { throw new RuntimeException($message); }
}
require_once dirname(__DIR__) . '/includes/class-vonseowp-content-types.php';
require_once dirname(__DIR__) . '/includes/class-vonseowp-taxonomies.php';
require_once dirname(__DIR__) . '/includes/class-vonseowp-admin.php';
require_once dirname(__DIR__) . '/includes/class-vonseowp-settings-transfer.php';
$admin = new VonSEOWP_Admin();
$admin->register_settings();
$transfer = new VonSEOWP_Settings_Transfer($admin);
$assertions = 0;
function vonseowp_transfer_check(bool $condition, string $message): void { $GLOBALS['assertions']++; if (!$condition) throw new RuntimeException($message); }
function vonseowp_transfer_json(array $settings): string { return (string) wp_json_encode(array('format' => 'vonseo-settings', 'version' => 1, 'settings' => (object) $settings)); }
$original = array(get_option('vonseowp_settings', null), get_option('blogname'), get_option('blogdescription'));
$callback = array($admin, 'sanitize_settings');
remove_filter('sanitize_option_vonseowp_settings', $callback);
update_option('vonseowp_settings', array('home_title' => 'Original title', 'home_desc' => 'Original tagline', 'enable_og' => 0,
    'google_verify' => 'keep-verification', 'enable_indexnow' => 1, 'canonical_host' => 'https://keep.example/', 'future_field' => 'keep-unknown',
    'toc_title' => 'Path C:\\docs and \'quotes\'', 'separator' => '|'));
add_filter('sanitize_option_vonseowp_settings', $callback);
try {
    $baseline = get_option('vonseowp_settings');
    $export = $transfer->export_document();
    vonseowp_transfer_check(!is_wp_error($export), 'Export failed.');
    $exported = (array) $export['settings'];
    foreach (array('google_verify', 'bing_verify', 'enable_indexnow', 'vonseowp_indexnow_key', 'future_field', 'canonical_host') as $key) vonseowp_transfer_check(!array_key_exists($key, $exported), 'Excluded setting was exported: ' . $key);
    vonseowp_transfer_check($exported['enable_og'] === 0 && $exported['enable_sitemap'] === 1, 'Effective ON/OFF defaults changed.');
    $large = $exported;
    $large['robots_txt'] = str_repeat('r', 65536);
    $large['llms_txt_content'] = '';
    $large['llms_txt_content'] = str_repeat('l', VonSEOWP_Settings_Transfer::MAX_BYTES - strlen(vonseowp_transfer_json($large)) - 1);
    vonseowp_transfer_check(strlen(vonseowp_transfer_json($large)) <= VonSEOWP_Settings_Transfer::MAX_BYTES, 'Boundary fixture must fit as compact JSON.');
    remove_filter('sanitize_option_vonseowp_settings', $callback);
    update_option('vonseowp_settings', array_replace($baseline, $large));
    add_filter('sanitize_option_vonseowp_settings', $callback);
    vonseowp_transfer_check(is_wp_error($transfer->export_document()), 'Export must bound the actual formatted download, not just compact JSON.');
    remove_filter('sanitize_option_vonseowp_settings', $callback);
    update_option('vonseowp_settings', $baseline);
    add_filter('sanitize_option_vonseowp_settings', $callback);
    $pending = $transfer->stage((string) wp_json_encode($export));
    vonseowp_transfer_check(!is_wp_error($pending) && get_option('vonseowp_settings') === $baseline, 'Preview wrote settings.');
    vonseowp_transfer_check($transfer->apply($pending['token']) === true, 'Round trip failed.');
    $roundtrip = get_option('vonseowp_settings');
    foreach ($exported as $key => $value) vonseowp_transfer_check($roundtrip[$key] === $value, 'Round trip changed: ' . $key);
    foreach (array('google_verify', 'enable_indexnow', 'canonical_host', 'future_field') as $key) vonseowp_transfer_check($roundtrip[$key] === $baseline[$key], 'Excluded setting changed: ' . $key);
    vonseowp_transfer_check(is_wp_error($transfer->apply($pending['token'])), 'Preview replay succeeded.');
    $valid = vonseowp_transfer_json(array('home_title' => 'Imported & title', 'home_desc' => 'Imported description', 'enable_og' => 1));
    $before = get_option('vonseowp_settings');
    $bad = array('', '{}', '[]', 'null', '{', str_repeat('x', 131073),
        str_replace('"version":1', '"version":2', $valid), str_replace('"version":1', '"version":"1"', $valid),
        str_replace('"format":"vonseo-settings"', '"format":"other"', $valid),
        vonseowp_transfer_json(array()), vonseowp_transfer_json(array('home_title' => array('bad'))),
        vonseowp_transfer_json(array('enable_og' => '1')), vonseowp_transfer_json(array('enable_og' => true)),
        vonseowp_transfer_json(array('google_verify' => 'forbidden')), vonseowp_transfer_json(array('unknown' => 'bad')),
        vonseowp_transfer_json(array('default_image' => 'javascript:alert(1)')), vonseowp_transfer_json(array('default_image' => 'https://user:pass@example.invalid/')),
        vonseowp_transfer_json(array('seo_post_types' => array('not_available'))), vonseowp_transfer_json(array('seo_taxonomies' => array(array('bad')))),
        vonseowp_transfer_json(array('toc_min_headings' => 11)), vonseowp_transfer_json(array('toc_position' => 'bottom')),
        vonseowp_transfer_json(array('home_title' => "null\0byte")), vonseowp_transfer_json(array('separator' => 'invalid')));
    foreach ($bad as $index => $json) {
        vonseowp_transfer_check(is_wp_error($transfer->stage($json)), 'Invalid payload accepted: ' . $index);
        vonseowp_transfer_check(get_option('vonseowp_settings') === $before, 'Rejected payload changed settings: ' . $index);
    }
    $pending = $transfer->stage($valid);
    if (!$native) wp_set_current_user(2); else { $other = username_exists('vonseo_transfer_other') ?: wp_create_user('vonseo_transfer_other', wp_generate_password(24)); wp_set_current_user($other); }
    vonseowp_transfer_check($transfer->pending($pending['token']) === array(), 'Another user accessed preview.');
    wp_set_current_user(0);
    vonseowp_transfer_check(is_wp_error($transfer->export_document()) && is_wp_error($transfer->stage($valid)) && is_wp_error($transfer->apply($pending['token'])), 'Unauthorized user accessed transfer.');
    wp_set_current_user(1);
    $pending = $transfer->stage($valid);
    update_option('blogname', 'Concurrent title');
    $concurrent = get_option('vonseowp_settings');
    vonseowp_transfer_check(is_wp_error($transfer->apply($pending['token'])) && get_option('vonseowp_settings') === $concurrent, 'Stale preview overwrote concurrent settings.');
    $pending = $transfer->stage($valid);
    $expired = $pending; $expired['expires'] = time() - 1;
    set_transient('vonseowp_settings_import_1', $expired, 600);
    vonseowp_transfer_check(is_wp_error($transfer->apply($pending['token'])), 'Expired preview applied.');
    $before = get_option('vonseowp_settings');
    $block = static function ($new, $old) { return ($new['home_title'] ?? '') === 'Imported & title' ? $old : $new; };
    add_filter('pre_update_option_vonseowp_settings', $block, 99, 2);
    $pending = $transfer->stage($valid);
    vonseowp_transfer_check(is_wp_error($transfer->apply($pending['token'])) && get_option('vonseowp_settings') === $before, 'Failed write did not retain settings.');
    remove_filter('pre_update_option_vonseowp_settings', $block, 99);
    $before_title = get_option('blogname');
    $blocked_title = sanitize_option('blogname', 'Imported & title');
    $block_title = static function ($new, $old) use ($blocked_title) { return $new === $blocked_title ? $old : $new; };
    add_filter('pre_update_option_blogname', $block_title, 99, 2);
    $pending = $transfer->stage($valid);
    $result = $transfer->apply($pending['token']);
    vonseowp_transfer_check(is_wp_error($result) && $result->get_error_code() === 'save' && get_option('vonseowp_settings') === $before && get_option('blogname') === $before_title, 'Partial core-sync failure did not roll back.');
    remove_filter('pre_update_option_blogname', $block_title, 99);
    $pending = $transfer->stage($valid);
    vonseowp_transfer_check($transfer->apply($pending['token']) === true && get_option('blogname') === sanitize_option('blogname', 'Imported & title') && get_option('blogdescription') === sanitize_option('blogdescription', 'Imported description'), 'Confirmed import/core identity sync failed.');
    vonseowp_transfer_check(has_filter('sanitize_option_vonseowp_settings', $callback) !== false, 'Form sanitizer was not restored.');
    delete_option('vonseowp_settings');
    $pending = $transfer->stage($valid);
    vonseowp_transfer_check($transfer->apply($pending['token']) === true && get_option('blogname') === sanitize_option('blogname', 'Imported & title'), 'First-ever settings import must synchronize WordPress identity too.');
    if (!$native) {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        foreach (array('export_request', 'preview_request', 'apply_request') as $method) {
            try { $transfer->$method(); throw new LogicException('GET endpoint allowed'); } catch (RuntimeException $error) { vonseowp_transfer_check(strpos($error->getMessage(), 'POST') !== false, 'GET was not rejected.'); }
        }
        $_SERVER['REQUEST_METHOD'] = 'POST';
        foreach (array('export_request', 'preview_request', 'apply_request') as $method) {
            try { $transfer->$method(); throw new LogicException('Bad nonce allowed'); } catch (RuntimeException $error) { vonseowp_transfer_check($error->getMessage() === 'nonce denied', 'Nonce was not checked.'); }
        }
    }
} finally {
    remove_filter('sanitize_option_vonseowp_settings', $callback);
    remove_action('update_option_vonseowp_settings', array($admin, 'sync_options_to_wp'), 10);
    remove_action('update_option_blogname', array($admin, 'sync_title_from_wp'), 10);
    remove_action('update_option_blogdescription', array($admin, 'sync_tagline_from_wp'), 10);
    if ($original[0] === null) delete_option('vonseowp_settings'); else update_option('vonseowp_settings', $original[0]);
    update_option('blogname', $original[1]); update_option('blogdescription', $original[2]);
    delete_transient('vonseowp_settings_import_1');
}
echo 'Settings transfer tests passed (' . $assertions . ' assertions; ' . ($native ? 'native WordPress' : 'portable model') . ', PHP ' . PHP_VERSION . ').' . PHP_EOL;
