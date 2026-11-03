<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\Publishing\StoryDrafts;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class StageStory extends Command
{
    protected $signature = 'stories:stage {file : One-story JSON file} {--revision= : Exact review revision identifier} {--remote : Stage through the configured editorial SSH connection}';

    protected $description = 'Stage a private revision for admin preview without changing the published story';

    public function handle(StoryDrafts $drafts): int
    {
        try {
            $stories = json_decode(file_get_contents($this->argument('file')), true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($stories) || ! array_is_list($stories) || count($stories) !== 1 || ! is_array($stories[0])) {
                $this->error('Stage exactly one existing story at a time.');

                return self::FAILURE;
            }
            if ($this->option('remote')) {
                return $this->stageRemote($stories);
            }
            $post = Post::where('slug', $stories[0]['slug'] ?? '')->firstOrFail();
            $drafts->stage($post, $stories[0], (string) $this->option('revision'));
            $this->line(json_encode(['status' => 'staged', 'post_id' => $post->id, 'revision' => $this->option('revision'),
                'urls' => ['tr' => $post->publicUrl('tr').'?draft=1', 'en' => $post->publicUrl('en').'?draft=1']], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        } catch (ValidationException $exception) {
            $this->error(implode(' ', $exception->validator->errors()->all()));
        } catch (Throwable) {
            $this->error('The draft could not be staged. Verify the JSON file and existing story.');
        }

        return self::FAILURE;
    }

    private function stageRemote(array $stories): int
    {
        $host = (string) config('editorial.ssh_host');
        $path = (string) config('editorial.remote_path');
        $php = (string) config('editorial.remote_php');
        if (! app()->isLocal() || ! preg_match('/^[A-Za-z0-9][A-Za-z0-9._@-]*$/', $host)
            || ! preg_match('~^/[A-Za-z0-9/_.-]+$~', $path) || str_contains($path, '..')
            || ! preg_match('~^[A-Za-z0-9/_.-]+$~', $php) || str_starts_with($php, '-')) {
            throw new RuntimeException('Invalid editorial connection.');
        }
        $script = <<<'REMOTE'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$payload = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
$post = App\Models\Post::where('slug', $payload['story']['slug'] ?? '')->firstOrFail();
app(App\Services\Publishing\StoryDrafts::class)->stage($post, $payload['story'], $payload['revision']);
echo json_encode(['status' => 'staged', 'post_id' => $post->id, 'revision' => $payload['revision'],
    'urls' => ['tr' => $post->publicUrl('tr').'?draft=1', 'en' => $post->publicUrl('en').'?draft=1']], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
REMOTE;
        $process = new Process(['ssh', '-o', 'BatchMode=yes', '-o', 'ConnectTimeout=10', '--', $host,
            'cd '.escapeshellarg($path).' && '.escapeshellarg($php).' -r '.escapeshellarg($script)]);
        $process->setInput(json_encode(['story' => $stories[0], 'revision' => (string) $this->option('revision')], JSON_THROW_ON_ERROR));
        $process->setTimeout(30)->run();
        $result = json_decode(trim($process->getOutput()), true);
        if (! $process->isSuccessful() || ! is_array($result) || ($result['status'] ?? null) !== 'staged') {
            throw new RuntimeException('Remote staging failed.');
        }
        $this->line(json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
