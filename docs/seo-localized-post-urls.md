# Localized article and case-study URLs

Reviewed against Google Search Central on 21 October 2026.

## Findings and decisions

The site already renders article bodies on the server, publishes RSS and XML sitemaps, supplies descriptive metadata, and emits author, article and breadcrumb JSON-LD. The gap was the dynamic story slug: the language prefix and section changed, but the English slug stayed identical in Turkish. The fix gives each language a stable editorial URL and keeps the existing story key as a permanent alias.

Google recommends separate URLs for different languages and permits localized words in those URLs. Localized slugs help people understand the destination; this change does not promise higher rankings. Story URLs continue to determine the content language independently of browser preferences. [Managing multilingual sites](https://developers.google.com/search/docs/specialty/international/managing-multi-regional-sites), [URL structure](https://developers.google.com/search/docs/crawling-indexing/url-structure).

Each localized story has a self-referencing canonical. Its HTML and sitemap list the same absolute `en`, `tr` and `x-default` URLs, including itself. `x-default` points to the configured fallback language. Turkish and English pages are not canonicalized to one language. Google requires reciprocal alternate sets and recommends a canonical in the same language where possible. [Localized versions](https://developers.google.com/search/docs/specialty/international/localized-versions), [Canonical URLs](https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls).

The original content key and either language slug resolve a published story. Legacy paths, old `/blog/` paths, mixed-language section names and a moved case study under `/writing/` redirect directly to its current canonical section and locale slug with HTTP 301. The trailing-slash middleware also resolves the final destination to avoid a slash-removal hop before a slug redirect. Unknown, draft, scheduled and deleted stories stay unavailable. Internal navigation, series links, RSS links, sitemap entries, structured-data URLs and IndexNow notifications use the same `publicUrl()` method. Google recommends permanent server-side redirects, updating internal links and sitemaps, avoiding redirect chains, and retaining redirects for at least a year; these aliases have no expiry. [URL migrations](https://developers.google.com/search/docs/crawling-indexing/site-move-with-url-changes).

The existing article schema continues to describe the visible headline, summary, language, author, images and publication/modification dates. It now receives the localized canonical URL automatically. No fabricated ratings, financial claims or unsupported rich-result promises were added. [Article structured data](https://developers.google.com/search/docs/appearance/structured-data/article).

RSS item links use the current locale canonical, while GUIDs retain their original locale URL based on the stable content key. The old URL remains a valid permanent redirect. This preserves the identity readers use to recognize an existing item after a URL change. [RSS 2.0 specification](https://www.rssboard.org/rss-specification#ltguidgtSubelementOfLtitemgt).

Original experience, useful explanations and checked facts remain editorial requirements. Google's generative-content guidance also applies to titles, descriptions, structured data and image text. A word count is this repository's editorial constraint, not a Google ranking requirement. [Google's guidance on generative content](https://developers.google.com/search/docs/fundamentals/using-gen-ai-content/).

## Data and migration

- `story.slug` / `posts.slug`: immutable content key and original URL alias; also keeps existing OG card filenames working.
- `story.tr.slug` → `posts.slug_tr`; `story.en.slug` → `posts.slug_en`: optional explicit language addresses. Existing payloads without them remain compatible and fall back to the stable key.
- Slugs accept lowercase ASCII words separated by hyphens. Another story cannot reuse a legacy or locale slug, including one belonging to a deleted story. Each locale column has its own unique database index; cross-column collisions are rejected by publication validation.
- The migration first loads final slugs from normal source stories and `resources/editorial/launch-story.json`, keyed by original slug. It backfills every existing row, including trash. Other rows receive a Turkish title-derived slug and retain their original English slug. Collision suffixes (`-2`, `-3`, …) are deterministic. It preserves original keys, publication dates and content modification timestamps.
- Both language slugs freeze at first publication. Later headline changes and `stories:sync` do not silently change URLs. A future URL rename would need an explicit historical-alias migration; it is intentionally outside the normal editing workflow.
- Editorial checksums include the nested slugs. Re-seal a changed bundle before validation/import. Translation content excludes nested `slug` keys after import.
- `stories:sync grounding-ai-market-reports-in-source-data --include-launch` updates only the explicitly requested existing launch post. It does not create or publish a missing post and leaves its address and publication date unchanged.

## Release verification

Run `php artisan test --compact` and `npm run build` only at the finalization stage, per the repository instructions. Focused regression coverage is in `tests/Feature/LocalizedPostUrlsTest.php`; existing publishing, locale-routing, SEO, feed and sitemap tests remain relevant. Do not treat this note as evidence that any checks have passed.

Ship the final source JSON files with the migration so existing rows receive the reviewed addresses immediately. Run the migration before serving requests with code that queries the new columns. Back up the database before release. Verify representative old URLs return a single 301 and their final targets return 200, then inspect both language versions, canonical/hreflang, RSS and sitemap in production. Submit the current sitemap in Search Console and monitor URL inspection, indexing and crawl errors as Google revisits the changed URLs; indexing timing and ranking changes cannot be guaranteed.
