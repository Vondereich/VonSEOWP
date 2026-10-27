<?php
/** State-changing native taxonomy QC; refuses any non-owned database. */
declare(strict_types=1);
$root = rtrim($argv[1] ?? '', '/\\');
if (!is_file($root . '/qc-owner.json')) throw new RuntimeException('Use an owned isolated QC root.');
require $root . '/wp-load.php';
global $wpdb, $wp_query, $post, $wp_version;
$owner = json_decode((string) file_get_contents($root . '/qc-owner.json'), true);
if (($owner['database'] ?? '') !== $wpdb->dbname || ($owner['root'] ?? '') !== realpath($root)
    || !preg_match('/^vonseoqc250_[a-f0-9]{8}_$/D', $wpdb->base_prefix)) throw new RuntimeException('Refusing non-QC database.');
wp_set_current_user(1);
add_filter('pre_http_request', static function () { return new WP_Error('qc_offline', 'No external requests in QC.'); });
$assertions = 0;
function vonseowp_native_tax_check(bool $condition, string $message): void {
    $GLOBALS['assertions']++;
    if (!$condition) throw new RuntimeException($message);
}
if (($argv[2] ?? '') === 'uninstall') {
    $result = wp_insert_term('Uninstall fixture', 'category');
    if (is_wp_error($result)) throw new RuntimeException($result->get_error_message());
    $id = (int) $result['term_id'];
    foreach (array('title', 'description', 'canonical', 'social_desc', 'noindex') as $field) update_term_meta($id, '_vonseowp_' . $field, 'sentinel');
    update_term_meta($id, '_other_plugin_value', 'keep');
    define('WP_UNINSTALL_PLUGIN', 'vonseo/vonseo.php');
    require dirname(__DIR__) . '/uninstall.php';
    foreach (array('title', 'description', 'canonical', 'social_desc', 'noindex') as $field) {
        vonseowp_native_tax_check(get_term_meta($id, '_vonseowp_' . $field, true) === '', 'Term uninstall cleanup missed ' . $field);
    }
    vonseowp_native_tax_check(get_term_meta($id, '_other_plugin_value', true) === 'keep', 'Uninstall removed another plugin term key.');
    echo 'Native term uninstall passed (' . $assertions . ' assertions).' . PHP_EOL;
    exit;
}
$old = get_option('vonseowp_settings', array());
$settings = array_merge(is_array($old) ? $old : array(), array('enable_indexnow' => 0, 'enable_sitemap' => 1,
    'enable_og' => 1, 'seo_post_types' => array('post', 'page'), 'seo_taxonomies' => array('category', 'post_tag', 'vonseqc_genre')));
update_option('vonseowp_settings', $settings);
update_option('blog_public', 1);
update_option('show_on_front', 'posts');
update_option('permalink_structure', '/%postname%/');
register_taxonomy('vonseqc_genre', 'post', array('public' => true, 'show_ui' => true, 'rewrite' => array('slug' => 'genre')));
register_taxonomy('vonseqc_private', 'post', array('public' => false, 'show_ui' => true));
register_taxonomy('vonseqc_caps', 'post', array('public' => true, 'capabilities' => array('edit_terms' => 'vonseqc_edit_terms')));
vonseowp_native_tax_check(!isset(VonSEOWP_Taxonomies::get_available()['vonseqc_private']), 'Private taxonomy was offered.');
vonseowp_native_tax_check(VonSEOWP_Taxonomies::get_saved(array()) === array('category', 'post_tag'), 'Upgrade defaults changed.');
$engine = new VonSEOWP_Taxonomies();
$frontend = new VonSEOWP_Frontend();
$ids = array();
foreach (array('category', 'post_tag', 'vonseqc_genre', 'vonseqc_private', 'vonseqc_caps') as $taxonomy) {
    foreach (get_terms(array('taxonomy' => $taxonomy, 'slug' => 'tax-qc-' . $taxonomy, 'hide_empty' => false)) as $existing) {
        if ($existing->term_id !== (int) get_option('default_category')) wp_delete_term($existing->term_id, $taxonomy);
    }
    $result = wp_insert_term('Taxonomy ' . $taxonomy, $taxonomy, array('slug' => 'tax-qc-' . $taxonomy, 'description' => '<p>Native archive description &amp; fallback.</p>'));
    if (is_wp_error($result)) throw new RuntimeException($result->get_error_message());
    $ids[$taxonomy] = (int) $result['term_id'];
}
$_POST = array('vonseowp_term_nonce' => wp_create_nonce('vonseowp_term_category'), 'vonseowp_term_title' => 'Archive SEO & Title',
    'vonseowp_term_description' => 'Archive SEO description', 'vonseowp_term_social_desc' => 'Social archive description',
    'vonseowp_term_canonical' => 'http://localhost/wordpress/category/target/', 'vonseowp_term_noindex' => '1');
