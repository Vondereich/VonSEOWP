<?php
/** Optional read-only integration test: php scripts/test-wordpress-toc-robots.php /path/to/wordpress */
declare(strict_types=1);
$root = rtrim($argv[1] ?? '', '/\\');
if (!is_file($root . '/wp-load.php')) {
    fwrite(STDERR, 'Pass the path to an installed WordPress instance.' . PHP_EOL);
    exit(1);
}
require_once $root . '/wp-includes/plugin.php';
add_filter('option_active_plugins', static function ($plugins) {
    return array_values(array_diff($plugins, array('vonseo/vonseo.php')));
});
require $root . '/wp-load.php';
global $wp_version;
define('VONSEOWP_URL', plugins_url('vonseo/'));
define('VONSEOWP_VERSION', 'integration-test');
require_once dirname(__DIR__) . '/includes/class-vonseowp-toc.php';
require_once dirname(__DIR__) . '/includes/class-vonseowp-frontend.php';
$settings = array('enable_toc' => 1, 'toc_min_headings' => 1);
$public = 1;
$meta = array();
$assertions = 0;
add_filter('pre_option_vonseowp_settings', static function () { return $GLOBALS['settings']; });
add_filter('pre_option_blog_public', static function () { return $GLOBALS['public']; });
add_filter('get_post_metadata', static function ($value, $id, $key) {
    return $GLOBALS['meta'][$key] ?? $value;
}, 10, 3);
$post = new WP_Post((object)array('ID' => 999999, 'post_content' => '', 'post_type' => 'post', 'post_title' => 'TOC integration fixture', 'post_status' => 'publish', 'post_author' => 1, 'filter' => 'raw', 'post_date' => '2026-10-02 12:00:00', 'post_date_gmt' => '2026-10-02 04:00:00'));
$wp_query = new WP_Query();
$wp_query->is_single = true;
$wp_query->is_singular = true;
$wp_query->in_the_loop = true;
$wp_query->posts = array($post);
$wp_query->post = $post;
$wp_query->queried_object = $post;
$frontend = new VonSEOWP_Frontend();
$toc = new VonSEOWP_TOC();
function vonseowp_integration_check(bool $condition, string $message): void {
    $GLOBALS['assertions']++;
    if (!$condition) throw new RuntimeException($message);
}
function vonseowp_check_dom_targets(string $html): void {
    $document = new DOMDocument();
    $errors = libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();
    libxml_use_internal_errors($errors);
    $xpath = new DOMXPath($document);
    $ids = array();
    foreach ($xpath->query('//*[@id]') as $element) {
        if (!($element instanceof DOMElement)) throw new RuntimeException('Expected an HTML element.');
        $ids[] = $element->getAttribute('id');
    }
    vonseowp_integration_check(count($ids) === count(array_unique($ids)), 'Native HTML API output contains duplicate IDs.');
    foreach ($xpath->query('//div[@class="vonseo-toc-container"]//a') as $link) {
        if (!($link instanceof DOMElement)) throw new RuntimeException('Expected a link element.');
        vonseowp_integration_check(in_array(rawurldecode(substr($link->getAttribute('href'), 1)), $ids, true), 'Native TOC link lacks its target.');
    }
}
foreach (array(
    '<h2>Setup</h2><h2>Setup</h2>',
    '<h2 id="custom"><strong>Setup</strong> &amp; Go</h2>',
    '<h2 id="bad id" id="duplicate">Setup</h2>',
    '<h2 title="a > b">$1 and $0</h2>',
    '<div id="setup"></div><h2>Setup</h2>',
    '<h2 id="a%20b">Percent</h2><h2>!!!</h2>',
    '<h2 id="a&amp;b">Entities</h2>',
    '<h2>&#20013;&#25991;</h2>',
    '<!-- <h2>Hidden</h2> --><h3>Shown</h3>',
) as $fixture) {
    vonseowp_check_dom_targets(apply_filters('the_content', $fixture));
}
add_shortcode('vonseo_test_heading', static function () { return '<h2><em>Generated Heading</em></h2>'; });
vonseowp_integration_check(strpos(apply_filters('the_content', '<h2/id="custom">Slash Separator</h2>'), 'href="#custom"') !== false, 'Native slash-separated ID must remain the TOC target.');
$settings['enable_toc'] = 0;
$output = apply_filters('the_content', '[vonseo_toc][vonseo_test_heading][vonseo_toc]');
vonseowp_integration_check(substr_count($output, 'class="vonseo-toc-container"') === 1, 'Native shortcode/automatic flow must produce one TOC.');
vonseowp_integration_check(strpos($output, '<em>Generated Heading</em>') !== false, 'Other shortcode markup must survive.');
vonseowp_check_dom_targets($output);
vonseowp_integration_check(trim(apply_filters('the_content', "[vonseo_toc]\n\n<p>No headings</p>")) === '<p>No headings</p>', 'Empty shortcode must not output a placeholder.');
$post->post_content = '<h2>Template Heading</h2>[vonseo_test_heading]';
$template_toc = do_shortcode('[vonseo_toc]');
$template_content = apply_filters('the_content', $post->post_content);
vonseowp_integration_check(strpos($template_toc, 'vonseo-toc-container') !== false, 'Direct theme shortcode must produce markup, not a deferred comment.');
vonseowp_integration_check(strpos($template_content, 'vonseo-toc-container') === false, 'An external manual TOC must not add a second automatic TOC.');
vonseowp_check_dom_targets($template_toc . $template_content);
$post->post_password = 'fixture-password';
vonseowp_integration_check(do_shortcode('[vonseo_toc]') === '', 'Direct shortcode must not reveal protected headings.');
$post->post_password = '';
vonseowp_integration_check(has_action('wp_head', 'wp_robots') !== false, 'Core robots emitter must remain registered.');
$wp_query->is_embed = true;
$robots = apply_filters('wp_robots', array());
vonseowp_integration_check(!empty($robots['noindex']) && !isset($robots['index']) && !empty($robots['follow']), 'Real WordPress embed noindex/follow must survive.');
$wp_query->is_embed = false;
$public = 0;
$robots = apply_filters('wp_robots', array());
vonseowp_integration_check(!empty($robots['noindex']) && !empty($robots['nofollow']), 'Real WordPress private site must remain noindex.');
$public = 1;
$meta['_vonseowp_noindex'] = '1';
$robots = apply_filters('wp_robots', array());
vonseowp_integration_check(!empty($robots['noindex']) && !isset($robots['index']), 'Native per-post noindex must work: ' . wp_json_encode(array(is_singular(), get_post(), get_post_meta(999999, '_vonseowp_noindex', true), $robots)));
$meta = array();
add_filter('wp_robots', static function ($robots) {
    $robots['noarchive'] = true;
    $robots['max-image-preview'] = 'none';
    $robots['max-snippet'] = 0;
    return $robots;
}, 50);
$robots = apply_filters('wp_robots', array());
vonseowp_integration_check($robots['noarchive'] && $robots['max-image-preview'] === 'none' && $robots['max-snippet'] === 0, 'Native third-party directives must not be weakened.');
ob_start();
wp_robots();
$frontend->output_meta_tags();
$head = ob_get_clean();
vonseowp_integration_check(substr_count($head, 'name="robots"') + substr_count($head, "name='robots'") === 1, 'Core and VonSEO must emit exactly one robots tag.');
echo 'WordPress ' . $wp_version . ' native TOC/robots integration passed (' . $assertions . ' assertions)' . PHP_EOL;
