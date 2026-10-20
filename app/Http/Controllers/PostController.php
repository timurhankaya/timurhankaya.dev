<?php

namespace App\Http\Controllers;

use App\Enums\PostType;
use App\Models\Post;
use App\Services\Portfolio\ProjectCatalog;
use App\Services\Publishing\PostContent;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class PostController extends Controller
{
    public function index(Request $request)
    {
        // Writing and Case study are separate sections; the old type filters point at their own pages.
        $target = match ($request->query('type')) {
            PostType::CaseStudy->value => 'case-studies.index',
            PostType::Article->value => 'blog.index',
            default => null,
        };
        if ($target !== null) {
            return redirect(Seo::route($target, $request->except(['type', 'lang', 'project'])), 301);
        }

        $posts = Post::published()->where('type', PostType::Article)->latest('published_at')->paginate(10);
        abort_if($posts->currentPage() > max(1, $posts->lastPage()), 404);

        return view('pages.blog.index', [
            'posts' => $posts, 'type' => PostType::Article, 'caseIndex' => false,
            'listing' => $this->listing('blog.index', $posts, __('brand.blog_meta_title'), __('brand.blog_meta_description')),
        ]);
    }

    public function caseIndex(Request $request)
    {
        if ($request->query->has('project')) {
            return redirect(Seo::route('case-studies.index'), 301);
        }

        $order = collect(ProjectCatalog::STORY_ORDER)->map(fn (string $key, int $i): string => 'WHEN ? THEN '.$i)->implode(' ');
        $posts = Post::published()->where('type', PostType::CaseStudy)
            ->orderByRaw('CASE project_key '.$order.' ELSE '.count(ProjectCatalog::STORY_ORDER).' END', ProjectCatalog::STORY_ORDER)
            ->orderBy('sort_order')->orderBy('published_at')->orderBy('id')->paginate(24);
        abort_if($posts->currentPage() > max(1, $posts->lastPage()), 404);

        return view('pages.blog.index', [
            'posts' => $posts, 'type' => PostType::CaseStudy, 'caseIndex' => true,
            'listing' => $this->listing('case-studies.index', $posts, __('brand.case_meta_title'), __('brand.case_meta_description')),
        ]);
    }

    /**
     * Canonical, title, description and prev/next for a listing. Only `page` ever
     * reach the address, so tracking parameters and out-of-range values never become indexable URLs.
     *
     * @return array{canonical: string, title: string, description: string, prev: ?string, next: ?string}
     */
    private function listing(string $route, LengthAwarePaginator $posts, string $metaTitle, string $metaDescription): array
    {
        $page = $posts->currentPage();
        $url = fn (int $n): string => Seo::route($route, array_filter(['page' => $n > 1 ? $n : null]));
        $title = $metaTitle.($page > 1 ? ' ('.mb_strtolower(__('brand.page_n', ['n' => $page])).')' : '');

        return [
            'canonical' => $url($page), 'title' => $title,
            'description' => $page > 1 ? $metaDescription.' '.__('brand.page_n', ['n' => $page]).'.' : $metaDescription,
            'prev' => $page > 1 ? $url($page - 1) : null, 'next' => $posts->hasMorePages() ? $url($page + 1) : null,
        ];
    }

    public function show(string $slug, PostContent $content)
    {
        $post = Post::published()->withPublicSlug($slug)->firstOrFail();
        if ($post->type === PostType::CaseStudy || $slug !== $post->publicSlug()) {
            return redirect($post->publicUrl(), 301);
        }

        return view('pages.blog.show', ['post' => $post, ...$content->prepare($post)]);
    }

    public function caseShow(string $slug, PostContent $content)
    {
        $post = Post::published()->where('type', PostType::CaseStudy)->withPublicSlug($slug)->firstOrFail();
        if ($slug !== $post->publicSlug()) {
            return redirect($post->publicUrl(), 301);
        }
        $series = $post->storySeries();
        $position = $series->search(fn (Post $story): bool => $story->is($post));

        return view('pages.case-study', [
            'post' => $post,
            'series' => $series,
            'previous' => is_int($position) && $position > 0 ? $series[$position - 1] : null,
            'next' => is_int($position) ? $series->get($position + 1) : null,
            'writing' => $post->project_key ? Post::published()->where('type', PostType::Article)->where('project_key', $post->project_key)->latest('published_at')->limit(3)->get() : collect(),
            'related' => Post::published()->where('type', PostType::CaseStudy)->whereNotIn('id', $series->pluck('id')->push($post->id)->all())
                ->orderBy('sort_order')->latest('published_at')->limit(2)->get(),
            ...$content->prepare($post),
        ]);
    }
}
