<?php
/** Admin-only, previewed site-settings portability. */
if (!defined('ABSPATH')) exit;

class VonSEOWP_Settings_Transfer {
    public const MAX_BYTES = 131072;
    private const EXPIRY = 600;
    private $admin;

    public function __construct(VonSEOWP_Admin $admin) {
        $this->admin = $admin;
        add_action('admin_post_vonseowp_export_settings', array($this, 'export_request'));
        add_action('admin_post_vonseowp_preview_settings', array($this, 'preview_request'));
        add_action('admin_post_vonseowp_apply_settings', array($this, 'apply_request'));
        add_action('vonseowp_settings_transfer', array($this, 'render'));
    }

    /** Explicit allowlist; identity tokens and IndexNow consent/key never travel. */
    public static function fields(): array {
        return array(
            'home_title' => 'text', 'home_desc' => 'textarea', 'separator' => 'separator',
            'seo_post_types' => 'post_types', 'seo_taxonomies' => 'taxonomies',
            'facebook_url' => 'url', 'twitter_username' => 'text', 'default_image' => 'url',
            'enable_og' => 'flag', 'breadcrumb_separator' => 'short_text',
            'enable_sitemap' => 'flag', 'sitemap_posts' => 'flag', 'sitemap_pages' => 'flag',
            'enable_404_log' => 'flag', 'redirects_list' => 'textarea',
            'business_name' => 'text', 'address' => 'text', 'city' => 'text', 'state' => 'text',
            'zip' => 'text', 'country' => 'text', 'phone' => 'text',
            'auto_image_alt' => 'flag', 'auto_image_title' => 'flag',
            'redirect_attachments' => 'flag', 'enable_rss_footer' => 'flag',
            'robots_txt' => 'textarea', 'enable_toc' => 'flag', 'toc_title' => 'text',
            'toc_position' => 'position', 'toc_min_headings' => 'headings',
            'enable_llms_txt' => 'flag', 'llms_txt_content' => 'textarea',
        );
    }

    private function settings(): array {
        $settings = get_option('vonseowp_settings', array());
        return is_array($settings) ? $settings : array();
    }

    private function state_hash(): string {
        return hash('sha256', serialize(array(get_option('vonseowp_settings'), get_option('blogname'), get_option('blogdescription'))));
    }

    private function transient_key(): string {
        return 'vonseowp_settings_import_' . get_current_user_id();
    }

    /** @return array|WP_Error */
    public function export_document() {
        if (!current_user_can('manage_options')) return new WP_Error('permission', __('Administrator access is required.', 'vonseo'));
        $document = array('format' => 'vonseo-settings', 'version' => 1, 'settings' => (object) $this->export_values());
        $json = wp_json_encode($document, JSON_PRETTY_PRINT);
        if (!is_string($json)) return new WP_Error('json', __('The settings could not be encoded.', 'vonseo'));
        $valid = $this->decode($json);
        return is_wp_error($valid) ? $valid : $document;
    }

    private function export_values(): array {
        $settings = $this->settings();
        $defaults = array('home_title' => get_option('blogname'), 'home_desc' => get_option('blogdescription'),
            'seo_post_types' => array('post', 'page'), 'seo_taxonomies' => array('category', 'post_tag'),
            'enable_og' => 1, 'enable_sitemap' => 1, 'sitemap_posts' => 1, 'sitemap_pages' => 1,
            'enable_llms_txt' => 1, 'separator' => '-', 'breadcrumb_separator' => '/',
            'toc_position' => 'before_first_heading', 'toc_min_headings' => 3);
        $export = array();
        foreach (self::fields() as $key => $type) {
            $export[$key] = $settings[$key] ?? ($defaults[$key] ?? ($type === 'flag' ? 0 : ''));
            if ($type === 'flag') $export[$key] = in_array($export[$key], array(1, '1', true), true) ? 1 : 0;
            if ($type === 'headings') $export[$key] = (int) $export[$key];
        }
        return $export;
    }

