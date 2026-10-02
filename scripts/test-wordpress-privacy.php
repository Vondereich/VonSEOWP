<?php
/** Read-only integration: php scripts/test-wordpress-privacy.php /path/to/wordpress */
declare(strict_types=1);
$root = rtrim($argv[1] ?? '', '/\\');
if (!is_file($root . '/wp-load.php')) {
    fwrite(STDERR, 'Pass the path to an installed WordPress instance.' . PHP_EOL);
    exit(1);
}
require_once $root . '/wp-includes/plugin.php';
add_filter('option_active_plugins', static function ($plugins) {
    return array_values(array_diff($plugins, array('vonseo/vonseo.php')));
});
require $root . '/wp-load.php';
global $wp_version;
require_once dirname(__DIR__) . '/includes/class-vonseowp-frontend.php';
require_once ABSPATH . WPINC . '/class-phpass.php';
wp_set_current_user(0);
$_COOKIE = array();
$settings = array('home_desc' => 'Public site description', 'default_image' => '', 'schema_org_type' => 'Organization');
$meta = array();
$assertions = 0;
add_filter('pre_option_vonseowp_settings', static function () { return $GLOBALS['settings']; });
add_filter('get_post_metadata', static function ($value, $id, $key) {
    return array_key_exists($key, $GLOBALS['meta']) ? array($GLOBALS['meta'][$key]) : $value;
}, 10, 3);
$post = new WP_Post((object)array(
    'ID' => 999999, 'post_type' => 'post', 'post_status' => 'publish', 'filter' => 'raw',
    'post_title' => 'Public protected title', 'post_author' => 1, 'post_name' => 'privacy-fixture',
    'post_content' => '<p>PrivateBodySentinel is password protected.</p>', 'post_password' => 'fixture-password',
    'post_date' => '2026-10-02 12:00:00', 'post_date_gmt' => '2026-10-02 04:00:00',
    'post_modified' => '2026-10-02 12:00:00', 'post_modified_gmt' => '2026-10-02 04:00:00',
));
$wp_query = new WP_Query();
$wp_query->is_singular = true;
$wp_query->post = $post;
$wp_query->posts = array($post);
$wp_query->queried_object = $post;
$wp_query->queried_object_id = $post->ID;
$frontend = new VonSEOWP_Frontend();
$hasher = new PasswordHash(8, true);
$cookies = array('absent' => null, 'wrong' => $hasher->HashPassword('wrong-password'), 'correct' => $hasher->HashPassword('fixture-password'));
function vonseowp_privacy_check(bool $condition, string $message): void {
    $GLOBALS['assertions']++;
    if (!$condition) throw new RuntimeException($message);
}
function vonseowp_privacy_head() {
    unset($GLOBALS['vonseowp_meta_tags_done'], $GLOBALS['vonseowp_json_ld_done']);
    wp_cache_set($GLOBALS['post']->ID, $GLOBALS['post'], 'posts');
    ob_start();
    $GLOBALS['frontend']->output_meta_tags();
    $GLOBALS['frontend']->output_json_ld();
    return ob_get_clean();
}
foreach (array('post', 'page') as $post_type) {
    $post->post_type = $post_type;
    $wp_query->is_single = $post_type === 'post';
    $wp_query->is_page = $post_type === 'page';
    foreach (array(0, 1) as $og) {
        $settings['enable_og'] = $og;
        foreach (array('body', 'excerpt', 'custom') as $description) {
            $post->post_excerpt = $description === 'excerpt' ? 'PrivateExcerptSentinel' : '';
            $meta = array(
                '_vonseowp_title' => 'PrivateTitleSentinel', '_vonseowp_social_title' => 'PrivateSocialTitleSentinel',
                '_vonseowp_social_desc' => 'PrivateSocialDescriptionSentinel', '_vonseowp_keywords' => 'PrivateKeywordsSentinel',
                '_vonseowp_image' => 'https://example.test/PrivateImageSentinel.jpg', '_vonseowp_noindex' => '1',
                '_vonseowp_faq' => array(array('q' => 'PrivateQuestionSentinel', 'a' => 'PrivateAnswerSentinel')),
            );
            if ($description === 'custom') $meta['_vonseowp_description'] = 'PrivateDescriptionSentinel';
            foreach (array('', 'PrivateVideoDescriptionSentinel') as $video_description) {
                $meta['_vonseowp_video'] = array('url' => 'https://example.test/PrivateVideoSentinel', 'name' => 'PrivateVideoNameSentinel', 'desc' => $video_description);
                foreach ($cookies as $cookie_state => $cookie) {
                    $_COOKIE = $cookie === null ? array() : array('wp-postpass_' . COOKIEHASH => $cookie);
                    $locked = $cookie_state !== 'correct';
                    vonseowp_privacy_check(post_password_required($post) === $locked, 'Native password-cookie state is incorrect.');
                    $head = vonseowp_privacy_head();
                    vonseowp_privacy_check(strpos($head, '<link rel="canonical"') !== false, 'Canonical must remain available on locked pages.');
                    vonseowp_privacy_check(strpos($head, '"@type":"Organization"') !== false && strpos($head, '"@type":"WebSite"') !== false, 'Public site schema must remain.');
                    if ($locked) {
                        vonseowp_privacy_check(strpos($head, 'Private') === false, 'Protected body or metadata leaked: ' . $description . '/' . $cookie_state);
                        vonseowp_privacy_check($frontend->filter_document_title('Public title') === 'Public title', 'Protected custom title leaked.');
                        vonseowp_privacy_check(strpos($head, '"@type":"FAQPage"') === false && strpos($head, '"@type":"VideoObject"') === false, 'Protected schema nodes must be omitted.');
                    } else {
                        vonseowp_privacy_check(strpos($head, 'PrivateAnswerSentinel') !== false && strpos($head, 'PrivateVideoSentinel') !== false, 'Correct password must restore FAQ/video.');
                        vonseowp_privacy_check($frontend->filter_document_title('Public title') === 'PrivateTitleSentinel', 'Correct password must restore custom title.');
                        $expected = array('body' => 'PrivateBodySentinel', 'excerpt' => 'PrivateExcerptSentinel', 'custom' => 'PrivateDescriptionSentinel')[$description];
                        vonseowp_privacy_check(strpos($head, $expected) !== false, 'Correct password must restore the normal description path: ' . $description);
                    }
                    vonseowp_privacy_check((strpos($head, 'property="og:description"') !== false) === (bool)$og, 'OpenGraph setting must still work.');
                }
            }
        }
    }
}
$_COOKIE = array();
$post->post_password = '';
vonseowp_privacy_check(strpos(vonseowp_privacy_head(), 'PrivateAnswerSentinel') !== false, 'Ordinary public posts must retain schema.');
$post->post_password = 'fixture-password';
$wp_query->is_singular = false;
$wp_query->is_single = false;
$wp_query->is_page = false;
$head = vonseowp_privacy_head();
vonseowp_privacy_check(strpos($head, 'Private') === false && strpos($head, 'Public site description') !== false, 'Archive output must remain generic.');
$wp_query->is_singular = true;
$wp_query->is_page = true;
add_filter('pre_option_show_on_front', static function () { return 'page'; });
add_filter('pre_option_page_on_front', static function () { return 999999; });
$settings['home_title'] = 'Public homepage title';
vonseowp_privacy_check(is_front_page(), 'Static front-page fixture must match native query.');
vonseowp_privacy_check(strpos(vonseowp_privacy_head(), 'Private') === false, 'Locked static front page must not leak post fields.');
vonseowp_privacy_check($frontend->filter_document_title('Fallback') === 'Public homepage title', 'Deliberately public homepage setting must remain.');
echo 'WordPress ' . $wp_version . ' password privacy tests passed (' . $assertions . ' assertions)' . PHP_EOL;
