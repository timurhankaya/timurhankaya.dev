<?php

use App\Support\StoryStyle;

it('accepts a story written the way the voice guide asks', function () {
    expect(StoryStyle::violations(cleanStory()))->toBe([]);
});

it('flags titles with a colon, a from-to pattern or more than 70 characters', function () {
    expect(StoryStyle::violations(cleanStory(['en' => ['title' => 'Building Mona: a tool loop']])))->toContain('en: title has a colon');
    expect(StoryStyle::violations(cleanStory(['tr' => ['title' => 'Monafy: fiyatlar']])))->toContain('tr: title has a colon');
    expect(StoryStyle::violations(cleanStory(['en' => ['title' => 'From keyword gates to a tool loop']])))->toContain('en: title uses a from-to pattern');
    expect(StoryStyle::violations(cleanStory(['en' => ['title' => str_repeat('a', 71)]])))->toContain('en: title is longer than 70 characters');
});

it('flags banned words in titles and content in both languages', function () {
    expect(StoryStyle::violations(cleanStory(['en' => ['content' => '<p>We added guardrails. kelime</p>']])))->toContain('en: banned word “guardrails”');
    expect(StoryStyle::violations(cleanStory(['tr' => ['title' => 'Kusursuz bir deploy nasıl bozuldu?']])))->toContain('tr: banned word “kusursuz”');
});

it('limits case studies to 350-600 and articles to 500-1000 English words, with shorter Turkish ranges', function () {
    expect(StoryStyle::violations(cleanStory(['en' => ['content' => storyParagraphs(1100)]])))->toContain('en: case study has 1104 words, expected 350-600')
        ->and(StoryStyle::violations(cleanStory(['tr' => ['content' => storyParagraphs(200)]])))->toContain('tr: case study has 204 words, expected 280-500')
        ->and(StoryStyle::violations(cleanStory(['type' => 'article', 'project_key' => null, 'en' => ['content' => storyParagraphs(1500)]])))->toContain('en: article has 1504 words, expected 500-1000')
        ->and(StoryStyle::violations(cleanStory(['type' => 'article', 'project_key' => null, 'en' => ['content' => storyParagraphs(800)], 'tr' => ['content' => storyParagraphs(600)]])))->toBe([]);
});

it('keeps every paragraph short', function () {
    $long = fn (int $n): string => '<p>'.implode(' ', array_fill(0, $n, 'kelime')).'</p>';

    expect(StoryStyle::violations(cleanStory(['en' => ['content' => storyParagraphs(700).$long(90)]])))->toContain('en: a paragraph has 90 words, keep it to 75')
        ->and(StoryStyle::violations(cleanStory(['tr' => ['content' => storyParagraphs(600).$long(65)]])))->toContain('tr: a paragraph has 65 words, keep it to 60');
});

it('requires the story to open in prose, not with a heading', function () {
    expect(StoryStyle::violations(cleanStory(['tr' => ['content' => '<h2>Bağlam</h2><p>x</p>']])))->toContain('tr: story opens with a heading, not prose');
});

it('reports section headings reused across stories in the same language', function () {
    $a = cleanStory(['slug' => 'a']);
    $b = cleanStory(['slug' => 'b', 'en' => ['content' => '<p>Other opening.</p><h2>The Table Had Bloated</h2>']]);
    expect(StoryStyle::sharedHeadings([$a, $b]))->toBe(['en: the table had bloated', 'tr: tablo şişmişti']);
    expect(StoryStyle::sharedHeadings([$a]))->toBe([]);
});

it('keeps every shipped story inside the voice guide', function () {
    $stories = collect(glob(resource_path('content/stories/*.json')))
        ->flatMap(fn (string $file): array => json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR))->all();
    $problems = collect($stories)->flatMap(fn (array $story): array => array_map(fn (string $v): string => $story['slug'].' — '.$v, StoryStyle::violations($story)))
        ->merge(array_map(fn (string $h): string => 'shared heading — '.$h, StoryStyle::sharedHeadings($stories)))->all();
    expect($problems)->toBe([]);
});

it('keeps blog articles anonymous or about the owner\'s own projects, and makes those link the project', function () {
    $article = fn (array $overrides) => cleanStory(['type' => 'article', 'en' => ['content' => storyParagraphs(700, 'One evening.')], 'tr' => ['content' => storyParagraphs(550)], ...$overrides]);
    $linked = '<p>Bir akşam <a href="https://monafy.com/tr/">Monafy</a> durdu.</p>'.storyParagraphs(800);

    expect(StoryStyle::violations($article(['project_key' => null])))->toBe([])
        ->and(StoryStyle::violations($article(['project_key' => 'moneo-render'])))->toContain('article is not about one of the owner\'s own projects')
        ->and(StoryStyle::violations($article(['project_key' => 'edanisman'])))->toContain('article is not about one of the owner\'s own projects')
        ->and(StoryStyle::violations($article(['project_key' => 'monafy'])))->toContain('tr: article does not link https://monafy.com')
        ->and(StoryStyle::violations($article(['project_key' => 'monafy', 'tr' => ['content' => $linked], 'en' => ['content' => str_replace('/tr/', '/', $linked)]])))->toBe([]);
});

it('allows at most one code block per story', function () {
    $code = '<pre><code class="language-php">$x = 1;</code></pre>';

    expect(StoryStyle::violations(cleanStory(['en' => ['content' => storyParagraphs(700).$code.$code]])))->toContain('en: 2 code blocks, keep at most one')
        ->and(StoryStyle::violations(cleanStory(['en' => ['content' => storyParagraphs(450).$code]])))->toBe([]);
});
