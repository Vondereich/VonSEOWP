<?php
/**
 * VonSEO Uninstall
 *
 * This file is called when the plugin is deleted via the WordPress admin.
 * It cleans up all options and metadata created by the plugin.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// 1. Delete Global Options
$vonseowp_uninstall_options = array(
    'vonseowp_settings',
    'vonseowp_version',
    'vonseowp_indexnow_key',
    'vonseowp_indexnow_file',
    'vonseowp_404_log',
    'vonseowp_flush_sitemap'
);

foreach ($vonseowp_uninstall_options as $vonseowp_option) {
    delete_option($vonseowp_option);
    delete_site_option($vonseowp_option); // For Multisite
}

delete_transient('vonseowp_site_audit_results');

// 2. Delete Post Metadata
global $wpdb;
// Pending administrator previews expire naturally; remove them immediately on deletion too.
foreach (array(
    '_transient_vonseowp_settings_import_', '_transient_timeout_vonseowp_settings_import_',
    // Competitor scan cache (12-hour transients keyed by URL hash).
    '_transient_vonseowp_comp_', '_transient_timeout_vonseowp_comp_',
) as $vonseowp_preview_prefix) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned transient cleanup on uninstall.
    $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name LIKE %s", $wpdb->esc_like($vonseowp_preview_prefix) . '%'));
}
$vonseowp_meta_keys = array(
    '_vonseowp_title',
    '_vonseowp_description',
    '_vonseowp_keywords',
    '_vonseowp_image',
    '_vonseowp_noindex',
    '_vonseowp_schema_type',
    '_vonseowp_rating',
    '_vonseowp_rating_count',
    '_vonseowp_reviewed_product',
    '_vonseowp_social_title',
    '_vonseowp_social_desc',
    '_vonseowp_faq',
    '_vonseowp_video',
    '_vonseowp_disable_toc'
);

foreach ($vonseowp_meta_keys as $vonseowp_key) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE meta_key = %s", $vonseowp_key));
}

// 3. Delete Term Metadata
foreach (array('_vonseowp_title', '_vonseowp_description', '_vonseowp_canonical', '_vonseowp_social_desc', '_vonseowp_noindex') as $vonseowp_term_key) {
    delete_metadata('term', 0, $vonseowp_term_key, '', true);
}

// 4. Clear scheduled events (daily_cleanup is a legacy hook from earlier releases).
wp_clear_scheduled_hook('vonseowp_daily_cleanup');
wp_unschedule_hook('vonseowp_indexnow_submit');
