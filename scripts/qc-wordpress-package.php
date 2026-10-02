<?php
/** Local QC helper. Explicit modes: state, upgrade, check, fixture-create, fixture-delete, cleanup-fresh. */
declare(strict_types=1);
$root = rtrim($argv[1] ?? '', '/\\');
$mode = $argv[2] ?? '';
if (!is_file($root . '/wp-load.php') || !in_array($mode, array('state', 'upgrade', 'check', 'fixture-create', 'fixture-delete', 'cleanup-fresh'), true)) {
    fwrite(STDERR, 'Usage: php qc-wordpress-package.php WP_ROOT MODE [arguments]' . PHP_EOL);
    exit(1);
}
define('FS_METHOD', 'direct');
require $root . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
add_filter('pre_http_request', static function ($result, $args, $url) {
    if (strpos($url, 'https://www.bing.com/indexnow') === 0 || strpos($url, 'https://yandex.com/indexnow') === 0) {
        return new WP_Error('vonseo_qc_no_indexnow', 'No external publication during local QC.');
    }
    return $result;
}, 10, 3);
if ($mode === 'cleanup-fresh') {
    global $wpdb;
    $expected_prefix = $argv[3] ?? '';
    if (!preg_match('/^vonseoqc245_[a-f0-9]{8}_$/D', $expected_prefix) || $wpdb->base_prefix !== $expected_prefix) {
        throw new RuntimeException('Refusing cleanup outside the dedicated fresh-QC table prefix.');
    }
    $tables = $wpdb->get_col($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($expected_prefix) . '%'));
    foreach ($tables as $table) {
        if (!preg_match('/^' . preg_quote($expected_prefix, '/') . '[a-z0-9_]+$/D', $table)) throw new RuntimeException('Unexpected fresh-QC table name.');
        if ($wpdb->query('DROP TABLE `' . esc_sql($table) . '`') === false) throw new RuntimeException('Fresh-QC cleanup failed.');
    }
    echo wp_json_encode(array('fresh_tables_removed' => count($tables))) . PHP_EOL;
} elseif ($mode === 'state') {
    global $wpdb;
    echo wp_json_encode(array(
        'settings' => hash('sha256', serialize(get_option('vonseowp_settings'))),
        'metadata' => hash('sha256', serialize($wpdb->get_results($wpdb->prepare("SELECT meta_id, post_id, meta_key, meta_value FROM $wpdb->postmeta WHERE meta_key LIKE %s ORDER BY meta_id", $wpdb->esc_like('_vonseowp_') . '%'), ARRAY_A))),
        'active_plugins' => hash('sha256', serialize(get_option('active_plugins'))),
        'runtime_tables' => $wpdb->get_col($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($wpdb->base_prefix . 'pc_') . '%')),
        'object_cache' => file_exists(WP_CONTENT_DIR . '/object-cache.php'),
    )) . PHP_EOL;
} elseif ($mode === 'upgrade') {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/update.php';
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    global $wpdb;
    $snapshot = static function () use ($wpdb) {
        return array(
            'settings' => hash('sha256', serialize(get_option('vonseowp_settings'))),
            'metadata' => hash('sha256', serialize($wpdb->get_results($wpdb->prepare("SELECT meta_id, post_id, meta_key, meta_value FROM $wpdb->postmeta WHERE meta_key LIKE %s ORDER BY meta_id", $wpdb->esc_like('_vonseowp_') . '%'), ARRAY_A))),
            'metadata_count' => (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key LIKE %s", $wpdb->esc_like('_vonseowp_') . '%')),
            'active_plugins' => get_option('active_plugins'),
        );
    };
    $before = $snapshot();
    $versions = array();
    foreach (array_slice($argv, 3) as $zip) {
        if (!is_file($zip)) throw new RuntimeException('Upgrade ZIP missing.');
        $update = (object)array('response' => array('vonseo/vonseo.php' => (object)array('slug' => 'vonseo', 'plugin' => 'vonseo/vonseo.php', 'new_version' => 'qc', 'package' => $zip)));
        $filter = static function () use ($update) { return $update; };
        add_filter('pre_site_transient_update_plugins', $filter);
        $skin = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader($skin);
        ob_start();
        $result = $upgrader->bulk_upgrade(array('vonseo/vonseo.php'));
        ob_end_clean();
        remove_filter('pre_site_transient_update_plugins', $filter);
        if (!is_array($result) || empty($result['vonseo/vonseo.php']) || is_wp_error($result['vonseo/vonseo.php'])) throw new RuntimeException('Native bulk upgrade failed.');
        $versions[] = get_plugin_data(WP_PLUGIN_DIR . '/vonseo/vonseo.php', false, false)['Version'];
        if ($snapshot() !== $before) throw new RuntimeException('Native upgrade changed settings, metadata or activation.');
    }
    echo wp_json_encode(array('versions' => $versions, 'preserved' => true, 'metadata_count' => $before['metadata_count'], 'active' => is_plugin_active('vonseo/vonseo.php'))) . PHP_EOL;
} elseif ($mode === 'check') {
    if (!class_exists('WordPress\\Plugin_Check\\Checker\\Default_Check_Repository')) require WP_PLUGIN_DIR . '/plugin-check/plugin.php';
    $repository = new WordPress\Plugin_Check\Checker\Default_Check_Repository();
    $checks = $repository->get_checks(WordPress\Plugin_Check\Checker\Check_Repository::TYPE_STATIC)->to_array();
    $context = new WordPress\Plugin_Check\Checker\Check_Context(WP_PLUGIN_DIR . '/vonseo/vonseo.php', 'vonseo', 'update');
    $result = (new WordPress\Plugin_Check\Checker\Checks())->run_checks($context, $checks);
    echo wp_json_encode(array('checks' => count($checks), 'errors' => $result->get_error_count(), 'warnings' => $result->get_warning_count(), 'details' => array($result->get_errors(), $result->get_warnings()))) . PHP_EOL;
    if ($result->get_error_count() || $result->get_warning_count()) exit(1);
} elseif ($mode === 'fixture-create') {
    $protected = ($argv[3] ?? '') === 'protected';
    $id = wp_insert_post(array(
        'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'VonSEO QC fixture ' . wp_generate_uuid4(),
        'post_password' => $protected ? 'vonseo-qc-password' : '',
        'post_content' => '[vonseo_toc]<p>PrivateBrowserBodySentinel fixture.</p><h2><strong>Setup</strong> &amp; Install</h2><p>First section.</p><h2>Setup</h2><p>Second section.</p><h3 id="author-target">Existing ID</h3><p>Third section.</p><h2>Long heading with words that must wrap naturally inside the narrow mobile contents area</h2><p>Last section.</p>',
    ), true);
    if (is_wp_error($id)) throw new RuntimeException($id->get_error_message());
    update_post_meta($id, '_vonseo_qc_fixture', '244');
    update_post_meta($id, '_vonseowp_disable_toc', '1');
    update_post_meta($id, '_vonseowp_faq', array(array('q' => 'PrivateBrowserQuestionSentinel', 'a' => 'PrivateBrowserAnswerSentinel')));
    update_post_meta($id, '_vonseowp_video', array('url' => 'https://example.test/PrivateBrowserVideoSentinel', 'name' => 'Video', 'desc' => ''));
    echo wp_json_encode(array('id' => $id, 'url' => get_permalink($id), 'protected' => $protected)) . PHP_EOL;
} else {
    $id = (int)($argv[3] ?? 0);
    if (!$id || get_post_meta($id, '_vonseo_qc_fixture', true) !== '244') throw new RuntimeException('Not a matching QC fixture; refusing deletion.');
    if (!wp_delete_post($id, true)) throw new RuntimeException('Fixture cleanup failed.');
    echo wp_json_encode(array('deleted' => $id)) . PHP_EOL;
}
