<?php

namespace App\Http\Controllers;

use App\Enums\PostType;
use App\Models\Post;
use App\Services\Publishing\PostContent;
use App\Support\Seo;
use Illuminate\Http\Request;

class PreviewPostController extends Controller
{
    public function __invoke(Request $request, int $id, PostContent $content)
    {
        if (! $request->user()) {
            return redirect()->route('filament.admin.auth.login');
        }
        abort_unless($request->user()->is_admin, 403);
        $locale = $request->query('locale');
        if (in_array($locale, Seo::supportedLocales(), true)) {
            app()->setLocale($locale);
        }
        $post = Post::withTrashed()->findOrFail($id);

        return response()->view($post->type === PostType::CaseStudy ? 'pages.case-study' : 'pages.blog.show', [
            'post' => $post, 'isPreview' => true, 'related' => collect(), ...$content->prepare($post),
        ])->header('X-Robots-Tag', 'noindex, nofollow')->header('Cache-Control', 'private, no-store');
    }
}
