<?php
if (!defined('ABSPATH')) exit;

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 */
if (false) {
    /** @var WP_Post $post */
    require_once __DIR__ . '/../../_wp_stubs.php';
}

global $post;

// Retrieve metadata
$vonseowp_seo_title = get_post_meta($post->ID, '_vonseowp_title', true);
$vonseowp_seo_desc = get_post_meta($post->ID, '_vonseowp_description', true);
$vonseowp_seo_keywords = get_post_meta($post->ID, '_vonseowp_keywords', true);
$vonseowp_seo_image = get_post_meta($post->ID, '_vonseowp_image', true);
$vonseowp_seo_noindex = get_post_meta($post->ID, '_vonseowp_noindex', true);
$vonseowp_seo_disable_toc = get_post_meta($post->ID, '_vonseowp_disable_toc', true);
$vonseowp_seo_schema_type = get_post_meta($post->ID, '_vonseowp_schema_type', true);
$vonseowp_seo_rating = get_post_meta($post->ID, '_vonseowp_rating', true);
$vonseowp_seo_rating_count = get_post_meta($post->ID, '_vonseowp_rating_count', true);
$vonseowp_seo_reviewed_product = get_post_meta($post->ID, '_vonseowp_reviewed_product', true);
$vonseowp_seo_faq = get_post_meta($post->ID, '_vonseowp_faq', true);
$vonseowp_seo_video = get_post_meta($post->ID, '_vonseowp_video', true) ?: array();
if (!is_array($vonseowp_seo_faq)) {
    $vonseowp_seo_faq = array();
}

// Social Metas
$vonseowp_social_title = get_post_meta($post->ID, '_vonseowp_social_title', true);
$vonseowp_social_desc = get_post_meta($post->ID, '_vonseowp_social_desc', true);

// Fallbacks for preview
$vonseowp_default_title = $post->post_title;
$vonseowp_default_desc = wp_trim_words($post->post_content, 25);
$vonseowp_permalink = get_permalink($post->ID);
?>

