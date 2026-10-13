<?php
/** Native integration; intentionally refuses non-QC database prefixes. */
declare(strict_types=1);
$root = rtrim($argv[1] ?? '', '/\\');
if (!is_file($root . '/wp-load.php')) throw new RuntimeException('Pass an isolated WordPress root.');
require_once $root . '/wp-includes/plugin.php';
add_filter('wp_die_handler', static function () {
    return static function (): void {
        fwrite(STDERR, 'WordPress bootstrap/request failed; this is not a passing test.' . PHP_EOL);
        exit(1);
    };
});
require $root . '/wp-load.php';
global $wpdb, $wp_version;
if (!preg_match('/^vonseoqc250_[a-f0-9]{8}_$/D', $wpdb->base_prefix)) {
    throw new RuntimeException('This test writes fixtures and must only run on dedicated QC tables.');
}
if (!class_exists('VonSEOWP_Content_Types')) throw new RuntimeException('Activate the development source first.');
// These fixture cases intentionally change home across origin/subdirectory variants, even in a constant-pinned QC copy.
remove_filter('option_home', '_config_wp_home');
add_filter('pre_http_request', static function () { return new WP_Error('vonseo_qc_offline', 'External requests disabled during QC.'); });
$assertions = 0;
function vonseowp_cpt_check(bool $condition, string $message): void {
    $GLOBALS['assertions']++;
    if (!$condition) throw new RuntimeException($message);
}
function vonseowp_cpt_create(string $type, array $extra = array()): int {
    $id = wp_insert_post(array_merge(array('post_type' => $type, 'post_status' => 'publish',
        'post_title' => 'VonSEO isolated fixture', 'post_name' => 'vonseo-qc-' . wp_generate_uuid4(),
        'post_content' => '<p>Public fixture content.</p>'), $extra), true);
    if (is_wp_error($id)) throw new RuntimeException($id->get_error_message());
    return $id;
}
function vonseowp_cpt_head(int $id): string {
    global $wp_query, $post;
    $wp_query = new WP_Query(array('p' => $id, 'post_type' => get_post_type($id)));
    $post = get_post($id);
    unset($GLOBALS['vonseowp_meta_tags_done'], $GLOBALS['vonseowp_json_ld_done']);
    $frontend = (new ReflectionClass('VonSEOWP_Frontend'))->newInstanceWithoutConstructor();
    ob_start();
    $frontend->output_meta_tags();
    $frontend->output_json_ld();
    return (string) ob_get_clean();
}
register_post_type('vonseqc_book', array('public' => true, 'show_ui' => true, 'supports' => array('title', 'editor'),
    'labels' => array('name' => 'QC Books', 'singular_name' => 'QC Book')));
register_post_type('vonseqc_secret', array('public' => false, 'publicly_queryable' => true));
register_post_type('vonseqc_hidden', array('public' => true, 'publicly_queryable' => false));
$wpdb->query('START TRANSACTION');
foreach (get_posts(array('post_type' => array_keys(get_post_types()), 'post_status' => 'any', 'numberposts' => -1)) as $fixture) {
    wp_delete_post($fixture->ID, true);
}
$wpdb->query('COMMIT');
delete_option('vonseowp_settings');
vonseowp_cpt_check(VonSEOWP_Content_Types::get_enabled() === array('post', 'page'), 'Upgrade defaults must remain post/page.');
$available = VonSEOWP_Content_Types::get_available();
vonseowp_cpt_check(isset($available['vonseqc_book']) && !isset($available['attachment'], $available['revision'])
    && !isset($available['vonseqc_secret']) && !isset($available['vonseqc_hidden']), 'Native public/queryable eligibility is incorrect.');
$settings = array('seo_post_types' => array('post', 'page', 'vonseqc_book'), 'enable_indexnow' => 0,
    'home_desc' => 'Public site default', 'enable_og' => 1, 'sitemap_posts' => 1, 'sitemap_pages' => 1);
update_option('vonseowp_settings', $settings);
wp_set_current_user(1);
$book = vonseowp_cpt_create('vonseqc_book');
$other = vonseowp_cpt_create('vonseqc_hidden');
$box = new VonSEOWP_Meta_Box();
$_POST = array('vonseowp_meta_box_nonce' => wp_create_nonce('vonseowp_meta_box'),
    'vonseowp_title' => 'CptTitleSentinel', 'vonseowp_description' => 'CptDescriptionSentinel',
    'vonseowp_social_title' => 'CptSocialSentinel', 'vonseowp_schema_type' => 'Article', 'vonseowp_noindex' => '1');
