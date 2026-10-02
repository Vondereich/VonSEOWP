<?php
/**
 * VonSEO WordPress Stubs (IDE only)
 *
 * This file provides lightweight definitions for common WordPress core
 * functions so IDE analyzers such as Intelephense do not report false
 * positives while working on the plugin source.
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}
if (!defined('WPINC')) {
    define('WPINC', 'wp-includes');
}

if (!class_exists('WP_Post')) {
    class WP_Post {
        public int $ID = 0;
        public string $post_title = '';
        public string $post_content = '';
        public string $post_type = '';
        public string $post_status = '';
        public int $post_author = 0;
        public string $post_password = '';
        public string $post_excerpt = '';
    }
}

/** @var WP_Post $post */
global $post;

if (!function_exists('checked')) {
    function checked($checked, $current = true, bool $echo = true): string { return ''; }
}

if (!function_exists('selected')) {
    function selected($selected, $current = true, bool $echo = true): string { return ''; }
}

if (!function_exists('disabled')) {
    function disabled($disabled, $current = true, bool $echo = true): string { return ''; }
}

if (!function_exists('absint')) {
    function absint(mixed $maybeint): int { return 0; }
}

if (!function_exists('in_the_loop')) {
    function in_the_loop(): bool { return true; }
}

if (!function_exists('post_password_required')) {
    /** @param WP_Post|int|null $post */
    function post_password_required($post = null): bool { return false; }
}

if (!function_exists('get_the_content')) {
    /** @param WP_Post|int|null $post */
    function get_the_content($more_link_text = null, bool $strip_teaser = false, $post = null): string { return ''; }
}

if (!function_exists('sanitize_title')) {
    function sanitize_title(string $title, string $fallback_title = '', string $context = 'save'): string { return $title; }
}

if (!function_exists('__')) {
    function __(string $text, string $domain = 'default'): string { return $text; }
}

if (!function_exists('_e')) {
    function _e(string $text, string $domain = 'default'): void { echo $text; }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post(string $data): string { return $data; }
}

if (!function_exists('esc_html__')) {
    function esc_html__(string $text, string $domain = 'default'): string { return $text; }
}

if (!function_exists('esc_html_e')) {
    function esc_html_e(string $text, string $domain = 'default'): void { echo $text; }
}

if (!function_exists('esc_attr__')) {
    function esc_attr__(string $text, string $domain = 'default'): string { return $text; }
}

if (!function_exists('esc_attr_e')) {
    function esc_attr_e(string $text, string $domain = 'default'): void { echo $text; }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path(string $file): string { return ''; }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url(string $file): string { return ''; }
}

if (!function_exists('plugin_basename')) {
    function plugin_basename(string $file): string { return ''; }
}

if (!function_exists('add_action')) {
    function add_action(string $hook, $callback, int $priority = 10, int $accepted_args = 1): bool { return true; }
}

if (!function_exists('add_filter')) {
    function add_filter(string $hook, $callback, int $priority = 10, int $accepted_args = 1): bool { return true; }
}

if (!function_exists('apply_filters')) {
    function apply_filters(string $hook, mixed $value, mixed ...$args) { return $value; }
}

if (!function_exists('add_shortcode')) {
    function add_shortcode(string $tag, $callback): bool { return true; }
}

if (!function_exists('get_transient')) {
    function get_transient(string $transient): mixed { return false; }
}

if (!function_exists('set_transient')) {
    function set_transient(string $transient, mixed $value, int $expiration = 0): bool { return true; }
}

if (!function_exists('delete_transient')) {
    function delete_transient(string $transient): bool { return true; }
}

if (!function_exists('wp_count_posts')) {
    function wp_count_posts(string $type = 'post'): object { return (object) array('publish' => 0); }
}

if (!function_exists('add_query_arg')) {
    function add_query_arg(...$args): string { return ''; }
}

if (!function_exists('shortcode_atts')) {
    function shortcode_atts(mixed $pairs, mixed $atts, string $shortcode = ''): array {
        return array_merge((array) $pairs, is_array($atts) ? $atts : array());
    }
}

