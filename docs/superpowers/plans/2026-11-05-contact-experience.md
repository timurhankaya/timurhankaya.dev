# Contact experience implementation plan

**Goal:** Replace the cramped contact layout with a typographic introduction and a clear, accessible bilingual contact experience.
**Architecture:** Keep Laravel POST validation, CSRF, honeypot, encrypted timer, throttling and admin persistence. Change the Blade view, scoped page styles and bilingual copy; improve form focus handling in the existing JavaScript.
**Tech stack:** Laravel 12, Blade, CSS, existing JavaScript/Vite. No new dependencies.

## Design

The previous sticky introduction leaves unused space opposite a densely boxed form. A full-width “Konuşalım.” / “Let’s talk.” heading establishes hierarchy. Below it, a compact direct-contact rail balances a spacious, unboxed form with three numbered groups: topic, sender, message. Orange indicates selection and action. At 900px and below, the layout stacks. All editable fields use at least 16px type and visible keyboard focus.

A wizard would obscure the short form and complicate preselection and validation. Retaining the existing card would preserve the imbalance. Use the editorial layout described above, as the implementation decision under the user's request to redesign and proceed.

## Constraints and review focus

- Run tests, lint and npm run build only at finalization, as the user instructed; this overrides per-step TDD runs.
- Preserve query preselection, old input, services, budget, timeline, errors, success and backend behavior.
- Review both locales, 1440/768/390/320px, long labels, radio arrow keys, checkbox Space, Tab and focus after errors/success.
- Confirm no horizontal overflow, hidden project fields stay out of the tab order, message guidance is associated with its field, and field errors remain associated.
- Do not submit test messages to production; verify real storage and admin access using the isolated test database.

## Tasks

- [x] Update Blade composition and paired TR/EN copy; preserve all POST field names and preselection.
- [x] Replace scoped contact CSS; add direct email and existing social profiles; improve error/success focus and no-JS project access.
- [x] Inspect responsive views during development using Vite; finalize with full Pest suite, Pint lint, JS syntax check and production build.
- [ ] Review changes, commit and push to main, deploy with Forge and verify the live TR/EN pages and form interactions.

## Final validation before deployment

- `composer run test`: 508 passed, 3,304 assertions; includes validation, honeypot/timing/throttling, persistence and Filament admin authorization/read status.
- `vendor/bin/pint --test`: pass. `node --check resources/js/app.js` and `git diff --check`: pass. No npm lint script is configured.
- `npm run build`: pass, Vite 7.3.1. Production assets also used for the final browser checks.
- Both locales at 1440, 768, 390 and 320px: no horizontal overflow; 16px field text; radio arrow keys, checkbox Space, hidden-field Tab skipping, submit reachability, required-field validation and query preselection pass.
- Separate temporary SQLite browser environment: TR/EN server validation retains input and focuses summary; valid project submissions persist services, budget and timeline, redirect to the localized page, and focus the success status. No live test messages submitted.
- JavaScript-disabled browser: all optional project fields remain available. JavaScript enhances this by conditionally hiding them.
- Independent read-only review identified mobile DOM/visual ordering; fixed by putting the form first in DOM and explicitly placing desktop columns. Browser regression check now passes. No deferred findings.
- The native fragment navigation initially reset success focus; focusing on `pageshow` resolves this in browser submission checks.

Screenshots are local review artifacts under `output/playwright/`, excluded from the commit.
