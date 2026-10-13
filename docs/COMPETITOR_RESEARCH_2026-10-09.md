# Competitor Research - 2026-10-09

Scope: advertised features on official vendor pages, checked on this date. This is not a hands-on benchmark, security audit, or proof of ranking improvements. Plan availability can change; no pricing or full free-versus-paid parity is asserted here.

## What Competitors Offer

| Vendor | Relevant advertised capabilities | Official source |
| --- | --- | --- |
| Rank Math | CPT support, bulk titles/descriptions, internal-link suggestions, redirects, 404 monitoring, analytics and schema tools. Its separately listed automatic Link Builder is marked Coming Soon, not a verified shipped feature. | [SEO suite](https://rankmath.com/wordpress/plugin/seo-suite/) |
| Yoast Premium | Internal-link suggestions and orphaned-content review; redirect management with CSV; review-before-save bulk AI metadata drafts. These are Premium claims, not claims about the free plugin. | [Yoast SEO Premium](https://yoast.com/product/yoast-seo-premium-wordpress/) |
| SEOPress PRO | Issue-prioritized Site Audit, public post-type selection, configurable scan scope, broken-link results, and optional Search Console context. | [Official features](https://www.seopress.org/features/) |
| AIOSEO | SEO change history, comparison, user activity and restoring earlier metadata/schema settings. No inference that every plan includes these tools. | [SEO Revisions](https://aioseo.com/features/seo-revisions/) |

## Ideas Worth Adapting

These are VonSEO design proposals, not features already implemented and not additions to the approved 2.5.0 scope.

1. **Visibility inspector (highest priority):** explain locally why an individual URL is absent from VonSEO's sitemap: site visibility, content-type opt-in, publication status, password, noindex, or static-front-page deduplication. This diagnoses plugin output only; do not claim to know Google's indexing state.
2. **Internal links and orphaned content:** extend the existing 2.6.1 proposal with incoming-link counts, source/target suggestions, and manual review. Scan saved public content in bounded batches; no HTTP crawler, automatic link insertion, or exposure of protected/private content. Exclude navigation-only links from claims unless that surface is actually inspected.
3. **Metadata review queue:** filter published content with missing or unsuitable titles/descriptions, preview changes, and explicitly apply selected edits. Start with CSV export and local fallbacks; no bulk rewriting or mandatory AI service. Preserve capabilities, per-item nonces/authorization, validation, and a recovery path.
4. **SEO revision history:** inspect WordPress-native revision compatibility first, then propose bounded opt-in history for supported SEO fields with before/after comparison and an authorized restore. Do not create unlimited options, public logs, or custom tables by default. Restoring metadata is not evidence of recovering search rankings.
5. **Migration preview:** later assess importing competitor metadata via a strict field allowlist, dry-run and conflict report. This is separate from 2.5.2 site-settings JSON portability and must not overwrite existing metadata silently.

## Product Decision

- Finish 2.5.0 CPT correctness and its QC first; retain the existing 2.5.1 and 2.5.2 sequence.
- Add these proposals to the parking lot without assigning release numbers prematurely.
- Fold incoming-link/orphan evidence into the existing 2.6.1 internal-link proposal instead of creating a competing duplicate.
- Keep Google dashboards, remote rank tracking, mandatory AI, automatic external crawling and bulk content rewriting outside the current direction.
- Prefer transparent, reproducible checks over vanity scores or promises about rich results, AI citations and rankings.