if (!defined('MINUTE_IN_SECONDS')) define('MINUTE_IN_SECONDS', 60);
if (!defined('HOUR_IN_SECONDS'))   define('HOUR_IN_SECONDS', 3600);
if (!defined('DAY_IN_SECONDS'))    define('DAY_IN_SECONDS', 86400);
if (!defined('WEEK_IN_SECONDS'))   define('WEEK_IN_SECONDS', 604800);
if (!defined('MONTH_IN_SECONDS'))  define('MONTH_IN_SECONDS', 2592000);
if (!defined('YEAR_IN_SECONDS'))   define('YEAR_IN_SECONDS', 31536000);

if (!function_exists('register_activation_hook')) {
    function register_activation_hook(string $file, $callback): bool { return true; }
}

if (!function_exists('flush_rewrite_rules')) {
    function flush_rewrite_rules(bool $hard = true): bool { return true; }
}

if (!function_exists('load_plugin_textdomain')) {
    function load_plugin_textdomain(string $domain, $deprecated = false, $plugin_rel_path = false): bool { return true; }
}

if (!function_exists('get_option')) {
    function get_option(string $option, mixed $default = false): mixed { return $default; }
}

if (!function_exists('add_option')) {
    function add_option(string $option, $value = '', $deprecated = '', $autoload = 'yes'): bool { return true; }
}

if (!function_exists('update_option')) {
    function update_option(string $option, $value, $autoload = null): bool { return true; }
}

if (!function_exists('delete_option')) {
    function delete_option(string $option): bool { return true; }
}

if (!function_exists('delete_site_option')) {
    function delete_site_option(string $option): bool { return true; }
}

if (!function_exists('esc_html')) {
    function esc_html(string $text): string { return $text; }
}

if (!function_exists('esc_textarea')) {
    function esc_textarea(string $text): string { return $text; }
}

if (!function_exists('esc_attr')) {
    function esc_attr(string $text): string { return $text; }
}

if (!function_exists('esc_url')) {
    function esc_url(string $url): string { return $url; }
}

if (!function_exists('wp_kses')) {
    function wp_kses(string $string, array $allowed_html, array $allowed_protocols = array()): string { return $string; }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field(string $str): string { return $str; }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key(string $key): string { return $key; }
}

if (!function_exists('sanitize_html_class')) {
    function sanitize_html_class(string $class, string $fallback = ''): string { return $class ?: $fallback; }
}

if (!function_exists('wp_parse_args')) {
    function wp_parse_args(mixed $args, array $defaults = array()): array {
        return array_merge((array) $defaults, is_array($args) ? $args : array());
    }
}

if (!function_exists('home_url')) {
    function home_url(string $path = '', ?string $scheme = null): string { return $path; }
}

if (!function_exists('get_permalink')) {
    function get_permalink($post = 0, bool $leavename = false): string { return ''; }
}

if (!function_exists('get_pagenum_link')) {
    function get_pagenum_link(int $pagenum = 1, bool $escape = true): string { return ''; }
}

if (!function_exists('get_the_title')) {
    function get_the_title($post = 0): string { return ''; }
}

if (!function_exists('get_the_category')) {
    function get_the_category($post_id = false): array { return array(); }
}

if (!function_exists('get_post_type_archive_link')) {
    function get_post_type_archive_link(string $post_type): string { return ''; }
}

if (!function_exists('get_search_query')) {
    function get_search_query(bool $escaped = true): string { return ''; }
}

if (!function_exists('get_queried_object')) {
    function get_queried_object(): object { return (object) array(); }
}

if (!function_exists('get_queried_object_id')) {
    function get_queried_object_id(): int { return 0; }
}

if (!function_exists('get_post_ancestors')) {
    function get_post_ancestors($post): array { return array(); }
}

if (!function_exists('get_ancestors')) {
    function get_ancestors(int $object_id = 0, string $object_type = '', string $resource_type = ''): array { return array(); }
}

if (!function_exists('get_term')) {
    function get_term($term, string $taxonomy = ''): object {
        return (object) array(
            'term_id' => 0,
            'taxonomy' => $taxonomy,
            'name' => '',
        );
    }
}

