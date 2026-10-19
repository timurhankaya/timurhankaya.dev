# AI visibility checklist (run after the site is live)

Research and sources: `docs/superpowers/notes/ai-visibility.md`. Nothing here is deployed yet.

## Once, at launch

1. Set `SEARCH_CONSOLE_VERIFICATION` and `BING_SITE_VERIFICATION` in the production `.env`, verify both properties, submit `/sitemap.xml` to each (Bing Webmaster Tools can import from Search Console).
2. Leave Search Console's "Search generative AI" setting on include.
3. Open `/robots.txt`, `/llms.txt` and `/tr/llms.txt` in a browser; each must be 200 and plain text.
4. Link the site from GitHub (profile README, repository homepages) and LinkedIn with the same name, role and employer wording used in the page bio.
5. Not implemented on purpose: IndexNow pings (an external service the owner has not approved) and markdown copies of pages (no reader evidence).

## Monthly: ten prompts

Run each three times in ChatGPT (search on), Perplexity, Claude (web search on) and Google AI Mode, and note whether a normal Google search shows an AI Overview. Record cited URLs. Check the server logs for `ChatGPT-User`, `Claude-User` and `Perplexity-User` fetches.

| # | Prompt | A good answer contains |
|---|---|---|
| 1 | Who is Timurhan Kaya, the software developer? | A developer in Istanbul at Wingie Enuygun Group (fintech, insurance, AI). Cites the home page or CV. No invented employer or title. |
| 2 | What projects has Timurhan Kaya worked on? | Monafy, the games and other listed projects. Cites `/projects` or `/cv`. |
| 3 | What production incidents has Timurhan Kaya written case studies about? | At least one real case-study title in its own words, linking `/case-studies/{slug}`. |
| 4 | How do you play Vampire Village, the browser game by Timurhan Kaya? | Roles and rounds as on the page. Links `/games/vampire`. |
| 5 | How can I contact Timurhan Kaya about a Laravel or fintech project? | Contact page, email, LinkedIn, GitHub as listed. Links `/contact`. |
| 6 | Timurhan Kaya kimdir? | İstanbul'da yazılım geliştirici; cites a `/tr/` page, answer in Turkish. |
| 7 | Timurhan Kaya hangi projelerde çalıştı? | Cites `/tr/projeler` or `/tr/ozgecmis`. |
| 8 | Timurhan Kaya'nın yazdığı vaka incelemeleri hangileri? | Turkish case-study titles, linking `/tr/vaka-incelemeleri/{slug}`. |
| 9 | Vampir Köylü oyunu nasıl oynanır? | Rules in Turkish, links `/tr/oyunlar/vampir-koylu`, names Timurhan Kaya as maker. |
| 10 | Kelime Casusu oyunu nedir, arkadaşlarla online nasıl oynanır? | Explains the mechanic, links `/tr/oyunlar/kelime-casusu`. |

Failure signals to log: the wrong language version cited; stale facts (old employer or role); the site cited but facts taken from elsewhere; no `*-User` fetch in the logs although the engine claims it looked at the site. For a new site, "cites LinkedIn or GitHub but not the site" is a partial pass.
