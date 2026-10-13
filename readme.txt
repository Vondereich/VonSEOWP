=== VonSEO ===
Contributors: vondereich, kurama87
Tags: seo, toc, schema, sitemap, indexnow
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.5.0
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
*   **Privacy as a Feature**: No tracking or telemetry. Editor analysis and Site Audit stay local; optional IndexNow notifications and manual Competitor scans make the outbound requests described below.
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
*   **IndexNow Notifications**: Opt in to notify Bing and Yandex about eligible published updates using native WordPress cron.
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
VonSEO uses native WordPress storage, runs editor analysis in your browser and processes manual Site Audit results in 25-item batches. Actual performance depends on your content, theme, hosting and other plugins.

= Where is the content analyzer? =
Open an enabled post, page or public custom post type editor and use the VonSEO Content Intelligence sidebar/metabox. The analyzer does not run on the public frontend.

= Does the analyzer send my draft content anywhere? =
No. Content analysis runs locally in your browser and does not send draft text to an external API. Optional IndexNow notifications and manual Competitor scans make the requests described in the external-services section below.

= Does VonSEO support custom post types? =
Yes. Enable eligible public custom post types in SEO Content Types. Selected types receive the existing editor tools, metadata, schema, Quick Edit, Site Audit and sitemap coverage. Attachments and non-public types are excluded; posts and pages remain enabled by default.

= Can Site Audit check thousands of posts? =
Yes. Each run checks 25 items, with Previous/Next navigation through all published content in the selected types. It does not load or crawl thousands of posts in one request.

= Does the SEO Title work with older themes? =
VonSEO supports the modern WordPress document-title API and the legacy wp_title() API used by themes such as Twenty Eleven. Legacy themes may still append the site name themselves. Empty overrides keep the native title, and protected content does not expose its saved SEO title until the password is accepted.

= Does VonSEO create custom database tables? =
No. VonSEO uses native WordPress options and post meta so it stays portable, backup-friendly, and easy to remove.

= Does VonSEO include paywalled Pro features? =
No. VonSEO is built as a complete lightweight toolkit without hidden Pro tabs or forced upgrades.

= Can I use VonSEO with the block editor and classic editor? =
Yes. The editor tools are designed to read content from the WordPress editor and include a fallback for classic editor content.

= Does VonSEO generate schema automatically? =
Yes. Supported output includes article variants, WebPage, Product, Product Review, Service, FAQ, Video and Breadcrumbs, according to the saved settings and available data. Incomplete rated Product/Review objects fall back to WebPage rather than inventing ratings. Schema output does not guarantee search-engine rich results.

= Does VonSEO support large sites? =
Yes. The sitemap system supports sitemap indexing and paginates large post sets to avoid one oversized sitemap file.

= Does IndexNow require an external account? =
No external account is required. IndexNow is optional and defaults to off unless enablement was previously saved. Eligible canonical URLs are queued with native WordPress cron and checked again before delivery; drafts, password-protected and noindex content are excluded. Delivery timing depends on cron/site traffic, and notification does not guarantee indexing.

= Can I remove VonSEO cleanly? =
Deactivation keeps your saved data. Deleting the plugin runs cleanup for its stored options and SEO metadata, so take a backup first. Pending IndexNow events are cleared on both deactivation and deletion.

= What are the minimum requirements? =
WordPress 6.0 or later and PHP 7.4 or later. The current regression checks cover PHP 7.4 and 8.3; third-party theme/plugin compatibility should also be tested on your own site.

== External services and public discovery ==

= IndexNow (Bing and Yandex) =
IndexNow is optional and defaults to off when no enablement has been saved. When enabled, eligible published posts/pages and selected public custom post types are queued for notification after 60 seconds using native WordPress cron; delivery can occur later depending on site traffic or cron configuration. Saved site visibility, password, noindex and content-type eligibility are checked again immediately before notification. Repeated pending updates to the same post share one event. Deactivation and uninstall remove queued events.

The server sends the canonical content URL, site hostname, IndexNow verification key and key-file location to https://www.bing.com/indexnow and https://yandex.com/indexnow. Post bodies, draft text and SEO descriptions are not included. The receiving services can also observe the server's IP address and normal HTTP request information. Notification does not guarantee indexing.

Documentation: https://www.indexnow.org/documentation
Terms: https://www.indexnow.org/terms
Microsoft privacy: https://www.microsoft.com/en-us/privacy/privacystatement
Yandex privacy: https://yandex.com/legal/confidential/en/

= Manual Competitor scans =
When an authorized editor requests a Competitor scan, the WordPress server fetches that editor-supplied URL directly, using the VonSEOWP-Bot/1.0 user agent. The destination website sees the requested URL, server IP address and ordinary HTTP request information; its own terms/privacy policy apply. Current editor content is not sent to that website or an AI service. Successful analysis metrics are cached in a local WordPress transient for 12 hours.

= llms.txt =
This is a public, curated text endpoint, not an access-control feature or automatic protected-post export. Publish only material intended for public reading. When search-engine visibility is off, or the feature is disabled, both the rewritten endpoint and its query alias return an uncached 404 with noindex/nofollow instead of curated/default text.

== Changelog ==

= 2.5.0 =
* Added opt-in SEO tools for public custom post types, with post/page defaults and retained unavailable selections.
* Extended editor analysis, metadata, social fields, noindex, Quick Edit, score columns and Site Audit to selected types.
* Fixed sitemap selection/count/pagination, homepage duplication and invalid batch handling.
* Blocked self-redirect loops while preserving safe redirects and valid HTTP-to-HTTPS upgrades.
* Guarded ordinary WordPress identity-query canonical loops without blocking multipage or different-path same-slug CPT redirects.
* Fixed Competitor access for editors with custom CPT capabilities, with post-specific authorization and validated input.
* Made IndexNow explicit opt-in and blocked password, noindex, private-site and unselected content at the outbound boundary.
* Deferred/deduplicated automatic IndexNow work until after metadata saves, rechecking current eligibility before native-cron delivery.
* Suppressed llms.txt on non-public sites, with uncached noindex/nofollow 404 responses for rewrite and query-alias routes.
* Clarified optional external requests and public curated-content boundaries.
* Fixed schema author lookup before the theme Loop, preserving valid Reviews and avoiding unrelated or empty authors.
* Centered Site Audit Previous/Next arrows and matched button widths on desktop and mobile.
* Applied saved SEO titles on legacy wp_title() themes such as Twenty Eleven, preserving native fallbacks and password privacy.

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
