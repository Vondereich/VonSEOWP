# VonSEO 2.5.1 Development QC

Initial date: 2026-10-27. Scope: taxonomy archive SEO, scoped QC, independent senior-level review and user-requested local packaging. Sections record successive checkpoints; later sections supersede earlier pending gates and artifact hashes. The latest homepage-title patch and rebuilt package are recorded below.

## Implementation

- Category/tag defaults and opt-in public custom taxonomies; exclude internal/private/non-queryable types and retain unavailable selections.
- Native term create/edit fields for SEO title, description, canonical, social description and noindex. Use the taxonomy's edit capability plus `edit_term`, a taxonomy-bound nonce, scalar validation, unslashing, sanitization and late escaping. No REST metadata writes or external services were added.
- Apply selected term overrides to modern/legacy titles, canonical, native robots, Open Graph and Twitter. Empty fields preserve native titles and plain-text term descriptions. Custom canonicals apply on page one only; paginated archives retain self-referencing canonicals. Legacy themes own their site-name/page suffix assembly.
- Mixed content/term sitemap batches remain limited to 1,000 URLs. Only nonempty selected terms without noindex or explicit canonical overrides qualify. Count distinct term IDs before pagination, not joined metadata rows. Explicit-canonical terms are conservatively omitted even if an override happens to equal their native URL.
- Uninstall removes only the five new VonSEO term-meta keys, leaving unrelated term metadata intact.

## Independent Review

One user-requested independent senior-level reviewer inspected the full working change, including the new class/tests and the 2.5.1 changelog. The reviewer found a P2 duplicate legacy pagination suffix: the plugin added `- Page 2`, then Twenty Eleven added `| Page 2` again. Fixed the legacy path to return the base SEO title while retaining modern API pagination. The reviewer independently reran portable taxonomy/title/settings checks and a modeled Twenty Eleven assembly regression, accepted the fix and reported no remaining concrete concern in the reviewed scope. This is not an exhaustive repository-wide security scan.

The reviewer identified coverage gaps rather than additional confirmed bugs. Parent-run native tests subsequently covered real create/update hooks, taxonomy-only capabilities, missing/bad/array/wrong-taxonomy nonces, unavailable-type reactivation, feed/404/unselected contexts and actual term uninstall cleanup. Parent-run HTTP tests confirmed real theme assembly, not just a filter return value.

## Verification

| Gate | Result |
| --- | --- |
| Portable PHP | All 12 tasks passed on PHP 7.4.33 and 8.3.30: taxonomies (21), titles (18), settings (227), schema (61), TOC (77), robots (20), content types (13), Competitor permissions (16), redirects (41), indexing (31), columns and Site Audit. |
| Portable Node | Analyzer, frontend metadata, security-source regression and TOC-toggle tests passed. |
| Native taxonomy | 49 assertions passed separately on WordPress 6.0/PHP 7.4.33 and WordPress 7.1.3/PHP 8.3.30, including term-only and mixed sitemap boundaries at 1,000 URLs. |
| Existing native regressions | On each runtime: password privacy 509, TOC/robots 37, CPT/sitemap/redirect 56. These use development source on disposable databases, not a new 2.5.1 ZIP. |
| Term uninstall | 6 assertions passed independently on each runtime, exercising the actual uninstall file and preservation of another plugin's key. |
| Official Plugin Check | 29 static checks on the isolated development copy: zero errors and warnings. An initial input-sanitization warning was corrected with direct per-field sanitization, not suppression. Full runtime/package checks remain release gates. |
| Actual archive HTTP | Twenty Eleven page one and page two both returned 200; one title element each, correct custom/self-referencing canonicals and noindex. Page-two title: `Archive QC SEO | VonSEO isolated QC | Page 2`, with no duplicate suffix. |
| Native editor UI | Real authenticated category edit/save/reload preserved SEO title, description, canonical and noindex. Desktop and 390px mobile screenshots inspected; all new fields fit the viewport. |
| PHP/core/diff | PHP 8.3 syntax check of 26 runtime/focused test files, actual-core modeled canonical regression and Git whitespace check passed. |

## Test Setup Corrections

