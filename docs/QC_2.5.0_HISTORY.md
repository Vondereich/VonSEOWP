# VonSEO 2.5.0 QC History

Consolidated on 2026-10-14 from seven dated reports. These are historical checkpoints, not current release approval. Current results and remaining limits are in [the current QC report](QC_2.5.0.md).

Older backup paths below describe their original locations. They are now preserved inside `backups/history-before-20261014.zip`; only 2.4.5 and the latest pre-patch 2.5.0 rollback ZIP remain directly under `backups/release-snapshots/`. The local history archive contains a per-file SHA-256 manifest and is not a plugin install package.

## Development Checkpoint

Original file: `QC_2.5.0_DEV.md`. Original SHA-256: `EE43EA59DD23D5504B6180599981B55FF4F8E34274E49273ECAD4C7E9ACB5062`.

### VonSEO 2.5.0 Development QC

Date: 2026-10-09. Baseline: committed 2.4.5 source (`20ada55`), plus the uncommitted CPT/sitemap/redirect changes in this workspace. Active plugin header/constant, stable tag and package version remain 2.4.5. No release ZIP, commit, push, tag or WordPress.org publication is authorized by this checkpoint.

#### Implemented Scope

- Shared `VonSEOWP_Content_Types` policy, new `seo_post_types` option, public/queryable CPT discovery, post/page defaults, strict new-value allowlist and retained unavailable selections.
- Native settings checkboxes with late escaping, editor-only assets, selected-type metadata/Quick Edit save guards, native `edit_post` capability and nonce checks, score-column registration after CPT initialization.
- Per-content title/description/social/schema/noindex overrides only for enabled types. Existing canonical, site-level metadata/graph and TOC behavior remain independent. CPT opt-in does not imply valid Product/Review/Service schema or WooCommerce integration.
- Shared sitemap count/output query filters, noindex/password exclusion before pagination, stable modified/ID ordering, one homepage slot, static-front-page deduplication and invalid-batch 404s. A static homepage's stored noindex remains a conservative sitemap exclusion even if its type's SEO controls are disabled.
- Selected-type Site Audit, 25 items per request, actual CPT labels and cache invalidation on selection changes. Noindex and password content remain in the authorized audit; they are not exposed through the public sitemap.
- Redirect validation before emission, same-destination rejection and correct request-origin/subdirectory handling; valid HTTP-to-HTTPS and query-changing redirects remain supported.

#### Verified Locally

| Gate | Result / proof boundary |
| --- | --- |
| Minimum install/activation | Existing exact 2.4.5 ZIP installed and activated on official WordPress 6.0 with isolated PHP 7.4.33. This establishes baseline activation, not a new 2.5.0 packaged upgrade. |
| Native development integration | 56 assertions on WP 6.0/PHP 7.4.33 and current WP 7.1.2/PHP 8.3.30: opt-in/defaults, save authorization, editor assets/columns, selected/disabled/protected CPT output, unavailable type restoration, real SQL sitemap counts/index/pagination, audit and redirects. |
| Privacy / TOC / robots | Development source passed 509 protected-content assertions and 37 native TOC/robots assertions on each runtime. |
| Regression suite | All eight original regressions plus 13 new selection assertions passed; PHP regressions also ran under actual PHP 7.4.33. CI includes the new selection test. |
| PHP syntax | All runtime and QC PHP paths passed PHP 7.4.33 lint; IDE-only PHP 8 declaration stubs are not shipped or included at runtime. |
| Real admin and HTTP browser | Authenticated disposable WP admin: settings persist opt-in/opt-out, CPT editor/list tools follow selection, and controls fit 1440/390/320px. HTTP sitemap index/page sizes 1,000 and 1, no duplicated URL, invalid/array/out-of-range pages return 404, self redirect falls through and a valid rule emits 301 plus the correct Location. |
| Rendered admin previews | Native PHP/style previews at 1440/1024/390/320px cover unavailable and long-label types, checkbox interaction and existing Donate layout/focus/reduced-motion states. These previews are separate from the authenticated browser checks. |
| Intelephense | Fifteen PHP paths checked with installed Intelephense 1.18.5: no errors/warnings. IDE-only unused/deprecation/unreachable-stub hints are not runtime failures. |
| Official Plugin Check | 29 static checks passed with 0 errors/warnings. Full official standard static/runtime suite via its early WP-CLI bootstrap also passed with error/warning severity set to 1. No experimental/AI checks were enabled; the two narrow performance annotations below remain explicit reviewed exceptions. |
| Future package manifest | Existing metadata/dependency validation passed without creating a ZIP. The new shared helper is included; development scripts, fixtures, IDE stubs and QC/research docs are excluded. |

Sitemap query exceptions: Plugin Check initially reported the generic `meta_query` and `post__not_in` performance advisories. Both have narrowly scoped source annotations with their rationale: filter the single VonSEO noindex key before pagination and exclude exactly one static front-page ID. These are intentional reviewed exceptions, not evidence that all database costs are constant. One 1,000-entry local warm count took about 0.007s on WP 6.0/PHP 7.4; current WordPress can cache the identical query. No production or 100,000-post benchmark was performed. Offset pagination assumes the collection is not changing between requests; it is not a transactional snapshot.

#### Focused Security Review

