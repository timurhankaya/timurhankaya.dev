<?php

namespace App\Services\Editorial;

use App\Enums\PostType;
use App\Support\MonafyLinks;
use App\Support\StoryStyle;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class EditorialBundle
{
    public static function seal(string $topic, array $story, string $fingerprint): array
    {
        $payload = ['version' => 1, 'topic_key' => $topic, 'story' => $story, 'source_fingerprint' => $fingerprint];

        return [...$payload, 'id' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))];
    }

    public function prepare(array $private): array
    {
        Validator::make($private, [
            'version' => ['required', 'in:1'], 'topic_key' => ['required', 'string'], 'story' => ['required', 'array'],
            'evidence' => ['required', 'array', 'min:1', 'max:30'],
            'evidence.*.claim' => ['required', 'string', 'min:20', 'max:1000'],
            'evidence.*.sources' => ['required', 'array', 'min:1', 'max:10'],
            'evidence.*.sources.*.repository' => ['required', 'string'],
            'evidence.*.sources.*.commit' => ['required', 'regex:/^[a-f0-9]{40}$/'],
            'evidence.*.sources.*.path' => ['required', 'string'],
        ])->validate();
        $collector = app(RepositoryCollector::class);
        $root = realpath(config('editorial.source_root'));
        $allowed = array_values($collector->repositories(config('editorial.source_root')));
        $sources = [];
        foreach ($private['evidence'] as $evidence) {
            foreach ($evidence['sources'] as $source) {
                $path = realpath($root.'/'.$source['repository']);
                $this->require(in_array($path, $allowed, true), 'Unknown evidence repository.');
                $this->require(RepositoryCollector::safePath($source['path']), 'Unsafe evidence file.');
                $this->require($collector->git($path, ['merge-base', '--is-ancestor', $source['commit'], 'origin/main'], false, true) === 'ok', 'Evidence is not on the merged main branch.');
                $object = $source['commit'].':'.$source['path'];
                $this->require($collector->git($path, ['cat-file', '-t', $object], false) === "blob\n", 'Evidence file does not exist at its recorded commit.');
                $repositoryName = ltrim(substr($path, strlen($root)), '/');
                $sources[] = $repositoryName.'@'.$source['commit'];
            }
        }
        sort($sources);
        $bundle = self::seal($private['topic_key'], $private['story'], hash('sha256', implode("\n", array_unique($sources))));
        $this->validatePublic($bundle);

        return $bundle;
    }

    public function validatePublic(array $bundle): array
    {
        Validator::make($bundle, [
            'version' => ['required', 'integer', 'in:1'],
            'id' => ['required', 'regex:/^[a-f0-9]{64}$/'],
            'topic_key' => ['required', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'max:150'],
            'source_fingerprint' => ['required', 'regex:/^[a-f0-9]{64}$/'],
            'story' => ['required', 'array:type,slug,project_key,sort_order,case_meta,tr,en'],
            'story.type' => ['required', Rule::enum(PostType::class)],
            'story.slug' => ['required', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'max:200'],
            'story.project_key' => ['required', 'in:monafy'],
            'story.sort_order' => ['sometimes', 'integer', 'between:0,65535'],
            'story.case_meta' => ['sometimes', 'array:project_name,project_name_en,context,period,technologies,url,links'],
            'story.case_meta.context' => ['sometimes', 'in:founder'],
            'story.tr' => ['required', 'array:title,slug,excerpt,content,meta_title,meta_description'],
            'story.en' => ['required', 'array:title,slug,excerpt,content,meta_title,meta_description'],
        ])->validate();
        $expected = self::seal($bundle['topic_key'], $bundle['story'], $bundle['source_fingerprint']);
        $this->require(hash_equals($expected['id'], $bundle['id']), 'The bundle checksum does not match its contents.');
        $story = $bundle['story'];
        foreach (['tr', 'en'] as $locale) {
            Validator::make($story[$locale], [
                'slug' => ['sometimes', 'required', 'string', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'max:200'],
                'title' => ['required', 'string', 'max:70'], 'excerpt' => ['required', 'string', 'max:500'],
                'content' => ['required', 'string', 'max:50000'], 'meta_title' => ['required', 'string', 'max:60'],
                'meta_description' => ['required', 'string', 'min:40', 'max:160'],
            ])->validate();
            $html = $story[$locale]['content'];
            $allowed = '<p><h2><h3><ul><ol><li><strong><em><a><code><pre><figure><figcaption><div><span><table><thead><tbody><tr><th><td><blockquote>';
            $this->require(strip_tags($html, $allowed) === $html, 'The story contains unsupported HTML.');
            $this->require(! preg_match('~\s(?:on[a-z]+|style|srcdoc)\s*=|<\?|<!~i', $html), 'The story contains unsafe attributes.');
            $document = new \DOMDocument;
            $previous = libxml_use_internal_errors(true);
            $document->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            foreach ($document->getElementsByTagName('*') as $node) {
                foreach ($node->attributes as $attribute) {
                    $this->require(in_array($attribute->name, ['class', 'href'], true), 'The story contains an unsupported attribute.');
                    if ($attribute->name === 'href') {
                        $this->require($node->nodeName === 'a' && (bool) preg_match('~^(?:https://[^\\s]+|/(?!/)[^\\s]*|#[A-Za-z0-9_-]+)$~', $attribute->value), 'The story contains an unsafe link.');
                        if (str_starts_with($attribute->value, 'https://')) {
                            $this->publicUrl($attribute->value);
                            $parts = parse_url($attribute->value);
                            if (in_array(strtolower($parts['host'] ?? ''), ['monafy.com', 'www.monafy.com'], true)) {
                                $path = $parts['path'] ?? '/';
                                $this->require(($locale === 'tr' ? str_starts_with($path, '/tr/') : $path !== '/' && $path !== '' && ! preg_match('~^/tr(?:/|$)~', $path))
                                    && MonafyLinks::localized($attribute->value, $locale) === $attribute->value,
                                    'Monafy links must use a direct destination in the story language, not the language-negotiating homepage.');
                            }
                        }
                    }
                }
            }
            $text = $story[$locale]['title'].' '.$story[$locale]['excerpt'].' '.$html.' '.$story[$locale]['meta_title'].' '.$story[$locale]['meta_description'];
            $this->require(! preg_match('~/(?:Users|home|var/www)/|\b(?:sk-[A-Za-z0-9_-]{10,}|BEGIN [A-Z ]*PRIVATE KEY)\b|\b(?:password|api_key|secret)\s*[=:]|\b(?:Yahoo Finance|FMP)\b~i', $text), 'The story contains private or excluded information.');
            $this->require(! preg_match('~\bBIST\s+(?:ve|and)\s+Nasdaq\b~iu', $text), 'Use the approved Turkey and US market names.');
        }
        array_walk_recursive($story, function ($value): void {
            if (! is_string($value)) {
                return;
            }
            $decoded = $value;
            for ($pass = 0; $pass < 3; $pass++) {
                $decoded = rawurldecode(html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
            $this->require(! preg_match('~/(?:Users|home|var/www)/|\\b(?:sk-[A-Za-z0-9_-]{10,}|BEGIN [A-Z ]*PRIVATE KEY|Yahoo Finance|FMP)\\b|\\b(?:password|api_key|secret)\\s*[=:]|\\b(?:[0-9]{1,3}\\.){3}[0-9]{1,3}\\b~i', $decoded), 'The public metadata contains private or excluded information.');
            preg_match_all('~https?://[^\\s<>"\\x27]+~i', $decoded, $urls);
            foreach ($urls[0] as $url) {
                $this->publicUrl($url);
            }
        });
        $problems = StoryStyle::violations($story);
        $this->require($problems === [], implode(' ', $problems));

        return $story;
    }

    private function publicUrl(string $url): void
    {
        $parts = parse_url($url);
        $host = strtolower(trim($parts['host'] ?? '', '[]'));
        $this->require(is_array($parts) && ($parts['scheme'] ?? '') === 'https'
            && ! isset($parts['user']) && ! isset($parts['pass'])
            && (! isset($parts['port']) || $parts['port'] === 443)
            && ! filter_var($host, FILTER_VALIDATE_IP)
            && (bool) preg_match('/^[a-z0-9-]+(?:\\.[a-z0-9-]+)*\\.[a-z]{2,}$/', $host)
            && ! preg_match('/(?:^|\\.)(?:localhost|local|internal|test|invalid|lan|home|corp|arpa|onion)$/', $host)
            && ! preg_match('/^(?:api|apigw|ai-apigw|internal|staging|dev|forge|admin|redis|db)[.-]/', $host)
            && (! str_ends_with($host, '.monafy.com') || $host === 'www.monafy.com'),
            'Public stories cannot disclose private or infrastructure addresses.');
    }

    private function require(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['bundle' => $message]);
        }
    }
}
