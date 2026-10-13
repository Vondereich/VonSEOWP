<?php
/** Creates/removes only nonce-named disposable QC databases; never packaged. */
declare(strict_types=1);
$root = realpath($argv[1] ?? '');
$database = $argv[2] ?? '';
$mode = $argv[3] ?? '';
$zip = $argv[4] ?? '';
$temp = realpath((string) getenv('LOCALAPPDATA'));
if (!$root || !$temp || strpos(strtolower(str_replace('\\', '/', $root)), strtolower(str_replace('\\', '/', $temp)) . '/') !== 0
    || !preg_match('~/Temp/vonseo-final-patch-250-[a-f0-9]{8}/wp-(current|minimum)$~iD', str_replace('\\', '/', $root))
    || !preg_match('/^vonseoqc250_[a-f0-9]{8}$/D', $database) || !in_array($mode, array('install', 'cleanup'), true)) {
    throw new RuntimeException('Pass a nonce-named disposable temporary WordPress root/database.');
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connection = new mysqli('127.0.0.1', 'root', '');
$marker = $root . '/qc-owner.json';
if ($mode === 'cleanup') {
    $owner = is_file($marker) ? json_decode((string) file_get_contents($marker), true) : null;
    if (!is_array($owner) || $owner['database'] !== $database || $owner['root'] !== $root) throw new RuntimeException('QC ownership mismatch.');
    $tables = $connection->query('SHOW TABLES FROM `' . $database . '`');
    $count = 0;
    while ($row = $tables->fetch_row()) {
        if (strpos($row[0], $database . '_') !== 0) throw new RuntimeException('Unexpected table outside owned prefix.');
        $count++;
    }
    $connection->query('DROP DATABASE `' . $database . '`');
    echo json_encode(array('removed_database' => $database, 'tables' => $count)) . PHP_EOL;
    exit;
}
if (is_file($root . '/wp-config.php') || is_file($marker) || !is_file($root . '/wp-load.php') || !is_file($zip)) throw new RuntimeException('Use an unconfigured fresh WordPress copy and existing ZIP.');
$statement = $connection->prepare('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?');
$statement->bind_param('s', $database);
$statement->execute();
if ($statement->get_result()->num_rows) throw new RuntimeException('Refusing an existing database.');
$connection->query('CREATE DATABASE `' . $database . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
file_put_contents($marker, json_encode(array('database' => $database, 'root' => $root)));
$config = "<?php\n";
foreach (array('DB_NAME' => $database, 'DB_USER' => 'root', 'DB_PASSWORD' => '', 'DB_HOST' => '127.0.0.1', 'DB_CHARSET' => 'utf8mb4', 'DB_COLLATE' => '',
    'WP_HOME' => 'http://127.0.0.1:8766', 'WP_SITEURL' => 'http://127.0.0.1:8766', 'WP_HTTP_BLOCK_EXTERNAL' => true, 'DISABLE_WP_CRON' => true,
    'FS_METHOD' => 'direct', 'WP_DEBUG' => true, 'WP_DEBUG_DISPLAY' => false, 'WP_DEBUG_LOG' => true) as $key => $value) {
    $config .= 'define(' . var_export($key, true) . ', ' . var_export($value, true) . ");\n";
}
foreach (array('AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT') as $key) {
    $config .= 'define(' . var_export($key, true) . ', ' . var_export(bin2hex(random_bytes(32)), true) . ");\n";
}
$config .= '$table_prefix = ' . var_export($database . '_', true) . ";\n";
$config .= "if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');\nrequire_once ABSPATH . 'wp-settings.php';\n";
file_put_contents($root . '/wp-config.php', $config);
define('WP_INSTALLING', true);
require $root . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
add_filter('pre_wp_mail', '__return_true');
$password = bin2hex(random_bytes(24));
$installed = wp_install('VonSEO isolated QC', 'vonseo_qc_admin', 'qc@example.invalid', true, '', $password);
if (is_wp_error($installed)) throw new RuntimeException($installed->get_error_message());
file_put_contents($root . '/browser-auth.json', json_encode(array('username' => 'vonseo_qc_admin', 'password' => $password)));
$archive = new ZipArchive();
if ($archive->open($zip) !== true) throw new RuntimeException('Cannot read QC ZIP.');
for ($i = 0; $i < $archive->numFiles; $i++) {
    $name = $archive->getNameIndex($i);
    if (strpos($name, 'vonseo/') !== 0 || strpos($name, '..') !== false) throw new RuntimeException('Unsafe ZIP member.');
}
$archive->extractTo(WP_PLUGIN_DIR);
$archive->close();
wp_set_current_user(1);
$result = activate_plugin('vonseo/vonseo.php');
if (is_wp_error($result)) throw new RuntimeException($result->get_error_message());
global $wp_version;
echo json_encode(array('installed_wp' => $wp_version, 'php' => PHP_VERSION, 'plugin' => get_plugin_data(WP_PLUGIN_DIR . '/vonseo/vonseo.php', false, false)['Version'], 'database' => $database)) . PHP_EOL;
