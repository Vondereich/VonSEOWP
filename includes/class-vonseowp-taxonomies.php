<?php
/**
 * Opt-in taxonomy archive metadata and native term forms.
 */
if (!defined('ABSPATH')) exit;

class VonSEOWP_Taxonomies {
    public function __construct() {
        add_action('init', array($this, 'register_forms'), 100);
        add_action('created_term', array($this, 'save_meta'), 10, 3);
        add_action('edited_term', array($this, 'save_meta'), 10, 3);
    }

    /** @return array<string, WP_Taxonomy> */
    public static function get_available(): array {
        $available = array();
        foreach (get_taxonomies(array(), 'objects') as $name => $taxonomy) {
            if (!empty($taxonomy->public) && !empty($taxonomy->publicly_queryable)
                && (in_array($name, array('category', 'post_tag'), true) || empty($taxonomy->_builtin))) {
                $available[$name] = $taxonomy;
            }
        }
        return $available;
    }

    public static function get_saved(array $options): array {
        if (!isset($options['seo_taxonomies']) || !is_array($options['seo_taxonomies'])) {
            return array('category', 'post_tag');
        }
        return array_values(array_unique(array_filter($options['seo_taxonomies'], static function ($name): bool {
            return is_string($name) && $name !== '' && sanitize_key($name) === $name;
        })));
    }

    public static function get_enabled(): array {
        $options = get_option('vonseowp_settings', array());
        return array_values(array_intersect(self::get_saved(is_array($options) ? $options : array()), array_keys(self::get_available())));
    }

    /** @param mixed $input */
    public static function sanitize_selection($input, array $previous): array {
        if (!is_array($input)) return self::get_saved($previous);
        $allowed = array_merge(array_keys(self::get_available()), self::get_saved($previous));
        return array_values(array_unique(array_filter($input, static function ($name) use ($allowed): bool {
            return is_string($name) && in_array($name, $allowed, true);
        })));
    }

    public static function get_current(): ?WP_Term {
        if (is_404() || is_feed() || !(is_category() || is_tag() || is_tax())) return null;
        $term = get_queried_object();
        return $term instanceof WP_Term && in_array($term->taxonomy, self::get_enabled(), true) ? $term : null;
    }

    public static function get_metadata(WP_Term $term): array {
        $metadata = array();
        foreach (array('title', 'description', 'canonical', 'social_desc', 'noindex') as $field) {
            $value = get_term_meta($term->term_id, '_vonseowp_' . $field, true);
            $metadata[$field] = is_string($value) ? $value : '';
        }
        return $metadata;
    }

    public function register_forms(): void {
        foreach (self::get_enabled() as $taxonomy) {
            add_action($taxonomy . '_add_form_fields', array($this, 'render_add_fields'));
            add_action($taxonomy . '_edit_form_fields', array($this, 'render_edit_fields'));
        }
    }

    public function render_add_fields(string $taxonomy): void {
        $this->render_fields($taxonomy);
    }

    public function render_edit_fields(WP_Term $term): void {
        $this->render_fields($term->taxonomy, $term);
    }

    private function render_fields(string $taxonomy, ?WP_Term $term = null): void {
        $object = get_taxonomy($taxonomy);
        if (!$object || !in_array($taxonomy, self::get_enabled(), true) || !current_user_can($object->cap->edit_terms)) return;
        $values = $term ? self::get_metadata($term) : array();
        $fields = array('title' => __('SEO Title', 'vonseo'), 'description' => __('Meta Description', 'vonseo'),
            'canonical' => __('Canonical URL', 'vonseo'), 'social_desc' => __('Social Description', 'vonseo'), 'noindex' => __('Noindex', 'vonseo'));
        foreach ($fields as $field => $label) {
            $id = 'vonseowp_term_' . $field;
            if ($term) {
                echo '<tr class="form-field"><th scope="row"><label for="' . esc_attr($id) . '">' . esc_html($label) . '</label></th><td>';
            } else {
                echo '<div class="form-field"><label for="' . esc_attr($id) . '">' . esc_html($label) . '</label>';
            }
            if ($field === 'title') wp_nonce_field('vonseowp_term_' . $taxonomy, 'vonseowp_term_nonce');
            if (in_array($field, array('description', 'social_desc'), true)) {
                echo '<textarea id="' . esc_attr($id) . '" name="' . esc_attr($id) . '" rows="3" cols="40">' . esc_textarea($values[$field] ?? '') . '</textarea>';
            } elseif ($field === 'noindex') {
                echo '<input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($id) . '" value="1" ' . checked($values[$field] ?? '', '1', false) . ' />';
            } else {
                echo '<input type="' . ($field === 'canonical' ? 'url' : 'text') . '" id="' . esc_attr($id) . '" name="' . esc_attr($id) . '" value="' . esc_attr($values[$field] ?? '') . '" class="regular-text" />';
            }
            echo $term ? '</td></tr>' : '</div>';
        }
    }

    public function save_meta(int $term_id, int $tt_id, string $taxonomy): void {
        $object = get_taxonomy($taxonomy);
        $term = get_term($term_id, $taxonomy);
        if (!$object || !$term instanceof WP_Term || !in_array($taxonomy, self::get_enabled(), true)
            || !current_user_can($object->cap->edit_terms) || !current_user_can('edit_term', $term_id)
            || !isset($_POST['vonseowp_term_nonce']) || !is_string($_POST['vonseowp_term_nonce'])
            || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vonseowp_term_nonce'])), 'vonseowp_term_' . $taxonomy)) return;

        foreach (array('title', 'description', 'canonical', 'social_desc') as $field) {
            $name = 'vonseowp_term_' . $field;
            if (!isset($_POST[$name]) || !is_string($_POST[$name])) continue;
            if ($field === 'canonical') {
                $value = esc_url_raw(wp_unslash($_POST[$name]), array('http', 'https'));
                $parts = wp_parse_url($value);
                if (!is_array($parts) || empty($parts['host']) || !in_array(strtolower($parts['scheme'] ?? ''), array('http', 'https'), true)) $value = '';
            } elseif ($field === 'title') {
                $value = sanitize_text_field(wp_unslash($_POST[$name]));
            } else {
                $value = sanitize_textarea_field(wp_unslash($_POST[$name]));
            }
            if ($value === '') delete_term_meta($term_id, '_vonseowp_' . $field);
            else update_term_meta($term_id, '_vonseowp_' . $field, $value);
        }
        $noindex = isset($_POST['vonseowp_term_noindex']) && $_POST['vonseowp_term_noindex'] === '1';
        if ($noindex) update_term_meta($term_id, '_vonseowp_noindex', '1');
        else delete_term_meta($term_id, '_vonseowp_noindex');
    }
}
