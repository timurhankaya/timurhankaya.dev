<?php

namespace App\Support;

/**
 * Mechanical checks for the voice guide (docs/superpowers/notes/writing-voice.md).
 * They catch template tells; they cannot judge whether a story sounds human.
 */
final class StoryStyle
{
    public const MAX_TITLE = 70;

    /** Results pages show about 60 characters; a longer headline carries a shorter meta_title for search. */
    public const MAX_SEARCH_TITLE = 60;

    /** Turkish packs suffixes into words, so the same text runs about a fifth shorter than its English. */
    public const CASE_WORDS = ['en' => [350, 600], 'tr' => [280, 500]];

    /** Articles may run longer than case studies, but not much. */
    public const ARTICLE_WORDS = ['en' => [500, 1000], 'tr' => [400, 850]];

    /** Longest paragraph allowed, in words. */
    public const PARAGRAPH_WORDS = ['en' => 75, 'tr' => 60];

    /** A blog article is anonymous (no project_key) or about one of the owner's own projects, and then links its public site (null: no site). */
    public const OWN_PROJECTS = ['monafy' => 'https://monafy.com', 'word-imposter' => 'https://wordimposter.com', 'vampir-koylu' => null];

    public const BANNED = [
        'en' => ['guardrails', 'seamless', 'robust', 'leverage', 'delve', 'journey', 'tapestry', 'cutting-edge', 'game-changer', 'revolutionary', 'unlock', 'in conclusion'],
        'tr' => ['kusursuz', 'sorunsuz', 'yolculuk', 'güçlü bir altyapı', 'derinlemesine', 'çığır açan', 'sonuç olarak'],
    ];

    /**
     * @param  array<string, mixed>  $story  one entry of resources/content/stories/*.json
     * @return list<string>
     */
    public static function violations(array $story): array
    {
        $found = [];
        $article = ($story['type'] ?? null) === 'article';
        if ($article && filled($story['project_key'] ?? null) && ! array_key_exists((string) $story['project_key'], self::OWN_PROJECTS)) {
            $found[] = 'article is not about one of the owner\'s own projects';
        }
        $site = $article ? (self::OWN_PROJECTS[(string) ($story['project_key'] ?? '')] ?? null) : null;
        foreach (['en', 'tr'] as $lang) {
            $title = (string) ($story[$lang]['title'] ?? '');
            $html = (string) ($story[$lang]['content'] ?? '');
            // Tags become spaces so the last word of one paragraph never merges with the first of the next.
            $text = (string) preg_replace('/<[^>]+>/', ' ', $html);

            if (str_contains($title, ':')) {
                $found[] = "$lang: title has a colon";
            }
            if ($lang === 'en' && preg_match('/\bfrom\b.+\bto\b/i', $title)) {
                $found[] = 'en: title uses a from-to pattern';
            }
            if ($lang === 'tr' && preg_match('/\b(?!neden\b|nereden\b)\p{L}+(?:den|dan|ten|tan)\b.+\b\p{L}+(?:ye|ya|ne|na|e|a)[?.!]?$/iu', $title)) {
                $found[] = 'tr: title uses a from-to pattern';
            }
            if (mb_strlen($title) > self::MAX_TITLE) {
                $found[] = "$lang: title is longer than ".self::MAX_TITLE.' characters';
            }
            $search = (string) ($story[$lang]['meta_title'] ?? '');
            if ($search === '' && mb_strlen($title) > self::MAX_SEARCH_TITLE) {
                $found[] = "$lang: title is longer than ".self::MAX_SEARCH_TITLE.' characters and needs a meta_title';
            }
            if (mb_strlen($search) > self::MAX_SEARCH_TITLE) {
                $found[] = "$lang: meta_title is longer than ".self::MAX_SEARCH_TITLE.' characters';
            }
            if (str_contains($search, ':')) {
                $found[] = "$lang: meta_title has a colon";
            }
            foreach (self::BANNED[$lang] as $word) {
                if (mb_stripos($title.' '.$text, $word) !== false) {
                    $found[] = "$lang: banned word “{$word}”";
                }
            }
            $words = preg_match_all('/[\p{L}\p{N}][\p{L}\p{N}\'’-]*/u', $text);
            [$kind, [$min, $max]] = $article ? ['article', self::ARTICLE_WORDS[$lang]] : ['case study', self::CASE_WORDS[$lang]];
            if ($words < $min || $words > $max) {
                $found[] = "$lang: $kind has $words words, expected $min-$max";
            }
            preg_match_all('~<p\b[^>]*>(.*?)</p>~s', $html, $paragraphs);
            foreach ($paragraphs[1] as $paragraph) {
                $length = preg_match_all('/[\p{L}\p{N}][\p{L}\p{N}\'’-]*/u', strip_tags($paragraph));
                if ($length > self::PARAGRAPH_WORDS[$lang]) {
                    $found[] = "$lang: a paragraph has $length words, keep it to ".self::PARAGRAPH_WORDS[$lang];
                }
            }
            if ($site !== null && ! str_contains($html, 'href="'.$site)) {
                $found[] = "$lang: article does not link $site";
            }
            preg_match_all('~<pre><code(?: class="language-([a-z]+)")?>~', $html, $blocks);
            if (array_diff($blocks[1], array_keys(CodeBlocks::LANGUAGES)) !== []) {
                $found[] = "$lang: code block without a supported language class";
            }
            // Code only where it explains the decision; one excerpt is enough for a reader of this site.
            if (count($blocks[0]) > 1) {
                $found[] = "$lang: ".count($blocks[0]).' code blocks, keep at most one';
            }
            if (preg_match('/^\s*<h[1-6]\b/i', $html)) {
                $found[] = "$lang: story opens with a heading, not prose";
            }
        }

        return $found;
    }

    /**
     * Section headings that more than one story uses in the same language, lower-cased as "lang: heading".
     *
     * @param  list<array<string, mixed>>  $stories
     * @return list<string>
     */
    public static function sharedHeadings(array $stories): array
    {
        $seen = [];
        foreach ($stories as $index => $story) {
            foreach (['en', 'tr'] as $lang) {
                preg_match_all('~<h2\b[^>]*>(.*?)</h2>~is', (string) ($story[$lang]['content'] ?? ''), $matches);
                foreach ($matches[1] as $heading) {
                    $seen[$lang.': '.mb_strtolower(trim(strip_tags($heading)))][$index] = true;
                }
            }
        }
        $shared = array_keys(array_filter($seen, fn (array $stories): bool => count($stories) > 1));
        sort($shared);

        return $shared;
    }
}