- The first native count assertion exposed WordPress term `COUNT(*)` counting metadata-join duplicates. The implementation now scopes `COUNT(DISTINCT t.term_id)` to this sitemap count query and removes the temporary filter in `finally`; native count/output boundaries passed afterward.
- The old cached PHP 7.4 executable had expired. Reacquired 7.4.33 from the official Windows PHP archive; failed/missing-runtime attempts are not counted as passes.
- A 1,000-category fixture generator was slow and exhausted the older core's in-memory cache, before the bounded sitemap output. Replaced that setup with 1,000 nonhierarchical tags and periodic fixture-cache flushing. One slow owned generator was stopped; both completed native runs passed. This does not establish a production memory/performance benchmark.
- The current isolated core initially lacked a theme; supplied a local Twenty Eleven copy before browser/theme validation. Earlier setup notices are not attributed to production plugin behavior.

## Evidence And Cleanup

- Visual evidence: `C:/Users/kuram/.codex/visualizations/2026/05/17/019e34c5-dd26-7740-9769-ebf45bca53ba/taxonomy-251-20261027/category-editor.jpg` and `category-editor-mobile.jpg`.
- QC used only marker-owned databases `vonseoqc250_29af7501` and `vonseoqc250_29af7502` under the temporary `vonseo-final-patch-250-29af7501` folder. The existing helper's `250` name is a test namespace, not a release-version claim.
- The temporary loopback server was stopped, the browser tab closed and responsive override reset. Both QC databases/installations and generated authentication/configuration files are removed after checks. Downloaded core/runtime tools may remain cached outside the repository.
- Main `localhost/wordpress` settings, content, installed plugin and theme were not changed. The old 2.5.0 ZIP remains untouched.

## Final Source QC - 2026-10-27

Fresh verification after the Tutorial/privacy additions:

- All 12 registered portable PHP tasks passed again on PHP 7.4.33 and 8.3.30. After the final copy/fixture corrections, settings passed 239 assertions and taxonomies passed 21 assertions again on both runtimes.
- All four registered portable Node tasks passed. These include security-source regressions, not an exhaustive vulnerability scan.
- PHP 8.3 syntax passed for all 56 tracked/new PHP files. PHP 7.4 syntax passed for all 55 executable runtime/test files. The existing editor-only `_wp_stubs.php` uses PHP 8 union types and is intentionally excluded from the PHP 7.4 gate; the release inventory confirms it is not packaged or executed by the plugin.
- JavaScript syntax passed for all 17 tracked/new JS/CJS files. Actual-core modeled canonical regressions and Git whitespace checks passed.
- Intelephense 1.18.5 checked 16 selected runtime/test files: no errors or warnings remain. Hints for unused hook parameters, IDE-only guards and test reflection deprecations remain. The initial pass identified a test fixture constructor mismatch with native `WP_Term`; the fixture now follows the native constructor shape, without diagnostic suppression or runtime changes.
- The privacy source review identified missing disclosures for optional local 404 logs and settings-tab session storage. The policy now explains the 50-URL limit, count/time fields, possible sensitive query strings, absence of IP/user-agent logging, off-versus-delete retention and non-tracking tab preference. Added three help-contract assertions and updated the changelog.
- Reviewed the 2.5.1 changelog against the current diff/new runtime class. Release-builder dry checks found all 51 intended source files and runtime dependencies; metadata remains consistently at 2.5.0 pending the explicit release bump. No archive was written. The existing 2.5.0 ZIP hash remains `B41A68EF440117A62E540B659262B6A954520CB0A4FAB4F847606D9B02381DBA`.

This final pass did not rerun native database/browser/official Plugin Check checks. Their earlier results above apply to the unchanged runtime implementation, not to a newly built archive. The latest policy copy has syntax/source-contract coverage, not a new browser layout check. No main-site changes, commit, push or version bump were performed. No new runtime blocker was found in the reviewed scope.

## Packaging Checkpoint - 2026-10-27

- User requested the 2.5.1 bump and ZIP. Aligned the plugin header/constant, package version, readme stable tag and dated changelog; added the public readme release notes and taxonomy/policy guidance.
- Preserved the verified 2.5.0 ZIP in ignored `backups/release-snapshots/vonseo-v2.5.0.zip` before the standard builder replaced the root artifact.
- Artifact: `D:/vonseowp/vonseo-v2.5.1.zip`, 1,854,290 bytes, SHA-256 `6DC74FFFD6676FFDC6AE25F68FD4F670B512A22E4F6703AB516832A1C7D7DB7A`.
- Builder metadata, dependency and manifest checks passed: 51 files under one `vonseo/` directory, without development scripts/docs/backups. ZIP CRC testing and exact byte parity across all source/staged/archived files passed.
- All 26 packaged PHP files passed syntax checks on PHP 7.4.33 and 8.3.30. All 12 portable PHP tasks passed again on both, and all four portable Node tasks passed. Git whitespace checks passed.
- These are package integrity/source checks, not an installed ZIP, upgrade or packaged runtime Plugin Check result. Main localhost, GitHub and WordPress.org were not changed.

