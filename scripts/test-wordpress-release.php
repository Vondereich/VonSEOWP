<?php
/** Exact-package QC on disposable tables only; never packaged. */
declare(strict_types=1);
$root = rtrim($argv[1] ?? '', '/\\');
$mode = $argv[2] ?? '';
if (!is_file($root . '/wp-load.php') || !in_array($mode, array('seed-upgrade', 'patch-behavior', 'http-seed'), true)) {
    throw new RuntimeException('Pass the isolated WordPress root and a supported QC mode.');
}
if ($mode === 'patch-behavior') define('DOING_AJAX', true);
require $root . '/wp-load.php';
global $wpdb, $wp_version, $wp_rewrite;
if (!preg_match('/^vonseoqc250_[a-f0-9]{8}_$/D', $wpdb->base_prefix)
    || !preg_match('/^vonseoqc250_[a-f0-9]{8}$/D', $wpdb->dbname)) throw new RuntimeException('Refusing non-disposable QC database.');
require_once ABSPATH . 'wp-admin/includes/plugin.php';
wp_set_current_user(1);
$assertions = 0;
function vonseowp_release_check(bool $condition, string $message): void {
    $GLOBALS['assertions']++;
    if (!$condition) throw new RuntimeException($message);
}
function vonseowp_release_post(string $type, string $slug, string $content = '<p>QC fixture.</p>'): int {
    $id = wp_insert_post(array('post_type' => $type, 'post_status' => 'publish', 'post_name' => $slug,
        'post_title' => 'VonSEO disposable QC ' . $slug, 'post_author' => get_current_user_id(), 'post_content' => $content), true);
    if (is_wp_error($id)) throw new RuntimeException($id->get_error_message());
    return $id;
}
if ($mode === 'seed-upgrade') {
    vonseowp_release_check(VONSEOWP_VERSION === '2.4.5', 'Seed the actual prior stable ZIP first.');
    update_option('vonseowp_settings', array('home_desc' => 'UpgradePreservationSentinel', 'enable_indexnow' => 0,
        'enable_og' => 1, 'enable_toc' => 1, 'toc_min_headings' => 2, 'sitemap_posts' => 1, 'sitemap_pages' => 1));
    foreach (array('post', 'page') as $type) {
        $id = vonseowp_release_post($type, 'vonseo-upgrade-' . $type);
        update_post_meta($id, '_vonseowp_title', 'UpgradeTitleSentinel');
        update_post_meta($id, '_vonseowp_description', 'UpgradeDescriptionSentinel');
        update_post_meta($id, '_vonseowp_noindex', '1');
        update_post_meta($id, '_vonseowp_faq', array(array('q' => 'UpgradeQ', 'a' => 'UpgradeA')));
    }
    echo wp_json_encode(array('seeded_prior_version' => VONSEOWP_VERSION, 'metadata_rows' => 8)) . PHP_EOL;
    exit;
}
vonseowp_release_check(VONSEOWP_VERSION === '2.5.0' && is_plugin_active('vonseo/vonseo.php'), 'The exact 2.5.0 ZIP must be active.');
if ($mode === 'patch-behavior') {
    vonseowp_release_check(VonSEOWP_Content_Types::get_enabled() === array('post', 'page'), 'Upgrade defaults must remain post/page.');
    register_post_type('vonseqc_custom', array('public' => true, 'capability_type' => array('vonseqc_book', 'vonseqc_books'), 'map_meta_cap' => true));
    $capabilities = array('read' => true, 'edit_vonseqc_books' => true, 'edit_published_vonseqc_books' => true,
        'publish_vonseqc_books' => true, 'delete_vonseqc_books' => true);
    add_role('vonseqc_book_editor', 'QC CPT-only editor', $capabilities);
    $user = wp_insert_user(array('user_login' => 'vonseo_cpt_only', 'user_pass' => wp_generate_password(32, true),
        'user_email' => 'cpt-only@example.invalid', 'role' => 'vonseqc_book_editor'));
    if (is_wp_error($user)) throw new RuntimeException($user->get_error_message());
    wp_set_current_user($user);
    $id = vonseowp_release_post('vonseqc_custom', 'vonseo-custom-caps');
    wp_set_current_user(1);
    $other = vonseowp_release_post('vonseqc_custom', 'vonseo-other-owner');
    $settings = get_option('vonseowp_settings');
    $settings['seo_post_types'] = array('post', 'page', 'vonseqc_custom');
    update_option('vonseowp_settings', $settings);
    wp_set_current_user($user);
    vonseowp_release_check(current_user_can('edit_post', $id) && !current_user_can('edit_posts'), 'Native custom-role mapping must reproduce CPT-only authorization.');
    $_POST = array('vonseowp_meta_box_nonce' => wp_create_nonce('vonseowp_meta_box'), 'vonseowp_title' => 'NativeCustomCapabilityTitle');
    (new VonSEOWP_Meta_Box())->save_meta_box($id);
    vonseowp_release_check(get_post_meta($id, '_vonseowp_title', true) === 'NativeCustomCapabilityTitle', 'Native CPT editor must save metadata.');
    $url = 'https://cached-competitor.invalid/';
    set_transient('vonseowp_comp_' . md5($url), array('title' => 'NativeCachedCompetitor'), HOUR_IN_SECONDS);
    $network_calls = 0;
    add_filter('pre_http_request', static function () {
        $GLOBALS['network_calls']++;
        return new WP_Error('qc_offline', 'Network disabled.');
    });
    $die = static function () { throw new RuntimeException('QC_JSON_DONE'); };
    add_filter('wp_die_ajax_handler', static function () use ($die) { return $die; });
    $request = static function (array $input): array {
        $_POST = $input;
        $_REQUEST = $input;
        ob_start();
        try { (new VonSEOWP_Competitors())->ajax_scan_competitor(); }
        catch (RuntimeException $error) { if ($error->getMessage() !== 'QC_JSON_DONE') { ob_end_clean(); throw $error; } }
        $json = json_decode((string) ob_get_clean(), true);
        if (!is_array($json)) throw new RuntimeException('Expected native JSON response.');
        return $json;
    };
    $valid = array('post_id' => (string) $id, 'url' => $url, 'nonce' => wp_create_nonce('vonseowp_meta_box'));
    $result = $request($valid);
    vonseowp_release_check($result['success'] && $result['data']['title'] === 'NativeCachedCompetitor', 'Native CPT-only editor must use Competitor.');
    foreach (array((string) $other, '0', '-1', array($id), '999999') as $denied_id) {
        vonseowp_release_check(!$request(array_replace($valid, array('post_id' => $denied_id)))['success'], 'Uneditable/malformed native AJAX IDs must be rejected.');
    }
    wp_set_current_user(1);
    $settings['seo_post_types'] = array('post', 'page');
    update_option('vonseowp_settings', $settings);
    wp_set_current_user($user);
    vonseowp_release_check(!$request($valid)['success'], 'Disabled CPT must reject the native AJAX tool.');
    wp_set_current_user(0);
    vonseowp_release_check(!$request(array_replace($valid, array('nonce' => wp_create_nonce('vonseowp_meta_box'))))['success'], 'Anonymous native AJAX must not authorize content.');
    vonseowp_release_check($network_calls === 0, 'Cached/denied native AJAX must not reach network.');
    wp_set_current_user(1);
    $settings['seo_post_types'] = array('post', 'page');
    update_option('vonseowp_settings', $settings);
    echo 'Native release/CPT-capability regressions passed (' . $assertions . ' assertions; WP ' . $wp_version . ', PHP ' . PHP_VERSION . ')' . PHP_EOL;
    exit;
}
update_option('home', 'http://127.0.0.1:8766');
update_option('siteurl', 'http://127.0.0.1:8766');
$wp_rewrite->set_permalink_structure('/vonseo-qc-articles/%postname%/');
update_option('show_on_front', 'posts');
update_option('blog_public', 1);
register_post_type('vonseqc_book', array('public' => true, 'rewrite' => array('slug' => 'vonseo-qc-books', 'with_front' => false)));
$loop = vonseowp_release_post('post', 'loop', '<p>One.</p><!--nextpage--><p>Two.</p>');
$multi = vonseowp_release_post('post', 'multi', '<p>One.</p><!--nextpage--><p>Two.</p>');
$article = vonseowp_release_post('post', 'same-slug');
$book = vonseowp_release_post('vonseqc_book', 'same-slug');
$locked = vonseowp_release_post('post', 'protected', '<p>NativeProtectedBodySentinel</p>');
wp_update_post(array('ID' => $locked, 'post_password' => 'fixture-only'));
update_post_meta($locked, '_vonseowp_description', 'NativeProtectedDescriptionSentinel');
update_option('vonseowp_settings', array('seo_post_types' => array('post', 'page', 'vonseqc_book'), 'enable_indexnow' => 0,
    'enable_og' => 1, 'enable_sitemap' => 1, 'sitemap_posts' => 1, 'sitemap_pages' => 1,
    'redirects_list' => "/vonseo-qc-articles/loop/ -> /vonseo-qc-articles/loop/?p=$loop\n"
        . "/vonseo-qc-articles/multi/ -> /vonseo-qc-articles/multi/?p=$multi&page=2\n"
        . '/vonseo-qc-books/same-slug/ -> /vonseo-qc-articles/same-slug/?name=same-slug'));
flush_rewrite_rules(false);
echo wp_json_encode(array('loop' => $loop, 'multi' => $multi, 'article' => $article, 'book' => $book, 'protected' => $locked)) . PHP_EOL;