    /** @return array|WP_Error */
    private function decode(string $json) {
        if (strlen($json) > self::MAX_BYTES) return new WP_Error('size', __('The JSON file exceeds 128 KB.', 'vonseo'));
        $document = json_decode($json, false, 16);
        if (json_last_error() !== JSON_ERROR_NONE || !($document instanceof stdClass)
            || count(get_object_vars($document)) !== 3 || ($document->format ?? null) !== 'vonseo-settings'
            || ($document->version ?? null) !== 1 || !(($document->settings ?? null) instanceof stdClass)) {
            return new WP_Error('format', __('Use a valid VonSEO settings JSON file with format version 1.', 'vonseo'));
        }
        $incoming = get_object_vars($document->settings);
        if (!$incoming) return new WP_Error('empty', __('The file contains no settings.', 'vonseo'));
        $fields = self::fields();
        $previous = $this->settings();
        foreach ($incoming as $key => $value) {
            if (!isset($fields[$key])) return new WP_Error('field', __('The file contains an unsupported or excluded setting.', 'vonseo'));
            $type = $fields[$key];
            $valid = false;
            if ($type === 'flag') $valid = is_int($value) && in_array($value, array(0, 1), true);
            elseif ($type === 'headings') $valid = is_int($value) && $value >= 1 && $value <= 10;
            elseif ($type === 'post_types' || $type === 'taxonomies') {
                $allowed = $type === 'post_types'
                    ? array_merge(array_keys(VonSEOWP_Content_Types::get_available()), VonSEOWP_Content_Types::get_saved($previous))
                    : array_merge(array_keys(VonSEOWP_Taxonomies::get_available()), VonSEOWP_Taxonomies::get_saved($previous));
                $valid = is_array($value) && count($value) <= 100 && array_keys($value) === array_keys(array_values($value));
                if ($valid) foreach ($value as $slug) {
                    if (!is_string($slug) || !in_array($slug, $allowed, true)) $valid = false;
                }
                if (!$valid) return new WP_Error('selection', __('Enable the required public content types or taxonomies on this site before importing their selection.', 'vonseo'));
            } else {
                $valid = is_string($value) && strlen($value) <= ($type === 'textarea' ? 65536 : 2048) && strpos($value, "\0") === false;
                if ($valid && $type === 'url' && $value !== '') {
                    $parts = wp_parse_url($value);
                    $valid = is_array($parts) && isset($parts['host'], $parts['scheme'])
                        && in_array(strtolower($parts['scheme']), array('http', 'https'), true)
                        && !isset($parts['user']) && !isset($parts['pass']) && esc_url_raw($value, array('http', 'https')) !== '';
                }
                if ($type === 'separator') $valid = $valid && in_array($value, array('-', '|', "\xE2\x80\xA2", "\xE2\x80\x94"), true);
                if ($type === 'short_text') $valid = $valid && strlen($value) >= 1 && strlen($value) <= 5;
                if ($type === 'position') $valid = $valid && in_array($value, array('before_first_heading', 'top'), true);
            }
            if (!$valid) return new WP_Error('value', __('A setting has an invalid type or value. No settings were changed.', 'vonseo'));
        }
        return $incoming;
    }

    /** @return array|WP_Error */
    public function stage(string $json) {
        if (!current_user_can('manage_options')) return new WP_Error('permission', __('Administrator access is required.', 'vonseo'));
        $incoming = $this->decode($json);
        if (is_wp_error($incoming)) return $incoming;
        // The existing form sanitizer expects slashed input. Only imported keys may change.
        $clean = $this->admin->sanitize_settings(wp_slash(array_replace($this->settings(), $incoming)));
        $incoming = array_intersect_key($clean, $incoming);
        $pending = array('token' => wp_generate_password(32, false, false), 'settings' => $incoming,
            'base' => $this->state_hash(), 'expires' => time() + self::EXPIRY);
        if (!set_transient($this->transient_key(), $pending, self::EXPIRY)) return new WP_Error('storage', __('The import preview could not be saved. Try again.', 'vonseo'));
        return $pending;
    }

