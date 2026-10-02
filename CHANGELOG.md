# Changelog

All notable changes to the VonSEOWP plugin will be documented in this file.

## [2.4.5] - 2026-10-02
### Improved
- **Donate Controls**: Use restrained WordPress-style buttons with aligned decorative coffee icons, wrapping labels, 44px minimum height, clear hover/keyboard-focus states, and reduced-motion support.
- **Support Layout**: Simplify the sidebar support block and Tutorial donation section; keep the existing PayPal destination and safe new-tab attributes.

## [2.4.4] - 2026-10-02
### Fixed
- **Protected Post Privacy**: Respect WordPress password-cookie checks for per-post titles, descriptions, social fields, images, and article/FAQ/video schema while retaining public canonical and site-level output.
- **TOC Scaling**: Reuse duplicate-anchor suffix cursors and reconstruct heading tags in one pass; preserve direct theme shortcode rendering without exposing protected headings.
- **TOC Anchors**: Assign unique heading IDs by source position, preserve valid author IDs and inline markup, and avoid collisions with other content IDs.
- **Manual TOC**: Resolve shortcodes after content rendering so automatic-off and shortcode-generated headings work without duplicate TOCs.
- **TOC Controls**: Package and conditionally enqueue frontend CSS/JS, associate each toggle with its list, use translated labels, and scope dark-mode text colors correctly.
- **TOC Settings**: Clamp new and legacy minimum heading values to 1-10.
- **Native Robots**: Use the WordPress `wp_robots` filter instead of replacing its emitter; retain core embed restrictions and independent third-party directives without conflicting index/follow values.

### Dev
- Expanded TOC and crawler-directive regressions, added translated toggle tests and optional native WordPress password-cookie integration tests, and included TOC tests in release CI.
- Release validation now requires both frontend TOC assets.

## [2.4.3] - 2026-09-29
### Fixed
- **Analyzer HTML Parsing**: Replaced script/style filtering regexes with browser DOM parsing and a non-regex fallback tokenizer so malformed end tags cannot leak hidden content into SEO analysis.
- **Table of Contents Parsing**: Replaced ignored-block filtering regexes with a stateful tokenizer so malformed closing tags cannot contribute false headings.

### Dev
- Added regression coverage for whitespace and attributes in browser-tolerated script/style end tags.

## [2.4.1] - 2026-09-20
### Fixed
- **Site Audit Pagination Escaping**: Escaped the final formatted batch label to clear the remaining WordPress Coding Standards output warning without changing pagination behavior.

### Improved
- **WordPress 7.1 Compatibility**: Confirmed the hotfix on WordPress 7.1.1 and updated the plugin compatibility metadata to WordPress 7.1.

### Dev
- Added regression coverage for the Site Audit pagination output escaping contract and release validation for synchronized `Tested up to` headers.

## [2.4.0] - 2026-08-10
### Added
- **Local Site SEO Audit**: Added a manual, local-only audit screen for technical settings and published content processed in safe 25-item batches.
- **Complete Content Coverage**: Added Previous/Next batch navigation so large sites can audit all published posts and pages without one heavy request.
- **Actionable Content Findings**: Reports effective metadata fallbacks, length issues, noindex usage, missing image ALT text, and missing internal or external links without sending content off-site.
- **Audit Regression Coverage**: Added PHP fixtures for metadata fallback, link classification, batch clamping, ranges, and query bounds.

### Improved
- **Release Contract**: Preserved the shipped `vonseo/` plugin directory and added package verification for metadata, top-level folder, main plugin file, and development-file exclusions.
- **Release QC**: GitHub releases now run Node and PHP regression checks plus packaged PHP lint before building the ZIP.
- **Social Card Fallback**: Square WordPress site icons now use the standard Twitter summary card instead of claiming a large landscape image.
- **Sitemap Ownership**: WordPress core sitemaps are disabled only while the VonSEO sitemap is enabled, keeping a single advertised sitemap endpoint.

### Fixed
- **Canonical Deduplication**: Removed the WordPress core singular canonical when VonSEO outputs its canonical tag.
- **Subdirectory Robots Route**: Added the missing virtual `robots.txt` rewrite for WordPress installations hosted below the domain root.
- **Robots and Sitemap Alignment**: Fresh installs now publish subdirectory-aware crawler paths and advertise the active VonSEO sitemap without requiring an initial settings save.
- **Word-Safe Metadata**: Generated titles and descriptions now truncate at word boundaries after focus-keyword injection.
- **Bounded Content Fallbacks**: Automatic content descriptions now decode HTML entities, normalize whitespace, and stay within 160 characters.

