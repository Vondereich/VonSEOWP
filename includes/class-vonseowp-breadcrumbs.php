<?php
/**
 * Breadcrumb generator for themes and shortcode usage.
 */
if (!defined('ABSPATH')) exit;

class VonSEOWP_Breadcrumbs {

    public function __construct() {
        add_shortcode('vonseo_breadcrumbs', array($this, 'render_shortcode'));
        add_shortcode('vonseowp_breadcrumbs', array($this, 'render_shortcode'));
    }

    public function render_shortcode($atts = array(), $content = null, string $shortcode_tag = 'vonseo_breadcrumbs'): string {
        $atts = shortcode_atts(array(
            'separator'    => '',
            'show_current' => '1',
            'class'        => '',
        ), $atts, $shortcode_tag);

        return self::get_markup(array(
            'separator'    => $atts['separator'],
            'show_current' => '0' !== $atts['show_current'],
            'class'        => $atts['class'],
        ));
    }

    public static function get_markup(array $args = array()): string {
        $defaults = array(
            'separator'    => VonSEOWP::get_option('breadcrumb_separator', '/'),
            'show_current' => true,
            'class'        => '',
        );

        $args = wp_parse_args($args, $defaults);
        $items = self::get_items();

        if (empty($items)) {
            return '';
        }

        if (empty($args['show_current'])) {
            array_pop($items);
        }

        if (count($items) < 2) {
            return '';
        }

        $separator = '' !== trim((string) $args['separator']) ? (string) $args['separator'] : '/';
        $classes = array('vonseo-breadcrumbs');

        if (!empty($args['class'])) {
            $extra_classes = preg_split('/\s+/', (string) $args['class']);
            foreach ($extra_classes as $extra_class) {
                $extra_class = sanitize_html_class($extra_class);
                if (!empty($extra_class)) {
                    $classes[] = $extra_class;
                }
            }
        }

        $parts = array();
        $last_index = count($items) - 1;

        foreach ($items as $index => $item) {
            $label = isset($item['label']) ? $item['label'] : '';
            $url = isset($item['url']) ? $item['url'] : '';

            if ('' === $label) {
                continue;
            }

            if ($index === $last_index || empty($url)) {
                $parts[] = '<span class="vonseo-breadcrumbs__current" aria-current="page">' . esc_html($label) . '</span>';
                continue;
            }

            $parts[] = '<a class="vonseo-breadcrumbs__link" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
        }

        if (count($parts) < 2) {
            return '';
        }

        $separator_html = '<span class="vonseo-breadcrumbs__separator" aria-hidden="true"> ' . esc_html($separator) . ' </span>';

        return '<nav class="' . esc_attr(implode(' ', array_unique($classes))) . '" aria-label="' . esc_attr__('Breadcrumbs', 'vonseo') . '">' . implode($separator_html, $parts) . '</nav>';
    }

