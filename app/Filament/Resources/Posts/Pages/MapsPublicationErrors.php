<?php

namespace App\Filament\Resources\Posts\Pages;

use Illuminate\Validation\ValidationException;

trait MapsPublicationErrors
{
    protected function persistWithFieldErrors(callable $callback)
    {
        try {
            return $callback();
        } catch (ValidationException $error) {
            $messages = [];
            foreach ($error->errors() as $field => $errors) {
                $messages['data.'.$field] = $errors;
            }
            throw ValidationException::withMessages($messages);
        }
    }
}
