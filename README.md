# VonSEO

VonSEO is a lightweight SEO plugin for WordPress. It stores its data in native WordPress options and post/term metadata, runs content analysis in the browser, and sends no telemetry.

**Requirements:** WordPress 6.0 or later, PHP 7.4 or later.

## Features

### Content SEO
- SEO title, meta description, focus keywords and noindex for posts, pages and selected public custom post types.
- Social title, description and image for Open Graph and X (Twitter) cards, with Facebook and X previews in the editor.
- Local content analysis with an SEO score, title and description length checks, keyword placement, headings, image ALT text and links.
- Quick Edit fields and SEO columns in post lists.
- SEO title, description, canonical URL, social description and noindex for categories, tags and selected public taxonomies.

### Structured data
- Organization or local business details and WebSite markup.
- Article, NewsArticle, BlogPosting, WebPage, Product, Product Review and Service per post.
- FAQ and Video markup from editor fields, and BreadcrumbList from the breadcrumb trail.

### Technical SEO
- XML sitemap with a sitemap index and batches of up to 1,000 URLs.
- Canonical URLs, robots directives through the WordPress robots API, and Google and Bing verification tags.
- Editable robots.txt and a curated llms.txt file.
- Redirect rules, attachment page redirects and an optional 404 log.
- Optional IndexNow notifications for Bing and Yandex.

### Site tools
- Table of contents, automatic or by shortcode.
- Breadcrumbs by shortcode or theme function.
- Manual Site Audit of technical settings and published content in batches of 25.
- Manual Competitor comparison of word count, headings, images and reading ease.
- Automatic image ALT and title attributes from attachment titles, and an optional RSS footer link.
- Settings export and import with a preview before changes are saved.

## Installation

1. Upload the `vonseo` folder to `/wp-content/plugins/`, or install the ZIP from **Plugins > Add New**.
2. Activate the plugin.
3. Open **VonSEO** in the admin menu and save your settings.

## Privacy

VonSEO has no telemetry or analytics tracking. Content analysis and Site Audit run locally. Two optional features make outbound requests: IndexNow notifications, and Competitor scans that an editor starts manually. The external-services section of [readme.txt](readme.txt) describes both.

## FAQ

**Where is the content analyzer?**
In the VonSEO sidebar/metabox when editing an enabled post, page or custom post type. It does not run on the public frontend.

**Does the analyzer send draft content outside WordPress?**
No. It runs in your browser.

**Does it support custom post types?**
Yes. Select eligible public types under SEO Content Types to enable the editor tools, metadata, schema, Quick Edit, Site Audit and sitemap coverage. Attachments and non-public types are excluded.

**Does the SEO title work with older themes?**
Yes. VonSEO supports the modern document-title API and the legacy `wp_title()` API used by themes such as Twenty Eleven.

**Does IndexNow guarantee indexing?**
No. It is optional and off by default. Search engines decide whether to crawl and index a URL.

**Does VonSEO create custom database tables?**
No. It uses WordPress options and post/term metadata.

**What happens when I remove the plugin?**
Deactivation keeps your data. Deleting the plugin removes VonSEO's settings and SEO metadata, so take a backup first.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

GPLv3 or later.
