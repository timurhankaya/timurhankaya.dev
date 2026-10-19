<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostActions;
use App\Filament\Resources\Posts\PostResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPost extends EditRecord
{
    use MapsPublicationErrors;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return $this->persistWithFieldErrors(fn () => parent::handleRecordUpdate($record, $data));
    }

    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            PostActions::preview(),
            PostActions::publish(),
            PostActions::unpublish(),
            RestoreAction::make(),
        ];
    }
}
