<?php if (!defined('ABSPATH')) exit; ?>
<?php
$vonseowp_has_results = !empty($vonseowp_audit_results);
$vonseowp_technical = $vonseowp_has_results && isset($vonseowp_audit_results['technical']) && is_array($vonseowp_audit_results['technical'])
    ? $vonseowp_audit_results['technical']
    : array();
$vonseowp_items = $vonseowp_has_results && isset($vonseowp_audit_results['items']) && is_array($vonseowp_audit_results['items'])
    ? $vonseowp_audit_results['items']
    : array();
$vonseowp_current_page = $vonseowp_has_results && isset($vonseowp_audit_results['page'])
    ? max(1, (int) $vonseowp_audit_results['page'])
    : 1;
$vonseowp_total_pages = $vonseowp_has_results && isset($vonseowp_audit_results['pages'])
    ? max(1, (int) $vonseowp_audit_results['pages'])
    : 1;
$vonseowp_range_start = $vonseowp_has_results && isset($vonseowp_audit_results['range_start'])
    ? max(0, (int) $vonseowp_audit_results['range_start'])
    : 0;
$vonseowp_range_end = $vonseowp_has_results && isset($vonseowp_audit_results['range_end'])
    ? max(0, (int) $vonseowp_audit_results['range_end'])
    : 0;
