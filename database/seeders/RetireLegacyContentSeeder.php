<?php

namespace Database\Seeders;

use App\Enums\PostType;
use App\Models\Post;
use Illuminate\Database\Seeder;

/**
 * Takes the placeholder case studies and the demo articles off the public site.
 * Placeholders are soft-deleted and the demos unpublished, so both stay restorable in the admin panel.
 */
class RetireLegacyContentSeeder extends Seeder
{
    private const PLACEHOLDERS = [
        'monafy', 'transiyzi', 'edanisman', 'smart-global', 'herbalfit', 'word-imposter', 'vampir-koylu',
        // Project-overview case studies superseded by single-incident stories.
        'monafy-service-boundaries', 'monafy-market-data-pipeline', 'monafy-realtime-prices-and-caching', 'monafy-mona-ai-assistant', 'monafy-production-verification',
    ];

    private const DEMO_ARTICLES = ['html-elements-demonstration', 'published-post', 'seo-article'];

    public function run(): void
    {
        Post::where('type', PostType::CaseStudy)->whereIn('slug', self::PLACEHOLDERS)->get()->each->delete();
        Post::whereIn('slug', self::DEMO_ARTICLES)->whereNotNull('published_at')->get()
            ->each(fn (Post $post) => $post->update(['published_at' => null]));
    }
}
