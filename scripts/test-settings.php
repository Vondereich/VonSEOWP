<?php
/** Portable settings lifecycle regressions; native database coverage is separate. */
declare(strict_types=1);
define('ABSPATH', __DIR__);
$options = array('blogname' => 'Old title', 'blogdescription' => 'Old tagline');
$actions = array();
$sanitizers = array();
$assertions = 0;
function add_action(string $hook, callable $callback, int $priority = 10, int $accepted = 1): void {
    $GLOBALS['actions'][$hook][] = array($callback, $accepted);
}
function remove_action(string $hook, callable $callback, int $priority = 10): void {
    foreach ($GLOBALS['actions'][$hook] ?? array() as $key => $entry) {
        if ($entry[0] === $callback) unset($GLOBALS['actions'][$hook][$key]);
    }
}
/** @param mixed ...$args */
function do_action(string $hook, ...$args): void {
    foreach ($GLOBALS['actions'][$hook] ?? array() as $entry) call_user_func_array($entry[0], array_slice($args, 0, $entry[1]));
}
function register_setting(string $group, string $name, array $args): void { $GLOBALS['sanitizers'][$name] = $args['sanitize_callback']; }
/** @param mixed $default @return mixed */
function get_option(string $name, $default = false) { return $GLOBALS['options'][$name] ?? $default; }
/** @param mixed $value */
function update_option(string $name, $value): bool {
    $old = get_option($name);
    $exists = array_key_exists($name, $GLOBALS['options']);
    if (isset($GLOBALS['sanitizers'][$name])) {
        $value = call_user_func($GLOBALS['sanitizers'][$name], $value);
        if (!$exists) $value = call_user_func($GLOBALS['sanitizers'][$name], $value);
    }
    $GLOBALS['options'][$name] = $value;
    if ($exists && $value !== $old) do_action('update_option_' . $name, $old, $value, $name);
    return $value !== $old;
}
function get_post_types(array $args = array(), string $output = 'names'): array {
    return array('post' => (object) array('_builtin' => true), 'page' => (object) array('_builtin' => true));
}
function sanitize_key(string $text): string { return strtolower($text); }
/** @param mixed $value @return mixed */
function wp_unslash($value) { return is_array($value) ? array_map('wp_unslash', $value) : (is_string($value) ? stripslashes($value) : $value); }
function sanitize_text_field(string $text): string { return trim(strip_tags($text)); }
function sanitize_textarea_field(string $text): string { return sanitize_text_field($text); }
function esc_url_raw(string $text): string { return $text; }
function vonseowp_settings_check(bool $condition, string $message): void {
    $GLOBALS['assertions']++;
    if (!$condition) throw new RuntimeException($message);
}
require dirname(__DIR__) . '/includes/class-vonseowp-content-types.php';
require dirname(__DIR__) . '/includes/class-vonseowp-admin.php';
$admin = new VonSEOWP_Admin();
do_action('admin_init');
$flags = array('enable_og', 'enable_sitemap', 'sitemap_posts', 'sitemap_pages', 'enable_404_log',
    'auto_image_alt', 'auto_image_title', 'remove_category_base', 'redirect_attachments', 'enable_indexnow',
    'enable_rss_footer', 'enable_toc', 'enable_llms_txt');
$raw = array('seo_post_types' => array('post', 'page'), 'home_title' => 'Old title', 'home_desc' => 'Old tagline');
$once = $admin->sanitize_settings($raw);
vonseowp_settings_check($admin->sanitize_settings($once) === $once, 'Sanitization must be idempotent, including saved OFF flags.');
foreach (array(0, '0', false, null, '', 'false', 'yes', 'on', 2, '2', array(1)) as $value) {
    $result = $admin->sanitize_settings(array_merge($raw, array_fill_keys($flags, $value)));
    foreach ($flags as $flag) vonseowp_settings_check($result[$flag] === 0, 'Disabled/malformed flag became enabled: ' . $flag);
}
foreach (array(1, '1', true) as $value) {
    $result = $admin->sanitize_settings(array_merge($raw, array_fill_keys($flags, $value)));
    foreach ($flags as $flag) vonseowp_settings_check($result[$flag] === 1, 'Explicit checked value must remain enabled: ' . $flag);
    vonseowp_settings_check($admin->sanitize_settings($result) === $result, 'Enabled settings must also be idempotent.');
}
update_option('vonseowp_settings', $raw);
foreach ($flags as $flag) vonseowp_settings_check(get_option('vonseowp_settings')[$flag] === 0, 'First-save double sanitization enabled: ' . $flag);
update_option('blogname', 'New title');
update_option('blogdescription', 'New tagline');
$saved = get_option('vonseowp_settings');
foreach ($flags as $flag) vonseowp_settings_check($saved[$flag] === 0, 'Core title/tagline synchronization enabled: ' . $flag);
vonseowp_settings_check($saved['home_title'] === 'New title' && $saved['home_desc'] === 'New tagline', 'Core sync must still update text.');
$saved = array_merge($saved, array_fill_keys($flags, 1), array('home_title' => 'Plugin title', 'home_desc' => 'Plugin tagline'));
update_option('vonseowp_settings', $saved);
vonseowp_settings_check(get_option('blogname') === 'Plugin title' && get_option('blogdescription') === 'Plugin tagline', 'Plugin-to-core synchronization must remain functional.');
update_option('blogname', 'Final title');
foreach ($flags as $flag) vonseowp_settings_check(get_option('vonseowp_settings')[$flag] === 1, 'Core sync must preserve explicit ON: ' . $flag);
echo 'Settings tests passed (' . $assertions . ' assertions; modeled options lifecycle)' . PHP_EOL;