- New settings continue through the native Settings API; selection is unslashed, type-checked and allowlisted. Stored unavailable selections do not become active types until a matching eligible registration returns.
- New labels/values use `esc_html`/`esc_attr`; no client HTML injection or remote-service call was added.
- Metadata/Quick Edit paths retain nonce and object-specific capability checks; revisions and unselected types cannot be saved through these tools. Anonymous and invalid-nonce negative controls passed.
- Runtime database selection uses native `WP_Query`, not interpolated SQL. Fixture transactions/deletion are restricted to dedicated QC prefixes and never shipped.
- Native password-cookie gates still guard direct content/meta reads. Redirect destinations still pass WordPress sanitization and host validation before `wp_safe_redirect`.
- This is a focused review of the changed paths plus automated checks, not a new exhaustive repository security audit or production penetration test.

#### Isolation And Remaining Gates

Temporary installations used only `vonseoqc250_25673866_` and `vonseoqc250_9ac3edd1_` tables. The official runtime checker removed its temporary tables/drop-in and preserved the isolated site's settings/metadata/activation hashes. Dedicated cleanup then removed 12 tables from each installation; a final database query confirmed zero remaining tables for both prefixes. Both temporary WordPress roots/configs were deleted after absolute-path validation, and the dedicated loopback server was stopped. Downloaded runtimes and screenshots remain in the temporary QC directory; no credential-bearing WordPress config remains there.

The actual `C:\laragon\www\wordpress` plugin was not replaced. Its before/after hashes match: settings `555a48c7ef11d60f4aa05235bac41c2cf5ac161b491f46d2f1fb66dbe301ecd3`, VonSEO metadata `9db3ae4910d22f58c60341d8aa1ab220a2da43d1618f3c819a187b355153d65c`, activation `502911445024a2104307b6c2856600b72958f0423b50ba935cc447c5c6693708`. No Plugin Check runtime tables or object-cache drop-in exist on the main site. The existing MySQL service was started for local QC without changing its configuration; no mail delivery or external IndexNow behavior is claimed.

Still required before a 2.5.0 release: resolve or explicitly disposition the remaining pre-2.5 roadmap findings, synchronize release metadata, build the exact artifact, verify source/ZIP/installed parity, native previous-version dashboard upgrade with data preservation, and repeat release checks against that artifact. Multisite, production cache/CDN/membership combinations, arbitrary third-party CPT editors, WooCommerce and external IndexNow delivery are unverified. Research proposals are documented, not silently implemented.

## Independent Review Fixes

Original file: `QC_2.5.0_NEMESIS_PATCH.md`. Original SHA-256: `24E690D7B895325AC357D5D3190F5C53AED53B309BDB1C84CE0561D9A8250255`.

### VonSEO 2.5.0 Independent Review Fixes

Date: 2026-10-09. User-approved scope: patch the two independent reviewer findings and rebuild the local 2.5.0 ZIP. No new version, installed-site replacement, database changes, commit, push, tag or publication.

#### Findings And Changes

1. P2, newly exposed CPT compatibility issue: the Competitor AJAX handler required `edit_posts`, despite selected CPT metadata saves using `edit_post($post_id)`. It now validates a scalar decimal post ID, resolves the actual post, checks enabled type and native object-specific edit capability. Nonce, safe HTTP fetch, response cap and cache behavior remain. Missing/malformed/uneditable IDs and disabled types are rejected before cache/network access; malformed URL fields return a controlled error.
2. P2, residual RED-01: `/foo/ -> /foo/?p=101` could bounce through native canonical handling back to `/foo/`. The guard now recognizes ordinary identity-query targets whose native permalink returns to the source path on the same scheme/host/port. It does not mutate query arguments or call canonical logic with fabricated global state in production.

The same independent reviewer caught two regressions in intermediate redirect patches: `page=2` must canonicalize to `/foo/2/`, and a different-path target sharing the source CPT's slug must not be inferred from the source post. The guard was narrowed accordingly. It predicts only one identity parameter, allows only matching optional `post_type`, and skips prediction when other native/registered public query variables are supplied. Name-only prediction is restricted to the same normalized path and resolved source slug.

This is not a general redirect-graph cycle detector or a claim that arbitrary query/filter/hosting combinations are resolved. Existing roadmap schema, settings, scoring and external indexing/data-flow findings remain open.

#### Verification

- Both original focused regression scripts failed on the unpatched source before implementation.
- Competitor permission tests: 16 assertions passed on PHP 7.4.33 and 8.3.30, using modeled capabilities and cached disposable `.invalid` data; zero network calls.
- Redirect tests: 35 assertions passed on both runtimes, covering identity rebound rejection and preservation of ordinary query changes, HTTPS upgrades, different ports, multipage/feed/comment/embed/preview/custom query modifiers and cross-type same-slug targets.
- `scripts/test-redirects-core.php` passed on both runtimes using actual local WordPress `compat.php`, `class-wp.php`, `pluggable.php` and `canonical.php`. It reproduces the original native rebound, verifies valid multipage canonical `/foo/2/`, and verifies a different-path same-slug CPT target. Query/post state is modeled; `wp-load.php`, configuration, database, network and live services are not used.
- The eleven portable regression tasks passed; the seven PHP tasks run on both runtimes, plus four Node tasks. The native-code regression is optional and requires a supplied WordPress source root, so CI does not run it.
- All 25 shipped PHP files and three new QC scripts passed syntax checks on both runtimes.
- Targeted installed WordPress PHPCS security sniffs for nonce verification, validated/sanitized input and output escaping passed on both patched classes. This is not a rerun of all 29 official Plugin Check checks or its runtime suite.

Final independent follow-up: scoped acceptance of both fixes, with no remaining concrete issue found in the two patched classes/three focused test scripts. The reviewer independently reran 16 permission assertions, 35 redirect assertions and the actual-core canonical/multipage/cross-type regressions on both runtimes. This is not a second full-source review or universal loop-prevention sign-off.

