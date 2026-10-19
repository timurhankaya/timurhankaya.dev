<?php

namespace App\Services\Portfolio;

final class PortfolioProjects
{
    public function __construct(private PortfolioContentService $content) {}

    public function sections(): array
    {
        $sections = $this->content->get()['projects']['sections'];
        foreach ($sections as &$section) {
            // Entries flagged "hidden" stay in the content file but are not shown publicly.
            $section['items'] = array_values(array_filter($section['items'], fn (array $project): bool => ! ($project['hidden'] ?? false)));
        }
        unset($section);

        // A section whose every project is hidden disappears instead of showing "0 projects".
        return array_values(array_filter($sections, fn (array $section): bool => $section['items'] !== []));
    }

    public function featured(): array
    {
        $keys = ['monafy', 'moneo-render', 'transiyzi'];
        $projects = collect($this->sections())->flatMap(fn (array $section): array => $section['items'])->keyBy('key');

        return collect($keys)->map(fn (string $key) => $projects->get($key))->filter()->values()->all();
    }
}