    public static function get_items(): array {
        // The root of a "static page" setup with no homepage chosen is is_home() but queries no page.
        $posts_page = (int) get_option('page_for_posts');
        $is_posts_page = is_home() && get_option('show_on_front') === 'page' && $posts_page > 0
            && (int) get_queried_object_id() === $posts_page;
        if (is_front_page() || (is_home() && !$is_posts_page)) {
            return array();
        }

        $items = array(
            array(
                'label' => __('Home', 'vonseo'),
                'url'   => home_url('/'),
            ),
        );

        if (is_home()) {
            $posts_page_id = (int) get_option('page_for_posts');
            if ($posts_page_id > 0) {
                $items[] = array(
                    'label' => get_the_title($posts_page_id),
                    'url'   => get_permalink($posts_page_id),
                );
            }

            return self::filter_items($items);
        }

        if (is_singular()) {
            $items = self::build_singular_items($items);
            return self::filter_items($items);
        }

        if (is_category() || is_tag() || is_tax()) {
            $items = self::build_term_items($items);
            return self::filter_items($items);
        }

        if (is_post_type_archive()) {
            $post_type_object = get_queried_object();
            if ($post_type_object && !empty($post_type_object->labels->name)) {
                $items[] = array(
                    'label' => $post_type_object->labels->name,
                    'url'   => get_post_type_archive_link($post_type_object->name),
                );
            }

            return self::filter_items($items);
        }

        if (is_search()) {
            $items[] = array(
                /* translators: %s: search query */
                'label' => sprintf(__('Search results for "%s"', 'vonseo'), get_search_query()),
                'url'   => '',
            );

            return self::filter_items($items);
        }

        if (is_author()) {
            $author = get_queried_object();
            if ($author && !empty($author->display_name)) {
                $items[] = array(
                    /* translators: %s: author name */
                    'label' => sprintf(__('Articles by %s', 'vonseo'), $author->display_name),
                    'url'   => '',
                );
            }

            return self::filter_items($items);
        }

        if (is_year() || is_month() || is_day()) {
            $items[] = array(
                'label' => __('Archives', 'vonseo'),
                'url'   => '',
            );

            return self::filter_items($items);
        }

        if (is_404()) {
            $items[] = array(
                'label' => __('404 Not Found', 'vonseo'),
                'url'   => '',
            );
        }

        return self::filter_items($items);
    }

    /** A third-party filter returning a non-array must not break breadcrumbs or the head schema. */
    private static function filter_items(array $items): array {
        $filtered = apply_filters('vonseowp_breadcrumb_items', $items);
        return is_array($filtered) ? $filtered : $items;
    }

    public static function is_public_ancestor(int $post_id): bool {
        $ancestor = get_post($post_id);
        // Password-protected pages stay listed: WordPress already shows their titles publicly.
        return $ancestor && $ancestor->post_status === 'publish';
    }

    private static function build_singular_items(array $items): array {
        $post_id = get_queried_object_id();
        if (!$post_id) {
            return $items;
        }

        if (is_page()) {
            $ancestors = array_reverse(get_post_ancestors($post_id));
            foreach ($ancestors as $ancestor_id) {
                // Never reveal private, draft or pending parent pages in a public trail.
                if (!self::is_public_ancestor((int) $ancestor_id)) continue;
                $items[] = array(
                    'label' => get_the_title($ancestor_id),
                    'url'   => get_permalink($ancestor_id),
                );
            }
        } elseif (is_single()) {
            $categories = get_the_category($post_id);
            if (!empty($categories) && !is_wp_error($categories)) {
                $primary_category = $categories[0];
                $ancestor_ids = array_reverse(get_ancestors($primary_category->term_id, 'category'));

                foreach ($ancestor_ids as $ancestor_id) {
                    $ancestor = get_term($ancestor_id, 'category');
                    if ($ancestor && !is_wp_error($ancestor)) {
                        $items[] = array(
                            'label' => $ancestor->name,
                            'url'   => get_term_link($ancestor),
                        );
                    }
                }

                $items[] = array(
                    'label' => $primary_category->name,
                    'url'   => get_term_link($primary_category),
                );
            }
        }

        $items[] = array(
            'label' => get_the_title($post_id),
            'url'   => get_permalink($post_id),
        );

        return $items;
    }

    private static function build_term_items(array $items): array {
        $term = get_queried_object();
        if (!$term || empty($term->taxonomy)) {
            return $items;
        }

        $ancestor_ids = array_reverse(get_ancestors($term->term_id, $term->taxonomy));
        foreach ($ancestor_ids as $ancestor_id) {
            $ancestor = get_term($ancestor_id, $term->taxonomy);
            if ($ancestor && !is_wp_error($ancestor)) {
                $items[] = array(
                    'label' => $ancestor->name,
                    'url'   => get_term_link($ancestor),
                );
            }
        }

        $items[] = array(
            'label' => $term->name,
            'url'   => get_term_link($term),
        );

        return $items;
    }
}