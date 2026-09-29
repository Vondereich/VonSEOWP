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

    public function __construct() {
        add_filter('the_content', array($this, 'inject_toc'), 100);
        add_shortcode('vonseo_toc', array($this, 'render_shortcode'));
    }

    /**
     * Shortcode [vonseo_toc]
     */
    public function render_shortcode($atts = array()): string {
        $post = get_post();
        if (!$post) return '';

        $headings = $this->extract_headings($post->post_content);
        if (empty($headings)) return '';

        return $this->generate_toc_markup($headings);
    }

    /**
     * Automatically inject TOC into content if enabled
     */
    public function inject_toc(string $content): string {
        if (!is_singular() || !in_the_loop()) {
            return $content;
        }

        $options = get_option('vonseowp_settings', array());
        $enabled = isset($options['enable_toc']) ? (bool)$options['enable_toc'] : false;

        if (!$enabled) {
            return $content;
        }

        // Check if TOC is manually disabled for this post
        $post_id = get_the_ID();
        if (get_post_meta($post_id, '_vonseowp_disable_toc', true) === '1') {
            return $content;
        }

        $headings = $this->extract_headings($content);
        if (count($headings) < (int)($options['toc_min_headings'] ?? 3)) {
            return $content;
        }

        // Add anchors to content headings
        $content_with_anchors = $this->add_anchors_to_content($content, $headings);
        
        // Generate TOC Markup
        $toc_markup = $this->generate_toc_markup($headings);

        // Inject position
        $position = $options['toc_position'] ?? 'before_first_heading';

        if ($position === 'before_first_heading') {
            $first_heading_tag = $headings[0]['tag'];
            $pattern = '/<'.$first_heading_tag.'[^>]*>'.preg_quote($headings[0]['text'], '/').'<\/'.$first_heading_tag.'>/is';
            $content_with_anchors = preg_replace($pattern, $toc_markup . '$0', $content_with_anchors, 1);
        } else {
            $content_with_anchors = $toc_markup . $content_with_anchors;
        }

        return $content_with_anchors;
    }

    /**
     * Extract h1-h6 headings from content
     */
    private function extract_headings(string $content): array {
        $headings = array();
        if (empty($content)) return $headings;

        $clean_content = $this->remove_ignored_blocks($content);

        if (preg_match_all('/<(h[1-6])([^>]*)>(.*?)<\/h[1-6]>/is', $clean_content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $tag = strtolower($match[1]);
                $text = wp_strip_all_tags($match[3]);
                if (empty($text)) continue;

                $headings[] = array(
                    'tag'   => $tag,
                    'level' => (int)substr($tag, 1),
                    'text'  => $text,
                    'anchor' => sanitize_title($text)
                );
            }
        }

        return $headings;
    }

    /**
     * Remove blocks that must not contribute headings without treating HTML as a regular language.
     */
    private function remove_ignored_blocks(string $content): string {
        $ignored_names = array('script', 'style', 'pre', 'code');
        $output = '';
        $ignored_name = '';
        $index = 0;
        $length = strlen($content);

        while ($index < $length) {
            if ($ignored_name !== '') {
                $closing_prefix = '</' . $ignored_name;
                $prefix_length = strlen($closing_prefix);
                $possible_closing_tag = substr($content, $index, $prefix_length);
                $boundary = $content[$index + $prefix_length] ?? '';
                $has_boundary = $boundary === '' || ctype_space($boundary) || $boundary === '/' || $boundary === '>';

                if (strtolower($possible_closing_tag) === $closing_prefix && $has_boundary) {
                    $tag_end = $this->find_tag_end($content, $index);
                    if ($tag_end < 0) {
                        break;
                    }

                    $ignored_name = '';
                    $output .= ' ';
                    $index = $tag_end + 1;
                    continue;
                }

                $index++;
                continue;
            }

            if ($content[$index] !== '<') {
                $output .= $content[$index];
                $index++;
                continue;
            }

            $tag_end = $this->find_tag_end($content, $index);
            if ($tag_end < 0) {
                $output .= substr($content, $index);
                break;
            }

            $tag_source = substr($content, $index, $tag_end - $index + 1);
            $tag = $this->parse_tag($tag_source);
            if (in_array($tag['name'], $ignored_names, true) && !$tag['closing']) {
                $ignored_name = $tag['name'];
            } else {
                $output .= $tag_source;
            }

            $index = $tag_end + 1;
        }

        return $output;
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

        while ($index < $length && ctype_space($tag[$index])) {
            $index++;
        }
        if ($index < $length && $tag[$index] === '/') {
            $closing = true;
            $index++;
        }
        while ($index < $length && ctype_space($tag[$index])) {
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
        foreach ($headings as $h) {
            $pattern = '/<'.$h['tag'].'([^>]*)>'.preg_quote($h['text'], '/').'<\/'.$h['tag'].'>/is';
            $replacement = '<'.$h['tag'].'$1 id="'.$h['anchor'].'">'.$h['text'].'</'.$h['tag'].'>';
            $content = preg_replace($pattern, $replacement, $content, 1);
        }
        return $content;
    }

    /**
     * Generate HTML for TOC
     */
    private function generate_toc_markup(array $headings): string {
        $options = get_option('vonseowp_settings', array());
        $title = !empty($options['toc_title']) ? $options['toc_title'] : __('Table of Contents', 'vonseo');
        
        $html = '<div class="vonseo-toc-container" role="navigation" aria-label="'.esc_attr($title).'">';
        $html .= '<div class="vonseo-toc-header">';
        $html .= '<span class="vonseo-toc-title">' . esc_html($title) . '</span>';
        $html .= '<button type="button" class="vonseo-toc-toggle" aria-expanded="true">[' . esc_html__('hide', 'vonseo') . ']</button>';
        $html .= '</div>';
        $html .= '<ul class="vonseo-toc-list">';

        $base_level = $headings[0]['level'];

        foreach ($headings as $h) {
            $depth_class = 'vonseo-toc-depth-' . ($h['level'] - $base_level + 1);
            $html .= '<li class="' . esc_attr($depth_class) . '">';
            $html .= '<a href="#' . esc_attr($h['anchor']) . '">' . esc_html($h['text']) . '</a>';
            $html .= '</li>';
        }

        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }
}
