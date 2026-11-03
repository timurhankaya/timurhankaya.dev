<?php

namespace App\Services\Publishing;

use App\Models\Post;
use App\Support\StoryStyle;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JsonException;
use RuntimeException;

final class StoryDrafts
{
    private const FIELDS = ['title', 'excerpt', 'content', 'meta_title', 'meta_description'];

    public function stage(Post $post, array $story, string $revision): void
    {
        Validator::make(['revision' => $revision, ...$story], [
            'revision' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/'],
            'tr' => ['required', 'array'], 'en' => ['required', 'array'],
            'tr.title' => ['required', 'string'], 'en.title' => ['required', 'string'],
            'tr.excerpt' => ['required', 'string'], 'en.excerpt' => ['required', 'string'],
            'tr.content' => ['required', 'string'], 'en.content' => ['required', 'string'],
        ])->validate();
        if (($story['slug'] ?? null) !== $post->slug || ($story['type'] ?? null) !== $post->type->value
            || ($story['project_key'] ?? null) !== $post->project_key || $post->trashed()) {
            throw ValidationException::withMessages(['story' => 'The draft must match the existing story.']);
        }
        if ($problems = StoryStyle::violations($story)) {
            throw ValidationException::withMessages(['story' => $problems]);
        }
        app(PostPublication::class)->validate($this->overlay($post, $story));
        $payload = ['revision' => $revision, 'baseline' => $this->fingerprint($post), 'story' => $story];
        if (! Storage::disk('local')->put($this->path($post), json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE))) {
            throw new RuntimeException('The private draft could not be stored.');
        }
    }

    public function preview(Post $post): Post
    {
        $disk = Storage::disk('local');
        if (! $disk->exists($this->path($post))) {
            return $post;
        }
        try {
            $draft = json_decode($disk->get($this->path($post)), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            abort(409, 'The draft must be staged again.');
        }
        abort_unless(is_array($draft) && ($draft['baseline'] ?? null) === $this->fingerprint($post)
            && is_array($draft['story'] ?? null), 409, 'The live story changed. Stage the revised draft again.');

        return $this->overlay($post, $draft['story']);
    }

    private function overlay(Post $post, array $story): Post
    {
        $preview = clone $post;
        $preview->fill(array_intersect_key($story['tr'], array_flip(self::FIELDS)));
        $preview->translations = [
            ...array_diff_key((array) $post->translations, ['tr' => true]),
            'en' => array_intersect_key($story['en'], array_flip(self::FIELDS)),
        ];

        return $preview;
    }

    private function fingerprint(Post $post): string
    {
        return hash('sha256', json_encode($post->only([
            ...self::FIELDS, 'translations', 'slug', 'slug_tr', 'slug_en', 'type', 'project_key', 'case_meta', 'published_at',
        ]), JSON_THROW_ON_ERROR));
    }

    private function path(Post $post): string
    {
        return 'editorial/previews/'.$post->id.'.json';
    }
}
