<?php
/** Real registered option/save/schema APIs on the disposable exact ZIP only. */
declare(strict_types=1);
$root = rtrim($argv[1] ?? '', '/\\');
if (!is_file($root . '/qc-owner.json') || !is_file($root . '/wp-load.php')) throw new RuntimeException('Use the owned isolated QC installation.');
require $root . '/wp-load.php';
global $wpdb, $wp_version, $wp_rewrite;
$owner = json_decode((string) file_get_contents($root . '/qc-owner.json'), true);
if (!is_array($owner) || $owner['database'] !== $wpdb->dbname || $owner['root'] !== realpath($root)
    || !preg_match('/^vonseoqc250_[a-f0-9]{8}_$/D', $wpdb->base_prefix)) throw new RuntimeException('Refusing non-QC database.');
if (!defined('VONSEOWP_VERSION') || VONSEOWP_VERSION !== '2.5.0') throw new RuntimeException('Install the patched exact ZIP first.');
wp_set_current_user(1);
$assertions = 0;
$network_calls = 0;
add_filter('pre_http_request', static function ($result, array $args, string $url) {
    if (strpos($url, '/indexnow') !== false) $GLOBALS['network_calls']++;
    return new WP_Error('qc_offline', 'External requests intercepted.');
}, PHP_INT_MAX, 3);
function vonseowp_final_check(bool $condition, string $message): void {
    $GLOBALS['assertions']++;
    if (!$condition) throw new RuntimeException($message);
}
foreach (array('_maybe_update_core', '_maybe_update_plugins', '_maybe_update_themes') as $callback) remove_action('admin_init', $callback);
do_action('admin_init');
vonseowp_final_check(has_filter('sanitize_option_vonseowp_settings') !== false, 'Native registered sanitizer must be active.');
$flags = array('enable_og', 'enable_sitemap', 'sitemap_posts', 'sitemap_pages', 'enable_404_log',
    'auto_image_alt', 'auto_image_title', 'remove_category_base', 'redirect_attachments', 'enable_indexnow', 'enable_rss_footer', 'enable_toc', 'enable_llms_txt');
delete_option('vonseowp_settings');
$passes = 0;
$counter = static function ($input) { $GLOBALS['passes']++; return $input; };
add_filter('sanitize_option_vonseowp_settings', $counter, PHP_INT_MAX);
update_option('vonseowp_settings', array('seo_post_types' => array('post', 'page')));
remove_filter('sanitize_option_vonseowp_settings', $counter, PHP_INT_MAX);
vonseowp_final_check($passes >= 2, 'Real first-option creation must exercise double sanitization.');
foreach ($flags as $flag) vonseowp_final_check(get_option('vonseowp_settings')[$flag] === 0, 'Native first-save turned OFF into ON: ' . $flag);
$title = 'Native new site title ' . wp_generate_uuid4();
$tagline = 'Native new tagline ' . wp_generate_uuid4();
update_option('blogname', $title);
update_option('blogdescription', $tagline);
foreach ($flags as $flag) vonseowp_final_check(get_option('vonseowp_settings')[$flag] === 0, 'Native core sync turned OFF into ON: ' . $flag);
vonseowp_final_check(get_option('vonseowp_settings')['home_title'] === $title && get_option('vonseowp_settings')['home_desc'] === $tagline, 'Native title/tagline sync must still work.');
$id = wp_insert_post(array('post_title' => 'Native & schema product', 'post_name' => 'combined-identity', 'post_author' => 1,
    'post_status' => 'publish', 'post_content' => '<p>Native schema body.</p><!--nextpage--><p>Second page.</p>'), true);
if (is_wp_error($id)) throw new RuntimeException($id->get_error_message());
do_action('vonseowp_indexnow_submit', $id);
vonseowp_final_check($network_calls === 0 && !wp_next_scheduled('vonseowp_indexnow_submit', array($id)), 'Native OFF after sync must not queue or notify.');
$settings = get_option('vonseowp_settings');
$settings = array_merge($settings, array_fill_keys($flags, 1), array('home_title' => 'Plugin new title', 'home_desc' => 'Plugin new tagline'));
update_option('vonseowp_settings', $settings);
vonseowp_final_check(get_option('blogname') === 'Plugin new title' && get_option('blogdescription') === 'Plugin new tagline', 'Native plugin-to-core sync must survive.');
update_option('blogname', 'Native final title');
foreach ($flags as $flag) vonseowp_final_check(get_option('vonseowp_settings')[$flag] === 1, 'Native explicit ON must survive core sync: ' . $flag);
$settings = get_option('vonseowp_settings');
$settings['enable_indexnow'] = 0;
update_option('vonseowp_settings', $settings);
$_POST = array('vonseowp_meta_box_nonce' => wp_create_nonce('vonseowp_meta_box'), 'vonseowp_schema_type' => 'Review',
    'vonseowp_reviewed_product' => 'Actual reviewed <b>product</b>', 'vonseowp_rating' => '4.5', 'vonseowp_rating_count' => '2');
