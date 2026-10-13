<?php
/**
 * Per-Post/Page Meta Box for custom SEO
 */
if (!defined('ABSPATH')) exit;

if (false) {
    require_once dirname(__DIR__) . '/_wp_stubs.php';
}




class VonSEOWP_Meta_Box {

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function enqueue_assets(string $hook): void {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        $screen = get_current_screen();
        if (!$screen || !VonSEOWP_Content_Types::is_enabled((string) $screen->post_type)) return;
        wp_enqueue_media();
        wp_enqueue_style('vonseowp-metabox-css', VONSEOWP_URL . 'admin/css/vonseowp-admin.css', array(), VONSEOWP_VERSION);
        wp_enqueue_style('vonseowp-sidebar-css', VONSEOWP_URL . 'admin/css/vonseowp-sidebar.css', array(), VONSEOWP_VERSION);
        wp_enqueue_style('vonseowp-social-previews-css', VONSEOWP_URL . 'admin/css/vonseowp-social-previews.css', array(), VONSEOWP_VERSION);
        wp_enqueue_script('vonseowp-analyzer-js', VONSEOWP_URL . 'admin/js/vonseowp-analyzer.js', array(), VONSEOWP_VERSION, true);
        wp_enqueue_script('vonseowp-metabox-js', VONSEOWP_URL . 'admin/js/vonseowp-metabox.js', array('jquery', 'vonseowp-analyzer-js'), VONSEOWP_VERSION, true);

        wp_localize_script('vonseowp-metabox-js', 'vonseowp_metabox_data', array(
            'site_url' => home_url('/'),
            'analysis' => array(
                'waiting_keyword' => __('Add a focus keyword to start local SEO checks.', 'vonseo'),
                'analyzing'       => __('Analyzing content...', 'vonseo'),
                'optimal'         => __('optimal', 'vonseo'),
                'acceptable'      => __('acceptable', 'vonseo'),
                'too_short'       => __('too short', 'vonseo'),
                'too_long'        => __('too long', 'vonseo'),
                'kw_found_title'  => __('Focus keyword found in SEO Title.', 'vonseo'),
                'kw_missing_title' => __('Focus keyword missing from SEO Title.', 'vonseo'),
                'kw_found_desc'   => __('Focus keyword found in Meta Description.', 'vonseo'),
                'kw_missing_desc'  => __('Focus keyword missing from Meta Description.', 'vonseo'),
                'kw_found_intro'   => __('Focus keyword appears in the opening paragraph.', 'vonseo'),
                'kw_missing_intro' => __('Add the focus keyword near the start of the content.', 'vonseo'),
                /* translators: %s: keyword density percentage */
                'density_good'     => __('Keyword density looks natural (%s%%).', 'vonseo'),
                /* translators: %s: keyword density percentage */
                'density_warn'     => __('Keyword density may need adjustment (%s%%).', 'vonseo'),
                'density_bad'      => __('Focus keyword is missing or overused in the body content.', 'vonseo'),
                /* translators: %d: word count */
                'content_good'    => __('Content length is good (%d words).', 'vonseo'),
                /* translators: %d: word count */
                'content_short'   => __('Content is a bit short (%d words). Aim for 300+.', 'vonseo'),
                'title_optimal'    => __('SEO title length is optimal.', 'vonseo'),
                'title_near_short' => __('SEO title is usable but a little short.', 'vonseo'),
                'title_near_long'  => __('SEO title is usable but may be truncated.', 'vonseo'),
                'title_missing'    => __('Add an SEO title for this post.', 'vonseo'),
                'title_too_short'  => __('SEO title is too short to be useful.', 'vonseo'),
                'title_too_long'   => __('SEO title is too long and likely to be truncated.', 'vonseo'),
                'desc_optimal'     => __('Meta description length is optimal.', 'vonseo'),
                'desc_near_short'  => __('Meta description is usable but a little short.', 'vonseo'),
                'desc_near_long'   => __('Meta description is usable but may be truncated.', 'vonseo'),
                'desc_missing'     => __('Add a meta description for search snippets.', 'vonseo'),
                'desc_too_short'   => __('Meta description is too short to be useful.', 'vonseo'),
                'desc_too_long'    => __('Meta description is too long and likely to be truncated.', 'vonseo'),
                'headings_good'   => __('Heading structure supports the focus keyword.', 'vonseo'),
                'headings_warn'   => __('Add clearer H2/H3 structure or include the focus keyword in a heading.', 'vonseo'),
                'headings_bad'    => __('Add headings to structure this content.', 'vonseo'),
                /* translators: %d: image count */
                'images_good'     => __('All detected images include ALT text (%d images).', 'vonseo'),
                'images_none'     => __('No images detected in the editor content.', 'vonseo'),
                /* translators: %d: missing image ALT count */
                'images_bad'      => __('Add ALT text to %d image(s).', 'vonseo'),
                'links_good'      => __('Content includes both internal and external links.', 'vonseo'),
                'links_warn'      => __('Add at least one useful internal or external link.', 'vonseo'),
                'faq_q'           => __('Question...', 'vonseo'),
                'faq_a'           => __('Answer...', 'vonseo'),
                'remove'          => __('Remove', 'vonseo'),
            ),
            'ai' => array(
                'thinking' => __('Thinking...', 'vonseo'),
                'scanning' => __('Scanning...', 'vonseo'),
                'no_url'   => __('Please enter a competitor URL.', 'vonseo'),
                'conn_err' => __('Could not connect to the server.', 'vonseo'),
                'advice_prefix' => __('AI Recommendation:', 'vonseo'),
                /* translators: %d: word count difference */
                'advice_short'  => __('Your content is shorter than your competitor\'s. Add about %d more words to stay competitive.', 'vonseo'),
                'advice_depth'  => __('Great! You have more content depth than your competitor.', 'vonseo'),
                'advice_reading' => __('Try to simplify your sentences; the competitor\'s content is easier to read.', 'vonseo'),
            )
        ));
    }

