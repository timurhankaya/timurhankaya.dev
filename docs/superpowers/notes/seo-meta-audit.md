# SEO meta audit (2026-10-17)

Audited every public page type in English and Turkish against the local app (home, projects, contact, CV, games index and both game pages, writing index, article, case-study index, case study, project-filtered index, 404). `tests/Feature/SeoMetaTest.php` keeps the rules below enforced.

## Rules

| Item | Rule |
|---|---|
| `<title>` | At most 70 characters. The brand ("— Timurhan Kaya") is appended only when the whole title still fits in 65. Stories take `meta_title`, else the page title. |
| Description | 40–160 characters, ends on a word boundary, never HTML. Stories take `meta_description`, else the excerpt. |
| h1 | Exactly one per page, no skipped heading levels. |
| Canonical | Every indexable page has a self-canonical. `?page=N` and `?project=key` are their own canonical. `?type=` no longer exists (301 to the section). Game rooms and the 404 page are `noindex` and carry no canonical or hreflang. |
| hreflang | `en`, `tr` and `x-default` (English) on every indexable page and in `sitemap.xml`. |
| Images | Every `<img>` has `alt`; social images use real file dimensions. |
| Breadcrumb | Visible `<nav class="brand-breadcrumbs">` on every inner page; names are identical to the `BreadcrumbList` in JSON-LD. |
| Search Console | `<meta name="google-site-verification">` only when `SEARCH_CONSOLE_VERIFICATION` is set. |

## Findings and fixes

- CV meta description was the whole profile text (550+ characters): now shortened with `Seo::metaDescription` for description, `og:description`, `twitter:description`.
- Case-study and article titles run 64–73 characters because they were written as long template titles. The meta field and the title rewrite in plan task 7 fix this; the test caps titles at 70.
- Contact (64–71 characters) and writing index (71–75) descriptions are short but acceptable.
- No missing alt text, no heading jumps, one h1 everywhere, canonical and hreflang present everywhere indexable.
- Back links in story headers replaced by the breadcrumb (the parent link is inside it).
- Fonts moved from a separate `typography.css` into the Vite bundle (`resources/fonts`): one fewer request in the critical path, and fonts now load in Vite dev as well as in the build. `public/fonts/typography.css` stays for the printable CV.