$original = $_POST;
$engine->save_meta($ids['category'], 0, 'category');
vonseowp_native_tax_check(get_term_meta($ids['category'], '_vonseowp_title', true) === 'Archive SEO & Title', 'Native term save failed.');
vonseowp_native_tax_check(get_term_meta($ids['category'], '_vonseowp_canonical', true) === $original['vonseowp_term_canonical'], 'Local canonical was rejected.');
$_POST['vonseowp_term_title'] = 'Native create hook';
$hooked = wp_insert_term('Hook-driven save', 'category');
if (is_wp_error($hooked)) throw new RuntimeException($hooked->get_error_message());
vonseowp_native_tax_check(get_term_meta($hooked['term_id'], '_vonseowp_title', true) === 'Native create hook', 'Native created_term hook did not save.');
$_POST['vonseowp_term_title'] = 'Native update hook';
wp_update_term($hooked['term_id'], 'category', array('name' => 'Hook-driven update'));
vonseowp_native_tax_check(get_term_meta($hooked['term_id'], '_vonseowp_title', true) === 'Native update hook', 'Native edited_term hook did not save.');
wp_delete_term($hooked['term_id'], 'category');
unregister_taxonomy('vonseqc_genre');
vonseowp_native_tax_check(!in_array('vonseqc_genre', VonSEOWP_Taxonomies::get_enabled(), true)
    && in_array('vonseqc_genre', VonSEOWP_Taxonomies::get_saved($settings), true), 'Unavailable taxonomy lost its stored selection.');
