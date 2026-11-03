<?php

namespace App\Http\Controllers;

use App\Enums\PostType;
use App\Models\Post;
use App\Services\Publishing\PostContent;
use App\Services\Publishing\StoryDrafts;
use App\Support\Seo;
use Illuminate\Http\Request;

class PreviewPostController extends Controller
{
    public function __invoke(Request $request, int $id, PostContent $content)
    {
        if ($response = $this->authorizePreview($request)) {
            return $response;
        }
        $locale = $request->query('locale');
        if (in_array($locale, Seo::supportedLocales(), true)) {
            app()->setLocale($locale);
        }
        $post = Post::withTrashed()->findOrFail($id);

        return $this->render($post, $content);
    }

    public function bySlug(Request $request, string $slug, PostContent $content, ?PostType $type = null)
    {
        if ($response = $this->authorizePreview($request)) {
            return $response;
        }
        $post = Post::withPublicSlug($slug)->when($type, fn ($query) => $query->where('type', $type))->firstOrFail();
        if ($request->url() !== $post->publicUrl()) {
            return redirect($post->publicUrl().'?draft=1')->header('Cache-Control', 'private, no-store')
                ->header('X-Robots-Tag', 'noindex, nofollow');
        }

        return $this->render($post, $content);
    }

    private function authorizePreview(Request $request)
    {
        if (! $request->user()) {
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('filament.admin.auth.login')->header('Cache-Control', 'private, no-store')
                ->header('X-Robots-Tag', 'noindex, nofollow');
        }
        abort_unless($request->user()->is_admin, 403);

        return null;
    }

    private function render(Post $post, PostContent $content)
    {
        $post = app(StoryDrafts::class)->preview($post);

        return response()->view($post->type === PostType::CaseStudy ? 'pages.case-study' : 'pages.blog.show', [
            'post' => $post, 'isPreview' => true, 'related' => collect(), ...$content->prepare($post),
        ])->header('X-Robots-Tag', 'noindex, nofollow')->header('Cache-Control', 'private, no-store');
    }
}
