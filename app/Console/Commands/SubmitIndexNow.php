<?php

namespace App\Console\Commands;

use App\Support\IndexNow;
use App\Support\Sitemap;
use Illuminate\Console\Command;

class SubmitIndexNow extends Command
{
    protected $signature = 'seo:indexnow';

    protected $description = 'Submit every sitemap address to IndexNow (Bing, Yandex and the other participating engines)';

    public function handle(): int
    {
        if (IndexNow::key() === null) {
            $this->error('Set INDEXNOW_KEY first; the key file is then served at /<key>.txt.');

            return self::FAILURE;
        }
        $urls = Sitemap::urls();
        if (! IndexNow::submit($urls)) {
            $this->error('IndexNow did not accept the submission.');

            return self::FAILURE;
        }
        $this->info('submitted: '.count($urls).' addresses');

        return self::SUCCESS;
    }
}