(new VonSEOWP_Meta_Box())->save_meta_box($id);
$_POST = array();
vonseowp_final_check(get_post_meta($id, '_vonseowp_reviewed_product', true) === 'Actual reviewed product', 'New Review field must save through the authorized native metabox path.');
wp_set_current_user(0);
$_POST = array('vonseowp_meta_box_nonce' => wp_create_nonce('vonseowp_meta_box'), 'vonseowp_reviewed_product' => 'Unauthorized overwrite');
(new VonSEOWP_Meta_Box())->save_meta_box($id);
$_POST = array();
vonseowp_final_check(get_post_meta($id, '_vonseowp_reviewed_product', true) === 'Actual reviewed product', 'Anonymous requests must not modify Review data.');
wp_set_current_user(1);
function vonseowp_final_node(int $id, string $type, bool $before_loop = false): array {
    update_post_meta($id, '_vonseowp_schema_type', $type);
    $GLOBALS['wp_query'] = new WP_Query(array('p' => $id));
    if ($before_loop) {
        $GLOBALS['post'] = get_post($id);
    } else {
        $GLOBALS['wp_query']->the_post();
    }
    unset($GLOBALS['vonseowp_json_ld_done']);
    ob_start(); (new VonSEOWP_Frontend())->output_json_ld(); $html = (string) ob_get_clean();
    if (!preg_match('/<script[^>]*>(.*)<\/script>/s', $html, $match)) throw new RuntimeException('Missing native schema output.');
    $graph = json_decode($match[1], true)['@graph'];
    foreach ($graph as $node) if (strpos($node['@id'] ?? '', get_permalink($id) . '#') === 0) return $node;
    throw new RuntimeException('Expected native content node.');
}
$product = vonseowp_final_node($id, 'Product');
vonseowp_final_check($product['name'] === get_the_title($id) && isset($product['aggregateRating']) && !isset($product['headline']), 'Native Product must have its own valid rated shape.');
$review = vonseowp_final_node($id, 'Review');
vonseowp_final_check($review['itemReviewed']['name'] === 'Actual reviewed product' && isset($review['reviewRating'], $review['author']) && !isset($review['aggregateRating']), 'Native Review must identify item/rating/author.');
$expected_author = array('@type' => 'Person', 'name' => get_the_author_meta('display_name', 1), 'url' => get_author_posts_url(1));
foreach (array(null, (object) array('ID' => 999, 'display_name' => 'Unrelated Loop author')) as $loop_author) {
    $GLOBALS['authordata'] = $loop_author;
    foreach (array('Review', 'Article', 'NewsArticle', 'BlogPosting', 'WebPage') as $type) {
        $node = vonseowp_final_node($id, $type, true);
        vonseowp_final_check($node['@type'] === $type && ($node['author'] ?? array()) === $expected_author, 'Native head-time schema must use the post author, not Loop state: ' . $type);
    }
}
$service = vonseowp_final_node($id, 'Service');
vonseowp_final_check(isset($service['name'], $service['provider']) && !isset($service['headline']) && !isset($service['aggregateRating']), 'Native Service must not reuse Article/rating output.');
foreach (array('Article', 'NewsArticle', 'BlogPosting') as $type) vonseowp_final_check(!isset(vonseowp_final_node($id, $type)['aggregateRating']), 'Native articles must not acquire ratings.');
delete_post_meta($id, '_vonseowp_reviewed_product');
vonseowp_final_check(vonseowp_final_node($id, 'Review')['@type'] === 'WebPage' && get_post_meta($id, '_vonseowp_schema_type', true) === 'Review', 'Incomplete legacy Review must preserve saved choice but emit WebPage.');
delete_post_meta($id, '_vonseowp_rating_count');
vonseowp_final_check(vonseowp_final_node($id, 'Product')['@type'] === 'WebPage', 'Native missing Product count must not default to 1.');
update_post_meta($id, '_vonseowp_reviewed_product', 'Actual reviewed product');
update_post_meta($id, '_vonseowp_rating_count', '2');
update_post_meta($id, '_vonseowp_schema_type', 'Review');
$wp_rewrite->set_permalink_structure('/vonseo-qc-articles/%postname%/');
update_option('show_on_front', 'posts');
$settings = get_option('vonseowp_settings');
$settings['enable_toc'] = 0;
$settings['enable_indexnow'] = 0;
$slug = get_post_field('post_name', $id);
$path = '/vonseo-qc-articles/' . $slug . '/';
$settings['redirects_list'] = $path . ' -> ' . $path . '?p=' . $id . '&name=' . $slug;
update_option('vonseowp_settings', $settings);
flush_rewrite_rules(false);
file_put_contents($root . '/qc-final-result.json', wp_json_encode(array('id' => $id, 'slug' => $slug, 'url' => get_permalink($id), 'assertions' => $assertions)));
echo 'Native final patch tests passed (' . $assertions . ' assertions; WP ' . $wp_version . ', PHP ' . PHP_VERSION . '; outbound requests: ' . $network_calls . ')' . PHP_EOL;
