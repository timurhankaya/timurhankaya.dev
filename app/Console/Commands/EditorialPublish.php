<?php

namespace App\Console\Commands;

use App\Services\Editorial\EditorialBundle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class EditorialPublish extends Command
{
    protected $signature = 'editorial:publish {file : Private bundle JSON} {--dry-run : Validate without contacting production}';

    protected $description = 'Publish a verified bundle over SSH and write local external-share drafts';

    public function handle(EditorialBundle $bundles): int
    {
        try {
            if (! app()->isLocal()) {
                throw new RuntimeException('Publishing must be initiated from the local environment.');
            }
            $private = json_decode(file_get_contents($this->argument('file')), true, flags: JSON_THROW_ON_ERROR);
            $public = $bundles->prepare($private);
            if ($this->option('dry-run')) {
                $this->line(json_encode(['status' => 'validated', 'bundle_id' => $public['id']]));

                return self::SUCCESS;
            }
            $host = (string) config('editorial.ssh_host');
            $path = (string) config('editorial.remote_path');
            $php = (string) config('editorial.remote_php');
            if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9._@-]*$/', $host) || ! preg_match('~^/[A-Za-z0-9/_.-]+$~', $path) || str_contains($path, '..') || ! preg_match('~^[A-Za-z0-9/_.-]+$~', $php) || str_starts_with($php, '-')) {
                throw new RuntimeException('Configure a valid editorial SSH host, application directory and PHP executable.');
            }
            // SSH stdin transfers only the sealed public payload. Source evidence stays on this computer.
            $remote = 'cd '.escapeshellarg($path).' && '.escapeshellarg($php).' artisan editorial:import - --no-ansi';
            $process = new Process(['ssh', '-o', 'BatchMode=yes', '-o', 'ConnectTimeout=10', '--', $host, $remote]);
            $process->setInput(json_encode($public, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))->setTimeout(120)->run();
            $lines = array_values(array_filter(explode("\n", trim($process->getOutput()))));
            $result = $lines ? json_decode(end($lines), true) : null;
            if (! $process->isSuccessful() || ! is_array($result) || ! in_array($result['status'] ?? '', ['published', 'existing', 'suppressed'], true)) {
                throw new RuntimeException($result['error'] ?? 'SSH publication failed. Retry the unchanged bundle after resolving the connection; its id prevents duplicate publication.');
            }
            if (($result['bundle_id'] ?? null) !== $public['id']) {
                throw new RuntimeException('The remote import returned a different bundle id.');
            }
            if ($result['status'] !== 'suppressed') {
                foreach (['tr', 'en'] as $locale) {
                    $url = $result['urls'][$locale] ?? null;
                    if (! is_string($url) || ! str_starts_with($url, 'https://timurhankaya.dev/')) {
                        throw new RuntimeException('The remote import returned an unexpected public address.');
                    }
                    $response = Http::timeout(20)->get($url);
                    if (! $response->successful() || ! str_contains(html_entity_decode($response->body(), ENT_QUOTES | ENT_HTML5), $result['titles'][$locale])) {
                        throw new RuntimeException('The post exists but its live page could not be verified. Retry the same bundle; do not generate a new id.');
                    }
                }
                $directory = storage_path('app/private/editorial/published/'.$public['topic_key']);
                if (! is_dir($directory)) {
                    mkdir($directory, 0700, true);
                }
                file_put_contents($directory.'/receipt.json', json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                foreach (['tr', 'en'] as $locale) {
                    $text = $private['story'][$locale];
                    $url = $result['urls'][$locale];
                    $draft = '# '.$text['title']."\n\n".$text['excerpt']."\n\n".$url."\n\n---\n\n";
                    $draft .= $locale === 'tr' ? 'LinkedIn için taslak; yayımlanmadı. DEV.to uyarlamasında bu adresi canonical olarak kullan.' : 'Draft for LinkedIn; not published. Use this URL as the canonical address for a DEV.to adaptation.';
                    file_put_contents($directory.'/linkedin-'.$locale.'.md', $draft."\n");
                    $dev = "---\ntitle: ".json_encode($text['title'], JSON_UNESCAPED_UNICODE)."\npublished: false\ncanonical_url: ".$url."\n---\n\n".$text['excerpt']."\n\n[".($locale === 'tr' ? 'Yazının tamamı' : 'Read the full article').']('.$url.")\n";
                    file_put_contents($directory.'/devto-'.$locale.'.md', $dev);
                }
                $result['drafts'] = $directory;
            }
            $this->line(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception instanceof ValidationException ? implode(' ', $exception->validator->errors()->all()) : $exception->getMessage());

            return self::FAILURE;
        }
    }
}
