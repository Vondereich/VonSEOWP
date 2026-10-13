<?php
/**
 * Redirection Manager
 */
if (!defined('ABSPATH')) exit;

class VonSEOWP_Redirects {

    private $redirects = array();
    private $log_404 = false;
    private $redirect_attachments = true;

    public function __construct() {
        $options = get_option('vonseowp_settings', array());
        if (!is_array($options)) {
            $options = array();
        }

        // Parse Redirects (newline separated string: /old -> /new)
        if (!empty($options['redirects_list'])) {
            $lines = explode("\n", $options['redirects_list']);
            foreach ($lines as $line) {
                $parts = explode('->', $line);
                if (count($parts) === 2) {
                    $this->redirects[trim($parts[0])] = trim($parts[1]);
                }
            }
        }

        $this->log_404 = !empty($options['enable_404_log']);
        $this->redirect_attachments = !isset($options['redirect_attachments']) || (int) $options['redirect_attachments'] === 1;

        add_action('template_redirect', array($this, 'handle_redirects'), 1);
        add_action('template_redirect', array($this, 'redirect_attachment_page'), 5);
        add_action('template_redirect', array($this, 'log_404_error'), 99);
    }

    public function handle_redirects() {
        if (empty($this->redirects)) return;

        $current_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        $target = $this->get_redirect_target($current_uri);
        if ($target !== '' && wp_safe_redirect($target, 301)) {
            exit;
        }
    }

    public function get_redirect_target(string $request_uri): string {
        $path = wp_parse_url($request_uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '' || $path[0] !== '/') return '';
        foreach (array_unique(array($path, untrailingslashit($path), trailingslashit($path))) as $match) {
            if (isset($this->redirects[$match])) {
                return $this->validate_target($this->redirects[$match], $request_uri);
            }
        }
        return '';
    }

    private function validate_target(string $target, string $request_uri): string {
        $target = wp_sanitize_redirect($target);
        if ($target === '') return '';
        if ($target[0] !== '/' && !wp_parse_url($target, PHP_URL_SCHEME)) {
            $path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
            if ($target[0] === '?' || $target[0] === '#') {
                $target = $path . ($target[0] === '#' && wp_parse_url($request_uri, PHP_URL_QUERY)
                    ? '?' . wp_parse_url($request_uri, PHP_URL_QUERY) : '') . $target;
            } else {
                $base = substr($path, -1) === '/' ? $path : trailingslashit(dirname($path));
                $target = $base . $target;
            }
        }
        $target = wp_validate_redirect($target, '');
        if ($target === '') return '';
        $home = wp_parse_url(home_url('/'));
        if (!is_array($home) || empty($home['host'])) return '';
        $request_host = isset($_SERVER['HTTP_HOST']) && is_string($_SERVER['HTTP_HOST'])
            ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : $home['host'];
        if (!isset($_SERVER['HTTP_HOST']) && isset($home['port'])) $request_host .= ':' . $home['port'];
        $origin = (is_ssl() ? 'https' : 'http') . '://' . $request_host;
        // REQUEST_URI already contains any WordPress subdirectory.
        $current = $this->url_identity($origin . $request_uri);
        $destination = $target[0] === '/' ? $origin . $target : $target;
        $destination_identity = $this->url_identity($destination);
        return $current === $destination_identity || $this->is_canonical_rebound($current, $destination_identity)
            ? '' : $target;
    }

