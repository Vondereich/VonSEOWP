<?php
/** Native indexing regressions; writes only disposable QC tables. */
declare(strict_types=1);
$root = rtrim($argv[1] ?? '', '/\\');
$mode = $argv[2] ?? 'indexing';
if (!is_file($root . '/wp-load.php') || !in_array($mode, array('indexing', 'llm-public', 'llm-private', 'llm-disabled', 'llm-default'), true)) {
    throw new RuntimeException('Pass an isolated WordPress root and supported mode.');
}
require_once $root . '/wp-includes/plugin.php';
add_filter('wp_die_handler', static function () {
    return static function (): void {
        fwrite(STDERR, 'WordPress bootstrap failed; this is not a passing test.' . PHP_EOL);
        exit(1);
    };
});
require $root . '/wp-load.php';
global $wpdb, $wp_version;
if (!preg_match('/^vonseoqc250_[a-f0-9]{8}_$/D', $wpdb->base_prefix)
    || !preg_match('/^vonseoqc250_[a-f0-9]{8}$/D', $wpdb->dbname)) throw new RuntimeException('Refusing non-QC database.');
if (!defined('VONSEOWP_VERSION') || VONSEOWP_VERSION !== '2.5.0') throw new RuntimeException('Install the patched exact ZIP first.');
wp_set_current_user(1);
$settings = get_option('vonseowp_settings', array());
$settings = is_array($settings) ? $settings : array();
if ($mode !== 'indexing') {
    $settings['enable_indexnow'] = 0;
    $settings['enable_llms_txt'] = $mode === 'llm-disabled' ? 0 : 1;
    $settings['llms_txt_content'] = $mode === 'llm-default' ? '' : 'NativeCuratedVisibilitySentinel';
    update_option('vonseowp_settings', $settings);
    update_option('blog_public', $mode === 'llm-private' ? 0 : 1);
    echo wp_json_encode(array('mode' => $mode)) . PHP_EOL;
    exit;
}
$assertions = 0;
$calls = array();
function vonseowp_native_indexing_check(bool $condition, string $message): void {
    $GLOBALS['assertions']++;
    if (!$condition) throw new RuntimeException($message);
}
function vonseowp_native_indexing_post(string $type = 'post', array $extra = array()): int {
    $id = wp_insert_post(array_merge(array('post_type' => $type, 'post_status' => 'publish', 'post_author' => 1,
        'post_title' => 'VonSEO native indexing fixture', 'post_name' => 'vonseo-indexing-' . wp_generate_uuid4(),
        'post_content' => '<p>Native fixture.</p>'), $extra), true);
    if (is_wp_error($id)) throw new RuntimeException($id->get_error_message());
    return $id;
}
add_filter('pre_http_request', static function ($result, array $args, string $url) {
    if (strpos($url, '/indexnow') !== false) $GLOBALS['calls'][] = array('url' => $url, 'body' => json_decode($args['body'], true), 'blocking' => $args['blocking']);
    return new WP_Error('vonseo_qc_offline', 'No external requests during QC.');
}, PHP_INT_MAX, 3);
$engine = new VonSEOWP_IndexNow();
// The plugin already registered the real hooks; use this instance only for direct calls.
remove_action('wp_after_insert_post', array($engine, 'auto_submit_url'));
remove_action('vonseowp_indexnow_submit', array($engine, 'submit_post'));
wp_unschedule_hook('vonseowp_indexnow_submit');
update_option('blog_public', 1);
update_option('vonseowp_settings', array());
$id = vonseowp_native_indexing_post();
vonseowp_native_indexing_check(!$calls && !wp_next_scheduled('vonseowp_indexnow_submit', array($id)), 'Missing enablement must stay off after a native publish.');
$enabled = array('enable_indexnow' => 1, 'seo_post_types' => array('post', 'page', 'vonseqc_book'));
update_option('vonseowp_settings', $enabled);
foreach (array('post', 'page', 'vonseqc_book') as $type) {
    $id = vonseowp_native_indexing_post($type);
    $scheduled = wp_next_scheduled('vonseowp_indexnow_submit', array($id));
    vonseowp_native_indexing_check(is_int($scheduled) && $scheduled >= time() + 59 && !$calls, 'Native published selected types must queue, not submit immediately.');
    wp_update_post(array('ID' => $id, 'post_title' => 'Repeated update'));
    vonseowp_native_indexing_check(wp_next_scheduled('vonseowp_indexnow_submit', array($id)) === $scheduled, 'Repeated native saves must share a single event.');
    do_action('vonseowp_indexnow_submit', $id);
    vonseowp_native_indexing_check(count($calls) === 2 && $calls[0]['body']['urlList'] === array(get_permalink($id)) && !$calls[0]['blocking'], 'Queued eligible callback must retain canonical asynchronous payload.');
    $calls = array();
    wp_clear_scheduled_hook('vonseowp_indexnow_submit', array($id));
}
foreach (array(array('post_password' => 'secret'), array('post_password' => '0'), array('meta_input' => array('_vonseowp_noindex' => '1')),
    array('post_status' => 'private'), array('post_status' => 'draft')) as $extra) {
    $id = vonseowp_native_indexing_post('post', $extra);
    $engine->submit_url(add_query_arg(array('p' => $id, 'secret' => 'NeverForward'), home_url('/')));
    do_action('vonseowp_indexnow_submit', $id);
    vonseowp_native_indexing_check(!$calls && !wp_next_scheduled('vonseowp_indexnow_submit', array($id)), 'Native protected/nonindexable lifecycle and aliases must not send or queue.');
}
$late = static function (int $id): void { update_post_meta($id, '_vonseowp_noindex', '1'); };
add_action('save_post', $late, 99);
$id = vonseowp_native_indexing_post();
remove_action('save_post', $late, 99);
vonseowp_native_indexing_check(get_post_meta($id, '_vonseowp_noindex', true) === '1' && !wp_next_scheduled('vonseowp_indexnow_submit', array($id)) && !$calls, 'Late save_post metadata must be visible before scheduling.');

