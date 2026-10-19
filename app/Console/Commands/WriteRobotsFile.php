<?php

namespace App\Console\Commands;

use App\Http\Controllers\SitemapController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;

/**
 * Forge's nginx serves /robots.txt straight from disk and never asks the app, so the route's output
 * is written to public/robots.txt. Run it again whenever the crawler groups or rules change.
 */
class WriteRobotsFile extends Command
{
    protected $signature = 'seo:robots {--path= : Where to write (default public/robots.txt)} {--url= : Site address for the sitemap line (default APP_URL)}';

    protected $description = 'Write public/robots.txt from the robots route for the given site address';

    public function handle(SitemapController $sitemap): int
    {
        $root = rtrim((string) ($this->option('url') ?: config('app.url')), '/');
        URL::forceRootUrl($root);
        URL::forceScheme((string) parse_url($root, PHP_URL_SCHEME));
        $path = (string) ($this->option('path') ?: public_path('robots.txt'));
        file_put_contents($path, $sitemap->robots()->getContent());
        $this->info('written: '.$path);

        return self::SUCCESS;
    }
}
