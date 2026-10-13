# VonSEO Lean Roadmap

VonSEO should stay a lightweight WordPress SEO toolkit: local-first, privacy-safe, and useful inside normal publishing workflows. New features must earn their footprint.

## Product Guardrails
- No telemetry, tracking, SaaS lock-in, or required external API.
- No custom database tables unless a feature cannot work safely with `post_meta`, `wp_options`, or transients.
- No heavy background scanners by default; expensive checks must be manual, cached, or limited.
- No AI wording unless the feature is actually AI-powered. Prefer "assistant", "suggestions", or "analysis" for local logic.
- Every admin action must follow WordPress patterns: capability checks, nonces, sanitization, escaping, and prefixed code.

## Completed Baseline

### v2.1.x - Essential Tools
- [x] FAQ Schema (JSON-LD) repeater.
- [x] Advanced robots.txt editor with Pro Rules reset.
- [x] Social media previews for Facebook and Twitter cards.
- [x] Structured data testing links.
- [x] VideoObject schema support.
- [x] Header cleanup for noisy WordPress meta tags.

### v2.2.x - Utility, Redirects, and Hygiene
- [x] Admin Quick Edit SEO columns and inline editing.
- [x] RSS footer protection.
- [x] Attachment URL redirects.
- [x] Breadcrumbs shortcode and theme helper.
- [x] Frontend settings sync for homepage metadata, social defaults, and verification tags.
- [x] Internationalization and JS localization cleanup.
- [x] PHP 7.4 compatibility and security hardening.

### v2.3.0 - v2.3.3 - Intelligence and Release Polish
- [x] Table of Contents generator.
- [x] System Health Monitor.
- [x] Frontend asset layer for public components.
- [x] Duplicate meta output guards.
- [x] Sitemap index and 1,000-post pagination.
- [x] Competitor analysis cache.
- [x] Canonical, description fallback, admin tab persistence, and header polish.

## v2.3.4 - Lean Content Analyzer (Completed)

Goal: improve the existing editor SEO Health panel without adding server load or external dependencies.

- [x] Split analyzer logic into small local JS functions for readability and testability.
- [x] Add keyword density check with a sane warning range, not a rigid ranking promise.
- [x] Add heading structure checks: missing H1/H2, repeated empty headings, and focus keyword presence.
- [x] Add image ALT scan for editor content.
- [x] Add first-paragraph keyword check.
- [x] Add internal/external link presence checks.
- [x] Make score rules explicit so the sidebar score and All Posts score can be aligned later.
- [x] Keep the analyzer lightweight and loaded only on post/page edit screens.

## v2.3.5 - Lean Frontend Meta Output (Completed)

Goal: keep public head markup clean while preserving useful social preview tags.

- [x] Remove public generator/version meta output.
- [x] Remove public wrapper comments around VonSEO meta tags.
- [x] Add WordPress site icon fallback for social image tags.
- [x] Keep site title casing under the owner's WordPress settings instead of mutating it in plugin output.

## v2.3.6 - Score Consistency and Editorial Polish (Completed)

Goal: make existing SEO scoring feel trustworthy across the admin UI.

- [x] Align the All Posts score column with the editor analyzer rules where practical.
- [x] Improve empty-state guidance without adding marketing copy.
- [x] Add clearer warnings for title and description length.
- [x] Keep score calculations local and deterministic.

## v2.4.0 - Local Site Audit Lite (Completed)

Goal: add a manual, privacy-safe audit screen for technical SEO checks that can run on shared hosting.

- [x] Manual scan only; no scheduled crawler by default.
- [x] Check sitemap configuration, robots rules, homepage metadata, site visibility, and permalink health.
- [x] Scan up to 25 recently modified published posts/pages per request with Previous/Next batch navigation for complete coverage.
- [x] Distinguish effective WordPress metadata fallbacks from genuinely missing metadata.
- [x] Report noindex usage, image ALT gaps, and internal/external link observations.
- [x] Cache compact scan results with a 12-hour transient.
- [x] Show actionable findings, not vanity scores.
- [x] Avoid HTTP crawling and external requests.

## v2.4.1 - WordPress.org Compliance Hotfix (Ready for Release)

Goal: clear the remaining WordPress Plugin Check output without changing Site Audit behavior.