## Remaining Release Gates

Tutorial/policy addendum: the existing Tutorial was updated for the actual content/taxonomy workflow. It now includes a no-guarantee indexing/ranking disclaimer and a factual Privacy & Data Policy, including optional local 404 logs, browser tab preferences, IndexNow, manual Competitor scans, schema-validation URL links, public llms.txt and removal boundaries. The privacy wording describes VonSEO's own behavior, not WordPress core, hosting or third-party tracking. These documentation-only additions receive focused text/syntax checks; the earlier native test counts above are not presented as a rerun after copy changes.

At the initial packaging checkpoint, installed archive byte parity, previous-stable upgrade preservation and official packaged Plugin Check were still pending. Subsequent localhost checks below record their status. Whole-workspace Intelephense, multisite, arbitrary plugin/theme combinations and search-engine indexing outcomes are not certified by this scoped QC.

API references used during implementation: [native taxonomy discovery](https://developer.wordpress.org/reference/functions/get_taxonomies/), [term query parameters](https://developer.wordpress.org/reference/classes/wp_term_query/__construct/) and [native term-update hook](https://developer.wordpress.org/reference/hooks/edited_taxonomy/).

## Localhost And Homepage Patch - 2026-10-28

- Before this patch, the native WordPress upgrader successfully updated the main localhost installation from 2.5.0 to 2.5.1 while preserving settings, activation and all 36 SEO post-meta rows. The official browser Plugin Check completed with no errors; its individual runtime-check inventory was not captured.
- A subsequent guest HTTP smoke test found duplicate homepage branding in Twenty Eleven when the saved homepage SEO title matched the site name. The legacy title filter now preserves the native title in that case only. Distinct overrides, modern titles, singular titles and theme-owned suffixes remain unchanged. Added a failing regression before applying the fix.
- All 12 portable PHP tasks passed on PHP 7.4.33 and 8.3.30 after the patch, including 29 title assertions and 239 settings assertions. All four portable Node tasks passed. Native title tests passed 35 assertions on WordPress 7.1.3/PHP 8.3.30 without database writes.
- Rebuilt artifact: `D:/vonseowp/vonseo-v2.5.1.zip`, 1,854,436 bytes, SHA-256 `E395344DC850F2B21E492626AA6B40005217428DAD92D0C6CA0C9E7685241ECF`. This replaces the earlier 2.5.1 artifact recorded above, without another version bump.
- Builder metadata/dependency/manifest gates and ZIP CRC passed. All 51 source, staging, installed and archive files match byte-for-byte. All 26 packaged PHP files passed syntax checks on PHP 7.4.33 and 8.3.30.
- Installed the rebuilt ZIP through the native upgrader; settings, activation and all 36 SEO post-meta rows remained unchanged. The patched installed copy passed all 29 official static Plugin Check checks with zero errors and warnings. The full browser Plugin Check was not rerun after this three-line runtime patch.
- Normal guest homepage requests returned HTTP 200 with one title element: `Wordpress Alternative | VonCMS | Dah tua pun bodoh`. Verified both a Jetpack Boost cache miss and a subsequent cache hit; the earlier duplicate HTML was cached. The homepage canonical remained singular. The existing article title and canonical were unchanged. Live HTTP used PHP 8.5.12, separately from the CLI/native-test runtime above.
- Changelog/readme release notes include the fix. No new security scan or broad native database/browser regression was run for this narrowly scoped patch. This rebuilt package and source patch have not been committed or pushed to GitHub; WordPress.org publication remains the user's responsibility.

## Alternate-Theme HTTP Smoke - 2026-10-28

- Tested Twenty Eleven 5.2, Twenty Ten 4.7, Twenty Sixteen 3.9 and Twenty Twenty-Five 1.5 on the existing localhost installation. An expiring, loopback/token-gated MU helper selected the actual installed theme for each test request; no persistent theme switch or SEO-setting writes were used. Test responses confirmed the requested stylesheet, preventing cached original-theme HTML from being mistaken for a pass.
- All 24 requests returned HTTP 200 with one title element and one canonical element: homepage, existing article, existing category, homepage page two, distinct homepage title and empty-home-title fallback for each theme. No duplicated site-name branding or visible PHP errors appeared. This confirms the reported duplicate-title fix across legacy, modern classic and block title rendering, not full visual/theme compatibility.
- **Finding, resolved in the following checkpoint: paginated homepage canonical.** All four `/wordpress/page/2/` responses incorrectly pointed their canonical to `http://localhost/wordpress/`, rather than the paginated URL. `get_canonical_url()` returned the homepage/posts-page base before considering `paged`. This pre-existing path was not changed by the three-line title fix. Twenty otherwise successful cases did not clear these four canonical-target failures. Runtime code and ZIP were not changed during that test.
- Modern classic/block page-two titles also omitted the `Page 2` suffix when the homepage override was set; legacy themes appended it themselves. This is an observed convention difference, not a separate confirmed SEO defect: [Google's pagination guidance](https://developers.google.com/search/docs/specialty/ecommerce/pagination-and-incremental-page-loading) permits the same title across a sequence, but explicitly requires each page's own canonical instead of canonicalizing to page one.
- Before/after hashes matched for the original theme, theme mods, widgets, active plugins, VonSEO settings, posts and SEO post/term metadata. Twenty Eleven remained active. Both temporary theme helpers were removed; external HTTP and cron were blocked for the marked test requests. That test left the ZIP unchanged and did not clear the canonical issue; the next checkpoint records its fix.

## Pagination Patch And Rebuilt ZIP - 2026-10-28

- Moved archive pagination handling ahead of the first-page homepage/posts-page canonical fallbacks, while retaining singular permalink precedence. Use the native unescaped pagination URL and escape only when outputting HTML. Homepage and separate blog pages now retain their page number.
- Added a failing regression for the reported homepage canonical before changing runtime code. Native rendered-head tests then reproduced a related loss of `?paged=2` when a canonical host was configured. Preserve the native canonical query string during host remapping, including plain posts-page identity parameters; canonical and Open Graph URLs remain identical. First-page and singular behavior are covered by the scoped tests.
- All 12 portable PHP tasks passed on PHP 7.4.33 and 8.3.30, including 37 title/canonical assertions. All four portable Node tasks passed. Native WordPress 7.1.3/PHP 8.3.30 tests passed 75 assertions, covering existing title/privacy cases plus first/later homepage and separate posts-page canonicals, pretty/plain permalinks, canonical-host aliases and rendered canonical/Open Graph parity. Native fixtures and permalink changes existed only in process memory, without database writes.
- Rebuilt `D:/vonseowp/vonseo-v2.5.1.zip`: 1,854,616 bytes, SHA-256 `D84A9E895B978834425D7B3DB8484D3801B0C397D3F91762FA5C1B082BFF6C87`. This is the current artifact, superseding both earlier 2.5.1 hashes above. Version remains 2.5.1 as requested.
- Builder metadata/dependency/manifest checks and ZIP CRC passed. All 51 source/staged/installed/archive files match byte-for-byte. All 26 packaged PHP files passed syntax checks on PHP 7.4.33 and 8.3.30. The native upgrader installed this ZIP on localhost while preserving settings, activation and all 36 SEO post-meta rows. Official static Plugin Check: 29 checks, zero errors and warnings.
- Repeated actual guest HTTP rendering on Twenty Eleven, Twenty Ten, Twenty Sixteen and Twenty Twenty-Five: all 16 homepage/page-two/article/category cases passed, with exact canonical targets, matching Open Graph URLs, one title/canonical element and no duplicated branding. Page-two requests confirmed the native `paged=2` query. The temporary helper stripped only its own test parameters before WordPress URL generation and did not change saved settings. An ordinary `/wordpress/page/2/` request after helper removal also returned 200 with canonical `http://localhost/wordpress/page/2/`.
- Original theme, theme mods, widgets, active plugins, posts and SEO settings/post/term metadata hashes remained unchanged. Twenty Eleven is active; temporary helpers were removed. Changelog/readme include the pagination fix. No new repository-wide security scan, full browser Plugin Check, multisite or arbitrary-theme certification is claimed. No GitHub commit/push or WordPress.org publication was performed for this rebuilt artifact.
