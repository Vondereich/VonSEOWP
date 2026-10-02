=== VonSEO ===
Contributors: vondereich, kurama87
Tags: seo, toc, schema, sitemap, indexnow
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.4.5
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Surgical SEO. Zero Bloat. Born from the high-performance VonCMS ecosystem.

== Description ==

VonSEO isn't just another SEO plugin; it’s a **high-performance publishing engine**. Originally forged within the enterprise-grade **VonCMS** ecosystem, it has been meticulously adapted for WordPress publishers who refuse to compromise on speed, security, and database hygiene.

Stop turning your WordPress dashboard into a heavy SaaS marketing platform. It's time for **Performance-First SEO**.

### Why Choose VonSEO?

Most SEO plugins become increasingly heavy over time, trading performance for oversized dashboards, telemetry, and marketing layers. VonSEO rejects the bloat.

*   **Zero-Waste Architecture**: No custom database tables. We use native WordPress `post_meta` and `wp_options` for maximum speed and compatibility.
*   **Enterprise Heritage**: Built with the same performance DNA as VonCMS.
*   **Privacy as a Feature**: No tracking. No telemetry. No "phoning home." Your data stays on your server.
*   **No Paywalls**: What you see is what you get. No hidden “Pro” tabs or forced upgrades.

### The Professional Toolkit

#### 1. Modern SEO Workspace
A clean, focused editorial workflow designed for speed and usability.
*   **Compact Sidebar Interface**: Optimize content without leaving the editor.
*   **Real-Time Google Preview**: See exactly how your site looks in the wild.
*   **Smart Metadata Assistance**: Faster editorial workflows without the server load.
*   **Competitor SEO Math**: Live metric comparison against any URL.

#### 2. Advanced Technical Engine
*   **Dynamic Sitemap Indexing**: Automated scaling with `sitemap_index.xml` and 1,000-post pagination.
*   **Instant Indexing (IndexNow)**: Instantly notify Bing and Yandex when content is updated.
*   **Surgical JSON-LD Schema**: Pure, optimized structured data (Article, FAQ, Video, Review, Breadcrumbs).
*   **SEO Table of Contents (TOC)**: Lightweight, anchor-based TOC with zero external dependencies.

#### 3. Safety & System Health
*   **System Health Monitor**: Real-time technical diagnostics for your PHP environment and permalinks.
*   **Manual Site SEO Audit**: Local technical and content checks with safe Previous/Next navigation across 25-item batches.
*   **Redirect & Robots Manager**: Lightweight control over site navigation and crawling instructions.
*   **Production-Ready Security**: Strict Nonce verification, Capability checks, and Sanitization at every layer.

== Installation ==

1. Upload the `vonseo` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Access the VonSEO dashboard and save your settings.

== Frequently Asked Questions ==

= Does this plugin slow down my site? =
No. VonSEO is engineered with a lightweight-first architecture using native WordPress storage and minimal frontend overhead.

= Where is the content analyzer? =
Open any Post or Page editor and use the VonSEO Content Intelligence sidebar/metabox. The analyzer does not run on the public frontend.

= Does the analyzer send my draft content anywhere? =
No. The v2.3.4 analyzer runs locally in the editor screen. It checks the current draft content in your browser and does not require an external API.

= Does VonSEO create custom database tables? =
No. VonSEO uses native WordPress options and post meta so it stays portable, backup-friendly, and easy to remove.

= Does VonSEO include paywalled Pro features? =
No. VonSEO is built as a complete lightweight toolkit without hidden Pro tabs or forced upgrades.

= Can I use VonSEO with the block editor and classic editor? =
Yes. The editor tools are designed to read content from the WordPress editor and include a fallback for classic editor content.

= Does VonSEO generate schema automatically? =
Yes. VonSEO can output optimized JSON-LD schema such as Article, FAQ, Video, Review, and Breadcrumbs depending on the saved post settings.

= Does VonSEO support large sites? =
Yes. The sitemap system supports sitemap indexing and paginates large post sets to avoid one oversized sitemap file.

= Does IndexNow require an external account? =
No external account is required. IndexNow uses a site key and notifies supported search engines when enabled.

= Can I remove VonSEO cleanly? =
Yes. VonSEO avoids custom tables and includes uninstall cleanup for its stored options and metadata.

= Is this compatible with PHP 8.2? =
Yes. VonSEO requires PHP 7.4+ and is fully optimized for PHP 8.2/8.3.

== Changelog ==

= 2.4.5 =
*   Improved: Simplified donation styling with aligned coffee icons, readable labels, visible keyboard focus, and responsive placement.
*   Improved: Replaced the decorative donation panel with a compact support section while retaining the existing PayPal destination.

