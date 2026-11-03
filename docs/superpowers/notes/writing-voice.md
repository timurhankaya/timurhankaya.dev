# Writing voice for Writing and Case study

`App\Support\StoryStyle` enforces the mechanical rules below over `resources/content/stories/*.json`
(run with `STORY_LINT=1 php artisan test --filter=StoryStyleTest`). Everything else is judgment.

## Iterative review

Owner instruction24 October2026 covers current technical stories too: remove Monafy names/links and story-specific project labeling from public prose, cards and metadata. Keep technical meaning and historical URLs/dates. Retain source provenance privately. This narrow cleanup does not approve pending full rewrites.


Revise one story at a time and submit the complete TR/EN revision for owner approval before applying it or starting another story. Provide admin-only live preview links with `?draft=1` for the exact staged revision, alongside the private readable copy; approval still applies to one exact revision. Improve the writing instructions as concrete feedback arrives. A prompt-engineering explanation may need both a generalized prompt-text example and the application code: show complete illustrative input and expected output, not an unfinished template. Do not publish private production prompts or invent measured results. Keep examples focused: avoid generic filling and missing-data instructions when the technique is already clear from the template and input/output pair. When explaining placeholders, show them in a clearly labelled prompt template and pair every field with a concrete illustrative input and filled answer. This is different from leaving ordinary reading prose unfinished.

## Independent agent review

For each complete TR/EN draft after post 7 (price latency), dispatch a separate reviewer before owner submission. Review the complete prose and private evidence against the current editorial policy and this guide: concrete supported problem/solution, junior-level clarity with developer detail, complete examples, title/summary/SEO scope, unnecessary prose, bilingual consistency and privacy. Review topic value first and record why the supported decision warrants its own story. Insufficient subject value is material even when correctness and readability pass. Keep severity-ranked findings, reviewer identity and the exact stories.json SHA256 in the private revision directory. Resolve material findings and re-review the revised full text. The reviewer does not edit content, publish, start another story or run tests/builds. Unfinished review remains private as agent_review_pending. This review does not replace owner approval.

## Purpose and editorial forms

The reusable writing criteria below apply to current and future interactive and automated articles. The latest owner feedback asks for shorter prose, especially towards the end. These criteria guide selection and review; they do not prescribe a component or paragraph for every concern.

Periodically share good solutions the owner implemented and critical problems they solved, using plain, simple language. Let the reader understand the problem, the chosen solution and its meaningful tradeoff. For transferable technical pieces of this kind, omit Monafy branding and promotion. Use a general data-flow or application problem instead of a price/market label when the domain is unnecessary. Preserve the supported mechanism and concrete examples; source-project names may remain in private evidence. An anonymous article can have project_key=null to remove its project sidebar. A separately requested product/tool analysis can still name the relevant tool. Topic value comes before brevity: an elementary integration fix alone does not need a standalone story. Apply that judgment to existing stories too, and do not repackage a topic the owner rejected for being too basic.

Choose the subject before polishing its prose. A standalone piece needs an actual hard problem or constraint and a supported engineering decision beyond routine implementation. Ordinary permission/validation checks, GET/POST separation or basic integrations can appear as details of a larger solution; they do not justify their own story. Longer text, code, tables and terminology do not change that threshold. Date alignment, missing-value handling, elementary period calculations and ordinary data consistency do not qualify by themselves, even when combined. Examples and a precomputation paragraph do not create a larger engineering problem. Use these only as supporting details of a supported substantive decision. The owner rejected the fund price/return-date subject; do not revive it with a new title. Keep the explanation simple without making the subject elementary.

## Concise technical explanation

- Replace vague process metaphors with observable actions: say which request is awaited, which content is returned, or which work repeats. Expand only the phrase that needs explanation; do not add a terminology lesson or invent precise thresholds absent from evidence.

