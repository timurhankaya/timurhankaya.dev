# Studio HTML Implementation Plan

> **For agentic workers:** Use superpowers:executing-plans to implement this plan in the current session.

**Goal:** Deliver an independent, responsive Turkish studio concept as static HTML.

**Architecture:** Keep the existing application untouched. Place the entire prototype and its local fonts in prototypes/studio; no dependencies, build step, server endpoints or network-loaded assets.

**Tech Stack:** Semantic HTML, CSS, small progressive-enhancement JavaScript, Geist WOFF2.

**Spec:** docs/superpowers/specs/2026-11-17-studio-html-design.md

## Global Constraints

- User-approved visual direction, truthful project roles, temporary brand name and founder-led delivery.
- Run tests only at finalization. Do not connect the prototype to Laravel or Vite.
- Reuse the current clean checkout; this addition is isolated in a new directory. No commit, publish or domain purchase is requested.

## Review Focus

- 320px and zoomed layouts remain readable without horizontal page scrolling.
- JavaScript-disabled browsers retain access to navigation and all content.
- Mobile menu closing returns focus safely and desktop resizing reveals navigation.
- Every external/internal link has a real destination; contact opens email rather than simulating submission.
- Fonts and decorative graphics work when opened directly from the filesystem.

## Tasks

- [x] Build prototypes/studio/index.html and styles.css: all approved sections, typography, navy/orange palette and responsive compositions.
- [x] Add script.js: enhanced mobile toggle, Escape handling and resize cleanup. Copy both existing Geist subsets and their license into assets; document direct opening and the provisional brand in README.md.
- [x] Finalize: browser-check all target widths, keyboard, no-JS, reduced motion, file:// and missing fonts. Review local asset paths, link behavior, contrast and git diff; capture preview screenshots. No Laravel build is needed because its source and dependency graph are unchanged.

## Execution notes

- The approved chat plan is preserved in the accompanying spec. User instructions override per-step test execution; verification is reserved for finalization.
- Project descriptions come from resources/content/portfolio.tr.json, including contribution labels. Existing entries hidden on the public portfolio are used only in the explicitly approved local concept, not published or made public in the personal site.

## Final verification — 2026-11-17

- Chromium via Playwright CLI: 320/390/768/1000/1001/1440px and additional 720px zoom-equivalent layout, no horizontal page overflow. Desktop/mobile navigation updates at the 1000px breakpoint.
- Real keyboard interaction: Enter opens, Tab enters navigation, Escape closes and restores toggle focus; activating a section link closes the mobile menu and reaches its fragment. Desktop resize reveals navigation.
- JavaScript-disabled 320px context: navigation remains visible and page does not overflow.
- Direct file:// opening: local fonts and script load, menu enhancement works, no HTTP/HTTPS requests, no overflow at 390px.
- Blocked-font 320px context: fallback remains readable without overflow. Emulated reduced motion disables smooth scrolling.
- Computed text foreground/background audit: all audited pairs >= 7.44:1 (target >= 4.5:1). This is a text-contrast check, not a claim of full WCAG conformance.
- One h1, 18 unique IDs, 21 real links, all internal anchors and local assets valid. Both mailto links use the existing contact address. No mail was sent and external destination availability was not independently tested.
- `node --check prototypes/studio/script.js` and `git diff --check`: pass. No browser runtime errors or failed requests on the normal local page.
- Screenshots: output/playwright/studio-desktop.png, studio-desktop-viewport.png, studio-mobile.png and studio-mobile-viewport.png (ignored local artifacts).
- Independent read-only final reviewer: no actionable issues. No PHP tests or npm build run; the existing Laravel application is unchanged and this prototype has no build step.
- First browser-test attempt used a locator by the button name “Menü”, which changes to “Kapat” on activation; corrected the test locator to the stable button selector. No product-code change was needed.