register_taxonomy('vonseqc_genre', 'post', array('public' => true, 'show_ui' => true, 'rewrite' => array('slug' => 'genre')));
vonseowp_native_tax_check(in_array('vonseqc_genre', VonSEOWP_Taxonomies::get_enabled(), true), 'Reactivated taxonomy did not regain its selection.');
foreach (array('invalid-nonce', 'array-nonce', 'wrong-taxonomy', 'no-user', 'not-selected') as $case) {
    $_POST = $original;
    $_POST['vonseowp_term_title'] = 'Forbidden';
    if ($case === 'invalid-nonce') $_POST['vonseowp_term_nonce'] = 'bad';
    if ($case === 'array-nonce') $_POST['vonseowp_term_nonce'] = array('bad');
    if ($case === 'wrong-taxonomy') $_POST['vonseowp_term_nonce'] = wp_create_nonce('vonseowp_term_post_tag');
    if ($case === 'no-user') wp_set_current_user(0);
    if ($case === 'not-selected') update_option('vonseowp_settings', array_merge($settings, array('seo_taxonomies' => array())));
    $engine->save_meta($ids['category'], 0, 'category');
    vonseowp_native_tax_check(get_term_meta($ids['category'], '_vonseowp_title', true) === 'Archive SEO & Title', 'Denied native write changed metadata: ' . $case);
    wp_set_current_user(1);
    update_option('vonseowp_settings', $settings);
}
// Test taxonomy-specific capabilities without granting general post editing rights.
add_role('vonseqc_tax_editor', 'QC taxonomy editor', array('read' => true, 'vonseqc_edit_terms' => true));
$user_id = wp_insert_user(array('user_login' => 'tax_editor_' . wp_generate_password(8, false), 'user_pass' => wp_generate_password(), 'role' => 'vonseqc_tax_editor'));
if (is_wp_error($user_id)) throw new RuntimeException($user_id->get_error_message());
$settings['seo_taxonomies'][] = 'vonseqc_caps';
update_option('vonseowp_settings', $settings);
wp_set_current_user($user_id);
$_POST = array('vonseowp_term_nonce' => wp_create_nonce('vonseowp_term_vonseqc_caps'), 'vonseowp_term_title' => 'Capability title');
$engine->save_meta($ids['vonseqc_caps'], 0, 'vonseqc_caps');
vonseowp_native_tax_check(!current_user_can('edit_posts') && get_term_meta($ids['vonseqc_caps'], '_vonseowp_title', true) === 'Capability title', 'Native taxonomy-only editor failed.');
$_POST['vonseowp_term_nonce'] = wp_create_nonce('vonseowp_term_category');
$engine->save_meta($ids['category'], 0, 'category');
vonseowp_native_tax_check(get_term_meta($ids['category'], '_vonseowp_title', true) === 'Archive SEO & Title', 'Taxonomy editor escaped its capability boundary.');
wp_set_current_user(1);
$post = null;
$term = get_term($ids['category'], 'category');
$wp_query = new WP_Query();
$wp_query->is_category = $wp_query->is_archive = true;
$wp_query->queried_object = $term;
$wp_query->queried_object_id = $term->term_id;
$_SERVER['REQUEST_URI'] = '/category/' . $term->slug . '/';
foreach (array('is_feed', 'is_404') as $flag) {
    $wp_query->$flag = true;
    vonseowp_native_tax_check(VonSEOWP_Taxonomies::get_current() === null, 'Taxonomy metadata leaked into ' . $flag);
    $wp_query->$flag = false;
}
update_option('vonseowp_settings', array_merge($settings, array('seo_taxonomies' => array())));
vonseowp_native_tax_check(VonSEOWP_Taxonomies::get_current() === null, 'Unselected current term still received metadata.');
update_option('vonseowp_settings', $settings);
foreach (array(1, 2) as $page) {
    $wp_query->set('paged', $page);
    unset($GLOBALS['vonseowp_meta_tags_done']);
    ob_start(); $frontend->output_meta_tags(); $head = (string) ob_get_clean();
    $document = new DOMDocument();
    @$document->loadHTML('<html><head>' . $head . '</head></html>');
    $xpath = new DOMXPath($document);
    $canonical = $xpath->evaluate('string(//link[@rel="canonical"]/@href)');
    $expected = $page === 1 ? $original['vonseowp_term_canonical'] : get_pagenum_link(2);
    vonseowp_native_tax_check($canonical === $expected, 'Canonical pagination collapsed: page ' . $page);
    vonseowp_native_tax_check($xpath->evaluate('string(//meta[@property="og:url"]/@content)') === $canonical, 'Social and canonical URL disagree.');
    vonseowp_native_tax_check($xpath->evaluate('string(//meta[@name="description"]/@content)') === 'Archive SEO description', 'Term description missing.');
    vonseowp_native_tax_check($xpath->evaluate('string(//meta[@name="twitter:description"]/@content)') === 'Social archive description', 'Term social description missing.');
    vonseowp_native_tax_check($frontend->filter_document_title('Native') === 'Archive SEO & Title' . ($page === 2 ? ' - Page 2' : ''), 'Paged title missing.');
    $legacy_title = $frontend->filter_legacy_title('Native', '|', 'right');
    vonseowp_native_tax_check($legacy_title === 'Archive SEO &amp; Title | ', 'Legacy filter must leave pagination to the theme.');
    $theme_title = $legacy_title . get_bloginfo('name') . ($page > 1 ? ' | Page ' . $page : '');
    vonseowp_native_tax_check(substr_count($theme_title, 'Page 2') === ($page > 1 ? 1 : 0), 'Twenty Eleven assembly duplicated the page suffix.');
    $robots = $frontend->filter_robots(array('index' => true));
    vonseowp_native_tax_check(!empty($robots['noindex']) && !isset($robots['index']), 'Term noindex not applied.');
}
delete_term_meta($term->term_id, '_vonseowp_title');
delete_term_meta($term->term_id, '_vonseowp_description');
delete_term_meta($term->term_id, '_vonseowp_noindex');
vonseowp_native_tax_check($frontend->filter_document_title('Native archive') === 'Native archive', 'Empty override lost native title.');
unset($GLOBALS['vonseowp_meta_tags_done']);
ob_start(); $frontend->output_meta_tags(); $fallback_head = (string) ob_get_clean();
vonseowp_native_tax_check(strpos($fallback_head, 'Native archive description &amp; fallback.') !== false, 'Native term-description fallback missing or incorrectly escaped.');
vonseowp_native_tax_check(empty($frontend->filter_robots(array())['noindex']), 'Cleared noindex remained active.');
update_option('blog_public', 0);
vonseowp_native_tax_check(!empty($frontend->filter_robots(array())['noindex']), 'Term override made private site indexable.');
update_option('blog_public', 1);
ob_start(); $engine->render_edit_fields($term); $form = (string) ob_get_clean();
vonseowp_native_tax_check(strpos($form, 'vonseowp_term_nonce') !== false && strpos($form, 'vonseowp_term_canonical') !== false, 'Native edit fields absent.');
update_term_meta($term->term_id, '_vonseowp_title', '"><script>Forbidden</script>');
ob_start(); $engine->render_edit_fields($term); $form = (string) ob_get_clean();
vonseowp_native_tax_check(strpos($form, '<script>Forbidden') === false, 'Stored term field was not escaped.');
$_POST = array();

