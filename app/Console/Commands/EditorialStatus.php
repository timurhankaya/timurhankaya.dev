<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class EditorialStatus extends Command
{
    protected $signature = 'editorial:status {--remote : Read the production publication ledger over SSH}';

    protected $description = 'Read remaining editorial capacity before collecting or researching sources';

    public function handle(): int
    {
        try {
            if ($this->option('remote')) {
                if (! app()->isLocal()) {
                    throw new RuntimeException('Remote status must be initiated locally.');
                }
                $host = (string) config('editorial.ssh_host');
                $path = (string) config('editorial.remote_path');
                $php = (string) config('editorial.remote_php');
                if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9._@-]*$/', $host) || ! preg_match('~^/[A-Za-z0-9/_.-]+$~', $path) || str_contains($path, '..') || ! preg_match('~^[A-Za-z0-9/_.-]+$~', $php) || str_starts_with($php, '-')) {
                    throw new RuntimeException('Configure valid editorial SSH settings.');
                }
                $command = 'cd '.escapeshellarg($path).' && '.escapeshellarg($php).' artisan editorial:status --no-ansi';
                $process = new Process(['ssh', '-o', 'BatchMode=yes', '-o', 'ConnectTimeout=10', '--', $host, $command]);
                $process->setTimeout(25)->run();
                $lines = array_values(array_filter(explode("\n", trim($process->getOutput()))));
                $status = $lines ? json_decode(end($lines), true) : null;
                if (! $process->isSuccessful() || ! is_array($status) || ($status['status'] ?? null) !== 'ok'
                    || ! is_int($status['remaining'] ?? null) || ! is_int($status['recent_publications'] ?? null)
                    || ! is_int($status['weekly_limit'] ?? null) || ! isset($status['checked_at'])) {
                    throw new RuntimeException('Production editorial status could not be verified; stop before collection.');
                }
                $status['publish_enabled'] = (bool) config('editorial.publish_enabled');
            } else {
                $checked = CarbonImmutable::now();
                $limit = (int) config('editorial.weekly_limit');
                $recent = DB::table('editorial_publications')->where('created_at', '>', $checked->subDays(7));
                $count = (clone $recent)->count();
                $next = $count >= $limit
                    ? (clone $recent)->orderBy('created_at')->offset($count - $limit)->value('created_at')
                    : null;
                $status = [
                    'status' => 'ok', 'checked_at' => $checked->toIso8601String(),
                    'weekly_limit' => $limit, 'recent_publications' => $count,
                    'remaining' => max(0, $limit - $count),
                    'next_slot_at' => $next ? CarbonImmutable::parse($next)->addDays(7)->toIso8601String() : null,
                ];
            }
            $this->line(json_encode($status, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            // SSH diagnostics can contain private connection details.
            $this->line(json_encode(['status' => 'failed', 'error' => 'Editorial status unavailable; verify the connection and command installation.']));

            return self::FAILURE;
        }
    }
}