    public function pending(string $token): array {
        if (!current_user_can('manage_options') || !preg_match('/^[a-zA-Z0-9]{32}$/D', $token)) return array();
        $pending = get_transient($this->transient_key());
        if (!is_array($pending) || ($pending['expires'] ?? 0) < time() || !hash_equals($pending['token'] ?? '', $token)) return array();
        return $pending;
    }

    /** @param mixed $value */
    private function persist($value): void {
        $callback = array($this->admin, 'sanitize_settings');
        $priority = has_filter('sanitize_option_vonseowp_settings', $callback);
        // Values were sanitized for the preview. Re-running this callback would unslash them again and drop excluded keys.
        if ($priority !== false) remove_filter('sanitize_option_vonseowp_settings', $callback, $priority);
        try { update_option('vonseowp_settings', $value); }
        finally { if ($priority !== false) add_filter('sanitize_option_vonseowp_settings', $callback, $priority); }
    }

    /** @return true|WP_Error */
    public function apply(string $token) {
        $pending = $this->pending($token);
        if (!$pending) return new WP_Error('expired', __('The preview expired or belongs to another user. Upload the file again.', 'vonseo'));
        delete_transient($this->transient_key());
        if (!hash_equals($pending['base'], $this->state_hash())) return new WP_Error('stale', __('Settings changed after this preview. Upload the file again to review the latest values.', 'vonseo'));
        $valid = $this->decode((string) wp_json_encode(array('format' => 'vonseo-settings', 'version' => 1, 'settings' => (object) $pending['settings'])));
        if (is_wp_error($valid)) return $valid;
        $before = get_option('vonseowp_settings', null);
        $title = get_option('blogname');
        $description = get_option('blogdescription');
        $target = array_replace($this->settings(), $valid);
        $this->persist($target);
        // WordPress fires add_option, not update_option, for a site's first settings save.
        if ($before === null && get_option('vonseowp_settings') === $target) {
            $this->admin->sync_options_to_wp(null, $target, 'vonseowp_settings');
        }
        $title_ok = empty($valid['home_title']) || get_option('blogname') === sanitize_option('blogname', $valid['home_title']);
        $description_ok = empty($valid['home_desc']) || get_option('blogdescription') === sanitize_option('blogdescription', $valid['home_desc']);
        if (get_option('vonseowp_settings') === $target && $title_ok && $description_ok) return true;
        $this->persist($before);
        if ($before === null) delete_option('vonseowp_settings');
        remove_action('update_option_blogname', array($this->admin, 'sync_title_from_wp'), 10);
        remove_action('update_option_blogdescription', array($this->admin, 'sync_tagline_from_wp'), 10);
        try {
            update_option('blogname', $title);
            update_option('blogdescription', $description);
        } finally {
            add_action('update_option_blogname', array($this->admin, 'sync_title_from_wp'), 10, 2);
            add_action('update_option_blogdescription', array($this->admin, 'sync_tagline_from_wp'), 10, 2);
        }
        if (get_option('vonseowp_settings', null) !== $before || get_option('blogname') !== $title || get_option('blogdescription') !== $description) {
            return new WP_Error('restore', __('The import failed and restoration could not be verified. Check your database and restore your saved export.', 'vonseo'));
        }
        return new WP_Error('save', __('The import could not be saved. Previous settings were restored.', 'vonseo'));
    }

    private function authorize(): void {
        $method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : '';
        if ($method !== 'POST' || !current_user_can('manage_options')) wp_die(esc_html__('Administrator POST access is required.', 'vonseo'), '', array('response' => 403));
    }

    private function destination(array $args = array()): string {
        return add_query_arg(array_merge(array('page' => 'vonseo', '_wpnonce' => wp_create_nonce('vonseowp_transfer_view')), $args), admin_url('admin.php')) . '#tab-transfer';
    }

    /** @param WP_Error $error */
    private function fail($error): void {
        wp_die(esc_html($error->get_error_message()), esc_html__('Settings transfer', 'vonseo'), array('response' => 400, 'back_link' => true));
    }

