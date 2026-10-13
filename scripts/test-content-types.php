<?php
declare(strict_types=1);
define('ABSPATH', __DIR__);
$options = array();
$types = array();
$assertions = 0;
function get_option(string $name, $default = false) { return $GLOBALS['options'][$name] ?? $default; }
function get_post_types(array $args = array(), string $output = 'names'): array { return $GLOBALS['types']; }
function sanitize_key(string $key): string { return strtolower(preg_replace('/[^a-z0-9_\-]/i', '', $key)); }
/** @param mixed $value */
function wp_unslash($value) { return is_array($value) ? array_map('wp_unslash', $value) : (is_string($value) ? stripslashes($value) : $value); }
function vonseowp_types_check(bool $condition, string $message): void {
    $GLOBALS['assertions']++;
    if (!$condition) throw new RuntimeException($message);
}
require_once dirname(__DIR__) . '/includes/class-vonseowp-content-types.php';
require_once dirname(__DIR__) . '/includes/class-vonseowp-admin.php';
foreach (array('post', 'page', 'attachment', 'revision', 'book', 'secret', 'hidden', 'wp_template') as $type) {
    $types[$type] = (object) array(
        '_builtin' => in_array($type, array('post', 'page', 'attachment', 'revision', 'wp_template'), true),
        'public' => in_array($type, array('post', 'page', 'attachment', 'book', 'hidden'), true),
        'publicly_queryable' => in_array($type, array('post', 'attachment', 'book', 'secret'), true),
    );
}
vonseowp_types_check(array_keys(VonSEOWP_Content_Types::get_available()) === array('post', 'page', 'book'), 'Only eligible public types may be offered.');
vonseowp_types_check(VonSEOWP_Content_Types::get_enabled() === array('post', 'page'), 'Old settings must retain post/page defaults.');
$admin = (new ReflectionClass('VonSEOWP_Admin'))->newInstanceWithoutConstructor();
vonseowp_types_check($admin->sanitize_settings(array())['seo_post_types'] === array('post', 'page'), 'Old form submissions must preserve upgrade defaults.');
$options['vonseowp_settings'] = array('seo_post_types' => array('post', 'book'));
vonseowp_types_check(VonSEOWP_Content_Types::get_enabled() === array('post', 'book'), 'Custom types are opt-in.');
$input = array('', 'book', 'book', 'attachment', 'secret', 'hidden', 'wp_template', 'unknown', array('post'), 42);
vonseowp_types_check($admin->sanitize_settings(array('seo_post_types' => $input))['seo_post_types'] === array('book'), 'Reject injected types, nested values, and duplicates.');
vonseowp_types_check($admin->sanitize_settings(array('seo_post_types' => 'book'))['seo_post_types'] === array('post', 'book'), 'Malformed selections must preserve the saved setting.');
vonseowp_types_check($admin->sanitize_settings(array('seo_post_types' => array('')))['seo_post_types'] === array(), 'Explicitly unchecked types must remain disabled.');
unset($types['book']);
vonseowp_types_check(VonSEOWP_Content_Types::get_enabled() === array('post'), 'An unavailable type must not generate runtime output.');
vonseowp_types_check($admin->sanitize_settings(array('seo_post_types' => array('post', 'book')))['seo_post_types'] === array('post', 'book'), 'Unavailable saved types must survive settings saves.');
vonseowp_types_check($admin->sanitize_settings(array())['seo_post_types'] === array('post', 'book'), 'Missing fields must not erase saved selections.');
$types['book'] = (object) array('_builtin' => false, 'public' => true, 'publicly_queryable' => true);
vonseowp_types_check(VonSEOWP_Content_Types::is_enabled('book'), 'Re-registering a type must restore the saved opt-in.');
vonseowp_types_check(VonSEOWP_Content_Types::get_sitemap_types(array('seo_post_types' => array('post', 'page', 'book'), 'sitemap_posts' => 0)) === array('page', 'book'), 'Legacy sitemap switches must remain effective.');
$options['vonseowp_settings'] = array('seo_post_types' => array());
vonseowp_types_check(VonSEOWP_Content_Types::get_enabled() === array(), 'An empty selection must not become all post types.');
echo 'Content type tests passed (' . $assertions . ' assertions)' . PHP_EOL;
