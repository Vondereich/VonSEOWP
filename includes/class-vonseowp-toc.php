<?php
/**
 * Advanced Table of Contents (TOC) Generator
 * Automatically generates a structured TOC based on headings in the content.
 */

if (!defined('ABSPATH')) exit;

if (false) {
    require_once dirname(__DIR__) . '/_wp_stubs.php';
}

class VonSEOWP_TOC {

    private $shortcode_markers = array();
    private $shortcode_count = 0;
    private $external_shortcode_posts = array();
    private $rendering_external_shortcode = false;

    public function __construct() {
        add_filter('the_content', array($this, 'inject_toc'), 100);
        add_shortcode('vonseo_toc', array($this, 'render_shortcode'));
    }

    /**
     * Shortcode [vonseo_toc]
     */
    public function render_shortcode($atts = array()): string {
        if ($this->rendering_external_shortcode) return '';
        if (!doing_filter('the_content')) {
            $post = get_post();
            if (!$post || post_password_required($post)) return '';
            $this->external_shortcode_posts[$post->ID] = true;
            $this->rendering_external_shortcode = true;
            try {
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Use WordPress's native content pipeline, not a custom hook.
                $content = apply_filters('the_content', get_the_content(null, false, $post));
            } finally {
                $this->rendering_external_shortcode = false;
            }
            $headings = $this->extract_headings($content);
            return empty($headings) ? '' : $this->generate_toc_markup($headings);
        }
        // Resolve after blocks and other shortcodes have produced the final headings.
        $marker = '<!-- vonseo-toc-' . ++$this->shortcode_count . ' -->';
        $this->shortcode_markers[] = $marker;
        return $marker;
    }

    /**
     * Automatically inject TOC into content if enabled
     */
    public function inject_toc(string $content): string {
        if (strpos($content, '<div class="vonseo-toc-container"') !== false) return $content;
        $markers = array();
        foreach ($this->shortcode_markers as $marker) {
            if (strpos($content, $marker) !== false) {
                $markers[] = $marker;
            }
        }
        $options = get_option('vonseowp_settings', array());
        $external = isset($this->external_shortcode_posts[get_the_ID()]);
        $automatic = is_singular() && in_the_loop() && !empty($options['enable_toc'])
            && !$external && get_post_meta(get_the_ID(), '_vonseowp_disable_toc', true) !== '1';
        if (!$automatic && !$external && empty($markers)) {
            return $content;
        }
        $headings = $this->extract_headings($content);
        $minimum = max(1, min(10, (int)($options['toc_min_headings'] ?? 3)));
        if (empty($headings) || (!$external && empty($markers) && count($headings) < $minimum)) {
            return str_replace($markers, '', $content);
        }
        $content_with_anchors = $this->add_anchors_to_content($content, $headings);
        if ($external && empty($markers)) return $content_with_anchors;
        $toc_markup = $this->generate_toc_markup($headings);
        if (!empty($markers)) {
            $position = strpos($content_with_anchors, $markers[0]);
            $content_with_anchors = substr_replace($content_with_anchors, $toc_markup, $position, strlen($markers[0]));
            return str_replace($markers, '', $content_with_anchors);
        }
        $position = ($options['toc_position'] ?? 'before_first_heading') === 'before_first_heading'
            ? $headings[0]['offset'] : 0;
        return substr_replace($content_with_anchors, $toc_markup, $position, 0);
    }

