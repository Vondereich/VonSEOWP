<?php
/** Temporary MU fixture for an isolated local WordPress QC instance only. */
if (!defined('ABSPATH')) exit;
global $wpdb;
if (!preg_match('/^vonseoqc250_[a-f0-9]{8}_$/D', $wpdb->base_prefix)) return;
add_action('init', static function (): void {
    register_post_type('vonseqc_book', array(
        'public' => true,
        'show_in_rest' => true,
        'supports' => array('title', 'editor'),
        'labels' => array('name' => 'QC Books', 'singular_name' => 'QC Book'),
    ));
});
add_filter('pre_http_request', static function ($result, $args, $url) {
    if (strpos($url, 'https://www.bing.com/indexnow') === 0 || strpos($url, 'https://yandex.com/indexnow') === 0) {
        return new WP_Error('vonseo_qc_no_publication', 'External publication disabled during QC.');
    }
    return $result;
}, 10, 3);
