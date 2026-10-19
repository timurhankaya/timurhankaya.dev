<?php

namespace App\Services\Publishing;

use App\Models\Post;
use Illuminate\Support\Str;

final class PostContent
{
    /**
     * Gives every h2/h3 a readable id taken from its text (/…#kart-bilgisi-…) and returns the h2 sections
     * for the contents list. Repeated headings get -2, -3 so every link has one target.
     */
    public function prepare(Post $post): array
    {
        $headings = [];
        $used = [];
        $html = preg_replace_callback('~<h([23])(?:\s[^>]*)?>(.*?)</h\1>~is', function (array $match) use (&$headings, &$used): string {
            $title = html_entity_decode(strip_tags($match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $base = Str::slug($title, '-', 'tr') ?: 'section';
            $used[$base] = ($used[$base] ?? 0) + 1;
            $id = $used[$base] > 1 ? $base.'-'.$used[$base] : $base;
            if ($match[1] === '2') {
                $headings[] = ['id' => $id, 'title' => $title];
            }

            return '<h'.$match[1].' id="'.$id.'">'.$match[2].'</h'.$match[1].'>';
        }, $post->renderedContent());

        return ['articleHtml' => $html, 'headings' => $headings];
    }
}
