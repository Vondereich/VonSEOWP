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

        // v2: metrics now share the editor's text rules and non-2xx pages are rejected; ignore older caches.
        $cache_key = 'vonseowp_comp_v2_' . md5($url);
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

    private const MAX_REDIRECTS = 3;

    /**
     * wp_safe_remote_get() with each redirect hop followed here, so every hop passes is_allowed_target()
     * as well as core's own private/loopback checks.
     *
     * @return array|WP_Error
     */
    private function fetch(string $url) {
        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            if (!self::is_allowed_target($url)) {
                return new WP_Error('blocked_host', __('The competitor URL points to a local or reserved network address.', 'vonseo'));
            }
            $response = wp_safe_remote_get($url, array(
                'timeout' => 15,
                'redirection' => 0,
                'limit_response_size' => 1024 * 1024,
                'user-agent' => 'VonSEOWP-Bot/1.0 (Premium SEO Toolkit)'
            ));
            if (is_wp_error($response)) return $response;
            $status = (int) wp_remote_retrieve_response_code($response);
            $location = wp_remote_retrieve_header($response, 'location');
            if (is_array($location)) $location = (string) end($location);
            if ($status < 300 || $status >= 400 || !is_string($location) || trim($location) === '') {
                return $response;
            }
            $url = WP_Http::make_absolute_url(trim($location), $url);
        }
        return new WP_Error('too_many_redirects', __('The competitor URL redirected too many times.', 'vonseo'));
    }

    /**
     * Rejects non-HTTP(S) URLs, IPv6 literals and hosts resolving to link-local, CGNAT, private, loopback
     * or reserved addresses. Older WordPress releases do not block link-local (cloud metadata) or CGNAT
     * ranges in wp_safe_remote_get(), and core never checks IPv6 (AAAA) records.
     */
    public static function is_allowed_target(string $url): bool {
        $parts = wp_parse_url($url);
        if (!is_array($parts) || empty($parts['host']) || !in_array(strtolower($parts['scheme'] ?? ''), array('http', 'https'), true)) {
            return false;
        }
        $host = strtolower(trim((string) $parts['host'], '[]'));
        if ($host === 'localhost' || substr($host, -10) === '.localhost' || strpos($host, ':') !== false) return false;
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return !self::is_reserved_ipv4($host);
        }
        $ipv4 = function_exists('gethostbynamel') ? gethostbynamel($host) : false;
        foreach (is_array($ipv4) ? $ipv4 : array() as $ip) {
            if (self::is_reserved_ipv4($ip)) return false;
        }
        $ipv6 = function_exists('dns_get_record') ? @dns_get_record($host, DNS_AAAA) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Lookup failures are expected for IPv4-only hosts.
        foreach (is_array($ipv6) ? $ipv6 : array() as $record) {
            if (isset($record['ipv6']) && self::is_reserved_ipv6((string) $record['ipv6'])) return false;
        }
        return true;
    }

    public static function is_reserved_ipv4(string $ip): bool {
        $long = ip2long($ip);
        if ($long === false) return true;
        foreach (array('0.0.0.0/8', '10.0.0.0/8', '100.64.0.0/10', '127.0.0.0/8', '169.254.0.0/16', '172.16.0.0/12',
            '192.0.0.0/24', '192.0.2.0/24', '192.88.99.0/24', '192.168.0.0/16', '198.18.0.0/15', '198.51.100.0/24',
            '203.0.113.0/24', '224.0.0.0/3') as $range) {
            list($network, $bits) = explode('/', $range);
            $mask = -1 << (32 - (int) $bits);
            if (($long & $mask) === (ip2long($network) & $mask)) return true;
        }
        return false;
    }

    public static function is_reserved_ipv6(string $ip): bool {
        $packed = @inet_pton($ip); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Invalid input is treated as reserved.
        if ($packed === false || strlen($packed) !== 16) return true;
        $first = ord($packed[0]);
        $second = ord($packed[1]);
        if ($packed === str_repeat(chr(0), 15) . chr(1) || $packed === str_repeat(chr(0), 16)) return true; // ::1, ::
        if (($first & 0xfe) === 0xfc) return true;                 // fc00::/7 unique local
        if ($first === 0xfe && ($second & 0xc0) === 0x80) return true; // fe80::/10 link-local
        if ($first === 0xff) return true;                          // multicast
        if (substr($packed, 0, 12) === str_repeat(chr(0), 10) . chr(255) . chr(255)) {   // ::ffff:a.b.c.d
            return self::is_reserved_ipv4((string) inet_ntop(substr($packed, 12)));
        }
        return false;
    }

    /**
     * Fetch and analyze a URL for SEO metrics
     */
    private function analyze_url(string $url) {
        $response = $this->fetch($url);

        if (is_wp_error($response)) {
            return $response;
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        if ($status < 200 || $status >= 300) {
            return new WP_Error('http_status', sprintf(
                /* translators: %d: HTTP status code */
                __('The competitor URL returned HTTP %d instead of a successful page.', 'vonseo'), $status
            ));
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

        $metrics = self::text_metrics(self::visible_text($dom));

        return array(
            'title' => $this->get_node_text($xpath, '//title'),
            'description' => $this->get_meta_content($xpath, 'description'),
            'h1_count' => $xpath->query('//h1')->length,
            'h2_count' => $xpath->query('//h2')->length,
            'h3_count' => $xpath->query('//h3')->length,
            'image_count' => $xpath->query('//img')->length,
            'image_alt_count' => $xpath->query('//img[normalize-space(@alt)!=""]')->length,
            'word_count' => $metrics['word_count'],
            'reading_ease' => $metrics['reading_ease'],
        );
    }

    private function get_node_text(DOMXPath $xpath, string $query): string {
        $nodes = $xpath->query($query);
        return ($nodes->length > 0) ? trim($nodes->item(0)->textContent) : '';
    }

    private function get_meta_content(DOMXPath $xpath, string $name): string {
        $query = "//meta[@name='$name']/@content | //meta[@property='og:$name']/@content";
        return $this->get_node_text($xpath, $query);
    }

    /** Mirrors visibleText() in admin/js/vonseowp-metabox.js. */
    public const BLOCK_TAGS = array('address', 'article', 'aside', 'blockquote', 'br', 'dd', 'details', 'div', 'dl', 'dt',
        'figcaption', 'figure', 'footer', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'hr', 'li', 'main', 'nav',
        'ol', 'p', 'pre', 'section', 'summary', 'table', 'td', 'th', 'tr', 'ul');

    /**
     * Rendered body text: non-rendered elements are dropped and block boundaries become spaces, so
     * <p>a</p><p>b</p> stays two words while inline markup such as <b>Wor</b>d stays one.
     */
    public static function visible_text(DOMDocument $dom): string {
        foreach (array('script', 'style', 'noscript', 'template') as $tag) {
            $nodes = $dom->getElementsByTagName($tag);
            for ($index = $nodes->length - 1; $index >= 0; $index--) {
                $node = $nodes->item($index);
                if ($node && $node->parentNode) $node->parentNode->removeChild($node);
            }
        }
        foreach (self::BLOCK_TAGS as $tag) {
            $nodes = $dom->getElementsByTagName($tag);
            for ($index = $nodes->length - 1; $index >= 0; $index--) {
                $node = $nodes->item($index);
                if (!$node || !$node->parentNode) continue;
                $node->parentNode->insertBefore($dom->createTextNode(' '), $node);
                $node->parentNode->insertBefore($dom->createTextNode(' '), $node->nextSibling);
            }
        }
        $body = $dom->getElementsByTagName('body');
        return $body->length > 0 ? (string) $body->item(0)->textContent : '';
    }

    /**
     * Shared comparison metrics. Mirrors VonSEOWPAnalyzer.textMetrics() in admin/js/vonseowp-analyzer.js
     * exactly, so the "You" and "Competitor" columns use identical rules.
     *
     * @return array{word_count: int, reading_ease: int}
     */
    public static function text_metrics(string $text): array {
        $tokens = preg_split('/(*UCP)\s+/u', $text);
        $words = array_values(array_filter(is_array($tokens) ? $tokens : array(), static function ($token): bool {
            return preg_match('/[\p{L}\p{N}]/u', (string) $token) === 1;
        }));
        $word_count = count($words);
        if ($word_count === 0) return array('word_count' => 0, 'reading_ease' => 0);
        $sentences = max(1, (int) preg_match_all('/[.!?]+/u', $text));
        $syllables = 0;
        foreach ($words as $word) $syllables += self::count_syllables($word);
        $score = 206.835 - (1.015 * ($word_count / $sentences)) - (84.6 * ($syllables / $word_count));
        return array('word_count' => $word_count, 'reading_ease' => (int) round(max(0, min(100, $score))));
    }

    private static function count_syllables(string $word): int {
        $letters = (string) preg_replace('/[^a-z]/', '', strtolower($word));
        if ($letters === '') return 1;
        $count = (int) preg_match_all('/[aeiouy]+/', $letters);
        if ($count > 1 && preg_match('/[^aeiouy]e$/', $letters)) $count--;
        return max(1, $count);
    }
}
