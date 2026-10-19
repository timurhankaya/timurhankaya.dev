<?php

/*
 * AI-related crawler tokens named in robots.txt. The owner allows all of them, training included.
 * Source and per-token evidence: docs/superpowers/notes/ai-visibility.md (section 2 and 6a).
 * A crawler that finds a group naming it ignores the `*` group, so SitemapController repeats the
 * private-path rules inside the named group.
 */
return [
    // Search and answer indexing.
    'search' => ['Googlebot', 'bingbot', 'OAI-SearchBot', 'Claude-SearchBot', 'PerplexityBot', 'Applebot', 'DuckDuckBot', 'Amzn-SearchBot', 'meta-webindexer', 'MistralAI-Index'],
    // User-initiated fetches; several ignore robots.txt, listed so the policy is stated.
    'fetch' => ['ChatGPT-User', 'Claude-User', 'Perplexity-User', 'Google-Agent', 'DuckAssistBot', 'meta-externalfetcher', 'MistralAI-User', 'Amzn-User'],
    // Model training and data collection.
    'training' => ['GPTBot', 'ClaudeBot', 'Google-Extended', 'Applebot-Extended', 'CCBot', 'meta-externalagent', 'Amazonbot', 'MistralAI-Training', 'Bytespider'],
];