- Start with the concrete problem and explain the supported change and why it helps. Keep the causal connection across relevant stages; a new example at every stage is unnecessary.
- Write for a junior who can read code. Explain unfamiliar, decision-relevant terms in place; skip generic language/API lessons. Technical depth comes from the mechanism, not the number of terms or paragraphs.
- Choose examples, code and diagrams by what they clarify. Give a complete input/result where needed, then avoid restating the example before and after it. One useful example can suffice. New stories use at least one meaningful existing component, such as a flow, table, callout or code example; keep the surrounding prose clear on its own.
- Keep counts and labels consistent between prose and diagrams. When a diagram shows the complete flow but the prose discusses only selected parts, name that distinction instead of calling both the same number of stages.
- Describe the work or waiting reduced when performance matters. Distinguish network trips from database work and view updates from physical paints. Separate API behavior from application logic. Never invent measured gains; distinguish evidence, illustrative arithmetic and product marketing. Faster bot responses do not prove better real-user Core Web Vitals.
- Name each section's actual subject. Keep paragraphs focused and transitions causal; avoid defensive contrast headings, repeated assurances and unrelated closing material. Put a necessary tradeoff beside its mechanism, not in a compulsory separate section.
- Make a deletion pass: remove a sentence or paragraph if the problem, solution and necessary limits remain clear without it. Preserve enough explanation to follow the decision; do not replace prose with unexplained jargon. No minimum, fixed reduction percentage, paragraph count, four-stage template or mandatory sensor/code example applies. Stop when the explanation is complete.
- Keep the detailed claim/evidence audit and research-process caveats privately. External primary documentation supports mechanisms, not unobserved project results or imported benchmarks. Read relevant current sources when needed.
- The separate reviewer checks natural TR/EN, technical accuracy and avoidable length. Report concrete expendable passages rather than approving solely because claims are correct. Design approval never approves an unseen full revision for publication.

For workflow topics, explain handoffs and verification only where they affect the decision; do not expand every article into a process manual. Prompt-template examples, when necessary, still need complete input/output without revealing private production prompts.

## What a case study is

An evidence-backed account of one real product or engineering problem: what the system looked like, what went wrong or got hard,
which constraints mattered, what was built, which alternatives were rejected and why, what changed afterwards,
and what was learned. It is not a project overview or a brochure; every section is about the problem.
The reader is an engineer or a hiring manager who should finish knowing exactly what was solved and how well it was solved.

Disclosure rules per project (updated after owner review, 20 October 2026):
- Monafy: default to public product behavior and reusable engineering principles. Keep implementation topology, internal service/module names, event topics, database schema, queue/cache keys, operational timings, unpublished roadmap, incidents and business information private. Public methods may be explained where they help the reader; permission to inspect a private repo is not permission to publish its internals.
- Client projects: explain the general problem and decision without client business data, internal system details or customer information.
- Never disclose secrets, private hostnames/IPs, user data or market-data vendor names. Keep evidence references private. If a specific internal detail is essential, leave it out of the draft pending explicit permission.

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

A short engineering note grounded in a real development decision or useful solution. Explain why the problem mattered and how the approach addresses it. It needs a plain title and a prose opening; it does not need a general introduction to the technology.

## Titles

- Name the reader's concrete question or the decision that distinguishes this piece. Sentence case. At most 70 characters; provide a shorter search title when needed. No colon or "from … to …" pattern.
- Read the body as a domain specialist before naming it. A latency article must distinguish transport from waiting; a financial article must preserve the measurement date and period. Do not imply a universal guarantee, a measured improvement or an incident that the body does not establish.
- Compare a few candidate headlines privately, then choose the one that states the article's useful contribution most clearly. Do not publish the candidates or reuse a question/infinitive template across every title.
- Good: "Canlı fiyatlar neden gecikir? Kafka hattında gecikme analizi", "Tek şablondan müşteriye özel SaaS iş akışları", "What a financial metric leaves unexplained".
- Weak: "Kafka fiyat akışında gecikmeyi ölçmek ve azaltmak" (names activities without the finding), "Finansal göstergeleri anlaşılır kılan ürün kararları" (does not name what the reader will understand).
- Published locale slugs stay fixed when a headline is edited.

## Body

- Open with the concrete problem, in prose. Describe a discovery moment only when it is documented. Never open with a heading.
- Direct narration centered on the problem, workflow and result, with concrete nouns and short paragraphs. Avoid first-person I-do/I-check narration in body and summary. Name the agent, service, file or task where useful; use natural passive wording when the actor is immaterial. Do not mechanically turn every verb passive. Preserve factual uncertainty and evidence boundaries. Professional and precise, never chatty or salesy.
- No minimum length. Case-study ceilings are 600 English / 500 Turkish words; article ceilings are 1,000 English / 850 Turkish words. These are upper limits, not targets. Say each thing once and finish when the decision is clear. Paragraphs stay short: at most 75 English or 60 Turkish words.
- At most one code block per piece, and only where the code itself explains the decision. Most pieces need none.
- Vary the section headings from story to story; name what the section is about instead of "Context" or "Results"
  (the same `<h2>` text in two stories fails the check).
