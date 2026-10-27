<?php
/** Real WordPress title/password APIs with modeled posts; no database writes. */
declare(strict_types=1);
$root = rtrim($argv[1] ?? '', '/\\');
if (!is_file($root . '/wp-load.php')) throw new RuntimeException('Pass an installed WordPress root.');
require_once $root . '/wp-includes/plugin.php';
add_filter('option_active_plugins', static function (array $plugins): array {
    return array_values(array_diff($plugins, array('vonseo/vonseo.php')));
});
require $root . '/wp-load.php';
require_once dirname(__DIR__) . '/includes/class-vonseowp-content-types.php';
require_once dirname(__DIR__) . '/includes/class-vonseowp-frontend.php';
require_once ABSPATH . WPINC . '/class-phpass.php';
$settings = array('seo_post_types' => array('post', 'page', 'vonseqc_title'));
$meta = array('_vonseowp_title' => 'Native SEO & Title');
add_filter('pre_option_vonseowp_settings', static function () { return $GLOBALS['settings']; });
add_filter('get_post_metadata', static function ($value, $id, $key) {
    return $id === 999999 && isset($GLOBALS['meta'][$key]) ? array($GLOBALS['meta'][$key]) : $value;
}, 10, 3);
register_post_type('vonseqc_title', array('public' => true, 'publicly_queryable' => true));
$frontend = new VonSEOWP_Frontend();
$assertions = 0;
function vonseowp_native_title_check(bool $condition, string $message): void {
    $GLOBALS['assertions']++;
    if (!$condition) throw new RuntimeException($message);
}
foreach (array('post', 'page', 'vonseqc_title') as $type) {
    $post = new WP_Post((object) array('ID' => 999999, 'post_type' => $type, 'post_status' => 'publish', 'filter' => 'raw',
        'post_title' => 'Native public title', 'post_name' => 'title-qc', 'post_content' => '', 'post_password' => ''));
    $wp_query = new WP_Query();
    $wp_query->is_singular = true;
    $wp_query->is_single = $type !== 'page';
    $wp_query->is_page = $type === 'page';
    $wp_query->post = $post;
    $wp_query->queried_object = $post;
    $wp_query->queried_object_id = $post->ID;
    wp_cache_set($post->ID, $post, 'posts');
    vonseowp_native_title_check(wp_title('|', false, 'right') === 'Native SEO &amp; Title | ', 'Native legacy right title failed: ' . $type);
    vonseowp_native_title_check(wp_title('|', false, 'left') === ' | Native SEO &amp; Title', 'Native legacy left title failed: ' . $type);
    vonseowp_native_title_check(wp_get_document_title() === 'Native SEO & Title', 'Modern title changed: ' . $type);
    $post->post_password = 'title-qc-password';
    wp_cache_set($post->ID, $post, 'posts');
    $hasher = new PasswordHash(8, true);
    foreach (array(null, $hasher->HashPassword('wrong'), $hasher->HashPassword('title-qc-password')) as $cookie) {
        $_COOKIE = $cookie === null ? array() : array('wp-postpass_' . COOKIEHASH => $cookie);
        $unlocked = !post_password_required($post);
        $legacy = wp_title('|', false, 'right');
        $modern = wp_get_document_title();
        vonseowp_native_title_check((strpos($legacy, 'Native SEO') !== false) === $unlocked, 'Legacy password-cookie boundary failed: ' . $type);
        vonseowp_native_title_check((strpos($modern, 'Native SEO') !== false) === $unlocked, 'Modern password-cookie boundary failed: ' . $type);
    }
    $_COOKIE = array();
    $post->post_password = '';
    wp_cache_set($post->ID, $post, 'posts');
    $meta['_vonseowp_title'] = '';
    vonseowp_native_title_check(wp_title('|', false, 'right') === 'Native public title | ', 'Native fallback changed: ' . $type);
    $meta['_vonseowp_title'] = 'Native SEO & Title';
}
$wp_query = new WP_Query();
$wp_query->is_home = true;
add_filter('pre_option_show_on_front', static function () { return 'posts'; });
$site_name = get_bloginfo('name');
$settings['home_title'] = $site_name;
vonseowp_native_title_check(wp_title('|', false, 'right') === '', 'Native homepage must let legacy themes add the site name once.');
vonseowp_native_title_check(wp_get_document_title() === $site_name, 'Modern homepage override must remain unchanged.');
$settings['home_title'] = esc_html($site_name);
vonseowp_native_title_check(wp_title('|', false, 'right') === '', 'Entity-equivalent homepage override must not duplicate the site name.');
$settings['home_title'] = 'Distinct Home SEO';
vonseowp_native_title_check(wp_title('|', false, 'right') === 'Distinct Home SEO | ', 'Distinct homepage overrides must remain supported.');
$settings['home_title'] = '';
vonseowp_native_title_check(wp_title('|', false, 'right') === '', 'Empty homepage override must retain the native fallback.');

