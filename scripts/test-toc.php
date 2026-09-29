<?php
declare(strict_types=1);

define('ABSPATH', __DIR__);

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

echo 'TOC tests passed' . PHP_EOL;
