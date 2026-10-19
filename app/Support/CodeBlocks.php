<?php

namespace App\Support;

use Tempest\Highlight\Highlighter;

/**
 * Turns `<pre><code class="language-…">` in sanitized post content into the site's code component:
 * a language bar with a copy button and server-side highlighted, line-numbered code (no client-side highlighter).
 */
final class CodeBlocks
{
    /** Language names a story may use in class="language-…", with the label shown in the bar. */
    public const LANGUAGES = [
        'php' => 'PHP', 'blade' => 'Blade', 'python' => 'Python', 'sql' => 'SQL', 'bash' => 'Shell', 'json' => 'JSON',
        'yaml' => 'YAML', 'markdown' => 'Markdown', 'javascript' => 'JavaScript', 'typescript' => 'TypeScript',
        'html' => 'HTML', 'css' => 'CSS', 'xml' => 'XML', 'ini' => 'INI', 'diff' => 'Diff', 'text' => 'Text',
    ];

    public static function render(string $html): string
    {
        return preg_replace_callback('~<pre><code(?: class="language-([a-z]+)")?>(.*?)</code></pre>~s', function (array $match): string {
            $language = array_key_exists($match[1] ?? '', self::LANGUAGES) ? $match[1] : 'text';
            // The sanitizer left the code entity-encoded; the highlighter escapes it again as it wraps tokens.
            $code = rtrim(html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'), "\n");
            $highlighted = (new Highlighter)->withGutter(1)->parse($code, $language);

            return '<figure class="brand-code" data-lang="'.$language.'">'
                .'<div class="brand-code-bar"><span class="brand-code-lang">'.self::LANGUAGES[$language].'</span>'
                .'<button type="button" class="brand-code-copy" data-copy-code data-copied-label="'.e(__('brand.code_copied')).'">'.e(__('brand.code_copy')).'</button></div>'
                .'<pre><code class="language-'.$language.'">'.$highlighted.'</code></pre></figure>';
        }, $html) ?? $html;
    }
}