$canonical = new ReflectionMethod($frontend, 'get_canonical_url');
$canonical->setAccessible(true);
$canonical_options = array('show_on_front' => 'posts', 'page_for_posts' => 0);
foreach (array_keys($canonical_options) as $key) {
    add_filter('pre_option_' . $key, static function () use ($key) { return $GLOBALS['canonical_options'][$key]; }, 20);
}
$blog_page = new WP_Post((object) array('ID' => 999998, 'post_type' => 'page', 'post_status' => 'publish',
    'post_title' => 'Blog', 'post_name' => 'blog', 'post_parent' => 0, 'post_date' => '2026-01-01 00:00:00', 'filter' => 'raw'));
wp_cache_set($blog_page->ID, $blog_page, 'posts');
global $wp_rewrite;
foreach (array('/%postname%/', '') as $structure) {
    $wp_rewrite->permalink_structure = $structure;
    foreach (array('home', 'blog') as $context) {
        $canonical_options['show_on_front'] = $context === 'home' ? 'posts' : 'page';
        $canonical_options['page_for_posts'] = $context === 'home' ? 0 : $blog_page->ID;
        foreach (array(1, 2) as $page_number) {
            $wp_query = new WP_Query();
            $wp_query->is_home = true;
            $wp_query->is_paged = $page_number > 1;
            $wp_query->set('paged', $page_number);
            $post = null;
            $base = $context === 'home' ? home_url('/') : get_permalink($blog_page);
            if ($structure !== '') {
                $expected = $page_number > 1 ? trailingslashit($base) . 'page/2/' : $base;
            } else {
                $expected = $page_number > 1 ? add_query_arg('paged', 2, $base) : $base;
            }
            $_SERVER['REQUEST_URI'] = (string) wp_parse_url($expected, PHP_URL_PATH);
            $query = wp_parse_url($expected, PHP_URL_QUERY);
            if ($query !== null) $_SERVER['REQUEST_URI'] .= '?' . $query;
            $label = ($structure !== '' ? 'pretty' : 'plain') . '/' . $context . '/' . $page_number;
            vonseowp_native_title_check($canonical->invoke($frontend) === $expected, 'Native canonical failed: ' . $label);
            foreach (array('', 'https://canonical.example.invalid/base/') as $host) {
                $settings['canonical_host'] = $host;
                unset($GLOBALS['vonseowp_meta_tags_done']);
                ob_start();
                $frontend->output_meta_tags();
                $head = ob_get_clean();
                $document = new DOMDocument();
                $document->loadHTML('<html><head>' . $head . '</head></html>', LIBXML_NOERROR | LIBXML_NOWARNING);
                $xpath = new DOMXPath($document);
                $expected_head = $host === '' ? $expected : $host . substr($expected, strlen(home_url('/')));
                vonseowp_native_title_check($xpath->evaluate('string(//link[@rel="canonical"]/@href)') === $expected_head, 'Rendered canonical lost path/query: ' . $label);
                vonseowp_native_title_check($xpath->evaluate('string(//meta[@property="og:url"]/@content)') === $expected_head, 'Social URL differs from canonical: ' . $label);
            }
        }
    }
}

global $wp_version;
echo 'Native title tests passed (' . $assertions . ' assertions; WordPress ' . $wp_version . ', PHP ' . PHP_VERSION . ').' . PHP_EOL;