    /**
     * Extract h1-h6 headings from content
     */
    private function extract_headings(string $content): array {
        $headings = array();
        $reserved = array();
        $pending = null;
        $tags = $this->scan_tags($content);
        foreach ($tags as $tag) {
            if (!$tag['closing']) {
                $id = $this->get_tag_id($tag['source']);
                if ($this->is_valid_id($id) && !isset($reserved[$id])) {
                    $reserved[$id] = $tag['offset'];
                }
            }
            if ($tag['ignored'] || !preg_match('/^h[1-6]$/', $tag['name'])) continue;
            if (!$tag['closing']) {
                $pending = $tag;
            } elseif ($pending !== null && $pending['name'] === $tag['name']) {
                $inner_start = $pending['offset'] + strlen($pending['source']);
                $text = trim(html_entity_decode(wp_strip_all_tags(substr($content, $inner_start, $tag['offset'] - $inner_start)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($text !== '') {
                    $headings[] = array(
                        'tag' => $pending['name'],
                        'level' => (int)substr($pending['name'], 1),
                        'text' => $text,
                        'offset' => $pending['offset'],
                        'source' => $pending['source'],
                    );
                }
                $pending = null;
            }
        }
        $next_suffix = array();
        foreach ($headings as $index => &$heading) {
            $id = $this->get_tag_id($heading['source']);
            if ($this->is_valid_id($id) && $reserved[$id] === $heading['offset']) {
                $heading['anchor'] = $id;
                continue;
            }
            $base = rawurldecode(sanitize_title($heading['text']));
            if (!$this->is_valid_id($base)) $base = 'vonseo-heading-' . ($index + 1);
            $anchor = $base;
            $suffix = $next_suffix[$base] ?? 2;
            while (isset($reserved[$anchor])) {
                $anchor = $base . '-' . $suffix++;
            }
            $reserved[$anchor] = $heading['offset'];
            $next_suffix[$base] = $suffix;
            $heading['anchor'] = $anchor;
        }
        unset($heading);
        return $headings;
    }

    /**
     * Retain byte offsets so only opening tags change, never the heading's inner markup.
     */
    private function scan_tags(string $content): array {
        $raw_names = array('script', 'style', 'textarea', 'title', 'xmp', 'iframe', 'noembed', 'noframes');
        $ignored_depth = array('pre' => 0, 'code' => 0);
        $tags = array();
        $index = 0;
        $length = strlen($content);
        while ($index < $length && ($index = strpos($content, '<', $index)) !== false) {
            if (substr($content, $index, 4) === '<!--') {
                $end = strpos($content, '-->', $index + 4);
                if ($end === false) break;
                $index = $end + 3;
                continue;
            }
            $tag_end = $this->find_tag_end($content, $index);
            if ($tag_end < 0) break;
            $tag_source = substr($content, $index, $tag_end - $index + 1);
            $tag = $this->parse_tag($tag_source);
            if ($tag['name'] === '') {
                $index++;
                continue;
            }
            $tag['source'] = $tag_source;
            $tag['offset'] = $index;
            $tag['ignored'] = array_sum($ignored_depth) > 0;
            $tags[] = $tag;
            if (isset($ignored_depth[$tag['name']])) {
                $ignored_depth[$tag['name']] = max(0, $ignored_depth[$tag['name']] + ($tag['closing'] ? -1 : 1));
            }
            $index = $tag_end + 1;
            if (!$tag['closing'] && in_array($tag['name'], $raw_names, true)) {
                $prefix = '</' . $tag['name'];
                while (($index = stripos($content, $prefix, $index)) !== false) {
                    $boundary = $content[$index + strlen($prefix)] ?? '';
                    if ($boundary === '>' || $boundary === '/' || ctype_space($boundary)) break;
                    $index += strlen($prefix);
                }
                if ($index === false) break;
            }
        }
        return $tags;
    }

    private function find_tag_end(string $content, int $start): int {
        $quote = '';
        $length = strlen($content);

        for ($index = $start + 1; $index < $length; $index++) {
            $character = $content[$index];
            if ($quote !== '') {
                if ($character === $quote) {
                    $quote = '';
                }
                continue;
            }

            if ($character === '"' || $character === "'") {
                $quote = $character;
            } elseif ($character === '>') {
                return $index;
            }
        }

        return -1;
    }

    /**
     * @return array{name: string, closing: bool}
     */
    private function parse_tag(string $tag): array {
        $index = 1;
        $length = strlen($tag);
        $closing = false;

        if ($index < $length && $tag[$index] === '/') {
            $closing = true;
            $index++;
        }

        $start = $index;
        while ($index < $length) {
            $character = $tag[$index];
            if (!ctype_alnum($character) && $character !== ':' && $character !== '-') {
                break;
            }
            $index++;
        }

        return array(
            'name' => strtolower(substr($tag, $start, $index - $start)),
            'closing' => $closing,
        );
    }

    /**
     * Add ID anchors to heading tags in content
     */
    private function add_anchors_to_content(string $content, array $headings): string {
        $parts = array();
        $cursor = 0;
        foreach ($headings as $heading) {
            $source = $heading['source'];
            $ids = $this->get_id_attributes($source);
            if (count($ids) === 1 && $this->get_tag_id($source) === $heading['anchor']) continue;
            // Remove invalid/duplicate IDs before setting exactly one attribute.
            foreach (array_reverse($ids) as $id) {
                $source = substr_replace($source, '', $id['offset'], $id['length']);
            }
            if (class_exists('WP_HTML_Tag_Processor')) {
                $processor = new WP_HTML_Tag_Processor($source);
                if ($processor->next_tag()) {
                    $processor->set_attribute('id', $heading['anchor']);
                    $source = $processor->get_updated_html();
                }
            } else {
                // WordPress 6.0/6.1 do not yet provide the native HTML API.
                $end = strlen($source) - 1;
                if ($source[$end - 1] === '/') $end--;
                $source = substr_replace($source, ' id="' . esc_attr($heading['anchor']) . '"', $end, 0);
            }
            $parts[] = substr($content, $cursor, $heading['offset'] - $cursor);
            $parts[] = $source;
            $cursor = $heading['offset'] + strlen($heading['source']);
        }
        $parts[] = substr($content, $cursor);
        return implode('', $parts);
    }

    /** @param mixed $id */
    private function is_valid_id($id): bool {
        return is_string($id) && $id !== '' && !preg_match('/[\x00\x09\x0a\x0c\x0d\x20]/', $id);
    }

    private function get_tag_id(string $source) {
        if (class_exists('WP_HTML_Tag_Processor')) {
            $processor = new WP_HTML_Tag_Processor($source);
            if ($processor->next_tag()) return $processor->get_attribute('id');
        }
        $ids = $this->get_id_attributes($source);
        return empty($ids) ? null : $ids[0]['value'];
    }

    private function get_id_attributes(string $source): array {
        $ids = array();
        // Tokenize whole attributes, including quoted values containing spaces or '>'.
        preg_match_all('/(?:\s+|\/)([^\s=\/>]+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+)))?/', $source, $attributes, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        foreach ($attributes as $attribute) {
            if (strtolower($attribute[1][0]) !== 'id') continue;
            $value = '';
            for ($index = 2; $index <= 4; $index++) {
                if (isset($attribute[$index]) && $attribute[$index][1] >= 0) {
                    $value = html_entity_decode($attribute[$index][0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    break;
                }
            }
            $ids[] = array('offset' => $attribute[0][1], 'length' => strlen($attribute[0][0]), 'value' => $value);
        }
        return $ids;
    }

    /**
     * Generate HTML for TOC
     */
    private function generate_toc_markup(array $headings): string {
        $options = get_option('vonseowp_settings', array());
        $title = !empty($options['toc_title']) ? $options['toc_title'] : __('Table of Contents', 'vonseo');
        
        $list_id = wp_unique_id('vonseo-toc-list-');
        $show_label = '[' . __('show', 'vonseo') . ']';
        $hide_label = '[' . __('hide', 'vonseo') . ']';
        $html = '<div class="vonseo-toc-container" role="navigation" aria-label="'.esc_attr($title).'">';
        $html .= '<div class="vonseo-toc-header">';
        $html .= '<span class="vonseo-toc-title">' . esc_html($title) . '</span>';
        $html .= '<button type="button" class="vonseo-toc-toggle" aria-expanded="true" aria-controls="' . esc_attr($list_id) . '" data-show-label="' . esc_attr($show_label) . '" data-hide-label="' . esc_attr($hide_label) . '">' . esc_html($hide_label) . '</button>';
        $html .= '</div>';
        $html .= '<ul id="' . esc_attr($list_id) . '" class="vonseo-toc-list">';

        $base_level = min(array_column($headings, 'level'));

        foreach ($headings as $h) {
            $depth_class = 'vonseo-toc-depth-' . ($h['level'] - $base_level + 1);
            $html .= '<li class="' . esc_attr($depth_class) . '">';
            $html .= '<a href="#' . esc_attr(rawurlencode($h['anchor'])) . '">' . esc_html($h['text']) . '</a>';
            $html .= '</li>';
        }

        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }
}