Intelephense 1.18.5 checked 12 paths with zero errors/warnings; informational fixture/unused/deprecation hints remain. The rebuilt 50-file ZIP passed CRC, manifest, runtime dependency, duplicate-member and source/staged/ZIP byte-parity checks. SHA-256 and preserved pre-patch archive are recorded in the [snapshot record](PACKAGE_2.5.0_SNAPSHOT.md). Previous native/database/browser/Plugin Check evidence belongs to the earlier development checkpoint, not this exact patched archive.

#### Untested Release Boundaries

Actual custom-role/browser operation, native SQL integration after this patch, HTTP redirect chains, exact-ZIP fresh activation/dashboard upgrade, full official Plugin Check rerun, Multisite, external delivery, production routing/caches and arbitrary third-party canonical filters remain unverified here. The local ZIP is a storage/testing snapshot, not public-release approval.

## Pre-Push Checkpoint

Original file: `QC_2.5.0_PREPUSH.md`. Original SHA-256: `18E53F4B2E9E2D0786C1C03B32FA365E08B03A48115A46B2ABCCD10F9DEB168D`.

### VonSEO 2.5.0 Pre-Push QC

Historical artifact notice: this document records the pre-indexing-patch archive with SHA-256 `67918161ECEF965918DCF470B8FEF1A68B2362A944EF44DA877B8EBA576DB220`. It is superseded for the current root ZIP by the 2026-10-11 [package record](PACKAGE_2.5.0_SNAPSHOT.md) and its retained fix/QC evidence. The indexing findings below were subsequently patched and tested; other findings were not silently closed.

Date: 2026-10-10. Requested scope: double-check before deciding whether to push. No commit, push, tag, publication, production-plugin patch or ZIP rebuild was performed.

#### Verdict

The exact local archive passed the release-artifact, fresh-install, native-upgrade, focused integration, HTTP/browser and official Plugin Check gates below. This is not blanket public-release approval or an exhaustive new repository security scan. Two existing indexing-policy findings remain reproducible and should be fixed or explicitly dispositioned before public publication. The broader schema/settings/comparison findings already recorded in the roadmap remain open.

#### Exact Artifact

- ZIP: `D:\vonseowp\vonseo-v2.5.0.zip`
- Version: 2.5.0; metadata requires WordPress 6.0 / PHP 7.4 and declares Tested up to 7.1.
- Size: 1,842,589 bytes; 50 packaged files under the canonical `vonseo/` folder.
- SHA-256: `67918161ECEF965918DCF470B8FEF1A68B2362A944EF44DA877B8EBA576DB220`
- Final manifest, runtime-dependency, CRC, duplicate-member and metadata checks passed without invoking the builder.
- Every packaged file matched current source, `dist/vonseo/`, and both installed copies byte for byte after native upgrade.
- The preserved 2.4.5 archive in `backups/release-snapshots/` retains SHA-256 `A361DE0C39D06CAF88B6AFF8FA511AB16B66EBBF61C249C3617046C9EB8C2A98`. Its previously recorded root copy is now absent; it was not restored or overwritten in this request.

#### Verified Gates

| Gate | Result / proof boundary |
| --- | --- |
| Portable regression suite | All eleven tasks passed. The seven PHP tasks passed on PHP 7.4.33 and 8.3.30; the four Node tasks cover analyzer, frontend metadata, security source contracts and TOC toggle. These are focused tests, not exhaustive security proof. |
| Syntax | All 41 runtime/QC PHP files passed lint on both PHP versions. All 14 admin/public/QC JavaScript files passed syntax checks. IDE-only declaration stubs are not shipped or runtime-loaded. `git diff --check` passed. |
| Fresh exact-ZIP activation | Native installation and activation passed on isolated WordPress 6.0 / PHP 7.4.33 and WordPress 7.1.3 / PHP 8.3.30. All 50 installed files matched the archive. |
| Previous-stable native upgrade | On each runtime, the exact preserved 2.4.5 archive was installed and seeded with settings plus eight VonSEO metadata rows. `Plugin_Upgrader::bulk_upgrade()` then upgraded to the exact 2.5.0 ZIP. Settings/metadata hashes and activation were unchanged; a fresh bootstrap confirmed active version 2.5.0. This exercises the native upgrader, not a rendered dashboard update click. |
| Actual CPT-only role | Thirteen native assertions passed on each installation: a custom-capability editor with `edit_post` but no `edit_posts` can save its own CPT metadata and use cached Competitor AJAX. Other-owner, disabled-type, anonymous and malformed/missing-ID requests are denied. No outbound Competitor request was made. This is native capability/handler integration, not a custom-role browser journey. |
| Native CPT / sitemap / redirect | Fifty-six native assertions passed on each installation, including opt-in policy, saves/assets/columns, unavailable types, real SQL sitemap selection/counting/pagination, audit and redirect checks. The earlier actual-core modeled canonical regression also passed on PHP 7.4.33. |
| Password privacy / TOC / robots | Each runtime passed 509 protected-content assertions and 37 native TOC/robots assertions. The real HTTP protected-post response also contains the password form without the fixture body/description sentinels in its head or body. |
| Authenticated current-WP browser | Settings persist opt-in/opt-out; CPT editor/analyzer/list tools follow selection. Native controls fit 1440/390/320px. HTTP sitemap batches contain 1,000 and 1 entries with no duplicates; invalid/array/out-of-range requests return 404, self-redirect falls through and a valid rule emits the expected 301. No browser JavaScript errors. |
| Native HTTP canonical chains | Identity-query rebound is suppressed: source returns 200 directly. Multipage and different-path same-slug CPT redirects each retain the plugin 301 and WordPress canonical 301, then finish at 200. Final multipage URL is `/vonseo-qc-articles/multi/2/`; the cross-type destination is `/vonseo-qc-articles/same-slug/`. |
| HTTP sitemap after added fixtures | Two batches contain 1,005 total unique URLs; each batch has at most 1,000 entries. Homepage appears once, protected fixture is absent. This is a fixed local fixture collection, not a transactional snapshot during concurrent publication. |
| Donate / long-label previews | Native PHP/style previews passed at 1440/1024/390/320px: two Donate links, icon/label centering, stable hover, keyboard focus, minimum target height, long translated labels and reduced-motion states. Destination remains unchanged. Representative desktop/mobile controls and Donate screenshots were visually inspected. These previews are separate from authenticated browser testing. |
| Official Plugin Check 2.1.0 | All 29 static checks passed with zero errors/warnings. The complete standard static/runtime CLI run also passed with error and warning severity set to 1. No experimental/AI checks were enabled. Existing narrow sitemap performance annotations remain reviewed exceptions. |
| Checker data preservation | Before/after isolated settings, metadata and active-plugin hashes matched. No temporary checker tables or `object-cache.php` drop-in remained. |