// Term-only sitemap pagination crosses the 1,000-URL boundary without loading all terms.
foreach (get_posts(array('post_type' => 'post', 'numberposts' => -1, 'post_status' => 'any')) as $existing) wp_delete_post($existing->ID, true);
$post_id = wp_insert_post(array('post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Archive fixture'));
wp_set_object_terms($post_id, array($ids['category']), 'category');
delete_term_meta($ids['category'], '_vonseowp_canonical');
wp_set_object_terms($post_id, array($ids['post_tag']), 'post_tag');
wp_set_object_terms($post_id, array($ids['vonseqc_genre']), 'vonseqc_genre');
$settings['seo_post_types'] = array();
update_option('vonseowp_settings', $settings);
$sitemap = new VonSEOWP_Sitemap();
$count = new ReflectionMethod($sitemap, 'get_total_entries'); $count->setAccessible(true);
$xml = new ReflectionMethod($sitemap, 'output_xml'); $xml->setAccessible(true);
vonseowp_native_tax_check($count->invoke($sitemap) === 4, 'Native sitemap must contain home and three nonempty selected terms.');
update_term_meta($ids['post_tag'], '_vonseowp_noindex', '1');
update_term_meta($ids['vonseqc_genre'], '_vonseowp_canonical', 'https://example.invalid/other/');
vonseowp_native_tax_check($count->invoke($sitemap) === 2, 'Noindex/custom-canonical terms must be filtered before count.');
$bulk_ids = array($ids['post_tag']);
for ($i = 0; $i < 1000; $i++) {
    // Bound the core fixture-generator cache on older WordPress/PHP runtimes.
    if ($i % 50 === 0) wp_cache_flush();
    $result = wp_insert_term('Boundary tag ' . wp_generate_uuid4(), 'post_tag');
    if (is_wp_error($result)) throw new RuntimeException($result->get_error_message());
    $bulk_ids[] = (int) $result['term_id'];
}
wp_set_object_terms($post_id, $bulk_ids, 'post_tag');
vonseowp_native_tax_check($count->invoke($sitemap) === 1002, 'Term sitemap boundary count wrong.');
$locations = array();
foreach (array(1 => 1000, 2 => 2) as $page => $expected) {
    $wp_query->set('vonseowp_sitemap_page', $page);
    ob_start(); $xml->invoke($sitemap, $settings); $document = simplexml_load_string((string) ob_get_clean());
    vonseowp_native_tax_check($document !== false && count($document->url) === $expected, 'Native term sitemap page size wrong.');
    foreach ($document->url as $entry) $locations[] = (string) $entry->loc;
}
vonseowp_native_tax_check(count($locations) === count(array_unique($locations)) && count(array_keys($locations, home_url('/'), true)) === 1, 'Duplicate sitemap entries or repeated home.');
$settings['seo_post_types'] = array('post');
update_option('vonseowp_settings', $settings);
$wp_query->set('vonseowp_sitemap_page', 1);
ob_start(); $xml->invoke($sitemap, $settings); $mixed = simplexml_load_string((string) ob_get_clean());
vonseowp_native_tax_check(count($mixed->url) === 1000 && (string) $mixed->url[1]->loc === get_permalink($post_id), 'Mixed sitemap must allocate post slots before term slots.');
$settings['seo_taxonomies'] = array();
update_option('vonseowp_settings', $settings);
vonseowp_native_tax_check($count->invoke($sitemap) === 2, 'Disabled taxonomy selection still affects sitemap.');
update_option('blog_public', 0);
vonseowp_native_tax_check($count->invoke($sitemap) === 0, 'Private-site sitemap should be empty.');
update_option('blog_public', 1);
file_put_contents($root . '/qc-taxonomy-result.json', wp_json_encode(array('assertions' => $assertions, 'category' => $ids['category'], 'genre' => $ids['vonseqc_genre'])));
echo 'Native taxonomy tests passed (' . $assertions . ' assertions; WordPress ' . $wp_version . ', PHP ' . PHP_VERSION . ').' . PHP_EOL;
