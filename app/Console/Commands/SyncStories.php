<?php

namespace App\Console\Commands;

use App\Enums\PostType;
use App\Models\Post;
use Database\Seeders\StorySeeder;
use Illuminate\Console\Command;

/**
 * StorySeeder never overwrites an existing slug, so a rewrite in resources/content/stories reaches the
 * database only through this command. It copies the text and metadata; address, type and dates stay.
 */
class SyncStories extends Command
{
    protected $signature = 'stories:sync {slug* : Slugs of the stories to update}';

    protected $description = 'Copy the text of existing stories from their JSON files (overwrites admin edits to those fields)';

    public function handle(): int
    {
        $stories = collect(glob((StorySeeder::$path ?? resource_path('content/stories')).'/*.json') ?: [])
            ->flatMap(fn (string $file): array => json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR))
            ->keyBy('slug');
        $failed = false;
        foreach ($this->argument('slug') as $slug) {
            $story = $stories->get($slug);
            $post = $story ? Post::where('slug', $slug)->first() : null;
            if (! $post) {
                $this->error('not found: '.$slug);
                $failed = true;

                continue;
            }
            $post->fill([
                'title' => $story['tr']['title'],
                'meta_title' => $story['tr']['meta_title'] ?? null,
                'meta_description' => $story['tr']['meta_description'] ?? null,
                'excerpt' => $story['tr']['excerpt'],
                'content' => $story['tr']['content'],
                'project_key' => $story['project_key'] ?? null,
                'sort_order' => $story['sort_order'] ?? 100,
                'case_meta' => $post->type === PostType::CaseStudy ? $story['case_meta'] : null,
                'translations' => [...(array) $post->translations, 'en' => $story['en']],
            ])->save();
            $this->info('synced: '.$slug);
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
