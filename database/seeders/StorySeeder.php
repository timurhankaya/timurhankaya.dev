<?php

namespace Database\Seeders;

use App\Enums\PostType;
use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Imports bilingual case studies and articles from resources/content/stories/*.json.
 * Turkish is the primary content; English is stored as a translation. Existing slugs
 * (including soft-deleted ones) are skipped so later admin edits are never overwritten.
 * Entries marked "draft": true are created unpublished for review in the admin panel.
 * An entry with "replaces": "old-slug" (or a list of slugs) soft-deletes those rows when it is created.
 */
class StorySeeder extends Seeder
{
    /** Overrides the stories directory (tests point this at fixtures). */
    public static ?string $path = null;

    public function run(): void
    {
        foreach (glob((self::$path ?? resource_path('content/stories')).'/*.json') ?: [] as $file) {
            foreach (json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR) as $story) {
                if (Post::withTrashed()->where('slug', $story['slug'])->exists()) {
                    $this->command?->warn("Skipped {$story['slug']}: that slug already exists (possibly in the trash).");

                    continue;
                }
                $type = PostType::from($story['type']);
                Post::create([
                    'title' => $story['tr']['title'],
                    'slug' => $story['slug'],
                    'slug_tr' => $story['tr']['slug'] ?? null,
                    'slug_en' => $story['en']['slug'] ?? null,
                    'excerpt' => $story['tr']['excerpt'],
                    // English keeps its own meta_title inside the translation.
                    'meta_title' => $story['tr']['meta_title'] ?? null,
                    'meta_description' => $story['tr']['meta_description'] ?? null,
                    'content' => $story['tr']['content'],
                    'type' => $type,
                    'project_key' => $story['project_key'] ?? null,
                    'sort_order' => $story['sort_order'] ?? 100,
                    'case_meta' => $type === PostType::CaseStudy ? $story['case_meta'] : null,
                    'translations' => ['en' => array_diff_key($story['en'], ['slug' => true])],
                    'published_at' => ($story['draft'] ?? false) ? null : Carbon::parse($story['published_at'])->setTime(9, 0),
                ]);
                // A rewritten story retires the rows it replaces (soft delete, restorable from the admin panel).
                if (! empty($story['replaces'])) {
                    Post::whereIn('slug', (array) $story['replaces'])->get()->each->delete();
                }
            }
        }
    }
}