$box->save_meta_box($book);
vonseowp_cpt_check(get_post_meta($book, '_vonseowp_title', true) === 'CptTitleSentinel', 'Selected CPT metadata must save.');
$box->save_meta_box($other);
vonseowp_cpt_check(get_post_meta($other, '_vonseowp_title', true) === '', 'Unselected/private CPT metadata must not save.');
$_POST['vonseowp_meta_box_nonce'] = 'invalid';
$_POST['vonseowp_title'] = 'InvalidNonceSentinel';
$box->save_meta_box($book);
vonseowp_cpt_check(get_post_meta($book, '_vonseowp_title', true) === 'CptTitleSentinel', 'Invalid nonce must prevent saving.');
$_POST['vonseowp_meta_box_nonce'] = wp_create_nonce('vonseowp_meta_box');
wp_set_current_user(0);
$_POST['vonseowp_meta_box_nonce'] = wp_create_nonce('vonseowp_meta_box');
$box->save_meta_box($book);
vonseowp_cpt_check(get_post_meta($book, '_vonseowp_title', true) === 'CptTitleSentinel', 'Anonymous writes must be rejected.');
wp_set_current_user(1);
$_POST = array();
$columns = new VonSEOWP_Columns();
$_POST = array('vonseowp_quick_edit_nonce' => wp_create_nonce('vonseowp_quick_edit_nonce'),
    'vonseowp_title' => 'CptTitleSentinel', 'vonseowp_keywords' => 'CptKeywordSentinel');
$columns->save_quick_edit_data($book);
$columns->save_quick_edit_data($other);
vonseowp_cpt_check(get_post_meta($book, '_vonseowp_keywords', true) === 'CptKeywordSentinel'
    && get_post_meta($other, '_vonseowp_keywords', true) === '', 'Quick Edit writes must follow content-type opt-in.');
$_POST['vonseowp_quick_edit_nonce'] = 'invalid';
$_POST['vonseowp_title'] = 'QuickEditDeniedSentinel';
$columns->save_quick_edit_data($book);
vonseowp_cpt_check(get_post_meta($book, '_vonseowp_title', true) === 'CptTitleSentinel', 'Quick Edit must reject invalid nonces.');
$_POST = array();
$columns->register_columns();
vonseowp_cpt_check(has_filter('manage_vonseqc_book_posts_columns', array($columns, 'add_seo_columns')) !== false
    && has_filter('manage_vonseqc_hidden_posts_columns', array($columns, 'add_seo_columns')) === false, 'Score columns must be scoped to enabled types.');
require_once ABSPATH . 'wp-admin/includes/screen.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
require_once ABSPATH . 'wp-admin/includes/post.php';
set_current_screen('vonseqc_book');
$box->enqueue_assets('post.php');
vonseowp_cpt_check(wp_script_is('vonseowp-analyzer-js', 'enqueued'), 'Selected CPT editors must load the analyzer.');
wp_dequeue_script('vonseowp-analyzer-js');
wp_dequeue_script('vonseowp-metabox-js');
set_current_screen('vonseqc_hidden');
$box->enqueue_assets('post.php');
vonseowp_cpt_check(!wp_script_is('vonseowp-analyzer-js', 'enqueued'), 'Unselected CPT editors must not load the analyzer.');
$head = vonseowp_cpt_head($book);
vonseowp_cpt_check(strpos($head, 'CptDescriptionSentinel') !== false && strpos($head, 'CptSocialSentinel') !== false
    && strpos($head, '"@type":"Article"') !== false, 'Selected CPT social and article output must use saved metadata.');
$frontend = (new ReflectionClass('VonSEOWP_Frontend'))->newInstanceWithoutConstructor();
vonseowp_cpt_check($frontend->filter_document_title('Fallback') === 'CptTitleSentinel', 'Selected CPT titles must use the override.');
wp_update_post(array('ID' => $book, 'post_password' => 'fixture-only'));
$locked_head = vonseowp_cpt_head($book);
vonseowp_cpt_check(strpos($locked_head, 'CptDescriptionSentinel') === false && strpos($locked_head, 'CptSocialSentinel') === false,
    'Selected password-protected CPTs must retain the privacy guard.');
