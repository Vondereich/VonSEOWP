<?php
declare(strict_types=1);

define('ABSPATH', __DIR__);

$vonseowp_test_meta = array();

function get_post_meta(int $post_id, string $key = '', bool $single = false) {
    global $vonseowp_test_meta;
    if (!isset($vonseowp_test_meta[$post_id])) {
        return $single ? '' : array();
    }
    if ($key === '') {
        return $vonseowp_test_meta[$post_id];
    }
    return $vonseowp_test_meta[$post_id][$key] ?? '';
}

function wp_strip_all_tags(string $string, bool $remove_breaks = false): string {
    $string = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $string);
    $string = strip_tags((string) $string);
    return $remove_breaks ? preg_replace('/[\r\n\t ]+/', ' ', $string) : $string;
}

require_once dirname(__DIR__) . '/includes/class-vonseowp-columns.php';

function vonseowp_assert_true(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

function vonseowp_column_score(array $meta): int {
    global $vonseowp_test_meta;

    $post_id = 1001;
    $vonseowp_test_meta[$post_id] = $meta;

    $columns = (new ReflectionClass('VonSEOWP_Columns'))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod('VonSEOWP_Columns', 'calculate_basic_score');
    if (PHP_VERSION_ID < 80100) $method->setAccessible(true);

    return (int) $method->invoke($columns, $post_id);
}

$strong_score = vonseowp_column_score(array(
    '_vonseowp_title' => 'Lightweight SEO checklist for WordPress publishers',
    '_vonseowp_description' => 'A lightweight SEO checklist for WordPress publishers who want fast local content guidance, clean snippets, and practical metadata.',
    '_vonseowp_keywords' => 'lightweight seo',
));

vonseowp_assert_true(
    $strong_score >= 90,
    'Expected strong metadata to score at least 90, got ' . $strong_score
);

$weak_score = vonseowp_column_score(array(
    '_vonseowp_title' => 'Draft',
    '_vonseowp_description' => 'Short.',
    '_vonseowp_keywords' => 'lightweight seo',
));

vonseowp_assert_true(
    $weak_score < 50,
    'Expected weak metadata to score below 50, got ' . $weak_score
);

$missing_keyword_score = vonseowp_column_score(array(
    '_vonseowp_title' => 'Lightweight SEO checklist for WordPress publishers',
    '_vonseowp_description' => 'A lightweight SEO checklist for WordPress publishers who want fast local content guidance, clean snippets, and practical metadata.',
    '_vonseowp_keywords' => '',
));

vonseowp_assert_true(
    $missing_keyword_score === 0,
    'Expected missing focus keyword to match editor waiting score 0, got ' . $missing_keyword_score
);

echo 'Columns score tests passed' . PHP_EOL;

