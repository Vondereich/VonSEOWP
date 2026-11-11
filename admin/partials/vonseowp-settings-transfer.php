<?php
/** @var array $vonseowp_pending @var array $vonseowp_current @var string $vonseowp_token @var bool $vonseowp_imported */
if (!defined('ABSPATH')) exit;
?>
<section class="von-transfer-section" aria-labelledby="von-transfer-title">
    <h3 id="von-transfer-title"><?php esc_html_e('Import / Export Settings', 'vonseo'); ?></h3>
    <p><?php esc_html_e('Transfer supported VonSEO site settings only. Posts, term metadata, media files, verification tokens, IndexNow consent/key, logs and caches are excluded. Existing excluded settings are kept.', 'vonseo'); ?></p>
    <p><?php esc_html_e('Exports may contain business contact details, redirect rules and site URLs. Keep them private. URLs are not rewritten for another site; review image links, robots.txt, llms.txt and redirects before importing.', 'vonseo'); ?></p>
    <?php if ($vonseowp_imported) : ?>
        <div class="notice notice-success inline"><p><?php esc_html_e('The reviewed settings were imported.', 'vonseo'); ?></p></div>
    <?php endif; ?>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="von-transfer-export">
        <input type="hidden" name="action" value="vonseowp_export_settings">
        <?php wp_nonce_field('vonseowp_export_settings'); ?>
        <button type="submit" class="von-btn-secondary"><span class="dashicons dashicons-download" aria-hidden="true"></span><?php esc_html_e('Export JSON', 'vonseo'); ?></button>
    </form>
    <hr>
    <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="von-transfer-upload">
        <input type="hidden" name="action" value="vonseowp_preview_settings">
        <input type="hidden" name="MAX_FILE_SIZE" value="131072">
        <?php wp_nonce_field('vonseowp_preview_settings'); ?>
        <label for="vonseowp-settings-file"><?php esc_html_e('Settings file (.json, up to 128 KB)', 'vonseo'); ?></label>
        <input type="file" id="vonseowp-settings-file" name="vonseowp_settings_file" accept=".json,application/json" required>
        <button type="submit" class="von-btn-secondary"><span class="dashicons dashicons-search" aria-hidden="true"></span><?php esc_html_e('Preview Import', 'vonseo'); ?></button>
    </form>
    <?php if ($vonseowp_token !== '' && !$vonseowp_pending) : ?>
        <div class="notice notice-warning inline"><p><?php esc_html_e('The preview expired or is unavailable. Upload the file again.', 'vonseo'); ?></p></div>
    <?php endif; ?>
    <?php if ($vonseowp_pending) : ?>
        <h4><?php esc_html_e('Review Changes', 'vonseo'); ?></h4>
        <p><?php esc_html_e('Only changed fields are listed. Unlisted and excluded settings remain unchanged. Site Title and Tagline also synchronize with WordPress General Settings. This preview expires after 10 minutes.', 'vonseo'); ?></p>
        <div class="von-transfer-table-wrap" tabindex="0" role="region" aria-label="<?php esc_attr_e('Settings changes', 'vonseo'); ?>">
            <table class="widefat striped von-transfer-table">
                <thead><tr><th scope="col"><?php esc_html_e('Setting', 'vonseo'); ?></th><th scope="col"><?php esc_html_e('Current', 'vonseo'); ?></th><th scope="col"><?php esc_html_e('After Import', 'vonseo'); ?></th></tr></thead>
                <tbody>
                <?php $vonseowp_changes = 0; ?>
                <?php foreach ($vonseowp_pending['settings'] as $vonseowp_key => $vonseowp_value) : ?>
                    <?php if (array_key_exists($vonseowp_key, $vonseowp_current) && $vonseowp_current[$vonseowp_key] === $vonseowp_value) continue; ?>
                    <?php $vonseowp_changes++; ?>
                    <tr>
                        <th scope="row"><code><?php echo esc_html($vonseowp_key); ?></code></th>
                        <td><pre><?php echo esc_html(array_key_exists($vonseowp_key, $vonseowp_current) ? (string) wp_json_encode($vonseowp_current[$vonseowp_key], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : __('Not saved (default)', 'vonseo')); ?></pre></td>
                        <td><pre><?php echo esc_html((string) wp_json_encode($vonseowp_value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></pre></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$vonseowp_changes) : ?>
                    <tr><td colspan="3"><?php esc_html_e('No settings changes.', 'vonseo'); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="von-transfer-confirm">
            <input type="hidden" name="action" value="vonseowp_apply_settings">
            <input type="hidden" name="vonseowp_preview" value="<?php echo esc_attr($vonseowp_token); ?>">
            <?php wp_nonce_field('vonseowp_apply_settings'); ?>
            <label class="von-checkbox-row"><input type="checkbox" name="vonseowp_confirm" value="1" required><span><?php esc_html_e('I reviewed these changes, including site identity, public URLs and any enabled features.', 'vonseo'); ?></span></label>
            <div class="von-transfer-actions">
                <button type="submit" class="von-btn-primary"><span class="dashicons dashicons-yes" aria-hidden="true"></span><?php esc_html_e('Confirm Import', 'vonseo'); ?></button>
                <button type="submit" name="vonseowp_cancel" value="1" formnovalidate class="von-btn-secondary"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span><?php esc_html_e('Cancel', 'vonseo'); ?></button>
            </div>
        </form>
    <?php endif; ?>
</section>
