# Local Game Simulation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [x]`) syntax for tracking.

**Goal:** Add controlled local bot simulation to both games, fix the host bar blur, present Monafy through engineering work in the CV, and publish a verified bilingual case study.

**Architecture:** A server-only simulator registers normal room participants and invokes existing game services. Livewire uses session identity and exposes only safe simulation status. CV uses explicit localized overrides.

**Tech Stack:** Laravel 12, PHP 8.4, Livewire, cache locks, Blade, Vite, existing OG renderer.

**Spec:** `docs/superpowers/specs/2026-10-21-local-game-simulation-design.md`

## Global Constraints

- User explicitly requested direct implementation after scope/design delivery; execute without further approval gates.
- Run tests and `npm run build` only at finalization.
- Preserve unrelated `.env.example` changes.
- Simulation is available only in `local`/`testing`, with server-side session/host checks on every action.
- At most 12 bots per room; no direct game-state mutation and no external AI calls.
- Keep secret roles and simulation registry out of public Livewire state.
- Auto mode handles bot actions only; host phase controls remain manual.
- Publish the case only after implementation and final verification; retain privacy and TR/EN SEO rules.

## Review Focus

- Forged Livewire host/public fields must not authorize simulation: environment and session ownership tests.
- Duplicate poll or button requests must not toggle an existing vote off: replay/idempotence tests.
- A host change must stop automation and must not convert human participants into bots: ownership and registry tests.
- Doctor/detective target restrictions and dead/hunter behavior must use normal service rules: role-policy tests.
- CV images and serialized metadata must agree with the CV title and profile: localized HTML/schema/image checks.

## Task 1: Bot policies and simulation service

**Files:** Create `app/Services/Games/Simulation/LocalGameSimulation.php`, `BotMoves.php`; create `tests/Feature/LocalGameSimulationTest.php`.

**Interfaces:** `status(string $game, string $code, ?string $actor): array`; `addBots(..., int $count): void`; `removeBots(...): void`; `setAutomatic(..., bool $enabled): void`; `play(..., bool $automaticOnly = false): void`. Game identifiers are `imposter` and `vampire`.

- [x] Write behavioral tests for server environment/host gates, registry isolation, 12-bot cap, password joining, lobby restrictions, human preservation, duplicate actions, ownership changes and role rules.
- [x] Implement server registry under a separate simulation cache key/lock; existing room service locks remain authoritative.
- [x] Use stable per-stage target selection with each bot's presented view. Read room state before each action and leave human turns/host controls alone.

## Task 2: Local UI and host-bar blur

**Files:** Create `app/Livewire/Games/Concerns/WithLocalSimulation.php`, `resources/views/livewire/games/partials/simulation.blade.php`; modify both Room components/views, `resources/lang/{tr,en}/games.php`, `resources/css/games.css`.

- [x] Add shared actions using the actual session player key; keep visibility and safe status as computed server values.
- [x] Run automatic play from the existing room refresh only in supported environments; update normal presenters afterwards.
- [x] Expose bounded bot count input, add/remove/manual play/automatic toggle, and role-count guidance for Vampire.
- [x] Put standard host-bar backdrop-filter last and add an opaque fallback; retain mobile safe areas.
- [x] Verify browser layout, manual/automatic gameplay and production rejection at finalization.

## Task 3: CV presentation

**Files:** Modify portfolio TR/EN JSON, CV Blade, OG spec/renderer and narrow image URL versioning; add CV tests.

- [x] Introduce CV role/profile/Monafy description overrides, preserving site biography and real job history.
- [x] Remove co-founder language from CV display, print, metadata and share cards; describe actual product/architecture/AI work.
- [x] Generate only changed CV cards; test metadata, versioned images and A4 fit at finalization.

## Task 4: Case study, review and release

**Files:** Add one bilingual story JSON under `resources/content/stories/`; add local simulator usage documentation; generate its OG cards.

- [x] Write an evidence-backed case on testing multiplayer flows locally, with a flow diagram and explicit network/human-behavior limits.
- [x] Review domain accuracy, language, metadata and links against the implemented code.
- [x] At finalization run complete tests, build, targeted browser flows and independent code review. Fix concrete failures and rerun relevant checks.
- [x] Commit and push only task changes; deploy atomically without clearing game caches. Import the new story through the existing seeder/publication path.
- [x] Verify production bot access is rejected, blur/CV work, and both case URLs/canonical/hreflang/OG/feed are correct.

## Verification before release

452 tests passed (2,900 assertions); production build and PHP style checks passed.
Four selected OG cards generated. Desktop and 390 px browser checks show blur(14px)
with the built stylesheet and no horizontal overflow in both games. Manual Imposter
bot actions and Vampire automatic polling use the host panel; CV print layouts fit
A4 in TR/EN. Local case URLs, canonical/hreflang, images, game links, sitemap and
both feeds returned the expected content.

Independent review found one warm-cache CV schema defect. Two failing regression
tests reproduced it; changing only the portfolio cache version fixed it, followed
by the passing full suite. Existing game cache keys are preserved.

## Release evidence

21 October 2026: commit `1f5251e` pushed to `codex/local-game-simulation`.
Release `20261021-local-game-simulation-1f5251e` cloned the latest live release,
applied only this task’s files and applied the CV patch without overwriting
concurrent ATS changes. Activation checked that the previous release was still
current before switching the symlink. No game caches were cleared.

Only this case was imported through a scoped StorySeeder directory. Live CV and
case pages in both languages returned 200 with correct canonical/hreflang,
versioned images and working game links. Sitemap and both feeds contain the case.
Production server checks rejected all bot operations in both games before and
after activation without changing the temporary verification rooms.

## Execution decisions

- Reused the working checkout on a new branch to preserve existing CV work;
  unrelated environment, editorial and ATS changes were excluded from the commit.
- Authored tests before implementation, but ran them only at finalization as the
  user requested.
- Used the existing shared active night phase for all night roles; no extra host
  phase transitions were introduced.
- Added optional snapshot guards to normal player actions and bot joins, so
  concurrent host, roster or phase changes reject stale bot actions under the
  existing room lock. Existing callers remain compatible.
- Added a server-only round identifier to distinguish games started in the same
  second, keeping replay protection scoped to the correct round.
- Bumped only the portfolio content cache version for the new CV schema; active
  game state and other caches remain intact.

Case: `/case-studies/local-bots-for-multiplayer-testing` and
`/tr/vaka-incelemeleri/yerel-botlarla-oyun-akisini-denemek`.
