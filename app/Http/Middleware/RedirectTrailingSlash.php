<?php

namespace App\Http\Middleware;

use App\Support\LocalizedRoutes;
use App\Support\Seo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The router answers `/writing/` and `/writing` alike, which leaves two addresses for one page.
 * Reads are sent to the address without the slash so links and crawlers settle on one.
 */
class RedirectTrailingSlash
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->getPathInfo();
        if ($path === '/' || ! str_ends_with($path, '/') || ! $request->isMethodSafe()) {
            return $next($request);
        }
        $query = $request->getQueryString();
        $segments = explode('/', trim($path, '/'));
        $locale = in_array($segments[0] ?? '', Seo::supportedLocales(), true) ? array_shift($segments) : null;
        if (LocalizedRoutes::match(implode('/', $segments)) !== null) {
            $locale ??= in_array($request->query('lang'), Seo::supportedLocales(), true) ? $request->query('lang') : 'en';

            return redirect(Seo::localizedUrl($request->fullUrl(), $locale), 301);
        }

        return redirect()->to(rtrim($path, '/').($query !== null ? '?'.$query : ''), 301);
    }
}
