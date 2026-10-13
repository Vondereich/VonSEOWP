<?php
/**
 * Competitor Analysis Module
 * Handles scraping and SEO math comparison.
 */
if (!defined('ABSPATH')) exit;

if (false) {
    require_once dirname(__DIR__) . '/_wp_stubs.php';
}

class VonSEOWP_Competitors {

    public function __construct() {
        add_action('wp_ajax_vonseowp_scan_competitor', array($this, 'ajax_scan_competitor'));
    }

    /**
     * AJAX handler to scan a competitor URL
     */
    public function ajax_scan_competitor() {
        check_ajax_referer('vonseowp_meta_box', 'nonce');

        $raw_post_id = isset($_POST['post_id']) && (is_string($_POST['post_id']) || is_int($_POST['post_id']))
            ? sanitize_text_field(wp_unslash($_POST['post_id'])) : '';
        $post_id = ctype_digit($raw_post_id) ? (int) $raw_post_id : 0;
        $post = $post_id > 0 ? get_post($post_id) : null;
        if (!$post || !VonSEOWP_Content_Types::is_enabled((string) $post->post_type)
            || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(array('message' => __('Unauthorized', 'vonseo')));
        }

        $url = isset($_POST['url']) && is_string($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';
        
        if (empty($url)) {
            wp_send_json_error(array('message' => __('Invalid URL', 'vonseo')));
        }

        $cache_key = 'vonseowp_comp_' . md5($url);
        $results = get_transient($cache_key);

        if (false === $results) {
            $results = $this->analyze_url($url);

            if (is_wp_error($results)) {
                wp_send_json_error(array('message' => $results->get_error_message()));
            }

            // Cache for 12 hours
            set_transient($cache_key, $results, 12 * HOUR_IN_SECONDS);
        }

        wp_send_json_success($results);
    }

    /**
     * Fetch and analyze a URL for SEO metrics
     */
    private function analyze_url(string $url) {
        $response = wp_safe_remote_get($url, array(
            'timeout' => 15,
            'limit_response_size' => 1024 * 1024,
            'user-agent' => 'VonSEOWP-Bot/1.0 (Premium SEO Toolkit)'
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $content_type = (string) wp_remote_retrieve_header($response, 'content-type');
        if ($content_type && !preg_match('/\b(text\/html|application\/xhtml\+xml)\b/i', $content_type)) {
            return new WP_Error('invalid_content_type', __('The competitor URL did not return HTML content', 'vonseo'));
        }

        $html = wp_remote_retrieve_body($response);
        if (empty($html)) {
            return new WP_Error('empty_body', __('Could not retrieve page content', 'vonseo'));
        }

        // Use DOMDocument for parsing
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // Extract Metrics
        $data = array(
            'title' => $this->get_node_text($xpath, '//title'),
            'description' => $this->get_meta_content($xpath, 'description'),
            'h1_count' => $xpath->query('//h1')->length,
            'h2_count' => $xpath->query('//h2')->length,
            'h3_count' => $xpath->query('//h3')->length,
            'image_count' => $xpath->query('//img')->length,
            'image_alt_count' => $xpath->query('//img[@alt!=""]')->length,
            'word_count' => $this->calculate_word_count($dom),
            'reading_ease' => $this->calculate_reading_ease($html)
        );

        return $data;
    }

    private function get_node_text(DOMXPath $xpath, string $query): string {
        $nodes = $xpath->query($query);
        return ($nodes->length > 0) ? trim($nodes->item(0)->textContent) : '';
    }

    private function get_meta_content(DOMXPath $xpath, string $name): string {
        $query = "//meta[@name='$name']/@content | //meta[@property='og:$name']/@content";
        return $this->get_node_text($xpath, $query);
    }

    private function calculate_word_count(DOMDocument $dom): int {
        $body = $dom->getElementsByTagName('body');
        if ($body->length > 0) {
            $text = $body->item(0)->textContent;
            return str_word_count(wp_strip_all_tags($text));
        }
        return 0;
    }

    private function calculate_reading_ease(string $html) {
        $text = wp_strip_all_tags($html);
        $word_count = str_word_count($text);
        if ($word_count === 0) return 0;

        $sentence_count = preg_match_all('/[.!?]/', $text);
        if ($sentence_count === 0) $sentence_count = 1;

        // Basic syllable count approximation
        $syllable_count = $this->count_syllables_simple($text);

        // Flesch-Kincaid formula
        $score = 206.835 - (1.015 * ($word_count / $sentence_count)) - (84.6 * ($syllable_count / $word_count));
        return round(max(0, min(100, $score)));
    }

    private function count_syllables_simple(string $text): int {
        $text = strtolower($text);
        $vowels = array('a', 'e', 'i', 'o', 'u', 'y');
        $count = 0;
        $words = explode(' ', $text);
        foreach ($words as $word) {
            $word = preg_replace('/[^a-z]/', '', $word);
            if (empty($word)) continue;
            
            $syllable_count = 0;
            $prev_is_vowel = false;
            for ($i = 0; $i < strlen($word); $i++) {
                $is_vowel = in_array($word[$i], $vowels);
                if ($is_vowel && !$prev_is_vowel) {
                    $syllable_count++;
                }
                $prev_is_vowel = $is_vowel;
            }
            if (substr($word, -1) === 'e') $syllable_count--;
            $count += max(1, $syllable_count);
        }
        return $count;
    }
}