// Gutenberg's REST save precedes its legacy metabox request.
$id = vonseowp_native_indexing_post();
wp_clear_scheduled_hook('vonseowp_indexnow_submit', array($id));
$request = new WP_REST_Request('POST', '/wp/v2/posts/' . $id);
$request->set_param('content', '<p>Updated through native REST.</p>');
$response = rest_do_request($request);
vonseowp_native_indexing_check($response->get_status() === 200 && !$calls && wp_next_scheduled('vonseowp_indexnow_submit', array($id)) !== false, 'REST phase must queue without racing the second metabox save.');
$_POST = array('vonseowp_meta_box_nonce' => wp_create_nonce('vonseowp_meta_box'), 'vonseowp_noindex' => '1');
wp_update_post(array('ID' => $id, 'post_title' => 'Legacy metadata phase'));
$_POST = array();
do_action('vonseowp_indexnow_submit', $id);
vonseowp_native_indexing_check(get_post_meta($id, '_vonseowp_noindex', true) === '1' && !$calls && !wp_next_scheduled('vonseowp_indexnow_submit', array($id)), 'Second legacy metabox save must cancel and block the REST-phase work.');

$id = vonseowp_native_indexing_post();
update_post_meta($id, '_vonseowp_noindex', '1');
do_action('vonseowp_indexnow_submit', $id);
vonseowp_native_indexing_check(!$calls, 'Cron callback must re-read metadata changed after queueing.');
delete_post_meta($id, '_vonseowp_noindex');
update_option('blog_public', 0);
$engine->submit_url(get_permalink($id));
do_action('vonseowp_indexnow_submit', $id);
vonseowp_native_indexing_check(!$calls, 'Queued/direct public URLs must stop when site visibility changes.');
update_option('blog_public', 1);
foreach (array(array('enable_indexnow' => 0), array('enable_indexnow' => 1, 'seo_post_types' => array()),
    array('enable_indexnow' => 1, 'seo_post_types' => array('page'))) as $blocked) {
    update_option('vonseowp_settings', $blocked);
    $engine->submit_url(get_permalink($id));
    do_action('vonseowp_indexnow_submit', $id);
    vonseowp_native_indexing_check(!$calls, 'Delivery must recheck disabled feature/selection.');
}
update_option('vonseowp_settings', $enabled);
$engine->submit_url(add_query_arg(array('p' => $id, 'secret' => 'NeverForward'), home_url('/')) . '#private');
vonseowp_native_indexing_check(count($calls) === 2 && $calls[0]['body']['urlList'] === array(get_permalink($id)), 'Native identity alias must resolve and canonicalize without query/fragment disclosure.');
$calls = array();
$GLOBALS['post'] = get_post($id);
update_post_meta($id, '_vonseowp_noindex', '1');
foreach (array(home_url('/no-such-route/'), 'https://external.invalid/?p=' . $id, '') as $url) $engine->submit_url($url);
vonseowp_native_indexing_check(!$calls, 'Unknown ID=0 must never fall back to the global protected/noindex post.');
delete_post_meta($id, '_vonseowp_noindex');
wp_unschedule_hook('vonseowp_indexnow_submit');
wp_schedule_single_event(time() - 1, 'vonseowp_indexnow_submit', array($id));
// Validate after native wp-cron.php terminates its process.
register_shutdown_function(static function () use ($id, $wp_version): void {
    try {
        vonseowp_native_indexing_check(count($GLOBALS['calls']) === 2 && !wp_next_scheduled('vonseowp_indexnow_submit', array($id)), 'Actual native cron runner must execute and consume the single eligible event.');
        wp_schedule_single_event(time() + 60, 'vonseowp_indexnow_submit', array($id));
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        deactivate_plugins('vonseo/vonseo.php');
        vonseowp_native_indexing_check(!wp_next_scheduled('vonseowp_indexnow_submit', array($id)), 'Native deactivation cleanup must remove argument-bearing events.');
        wp_schedule_single_event(time() + 60, 'vonseowp_indexnow_submit', array($id));
        uninstall_plugin('vonseo/vonseo.php');
        vonseowp_native_indexing_check(!wp_next_scheduled('vonseowp_indexnow_submit', array($id)) && get_option('vonseowp_settings') === false, 'Native uninstall must remove argument-bearing events and plugin settings.');
        vonseowp_native_indexing_check(activate_plugin('vonseo/vonseo.php') === null && is_plugin_active('vonseo/vonseo.php'), 'Plugin must reactivate after disposable uninstall cleanup.');
        update_option('vonseowp_settings', array('enable_indexnow' => 0));
        echo 'Native indexing tests passed (' . $GLOBALS['assertions'] . ' assertions; WP ' . $wp_version . ', PHP ' . PHP_VERSION . '; external requests sent: 0)' . PHP_EOL;
    } catch (Throwable $error) {
        fwrite(STDERR, $error->getMessage() . PHP_EOL);
        exit(1);
    }
});
require ABSPATH . 'wp-cron.php';
exit;
