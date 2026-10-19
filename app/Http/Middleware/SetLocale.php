<?php

namespace App\Http\Middleware;

use App\Support\Seo;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $pathLocale = $request->routeIs('en.home') ? null : $request->route('locale');
        if ($pathLocale === null && in_array($request->segment(1), Seo::supportedLocales(), true)) {
            $pathLocale = $request->segment(1);
        }
        $queryLocale = $request->query('lang');
        $rememberedLocale = $request->hasSession() ? $request->session()->get('locale') : null;
        // English paths carry no prefix, so an explicit ?lang= choice outranks their route default.
        $unprefixed = str_starts_with((string) $request->route()?->getName(), 'en.');
        $locale = collect($unprefixed ? [$queryLocale, $pathLocale, $rememberedLocale] : [$pathLocale, $queryLocale, $rememberedLocale])
            ->first(fn ($candidate): bool => is_string($candidate) && in_array($candidate, Seo::supportedLocales(), true));
        $locale ??= $request->getPreferredLanguage(Seo::supportedLocales()) ?: Seo::fallbackLocale();

        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);

        // Background updates keep their page language without changing a user's
        // preference selected in another tab.
        if ($request->hasSession() && ! $request->hasHeader('X-Livewire')) {
            $request->session()->put('locale', $locale);
        }

        // The prefix configures the request; controller arguments remain unchanged.
        if ($pathLocale !== null) {
            $request->route()->forgetParameter('locale');

            if ($request->isMethod('GET') && $request->query->has('lang')) {
                return redirect(Seo::localizedUrl($request->fullUrl(), $locale), 301);
            }
        }

        return $next($request);
    }
}