    public function export_request(): void {
        $this->authorize();
        check_admin_referer('vonseowp_export_settings');
        $document = $this->export_document();
        if (is_wp_error($document)) { $this->fail($document); return; }
        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="vonseo-settings.json"');
        header('X-Content-Type-Options: nosniff');
        // JSON download, not HTML output; no imported data is executed.
        echo wp_json_encode($document, JSON_PRETTY_PRINT); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON response.
        exit;
    }

    public function preview_request(): void {
        $this->authorize();
        check_admin_referer('vonseowp_preview_settings');
        if (!isset($_FILES['vonseowp_settings_file']) || !is_array($_FILES['vonseowp_settings_file'])) { $this->fail(new WP_Error('file', __('Choose a JSON file.', 'vonseo'))); return; }
        $file = $_FILES['vonseowp_settings_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- File metadata is validated below; file contents are parsed, not stored.
        if (($file['error'] ?? null) !== UPLOAD_ERR_OK || !is_string($file['name'] ?? null)
            || strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'json'
            || !is_string($file['tmp_name'] ?? null) || !is_uploaded_file($file['tmp_name'])
            || !is_int($file['size'] ?? null) || $file['size'] > self::MAX_BYTES) {
            $this->fail(new WP_Error('file', __('Choose an uploaded .json file no larger than 128 KB.', 'vonseo'))); return;
        }
        $json = file_get_contents($file['tmp_name'], false, null, 0, self::MAX_BYTES + 1);
        if (!is_string($json)) { $this->fail(new WP_Error('file', __('The uploaded file could not be read.', 'vonseo'))); return; }
        $pending = $this->stage($json);
        if (is_wp_error($pending)) { $this->fail($pending); return; }
        wp_safe_redirect($this->destination(array('vonseowp_preview' => $pending['token'])));
        exit;
    }

    public function apply_request(): void {
        $this->authorize();
        check_admin_referer('vonseowp_apply_settings');
        $token = isset($_POST['vonseowp_preview']) && is_string($_POST['vonseowp_preview']) ? sanitize_text_field(wp_unslash($_POST['vonseowp_preview'])) : '';
        $cancel = isset($_POST['vonseowp_cancel']) && is_string($_POST['vonseowp_cancel']) ? sanitize_text_field(wp_unslash($_POST['vonseowp_cancel'])) : '';
        $confirm = isset($_POST['vonseowp_confirm']) && is_string($_POST['vonseowp_confirm']) ? sanitize_text_field(wp_unslash($_POST['vonseowp_confirm'])) : '';
        if ($cancel === '1') {
            if ($this->pending($token)) delete_transient($this->transient_key());
            wp_safe_redirect($this->destination());
            exit;
        }
        if ($confirm !== '1') { $this->fail(new WP_Error('confirm', __('Confirm the reviewed changes before importing.', 'vonseo'))); return; }
        $result = $this->apply($token);
        if (is_wp_error($result)) { $this->fail($result); return; }
        wp_safe_redirect($this->destination(array('vonseowp_imported' => '1')));
        exit;
    }

    public function render(): void {
        if (!current_user_can('manage_options')) return;
        $vonseowp_token = '';
        $vonseowp_imported = false;
        $nonce = isset($_GET['_wpnonce']) && is_string($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
        if (wp_verify_nonce($nonce, 'vonseowp_transfer_view')) {
            $vonseowp_token = isset($_GET['vonseowp_preview']) && is_string($_GET['vonseowp_preview']) ? sanitize_text_field(wp_unslash($_GET['vonseowp_preview'])) : '';
            $vonseowp_imported = isset($_GET['vonseowp_imported']) && is_string($_GET['vonseowp_imported']) && sanitize_text_field(wp_unslash($_GET['vonseowp_imported'])) === '1';
        }
        $vonseowp_pending = $this->pending($vonseowp_token);
        $vonseowp_current = $this->export_values();
        require VONSEOWP_PATH . 'admin/partials/vonseowp-settings-transfer.php';
    }
}