- Outcomes are only what the repository or the owner can show. No invented numbers; a measurement says how it was measured.
- No triads for rhythm, no closing summary paragraph, no "In conclusion".
- Do not repeatedly introduce the author as co-founder, engineer or AI specialist in article prose. Use the bio for identity; describe the actual decision in the story.
- Avoid the banned words listed in `StoryStyle::BANNED` (for example guardrails, seamless, robust, leverage, kusursuz, sorunsuz, yolculuk).
- Turkish is written as Turkish, not translated from English. Check it by reading it aloud.
- Avoid “insan” and its inflections in public Turkish prose; choose gerçek kullanıcı, kullanıcı, oyuncu or okuyucu for the actual role. Use real user, player or visitor instead of human in English. Keep bot/user distinctions precise without mechanically replacing words.

## Summary line

`excerpt` is the answer-first summary shown at the top of the page and used as the description fallback.
One or two sentences that give the finding or useful distinction, 160 characters or fewer where possible. Do not spend this space on "nasıl yaptığımı anlatıyorum", "bu yazıda" or "in this article"; state the substance directly.

The excerpt must make sense to someone who has not read the body. State the actual problem or finding; a list of tools or implementation steps does not supply that context. Use technical terms only where their role is clear.

The meta description has a different job: identify the page's specific scope and what a searcher can learn. Write it separately from the excerpt rather than copying the same sentence by default. Include technical terms only when they explain the decision or match the reader's question. Keep each language natural, with the same factual boundaries. Character limits are display conventions, not ranking promises.

## Never

Vendor names for market-data sources (Yahoo Finance, FMP), credentials, hostnames, IP addresses, customer data, invented incidents or numbers.

## Brand and market language (20 October 2026)

General identity: Software & AI Engineer · Tech Co-founder / Yazılım ve AI Mühendisi · Teknik kurucu ortak. Connect co-founder to Monafy in the bio; keep historical job roles accurate. Do not repeat the role in every article.

General product market language: Türkiye ve ABD borsaları / Turkish and US stock markets. Do not use the BIST-and-Nasdaq pairing as a product description. Specific code identifiers may be explained without overstating coverage.

Word ceilings and character ranges are house conventions, not Google ranking requirements. No minimum word count applies. Never pad a story to reach a quota. Automated writing also follows resources/editorial/policy.md and must not imply human editorial review.

## Product links and visual emphasis (owner-approved direction)

The product-link rules below apply only when product context is explicitly in scope. Do not add Monafy links to an unbranded general technical article.


Prefer general, reader-oriented titles that name the useful problem or decision. Explain the Monafy application in the body without turning every title into a brand or internal module name.

Use natural, descriptive links to relevant public Monafy reports, country market pages, glossary entries, methodology sections and fund pages when those pages are publicly accessible. Match the article language. Link to the specific destination that helps at that point in the explanation; do not fill a backlink quota or repeat keyword anchors. Verify the first GET response without following redirects and check the destination HTML language. Link the general Monafy mention to its homepage: https://monafy.com/tr/ in Turkish and https://monafy.com/ in English, never the Features index. Also link directly relevant public pages where they help explain the topic. Check that the anchor names what the destination actually explains. Prefer the specific Sources page for a publication-delay explanation over the general methodology hub. Use canonical destinations; the English root homepage can negotiate browser language, which is a verified homepage exception to record privately rather than a reason to substitute Features. Check body and case-sidebar links. Never link a 404, private account view or unpublished feature. A public glossary entry is a useful alternative when the product page is unavailable.

Emphasize the most important decisions with a few short strong phrases and, where useful, a callout or pull quote. Use the existing flow, layers, responsive table, list and heading styles to explain different relationships. Choose components by meaning across the writing; do not force every component into every story or add decorative code. Keep the introductory prose and the full text readable without diagrams.

## Selected technical depth and working practices

The owner permits useful explanations of parallel sessions, CLAUDE.md rule design and AI-assisted development. Explain task boundaries, context refresh, checkable acceptance criteria, integration and review with source evidence. A tool manual describes tool capability; it does not prove a personal incident or outcome. Label local/unversioned workflow observations separately in private research notes.

Technical articles/case studies may show one short illustrative code example when it explains a decision. Rewrite the example without private paths, names, credentials, schemas or infrastructure details. Focus on the reusable pattern and tradeoff. Do not manufacture a failure story or measured improvement to make an example more dramatic.