wp_update_post(array('ID' => $book, 'post_password' => ''));
vonseowp_cpt_head($book);
vonseowp_cpt_check(!empty($frontend->filter_robots(array())['noindex']), 'Selected CPT noindex must reach native robots.');
$settings['seo_post_types'] = array('post', 'page');
update_option('vonseowp_settings', $settings);
$head = vonseowp_cpt_head($book);
vonseowp_cpt_check(strpos($head, 'CptDescriptionSentinel') === false && strpos($head, 'CptSocialSentinel') === false
    && strpos($head, '"@type":"Article"') === false, 'Disabled CPTs must not emit per-content overrides.');
vonseowp_cpt_check($frontend->filter_document_title('Fallback') === 'Fallback'
    && empty($frontend->filter_robots(array())['noindex']), 'Disabled CPTs must not override titles or robots.');
vonseowp_cpt_check(strpos($head, 'rel="canonical"') !== false, 'Global canonical behavior must not disappear when CPT SEO is disabled.');
$settings['seo_post_types'][] = 'vonseqc_book';
update_option('vonseowp_settings', $settings);
unregister_post_type('vonseqc_book');
vonseowp_cpt_check(!VonSEOWP_Content_Types::is_enabled('vonseqc_book')
    && in_array('vonseqc_book', VonSEOWP_Content_Types::get_saved(get_option('vonseowp_settings')), true), 'Unavailable types must retain settings without running.');
register_post_type('vonseqc_book', array('public' => true));
vonseowp_cpt_check(VonSEOWP_Content_Types::is_enabled('vonseqc_book'), 'Reactivated types must regain their opt-in.');

// Count and enumerate real SQL results at the sitemap page boundary.
update_option('blog_public', 1);
update_option('show_on_front', 'posts');
foreach (get_posts(array('post_type' => array('post', 'page'), 'post_status' => 'any', 'numberposts' => -1)) as $fixture) {
    wp_delete_post($fixture->ID, true);
}
$sitemap = (new ReflectionClass('VonSEOWP_Sitemap'))->newInstanceWithoutConstructor();
$count = new ReflectionMethod($sitemap, 'get_total_entries');
$count->setAccessible(true);
$xml = new ReflectionMethod($sitemap, 'output_xml');
$xml->setAccessible(true);
vonseowp_cpt_check($count->invoke($sitemap) === 1, 'Noindex CPT must be excluded before counting; home remains.');
update_post_meta($book, '_vonseowp_noindex', '0');
$locked = vonseowp_cpt_create('vonseqc_book', array('post_password' => 'fixture-only'));
vonseowp_cpt_check($count->invoke($sitemap) === 2, 'Password-protected entries must be excluded from sitemap counts.');
$wpdb->query('START TRANSACTION');
for ($i = 0; $i < 999; $i++) vonseowp_cpt_create('vonseqc_book');
$wpdb->query('COMMIT');
vonseowp_cpt_check($count->invoke($sitemap) === 1001, 'Exactly 1,000 eligible entries plus home must require two sitemap pages.');
$count_started = microtime(true);
$count->invoke($sitemap);
$count_seconds = microtime(true) - $count_started;
$index = new ReflectionMethod($sitemap, 'output_index');
$index->setAccessible(true);
ob_start();
$index->invoke($sitemap, $count->invoke($sitemap));
$index_xml = simplexml_load_string((string) ob_get_clean());
vonseowp_cpt_check($index_xml !== false && count($index_xml->sitemap) === 2, 'Sitemap index must enumerate exactly the filtered page count.');
$locations = array();
foreach (array(1 => 1000, 2 => 1) as $page => $expected) {
    $GLOBALS['wp_query']->set('vonseowp_sitemap_page', $page);
    ob_start();
    $xml->invoke($sitemap, $settings);
    $document = simplexml_load_string((string) ob_get_clean());
    vonseowp_cpt_check($document !== false && count($document->url) === $expected, 'Sitemap page must contain the expected number of URLs.');
    foreach ($document->url as $entry) $locations[] = (string) $entry->loc;
}
vonseowp_cpt_check(count($locations) === count(array_unique($locations))
    && count(array_keys($locations, home_url('/'), true)) === 1, 'Sitemap pages must have no duplicate URLs or repeated homepage.');