- [x] Escape the final formatted `Batch %1$d of %2$d` pagination label in the Site Audit screen.
- [x] Re-run WordPress Plugin Check against the packaged plugin and require zero escaping errors.
- [ ] Publish the fix as a new `2.4.1` release instead of modifying the existing `2.4.0` tag.

## v2.4.3 - HTML Parsing Hardening (Ready for Release)

Goal: clear the CodeQL bad HTML filtering regexp finding without treating analyzer text extraction as an HTML sanitizer.

- [x] Replace script/style filtering regexes with browser DOM parsing and a non-regex fallback tokenizer.
- [x] Cover whitespace and attributes in malformed script/style end tags with focused regression tests.
- [x] Harden equivalent ignored-block parsing in the Table of Contents generator and cover browser-tolerated closing tags.
- [x] Re-run regressions, packaged Plugin Check, CodeQL-pattern verification, native upgrade, browser probe, and localhost smoke.
- [ ] Publish only after the exact `2.4.3` artifact passes every release gate.

## v2.4.x - Verified Correctness Backlog (2026-10-02)

Baseline: VonSEO v2.4.3 source and its existing ZIP. The supplied review was checked against source, isolated PHP/Node probes, official WordPress/Google documentation, and the ZIP manifest. This verifies the findings below; it does not establish the review's numerical security scores or a full security sign-off. Implementation is still pending.

Priority: P1 means broken public behavior or misleading SEO output; P2 means settings/analysis consistency; P3 is optional package hygiene. No emergency security vulnerability was established by this review.

### Confirmed Findings

| ID | Priority | Evidence and consequence | Owning source |
| --- | --- | --- | --- |
| TOC-01 | P1 | PHP probe: repeated `Setup` headings produce two `id="setup"` attributes on the first heading and none on the second. An existing `id="custom"` gets another ID; inline `<strong>` prevents anchor insertion. | `includes/class-vonseowp-toc.php`, `extract_headings()` / `add_anchors_to_content()` |
| TOC-02 | P1 | PHP probe: `[vonseo_toc]` produces `href="#setup"` while `inject_toc()` leaves the heading without an ID when automatic TOC is off. | `includes/class-vonseowp-toc.php`, `render_shortcode()` / `inject_toc()` |
| TOC-03 | P1 | Toggle JS exists in `public/js/vonseowp-public.js`, but frontend CSS/JS enqueue is commented out and the ZIP contains zero `public/` files. The report's claim that no JS exists is incorrect; the delivery/loading failure is confirmed. | `includes/class-vonseowp-frontend.php`, `enqueue_assets()`; `scripts/release.py`, `INCLUDE_DIRS` |
| ROB-01 | P1 | VonSEO removes core `wp_robots` and prints its own fixed directives. It omits embed handling and bypasses other callbacks on the native filter. | `includes/class-vonseowp-frontend.php`, `force_remove_wp_robots()` / `output_meta_tags()` |
| RED-01 | P1 | Source accepts `/foo -> /foo` and redirects matching requests without a same-destination check, creating a 301 loop. | `includes/class-vonseowp-redirects.php`, constructor / `handle_redirects()` |
| SMP-01 | P1 | PHP probe: 1,500 disabled posts plus 100 enabled pages are counted as 1,600 sitemap entries. Counts ignore selected types and noindex; output filters noindex only after pagination. The homepage is also repeated on every page, a redundancy rather than an invalid-XML finding. | `includes/class-vonseowp-sitemap.php`, `get_total_entries()` / `output_xml()` |
| MET-01 | P1 | Same-text probe: UI counts 4 words and reading score 93, PHP counts 14 words and reading score 100. UI drops short words and uses a different readability formula. It also compares the main analyzer score with an unrelated competitor heuristic starting at 50. | `admin/js/vonseowp-metabox.js`, comparison helpers; `includes/class-vonseowp-competitors.php`, metric helpers |
| SCH-01 | P1 | Product/Review/Service reuse an article-shaped object. Product has no `name`; standalone Review has no `itemReviewed` or `reviewRating`. Some ratings are emitted, but that does not complete these type contracts. Service needs appropriate properties without a rich-result promise. | `includes/class-vonseowp-frontend.php`, `output_json_ld()` |
| SCH-02 | P2 | A single video URL is assigned to both `contentUrl` and `embedUrl` without distinguishing a media file from a player URL. | `includes/class-vonseowp-frontend.php`, VideoObject builder |
| SET-01 | P2 | Title separator and Remove Category Base are rendered/saved but have no runtime consumer. Breadcrumb separator is a separate working option. | `includes/class-vonseowp-admin.php`, `sanitize_settings()`; `admin/partials/vonseowp-admin-display.php` |
| SET-02 | P2 | RSS footer UI defaults to ON, while the runtime default is empty/OFF. PHP probe also confirms `toc_min_headings` saves -2 and 25 despite the UI range of 1-10. | `includes/class-vonseowp-rss.php`, `add_rss_footer()`; `includes/class-vonseowp-admin.php`, `sanitize_settings()` |
| MET-02 | P2 | Competitor parsing never checks HTTP 2xx. Missing DOM extensions already prevent module initialization, but the editor still offers an enabled scan control without a dependency state. This is a UI/availability defect, not a proven missing-extension fatal error. | `includes/class-vonseowp-competitors.php`, `analyze_url()`; `vonseo.php`, `init()`; metabox UI |
| PKG-01 | P3 | ZIP contains 47 files, 2,137,209 uncompressed bytes; PNGs account for 1,788,058 bytes. Investigate unused/duplicate marketing assets before excluding any; no website speed regression is established. | `assets/`; `scripts/release.py` |

