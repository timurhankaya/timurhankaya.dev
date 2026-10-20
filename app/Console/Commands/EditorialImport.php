<?php

namespace App\Console\Commands;

use App\Services\Editorial\EditorialPublisher;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Throwable;

class EditorialImport extends Command
{
    protected $signature = 'editorial:import {file : Public bundle JSON, or - for stdin}';

    protected $description = 'Atomically publish a verified public editorial bundle (idempotent)';

    public function handle(EditorialPublisher $publisher): int
    {
        try {
            $source = $this->argument('file') === '-' ? STDIN : fopen($this->argument('file'), 'r');
            $json = stream_get_contents($source, 300001);
            if (strlen($json) > 300000) {
                throw new \RuntimeException('Bundle exceeds the import size limit.');
            }
            $this->line(json_encode($publisher->import(json_decode($json, true, flags: JSON_THROW_ON_ERROR)), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $message = $exception instanceof ValidationException ? implode(' ', $exception->validator->errors()->all()) : 'Editorial import failed; inspect the private application log.';
            if (! $exception instanceof ValidationException) {
                report($exception);
            }
            $this->line(json_encode(['status' => 'failed', 'error' => $message]));

            return self::FAILURE;
        }
    }
}