<div class="von-metabox-wrap">
    
    <!-- Top Tabs (Icon-based for Sidebar) -->
    <div class="von-meta-tabs icon-tabs">
        <button type="button" class="von-meta-tab active" data-tab="meta-general" title="<?php esc_attr_e('General', 'vonseo'); ?>"><span class="dashicons dashicons-admin-settings"></span></button>
        <button type="button" class="von-meta-tab" data-tab="meta-social" title="<?php esc_attr_e('Social', 'vonseo'); ?>"><span class="dashicons dashicons-share"></span></button>
        <button type="button" class="von-meta-tab" data-tab="meta-analysis" title="<?php esc_attr_e('SEO Health', 'vonseo'); ?>"><span class="dashicons dashicons-chart-bar"></span></button>
        <button type="button" class="von-meta-tab" data-tab="meta-schema" title="<?php esc_attr_e('Schema', 'vonseo'); ?>"><span class="dashicons dashicons-networking"></span></button>
        <button type="button" class="von-meta-tab" data-tab="meta-competitors" title="<?php esc_attr_e('Competitors', 'vonseo'); ?>"><span class="dashicons dashicons-groups"></span></button>
    </div>

    <!-- TAB 1: GENERAL -->
    <div id="meta-general" class="von-meta-content active">
        
        <div class="von-sidebar-section">
            <div class="label-row">
                <label><?php esc_html_e('SEO Title', 'vonseo'); ?></label>
                <button type="button" class="von-btn-ai compact" id="von-ai-gen-title"><?php esc_html_e('AI', 'vonseo'); ?></button>
            </div>
            <input type="text" name="vonseowp_title" id="vonseowp_title" value="<?php echo esc_attr($vonseowp_seo_title); ?>" placeholder="<?php echo esc_attr($vonseowp_default_title); ?>" class="von-input-sm">
            <div class="von-progress-mini"><div class="bar" id="title-bar"></div></div>
            <div class="von-field-hint"><?php esc_html_e('Length:', 'vonseo'); ?> <span id="title-len">0</span> <span class="range-hint">(<?php esc_html_e('Optimal: 45-63', 'vonseo'); ?>)</span></div>
        </div>

        <div class="von-sidebar-section">
            <div class="label-row">
                <label><?php esc_html_e('Meta Description', 'vonseo'); ?></label>
                <button type="button" class="von-btn-ai compact" id="von-ai-gen-desc"><?php esc_html_e('AI', 'vonseo'); ?></button>
            </div>
            <textarea name="vonseowp_description" id="vonseowp_description" rows="3" class="von-input-sm"><?php echo esc_textarea($vonseowp_seo_desc); ?></textarea>
            <div class="von-progress-mini"><div class="bar" id="desc-bar"></div></div>
            <div class="von-field-hint"><?php esc_html_e('Length:', 'vonseo'); ?> <span id="desc-len">0</span> <span class="range-hint">(<?php esc_html_e('Optimal: 120-160', 'vonseo'); ?>)</span></div>
        </div>

        <div class="von-sidebar-section">
            <div class="label-row">
                <label><?php esc_html_e('Focus Keywords', 'vonseo'); ?></label>
                <button type="button" class="von-btn-ai compact" id="von-ai-gen-keywords"><?php esc_html_e('AI', 'vonseo'); ?></button>
            </div>
            <input type="text" name="vonseowp_keywords" id="vonseowp_keywords" value="<?php echo esc_attr($vonseowp_seo_keywords); ?>" placeholder="<?php esc_attr_e('Keywords...', 'vonseo'); ?>" class="von-input-sm">
        </div>



        <div class="von-sidebar-section check-row">
             <label class="von-checkbox-label">
                <input type="checkbox" name="vonseowp_noindex" id="vonseowp_noindex" value="1" <?php checked($vonseowp_seo_noindex, '1'); ?>>
                <?php esc_html_e('No-Index (Hide from Google)', 'vonseo'); ?>
            </label>
        </div>

        <div class="von-sidebar-section check-row">
             <label class="von-checkbox-label">
                <input type="checkbox" name="vonseowp_disable_toc" id="vonseowp_disable_toc" value="1" <?php checked($vonseowp_seo_disable_toc, '1'); ?>>
                <?php esc_html_e('Disable Table of Contents', 'vonseo'); ?>
            </label>
        </div>
    </div>

    <!-- TAB: SOCIAL -->
    <div id="meta-social" class="von-meta-content">
        <div class="von-sidebar-section">
             <div class="label-row">
                <label><?php esc_html_e('Social Title (Optional)', 'vonseo'); ?></label>
            </div>
            <input type="text" name="vonseowp_social_title" id="vonseowp_social_title" value="<?php echo esc_attr($vonseowp_social_title); ?>" placeholder="<?php esc_attr_e('Same as SEO Title...', 'vonseo'); ?>" class="von-input-sm">
        </div>

        <div class="von-sidebar-section">
             <div class="label-row">
                <label><?php esc_html_e('Social Description (Optional)', 'vonseo'); ?></label>
            </div>
            <textarea name="vonseowp_social_desc" id="vonseowp_social_desc" rows="3" class="von-input-sm" placeholder="<?php esc_attr_e('Same as SEO Description...', 'vonseo'); ?>"><?php echo esc_textarea($vonseowp_social_desc); ?></textarea>
        </div>

        <div class="von-sidebar-section von-social-section">
            <label><?php esc_html_e('Social Image (Facebook/Twitter/WhatsApp)', 'vonseo'); ?></label>
            <div class="von-image-upload-wrap">
                <input type="url" name="vonseowp_image" id="vonseowp_image" value="<?php echo esc_attr($vonseowp_seo_image); ?>" class="von-input-sm">
                <button type="button" class="von-upload-icon-btn von-upload-btn" id="von-upload-image" title="<?php esc_attr_e('Upload Image', 'vonseo'); ?>"><span class="dashicons dashicons-upload"></span></button>
            </div>
            
            <div style="margin-top: 10px;">
                <?php if ($vonseowp_seo_image) : ?>
                    <img src="<?php echo esc_url($vonseowp_seo_image); ?>" id="von-image-preview" style="max-width: 100%; height: auto; border-radius: 4px; display: block;" />
                <?php else: ?>
                    <img src="" id="von-image-preview" style="max-width: 100%; height: auto; border-radius: 4px; display: none;" />
                <?php endif; ?>
            </div>
        </div>

        <hr>

        <div class="von-social-preview-tabs">
            <button type="button" class="von-sp-tab active" data-target="sp-fb"><span class="dashicons dashicons-facebook-alt"></span> <?php esc_html_e('Facebook', 'vonseo'); ?></button>
            <button type="button" class="von-sp-tab" data-target="sp-twitter"><span class="dashicons dashicons-twitter"></span> <?php esc_html_e('Twitter', 'vonseo'); ?></button>
        </div>

        <div class="von-social-previews">
            <!-- Facebook Preview -->
            <div id="sp-fb" class="von-sp-content active">
                <div class="von-fb-card">
                    <div class="von-fb-img" id="fb-preview-img"></div>
                    <div class="von-fb-meta">
                        <div class="von-fb-domain"><?php echo esc_html(wp_parse_url(home_url(), PHP_URL_HOST)); ?></div>
                        <div class="von-fb-title" id="fb-preview-title"><?php echo esc_html($vonseowp_social_title ?: ($vonseowp_seo_title ?: $vonseowp_default_title)); ?></div>
                        <div class="von-fb-desc" id="fb-preview-desc"><?php echo esc_html($vonseowp_social_desc ?: ($vonseowp_seo_desc ?: $vonseowp_default_desc)); ?></div>
                    </div>
                </div>
            </div>

            <!-- Twitter Preview -->
            <div id="sp-twitter" class="von-sp-content">
                <div class="von-tw-card">
                    <div class="von-tw-img" id="tw-preview-img"></div>
                    <div class="von-tw-meta">
                         <div class="von-tw-title" id="tw-preview-title"><?php echo esc_html($vonseowp_social_title ?: ($vonseowp_seo_title ?: $vonseowp_default_title)); ?></div>
                         <div class="von-tw-desc" id="tw-preview-desc"><?php echo esc_html($vonseowp_social_desc ?: ($vonseowp_seo_desc ?: $vonseowp_default_desc)); ?></div>
                         <div class="von-tw-domain"><?php echo esc_html(wp_parse_url(home_url(), PHP_URL_HOST)); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: ANALYSIS (SEO Health) -->
    <div id="meta-analysis" class="von-meta-content">
        <div class="von-health-card">
            <div class="von-health-header">
                <div class="von-health-score">
                    <span id="overall-score-circle">--</span>
                    <small>/ 100</small>
                </div>
                <div class="von-health-meta">
                    <h4><?php esc_html_e('SEO Health', 'vonseo'); ?></h4>
                    <div class="von-optimization-label"><?php esc_html_e('Optimization Level:', 'vonseo'); ?> <span id="opt-percent">0%</span></div>
                </div>
            </div>
            <div class="von-health-progress-wrap">
                <div class="von-health-progress-bar" id="optimization-level-bar" style="width: 0%;"></div>
            </div>
        </div>

        <ul class="von-health-checklist" id="von-analysis-list">
            <li class="item wait"><span class="dashicons dashicons-clock"></span> <?php esc_html_e('Analyzing content...', 'vonseo'); ?></li>
        </ul>
    </div>



    <!-- TAB 3: SCHEMA -->
    <div id="meta-schema" class="von-meta-content">
        <div class="von-sidebar-section">
            <label><?php esc_html_e('Schema Type', 'vonseo'); ?></label>
            <select name="vonseowp_schema_type" id="vonseowp_schema_type" class="von-input-sm">
                <option value="" <?php selected($vonseowp_seo_schema_type, ''); ?>><?php esc_html_e('Default', 'vonseo'); ?></option>
                <option value="Article" <?php selected($vonseowp_seo_schema_type, 'Article'); ?>><?php esc_html_e('Article', 'vonseo'); ?></option>
                <option value="NewsArticle" <?php selected($vonseowp_seo_schema_type, 'NewsArticle'); ?>><?php esc_html_e('News Article', 'vonseo'); ?></option>
                <option value="BlogPosting" <?php selected($vonseowp_seo_schema_type, 'BlogPosting'); ?>><?php esc_html_e('Blog Posting', 'vonseo'); ?></option>
                <option value="Product" <?php selected($vonseowp_seo_schema_type, 'Product'); ?>><?php esc_html_e('Product', 'vonseo'); ?></option>
                <option value="Service" <?php selected($vonseowp_seo_schema_type, 'Service'); ?>><?php esc_html_e('Service', 'vonseo'); ?></option>
                <option value="Review" <?php selected($vonseowp_seo_schema_type, 'Review'); ?>><?php esc_html_e('Product Review', 'vonseo'); ?></option>
            </select>
            <div class="von-field-hint"><?php esc_html_e('Override default schema.', 'vonseo'); ?></div>
        </div>

        <div class="von-sidebar-section" id="row-review-rating">
            <div style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <label><?php esc_html_e('Rating (1-5)', 'vonseo'); ?></label>
                    <input type="number" step="0.1" min="1" max="5" name="vonseowp_rating" value="<?php echo esc_attr($vonseowp_seo_rating); ?>" class="von-input-sm">
                </div>
                <div style="flex: 1;">
                    <label><?php esc_html_e('Rating Count', 'vonseo'); ?></label>
                    <input type="number" min="1" name="vonseowp_rating_count" value="<?php echo esc_attr($vonseowp_seo_rating_count); ?>" class="von-input-sm">
                </div>
            </div>
        </div>

        <div class="von-sidebar-section" id="row-reviewed-product">
            <label for="vonseowp_reviewed_product"><?php esc_html_e('Reviewed Product Name', 'vonseo'); ?></label>
            <input type="text" id="vonseowp_reviewed_product" name="vonseowp_reviewed_product" value="<?php echo esc_attr($vonseowp_seo_reviewed_product); ?>" class="von-input-sm">
        </div>

        <hr>

        <div class="von-sidebar-section">
            <label><?php esc_html_e('FAQ Schema (JSON-LD)', 'vonseo'); ?></label>
            <div id="von-faq-repeater">
                <?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Partial included inside a class method.
                foreach ($vonseowp_seo_faq as $vonseo_index => $vonseo_faq) : ?>
                    <div class="von-faq-row" data-index="<?php echo absint($vonseo_index); ?>">
                        <input type="text" name="vonseowp_faq[<?php echo absint($vonseo_index); ?>][q]" value="<?php echo esc_attr($vonseo_faq['q']); ?>" placeholder="<?php esc_attr_e('Question...', 'vonseo'); ?>" class="von-input-sm">
                        <textarea name="vonseowp_faq[<?php echo absint($vonseo_index); ?>][a]" placeholder="<?php esc_attr_e('Answer...', 'vonseo'); ?>" class="von-input-sm"><?php echo esc_textarea($vonseo_faq['a']); ?></textarea>
                        <button type="button" class="von-remove-faq"><?php esc_html_e('Remove', 'vonseo'); ?></button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="von-btn-secondary" id="von-add-faq">+ <?php esc_html_e('Add FAQ', 'vonseo'); ?></button>
            <div class="von-field-hint"><?php esc_html_e('Add Q&A for Google Search snippets.', 'vonseo'); ?></div>
        </div>

        <hr>

        <div class="von-sidebar-section">
            <label><?php esc_html_e('Video Schema (JSON-LD)', 'vonseo'); ?></label>
            <div class="von-video-fields">
                <input type="url" name="vonseowp_video[url]" value="<?php echo esc_attr($vonseowp_seo_video['url'] ?? ''); ?>" placeholder="<?php esc_attr_e('Video URL (YouTube/Vimeo)...', 'vonseo'); ?>" class="von-input-sm">
                <input type="text" name="vonseowp_video[name]" value="<?php echo esc_attr($vonseowp_seo_video['name'] ?? ''); ?>" placeholder="<?php esc_attr_e('Video Title...', 'vonseo'); ?>" class="von-input-sm" style="margin-top:5px;">
                <textarea name="vonseowp_video[desc]" placeholder="<?php esc_attr_e('Video Description...', 'vonseo'); ?>" class="von-input-sm" style="margin-top:5px;"><?php echo esc_textarea($vonseowp_seo_video['desc'] ?? ''); ?></textarea>
            </div>
            <div class="von-field-hint"><?php esc_html_e('Helps Google index your videos better.', 'vonseo'); ?></div>
        </div>

        <hr>

        <div class="von-sidebar-section">
            <label><?php esc_html_e('Validation', 'vonseo'); ?></label>
            <button type="button" class="von-btn-tool" id="von-test-schema" style="margin-bottom: 5px;">
                <span class="dashicons dashicons-external"></span> <?php esc_html_e('Test Rich Results', 'vonseo'); ?>
            </button>
            <button type="button" class="von-btn-tool" id="von-validate-schema">
                <span class="dashicons dashicons-search"></span> <?php esc_html_e('Validate Schema.org', 'vonseo'); ?>
            </button>
            <div class="von-field-hint"><?php esc_html_e('Check for errors in Google validator.', 'vonseo'); ?></div>
        </div>
    </div>

    <!-- TAB 4: COMPETITORS -->
    <div id="meta-competitors" class="von-meta-content">
        <div class="von-sidebar-section">
            <label><?php esc_html_e('Competitor Search Math', 'vonseo'); ?></label>
            <div class="von-image-upload-wrap">
                <input type="url" id="von-competitor-url" placeholder="<?php esc_attr_e('URL...', 'vonseo'); ?>" class="von-input-sm">
                <button type="button" class="von-upload-icon-btn" id="von-scan-competitor" title="<?php esc_attr_e('Scan Competitor', 'vonseo'); ?>"><span class="dashicons dashicons-search"></span></button>
            </div>
            <div class="von-field-hint"><?php esc_html_e('Analyze top ranking sites.', 'vonseo'); ?></div>
        </div>

        <div id="von-competitor-results" style="display:none;">
            <table class="von-table competitor-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Metric', 'vonseo'); ?></th>
                        <th><?php esc_html_e('You', 'vonseo'); ?></th>
                        <th><?php esc_html_e('Competitor', 'vonseo'); ?></th>
                        <th><?php esc_html_e('Gap', 'vonseo'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr data-metric="word_count">
                        <td><?php esc_html_e('Word Count', 'vonseo'); ?></td>
                        <td class="val-user">--</td>
                        <td class="val-comp">--</td>
                        <td class="val-gap">--</td>
                    </tr>
                    <tr data-metric="h_count">
                        <td><?php esc_html_e('Headings (H1/H2/H3)', 'vonseo'); ?></td>
                        <td class="val-user">--</td>
                        <td class="val-comp">--</td>
                        <td class="val-gap">--</td>
                    </tr>
                    <tr data-metric="images">
                        <td><?php esc_html_e('Images (Alt Tags)', 'vonseo'); ?></td>
                        <td class="val-user">--</td>
                        <td class="val-comp">--</td>
                        <td class="val-gap">--</td>
                    </tr>
                    <tr data-metric="reading_ease">
                        <td><?php esc_html_e('Reading Ease', 'vonseo'); ?></td>
                        <td class="val-user">--</td>
                        <td class="val-comp">--</td>
                        <td class="val-gap">--</td>
                    </tr>
                    <tr data-metric="seo_score">
                        <td><?php esc_html_e('SEO Math Score', 'vonseo'); ?></td>
                        <td class="val-user">--</td>
                        <td class="val-comp">--</td>
                        <td class="val-gap">--</td>
                    </tr>
                </tbody>
            </table>
            
            <div class="von-ai-recommendation" id="von-comp-advice">
                <!-- AI recommendation will appear here -->
            </div>
        </div>
    </div>
    </div>
    
    <!-- Hidden fields for JS to read post content -->
    <input type="hidden" id="von-post-content-ref" value="<?php echo esc_attr($post->post_content); ?>">
</div>