$front = vonseowp_cpt_create('page');
update_option('show_on_front', 'page');
update_option('page_on_front', $front);
vonseowp_cpt_check($count->invoke($sitemap) === 1001, 'Static front page must not be counted twice.');
update_post_meta($front, '_vonseowp_noindex', '1');
vonseowp_cpt_check($count->invoke($sitemap) === 1000, 'Noindex static homepage must not be emitted.');
$settings['sitemap_posts'] = 0;
$settings['sitemap_pages'] = 0;
update_option('vonseowp_settings', $settings);
vonseowp_cpt_check($count->invoke($sitemap) === 1000, 'Disabled core sitemap types must not remove selected CPTs.');
update_option('blog_public', 0);
vonseowp_cpt_check($count->invoke($sitemap) === 0, 'Private-site sitemap count must be zero.');
update_option('blog_public', 1);
$audit = new VonSEOWP_Site_Audit();
$results = $audit->run_audit(1);
vonseowp_cpt_check($results['total'] === 1002 && $results['scanned'] === 25
    && in_array('vonseqc_book', $results['post_types'], true), 'Audit must include selected CPTs, including noindex and locked entries, in bounded batches.');
vonseowp_cpt_check(in_array('noindex', $audit->analyze_post($front), true), 'Noindex content must remain visible as an audit finding.');
$settings['seo_post_types'] = array();
update_option('vonseowp_settings', $settings);
vonseowp_cpt_check($audit->run_audit()['total'] === 0 && $audit->run_audit()['scanned'] === 0, 'Empty audit selection must not fall back to all types.');
vonseowp_cpt_check($count->invoke($sitemap) === 0, 'Empty selection plus noindex front page must produce no sitemap entries.');

$cases = array(
    array('/', '/', ''), array('/foo', '/foo', ''), array('/foo/', '/foo', ''),
    array('/foo', '/foo/', ''), array('/wordpress/foo/', '/wordpress/foo', ''),
    array('/wordpress/foo', 'http://localhost/wordpress/foo', ''), array('/old', '/new', '/new'),
    array('/foo?x=1', '/foo?x=1', ''), array('/foo?x=1', '/foo?x=2', '/foo?x=2'),
    array('/foo', 'https://evil.invalid/path', ''), array('/foo', 'javascript:alert(1)', ''),
    array('/wordpress/foo', 'foo', ''),
    array('/foo', '/./foo', ''), array('/foo', '/other/../foo', ''),
    array('/foo', '#section', ''), array('/foo?x=1', '?x=1', ''),
    array('/foo?x=1', '?x=2', '/foo?x=2'), array('/folder/foo/', 'bar', '/folder/foo/bar'),
);
update_option('home', 'http://localhost/wordpress');
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'off';
foreach ($cases as $case) {
    list($request, $target, $expected) = $case;
    $path = wp_parse_url($request, PHP_URL_PATH);
    update_option('vonseowp_settings', array('redirects_list' => $path . ' -> ' . $target));
    $_SERVER['REQUEST_URI'] = $request;
    $redirects = new VonSEOWP_Redirects();
    vonseowp_cpt_check($redirects->get_redirect_target($request) === $expected, 'Redirect target mismatch for ' . $request . ' -> ' . $target);
}
update_option('home', 'https://localhost/wordpress');
update_option('vonseowp_settings', array('redirects_list' => '/wordpress/foo -> https://localhost/wordpress/foo'));
$redirects = new VonSEOWP_Redirects();
vonseowp_cpt_check($redirects->get_redirect_target('/wordpress/foo') === 'https://localhost/wordpress/foo', 'A same-path HTTP-to-HTTPS redirect must remain valid.');
$_SERVER['HTTPS'] = 'on';
vonseowp_cpt_check($redirects->get_redirect_target('/wordpress/foo') === '', 'A same-path HTTPS self-redirect must be blocked.');
$_SERVER['HTTPS'] = 'off';
$_SERVER['HTTP_HOST'] = 'localhost:8080';
update_option('vonseowp_settings', array('redirects_list' => '/foo -> http://localhost:8080/foo'));
$redirects = new VonSEOWP_Redirects();
vonseowp_cpt_check($redirects->get_redirect_target('/foo') === '', 'Same host and non-default port must not self-redirect.');
$_POST = array();
echo 'Native CPT/sitemap/redirect tests passed (' . $assertions . ' assertions; WP ' . $wp_version . ', PHP ' . PHP_VERSION
    . '; 1,000-entry count ' . number_format($count_seconds, 4) . 's)' . PHP_EOL;
