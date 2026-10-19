# AI visibility research note (2026-10-17)

How a small bilingual (EN/TR), server-rendered Laravel portfolio and blog gets found and cited by AI answer engines. Every source was accessed on **2026-10-17**. Source numbers like [S1] point to the "Sources" list. When a claim rests on a secondary source, a search-result snippet, or an archived copy, the text says so.

## Summary

- **The search index decides what gets cited.** Every vendor documents a retrieval step before the answer. Google AI Overviews and AI Mode use the Google index. Copilot uses the Bing search service. ChatGPT search uses "third-party search providers" (the help page links Microsoft's and Shopify's privacy statements) plus its own OAI-SearchBot. Perplexity runs its own index. Claude's web search provider is not named in Anthropic's product docs: Brave Search was added as a web-search subprocessor in March 2025, and Claude-SearchBot is Anthropic's own search crawler. [S1–S4, S6–S11, S13, S14, S23]
- **Training bots do not affect answers.** OpenAI, Google, Apple and Mistral say explicitly that their training bot or token (GPTBot, Google-Extended, Applebot-Extended, MistralAI-Training) is separate from search inclusion. Anthropic describes only the training effect of blocking ClaudeBot. Visibility in answers depends on the search and user-fetch bots. The owner's policy is "allow everything", so the only work is to state that policy explicitly and keep it consistent. [S1, S6, S17, S32, S38]
- **No AI vendor says it reads llms.txt.** Google Search says it ignores the file. The only Google product that checks it is Chrome Lighthouse's "Agentic Browsing" audit, which treats a missing file as "not applicable". A 137K-domain log study found that 97% of llms.txt files got zero requests in a month. The file is cheap and the repo already has it. Expect no citation effect. [S14, S43, S44, S49]
- **The strongest evidence is plain:** be indexed in Google and Bing, serve the main content as server-rendered HTML, and don't send snippet-blocking directives. Third-party mentions have moderate, correlational evidence. Freshness and answer-first writing have vendor guidance and lab studies behind them. Structured data has a controlled 2026 study that found **no** citation lift. Question-style headings and page speed have no evidence specific to AI answers. [S13, S14, S50, S51, S55, S57]
- **Measurement is now possible without paid tools.** Google Search Console's Generative AI performance report (rolled out to all sites by 2026-08-31), Bing Webmaster Tools' AI Performance report (preview since 2026-02-10), Microsoft Clarity citations (GA 2026-05-13), `utm_source=chatgpt.com` on ChatGPT referrals, and user-agent lines in server logs. OpenAI, Anthropic and Perplexity offer no self-serve citation report that I could find. [S4, S16, S26, S31]

---

## 1. How the engines choose and cite pages

| Engine | Index or source (documented) | Own crawler | How citations are chosen (documented) | Site-owner controls |
|---|---|---|---|---|
| **ChatGPT search** | "Third-party search providers, as well as content provided directly by our partners" [S2]. The help article says ChatGPT "rewrites your query into one or more targeted queries" sent to search partners, and links the Microsoft and Shopify privacy statements [S3]. Bing is therefore very likely one provider, but OpenAI does not name it on these pages. | OAI-SearchBot [S1] | Not documented beyond query rewriting and relevance. "Any public website can appear in ChatGPT search" [S4]. | Allow OAI-SearchBot. Use `noindex` to stay out. If the page is disallowed, ChatGPT can still show the link and title [S1, S4]. |
| **Perplexity** | Its own index: "over 200 billion unique URLs" [S11]. No third-party provider is documented. | PerplexityBot (index) and Perplexity-User (live fetch) [S10] | Hybrid lexical and embedding retrieval, then cross-encoder reranking. Documents are split into "self-contained spans, each of which can be individually retrieved and ranked". "Stale content" is prefiltered out. Crawl priority favours "authoritative domains" [S11]. | Allow PerplexityBot and its IP ranges [S10]. Cloudflare reported in 2025 that Perplexity used undeclared crawlers on sites that blocked it; Perplexity disputed this [S12]. |
| **Claude (web search)** | Anthropic's docs say only "The API runs the searches" [S7]. Brave Search was added as a web-search subprocessor on 2025-03-19, and Claude's citations matched Brave's results in a test [S9]. Image results are "powered by Bing" [S8]. **Whether Brave is still the provider in 2026 is unverified** (the Trust Center page renders only with JavaScript). | Claude-SearchBot (search quality) and Claude-User (live fetch) [S6] | Claude decides when to search. Questions about "specific organizations, people, or products that might have changed" trigger a search. "Citations are always enabled for web search." Each citation carries up to 150 characters of `cited_text`. Results include `page_age` [S7]. | Allow Claude-SearchBot and Claude-User. Anthropic says blocking either "may reduce your site's visibility" [S6]. |
| **Google AI Overviews and AI Mode** | The Google Search index. A page "must be indexed and eligible to be shown in Google Search with a snippet". "There are no additional technical requirements" [S13]. | Googlebot | Built on core ranking systems with grounding and "query fan-out" (issuing multiple related searches across subtopics) [S13, S14]. In March 2026, 38% of cited URLs ranked in the top 10 for the original query, down from about 76% in July 2025, which suggests heavy use of fan-out results [S53, third-party measurement]. | `nosnippet`, `data-nosnippet`, `max-snippet`, `noindex` [S13]. A Search Console setting "Exclude my site's links and content from Search generative AI features" exists; sites are included by default [S15]. Google-Extended does **not** affect Search [S17]. |
| **Microsoft Copilot** | The Bing search service. Copilot "generates a search query that it sends to the Bing search service", and the citation section shows the exact queries [S23]. Bing's AI Performance report covers "Microsoft Copilot, AI-generated summaries in Bing, and select partner integrations" [S26]. | bingbot | Bing's February 2026 guidelines name "Generative Engine Optimization" and recommend stating facts directly, consistent entity names, one topic per URL, and key information near the top [S25; the guidelines page itself is JavaScript-rendered, see S24]. | `NOARCHIVE` keeps a page out of Copilot answers and grounding. `NOCACHE` limits Copilot to the URL, title and snippet [S25, S28]. |

