<?php

namespace App\Http\Controllers;

use App\Enums\PostType;
use App\Models\Post;
use App\Support\Anchors;
use App\Support\Seo;
use Illuminate\Http\Response;

/** /llms.txt: a plain markdown index of what is on the site, in the visitor's language. */
class LlmsController extends Controller
{
    public function __invoke(): Response
    {
        // The address decides the language, never the visitor's session or headers, so the root file is always English.
        $locale = str_starts_with((string) request()->route()?->getName(), 'tr.') ? 'tr' : 'en';
        app()->setLocale($locale);
        $line = fn (string $title, string $url, ?string $description): string => '- ['.$title.']('.$url.')'.($description ? ': '.preg_replace('/\s+/u', ' ', trim($description)) : '');
        $posts = fn (PostType $type): array => Post::published()->where('type', $type)->orderByDesc('published_at')->get()
            ->map(fn (Post $post): string => $line($post->text('title', $locale), $post->publicUrl($locale), $post->text('excerpt', $locale)))->all();

        $sections = [
            '# Timurhan Kaya',
            '> '.__('brand.llms_intro'),
            $this->section(__('brand.llms_writing'), $posts(PostType::Article)),
            $this->section(__('brand.llms_cases'), $posts(PostType::CaseStudy)),
            $this->section(__('brand.llms_projects'), [
                $line(__('brand.work'), Anchors::home('portfolio'), null),
                $line('CV', Seo::route('cv', [], $locale), __('brand.cv_note')),
            ]),
            $this->section(__('brand.llms_contact'), [$line(__('brand.contact_page'), Seo::route('contact', [], $locale), __('brand.contact_intro'))]),
            $this->section(__('brand.llms_optional'), [
                $line(__('portfolio.nav.games'), Seo::route('games.index', [], $locale), __('games.metaDescription')),
                $line($locale === 'en' ? 'Türkçe' : 'English', url($locale === 'en' ? '/tr/llms.txt' : '/llms.txt'), null),
            ]),
        ];

        return response(implode("\n\n", array_filter($sections))."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /** @param  list<string>  $lines */
    private function section(string $heading, array $lines): ?string
    {
        return $lines === [] ? null : "## {$heading}\n".implode("\n", $lines);
    }
}
