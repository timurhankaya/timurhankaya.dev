<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * The site sits behind Cloudflare, so the connecting address is Cloudflare's and every visitor
 * would share it. Cloudflare passes the real one in CF-Connecting-IP; anything malformed falls back.
 */
final class VisitorIp
{
    public static function of(Request $request): string
    {
        $forwarded = (string) $request->header('CF-Connecting-IP', '');

        return filter_var($forwarded, FILTER_VALIDATE_IP) ? $forwarded : (string) $request->ip();
    }
}
