<?php

namespace App\Console\Commands;

use App\Support\StoryStyle;
use Illuminate\Console\Command;

class LintStories extends Command
{
    protected $signature = 'stories:lint {file : Path to a stories JSON file}';

    protected $description = 'Check a stories JSON file against the writing voice guide';

    public function handle(): int
    {
        $stories = json_decode((string) file_get_contents($this->argument('file')), true, flags: JSON_THROW_ON_ERROR);
        $problems = [];
        foreach ($stories as $story) {
            foreach (StoryStyle::violations($story) as $violation) {
                $problems[] = ($story['slug'] ?? '?').' — '.$violation;
            }
        }
        foreach (StoryStyle::sharedHeadings($stories) as $heading) {
            $problems[] = 'shared heading — '.$heading;
        }
        if ($problems === []) {
            $this->info('clean: '.count($stories).' stories');

            return self::SUCCESS;
        }
        foreach ($problems as $problem) {
            $this->line($problem);
        }

        return self::FAILURE;
    }
}
