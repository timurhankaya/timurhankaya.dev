<?php

namespace App\Console\Commands;

use App\Services\Editorial\EditorialBundle;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Throwable;

class EditorialValidate extends Command
{
    protected $signature = 'editorial:validate {file : Private bundle JSON}';

    protected $description = 'Validate bilingual copy and its recorded local Git evidence';

    public function handle(EditorialBundle $bundles): int
    {
        try {
            $private = json_decode(file_get_contents($this->argument('file')), true, flags: JSON_THROW_ON_ERROR);
            $public = $bundles->prepare($private);
            $this->line(json_encode(['valid' => true, 'bundle_id' => $public['id'], 'topic_key' => $public['topic_key']], JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception instanceof ValidationException ? implode(' ', $exception->validator->errors()->all()) : $exception->getMessage());

            return self::FAILURE;
        }
    }
}
