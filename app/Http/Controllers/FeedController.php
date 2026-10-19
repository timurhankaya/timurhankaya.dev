<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Response;

class FeedController extends Controller
{
    public function __invoke(): Response
    {
        // The address decides the language, never the visitor's session or headers.
        $locale = str_starts_with((string) request()->route()?->getName(), 'tr.') ? 'tr' : 'en';
        app()->setLocale($locale);
        $posts = Post::published()->latest('published_at')->orderByDesc('id')->limit(30)->get();

        return response()->view('feed', ['posts' => $posts, 'locale' => $locale], 200)
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
