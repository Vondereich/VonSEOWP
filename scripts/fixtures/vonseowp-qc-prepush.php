<?php
/** Offline MU fixture for disposable exact-package QC only; never packaged. */
if (!defined('ABSPATH')) exit;
global $wpdb;
if (!preg_match('/^vonseoqc250_[a-f0-9]{8}_$/D', $wpdb->base_prefix)
    || !preg_match('/^vonseoqc250_[a-f0-9]{8}$/D', $wpdb->dbname)) return;
add_filter('pre_http_request', static function () {
    return new WP_Error('vonseo_qc_offline', 'External requests disabled during isolated pre-push QC.');
}, PHP_INT_MAX);
add_action('init', static function (): void {
    register_post_type('vonseqc_book', array('public' => true, 'show_in_rest' => true,
        'supports' => array('title', 'editor'), 'rewrite' => array('slug' => 'vonseo-qc-books', 'with_front' => false),
        'labels' => array('name' => 'QC Books', 'singular_name' => 'QC Book')));
});
