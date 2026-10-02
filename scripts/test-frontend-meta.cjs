const assert = require('assert');
const fs = require('fs');
const path = require('path');

const frontendPath = path.join(__dirname, '..', 'includes', 'class-vonseowp-frontend.php');
const source = fs.readFileSync(frontendPath, 'utf8');
const metaboxPath = path.join(__dirname, '..', 'admin', 'js', 'vonseowp-metabox.js');
const metaboxSource = fs.readFileSync(metaboxPath, 'utf8');
const sitemapPath = path.join(__dirname, '..', 'includes', 'class-vonseowp-sitemap.php');
const sitemapSource = fs.readFileSync(sitemapPath, 'utf8');

assert.ok(source.includes('!post_password_required($post)'), 'Post metadata must respect the native password-cookie state');
assert.equal((source.match(/if \(\$this->can_output_post_metadata\(\$post\)\)/g) || []).length, 4, 'Title, meta, social and article schema must share the visibility guard');

assert.ok(
  !source.includes('Premium SEO'),
  'frontend meta output should not expose the old Premium SEO comment',
);

assert.ok(
  !source.includes('meta name="generator"'),
  'frontend meta output should not expose a generator/version tag',
);

assert.ok(
  source.includes('<!-- Optimized by VonSEO -->') && source.includes('<!-- /VonSEO -->'),
  'frontend meta output should include clean VonSEO branding comments without a version',
);

assert.ok(
  source.includes('get_site_icon_url(512)'),
  'frontend meta output should fall back to the WordPress site icon for social image tags',
);

assert.ok(
  source.includes("remove_action('wp_head', 'rel_canonical')"),
  'frontend meta output should replace the WordPress core singular canonical instead of duplicating it',
);

assert.ok(
  source.includes("add_rewrite_rule('robots\\.txt$', 'index.php?robots=1', 'top')"),
  'frontend should expose the virtual robots.txt route for subdirectory WordPress installs',
);

assert.ok(
  source.includes("$image && !$image_is_site_icon ? 'summary_large_image' : 'summary'"),
  'site icon fallback should use the square Twitter summary card',
);

assert.ok(
  metaboxSource.includes('truncateAtWordBoundary(result, 160)') &&
    !metaboxSource.includes('result.substring(0, 157)'),
  'generated descriptions should truncate on a word boundary',
);

assert.ok(
  source.includes('get_content_fallback_description') && source.includes("$max_length = 160"),
  'frontend content fallbacks should produce a bounded plain-text description',
);

assert.ok(
  source.includes("'Disallow: ' . $prefix . '/wp-admin/'") &&
    source.includes("'Sitemap: ' . home_url('/sitemap.xml')"),
  'default robots output should honor subdirectory paths and advertise the VonSEO sitemap',
);

assert.ok(
  sitemapSource.includes("add_filter('wp_sitemaps_enabled'") &&
    sitemapSource.includes('return $vonseo_enabled ? false : $enabled;'),
  'WordPress core sitemaps should be disabled only while the VonSEO sitemap is enabled',
);

console.log('Frontend meta tests passed');
