<?php

function storiesFile(array $stories): string
{
    $file = tempnam(sys_get_temp_dir(), 'stories').'.json';
    file_put_contents($file, json_encode($stories));

    return $file;
}

it('exits non-zero and names the story and the rule when a stories file breaks the voice guide', function () {
    $file = storiesFile([cleanStory(['slug' => 'bad-one', 'en' => ['title' => 'Bad: title']])]);
    $this->artisan('stories:lint', ['file' => $file])->expectsOutputToContain('bad-one — en: title has a colon')->assertExitCode(1);
});

it('reports headings shared between stories in the same file', function () {
    $file = storiesFile([cleanStory(['slug' => 'one']), cleanStory(['slug' => 'two'])]);
    $this->artisan('stories:lint', ['file' => $file])->expectsOutputToContain('shared heading — en: the table had bloated')->assertExitCode(1);
});

it('exits zero for a clean file', function () {
    $file = storiesFile([cleanStory()]);
    $this->artisan('stories:lint', ['file' => $file])->expectsOutputToContain('clean')->assertExitCode(0);
});
