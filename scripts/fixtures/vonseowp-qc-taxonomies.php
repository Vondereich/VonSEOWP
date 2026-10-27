<?php
/** Public/private custom-taxonomy fixtures for owned isolated QC only. */
if (!defined('ABSPATH') || !is_file(ABSPATH . 'qc-owner.json')) return;
add_action('init', static function (): void {
    register_taxonomy('vonseqc_genre', 'post', array('public' => true, 'show_ui' => true,
        'rewrite' => array('slug' => 'genre'), 'label' => 'QC Genres'));
    register_taxonomy('vonseqc_private', 'post', array('public' => false, 'show_ui' => true));
}, 0);
add_filter('pre_http_request', static function () {
    return new WP_Error('vonseowp_qc_offline', 'External requests blocked in isolated QC.');
}, PHP_INT_MAX);