    private function is_canonical_rebound(array $current, array $destination): bool {
        if (array_slice($current, 0, 3) !== array_slice($destination, 0, 3)
            || !get_option('permalink_structure') || empty($destination[4])) return false;

        $query = array();
        wp_parse_str($destination[4], $query);
        global $wp;
        if (!isset($wp->public_query_vars) || !is_array($wp->public_query_vars)) return false;
        $identity_keys = array('p', 'page_id', 'attachment_id', 'name');
        if (!array_intersect(array_keys($query), $identity_keys)
            || array_diff(array_intersect(array_keys($query), $wp->public_query_vars),
                array_merge($identity_keys, array('post_type')))) return false;
        $post = null;
        foreach (array('p', 'page_id', 'attachment_id') as $key) {
            if (!isset($query[$key])) continue;
            if (!is_string($query[$key]) || !ctype_digit($query[$key]) || (int) $query[$key] < 1) return false;
            $candidate = get_post((int) $query[$key]);
            if (!$candidate || ($post && $post->ID !== $candidate->ID)
                || ($key === 'page_id' && $candidate->post_type !== 'page')
                || ($key === 'attachment_id' && $candidate->post_type !== 'attachment')) return false;
            $post = $candidate;
        }
        if (!$post && $current[3] === $destination[3]
            && isset($query['name']) && is_string($query['name']) && is_singular()) {
            $candidate = get_post();
            if ($candidate && $candidate->post_name === $query['name']
                && !in_array($candidate->post_type, array('page', 'attachment'), true)) $post = $candidate;
        }
        if (!$post || !in_array($post->post_status, array('publish', 'inherit'), true)) return false;
        if (isset($query['name']) && (!is_string($query['name']) || $query['name'] !== $post->post_name)) return false;
        if (isset($query['post_type']) && $query['post_type'] !== $post->post_type) return false;
        $permalink = get_permalink($post->ID);
        if (!$permalink) return false;

        // Core removes identity parameters; this manager matches paths regardless of the remaining query.
        return array_slice($this->url_identity($permalink), 0, 4) === array_slice($current, 0, 4);
    }

    private function url_identity(string $url): array {
        $parts = wp_parse_url($url);
        if (!is_array($parts)) return array();
        $scheme = strtolower($parts['scheme'] ?? '');
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        $path = rawurldecode($parts['path'] ?? '/');
        $segments = array();
        foreach (explode('/', $path) as $segment) {
            if ($segment === '.') continue;
            if ($segment === '..') {
                array_pop($segments);
            } else {
                $segments[] = $segment;
            }
        }
        $path = implode('/', $segments);
        // Treat slash variants as equivalent to avoid canonical redirect loops.
        return array($scheme, strtolower($parts['host'] ?? ''), $port,
            untrailingslashit($path), $parts['query'] ?? '');
    }

    public function redirect_attachment_page() {
        if (!$this->redirect_attachments || !is_attachment()) {
            return;
        }

        $attachment_id = get_queried_object_id();
        if (!$attachment_id) {
            return;
        }

        $attachment = get_post($attachment_id);
        if (!$attachment || !isset($attachment->post_type) || 'attachment' !== $attachment->post_type) {
            return;
        }

        $target_url = '';
        $parent_id = isset($attachment->post_parent) ? (int) $attachment->post_parent : 0;

        if ($parent_id > 0 && 'publish' === get_post_status($parent_id)) {
            $target_url = get_permalink($parent_id);
        } else {
            $target_url = wp_get_attachment_url($attachment_id);
        }

        if (empty($target_url)) {
            return;
        }

        $current_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        $target_url = $this->validate_target($target_url, $current_uri);
        if ($target_url !== '' && wp_safe_redirect($target_url, 301)) exit;
    }

    public function log_404_error() {
        if (!is_404() || !$this->log_404) return;

        $log = get_option('vonseowp_404_log', array());
        if (!is_array($log)) {
            $log = array();
        }
        $current_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        
        // Truncate URL to prevent option bloat attack
        if (strlen($current_uri) > 200) {
            $current_uri = substr($current_uri, 0, 200) . '...';
        }

        // Add new entry
        if (!isset($log[$current_uri])) {
            $log[$current_uri] = array(
                'count' => 1,
                'last_hit' => current_time('mysql')
            );
        } else {
            $log[$current_uri]['count']++;
            $log[$current_uri]['last_hit'] = current_time('mysql');
        }

        // Limit log size
        if (count($log) > 50) {
            // Sort by time ASC and remove first
            uasort($log, function($a, $b) {
                return strtotime($a['last_hit']) - strtotime($b['last_hit']);
            });
            array_shift($log);
        }

        update_option('vonseowp_404_log', $log);
    }
}
