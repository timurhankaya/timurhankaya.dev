<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Tells Bing, Yandex and the other IndexNow engines that addresses changed, instead of waiting
 * for their next crawl. Does nothing until INDEXNOW_KEY is set, so local and test runs stay offline.
 */
final class IndexNow
{
    public const ENDPOINT = 'https://api.indexnow.org/indexnow';

    public static function key(): ?string
    {
        $key = trim((string) config('services.indexnow.key'));

        return $key !== '' ? $key : null;
    }

    /** @param  list<string>  $urls  addresses on this site's host */
    public static function submit(array $urls): bool
    {
        $key = self::key();
        $urls = array_values(array_unique(array_filter($urls)));
        if ($key === null || $urls === []) {
            return false;
        }

        try {
            // The protocol takes at most 10,000 addresses per request.
            foreach (array_chunk($urls, 10000) as $chunk) {
                $response = Http::timeout(5)->acceptJson()->post(self::ENDPOINT, [
                    'host' => parse_url($chunk[0], PHP_URL_HOST),
                    'key' => $key,
                    'urlList' => $chunk,
                ]);
                if (! $response->successful()) {
                    return false;
                }
            }
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }

        return true;
    }
}