### v2.4.4 - TOC and WordPress Robots Stability (Implementation / QC)

Goal: fix the public navigation and native robots integration before expanding publisher coverage.

- [x] Give each heading a stable, unique anchor; preserve valid existing IDs and inline markup.
- [x] Make shortcode links resolve with automatic TOC enabled or disabled, without inserting duplicate TOCs.
- [x] Package and conditionally load the existing frontend assets; verify toggle behavior, `aria-expanded`, and translated labels.
- [x] Clamp `toc_min_headings` to 1-10 and cover invalid stored/input values.
- [x] Integrate VonSEO directives through `wp_robots`; retain core embed/site-visibility rules and third-party directives with one robots tag.
- [x] Add regression coverage for duplicate headings, existing IDs, inline markup, shortcode modes, and robots filter coexistence.
- [x] Close the confirmed password-protected post metadata/schema leak with a shared native WordPress visibility check; verify absent/wrong/correct password cookies.
- [x] Remove quadratic duplicate-anchor searching and full-content rewriting; cover repetitive headings and direct theme shortcode rendering.
- [x] Verify the exact package with all 29 static Plugin Checks, browser TOC clicks on desktop/mobile, and native upgrade from v2.4.3 while preserving settings/metadata.
- [ ] Finish isolated fresh-install/activation and full Plugin Check runtime checks before release sign-off.
- [ ] Verify the declared minimum WordPress 6.0/PHP 7.4 runtime; local native tests currently cover WordPress 7.1.2/PHP 8.3 only.

Audit checkpoint (2026-10-02): all 49 executable source/test/build/config paths reviewed. Pre-fix findings: protected-head information disclosure (medium) and repetitive-heading TOC complexity (low, requires publishing permission), both patched and locally re-tested. Native WordPress 7.1.2 privacy regression passed 509 assertions; 10,000-heading fixture improved from 5.15s to 0.27s on local PHP 8.3. Final ZIP passed all 29 static Plugin Checks (0 errors/warnings), 49-file source/ZIP/installed parity, native 2.4.3 -> 2.4.4 upgrade (22 metadata rows and settings preserved), and browser TOC tests at 1440/390/320px. Real anonymous/protected-post HTTP head was clean; correct WordPress password form restored output. All temporary posts were removed. Intelephense 1.18.5 reported no errors/warnings for the seven reported files; IDE-only unused/deprecation hints remain. These are local results, not production penetration-testing, full runtime Plugin Check, fresh-install or minimum-version proof. See [QC evidence](docs/QC_2.4.4.md) for the remaining release gates.

### v2.4.5 - Donate Polish (Packaged / Local QC)

