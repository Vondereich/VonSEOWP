<?php
/**
 * Admin Settings Page with Tabs
 */
if (!defined('ABSPATH')) exit;

if (false) {
    require_once dirname(__DIR__) . '/_wp_stubs.php';
}

class VonSEOWP_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));

        // Bi-directional Title & Tagline Sync
        add_action('update_option_vonseowp_settings', array($this, 'sync_options_to_wp'), 10, 3);
        add_action('update_option_blogname', array($this, 'sync_title_from_wp'), 10, 2);
        add_action('update_option_blogdescription', array($this, 'sync_tagline_from_wp'), 10, 2);
    }

    /**
     * Sync Plugin Settings to WP core (Title & Tagline)
     *
     * @param mixed  $old_value Old value.
     * @param mixed  $new_value New value.
     * @param string $option    Option name.
     */
    public function sync_options_to_wp($old_value, $new_value, string $option): void {
        if (!is_array($new_value)) return;
        
        // Sync Title
        $home_title = $new_value['home_title'] ?? '';
        if (!empty($home_title) && $home_title !== get_option('blogname')) {
            remove_action('update_option_blogname', array($this, 'sync_title_from_wp'), 10);
            update_option('blogname', $home_title);
            add_action('update_option_blogname', array($this, 'sync_title_from_wp'), 10, 2);
        }

        // Sync Tagline
        $home_desc = $new_value['home_desc'] ?? '';
        if (!empty($home_desc) && $home_desc !== get_option('blogdescription')) {
            remove_action('update_option_blogdescription', array($this, 'sync_tagline_from_wp'), 10);
            update_option('blogdescription', $home_desc);
            add_action('update_option_blogdescription', array($this, 'sync_tagline_from_wp'), 10, 2);
        }
    }

    /**
     * Sync WP Site Title (blogname) to Plugin Homepage Title
     *
     * @param mixed $old_value Old value.
     * @param mixed $new_value New value.
     */
    public function sync_title_from_wp($old_value, $new_value): void {
        $options = get_option('vonseowp_settings', array());
        if (!is_array($options)) {
            $options = array();
        }
        $current_home_title = $options['home_title'] ?? '';

        if ($current_home_title !== $new_value) {
            $options['home_title'] = $new_value;
            remove_action('update_option_vonseowp_settings', array($this, 'sync_options_to_wp'), 10);
            update_option('vonseowp_settings', $options);
            add_action('update_option_vonseowp_settings', array($this, 'sync_options_to_wp'), 10, 3);
        }
    }

    /**
     * Sync WP Tagline (blogdescription) to Plugin Homepage Description
     *
     * @param mixed $old_value Old value.
     * @param mixed $new_value New value.
     */
    public function sync_tagline_from_wp($old_value, $new_value): void {
        $options = get_option('vonseowp_settings', array());
        if (!is_array($options)) {
            $options = array();
        }
        $current_home_desc = $options['home_desc'] ?? '';

        if ($current_home_desc !== $new_value) {
            $options['home_desc'] = $new_value;
            remove_action('update_option_vonseowp_settings', array($this, 'sync_options_to_wp'), 10);
            update_option('vonseowp_settings', $options);
            add_action('update_option_vonseowp_settings', array($this, 'sync_options_to_wp'), 10, 3);
        }
    }

    public function add_menu() {
        add_menu_page(
            __('VonSEO', 'vonseo'),
            __('VonSEO', 'vonseo'),
            'manage_options',
            'vonseo',
            array($this, 'render_settings_page'),
            'dashicons-chart-area',
            80
        );
    }

    public function enqueue_assets(string $hook): void {
        if ('toplevel_page_vonseo' !== $hook) return;
        wp_enqueue_style('vonseo-admin-css', VONSEOWP_URL . 'admin/css/vonseowp-admin.css', array(), VONSEOWP_VERSION);
        wp_enqueue_script('vonseo-admin-js', VONSEOWP_URL . 'admin/js/vonseowp-admin.js', array('jquery'), VONSEOWP_VERSION, true);

        wp_localize_script('vonseo-admin-js', 'vonseowp_admin_data', array(
            'site_name' => get_bloginfo('name'),
            'site_desc' => get_bloginfo('description'),
            'home_url'  => home_url(),
            'default_robots_txt' => VonSEOWP_Frontend::get_default_robots_txt(),
            'confirm_reset_robots' => __('Are you sure you want to reset robots.txt to recommended Pro rules?', 'vonseo'),
            'confirm_reset_llm'    => __('Are you sure you want to reset llms.txt to the recommended AEO template?', 'vonseo'),
            'llm_template' => array(
                'key_resources' => __('Key Resources', 'vonseo'),
                'home_page'     => __('Home Page', 'vonseo'),
                'entry_point'   => __('Main entry point.', 'vonseo'),
                'xml_sitemap'   => __('XML Sitemap', 'vonseo'),
                'full_index'    => __('Full index of pages.', 'vonseo'),
                'about'         => __('About', 'vonseo'),
                'aeo_footer'    => __('Created for AI Engine Optimization (AEO).', 'vonseo')
            )
        ));
    }

    public function register_settings() {
        register_setting('vonseowp_options_group', 'vonseowp_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings')
        ));
    }

    /**
     * Sanitize plugin settings.
     *
     * @param mixed $input Raw settings input from WordPress.
     */
    public function sanitize_settings($input): array {
        $sanitized = array();
        
        if (!is_array($input)) {
            return $sanitized;
        }

        // --- General ---
        if (isset($input['separator'])) {
            $sanitized['separator'] = sanitize_text_field(wp_unslash($input['separator']));
        }
        
        if (isset($input['home_title'])) {
            $sanitized['home_title'] = sanitize_text_field(wp_unslash($input['home_title']));
        }

        if (isset($input['home_desc'])) {
            $sanitized['home_desc'] = sanitize_textarea_field(wp_unslash($input['home_desc']));
        }

        if (isset($input['google_verify'])) {
            $sanitized['google_verify'] = sanitize_text_field(wp_unslash($input['google_verify']));
        }

        if (isset($input['bing_verify'])) {
            $sanitized['bing_verify'] = sanitize_text_field(wp_unslash($input['bing_verify']));
        }

        // --- Social ---
        if (isset($input['facebook_url'])) {
            $sanitized['facebook_url'] = esc_url_raw(wp_unslash($input['facebook_url']));
        }

        if (isset($input['twitter_username'])) {
            $sanitized['twitter_username'] = sanitize_text_field(wp_unslash($input['twitter_username']));
        }

        $sanitized['enable_og'] = isset($input['enable_og']) ? 1 : 0;

        if (isset($input['default_image'])) {
            $sanitized['default_image'] = esc_url_raw(wp_unslash($input['default_image']));
        }

        if (isset($input['breadcrumb_separator'])) {
            $separator = sanitize_text_field(wp_unslash($input['breadcrumb_separator']));
            $sanitized['breadcrumb_separator'] = '' !== $separator ? substr($separator, 0, 5) : '/';
        }
        // --- Sitemap ---
        $sanitized['enable_sitemap'] = isset($input['enable_sitemap']) ? 1 : 0;
        $sanitized['sitemap_posts'] = isset($input['sitemap_posts']) ? 1 : 0;
        $sanitized['sitemap_pages'] = isset($input['sitemap_pages']) ? 1 : 0;

        // --- Redirects ---
        $sanitized['enable_404_log'] = isset($input['enable_404_log']) ? 1 : 0;
        
        if (isset($input['redirects_list'])) {
            $sanitized['redirects_list'] = sanitize_textarea_field(wp_unslash($input['redirects_list']));
        }

        // --- Local SEO ---
        $local_fields = array('business_name', 'address', 'city', 'state', 'zip', 'country', 'phone');
        foreach ($local_fields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = sanitize_text_field(wp_unslash($input[$field]));
            }
        }

        // --- Image SEO ---
        $sanitized['auto_image_alt'] = isset($input['auto_image_alt']) ? 1 : 0;
        $sanitized['auto_image_title'] = isset($input['auto_image_title']) ? 1 : 0;

        // --- Advanced ---
        $sanitized['remove_category_base'] = isset($input['remove_category_base']) ? 1 : 0;
        $sanitized['redirect_attachments'] = isset($input['redirect_attachments']) ? 1 : 0;
        $sanitized['enable_indexnow'] = isset($input['enable_indexnow']) ? 1 : 0;
        $sanitized['enable_rss_footer'] = isset($input['enable_rss_footer']) ? 1 : 0;

        // --- Robots.txt ---
        if (isset($input['robots_txt'])) {
            $sanitized['robots_txt'] = sanitize_textarea_field(wp_unslash($input['robots_txt']));
        }

        // --- TOC ---
        $sanitized['enable_toc'] = isset($input['enable_toc']) ? 1 : 0;
        if (isset($input['toc_title'])) {
            $sanitized['toc_title'] = sanitize_text_field(wp_unslash($input['toc_title']));
        }
        if (isset($input['toc_position'])) {
            $sanitized['toc_position'] = sanitize_text_field(wp_unslash($input['toc_position']));
        }
        if (isset($input['toc_min_headings'])) {
            $sanitized['toc_min_headings'] = max(1, min(10, (int) $input['toc_min_headings']));
        }

        // --- AI & LLM ---
        $sanitized['enable_llms_txt'] = isset($input['enable_llms_txt']) ? 1 : 0;
        if (isset($input['llms_txt_content'])) {
            $sanitized['llms_txt_content'] = sanitize_textarea_field(wp_unslash($input['llms_txt_content']));
        }

        return $sanitized;
    }

    public function render_settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Add error/update messages
        // settings_errors('vonseowp_messages');

        require_once VONSEOWP_PATH . 'admin/partials/vonseowp-admin-display.php';
    }
}