### Security
- **Bounded Manual Scan**: Audit runs require `manage_options`, a dedicated nonce, and never use background jobs, external requests, or custom database tables.

## [2.3.6] - 2026-06-28
### Improved
- **Score Consistency**: All Posts SEO score now uses lean metadata rules aligned with the editor analyzer where practical.
- **Editorial Guidance**: Title and description warnings now distinguish missing, short, long, and near-range values more clearly.

### Dev
- Added regression coverage for deterministic All Posts score behavior.
## [2.3.5] - 2026-06-05
### Improved
- **Lean Frontend Meta Output**: Removed the public generator tag and wrapper comments to reduce version exposure and head markup noise.
- **Clean Frontend Branding**: Restored lightweight VonSEO head comments without exposing plugin version metadata.
- **Social Image Fallback**: Uses the WordPress site icon as a fallback social image when no post image, featured image, or default social image is configured.
- **Social Image Accuracy**: Removed fixed Open Graph image dimension tags so fallback icons are not described with incorrect dimensions.
- **Competitor Scan Hardening**: Capped remote competitor responses and only parses HTML-like response content.

### Dev
- Added a focused frontend meta regression test for lean public output and social image fallback.
- Removed the obsolete root activation harness and added a security regression test for repo/package hygiene.

## [2.3.4] - 2026-06-05
### Added
- **Lean Content Analyzer**: Added a local editor-only analyzer core for keyword density, heading structure, image ALT coverage, first-paragraph keyword usage, and link presence.
- **Analyzer Test Coverage**: Added a focused Node test for the lightweight analyzer rules.

### Improved
- **SEO Health Panel**: Replaced the older five-check score with explicit deterministic rules while keeping the analyzer local and loaded only on post/page edit screens.

## [2.3.3] - 2026-05-22
### Fixed
- **Admin Tab Persistence**: Settings pages now return to the active tab after saving instead of falling back to General.
- **Canonical and Description Output**: Fixed subdirectory homepage canonicals and made empty homepage descriptions fall back cleanly instead of rendering blank social meta tags.
- **IDE Support**: Added the missing `get_pagenum_link()` WordPress stub for static analysis.

### Improved
- **Admin Header Polish**: Replaced the Professional badge with a cleaner product description and aligned the logo/title block.

## [2.3.2] - 2026-05-17
### Fixed
- **PHP 7.4 Compatibility**: Backported PHP 8 mixed parameters and union type-hints to standard PHP 7.4-compatible signatures across all modules.
- **Rewrite Flush Timing**: Corrected version-update check and flush_rewrite_rules execution timing by shifting it to a late `init` hook priority (999) to prevent 404 errors on sitemaps and LLM endpoints.
- **Robots Meta Hardening**: Robots tag now respects WordPress' core "Discourage search engines from indexing this site" privacy setting, search results pages (`is_search()`), and 404 pages (`is_404()`).
- **Dynamic Rating Count**: Restored full dynamic rating count functionality for Review/Product schema graphs by adding `rating_count` save pathways in the admin meta box.
- **Security Hardening**: Relocated `ABSPATH` access protection guards to the absolute first line of admin layout templates to enforce strict direct-access restrictions.
- **Release Audit Closure**: Added rating count uninstall cleanup, hardened admin external links with `rel="noopener noreferrer"`, and made the sitemap respect WordPress site visibility privacy.

## [2.3.1] - 2026-05-17
### Added
- **Sitemap Index System**: Automated generation of `sitemap_index.xml` when post count exceeds 1,000 for improved crawling efficiency.
- **Competitor Analysis Cache**: Implemented 12-hour transient caching for URL scans to prevent redundant scraping and improve dashboard performance.
- **Repository Standards**: Added `.gitattributes` to standardize LF line endings across development environments.
- **Local Analyzer Roadmap**: Integrated architecture plans for v2.3.4 (JS) and v2.4 (PHP) into the roadmap.

### Fixed
- **Sitemap Query Conflict**: Renamed pagination parameter to `vonseowp_sitemap_page` to prevent conflicts with WordPress core post queries.
- **Security Hardening**: Applied `wp_kses_post` escaping to the `llms.txt` output layer.
- **Schema Accuracy**: Replaced hardcoded `ratingCount` with dynamic meta-data retrieval.
- **404 Asset Errors**: Resolved browser console errors by disabling non-existent public CSS/JS enqueues.
- **IDE Support**: Expanded `_wp_stubs.php` with missing WordPress core functions and constants.

