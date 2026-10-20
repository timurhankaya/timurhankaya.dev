# Writing voice for Writing and Case study

`App\Support\StoryStyle` enforces the mechanical rules below over `resources/content/stories/*.json`
(run with `STORY_LINT=1 php artisan test --filter=StoryStyleTest`). Everything else is judgment.

## What a case study is

A deep technical account of one real problem on one project: what the system looked like, what went wrong or got hard,
which constraints mattered, what was built (with a diagram), which alternatives were rejected and why, what changed afterwards,
and what was learned. It is not a project overview or a brochure; every section is about the problem.
The reader is an engineer or a hiring manager who should finish knowing exactly what was solved and how well it was solved.

Disclosure rules per project:
- Monafy (the owner is co-founder): technical detail is open. Service and module names, Kafka topics, data flow, real incidents,
  and approximate measurements taken from the repository are allowed. Secrets, hostnames, IPs, user data and market-data vendor names are not.
- Client projects (eDanışman, Smart Global, Herbalfit): architecture and engineering decisions are open; client business data,
  customer data, secrets and hostnames are not.

## Building blocks a case study may use

All of these survive the content sanitizer and are styled by `brand.css`:

- Flow diagram, a sequence of steps or services:
  `<figure class="brand-diagram"><ol class="brand-flow"><li><strong>Name</strong><span>one line</span></li>…</ol><figcaption>…</figcaption></figure>`
- Layer or component diagram, groups of parts side by side:
  `<figure class="brand-diagram"><div class="brand-layers"><div><strong>Group</strong><ul><li>part</li></ul></div>…</div><figcaption>…</figcaption></figure>`
- A comparison `<table>` with `<thead>` for alternatives and trade-offs.
- At most one short `<pre><code class="language-…">` excerpt, only when it explains the decision (illustrative, rewritten, never secrets or real hostnames).
- `<h3>` sub-headings inside a long section.

## What an article is

A method, an opinion or a how-to that stands without any one incident (how CLAUDE.md is layered, why a worktree per task).
Articles run a little longer than case studies (500–1,000 English words). They still need a plain title and an opening in prose.

## Titles

- Start from what the reader would have seen or asked. Sentence case. At most 70 characters. No colon, no "from … to …".
- Good: "Fiyatlar ABD kapanışında neden düşüyordu?", "Günlük rapor bir gün boyunca neden yazılmadı?", "Why did the daily report not run for a day?"
- Bad: "Building Mona: from keyword gates to a tool loop with guardrails", "One way in: Monafy's market-data pipeline", "Checking production every evening: verification, runbooks and deploy order"

## Body

- Open with the moment the problem was noticed, in prose. Never open with a heading.
- First person, concrete nouns, short paragraphs. Say what you did not know at the time. Professional and precise, never chatty or salesy.
- Case studies run 350–600 words in English and 280–500 in Turkish (Turkish packs more into each word);
  articles 500–1,000 and 400–850. Say each thing once, in the fewest words that keep it clear; no background set-up before the point. Paragraphs stay short: at most 75 English or 60 Turkish words.
- At most one code block per piece, and only where the code itself explains the decision. Most pieces need none.
- Vary the section headings from story to story; name what the section is about instead of "Context" or "Results"
  (the same `<h2>` text in two stories fails the check).
- Outcomes are only what the repository or the owner can show. No invented numbers; a measurement says how it was measured.
- No triads for rhythm, no closing summary paragraph, no "In conclusion".
- Avoid the banned words listed in `StoryStyle::BANNED` (for example guardrails, seamless, robust, leverage, kusursuz, sorunsuz, yolculuk).
- Turkish is written as Turkish, not translated from English. Check it by reading it aloud.

## Summary line

`excerpt` is the answer-first summary shown at the top of the page and used as the description fallback.
One or two sentences that answer the title, 160 characters or fewer where possible.

## Never

Vendor names for market-data sources (Yahoo Finance, FMP), credentials, hostnames, IP addresses, customer data, invented incidents or numbers.

## Brand and market language (20 October 2026)

General identity: Software & AI Engineer · Co-founder / Yazılım ve AI Mühendisi · Kurucu ortak. Connect co-founder to Monafy; keep historical job roles accurate.

General product market language: Türkiye ve ABD borsaları / Turkish and US stock markets. Do not use the BIST-and-Nasdaq pairing as a product description. Specific code identifiers may be explained without overstating coverage.

Word/character ranges are house conventions, not Google ranking requirements. Never pad a story to reach a quota. Automated writing also follows resources/editorial/policy.md and must not imply human editorial review.
