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