## [2.3.0] - 2026-05-16
### Added
- **SEO Table of Contents (TOC)**: Automatic TOC generator with anchor injection, shortcode support, and per-post controls.
- **System Health Monitor**: Real-time diagnostic panel for PHP, Permalinks, and required server extensions in the Advanced tab.
- **Frontend Asset Layer**: New lightweight CSS/JS system for professional component styling.
- **Developer Guide**: Comprehensive `docs/DEV_GUIDE.md` for architecture and security standards.
- **Admin Quick Edit**: SEO Title, Description, and Score columns added with inline editing support.
- **RSS Footer Protection**: New setting to prevent content theft via RSS feeds.

### Improved
- **Security Posture**: Completed a full 4-Layer Defense audit (Nonce, Capability, Sanitization, Escaping) across all core modules.
- **IDE Support**: Hardened `_wp_stubs.php` with `WP_Post`/`WP_Query` mocks and implemented an "Unreachable Stub Injection" pattern in admin partials to eliminate false-positive undefined variable/function errors.
- **UI Consistency**: Premium dashboard polish and smooth transitions for TOC.

## [2.2.7] - 2026-05-14

## [2.2.6] - 2026-04-09
### Added
- **Attachment URL Redirects**: Attachment pages can now redirect cleanly to their published parent post, with a safe fallback to the media file URL when no parent is available.
### Fixed
- **Advanced Settings**: The existing `Redirect Attachment URLs` option now performs the expected frontend 301 redirect behavior.
- **IDE/LSP Support**: Expanded the shared WordPress stubs to cover the attachment-related core functions used by the redirect module.
### Maintenance
- **Release Sync**: Aligned plugin, package, documentation, dist, and release artifact metadata for the 2.2.6 release.
## [2.2.5] - 2026-04-09
### Fixed
- **IDE/LSP Support**: Expanded the shared WordPress stubs to eliminate false-positive undefined function diagnostics in the breadcrumbs module and related files.
- **Breadcrumbs Compatibility**: Added support for both `[vonseo_breadcrumbs]` and `[vonseowp_breadcrumbs]` shortcodes.
- **Sitemap Controls**: The generated sitemap now respects the saved enable/posts/pages settings before rendering output.
- **IndexNow Stability**: Hardened legacy admin hook callbacks and key-file path handling for the IndexNow module.
### Maintenance
- **Release Metadata**: Synchronized plugin, package, readme, dist, and release artifact versioning for the 2.2.5 build.
## [2.2.4] - 2026-04-03
### Added
- **IDE Stubs**: Integrated comprehensive WordPress stubs within the main plugin file to resolve IDE "False Positive" errors globally.
### Fixed
- **404 Log Robustness**: Added type-checking to prevent runtime errors when the 404 log is empty.
- **I18n Compliance**: Fixed missing translator comments for dynamic placeholders in meta boxes.
- **Security Audit**: Completed a system-wide audit of escaping and sanitization for all admin settings and frontend meta outputs.
### Maintenance
- **Architectural Hardening**: Reorganized module loading to ensure PHP version and XML/DOM extension requirements are met before class instantiation.
- **Standalone Test Environment**: Restored and enhanced `test_activation.php` for syntax and activation verification.

## [2.2.3] - 2026-03-29
### Added
- **Breadcrumbs Generator**: Added a built-in [vonseo_breadcrumbs] shortcode and vonseo_breadcrumbs() theme helper for rendering on-page breadcrumbs.
- **Admin Settings**: Added a configurable breadcrumb separator in the Advanced tab.
### Fixed
- **Frontend Settings Sync**: Homepage title, homepage description, default social image, and Open Graph toggles now use the saved admin settings.
- **Webmaster Tools**: Google and Bing verification codes now output the correct verification meta tags on the frontend.
### Maintenance & Polish
- **I18n**: Wrapped hardcoded English strings in the Dashboard (Tutorial/FAQ) with `_e()` for 100% translation readiness.
- **Documentation**: Harmonized project docs and developer workflows to use the correct `vonseo.php` filename.
- **Dev Experience**: Added `_wp_stubs.php` for better IDE/LSP and AI support (excluded from production builds).

## [2.2.1] - 2026-03-14
### Added
- **I18n**: Full internationalization support across the dashboard and meta box.
- **JS Localization**: Localized client-side strings and AI recommendations.
- **Security**: Hardened JSON-LD output and AJAX handlers.
- **English standardization**: Translated internal roadmap and release logs.

## [2.2.0] - 2026-03-03
### Added
- **AI & LLM**: Native support for `llms.txt` (AEO) to help AI models parse site content.
- **AI & LLM**: New dedicated admin tab for managing AI crawler settings.
- **UI/UX**: Revamped SEO Title and Meta Description length indicators with smarter, flexible validation ranges and premium gradient progress bars.

