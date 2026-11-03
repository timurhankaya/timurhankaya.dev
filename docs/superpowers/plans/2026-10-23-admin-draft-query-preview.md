# Admin draft query preview implementation plan

**Goal:** Let the owner review the pending bilingual revision on its live page using `?draft=1` after admin login.
**Architecture:** Existing preview controller handles authentication/rendering; a small private story draft store overlays text without database writes. A local/remote CLI stages an exact story file. No schema change.
**Tech stack:** Laravel 12, PHP 8.4, Pest.
**Spec:** docs/superpowers/specs/2026-10-23-admin-draft-query-preview-design.md

## Constraints and review focus

- Reuse this checkout: it holds the private revision queue and unrelated in-progress work. Install only this task's files remotely.
- Write feature tests before implementation; run them only at finalization as instructed by the owner.
- Protect both article/case-study URLs and both languages from guest/non-admin leaks.
- Preserve canonical redirects, sanitized HTML, noindex and private no-store headers.
- Preview a database draft without publishing; overlay staged text without changing live data.
- Detect stale or malformed stage files; never accept a query parameter as a filename.

## Tasks

- [x] Apply the explicitly approved SaaS review-02 with original snapshot guard and verify both live pages.
- [x] Add tests in tests/Feature/DraftQueryPreviewTest.php for auth, localized rendering, public isolation, sanitization, redirects, stale baseline and staging validation.
- [x] Add app/Services/Publishing/StoryDrafts.php and app/Console/Commands/StageStory.php; stage one exact existing story in private storage.
- [x] Extend PreviewPostController and delegate `?draft=1` requests from PostController. Preserve normal public behavior.
- [x] Document staging/review/approval flow; prepare and stage only the next article for review.
- [x] Finalize with focused/full tests, formatting check, npm run build; install only feature files with remote backups.
- [x] Verify live guest denial and authenticated rendering without creating a login token or altering the owner's session; record limits if real browser auth is unavailable.

## Validation and delivery

488 tests passed / 3152 assertions, Pint check and frontend build passed. Five feature files installed with before-hash guards and backups; no unrelated workspace files or frontend assets installed. External guest GETs redirect to admin login with private no-store. Production HTTP-kernel checks using an existing admin in memory rendered every proposed paragraph in both languages without writing a session token or changing the public post. Browser-cookie login was not exercised. Pending post 2 is staged and still requires owner approval.