= 2.4.4 =
*   Security: Password-protected posts no longer expose protected descriptions, social fields, FAQ/video schema or custom SEO titles before the correct password is supplied.
*   Fixed: Avoided quadratic work for repeated TOC headings and retained direct theme shortcode rendering with native password protection.
*   Fixed: TOC links now target unique heading IDs, preserving valid existing IDs and inline heading markup.
*   Fixed: Manual TOC shortcodes resolve against rendered content even when automatic TOC is disabled, without duplicate TOCs.
*   Fixed: Packaged and conditionally loaded TOC assets with accessible, translated show/hide controls and corrected dark-mode styles.
*   Fixed: Clamped saved and legacy TOC minimum heading counts to 1-10.
*   Fixed: Integrated robots directives with the native WordPress filter, retaining embed noindex and independent crawler restrictions with one robots tag.
*   Dev: Expanded TOC, toggle, robots, and asset regressions and added them to release checks.

= 2.4.3 =
*   Fixed: Replaced analyzer script/style filtering regexes with browser DOM parsing and a safe non-DOM fallback.
*   Fixed: Hardened Table of Contents parsing against browser-tolerated closing tags in ignored content blocks.
*   Dev: Added regression coverage for malformed script and style end tags accepted by browser HTML parsers.

= 2.4.1 =
*   Fixed: Escaped the final formatted Site Audit batch pagination label for WordPress Coding Standards compliance.
*   Improved: Confirmed compatibility with WordPress 7.1 and updated the Tested up to metadata.
*   Dev: Added regression coverage for the pagination output escaping contract.

= 2.4.0 =
*   New: Manual, local-only Site SEO Audit with safe 25-item batch navigation across all published posts and pages.
*   New: Actionable checks for effective metadata fallbacks, noindex, image ALT text, and internal or external links.
*   Improved: Release builds now verify the canonical `vonseo/` folder, synchronized metadata, required plugin files, and package exclusions.
*   Fixed: Prevented duplicate singular canonicals, restored virtual robots.txt on subdirectory installs, and made generated metadata truncate at word boundaries.
*   Improved: Site icon fallbacks now use the standard square Twitter summary card, while robots.txt and sitemap output stay aligned on fresh installs.
*   Security: Audit runs require administrator capability and a dedicated nonce, with no remote requests or background scanner.

= 2.3.6 =
*   Improved: All Posts SEO score now uses lean metadata rules aligned with the editor analyzer where practical.
*   Improved: Title and description guidance now distinguishes missing, short, long, and near-range values more clearly.
*   Dev: Added regression coverage for deterministic All Posts score behavior.
= 2.3.5 =
*   Improved: Removed the public generator tag and wrapper comments to keep frontend head output lean.
*   Improved: Added a WordPress site icon fallback for social image tags when no post/default image is configured.
*   Improved: Removed fixed Open Graph image dimension tags to avoid inaccurate dimensions on fallback images.
*   Dev: Added frontend meta regression test coverage.

= 2.3.4 =
*   New: Local editor-only content analyzer for keyword density, heading structure, image ALT coverage, first-paragraph keyword usage, and link presence.
*   Improved: SEO Health score now uses explicit deterministic rules while staying lightweight and private.
*   Dev: Added focused analyzer test coverage.

= 2.3.3 =
*   Fixed: Settings pages now return to the active tab after saving instead of falling back to General.
*   Fixed: Subdirectory homepage canonicals no longer duplicate the WordPress base path.
*   Fixed: Empty homepage descriptions now fall back cleanly instead of rendering blank social meta tags.
*   Fixed: Added the missing get_pagenum_link() WordPress stub for static analysis.
*   Improved: Refined the admin header with a cleaner product description and aligned logo/title block.

= 2.3.2 =
*   Fixed: Backported PHP 8 mixed and union type hints to ensure PHP 7.4 compatibility.
*   Fixed: Corrected rewrite rules flush timing to prevent 404 errors on activation/update.
*   Fixed: Robots meta tag now respects site visibility preferences, search results, and 404 pages.
*   Fixed: Added rating_count storage and input field support for dynamic schema ratings.
*   Improved: Relocated security guards to the absolute top of template partials.

= 2.3.1 =
*   New: Sitemap Index system for automated scaling (sitemap_index.xml).
*   New: 12-hour caching for Competitor Analysis scans to improve performance.
*   Fixed: Sitemap pagination query variable conflict with WordPress core.
*   Fixed: Hardcoded ratingCount in Schema graph (now dynamic).
*   Fixed: 404 console errors on frontend due to missing public assets.
*   Improved: Security hardening with proper output escaping (wp_kses_post).
*   Improved: Standardized repository line endings to LF.

= 2.3.0 =
*   New: SEO Table of Contents (TOC) with automatic anchor injection.
*   New: System Health Monitor for real-time SEO diagnostics.
*   New: Admin Quick Edit columns for SEO Title, Description, and Score.
*   Improved: Hardened security posture across all core modules.

= 2.0.0 =
*   NEW: Full Professional Suite release with Sitemaps and Redirection.
