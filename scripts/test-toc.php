<?php
declare(strict_types=1);

define('ABSPATH', __DIR__);
$settings = array('enable_toc' => 1, 'toc_min_headings' => 1);
$disabled = '';
$singular = true;
$in_loop = true;
$assertions = 0;
function get_option($name, $default = false) { return $GLOBALS['settings']; }
function get_the_ID() { return 7; }
function get_post_meta($id, $key, $single = false) { return $GLOBALS['disabled']; }
function is_singular() { return $GLOBALS['singular']; }
function in_the_loop() { return $GLOBALS['in_loop']; }
function __(string $text, string $domain = ''): string { return $text; }
function esc_attr(string $text): string { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
function esc_html(string $text): string { return esc_attr($text); }
function wp_unique_id($prefix = '') { static $id = 0; return $prefix . ++$id; }
function doing_filter($hook = null) { return true; }

function add_filter(string $hook, $callback, int $priority = 10, int $accepted_args = 1): bool {
    return true;
}

function add_shortcode(string $tag, $callback): bool {
    return true;
}

function wp_strip_all_tags(string $text): string {
    return strip_tags($text);
}

function sanitize_title(string $title): string {
    return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
}

require_once dirname(__DIR__) . '/includes/class-vonseowp-toc.php';
require_once dirname(__DIR__) . '/includes/class-vonseowp-admin.php';

function check(bool $condition, string $message): void {
    $GLOBALS['assertions']++;
    if (!$condition) throw new RuntimeException($message);
}
function check_targets(string $output): void {
    $document = new DOMDocument();
    $errors = libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="UTF-8">' . $output);
    libxml_clear_errors();
    libxml_use_internal_errors($errors);
    $xpath = new DOMXPath($document);
    $decoded_ids = array();
    foreach ($xpath->query('//h1[@id]|//h2[@id]|//h3[@id]|//h4[@id]|//h5[@id]|//h6[@id]') as $heading) {
        if (!($heading instanceof DOMElement)) throw new RuntimeException('Expected a heading element.');
        $decoded_ids[] = $heading->getAttribute('id');
    }
    check(count($decoded_ids) === count(array_unique($decoded_ids)), 'Heading IDs must be unique.');
    foreach ($xpath->query('//div[@class="vonseo-toc-container"]//a') as $link) {
        if (!($link instanceof DOMElement)) throw new RuntimeException('Expected a link element.');
        check(in_array(rawurldecode(substr($link->getAttribute('href'), 1)), $decoded_ids, true), 'Every TOC link must point to a heading ID.');
    }
}

$toc = new VonSEOWP_TOC();
$extract = new ReflectionMethod(VonSEOWP_TOC::class, 'extract_headings');
$extract->setAccessible(true);

$content = <<<'HTML'
<h2>Visible One</h2>
<script>if (a < b) { window.hidden = '<h2>Hidden Script</h2>'; }</script >
<style>.sample::after { content: '<h2>Hidden Style</h2>'; }</style data-test="1">
<pre><h2>Hidden Pre</h2></pre >
<code><h2>Hidden Code</h2></code foo="bar">
<script/><h2>Hidden Self-closing Script</h2></script>
<!-- <h2>Hidden Comment</h2> -->
<textarea><h2>Hidden Textarea</h2></textarea>
<h3>Visible Two</h3>
HTML;

$headings = $extract->invoke($toc, $content);
if (!is_array($headings) || count($headings) !== 2) {
    fwrite(STDERR, 'TOC should extract exactly two visible headings.' . PHP_EOL);
    exit(1);
}

if ($headings[0]['text'] !== 'Visible One' || $headings[1]['text'] !== 'Visible Two') {
    fwrite(STDERR, 'TOC included a heading from an ignored HTML block.' . PHP_EOL);
    exit(1);
}

$output = $toc->inject_toc($content);
check(strpos($output, '<script>if (a < b)') !== false, 'Ignored HTML must remain unchanged.');
check_targets($output);
foreach (array(
    '<h2>Setup</h2><h2>Setup</h2>',
    '<h2 id="custom">Setup</h2><h2>Setup</h2>',
    '<h2><strong>Setup</strong> &amp; <em>Install</em></h2>',
    '<h2 title="a > b">$1 and $0</h2>',
    '<div id="setup"></div><h2>Setup</h2><h2 id="setup-2">Other</h2>',
    '<pre><span id="setup"></span></pre><h2>Setup</h2>',
    '<h2 id="same">First</h2><h2 id="same">Second</h2>',
    '<h2 id="bad id" id="another">Setup</h2><h2 id>Empty</h2>',
    '<h2 data-note="fake id=wrong" ID="Real">Setup</h2>',
    '<h2 id="a%20b">Percent</h2><h2>!!!</h2>',
    '<h2 id="a&amp;b">Entities &amp; Text</h2>',
) as $fixture) {
    $output = $toc->inject_toc($fixture);
    check_targets($output);
    preg_match_all('/<h[1-6]\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/i', $output, $tags);
    foreach ($tags[0] as $tag) {
        $names = preg_replace('/"[^"]*"|\'[^\']*\'/', '""', $tag);
        check(preg_match_all('/(?:\s+|\/)id\s*=/i', $names) === 1, 'Each heading must have exactly one ID attribute.');
    }
}
$output = $toc->inject_toc('<h2 id="custom"><strong>Setup</strong></h2>');
check(strpos($toc->inject_toc('<h2/id="custom">Slash Separator</h2>'), 'href="#custom"') !== false, 'Browser-tolerated slash separators must preserve IDs.');
check(strpos($output, '<h2 id="custom"><strong>Setup</strong></h2>') !== false, 'Valid author IDs and inline markup must remain unchanged.');
check(strpos($output, 'class="vonseo-toc-container"') < strpos($output, '<h2'), 'TOC must precede the first heading.');
check($toc->inject_toc($output) === $output, 'Repeated content filters must not duplicate TOCs.');
$output = $toc->inject_toc('<h2>$1 and $0</h2>');
check(strpos($output, '<h2 id="1-and-0">$1 and $0</h2>') !== false, 'Replacement-like text must stay literal.');
$settings['toc_position'] = 'top';
check(strpos($toc->inject_toc('<p>Intro</p><h2>Setup</h2>'), '<div class="vonseo-toc-container"') === 0, 'Top placement must work.');
unset($settings['toc_position']);
$settings['enable_toc'] = 0;
check($toc->inject_toc('<h2>Setup</h2>') === '<h2>Setup</h2>', 'Automatic TOC off must leave ordinary content unchanged.');
$first = $toc->render_shortcode();
$second = $toc->render_shortcode();
$output = $toc->inject_toc($first . '<h2>Shortcode Heading</h2>' . $second);
check(substr_count($output, 'class="vonseo-toc-container"') === 1, 'Manual shortcodes must work without automatic TOC and must not duplicate TOCs.');
check(strpos($output, '<!-- vonseo-toc-') === false, 'Resolved placeholders must not leak.');
check_targets($output);
check($toc->inject_toc($toc->render_shortcode() . '<p>No headings</p>') === '<p>No headings</p>', 'Empty shortcode TOCs must disappear cleanly.');
$settings['enable_toc'] = 1;
$output = $toc->inject_toc($toc->render_shortcode() . '<h2>Manual and Auto</h2>');
check(substr_count($output, 'class="vonseo-toc-container"') === 1, 'Manual and automatic TOCs must not both render.');
$disabled = '1';
check($toc->inject_toc('<h2>Setup</h2>') === '<h2>Setup</h2>', 'Per-post automatic disable must work.');
$disabled = '';
$singular = false;
check($toc->inject_toc('<h2>Setup</h2>') === '<h2>Setup</h2>', 'Automatic TOCs must not alter archives.');
$singular = true;
$in_loop = false;
check($toc->inject_toc('<h2>Setup</h2>') === '<h2>Setup</h2>', 'Automatic TOCs must not alter content outside the loop.');
$in_loop = true;
$settings['toc_min_headings'] = 0;
check($toc->inject_toc('<p>No headings</p>') === '<p>No headings</p>', 'Legacy minimum zero must never generate an empty TOC.');
check(strpos($toc->inject_toc('<h2>Setup</h2>'), 'vonseo-toc-container') !== false, 'Legacy low minimum must clamp to one.');
$settings['toc_min_headings'] = 25;
check(strpos($toc->inject_toc(str_repeat('<h2>Setup</h2>', 10)), 'vonseo-toc-container') !== false, 'Legacy high minimum must clamp to ten.');
$admin = (new ReflectionClass(VonSEOWP_Admin::class))->newInstanceWithoutConstructor();
foreach (array(-2 => 1, 0 => 1, 5 => 5, 25 => 10) as $input => $expected) {
    check($admin->sanitize_settings(array('toc_min_headings' => $input))['toc_min_headings'] === $expected, 'Saved TOC minimum must clamp to 1-10.');
}
$settings['toc_min_headings'] = 1;
$settings['toc_title'] = '<script>alert(1)</script>"';
$output = $toc->inject_toc('<h2>&lt;img onerror=alert(1)&gt;</h2>');
check(strpos($output, '<script>') === false && strpos($output, '<img onerror') === false, 'TOC titles and heading labels must be escaped.');
check(strpos($output, 'aria-controls="vonseo-toc-list-') !== false && strpos($output, 'data-show-label="[show]"') !== false, 'Toggle must expose an accessible target and translated labels.');
$settings['toc_title'] = 'Contents';
$start = microtime(true);
$output = $toc->inject_toc(str_repeat('<h2>Repeat</h2>', 10000));
check(substr_count($output, '<h2 id="repeat') === 10000, 'All repeated headings must get an anchor.');
check(strpos($output, 'href="#repeat-10000"') !== false, 'Suffix cursors must reach the final unique target.');
check(microtime(true) - $start < 3, 'Repetitive headings must not trigger quadratic anchor searches.');
echo 'TOC tests passed (' . $assertions . ' assertions, fallback HTML API)' . PHP_EOL;
