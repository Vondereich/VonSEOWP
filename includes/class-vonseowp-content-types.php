<?php
/**
 * Shared opt-in policy for per-content SEO tools.
 */
if (!defined('ABSPATH')) exit;

if (false) {
    require_once dirname(__DIR__) . '/_wp_stubs.php';
}

class VonSEOWP_Content_Types {

    /** @return array<string, WP_Post_Type> */
    public static function get_available(): array {
        $available = array();
        foreach (get_post_types(array(), 'objects') as $name => $type) {
            // Core pages are public but intentionally not publicly_queryable.
            if (in_array($name, array('post', 'page'), true)
                || (empty($type->_builtin) && !empty($type->public) && !empty($type->publicly_queryable))
            ) {
                $available[$name] = $type;
            }
        }
        return $available;
    }

    public static function get_saved(array $options): array {
        if (!isset($options['seo_post_types']) || !is_array($options['seo_post_types'])) {
            return array('post', 'page');
        }
        $types = array();
        foreach ($options['seo_post_types'] as $type) {
            if (is_string($type) && $type !== '' && sanitize_key($type) === $type) {
                $types[] = $type;
            }
        }
        return array_values(array_unique($types));
    }

    public static function get_enabled(): array {
        $options = get_option('vonseowp_settings', array());
        $saved = self::get_saved(is_array($options) ? $options : array());
        return array_values(array_intersect($saved, array_keys(self::get_available())));
    }

    public static function is_enabled(string $post_type): bool {
        return in_array($post_type, self::get_enabled(), true);
    }

    public static function get_sitemap_types(array $options): array {
        $types = array_intersect(self::get_saved($options), array_keys(self::get_available()));
        foreach (array('post' => 'sitemap_posts', 'page' => 'sitemap_pages') as $type => $setting) {
            if (isset($options[$setting]) && (int) $options[$setting] !== 1) {
                $types = array_diff($types, array($type));
            }
        }
        return array_values($types);
    }

    /** @param mixed $input Submitted checkbox values. */
    public static function sanitize_selection($input, array $previous): array {
        if (!is_array($input)) {
            return self::get_saved($previous);
        }
        // Retain unavailable saved types, but never accept new arbitrary slugs.
        $allowed = array_merge(array_keys(self::get_available()), self::get_saved($previous));
        $selected = array();
        foreach ($input as $type) {
            if (is_string($type) && in_array($type, $allowed, true)) {
                $selected[] = sanitize_key($type);
            }
        }
        return array_values(array_unique($selected));
    }
}