$vonseowp_passed_checks = 0;
foreach ($vonseowp_technical as $vonseowp_check) {
    if (!empty($vonseowp_check['passed'])) {
        $vonseowp_passed_checks++;
    }
}
$vonseowp_total_findings = 0;
if ($vonseowp_has_results && isset($vonseowp_audit_results['issue_counts']) && is_array($vonseowp_audit_results['issue_counts'])) {
    $vonseowp_total_findings = array_sum(array_map('intval', $vonseowp_audit_results['issue_counts']));
}
?>
<div class="von-dashboard-wrapper von-audit-page">
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="von-audit-form">
        <input type="hidden" name="action" value="vonseowp_run_site_audit">
        <?php wp_nonce_field('vonseowp_run_site_audit'); ?>

        <div class="von-header">
            <div class="von-header-left">
                <div class="von-brand-mark" aria-hidden="true">
                    <span class="dashicons dashicons-search"></span>
                </div>
                <div class="von-brand-copy">
                    <span class="von-logo"><?php esc_html_e('Site SEO Audit', 'vonseo'); ?></span>
                    <span class="von-brand-description"><?php esc_html_e('Manual, local checks for technical settings and published content.', 'vonseo'); ?></span>
                </div>
            </div>

            <button type="submit" name="vonseowp_audit_page" value="<?php echo esc_attr((string) $vonseowp_current_page); ?>" class="button button-primary von-audit-run">
                <span class="dashicons dashicons-update" aria-hidden="true"></span>
                <?php echo esc_html($vonseowp_has_results ? __('Run Again', 'vonseo') : __('Run Audit', 'vonseo')); ?>
            </button>
        </div>

    <?php if ('complete' === $vonseowp_audit_notice) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Audit batch completed and cached for 12 hours.', 'vonseo'); ?></p></div>
    <?php endif; ?>

    <?php if (!$vonseowp_has_results) : ?>
        <section class="von-audit-empty">
            <span class="dashicons dashicons-analytics" aria-hidden="true"></span>
            <h2><?php esc_html_e('No audit results yet', 'vonseo'); ?></h2>
            <p><?php esc_html_e('Run a manual scan, then move through published content in safe batches of 25 items.', 'vonseo'); ?></p>
        </section>
    <?php else : ?>
        <div class="von-audit-meta">
            <?php
            printf(
                /* translators: %s: audit date and time */
                esc_html__('Last scan: %s', 'vonseo'),
                esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), (int) $vonseowp_audit_results['generated_at']))
            );
            ?>
        </div>

        <section class="von-audit-summary" aria-label="<?php esc_attr_e('Audit summary', 'vonseo'); ?>">
            <div class="von-audit-stat">
                <span><?php esc_html_e('Content scanned', 'vonseo'); ?></span>
                <strong>
                    <?php
                    echo esc_html(
                        $vonseowp_range_start > 0
                            ? $vonseowp_range_start . '-' . $vonseowp_range_end
                            : '0'
                    );
                    ?>
                </strong>
                <small>
                    <?php
                    printf(
                        /* translators: %d: total number of published posts and pages */
                        esc_html__('of %d published', 'vonseo'),
                        (int) $vonseowp_audit_results['total']
                    );
                    ?>
                </small>
            </div>
            <div class="von-audit-stat">
                <span><?php esc_html_e('Technical checks', 'vonseo'); ?></span>
                <strong><?php echo esc_html($vonseowp_passed_checks . '/' . count($vonseowp_technical)); ?></strong>
                <small><?php esc_html_e('passed', 'vonseo'); ?></small>
            </div>
            <div class="von-audit-stat">
                <span><?php esc_html_e('Content findings', 'vonseo'); ?></span>
                <strong><?php echo esc_html((string) $vonseowp_total_findings); ?></strong>
                <small><?php esc_html_e('review items', 'vonseo'); ?></small>
            </div>
        </section>

        <section class="von-audit-section">
            <div class="von-audit-section-heading">
                <div>
                    <h2><?php esc_html_e('Technical SEO', 'vonseo'); ?></h2>
                    <p><?php esc_html_e('Configuration checks performed without external requests.', 'vonseo'); ?></p>
                </div>
            </div>
            <div class="von-audit-table-wrap">
                <table class="von-table von-audit-table">
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e('Status', 'vonseo'); ?></th>
                            <th scope="col"><?php esc_html_e('Check', 'vonseo'); ?></th>
                            <th scope="col"><?php esc_html_e('Detail', 'vonseo'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vonseowp_technical as $vonseowp_check) : ?>
                            <tr>
                                <td>
                                    <span class="von-audit-status <?php echo !empty($vonseowp_check['passed']) ? 'is-pass' : 'is-review'; ?>">
                                        <span class="dashicons dashicons-<?php echo !empty($vonseowp_check['passed']) ? 'yes-alt' : 'warning'; ?>" aria-hidden="true"></span>
                                        <?php echo esc_html(!empty($vonseowp_check['passed']) ? __('Pass', 'vonseo') : __('Review', 'vonseo')); ?>
                                    </span>
                                </td>
                                <td><strong><?php echo esc_html((string) $vonseowp_check['label']); ?></strong></td>
                                <td><?php echo esc_html((string) $vonseowp_check['detail']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="von-audit-section">
            <div class="von-audit-section-heading">
                <div>
                    <h2><?php esc_html_e('Published Content', 'vonseo'); ?></h2>
                    <p>
                        <?php
                        printf(
                            /* translators: %d: maximum number of posts checked per batch */
                            esc_html__('Only posts with findings are listed. Each batch checks at most %d items, ordered by latest modification.', 'vonseo'),
                            (int) $vonseowp_audit_results['limit']
                        );
                        ?>
                    </p>
                </div>
            </div>

            <?php if (empty($vonseowp_items)) : ?>
                <div class="von-audit-clear">
                    <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
                    <?php esc_html_e('No content findings in the scanned sample.', 'vonseo'); ?>
                </div>
            <?php else : ?>
                <div class="von-audit-table-wrap">
                    <table class="von-table von-audit-table von-audit-content-table">
                        <thead>
                            <tr>
                                <th scope="col"><?php esc_html_e('Content', 'vonseo'); ?></th>
                                <th scope="col"><?php esc_html_e('Type', 'vonseo'); ?></th>
                                <th scope="col"><?php esc_html_e('Findings', 'vonseo'); ?></th>
                                <th scope="col" class="von-audit-actions"><?php esc_html_e('Action', 'vonseo'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vonseowp_items as $vonseowp_item) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html($vonseowp_item['title'] !== '' ? $vonseowp_item['title'] : __('(no title)', 'vonseo')); ?></strong></td>
                                    <td><?php
                                        $vonseowp_type_object = get_post_type_object($vonseowp_item['post_type']);
                                        echo esc_html($vonseowp_type_object ? $vonseowp_type_object->labels->singular_name : $vonseowp_item['post_type']);
                                    ?></td>
                                    <td>
                                        <div class="von-audit-issues">
                                            <?php foreach ($vonseowp_item['issues'] as $vonseowp_issue) : ?>
                                                <?php if (isset($vonseowp_issue_labels[$vonseowp_issue])) : ?>
                                                    <span class="von-audit-issue"><?php echo esc_html($vonseowp_issue_labels[$vonseowp_issue]); ?></span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td class="von-audit-actions">
                                        <a class="button button-small" href="<?php echo esc_url(get_edit_post_link((int) $vonseowp_item['id'], '')); ?>">
                                            <?php esc_html_e('Edit', 'vonseo'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ($vonseowp_total_pages > 1) : ?>
                <div class="von-audit-pagination" aria-label="<?php esc_attr_e('Audit batches', 'vonseo'); ?>">
                    <button
                        type="submit"
                        name="vonseowp_audit_page"
                        value="<?php echo esc_attr((string) max(1, $vonseowp_current_page - 1)); ?>"
                        class="button von-audit-page-button"
                        <?php disabled($vonseowp_current_page <= 1); ?>
                    >
                        <span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
                        <?php esc_html_e('Previous 25', 'vonseo'); ?>
                    </button>

                    <span class="von-audit-page-number">
                        <?php
                        echo esc_html(
                            sprintf(
                                /* translators: 1: current batch number, 2: total number of batches */
                                __('Batch %1$d of %2$d', 'vonseo'),
                                $vonseowp_current_page,
                                $vonseowp_total_pages
                            )
                        );
                        ?>
                    </span>

                    <button
                        type="submit"
                        name="vonseowp_audit_page"
                        value="<?php echo esc_attr((string) min($vonseowp_total_pages, $vonseowp_current_page + 1)); ?>"
                        class="button von-audit-page-button"
                        <?php disabled($vonseowp_current_page >= $vonseowp_total_pages); ?>
                    >
                        <?php esc_html_e('Next 25', 'vonseo'); ?>
                        <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                    </button>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
    </form>
</div>
