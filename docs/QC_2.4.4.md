# VonSEO 2.4.4 QC Checkpoint

Date: 2026-10-02. No commit, push, tag or publication performed.

Historical pre-2.4.5 checkpoint. Fresh activation and full runtime Plugin Check were subsequently completed against the exact 2.4.5 package; see [the current package QC](QC_2.4.5.md). The minimum-version runtime remains unverified.

## Confirmed Findings And Fixes

- Medium: password-protected published post/page body and FAQ/video fields were exposed in metadata/JSON-LD. A shared native `post_password_required($post)` check now gates title/meta/social/article fields; canonical, public WordPress title and site schema remain.
- Low: repeated heading bases caused quadratic TOC anchor searches and repeated complete-string replacement. Per-base suffix cursors and single-pass reconstruction now preserve unique anchors without that cost. Native fixture: 10,000 headings/100KB, 5.1504s before and 0.2744s after, on local PHP 8.3. This is not a production load/outage test.
- Read-only independent patch review found no surviving privacy bypass or regression in the scoped candidate.

## Passed

- All eight configured regression tasks: analyzer, frontend metadata, security source contracts, columns score, site audit, TOC, robots/assets and toggle.
- TOC fallback test: 77 assertions, including 10,000 repeated headings.
- Native WordPress 7.1.2 TOC/robots test: 37 assertions.
- Native password privacy test: 509 assertions, absent/wrong/correct native cookies; post/page; body/excerpt/custom descriptions; OG disabled/enabled; FAQ/video; site front page/archive/public controls.
- Browser against installed ZIP and actual localhost theme: 1440px, 390px, 320px; all four TOC targets resolve; mouse/keyboard toggles and ARIA state pass; no TOC overflow or JavaScript errors. Screenshots visually inspected; theme button styling no longer overrides the toggle's readable control style.
- Real HTTP protected fixture: anonymous head did not contain body/FAQ/video sentinels; native password form/cookie restored normal schema and TOC.
- Native dashboard bulk upgrade: 2.4.3 -> 2.4.4; settings hash, all 22 original `_vonseowp_*` metadata rows and active-plugin list preserved.
- Official Plugin Check: all 29 static checks, 0 errors and 0 warnings on final installed package. This is not the full runtime check suite.
- PHP 8.3 lint: all 24 packaged PHP files, all PHP test helpers and IDE stub passed; `git diff --check` passed.
- ZIP/source/installed byte parity: all 49 files match, only `vonseo/` root, frontend CSS/JS included, test helpers and IDE configuration excluded.
- Intelephense 1.18.5 actual language-server check: no errors/warnings for frontend, TOC, Site Audit, TOC test, native package helper, privacy test and native TOC/robots test. Informational unused/dummy-parameter/deprecation hints remain; diagnostics were not disabled.
- Temporary test posts 338, 339 and 340 removed after browser tests. Original 22 metadata rows restored.

Final ZIP: `vonseo-v2.4.4.zip`.

SHA-256: `121E6E7DECB2DC22C3A7ED2618CD5099C252276DDDA06B20B584CD243774370B`.

## Remaining Gates

- Isolated clean install/fresh activation.
- Complete official Plugin Check runtime checks (static suite already passed).
- Actual WordPress 6.0/PHP 7.4 minimum-version runtime. Only WordPress 7.1.2/PHP 8.3 native integration was executed locally; fallback-parser tests are not a complete minimum-version deployment.
- Production/membership/cache/CDN/Multisite combinations and external crawler/IndexNow delivery are unverified.

These gaps mean this checkpoint is not a full release sign-off or a claim that every SEO behavior is correct. Existing sitemap/redirect/metrics/schema/settings correctness work remains in the roadmap.

## Local IDE And Theme Notes

The ignored `.vscode/settings.json` indexes the current Laragon WordPress and Plugin Check APIs. It is local-only and not part of Git or the release. `_wp_stubs.php` was extended for portable WordPress property/function hints but remains IDE-only and excluded from the ZIP. DOM tests now narrow nodes to actual DOM elements and declare helper parameter types.

The localhost theme emits duplicate `custom-background-css`, `block-7`, `block-8` and `block-9` IDs. VonSEO heading targets are unique. Theme source was not changed; this is a separate theme integration observation.

Donate button polish is recorded for a follow-up session, preserving its existing destination.

The pre-fix Codex Security source scan covered all 49 executable source/test/build/config paths. Binary PNG contents and non-executable documents were not fully security-audited; WordPress core/other plugins/themes/hosting were outside repository scope.
