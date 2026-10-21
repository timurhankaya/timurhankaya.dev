<?php

namespace App\Services\Portfolio;

use Illuminate\Support\Facades\Cache;

final class PortfolioContentService
{
    /**
     * @return array<string, mixed>
     */
    public function get(?string $locale = null): array
    {
        $resolvedLocale = $locale ?? app()->getLocale();
        $resolvedLocale = in_array($resolvedLocale, ['tr', 'en'], true) ? $resolvedLocale : 'en';

        return Cache::remember(
            'portfolio:content:v4:'.$resolvedLocale,
            now()->addMinutes(30),
            fn (): array => $this->load($resolvedLocale),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function load(string $locale): array
    {
        $path = resource_path("content/portfolio.{$locale}.json");
        $json = file_get_contents($path);

        if (! is_string($json)) {
            throw new \RuntimeException("Unable to read portfolio content: {$path}");
        }

        $data = json_decode($json, true);

        if (! is_array($data)) {
            throw new \RuntimeException("Invalid portfolio JSON: {$path}");
        }

        return $data;
    }
}
