<?php
/** Portable selection, authorization and term-metadata regressions. */
declare(strict_types=1);
define('ABSPATH', __DIR__);
class WP_Term {
    public int $term_id;
    public string $taxonomy;
    public function __construct(object $term) {
        $this->term_id = $term->term_id;
        $this->taxonomy = $term->taxonomy;
    }
}
$term = new WP_Term((object) array('term_id' => 12, 'taxonomy' => 'category'));
$settings = array();
$metadata = array();
$authorized = true;
$valid_nonce = true;
$assertions = 0;
$taxonomies = array();
foreach (array('category', 'post_tag', 'genre', 'private', 'internal') as $name) {
    $taxonomies[$name] = (object) array('public' => $name !== 'private', 'publicly_queryable' => true,
        '_builtin' => in_array($name, array('category', 'post_tag', 'internal'), true), 'cap' => (object) array('edit_terms' => 'edit_' . $name));
}
function add_action(string $hook, callable $callback, int $priority = 10, int $accepted = 1): void {}
function get_taxonomies(array $args = array(), string $output = 'names'): array { return $GLOBALS['taxonomies']; }
/** @return object|false */
function get_taxonomy(string $name) { return $GLOBALS['taxonomies'][$name] ?? false; }
/** @return WP_Term|false */
function get_term(int $id, string $taxonomy) { return $id === 12 && $taxonomy === $GLOBALS['term']->taxonomy ? $GLOBALS['term'] : false; }
/** @param mixed $default @return mixed */
function get_option(string $name, $default = false) { return $name === 'vonseowp_settings' ? $GLOBALS['settings'] : $default; }
function sanitize_key(string $text): string { return strtolower(preg_replace('/[^a-z0-9_\-]/i', '', $text)); }
function sanitize_text_field(string $text): string { return trim(strip_tags($text)); }
function sanitize_textarea_field(string $text): string { return sanitize_text_field($text); }
function wp_unslash(string $text): string { return stripslashes($text); }
function current_user_can(string $capability, int $id = 0): bool { return $GLOBALS['authorized']; }
/** @return int|false */
function wp_verify_nonce(string $nonce, string $action) { return $GLOBALS['valid_nonce'] && $nonce === 'valid' && $action === 'vonseowp_term_category' ? 1 : false; }
/** @return mixed */
function get_term_meta(int $id, string $key, bool $single = false) { return $GLOBALS['metadata'][$key] ?? ''; }
function update_term_meta(int $id, string $key, string $value): void { $GLOBALS['metadata'][$key] = $value; }
function delete_term_meta(int $id, string $key): void { unset($GLOBALS['metadata'][$key]); }
function esc_url_raw(string $url, array $protocols = array()): string { return preg_match('~^https?://~', $url) ? $url : ''; }
/** @return array|false */
function wp_parse_url(string $url) { return parse_url($url); }
require dirname(__DIR__) . '/includes/class-vonseowp-taxonomies.php';
function vonseowp_taxonomy_check(bool $condition, string $message): void {
    $GLOBALS['assertions']++;
    if (!$condition) throw new RuntimeException($message);
}
vonseowp_taxonomy_check(array_keys(VonSEOWP_Taxonomies::get_available()) === array('category', 'post_tag', 'genre'), 'Private and internal taxonomies must be excluded.');
vonseowp_taxonomy_check(VonSEOWP_Taxonomies::get_enabled() === array('category', 'post_tag'), 'Old options must default to categories and tags only.');
$settings['seo_taxonomies'] = array();
vonseowp_taxonomy_check(VonSEOWP_Taxonomies::get_enabled() === array(), 'Empty selection must not fall back to defaults.');
$settings['seo_taxonomies'] = array('genre', 'missing');
vonseowp_taxonomy_check(VonSEOWP_Taxonomies::get_enabled() === array('genre'), 'Unavailable saved selections must not run.');
vonseowp_taxonomy_check(VonSEOWP_Taxonomies::sanitize_selection(array('', 'private', 'genre', 'missing', 'genre', array('category')), $settings) === array('genre', 'missing'), 'Only available or previously saved scalar names may be selected.');
vonseowp_taxonomy_check(VonSEOWP_Taxonomies::sanitize_selection('malformed', $settings) === array('genre', 'missing'), 'Malformed selection must preserve saved choices.');
$settings = array();
$engine = new VonSEOWP_Taxonomies();
$_POST = array('vonseowp_term_nonce' => 'valid', 'vonseowp_term_title' => '<b>Custom</b> SEO',
    'vonseowp_term_description' => 'Publisher\'s \\"quote\\"', 'vonseowp_term_canonical' => 'http://localhost/wordpress/category/news/',
    'vonseowp_term_social_desc' => '<i>Social</i> description', 'vonseowp_term_noindex' => '1');
$engine->save_meta(12, 12, 'category');
vonseowp_taxonomy_check($metadata['_vonseowp_title'] === 'Custom SEO' && $metadata['_vonseowp_social_desc'] === 'Social description', 'Term text must be sanitized.');
vonseowp_taxonomy_check($metadata['_vonseowp_canonical'] === 'http://localhost/wordpress/category/news/', 'Canonical links must allow local development without network validation.');
vonseowp_taxonomy_check($metadata['_vonseowp_noindex'] === '1', 'Explicit noindex must save.');
$saved = $metadata;
foreach (array('missing', 'wrong', 'array', 'unauthorized', 'unselected', 'wrong-term') as $case) {
    $authorized = $case !== 'unauthorized';
    $valid_nonce = $case !== 'wrong';
    $settings = $case === 'unselected' ? array('seo_taxonomies' => array()) : array();
    $_POST['vonseowp_term_nonce'] = $case === 'array' ? array('valid') : 'valid';
    if ($case === 'missing') unset($_POST['vonseowp_term_nonce']);
    $_POST['vonseowp_term_title'] = 'Forbidden';
    $engine->save_meta($case === 'wrong-term' ? 99 : 12, 12, 'category');
    vonseowp_taxonomy_check($metadata === $saved, 'Rejected write changed metadata: ' . $case);
}
$authorized = $valid_nonce = true;
$settings = array();
$_POST = array('vonseowp_term_nonce' => 'valid', 'vonseowp_term_title' => array('bad'), 'vonseowp_term_description' => '');
$engine->save_meta(12, 12, 'category');
vonseowp_taxonomy_check($metadata['_vonseowp_title'] === 'Custom SEO', 'Malformed field must not destroy saved title.');
vonseowp_taxonomy_check(!isset($metadata['_vonseowp_description'], $metadata['_vonseowp_noindex']), 'Cleared description and unchecked noindex must delete overrides.');
foreach (array('javascript:alert(1)', '/relative/', 'https://', '//external.test/') as $invalid_url) {
    $_POST = array('vonseowp_term_nonce' => 'valid', 'vonseowp_term_canonical' => $invalid_url);
    $engine->save_meta(12, 12, 'category');
    vonseowp_taxonomy_check(!isset($metadata['_vonseowp_canonical']), 'Invalid canonical was stored: ' . $invalid_url);
}
echo 'Taxonomy tests passed (' . $assertions . ' assertions).' . PHP_EOL;