- [x] Simplify both donation buttons and support layouts; align decorative coffee icons, wrap long labels, retain the PayPal destination, and provide visible keyboard-focus and reduced-motion states.
- [x] Keep the main settings area constrained to the mobile viewport; verify both Donate controls at 1440/1024/390/320px using the current source partial and native WordPress APIs/styles.
- [x] Synchronize the plugin header/constant, package version, readme stable tag, and changelog to 2.4.5; re-run all eight existing regressions.
- [x] Build and verify the exact 49-file 2.4.5 ZIP; confirm source/ZIP/installed parity and native 2.4.4 -> 2.4.5 dashboard update without changing settings, metadata or activation.
- [x] Complete the full standard static/runtime Plugin Check suite, including severity 1, with no findings; clean its isolated tables/drop-in and verify main data preservation.
- [x] Install and activate the exact ZIP on isolated fresh WordPress 7.1.2/PHP 8.3; repeat 509 privacy and 37 TOC/robots assertions, then remove the dedicated QC tables/files.
- [x] Repeat installed-ZIP frontend browser checks at 1440/390/320px, including the native protected-post password form/cookie; remove all temporary fixtures.
- [x] Install and activate the existing exact 2.4.5 ZIP on isolated WordPress 6.0/PHP 7.4.33 (2026-10-09). Native privacy/TOC/robots and CPT regressions were then run against development source on that runtime, not the unchanged 2.4.5 ZIP; see the [development QC checkpoint](docs/QC_2.5.0_HISTORY.md#development-checkpoint).

See [2.4.5 package and local QC evidence](docs/QC_2.4.5.md) for the original artifact gates and the dated development checkpoint for subsequent minimum-runtime evidence. Production coverage is not a full release sign-off.

### Follow-Up Sessions Before v2.5.0

- [x] Legacy-theme document title compatibility (2026-10-14): add `wp_title()` support using the existing title/password policy, escaped output and native separator placement. Confirm saved SEO Title in actual Twenty Eleven browser/anonymous HTTP output, modern/native fallbacks and absent/wrong/correct password-cookie cases on WordPress 6.0/PHP 7.4 and WordPress 7.1.3/PHP 8.3. See [current QC](docs/QC_2.5.0.md); unrelated roadmap items remain open.
- [x] Site Audit pagination alignment (2026-10-14): scoped Dashicons/glyph line-height overrides native WordPress button styles; Previous/Next both measure 126px wide with centered 16px arrows. Verified mobile/desktop layout metrics and live batch navigation on the installed ZIP.
- [x] Privacy/indexing policy (2026-10-11): disclose optional IndexNow/Competitor requests; require explicit IndexNow opt-in and fresh public/published/non-password/non-noindex/selected-type eligibility. Defer and deduplicate native cron work, recheck at delivery, and remove queued events on deactivation/uninstall. Gate rewritten/query-alias llms output by feature/site visibility; curated content is deliberately public, not a per-link password/noindex validator or access control.
- [x] Technical SEO source fixes and local native QC: reject self-redirects with slash/root/subfolder variants; align sitemap counts/pagination with selected, indexable content and emit homepage once (RED-01, SMP-01). Packaged-release verification remains a separate gate.
- [ ] Settings: connect the title separator to WordPress title assembly, align RSS defaults, and retire/disable the inactive Category Base control until collision-safe rewrite behavior is implemented (SET-01, SET-02).
- [ ] Competitor comparison: use matching text extraction/word/readability rules, remove or align incomparable score gaps, reject non-2xx responses, and expose missing-DOM state; invalidate old metric caches (MET-01, MET-02).
- [x] Schema type contracts: build distinct Article/Product/Product Review/Service objects, require applicable rating/item/author data, and retain saved fields with WebPage fallback for incomplete rated objects (SCH-01; patched and tested 2026-10-13).
- [x] Head-time author context: resolve the actual post author ID without Loop globals and omit empty Person nodes. Reproduced the old Review fallback on Graceful Pixel Blog, then verified real Review/Article HTTP output and missing/stale Loop regressions after patch (2026-10-13). Full functional smoke remains incomplete; see [main smoke evidence](docs/QC_2.5.0_HISTORY.md#main-smoke-checkpoint).
- [ ] Schema follow-up: separate video-file/player URLs and avoid duplicate WooCommerce schema ahead of v2.6.0 (SCH-02 and commerce coordination).
- [ ] Optional packaging: exclude only assets proven unused in runtime and required distribution documentation (PKG-01).
- [ ] IDE include context: document the Site Audit partial's caller-provided variables to clear four existing Intelephense P1008 diagnostics without suppressing checks; native caller initialization is already present.

### Reference Checks

- [WordPress robots filter](https://developer.wordpress.org/reference/hooks/wp_robots/) and [embed noindex rule](https://developer.wordpress.org/reference/functions/wp_robots_noindex_embeds/).
- [Google Product snippet requirements](https://developers.google.com/search/docs/appearance/structured-data/product-snippet): `name` plus at least one of `review`, `aggregateRating`, or `offers`.
- [Google Review requirements](https://developers.google.com/search/docs/appearance/structured-data/review-snippet) and [Schema.org Service properties](https://schema.org/Service).
- [Google VideoObject URL definitions](https://developers.google.com/search/docs/appearance/structured-data/video): `contentUrl` refers to video bytes; `embedUrl` refers to a player.

## v2.5.x - Publisher Coverage and Portability (Planned)

Series goal: extend the proven post/page SEO workflow to publisher content structures without turning VonSEO into a heavy all-purpose suite.

### Series-Wide Release Gates

- [ ] Preserve existing `post` and `page` behavior and stored metadata across every upgrade.
- [ ] Run the full official Plugin Check against each packaged artifact, including readme and compatibility metadata checks.
- [ ] Pass focused regressions, PHP lint, package integrity, fresh activation, previous-version upgrade, and localhost frontend smoke before release.
- [ ] Keep every release local-first: no custom database tables, telemetry, background crawler, or required external service.

### v2.5.0 - Custom Post Type Foundation (Implemented / Local Exact-ZIP QC)

Goal: safely extend the existing editor and technical SEO workflow to selected public custom post types.

- [x] Discover eligible public/queryable custom post types while excluding attachments and internal/non-public types.
- [x] Add an opt-in settings control, with `post` and `page` enabled by default for backward compatibility.
- [x] Enable per-content metadata, social fields, schema, noindex, editor analysis, Quick Edit and admin score tools only for selected types. Existing site-level metadata/canonical/site graph and standalone TOC behavior remain independent.
- [x] Include selected types in XML sitemap generation and Site Audit batches. Exclude noindex/password content from the public sitemap; retain it in authorized Site Audit so users can review those findings.
- [x] Handle removed or deactivated post types without warnings, per-content overrides or settings loss; invalidate cached audit results when the active selection changes.
- [x] Add regressions for selection, authorized/unauthorized saves, frontend output, sitemap inclusion/count/pagination, audit coverage and old-settings defaults.

Checkpoint 2026-10-09: development source passes native minimum/current-runtime integration and real local admin/HTTP checks. Metadata was subsequently bumped to 2.5.0 for a user-requested local snapshot. This is not an upgrade, publication or full repository security sign-off; remaining follow-up findings and series release gates above still apply. See [development QC evidence](docs/QC_2.5.0_HISTORY.md#development-checkpoint) and the [local snapshot record](docs/PACKAGE_2.5.0_SNAPSHOT.md).

Independent reviewer follow-up (2026-10-09): the 30-file shipped PHP/JS review found CPT-only Competitor authorization failure and a residual identity-query canonical redirect loop. Both were patched with targeted regressions; follow-up probes also prevent multipage and cross-type same-slug overblocking. See [patch QC](docs/QC_2.5.0_HISTORY.md#independent-review-fixes). Complex native query combinations and multi-rule redirect cycles are not claimed solved by this narrow guard; exact-ZIP install/upgrade/HTTP release gates remain open.

Current checkpoint (2026-10-11): scoped IndexNow/llms fixes and the rebuilt local ZIP passed dual-runtime fresh plugin installation, native 2.4.5 upgrade with data preservation, native privacy/CPT/cron regressions, 24 llms HTTP responses, current-runtime browser/redirect/sitemap checks, full standard Plugin Check, and 50-file source/dist/installed parity. The exact artifact is recorded in the [snapshot record](docs/PACKAGE_2.5.0_SNAPSHOT.md). Earlier dated open artifact gates above describe earlier checkpoints, not this new ZIP. Four IDE include-context diagnostics and unrelated settings/schema/comparison findings remain open; this is neither a new full-source security audit nor publication approval. No commit or push.

Final-audit correction and patch (2026-10-13): earlier tests missed stored OFF flags being re-enabled by registered settings sanitization and Site Title/Tagline sync, and a coherent `p` plus `name` canonical cycle. Both are now patched, along with the previously open SCH-01 type contracts. New regressions first reproduced the failures, then passed on both PHP runtimes and actual WordPress option/save/schema APIs. The rebuilt local ZIP passed dual-runtime fresh activation, native 2.4.5 upgrade/data preservation, existing native privacy/TOC/CPT/cron tests, current-runtime authenticated browser/HTTP checks and 29 static Plugin Checks. Full runtime Plugin Check was not repeated in this patch session. See [final patch QC](docs/QC_2.5.0_HISTORY.md#final-patch-checkpoint); unrelated backlog and broad IDE diagnostics remain open. No commit, push or publication.

### v2.5.1 - Taxonomy Archive SEO

Goal: give publishers controlled SEO output for category, tag, and selected public-taxonomy archives.

- [ ] Add opt-in taxonomy selection with category and tag enabled by default.
- [ ] Add term controls for SEO title, description, canonical URL, social description, and noindex.
- [ ] Apply saved term metadata to document titles, canonical, robots, Open Graph, and Twitter output with safe WordPress fallbacks.
- [ ] Include indexable selected taxonomy archives in sitemap coverage and exclude noindex terms.
- [ ] Require taxonomy-specific capabilities, nonces, unslashing, sanitization, and late output escaping.
- [ ] Add regression fixtures for empty fallbacks, pagination canonicals, noindex behavior, sitemap inclusion, and unauthorized saves.

### v2.5.2 - Settings Portability

Goal: make VonSEO site configuration portable without creating a second storage system.

- [ ] Export a versioned JSON document containing supported VonSEO site settings and no credentials or transient data.
- [ ] Validate file type, schema version, field allowlist, and field values before accepting an import.
- [ ] Show an import preview and require explicit confirmation before replacing any saved settings.
- [ ] Apply imports through the existing sanitization contract and preserve the previous settings when validation fails.
- [ ] Document that normal WordPress content export remains responsible for per-post and per-term metadata migration.
- [ ] Add round-trip, malformed-payload, unsupported-version, capability, nonce, and rollback regression coverage.

### v2.5.3 - Reserved Stability Release

Goal: ship only if real-world use of v2.5.0-v2.5.2 reveals a verified regression or compatibility issue.

- [ ] Do not add a feature merely to fill this version number.
- [ ] Limit changes to confirmed compatibility fixes, performance corrections, documentation, and regression coverage.

## v2.6.x - E-Commerce & Content Productivity (Planned)

Series goal: Provide seamless integration with WooCommerce and introduce smart productivity tools for content creators without relying on external APIs.

### v2.6.0 - WooCommerce SEO Integration

Goal: Automatically recognize and map WooCommerce products to appropriate Schema and SEO fields.

- [ ] Map WooCommerce Product data (Price, Availability, SKU) to Schema.org/Product.
- [ ] Inject Product-specific Open Graph tags (og:price:amount, og:price:currency).
- [ ] Integrate seamlessly into the WooCommerce product editor screen without UI conflicts.
- [ ] Add specific regression tests for WooCommerce-active environments.

### v2.6.1 - Smart Alt Text & Internal Linking

Goal: Improve content workflow efficiency using only local processing and existing database content.

- [ ] **Smart Alt Text**: Auto-generate initial alt text suggestions based on post title and filename (with admin review).
- [ ] **Internal Link Suggestions**: Introduce a lightweight, database-only scanner that suggests internal links based on taxonomy overlap and keyword density.
- [ ] **Orphaned Content Evidence**: Extend the same bounded local scan with incoming-link/source counts and manually reviewed suggestions; distinguish saved-content links from theme/navigation links and exclude private/protected content.

## Parking Lot

These are outside the v2.5.x and v2.6.x scope and need separate evidence and planning after the current series stabilizes.

- [ ] Additional niche schema variants such as ScholarlyArticle.
- [ ] Local visibility inspector: explain why a URL is absent from VonSEO's sitemap without claiming to know a search engine's indexing state.
- [ ] Metadata review queue: filtered gaps, CSV export, preview and explicitly selected updates with recovery; no bulk content rewriting.
- [ ] Bounded, opt-in SEO change history and authorized restore using native WordPress storage where compatible.
- [ ] Competitor metadata migration preview with strict field allowlists and conflict handling, separate from 2.5.2 site-settings JSON portability.

These competitor-inspired proposals are not implemented or assigned new release numbers. Sources and tradeoffs: [official competitor research, 2026-10-09](docs/COMPETITOR_RESEARCH_2026-10-09.md).

## Avoid for Now

These create bloat, support burden, or privacy concerns that do not fit the current product direction.

- [ ] Google Search Console dashboard integration.
- [ ] SaaS connectivity or VonSEO Cloud sync.
- [ ] Competitor rank tracking.
- [ ] Bulk content rewriting.
- [ ] Required external AI generation.