Notes:
- Gemini Apps (not Google Search) use Google-Extended to decide whether crawled content may be used for **grounding** as well as training [S17]. If you block Google-Extended, you leave Gemini-app answers but stay in AI Overviews and AI Mode.
- Overlap between assistant citations and the classic top 10 is low. In July 2025 data, 8.0% of ChatGPT in-text citations were in Google's top 10 and 8.1% were in Bing's. Perplexity's overlap with Google's top 10 was 28.6% [S52]. The likely reason is query fan-out: assistants cite pages that rank for sub-queries rather than the literal prompt. *Possibly outdated:* the data was collected in early July 2025.

## 2. AI crawler user agents

Classes: **S** = search or answer indexing, **U** = user-initiated or real-time fetch, **T** = model training or data collection. "robots.txt" means the vendor's own statement. Tokens use the vendor's spelling. Matching is case-insensitive [S41].

| Vendor | robots.txt token | Class | Purpose (vendor's description) | Honours robots.txt? | Blocking removes you from that vendor's answers? | Doc |
|---|---|---|---|---|---|---|
| OpenAI | `OAI-SearchBot` | S | Surfaces websites in ChatGPT search | Yes | **Yes.** Excluded from answers, but "can still appear as navigational links" | [S1] |
| OpenAI | `ChatGPT-User` | U | User actions in ChatGPT, Custom GPTs and GPT Actions | "robots.txt rules may not apply" | Not used to decide search inclusion | [S1] |
| OpenAI | `GPTBot` | T | Content that may be used to train foundation models | Yes | No. Settings are independent. If both bots are allowed, one crawl may serve both | [S1] |
| OpenAI | `OAI-AdsBot` | other | Checks ad landing pages | Not stated | n/a (ads only) | [S1] |
| OpenAI | ChatGPT agent / Atlas | U | Requests are signed with a `Signature-Agent: "https://chatgpt.com"` header (RFC 9421) and have no distinct UA token on the bots page | n/a | n/a | [S5]: search snippet only, page returned 403 |
| Anthropic | `Claude-SearchBot` | S | "navigates the web to improve search result quality" | Yes (the page makes one statement for all three bots) | **Yes.** "may reduce your site's visibility and accuracy in user search results" | [S6] |
| Anthropic | `Claude-User` | U | Fetches pages when users ask Claude questions | Yes (same collective statement) | **Yes.** "may reduce your site's visibility for user-directed web search" | [S6] |
| Anthropic | `ClaudeBot` | T | Collects content "that could potentially contribute to their training" | Yes. Also supports `Crawl-delay` | The only documented effect is exclusion from training | [S6] |
| Anthropic | `anthropic-ai`, `Claude-Web` | (legacy) | **Not in the current Anthropic page.** Status unconfirmed | Unknown | Unknown | none |
| Perplexity | `PerplexityBot` | S | Surfaces and links sites in Perplexity results; "not used to crawl content for AI foundation models" | Yes [S10, S11]. Disputed by Cloudflare's 2025 tests [S12] | Perplexity says to allow it so your site "appears in search results" | [S10] |
| Perplexity | `Perplexity-User` | U | Visits a page to answer a user's question and may link it | "generally ignores robots.txt rules" | n/a | [S10] |
| Google | `Googlebot` | S | Google Search, including all Search features | Yes | **Yes.** Removes the site from Search, including AI Overviews and AI Mode | [S13, S17] |
| Google | `Google-Extended` | T (control token) | No separate UA. Controls use of crawled content for Gemini training and for grounding in Gemini Apps and Vertex AI | Yes (it is a robots token) | No effect on Google Search or ranking. Does remove content from Gemini-app grounding | [S17] |
| Google | `Google-Agent` | U | "agents hosted on Google infrastructure to navigate the web and perform actions upon user request". Testing Web Bot Auth as `agent.bot.goog` | User-triggered fetchers "generally ignore robots.txt rules" | n/a | [S18, S19] |
| Google | `Google-GeminiNotebook` (formerly `Google-NotebookLM`, supported "until August 2026") | U | Fetches URLs that users add as sources | Generally ignores | n/a | [S18] |
| Google | `GoogleOther` | T (generic) | One-off research crawls; "don't affect any specific product" | Yes | No | [S17] |
| Apple | `Applebot` | S, and also T | Search in Spotlight, Siri and Safari; "may also be used to help train Apple foundation models". Renders JavaScript | Yes. Follows Googlebot rules if Applebot is not named. Ignores `Crawl-delay` | **Yes**, for Apple's search features | [S32] |
| Apple | `Applebot-Extended` | T (control token) | Opt out of training Apple's foundation models. "does not crawl webpages" | Yes | No. "can still be included in search results" | [S32] |
| Microsoft | `bingbot` | S | The Bing index, which Copilot grounding uses | Yes ("respects all content owner preferences expressed through robots.txt") | **Yes.** Copilot grounds on Bing [S23, S26]. ChatGPT probably does too through its providers (inferred, see section 1) | [S26, S29, S30] |
| Common Crawl | `CCBot` | T | Open repository of web crawl data | Yes | No direct answer engine. The page does not mention AI training (its use in training sets is common knowledge but **not stated by the source**) | [S33] |
| Meta | `meta-externalagent` | T | "training foundation AI models" and product indexing | Yes (implied by the robots example) | Not stated | [S34] |
| Meta | `meta-webindexer` | S | "improve Meta AI search result quality". Allowing it "helps us cite and link to your content" | Yes (implied) | **Yes**, for Meta AI citations | [S34] |
| Meta | `meta-externalfetcher` | U | Fetches individual links on user request | "may bypass robots.txt rules" | n/a | [S34] |
| Meta | `facebookexternalhit` | other | Link previews when a URL is shared | Mostly, but "might bypass robots.txt" for security checks | n/a | [S34] |
| ByteDance | `Bytespider` | T (per Cloudflare) | **Unconfirmed.** No ByteDance documentation found. Cloudflare lists it as "AI Crawler". Third-party reports say it ignores robots.txt | Unconfirmed | Unknown | [S40] only |
| Amazon | `Amazonbot` | T | Improves Amazon products; "may be used to train Amazon AI models" | Yes. No `Crawl-delay`. Page-level `noarchive` = no training | Not stated | [S35] |
| Amazon | `Amzn-SearchBot` | S | Search in Amazon products such as Alexa. Not used for training | Yes | **Yes.** Allowing it makes content eligible | [S35] |
| Amazon | `Amzn-User` | U | Live fetches for user actions (for example, Alexa questions) | "may not follow all robots.txt directives" | n/a | [S35] |
| DuckDuckGo | `DuckAssistBot` | U (real-time answer fetch) | Fetches pages in real time for DuckAssist answers. Not used for training | Yes. Takes effect after 72 hours | **Yes** for DuckAssist. "does not impact organic search rankings" | [S36] |
| DuckDuckGo | `DuckDuckBot` | S | DuckDuckGo search | "respects WWW::RobotRules" | Organic results | [S37] |
| Cohere | none | n/a | Cohere says it does not use bots to crawl for training "at this time". The bot table is "N/A". `cohere-ai` and `cohere-training-data-crawler` appear only on third-party lists. **Unconfirmed** | n/a | n/a | [S39] |
| Mistral | `MistralAI-User` | U | Fetches pages for user actions in Mistral's assistant (which the page calls "Vibe") | robots.txt "governs which sites these user requests can be made to" | Not stated | [S38] |
| Mistral | `MistralAI-Index` | S | Crawls for Mistral search. Not used for training | Not specified | Not stated | [S38] |
| Mistral | `MistralAI-Training` | T | Builds training datasets | Yes ("can disallow") | No (it is "not used for search indexing") | [S38] |

Full UA strings are on each vendor page. Ones I checked: OAI-SearchBot `…compatible; OAI-SearchBot/1.4; +https://openai.com/searchbot`, GPTBot `…compatible; GPTBot/1.4; +https://openai.com/gptbot`, ChatGPT-User `…compatible; ChatGPT-User/1.0; +https://openai.com/bot` [S1]; PerplexityBot `…compatible; PerplexityBot/1.0; +https://perplexity.ai/perplexitybot`, Perplexity-User `…compatible; Perplexity-User/1.0; +https://perplexity.ai/perplexity-user` [S10]; bingbot `…compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm) Chrome/W.X.Y.Z Safari/537.36` [S29]; CCBot `CCBot/2.0 (https://commoncrawl.org/faq/)` [S33]; DuckAssistBot `DuckAssistBot/1.2; (+http://duckduckgo.com/duckassistbot.html)` [S36]; Amazon's three bots `…compatible; Amazonbot/0.1)` and the same pattern for the other two [S35]; Mistral's three bots `…compatible; MistralAI-User/1.0; +https://docs.mistral.ai/robots)` and the same pattern [S38]; Meta's bots `meta-externalagent/1.1`, `meta-webindexer/1.1`, `meta-externalfetcher/1.1` [S34]. **Anthropic's page gives no full UA strings**, so none are listed here.

## 3. llms.txt

**The proposal.** Jeremy Howard (Answer.AI) proposed it on 2024-09-03. The page is now "v2", last modified 2026-08-10. The format is an H1 with the site name ("the only required section"), an optional blockquote summary, free markdown without headings, then H2 sections of `[name](url): notes` link lists. An H2 named "Optional" holds links an agent can skip. v2 adds that the file may sit at a sub-path and covers URLs below it, defines `.md` mirrors of pages, and discovery through `rel="alternate" type="text/markdown"` and `rel="describedby"` [S42].

**Who says they read it.**
- No AI answer vendor. The crawler docs of OpenAI, Anthropic, Perplexity, Google, Apple, Meta, Amazon, DuckDuckGo and Mistral [S1, S6, S10, S17, S32, S34–S38] do not mention llms.txt. All of them point to robots.txt.
- llmstxt.org lists tools that **publish or generate** the file: Mintlify, GitBook, Yoast, VitePress and Docusaurus plugins. It also says OpenAI, Anthropic and Gemini publish llms.txt files for their developer docs. Publishing a file is not the same as reading other sites' files [S42].
- Chrome **Lighthouse** has an `llms-txt` audit in its "Agentic Browsing" category (doc updated 2026-05-05). It fails only when fetching the file gives a server error. A 404 counts as "Not Applicable", and "providing the file is optional at the moment" [S43]. SEJ reports this shipped by default in Lighthouse 13.3 [S44].

**What Google said.**
- Google's generative-AI optimization guide (updated 2026-07-10): "You don't need to create new machine readable files, AI text files, markup, or Markdown to appear in Google Search". Doing so "will neither harm nor help your site's visibility or rankings in Google Search, as Google Search ignores them" [S14].
- John Mueller, 2025-04-17: llms.txt is "comparable to the keywords meta tag", "AFAIK none of the AI services have said they're using LLMs.TXT", and bots "don't even check for it" [S45]. In 2026-02 he called converting pages to markdown for bots "such a stupid idea" [S47]. In 2026-06 he said you "can't trust what is here as a way of differentiating between different websites" [S48].
- Gary Illyes and Amir Taboul (Search Central Live APAC, reported 2025-07-23): Google does not plan to adopt llms.txt. This is the reporter's summary; no direct quote was given [S46].
- In December (year not stated in the source), an llms.txt file appeared briefly on Search Central and stayed on developer.chrome.com and web.dev. It was probably a CMS deployment, not a policy [S44].

**Evidence on effect.**
- *Usage (log data, single vendor):* Ahrefs, 2026-06-15, covered 137,210 domains in May 2026. 28% published a file. **97% of those files got zero requests.** Retrieval bots (OAI-SearchBot, PerplexityBot, Claude's search crawler) made "only a couple hundred fetches" combined. GPTBot and Claude-Code were the largest AI fetchers. No AI bot requested a missing llms.txt. The authors call the file "largely decoration" for AI search and see coding and agent tools as its real audience [S49].
- *Citations or ranking:* **No primary evidence that llms.txt affects citations or ranking.** A correlational study by SE Ranking (about 300K domains) reportedly found no relationship, but I found only secondary coverage of it. Treat that as unverified.
- **Verdict: absent evidence for citations, weak evidence of any machine reading at all.**

## 4. Content and technical patterns: evidence

Strength scale: **Strong** = vendor documentation states the mechanism, or a large measurement confirms it. **Moderate** = a controlled study or a large observational study, or vendor guidance plus a mechanism. **Weak** = vendor advice without data, a lab study far from production, or correlation only. **None found** = folklore.

| Pattern | Evidence | Strength |
|---|---|---|
| **Indexed in Google and Bing (and crawlable by the vendors' search bots)** | Google requires an indexed, snippet-eligible page [S13]. Copilot queries Bing [S23]. ChatGPT uses third-party providers plus OAI-SearchBot [S2–S4]. Perplexity and Anthropic say to allow their search bots [S6, S10]. | **Strong** |
| **No snippet-limiting directives** (`nosnippet`, `max-snippet:0`, `noarchive`/`nocache` for Bing) | Google [S13], Bing [S25, S28], Apple (`nosnippet` excludes content from AI answers) [S32], Amazon (`noarchive` = no training) [S35] | **Strong** (vendor docs) |
| **Server-rendered HTML with the main content in text** | Vercel measured (published 2024-12-17) that GPTBot, ClaudeBot, Meta-ExternalAgent, Bytespider and PerplexityBot fetch but do **not execute** JavaScript. Googlebot, Gemini and Applebot render it [S57]. Google: keep "important content in text form" [S13] and "use semantic HTML when possible" [S14]. *Possibly outdated:* a 2024 measurement, not re-verified. | **Strong** for "main content must be in the HTML" |
| **Third-party mentions (earned media)** | Chen et al. 2025 (arXiv, API models, US/CA, commercial verticals): AI engines cited earned third-party sources 69–92% of the time, against a more balanced mix on Google [S55]. Ahrefs (75K brands, 2025-05-26): branded web mentions had the strongest correlation with AI Overview mentions (Spearman 0.664; backlinks 0.218), "correlation ≠ causation" [S51]. Google: seeking "inauthentic 'mentions'" isn't helpful [S14]. | **Moderate** (observational, consistent) |
| **Language-specific pages** (separate EN and TR URLs) | Chen et al.: GPT's cited domains overlap "near zero" across languages, so it switches site ecosystems by language. Claude is more stable [S55]. hreflang itself has **no** AI-specific evidence. | **Moderate** (one study) |
| **Freshness and visible dates** | Perplexity prefilters "stale content" and estimates per-URL update frequency [S11]. Claude's search results carry `page_age` [S7]. Bing: "Keep content current" and use IndexNow [S26]. Lab study: LLM rerankers moved top-10 mean publication year forward by up to 4.78 years after date injection (7 models, TREC DL) [S56]. Google byline-date rules: a visible date that matches `datePublished`/`dateModified` [S22]. | **Moderate** (mechanism plus lab study, no production test) |
| **Answer-first summary and self-contained sections, with concrete facts, figures and quotes** | Bing guidelines: key information near the top, facts stated directly [S25]. Perplexity ranks "self-contained spans" [S11]. GEO (KDD 2024): adding quotations, statistics and citations raised visibility by up to about 40% in a simulated engine (GPT-3.5 over Google top 5) and by up to about 22–37% on Perplexity with uploaded sources. Keyword stuffing did worse than baseline [S54]. Google: no need to "chunk" or write specially for AI [S14]. *Possibly outdated:* GEO used 2023-era models. | **Weak to moderate** |
| **Clear entity and author information** (same name everywhere, `sameAs`, author line) | Bing guidelines: consistent entity names improve "grounding visibility and citation accuracy" (search snippet of [S24] and [S25]). Google's AI guide doesn't mention authors [S14]. No controlled study found. | **Weak** (vendor advice only) |
| **Structured data (JSON-LD)** | Ahrefs 2026-05-11: matched difference-in-differences on 1,885 pages that added JSON-LD, against about 4,000 controls. ChatGPT +2.2% and AI Mode +2.4% (both indistinguishable from zero). AI Overviews −4.6% (small, unexplained) [S50]. Google: "no special schema.org structured data that you need to add" [S13]. Bing's Fabrice Canel said in 2025 that schema helps Microsoft's LLMs [S59, secondary and not fetched]. | **Weak/negative** for citations. Keep it for rich results and entity disambiguation |
| **Headings phrased as questions, FAQ sections** | Only Bing's AI Performance post recommends "clear headings, tables, and FAQ sections" [S26]. Google: no need to rewrite for AI [S14]. No study found. | **Weak** (one vendor's advice) |
| **Page speed** | Google lists "low latency" under page experience in general [S14]. Nothing specific to AI citations. Real-time fetchers (ChatGPT-User, Claude-User, Perplexity-User) need a fast response in principle, but no vendor documents a timeout. | **None found** |
| **llms.txt, markdown mirrors** | See section 3. | **None found** |

## 5. Measuring whether the site is cited

1. **Vendor reports.**
   - **Google Search Console, Generative AI performance report:** impressions of your links in AI Overviews and AI Mode, by page, country and device. Rolled out "to all websites worldwide" by 2026-08-31. It stays empty for sites without enough AI impressions [S16]. Also confirm the "Search generative AI" setting is on "include" (the default) [S15].
   - **Bing Webmaster Tools, AI Performance** (preview since 2026-02-10): total citations, cited pages, and "grounding queries" (a sample) across Copilot, Bing AI summaries and partners [S26]. June 2026 added Intents, Topics, Citation Share and Compare [S27, search result only].
   - **Microsoft Clarity citations** (GA 2026-05-13): page citations, grounding queries and AI referral share. Verify through BWT or GSC [S31]. SEJ reads the coverage as Microsoft surfaces only (Copilot, Bing), not ChatGPT, Gemini or Perplexity [S60].
   - No equivalent self-serve report found for OpenAI, Anthropic or Perplexity.
2. **Referrals.** ChatGPT appends `utm_source=chatgpt.com` [S4]. Other engines' referrers (perplexity.ai, copilot.microsoft.com, gemini.google.com, claude.ai) are **not vendor-documented**. Check them in analytics as observed values.
3. **Server logs.** User-initiated fetchers are the best log signal that a real question led to your page: `ChatGPT-User`, `Claude-User`, `Perplexity-User`, `Google-Agent`, `meta-externalfetcher`, `MistralAI-User`, `Amzn-User`, `DuckAssistBot`. Index bots (`OAI-SearchBot`, `Claude-SearchBot`, `PerplexityBot`, `bingbot`, `Googlebot`, `Applebot`, `meta-webindexer`) show eligibility, not citation. Example check:
   ```sh
   grep -Eio 'OAI-SearchBot|ChatGPT-User|GPTBot|Claude-SearchBot|Claude-User|ClaudeBot|PerplexityBot|Perplexity-User|Googlebot|Google-Agent|bingbot|Applebot|DuckAssistBot|meta-webindexer|meta-externalagent|meta-externalfetcher|Amazonbot|Amzn-SearchBot|Amzn-User|MistralAI-[A-Za-z]+|CCBot|Bytespider' access.log | sort | uniq -c | sort -rn
   ```
   User agents can be spoofed. Verify with published IP lists: `openai.com/searchbot.json`, `/gptbot.json`, `/chatgpt-user.json` [S1]; `claude.com/crawling/bots.json` [S6]; `perplexity.com/perplexitybot.json`, `/perplexity-user.json` [S10]; Google's `common-crawlers.json` and `user-triggered-agents.json`, or reverse DNS on googlebot.com, google.com or googleusercontent.com [S20]; `search.developer.apple.com/applebot.json` or `*.applebot.apple.com` [S32]; `index.commoncrawl.org/ccbot.json` [S33]; `duckduckgo.com/duckduckbot.json` [S37]. For bingbot, use reverse DNS to `search.msn.com` [S61, search snippet only].
4. **Manual prompts.** Use a fixed list (section 6d). Run each prompt 3 times per engine with web search on, in a logged-out or private session where the engine allows it. Record the date, the engine, whether the site was cited, which URL, and whether the facts were right. Within minutes, check the logs for the matching `*-User` fetch. Answers are probabilistic: Ahrefs notes fan-out queries "change from run to run" [S53].

---

## Sources

All accessed 2026-10-17.

- [S1] OpenAI, "Overview of OpenAI crawlers", https://developers.openai.com/api/docs/bots (redirected from platform.openai.com/docs/bots; no revision date on page)
- [S2] OpenAI, "Introducing ChatGPT search" (2024-10-31), https://openai.com/index/introducing-chatgpt-search/ (direct fetch returned 403; read via Wayback snapshot 2025-12-28)
- [S3] OpenAI Help, "Searching the web with ChatGPT", https://help.openai.com/en/articles/9237897 (403; read via Wayback snapshot 2026-10-11, "Updated: 2 months ago")
- [S4] OpenAI Help, "Publishers and developers FAQ", https://help.openai.com/en/articles/12627856-publishers-and-developers-faq (403; Wayback snapshot 2026-10-04)
- [S5] OpenAI Help, "ChatGPT agent allowlisting", https://help.openai.com/en/articles/11845367-chatgpt-agent-allowlisting (403 directly and via Wayback; content known only from search snippets)
- [S6] Anthropic, "Does Anthropic crawl data from the web, and how can site owners block the crawler?", https://support.claude.com/en/articles/8896518-does-anthropic-crawl-data-from-the-web-and-how-can-site-owners-block-the-crawler
- [S7] Anthropic, "Web search tool", https://platform.claude.com/docs/en/agents-and-tools/tool-use/web-search-tool
- [S8] Anthropic Help, "Enabling and using web search", https://support.claude.com/en/articles/10684626-enabling-and-using-web-search
- [S9] Simon Willison, "Anthropic use Brave" (2025-03-21), https://simonwillison.net/2025/Mar/21/anthropic-use-brave/
- [S10] Perplexity, "Perplexity crawlers", https://docs.perplexity.ai/guides/bots
- [S11] Perplexity Research, "Architecting and evaluating an AI-first Search API" (2025-09-25), https://www.perplexity.ai/hub/blog/architecting-and-evaluating-an-ai-first-search-api
- [S12] Cloudflare, "Perplexity is using stealth, undeclared crawlers to evade website no-crawl directives" (2025-08-04), https://blog.cloudflare.com/perplexity-is-using-stealth-undeclared-crawlers-to-evade-website-no-crawl-directives/
- [S13] Google Search Central, "AI features and your website" (updated 2025-12-10), https://developers.google.com/search/docs/appearance/ai-features
- [S14] Google Search Central, "Optimizing for generative AI search" (updated 2026-07-10), https://developers.google.com/search/docs/fundamentals/ai-optimization-guide
- [S15] Search Console Help, "Search generative AI" setting, https://support.google.com/webmasters/answer/16908024
- [S16] Search Console Help, "Generative AI performance report", https://support.google.com/webmasters/answer/16984139
- [S17] Google, "Google's common crawlers" (updated 2026-10-15), https://developers.google.com/search/docs/crawling-indexing/google-common-crawlers
- [S18] Google, "Google's user-triggered fetchers" (updated 2026-08-19), https://developers.google.com/search/docs/crawling-indexing/google-user-triggered-fetchers
- [S19] Google, "Google-Agent" (updated 2026-08-19), https://developers.google.com/crawling/docs/crawlers-fetchers/google-agent
- [S20] Google, "Verifying Googlebot and other Google crawlers" (updated 2026-03-20), https://developers.google.com/search/docs/crawling-indexing/verifying-googlebot
- [S21] Google, "How Google interprets the robots.txt specification" (updated 2026-08-31), https://developers.google.com/search/docs/crawling-indexing/robots/robots_txt
- [S22] Google, "Byline dates" (updated 2025-12-10), https://developers.google.com/search/docs/appearance/publication-dates
- [S23] Microsoft Learn, "Data, privacy, and security for web search in Microsoft Copilot and Microsoft Copilot Chat" (2026-08-18), https://learn.microsoft.com/en-us/microsoft-365/copilot/manage-public-web-access
- [S24] Bing, "Webmaster Guidelines", https://www.bing.com/webmasters/help/webmaster-guidelines-30fba23a (renders only with JavaScript and could not be read directly; quotes come from S25 and search-engine snippets of this page)
- [S25] Search Engine Journal, "Bing Adds GEO To Official Guidelines, Expands AI Abuse Definitions" (2026-02-27), https://www.searchenginejournal.com/bing-adds-geo-to-official-guidelines-expands-ai-abuse-definitions/568442/
- [S26] Bing Webmaster Blog, "Introducing AI Performance in Bing Webmaster Tools Public Preview" (2026-02-10), https://blogs.bing.com/webmaster/February-2026/Introducing-AI-Performance-in-Bing-Webmaster-Tools-Public-Preview
- [S27] Bing Search Blog, "New AI Visibility Insights in Bing Webmaster Tools: Intents, Topics, Citation Share, Compare" (June 2026), https://blogs.bing.com/search/June-2026/New-AI-Visibility-Insights-in-Bing-Webmaster-Tools-Intents-Topics-Citation-Share-Compare (search result only, not fetched)
- [S28] Bing Webmaster Blog, "Announcing new options for webmasters to control usage of their content in Bing Chat" (2023-09-22), https://blogs.bing.com/webmaster/september-2023/Announcing-new-options-for-webmasters-to-control-usage-of-their-content-in-Bing-Chat
- [S29] Bing Webmaster Blog, "Announcing user-agent change for Bing crawler bingbot" (2022-04-28), https://blogs.bing.com/webmaster/2022/4/Announcing-user-agent-change-for-Bing-crawler-bingbot/
- [S30] Bing, "Overview of Bing crawlers (user agents)", https://www.bing.com/webmasters/help/which-crawlers-does-bing-use-8c184ec0 (renders only with JavaScript; content not verified)
- [S31] Microsoft Clarity, "Citations now generally available" (2026-05-13), https://clarity.microsoft.com/blog/citations-now-generally-available/
- [S32] Apple, "About Applebot" (published 2026-09-04), https://support.apple.com/en-us/119829
- [S33] Common Crawl, "CCBot", https://commoncrawl.org/ccbot
- [S34] Meta for Developers, "Meta web crawlers", https://developers.facebook.com/docs/sharing/webmasters/web-crawlers
- [S35] Amazon, "Amazonbot", https://developer.amazon.com/amazonbot
- [S36] DuckDuckGo, "DuckAssistBot", https://duckduckgo.com/duckduckgo-help-pages/results/duckassistbot
- [S37] DuckDuckGo, "DuckDuckBot", https://duckduckgo.com/duckduckgo-help-pages/results/duckduckbot
- [S38] Mistral AI, "Robots", https://docs.mistral.ai/robots
- [S39] Cohere, "Cohere web crawlers", https://docs.cohere.com/docs/cohere-web-crawlers
- [S40] Cloudflare, "AI Crawl Control: bots reference" (updated 2026-04-23), https://developers.cloudflare.com/ai-crawl-control/reference/bots/
- [S41] IETF, RFC 9309 "Robots Exclusion Protocol", https://www.rfc-editor.org/rfc/rfc9309.html
- [S42] llmstxt.org, "The /llms.txt file" (v2, modified 2026-08-10), https://llmstxt.org/
- [S43] Chrome for Developers, Lighthouse "llms.txt" audit (updated 2026-05-05), https://developer.chrome.com/docs/lighthouse/agentic-browsing/llms-txt
- [S44] Search Engine Journal, "Google's llms.txt Guidance Depends On Which Product You Ask" (2026-05-20), https://www.searchenginejournal.com/googles-llms-txt-guidance-depends-on-which-product-you-ask/575431/
- [S45] Search Engine Journal, "Google says llms.txt comparable to keywords meta tag" (2025-04-17), https://www.searchenginejournal.com/google-says-llms-txt-comparable-to-keywords-meta-tag/544804/ (original: https://www.reddit.com/r/TechSEO/comments/1k0kcx9/llmtxt_where_are_we_at/)
- [S46] Search Engine Journal, "Google Search Central APAC 2025: everything from day one" (2025-07-23), https://www.searchenginejournal.com/google-search-central-apac-2025-everything-day-one/551634/
- [S47] Search Engine Journal, "Google's Mueller calls markdown-for-bots idea 'a stupid idea'" (2026-02-04), https://www.searchenginejournal.com/googles-mueller-calls-markdown-for-bots-idea-a-stupid-idea/566598/
- [S48] Search Engine Journal, "Google's Mueller says llms.txt can't help LLMs differentiate sites" (2026-06-15), https://www.searchenginejournal.com/googles-mueller-says-llms-txt-cant-help-llms-differentiate-sites/579304/
- [S49] Ahrefs, "We analyzed 137K sites: 97% of llms.txt files never get read" (2026-06-15), https://ahrefs.com/blog/llmstxt-study/
- [S50] Ahrefs, schema and AI citations study (2026-05-11), https://ahrefs.com/blog/schema-ai-citations/
- [S51] Ahrefs, "An analysis of AI Overview brand visibility factors (75K brands studied)" (2025-05-26), https://ahrefs.com/blog/ai-overview-brand-correlation/
- [S52] Ahrefs, "Only 12% of AI cited URLs rank in Google's top 10 for the original prompt" (2025-08-11), https://ahrefs.com/blog/ai-search-overlap/
- [S53] Ahrefs, AI Overview citations and top 10 (2026-03-02), https://ahrefs.com/blog/ai-overview-citations-top-10/
- [S54] Aggarwal et al., "GEO: Generative Engine Optimization", KDD 2024, https://arxiv.org/abs/2311.09735 (body: https://arxiv.org/html/2311.09735v3)
- [S55] Chen, Wang, Chen, Koudas, "Generative Engine Optimization: How to Dominate AI Search" (2025-09-10), https://arxiv.org/abs/2509.08919 (body: https://arxiv.org/html/2509.08919v1; only the first 100K characters were read, so the freshness and limitations sections were not)
- [S56] Fang et al., "Do Large Language Models Favor Recent Content? A Study on Recency Bias in LLM-Based Reranking" (v1 2025-09-14, v2 2026-09-21), https://arxiv.org/abs/2509.11353
- [S57] Vercel, "The rise of the AI crawler" (2024-12-17), https://vercel.com/blog/the-rise-of-the-ai-crawler
- [S58] IndexNow, "FAQ", https://www.indexnow.org/faq
- [S59] Search Engine Land, "Microsoft Bing/Copilot use schema for its LLMs" (March 2025), https://searchengineland.com/microsoft-bing-copilot-use-schema-for-its-llms-453455 (403; known from search results only)
- [S60] Search Engine Journal, "Microsoft Clarity now shows grounding queries behind AI citations" (2026-05-21), https://www.searchenginejournal.com/microsoft-clarity-now-shows-grounding-queries-behind-ai-citations/575279/
- [S61] Bing, "How to verify Bingbot", https://www.bing.com/webmasters/help/how-to-verify-bingbot-3905dc26 (renders only with JavaScript; the reverse DNS rule comes from a search-engine snippet)

---

## 6. Decisions for the implementation

Current state of the repo, checked 2026-10-17:
- `SitemapController::robots()` emits one `User-agent: *` group with `Allow: /` and disallow rules for `/admin`, `/livewire`, `/preview/` and `/tr/onizleme/`.
- `LlmsController` serves `/llms.txt` and `/tr/llms.txt`.
- `StructuredData` emits `Person` with `sameAs` (LinkedIn, GitHub), and `BlogPosting`/`TechArticle` with `dateModified`.
- Indexable pages send `max-snippet:-1`. Only `noindex` pages carry `noarchive`.

### (a) robots.txt user-agent tokens (owner policy: all allowed, training included)

**Rule that matters:** a crawler that finds a group naming it ignores the `*` group (Google: "User agent specific groups and global groups (`*`) are not combined" [S21]; RFC 9309 §2.2.1 [S41]). Every named group must therefore **repeat the same Disallow lines** as `*`. Otherwise named bots would be allowed into `/admin`, `/livewire` and the preview paths.

Implementation:
- Build both groups from one PHP rules array so they cannot drift.
- Keep comment lines (allowed anywhere by RFC 9309's grammar).
- Put no blank lines inside a group.
- Add no `Crawl-delay` (most of these bots ignore it).
- Add a Pest test asserting that both groups carry identical rules and that every token below is present.

```
# Search and answer indexing
User-agent: Googlebot           # Google Search, AI Overviews, AI Mode
User-agent: bingbot             # Bing index; Copilot grounding; ChatGPT search providers (inferred)
User-agent: OAI-SearchBot       # ChatGPT search
User-agent: Claude-SearchBot    # Claude web search quality
User-agent: PerplexityBot       # Perplexity index
User-agent: Applebot            # Siri, Spotlight, Safari search (also may train Apple models)
User-agent: DuckDuckBot         # DuckDuckGo search
User-agent: Amzn-SearchBot      # Alexa / Amazon search experiences
User-agent: meta-webindexer     # Meta AI search citations
User-agent: MistralAI-Index     # Mistral search
# User-initiated or real-time fetchers (several ignore robots.txt; listed to state the policy)
User-agent: ChatGPT-User
User-agent: Claude-User
User-agent: Perplexity-User
User-agent: Google-Agent
User-agent: DuckAssistBot
User-agent: meta-externalfetcher
User-agent: MistralAI-User
User-agent: Amzn-User
# Model training and data collection (allowed by owner policy)
User-agent: GPTBot
User-agent: ClaudeBot
User-agent: Google-Extended     # control token, also Gemini-app grounding
User-agent: Applebot-Extended   # control token
User-agent: CCBot
User-agent: meta-externalagent
User-agent: Amazonbot
User-agent: MistralAI-Training
User-agent: Bytespider          # unconfirmed: no ByteDance documentation
Allow: /
Disallow: /admin
Disallow: /livewire
Disallow: /preview/
Disallow: /tr/onizleme/

User-agent: *
Allow: /
Disallow: /admin
Disallow: /livewire
Disallow: /preview/
Disallow: /tr/onizleme/

Sitemap: https://timurhankaya.dev/sitemap.xml
```

Deliberately not listed:
- `anthropic-ai` and `Claude-Web`: not in Anthropic's current docs.
- `cohere-ai` and `cohere-training-data-crawler`: Cohere says it runs no crawlers.
- `FacebookBot`: not on Meta's current page.
- `OAI-AdsBot`: ads only.
- `GoogleOther` and `Google-CloudVertexBot`: no answer-engine effect.
- `Google-GeminiNotebook`: user-triggered and ignores robots.txt.

All of these still fall under `*`. Inline `#` comments after a value are allowed by RFC 9309. If a parser ever chokes on them, move the comments to their own lines.

### (b) /llms.txt

**Recommendation:** keep it. It already exists and costs nothing to maintain because it is generated from the published posts. Do **not** expect any citation or ranking effect (section 3). Do **not** build `.md` mirrors of pages: there is no reader evidence, Google says it's unnecessary [S14], and it is a second copy that can go stale.

Contents. The current controller already matches this shape:
- `# Timurhan Kaya` (the H1 is required [S42]).
- A one-sentence blockquote: software developer in Istanbul at Wingie Enuygun Group; corporate platforms, fintech and AI features; writes about real incidents. Use the same wording as the `Person` JSON-LD and the home page, so the entity description is identical everywhere.
- One short paragraph: the site is bilingual. English lives at `/`, Turkish at `/tr/` with its own `/tr/llms.txt`. Contact through the contact page, LinkedIn and GitHub (the same URLs as `sameAs`).
- `## Writing`: every published article as `[Title](absolute URL): excerpt`.
- `## Case studies`: every published case study, same format.
- `## Projects and CV`: projects page and CV.
- `## Contact`: contact page.
- `## Optional`: the games pages, and in the root file a link to `/tr/llms.txt`.

Checks:
- Root `/llms.txt` must always be English, whatever the `Accept-Language` header or cookies say.
- `/tr/llms.txt` lists only `/tr/` URLs. Spec v2 lets a sub-path file cover its subtree [S42].
- Links use markdown `[text](url)` (the Lighthouse audit expects this, per secondary reports).
- Never list admin, preview or room URLs.
- Return 200 with `text/plain` or `text/markdown` in UTF-8, never 5xx: a server error is the only Lighthouse failure [S43].

### (c) On-page and technical patterns, ranked by evidence

1. **Indexed in Google and Bing (strong).** Verify the site in Search Console (`SEARCH_CONSOLE_VERIFICATION` exists) **and** Bing Webmaster Tools (its UI offers import from Search Console, as the UI strings in S24 show). Submit `sitemap.xml` to both. Leave the GSC "Search generative AI" setting on include [S15].
2. **Keep snippet directives open (strong).** Indexable pages keep `max-snippet:-1` and never get `nosnippet`, `noarchive` or `nocache`. Add a test so a future change can't add them by accident [S13, S25, S28, S32].
3. **Server-rendered main content (strong; already true).** Keep the game index pages' descriptive text and every story body in the initial HTML, not behind Livewire or JavaScript [S57].
4. **Freshness signals (moderate).** Show a visible "Updated" date next to the published date when `updated_at` differs, and keep it equal to JSON-LD `dateModified` [S22]. Keep `lastmod` honest (already done). Ping **IndexNow** on publish and update: it reaches Bing (so Copilot, and ChatGPT through its providers), Yandex, Seznam, Naver, Yep and Amazon. Google is not listed [S26, S58].
5. **Answer-first summary and self-contained sections with concrete facts (weak to moderate).** Use the `excerpt` as a rendered opening paragraph (already planned). Keep each section readable on its own, with real numbers, named technologies and quoted error messages where they're true [S11, S25, S54].
6. **Third-party mentions (moderate, off-page).** Link the site from GitHub (profile README and repo homepages), LinkedIn (profile and posts), and conference or meetup pages. Cross-post with `rel=canonical` to the original. Only genuine mentions count [S14, S51, S55].
7. **Entity consistency (weak).** Use the exact same name, role and employer string in the visible bio, `Person` JSON-LD, llms.txt, LinkedIn and GitHub. Add a visible author line linking to the CV or about page on every story [S24, S25].
8. **Full Turkish versions at their own URLs (moderate for language, weak for hreflang).** Already true. Engines cite different ecosystems per language [S55].
9. **Structured data (weak/negative for citations).** Keep the existing JSON-LD for Google rich results and entity identity. Don't add more schema types expecting AI citations [S50].
10. **Question-style headings or FAQ blocks (weak).** Use specific headings. A question is fine where it is the natural title (the spec's symptom-first titles already are). Don't add FAQ blocks for AI [S26, S14].
11. **Page speed (no evidence for AI).** Keep current performance; no extra work justified.
12. **llms.txt (no evidence).** See (b).

### (d) Ten manual test prompts (run after launch, then monthly)

Run in ChatGPT (search on), Perplexity, Claude (web search on) and Google (AI Mode; also note whether a normal search shows an AI Overview). Run each prompt 3 times. Record the cited URLs and check the server logs for `ChatGPT-User`, `Claude-User` and `Perplexity-User` fetches. For a new site, "cites LinkedIn or GitHub but not the site" is a partial pass.

| # | Prompt | A good answer contains |
|---|---|---|
| 1 | Who is Timurhan Kaya, the software developer? | A software developer in Istanbul at Wingie Enuygun Group, working on fintech, insurance and AI features. Cites `timurhankaya.dev` (home or CV). No invented employer or title. |
| 2 | What projects has Timurhan Kaya worked on? | Names Enuygun Sigorta high-traffic systems and Monafy (financial data and AI), plus the games. Cites `/projects` or `/cv`. |
| 3 | What production incidents has Timurhan Kaya written case studies about? | At least one real case-study title, in its own words. Links `/case-studies/{slug}`. |
| 4 | How do you play Vampire Village, the browser game by Timurhan Kaya? | The game's roles and rounds as described on the page. Links `/games/vampire`. |
| 5 | How can I contact Timurhan Kaya about a Laravel or fintech project? | Contact page, email, LinkedIn and GitHub as listed on the site. Links `/contact`. |
| 6 | Timurhan Kaya kimdir? | İstanbul'da, Wingie Enuygun Group'ta yazılım geliştirici; fintech, sigorta ve yapay zekâ. Cites a **/tr/** page (home or `/tr/ozgecmis`), and the answer is in Turkish. |
| 7 | Timurhan Kaya hangi projelerde çalıştı? | Enuygun Sigorta, Monafy and the games. Cites `/tr/projeler` or `/tr/ozgecmis`. |
| 8 | Timurhan Kaya'nın yazdığı vaka incelemeleri hangileri? | Turkish case-study titles (symptom first). Links `/tr/vaka-incelemeleri/{slug}`. |
| 9 | Vampir Köylü oyunu nasıl oynanır? | Rules in Turkish. Links `/tr/oyunlar/vampir-koylu` and names Timurhan Kaya as the maker. |
| 10 | Kelime Casusu oyunu nedir, arkadaşlarla online nasıl oynanır? | Explains the word-imposter mechanic. Links `/tr/oyunlar/kelime-casusu`. |

Failure signals to log:
- The wrong language version is cited (an EN page for a TR prompt).
- Stale facts (an old employer or role).
- The site is cited, but the facts come from elsewhere.
- No `*-User` fetch appears in the logs while the engine claims it looked at the site.
