<?php

use App\Http\Controllers\ContactRequestController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\LlmsController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PreviewPostController;
use App\Http\Controllers\SitemapController;
use App\Services\Portfolio\PortfolioContentService;
use App\Services\Portfolio\PortfolioProjects;
use App\Support\IndexNow;
use App\Support\LocalizedRoutes;
use App\Support\Seo;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SitemapController::class, 'sitemap'])->name('sitemap');
Route::get('/site.webmanifest', [SitemapController::class, 'manifest'])->name('manifest');
// One address for both languages; the form sends its locale and the visitor returns to their page.
Route::post('/contact-requests', [ContactRequestController::class, 'store'])->middleware('throttle:contact')->name('contact.store');

Route::get('/admin/preview/posts/{id}', PreviewPostController::class)->whereNumber('id')->name('admin.posts.preview');

foreach (Seo::supportedLocales() as $locale) {
    Route::prefix($locale === 'en' ? '' : $locale)->name($locale.'.')->group(function () use ($locale): void {
        $path = fn (string $name): string => '/'.LocalizedRoutes::PATHS[$name][$locale];
        $register = function (string $name, $action) use ($path, $locale) {
            return Route::get($path($name), $action)->defaults('locale', $locale)->name($name);
        };
        $register('home', function (PortfolioContentService $portfolio, PortfolioProjects $projects) use ($locale) {
            if (request()->query->has('lang')) {
                return redirect(Seo::route('home'), 301);
            }
            if ($locale === 'en' && app()->getLocale() === 'tr') {
                return redirect(Seo::route('home'), 302)->header('Vary', 'Accept-Language, Cookie')->header('Cache-Control', 'private, no-cache');
            }

            return view('pages.home', ['content' => $portfolio->get(), 'featuredProjects' => $projects->featured()]);
        });
        $register('contact', fn (PortfolioContentService $portfolio) => view('pages.contact', ['content' => $portfolio->get()]));
        $register('cv', fn () => view('pages.cv'));
        $register('games.index', fn () => view('pages.games.index'));
        foreach (['imposter', 'vampire'] as $game) {
            $register('games.'.$game.'.index', fn () => view('pages.games.'.$game.'.index'));
            $register('games.'.$game.'.room', fn (string $roomCode) => view('pages.games.'.$game.'.room', compact('roomCode')))->where('roomCode', '[A-Z0-9]{4,10}');
        }
        $register('blog.index', [PostController::class, 'index']);
        $register('blog.show', [PostController::class, 'show']);
        $register('case-studies.index', [PostController::class, 'caseIndex']);
        $register('case-studies.show', [PostController::class, 'caseShow']);
        $register('llms', LlmsController::class);
        $register('feed', FeedController::class);
        $register('preview.typography', function () {
            abort_unless(app()->isLocal(), 404);

            return response()->view('pages.article-preview')->header('X-Robots-Tag', 'noindex, nofollow');
        });
    });
}

// IndexNow proves ownership with a root-level file named after the key; any other name stays a 404.
Route::get('/{indexNowKey}.txt', function (string $indexNowKey) {
    $key = IndexNow::key();
    abort_unless($key !== null && hash_equals($key, $indexNowKey), 404);

    return response($key, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->where('indexNowKey', '[A-Za-z0-9-]{8,128}');

// Resolve old bookmarks and mixed-language paths to their canonical translation.
Route::get('/{legacyPath?}', function (?string $legacyPath = null) {
    $segments = explode('/', trim($legacyPath ?? '', '/'));
    $prefix = in_array($segments[0] ?? null, Seo::supportedLocales(), true) ? array_shift($segments) : null;
    abort_unless(LocalizedRoutes::match(implode('/', $segments)) !== null, 404);
    $locale = $prefix ?? Seo::currentLocale();
    $permanent = $legacyPath !== null || in_array(request()->query('lang'), Seo::supportedLocales(), true);

    return redirect(Seo::localizedUrl(request()->fullUrl(), $locale), $permanent ? 301 : 302)
        ->header('Vary', 'Accept-Language, Cookie')->header('Cache-Control', 'private, no-cache');
})->where('legacyPath', '.*');
