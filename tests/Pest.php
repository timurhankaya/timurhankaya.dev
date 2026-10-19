<?php

use App\Enums\PostType;
use App\Models\Post;
use App\Support\ContentStamp;
use Illuminate\Support\Str;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    // Content stamps go to a throwaway file so tests never touch storage/app.
    ->beforeEach(fn () => ContentStamp::$path = tempnam(sys_get_temp_dir(), 'stamps'))
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function portfolioPost(array $attributes = []): Post
{
    return Post::create(array_replace([
        'title' => 'A thoughtful product', 'slug' => 'a-thoughtful-product', 'excerpt' => 'The thinking behind a product.',
        'content' => '<h2>Approach</h2><p>Clear decisions and careful implementation.</p>', 'published_at' => now(),
    ], $attributes));
}

function caseStudy(array $attributes = []): Post
{
    return portfolioPost(array_replace_recursive([
        'title' => 'Prices vanished at the close', 'slug' => 'prices-vanished-at-the-close',
        'excerpt' => 'Live prices stopped updating near the US close.',
        'type' => PostType::CaseStudy, 'project_key' => 'monafy',
        'case_meta' => ['project_name' => 'Monafy', 'context' => 'employment', 'period' => '2025–', 'technologies' => ['Laravel'], 'url' => 'https://monafy.com'],
    ], $attributes));
}

/** The page's own content, without the header, footer or command palette (which lists every published story). */
function mainContent(string $html): string
{
    return Str::between($html, '<main id="content"', '</main>');
}

/** Story body of about $words words, split into paragraphs short enough for the voice guide. */
function storyParagraphs(int $words, string $opening = 'Bir akşam fiyatlar durdu.', int $perParagraph = 50): string
{
    $chunks = array_chunk(array_fill(0, $words, 'kelime'), $perParagraph);

    return '<p>'.$opening.'</p>'.implode('', array_map(fn (array $chunk): string => '<p>'.implode(' ', $chunk).'</p>', $chunks));
}

function cleanStory(array $overrides = []): array
{
    return array_replace_recursive([
        'type' => 'case_study', 'slug' => 'clean-story', 'project_key' => 'monafy',
        'tr' => ['title' => 'Fiyatlar kapanışta neden düşüyordu?', 'excerpt' => 'Kısa cevap.', 'content' => storyParagraphs(400).'<h2>Tablo şişmişti</h2><p>Ayrıntı.</p>'],
        'en' => ['title' => 'Why did prices drop at the close?', 'excerpt' => 'Short answer.', 'content' => storyParagraphs(500, 'One evening prices stopped.').'<h2>The table had bloated</h2><p>Detail.</p>'],
    ], $overrides);
}