if (!function_exists('get_term_link')) {
    function get_term_link($term, string $taxonomy = ''): string { return ''; }
}

if (!function_exists('is_front_page')) {
    function is_front_page(): bool { return false; }
}

if (!function_exists('is_home')) {
    function is_home(): bool { return false; }
}

if (!function_exists('is_singular')) {
    function is_singular($post_types = ''): bool { return false; }
}

if (!function_exists('is_page')) {
    function is_page($page = ''): bool { return false; }
}

if (!function_exists('is_single')) {
    function is_single($post = ''): bool { return false; }
}

if (!function_exists('is_category')) {
    function is_category($category = ''): bool { return false; }
}

if (!function_exists('is_tag')) {
    function is_tag($tag = ''): bool { return false; }
}

if (!function_exists('is_tax')) {
    function is_tax(string $taxonomy = '', $term = ''): bool { return false; }
}

if (!function_exists('is_post_type_archive')) {
    function is_post_type_archive($post_types = ''): bool { return false; }
}

if (!function_exists('is_search')) {
    function is_search(): bool { return false; }
}

if (!function_exists('is_author')) {
    function is_author($author = ''): bool { return false; }
}

if (!function_exists('is_year')) {
    function is_year(): bool { return false; }
}

if (!function_exists('is_month')) {
    function is_month(): bool { return false; }
}

if (!function_exists('is_day')) {
    function is_day(): bool { return false; }
}

if (!function_exists('is_404')) {
    function is_404(): bool { return false; }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing): bool { return false; }
}

if (!function_exists('check_ajax_referer')) {
    function check_ajax_referer(string|int $action = -1, string|bool $query_arg = false, bool $die = true): int|bool { return 1; }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error(mixed $data = null, ?int $status_code = null, int $options = 0): void { exit; }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success(mixed $data = null, ?int $status_code = null, int $options = 0): void { exit; }
}

if (!function_exists('wp_safe_remote_get')) {
    function wp_safe_remote_get(string $url, array $args = array()): array|WP_Error { return array(); }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body(array|WP_Error $response): string { return ''; }
}

if (!function_exists('wp_remote_retrieve_header')) {
    function wp_remote_retrieve_header(array|WP_Error $response, string $header): string { return ''; }
}

if (!function_exists('wp_enqueue_media')) {
    function wp_enqueue_media(array $args = array()): void {}
}

if (!function_exists('add_meta_box')) {
    function add_meta_box(string $id, string $title, callable $callback, string|array|null $screen = null, string $context = 'advanced', string $priority = 'default', ?array $callback_args = null): void {}
}

if (!function_exists('delete_post_meta')) {
    function delete_post_meta(int $post_id, string $meta_key, mixed $meta_value = ''): bool { return true; }
}

if (!class_exists('WP_Error')) {
    class WP_Error {
        public function __construct(string|int $code = '', string $message = '', mixed $data = '') {}
        public function get_error_message(string $code = ''): string { return ''; }
    }
}

// --------------------------------------------------------
// Attachment & Redirect Module Stubs (Added for 2.2.6)
// --------------------------------------------------------

if (!function_exists('is_attachment')) {
    function is_attachment($attachment = ''): bool { return false; }
}

if (!function_exists('get_post')) {
    function get_post(mixed $post = null, string $output = 'OBJECT', string $filter = 'raw'): object { return (object) array(); }
}

if (!function_exists('get_posts')) {
    function get_posts(array $args = array()): array { return array(); }
}

if (!function_exists('get_post_status')) {
    function get_post_status($post = '') { return false; }
}

if (!function_exists('wp_get_attachment_url')) {
    function wp_get_attachment_url(int $post_id = 0) { return false; }
}

if (!function_exists('wp_redirect')) {
    function wp_redirect(string $location, int $status = 302, string $x_redirect_by = 'WordPress'): bool { return true; }
}

if (!function_exists('wp_safe_redirect')) {
    function wp_safe_redirect(string $location, int $status = 302, string $x_redirect_by = 'WordPress'): bool { return true; }
}

