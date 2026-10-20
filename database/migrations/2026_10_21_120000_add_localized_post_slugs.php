<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->string('slug_tr')->nullable()->unique();
            $table->string('slug_en')->nullable()->unique();
        });

        // Read the final editorial URLs before changing any existing address. The
        // stable slug stays intact, so every old locale URL remains a 301 alias.
        $stories = [];
        foreach (glob(resource_path('content/stories/*.json')) ?: [] as $file) {
            foreach (json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR) as $story) {
                $stories[$story['slug']] = $story;
            }
        }
        $launch = resource_path('editorial/launch-story.json');
        if (is_file($launch)) {
            $bundle = json_decode(file_get_contents($launch), true, flags: JSON_THROW_ON_ERROR);
            $story = $bundle['story'] ?? $bundle;
            if (isset($story['slug'])) {
                $stories[$story['slug']] = $story;
            }
        }

        // Reserve deleted rows too: restoring a story must not steal an address.
        $posts = DB::table('posts')->orderBy('id')->get(['id', 'slug', 'title']);
        $reserved = $posts->pluck('id', 'slug')->all();
        foreach ($posts as $post) {
            $slugs = [];
            foreach (['tr', 'en'] as $locale) {
                $preferred = $stories[$post->slug][$locale]['slug'] ?? ($locale === 'tr' ? Str::slug($post->title, '-', 'tr') : $post->slug);
                $base = rtrim(substr($preferred ?: $post->slug, 0, 240), '-');
                $slug = $base;
                $suffix = 2;
                while (isset($reserved[$slug]) && $reserved[$slug] !== $post->id) {
                    $slug = $base.'-'.$suffix++;
                }
                $reserved[$slug] = $post->id;
                $slugs['slug_'.$locale] = $slug;
            }
            // Query builder preserves real content timestamps and bypasses the
            // publication immutability guard only for this one-time migration.
            DB::table('posts')->where('id', $post->id)->update($slugs);
        }
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropUnique(['slug_tr']);
            $table->dropUnique(['slug_en']);
            $table->dropColumn(['slug_tr', 'slug_en']);
        });
    }
};
