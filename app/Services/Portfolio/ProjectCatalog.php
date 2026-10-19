<?php

namespace App\Services\Portfolio;

final class ProjectCatalog
{
    /** Keys for work told without naming the client; they group stories but have no portfolio card. */
    public const UNLISTED = ['e-commerce' => 'E-ticaret altyapısı (isimsiz müşteri)', 'b2b-saas' => 'B2B SaaS platformu (isimsiz müşteri)'];

    /** Reading order of case-study groups: the owner's product first, client work next, side projects last. */
    public const STORY_ORDER = ['monafy', 'b2b-saas', 'e-commerce', 'vampir-koylu', 'word-imposter'];

    public function options(): array
    {
        return collect(app(PortfolioContentService::class)->get()['projects']['sections'])
            ->flatMap(fn (array $section): array => $section['items'])
            ->pluck('name', 'key')->all() + self::UNLISTED;
    }

    /** @return array{name: string, url: string}|null a portfolio project with a public site, in the current language */
    public function find(?string $key): ?array
    {
        $project = collect(app(PortfolioContentService::class)->get()['projects']['sections'])
            ->flatMap(fn (array $section): array => $section['items'])->firstWhere('key', $key);

        return filled($project['url'] ?? null) ? ['name' => (string) $project['name'], 'url' => (string) $project['url']] : null;
    }
}
