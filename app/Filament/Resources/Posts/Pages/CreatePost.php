<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePost extends CreateRecord
{
    use MapsPublicationErrors;

    protected function handleRecordCreation(array $data): Model
    {
        return $this->persistWithFieldErrors(fn () => parent::handleRecordCreation($data));
    }

    protected static string $resource = PostResource::class;
}
