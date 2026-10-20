# Brand and editorial automation implementation plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Refresh bilingual brand/editorial content and ship local evidence-based publishing.
**Architecture:** Existing Post publishing stays the public source of truth. A local Codex scheduled task uses repository-owned collection, validation and SSH import commands. Evidence stays local and external shares stay drafts.
**Tech Stack:** Laravel 12, PHP 8.4, Pest, Vite, CSS Grid, local Codex automations.
**Spec:** docs/superpowers/specs/2026-10-20-brand-editorial-automation-design.md

## Global constraints

- Türkiye ve ABD borsaları / Turkish and US stock markets in general copy.
- Software & AI Engineer · Co-founder with Turkish equivalent; historical employment titles remain accurate.
- Publish on the personal site only; external channels receive local drafts.
- Tests and npm run build run at finalization only, per user instruction.
- Daily 10:00 Europe/Istanbul scan; maximum two new stories in seven rolling days.
- Preserve existing user .env.example modifications; no secrets in repository/output.

## Review focus

- Concurrent imports and lost SSH replies cannot duplicate posts or bypass limits.
- Stale checkouts/worktree duplicates cannot hide or repeat merged work.
- Invalid HTML, unsupported claims and private paths cannot be published.
- Deleted/imported and hand-edited posts cannot be recreated or overwritten.
- Narrow screens and long translated titles keep card actions aligned/readable.

### Task 1: Brand identity and cards
**Files:** localized brand.php and portfolio JSON, home.blade.php, brand-games partial, brand.css, StructuredData.php, Sitemap.php, resources/og/cards.json, tests/Feature/BrandIdentityTest.php.
**Interfaces:** retain translation keys; add employment_role; use localized general identity in schema.
- [ ] Write identity/localization/market naming regression assertions.
- [ ] Rewrite identity, about, services, projects and game copy; update share cards/schema.
- [ ] Share game/story card content tracks and anchor CTAs; inspect both languages at finalization.

### Task 2: Editorial content
**Files:** stories/*.json, writing-voice.md, SyncStories.php, StorySeeder.php.
**Interfaces:** preserve published slug/type; complete per-language SEO imports.
- [ ] Read and rewrite all existing stories against source evidence.
- [ ] Write the first market-report story and private evidence.
- [ ] Complete primary meta_description import; regenerate OG images at finalization.

### Task 3: Collection and validation
**Files:** config/editorial.php, Services/Editorial/{RepositoryCollector,EditorialBundle}.php, Console/Commands/{EditorialCollect,EditorialValidate}.php, tests/Feature/EditorialTest.php, resources/editorial/{policy.md,bundle.schema.json}.
**Interfaces:** collect emits frozen repo refs/SHAs; bundle validation emits normalized public Post payload.
- [ ] Write tests for real temporary Git repositories, worktree deduplication, unsafe files, bad refs and invalid evidence/bundles.
- [ ] Implement bounded incremental collection from origin/main without checkout/pull.
- [ ] Implement strict bilingual payload/HTML/evidence validation and researcher/writer/editor policy.

### Task 4: Atomic publication
**Files:** editorial_publications migration, Services/Editorial/EditorialPublisher.php, Console/Commands/{EditorialImport,EditorialPublish}.php, EditorialTest.php.
**Interfaces:** import returns stable post/bundle/URLs; publish transports public artifact over configured SSH.
- [ ] Write duplicate/deletion/slug/topic/rate-limit/rollback tests.
- [ ] Implement unique ledger and serialized transactional import through PostPublication.
- [ ] Implement safe SSH transfer, localized external-share drafts and recovery documentation.

### Task 5: Finalization and release
**Files:** README.md, tests, official automation configuration.
- [ ] Run full Pest/content checks; fix failures and rerun affected checks.
- [ ] Run build/OG generation; inspect screenshots and geometry in both languages.
- [ ] Obtain fresh whole-branch review and fix material findings.
- [ ] Back up selected live posts; deploy code and sync chosen stories only.
- [ ] Import market-report story; verify live pages, RSS, sitemap.
- [ ] Create daily 10:00 Europe/Istanbul local automation through official tool.
- [ ] Merge to original checkout preserving .env.example; report links and draft location.

## Execution rulings

The user explicitly requested immediate development; proceed inline without further approval. Tests are authored first but execute only at finalization. Use the managed isolated worktree, existing copied/reused dependencies and a fresh reviewer at the end.
