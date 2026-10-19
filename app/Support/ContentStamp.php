<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * When a page's source files last changed in content. File mtimes cannot answer that: every deploy rewrites them,
 * which would mark every page as modified. Instead the hash of the files is stored with the time it was first seen.
 */
final class ContentStamp
{
    /** Overrides the stamp file (tests point this at a temporary file). */
    public static ?string $path = null;

    /** @param  list<string>  $paths */
    public static function lastChanged(string $key, array $paths): ?Carbon
    {
        $files = array_values(array_filter($paths, 'is_file'));
        if ($files === []) {
            return null;
        }
        $hash = sha1(implode("\0", array_map(fn (string $file): string => (string) sha1_file($file), $files)));
        $store = self::$path ?? storage_path('app/private/content-stamps.json');
        $stamps = is_file($store) ? (json_decode((string) file_get_contents($store), true) ?: []) : [];

        if (($stamps[$key]['hash'] ?? null) !== $hash) {
            $stamps[$key] = ['hash' => $hash, 'at' => now()->toAtomString()];
            if (! is_dir(dirname($store))) {
                mkdir(dirname($store), 0775, true);
            }
            file_put_contents($store, json_encode($stamps, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        }

        return Carbon::parse($stamps[$key]['at']);
    }
}
