<?php

namespace App\Services\Editorial;

use RuntimeException;
use Symfony\Component\Process\Process;

final class RepositoryCollector
{
    public static function safePath(string $path): bool
    {
        return $path !== '' && ! str_starts_with($path, '/') && ! str_contains($path, '..')
            && ! preg_match('~(^|/)(?:\.[^/]+|vendor|node_modules|storage|public|reports|credentials|secrets)(/|$)|\.(?:pem|key|crt|log|csv|sql|sqlite|lock)$~i', $path)
            && (bool) preg_match('~\.(?:php|ts|js|vue|py|md|json|yml|yaml|toml|css)$~', $path);
    }

    /** @return array<string, string> canonical identity => repository path */
    public function repositories(string $root): array
    {
        $root = realpath($root) ?: throw new RuntimeException('The configured source root does not exist.');
        $candidates = [$root, ...(glob($root.'/*', GLOB_ONLYDIR) ?: []), ...(glob($root.'/packages/*', GLOB_ONLYDIR) ?: [])];
        $repos = [];
        foreach ($candidates as $path) {
            $real = realpath($path);
            if (! $real || ($real !== $root && ! str_starts_with($real, $root.'/')) || ! file_exists($real.'/.git')) {
                continue;
            }
            if (preg_match('~/(?:\.worktrees|worktrees|vendor|node_modules|docs|reports)(/|$)~', $real)) {
                continue;
            }
            $common = trim($this->git($real, ['rev-parse', '--git-common-dir']));
            $common = realpath(str_starts_with($common, '/') ? $common : $real.'/'.$common);
            $repos[hash('sha256', $common)] ??= $real;
        }

        return $repos;
    }

    public function collect(string $root, array $cursors = [], bool $fetch = true, ?string $since = null): array
    {
        $historical = $since !== null;
        $since ??= '-30 days';
        if (strtotime($since) === false) {
            throw new RuntimeException('Invalid starting date.');
        }
        $repositories = [];
        foreach ($this->repositories($root) as $identity => $path) {
            if ($fetch) {
                $this->git($path, ['fetch', '--quiet', 'origin']);
            }
            $ref = trim($this->git($path, ['symbolic-ref', '--quiet', 'refs/remotes/origin/HEAD'], false));
            if ($ref === '') {
                $ref = $this->git($path, ['rev-parse', '--verify', 'origin/main'], false) !== '' ? 'origin/main' : 'origin/master';
            }
            $head = trim($this->git($path, ['rev-parse', '--verify', $ref]));
            $cursor = $cursors[$identity] ?? null;
            $validCursor = ! $historical && is_string($cursor) && preg_match('/^[a-f0-9]{40}$/', $cursor)
                && $this->git($path, ['merge-base', '--is-ancestor', $cursor, $head], false, true) === 'ok';
            $range = $validCursor ? [$cursor.'..'.$head] : ['--since='.$since, $head];
            $shas = array_values(array_filter(explode("\n", trim($this->git($path, ['rev-list', '--reverse', '--no-merges', ...$range])))));
            $selected = array_slice($shas, 0, 120);
            $to = count($shas) > count($selected) ? end($selected) : $head;
            $commits = [];
            foreach ($selected as $sha) {
                $files = explode("\n", trim($this->git($path, ['diff-tree', '--root', '--no-commit-id', '--name-only', '-r', $sha])));
                $files = array_values(array_filter($files, self::safePath(...)));
                if ($files !== []) {
                    $subject = trim($this->git($path, ['show', '-s', '--format=%s', $sha]));
                    $subject = preg_replace('~(?:sk-[A-Za-z0-9_-]+|https?://[^\s]+|[A-Za-z0-9_.+-]+@[A-Za-z0-9.-]+)~', '[redacted]', $subject);
                    $commits[] = ['sha' => $sha, 'subject' => $subject, 'files' => array_slice($files, 0, 200)];
                }
            }
            $repositories[] = ['identity' => $identity, 'repository' => ltrim(substr($path, strlen(rtrim(realpath($root), '/'))), '/') ?: basename($path), 'path' => $path, 'ref' => $ref, 'head' => $head, 'cursor' => $to, 'cursor_reset' => ! $historical && $cursor !== null && ! $validCursor, 'commits' => $commits];
        }
        if ($repositories === []) {
            throw new RuntimeException('No source repositories were found.');
        }

        return ['version' => 1, 'generated_at' => now()->toIso8601String(), 'repositories' => $repositories];
    }

    public function git(string $path, array $arguments, bool $required = true, bool $statusOnly = false): string
    {
        // Keep stderr private: fetch failures can include authenticated remote addresses.
        $command = new Process(['git', '-C', $path, ...$arguments]);
        $command->setTimeout(90)->run();
        if ($required && ! $command->isSuccessful()) {
            throw new RuntimeException('Git source operation failed for '.basename($path).'.');
        }

        return $statusOnly ? ($command->isSuccessful() ? 'ok' : '') : ($command->isSuccessful() ? $command->getOutput() : '');
    }
}
