<?php
/** Real WordPress title/password APIs with modeled posts; no database writes. */
declare(strict_types=1);
$root = rtrim($argv[1] ?? '', '/\\');
if (!is_file($root . '/wp-load.php')) throw new RuntimeException('Pass an installed WordPress root.');
require_once $root . '/wp-includes/plugin.php';
add_filter('option_active_plugins', static function (array $plugins): array {
    return array_values(array_diff($plugins, array('vonseo/vonseo.php')));
});
require $root . '/wp-load.php';
require_once dirname(__DIR__) . '/includes/class-vonseowp-content-types.php';
require_once dirname(__DIR__) . '/includes/class-vonseowp-frontend.php';
require_once ABSPATH . WPINC . '/class-phpass.php';
$settings = array('seo_post_types' => array('post', 'page', 'vonseqc_title'));
$meta = array('_vonseowp_title' => 'Native SEO & Title');
add_filter('pre_option_vonseowp_settings', static function () { return $GLOBALS['settings']; });
add_filter('get_post_metadata', static function ($value, $id, $key) {
    return $id === 999999 && isset($GLOBALS['meta'][$key]) ? array($GLOBALS['meta'][$key]) : $value;
}, 10, 3);
register_post_type('vonseqc_title', array('public' => true, 'publicly_queryable' => true));
$frontend = new VonSEOWP_Frontend();
$assertions = 0;
function vonseowp_native_title_check(bool $condition, string $message): void {
    $GLOBALS['assertions']++;
    if (!$condition) throw new RuntimeException($message);
}
foreach (array('post', 'page', 'vonseqc_title') as $type) {
    $post = new WP_Post((object) array('ID' => 999999, 'post_type' => $type, 'post_status' => 'publish', 'filter' => 'raw',
        'post_title' => 'Native public title', 'post_name' => 'title-qc', 'post_content' => '', 'post_password' => ''));
    $wp_query = new WP_Query();
    $wp_query->is_singular = true;
    $wp_query->is_single = $type !== 'page';
    $wp_query->is_page = $type === 'page';
    $wp_query->post = $post;
    $wp_query->queried_object = $post;
    $wp_query->queried_object_id = $post->ID;
    wp_cache_set($post->ID, $post, 'posts');
    vonseowp_native_title_check(wp_title('|', false, 'right') === 'Native SEO &amp; Title | ', 'Native legacy right title failed: ' . $type);
    vonseowp_native_title_check(wp_title('|', false, 'left') === ' | Native SEO &amp; Title', 'Native legacy left title failed: ' . $type);
    vonseowp_native_title_check(wp_get_document_title() === 'Native SEO & Title', 'Modern title changed: ' . $type);
    $post->post_password = 'title-qc-password';
    wp_cache_set($post->ID, $post, 'posts');
    $hasher = new PasswordHash(8, true);
    foreach (array(null, $hasher->HashPassword('wrong'), $hasher->HashPassword('title-qc-password')) as $cookie) {
        $_COOKIE = $cookie === null ? array() : array('wp-postpass_' . COOKIEHASH => $cookie);
        $unlocked = !post_password_required($post);
        $legacy = wp_title('|', false, 'right');
        $modern = wp_get_document_title();
        vonseowp_native_title_check((strpos($legacy, 'Native SEO') !== false) === $unlocked, 'Legacy password-cookie boundary failed: ' . $type);
        vonseowp_native_title_check((strpos($modern, 'Native SEO') !== false) === $unlocked, 'Modern password-cookie boundary failed: ' . $type);
    }
    $_COOKIE = array();
    $post->post_password = '';
    wp_cache_set($post->ID, $post, 'posts');
    $meta['_vonseowp_title'] = '';
    vonseowp_native_title_check(wp_title('|', false, 'right') === 'Native public title | ', 'Native fallback changed: ' . $type);
    $meta['_vonseowp_title'] = 'Native SEO & Title';
}
global $wp_version;
echo 'Native title tests passed (' . $assertions . ' assertions; WordPress ' . $wp_version . ', PHP ' . PHP_VERSION . ').' . PHP_EOL;
