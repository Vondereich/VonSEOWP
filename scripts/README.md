# Developer Scripts

These files are development tools, not plugin runtime files. The release builder includes only its explicit runtime directories/files; `scripts/`, fixtures, docs, backups and IDE stubs stay out of the install ZIP.

## Build And Portable Tests

- `release.py`: validates release metadata/dependencies and builds `vonseo-v<version>.zip`. It recreates `dist/` and deletes older root `vonseo-v*.zip` files; preserve a needed rollback before running it.
- `test-*.php` without the `wordpress`/`local` prefix: portable modeled-WordPress regressions for columns, audit, TOC, robots, content types, taxonomies, permissions, redirects, indexing, settings, schema and titles. `test-redirects-core.php` additionally requires a WordPress source-root argument but does not bootstrap its database.
- `test-analyzer.cjs`, `test-frontend-meta.cjs`, `test-security-audit.cjs`, `test-toc-toggle.cjs`: portable Node regressions.
- `test-intelephense.cjs`: optional installed-language-server diagnostics; requires its documented runtime/extension paths.

Run registered portable checks from the repository root, for example `npm run test:titles`. The release workflow lists the required portable gates. The source-pattern security regression is not a full security audit.

## Native WordPress QC

- `test-wordpress-privacy.php`, `test-wordpress-toc-robots.php`, `test-wordpress-titles.php`: real WordPress APIs with modeled content and no intended database mutations. Use an isolated installation; a normal WordPress bootstrap can still trigger plugin hooks/cron/network activity without a guard.
- `test-wordpress-cpt.php`, `test-wordpress-final-patch.php`, `test-wordpress-indexing.php`, `test-wordpress-release.php`: state-changing native tests, restricted to owned QC markers/database prefixes. They create content, change settings or exercise activation/uninstall; never point them at a production database.
- `test-wordpress-taxonomies.php`: marker/prefix-restricted taxonomy create/update, permission, archive metadata and mixed sitemap boundary tests. The optional `uninstall` argument also exercises actual term-meta cleanup; use it last on a disposable installation.
- `qc-final-patch-setup.php`: creates or removes only marker-owned, nonce-named disposable installations/databases in the approved temporary directory. Its local database connection is intended for the Laragon QC environment, not deployment.
- `qc-wordpress-package.php`: explicit state/upgrade/check/fixture/cleanup modes. The upgrader preserves existing plugin data, but fixture modes still change content; use an approved local/QC target and retain rollback/restoration evidence.

Example read-only title matrix on an already installed isolated root: `php scripts/test-wordpress-titles.php <QC_WP_ROOT>`.

## Optional Browser And HTTP Checks

`test-cpt-browser.cjs`, `test-donate-browser.cjs`, `test-final-patch-browser.cjs`, `test-toc-browser.cjs` need their documented browser dependencies and prepared fixtures. The `*-http.cjs` scripts verify prepared local responses. Syntax checks do not establish that these optional scenarios ran. Do not send local fixture data to external services.

`fixtures/` holds temporary MU registrations, outbound-request blockers and local routing helpers. They are deliberately retained for reproducible QC, never installed automatically and never shipped. Remove installed copies after testing; do not delete source fixtures that a lifecycle helper still requires.