## [2.1.9] - 2026-03-03
### Added
- **Security & Performance**: Added native WP Header Cleanup module to remove unnecessary meta tags like `wp_generator`, `rsd_link`, and `wlwmanifest`.

## [2.1.8] - 2026-02-19
### Added
- **Social Previews**: Real-time Facebook & Twitter card previews in the Meta Box.
- **Social Meta Tags**: Dedicated fields for Social Title, Description, and Image with smart fallbacks.
- **Schema Validator**: Added direct link to `validator.schema.org` for instant structured data testing.
- **UI**: Glassmorphism styling for social preview tabs and character counters.

## [2.1.7] - 2026-02-13
### Security
- **Hardening**: Implemented strict, field-specific sanitization for `register_setting` to meet WordPress.org Plugin Review guidelines.
- **Compliance**: Applied `wp_unslash()` to all input fields before sanitization.
- **Refactor**: Replaced generic sanitization loop with explicit field handling in `class-vonseowp-admin.php`.

## [2.1.5] - 2026-01-31
### Added
- **FAQ Schema**: New repeater in Post Meta Box for generating FAQ JSON-LD Rich Snippets.
- **Video Schema**: New VideoObject schema support for better video indexing.
- **Robots.txt Editor**: Advanced admin tab to control crawler access with "Pro Rules" reset functionality.
### Security
- **Hardening**: Completed 100% PHPCS & WordPress Security compliance audit.
- **Compliance**: Improved data sanitization (unslashing/recursive loops) and variable prefixing.
### Fixed
- **UI**: Corrected Robots.txt layout alignment and enhanced code editor styling.

## [2.1.4] - 2026-01-31
### Improved
- **Offline AI**: Implemented Smart Content Parser to generate distinct Title and Descriptions based on HTML structure (H1/H2 vs Paragraphs).
- **Offline AI**: Added metadata filtering to exclude author credits, photo sources, and separator lines from descriptions.
- **Offline AI**: Added automatic Focus Keyword injection into generated Titles and Descriptions if missing.
- **Offline AI**: Enhanced Dashboard suggestions with randomized templates and Site Tagline integration.
- **Offline AI**: Expanded keyword extraction stopword list for better relevance.

## [2.1.3] - 2026-01-31
### Added
- **IndexNow**: Native support for IndexNow (Bing/Yandex) instant indexing without configuration.
- **Fast Crawl**: Auto-ping search engines on post publish/update.

## [2.1.2] - 2026-01-30
### Security
- **Hardening**: Enhanced escaping and sanitization across Frontend, Sitemap, and Competitors modules.
- **Compliance**: Disabled OTA updater for WordPress.org repository approval.
- **Fix**: Added missing `languages` directory.

## [2.1.1] - 2026-01-30
### Fixed
- **OTA System**: Fixed updater instantiation in the main plugin file.
- **OTA System**: Improved version comparison and cleaning for GitHub tags.
### Added
- OTA Update test release.

## [2.1.0] - 2026-01-30
### Added
- **Competitor Analysis Module**: Real-time "SEO Math" comparison against any URL.
- **AI Magic**: Ultra-compact AI suggestions for Titles, Descriptions, and Keywords.
- **Bi-directional Sync**: Site Title & Tagline synchronized with WP General Settings.
- **Support UI Facelift**: Premium Glassmorphism design with Amber/Gold button.
- **Help & Tutorial Tab**: Integrated quick-start guides and setup checklist.
- **Build System**: Automated versioning and ZIP packaging via Python.

### Fixed
- **Security**: Hardened against XSS (Deep deep audit).
- **Security**: Patched SSRF vulnerability in competitor scanner.
- **Security**: Added proper nonce and capability checks to all AJAX actions.
- **UI**: Fixed `line-clamp` CSS compatibility and button centering.

## [2.0.0] - 2026-01-20
- **NEW**: Full Premium Suite release.
- **NEW**: XML Sitemap Generator (`/sitemap.xml`).
- **NEW**: Redirection Manager with 404 Logging.
- **NEW**: Local SEO & Schema markup support.
- **NEW**: Image SEO (Auto ALT/Title tags).
- **NEW**: Content Analysis in Post Editor.

## [1.13.0]
- **FIX**: Adjusted updater download link to correct package failure.

## [1.12.0]
- **NEW**: Added Auto-Updater directly from GitHub releases.
- **FIX**: Minor stability improvements.

## [1.1.0]
- **NEW**: Complete UI Overhaul - introduced premium dashboard design.
- **FIX**: Resolved PHP Warning "Undefined array key 'site_title'".
- **IMPROVED**: Better spacing and typography in admin panels.

## [1.0.0]
- Initial release.
- Ported core logic from VonCMS v1.9.6.
