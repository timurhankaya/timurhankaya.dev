# Concise engineering writing implementation plan

> **For agentic workers:** Use superpowers:executing-plans to implement this small approved change in the current session.

**Goal:** Make future and current writing concise accounts of real engineering decisions.
**Architecture:** Align the editorial documents and StoryStyle with the approved no-minimum rule, then revise existing bilingual content through stories:sync. Keep revision evidence private and preserve addresses and publication history.
**Tech Stack:** Laravel/PHP, JSON story bundles, Markdown editorial instructions.
**Spec:** docs/superpowers/specs/2026-10-23-concise-engineering-writing-design.md

## Global constraints

- Owner approved the conversational design and requested execution; no further execution-choice prompt needed.
- Run tests and npm run build only at finalization, per the owner's AGENTS instruction. Test-first source edits are permitted; early RED runs are deferred to finalization.
- Preserve unrelated dirty files. No Monafy source changes or new publication quota entries.
- No minimum word count; keep existing maximums and paragraph limits.

## Review focus

- Short valid stories must pass both article and case-study validation.
- Overlong stories must still fail.
- Process-disclaimer removal must preserve meaningful technical limits.
- TR/EN revisions must agree and retain canonical addresses and metadata.
- Concurrent content changes must abort synchronization rather than be overwritten.

## Task 1: Approved writing rules and content revisions

- [x] Update StoryStyle tests for short valid articles/case studies and unchanged maximum limits.
- [x] Remove only the word-floor constraint from StoryStyle; align prompt.md, policy.md and writing-voice.md.
- [x] Refresh the private live snapshot; prepare narrowly scoped bilingual revisions for posts 10–13 and a short problem/solution rewrite of post 15. Audit the remaining ten topics without forcing changes.
- [x] At finalization run relevant content validation, the full test suite, npm run build and scoped formatting. Inspect actual outputs.
- [x] Back up and install only the validator needed for short publications; apply content using existing stories:sync and its model validation, with stale-content guards and immutable-field comparison.
- [x] Verify changed pages with bounded direct curl GETs, metadata and exact revised text; save receipts, updated private review copies and automation memory.

Final verification: 477 tests / 3093 assertions passed; npm run build passed; scoped Pint passed; five-story content lint passed. A regression for empty readable bodies was observed failing before its fix and passes in the final suite. Ten revised public locale pages returned direct 200 and matched new paragraphs, titles, descriptions, canonicals and alternates.

Review: one P2 finding about preserving the AI report validation limit was addressed with a concise substantive sentence in both languages. No unresolved material findings.
