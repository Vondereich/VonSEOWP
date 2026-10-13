# VonSEO 2.5.0 Artifact Record

Updated: 2026-10-14. Current results: [QC report](QC_2.5.0.md). Earlier test scopes: [QC history](QC_2.5.0_HISTORY.md). This record is not publication approval.

## Current ZIP

- Path: `D:/vonseowp/vonseo-v2.5.0.zip`.
- Size: 1,846,815 bytes; 50 release files; version 2.5.0.
- SHA-256: `B41A68EF440117A62E540B659262B6A954520CB0A4FAB4F847606D9B02381DBA`.
- Change: legacy `wp_title()` support with existing title/privacy policy and escaped separator-preserving output. Includes the previous Audit pagination alignment.
- Installed by native upgrader on main localhost and minimum QC with settings/metadata/activation preserved. The preceding runtime-identical title-patch ZIP was freshly activated on disposable WordPress 6.0/PHP 7.4.33; title/privacy regressions also passed on WordPress 7.1.3/PHP 8.3.30. Final changes are professional public notes and updated FAQs only.
- ZIP CRC/unique manifest passed; every member matches source, generated staging, main and minimum installed copies. All 25 shipped PHP files linted on PHP 7.4/8.3. Developer scripts/docs/stubs/backups are excluded.
- GitHub source synchronization is separate from package verification. No tag or WordPress.org publication is part of these checks.

## Rollback And History

`backups/` is local-only and ignored by Git. It contains three files:

- `release-snapshots/vonseo-v2.4.5.zip`: last stable rollback, SHA-256 `A361DE0C39D06CAF88B6AFF8FA511AB16B66EBBF61C249C3617046C9EB8C2A98`.
- `release-snapshots/vonseo-v2.5.0-pre-release-notes-20261014-3F1A62B9.zip`: verified title-patch rollback before public-note changes, SHA-256 `3F1A62B9DE1D8926BC60F1A49B81C7E8CB2C6E3EE5D62FEB0290004C13AEBB53`.
- `history-before-20261014.zip`: nine older ZIPs plus one author-QC JSON record, retained byte for byte. Its `manifest.json` records all ten original paths/sizes/SHA-256 values. Extract the required nested ZIP for historical recovery; this outer history archive is not a plugin installation package.

Older backup paths in the history report describe their original locations, not additional files still present on disk. Backup consolidation reduced the file count without discarding historical data.

## Artifact Timeline

| Snapshot | Bytes | SHA-256 |
| --- | ---: | --- |
| Pre-independent-review | 1,841,589 | `588E2303EFF061EF8C97A59A987D2B88CA0E8344E190314D33DB471B74E3AB1B` |
| Independent-review patch | 1,842,589 | `67918161ECEF965918DCF470B8FEF1A68B2362A944EF44DA877B8EBA576DB220` |
| Pre-literal-llms HTTP fix | 1,844,530 | `70D3814897EE7CDA41562B4FC7140B9CF93C11EEFEE92F47230F0A6A996A5EA9` |
| Indexing/llms patch | 1,844,544 | `1A9FDE1D3C8B3837747CEAA8269FFFB5CD59B5F944D649E4E214B56282B9D5BA` |
| Settings/schema/canonical patch | 1,845,574 | `B25E23D882E9CA737EB217949C367E6DCA603EC8D053F5C893C8DE138BCEB0DE` |
| Head-time author patch | 1,845,780 | `31B3BC0C6C3F07207F4E29B03D19BC59A8FDAEDD4A5C52630A296A256C7E8B5B` |
| Pagination alignment | 1,845,916 | `C44F9D38429023D9921FD3D09731FC8A7D63779068F6FC1A81D707588C80C374` |
| Legacy-title patch | 1,846,229 | `3F1A62B9DE1D8926BC60F1A49B81C7E8CB2C6E3EE5D62FEB0290004C13AEBB53` |
| Current public-notes/FAQ update | 1,846,815 | `B41A68EF440117A62E540B659262B6A954520CB0A4FAB4F847606D9B02381DBA` |

The history archive also contains two snapshots of the previously installed main-site plugin. Their complete hashes and original filenames are in the recovery manifest; they are not mislabeled as exact release artifacts.
