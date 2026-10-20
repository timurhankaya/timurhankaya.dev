# timurhankaya.dev

Personal website and playground project built with Laravel + Livewire.

This repository includes:
- Portfolio homepage and contact pages
- CV page
- Blog (listing and post detail pages)
- Multiplayer mini-games:
  - Imposter
  - Vampire
- SEO utilities (localized URLs, sitemap, robots)
- EN/TR content support

## Tech Stack

- PHP 8.4
- Laravel 12
- Livewire 4
- Tailwind CSS 4
- Vite 7
- GSAP
- Pest (testing)
- Optional Redis support via Predis

## Quick Start

### 1) Install dependencies and prepare app

```bash
composer run setup
```

This script installs PHP/Node dependencies, prepares `.env`, generates `APP_KEY`, runs migrations, and builds assets.

### 2) Start local development

```bash
composer run dev
```

This runs:
- Laravel dev server (`0.0.0.0`)
- Queue listener
- Log tailing (`pail`)
- Vite dev server

## Useful Commands

```bash
# Run test suite
composer run test

# Run imposter-focused coverage check
composer run test:imposter-coverage

# Build frontend assets for production
npm run build
```

## Main Routes

- `/` - Home / portfolio
- `/contact` - Contact
- `/cv` - CV
- `/games` - Games hub
- `/games/imposter`
- `/games/vampire`
- `/blog`
- `/sitemap.xml`
- `/robots.txt`

## Project Structure (high level)

- `app/Livewire/Games` - Interactive game flows
- `app/Services` - Domain/game/business services
- `resources/views/pages` - Main page views
- `resources/views/livewire` - Livewire blade views
- `resources/content` - Portfolio/game content (EN/TR)
- `lang` + `resources/lang` - Localization strings
- `tests` - Pest feature and unit tests

## Environment Notes

- Copy `.env.example` to `.env` if you do not use the setup script.
- Configure database credentials in `.env`.
- Optional: configure Redis if you want cache/session/queue improvements.

## License

MIT (see `LICENSE`).


## Publishing and administration

The real public site uses English at `/` (no `/en` prefix) and Turkish at `/tr`.
Translated paths include `/projects` ↔ `/tr/projeler`, `/writing` ↔ `/tr/yazilar`,
`/case-studies` ↔ `/tr/vaka-incelemeleri`, and `/contact` ↔ `/tr/iletisim`.
Old links redirect to their canonical translations. Browser language negotiation
runs only at the root; explicit translated paths remain accessible.

```bash
php artisan migrate
php artisan db:seed --class=RetireLegacyContentSeeder
php artisan db:seed --class=StorySeeder
php artisan storage:link
php artisan filament:assets
php artisan portfolio:admin
```

`StorySeeder` creates missing stories as drafts only; it does not rewrite
existing posts, editor changes, drafts, or soft-deleted records. Never run `migrate:fresh`
on a database with content to preserve. `portfolio:admin` asks for a name, email, and
hidden password; it does not create default credentials or promote existing users.

A project can own several case studies. They form a series ordered by `sort_order`
(the overview story, whose slug equals its project key, uses `0`); cards, the case index
(`/case-studies?project=<key>`) and each story page show the series, with previous/next
links and the project's related articles. `StorySeeder` imports bilingual stories from
`resources/content/stories/*.json` with the same create-missing-only rule; entries with
`"draft": true` arrive unpublished so they can be reviewed in `/admin` first.

Sign in at `/admin`. The editor supports article/case-study types, primary content,
optional English translations, covers (JPEG/PNG/WebP, 5 MB), project metadata,
drafts, scheduling, publishing, and reversible deletion. A blank translation falls
back to primary content. Published slugs/types stay locked after unpublishing.
Draft previews require administrator access and are not indexed.

**Writing and Case study.** They are separate sections: `/writing` lists articles (method, opinion, how-to) and
`/case-studies` lists case studies, each one a single real incident from a project, told as a story. Voice and title rules
live in `docs/superpowers/notes/writing-voice.md`; `php artisan stories:lint <file>` checks a stories JSON file against them,
and `STORY_LINT=1 php artisan test --filter=StoryStyleTest` checks every shipped story. New stories are added as drafts
through `StorySeeder` (`"draft": true`); an entry with `"replaces": "old-slug"` soft-deletes the row it supersedes. The earlier
placeholder case studies and demo articles are taken off the public site with
`php artisan db:seed --class=RetireLegacyContentSeeder` (soft delete and unpublish, both reversible in `/admin`).

**Search and AI visibility.** Every post has optional per-language SEO title (70 characters) and description (160) in the admin
panel; blanks fall back to the title and excerpt. The excerpt is shown as a "Short answer" at the top of each story and is the
description fallback. `/llms.txt` and `/tr/llms.txt` index published writing and case studies. `robots.txt` lists the AI
crawlers explicitly (all allowed). Set `SEARCH_CONSOLE_VERIFICATION` in `.env` to emit the Google verification tag. Findings and
rules: `docs/superpowers/notes/seo-meta-audit.md`, `docs/superpowers/notes/ai-visibility.md`.

The HTML specimen under `/tr/onizleme/icerik-ornekleri` is a local-only design example,
separate from editorial content. Uploaded covers are public files; use them only for
images intended for publication. Filament styling is separate from public-site CSS.

## Local editorial automation

The local Codex task reads `resources/editorial/prompt.md`,
`resources/editorial/policy.md` and the writing voice guide. It selects useful,
evidence-backed articles or case studies from merged Monafy work and can publish
them on this site when the local `EDITORIAL_PUBLISH_ENABLED` switch is enabled.
LinkedIn and DEV.to outputs remain unpublished drafts.

The task runs daily at 10:00 Europe/Istanbul while the computer and Codex are open.
It considers source code, unhandled scan backlog, existing publications and pending
drafts. A meaningful product capability, working method or substantial engineering
decision qualifies; maintenance and small fixes do not. No strong topic means no
publication or notification. One new topic per run and two per rolling seven days
are maximums, never targets.

```bash
php artisan editorial:collect
php artisan editorial:validate storage/app/private/editorial/bundles/topic.json
php artisan editorial:publish storage/app/private/editorial/bundles/topic.json --dry-run
php artisan editorial:publish storage/app/private/editorial/bundles/topic.json
```

Configure `EDITORIAL_SOURCE_ROOT`, `EDITORIAL_SSH_HOST`, `EDITORIAL_REMOTE_PATH`,
optionally `EDITORIAL_REMOTE_PHP`, and the owner-approved publication switch in the
ignored local `.env`. The config default is disabled. Scheduled runs must not change
permissions themselves. Only sealed public content travels over SSH stdin to
`editorial:import`; evidence and source references stay private. The import has no
public HTTP endpoint. Repeated bundles are idempotent, admin edits are preserved,
and removed publications stay suppressed. Unpublish incorrect output through `/admin`.

Readable drafts and private evidence live under `storage/app/private/editorial`.
Receipts and share drafts live under `published/<topic_key>/`. External drafts have
not been sent anywhere and do not create backlinks by themselves. Automated checks
do not guarantee factual accuracy, human review or search placement. No new model
API key is required for the local Codex task.

Articles use separate `tr.slug` and `en.slug` canonical addresses. The top-level
`slug` remains the original content key/URL alias and OG image key. Published
addresses stay stable after title edits. See [localized URL and SEO notes](docs/seo-localized-post-urls.md)
for migration, redirects, sitemap and feed behavior.
