<?php

namespace App\Models;

use App\Enums\PostType;
use App\Services\Publishing\PostPublication;
use App\Support\CodeBlocks;
use App\Support\IndexNow;
use App\Support\Seo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Illuminate\Support\defer;

class Post extends Model
{
    use SoftDeletes;

    /** Language of the primary columns; every other language lives in `translations`. */
    public const PRIMARY_LOCALE = 'tr';

    protected $fillable = ['title', 'slug', 'slug_tr', 'slug_en', 'cover_image', 'excerpt', 'meta_title', 'meta_description', 'content', 'published_at', 'type', 'project_key', 'sort_order', 'case_meta', 'translations'];

    protected $attributes = ['type' => 'article', 'content' => '', 'sort_order' => 100];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'first_published_at' => 'datetime', 'type' => PostType::class, 'sort_order' => 'integer', 'case_meta' => 'array', 'translations' => 'array'];
    }

    protected static function booted(): void
    {
        static::saving(function (Post $post): void {
            $post->content ??= '';
            app(PostPublication::class)->validate($post);
            if ($post->published_at && ! $post->first_published_at) {
                $post->first_published_at = $post->published_at;
            }
        });
        static::restoring(fn (Post $post) => app(PostPublication::class)->validate($post));
        // Engines hear about a public story as soon as it appears, changes or goes away.
        static::saved(function (Post $post): void {
            $wasPublic = ($before = $post->getOriginal('published_at')) !== null && $before <= now();
            if (($post->wasRecentlyCreated || $post->wasChanged()) && ($post->isPublic() || $wasPublic)) {
                defer(fn () => IndexNow::submit($post->indexNowUrls()));
            }
        });
        static::deleted(function (Post $post): void {
            if ($post->isPublic()) {
                defer(fn () => IndexNow::submit($post->indexNowUrls()));
            }
        });
    }

    public function isAutomated(): bool
    {
        return DB::table('editorial_publications')->where('post_id', $this->id)->exists();
    }

    public function isPublic(): bool
    {
        return $this->published_at !== null && $this->published_at <= now();
    }

    /**
     * The story in every language plus the listing that shows it.
     *
     * @return list<string>
     */
    public function indexNowUrls(): array
    {
        $listing = $this->type === PostType::CaseStudy ? 'case-studies.index' : 'blog.index';

        return collect(Seo::supportedLocales())
            ->flatMap(fn (string $locale): array => [$this->publicUrl($locale), Seo::route($listing, [], $locale)])
            ->values()->all();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    /** Canonical slugs and the permanent, original content key share one reserved namespace. */
    public function scopeWithPublicSlug(Builder $query, string $slug): Builder
    {
        return $query->where(fn (Builder $query) => $query->where('slug', $slug)->orWhere('slug_tr', $slug)->orWhere('slug_en', $slug));
    }

    public function publicSlug(?string $locale = null): string
    {
        $locale ??= Seo::currentLocale();

        return (string) ($this->getAttribute('slug_'.$locale) ?: $this->slug);
    }

    /** @return array<string, string> */
    public function alternateUrls(): array
    {
        $urls = collect(Seo::supportedLocales())->mapWithKeys(fn (string $locale): array => [$locale => $this->publicUrl($locale)])->all();

        return [...$urls, 'x-default' => $this->publicUrl(Seo::fallbackLocale())];
    }

    /** Published case studies of one project, in reading order. */
    public function scopeSeries(Builder $query, string $projectKey): Builder
    {
        return $query->published()->where('type', PostType::CaseStudy)->where('project_key', $projectKey)
            ->orderBy('sort_order')->orderBy('published_at')->orderBy('id');
    }

    /** @return Collection<int, Post> Published case studies that share this post's project. */
    public function storySeries(): Collection
    {
        return $this->type === PostType::CaseStudy && $this->project_key ? static::query()->series($this->project_key)->get() : new Collection;
    }

    /** @return array<int, array{label: string, url: string}> */
    public function caseLinks(?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        return collect((array) data_get($this->case_meta, 'links', []))->map(function ($link) use ($locale): array {
            $label = data_get($link, 'label');

            return ['label' => (string) (is_array($label) ? (collect([$label[$locale] ?? null, ...array_values($label)])->first(fn ($text) => is_string($text) && trim($text) !== '') ?? '') : $label), 'url' => (string) data_get($link, 'url')];
        })->filter(fn (array $link): bool => $link['label'] !== '' && $link['url'] !== '')->values()->all();
    }

    /** Project label in the page language: `project_name` is Turkish (primary), `project_name_en` an optional English name. */
    public function projectName(?string $locale = null): string
    {
        $english = ($locale ?? app()->getLocale()) === 'en' ? trim((string) data_get($this->case_meta, 'project_name_en')) : '';

        return $english !== '' ? $english : (string) data_get($this->case_meta, 'project_name');
    }

    /**
     * case_meta with the project name already in the page language, for views.
     *
     * @return array<string, mixed>
     */
    public function caseMeta(?string $locale = null): array
    {
        return ['project_name' => $this->projectName($locale)] + (array) $this->case_meta;
    }

    public function text(string $field, ?string $locale = null): string
    {
        $value = data_get($this->translations, ($locale ?? app()->getLocale()).'.'.$field);

        return is_string($value) && trim(strip_tags($value)) !== '' ? $value : (string) $this->getAttribute($field);
    }

    /** Search-result title: the language's own meta title, else its page title. */
    public function metaTitle(?string $locale = null): string
    {
        return Seo::clip($this->metaValue('meta_title', 'title', $locale), 70);
    }

    /** Search-result description: the language's own meta description, else its excerpt, at most 160 characters. */
    public function metaDescription(?string $locale = null): string
    {
        return Seo::metaDescription($this->metaValue('meta_description', 'excerpt', $locale));
    }

    private function metaValue(string $field, string $fallback, ?string $locale): string
    {
        $locale ??= app()->getLocale();
        $own = $locale === self::PRIMARY_LOCALE
            ? (string) $this->getAttribute($field)
            : (string) data_get($this->translations, $locale.'.'.$field);

        return trim($own) !== '' ? trim($own) : $this->text($fallback, $locale);
    }

    public function renderedContent(?string $locale = null): string
    {
        return CodeBlocks::render(Str::sanitizeHtml($this->text('content', $locale)));
    }

    public function publicUrl(?string $locale = null): string
    {
        return Seo::route($this->type === PostType::CaseStudy ? 'case-studies.show' : 'blog.show', ['slug' => $this->publicSlug($locale)], $locale);
    }

    /** Preserve existing RSS item identity when its canonical language URL changes. */
    public function feedGuid(string $locale): string
    {
        return Seo::route($this->type === PostType::CaseStudy ? 'case-studies.show' : 'blog.show', ['slug' => $this->slug], $locale);
    }

    /** Share image: the uploaded cover, else the generated title card for this language (npm run og), else the generic card. */
    public function ogImage(?string $locale = null): string
    {
        if ($cover = $this->coverUrl()) {
            return $cover;
        }
        $card = 'og/stories/'.$this->slug.'-'.($locale ?? app()->getLocale()).'.png';

        return is_file(public_path($card)) ? $card : 'og-blog-post.png';
    }

    public function coverUrl(): ?string
    {
        if (! $this->cover_image) {
            return null;
        }
        if (Str::startsWith($this->cover_image, ['https://', 'http://', '/'])) {
            return $this->cover_image;
        }

        return asset('storage/'.$this->cover_image);
    }

    public function resolveRouteBindingQuery($query, $value, $field = null): Builder
    {
        return ($field === 'slug' ? $query->published() : $query)->where($field ?? $this->getRouteKeyName(), $value);
    }
}