    public function add_meta_box() {
        $screens = VonSEOWP_Content_Types::get_enabled();
        foreach ($screens as $screen) {
            add_meta_box(
                'vonseowp_meta_box',
                __('VonSEO - Content Intelligence', 'vonseo'),
                array($this, 'render_meta_box'),
                $screen,
                'side',
                'high'
            );
        }
    }

    public function render_meta_box(object $post): void {
        wp_nonce_field('vonseowp_meta_box', 'vonseowp_meta_box_nonce');
        require VONSEOWP_PATH . 'admin/partials/vonseowp-meta-box-display.php';
    }

    public function save_meta_box(int $post_id): void {
        if (!isset($_POST['vonseowp_meta_box_nonce'])) return;
        if (!wp_verify_nonce(sanitize_key(wp_unslash($_POST['vonseowp_meta_box_nonce'])), 'vonseowp_meta_box')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        if (wp_is_post_revision($post_id) || !VonSEOWP_Content_Types::is_enabled((string) get_post_type($post_id))) return;

        $fields = array('title', 'description', 'keywords', 'image', 'schema_type', 'rating', 'rating_count', 'reviewed_product', 'social_title', 'social_desc');
        foreach ($fields as $field) {
            if (isset($_POST['vonseowp_' . $field])) {
                if ($field === 'description') {
                    update_post_meta($post_id, '_vonseowp_' . $field, sanitize_textarea_field(wp_unslash($_POST['vonseowp_' . $field])));
                } else {
                    update_post_meta($post_id, '_vonseowp_' . $field, sanitize_text_field(wp_unslash($_POST['vonseowp_' . $field])));
                }
            }
        }

        $noindex = isset($_POST['vonseowp_noindex']) ? '1' : '0';
        update_post_meta($post_id, '_vonseowp_noindex', $noindex);

        $disable_toc = isset($_POST['vonseowp_disable_toc']) ? '1' : '0';
        update_post_meta($post_id, '_vonseowp_disable_toc', $disable_toc);

        // Save FAQ
        if (isset($_POST['vonseowp_faq']) && is_array($_POST['vonseowp_faq'])) {
            $vonseo_faqs_raw = wp_unslash($_POST['vonseowp_faq']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $faqs = array();
            foreach ($vonseo_faqs_raw as $faq) {
                if (is_array($faq) && !empty($faq['q']) && !empty($faq['a'])) {
                    $faqs[] = array(
                        'q' => sanitize_text_field($faq['q']),
                        'a' => sanitize_textarea_field($faq['a'])
                    );
                }
            }
            update_post_meta($post_id, '_vonseowp_faq', $faqs);
        } else {
            delete_post_meta($post_id, '_vonseowp_faq');
        }

        // Save Video Schema
        if (isset($_POST['vonseowp_video']) && is_array($_POST['vonseowp_video'])) {
            $vonseo_video_raw = wp_unslash($_POST['vonseowp_video']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $video = array(
                'url'  => esc_url_raw($vonseo_video_raw['url'] ?? ''),
                'name' => sanitize_text_field($vonseo_video_raw['name'] ?? ''),
                'desc' => sanitize_textarea_field($vonseo_video_raw['desc'] ?? '')
            );
            if (!empty($video['url'])) {
                update_post_meta($post_id, '_vonseowp_video', $video);
            } else {
                delete_post_meta($post_id, '_vonseowp_video');
            }
        }
    }
}
