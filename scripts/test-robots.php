<?php
declare(strict_types=1);
define('ABSPATH', __DIR__);
define('VONSEOWP_URL', 'https://example.test/wp-content/plugins/vonseo/');
define('VONSEOWP_VERSION', 'test');
$context = array();
$hooks = array();
$removed = array();
$assets = array();
$assertions = 0;
function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) { $GLOBALS['hooks'][$hook][] = $callback; }
function add_action($hook, $callback, $priority = 10, $accepted_args = 1) { add_filter($hook, $callback, $priority, $accepted_args); }
function remove_action($hook, $callback, $priority = 10) { $GLOBALS['removed'][] = $callback; }
function get_post() { return $GLOBALS['context']['post'] ?? (object)array('ID' => 7, 'post_content' => ''); }
function get_option($name, $default = false) { return $GLOBALS['context'][$name] ?? $default; }
function get_post_meta($id, $key, $single = false) { return $GLOBALS['context'][$key] ?? ''; }
function is_singular() { return $GLOBALS['context']['singular'] ?? true; }
function is_search() { return $GLOBALS['context']['search'] ?? false; }
function is_404() { return $GLOBALS['context']['404'] ?? false; }
function has_shortcode($content, $tag) { return strpos($content, '[' . $tag . ']') !== false; }
function wp_enqueue_style($handle, $url, $dependencies, $version) { $GLOBALS['assets'][$handle] = $url; }
function wp_enqueue_script($handle, $url, $dependencies, $version, $footer) { $GLOBALS['assets'][$handle] = $url; }
require_once dirname(__DIR__) . '/includes/class-vonseowp-frontend.php';
function check($condition, $message) { $GLOBALS['assertions']++; if (!$condition) throw new RuntimeException($message); }
$frontend = new VonSEOWP_Frontend();
check(isset($hooks['wp_robots']), 'VonSEO must use the native robots filter.');
check(!in_array('wp_robots', $removed, true), 'VonSEO must not remove the core emitter.');
$context = array('blog_public' => 1);
$robots = $frontend->filter_robots(array());
check($robots === array('index' => true, 'max-image-preview' => 'large', 'follow' => true), 'Public content must retain the default indexing policy.');
foreach (array(array('blog_public' => 0), array('search' => true), array('404' => true), array('_vonseowp_noindex' => '1')) as $flags) {
    $context = array_replace(array('blog_public' => 1), $flags);
    $robots = $frontend->filter_robots(array('index' => true, 'follow' => true, 'noarchive' => true));
    check(!empty($robots['noindex']) && !empty($robots['nofollow']) && !isset($robots['index']) && !isset($robots['follow']), 'Private/search/404/per-post rules must not have conflicting directives: ' . json_encode(array($flags, $robots)));
    check($robots['noarchive'] === true, 'Independent core/plugin directives must be preserved.');
}
$context = array('blog_public' => 1);
$robots = $frontend->filter_robots(array('noindex' => true, 'follow' => true, 'nosnippet' => true));
check(!isset($robots['index']) && $robots['noindex'] && $robots['follow'] && $robots['nosnippet'] && !isset($robots['nofollow']), 'Core embed noindex/follow and third-party restrictions must survive.');
$robots = $frontend->filter_robots(array('nofollow' => true, 'max-image-preview' => 'none', 'max-snippet' => 0, 'max-video-preview' => 0));
check(!isset($robots['follow']) && $robots['max-image-preview'] === 'none' && $robots['max-snippet'] === 0 && $robots['max-video-preview'] === 0, 'Third-party crawler limits must not be weakened.');
$robots = $frontend->filter_robots(array('noimageindex' => true));
check(!isset($robots['max-image-preview']) && $robots['noimageindex'], 'Noimageindex must not acquire a new preview allowance.');
$context = array('blog_public' => 1, 'singular' => false, '_vonseowp_noindex' => '1');
check(empty($frontend->filter_robots(array())['noindex']), 'A global post must not noindex archives.');
$context = array('vonseowp_settings' => array('enable_toc' => 0));
$frontend->enqueue_assets();
check(empty($assets), 'Pages without TOC must not load TOC assets.');
$context['vonseowp_settings']['enable_toc'] = 1;
$frontend->enqueue_assets();
check(count($assets) === 2, 'Automatic TOC must enqueue both assets.');
$assets = array();
$context['_vonseowp_disable_toc'] = '1';
$frontend->enqueue_assets();
check(empty($assets), 'Disabled automatic TOC must not enqueue assets.');
$context['post'] = (object)array('ID' => 7, 'post_content' => '[vonseo_toc]');
$frontend->enqueue_assets();
check(count($assets) === 2, 'Explicit shortcodes must load assets even with automatic TOC disabled.');
$assets = array();
$context = array('singular' => false, 'vonseowp_settings' => array());
$wp_query = (object)array('posts' => array((object)array('post_content' => ''), (object)array('post_content' => '[vonseo_toc]')));
$frontend->enqueue_assets();
check(count($assets) === 2, 'Archive shortcodes outside the first post must load assets.');
echo 'Robots and asset tests passed (' . $assertions . ' assertions)' . PHP_EOL;
