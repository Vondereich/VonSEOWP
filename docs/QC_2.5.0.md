# VonSEO 2.5.0 Current QC

Updated: 2026-10-14. This is the current report; seven earlier reports are consolidated in [QC history](QC_2.5.0_HISTORY.md). GitHub source synchronization was requested after these checks; WordPress.org publication remains manual.

## Current Artifact

- ZIP: `D:/vonseowp/vonseo-v2.5.0.zip`; 1,846,815 bytes; 50 release files.
- SHA-256: `B41A68EF440117A62E540B659262B6A954520CB0A4FAB4F847606D9B02381DBA`.
- Version remains 2.5.0. The latest functional change adds the legacy `wp_title` filter; the final public notes/FAQ update changes only CHANGELOG, README and readme. Modern document-title behavior is unchanged.

## Legacy Title Fix

The old plugin only hooked `pre_get_document_title`. Twenty Eleven instead calls `wp_title()` and appends the site name itself. The new filter reuses the existing homepage/selected-content/password policy, escapes legacy output and preserves the separator direction. It does not rewrite theme HTML, remove the theme's site-name suffix or invent SEO titles when the saved field is empty.

Actual Twenty Eleven result: `VonSEO Legacy Title QC & Verified | Wordpress Alternative | VonCMS`; Open Graph uses the saved title without the theme suffix. Browser and anonymous HTTP checks also confirmed the native fallback and absence of the protected SEO-title sentinel. The previously open compatibility finding is closed for this tested theme/core path; universal third-party theme support is not claimed.

## Verification

- Regression-first: the new portable test failed on the missing legacy hook before the patch. All 18 title assertions pass on PHP 7.4.33 and 8.3.30, covering separator placement, entity escaping, empty/home fallback, selected pages/CPTs and locked/unlocked posts.
- Existing portable columns/audit/TOC/robots/content-types/Competitor/redirect/indexing/settings/schema tests passed on both PHP runtimes. Analyzer, frontend-meta, security-audit and TOC-toggle Node regressions passed.
- Real WordPress title/password APIs with modeled posts: 30 assertions passed on WP 6.0/PHP 7.4.33 and WP 7.1.3/PHP 8.3.30. The native title matrix covers post/page/CPT, left/right separators, modern title behavior and absent/wrong/correct password cookies.
- Native privacy: 509 assertions passed independently on each WordPress/PHP combination.
- Current ZIP installed on main localhost using native `Plugin_Upgrader`; original settings, 22 metadata rows and activation were preserved. Actual Twenty Eleven rendering was verified through the browser and three anonymous HTTP 200 responses, with exactly one title element per page.
- The title-patch ZIP was freshly installed and activated on disposable WP 6.0/PHP 7.4.33, then upgraded to the final documentation-only ZIP with data/activation preserved. All 50 final files match source, staging, main and minimum installations; ZIP CRC and unique manifest passed. Byte comparison confirms only the three public documentation files differ from the tested title-patch ZIP.
- Full standard static/runtime Plugin Check completed with all five categories and error/warning types selected on the title-patch runtime: no findings. The final FAQ ZIP independently passed 29 static checks, zero errors/warnings. Runtime PHP/JS/CSS bytes are unchanged; the full UI suite was not repeated after documentation-only edits.
- Developer script syntax: all 30 PHP scripts/fixtures passed on PHP 7.4/8.3; all 12 JavaScript scripts and the Python release builder passed syntax checks. This is not browser execution of every optional script.

Browser/HTTP evidence: `C:/Users/kuram/.codex/visualizations/2026/05/17/019e34c5-dd26-7740-9769-ebf45bca53ba/final-check-20261014/legacy-title-after.json`. Private main fixture/restoration record: `C:/Users/kuram/AppData/Local/Temp/vonseo-title-qc-8f3129c6/state.json`.

## Cleanup

- Removed the three owned main title fixtures; original settings/metadata/activation/configuration hashes and post count match this title-QC phase's baseline. No main theme or account changes.
- Removed the temporary main guard and the owned minimum installation/database (12 tables). Final main Plugin Check tables are absent and no temporary object-cache drop-in remains. Generated staging is removed after parity verification; the final ZIP is retained.
- Kept `.agent/` with all nine workflow files unchanged; removed the empty `.agents/` directory.
- Backups now contain two direct rollback ZIPs (2.4.5 and the verified title-patch 2.5.0 before public-note changes) plus one history archive. All ten archived historical files have individually verified byte hashes and a recovery manifest. No historical backup data was discarded.
- Consolidated seven dated 2.5.0 reports into one history file; current QC and artifact record remain separate. Old report links are redirected to their history sections.
- Kept supported regression/integration scripts for CI/reproducible QC. Removed the completed CSS cache-bypass fixture and moved four session-specific localhost helpers/guards into ignored `.local-qc/`; the 30-file PHP syntax result covers both retained/public and moved/private files. Usage/mutation boundaries are in [scripts guide](../scripts/README.md).

## Limits

Earlier full-function/batch/schema/sitemap/llms/cron checks apply to their dated artifact checkpoints in the history, not a repeated exhaustive scan of this title patch. Earlier concurrent activation drift is preserved in the history and was not force-reverted. Roadmap settings, comparison metrics, video/commerce schema and IDE include-context items remain open. Real external indexing, multisite and arbitrary third-party combinations remain unverified. The scoped title fix is not blanket all-source release certification.
