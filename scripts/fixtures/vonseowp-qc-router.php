<?php
/** Router for the loopback PHP development server; never packaged. */
$vonseowp_qc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
$vonseowp_qc_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (is_string($vonseowp_qc_path) && is_file($vonseowp_qc_root . $vonseowp_qc_path)) return false;
require $vonseowp_qc_root . '/index.php';
