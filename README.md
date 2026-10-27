# VonSEOWP: The High-Performance SEO Engine

> **Surgical SEO. Zero Bloat. Born from VonCMS.**

VonSEOWP isn't just another SEO plugin; it’s a high-performance publishing engine. Originally forged within the enterprise-grade **VonCMS** ecosystem, it has been meticulously adapted for WordPress publishers who refuse to compromise on speed, security, and database hygiene.

Stop turning your WordPress dashboard into a heavy SaaS marketing platform. It's time for **Performance-First SEO**.

---

## ⚡ Why VonSEOWP?

Most SEO plugins become increasingly heavy over time, trading performance for oversized dashboards, telemetry, and marketing layers. VonSEOWP rejects the bloat.

- **Zero-Waste Architecture**: No custom database tables. We use native WordPress options and post/term metadata.
- **Enterprise Heritage**: Built with the same performance DNA as VonCMS.
- **Privacy as a Feature**: No tracking or telemetry. Editor analysis and Site Audit stay local; optional IndexNow notifications and manual Competitor scans make disclosed outbound requests. See the external-services section in [readme.txt](readme.txt).
- **Supported Platform**: Requires **WordPress 6.0+** and **PHP 7.4+**, with document-title support for modern and legacy themes.

---

## 🛠️ The Professional Toolkit

### 1. The Modern SEO Workspace
A clean, focused editorial workflow that stays out of your way until you need it.
- **Compact Sidebar Interface**: Optimize content without leaving the editor.
- **Real-Time Google Preview**: See exactly how your site looks in the wild.
- **Smart Metadata Assistance**: Faster editorial workflows without the server load.
- **Selected Content Types**: Apply SEO tools to posts, pages and opted-in public custom post types.
- **Taxonomy Archive SEO**: Edit SEO titles, descriptions, canonicals, social descriptions and noindex on category, tag and selected public-taxonomy screens.
- **Competitor SEO Math**: Live metric comparison against any URL (with 12-hour transient caching).

### 2. Advanced Technical Engine
- **Dynamic Sitemap Indexing**: `sitemap_index.xml` with batches of up to 1,000 URLs across selected content and eligible nonempty taxonomy archives.
- **IndexNow Integration**: Explicit opt-in notifications for eligible published URLs, deferred and rechecked using native WordPress cron.
- **Surgical JSON-LD Schema**: Pure, optimized structured data (Article, FAQ, Video, Review, Breadcrumbs).
- **SEO Table of Contents (TOC)**: Lightweight, anchor-based TOC with zero external dependencies.

### 3. Safety & Health
- **System Health Monitor**: Real-time technical diagnostics for your environment.
- **Manual Site SEO Audit**: Local technical and content checks with safe Previous/Next navigation across 25-item batches.
- **Redirect & Robots Manager**: Lightweight control over your site's navigation and crawling instructions.
- **4-Layer Defense**: Strict Nonce verification, Capability checks, Sanitization, and Escaping at every layer.

---

## 🏁 Performance Philosophy

VonSEOWP is engineered with a lightweight-first architecture using native WordPress storage and minimal frontend overhead.

- **Lightweight by Default**: Minimal frontend requests.
- **Native Storage**: Maximum compatibility with migrations and backups.
- **Developer-First**: Clean code, hardened IDE stubs, and predictable hooks.

---

## 🚀 Installation & Requirements

1. **Upload** the `vonseo` folder to `/wp-content/plugins/`.
2. **Activate** and save your settings.
3. **Enjoy** a faster, cleaner WordPress.

**Requirements:**
- **WordPress**: 6.0+ (Required)
- **PHP**: 7.4+ (Required)

---

## FAQ

**Where is the analyzer?**
Open an enabled post, page or custom post type editor and use the VonSEO Content Intelligence sidebar/metabox. It is not a public frontend script.

**Does the analyzer send draft content outside WordPress?**
No. Content analysis runs locally in your browser. Optional IndexNow notifications and manual Competitor scans make the disclosed requests in [readme.txt](readme.txt).

**Does it support custom post types?**
Yes. Select eligible public types in SEO Content Types to enable editor tools, metadata, schema, Quick Edit, Site Audit and sitemap coverage. Attachments and non-public types are excluded.

**Can Site Audit check thousands of posts?**
Yes. Previous/Next navigation processes published content in 25-item batches instead of loading everything in one request.

**Does SEO Title work with older themes?**
Yes, through the modern document-title API and legacy `wp_title()`, including Twenty Eleven. Legacy themes can still append their site name. Empty overrides preserve native titles, and password-protected SEO titles stay hidden until access is authorized.

**Does IndexNow guarantee indexing?**
No. It is an optional notification service, off by default unless previously enabled. Eligible canonical URLs are queued using native WordPress cron; search engines decide whether to index them.

**Does VonSEO create custom database tables?**
No. It uses native WordPress options and post meta.

**Is there a hidden Pro layer?**
No. The plugin is built as a lightweight toolkit without forced upgrades or paywalled admin tabs.

**Does it support large sites?**
Yes. The sitemap system supports sitemap indexing and paginated sitemap output.

**What happens when I remove the plugin?**
Deactivation keeps your data. Deletion removes VonSEO's stored settings and SEO metadata, so keep a backup. Both actions clear queued IndexNow events.

---

## 🗺️ Roadmap
- [x] Lean local content analyzer (v2.3.4)
- [x] Lean frontend meta output and social image fallback (v2.3.5)
- [x] Score consistency across editor and All Posts (v2.3.6)
- [x] Manual Local Site Audit Lite (v2.4.0)
- [ ] Publisher schema variants after the lean audit path is proven

---

## ⚖️ License
Licensed under **GPLv3 or later**. Built by publishers, for publishers.