The 1,000-entry sitemap count ran against real local SQL; observed warm counts were about 0.0003s on current WordPress and 0.0072s on the minimum runtime. This is not a production-load, cold-cache or 100,000-post benchmark.

#### Remaining Findings

##### IndexNow Eligibility Policy

`includes/class-vonseowp-indexnow.php:80-92` defaults to enabled when its option is missing and checks published status/revision/autosave only. It does not gate on site search-engine visibility, the VonSEO noindex flag, a post password or the selected content types.

On the isolated exact ZIP, native callback probes for a password post, noindex post, disabled CPT and `blog_public=0` each attempted submission to both endpoints. Every request was intercepted locally; zero requests were sent externally. The submission payload contains URLs, not the protected post body. This is an existing policy/URL-disclosure concern, not evidence that the password-gated HTML fix failed or that a search engine actually accepted/indexed a URL.

##### llms.txt Visibility Policy

`includes/class-vonseowp-llm.php:37-58` serves the enabled file with `X-Robots-Tag: index, follow`. Real HTTP returned 200 and that header while the isolated site had `blog_public=0`.

The default template links home/sitemap and does not enumerate protected bodies. User-curated content is still publicly served by this endpoint. A consistent visibility policy needs a decision and regression coverage; robots directives are not access control.

##### Editor Diagnostics

Installed Intelephense 1.18.5 checked 41 unique PHP paths with diagnostics enabled: four errors, zero warnings and 104 informational hints. All four errors are include-context `P1008` diagnostics in `admin/partials/vonseowp-site-audit-display.php` at lines 55, 71, 93 and 153. `VonSEOWP_Site_Audit::render_page()` supplies both variables before including the partial (`includes/class-vonseowp-site-audit.php:219-236`). These diagnostics do not establish a runtime undefined-variable defect: the caller supplies the include context. Do not describe the broad IDE check as zero diagnostics; optional PHPDoc include-context annotations can address this separately.

##### Other Proof Limits

No new independent full-repository scan was run in this request. The previous independent reviewer accepted the two scoped authorization/canonical fixes; this pass adds exact-ZIP/runtime evidence, not another exhaustive security audit.

Multisite, arbitrary third-party CPT editors, membership/password-cookie caching combinations, production caches/CDNs, WooCommerce schema, real external IndexNow delivery, complex query/filter/hosting canonical behavior and multi-rule redirect cycles remain unverified. Existing Product/Review/Service/Video schema, inert settings and comparison-metric findings in [ROADMAP.md](../ROADMAP.md) are not resolved by these tests.

#### Harness Repairs And Cleanup

Two failures were in QC setup, not patched plugin behavior: the full runtime checker initially lacked its active bootstrap constant, and changing the permalink option directly left in-memory rewrite rules stale. Plugin Check was activated only in the disposable current installation; the corrected full run then passed. The HTTP fixture now uses `WP_Rewrite::set_permalink_structure()`, and native rewrite flushing corrected the isolated route before the successful HTTP rerun. The upgrade helper also avoids redefining an existing `FS_METHOD` constant. These helper changes are excluded from the ZIP.

QC used only dedicated database `vonseoqc250_e66533f7` with prefixes `vonseoqc250_e66533f7_` and `vonseoqc250_b66533f7_`. Cleanup removed 12 tables from each prefix, verified the database was empty, then removed that database. The identified hidden loopback server was stopped; both WordPress roots/configs and browser credentials were removed after absolute-path and reparse-point validation. Runtime archives, diagnostic logs and screenshots remain under `%TEMP%/vonseo-prepush-250-e66533f7/`.

Read-only before/after snapshots of the main `C:\laragon\www\wordpress` installation match: settings/activation hash `78bf717fadf8b628620b5709eee8d82be25f9e483a97c9af2fbba9c1c5006376`, 22 metadata rows/hash `89027c786de65c30cf19cb10e5f0544f62a5fbf106e788d222d0b7e04d8a7c24`, and plugin header-file hash `C987B76A8C2893DA5ABA1B63830BA06BBE5A0E7FDA3A1E659FBF1AAA6A8070E1`. No checker runtime tables or object-cache drop-in exist there. The existing MySQL service was left running and its configuration was not changed. No main-site install/update, database mutation, mail delivery, external URL publication, GitHub write or WordPress.org publication was performed.

## Final Patch Checkpoint

Original file: `QC_2.5.0_FINAL_PATCH.md`. Original SHA-256: `47A6A2E3420FEE618B5F34AF2EA10787AB1CFBF54A50274BAF57B75810AFCD53`.

