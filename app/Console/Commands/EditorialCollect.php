<?php

namespace App\Console\Commands;

use App\Services\Editorial\RepositoryCollector;
use Illuminate\Console\Command;
use Throwable;

class EditorialCollect extends Command
{
    protected $signature = 'editorial:collect {--since= : Explicit historical scan; does not advance routine cursors} {--no-fetch : Use existing remote refs}';

    protected $description = 'Collect merged Monafy changes into a private local evidence scan';

    public function handle(RepositoryCollector $collector): int
    {
        if (! app()->isLocal()) {
            $this->error('Source collection runs only in the local environment.');

            return self::FAILURE;
        }
        $directory = storage_path('app/private/editorial');
        if (! is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        $lock = fopen($directory.'/collect.lock', 'c');
        if (! flock($lock, LOCK_EX | LOCK_NB)) {
            $this->error('An editorial scan is already running.');

            return self::FAILURE;
        }
        try {
            $statePath = $directory.'/cursors.json';
            $state = is_file($statePath) ? json_decode(file_get_contents($statePath), true, flags: JSON_THROW_ON_ERROR) : [];
            $scan = $collector->collect(config('editorial.source_root'), $state, ! $this->option('no-fetch'), $this->option('since'));
            $file = $directory.'/scan-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(3)).'.json';
            file_put_contents($file, json_encode($scan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), LOCK_EX);
            chmod($file, 0600);
            if ($this->option('since') === null) {
                foreach ($scan['repositories'] as $repository) {
                    $state[$repository['identity']] = $repository['cursor'];
                }
                file_put_contents($statePath.'.tmp', json_encode($state, JSON_THROW_ON_ERROR), LOCK_EX);
                chmod($statePath.'.tmp', 0600);
                rename($statePath.'.tmp', $statePath);
            }
            $this->line(json_encode(['scan' => $file, 'commits' => array_sum(array_map(fn ($repo) => count($repo['commits']), $scan['repositories']))], JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