if (!function_exists('admin_url')) {
    function admin_url(string $path = '', string $scheme = 'admin'): string { return $path; }
}

if (!function_exists('wp_unslash')) {
    function wp_unslash(mixed $value) { return $value; }
}

if (!function_exists('esc_url_raw')) {
    function esc_url_raw(string $url, $protocols = null): string { return $url; }
}

if (!function_exists('untrailingslashit')) {
    function untrailingslashit(string $string): string { return rtrim($string, '/\\'); }
}

if (!function_exists('trailingslashit')) {
    function trailingslashit(string $string): string { return untrailingslashit($string) . '/'; }
}

if (!function_exists('current_time')) {
    function current_time(string $type, $gmt = 0): string { return ''; }
}

if (!function_exists('is_feed')) {
    function is_feed(): bool { return false; }
}

if (!function_exists('get_bloginfo')) {
    function get_bloginfo(string $show = '', string $filter = 'raw'): string { return ''; }
}

if (!function_exists('wp_date')) {
    function wp_date(string $format, ?int $timestamp = null, ?DateTimeZone $timezone = null): string { return ''; }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce(string $nonce, string|int $action = -1): int|bool { return 1; }
}

if (!function_exists('wp_nonce_field')) {
    function wp_nonce_field(string|int $action = -1, string $name = "_wpnonce", bool $referer = true, bool $echo = true): string { return ''; }
}

if (!function_exists('current_user_can')) {
    function current_user_can(string $capability, ...$args): bool { return true; }
}

if (!function_exists('check_admin_referer')) {
    function check_admin_referer(string|int $action = -1, string $query_arg = '_wpnonce'): int|bool { return 1; }
}

if (!function_exists('has_excerpt')) {
    function has_excerpt(int|object $post = 0): bool { return false; }
}

if (!function_exists('get_the_excerpt')) {
    function get_the_excerpt(int|object|null $post = null): string { return ''; }
}

if (!function_exists('strip_shortcodes')) {
    function strip_shortcodes(string $content): string { return $content; }
}

if (!function_exists('has_post_thumbnail')) {
    function has_post_thumbnail(int|object|null $post = null): bool { return false; }
}

if (!function_exists('get_the_post_thumbnail_url')) {
    function get_the_post_thumbnail_url(int|object|null $post = null, string|array $size = 'post-thumbnail'): string|bool { return ''; }
}

if (!function_exists('wp_get_document_title')) {
    function wp_get_document_title(): string { return ''; }
}

if (!function_exists('get_locale')) {
    function get_locale(): string { return 'en_US'; }
}

if (!function_exists('get_the_date')) {
    function get_the_date(string $format = '', int|object|null $post = null): string|int|bool { return ''; }
}

if (!function_exists('get_the_modified_date')) {
    function get_the_modified_date(string $format = '', int|object|null $post = null): string|int|bool { return ''; }
}

if (!function_exists('has_site_icon')) {
    function has_site_icon(int $blog_id = 0): bool { return false; }
}

if (!function_exists('get_site_icon_url')) {
    function get_site_icon_url(int $size = 512, string $url = '', int $blog_id = 0): string { return ''; }
}

if (!function_exists('wp_trim_words')) {
    function wp_trim_words(string $text, int $num_words = 55, ?string $more = null): string { return $text; }
}

if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags(string $string, bool $remove_breaks = false): string { return $string; }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode(mixed $data, int $options = 0, int $depth = 512): string|bool { return ''; }
}

if (!function_exists('wp_parse_url')) {
    function wp_parse_url(string $url, int $component = -1): mixed { return array(); }
}

if (!function_exists('get_the_author')) {
    function get_the_author(): string { return ''; }
}

if (!function_exists('get_the_author_meta')) {
    function get_the_author_meta(string $field = '', int|bool $user_id = false): string { return ''; }
}

if (!function_exists('get_author_posts_url')) {
    function get_author_posts_url(int $author_id, string $author_nicename = ''): string { return ''; }
}

