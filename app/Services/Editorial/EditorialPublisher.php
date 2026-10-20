<?php

namespace App\Services\Editorial;

use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class EditorialPublisher
{
    public function __construct(private EditorialBundle $bundles) {}

    public function import(array $bundle): array
    {
        $story = $this->bundles->validatePublic($bundle);

        return DB::transaction(function () use ($bundle, $story): array {
            // An UPDATE obtains the same transaction-held row lock on MySQL and SQLite.
            DB::table('editorial_publication_lock')->where('id', 1)->update(['updated_at' => now()]);
            $existing = DB::table('editorial_publications')->where('bundle_id', $bundle['id'])->first();
            if ($existing) {
                return $this->result($existing->post_id, $bundle['id'], 'existing');
            }
            if (DB::table('editorial_publications')->where('topic_key', $bundle['topic_key'])->orWhere('source_fingerprint', $bundle['source_fingerprint'])->exists()) {
                throw ValidationException::withMessages(['bundle' => 'This topic or source set has already been published.']);
            }
            if (DB::table('editorial_publications')->where('created_at', '>', now()->subDays(7))->count() >= config('editorial.weekly_limit')) {
                throw ValidationException::withMessages(['bundle' => 'The rolling seven-day publication limit has been reached.']);
            }
            if (Post::withTrashed()->where('slug', $story['slug'])->exists()) {
                throw ValidationException::withMessages(['bundle' => 'This slug already exists; existing stories are never overwritten.']);
            }
            $post = Post::create([
                'type' => $story['type'], 'slug' => $story['slug'], 'project_key' => $story['project_key'],
                'sort_order' => $story['sort_order'] ?? 100, 'case_meta' => $story['case_meta'] ?? null,
                ...$story['tr'], 'translations' => ['en' => $story['en']], 'published_at' => now(),
            ]);
            DB::table('editorial_publications')->insert([
                'topic_key' => $bundle['topic_key'], 'bundle_id' => $bundle['id'], 'source_fingerprint' => $bundle['source_fingerprint'],
                'post_id' => $post->id, 'created_at' => now(), 'updated_at' => now(),
            ]);

            return $this->result($post->id, $bundle['id'], 'published');
        }, 3);
    }

    private function result(?int $postId, string $bundleId, string $status): array
    {
        $post = $postId ? Post::find($postId) : null;

        return ['status' => $post && $post->isPublic() ? $status : 'suppressed', 'bundle_id' => $bundleId,
            'post_id' => $postId, 'titles' => $post ? ['tr' => $post->text('title', 'tr'), 'en' => $post->text('title', 'en')] : [], 'urls' => $post && $post->isPublic() ? ['tr' => $post->publicUrl('tr'), 'en' => $post->publicUrl('en')] : []];
    }
}