### VonSEO 2.5.0 Final Audit Patch QC

Date: 2026-10-13 (Asia/Kuala_Lumpur).

Later main-site smoke found a head-time author-context failure on the active classic theme. A subsequent narrow author patch resolves the actual post author explicitly and has now passed before-Loop native tests plus real Review/Article HTTP checks on Graceful Pixel Blog. See the [dated author patch addendum](QC_2.5.0_HISTORY.md#main-smoke-checkpoint) for the current ZIP/hash, fresh installation, static checker and cleanup evidence. Full functional smoke remains incomplete; there is no blanket release approval.

Historical scope of the checks below: fix the three confirmed final-audit release blockers, add regressions, rebuild the same local 2.5.0 snapshot, and test only disposable WordPress installations. No version bump or Git publication. The subsequent user-authorized main-site installation and author patch are recorded separately in the addendum, not folded into these earlier test counts.

#### Fixed Contracts

- Checkbox values: accept only integer/string `1` or boolean `true`; missing, stored `0`, false and malformed values remain OFF. Apply this to all 13 settings flags, not just IndexNow. WordPress first-option double sanitization and registered Site Title/Tagline sync are now tested.
- Schema: Article/NewsArticle/BlogPosting/WebPage, Product, Product Review and Service no longer share one article-shaped object. Product uses the post title as its name and requires a 1-5 rating plus a positive integer count; Review requires a named reviewed product, rating and real author and emits `itemReviewed`/`reviewRating`. Service uses `name`/`provider`. Other types do not acquire aggregate ratings. Missing/invalid rated data falls back to WebPage while preserving saved choices/metadata. No offers, prices, item names or rating counts are invented.
- Editor: add the reviewed-product field with the existing nonce/capability save boundary; show rating count only for Product and the reviewed-product field only for Review. Remove the automatic count of 1 and add the default schema choice. Review here is explicitly Product Review, not arbitrary reviewed item types.
- Redirect: coherent same-resource `p` plus `name` is checked for canonical rebound. Conflicting/malformed identities, cross-resource targets and registered pagination/feed modifiers are not overblocked. This is not a general multi-rule cycle detector.

The Product/Review field contracts were checked against [Google Product requirements](https://developers.google.com/search/docs/appearance/structured-data/product-snippet), [Google Review requirements](https://developers.google.com/search/docs/appearance/structured-data/review-snippet), and [Schema.org Service](https://schema.org/Service). Eligibility is not a promise of rich results or ranking.

#### Verification

| Gate | Evidence |
| --- | --- |
| Red before fix | New settings test reproduced OFF-to-ON, schema test reproduced missing Product name, and redirect test reproduced `p` + `name` rebound on the pre-patch source. An initial schema fixture callback-type error was corrected before the real failure was reproduced. |
| Portable regressions | Ten PHP suites plus actual-core canonical modeled-state regression passed on PHP 7.4.33 and 8.3.30; four Node suites passed. New settings: 227 assertions; schema: 38; redirect: 41. |
| Native exact-ZIP fresh installation | WordPress 6.0/PHP 7.4.33 and WordPress 7.1.3/PHP 8.3.30 installed/activated the rebuilt ZIP. Separate nonce-named databases and copied core/theme files only. |
| Native new regressions | 54 assertions on each runtime, including the real registered sanitizer's first-option double pass, OFF/ON preservation through core sync, no IndexNow queue/notification after OFF sync, nonce/capability-protected Review saves, type-specific JSON, and incomplete legacy-data fallback. |
| Native previous-stable upgrade | `Plugin_Upgrader::bulk_upgrade()` upgraded the preserved 2.4.5 ZIP to the rebuilt 2.5.0 ZIP on both runtimes. Settings, metadata and active-plugin hashes stayed unchanged. Current/minimum snapshots contained 26/14 metadata rows respectively; both were checked individually. A fresh bootstrap repeated all 54 new assertions. |
| Existing native regressions | Each runtime passed password privacy 509, TOC/robots 37, CPT/sitemap/redirect 56, and IndexNow/actual cron/deactivation/uninstall 29 assertions. Real external IndexNow requests: zero. |
| Browser/HTTP | Current runtime: source and `p`+`name`/`name` entry paths terminate without a redirect cycle. Authenticated editor saves the reviewed-product name and frontend JSON contains that exact name with `reviewRating`. Review/Product/Article controls behave correctly; no page JS errors. Screenshots at 1440/390/320px were checked, including input/container bounds and desktop/mobile visual inspection. |
| Official Plugin Check | Current exact-ZIP installation: 29 static checks, 0 errors, 0 warnings. The full runtime checker was not rerun in this patch session. |
| Syntax | 46 runtime/QC PHP paths initially passed lint on both PHP versions; 15 JS/CJS paths passed before the added browser fixture. Final syntax/diff checks include the added fixtures. |
| Intelephense | Targeted original seven paths plus the eleven modified/new PHP paths: no errors/warnings. Existing broad Site Audit include-context diagnostics are not closed by this targeted result. |
| Package | 50 unique release files, no dev scripts/docs/stubs, aligned 2.5.0 metadata, and every archive byte hash matches source, staged output and both installed plugin copies. |

#### Test Setup Corrections

Failures in QC preparation were not labeled passing: the portable PHP 7.4 runtime initially lacked mysqli, the installer used a duplicate non-once admin include, the native rerun reused an unchanged title (so no update hook fired), and the legacy CPT test tried to change a constant-pinned home URL. These were corrected in dev-only helpers/fixtures and the affected checks were rerun. The CPT fixture removes the config home filter only inside its guarded disposable process so its deliberate origin/subfolder variations use the real option API. No production filters or main-site config were changed. WordPress update attempts were intercepted by the offline MU fixture; their offline warnings are not plugin failures.

#### Artifact And Remaining Limits

- ZIP: `D:/vonseowp/vonseo-v2.5.0.zip`, 1,845,574 bytes, 50 release files.
- SHA-256: `B25E23D882E9CA737EB217949C367E6DCA603EC8D053F5C893C8DE138BCEB0DE`.
- Previous ZIP preserved as `backups/release-snapshots/vonseo-v2.5.0-pre-final-patch-20261013-1A9FDE1D.zip`; its hash remains `1A9FDE1D3C8B3837747CEAA8269FFFB5CD59B5F944D649E4E214B56282B9D5BA`.
- These three blockers are fixed within the above scope. This is not a new exhaustive repository security audit or blanket roadmap closure. Video URL semantics, WooCommerce schema coordination, unrelated settings/scoring findings, four broad include-context IDE diagnostics, multisite/third-party compatibility and real external indexing remain outside this patch.
- Do not use a tracked-files-only commit: the required content-types module and new dev tests remain untracked until deliberately added. No commit or push was performed.

#### Cleanup

The identified hidden loopback PHP server was stopped. Both nonce-named QC databases (`vonseoqc250_00e22e3d` and `vonseoqc250_9c0b4e5a`) were removed after checking ownership and every table prefix; a native schema query confirmed neither remains. Both temporary WordPress roots/configs/generated browser credentials were removed after absolute-path/reparse-point checks. Port 8766 is no longer listening. Screenshots, source archive and non-secret server logs remain in the temporary `vonseo-final-patch-250-00e22e3d` evidence folder. The main localhost WordPress plugin/configuration/database was not modified or bootstrapped by these QC commands.

Final syntax checks cover 47 PHP paths on both versions and 16 JS/CJS paths. The root ZIP and preserved pre-patch ZIP hashes remained unchanged after QC; no files are staged.

## Local Install Checkpoint

Original file: `LOCAL_INSTALL_2026-10-13.md`. Original SHA-256: `34FAAD0ED53D6D4A083AA701EF856E1329FF91689896205D846E6F76D5A7D01B`.

### Local WordPress Installation

Date: 2026-10-13 (Asia/Kuala_Lumpur).

The user explicitly requested installation on `http://localhost/wordpress/` after the isolated patch QC. This is separate from the earlier QC session that did not modify the main installation.

- Target: `C:/laragon/www/wordpress/wp-content/plugins/vonseo/`; absolute path and reparse-point boundaries checked before replacement.
- ZIP: `D:/vonseowp/vonseo-v2.5.0.zip`; SHA-256 `B25E23D882E9CA737EB217949C367E6DCA603EC8D053F5C893C8DE138BCEB0DE`.
- The previously installed version header was already 2.5.0, but seven files differed from this patched ZIP.
- Backup: `D:/vonseowp/backups/localhost-wordpress/vonseo-before-install-20261013-2c513c26.zip`; 50 files; SHA-256 `3C4FB482EE3059C090B13AD5C0BF9F486F58E251E610F054376BF09FC9CBFAE8`.
- Installed using native `Plugin_Upgrader::bulk_upgrade()` with the local package only; VonSEO remains active at version 2.5.0. A temporary CLI prepend suppressed network requests during the upgrade and final data snapshot; that helper was removed afterward. No main configuration edits, test-content insertion, uninstall or settings reset.
- Settings, all 22 VonSEO metadata rows and active-plugin state were preserved by the upgrader. Before/after state hashes also match; no checker tables or object-cache drop-in appeared.
- All 50 installed files byte-match the exact ZIP, with no additional installed files.
- Homepage returned HTTP 200 and VonSEO output, with no fatal-error page. `/wp-admin/` reaches the expected login screen and returns HTTP 200. This is a smoke check, not a new authenticated main-site feature audit.
- No Git staging, commit, push, tag or public publication.

## Main Smoke Checkpoint

Original file: `SMOKE_LOCAL_2026-10-13.md`. Original SHA-256: `190F74DC22E911104F25852D73636583A0C3DBECE16FD348BE1007D4F364BFF4`.

### Main Localhost Functional Smoke

Date: 2026-10-13. Target: `http://localhost/wordpress/`; actual browser PHP reports 8.4.22 / WordPress 7.1.3.

**Current verdict: author failure patched and revalidated below; full functional smoke remains incomplete, so no blanket release approval.**

Historical smoke checkpoint: HOLD. Review failed on the active Graceful Pixel Blog theme. No source fix or new ZIP was made during that original smoke session.

#### Confirmed Failure

The valid temporary Review stored a reviewed-product name, rating 4.5 and count 3 through the editor. Actual `wp_head` JSON emitted WebPage with an empty Person name and `/author/` URL instead of Review. `build_content_schema()` reads `get_the_author()` and `get_the_author_meta('ID')` before the theme's Loop has established author globals (frontend lines 421 and 458). The post's own author ID should be used, as the separate author meta tag already does. Earlier modeled/native tests explicitly established Loop data and the other theme's browser test did not expose this context gap. Those passing tests do not clear this failure.

#### Completed Checks

- All eleven settings tabs open. Site Title/Tagline, verification fields and Local SEO values save through the real admin form. IndexNow remains OFF after title/tagline sync.
- Classic editor metadata/social fields persist. Media picker selects an existing image without modifying that media record; Facebook/Twitter previews reflect entered values. Frontend canonical, description, OG title/image and custom document title match the saved fields.
- All three local generators produce values; analyzer displays a numeric score. Quality issues remain: shortcode text appears in keyword suggestions; this same fixture counts 35 words in analyzer versus 28 in Competitor UI. Existing metric-alignment backlog is not closed.
- Product/Review controls switch correctly; rating count is shown only for Product and reviewed-product name only for Review. FAQ add/remove/save and FAQPage rendering work. VideoObject renders, but the known shared contentUrl/embedUrl limitation remains.
- Competitor AJAX displays metrics from an explicitly controlled local response/cache. This is not live external scraping proof. No page console errors were captured at that checkpoint.
- Native IndexNow checks pass queue/canonical payload, protected/noindex/draft denial and OFF denial; two requests were intercepted and zero were sent. Image SEO generates missing attributes and preserves existing ones.
- Breadcrumb shortcode appears in actual frontend output. The initial TOC count used an incorrect selector; it is not a confirmed TOC failure or a completed toggle test.
- Gutenberg, page/CPT editor save, Quick Edit, live audit pagination, full endpoint/toggle/feed/redirect matrix and full runtime Plugin Check were not completed in this session. Do not describe all functions as smoke-passed.

#### Homepage Metadata Question

Raw canonical/og:url values are plain `http://localhost/wordpress/`, not Markdown links. Description/title reproduce the configured homepage values. Site icon ID is 0 and no default social image is set, explaining absent OG/Twitter image tags and summary card. A robots tag exists elsewhere in the head, outside the VonSEO comment block.

While the temporary identity was saved, anonymous plain homepage returned old identity and the cache-bypassing request returned the new identity. This is consistent with cached HTML; the cache layer was not conclusively identified and no cache was purged.

#### Restoration

All 33 owned fixtures and their revisions were removed. The temporary CPT/network guard and fixture competitor cache were removed. Original settings, title/tagline, visibility, rewrite rules, audit transient and 404 log were restored. Original SEO metadata, settings, active-plugin state and wp-config hashes match; native query confirms zero remaining owned smoke posts. Existing media, credentials, plugin source and ZIP were not changed. No commit/push/publication.

Current ZIP remains SHA-256 `B25E23D882E9CA737EB217949C367E6DCA603EC8D053F5C893C8DE138BCEB0DE`. A private restoration snapshot remains in the approved temporary folder, not in the release package. The browser tab was closed/unavailable at final screenshot capture; no replacement user tab was opened.

Captured Competitor screenshot: `C:/Users/kuram/.codex/visualizations/2026/05/17/019e34c5-dd26-7740-9769-ebf45bca53ba/vonseo-smoke-20261013/competitor.jpg`.

#### Author Patch Addendum - 2026-10-13

- Scope: explicit post-author lookup in `build_content_schema()`, empty-Person omission, targeted regressions, same-version ZIP rebuild and authorized main localhost installation. No settings redesign or Git publication.
- Red evidence: the portable new before-Loop test failed with valid Review becoming WebPage; an anonymous HTTP request to one new owned noindex fixture independently reproduced WebPage on the old installed ZIP and Graceful Pixel Blog.
- Green evidence: portable schema suite passes 61 assertions on PHP 7.4.33 and 8.3.30, covering absent/unrelated Loop authors, true missing author IDs and the existing schema/privacy cases. Native main-site checks pass 10 pre-Loop assertions across Review/Article/NewsArticle/BlogPosting/WebPage and two Loop states. Real anonymous theme HTTP requests separately pass Review and Article, with the correct Person name/archive and Review item/rating.
- Exact rebuilt ZIP fresh-installs/activates on isolated WordPress 7.1.3 / PHP 8.3.30. Registered settings/schema regressions pass 64 assertions, including the new head-time cases; native privacy passes 509 and TOC/robots passes 37. These are current-runtime native passes, not a new dual-runtime native installation claim.
- Ten portable PHP suites and the actual-core modeled canonical test pass on both PHP runtimes; four existing Node suites pass. All 25 packaged PHP files and four changed/new PHP QC files pass syntax on both runtimes. Targeted Intelephense reports no errors/warnings; existing informational hints and broader include-context findings are not closed.
- Official Plugin Check on the installed patched ZIP: 29 static checks, zero errors/warnings. Full runtime Plugin Check and the incomplete functional cases above were not rerun.
- Current ZIP: `D:/vonseowp/vonseo-v2.5.0.zip`, 1,845,780 bytes, 50 files, SHA-256 `31B3BC0C6C3F07207F4E29B03D19BC59A8FDAEDD4A5C52630A296A256C7E8B5B`. Every member matches source, `dist/vonseo` and the main installed plugin byte for byte. Old ZIP retained as `backups/release-snapshots/vonseo-v2.5.0-pre-author-20261013-B25E23D8.zip`; main pre-patch plugin retained as `backups/localhost-wordpress/vonseo-pre-author-20261013-4cd170ab.zip`.
- Cleanup: owned fixture ID 387 and its revisions/IndexNow event were removed, temporary network guard removed, and original settings/identity/SEO metadata/activation/post-count snapshots match. Main wp-config hash remains unchanged. Isolated nonce database `vonseoqc250_4cd170ab` and its temporary root/generated credentials were removed after ownership/path checks. No existing accounts/media/theme/settings were edited, and no commit/push was made.

The earlier failure record is retained deliberately. This targeted fix does not convert the unfinished all-function smoke into a pass or promise indexing/rich results.

## Final Checkpoint Before Legacy Title Patch

Original file: `QC_2.5.0_FINAL_2026-10-14.md`. Original SHA-256: `A9E995E26918DAE68A2F799660BD478DE5C46640CB4B0E710655AF32469A7A0C`.

### VonSEO 2.5.0 Final Local QC

Date: 2026-10-14. Scope: final revalidation, requested pagination alignment and generated-file cleanup. No commit, push, tag or publication.

#### Open Finding

**P2: Legacy-theme SEO Title compatibility.** On Twenty Eleven, the saved title `Final QC Quick Edit metadata persistence check` appeared in `og:title`, but the browser title remained the original post title plus site name. `includes/class-vonseowp-frontend.php` hooks `pre_get_document_title`; Twenty Eleven's `header.php` instead calls `wp_title()` and manually appends site text. The existing 2.4.5 baseline uses the same modern-only hook. This is a confirmed compatibility gap, not an introduced 2.5.0 regression. Not patched in this cosmetic task; recorded in the roadmap. Passing Plugin Check does not detect this behavior.

Evidence: `C:/Users/kuram/.codex/visualizations/2026/05/17/019e34c5-dd26-7740-9769-ebf45bca53ba/final-check-20261014/legacy-title-evidence.json`.

#### Completed Checks

- Pagination: native button CSS previously gave a 16px icon a 30.4px glyph line-height. Scoped icon/pseudo-element rules now enforce 16px; both controls measure 126px wide and icon-center delta is zero at 390px and 1440px layout widths. Mobile screenshot is rendered proof; the desktop screenshot is clipped by the app pane, so full-wide desktop visual coverage is not claimed. Live batches 1 -> 2 -> 3 -> 2 and disabled boundary controls passed.
- Portable regressions: columns, audit, TOC (77), robots (20), content types (13), Competitor permissions (16), redirects (41), indexing (31), settings (227), schema (61), and the actual-core/modeled-state canonical regression passed on PHP 7.4.33 and 8.3.30. Analyzer, frontend metadata, security-audit and TOC-toggle Node tests passed. The focused audit test passed again after the CSS patch.
- Isolated native WordPress: official WP 7.1.3/PHP 8.3.30 and WP 6.0/PHP 7.4.33 passed settings/schema (64), privacy (509), TOC/robots (37), CPT/sitemap/redirect (56) and IndexNow/cron/deactivation/uninstall (29) checks. Fresh activation and native 2.4.5 upgrade of the preceding `31B3BC0C...` ZIP preserved settings, metadata and activation. Both installations then upgraded to final `C44F9D38...` and repeated 64 settings/schema assertions; the final snapshot changes only CSS/release notes. No external IndexNow requests were sent.
- Main installed UI: Gutenberg draft save/reload/publish, classic Page/CPT saves, Quick Edit save/reopen, Audit batches and TOC hide/show passed using owned fixtures. Anonymous metadata/canonical/schema-author, noindex, password privacy, sitemap, robots, RSS, curated llms and redirect HTTP checks passed. These checks do not establish universal third-party theme/editor compatibility.
- Native current-runtime llms: 12 real HTTP responses covered public/private/OFF/default modes and literal/scalar/array routes.
- Official main-site Plugin Check: all five categories and error/warning types selected, AI analysis off; completed with no errors or warning rows. Evidence: `final-check-20261014/plugin-check-final.jpg` in the visualization directory above.
- Package: 50 unique release files, every member byte-matches source, staging and all three installed copies before isolated cleanup; all 25 packaged PHP files linted on PHP 7.4/8.3. Final source/staging/main parity was checked again immediately before deleting generated staging.

#### Test Corrections

- The first isolated IndexNow run lacked the test CPT registration. Installed the existing QC registration fixture on both disposable sites and repeated the failed check successfully; no runtime patch was needed.
- Main `/sitemap.xml` returns one canonical 301 to `/sitemap.xml/`, followed by valid XML 200. This is not a loop; the initial direct-200 assertion was corrected.
- The main redirect uses a relative Location header that resolves correctly to the article. The assertion was corrected to resolve it rather than requiring an absolute header.

#### Cleanup And Preservation

- Removed all 33 owned main-site fixtures/revisions. Zero owner markers remain. All nine snapshotted options, original VonSEO settings/metadata hashes and `wp-config.php` hash match their originals.
- Active-plugin hash differs from the snapshot. Removing any single current plugin, with or without sorting, did not reconstruct it, so the cause is not established. Current activation order/list and current Twenty Eleven theme were left untouched; activation preservation is not claimed for the whole concurrent smoke session.
- Removed the three content-verified, owned main MU guards, including outbound-request blocking and temporary CSS cache bypass. No normal plugin/theme files were deleted.
- Stopped owned QC server PID 5964; removed both explicitly disposable databases (`vonseoqc250_728bdfe1`, `vonseoqc250_946bef82`, 12 tables each) and their installation roots. Private snapshots/logs remain outside the repo.
- Removed generated `dist/` (50 duplicate build files) and `scripts/__pycache__/` (one generated file) only after verifying no tracked files, no reparse points and archive parity. Current ZIP, rollback backups, source, developer tests/fixtures and dated evidence are retained. `backups/` is excluded from Git to keep localhost snapshots out of a future commit.

#### Artifact And Verdict

- ZIP: `D:/vonseowp/vonseo-v2.5.0.zip`; 1,845,916 bytes; SHA-256 `C44F9D38429023D9921FD3D09731FC8A7D63779068F6FC1A81D707588C80C374`.
- Previous ZIP: `backups/release-snapshots/vonseo-v2.5.0-pre-pagination-20261014-31B3BC0C.zip`.
- Scoped checks pass, but the legacy-title finding and earlier roadmap backlog remain open. No blanket all-source/all-function public-release sign-off. Real external indexing, multisite, WooCommerce coordination, arbitrary cache/membership/CPT integrations and full broad IDE revalidation are not proved by this run.
- The new runtime content-types class is still untracked along with required new tests/docs. Do not use `git commit -am` as the only future staging step.