if (!function_exists('get_category_link')) {
    function get_category_link(int|object $category): string { return ''; }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field(string $str): string { return $str; }
}

if (!function_exists('get_post_meta')) {
    function get_post_meta(int $post_id, string $key = '', bool $single = false): mixed { return ''; }
}

if (!function_exists('get_edit_post_link')) {
    function get_edit_post_link(int $post_id = 0, string $context = 'display'): string { return ''; }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta(int $post_id, string $meta_key, mixed $meta_value, mixed $prev_value = ''): int|bool { return true; }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script(string $handle, string $src = '', array $deps = array(), string|bool|null $ver = false, bool $in_footer = false): void {}
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style(string $handle, string $src = '', array $deps = array(), string|bool|null $ver = false, string $media = 'all'): void {}
}

if (!function_exists('remove_action')) {
    function remove_action(string $hook, $callback, int $priority = 10): bool { return true; }
}

if (!function_exists('add_menu_page')) {
    function add_menu_page(string $page_title, string $menu_title, string $capability, string $menu_slug, $callback = '', string $icon_url = '', ?int $position = null): string { return ''; }
}

if (!function_exists('add_submenu_page')) {
    function add_submenu_page(string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, $callback = '', ?int $position = null): string { return ''; }
}

if (!function_exists('wp_die')) {
    function wp_die(string $message = '', string $title = '', array $args = array()): void {}
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script(string $handle, string $object_name, array $l10n): bool { return true; }
}

if (!function_exists('register_setting')) {
    function register_setting(string $option_group, string $option_name, array $args = array()): void {}
}

if (!function_exists('settings_fields')) {
    function settings_fields(string $option_group): void {}
}

if (!function_exists('do_settings_sections')) {
    function do_settings_sections(string $page): void {}
}

if (!function_exists('submit_button')) {
    function submit_button(string $text = '', string $type = 'primary', string $name = 'submit', bool $wrap = true, array $other_attributes = array()): void {}
}

if (!function_exists('wp_generate_password')) {
    function wp_generate_password(int $length = 12, bool $special_chars = true, bool $extra_special_chars = false): string { return ''; }
}

if (!function_exists('wp_is_post_revision')) {
    function wp_is_post_revision(int|object|null $post): int|bool { return false; }
}

if (!function_exists('wp_is_post_autosave')) {
    function wp_is_post_autosave(int|object|null $post): int|bool { return false; }
}

if (!function_exists('wp_remote_post')) {
    function wp_remote_post(string $url, array $args = array()): array|WP_Error { return array(); }
}

if (!function_exists('add_rewrite_rule')) {
    function add_rewrite_rule(string $regex, string|array $redirect, string $after = 'bottom'): void {}
}

if (!function_exists('get_query_var')) {
    function get_query_var(string $var, mixed $default = ''): mixed { return $default; }
}

if (!function_exists('status_header')) {
    function status_header(int $code, string $description = ''): void {}
}

if (!function_exists('nocache_headers')) {
    function nocache_headers(): void {}
}

if (!function_exists('get_query_template')) {
    function get_query_template(string $type, array $templates = array()): string { return ''; }
}

if (!function_exists('get_the_ID')) {
    function get_the_ID(): int|bool { return 0; }
}

if (!function_exists('wp_reset_postdata')) {
    function wp_reset_postdata(): void {}
}

if (!class_exists('WP_Query')) {
    class WP_Query {
        public array $posts = array();
        public int $post_count = 0;
        public bool $is_single = false;
        public bool $is_singular = false;
        public bool $is_page = false;
        public bool $is_embed = false;
        public bool $in_the_loop = false;
        public ?WP_Post $post = null;
        public ?object $queried_object = null;
        public int $queried_object_id = 0;
        public function __construct(mixed $query = '') {}
        public function have_posts(): bool { return false; }
        public function the_post(): void {}
    }
}

if (!function_exists('wp_clear_scheduled_hook')) {
    function wp_clear_scheduled_hook(string $hook, array $args = array(), bool $wp_error = false): int|bool { return true; }
}
