<?php

namespace App\Services\Publishing;

use App\Enums\PostType;
use App\Models\Post;
use App\Services\Portfolio\ProjectCatalog;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class PostPublication
{
    public function validate(Post $post): void
    {
        if ($post->exists && $post->getOriginal('first_published_at') !== null && $post->isDirty(['slug', 'slug_tr', 'slug_en', 'type'])) {
            throw ValidationException::withMessages(['slug' => __('brand.immutable_post')]);
        }
        $rawType = $post->getAttributes()['type'] ?? 'article';
        if (PostType::tryFrom($rawType) === null) {
            throw ValidationException::withMessages(['type' => 'Select a valid post type.']);
        }
        $publishing = $post->published_at !== null;
        $case = $post->type === PostType::CaseStudy;
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('posts', 'slug')->ignore($post->id)],
            'slug_tr' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('posts', 'slug_tr')->ignore($post->id)],
            'slug_en' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('posts', 'slug_en')->ignore($post->id)],
            'type' => [Rule::enum(PostType::class)],
            'project_key' => ['nullable', Rule::in(array_keys(app(ProjectCatalog::class)->options()))],
            'excerpt' => [$publishing ? 'required' : 'nullable', 'string', 'max:2000'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'content' => [$publishing ? 'required' : 'nullable', 'string'],
            'case_meta' => [$case && $publishing ? 'required' : 'nullable', 'array'],
            'case_meta.project_name' => [$case && $publishing ? 'required' : 'nullable', 'string', 'max:255'],
            'case_meta.project_name_en' => ['nullable', 'string', 'max:255'],
            'case_meta.context' => [$case && $publishing ? 'required' : 'nullable', Rule::in(['founder', 'employment', 'client', 'personal'])],
            'case_meta.period' => ['nullable', 'string', 'max:100'],
            'case_meta.technologies' => [$case && $publishing ? 'required' : 'nullable', 'array', 'max:20'],
            'case_meta.technologies.*' => ['string', 'max:100'],
            'case_meta.url' => ['nullable', 'url:http,https', 'max:2000'],
            'case_meta.links' => ['nullable', 'array', 'max:8'],
            'case_meta.links.*.url' => ['required', 'url:http,https', 'max:2000'],
            'case_meta.links.*.label' => ['required', 'array:tr,en'],
            'case_meta.links.*.label.*' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['integer', 'between:0,65535'],
            'translations' => ['nullable', 'array:tr,en'],
            'translations.*' => ['array:title,excerpt,content,meta_title,meta_description'],
            'translations.*.title' => ['nullable', 'string', 'max:255'],
            'translations.*.excerpt' => ['nullable', 'string', 'max:2000'],
            'translations.*.content' => ['nullable', 'string'],
            'translations.*.meta_title' => ['nullable', 'string', 'max:70'],
            'translations.*.meta_description' => ['nullable', 'string', 'max:160'],
        ];
        $data = $post->getAttributes();
        $data['type'] = $post->type->value;
        $data['case_meta'] = $post->case_meta;
        $data['translations'] = $post->translations;
        Validator::make($data, $rules)->validate();
        foreach (['slug', 'slug_tr', 'slug_en'] as $field) {
            if ($post->{$field} && Post::withTrashed()->whereKeyNot($post->id ?? 0)->withPublicSlug($post->{$field})->exists()) {
                throw ValidationException::withMessages([$field => 'This address is reserved by another story.']);
            }
        }
        if ($publishing && trim(strip_tags(Str::sanitizeHtml($post->content ?? ''))) === '') {
            throw ValidationException::withMessages(['content' => __('brand.content_required')]);
        }
    }
}
