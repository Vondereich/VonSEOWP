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

## v2.5.x - Publisher Coverage and Portability (Planned)

Series goal: extend the proven post/page SEO workflow to publisher content structures without turning VonSEO into a heavy all-purpose suite.

### Series-Wide Release Gates

- [ ] Preserve existing `post` and `page` behavior and stored metadata across every upgrade.
- [ ] Run the full official Plugin Check against each packaged artifact, including readme and compatibility metadata checks.
- [ ] Pass focused regressions, PHP lint, package integrity, fresh activation, previous-version upgrade, and localhost frontend smoke before release.
- [ ] Keep every release local-first: no custom database tables, telemetry, background crawler, or required external service.

### v2.5.0 - Custom Post Type Foundation

Goal: safely extend the existing editor and technical SEO workflow to selected public custom post types.

- [ ] Discover eligible public custom post types while excluding attachments and internal/non-public types.
- [ ] Add an opt-in settings control, with `post` and `page` enabled by default for backward compatibility.
- [ ] Enable VonSEO metadata, social fields, schema, noindex, editor analysis, and admin score tools only for selected types.
- [ ] Include selected types in XML sitemap generation and Site Audit batches while excluding noindex content.
- [ ] Handle removed or deactivated post types without warnings, stale output, or settings loss.
- [ ] Add regression fixtures for selection, save behavior, frontend output, sitemap inclusion, audit coverage, and upgrade defaults.

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

## Parking Lot

These are outside the v2.5.x scope and need separate evidence and planning after the publisher series stabilizes.

- [ ] Additional niche schema variants such as ScholarlyArticle.
- [ ] Internal link suggestions based on current post content and existing titles.
- [ ] Smart image ALT suggestions using local filename/title/context only.

## Avoid for Now

These create bloat, support burden, or privacy concerns that do not fit the current product direction.

- [ ] Google Search Console dashboard integration.
- [ ] SaaS connectivity or VonSEO Cloud sync.
- [ ] Competitor rank tracking.
- [ ] Bulk content rewriting.
- [ ] Required external AI generation.
